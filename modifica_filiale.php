<?php
// File: modifica_filiale.php
include 'dati_utente.php'; // Gestisce la sessione e l'eventuale redirect al login
include_once 'config.php'; // Includi configurazione DB

// Verifica che l'utente sia loggato (ridondante se fatto in dati_utente.php, ma sicurezza extra)
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Includi il file di query specifico per la tabella utenti
include 'query/qutenti.php';

// *** Recupera i dati dell'utente LOGGATO in modo sicuro (Standardizzato) ***
$username_loggato = $_SESSION['username'];
$utente_loggato_data = []; // Inizializza l'array
$livello_utente_loggato = 99; // Default a un livello alto per sicurezza

// Usiamo un prepared statement per recuperare i dati dell'utente loggato
$sql_user = $conn->prepare("SELECT Nome, Cognome, targa_mezzo, divisione, filiale, livello FROM utenti WHERE username = ? LIMIT 1");
if ($sql_user) {
    $sql_user->bind_param("s", $username_loggato);
    $sql_user->execute();
    $result_user = $sql_user->get_result();
    if ($result_user->num_rows > 0) {
        $utente_loggato_data = $result_user->fetch_assoc();
        $utente_loggato_data['username'] = $username_loggato;
        $livello_utente_loggato = $utente_loggato_data['livello'];
    } else {
        error_log("Utente loggato '$username_loggato' non trovato nel database utenti durante modifica filiale.");
        session_destroy();
        header("Location: login.php");
        exit();
    }
    $sql_user->close();
} else {
    error_log("Errore preparazione query dati utente in modifica_filiale.php: " . $conn->error);
    die("Errore critico nel recupero dati utente.");
}

// --- CONTROLLO PERMESSI SPECIFICO (solo livello 1) ---
if ($livello_utente_loggato != 1) {
    header("Location: unauthorized.php");
    exit();
}

// Inizializza variabili
$errore = "";
$filiale_data = null;

// Verifica se è stata passata la divisione da modificare (GET o POST)
if ((isset($_GET['divisione']) && !empty($_GET['divisione'])) || (isset($_POST['divisione']) && !empty($_POST['divisione']))) {
    $divisione_da_modificare = isset($_GET['divisione']) ? trim($_GET['divisione']) : trim($_POST['divisione']);
    
    try {
        // Recupera i dati della filiale da modificare
        $query_filiale = "SELECT divisione, filiale FROM filiali WHERE divisione = ? LIMIT 1";
        $stmt_filiale = $conn->prepare($query_filiale);
        $stmt_filiale->bind_param("s", $divisione_da_modificare);
        $stmt_filiale->execute();
        $result_filiale = $stmt_filiale->get_result();
        
        if ($result_filiale->num_rows > 0) {
            $filiale_data = $result_filiale->fetch_assoc();
        } else {
            $_SESSION['error_message'] = "Filiale non trovata.";
            header("Location: gestisci_filiali.php");
            exit();
        }
        $stmt_filiale->close();
        
    } catch (Exception $e) {
        error_log("Errore recupero filiale in modifica_filiale.php: " . $e->getMessage());
        $_SESSION['error_message'] = "Errore durante il recupero dei dati della filiale.";
        header("Location: gestisci_filiali.php");
        exit();
    }
} else {
    // Parametro mancante - reindirizza alla gestione filiali
    $_SESSION['error_message'] = "Accesso non valido. Seleziona una filiale da modificare.";
    header("Location: gestisci_filiali.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Filiale</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 80px;
        }

        .fixed-top-elements {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background-color: #e9ecef;
            padding: 10px 15px;
            z-index: 1030;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #dee2e6;
            flex-wrap: wrap;
        }

        .menu-btn {
            font-size: 1.2rem;
        }

        .user-info-display {
            font-size: 0.8rem;
            text-align: right;
            color: #495057;
            line-height: 1.3;
        }
        .user-info-display strong {
            display: inline-block;
            margin-right: 5px;
        }
    </style>
</head>
<body>

<div class="fixed-top-elements">
    <button class="btn btn-outline-secondary menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMenu" aria-controls="mainMenu">
        <i class="bi bi-list"></i> Menu
    </button>
    <div class="user-info-display">
        <strong>Utente:</strong> <?php echo htmlspecialchars($utente_loggato_data['username']); ?><br>
        (Liv: <?php echo htmlspecialchars($livello_utente_loggato); ?>)
    </div>
</div>

<?php include 'include/menu.php'; ?>

<div class="container" id="main-content">
    <h1 class="mb-4 text-center">Modifica Filiale</h1>

    <?php
    // Gestione messaggi di sessione
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['error_message']);
    }
    if (!empty($errore)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($errore); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($filiale_data): ?>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form id="filialeForm" action="aggiorna_filiale.php" method="post" class="bg-white shadow-sm rounded p-4">
                    <input type="hidden" name="divisione" value="<?php echo htmlspecialchars($filiale_data['divisione']); ?>">

                    <div class="mb-3">
                        <label for="divisione_readonly" class="form-label">Divisione:</label>
                        <input type="text" 
                               id="divisione_readonly" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($filiale_data['divisione']); ?>" 
                               readonly>
                        <div class="form-text">La divisione non può essere modificata.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="filiale" class="form-label">Nome Filiale:</label>
                        <input type="text" 
                               name="filiale" 
                               id="filiale" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($filiale_data['filiale']); ?>" 
                               required>
                        <div class="form-text text-muted">
                            <small> Il testo verrà automaticamente convertito in maiuscolo</small>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" name="submit_update_filiale" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i> Aggiorna Filiale
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="gestisci_filiali.php" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Torna alla Gestione Filiali
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('filialeForm');
    if (form) {
        // Conversione in maiuscolo durante la digitazione
        const textFields = form.querySelectorAll('input[type="text"]:not([readonly]):not([disabled]), textarea:not([readonly]):not([disabled])');
        textFields.forEach(function(field) {
            field.addEventListener('input', function(e) {
                // Salva la posizione del cursore
                const cursorPosition = e.target.selectionStart;
                // Converti in maiuscolo
                e.target.value = e.target.value.toUpperCase();
                // Ripristina la posizione del cursore
                e.target.setSelectionRange(cursorPosition, cursorPosition);
            });
        });
        
        // Mantieni anche la conversione all'invio come backup e per il trim
        form.addEventListener('submit', function(e) {
            textFields.forEach(function(field) {
                field.value = field.value.toUpperCase().trim();
            });
        });
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>
<?php
// Chiudi la connessione al database se aperta
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>