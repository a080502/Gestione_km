<?php
/**
 * API per gestione anomalie report avanzato
 */

session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorizzato']);
    exit();
}

include_once 'config.php';
include_once 'dati_utente.php';

// Verifica privilegi
if ($utente_data['livello'] > 2) {
    http_response_code(403);
    echo json_encode(['error' => 'Privilegi insufficienti']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'flag_anomalia':
        flagAnomalia();
        break;
    case 'get_dettaglio':
        getDettaglioAnomalia();
        break;
    case 'get_statistiche_utente':
        getStatisticheUtente();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Azione non valida']);
}

function flagAnomalia() {
    global $conn;
    
    $id_registrazione = intval($_POST['id'] ?? 0);
    $tipo_flag = $_POST['tipo'] ?? 'ANOMALIA_VERIFICATA';
    $note = $_POST['note'] ?? '';
    
    if ($id_registrazione <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID registrazione non valido']);
        return;
    }
    
    // Verifica che la registrazione esista
    $stmt = $conn->prepare("SELECT id FROM chilometri WHERE id = ?");
    $stmt->bind_param("i", $id_registrazione);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Registrazione non trovata']);
        return;
    }
    
    // Crea o aggiorna la tabella per le anomalie flaggate se non esiste
    $conn->query("CREATE TABLE IF NOT EXISTS anomalie_flaggate (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_registrazione INT NOT NULL,
        tipo_flag VARCHAR(50) NOT NULL,
        note TEXT,
        flaggato_da VARCHAR(255) NOT NULL,
        data_flag TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        risolto BOOLEAN DEFAULT FALSE,
        UNIQUE KEY unique_flag (id_registrazione),
        FOREIGN KEY (id_registrazione) REFERENCES chilometri(id) ON DELETE CASCADE
    )");
    
    // Inserisci o aggiorna il flag
    $stmt = $conn->prepare("INSERT INTO anomalie_flaggate (id_registrazione, tipo_flag, note, flaggato_da) 
                           VALUES (?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE 
                           tipo_flag = VALUES(tipo_flag),
                           note = VALUES(note),
                           flaggato_da = VALUES(flaggato_da),
                           data_flag = CURRENT_TIMESTAMP");
    
    $stmt->bind_param("isss", $id_registrazione, $tipo_flag, $note, $_SESSION['username']);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Anomalia flaggata con successo'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Errore durante il salvataggio']);
    }
}

function getDettaglioAnomalia() {
    global $conn;
    
    $id_registrazione = intval($_GET['id'] ?? 0);
    
    if ($id_registrazione <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID registrazione non valido']);
        return;
    }
    
    // Query per ottenere dettagli completi
    $stmt = $conn->prepare("
        SELECT 
            c.*,
            af.tipo_flag,
            af.note as note_flag,
            af.flaggato_da,
            af.data_flag,
            af.risolto,
            -- Statistiche comparative
            (SELECT AVG((chilometri_finali - chilometri_iniziali) / NULLIF(CAST(litri_carburante as DECIMAL(10,2)), 0))
             FROM chilometri c2 
             WHERE c2.targa_mezzo = c.targa_mezzo 
             AND c2.data >= DATE_SUB(c.data, INTERVAL 3 MONTH)
             AND c2.data < c.data
             AND CAST(c2.litri_carburante as DECIMAL(10,2)) > 0) as consumo_medio_precedente,
            
            (SELECT COUNT(*) 
             FROM chilometri c3 
             WHERE c3.username = c.username 
             AND c3.data >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)) as rifornimenti_ultimi_6_mesi
             
        FROM chilometri c
        LEFT JOIN anomalie_flaggate af ON c.id = af.id_registrazione
        WHERE c.id = ?
    ");
    
    $stmt->bind_param("i", $id_registrazione);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Calcola metriche aggiuntive
        $km_percorsi = $row['chilometri_finali'] - $row['chilometri_iniziali'];
        $litri = floatval($row['litri_carburante']);
        $consumo_attuale = $litri > 0 ? $km_percorsi / $litri : 0;
        $prezzo_litro = $litri > 0 ? $row['euro_spesi'] / $litri : 0;
        
        $row['dettagli_calcolati'] = [
            'km_percorsi' => $km_percorsi,
            'consumo_km_litro' => $consumo_attuale,
            'prezzo_per_litro' => $prezzo_litro,
            'scostamento_percentuale' => $row['consumo_medio_precedente'] > 0 
                ? (($consumo_attuale - $row['consumo_medio_precedente']) / $row['consumo_medio_precedente']) * 100 
                : null
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $row
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Registrazione non trovata']);
    }
}

function getStatisticheUtente() {
    global $conn;
    
    $username = $_GET['username'] ?? '';
    $targa = $_GET['targa'] ?? '';
    
    if (empty($username) || empty($targa)) {
        http_response_code(400);
        echo json_encode(['error' => 'Username e targa sono richiesti']);
        return;
    }
    
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as totale_registrazioni,
            SUM(chilometri_finali - chilometri_iniziali) as km_totali,
            SUM(CAST(litri_carburante as DECIMAL(10,2))) as litri_totali,
            SUM(euro_spesi) as spesa_totale,
            AVG((chilometri_finali - chilometri_iniziali) / NULLIF(CAST(litri_carburante as DECIMAL(10,2)), 0)) as consumo_medio,
            MIN(data) as prima_registrazione,
            MAX(data) as ultima_registrazione,
            
            -- Anomalie per questo utente
            (SELECT COUNT(*) 
             FROM anomalie_flaggate af 
             JOIN chilometri c2 ON af.id_registrazione = c2.id 
             WHERE c2.username = ? AND c2.targa_mezzo = ?) as anomalie_flaggate,
             
            -- Tendenza ultimi 3 mesi
            (SELECT AVG((chilometri_finali - chilometri_iniziali) / NULLIF(CAST(litri_carburante as DECIMAL(10,2)), 0))
             FROM chilometri c3
             WHERE c3.username = ? AND c3.targa_mezzo = ?
             AND c3.data >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
             AND CAST(c3.litri_carburante as DECIMAL(10,2)) > 0) as consumo_ultimi_3_mesi
             
        FROM chilometri 
        WHERE username = ? AND targa_mezzo = ?
        AND data >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    ");
    
    $stmt->bind_param("ssssss", $username, $targa, $username, $targa, $username, $targa);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'data' => $row
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => null
        ]);
    }
}

$conn->close();
?>