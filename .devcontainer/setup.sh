#!/bin/bash

# Script di setup per installazione iniziale dei servizi
echo "🚀 Configurazione iniziale del container..."

# Aggiorna i pacchetti
sudo apt-get update

# Installa Apache, MySQL e PHP
echo "📦 Installazione di Apache, MySQL e moduli PHP..."
sudo apt-get install -y \
    apache2 \
    mysql-server \
    libapache2-mod-php \
    php-mysql \
    php-curl \
    php-gd \
    php-xml \
    php-zip \
    php-mbstring \
    php-json \
    php-bcmath

# Abilita il modulo PHP in Apache
sudo a2enmod php8.3
sudo a2enmod rewrite

# Configura Apache per servire dal workspace
sudo tee /etc/apache2/sites-available/workspace.conf > /dev/null <<EOF
<VirtualHost *:80>
    DocumentRoot /workspaces/Gestione_km
    
    <Directory /workspaces/Gestione_km>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/workspace_error.log
    CustomLog \${APACHE_LOG_DIR}/workspace_access.log combined
</VirtualHost>
EOF

# Disabilita il sito default e abilita il nostro
sudo a2dissite 000-default
sudo a2ensite workspace

# Configura MySQL per essere più permissivo in ambiente di sviluppo
sudo tee /etc/mysql/mysql.conf.d/99-dev.cnf > /dev/null <<EOF
[mysqld]
bind-address = 0.0.0.0
skip-name-resolve
EOF

echo "✅ Setup completato!"