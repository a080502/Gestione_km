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
} else {
    die('File di configurazione non trovato');
}

// Funzione per formattare dimensioni file
function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');   
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

// Funzione per backup database (semplificata)
function backupDatabase($config) {
    $backupDir = 'backups/database/';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $filename = 'db_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backupDir . $filename;
    
    // Prova mysqldump prima
    $command = sprintf(
        'mysqldump --host=%s --user=%s --password=%s %s > %s 2>&1',
        escapeshellarg($config['DB_HOST']),
        escapeshellarg($config['DB_USERNAME']),
        escapeshellarg($config['DB_PASSWORD']),
        escapeshellarg($config['DB_NAME']),
        escapeshellarg($filepath)
    );
    
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    
    // Se mysqldump fallisce, usa MySQLi se disponibile
    if ($return_var !== 0 || !file_exists($filepath) || filesize($filepath) == 0) {
        if (class_exists('mysqli')) {
            return backupDatabaseMySQLi($config, $filepath);
        } else {
            return [
                'success' => false, 
                'error' => 'mysqldump fallito e MySQLi non disponibile. Output: ' . implode(' ', $output)
            ];
        }
    }
    
    return [
        'success' => true,
        'file' => $filepath,
        'size' => filesize($filepath),
        'method' => 'mysqldump'
    ];
}

// Backup database con MySQLi
function backupDatabaseMySQLi($config, $filepath) {
    try {
        $conn = new mysqli($config['DB_HOST'], $config['DB_USERNAME'], $config['DB_PASSWORD'], $config['DB_NAME']);
        
        if ($conn->connect_error) {
            return ['success' => false, 'error' => 'Connessione fallita: ' . $conn->connect_error];
        }
        
        $conn->set_charset("utf8mb4");
        
        $sql = "-- Database Backup - " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Database: " . $config['DB_NAME'] . "\n\n";
        $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql .= "START TRANSACTION;\n\n";
        
        // Ottieni tabelle
        $result = $conn->query("SHOW TABLES");
        $tables = [];
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        foreach ($tables as $table) {
            // Struttura
            $createResult = $conn->query("SHOW CREATE TABLE `$table`");
            $createRow = $createResult->fetch_assoc();
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $createRow['Create Table'] . ";\n\n";
            
            // Dati
            $dataResult = $conn->query("SELECT * FROM `$table`");
            if ($dataResult && $dataResult->num_rows > 0) {
                while ($row = $dataResult->fetch_assoc()) {
                    $columns = array_keys($row);
                    $columnList = '`' . implode('`, `', $columns) . '`';
                    
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . $conn->real_escape_string($value) . "'";
                        }
                    }
                    
                    $sql .= "INSERT INTO `$table` ($columnList) VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }
        
        $sql .= "COMMIT;\n";
        $conn->close();
        
        if (file_put_contents($filepath, $sql)) {
            return [
                'success' => true,
                'file' => $filepath,
                'size' => filesize($filepath),
                'method' => 'mysqli'
            ];
        } else {
            return ['success' => false, 'error' => 'Impossibile scrivere file SQL'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Errore MySQLi: ' . $e->getMessage()];
    }
}

// Backup files
function backupFiles() {
    $backupDir = 'backups/files/';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $filename = 'files_backup_' . date('Y-m-d_H-i-s') . '.tar.gz';
    $filepath = $backupDir . $filename;
    
    // Usa sempre tar (più affidabile)
    $excludes = '--exclude=backups --exclude=vendor/tecnickcom --exclude=.git --exclude=tmp';
    $command = "tar -czf " . escapeshellarg($filepath) . " $excludes --exclude='*.log' . 2>&1";
    
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    
    if ($return_var === 0 && file_exists($filepath) && filesize($filepath) > 0) {
        return [
            'success' => true,
            'file' => $filepath,
            'size' => filesize($filepath)
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Errore TAR: ' . implode(' ', $output)
        ];
    }
}

// Backup completo
function backupComplete($config) {
    $backupDir = 'backups/complete/';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $filename = 'complete_backup_' . $timestamp . '.tar.gz';
    $filepath = $backupDir . $filename;
    
    // Crea backup database
    $dbBackup = backupDatabase($config);
    if (!$dbBackup['success']) {
        return ['success' => false, 'error' => 'Errore backup DB: ' . $dbBackup['error']];
    }
    
    // Crea directory temporanea
    $tempDir = 'temp_backup_' . $timestamp;
    mkdir($tempDir, 0755, true);
    mkdir($tempDir . '/database', 0755, true);
    mkdir($tempDir . '/website', 0755, true);
    
    // Copia backup database
    if (!copy($dbBackup['file'], $tempDir . '/database/' . basename($dbBackup['file']))) {
        exec("rm -rf " . escapeshellarg($tempDir));
        return ['success' => false, 'error' => 'Errore copia file database'];
    }
    
    // Crea README
    $readme = "=== BACKUP COMPLETO GESTIONE KM ===\n";
    $readme .= "Data: " . date('Y-m-d H:i:s') . "\n";
    $readme .= "Database: " . $config['DB_NAME'] . "\n\n";
    $readme .= "CONTENUTO:\n";
    $readme .= "- database/ : Backup SQL completo\n";
    $readme .= "- website/  : Tutti i file del progetto\n\n";
    $readme .= "RIPRISTINO:\n";
    $readme .= "1. Estrarre questo archivio\n";
    $readme .= "2. Caricare files da website/\n";
    $readme .= "3. Importare SQL da database/\n";
    file_put_contents($tempDir . '/README.txt', $readme);
    
    // Lista di elementi da escludere
    $excludeItems = ['backups', $tempDir, '.git', 'tmp', '.gitignore'];
    
    // Copia files del progetto (metodo più affidabile)
    $items = array_diff(scandir('.'), ['.', '..']);
    $copyErrors = [];
    
    foreach ($items as $item) {
        // Salta elementi da escludere
        if (in_array($item, $excludeItems)) {
            continue;
        }
        
        $sourcePath = $item;
        $destPath = $tempDir . '/website/' . $item;
        
        if (is_dir($sourcePath)) {
            // Copia directory ricorsivamente
            $command = "cp -r " . escapeshellarg($sourcePath) . " " . escapeshellarg($destPath) . " 2>&1";
            $output = [];
            $return_var = 0;
            exec($command, $output, $return_var);
            
            if ($return_var !== 0) {
                $copyErrors[] = "Directory $item: " . implode(' ', $output);
            }
        } else {
            // Copia file singolo
            if (!copy($sourcePath, $destPath)) {
                $copyErrors[] = "File $item: impossibile copiare";
            }
        }
    }
    
    // Verifica che ci siano file copiati
    $websiteFiles = array_diff(scandir($tempDir . '/website'), ['.', '..']);
    if (empty($websiteFiles)) {
        exec("rm -rf " . escapeshellarg($tempDir));
        $errorMsg = 'Nessun file copiato nella cartella website';
        if (!empty($copyErrors)) {
            $errorMsg .= '. Errori: ' . implode('; ', $copyErrors);
        }
        return ['success' => false, 'error' => $errorMsg];
    }
    
    // Crea archivio finale
    $command = "tar -czf " . escapeshellarg($filepath) . " -C " . escapeshellarg($tempDir) . " . 2>&1";
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    
    // Pulizia directory temporanea
    exec("rm -rf " . escapeshellarg($tempDir));
    
    // Pulizia backup database temporaneo
    if (file_exists($dbBackup['file'])) {
        unlink($dbBackup['file']);
    }
    
    if ($return_var === 0 && file_exists($filepath) && filesize($filepath) > 0) {
        return [
            'success' => true,
            'file' => $filepath,
            'size' => filesize($filepath),
            'files_copied' => count($websiteFiles),
            'copy_errors' => $copyErrors
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Errore creazione archivio finale: ' . implode(' ', $output)
        ];
    }
}

// Lista backup esistenti
function listBackups() {
    $backups = [];
    $types = ['database', 'files', 'complete'];
    
    foreach ($types as $type) {
        $dir = "backups/$type/";
        if (is_dir($dir)) {
            $files = glob($dir . "*");
            foreach ($files as $file) {
                if (is_file($file)) {
                    $backups[] = [
                        'type' => $type,
                        'name' => basename($file),
                        'path' => $file,
                        'size' => formatBytes(filesize($file)),
                        'date' => date('d/m/Y H:i:s', filemtime($file)),
                        'timestamp' => filemtime($file)
                    ];
                }
            }
        }
    }
    
    // Ordina per data (più recenti prima)
    usort($backups, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    
    return $backups;
}

// Gestione richieste AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'backup_database':
            $result = backupDatabase($config);
            echo json_encode($result);
            exit;
            
        case 'backup_files':
            $result = backupFiles();
            echo json_encode($result);
            exit;
            
        case 'backup_complete':
            $result = backupComplete($config);
            echo json_encode($result);
            exit;
            
        case 'list_backups':
            $backups = listBackups();
            echo json_encode(['success' => true, 'backups' => $backups]);
            exit;
            
        case 'delete_backup':
            $file = $_POST['file'] ?? '';
            if ($file && file_exists($file) && strpos(realpath($file), realpath('backups/')) === 0) {
                if (unlink($file)) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Impossibile eliminare il file']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'File non valido']);
            }
            exit;
    }
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
        exit;
    }
}

// Test connessione database per diagnostica
$dbStatus = 'unknown';
$dbError = '';

if (class_exists('mysqli')) {
    $testConn = @new mysqli($config['DB_HOST'], $config['DB_USERNAME'], $config['DB_PASSWORD'], $config['DB_NAME']);
    if ($testConn->connect_error) {
        $dbStatus = 'error';
        $dbError = $testConn->connect_error;
    } else {
        $dbStatus = 'ok';
        $testConn->close();
    }
} else {
    $dbStatus = 'no_mysqli';
    $dbError = 'MySQLi non disponibile';
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
        .status-card {
            border-radius: 10px;
            border: none;
            margin-bottom: 1rem;
        }
        .status-ok { border-left: 4px solid #28a745; background-color: #d4edda; }
        .status-warning { border-left: 4px solid #ffc107; background-color: #fff3cd; }
        .status-error { border-left: 4px solid #dc3545; background-color: #f8d7da; }
        
        .backup-card {
            transition: all 0.3s ease;
            border-radius: 10px;
            border: none;
            margin-bottom: 1rem;
        }
        .backup-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .btn-backup {
            min-width: 120px;
        }
        .backup-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            padding: 1rem;
        }
        .badge-database { background-color: #28a745; }
        .badge-files { background-color: #007bff; }
        .badge-complete { background-color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-container">
            <div class="header-section">
                <h1><i class="bi bi-shield-check"></i> Sistema Backup Semplificato</h1>
                <p class="mb-0">Backup sicuro e affidabile per Gestione KM</p>
            </div>
            
            <div class="container-fluid p-4">
                <!-- Status Sistema -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h3><i class="bi bi-info-circle"></i> Stato Sistema</h3>
                    </div>
                    <div class="col-md-4">
                        <div class="card status-card <?php echo $dbStatus === 'ok' ? 'status-ok' : 'status-error'; ?>">
                            <div class="card-body text-center">
                                <h5>Database</h5>
                                <?php if ($dbStatus === 'ok'): ?>
                                    <i class="bi bi-check-circle text-success fs-1"></i>
                                    <p class="mb-0">Connessione OK</p>
                                <?php else: ?>
                                    <i class="bi bi-x-circle text-danger fs-1"></i>
                                    <p class="mb-0">Errore: <?php echo htmlspecialchars($dbError); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card status-card status-ok">
                            <div class="card-body text-center">
                                <h5>TAR/GZIP</h5>
                                <i class="bi bi-check-circle text-success fs-1"></i>
                                <p class="mb-0">Disponibile</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card status-card status-ok">
                            <div class="card-body text-center">
                                <h5>Cartelle</h5>
                                <i class="bi bi-check-circle text-success fs-1"></i>
                                <p class="mb-0">Permessi OK</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Creazione Backup -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h3><i class="bi bi-plus-circle"></i> Crea Backup</h3>
                    </div>
                    <div class="col-md-4">
                        <div class="card backup-card">
                            <div class="card-body text-center">
                                <i class="bi bi-database text-success fs-1 mb-3"></i>
                                <h5>Backup Database</h5>
                                <p>Esporta tutte le tabelle e dati in formato SQL</p>
                                <button class="btn btn-success btn-backup" onclick="createBackup('database')" id="btn-database">
                                    <i class="bi bi-download"></i> Backup DB
                                </button>
                                <div class="mt-3" id="result-database" style="display:none;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card backup-card">
                            <div class="card-body text-center">
                                <i class="bi bi-folder text-primary fs-1 mb-3"></i>
                                <h5>Backup Files</h5>
                                <p>Archivio compresso di tutti i file del progetto</p>
                                <button class="btn btn-primary btn-backup" onclick="createBackup('files')" id="btn-files">
                                    <i class="bi bi-file-zip"></i> Backup Files
                                </button>
                                <div class="mt-3" id="result-files" style="display:none;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card backup-card">
                            <div class="card-body text-center">
                                <i class="bi bi-hdd-stack text-danger fs-1 mb-3"></i>
                                <h5>Backup Completo</h5>
                                <p>Database + Files + istruzioni in unico archivio</p>
                                <button class="btn btn-danger btn-backup" onclick="createBackup('complete')" id="btn-complete">
                                    <i class="bi bi-collection"></i> Backup Totale
                                </button>
                                <div class="mt-3" id="result-complete" style="display:none;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Lista Backup -->
                <div class="row">
                    <div class="col-12">
                        <h3><i class="bi bi-list"></i> Backup Disponibili</h3>
                        <button class="btn btn-outline-secondary btn-sm mb-3" onclick="loadBackupList()">
                            <i class="bi bi-arrow-clockwise"></i> Aggiorna
                        </button>
                        <div id="backup-list">
                            <div class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Caricamento...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
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
        function createBackup(type) {
            const button = document.getElementById('btn-' + type);
            const result = document.getElementById('result-' + type);
            
            // Disabilita pulsante e mostra loading
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creazione...';
            result.style.display = 'none';
            
            // Richiesta AJAX
            fetch('backup_system.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=backup_' + type
            })
            .then(response => response.text())
            .then(text => {
                console.log('Risposta server:', text);
                try {
                    const data = JSON.parse(text);
                    
                    if (data.success) {
                        result.innerHTML = `
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> Backup creato!<br>
                                <small>Dimensione: ${formatBytes(data.size)}</small><br>
                                <a href="?download=${encodeURIComponent(data.file)}" class="btn btn-success btn-sm mt-2">
                                    <i class="bi bi-download"></i> Scarica
                                </a>
                            </div>
                        `;
                        loadBackupList();
                    } else {
                        result.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="bi bi-x-circle"></i> Errore:<br>
                                <small>${data.error}</small>
                            </div>
                        `;
                    }
                    
                } catch (e) {
                    console.error('Errore parsing JSON:', e);
                    result.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-x-circle"></i> Errore di comunicazione<br>
                            <small>Risposta non valida dal server</small>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Errore fetch:', error);
                result.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-x-circle"></i> Errore di rete<br>
                        <small>${error.message}</small>
                    </div>
                `;
            })
            .finally(() => {
                // Reset pulsante
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
                
                button.disabled = false;
                button.innerHTML = `<i class="bi ${icons[type]}"></i> ${labels[type]}`;
                result.style.display = 'block';
            });
        }
        
        function loadBackupList() {
            const listContainer = document.getElementById('backup-list');
            listContainer.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';
            
            fetch('backup_system.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=list_backups'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.backups.length > 0) {
                    let html = '';
                    data.backups.forEach(backup => {
                        const badgeClass = `badge-${backup.type}`;
                        html += `
                            <div class="backup-item">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h6 class="mb-1">${backup.name}</h6>
                                        <small class="text-muted">${backup.date}</small>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <span class="badge ${badgeClass}">${backup.type.toUpperCase()}</span><br>
                                        <small>${backup.size}</small>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <a href="?download=${encodeURIComponent(backup.path)}" class="btn btn-primary btn-sm me-1">
                                            <i class="bi bi-download"></i>
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
                        <div class="text-center py-4">
                            <i class="bi bi-inbox display-1 text-muted"></i>
                            <h4>Nessun backup disponibile</h4>
                            <p>Crea il tuo primo backup usando i pulsanti sopra.</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                listContainer.innerHTML = `
                    <div class="alert alert-danger">
                        Errore nel caricamento: ${error.message}
                    </div>
                `;
            });
        }
        
        function deleteBackup(path, name) {
            if (confirm(`Eliminare il backup "${name}"?`)) {
                fetch('backup_system.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=delete_backup&file=' + encodeURIComponent(path)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadBackupList();
                    } else {
                        alert('Errore eliminazione: ' + data.error);
                    }
                });
            }
        }
        
        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Carica lista all'avvio
        document.addEventListener('DOMContentLoaded', loadBackupList);
    </script>
</body>
</html>