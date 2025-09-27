<?php
session_start();

// Verifica che l'utente sia loggato
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include_once 'config.php';
include 'query/qutenti.php'; // Includi il file delle query

$username = $_SESSION['username'];

// Recupera i dati dell'utente
$utente_data = get_user_data($conn, $username);

// Recupera l'ultimo valore di chilometri finali
$ultimo_chilometri_finali = 0;
$sql_chilometri = $conn->prepare("SELECT chilometri_finali FROM chilometri WHERE username = ? ORDER BY id DESC LIMIT 1");
if ($sql_chilometri) { // Aggiunto controllo sulla preparazione
    $sql_chilometri->bind_param("s", $username);
    $sql_chilometri->execute();
    $result_chilometri = $sql_chilometri->get_result();

    if ($result_chilometri->num_rows > 0) {
        $row_chilometri = $result_chilometri->fetch_assoc();
        $ultimo_chilometri_finali = $row_chilometri['chilometri_finali'];
    }
    $sql_chilometri->close();
} else {
    // Gestire l'errore di preparazione, se necessario
    error_log("Errore nella preparazione della query SQL per ultimi chilometri: " . $conn->error);
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserimento Chilometri - Mobile Optimized</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/app.css">
    <style>
        /* Stile specifico per il messaggio di errore validazione chilometri */
        .error-message {
            color: var(--danger-color, #dc3545);
            font-size: 0.875em;
            margin-top: -0.5rem;
            margin-bottom: 1rem;
            display: none;
            font-weight: 500;
        }
        .form-control.is-invalid + .error-message {
            display: block;
        }
    </style>
</head>
<body>

    <div class="fixed-top-elements">
        <button class="btn btn-outline-secondary menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMenu" aria-controls="mainMenu">
            ☰ Menu
        </button>
        <div class="username-display">
            <?php echo "Utente: " . htmlspecialchars($_SESSION['username']); ?>
        </div>
    </div>

    <?php include 'include/menu.php'; ?>

    <div class="container" id="main-content">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <!-- Card principale del form -->
                <div class="card form-container slide-in">
                    <div class="card-header">
                        <h1 class="mb-0 h4"><i class="bi bi-fuel-pump-fill me-2"></i>Inserimento Chilometri</h1>
                    </div>
                    <div class="card-body">
                        <form method="post" action="inserisci.php" onsubmit="return validateKilometers()" enctype="multipart/form-data" id="inserimentoForm">
                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                            <input type="hidden" name="targa_mezzo" value="<?php echo htmlspecialchars($utente_data['targa_mezzo'] ?? ''); ?>">
                            <input type="hidden" name="divisione" value="<?php echo htmlspecialchars($utente_data['divisione'] ?? ''); ?>">
                            <input type="hidden" name="filiale" value="<?php echo htmlspecialchars($utente_data['filiale'] ?? ''); ?>">
                            <input type="hidden" name="livello" value="<?php echo htmlspecialchars($utente_data['livello'] ?? ''); ?>">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="data" class="form-label"><i class="bi bi-calendar3 me-1"></i>Data:</label>
                                    <input type="date" class="form-control" name="data" id="data" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="chilometri_iniziali" class="form-label"><i class="bi bi-speedometer me-1"></i>Chilometri Iniziali:</label>
                                    <input type="number" class="form-control" name="chilometri_iniziali" id="chilometri_iniziali" value="<?php echo $ultimo_chilometri_finali; ?>" required inputmode="numeric" min="0">
                                    <div class="invalid-feedback">Inserire un valore numerico valido.</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="chilometri_finali" class="form-label"><i class="bi bi-speedometer2 me-1"></i>Chilometri Finali:</label>
                                    <input type="number" class="form-control" name="chilometri_finali" id="chilometri_finali" required inputmode="numeric" min="0">
                                    <div id="kilometers-error" class="error-message"></div>
                                    <div class="invalid-feedback">Inserire un valore numerico valido.</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="litri_carburante" class="form-label"><i class="bi bi-droplet-fill me-1"></i>Litri Carburante:</label>
                                    <input type="number" class="form-control" name="litri_carburante" id="litri_carburante" step="0.01" required inputmode="decimal" min="0">
                                    <div class="invalid-feedback">Inserire un valore numerico (es. 50.25).</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="euro_spesi" class="form-label"><i class="bi bi-currency-euro me-1"></i>Euro Spesi:</label>
                                    <input type="number" class="form-control" name="euro_spesi" id="euro_spesi" step="0.01" required inputmode="decimal" min="0">
                                    <div class="invalid-feedback">Inserire un valore numerico (es. 75.50).</div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="cedolino" class="form-label"><i class="bi bi-camera-fill me-1"></i>Foto Cedolino (Opzionale):</label>
                                <input type="file" class="form-control" name="cedolino" id="cedolino" accept="image/*" capture="environment">
                                <div class="form-text"><i class="bi bi-info-circle me-1"></i>Scatta una foto della ricevuta con la fotocamera del dispositivo.</div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-plus-circle-fill me-2"></i>Inserisci Dati
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabella ultima registrazione -->
        <div class="row mt-4">
            <div class="col-lg-10 mx-auto">
                <div class="card slide-in">
                    <div class="card-header">
                        <h2 class="mb-0 h5"><i class="bi bi-clock-history me-2"></i>Ultima Registrazione Inserita</h2>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th><i class="bi bi-calendar3 me-1"></i>Data</th>
                                        <th class="text-end"><i class="bi bi-speedometer me-1"></i>Km Iniz.</th>
                                        <th class="text-end"><i class="bi bi-speedometer2 me-1"></i>Km Fin.</th>
                                        <th class="text-end"><i class="bi bi-droplet-fill me-1"></i>Litri</th>
                                        <th class="text-end"><i class="bi bi-currency-euro me-1"></i>Euro</th>
                                        <th class="text-center"><i class="bi bi-image me-1"></i>Cedolino</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                // Query per l'ultima registrazione (invariata)
                                $sql_ultima_registrazione = $conn->prepare("SELECT data, chilometri_iniziali, chilometri_finali, litri_carburante, euro_spesi, percorso_cedolino FROM chilometri WHERE username = ? ORDER BY id DESC LIMIT 1");
                                 if ($sql_ultima_registrazione) { // Aggiunto controllo
                                    $sql_ultima_registrazione->bind_param("s", $username);
                                    $sql_ultima_registrazione->execute();
                                    $result_ultima_registrazione = $sql_ultima_registrazione->get_result();

                                    if ($result_ultima_registrazione->num_rows > 0) {
                                        $row = $result_ultima_registrazione->fetch_assoc();
                                        echo "<tr>";
                                        echo "<td><span class='badge bg-primary'>" . htmlspecialchars(date("d/m/y", strtotime($row["data"]))) . "</span></td>";
                                        echo "<td class='text-end fw-semibold'>" . htmlspecialchars(number_format($row["chilometri_iniziali"], 0, ',', '.')) . " km</td>";
                                        echo "<td class='text-end fw-semibold'>" . htmlspecialchars(number_format($row["chilometri_finali"], 0, ',', '.')) . " km</td>";
                                        echo "<td class='text-end fw-semibold'>" . htmlspecialchars(number_format($row["litri_carburante"], 2, ',', '.')) . " L</td>";
                                        echo "<td class='text-end fw-semibold text-success'>" . htmlspecialchars(number_format($row["euro_spesi"], 2, ',', '.')) . " €</td>";
                                        echo "<td class='text-center'>";
                                        if (!empty($row["percorso_cedolino"]) && file_exists($row["percorso_cedolino"])) {
                                            echo "<a href='" . htmlspecialchars($row["percorso_cedolino"]) . "' target='_blank' title='Vedi Foto Cedolino' data-cedolino-url='" . htmlspecialchars($row["percorso_cedolino"]) . "' class='cedolino-preview-link btn btn-sm btn-outline-primary'>";
                                            echo "<i class='bi bi-eye-fill'></i>";
                                            echo "</a>";
                                        } else {
                                            echo "<span class='text-muted' title='Foto non disponibile'>";
                                            echo "<i class='bi bi-image'></i>";
                                            echo "</span>";
                                        }
                                        echo "</td>";
                                        echo "</tr>";
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center text-muted py-4'>";
                                        echo "<i class='bi bi-inbox display-4 d-block mb-2 text-muted'></i>";
                                        echo "<em>Nessuna registrazione trovata</em>";
                                        echo "</td></tr>";
                                    }
                                    $sql_ultima_registrazione->close();
                                 } else {
                                    echo "<tr><td colspan='6' class='text-center text-danger py-4'>";
                                    echo "<i class='bi bi-exclamation-triangle display-4 d-block mb-2'></i>";
                                    echo "Errore nel caricamento ultima registrazione";
                                    echo "</td></tr>";
                                    error_log("Errore nella preparazione della query SQL per ultima registrazione: " . $conn->error);
                                 }
                                 ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="image-preview-container">
            <img src="" alt="Anteprima Cedolino">
        </div>
    </div>    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script src="js/app.js"></script>
    <script>
        // Validazione specifica per i chilometri (mantiene la logica originale ma migliorata)
        function validateKilometers() {
            const initialKilometersInput = document.getElementById('chilometri_iniziali');
            const finalKilometersInput = document.getElementById('chilometri_finali');
            const errorDiv = document.getElementById('kilometers-error');
            const submitBtn = document.querySelector('button[type="submit"]');
            let isValid = true;

            // Pulisci errori precedenti
            errorDiv.textContent = '';
            errorDiv.style.display = 'none';
            errorDiv.className = 'error-message';
            initialKilometersInput.classList.remove('is-invalid', 'is-valid');
            finalKilometersInput.classList.remove('is-invalid', 'is-valid');

            // Validazione campi vuoti
            if (!initialKilometersInput.value.trim()) {
                initialKilometersInput.classList.add('is-invalid');
                isValid = false;
            }
            if (!finalKilometersInput.value.trim()) {
                finalKilometersInput.classList.add('is-invalid');
                isValid = false;
            }

            // Validazione numerica e logica
            if (isValid) {
                const initialKm = parseInt(initialKilometersInput.value, 10);
                const finalKm = parseInt(finalKilometersInput.value, 10);

                if (isNaN(initialKm)) {
                    initialKilometersInput.classList.add('is-invalid');
                    isValid = false;
                }
                if (isNaN(finalKm)) {
                    finalKilometersInput.classList.add('is-invalid');
                    isValid = false;
                }

                // Controllo logico chilometri
                if (isValid && finalKm < initialKm) {
                    errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>I Chilometri Finali non possono essere inferiori agli Iniziali.';
                    errorDiv.style.display = 'block';
                    finalKilometersInput.classList.add('is-invalid');
                    finalKilometersInput.focus();
                    isValid = false;
                }

                // Warning per differenze eccessive
                if (isValid && (finalKm - initialKm) > AppConfig.maxKmDifference) {
                    const kmDiff = AppUtils.formatNumber(finalKm - initialKm, 0);
                    if (!confirm(`Attenzione: hai inserito ${kmDiff} km di differenza. Confermi che sia corretto?`)) {
                        finalKilometersInput.focus();
                        return false;
                    }
                }
            }

            // Feedback positivo
            if (isValid) {
                initialKilometersInput.classList.add('is-valid');
                finalKilometersInput.classList.add('is-valid');
                
                errorDiv.innerHTML = '<i class="bi bi-check-circle me-2 text-success"></i>Dati validati correttamente!';
                errorDiv.className = 'text-success mt-2 small';
                errorDiv.style.display = 'block';
                
                AppUtils.showToast('Dati chilometri validati correttamente', 'success');
            } else {
                const firstInvalid = document.querySelector('#inserimentoForm .is-invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
                AppUtils.showToast('Correggere gli errori nel form', 'danger');
            }

            return isValid;
        }

        // Override the form validator for custom kilometers validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('inserimentoForm');
            if (form) {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    event.stopPropagation();

                    // Custom kilometers validation first
                    if (validateKilometers() && form.checkValidity()) {
                        const submitBtn = form.querySelector('button[type="submit"]');
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Invio in corso...';
                        submitBtn.disabled = true;
                        
                        setTimeout(() => {
                            form.submit();
                        }, 800);
                    }

                    form.classList.add('was-validated');
                }, false);

                // Clear errors on input
                ['chilometri_iniziali', 'chilometri_finali'].forEach(fieldId => {
                    document.getElementById(fieldId).addEventListener('input', function() {
                        const errorDiv = document.getElementById('kilometers-error');
                        if (errorDiv.style.display === 'block') {
                            errorDiv.style.display = 'none';
                            errorDiv.className = 'error-message';
                            
                            ['chilometri_iniziali', 'chilometri_finali'].forEach(id => {
                                document.getElementById(id).classList.remove('is-valid', 'is-invalid');
                            });
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) { // Controlla se $conn è un oggetto mysqli valido
    $conn->close(); // Chiudi la connessione al database
}
?>