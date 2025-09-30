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


// Supporta sia GET che POST per la ricezione dei dati
$rows_json = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rows'])) {
    $rows_json = $_POST['rows'];
} elseif (isset($_GET['rows'])) {
    $rows_json = $_GET['rows'];
} else {
    $rows_json = '[]';
}

$rows_selezionate = json_decode($rows_json, true);

if (empty($rows_selezionate)) {
      // Aggiungi un messaggio di errore o reindirizza se non ci sono righe selezionate
    echo "Nessuna riga selezionata o dati non validi.";
      exit();
}

require_once('tcpdf/tcpdf.php');

// Estendi la classe TCPDF per personalizzare l'header e il footer
class MYPDF extends TCPDF {
      protected $footer_text;

      public function setFooterText($text) {
            $this->footer_text = $text;
      }

      // Override del metodo Footer()
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

// Crea un nuovo documento PDF con orientamento orizzontale e formato A4
$pdf = new MYPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Imposta le informazioni del documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Spazio S.p.a');
$pdf->SetTitle('Dati Mensili');
$pdf->SetSubject('Report dati mensili');
$pdf->SetKeywords('PDF, report, dati, mensili');

// Imposta i margini
$left_margin = 10; // Definisci i margini come variabili
$top_margin = 10;
$right_margin = 10;
$bottom_margin = 10; // Margine per il footer

$pdf->SetMargins($left_margin, $top_margin, $right_margin);
$pdf->SetHeaderMargin($top_margin);
$pdf->SetFooterMargin($bottom_margin); // Usa il margine inferiore definito

// Imposta l'interruzione di pagina automatica
$pdf->SetAutoPageBreak(TRUE, $bottom_margin + 5); // Aumenta il margine inferiore per il footer (TCPDF considera il footer al di sopra di questo margine)

// Imposta il font predefinito
$pdf->SetFont('helvetica', '', 10); // Ridotto il font predefinito a 10

// Ottieni il testo del footer
// Usa le variabili $Nome e $Cognome recuperate all'inizio
$footer_text = 'Generato da: ' . htmlspecialchars($Nome) . ' ' . htmlspecialchars($Cognome) . ' - Generato il: ' . date('d/m/Y H:i:s');

// Imposta il testo del footer
$pdf->setFooterText($footer_text);

// Aggiungi una pagina
$pdf->AddPage();

// Aggiungi il logo in alto a destra
$pdf->Ln(8);
$logoPath = 'immagini/logo.png'; //percorso del tuo logo
// Calcola la posizione x per allineare il logo a destra usando il margine corretto
$pageWidth = $pdf->getPageWidth();
// Correzione: Usa la variabile $right_margin definita in precedenza
$logoWidth = 40; // Larghezza del logo
$logoX = $pageWidth - $right_margin - $logoWidth;
$logoY = $top_margin; // Posizione Y dall'alto (usa il margine superiore)

if (file_exists($logoPath)) {
      $pdf->Image($logoPath, $logoX, $logoY, $logoWidth, 0, 'PNG', '', '', false, 300, '', false, false, 0, false, false, false);
}

// Titolo con stile
$pdf->Ln(7);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Dati Mensili Selezionati Raggruppati per Targa', 0, 1, 'C'); // Modifica il titolo
$pdf->Ln(2);

// Stili per la tabella
$pdf->SetFont('helvetica', '', 9); // Ridotto il font della tabella a 9
$pdf->SetFillColor(240, 240, 240);
$pdf->SetTextColor(0);
$pdf->SetDrawColor(200, 200, 200);
$pdf->SetLineWidth(0.3);

// Intestazione della tabella aggiornata
$header = array('Mese', 'Targa', 'Utente', 'Divisione', 'Km Finali', 'Chilometri', 'Litri', 'Euro', 'Registrazioni'); // *** AGGIUNTA COLONNA KM FINALI ***
// Calcola le larghezze delle colonne in base all'orientamento orizzontale e al formato A4
// Correzione: Calcola larghezza utilizzabile usando le variabili di margine
$usablePageWidth = $pdf->getPageWidth() - $left_margin - $right_margin;
// Larghezze ottimizzate per 9 colonne
$w = array(35, 35, 35, 25, 30, 30, 30, 25, 30); // *** AGGIORNATE LARGHEZZE E AGGIUNTA LARGHEZZA PER KM FINALI ***
$sum_w = array_sum($w);
if ($sum_w > $usablePageWidth) {
      // Calcola un fattore di scala se la somma delle larghezze supera la larghezza della pagina
      $scale_factor = $usablePageWidth / $sum_w;
      foreach ($w as &$width) {
            $width *= $scale_factor;
      }
}

// Stampa l'intestazione (sarà stampata per ogni gruppo di targa)
function printTableHeader($pdf, $w, $header) {
      $pdf->SetFont('helvetica', 'B', 9); // Font grassetto per l'intestazione
      $pdf->SetFillColor(220, 220, 220); // Colore di sfondo leggermente più scuro per l'intestazione
      for ($i = 0; $i < count($header); $i++) {
            // Rimosso fixHTMLAccessibility()
            $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1); // Altezza 7, centrato, sfondo, bordo
      }
      $pdf->Ln();
      $pdf->SetFont('helvetica', '', 9); // Ripristina il font normale per i dati
      $pdf->SetFillColor(255, 255, 255); // Ripristina il colore di sfondo bianco
}

// Dati della tabella
$fill = 0;

// Inizializza le variabili per i totali generali
$totale_chilometri_generale = 0;
$totale_litri_generale = 0;
$totale_euro_generale = 0;

// Raggruppa le righe selezionate per targa (il JSON è già raggruppato per mese/targa/utente, raggruppiamo ulteriormente per targa qui)
$dati_raggruppati_per_targa = [];
foreach ($rows_selezionate as $riga) {
      $targa = $riga['Targa']; // La chiave 'Targa' non è stata cambiata in JS
      if (!isset($dati_raggruppati_per_targa[$targa])) {
            $dati_raggruppati_per_targa[$targa] = [];
      }
      // Salva l'intera riga così come ricevuta dal JSON
      $dati_raggruppati_per_targa[$targa][] = $riga;
}

// Ordina i dati raggruppati per targa (opzionale ma utile)
ksort($dati_raggruppati_per_targa);


foreach ($dati_raggruppati_per_targa as $targa_corrente => $righe_targa) {
      $pdf->SetFont('helvetica', 'B', 12);
      $pdf->Cell(0, 10, 'Targa: ' . $targa_corrente, 0, 1, 'L');
      printTableHeader($pdf, $w, $header); // Stampa l'intestazione per ogni gruppo di targa
      $fill = 0; // Reimposta il riempimento per la nuova targa

      // Inizializza le variabili per i totali della targa corrente
      $totale_chilometri_targa = 0;
      $totale_litri_targa = 0;
      $totale_euro_targa = 0;

      $num_righe_targa = count($righe_targa);
      $counter_righe_targa = 0;

      // Cicla sulle righe *già selezionate e raggruppate per targa*
      foreach ($righe_targa as $riga) {
            $counter_righe_targa++;
            $border = 'LR'; // Bordo predefinito
            if ($counter_righe_targa === $num_righe_targa) {
                  $border = 'LRB'; // Aggiungi il bordo inferiore per l'ultima riga
            }

            // Recupera i dati dalla riga selezionata (dal JSON decodificato)
            // *** USA LE CHIAVI IN MINUSCOLO COME INVIATE DAL JAVASCRIPT ***
            $mese = $riga['Mese']; // Questa chiave non è cambiata
            $utente = $riga['Utente']; // Questa chiave non è cambiata
            $chilometri_percorsi = $riga['km_percorsi']; // *** CORRETTO ***
            $litri_totali = $riga['litri'];         // *** CORRETTO ***
            $euro_totali = $riga['euro'];                 // *** CORRETTO ***
            $conteggio_righe = $riga['registrazioni'];       // *** CORRETTO ***
            $km_finali_mese = $riga['km_finali_Mese'];         // *** CORRETTO ***

        // Recupera la divisione per l'Utente corrente (richiede una query separata)
        // Ho mantenuto la query qui, ma passarla nel JSON sarebbe più efficiente.
        $divisione_utente = 'N/D'; // Valore predefinito
        // Usa la connessione $conn già definita e l'utente dalla riga corrente
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


            // Stampa le celle della riga (altezza aumentata a 7)
            $pdf->Cell($w[0], 7, $mese, $border, 0, 'C', $fill);
            $pdf->Cell($w[1], 7, $targa_corrente, $border, 0, 'C', $fill);
            $pdf->Cell($w[2], 7, $utente, $border, 0, 'C', $fill); // Allineamento a sinistra per l'utente
            $pdf->Cell($w[3], 7, $divisione_utente, $border, 0, 'C', $fill); // *** STAMPA DIVISIONE RECUPERATA ***
            $pdf->Cell($w[4], 7, number_format($km_finali_mese, 0, ',', '.'), $border, 0, 'C', $fill); // *** STAMPA KM FINALI MESE ***
            $pdf->Cell($w[5], 7, number_format($chilometri_percorsi, 0, ',', '.'), $border, 0, 'C', $fill); // Formattato
            $pdf->Cell($w[6], 7, number_format($litri_totali, 2, ',', '.'), $border, 0, 'C', $fill); // Formattato
            $pdf->Cell($w[7], 7, number_format($euro_totali, 2, ',', '.') . ' €', $border, 0, 'C', $fill); // Formattato
            $pdf->Cell($w[8], 7, $conteggio_righe, $border, 0, 'C', $fill);
            $pdf->Ln();
            $fill = !$fill; // Alterna il colore di sfondo

            // Aggiorna i totali generali
            $totale_chilometri_generale += $chilometri_percorsi;
            $totale_litri_generale += $litri_totali;
            $totale_euro_generale += $euro_totali;

            // Aggiorna i totali della targa corrente
            $totale_chilometri_targa += $chilometri_percorsi;
            $totale_litri_targa += $litri_totali;
            $totale_euro_targa += $euro_totali;

      } // Fine del loop sulle righe della targa
      // Stampa i totali per la targa corrente
      $pdf->SetFont('helvetica', 'B', 10);
      // La cella dell'etichetta "Totali Targa..." deve coprire le prime 4 colonne
      $label_width_targa = $w[0] + $w[1] + $w[2] + $w[3] + $w[4]; // Mese, Targa, Utente, Divisione
      $pdf->Cell($label_width_targa, 6, 'Totali Targa ' . $targa_corrente . ':', 0, 0, 'R');
      // Le celle dei totali numerici
      $pdf->Cell($w[5], 6, number_format($totale_chilometri_targa, 0, ',', '.'), 0, 0, 'C'); // Chilometri
      $pdf->Cell($w[6], 6, number_format($totale_litri_targa, 2, ',', '.'), 0, 0, 'C'); // Litri
      $pdf->Cell($w[7], 6, number_format($totale_euro_targa, 2, ',', '.') . ' €', 0, 0, 'C'); // Euro
      // Celle vuote per Registrazioni e Km Finali
      $pdf->Cell($w[7], 6, '', 0, 0, 'C'); // Registrazioni
      $pdf->Cell($w[8], 6, '', 0, 1, 'C'); // *** CELLA VUOTA PER KM FINALI NEI TOTALI DI TARGA ***
      $pdf->Ln(1);
} // Fine del loop sui gruppi di targa

// Stampa i totali generali
$pdf->SetFont('helvetica', 'B', 10);
// La cella dell'etichetta "Totali Generali:" deve coprire le prime 4 colonne
$label_width_generali = $w[0] + $w[1] + $w[2] + $w[3] + $w[4]; // Mese, Targa, Utente, Divisione, KmFinali
$pdf->Cell($label_width_generali, 6, 'Totali Generali:', 0, 0, 'R');
// Le celle dei totali numerici
$pdf->Cell($w[5], 6, number_format($totale_chilometri_generale, 0, ',', '.'), 0, 0, 'C'); // Chilometri
      $pdf->Cell($w[6], 6, number_format($totale_litri_generale, 2, ',', '.'), 0, 0, 'C'); // Litri
      $pdf->Cell($w[7], 6, number_format($totale_euro_generale, 2, ',', '.') . ' €', 0, 0, 'C'); // Euro
// Celle vuote per Registrazioni e Km Finali
$pdf->Cell($w[7], 6, '', 0, 0, 'C'); // Registrazioni
$pdf->Cell($w[8], 6, '', 0, 1, 'C'); // *** CELLA VUOTA PER KM FINALI NEI TOTALI GENERALI ***
$pdf->Ln(5);

// Assicurati che nessun output sia stato inviato prima di questa riga!
$pdf->Output('dati_mensili.pdf', 'D');

// Chiudi la connessione al database se aperta
if (isset($conn) && $conn instanceof mysqli) {
      $conn->close();
}
?>