<?php
// File: inserisci_filiale.php (Standardizzato)
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
        error_log("Utente loggato '$username_loggato' non trovato nel database utenti durante inserimento filiale.");
        session_destroy();
        header("Location: login.php");
        exit();
    }
    $sql_user->close();
} else {
    error_log("Errore preparazione query dati utente in inserisci_filiale.php: " . $conn->error);
    die("Errore critico nel recupero dati utente.");
}

// Assegna i dati dell'utente alla variabile attesa dal menu.php
$utente_data = $utente_loggato_data;

// --- CONTROLLO PERMESSI SPECIFICO (solo livello 1) ---
if ($livello_utente_loggato != 1) {
    header("Location: unauthorized.php");
    exit();
}

$conn->close(); // Chiudi la connessione
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserimento Filiale</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 80px; /* Spazio per la navbar fissa standardizzata */
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
            flex-wrap: wrap; /* Aggiunto per responsività */
        }
        .menu-btn {
            font-size: 1.2rem;
        }
        /* Stile standardizzato per il display utente */
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
        /* Stile per i campi in maiuscolo */
        .uppercase-input {
            text-transform: uppercase;
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
    <h1 class="mb-4 text-center">Inserimento Nuova Filiale</h1>

    <?php
    // Gestione messaggi di sessione (Standardizzato)
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['error_message']);
    }
    ?>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <form action="salva_filiale.php" method="post" class="bg-white shadow-sm rounded p-4" id="filialeForm">
                <div class="mb-3">
                    <label for="divisione" class="form-label">Divisione:</label>
                    <input type="text" name="divisione" id="divisione" class="form-control uppercase-input" required>
                    <small class="form-text text-muted">Il testo verrà automaticamente convertito in maiuscolo</small>
                </div>
                <div class="mb-3">
                    <label for="nome_divisione" class="form-label">Nome Divisione:</label>
                    <input type="text" name="nome_divisione" id="nome_divisione" class="form-control uppercase-input" required>
                    <small class="form-text text-muted">Il testo verrà automaticamente convertito in maiuscolo</small>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-building-add me-2"></i> Inserisci Filiale</button>
                </div>
            </form>
            <div class="text-center mt-3">
                <a href="gestisci_filiali.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Torna alla Gestione Filiali</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Seleziona tutti gli input con classe uppercase-input
    const uppercaseInputs = document.querySelectorAll('.uppercase-input');
    
    uppercaseInputs.forEach(function(input) {
        // Converte in maiuscolo mentre l'utente digita
        input.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
        
        // Converte in maiuscolo quando l'utente esce dal campo
        input.addEventListener('blur', function() {
            this.value = this.value.toUpperCase();
        });
    });
    
    // Assicurati che i valori siano in maiuscolo prima dell'invio del form
    document.getElementById('filialeForm').addEventListener('submit', function(e) {
        uppercaseInputs.forEach(function(input) {
            input.value = input.value.toUpperCase();
        });
    });

    // Auto-chiudi gli alert dopo 5 secondi
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000); // 5000 ms = 5 secondi
    });
});
</script>
</body>
</html>