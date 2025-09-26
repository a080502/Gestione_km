<?php
// qutenti.php

function get_user_data($conn, $username) {
    $sql = $conn->prepare("SELECT targa_mezzo, divisione, filiale, livello FROM utenti WHERE username = ?");
    $sql->bind_param("s", $username);
    $sql->execute();
    $result = $sql->get_result();
    $utente_data = null;
    if ($result->num_rows > 0) {
        $utente_data = $result->fetch_assoc();
    }
    $sql->close(); // Chiudi lo statement preparato
    return $utente_data; // Restituisci i dati dell'utente
}

/**
 * Funzione per recuperare il totale dei chilometri registrati per una targa specifica fino a un dato mese e anno (opzionale).
 *
 * @param mysqli $conn Connessione al database.
 * @param string $targa_mezzo Targa del mezzo.
 * @param int|null $year Anno fino al quale calcolare i chilometri (opzionale).
 * @param string|null $month Mese fino al quale calcolare i chilometri (formato 'MM', opzionale).
 * @return int Il totale dei chilometri registrati, o 0 in caso di errore.
 */
function get_total_registered_kilometers(mysqli $conn, string $targa_mezzo, ?int $year = null, ?string $month = null): int
{
    $sql = "SELECT SUM(chilometri_finali - chilometri_iniziali) AS totale_km FROM chilometri WHERE targa_mezzo = ?";
    if ($year !== null) {
        $sql .= " AND DATE_FORMAT(data, '%Y') <= ?";
        if ($month !== null) {
            $sql .= " AND DATE_FORMAT(data, '%m') <= ?";
        }
    }
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return 0;
    }
    if ($year !== null && $month !== null) {
        $stmt->bind_param("sis", $targa_mezzo, $year, $month);
    } elseif ($year !== null) {
        $stmt->bind_param("si", $targa_mezzo, $year);
    } else {
        $stmt->bind_param("s", $targa_mezzo);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $totale_km = 0;
    if ($row = $result->fetch_assoc()) {
        $totale_km = $row['totale_km'] ?? 0;
    }
    $stmt->close();
    return $totale_km;
}

/**
 * Funzione per recuperare l'ID del costo extra per una data targa.
 *
 * @param mysqli $conn Connessione al database.
 * @param string $targa_mezzo Targa del mezzo.
 * @return int|null L'ID del costo extra, o null se non trovato.
 */
function get_costo_sconfino_id_by_targa(mysqli $conn, string $targa_mezzo): ?int
{
    $sql = "SELECT id FROM costo_extra WHERE targa_mezzo = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return null;
    }
    $stmt->bind_param("s", $targa_mezzo);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return (int)$row['id'];
    }
    $stmt->close();
    return null;
}

/**
 * Funzione per recuperare il costo extra per una data targa.
 *
 * @param mysqli $conn Connessione al database.
 * @param string $targa_mezzo Targa del mezzo.
 * @return float|null Il costo extra, o null se non trovato.
 */
function get_costo_sconfino_by_targa(mysqli $conn, string $targa_mezzo): ?float
{
    $sql = "SELECT costo FROM costo_extra WHERE targa_mezzo = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return null;
    }
    $stmt->bind_param("s", $targa_mezzo);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return (float)$row['costo'];
    }
    $stmt->close();
    return null;
}

/**
 * Funzione per aggiornare il costo extra per una data targa.
 *
 * @param mysqli $conn Connessione al database.
 * @param float $costo Nuovo costo extra.
 * @param string $targa_mezzo Targa del mezzo.
 * @return bool True se l'aggiornamento ha successo, false altrimenti.
 */
function update_costo_sconfino(mysqli $conn, float $costo, string $targa_mezzo): bool
{
    $sql = "UPDATE costo_extra SET costo = ? WHERE targa_mezzo = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return false;
    }
    $stmt->bind_param("ds", $costo, $targa_mezzo);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Funzione per inserire il costo extra nel database.
 *
 * @param mysqli $conn Connessione al database.
 * @param string $targa_mezzo Targa del mezzo.
 * @param float $costo Costo extra.
 * @return bool True se l'inserimento ha successo, false altrimenti.
 */
function insert_costo_sconfino(mysqli $conn, string $targa_mezzo, float $costo): bool
{
    $sql = "INSERT INTO costo_extra (targa_mezzo, costo) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return false;
    }
    $stmt->bind_param("sd", $targa_mezzo, $costo);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}
?>
