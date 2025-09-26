<?php
include 'query/qutenti.php'; // Includi il file delle query
include 'dati_utente.php';
include_once 'config.php'; // Assicurati che la connessione $conn sia stabilita qui

// Avvia la sessione se non lo è già
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
      header("Location: login.php");
      exit();
}

// Queste variabili vengono ottenute da 'dati_utente.php' incluso o dalla sessione.
// Le usiamo per il footer del PDF.
$Nome = isset($_SESSION['dati_utente']['Nome']) ? $_SESSION['dati_utente']['Nome'] : '';
$Cognome = isset($_SESSION['dati_utente']['Cognome']) ? $_SESSION['dati_utente']['Cognome'] : '';


$rows_json = isset($_GET['rows']) ? $_GET['rows'] : '[]'; // Gestisce caso 'rows' non presente
$rows_selezionate = json_decode($rows_json, true);

if (empty($rows_selezionate)) {
      // Aggiungi un messaggio di errore o reindirizza se non ci sono righe selezionate
    echo "Nessuna riga selezionata o dati non validi.";
      exit();
}

require_once('tcpdf/tcpdf.php');

// Estendi la classe TCPDF per personalizzare l'header e il footer
class MYPDF extends TCPDF {
    // Proprietà per il testo del footer, titolo dell'header e info logo
      protected $footer_text;
    protected $header_title = 'Report Dati Mensili'; // Titolo che vuoi nell'header
    protected $logoPath = 'immagini/logo.png'; // Percorso del tuo logo
    protected $logoWidth = 40; // Larghezza del logo in mm

    // Metodo per impostare il testo del footer
      public function setFooterText($text) {
                  $this->footer_text = $text;
      }

    // Override del metodo Header() di TCPDF
    public function Header() {
        // Imposta il font per l'header
        $this->SetFont('helvetica', 'B', 12); // Font e dimensione per il titolo nell'header

        // Ottieni i margini correnti impostati
        $margins = $this->getMargins();
        $pageWidth = $this->GetPageWidth();

        // Calcola la posizione Y per il titolo (es. 5mm sotto il margine superiore)
        $titleY = $margins['top'] + 5;

        // Stampa il titolo dell'header allineato a sinistra
        // La Cell copre la larghezza fino all'area del logo
        $titleWidth = $pageWidth - $margins['left'] - $margins['right'] - $this->logoWidth - 5; // Spazio per titolo a sinistra del logo
        if ($titleWidth < 0) $titleWidth = $pageWidth - $margins['left'] - $margins['right']; // Se non c'è spazio per il logo, usa tutta la larghezza
        
        $this->SetXY($margins['left'], $titleY);
        $this->Cell($titleWidth, 10, $this->header_title, 0, 0, 'L', 0, '', 0, false, 'T', 'M');

        // Aggiungi il logo in alto a destra nell'header
        if (file_exists($this->logoPath)) {
            // Calcola la posizione X per allineare il logo a destra
            $logoX = $pageWidth - $margins['right'] - $this->logoWidth;
            $logoY = $margins['top']; // Posizione Y allineata al margine superiore

            // Assicurati che il logo non si sovrapponga al titolo se lo spazio è ridotto
            // Puoi aggiungere una logica qui per regolare Y del logo se necessario,
            // ma con un margine superiore sufficiente e il titolo spostato leggermente sotto,
            // dovrebbero coesistere. Usiamo 'T' (Top) allineamento verticale per l'immagine.
            $this->Image($this->logoPath, $logoX, $logoY, $this->logoWidth, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }

        // Linea di separazione (Opzionale)
        // Calcola la posizione Y per la linea, sotto il contenuto dell'header
        // Scegli un valore Y sufficiente a stare sotto logo e titolo
        $lineY = $margins['top'] + max(10 + 5, $this->logoWidth * (40/40) * 0.8) + 2; // Esempio: sotto titolo + piccolo buffer o sotto logo ridimensionato
        $this->Line($margins['left'], $lineY, $pageWidth - $margins['right'], $lineY);

        // Imposta la posizione Y per l'inizio del contenuto del body
        // TCPDF gestisce automaticamente l'inizio del body in base all'header margin.
        // Qui potresti impostare un extra spazio se necessario dopo la linea.
        // $this->SetY($lineY + 2); // Esempio: 2mm sotto la linea
    }


         // Override del metodo Footer() - Mantieni il metodo footer esistente
         public function Footer() {
                  // Posiziona il cursore a 15 mm dal fondo
                  $this->SetY(-15);
                  // Imposta il font
                  $this->SetFont('helvetica', 'I', 8);
                  // Stampa il testo personalizzato a sinistra
                  $this->Cell(0, 10, $this->footer_text, 0, 0, 'L');
                  // Stampa il numero di pagina a destra
                  $this->Cell(0, 10, 'Pagina '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, 0, 'R');
         }
}
// *** FINE DEFINIZIONE CLASSE MYPDF ***

// Rimuovi la definizione della classe PDF che estende FPDF
// class PDF extends FPDF { ... } // <--- RIMUOVERE QUESTA SEZIONE

// Crea un nuovo documento PDF con orientamento orizzontale e formato A4
$pdf = new MYPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Imposta le informazioni del documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Spazio S.p.a');
$pdf->SetTitle('Dati Mensili'); // Puoi mantenere questo titolo per i metadati del PDF
$pdf->SetSubject('Report dati mensili');
$pdf->SetKeywords('PDF, report, dati, mensili');

// Imposta i margini
// Aumenta il margine superiore per fare spazio all'header
$left_margin = 10;
$top_margin = 30; // *** AUMENTATO MARGINE SUPERIORE per l'HEADER ***
$right_margin = 10;
$bottom_margin = 10; // Margine per il footer

$pdf->SetMargins($left_margin, $top_margin, $right_margin);
// Imposta il margine dell'header (spazio riservato in alto per l'header)
$pdf->SetHeaderMargin(15); // *** IMPOSTATO MARGINE DELL'HEADER ***
$pdf->SetFooterMargin($bottom_margin); // Usa il margine inferiore definito

// Imposta l'interruzione di pagina automatica
$pdf->SetAutoPageBreak(TRUE, $bottom_margin + 5); // Aumenta il margine inferiore per il footer (TCPDF considera il footer al di sopra di questo margine)

// Ottieni il testo del footer
// Usa le variabili $Nome e $Cognome recuperate all'inizio
$footer_text = 'Generato da: ' . htmlspecialchars($Nome) . ' ' . htmlspecialchars($Cognome) . ' - Generato il: ' . date('d/m/Y H:i:s');

// Imposta il testo del footer chiamando il metodo nella classe MYPDF
$pdf->setFooterText($footer_text);

// Aggiungi una pagina
$pdf->AddPage();

// --- CODICE SPOSTATO NELL'HEADER() ---
// Rimuovi l'aggiunta del logo qui, ora è nell'Header()
/*
$pdf->Ln(8);
$logoPath = 'immagini/logo.png'; //percorso del tuo logo
$pageWidth = $pdf->getPageWidth();
$logoWidth = 40; // Larghezza del logo
$logoX = $pageWidth - $right_margin - $logoWidth;
$logoY = $top_margin;

if (file_exists($logoPath)) {
      $pdf->Image($logoPath, $logoX, $logoY, $logoWidth, 0, 'PNG', '', '', false, 300, '', false, false, 0, false, false, false);
}
*/

// Rimuovi il titolo principale qui, ora un titolo simile è nell'Header()
/*
$pdf->Ln(7);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Dati Mensili Selezionati Raggruppati per Targa', 0, 1, 'C');
$pdf->Ln(2);
*/

// --- INIZIO CONTENUTO DEL BODY ---
// Potresti voler aggiungere uno spazio qui se l'header non è sufficiente,
// ma il SetHeaderMargin dovrebbe gestirlo.
// $pdf->Ln(5); // Esempio: Aggiungi un piccolo spazio dopo l'header


// Stili per la tabella
$pdf->SetFont('helvetica', '', 9); // Ridotto il font della tabella a 9
$pdf->SetFillColor(240, 240, 240);
$pdf->SetTextColor(0);
$pdf->SetDrawColor(200, 200, 200);
$pdf->SetLineWidth(0.3);

// Intestazione della tabella - La stampa dell'intestazione per ogni gruppo di targa è gestita nel loop successivo
$header = array('Mese', 'Targa', 'Utente', 'Divisione', 'Chilometri', 'Litri', 'Euro', 'Registrazioni', 'Km Finali');
// Calcola le larghezze delle colonne
$usablePageWidth = $pdf->getPageWidth() - $left_margin - $right_margin;
$w = array(35, 35, 35, 25, 30, 30, 30, 25, 30);
$sum_w = array_sum($w);
if ($sum_w > $usablePageWidth) {
      $scale_factor = $usablePageWidth / $sum_w;
      foreach ($w as &$width) {
            $width *= $scale_factor;
      }
}

// La funzione printTableHeader ora stampa solo l'intestazione della tabella, non il titolo principale
function printTableHeader($pdf, $w, $header) {
      $pdf->SetFont('helvetica', 'B', 9);
      $pdf->SetFillColor(220, 220, 220);
      for ($i = 0; $i < count($header); $i++) {
            $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
      }
      $pdf->Ln();
      $pdf->SetFont('helvetica', '', 9);
      $pdf->SetFillColor(255, 255, 255);
}

// Dati della tabella
$fill = 0;

// Inizializza le variabili per i totali generali
$totale_chilometri_generale = 0;
$totale_litri_generale = 0;
$totale_euro_generale = 0;

// Raggruppa le righe selezionate per targa
$dati_raggruppati_per_targa = [];
foreach ($rows_selezionate as $riga) {
      $targa = $riga['Targa'];
      if (!isset($dati_raggruppati_per_targa[$targa])) {
            $dati_raggruppati_per_targa[$targa] = [];
      }
      $dati_raggruppati_per_targa[$targa][] = $riga;
}

// Ordina i dati raggruppati per targa (opzionale ma utile)
ksort($dati_raggruppati_per_targa);

foreach ($dati_raggruppati_per_targa as $targa_corrente => $righe_targa) {
    // Aggiungi uno spazio o un titolo per ogni gruppo di targa nel body
    // Questo titolo NON si ripeterà in ogni pagina, solo all'inizio del gruppo
      $pdf->SetFont('helvetica', 'B', 12);
      $pdf->Cell(0, 10, 'Targa: ' . $targa_corrente, 0, 1, 'L');
    // Puoi aggiungere qui l'intestazione della tabella per ogni gruppo, come facevi prima.
    // Se preferisci che l'intestazione della tabella appaia solo una volta per pagina
    // dopo l'header PDF, la logica andrebbe spostata e gestita dalla libreria PDF.
    // Manteniamo l'approccio attuale di stampare l'intestazione per ogni gruppo di targa per coerenza con il codice precedente.
      printTableHeader($pdf, $w, $header);
      $fill = 0;

      // Inizializza le variabili per i totali della targa corrente
      $totale_chilometri_targa = 0;
      $totale_litri_targa = 0;
      $totale_euro_targa = 0;

      $num_righe_targa = count($righe_targa);
      $counter_righe_targa = 0;

      foreach ($righe_targa as $riga) {
            $counter_righe_targa++;
            $border = 'LR';
            if ($counter_righe_targa === $num_righe_targa) {
                  $border = 'LRB';
            }

            // Recupera i dati dalla riga selezionata (dal JSON decodificato)
            // Usa le chiavi in minuscolo come inviate dal javascript
            $mese = $riga['Mese'];
            $utente = $riga['Utente'];
            $chilometri_percorsi = $riga['km_percorsi'];
            $litri_totali = $riga['litri'];
            $euro_totali = $riga['euro']; // CORRETTO
            $conteggio_righe = $riga['registrazioni'];
            $km_finali_mese = $riga['km_finali_Mese'];

        // Recupera la divisione per l'Utente corrente (richiede una query separata)
        $divisione_utente = 'N/D';
        $sql_divisione = $conn->prepare("SELECT divisione FROM utenti WHERE username = ?");
        if ($sql_divisione) {
            $sql_divisione->bind_param("s", $utente);
            $sql_divisione->execute();
            $result_divisione = $sql_divisione->get_result();
            if ($row_divisione = $result_divisione->fetch_assoc()) {
                $divisione_utente = $row_divisione['divisione'];
            }
            $sql_divisione->close();
        } else {
             error_log("Errore preparazione query divisione: " . $conn->error);
        }

            // Stampa le celle della riga
            $pdf->Cell($w[0], 7, $mese, $border, 0, 'C', $fill);
            $pdf->Cell($w[1], 7, $targa_corrente, $border, 0, 'C', $fill);
            $pdf->Cell($w[2], 7, $utente, $border, 0, 'L', $fill);
        $pdf->Cell($w[3], 7, $divisione_utente, $border, 0, 'L', $fill);
            $pdf->Cell($w[4], 7, number_format($chilometri_percorsi, 0, ',', '.'), $border, 0, 'R', $fill);
            $pdf->Cell($w[5], 7, number_format($litri_totali, 2, ',', '.'), $border, 0, 'R', $fill);
            $pdf->Cell($w[6], 7, number_format($euro_totali, 2, ',', '.') . ' €', $border, 0, 'R', $fill);
            $pdf->Cell($w[7], 7, $conteggio_righe, $border, 0, 'R', $fill);
            $pdf->Cell($w[8], 7, number_format($km_finali_mese, 0, ',', '.'), $border, 0, 'R', $fill);
            $pdf->Ln();
            $fill = !$fill;

            // Aggiorna i totali generali
            $totale_chilometri_generale += $chilometri_percorsi;
            $totale_litri_generale += $litri_totali;
            $totale_euro_generale += $euro_totali;

            // Aggiorna i totali della targa corrente
            $totale_chilometri_targa += $chilometri_percorsi;
            $totale_litri_targa += $litri_totali;
            $totale_euro_targa += $euro_totali;

      }
      // Stampa i totali per la targa corrente
      $pdf->SetFont('helvetica', 'B', 10);
      $label_width_targa = $w[0] + $w[1] + $w[2] + $w[3];
      $pdf->Cell($label_width_targa, 6, 'Totali Targa ' . $targa_corrente . ':', 0, 0, 'R');
      $pdf->Cell($w[4], 6, number_format($totale_chilometri_targa, 0, ',', '.'), 0, 0, 'R');
      $pdf->Cell($w[5], 6, number_format($totale_litri_targa, 2, ',', '.'), 0, 0, 'R');
      $pdf->Cell($w[6], 6, number_format($totale_euro_targa, 2, ',', '.') . ' €', 0, 0, 'R');
      $pdf->Cell($w[7], 6, '', 0, 0, 'C');
      $pdf->Cell($w[8], 6, '', 0, 1, 'C');
      $pdf->Ln(1);
}

// Stampa i totali generali
$pdf->SetFont('helvetica', 'B', 10);
$label_width_generali = $w[0] + $w[1] + $w[2] + $w[3];
$pdf->Cell($label_width_generali, 6, 'Totali Generali:', 0, 0, 'R');
$pdf->Cell($w[4], 6, number_format($totale_chilometri_generale, 0, ',', '.'), 0, 0, 'R');
$pdf->Cell($w[5], 6, number_format($totale_litri_generale, 2, ',', '.'), 0, 0, 'R');
      $pdf->Cell($w[6], 6, number_format($totale_euro_generale, 2, ',', '.') . ' €', 0, 0, 'R');
$pdf->Cell($w[7], 6, '', 0, 0, 'C');
$pdf->Cell($w[8], 6, '', 0, 1, 'C');
$pdf->Ln(5);


$pdf->Output('dati_mensili.pdf', 'D');

// Chiudi la connessione al database se aperta
if (isset($conn) && $conn instanceof mysqli) {
      $conn->close();
}
?>