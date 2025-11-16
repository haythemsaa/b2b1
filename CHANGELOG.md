# Changelog

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/lang/fr/).

## [1.0.0] - 2025-01-16

### Ajouté

#### Architecture & Base
- ✅ Laravel 11.46.1 avec PHP 8.2+
- ✅ Laravel Sanctum pour authentification API
- ✅ Architecture Service Layer complète
- ✅ 21 migrations de base de données
- ✅ 17 modèles Eloquent avec relations

#### Gestion des Vendeurs
- ✅ Système de groupes de vendeurs (VIP, Gold, Standard, Bronze)
- ✅ Profils vendeurs complets avec informations fiscales
- ✅ Gestion des limites de crédit
- ✅ Conditions de paiement personnalisées (immédiat, net 30/60/90)
- ✅ Adresses de facturation et livraison
- ✅ Features personnalisées par groupe/vendeur

#### Catalogue Produits
- ✅ Catalogues personnalisés par vendeur/groupe
- ✅ Visibilité contrôlée (ProductVendorVisibility)
- ✅ Support multilingue (Français/Arabe)
- ✅ Catégories hiérarchiques
- ✅ Images multiples avec image principale
- ✅ Gestion SKU unique

#### Tarification
- ✅ Tarification différenciée (vendor > group > base)
- ✅ Remises volumiques avec paliers de quantité
- ✅ Système de promotions ciblées
- ✅ Promotions par produit/catégorie/vendeur/groupe
- ✅ Promotions avec dates de validité
- ✅ Calcul automatique du meilleur prix

#### Gestion des Commandes
- ✅ Workflow complet (pending → delivered)
- ✅ Réservation automatique du stock
- ✅ Transitions de statut validées
- ✅ Notes vendeur et admin
- ✅ Timestamps pour chaque étape
- ✅ Expédition prioritaire pour VIP
- ✅ Statistiques de commandes

#### Système RMA
- ✅ Demandes de retour (refund/exchange/credit)
- ✅ Workflow d'approbation
- ✅ Numérotation unique RMA
- ✅ Validation des quantités retournables
- ✅ Historique complet

#### Gestion des Stocks
- ✅ Mouvements de stock avec audit trail
- ✅ Types: in, out, adjustment, reserved, released
- ✅ Références polymorphiques (Order, etc.)
- ✅ Réservation lors de la commande
- ✅ Libération lors de l'annulation
- ✅ Déduction lors de l'expédition
- ✅ Alertes stock bas automatiques
- ✅ Historique par produit

#### Communication
- ✅ Chat en temps réel (vendor ↔ admin)
- ✅ Support pièces jointes
- ✅ Compteurs de messages non lus
- ✅ Broadcasting WebSocket
- ✅ Archivage des conversations
- ✅ Notifications nouveau message

#### Notifications
- ✅ Notifications email (OrderCreated, OrderShipped, etc.)
- ✅ Notifications database
- ✅ Queue workers pour envoi asynchrone
- ✅ Event listeners automatiques
- ✅ Notifications stock bas

#### API
- ✅ RESTful API complète
- ✅ Routes vendor et admin séparées
- ✅ Authentification Bearer token
- ✅ Validation des données entrantes
- ✅ Réponses JSON standardisées
- ✅ Gestion d'erreurs appropriée

#### Sécurité & Autorisation
- ✅ Middleware admin/vendor
- ✅ Policies (Product, Order, Chat)
- ✅ Validation des permissions
- ✅ Soft deletes sur User et Product
- ✅ Hashing des mots de passe

#### Multilingue
- ✅ Support Français et Arabe
- ✅ Middleware SetLocale
- ✅ Fichiers de traduction (messages, validation)
- ✅ Méthodes multilingues dans models
- ✅ Locale par utilisateur

#### Seeders & Demo Data
- ✅ VendorGroupsSeeder (4 groupes)
- ✅ AdminUserSeeder (compte admin)
- ✅ CategoriesSeeder (catégories FR/AR)
- ✅ DemoVendorsSeeder (3 vendeurs test)

#### Observers
- ✅ ProductObserver (alertes stock bas)
- ✅ OrderObserver (gestion statuts)

#### Documentation
- ✅ README.md complet avec badges
- ✅ PROJET_B2B_DOCUMENTATION.md (doc technique)
- ✅ CHANGELOG.md
- ✅ Collection Postman
- ✅ Script d'installation bash
- ✅ .env.example configuré

#### Outils
- ✅ install.sh (script d'installation automatisé)
- ✅ POSTMAN_COLLECTION.json (tests API)

### Spécificités Techniques
- **Devise**: Dinar Tunisien (TND) avec 3 décimales
- **Timezone**: Africa/Tunis
- **Database**: MySQL 8.0+ / PostgreSQL 13+
- **Cache**: Database/Redis
- **Queue**: Database/Redis
- **Broadcasting**: Pusher/Laravel Reverb

## [Unreleased]

### Planifié pour v1.1.0
- [ ] Tests unitaires complets
- [ ] Tests d'intégration API
- [ ] Tableau de bord analytics
- [ ] Rapports PDF/Excel
- [ ] Logs avancés (activity log)

### Planifié pour v2.0.0 - Mobile App
- [ ] Application React Native
- [ ] Notifications push natives
- [ ] Scanner codes-barres
- [ ] Mode hors ligne
- [ ] Synchronisation automatique

### Planifié pour v3.0.0 - Intégrations
- [ ] Intégration ERP (SAP, Odoo)
- [ ] API transporteurs (tracking)
- [ ] Paiement en ligne (Paymee, ClickToPay)
- [ ] Module comptabilité

---

## Types de changements

- `Ajouté` pour les nouvelles fonctionnalités
- `Modifié` pour les changements dans les fonctionnalités existantes
- `Déprécié` pour les fonctionnalités bientôt supprimées
- `Supprimé` pour les fonctionnalités supprimées
- `Corrigé` pour les corrections de bugs
- `Sécurité` pour les corrections de vulnérabilités

---

[1.0.0]: https://github.com/votre-repo/b2b1/releases/tag/v1.0.0
