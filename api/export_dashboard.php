<?php
// File: api/export_dashboard.php
// Esportazione dati dashboard in formato Excel

session_start();

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    exit('Non autorizzato');
}

include_once '../config.php';
include '../query/qutenti.php';

$username = $_SESSION['username'];
$utente_data = get_user_data($conn, $username);
$livello = $utente_data['livello'];

// Solo admin e responsabili possono esportare
if ($livello > 2) {
    http_response_code(403);
    exit('Accesso non autorizzato');
}

// Headers per download Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Dashboard_Report_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard Report - <?= date('Y-m-d') ?></title>
</head>
<body>
    <h2>📊 Dashboard Report Flotte - <?= date('d/m/Y') ?></h2>
    
    <h3>KPI Principali</h3>
    <table border="1">
        <tr>
            <th>Metrica</th>
            <th>Valore Corrente</th>
            <th>Trend %</th>
            <th>Status</th>
        </tr>
        <?php
        // Ricrea i calcoli KPI per l'export
        $currentMonth = date('Y-m');
        
        // Chilometri totali mese
        $sql = "SELECT SUM(chilometri_finali - chilometri_iniziali) as total_km FROM chilometri WHERE DATE_FORMAT(data, '%Y-%m') = ?";
        if ($livello > 1) $sql .= " AND filiale = ?";
        
        $stmt = $conn->prepare($sql);
        if ($livello > 1) {
            $stmt->bind_param("ss", $currentMonth, $utente_data['filiale']);
        } else {
            $stmt->bind_param("s", $currentMonth);
        }
        $stmt->execute();
        $totalKm = $stmt->get_result()->fetch_assoc()['total_km'] ?? 0;
        
        // Consumo medio
        $sql2 = "SELECT SUM(CAST(litri_carburante AS DECIMAL(10,2))) / SUM(chilometri_finali - chilometri_iniziali) * 100 as avg_consumption 
                 FROM chilometri 
                 WHERE DATE_FORMAT(data, '%Y-%m') = ? AND litri_carburante IS NOT NULL AND litri_carburante != '' AND litri_carburante != '0'";
        if ($livello > 1) $sql2 .= " AND filiale = ?";
        
        $stmt2 = $conn->prepare($sql2);
        if ($livello > 1) {
            $stmt2->bind_param("ss", $currentMonth, $utente_data['filiale']);
        } else {
            $stmt2->bind_param("s", $currentMonth);
        }
        $stmt2->execute();
        $avgConsumption = $stmt2->get_result()->fetch_assoc()['avg_consumption'] ?? 0;
        
        // Costo totale
        $sql3 = "SELECT SUM(euro_spesi) as total_cost FROM chilometri WHERE DATE_FORMAT(data, '%Y-%m') = ? AND euro_spesi IS NOT NULL";
        if ($livello > 1) $sql3 .= " AND filiale = ?";
        
        $stmt3 = $conn->prepare($sql3);
        if ($livello > 1) {
            $stmt3->bind_param("ss", $currentMonth, $utente_data['filiale']);
        } else {
            $stmt3->bind_param("s", $currentMonth);
        }
        $stmt3->execute();
        $totalCost = $stmt3->get_result()->fetch_assoc()['total_cost'] ?? 0;
        ?>
        
        <tr>
            <td>Chilometri Percorsi</td>
            <td><?= number_format($totalKm) ?></td>
            <td>-</td>
            <td><?= $totalKm > 0 ? '✅ OK' : '❌ Basso' ?></td>
        </tr>
        <tr>
            <td>Consumo Medio (L/100km)</td>
            <td><?= number_format($avgConsumption, 2) ?></td>
            <td>-</td>
            <td><?= $avgConsumption < 8.5 ? '✅ Efficiente' : '⚠️ Alto' ?></td>
        </tr>
        <tr>
            <td>Costo Totale Carburante</td>
            <td>€ <?= number_format($totalCost, 2) ?></td>
            <td>-</td>
            <td>ℹ️ Monitoraggio</td>
        </tr>
    </table>
    
    <br><br>
    
    <h3>Dettaglio per Veicolo</h3>
    <table border="1">
        <tr>
            <th>Targa</th>
            <th>Operatore</th>
            <th>Filiale</th>
            <th>Km Mese</th>
            <th>Litri</th>
            <th>L/100km</th>
            <th>€ Spesi</th>
            <th>Ultima Registrazione</th>
        </tr>
        <?php
        // Query dettaglio veicoli
        $sql_detail = "SELECT 
                          c.targa_mezzo,
                          c.username,
                          c.filiale,
                          SUM(c.chilometri_finali - c.chilometri_iniziali) as km_totali,
                          SUM(CAST(COALESCE(c.litri_carburante, 0) AS DECIMAL(10,2))) as litri_totali,
                          SUM(COALESCE(c.euro_spesi, 0)) as costo_totale,
                          MAX(c.data) as ultima_registrazione,
                          (SUM(CAST(COALESCE(c.litri_carburante, 0) AS DECIMAL(10,2))) / SUM(c.chilometri_finali - c.chilometri_iniziali) * 100) as consumo_medio
                       FROM chilometri c
                       WHERE DATE_FORMAT(c.data, '%Y-%m') = ?";
        
        if ($livello > 1) {
            $sql_detail .= " AND c.filiale = ?";
        }
        
        $sql_detail .= " GROUP BY c.targa_mezzo, c.username, c.filiale 
                         ORDER BY km_totali DESC";
        
        $stmt_detail = $conn->prepare($sql_detail);
        
        if ($livello > 1) {
            $stmt_detail->bind_param("ss", $currentMonth, $utente_data['filiale']);
        } else {
            $stmt_detail->bind_param("s", $currentMonth);
        }
        
        $stmt_detail->execute();
        $result_detail = $stmt_detail->get_result();
        
        while ($row = $result_detail->fetch_assoc()):
        ?>
        <tr>
            <td><?= htmlspecialchars($row['targa_mezzo']) ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['filiale']) ?></td>
            <td><?= number_format($row['km_totali']) ?></td>
            <td><?= number_format($row['litri_totali'], 2) ?></td>
            <td><?= $row['consumo_medio'] ? number_format($row['consumo_medio'], 2) : 'N/D' ?></td>
            <td>€ <?= number_format($row['costo_totale'], 2) ?></td>
            <td><?= date('d/m/Y', strtotime($row['ultima_registrazione'])) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    
    <br><br>
    
    <h3>Performance per Filiale</h3>
    <table border="1">
        <tr>
            <th>Filiale</th>
            <th>Divisione</th>
            <th>Km Totali</th>
            <th>N° Veicoli Attivi</th>
            <th>Consumo Medio</th>
            <th>Costo Totale</th>
        </tr>
        <?php
        $sql_branch = "SELECT 
                          c.filiale,
                          c.divisione,
                          SUM(c.chilometri_finali - c.chilometri_iniziali) as km_totali,
                          COUNT(DISTINCT c.targa_mezzo) as veicoli_attivi,
                          AVG(
                              CASE 
                                  WHEN c.litri_carburante IS NOT NULL AND c.litri_carburante != '' AND c.litri_carburante != '0'
                                  THEN (CAST(c.litri_carburante AS DECIMAL(10,2)) / (c.chilometri_finali - c.chilometri_iniziali) * 100)
                                  ELSE NULL
                              END
                          ) as consumo_medio,
                          SUM(COALESCE(c.euro_spesi, 0)) as costo_totale
                       FROM chilometri c
                       WHERE DATE_FORMAT(c.data, '%Y-%m') = ?";
        
        if ($livello > 1) {
            $sql_branch .= " AND c.filiale = ?";
        }
        
        $sql_branch .= " GROUP BY c.filiale, c.divisione 
                         ORDER BY km_totali DESC";
        
        $stmt_branch = $conn->prepare($sql_branch);
        
        if ($livello > 1) {
            $stmt_branch->bind_param("ss", $currentMonth, $utente_data['filiale']);
        } else {
            $stmt_branch->bind_param("s", $currentMonth);
        }
        
        $stmt_branch->execute();
        $result_branch = $stmt_branch->get_result();
        
        while ($row = $result_branch->fetch_assoc()):
        ?>
        <tr>
            <td><?= htmlspecialchars($row['filiale']) ?></td>
            <td><?= htmlspecialchars($row['divisione']) ?></td>
            <td><?= number_format($row['km_totali']) ?></td>
            <td><?= $row['veicoli_attivi'] ?></td>
            <td><?= $row['consumo_medio'] ? number_format($row['consumo_medio'], 2) . ' L/100km' : 'N/D' ?></td>
            <td>€ <?= number_format($row['costo_totale'], 2) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    
    <br><br>
    <p><small>Report generato il <?= date('d/m/Y H:i:s') ?> dal sistema Gestione Chilometri</small></p>
    
</body>
</html>

<?php $conn->close(); ?>