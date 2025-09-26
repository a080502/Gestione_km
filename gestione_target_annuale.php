<?php
// File: visualizza_target.php (Standardizzato - Modifica via POST)

// --- Inizio Blocco Sicurezza e Dati Standardizzati ---
include_once 'config.php'; // Includi configurazione DB

// Assumi che dati_utente.php gestisca la sessione e il redirect se non loggato
// e che definisca $_SESSION['username']
include 'dati_utente.php'; // Gestisce le informazioni sull'utente corrente (es. sessione)

// Verifica che l'utente sia loggato (ridondante se fatto in dati_utente.php, ma sicurezza extra)
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); // Assicurati che login.php esista
    exit();
}

// Includi il file di query specifico per la tabella T
include 'query/q_target_km.php'; // Contiene count_target_km_by_user e get_target_km_by_user

// *** Recupera i dati dell'utente LOGGATO in modo sicuro (Standardizzato) ***
// Usiamo lo stesso nome variabile del file utenti per coerenza
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
        // Aggiungi username per coerenza, anche se è già nella sessione
        $utente_loggato_data['username'] = $username_loggato;
        $livello_utente_loggato = $utente_loggato_data['livello']; // Recupera il livello
    } else {
        // Questa situazione non dovrebbe verificarsi se dati_utente.php funziona correttamente
        error_log("Utente loggato '$username_loggato' non trovato nel database utenti durante il recupero dati in visualizza_target.php.");
        // Potresti voler reindirizzare a una pagina di errore o logout qui
        // Esempio: header("Location: logout.php"); exit();
        // Per ora, impostiamo dati di default e un livello alto
        $utente_loggato_data['username'] = $username_loggato;
        $utente_loggato_data['Nome'] = 'N/D'; // Valori di default
        $utente_loggato_data['Cognome'] = '';
        $utente_loggato_data['targa_mezzo'] = 'N/D';
        $utente_loggato_data['divisione'] = 'N/D';
        $utente_loggato_data['filiale'] = 'N/D';
        // $livello_utente_loggato rimane 99
    }
    $sql_user->close();
} else {
    error_log("Errore preparazione query dati utente (utenti table) in visualizza_target.php: " . $conn->error);
    die("Errore critico nel recupero dati utente.");
}
// --- Fine Blocco Sicurezza e Dati Standardizzati ---
// Assegna i dati dell'utente alla variabile attesa dal menu.php
$utente_data = $utente_loggato_data; // <-- AGGIUNTA QUESTA RIGA PER IL MENU

// --- Inizio Blocco Paginazione ---
$limite = 15; // Numero di record per pagina
$pagina_corrente = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_corrente - 1) * $limite;

// Conta totale record dalla tabella T usando la funzione dedicata, PASSANDO USERNAME e LIVELLO STANDARDIZZATI
$totale_record = count_target_km_by_user($conn, $utente_loggato_data['username'], $livello_utente_loggato);

$totale_pagine = ($limite > 0) ? ceil($totale_record / $limite) : 0;
// Assicura che la pagina corrente non superi il totale delle pagine se il totale record cambia
if ($pagina_corrente > $totale_pagine && $totale_pagine > 0) {
    // Reindirizza alla prima pagina o all'ultima valida
    header("Location: visualizza_target.php?pagina=" . $totale_pagine);
    exit();
} elseif ($totale_pagine == 0) {
    $pagina_corrente = 1; // Imposta a 1 se non ci sono pagine (nessun record)
}


// --- Fine Blocco Paginazione ---


// --- Inizio Blocco Recupero Record Pagina ---
// Recupera record per la pagina corrente dalla tabella T usando la funzione dedicata, PASSANDO USERNAME e LIVELLO STANDARDIZZATI
$records = get_target_km_by_user($conn, $utente_loggato_data['username'], $livello_utente_loggato, $limite, $offset);
// --- Fine Blocco Recupero Record Pagina ---

?>
    <!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Visualizza Target KM</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <style>
            /* Includi qui lo stesso CSS del file visualizza.php */
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

            .offcanvas-header { border-bottom: 1px solid #dee2e6; }
            .offcanvas-body .nav-link { padding: 0.8rem 1.5rem; font-size: 1.1rem; color: #212529; }
            .offcanvas-body .nav-link:hover { background-color: #e9ecef; }
            .offcanvas-body hr { margin: 1rem 1.5rem; }

            .table th, .table td {
                vertical-align: middle;
            }

            /* Standardizza lo stile dei link di azione come nel file utenti */
            /* Applica stili anche ai form per allinearli */
            .action-links a, .action-links form {
                margin-right: 5px; /* Spaziatura tra i bottoni/form */
                margin-bottom: 5px; /* Aggiunto per spaziatura verticale su schermi piccoli */
                display: inline-block; /* Assicura che margin-bottom funzioni e stiano sulla stessa riga */
            }
            /* Aggiunto margine per separare bottoni */
            .action-links .btn-danger {
                margin-left: 0; /* Rimuove il margin-left superfluo se .action-links ha margin-right */
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
            }


            .pagination-container {
                display: flex;
                justify-content: center;
                margin-top: 1.5rem;
                flex-wrap: wrap;
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

    <?php include 'include/menu.php'; // Assicurati che questo file esista e contenga il menu Bootstrap ?>

    <div class="container" id="main-content">

        <h1 class="mb-4 h3">Elenco Target Chilometri</h1>

        <div class="text-center mb-3">
            <?php
            // Mostra il pulsante Aggiungi solo se il livello utente è < 3
            if ($livello_utente_loggato < 3) {
                echo '<a href="imposta_target.php" class="btn btn-success"><i class="bi bi-plus-square me-2"></i> Aggiungi Nuovo Target</a>';
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
        // Rimuovi il messaggio di registrazione successo se non pertinente qui, altrimenti standardizza il nome
        if (isset($_SESSION['registrazione_successo'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['registrazione_successo']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            unset($_SESSION['registrazione_successo']);
        }

        ?>


        <div class="table-responsive shadow-sm">
            <table class="table table-sm table-striped table-bordered table-hover caption-top">
                <caption><?php echo "Target totali: " . htmlspecialchars($totale_record) . " - Pagina " . htmlspecialchars($pagina_corrente) . " di " . htmlspecialchars($totale_pagine); ?></caption>
                <thead class="table-light">
                <tr>
                    <th>Anno</th>
                    <th class="text-end">Target KM</th>
                    <th class="col-hide-mobile">Username</th>
                    <th class="col-hide-mobile">Targa</th>
                    <th class="col-hide-mobile">Divisione</th>
                    <th class="col-hide-mobile">Filiale</th>
                    <?php if ($livello_utente_loggato < 3): ?>
                        <th class="text-center">Azioni</th>
                    <?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($records)): ?>
                    <?php foreach ($records as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row["anno"]); ?></td>
                            <td class="text-end"><?php echo htmlspecialchars(number_format($row["target_chilometri"], 0, ',', '.')); ?></td>
                            <td class="col-hide-mobile"><?php echo htmlspecialchars($row["username"]); ?></td>
                            <td class="col-hide-mobile"><?php echo htmlspecialchars($row["targa_mezzo"]); ?></td>
                            <td class="col-hide-mobile"><?php echo htmlspecialchars($row["divisione"]); ?></td>
                            <td class="col-hide-mobile"><?php echo htmlspecialchars($row["filiale"]); ?></td>
                            <?php if ($livello_utente_loggato < 3): ?>
                                <td class='action-links text-center'>
                                    <form action="modifica_target.php" method="post" style="display:inline;">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($row["id"]); ?>">
                                        <button type="submit" class="btn btn-primary btn-sm" title="Modifica Target">
                                            <i class="bi bi-pencil me-1"></i>Modifica
                                        </button>
                                    </form>
                                    <a href='cancella_target.php?id=<?php echo htmlspecialchars($row["id"]); ?>' class='btn btn-danger btn-sm' title='Cancella Target' onclick=\"return confirm('Sei sicuro di voler cancellare questo record target?')\">
                                    <i class='bi bi-trash me-1'></i>Cancella
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo ($livello_utente_loggato < 3) ? '7' : '6'; ?>" class="text-center fst-italic text-muted p-3">Nessun record target trovato per questa pagina o per questo utente.</td>
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
                    // Logica per mostrare un numero limitato di pagine
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
        // Opzionale: Inizializza eventuali tooltip di Bootstrap, se li usi
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