<?php
// Test script per verificare il funzionamento del PDF con POST
session_start();

// Simula una sessione valida
$_SESSION['username'] = 'denis';
$_SESSION['dati_utente'] = [
    'Nome' => 'Denis',
    'Cognome' => 'Test'
];

// Crea dati di test simulando molte righe
$test_data = [];
for ($i = 1; $i <= 50; $i++) {
    $test_data[] = [
        'Mese' => '2025-0' . (($i % 9) + 1),
        'Targa' => 'TEST' . sprintf('%03d', $i),
        'Utente' => 'user' . ($i % 5 + 1),
        'km_percorsi' => 100 + ($i * 10),
        'litri' => 50 + ($i * 2),
        'euro' => 100 + ($i * 5),
        'registrazioni' => ($i % 3) + 1,
        'km_finali_Mese' => 1000 + ($i * 100)
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test PDF POST</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .btn { 
            background-color: #007bff; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            margin: 10px 0;
        }
        .btn:hover { background-color: #0056b3; }
        .test-info { 
            background-color: #f8f9fa; 
            border: 1px solid #dee2e6; 
            padding: 15px; 
            border-radius: 4px; 
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Generazione PDF con POST</h1>
        
        <div class="test-info">
            <h3>Informazioni Test:</h3>
            <p><strong>Righe di test generate:</strong> <?php echo count($test_data); ?></p>
            <p><strong>Dimensione dati JSON:</strong> <?php echo strlen(json_encode($test_data)); ?> caratteri</p>
            <p><strong>Metodo utilizzato:</strong> POST (per evitare limiti URL)</p>
        </div>

        <form method="POST" action="create_pdf.php" target="_blank">
            <input type="hidden" name="rows" value="<?php echo htmlspecialchars(json_encode($test_data), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="username" value="denis">
            <button type="submit" class="btn">Genera PDF di Test (<?php echo count($test_data); ?> righe)</button>
        </form>

        <h3>Dati di Test (Prime 5 righe):</h3>
        <pre><?php echo json_encode(array_slice($test_data, 0, 5), JSON_PRETTY_PRINT); ?></pre>
        
        <p><a href="esportazione_dati.php" class="btn">Torna alla Pagina di Esportazione</a></p>
    </div>
</body>
</html>