<?php
/**
 * Test super dettagliato della pulizia SQL migliorata
 */

echo "<h2>Test dettagliato pulizia SQL - Versione ROBUSTA</h2>";

$sqlContent = file_get_contents(__DIR__ . '/database_km.sql');

echo "<h3>1. Analisi file SQL originale</h3>";
echo "<p><strong>Dimensione file:</strong> " . strlen($sqlContent) . " bytes</p>";

// Mostra le prime righe problematiche
$originalLines = explode("\n", $sqlContent);
echo "<p><strong>Prime 20 righe del file originale:</strong></p>";
echo "<pre style='background:#f5f5f5; padding:10px; font-size:12px;'>";
foreach (array_slice($originalLines, 0, 20) as $i => $line) {
    echo sprintf("%02d: %s\n", $i+1, htmlspecialchars($line));
}
echo "</pre>";

echo "<h3>2. Applicazione pulizia ROBUSTA</h3>";

// PULIZIA ROBUSTA (copia della funzione migliorata)

// 1. Rimuovi tutti i commenti MySQL specifici /*!...*/
$step1 = preg_replace('/\/\*!\d+.*?\*\/;?/s', '', $sqlContent);
echo "<p><strong>Step 1:</strong> Rimossi " . (strlen($sqlContent) - strlen($step1)) . " bytes di commenti MySQL specifici</p>";

// 2. Rimuovi commenti multilinea standard /*...*/
$step2 = preg_replace('/\/\*.*?\*\//s', '', $step1);
echo "<p><strong>Step 2:</strong> Rimossi " . (strlen($step1) - strlen($step2)) . " bytes di commenti multilinea</p>";

// 3. Processa linea per linea
$lines = explode("\n", $step2);
$cleanedLines = [];
$skippedLines = [];

foreach ($lines as $lineNum => $line) {
    $originalLine = $line;
    $line = trim($line);
    
    $skip = false;
    $reason = '';
    
    if (empty($line)) { $skip = true; $reason = 'riga vuota'; }
    else if (strpos($line, '--') === 0) { $skip = true; $reason = 'commento --'; }
    else if (stripos($line, 'CREATE DATABASE') !== false) { $skip = true; $reason = 'CREATE DATABASE'; }
    else if (stripos($line, 'USE `') !== false) { $skip = true; $reason = 'USE database'; }
    else if (stripos($line, 'USE ') === 0) { $skip = true; $reason = 'USE database'; }
    else if (stripos($line, 'START TRANSACTION') !== false) { $skip = true; $reason = 'START TRANSACTION'; }
    else if (stripos($line, 'SET SQL_MODE') !== false) { $skip = true; $reason = 'SET SQL_MODE'; }
    else if (stripos($line, 'SET time_zone') !== false) { $skip = true; $reason = 'SET time_zone'; }
    else if (stripos($line, 'SET @OLD_') !== false) { $skip = true; $reason = 'SET @OLD_'; }
    else if (stripos($line, 'SET NAMES') !== false) { $skip = true; $reason = 'SET NAMES'; }
    else if ($line === ';') { $skip = true; $reason = 'solo punto e virgola'; }
    
    if ($skip) {
        $skippedLines[] = "Riga " . ($lineNum+1) . " ($reason): " . substr($originalLine, 0, 60);
    } else {
        $cleanedLines[] = $line;
    }
}

echo "<p><strong>Step 3:</strong> Processate " . count($lines) . " righe, mantenute " . count($cleanedLines) . ", saltate " . count($skippedLines) . "</p>";

if (count($skippedLines) > 0) {
    echo "<details><summary>Mostra righe saltate (" . count($skippedLines) . ")</summary>";
    echo "<pre style='background:#fff3cd; padding:10px; font-size:11px;'>";
    foreach (array_slice($skippedLines, 0, 15) as $skipped) {
        echo htmlspecialchars($skipped) . "\n";
    }
    if (count($skippedLines) > 15) {
        echo "... e altre " . (count($skippedLines) - 15) . " righe\n";
    }
    echo "</pre></details>";
}

// 4. Ricomponi e pulizia finale
$step3 = implode("\n", $cleanedLines);

// 5. Rimuovi punti e virgola multipli consecutivi
$step4 = preg_replace('/;+/', ';', $step3);
echo "<p><strong>Step 4:</strong> Normalizzati punti e virgola multipli</p>";

// 6. Rimuovi spazi e newline in eccesso
$step5 = preg_replace('/\s+/', ' ', $step4);
$step6 = str_replace('; ', ";\n", $step5);
echo "<p><strong>Step 5-6:</strong> Normalizzati spazi e newline</p>";

// 7. Dividi le query
$queries = explode(';', $step6);
$validQueries = array_filter($queries, function($q) { 
    $q = trim($q);
    return !empty($q) && strlen($q) >= 5;
});

echo "<p><strong>Step 7:</strong> Trovate " . count($validQueries) . " query valide da un totale di " . count($queries) . " parti</p>";

echo "<h3>3. Prime query risultanti</h3>";
echo "<ol>";
foreach (array_slice($validQueries, 0, 5) as $i => $query) {
    $query = trim($query);
    echo "<li><code>" . htmlspecialchars(substr($query, 0, 100)) . (strlen($query) > 100 ? '...' : '') . "</code></li>";
}
echo "</ol>";

echo "<h3>4. Controllo finale sintassi</h3>";
$syntaxIssues = [];
foreach (array_slice($validQueries, 0, 10) as $i => $query) {
    $query = trim($query);
    // Controlla pattern problematici
    if (strpos($query, ';;') !== false) {
        $syntaxIssues[] = "Query " . ($i+1) . ": contiene ;;";
    }
    if (preg_match('/CREATE\s+DATABASE/i', $query)) {
        $syntaxIssues[] = "Query " . ($i+1) . ": contiene ancora CREATE DATABASE";
    }
    if (preg_match('/USE\s+`/i', $query)) {
        $syntaxIssues[] = "Query " . ($i+1) . ": contiene ancora USE";
    }
}

if (empty($syntaxIssues)) {
    echo "<p>✅ <strong>Nessun problema di sintassi rilevato nelle prime 10 query!</strong></p>";
} else {
    echo "<p>❌ <strong>Problemi rilevati:</strong></p>";
    echo "<ul>";
    foreach ($syntaxIssues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<h3>Risultato</h3>";
echo "<p>✅ File SQL processato: <strong>" . count($validQueries) . " query pronte per l'esecuzione</strong></p>";
echo "<p>🧹 Pulizia completata: rimossi commenti, comandi problematici e normalizzata sintassi</p>";
echo "<p>🚀 <a href='setup.php'>Prova ora il setup migliorato!</a></p>";
?>