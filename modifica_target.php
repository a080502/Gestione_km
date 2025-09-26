<?php
// File: modifica_target.php (Standardizzato - Accetta ID via GET o POST)

// --- Inizio Blocco Sicurezza e Dati ---
include_once 'config.php'; // Includi configurazione DB
include 'dati_utente.php'; // Gestisce le informazioni sull'utente corrente (es. sessione)

// Verifica che l'utente sia loggato
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Includi il file di query specifico per la tabella T
include 'query/q_target_km.php';
// Includi file di query
include 'query/qutenti.php'; // Funzioni per interagire con la tabella utenti
include 'query/q_costo_extra.php'; // Funzioni per recuperare dati relativi ai costi extra

// Recupera i dati dell'utente loggato, incluso il livello
$username_loggato = $_SESSION['username'];
$dati_utente = [];
$livello_utente = 99; // Default

$sql_user = $conn->prepare("SELECT Nome, Cognome, targa_mezzo, divisione, filiale, livello FROM utenti WHERE username = ? LIMIT 1");
if ($sql_user) {
    $sql_user->bind_param("s", $username_loggato);
    $sql_user->execute();
    $result_user = $sql_user->get_result();
    if ($result_user->num_rows > 0) {
        $dati_utente = $result_user->fetch_assoc();
        $dati_utente['username'] = $username_loggato;
        $livello_utente = $dati_utente['livello'];
    } else {
        error_log("Utente loggato '$username_loggato' non trovato nel database utenti.");
        // Considera un logout o un errore grave qui
        session_destroy();
        header("Location: login.php");
        exit();
    }
    $sql_user->close();
} else {
    error_log("Errore preparazione query dati utente (utenti table) in modifica: " . $conn->error);
    die("Errore nel recupero dati utente.");
}

// --- FINE Recupero dati utente ---


// --- Gestione ID record e recupero dati esistenti (Accetta GET o POST per ID iniziale) ---
$target_id = null;
$record_esistente = null; // Questo conterrà i dati del record dal DB O i dati inviati in POST in caso di errore
$errore = '';
$messaggio = '';

// Flag per sapere se stiamo processando un POST fallito con errori di validazione
$is_post_with_errors = false;

// *** CONTROLLA SE L'ID È STATO PASSATO TRAMITE GET O POST ***
if (isset($_GET['id'])) {
    $target_id = intval($_GET['id']);
} elseif (isset($_POST['id'])) {
    // Se l'ID arriva via POST, è probabilmente la richiesta iniziale dal form di visualizza_target.php
    $target_id = intval($_POST['id']);
    // NOTA: Se il form *di modifica* in questa pagina viene inviato, $_POST['id']
    // sarà l'ID del record che stiamo modificando.
    // Dobbiamo distinguere l'invio iniziale dall'invio del modulo di modifica.
    // La distinzione avviene nel blocco $_SERVER['REQUEST_METHOD'] === 'POST'.
    // Per l'ID iniziale, possiamo semplicemente prenderlo da POST se GET non è presente.

} else {
    $errore = "ID record non specificato.";
}


if ($target_id !== null && $target_id > 0) {
    // Se un ID valido è stato trovato (tramite GET o POST iniziale)
    // Recupera il record dalla tabella target_annuale
    $record_esistente = get_target_km_by_id($conn, $target_id);

    if (!$record_esistente) {
        $errore = "Record con ID " . htmlspecialchars($target_id) . " non trovato.";
        $target_id = null; // Invalida l'ID se il record non esiste
    } else {
        // --- CONTROLLO PERMESSI DI MODIFICA ---
        // Utenti con livello >= 3 possono modificare SOLO i propri record.
        // Utenti con livello < 3 possono modificare TUTTI i record.
        if ($livello_utente >= 3 && $record_esistente['username'] !== $username_loggato) {
            // Record non appartiene all'utente loggato e livello >= 3
            header("Location: unauthorized.php"); // O un'altra pagina di errore permessi
            exit();
        }
        // --- FINE CONTROLLO PERMESSI ---
    }
} else {
    if (empty($errore)) { // Evita di sovrascrivere un errore precedente (es. ID <= 0)
        $errore = "ID record non specificato o non valido.";
    }
}


// --- Gestione invio del modulo di modifica (POST da QUESTA PAGINA) ---
// Questo blocco si attiva quando il form in modifica_target.php viene inviato
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_modifica_form'])) { // Uso un nome specifico per il submit button di questo form

    $target_id_post = isset($_POST['id']) ? intval($_POST['id']) : 0;

    // Re-recupera il record ORIGINALE per un controllo di sicurezza aggiuntivo e per recuperare i campi non modificabili
    // Usa l'ID dal POST, che dovrebbe essere l'ID del record che si sta tentando di aggiornare
    $record_originale_db = null;
    if ($target_id_post > 0) {
        $record_originale_db = get_target_km_by_id($conn, $target_id_post);
    }


    if ($target_id_post <= 0 || !$record_originale_db) {
        // Se l'ID dal form POST non è valido o il record originale non esiste, è un errore
        $errore = "Errore di sicurezza o record non trovato durante l'aggiornamento.";
        // Non possiamo ripopolare il form con dati originali validi
        $record_esistente = null;

    } else {
        // --- NUOVO CONTROLLO PERMESSI DI MODIFICA SULLA RICHIESTA POST ---
        if ($livello_utente >= 3 && $record_originale_db['username'] !== $username_loggato) {
            // Tentativo di modificare un record non proprio da un utente con livello >= 3
            header("Location: unauthorized.php");
            exit();
        }
        // --- FINE CONTROLLO PERMESSI SULLA RICHIESTA POST ---
        // RECUPERA SOLO IL CAMPO MODIFICABILE DAL POST
        $target_chilometri_post = filter_input(INPUT_POST, 'target_chilometri', FILTER_VALIDATE_INT);

        // VALIDAZIONE SOLO PER IL CAMPO MODIFICABILE
        if ($target_chilometri_post === false || $target_chilometri_post === null || $target_chilometri_post < 0) {
            $errore = "Target chilometri non valido.";
        }

        if (empty($errore)) {
            // Prepara i dati da passare alla funzione di update
            // Anno, Username, Targa, Divisione, Filiale vengono SEMPRE presi dal record originale del DB
            $anno_da_salvare = $record_originale_db['anno'];
            $username_da_salvare = $record_originale_db['username'];
            $targa_da_salvare = $record_originale_db['targa_mezzo'];
            $divisione_da_salvare = $record_originale_db['divisione'];
            $filiale_da_salvare = $record_originale_db['filiale'];
            $target_chilometri_da_salvare = $target_chilometri_post; // Questo viene dall'input utente validato

            // Esegui l'aggiornamento
            $success = update_target_km($conn, $target_id_post, $anno_da_salvare, $target_chilometri_da_salvare, $username_da_salvare, $targa_da_salvare, $divisione_da_salvare, $filiale_da_salvare);

            if ($success) {
                // Reindirizza alla pagina di visualizzazione con messaggio di successo
                // Standardizza il nome del file di destinazione e usa $_SESSION per il messaggio
                $_SESSION['success_message'] = "Record target aggiornato con successo.";
                header("Location: visualizza_target.php"); // Reindirizza al file corretto
                exit();
            } else {
                $errore = "Si è verificato un errore durante l'aggiornamento del record nel database.";
                // In caso di errore DB, i dati originali sono in $record_originale_db
                $record_esistente = $record_originale_db; // Usiamo i dati originali per ripopolare i campi non modificabili
                // Metti il valore inviato per il campo modificabile per permettere all'utente di correggerlo
                $record_esistente['target_chilometri'] = filter_input(INPUT_POST, 'target_chilometri', FILTER_UNSAFE_RAW);
                // Assicurati che l'ID per il form POST rimanga corretto
                $record_esistente['id'] = $target_id_post;
                $is_post_with_errors = true; // Indica che stiamo mostrando il form dopo un POST con errori
            }
        } else {
            // Errore di validazione del campo modificabile
            // Ricarica i dati originali del DB per i campi non modificabili e ripopola il campo modificabile con l'input inviato
            $record_esistente = $record_originale_db;
            $record_esistente['target_chilometri'] = filter_input(INPUT_POST, 'target_chilometri', FILTER_UNSAFE_RAW);
            // Assicurati che l'ID per il form POST rimanga corretto
            $record_esistente['id'] = $target_id_post;
            $is_post_with_errors = true; // Indica che stiamo mostrando il form dopo un POST con errori
        }
    }
}
$utente_data = $dati_utente;

?>
    <!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Modifica Target KM</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <style>
            body {
                background-color: #f8f9fa;
                padding-top: 80px; /* Regola questo valore se necessario */
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
            .menu-btn { font-size: 1.2rem; }
            .user-info-display { font-size: 0.8rem; text-align: right; color: #495057; line-height: 1.3; }
            .user-info-display strong { display: inline-block; margin-right: 5px; }
            .offcanvas-header { border-bottom: 1px solid #dee2e6; }
            .offcanvas-body .nav-link { padding: 0.8rem 1.5rem; font-size: 1.1rem; color: #212529; }
            .offcanvas-body .nav-link:hover { background-color: #e9ecef; }
            .offcanvas-body hr { margin: 1rem 1.5rem; }
        </style>
    </head>
    <body>

    <div class="fixed-top-elements">
        <button class="btn btn-outline-secondary menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMenu" aria-controls="mainMenu">
            <i class="bi bi-list"></i> Menu </button>
        <div class="user-info-display">
            <strong>Utente:</strong> <?php echo htmlspecialchars($dati_utente['username']); ?><br>
            (Liv: <?php echo htmlspecialchars($livello_utente); ?>)
        </div>
    </div>

    <?php include 'include/menu.php'; // Assicurati che questo file esista ?>

    <div class="container" id="main-content">

        <h1 class="mb-4 h3">Modifica Record Target KM</h1>

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
        // Mostra l'errore gestito direttamente in questa pagina se presente
        if (!empty($errore)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($errore); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>


        <?php if ($record_esistente && $target_id !== null && $target_id > 0): // Mostra il form solo se il record è stato trovato con un ID valido e i permessi verificati ?>

            <form method="POST" action="modifica_target.php"> <input type="hidden" name="id" value="<?php echo htmlspecialchars($record_esistente['id']); ?>">

                <div class="mb-3">
                    <label for="anno" class="form-label">Anno</label>
                    <input type="number" class="form-control" id="anno" value="<?php echo htmlspecialchars($record_esistente['anno']); ?>" readonly>
                    <input type="hidden" name="anno" value="<?php echo htmlspecialchars($record_esistente['anno']); ?>">
                </div>

                <div class="mb-3">
                    <label for="target_chilometri" class="form-label">Target Chilometri</label>
                    <input type="number" class="form-control" id="target_chilometri" name="target_chilometri" value="<?php echo htmlspecialchars($record_esistente['target_chilometri']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($record_esistente['username']); ?>" readonly>
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($record_esistente['username']); ?>">
                </div>

                <div class="mb-3">
                    <label for="targa_mezzo" class="form-label">Targa Mezzo</label>
                    <input type="text" class="form-control" id="targa_mezzo" value="<?php echo htmlspecialchars($record_esistente['targa_mezzo']); ?>" readonly>
                    <input type="hidden" name="targa_mezzo" value="<?php echo htmlspecialchars($record_esistente['targa_mezzo']); ?>">
                </div>

                <div class="mb-3">
                    <label for="divisione" class="form-label">Divisione</label>
                    <input type="text" class="form-control" id="divisione" value="<?php echo htmlspecialchars($record_esistente['divisione']); ?>" readonly>
                    <input type="hidden" name="divisione" value="<?php echo htmlspecialchars($record_esistente['divisione']); ?>">
                </div>

                <div class="mb-3">
                    <label for="filiale" class="form-label">Filiale</label>
                    <input type="text" class="form-control" id="filiale" value="<?php echo htmlspecialchars($record_esistente['filiale']); ?>" readonly>
                    <input type="hidden" name="filiale" value="<?php echo htmlspecialchars($record_esistente['filiale']); ?>">
                </div>

                <button type="submit" name="submit_modifica_form" class="btn btn-primary">Salva Modifiche</button>
                <a href="visualizza_target.php" class="btn btn-secondary">Annulla</a>
            </form>

        <?php elseif(empty($errore)): ?>
            <p class="alert alert-info">Seleziona un record da modificare dalla pagina di visualizzazione.</p>
        <?php endif; // Fine if per mostrare il form ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    </body>
    </html>
<?php
// Chiudi la connessione al database se aperta
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>