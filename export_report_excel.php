<?php
/**
 * Esportazione Report Avanzato Antifrode in formato Excel/CSV
 */

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include_once 'config.php';
include_once 'dati_utente.php';

// Verifica che i dati utente siano disponibili
if (!isset($dati_utente) || $dati_utente === null) {
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

// Parametri di filtro dalla query string
$filtro_periodo = isset($_GET['periodo']) ? intval($_GET['periodo']) : 12;
$filtro_targa = isset($_GET['targa']) ? $_GET['targa'] : '';
$filtro_filiale = isset($_GET['filiale']) ? $_GET['filiale'] : '';
$formato = isset($_GET['formato']) ? $_GET['formato'] : 'xlsx'; // xlsx o csv

// Funzioni per recuperare i dati (stesso codice del report)
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
                filiale,
                divisione,
                AVG(chilometri_finali - chilometri_iniziali) as km_medi,
                AVG(CAST(litri_carburante as DECIMAL(10,2))) as litri_medi,
                AVG(euro_spesi) as costo_medio,
                AVG((chilometri_finali - chilometri_iniziali) / NULLIF(CAST(litri_carburante as DECIMAL(10,2)), 0)) as consumo_medio_km_litro,
                COUNT(*) as numero_rifornimenti,
                SUM(chilometri_finali - chilometri_iniziali) as km_totali,
                SUM(CAST(litri_carburante as DECIMAL(10,2))) as litri_totali,
                SUM(euro_spesi) as costo_totale,
                MIN(data) as prima_registrazione,
                MAX(data) as ultima_registrazione
            FROM chilometri 
            WHERE " . implode(" AND ", $where_clauses) . "
            GROUP BY targa_mezzo, username, filiale, divisione
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
                    filiale,
                    divisione,
                    (chilometri_finali - chilometri_iniziali) as km_percorsi,
                    CAST(litri_carburante as DECIMAL(10,2)) as litri,
                    euro_spesi,
                    (chilometri_finali - chilometri_iniziali) / NULLIF(CAST(litri_carburante as DECIMAL(10,2)), 0) as km_per_litro,
                    note
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
                    ELSE 'ANOMALIA_GENERICA'
                END as tipo_anomalia,
                s.euro_spesi / NULLIF(s.litri, 0) as prezzo_per_litro
            FROM stats s
            JOIN medie m ON s.targa_mezzo = m.targa_mezzo
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
                AVG((chilometri_finali - chilometri_iniziali) / NULLIF(CAST(litri_carburante as DECIMAL(10,2)), 0)) as consumo_medio,
                AVG(euro_spesi / NULLIF(CAST(litri_carburante as DECIMAL(10,2)), 0)) as prezzo_medio_litro
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

// Recupera tutti i dati
$consumi_medi = calcolaConsumiMedi($conn, $filtro_targa, $filtro_periodo);
$anomalie = identificaAnomalieConsumo($conn);
$andamento_consumi = getAndamentoConsumi($conn, $filtro_periodo);

if ($formato === 'csv') {
    // Esportazione CSV
    $filename = "report_antifrode_" . date('Y-m-d_H-i-s') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // BOM per supportare UTF-8 in Excel
    fputs($output, "\xEF\xBB\xBF");
    
    // Sheet 1: Consumi per Utente
    fputcsv($output, ['=== REPORT ANTIFRODE - CONSUMI PER UTENTE ==='], ';');
    fputcsv($output, ['Generato il:', date('d/m/Y H:i:s')], ';');
    fputcsv($output, ['Periodo:', $filtro_periodo . ' mesi'], ';');
    fputcsv($output, ['Filtro Targa:', $filtro_targa ?: 'Tutte'], ';');
    fputcsv($output, [''], ';');
    
    fputcsv($output, [
        'Username', 'Targa', 'Filiale', 'Divisione', 'Rifornimenti', 
        'KM Totali', 'Litri Totali', 'Costo Totale (€)', 'Consumo Medio (KM/L)',
        'Costo Medio', 'Prima Registrazione', 'Ultima Registrazione'
    ], ';');
    
    while ($row = $consumi_medi->fetch_assoc()) {
        fputcsv($output, [
            $row['username'],
            $row['targa_mezzo'],
            $row['filiale'],
            $row['divisione'],
            $row['numero_rifornimenti'],
            round($row['km_totali']),
            round($row['litri_totali'], 2),
            round($row['costo_totale'], 2),
            round($row['consumo_medio_km_litro'], 2),
            round($row['costo_medio'], 2),
            $row['prima_registrazione'],
            $row['ultima_registrazione']
        ], ';');
    }
    
    fputcsv($output, [''], ';');
    fputcsv($output, [''], ';');
    
    // Sheet 2: Anomalie Rilevate
    fputcsv($output, ['=== ANOMALIE RILEVATE ==='], ';');
    fputcsv($output, [''], ';');
    fputcsv($output, [
        'ID', 'Data', 'Username', 'Targa', 'Filiale', 'KM Percorsi', 
        'Litri', 'Euro Spesi', 'KM/L', 'Prezzo/L', 'Z-Score', 
        'Tipo Anomalia', 'Note'
    ], ';');
    
    while ($row = $anomalie->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['data'],
            $row['username'],
            $row['targa_mezzo'],
            $row['filiale'],
            round($row['km_percorsi']),
            round($row['litri'], 2),
            round($row['euro_spesi'], 2),
            round($row['km_per_litro'], 2),
            round($row['prezzo_per_litro'], 2),
            round($row['z_score'], 2),
            $row['tipo_anomalia'],
            $row['note']
        ], ';');
    }
    
    fputcsv($output, [''], ';');
    fputcsv($output, [''], ';');
    
    // Sheet 3: Andamento Mensile
    fputcsv($output, ['=== ANDAMENTO MENSILE ==='], ';');
    fputcsv($output, [''], ';');
    fputcsv($output, [
        'Mese', 'Numero Rifornimenti', 'KM Totali', 'Litri Totali', 
        'Costo Totale (€)', 'Consumo Medio (KM/L)', 'Prezzo Medio/L (€)'
    ], ';');
    
    while ($row = $andamento_consumi->fetch_assoc()) {
        fputcsv($output, [
            $row['mese'],
            $row['numero_rifornimenti'],
            round($row['km_totali']),
            round($row['litri_totali'], 2),
            round($row['costo_totale'], 2),
            round($row['consumo_medio'], 2),
            round($row['prezzo_medio_litro'], 2)
        ], ';');
    }
    
    fclose($output);
    exit();

} else {
    // Esportazione XLSX (Fake Excel usando HTML)
    $filename = "report_antifrode_" . date('Y-m-d_H-i-s') . ".xlsx";
    
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo "\xEF\xBB\xBF"; // BOM UTF-8
    
    ?>
    <html xmlns:x="urn:schemas-microsoft-com:office:excel">
    <head>
        <meta charset="UTF-8">
        <style>
            .header { background-color: #4472C4; color: white; font-weight: bold; }
            .anomalia-critica { background-color: #FFC7CE; }
            .anomalia-media { background-color: #FFEB9C; }
            .numero { mso-number-format: "#,##0.00"; }
            .data { mso-number-format: "dd/mm/yyyy"; }
        </style>
    </head>
    <body>
    
    <!-- Sheet 1: Consumi per Utente -->
    <table border="1">
        <tr><td colspan="12" class="header">REPORT ANTIFRODE - CONSUMI PER UTENTE</td></tr>
        <tr><td>Generato il:</td><td><?php echo date('d/m/Y H:i:s'); ?></td></tr>
        <tr><td>Periodo:</td><td><?php echo $filtro_periodo; ?> mesi</td></tr>
        <tr><td>Filtro Targa:</td><td><?php echo $filtro_targa ?: 'Tutte'; ?></td></tr>
        <tr><td colspan="12">&nbsp;</td></tr>
        
        <tr class="header">
            <td>Username</td><td>Targa</td><td>Filiale</td><td>Divisione</td>
            <td>Rifornimenti</td><td>KM Totali</td><td>Litri Totali</td>
            <td>Costo Totale (€)</td><td>Consumo Medio (KM/L)</td>
            <td>Costo Medio (€)</td><td>Prima Registrazione</td><td>Ultima Registrazione</td>
        </tr>
        
        <?php 
        // Reset del result set
        $consumi_medi = calcolaConsumiMedi($conn, $filtro_targa, $filtro_periodo);
        while ($row = $consumi_medi->fetch_assoc()): 
        ?>
        <tr>
            <td><?php echo htmlspecialchars($row['username']); ?></td>
            <td><?php echo htmlspecialchars($row['targa_mezzo']); ?></td>
            <td><?php echo htmlspecialchars($row['filiale']); ?></td>
            <td><?php echo htmlspecialchars($row['divisione']); ?></td>
            <td><?php echo $row['numero_rifornimenti']; ?></td>
            <td class="numero"><?php echo round($row['km_totali']); ?></td>
            <td class="numero"><?php echo round($row['litri_totali'], 2); ?></td>
            <td class="numero"><?php echo round($row['costo_totale'], 2); ?></td>
            <td class="numero"><?php echo round($row['consumo_medio_km_litro'], 2); ?></td>
            <td class="numero"><?php echo round($row['costo_medio'], 2); ?></td>
            <td class="data"><?php echo date('d/m/Y', strtotime($row['prima_registrazione'])); ?></td>
            <td class="data"><?php echo date('d/m/Y', strtotime($row['ultima_registrazione'])); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    
    <br><br>
    
    <!-- Sheet 2: Anomalie -->
    <table border="1">
        <tr><td colspan="13" class="header">ANOMALIE RILEVATE</td></tr>
        <tr><td colspan="13">&nbsp;</td></tr>
        
        <tr class="header">
            <td>ID</td><td>Data</td><td>Username</td><td>Targa</td><td>Filiale</td>
            <td>KM Percorsi</td><td>Litri</td><td>Euro Spesi</td><td>KM/L</td>
            <td>Prezzo/L (€)</td><td>Z-Score</td><td>Tipo Anomalia</td><td>Note</td>
        </tr>
        
        <?php 
        // Reset del result set
        $anomalie = identificaAnomalieConsumo($conn);
        while ($row = $anomalie->fetch_assoc()): 
            $classe = $row['z_score'] > 3 ? 'anomalia-critica' : 'anomalia-media';
        ?>
        <tr class="<?php echo $classe; ?>">
            <td><?php echo $row['id']; ?></td>
            <td class="data"><?php echo date('d/m/Y', strtotime($row['data'])); ?></td>
            <td><?php echo htmlspecialchars($row['username']); ?></td>
            <td><?php echo htmlspecialchars($row['targa_mezzo']); ?></td>
            <td><?php echo htmlspecialchars($row['filiale']); ?></td>
            <td class="numero"><?php echo round($row['km_percorsi']); ?></td>
            <td class="numero"><?php echo round($row['litri'], 2); ?></td>
            <td class="numero"><?php echo round($row['euro_spesi'], 2); ?></td>
            <td class="numero"><?php echo round($row['km_per_litro'], 2); ?></td>
            <td class="numero"><?php echo round($row['prezzo_per_litro'], 2); ?></td>
            <td class="numero"><?php echo round($row['z_score'], 2); ?></td>
            <td><?php echo htmlspecialchars($row['tipo_anomalia']); ?></td>
            <td><?php echo htmlspecialchars($row['note']); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    
    <br><br>
    
    <!-- Sheet 3: Andamento Mensile -->
    <table border="1">
        <tr><td colspan="7" class="header">ANDAMENTO MENSILE</td></tr>
        <tr><td colspan="7">&nbsp;</td></tr>
        
        <tr class="header">
            <td>Mese</td><td>Numero Rifornimenti</td><td>KM Totali</td>
            <td>Litri Totali</td><td>Costo Totale (€)</td>
            <td>Consumo Medio (KM/L)</td><td>Prezzo Medio/L (€)</td>
        </tr>
        
        <?php 
        // Reset del result set
        $andamento_consumi = getAndamentoConsumi($conn, $filtro_periodo);
        while ($row = $andamento_consumi->fetch_assoc()): 
        ?>
        <tr>
            <td><?php echo $row['mese']; ?></td>
            <td><?php echo $row['numero_rifornimenti']; ?></td>
            <td class="numero"><?php echo round($row['km_totali']); ?></td>
            <td class="numero"><?php echo round($row['litri_totali'], 2); ?></td>
            <td class="numero"><?php echo round($row['costo_totale'], 2); ?></td>
            <td class="numero"><?php echo round($row['consumo_medio'], 2); ?></td>
            <td class="numero"><?php echo round($row['prezzo_medio_litro'], 2); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    
    </body>
    </html>
    <?php
    exit();
}

$conn->close();
?>