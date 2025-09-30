#!/bin/bash

# Script di utilità per PHP CodeSniffer
# Utilizzo: ./phpcs-utils.sh [check|fix|check-all|fix-all] [file]

PHPCS="./vendor/bin/phpcs"
PHPCBF="./vendor/bin/phpcbf"
CONFIG="./phpcs.xml"

case "$1" in
    "check")
        if [ -n "$2" ]; then
            echo "🔍 Controllo del file: $2"
            $PHPCS --standard=$CONFIG "$2"
        else
            echo "❌ Specificare un file: ./phpcs-utils.sh check file.php"
            exit 1
        fi
        ;;
    "fix")
        if [ -n "$2" ]; then
            echo "🔧 Correzione del file: $2"
            $PHPCBF --standard=$CONFIG "$2"
            echo "✅ Correzione completata! Controlla di nuovo:"
            $PHPCS --standard=$CONFIG "$2"
        else
            echo "❌ Specificare un file: ./phpcs-utils.sh fix file.php"
            exit 1
        fi
        ;;
    "check-all")
        echo "🔍 Controllo di tutti i file PHP..."
        $PHPCS --standard=$CONFIG .
        ;;
    "fix-all")
        echo "🔧 Correzione di tutti i file PHP..."
        $PHPCBF --standard=$CONFIG .
        echo "✅ Correzione completata! Controlla tutti i file:"
        $PHPCS --standard=$CONFIG .
        ;;
    *)
        echo "📋 Utilizzo: ./phpcs-utils.sh [check|fix|check-all|fix-all] [file]"
        echo ""
        echo "Comandi disponibili:"
        echo "  check <file>    - Controlla un file specifico"
        echo "  fix <file>      - Corregge un file specifico"
        echo "  check-all       - Controlla tutti i file PHP"
        echo "  fix-all         - Corregge tutti i file PHP"
        echo ""
        echo "Esempi:"
        echo "  ./phpcs-utils.sh check index.php"
        echo "  ./phpcs-utils.sh fix config.php"
        echo "  ./phpcs-utils.sh check-all"
        ;;
esac