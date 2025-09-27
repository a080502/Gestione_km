=== SISTEMA BACKUP GESTIONE KM ===

Questo archivio contiene un backup completo del sistema Gestione KM.

STRUTTURA BACKUP:
===============
📁 database/       → File SQL del database
📁 website/        → Tutti i file del sito web
📄 README_BACKUP.txt → Questo file con le istruzioni

CONTENUTO DATABASE:
==================
✅ Struttura completa di tutte le tabelle
✅ Tutti i dati delle registrazioni
✅ Configurazioni utenti e sistema
✅ Dati filiali e target

CONTENUTO FILES:
================
✅ Codice PHP dell'applicazione
✅ File di configurazione
✅ Immagini e loghi
✅ Fogli di stile CSS
✅ Script JavaScript
✅ File README e documentazione

ISTRUZIONI RIPRISTINO:
=====================

STEP 1 - PREPARAZIONE:
• Estrarre completamente questo archivio ZIP
• Verificare di avere accesso al server web (Apache/Nginx)
• Verificare di avere accesso al database MySQL/MariaDB

STEP 2 - RIPRISTINO DATABASE:
• Creare un database vuoto MySQL/MariaDB
• Importare il file SQL dalla cartella "database/":
  
  Metodo 1 - phpMyAdmin:
  → Aprire phpMyAdmin
  → Selezionare il database creato
  → Andare su "Importa"
  → Selezionare il file .sql dalla cartella database/
  → Cliccare "Esegui"
  
  Metodo 2 - Linea di comando:
  mysql -u username -p nome_database < database/db_backup_XXXX.sql

STEP 3 - RIPRISTINO FILES:
• Caricare tutti i file dalla cartella "website/" sul server web
• Verificare che la cartella "immagini/" abbia permessi di scrittura (755)
• Verificare che la cartella "backups/" abbia permessi di scrittura (755)

STEP 4 - CONFIGURAZIONE:
• Modificare il file "editable_config.php" con i dati del nuovo database:
  - DB_HOST (di solito "localhost")
  - DB_USERNAME (nome utente database)  
  - DB_PASSWORD (password database)
  - DB_NAME (nome del database creato)
  
• Se necessario, aggiornare altre configurazioni come:
  - SITE_TITLE
  - COMPANY_NAME
  - ITEMS_PER_PAGE

STEP 5 - TEST FINALE:
• Aprire il sito nel browser
• Tentare il login con le credenziali esistenti
• Verificare che i dati siano presenti
• Testare le funzionalità principali

RISOLUZIONE PROBLEMI:
====================

🔧 Errore connessione database:
• Verificare credenziali in editable_config.php
• Controllare che il database sia avviato
• Verificare che l'utente abbia i permessi sul database

🔧 Errori di permessi files:
• Impostare permessi 755 sulle cartelle
• Impostare permessi 644 sui file PHP
• Verificare ownership dei file (www-data o apache)

🔧 Pagina bianca o errori PHP:
• Attivare display_errors in PHP
• Controllare i log di errore del server web
• Verificare versione PHP (compatibile con 7.4+)

🔧 Mancano immagini o files:
• Verificare che tutti i file siano stati caricati
• Controllare la struttura delle cartelle
• Verificare permessi di lettura

SUPPORTO TECNICO:
================
Per assistenza contattare l'amministratore del sistema
o consultare la documentazione completa nel README.md

Data backup: {TIMESTAMP}
Versione sistema: Gestione KM v2.0
Database: {DATABASE_NAME}

=== FINE ISTRUZIONI ===