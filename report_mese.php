<?php
// --- Configurazione pagina ---
$page_title = "Report Mensile";
$page_description = "Report e statistiche mensili dei rifornimenti e chilometri";
$require_auth = true;
$require_config = true;

// Includi file di configurazione e funzioni utili
include_once 'config.php';
include 'dati_utente.php';

// Verifica se l'utente è autenticato
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Includi file di query
include 'query/qutenti.php';
include 'query/q_costo_extra.php';

// Ottieni l'username dalla sessione
$username = $_SESSION['username'];
$utente_data = get_user_data($conn, $username);
$livello = $utente_data['livello'];
$divisione = $utente_data['divisione'];

/**
 * Funzione per recuperare le targhe dei mezzi accessibili all'utente in base al suo livello.
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
            return [];
    }

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Errore nella preparazione della query: " . $conn->error);
    }

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

// Funzione per colorazione barra di avanzamento
function getProgressBarColor($percentage) {
    if ($percentage <= 25) return '#28a745';      // Verde
    elseif ($percentage <= 50) return '#93c54b';  // Verde chiaro
    elseif ($percentage <= 75) return '#f0ad4e';  // Giallo/Arancione
    elseif ($percentage <= 90) return '#ff7043';  // Arancione/Rosso
    else return '#dc3545';                         // Rosso
}

// Include header
include 'template/header.php';
?>

    <!-- Contenuto principale -->
    <main class="container" id="main-content">
        <div class="row">
            <div class="col-12">
                <!-- Header pagina -->
                <div class="card slide-in mb-4">
                    <div class="card-header">
                        <h1 class="mb-0 h4">
                            <i class="bi bi-bar-chart-line-fill me-2"></i>Report Mensile KM
                        </h1>
                        <small class="text-light">Statistiche e analisi dei rifornimenti</small>
                    </div>
                </div>

                <!-- Dati Annuali -->
                <div class="card slide-in mb-4">
                    <div class="card-header">
                        <h2 class="mb-0 h5">
                            <i class="bi bi-calendar-range me-2"></i>Dati Annuali
                        </h2>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th><i class="bi bi-calendar3 me-1"></i>Anno</th>
                                        <th><i class="bi bi-car-front me-1"></i>Targa</th>
                                        <th class="text-end d-none d-md-table-cell"><i class="bi bi-speedometer2 me-1"></i>Km Percorsi</th>
                                        <th class="text-end d-none d-lg-table-cell"><i class="bi bi-droplet-fill me-1"></i>Litri</th>
                                        <th class="text-end d-none d-lg-table-cell"><i class="bi bi-currency-euro me-1"></i>Euro</th>
                                        <th class="text-center d-none d-md-table-cell"><i class="bi bi-file-text me-1"></i>Cedolini</th>
                                        <th class="text-center">% Target</th>
                                        <th class="text-center">Avanzamento</th>
                                        <th class="text-end d-none d-xl-table-cell"><i class="bi bi-bullseye me-1"></i>Target</th>
                                        <th class="text-end d-none d-xl-table-cell"><i class="bi bi-exclamation-triangle me-1"></i>Esubero</th>
                                        <th class="text-end d-none d-xl-table-cell"><i class="bi bi-cash me-1"></i>Costi Extra</th>
                                        <th class="text-end">Km Totali</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Recupera i dati utente per la sessione
                                    $sql_user_session = $conn->prepare("SELECT username FROM utenti WHERE username = ?");
                                    $sql_user_session->bind_param("s", $username);
                                    $sql_user_session->execute();
                                    $result_user_session = $sql_user_session->get_result();
                                    $username_session = $result_user_session->fetch_assoc()['username'];
                                    $sql_user_session->close();

                                    // Query base per recuperare i dati annuali
                                    $sql_anno_base = "SELECT DATE_FORMAT(data, '%Y') AS anno, targa_mezzo, SUM(chilometri_finali - chilometri_iniziali) AS chilometri_percorsi, SUM(litri_carburante) AS litri_totali, SUM(euro_spesi) AS euro_totali, COUNT(*) AS conteggio_righe FROM chilometri WHERE ";
                                    $sql_anno_where = "";
                                    $params_anno = [];

                                    // Costruisci la clausola WHERE in base al livello dell'utente
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

                                    // Prepara la query completa per i dati annuali
                                    $sql_anno = $conn->prepare($sql_anno_base . $sql_anno_where . " GROUP BY anno, targa_mezzo ORDER BY anno DESC");

                                    $esubero_km = '';
                                    $extra_costi = '';

                                    if ($sql_anno) {
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
                                                $chilometri_percorsi = $row_anno["chilometri_percorsi"];

                                                // Ottieni il totale dei chilometri registrati
                                                $totale_km_registrati = get_total_registered_kilometers($conn, $targa_mezzo_riga, (int)$anno);

                                                // Inizializza le variabili per il target
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

                                                    if ($target_annuale != 0) {
                                                        $percentuale_percorsi = ($chilometri_percorsi / $target_annuale) * 100;
                                                        if ($percentuale_percorsi > 100) {
                                                            $percentuale_percorsi = 100;
                                                        }
                                                    }
                                                }

                                                $target_valido = is_numeric($target_annuale);
                                                $style_chilometri = '';

                                                // Evidenzia in rosso se i chilometri percorsi superano il target
                                                if ($target_valido && $chilometri_percorsi > $target_annuale) {
                                                    $style_chilometri = 'table-danger';
                                                }

                                                // Calcola esubero
                                                $esubero_km = '';
                                                if ($target_valido) {
                                                    $esubero_km_val = $chilometri_percorsi - $target_annuale;
                                                    if ($esubero_km_val >= 0) {
                                                        $esubero_km = number_format($esubero_km_val, 0, ',', '.');
                                                    }
                                                } else {
                                                    $esubero_km = "N/A";
                                                }

                                                // Recupera il costo extra dal database
                                                $costo_extra = get_costo_extra($conn, $targa_mezzo_riga);

                                                // Calcola i costi extra
                                                $extra_costi = '';
                                                if (is_numeric(str_replace('.', '', str_replace(',', '.', $esubero_km))) && is_numeric($costo_extra)) {
                                                    $esubero_km_numerico = floatval(str_replace('.', '', str_replace(',', '.', $esubero_km)));
                                                    $extra_costi = number_format($esubero_km_numerico * $costo_extra, 2, ',', '.');
                                                } elseif ($esubero_km === 'N/A' || !is_numeric($costo_extra)) {
                                                    $extra_costi = 'N/A';
                                                }

                                                echo "<tr class='$style_chilometri'>";
                                                echo "<td><span class='badge bg-primary'>$anno</span></td>";
                                                echo "<td class='fw-semibold'>" . htmlspecialchars($targa_mezzo_riga) . "</td>";
                                                echo "<td class='text-end fw-semibold d-none d-md-table-cell'>" . number_format($chilometri_percorsi, 0, ',', '.') . " <small class='text-muted'>km</small></td>";
                                                echo "<td class='text-end d-none d-lg-table-cell'>" . number_format($row_anno['litri_totali'], 1, ',', '.') . " <small class='text-muted'>L</small></td>";
                                                echo "<td class='text-end text-success fw-semibold d-none d-lg-table-cell'>" . number_format($row_anno['euro_totali'], 2, ',', '.') . " €</td>";
                                                echo "<td class='text-center d-none d-md-table-cell'><span class='badge bg-secondary'>" . $row_anno['conteggio_righe'] . "</span></td>";
                                                
                                                // Percentuale e barra di avanzamento
                                                if ($target_valido) {
                                                    $color = getProgressBarColor($percentuale_percorsi);
                                                    $perc_display = number_format($percentuale_percorsi, 1);
                                                    echo "<td class='text-center'>";
                                                    echo "<div class='progress' style='height: 20px;'>";
                                                    echo "<div class='progress-bar' role='progressbar' style='width: {$perc_display}%; background-color: {$color};' aria-valuenow='{$perc_display}' aria-valuemin='0' aria-valuemax='100'>";
                                                    echo "<span class='small fw-bold'>{$perc_display}%</span>";
                                                    echo "</div>";
                                                    echo "</div>";
                                                    echo "</td>";
                                                    echo "<td class='text-center'>";
                                                    echo "<div class='progress' style='height: 8px;'>";
                                                    echo "<div class='progress-bar bg-info' style='width: {$perc_display}%;'></div>";
                                                    echo "</div>";
                                                    echo "</td>";
                                                } else {
                                                    echo "<td class='text-center'><span class='text-muted small'>N/A</span></td>";
                                                    echo "<td class='text-center'><span class='text-muted small'>N/A</span></td>";
                                                }

                                                echo "<td class='text-end d-none d-xl-table-cell'>" . (is_numeric($target_annuale) ? number_format($target_annuale, 0, ',', '.') : $target_annuale) . "</td>";
                                                echo "<td class='text-end d-none d-xl-table-cell'>" . ($esubero_km !== '' && $esubero_km !== 'N/A' ? "<span class='text-danger fw-semibold'>$esubero_km km</span>" : "<span class='text-muted'>$esubero_km</span>") . "</td>";
                                                echo "<td class='text-end d-none d-xl-table-cell'>" . ($extra_costi !== '' && $extra_costi !== 'N/A' ? "<span class='text-danger fw-semibold'>$extra_costi €</span>" : "<span class='text-muted'>$extra_costi</span>") . "</td>";
                                                echo "<td class='text-end fw-bold'>" . number_format($totale_km_registrati, 0, ',', '.') . " <small class='text-muted'>km</small>";
                                                
                                                // Mostra km percorsi su mobile
                                                echo "<div class='small text-muted d-md-none'>" . number_format($chilometri_percorsi, 0, ',', '.') . " km percorsi</div>";
                                                echo "</td>";
                                                echo "</tr>";
                                                
                                                $sql_target->close();
                                            }
                                        } else {
                                            echo "<tr><td colspan='12' class='text-center py-4'>";
                                            echo "<i class='bi bi-inbox display-4 text-muted d-block mb-2'></i>";
                                            echo "<span class='text-muted'>Nessun dato disponibile per il report annuale</span>";
                                            echo "</td></tr>";
                                        }
                                        $sql_anno->close();
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Azioni rapide -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card slide-in">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    <i class="bi bi-table text-info me-2"></i>Tutte le Registrazioni
                                </h5>
                                <p class="card-text small text-muted">Visualizza l'elenco completo</p>
                                <a href="visualizza.php" class="btn btn-info">
                                    <i class="bi bi-eye me-2"></i>Visualizza
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card slide-in">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    <i class="bi bi-download text-warning me-2"></i>Esporta Dati
                                </h5>
                                <p class="card-text small text-muted">Scarica i dati in formato CSV</p>
                                <a href="esportazione_dati.php" class="btn btn-warning">
                                    <i class="bi bi-file-earmark-arrow-down me-2"></i>Esporta
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card slide-in">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    <i class="bi bi-file-earmark-pdf text-danger me-2"></i>Report PDF
                                </h5>
                                <p class="card-text small text-muted">Genera report in PDF</p>
                                <a href="create_pdf.php" class="btn btn-danger">
                                    <i class="bi bi-file-pdf me-2"></i>Genera
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

<?php 
// Include footer
include 'template/footer.php';
?>