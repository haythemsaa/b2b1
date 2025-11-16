# 🚀 Quick Start - B2B Platform

## Démarrage Rapide en 5 Minutes

### 1️⃣ Démarrer le Serveur

```bash
# Lancer le serveur Laravel
php artisan serve
```

Ou avec Docker:
```bash
docker-compose up -d
```

### 2️⃣ Ouvrir l'Application Web

**URL:** http://localhost:8000

### 3️⃣ Se Connecter

#### Compte Vendeur (Recommandé pour tester)
```
Email: vendor1@example.com
Mot de passe: password
```

#### Compte Admin
```
Email: admin@b2b-platform.com
Mot de passe: password
```

### 4️⃣ Explorer les Fonctionnalités

#### Pour Vendeur:
1. **Dashboard** → Vue d'ensemble avec graphiques et recommandations AI
2. **Products** → Parcourir le catalogue de produits
3. **Orders** → Créer et suivre vos commandes
4. **RFQs** → Créer des demandes de devis
5. **Analytics** → Analyser vos performances
6. **AI Recommendations** → Découvrir les suggestions personnalisées
7. **Approvals** → Gérer les approbations
8. **Documents** → Uploader et gérer vos documents

## 📍 URLs Disponibles

### Authentification
- `/login` - Page de connexion

### Vendor
- `/vendor/dashboard` - Tableau de bord
- `/vendor/products` - Catalogue produits
- `/vendor/orders` - Gestion commandes
- `/vendor/rfqs` - Demandes de devis
- `/vendor/analytics` - Analytics & rapports
- `/vendor/recommendations` - Recommandations AI
- `/vendor/approvals` - Approbations
- `/vendor/documents` - Gestion documentaire

### Admin
- `/admin/dashboard` - Dashboard admin
- `/admin/vendors` - Gestion vendeurs
- `/admin/products` - Gestion produits
- `/admin/orders` - Gestion commandes
- `/admin/rfqs` - Gestion RFQs

## 🎯 Scénarios de Test

### Scénario 1: Passer une Commande
1. Se connecter comme `vendor1@example.com`
2. Aller sur **Products**
3. Parcourir le catalogue
4. Cliquer sur "Add to Cart" sur plusieurs produits
5. Aller sur **Orders**
6. Créer une nouvelle commande

### Scénario 2: Créer un RFQ
1. Aller sur **RFQs**
2. Cliquer "New RFQ"
3. Remplir le formulaire:
   - Titre: "Demande de prix pour matériel de bureau"
   - Description: "Besoin de 100 unités..."
   - Deadline: [date future]
4. Créer le RFQ
5. Voir le RFQ dans la liste

### Scénario 3: Voir les Analytics
1. Aller sur **Analytics**
2. Observer les KPI cards
3. Voir les graphiques:
   - Tendance des revenus
   - Tendance des commandes
   - Distribution des statuts
   - Top catégories
4. Changer la période (Daily/Weekly/Monthly)

### Scénario 4: Tester les Recommandations AI
1. Aller sur **AI Recommendations**
2. Voir les onglets:
   - **Personnalisées**: Produits recommandés avec score
   - **Tendances**: Produits populaires
   - **Prédictions**: Suggestions de réapprovisionnement
3. Cliquer "Generate New Recommendations"
4. Observer les nouveaux résultats

### Scénario 5: Gérer des Documents
1. Aller sur **Documents**
2. Cliquer "Upload Document"
3. Remplir le formulaire:
   - Nom: "Contrat 2024"
   - Type: Contract
   - Fichier: [choisir un PDF]
   - Date d'expiration: [optionnel]
4. Upload
5. Voir le document dans la grille

### Scénario 6: Approbations
1. Aller sur **Approvals**
2. Voir les demandes en attente
3. Cliquer "Approve" ou "Reject"
4. Ajouter des commentaires
5. Voir l'historique dans l'onglet "History"

## 🔧 Configuration (Optionnel)

### Base de Données
Si la base de données est vide:

```bash
# Migrer la base de données
php artisan migrate

# Charger les données de démo
php artisan db:seed --class=DemoDataSeeder
```

### Générer les Recommandations AI
```bash
php artisan recommendations:calculate
php artisan predictions:generate
```

### Compiler les Assets (Si modifiés)
```bash
# Development (avec hot reload)
npm run dev

# Production
npm run build
```

## 📊 Données de Démo

Le seeder crée:
- 1 admin
- 2 vendeurs (vendor1, vendor2)
- 40 produits (10 par catégorie)
- 4 catégories (Electronics, Furniture, Office Supplies, Equipment)

## 🎨 Interface

### Thème
- Palette: Bleu, Vert, Violet, Orange
- Style: Moderne, Clean, Professionnel
- Layout: Sidebar + Content
- Responsive: Desktop, Tablet, Mobile

### Composants
- Cartes KPI avec gradients
- Graphiques interactifs (Chart.js)
- Tableaux triables
- Modals pour formulaires
- Badges de statut colorés
- Notifications en temps réel

## 🚨 Dépannage Rapide

### Le serveur ne démarre pas
```bash
# Vérifier PHP
php -v

# Installer les dépendances
composer install
```

### Les assets ne chargent pas
```bash
# Recompiler
npm run build

# Vider le cache
php artisan cache:clear
php artisan view:clear
```

### Erreur de connexion
1. Vérifier que le seeder a été exécuté
2. Vérifier les identifiants (voir section 3️⃣)
3. Vider le localStorage du navigateur

### Les graphiques ne s'affichent pas
1. Ouvrir la console du navigateur (F12)
2. Vérifier les erreurs JavaScript
3. Vérifier que Chart.js est chargé

## 📱 Navigation

### Raccourcis Clavier (À venir)
- `Ctrl+K` - Recherche rapide
- `Ctrl+N` - Nouvelle commande
- `Ctrl+D` - Dashboard

### Menu Mobile
- Cliquer sur l'icône hamburger (☰) en haut à gauche
- Le sidebar s'ouvre
- Cliquer en dehors pour fermer

## 🎯 Prochaines Étapes

1. **Explorer toutes les pages** - Testez chaque fonctionnalité
2. **Créer des données** - Ajoutez vos propres commandes, RFQs, documents
3. **Tester les workflows** - Créez et approuvez des demandes
4. **Analyser les métriques** - Utilisez le dashboard analytics
5. **Utiliser l'API** - Consultez `API_DOCUMENTATION.md`

## 📚 Documentation Complète

- **WEB_APPLICATION_SUMMARY.md** - Résumé complet de l'application
- **WEB_APP_GUIDE.md** - Guide détaillé d'utilisation
- **API_DOCUMENTATION.md** - Documentation API
- **DEPLOYMENT_GUIDE.md** - Guide de déploiement
- **README.md** - Vue d'ensemble du projet

## 💡 Astuces

### Pour Développeur
- Les fichiers Blade sont dans `resources/views/`
- Le client API est dans `resources/js/api.js`
- Les routes web sont dans `routes/web.php`
- Alpine.js est configuré dans `resources/js/app.js`

### Pour Testeur
- Utilisez plusieurs onglets pour tester multi-utilisateurs
- Testez sur mobile avec les DevTools
- Utilisez la console réseau pour voir les appels API
- Testez les cas d'erreur (mauvais identifiants, etc.)

### Pour Designer
- Tailwind CSS est configuré
- Les couleurs sont dans `tailwind.config.js`
- Les composants sont dans `resources/views/`

## ✅ Checklist de Démarrage

- [ ] Serveur démarré
- [ ] Page de login ouverte
- [ ] Connexion réussie
- [ ] Dashboard affiché
- [ ] Toutes les pages visitées
- [ ] Au moins une action testée par page
- [ ] Documentation consultée

## 🎊 Bon démarrage!

Vous êtes prêt à utiliser la plateforme B2B complète!

Pour toute question, consultez la documentation détaillée dans les fichiers mentionnés ci-dessus.

---

**🚀 Bonne exploration de la plateforme!**
