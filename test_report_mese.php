<?php
include 'dati_utente.php';
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include_once 'config.php';
include 'query/qutenti.php';
$username = $_SESSION['username'];
$utente_data = get_user_data($conn, $username);
$sql_anni_query = "SELECT DISTINCT DATE_FORMAT(data, '%Y') AS anno FROM chilometri";
$livello = $utente_data['livello'];
$divisione = $utente_data['divisione'];
function get_user_targhe($conn, $username, $livello, $divisione) {
    $sql = "";
    $params = [];
    switch ($livello) {
        case 1:
            $sql = "SELECT DISTINCT targa_mezzo FROM utenti WHERE username != ?";
            $params = [$username];
            break;
        case 2:
            $sql = "SELECT DISTINCT targa_mezzo FROM utenti WHERE divisione = ? AND username != ?";
            $params = [$divisione, $username];
            break;
        case 3:
            $sql = "SELECT DISTINCT targa_mezzo FROM utenti WHERE username = ?";
            $params = [$username];
            break;
        default:
            return [];
    }
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Errore nella preparazione della query: " . $conn->error);
    }
    $stmt->bind_param(str_repeat("s", count($params)), ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $targhe = [];
    while ($row = $result->fetch_assoc()) {
        $targhe[] = $row['targa_mezzo'];
    }
    $stmt->close();
    return $targhe;
}
$targhe_mezzo_utente = get_user_targhe($conn, $username, $livello, $divisione);
if (empty($targhe_mezzo_utente)) {
    echo "<p>Nessuna targa trovata per il tuo livello di autorizzazione.</p>";
}
$username_session = $_SESSION['username'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Report KM</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        #main {
            padding: 20px;
            background-color: white;
            margin: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .progress-bar {
            width: 100%;
            background-color: #f0f0f0;
            border-radius: 5px;
            height: 20px;
        }
        .progress {
            height: 100%;
            background-color: #4CAF50;
            border-radius: 5px;
        }
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
            padding: 8px 8px 8px 32px;
            text-decoration: none;
            font-size: 20px;
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
        .openbtn {
            font-size: 20px;
            cursor: pointer;
            background-color: #111;
            color: white;
            padding: 10px 15px;
            border: none;
        }
        .openbtn:hover {
            background-color: #444;
        }
        .username-display {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #333;
            font-size: 16px;
            background-color: LightGray;
            padding: 5px;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        a {
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            background-color: #000000;
            color: white;
        }
        a:hover {
            background-color: #0056b3;
        }
        select, input[type="submit"] {
            padding: 8px;
            margin: 5px 5px 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<div id="mySidebar" class="sidebar">
    <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">×</a>
    <a href="index.php">Inserimento Nuovo Rifornimento</a>
    <a href="visualizza.php">Visualizza tutti record</a>
    <a>================</a>
    <a href="esportazione_dati.php">Esporta o invia dati</a>
    <a href="imposta_target.php">Imposta Target Annuale</a>
    <a href="registrazione.php">Registrazione nuovo utente</a>
    <a href="gestisci_utenti.php">Gestione Anagrafica Utenti</a>
    <a href="logout.php">Logout</a>
</div>
<div id="main">
    <button class="openbtn" onclick="openNav()">☰ Apri Menu</button>
    <div class="username-display">
        <?php echo "Utente: " . htmlspecialchars($_SESSION['username']);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        ?>
    </div>
    <h1>Report KM</h1>
    <h2>Dati Annuali</h2>
    <table border="1">
        <tr>
            <th>Anno</th>
            <th>Targa</th>
            <th>Chilometri percorsi</th>
            <th>Litri carburante</th>
            <th>Euro spesi</th>
            <th>Registrazioni inserite</th>
            <th>Percentuale chilometri percorsi</th>
            <th>Avanzamento</th>
            <th>Target Annuale</th>
        </tr>
        <?php
        $sql_anno_base = "SELECT DATE_FORMAT(data, '%Y') AS anno, targa_mezzo, SUM(chilometri_finali - chilometri_iniziali) AS chilometri_percorsi, SUM(litri_carburante) AS litri_totali, SUM(euro_spesi) AS euro_totali, COUNT(*) AS conteggio_righe FROM chilometri WHERE ";
        $sql_anno_where = "";
        $params_anno = [];
        if ($livello == 1) {
            $sql_anno_where = "1=1";
        } elseif ($livello == 2) {
            if (!empty($targhe_mezzo_utente)) {
                $placeholders = implode(',', array_fill(0, count($targhe_mezzo_utente), '?'));
                $sql_anno_where = "targa_mezzo IN (" . $placeholders . ")";
                $params_anno = $targhe_mezzo_utente;
            } else {
                $sql_anno_where = "1=0";
            }
        } else {
            $sql_anno_where = "username = ?";
            $params_anno = [$username_session];
        }
        $sql_anno = $conn->prepare($sql_anno_base . $sql_anno_where . " GROUP BY anno, targa_mezzo ORDER BY anno");
        if ($sql_anno) {
            $types = str_repeat("s", count($params_anno));
            if (!empty($params_anno)) {
                $sql_anno->bind_param($types, ...$params_anno);
            }
            $sql_anno->execute();
            $result_anno = $sql_anno->get_result();
            if ($result_anno->num_rows > 0) {
            while($row_anno = $result_anno->fetch_assoc()) {
                $anno = $row_anno['anno'];
                $targa_mezzo_riga = $row_anno['targa_mezzo'];
                $sql_target = $conn->prepare("SELECT target_chilometri FROM target_annuale WHERE anno = ? AND targa_mezzo = ?");
                $sql_target->bind_param("is", $anno, $targa_mezzo_riga);
                $sql_target->execute();
                $result_target = $sql_target->get_result();
                if ($result_target->num_rows > 0) {
                    $row_target = $result_target->fetch_assoc();
                    $target_annuale = $row_target['target_chilometri'];
                    if ($target_annuale != 0) {
                        $percentuale_percorsi = ($row_anno['chilometri_percorsi'] / $target_annuale) * 100;
                        if ($percentuale_percorsi > 100) {
                            $percentuale_percorsi = 100;
                        }
                    } else {
                        $percentuale_percorsi = 0;
                    }
                } else {
                    $target_annuale = "Target non impostato";
                    $percentuale_percorsi = 0;
                }

                $chilometri_percorsi = $row_anno["chilometri_percorsi"];
                $target_valido = is_numeric($target_annuale);
                $style_chilometri = '';
                if ($target_valido && $chilometri_percorsi > $target_annuale) {
                    $style_chilometri = 'style="background-color: red;"';
                }

                echo "<tr><td>" . $row_anno["anno"]. "</td><td>" . $targa_mezzo_riga. "</td><td " . $style_chilometri . ">" . $chilometri_percorsi. "</td><td>" . number_format($row_anno["litri_totali"], 2). "</td><td>" . number_format($row_anno["euro_totali"], 2). "</td><td>" . $row_anno["conteggio_righe"]. "</td><td>" . number_format($percentuale_percorsi, 2) . "%</td><td><div class='progress-bar'><div class='progress' style='width: " . $percentuale_percorsi . "%;'></div></div></td><td>" . $target_annuale . "</td></tr>";
            }
        } else {
            echo "<tr><td colspan='9'>0 risultati</td></tr>";
        }
        }
        ?>
    </table>
    <h2>Dati Mensili</h2>
    <form method="get">
        <select name="anno_selezionato">
            <option value="tutti" <?php if (!isset($_GET['anno_selezionato']) || $_GET['anno_selezionato'] == 'tutti') echo 'selected'; ?>>Tutti gli anni</option>
            <?php
            $anni = array();
            $sql_anni = $conn->prepare("SELECT DISTINCT DATE_FORMAT(data, '%Y') AS anno FROM chilometri ORDER BY anno");
            $sql_anni->execute();
            $result_anni = $sql_anni->get_result();
            $current_year = date('Y');
            $selected_year_from_get = isset($_GET['anno_selezionato']) ? $_GET['anno_selezionato'] : null;
            while ($row_anni = $result_anni->fetch_assoc()) {
                $anno = $row_anni['anno'];
                $selected = '';
                if ($selected_year_from_get) {
                    $selected = ($selected_year_from_get == $anno) ? 'selected' : '';
                } elseif (!$selected_year_from_get && $anno == $current_year) {
                    $selected = 'selected';
                }
                echo "<option value='$anno' $selected>$anno</option>";
            }
            ?>
        </select>
        <select name="mese_selezionato">
            <option value="tutti" <?php if (!isset($_GET['mese_selezionato']) || $_GET['mese_selezionato'] == 'tutti') echo 'selected'; ?>>Tutti i mesi</option>
            <?php
            $mesi = array(
                1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile', 5 => 'Maggio', 6 => 'Giugno',
                7 => 'Luglio', 8 => 'Agosto', 9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
            );
            $selected_mese_from_get = isset($_GET['mese_selezionato']) ? $_GET['mese_selezionato'] : null;
            foreach ($mesi as $numero_mese => $nome_mese) {
                $selected = ($selected_mese_from_get == $numero_mese) ? 'selected' : '';
                echo "<option value='$numero_mese' $selected>$nome_mese</option>";
            }
            ?>
        </select>
        <select name="targa_selezionata">
            <option value="tutte" <?php if (!isset($_GET['targa_selezionata']) || $_GET['targa_selezionata'] == 'tutte') echo 'selected'; ?>>Tutte le targhe</option>
            <?php
            $selected_targa_from_get = isset($_GET['targa_selezionata']) ? $_GET['targa_selezionata'] : null;
            foreach ($targhe_mezzo_utente as $targa) {
                $selected = ($selected_targa_from_get == $targa) ? 'selected' : '';
                echo "<option value='$targa' $selected>$targa</option>";
            }
            ?>
        </select>
        <input type="submit" value="Filtra">
    </form>
    <table border="1">
        <tr>
            <th>Mese</th>
            <th>Targa</th>
            <th>Chilometri percorsi</th>
            <th>Litri carburante</th>
            <th>Euro spesi</th>
            <th>Registrazioni inserite</th>
        </tr>
        <?php
        $anno_selezionato = isset($_GET['anno_selezionato']) ? $_GET['anno_selezionato'] : 'tutti';
        $mese_selezionato = isset($_GET['mese_selezionato']) ? $_GET['mese_selezionato'] : 'tutti';
        $targa_selezionata = isset($_GET['targa_selezionata']) ? $_GET['targa_selezionata'] : 'tutte';
        $sql_mese = null;
        $where_clause = "";
        $params_mese = [];
        if ($livello == 1) {
            $where_clause .= "WHERE 1=1";
        } elseif ($livello == 2) {
            if (!empty($targhe_mezzo_utente)) {
                $placeholders = implode(',', array_fill(0, count($targhe_mezzo_utente), '?'));
                $where_clause .= "WHERE targa_mezzo IN (" . $placeholders . ")";
                $params_mese = $targhe_mezzo_utente;
            } else {
                $where_clause .= "WHERE 1=0";
            }
        } else {
            $where_clause .= "WHERE username = ?";
            $params_mese[] = $username_session;
        }
        if ($anno_selezionato != 'tutti') {
            $where_clause .= " AND DATE_FORMAT(data, '%Y') = ?";
            $params_mese[] = $anno_selezionato;
        }
        if ($mese_selezionato != 'tutti') {
            $where_clause .= " AND DATE_FORMAT(data, '%m') = ?";
            $params_mese[] = $mese_selezionato;
        }
        if ($targa_selezionata != 'tutte') {
            if ($livello != 2) {
                $where_clause .= " AND targa_mezzo = ?";
                $params_mese[] = $targa_selezionata;
            } elseif ($livello == 2) {
                if (in_array($targa_selezionata, $targhe_mezzo_utente)) {
                    $where_clause = "WHERE targa_mezzo = ?";
                    $params_mese = [$targa_selezionata];
                    if ($anno_selezionato != 'tutti') {
                        $where_clause .= " AND DATE_FORMAT(data, '%Y') = ?";
                        $params_mese[] = $anno_selezionato;
                    }
                    if ($mese_selezionato != 'tutti') {
                        $where_clause .= " AND DATE_FORMAT(data, '%m') = ?";
                        $params_mese[] = $mese_selezionato;
                    }
                } else {
                    $where_clause = "WHERE 1=0";
                    $params_mese = [];
                }
            }
        } elseif ($livello == 2 && $targa_selezionata == 'tutte') {
        }
        if ($livello == 1 && $anno_selezionato == 'tutti' && $mese_selezionato == 'tutti' && $targa_selezionata == 'tutti') {
            $where_clause = str_replace("WHERE 1=1 AND", "WHERE", $where_clause);
            $where_clause = str_replace("WHERE 1=1", "", $where_clause);
        } elseif ($livello != 1 && strpos($where_clause, "WHERE") === 0 && strpos($where_clause, "AND") === 0) {
            $where_clause = "WHERE " . substr($where_clause, strpos($where_clause, "AND") + 5);
        }
        $sql_mese = $conn->prepare("SELECT DATE_FORMAT(data, '%Y-%m') AS mese, targa_mezzo, SUM(chilometri_finali - chilometri_iniziali) AS chilometri_percorsi, SUM(litri_carburante) AS litri_totali, SUM(euro_spesi) AS euro_totali, COUNT(*) AS conteggio_righe FROM chilometri " . $where_clause . " GROUP BY mese, targa_mezzo ORDER BY mese, targa_mezzo");
        if ($sql_mese) {
            $types = str_repeat("s", count($params_mese));
            if (!empty($params_mese)) {
                $sql_mese->bind_param($types, ...$params_mese);
            }
            $sql_mese->execute();
            $result_mese = $sql_mese->get_result();
            if ($result_mese->num_rows > 0) {
                while($row_mese = $result_mese->fetch_assoc()) {
                    echo "<tr><td>" . $row_mese["mese"]. "</td><td>" . $row_mese["targa_mezzo"]. "</td><td>" . $row_mese["chilometri_percorsi"]. "</td><td>" . number_format($row_mese["litri_totali"], 2). "</td><td>" . number_format($row_mese["euro_totali"], 2). "</td><td>" . $row_mese["conteggio_righe"]. "</td></tr>";
                }
            } else {
                echo "<tr><td colspan='6'>0 risultati</td></tr>";
            }
            $sql_mese->close();
        } else {
            echo "Errore: Impossibile costruire la query per il filtro mensile.";
        }
        ?>
    </table>
    <br>
    <a href="index.php">Torna all'inserimento</a> | <a href="visualizza.php">Visualizza i tuoi dati</a> | <a href="imposta_target.php">Imposta Target Annuale</a> | <a href="esportazione_dati.php">ESPORTA DATI</a>
</div>
<script>
    function openNav() {
        document.getElementById("mySidebar").style.width = "250px";
        document.getElementById("main").style.marginLeft = "250px";
    }
    function closeNav() {
        document.getElementById("mySidebar").style.width = "0";
        document.getElementById("main").style.marginLeft= "0";
    }
</script>
</body>
</html>