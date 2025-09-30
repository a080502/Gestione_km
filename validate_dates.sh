#!/bin/bash

# Script per validare tutte le date nei file SQL
echo "🔍 Validazione date nei file SQL..."

# Funzione per controllare se una data è valida
validate_date() {
    local date_string="$1"
    
    # Verifica formato YYYY-MM-DD
    if [[ ! $date_string =~ ^[0-9]{4}-[0-9]{2}-[0-9]{2}$ ]]; then
        return 1
    fi
    
    # Estrai anno, mese e giorno
    year=$(echo $date_string | cut -d'-' -f1)
    month=$(echo $date_string | cut -d'-' -f2)
    day=$(echo $date_string | cut -d'-' -f3)
    
    # Rimuovi zeri iniziali
    month=$((10#$month))
    day=$((10#$day))
    
    # Controlla range base
    if [ $month -lt 1 ] || [ $month -gt 12 ]; then
        return 1
    fi
    
    if [ $day -lt 1 ] || [ $day -gt 31 ]; then
        return 1
    fi
    
    # Giorni per mese
    case $month in
        2) # Febbraio
            # Controllo anno bisestile
            if [ $((year % 4)) -eq 0 ] && ([ $((year % 100)) -ne 0 ] || [ $((year % 400)) -eq 0 ]); then
                max_day=29
            else
                max_day=28
            fi
            ;;
        4|6|9|11) # Aprile, Giugno, Settembre, Novembre
            max_day=30
            ;;
        *) # Gennaio, Marzo, Maggio, Luglio, Agosto, Ottobre, Dicembre
            max_day=31
            ;;
    esac
    
    if [ $day -gt $max_day ]; then
        return 1
    fi
    
    return 0
}

# Trova tutti i file SQL
sql_files=$(find /workspaces/Gestione_km -name "*.sql" -type f)

echo "📁 File SQL trovati:"
echo "$sql_files"
echo ""

errors_found=0

# Per ogni file SQL
for file in $sql_files; do
    echo "🔎 Controllando: $file"
    
    # Estrai tutte le date nel formato YYYY-MM-DD
    dates=$(grep -oE "'[0-9]{4}-[0-9]{2}-[0-9]{2}'" "$file" | sort | uniq)
    
    if [ -n "$dates" ]; then
        for date_with_quotes in $dates; do
            # Rimuovi le virgolette
            date_clean=$(echo $date_with_quotes | tr -d "'")
            
            if ! validate_date "$date_clean"; then
                echo "❌ Data non valida trovata: $date_clean in $file"
                grep -n "$date_with_quotes" "$file"
                errors_found=$((errors_found + 1))
            fi
        done
    fi
done

echo ""
if [ $errors_found -eq 0 ]; then
    echo "✅ Tutte le date sono valide!"
    exit 0
else
    echo "❌ Trovate $errors_found date non valide!"
    exit 1
fi