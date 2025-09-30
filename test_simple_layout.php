<?php
// Test semplificato per layout tabella
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Query semplificata
$query = "SELECT c.id, c.data, u.username, c.targa_mezzo, c.km_percorsi, c.litri, c.euro_spesi,
                 (c.km_percorsi / c.litri) as km_per_litro, 
                 3.5 as z_score, 'TEST' as tipo_anomalia,
                 COALESCE(af.id, 0) as is_flagged,
                 af.flaggato_da, af.data_flag
          FROM chilometri c 
          JOIN utenti u ON c.utente_id = u.id
          LEFT JOIN anomalie_flaggate af ON c.id = af.chilometro_id
          WHERE c.data >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
          ORDER BY c.data DESC
          LIMIT 5";

$result = mysqli_query($connection, $query);
$anomalie = [];
while ($row = mysqli_fetch_assoc($result)) {
    $anomalie[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Layout Semplificato</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .simple-table {
            table-layout: fixed !important;
            width: 100% !important;
            border-collapse: collapse !important;
        }
        
        .simple-table th, .simple-table td {
            border: 1px solid #dee2e6 !important;
            padding: 8px !important;
            text-align: center !important;
        }
        
        /* Larghezze fisse uguali per tutte le colonne per test */
        .simple-table th:nth-child(1), .simple-table td:nth-child(1) { width: 100px !important; }
        .simple-table th:nth-child(2), .simple-table td:nth-child(2) { width: 100px !important; }
        .simple-table th:nth-child(3), .simple-table td:nth-child(3) { width: 100px !important; }
        .simple-table th:nth-child(4), .simple-table td:nth-child(4) { width: 80px !important; }
        .simple-table th:nth-child(5), .simple-table td:nth-child(5) { width: 80px !important; }
        .simple-table th:nth-child(6), .simple-table td:nth-child(6) { width: 80px !important; }
        .simple-table th:nth-child(7), .simple-table td:nth-child(7) { width: 80px !important; }
        .simple-table th:nth-child(8), .simple-table td:nth-child(8) { width: 80px !important; }
        .simple-table th:nth-child(9), .simple-table td:nth-child(9) { width: 120px !important; }
        .simple-table th:nth-child(10), .simple-table td:nth-child(10) { width: 100px !important; }
        .simple-table th:nth-child(11), .simple-table td:nth-child(11) { width: 60px !important; }
        
        .flagged-row { background-color: rgba(13, 202, 240, 0.1) !important; }
        .normal-row { background-color: rgba(255, 193, 7, 0.1) !important; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h2>Test Layout Semplificato</h2>
        <p>Test per verificare allineamento colonne con struttura semplificata</p>
        
        <table class="simple-table">
            <thead style="background-color: #dc3545; color: white;">
                <tr>
                    <th>DATA</th>
                    <th>UTENTE</th>
                    <th>TARGA</th>
                    <th>KM</th>
                    <th>LITRI</th>
                    <th>€</th>
                    <th>KM/L</th>
                    <th>Z-SCORE</th>
                    <th>TIPO</th>
                    <th>AZIONI</th>
                    <th>FLAG</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($anomalie as $anomalia): 
                    $is_flagged = $anomalia['is_flagged'] > 0;
                    $row_class = $is_flagged ? 'flagged-row' : 'normal-row';
                ?>
                <tr class="<?php echo $row_class; ?>">
                    <td><?php echo date('d/m/y', strtotime($anomalia['data'])); ?></td>
                    <td><?php echo htmlspecialchars($anomalia['username']); ?></td>
                    <td><?php echo htmlspecialchars($anomalia['targa_mezzo']); ?></td>
                    <td><?php echo number_format($anomalia['km_percorsi']); ?></td>
                    <td><?php echo number_format($anomalia['litri'], 1); ?></td>
                    <td><?php echo number_format($anomalia['euro_spesi'], 1); ?>€</td>
                    <td><?php echo number_format($anomalia['km_per_litro'], 1); ?></td>
                    <td><?php echo number_format($anomalia['z_score'], 1); ?></td>
                    <td><?php echo $anomalia['tipo_anomalia']; ?></td>
                    <td>BTN</td>
                    <td>
                        <?php if ($is_flagged): ?>
                            🏁
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="mt-3">
            <h5>Debug Info:</h5>
            <p>Header colonne: 11</p>
            <p>Righe totali: <?php echo count($anomalie); ?></p>
            <?php foreach ($anomalie as $index => $anomalia): ?>
                <p>Riga <?php echo $index + 1; ?>: flagged = <?php echo $anomalia['is_flagged'] > 0 ? 'SÌ' : 'NO'; ?></p>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>