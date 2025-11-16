# 🐳 Docker Guide - B2B Platform

Guide complet pour déployer la plateforme B2B avec Docker.

## 📋 Prérequis

- Docker Desktop installé
- Docker Compose installé
- 4GB RAM minimum
- 10GB espace disque

## 🚀 Démarrage Rapide

### 1. Cloner et configurer

```bash
# Cloner le projet
git clone <repository-url>
cd b2b1

# Copier .env
cp .env.example .env
```

### 2. Configurer .env pour Docker

```env
APP_NAME="B2B Wholesale Platform"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE="Africa/Tunis"
APP_URL=http://localhost:8000

# Database (Docker)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=b2b_platform
DB_USERNAME=b2b_user
DB_PASSWORD=secret

# Redis (Docker)
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Broadcasting
BROADCAST_CONNECTION=redis
```

### 3. Démarrer les conteneurs

```bash
# Build et démarrer
docker-compose up -d --build

# Vérifier que tout tourne
docker-compose ps
```

### 4. Installation de l'application

```bash
# Générer la clé d'application
docker-compose exec app php artisan key:generate

# Installer les dépendances
docker-compose exec app composer install

# Exécuter les migrations
docker-compose exec app php artisan migrate --seed

# Créer le lien symbolique
docker-compose exec app php artisan storage:link

# Corriger les permissions
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

## 📦 Services Docker

L'environnement Docker comprend:

### 1. App (PHP 8.2-FPM)
- **Port**: Interne 9000
- **Rôle**: Application Laravel
- **Volumes**: Code source monté

### 2. Nginx
- **Port**: 8000
- **Rôle**: Serveur web
- **URL**: http://localhost:8000

### 3. MySQL 8.0
- **Port**: 3306
- **Database**: b2b_platform
- **User**: b2b_user
- **Password**: secret

### 4. Redis
- **Port**: 6379
- **Rôle**: Cache, Queue, Sessions

### 5. Queue Worker
- **Rôle**: Traite les jobs en background
- **Command**: `queue:work`

### 6. Reverb (WebSocket)
- **Port**: 8080
- **Rôle**: Broadcasting temps réel

### 7. PhpMyAdmin (optionnel)
- **Port**: 8081
- **URL**: http://localhost:8081
- **User**: root
- **Password**: secret

## 🔧 Commandes Utiles

### Gestion des conteneurs

```bash
# Démarrer
docker-compose up -d

# Arrêter
docker-compose down

# Rebuild
docker-compose up -d --build

# Voir les logs
docker-compose logs -f

# Logs d'un service spécifique
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f mysql
```

### Commandes Artisan

```bash
# Toutes les commandes artisan via Docker
docker-compose exec app php artisan [commande]

# Exemples
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:list
```

### Accès aux conteneurs

```bash
# Shell dans le conteneur app
docker-compose exec app bash

# Shell dans MySQL
docker-compose exec mysql bash
docker-compose exec mysql mysql -u root -p

# Shell dans Redis
docker-compose exec redis redis-cli
```

### Composer

```bash
# Installer une dépendance
docker-compose exec app composer require package/name

# Update
docker-compose exec app composer update

# Dump autoload
docker-compose exec app composer dump-autoload
```

### Tests

```bash
# Exécuter les tests
docker-compose exec app php artisan test

# Tests avec coverage
docker-compose exec app php artisan test --coverage
```

## 🗄️ Base de Données

### Accès MySQL

**Via PhpMyAdmin:**
- URL: http://localhost:8081
- Server: mysql
- User: root
- Password: secret

**Via ligne de commande:**
```bash
docker-compose exec mysql mysql -u root -p
# Password: secret

# Se connecter à la DB
mysql> USE b2b_platform;
mysql> SHOW TABLES;
```

### Backup

```bash
# Backup
docker-compose exec mysql mysqldump -u root -psecret b2b_platform > backup.sql

# Restore
docker-compose exec -T mysql mysql -u root -psecret b2b_platform < backup.sql
```

## 🔄 Queue & Broadcasting

### Queue Worker

Le worker tourne automatiquement dans un conteneur séparé.

Voir les logs:
```bash
docker-compose logs -f queue
```

Restart si besoin:
```bash
docker-compose restart queue
```

### Broadcasting (Reverb)

Le serveur WebSocket tourne sur le port 8080.

Tester:
```bash
curl http://localhost:8080/health
```

## 📊 Monitoring

### Voir l'utilisation des ressources

```bash
docker stats
```

### Voir les processus

```bash
docker-compose top
```

## 🐛 Dépannage

### Les conteneurs ne démarrent pas

```bash
# Vérifier les logs
docker-compose logs

# Nettoyer et rebuild
docker-compose down -v
docker-compose up -d --build
```

### Problèmes de permissions

```bash
# Dans le conteneur app
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Base de données inaccessible

```bash
# Vérifier que MySQL est démarré
docker-compose ps mysql

# Recréer la DB
docker-compose exec mysql mysql -u root -psecret -e "DROP DATABASE IF EXISTS b2b_platform; CREATE DATABASE b2b_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Port déjà utilisé

Si le port 8000, 3306 ou 8080 est déjà utilisé:

**Option 1**: Arrêter le service qui utilise le port

**Option 2**: Modifier `docker-compose.yml`:
```yaml
nginx:
  ports:
    - "8001:80"  # Changer 8000 → 8001
```

### Cache problèmes

```bash
# Nettoyer tous les caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

## 🔒 Production

Pour la production, modifiez `docker-compose.yml`:

```yaml
app:
  build:
    context: .
    dockerfile: Dockerfile
  environment:
    - APP_ENV=production
    - APP_DEBUG=false
```

Et créez un `.env.production`:

```env
APP_ENV=production
APP_DEBUG=false
DB_PASSWORD=mot_de_passe_fort
REDIS_PASSWORD=mot_de_passe_fort
```

## 🧹 Nettoyage

### Arrêter et supprimer tout

```bash
# Arrêter et supprimer conteneurs + volumes
docker-compose down -v

# Supprimer images
docker-compose down --rmi all

# Nettoyage complet Docker
docker system prune -a --volumes
```

## 📝 Volumes

Les données persistantes sont stockées dans:

- `mysql_data`: Base de données MySQL
- `./`: Code source (monté)

Pour reset complètement:
```bash
docker-compose down -v
docker volume rm b2b1_mysql_data
```

## 🎯 URLs de Développement

- **Application**: http://localhost:8000
- **API**: http://localhost:8000/api
- **PhpMyAdmin**: http://localhost:8081
- **WebSocket**: ws://localhost:8080

## ✅ Checklist Post-Installation

- [ ] Conteneurs démarrés: `docker-compose ps`
- [ ] Migrations exécutées: `docker-compose exec app php artisan migrate:status`
- [ ] Seeders exécutés: Vérifier dans PhpMyAdmin
- [ ] Storage link créé: `docker-compose exec app ls -la public/storage`
- [ ] Application accessible: http://localhost:8000
- [ ] API fonctionne: `curl http://localhost:8000/api/auth/login`
- [ ] Queue worker actif: `docker-compose logs queue`

## 🤝 Support

Pour toute question Docker:
- Documentation Docker: https://docs.docker.com
- Documentation Docker Compose: https://docs.docker.com/compose

---

**Happy Dockering! 🐳**
