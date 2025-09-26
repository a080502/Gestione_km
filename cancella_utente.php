<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include_once 'config.php';

// Verifica se l'ID dell'utente è stato passato tramite GET
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_id = $_GET['id'];

    // Prepara la query SQL per la cancellazione
    $sql = $conn->prepare("DELETE FROM utenti WHERE id = ?");

    // Associa il parametro alla query preparata
    $sql->bind_param("i", $user_id);

    // Esegue la query
    if ($sql->execute()) {
        $_SESSION['success_message'] = "Utente cancellato con successo.";
    } else {
        $_SESSION['error_message'] = "Errore durante la cancellazione dell'utente: " . $sql->error;
    }

    // Chiudi lo statement
    $sql->close();
} else {
    // ID utente non valido
    $_SESSION['error_message'] = "ID utente non valido per la cancellazione.";
}

// Chiudi la connessione al database
$conn->close();

// Reindirizza alla pagina di gestione utenti
header("Location: gestisci_utenti.php");
exit();
?><?php
