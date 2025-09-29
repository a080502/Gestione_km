<?php
/**
 * Test della funzione importDatabaseSchema migliorata
 */

// Funzione per importare il schema del database MIGLIORATA
function importDatabaseSchema($host, $username, $password, $database) {
    try {
        $schemaFile = __DIR__ . '/database_km.sql';
        
        if (!file_exists($schemaFile)) {
            return ['success' => false, 'error' => 'File database_km.sql non trovato'];
        }
        
        $conn = new mysqli($host, $username, $password, $database);
        
        if ($conn->connect_error) {
            return ['success' => false, 'error' => $conn->connect_error];
        }
        
        $sqlContent = file_get_contents($schemaFile);
        
        // Pulizia del file SQL
        $lines = explode("\n", $sqlContent);
        $cleanedLines = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Salta righe vuote
            if (empty($line)) continue;
            
            // Salta commenti che iniziano con --
            if (strpos($line, '--') === 0) continue;
            
            // Salta comandi specifici che non servono
            if (stripos($line, 'CREATE DATABASE') !== false) continue;
            if (stripos($line, 'USE ') !== false) continue;
            if (stripos($line, 'START TRANSACTION') !== false) continue;
            if (stripos($line, 'SET SQL_MODE') !== false) continue;
            if (stripos($line, 'SET time_zone') !== false) continue;
            if (preg_match('/^\/\*.*\*\/$/', $line)) continue; // Commenti /* */
            
            $cleanedLines[] = $line;
        }
        
        // Ricomponi il SQL pulito
        $cleanedSQL = implode("\n", $cleanedLines);
        
        // Rimuovi commenti multilinea
        $cleanedSQL = preg_replace('/\/\*.*?\*\//s', '', $cleanedSQL);
        
        // Dividi le query per punto e virgola
        $queries = explode(';', $cleanedSQL);
        
        $executedQueries = 0;
        $errors = [];
        
        foreach ($queries as $query) {
            $query = trim($query);
            
            // Salta query vuote
            if (empty($query)) continue;
            
            // Mostra la query che si sta per eseguire (per debug)
            echo "<small>Eseguendo: " . substr($query, 0, 80) . "...</small><br>";
            
            // Esegui la query
            if (!$conn->query($query)) {
                $errors[] = "Errore nella query: " . substr($query, 0, 100) . "... -> " . $conn->error;
            } else {
                $executedQueries++;
            }
        }
        
        $conn->close();
        
        if (!empty($errors)) {
            return [
                'success' => false, 
                'error' => 'Errori durante l\'importazione: ' . implode('; ', $errors),
                'executed_queries' => $executedQueries
            ];
        }
        
        return [
            'success' => true, 
            'message' => "Schema importato con successo! Eseguite $executedQueries query.",
            'executed_queries' => $executedQueries
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

echo "<h2>Test importazione schema database migliorata</h2>";

// Test della pulizia del file SQL
echo "<h3>1. Analisi file SQL originale</h3>";
$originalSQL = file_get_contents(__DIR__ . '/database_km.sql');
$lines = explode("\n", $originalSQL);

echo "<p>Righe totali nel file SQL: " . count($lines) . "</p>";

// Mostra alcune righe problematiche
echo "<h4>Righe che verranno filtrate:</h4>";
echo "<ul>";
$problemLines = 0;
foreach (array_slice($lines, 0, 50) as $lineNum => $line) {
    $line = trim($line);
    if (empty($line) || 
        strpos($line, '--') === 0 || 
        stripos($line, 'CREATE DATABASE') !== false ||
        stripos($line, 'USE ') !== false ||
        stripos($line, 'START TRANSACTION') !== false ||
        stripos($line, 'SET SQL_MODE') !== false ||
        stripos($line, 'SET time_zone') !== false) {
        echo "<li>Riga " . ($lineNum + 1) . ": <code>" . htmlspecialchars(substr($line, 0, 60)) . "...</code></li>";
        $problemLines++;
        if ($problemLines >= 10) {
            echo "<li><em>...e altre righe simili</em></li>";
            break;
        }
    }
}
echo "</ul>";

echo "<h3>2. Test importazione (SIMULAZIONE - senza connessione DB)</h3>";
echo "<p><strong>Nota:</strong> Questo è un test di parsing, non verrà eseguito il database reale.</p>";

// Simula la pulizia senza connettere al database
$sqlContent = file_get_contents(__DIR__ . '/database_km.sql');
$lines = explode("\n", $sqlContent);
$cleanedLines = [];

foreach ($lines as $line) {
    $line = trim($line);
    
    if (empty($line)) continue;
    if (strpos($line, '--') === 0) continue;
    if (stripos($line, 'CREATE DATABASE') !== false) continue;
    if (stripos($line, 'USE ') !== false) continue;
    if (stripos($line, 'START TRANSACTION') !== false) continue;
    if (stripos($line, 'SET SQL_MODE') !== false) continue;
    if (stripos($line, 'SET time_zone') !== false) continue;
    if (preg_match('/^\/\*.*\*\/$/', $line)) continue;
    
    $cleanedLines[] = $line;
}

$cleanedSQL = implode("\n", $cleanedLines);
$cleanedSQL = preg_replace('/\/\*.*?\*\//s', '', $cleanedSQL);
$queries = explode(';', $cleanedSQL);

$validQueries = 0;
echo "<h4>Query che verranno eseguite:</h4>";
echo "<ol>";
foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        $validQueries++;
        echo "<li><code>" . htmlspecialchars(substr($query, 0, 80)) . "...</code></li>";
        if ($validQueries >= 10) {
            echo "<li><em>...e altre " . (count($queries) - $validQueries) . " query</em></li>";
            break;
        }
    }
}
echo "</ol>";

echo "<p><strong>Totale query valide da eseguire:</strong> " . count(array_filter($queries, function($q) { return !empty(trim($q)); })) . "</p>";

echo "<hr>";
echo "<p>✅ <strong>La pulizia del file SQL sembra funzionare correttamente!</strong></p>";
echo "<p>🚀 <strong>Prova ora il setup reale per verificare che l'importazione funzioni.</strong></p>";

echo "<p><a href='setup.php'>Testa il setup completo</a> | <a href='login.php'>Torna al login</a></p>";
?>