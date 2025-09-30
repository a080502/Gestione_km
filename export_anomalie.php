<?php
/**
 * Esportazione Solo Anomalie in formato Excel/CSV
 */

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include_once 'config.php';
include_once 'dati_utente.php';

// Verifica privilegi
if (!isset($dati_utente) || $dati_utente === null || $dati_utente['livello'] > 2) {
    header("Location: unauthorized.php");
    exit();
}

$formato = isset($_GET['formato']) ? $_GET['formato'] : 'csv';

function getAnomaliePerId($conn, $ids) {
    if (empty($ids)) return false;
    
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $sql = "SELECT 
                c.id,
                c.data,
                c.username,
                c.targa_mezzo,
                c.filiale,
                c.divisione,
                (c.chilometri_finali - c.chilometri_iniziali) as km_percorsi,
                CAST(c.litri_carburante as DECIMAL(10,2)) as litri,
                c.euro_spesi,
                (c.chilometri_finali - c.chilometri_iniziali) / NULLIF(CAST(c.litri_carburante as DECIMAL(10,2)), 0) as km_per_litro,
                c.euro_spesi / NULLIF(CAST(c.litri_carburante as DECIMAL(10,2)), 0) as prezzo_per_litro,
                c.note,
                af.tipo_flag,
                af.note as note_flag,
                af.flaggato_da,
                af.data_flag
            FROM chilometri c
            LEFT JOIN anomalie_flaggate af ON c.id = af.id_registrazione
            WHERE c.id IN ($placeholders)
            ORDER BY c.data DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    return $stmt->get_result();
}

// Se vengono passati specifici ID di anomalie
$ids_anomalie = [];
if (isset($_GET['ids']) && !empty($_GET['ids'])) {
    $ids_anomalie = array_map('intval', explode(',', $_GET['ids']));
} else {
    // Altrimenti recupera tutte le anomalie
    $sql_ids = "WITH stats AS (
                    SELECT 
                        id,
                        (chilometri_finali - chilometri_iniziali) / NULLIF(CAST(litri_carburante as DECIMAL(10,2)), 0) as km_per_litro,
                        targa_mezzo
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
                SELECT s.id
                FROM stats s
                JOIN medie m ON s.targa_mezzo = m.targa_mezzo
                WHERE ABS(s.km_per_litro - m.media_consumo) / NULLIF(m.dev_std_consumo, 0) > 2.0";
    
    $result_ids = $conn->query($sql_ids);
    if (!$result_ids) {
        // Errore nella query - potrebbe essere un problema di compatibilità MySQL
        error_log("Errore query anomalie: " . $conn->error);
        ?>
        <!DOCTYPE html>
        <html lang="it">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Errore Analisi Anomalie</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h4><i class="fas fa-exclamation-triangle me-2"></i>Errore nell'Analisi Anomalie</h4>
                            </div>
                            <div class="card-body">
                                <p class="lead">Si è verificato un errore durante l'analisi delle anomalie di consumo carburante.</p>
                                <p>Possibili cause:</p>
                                <ul>
                                    <li>Dati insufficienti per l'analisi statistica</li>
                                    <li>Problema temporaneo con il database</li>
                                    <li>Versione MySQL non compatibile con le query avanzate</li>
                                </ul>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                                    <a href="dashboard_bi.php" class="btn btn-primary">
                                        <i class="fas fa-chart-bar me-2"></i>Dashboard
                                    </a>
                                    <a href="visualizza.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-table me-2"></i>Visualizza Dati
                                    </a>
                                    <a href="javascript:history.back()" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Indietro
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
    
    while ($row = $result_ids->fetch_assoc()) {
        $ids_anomalie[] = $row['id'];
    }
}

if (empty($ids_anomalie)) {
    // Nessuna anomalia trovata - mostra una pagina informativa
    ?>
    <!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Nessuna Anomalia da Esportare</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            .info-container {
                min-height: 80vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .info-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border-radius: 15px;
                box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            }
            .info-icon {
                font-size: 4rem;
                margin-bottom: 1rem;
                opacity: 0.8;
            }
        </style>
    </head>
    <body class="bg-light">
        <div class="container info-container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card info-card">
                        <div class="card-body text-center p-5">
                            <i class="fas fa-check-circle info-icon"></i>
                            <h2 class="card-title mb-4">Nessuna Anomalia Rilevata</h2>
                            <p class="card-text lead mb-4">
                                Ottima notizia! Non sono state trovate anomalie nei dati di consumo carburante 
                                degli ultimi 12 mesi.
                            </p>
                            <p class="card-text mb-4">
                                Il sistema ha analizzato i consumi di tutti i veicoli e non ha rilevato 
                                variazioni significative rispetto ai pattern di consumo normali.
                            </p>
                            <div class="d-grid gap-2">
                                <a href="dashboard_bi.php" class="btn btn-light btn-lg">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    Vai alla Dashboard
                                </a>
                                <a href="visualizza.php" class="btn btn-outline-light">
                                    <i class="fas fa-table me-2"></i>
                                    Visualizza Tutti i Dati
                                </a>
                                <a href="javascript:history.back()" class="btn btn-outline-light">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Torna Indietro
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

$anomalie_result = getAnomaliePerId($conn, $ids_anomalie);

if ($formato === 'csv') {
    $filename = "anomalie_" . date('Y-m-d_H-i-s') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF"); // BOM UTF-8
    
    fputcsv($output, ['REPORT ANOMALIE - Generato il ' . date('d/m/Y H:i:s')], ';');
    fputcsv($output, ['Totale anomalie esportate: ' . count($ids_anomalie)], ';');
    fputcsv($output, [''], ';');
    
    fputcsv($output, [
        'ID', 'Data', 'Username', 'Targa', 'Filiale', 'Divisione',
        'KM Percorsi', 'Litri', 'Euro Spesi', 'KM/L', 'Prezzo/L',
        'Note Registrazione', 'Flaggata', 'Tipo Flag', 'Note Flag', 'Flaggato Da'
    ], ';');
    
    while ($row = $anomalie_result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['data'],
            $row['username'],
            $row['targa_mezzo'],
            $row['filiale'],
            $row['divisione'],
            round($row['km_percorsi']),
            round($row['litri'], 2),
            round($row['euro_spesi'], 2),
            round($row['km_per_litro'], 2),
            round($row['prezzo_per_litro'], 2),
            $row['note'],
            $row['tipo_flag'] ? 'SÌ' : 'NO',
            $row['tipo_flag'] ?: '',
            $row['note_flag'] ?: '',
            $row['flaggato_da'] ?: ''
        ], ';');
    }
    
    fclose($output);
} else {
    // Formato Excel (HTML)
    $filename = "anomalie_" . date('Y-m-d_H-i-s') . ".xlsx";
    
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo "\xEF\xBB\xBF"; // BOM UTF-8
    ?>
    <html xmlns:x="urn:schemas-microsoft-com:office:excel">
    <head>
        <meta charset="UTF-8">
        <style>
            .header { background-color: #DC3545; color: white; font-weight: bold; }
            .flaggata { background-color: #FFF3CD; }
            .critica { background-color: #F8D7DA; }
            .numero { mso-number-format: "#,##0.00"; }
            .data { mso-number-format: "dd/mm/yyyy"; }
        </style>
    </head>
    <body>
    
    <table border="1">
        <tr><td colspan="16" class="header">REPORT ANOMALIE ANTIFRODE</td></tr>
        <tr><td>Generato il:</td><td><?php echo date('d/m/Y H:i:s'); ?></td></tr>
        <tr><td>Totale anomalie:</td><td><?php echo count($ids_anomalie); ?></td></tr>
        <tr><td>Operatore:</td><td><?php echo $_SESSION['username']; ?></td></tr>
        <tr><td colspan="16">&nbsp;</td></tr>
        
        <tr class="header">
            <td>ID</td><td>Data</td><td>Username</td><td>Targa</td><td>Filiale</td><td>Divisione</td>
            <td>KM Percorsi</td><td>Litri</td><td>Euro Spesi</td><td>KM/L</td><td>Prezzo/L</td>
            <td>Note Registrazione</td><td>Flaggata</td><td>Tipo Flag</td><td>Note Flag</td><td>Flaggato Da</td>
        </tr>
        
        <?php while ($row = $anomalie_result->fetch_assoc()): 
            $classe = '';
            if ($row['tipo_flag']) $classe = 'flaggata';
            if ($row['km_per_litro'] > 20 || $row['km_per_litro'] < 5) $classe = 'critica';
        ?>
        <tr class="<?php echo $classe; ?>">
            <td><?php echo $row['id']; ?></td>
            <td class="data"><?php echo date('d/m/Y', strtotime($row['data'])); ?></td>
            <td><?php echo htmlspecialchars($row['username']); ?></td>
            <td><?php echo htmlspecialchars($row['targa_mezzo']); ?></td>
            <td><?php echo htmlspecialchars($row['filiale']); ?></td>
            <td><?php echo htmlspecialchars($row['divisione']); ?></td>
            <td class="numero"><?php echo round($row['km_percorsi']); ?></td>
            <td class="numero"><?php echo round($row['litri'], 2); ?></td>
            <td class="numero"><?php echo round($row['euro_spesi'], 2); ?></td>
            <td class="numero"><?php echo round($row['km_per_litro'], 2); ?></td>
            <td class="numero"><?php echo round($row['prezzo_per_litro'], 2); ?></td>
            <td><?php echo htmlspecialchars($row['note'] ?: ''); ?></td>
            <td><?php echo $row['tipo_flag'] ? 'SÌ' : 'NO'; ?></td>
            <td><?php echo htmlspecialchars($row['tipo_flag'] ?: ''); ?></td>
            <td><?php echo htmlspecialchars($row['note_flag'] ?: ''); ?></td>
            <td><?php echo htmlspecialchars($row['flaggato_da'] ?: ''); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    
    </body>
    </html>
    <?php
}

exit();
?>