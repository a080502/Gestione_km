<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sql = "SELECT * FROM chilometri WHERE id = $id";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Chilometri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 60px; /* Spazio per la navbar fissa */
        }

        .fixed-top-elements {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background-color: #e9ecef;
            padding: 10px 15px;
            z-index: 1030;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #dee2e6;
        }

        .menu-btn {
            font-size: 1.2rem;
        }
    </style>
</head>

<body>

    <div class="fixed-top-elements">
        <button class="btn btn-outline-secondary menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMenu" aria-controls="mainMenu">
            <i class="bi bi-list"></i> Menu
        </button>
        <div></div> </div>

    <?php include 'include/menu.php'; ?>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Modifica dati chilometri</h1>

        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form action="aggiorna.php" method="post" enctype="multipart/form-data" onsubmit="return confermaAggiornamento()">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">

                            <div class="mb-3">
                                <label for="data" class="form-label">Data:</label>
                                <input type="date" class="form-control" id="data" name="data" value="<?php echo htmlspecialchars($row['data']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="chilometri_iniziali" class="form-label">Chilometri iniziali:</label>
                                <input type="number" class="form-control" id="chilometri_iniziali" name="chilometri_iniziali" value="<?php echo htmlspecialchars($row['chilometri_iniziali']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="chilometri_finali" class="form-label">Chilometri finali:</label>
                                <input type="number" class="form-control" id="chilometri_finali" name="chilometri_finali" value="<?php echo htmlspecialchars($row['chilometri_finali']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="litri_carburante" class="form-label">Litri carburante:</label>
                                <input type="number" class="form-control" id="litri_carburante" name="litri_carburante" value="<?php echo htmlspecialchars($row['litri_carburante']); ?>" step="0.01">
                            </div>

                            <div class="mb-3">
                                <label for="euro_spesi" class="form-label">Euro spesi:</label>
                                <input type="number" class="form-control" id="euro_spesi" name="euro_spesi" value="<?php echo htmlspecialchars($row['euro_spesi']); ?>" step="0.01">
                            </div>

                            <div class="mb-3">
                                <label for="note" class="form-label">Note:</label>
                                <textarea class="form-control" id="note" name="note"><?php echo htmlspecialchars($row['note']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="cedolino" class="form-label">Modifica Cedolino:</label>
                                <?php if (!empty($row['percorso_cedolino'])): ?>
                                    <p>Cedolino attuale: <a href="#" onclick="window.open('<?php echo htmlspecialchars($row['percorso_cedolino']); ?>', 'Cedolino', 'width=600,height=800'); return false;">Visualizza</a></p>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="rimuovi_cedolino" id="rimuovi_cedolino" value="1">
                                        <label class="form-check-label" for="rimuovi_cedolino">Rimuovi cedolino attuale</label>
                                    </div>
                                <?php else: ?>
                                    <p>Nessun cedolino caricato.</p>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="cedolino" name="cedolino">
                                <small class="form-text text-muted">Seleziona un nuovo file per sostituire quello esistente o caricarne uno.</small>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i> Aggiorna</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script>
        function openNav() {
            document.getElementById("mySidebar").style.width = "250px";
            document.getElementById("main").style.marginLeft = "250px";
        }

        function closeNav() {
            document.getElementById("mySidebar").style.width = "0";
            document.getElementById("main").style.marginLeft = "0";
        }

        function confermaAggiornamento() {
            return confirm("Sei sicuro di voler aggiornare i dati?");
        }
    </script>

</body>
</html>