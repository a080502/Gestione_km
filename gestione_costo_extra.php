<?php
// File: gestione_costo_extra.php (Standardizzato - Correzione Layout Pulsanti Azione)

// --- Inizio Blocco Sicurezza e Dati Standardizzati ---
include_once 'config.php'; // Includi configurazione DB

// Assumi che dati_utente.php gestisca la sessione e il redirect se non loggato
// e che definisca $_SESSION['username']
include 'dati_utente.php'; // Gestisce la sessione e l'eventuale redirect al login

// Verifica che l'utente sia loggato
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); // Reindirizza alla pagina di login
    exit();
}

// Includi il file di query specifico per la tabella costo_extra
// Assicurati che questo file esista e contenga le funzioni necessarie
include 'query/q_costo_extra.php'; // count_costo_extra_by_user, get_costo_extra_by_user

// *** Recupera i dati dell'utente LOGGATO in modo sicuro (Standardizzato) ***
$username_loggato = $_SESSION['username'];
$utente_loggato_data = []; // Inizializza l'array
$livello_utente_loggato = 99; // Default a un livello alto per sicurezza

// Usiamo un prepared statement per recuperare i dati dell'utente loggato dalla tabella utenti
// Includiamo anche la targa_mezzo che è necessaria per filtrare i costi extra per i livelli >= 3
$sql_user = $conn->prepare("SELECT Nome, Cognome, targa_mezzo, divisione, filiale, livello FROM utenti WHERE username = ? LIMIT 1");
if ($sql_user) {
    $sql_user->bind_param("s", $username_loggato);
    $sql_user->execute();
    $result_user = $sql_user->get_result();
    if ($result_user->num_rows > 0) {
        $utente_loggato_data = $result_user->fetch_assoc();
        // Aggiungi username per coerenza, anche se è già nella sessione
        $utente_loggato_data['username'] = $username_loggato;
        $livello_utente_loggato = $utente_loggato_data['livello']; // Recupera il livello
        // $utente_loggato_data['targa_mezzo'] contiene già la targa, se presente
    } else {
        // Questa situazione non dovrebbe verificarsi se dati_utente.php e la tabella utenti sono coerenti
        error_log("Utente loggato '$username_loggato' non trovato nel database utenti durante il recupero dati in gestione_costo_extra.php.");
        // Gestione utente non trovato nel DB (improbabile se loggato correttamente)
        // Reindirizza al logout per sicurezza
        session_destroy(); // Distrugge tutti i dati della sessione corrente
        header("Location: login.php"); // Reindirizza alla pagina di login
        exit();
    }
    $sql_user->close();
} else {
    error_log("Errore preparazione query dati utente (utenti table) in gestione_costo_extra.php: " . $conn->error);
    die("Errore critico nel recupero dati utente.");
}
// --- Fine Blocco Sicurezza e Dati Standardizzati ---


// --- Inizio Blocco Paginazione ---
$limite = 15; // Record per pagina
$pagina_corrente = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_corrente - 1) * $limite;

// Conta totale record dalla tabella costo_extra usando la funzione dedicata
// Passiamo la targa e il livello per filtrare se necessario (livello >= 3)
// Assicurati che queste funzioni in q_costo_extra.php gestiscano correttamente il filtro
$totale_record = count_costo_extra_by_user($conn, $utente_loggato_data['targa_mezzo'], $livello_utente_loggato);

$totale_pagine = ($limite > 0 && $totale_record > 0) ? ceil($totale_record / $limite) : 1; // Almeno una pagina se non ci sono record
// Assicura che la pagina corrente non superi il totale delle pagine
if ($pagina_corrente > $totale_pagine) {
    // Reindirizza all'ultima pagina valida
    header("Location: gestione_costo_extra.php?pagina=" . $totale_pagine);
    exit();
}

// Assegna i dati dell'utente alla variabile attesa dal menu.php
$utente_data = $utente_loggato_data; // <-- AGGIUNTA QUESTA RIGA PER IL MENU
// --- Fine Blocco Paginazione ---


// --- Inizio Blocco Recupero Record Pagina ---
// Recupera record per la pagina corrente dalla tabella costo_extra
// Passiamo targa e livello per filtrare se necessario (livello >= 3)
// Assicurati che queste funzioni in q_costo_extra.php gestiscano correttamente il filtro
$records = get_costo_extra_by_user($conn, $utente_loggato_data['targa_mezzo'], $livello_utente_loggato, $limite, $offset);
// --- Fine Blocco Recupero Record Pagina ---

?>
    <!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Visualizza Costi Extra</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <style>
            /* Standardizza lo stile CSS */
            body { background-color: #f8f9fa; padding-top: 80px; }
            .fixed-top-elements { position: fixed; top: 0; left: 0; right: 0; background-color: #e9ecef; padding: 10px 15px; z-index: 1030; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #dee2e6; flex-wrap: wrap; }
            .menu-btn { font-size: 1.2rem; }
            .user-info-display { font-size: 0.8rem; text-align: right; color: #495057; line-height: 1.3; }
            .user-info-display strong { display: inline-block; margin-right: 5px; }
            .offcanvas-header { border-bottom: 1px solid #dee2e6; }
            .offcanvas-body .nav-link { padding: 0.8rem 1.5rem; font-size: 1.1rem; color: #212529; }
            .offcanvas-body .nav-link:hover { background-color: #e9ecef; }
            .offcanvas-body hr { margin: 1rem 1.5rem; }
            .table th, .table td { vertical-align: middle; }

            /* Standardizza lo stile dei link di azione */
            .action-links a, .action-links form { /* Applica stili anche ai form per allinearli */
                margin-right: 5px; /* Spaziatura tra i bottoni/form */
                margin-bottom: 5px; /* Aggiunto per spaziatura verticale su schermi piccoli */
                display: inline-block; /* Assicura che margin-bottom funzioni e stiano sulla stessa riga */
                /* Rimuovi flexbox da questi contenitori */
            }

            /* Stile per i link e bottoni all'interno di action-links */
            .action-links a.btn, .action-links form button.btn {
                /* Queste classi sono già presenti sugli elementi */
                /* Rimuovi display flex diretto sui bottoni */
                /* display: flex; */
                /* align-items: center; */
                /* justify-content: center; */
                /* Assicurati che si comportino come inline-block se necessario,
                   ma di solito le classi btn e btn-sm gestiscono già il display */
                display: inline-block; /* Bootstrap btn-sm è inline-flex, a volte inline-block è più prevedibile qui */

                padding: .25rem .5rem; /* Padding del btn-sm */
                font-size: .875rem; /* Font size del btn-sm */
                line-height: 1.5; /* Potrebbe aiutare */
                border-radius: .2rem; /* Border radius del btn-sm */
                margin: 0; /* Rimuovi margini extra sul bottone stesso */

            }

            .action-links .btn-danger {
                margin-left: 0; /* Rimuove il margin-left superfluo */
                margin-right: 0; /* Gestito dal .action-links a/form */
            }


            /* CORREZIONE: Allineamento verticale delle icone */
            /* Applica vertical-align solo alle icone */
            .action-links i {
                vertical-align: middle; /* Applica vertical-align per l'allineamento */
            }


            .pagination-container { display: flex; justify-content: center; margin-top: 1.5rem; flex-wrap: wrap; }
            /* Nascondi colonne su mobile se necessario */
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
            <?php if (isset($utente_loggato_data['targa_mezzo']) && $utente_loggato_data['targa_mezzo']): ?>
                <br><strong>Targa:</strong> <?php echo htmlspecialchars($utente_loggato_data['targa_mezzo']); ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'include/menu.php'; // Assicurati che questo file esista ?>

    <div class="container" id="main-content">

        <h1 class="mb-4 h3">Elenco Costi Extra Veicoli</h1>

        <div class="text-center mb-3">
            <?php
            // Pulsante Aggiungi: solo per livelli < 3 (Standardizzato)
            if ($livello_utente_loggato < 3) {
                // *** ASSICURATI CHE inserisci_extra.php ESISTA ***
                echo '<a href="inserisci_extra.php" class="btn btn-success"><i class="bi bi-plus-square me-2"></i> Aggiungi Costo Extra</a>';
            }
            ?>
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
        // Rimuovi o adatta altri messaggi se non pertinenti
        ?>

        <div class="table-responsive shadow-sm">
            <table class="table table-sm table-striped table-bordered table-hover caption-top">
                <caption><?php echo "Costi extra totali: " . htmlspecialchars($totale_record) . " - Pagina " . htmlspecialchars($pagina_corrente) . " di " . htmlspecialchars($totale_pagine); ?></caption>
                <thead class="table-light">
                <tr>
                    <th>Targa Mezzo</th>
                    <th class="text-end">Costo (€)</th>
                    <th class="col-hide-mobile">Data/Ora Registrazione</th>
                    <?php if ($livello_utente_loggato < 3): ?>
                        <th class="text-center">Azioni</th>
                    <?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($records)): ?>
                    <?php foreach ($records as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row["targa_mezzo"]); ?></td>
                            <td class="text-end">
                                <?php
                                // Formatta il costo come valuta
                                echo htmlspecialchars(number_format($row["costo"], 2, ',', '.'));
                                ?>
                            </td>
                            <td class="col-hide-mobile">
                                <?php
                                // Formatta il timestamp
                                try {
                                    $date = new DateTime($row["time_stamp"]);
                                    echo htmlspecialchars($date->format('d/m/Y H:i:s'));
                                } catch (Exception $e) {
                                    echo htmlspecialchars($row["time_stamp"]); // Mostra raw se formato non valido
                                }
                                ?>
                            </td>
                            <?php if ($livello_utente_loggato < 3): ?>
                                <td class='action-links text-center'>
                                    <?php
                                    // Ripristina il form POST per il pulsante Modifica per compatibilità
                                    // con modifica_costo_extra.php
                                    ?>
                                    <form action="modifica_costo_extra.php" method="post" style="display:inline;">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($row["id"]); ?>">
                                        <button type="submit" class="btn btn-primary btn-sm" title="Modifica Costo">
                                            <i class="bi bi-pencil me-1"></i>Modifica
                                        </button>
                                    </form>

                                    <?php
                                    // Il link di cancellazione rimane GET come standardizzato
                                    // Assicurati che cancella_costo_extra.php sia preparato per ricevere l'ID via GET
                                    ?>
                                    <a href='cancella_costo_extra.php?id=<?php echo htmlspecialchars($row["id"]); ?>' class='btn btn-danger btn-sm' title='Cancella Costo' onclick="return confirm('Sei sicuro di voler cancellare questo costo extra?')">
                                        <i class='bi bi-trash me-1'></i>Cancella
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo ($livello_utente_loggato < 3) ? '4' : '3'; ?>" class="text-center fst-italic text-muted p-3">Nessun costo extra trovato per questa pagina o per questo utente/targa.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totale_pagine > 1): ?>
            <nav aria-label="Navigazione pagine" class="pagination-container">
                <ul class="pagination">
                    <li class="page-item <?php echo ($pagina_corrente <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $pagina_corrente - 1; ?>" aria-label="Precedente">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>

                    <?php
                    $range = 2;
                    $start = max(1, $pagina_corrente - $range);
                    $end = min($totale_pagine, $pagina_corrente + $range);

                    if ($start > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?pagina=1">1</a></li>';
                        if ($start > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }

                    for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?php echo ($i == $pagina_corrente) ? 'active' : ''; ?>" <?php echo ($i == $pagina_corrente) ? 'aria-current="page"' : ''; ?>>
                            <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor;

                    if ($end < $totale_pagine) {
                        if ($end < $totale_pagine - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="?pagina='.$totale_pagine.'">'.$totale_pagine.'</a></li>';
                    }
                    ?>

                    <li class="page-item <?php echo ($pagina_corrente >= $totale_pagine) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $pagina_corrente + 1; ?>" aria-label="Successivo">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script>
        // Opzionale: Inizializza tooltip
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
    </body>
    </html>
<?php
// Chiudi la connessione al database se aperta
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>