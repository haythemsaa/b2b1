#!/bin/bash

#########################################
# B2B Platform Backup Script
#
# Ce script effectue:
# - Backup de la base de données MySQL
# - Backup du répertoire storage
# - Rotation des backups (suppression > 30 jours)
# - Notification par email (optionnel)
#########################################

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_ROOT="/backups/b2b-platform"
DATE=$(date +%Y%m%d_%H%M%S)
DATE_LABEL=$(date +"%Y-%m-%d %H:%M:%S")
RETENTION_DAYS=30

# Charger les variables d'environnement
if [ -f "$SCRIPT_DIR/.env" ]; then
    export $(grep -v '^#' "$SCRIPT_DIR/.env" | xargs)
else
    echo "Error: .env file not found"
    exit 1
fi

# Couleurs pour output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonctions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Vérifier si le script est exécuté en tant que root ou avec sudo
if [ "$EUID" -eq 0 ]; then
    log_warn "Running as root. Consider running as www-data user."
fi

# Créer les répertoires de backup
mkdir -p "$BACKUP_ROOT/database"
mkdir -p "$BACKUP_ROOT/storage"
mkdir -p "$BACKUP_ROOT/logs"

LOG_FILE="$BACKUP_ROOT/logs/backup_$DATE.log"
exec > >(tee -a "$LOG_FILE")
exec 2>&1

log_info "==================================================="
log_info "B2B Platform Backup Started: $DATE_LABEL"
log_info "==================================================="

# 1. Backup de la base de données
log_info "Starting database backup..."

DB_BACKUP_FILE="$BACKUP_ROOT/database/db_$DATE.sql.gz"

if [ "$DB_CONNECTION" = "mysql" ]; then
    # MySQL Backup
    if command -v mysqldump &> /dev/null; then
        mysqldump \
            --user="$DB_USERNAME" \
            --password="$DB_PASSWORD" \
            --host="$DB_HOST" \
            --port="${DB_PORT:-3306}" \
            --single-transaction \
            --quick \
            --lock-tables=false \
            "$DB_DATABASE" | gzip > "$DB_BACKUP_FILE"

        if [ $? -eq 0 ]; then
            DB_SIZE=$(du -h "$DB_BACKUP_FILE" | cut -f1)
            log_info "Database backup completed: $DB_SIZE"
        else
            log_error "Database backup failed!"
            exit 1
        fi
    else
        log_error "mysqldump not found!"
        exit 1
    fi
elif [ "$DB_CONNECTION" = "pgsql" ]; then
    # PostgreSQL Backup
    if command -v pg_dump &> /dev/null; then
        PGPASSWORD="$DB_PASSWORD" pg_dump \
            --host="$DB_HOST" \
            --port="${DB_PORT:-5432}" \
            --username="$DB_USERNAME" \
            --format=plain \
            --no-owner \
            --no-acl \
            "$DB_DATABASE" | gzip > "$DB_BACKUP_FILE"

        if [ $? -eq 0 ]; then
            DB_SIZE=$(du -h "$DB_BACKUP_FILE" | cut -f1)
            log_info "Database backup completed: $DB_SIZE"
        else
            log_error "Database backup failed!"
            exit 1
        fi
    else
        log_error "pg_dump not found!"
        exit 1
    fi
else
    log_error "Unsupported database connection: $DB_CONNECTION"
    exit 1
fi

# 2. Backup du répertoire storage
log_info "Starting storage backup..."

STORAGE_BACKUP_FILE="$BACKUP_ROOT/storage/storage_$DATE.tar.gz"
STORAGE_PATH="$SCRIPT_DIR/storage"

if [ -d "$STORAGE_PATH" ]; then
    tar -czf "$STORAGE_BACKUP_FILE" \
        --exclude="$STORAGE_PATH/framework/cache/*" \
        --exclude="$STORAGE_PATH/framework/sessions/*" \
        --exclude="$STORAGE_PATH/framework/views/*" \
        --exclude="$STORAGE_PATH/logs/*.log" \
        -C "$SCRIPT_DIR" storage

    if [ $? -eq 0 ]; then
        STORAGE_SIZE=$(du -h "$STORAGE_BACKUP_FILE" | cut -f1)
        log_info "Storage backup completed: $STORAGE_SIZE"
    else
        log_error "Storage backup failed!"
        exit 1
    fi
else
    log_warn "Storage directory not found, skipping..."
fi

# 3. Backup de la configuration .env (sans mots de passe)
log_info "Backing up configuration..."

ENV_BACKUP_FILE="$BACKUP_ROOT/env_$DATE.backup"
if [ -f "$SCRIPT_DIR/.env" ]; then
    # Copier .env en masquant les valeurs sensibles
    sed 's/\(PASSWORD\|SECRET\|KEY\)=.*/\1=***REDACTED***/g' "$SCRIPT_DIR/.env" > "$ENV_BACKUP_FILE"
    log_info "Configuration backup completed"
fi

# 4. Rotation des anciens backups
log_info "Cleaning up old backups (older than $RETENTION_DAYS days)..."

# Supprimer les backups de base de données anciens
find "$BACKUP_ROOT/database" -name "db_*.sql.gz" -type f -mtime +$RETENTION_DAYS -delete
DELETED_DB=$(find "$BACKUP_ROOT/database" -name "db_*.sql.gz" -type f -mtime +$RETENTION_DAYS | wc -l)

# Supprimer les backups de storage anciens
find "$BACKUP_ROOT/storage" -name "storage_*.tar.gz" -type f -mtime +$RETENTION_DAYS -delete
DELETED_STORAGE=$(find "$BACKUP_ROOT/storage" -name "storage_*.tar.gz" -type f -mtime +$RETENTION_DAYS | wc -l)

# Supprimer les logs de backup anciens
find "$BACKUP_ROOT/logs" -name "backup_*.log" -type f -mtime +$RETENTION_DAYS -delete

log_info "Deleted $DELETED_DB old database backups"
log_info "Deleted $DELETED_STORAGE old storage backups"

# 5. Statistiques finales
log_info "==================================================="
log_info "Backup Summary:"
log_info "  - Database: $DB_SIZE"
log_info "  - Storage: $STORAGE_SIZE"
log_info "  - Location: $BACKUP_ROOT"
log_info "  - Retention: $RETENTION_DAYS days"
log_info "==================================================="

# 6. Vérification de l'espace disque
DISK_USAGE=$(df -h "$BACKUP_ROOT" | tail -1 | awk '{print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 80 ]; then
    log_warn "Disk usage is high: ${DISK_USAGE}%"
fi

# 7. Notification par email (optionnel)
if [ ! -z "$BACKUP_EMAIL" ] && command -v mail &> /dev/null; then
    log_info "Sending backup notification to $BACKUP_EMAIL..."

    echo "B2B Platform Backup Report

Date: $DATE_LABEL
Status: SUCCESS

Database Backup: $DB_SIZE
Storage Backup: $STORAGE_SIZE
Location: $BACKUP_ROOT
Disk Usage: ${DISK_USAGE}%

Old backups deleted:
- Database: $DELETED_DB
- Storage: $DELETED_STORAGE

Retention policy: $RETENTION_DAYS days
" | mail -s "B2B Platform Backup - $DATE_LABEL" "$BACKUP_EMAIL"
fi

log_info "Backup completed successfully at $(date +"%Y-%m-%d %H:%M:%S")"
log_info "Log file: $LOG_FILE"

exit 0
