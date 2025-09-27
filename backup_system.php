<?php
session_start();

// --- Controllo di Sicurezza ---
if (!isset($_SESSION['username'])) {
    header("Location: login.php?error=unauthorized");
    exit();
}

// Carica configurazione
$config = [];
if (file_exists('editable_config.php')) {
    $config = include 'editable_config.php';
}

// Funzione per ottenere dimensione leggibile
function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');   
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

// Funzione per creare backup database
function createDatabaseBackup($config) {
    $backupDir = 'backups/database/';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $filename = 'db_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backupDir . $filename;
    
    $host = $config['DB_HOST'];
    $username = $config['DB_USERNAME'];
    $password = $config['DB_PASSWORD'];
    $database = $config['DB_NAME'];
    
    // Comando mysqldump
    $command = "mysqldump --host=$host --user=$username --password='$password' $database > $filepath 2>&1";
    
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    
    if ($return_var === 0 && file_exists($filepath) && filesize($filepath) > 0) {
        return ['success' => true, 'file' => $filepath, 'size' => filesize($filepath)];
    } else {
        // Tenta backup alternativo con PHP
        return createDatabaseBackupPHP($config, $filepath);
    }
}

// Backup database alternativo con PHP
function createDatabaseBackupPHP($config, $filepath) {
    try {
        $pdo = new PDO(
            "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']};charset=utf8mb4",
            $config['DB_USERNAME'],
            $config['DB_PASSWORD'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $sql = "-- Database Backup Generated on " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Database: {$config['DB_NAME']}\n\n";
        $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql .= "SET AUTOCOMMIT = 0;\n";
        $sql .= "START TRANSACTION;\n\n";
        
        // Ottieni tutte le tabelle
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            // Struttura tabella
            $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
            $sql .= "-- Structure for table `$table`\n";
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $createTable['Create Table'] . ";\n\n";
            
            // Dati tabella
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $sql .= "-- Data for table `$table`\n";
                $columns = array_keys($rows[0]);
                $columnList = '`' . implode('`, `', $columns) . '`';
                
                foreach ($rows as $row) {
                    $values = array_map(function($value) use ($pdo) {
                        return $value === null ? 'NULL' : $pdo->quote($value);
                    }, array_values($row));
                    
                    $sql .= "INSERT INTO `$table` ($columnList) VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }
        
        $sql .= "COMMIT;\n";
        
        if (file_put_contents($filepath, $sql)) {
            return ['success' => true, 'file' => $filepath, 'size' => filesize($filepath)];
        } else {
            return ['success' => false, 'error' => 'Impossibile scrivere il file di backup'];
        }
        
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Errore database: ' . $e->getMessage()];
    }
}

// Funzione per creare backup files (fallback con tar)
function createFilesBackupTar() {
    $backupDir = 'backups/files/';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $filename = 'files_backup_' . date('Y-m-d_H-i-s') . '.tar.gz';
    $filepath = $backupDir . $filename;
    
    // Lista delle cartelle da escludere
    $excludeDirs = ['backups', 'vendor/tecnickcom', 'node_modules', '.git', 'tmp'];
    $excludeOptions = '';
    foreach ($excludeDirs as $dir) {
        $excludeOptions .= " --exclude='$dir'";
    }
    
    // Comando tar per creare archivio compresso
    $command = "tar -czf " . escapeshellarg($filepath) . $excludeOptions . " --exclude='*.log' . 2>&1";
    
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    
    if ($return_var === 0 && file_exists($filepath) && filesize($filepath) > 0) {
        return ['success' => true, 'file' => $filepath, 'size' => filesize($filepath)];
    } else {
        return ['success' => false, 'error' => 'Errore durante la creazione del backup files: ' . implode(' ', $output)];
    }
}

// Funzione per creare backup files
function createFilesBackup() {
    // Verifica se ZipArchive è disponibile
    if (!class_exists('ZipArchive')) {
        return createFilesBackupTar();
    }
    
    $backupDir = 'backups/files/';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $filename = 'files_backup_' . date('Y-m-d_H-i-s') . '.zip';
    $filepath = $backupDir . $filename;
    
    $zip = new ZipArchive();
    if ($zip->open($filepath, ZipArchive::CREATE) !== TRUE) {
        return ['success' => false, 'error' => 'Impossibile creare il file ZIP'];
    }
    
    // Escludi alcune cartelle dal backup
    $excludeDirs = ['backups', 'vendor/tecnickcom', 'node_modules', '.git', 'tmp'];
    $excludeFiles = ['.DS_Store', 'Thumbs.db', '*.log'];
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen(realpath('.')) + 1);
        
        // Controlla se il file/cartella è da escludere
        $skip = false;
        foreach ($excludeDirs as $excludeDir) {
            if (strpos($relativePath, $excludeDir . DIRECTORY_SEPARATOR) === 0 || 
                strpos($relativePath, $excludeDir . '/') === 0 ||
                $relativePath === $excludeDir) {
                $skip = true;
                break;
            }
        }
        
        if ($skip) continue;
        
        if ($file->isDir()) {
            $zip->addEmptyDir($relativePath);
        } elseif ($file->isFile()) {
            $zip->addFile($filePath, $relativePath);
        }
    }
    
    $zip->close();
    
    if (file_exists($filepath) && filesize($filepath) > 0) {
        return ['success' => true, 'file' => $filepath, 'size' => filesize($filepath)];
    } else {
        return ['success' => false, 'error' => 'Errore durante la creazione del backup files'];
    }
}

// Funzione per creare backup completo (fallback con tar)
function createCompleteBackupTar($config) {
    $backupDir = 'backups/complete/';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $filename = 'complete_backup_' . $timestamp . '.tar.gz';
    $filepath = $backupDir . $filename;
    
    // Crea backup database
    $dbBackup = createDatabaseBackup($config);
    if (!$dbBackup['success']) {
        return ['success' => false, 'error' => 'Errore backup database: ' . ($dbBackup['error'] ?? 'Errore sconosciuto')];
    }
    
    // Crea cartella temporanea per organizzare i file
    $tempDir = 'tmp_backup_' . $timestamp;
    mkdir($tempDir, 0755, true);
    mkdir($tempDir . '/database', 0755, true);
    mkdir($tempDir . '/website', 0755, true);
    
    // Copia backup database nella cartella temp
    copy($dbBackup['file'], $tempDir . '/database/' . basename($dbBackup['file']));
    
    // Crea file README per il backup
    $backupInfo = "=== BACKUP COMPLETO GESTIONE KM ===\n";
    $backupInfo .= "Data creazione: " . date('Y-m-d H:i:s') . "\n";
    $backupInfo .= "Utente: " . $_SESSION['username'] . "\n";
    $backupInfo .= "Database: " . $config['DB_NAME'] . "\n";
    $backupInfo .= "Host: " . $config['DB_HOST'] . "\n";
    $backupInfo .= "\nContenuto:\n";
    $backupInfo .= "- Database completo (SQL)\n";
    $backupInfo .= "- Tutti i file del sito web\n";
    $backupInfo .= "- Configurazioni e immagini\n";
    $backupInfo .= "\nPer ripristinare:\n";
    $backupInfo .= "1. Estrarre il contenuto di questo archivio\n";
    $backupInfo .= "2. Caricare i file dalla cartella 'website/'\n";
    $backupInfo .= "3. Importare il file SQL dalla cartella 'database/'\n";
    
    file_put_contents($tempDir . '/README_BACKUP.txt', $backupInfo);
    
    // Lista delle cartelle da escludere
    $excludeDirs = ['backups', 'vendor/tecnickcom', 'node_modules', '.git', 'tmp', $tempDir];
    $excludeOptions = '';
    foreach ($excludeDirs as $dir) {
        $excludeOptions .= " --exclude='$dir'";
    }
    
    // Copia tutti i file del sito nella cartella website del temp
    $command = "cp -r . " . escapeshellarg($tempDir . '/website/') . " 2>/dev/null";
    exec($command);
    
    // Rimuovi cartelle escluse dalla copia
    foreach ($excludeDirs as $dir) {
        if (is_dir($tempDir . '/website/' . $dir)) {
            exec("rm -rf " . escapeshellarg($tempDir . '/website/' . $dir));
        }
    }
    
    // Crea archivio tar compresso
    $command = "tar -czf " . escapeshellarg($filepath) . " -C " . escapeshellarg($tempDir) . " . 2>&1";
    
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    
    // Pulizia: rimuovi cartella temporanea e backup database temporaneo
    exec("rm -rf " . escapeshellarg($tempDir));
    if (file_exists($dbBackup['file'])) {
        unlink($dbBackup['file']);
    }
    
    if ($return_var === 0 && file_exists($filepath) && filesize($filepath) > 0) {
        return ['success' => true, 'file' => $filepath, 'size' => filesize($filepath)];
    } else {
        return ['success' => false, 'error' => 'Errore durante la creazione del backup completo: ' . implode(' ', $output)];
    }
}

// Funzione per creare backup completo
function createCompleteBackup($config) {
    // Verifica se ZipArchive è disponibile
    if (!class_exists('ZipArchive')) {
        return createCompleteBackupTar($config);
    }
    
    $backupDir = 'backups/complete/';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $filename = 'complete_backup_' . $timestamp . '.zip';
    $filepath = $backupDir . $filename;
    
    // Crea backup database
    $dbBackup = createDatabaseBackup($config);
    if (!$dbBackup['success']) {
        return ['success' => false, 'error' => 'Errore backup database: ' . ($dbBackup['error'] ?? 'Errore sconosciuto')];
    }
    
    // Crea ZIP completo
    $zip = new ZipArchive();
    if ($zip->open($filepath, ZipArchive::CREATE) !== TRUE) {
        return ['success' => false, 'error' => 'Impossibile creare il file ZIP completo'];
    }
    
    // Aggiungi backup database
    $zip->addFile($dbBackup['file'], 'database/' . basename($dbBackup['file']));
    
    // Aggiungi tutti i files (esclusi backup precedenti)
    $excludeDirs = ['backups', 'vendor/tecnickcom', 'node_modules', '.git', 'tmp'];
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen(realpath('.')) + 1);
        
        // Controlla se il file/cartella è da escludere
        $skip = false;
        foreach ($excludeDirs as $excludeDir) {
            if (strpos($relativePath, $excludeDir . DIRECTORY_SEPARATOR) === 0 || 
                strpos($relativePath, $excludeDir . '/') === 0 ||
                $relativePath === $excludeDir) {
                $skip = true;
                break;
            }
        }
        
        if ($skip) continue;
        
        if ($file->isDir()) {
            $zip->addEmptyDir('website/' . $relativePath);
        } elseif ($file->isFile()) {
            $zip->addFile($filePath, 'website/' . $relativePath);
        }
    }
    
    // Aggiungi file info sul backup
    $backupInfo = "=== BACKUP COMPLETO GESTIONE KM ===\n";
    $backupInfo .= "Data creazione: " . date('Y-m-d H:i:s') . "\n";
    $backupInfo .= "Utente: " . $_SESSION['username'] . "\n";
    $backupInfo .= "Database: " . $config['DB_NAME'] . "\n";
    $backupInfo .= "Host: " . $config['DB_HOST'] . "\n";
    $backupInfo .= "\nContenuto:\n";
    $backupInfo .= "- Database completo (SQL)\n";
    $backupInfo .= "- Tutti i file del sito web\n";
    $backupInfo .= "- Configurazioni e immagini\n";
    $backupInfo .= "\nPer ripristinare:\n";
    $backupInfo .= "1. Estrarre il contenuto di questo ZIP\n";
    $backupInfo .= "2. Caricare i file dalla cartella 'website/'\n";
    $backupInfo .= "3. Importare il file SQL dalla cartella 'database/'\n";
    
    $zip->addFromString('README_BACKUP.txt', $backupInfo);
    
    $zip->close();
    
    // Pulizia: rimuovi il backup database temporaneo
    if (file_exists($dbBackup['file'])) {
        unlink($dbBackup['file']);
    }
    
    if (file_exists($filepath) && filesize($filepath) > 0) {
        return ['success' => true, 'file' => $filepath, 'size' => filesize($filepath)];
    } else {
        return ['success' => false, 'error' => 'Errore durante la creazione del backup completo'];
    }
}

// Gestione richieste AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Abilita error reporting per debugging ma non mostrare errori HTML
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    
    // Buffer output per catturare eventuali warning/notice
    ob_start();
    
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'backup_database':
                $result = createDatabaseBackup($config);
                echo json_encode($result);
                break;
                
            case 'backup_files':
                $result = createFilesBackup();
                echo json_encode($result);
                break;
                
            case 'backup_complete':
                $result = createCompleteBackup($config);
                echo json_encode($result);
                break;
            
        case 'list_backups':
            $backups = [];
            $backupTypes = ['database', 'files', 'complete'];
            
            foreach ($backupTypes as $type) {
                $dir = "backups/$type/";
                if (is_dir($dir)) {
                    $files = glob($dir . "*");
                    foreach ($files as $file) {
                        $backups[] = [
                            'type' => $type,
                            'name' => basename($file),
                            'path' => $file,
                            'size' => formatBytes(filesize($file)),
                            'date' => date('d/m/Y H:i:s', filemtime($file))
                        ];
                    }
                }
            }
            
            // Ordina per data (più recenti prima)
            usort($backups, function($a, $b) {
                return filemtime($b['path']) - filemtime($a['path']);
            });
            
            echo json_encode(['success' => true, 'backups' => $backups]);
            break;
            
        case 'delete_backup':
            $file = $_POST['file'] ?? '';
            if ($file && file_exists($file) && strpos(realpath($file), realpath('backups/')) === 0) {
                if (unlink($file)) {
                    echo json_encode(['success' => true, 'message' => 'Backup eliminato con successo']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Impossibile eliminare il file']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'File non valido']);
            }
            break;
    }
    
    } catch (Exception $e) {
        // Pulisci l'output buffer in caso di errore
        ob_clean();
        echo json_encode([
            'success' => false, 
            'error' => 'Errore durante l\'operazione: ' . $e->getMessage()
        ]);
    } finally {
        // Pulisci sempre l'output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
    }
    
    exit();
}

// Gestione download
if (isset($_GET['download']) && !empty($_GET['download'])) {
    $file = $_GET['download'];
    if (file_exists($file) && strpos(realpath($file), realpath('backups/')) === 0) {
        $filename = basename($file);
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        readfile($file);
        exit();
    } else {
        die('File non trovato o non valido.');
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Backup - Gestione KM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .main-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header-section {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .backup-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .backup-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .backup-card .card-header {
            font-weight: 600;
            border-bottom: 2px solid;
        }
        
        .card-database { border-left: 4px solid #28a745; }
        .card-database .card-header { background-color: #d4edda; border-color: #28a745; }
        
        .card-files { border-left: 4px solid #007bff; }
        .card-files .card-header { background-color: #cce5ff; border-color: #007bff; }
        
        .card-complete { border-left: 4px solid #dc3545; }
        .card-complete .card-header { background-color: #f8d7da; border-color: #dc3545; }
        
        .progress-container {
            display: none;
            margin: 1rem 0;
        }
        
        .backup-list-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .backup-list-item:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }
        
        .badge-database { background-color: #28a745; }
        .badge-files { background-color: #007bff; }
        .badge-complete { background-color: #dc3545; }
        
        .btn-action {
            min-width: 100px;
        }
        
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
        
        .status-message {
            display: none;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-container">
            <div class="header-section">
                <h1><i class="bi bi-shield-check"></i> Sistema Backup Completo</h1>
                <p class="mb-0">Gestione backup database e files del sistema Gestione KM</p>
            </div>
            
            <div class="container-fluid p-4">
                <!-- Sezione Creazione Backup -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h3><i class="bi bi-plus-circle"></i> Crea Nuovo Backup</h3>
                        <?php if (!class_exists('ZipArchive')): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> <strong>Nota:</strong> 
                            L'estensione PHP ZipArchive non è disponibile. I backup verranno creati in formato TAR.GZ invece che ZIP.
                        </div>
                        <?php endif; ?>
                        <hr>
                    </div>
                </div>
                
                <div class="row mb-5">
                    <!-- Backup Database -->
                    <div class="col-lg-4 mb-3">
                        <div class="card backup-card card-database h-100">
                            <div class="card-header">
                                <i class="bi bi-database"></i> Backup Database
                            </div>
                            <div class="card-body">
                                <p>Crea un backup completo del database con tutte le tabelle e dati.</p>
                                <ul class="list-unstyled">
                                    <li><i class="bi bi-check text-success"></i> Struttura tabelle</li>
                                    <li><i class="bi bi-check text-success"></i> Tutti i dati</li>
                                    <li><i class="bi bi-check text-success"></i> File SQL standard</li>
                                </ul>
                            </div>
                            <div class="card-footer text-center">
                                <button class="btn btn-success btn-action" onclick="createBackup('database')">
                                    <i class="bi bi-download"></i> Backup DB
                                </button>
                                <div class="progress-container">
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-animated" style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted">Creazione backup in corso...</small>
                                </div>
                                <div class="status-message alert"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Backup Files -->
                    <div class="col-lg-4 mb-3">
                        <div class="card backup-card card-files h-100">
                            <div class="card-header">
                                <i class="bi bi-folder-fill"></i> Backup Files
                            </div>
                            <div class="card-body">
                                <p>Crea un archivio ZIP con tutti i files del sito web.</p>
                                <ul class="list-unstyled">
                                    <li><i class="bi bi-check text-success"></i> Codice PHP</li>
                                    <li><i class="bi bi-check text-success"></i> Immagini e media</li>
                                    <li><i class="bi bi-check text-success"></i> Configurazioni</li>
                                </ul>
                            </div>
                            <div class="card-footer text-center">
                                <button class="btn btn-primary btn-action" onclick="createBackup('files')">
                                    <i class="bi bi-file-zip"></i> Backup Files
                                </button>
                                <div class="progress-container">
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-animated" style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted">Creazione backup in corso...</small>
                                </div>
                                <div class="status-message alert"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Backup Completo -->
                    <div class="col-lg-4 mb-3">
                        <div class="card backup-card card-complete h-100">
                            <div class="card-header">
                                <i class="bi bi-hdd-stack"></i> Backup Completo
                            </div>
                            <div class="card-body">
                                <p>Backup completo: database + files in un unico archivio.</p>
                                <ul class="list-unstyled">
                                    <li><i class="bi bi-check text-success"></i> Database completo</li>
                                    <li><i class="bi bi-check text-success"></i> Tutti i files</li>
                                    <li><i class="bi bi-check text-success"></i> Istruzioni ripristino</li>
                                </ul>
                            </div>
                            <div class="card-footer text-center">
                                <button class="btn btn-danger btn-action" onclick="createBackup('complete')">
                                    <i class="bi bi-collection"></i> Backup Totale
                                </button>
                                <div class="progress-container">
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-animated" style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted">Creazione backup in corso...</small>
                                </div>
                                <div class="status-message alert"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sezione Lista Backup -->
                <div class="row">
                    <div class="col-12">
                        <h3><i class="bi bi-list"></i> Backup Disponibili</h3>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">I tuoi backup salvati</span>
                            <button class="btn btn-outline-secondary btn-sm" onclick="loadBackupList()">
                                <i class="bi bi-arrow-clockwise"></i> Aggiorna Lista
                            </button>
                        </div>
                        
                        <div id="backup-list">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Caricamento...</span>
                                </div>
                                <p class="mt-2">Caricamento backup disponibili...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pulsante Torna Indietro -->
                <div class="row mt-5">
                    <div class="col-12 text-center">
                        <a href="index.php" class="btn btn-outline-dark btn-lg">
                            <i class="bi bi-arrow-left"></i> Torna alla Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Funzione per creare backup
        function createBackup(type) {
            const card = document.querySelector(`.card-${type}`);
            const button = card.querySelector('button');
            const progressContainer = card.querySelector('.progress-container');
            const progressBar = card.querySelector('.progress-bar');
            const statusMessage = card.querySelector('.status-message');
            
            // Reset UI
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creazione...';
            progressContainer.style.display = 'block';
            statusMessage.style.display = 'none';
            
            // Simula progresso
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                progressBar.style.width = progress + '%';
            }, 500);
            
            // Richiesta AJAX
            const formData = new FormData();
            formData.append('action', `backup_${type}`);
            
            fetch('backup_system.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                clearInterval(progressInterval);
                progressBar.style.width = '100%';
                
                setTimeout(() => {
                    progressContainer.style.display = 'none';
                    
                    if (data.success) {
                        statusMessage.className = 'status-message alert alert-success';
                        statusMessage.innerHTML = `
                            <i class="bi bi-check-circle"></i> Backup creato con successo!<br>
                            <small>Dimensione: ${formatBytes(data.size)}</small><br>
                            <a href="?download=${encodeURIComponent(data.file)}" class="btn btn-success btn-sm mt-2">
                                <i class="bi bi-download"></i> Scarica Ora
                            </a>
                        `;
                        
                        // Ricarica lista backup
                        setTimeout(() => loadBackupList(), 1000);
                        
                    } else {
                        statusMessage.className = 'status-message alert alert-danger';
                        statusMessage.innerHTML = `
                            <i class="bi bi-exclamation-circle"></i> Errore durante il backup:<br>
                            <small>${data.error || 'Errore sconosciuto'}</small>
                        `;
                    }
                    
                    statusMessage.style.display = 'block';
                    
                    // Reset button
                    resetButton(button, type);
                    
                }, 1000);
            })
            .catch(error => {
                clearInterval(progressInterval);
                progressContainer.style.display = 'none';
                
                statusMessage.className = 'status-message alert alert-danger';
                statusMessage.innerHTML = `
                    <i class="bi bi-exclamation-circle"></i> Errore di connessione:<br>
                    <small>${error.message}</small>
                `;
                statusMessage.style.display = 'block';
                
                resetButton(button, type);
            });
        }
        
        function resetButton(button, type) {
            button.disabled = false;
            const icons = {
                'database': 'bi-download',
                'files': 'bi-file-zip',
                'complete': 'bi-collection'
            };
            const labels = {
                'database': 'Backup DB',
                'files': 'Backup Files',
                'complete': 'Backup Totale'
            };
            
            button.innerHTML = `<i class="bi ${icons[type]}"></i> ${labels[type]}`;
        }
        
        // Funzione per caricare lista backup
        function loadBackupList() {
            const listContainer = document.getElementById('backup-list');
            listContainer.innerHTML = `
                <div class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Caricamento...</span>
                    </div>
                </div>
            `;
            
            const formData = new FormData();
            formData.append('action', 'list_backups');
            
            fetch('backup_system.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.backups.length > 0) {
                    let html = '';
                    data.backups.forEach(backup => {
                        const badgeClass = `badge-${backup.type}`;
                        const typeIcon = {
                            'database': 'bi-database',
                            'files': 'bi-folder-fill', 
                            'complete': 'bi-hdd-stack'
                        }[backup.type];
                        
                        html += `
                            <div class="backup-list-item p-3">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <i class="bi ${typeIcon} me-2 fs-4"></i>
                                            <div>
                                                <h6 class="mb-1">${backup.name}</h6>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar"></i> ${backup.date}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <span class="badge ${badgeClass} text-uppercase">${backup.type}</span><br>
                                        <small class="text-muted">${backup.size}</small>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <a href="?download=${encodeURIComponent(backup.path)}" class="btn btn-primary btn-sm me-1">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                        <button class="btn btn-outline-danger btn-sm" onclick="deleteBackup('${backup.path}', '${backup.name}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    listContainer.innerHTML = html;
                } else {
                    listContainer.innerHTML = `
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox display-1"></i>
                            <h4>Nessun backup disponibile</h4>
                            <p>Crea il tuo primo backup usando i pulsanti sopra.</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                listContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i> Errore nel caricamento della lista backup.
                    </div>
                `;
            });
        }
        
        // Funzione per eliminare backup
        function deleteBackup(filePath, fileName) {
            if (confirm(`Sei sicuro di voler eliminare il backup "${fileName}"?\n\nQuesta operazione non può essere annullata.`)) {
                const formData = new FormData();
                formData.append('action', 'delete_backup');
                formData.append('file', filePath);
                
                fetch('backup_system.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Backup eliminato con successo!');
                        loadBackupList();
                    } else {
                        alert('Errore durante l\'eliminazione: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Errore durante l\'eliminazione del backup.');
                });
            }
        }
        
        // Funzione per formattare bytes
        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Carica lista backup all'avvio
        document.addEventListener('DOMContentLoaded', function() {
            loadBackupList();
        });
    </script>
</body>
</html>