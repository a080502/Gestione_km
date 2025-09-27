#!/usr/bin/env php
<?php
/**
 * Script CLI per backup automatico
 * Uso: php backup_cli.php [database|files|complete] [--quiet]
 * Esempio: php backup_cli.php complete --quiet
 */

// Verifica che lo script sia eseguito da CLI
if (php_sapi_name() !== 'cli') {
    die('Questo script può essere eseguito solo da linea di comando.' . PHP_EOL);
}

// Includi le funzioni di backup
require_once __DIR__ . '/backup_system.php';

// Funzioni helper per CLI
function logMessage($message, $quiet = false) {
    if (!$quiet) {
        echo "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
    }
}

function showUsage() {
    echo "Sistema Backup CLI - Gestione KM" . PHP_EOL;
    echo "=================================" . PHP_EOL;
    echo PHP_EOL;
    echo "Uso: php backup_cli.php [TIPO] [OPZIONI]" . PHP_EOL;
    echo PHP_EOL;
    echo "TIPI BACKUP:" . PHP_EOL;
    echo "  database    Backup solo database (SQL)" . PHP_EOL;
    echo "  files       Backup solo files (ZIP)" . PHP_EOL;
    echo "  complete    Backup completo (database + files)" . PHP_EOL;
    echo PHP_EOL;
    echo "OPZIONI:" . PHP_EOL;
    echo "  --quiet     Modalità silenziosa (senza output)" . PHP_EOL;
    echo "  --help      Mostra questo messaggio" . PHP_EOL;
    echo PHP_EOL;
    echo "ESEMPI:" . PHP_EOL;
    echo "  php backup_cli.php complete" . PHP_EOL;
    echo "  php backup_cli.php database --quiet" . PHP_EOL;
    echo "  php backup_cli.php files" . PHP_EOL;
    echo PHP_EOL;
    echo "CRON AUTOMATICO:" . PHP_EOL;
    echo "  # Backup completo ogni giorno alle 2:00" . PHP_EOL;
    echo "  0 2 * * * cd /path/to/project && php backup_cli.php complete --quiet" . PHP_EOL;
    echo PHP_EOL;
    echo "  # Backup database ogni 6 ore" . PHP_EOL;
    echo "  0 */6 * * * cd /path/to/project && php backup_cli.php database --quiet" . PHP_EOL;
    echo PHP_EOL;
}

// Parsing argomenti
$args = array_slice($argv, 1);
$type = isset($args[0]) ? $args[0] : '';
$quiet = in_array('--quiet', $args);

// Help
if (empty($type) || in_array('--help', $args) || in_array('-h', $args)) {
    showUsage();
    exit(0);
}

// Validazione tipo backup
$validTypes = ['database', 'files', 'complete'];
if (!in_array($type, $validTypes)) {
    echo "ERRORE: Tipo backup non valido '$type'" . PHP_EOL;
    echo "Tipi supportati: " . implode(', ', $validTypes) . PHP_EOL;
    exit(1);
}

// Carica configurazione
$config = [];
if (file_exists('editable_config.php')) {
    $config = include 'editable_config.php';
} else {
    echo "ERRORE: File configurazione 'editable_config.php' non trovato!" . PHP_EOL;
    exit(1);
}

// Verifica configurazione database per backup database/complete
if (($type === 'database' || $type === 'complete') && 
    (empty($config['DB_HOST']) || empty($config['DB_USERNAME']) || empty($config['DB_NAME']))) {
    echo "ERRORE: Configurazione database incompleta!" . PHP_EOL;
    echo "Verificare DB_HOST, DB_USERNAME e DB_NAME in editable_config.php" . PHP_EOL;
    exit(1);
}

logMessage("=== AVVIO BACKUP SISTEMA ===", $quiet);
logMessage("Tipo backup: " . strtoupper($type), $quiet);
logMessage("Directory lavoro: " . getcwd(), $quiet);

$startTime = microtime(true);
$result = null;

// Esegui backup in base al tipo
switch ($type) {
    case 'database':
        logMessage("Creazione backup database...", $quiet);
        $result = createDatabaseBackup($config);
        break;
        
    case 'files':
        logMessage("Creazione backup files...", $quiet);
        $result = createFilesBackup();
        break;
        
    case 'complete':
        logMessage("Creazione backup completo...", $quiet);
        $result = createCompleteBackup($config);
        break;
}

$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

// Risultato
if ($result && $result['success']) {
    $fileSize = formatBytes($result['size']);
    logMessage("✅ BACKUP COMPLETATO CON SUCCESSO!", $quiet);
    logMessage("File creato: " . $result['file'], $quiet);
    logMessage("Dimensione: " . $fileSize, $quiet);
    logMessage("Tempo esecuzione: {$executionTime} secondi", $quiet);
    
    // Output JSON per automazione (solo in modalità quiet)
    if ($quiet) {
        $output = [
            'success' => true,
            'file' => $result['file'],
            'size' => $result['size'],
            'execution_time' => $executionTime,
            'timestamp' => date('c')
        ];
        echo json_encode($output) . PHP_EOL;
    }
    
    exit(0);
} else {
    $error = $result['error'] ?? 'Errore sconosciuto';
    logMessage("❌ BACKUP FALLITO!", $quiet);
    logMessage("Errore: " . $error, $quiet);
    logMessage("Tempo esecuzione: {$executionTime} secondi", $quiet);
    
    // Output JSON per automazione (solo in modalità quiet)
    if ($quiet) {
        $output = [
            'success' => false,
            'error' => $error,
            'execution_time' => $executionTime,
            'timestamp' => date('c')
        ];
        echo json_encode($output) . PHP_EOL;
    }
    
    exit(1);
}

// Funzione helper (se non già definita)
if (!function_exists('formatBytes')) {
    function formatBytes($size, $precision = 2) {
        $base = log($size, 1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');   
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
}
?>