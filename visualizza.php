<?php
// --- Inizio Blocco Sicurezza e Dati ---

include_once 'config.php'; // Includi configurazione DB
include 'dati_utente.php'; // Gestisce le informazioni sull'utente corrente (es. sessione)


// Verifica che l'utente sia loggato
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Includi file di query
include 'query/qutenti.php'; // Funzioni per interagire con la tabella utenti
include 'query/q_costo_extra.php'; // Funzioni per recuperare dati relativi ai costi extra
// Recupera i dati dell'utente dal database
$utente_data = get_user_data($conn, $username);

// Ottieni il livello e la divisione dell'utente
$livello = $utente_data['livello'];
$divisione = $utente_data['divisione'];

// Includi o definisci qui come ottenere $dati_utente in modo sicuro
// Esempio: assumendo che dati_utente.php faccia questo e restituisca un array $dati_utente
// include 'dati_utente.php';
// *** Esempio Sostitutivo Sicuro (DA ADATTARE!) ***
// Recupera i dati dell'utente LOGGATO dalla sessione o dal DB usando prepared statements
$username_loggato = $_SESSION['username'];
$dati_utente = []; // Inizializza l'array
$sql_user = $conn->prepare("SELECT Nome, Cognome, targa_mezzo, divisione, filiale FROM utenti WHERE username = ? LIMIT 1");
if ($sql_user) {
    $sql_user->bind_param("s", $username_loggato);
    $sql_user->execute();
    $result_user = $sql_user->get_result();
    if ($result_user->num_rows > 0) {
        $dati_utente = $result_user->fetch_assoc();
        // Aggiungi username all'array per coerenza con il codice precedente, se necessario
        $dati_utente['username'] = $username_loggato;
    } else {
        // Gestione utente non trovato nel DB, anche se loggato?
        error_log("Utente loggato '$username_loggato' non trovato nel database.");
        // Potresti voler fare un logout o mostrare un errore specifico
        // Per ora, lasciamo $dati_utente parzialmente vuoto o con valori di default
        $dati_utente['username'] = $username_loggato; // Almeno lo username
        $dati_utente['Nome'] = 'N/D';
        $dati_utente['Cognome'] = '';
        $dati_utente['targa_mezzo'] = 'N/D';
        $dati_utente['divisione'] = 'N/D';
        $dati_utente['filiale'] = 'N/D';
    }
    $sql_user->close();
} else {
    error_log("Errore preparazione query dati utente: " . $conn->error);
    // Gestione errore DB
    die("Errore nel recupero dati utente.");
}
// --- Fine Blocco Sicurezza e Dati ---


// --- Inizio Blocco Paginazione ---
$limite = 15; // Ridotto per mobile? Valuta tu
$pagina_corrente = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_corrente - 1) * $limite;

// Conta totale record con Prepared Statement
$sql_conta = $conn->prepare("SELECT COUNT(*) as totale FROM chilometri WHERE username = ?");
$totale_record = 0;
if ($sql_conta) {
    $sql_conta->bind_param("s", $dati_utente['username']);
    $sql_conta->execute();
    $result_conta = $sql_conta->get_result();
    $row_conta = $result_conta->fetch_assoc();
    $totale_record = $row_conta['totale'];
    $sql_conta->close();
} else {
        error_log("Errore preparazione query conteggio: " . $conn->error);
        // Gestione errore DB
}
$totale_pagine = ($limite > 0) ? ceil($totale_record / $limite) : 0;
// --- Fine Blocco Paginazione ---


// --- Inizio Blocco Recupero Record Pagina ---
// Recupera record per la pagina corrente con Prepared Statement
$sql_records = $conn->prepare("SELECT * FROM chilometri WHERE username = ? ORDER BY data DESC, id DESC LIMIT ? OFFSET ?");
$records = []; // Inizializza array risultati
if ($sql_records) {
    $sql_records->bind_param("sii", $dati_utente['username'], $limite, $offset);
    $sql_records->execute();
    $result_records = $sql_records->get_result();
    while ($row = $result_records->fetch_assoc()) {
        $records[] = $row;
    }
    $sql_records->close();
} else {
    error_log("Errore preparazione query recupero record: " . $conn->error);
    // Gestione errore DB
}
// --- Fine Blocco Recupero Record Pagina ---

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizza Record KM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            /* Aggiunge padding in alto per non sovrapporre contenuto al top fisso */
            /* L'altezza effettiva dipende dal contenuto di .fixed-top-elements */
            padding-top: 80px; /* Regola questo valore se necessario */
        }

        .fixed-top-elements {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background-color: #e9ecef; /* Sfondo leggermente diverso per distinguerlo */
            padding: 10px 15px;
            z-index: 1030;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #dee2e6;
            flex-wrap: wrap; /* Permette al contenuto di andare a capo se non c'è spazio */
        }

        .menu-btn {
            font-size: 1.2rem;
        }

        .user-info-display {
            font-size: 0.8rem; /* Testo più piccolo per info utente */
            text-align: right;
            color: #495057;
            line-height: 1.3; /* Migliora leggibilità su più righe */
        }
         .user-info-display strong {
             display: inline-block; /* O block se vuoi ogni etichetta su nuova riga */
             margin-right: 5px;
        }

        /* Offcanvas (opzionale, Bootstrap ha buoni default) */
        .offcanvas-header { border-bottom: 1px solid #dee2e6; }
        .offcanvas-body .nav-link { padding: 0.8rem 1.5rem; font-size: 1.1rem; color: #212529; }
        .offcanvas-body .nav-link:hover { background-color: #e9ecef; }
        .offcanvas-body hr { margin: 1rem 1.5rem; }

        /* Tabella */
        .table th, .table td {
            vertical-align: middle; /* Allinea verticalmente al centro */
        }
        .table .action-icons a {
            margin: 0 3px; /* Spazio tra le icone azione */
            text-decoration: none;
        }
         .table .action-icons .bi-pencil-square { color: var(--bs-warning); } /* Icona modifica */
         .table .action-icons .bi-trash-fill { color: var(--bs-danger); } /* Icona cancella */
         .table .action-icons .bi-eye-fill { color: var(--bs-primary); } /* Icona cedolino */


        /* Paginazione: Centratura e spazio */
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 1.5rem;
            flex-wrap: wrap; /* Va a capo su schermi molto piccoli */
        }

        /* --- Strategia Colonne Nascoste (Opzionale) ---
             Nasconde colonne meno importanti su schermi piccoli (sotto md breakpoint)
             Puoi aggiungere la classe .d-none .d-md-table-cell alle <th> e <td>
             che vuoi nascondere su mobile/tablet piccolo.
        */
        /*
        @media (max-width: 767.98px) {
            .table .col-hide-mobile {
                display: none;
            }
        }
        */

        /* Stili per l'anteprima immagine */
        #image-preview-container {
            position: absolute;
            background-color: #fff;
            border: 1px solid #ccc;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
            padding: 5px;
            z-index: 1050; /* Sopra altri elementi */
            display: none; /* Inizialmente nascosto */
            max-width: 900px;
            max-height: 900px;
            overflow: hidden;
        }
        #image-preview-container img {
            display: block;
            max-width: 100%;
            height: auto;
        }

    </style>
</head>
<body>

    <div class="fixed-top-elements">
        <button class="btn btn-outline-secondary menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMenu" aria-controls="mainMenu">
            <i class="bi bi-list"></i> Menu </button>
        <div class="user-info-display">
            <strong>Utente:</strong> <?php echo htmlspecialchars($dati_utente['username']); ?><br>
        </div>
    </div>

    <?php include 'include/menu.php'; ?>

    <div class="container" id="main-content">

        <h1 class="mb-4 h3">Elenco Registrazioni Chilometri</h1>

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
                    <caption><?php echo "Record totali: " . $totale_record . " - Pagina " . $pagina_corrente . " di " . $totale_pagine; ?></caption>
                <thead class="table-light">
                    <tr>
                        <th>Data</th>
                        <th class="col-hide-mobile">Targa</th>
                        <th class="col-hide-mobile">Divisione</th>
                        <th class="col-hide-mobile">Filiale</th>
                        <th class="text-end">Km Iniz.</th>
                        <th class="text-end">Km Fin.</th>
                        <th class="text-end">Litri</th>
                        <th class="text-end">Euro</th>
                        <th class="col-hide-mobile">Note</th> <th class="text-center">Cedolino</th>
                        <th class="text-center">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($records)): ?>
                        <?php foreach ($records as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date("d/m/y", strtotime($row["data"]))); ?></td>
                                <td class="col-hide-mobile"><?php echo htmlspecialchars($dati_utente['targa_mezzo'] ?? 'N/D'); ?></td>
                                <td class="col-hide-mobile"><?php echo htmlspecialchars($dati_utente['divisione'] ?? 'N/D'); ?></td>
                                <td class="col-hide-mobile"><?php echo htmlspecialchars($dati_utente['filiale'] ?? 'N/D'); ?></td>
                                <td class="text-end"><?php echo htmlspecialchars(number_format($row["chilometri_iniziali"], 0, ',', '.')); ?></td>
                                <td class="text-end"><?php echo htmlspecialchars(number_format($row["chilometri_finali"], 0, ',', '.')); ?></td>
                                <td class="text-end"><?php echo htmlspecialchars(number_format($row["litri_carburante"], 2, ',', '.')); ?></td>
                                <td class="text-end"><?php echo htmlspecialchars(number_format($row["euro_spesi"], 2, ',', '.')); ?> €</td>
                                <td class="col-hide-mobile"><?php echo !empty($row["note"]) ? htmlspecialchars($row["note"]) : '-'; ?></td>
                                <td class="text-center action-icons">
                                    <?php if (!empty($row["percorso_cedolino"]) && file_exists($row["percorso_cedolino"])): ?>
                                        <a href="<?php echo htmlspecialchars($row["percorso_cedolino"]); ?>" target="_blank" title="Visualizza Cedolino" data-cedolino-url="<?php echo htmlspecialchars($row["percorso_cedolino"]); ?>" class="cedolino-preview-link">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                    <?php else: ?>
                                        <i class="bi bi-image text-muted" title="Cedolino non disponibile"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center action-icons">
                                    <a href="modifica.php?id=<?php echo $row["id"]; ?>" title="Modifica Record">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="cancella.php?id=<?php echo $row["id"]; ?>" title="Cancella Record" onclick="return confirm('Sei sicuro di voler cancellare questo record?');">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center fst-italic text-muted p-3">Nessun record trovato per questa pagina o per questo utente.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div><?php if ($totale_pagine > 1): ?>
        <nav aria-label="Navigazione pagine" class="pagination-container">
            <ul class="pagination">
                <li class="page-item <?php echo ($pagina_corrente <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?pagina=<?php echo $pagina_corrente - 1; ?>" aria-label="Precedente">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>

                <?php
                // Logica per mostrare un numero limitato di pagine (es. +/- 2 dalla corrente)
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
        <div id="image-preview-container">
            <img src="" alt="Anteprima Cedolino">
        </div>
    </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script>
        // Opzionale: Inizializza eventuali tooltip di Bootstrap, se li usi
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl)
        })

        // Gestione anteprima cedolino
        const previewContainer = document.getElementById('image-preview-container');
        const previewImage = previewContainer.querySelector('img');
        const cedolinoLinks = document.querySelectorAll('.cedolino-preview-link');

        cedolinoLinks.forEach(link => {
            link.addEventListener('mouseover', (event) => {
                const imageUrl = link.dataset.cedolinoUrl;
                if (imageUrl) {
                    previewImage.src = imageUrl;
                    previewContainer.style.display = 'block';
                    // Posiziona l'anteprima vicino al cursore
                    previewContainer.style.left = (event.pageX + 10) + 'px';
                    previewContainer.style.top = (event.pageY + 10) + 'px';
                } else {
                    previewContainer.style.display = 'none';
                }
            });

            link.addEventListener('mouseout', () => {
                previewContainer.style.display = 'none';
            });
        });

        // Nascondi l'anteprima se il mouse esce dal contenitore
        previewContainer.addEventListener('mouseleave', () => {
            previewContainer.style.display = 'none';
        });
    </script>
</body>
</html>
<?php
// Chiudi la connessione al database se aperta
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>