<?php
/**
 * System Status Checker - Sistema di Gestione Chilometri
 * Verifica lo stato del sistema e la configurazione
 */

// Sicurezza: Solo per admin o in modalità debug
session_start();
$isAdmin = isset($_SESSION['username']) && isset($_SESSION['livello']) && $_SESSION['livello'] == '1';
$debugMode = file_exists('.debug') || $_GET['debug'] === 'true';

if (!$isAdmin && !$debugMode) {
    http_response_code(403);
    die('Accesso negato. Solo amministratori possono accedere a questa pagina.');
}

function checkSystemStatus() {
    $status = [
        'php' => [],
        'database' => [],
        'files' => [],
        'permissions' => [],
        'security' => [],
        'overall' => 'unknown'
    ];
    
    // Check PHP
    $status['php']['version'] = [
        'value' => PHP_VERSION,
        'status' => version_compare(PHP_VERSION, '8.0', '>=') ? 'ok' : 'error',
        'message' => 'PHP ' . PHP_VERSION . (version_compare(PHP_VERSION, '8.0', '>=') ? ' (OK)' : ' - Aggiorna a 8.0+')
    ];
    
    $extensions = ['mysqli', 'session', 'json', 'mbstring', 'fileinfo', 'gd'];
    foreach ($extensions as $ext) {
        $status['php']['ext_' . $ext] = [
            'value' => extension_loaded($ext) ? 'Installata' : 'Mancante',
            'status' => extension_loaded($ext) ? 'ok' : 'error',
            'message' => extension_loaded($ext) ? "Estensione $ext: OK" : "Estensione $ext: MANCANTE"
        ];
    }
    
    // Check Database
    try {
        if (file_exists('editable_config.php')) {
            $config = include 'editable_config.php';
            $conn = new mysqli($config['DB_HOST'], $config['DB_USERNAME'], $config['DB_PASSWORD'], $config['DB_NAME']);
            
            if ($conn->connect_error) {
                $status['database']['connection'] = [
                    'value' => 'Errore',
                    'status' => 'error',
                    'message' => 'Connessione database fallita: ' . $conn->connect_error
                ];
            } else {
                $status['database']['connection'] = [
                    'value' => 'Connesso',
                    'status' => 'ok',
                    'message' => 'Connessione database: OK (' . $conn->server_info . ')'
                ];
                
                // Check tables
                $tables = ['utenti', 'chilometri', 'filiali', 'target_annuale', 'costo_extra', 'livelli_autorizzazione'];
                foreach ($tables as $table) {
                    $result = $conn->query("SHOW TABLES LIKE '$table'");
                    $status['database']['table_' . $table] = [
                        'value' => $result && $result->num_rows > 0 ? 'Esistente' : 'Mancante',
                        'status' => $result && $result->num_rows > 0 ? 'ok' : 'error',
                        'message' => "Tabella $table: " . ($result && $result->num_rows > 0 ? 'OK' : 'MANCANTE')
                    ];
                }
                
                // Check admin user
                $adminCheck = $conn->query("SELECT COUNT(*) as count FROM utenti WHERE livello = '1'");
                $adminCount = $adminCheck ? $adminCheck->fetch_assoc()['count'] : 0;
                
                $status['database']['admin_user'] = [
                    'value' => $adminCount > 0 ? "$adminCount trovati" : 'Nessuno',
                    'status' => $adminCount > 0 ? 'ok' : 'warning',
                    'message' => "Utenti admin: " . ($adminCount > 0 ? "$adminCount (OK)" : "NESSUNO - Crea utente admin")
                ];
                
                $conn->close();
            }
        } else {
            $status['database']['config'] = [
                'value' => 'Mancante',
                'status' => 'error',
                'message' => 'File editable_config.php non trovato'
            ];
        }
    } catch (Exception $e) {
        $status['database']['error'] = [
            'value' => 'Errore',
            'status' => 'error',
            'message' => 'Errore database: ' . $e->getMessage()
        ];
    }
    
    // Check Files
    $requiredFiles = [
        'config.php' => 'File configurazione principale',
        'login.php' => 'Pagina login',
        'index.php' => 'Homepage applicazione',
        'database_km.sql' => 'Schema database'
    ];
    
    foreach ($requiredFiles as $file => $desc) {
        $exists = file_exists($file);
        $status['files'][$file] = [
            'value' => $exists ? 'Esistente' : 'Mancante',
            'status' => $exists ? 'ok' : 'error',
            'message' => "$desc: " . ($exists ? 'OK' : 'MANCANTE')
        ];
    }
    
    // Check Permissions
    $directories = [
        'uploads/' => 'Directory upload',
        'uploads/cedolini/' => 'Directory cedolini'
    ];
    
    foreach ($directories as $dir => $desc) {
        $exists = is_dir($dir);
        $writable = $exists ? is_writable($dir) : false;
        
        $status['permissions'][$dir] = [
            'value' => $writable ? 'Scrivibile' : ($exists ? 'Solo lettura' : 'Mancante'),
            'status' => $writable ? 'ok' : 'error',
            'message' => "$desc: " . ($writable ? 'OK' : ($exists ? 'SOLO LETTURA' : 'MANCANTE'))
        ];
    }
    
    // Check Security
    $securityFiles = [
        'setup.php' => ['should_exist' => false, 'desc' => 'File setup (dovrebbe essere eliminato)'],
        '.htaccess' => ['should_exist' => true, 'desc' => 'File sicurezza Apache'],
        'editable_config.php' => ['should_exist' => true, 'desc' => 'File configurazione']
    ];
    
    foreach ($securityFiles as $file => $config) {
        $exists = file_exists($file);
        $shouldExist = $config['should_exist'];
        $isOk = $exists === $shouldExist;
        
        $status['security'][$file] = [
            'value' => $exists ? 'Presente' : 'Assente',
            'status' => $isOk ? 'ok' : ($file === 'setup.php' ? 'warning' : 'error'),
            'message' => $config['desc'] . ': ' . ($isOk ? 'OK' : ($exists ? 'PRESENTE (rimuovi)' : 'MANCANTE'))
        ];
    }
    
    // Overall Status
    $errors = 0;
    $warnings = 0;
    
    foreach ($status as $category => $checks) {
        if ($category === 'overall') continue;
        
        foreach ($checks as $check) {
            if ($check['status'] === 'error') $errors++;
            if ($check['status'] === 'warning') $warnings++;
        }
    }
    
    if ($errors > 0) {
        $status['overall'] = 'error';
    } elseif ($warnings > 0) {
        $status['overall'] = 'warning';
    } else {
        $status['overall'] = 'ok';
    }
    
    return $status;
}

$systemStatus = checkSystemStatus();

// Se richiesto JSON, restituisci solo i dati
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode($systemStatus);
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Status - Gestione Chilometri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .status-ok { color: #198754; }
        .status-warning { color: #fd7e14; }
        .status-error { color: #dc3545; }
        .card-header {
            font-weight: 600;
        }
        .check-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .check-item:last-child {
            border-bottom: none;
        }
        .system-overview {
            font-size: 1.1rem;
            font-weight: 600;
            padding: 1rem;
            border-radius: 0.5rem;
            text-align: center;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body class="bg-light py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h1 class="text-center mb-4">
                    <i class="bi bi-speedometer2"></i>
                    System Status Check
                </h1>
                
                <!-- Overall Status -->
                <div class="system-overview <?php 
                    echo $systemStatus['overall'] === 'ok' ? 'bg-success text-white' : 
                         ($systemStatus['overall'] === 'warning' ? 'bg-warning' : 'bg-danger text-white'); 
                ?>">
                    <i class="bi <?php 
                        echo $systemStatus['overall'] === 'ok' ? 'bi-check-circle-fill' : 
                             ($systemStatus['overall'] === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-x-circle-fill'); 
                    ?>"></i>
                    Sistema: <?php 
                        echo $systemStatus['overall'] === 'ok' ? 'OPERATIVO' : 
                             ($systemStatus['overall'] === 'warning' ? 'AVVISI PRESENTI' : 'ERRORI RILEVATI'); 
                    ?>
                </div>
                
                <div class="row">
                    <!-- PHP Status -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <i class="bi bi-code-slash me-2"></i>PHP Environment
                            </div>
                            <div class="card-body">
                                <?php foreach ($systemStatus['php'] as $key => $check): ?>
                                <div class="check-item">
                                    <span><?= htmlspecialchars($check['message']) ?></span>
                                    <i class="bi <?php 
                                        echo $check['status'] === 'ok' ? 'bi-check-circle status-ok' : 
                                             ($check['status'] === 'warning' ? 'bi-exclamation-triangle status-warning' : 'bi-x-circle status-error'); 
                                    ?>"></i>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Database Status -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <i class="bi bi-database me-2"></i>Database
                            </div>
                            <div class="card-body">
                                <?php foreach ($systemStatus['database'] as $key => $check): ?>
                                <div class="check-item">
                                    <span><?= htmlspecialchars($check['message']) ?></span>
                                    <i class="bi <?php 
                                        echo $check['status'] === 'ok' ? 'bi-check-circle status-ok' : 
                                             ($check['status'] === 'warning' ? 'bi-exclamation-triangle status-warning' : 'bi-x-circle status-error'); 
                                    ?>"></i>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Files Status -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <i class="bi bi-file-text me-2"></i>Files System
                            </div>
                            <div class="card-body">
                                <?php foreach ($systemStatus['files'] as $key => $check): ?>
                                <div class="check-item">
                                    <span><?= htmlspecialchars($check['message']) ?></span>
                                    <i class="bi <?php 
                                        echo $check['status'] === 'ok' ? 'bi-check-circle status-ok' : 
                                             ($check['status'] === 'warning' ? 'bi-exclamation-triangle status-warning' : 'bi-x-circle status-error'); 
                                    ?>"></i>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Permissions Status -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-warning">
                                <i class="bi bi-shield-lock me-2"></i>Permissions
                            </div>
                            <div class="card-body">
                                <?php foreach ($systemStatus['permissions'] as $key => $check): ?>
                                <div class="check-item">
                                    <span><?= htmlspecialchars($check['message']) ?></span>
                                    <i class="bi <?php 
                                        echo $check['status'] === 'ok' ? 'bi-check-circle status-ok' : 
                                             ($check['status'] === 'warning' ? 'bi-exclamation-triangle status-warning' : 'bi-x-circle status-error'); 
                                    ?>"></i>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Security Status -->
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header bg-danger text-white">
                                <i class="bi bi-shield-check me-2"></i>Security
                            </div>
                            <div class="card-body">
                                <?php foreach ($systemStatus['security'] as $key => $check): ?>
                                <div class="check-item">
                                    <span><?= htmlspecialchars($check['message']) ?></span>
                                    <i class="bi <?php 
                                        echo $check['status'] === 'ok' ? 'bi-check-circle status-ok' : 
                                             ($check['status'] === 'warning' ? 'bi-exclamation-triangle status-warning' : 'bi-x-circle status-error'); 
                                    ?>"></i>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="text-center mt-4">
                    <button class="btn btn-primary me-2" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-2"></i>Ricontrolla
                    </button>
                    
                    <?php if ($systemStatus['overall'] === 'ok'): ?>
                    <a href="login.php" class="btn btn-success">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Vai al Sistema
                    </a>
                    <?php else: ?>
                    <a href="setup.php" class="btn btn-warning">
                        <i class="bi bi-tools me-2"></i>Setup System
                    </a>
                    <?php endif; ?>
                    
                    <button class="btn btn-outline-secondary ms-2" onclick="downloadReport()">
                        <i class="bi bi-download me-2"></i>Download Report
                    </button>
                </div>
                
                <div class="text-center mt-3">
                    <small class="text-muted">
                        Last check: <?= date('d/m/Y H:i:s') ?>
                        | System Version: <?= SETUP_VERSION ?? '1.0' ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script>
        function downloadReport() {
            window.open('status.php?format=json', '_blank');
        }
        
        // Auto refresh every 30 seconds if there are errors
        <?php if ($systemStatus['overall'] !== 'ok'): ?>
        setTimeout(() => {
            if (confirm('Ricontrollare lo stato del sistema?')) {
                location.reload();
            }
        }, 30000);
        <?php endif; ?>
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>