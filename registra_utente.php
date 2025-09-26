<?php
// registra_utente.php
// Includi la configurazione del database
include_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $cognome = $_POST['cognome'];
    $targa_mezzo = $_POST['targa_mezzo'];
    $divisione = $_POST['divisione'];
    $filiale = $_POST['filiale'];
    $livello = $_POST['livello'];
    $username = strtolower($_POST['username']); // Converte sempre in minuscolo
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Assicurati di usare password_hash
    
    // Inserimento nella tabella utenti (come già presente nel tuo script)
    $sql_utenti = "INSERT INTO utenti (nome, cognome, targa_mezzo, divisione, filiale, livello, username, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_utenti = $conn->prepare($sql_utenti);
    $stmt_utenti->bind_param("ssssssss", $nome, $cognome, $targa_mezzo, $divisione, $filiale, $livello, $username, $password);
    
    if ($stmt_utenti->execute()) {
        // Imposta un messaggio di successo nella sessione
        $_SESSION['registrazione_successo'] = "Utente registrato con successo!";
        
        // Inserimento nella tabella costo_extra
        $costo = $_POST['costo'];
        if (!empty($costo)) { // Inserisci solo se il costo è stato fornito
            $sql_costo_extra = "INSERT INTO costo_extra (targa_mezzo, costo) VALUES (?, ?)";
            $stmt_costo_extra = $conn->prepare($sql_costo_extra);
            $stmt_costo_extra->bind_param("sd", $targa_mezzo, $costo); // 'd' per double/decimal
            
            if ($stmt_costo_extra->execute()) {
                // Inserimento costo extra avvenuto con successo
                header("Location: gestisci_utenti.php"); // Redirect a una pagina di successo
                exit();
            } else {
                echo "Errore durante l'inserimento del costo extra: " . $stmt_costo_extra->error;
            }
            $stmt_costo_extra->close();
        } else {
            header("Location: gestisci_utenti.php"); // Redirect anche se non c'è costo
            exit();
        }
    } else {
        echo "Errore durante la registrazione dell'utente: " . $stmt_utenti->error;
    }
    $stmt_utenti->close();
}
$conn->close();
?>