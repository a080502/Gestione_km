<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $divisione_originale = $_POST['divisione']; // Usiamo la divisione originale per la WHERE clause
    $nuova_divisione = $_POST['divisione']; // Potresti permettere di modificare la divisione, ma attenzione alle implicazioni (chiave primaria?)
    $filiale = $_POST["filiale"];

    // Prepara la query SQL per l'aggiornamento
    $sql = $conn->prepare("UPDATE filiali SET divisione = ?, filiale = ? WHERE divisione = ?");
    $sql->bind_param("sss", $nuova_divisione, $filiale, $divisione_originale);

    if ($sql->execute()) {
        $_SESSION['success_message'] = "Filiale aggiornata con successo.";
    } else {
        $_SESSION['error_message'] = "Errore durante l'aggiornamento della filiale: " . $sql->error;
    }

    $sql->close();
} else {
    $_SESSION['error_message'] = "Metodo non valido per l'aggiornamento della filiale.";
}

$conn->close();
header("Location: gestisci_filiali.php");
exit();
?><?php
