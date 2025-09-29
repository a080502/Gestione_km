<?php
/**
 * Setup Wizard - Sistema di Gestione Chilometri
 * Configurazione guidata per l'installazione del sistema
 */

// Disabilita il timeout per operazioni lunghe
set_time_limit(300);

// Definizione costanti di setup
define('SETUP_VERSION', '1.0');
define('PHP_MIN_VERSION', '8.0');
define('MYSQL_MIN_VERSION', '8.0');

// Inizializzazione sessione
session_start();

// Funzione per controllare i prerequisiti
function checkPrerequisites() {
    $checks = [];
    
    // PHP Version
    $phpVersion = PHP_VERSION;
    $checks['php_version'] = [
        'name' => 'Versione PHP',
        'required' => PHP_MIN_VERSION . '+',
        'current' => $phpVersion,
        'status' => version_compare($phpVersion, PHP_MIN_VERSION, '>='),
        'message' => version_compare($phpVersion, PHP_MIN_VERSION, '>=') ? 'OK' : 'Aggiorna PHP alla versione ' . PHP_MIN_VERSION . ' o superiore'
    ];
    
    // Estensioni PHP
    $extensions = ['mysqli', 'session', 'json', 'mbstring', 'fileinfo', 'gd'];
    foreach ($extensions as $ext) {
        $checks['ext_' . $ext] = [
            'name' => "Estensione PHP: $ext",
            'required' => 'Abilitata',
            'current' => extension_loaded($ext) ? 'Abilitata' : 'Disabilitata',
            'status' => extension_loaded($ext),
            'message' => extension_loaded($ext) ? 'OK' : "Abilita l'estensione $ext"
        ];
    }
    
    // Permessi cartelle
    $directories = [
        'uploads' => 'uploads/',
        'uploads_cedolini' => 'uploads/cedolini/',
        'config' => './'
    ];
    
    foreach ($directories as $key => $dir) {
        $exists = is_dir($dir);
        $writable = $exists ? is_writable($dir) : false;
        
        if (!$exists && $key !== 'config') {
            @mkdir($dir, 0755, true);
            $exists = is_dir($dir);
            $writable = $exists ? is_writable($dir) : false;
        }
        
        $checks['perm_' . $key] = [
            'name' => "Permessi cartella: $dir",
            'required' => 'Scrivibile',
            'current' => $writable ? 'Scrivibile' : ($exists ? 'Solo lettura' : 'Non esistente'),
            'status' => $writable,
            'message' => $writable ? 'OK' : ($exists ? "chmod 755 $dir" : "mkdir $dir && chmod 755 $dir")
        ];
    }
    
    return $checks;
}

// Funzione per testare la connessione al database
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

// Funzione per creare il database
function createDatabase($host, $username, $password, $database) {
    try {
        $conn = new mysqli($host, $username, $password);
        
        if ($conn->connect_error) {
            return ['success' => false, 'error' => $conn->connect_error];
        }
        
        $sql = "CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
        
        if ($conn->query($sql)) {
            $conn->close();
            return ['success' => true, 'message' => "Database '$database' creato con successo"];
        } else {
            $error = $conn->error;
            $conn->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Funzione per importare il schema del database
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
        
        // PULIZIA ROBUSTA DEL FILE SQL
        // 1. Rimuovi tutti i commenti MySQL specifici /*!...*/
        $sqlContent = preg_replace('/\/\*!\d+.*?\*\/;?/s', '', $sqlContent);
        
        // 2. Rimuovi commenti multilinea standard /*...*/
        $sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent);
        
        // 3. Processa linea per linea per una pulizia più precisa
        $lines = explode("\n", $sqlContent);
        $cleanedLines = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Salta righe vuote
            if (empty($line)) continue;
            
            // Salta commenti che iniziano con --
            if (strpos($line, '--') === 0) continue;
            
            // Salta comandi specifici che causano problemi
            if (stripos($line, 'CREATE DATABASE') !== false) continue;
            if (stripos($line, 'USE `') !== false) continue;
            if (stripos($line, 'USE ') === 0) continue;
            if (stripos($line, 'START TRANSACTION') !== false) continue;
            if (stripos($line, 'SET SQL_MODE') !== false) continue;
            if (stripos($line, 'SET time_zone') !== false) continue;
            if (stripos($line, 'SET @OLD_') !== false) continue;
            if (stripos($line, 'SET NAMES') !== false) continue;
            
            // Salta righe che contengono solo punti e virgola
            if ($line === ';') continue;
            
            $cleanedLines[] = $line;
        }
        
        // 4. Ricomponi il SQL e fai una pulizia finale
        $cleanedSQL = implode("\n", $cleanedLines);
        
        // 5. Rimuovi punti e virgola multipli consecutivi
        $cleanedSQL = preg_replace('/;+/', ';', $cleanedSQL);
        
        // 6. Rimuovi spazi e newline in eccesso
        $cleanedSQL = preg_replace('/\s+/', ' ', $cleanedSQL);
        $cleanedSQL = str_replace('; ', ";\n", $cleanedSQL);
        
        // 7. Dividi le query per punto e virgola e pulisci ulteriormente
        $queries = explode(';', $cleanedSQL);
        
        $executedQueries = 0;
        $errors = [];
        $debugQueries = [];
        
        foreach ($queries as $index => $query) {
            $query = trim($query);
            
            // Salta query vuote o che contengono solo spazi/newline
            if (empty($query) || strlen($query) < 5) continue;
            
            // Debug: salva le prime 5 query per il log
            if (count($debugQueries) < 5) {
                $debugQueries[] = substr($query, 0, 100) . (strlen($query) > 100 ? '...' : '');
            }
            
            // Esegui la query
            if (!$conn->query($query)) {
                $errors[] = "Query " . ($index + 1) . " fallita: " . substr($query, 0, 50) . "... -> " . $conn->error;
                
                // Se abbiamo troppi errori, fermati
                if (count($errors) >= 5) {
                    break;
                }
            } else {
                $executedQueries++;
            }
        }
        
        $conn->close();
        
        if (!empty($errors)) {
            return [
                'success' => false, 
                'error' => 'Errori durante importazione: ' . implode(' | ', $errors),
                'executed_queries' => $executedQueries,
                'debug_queries' => $debugQueries
            ];
        }
        
        return [
            'success' => true, 
            'message' => "Schema importato con successo! Eseguite $executedQueries query.",
            'executed_queries' => $executedQueries,
            'debug_queries' => $debugQueries
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false, 
            'error' => 'Eccezione durante importazione: ' . $e->getMessage(),
            'debug_info' => 'File: ' . $e->getFile() . ', Linea: ' . $e->getLine()
        ];
    }
}

// Funzione per creare il file di configurazione
function createConfigFile($host, $username, $password, $database) {
    $configContent = "<?php\n";
    $configContent .= "// Configurazione Database - Generata automaticamente dal Setup\n";
    $configContent .= "// Data: " . date('Y-m-d H:i:s') . "\n\n";
    $configContent .= "return [\n";
    $configContent .= "    'DB_HOST' => '" . addslashes($host) . "',\n";
    $configContent .= "    'DB_USERNAME' => '" . addslashes($username) . "',\n";
    $configContent .= "    'DB_PASSWORD' => '" . addslashes($password) . "',\n";
    $configContent .= "    'DB_NAME' => '" . addslashes($database) . "'\n";
    $configContent .= "];\n";
    
    $configFile = __DIR__ . '/editable_config.php';
    
    if (file_put_contents($configFile, $configContent)) {
        chmod($configFile, 0644);
        return ['success' => true, 'message' => 'File di configurazione creato: editable_config.php'];
    } else {
        return ['success' => false, 'error' => 'Impossibile creare il file editable_config.php'];
    }
}

// Funzione per creare l'utente amministratore
function createAdminUser($host, $username, $password, $database, $adminUser, $adminPass, $adminName, $adminSurname) {
    try {
        $conn = new mysqli($host, $username, $password, $database);
        
        if ($conn->connect_error) {
            return ['success' => false, 'error' => $conn->connect_error];
        }
        
        // Hash della password
        $hashedPassword = password_hash($adminPass, PASSWORD_BCRYPT);
        
        // Inserisci l'utente amministratore
        $sql = $conn->prepare("INSERT INTO utenti (username, password, targa_mezzo, divisione, filiale, livello, Nome, Cognome) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $targa = '*';
        $divisione = '';
        $filiale = '';
        $livello = '1';
        
        $sql->bind_param("ssssssss", $adminUser, $hashedPassword, $targa, $divisione, $filiale, $livello, $adminName, $adminSurname);
        
        if ($sql->execute()) {
            $sql->close();
            $conn->close();
            return ['success' => true, 'message' => "Utente amministratore '$adminUser' creato con successo"];
        } else {
            $error = $sql->error;
            $sql->close();
            $conn->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Gestione delle richieste POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'test_db':
            $result = testDatabaseConnection($_POST['host'], $_POST['username'], $_POST['password'], $_POST['database'] ?? null);
            echo json_encode($result);
            exit;
            
        case 'create_db':
            $result = createDatabase($_POST['host'], $_POST['username'], $_POST['password'], $_POST['database']);
            echo json_encode($result);
            exit;
            
        case 'import_schema':
            $result = importDatabaseSchema($_POST['host'], $_POST['username'], $_POST['password'], $_POST['database']);
            echo json_encode($result);
            exit;
            
        case 'create_config':
            $result = createConfigFile($_POST['host'], $_POST['username'], $_POST['password'], $_POST['database']);
            echo json_encode($result);
            exit;
            
        case 'create_admin':
            $result = createAdminUser(
                $_POST['host'], $_POST['username'], $_POST['password'], $_POST['database'],
                $_POST['admin_user'], $_POST['admin_pass'], $_POST['admin_name'], $_POST['admin_surname']
            );
            echo json_encode($result);
            exit;
    }
}

// Controllo prerequisiti
$prerequisites = checkPrerequisites();
$prereqPassed = true;
foreach ($prerequisites as $check) {
    if (!$check['status']) {
        $prereqPassed = false;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Sistema di Gestione Chilometri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        .step-indicator {
            background: #e9ecef;
            height: 4px;
            border-radius: 2px;
            overflow: hidden;
        }
        .step-progress {
            height: 100%;
            background: linear-gradient(90deg, #0d6efd, #198754);
            transition: width 0.3s ease;
        }
        .check-item {
            padding: 0.75rem;
            margin: 0.25rem 0;
            border-radius: 0.5rem;
            border: 1px solid #dee2e6;
        }
        .check-item.success {
            background-color: #d1e7dd;
            border-color: #badbcc;
        }
        .check-item.error {
            background-color: #f8d7da;
            border-color: #f5c2c7;
        }
        .result-message {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0.5rem;
            display: none;
        }
        .result-message.success {
            background-color: #d1e7dd;
            border: 1px solid #badbcc;
            color: #0f5132;
        }
        .result-message.error {
            background-color: #f8d7da;
            border: 1px solid #f5c2c7;
            color: #842029;
        }
        .loading {
            display: none;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h1 class="h3 mb-0">
                            <i class="bi bi-gear-fill me-2"></i>
                            Setup - Sistema di Gestione Chilometri
                        </h1>
                        <p class="mb-0 mt-2">Versione <?= SETUP_VERSION ?> - Configurazione guidata</p>
                    </div>
                    
                    <div class="card-body">
                        <!-- Progress Bar -->
                        <div class="step-indicator mb-4">
                            <div class="step-progress" style="width: 20%"></div>
                        </div>
                        
                        <!-- Step 1: Prerequisiti -->
                        <div class="step active" id="step-1">
                            <h4><i class="bi bi-clipboard-check text-primary me-2"></i>Step 1: Controllo Prerequisiti</h4>
                            <p class="text-muted">Verifica dei requisiti di sistema necessari per l'installazione.</p>
                            
                            <div class="prerequisites-list">
                                <?php foreach ($prerequisites as $key => $check): ?>
                                <div class="check-item <?= $check['status'] ? 'success' : 'error' ?>">
                                    <div class="d-flex align-items-center">
                                        <i class="bi <?= $check['status'] ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?> me-3"></i>
                                        <div class="flex-grow-1">
                                            <strong><?= $check['name'] ?></strong>
                                            <div class="small text-muted">
                                                Richiesto: <?= $check['required'] ?> | 
                                                Attuale: <?= $check['current'] ?>
                                            </div>
                                        </div>
                                        <span class="badge <?= $check['status'] ? 'bg-success' : 'bg-danger' ?>">
                                            <?= $check['message'] ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mt-4">
                                <?php if ($prereqPassed): ?>
                                <button class="btn btn-primary" onclick="safeNextStep()">
                                    <i class="bi bi-arrow-right me-2"></i>Continua
                                </button>
                                <small class="d-block mt-2 text-muted">
                                    Debug: <button class="btn btn-sm btn-outline-secondary" onclick="console.log('nextStep disponibile:', typeof nextStep, 'safeNextStep:', typeof safeNextStep)">Test JS</button>
                                </small>
                                <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>Attenzione!</strong> Alcuni prerequisiti non sono soddisfatti. 
                                    Risolvi i problemi evidenziati prima di continuare.
                                </div>
                                <button class="btn btn-outline-primary" onclick="location.reload()">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Ricontrolla
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Step 2: Configurazione Database -->
                        <div class="step" id="step-2">
                            <h4><i class="bi bi-database text-primary me-2"></i>Step 2: Configurazione Database</h4>
                            <p class="text-muted">Inserisci i parametri di connessione al database MySQL/MariaDB.</p>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Nota:</strong> Il database può non esistere ancora. L'installer verificherà prima la connessione al server MySQL 
                                e, se necessario, creerà automaticamente il database durante il processo di installazione.
                            </div>
                            
                            <form id="db-form">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Host Database</label>
                                            <input type="text" class="form-control" name="host" value="localhost" required>
                                            <div class="form-text">Di solito 'localhost' o IP del server MySQL</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nome Database</label>
                                            <input type="text" class="form-control" name="database" value="chilometri" required>
                                            <div class="form-text">Nome del database da creare/utilizzare</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" name="username" required>
                                            <div class="form-text">Utente con privilegi di creazione database</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Password</label>
                                            <input type="password" class="form-control" name="password">
                                            <div class="form-text">Password dell'utente MySQL</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <button type="button" class="btn btn-outline-primary" onclick="testDatabase()">
                                        <i class="bi bi-wifi me-2"></i>Testa Connessione
                                    </button>
                                </div>
                            </form>
                            
                            <div id="db-result" class="result-message"></div>
                            
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-outline-secondary" onclick="prevStep()">
                                    <i class="bi bi-arrow-left me-2"></i>Indietro
                                </button>
                                <button class="btn btn-primary" onclick="createDatabase()" disabled id="next-step-2">
                                    <i class="bi bi-arrow-right me-2"></i>Crea Database e Continua
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 3: Importazione Schema -->
                        <div class="step" id="step-3">
                            <h4><i class="bi bi-table text-primary me-2"></i>Step 3: Importazione Schema</h4>
                            <p class="text-muted">Importazione delle tabelle e struttura del database.</p>
                            
                            <div id="schema-progress">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="loading spinner-border spinner-border-sm me-3" role="status"></div>
                                    <span>Importazione schema in corso...</span>
                                </div>
                            </div>
                            
                            <div id="schema-result" class="result-message"></div>
                            
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-outline-secondary" onclick="prevStep()">
                                    <i class="bi bi-arrow-left me-2"></i>Indietro
                                </button>
                                <button class="btn btn-primary" onclick="nextStep()" disabled id="next-step-3">
                                    <i class="bi bi-arrow-right me-2"></i>Continua
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 4: Configurazione Admin -->
                        <div class="step" id="step-4">
                            <h4><i class="bi bi-person-gear text-primary me-2"></i>Step 4: Utente Amministratore</h4>
                            <p class="text-muted">Crea il primo utente amministratore del sistema.</p>
                            
                            <form id="admin-form">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" name="admin_user" value="admin" required>
                                            <div class="form-text">Username per l'accesso amministratore</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Password</label>
                                            <input type="password" class="form-control" name="admin_pass" required minlength="6">
                                            <div class="form-text">Minimo 6 caratteri</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nome</label>
                                            <input type="text" class="form-control" name="admin_name" value="Amministratore" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Cognome</label>
                                            <input type="text" class="form-control" name="admin_surname" value="Sistema" required>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            
                            <div id="admin-result" class="result-message"></div>
                            
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-outline-secondary" onclick="prevStep()">
                                    <i class="bi bi-arrow-left me-2"></i>Indietro
                                </button>
                                <button class="btn btn-primary" onclick="createAdmin()">
                                    <i class="bi bi-person-plus me-2"></i>Crea Amministratore
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 5: Completamento -->
                        <div class="step" id="step-5">
                            <div class="text-center">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                                <h4 class="mt-3">Installazione Completata!</h4>
                                <p class="text-muted">Il Sistema di Gestione Chilometri è stato configurato correttamente.</p>
                                
                                <div class="card bg-light mt-4">
                                    <div class="card-body">
                                        <h5>Prossimi Passi:</h5>
                                        <ol class="text-start">
                                            <li><strong>Elimina questo file:</strong> Rimuovi <code>setup.php</code> per sicurezza</li>
                                            <li><strong>Configura filiali:</strong> Aggiungi le divisioni e filiali aziendali</li>
                                            <li><strong>Crea utenti:</strong> Aggiungi gli utenti del sistema</li>
                                            <li><strong>Imposta target:</strong> Definisci gli obiettivi chilometrici</li>
                                            <li><strong>Backup:</strong> Configura i backup automatici</li>
                                        </ol>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <a href="login.php" class="btn btn-success btn-lg">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>Vai al Login
                                    </a>
                                    <button class="btn btn-danger ms-2" onclick="deleteSetup()">
                                        <i class="bi bi-trash me-2"></i>Elimina Setup
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Setup JavaScript - Versione pulita
        console.log('Setup JavaScript caricato');

        let currentStep = 1;
        let dbConfig = {};

        // Funzione sicura per andare al prossimo step
        function safeNextStep() {
            console.log('safeNextStep chiamata');
            console.log('Tipo di nextStep:', typeof nextStep);
            
            if (typeof nextStep === 'function') {
                nextStep();
            } else {
                console.error('nextStep non è ancora disponibile!');
                alert('Errore: Il sistema non è ancora pronto. Ricarica la pagina e riprova.');
                return false;
            }
        }

        function nextStep() {
            console.log('nextStep chiamata, currentStep:', currentStep);
            const currentStepEl = document.getElementById(`step-${currentStep}`);
            currentStep++;
            const nextStepEl = document.getElementById(`step-${currentStep}`);
            
            console.log('Transizione da step-' + (currentStep-1) + ' a step-' + currentStep);
            
            if (nextStepEl) {
                if (currentStepEl) currentStepEl.classList.remove('active');
                nextStepEl.classList.add('active');
                updateProgress();
                console.log('Step cambiato con successo');
            } else {
                console.error('Elemento step-' + currentStep + ' non trovato!');
            }
        }

        function prevStep() {
            console.log('prevStep chiamata, currentStep:', currentStep);
            const currentStepEl = document.getElementById(`step-${currentStep}`);
            currentStep--;
            const prevStepEl = document.getElementById(`step-${currentStep}`);
            
            if (prevStepEl) {
                if (currentStepEl) currentStepEl.classList.remove('active');
                prevStepEl.classList.add('active');
                updateProgress();
            }
        }

        function updateProgress() {
            const progress = (currentStep / 5) * 100;
            const progressBar = document.querySelector('.step-progress');
            if (progressBar) {
                progressBar.style.width = `${progress}%`;
                console.log('Progress aggiornato:', progress + '%');
            }
        }

        function showResult(elementId, success, message) {
            const element = document.getElementById(elementId);
            element.className = `result-message ${success ? 'success' : 'error'}`;
            element.innerHTML = `<i class="bi ${success ? 'bi-check-circle' : 'bi-x-circle'} me-2"></i>${message}`;
            element.style.display = 'block';
        }

        function testDatabase() {
            const form = document.getElementById('db-form');
            const formData = new FormData(form);
            formData.append('action', 'test_db');
            
            fetch('setup.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.server_connection) {
                    let message = data.message || `Connessione riuscita! MySQL ${data.version}`;
                    
                    if (data.database_exists === false) {
                        message += ` <br><small class="text-info"><i class="bi bi-info-circle"></i> Il database sarà creato automaticamente durante l'installazione.</small>`;
                    }
                    
                    showResult('db-result', true, message);
                    document.getElementById('next-step-2').disabled = false;
                    
                    const form = document.getElementById('db-form');
                    dbConfig = {
                        host: form.host.value,
                        username: form.username.value,
                        password: form.password.value,
                        database: form.database.value
                    };
                } else {
                    let errorMessage = `Errore di connessione: ${data.error}`;
                    if (!data.server_connection) {
                        errorMessage += '<br><small class="text-muted">Verifica che il server MySQL sia in esecuzione e che le credenziali siano corrette.</small>';
                    }
                    
                    showResult('db-result', false, errorMessage);
                    document.getElementById('next-step-2').disabled = true;
                }
            })
            .catch(error => {
                showResult('db-result', false, `Errore: ${error}<br><small class="text-muted">Problema di comunicazione con il server.</small>`);
                document.getElementById('next-step-2').disabled = true;
            });
        }

        function createDatabase() {
            const formData = new FormData();
            Object.keys(dbConfig).forEach(key => {
                formData.append(key, dbConfig[key]);
            });
            formData.append('action', 'create_db');
            
            fetch('setup.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showResult('db-result', true, data.message);
                    setTimeout(() => {
                        nextStep();
                        importSchema();
                    }, 1000);
                } else {
                    showResult('db-result', false, `Errore: ${data.error}`);
                }
            })
            .catch(error => {
                showResult('db-result', false, `Errore: ${error}`);
            });
        }

        function importSchema() {
            document.querySelector('#schema-progress .loading').style.display = 'inline-block';
            
            const formData = new FormData();
            Object.keys(dbConfig).forEach(key => {
                formData.append(key, dbConfig[key]);
            });
            formData.append('action', 'import_schema');
            
            fetch('setup.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.querySelector('#schema-progress .loading').style.display = 'none';
                
                if (data.success) {
                    let message = data.message;
                    if (data.debug_queries && data.debug_queries.length > 0) {
                        message += '<br><small class="text-muted">Prime query eseguite: ' + data.debug_queries.slice(0,2).join('; ') + '</small>';
                    }
                    showResult('schema-result', true, message);
                    document.getElementById('next-step-3').disabled = false;
                    createConfigFile();
                } else {
                    let errorMessage = `Errore importazione schema: ${data.error}`;
                    if (data.debug_queries) {
                        errorMessage += '<br><small class="text-info">Query di test: ' + data.debug_queries.slice(0,2).join('; ') + '</small>';
                    }
                    if (data.executed_queries !== undefined) {
                        errorMessage += `<br><small class="text-muted">Query eseguite con successo: ${data.executed_queries}</small>`;
                    }
                    showResult('schema-result', false, errorMessage);
                }
            })
            .catch(error => {
                document.querySelector('#schema-progress .loading').style.display = 'none';
                showResult('schema-result', false, `Errore di comunicazione: ${error}`);
            });
        }

        function createConfigFile() {
            const formData = new FormData();
            Object.keys(dbConfig).forEach(key => {
                formData.append(key, dbConfig[key]);
            });
            formData.append('action', 'create_config');
            
            fetch('setup.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Config file creato con successo');
                    nextStep();
                } else {
                    showResult('schema-result', false, `Errore creazione config: ${data.error}`);
                }
            })
            .catch(error => {
                showResult('schema-result', false, `Errore: ${error}`);
            });
        }

        function createAdmin() {
            const form = document.getElementById('admin-form');
            const formData = new FormData(form);
            Object.keys(dbConfig).forEach(key => {
                formData.append(key, dbConfig[key]);
            });
            formData.append('action', 'create_admin');
            
            fetch('setup.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showResult('admin-result', true, data.message);
                    setTimeout(() => {
                        nextStep();
                    }, 1500);
                } else {
                    showResult('admin-result', false, `Errore: ${data.error}`);
                }
            })
            .catch(error => {
                showResult('admin-result', false, `Errore: ${error}`);
            });
        }

        function deleteSetup() {
            if (confirm('Sei sicuro di voler eliminare il file setup.php?')) {
                alert('Ricorda di eliminare manualmente il file setup.php dal server!');
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM caricato, inizializzando...');
            updateProgress();
            
            const step1 = document.getElementById('step-1');
            const step2 = document.getElementById('step-2');
            
            if (!step1) console.error('Elemento step-1 non trovato!');
            if (!step2) console.error('Elemento step-2 non trovato!');
            
            console.log('Funzioni disponibili:');
            console.log('- nextStep:', typeof nextStep);
            console.log('- safeNextStep:', typeof safeNextStep);
            console.log('- prevStep:', typeof prevStep);
            
            console.log('Setup inizializzato correttamente');
        });

        // Rendi le funzioni globali per sicurezza
        window.nextStep = nextStep;
        window.safeNextStep = safeNextStep;
        window.prevStep = prevStep;
        window.testDatabase = testDatabase;
        window.createDatabase = createDatabase;
        window.createAdmin = createAdmin;
        window.deleteSetup = deleteSetup;
    </script>
</body>
</html>