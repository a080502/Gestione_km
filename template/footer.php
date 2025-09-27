<?php
/**
 * Template Footer - Sezione di chiusura comune a tutte le pagine
 * Versione: 2.0.0-ui-refresh
 */
?>
    <!-- Toast container -->
    <div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1060;"></div>

    <!-- Image preview container -->
    <div id="image-preview-container" class="position-absolute bg-white rounded shadow-lg p-2" style="z-index: 1050; display: none; max-width: 250px; max-height: 250px; overflow: hidden; border: 2px solid var(--primary-color);">
        <img src="" alt="Anteprima" class="w-100 h-auto rounded">
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script src="js/app.js"></script>
    
    <!-- Page specific scripts -->
    <?php if (isset($additional_scripts)): echo $additional_scripts; endif; ?>

    <!-- Service Worker Registration -->
    <?php if (file_exists('service-worker.js')): ?>
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

    <!-- Cleanup -->
    <?php
    if (isset($conn) && $conn instanceof mysqli && isset($conn->thread_id)) {
        try {
            if ($conn->ping()) {
                $conn->close();
            }
        } catch (Error $e) {
            // Connessione già chiusa, ignora l'errore
        }
    }
    ?>
</body>
</html>