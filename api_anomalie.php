<?php
// API minimale per la gestione delle anomalie (flag/unflag/dettaglio)
header('Content-Type: application/json; charset=utf-8');
include_once 'config.php';
session_start();

// Controllo autenticazione minima
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user = $_SESSION['username'];

$action = $_POST['action'] ?? $_GET['action'] ?? null;

try {
    if ($action === 'flag_anomalia' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_rifornimento = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $tipo = $_POST['tipo'] ?? 'ANOMALIA_VERIFICATA';
        $note = $_POST['note'] ?? null;

        if ($id_rifornimento <= 0) throw new Exception('ID non valido');

        // Proviamo prima ad aggiornare se esiste già un flag per questo rifornimento
        $upd = $conn->prepare('UPDATE anomalie_flaggate SET tipo_flag = ?, flaggato_da = ?, note = ?, risolto = 0 WHERE id_rifornimento = ?');
        $upd->bind_param('sssi', $tipo, $user, $note, $id_rifornimento);
        if (!$upd->execute()) throw new Exception('DB error (update): ' . $upd->error);
        if ($upd->affected_rows > 0) {
            echo json_encode(['success' => true, 'action' => 'updated']);
            exit();
        }

        // Se non esiste, recupera i dati del rifornimento e inserisci il flag
        $stmt = $conn->prepare('SELECT id, username, targa_mezzo FROM chilometri WHERE id = ?');
        $stmt->bind_param('i', $id_rifornimento);
        $stmt->execute();
        $res = $stmt->get_result();
        $r = $res->fetch_assoc();
        if (!$r) throw new Exception('Rifornimento non trovato');

        $ins = $conn->prepare('INSERT INTO anomalie_flaggate (id_rifornimento, username, targa_mezzo, tipo_flag, flaggato_da, note) VALUES (?, ?, ?, ?, ?, ?)');
        $ins->bind_param('isssss', $id_rifornimento, $r['username'], $r['targa_mezzo'], $tipo, $user, $note);
        if (!$ins->execute()) throw new Exception('DB error (insert): ' . $ins->error);

        echo json_encode(['success' => true, 'action' => 'inserted']);
        exit();
    } elseif ($action === 'unflag_anomalia' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_rifornimento = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id_rifornimento <= 0) throw new Exception('ID non valido');

        // Marca come risolto se presente (usiamo id_rifornimento)
        $stmt = $conn->prepare('UPDATE anomalie_flaggate SET risolto = 1 WHERE id_rifornimento = ?');
        $stmt->bind_param('i', $id_rifornimento);
        if (!$stmt->execute()) throw new Exception('DB error: ' . $stmt->error);

        echo json_encode(['success' => true]);
        exit();
    } elseif (($action === 'get_dettaglio' || $action === 'get_dettaglio') && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID non valido']);
            exit();
        }

        // Recupera i dettagli dalla tabella chilometri
        $stmt = $conn->prepare('SELECT * FROM chilometri WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        if (!$row) {
            echo json_encode(['success' => false, 'error' => 'Registrazione non trovata']);
            exit();
        }

        // Calcoli ausiliari
        $km_percorsi = (int)$row['chilometri_finali'] - (int)$row['chilometri_iniziali'];
        $litri = (float)$row['litri_carburante'];
        $consumo = $litri > 0 ? $km_percorsi / $litri : null;

        $row['dettagli_calcolati'] = [
            'km_percorsi' => $km_percorsi,
            'consumo_km_litro' => $consumo,
            'prezzo_per_litro' => $litri > 0 ? ((float)$row['euro_spesi'] / $litri) : null,
            'scostamento_percentuale' => null
        ];

        echo json_encode(['success' => true, 'data' => $row]);
        exit();
    } else {
        echo json_encode(['success' => false, 'error' => 'Azione non supportata']);
        exit();
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit();
}
