<?php
/**
 * Test delle funzioni di setup database migliorate
 */

// Include le funzioni di setup
function checkPrerequisites() {
    $checks = [];
    
    // PHP Version
    $phpVersion = PHP_VERSION;
    $checks['php_version'] = [
        'name' => 'Versione PHP',
        'required' => '8.0+',
        'current' => $phpVersion,
        'status' => version_compare($phpVersion, '8.0', '>='),
        'message' => version_compare($phpVersion, '8.0', '>=') ? 'OK' : 'Aggiorna PHP'
    ];
    
    return $checks;
}

// Funzione per testare la connessione al database MIGLIORATA
function testDatabaseConnection($host, $username, $password, $database = null) {
    try {
        // Prima testa la connessione al server MySQL (senza database specifico)
        $conn = new mysqli($host, $username, $password);
        
        if ($conn->connect_error) {
            return [
                'success' => false, 
                'error' => $conn->connect_error,
                'server_connection' => false
            ];
        }
        
        $version = $conn->server_info;
        
        // Se non è specificato un database, testa solo la connessione al server
        if (empty($database)) {
            $conn->close();
            return [
                'success' => true, 
                'version' => $version,
                'server_connection' => true,
                'database_exists' => null,
                'message' => 'Connessione al server MySQL riuscita'
            ];
        }
        
        // Verifica se il database esiste
        $result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$database'");
        $databaseExists = ($result && $result->num_rows > 0);
        
        $conn->close();
        
        // Ora testa la connessione specifica al database se esiste
        if ($databaseExists) {
            $connDb = new mysqli($host, $username, $password, $database);
            if ($connDb->connect_error) {
                return [
                    'success' => false,
                    'error' => $connDb->connect_error,
                    'server_connection' => true,
                    'database_exists' => true
                ];
            }
            $connDb->close();
        }
        
        return [
            'success' => true,
            'version' => $version,
            'server_connection' => true,
            'database_exists' => $databaseExists,
            'database_name' => $database,
            'message' => $databaseExists 
                ? "Connessione riuscita! Database '$database' trovato"
                : "Connessione al server riuscita! Database '$database' non esiste (verrà creato durante l'installazione)"
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false, 
            'error' => $e->getMessage(),
            'server_connection' => false
        ];
    }
}

echo "<h2>Test delle funzioni di setup migliorate</h2>";

echo "<h3>Test 1: Connessione solo al server (senza specificare database)</h3>";
$test1 = testDatabaseConnection('localhost', 'root', '');
echo "<pre>" . print_r($test1, true) . "</pre>";

echo "<h3>Test 2: Connessione con database esistente</h3>";
$test2 = testDatabaseConnection('localhost', 'root', '', 'mysql'); // mysql è sempre presente
echo "<pre>" . print_r($test2, true) . "</pre>";

echo "<h3>Test 3: Connessione con database NON esistente</h3>";
$test3 = testDatabaseConnection('localhost', 'root', '', 'database_che_non_esiste_123');
echo "<pre>" . print_r($test3, true) . "</pre>";

echo "<h3>Test 4: Connessione con credenziali sbagliate</h3>";
$test4 = testDatabaseConnection('localhost', 'utente_sbagliato', 'password_sbagliata', 'test');
echo "<pre>" . print_r($test4, true) . "</pre>";

echo "<hr>";
echo "<p><strong>Risultati:</strong></p>";
echo "<ul>";
echo "<li>Test 1 (Solo server): " . ($test1['success'] ? '✅ SUCCESSO' : '❌ FALLITO') . "</li>";
echo "<li>Test 2 (DB esistente): " . ($test2['success'] ? '✅ SUCCESSO' : '❌ FALLITO') . "</li>";
echo "<li>Test 3 (DB non esistente): " . ($test3['success'] && !$test3['database_exists'] ? '✅ SUCCESSO (DB non esiste ma server OK)' : '❌ FALLITO') . "</li>";
echo "<li>Test 4 (Credenziali sbagliate): " . (!$test4['success'] ? '✅ SUCCESSO (errore atteso)' : '❌ FALLITO (doveva fallire)') . "</li>";
echo "</ul>";

echo "<p><a href='setup.php'>Prova il setup migliorato</a> | <a href='login.php'>Torna al login</a></p>";
?>