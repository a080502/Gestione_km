<?php
session_start();
include_once 'config.php'; // Assicurati che il percorso sia corretto

// Verifica login (se necessario anche qui)
if (!isset($_SESSION['username'])) {
    // Gestisci utente non loggato, es: redirect
    header("Location: login.php");
    exit();
}

// Verifica che la richiesta sia POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recupera i dati dal form (usa htmlspecialchars per sicurezza)
    $username = $_POST['username'] ?? ''; // Usa l'operatore null coalescing per sicurezza
    $targa_mezzo = $_POST['targa_mezzo'] ?? '';
    $divisione = $_POST['divisione'] ?? '';
    $filiale = $_POST['filiale'] ?? '';
    $livello = $_POST['livello'] ?? ''; // Non so se ti serve salvarlo di nuovo
    $data = $_POST['data'] ?? '';
    $chilometri_iniziali = $_POST['chilometri_iniziali'] ?? 0;
    $chilometri_finali = $_POST['chilometri_finali'] ?? 0;
    $litri_carburante = $_POST['litri_carburante'] ?? 0;
    $euro_spesi = $_POST['euro_spesi'] ?? 0;

    // Variabile per memorizzare il percorso del file nel DB
    $percorso_cedolino_db = null; // Inizializza a null

    // --- Gestione Upload File ---
    // Verifica se è stato caricato un file e non ci sono errori
    if (isset($_FILES['cedolino']) && $_FILES['cedolino']['error'] === UPLOAD_ERR_OK) {

        $file_tmp_path = $_FILES['cedolino']['tmp_name']; // Percorso temporaneo del file
        $file_name = $_FILES['cedolino']['name'];       // Nome originale del file
        $file_size = $_FILES['cedolino']['size'];       // Dimensione in byte
        $file_type = $_FILES['cedolino']['type'];       // MIME type (es. image/jpeg)

        // Definisci la cartella di destinazione per gli upload
        // ASSICURATI CHE QUESTA CARTELLA ESISTA E SIA SCRIVIBILE DAL SERVER WEB (es. permessi 755 o 775)
        $upload_dir = 'uploads/cedolini/'; // Crea questa cartella nel tuo progetto
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true); // Prova a crearla se non esiste
        }


        // --- Validazione (Opzionale ma consigliata) ---
        // 1. Estensione del file
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf']; // Aggiungi PDF se serve
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_extensions)) {
            die("Errore: Tipo di file non permesso. Sono ammessi solo: " . implode(', ', $allowed_extensions));
            // In un'app reale, mostra un messaggio più user-friendly e non usare die()
        }

        // 2. Dimensione massima (es. 5MB)
        $max_file_size = 5 * 1024 * 1024; // 5 MB in byte
        if ($file_size > $max_file_size) {
            die("Errore: Il file è troppo grande. La dimensione massima è 5MB.");
            // Gestione errore user-friendly
        }

        // 3. Verifica MIME Type (più sicuro dell'estensione)
        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        if (!in_array($file_type, $allowed_mime_types)) {
            die("Errore: Contenuto del file non valido.");
            // Gestione errore user-friendly
        }
        // --- Fine Validazione ---


        // Crea un nome univoco per il file per evitare sovrascritture
        // Es: cedolino_utente_timestamp.ext
        $unique_name = "cedolino_" . preg_replace("/[^a-zA-Z0-9]/", "_", $username) . "_" . time() . '.' . $file_ext;
        $dest_path = $upload_dir . $unique_name;

        // Sposta il file dalla cartella temporanea alla destinazione finale
        if (move_uploaded_file($file_tmp_path, $dest_path)) {
            // File spostato con successo! Salva il percorso nel database
            $percorso_cedolino_db = $dest_path; // Memorizza il percorso relativo
        } else {
            // Errore durante lo spostamento del file
            echo "Errore: Impossibile caricare il file.";
            // Potresti voler loggare l'errore o informare l'utente
            // In questo caso, $percorso_cedolino_db rimarrà null
        }
    } elseif (isset($_FILES['cedolino']) && $_FILES['cedolino']['error'] !== UPLOAD_ERR_NO_FILE) {
        // C'è stato un errore diverso da "nessun file caricato"
        echo "Errore durante l'upload del file: Codice " . $_FILES['cedolino']['error'];
        // Vedi: https://www.php.net/manual/en/features.file-upload.errors.php
    }
    // Se $_FILES['cedolino']['error'] === UPLOAD_ERR_NO_FILE, significa semplicemente
    // che l'utente non ha selezionato un file, quindi $percorso_cedolino_db resterà null (OK)

    // --- Inserimento nel Database ---
    // Prepara la query SQL includendo la nuova colonna 'percorso_cedolino'
    $sql = $conn->prepare("INSERT INTO chilometri (username, targa_mezzo, divisione, filiale, data, chilometri_iniziali, chilometri_finali, litri_carburante, euro_spesi, percorso_cedolino) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Aggiorna i tipi nel bind_param (aggiungi 's' per il percorso del cedolino)
    // s = string, i = integer, d = double
    $sql->bind_param("sssssiidds",
        $username,
        $targa_mezzo,
        $divisione,
        $filiale,
        $data,
        $chilometri_iniziali,
        $chilometri_finali,
        $litri_carburante,
        $euro_spesi,
        $percorso_cedolino_db // Passa il percorso (o null se nessun file caricato/errore)
    );

    // Esegui la query e controlla il risultato
    if ($sql->execute()) {
        // Inserimento riuscito
        // Redirect alla pagina principale o a una pagina di successo
        header("Location: index.php?status=success"); // Puoi usare index.php se è il form
        exit();
    } else {
        // Errore nell'inserimento
        echo "Errore durante l'inserimento nel database: " . $sql->error;
        // In un'applicazione reale, logga l'errore dettagliato
    }

    $sql->close(); // Chiudi lo statement

} else {
    // Se la richiesta non è POST, reindirizza o mostra un errore
    echo "Metodo di richiesta non valido.";
    // header("Location: index.php"); // Esempio di redirect
    // exit();
}

$conn->close(); // Chiudi la connessione al database
?>