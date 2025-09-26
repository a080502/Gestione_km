<?php
// File: query/q_target_km.php (MODIFICATO - Aggunto get by ID e update)

/**
 * Conta il numero totale di record nella tabella target_annuale.
 * Se il livello è >= 3, conta solo per l'utente specificato.
 * @param mysqli $conn La connessione al database.
 * @param string $username Lo username dell'utente (usato solo se livello >= 3).
 * @param int $livello Il livello di autorizzazione dell'utente.
 * @return int Il numero totale di record o 0 in caso di errore/nessun record.
 */
function count_target_km_by_user($conn, $username, $livello) {
    $totale_record = 0;
    $sql = "SELECT COUNT(*) as totale FROM target_annuale";
    $param_types = "";
    $param_values = [];

    if ($livello >= 3) {
        $sql .= " WHERE username = ?";
        $param_types = "s";
        $param_values[] = $username;
    }

    $sql_conta = $conn->prepare($sql);

    if ($sql_conta) {
        if (!empty($param_types)) {
            $sql_conta->bind_param($param_types, ...$param_values);
        }
        $sql_conta->execute();
        $result_conta = $sql_conta->get_result();
        if ($result_conta->num_rows > 0) {
            $row_conta = $result_conta->fetch_assoc();
            $totale_record = $row_conta['totale'];
        }
        $sql_conta->close();
    } else {
        error_log("Errore preparazione query conteggio target KM (livello: $livello): " . $conn->error);
    }
    return $totale_record;
}

/**
 * Recupera i record dalla tabella target_annuale con paginazione.
 * Se il livello è >= 3, recupera solo per l'utente specificato.
 * Ordina per anno (discendente), filiale (ascendente), targa_mezzo (ascendente), target_chilometri (ascendente).
 * @param mysqli $conn La connessione al database.
 * @param string $username Lo username dell'utente (usato solo se livello >= 3).
 * @param int $livello Il livello di autorizzazione dell'utente.
 * @param int $limit Il numero massimo di record da recuperare.
 * @param int $offset L'offset per la paginazione.
 * @return array Un array di record o un array vuoto in caso di errore/nessun record.
 */
function get_target_km_by_user($conn, $username, $livello, $limit, $offset) {
    $records = []; // Inizializza array risultati

    $sql = "SELECT * FROM target_annuale";
    $param_types = "";
    $param_values = [];

    if ($livello >= 3) {
        $sql .= " WHERE username = ?";
        $param_types = "s";
        $param_values[] = $username;
    }

    // Ordina per anno (DESC), filiale (ASC), targa_mezzo (ASC), target_chilometri (ASC), e id (DESC) per tie-breaker
    $sql .= " ORDER BY targa_mezzo ASC, filiale ASC, anno DESC, target_chilometri ASC, id DESC LIMIT ? OFFSET ?";

    // Aggiungi i tipi e valori per LIMIT e OFFSET
    $param_types .= "ii";
    $param_values[] = $limit;
    $param_values[] = $offset;


    $sql_records = $conn->prepare($sql);

    if ($sql_records) {
        if (!empty($param_types)) {
            // Utilizza call_user_func_array o l'operatore ... per bind_param
            $sql_records->bind_param($param_types, ...$param_values);
        }
        $sql_records->execute();
        $result_records = $sql_records->get_result();
        while ($row = $result_records->fetch_assoc()) {
            $records[] = $row;
        }
        $sql_records->close();
    } else {
        error_log("Errore preparazione query recupero record target KM (livello: $livello): " . $conn->error);
    }
    return $records;
}

/**
 * Recupera un singolo record dalla tabella target_annuale per ID.
 * @param mysqli $conn La connessione al database.
 * @param int $id L'ID del record da recuperare.
 * @return array|null Un array associativo del record o null se non trovato.
 */
function get_target_km_by_id($conn, $id) {
    $record = null;
    // Usiamo solo l'ID, i controlli di proprietà verranno fatti nella pagina chiamante
    $sql = "SELECT * FROM target_annuale WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $record = $result->fetch_assoc();
        }
        $stmt->close();
    } else {
        error_log("Errore preparazione query get target by ID: " . $conn->error);
    }
    return $record;
}

/**
 * Aggiorna un record nella tabella target_annuale.
 * @param mysqli $conn La connessione al database.
 * @param int $id L'ID del record da aggiornare.
 * @param int $anno L'anno del target.
 * @param int $target_chilometri Il valore del target chilometri.
 * @param string $username Lo username associato.
 * @param string $targa_mezzo La targa del mezzo.
 * @param string $divisione La divisione.
 * @param string $filiale La filiale.
 * @return bool True in caso di successo, false in caso di errore.
 */
function update_target_km($conn, $id, $anno, $target_chilometri, $username, $targa_mezzo, $divisione, $filiale) {
    // Aggiorniamo tutti i campi passati (tranne l'ID).
    $sql = "UPDATE target_annuale SET anno = ?, target_chilometri = ?, username = ?, targa_mezzo = ?, divisione = ?, filiale = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("iissssi", $anno, $target_chilometri, $username, $targa_mezzo, $divisione, $filiale, $id);
        $success = $stmt->execute();
        if (!$success) {
            error_log("Errore esecuzione query update target KM: " . $stmt->error);
        }
        $stmt->close();
        return $success;
    } else {
        error_log("Errore preparazione query update target KM: " . $conn->error);
        return false;
    }
}

?>