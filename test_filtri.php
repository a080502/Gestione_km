<?php
// Test rapido per verificare i filtri di esportazione_dati.php
session_start();

// Simula una sessione valida
$_SESSION['username'] = 'denis';
$_SESSION['livello'] = 1; // Admin per vedere tutti i dati

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Filtri</title></head><body>";
echo "<h1>Test Filtri Esportazione Dati</h1>";

// Test con diversi filtri
$test_cases = [
    ['anno' => '2025', 'mese' => 'tutti', 'desc' => 'Anno 2025, tutti i mesi'],
    ['anno' => '2025', 'mese' => '09', 'desc' => 'Anno 2025, solo settembre'],
    ['anno' => 'tutti', 'mese' => '09', 'desc' => 'Tutti gli anni, solo settembre'],
    ['anno' => 'tutti', 'mese' => 'tutti', 'desc' => 'Tutti gli anni e mesi']
];

foreach ($test_cases as $test) {
    echo "<h3>Test: {$test['desc']}</h3>";
    $url = "esportazione_dati.php?anno_selezionato={$test['anno']}&mese_selezionato={$test['mese']}";
    echo "<a href='$url' target='_blank'>$url</a><br><br>";
}

echo "</body></html>";
?>