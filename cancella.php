<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include_once 'config.php';

$id = $_GET['id'];

// Recupera il percorso del file del cedolino prima di eliminare il record
$sql_select_file = "SELECT percorso_cedolino FROM chilometri WHERE id = $id";
$result_select = $conn->query($sql_select_file);

if ($result_select->num_rows > 0) {
    $row = $result_select->fetch_assoc();
    $percorso_cedolino = $row['percorso_cedolino'];

    // Elimina il file se il percorso non è vuoto e il file esiste
    if (!empty($percorso_cedolino) && file_exists($percorso_cedolino)) {
        if (unlink($percorso_cedolino)) {
            $file_eliminato = true;
        } else {
            $file_eliminato = false;
            $errore_file = "Errore durante l'eliminazione del file: " . $percorso_cedolino;
        }
    } else {
        $file_eliminato = true; // Considera successo se non c'è file o il percorso è vuoto
    }

    // Elimina il record dal database
    $sql_delete = "DELETE FROM chilometri WHERE id = $id";
    if ($conn->query($sql_delete) === TRUE) {
        $messaggio = "Record eliminato con successo.";
        if (isset($file_eliminato) && $file_eliminato && !empty($percorso_cedolino)) {
            $messaggio .= " Anche il file del cedolino è stato eliminato.";
        } elseif (isset($file_eliminato) && !$file_eliminato && isset($errore_file)) {
            $messaggio .= " Attenzione: " . $errore_file . " (Il record nel database è stato comunque eliminato).";
        }
        header("Location: visualizza.php?messaggio=" . urlencode($messaggio)); // Reindirizza alla visualizzazione con un messaggio
    } else {
        $errore_db = "Errore durante l'eliminazione del record: " . $sql_delete . "<br>" . $conn->error;
        // Se l'eliminazione del database fallisce, potresti voler considerare di non eliminare il file o di segnalare ulteriormente l'errore.
        header("Location: visualizza.php?errore=" . urlencode($errore_db));
    }

} else {
    header("Location: visualizza.php?errore=" . urlencode("Record non trovato."));
}

$conn->close();
?>