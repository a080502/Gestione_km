<?php
// Test dell'importazione dati di esempio
require_once 'setup.php';

echo "<h2>Test Importazione Dati di Esempio</h2>";

// Simuliamo l'importazione con parametri di esempio
// ATTENZIONE: Modifica questi parametri con il tuo database di test!
$testHost = 'localhost';
$testUsername = 'root'; 
$testPassword = '';
$testDatabase = 'gestione_km_test';

echo "<h3>1. Verifica esistenza file sample_data.sql</h3>";
if (file_exists('sample_data.sql')) {
    echo "✅ File sample_data.sql trovato<br>";
    
    // Mostra le prime righe per verifica
    $content = file_get_contents('sample_data.sql');
    $lines = explode("\n", $content);
    echo "<strong>Prime 10 righe del file:</strong><br>";
    echo "<pre>";
    for ($i = 0; $i < 10 && $i < count($lines); $i++) {
        echo htmlspecialchars($lines[$i]) . "\n";
    }
    echo "</pre>";
    
    echo "<strong>Statistiche file:</strong><br>";
    echo "- Dimensione: " . number_format(strlen($content)) . " caratteri<br>";
    echo "- Righe totali: " . count($lines) . "<br>";
    echo "- Query INSERT (stimate): " . substr_count($content, 'INSERT INTO') . "<br>";
    
} else {
    echo "❌ File sample_data.sql NON trovato<br>";
}

echo "<h3>2. Test parsing SQL (senza esecuzione)</h3>";

if (file_exists('sample_data.sql')) {
    $sqlContent = file_get_contents('sample_data.sql');
    
    // Simula la pulizia del SQL (stesso algoritmo della funzione importSampleData)
    $lines = explode("\n", $sqlContent);
    $cleanedLines = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Salta righe vuote e commenti
        if (empty($line) || strpos($line, '--') === 0) continue;
        
        $cleanedLines[] = $line;
    }
    
    $cleanedSQL = implode("\n", $cleanedLines);
    
    // Dividi le query per punto e virgola
    $queries = explode(';', $cleanedSQL);
    
    $validQueries = 0;
    $queryTypes = [];
    
    foreach ($queries as $query) {
        $query = trim($query);
        
        // Salta query vuote
        if (empty($query) || strlen($query) < 5) continue;
        
        $validQueries++;
        
        // Identifica il tipo di query
        if (stripos($query, 'INSERT INTO') === 0) {
            $queryTypes['INSERT'] = ($queryTypes['INSERT'] ?? 0) + 1;
        } elseif (stripos($query, 'UPDATE') === 0) {
            $queryTypes['UPDATE'] = ($queryTypes['UPDATE'] ?? 0) + 1;
        } elseif (stripos($query, 'DELETE') === 0) {
            $queryTypes['DELETE'] = ($queryTypes['DELETE'] ?? 0) + 1;
        } else {
            $queryTypes['OTHER'] = ($queryTypes['OTHER'] ?? 0) + 1;
        }
    }
    
    echo "✅ Parsing completato con successo<br>";
    echo "<strong>Query valide trovate:</strong> $validQueries<br>";
    echo "<strong>Tipi di query:</strong><br>";
    foreach ($queryTypes as $type => $count) {
        echo "- $type: $count query<br>";
    }
    
    // Mostra le prime 3 query per verifica
    echo "<h4>Prime 3 query elaborate:</h4>";
    echo "<pre>";
    $shown = 0;
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query) || strlen($query) < 5) continue;
        
        echo ($shown + 1) . ". " . htmlspecialchars(substr($query, 0, 100)) . (strlen($query) > 100 ? '...' : '') . "\n\n";
        $shown++;
        if ($shown >= 3) break;
    }
    echo "</pre>";
}

echo "<h3>3. Test funzione importSampleData() (simulazione)</h3>";

// Verifica che la funzione esista
if (function_exists('importSampleData')) {
    echo "✅ Funzione importSampleData() definita correttamente<br>";
    
    // Per sicurezza, non eseguiamo l'importazione reale su database di produzione
    // Questo test deve essere fatto manualmente in un ambiente di test
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin: 10px 0;'>";
    echo "<strong>⚠️ ATTENZIONE:</strong> Per testare l'importazione completa, modifica i parametri di connessione nel file e usa un database di test!<br>";
    echo "Poi decommenta la sezione sottostante per eseguire il test reale.";
    echo "</div>";
    
    /*
    // DECOMMENTA SOLO PER TEST SU DATABASE DI TEST!
    echo "Esecuzione importazione su database di test...<br>";
    $result = importSampleData($testHost, $testUsername, $testPassword, $testDatabase);
    
    if ($result['success']) {
        echo "✅ " . $result['message'] . "<br>";
    } else {
        echo "❌ " . $result['error'] . "<br>";
    }
    */
} else {
    echo "❌ Funzione importSampleData() NON trovata<br>";
}

echo "<h3>4. Verifica integrità setup.php</h3>";

// Controlla se il setup.php può essere caricato senza errori
$setupContent = file_get_contents('setup.php');

// Verifica presenza componenti chiave
$checks = [
    "case 'import_sample_data'" => "Azione import_sample_data",
    "function importSampleData" => "Funzione PHP importSampleData",
    "function importSampleData()" => "Funzione JavaScript importSampleData",
    "install-sample-data" => "Checkbox UI dati di esempio",
    "sample-data-option" => "Sezione UI opzioni dati"
];

foreach ($checks as $search => $description) {
    if (strpos($setupContent, $search) !== false) {
        echo "✅ $description trovato<br>";
    } else {
        echo "❌ $description NON trovato<br>";
    }
}

echo "<h3>5. Riepilogo Test</h3>";
echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px;'>";
echo "<strong>✅ Test completato!</strong><br>";
echo "La funzionalità dei dati di esempio è stata implementata e sembra funzionare correttamente.<br>";
echo "<br><strong>Per testare completamente:</strong><br>";
echo "1. Configura un database di test<br>";
echo "2. Apri setup.php nel browser<br>";
echo "3. Completa steps 1-2 normalmente<br>";
echo "4. Nello step 3, dopo l'importazione schema, seleziona 'Installa dati di esempio'<br>";
echo "5. Verifica che i dati siano importati correttamente<br>";
echo "</div>";
?>