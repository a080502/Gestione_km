<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include_once 'config.php';

// Verifica se la divisione della filiale è stata passata tramite GET
if (isset($_GET['divisione'])) {
    $divisione_da_cancellare = $_GET['divisione'];

    // Prepara la query SQL per la cancellazione
    $sql = $conn->prepare("DELETE FROM filiali WHERE divisione = ?");
    $sql->bind_param("s", $divisione_da_cancellare);

    if ($sql->execute()) {
        $_SESSION['success_message'] = "Filiale cancellata con successo.";
    } else {
        $_SESSION['error_message'] = "Errore durante la cancellazione della filiale: " . $sql->error;
    }

    $sql->close();
} else {
    $_SESSION['error_message'] = "Divisione filiale non valida per la cancellazione.";
}

$conn->close();
header("Location: gestisci_filiali.php");
exit();
?>  <?php
