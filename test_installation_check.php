<?php
/**
 * Script di test per verificare il funzionamento del controllo installazione
 */

echo "<h2>Test controllo installazione</h2>";

// Include la funzione di controllo
include_once 'include/installation_check.php';

echo "<h3>Stato attuale:</h3>";

// Test della funzione
$needsInstall = needsInstallation();
$isCompleted = isInstallationCompleted();

echo "<p><strong>needsInstallation():</strong> " . ($needsInstall ? "TRUE (installazione necessaria)" : "FALSE (installazione completata)") . "</p>";
echo "<p><strong>isInstallationCompleted():</strong> " . ($isCompleted ? "TRUE (installazione completata)" : "FALSE (installazione non completata)") . "</p>";

echo "<h3>Verifica file di configurazione:</h3>";
$configFile = __DIR__ . '/editable_config.php';
echo "<p><strong>File editable_config.php esiste:</strong> " . (file_exists($configFile) ? "SÌ" : "NO") . "</p>";

if (file_exists($configFile)) {
    $appSettings = include $configFile;
    echo "<p><strong>Configurazione caricata correttamente:</strong> " . (is_array($appSettings) ? "SÌ" : "NO") . "</p>";
    
    if (is_array($appSettings)) {
        echo "<p><strong>Database configurato:</strong> " . (!empty($appSettings['DB_NAME']) ? "SÌ (" . $appSettings['DB_NAME'] . ")" : "NO") . "</p>";
        
        // Test connessione database
        try {
            $dbHost = $appSettings['DB_HOST'] ?? 'localhost';
            $dbUsername = $appSettings['DB_USERNAME'] ?? 'root';
            $dbPassword = $appSettings['DB_PASSWORD'] ?? '';
            $dbName = $appSettings['DB_NAME'] ?? '';
            
            if (!empty($dbName)) {
                $conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
                
                if ($conn->connect_error) {
                    echo "<p><strong>Connessione database:</strong> FALLITA (" . $conn->connect_error . ")</p>";
                } else {
                    echo "<p><strong>Connessione database:</strong> RIUSCITA</p>";
                    
                    // Controlla tabelle
                    $tablesToCheck = ['utenti', 'filiali'];
                    foreach ($tablesToCheck as $table) {
                        $result = $conn->query("SHOW TABLES LIKE '$table'");
                        $exists = ($result && $result->num_rows > 0);
                        echo "<p><strong>Tabella '$table' esiste:</strong> " . ($exists ? "SÌ" : "NO") . "</p>";
                    }
                    
                    // Controlla utenti
                    $result = $conn->query("SELECT COUNT(*) as count FROM utenti");
                    if ($result) {
                        $row = $result->fetch_assoc();
                        $userCount = (int)$row['count'];
                        echo "<p><strong>Numero utenti nel sistema:</strong> $userCount</p>";
                    }
                    
                    $conn->close();
                }
            } else {
                echo "<p><strong>Connessione database:</strong> Nome database non configurato</p>";
            }
        } catch (Exception $e) {
            echo "<p><strong>Errore test database:</strong> " . $e->getMessage() . "</p>";
        }
    }
}

echo "<hr>";
echo "<p><a href='login.php'>Torna alla pagina di login</a> per vedere il risultato</p>";
?>