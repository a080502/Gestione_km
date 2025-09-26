# 🚛 Sistema di Gestione Chilometri - Documentazione Completa

> **Versione**: 1.0  
> **Data**: 26 Settembre 2025  
> **Repository**: [https://github.com/a080502/Gestione_km](https://github.com/a080502/Gestione_km)

## 📋 Indice

- [Panoramica](#panoramica)
- [Architettura del Sistema](#architettura-del-sistema)
- [Struttura Database](#struttura-database)
- [Funzionalità Principali](#funzionalità-principali)
- [Struttura File](#struttura-file)
- [Installazione e Setup](#installazione-e-setup)
- [Guide d'Uso](#guide-duso)
- [Sistema di Autorizzazioni](#sistema-di-autorizzazioni)
- [Progetti Laravel Integrati](#progetti-laravel-integrati)
- [Sicurezza](#sicurezza)
- [API e Integrazione](#api-e-integrazione)
- [Manutenzione](#manutenzione)

---

## 🎯 Panoramica

Il **Sistema di Gestione Chilometri** è un'applicazione web completa progettata per il monitoraggio e la gestione dei chilometri percorsi dai veicoli aziendali. Il sistema offre funzionalità complete per l'inserimento dati, generazione report, gestione utenti e controllo dei target chilometrici.

### 🚀 Caratteristiche Principali

- **📱 Mobile-First Design**: Interfaccia ottimizzata per dispositivi mobili
- **🔐 Sistema di Autenticazione Sicuro**: Login con hash delle password
- **📊 Report Avanzati**: Generazione report PDF con TCPDF
- **👥 Gestione Multi-Utente**: Sistema di autorizzazioni a livelli
- **🎯 Target Management**: Gestione obiettivi chilometrici annuali
- **💰 Controllo Costi**: Monitoraggio costi carburante e sforamento target
- **📸 Upload Cedolini**: Caricamento foto ricevute carburante
- **🏢 Multi-Filiale**: Supporto per multiple filiali e divisioni

---

## 🏗️ Architettura del Sistema

### Stack Tecnologico

- **Backend**: PHP 8.2+ con MySQLi
- **Database**: MySQL/MariaDB
- **Frontend**: Bootstrap 5.3, JavaScript vanilla
- **PDF Generation**: TCPDF Library
- **Dependency Management**: Composer
- **Version Control**: Git

### Pattern Architetturale

Il sistema segue un'architettura **MVC semplificata** con:
- **Model**: Query parametrizzate in `/query/`
- **View**: Template PHP con inclusioni modulari
- **Controller**: Logic embedding nei file principali

---

## 🗄️ Struttura Database

### Schema Database: `chilometri`

#### Tabella: `utenti`
```sql
CREATE TABLE `utenti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `targa_mezzo` varchar(255) NOT NULL,
  `divisione` varchar(255) NOT NULL,
  `filiale` varchar(255) NOT NULL,
  `livello` varchar(255) NOT NULL,
  `Nome` varchar(255) NOT NULL,
  `Cognome` varchar(255) NOT NULL,
  `time_stamp` varchar(255) NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
);
```

**Campi Principali:**
- `username`: Identificativo unico utente
- `password`: Hash bcrypt della password
- `targa_mezzo`: Targa del veicolo assegnato
- `divisione`: Codice divisione (es. SP09, SP10)
- `filiale`: Nome filiale di appartenenza
- `livello`: Livello autorizzazione (1=Admin, 2=Responsabile, 3=Utente)

#### Tabella: `chilometri`
```sql
CREATE TABLE `chilometri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` date NOT NULL,
  `chilometri_iniziali` int(11) NOT NULL,
  `chilometri_finali` float NOT NULL,
  `note` text DEFAULT NULL,
  `litri_carburante` varchar(255) DEFAULT NULL,
  `euro_spesi` decimal(10,2) DEFAULT NULL,
  `percorso_cedolino` varchar(255) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `targa_mezzo` varchar(255) NOT NULL,
  `divisione` varchar(255) NOT NULL,
  `filiale` varchar(255) NOT NULL,
  `livello` int(1) NOT NULL,
  `timestamp` varchar(255) NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
);
```

**Funzionalità:**
- Registrazione completa rifornimenti
- Calcolo automatico km percorsi
- Upload foto cedolini carburante
- Tracciamento temporale operazioni

#### Tabella: `target_annuale`
```sql
CREATE TABLE `target_annuale` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `anno` int(11) NOT NULL,
  `target_chilometri` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `targa_mezzo` varchar(255) NOT NULL,
  `divisione` varchar(255) NOT NULL,
  `filiale` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
);
```

#### Tabella: `costo_extra`
```sql
CREATE TABLE `costo_extra` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `targa_mezzo` varchar(255) NOT NULL,
  `costo` decimal(10,2) DEFAULT NULL,
  `time_stamp` varchar(255) NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
);
```

#### Tabella: `filiali`
```sql
CREATE TABLE `filiali` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `divisione` varchar(255) DEFAULT NULL,
  `filiale` varchar(255) DEFAULT NULL,
  `time_stamp` varchar(255) NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
);
```

#### Tabella: `livelli_autorizzazione`
```sql
CREATE TABLE `livelli_autorizzazione` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `livello` varchar(255) NOT NULL,
  `descrizione_livello` varchar(255) NOT NULL,
  `_libero` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
);
```

---

## ⚙️ Funzionalità Principali

### 🔑 Sistema di Autenticazione

#### Login (`login.php`)
- **Sicurezza**: Conversione automatica username in minuscolo
- **UI/UX**: Toggle password visibility con Font Awesome
- **Validazione**: Controllo credenziali con hash bcrypt
- **Responsive**: Design mobile-first con Bootstrap 5

#### Verifica Login (`verifica_login.php`)
- **Prepared Statements**: Protezione SQL Injection
- **Session Management**: Gestione sessioni PHP sicure
- **Error Handling**: Messaggi di errore user-friendly

### 📊 Inserimento Dati

#### Form Rifornimento (`index.php`)
- **Auto-fill**: Compilazione automatica km iniziali
- **Validazione Real-time**: Controllo coerenza chilometri
- **Upload Cedolini**: Caricamento foto ricevute
- **Mobile Optimized**: Interfaccia touch-friendly

**Funzionalità Avanzate:**
- Validazione JavaScript km finali > km iniziali
- Preview immagini al passaggio del mouse
- Input type="number" con inputmode per mobile
- Gestione errori con feedback visivo Bootstrap

### 📈 Visualizzazione e Report

#### Report Mensile (`report_mese.php`)
- **Filtri Avanzati**: Per mese, anno, utente, filiale
- **Calcoli Automatici**: Km percorsi, consumo medio
- **Export PDF**: Report formattati con TCPDF
- **Statistiche**: Grafici e metriche performance

#### Visualizza Registrazioni (`visualizza.php`)
- **Paginazione**: Navigazione ottimizzata grandi dataset
- **Ordinamento**: Per data, utente, filiale
- **Filtri**: Ricerca avanzata multi-criterio
- **Preview Cedolini**: Visualizzazione immagini hover

### 👥 Gestione Utenti

#### Sistema Multi-Livello (`gestisci_utenti.php`)
- **Livello 1 (Admin)**: Accesso completo sistema
- **Livello 2 (Responsabile)**: Gestione filiale
- **Livello 3 (Utente)**: Solo propri dati

**Operazioni:**
- Creazione/modifica/eliminazione utenti
- Assegnazione targhe e filiali
- Reset password amministrativo
- Esportazione dati utenti

### 🎯 Target e Obiettivi

#### Gestione Target (`gestione_target_annuale.php`)
- **Impostazione Obiettivi**: Target km annuali per utente/veicolo
- **Monitoraggio Progress**: Confronto realizzato vs target
- **Alert System**: Notifiche sforamento target
- **Reportistica**: Analisi performance vs obiettivi

### 💰 Controllo Costi

#### Costi Extra (`gestione_costo_extra.php`)
- **Parametrizzazione**: Costo per km di sforamento
- **Calcolo Automatico**: Penali per superamento target
- **Report Costi**: Analisi spese carburante e penali

---

## 📁 Struttura File

```
/workspaces/Gestione_km/
├── 📁 Cestino/                      # File di backup
│   ├── _HOLD_gestione_costo_extra.php
│   ├── _HOLD_modifica_costo_extra.php
│   └── ...
├── 📁 css/                          # Fogli di stile
│   └── global.css
├── 📁 denis/                        # Progetto Laravel Livewire
│   ├── app/
│   ├── config/
│   ├── resources/
│   └── composer.json
├── 📁 freeCodeGram/                 # Progetto Laravel Base
│   ├── app/
│   ├── config/
│   └── composer.json
├── 📁 immagini/                     # Assets grafici
│   └── logo.png
├── 📁 include/                      # Componenti riusabili
│   └── menu.php                     # Menu di navigazione
├── 📁 query/                        # Funzioni database
│   ├── qutenti.php                  # Query utenti
│   ├── q_costo_extra.php           # Query costi extra
│   ├── q_target_km.php             # Query target km
│   └── query_filtrata.php          # Query filtrate
├── 📁 tcpdf/                        # Libreria PDF
├── 📁 uploads/                      # File caricati
│   └── cedolini/                    # Foto ricevute
├── 📁 vendor/                       # Dipendenze Composer
│
├── 🔧 config.php                    # Configurazione database
├── 🔧 editable_config.php          # Configurazione editabile
├── 📊 database_km.sql               # Schema database
├── 🏠 index.php                     # Homepage - Inserimento dati
├── 🔐 login.php                     # Pagina login
├── 📝 inserisci.php                 # Processing inserimento
├── 👁️ visualizza.php                # Visualizzazione dati
├── 📈 report_mese.php               # Report mensili
├── 👥 gestisci_utenti.php           # Gestione utenti
├── 🏢 gestisci_filiali.php          # Gestione filiali
├── 🎯 gestione_target_annuale.php   # Gestione target
├── 💰 gestione_costo_extra.php      # Gestione costi
├── ⚙️ gestione_dati_server.php      # Configurazione server
├── 📄 create_pdf.php                # Generazione PDF
├── 📧 send_email.php                # Invio email
├── 🔄 aggiorna.php                  # Aggiornamento record
├── 🗑️ cancella.php                  # Eliminazione record
├── 📦 composer.json                 # Dipendenze PHP
└── 📱 manifest.json                 # Web App Manifest
```

---

## 🚀 Installazione e Setup

### Prerequisiti

- **PHP**: 8.2 o superiore
- **MySQL/MariaDB**: 8.0 o superiore  
- **Web Server**: Apache/Nginx
- **Composer**: Per gestione dipendenze

### Passo 1: Clone Repository

```bash
git clone https://github.com/a080502/Gestione_km.git
cd Gestione_km
```

### Passo 2: Installazione Dipendenze

```bash
composer install
```

### Passo 3: Configurazione Database

1. **Crea Database**:
   ```sql
   CREATE DATABASE chilometri CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
   ```

2. **Importa Schema**:
   ```bash
   mysql -u username -p chilometri < database_km.sql
   ```

3. **Configura Connessione**:
   Crea file `editable_config.php`:
   ```php
   <?php
   return [
       'DB_HOST' => 'localhost',
       'DB_USERNAME' => 'your_username',
       'DB_PASSWORD' => 'your_password',
       'DB_NAME' => 'chilometri'
   ];
   ```

### Passo 4: Configurazione Permessi

```bash
chmod 755 uploads/
chmod 755 uploads/cedolini/
chmod 644 editable_config.php
```

### Passo 5: Setup Web Server

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security Headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/Gestione_km;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## 📖 Guide d'Uso

### 👨‍💼 Per Amministratori

#### Creazione Primo Utente Admin
```sql
INSERT INTO utenti (username, password, targa_mezzo, divisione, filiale, livello, Nome, Cognome) 
VALUES ('admin', '$2y$10$example_hash', '*', '', '', '1', 'Admin', 'System');
```

#### Gestione Filiali
1. Accedere a **Gestione Filiali**
2. Aggiungere divisioni e filiali
3. Associare utenti alle strutture

#### Impostazione Target
1. Aprire **Gestione Target Km**
2. Impostare obiettivi annuali per utente/veicolo
3. Definire costi sforamento

### 👨‍🔧 Per Responsabili

#### Monitoraggio Team
1. Accesso report filiale
2. Controllo avanzamento target
3. Approvazione/correzione dati

#### Gestione Utenti Filiale
1. Creazione nuovi utenti
2. Assegnazione veicoli
3. Modifica autorizzazioni

### 👷‍♂️ Per Utenti Base

#### Inserimento Rifornimento
1. **Login**: Inserire credenziali
2. **Data**: Selezionare data rifornimento
3. **Chilometri**: 
   - Iniziali (auto-compilati dall'ultimo inserimento)
   - Finali (inserimento manuale)
4. **Carburante**: Litri e importo speso
5. **Cedolino**: Upload foto ricevuta (opzionale)
6. **Invio**: Conferma inserimento

#### Visualizzazione Storico
1. Menu → **Tutte le Registrazioni**
2. Navigazione con paginazione
3. Filtri per periodo/tipo

---

## 🔐 Sistema di Autorizzazioni

### Livelli Utente

#### Livello 1 - Amministratore
**Autorizzazioni Complete:**
- ✅ Gestione tutti gli utenti
- ✅ Configurazione sistema
- ✅ Accesso tutti i dati
- ✅ Modifica configurazioni server
- ✅ Gestione filiali e divisioni
- ✅ Export completo dati

#### Livello 2 - Responsabile
**Gestione Filiale:**
- ✅ Visualizzazione utenti propria filiale
- ✅ Gestione target filiale
- ✅ Report aggregati filiale
- ❌ Modifica configurazioni globali
- ❌ Accesso altre filiali

#### Livello 3 - Utente Base
**Solo Propri Dati:**
- ✅ Inserimento rifornimenti personali
- ✅ Visualizzazione storico personale  
- ✅ Report personali
- ❌ Gestione utenti
- ❌ Accesso dati altri utenti

### Controlli di Sicurezza

#### Validazione Session
```php
// Ogni pagina protetta
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
```

#### Controllo Livello
```php
// Funzioni amministrative
if ($utente_data['livello'] != '1') {
    header("Location: unauthorized.php");
    exit();
}
```

#### Prepared Statements
```php
// Tutte le query
$sql = $conn->prepare("SELECT * FROM utenti WHERE username = ?");
$sql->bind_param("s", $username);
$sql->execute();
```

---

## 🚀 Progetti Laravel Integrati

### Denis - Laravel Livewire Starter Kit

**Percorso**: `/denis/`
**Descrizione**: Progetto avanzato con Livewire e Flux UI

#### Caratteristiche:
- **Framework**: Laravel 12.0
- **UI**: Livewire Flux 2.0
- **Features**: 
  - Authentication completo
  - Dashboard interattiva
  - Componenti reattivi
  - Testing suite

#### Stack Tecnico:
```json
{
  "php": "^8.2",
  "laravel/framework": "^12.0",
  "livewire/flux": "^2.0",
  "livewire/volt": "^1.7.0"
}
```

#### Struttura:
```
denis/
├── app/
│   ├── Http/Controllers/Auth/
│   ├── Livewire/
│   ├── Models/
│   └── Providers/
├── resources/views/
│   ├── components/
│   ├── livewire/
│   └── layouts/
└── routes/
```

### FreeCodeGram - Laravel Base

**Percorso**: `/freeCodeGram/`
**Descrizione**: Progetto Laravel standard per sviluppi futuri

#### Caratteristiche:
- **Framework**: Laravel 12.0
- **Template**: Skeleton application base
- **Scopo**: Prototipazione rapida

---

## 🔒 Sicurezza

### Misure Implementate

#### 1. Autenticazione Sicura
- **Hash Password**: bcrypt con salt
- **Session Management**: PHP session secure
- **Login Throttling**: Protezione brute force

#### 2. Protezione Database
- **Prepared Statements**: Prevenzione SQL Injection
- **Input Validation**: Sanitizzazione dati
- **Error Handling**: Logging errori senza esposizione

#### 3. File Upload Sicuro
```php
// Validazione estensioni
$allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Controllo MIME type
$allowed_mime_types = ['image/jpeg', 'image/png', 'application/pdf'];

// Limite dimensione
$max_file_size = 5 * 1024 * 1024; // 5MB
```

#### 4. Headers di Sicurezza
```apache
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=63072000"
```

### Raccomandazioni Produzione

#### 1. Server Configuration
- **HTTPS**: Certificato SSL/TLS
- **PHP**: Configurazione sicura php.ini
- **Database**: Utente con privilegi minimi

#### 2. Backup Strategy
```bash
# Backup database giornaliero
0 2 * * * mysqldump -u backup_user -p database_name > /backups/$(date +\%Y\%m\%d)_backup.sql

# Backup files settimanale
0 3 * * 0 tar -czf /backups/files_$(date +\%Y\%m\%d).tar.gz /var/www/gestione_km/uploads/
```

#### 3. Monitoring
- **Log Analysis**: Analisi log accessi e errori
- **Performance**: Monitoring query database
- **Security**: Scansione vulnerabilità regolari

---

## 🔌 API e Integrazione

### Endpoint Interni

#### Gestione Filiali
```php
// GET /get_filiali.php
// Ritorna lista filiali per divisione
{
  "success": true,
  "data": [
    {"id": 1, "divisione": "SP09", "filiale": "TRENTO STORAGE"},
    {"id": 2, "divisione": "SP10", "filiale": "BORGO LARES"}
  ]
}
```

#### Report Data
```php
// POST /report_mese.php
// Parametri: mese, anno, filiale, formato
// Response: PDF stream o JSON data
```

### Integrazione Futura

#### API REST Pianificata
- **Authentication**: JWT tokens
- **Endpoints**: CRUD operations
- **Documentation**: OpenAPI/Swagger
- **Rate Limiting**: Protezione overuse

#### Webhook Support
- **Events**: Inserimento dati, sforamento target
- **Notifications**: Email, SMS, Slack
- **Third-party**: ERP, fleet management

---

## 🛠️ Manutenzione

### Routine Amministrative

#### Pulizia Database
```sql
-- Rimozione dati obsoleti (oltre 5 anni)
DELETE FROM chilometri WHERE data < DATE_SUB(NOW(), INTERVAL 5 YEAR);

-- Ottimizzazione tabelle
OPTIMIZE TABLE chilometri, utenti, target_annuale;
```

#### Cleanup Files
```bash
# Pulizia cedolini orfani
find uploads/cedolini/ -type f -mtime +365 -delete

# Pulizia log
find logs/ -name "*.log" -mtime +90 -delete
```

### Updates e Patches

#### Versioning Schema
- **Major**: Modifiche struttura database
- **Minor**: Nuove features
- **Patch**: Bug fixes, security updates

#### Deployment Process
1. **Backup**: Database e files
2. **Maintenance Mode**: Attivazione
3. **Update**: Deploy nuovo codice
4. **Migration**: Schema database
5. **Testing**: Verifica funzionalità
6. **Live**: Disattivazione maintenance

### Troubleshooting Comune

#### 1. Problemi di Connessione Database
```php
// Verifica configurazione
if ($conn->connect_error) {
    error_log("DB Connection failed: " . $conn->connect_error);
}
```

#### 2. Upload Files Fallisce
```bash
# Verifica permessi
chmod 755 uploads/cedolini/
chown www-data:www-data uploads/cedolini/
```

#### 3. Performance Issues
```sql
-- Analisi query lente
SHOW FULL PROCESSLIST;

-- Aggiunta indici
CREATE INDEX idx_data_username ON chilometri(data, username);
CREATE INDEX idx_targa_anno ON target_annuale(targa_mezzo, anno);
```

---

## 📊 Metriche e KPI

### Dashboard Amministratori

#### Utilizzo Sistema
- **Utenti Attivi**: Login ultimi 30 giorni
- **Registrazioni Giornaliere**: Media inserimenti
- **Storage Used**: Spazio occupato uploads
- **Performance**: Tempi risposta medi

#### Business Metrics
- **Target Achievement**: % raggiungimento obiettivi
- **Cost Analysis**: Spesa carburante trend
- **Fleet Utilization**: Km per veicolo
- **Efficiency Ratios**: Consumo/km medio

### Report Automatici

#### Daily Reports
- Riepilogo inserimenti giorno precedente
- Alert sforamento target
- Problemi sistema/errori

#### Weekly Reports  
- Andamento consumi settimanale
- Performance utenti/filiali
- Trend costi carburante

#### Monthly Reports
- Report completo mensile
- Analisi target vs realizzato
- Proiezioni fine anno
- Budget variance analysis

---

## 🤝 Supporto e Contributi

### Documentazione Tecnica

#### Code Standards
- **PHP**: PSR-12 coding standards
- **JavaScript**: ES6+ modern syntax
- **SQL**: ANSI SQL compatibility
- **Comments**: Inline documentation

#### Testing Strategy
- **Unit Tests**: Funzioni critiche
- **Integration Tests**: Workflow completi
- **Security Tests**: Penetration testing
- **Performance Tests**: Load testing

### Contribuzione

#### Development Workflow
1. **Fork**: Repository principale
2. **Branch**: Feature/bugfix branch
3. **Development**: Codifica e testing
4. **Pull Request**: Review e merge
5. **Deployment**: Release management

### Contatti

- **Repository**: [GitHub - Gestione_km](https://github.com/a080502/Gestione_km)
- **Issues**: GitHub Issues per bug report
- **Wiki**: Documentazione aggiuntiva
- **Releases**: Change log e versioning

---

**📝 Ultima Modifica**: 26 Settembre 2025  
**👨‍💻 Mantainer**: a080502  
**📄 Licenza**: Proprietaria - Uso Aziendale

---

> 🚀 **Sistema di Gestione Chilometri** - Soluzione completa per il monitoraggio fleet aziendale con tecnologie moderne e sicurezza enterprise-grade.