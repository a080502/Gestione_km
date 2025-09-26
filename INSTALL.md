# 🚀 Guida Installazione Rapida

## Setup Automatico (Raccomandato)

### 1. Accesso Setup Wizard
Visita: `http://tuodominio.com/setup.php`

Il wizard ti guiderà attraverso:
- ✅ Controllo prerequisiti di sistema
- ⚙️ Configurazione database
- 📊 Importazione schema
- 👤 Creazione utente admin
- 🔧 Generazione file configurazione

### 2. Dopo l'installazione
- 🗑️ **IMPORTANTE**: Elimina `setup.php` per sicurezza
- 🔐 Accedi con le credenziali admin create
- 🏢 Configura filiali e divisioni
- 👥 Aggiungi utenti del sistema

---

## Setup Manuale

### Prerequisiti
```bash
# Versioni minime richieste
PHP >= 8.0
MySQL/MariaDB >= 8.0

# Estensioni PHP necessarie
php-mysqli, php-session, php-json, php-mbstring, php-fileinfo, php-gd
```

### 1. Database Setup
```sql
-- Crea database
CREATE DATABASE chilometri CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Importa schema
mysql -u username -p chilometri < database_km.sql
```

### 2. Configurazione
```bash
# Copia il file di configurazione
cp editable_config.php.example editable_config.php

# Modifica le credenziali database
nano editable_config.php
```

### 3. Permessi
```bash
# Imposta permessi corretti
chmod 755 uploads/
chmod 755 uploads/cedolini/
chmod 644 editable_config.php
```

### 4. Primo Utente Admin
```sql
INSERT INTO utenti (username, password, targa_mezzo, divisione, filiale, livello, Nome, Cognome) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '*', '', '', '1', 'Admin', 'Sistema');
-- Password: password (cambiala immediatamente!)
```

---

## Configurazione Web Server

### Apache (.htaccess)
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

### Nginx
```nginx
server {
    listen 80;
    server_name tuodominio.com;
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

    # Sicurezza
    location ~ /\. {
        deny all;
    }
    
    location ~* \.(log|sql)$ {
        deny all;
    }
}
```

---

## Verifica Installazione

### Test Connessione
```php
<?php
include_once 'config.php';
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
echo "Connessione database: OK";
?>
```

### Test Upload
```bash
# Verifica permessi upload
ls -la uploads/cedolini/
# Dovrebbe mostrare: drwxr-xr-x
```

### Test Login
1. Vai a `http://tuodominio.com/login.php`
2. Usa le credenziali admin create
3. Verifica accesso alla dashboard

---

## Troubleshooting

### Problema: Database connection failed
**Soluzione**: Verifica credenziali in `editable_config.php`

### Problema: Upload files non funziona
**Soluzione**: 
```bash
chmod 755 uploads/cedolini/
chown www-data:www-data uploads/cedolini/
```

### Problema: Sessioni non persistono
**Soluzione**: Verifica configurazione PHP sessions

### Problema: Errori PHP
**Soluzione**: Abilita error logging in `config.php`

---

## Sicurezza Post-Installazione

### 1. Elimina file temporanei
```bash
rm setup.php
rm INSTALL.md
```

### 2. Cambia password admin
- Login come admin
- Vai in "Gestione Utenti"
- Modifica password admin

### 3. Configura backup
```bash
# Script backup giornaliero
#!/bin/bash
mysqldump -u user -p database > backup_$(date +%Y%m%d).sql
tar -czf files_$(date +%Y%m%d).tar.gz uploads/
```

### 4. Aggiorna permessi
```bash
# Solo lettura per config
chmod 644 editable_config.php

# No accesso diretto ai file sensibili  
echo "deny from all" > .htaccess
```

---

## Supporto

- 📖 **Documentazione**: README.md
- 🐛 **Bug Report**: GitHub Issues
- 💬 **Supporto**: [support@yourcompany.com](mailto:support@yourcompany.com)

---

**🎉 Installazione completata! Il sistema è pronto all'uso.**