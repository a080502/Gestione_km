<?php

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include_once 'config.php';
include 'query/qutenti.php'; // Assicurati che il percorso sia corretto

$username = $_SESSION['username'];
$utente_data = get_user_data($conn, $username);
$livello = $utente_data['livello'];
$divisione = $utente_data['divisione']; // Recupero la divisione

// Funzione per recuperare le targhe in base al livello utente
function get_user_targhe($conn, $username, $livello, $divisione) {
    if ($livello == 1) {
        // Admin vede tutti i mezzi, eccetto il suo
        $sql = "SELECT DISTINCT targa_mezzo FROM utenti WHERE username != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
    } elseif ($livello == 2) {
        // Utente vede solo i mezzi della sua divisione, eccetto il suo
        $sql = "SELECT DISTINCT targa_mezzo FROM utenti WHERE divisione = ? AND username != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $divisione, $username);
    } elseif ($livello == 3) {
        // Responsabile vede solo il suo mezzo
        $sql = "SELECT DISTINCT targa_mezzo FROM utenti WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
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

$targhe_mezzo = get_user_targhe($conn, $username, $livello, $divisione);
?>