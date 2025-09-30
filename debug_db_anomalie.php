<?php
// Debug: mostra i record in anomalie_flaggate per id_rifornimento passato via GET
header('Content-Type: application/json; charset=utf-8');
include_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'id non valido']);
    exit();
}

$stmt = $conn->prepare('SELECT * FROM anomalie_flaggate WHERE id_rifornimento = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
}

echo json_encode(['success' => true, 'rows' => $rows]);
