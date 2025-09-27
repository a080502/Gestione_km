<?php
session_start();

// --- Logica per Testare la Connessione DB (AJAX) ---
if (isset($_POST['action']) && $_POST['action'] === 'test_db_connection') {
    header('Content-Type: application/json'); // Imposta l'header per la risposta JSON

    $db_host = isset($_POST['db_host']) ? trim($_POST['db_host']) : '';
    $db_username = isset($_POST['db_username']) ? trim($_POST['db_username']) : '';
    $db_password = isset($_POST['db_password']) ? trim($_POST['db_password']) : ''; // La password può essere vuota
    $db_name = isset($_POST['db_name']) ? trim($_POST['db_name']) : '';

    $response = ['success' => false, 'message' => 'Dati di connessione incompleti.'];

    if (!empty($db_host) && !empty($db_username) && !empty($db_name)) {
        // Tentativo di connessione
        // @ sopprime gli errori di connessione standard per gestirli manualmente
        $conn_test = @new mysqli($db_host, $db_username, $db_password, $db_name);

        if ($conn_test->connect_error) {
            $response['message'] = "Connessione fallita: " . htmlspecialchars($conn_test->connect_error);
        } else {
            $response['success'] = true;
            $response['message'] = "Connessione al database riuscita!";
            $conn_test->close();
        }
    }
    echo json_encode($response);
    exit(); // Termina lo script dopo aver inviato la risposta JSON
}

// Gestione operazioni sui backup del logo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    if ($_POST['action'] === 'restore_backup') {
        $backupFile = $_POST['backup_file'] ?? '';
        $backupPath = 'immagini/' . $backupFile;
        $currentLogoPath = 'immagini/logo.png';
        
        // Validazione nome file backup per sicurezza
        if (preg_match('/^logo_hold\d+\.png$/', $backupFile) && file_exists($backupPath)) {
            // Crea backup del logo attuale se esiste
            if (file_exists($currentLogoPath)) {
                $holdCounter = 1;
                $newHoldPath = 'immagini/logo_hold' . $holdCounter . '.png';
                
                while (file_exists($newHoldPath)) {
                    $holdCounter++;
                    $newHoldPath = 'immagini/logo_hold' . $holdCounter . '.png';
                }
                
                if (rename($currentLogoPath, $newHoldPath)) {
                    $response['message'] = "Logo attuale salvato come backup. ";
                }
            }
            
            // Ripristina il backup
            if (copy($backupPath, $currentLogoPath)) {
                $response['success'] = true;
                $response['message'] .= "Backup ripristinato con successo!";
            } else {
                $response['message'] = "Errore durante il ripristino del backup.";
            }
        } else {
            $response['message'] = "File di backup non valido o inesistente.";
        }
        
        echo json_encode($response);
        exit();
    }
    
    if ($_POST['action'] === 'delete_backup') {
        $backupFile = $_POST['backup_file'] ?? '';
        $backupPath = 'immagini/' . $backupFile;
        
        // Validazione nome file backup per sicurezza
        if (preg_match('/^logo_hold\d+\.png$/', $backupFile) && file_exists($backupPath)) {
            if (unlink($backupPath)) {
                $response['success'] = true;
                $response['message'] = "Backup eliminato con successo!";
            } else {
                $response['message'] = "Errore durante l'eliminazione del backup.";
            }
        } else {
            $response['message'] = "File di backup non valido o inesistente.";
        }
        
        echo json_encode($response);
        exit();
    }
}


// --- Controllo di Sicurezza ---
if (!isset($_SESSION['username'])) {
    header("Location: login.php?error=unauthorized");
    exit();
}
// include 'query/qutenti.php'; // Assicurarsi che il percorso sia corretto
$username_loggato = $_SESSION['username'];


// --- Gestione File di Configurazione ---
$configFile = 'editable_config.php';
$defaultConfig = [
    'DB_HOST' => 'localhost',
    'DB_USERNAME' => 'root',
    'DB_PASSWORD' => '',
    'DB_NAME' => 'mydatabase',
    'SITE_TITLE' => 'Il Mio Sito Web',
    'ITEMS_PER_PAGE' => 10,
    'COMPANY_LOGO' => 'immagini/logo.png',
    'COMPANY_NAME' => 'La Tua Azienda'
];
$config = [];
if (file_exists($configFile)) {
    $loadedConfig = include $configFile;
    if (is_array($loadedConfig)) {
        $config = $loadedConfig;
    }
}
$config = array_merge($defaultConfig, $config);

$message = ''; 
$message_type = ''; 
$perform_delayed_redirect = false; // Flag per il redirect ritardato

// --- Gestione Invio Modulo (richiesta POST per SALVARE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && (!isset($_POST['action']) || $_POST['action'] !== 'test_db_connection')) {
    
    // Gestione upload immagine logo
    $logoPath = $config['COMPANY_LOGO'] ?? 'immagini/logo.png';
    $logoMessage = '';
    
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'immagini/';
        
        // Crea la cartella se non exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        $fileType = $_FILES['company_logo']['type'];
        $fileExt = strtolower(pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION));
        $fileSize = $_FILES['company_logo']['size'];
        
        if (!in_array($fileType, $allowedTypes) || !in_array($fileExt, $allowedExtensions)) {
            $logoMessage = "Formato immagine non supportato. Usa JPG, PNG, GIF o WebP.";
            $message_type = 'error';
        } elseif ($fileSize > $maxSize) {
            $logoMessage = "File troppo grande. Dimensione massima: 2MB.";
            $message_type = 'error';
        } else {
            // Il nuovo logo si chiamerà sempre logo.png
            $newFilePath = $uploadDir . 'logo.png';
            
            // Se esiste già un logo.png, creane un backup incrementale
            if (file_exists($newFilePath)) {
                $backupCounter = 1;
                $backupPath = $uploadDir . 'logo_hold' . $backupCounter . '.png';
                
                // Trova il primo numero disponibile per il backup
                while (file_exists($backupPath)) {
                    $backupCounter++;
                    $backupPath = $uploadDir . 'logo_hold' . $backupCounter . '.png';
                }
                
                // Crea il backup del logo esistente
                if (rename($newFilePath, $backupPath)) {
                    $logoMessage = "Logo precedente salvato come backup: logo_hold{$backupCounter}.png. ";
                } else {
                    $logoMessage = "Attenzione: impossibile creare backup del logo esistente. ";
                }
            }
            
            // Salva il nuovo logo come logo.png
            if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $newFilePath)) {
                $logoPath = $newFilePath;
                $logoMessage .= "Nuovo logo caricato con successo come logo.png!";
            } else {
                $logoMessage = "Errore durante il caricamento dell'immagine.";
                $message_type = 'error';
            }
        }
    }
    
    $newConfig = [
        'DB_HOST' => isset($_POST['db_host']) ? htmlspecialchars(trim($_POST['db_host'])) : $config['DB_HOST'],
        'DB_USERNAME' => isset($_POST['db_username']) ? htmlspecialchars(trim($_POST['db_username'])) : $config['DB_USERNAME'],
        'DB_PASSWORD' => (isset($_POST['db_password_provided']) && $_POST['db_password_provided'] === '1' && !empty(trim($_POST['db_password']))) ? trim($_POST['db_password']) : $config['DB_PASSWORD'],
        'DB_NAME' => isset($_POST['db_name']) ? htmlspecialchars(trim($_POST['db_name'])) : $config['DB_NAME'],
        'SITE_TITLE' => isset($_POST['site_title']) ? htmlspecialchars(trim($_POST['site_title'])) : $config['SITE_TITLE'],
        'ITEMS_PER_PAGE' => isset($_POST['items_per_page']) ? filter_var(trim($_POST['items_per_page']), FILTER_VALIDATE_INT) : $config['ITEMS_PER_PAGE'],
        'COMPANY_LOGO' => $logoPath,
        'COMPANY_NAME' => isset($_POST['company_name']) ? htmlspecialchars(trim($_POST['company_name'])) : ($config['COMPANY_NAME'] ?? 'La Tua Azienda'),
    ];
    
    if ($newConfig['ITEMS_PER_PAGE'] === false || $newConfig['ITEMS_PER_PAGE'] < 1) {
        $newConfig['ITEMS_PER_PAGE'] = $config['ITEMS_PER_PAGE'];
    }

    $phpCode = "<?php\n// File di configurazione generato automaticamente.\n\nreturn " . var_export($newConfig, true) . ";\n";

    if (file_put_contents($configFile, $phpCode) !== false) {
        $finalMessage = "Configurazione aggiornata con successo!";
        if (!empty($logoMessage)) {
            $finalMessage .= " " . $logoMessage;
        }
        $finalMessage .= " Sarai reindirizzato alla pagina index tra 4 secondi.";
        
        $message = $finalMessage;
        $message_type = 'success';
        $config = $newConfig; 
        
        $_SESSION['flash_message'] = "Configurazione aggiornata con successo.";
        $_SESSION['flash_message_type'] = 'success';
        
        $perform_delayed_redirect = true; 

    } else {
        $message = "Errore nel salvataggio della configurazione. Controlla i permessi del file '$configFile'.";
        if (!empty($logoMessage) && $message_type !== 'error') {
            $message .= " " . $logoMessage;
        }
        $message_type = 'error';
    }
}

if (isset($_SESSION['flash_message']) && !$perform_delayed_redirect) { 
    $message = $_SESSION['flash_message'];
    $message_type = isset($_SESSION['flash_message_type']) ? $_SESSION['flash_message_type'] : 'info';
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_message_type']);
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Dati Server</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f8f9fa; /* Bootstrap background color */
            color: #212529; /* Bootstrap default text color */
            padding-top: 70px; /* Altezza dell'header fisso + un po' di spazio */
            margin: 0; /* Rimuovi margini di default */
        }

        /* Contenitore per il bottone menu e username fissi */
        .fixed-top-elements {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background-color: #e9ecef; /* Un grigio chiaro per l'header */
            padding: 10px 15px;
            z-index: 1031; /* Sopra l'offcanvas (1030 di default per offcanvas backdrop) */
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 1px solid #dee2e6; 
        }

        .menu-btn {
            font-size: 1.2rem; 
        }

        .username-display {
            font-size: 0.9rem;
            color: #495057;
            background-color: #fff;
            padding: 0.3rem 0.6rem;
            border-radius: 0.2rem;
            border: 1px solid #ced4da;
        }
        
        /* Stili per il container principale della pagina */
        .page-container {
            padding: 20px; /* Spazio interno */
        }

        /* Adattamenti per fieldset e legend con Bootstrap */
        fieldset { 
            border: 1px solid #dee2e6; /* Bordo standard Bootstrap */
            border-radius: .375rem; /* Bootstrap .rounded */
            margin-bottom: 1.5rem; /* Bootstrap .mb-3 o .mb-4 */
            padding: 1.25rem; /* Bootstrap .p-3 */
        }
        legend { 
            font-weight: 600; /* Più vicino a .fw-semibold di Bootstrap */
            color: #495057; /* Colore testo secondario Bootstrap */
            padding: 0 0.5rem; 
            float: none; /* Reset float per Bootstrap 5 */
            width: auto; /* Necessario con float:none */
            font-size: 1.1rem; /* Leggermente più grande */
            margin-bottom: 0.75rem; /* Spazio sotto la legenda */
        }

        /* Messaggi di feedback (success/error/info) */
        /* Usiamo le classi alert di Bootstrap per coerenza */
        #db_test_result:empty { display: none; } 

        /* Avviso specifico */
        .warning-custom { /* Rinominato per evitare conflitti con .warning di Bootstrap */
            background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba;
            padding: 1rem; margin-bottom:1.5rem; border-radius: .375rem;
        }
        .warning-custom code {
             font-family: Consolas, Monaco, 'Andale Mono', 'Ubuntu Mono', monospace; 
             background-color: rgba(0,0,0,0.05);
             padding: 0.1rem 0.3rem;
             border-radius: 0.2rem;
        }
        
        /* Stili per la preview dell'immagine */
        .logo-preview-container {
            max-width: 200px;
            border: 2px dashed #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            text-align: center;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        .logo-preview-container:hover {
            border-color: #0d6efd;
            background-color: #e7f3ff;
        }
        .logo-preview-container img {
            max-width: 100%;
            max-height: 150px;
            object-fit: contain;
            border-radius: 0.25rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .logo-preview-placeholder {
            color: #6c757d;
            font-size: 0.9rem;
            padding: 2rem 1rem;
        }
        .file-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 0.375rem;
            padding: 1.5rem;
            text-align: center;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .file-upload-area:hover {
            border-color: #0d6efd;
            background-color: #e7f3ff;
        }
        .file-upload-area.dragover {
            border-color: #198754;
            background-color: #d1e7dd;
        }

        .backup-files-container {
            max-height: 300px;
            overflow-y: auto;
        }

        .backup-item {
            background-color: #f8f9fa;
        }

        .backup-thumb {
            width: 40px;
            height: 30px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        
        /* Stili per l'offcanvas menu */
        .offcanvas-header {
            border-bottom: 1px solid #dee2e6;
        }
        .offcanvas-body .nav-link {
            padding: 0.75rem 1.25rem;
            font-size: 1rem;
            color: #212529; 
        }
        .offcanvas-body .nav-link:hover, .offcanvas-body .nav-link.active {
            background-color: #e9ecef;
        }
         .offcanvas-body .nav-link .bi {
            margin-right: 0.5rem;
        }
        .offcanvas-body hr {
            margin: 1rem 0; 
        }
    </style>
</head>
<body>

    <div class="fixed-top-elements">
        <button class="btn btn-outline-secondary menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMenuOffcanvas" aria-controls="mainMenuOffcanvas">
            <i class="bi bi-list"></i> Menu
        </button>
        <div class="username-display">
            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($username_loggato); ?>
        </div>
    </div>

    <!-- Offcanvas Menu -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="mainMenuOffcanvas" aria-labelledby="mainMenuOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="mainMenuOffcanvasLabel"><i class="bi bi-gear-fill"></i> Menu Navigazione</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <nav class="nav flex-column">
                <a class="nav-link active" href="index.php"><i class="bi bi-house-door-fill"></i> Dashboard</a>
                <a class="nav-link" href="inserimento_chilometri.php"><i class="bi bi-fuel-pump-fill"></i> Inserisci Chilometri</a>
                <a class="nav-link" href="visualizza_dati.php"><i class="bi bi-table"></i> Visualizza Dati</a>
                <hr>
                <a class="nav-link" href="gestione_dati_server.php"><i class="bi bi-hdd-stack-fill"></i> Impostazioni Server</a>
                <a class="nav-link" href="profilo_utente.php"><i class="bi bi-person-badge-fill"></i> Profilo Utente</a>
                <hr>
                <a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
            <!-- Potresti voler aggiungere qui il contenuto del tuo 'include/menu.php' se è più complesso -->
        </div>
    </div>

    <div class="container page-container">
        <h1 class="mb-4">🔧 Gestione Dati Server</h1>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo ($message_type === 'success' ? 'alert-success' : ($message_type === 'error' ? 'alert-danger' : 'alert-info')); ?>" role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="alert alert-warning warning-custom" role="alert">
            <p class="mb-1"><strong><i class="bi bi-exclamation-triangle-fill"></i> Attenzione:</strong> La modifica di questi dati influisce direttamente sulla configurazione del server e della connessione al database. Procedere con estrema cautela.</p>
            <p class="mb-0">Il file di configurazione gestito da questa pagina è: <code><?php echo htmlspecialchars($configFile); ?></code>.</p>
        </div>
        
        <form id="configForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <fieldset>
                <legend>⚙️ Configurazione Database</legend>
                <div class="mb-3">
                    <label for="db_host" class="form-label">Host Database:</label>
                    <input type="text" class="form-control" id="db_host" name="db_host" value="<?php echo htmlspecialchars($config['DB_HOST']); ?>" required>
                    <div class="invalid-feedback">Inserire l'host del database.</div>
                </div>

                <div class="mb-3">
                    <label for="db_username" class="form-label">Username Database:</label>
                    <input type="text" class="form-control" id="db_username" name="db_username" value="<?php echo htmlspecialchars($config['DB_USERNAME']); ?>" required>
                    <div class="invalid-feedback">Inserire l'username del database.</div>
                </div>

                <div class="mb-3">
                    <label for="db_password" class="form-label">Password Database: <small class="text-muted">(lasciare vuoto per non modificare)</small></label>
                    <input type="password" class="form-control" id="db_password" name="db_password" value="" placeholder="Nuova password (se si desidera cambiarla)">
                    <input type="hidden" name="db_password_provided" id="db_password_provided" value="0">
                    <div class="form-text">La password verrà aggiornata solo se viene inserito un nuovo valore.</div>
                </div>

                <div class="mb-3">
                    <label for="db_name" class="form-label">Nome Database:</label>
                    <input type="text" class="form-control" id="db_name" name="db_name" value="<?php echo htmlspecialchars($config['DB_NAME']); ?>" required>
                    <div class="invalid-feedback">Inserire il nome del database.</div>
                </div>
                
                <div id="db_test_result" class="mt-3"></div> {/* Qui verranno mostrati i messaggi del test AJAX */}

            </fieldset>

            <fieldset class="mt-4">
                <legend>🌐 Impostazioni Generali Sito</legend>
                <div class="mb-3">
                    <label for="company_name" class="form-label">Nome Azienda:</label>
                    <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($config['COMPANY_NAME'] ?? 'La Tua Azienda'); ?>">
                    <div class="form-text">Nome che apparirà nei report PDF e nell'intestazione</div>
                </div>
                
                <div class="mb-3">
                    <label for="site_title" class="form-label">Titolo Sito:</label>
                    <input type="text" class="form-control" id="site_title" name="site_title" value="<?php echo htmlspecialchars($config['SITE_TITLE']); ?>">
                </div>

                <div class="mb-3">
                    <label for="items_per_page" class="form-label">Elementi per Pagina (numero):</label>
                    <input type="number" class="form-control" id="items_per_page" name="items_per_page" value="<?php echo htmlspecialchars($config['ITEMS_PER_PAGE']); ?>" min="1">
                    <div class="invalid-feedback">Inserire un numero valido (minimo 1).</div>
                </div>
            </fieldset>

            <fieldset class="mt-4">
                <legend>🖼️ Logo Aziendale per Report PDF</legend>
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Anteprima Attuale:</label>
                        <div class="logo-preview-container">
                            <?php 
                            $currentLogo = $config['COMPANY_LOGO'] ?? 'immagini/logo.png';
                            if (file_exists($currentLogo)): 
                            ?>
                                <img src="<?php echo htmlspecialchars($currentLogo); ?>?v=<?php echo time(); ?>" alt="Logo Aziendale" id="logo-preview">
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <?php 
                                        $fileSize = round(filesize($currentLogo) / 1024, 1);
                                        echo "Dimensione: {$fileSize}KB";
                                        ?>
                                    </small>
                                </div>
                            <?php else: ?>
                                <div class="logo-preview-placeholder">
                                    <i class="bi bi-image" style="font-size: 2rem; color: #dee2e6;"></i>
                                    <div>Nessuna immagine</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <label for="company_logo" class="form-label">Carica Nuovo Logo:</label>
                        <div class="file-upload-area" onclick="document.getElementById('company_logo').click()">
                            <i class="bi bi-cloud-upload" style="font-size: 2rem; color: #0d6efd;"></i>
                            <div class="mt-2">
                                <strong>Clicca per selezionare</strong> o trascina qui il file
                            </div>
                            <small class="text-muted">
                                Formati supportati: JPG, PNG, GIF, WebP<br>
                                Dimensione massima: 2MB<br>
                                <strong>Nome file:</strong> Viene sempre salvato come "logo.png"
                            </small>
                        </div>
                        <input type="file" class="form-control d-none" id="company_logo" name="company_logo" accept=".jpg,.jpeg,.png,.gif,.webp,image/*">
                        
                        <div class="mt-3" id="file-info" style="display: none;">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <span id="file-name"></span>
                                <button type="button" class="btn btn-sm btn-outline-secondary float-end" onclick="clearFileSelection()">
                                    <i class="bi bi-x"></i> Rimuovi
                                </button>
                            </div>
                        </div>

                        <?php 
                        // Mostra i backup disponibili
                        $backupFiles = [];
                        if (is_dir('immagini/')) {
                            $files = scandir('immagini/');
                            foreach ($files as $file) {
                                if (preg_match('/^logo_hold\d+\.png$/', $file)) {
                                    $backupFiles[] = $file;
                                }
                            }
                        }
                        
                        if (!empty($backupFiles)): 
                            // Ordina i backup per numero
                            usort($backupFiles, function($a, $b) {
                                preg_match('/logo_hold(\d+)\.png/', $a, $matchesA);
                                preg_match('/logo_hold(\d+)\.png/', $b, $matchesB);
                                return (int)$matchesA[1] - (int)$matchesB[1];
                            });
                        ?>
                        
                        <div class="mt-3">
                            <label class="form-label">📦 Backup Disponibili:</label>
                            <div class="backup-files-container">
                                <?php foreach ($backupFiles as $backupFile): 
                                    $backupPath = 'immagini/' . $backupFile;
                                    $fileSize = round(filesize($backupPath) / 1024, 1);
                                    $fileTime = date('d/m/Y H:i', filemtime($backupPath));
                                ?>
                                <div class="backup-item d-flex align-items-center justify-content-between p-2 border rounded mb-2">
                                    <div class="backup-info d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($backupPath); ?>?v=<?php echo time(); ?>" alt="Backup" class="backup-thumb me-2">
                                        <div>
                                            <strong><?php echo htmlspecialchars($backupFile); ?></strong><br>
                                            <small class="text-muted"><?php echo $fileTime; ?> • <?php echo $fileSize; ?>KB</small>
                                        </div>
                                    </div>
                                    <div class="backup-actions">
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="restoreBackup('<?php echo htmlspecialchars($backupFile); ?>')">
                                            <i class="bi bi-arrow-clockwise"></i> Ripristina
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteBackup('<?php echo htmlspecialchars($backupFile); ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-text">
                            <strong>Raccomandazioni:</strong>
                            <ul class="mb-0 mt-1">
                                <li>Dimensioni ottimali: 300x150px (o proporzioni simili)</li>
                                <li>Formato PNG con sfondo trasparente per risultati migliori</li>
                                <li>L'immagine apparirà nell'intestazione dei report PDF</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </fieldset>

            <div class="mt-4 d-grid gap-2 d-md-flex justify-content-md-start">
                <button type="button" id="test_connection_btn" class="btn btn-info btn-lg me-md-2 mb-2 mb-md-0"><i class="bi bi-plug-fill"></i> Test Connessione DB</button>
                <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-check-circle-fill"></i> Salva Modifiche</button>
            </div>
        </form>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const testConnectionBtn = document.getElementById('test_connection_btn');
        const dbTestResultDiv = document.getElementById('db_test_result');
        
        const dbPasswordField = document.getElementById('db_password');
        const dbPasswordProvidedField = document.getElementById('db_password_provided');
        if(dbPasswordField) {
            dbPasswordField.addEventListener('input', function() {
                dbPasswordProvidedField.value = (this.value.trim() !== '') ? '1' : '0';
            });
        }

        if (testConnectionBtn) {
            testConnectionBtn.addEventListener('click', function() {
                const dbHost = document.getElementById('db_host').value;
                const dbUsername = document.getElementById('db_username').value;
                const dbPassword = document.getElementById('db_password').value; 
                const dbName = document.getElementById('db_name').value;

                const formData = new FormData();
                formData.append('action', 'test_db_connection');
                formData.append('db_host', dbHost);
                formData.append('db_username', dbUsername);
                formData.append('db_password', dbPassword);
                formData.append('db_name', dbName);

                dbTestResultDiv.innerHTML = '<div class="alert alert-info" role="alert">Test in corso...</div>';
                // Rimuovi classi precedenti per evitare conflitti di colore
                dbTestResultDiv.className = 'mt-3';


                fetch('<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    let alertClass = data.success ? 'alert-success' : 'alert-danger';
                    dbTestResultDiv.innerHTML = '<div class="alert ' + alertClass + '" role="alert">' + htmlspecialchars(data.message) + '</div>';
                })
                .catch(error => {
                    dbTestResultDiv.innerHTML = '<div class="alert alert-danger" role="alert">Errore durante il test della connessione (richiesta fallita). Dettagli: ' + htmlspecialchars(error.toString()) + '</div>';
                    console.error('Errore AJAX:', error);
                });
            });
        }

        <?php if (isset($perform_delayed_redirect) && $perform_delayed_redirect === true): ?>
        console.log('Redirect ritardato attivato. Reindirizzamento tra 4 secondi a index.php.');
        setTimeout(function() {
            window.location.href = 'index.php?status=config_saved';
        }, 4000); 
        <?php endif; ?>

        function htmlspecialchars(str) {
            if (typeof str !== 'string') return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return str.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Attivazione validazione Bootstrap al submit
        const form = document.getElementById('configForm');
        if (form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        }

        // Gestione upload logo
        const logoUpload = document.getElementById('company_logo');
        if (logoUpload) {
            logoUpload.addEventListener('change', function(e) {
                const file = e.target.files[0];
                const fileInfo = document.getElementById('file-info');
                const fileName = document.getElementById('file-name');
                
                if (file) {
                    // Validazione tipo file
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    if (!allowedTypes.includes(file.type)) {
                        alert('Formato file non supportato. Utilizzare JPG, PNG, GIF o WebP.');
                        e.target.value = '';
                        return;
                    }
                    
                    // Validazione dimensione (2MB)
                    if (file.size > 2 * 1024 * 1024) {
                        alert('Il file è troppo grande. Dimensione massima consentita: 2MB.');
                        e.target.value = '';
                        return;
                    }
                    
                    // Mostra info file
                    const sizeKB = Math.round(file.size / 1024);
                    fileName.textContent = `📁 ${file.name} (${sizeKB}KB)`;
                    fileInfo.style.display = 'block';
                    
                    // Anteprima immagine
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = document.getElementById('logo-preview');
                        if (preview) {
                            preview.src = e.target.result;
                        } else {
                            // Crea anteprima se non esiste
                            const container = document.querySelector('.logo-preview-container');
                            container.innerHTML = `<img src="${e.target.result}" alt="Anteprima Logo" id="logo-preview">`;
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Gestione drag & drop
        const uploadArea = document.querySelector('.file-upload-area');
        if (uploadArea) {
            // Impedisce il comportamento predefinito del drag & drop sulla pagina
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                document.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            // Gestione drag & drop sull'area di upload
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, unhighlight, false);
            });
            
            uploadArea.addEventListener('drop', handleDrop, false);
            
            function highlight(e) {
                uploadArea.style.backgroundColor = '#e3f2fd';
                uploadArea.style.borderColor = '#2196f3';
            }
            
            function unhighlight(e) {
                uploadArea.style.backgroundColor = '';
                uploadArea.style.borderColor = '';
            }
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                if (files.length > 0) {
                    logoUpload.files = files;
                    // Trigger change event
                    logoUpload.dispatchEvent(new Event('change'));
                }
            }
        }

        // Salva l'immagine originale per il reset
        const preview = document.getElementById('logo-preview');
        if (preview) {
            preview.dataset.original = preview.src;
        }
        
    });

    function clearFileSelection() {
        document.getElementById('company_logo').value = '';
        document.getElementById('file-info').style.display = 'none';
        
        // Ripristina anteprima originale
        const preview = document.getElementById('logo-preview');
        if (preview && preview.dataset.original) {
            preview.src = preview.dataset.original;
        }
    }

    function restoreBackup(backupFile) {
        if (confirm(`Sei sicuro di voler ripristinare il backup "${backupFile}"?\n\nIl logo attuale verrà sostituito e un nuovo backup verrà creato automaticamente.`)) {
            const formData = new FormData();
            formData.append('action', 'restore_backup');
            formData.append('backup_file', backupFile);

            fetch('<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Backup ripristinato con successo!');
                    location.reload();
                } else {
                    alert('Errore durante il ripristino: ' + data.message);
                }
            })
            .catch(error => {
                alert('Errore durante il ripristino del backup.');
                console.error('Errore:', error);
            });
        }
    }

    function deleteBackup(backupFile) {
        if (confirm(`Sei sicuro di voler eliminare definitivamente il backup "${backupFile}"?\n\nQuesta operazione non può essere annullata.`)) {
            const formData = new FormData();
            formData.append('action', 'delete_backup');
            formData.append('backup_file', backupFile);

            fetch('<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Backup eliminato con successo!');
                    location.reload();
                } else {
                    alert('Errore durante l\'eliminazione: ' + data.message);
                }
            })
            .catch(error => {
                alert('Errore durante l\'eliminazione del backup.');
                console.error('Errore:', error);
            });
        }
    }
    </script>
</body>
</html>