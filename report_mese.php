<?php
// Includi file di configurazione e funzioni utili
include_once 'config.php'; // Contiene le impostazioni di connessione al database
include 'dati_utente.php'; // Gestisce le informazioni sull'utente corrente (es. sessione)

// Verifica se l'utente è autenticato
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Includi file di query
include 'query/qutenti.php'; // Funzioni per interagire con la tabella utenti
include 'query/q_costo_extra.php'; // Funzioni per recuperare dati relativi ai costi extra

// Ottieni l'username dalla sessione
$username = $_SESSION['username'];

// Recupera i dati dell'utente dal database
$utente_data = get_user_data($conn, $username);

// Ottieni il livello e la divisione dell'utente
$livello = $utente_data['livello'];
$divisione = $utente_data['divisione'];

/**
 * Funzione per recuperare le targhe dei mezzi accessibili all'utente in base al suo livello.
 *
 * @param mysqli $conn Connessione al database.
 * @param string $username Username dell'utente corrente.
 * @param int $livello Livello di autorizzazione dell'utente (1: tutti, 2: divisione, 3: singolo utente).
 * @param string $divisione Divisione dell'utente (rilevante solo per livello 2).
 * @return array Array di targhe dei mezzi.
 */
function get_user_targhe(mysqli $conn, string $username, int $livello, string $divisione): array
{
    $sql = "";
    $params = [];

    switch ($livello) {
        case 1: // Livello 1: Visualizza le targhe di tutti gli utenti tranne se stesso
            $sql = "SELECT DISTINCT targa_mezzo FROM utenti WHERE username != ?";
            $params = [$username];
            break;
        case 2: // Livello 2: Visualizza le targhe degli utenti nella sua stessa divisione tranne se stesso
            $sql = "SELECT DISTINCT targa_mezzo FROM utenti WHERE divisione = ? AND username != ?";
            $params = [$divisione, $username];
            break;
        case 3: // Livello 3: Visualizza solo la sua targa
            $sql = "SELECT DISTINCT targa_mezzo FROM utenti WHERE username = ?";
            $params = [$username];
            break;
        default:
            return []; // Livello non valido
    }

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Errore nella preparazione della query: " . $conn->error);
    }

    // Binding dinamico dei parametri
    if (!empty($params)) {
        $types = str_repeat("s", count($params));
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $targhe = [];

    while ($row = $result->fetch_assoc()) {
        $targhe[] = $row['targa_mezzo'];
    }

    $stmt->close();
    return $targhe;
}

// Recupera le targhe dei mezzi accessibili all'utente
$targhe_mezzo_utente = get_user_targhe($conn, $username, $livello, $divisione);

// Messaggio se non ci sono targhe disponibili per l'utente
if (empty($targhe_mezzo_utente)) {
    echo "<p>Nessuna targa trovata per il tuo livello di autorizzazione.</p>";
}

// Ottieni l'username dalla sessione per utilizzarlo nelle query
$username_session = $_SESSION['username'];
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

        /* Stili per la tabella responsive */
        .table-responsive {
            overflow-x: auto;
        }

        .progress-bar {
            height: 20px;
        }

        /* Stili opzionali per nascondere colonne su schermi piccoli */
        @media (max-width: 767.98px) {
            .col-hide-mobile {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="fixed-top-elements">
        <button class="btn btn-outline-secondary menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMenu" aria-controls="mainMenu">
            <i class="bi bi-list"></i> Menu
        </button>
        <div class="username-display">
            <strong>Utente:</strong> <?php echo htmlspecialchars($dati_utente['username']); ?><br>
        </div>
    </div>

    <?php include 'include/menu.php'; ?>
    <div class="container" id="main-content">
        <h1 class="mb-4">Report KM</h1>

        <h2>Dati Annuali</h2>
        <div class="table-responsive shadow-sm mb-4">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Anno</th>
                        <th>Targa</th>
                        <th class="col-hide-mobile">Chilometri percorsi</th>
                        <th class="col-hide-mobile">Litri carburante</th>
                        <th class="col-hide-mobile">Euro spesi</th>
                        <th class="col-hide-mobile">Nr° Cedolini Reg.</th>
                        <th>% KM Vs/Target</th>
                        <th>Avanzamento</th>
                        <th class="col-hide-mobile">Target Annuale</th>
                        <th class="col-hide-mobile">Esubero KM</th>
                        <th class="col-hide-mobile">Extra costi</th>
                        <th>Chilometri Totali (Registrati)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
					// questa funzione crea le condizioni per la colorazione della barra di avanzanmento:
					function getProgressBarColor($percentage) {
					// Cambia colore gradualmente dal verde al rosso
					if ($percentage <= 25) {
					return '#28a745'; // Verde
					} elseif ($percentage <= 50) {
					return '#93c54b'; // Verde chiaro
					} elseif ($percentage <= 75) {
					return '#f0ad4e'; // Giallo/Arancione
					} elseif ($percentage <= 90) {
					return '#ff7043'; // Arancione/Rosso
					} else {
					return '#dc3545'; // Rosso
					}
					}
                    // Query base per recuperare i dati annuali
                    $sql_anno_base = "SELECT DATE_FORMAT(data, '%Y') AS anno, targa_mezzo, SUM(chilometri_finali - chilometri_iniziali) AS chilometri_percorsi, SUM(litri_carburante) AS litri_totali, SUM(euro_spesi) AS euro_totali, COUNT(*) AS conteggio_righe FROM chilometri WHERE ";
                    $sql_anno_where = "";
                    $params_anno = [];

                    // Costruisci la clausola WHERE in base al livello dell'utente
                    if ($livello == 1) {
                        $sql_anno_where = "1=1"; // Livello 1: visualizza tutti i dati
                    } elseif ($livello == 2) {
                        // Livello 2: visualizza i dati solo per le targhe associate alla sua divisione
                        if (!empty($targhe_mezzo_utente)) {
                            $placeholders = implode(',', array_fill(0, count($targhe_mezzo_utente), '?'));
                            $sql_anno_where = "targa_mezzo IN (" . $placeholders . ")";
                            $params_anno = $targhe_mezzo_utente;
                        } else {
                            $sql_anno_where = "1=0"; // Se non ci sono targhe, non mostrare nulla
                        }
                    } else {
                        // Livello 3 o altri: visualizza solo i suoi dati
                        $sql_anno_where = "username = ?";
                        $params_anno = [$username_session];
                    }

                    // Prepara la query completa per i dati annuali
                    $sql_anno = $conn->prepare($sql_anno_base . $sql_anno_where . " GROUP BY anno, targa_mezzo ORDER BY anno");

                    // Inizializza le variabili per l'output della tabella annuale
                    $esubero_km = '';
                    $extra_costi = '';

                    if ($sql_anno) {
                        // Binding dinamico dei parametri
                        if (!empty($params_anno)) {
                            $types = str_repeat("s", count($params_anno));
                            $sql_anno->bind_param($types, ...$params_anno);
                        }

                        $sql_anno->execute();
                        $result_anno = $sql_anno->get_result();

                        if ($result_anno->num_rows > 0) {
                            while ($row_anno = $result_anno->fetch_assoc()) {
                                $anno = $row_anno['anno'];
                                $targa_mezzo_riga = $row_anno['targa_mezzo'];

                                // Ottieni il totale dei chilometri registrati chiamando la funzione in qutenti.php
                                $totale_km_registrati = get_total_registered_kilometers($conn, $targa_mezzo_riga);

                                // Inizializza le variabili prima della query al target
                                $target_annuale = "Target non impostato";
                                $percentuale_percorsi = 0;

                                // Recupera il target annuale per l'anno e la targa corrente
                                $sql_target = $conn->prepare("SELECT target_chilometri FROM target_annuale WHERE anno = ? AND targa_mezzo = ?");
                                $sql_target->bind_param("is", $anno, $targa_mezzo_riga);
                                $sql_target->execute();
                                $result_target = $sql_target->get_result();

                                if ($result_target->num_rows > 0) {
                                    $row_target = $result_target->fetch_assoc();
                                    $target_annuale = $row_target['target_chilometri'];

                                    // Calcola la percentuale di chilometri percorsi rispetto al target
                                    if ($target_annuale != 0) {
                                        $percentuale_percorsi = ($row_anno['chilometri_percorsi'] / $target_annuale) * 100;
                                        if ($percentuale_percorsi > 100) {
                                            $percentuale_percorsi = 100; // Non superare il 100%
                                        }
                                    } else {
                                        $percentuale_percorsi = 0;
                                    }
                                }

                                $chilometri_percorsi = $row_anno["chilometri_percorsi"];
                                $target_valido = is_numeric($target_annuale);
                                $style_chilometri = '';
                                // Ottieni il totale dei chilometri registrati fino a questo anno chiamando la funzione in qutenti.php
                                $totale_km_registrati = get_total_registered_kilometers($conn, $targa_mezzo_riga, (int)$anno);

                                // Evidenzia in rosso se i chilometri percorsi superano il target
                                if ($target_valido && $chilometri_percorsi > $target_annuale) {
                                    $style_chilometri = 'class="bg-danger text-white"';
                                }

                                $esubero_km = '';
                                if ($target_valido) {
                                    $esubero_km_val = $chilometri_percorsi - $target_annuale;
                                    if ($esubero_km_val >= 0) {
                                        $esubero_km = htmlspecialchars(number_format($esubero_km_val, 0, ',', '.'));
                                    } else {
                                        $esubero_km = "";
                                    }
                                } else {
                                    $esubero_km = "N/A";
                                }
                                // Recupera il costo extra dal database in base alla targa del mezzo
                                $costo_extra = get_costo_extra($conn, $targa_mezzo_riga);

                                // Calcola i costi extra solo se c'è un esubero di chilometri e il costo extra è valido
                                $extra_costi = '';
                                if (is_numeric(str_replace('.', '', str_replace(',', '.', $esubero_km))) && is_numeric($costo_extra)) {
                                    $esubero_km_numerico = floatval(str_replace('.', '', str_replace(',', '.', $esubero_km)));
                                    $extra_costi = htmlspecialchars(number_format($esubero_km_numerico * $costo_extra, 2, ',', '.'));
                                } elseif ($esubero_km === 'N/A' || !is_numeric($costo_extra)) {
                                    $extra_costi = 'N/A';
                                }
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row_anno["anno"]) . "</td>";
                                echo "<td>" . htmlspecialchars($targa_mezzo_riga) . "</td>";
                                echo "<td class='col-hide-mobile' " . $style_chilometri . ">" . htmlspecialchars(number_format($chilometri_percorsi, 0, ',', '.')) . "</td>";
                                echo "<td class='col-hide-mobile'>" . htmlspecialchars(number_format($row_anno["litri_totali"], 2, ',', '.')) . "</td>";
                                echo "<td class='col-hide-mobile'>" . htmlspecialchars(number_format($row_anno["euro_totali"], 2, ',', '.')) . "</td>";
                                echo "<td class='col-hide-mobile'>" . htmlspecialchars($row_anno["conteggio_righe"]) . "</td>";
                                echo "<td>" . htmlspecialchars(number_format($percentuale_percorsi, 2)) . "%</td>";
								echo "<td><div class='progress' style='height: 20px;'><div class='progress-bar' role='progressbar' style='width: " . $percentuale_percorsi . "%; background-color: " . getProgressBarColor($percentuale_percorsi) . ";' aria-valuenow='" . $percentuale_percorsi . "' aria-valuemin='0' aria-valuemax='100'></div></div></td>";
                                echo "<td class='col-hide-mobile'>" . htmlspecialchars($target_annuale) . "</td>";
                                echo "<td class='col-hide-mobile'>" . $esubero_km . "</td>";
                                echo "<td class='col-hide-mobile'>" . $extra_costi . "</td>";
                                echo "<td>" . htmlspecialchars(number_format($totale_km_registrati, 0, ',', '.')) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='12'>Nessun dato annuale disponibile.</td></tr>";
                        }
                        $sql_anno->close();
                    } else {
                        echo "Errore nella preparazione della query annuale: " . $conn->error;
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <h2>Dati Mensili</h2>
        <form method="get" class="mb-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <label for="anno_selezionato" class="form-label">Anno:</label>
                    <select name="anno_selezionato" id="anno_selezionato" class="form-select form-select-sm">
                        <option value="tutti" <?php if (isset($_GET['anno_selezionato']) && $_GET['anno_selezionato'] == 'tutti') echo 'selected'; ?>>Tutti gli anni</option>
                        <?php
                        $sql_anni = $conn->prepare("SELECT DISTINCT DATE_FORMAT(data, '%Y') AS anno FROM chilometri ORDER BY anno DESC");
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
                        $sql_anni->close();
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="mese_selezionato" class="form-label">Mese:</label>
                    <select name="mese_selezionato" id="mese_selezionato" class="form-select form-select-sm">
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
                </div>
                <div class="col-md-3">
                    <label for="targa_selezionata" class="form-label">Targa:</label>
                    <select name="targa_selezionata" id="targa_selezionata" class="form-select form-select-sm">
                        <option value="tutte" <?php if (!isset($_GET['targa_selezionata']) || $_GET['targa_selezionata'] == 'tutte') echo 'selected'; ?>>Tutte le targhe</option>
                        <?php
                        $selected_targa_from_get = isset($_GET['targa_selezionata']) ? $_GET['targa_selezionata'] : null;
                        foreach ($targhe_mezzo_utente as $targa) {
                            $selected = ($selected_targa_from_get == $targa) ? 'selected' : '';
                            echo "<option value='$targa' $selected>" . htmlspecialchars($targa) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm">Filtra</button>
                </div>
            </div>
        </form>

        <div class="table-responsive shadow-sm">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Mese</th>
                        <th>Targa</th>
                        <th class="col-hide-mobile">Chilometri percorsi</th>
                        <th class="col-hide-mobile">Litri carburante</th>
                        <th class="col-hide-mobile">Euro spesi</th>
                        <th>Registrazioni inserite</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $anno_selezionato = isset($_GET['anno_selezionato']) ? $_GET['anno_selezionato'] : date('Y');
                    $mese_selezionato = isset($_GET['mese_selezionato']) ? $_GET['mese_selezionato'] : 'tutti';
                    $targa_selezionata = isset($_GET['targa_selezionata']) ? $_GET['targa_selezionata'] : 'tutte';

                    $where_clause_mese = "WHERE ";
                    $params_mese = [];

                    // Costruisci la clausola WHERE per i dati mensili in base al livello dell'utente
                    if ($livello == 1) {
                        $where_clause_mese .= "1=1";
                    } elseif ($livello == 2) {
                        if (!empty($targhe_mezzo_utente)) {
                            $placeholders = implode(',', array_fill(0, count($targhe_mezzo_utente), '?'));
                            $where_clause_mese .= "targa_mezzo IN (" . $placeholders . ")";
                            $params_mese = $targhe_mezzo_utente;
                        } else {
                            $where_clause_mese .= "1=0";
                        }
                    } else {
                        $where_clause_mese .= "username = ?";
                        $params_mese[] = $username_session;
                    }

                    // Applica i filtri selezionati
                    if ($anno_selezionato != 'tutti') {
                        $where_clause_mese .= " AND DATE_FORMAT(data, '%Y') = ?";
                        $params_mese[] = $anno_selezionato;
                    }
                    if ($mese_selezionato != 'tutti') {
                        $where_clause_mese .= " AND DATE_FORMAT(data, '%m') = ?";
                        $params_mese[] = sprintf('%02d', $mese_selezionato); // Assicura che il mese abbia due cifre
                    }
                    if ($targa_selezionata != 'tutte') {
                        $where_clause_mese .= " AND targa_mezzo = ?";
                        $params_mese[] = $targa_selezionata;
                    }

                    // Rimuovi il "WHERE " iniziale se non ci sono condizioni
                    if ($where_clause_mese == "WHERE ") {
                        $where_clause_mese = "";
                    }

                    // Prepara la query per i dati mensili
                    $sql_mese = $conn->prepare("SELECT DATE_FORMAT(data, '%Y-%m') AS mese, targa_mezzo, SUM(chilometri_finali - chilometri_iniziali) AS chilometri_percorsi, SUM(litri_carburante) AS litri_totali, SUM(euro_spesi) AS euro_totali, COUNT(*) AS conteggio_righe FROM chilometri " . $where_clause_mese . " GROUP BY mese, targa_mezzo ORDER BY mese, targa_mezzo");

                    if ($sql_mese) {
                        // Binding dinamico dei parametri
                        if (!empty($params_mese)) {
                            $types = str_repeat("s", count($params_mese));
                            $sql_mese->bind_param($types, ...$params_mese);
                        }

                        $sql_mese->execute();
                        $result_mese = $sql_mese->get_result();

                        if ($result_mese->num_rows > 0) {
                            while ($row_mese = $result_mese->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row_mese["mese"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row_mese["targa_mezzo"]) . "</td>";
                                echo "<td class='col-hide-mobile'>" . htmlspecialchars(number_format($row_mese["chilometri_percorsi"], 0, ',', '.')) . "</td>";
                                echo "<td class='col-hide-mobile'>" . htmlspecialchars(number_format($row_mese["litri_totali"], 2, ',', '.')) . "</td>";
                                echo "<td class='col-hide-mobile'>" . htmlspecialchars(number_format($row_mese["euro_totali"], 2, ',', '.')) . "</td>";
                                echo "<td>" . htmlspecialchars($row_mese["conteggio_righe"]) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>Nessun risultato trovato con i filtri selezionati.</td></tr>";
                        }
                        $sql_mese->close();
                    } else {
                        echo "Errore nella preparazione della query per il filtro mensile: " . $conn->error;
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <a href="index.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Torna all'inserimento</a>
            <a href="visualizza.php" class="btn btn-info btn-sm"><i class="bi bi-eye me-1"></i> Visualizza i tuoi dati</a>
            <a href="imposta_target.php" class="btn btn-warning btn-sm"><i class="bi bi-bullseye me-1"></i> Imposta Target Annuale</a>
            <a href="esportazione_dati.php" class="btn btn-success btn-sm"><i class="bi bi-download me-1"></i> ESPORTA DATI</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>
<?php
// Queste impostazioni di errore sono utili in fase di sviluppo, ma andrebbero rimosse in produzione
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
?>