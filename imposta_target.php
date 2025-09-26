<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include_once 'config.php';
include 'query/qutenti.php'; // Assicurati che il percorso sia corretto

$username = $_SESSION['username'];
$utente_data = get_user_data($conn, $username);
$livello = $utente_data['livello'];
$divisione = $utente_data['divisione']; // Recupero la divisione
// Verifica se l'utente è stato trovato e se il suo livello è minore di 3
if (!$utente_data || $utente_data['livello'] >= 3) {
    // Se il livello è 3 o superiore, o l'utente non esiste, reindirizza alla pagina di non autorizzazione
    header("Location: unauthorized.php"); // Assicurati di avere la pagina unauthorized.php creata
    exit();
}
// Funzione per recuperare le targhe in base al livello utente
function get_user_targhe($conn, $username, $livello, $divisione) {
    // Default per evitare problemi in caso di livelli non definiti
    $sql = "";
    $params = [];

    switch ($livello) {
        case 1:
            // Admin vede tutti i mezzi, eccetto il suo
            $sql = "SELECT DISTINCT targa_mezzo FROM utenti WHERE username != ?";
            $params = [$username];
            break;
        case 2:
            // Utente vede solo i mezzi della sua divisione, eccetto il suo
            $sql = "SELECT DISTINCT targa_mezzo FROM utenti WHERE divisione = ? AND username != ?";
            $params = [$divisione, $username];
            break;
        case 3:
            // Responsabile vede solo il suo mezzo
            $sql = "SELECT DISTINCT targa_mezzo FROM utenti WHERE username = ?";
            $params = [$username];
            break;
        default:
            return []; // Restituisci un array vuoto se il livello non è valido
    }

    // Prepara la query
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Errore nella preparazione della query: " . $conn->error);
    }

    // Associa i parametri
    $stmt->bind_param(str_repeat("s", count($params)), ...$params);

    // Esegui la query
    $stmt->execute();
    $result = $stmt->get_result();

    // Recupera le targhe
    $targhe = [];
    while ($row = $result->fetch_assoc()) {
        $targhe[] = $row['targa_mezzo'];
    }

    // Chiudi la dichiarazione
    $stmt->close();

    return $targhe;
}

$targhe_mezzo = get_user_targhe($conn, $username, $livello, $divisione);

// Gestisci il caso in cui non ci sono targhe
if (empty($targhe_mezzo)) {
    echo "<div class='alert alert-info'>Nessuna targa trovata per il tuo livello di autorizzazione.</div>";
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imposta Target Annuale</title>
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

        .username-display {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <div class="fixed-top-elements">
        <button class="btn btn-outline-secondary menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMenu" aria-controls="mainMenu">
            <i class="bi bi-list"></i> Menu
        </button>
        <div class="username-display">
            Utente: <?php echo htmlspecialchars($_SESSION['username']); ?>
        </div>
    </div>

    <?php include 'include/menu.php'; ?>
    <div class="container" id="main-content">
        <h1 class="mb-4 text-center">Imposta Target Annuale</h1>

        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php if (!empty($targhe_mezzo)): ?>
                    <form method="post" action="salva_target.php" class="bg-white shadow-sm rounded p-4">
                        <div class="mb-3">
                            <label for="targa_mezzo" class="form-label">Targa Mezzo:</label>
                            <select name="targa_mezzo" id="targa_mezzo" class="form-select">
                                <?php foreach ($targhe_mezzo as $targa): ?>
                                    <option value="<?php echo htmlspecialchars($targa); ?>"><?php echo htmlspecialchars($targa); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <input type="hidden" name="divisione" value="<?php echo htmlspecialchars($utente_data['divisione'] ?? ''); ?>">
                        <input type="hidden" name="filiale" value="<?php echo htmlspecialchars($utente_data['filiale'] ?? ''); ?>">
                        <div class="mb-3">
                            <label for="anno" class="form-label">Anno:</label>
                            <input type="number" name="anno" id="anno" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="target_chilometri" class="form-label">Target Chilometri:</label>
                            <input type="number" name="target_chilometri" id="target_chilometri" class="form-control" required>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-bullseye me-2"></i> Salva Target</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>