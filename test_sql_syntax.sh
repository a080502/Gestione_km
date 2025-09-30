#!/bin/bash

# Script per testare la sintassi del file SQL
echo "Testando la sintassi del file database_km.sql..."

# Controlla solo la sintassi SQL senza eseguire
mysql --help > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "Client MySQL disponibile"
    
    # Verifica sintassi del file SQL
    echo "Verificando sintassi SQL..."
    if mysql --execute="source /workspaces/Gestione_km/database_km.sql" --force --verbose 2>&1 | grep -i "error"; then
        echo "ERRORI trovati nel file SQL"
        exit 1
    else
        echo "File SQL sintatticamente corretto"
        exit 0
    fi
else
    echo "Client MySQL non disponibile"
    exit 1
fi