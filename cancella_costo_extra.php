<?php
// File: cancella_costo_extra.php

// --- Inizio Blocco Sicurezza e Dati ---
include_once 'config.php'; // Includi configurazione DB
// dati_utente.php gestisce sessione e recupera dati in $_SESSION['dati_utente']
include 'dati_utente.php';

// --- NUOVO CONTROLLO E DEFINIZIONE VARIABILI ---
// Verifica che l'utente sia loggato E che i dati utente siano stati caricati correttamente nella sessione
if (!isset($_SESSION['username']) || !isset($_SESSION['dati_utente']) || $_SESSION['dati_utente'] === null) {
    // Se manca username in sessione O i dati utente non sono stati caricati in sessione,
    // significa che l'utente non è considerato loggato o i suoi dati non sono disponibili.
    header("Location: login.php"); // Reindirizza alla pagina di login
    exit();
}

// Definisci le variabili locali $dati_utente, $livello_utente e $targa_utente
// prendendole dall'array che dati_utente.php ha salvato nella sessione
$dati_utente = $_SESSION['dati_utente'];
$livello_utente = $dati_utente['livello']; // Ora $livello_utente è definito!
$targa_utente = isset($dati_utente['targa_mezzo']) ? $dati_utente['targa_mezzo'] : null; // Assicurati che targa_mezzo esista

// --- Fine NUOVO CONTROLLO ---


// Includi il file di query con la funzione delete_costo_extra
include 'query/q_costo_extra.php';

// --- Fine Blocco Sicurezza e Dati ---

// --- Inizio Logica di Cancellazione ---

// Verifica se l'ID è stato passato tramite GET
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: gestione_costo_extra.php?errore=ID costo extra non specificato per la cancellazione.");
    exit();
}

// Sanifica e valida l'ID
$id_costo = filter_var($_GET['id'], FILTER_VALIDATE_INT);

if ($id_costo === false || $id_costo <= 0) {
    header("Location: gestione_costo_extra.php?errore=ID costo extra non valido per la cancellazione.");
    exit();
}

// Controllo Permessi di Cancellazione: solo utenti con livello < 3
if ($livello_utente >= 3) {
    header("Location: gestione_costo_extra.php?errore=Permesso negato. Non hai i privilegi per cancellare costi extra.");
    exit();
}

// Se l'utente ha i permessi, procedi con la cancellazione
if (delete_costo_extra($conn, $id_costo)) {
    // Cancellazione riuscita
    header("Location: gestione_costo_extra.php?messaggio=Costo extra ID $id_costo cancellato con successo.");
    exit();
} else {
    // Errore nella cancellazione (es. ID non trovato, errore DB)
    // La funzione delete_costo_extra logga già l'errore DB
    header("Location: gestione_costo_extra.php?errore=Impossibile cancellare il costo extra ID $id_costo. Potrebbe non esistere o esserci un errore nel database.");
    exit();
}

// --- Fine Logica di Cancellazione ---

// Chiudi la connessione al database se aperta
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>