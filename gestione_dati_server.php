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
    'ITEMS_PER_PAGE' => 10
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
    $newConfig = [
        'DB_HOST' => isset($_POST['db_host']) ? htmlspecialchars(trim($_POST['db_host'])) : $config['DB_HOST'],
        'DB_USERNAME' => isset($_POST['db_username']) ? htmlspecialchars(trim($_POST['db_username'])) : $config['DB_USERNAME'],
        'DB_PASSWORD' => (isset($_POST['db_password_provided']) && $_POST['db_password_provided'] === '1' && !empty(trim($_POST['db_password']))) ? trim($_POST['db_password']) : $config['DB_PASSWORD'],
        'DB_NAME' => isset($_POST['db_name']) ? htmlspecialchars(trim($_POST['db_name'])) : $config['DB_NAME'],
        'SITE_TITLE' => isset($_POST['site_title']) ? htmlspecialchars(trim($_POST['site_title'])) : $config['SITE_TITLE'],
        'ITEMS_PER_PAGE' => isset($_POST['items_per_page']) ? filter_var(trim($_POST['items_per_page']), FILTER_VALIDATE_INT) : $config['ITEMS_PER_PAGE'],
    ];
    
    if ($newConfig['ITEMS_PER_PAGE'] === false || $newConfig['ITEMS_PER_PAGE'] < 1) {
        $newConfig['ITEMS_PER_PAGE'] = $config['ITEMS_PER_PAGE'];
    }

    $phpCode = "<?php\n// File di configurazione generato automaticamente.\n\nreturn " . var_export($newConfig, true) . ";\n";

    if (file_put_contents($configFile, $phpCode) !== false) {
        $message = "Configurazione aggiornata con successo! Sarai reindirizzato alla pagina index tra 4 secondi.";
        $message_type = 'success';
        $config = $newConfig; 
        
        $_SESSION['flash_message'] = "Configurazione aggiornata con successo.";
        $_SESSION['flash_message_type'] = 'success';
        
        $perform_delayed_redirect = true; 

    } else {
        $message = "Errore nel salvataggio della configurazione. Controlla i permessi del file '$configFile'.";
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
        
        <form id="configForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="needs-validation" novalidate>
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
                    <label for="site_title" class="form-label">Titolo Sito:</label>
                    <input type="text" class="form-control" id="site_title" name="site_title" value="<?php echo htmlspecialchars($config['SITE_TITLE']); ?>">
                </div>

                <div class="mb-3">
                    <label for="items_per_page" class="form-label">Elementi per Pagina (numero):</label>
                    <input type="number" class="form-control" id="items_per_page" name="items_per_page" value="<?php echo htmlspecialchars($config['ITEMS_PER_PAGE']); ?>" min="1">
                    <div class="invalid-feedback">Inserire un numero valido (minimo 1).</div>
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
    });
    </script>
</body>
</html>