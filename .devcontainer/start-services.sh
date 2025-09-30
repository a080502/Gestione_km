#!/bin/bash

# Script per avviare automaticamente i servizi
echo "🔄 Avvio dei servizi..."

# Avvia MySQL
echo "🗄️  Avvio MySQL..."
sudo service mysql start

# Configura l'utente root MySQL se necessario
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'root';" 2>/dev/null || true
sudo mysql -e "FLUSH PRIVILEGES;" 2>/dev/null || true

# Avvia Apache
echo "🌐 Avvio Apache..."
sudo service apache2 start

# Verifica che i servizi siano attivi
echo "✅ Verifica stato servizi:"
if sudo service mysql status | grep -q "running"; then
    echo "   ✓ MySQL: attivo"
else
    echo "   ✗ MySQL: errore"
fi

if sudo service apache2 status | grep -q "running"; then
    echo "   ✓ Apache: attivo"
else
    echo "   ✗ Apache: errore"
fi

# Mostra informazioni utili
echo ""
echo "🎉 Ambiente pronto!"
echo "   📂 Document Root: /workspaces/Gestione_km"
echo "   🌐 Web Server: http://localhost"
echo "   🗄️  MySQL: localhost:3306 (user: root, password: root)"
echo ""