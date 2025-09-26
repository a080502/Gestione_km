<?php
// File: gestione_costo_extra.php (Basato su visualizza_target.php)

// --- Inizio Blocco Sicurezza e Dati ---
include 'config.php'; // Includi configurazione DB

// Assumi che dati_utente.php gestisca la sessione e il redirect se non loggato
// e che definisca $_SESSION['username'] e recuperi i dati utente incluso 'livello' e 'targa_mezzo'
include 'dati_utente.php'; // Gestisce le informazioni sull'utente corrente

// Verifica che l'utente sia loggato (ridondante se fatto in dati_utente.php, ma sicurezza extra)
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); // Assicurati che login.php esista
    exit();
}

// Includi il file di query specifico per la tabella costo_extra
// *** ASSICURATI CHE QUESTO FILE ESISTA E CONTENGA LE FUNZIONI NECESSARIE ***
include 'query/q_costo_extra.php';

// *** Recupera i dati dell'utente LOGGATO in modo sicuro (da dati_utente.php) ***
// Assumiamo che $dati_utente (array) e $livello_utente (int) siano definiti in dati_utente.php
// Se non definiti lì, recuperali come nello script originale:
if (!isset($dati_utente) || !isset($livello_utente)) {
    // Blocco di recupero dati utente (come in visualizza_target.php)
    $username_loggato = $_SESSION['username'];
    $dati_utente = []; // Inizializza l'array
    $livello_utente = 99; // Default a un livello alto per sicurezza
    $targa_utente = null; // Targa dell'utente loggato

    $sql_user = $conn->prepare("SELECT Nome, Cognome, targa_mezzo, divisione, filiale, livello FROM utenti WHERE username = ? LIMIT 1");
    if ($sql_user) {
        $sql_user->bind_param("s", $username_loggato);
        $sql_user->execute();
        $result_user = $sql_user->get_result();
        if ($result_user->num_rows > 0) {
            $dati_utente = $result_user->fetch_assoc();
            $dati_utente['username'] = $username_loggato; // Aggiungi username
            $livello_utente = $dati_utente['livello']; // Recupera il livello
            $targa_utente = $dati_utente['targa_mezzo']; // Recupera la targa
        } else {
            error_log("Utente loggato '$username_loggato' non trovato nel database utenti.");
            // Gestione utente non trovato
            $dati_utente['username'] = $username_loggato;
            // ... valori di default ...
        }
        $sql_user->close();
    } else {
        error_log("Errore preparazione query dati utente: " . $conn->error);
        die("Errore nel recupero dati utente.");
    }
} else {
    // Se $dati_utente è già definito, assicurati che contenga la targa
    $targa_utente = isset($dati_utente['targa_mezzo']) ? $dati_utente['targa_mezzo'] : null;
}
// --- Fine Blocco Sicurezza e Dati ---


// --- Inizio Blocco Paginazione ---
$limite = 15; // Record per pagina
$pagina_corrente = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_corrente - 1) * $limite;

// Conta totale record dalla tabella costo_extra usando la funzione dedicata
// Passiamo la targa e il livello per filtrare se necessario (livello >= 3)
// *** ASSICURATI CHE count_costo_extra_by_user ESISTA IN q_costo_extra.php ***
$totale_record = count_costo_extra_by_user($conn, $targa_utente, $livello_utente);

$totale_pagine = ($limite > 0) ? ceil($totale_record / $limite) : 0;
// --- Fine Blocco Paginazione ---


// --- Inizio Blocco Recupero Record Pagina ---
// Recupera record per la pagina corrente dalla tabella costo_extra
// Passiamo targa e livello per filtrare se necessario (livello >= 3)
// *** ASSICURATI CHE get_costo_extra_by_user ESISTA IN q_costo_extra.php ***
$records = get_costo_extra_by_user($conn, $targa_utente, $livello_utente, $limite, $offset);
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
            /* Includi qui lo stesso CSS del file visualizza_target.php */
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
            .table .action-icons a { margin: 0 3px; text-decoration: none; }
            .table .action-icons .bi-pencil-square { color: var(--bs-warning); }
            .table .action-icons .bi-trash-fill { color: var(--bs-danger); }
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
            <strong>Utente:</strong> <?php echo htmlspecialchars($dati_utente['username']); ?><br>
            <?php if ($targa_utente): ?>
                <strong>Targa:</strong> <?php echo htmlspecialchars($targa_utente); ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'include/menu.php'; // Assicurati che questo file esista ?>

    <div class="container" id="main-content">

        <h1 class="mb-4 h3">Elenco Costi Extra Veicoli</h1>
        <?php
        // Pulsante Aggiungi: solo per livelli < 3
        if ($livello_utente < 3) {
            // *** ASSICURATI CHE inserisci_costo_extra.php ESISTA ***
            echo '<a href="inserisci_costo_extra.php" class="btn btn-success mb-3"><i class="bi bi-plus-square me-2"></i> Aggiungi Costo Extra</a>';
        }
        ?>

        <?php if (isset($_GET['messaggio'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['messaggio']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['errore'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['errore']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive shadow-sm">
            <table class="table table-sm table-striped table-bordered table-hover caption-top">
                <caption><?php echo "Costi extra totali: " . $totale_record . " - Pagina " . $pagina_corrente . " di " . $totale_pagine; ?></caption>
                <thead class="table-light">
                <tr>
                    <th>Targa Mezzo</th>
                    <th class="text-end">Costo (€)</th>
                    <th>Data/Ora Registrazione</th>
                    <th class="text-center">Azioni</th>
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
                            <td>
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
                            <td class="text-center action-icons">
                                <?php
                                // Condizione per Modifica: Livello < 3 O (Livello >= 3 E Targa corrisponde)
                                $can_edit = ($livello_utente < 3 || ($livello_utente >= 3 && $targa_utente !== null && $row['targa_mezzo'] === $targa_utente));

                                // Condizione per Cancella: Solo Livello < 3
                                $can_delete = ($livello_utente < 3);

                                if ($can_edit) {
                                    // *** ASSICURATI CHE modifica_costo_extra.php ESISTA ***
                                    echo '<a href="modifica_costo_extra.php?id=' . htmlspecialchars($row["id"]) . '" title="Modifica Costo">';
                                    echo '<i class="bi bi-pencil-square"></i>';
                                    echo '</a>';
                                }

                                if ($can_delete) {
                                    // *** ASSICURATI CHE cancella_costo_extra.php ESISTA ***
                                    echo '<a href="cancella_costo_extra.php?id=' . htmlspecialchars($row["id"]) . '" title="Cancella Costo" onclick="return confirm(\'Sei sicuro di voler cancellare questo costo extra?\');">';
                                    echo '<i class="bi bi-trash-fill"></i>';
                                    echo '</a>';
                                }

                                // Se nessuna azione è permessa
                                if (!$can_edit && !$can_delete) {
                                    echo '-';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center fst-italic text-muted p-3">Nessun costo extra trovato per questa pagina o per questo utente/targa.</td>
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