<?php
// File: dashboard_bi.php - Business Intelligence Dashboard
// Versione: 1.0 - Dashboard Analytics e KPI

session_start();

// Verifica autenticazione
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include_once 'config.php';
include 'query/qutenti.php';

$username = $_SESSION['username'];
$utente_data = get_user_data($conn, $username);
$livello = $utente_data['livello'];

// Solo admin e responsabili possono accedere alla BI Dashboard
if ($livello > 2) {
    header("Location: unauthorized.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Dashboard BI - Analytics e KPI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js"></script>
    <link rel="stylesheet" href="css/app.css">
    
    <style>
        .kpi-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            transition: transform 0.2s ease;
        }
        
        .kpi-card:hover {
            transform: translateY(-5px);
        }
        
        .kpi-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .kpi-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            margin: 20px 0;
        }
        
        .trend-up {
            color: #28a745;
        }
        
        .trend-down {
            color: #dc3545;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .alert-panel {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <!-- Header fisso - Solo per utenti autenticati -->
    <?php if (isset($_SESSION['username'])): ?>
    <div class="fixed-top-elements">
        <button class="btn btn-primary menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMenu" aria-controls="mainMenu">
            <i class="bi bi-list me-2"></i>Menu
        </button>
        <div class="username-display">
            <i class="bi bi-person-circle me-2"></i>
            <?php echo htmlspecialchars($_SESSION['username']); ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Header Dashboard -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">📊 Business Intelligence Dashboard</h1>
                    <p class="mb-0 opacity-75">Analytics avanzate e KPI per il controllo flotte</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="last-update">
                        <small><i class="bi bi-clock"></i> Ultimo aggiornamento: <span id="lastUpdate"></span></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'include/menu.php'; ?>

    <div class="container-fluid">
        <!-- Pannello Filtri -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">🔍 Filtri Dashboard</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="filterYear" class="form-label">Anno</label>
                                <select class="form-select" id="filterYear">
                                    <option value="2025" selected>2025</option>
                                    <option value="2024">2024</option>
                                    <option value="2023">2023</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filterMonth" class="form-label">Mese</label>
                                <select class="form-select" id="filterMonth">
                                    <option value="">Tutti i mesi</option>
                                    <option value="01">Gennaio</option>
                                    <option value="02">Febbraio</option>
                                    <option value="03">Marzo</option>
                                    <option value="04">Aprile</option>
                                    <option value="05">Maggio</option>
                                    <option value="06">Giugno</option>
                                    <option value="07">Luglio</option>
                                    <option value="08">Agosto</option>
                                    <option value="09" selected>Settembre</option>
                                    <option value="10">Ottobre</option>
                                    <option value="11">Novembre</option>
                                    <option value="12">Dicembre</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filterFiliale" class="form-label">Filiale</label>
                                <select class="form-select" id="filterFiliale">
                                    <option value="">Tutte le filiali</option>
                                    <!-- Popolato dinamicamente -->
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filterTarga" class="form-label">Targa</label>
                                <select class="form-select" id="filterTarga">
                                    <option value="">Tutte le targhe</option>
                                    <!-- Popolato dinamicamente -->
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <button class="btn btn-primary" id="applyFilters">
                                    <i class="bi bi-funnel"></i> Applica Filtri
                                </button>
                                <button class="btn btn-outline-secondary ms-2" id="resetFilters">
                                    <i class="bi bi-arrow-clockwise"></i> Reset
                                </button>
                                <div class="float-end">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle"></i> 
                                        I filtri influenzano sia i KPI che la tabella dettagli
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Panel per KPI Critici -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert-panel p-3 rounded" id="alertPanel" style="display: none;">
                    <h5><i class="bi bi-exclamation-triangle"></i> Alert Operativi</h5>
                    <div id="alertContent"></div>
                </div>
            </div>
        </div>

        <!-- KPI Cards Row -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 mb-3">
                <div class="card kpi-card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-speedometer2 mb-3" style="font-size: 2rem;"></i>
                        <div class="kpi-value" id="totalKmMonth">-</div>
                        <div class="kpi-label">Km Percorsi (Mese)</div>
                        <small class="d-block mt-2">
                            <span id="kmTrend" class="trend-indicator"></span>
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-6 mb-3">
                <div class="card kpi-card h-100" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="card-body text-center">
                        <i class="bi bi-fuel-pump mb-3" style="font-size: 2rem;"></i>
                        <div class="kpi-value" id="avgConsumption">-</div>
                        <div class="kpi-label">L/100km Medio</div>
                        <small class="d-block mt-2">
                            <span id="consumptionTrend" class="trend-indicator"></span>
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-6 mb-3">
                <div class="card kpi-card h-100" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="card-body text-center">
                        <i class="bi bi-bullseye mb-3" style="font-size: 2rem;"></i>
                        <div class="kpi-value" id="targetAchievement">-</div>
                        <div class="kpi-label">% Raggiungimento Target</div>
                        <small class="d-block mt-2">
                            <span id="targetTrend" class="trend-indicator"></span>
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-6 mb-3">
                <div class="card kpi-card h-100" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <div class="card-body text-center">
                        <i class="bi bi-cash mb-3" style="font-size: 2rem;"></i>
                        <div class="kpi-value" id="totalCostMonth">-</div>
                        <div class="kpi-label">€ Costi Totali (Mese)</div>
                        <small class="d-block mt-2">
                            <span id="costTrend" class="trend-indicator"></span>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Trend Chilometri -->
            <div class="col-lg-8 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">📈 Trend Chilometri vs Target</h5>
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="periodKm" id="km3m" value="3" checked>
                            <label class="btn btn-outline-primary" for="km3m">3M</label>
                            
                            <input type="radio" class="btn-check" name="periodKm" id="km6m" value="6">
                            <label class="btn btn-outline-primary" for="km6m">6M</label>
                            
                            <input type="radio" class="btn-check" name="periodKm" id="km12m" value="12">
                            <label class="btn btn-outline-primary" for="km12m">12M</label>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="kmTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Efficienza Flotta -->
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">⚡ Efficienza Flotta</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="efficiencyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Filiali e Dettagli -->
        <div class="row mb-4">
            <!-- Performance per Filiale -->
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">🏢 Performance per Filiale</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="branchPerformanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Performers -->
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">🏆 Top Performers</h5>
                    </div>
                    <div class="card-body">
                        <div id="topPerformers">
                            <!-- Dinamico via JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabella Dettagli -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">📋 Dettaglio Veicoli</h5>
                        <button class="btn btn-sm btn-outline-success" id="exportData">
                            <i class="bi bi-download"></i> Esporta Excel
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="vehicleDetailsTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Targa</th>
                                        <th>Operatore</th>
                                        <th>Filiale</th>
                                        <th>Km Mese</th>
                                        <th>Target %</th>
                                        <th>L/100km</th>
                                        <th>€ Costi</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="vehicleDetailsBody">
                                    <!-- Dinamico via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Dashboard BI Controller Class
        class BIDashboard {
            constructor() {
                this.charts = {};
                this.data = {};
                this.filters = {
                    year: '2025',
                    month: '09',
                    filiale: '',
                    targa: ''
                };
                this.init();
            }

            async init() {
                console.log('Dashboard initialization started');
                await this.loadFilterOptions();
                await this.loadData();
                console.log('Data loaded, updating KPIs');
                this.updateKPIs();
                console.log('Initializing charts');
                this.initCharts();
                console.log('Updating vehicle details table');
                this.updateVehicleDetailsTable();
                console.log('Updating last refresh');
                this.updateLastRefresh();
                this.setupEventListeners();
                
                // Auto-refresh ogni 5 minuti
                setInterval(() => this.refresh(), 300000);
                console.log('Dashboard initialization completed');
            }

            async loadData() {
                try {
                    // Carica dati KPI
                    console.log('Caricamento dati dashboard...');
                    const filterParams = this.buildFilterParams();
                    const url = 'api/dashboard_data.php' + (filterParams ? '?' + filterParams : '');
                    console.log('URL API:', url);
                    
                    const response = await fetch(url);
                    console.log('Response status:', response.status);
                    
                    const text = await response.text();
                    console.log('Response text length:', text.length);
                    
                    this.data = JSON.parse(text);
                    console.log('Dati caricati:', this.data);
                    console.log('Vehicle details count:', this.data.vehicleDetails ? this.data.vehicleDetails.length : 'undefined');
                } catch (error) {
                    console.error('Errore caricamento dati:', error);
                    this.showAlert('Errore nel caricamento dei dati dashboard');
                }
            }

            async loadFilterOptions() {
                try {
                    console.log('Caricamento opzioni filtri...');
                    const response = await fetch('api/dashboard_data.php?include_filters=1');
                    const data = await response.json();
                    
                    if (data.filterOptions) {
                        this.populateFilterOptions(data.filterOptions);
                    }
                } catch (error) {
                    console.error('Errore caricamento opzioni filtri:', error);
                }
            }

            populateFilterOptions(options) {
                // Popola filiali
                const filialeSelect = document.getElementById('filterFiliale');
                filialeSelect.innerHTML = '<option value="">Tutte le filiali</option>';
                options.filiali.forEach(filiale => {
                    const option = document.createElement('option');
                    option.value = filiale;
                    option.textContent = filiale;
                    filialeSelect.appendChild(option);
                });

                // Popola targhe
                const targaSelect = document.getElementById('filterTarga');
                targaSelect.innerHTML = '<option value="">Tutte le targhe</option>';
                options.targhe.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.targa;
                    option.textContent = `${item.targa} (${item.filiale})`;
                    targaSelect.appendChild(option);
                });
            }

            buildFilterParams() {
                const params = new URLSearchParams();
                
                if (this.filters.year) params.append('year', this.filters.year);
                if (this.filters.month) params.append('month', this.filters.month);
                if (this.filters.filiale) params.append('filiale', this.filters.filiale);
                if (this.filters.targa) params.append('targa', this.filters.targa);
                
                return params.toString();
            }

            updateKPIs() {
                const { kpis } = this.data;
                
                document.getElementById('totalKmMonth').textContent = 
                    kpis.totalKmMonth?.toLocaleString() || '0';
                
                document.getElementById('avgConsumption').textContent = 
                    kpis.avgConsumption?.toFixed(1) || '0.0';
                
                document.getElementById('targetAchievement').textContent = 
                    kpis.targetAchievement?.toFixed(0) + '%' || '0%';
                
                document.getElementById('totalCostMonth').textContent = 
                    '€' + (kpis.totalCostMonth?.toLocaleString() || '0');

                this.updateTrends(kpis);
                this.checkAlerts(kpis);
            }

            updateTrends(kpis) {
                this.setTrend('kmTrend', kpis.kmTrendPercent);
                this.setTrend('consumptionTrend', kpis.consumptionTrendPercent);
                this.setTrend('targetTrend', kpis.targetTrendPercent);
                this.setTrend('costTrend', kpis.costTrendPercent);
            }

            setTrend(elementId, value) {
                const element = document.getElementById(elementId);
                if (!element) return;

                const isPositive = value > 0;
                const icon = isPositive ? 'bi-arrow-up' : 'bi-arrow-down';
                const className = isPositive ? 'trend-up' : 'trend-down';
                
                element.innerHTML = `<i class="bi ${icon}"></i> ${Math.abs(value).toFixed(1)}%`;
                element.className = `trend-indicator ${className}`;
            }

            initCharts() {
                this.initKmTrendChart();
                this.initEfficiencyChart();
                this.initBranchPerformanceChart();
            }

            initKmTrendChart() {
                const ctx = document.getElementById('kmTrendChart').getContext('2d');
                this.charts.kmTrend = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: this.data.kmTrend?.labels || [],
                        datasets: [{
                            label: 'Km Effettivi',
                            data: this.data.kmTrend?.actual || [],
                            borderColor: '#007bff',
                            backgroundColor: 'rgba(0, 123, 255, 0.1)',
                            tension: 0.4
                        }, {
                            label: 'Target',
                            data: this.data.kmTrend?.target || [],
                            borderColor: '#28a745',
                            borderDash: [5, 5],
                            fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Andamento Chilometri vs Target'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Chilometri'
                                }
                            }
                        }
                    }
                });
            }

            initEfficiencyChart() {
                const ctx = document.getElementById('efficiencyChart').getContext('2d');
                this.charts.efficiency = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Ottima', 'Buona', 'Media', 'Scarsa'],
                        datasets: [{
                            data: this.data.efficiency?.distribution || [0, 0, 0, 0],
                            backgroundColor: [
                                '#28a745',
                                '#ffc107',
                                '#fd7e14',
                                '#dc3545'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }

            initBranchPerformanceChart() {
                const ctx = document.getElementById('branchPerformanceChart').getContext('2d');
                this.charts.branchPerformance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: this.data.branchPerformance?.labels || [],
                        datasets: [{
                            label: '% Target Raggiunto',
                            data: this.data.branchPerformance?.data || [],
                            backgroundColor: 'rgba(54, 162, 235, 0.8)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 120,
                                title: {
                                    display: true,
                                    text: 'Percentuale Target'
                                }
                            }
                        }
                    }
                });
            }

            updateCharts() {
                // Aggiorna i grafici esistenti con nuovi dati
                if (this.charts.kmTrend) {
                    this.charts.kmTrend.data.labels = this.data.kmTrend?.labels || [];
                    this.charts.kmTrend.data.datasets[0].data = this.data.kmTrend?.actual || [];
                    this.charts.kmTrend.data.datasets[1].data = this.data.kmTrend?.target || [];
                    this.charts.kmTrend.update();
                }

                if (this.charts.efficiency) {
                    this.charts.efficiency.data.datasets[0].data = this.data.efficiency?.distribution || [0, 0, 0, 0];
                    this.charts.efficiency.update();
                }

                if (this.charts.branchPerformance) {
                    this.charts.branchPerformance.data.labels = this.data.branchPerformance?.labels || [];
                    this.charts.branchPerformance.data.datasets[0].data = this.data.branchPerformance?.data || [];
                    this.charts.branchPerformance.update();
                }

                // Aggiorna tabella dettagli veicoli
                this.updateVehicleDetailsTable();
            }

            updateVehicleDetailsTable() {
                console.log('updateVehicleDetailsTable chiamata');
                const tbody = document.getElementById('vehicleDetailsBody');
                console.log('tbody element:', tbody);
                
                if (!tbody) return;

                const vehicles = this.data.vehicleDetails || [];
                console.log('Vehicles data:', vehicles);
                console.log('Number of vehicles:', vehicles.length);
                
                tbody.innerHTML = '';

                if (vehicles.length === 0) {
                    console.log('Nessun veicolo, inserendo messaggio placeholder');
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Nessun veicolo trovato</td></tr>';
                    return;
                }

                vehicles.forEach((vehicle, index) => {
                    console.log(`Processing vehicle ${index}:`, vehicle);
                    const row = document.createElement('tr');
                    
                    row.innerHTML = `
                        <td>${vehicle.targa || '-'}</td>
                        <td>${vehicle.operatore || '-'}</td>
                        <td>${vehicle.filiale || '-'}</td>
                        <td>${vehicle.km_mese?.toLocaleString() || '0'}</td>
                        <td>${vehicle.target_percentuale?.toFixed(1) || '0.0'}%</td>
                        <td>${vehicle.consumo_medio?.toFixed(1) || '-'} L/100km</td>
                        <td>€${vehicle.costi_totali?.toLocaleString() || '0'}</td>
                        <td>${vehicle.status || 'Nessun dato'}</td>
                    `;
                    
                    tbody.appendChild(row);
                });
                
                console.log('Tabella aggiornata con', vehicles.length, 'righe');
            }

            checkAlerts(kpis) {
                const alerts = [];
                
                if (kpis.targetAchievement < 80) {
                    alerts.push('⚠️ Raggiungimento target sotto l\'80%');
                }
                
                if (kpis.avgConsumption > 8.5) {
                    alerts.push('⛽ Consumo medio superiore a 8.5 L/100km');
                }

                if (alerts.length > 0) {
                    document.getElementById('alertPanel').style.display = 'block';
                    document.getElementById('alertContent').innerHTML = 
                        alerts.map(alert => `<div class="mb-1">${alert}</div>`).join('');
                }
            }

            setupEventListeners() {
                // Period selector per grafico km
                document.querySelectorAll('input[name="periodKm"]').forEach(radio => {
                    radio.addEventListener('change', (e) => {
                        this.updateKmTrendChart(e.target.value);
                    });
                });

                // Export button
                document.getElementById('exportData').addEventListener('click', () => {
                    this.exportToExcel();
                });

                // Filter buttons
                document.getElementById('applyFilters').addEventListener('click', () => {
                    this.applyFilters();
                });

                document.getElementById('resetFilters').addEventListener('click', () => {
                    this.resetFilters();
                });
            }

            updateLastRefresh() {
                const now = new Date();
                document.getElementById('lastUpdate').textContent = 
                    now.toLocaleString('it-IT');
            }

            async refresh() {
                await this.loadData();
                this.updateKPIs();
                this.updateCharts();
                this.updateVehicleDetailsTable();
                this.updateLastRefresh();
            }

            showAlert(message) {
                // Implementa sistema di notifiche
                console.error(message);
            }

            async applyFilters() {
                // Leggi i valori dai controlli
                this.filters.year = document.getElementById('filterYear').value;
                this.filters.month = document.getElementById('filterMonth').value;
                this.filters.filiale = document.getElementById('filterFiliale').value;
                this.filters.targa = document.getElementById('filterTarga').value;

                console.log('Applicando filtri:', this.filters);

                // Ricarica i dati
                await this.loadData();
                this.updateKPIs();
                this.updateCharts();
                this.updateVehicleDetailsTable();
                this.updateLastRefresh();
            }

            resetFilters() {
                // Reset ai valori predefiniti
                document.getElementById('filterYear').value = '2025';
                document.getElementById('filterMonth').value = '09';
                document.getElementById('filterFiliale').value = '';
                document.getElementById('filterTarga').value = '';

                // Applica i filtri resettati
                this.applyFilters();
            }

            exportToExcel() {
                // Implementa export Excel
                window.location.href = 'api/export_dashboard.php';
            }
        }

        // Inizializza Dashboard
        document.addEventListener('DOMContentLoaded', () => {
            new BIDashboard();
        });
    </script>
</body>
</html>