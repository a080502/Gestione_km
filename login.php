<?php
// --- Configurazione pagina ---
$page_title = "Accesso";
$page_description = "Effettua l'accesso al sistema di gestione KM";
$require_auth = false;
$require_config = false;
$body_class = "login-page"; // Classe per il body per identificare la pagina di login

// Avvia la sessione PHP
session_start();

// Controlla se l'utente è già loggato, in tal caso reindirizza a index.php
if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Gestione del messaggio di errore
$errorMessage = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case '1':
            $errorMessage = 'Username o password non validi. Riprova.';
            break;
        case '2':
            $errorMessage = 'Si è verificato un errore imprevisto.';
            break;
        default:
            $errorMessage = 'Errore sconosciuto.';
    }
}

// Include header
include 'template/header.php';
?>

    <div class="container-fluid p-0">
        <div class="row min-vh-100 align-items-center justify-content-center g-0">
            <div class="col-12 col-sm-8 col-md-6 col-lg-4 col-xl-3">
                <div class="card shadow-lg border-0 slide-in">
                    <div class="card-body p-4 p-md-5">
                        <!-- Logo/Header -->
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <i class="bi bi-shield-lock display-4 text-primary"></i>
                            </div>
                            <h1 class="h3 mb-1">Accesso al Sistema</h1>
                            <p class="text-muted small">Inserisci le tue credenziali per continuare</p>
                        </div>

                        <!-- Messaggi di errore -->
                        <?php if (!empty($errorMessage)): ?>
                            <div class="alert alert-danger d-flex align-items-center fade-in" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <div><?php echo htmlspecialchars($errorMessage); ?></div>
                            </div>
                        <?php endif; ?>

                        <!-- Form di login -->
                        <form action="verifica_login.php" method="post" id="loginForm" novalidate>
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="bi bi-person me-1"></i>Username
                                </label>
                                <input 
                                    type="text" 
                                    name="username" 
                                    id="username" 
                                    class="form-control form-control-lg"
                                    placeholder="Inserisci il tuo username"
                                    required 
                                    autocomplete="username"
                                    value="<?php echo isset($_GET['username']) ? htmlspecialchars($_GET['username']) : ''; ?>"
                                >
                                <div class="invalid-feedback">
                                    Il campo username è obbligatorio.
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock me-1"></i>Password
                                </label>
                                <div class="input-group">
                                    <input 
                                        type="password" 
                                        name="password" 
                                        id="password" 
                                        class="form-control form-control-lg"
                                        placeholder="Inserisci la tua password"
                                        required
                                        autocomplete="current-password"
                                    >
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Mostra/Nascondi password">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Il campo password è obbligatorio.
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg" id="loginBtn">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    <span class="btn-text">Accedi</span>
                                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                </button>
                            </div>
                        </form>

                        <!-- Link di registrazione -->
                        <div class="text-center mt-4">
                            <hr class="my-3">
                            <p class="small text-muted mb-2">Non hai un account?</p>
                            <a href="registrazione.php" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-person-plus me-1"></i>Registrati
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gestione form di login
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            const togglePasswordButton = document.getElementById('togglePassword');
            const toggleIcon = document.getElementById('toggleIcon');
            const loginBtn = document.getElementById('loginBtn');

            // Conversione username in minuscolo
            if (usernameField) {
                usernameField.addEventListener('input', function() {
                    this.value = this.value.toLowerCase().trim();
                });
            }

            // Toggle password visibility
            if (togglePasswordButton && passwordField && toggleIcon) {
                togglePasswordButton.addEventListener('click', function() {
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);
                    
                    toggleIcon.classList.toggle('bi-eye');
                    toggleIcon.classList.toggle('bi-eye-slash');
                });
            }

            // Validazione form e stato di caricamento
            if (loginForm && loginBtn) {
                loginForm.addEventListener('submit', function(e) {
                    // Validazione Bootstrap
                    if (!this.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                    } else {
                        // Mostra stato di caricamento
                        const btnText = loginBtn.querySelector('.btn-text');
                        const spinner = loginBtn.querySelector('.spinner-border');
                        
                        if (btnText && spinner) {
                            btnText.textContent = 'Accesso in corso...';
                            spinner.classList.remove('d-none');
                            loginBtn.disabled = true;
                        }
                    }
                    
                    this.classList.add('was-validated');
                });
            }

            // Focus automatico su username
            if (usernameField && !usernameField.value) {
                usernameField.focus();
            } else if (passwordField && usernameField.value) {
                passwordField.focus();
            }
        });
    </script>

<?php 
// Include footer
include 'template/footer.php';
?>
