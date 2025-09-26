<?php

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include_once 'config.php';
include 'query/qutenti.php'; // Assicurati che questo file esista e contenga la funzione get_user_data

$username = $_SESSION['username'];

// Recupera i dati dell'utente per ottenere il livello e la divisione
$utente_data = get_user_data($conn, $username);
$livello = $utente_data['livello'];
$divisione_utente_loggato = $utente_data['divisione']; // Rinominato per chiarezza

// Recupera i dati dell'utente per visualizzare il nome (questo rimane)
$sql_utente_nome = $conn->prepare("SELECT Nome, Cognome FROM utenti WHERE username = ?");
$sql_utente_nome->bind_param("s", $username);
$sql_utente_nome->execute();
$result_utente_nome = $sql_utente_nome->get_result();
$dati_utente_nome = $result_utente_nome->fetch_assoc();
$sql_utente_nome->close();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Report KM</title>
    <style>
        /* ... (il tuo CSS rimane invariato) ... */
        .sidebar {
            height: 100%; width: 0; position: fixed; z-index: 1; top: 0; left: 0;
            background-color: #111; overflow-x: hidden; transition: 0.5s; padding-top: 60px;
        }
        .sidebar a {
            padding: 8px 8px 8px 32px; text-decoration: none; font-size: 20px;
            color: #818181; display: block; transition: 0.3s;
        }
        .sidebar a:hover { color: #f1f1f1; }
        .sidebar .closebtn {
            position: absolute; top: 0; right: 25px; font-size: 36px; margin-left: 50px;
        }
        .openbtn {
            font-size: 20px; cursor: pointer; background-color: #111; color: white;
            padding: 10px 15px; border: none;
        }
        .openbtn:hover { background-color: #444; }
        #main { transition: margin-left .5s; padding: 16px; position: relative; margin-left: 0; }
        .username-display {
            position: absolute; top: 10px; right: 10px; color: #333; font-size: 16px;
            background-color: LightGray; padding: 5px; border-radius: 5px;
        }
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
        #main-content {
            width: 90%; margin: 20px auto; background-color: white; padding: 20px;
            border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h2 { color: #333; text-align: center; }
        form { margin-bottom: 20px; text-align: center; }
        select, input[type="submit"] { padding: 10px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; }
        input[type="submit"] { background-color: #007bff; color: white; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        button {
            padding: 10px 20px; margin: 5px; background-color: #28a745; color: white;
            border: none; border-radius: 4px; cursor: pointer;
        }
        button:hover { background-color: #218838; }
        #total-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        #total-table td { border: none; padding: 8px; text-align: right; font-weight: bold; }
        #total-table td:first-child { text-align: left; font-weight: normal; }
    </style>
</head>
<body>

<div id="mySidebar" class="sidebar">
    <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">×</a>
    <a href="visualizza.php">Visualizza tutti record</a>
    <a href="report_mese.php">Report Mensile</a>
    <a>================</a>
    <a href="imposta_target.php">Imposta Target Annuale</a>
    <a href="registrazione.php">Registrazione nuovo utente</a>
    <a href="gestisci_utenti.php">Gestione Anagrafica Utenti</a>
    <a href="logout.php">Logout</a>
</div>

<div id="main">
    <button class="openbtn" onclick="openNav()">☰ Apri Menu</button>

    <div class="username-display">
        <?php echo "Utente: " . htmlspecialchars($_SESSION['username']); ?>
    </div>

    <div id="main-content">
        <h2>Dati Mensili</h2>

        <form method="get">
            <select name="anno_selezionato">
                <?php
                // --- Logica selezione anno (leggermente modificata per usare JOIN dove serve) ---
                $anni = array();
                $sql_anni_base = "SELECT DISTINCT DATE_FORMAT(c.data, '%Y') AS anno FROM chilometri c";
                $sql_anni_order = " ORDER BY anno";
                $sql_anni_params = [];
                $sql_anni_types = "";

                if ($livello == 3) { // Filtra per l'utente loggato se è livello 3
                    $sql_anni_where = " WHERE c.username = ?";
                    $sql_anni_params[] = $username;
                    $sql_anni_types .= "s";
                } else if ($livello == 2) { // Filtra per divisione se è responsabile
                    $sql_anni_base .= " JOIN utenti u ON c.username = u.username"; // Join necessario
                    $sql_anni_where = " WHERE u.divisione = ?";
                    $sql_anni_params[] = $divisione_utente_loggato;
                    $sql_anni_types .= "s";
                } else { // Admin vede tutto
                    $sql_anni_where = "";
                }

                $sql_anni_final = $sql_anni_base . $sql_anni_where . $sql_anni_order;
                $sql_anni = $conn->prepare($sql_anni_final);

                if ($sql_anni === false) {
                    die("Errore preparazione query anni: " . $conn->error);
                }
                if (!empty($sql_anni_params)) {
                    if (!$sql_anni->bind_param($sql_anni_types, ...$sql_anni_params)) {
                        die("Errore binding parametri anni: " . $sql_anni->error);
                    }
                }
                if (!$sql_anni->execute()) {
                    die("Errore esecuzione query anni: " . $sql_anni->error);
                }


                $result_anni = $sql_anni->get_result();
                $current_year = date('Y');
                $selected_year_from_get = isset($_GET['anno_selezionato']) ? $_GET['anno_selezionato'] : $current_year;

                $found_selected_year = false;
                while ($row_anni = $result_anni->fetch_assoc()) {
                    $anno = $row_anni['anno'];
                    $selected = ($selected_year_from_get == $anno) ? 'selected' : '';
                    if ($selected) $found_selected_year = true;
                    echo "<option value='$anno' $selected>$anno</option>";
                }
                // Aggiungi l'anno corrente se non presente nei dati e se non è selezionato 'tutti'
                if (!$found_selected_year && $selected_year_from_get != 'tutti' && !in_array($current_year, $anni)) {
                    // Potresti voler aggiungere l'anno corrente alla lista anche se non ci sono record,
                    // dipende dalla logica desiderata. Qui lo omettiamo per coerenza con la query.
                }
                $sql_anni->close();


                ?>
                <option value="tutti" <?php if (isset($_GET['anno_selezionato']) && $_GET['anno_selezionato'] == 'tutti') echo 'selected'; ?>>Tutti gli anni</option>
            </select>
            <select name="mese_selezionato">
                <option value="tutti" <?php if (!isset($_GET['mese_selezionato']) || $_GET['mese_selezionato'] == 'tutti') echo 'selected'; ?>>Tutti i mesi</option>
                <option value="01" <?php if (isset($_GET['mese_selezionato']) && $_GET['mese_selezionato'] == '01') echo 'selected'; ?>>01 - Gennaio</option>
                <option value="02" <?php if (isset($_GET['mese_selezionato']) && $_GET['mese_selezionato'] == '02') echo 'selected'; ?>>02 - Febbraio</option>
                <option value="03" <?php if (isset($_GET['mese_selezionato']) && $_GET['mese_selezionato'] == '03') echo 'selected'; ?>>03 - Marzo</option>
                <option value="04" <?php if (isset($_GET['mese_selezionato']) && $_GET['mese_selezionato'] == '04') echo 'selected'; ?>>04 - Aprile</option>
                <option value="05" <?php if (isset($_GET['mese_selezionato']) && $_GET['mese_selezionato'] == '05') echo 'selected'; ?>>05 - Maggio</option>
                <option value="06" <?php if (isset($_GET['mese_selezionato']) && $_GET['mese_selezionato'] == '06') echo 'selected'; ?>>06 - Giugno</option>
                <option value="07" <?php if (isset($_GET['mese_selezionato']) && $_GET['mese_selezionato'] == '07') echo 'selected'; ?>>07 - Luglio</option>
                <option value="08" <?php if (isset($_GET['mese_selezionato']) && $_GET['mese_selezionato'] == '08') echo 'selected'; ?>>08 - Agosto</option>
                <option value="09" <?php if (isset($_GET['mese_selezionato']) && $_GET['mese_selezionato'] == '09') echo 'selected'; ?>>09 - Settembre</option>
                <option value="10" <?php if (isset($_GET['mese_selezionato']) && $_GET['mese_selezionato'] == '10') echo 'selected'; ?>>10 - Ottobre</option>
                <option value="11" <?php if (isset($_GET['mese_selezionato']) && $_GET['mese_selezionato'] == '11') echo 'selected'; ?>>11 - Novembre</option>
                <option value="12" <?php if (isset($_GET['mese_selezionato']) && $_GET['mese_selezionato'] == '12') echo 'selected'; ?>>12 - Dicembre</option>
            </select>

            <input type="submit" value="Filtra">
        </form>
        <table>
            <thead>
            <tr>
                <th>Seleziona</th>
                <th>Mese</th>
                <th>Targa</th>
                <th>Utente</th>
                <th>Divisione</th>
                <th>Chilometri percorsi</th>
                <th>Litri carburante</th>
                <th>Euro spesi</th>
                <th>Registrazioni inserite</th>
            </tr>
            </thead>
            <tbody>
            <?php

            $anno_selezionato = isset($_GET['anno_selezionato']) ? $_GET['anno_selezionato'] : date('Y');
            $mese_selezionato = isset($_GET['mese_selezionato']) ? $_GET['mese_selezionato'] : 'tutti';

            $totale_chilometri = 0;
            $totale_litri = 0;
            $totale_euro = 0;

            // --- Query Principale Modificata ---
            $base_sql = "SELECT
                            DATE_FORMAT(c.data, '%Y-%m') AS mese,
                            c.username,
                            u.divisione, -- Aggiunta divisione
                            c.targa_mezzo,
                            SUM(c.chilometri_finali - c.chilometri_iniziali) AS chilometri_percorsi,
                            SUM(c.litri_carburante) AS litri_totali,
                            SUM(c.euro_spesi) AS euro_totali,
                            COUNT(c.id) AS conteggio_righe -- Meglio contare su una colonna specifica
                        FROM
                            chilometri c
                        JOIN
                            utenti u ON c.username = u.username"; // JOIN con utenti

            $where_clause = "";
            $params = [];
            $types = ""; // Stringa per i tipi dei parametri

            // Applica filtro per livello utente
            if ($livello == 3) { // Livello 3 è Utente
                $where_clause = " WHERE c.username = ?";
                $params[] = $username;
                $types .= "s";
            } elseif ($livello == 2) { // Livello 2 è Responsabile
                $where_clause = " WHERE u.divisione = ?"; // Usa la divisione dell'utente loggato
                $params[] = $divisione_utente_loggato;
                $types .= "s";
            } // Livello 1 (Admin) non aggiunge filtri utente/divisione qui

            // Aggiungi filtri per anno e mese
            if ($anno_selezionato != 'tutti') {
                $where_clause .= ($where_clause == "" ? " WHERE" : " AND") . " DATE_FORMAT(c.data, '%Y') = ?";
                $params[] = $anno_selezionato;
                $types .= "s";
            }
            if ($mese_selezionato != 'tutti') {
                $where_clause .= ($where_clause == "" ? " WHERE" : " AND") . " DATE_FORMAT(c.data, '%m') = ?";
                $params[] = $mese_selezionato;
                $types .= "s";
            }

            // Aggiungi GROUP BY e ORDER BY
            // Raggruppa per tutti i campi non aggregati selezionati
            $group_order_sql = " GROUP BY mese, c.username, u.divisione, c.targa_mezzo
                                ORDER BY mese, c.username, c.targa_mezzo";

            // Combina le parti della query
            $final_sql = $base_sql . $where_clause . $group_order_sql;

            $sql_mese = $conn->prepare($final_sql);

            // Controllo errore preparazione
            if ($sql_mese === false) {
                die("Errore nella preparazione della query principale: " . $conn->error . "<br>SQL: " . $final_sql);
            }

            // Binding dei parametri se presenti
            if (!empty($params)) {
                if (!$sql_mese->bind_param($types, ...$params)) {
                    die("Errore nel binding dei parametri principali: " . $sql_mese->error);
                }
            }

            // Esecuzione query
            if (!$sql_mese->execute()) {
                die("Errore nell'esecuzione della query principale: " . $sql_mese->error);
            }

            $result_mese = $sql_mese->get_result();

            if ($result_mese) {
                if ($result_mese->num_rows > 0) {
                    while ($row_mese = $result_mese->fetch_assoc()) {
                        // Costruisci un valore univoco per la checkbox
                        $checkbox_value = htmlspecialchars($row_mese["mese"]) . "|" . htmlspecialchars($row_mese["targa_mezzo"]) . "|" . htmlspecialchars($row_mese["username"]);

                        echo "<tr>";
                        echo "<td><input type='checkbox' name='selected_rows[]' value='" . $checkbox_value . "'></td>"; // Valore checkbox modificato
                        echo "<td>" . htmlspecialchars($row_mese["mese"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row_mese["targa_mezzo"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row_mese["username"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row_mese["divisione"]) . "</td>"; // NUOVA CELLA DATI
                        echo "<td>" . htmlspecialchars($row_mese["chilometri_percorsi"]) . "</td>";
                        echo "<td>" . number_format($row_mese["litri_totali"], 2) . "</td>";
                        echo "<td>" . number_format($row_mese["euro_totali"], 2) . "</td>";
                        echo "<td>" . htmlspecialchars($row_mese["conteggio_righe"]) . "</td>";
                        echo "</tr>";

                        // Aggiorna i totali
                        $totale_chilometri += $row_mese["chilometri_percorsi"];
                        $totale_litri += $row_mese["litri_totali"];
                        $totale_euro += $row_mese["euro_totali"];
                    }

                    // --- Tabella dei Totali Aggiornata ---
                    echo "</tbody>"; // Chiudi il tbody esistente
                    echo "</table>"; // Chiudi la tabella dei dati

                    echo "<table id='total-table'>"; // Inizia una nuova tabella per i totali
                    echo "<tr>";
                    echo "<td><strong>Totali:</strong></td>";
                    echo "<td></td>"; // Spazio per Mese
                    echo "<td></td>"; // Spazio per Targa
                    echo "<td></td>"; // Spazio per Utente
                    echo "<td></td>"; // Spazio per Divisione (NUOVO)
                    echo "<td><strong>Chilometri:</strong></td>";
                    echo "<td style='text-align: right;'><strong>Litri:</strong></td>";
                    echo "<td style='text-align: right;'><strong>Euro:</strong></td>";
                    echo "<td></td>"; // Spazio per Registrazioni
                    echo "</tr>";
                    echo "<tr>";
                    echo "<td></td>"; // Spazio per "Totali:"
                    echo "<td></td>"; // Spazio per Mese
                    echo "<td></td>"; // Spazio per Targa
                    echo "<td></td>"; // Spazio per Utente
                    echo "<td></td>"; // Spazio per Divisione (NUOVO)
                    echo "<td style='text-align: right;'><strong>" . htmlspecialchars($totale_chilometri) . "</strong></td>";
                    echo "<td style='text-align: right;'><strong>" . number_format($totale_litri, 2) . "</strong></td>";
                    echo "<td style='text-align: right;'><strong>" . number_format($totale_euro, 2) . "</strong></td>";
                    echo "<td></td>"; // Spazio per Registrazioni
                    echo "</tr>";
                    echo "</table>";
                    // Fine tabella totali

                } else {
                    // Aggiorna colspan se non ci sono risultati
                    echo "<tr><td colspan='9'>0 risultati</td></tr>"; // Colspan aumentato a 9
                    echo "</tbody></table>"; // Chiudi tabella anche se vuota
                }
                $sql_mese->close(); // Chiudi lo statement qui
            } else {
                echo "<tr><td colspan='9'>Errore nel recupero dei risultati: " . $conn->error . "</td></tr>"; // Colspan aumentato a 9
                echo "</tbody></table>"; // Chiudi tabella in caso di errore
            }


            ?>
            <button onclick="sendEmail()">Invia Email</button>
            <button onclick="createPDF()">Crea PDF</button>
    </div>
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

    function sendEmail() {
        let selectedRows = getSelectedRowValues(); // Usa la nuova funzione helper
        if (selectedRows.length > 0) {
            // Decidi quali dati inviare. L'intero valore della checkbox potrebbe essere sufficiente
            // se send_email.php sa come interpretarlo (es. splittando per '|')
            window.location.href = 'send_email.php?rows=' + encodeURIComponent(JSON.stringify(selectedRows));
        } else {
            alert('Seleziona almeno una riga.');
        }
    }

    function createPDF() {
        let selectedRows = getSelectedRowValues(); // Usa la nuova funzione helper
        if (selectedRows.length > 0) {
            // Come per l'email, decidi quali dati inviare
            window.location.href = 'create_pdf.php?rows=' + encodeURIComponent(JSON.stringify(selectedRows)) + '&username=<?php echo urlencode($username); ?>';
        } else {
            alert('Seleziona almeno una riga.');
        }
    }

    // Funzione helper per ottenere i *valori* delle checkbox selezionate
    function getSelectedRowValues() {
        let selectedValues = [];
        let checkboxes = document.querySelectorAll('input[name="selected_rows[]"]:checked');
        checkboxes.forEach(function(checkbox) {
            selectedValues.push(checkbox.value); // Prende il valore (es. "2024-01|AB123CD|mariorossi")
        });
        return selectedValues;
    }
</script>
</body>
</html>