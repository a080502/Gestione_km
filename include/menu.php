<?php
// menu.php - Menu di navigazione ottimizzato
?>
<div class="offcanvas offcanvas-start" tabindex="-1" id="mainMenu" aria-labelledby="mainMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title text-gradient" id="mainMenuLabel">
            <i class="bi bi-speedometer me-2"></i>Gestione KM
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <nav class="nav flex-column">
            <!-- Sezione principale -->
            <div class="px-3 py-2">
                <small class="text-muted text-uppercase fw-bold">Operazioni Principali</small>
            </div>
            <a href="index.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'index.php') echo 'active'; ?>">
                <i class="bi bi-fuel-pump-fill me-2"></i> Inserimento Rifornimento
            </a>
            
            <hr class="my-2 mx-3">
            
            <!-- Sezione reports -->
            <div class="px-3 py-2">
                <small class="text-muted text-uppercase fw-bold">Report e Visualizzazioni</small>
            </div>
            <a href="report_mese.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'report_mese.php') echo 'active'; ?>">
                <i class="bi bi-bar-chart-line-fill me-2"></i> Report Mensile
            </a>
            <a href="visualizza.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'visualizza.php') echo 'active'; ?>">
                <i class="bi bi-table me-2"></i> Tutte le Registrazioni
            </a>
            
            <hr class="my-2 mx-3">
            
            <!-- Sezione gestione (solo per admin) -->
            <?php if (isset($utente_data) && $utente_data['livello'] === 'admin'): ?>
            <div class="px-3 py-2">
                <small class="text-muted text-uppercase fw-bold">Gestione Sistema</small>
            </div>
            <a href="gestisci_utenti.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'gestisci_utenti.php') echo 'active'; ?>">
                <i class="bi bi-people-fill me-2"></i> Gestione Utenti
            </a>
            <a href="gestisci_filiali.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'gestisci_filiali.php') echo 'active'; ?>">
                <i class="bi bi-building-add me-2"></i> Gestione Filiali
            </a>
            <a href="gestione_target_annuale.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'gestione_target_annuale.php') echo 'active'; ?>">
                <i class="bi bi-bullseye me-2"></i> Gestione Target Km
            </a>
            <a href="gestione_costo_extra.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'gestione_costo_extra.php') echo 'active'; ?>">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> Gestione Costo Sforamento
            </a>
            
            <hr class="my-2 mx-3">
            
            <!-- Sezione configurazione -->
            <div class="px-3 py-2">
                <small class="text-muted text-uppercase fw-bold">Configurazione</small>
            </div>
            <a href="gestione_dati_server.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'gestione_dati_server.php') echo 'active'; ?>">
                <i class="bi bi-gear-fill me-2"></i> Configurazione Sistema
            </a>
            <a href="backup_system.php" class="nav-link <?php if (basename($_SERVER['PHP_SELF']) == 'backup_system.php') echo 'active'; ?>">
                <i class="bi bi-shield-check me-2"></i> Sistema Backup
            </a>
            
            <hr class="my-2 mx-3">
            <?php endif; ?>
            
            <!-- Logout -->
            <a href="logout.php" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right me-2"></i> Disconnetti
            </a>
        </nav>
        
        <!-- Card informazioni utente -->
        <?php if (isset($utente_data)): ?>
            <div class="mt-auto p-3">
                <div class="card border-0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                    <div class="card-body p-3">
                        <h6 class="card-title text-primary mb-2">
                            <i class="bi bi-person-circle me-2"></i>Dati Utente
                        </h6>
                        <div class="small">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Targa:</span>
                                <span class="fw-semibold"><?php echo htmlspecialchars($utente_data['targa_mezzo']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Divisione:</span>
                                <span class="fw-semibold"><?php echo htmlspecialchars($utente_data['divisione']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Filiale:</span>
                                <span class="fw-semibold"><?php echo htmlspecialchars($utente_data['filiale']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Livello:</span>
                                <span class="badge <?php echo $utente_data['livello'] === 'admin' ? 'bg-danger' : 'bg-secondary'; ?> small">
                                    <?php echo htmlspecialchars(ucfirst($utente_data['livello'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="mt-auto p-3">
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <small>Dati utente non disponibili</small>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>