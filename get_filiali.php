<?php
include_once 'config.php';

if (isset($_GET['divisione'])) {
    $divisione = $_GET['divisione'];

    $sql_filiali = $conn->prepare("SELECT DISTINCT filiale FROM filiali WHERE divisione = ? ORDER BY filiale");
    $sql_filiali->bind_param("s", $divisione);
    $sql_filiali->execute();
    $result_filiali = $sql_filiali->get_result();

    $filiali = [];
    if ($result_filiali->num_rows > 0) {
        while ($row = $result_filiali->fetch_assoc()) {
            // Modifica qui per creare un array di oggetti
            $filiali[] = ['nome_divisione' => $row['filiale']];
        }
    }

    header('Content-Type: application/json');
    echo json_encode($filiali);
}
?>