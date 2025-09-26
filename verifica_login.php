<?php
session_start();

// Includi il file di configurazione del database
// Assicurati che config.php esista, funzioni correttamente
// e che la variabile $conn (oggetto della connessione) sia disponibile.
include_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST['username']) ? trim($_POST['username']) : ''; // trim() rimuove spazi bianchi
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Controllo preliminare se i campi sono vuoti
    if (empty($username) || empty($password)) {
        // Reindirizza se username o password sono vuoti, anche se HTML li marca come 'required'
        header("Location: login.php?error=1&username=" . urlencode($username));
        exit();
    }

    // Verifica che $conn sia un oggetto di connessione valido
    // Questo controllo dipende da come $conn è inizializzato in config.php
    // Se config.php muore (die/exit) in caso di errore di connessione, questo potrebbe non essere necessario,
    // ma è una buona pratica difensiva.
    if (!$conn || $conn->connect_error) {
        error_log("Errore di connessione al database in verifica_login.php. Controlla config.php e lo stato del DB.");
        // Non fornire dettagli specifici dell'errore DB all'utente per sicurezza
        header("Location: login.php?error=1&message=db_conn_failed&username=" . urlencode($username)); // Puoi usare un codice errore diverso se vuoi
        exit();
    }

    // Esegui una query per recuperare l'utente dalla tabella degli utenti
    // Utilizziamo SELECT * come nella tua versione funzionante.
    // Considera di specificare le colonne per maggiore chiarezza e performance in futuro.
    $sql = "SELECT * FROM utenti WHERE username = ?";
    
    $stmt = $conn->prepare($sql);

    // Controlla se la preparazione dello statement è fallita
    if ($stmt === false) {
        error_log("Errore nella preparazione dello statement SQL in verifica_login.php: " . $conn->error);
        // Non fornire dettagli specifici dell'errore SQL all'utente
        header("Location: login.php?error=1&message=stmt_failed&username=" . urlencode($username)); // Puoi usare un codice errore diverso
        $conn->close(); // Chiudi la connessione se $stmt non è valido
        exit();
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row_utente = $result->fetch_assoc()) {
        // Utente trovato, verifica la password
        if (password_verify($password, $row_utente['password'])) {
            // Password corretta, login riuscito
            $_SESSION['username'] = $row_utente['username']; // È meglio usare il valore dal DB

            // Controlla se la colonna 'livello_autorizzazione' esiste ed è impostata
            if (isset($row_utente['livello_autorizzazione'])) {
                $_SESSION['livello_autorizzazione'] = $row_utente['livello_autorizzazione'];
            } else {
                // La colonna non esiste o è NULL. Logga un avviso.
                error_log("Attenzione: La colonna 'livello_autorizzazione' non è stata trovata o è NULL per l'utente '" . $row_utente['username'] . "' nel file verifica_login.php.");
                // Puoi decidere se impostare un valore di default o lasciare la variabile di sessione non impostata
                // Esempio: $_SESSION['livello_autorizzazione'] = 'utente_standard';
            }
            
            // Se hai un campo 'id' nella tabella utenti, è utile salvarlo in sessione
            if (isset($row_utente['id'])) {
                $_SESSION['user_id'] = $row_utente['id'];
            }

            header("Location: index.php"); // Reindirizza alla pagina principale dopo il login
            $stmt->close();
            $conn->close();
            exit();
        } else {
            // Password errata
            $stmt->close();
            $conn->close();
            header("Location: login.php?error=1&username=" . urlencode($username));
            exit();
        }
    } else {
        // Utente non trovato
        $stmt->close();
        $conn->close();
        header("Location: login.php?error=1&username=" . urlencode($username));
        exit();
    }
    // Le righe $stmt->close(); e $conn->close(); qui sotto non verrebbero comunque raggiunte
    // a causa degli exit() nei blocchi precedenti. Le ho spostate all'interno di ogni blocco.
} else {
    // Se la richiesta non è POST, reindirizza semplicemente a login.php
    header("Location: login.php");
    exit();
}
?>
