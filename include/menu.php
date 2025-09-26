<?php
// menu.php
// Assegna i dati dell'utente alla variabile attesa dal menu.php

?>
<div class="offcanvas offcanvas-start" tabindex="-1" id="mainMenu" aria-labelledby="mainMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="mainMenuLabel">Menu Navigazione</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <nav class="nav flex-column">
            <a href="index.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'index.php') echo 'active'; ?>"><i class="bi bi-fuel-pump-fill me-2"></i> Inserimento Rifornimento</a>
            <hr class="my-2">
            <a href="report_mese.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'report_mese.php') echo 'active'; ?>"><i class="bi bi-bar-chart-line-fill me-2"></i> Report Mensile</a>
            <a href="visualizza.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'report_anno.php') echo 'active'; ?>"><i class="bi bi-calendar-event-fill me-2"></i> Tutte le Registrazioni</a>
            <hr class="my-2">
            <a href="gestisci_utenti.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'gestisci_utenti.php') echo 'active'; ?>"><i class="bi bi-people-fill me-2"></i> Gestione Utenti</a>
            <a href="gestisci_filiali.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'gestisci_filiali.php') echo 'active'; ?>"><i class="bi bi-building-add me-2"></i> Gestione Filiali</a>

            <a href="gestione_target_annuale.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'imposta_target.php') echo 'active'; ?>"><i class="bi bi-building-add me-2"></i> Gestione Target Km</a>
            <a href="gestione_costo_extra.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'gestione_costo_extra.php') echo 'active'; ?>"><i class="bi bi-building-add me-2"></i> Gestione Costo Sforo Km</a>
			<a href="gestione_dati_server.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'gestione_dati_server.php') echo 'active'; ?>"><i class="bi bi-building-add me-2"></i> Configurazione dati Server</a>

            <hr class="my-2">
            <a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
        </nav>
        <?php if (isset($utente_data)): ?>
            <div class="card mb-4 mt-3">
                <div class="card-body">
                    <h5 class="card-title">Dati Loggato</h5>
                    <strong>Targa:</strong>   <?php echo htmlspecialchars($utente_data['targa_mezzo']); ?> <br>
                    <strong>Divisione:</strong>   <?php echo htmlspecialchars($utente_data['divisione']); ?> <br>
                    <strong>Filiale:</strong>   <?php echo htmlspecialchars($utente_data['filiale']); ?> <br>
                    <strong>Autorizzazioni:</strong>   <?php echo htmlspecialchars($utente_data['livello']); ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">Dati utente non trovati.</div>
        <?php endif; ?>
    </div>
</div>