# Quick Start Guide - Advanced Product System

**Objectif:** Tester rapidement le système de produits avancé en 15 minutes

---

## 📋 Prérequis

- Laravel 11+ installé
- Base de données MySQL configurée
- Composer et npm installés
- Serveur local lancé (`php artisan serve`)

---

## 🚀 Installation Rapide (5 minutes)

### Étape 1: Migrer la Base de Données

```bash
# Migration du système de produits avancé
php artisan migrate --path=database/migrations/2024_01_20_000001_create_advanced_product_system.php

# Vérification des tables créées
php artisan tinker
>>> DB::select('SHOW TABLES');
>>> exit
```

**Tables créées:**
- `product_categories` (catégories illimitées)
- `product_attributes` (6 types)
- `category_attributes` (assignment)
- `product_variants` (variantes)
- `product_bundles` (bundles)
- `product_options` (options configurables)
- `product_reviews` (avis)
- `product_collections` (collections)
- `product_price_tiers` (prix volume)
- `product_relations` (produits liés)
- `product_inventory_log` (historique stock)

### Étape 2: Charger les Données d'Exemple

```bash
# Seeder avec exemples multi-industrie
php artisan db:seed --class=AdvancedProductSystemSeeder

# Vérifier les données
php artisan tinker
>>> App\Models\Product\ProductCategory::count();  # Devrait retourner 17
>>> App\Models\Product\ProductAttribute::count(); # Devrait retourner 14
>>> exit
```

**Données chargées:**
- 17 catégories (4 industries: Electronics, Fashion, Furniture, Tools)
- 14 attributs configurés
- Assignments catégorie-attributs pré-configurés

### Étape 3: Nettoyer les Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## 🎯 Test des Fonctionnalités (10 minutes)

### Test 1: Interface Admin - Catégories

**URL:** `http://localhost:8000/admin/categories`

**Actions:**
1. Voir la hiérarchie des catégories
2. Créer une nouvelle catégorie racine
3. Ajouter une sous-catégorie
4. Vérifier les breadcrumbs

**Exemple:**
```
Nouvelle catégorie:
  Nom: "Sports Equipment"
  Parent: (aucun pour racine)
  Order: 0

Sous-catégorie:
  Nom: "Football"
  Parent: "Sports Equipment"
  Order: 0
```

### Test 2: Interface Admin - Attributs

**URL:** `http://localhost:8000/admin/attributes`

**Actions:**
1. Créer un attribut de type "Select"
2. Ajouter des options
3. Marquer comme "Filterable" et "Variant"

**Exemple:**
```
Nouvel attribut:
  Nom: "Jersey Size"
  Type: Select
  Options: S, M, L, XL, XXL
  ✓ Filterable
  ✓ Variant
  ✗ Required
```

### Test 3: Interface Admin - Assignment Attributs

**URL:** `http://localhost:8000/admin/category-attributes`

**Actions:**
1. Sélectionner "Electronics > Laptops"
2. Assigner les attributs: Processor, RAM, Storage
3. Marquer "Processor" comme Required
4. Réorganiser l'ordre

**Résultat:** Les attributs apparaîtront sur les produits de cette catégorie

### Test 4: Configurateur de Produits - Product Variable

**URL:** `http://localhost:8000/admin/product-configurator`

**Actions:**
1. Sélectionner type "Variable Product"
2. Remplir les informations de base
3. Sélectionner les attributs pour variantes
4. Générer les variantes automatiquement

**Exemple complet:**
```
Type: Variable Product

Informations de base:
  Nom: "Premium T-Shirt"
  SKU: "TSHIRT-PREM-001"
  Catégorie: Fashion > T-Shirts
  Prix de base: 25.00
  Compare price: 35.00
  Stock: 0 (sera géré par variante)
  MOQ: 5

Attributs de variante:
  ✓ Size (S, M, L, XL)
  ✓ Color (Red, Blue, Green)

Clic sur "Generate Variants"
→ Génère 12 variantes automatiquement (4 sizes × 3 colors)

Pour chaque variante, ajuster:
  - SKU (auto-généré: TSHIRT-PREM-001-S-RED)
  - Prix (25.00 pour S/M, 27.00 pour L/XL)
  - Stock (50 pour chaque)
  - Active: ✓
```

### Test 5: Configurateur de Produits - Product Bundle

**URL:** `http://localhost:8000/admin/product-configurator`

**Actions:**
1. Sélectionner type "Bundle Product"
2. Ajouter plusieurs produits au bundle
3. Définir les quantités et réductions

**Exemple:**
```
Type: Bundle Product

Informations de base:
  Nom: "Office Starter Kit"
  SKU: "BUNDLE-OFFICE-001"
  Catégorie: Furniture
  Prix: (calculé automatiquement)

Bundle Items:
  Item 1:
    Produit: "Office Desk"
    Quantité: 1
    Discount: 15%

  Item 2:
    Produit: "Office Chair"
    Quantité: 1
    Discount: 20%

  Item 3:
    Produit: "Desk Lamp"
    Quantité: 2
    Discount: 10%

Prix total bundle: Calculé automatiquement
Total savings: Affiché en temps réel
```

### Test 6: Configurateur de Produits - Product Configurable

**URL:** `http://localhost:8000/admin/product-configurator`

**Exemple:**
```
Type: Configurable Product

Informations de base:
  Nom: "Custom Laptop Build"
  SKU: "LAPTOP-CUSTOM-001"
  Catégorie: Electronics > Laptops
  Prix de base: 999.00

Custom Options:
  Option 1:
    Nom: "RAM Upgrade"
    Type: Select
    Values:
      16GB
      32GB
      64GB
    Price Modifier: +100.00
    Required: No

  Option 2:
    Nom: "SSD Upgrade"
    Type: Select
    Values:
      512GB
      1TB
      2TB
    Price Modifier: +150.00
    Required: No

  Option 3:
    Nom: "Custom Engraving"
    Type: Text
    Price Modifier: +20.00
    Required: No
```

---

## 🛍️ Test Interface Vendor

### Test 7: Catalogue Avancé

**URL:** `http://localhost:8000/vendor/products-advanced`

**Actions à tester:**

1. **Filtrage par catégorie:**
   - Sélectionner "Electronics > Laptops"
   - Observer le chargement des attributs spécifiques

2. **Filtrage par attributs:**
   - Processor: Intel i7
   - RAM: 16GB
   - Prix: 500 - 2000
   - Stock: ✓ In Stock Only

3. **Recherche:**
   - Taper "laptop" dans la barre de recherche
   - Observer les résultats filtrés en temps réel

4. **Changement de vue:**
   - Basculer entre Grid et List view
   - Vérifier l'affichage des informations

5. **Tri:**
   - Trier par prix croissant
   - Trier par nom A-Z
   - Trier par nouveautés

6. **Badges:**
   - Observer les badges de type (Variable, Bundle, etc.)
   - Voir le compteur de variantes
   - Vérifier les pourcentages de réduction

### Test 8: Détails Produit Variable

**URL:** `http://localhost:8000/vendor/products/{id}` (Product type: Variable)

**Actions:**

1. **Sélection de variante:**
   - Cliquer sur Size: L
   - Cliquer sur Color: Red
   - Observer la variante sélectionnée apparaître
   - Vérifier le changement de prix
   - Vérifier le changement de stock
   - Voir le SKU de la variante

2. **Changement de variante:**
   - Changer Color: Blue
   - Observer le prix et stock se mettre à jour
   - Vérifier si l'image change (si variante a une image)

3. **Validation:**
   - Sans sélectionner de variante → Bouton "Add to Cart" désactivé
   - Avec variante sélectionnée → Bouton activé
   - Stock = 0 → Bouton désactivé

### Test 9: Détails Produit Bundle

**URL:** `http://localhost:8000/vendor/products/{id}` (Product type: Bundle)

**Vérifications:**

1. **Affichage bundle:**
   - Liste des items inclus
   - Quantité par item
   - Prix unitaire
   - Pourcentage de réduction
   - Prix après réduction

2. **Calcul des économies:**
   - Prix régulier total
   - Prix bundle
   - Total savings affiché
   - Badge "You save $X!"

### Test 10: Détails Produit Configurable

**URL:** `http://localhost:8000/vendor/products/{id}` (Product type: Configurable)

**Actions:**

1. **Options texte:**
   - Remplir "Custom Engraving"
   - Observer le prix se mettre à jour (+$20)

2. **Options select:**
   - Sélectionner "32GB RAM"
   - Prix augmente de $100

3. **Multiple options:**
   - Sélectionner RAM + SSD
   - Voir le prix total avec toutes les options
   - Vérifier "Total with Options: $1,249.00"

---

## 📡 Test des API (Optionnel)

### Avec Postman ou cURL

#### 1. Liste des Catégories

```bash
curl -X GET http://localhost:8000/api/admin/categories \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Réponse attendue:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Electronics",
      "breadcrumb": "Electronics",
      "depth": 0,
      "children_count": 2
    }
  ]
}
```

#### 2. Liste des Attributs

```bash
curl -X GET http://localhost:8000/api/admin/attributes \
  -H "Accept: application/json"
```

#### 3. Créer un Produit Variable

```bash
curl -X POST http://localhost:8000/api/admin/products/advanced \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "type": "variable",
    "name": "Test T-Shirt",
    "sku": "TEST-001",
    "category_id": 4,
    "price": 25.00,
    "compare_price": 35.00,
    "moq": 5,
    "variants": [
      {
        "sku": "TEST-001-S-RED",
        "attributes": {"size": "S", "color": "Red"},
        "price": 25.00,
        "stock": 100
      },
      {
        "sku": "TEST-001-M-RED",
        "attributes": {"size": "M", "color": "Red"},
        "price": 25.00,
        "stock": 100
      }
    ]
  }'
```

#### 4. Détails Produit (Vendor)

```bash
curl -X GET http://localhost:8000/api/vendor/products/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Réponse pour produit variable:**
```json
{
  "id": 1,
  "name": "Premium T-Shirt",
  "type": "variable",
  "variants": [
    {
      "id": 1,
      "sku": "TSHIRT-S-RED",
      "attributes": {"size": "S", "color": "Red"},
      "price": 25.00,
      "stock": 50
    }
  ]
}
```

---

## ✅ Checklist de Validation

Après les tests, vérifier:

**Base de données:**
- [ ] Tables créées (11 tables)
- [ ] Données seed chargées
- [ ] Relations fonctionnelles

**Interface Admin:**
- [ ] Catégories: CRUD complet
- [ ] Attributs: Création 6 types
- [ ] Assignment: Attributs assignés aux catégories
- [ ] Configurateur: 4 types de produits créables

**Interface Vendor:**
- [ ] Catalogue: Filtrage dynamique
- [ ] Produit Variable: Sélection variantes
- [ ] Produit Bundle: Calcul automatique
- [ ] Produit Configurable: Options fonctionnelles

**API:**
- [ ] Routes accessibles
- [ ] Validation des données
- [ ] Réponses JSON correctes

---

## 🐛 Dépannage

### Erreur: Table doesn't exist

**Solution:**
```bash
php artisan migrate:status
php artisan migrate --path=database/migrations/2024_01_20_000001_create_advanced_product_system.php
```

### Erreur: Class not found

**Solution:**
```bash
composer dump-autoload
php artisan config:clear
```

### Les variantes ne se génèrent pas

**Vérifications:**
1. Les attributs sont marqués `is_variant: true`
2. Les attributs ont des options définies
3. Au moins 1 attribut variant est sélectionné

### Le filtrage ne fonctionne pas

**Vérifications:**
1. Attributs assignés à la catégorie
2. Attributs marqués `is_filterable: true`
3. Cache vidé: `php artisan cache:clear`

---

## 📊 Données de Test Rapides

### Catégories Recommandées

```
Electronics
├── Computers
│   ├── Laptops
│   └── Desktops
└── Smartphones

Fashion
├── Men's Clothing
│   ├── T-Shirts
│   └── Shirts
└── Women's Clothing
```

### Attributs Recommandés

**Pour Laptops:**
- Processor (select): Intel i5, i7, i9, AMD Ryzen 5, 7
- RAM (select): 8GB, 16GB, 32GB, 64GB
- Storage (select): 256GB, 512GB, 1TB, 2TB
- Screen Size (select): 13", 15", 17"

**Pour T-Shirts:**
- Size (select, variant): XS, S, M, L, XL, XXL
- Color (color, variant): #FF0000, #0000FF, #00FF00
- Material (select): Cotton, Polyester, Blend
- Fit (select): Slim, Regular, Loose

---

## 🎯 Scénarios d'Usage Réels

### Scénario 1: E-commerce Fashion

1. Créer catégorie "Fashion > T-Shirts"
2. Créer attributs: Size (variant), Color (variant), Material
3. Assigner attributs à la catégorie
4. Créer produit variable avec 18 variantes (6 sizes × 3 colors)
5. Tester le sélecteur vendor côté

### Scénario 2: Grossiste Électronique

1. Créer catégorie "Electronics > Laptops"
2. Créer attributs: Processor, RAM, Storage, Screen
3. Créer produit avec 24 variantes (2 processors × 3 RAM × 4 storage)
4. Tester le filtrage par specs

### Scénario 3: Bundle Promotionnel

1. Créer 3 produits simples (Desk, Chair, Lamp)
2. Créer produit bundle incluant les 3
3. Définir réductions: 15%, 20%, 10%
4. Vérifier calcul automatique des économies

---

## 📞 Support

**Documentation complète:**
- `ADVANCED_PRODUCT_SYSTEM_COMPLETE.md` - Guide complet
- `PHASE_2_IMPLEMENTATION_SUMMARY.md` - Détails techniques
- `SESSION_SUMMARY.md` - Résumé de session

**En cas de problème:**
1. Vérifier les logs: `storage/logs/laravel.log`
2. Nettoyer caches: `php artisan cache:clear`
3. Consulter la documentation

---

## ⏱️ Temps Estimés

- Installation: **5 minutes**
- Tests Admin: **5 minutes**
- Tests Vendor: **5 minutes**
- Tests API (optionnel): **5 minutes**

**Total: 15-20 minutes pour validation complète**

---

*Guide créé: 2025-01-16*
*Testé avec: Laravel 11.46.1, PHP 8.2+*
