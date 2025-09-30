<?php
// Test per la funzionalità export_anomalie.php
session_start();

// Simula una sessione admin valida
$_SESSION['username'] = 'denis';
$_SESSION['dati_utente'] = [
    'Nome' => 'Denis',
    'Cognome' => 'Test',
    'livello' => 1
];

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Export Anomalie</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='container mt-4'>";
echo "<h1>Test Export Anomalie</h1>";

echo "<div class='alert alert-info'>";
echo "<h4>Test Cases per export_anomalie.php:</h4>";
echo "<p>Questa pagina testa diversi scenari per l'esportazione delle anomalie:</p>";
echo "</div>";

$test_cases = [
    ['url' => 'export_anomalie.php', 'desc' => 'Esportazione automatica anomalie (se presenti)'],
    ['url' => 'export_anomalie.php?formato=csv', 'desc' => 'Esportazione CSV'],
    ['url' => 'export_anomalie.php?formato=excel', 'desc' => 'Esportazione Excel'],
    ['url' => 'export_anomalie.php?ids=1,2,3', 'desc' => 'Esportazione ID specifici (test errore se non esistenti)']
];

echo "<div class='row'>";
foreach ($test_cases as $i => $test) {
    echo "<div class='col-md-6 mb-3'>";
    echo "<div class='card'>";
    echo "<div class='card-body'>";
    echo "<h5 class='card-title'>Test " . ($i + 1) . "</h5>";
    echo "<p class='card-text'>{$test['desc']}</p>";
    echo "<a href='{$test['url']}' target='_blank' class='btn btn-primary'>Esegui Test</a>";
    echo "</div></div></div>";
}
echo "</div>";

echo "<div class='mt-4'>";
echo "<a href='dashboard_bi.php' class='btn btn-secondary'>Torna alla Dashboard</a>";
echo "</div>";

echo "</body></html>";
?>