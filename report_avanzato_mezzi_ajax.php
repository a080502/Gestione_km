<?php
// Endpoint AJAX per la tabella mezzi filtrata
include_once 'config.php';

$filtro_utente = isset($_GET['utente']) ? $_GET['utente'] : '';
$filtro_periodo = isset($_GET['periodo']) ? $_GET['periodo'] : '12';
$filtro_targa = isset($_GET['targa']) ? $_GET['targa'] : '';
$filtro_filiale = isset($_GET['filiale']) ? $_GET['filiale'] : '';

function getStatisticheUtenti($conn, $filtro_utente = '', $filtro_targa = '', $filtro_filiale = '', $filtro_periodo = '12')
{
    $where = [];
    $params = [];
    $types = '';
    if (is_numeric($filtro_periodo) && (int)$filtro_periodo > 0) {
        $where[] = "data >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)";
        $params[] = (int)$filtro_periodo;
        $types .= 'i';
    } else {
        $where[] = "data >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
    }
    if (!empty($filtro_targa)) {
        $where[] = "targa_mezzo = ?";
        $params[] = $filtro_targa;
        $types .= 's';
    }
    if (!empty($filtro_filiale)) {
        $where[] = "filiale = ?";
        $params[] = $filtro_filiale;
        $types .= 's';
    }
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

$statistiche_utenti = getStatisticheUtenti($conn, $filtro_utente, $filtro_targa, $filtro_filiale, $filtro_periodo);

// Output solo le righe <tr> per la tabella principale (stesso ordine colonne)
$pos = 1;
while ($row = $statistiche_utenti->fetch_assoc()) {
    if ($row['consumo_medio'] > 5 && $row['consumo_medio'] < 50) {
        echo '<tr>';
        echo '<td>' . ($pos++) . '</td>';
        echo '<td>' . htmlspecialchars($row['username']) . '</td>';
        echo '<td>' . htmlspecialchars($row['targa_mezzo']) . '</td>';
        echo '<td class="text-success fw-bold">' . number_format($row['consumo_medio'], 2) . '</td>';
        echo '<td>' . number_format($row['km_totali']) . '</td>';
        echo '<td class="text-success">';
        echo '<i class="bi bi-arrow-down me-1"></i>';
        echo number_format(($row['consumo_medio'] - 10) * $row['litri_totali'] * 1.6, 0) . '€';
        echo '</td>';
        echo '</tr>';
    }
}
