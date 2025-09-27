<?php
// --- Configurazione pagina ---
$page_title = "Gestione Utenti";
$page_description = "Visualizza e gestisci tutti gli utenti del sistema";
$require_auth = true;
$require_config = true;

include_once 'config.php';
include 'dati_utente.php';

// Verifica che l'utente sia loggato
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'query/qutenti.php';

// Recupera i dati dell'utente loggato
$username_loggato = $_SESSION['username'];
$utente_loggato_data = get_user_data($conn, $username_loggato);

if (!$utente_loggato_data) {
    error_log("Utente loggato '$username_loggato' non trovato nel database.");
    session_destroy();
    header("Location: login.php");
    exit();
}

// Assegna i dati dell'utente per il menu
$utente_data = $utente_loggato_data;
$livello_utente_loggato = $utente_data['livello'];

// Query per recuperare gli utenti in base al livello di autorizzazione
if ($livello_utente_loggato < 3) {
    // Livello inferiore a 3: visualizza tutti gli utenti
    $sql = "SELECT id, username, password, targa_mezzo, divisione, filiale, livello, Nome, Cognome, time_stamp FROM utenti ORDER BY Nome, Cognome";
    $result = $conn->query($sql);
} else {
    // Livello 3 o superiore: visualizza solo l'utente corrente
    $sql = "SELECT id, username, password, targa_mezzo, divisione, filiale, livello, Nome, Cognome, time_stamp FROM utenti WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username_loggato);
    $stmt->execute();
    $result = $stmt->get_result();
}

// Include header
include 'template/header.php';
?>

    <!-- Contenuto principale -->
    <main class="container" id="main-content">
        <div class="row">
            <div class="col-12">
                <!-- Header pagina -->
                <div class="card slide-in mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="mb-0 h4">
                                <i class="bi bi-people-fill me-2"></i>Gestione Utenti
                            </h1>
                            <small class="text-light">Visualizza e gestisci gli utenti del sistema</small>
                        </div>
                        <?php if ($livello_utente_loggato < 3): ?>
                        <a href="registrazione.php" class="btn btn-primary">
                            <i class="bi bi-person-plus me-2"></i>Nuovo Utente
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Statistiche utenti -->
                <?php if ($result && $result->num_rows > 0): ?>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card slide-in text-center">
                            <div class="card-body">
                                <div class="display-6 text-primary mb-2">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <h5 class="card-title"><?php echo $result->num_rows; ?></h5>
                                <p class="card-text small text-muted">Utenti Totali</p>
                            </div>
                        </div>
                    </div>
                    <?php 
                    // Reset del result per contare le statistiche
                    if (isset($stmt)) {
                        $stmt->execute();
                        $result = $stmt->get_result();
                    } else {
                        $result = $conn->query($sql);
                    }
                    
                    $livelli = [1 => 0, 2 => 0, 3 => 0];
                    $temp_data = [];
                    while ($row = $result->fetch_assoc()) {
                        $temp_data[] = $row;
                        if (isset($livelli[$row['livello']])) {
                            $livelli[$row['livello']]++;
                        }
                    }
                    ?>
                    <div class="col-md-3">
                        <div class="card slide-in text-center">
                            <div class="card-body">
                                <div class="display-6 text-danger mb-2">
                                    <i class="bi bi-shield-fill-exclamation"></i>
                                </div>
                                <h5 class="card-title"><?php echo $livelli[1]; ?></h5>
                                <p class="card-text small text-muted">Amministratori</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card slide-in text-center">
                            <div class="card-body">
                                <div class="display-6 text-warning mb-2">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <h5 class="card-title"><?php echo $livelli[2]; ?></h5>
                                <p class="card-text small text-muted">Manager</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card slide-in text-center">
                            <div class="card-body">
                                <div class="display-6 text-info mb-2">
                                    <i class="bi bi-person"></i>
                                </div>
                                <h5 class="card-title"><?php echo $livelli[3]; ?></h5>
                                <p class="card-text small text-muted">Utenti Standard</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabella utenti -->
                <div class="card slide-in">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-table me-2"></i>Elenco Utenti
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center d-none d-md-table-cell">ID</th>
                                        <th><i class="bi bi-person me-1"></i>Nome Completo</th>
                                        <th class="d-none d-lg-table-cell"><i class="bi bi-at me-1"></i>Username</th>
                                        <th><i class="bi bi-car-front me-1"></i>Targa</th>
                                        <th class="d-none d-xl-table-cell"><i class="bi bi-building me-1"></i>Divisione</th>
                                        <th class="d-none d-xl-table-cell"><i class="bi bi-geo-alt me-1"></i>Filiale</th>
                                        <th class="text-center"><i class="bi bi-shield me-1"></i>Livello</th>
                                        <th class="d-none d-md-table-cell text-center"><i class="bi bi-calendar me-1"></i>Registrato</th>
                                        <?php if ($livello_utente_loggato < 3): ?>
                                        <th class="text-center">Azioni</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($temp_data as $row) {
                                        $badge_class = '';
                                        $livello_text = '';
                                        switch ($row['livello']) {
                                            case 1:
                                                $badge_class = 'bg-danger';
                                                $livello_text = 'Admin';
                                                break;
                                            case 2:
                                                $badge_class = 'bg-warning text-dark';
                                                $livello_text = 'Manager';
                                                break;
                                            case 3:
                                                $badge_class = 'bg-info';
                                                $livello_text = 'Utente';
                                                break;
                                            default:
                                                $badge_class = 'bg-secondary';
                                                $livello_text = 'Livello ' . $row['livello'];
                                        }
                                        
                                        echo "<tr>";
                                        echo "<td class='text-center d-none d-md-table-cell'><span class='badge bg-light text-dark'>" . htmlspecialchars($row['id']) . "</span></td>";
                                        echo "<td>";
                                        echo "<div class='fw-semibold'>" . htmlspecialchars($row['Nome'] . ' ' . $row['Cognome']) . "</div>";
                                        echo "<div class='small text-muted d-lg-none'>@" . htmlspecialchars($row['username']) . "</div>";
                                        echo "</td>";
                                        echo "<td class='d-none d-lg-table-cell'><code>" . htmlspecialchars($row['username']) . "</code></td>";
                                        echo "<td><span class='badge bg-primary'>" . htmlspecialchars($row['targa_mezzo']) . "</span></td>";
                                        echo "<td class='d-none d-xl-table-cell'>" . htmlspecialchars($row['divisione']) . "</td>";
                                        echo "<td class='d-none d-xl-table-cell'>" . htmlspecialchars($row['filiale']) . "</td>";
                                        echo "<td class='text-center'><span class='badge {$badge_class}'>{$livello_text}</span></td>";
                                        echo "<td class='text-center d-none d-md-table-cell'><small>" . date('d/m/Y', strtotime($row['time_stamp'])) . "</small></td>";
                                        
                                        if ($livello_utente_loggato < 3) {
                                            echo "<td class='text-center'>";
                                            echo "<div class='btn-group btn-group-sm' role='group'>";
                                            echo "<a href='modifica_utente.php?id=" . $row['id'] . "' class='btn btn-outline-primary btn-sm' title='Modifica'>";
                                            echo "<i class='bi bi-pencil'></i>";
                                            echo "</a>";
                                            if ($row['username'] !== $username_loggato) {
                                                echo "<a href='cancella_utente.php?id=" . $row['id'] . "' class='btn btn-outline-danger btn-sm' title='Elimina' onclick='return confirm(\"Sei sicuro di voler eliminare questo utente?\")'>";
                                                echo "<i class='bi bi-trash'></i>";
                                                echo "</a>";
                                            }
                                            echo "</div>";
                                            echo "</td>";
                                        }
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <!-- Stato vuoto -->
                <div class="card slide-in">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-people display-1 text-muted mb-3"></i>
                        <h4 class="text-muted">Nessun utente trovato</h4>
                        <p class="text-muted">Non ci sono utenti da visualizzare con i tuoi permessi attuali.</p>
                        <?php if ($livello_utente_loggato < 3): ?>
                        <a href="registrazione.php" class="btn btn-primary">
                            <i class="bi bi-person-plus me-2"></i>Registra Primo Utente
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Azioni rapide -->
                <?php if ($livello_utente_loggato < 3): ?>
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card slide-in">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    <i class="bi bi-person-plus text-primary me-2"></i>Nuovo Utente
                                </h5>
                                <p class="card-text small text-muted">Registra un nuovo utente nel sistema</p>
                                <a href="registrazione.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Registra
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card slide-in">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    <i class="bi bi-person-gear text-warning me-2"></i>Modifica Utente
                                </h5>
                                <p class="card-text small text-muted">Aggiorna le informazioni di un utente</p>
                                <a href="modifica_utente.php" class="btn btn-warning">
                                    <i class="bi bi-pencil-square me-2"></i>Modifica
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card slide-in">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    <i class="bi bi-person-x text-danger me-2"></i>Gestisci Accessi
                                </h5>
                                <p class="card-text small text-muted">Gestisci permessi e autorizzazioni</p>
                                <a href="aggiorna_utente.php" class="btn btn-danger">
                                    <i class="bi bi-shield-exclamation me-2"></i>Gestisci
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

<?php 
// Include footer
include 'template/footer.php';
?>



