<?php
include 'dati_utente.php';
include 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Paginazione
$limite = 20;
$pagina_corrente = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_corrente - 1) * $limite;

$sql_conta = "SELECT COUNT(*) as totale FROM chilometri WHERE username = '" . $dati_utente['username'] . "'";
$result_conta = $conn->query($sql_conta);
$row_conta = $result_conta->fetch_assoc();
$totale_record = $row_conta['totale'];
$totale_pagine = ceil($totale_record / $limite);

$sql = "SELECT * FROM chilometri WHERE username = '" . $dati_utente['username'] . "' ORDER BY data DESC LIMIT $limite OFFSET $offset";
$result = $conn->query($sql);
?>
    <!DOCTYPE html>
    <html lang="it">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Visualizza record KM</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 0;
                background-color: #f4f4f4;
            }

            /* Sidebar */
            .sidebar {
                height: 100%;
                width: 0;
                position: fixed;
                z-index: 1;
                top: 0;
                left: 0;
                background-color: #111;
                overflow-x: hidden;
                transition: 0.5s;
                padding-top: 60px;
            }

            .sidebar a {
                padding: 10px 30px;
                text-decoration: none;
                font-size: 18px;
                color: #818181;
                display: block;
                transition: 0.3s;
            }

            .sidebar a:hover {
                color: #f1f1f1;
            }

            .sidebar .closebtn {
                position: absolute;
                top: 0;
                right: 25px;
                font-size: 36px;
                margin-left: 50px;
            }

            /* Main */
            #main {
                transition: margin-left .5s;
                padding: 20px;
                margin: 30px;
                background-color: white;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                position: relative;
            }

            .openbtn {
                font-size: 20px;
                cursor: pointer;
                background-color: #111;
                color: white;
                padding: 10px 15px;
                border: none;
                border-radius: 5px;
            }

            .openbtn:hover {
                background-color: #444;
            }

            .username-display {
                position: absolute;
                top: 10px;
                right: 10px;
                color: #333;
                font-size: 18px;
                background-color: LightGray;
                padding: 8px 12px;
                border-radius: 5px;
            }

            /* Tabella */
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }

            table, th, td {
                border: 1px solid #ddd;
                padding: 10px;
                text-align: left; /* Allineamento a sinistra per i dati */
            }

            th {
                background-color: #f2f2f2;
            }

            tr:nth-child(even) {
                background-color: #f9f9f9;
            }

            /* Pagination */
            .pagination {
                margin-top: 20px;
                text-align: center;
            }

            .pagination a, .pagination strong {
                margin: 0 5px;
                padding: 8px 12px;
                text-decoration: none;
                border: 1px solid #ddd;
                border-radius: 5px;
                color: #333;
                background-color: #f9f9f9;
            }

            .pagination a:hover {
                background-color: #007bff;
                color: white;
            }

            .pagination strong {
                background-color: #007bff;
                color: white;
                border: 1px solid #007bff;
            }

            /* Stili per l'immagine al passaggio del mouse */
            .cedolino-container {
                position: relative;
                display: inline-block; /* Necessario per contenere l'immagine */
            }

            .cedolino-image {
                position: absolute;
                top: 100%; /* Mostra l'immagine sotto il link */
                left: 0;
                z-index: 10; /* Assicura che l'immagine sia sopra gli altri elementi */
                border: 1px solid #ccc;
                box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
                max-width: 300px; /* Imposta una larghezza massima per l'immagine */
                height: auto;
                opacity: 0; /* Inizialmente invisibile */
                transition: opacity 0.3s ease-in-out;
                background-color: white; /* Per coprire eventuali elementi sottostanti */
            }

            .cedolino-container:hover .cedolino-image {
                opacity: 1; /* Rende l'immagine visibile al passaggio del mouse */
            }

            .cedolino-link {
                text-decoration: none; /* Rimuove la sottolineatura predefinita del link */
            }
        </style>
    </head>

    <body>

    <div id="mySidebar" class="sidebar">
        <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">×</a>
        <a href="index.php">Inserimento Nuovo Rifornimento</a>
        <a href="report_mese.php">Report Mensile</a>
        <a>================</a>
        <a href="gestisci_utenti.php">Gestione Anagrafica Utenti</a>
        <a href="imposta_target.php">Imposta Target Annuale</a>
        <a href="registrazione.php">Registrazione nuovo utente</a>
        <a href="logout.php">Logout</a>
    </div>

    <div id="main">
        <button class="openbtn" onclick="openNav()">☰ Apri Menu</button>
        <div class="username-display">
            <?php echo "Utente: " . htmlspecialchars($_SESSION['username']) . "<br>Nome: " . htmlspecialchars($dati_utente['Nome']) . " " . htmlspecialchars($dati_utente['Cognome']) . "<br>Targa: " . htmlspecialchars($dati_utente['targa_mezzo']); ?>
        </div>

        <h1>Dati sui chilometri</h1>

        <table>
            <tr>
                <th>Data</th>
                <th>Targa</th> <th>Divisione</th> <th>Filiale</th> <th>Chilometri iniziali</th>
                <th>Chilometri finali</th>
                <th>Litri carburante</th>
                <th>Euro spesi</th>
                <th>Note</th>
                <th>Cedolino</th> <th>Azioni</th>
            </tr>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row["data"]) . "</td>";
                    echo "<td>" . htmlspecialchars($dati_utente['targa_mezzo']) . "</td>"; // Mostra la targa dell'utente loggato
                    echo "<td>" . htmlspecialchars($dati_utente['divisione']) . "</td>"; // Mostra la divisione dell'utente loggato
                    echo "<td>" . htmlspecialchars($dati_utente['filiale']) . "</td>"; // Mostra la filiale dell'utente loggato
                    echo "<td>" . htmlspecialchars($row["chilometri_iniziali"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["chilometri_finali"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["litri_carburante"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["euro_spesi"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["note"]) . "</td>";
                    echo "<td>";
                    if (!empty($row["percorso_cedolino"])) {
                        echo "<div class='cedolino-container'>";
                        echo "<a href='#' class='cedolino-link' onclick=\"window.open('" . htmlspecialchars($row["percorso_cedolino"]) . "', 'Cedolino', 'width=600,height=800'); return false;\">Visualizza Cedolino</a>";
                        echo "<img src='" . htmlspecialchars($row["percorso_cedolino"]) . "' alt='Cedolino' class='cedolino-image'>";
                        echo "</div>";
                    } else {
                        echo "N/A"; // Oppure lascia vuoto, o metti un altro placeholder
                    }
                    echo "</td>";
                    echo "<td><a href='modifica.php?id=" . $row["id"] . "'>Modifica</a> | <a href='cancella.php?id=" . $row["id"] . "'>Cancella</a></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='11'>Nessun record trovato.</td></tr>"; // Aggiornato il colspan
            }
            ?>

            <?php
            if (isset($_GET['messaggio'])) {
                echo "<div style='color: green; margin-bottom: 10px;'>" . htmlspecialchars($_GET['messaggio']) . "</div>";
            }
            if (isset($_GET['errore'])) {
                echo "<div style='color: red; margin-bottom: 10px;'>" . htmlspecialchars($_GET['errore']) . "</div>";
            }
            ?>
        </table>
        <div class="pagination">
            <?php
            for ($i = 1; $i <= $totale_pagine; $i++) {
                if ($i == $pagina_corrente) {
                    echo "<strong>$i</strong>";
                } else {
                    echo "<a href='?pagina=$i'>$i</a>";
                }
            }
            ?>
        </div>

        <br><br>
        <a href="index.php">Torna all'inserimento</a>
    </div>

    <script>
        function openNav() {
            document.getElementById("mySidebar").style.width = "250px";
            document.getElementById("main").style.marginLeft = "250px";
        }

        function closeNav() {
            document.getElementById("mySidebar").style.width = "0";
            document.getElementById("main").style.marginLeft = "0";
        }
    </script>

    </body>
    </html>
<?php $conn->close(); ?>