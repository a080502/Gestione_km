<?php
/**
 * Utility per verificare lo stato dell'installazione
 * Gestione Chilometri - Sistema di verifica installazione
 */

/**
 * Verifica se l'installazione iniziale è stata completata
 * 
 * @return bool True se l'installazione è completata, False altrimenti
 */
function isInstallationCompleted() {
    // Prima verifica: esiste il file di configurazione?
    $configFile = __DIR__ . '/editable_config.php';
    
    if (!file_exists($configFile)) {
        return false;
    }
    
    // Seconda verifica: il database è configurato e contiene tabelle?
    try {
        // Carica la configurazione
        $appSettings = include $configFile;
        
        if (!is_array($appSettings)) {
            return false;
        }
        
        // Estrai le credenziali del database
        $dbHost = $appSettings['DB_HOST'] ?? 'localhost';
        $dbUsername = $appSettings['DB_USERNAME'] ?? 'root';
        $dbPassword = $appSettings['DB_PASSWORD'] ?? '';
        $dbName = $appSettings['DB_NAME'] ?? '';
        
        if (empty($dbName)) {
            return false;
        }
        
        // Tenta la connessione al database
        $conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
        
        if ($conn->connect_error) {
            return false;
        }
        
        // Verifica se esistono le tabelle principali del sistema
        $tablesToCheck = ['utenti', 'filiali']; // Tabelle essenziali
        
        foreach ($tablesToCheck as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if (!$result || $result->num_rows === 0) {
                $conn->close();
                return false;
            }
        }
        
        // Verifica se esiste almeno un utente (installazione completata)
        $result = $conn->query("SELECT COUNT(*) as count FROM utenti");
        if (!$result) {
            $conn->close();
            return false;
        }
        
        $row = $result->fetch_assoc();
        $userCount = (int)$row['count'];
        
        $conn->close();
        
        // Se ci sono utenti, l'installazione è completata
        return $userCount > 0;
        
    } catch (Exception $e) {
        // In caso di errore, considera l'installazione non completata
        error_log("Errore durante la verifica dell'installazione: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica se il sistema ha bisogno di installazione
 * 
 * @return bool True se serve l'installazione, False altrimenti
 */
function needsInstallation() {
    return !isInstallationCompleted();
}
?>