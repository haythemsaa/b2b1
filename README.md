# 🏪 Plateforme B2B Wholesale - Tunisia

> Une plateforme B2B complète pour connecter les grossistes avec leurs clients professionnels (vendeurs/détaillants) en Tunisie.

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-Proprietary-yellow.svg)]()

## 📋 Table des Matières

- [Fonctionnalités](#-fonctionnalités)
- [Stack Technique](#-stack-technique)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Utilisation](#-utilisation)
- [API Documentation](#-api-documentation)
- [Tests](#-tests)
- [Déploiement](#-déploiement)

## ✨ Fonctionnalités

### 🏢 Gestion des Vendeurs
- ✅ Segmentation par groupes (VIP, Gold, Standard, Bronze)
- ✅ Profils complets avec informations fiscales et commerciales
- ✅ Gestion des limites de crédit par vendeur
- ✅ Conditions de paiement personnalisées (immédiat, net 30/60/90)
- ✅ Adresses de facturation et livraison

### 📦 Catalogue Produits
- ✅ Catalogues personnalisés par vendeur/groupe
- ✅ Tarification différenciée (individuelle > groupe > base)
- ✅ Remises volumiques avec paliers de quantité
- ✅ Support multilingue complet (Français/Arabe)
- ✅ Gestion complète des stocks avec audit trail
- ✅ Images produits multiples avec image principale
- ✅ Catégories hiérarchiques

### 🛒 Gestion des Commandes
- ✅ Cycle de vie complet (pending → confirmed → processing → shipped → delivered)
- ✅ Réservation automatique du stock lors de la commande
- ✅ Suivi en temps réel avec timestamps
- ✅ Système RMA pour retours/échanges/avoirs
- ✅ Notes vendeur et admin
- ✅ Expédition prioritaire pour VIP

### 💬 Communication
- ✅ Chat en temps réel entre vendeurs et admin
- ✅ Pièces jointes dans les messages (images, documents)
- ✅ Compteurs de messages non lus
- ✅ Notifications push via WebSocket
- ✅ Archivage des conversations

### 🎯 Promotions
- ✅ Campagnes promotionnelles ciblées
- ✅ Remises pourcentage ou montant fixe
- ✅ Éligibilité par produit/catégorie/vendeur/groupe
- ✅ Dates de début et fin
- ✅ Combinaison avec tarifs différenciés

### 🌍 Multilingue & Localisation
- ✅ Interface Français et Arabe
- ✅ Support RTL pour l'arabe
- ✅ Contenu traduit pour produits et catégories
- ✅ Locale par utilisateur
- ✅ Devise: Dinar Tunisien (TND) avec 3 décimales

### 🔔 Notifications
- ✅ Notifications email (création commande, statut, expédition)
- ✅ Notifications in-app (database)
- ✅ Alertes stock bas pour admin
- ✅ Notifications nouveau message

## 🛠️ Stack Technique

### Backend
- **Framework**: Laravel 11.46.1
- **PHP**: 8.2+
- **Base de données**: MySQL 8.0+ / PostgreSQL 13+
- **Authentification**: Laravel Sanctum (API Tokens)
- **Broadcasting**: Pusher / Laravel Reverb
- **Queue**: Database / Redis
- **Cache**: Database / Redis

### Architecture
- **Pattern**: Service Layer + Repository
- **API**: RESTful API
- **Authorization**: Policies
- **Events**: Event-driven pour notifications

### Spécificités Business
- **Devise**: Dinar Tunisien (TND)
- **Précision décimale**: 3 décimales (ex: 123.456 TND)
- **Fuseau horaire**: Africa/Tunis
- **Langues**: Français (fr), Arabe (ar)

## 📥 Installation

### Prérequis

```bash
PHP >= 8.2
Composer
MySQL 8.0+ ou PostgreSQL 13+
Node.js & NPM (pour frontend si besoin)
Redis (optionnel)
```

### 1. Cloner le projet

```bash
git clone <repository-url>
cd b2b1
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configuration de l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configurer la base de données

Éditez `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=b2b_platform
DB_USERNAME=root
DB_PASSWORD=votre_password
```

Créez la base de données:

```bash
mysql -u root -p
CREATE DATABASE b2b_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

### 5. Exécuter les migrations et seeders

```bash
php artisan migrate --seed
```

Cela va créer:
- ✅ 21 tables avec relations
- ✅ 4 groupes de vendeurs (VIP, Gold, Standard, Bronze)
- ✅ 1 admin (admin@b2bplatform.com)
- ✅ 3 vendeurs de test
- ✅ Catégories hiérarchiques multilingues

### 6. Créer le lien symbolique pour le stockage

```bash
php artisan storage:link
```

### 7. Démarrer le serveur

```bash
php artisan serve
```

L'application sera accessible sur `http://localhost:8000`

## ⚙️ Configuration

### Broadcasting (pour chat temps réel)

**Option A - Pusher (cloud):**

1. Créer un compte sur [pusher.com](https://pusher.com)
2. Créer une app
3. Copier les credentials dans `.env`:

```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=eu
```

**Option B - Laravel Reverb (self-hosted):**

```bash
composer require laravel/reverb
php artisan reverb:install
```

Puis dans `.env`:

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

Démarrer Reverb:

```bash
php artisan reverb:start
```

### Queue Workers (pour notifications)

```bash
# Lancer le worker
php artisan queue:work

# Ou avec Supervisor en production
php artisan queue:work --daemon
```

### Email (SMTP)

Configurez votre serveur SMTP dans `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@b2bplatform.com
MAIL_FROM_NAME="${APP_NAME}"
```

## 🚀 Utilisation

### Comptes de test

Après avoir exécuté les seeders:

**Admin:**
```
Email: admin@b2bplatform.com
Password: password
```

**Vendeurs:**
```
vendor1@example.com (Groupe VIP) - password
vendor2@example.com (Groupe Gold) - password
vendor3@example.com (Groupe Standard) - password
```

### Workflow typique

1. **Login Admin** → Créer produits → Définir tarifs par groupe
2. **Login Vendeur** → Consulter catalogue personnalisé → Créer commande
3. **Admin** → Confirmer commande → Traiter → Expédier
4. **Vendeur** → Recevoir notifications → Suivre commande
5. **Chat** → Communication en temps réel

## 📡 API Documentation

### Base URL

```
http://localhost:8000/api
```

### Authentification

Toutes les routes (sauf `/auth/login`) nécessitent un token Bearer.

**Login:**

```bash
POST /api/auth/login
Content-Type: application/json

{
  "email": "vendor1@example.com",
  "password": "password"
}
```

**Response:**

```json
{
  "user": { ... },
  "token": "1|abc123xyz...",
  "token_type": "Bearer"
}
```

**Utiliser le token:**

```bash
curl -H "Authorization: Bearer 1|abc123xyz..." \
     http://localhost:8000/api/vendor/products
```

### Endpoints principaux

#### Vendeur

```bash
# Produits
GET    /api/vendor/products
GET    /api/vendor/products/{id}
GET    /api/vendor/products/categories
POST   /api/vendor/products/{id}/calculate-price

# Commandes
GET    /api/vendor/orders
POST   /api/vendor/orders
GET    /api/vendor/orders/{id}
POST   /api/vendor/orders/{id}/cancel
GET    /api/vendor/orders/stats

# Panier
POST   /api/vendor/cart/validate
POST   /api/vendor/cart/calculate

# Chat
GET    /api/vendor/chat
GET    /api/vendor/chat/messages
POST   /api/vendor/chat/send
POST   /api/vendor/chat/mark-as-read
```

#### Admin

```bash
# Vendeurs
GET    /api/admin/vendors
POST   /api/admin/vendors
GET    /api/admin/vendors/{id}
PUT    /api/admin/vendors/{id}
DELETE /api/admin/vendors/{id}

# Produits
GET    /api/admin/products
POST   /api/admin/products
PUT    /api/admin/products/{id}
POST   /api/admin/products/{id}/adjust-stock
POST   /api/admin/products/{id}/vendor-pricing
POST   /api/admin/products/{id}/group-pricing

# Commandes
GET    /api/admin/orders
POST   /api/admin/orders/{id}/confirm
POST   /api/admin/orders/{id}/ship
POST   /api/admin/orders/{id}/deliver
POST   /api/admin/orders/{id}/cancel

# Chat
GET    /api/admin/chat
GET    /api/admin/chat/{id}/messages
POST   /api/admin/chat/{id}/send
```

**Documentation complète:** Voir [PROJET_B2B_DOCUMENTATION.md](PROJET_B2B_DOCUMENTATION.md)

## 🧪 Tests

```bash
# Exécuter tous les tests
php artisan test

# Tests avec coverage
php artisan test --coverage

# Tests spécifiques
php artisan test --filter=OrderServiceTest
```

## 🌐 Déploiement en Production

### 1. Optimisations

```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 2. Configuration .env

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.com

# Base de données production
DB_CONNECTION=mysql
DB_HOST=votre-host
DB_PORT=3306
DB_DATABASE=prod_db
DB_USERNAME=prod_user
DB_PASSWORD=mot_de_passe_fort

# HTTPS obligatoire
FORCE_HTTPS=true

# Broadcasting production
BROADCAST_CONNECTION=pusher
# ou reverb avec SSL

# Queue avec Redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
```

### 3. Sécurité

- ✅ Utiliser HTTPS (certificat SSL)
- ✅ Configurer CORS appropriés
- ✅ Rate limiting activé
- ✅ Backups réguliers de la DB
- ✅ Logs monitoring (Sentry, Bugsnag)
- ✅ Firewall configuré
- ✅ Variables d'environnement sécurisées

### 4. Serveur Web

**Nginx:**

```nginx
server {
    listen 80;
    server_name votre-domaine.com;
    root /var/www/b2b1/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 5. Supervisor (Queue Workers)

```ini
[program:b2b-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/b2b1/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/b2b1/storage/logs/worker.log
stopwaitsecs=3600
```

## 📁 Structure du Projet

```
app/
├── Events/              # Événements (OrderCreated, etc.)
├── Listeners/           # Listeners d'événements
├── Http/
│   ├── Controllers/Api/
│   │   ├── Auth/       # AuthController
│   │   ├── Vendor/     # ProductController, OrderController, ChatController
│   │   └── Admin/      # VendorController, ProductController, OrderController, ChatController
│   └── Middleware/     # EnsureUserIsAdmin, EnsureUserIsVendor, SetLocale
├── Models/             # 17 modèles Eloquent
├── Notifications/      # 5 notifications
├── Policies/           # ProductPolicy, OrderPolicy, ChatConversationPolicy
├── Providers/          # AuthServiceProvider, EventServiceProvider
└── Services/           # 5 services métier
    ├── Catalog/        # CatalogService
    ├── Pricing/        # PricingService
    ├── Order/          # OrderService
    ├── Inventory/      # StockService
    └── Chat/           # ChatService

database/
├── migrations/         # 21 migrations
└── seeders/           # 5 seeders

lang/
├── fr/                # Français
└── ar/                # Arabe

routes/
├── api.php            # Routes API
├── channels.php       # Broadcasting
└── web.php            # Routes web
```

## 🤝 Support

Pour toute question ou support:

- **Email**: support@b2bplatform.com
- **Documentation**: [PROJET_B2B_DOCUMENTATION.md](PROJET_B2B_DOCUMENTATION.md)
- **Issues**: Créer un ticket sur le repo

## 📝 Licence

Ce projet est privé et propriétaire. Tous droits réservés.

## 🎯 Roadmap

### Phase 2 - Application Mobile
- [ ] React Native app
- [ ] Notifications push natives
- [ ] Scanner de codes-barres
- [ ] Mode hors ligne

### Phase 3 - Analytics & Reporting
- [ ] Dashboard analytics avancé
- [ ] Rapports PDF/Excel automatisés
- [ ] Prévisions de stock IA
- [ ] Statistiques de vente par période

### Phase 4 - Intégrations
- [ ] ERP (SAP, Odoo, etc.)
- [ ] Transporteurs (tracking API)
- [ ] Paiement en ligne (Paymee, ClickToPay)
- [ ] Comptabilité

---

**Développé avec ❤️ pour les professionnels B2B en Tunisie**
