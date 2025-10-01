<?php
// DEBUG: Mostra errori PHP e variabili principali solo per admin o se ?debug=1
if ((isset($_GET['debug']) && $_GET['debug'] == '1') || (isset($dati_utente['livello']) && $dati_utente['livello'] === 1)) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    echo '<div style="background:#ffe0e0; color:#900; padding:8px; font-size:14px;">';
    echo '<b>DEBUG SESSIONE:</b> ';
    var_dump($_SESSION);
    echo '<br><b>DEBUG GET:</b> ';
    var_dump($_GET);
    echo '<br><b>DEBUG UTENTE:</b> ';
    if (isset($dati_utente)) var_dump($dati_utente);
    echo '</div>';
}

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

?>
<style>
    /* CSS personalizzato per la tabella anomalie migliorata */
    .table-anomalie {
        margin-bottom: 0;
        font-size: 0.9rem;
        width: 100%;
        table-layout: fixed;
        /* IMPORTANTE: forza layout fisso per prevenire spostamenti */
        border-collapse: separate;
        border-spacing: 0;
    }

    .table-anomalie th {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        color: white;
        font-weight: 600;
        text-align: center;
        padding: 15px 8px;
        border: none;
        position: sticky;
        top: 0;
        z-index: 10;
        font-size: 0.85rem;
        white-space: nowrap;
        overflow: hidden;
        /* Previeni overflow che causa spostamenti */
    }

    .table-anomalie td {
        padding: 12px 8px;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
        text-align: center;
        overflow: hidden;
        /* Previeni overflow che causa spostamenti */
        word-wrap: break-word;
        /* Gestisci testo lungo senza spostare colonne */
    }

    .table-anomalie tbody tr:hover {
        background-color: #f8f9fa;
        transform: scale(1.002);
        transition: all 0.2s ease;
    }

    /* Colorazione righe basata sul tipo di anomalia - con struttura fissa */
    .row-critica,
    .row-warning,
    .row-flagged,
    .row-normale {
        table-layout: fixed !important;
        /* Forza layout fisso anche sulle righe */
    }

    .row-critica {
        background-color: #f8d7da !important;
        border-left: 4px solid #dc3545;
    }

    .row-warning {
        background-color: #fff3cd !important;
        border-left: 4px solid #fd7e14;
    }

    .row-flagged {
        background-color: #d1ecf1 !important;
        border-left: 4px solid #0dcaf0;
    }

    .row-normale {
        background-color: #d4edda !important;
        border-left: 4px solid #198754;
    }

    /* Assicura che le celle delle righe flaggate mantengano le dimensioni */
    .row-flagged td {
        padding: 12px 8px !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    /* Badge personalizzati */
    .badge-anomalia {
        font-size: 0.75rem;
        padding: 0.5rem 0.75rem;
        border-radius: 25px;
        font-weight: 600;
    }

    .badge-critica {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
    }

    .badge-warning {
        background: linear-gradient(135deg, #fd7e14 0%, #e86b1a 100%);
        color: white;
    }

    .badge-normale {
        background: linear-gradient(135deg, #198754 0%, #146c3f 100%);
        color: white;
    }

    .badge-zscore {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-weight: 700;
    }

    .zscore-alto {
        background: linear-gradient(135deg, #ff4757 0%, #ff3742 100%);
        color: white;
    }

    .zscore-medio {
        background: linear-gradient(135deg, #ffa502 0%, #ff9500 100%);
        color: white;
    }

    .zscore-basso {
        background: linear-gradient(135deg, #2ed573 0%, #26d65a 100%);
        color: white;
    }

    /* Bottoni azione */
    .btn-action {
        border-radius: 25px;
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
        font-weight: 600;
        margin: 0 2px;
        transition: all 0.3s ease;
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .btn-dettaglio {
        background: linear-gradient(135deg, #0066cc 0%, #4c63d2 100%);
        border: none;
        color: white;
    }

    .btn-risolvi {
        background: linear-gradient(135deg, #198754 0%, #146c3f 100%);
        border: none;
        color: white;
    }

    .btn-flag {
        background: linear-gradient(135deg, #fd7e14 0%, #e86b1a 100%);
        border: none;
        color: white;
    }

    .btn-archive {
        background: linear-gradient(135deg, #6f42c1 0%, #563d7c 100%);
        border: none;
        color: white;
    }

    .btn-archive {
        background: linear-gradient(135deg, #6f42c1 0%, #563d7c 100%);
        border: none;
        color: white;
    }

    /* Colonne specifiche */
    /* Colonne specifiche con larghezze fisse e !important per prevenire spostamenti */
    .col-data {
        width: 90px !important;
        min-width: 90px;
        max-width: 90px;
    }

    .col-utente {
        width: 120px !important;
        min-width: 120px;
        max-width: 120px;
    }

    .col-targa {
        width: 90px !important;
        min-width: 90px;
        max-width: 90px;
    }

    .col-filiale {
        width: 100px !important;
        min-width: 100px;
        max-width: 100px;
    }

    .col-km {
        width: 80px !important;
        min-width: 80px;
        max-width: 80px;
    }

    .col-carburante {
        width: 120px !important;
        min-width: 120px;
        max-width: 120px;
    }

    .col-consumo {
        width: 90px !important;
        min-width: 90px;
        max-width: 90px;
    }

    .col-zscore {
        width: 100px !important;
        min-width: 100px;
        max-width: 100px;
    }

    .col-tipo {
        width: 140px !important;
        min-width: 140px;
        max-width: 140px;
    }

    .col-flag-id {
        width: 80px !important;
        min-width: 80px;
        max-width: 80px;
    }

    .col-flag-tipo {
        width: 120px !important;
        min-width: 120px;
        max-width: 120px;
    }

    .col-flag-note {
        width: 200px !important;
        min-width: 200px;
        max-width: 200px;
    }

    .col-flaggato-da {
        width: 110px !important;
        min-width: 110px;
        max-width: 110px;
    }

    .col-data-flag {
        width: 130px !important;
        min-width: 130px;
        max-width: 130px;
    }

    .col-risolto {
        width: 80px !important;
        min-width: 80px;
        max-width: 80px;
    }

    .col-azioni {
        width: 180px !important;
        min-width: 180px;
        max-width: 180px;
    }

    .text-truncate-custom {
        max-width: 180px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .card-anomalie {
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .card-header-anomalie {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 1.5rem;
        border: none;
    }

    .table-container {
        overflow-x: auto;
        max-height: 80vh;
    }

    /* Responsive improvements */
    @media (max-width: 1400px) {

        .table-anomalie th,
        .table-anomalie td {
            padding: 10px 6px;
            font-size: 0.8rem;
        }
    }

    @media (max-width: 1200px) {

        .table-anomalie th,
        .table-anomalie td {
            padding: 8px 4px;
            font-size: 0.75rem;
        }
    }
</style>
<?php

// Funzioni di analisi
function calcolaConsumiMedi($conn, $targa = '', $mesi = 12)
{
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

function identificaAnomalieConsumo($conn, $soglia_deviazione = 2.0)
{
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
            LEFT JOIN anomalie_flaggate af ON s.id = af.id_rifornimento AND (af.risolto = 0 OR af.risolto IS NULL)
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

function getAndamentoConsumi($conn, $mesi = 12)
{
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

function getStatisticheUtenti($conn, $filtro_utente = '', $filtro_targa = '', $filtro_filiale = '', $filtro_periodo = '12')
{
    $where = [];
    $params = [];
    $types = '';

    // Periodo
    if (is_numeric($filtro_periodo) && (int)$filtro_periodo > 0) {
        $where[] = "data >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)";
        $params[] = (int)$filtro_periodo;
        $types .= 'i';
    } else {
        $where[] = "data >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
    }

    // Targa
    if (!empty($filtro_targa)) {
        $where[] = "targa_mezzo = ?";
        $params[] = $filtro_targa;
        $types .= 's';
    }
    // Filiale
    if (!empty($filtro_filiale)) {
        $where[] = "filiale = ?";
        $params[] = $filtro_filiale;
        $types .= 's';
    }
    // Utente
    if (!empty($filtro_utente)) {
        $where[] = "username = ?";
        $params[] = $filtro_utente;
        $types .= 's';
    }

    $where[] = "CAST(litri_carburante as DECIMAL(10,2)) > 0";
    $where[] = "(chilometri_finali - chilometri_iniziali) > 0";
    $where[] = "(chilometri_finali - chilometri_iniziali) < 5000";
    $where[] = "CAST(litri_carburante as DECIMAL(10,2)) < 200";

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
            WHERE " . implode(' AND ', $where) . "
            GROUP BY username, targa_mezzo, filiale
            HAVING consumo_medio > 3 AND consumo_medio < 100
            ORDER BY consumo_medio DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

// Esegui le analisi
$consumi_medi = calcolaConsumiMedi($conn, $filtro_targa, $filtro_periodo);
$anomalie = identificaAnomalieConsumo($conn);
$andamento_consumi = getAndamentoConsumi($conn, $filtro_periodo);
$statistiche_utenti = getStatisticheUtenti($conn, $filtro_utente, $filtro_targa, $filtro_filiale, $filtro_periodo);

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
                                <option value="36" <?php echo $filtro_periodo == '36' ? 'selected' : ''; ?>>36 mesi</option>
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
                <div class="card-anomalie">
                    <div class="card-header-anomalie">
                        <h4 class="mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Anomalie Critiche Rilevate - Analisi Dettagliata
                        </h4>
                        <small>Ordinamento per Z-Score decrescente - Soglia di rilevamento: 2.0σ</small>
                    </div>

                    <div class="table-container">
                        <table class="table table-anomalie">
                            <thead>
                                <tr>
                                    <th class="col-data">📅<br>Data</th>
                                    <th class="col-utente">👤<br>Utente</th>
                                    <th class="col-targa">🚗<br>Targa</th>
                                    <th class="col-filiale">🏢<br>Filiale</th>
                                    <th class="col-km">📏<br>KM-Percorsi</th>
                                    <th class="col-carburante">⛽💰💲<br>Litri-Euro-€/L</th>
                                    <th class="col-consumo">📊<br>Consumo-KM/L</th>
                                    <th class="col-zscore">📈<br>Z-Score</th>
                                    <th class="col-tipo">⚠️<br>Tipo-Anomalia</th>
                                    <th class="col-flag-id">🏷️<br>Flag-Id</th>
                                    <th class="col-flag-tipo">🔖<br>Tipo-Flag</th>
                                    <th class="col-flag-note">📝<br>Note</th>
                                    <th class="col-flaggato-da">👮<br>Flaggato-Da</th>
                                    <th class="col-data-flag">📅<br>Data-Flag</th>
                                    <th class="col-risolto">✅<br>Stato</th>
                                    <th class="col-azioni">⚙️<br>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($dati_anomalie, 0, 10) as $anomalia):
                                    $is_flagged = $anomalia['is_flagged'] == 1;
                                    $z_score = floatval($anomalia['z_score']);

                                    // Determina la classe della riga
                                    if ($is_flagged) {
                                        $row_class = 'row-flagged';
                                    } elseif ($z_score > 3) {
                                        $row_class = 'row-critica';
                                    } elseif ($z_score > 2) {
                                        $row_class = 'row-warning';
                                    } else {
                                        $row_class = 'row-normale';
                                    }

                                    // Calcola prezzo per litro
                                    $prezzo_per_litro = $anomalia['litri'] > 0 ? $anomalia['euro_spesi'] / $anomalia['litri'] : 0;
                                ?>
                                    <tr class="<?php echo $row_class; ?>" data-anomalia-id="<?php echo $anomalia['id']; ?>">
                                        <!-- Data -->
                                        <td class="col-data">
                                            <strong><?php echo date('d/m/Y', strtotime($anomalia['data'])); ?></strong>
                                        </td>

                                        <!-- Utente -->
                                        <td class="col-utente">
                                            <strong><?php echo htmlspecialchars($anomalia['username']); ?></strong>
                                        </td>

                                        <!-- Targa -->
                                        <td class="col-targa">
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($anomalia['targa_mezzo']); ?></span>
                                        </td>

                                        <!-- Filiale -->
                                        <td class="col-filiale">
                                            <?php echo htmlspecialchars($anomalia['filiale'] ?? 'N/A'); ?>
                                        </td>

                                        <!-- KM Percorsi -->
                                        <td class="col-km">
                                            <strong><?php echo number_format($anomalia['km_percorsi']); ?></strong>
                                        </td>

                                        <!-- Carburante (Litri + Euro + Prezzo) -->
                                        <td class="col-carburante">
                                            <div style="line-height: 1.2;">
                                                <div><strong><?php echo number_format($anomalia['litri'], 2); ?>L</strong></div>
                                                <div style="color: #007bff; font-weight: bold;"><?php echo number_format($anomalia['euro_spesi'], 2); ?>€</div>
                                                <div>
                                                    <span class="badge <?php echo $prezzo_per_litro > 2.5 ? 'bg-danger' : ($prezzo_per_litro < 1.2 ? 'bg-warning' : 'bg-success'); ?>" style="font-size: 0.7rem; padding: 2px 6px;">
                                                        <?php echo number_format($prezzo_per_litro, 2); ?>€/L
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Consumo KM/L -->
                                        <td class="col-consumo">
                                            <?php echo number_format($anomalia['km_per_litro'], 2); ?>
                                        </td>

                                        <!-- Z-Score -->
                                        <td class="col-zscore">
                                            <span class="badge badge-zscore <?php
                                                                            echo $z_score > 3 ? 'zscore-alto' : ($z_score > 2 ? 'zscore-medio' : 'zscore-basso');
                                                                            ?>">
                                                <?php echo number_format($z_score, 2); ?>σ
                                            </span>
                                        </td>

                                        <!-- Tipo Anomalia -->
                                        <td class="col-tipo">
                                            <span class="badge badge-anomalia <?php
                                                                                echo strpos($anomalia['tipo_anomalia'], 'SOSPETTO') !== false ? 'badge-critica' : ($anomalia['tipo_anomalia'] === 'NORMALE' ? 'badge-normale' : 'badge-warning');
                                                                                ?>">
                                                <?php
                                                $tipo_display = str_replace([
                                                    'CONSUMO_TROPPO_',
                                                    'KM_ZERO_CON_',
                                                    'MOLTI_KM_POCO_',
                                                    'PREZZO_CARBURANTE_'
                                                ], [
                                                    'CONSUMO ',
                                                    'KM ZERO ',
                                                    'MOLTI KM ',
                                                    'PREZZO '
                                                ], $anomalia['tipo_anomalia']);
                                                echo htmlspecialchars($tipo_display);
                                                ?>
                                            </span>
                                        </td>

                                        <!-- Flag ID -->
                                        <td class="col-flag-id">
                                            <?php echo htmlspecialchars($anomalia['flag_id'] ?? '-'); ?>
                                        </td>

                                        <!-- Tipo Flag -->
                                        <td class="col-flag-tipo">
                                            <?php if (!empty($anomalia['tipo_flag'])): ?>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($anomalia['tipo_flag']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Note Flag -->
                                        <td class="col-flag-note">
                                            <?php if (!empty($anomalia['note_flag'])): ?>
                                                <span class="text-truncate-custom" title="<?php echo htmlspecialchars($anomalia['note_flag']); ?>">
                                                    <?php echo htmlspecialchars($anomalia['note_flag']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Flaggato Da -->
                                        <td class="col-flaggato-da">
                                            <?php echo htmlspecialchars($anomalia['flaggato_da'] ?? '-'); ?>
                                        </td>

                                        <!-- Data Flag -->
                                        <td class="col-data-flag">
                                            <?php echo !empty($anomalia['data_flag']) ? date('d/m/Y H:i', strtotime($anomalia['data_flag'])) : '-'; ?>
                                        </td>

                                        <!-- Stato Risoluzione -->
                                        <td class="col-risolto">
                                            <?php if (isset($anomalia['risolto']) && $anomalia['risolto'] == 2): ?>
                                                <span class="badge bg-secondary">🗃️ Archiviato</span>
                                            <?php elseif (isset($anomalia['risolto']) && $anomalia['risolto'] == 1): ?>
                                                <span class="badge bg-success">✅ Risolto</span>
                                            <?php elseif ($is_flagged): ?>
                                                <span class="badge bg-warning">⏳ In Corso</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">❌ Aperto</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Azioni -->
                                        <td class="col-azioni">
                                            <div class="d-flex flex-wrap gap-1 justify-content-center">
                                                <!-- Bottone Dettaglio -->
                                                <button class="btn btn-action btn-dettaglio"
                                                    onclick="dettaglioAnomalia(<?php echo $anomalia['id']; ?>)"
                                                    title="Visualizza Dettagli">
                                                    <i class="bi bi-eye"></i>
                                                </button>

                                                <!-- Bottone Flag/Unflag -->
                                                <?php if ($is_flagged): ?>
                                                    <button class="btn btn-action btn-risolvi"
                                                        onclick="unflagAnomalia(<?php echo $anomalia['id']; ?>)"
                                                        title="Marca come Risolto">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                    <button class="btn btn-action btn-archive"
                                                        onclick="archiviaAnomalia(<?php echo $anomalia['id']; ?>)"
                                                        title="Archivia Definitivamente">
                                                        <i class="bi bi-archive"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-action btn-flag"
                                                        onclick="flagAnomalia(<?php echo $anomalia['id']; ?>)"
                                                        title="Segnala Anomalia">
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

                    <!-- Footer con azioni aggiuntive -->
                    <?php if (count($dati_anomalie) > 10): ?>
                        <div class="card-footer bg-light text-center p-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <button class="btn btn-outline-primary" onclick="mostraTutteAnomalie()">
                                        Mostra tutte le <?php echo count($dati_anomalie); ?> anomalie
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button class="btn btn-outline-success" onclick="esportaSoloAnomalie()">
                                        <i class="bi bi-file-excel me-2"></i>Esporta Solo Anomalie
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
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
                    <h5 class="mb-0 d-flex align-items-center">
                        <i class="bi bi-trophy me-2"></i>Migliori Consumi (KM/Litro)
                        <span id="mezzi-loading-badge" class="badge bg-warning text-dark ms-3" style="display:none; font-size:0.75rem;">
                            <span class="spinner-border spinner-border-sm text-dark me-1" role="status" aria-hidden="true"></span>
                            Aggiornamento...
                        </span>
                    </h5>
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
                            <tbody id="tabella-mezzi-body">
                                <!-- Contenuto dinamico via AJAX -->
                            </tbody>
                            <tfoot>
                                <tr id="tabella-mezzi-loading-row" style="display:none;">
                                    <td colspan="6" class="text-center py-3">
                                        <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                                        <span class="ms-2">Caricamento...</span>
                                    </td>
                                </tr>
                                <tr id="tabella-mezzi-empty-row" style="display:none;">
                                    <td colspan="6" class="text-center text-muted py-3">Nessun mezzo trovato per i filtri selezionati.</td>
                                </tr>
                                <tr id="tabella-mezzi-error-row" style="display:none;">
                                    <td colspan="6" class="text-center text-danger py-3">Errore durante il caricamento dei dati. Riprova più tardi.</td>
                                </tr>
                            </tfoot>
                        </table>
                        <script>
                            function mostraElemento(id, mostra) {
                                const el = document.getElementById(id);
                                if (!el) return;
                                el.style.display = mostra ? '' : 'none';
                            }

                            async function aggiornaTabellaMezzi() {
                                const params = new URLSearchParams({
                                    periodo: document.getElementById('periodo')?.value || '',
                                    targa: document.getElementById('targa')?.value || '',
                                    filiale: document.getElementById('filiale')?.value || '',
                                    utente: document.getElementById('utente')?.value || ''
                                });

                                const tbody = document.getElementById('tabella-mezzi-body');
                                // Mostra spinner
                                tbody.innerHTML = '';
                                mostraElemento('tabella-mezzi-loading-row', true);
                                mostraElemento('tabella-mezzi-empty-row', false);
                                mostraElemento('tabella-mezzi-error-row', false);

                                try {
                                    const res = await fetch('report_avanzato_mezzi_ajax.php?' + params.toString());
                                    if (!res.ok) throw new Error('HTTP ' + res.status);
                                    const html = await res.text();

                                    // Inserisci le righe contenute nella risposta
                                    const temp = document.createElement('div');
                                    temp.innerHTML = html;
                                    // Supporta sia <tbody><tr>... che risposte che contengono solo <tr>
                                    const rowsFromTbody = temp.querySelectorAll('tbody tr');
                                    const rowsFromRoot = temp.querySelectorAll('tr');
                                    let rows = [];
                                    if (rowsFromTbody.length > 0) rows = rowsFromTbody;
                                    else if (rowsFromRoot.length > 0) rows = rowsFromRoot;

                                    tbody.innerHTML = '';
                                    rows.forEach(r => tbody.appendChild(r));

                                    // Nascondi spinner
                                    mostraElemento('tabella-mezzi-loading-row', false);

                                    // Se nessuna riga inserita mostra messaggio vuoto
                                    if (tbody.querySelectorAll('tr').length === 0) {
                                        mostraElemento('tabella-mezzi-empty-row', true);
                                    }
                                } catch (err) {
                                    console.error('Errore fetch mezzi:', err);
                                    mostraElemento('tabella-mezzi-loading-row', false);
                                    mostraElemento('tabella-mezzi-error-row', true);
                                }
                            }

                            // Debounce helper
                            function debounce(fn, wait) {
                                let t;
                                return function(...args) {
                                    clearTimeout(t);
                                    t = setTimeout(() => fn.apply(this, args), wait);
                                };
                            }

                            // Funzione ausiliaria per mostrare il badge di aggiornamento
                            function mostraBadgeAggiornamento(mostra) {
                                const badge = document.getElementById('mezzi-loading-badge');
                                if (!badge) return;
                                badge.style.display = mostra ? '' : 'none';
                            }

                            // Eventi su tutti i filtri con debounce
                            const aggiornaDebounced = debounce(aggiornaTabellaMezzi, 350);
                            ['periodo', 'targa', 'filiale', 'utente'].forEach(id => {
                                const el = document.getElementById(id);
                                if (el) el.addEventListener('change', () => {
                                    mostraBadgeAggiornamento(true);
                                    aggiornaDebounced();
                                });
                            });
                            // Primo caricamento
                            mostraBadgeAggiornamento(true);
                            aggiornaTabellaMezzi().then(() => mostraBadgeAggiornamento(false));
                        </script>
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
                                $utenti_peggiori = array_filter($dati_utenti, function ($u) use ($media_generale) {
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
    function flagAnomalia(id) {
        // Controlla se l'anomalia è già flaggata
        const row = document.querySelector(`tr[data-anomalia-id="${id}"]`);
        if (row && row.querySelector('.bi-check-circle')) {
            alert('Questa anomalia è già stata marcata come completata!');
            return;
        }

        if (confirm('Vuoi marcare questa registrazione come completata/verificata?')) {
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
                        // Ricarica la pagina per mostrare i dati aggiornati
                        alert('Anomalia marcata come verificata!');
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
                    alert('Errore durante la marcatura');
                    if (row) {
                        row.style.opacity = '1';
                    }
                });
        }
    }

    function unflagAnomalia(id) {
        if (confirm('Vuoi rimuovere la marcatura di completamento da questa anomalia?')) {
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
                        // Ricarica la pagina per mostrare i dati aggiornati
                        alert('Marcatura rimossa con successo!');
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
                    alert('Errore durante la rimozione della marcatura');
                    if (row) {
                        row.style.opacity = '1';
                    }
                });
        }
    }

    function archiviaAnomalia(id) {
        if (confirm('Vuoi archiviare definitivamente questa anomalia? \nQuesta operazione la marcherà come risolta e non apparirà più nei report.')) {
            // Mostra loading
            const row = document.querySelector(`tr[data-anomalia-id="${id}"]`);
            if (row) {
                row.style.opacity = '0.5';
            }

            const formData = new FormData();
            formData.append('action', 'archivia_anomalia');
            formData.append('id', id);
            formData.append('tipo', 'ANOMALIA_ARCHIVIATA');
            formData.append('note', prompt('Note di archiviazione (opzionale):') || 'Anomalia archiviata definitivamente');

            fetch('api_anomalie.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Ricarica la pagina per mostrare i dati aggiornati
                        alert('Anomalia archiviata con successo!');
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
                    alert('Errore durante l\'archiviazione');
                    if (row) {
                        row.style.opacity = '1';
                    }
                });
        }
    }

    function archiviaAnomalia(id) {
        if (confirm('Vuoi archiviare definitivamente questa anomalia? \nQuesta operazione la marcherà come risolta e non apparirà più nei report.')) {
            // Mostra loading
            const row = document.querySelector(`tr[data-anomalia-id="${id}"]`);
            if (row) {
                row.style.opacity = '0.5';
            }

            const formData = new FormData();
            formData.append('action', 'archivia_anomalia');
            formData.append('id', id);
            formData.append('tipo', 'ANOMALIA_ARCHIVIATA');
            formData.append('note', prompt('Note di archiviazione (opzionale):') || 'Anomalia archiviata definitivamente');

            fetch('api_anomalie.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Ricarica la pagina per mostrare i dati aggiornati
                        alert('Anomalia archiviata con successo!');
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
                    alert('Errore durante l\'archiviazione');
                    if (row) {
                        row.style.opacity = '1';
                    }
                });
        }
    }

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
                </div>
                <div class="modal-footer">
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