<?php

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include 'config.php';
include 'query/qutenti.php'; // Assicurati che questo file esista e contenga la funzione get_user_data

$username = $_SESSION['username'];

// Recupera i dati dell'utente per ottenere il livello e la divisione
$utente_data = get_user_data($conn, $username);
$livello = $utente_data['livello'];
$divisione = $utente_data['divisione'];

// Recupera i dati dell'utente per visualizzare il nome (questo rimane)
$sql_utente_nome = $conn->prepare("SELECT Nome, Cognome FROM utenti WHERE username = ?");
$sql_utente_nome->bind_param("s", $username);
$sql_utente_nome->execute();
$result_utente_nome = $sql_utente_nome->get_result();
$dati_utente_nome = $result_utente_nome->fetch_assoc();
$sql_utente_nome->close();

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report KM</title>
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

    <?php include 'include/menu.php'; ?>
    <div class="container" id="main-content">
        <h2 class="mb-4 text-center">Dati Mensili</h2>

        <form method="get" class="row justify-content-center mb-3">
            <div class="col-md-3">
                <select name="anno_selezionato" class="form-select">
                    <?php
                    $anni = array();
                    $sql_anni = $conn->prepare("SELECT DISTINCT DATE_FORMAT(data, '%Y') AS anno FROM chilometri ");
                    // Aggiungi la condizione WHERE in base al livello
                    if ($livello == 3) { // Filtra per l'utente loggato se è livello 3
                        $sql_anni = $conn->prepare("SELECT DISTINCT DATE_FORMAT(data, '%Y') AS anno FROM chilometri WHERE username = ? ORDER BY anno");
                        $sql_anni->bind_param("s", $username);
                    } else if ($livello == 2) { // Filtra per divisione se è responsabile
                        $sql_anni = $conn->prepare("SELECT DISTINCT DATE_FORMAT(data, '%Y') AS anno FROM chilometri JOIN utenti ON chilometri.username = utenti.username WHERE utenti.divisione = ? ORDER BY anno");
                        $sql_anni->bind_param("s", $divisione);
                    } else { // Admin vede tutto
                        $sql_anni = $conn->prepare("SELECT DISTINCT DATE_FORMAT(data, '%Y') AS anno FROM chilometri ORDER BY anno");
                    }
                    $sql_anni->execute();

                    $result_anni = $sql_anni->get_result();
                    $current_year = date('Y');
                    $selected_year_from_get = isset($_GET['anno_selezionato']) ? $_GET['anno_selezionato'] : $current_year;

                    while ($row_anni = $result_anni->fetch_assoc()) {
                        $anno = $row_anni['anno'];
                        $selected = ($selected_year_from_get == $anno) ? 'selected' : '';
                        echo "<option value='$anno' $selected>$anno</option>";
                    }
                    ?>
                    <option value="tutti" <?php if (isset($_GET['mese_selezionato']) && $_GET['mese_selezionato'] == 'tutti') echo 'selected'; ?>>Tutti i mesi</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="mese_selezionato" class="form-select">
                    <option value="tutti" <?php if (isset($_GET['mese_selezionato']) && $_GET['mese_selezionato'] == 'tutti') echo 'selected'; ?>>Tutti i mesi</option>
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
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-filter me-2"></i> Filtra</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Seleziona</th>
                        <th>Mese</th>
                        <th>Targa</th>
                        <th>Utente</th>
                        <th>Chilometri percorsi</th>
                        <th>Litri carburante</th>
                        <th>Euro spesi</th>
                        <th>Registrazioni inserite</th>
                        <th>Chilometri Totali (Registrati)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $anno_selezionato = isset($_GET['anno_selezionato']) ? $_GET['anno_selezionato'] : date('Y');
					$mese_selezionato = isset($_GET['mese_selezionato']) ? $_GET['mese_selezionato'] : date('m');

                    $totale_chilometri = 0;
                    $totale_litri = 0;
                    $totale_euro = 0;

                    $where_clause = "WHERE 1=1";
                    $params = [];

                    if ($livello == 3) { // Livello 3 è Utente
                        $where_clause = "WHERE username = ?";
                        $params[] = $username;
                    } elseif ($livello == 2) { // Livello 2 è Responsabile
                        $where_clause = "WHERE EXISTS (SELECT 1 FROM utenti WHERE utenti.username = chilometri.username AND utenti.divisione = ?)";
                        $params[] = $divisione;
                    } // Livello 1 (Admin) non ha bisogno di WHERE clause

                    if ($anno_selezionato != 'tutti') {
                        $where_clause .= " AND DATE_FORMAT(data, '%Y') = ?";
                        $params[] = $anno_selezionato;
                    }
                    if ($mese_selezionato != 'tutti') {
                        $where_clause .= " AND DATE_FORMAT(data, '%m') = ?";
                        $params[] = $mese_selezionato;
                    }

                    $sql_mese = $conn->prepare("SELECT DATE_FORMAT(data, '%Y-%m') AS mese, username, targa_mezzo, SUM(chilometri_finali - chilometri_iniziali) AS chilometri_percorsi, SUM(litri_carburante) AS litri_totali, SUM(euro_spesi) AS euro_totali, COUNT(*) AS conteggio_righe FROM chilometri " . $where_clause . " GROUP BY mese, targa_mezzo ORDER BY mese");

                    if ($sql_mese) {
                        $types = str_repeat("s", count($params));
                        if (!empty($params)) {
                            $sql_mese->bind_param($types, ...$params);
                        }
                        if ($sql_mese === false) {
                            die("Errore nella preparazione della query: " . $conn->error);
                        }

                        $sql_mese->execute();
                        $result_mese = $sql_mese->get_result();
                        if ($result_mese) {
                            if ($result_mese->num_rows > 0) {
                                while ($row_mese = $result_mese->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td><input type='checkbox' name='selected_rows[]' value='" . htmlspecialchars(json_encode(array('Mese' => $row_mese["mese"], 'Targa' => $row_mese["targa_mezzo"], 'Utente' => $row_mese["username"]))) . "'></td>";
                                    echo "<td>" . htmlspecialchars($row_mese["mese"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row_mese["targa_mezzo"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row_mese["username"]) . "</td>";
                                    $km_formatted = substr($row_mese["chilometri_percorsi"], 0, 1) . '.' . 
                substr($row_mese["chilometri_percorsi"], 1, 3);
									echo "<td>" . $km_formatted . "</td>";
                                    echo "<td>" . number_format($row_mese["litri_totali"], 2) . "</td>";
                                    echo "<td>" . number_format($row_mese["euro_totali"], 2) . "</td>";
                                    echo "<td>" . htmlspecialchars($row_mese["conteggio_righe"]) . "</td>";

                                    // Calcola e visualizza il totale dei chilometri registrati fino a questo mese
                                    $anno_mese = explode('-', $row_mese["mese"]);
                                    $anno_corrente = (int)$anno_mese[0];
                                    $mese_corrente = $anno_mese[1];
                                    $totale_km_registrati_mese = get_total_registered_kilometers($conn, $row_mese["targa_mezzo"], $anno_corrente, $mese_corrente);
                                    echo "<td>" . htmlspecialchars(number_format($totale_km_registrati_mese, 0, ',', '.')) . "</td>";

                                    echo "</tr>";

                                    // Aggiorna i totali
                                    $totale_chilometri += $row_mese["chilometri_percorsi"];
                                    $totale_litri += $row_mese["litri_totali"];
                                    $totale_euro += $row_mese["euro_totali"];
                                }
                            } else {
                                echo "<tr><td colspan='9'>0 risultati</td></tr>";
                            }
                        } else {
                            echo "Errore nel recupero dei risultati: " . $conn->error;
                        }
                    }

                    ?>
                </tbody>
            </table>
        </div>

        <table class="table table-bordered">
            <tfoot>
                <tr class="table-light">
                    <td colspan="4" class="text-end"><strong>Totali:</strong></td>
                    <td class="text-end"><strong>Totale Km:   <?php echo htmlspecialchars($totale_chilometri); ?></strong></td>
                    <td class="text-end"><strong>Totale Lt:   <?php echo number_format($totale_litri, 2); ?></strong></td>
                    <td class="text-end"><strong>Totale Eur:  <?php echo number_format($totale_euro, 2); ?></strong></td>
                 
                </tr>
            </tfoot>
        </table>

        <div class="text-center">
            <button onclick="sendEmail()" class="btn btn-success me-2"><i class="bi bi-envelope-fill me-2"></i> Invia Email</button>
            <button onclick="createPDF()" class="btn btn-danger"><i class="bi bi-file-earmark-pdf-fill me-2"></i> Crea PDF</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
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
            let selectedRows = getSelectedRows();
            if (selectedRows.length > 0) {
                window.location.href = 'send_email.php?rows=' + encodeURIComponent(JSON.stringify(selectedRows));
            } else {
                alert('Seleziona almeno una riga.');
            }
        }

        function createPDF() {
            let selectedRows = getSelectedRows();
            if (selectedRows.length > 0) {
                window.location.href = 'create_pdf.php?rows=' + encodeURIComponent(JSON.stringify(selectedRows)) + '&username=<?php echo urlencode($username); ?>';
            } else {
                alert('Seleziona almeno una riga.');
            }
        }

        function getSelectedRows() {
        let selectedRows = [];
        let checkboxes = document.querySelectorAll('input[name="selected_rows[]"]:checked');
        checkboxes.forEach(function(checkbox) {
            try {
                let rowData = JSON.parse(checkbox.value);
                selectedRows.push(rowData);
            } catch (e) {
                console.error("Errore nel parsing JSON:", e);
                // Gestisci l'errore se il parsing fallisce
            }
        });
        return selectedRows;
    }
    </script>
</body>
</html>