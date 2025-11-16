# B2B Wholesale Platform - Deployment Guide

## Prerequisites

- PHP 8.2 or higher
- MySQL 8.0 or higher
- Composer
- Node.js & NPM (for assets, if needed)
- Redis (optional, for caching/queues)

## Step 1: Clone & Setup

```bash
git clone <repository-url>
cd b2b1
composer install
cp .env.example .env
php artisan key:generate
```

## Step 2: Environment Configuration

Edit `.env` file:

```env
APP_NAME="B2B Wholesale Platform"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=b2b_platform
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

# Optional: SMS Configuration (for notifications)
# SMS_PROVIDER=twilio
# TWILIO_SID=your_sid
# TWILIO_TOKEN=your_token
# TWILIO_FROM=your_phone_number

# Optional: Push Notifications
# PUSH_PROVIDER=fcm
# FCM_SERVER_KEY=your_server_key

# File Storage
FILESYSTEM_DISK=local

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## Step 3: Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE b2b_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Run migrations
php artisan migrate

# Optional: Seed initial data
php artisan db:seed
```

## Step 4: Storage Setup

```bash
# Create storage link
php artisan storage:link

# Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Step 5: Generate Initial Data

```bash
# Calculate product recommendations
php artisan recommendations:calculate

# Generate order predictions
php artisan predictions:generate
```

## Step 6: Configure Cron Jobs

Add to crontab (`crontab -e`):

```cron
# Laravel Scheduler (runs all scheduled tasks)
* * * * * cd /path/to/b2b1 && php artisan schedule:run >> /dev/null 2>&1

# Specific commands (if not using scheduler)
# Process expired approvals (hourly)
0 * * * * cd /path/to/b2b1 && php artisan approvals:process-expired

# Process expired negotiations (hourly)
0 * * * * cd /path/to/b2b1 && php artisan negotiations:process-expired

# Notify about expiring documents (daily at 9am)
0 9 * * * cd /path/to/b2b1 && php artisan documents:notify-expiring

# Cleanup old notifications (daily at 2am)
0 2 * * * cd /path/to/b2b1 && php artisan notifications:cleanup

# Aggregate daily metrics (daily at 1am)
0 1 * * * cd /path/to/b2b1 && php artisan analytics:aggregate-daily

# Recalculate recommendations (daily at 3am)
0 3 * * * cd /path/to/b2b1 && php artisan recommendations:calculate

# Regenerate predictions (daily at 4am)
0 4 * * * cd /path/to/b2b1 && php artisan predictions:generate
```

## Step 7: Queue Workers

```bash
# Start queue worker (use supervisor in production)
php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600

# Or use supervisor configuration
# /etc/supervisor/conf.d/b2b-worker.conf
```

Supervisor config example:
```ini
[program:b2b-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/b2b1/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/b2b1/storage/logs/worker.log
stopwaitsecs=3600
```

## Step 8: Web Server Configuration

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/b2b1/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

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
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 20M;
}
```

### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/b2b1/public

    <Directory /path/to/b2b1/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/b2b-error.log
    CustomLog ${APACHE_LOG_DIR}/b2b-access.log combined
</VirtualHost>
```

## Step 9: SSL Certificate (Let's Encrypt)

```bash
# Install certbot
sudo apt install certbot python3-certbot-nginx

# Get certificate
sudo certbot --nginx -d your-domain.com

# Auto-renewal is configured automatically
```

## Step 10: Optimization

```bash
# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Enable OPcache in php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

## Step 11: Monitoring & Logging

### Log Rotation

Create `/etc/logrotate.d/b2b-platform`:

```
/path/to/b2b1/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

### Application Monitoring

Consider integrating:
- **Sentry** for error tracking
- **New Relic** or **DataDog** for APM
- **Laravel Telescope** for development debugging

## Step 12: Backup Strategy

```bash
# Database backup (daily at midnight)
0 0 * * * mysqldump -u username -p'password' b2b_platform | gzip > /backups/b2b_$(date +\%Y\%m\%d).sql.gz

# File backup
0 1 * * * tar -czf /backups/b2b_files_$(date +\%Y\%m\%d).tar.gz /path/to/b2b1/storage/app

# Keep backups for 30 days
find /backups -name "b2b_*.gz" -mtime +30 -delete
```

## Step 13: Security Checklist

- [ ] Change default passwords
- [ ] Configure firewall (UFW)
- [ ] Disable directory listing
- [ ] Set proper file permissions
- [ ] Enable HTTPS only
- [ ] Configure CORS properly
- [ ] Set up fail2ban
- [ ] Regular security updates
- [ ] Enable rate limiting
- [ ] Configure CSP headers

## Step 14: Testing

```bash
# Run tests
php artisan test

# Or with coverage
php artisan test --coverage

# Test specific endpoints
curl -X POST http://your-domain.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

## Step 15: Go Live

```bash
# Final checks
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Set to production mode
# Verify APP_ENV=production in .env
# Verify APP_DEBUG=false in .env

# Restart services
sudo service php8.2-fpm restart
sudo service nginx restart
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start b2b-worker:*
```

## Troubleshooting

### 500 Error
```bash
# Check logs
tail -f storage/logs/laravel.log

# Check permissions
ls -la storage bootstrap/cache
```

### Queue Not Processing
```bash
# Check queue worker status
sudo supervisorctl status b2b-worker:*

# Restart workers
sudo supervisorctl restart b2b-worker:*
```

### Database Connection Issues
```bash
# Test connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Slow Performance
```bash
# Enable query logging
DB::enableQueryLog();

# Check slow queries
tail -f /var/log/mysql/slow-query.log

# Optimize tables
php artisan db:optimize
```

## Maintenance Mode

```bash
# Enable maintenance mode
php artisan down --secret="your-secret-token"

# Access during maintenance
https://your-domain.com/your-secret-token

# Disable maintenance mode
php artisan up
```

## Scaling Considerations

### Horizontal Scaling
- Use load balancer (HAProxy/Nginx)
- Shared Redis for sessions/cache
- Centralized file storage (S3/MinIO)
- Database replication

### Vertical Scaling
- Increase PHP-FPM workers
- Optimize MySQL configuration
- Add more queue workers
- Enable CDN for static assets

## Post-Deployment

1. Monitor error logs for 24 hours
2. Check cron job execution
3. Verify email delivery
4. Test critical user flows
5. Monitor performance metrics
6. Set up alerts for critical issues

## Support

For issues or questions:
- Check documentation: `/docs`
- Review logs: `storage/logs/laravel.log`
- Contact support: support@your-domain.com
