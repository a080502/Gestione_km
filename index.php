<?php
session_start();

// Verifica che l'utente sia loggato
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include_once 'config.php';
include 'query/qutenti.php'; // Includi il file delle query

$username = $_SESSION['username'];

// Recupera i dati dell'utente
$utente_data = get_user_data($conn, $username);

// Recupera l'ultimo valore di chilometri finali
$ultimo_chilometri_finali = 0;
$sql_chilometri = $conn->prepare("SELECT chilometri_finali FROM chilometri WHERE username = ? ORDER BY id DESC LIMIT 1");
if ($sql_chilometri) { // Aggiunto controllo sulla preparazione
    $sql_chilometri->bind_param("s", $username);
    $sql_chilometri->execute();
    $result_chilometri = $sql_chilometri->get_result();

    if ($result_chilometri->num_rows > 0) {
        $row_chilometri = $result_chilometri->fetch_assoc();
        $ultimo_chilometri_finali = $row_chilometri['chilometri_finali'];
    }
    $sql_chilometri->close();
} else {
    // Gestire l'errore di preparazione, se necessario
    error_log("Errore nella preparazione della query SQL per ultimi chilometri: " . $conn->error);
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
 <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserimento Chilometri - Mobile Optimized</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* ... il tuo CSS ... */
    </style>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserimento Chilometri - Mobile Optimized</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <style>
        body {
            background-color: #f8f9fa;
            /* Aggiunge padding in alto per non sovrapporre contenuto al bottone menu/username */
            padding-top: 70px;
        }

        /* Contenitore per il bottone menu e username fissi */
        .fixed-top-elements {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background-color: #f8f9fa; /* Sfondo per evitare trasparenze */
            padding: 10px 15px;
            z-index: 1030; /* Sopra il contenuto, sotto l'offcanvas */
            display: flex; /* Usa flexbox per allineare */
            justify-content: space-between; /* Spazio tra bottone e username */
            align-items: center; /* Allinea verticalmente */
            border-bottom: 1px solid #dee2e6; /* Separatore opzionale */
        }

        .menu-btn {
            font-size: 1.2rem; /* Dimensione leggermente ridotta */
            /* Non serve più wrapper separato */
        }

        .username-display {
            font-size: 0.9rem;
            color: #495057;
            background-color: #e9ecef;
            padding: 0.3rem 0.6rem;
            border-radius: 0.2rem;
            /* Non serve più position: fixed */
        }

        /* Stili per il form (gran parte gestita da Bootstrap) */
        form label {
            font-weight: bold;
            margin-bottom: 0.5rem; /* Spazio sotto la label */
        }

        form .form-control {
             margin-bottom: 1rem; /* Spazio sotto ogni input */
        }

        /* Stile per il messaggio di errore validazione */
        .error-message {
            color: var(--bs-danger); /* Usa variabile colore Bootstrap per coerenza */
            font-size: 0.875em;
            margin-top: -0.5rem; /* Avvicina l'errore all'input */
            margin-bottom: 1rem; /* Spazio sotto l'errore */
            display: none; /* Nascondi per default */
        }
        .form-control.is-invalid + .error-message {
            display: block; /* Mostra quando l'input è invalido */
        }

        /* Offcanvas styling (opzionale, Bootstrap ha buoni default) */
        .offcanvas-header {
            border-bottom: 1px solid #dee2e6;
        }
        .offcanvas-body .nav-link {
            padding: 0.8rem 1.5rem;
            font-size: 1.1rem; /* Link leggermente più grandi per tap */
            color: #212529; /* Colore testo scuro per contrasto su sfondo chiaro */
        }
        .offcanvas-body .nav-link:hover {
            background-color: #e9ecef;
        }
        .offcanvas-body hr {
            margin: 1rem 1.5rem; /* Margine per il separatore */
        }

        /* Stili per la tabella (Bootstrap gestisce molto) */
        .table {
            margin-top: 1.5rem; /* Spazio sopra la tabella */
            font-size: 0.9rem; /* Font più piccolo per tabelle su mobile */
        }
        /* Allineamento per numeri/valute nella tabella */
        .table td.text-end, .table th.text-end {
            text-align: right;
        }

        /* Stili per l'anteprima immagine */
        #image-preview-container {
            position: absolute;
            background-color: #fff;
            border: 1px solid #ccc;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
            padding: 5px;
            z-index: 1050; /* Sopra altri elementi */
            display: none; /* Inizialmente nascosto */
            max-width: 200px;
            max-height: 200px;
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
            ☰ Menu
        </button>
        <div class="username-display">
            <?php echo "Utente: " . htmlspecialchars($_SESSION['username']); ?>
        </div>
    </div>

    <?php include 'include/menu.php'; ?>

    <div class="container" id="main-content">
        <h1 class="mb-3 h3">Inserimento Chilometri</h1>

        <form method="post" action="inserisci.php" onsubmit="return validateKilometers()" enctype="multipart/form-data" id="inserimentoForm">
            <input type="hidden" name="username" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
            <input type="hidden" name="targa_mezzo" value="<?php echo htmlspecialchars($utente_data['targa_mezzo'] ?? ''); ?>">
            <input type="hidden" name="divisione" value="<?php echo htmlspecialchars($utente_data['divisione'] ?? ''); ?>">
            <input type="hidden" name="filiale" value="<?php echo htmlspecialchars($utente_data['filiale'] ?? ''); ?>">
            <input type="hidden" name="livello" value="<?php echo htmlspecialchars($utente_data['livello'] ?? ''); ?>">

            <div class="mb-3">
                <label for="data" class="form-label">Data:</label>
                <input type="date" class="form-control" name="data" id="data" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="mb-3">
                <label for="chilometri_iniziali" class="form-label">Chilometri Iniziali:</label>
                <input type="number" class="form-control" name="chilometri_iniziali" id="chilometri_iniziali" value="<?php echo $ultimo_chilometri_finali; ?>" required inputmode="numeric">
                 <div class="invalid-feedback">Inserire un valore numerico valido.</div>
            </div>

            <div class="mb-3">
                <label for="chilometri_finali" class="form-label">Chilometri Finali:</label>
                <input type="number" class="form-control" name="chilometri_finali" id="chilometri_finali" required inputmode="numeric">
                <div id="kilometers-error" class="error-message"></div>
                 <div class="invalid-feedback">Inserire un valore numerico valido.</div>
            </div>

            <div class="mb-3">
                <label for="litri_carburante" class="form-label">Litri Carburante:</label>
                <input type="number" class="form-control" name="litri_carburante" id="litri_carburante" step="0.01" required inputmode="decimal">
                 <div class="invalid-feedback">Inserire un valore numerico (es. 50.25).</div>
            </div>

            <div class="mb-3">
                <label for="euro_spesi" class="form-label">Euro Spesi:</label>
                <input type="number" class="form-control" name="euro_spesi" id="euro_spesi" step="0.01" required inputmode="decimal">
                 <div class="invalid-feedback">Inserire un valore numerico (es. 75.50).</div>
            </div>

            <div class="mb-3">
                <label for="cedolino" class="form-label">Foto Cedolino (Opzionale):</label>
                <input type="file" class="form-control" name="cedolino" id="cedolino" accept="image/*" capture="environment">
                 <div class="form-text">Scatta una foto della ricevuta con la fotocamera.</div>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-lg">Inserisci Dati</button>
        </form>

        <h2 class="mt-5 h6">Ultima Registrazione Inserita</h2>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Data</th>
                        <th class="text-end">Km Iniz.</th>
                        <th class="text-end">Km Fin.</th>
                        <th class="text-end">Litri</th>
                        <th class="text-end">Euro</th>
                        <th>Cedolino</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // Query per l'ultima registrazione (invariata)
                $sql_ultima_registrazione = $conn->prepare("SELECT data, chilometri_iniziali, chilometri_finali, litri_carburante, euro_spesi, percorso_cedolino FROM chilometri WHERE username = ? ORDER BY id DESC LIMIT 1");
                 if ($sql_ultima_registrazione) { // Aggiunto controllo
                    $sql_ultima_registrazione->bind_param("s", $username);
                    $sql_ultima_registrazione->execute();
                    $result_ultima_registrazione = $sql_ultima_registrazione->get_result();

                    if ($result_ultima_registrazione->num_rows > 0) {
                        $row = $result_ultima_registrazione->fetch_assoc();
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars(date("d/m/y", strtotime($row["data"]))) . "</td>"; // Formato data breve
                        echo "<td class='text-end'>" . htmlspecialchars($row["chilometri_iniziali"]) . "</td>";
                        echo "<td class='text-end'>" . htmlspecialchars($row["chilometri_finali"]) . "</td>";
                        echo "<td class='text-end'>" . htmlspecialchars(number_format($row["litri_carburante"], 2, ',', '.')) . "</td>"; // Formato italiano
                        echo "<td class='text-end'>" . htmlspecialchars(number_format($row["euro_spesi"], 2, ',', '.')) . " €</td>"; // Formato italiano
                        echo "<td class='text-center'>"; // Centro l'icona/link
                        if (!empty($row["percorso_cedolino"]) && file_exists($row["percorso_cedolino"])) { // Aggiunto controllo esistenza file
                            // Icona per visualizzare (Bootstrap Icons)
                            echo "<a href='" . htmlspecialchars($row["percorso_cedolino"]) . "' target='_blank' title='Vedi Foto Cedolino' data-cedolino-url='" . htmlspecialchars($row["percorso_cedolino"]) . "' class='cedolino-preview-link'><svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' fill='currentColor' class='bi bi-eye-fill' viewBox='0 0 16 16'><path d='M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0'/><path d='M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7'/></svg></a>";
                        } else {
                            // Icona per 'non disponibile' (Bootstrap Icons)
                            echo "<svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' fill='currentColor' class='bi bi-image text-muted' viewBox='0 0 16 16' title='Foto non disponibile'></svg>";
                        }
                        echo "</td>";
                        echo "</tr>";
                    } else {
                        echo "<tr><td colspan='6' class='text-center fst-italic text-muted'>Nessuna registrazione trovata.</td></tr>";
                    }
                    $sql_ultima_registrazione->close();
                 } else {
                    echo "<tr><td colspan='6' class='text-center text-danger'>Errore nel caricamento ultima registrazione.</td></tr>";
                    error_log("Errore nella preparazione della query SQL per ultima registrazione: " . $conn->error);
                 }
                 ?>
                </tbody>
            </table>
        </div>
        <div id="image-preview-container">
            <img src="" alt="Anteprima Cedolino">
        </div>
    </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script>
        // Funzione di validazione dei chilometri (invariata nella logica, usa classi Bootstrap)
        function validateKilometers() {
            const initialKilometersInput = document.getElementById('chilometri_iniziali');
            const finalKilometersInput = document.getElementById('chilometri_finali');
            const errorDiv = document.getElementById('kilometers-error');
            let isValid = true;

            // Pulisci errori precedenti e classi di validazione
            errorDiv.textContent = '';
            errorDiv.style.display = 'none'; // Nascondi div errore specifico km
            initialKilometersInput.classList.remove('is-invalid', 'is-valid');
            finalKilometersInput.classList.remove('is-invalid', 'is-valid');

            // Controlla se i campi sono vuoti (già gestito da 'required', ma doppia verifica non fa male)
            if (initialKilometersInput.value.trim() === '') {
                initialKilometersInput.classList.add('is-invalid');
                isValid = false;
            }
             if (finalKilometersInput.value.trim() === '') {
                 finalKilometersInput.classList.add('is-invalid');
                 isValid = false;
             }

            // Prova a convertire in numeri interi solo se i campi non sono vuoti
            if(isValid) {
                const initialKilometers = parseInt(initialKilometersInput.value, 10);
                const finalKilometers = parseInt(finalKilometersInput.value, 10);

                // Controlla se la conversione ha prodotto NaN (non un numero)
                 if (isNaN(initialKilometers)) {
                     initialKilometersInput.classList.add('is-invalid');
                     isValid = false;
                 }
                 if (isNaN(finalKilometers)) {
                     finalKilometersInput.classList.add('is-invalid');
                     isValid = false;
                 }

                 // Se entrambi sono numeri validi, confrontali
                 if (isValid && finalKilometers < initialKilometers) {
                     errorDiv.textContent = 'I Chilometri Finali non possono essere inferiori agli Iniziali.';
                     errorDiv.style.display = 'block'; // Mostra errore specifico
                     finalKilometersInput.classList.add('is-invalid'); // Evidenzia il campo errato
                     finalKilometersInput.focus();
                     isValid = false; // Impedisce l'invio del form
                 }
            }

            // Se tutto è ok fino a qui, aggiungi classi 'is-valid' (feedback positivo opzionale)
            if (isValid) {
                initialKilometersInput.classList.add('is-valid');
                finalKilometersInput.classList.add('is-valid');
            } else {
                 // Se c'è stato un errore, porta il focus sul primo campo invalido trovato
                 const firstInvalid = document.querySelector('#inserimentoForm .is-invalid');
                 if(firstInvalid) {
                     firstInvalid.focus();
                 }
            }

            return isValid; // Restituisce true solo se tutti i controlli passano
        }

        // Aggiunta di validazione Bootstrap standard al submit (opzionale, ma buona pratica)
        // Questo attiva i messaggi 'invalid-feedback' di Bootstrap se i campi required sono vuoti
        // o se i tipi (number, date) non sono rispettati.
        const form = document.getElementById('inserimentoForm');
        form.addEventListener('submit', event => {
            // Esegui prima la tua validazione custom dei KM
            if (!validateKilometers()) {
                event.preventDefault(); // Blocca invio se KM non validi
                event.stopPropagation();
            }
            // Poi lascia che Bootstrap controlli gli altri campi 'required', 'type', etc.
            else if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated'); // Mostra feedback Bootstrap
        }, false);


        // Listener per ripulire l'errore specifico dei KM quando l'utente modifica i campi
         document.getElementById('chilometri_iniziali').addEventListener('input', () => {
             const errorDiv = document.getElementById('kilometers-error');
             if (errorDiv.style.display === 'block') { // Se l'errore KM era visibile
                 errorDiv.textContent = '';
                 errorDiv.style.display = 'none';
                 document.getElementById('chilometri_finali').classList.remove('is-invalid'); // Rimuovi lo stato invalido dal campo finale
                 // Potresti anche voler rimuovere 'is-valid' da entrambi se vuoi che rivenga validato
                 document.getElementById('chilometri_iniziali').classList.remove('is-valid', 'is-invalid');
                 document.getElementById('chilometri_finali').classList.remove('is-valid');

             }
         });
         document.getElementById('chilometri_finali').addEventListener('input', () => {
             const errorDiv = document.getElementById('kilometers-error');
              if (errorDiv.style.display === 'block') { // Se l'errore KM era visibile
                 errorDiv.textContent = '';
                 errorDiv.style.display = 'none';
                 document.getElementById('chilometri_finali').classList.remove('is-invalid');
                 document.getElementById('chilometri_iniziali').classList.remove('is-valid');
                 document.getElementById('chilometri_finali').classList.remove('is-valid');
             }
         });

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
if (isset($conn) && $conn instanceof mysqli) { // Controlla se $conn è un oggetto mysqli valido
    $conn->close(); // Chiudi la connessione al database
}
?>