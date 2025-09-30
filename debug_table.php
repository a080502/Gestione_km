<?php
// Test per debug della tabella
session_start();
require_once 'config.php';

// Verifica autenticazione
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Query semplificata per debug
$query = "SELECT id, data, username, targa_mezzo, km_percorsi, litri, euro_spesi, 
                 (km_percorsi / litri) as km_per_litro, 'CONSUMO_TROPPO_BASSO' as tipo_anomalia,
                 3.5 as z_score, 0 as is_flagged
          FROM chilometri c 
          JOIN utenti u ON c.utente_id = u.id
          WHERE c.data >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
          LIMIT 3";

$result = mysqli_query($connection, $query);
$dati = [];
while ($row = mysqli_fetch_assoc($result)) {
    $dati[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Tabella</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .debug-table {
            table-layout: fixed !important;
            width: 100% !important;
            border: 2px solid red !important;
        }
        
        .debug-table th, .debug-table td {
            border: 1px solid blue !important;
            text-align: center !important;
            vertical-align: middle !important;
        }
        
        .debug-table th:nth-child(1), .debug-table td:nth-child(1) { 
            width: 60px !important; 
            background-color: rgba(255,0,0,0.1) !important;
        }
        .debug-table th:nth-child(2), .debug-table td:nth-child(2) { 
            width: 100px !important; 
            background-color: rgba(0,255,0,0.1) !important;
        }
        .debug-table th:nth-child(3), .debug-table td:nth-child(3) { 
            width: 120px !important; 
            background-color: rgba(0,0,255,0.1) !important;
        }
        .debug-table th:nth-child(4), .debug-table td:nth-child(4) { 
            width: 100px !important; 
            background-color: rgba(255,255,0,0.1) !important;
        }
        .debug-table th:nth-child(5), .debug-table td:nth-child(5) { 
            width: 80px !important; 
            background-color: rgba(255,0,255,0.1) !important;
        }
        .debug-table th:nth-child(6), .debug-table td:nth-child(6) { 
            width: 80px !important; 
            background-color: rgba(0,255,255,0.1) !important;
        }
        .debug-table th:nth-child(7), .debug-table td:nth-child(7) { 
            width: 90px !important; 
            background-color: rgba(128,128,128,0.1) !important;
        }
        .debug-table th:nth-child(8), .debug-table td:nth-child(8) { 
            width: 80px !important; 
            background-color: rgba(255,128,0,0.1) !important;
        }
        .debug-table th:nth-child(9), .debug-table td:nth-child(9) { 
            width: 80px !important; 
            background-color: rgba(128,255,0,0.1) !important;
        }
        .debug-table th:nth-child(10), .debug-table td:nth-child(10) { 
            width: 140px !important; 
            background-color: rgba(0,128,255,0.1) !important;
        }
        .debug-table th:nth-child(11), .debug-table td:nth-child(11) { 
            width: 100px !important; 
            background-color: rgba(255,0,128,0.1) !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h2>Debug Tabella Anomalie</h2>
        
        <div class="table-responsive">
            <table class="table debug-table">
                <thead class="table-danger">
                    <tr>
                        <th>FLAG</th>
                        <th>DATA</th>
                        <th>UTENTE</th>
                        <th>TARGA</th>
                        <th>KM</th>
                        <th>LITRI</th>
                        <th>€</th>
                        <th>KM/L</th>
                        <th>Z-SCORE</th>
                        <th>TIPO ANOMALIA</th>
                        <th>AZIONI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dati as $anomalia): ?>
                    <tr>
                        <td>🏁</td>
                        <td><?php echo date('d/m/Y', strtotime($anomalia['data'])); ?></td>
                        <td><?php echo htmlspecialchars($anomalia['username']); ?></td>
                        <td><?php echo htmlspecialchars($anomalia['targa_mezzo']); ?></td>
                        <td><?php echo number_format($anomalia['km_percorsi']); ?></td>
                        <td><?php echo number_format($anomalia['litri'], 2); ?></td>
                        <td><?php echo number_format($anomalia['euro_spesi'], 2); ?>€</td>
                        <td><?php echo number_format($anomalia['km_per_litro'], 2); ?></td>
                        <td><?php echo number_format($anomalia['z_score'], 2); ?></td>
                        <td><?php echo htmlspecialchars($anomalia['tipo_anomalia']); ?></td>
                        <td>BTN</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <h3>Conteggio celle per riga</h3>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const rows = document.querySelectorAll('tbody tr');
                rows.forEach((row, index) => {
                    const cells = row.querySelectorAll('td');
                    console.log(`Riga ${index + 1}: ${cells.length} celle`);
                });
                
                const headerCells = document.querySelectorAll('thead th');
                console.log(`Header: ${headerCells.length} celle`);
            });
        </script>
    </div>
</body>
</html>