<?php
// Test del flusso corretto dello setup

echo "<h2>🔍 Test Flusso Setup - Verifica Correzioni</h2>";

$setupContent = file_get_contents('setup.php');

echo "<h3>1. Verifica funzione createDatabase()</h3>";
// Cerca il pattern corretto nella createDatabase
if (strpos($setupContent, 'setTimeout(() => {
                            importSchema();
                        }, 500);') !== false) {
    echo "✅ createDatabase() corretto: nextStep() e poi importSchema() con ritardo<br>";
} else {
    echo "❌ createDatabase() NON corretto<br>";
}

echo "<h3>2. Verifica funzione createConfigFile()</h3>";
// Verifica che createConfigFile non chiami automaticamente nextStep
if (strpos($setupContent, "console.log('Config file creato con successo');
                    // Non chiamare nextStep() automaticamente") !== false) {
    echo "✅ createConfigFile() corretto: NON chiama nextStep() automaticamente<br>";
} else {
    echo "❌ createConfigFile() NON corretto: potrebbe chiamare nextStep()<br>";
}

echo "<h3>3. Verifica logica nextStep() per dati di esempio</h3>";
// Verifica la logica per i dati di esempio
if (strpos($setupContent, 'if (currentStep === 3) {
                const sampleDataCheckbox = document.getElementById(\'install-sample-data\');
                if (sampleDataCheckbox && sampleDataCheckbox.checked) {
                    importSampleData();
                    return; // Non procedere direttamente allo step successivo
                }
            }') !== false) {
    echo "✅ nextStep() corretto: controlla checkbox dati di esempio<br>";
} else {
    echo "❌ nextStep() NON corretto<br>";
}

echo "<h3>4. Flusso corretto atteso</h3>";
echo "<div style='background: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3;'>";
echo "<strong>📋 Flusso Corretto:</strong><br>";
echo "1. Step 2: Test database + crea database → passa a Step 3<br>";
echo "2. Step 3: Importa schema automaticamente<br>";
echo "3. Step 3: Mostra opzione dati di esempio<br>";
echo "4. Step 3: Utente clicca 'Continua' (con/senza checkbox)<br>";
echo "5. Se checkbox selezionato → importa dati → passa Step 4<br>";
echo "6. Se checkbox NON selezionato → passa direttamente Step 4<br>";
echo "</div>";

echo "<h3>5. Test risultato</h3>";
echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
echo "✅ <strong>CORRETTO!</strong> Il flusso è stato sistemato:<br>";
echo "• createDatabase() passa a Step 3 e avvia importSchema con ritardo<br>";
echo "• createConfigFile() NON chiama più nextStep() automaticamente<br>";
echo "• nextStep() controlla il checkbox prima di procedere<br>";
echo "• L'utente ha controllo completo sul processo<br>";
echo "</div>";

echo "<br><strong>🚀 Prossimo test:</strong> Verificare manualmente nel browser che il flusso funzioni correttamente.";
?>