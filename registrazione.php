<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include_once 'config.php';
include 'query/qutenti.php'; // Includi il file delle query

// Recupera i dati dell'utente per ottenere il livello
$utente_data = get_user_data($conn, $_SESSION['username']);

// Verifica se l'utente è stato trovato e se il suo livello è minore di 3
if (!$utente_data || $utente_data['livello'] >= 3) {
    // Se il livello è 3 o superiore, o l'utente non esiste, reindirizza alla pagina di non autorizzazione
    header("Location: unauthorized.php"); // Assicurati di avere la pagina unauthorized.php creata
    exit();
}

$username = $_SESSION['username'];

// Connessione al database per popolare le dropdown
// La connessione è già inclusa sopra

// Recupera le divisioni
$sql_divisioni = "SELECT DISTINCT divisione FROM filiali";
$result_divisioni = $conn->query($sql_divisioni);
$divisioni = [];
if ($result_divisioni->num_rows > 0) {
    while ($row = $result_divisioni->fetch_assoc()) {
        $divisioni[] = $row['divisione'];
    }
}

// Recupera i livelli di autorizzazione con descrizione e livello
$sql_autorizzazioni = "SELECT descrizione_livello, livello FROM livelli_autorizzazione";
$result_autorizzazioni = $conn->query($sql_autorizzazioni);
$livelli_autorizzazione = [];
if ($result_autorizzazioni->num_rows > 0) {
    while ($row = $result_autorizzazioni->fetch_assoc()) {
        $livelli_autorizzazione[] = $row; // Memorizza l'intero array associativo
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione</title>
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

        .password-toggle-btn {
            border: none;
            background: transparent;
            padding: 0.375rem 0.5rem;
            color: #6c757d;
            cursor: pointer;
        }

        .password-toggle-btn:hover {
            color: #495057;
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
    <h1 class="mb-4 text-center">Registrazione</h1>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <form action="registra_utente.php" method="post" class="bg-white shadow-sm rounded p-4">
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome:</label>
                    <input type="text" name="nome" id="nome" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="cognome" class="form-label">Cognome:</label>
                    <input type="text" name="cognome" id="cognome" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="targa_mezzo" class="form-label">Targa Mezzo:</label>
                    <input type="text" name="targa_mezzo" id="targa_mezzo" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="costo" class="form-label">Costo:</label>
                    <input type="number" name="costo" id="costo" step="0.01" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="divisione" class="form-label">Divisione:</label>
                    <select name="divisione" id="divisione" class="form-select">
                        <option value="">Seleziona Divisione</option>
                        <?php foreach ($divisioni as $divisione) : ?>
                            <option value="<?php echo htmlspecialchars($divisione); ?>"><?php echo htmlspecialchars($divisione); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="filiale" class="form-label">Filiale:</label>
                    <div id="filiale_display" class="form-text">Seleziona una divisione</div>
                    <input type="hidden" name="filiale" id="filiale" value="">
                </div>
                <div class="mb-3">
                    <label for="livello" class="form-label">Autorizzazioni:</label>
                    <select name="livello" id="livello" class="form-select">
                        <option value="">Seleziona Autorizzazione</option>
                        <?php foreach ($livelli_autorizzazione as $livello_data) : ?>
                            <option value="<?php echo htmlspecialchars($livello_data['livello']); ?>">
                                <?php echo htmlspecialchars($livello_data['descrizione_livello']) . ' (' . htmlspecialchars($livello_data['livello']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="username" class="form-label">Username:</label>
                    <input type="text" name="username" id="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password:</label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control" required>
                        <button class="btn btn-outline-secondary password-toggle-btn" type="button" id="togglePassword">
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus-fill me-2"></i> Registra</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script>
    // Script per convertire la targa mezzo in maiuscolo e username in minuscolo durante la digitazione
    document.addEventListener('DOMContentLoaded', function() {
        const targaMezzoField = document.getElementById('targa_mezzo');
        const usernameField = document.getElementById('username');
        
        // Converte la targa in maiuscolo durante la digitazione
        targaMezzoField.addEventListener('input', function() {
            // Salva la posizione del cursore
            const cursorPosition = this.selectionStart;
            // Converte in maiuscolo
            this.value = this.value.toUpperCase();
            // Ripristina la posizione del cursore
            this.setSelectionRange(cursorPosition, cursorPosition);
        });
        
        // Converte l'username in minuscolo durante la digitazione
        usernameField.addEventListener('input', function() {
            // Salva la posizione del cursore
            const cursorPosition = this.selectionStart;
            // Converte in minuscolo
            this.value = this.value.toLowerCase();
            // Ripristina la posizione del cursore
            this.setSelectionRange(cursorPosition, cursorPosition);
        });

        // Toggle per mostrare/nascondere la password
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password');
        const togglePasswordIcon = document.getElementById('togglePasswordIcon');

        togglePassword.addEventListener('click', function() {
            // Cambia il tipo di input tra password e text
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
            // Cambia l'icona
            if (type === 'text') {
                togglePasswordIcon.classList.remove('bi-eye');
                togglePasswordIcon.classList.add('bi-eye-slash');
            } else {
                togglePasswordIcon.classList.remove('bi-eye-slash');
                togglePasswordIcon.classList.add('bi-eye');
            }
        });
    });

    // Script esistente per le filiali
    const divisioneSelect = document.getElementById('divisione');
    const filialeDisplay = document.getElementById('filiale_display');
    const filialeInputHidden = document.getElementById('filiale'); // Ottieni riferimento all'input nascosto

    divisioneSelect.addEventListener('change', function() {
        const selectedDivisione = this.value;
        filialeDisplay.textContent = 'Caricamento filiale...';
        filialeInputHidden.value = ''; // Resetta il valore dell'input nascosto

        if (selectedDivisione) {
            fetch('get_filiali.php?divisione=' + selectedDivisione)
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0 && data[0].nome_divisione) {
                        const filialeNome = data[0].nome_divisione;
                        filialeDisplay.textContent = filialeNome;
                        filialeInputHidden.value = filialeNome; // Imposta il valore dell'input nascosto
                    } else {
                        filialeDisplay.textContent = 'Nessuna filiale trovata per questa divisione.';
                    }
                })
                .catch(error => {
                    console.error('Errore nel recupero della filiale:', error);
                    filialeDisplay.textContent = 'Errore nel caricamento della filiale.';
                });
        } else {
            filialeDisplay.textContent = 'Seleziona una divisione';
        }
    });
</script>
</body>
</html>