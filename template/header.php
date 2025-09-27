<?php
/**
 * Template Header - Sezione HEAD comune a tutte le pagine
 * Versione: 2.0.0-ui-refresh
 */

// Assicurati che la sessione sia avviata
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica autenticazione se richiesta
if (isset($require_auth) && $require_auth && !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Includi configurazione se richiesta  
if (isset($require_config) && $require_config) {
    include_once 'config.php';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>Gestione KM</title>
    <meta name="description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : 'Sistema di gestione chilometri e rifornimenti'; ?>">
    <meta name="author" content="Sistema Gestione KM">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/app.css">
    
    <!-- PWA Manifest -->
    <?php if (file_exists('manifest.json')): ?>
    <link rel="manifest" href="manifest.json">
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="immagini/logo.png">
    
    <!-- Additional head content -->
    <?php if (isset($additional_head)): echo $additional_head; endif; ?>
</head>
<body>
    <!-- Loading overlay -->
    <div id="loading-overlay" class="position-fixed top-0 start-0 w-100 h-100 bg-light d-flex justify-content-center align-items-center" style="z-index: 9999; display: none !important;">
        <div class="text-center">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Caricamento...</span>
            </div>
            <div class="mt-3 text-muted">Caricamento in corso...</div>
        </div>
    </div>

    <!-- Header fisso -->
    <div class="fixed-top-elements">
        <button class="btn btn-primary menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMenu" aria-controls="mainMenu">
            <i class="bi bi-list me-2"></i>Menu
        </button>
        <div class="username-display">
            <i class="bi bi-person-circle me-2"></i>
            <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Utente'; ?>
        </div>
    </div>

    <!-- Menu sidebar -->
    <?php 
    // Include menu with user data if available
    if (isset($utente_data)) {
        include 'include/menu.php';
    } else {
        include 'include/menu.php';
    }
    ?>