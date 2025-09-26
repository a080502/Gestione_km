<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include_once 'config.php';

$username = $_SESSION['username'];

// Recupera i dati dell'utente per visualizzare il nome
$sql_utente = $conn->prepare("SELECT Nome, Cognome FROM utenti WHERE username = ?");
$sql_utente->bind_param("s", $username);
$sql_utente->execute();
$result_utente = $sql_utente->get_result();
$dati_utente = $result_utente->fetch_assoc();
$sql_utente->close();

?>