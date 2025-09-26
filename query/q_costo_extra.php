<?php
// File: query/q_costo_extra.php

/**
 * Conta i record di costo extra visibili dall'utente in base al suo livello e targa.
 *
 * @param mysqli $conn Connessione al database.
 * @param string|null $targa_utente Targa associata all'utente (usata se livello >= 3). Può essere null.
 * @param int $livello_utente Livello di permesso dell'utente.
 * @return int Numero totale di record visibili.
 */
function count_costo_extra_by_user(mysqli $conn, ?string $targa_utente, int $livello_utente): int
{
    $sql = "SELECT COUNT(*) as total FROM costo_extra";
    $params = [];
    $types = "";

    // Se l'utente non è admin (livello >= 3), filtra per la sua targa
    if ($livello_utente >= 3) {
        // Se l'utente di livello >= 3 non ha una targa associata, non può vedere nulla
        if (empty($targa_utente)) {
            return 0;
        }
        $sql .= " WHERE targa_mezzo = ?";
        $params[] = $targa_utente;
        $types .= "s";
    }
    // Altrimenti (livello < 3), l'admin vede tutto, nessuna clausola WHERE aggiuntiva.

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Errore preparazione query count_costo_extra_by_user: " . $conn->error);
        return 0;
    }

    // Bind dei parametri solo se ce ne sono
    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log("Errore esecuzione query count_costo_extra_by_user: " . $stmt->error);
        $stmt->close();
        return 0;
    }

    $result = $stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        $stmt->close();
        return isset($row['total']) ? (int) $row['total'] : 0;
    } else {
        error_log("Errore recupero risultato count_costo_extra_by_user: " . $stmt->error);
        $stmt->close();
        return 0;
    }
}

/**
 * Recupera un elenco paginato di record di costo extra visibili dall'utente.
 *
 * @param mysqli $conn Connessione al database.
 * @param string|null $targa_utente Targa associata all'utente (usata se livello >= 3). Può essere null.
 * @param int $livello_utente Livello di permesso dell'utente.
 * @param int $limite Numero massimo di record da restituire.
 * @param int $offset Numero di record da saltare (per paginazione).
 * @return array Elenco dei record trovati (array di array associativi). Array vuoto se nessun record o errore.
 */
function get_costo_extra_by_user(mysqli $conn, ?string $targa_utente, int $livello_utente, int $limite, int $offset): array
{
    // Controlla subito se l'utente >= 3 non ha targa, non può vedere nulla
    if ($livello_utente >= 3 && empty($targa_utente)) {
        return [];
    }

    $sql = "SELECT id, targa_mezzo, costo, time_stamp FROM costo_extra";
    $params = [];
    $types = "";

    // Se l'utente non è admin (livello >= 3), filtra per la sua targa
    if ($livello_utente >= 3) {
        // La targa è stata già verificata all'inizio della funzione
        $sql .= " WHERE targa_mezzo = ?";
        $params[] = $targa_utente;
        $types .= "s";
    }
    // Altrimenti (livello < 3), l'admin vede tutto.

    // Aggiungi ordinamento (es. per data più recente) e paginazione
    $sql .= " ORDER BY time_stamp DESC LIMIT ? OFFSET ?";
    $params[] = $limite;
    $params[] = $offset;
    $types .= "ii"; // Aggiunge i tipi per LIMIT e OFFSET

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Errore preparazione query get_costo_extra_by_user: " . $conn->error);
        return [];
    }

    // Bind dei parametri (ci saranno sempre almeno LIMIT e OFFSET)
    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        error_log("Errore esecuzione query get_costo_extra_by_user: " . $stmt->error);
        $stmt->close();
        return [];
    }

    $result = $stmt->get_result();
    $records = [];
    if ($result) {
        // Fetch all restituisce un array di array associativi, o null se non ci sono righe
        $records = $result->fetch_all(MYSQLI_ASSOC);
        if ($records === null) {
            $records = []; // Assicura che sia sempre un array
        }
    } else {
        error_log("Errore recupero risultato get_costo_extra_by_user: " . $stmt->error);
    }

    $stmt->close();
    return $records;
}


/**
 * Recupera UN singolo costo extra per una data targa.
 * NOTA: Questa funzione è stata fornita precedentemente, ma non è usata
 * direttamente da visualizza_costo_extra.php per la tabella principale.
 *
 * @param mysqli $conn Connessione al database.
 * @param string $targa_mezzo Targa specifica da cercare.
 * @return float|null Costo trovato come float, o null se non trovato/errore.
 */
function get_costo_extra(mysqli $conn, string $targa_mezzo): ?float
{
    $sql = "SELECT costo FROM costo_extra WHERE targa_mezzo = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Errore nella preparazione della query get_costo_extra: " . $conn->error);
        return null;
    }
    $stmt->bind_param("s", $targa_mezzo);

    if (!$stmt->execute()) {
        error_log("Errore esecuzione query get_costo_extra: " . $stmt->error);
        $stmt->close();
        return null;
    }

    $result = $stmt->get_result();
    $costo = null;
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $costo = (float) $row['costo'];
    }

    $stmt->close();
    return $costo;
}
/**
 * Cancella un record costo extra dal database tramite il suo ID.
 * NON gestisce i permessi utente - il controllo va fatto PRIMA di chiamare questa funzione.
 *
 * @param mysqli $conn Connessione al database.
 * @param int $id L'ID del record da cancellare.
 * @return bool True se la cancellazione è riuscita e almeno una riga è stata influenzata, false altrimenti.
 */
function delete_costo_extra(mysqli $conn, int $id): bool
{
    $sql = "DELETE FROM costo_extra WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Errore nella preparazione della query delete_costo_extra: " . $conn->error);
        return false;
    }
    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        error_log("Errore esecuzione query delete_costo_extra (ID: $id): " . $stmt->error);
        $stmt->close();
        return false;
    }

    $affected_rows = $stmt->affected_rows;
    $stmt->close();

    // Restituisce true solo se almeno una riga è stata influenzata (cancellata)
    return $affected_rows > 0;
}

/**
 * Recupera i dati di un singolo record costo extra tramite il suo ID.
 *
 * @param mysqli $conn Connessione al database.
 * @param int $id L'ID del record da recuperare.
 * @return array|null Un array associativo con i dati del record, o null se non trovato.
 */
function getCostoExtraById(mysqli $conn, int $id): ?array
{
    $sql = "SELECT id, targa_mezzo, costo, time_stamp FROM costo_extra WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Errore nella preparazione della query getCostoExtraById: " . $conn->error);
        return null;
    }
    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        error_log("Errore esecuzione query getCostoExtraById (ID: $id): " . $stmt->error);
        $stmt->close();
        return null;
    }

    $result = $stmt->get_result();
    $record = null;
    if ($result && $result->num_rows > 0) {
        $record = $result->fetch_assoc();
    }

    $stmt->close();
    return $record;
}

/**
 * Aggiorna un record costo extra nel database tramite il suo ID.
 * Permette di aggiornare targa_mezzo e costo.
 * NON gestisce i permessi utente o le restrizioni sulla modifica della targa - il controllo va fatto PRIMA di chiamare questa funzione.
 *
 * @param mysqli $conn Connessione al database.
 * @param int $id L'ID del record da aggiornare.
 * @param string $targa_mezzo Nuova targa del mezzo.
 * @param float $costo Nuovo costo extra.
 * @return bool True se l'aggiornamento è riuscito (anche se 0 righe cambiate), false altrimenti.
 */
function update_costo_extra(mysqli $conn, int $id, string $targa_mezzo, float $costo): bool
{
    $sql = "UPDATE costo_extra SET targa_mezzo = ?, costo = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Errore nella preparazione della query update_costo_extra: " . $conn->error);
        return false;
    }
    // Usa 'sdi': string (targa), double (costo), integer (id)
    $stmt->bind_param("sdi", $targa_mezzo, $costo, $id);

    if (!$stmt->execute()) {
        error_log("Errore esecuzione query update_costo_extra (ID: $id): " . $stmt->error);
        $stmt->close();
        return false;
    }

    // Nota: affected_rows può essere 0 se i dati non sono cambiati, ma l'operazione è comunque riuscita.
    $stmt->close();
    return true; // Consideriamo l'operazione riuscita a meno di errori di esecuzione.
}

// Aggiungo anche la funzione per inserimento per completezza, servirà per inserisci_costo_extra.php
/**
 * Inserisce un nuovo record costo extra nel database.
 * NON gestisce i permessi utente - il controllo va fatto PRIMA di chiamare questa funzione.
 * Il timestamp viene generato dal database al momento dell'inserimento.
 *
 * @param mysqli $conn Connessione al database.
 * @param string $targa_mezzo Targa del mezzo.
 * @param float $costo Costo extra.
 * @return int|false L'ID del record inserito in caso di successo, false altrimenti.
 */
function insert_costo_extra(mysqli $conn, string $targa_mezzo, float $costo) : int|false
{
    // Utilizza NOW() per lasciare al database il timestamp, più robusto
    $sql = "INSERT INTO costo_extra (targa_mezzo, costo, time_stamp) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Errore nella preparazione della query insert_costo_extra: " . $conn->error);
        return false;
    }
    // Usa 'sd': string (targa), double (costo)
    $stmt->bind_param("sd", $targa_mezzo, $costo);

    if (!$stmt->execute()) {
        error_log("Errore esecuzione query insert_costo_extra: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $inserted_id = $conn->insert_id; // Ottiene l'ID auto-incrementato
    $stmt->close();

    return $inserted_id > 0 ? $inserted_id : false;
}

?>