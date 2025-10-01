<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>Gestione KM</title>
    <meta name="description" content="Sistema di gestione chilometri e rifornimenti">
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
    <?php include 'include/menu.php'; ?>

    <!-- Contenuto principale -->
    <main class="container" id="main-content">
        <?php if (isset($page_content)): ?>
            <?php echo $page_content; ?>
        <?php else: ?>
            <!-- Content will be inserted here -->
        <?php endif; ?>
    </main>

    <!-- Toast container -->
    <div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1060;"></div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script src="js/app.js"></script>
    
    <?php if (isset($additional_scripts)): echo $additional_scripts; endif; ?>

    <!-- Service Worker Registration - TEMPORARILY DISABLED -->
    <!-- The service worker was causing "exports is not defined" errors that prevented dashboard functionality -->
    <?php if (false && file_exists('service-worker.js')): ?>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('service-worker.js')
                    .then(function(registration) {
                        console.log('SW registered: ', registration);
                    })
                    .catch(function(registrationError) {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>