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
                this.init();
            }

            async init() {
                await this.loadData();
                this.updateKPIs();
                this.initCharts();
                this.updateLastRefresh();
                this.setupEventListeners();
                
                // Auto-refresh ogni 5 minuti
                setInterval(() => this.refresh(), 300000);
            }

            async loadData() {
                try {
                    // Carica dati KPI
                    const response = await fetch('api/dashboard_data.php');
                    this.data = await response.json();
                } catch (error) {
                    console.error('Errore caricamento dati:', error);
                    this.showAlert('Errore nel caricamento dei dati dashboard');
                }
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
                this.updateLastRefresh();
            }

            showAlert(message) {
                // Implementa sistema di notifiche
                console.error(message);
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