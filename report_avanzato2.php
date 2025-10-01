<?php

/**
 * Report Avanzato Antifrode v2.0 - Sistema di Gestione KM
 * Versione completamente ridisegnata con tabella anomalie migliorata
 * Analisi consumi, identificazione anomalie e statistiche avanzate
 */

// Avvia sessione e include configurazione
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include_once 'config.php';
include_once 'dati_utente.php';

// Verifica che i dati utente siano disponibili
if (!isset($dati_utente) || $dati_utente === null) {
    error_log("ERRORE: dati_utente non disponibili per utente: " . ($_SESSION['username'] ?? 'sconosciuto'));
    header("Location: login.php?error=user_data_missing");
    exit();
}

// Verifica privilegi - solo admin e manager possono accedere
if (!isset($dati_utente['livello']) || $dati_utente['livello'] > 2) {
    header("Location: unauthorized.php");
    exit();
}

// Parametri di filtro
$filtro_utente = isset($_GET['utente']) ? $_GET['utente'] : '';
$filtro_periodo = isset($_GET['periodo']) ? $_GET['periodo'] : '12';
$filtro_targa = isset($_GET['targa']) ? $_GET['targa'] : '';
$filtro_filiale = isset($_GET['filiale']) ? $_GET['filiale'] : '';

// Funzioni di analisi migliorata
function identificaAnomalieConsumo($conn, $soglia_deviazione = 2.0)
{
    $sql = "WITH stats AS (
                SELECT 
                    id,
                    username,
                    targa_mezzo,
                    data,
                    divisione,
                    filiale,
                    (chilometri_finali - chilometri_iniziali) as km_percorsi,
                    CAST(litri_carburante as DECIMAL(10,2)) as litri,
                    euro_spesi,
                    (chilometri_finali - chilometri_iniziali) / NULLIF(CAST(litri_carburante as DECIMAL(10,2)), 0) as km_per_litro,
                    euro_spesi / NULLIF(CAST(litri_carburante as DECIMAL(10,2)), 0) as prezzo_per_litro
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
                    WHEN s.km_per_litro > (m.media_consumo + ? * m.dev_std_consumo) THEN 'CONSUMO_SOSPETTO_BASSO'
                    WHEN s.km_per_litro < (m.media_consumo - ? * m.dev_std_consumo) THEN 'CONSUMO_SOSPETTO_ALTO'
                    WHEN s.km_percorsi = 0 AND s.litri > 0 THEN 'KM_ZERO_CON_RIFORNIMENTO'
                    WHEN s.km_percorsi > 1000 AND s.litri < 10 THEN 'MOLTI_KM_POCO_CARBURANTE'
                    WHEN s.prezzo_per_litro > 3.0 THEN 'PREZZO_CARBURANTE_ALTO'
                    WHEN s.prezzo_per_litro < 1.0 THEN 'PREZZO_CARBURANTE_BASSO'
                    ELSE 'NORMALE'
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
            OR (s.prezzo_per_litro > 3.0)
            OR (s.prezzo_per_litro < 1.0)
            ORDER BY z_score DESC, data DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ddd", $soglia_deviazione, $soglia_deviazione, $soglia_deviazione);
    $stmt->execute();
    return $stmt->get_result();
}

// Esegui le analisi
$anomalie = identificaAnomalieConsumo($conn);

// Prepara dati per la visualizzazione
$dati_anomalie = [];
while ($row = $anomalie->fetch_assoc()) {
    $dati_anomalie[] = $row;
}

// Include header
include 'template/header.php';
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Avanzato Antifrode v2.0</title>

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #0066cc;
            --danger-color: #dc3545;
            --warning-color: #fd7e14;
            --success-color: #198754;
            --info-color: #0dcaf0;
            --dark-color: #212529;
            --light-color: #f8f9fa;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 98%;
        }

        .header-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #4c63d2 100%);
            color: white;
            padding: 2rem;
            border-radius: 20px 20px 0 0;
            text-align: center;
        }

        .header-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .anomalie-section {
            padding: 2rem;
        }

        .card-anomalie {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header-anomalie {
            background: linear-gradient(135deg, var(--danger-color) 0%, #c82333 100%);
            color: white;
            padding: 1.5rem;
            border: none;
        }

        .table-container {
            overflow-x: auto;
            max-height: 80vh;
        }

        .table-anomalie {
            margin-bottom: 0;
            font-size: 0.9rem;
            width: 100%;
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
        }

        .table-anomalie td {
            padding: 12px 8px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
            text-align: center;
        }

        .table-anomalie tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.002);
            transition: all 0.2s ease;
        }

        /* Colorazione righe basata sul tipo di anomalia */
        .row-critica {
            background-color: #f8d7da !important;
            border-left: 4px solid var(--danger-color);
        }

        .row-warning {
            background-color: #fff3cd !important;
            border-left: 4px solid var(--warning-color);
        }

        .row-flagged {
            background-color: #d1ecf1 !important;
            border-left: 4px solid var(--info-color);
        }

        .row-normale {
            background-color: #d4edda !important;
            border-left: 4px solid var(--success-color);
        }

        /* Badge personalizzati */
        .badge-anomalia {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
            border-radius: 25px;
            font-weight: 600;
        }

        .badge-critica {
            background: linear-gradient(135deg, var(--danger-color) 0%, #c82333 100%);
            color: white;
        }

        .badge-warning {
            background: linear-gradient(135deg, var(--warning-color) 0%, #e86b1a 100%);
            color: white;
        }

        .badge-normale {
            background: linear-gradient(135deg, var(--success-color) 0%, #146c3f 100%);
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
            background: linear-gradient(135deg, var(--primary-color) 0%, #4c63d2 100%);
            border: none;
            color: white;
        }

        .btn-risolvi {
            background: linear-gradient(135deg, var(--success-color) 0%, #146c3f 100%);
            border: none;
            color: white;
        }

        .btn-flag {
            background: linear-gradient(135deg, var(--warning-color) 0%, #e86b1a 100%);
            border: none;
            color: white;
        }

        /* Colonne specifiche */
        .col-data {
            width: 90px;
        }

        .col-utente {
            width: 120px;
        }

        .col-targa {
            width: 90px;
        }

        .col-filiale {
            width: 100px;
        }

        .col-km {
            width: 80px;
        }

        .col-litri {
            width: 80px;
        }

        .col-euro {
            width: 80px;
        }

        .col-prezzo {
            width: 90px;
        }

        .col-consumo {
            width: 90px;
        }

        .col-zscore {
            width: 100px;
        }

        .col-tipo {
            width: 140px;
        }

        .col-flag-id {
            width: 80px;
        }

        .col-flag-tipo {
            width: 120px;
        }

        .col-flag-note {
            width: 200px;
        }

        .col-flaggato-da {
            width: 110px;
        }

        .col-data-flag {
            width: 130px;
        }

        .col-risolto {
            width: 80px;
        }

        .col-azioni {
            width: 150px;
        }

        .text-truncate-custom {
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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

        /* Statistiche header */
        .stats-container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }

        .stat-item {
            text-align: center;
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin: 0.5rem;
            min-width: 150px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <!-- Header Section -->
        <div class="header-section">
            <h1><i class="bi bi-shield-exclamation me-3"></i>Report Avanzato Antifrode v2.0</h1>
            <p class="lead mb-0">Sistema di Rilevamento e Analisi Anomalie Consumi</p>
        </div>

        <!-- Statistiche Summary -->
        <div class="anomalie-section">
            <div class="stats-container">
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($dati_anomalie); ?></div>
                    <div class="stat-label">Anomalie Totali</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo count(array_filter($dati_anomalie, function ($a) {
                                                    return $a['z_score'] > 3;
                                                })); ?></div>
                    <div class="stat-label">Anomalie Critiche</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo count(array_filter($dati_anomalie, function ($a) {
                                                    return $a['is_flagged'] == 1;
                                                })); ?></div>
                    <div class="stat-label">Anomalie Flaggate</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo count(array_filter($dati_anomalie, function ($a) {
                                                    return $a['risolto'] == 1;
                                                })); ?></div>
                    <div class="stat-label">Anomalie Risolte</div>
                </div>
            </div>

            <!-- Tabella Anomalie Critiche Ridisegnata -->
            <?php if (!empty($dati_anomalie)): ?>
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
                                    <th class="col-km">📏<br>KM<br>Percorsi</th>
                                    <th class="col-litri">⛽<br>Litri</th>
                                    <th class="col-euro">💰<br>Euro<br>Spesi</th>
                                    <th class="col-prezzo">💲<br>Prezzo<br>€/L</th>
                                    <th class="col-consumo">📊<br>Consumo<br>KM/L</th>
                                    <th class="col-zscore">📈<br>Z-Score<br>Deviazione</th>
                                    <th class="col-tipo">⚠️<br>Tipo<br>Anomalia</th>
                                    <th class="col-flag-id">🏷️<br>Flag<br>ID</th>
                                    <th class="col-flag-tipo">🔖<br>Tipo<br>Flag</th>
                                    <th class="col-flag-note">📝<br>Note<br>Flag</th>
                                    <th class="col-flaggato-da">👮<br>Flaggato<br>Da</th>
                                    <th class="col-data-flag">📅<br>Data<br>Flag</th>
                                    <th class="col-risolto">✅<br>Stato<br>Risoluzione</th>
                                    <th class="col-azioni">⚙️<br>Azioni<br>Disponibili</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dati_anomalie as $anomalia):
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

                                        <!-- Litri -->
                                        <td class="col-litri">
                                            <?php echo number_format($anomalia['litri'], 2); ?>
                                        </td>

                                        <!-- Euro Spesi -->
                                        <td class="col-euro">
                                            <strong><?php echo number_format($anomalia['euro_spesi'], 2); ?>€</strong>
                                        </td>

                                        <!-- Prezzo per Litro -->
                                        <td class="col-prezzo">
                                            <span class="badge <?php echo $prezzo_per_litro > 2.5 ? 'bg-danger' : ($prezzo_per_litro < 1.2 ? 'bg-warning' : 'bg-success'); ?>">
                                                <?php echo number_format($prezzo_per_litro, 2); ?>€/L
                                            </span>
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
                                                    'CONSUMO_SOSPETTO_',
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
                                            <?php if (isset($anomalia['risolto']) && $anomalia['risolto']): ?>
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
                    <div class="card-footer bg-light text-center p-3">
                        <div class="row">
                            <div class="col-md-6">
                                <button class="btn btn-outline-primary" onclick="esportaAnomalie()">
                                    <i class="bi bi-file-excel me-2"></i>Esporta Tutte le Anomalie
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button class="btn btn-outline-success" onclick="generaReportPDF()">
                                    <i class="bi bi-file-pdf me-2"></i>Genera Report PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-success text-center" role="alert">
                    <i class="bi bi-check-circle-fill fs-1"></i>
                    <h4 class="mt-3">Nessuna Anomalia Rilevata!</h4>
                    <p>Il sistema non ha identificato anomalie significative nei consumi.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JavaScript per le funzioni interattive -->
    <script>
        function dettaglioAnomalia(id) {
            // Implementa la visualizzazione dei dettagli
            alert('Dettaglio anomalia ID: ' + id);
            // Qui puoi aprire un modal o navigare a una pagina di dettaglio
        }

        function flagAnomalia(id) {
            // Implementa la funzione di flag
            if (confirm('Vuoi segnalare questa anomalia per revisione?')) {
                // AJAX call per flaggare l'anomalia
                alert('Anomalia ID ' + id + ' segnalata per revisione');
                location.reload(); // Ricarica la pagina per aggiornare i dati
            }
        }

        function unflagAnomalia(id) {
            // Implementa la funzione di unflag (risoluzione)
            if (confirm('Vuoi marcare questa anomalia come risolta?')) {
                // AJAX call per risolvere l'anomalia
                alert('Anomalia ID ' + id + ' marcata come risolta');
                location.reload(); // Ricarica la pagina per aggiornare i dati
            }
        }

        function esportaAnomalie() {
            // Implementa l'esportazione in Excel
            window.location.href = 'export_anomalie.php';
        }

        function generaReportPDF() {
            // Implementa la generazione del PDF
            window.location.href = 'create_pdf.php?tipo=anomalie';
        }

        // Inizializzazione tooltips Bootstrap
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>

</html>

<?php
// Include footer se necessario
// include 'template/footer.php';
?>