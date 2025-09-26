<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupera i dati dal form
    $user_id = $_POST['id'];
    $nome = $_POST['nome'];
    $cognome = $_POST['cognome'];
    $targa_mezzo = $_POST['targa_mezzo'];
    $divisione   = $_POST['divisione'];
    $filiale = $_POST['filiale'];
    $livello = $_POST['livello'];
    $username = $_POST['username'];
    $costo = $_POST['costo'];

    // Prepara la query SQL per l'aggiornamento della tabella utenti
    $sql_utenti = $conn->prepare("UPDATE utenti SET nome = ?, cognome = ?, targa_mezzo = ?, divisione = ?, filiale = ?, livello = ?, username = ? WHERE id = ?");

    // Associa i parametri alla query preparata per la tabella utenti
    $sql_utenti->bind_param("sssssssi", $nome, $cognome, $targa_mezzo, $divisione, $filiale, $livello, $username, $user_id);

    // Esegue la query per la tabella utenti
    if ($sql_utenti->execute()) {
        $_SESSION['success_message'] = "Utente aggiornato con successo.";

        // INIZIO INTEGRAZIONE PER L'AGGIORNAMENTO DI costo_extra
        // Verifica se esiste già un record per questa targa in 'costo_extra'
        $sql_check_costo = $conn->prepare("SELECT COUNT(*) FROM costo_extra WHERE targa_mezzo = ?");
        $sql_check_costo->bind_param("s", $targa_mezzo);
        $sql_check_costo->execute();
        $result_check_costo = $sql_check_costo->get_result();
        $row_check_costo = $result_check_costo->fetch_row();
        $count = $row_check_costo[0];
        $sql_check_costo->close();

        if ($count > 0) {
            // Se il record esiste, aggiorna il costo
            $sql_costo_extra = $conn->prepare("UPDATE costo_extra SET costo=? WHERE targa_mezzo=?");
            $sql_costo_extra->bind_param("ds", $costo, $targa_mezzo);
        } else {
            // Se il record non esiste, inseriscine uno nuovo
            $sql_costo_extra = $conn->prepare("INSERT INTO costo_extra (targa_mezzo, costo) VALUES (?, ?)");
            $sql_costo_extra->bind_param("sd", $targa_mezzo, $costo);
        }

        if ($sql_costo_extra->execute()) {
            // L'aggiornamento o l'inserimento in costo_extra è avvenuto con successo (opzionale: puoi aggiungere un messaggio specifico)
        } else {
            $_SESSION['error_message'] .= " Errore durante l'aggiornamento del costo extra: " . $sql_costo_extra->error;
        }
        $sql_costo_extra->close();
        // FINE INTEGRAZIONE PER L'AGGIORNAMENTO DI costo_extra

    } else {
        $_SESSION['error_message'] = "Errore durante l'aggiornamento dell'utente: " . $sql_utenti->error;
    }

    // Chiudi lo statement per la tabella utenti
    $sql_utenti->close();
} else {
    $_SESSION['error_message'] = "Metodo non valido per l'aggiornamento.";
}

// Chiudi la connessione al database
$conn->close();

// Reindirizza alla pagina di gestione utenti
header("Location: gestisci_utenti.php");
exit();
?>