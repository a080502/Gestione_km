<?php
// menu.php - Menu di navigazione completo ottimizzato
// Determina il livello utente per il controllo degli accessi
$user_level = isset($utente_data['livello']) ? (int)$utente_data['livello'] : 3;
$is_admin = ($user_level === 1);
$is_manager = ($user_level <= 2);
$current_page = basename($_SERVER['PHP_SELF']);
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
            <!-- Dashboard -->
            <div class="px-3 py-2">
                <small class="text-muted text-uppercase fw-bold"><i class="bi bi-house-door me-1"></i>Dashboard</small>
            </div>
            <a href="index.php" class="nav-link <?php if ($current_page == 'index.php') echo 'active'; ?>">
                <i class="bi bi-fuel-pump-fill me-2"></i> Inserimento Rifornimento
            </a>
            <a href="status.php" class="nav-link <?php if ($current_page == 'status.php') echo 'active'; ?>">
                <i class="bi bi-speedometer2 me-2"></i> Stato Sistema
            </a>
            
            <hr class="my-2 mx-3">
            
            <!-- Visualizzazione Dati -->
            <div class="px-3 py-2">
                <small class="text-muted text-uppercase fw-bold"><i class="bi bi-graph-up me-1"></i>Report & Visualizzazioni</small>
            </div>
            <a href="visualizza.php" class="nav-link <?php if ($current_page == 'visualizza.php') echo 'active'; ?>">
                <i class="bi bi-table me-2"></i> Tutte le Registrazioni
            </a>
            <a href="report_mese.php" class="nav-link <?php if ($current_page == 'report_mese.php') echo 'active'; ?>">
                <i class="bi bi-bar-chart-line-fill me-2"></i> Report Mensile
            </a>
            <a href="esportazione_dati.php" class="nav-link <?php if ($current_page == 'esportazione_dati.php') echo 'active'; ?>">
                <i class="bi bi-download me-2"></i> Esportazione Dati
            </a>
            <a href="create_pdf.php" class="nav-link <?php if ($current_page == 'create_pdf.php') echo 'active'; ?>">
                <i class="bi bi-file-earmark-pdf me-2"></i> Genera PDF
            </a>
            
            <hr class="my-2 mx-3">
            
            <!-- Modifica Dati (solo se utente ha dati) -->
            <div class="px-3 py-2">
                <small class="text-muted text-uppercase fw-bold"><i class="bi bi-pencil me-1"></i>Modifica Dati</small>
            </div>
            <a href="modifica.php" class="nav-link <?php if ($current_page == 'modifica.php') echo 'active'; ?>">
                <i class="bi bi-pencil-square me-2"></i> Modifica Registrazione
            </a>
            <a href="aggiorna.php" class="nav-link <?php if ($current_page == 'aggiorna.php') echo 'active'; ?>">
                <i class="bi bi-arrow-clockwise me-2"></i> Aggiorna Dati
            </a>
            <a href="cancella.php" class="nav-link <?php if ($current_page == 'cancella.php') echo 'active'; ?>">
                <i class="bi bi-trash me-2"></i> Cancella Registrazione
            </a>
            
            <!-- Gestione Target (per manager e admin) -->
            <?php if ($is_manager): ?>
            <hr class="my-2 mx-3">
            <div class="px-3 py-2">
                <small class="text-muted text-uppercase fw-bold"><i class="bi bi-bullseye me-1"></i>Gestione Target</small>
            </div>
            <a href="gestione_target_annuale.php" class="nav-link <?php if ($current_page == 'gestione_target_annuale.php') echo 'active'; ?>">
                <i class="bi bi-bullseye me-2"></i> Target Annuale
            </a>
            <a href="imposta_target.php" class="nav-link <?php if ($current_page == 'imposta_target.php') echo 'active'; ?>">
                <i class="bi bi-gear-wide-connected me-2"></i> Imposta Target
            </a>
            <a href="modifica_target.php" class="nav-link <?php if ($current_page == 'modifica_target.php') echo 'active'; ?>">
                <i class="bi bi-pencil-fill me-2"></i> Modifica Target
            </a>
            <a href="salva_target.php" class="nav-link <?php if ($current_page == 'salva_target.php') echo 'active'; ?>">
                <i class="bi bi-save me-2"></i> Salva Target
            </a>
            
            <!-- Gestione Costi Extra -->
            <hr class="my-2 mx-3">
            <div class="px-3 py-2">
                <small class="text-muted text-uppercase fw-bold"><i class="bi bi-exclamation-triangle me-1"></i>Costi Sforamento</small>
            </div>
            <a href="gestione_costo_extra.php" class="nav-link <?php if ($current_page == 'gestione_costo_extra.php') echo 'active'; ?>">
                <i class="bi bi-currency-euro me-2"></i> Gestione Costi Extra
            </a>
            <a href="inserisci_extra.php" class="nav-link <?php if ($current_page == 'inserisci_extra.php') echo 'active'; ?>">
                <i class="bi bi-plus-circle me-2"></i> Inserisci Costo Extra
            </a>
            <a href="modifica_costo_extra.php" class="nav-link <?php if ($current_page == 'modifica_costo_extra.php') echo 'active'; ?>">
                <i class="bi bi-pencil-square me-2"></i> Modifica Costo Extra
            </a>
            <a href="cancella_costo_extra.php" class="nav-link <?php if ($current_page == 'cancella_costo_extra.php') echo 'active'; ?>">
                <i class="bi bi-x-circle me-2"></i> Cancella Costo Extra
            </a>
            <?php endif; ?>
            
            <!-- Gestione Sistema (solo admin) -->
            <?php if ($is_admin): ?>
            <hr class="my-2 mx-3">
            <div class="px-3 py-2">
                <small class="text-muted text-uppercase fw-bold"><i class="bi bi-gear me-1"></i>Amministrazione</small>
            </div>
            <a href="gestisci_utenti.php" class="nav-link <?php if ($current_page == 'gestisci_utenti.php') echo 'active'; ?>">
                <i class="bi bi-people-fill me-2"></i> Gestione Utenti
            </a>
            <a href="registrazione.php" class="nav-link <?php if ($current_page == 'registrazione.php') echo 'active'; ?>">
                <i class="bi bi-person-plus me-2"></i> Registra Utente
            </a>
            <a href="modifica_utente.php" class="nav-link <?php if ($current_page == 'modifica_utente.php') echo 'active'; ?>">
                <i class="bi bi-person-gear me-2"></i> Modifica Utente
            </a>
            <a href="aggiorna_utente.php" class="nav-link <?php if ($current_page == 'aggiorna_utente.php') echo 'active'; ?>">
                <i class="bi bi-person-check me-2"></i> Aggiorna Utente
            </a>
            <a href="cancella_utente.php" class="nav-link <?php if ($current_page == 'cancella_utente.php') echo 'active'; ?>">
                <i class="bi bi-person-x me-2"></i> Cancella Utente
            </a>
            
            <!-- Gestione Filiali -->
            <hr class="my-2 mx-3">
            <div class="px-3 py-2">
                <small class="text-muted text-uppercase fw-bold"><i class="bi bi-building me-1"></i>Filiali</small>
            </div>
            <a href="gestisci_filiali.php" class="nav-link <?php if ($current_page == 'gestisci_filiali.php') echo 'active'; ?>">
                <i class="bi bi-building-add me-2"></i> Gestione Filiali
            </a>
            <a href="inserisci_filiale.php" class="nav-link <?php if ($current_page == 'inserisci_filiale.php') echo 'active'; ?>">
                <i class="bi bi-building-plus me-2"></i> Inserisci Filiale
            </a>
            <a href="modifica_filiale.php" class="nav-link <?php if ($current_page == 'modifica_filiale.php') echo 'active'; ?>">
                <i class="bi bi-building-gear me-2"></i> Modifica Filiale
            </a>
            <a href="aggiorna_filiale.php" class="nav-link <?php if ($current_page == 'aggiorna_filiale.php') echo 'active'; ?>">
                <i class="bi bi-building-check me-2"></i> Aggiorna Filiale
            </a>
            <a href="salva_filiale.php" class="nav-link <?php if ($current_page == 'salva_filiale.php') echo 'active'; ?>">
                <i class="bi bi-building me-2"></i> Salva Filiale
            </a>
            <a href="cancella_filiale.php" class="nav-link <?php if ($current_page == 'cancella_filiale.php') echo 'active'; ?>">
                <i class="bi bi-building-x me-2"></i> Cancella Filiale
            </a>
            <a href="get_filiali.php" class="nav-link <?php if ($current_page == 'get_filiali.php') echo 'active'; ?>">
                <i class="bi bi-list-ul me-2"></i> Lista Filiali
            </a>
            
            <!-- Sistema e Configurazione -->
            <hr class="my-2 mx-3">
            <div class="px-3 py-2">
                <small class="text-muted text-uppercase fw-bold"><i class="bi bi-tools me-1"></i>Sistema</small>
            </div>
            <a href="gestione_dati_server.php" class="nav-link <?php if ($current_page == 'gestione_dati_server.php') echo 'active'; ?>">
                <i class="bi bi-server me-2"></i> Configurazione Server
            </a>
            <a href="backup_system.php" class="nav-link <?php if ($current_page == 'backup_system.php') echo 'active'; ?>">
                <i class="bi bi-shield-check me-2"></i> Sistema Backup
            </a>
            <a href="setup.php" class="nav-link <?php if ($current_page == 'setup.php') echo 'active'; ?>">
                <i class="bi bi-wrench-adjustable me-2"></i> Setup Sistema
            </a>
            <a href="send_email.php" class="nav-link <?php if ($current_page == 'send_email.php') echo 'active'; ?>">
                <i class="bi bi-envelope me-2"></i> Invio Email
            </a>
            
            <!-- Testing e Debug -->
            <hr class="my-2 mx-3">
            <div class="px-3 py-2">
                <small class="text-muted text-uppercase fw-bold"><i class="bi bi-bug me-1"></i>Test & Debug</small>
            </div>
            <a href="test_report_mese.php" class="nav-link <?php if ($current_page == 'test_report_mese.php') echo 'active'; ?>">
                <i class="bi bi-clipboard-data me-2"></i> Test Report
            </a>
            <a href="test_esportazione_dati.php" class="nav-link <?php if ($current_page == 'test_esportazione_dati.php') echo 'active'; ?>">
                <i class="bi bi-file-check me-2"></i> Test Esportazione
            </a>
            <a href="create_pdf_test.php" class="nav-link <?php if ($current_page == 'create_pdf_test.php') echo 'active'; ?>">
                <i class="bi bi-file-pdf me-2"></i> Test PDF
            </a>
            <?php endif; ?>
            
            <hr class="my-2 mx-3">
            
            <!-- Account -->
            <div class="px-3 py-2">
                <small class="text-muted text-uppercase fw-bold"><i class="bi bi-person me-1"></i>Account</small>
            </div>
            <?php if (!isset($_SESSION['username'])): ?>
            <a href="login.php" class="nav-link <?php if ($current_page == 'login.php') echo 'active'; ?>">
                <i class="bi bi-box-arrow-in-right me-2"></i> Accedi
            </a>
            <a href="registrazione.php" class="nav-link <?php if ($current_page == 'registrazione.php') echo 'active'; ?>">
                <i class="bi bi-person-plus me-2"></i> Registrati
            </a>
            <?php else: ?>
            <a href="logout.php" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right me-2"></i> Disconnetti
            </a>
            <?php endif; ?>
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