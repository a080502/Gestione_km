<?php
// File: gestione_filiali.php (Standardizzato)

// --- Inizio Blocco Sicurezza e Dati Standardizzati ---
// Assumi che dati_utente.php gestisca session_start() e il redirect se non loggato
// e che definisca $_SESSION['username']
include 'dati_utente.php'; // Gestisce la sessione e l'eventuale redirect al login
include_once 'config.php'; // Includi configurazione DB

// Verifica che l'utente sia loggato (ridondante se fatto in dati_utente.php, ma sicurezza extra)
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Includi il file di query specifico per la tabella utenti (per get_user_data)
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
        // Aggiungi username per coerenza
        $utente_loggato_data['username'] = $username_loggato;
        $livello_utente_loggato = $utente_loggato_data['livello']; // Recupera il livello
    } else {
        // Questa situazione non dovrebbe verificarsi se dati_utente.php e la tabella utenti sono coerenti
        error_log("Utente loggato '$username_loggato' non trovato nel database utenti durante il recupero dati in gestione_filiali.php.");
        // Reindirizza al logout per sicurezza
        session_destroy(); // Distrugge tutti i dati della sessione corrente
        header("Location: login.php"); // Reindirizza alla pagina di login
        exit();
    }
    $sql_user->close();
} else {
    error_log("Errore preparazione query dati utente (utenti table) in gestione_filiali.php: " . $conn->error);
    die("Errore critico nel recupero dati utente.");
}
// Assegna i dati dell'utente alla variabile attesa dal menu.php
$utente_data = $utente_loggato_data; // <-- AGGIUNTA QUESTA RIGA PER IL MENU
// --- CONTROLLO PERMESSI SPECIFICO PER QUESTA PAGINA (solo livello 1) ---
if ($livello_utente_loggato != 1) {
    // Se il livello non è 1, reindirizza a una pagina di accesso non autorizzato
    header("Location: unauthorized.php"); // Assicurati di avere una pagina unauthorized.php
    exit();
}
// --- FINE CONTROLLO PERMESSI ---


// Recupera tutte le filiali dal database (questa parte viene eseguita solo se il livello è 1)
// Considera di spostare questa query in un file dedicato query/qfiliali.php se diventa complessa
$sql = "SELECT divisione, filiale FROM filiali ORDER BY divisione ASC";
$result = $conn->query($sql);

// Controlla se la query ha avuto successo
if ($result === false) {
    error_log("Errore nella query SELECT * FROM filiali: " . $conn->error);
    // Gestisci l'errore (es. mostra un messaggio all'utente)
    $error_query = "Errore nel recupero dei dati delle filiali.";
    $result = false; // Assicura che $result sia falso in caso di errore
}


// --- Fine Blocco Sicurezza e Dati ---

// Nota: Questo file non ha paginazione.

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Filiali</title>
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


        .table-responsive {
            overflow-x: auto;
        }

        /* Standardizza lo stile dei link di azione */
        .action-links a, .action-links form { /* Applica stili anche ai form per allinearli */
            margin-right: 5px; /* Spaziatura tra i bottoni/form */
            margin-bottom: 5px; /* Aggiunto per spaziatura verticale su schermi piccoli */
            display: inline-block; /* Assicura che margin-bottom funzioni e stiano sulla stessa riga */
        }
        .action-links .btn-danger {
            margin-left: 0; /* Rimuove il margin-left superfluo */
            margin-right: 0; /* Gestito dal .action-links a/form */
        }

        /* Stile per i link e bottoni all'interno di action-links */
        .action-links a.btn, .action-links form button.btn {
            padding: .25rem .5rem; /* Padding del btn-sm */
            font-size: .875rem; /* Font size del btn-sm */
            line-height: 1.5;
            border-radius: .2rem; /* Border radius del btn-sm */
            margin: 0; /* Rimuove margini extra sul bottone stesso */
            display: inline-block; /* Assicura che si comportino come inline-block */
        }

        /* Allineamento verticale delle icone */
        .action-links i {
            vertical-align: middle; /* Applica vertical-align per l'allineamento */
            margin-right: 5px; /* Mantiene il margine standard dopo l'icona */
        }

        /* Nascondi colonne su mobile se necessario (non ci sono col-hide-mobile in questo file, ma mantengo la regola generica se vuoi usarla) */
        /* @media (max-width: 767.98px) { .table .col-hide-mobile { display: none; } } */

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
    <h1 class="mb-4 text-center">Gestione Filiali</h1>

    <div class="text-center mb-3">
        <?php
        // Il pulsante Aggiungi è sempre visibile se l'utente ha livello 1
        ?>
        <a href="inserisci_filiale.php" class="btn btn-success"><i class="bi bi-plus-square me-2"></i> Aggiungi Nuova Filiale</a>
    </div>

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
    // Mostra l'errore nella query se presente
    if (isset($error_query)): ?>
        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error_query); ?></div>
    <?php endif; ?>


    <div class="table-responsive shadow-sm">
        <?php
        // Mostra la tabella solo se la query ha avuto successo e ci sono righe
        if ($result && $result->num_rows > 0) {
            echo "<table class='table table-bordered table-striped table-hover'>";
            echo "<thead class='table-light'><tr><th>Divisione</th><th>Nome Filiale</th><th class='text-center'>Azioni</th></tr></thead>"; // Corretto header Nome Filiale
            echo "<tbody>";
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row["divisione"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["filiale"]) . "</td>"; // Corretto output Nome Filiale
                echo "<td class='action-links text-center'>";

                // Azioni Modifica e Cancella (visibili solo a livello 1, che è l'unico a vedere la pagina)
                // Standardizzo il link Modifica per usare POST per URL pulito
                ?>
                <form action="modifica_filiale.php" method="post" style="display:inline;">
                    <input type="hidden" name="divisione" value="<?php echo htmlspecialchars($row["divisione"]); ?>">
                    <input type="hidden" name="filiale" value="<?php echo htmlspecialchars($row["filiale"]); ?>">
                    <button type="submit" class="btn btn-primary btn-sm" title="Modifica Filiale">
                        <i class="bi bi-pencil me-1"></i>Modifica
                    </button>
                </form>
                <?php
                // Il link di cancellazione rimane GET standardizzato
                // Assicurati che cancella_filiale.php sia preparato per ricevere la 'divisione' via GET
                echo "<a href='cancella_filiale.php?divisione=" . htmlspecialchars($row["divisione"]) . "' class='btn btn-danger btn-sm' onclick=\"return confirm('Sei sicuro di voler cancellare questa filiale? Questa azione è irreversibile!')\" title='Cancella Filiale'><i class='bi bi-trash me-1'></i> Cancella</a>"; // Testo pulsante Cancella

                echo "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
        } elseif ($result) { // Query ha avuto successo ma non ci sono righe
            echo "<p class='alert alert-info'>Nessuna filiale registrata.</p>";
        } else { // La query non ha avuto successo (l'errore è già gestito sopra)
            // Messaggio di errore query già mostrato sopra
        }
        $conn->close(); // Chiudi la connessione solo qui
        ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>