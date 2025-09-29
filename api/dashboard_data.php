<?php
// File: api/dashboard_data.php
// API endpoint per fornire dati alla Dashboard BI

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Avvia la sessione e verifica l'autenticazione
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

// Solo admin e responsabili possono accedere ai dati BI
if ($livello > 2) {
    http_response_code(403);
    echo json_encode(['error' => 'Accesso non autorizzato']);
    exit();
}

/**
 * Classe per il calcolo dei KPI e analytics
 */
class DashboardKPIEngine {
    private $conn;
    private $livello;
    private $filiale;
    private $divisione;
    private $username;

    public function __construct($conn, $livello, $filiale = null, $divisione = null, $username = null) {
        $this->conn = $conn;
        $this->livello = $livello;
        $this->filiale = $filiale;
        $this->divisione = $divisione;
        $this->username = $username;
    }

    /**
     * Calcola i KPI principali per il mese corrente
     */
    public function calculateMainKPIs() {
        $currentMonth = date('Y-m');
        $previousMonth = date('Y-m', strtotime('-1 month'));
        
        return [
            'totalKmMonth' => $this->getTotalKmMonth($currentMonth),
            'avgConsumption' => $this->getAverageConsumption($currentMonth),
            'targetAchievement' => $this->getTargetAchievement($currentMonth),
            'totalCostMonth' => $this->getTotalCostMonth($currentMonth),
            
            // Trend rispetto al mese precedente
            'kmTrendPercent' => $this->getKmTrend($currentMonth, $previousMonth),
            'consumptionTrendPercent' => $this->getConsumptionTrend($currentMonth, $previousMonth),
            'targetTrendPercent' => $this->getTargetTrend($currentMonth, $previousMonth),
            'costTrendPercent' => $this->getCostTrend($currentMonth, $previousMonth)
        ];
    }

    /**
     * Calcola il totale chilometri per il mese specificato
     */
    private function getTotalKmMonth($month) {
        $sql = "SELECT SUM(chilometri_finali - chilometri_iniziali) as total_km 
                FROM chilometri 
                WHERE DATE_FORMAT(data, '%Y-%m') = ?";
        
        if ($this->livello > 1) {
            $sql .= " AND filiale = ?";
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if ($this->livello > 1) {
            $stmt->bind_param("ss", $month, $this->filiale);
        } else {
            $stmt->bind_param("s", $month);
        }
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return intval($result['total_km'] ?? 0);
    }

    /**
     * Calcola il consumo medio L/100km per il mese
     */
    private function getAverageConsumption($month) {
        $sql = "SELECT 
                    SUM(CAST(litri_carburante AS DECIMAL(10,2))) as total_litri,
                    SUM(chilometri_finali - chilometri_iniziali) as total_km
                FROM chilometri 
                WHERE DATE_FORMAT(data, '%Y-%m') = ? 
                    AND litri_carburante IS NOT NULL 
                    AND litri_carburante != ''
                    AND litri_carburante != '0'";
        
        if ($this->livello > 1) {
            $sql .= " AND filiale = ?";
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if ($this->livello > 1) {
            $stmt->bind_param("ss", $month, $this->filiale);
        } else {
            $stmt->bind_param("s", $month);
        }
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        $totalLitri = floatval($result['total_litri'] ?? 0);
        $totalKm = intval($result['total_km'] ?? 0);
        
        if ($totalKm > 0) {
            return ($totalLitri / $totalKm) * 100;
        }
        
        return 0.0;
    }

    /**
     * Calcola la percentuale di raggiungimento target per il mese
     */
    private function getTargetAchievement($month) {
        $year = date('Y', strtotime($month . '-01'));
        $monthNum = date('n', strtotime($month . '-01'));
        
        // Calcola km effettivi
        $actualKm = $this->getTotalKmMonth($month);
        
        // Calcola target proporzionale al mese
        $sql = "SELECT AVG(target_chilometri) as avg_target 
                FROM target_annuale 
                WHERE anno = ?";
        
        if ($this->livello > 1) {
            $sql .= " AND filiale = ?";
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if ($this->livello > 1) {
            $stmt->bind_param("is", $year, $this->filiale);
        } else {
            $stmt->bind_param("i", $year);
        }
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        $avgYearlyTarget = intval($result['avg_target'] ?? 0);
        
        if ($avgYearlyTarget > 0) {
            // Target mensile = target annuale / 12
            $monthlyTarget = $avgYearlyTarget / 12;
            return ($actualKm / $monthlyTarget) * 100;
        }
        
        return 0.0;
    }

    /**
     * Calcola il costo totale per il mese
     */
    private function getTotalCostMonth($month) {
        // Costi carburante
        $sql1 = "SELECT SUM(euro_spesi) as fuel_cost 
                 FROM chilometri 
                 WHERE DATE_FORMAT(data, '%Y-%m') = ? 
                     AND euro_spesi IS NOT NULL";
        
        if ($this->livello > 1) {
            $sql1 .= " AND filiale = ?";
        }
        
        $stmt1 = $this->conn->prepare($sql1);
        
        if ($this->livello > 1) {
            $stmt1->bind_param("ss", $month, $this->filiale);
        } else {
            $stmt1->bind_param("s", $month);
        }
        
        $stmt1->execute();
        $fuelCost = floatval($stmt1->get_result()->fetch_assoc()['fuel_cost'] ?? 0);
        
        // Calcola costi extra (assumendo che siano per il mese corrente)
        $sql2 = "SELECT 
                    SUM(ce.costo * (c.chilometri_finali - c.chilometri_iniziali)) as extra_cost
                 FROM chilometri c
                 JOIN costo_extra ce ON c.targa_mezzo = ce.targa_mezzo
                 WHERE DATE_FORMAT(c.data, '%Y-%m') = ?";
        
        if ($this->livello > 1) {
            $sql2 .= " AND c.filiale = ?";
        }
        
        $stmt2 = $this->conn->prepare($sql2);
        
        if ($this->livello > 1) {
            $stmt2->bind_param("ss", $month, $this->filiale);
        } else {
            $stmt2->bind_param("s", $month);
        }
        
        $stmt2->execute();
        $extraCost = floatval($stmt2->get_result()->fetch_assoc()['extra_cost'] ?? 0);
        
        return $fuelCost + $extraCost;
    }

    /**
     * Calcola il trend dei chilometri rispetto al mese precedente
     */
    private function getKmTrend($currentMonth, $previousMonth) {
        $currentKm = $this->getTotalKmMonth($currentMonth);
        $previousKm = $this->getTotalKmMonth($previousMonth);
        
        if ($previousKm > 0) {
            return (($currentKm - $previousKm) / $previousKm) * 100;
        }
        
        return 0.0;
    }

    /**
     * Calcola trend consumo
     */
    private function getConsumptionTrend($currentMonth, $previousMonth) {
        $currentConsumption = $this->getAverageConsumption($currentMonth);
        $previousConsumption = $this->getAverageConsumption($previousMonth);
        
        if ($previousConsumption > 0) {
            return (($currentConsumption - $previousConsumption) / $previousConsumption) * 100;
        }
        
        return 0.0;
    }

    /**
     * Calcola trend target
     */
    private function getTargetTrend($currentMonth, $previousMonth) {
        $currentTarget = $this->getTargetAchievement($currentMonth);
        $previousTarget = $this->getTargetAchievement($previousMonth);
        
        if ($previousTarget > 0) {
            return (($currentTarget - $previousTarget) / $previousTarget) * 100;
        }
        
        return 0.0;
    }

    /**
     * Calcola trend costi
     */
    private function getCostTrend($currentMonth, $previousMonth) {
        $currentCost = $this->getTotalCostMonth($currentMonth);
        $previousCost = $this->getTotalCostMonth($previousMonth);
        
        if ($previousCost > 0) {
            return (($currentCost - $previousCost) / $previousCost) * 100;
        }
        
        return 0.0;
    }

    /**
     * Genera dati per il grafico trend chilometri
     */
    public function getKmTrendData($months = 6) {
        $labels = [];
        $actualData = [];
        $targetData = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = date('Y-m', strtotime("-$i months"));
            $labels[] = date('M Y', strtotime($date . '-01'));
            
            $actualKm = $this->getTotalKmMonth($date);
            $actualData[] = $actualKm;
            
            // Calcola target mensile
            $year = date('Y', strtotime($date . '-01'));
            $sql = "SELECT AVG(target_chilometri) as avg_target 
                    FROM target_annuale 
                    WHERE anno = ?";
            
            if ($this->livello > 1) {
                $sql .= " AND filiale = ?";
            }
            
            $stmt = $this->conn->prepare($sql);
            
            if ($this->livello > 1) {
                $stmt->bind_param("is", $year, $this->filiale);
            } else {
                $stmt->bind_param("i", $year);
            }
            
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            $avgYearlyTarget = intval($result['avg_target'] ?? 0);
            $monthlyTarget = $avgYearlyTarget > 0 ? $avgYearlyTarget / 12 : 0;
            $targetData[] = intval($monthlyTarget);
        }
        
        return [
            'labels' => $labels,
            'actual' => $actualData,
            'target' => $targetData
        ];
    }

    /**
     * Calcola distribuzione efficienza flotta
     */
    public function getEfficiencyDistribution() {
        $sql = "SELECT 
                    targa_mezzo,
                    AVG(
                        CASE 
                            WHEN litri_carburante IS NOT NULL 
                                AND litri_carburante != '' 
                                AND litri_carburante != '0'
                                AND (chilometri_finali - chilometri_iniziali) > 0
                            THEN (CAST(litri_carburante AS DECIMAL(10,2)) / (chilometri_finali - chilometri_iniziali)) * 100
                            ELSE NULL
                        END
                    ) as avg_consumption
                FROM chilometri 
                WHERE DATE_FORMAT(data, '%Y-%m') = ?
                    AND litri_carburante IS NOT NULL 
                    AND litri_carburante != ''
                    AND litri_carburante != '0'";
        
        if ($this->livello > 1) {
            $sql .= " AND filiale = ?";
        }
        
        $sql .= " GROUP BY targa_mezzo
                  HAVING avg_consumption IS NOT NULL";
        
        $stmt = $this->conn->prepare($sql);
        
        $currentMonth = date('Y-m');
        
        if ($this->livello > 1) {
            $stmt->bind_param("ss", $currentMonth, $this->filiale);
        } else {
            $stmt->bind_param("s", $currentMonth);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $distribution = [0, 0, 0, 0]; // [Ottima, Buona, Media, Scarsa]
        
        while ($row = $result->fetch_assoc()) {
            $consumption = floatval($row['avg_consumption']);
            
            if ($consumption <= 6.5) {
                $distribution[0]++; // Ottima
            } elseif ($consumption <= 8.0) {
                $distribution[1]++; // Buona
            } elseif ($consumption <= 10.0) {
                $distribution[2]++; // Media
            } else {
                $distribution[3]++; // Scarsa
            }
        }
        
        return $distribution;
    }

    /**
     * Performance per filiale
     */
    public function getBranchPerformanceData() {
        if ($this->livello > 1) {
            // Se non è admin, mostra solo la sua filiale
            return [
                'labels' => [$this->filiale],
                'data' => [$this->getTargetAchievement(date('Y-m'))]
            ];
        }
        
        $sql = "SELECT 
                    f.filiale,
                    AVG(
                        (SELECT SUM(c2.chilometri_finali - c2.chilometri_iniziali) 
                         FROM chilometri c2 
                         WHERE c2.filiale = f.filiale 
                             AND DATE_FORMAT(c2.data, '%Y-%m') = ?) /
                        (SELECT AVG(t.target_chilometri) / 12 
                         FROM target_annuale t 
                         WHERE t.filiale = f.filiale 
                             AND t.anno = YEAR(NOW())) * 100
                    ) as performance
                FROM filiali f
                GROUP BY f.filiale
                ORDER BY performance DESC";
        
        $stmt = $this->conn->prepare($sql);
        $currentMonth = date('Y-m');
        $stmt->bind_param("s", $currentMonth);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $labels = [];
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $labels[] = $row['filiale'];
            $data[] = floatval($row['performance'] ?? 0);
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    /**
     * Dettagli veicoli per tabella dashboard
     */
    public function getVehicleDetails() {
        $currentMonth = date('Y-m');
        $currentYear = date('Y');

        $sql = "SELECT
                    u.targa_mezzo,
                    u.username as operatore,
                    u.filiale,
                    COALESCE(SUM(c.chilometri_finali - c.chilometri_iniziali), 0) as km_mese,
                    COALESCE(t.target_chilometri / 12, 0) as target_mensile,
                    ROUND(
                        CASE
                            WHEN COALESCE(t.target_chilometri / 12, 0) > 0
                            THEN (COALESCE(SUM(c.chilometri_finali - c.chilometri_iniziali), 0) / (t.target_chilometri / 12)) * 100
                            ELSE 0
                        END, 1
                    ) as target_percentuale,
                    ROUND(
                        AVG(
                            CASE
                                WHEN c.litri_carburante IS NOT NULL
                                    AND c.litri_carburante != ''
                                    AND c.litri_carburante != '0'
                                    AND (c.chilometri_finali - c.chilometri_iniziali) > 0
                                THEN (CAST(c.litri_carburante AS DECIMAL(10,2)) / (c.chilometri_finali - c.chilometri_iniziali)) * 100
                                ELSE NULL
                            END
                        ), 1
                    ) as consumo_medio,
                    ROUND(COALESCE(SUM(c.euro_spesi), 0), 2) as costi_totali,
                    CASE
                        WHEN COALESCE(SUM(c.chilometri_finali - c.chilometri_iniziali), 0) >= COALESCE(t.target_chilometri / 12, 0) * 0.9 THEN '🟢 Ottimo'
                        WHEN COALESCE(SUM(c.chilometri_finali - c.chilometri_iniziali), 0) >= COALESCE(t.target_chilometri / 12, 0) * 0.7 THEN '🟡 Buono'
                        WHEN COALESCE(SUM(c.chilometri_finali - c.chilometri_iniziali), 0) > 0 THEN '🟠 Insufficiente'
                        ELSE '🔴 Nessun dato'
                    END as status
                FROM utenti u
                LEFT JOIN chilometri c ON u.targa_mezzo = c.targa_mezzo
                    AND DATE_FORMAT(c.data, '%Y-%m') = ?
                LEFT JOIN target_annuale t ON u.targa_mezzo = t.targa_mezzo
                    AND t.anno = ?
                WHERE u.targa_mezzo IS NOT NULL
                    AND u.targa_mezzo != ''";

        // Filtro per livello utente
        if ($this->livello == 2) {
            $sql .= " AND u.divisione = ?";
        } elseif ($this->livello == 3) {
            $sql .= " AND u.username = ?";
        }

        $sql .= " GROUP BY u.targa_mezzo, u.username, u.filiale, t.target_chilometri
                  ORDER BY km_mese DESC";

        $stmt = $this->conn->prepare($sql);

        if ($this->livello == 2) {
            $stmt->bind_param("sis", $currentMonth, $currentYear, $this->divisione);
        } elseif ($this->livello == 3) {
            $stmt->bind_param("sis", $currentMonth, $currentYear, $this->username);
        } else {
            $stmt->bind_param("si", $currentMonth, $currentYear);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $vehicles = [];
        while ($row = $result->fetch_assoc()) {
            $vehicles[] = [
                'targa' => $row['targa_mezzo'],
                'operatore' => $row['operatore'],
                'filiale' => $row['filiale'],
                'km_mese' => intval($row['km_mese']),
                'target_percentuale' => floatval($row['target_percentuale']),
                'consumo_medio' => floatval($row['consumo_medio']),
                'costi_totali' => floatval($row['costi_totali']),
                'status' => $row['status']
            ];
        }

        return $vehicles;
    }
}

try {
    // Inizializza il motore KPI
    $kpiEngine = new DashboardKPIEngine(
        $conn, 
        $livello, 
        $utente_data['filiale'] ?? null,
        $utente_data['divisione'] ?? null,
        $username
    );

    // Calcola tutti i dati necessari per la dashboard
    $dashboardData = [
        'kpis' => $kpiEngine->calculateMainKPIs(),
        'kmTrend' => $kpiEngine->getKmTrendData(6),
        'efficiency' => [
            'distribution' => $kpiEngine->getEfficiencyDistribution()
        ],
        'branchPerformance' => $kpiEngine->getBranchPerformanceData(),
        'vehicleDetails' => $kpiEngine->getVehicleDetails(),
        'lastUpdate' => date('Y-m-d H:i:s')
    ];

    // Aggiungi cache headers per performance
    header('Cache-Control: max-age=300'); // Cache per 5 minuti
    header('ETag: ' . md5(json_encode($dashboardData)));
    
    echo json_encode($dashboardData, JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    error_log("Errore Dashboard API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore interno del server']);
}

$conn->close();
?>