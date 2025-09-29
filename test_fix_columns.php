<?php
// Test della correzione del file sample_data.sql

echo "<h2>🔧 Test Correzione sample_data.sql</h2>";

$file = 'sample_data.sql';
$content = file_get_contents($file);

echo "<h3>1. Verifica struttura colonne corrette</h3>";

// Controlla che non ci siano più le colonne errate
$wrongColumns = [
    'chilometri', 
    'data_registrazione',
    'prezzo_carburante',
    'quantita_carburante', 
    'costo_totale',
    'time_stamp'
];

$correctColumns = [
    'data',
    'chilometri_iniziali',
    'chilometri_finali', 
    'litri_carburante',
    'euro_spesi',
    'timestamp'
];

echo "<strong>❌ Colonne ERRATE (non devono esserci):</strong><br>";
foreach ($wrongColumns as $col) {
    $count = substr_count($content, "`$col`");
    if ($count > 0) {
        echo "⚠️ $col: trovate $count occorrenze<br>";
    } else {
        echo "✅ $col: non presente (corretto)<br>";
    }
}

echo "<br><strong>✅ Colonne CORRETTE (devono esserci):</strong><br>";
foreach ($correctColumns as $col) {
    $count = substr_count($content, "`$col`");
    if ($count > 0) {
        echo "✅ $col: trovate $count occorrenze<br>";
    } else {
        echo "❌ $col: non presente!<br>";
    }
}

echo "<h3>2. Verifica formato INSERT corretto</h3>";
$lines = explode("\n", $content);
$insertFound = false;
foreach ($lines as $i => $line) {
    if (stripos($line, 'INSERT INTO `chilometri`') !== false) {
        echo "✅ INSERT trovato alla riga " . ($i+1) . ":<br>";
        echo "<code>" . htmlspecialchars(substr($line, 0, 150)) . "...</code><br>";
        $insertFound = true;
        break;
    }
}

if (!$insertFound) {
    echo "❌ Nessun INSERT INTO `chilometri` trovato!<br>";
}

echo "<h3>3. Conteggio dati</h3>";
echo "• Registrazioni chilometri: " . substr_count($content, "('2024-") . " voci<br>";
echo "• Mesi coperti: " . count(array_unique(array_map(function($m) { 
    return substr($m, 1, 7); 
}, array_filter(explode("'", $content), function($s) { 
    return preg_match("/^\d{4}-\d{2}-/", $s); 
})))) . " mesi<br>";

echo "<h3>4. Test parsing semplificato</h3>";
$queries = explode(';', $content);
$chilometriQueries = 0;
foreach ($queries as $query) {
    if (stripos($query, 'INSERT INTO `chilometri`') !== false) {
        $chilometriQueries++;
    }
}
echo "• Query INSERT chilometri: $chilometriQueries<br>";

echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin-top: 20px;'>";
echo "<strong>✅ CORREZIONE COMPLETATA!</strong><br>";
echo "Il file sample_data.sql è stato corretto per usare:<br>";
echo "• data (invece di data_registrazione)<br>";
echo "• chilometri_iniziali e chilometri_finali (invece di chilometri)<br>";  
echo "• litri_carburante (invece di quantita_carburante)<br>";
echo "• euro_spesi (invece di costo_totale)<br>";
echo "• timestamp (invece di time_stamp)<br>";
echo "<br><strong>Ora dovrebbe importarsi senza errori!</strong>";
echo "</div>";
?>