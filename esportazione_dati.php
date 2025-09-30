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
// Assicurati che $utente_data non sia null (utente non trovato nel DB nonostante la sessione)
if ($utente_data === null) {
      // Gestisci il caso in cui l'utente loggato non venga trovato nel DB
      error_log("Utente loggato '$username' non trovato nel database durante recupero dati utente.");
      // Potresti voler fare un logout forzato o mostrare un errore
      header("Location: logout.php"); // Esempio: reindirizza al logout
      exit();
}
$livello = $utente_data['livello'];
$divisione = $utente_data['divisione'];

// --- Logica per i filtri Anno/Mese ---
$anno_selezionato = isset($_GET['anno_selezionato']) ? $_GET['anno_selezionato'] : date('Y');

// Gestione compatibilità per entrambi i formati del parametro mese
$mese_selezionato = '';
if (isset($_GET['mese_selezionato'])) {
    $mese_selezionato = $_GET['mese_selezionato'];
} elseif (isset($_GET['Mese_selezionato'])) {
    // Compatibilità per il vecchio formato
    $mese_selezionato = $_GET['Mese_selezionato'];
} else {
    $mese_selezionato = date('m');
}

// --- Costruzione dinamica della clausola WHERE e dei parametri ---
$where_clauses = [];
$params = [];
$types = "";

// Aggiungi filtri in base al livello Utente alla tabella principale 'c'
if ($livello == 3) { // Livello 3 è Utente
      $where_clauses[] = "c.username = ?";
      $params[] = $username;
      $types .= "s";
} elseif ($livello == 2) { // Livello 2 è Responsabile
      // Filtra per la divisione dell'Utente responsabile
      $where_clauses[] = "EXISTS (SELECT 1 FROM utenti WHERE utenti.username = c.username AND utenti.divisione = ?)";
      $params[] = $divisione;
      $types .= "s";
}
// Livello 1 (Admin) non ha filtri di livello sulla tabella principale

// Aggiungi filtro per l'anno selezionato (se non è 'tutti')
if ($anno_selezionato != 'tutti') {
      $where_clauses[] = "DATE_FORMAT(c.data, '%Y') = ?";
      $params[] = $anno_selezionato;
      $types .= "s";
}

// Aggiungi filtro per il mese selezionato (se non è 'tutti')
if ($mese_selezionato != 'tutti') {
      $where_clauses[] = "DATE_FORMAT(c.data, '%m') = ?";
      $params[] = $mese_selezionato;
      $types .= "s";
}

// Combina le clausole WHERE
$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// --- Costruzione della query SQL principale ---
// La query seleziona i dati aggregati per Mese/Utente/Targa E
// usa una subquery correlata per trovare i chilometri finali dell'ultima registrazione
// per quello specifico Mese/Utente/Targa.
$sql_Mese_text = "
      SELECT
            DATE_FORMAT(c.data, '%Y-%m') AS Mese,
            c.username,
            c.Targa_mezzo,
            SUM(c.chilometri_finali - c.chilometri_iniziali) AS chilometri_percorsi,
            SUM(CASE 
                WHEN c.litri_carburante IS NOT NULL AND c.litri_carburante != '' AND c.litri_carburante != '0' 
                THEN CAST(c.litri_carburante AS DECIMAL(10,2)) 
                ELSE 0 
            END) AS litri_totali,
            SUM(CASE 
                WHEN c.euro_spesi IS NOT NULL AND c.euro_spesi != '' 
                THEN c.euro_spesi 
                ELSE 0 
            END) AS euro_totali,
            COUNT(*) AS conteggio_righe,
            (
                  SELECT sub.chilometri_finali
                  FROM chilometri AS sub
                  WHERE sub.username = c.username
                     AND sub.Targa_mezzo = c.Targa_mezzo
                     AND DATE_FORMAT(sub.data, '%Y-%m') = DATE_FORMAT(c.data, '%Y-%m')
                  ORDER BY sub.data DESC, sub.id DESC
                  LIMIT 1
            ) AS ultimi_chilometri_Mese
      FROM chilometri AS c
      " . $where_sql . "
      GROUP BY DATE_FORMAT(c.data, '%Y-%m'), c.username, c.Targa_mezzo
      ORDER BY Mese DESC, c.username, c.Targa_mezzo"; // Ordina per Mese decrescente per mostrare i più recenti prima

// --- Recupera gli anni disponibili per il filtro ---
$sql_anni_text = "SELECT DISTINCT DATE_FORMAT(data, '%Y') AS anno FROM chilometri ";
$anno_params = [];
$anno_types = "";

// Applica gli stessi filtri di livello alla query degli anni
if ($livello == 3) { // Livello 3 è Utente
        $sql_anni_text .= "WHERE username = ? ";
        $anno_params[] = $username;
        $anno_types .= "s";
} else if ($livello == 2) { // Livello 2 è Responsabile
        $sql_anni_text .= "JOIN utenti ON chilometri.username = utenti.username WHERE utenti.divisione = ? ";
        $anno_params[] = $divisione;
        $anno_types .= "s";
}

$sql_anni_text .= "ORDER BY anno DESC"; // Ordina gli anni dal più recente

$sql_anni = $conn->prepare($sql_anni_text);
$anni_disponibili = []; // Array per memorizzare gli anni

if ($sql_anni) {
      if (!empty($anno_params)) {
              $sql_anni->bind_param($anno_types, ...$anno_params);
      }
      $sql_anni->execute();
      $result_anni = $sql_anni->get_result();
      while ($row_anni = $result_anni->fetch_assoc()) {
            $anni_disponibili[] = $row_anni['anno'];
      }
      $sql_anni->close();
} else {
        error_log("Errore preparazione query anni: " . $conn->error);
}


// Include header
include 'template/header.php';
?>
    <main class="main-content">
        <div class="container-fluid">
            <div class="content-wrapper">
                <!-- Header pagina -->
                <div class="page-header mb-4">
                    <div class="row align-items-center">
                        <div class="col">
                            <h1 class="h3 text-primary fw-bold mb-0">
                                <i class="bi bi-download me-2"></i>
                                Esportazione Dati
                            </h1>
                            <p class="text-muted mb-0">Filtra ed esporta i dati delle registrazioni chilometriche</p>
                        </div>
                        <div class="col-auto">
                            <div class="badge bg-primary bg-gradient">
                                <i class="bi bi-database me-1"></i>
                                Database KM
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form filtri -->
                <div class="card shadow-sm mb-4 slide-in">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-funnel me-2"></i>Filtri di Ricerca
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="esportazione_dati.php" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-md-3 col-sm-6">
                                    <label for="anno_selezionato" class="form-label">
                                        <i class="bi bi-calendar-year me-1"></i>Anno
                                    </label>
                                    <select name="anno_selezionato" id="anno_selezionato" class="form-select">
                                        <option value='tutti' <?php echo ($anno_selezionato == 'tutti') ? 'selected' : ''; ?>>Tutti gli anni</option>
                                        <?php
                                        foreach ($anni_disponibili as $anno) {
                                            $selected = ($anno_selezionato == $anno) ? 'selected' : '';
                                            echo "<option value='$anno' $selected>$anno</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label for="mese_selezionato" class="form-label">
                                        <i class="bi bi-calendar-month me-1"></i>Mese
                                    </label>
                                    <select name="mese_selezionato" id="mese_selezionato" class="form-select">
                                        <option value="tutti" <?php if ($mese_selezionato == 'tutti') echo 'selected'; ?>>Tutti i mesi</option>
                                        <option value="01" <?php if ($mese_selezionato == '01') echo 'selected'; ?>>01 - Gennaio</option>
                                        <option value="02" <?php if ($mese_selezionato == '02') echo 'selected'; ?>>02 - Febbraio</option>
                                        <option value="03" <?php if ($mese_selezionato == '03') echo 'selected'; ?>>03 - Marzo</option>
                                        <option value="04" <?php if ($mese_selezionato == '04') echo 'selected'; ?>>04 - Aprile</option>
                                        <option value="05" <?php if ($mese_selezionato == '05') echo 'selected'; ?>>05 - Maggio</option>
                                        <option value="06" <?php if ($mese_selezionato == '06') echo 'selected'; ?>>06 - Giugno</option>
                                        <option value="07" <?php if ($mese_selezionato == '07') echo 'selected'; ?>>07 - Luglio</option>
                                        <option value="08" <?php if ($mese_selezionato == '08') echo 'selected'; ?>>08 - Agosto</option>
                                        <option value="09" <?php if ($mese_selezionato == '09') echo 'selected'; ?>>09 - Settembre</option>
                                        <option value="10" <?php if ($mese_selezionato == '10') echo 'selected'; ?>>10 - Ottobre</option>
                                        <option value="11" <?php if ($mese_selezionato == '11') echo 'selected'; ?>>11 - Novembre</option>
                                        <option value="12" <?php if ($mese_selezionato == '12') echo 'selected'; ?>>12 - Dicembre</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-sm-12">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary flex-fill">
                                            <i class="bi bi-filter me-1"></i>Filtra Dati
                                        </button>
                                        <button type="button" class="btn btn-outline-success" onclick="esportaDati()">
                                            <i class="bi bi-download me-1"></i>Esporta CSV
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabella risultati -->
                <div class="card shadow-sm slide-in">
                    <div class="card-header bg-light">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-table me-2"></i>Risultati
                                </h5>
                                <small class="text-muted">
                                    <?php echo "Anno: <strong>" . htmlspecialchars($anno_selezionato ?? 'N/A') . "</strong>, Mese: <strong>" . htmlspecialchars($mese_selezionato == 'tutti' ? 'Tutti' : ($mese_selezionato ?? 'N/A')) . "</strong>"; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0" id="reportTable">
                                <thead class="table-primary sticky-top">
                              <tr>
                                    <th><input type="checkbox" id="selectAllCheckboxes"> Seleziona</th>                                     <th>Mese</th>
                                    <th>Targa</th>
                                    <th>Utente</th>
                                    <th>Km Percorsi (Mese)</th>
                                    <th>Litri (Mese)</th>
                                    <th>Euro Spesi (Mese)</th>
                                    <th>Registrazioni (Mese)</th>
                                    <th>Km Finali (Ultima Reg. Mese)</th> <th>Km Totali (Targa)</th>
                              </tr>
                        </thead>
                        <tbody>
                              <?php
                              $totale_chilometri_filtrati = 0; // Totali basati sul filtro
                              $totale_litri_filtrati = 0;
                              $totale_euro_filtrati = 0;


                              $sql_Mese = $conn->prepare($sql_Mese_text);

                              if ($sql_Mese) {
                                      // Binding dei parametri
                                      if (!empty($params)) {
                                            $sql_Mese->bind_param($types, ...$params);
                                      }

                                      $sql_Mese->execute();
                                      $result_Mese = $sql_Mese->get_result();

                                      if ($result_Mese) {
                                             if ($result_Mese->num_rows > 0) {
                                                     while ($row_Mese = $result_Mese->fetch_assoc()) {
                                                            echo "<tr>";
                                                            // Modificato il value del checkbox per includere i dati necessari
                                                            echo "<td><input type='checkbox' name='selected_rows[]' value='" . htmlspecialchars(json_encode(array('Mese' => $row_Mese["Mese"], 'Targa' => $row_Mese["Targa_mezzo"], 'Utente' => $row_Mese["username"], 'ChilometriPercorsi' => $row_Mese["chilometri_percorsi"], 'LitriCarburante' => $row_Mese["litri_totali"], 'EuroSpesi' => $row_Mese["euro_totali"], 'Registrazioni' => $row_Mese["conteggio_righe"], 'KmFinaliMese' => $row_Mese['ultimi_chilometri_Mese']))) . "'></td>";
                                                            echo "<td>" . htmlspecialchars($row_Mese["Mese"]) . "</td>";
                                                            echo "<td>" . htmlspecialchars($row_Mese["Targa_mezzo"]) . "</td>";
                                                            echo "<td>" . htmlspecialchars($row_Mese["username"]) . "</td>";
                                                            // Formatting Km percorsi del Mese
                                                            $km_percorsi_formatted = number_format($row_Mese["chilometri_percorsi"], 0, ',', '.');
                                                            echo "<td class='text-end'>" . $km_percorsi_formatted . "</td>"; // Allinea a destra
                                                            echo "<td class='text-end'>" . number_format($row_Mese["litri_totali"], 2, ',', '.') . "</td>"; // Allinea a destra
                                                            echo "<td class='text-end'>" . number_format($row_Mese["euro_totali"], 2, ',', '.') . " €</td>"; // Allinea a destra
                                                            echo "<td class='text-end'>" . htmlspecialchars($row_Mese["conteggio_righe"]) . "</td>"; // Allinea a destra
                                                            echo "<td class='text-end'>" . htmlspecialchars(number_format($row_Mese["ultimi_chilometri_Mese"], 0, ',', '.')) . "</td>"; // Nuova Colonna, allinea a destra

                                                            // Calcola e visualizza il totale dei chilometri registrati totali per quella Targa
                                                            // Questa query non è filtrata per Mese/anno, ma per Targa per dare il totale complessivo per il veicolo
                                                            $totale_km_Targa = 0;
                                                            $sql_total_km_Targa = $conn->prepare("SELECT MAX(chilometri_finali) AS max_km FROM chilometri WHERE Targa_mezzo = ?");
                                                            if($sql_total_km_Targa) {
                                                                    $sql_total_km_Targa->bind_param("s", $row_Mese["Targa_mezzo"]);
                                                                    $sql_total_km_Targa->execute();
                                                                    $result_total_km_Targa = $sql_total_km_Targa->get_result();
                                                                    if ($row_total_km_Targa = $result_total_km_Targa->fetch_assoc()) {
                                                                           $totale_km_Targa = $row_total_km_Targa['max_km'] ?? 0; // Usa ?? 0 per sicurezza
                                                                    }
                                                                    $sql_total_km_Targa->close();
                                                            } else {
                                                                    error_log("Errore preparazione query totale km Targa: " . $conn->error);
                                                            }
                                                            echo "<td class='text-end'>" . htmlspecialchars(number_format($totale_km_Targa, 0, ',', '.')) . "</td>"; // Allinea a destra

                                                            echo "</tr>";

                                                            // Aggiorna i totali per il report (questi totali sono basati sul filtro applicato)
                                                            $totale_chilometri_filtrati += $row_Mese["chilometri_percorsi"];
                                                            $totale_litri_filtrati += $row_Mese["litri_totali"];
                                                            $totale_euro_filtrati += $row_Mese["euro_totali"];
                                                     }
                                             } else {
                                                     echo "<tr><td colspan='10' class='text-center fst-italic text-muted p-3'>Nessun risultato trovato con i filtri selezionati.</td></tr>";
                                             }
                                      } else {
                                             echo "<tr><td colspan='10' class='text-center fst-italic text-muted p-3'>Errore nel recupero dei risultati: " . $conn->error . "</td></tr>";
                                      }
                                      $sql_Mese->close();
                              } else {
                                      echo "<tr><td colspan='10' class='text-center fst-italic text-muted p-3'>Errore nella preparazione della query principale: " . $conn->error . "</td></tr>";
                              }

                              ?>
                        </tbody>
                        <tfoot class="table-secondary">
                            <tr class="fw-bold">
                                <td colspan="4" class="text-end">Totali filtrati:</td>
                                <td class="text-end">
                                    <i class="bi bi-speedometer2 me-1"></i>
                                    <?php echo number_format($totale_chilometri_filtrati, 0, ',', '.'); ?> km
                                </td>
                                <td class="text-end">
                                    <i class="bi bi-fuel-pump me-1"></i>
                                    <?php echo number_format($totale_litri_filtrati, 2, ',', '.'); ?> L
                                </td>
                                <td class="text-end">
                                    <i class="bi bi-cash-coin me-1"></i>
                                    <?php echo number_format($totale_euro_filtrati, 2, ',', '.'); ?> €
                                </td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Azioni sui dati selezionati -->
        <div class="card shadow-sm mt-4 slide-in">
            <div class="card-body text-center">
                <h6 class="card-title text-muted mb-3">
                    <i class="bi bi-gear me-2"></i>Azioni sui dati selezionati
                </h6>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <button onclick="esportaDati()" class="btn btn-outline-info" title="Esporta tutti i dati visibili in CSV">
                        <i class="bi bi-download me-1"></i>Esporta CSV
                    </button>
                    <button onclick="sendEmail()" class="btn btn-success" title="Invia i dati selezionati via email">
                        <i class="bi bi-envelope-fill me-1"></i>Invia Email
                    </button>
                    <button onclick="createPDF()" class="btn btn-danger" title="Crea un PDF con i dati selezionati">
                        <i class="bi bi-file-earmark-pdf-fill me-1"></i>Crea PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Funzione per esportare tutti i dati visibili in CSV
function esportaDati() {
    const table = document.getElementById('reportTable');
    const rows = table.querySelectorAll('tr');
    let csvContent = '';
    
    rows.forEach((row, index) => {
        const cols = row.querySelectorAll(index === 0 ? 'th' : 'td');
        const rowData = [];
        
        cols.forEach((col, colIndex) => {
            if (colIndex > 0) { // Salta la colonna checkbox
                let text = col.textContent.trim();
                // Escape delle virgole e virgolette per CSV
                if (text.includes(',') || text.includes('"')) {
                    text = '"' + text.replace(/"/g, '""') + '"';
                }
                rowData.push(text);
            }
        });
        
        if (rowData.length > 0) {
            csvContent += rowData.join(',') + '\n';
        }
    });
    
    // Download del file CSV
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'esportazione_km_' + new Date().toISOString().split('T')[0] + '.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Select all functionality
document.addEventListener('DOMContentLoaded', function () {
    const selectAllCheckbox = document.getElementById('selectAllCheckboxes');
    const checkboxes = document.querySelectorAll('input[name="selected_rows[]"]');

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });

        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                if (!this.checked) {
                    selectAllCheckbox.checked = false;
                } else {
                    const allChecked = Array.from(checkboxes).every(chk => chk.checked);
                    selectAllCheckbox.checked = allChecked;
                }
            });
        });
    }
});

function sendEmail() {
    let selectedRows = getSelectedRows();
    if (selectedRows.length > 0) {
        const rowsData = selectedRows.map(row => ({
            Mese: row.Mese,
            Targa: row.Targa,
            Utente: row.Utente,
            km_percorsi: row.ChilometriPercorsi,
            litri: row.LitriCarburante,
            euro: row.EuroSpesi,
            registrazioni: row.Registrazioni,
            km_finali_Mese: row.KmFinaliMese
        }));
        window.location.href = 'send_email.php?rows=' + encodeURIComponent(JSON.stringify(rowsData));
    } else {
        showAlert('Seleziona almeno una riga per inviare l\'email.', 'warning');
    }
}

function createPDF() {
    let selectedRows = getSelectedRows();
    if (selectedRows.length > 0) {
        const rowsData = selectedRows.map(row => ({
            Mese: row.Mese,
            Targa: row.Targa,
            Utente: row.Utente,
            km_percorsi: row.ChilometriPercorsi,
            litri: row.LitriCarburante,
            euro: row.EuroSpesi,
            registrazioni: row.Registrazioni,
            km_finali_Mese: row.KmFinaliMese
        }));
        window.location.href = 'create_pdf.php?rows=' + encodeURIComponent(JSON.stringify(rowsData)) + '&username=<?php echo urlencode($username); ?>';
    } else {
        showAlert('Seleziona almeno una riga per creare il PDF.', 'warning');
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
            console.error("Errore nel parsing JSON del checkbox:", e);
        }
    });
    return selectedRows;
}
</script>

<?php
// Include footer
include 'template/footer.php';
?>