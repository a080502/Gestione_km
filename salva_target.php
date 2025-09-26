<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include_once 'config.php';

$username = $_SESSION['username'];
$anno = $_POST['anno'];
$target_chilometri = $_POST['target_chilometri'];
$targa_mezzo = $_POST['targa_mezzo'];
$divisione = $_POST['divisione'];
$filiale = $_POST['filiale'];

$sql_check = "SELECT * FROM target_annuale WHERE anno = $anno AND username = '$username' AND targa_mezzo = '$targa_mezzo'";
$result_check = $conn->query($sql_check);

if ($result_check->num_rows > 0) {
    $sql_update = "UPDATE target_annuale SET target_chilometri = $target_chilometri, divisione = '$divisione', filiale = '$filiale' WHERE anno = $anno AND username = '$username' AND targa_mezzo = '$targa_mezzo'";
    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Target aggiornato con successo!'); window.location.href = 'imposta_target.php';</script>";
    } else {
        echo "<script>alert('Errore durante l\'aggiornamento del target: " . $conn->error . "'); window.location.href = 'imposta_target.php';</script>";
    }
} else {
    $sql_insert = "INSERT INTO target_annuale (anno, target_chilometri, username, targa_mezzo, divisione, filiale) VALUES ($anno, $target_chilometri, '$username', '$targa_mezzo', '$divisione', '$filiale')";
    if ($conn->query($sql_insert) === TRUE) {
        echo "<script>alert('Target inserito con successo!'); window.location.href = 'imposta_target.php';</script>";
    } else {
        echo "<script>alert('Errore durante l\'inserimento del target: " . $conn->error . "'); window.location.href = 'imposta_target.php';</script>";
    }
}

$conn->close();
?>