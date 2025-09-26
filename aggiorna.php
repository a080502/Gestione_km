<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $data = $_POST['data'];
    $chilometri_iniziali = $_POST['chilometri_iniziali'];
    $chilometri_finali = $_POST['chilometri_finali'];
    $litri_carburante = $_POST['litri_carburante'];
    $euro_spesi = $_POST['euro_spesi'];
    $note = $_POST['note'];

    // Recupera il percorso del cedolino attuale dal database
    $sql_select_old_cedolino = "SELECT percorso_cedolino FROM chilometri WHERE id = ?";
    $stmt_select_old_cedolino = $conn->prepare($sql_select_old_cedolino);
    $stmt_select_old_cedolino->bind_param("i", $id);
    $stmt_select_old_cedolino->execute();
    $result_select_old_cedolino = $stmt_select_old_cedolino->get_result();
    $old_cedolino_path = null;
    if ($result_select_old_cedolino->num_rows > 0) {
        $row_old_cedolino = $result_select_old_cedolino->fetch_assoc();
        $old_cedolino_path = $row_old_cedolino['percorso_cedolino'];
    }
    $stmt_select_old_cedolino->close();

    $nuovo_percorso_cedolino = $old_cedolino_path; // Inizializza con il percorso esistente

    // Gestione rimozione cedolino
    if (isset($_POST['rimuovi_cedolino']) && $_POST['rimuovi_cedolino'] == 1 && !empty($old_cedolino_path) && file_exists($old_cedolino_path)) {
        if (unlink($old_cedolino_path)) {
            $nuovo_percorso_cedolino = null; // Imposta a null se rimosso
        } else {
            // Gestisci l'errore di eliminazione del file
            $errore = "Errore durante l'eliminazione del vecchio cedolino.";
            header("Location: modifica.php?id=" . $id . "&errore=" . urlencode($errore));
            exit();
        }
    }

    // Gestione upload nuovo cedolino
    if (isset($_FILES['cedolino']) && $_FILES['cedolino']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['cedolino']['tmp_name'];
        $file_name = basename($_FILES['cedolino']['name']);
        $upload_dir = 'uploads/cedolini/'; // **DA MODIFICARE CON IL PERCORSO REALE**
        $nuovo_percorso_cedolino = $upload_dir . $file_name;

        // **DA IMPLEMENTARE: VALIDAZIONE DEL TIPO DI FILE, DIMENSIONE, CONTROLLI DI SICUREZZA**

        if (move_uploaded_file($file_tmp, $nuovo_percorso_cedolino)) {
            // Se il caricamento ha successo ed esisteva un vecchio cedolino, eliminalo
            if (!empty($old_cedolino_path) && $old_cedolino_path !== $nuovo_percorso_cedolino && file_exists($old_cedolino_path)) {
                unlink($old_cedolino_path);
            }
        } else {
            // Gestisci l'errore di upload
            $errore = "Errore durante il caricamento del nuovo cedolino.";
            header("Location: modifica.php?id=" . $id . "&errore=" . urlencode($errore));
            exit();
        }
    }

    // Aggiorna il record nel database
    $sql_aggiorna = "UPDATE chilometri SET data=?, chilometri_iniziali=?, chilometri_finali=?, litri_carburante=?, euro_spesi=?, note=?, percorso_cedolino=? WHERE id=?";
    $stmt_aggiorna = $conn->prepare($sql_aggiorna);
    $stmt_aggiorna->bind_param("sddddssi", $data, $chilometri_iniziali, $chilometri_finali, $litri_carburante, $euro_spesi, $note, $nuovo_percorso_cedolino, $id);

    if ($stmt_aggiorna->execute()) {
        header("Location: visualizza.php?messaggio=" . urlencode("Dati aggiornati con successo."));
        exit();
    } else {
        $errore = "Errore durante l'aggiornamento dei dati: " . $stmt_aggiorna->error;
        header("Location: modifica.php?id=" . $id . "&errore=" . urlencode($errore));
        exit();
    }

    $stmt_aggiorna->close();

} else {
    header("Location: visualizza.php?errore=" . urlencode("Metodo non valido."));
    exit();
}

$conn->close();
?>