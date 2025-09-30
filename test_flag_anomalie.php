<?php
// Test per le funzionalità di flag/unflag anomalie
session_start();

// Simula una sessione admin valida
$_SESSION['username'] = 'denis';
$_SESSION['dati_utente'] = [
    'Nome' => 'Denis',
    'Cognome' => 'Test',
    'livello' => 1
];

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Flag/Unflag Anomalie</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css' rel='stylesheet'>";
echo "</head><body class='container mt-4'>";

echo "<h1>Test Flag/Unflag Anomalie</h1>";

echo "<div class='alert alert-info'>";
echo "<h4>Funzionalità Implementate:</h4>";
echo "<ul>";
echo "<li><strong>Visualizzazione differenziata:</strong> Anomalie flaggate hanno sfondo azzurro (table-info)</li>";
echo "<li><strong>Icona flag:</strong> <i class='bi bi-flag-fill text-primary'></i> per anomalie flaggate</li>";
echo "<li><strong>Pulsante dinamico:</strong> 'Flag' <i class='bi bi-flag'></i> o 'Unflag' <i class='bi bi-flag-slash'></i> a seconda dello stato</li>";
echo "<li><strong>Informazioni aggiuntive:</strong> Chi ha flaggato e quando</li>";
echo "<li><strong>Aggiornamento real-time:</strong> Senza ricaricare la pagina</li>";
echo "</ul>";
echo "</div>";

echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<div class='card'>";
echo "<div class='card-header bg-primary text-white'>";
echo "<h5><i class='bi bi-flag me-2'></i>Test Flag Anomalie</h5>";
echo "</div>";
echo "<div class='card-body'>";
echo "<p>Testa le funzionalità di flag/unflag:</p>";
echo "<a href='report_avanzato.php' target='_blank' class='btn btn-primary'>Apri Report Avanzato</a>";
echo "</div></div></div>";

echo "<div class='col-md-6'>";
echo "<div class='card'>";
echo "<div class='card-header bg-success text-white'>";
echo "<h5><i class='bi bi-api-app me-2'></i>Test API</h5>";
echo "</div>";
echo "<div class='card-body'>";
echo "<p>Testa direttamente le API:</p>";
echo "<div class='d-grid gap-2'>";
echo "<button class='btn btn-outline-warning' onclick='testFlagAPI()'>Test Flag API</button>";
echo "<button class='btn btn-outline-success' onclick='testUnflagAPI()'>Test Unflag API</button>";
echo "</div>";
echo "</div></div></div>";
echo "</div>";

echo "<div class='mt-4'>";
echo "<h3>Legenda Colori:</h3>";
echo "<div class='row'>";
echo "<div class='col-md-4'>";
echo "<div class='alert alert-danger'>table-danger - Anomalie critiche (Z-Score > 3)</div>";
echo "</div>";
echo "<div class='col-md-4'>";
echo "<div class='alert alert-warning'>table-warning - Anomalie moderate</div>";
echo "</div>";
echo "<div class='col-md-4'>";
echo "<div class='alert alert-info'>table-info - Anomalie flaggate</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<script>";
echo "function testFlagAPI() {";
echo "  const id = prompt('Inserisci ID anomalia da flaggare:');";
echo "  if (id) {";
echo "    const formData = new FormData();";
echo "    formData.append('action', 'flag_anomalia');";
echo "    formData.append('id', id);";
echo "    formData.append('tipo', 'TEST_FLAG');";
echo "    formData.append('note', 'Test da interfaccia di debug');";
echo "    ";
echo "    fetch('api_anomalie.php', {";
echo "      method: 'POST',";
echo "      body: formData";
echo "    })";
echo "    .then(response => response.json())";
echo "    .then(data => alert(JSON.stringify(data, null, 2)))";
echo "    .catch(error => alert('Errore: ' + error));";
echo "  }";
echo "}";

echo "function testUnflagAPI() {";
echo "  const id = prompt('Inserisci ID anomalia da unflaggare:');";
echo "  if (id) {";
echo "    const formData = new FormData();";
echo "    formData.append('action', 'unflag_anomalia');";
echo "    formData.append('id', id);";
echo "    ";
echo "    fetch('api_anomalie.php', {";
echo "      method: 'POST',";
echo "      body: formData";
echo "    })";
echo "    .then(response => response.json())";
echo "    .then(data => alert(JSON.stringify(data, null, 2)))";
echo "    .catch(error => alert('Errore: ' + error));";
echo "  }";
echo "}";
echo "</script>";

echo "</body></html>";
?>