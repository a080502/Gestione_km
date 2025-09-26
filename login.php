<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Collegamento a Font Awesome per le icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Stili per la sidebar (invariati dalla tua versione) */
        .sidebar {
            height: 100%;
            width: 0;
            position: fixed;
            z-index: 1;
            top: 0;
            left: 0;
            background-color: #111;
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 60px;
        }
        .sidebar a {
            padding: 10px 15px;
            text-decoration: none;
            font-size: 18px;
            color: #ddd;
            display: block;
        }
        .sidebar a:hover {
            color: white;
        }
        .sidebar .closebtn {
            position: absolute;
            top: 0;
            right: 25px;
            font-size: 36px;
        }
        /* Stile per rendere il pulsante toggle password cliccabile */
        #togglePassword {
            cursor: pointer;
        }
        /* Stili aggiuntivi per migliorare l'aspetto */
        body {
            font-family: 'Inter', sans-serif; /* Utilizzo di un font moderno */
        }
        .card {
            border: none; /* Rimuove il bordo predefinito della card */
            box-shadow: 0 4px 12px rgba(0,0,0,0.15); /* Ombra più pronunciata */
            border-radius: 0.75rem; /* Angoli più arrotondati */
        }
        .form-control {
            border-radius: 0.5rem; /* Angoli arrotondati per gli input */
        }
        .btn-primary {
            border-radius: 0.5rem; /* Angoli arrotondati per il pulsante */
            padding: 0.75rem; /* Padding maggiore per il pulsante */
        }
        .input-group .form-control {
            /* Assicura che l'input password non abbia angoli arrotondati a destra quando il pulsante è presente */
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        .input-group .btn {
            /* Assicura che il pulsante non abbia angoli arrotondati a sinistra */
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        /* Stile per il messaggio di errore */
        .error-message {
            margin-bottom: 1rem; /* Spazio sotto il messaggio di errore */
        }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">
<?php
// Avvia la sessione PHP
session_start();

// Controlla se l'utente è già loggato, in tal caso reindirizza a index.php
if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit(); // Termina lo script per assicurare il reindirizzamento
}

// Gestione del messaggio di errore
$errorMessage = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] == '1') {
        $errorMessage = 'Username o password non validi. Riprova.';
    } elseif ($_GET['error'] == '2') {
        // Potresti aggiungere altri codici di errore se necessario
        $errorMessage = 'Si è verificato un errore imprevisto.';
    }
    // Aggiungi altri casi di errore se necessario
}
?>
    <!-- Barra informazioni utente -->
    <div class="alert alert-secondary text-end m-0 rounded-0">
        <?php echo "Utente: Ospite"; // Mostra sempre Ospite dato che se loggato viene reindirizzato ?>
    </div>

    <!-- Contenitore principale per centrare il form verticalmente e orizzontalmente -->
    <div class="container flex-grow-1 d-flex align-items-center justify-content-center">
        <div class="card mx-auto my-4" style="max-width: 400px; width: 100%;">
            <div class="card-body p-4 p-md-5">
                <h3 class="text-center mb-4">Login</h3>

                <!-- Contenitore per il messaggio di errore -->
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger error-message" role="alert">
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>

                <form action="verifica_login.php" method="post" id="loginForm">
                    <!-- Campo Username -->
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" id="username" class="form-control" required value="<?php echo isset($_GET['username']) ? htmlspecialchars($_GET['username']) : ''; ?>">
                    </div>

                    <!-- Campo Password con pulsante mostra/nascondi -->
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Pulsante di Login -->
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
    </div>

<script>
    // Seleziona gli elementi del DOM
    const usernameField = document.getElementById('username');
    const passwordField = document.getElementById('password');
    const togglePasswordButton = document.getElementById('togglePassword');
    const loginForm = document.getElementById('loginForm'); // Seleziona il form

    // --- Conversione username in minuscolo durante la digitazione ---
    if (usernameField) {
        usernameField.addEventListener('input', function() {
            // Converte il valore del campo username in minuscolo
            this.value = this.value.toLowerCase();
        });
    }

    // --- Funzionalità per mostrare/nascondere la password ---
    if (passwordField && togglePasswordButton) {
        togglePasswordButton.addEventListener('click', function () {
            // Controlla il tipo corrente del campo password
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            // Imposta il nuovo tipo per il campo password
            passwordField.setAttribute('type', type);

            // Cambia l'icona del pulsante per riflettere lo stato (occhio / occhio barrato)
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            }
        });
    }

    // --- Funzioni per la sidebar (invariate dalla tua versione) ---
    function openNav() {
        const mySidebar = document.getElementById("mySidebar");
        if (mySidebar) {
            mySidebar.style.width = "250px";
        }
    }

    function closeNav() {
        const mySidebar = document.getElementById("mySidebar");
        if (mySidebar) {
            mySidebar.style.width = "0";
        }
    }
</script>
<!-- Script Bootstrap (Bundle JS) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
