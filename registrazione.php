<?php
// --- Configurazione pagina ---
$page_title = "Registrazione Utente";
$page_description = "Registra un nuovo utente nel sistema";
$require_auth = true;
$require_config = true;

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include_once 'config.php';
include 'query/qutenti.php';

// Recupera i dati dell'utente per ottenere il livello
$utente_data = get_user_data($conn, $_SESSION['username']);

// Verifica se l'utente è autorizzato (livello inferiore a 3)
if (!$utente_data || $utente_data['livello'] >= 3) {
    header("Location: unauthorized.php");
    exit();
}

// Recupera le divisioni
$sql_divisioni = "SELECT DISTINCT divisione FROM filiali";
$result_divisioni = $conn->query($sql_divisioni);
$divisioni = [];
if ($result_divisioni->num_rows > 0) {
    while ($row = $result_divisioni->fetch_assoc()) {
        $divisioni[] = $row['divisione'];
    }
}

// Recupera i livelli di autorizzazione
$sql_autorizzazioni = "SELECT descrizione_livello, livello FROM livelli_autorizzazione";
$result_autorizzazioni = $conn->query($sql_autorizzazioni);
$livelli_autorizzazione = [];
if ($result_autorizzazioni->num_rows > 0) {
    while ($row = $result_autorizzazioni->fetch_assoc()) {
        $livelli_autorizzazione[] = $row;
    }
}

$conn->close();

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
                            <i class="bi bi-person-plus-fill me-2"></i>Registrazione Nuovo Utente
                        </h1>
                        <small class="text-light">Compila tutti i campi per registrare un nuovo utente</small>
                    </div>
                </div>

                <!-- Form di registrazione -->
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-xl-6">
                        <div class="card slide-in">
                            <div class="card-body p-4">
                                <form action="registra_utente.php" method="post" id="registrationForm" novalidate>
                                    <div class="row">
                                        <!-- Nome -->
                                        <div class="col-md-6 mb-3">
                                            <label for="nome" class="form-label">
                                                <i class="bi bi-person me-1"></i>Nome *
                                            </label>
                                            <input 
                                                type="text" 
                                                name="nome" 
                                                id="nome" 
                                                class="form-control"
                                                placeholder="Inserisci il nome"
                                                required
                                            >
                                            <div class="invalid-feedback">
                                                Il nome è obbligatorio.
                                            </div>
                                        </div>

                                        <!-- Cognome -->
                                        <div class="col-md-6 mb-3">
                                            <label for="cognome" class="form-label">
                                                <i class="bi bi-person me-1"></i>Cognome *
                                            </label>
                                            <input 
                                                type="text" 
                                                name="cognome" 
                                                id="cognome" 
                                                class="form-control"
                                                placeholder="Inserisci il cognome"
                                                required
                                            >
                                            <div class="invalid-feedback">
                                                Il cognome è obbligatorio.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- Targa Mezzo -->
                                        <div class="col-md-6 mb-3">
                                            <label for="targa_mezzo" class="form-label">
                                                <i class="bi bi-car-front me-1"></i>Targa Mezzo *
                                            </label>
                                            <input 
                                                type="text" 
                                                name="targa_mezzo" 
                                                id="targa_mezzo" 
                                                class="form-control text-uppercase"
                                                placeholder="es. AA123BB"
                                                pattern="[A-Z]{2}[0-9]{3}[A-Z]{2}"
                                                title="Formato targa: 2 lettere + 3 numeri + 2 lettere"
                                                required
                                            >
                                            <div class="invalid-feedback">
                                                Inserisci una targa valida (es. AA123BB).
                                            </div>
                                        </div>

                                        <!-- Costo -->
                                        <div class="col-md-6 mb-3">
                                            <label for="costo" class="form-label">
                                                <i class="bi bi-currency-euro me-1"></i>Costo Extra (€/km)
                                            </label>
                                            <div class="input-group">
                                                <input 
                                                    type="number" 
                                                    name="costo" 
                                                    id="costo" 
                                                    step="0.01" 
                                                    min="0"
                                                    class="form-control"
                                                    placeholder="0.00"
                                                >
                                                <span class="input-group-text">€</span>
                                            </div>
                                            <div class="form-text">Costo per chilometro in esubero (opzionale)</div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- Divisione -->
                                        <div class="col-md-6 mb-3">
                                            <label for="divisione" class="form-label">
                                                <i class="bi bi-building me-1"></i>Divisione *
                                            </label>
                                            <select name="divisione" id="divisione" class="form-select" required>
                                                <option value="">Seleziona Divisione</option>
                                                <?php foreach ($divisioni as $divisione) : ?>
                                                    <option value="<?php echo htmlspecialchars($divisione); ?>">
                                                        <?php echo htmlspecialchars($divisione); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="invalid-feedback">
                                                Seleziona una divisione.
                                            </div>
                                        </div>

                                        <!-- Filiale -->
                                        <div class="col-md-6 mb-3">
                                            <label for="filiale" class="form-label">
                                                <i class="bi bi-geo-alt me-1"></i>Filiale
                                            </label>
                                            <div class="form-control bg-light" id="filiale_display">
                                                Seleziona una divisione
                                            </div>
                                            <input type="hidden" name="filiale" id="filiale" value="">
                                            <div class="form-text">La filiale verrà selezionata automaticamente</div>
                                        </div>
                                    </div>

                                    <!-- Livello di autorizzazione -->
                                    <div class="mb-3">
                                        <label for="livello" class="form-label">
                                            <i class="bi bi-shield-check me-1"></i>Livello di Autorizzazione *
                                        </label>
                                        <select name="livello" id="livello" class="form-select" required>
                                            <option value="">Seleziona Autorizzazione</option>
                                            <?php foreach ($livelli_autorizzazione as $livello_data) : ?>
                                                <option value="<?php echo htmlspecialchars($livello_data['livello']); ?>">
                                                    <?php echo htmlspecialchars($livello_data['descrizione_livello']) . ' (Livello ' . htmlspecialchars($livello_data['livello']) . ')'; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">
                                            Seleziona un livello di autorizzazione.
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- Username -->
                                        <div class="col-md-6 mb-3">
                                            <label for="username" class="form-label">
                                                <i class="bi bi-at me-1"></i>Username *
                                            </label>
                                            <input 
                                                type="text" 
                                                name="username" 
                                                id="username" 
                                                class="form-control"
                                                placeholder="username (minuscolo)"
                                                pattern="[a-z0-9._]{3,20}"
                                                title="3-20 caratteri: lettere minuscole, numeri, punto e underscore"
                                                required
                                            >
                                            <div class="invalid-feedback">
                                                Username non valido (3-20 caratteri, minuscole, numeri, . e _).
                                            </div>
                                        </div>

                                        <!-- Password -->
                                        <div class="col-md-6 mb-3">
                                            <label for="password" class="form-label">
                                                <i class="bi bi-lock me-1"></i>Password *
                                            </label>
                                            <div class="input-group">
                                                <input 
                                                    type="password" 
                                                    name="password" 
                                                    id="password" 
                                                    class="form-control"
                                                    placeholder="Inserisci password sicura"
                                                    minlength="6"
                                                    required
                                                >
                                                <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Mostra/Nascondi password">
                                                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                                </button>
                                            </div>
                                            <div class="invalid-feedback">
                                                La password deve avere almeno 6 caratteri.
                                            </div>
                                            <div class="form-text">Minimo 6 caratteri</div>
                                        </div>
                                    </div>

                                    <!-- Pulsanti -->
                                    <div class="d-flex gap-3 justify-content-center mt-4">
                                        <button type="submit" class="btn btn-primary" id="submitBtn">
                                            <i class="bi bi-person-plus-fill me-2"></i>
                                            <span class="btn-text">Registra Utente</span>
                                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                        </button>
                                        <a href="gestisci_utenti.php" class="btn btn-secondary">
                                            <i class="bi bi-arrow-left me-2"></i>Torna alla Lista
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registrationForm');
            const targaMezzoField = document.getElementById('targa_mezzo');
            const usernameField = document.getElementById('username');
            const togglePassword = document.getElementById('togglePassword');
            const passwordField = document.getElementById('password');
            const togglePasswordIcon = document.getElementById('togglePasswordIcon');
            const divisioneSelect = document.getElementById('divisione');
            const filialeDisplay = document.getElementById('filiale_display');
            const filialeInputHidden = document.getElementById('filiale');
            const submitBtn = document.getElementById('submitBtn');

            // Converte la targa in maiuscolo
            if (targaMezzoField) {
                targaMezzoField.addEventListener('input', function() {
                    const cursorPosition = this.selectionStart;
                    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                    this.setSelectionRange(cursorPosition, cursorPosition);
                });
            }

            // Converte l'username in minuscolo
            if (usernameField) {
                usernameField.addEventListener('input', function() {
                    const cursorPosition = this.selectionStart;
                    this.value = this.value.toLowerCase().replace(/[^a-z0-9._]/g, '');
                    this.setSelectionRange(cursorPosition, cursorPosition);
                });
            }

            // Toggle password visibility
            if (togglePassword && passwordField && togglePasswordIcon) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);
                    
                    togglePasswordIcon.classList.toggle('bi-eye');
                    togglePasswordIcon.classList.toggle('bi-eye-slash');
                });
            }

            // Gestione filiali
            if (divisioneSelect) {
                divisioneSelect.addEventListener('change', function() {
                    const selectedDivisione = this.value;
                    filialeDisplay.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Caricamento filiale...';
                    filialeInputHidden.value = '';

                    if (selectedDivisione) {
                        fetch('get_filiali.php?divisione=' + encodeURIComponent(selectedDivisione))
                            .then(response => response.json())
                            .then(data => {
                                if (data && data.length > 0 && data[0].nome_divisione) {
                                    const filialeNome = data[0].nome_divisione;
                                    filialeDisplay.innerHTML = '<i class="bi bi-geo-alt text-success me-2"></i>' + filialeNome;
                                    filialeInputHidden.value = filialeNome;
                                } else {
                                    filialeDisplay.innerHTML = '<i class="bi bi-exclamation-circle text-warning me-2"></i>Nessuna filiale trovata';
                                }
                            })
                            .catch(error => {
                                console.error('Errore nel recupero della filiale:', error);
                                filialeDisplay.innerHTML = '<i class="bi bi-x-circle text-danger me-2"></i>Errore nel caricamento della filiale';
                            });
                    } else {
                        filialeDisplay.innerHTML = 'Seleziona una divisione';
                    }
                });
            }

            // Validazione form
            if (form && submitBtn) {
                form.addEventListener('submit', function(e) {
                    if (!this.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        // Mostra toast di errore
                        app.showToast('Per favore correggi gli errori nel form', 'error');
                    } else {
                        // Mostra stato di caricamento
                        const btnText = submitBtn.querySelector('.btn-text');
                        const spinner = submitBtn.querySelector('.spinner-border');
                        
                        if (btnText && spinner) {
                            btnText.textContent = 'Registrazione in corso...';
                            spinner.classList.remove('d-none');
                            submitBtn.disabled = true;
                        }
                    }
                    
                    this.classList.add('was-validated');
                });
            }
        });
    </script>

<?php 
// Include footer
include 'template/footer.php';
?>