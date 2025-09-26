<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$rows_json = $_GET['rows'];
$rows = json_decode($rows_json, true);

if (empty($rows)) {
    echo "Nessuna riga selezionata.";
    exit;
}

include 'config.php'; // Includi il file di configurazione del database

// Costruisci il messaggio email
$message = "Dati mensili selezionati dall'utente " . $username . ":\n\n";
$message .= "Mese\tChilometri\tLitri\tEuro\tRegistrazioni\n";

foreach ($rows as $mese) {
    $sql = $conn->prepare("SELECT DATE_FORMAT(data, '%Y-%m') AS mese, SUM(chilometri_finali - chilometri_iniziali) AS chilometri_percorsi, SUM(litri_carburante) AS litri_totali, SUM(euro_spesi) AS euro_totali, COUNT(*) AS conteggio_righe FROM chilometri WHERE DATE_FORMAT(data, '%Y-%m') = ? AND username = ?");
    $sql->bind_param("ss", $mese, $username);
    $sql->execute();
    $result = $sql->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $message .= $row['mese'] . "\t" . $row['chilometri_percorsi'] . "\t" . number_format($row['litri_totali'], 2) . "\t" . number_format($row['euro_totali'], 2) . "\t" . $row['conteggio_righe'] . "\n";
    }
}

// Imposta l'intestazione dell'email
$to = "indirizzo_email_destinatario@example.com"; // Sostituisci con l'indirizzo email del destinatario
$subject = "Dati mensili selezionati";
$headers = "From: " . $username . "@example.com"; //Sostituisci con il tuo dominio

// Invia l'email
if (mail($to, $subject, $message, $headers)) {
    echo "Email inviata con successo.";
} else {
    echo "Errore durante l'invio dell'email.";
}

$conn->close();
?>