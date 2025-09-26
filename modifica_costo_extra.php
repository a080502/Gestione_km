<?php
// File: modifica_costo_extra.php - Completo con gestione POST differenziata e campi modificati

// --- Inizio Blocco Sicurezza e Dati ---
include_once 'config.php'; // Includi configurazione DB
// dati_utente.php gestisce sessione e recupera dati in $_SESSION['dati_utente']
// Assumi che dati_utente.php contenga session_start() e metta i dati utente in $_SESSION['dati_utente']
include 'dati_utente.php';

// --- CONTROLLO E DEFINIZIONE VARIABILI ---
// Verifica che l'utente sia loggato E che i dati utente siano stati caricati correttamente nella sessione
if (!isset($_SESSION['username']) || !isset($_SESSION['dati_utente']) || $_SESSION['dati_utente'] === null) {
    // Se manca username in sessione O i dati utente non sono stati caricati in sessione,
    // significa che l'utente non è considerato loggato o i suoi dati non sono disponibili.
    header("Location: login.php"); // Reindirizza alla pagina di login
    exit();
}

// Definisci le variabili locali $dati_utente, $livello_utente e $targa_utente
// prendendole dall'array che dati_utente.php ha salvato nella sessione
$dati_utente = $_SESSION['dati_utente'];
$livello_utente = $dati_utente['livello']; // Ora $livello_utente è definito!
$targa_utente = isset($dati_utente['targa_mezzo']) ? $dati_utente['targa_mezzo'] : null; // Assicurati che targa_mezzo esista

// --- Fine CONTROLLO ---


// Includi il file di query con le funzioni getCostoExtraById e update_costo_extra
include 'query/q_costo_extra.php';

// --- Fine Blocco Sicurezza e Dati ---


$id_costo = 0;
$record = null; // Array per contenere i dati del record da modificare
$errore = '';
$messaggio = ''; // Per futuri messaggi, al momento usiamo redirect

// --- Gestione richieste POST ---

// Controlla se la richiesta è POST E se proviene dal SUBMIT del form di questa pagina (modifica_costo_extra.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_update'])) {
    // --- Blocco 1: Gestione invio form di modifica da questa pagina ---

    // 1. Recupera e sanifica i dati dal POST (per l'aggiornamento)
    // L'ID viene recuperato dal campo hidden NEL FORM DI QUESTA PAGINA ('id_costo')
    $id_costo = isset($_POST['id_costo']) ? filter_var($_POST['id_costo'], FILTER_VALIDATE_INT) : 0;
    // La targa non è modificabile, non la recuperiamo dal POST per l'aggiornamento
    $costo_input = isset($_POST['costo']) ? str_replace(',', '.', trim($_POST['costo'])) : ''; // Sostituisci virgola con punto

    // 2. Validazione iniziale dei dati POST (solo ID del form e Costo)
    if ($id_costo === false || $id_costo <= 0) {
        $errore = "ID costo extra non valido dal form di modifica.";
    } elseif (!is_numeric($costo_input) || floatval($costo_input) < 0) {
        $errore = "Il costo deve essere un numero non negativo.";
    }

    // 3. Se non ci sono errori di validazione iniziali...
    if (empty($errore)) {
        // Recupera il record originale per fare i controlli sui permessi (la targa nel DB)
        $record_originale = getCostoExtraById($conn, $id_costo);

        if (!$record_originale) {
            $errore = "Record costo extra non trovato per l'ID specificato.";
        } else {
            // 4. Controllo Permessi di Modifica
            $can_edit = ($livello_utente < 3 || ($livello_utente >= 3 && $targa_utente !== null && $record_originale['targa_mezzo'] === $targa_utente));

            if (!$can_edit) {
                $errore = "Permesso negato. Non hai i privilegi per modificare questo costo extra.";
            } else {
                // La targa non è modificabile, usiamo SEMPRE la targa originale dal record.
                $targa_per_aggiornamento = $record_originale['targa_mezzo'];
                $nuovo_costo_float = floatval($costo_input);

                // 5. Esegui l'aggiornamento nel database
                // Chiamiamo la funzione di update con l'ID, la targa originale e il nuovo costo
                if (update_costo_extra($conn, $id_costo, $targa_per_aggiornamento, $nuovo_costo_float)) {
                    // Aggiornamento riuscito
                    header("Location: gestione_costo_extra.php?messaggio=Costo extra ID $id_costo aggiornato con successo.");
                    exit();
                } else {
                    // Errore nell'aggiornamento DB
                    $errore = "Errore durante l'aggiornamento del costo extra nel database.";
                    // Ricarica i dati per mostrare il form con l'errore
                    $record = $record_originale; // Usa i dati originali
                    $record['costo'] = $costo_input; // Mantieni il costo inserito dall'utente
                }
            }
        }
    }

    // Se c'è stato un errore POST (submit), rimane su questa pagina e mostra l'errore.
    // $errore contiene il messaggio. Dobbiamo assicurarci che $record sia caricato
    // per mostrare il form di nuovo con i dati inseriti (e l'errore).
    if(empty($record) && $id_costo > 0){
        $record = getCostoExtraById($conn, $id_costo);
        if($record){
            $record['costo'] = $costo_input;
        }
    }


} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Blocco 2: Gestione richiesta POST INIZIALE dall'elenco (senza flag submit_update) ---
    // Questa è la richiesta che arriva cliccando "Modifica" dall'elenco.
    // Dobbiamo solo caricare i dati del record per visualizzare il form.

    // 1. Verifica se l'ID è stato passato tramite POST dal form dell'elenco ('id')
    if (!isset($_POST['id']) || empty($_POST['id'])) { // <-- L'ID si chiama 'id' dal form dell'elenco
        header("Location: gestione_costo_extra.php?errore=Accesso non valido per la modifica. ID non ricevuto via POST.");
        exit();
    }

    // Sanifica e valida l'ID ricevuto via POST
    $id_costo = filter_var($_POST['id'], FILTER_VALIDATE_INT); // <-- Prende da $_POST['id']

    if ($id_costo === false || $id_costo <= 0) {
        header("Location: gestione_costo_extra.php?errore=ID costo extra non valido per la modifica (ricevuto via POST dall'elenco).");
        exit();
    }

    // 2. Recupera i dati del record dal database usando l'ID ricevuto via POST
    $record = getCostoExtraById($conn, $id_costo);

    if (!$record) {
        $errore = "Record costo extra con ID $id_costo non trovato."; // Imposta errore, non reindirizza subito
        // Il codice HTML sotto non mostrerà il form dato che $record è null.
    } else {
        // 3. Controllo Permessi di Modifica (sulla visualizzazione del form)
        $can_edit = ($livello_utente < 3 || ($livello_utente >= 3 && $targa_utente !== null && $record['targa_mezzo'] === $targa_utente));

        if (!$can_edit) {
            $errore = "Permesso negato. Non hai i privilegi per modificare questo costo extra."; // Imposta errore
            $record = null; // Impedisce la visualizzazione del form
        }
        // Se non ci sono errori, $record contiene i dati e verrà mostrato il form.
    }

} else {
    // --- Blocco 3: Gestione di richieste che NON sono POST (es. accesso diretto via GET) ---
    // Con l'invio da elenco via POST, questo blocco non dovrebbe essere mai raggiunto
    // per l'accesso iniziale, ma è una buona pratica gestirlo.
    header("Location: gestione_costo_extra.php?errore=Accesso non consentito direttamente.");
    exit();
}
$utente_data = $dati_utente; // <-- AGGIUNTA QUESTA RIGA PER IL MENU

// --- Fine Logica PHP - Inizio Output HTML ---
// Questo blocco HTML viene sempre mostrato se la logica PHP precedente ha caricato $record
// (sia nella richiesta iniziale dall'elenco che in caso di errore dopo un submit).
?>
    <!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Modifica Costo Extra</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <style>
            /* Includi qui lo stesso CSS usato in visualizza_target/gestione_costo_extra */
            body { background-color: #f8f9fa; padding-top: 80px; }
            .fixed-top-elements { position: fixed; top: 0; left: 0; right: 0; background-color: #e9ecef; padding: 10px 15px; z-index: 1030; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #dee2e6; flex-wrap: wrap; }
            .menu-btn { font-size: 1.2rem; }
            .user-info-display { font-size: 0.8rem; text-align: right; color: #495057; line-height: 1.3; }
            .user-info-display strong { display: inline-block; margin-right: 5px; }
            .offcanvas-header { border-bottom: 1px solid #dee2e6; }
            .offcanvas-body .nav-link { padding: 0.8rem 1.5rem; font-size: 1.1rem; color: #212529; }
            .offcanvas-body .nav-link:hover { background-color: #e9ecef; }
            .offcanvas-body hr { margin: 1rem 1.5rem; }
            #main-content { margin-top: 20px; }
        </style>
    </head>
    <body>

    <div class="fixed-top-elements">
        <button class="btn btn-outline-secondary menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMenu" aria-controls="mainMenu">
            <i class="bi bi-list"></i> Menu
        </button>
        <div class="user-info-display">
            <strong>Utente:</strong> <?php echo htmlspecialchars($dati_utente['username']); ?><br>
            <?php if ($targa_utente): ?>
                <strong>Targa:</strong> <?php echo htmlspecialchars($targa_utente); ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'include/menu.php'; // Assicurati che questo file esista ?>

    <div class="container" id="main-content">

        <h1 class="mb-4 h3">Modifica Costo Extra</h1>

        <?php if (!empty($errore)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($errore); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($record): // Mostra il form solo se $record è stato caricato correttamente ?>
            <div class="card shadow-sm">
                <div class="card-header">
                    Modifica Costo Extra (ID: <?php echo htmlspecialchars($record['id']); ?>)
                </div>
                <div class="card-body">
                    <form action="modifica_costo_extra.php" method="POST">
                        <!-- Campo nascosto per l'ID del record, usato nel blocco POST 1 -->
                        <input type="hidden" name="id_costo" value="<?php echo htmlspecialchars($record['id']); ?>">
                        <!-- Campo nascosto per indicare che questo è un SUBMIT del form di modifica -->
                        <input type="hidden" name="submit_update" value="1">

                        <div class="mb-3">
                            <label for="targa_mezzo" class="form-label">Targa Mezzo:</label>
                            <input type="text" class="form-control" id="targa_mezzo" name="targa_mezzo"
                                   value="<?php echo htmlspecialchars($record['targa_mezzo']); ?>"
                                   readonly required>
                            <div id="targaHelp" class="form-text">La targa associata al costo non è modificabile.</div>
                        </div>

                        <div class="mb-3">
                            <label for="costo" class="form-label">Costo (€):</label>
                            <input type="text" class="form-control" id="costo" name="costo"
                                   value="<?php echo htmlspecialchars(number_format($record['costo'], 2, ',', '.')); ?>"
                                   required>
                        </div>

                        <?php /* Campo time_stamp rimosso come richiesto */ ?>

                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Salva Modifiche</button>
                        <a href="gestione_costo_extra.php" class="btn btn-secondary"><i class="bi bi-x-circle me-2"></i>Annulla</a>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Questo messaggio viene mostrato se $record è null (es. record non trovato, permessi negati o ID non valido inizialmente) -->
            <p class="text-danger"><?php echo empty($errore) ? 'Impossibile caricare i dati del costo extra per la modifica.' : $errore; ?></p>
        <?php endif; ?>

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