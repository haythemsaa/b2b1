# 🚀 Production Deployment Guide - B2B Wholesale Platform

Guide complet pour déployer la plateforme B2B en production.

## 📋 Prérequis

### Serveur

**Configuration minimale recommandée:**
- **CPU**: 2 cores
- **RAM**: 4GB
- **Stockage**: 20GB SSD
- **Bande passante**: 100Mbps

**Configuration recommandée pour production:**
- **CPU**: 4+ cores
- **RAM**: 8GB+
- **Stockage**: 50GB+ SSD
- **Bande passante**: 1Gbps

### Software

- Ubuntu 22.04 LTS (ou supérieur)
- PHP 8.2+
- Nginx 1.18+
- MySQL 8.0+ ou PostgreSQL 13+
- Redis 6.0+
- Node.js 18+ (pour assets frontend si applicable)
- Composer 2.x
- Git

### Domaine & SSL

- Nom de domaine configuré
- Certificat SSL (Let's Encrypt recommandé)

## 🔧 Installation serveur

### 1. Mise à jour du système

```bash
sudo apt update && sudo apt upgrade -y
```

### 2. Installation PHP 8.2

```bash
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update

sudo apt install -y php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mysql php8.2-pgsql php8.2-redis php8.2-xml \
    php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd \
    php8.2-intl php8.2-bcmath php8.2-opcache
```

### 3. Installation MySQL

```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Créer base de données
sudo mysql -u root -p

mysql> CREATE DATABASE b2b_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
mysql> CREATE USER 'b2b_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
mysql> GRANT ALL PRIVILEGES ON b2b_platform.* TO 'b2b_user'@'localhost';
mysql> FLUSH PRIVILEGES;
mysql> EXIT;
```

### 4. Installation Redis

```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Sécuriser Redis
sudo nano /etc/redis/redis.conf
# Décommenter et définir: requirepass YOUR_REDIS_PASSWORD
sudo systemctl restart redis-server
```

### 5. Installation Nginx

```bash
sudo apt install -y nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

### 6. Installation Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

## 📦 Déploiement de l'application

### 1. Cloner le repository

```bash
cd /var/www
sudo git clone https://github.com/yourusername/b2b1.git b2b-platform
sudo chown -R www-data:www-data b2b-platform
cd b2b-platform
```

### 2. Installer les dépendances

```bash
composer install --optimize-autoloader --no-dev
```

### 3. Configuration de l'environnement

```bash
cp .env.example .env
nano .env
```

**Configuration .env production:**

```env
APP_NAME="B2B Wholesale Platform"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE="Africa/Tunis"
APP_URL=https://votre-domaine.com

LOG_CHANNEL=stack
LOG_LEVEL=error
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=b2b_platform
DB_USERNAME=b2b_user
DB_PASSWORD=STRONG_PASSWORD_HERE

BROADCAST_CONNECTION=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=YOUR_REDIS_PASSWORD
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votre-domaine.com
MAIL_FROM_NAME="${APP_NAME}"

# Laravel Reverb pour WebSocket
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=votre-domaine.com
REVERB_PORT=8080
REVERB_SCHEME=https

# B2B Platform
CURRENCY=TND
CURRENCY_DECIMALS=3
LOW_STOCK_THRESHOLD=10
ADMIN_EMAIL=admin@votre-domaine.com
SUPPORT_EMAIL=support@votre-domaine.com
```

### 4. Générer la clé d'application

```bash
php artisan key:generate
```

### 5. Exécuter les migrations

```bash
php artisan migrate --force
php artisan db:seed --force
```

### 6. Optimisations de production

```bash
# Cache de configuration
php artisan config:cache

# Cache des routes
php artisan route:cache

# Cache des vues
php artisan view:cache

# Cache des événements
php artisan event:cache

# Optimiser l'autoloader
composer dump-autoload --optimize
```

### 7. Permissions

```bash
sudo chown -R www-data:www-data /var/www/b2b-platform
sudo chmod -R 755 /var/www/b2b-platform
sudo chmod -R 775 /var/www/b2b-platform/storage
sudo chmod -R 775 /var/www/b2b-platform/bootstrap/cache

# Créer le lien symbolique pour storage
php artisan storage:link
```

## ⚙️ Configuration Nginx

### Créer le fichier de configuration

```bash
sudo nano /etc/nginx/sites-available/b2b-platform
```

**Configuration Nginx:**

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name votre-domaine.com www.votre-domaine.com;

    # Redirection vers HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name votre-domaine.com www.votre-domaine.com;
    root /var/www/b2b-platform/public;

    index index.php;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/votre-domaine.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/votre-domaine.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}

# WebSocket (Laravel Reverb)
server {
    listen 8080 ssl http2;
    listen [::]:8080 ssl http2;
    server_name votre-domaine.com;

    ssl_certificate /etc/letsencrypt/live/votre-domaine.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/votre-domaine.com/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### Activer le site

```bash
sudo ln -s /etc/nginx/sites-available/b2b-platform /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 🔒 SSL avec Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d votre-domaine.com -d www.votre-domaine.com

# Auto-renouvellement
sudo certbot renew --dry-run
```

## 🔄 Configuration des services

### Queue Worker (Systemd)

```bash
sudo nano /etc/systemd/system/b2b-queue.service
```

```ini
[Unit]
Description=B2B Platform Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=3
ExecStart=/usr/bin/php /var/www/b2b-platform/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable b2b-queue
sudo systemctl start b2b-queue
```

### Laravel Reverb (WebSocket)

```bash
sudo nano /etc/systemd/system/b2b-reverb.service
```

```ini
[Unit]
Description=B2B Platform Reverb WebSocket
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=3
ExecStart=/usr/bin/php /var/www/b2b-platform/artisan reverb:start

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable b2b-reverb
sudo systemctl start b2b-reverb
```

### Scheduler (Cron)

```bash
sudo crontab -e -u www-data
```

Ajouter:
```
* * * * * cd /var/www/b2b-platform && php artisan schedule:run >> /dev/null 2>&1
```

## 📊 Monitoring

### Laravel Horizon (optionnel mais recommandé)

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan horizon:publish
```

Créer service systemd pour Horizon:

```bash
sudo nano /etc/systemd/system/b2b-horizon.service
```

```ini
[Unit]
Description=B2B Platform Horizon
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=3
ExecStart=/usr/bin/php /var/www/b2b-platform/artisan horizon

[Install]
WantedBy=multi-user.target
```

### Logs

```bash
# Logs Laravel
tail -f storage/logs/laravel.log

# Logs Nginx
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log

# Logs PHP-FPM
tail -f /var/log/php8.2-fpm.log
```

## 🔐 Sécurité

### Firewall (UFW)

```bash
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw allow 8080/tcp  # WebSocket
sudo ufw enable
```

### Fail2Ban

```bash
sudo apt install -y fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

### PHP Configuration

```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

Recommandations:
```ini
expose_php = Off
max_execution_time = 60
max_input_time = 60
memory_limit = 256M
post_max_size = 32M
upload_max_filesize = 32M
```

```bash
sudo systemctl restart php8.2-fpm
```

## 💾 Backup

### Script de backup automatique

Créer `/var/www/b2b-platform/backup.sh`:

```bash
#!/bin/bash

# Configuration
BACKUP_DIR="/backups/b2b-platform"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="b2b_platform"
DB_USER="b2b_user"
DB_PASS="STRONG_PASSWORD_HERE"

# Créer répertoire de backup
mkdir -p $BACKUP_DIR

# Backup base de données
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup fichiers storage
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz /var/www/b2b-platform/storage

# Supprimer backups de plus de 7 jours
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completed: $DATE"
```

```bash
chmod +x /var/www/b2b-platform/backup.sh
```

### Cron pour backup quotidien

```bash
sudo crontab -e
```

```
0 2 * * * /var/www/b2b-platform/backup.sh >> /var/log/b2b-backup.log 2>&1
```

## 🚀 Déploiement continu

### Script de déploiement

Créer `/var/www/b2b-platform/deploy.sh`:

```bash
#!/bin/bash

cd /var/www/b2b-platform

# Mettre en mode maintenance
php artisan down

# Pull derniers changements
git pull origin main

# Installer dépendances
composer install --no-dev --optimize-autoloader

# Migrations
php artisan migrate --force

# Clear & recache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart b2b-queue
sudo systemctl restart b2b-reverb

# Sortir du mode maintenance
php artisan up

echo "Deployment completed successfully!"
```

## 📈 Performance

### OPcache

```bash
sudo nano /etc/php/8.2/fpm/conf.d/10-opcache.ini
```

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### MySQL Tuning

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

```ini
[mysqld]
innodb_buffer_pool_size = 2G
innodb_log_file_size = 512M
max_connections = 200
```

### Redis Configuration

```bash
sudo nano /etc/redis/redis.conf
```

```
maxmemory 512mb
maxmemory-policy allkeys-lru
```

## ✅ Checklist post-déploiement

- [ ] SSL/HTTPS fonctionne
- [ ] Application accessible
- [ ] API répond correctement
- [ ] Login admin fonctionne
- [ ] Base de données accessible
- [ ] Queue worker actif
- [ ] WebSocket fonctionne
- [ ] Emails sont envoyés
- [ ] Logs s'écrivent correctement
- [ ] Backups configurés
- [ ] Monitoring actif
- [ ] Firewall configuré
- [ ] Tests passent en production

## 🆘 Troubleshooting

### Permission denied

```bash
sudo chown -R www-data:www-data /var/www/b2b-platform
sudo chmod -R 775 storage bootstrap/cache
```

### 500 Internal Server Error

```bash
# Vérifier les logs
tail -f storage/logs/laravel.log
tail -f /var/log/nginx/error.log

# Clear cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Queue jobs not processing

```bash
# Restart queue worker
sudo systemctl restart b2b-queue

# Check status
sudo systemctl status b2b-queue
```

---

**Pour support:** support@votre-domaine.com
