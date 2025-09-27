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
            SUM(c.litri_carburante) AS litri_totali,
            SUM(c.euro_spesi) AS euro_totali,
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
      GROUP BY Mese, c.username, c.Targa_mezzo
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
              table th, table td {
                  vertical-align: middle; /* Allinea verticalmente al centro */
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

            <form method="get" class="row justify-content-center mb-3 g-2"> <div class="col-md-3 col-sm-6">
                        <select name="anno_selezionato" class="form-select">
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
                        <select name="mese_selezionato" class="form-select">
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
                  <div class="col-md-3 col-sm-auto"> <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter me-2"></i> Filtra</button>
                  </div>
            </form>

            <div class="table-responsive shadow-sm"> <table class="table table-sm table-striped table-bordered table-hover caption-top" id="reportTable"> <caption><?php echo "Dati filtrati per Anno: <strong>" . htmlspecialchars($anno_selezionato ?? 'N/A') . "</strong>, Mese: <strong>" . htmlspecialchars($mese_selezionato == 'tutti' ? 'Tutti' : ($mese_selezionato ?? 'N/A')) . "</strong>"; ?></caption>
                        <thead class="table-light">
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
                  </table>
            </div>

            <table class="table table-bordered mt-3"> <tfoot>
                        <tr class="table-light">
                              <td colspan="4" class="text-end"><strong>Totali filtrati (Anno/Mese/Livello):</strong></td>
                              <td class="text-end"><strong>Km Percorsi: <?php echo htmlspecialchars(number_format($totale_chilometri_filtrati, 0, ',', '.')); ?></strong></td>
                              <td class="text-end"><strong>Litri: <?php echo number_format($totale_litri_filtrati, 2, ',', '.'); ?></strong></td>
                              <td class="text-end"><strong>Euro: <?php echo number_format($totale_euro_filtrati, 2, ',', '.'); ?> €</strong></td>
                              <td colspan="3"></td> </tr>
                  </tfoot>
            </table>


            <div class="text-center mt-4">
                    <p>Seleziona le righe desiderate e poi clicca su "Invia Email" o "Crea PDF".</p>
                  <button onclick="sendEmail()" class="btn btn-success me-2" title="Invia i dati selezionati via email"><i class="bi bi-envelope-fill me-2"></i> Invia Email</button>
                  <button onclick="createPDF()" class="btn btn-danger" title="Crea un PDF con i dati selezionati"><i class="bi bi-file-earmark-pdf-fill me-2"></i> Crea PDF</button>
            </div>
      </div>

      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
      <script>
            // Le funzioni openNav e closeNav sembrano relative ad un sidebar non presente in questo codice HTML
            // Potresti averle nel file include/menu.php. Le lascio commentate o le rimuovi se non usate.
            /*
            function openNav() {
                  document.getElementById("mySidebar").style.width = "250px";
                  document.getElementById("main").style.marginLeft = "250px";
            }

            function closeNav() {
                  document.getElementById("mySidebar").style.width = "0";
                  document.getElementById("main").style.marginLeft = "0";
            }
            */

        // *** NUOVO CODICE JAVASCRIPT PER SELECT ALL ***
        document.addEventListener('DOMContentLoaded', function () {
            const selectAllCheckbox = document.getElementById('selectAllCheckboxes');
            const checkboxes = document.querySelectorAll('input[name="selected_rows[]"]');

            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function () {
                    checkboxes.forEach(function (checkbox) {
                        checkbox.checked = selectAllCheckbox.checked;
                    });
                });

                // Opzionale: Aggiorna lo stato del "select all" se le singole caselle vengono cliccate
                checkboxes.forEach(function(checkbox) {
                    checkbox.addEventListener('change', function() {
                        if (!this.checked) {
                            selectAllCheckbox.checked = false;
                        } else {
                            // Controlla se tutte le singole caselle sono ora selezionate
                            const allChecked = Array.from(checkboxes).every(chk => chk.checked);
                            selectAllCheckbox.checked = allChecked;
                        }
                    });
                });
            }
        });
        // *** FINE NUOVO CODICE JAVASCRIPT ***


            function sendEmail() {
                  let selectedRows = getSelectedRows();
                  if (selectedRows.length > 0) {
                        // Passa i dati serializzati in JSON
                          const rowsData = selectedRows.map(row => ({
                                Mese: row.Mese,
                                Targa: row.Targa,
                                Utente: row.Utente,
                                km_percorsi: row.ChilometriPercorsi, // NOTA: Qui stai ancora usando le chiavi originali maiuscole nel map.
                                litri: row.LitriCarburante,           // Questo genera le chiavi in minuscolo nel JSON inviato.
                                euro: row.EuroSpesi,                 // È coerente con la correzione fatta nel PDF, ma è una potenziale fonte
                                registrazioni: row.Registrazioni,   // di confusione se le chiavi PHP originali fossero state usate altrove.
                                km_finali_Mese: row.KmFinaliMese     // Lascio così per mantenere la compatibilità con il PDF corretto.
                          }));
                        window.location.href = 'send_email.php?rows=' + encodeURIComponent(JSON.stringify(rowsData));
                  } else {
                        alert('Seleziona almeno una riga.');
                  }
            }

            function createPDF() {
                  let selectedRows = getSelectedRows();
                  if (selectedRows.length > 0) {
                          // Passa i dati serializzati in JSON, includendo il nuovo dato
                          const rowsData = selectedRows.map(row => ({
                                Mese: row.Mese,
                                Targa: row.Targa,
                                Utente: row.Utente,
                                km_percorsi: row.ChilometriPercorsi, // NOTA: Qui stai ancora usando le chiavi originali maiuscole nel map.
                                litri: row.LitriCarburante,           // Questo genera le chiavi in minuscolo nel JSON inviato.
                                euro: row.EuroSpesi,                 // È coerente con la correzione fatta nel PDF, ma è una potenziale fonte
                                registrazioni: row.Registrazioni,   // di confusione se le chiavi PHP originali fossero state usate altrove.
                                km_finali_Mese: row.KmFinaliMese     // Lascio così per mantenere la compatibilità con il PDF corretto.
                          }));
                          // Passa anche l'username dell'Utente loggato
                        window.location.href = 'create_pdf.php?rows=' + encodeURIComponent(JSON.stringify(rowsData)) + '&username=<?php echo urlencode($username); ?>';
                  } else {
                        alert('Seleziona almeno una riga.');
                  }
            }

            function getSelectedRows() {
                    let selectedRows = [];
                    let checkboxes = document.querySelectorAll('input[name="selected_rows[]"]:checked');
                    checkboxes.forEach(function(checkbox) {
                          try {
                                // Parse the JSON value stored in the checkbox
                                let rowData = JSON.parse(checkbox.value);
                                selectedRows.push(rowData);
                          } catch (e) {
                                console.error("Errore nel parsing JSON del checkbox:", e);
                                // Potresti voler aggiungere un feedback all'Utente qui
                          }
                    });
                    return selectedRows;
              }
      </script>
</body>
</html>
<?php
// Chiudi la connessione al database se aperta
if (isset($conn) && $conn instanceof mysqli) {
      $conn->close();
}
?>