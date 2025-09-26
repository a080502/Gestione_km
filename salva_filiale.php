<?php
// File: salva_filiale.php
include 'dati_utente.php'; // Gestisce la sessione e l'eventuale redirect al login
include_once 'config.php'; // Includi configurazione DB

// Verifica che l'utente sia loggato (ridondante se fatto in dati_utente.php, ma sicurezza extra)
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Includi il file di query specifico per la tabella utenti
include 'query/qutenti.php';

// *** Recupera i dati dell'utente LOGGATO in modo sicuro (Standardizzato) ***
$username_loggato = $_SESSION['username'];
$utente_loggato_data = []; // Inizializza l'array
$livello_utente_loggato = 99; // Default a un livello alto per sicurezza

// Usiamo un prepared statement per recuperare i dati dell'utente loggato
$sql_user = $conn->prepare("SELECT Nome, Cognome, targa_mezzo, divisione, filiale, livello FROM utenti WHERE username = ? LIMIT 1");
if ($sql_user) {
    $sql_user->bind_param("s", $username_loggato);
    $sql_user->execute();
    $result_user = $sql_user->get_result();
    if ($result_user->num_rows > 0) {
        $utente_loggato_data = $result_user->fetch_assoc();
        $utente_loggato_data['username'] = $username_loggato;
        $livello_utente_loggato = $utente_loggato_data['livello'];
    } else {
        error_log("Utente loggato '$username_loggato' non trovato nel database utenti durante salvataggio filiale.");
        session_destroy();
        header("Location: login.php");
        exit();
    }
    $sql_user->close();
} else {
    error_log("Errore preparazione query dati utente in salva_filiale.php: " . $conn->error);
    die("Errore critico nel recupero dati utente.");
}

// --- CONTROLLO PERMESSI SPECIFICO (solo livello 1) ---
if ($livello_utente_loggato != 1) {
    header("Location: unauthorized.php");
    exit();
}

// Verifica che i dati siano stati inviati tramite POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recupera e pulisce i dati dal form
    $divisione = strtoupper(trim($_POST['divisione']));
    $nome_divisione = strtoupper(trim($_POST['nome_divisione']));
    
    // Validazione base
    if (empty($divisione) || empty($nome_divisione)) {
        $_SESSION['error_message'] = "Tutti i campi sono obbligatori.";
        header("Location: inserisci_filiale.php");
        exit();
    }
    
    try {
        // Verifica se la divisione esiste già
        $check_query = "SELECT COUNT(*) as count FROM filiali WHERE divisione = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $divisione);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $_SESSION['error_message'] = "Divisione già esistente. Scegliere un codice diverso.";
            header("Location: inserisci_filiale.php");
            exit();
        }
        
        // Inserisce la nuova filiale (adattato alla struttura della tabella esistente)
        $insert_query = "INSERT INTO filiali (divisione, filiale) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("ss", $divisione, $nome_divisione);
        
        if ($insert_stmt->execute()) {
            // Successo - imposta il messaggio di conferma e reindirizza
            $_SESSION['success_message'] = "Filiale '$divisione - $nome_divisione' inserita correttamente!";
            $insert_stmt->close();
            $conn->close();
            header("Location: gestisci_filiali.php"); // Corretto il nome del file
            exit();
        } else {
            throw new Exception("Errore durante l'inserimento della filiale.");
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Errore: " . $e->getMessage();
        header("Location: inserisci_filiale.php");
        exit();
    }
    
} else {
    // Se non è una richiesta POST, reindirizza alla pagina di inserimento
    header("Location: inserisci_filiale.php");
    exit();
}
?>