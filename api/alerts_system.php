<?php
// File: api/alerts_system.php
// Sistema di alert automatici per KPI fuori soglia

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorizzato']);
    exit();
}

include_once '../config.php';
include '../query/qutenti.php';

$username = $_SESSION['username'];
$utente_data = get_user_data($conn, $username);
$livello = $utente_data['livello'];

/**
 * Sistema di Alert Intelligente per KPI
 */
class AlertsSystem {
    private $conn;
    private $livello;
    private $filiale;
    private $alerts = [];

    public function __construct($conn, $livello, $filiale = null) {
        $this->conn = $conn;
        $this->livello = $livello;
        $this->filiale = $filiale;
    }

    /**
     * Verifica tutti gli alert possibili
     */
    public function checkAllAlerts() {
        $this->checkTargetAlerts();
        $this->checkConsumptionAlerts();
        $this->checkCostAlerts();
        $this->checkMaintenanceAlerts();
        $this->checkAnomaliesAlerts();
        
        return $this->alerts;
    }

    /**
     * Alert per target non raggiunti
     */
    private function checkTargetAlerts() {
        $currentMonth = date('Y-m');
        $year = date('Y');
        
        $sql = "SELECT 
                    c.targa_mezzo,
                    c.username,
                    c.filiale,
                    SUM(c.chilometri_finali - c.chilometri_iniziali) as km_effettivi,
                    t.target_chilometri,
                    (t.target_chilometri / 12) as target_mensile
                FROM chilometri c
                JOIN target_annuale t ON c.username = t.username AND c.targa_mezzo = t.targa_mezzo
                WHERE DATE_FORMAT(c.data, '%Y-%m') = ? 
                    AND t.anno = ?";
        
        if ($this->livello > 1) {
            $sql .= " AND c.filiale = ?";
        }
        
        $sql .= " GROUP BY c.targa_mezzo, c.username, c.filiale, t.target_chilometri
                  HAVING (km_effettivi / target_mensile * 100) < 80";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($this->livello > 1) {
            $stmt->bind_param("sis", $currentMonth, $year, $this->filiale);
        } else {
            $stmt->bind_param("si", $currentMonth, $year);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $percentuale = ($row['km_effettivi'] / $row['target_mensile']) * 100;
            
            $this->addAlert([
                'type' => 'target',
                'severity' => $percentuale < 60 ? 'critical' : 'warning',
                'title' => 'Target Non Raggiunto',
                'message' => "Il veicolo {$row['targa_mezzo']} ({$row['username']}) ha raggiunto solo il " . 
                            number_format($percentuale, 1) . "% del target mensile",
                'data' => $row,
                'recommended_action' => 'Verificare la pianificazione operativa e contattare l\'operatore'
            ]);
        }
    }

    /**
     * Alert per consumi anomali
     */
    private function checkConsumptionAlerts() {
        $sql = "SELECT 
                    targa_mezzo,
                    username,
                    filiale,
                    AVG(
                        CASE 
                            WHEN litri_carburante IS NOT NULL 
                                AND litri_carburante != '' 
                                AND litri_carburante != '0'
                                AND (chilometri_finali - chilometri_iniziali) > 0
                            THEN (CAST(litri_carburante AS DECIMAL(10,2)) / (chilometri_finali - chilometri_iniziali)) * 100
                            ELSE NULL
                        END
                    ) as consumo_medio,
                    COUNT(*) as registrazioni
                FROM chilometri 
                WHERE DATE_FORMAT(data, '%Y-%m') >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 3 MONTH), '%Y-%m')
                    AND litri_carburante IS NOT NULL 
                    AND litri_carburante != ''
                    AND litri_carburante != '0'";
        
        if ($this->livello > 1) {
            $sql .= " AND filiale = ?";
        }
        
        $sql .= " GROUP BY targa_mezzo, username, filiale
                  HAVING consumo_medio > 10.0 OR consumo_medio IS NULL
                  ORDER BY consumo_medio DESC";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($this->livello > 1) {
            $stmt->bind_param("s", $this->filiale);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $consumo = floatval($row['consumo_medio']);
            
            if ($consumo > 12.0) {
                $severity = 'critical';
                $message = "Consumo estremamente alto: " . number_format($consumo, 2) . " L/100km";
                $action = "Controllo meccanico urgente del veicolo necessario";
            } elseif ($consumo > 10.0) {
                $severity = 'warning';
                $message = "Consumo alto: " . number_format($consumo, 2) . " L/100km";
                $action = "Pianificare controllo meccanico";
            } else {
                $severity = 'info';
                $message = "Dati di consumo mancanti o incompleti";
                $action = "Verificare inserimento dati carburante";
            }
            
            $this->addAlert([
                'type' => 'consumption',
                'severity' => $severity,
                'title' => 'Anomalia Consumo',
                'message' => "Veicolo {$row['targa_mezzo']} ({$row['username']}): $message",
                'data' => $row,
                'recommended_action' => $action
            ]);
        }
    }

    /**
     * Alert per costi elevati
     */
    private function checkCostAlerts() {
        $currentMonth = date('Y-m');
        $previousMonth = date('Y-m', strtotime('-1 month'));
        
        // Confronta costi mese corrente vs precedente
        $sql = "SELECT 
                    current.targa_mezzo,
                    current.username,
                    current.filiale,
                    current.costo_corrente,
                    previous.costo_precedente,
                    ((current.costo_corrente - COALESCE(previous.costo_precedente, 0)) / COALESCE(previous.costo_precedente, current.costo_corrente)) * 100 as variazione_percentuale
                FROM (
                    SELECT 
                        targa_mezzo,
                        username,
                        filiale,
                        SUM(COALESCE(euro_spesi, 0)) as costo_corrente
                    FROM chilometri 
                    WHERE DATE_FORMAT(data, '%Y-%m') = ?";
        
        if ($this->livello > 1) {
            $sql .= " AND filiale = ?";
        }
        
        $sql .= " GROUP BY targa_mezzo, username, filiale
                ) current
                LEFT JOIN (
                    SELECT 
                        targa_mezzo,
                        username,
                        SUM(COALESCE(euro_spesi, 0)) as costo_precedente
                    FROM chilometri 
                    WHERE DATE_FORMAT(data, '%Y-%m') = ?";
        
        if ($this->livello > 1) {
            $sql .= " AND filiale = ?";
        }
        
        $sql .= " GROUP BY targa_mezzo, username
                ) previous ON current.targa_mezzo = previous.targa_mezzo 
                         AND current.username = previous.username
                WHERE current.costo_corrente > 500 
                   OR ABS(((current.costo_corrente - COALESCE(previous.costo_precedente, 0)) / COALESCE(previous.costo_precedente, current.costo_corrente)) * 100) > 50
                ORDER BY variazione_percentuale DESC";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($this->livello > 1) {
            $stmt->bind_param("ssss", $currentMonth, $this->filiale, $previousMonth, $this->filiale);
        } else {
            $stmt->bind_param("ss", $currentMonth, $previousMonth);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $variazione = floatval($row['variazione_percentuale']);
            $costo = floatval($row['costo_corrente']);
            
            if (abs($variazione) > 75) {
                $severity = 'critical';
            } elseif (abs($variazione) > 50 || $costo > 800) {
                $severity = 'warning';
            } else {
                $severity = 'info';
            }
            
            $this->addAlert([
                'type' => 'cost',
                'severity' => $severity,
                'title' => 'Variazione Costi Significativa',
                'message' => "Veicolo {$row['targa_mezzo']}: Costo €" . number_format($costo, 2) . 
                            " (variazione: " . ($variazione > 0 ? '+' : '') . number_format($variazione, 1) . "%)",
                'data' => $row,
                'recommended_action' => 'Analizzare le cause dell\'aumento dei costi'
            ]);
        }
    }

    /**
     * Alert per manutenzione preventiva
     */
    private function checkMaintenanceAlerts() {
        // Veicoli che superano soglia chilometraggio per manutenzione
        $sql = "SELECT 
                    targa_mezzo,
                    username,
                    filiale,
                    MAX(chilometri_finali) as chilometri_attuali,
                    MIN(data) as prima_registrazione,
                    MAX(data) as ultima_registrazione,
                    DATEDIFF(NOW(), MAX(data)) as giorni_ultima_registrazione
                FROM chilometri";
        
        if ($this->livello > 1) {
            $sql .= " WHERE filiale = ?";
        }
        
        $sql .= " GROUP BY targa_mezzo, username, filiale
                  HAVING chilometri_attuali > 0 
                     AND (chilometri_attuali % 15000 < 1000 AND chilometri_attuali % 15000 > 0)
                     OR giorni_ultima_registrazione > 7
                  ORDER BY giorni_ultima_registrazione DESC";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($this->livello > 1) {
            $stmt->bind_param("s", $this->filiale);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $giorni = intval($row['giorni_ultima_registrazione']);
            $km = intval($row['chilometri_attuali']);
            
            if ($giorni > 14) {
                $this->addAlert([
                    'type' => 'maintenance',
                    'severity' => 'warning',
                    'title' => 'Mancanza Dati',
                    'message' => "Veicolo {$row['targa_mezzo']}: Nessuna registrazione da $giorni giorni",
                    'data' => $row,
                    'recommended_action' => 'Contattare operatore per verifica stato veicolo'
                ]);
            }
            
            $nextMaintenance = ceil($km / 15000) * 15000;
            $kmToMaintenance = $nextMaintenance - $km;
            
            if ($kmToMaintenance < 1000) {
                $this->addAlert([
                    'type' => 'maintenance',
                    'severity' => 'info',
                    'title' => 'Manutenzione Programmata',
                    'message' => "Veicolo {$row['targa_mezzo']}: Manutenzione tra $kmToMaintenance km (a " . number_format($nextMaintenance) . " km)",
                    'data' => $row,
                    'recommended_action' => 'Pianificare intervento manutentivo'
                ]);
            }
        }
    }

    /**
     * Rileva anomalie nei dati
     */
    private function checkAnomaliesAlerts() {
        // Cerca registrazioni con valori anomali
        $sql = "SELECT 
                    id,
                    targa_mezzo,
                    username,
                    filiale,
                    data,
                    chilometri_iniziali,
                    chilometri_finali,
                    litri_carburante,
                    euro_spesi,
                    CASE 
                        WHEN chilometri_finali - chilometri_iniziali < 0 THEN 'km_negativi'
                        WHEN chilometri_finali - chilometri_iniziali > 2000 THEN 'km_eccessivi'
                        WHEN litri_carburante IS NOT NULL AND litri_carburante != '' AND 
                             CAST(litri_carburante AS DECIMAL(10,2)) > 150 THEN 'litri_eccessivi'
                        WHEN euro_spesi IS NOT NULL AND euro_spesi > 300 THEN 'costo_eccessivo'
                        ELSE 'normale'
                    END as anomalia
                FROM chilometri 
                WHERE DATE_FORMAT(data, '%Y-%m') >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m')";
        
        if ($this->livello > 1) {
            $sql .= " AND filiale = ?";
        }
        
        $sql .= " HAVING anomalia != 'normale'
                  ORDER BY data DESC
                  LIMIT 10";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($this->livello > 1) {
            $stmt->bind_param("s", $this->filiale);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $anomalia = $row['anomalia'];
            $km = $row['chilometri_finali'] - $row['chilometri_iniziali'];
            
            switch ($anomalia) {
                case 'km_negativi':
                    $message = "Chilometri negativi: $km km";
                    $severity = 'critical';
                    break;
                case 'km_eccessivi':
                    $message = "Chilometri giornalieri eccessivi: " . number_format($km) . " km";
                    $severity = 'warning';
                    break;
                case 'litri_eccessivi':
                    $message = "Litri carburante eccessivi: " . $row['litri_carburante'] . " L";
                    $severity = 'warning';
                    break;
                case 'costo_eccessivo':
                    $message = "Costo giornaliero elevato: €" . number_format($row['euro_spesi'], 2);
                    $severity = 'warning';
                    break;
                default:
                    continue 2;
            }
            
            $this->addAlert([
                'type' => 'anomaly',
                'severity' => $severity,
                'title' => 'Anomalia Dati',
                'message' => "Veicolo {$row['targa_mezzo']} del " . date('d/m/Y', strtotime($row['data'])) . ": $message",
                'data' => $row,
                'recommended_action' => 'Verificare e correggere il dato inserito'
            ]);
        }
    }

    /**
     * Aggiunge un alert al sistema
     */
    private function addAlert($alert) {
        $alert['timestamp'] = date('Y-m-d H:i:s');
        $alert['id'] = uniqid();
        $this->alerts[] = $alert;
    }

    /**
     * Salva alert nel database per tracking
     */
    public function saveAlerts() {
        foreach ($this->alerts as $alert) {
            // Qui potresti salvare gli alert in una tabella dedicata
            // per tracking e reportistica
        }
    }
}

try {
    $alertsSystem = new AlertsSystem($conn, $livello, $utente_data['filiale'] ?? null);
    $alerts = $alertsSystem->checkAllAlerts();
    
    // Organizza alert per severità
    $organized = [
        'critical' => [],
        'warning' => [],
        'info' => []
    ];
    
    foreach ($alerts as $alert) {
        $organized[$alert['severity']][] = $alert;
    }
    
    echo json_encode([
        'alerts' => $organized,
        'total' => count($alerts),
        'summary' => [
            'critical' => count($organized['critical']),
            'warning' => count($organized['warning']),
            'info' => count($organized['info'])
        ]
    ], JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    error_log("Errore Alerts System: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore nel sistema di alert']);
}

$conn->close();
?>