<?php
// File: modifica_utente.php (Standardizzato)

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

// Includi file di query
include 'query/qutenti.php'; // Funzioni per interagire con la tabella utenti
include 'query/q_costo_extra.php'; // Funzioni per recuperare dati relativi ai costi extra

// *** Recupera i dati dell'utente LOGGATO in modo sicuro (Standardizzato) ***
$username_loggato = $_SESSION['username'];
$utente_loggato_data = []; // Inizializza l'array
$dati_utente = [];
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
        // Questa situazione non dovrebbe verificarsi
        error_log("Utente loggato '$username_loggato' non trovato nel database utenti durante il recupero dati in modifica_utente.php.");
        // Reindirizza al logout per sicurezza
        session_destroy(); // Distrugge tutti i dati della sessione corrente
        header("Location: login.php"); // Reindirizza alla pagina di login
        exit();
    }
    $sql_user->close();
} else {
    error_log("Errore preparazione query dati utente (utenti table) in modifica_utente.php: " . $conn->error);
    die("Errore critico nel recupero dati utente.");
}
// Assegna i dati dell'utente alla variabile attesa dal menu.php
$utente_data = $utente_loggato_data; // <-- AGGIUNTA QUESTA RIGA PER IL MENU
// --- CONTROLLO PERMESSI (Solo livelli < 3 possono accedere alla modifica di altri utenti) ---
// Questo allinea la sicurezza di questa pagina con la visibilità del link Modifica nella pagina di visualizzazione
if ($livello_utente_loggato >= 3) {
    header("Location: unauthorized.php"); // Assicurati di avere una pagina unauthorized.php
    exit();
}
// --- FINE CONTROLLO PERMESSI ---


// --- Gestione ID utente target e recupero dati esistenti ---
$user_id = null;
$user_data = null; // Conterrà i dati dell'utente target da mostrare nel form
$costo_extra = null; // Conterrà il costo extra associato
$errore = '';
$messaggio = ''; // Per messaggi futuri (es. validazione in pagina)


// *** CONTROLLA SE L'ID È STATO PASSATO TRAMITE POST O GET ***
if (isset($_POST['id']) && is_numeric($_POST['id'])) {
    $user_id = intval($_POST['id']);
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_id = intval($_GET['id']);
} else {
    // ID utente non valido o non specificato in nessun modo
    $_SESSION['error_message'] = "ID utente non specificato o non valido.";
    header("Location: gestisci_utenti.php"); // Reindirizza alla pagina di gestisci_utenti
    exit();
}

// Assicurati che l'ID recuperato sia valido (> 0) prima di cercare nel DB
if ($user_id > 0) {
    // Recupera i dati dell'utente target dal database
    $sql_utente = $conn->prepare("SELECT id, Nome, Cognome, targa_mezzo, divisione, filiale, livello, username FROM utenti WHERE id = ?");
    if ($sql_utente) {
        $sql_utente->bind_param("i", $user_id);
        $sql_utente->execute();
        $result_utente = $sql_utente->get_result();

        if ($result_utente->num_rows === 1) {
            $user_data = $result_utente->fetch_assoc();

            // Recupera il costo extra associato alla targa dell'utente target
            // NOTA: Il file originale recupera UN costo extra. Potrebbe essercene più di uno per targa.
            // Qui recuperiamo il primo trovato, per coerenza con l'originale.
            $sql_costo_extra = $conn->prepare("SELECT costo FROM costo_extra WHERE targa_mezzo = ? ORDER BY time_stamp DESC LIMIT 1"); // Ordina per data per prendere il più recente? (Non specificato nell'originale)
            if ($sql_costo_extra) {
                $sql_costo_extra->bind_param("s", $user_data['targa_mezzo']);
                $sql_costo_extra->execute();
                $result_costo_extra = $sql_costo_extra->get_result();
                if ($result_costo_extra->num_rows > 0) {
                    $row_costo_extra = $result_costo_extra->fetch_assoc();
                    $costo_extra = $row_costo_extra['costo'];
                }
                $sql_costo_extra->close();
            } else {
                error_log("Errore preparazione query costo_extra in modifica_utente.php: " . $conn->error);
                // Questo non è un errore bloccante, il campo costo rimarrà vuoto
            }


        } else {
            // L'utente target con l'ID specificato non esiste
            $_SESSION['error_message'] = "Utente con ID " . htmlspecialchars($user_id) . " non trovato.";
            header("Location: gestisci_utenti.php"); // Reindirizza alla pagina di gestisci_utenti
            exit();
        }
        $sql_utente->close();

    } else {
        error_log("Errore preparazione query utente target in modifica_utente.php: " . $conn->error);
        die("Errore critico nel recupero dati utente target.");
    }


    // Recupera le divisioni per la dropdown (query diretta, considera spostarla)
    $sql_divisioni = "SELECT DISTINCT divisione FROM filiali ORDER BY divisione";
    $result_divisioni = $conn->query($sql_divisioni);
    $divisioni = [];
    if ($result_divisioni) {
        if ($result_divisioni->num_rows > 0) {
            while ($row = $result_divisioni->fetch_assoc()) {
                $divisioni[] = $row['divisione'];
            }
        }
        $result_divisioni->free(); // Libera il risultato
    } else {
        error_log("Errore query divisioni in modifica_utente.php: " . $conn->error);
        // Questo non è un errore bloccante, la dropdown divisioni sarà vuota
    }


    // Recupera i livelli di autorizzazione con descrizione e livello (query diretta, considera spostarla)
    $sql_livelli = "SELECT descrizione_livello, livello FROM livelli_autorizzazione ORDER BY livello"; // Variabile rinominata
    $result_autorizzazioni = $conn->query($sql_livelli);
    $livelli_autorizzazione = [];
    if ($result_autorizzazioni) {
        if ($result_autorizzazioni->num_rows > 0) {
            while ($row = $result_autorizzazioni->fetch_assoc()) {
                $livelli_autorizzazione[] = $row;
            }
        }
        $result_autorizzazioni->free(); // Libera il risultato
    } else {
        error_log("Errore query livelli autorizzazione in modifica_utente.php: " . $conn->error);
        // Questo non è un errore bloccante, la dropdown livelli sarà vuota
    }

} else {
    // L'ID recuperato (GET/POST) era <= 0 dopo la validazione is_numeric
    $_SESSION['error_message'] = "ID utente non valido.";
    header("Location: gestisci_utenti.php"); // Reindirizza alla pagina di gestisci_utenti
    exit();
}


// Nota: La logica di aggiornamento (POST handling del form) è in aggiorna_utente.php


?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Utente</title>
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

        .readonly-field {
            background-color: #e9ecef; /* Colore di sfondo per indicare la non modificabilità */
            cursor: not-allowed; /* Cambia il cursore per indicare che non è interattivo */
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
    <h1 class="mb-4 text-center">Modifica Utente</h1>

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
    // Mostra l'errore gestito direttamente in questa pagina se presente (meno probabile con redirect immediati)
    if (!empty($errore)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($errore); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>


    <?php if ($user_data): // Mostra il form solo se i dati dell'utente target sono stati recuperati ?>

        <div class="row justify-content-center">
            <div class="col-md-6">
                <form action="aggiorna_utente.php" method="post" class="bg-white shadow-sm rounded p-4" id="updateUserForm">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($user_data['id']); ?>">

                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome:</label>
                        <input type="text" name="nome" id="nome" class="form-control <?php if ($livello_utente_loggato >= 3) echo 'readonly-field'; ?>" value="<?php echo htmlspecialchars($user_data['Nome']); ?>" <?php if ($livello_utente_loggato >= 3) echo 'readonly'; ?> required> </div>
                    <div class="mb-3">
                        <label for="cognome" class="form-label">Cognome:</label>
                        <input type="text" name="cognome" id="cognome" class="form-control <?php if ($livello_utente_loggato >= 3) echo 'readonly-field'; ?>" value="<?php echo htmlspecialchars($user_data['Cognome']); ?>" <?php if ($livello_utente_loggato >= 3) echo 'readonly'; ?> required> </div>
                    <div class="mb-3">
                        <label for="targa_mezzo" class="form-label">Targa Mezzo:</label>
                        <input type="text" name="targa_mezzo" id="targa_mezzo" class="form-control" value="<?php echo htmlspecialchars($user_data['targa_mezzo']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="costo" class="form-label">Costo Extra Associato:</label>
                        <input type="number" name="costo" id="costo" step="0.01" class="form-control" value="<?php echo htmlspecialchars($costo_extra ?? ''); ?>">
                        <div class="form-text">Modifica il costo extra associato alla targa dell'utente.</div>
                    </div>
                    <div class="mb-3">
                        <label for="divisione" class="form-label">Divisione:</label>
                        <select name="divisione" id="divisione" class="form-select <?php if ($livello_utente_loggato >= 3) echo 'readonly-field'; ?>" <?php if ($livello_utente_loggato >= 3) echo 'disabled'; // Disabled impedisce l'invio del valore ?>>
                            <option value="">Seleziona Divisione</option>
                            <?php foreach ($divisioni as $divisione_option) : // Variabile rinominata ?>
                                <option value="<?php echo htmlspecialchars($divisione_option); ?>" <?php if ($divisione_option === $user_data['divisione']) echo 'selected'; ?>><?php echo htmlspecialchars($divisione_option); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($livello_utente_loggato >= 3): ?>
                            <input type="hidden" name="divisione" value="<?php echo htmlspecialchars($user_data['divisione']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="filiale_display" class="form-label">Filiale:</label>
                        <div id="filiale_display" class="form-control-plaintext">
                            <?php echo htmlspecialchars($user_data['filiale']); ?>
                        </div>
                        <input type="hidden" name="filiale" id="filiale" value="<?php echo htmlspecialchars($user_data['filiale']); ?>">
                        <div class="form-text">La Filiale si aggiorna automaticamente in base alla Divisione selezionata.</div>
                    </div>
                    <div class="mb-3">
                        <label for="livello" class="form-label">Livello:</label>
                        <select name="livello" id="livello" class="form-select <?php if ($livello_utente_loggato >= 3) echo 'readonly-field'; ?>" <?php if ($livello_utente_loggato >= 3) echo 'disabled'; ?>>
                            <option value="">Seleziona Autorizzazione</option>
                            <?php foreach ($livelli_autorizzazione as $livello_data_option) : // Variabile rinominata ?>
                                <option value="<?php echo htmlspecialchars($livello_data_option['livello']); ?>" <?php if ($livello_data_option['livello'] === $user_data['livello']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($livello_data_option['descrizione_livello']) . ' (' . htmlspecialchars($livello_data_option['livello']) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($livello_utente_loggato >= 3): ?>
                            <input type="hidden" name="livello" value="<?php echo htmlspecialchars($user_data['livello']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="username_input" class="form-label">Username:</label> 
                        <input type="text" name="username" id="username_input" class="form-control <?php if ($livello_utente_loggato >= 3) echo 'readonly-field'; ?>" value="<?php echo htmlspecialchars($user_data['username']); ?>" required <?php if ($livello_utente_loggato >= 3) echo 'readonly'; ?>>
                    </div>
                    <div class="text-center">
                        <button type="submit" name="submit_update_utente" class="btn btn-primary"><i class="bi bi-save me-2"></i> Aggiorna Utente</button>
                    </div>
                </form>
                <div class="text-center mt-3">
                    <a href="gestisci_utenti.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Torna alla Gestione Utenti</a>
                </div>
            </div>
        </div>

    <?php endif; // Fine if per mostrare il form ?>


</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script>
// Script per convertire la targa mezzo in maiuscolo e username in minuscolo durante la digitazione
document.addEventListener('DOMContentLoaded', function() {
    const targaMezzoField = document.getElementById('targa_mezzo');
    const usernameField = document.getElementById('username_input');
    
    // Converte la targa in maiuscolo durante la digitazione
    targaMezzoField.addEventListener('input', function() {
        // Salva la posizione del cursore
        const cursorPosition = this.selectionStart;
        // Converte in maiuscolo
        this.value = this.value.toUpperCase();
        // Ripristina la posizione del cursore
        this.setSelectionRange(cursorPosition, cursorPosition);
    });
    
		// Converte l'username in minuscolo durante la digitazione
		usernameField.addEventListener('input', function() {
			// Salva la posizione del cursore
			const cursorPosition = this.selectionStart;
			// Converte in minuscolo
			this.value = this.value.toLowerCase();
			// Ripristina la posizione del cursore
			this.setSelectionRange(cursorPosition, cursorPosition);
		});
});

// Script per convertire username in minuscolo prima dell'invio
document.getElementById('updateUserForm').addEventListener('submit', function(e) {
    const usernameField = document.getElementById('username_input');
    
    // Converte sempre l'username in minuscolo
    usernameField.value = usernameField.value.toLowerCase();
});
    // Script per caricare le filiali in base alla divisione selezionata
    document.addEventListener('DOMContentLoaded', function() {
        const divisioneSelect = document.getElementById('divisione');
        const filialeDisplay = document.getElementById('filiale_display');
        const filialeInputHidden = document.getElementById('filiale');
        // Recupera i valori correnti dell'utente target per visualizzazione iniziale e hidden input
        const initialDivisione = "<?php echo htmlspecialchars($user_data['divisione'] ?? ''); ?>"; // Usa ?? '' per evitare errori se $user_data non è definito
        const initialFiliale = "<?php echo htmlspecialchars($user_data['filiale'] ?? ''); ?>";

        function caricaFiliali(divisione) {
            if (divisione) {
                // Assicurati che l'URL di get_filiali.php sia corretto
                fetch('get_filiali.php?divisione=' + encodeURIComponent(divisione))
                    .then(response => {
                        if (!response.ok) {
                            // Gestisci risposte HTTP non riuscite
                            console.error('Errore nella fetch:', response.status, response.statusText);
                            filialeDisplay.textContent = 'Errore caricamento filiali';
                            filialeInputHidden.value = '';
                            return []; // Ritorna un array vuoto per evitare errori nel next .then
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Assumiamo che get_filiali.php restituisca un array di oggetti,
                        // e ogni oggetto abbia una proprietà per il nome della filiale.
                        // L'originale usava data[0]['nome_divisione'], che sembra un po' strano per una filiale.
                        // Assumo che la proprietà corretta sia 'filiale' o simile.
                        // Per ora, userò una proprietà ipotetica 'nome_filiale'.
                        // Se 'get_filiali.php' restituisce la struttura { divisione: '...', filiale: '...' }
                        // allora data[0]['filiale'] sarebbe corretto.
                        // Dato che l'originale usava nome_divisione, mantengo quello ma con un commento.
                        if (data && data.length > 0) {
                            // *** ASSICURATI CHE get_filiali.php RESTITUISCA UN CAMPO CHIAMATO 'nome_divisione' O AGGIUSTA QUI ***
                            filialeDisplay.textContent = data[0]['nome_divisione']; // Mostra il nome della filiale
                            filialeInputHidden.value = data[0]['nome_divisione']; // Aggiorna il valore del campo nascosto
                        } else {
                            filialeDisplay.textContent = 'Nessuna filiale trovata per questa Divisione';
                            filialeInputHidden.value = '';
                        }
                    })
                    .catch(error => {
                        console.error('Errore durante il fetch delle filiali:', error);
                        filialeDisplay.textContent = 'Errore caricamento filiali';
                        filialeInputHidden.value = '';
                    });
            } else {
                // Se nessuna divisione selezionata, ripristina la filiale iniziale dell'utente target
                filialeDisplay.textContent = initialFiliale;
                filialeInputHidden.value = initialFiliale;
            }
        }

        // Imposta la filiale iniziale al caricamento della pagina basandosi sulla divisione corrente dell'utente target
        // Non chiamare caricaFiliali qui a meno che tu non voglia caricarle *sempre* all'inizio
        // basandoti sulla divisione pre-selezionata. L'originale JS sembrava farlo solo su change.
        // Mantengo la logica originale che mostra la filiale già associata e aggiorna solo su cambio divisione.
        // Se vuoi caricarle dinamicamente all'apertura basandoti sulla divisione pre-selezionata, decommenta/modifica.

        // Aggiungi l'event listener per il cambio di divisione
        // Assicurati che il selettore divisione non sia disabled, altrimenti l'evento change non scatterà
        if (divisioneSelect && !divisioneSelect.disabled) {
            divisioneSelect.addEventListener('change', function() {
                const selectedDivisione = this.value;
                caricaFiliali(selectedDivisione);
            });
        }

        // Se la divisione è readonly/disabled, la filiale non può cambiare.
        // In quel caso, la filiale mostrata sarà sempre quella iniziale dell'utente target.
        // Il form-control-plaintext per filialeDisplay è appropriato in questo caso.


    });
</script>
</body>
</html>