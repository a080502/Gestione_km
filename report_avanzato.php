<?php
/**
 * Report Avanzato Antifrode - Sistema di Gestione KM
 * Analisi consumi, identificazione anomalie e statistiche avanzate
 * Versione: 1.0.0
 */

// --- Configurazione pagina ---
$page_title = "Report Avanzato Antifrode";
$page_description = "Analisi dettagliata consumi e identificazione anomalie";
$require_auth = true;
$require_config = true;
$additional_head = '<link rel="stylesheet" href="css/report_avanzato.css?v=' . time() . '">';

// Avvia sessione e include configurazione
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include_once 'config.php';
include_once 'dati_utente.php';

// Debug: Verifica lo stato delle variabili
if (!isset($_SESSION)) {
    error_log("ERRORE: Sessione non avviata");
    header("Location: login.php?error=no_session");
    exit();
}

if (!isset($_SESSION['username'])) {
    error_log("ERRORE: Username non presente in sessione");
    header("Location: login.php?error=no_username");
    exit();
}

// Verifica che i dati utente siano disponibili
if (!isset($dati_utente) || $dati_utente === null) {
    error_log("ERRORE: dati_utente non disponibili per utente: " . ($_SESSION['username'] ?? 'sconosciuto'));
    header("Location: login.php?error=user_data_missing");
    exit();
}

// Normalizza il nome della variabile per compatibilità
$utente_data = $dati_utente;

// Verifica privilegi - solo admin e manager possono accedere
if (!isset($utente_data['livello']) || $utente_data['livello'] > 2) {
    header("Location: unauthorized.php");
    exit();
}

// Parametri di filtro
$filtro_utente = isset($_GET['utente']) ? $_GET['utente'] : '';
$filtro_periodo = isset($_GET['periodo']) ? $_GET['periodo'] : '12'; // mesi
$filtro_targa = isset($_GET['targa']) ? $_GET['targa'] : '';
$filtro_filiale = isset($_GET['filiale']) ? $_GET['filiale'] : '';

// Include header
include 'template/header.php';

// Funzioni di analisi
function calcolaConsumiMedi($conn, $targa = '', $mesi = 12) {
    $where_clauses = ["1=1"];
    $params = [];
    $types = "";
    
    if (!empty($targa)) {
        $where_clauses[] = "targa_mezzo = ?";
        $params[] = $targa;
        $types .= "s";
    }
    
    $where_clauses[] = "data >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)";
    $params[] = $mesi;
    $types .= "i";
    
    $sql = "SELECT 
                targa_mezzo,
                username,
                AVG(chilometri_finali - chilometri_iniziali) as km_medi,
                AVG(CAST(litri_carburante as DECIMAL(10,2))) as litri_medi,
                AVG(euro_spesi) as costo_medio,
                AVG((chilometri_finali - chilometri_iniziali) / NULLIF(CAST(litri_carburante as DECIMAL(10,2)), 0)) as consumo_medio_km_litro,
                COUNT(*) as numero_rifornimenti,
                SUM(chilometri_finali - chilometri_iniziali) as km_totali,
                SUM(CAST(litri_carburante as DECIMAL(10,2))) as litri_totali,
                SUM(euro_spesi) as costo_totale
            FROM chilometri 
            WHERE " . implode(" AND ", $where_clauses) . "
            GROUP BY targa_mezzo, username
            ORDER BY consumo_medio_km_litro DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

function identificaAnomalieConsumo($conn, $soglia_deviazione = 2.0) {
    $sql = "WITH stats AS (
                SELECT 
                    id,
                    username,
                    targa_mezzo,
                    data,
                    (chilometri_finali - chilometri_iniziali) as km_percorsi,
                    CAST(litri_carburante as DECIMAL(10,2)) as litri,
                    euro_spesi,
                    (chilometri_finali - chilometri_iniziali) / NULLIF(CAST(litri_carburante as DECIMAL(10,2)), 0) as km_per_litro
                FROM chilometri 
                WHERE CAST(litri_carburante as DECIMAL(10,2)) > 0 
                AND data >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            ),
            medie AS (
                SELECT 
                    targa_mezzo,
                    AVG(km_per_litro) as media_consumo,
                    STDDEV(km_per_litro) as dev_std_consumo
                FROM stats
                GROUP BY targa_mezzo
            )
            SELECT 
                s.*,
                m.media_consumo,
                m.dev_std_consumo,
                ABS(s.km_per_litro - m.media_consumo) / NULLIF(m.dev_std_consumo, 0) as z_score,
                CASE 
                    WHEN s.km_per_litro > (m.media_consumo + ? * m.dev_std_consumo) THEN 'CONSUMO_TROPPO_BASSO'
                    WHEN s.km_per_litro < (m.media_consumo - ? * m.dev_std_consumo) THEN 'CONSUMO_TROPPO_ALTO'
                    WHEN s.km_percorsi = 0 AND s.litri > 0 THEN 'KM_ZERO_CON_RIFORNIMENTO'
                    WHEN s.km_percorsi > 1000 AND s.litri < 10 THEN 'MOLTI_KM_POCO_CARBURANTE'
                    WHEN s.euro_spesi / NULLIF(s.litri, 0) > 3.0 THEN 'PREZZO_CARBURANTE_ALTO'
                    WHEN s.euro_spesi / NULLIF(s.litri, 0) < 1.0 THEN 'PREZZO_CARBURANTE_BASSO'
                    ELSE 'OK'
                END as tipo_anomalia,
                af.id as flag_id,
                af.tipo_flag,
                af.note as note_flag,
                af.flaggato_da,
                af.data_flag,
                af.risolto,
                CASE WHEN af.id IS NOT NULL THEN 1 ELSE 0 END as is_flagged
            FROM stats s
            JOIN medie m ON s.targa_mezzo = m.targa_mezzo
            LEFT JOIN anomalie_flaggate af ON s.id = af.id_registrazione
            WHERE ABS(s.km_per_litro - m.media_consumo) / NULLIF(m.dev_std_consumo, 0) > ?
            OR (s.km_percorsi = 0 AND s.litri > 0)
            OR (s.km_percorsi > 1000 AND s.litri < 10)
            OR (s.euro_spesi / NULLIF(s.litri, 0) > 3.0)
            OR (s.euro_spesi / NULLIF(s.litri, 0) < 1.0)
            ORDER BY z_score DESC, data DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ddd", $soglia_deviazione, $soglia_deviazione, $soglia_deviazione);
    $stmt->execute();
    return $stmt->get_result();
}

function getAndamentoConsumi($conn, $mesi = 12) {
    $sql = "SELECT 
                DATE_FORMAT(data, '%Y-%m') as mese,
                COUNT(*) as numero_rifornimenti,
                SUM(chilometri_finali - chilometri_iniziali) as km_totali,
                SUM(CAST(litri_carburante as DECIMAL(10,2))) as litri_totali,
                SUM(euro_spesi) as costo_totale,
                AVG((chilometri_finali - chilometri_iniziali) / NULLIF(CAST(litri_carburante as DECIMAL(10,2)), 0)) as consumo_medio
            FROM chilometri 
            WHERE data >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            AND CAST(litri_carburante as DECIMAL(10,2)) > 0
            GROUP BY DATE_FORMAT(data, '%Y-%m')
            ORDER BY mese";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $mesi);
    $stmt->execute();
    return $stmt->get_result();
}

function getStatisticheUtenti($conn) {
    $sql = "SELECT 
                username,
                targa_mezzo,
                filiale,
                COUNT(*) as numero_registrazioni,
                SUM(chilometri_finali - chilometri_iniziali) as km_totali,
                SUM(CAST(litri_carburante as DECIMAL(10,2))) as litri_totali,
                SUM(euro_spesi) as spesa_totale,
                AVG((chilometri_finali - chilometri_iniziali) / NULLIF(CAST(litri_carburante as DECIMAL(10,2)), 0)) as consumo_medio,
                MIN(data) as prima_registrazione,
                MAX(data) as ultima_registrazione,
                DATEDIFF(MAX(data), MIN(data)) as giorni_attivita
            FROM chilometri 
            WHERE data >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            AND CAST(litri_carburante as DECIMAL(10,2)) > 0
            GROUP BY username, targa_mezzo, filiale
            ORDER BY consumo_medio DESC";
    
    return $conn->query($sql);
}

// Esegui le analisi
$consumi_medi = calcolaConsumiMedi($conn, $filtro_targa, $filtro_periodo);
$anomalie = identificaAnomalieConsumo($conn);
$andamento_consumi = getAndamentoConsumi($conn, $filtro_periodo);
$statistiche_utenti = getStatisticheUtenti($conn);

// Prepara dati per i grafici
$dati_andamento = [];
while ($row = $andamento_consumi->fetch_assoc()) {
    $dati_andamento[] = $row;
}

$dati_anomalie = [];
while ($row = $anomalie->fetch_assoc()) {
    $dati_anomalie[] = $row;
}

$dati_utenti = [];
while ($row = $statistiche_utenti->fetch_assoc()) {
    $dati_utenti[] = $row;
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 text-gradient mb-1">
                        <i class="bi bi-shield-exclamation me-2"></i>Report Avanzato Antifrode
                    </h1>
                    <p class="text-muted">Analisi dettagliata consumi e identificazione anomalie</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="window.print()">
                        <i class="bi bi-printer me-2"></i>Stampa
                    </button>
                    <button class="btn btn-success" onclick="esportaExcel()">
                        <i class="bi bi-file-excel me-2"></i>Esporta Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtri -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtri Analisi</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="periodo" class="form-label">Periodo (mesi)</label>
                            <select class="form-select" id="periodo" name="periodo">
                                <option value="3" <?php echo $filtro_periodo == '3' ? 'selected' : ''; ?>>3 mesi</option>
                                <option value="6" <?php echo $filtro_periodo == '6' ? 'selected' : ''; ?>>6 mesi</option>
                                <option value="12" <?php echo $filtro_periodo == '12' ? 'selected' : ''; ?>>12 mesi</option>
                                <option value="24" <?php echo $filtro_periodo == '24' ? 'selected' : ''; ?>>24 mesi</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="targa" class="form-label">Targa</label>
                            <select class="form-select" id="targa" name="targa">
                                <option value="">Tutte le targhe</option>
                                <?php
                                $targhe_result = $conn->query("SELECT DISTINCT targa_mezzo FROM chilometri ORDER BY targa_mezzo");
                                while ($row = $targhe_result->fetch_assoc()) {
                                    $selected = ($filtro_targa == $row['targa_mezzo']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($row['targa_mezzo']) . "' $selected>" . htmlspecialchars($row['targa_mezzo']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filiale" class="form-label">Filiale</label>
                            <select class="form-select" id="filiale" name="filiale">
                                <option value="">Tutte le filiali</option>
                                <?php
                                $filiali_result = $conn->query("SELECT DISTINCT filiale FROM chilometri ORDER BY filiale");
                                while ($row = $filiali_result->fetch_assoc()) {
                                    $selected = ($filtro_filiale == $row['filiale']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($row['filiale']) . "' $selected>" . htmlspecialchars($row['filiale']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search me-2"></i>Applica Filtri
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiche principali -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-exclamation-triangle text-danger display-4"></i>
                    <h4 class="mt-2"><?php echo count($dati_anomalie); ?></h4>
                    <p class="text-muted mb-0">Anomalie Rilevate</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-people text-info display-4"></i>
                    <h4 class="mt-2"><?php echo count($dati_utenti); ?></h4>
                    <p class="text-muted mb-0">Utenti Analizzati</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-calendar3 text-warning display-4"></i>
                    <h4 class="mt-2"><?php echo array_sum(array_column($dati_andamento, 'numero_rifornimenti')); ?></h4>
                    <p class="text-muted mb-0">Rifornimenti Totali</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-currency-euro text-success display-4"></i>
                    <h4 class="mt-2"><?php echo number_format(array_sum(array_column($dati_andamento, 'costo_totale')), 0); ?>€</h4>
                    <p class="text-muted mb-0">Spesa Totale</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Anomalie Critiche -->
    <?php if (!empty($dati_anomalie)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-shield-x me-2"></i>Anomalie Critiche Rilevate</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-fixed mb-0" style="table-layout: fixed !important; width: 100% !important;">
                            <thead class="table-danger">
                                <tr>
                                    <th style="width: 60px !important; min-width: 60px !important; max-width: 60px !important; text-align: center !important;">Flag</th>
                                    <th style="width: 100px !important;">Data</th>
                                    <th style="width: 120px !important;">Utente</th>
                                    <th style="width: 100px !important;">Targa</th>
                                    <th style="width: 80px !important; text-align: right !important;">KM</th>
                                    <th style="width: 80px !important; text-align: right !important;">Litri</th>
                                    <th style="width: 90px !important; text-align: right !important;">€</th>
                                    <th style="width: 80px !important; text-align: right !important;">KM/L</th>
                                    <th style="width: 80px !important; text-align: center !important;">Z-Score</th>
                                    <th style="width: 140px !important;">Tipo Anomalia</th>
                                    <th style="width: 100px !important; text-align: center !important;">Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($dati_anomalie, 0, 10) as $anomalia): 
                                    $is_flagged = $anomalia['is_flagged'] == 1;
                                    $row_class = $is_flagged ? 'table-info' : ($anomalia['z_score'] > 3 ? 'table-danger' : 'table-warning');
                                ?>
                                <tr class="<?php echo $row_class; ?>" data-anomalia-id="<?php echo $anomalia['id']; ?>">
                                    <td class="flag-cell" style="width: 60px !important; min-width: 60px !important; max-width: 60px !important; text-align: center !important; padding: 0.5rem 0.25rem !important;">
                                        <?php if ($is_flagged): ?>
                                            <i class="bi bi-flag-fill flag-icon" title="Anomalia flaggata da: <?php echo htmlspecialchars($anomalia['flaggato_da']); ?> il <?php echo date('d/m/Y H:i', strtotime($anomalia['data_flag'])); ?>"></i>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 1.2em;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="width: 100px !important;">
                                        <?php echo date('d/m/Y', strtotime($anomalia['data'])); ?>
                                    </td>
                                    <td style="width: 120px !important;"><strong><?php echo htmlspecialchars($anomalia['username']); ?></strong></td>
                                    <td style="width: 100px !important;"><?php echo htmlspecialchars($anomalia['targa_mezzo']); ?></td>
                                    <td style="width: 80px !important; text-align: right !important;"><?php echo number_format($anomalia['km_percorsi']); ?></td>
                                    <td style="width: 80px !important; text-align: right !important;"><?php echo number_format($anomalia['litri'], 2); ?></td>
                                    <td style="width: 90px !important; text-align: right !important;"><?php echo number_format($anomalia['euro_spesi'], 2); ?>€</td>
                                    <td style="width: 80px !important; text-align: right !important;"><?php echo number_format($anomalia['km_per_litro'], 2); ?></td>
                                    <td style="width: 80px !important; text-align: center !important;">
                                        <span class="badge <?php echo $anomalia['z_score'] > 3 ? 'bg-danger' : 'bg-warning'; ?>">
                                            <?php echo number_format($anomalia['z_score'], 2); ?>
                                        </span>
                                    </td>
                                    <td style="width: 140px !important;">
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($anomalia['tipo_anomalia']); ?></span>
                                    </td>
                                    <td style="width: 100px !important; text-align: center !important;">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary btn-sm" onclick="dettaglioAnomalia(<?php echo $anomalia['id']; ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php if ($is_flagged): ?>
                                                <button class="btn btn-outline-success btn-sm" onclick="unflagAnomalia(<?php echo $anomalia['id']; ?>)" title="Annulla Flag">
                                                    <i class="bi bi-flag-slash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-outline-warning btn-sm" onclick="flagAnomalia(<?php echo $anomalia['id']; ?>)" title="Flagga Anomalia">
                                                    <i class="bi bi-flag"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (count($dati_anomalie) > 10): ?>
                    <div class="card-footer text-center">
                        <div class="btn-group">
                            <button class="btn btn-outline-primary" onclick="mostraTutteAnomalie()">
                                Mostra tutte le <?php echo count($dati_anomalie); ?> anomalie
                            </button>
                            <button class="btn btn-outline-success" onclick="esportaSoloAnomalie()">
                                <i class="bi bi-file-excel me-2"></i>Esporta Solo Anomalie
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Grafici -->
    <div class="row mb-4">
        <!-- Andamento Consumi -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Andamento Consumi Mensili</h5>
                </div>
                <div class="card-body">
                    <canvas id="graficoAndamento" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Distribuzione Consumi -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Distribuzione Consumi per Utente</h5>
                </div>
                <div class="card-body">
                    <canvas id="graficoDistribuzione" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Performers e Anomalie -->
    <div class="row mb-4">
        <!-- Migliori Consumi -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-trophy me-2"></i>Migliori Consumi (KM/Litro)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-success">
                                <tr>
                                    <th>Pos</th>
                                    <th>Utente</th>
                                    <th>Targa</th>
                                    <th>KM/L</th>
                                    <th>Totale KM</th>
                                    <th>Risparmio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $pos = 1;
                                foreach (array_slice($dati_utenti, 0, 5) as $utente): 
                                    if ($utente['consumo_medio'] > 0):
                                ?>
                                <tr>
                                    <td><?php echo $pos++; ?></td>
                                    <td><?php echo htmlspecialchars($utente['username']); ?></td>
                                    <td><?php echo htmlspecialchars($utente['targa_mezzo']); ?></td>
                                    <td class="text-success fw-bold"><?php echo number_format($utente['consumo_medio'], 2); ?></td>
                                    <td><?php echo number_format($utente['km_totali']); ?></td>
                                    <td class="text-success">
                                        <i class="bi bi-arrow-down me-1"></i>
                                        <?php echo number_format(($utente['consumo_medio'] - 10) * $utente['litri_totali'] * 1.6, 0); ?>€
                                    </td>
                                </tr>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Peggiori Consumi -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Consumi da Monitorare</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-warning">
                                <tr>
                                    <th>Utente</th>
                                    <th>Targa</th>
                                    <th>KM/L</th>
                                    <th>Scostamento</th>
                                    <th>Extra Costo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $media_generale = array_sum(array_column($dati_utenti, 'consumo_medio')) / count($dati_utenti);
                                $utenti_peggiori = array_filter($dati_utenti, function($u) use ($media_generale) { 
                                    return $u['consumo_medio'] > 0 && $u['consumo_medio'] < $media_generale * 0.8; 
                                });
                                foreach (array_slice($utenti_peggiori, 0, 5) as $utente): 
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($utente['username']); ?></td>
                                    <td><?php echo htmlspecialchars($utente['targa_mezzo']); ?></td>
                                    <td class="text-warning fw-bold"><?php echo number_format($utente['consumo_medio'], 2); ?></td>
                                    <td class="text-danger">
                                        <?php echo number_format((($media_generale - $utente['consumo_medio']) / $media_generale) * 100, 1); ?>%
                                    </td>
                                    <td class="text-danger">
                                        +<?php echo number_format(($media_generale - $utente['consumo_medio']) * $utente['litri_totali'] * 1.6, 0); ?>€
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analisi Temporal Pattern -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clock me-2"></i>Pattern Temporali Sospetti</h5>
                </div>
                <div class="card-body">
                    <canvas id="graficoPattern" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@2.0.1/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

<script>
// Dati per i grafici
const datiAndamento = <?php echo json_encode($dati_andamento); ?>;
const datiUtenti = <?php echo json_encode($dati_utenti); ?>;
const datiAnomalieJs = <?php echo json_encode($dati_anomalie); ?>;

// Grafico andamento consumi
const ctxAndamento = document.getElementById('graficoAndamento').getContext('2d');
new Chart(ctxAndamento, {
    type: 'line',
    data: {
        labels: datiAndamento.map(d => d.mese),
        datasets: [{
            label: 'Consumo Medio (KM/L)',
            data: datiAndamento.map(d => parseFloat(d.consumo_medio)),
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.4
        }, {
            label: 'Costo Totale (€)',
            data: datiAndamento.map(d => parseFloat(d.costo_totale)),
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.1)',
            yAxisID: 'y1',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'KM per Litro'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Costo (€)'
                },
                grid: {
                    drawOnChartArea: false,
                }
            }
        }
    }
});

// Grafico distribuzione consumi
const ctxDistribuzione = document.getElementById('graficoDistribuzione').getContext('2d');
const utentiTop = datiUtenti.slice(0, 10);
new Chart(ctxDistribuzione, {
    type: 'doughnut',
    data: {
        labels: utentiTop.map(u => `${u.username} (${u.targa_mezzo})`),
        datasets: [{
            data: utentiTop.map(u => parseFloat(u.km_totali)),
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
                '#4BC0C0', '#FF6384'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + 
                               new Intl.NumberFormat().format(context.parsed) + ' km';
                    }
                }
            }
        }
    }
});

// Grafico pattern temporali
const ctxPattern = document.getElementById('graficoPattern').getContext('2d');
const anomaliePerGiorno = {};
datiAnomalieJs.forEach(a => {
    const giorno = new Date(a.data).getDay();
    anomaliePerGiorno[giorno] = (anomaliePerGiorno[giorno] || 0) + 1;
});

const giorniSettimana = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
new Chart(ctxPattern, {
    type: 'bar',
    data: {
        labels: giorniSettimana,
        datasets: [{
            label: 'Anomalie per Giorno',
            data: giorniSettimana.map((_, i) => anomaliePerGiorno[i] || 0),
            backgroundColor: 'rgba(255, 99, 132, 0.8)',
            borderColor: 'rgb(255, 99, 132)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Numero Anomalie'
                }
            }
        }
    }
});

// Funzioni JavaScript
function dettaglioAnomalia(id) {
    fetch(`api_anomalie.php?action=get_dettaglio&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostraModalDettaglio(data.data);
            } else {
                alert('Errore: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Errore durante il caricamento dei dettagli');
        });
}

function mostraModalDettaglio(dati) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-info-circle me-2"></i>Dettaglio Registrazione #${dati.id}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Informazioni Base</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Data:</strong></td><td>${new Date(dati.data).toLocaleDateString('it-IT')}</td></tr>
                                <tr><td><strong>Utente:</strong></td><td>${dati.username}</td></tr>
                                <tr><td><strong>Targa:</strong></td><td>${dati.targa_mezzo}</td></tr>
                                <tr><td><strong>Filiale:</strong></td><td>${dati.filiale}</td></tr>
                                <tr><td><strong>KM Iniziali:</strong></td><td>${dati.chilometri_iniziali.toLocaleString()}</td></tr>
                                <tr><td><strong>KM Finali:</strong></td><td>${dati.chilometri_finali.toLocaleString()}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Dati Carburante</h6>
                            <table class="table table-sm">
                                <tr><td><strong>KM Percorsi:</strong></td><td>${dati.dettagli_calcolati.km_percorsi.toLocaleString()}</td></tr>
                                <tr><td><strong>Litri:</strong></td><td>${parseFloat(dati.litri_carburante).toFixed(2)}</td></tr>
                                <tr><td><strong>Euro Spesi:</strong></td><td>€${parseFloat(dati.euro_spesi).toFixed(2)}</td></tr>
                                <tr><td><strong>KM/Litro:</strong></td><td class="${dati.dettagli_calcolati.consumo_km_litro < 8 ? 'text-danger' : 'text-success'}">${dati.dettagli_calcolati.consumo_km_litro.toFixed(2)}</td></tr>
                                <tr><td><strong>€/Litro:</strong></td><td>${dati.dettagli_calcolati.prezzo_per_litro.toFixed(2)}</td></tr>
                                ${dati.dettagli_calcolati.scostamento_percentuale !== null ? 
                                    `<tr><td><strong>Scostamento:</strong></td><td class="${dati.dettagli_calcolati.scostamento_percentuale > 20 ? 'text-danger' : 'text-warning'}">${dati.dettagli_calcolati.scostamento_percentuale.toFixed(1)}%</td></tr>` 
                                    : ''}
                            </table>
                        </div>
                    </div>
                    ${dati.note ? `<div class="mt-3"><h6>Note:</h6><p class="bg-light p-2 rounded">${dati.note}</p></div>` : ''}
                    ${dati.tipo_flag ? `
                        <div class="alert alert-warning">
                            <strong>Anomalia Flaggata:</strong> ${dati.tipo_flag}<br>
                            <small>Da: ${dati.flaggato_da} il ${new Date(dati.data_flag).toLocaleString('it-IT')}</small>
                            ${dati.note_flag ? `<br><strong>Note:</strong> ${dati.note_flag}` : ''}
                        </div>
                    ` : ''}
                </div>
                <div class="modal-footer">
                    ${!dati.tipo_flag ? `
                        <button class="btn btn-warning" onclick="flagAnomaliaModal(${dati.id})">
                            <i class="bi bi-flag me-2"></i>Flagga Anomalia
                        </button>
                    ` : ''}
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
    
    modal.addEventListener('hidden.bs.modal', () => {
        document.body.removeChild(modal);
    });
}

function flagAnomalia(id) {
    // Controlla se l'anomalia è già flaggata
    const row = document.querySelector(`tr[data-anomalia-id="${id}"]`);
    if (row && row.querySelector('.bi-flag-fill')) {
        alert('Questa anomalia è già stata flaggata!');
        return;
    }
    
    if (confirm('Vuoi segnalare questa registrazione come anomalia verificata?')) {
        // Mostra loading
        if (row) {
            row.style.opacity = '0.5';
        }
        
        const formData = new FormData();
        formData.append('action', 'flag_anomalia');
        formData.append('id', id);
        formData.append('tipo', 'ANOMALIA_VERIFICATA');
        formData.append('note', prompt('Note aggiuntive (opzionale):') || '');
        
        fetch('api_anomalie.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Ricarica la pagina per garantire coerenza strutturale
                location.reload();
            } else {
                alert('Errore: ' + data.error);
                if (row) {
                    row.style.opacity = '1';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Errore durante la segnalazione');
            if (row) {
                row.style.opacity = '1';
            }
        });
    }
}

function unflagAnomalia(id) {
    if (confirm('Vuoi rimuovere il flag da questa anomalia?')) {
        // Mostra loading
        const row = document.querySelector(`tr[data-anomalia-id="${id}"]`);
        if (row) {
            row.style.opacity = '0.5';
        }
        
        const formData = new FormData();
        formData.append('action', 'unflag_anomalia');
        formData.append('id', id);
        
        fetch('api_anomalie.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Ricarica la pagina per garantire coerenza strutturale
                location.reload();
            } else {
                alert('Errore: ' + data.error);
                if (row) {
                    row.style.opacity = '1';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Errore durante la rimozione del flag');
            if (row) {
                row.style.opacity = '1';
            }
        });
    }
}

function flagAnomaliaModal(id) {
    const note = prompt('Inserisci note per questa anomalia:') || '';
    
    const formData = new FormData();
    formData.append('action', 'flag_anomalia');
    formData.append('id', id);
    formData.append('tipo', 'ANOMALIA_VERIFICATA');
    formData.append('note', note);
    
    fetch('api_anomalie.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Anomalia flaggata con successo');
            location.reload();
        } else {
            alert('Errore: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Errore durante la segnalazione');
    });
}

function mostraTutteAnomalie() {
    window.location.href = 'report_avanzato.php?mostra_tutte=1';
}

function esportaExcel() {
    // Recupera i parametri di filtro attuali
    const urlParams = new URLSearchParams(window.location.search);
    const periodo = urlParams.get('periodo') || '12';
    const targa = urlParams.get('targa') || '';
    const filiale = urlParams.get('filiale') || '';
    
    // Mostra menu di scelta formato
    const formato = confirm('Scegli il formato di esportazione:\n\nOK = Excel (.xlsx)\nAnnulla = CSV (.csv)') ? 'xlsx' : 'csv';
    
    // Costruisci URL di export con filtri
    const exportUrl = `export_report_excel.php?formato=${formato}&periodo=${periodo}&targa=${encodeURIComponent(targa)}&filiale=${encodeURIComponent(filiale)}`;
    
    // Mostra indicatore di caricamento
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-download me-2"></i>Esportazione...';
    btn.disabled = true;
    
    // Crea link nascosto per download
    const link = document.createElement('a');
    link.href = exportUrl;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Ripristina pulsante dopo 2 secondi
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }, 2000);
}

function esportaSoloAnomalie() {
    // Scegli il formato
    const formato = confirm('Formato esportazione anomalie:\n\nOK = Excel (.xlsx)\nAnnulla = CSV (.csv)') ? 'xlsx' : 'csv';
    
    // Raccogli gli ID delle anomalie attualmente visualizzate
    const anomalieIds = [];
    datiAnomalieJs.forEach(a => anomalieIds.push(a.id));
    
    if (anomalieIds.length === 0) {
        alert('Nessuna anomalia da esportare.');
        return;
    }
    
    // URL di export
    const exportUrl = `export_anomalie.php?formato=${formato}&ids=${anomalieIds.join(',')}`;
    
    // Download
    const link = document.createElement('a');
    link.href = exportUrl;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Aggiorna automaticamente ogni 5 minuti
setInterval(() => {
    location.reload();
}, 300000);

</script>

<?php include 'template/footer.php'; ?>