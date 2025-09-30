<?php
// Inizio di config.php

// Abilita la visualizzazione degli errori e il logging per il debug
// In produzione, potresti voler impostare display_errors a 0 e fare affidamento solo su error_log
error_reporting(E_ALL);
ini_set('display_errors', 1); // Imposta a 0 in produzione

$settingsFile = __DIR__ . '/editable_config.php'; // Percorso al file con le credenziali
$appSettings = [];

// Controlla se il file editable_config.php esiste e leggilo
if (file_exists($settingsFile)) {
    $appSettings = include $settingsFile; // include restituisce il valore di return del file
    // Verifica che $appSettings sia effettivamente un array
    if (!is_array($appSettings)) {
        error_log("CRITICO: Il file di configurazione '$settingsFile' non ha restituito un array.");
        die("Errore critico nella configurazione del sito (codice: cfg_array_fail). Si prega di contattare l'amministratore.");
    }
} else {
    error_log("CRITICO: File di configurazione delle impostazioni '$settingsFile' mancante.");
    die("Errore critico nella configurazione del sito (codice: cfg_missing). Si prega di contattare l'amministratore.");
}

// Definisci le costanti per la connessione al database
// Usa l'operatore null coalescing (??) per fornire valori di fallback se le chiavi non sono presenti in $appSettings
define('DB_HOST', $appSettings['DB_HOST'] ?? 'localhost');
define('DB_USERNAME', $appSettings['DB_USERNAME'] ?? 'dome');
define('DB_PASSWORD', $appSettings['DB_PASSWORD'] ?? 'a080502'); // Fallback a password vuota
define('DB_NAME', $appSettings['DB_NAME'] ?? 'chilometri');   // Fallback a database 'test'

// Qui ISTANZIA la connessione al database usando le costanti definite
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Controlla se la connessione è fallita
if ($conn->connect_error) {
    // Se c'è un errore di connessione, registra l'errore specifico e termina lo script
    $specific_error_message = "Host: " . DB_HOST . ", Utente: " . DB_USERNAME . ", DB: " . DB_NAME . ". Errore MySQL [" . $conn->connect_errno . "]: " . htmlspecialchars($conn->connect_error);
    error_log("FALLIMENTO CONNESSIONE DB: " . $specific_error_message);

    // Mostra un messaggio di errore all'utente (più dettagliato per il debug, generico per la produzione)
    // Per il debug:
    die("Impossibile connettersi al database. <br><strong>Dettagli Errore:</strong> " . $specific_error_message . "<br>Verifica le credenziali in 'editable_config.php', che il server DB sia attivo e che il database esista.");
    // Per la produzione, potresti usare un messaggio più generico:
    // die("Impossibile connettersi al database. Si prega di riprovare più tardi o contattare l'amministratore (codice: db_conn_fail).");
}
// Se lo script arriva a questo punto, la connessione al database è RIUSCITA.

// Imposta il set di caratteri della connessione a utf8mb4 (altamente raccomandato)
if (!$conn->set_charset("utf8mb4")) {
    // Se l'impostazione del charset fallisce, registra un avviso ma non terminare necessariamente lo script
    error_log("AVVISO: Impossibile impostare il set di caratteri della connessione a utf8mb4. Errore MySQL: " . htmlspecialchars($conn->error));
    // Potresti voler mostrare un avviso discreto durante il debug:
    // echo "<p style='color:orange; font-weight:bold;'>Attenzione: Non è stato possibile impostare il charset utf8mb4 per la connessione al database.</p>";
}

// (Opzionale) Messaggio di debug per confermare che config.php è stato eseguito con successo
// Da commentare o rimuovere in produzione
// echo "<p style='color:green; font-weight:bold;'>DEBUG [config.php]: Connessione al database stabilita con successo e charset impostato.</p>";

// La variabile $conn è ora disponibile per gli script che includono questo file.
// Non chiudere la connessione qui ($conn->close();) se deve essere usata da altri script.
