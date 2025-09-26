<?php

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include_once 'config.php';
// Assicurati che i percorsi siano corretti
include 'query/qutenti.php';
// Potrebbe essere necessario includere anche le funzioni per il costo sconfino
// Se non sono già in qutenti.php o config.php, includile da un altro file
// include 'query/qcostosconfino.php'; // Esempio: se le funzioni sono in questo file

$username = $_SESSION['username'];
$utente_data = get_user_data($conn, $username);
$livello = $utente_data['livello'];
$divisione = $utente_data['divisione'];

// Verifica l'accesso in base al livello (similmente al primo script se necessario)
// if (!$utente_data || $utente_data['livello'] >= X) { // Aggiungi la tua logica di controllo livello qui
//     header("Location: unauthorized.php");
//     exit();
// }


// Recupera tutte le targhe uniche dal database
// Mantengo la query per l'autocomplete, ma considera se limitare le targhe
// in base al livello utente come nel primo script, se appropriato per questa pagina.
$sql_targhe = $conn->prepare("SELECT DISTINCT targa_mezzo FROM chilometri ORDER BY targa_mezzo");
if ($sql_targhe) {
    $sql_targhe->execute();
    $result_targhe = $sql_targhe->get_result();
    $targhe = [];
    while ($row_targa = $result_targhe->fetch_assoc()) {
        $targhe[] = $row_targa['targa_mezzo'];
    }
    $sql_targhe->close();
} else {
    // Gestisci l'errore nella preparazione della query
    $targhe = [];
    // Puoi aggiungere un log o un messaggio di errore visibile se necessario
    // echo "Errore nella preparazione della query delle targhe: " . $conn->error;
}


// Gestisci l'inserimento/aggiornamento del costo sconfino
$inserimento_successo = null;
$messaggio_errore = '';
$costo_esistente = '';

// Assicurati che le funzioni get_costo_sconfino_id_by_targa, update_costo_sconfino, insert_costo_sconfino
// siano definite e incluse (es. in query/qcostosconfino.php)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $targa_mezzo_post = isset($_POST['targa_mezzo']) ? trim($_POST['targa_mezzo']) : '';
    $costo = isset($_POST['costo']) ? str_replace(',', '.', trim($_POST['costo'])) : '';

    if (empty($targa_mezzo_post)) {
        $inserimento_successo = false;
        $messaggio_errore = "Inserisci la targa del mezzo.";
    } elseif (!is_numeric($costo)) {
        $inserimento_successo = false;
        $messaggio_errore = "Il costo deve essere un numero.";
    } else {
        // Verifica se esiste già un costo per questa targa (richiede la funzione get_costo_sconfino_id_by_targa)
        // Assicurati che questa funzione sia definita e accessibile
        if (function_exists('get_costo_sconfino_id_by_targa')) {
            $costo_id = get_costo_sconfino_id_by_targa($conn, $targa_mezzo_post);

            if ($costo_id) {
                // Aggiorna il costo esistente (richiede la funzione update_costo_sconfino)
                if (function_exists('update_costo_sconfino') && update_costo_sconfino($conn, $costo, $targa_mezzo_post)) {
                    $inserimento_successo = true;
                } else {
                    $inserimento_successo = false;
                    $messaggio_errore = "Errore nell'aggiornamento del costo.";
                }
            } else {
                // Inserisci un nuovo costo (richiede la funzione insert_costo_sconfino)
                if (function_exists('insert_costo_sconfino') && insert_costo_sconfino($conn, $targa_mezzo_post, (float)$costo)) {
                    $inserimento_successo = true;
                } else {
                    $inserimento_successo = false;
                    $messaggio_errore = "Errore nell'inserimento del costo.";
                }
            }
        } else {
            $inserimento_successo = false;
            $messaggio_errore = "Funzioni di gestione costo non disponibili.";
        }
    }
} elseif (isset($_GET['targa'])) {
    $targa_selezionata = $_GET['targa'];
    // Recupera il costo esistente (richiede la funzione get_costo_sconfino_by_targa)
    if (function_exists('get_costo_sconfino_by_targa')) {
        $costo_valore = get_costo_sconfino_by_targa($conn, $targa_selezionata);
        if ($costo_valore !== null) {
            $costo_esistente = number_format($costo_valore, 2, '.', ''); // Assicura il punto decimale per JS
        }
    } else {
        // echo "Funzione get_costo_sconfino_by_targa non disponibile.";
    }
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Costo Sconfino Kilometrico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 60px; /* Spazio per la navbar fissa */
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
        }

        .menu-btn {
            font-size: 1.2rem;
        }

        .username-display {
            font-size: 0.9rem;
        }

        /* Stili specifici per l'autocomplete, adattati per essere meno invasivi con Bootstrap */
        .autocomplete-wrapper {
            position: relative;
        }

        .autocomplete-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: white;
            border: 1px solid #ddd;
            border-top: none;
            z-index: 10;
            max-height: 150px;
            overflow-y: auto;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Aggiunto ombra come il container */
            border-radius: 0 0 4px 4px; /* Arrotonda solo i bordi inferiori */
        }

        .autocomplete-list div {
            padding: 8px;
            cursor: pointer;
            border-bottom: 1px solid #eee; /* Aggiunto un separatore leggero */
        }

        .autocomplete-list div:last-child {
            border-bottom: none; /* Rimuovi il separatore sull'ultimo elemento */
        }

        .autocomplete-list div:hover {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>

<div class="fixed-top-elements">
    <button class="btn btn-outline-secondary menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMenu" aria-controls="mainMenu">
        <i class="bi bi-list"></i> Menu
    </button>
    <div class="username-display">
        Utente: <?php echo htmlspecialchars($_SESSION['username']); ?>
    </div>
</div>

<?php include 'include/menu.php'; // Assicurati che questo includa la struttura Offcanvas ?>

<div class="container" id="main-content">
    <h1 class="mb-4 text-center">Costo Sconfino Kilometrico</h1>

    <div class="row justify-content-center">
        <div class="col-md-6">

            <?php if ($inserimento_successo === true): ?>
                <div class="alert alert-success" role="alert">
                    Costo sconfino kilometrico salvato con successo.
                </div>
            <?php elseif ($inserimento_successo === false): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($messaggio_errore); ?>
                </div>
            <?php endif; ?>

            <form method="post" id="costoSconfinoForm" class="bg-white shadow-sm rounded p-4">
                <div class="mb-3">
                    <label for="targa_mezzo" class="form-label">Targa Mezzo:</label>
                    <div class="autocomplete-wrapper">
                        <input type="text" id="targa_mezzo" name="targa_mezzo" class="form-control autocomplete-input" placeholder="Inserisci o seleziona una targa..." value="<?php echo isset($_GET['targa']) ? htmlspecialchars($_GET['targa']) : ''; ?>">
                        <div class="autocomplete-list" id="autocomplete-list"></div> </div>
                </div>

                <div class="mb-3">
                    <label for="costo" class="form-label">Costo (formato 0.00):</label>
                    <input type="text" id="costo" name="costo" class="form-control" placeholder="0.00" value="<?php echo htmlspecialchars($costo_esistente); ?>" required>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i> Salva Costo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const targaInput = document.getElementById('targa_mezzo');
        const costoInput = document.getElementById('costo');
        const autocompleteList = document.getElementById('autocomplete-list');
        const allTarghe = <?php echo json_encode($targhe ?? []); ?>; // Assicurati che $targhe sia un array

        // Funzione per popolare la lista di autocomplete
        function populateAutocomplete(inputValue) {
            autocompleteList.innerHTML = ''; // Pulisce la lista esistente
            autocompleteList.style.display = 'none'; // Nasconde la lista di default

            if (inputValue.length > 0) {
                const filteredTarghe = allTarghe.filter(targa => targa.toLowerCase().includes(inputValue.toLowerCase()));

                if (filteredTarghe.length > 0) {
                    filteredTarghe.forEach(targa => {
                        const listItem = document.createElement('div');
                        listItem.textContent = targa;
                        listItem.classList.add('autocomplete-item'); // Aggiunto una classe per futuro stile/selezione
                        listItem.addEventListener('click', function() {
                            targaInput.value = targa;
                            autocompleteList.innerHTML = ''; // Pulisce la lista
                            autocompleteList.style.display = 'none'; // Nasconde la lista
                            fetchCosto(targa); // Richiama la funzione per recuperare il costo
                        });
                        autocompleteList.appendChild(listItem);
                    });
                    autocompleteList.style.display = 'block'; // Mostra la lista
                }
            }
        }

        // Funzione per recuperare il costo via AJAX
        function fetchCosto(targa) {
            fetch('get_costo_sconfino.php?targa=' + encodeURIComponent(targa))
                .then(response => {
                    if (!response.ok) {
                        // Gestisci errori HTTP, es. 404
                        console.error('Errore nella richiesta fetch:', response.status);
                        costoInput.value = ''; // Pulisce il campo costo in caso di errore
                        return Promise.reject('Errore HTTP: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.costo !== null && data.costo !== undefined) {
                        // Formatta il numero con due decimali
                        costoInput.value = parseFloat(data.costo).toFixed(2);
                    } else {
                        costoInput.value = ''; // Pulisce il campo se nessun costo trovato
                    }
                })
                .catch(error => {
                    console.error('Errore nel parsing JSON o nella fetch:', error);
                    costoInput.value = ''; // Pulisce il campo costo in caso di errore
                });
        }


        targaInput.addEventListener('input', function() {
            populateAutocomplete(this.value);
        });

        // Nasconde la lista quando si clicca fuori
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.autocomplete-wrapper')) {
                autocompleteList.innerHTML = '';
                autocompleteList.style.display = 'none';
            }
        });

        // Gestisci la selezione iniziale dalla query string
        const urlParams = new URLSearchParams(window.location.search);
        const targaParam = urlParams.get('targa');
        if (targaParam) {
            targaInput.value = targaParam;
            fetchCosto(targaParam); // Richiama la funzione per popolare il costo all'avvio
        }

        const costoSconfinoForm = document.getElementById('costoSconfinoForm');
        costoSconfinoForm.addEventListener('submit', function(event) {
            const targa = targaInput.value.trim();
            // Sostituisci la virgola con il punto per la validazione numerica
            const costo = costoInput.value.trim().replace(',', '.');

            if (!targa) {
                alert('Inserisci la targa del mezzo.');
                event.preventDefault();
                return;
            }
            // Valida il formato numerico (accetta numeri interi o decimali con punto)
            if (!/^\d+(\.\d+)?$/.test(costo)) {
                alert('Il costo deve essere un numero (usa il punto come separatore decimale se necessario).');
                event.preventDefault();
                return;
            }
            // Opzionale: valida per un massimo di due decimali dopo l'input (il server dovrebbe validare comunque)
            if (!/^\d+(\.\d{1,2})?$/.test(costo)) {
                // Questo check è più restrittivo sui decimali
                // alert('Il costo deve essere un numero nel formato 0.00.');
                // event.preventDefault();
                // return;
            }
        });

        // Assicura che il campo costo accetti sia virgola che punto come separatore decimale durante l'input
        costoInput.addEventListener('change', function() {
            // Sostituisci la virgola con il punto al cambio del valore
            this.value = this.value.replace(',', '.');
        });
        // Potresti aggiungere un listener 'input' per sostituire la virgola in tempo reale
        costoInput.addEventListener('input', function() {
            // Sostituisci la virgola con il punto mentre l'utente digita
            this.value = this.value.replace(',', '.');
        });
    });
</script>

</body>
</html>