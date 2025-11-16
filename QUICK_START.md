# 🚀 Quick Start Guide - B2B Platform

Guide de démarrage rapide pour lancer la plateforme B2B en moins de 5 minutes!

## Option 1: Installation Automatique (Recommandé)

```bash
# 1. Cloner le projet
git clone <repository-url>
cd b2b1

# 2. Lancer le script d'installation
chmod +x install.sh
./install.sh
```

Le script va:
- ✅ Installer les dépendances Composer
- ✅ Créer le fichier .env
- ✅ Générer la clé d'application
- ✅ Configurer la base de données
- ✅ Exécuter les migrations
- ✅ Seed les données de démo
- ✅ Configurer les permissions

## Option 2: Installation Manuelle

### Étape 1: Installation de base

```bash
# Installer les dépendances
composer install

# Configurer l'environnement
cp .env.example .env
php artisan key:generate
```

### Étape 2: Configuration de la base de données

Éditez `.env`:

```env
DB_CONNECTION=mysql
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

### Étape 3: Migrations et Seeders

```bash
# Exécuter migrations + seeders
php artisan migrate --seed

# Créer le lien symbolique pour storage
php artisan storage:link
```

## Lancement du Serveur

```bash
# Démarrer le serveur Laravel
php artisan serve

# Dans un autre terminal: Queue worker (pour notifications)
php artisan queue:work

# (Optionnel) Broadcasting pour chat temps réel
php artisan reverb:start
```

L'API sera accessible sur: `http://localhost:8000/api`

## Tester l'API

### Avec cURL

```bash
# 1. Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "vendor1@example.com",
    "password": "password"
  }'

# Copier le token de la réponse

# 2. Obtenir les produits
curl -X GET http://localhost:8000/api/vendor/products \
  -H "Authorization: Bearer VOTRE_TOKEN"
```

### Avec Postman

1. Importer `POSTMAN_COLLECTION.json`
2. Configurer la variable `base_url` à `http://localhost:8000`
3. Lancer "Login" ou "Login Admin"
4. Le token est automatiquement sauvegardé
5. Tester les autres endpoints!

## Comptes de Test

### Admin
```
Email: admin@b2bplatform.com
Password: password
```

### Vendeurs
```
vendor1@example.com (Groupe VIP)     - password
vendor2@example.com (Groupe Gold)    - password
vendor3@example.com (Groupe Standard) - password
```

## Workflow de Test Rapide

### 1. Login Admin

```bash
POST /api/auth/login
{
  "email": "admin@b2bplatform.com",
  "password": "password"
}
```

### 2. Créer un Produit

```bash
POST /api/admin/products
{
  "sku": "PHONE-001",
  "name_fr": "iPhone 15 Pro",
  "name_ar": "آيفون 15 برو",
  "base_price": 5000.000,
  "stock_quantity": 50,
  "category_id": 1,
  "minimum_order_quantity": 1,
  "is_active": true
}
```

### 3. Définir Tarif Groupe VIP

```bash
POST /api/admin/products/1/group-pricing
{
  "vendor_group_id": 1,
  "price": 4500.000,
  "discount_percentage": 10,
  "min_quantity": 10
}
```

### 4. Login Vendeur VIP

```bash
POST /api/auth/login
{
  "email": "vendor1@example.com",
  "password": "password"
}
```

### 5. Voir Catalogue Personnalisé

```bash
GET /api/vendor/products
```

### 6. Calculer Prix pour Quantité

```bash
POST /api/vendor/products/1/calculate-price
{
  "quantity": 15
}
```

### 7. Créer Commande

```bash
POST /api/vendor/orders
{
  "items": [
    {
      "product_id": 1,
      "quantity": 15
    }
  ],
  "notes": "Livraison urgente"
}
```

### 8. Admin: Confirmer et Expédier

```bash
# Confirmer
POST /api/admin/orders/1/confirm

# Traiter
POST /api/admin/orders/1/start-processing

# Expédier
POST /api/admin/orders/1/ship
{
  "tracking_number": "TN123456",
  "carrier": "Aramex"
}
```

### 9. Chat en Temps Réel

```bash
# Vendeur envoie message
POST /api/vendor/chat/send
{
  "message": "Bonjour, j'ai une question..."
}

# Admin répond
POST /api/admin/chat/1/send
{
  "message": "Bonjour, comment puis-je vous aider?"
}
```

## Dépannage Rapide

### Erreur: "Access denied for user"
```bash
# Vérifier les credentials dans .env
# Créer la base de données si nécessaire
mysql -u root -p -e "CREATE DATABASE b2b_platform"
```

### Erreur: "Class not found"
```bash
# Régénérer l'autoload
composer dump-autoload
```

### Erreur: "SQLSTATE[HY000] [2002]"
```bash
# Vérifier que MySQL est démarré
sudo service mysql start
# ou
brew services start mysql
```

### Cache problèmes
```bash
# Nettoyer tous les caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Permissions
```bash
# Corriger les permissions
chmod -R 775 storage bootstrap/cache
```

## Prochaines Étapes

1. ✅ Lisez [README.md](README.md) pour documentation complète
2. ✅ Consultez [PROJET_B2B_DOCUMENTATION.md](PROJET_B2B_DOCUMENTATION.md) pour architecture
3. ✅ Importez [POSTMAN_COLLECTION.json](POSTMAN_COLLECTION.json) pour tester l'API
4. ✅ Configurez Broadcasting pour chat temps réel (voir README)
5. ✅ Configurez SMTP pour emails (voir README)

## Support

- 📧 Email: support@b2bplatform.com
- 📖 Documentation: Voir README.md et PROJET_B2B_DOCUMENTATION.md
- 🐛 Issues: Créer un ticket sur le repo

---

**Bon développement! 🚀**
