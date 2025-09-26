<?php
session_start();

// Verifica che l'utente sia loggato
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'config.php';
include 'query/qutenti.php'; // Includi il file delle query

$username = $_SESSION['username'];

// Recupera i dati dell'utente
$utente_data = get_user_data($conn, $username);

// Recupera l'ultimo valore di chilometri finali
$ultimo_chilometri_finali = 0;
$sql_chilometri = $conn->prepare("SELECT chilometri_finali FROM chilometri WHERE username = ? ORDER BY id DESC LIMIT 1");
$sql_chilometri->bind_param("s", $username);
$sql_chilometri->execute();
$result_chilometri = $sql_chilometri->get_result();

if ($result_chilometri->num_rows > 0) {
    $row_chilometri = $result_chilometri->fetch_assoc();
    $ultimo_chilometri_finali = $row_chilometri['chilometri_finali'];
}
$sql_chilometri->close();
?>

    <!DOCTYPE html>
    <html lang="it">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Inserimento Chilometri</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        <style>
            /* --- Stili Personalizzati (puoi mantenere o adattare) --- */
            /* ... (i tuoi stili CSS rimangono invariati) ... */
            body {
                background-color: #f8f9fa; /* Un grigio chiaro di Bootstrap */
            }

            #main {
                margin-top: 80px; /* Spazio per la navbar fissa se la aggiungi */
                padding: 30px;
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                width: 250px;
                background-color: #343a40; /* Dark theme di Bootstrap */
                color: white;
                padding-top: 3.5rem; /* Spazio per la navbar fissa */
                overflow-y: auto;
                transition: transform .3s ease-in-out;
                transform: translateX(-250px);
                z-index: 1030; /* Assicura che sia sopra il contenuto principale */
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .sidebar .closebtn {
                position: absolute;
                top: 0;
                right: 15px;
                font-size: 2.5rem;
                margin-left: 50px;
                cursor: pointer;
                color: #f8f9fa; /* Colore più visibile sul background scuro */
                line-height: 1; /* Allinea meglio il carattere 'x' */
                padding-top: 0.5rem; /* Leggero padding superiore */
            }

            .sidebar a {
                padding: 1rem 1.5rem;
                text-decoration: none;
                font-size: 1rem;
                color: #f8f9fa;
                display: block;
                transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out; /* Aggiunta transizione background */
            }

            .sidebar a:hover {
                color: #ffffff; /* Bianco pieno */
                background-color: #495057; /* Grigio scuro per l'hover */
            }

            .openbtn-wrapper {
                position: fixed; /* Posiziona il bottone rispetto alla viewport */
                top: 15px; /* Distanza dal top */
                left: 15px; /* Distanza dal left */
                z-index: 1031; /* Sopra la sidebar quando è chiusa */
            }

            .openbtn {
                font-size: 1.5rem;
                cursor: pointer;
                background-color: #343a40;
                color: white;
                padding: 10px 15px;
                border: none;
                border-radius: 5px;
                /* Rimuovi margin-bottom se usi .openbtn-wrapper */
            }

            .openbtn:hover {
                background-color: #1d2124;
            }

            /* Aggiusta il margine principale quando la sidebar è chiusa e aperta */
            #main {
                transition: margin-left .3s ease-in-out;
                padding-top: 70px; /* Spazio per il bottone fisso e il display utente */
                /* Rimuovi margin-top: 80px */
                padding: 30px;
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            }

            .sidebar.show + #main {
                margin-left: 250px; /* Sposta il contenuto quando la sidebar è aperta */
            }


            .username-display {
                position: fixed; /* Fisso rispetto alla viewport */
                top: 15px; /* Allineato con il bottone */
                right: 15px; /* Distanza da destra */
                color: #495057;
                font-size: 0.9rem;
                background-color: #e9ecef;
                padding: 0.5rem 0.75rem; /* Leggermente più grande */
                border-radius: 0.2rem;
                z-index: 1031; /* Sopra altri elementi */
            }

            form label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: bold;
            }

            form input[type="text"],
            form input[type="number"],
            form input[type="date"],
            form input[type="file"] { /* Aggiunto stile per input file */
                width: 100%;
                padding: 0.75rem;
                margin-bottom: 1rem;
                border: 1px solid #ced4da;
                border-radius: 0.25rem;
            }

            /* Stile specifico per l'input file per coerenza */
            form input[type="file"] {
                padding: 0.5rem 0.75rem; /* Leggermente meno padding verticale per file */
            }

            form button[type="submit"] { /* Cambiato selettore da input a button */
                background-color: #007bff;
                color: white;
                padding: 0.75rem 1.5rem;
                border: none;
                border-radius: 0.25rem;
                cursor: pointer;
                width: 100%;
                font-size: 1rem; /* Dimensione font consistente */
            }

            form button[type="submit"]:hover { /* Cambiato selettore */
                background-color: #0056b3;
            }

            table {
                margin-top: 2rem;
            }

            .error-message {
                color: red;
                margin-top: 0.5rem;
                font-size: 0.875em; /* Dimensione font leggermente ridotta */
            }
        </style>
    </head>

    <body>

    <div id="mySidebar" class="sidebar">
        <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">×</a>
        <a href="index.php" class="nav-link">Inserimento Nuovo Rifornimento</a>
        <a href="visualizza.php" class="nav-link">Visualizza tutti record</a>
        <hr class="bg-light">
        <a href="report_mese.php" class="nav-link">Report Mensile</a>
        <a href="report_anno.php" class="nav-link">Report Annuale</a>
        <a href="registrazione.php" class="nav-link">Registrazione nuovo utente</a>
        <a href="gestisci_utenti.php" class="nav-link">Gestione Utenti</a>
        <a href="logout.php" class="nav-link">Logout</a>
    </div>

    <div class="openbtn-wrapper">
        <button class="openbtn" onclick="openNav()">☰ Menu</button>
    </div>

    <div class="username-display">
        <?php echo "Utente: " . htmlspecialchars($_SESSION['username']); ?>
    </div>


    <div id="main" class="container">
        <h1>Inserimento Chilometri</h1>

        <?php if ($utente_data): ?>
            <p class="lead">
                <strong>Targa Mezzo:</strong> <?php echo htmlspecialchars($utente_data['targa_mezzo']); ?><br>
                <strong>Divisione:</strong> <?php echo htmlspecialchars($utente_data['divisione']); ?><br>
                <strong>Filiale:</strong> <?php echo htmlspecialchars($utente_data['filiale']); ?><br>
                <strong>Autorizzazioni:</strong> <?php echo htmlspecialchars($utente_data['livello']); ?>
            </p>
        <?php else: ?>
            <p class="alert alert-warning">Dati utente non trovati.</p>
        <?php endif; ?>

        <form method="post" action="inserisci.php" onsubmit="return validateKilometers()" enctype="multipart/form-data">
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
                <input type="number" class="form-control" name="chilometri_iniziali" id="chilometri_iniziali" value="<?php echo $ultimo_chilometri_finali; ?>" required>
            </div>

            <div class="mb-3">
                <label for="chilometri_finali" class="form-label">Chilometri Finali:</label>
                <input type="number" class="form-control" name="chilometri_finali" id="chilometri_finali" required>
                <div id="kilometers-error" class="error-message"></div>
            </div>

            <div class="mb-3">
                <label for="litri_carburante" class="form-label">Litri Carburante:</label>
                <input type="number" class="form-control" name="litri_carburante" id="litri_carburante" step="0.01" required>
            </div>

            <div class="mb-3">
                <label for="euro_spesi" class="form-label">Euro Spesi:</label>
                <input type="number" class="form-control" name="euro_spesi" id="euro_spesi" step="0.01" required>
            </div>

            <div class="mb-3">
                <label for="cedolino" class="form-label">Foto Cedolino (Opzionale):</label>
                <input type="file" class="form-control" name="cedolino" id="cedolino" accept="image/*" capture="environment">
            </div>
            <button type="submit" class="btn btn-primary w-100">Inserisci</button>
        </form>

        <h2 class="mt-4">Ultima registrazione inserita</h2>
        <table class="table table-bordered table-striped"> <thead>
            <tr>
                <th>Data</th>
                <th>Km Iniziali</th> <th>Km Finali</th>   <th>Litri</th>       <th>Euro</th>        <th>Cedolino</th>
            </tr>
            </thead>
            <tbody>
            <?php
            // Modifica la query per includere la nuova colonna
            $sql_ultima_registrazione = $conn->prepare("SELECT data, chilometri_iniziali, chilometri_finali, litri_carburante, euro_spesi, percorso_cedolino FROM chilometri WHERE username = ? ORDER BY id DESC LIMIT 1");
            $sql_ultima_registrazione->bind_param("s", $username);
            $sql_ultima_registrazione->execute();
            $result_ultima_registrazione = $sql_ultima_registrazione->get_result();

            if ($result_ultima_registrazione->num_rows > 0) {
                $row_ultima_registrazione = $result_ultima_registrazione->fetch_assoc();
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row_ultima_registrazione["data"]) . "</td>";
                echo "<td>" . htmlspecialchars($row_ultima_registrazione["chilometri_iniziali"]) . "</td>";
                echo "<td>" . htmlspecialchars($row_ultima_registrazione["chilometri_finali"]) . "</td>";
                echo "<td class='text-end'>" . htmlspecialchars(number_format($row_ultima_registrazione["litri_carburante"], 2)) . "</td>"; // Allinea a destra
                echo "<td class='text-end'>" . htmlspecialchars(number_format($row_ultima_registrazione["euro_spesi"], 2)) . " €</td>"; // Allinea a destra e aggiunge €
                // MODIFICA 4: Mostra un link se il percorso del cedolino esiste
                echo "<td>";
                if (!empty($row_ultima_registrazione["percorso_cedolino"])) {
                    // Assicurati che il percorso sia accessibile via web
                    // Esempio: se salvi in 'uploads/cedolini/nomefile.jpg' e la cartella 'uploads'
                    // è nella stessa directory dello script, il link sarà così:
                    echo "<a href='" . htmlspecialchars($row_ultima_registrazione["percorso_cedolino"]) . "' target='_blank'>Vedi Foto</a>";
                } else {
                    echo "N/D"; // Non disponibile
                }
                echo "</td>";
                // Fine Modifica 4
                echo "</tr>";
            } else {
                // Modifica colspan per includere la nuova colonna
                echo "<tr><td colspan='6' class='text-center'>Nessuna registrazione inserita</td></tr>"; // Centrato
            }
            $sql_ultima_registrazione->close();
            ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script>
        const sidebar = document.getElementById('mySidebar');
        const mainContent = document.getElementById('main');
        const openBtnWrapper = document.querySelector('.openbtn-wrapper'); // Selettore aggiornato

        function openNav() {
            sidebar.classList.add('show');
            mainContent.style.marginLeft = "250px";
            // Nascondi il bottone Apri Menu quando la sidebar è aperta (opzionale)
            // openBtnWrapper.style.display = 'none';
        }

        function closeNav() {
            sidebar.classList.remove('show');
            mainContent.style.marginLeft = "0";
            // Mostra di nuovo il bottone Apri Menu
            // openBtnWrapper.style.display = 'block';
        }

        // Chiudi la sidebar se si clicca fuori (opzionale ma consigliato)
        document.addEventListener('click', function(event) {
            const openBtn = document.querySelector('.openbtn'); // Prendilo qui dentro se non è globale
            // Controlla se il click NON è sulla sidebar E NON è sul bottone per aprirla
            // E la sidebar è attualmente visibile ('show')
            if (!sidebar.contains(event.target) && !openBtn.contains(event.target) && sidebar.classList.contains('show')) {
                closeNav();
            }
        });


        function validateKilometers() {
            // Assicurati che i valori siano numeri prima di confrontare
            const initialKilometersInput = document.getElementById('chilometri_iniziali');
            const finalKilometersInput = document.getElementById('chilometri_finali');
            const errorDiv = document.getElementById('kilometers-error');

            // Pulisci errori precedenti
            errorDiv.textContent = '';
            initialKilometersInput.classList.remove('is-invalid');
            finalKilometersInput.classList.remove('is-invalid');


            // Prova a convertire in numeri interi
            const initialKilometers = parseInt(initialKilometersInput.value, 10);
            const finalKilometers = parseInt(finalKilometersInput.value, 10);


            // Controlla se i campi sono vuoti o non sono numeri validi (se richiesto)
            if (isNaN(initialKilometers) || initialKilometersInput.value.trim() === '') {
                errorDiv.textContent = 'Inserire un valore numerico per Chilometri Iniziali.';
                initialKilometersInput.classList.add('is-invalid'); // Stile Bootstrap per errore
                initialKilometersInput.focus(); // Porta il focus sul campo errato
                return false;
            }
            if (isNaN(finalKilometers) || finalKilometersInput.value.trim() === '') {
                errorDiv.textContent = 'Inserire un valore numerico per Chilometri Finali.';
                finalKilometersInput.classList.add('is-invalid');
                finalKilometersInput.focus();
                return false;
            }


            if (finalKilometers < initialKilometers) {
                errorDiv.textContent = 'Il campo Chilometri Finali non può essere inferiore al campo Chilometri Iniziali.';
                finalKilometersInput.classList.add('is-invalid'); // Evidenzia il campo errato
                finalKilometersInput.focus();
                return false; // Impedisce l'invio del form
            }

            // Se tutto ok, rimuovi eventuali classi di errore (se aggiunte prima)
            initialKilometersInput.classList.remove('is-invalid');
            finalKilometersInput.classList.remove('is-invalid');
            // Rimuovi anche is-valid se vuoi essere pulito
            initialKilometersInput.classList.add('is-valid');
            finalKilometersInput.classList.add('is-valid');


            return true; // Permette l'invio del form
        }

        // Aggiungi listener per validare mentre si digita (opzionale)
        /*
        document.getElementById('chilometri_finali').addEventListener('input', validateKilometers);
        document.getElementById('chilometri_iniziali').addEventListener('input', validateKilometers);
        */
    </script>

    </body>
    </html>

<?php
$conn->close(); // Chiudi la connessione al database
?>