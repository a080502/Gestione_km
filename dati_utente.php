<?php
// Assicurati che la sessione sia avviata
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once 'config.php'; // Includi il file di configurazione del database

// Verifica che l'utente sia loggato
if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    $dati_utente = null;
    return;
}

$username = $_SESSION['username'];

// Query per recuperare tutti i dati dell'utente dalla tabella utenti
$sql = $conn->prepare("SELECT id, username, targa_mezzo, filiale, divisione, livello, Nome, Cognome FROM utenti WHERE username = ?");
$sql->bind_param("s", $username);
$sql->execute();
$result = $sql->get_result();

if ($result->num_rows > 0) {
    $dati_utente = $result->fetch_assoc();
    // Rendi i dati dell'utente disponibili come variabili di sessione
    $_SESSION['dati_utente'] = $dati_utente;
} else {
    // Gestisci il caso in cui l'utente non viene trovato
    $dati_utente = null;
    $_SESSION['dati_utente'] = null;
}

?>