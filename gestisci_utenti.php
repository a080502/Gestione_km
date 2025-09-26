<?php
include_once 'config.php'; // Includi configurazione DB
// File: visualizza_utenti.php (Standardizzato)

// --- Inizio Blocco Sicurezza e Dati Standardizzati ---
// Assumi che dati_utente.php gestisca session_start() e il redirect se non loggato
// e che definisca $_SESSION['username']
include 'dati_utente.php'; // Gestisce la sessione e l'eventuale redirect al login


// Verifica che l'utente sia loggato (ridondante se fatto in dati_utente.php, ma sicurezza extra)
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
// Includi file di query
include 'query/qutenti.php'; // Funzioni per interagire con la tabella utenti
// include 'query/q_costo_extra.php'; // Questa inclusione non sembra necessaria in questo file, la rimuovo per pulizia.


// *** Recupera i dati dell'utente LOGGATO in modo sicuro (Standardizzato) ***
$username_loggato = $_SESSION['username'];
$utente_loggato_data = []; // Inizializza l'array
$livello_utente_loggato = 99; // Default a un livello alto per sicurezza


// Usiamo un prepared statement per recuperare i dati dell'utente loggato
$sql_user = $conn->prepare("SELECT id, username, password, targa_mezzo, divisione, filiale, livello, Nome, Cognome, time_stamp FROM utenti WHERE username = ? LIMIT 1");
if ($sql_user) {
    $sql_user->bind_param("s", $username_loggato);
    $sql_user->execute();
    $result_user = $sql_user->get_result();
    if ($result_user->num_rows > 0) {
        $utente_loggato_data = $result_user->fetch_assoc();
        // Aggiungi username per coerenza (anche se già nella chiave 'username')
        $utente_loggato_data['username'] = $username_loggato;
        $livello_utente_loggato = $utente_loggato_data['livello']; // Recupera il livello
    } else {
        // Questa situazione non dovrebbe verificarsi se dati_utente.php e la tabella utenti sono coerenti
        error_log("Utente loggato '$username_loggato' non trovato nel database utenti durante il recupero dati in visualizza_utenti.php.");
        // Reindirizza al logout per sicurezza
        session_destroy(); // Distrugge tutti i dati della sessione corrente
        header("Location: login.php"); // Reindirizza alla pagina di login
        exit();
    }
    $sql_user->close();
} else {
    error_log("Errore preparazione query dati utente (utenti table) in visualizza_utenti.php: " . $conn->error);
    die("Errore critico nel recupero dati utente.");
}

// Assegna i dati dell'utente alla variabile attesa dal menu.php
$utente_data = $utente_loggato_data; // <-- AGGIUNTA QUESTA RIGA PER IL MENU

// Modifica la query per recuperare i dati degli utenti in base al livello di autorizzazione
if ($livello_utente_loggato < 3) {
    // Livello inferiore a 3: visualizza tutti gli utenti
    $sql = "SELECT id, username, password, targa_mezzo, divisione, filiale, livello, Nome, Cognome, time_stamp FROM utenti";
    $result = $conn->query($sql);
} else {
    // Livello 3 o superiore: visualizza solo l'utente corrente
    $sql = "SELECT id, username, password, targa_mezzo, divisione, filiale, livello, Nome, Cognome, time_stamp FROM utenti WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username_loggato);
    $stmt->execute();
    $result = $stmt->get_result();
}

// Controlla se la query principale ha avuto successo
if ($result === false && (isset($stmt) && $stmt->error)) {
    $error_query = "Errore nell'esecuzione della query: " . (isset($stmt) ? $stmt->error : $conn->error);
    error_log("Errore query utenti in visualizza_utenti.php: " . $error_query);
    $result = false; // Assicura che $result sia falso in caso di errore
}


?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Utenti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 80px; /* Standardizzato a 80px */
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
            margin-top: 20px;
        }

        /* Standardizza lo stile dei link di azione */
        /* Applica stili anche ai form per allinearli */
        .action-links a, .action-links form {
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


        /* Applica la classe solo per nascondere su mobile */
        @media (max-width: 767.98px) { /*breakpoint md è 768px */
            .table .col-hide-mobile {
                display: none;
            }
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
    <h1 class="mb-4 text-center">Gestione Utenti</h1>
    <div class="text-center mb-3">
        <?php
        if ($livello_utente_loggato < 3) {
            echo '<a href="registrazione.php" class="btn btn-success"><i class="bi bi-plus-square me-2"></i> Aggiungi Nuovo Utente</a>';
        }
        ?>
    </div>

    <?php
    // Gestione messaggi di sessione (Standardizzato con classi dismissible)
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['error_message']);
    }
    if (isset($_SESSION['registrazione_successo'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['registrazione_successo']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['registrazione_successo']);
    }
    // Mostra l'errore nella query se presente
    if (isset($error_query)): ?>
        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error_query); ?></div>
    <?php endif; ?>


    <div class="table-responsive shadow-sm">
        <?php
        // Mostra la tabella solo se la query ha avuto successo e ci sono risultati
        if ($result && ( ($result instanceof mysqli_result && $result->num_rows > 0) || (isset($result) && $result instanceof mysqli_stmt && $result->num_rows > 0) ) ) { // Aggiunto controllo isset per $result se è stmt
            echo "<table class='table table-bordered table-striped table-hover'>";
            echo "<thead class='table-light'><tr><th>ID</th><th>Nome</th><th>Cognome</th><th>Targa Mezzo</th><th>Filiale</th><th>Autorizzazioni</th><th>Username</th><th class='text-center'>Azioni</th></tr></thead>";
            echo "<tbody>";

            // Se il risultato è da una query diretta (livello < 3)
            if ($result instanceof mysqli_result) {
                $data_rows = $result;
            }
            // Se il risultato è da uno statement preparato (livello >= 3)
            elseif (isset($result) && $result instanceof mysqli_stmt) {
                // Il risultato è già stato ottenuto con get_result() prima
                $data_rows = $result; // $result è già il mysqli_result in questo caso dallo statement
            } else {
                $data_rows = false; // Caso non gestito o errore
            }


            if ($data_rows) {
                while($row = $data_rows->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row["id"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["Nome"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["Cognome"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["targa_mezzo"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["filiale"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["livello"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["username"]) . "</td>";
                    echo "<td class='action-links text-center'>";

                    // Azione Modifica: visibile solo se livello utente loggato < 3
                    if ($livello_utente_loggato < 4) {
                        // Standardizzo il link Modifica per usare POST per URL pulito
                        ?>
                        <form action="modifica_utente.php" method="post" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($row["id"]); ?>">
                            <button type="submit" class="btn btn-primary btn-sm" title="Modifica Utente">
                                <i class="bi bi-pencil me-1"></i>Modifica
                            </button>
                        </form>
                        <?php
                    } else {
                        // Per livelli >= 3, mostra solo un trattino se non ci sono azioni per loro
                        // Nel file utente originale non mostrava azioni per >=3, manteniamo coerenza
                        echo "-";
                    }


                    // Azione Cancella: visibile solo se livello utente loggato < 3
                    if ($livello_utente_loggato < 3) {
                        // Il link di cancellazione rimane GET standardizzato
                        // Assicurati che cancella_utente.php sia preparato per ricevere l'ID via GET
                        echo "<a href='cancella_utente.php?id=" . htmlspecialchars($row["id"]) . "' class='btn btn-danger btn-sm' onclick=\"return confirm('Sei sicuro di voler cancellare questo utente e non sarà piu recuperabile!!')\" title='Cancella Utente'><i class='bi bi-trash me-1'></i> Cancella</a>";
                    }
                    // Se livello >= 3, il pulsante Cancella non viene mostrato qui, coerente con la logica sopra


                    echo "</td>";
                    echo "</tr>";
                }
            }


            echo "</tbody>";
            echo "</table>";

            // Libera il risultato solo se è un mysqli_result da query diretta
            if ($result instanceof mysqli_result) {
                $result->free();
            }
            // Chiudi lo statement preparato se è stato usato
            // L'oggetto $result dello statement viene chiuso automaticamente con lo statement
            // if (isset($stmt) && $stmt instanceof mysqli_stmt) { $stmt->close(); } // Chiuso correttamente dopo l'esecuzione

        } elseif ($result !== false) { // Query ha avuto successo ma non ci sono righe ( $result è 0 o empty)
            echo "<p class='alert alert-info'>Nessun utente registrato.</p>";
        } else { // La query non ha avuto successo (l'errore è già gestito sopra e $result è false)
            // Messaggio di errore query già mostrato sopra
        }

        // Chiudi lo statement preparato se è stato usato e non è stato chiuso prima
        if (isset($stmt) && $stmt instanceof mysqli_stmt) {
            $stmt->close(); // Assicura che lo statement sia chiuso
        }


        $conn->close(); // Chiudi la connessione alla fine
        ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>