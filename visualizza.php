<?php
// --- Configurazione pagina ---
$page_title = "Visualizza Registrazioni";
$page_description = "Elenco completo delle registrazioni chilometri";
$require_auth = true;
$require_config = true;

// --- Inizio Blocco Sicurezza e Dati ---
include_once 'config.php';
include 'dati_utente.php';

// Verifica che l'utente sia loggato
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Includi file di query
include 'query/qutenti.php';
include 'query/q_costo_extra.php';

// Recupera i dati dell'utente dal database
$utente_data = get_user_data($conn, $username);
$livello = $utente_data['livello'];
$divisione = $utente_data['divisione'];

// Recupera i dati dell'utente LOGGATO dalla sessione o dal DB
$username_loggato = $_SESSION['username'];
$dati_utente = [];
$sql_user = $conn->prepare("SELECT Nome, Cognome, targa_mezzo, divisione, filiale FROM utenti WHERE username = ? LIMIT 1");
if ($sql_user) {
    $sql_user->bind_param("s", $username_loggato);
    $sql_user->execute();
    $result_user = $sql_user->get_result();
    if ($result_user->num_rows > 0) {
        $dati_utente = $result_user->fetch_assoc();
        $dati_utente['username'] = $username_loggato;
    } else {
        error_log("Utente loggato '$username_loggato' non trovato nel database.");
        $dati_utente['username'] = $username_loggato;
        $dati_utente['Nome'] = 'N/D';
        $dati_utente['Cognome'] = '';
        $dati_utente['targa_mezzo'] = 'N/D';
        $dati_utente['divisione'] = 'N/D';
        $dati_utente['filiale'] = 'N/D';
    }
    $sql_user->close();
} else {
    error_log("Errore preparazione query dati utente: " . $conn->error);
    die("Errore nel recupero dati utente.");
}

// --- Blocco Paginazione ---
$limite = 20;
$pagina_corrente = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_corrente - 1) * $limite;

// Conta totale record
$sql_conta = $conn->prepare("SELECT COUNT(*) as totale FROM chilometri WHERE username = ?");
$totale_record = 0;
if ($sql_conta) {
    $sql_conta->bind_param("s", $dati_utente['username']);
    $sql_conta->execute();
    $result_conta = $sql_conta->get_result();
    $row_conta = $result_conta->fetch_assoc();
    $totale_record = $row_conta['totale'];
    $sql_conta->close();
}
$totale_pagine = ($limite > 0) ? ceil($totale_record / $limite) : 0;

// Recupera record per la pagina corrente
$sql_records = $conn->prepare("SELECT * FROM chilometri WHERE username = ? ORDER BY data DESC, id DESC LIMIT ? OFFSET ?");
$records = [];
if ($sql_records) {
    $sql_records->bind_param("sii", $dati_utente['username'], $limite, $offset);
    $sql_records->execute();
    $result_records = $sql_records->get_result();
    while ($row = $result_records->fetch_assoc()) {
        $records[] = $row;
    }
    $sql_records->close();
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
                    <div class="card-header">
                        <h1 class="mb-0 h4">
                            <i class="bi bi-table me-2"></i>Elenco Registrazioni Chilometri
                        </h1>
                        <small class="text-light">
                            Record totali: <?php echo number_format($totale_record, 0, ',', '.'); ?> - 
                            Pagina <?php echo $pagina_corrente; ?> di <?php echo $totale_pagine; ?>
                        </small>
                    </div>
                </div>

                <!-- Messaggi -->
                <?php if (isset($_GET['messaggio'])): ?>
                    <div class="alert alert-success alert-dismissible fade show slide-in" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo htmlspecialchars($_GET['messaggio']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['errore'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show slide-in" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($_GET['errore']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Tabella registrazioni -->
                <div class="card slide-in">
                    <div class="card-body p-0">
                        <?php if (empty($records)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox display-1 text-muted d-block mb-3"></i>
                                <h5 class="text-muted">Nessuna registrazione trovata</h5>
                                <p class="text-muted">Inizia inserendo il tuo primo rifornimento!</p>
                                <a href="index.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Inserisci Rifornimento
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th><i class="bi bi-calendar3 me-1"></i>Data</th>
                                            <th class="d-none d-md-table-cell"><i class="bi bi-car-front me-1"></i>Targa</th>
                                            <th class="d-none d-lg-table-cell"><i class="bi bi-building me-1"></i>Divisione</th>
                                            <th class="d-none d-lg-table-cell"><i class="bi bi-geo-alt me-1"></i>Filiale</th>
                                            <th class="text-end"><i class="bi bi-speedometer me-1"></i>Km Iniz.</th>
                                            <th class="text-end"><i class="bi bi-speedometer2 me-1"></i>Km Fin.</th>
                                            <th class="text-end d-none d-sm-table-cell"><i class="bi bi-droplet-fill me-1"></i>Litri</th>
                                            <th class="text-end"><i class="bi bi-currency-euro me-1"></i>Euro</th>
                                            <th class="text-center"><i class="bi bi-image me-1"></i>Foto</th>
                                            <th class="text-center">Azioni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($records as $record): 
                                            $km_percorsi = $record['chilometri_finali'] - $record['chilometri_iniziali'];
                                            $consumo = $record['litri_carburante'] > 0 && $km_percorsi > 0 ? 
                                                      ($record['litri_carburante'] / $km_percorsi * 100) : 0;
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo date("d/m/y", strtotime($record["data"])); ?>
                                                </span>
                                                <div class="small text-muted d-md-none">
                                                    <?php echo htmlspecialchars($record["targa_mezzo"]); ?>
                                                </div>
                                            </td>
                                            <td class="d-none d-md-table-cell fw-semibold">
                                                <?php echo htmlspecialchars($record["targa_mezzo"]); ?>
                                            </td>
                                            <td class="d-none d-lg-table-cell">
                                                <?php echo htmlspecialchars($record["divisione"]); ?>
                                            </td>
                                            <td class="d-none d-lg-table-cell">
                                                <?php echo htmlspecialchars($record["filiale"]); ?>
                                            </td>
                                            <td class="text-end fw-semibold">
                                                <?php echo number_format($record["chilometri_iniziali"], 0, ',', '.'); ?>
                                                <small class="text-muted d-block">km</small>
                                            </td>
                                            <td class="text-end fw-semibold">
                                                <?php echo number_format($record["chilometri_finali"], 0, ',', '.'); ?>
                                                <small class="text-muted d-block">km</small>
                                                <?php if ($km_percorsi > 0): ?>
                                                <small class="badge bg-secondary">+<?php echo number_format($km_percorsi, 0, ',', '.'); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end fw-semibold d-none d-sm-table-cell">
                                                <?php echo number_format($record["litri_carburante"], 2, ',', '.'); ?>
                                                <small class="text-muted d-block">L</small>
                                                <?php if ($consumo > 0): ?>
                                                <small class="badge <?php echo $consumo > 10 ? 'bg-danger' : ($consumo > 8 ? 'bg-warning text-dark' : 'bg-success'); ?>">
                                                    <?php echo number_format($consumo, 1, ',', '.'); ?> L/100km
                                                </small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end fw-semibold text-success">
                                                <?php echo number_format($record["euro_spesi"], 2, ',', '.'); ?> €
                                                <div class="small text-muted d-sm-none">
                                                    <?php echo number_format($record["litri_carburante"], 1, ',', '.'); ?> L
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <?php if (!empty($record["percorso_cedolino"]) && file_exists($record["percorso_cedolino"])): ?>
                                                    <a href="<?php echo htmlspecialchars($record["percorso_cedolino"]); ?>" 
                                                       target="_blank" 
                                                       title="Visualizza Cedolino" 
                                                       data-cedolino-url="<?php echo htmlspecialchars($record["percorso_cedolino"]); ?>" 
                                                       class="cedolino-preview-link btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted" title="Foto non disponibile">
                                                        <i class="bi bi-image"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="modifica.php?id=<?php echo $record['id']; ?>" 
                                                       class="btn btn-outline-warning btn-sm" 
                                                       title="Modifica">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="cancella.php?id=<?php echo $record['id']; ?>" 
                                                       class="btn btn-outline-danger btn-sm" 
                                                       title="Cancella"
                                                       onclick="return confirm('Sei sicuro di voler cancellare questa registrazione?')">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginazione -->
                            <?php if ($totale_pagine > 1): ?>
                            <div class="card-footer">
                                <nav aria-label="Navigazione pagine">
                                    <ul class="pagination justify-content-center mb-0">
                                        <?php if ($pagina_corrente > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?pagina=1" aria-label="Prima pagina">
                                                <i class="bi bi-chevron-double-left"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?pagina=<?php echo $pagina_corrente - 1; ?>" aria-label="Precedente">
                                                <i class="bi bi-chevron-left"></i>
                                            </a>
                                        </li>
                                        <?php endif; ?>

                                        <?php
                                        $inizio = max(1, $pagina_corrente - 2);
                                        $fine = min($totale_pagine, $pagina_corrente + 2);
                                        
                                        for ($i = $inizio; $i <= $fine; $i++): ?>
                                        <li class="page-item <?php echo $i == $pagina_corrente ? 'active' : ''; ?>">
                                            <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                        <?php endfor; ?>

                                        <?php if ($pagina_corrente < $totale_pagine): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?pagina=<?php echo $pagina_corrente + 1; ?>" aria-label="Successivo">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?pagina=<?php echo $totale_pagine; ?>" aria-label="Ultima pagina">
                                                <i class="bi bi-chevron-double-right"></i>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Azioni rapide -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card slide-in">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    <i class="bi bi-plus-circle text-primary me-2"></i>Nuovo Rifornimento
                                </h5>
                                <p class="card-text small text-muted">Inserisci una nuova registrazione</p>
                                <a href="index.php" class="btn btn-primary">
                                    <i class="bi bi-fuel-pump-fill me-2"></i>Inserisci
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card slide-in">
                            <div class="card-body text-center">
                                <h5 class="card-title">
                                    <i class="bi bi-bar-chart-line text-success me-2"></i>Report Mensile
                                </h5>
                                <p class="card-text small text-muted">Visualizza statistiche del mese</p>
                                <a href="report_mese.php" class="btn btn-success">
                                    <i class="bi bi-graph-up me-2"></i>Visualizza
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

<?php 
// Script aggiuntivo per anteprima cedolini
$additional_scripts = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    const previewContainer = document.getElementById("image-preview-container");
    if (!previewContainer) return;
    
    const previewImage = previewContainer.querySelector("img");
    const cedolinoLinks = document.querySelectorAll(".cedolino-preview-link");

    cedolinoLinks.forEach(link => {
        link.addEventListener("mouseover", (event) => {
            const imageUrl = link.dataset.cedolinoUrl;
            if (imageUrl) {
                previewImage.src = imageUrl;
                previewContainer.style.display = "block";
                previewContainer.style.left = (event.pageX + 10) + "px";
                previewContainer.style.top = (event.pageY + 10) + "px";
            }
        });

        link.addEventListener("mouseout", () => {
            previewContainer.style.display = "none";
        });
    });

    previewContainer.addEventListener("mouseleave", () => {
        previewContainer.style.display = "none";
    });
});
</script>';

// Include footer
include 'template/footer.php';
?>