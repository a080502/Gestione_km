<?php
// Debug per report_avanzato.php
session_start();

// Simula una sessione admin valida
$_SESSION['username'] = 'denis';
$_SESSION['dati_utente'] = [
    'Nome' => 'Denis',
    'Cognome' => 'Test',
    'livello' => 1
];

include_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Debug Report Avanzato</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='container mt-4'>";

echo "<h1>Debug Report Avanzato</h1>";

// Test connessione database
echo "<div class='card mb-3'>";
echo "<div class='card-header bg-primary text-white'>";
echo "<h5>Test Connessione Database</h5>";
echo "</div>";
echo "<div class='card-body'>";

if (isset($conn) && $conn instanceof mysqli) {
    echo "<div class='alert alert-success'>✅ Connessione database OK</div>";

    // Test query semplice
    $result = $conn->query("SELECT COUNT(*) as total FROM chilometri");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p><strong>Totale record chilometri:</strong> " . $row['total'] . "</p>";
    }
} else {
    echo "<div class='alert alert-danger'>❌ Connessione database fallita</div>";
}

echo "</div></div>";

// Test funzioni report
echo "<div class='card mb-3'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h5>Test Funzioni Report</h5>";
echo "</div>";
echo "<div class='card-body'>";

// Includi le funzioni
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
            LEFT JOIN anomalie_flaggate af ON s.id = af.id_rifornimento
            WHERE ABS(s.km_per_litro - m.media_consumo) / NULLIF(m.dev_std_consumo, 0) > ?
            OR (s.km_percorsi = 0 AND s.litri > 0)
            OR (s.km_percorsi > 1000 AND s.litri < 10)
            OR (s.euro_spesi / NULLIF(s.litri, 0) > 3.0)
            OR (s.euro_spesi / NULLIF(s.litri, 0) < 1.0)
            ORDER BY z_score DESC, data DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ddd", $soglia_deviazione, $soglia_deviazione, $soglia_deviazione);
        $stmt->execute();
        return $stmt->get_result();
    }
    return false;
}

if (isset($conn)) {
    $anomalie = identificaAnomalieConsumo($conn);
    if ($anomalie) {
        $count_anomalie = $anomalie->num_rows;
        echo "<div class='alert alert-success'>✅ Query anomalie OK - Trovate $count_anomalie anomalie</div>";

        if ($count_anomalie > 0) {
            echo "<h6>Prime 3 anomalie:</h6>";
            echo "<table class='table table-sm'>";
            echo "<tr><th>ID</th><th>Data</th><th>Utente</th><th>Targa</th><th>Tipo</th><th>Flaggata</th></tr>";
            $i = 0;
            while (($row = $anomalie->fetch_assoc()) && $i < 3) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . date('d/m/Y', strtotime($row['data'])) . "</td>";
                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                echo "<td>" . htmlspecialchars($row['targa_mezzo']) . "</td>";
                echo "<td>" . htmlspecialchars($row['tipo_anomalia']) . "</td>";
                echo "<td>" . ($row['is_flagged'] ? 'Sì' : 'No') . "</td>";
                echo "</tr>";
                $i++;
            }
            echo "</table>";
        }
    } else {
        echo "<div class='alert alert-warning'>⚠️ Query anomalie fallita</div>";
    }
} else {
    echo "<div class='alert alert-danger'>❌ Connessione non disponibile</div>";
}

echo "</div></div>";

echo "<div class='mt-4'>";
echo "<a href='report_avanzato.php' class='btn btn-primary'>Apri Report Avanzato</a>";
echo "</div>";

echo "</body></html>";
