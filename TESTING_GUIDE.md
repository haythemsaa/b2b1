# 🧪 Guide de Tests - B2B Wholesale Platform

Guide complet pour exécuter et comprendre la suite de tests de la plateforme B2B.

## 📊 Vue d'ensemble

La plateforme dispose de **251 tests** répartis en:
- **115 tests unitaires** (Services métier)
- **136 tests de fonctionnalités API** (Endpoints REST)

### Couverture de tests

```
✅ Services métier (100%)
   - PricingService
   - StockService
   - OrderService
   - CatalogService
   - ChatService

✅ API Vendor (100%)
   - Products
   - Orders
   - Cart
   - Chat

✅ API Admin (100%)
   - Products
   - Orders
   - Vendors
   - Chat

✅ Authentication (100%)
```

## 🚀 Exécution des tests

### Avec Docker (Recommandé)

```bash
# Tous les tests
docker-compose exec app php artisan test

# Tests avec affichage détaillé
docker-compose exec app php artisan test --parallel

# Tests spécifiques
docker-compose exec app php artisan test --testsuite=Unit
docker-compose exec app php artisan test --testsuite=Feature

# Un fichier de test spécifique
docker-compose exec app php artisan test tests/Unit/Services/StockServiceTest.php

# Un test spécifique
docker-compose exec app php artisan test --filter=it_can_add_stock_to_product
```

### Localement (sans Docker)

```bash
# Tous les tests
php artisan test

# Tests avec couverture de code
php artisan test --coverage

# Tests avec couverture minimale requise (80%)
php artisan test --coverage --min=80

# Tests parallèles (plus rapide)
php artisan test --parallel
```

### Avec PHPUnit directement

```bash
# Via vendor/bin
./vendor/bin/phpunit

# Avec configuration personnalisée
./vendor/bin/phpunit --configuration phpunit.xml

# Avec génération de rapport HTML
./vendor/bin/phpunit --coverage-html coverage/
```

## 📂 Structure des tests

```
tests/
├── Unit/                           # Tests unitaires (115 tests)
│   └── Services/
│       ├── PricingServiceTest.php      # 10 tests
│       ├── StockServiceTest.php        # 26 tests
│       ├── OrderServiceTest.php        # 27 tests
│       ├── CatalogServiceTest.php      # 27 tests
│       └── ChatServiceTest.php         # 35 tests
│
└── Feature/                        # Tests de fonctionnalités (136 tests)
    ├── Api/
    │   ├── Auth/
    │   │   └── AuthenticationTest.php  # 7 tests
    │   │
    │   ├── Vendor/
    │   │   ├── ProductApiTest.php      # 20 tests
    │   │   ├── OrderApiTest.php        # 22 tests
    │   │   └── ChatApiTest.php         # 17 tests
    │   │
    │   └── Admin/
    │       ├── ProductApiTest.php      # 25 tests
    │       ├── OrderApiTest.php        # 23 tests
    │       ├── VendorApiTest.php       # 19 tests
    │       └── ChatApiTest.php         # 20 tests
```

## 🧪 Types de tests

### 1. Tests Unitaires

Testent les services métier en isolation.

**Exemple: StockServiceTest**

```php
/** @test */
public function it_can_add_stock_to_product()
{
    $product = Product::factory()->create(['stock_quantity' => 100]);

    $movement = $this->stockService->addStock($product, 50, 'Restock');

    $this->assertEquals(150, $product->fresh()->stock_quantity);
    $this->assertDatabaseHas('stock_movements', [
        'type' => 'in',
        'quantity' => 50,
    ]);
}
```

**Couverture:**
- Logique métier
- Calculs
- Validations
- Transactions de base de données
- Gestion d'erreurs

### 2. Tests de Fonctionnalités (Feature Tests)

Testent les endpoints API de bout en bout.

**Exemple: ProductApiTest**

```php
/** @test */
public function vendor_can_list_visible_products()
{
    Product::factory()->count(5)->create(['is_active' => true]);

    $response = $this->actingAs($this->vendor, 'sanctum')
        ->getJson('/api/vendor/products');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['*' => ['id', 'sku', 'name']],
        ]);
}
```

**Couverture:**
- Authentification/Autorisation
- Validation des requêtes
- Réponses JSON
- Codes de statut HTTP
- Middleware
- Policies

## 🎯 Scénarios de test

### Gestion du stock

```bash
# Exécuter uniquement les tests de stock
php artisan test tests/Unit/Services/StockServiceTest.php
```

**Scénarios testés:**
- ✅ Ajout de stock
- ✅ Retrait de stock
- ✅ Ajustement de stock
- ✅ Réservation pour commandes
- ✅ Libération de stock (annulation)
- ✅ Confirmation de déduction (expédition)
- ✅ Retour de stock (RMA)
- ✅ Import en masse
- ✅ Historique des mouvements
- ✅ Calculs de valeur d'inventaire

### Cycle de vie des commandes

```bash
# Exécuter les tests de commandes
php artisan test tests/Unit/Services/OrderServiceTest.php
php artisan test tests/Feature/Api/Vendor/OrderApiTest.php
php artisan test tests/Feature/Api/Admin/OrderApiTest.php
```

**Workflow testé:**
```
pending → confirmed → processing → shipped → delivered
           ↓
       cancelled (avec libération stock)
```

### Visibilité des produits

```bash
# Tests de catalogService
php artisan test tests/Unit/Services/CatalogServiceTest.php
```

**Règles testées:**
- Visibilité vendeur spécifique > Groupe > Défaut
- Produits cachés explicitement
- Produits inactifs exclus
- Filtrage par catégorie, stock, prix

### Chat en temps réel

```bash
# Tests de chat
php artisan test tests/Unit/Services/ChatServiceTest.php
php artisan test tests/Feature/Api/Vendor/ChatApiTest.php
php artisan test tests/Feature/Api/Admin/ChatApiTest.php
```

**Fonctionnalités testées:**
- Création automatique de conversations
- Envoi de messages
- Compteurs de messages non lus
- Pièces jointes
- Recherche de messages
- Archivage

## 📝 Conventions de tests

### Nommage des tests

```php
// ✅ Bon - Descriptif et en anglais
public function it_validates_minimum_order_quantity()
public function admin_can_create_vendor()
public function vendor_cannot_view_hidden_product()

// ❌ Mauvais - Peu descriptif
public function test_validation()
public function test1()
```

### Structure AAA (Arrange-Act-Assert)

```php
/** @test */
public function it_applies_vendor_specific_pricing()
{
    // Arrange - Préparation
    $product = Product::factory()->create(['base_price' => 100.000]);
    ProductPricing::create([
        'product_id' => $product->id,
        'vendor_id' => $this->vendor->id,
        'price' => 85.000,
    ]);

    // Act - Action
    $result = $this->pricingService->calculatePrice(
        $product,
        $this->vendor,
        1
    );

    // Assert - Vérification
    $this->assertEquals(85.000, $result['base_price']);
}
```

### Factories vs Create manuel

```php
// ✅ Préférer les factories
$product = Product::factory()->create(['sku' => 'TEST-001']);

// ❌ Éviter create manuel (sauf nécessité)
$product = Product::create([
    'sku' => 'TEST-001',
    'name_fr' => 'Test',
    'name_ar' => 'اختبار',
    'category_id' => 1,
    // ... 20 champs
]);
```

## 🔍 Tests de régression

### Avant chaque commit

```bash
# Tests rapides (unitaires seulement)
php artisan test --testsuite=Unit

# Tests complets
php artisan test
```

### Avant chaque push

```bash
# Tests avec couverture
php artisan test --coverage --min=80

# Vérifier le style de code
./vendor/bin/pint --test
```

### CI/CD (GitHub Actions)

```yaml
# .github/workflows/tests.yml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Run tests
        run: |
          composer install
          php artisan test --coverage
```

## 🐛 Debugging des tests

### Afficher les queries SQL

```php
use Illuminate\Support\Facades\DB;

/** @test */
public function debug_test()
{
    DB::enableQueryLog();

    // Votre code de test
    $this->stockService->addStock($product, 50);

    dd(DB::getQueryLog());
}
```

### Dump des données

```php
/** @test */
public function debug_response()
{
    $response = $this->getJson('/api/vendor/products');

    $response->dump();        // Affiche la réponse
    $response->dumpHeaders(); // Affiche les headers
    $response->dd();          // Dump et die
}
```

### Ray (outil de debugging)

```php
use Spatie\LaravelRay\Ray;

/** @test */
public function debug_with_ray()
{
    ray($product)->blue();
    ray($response->json())->green();
}
```

## 📊 Couverture de code

### Générer un rapport

```bash
# Rapport texte dans le terminal
php artisan test --coverage

# Rapport HTML détaillé
php artisan test --coverage-html coverage/

# Ouvrir le rapport
open coverage/index.html
```

### Objectifs de couverture

- **Minimum**: 80% de couverture globale
- **Services métier**: 95%+ recommandé
- **Controllers**: 85%+ recommandé
- **Models**: 80%+ recommandé

## 🚨 Tests en échec

### Diagnostiquer un échec

```bash
# Exécuter uniquement le test qui échoue
php artisan test --filter=nom_du_test

# Avec arrêt au premier échec
php artisan test --stop-on-failure

# Mode verbose
php artisan test -vvv
```

### Erreurs communes

**1. Database not refreshed**
```php
// ✅ Ajouter le trait
use RefreshDatabase;

protected function setUp(): void
{
    parent::setUp();
    // ...
}
```

**2. Authentication non configurée**
```php
// ✅ Utiliser actingAs
$response = $this->actingAs($this->vendor, 'sanctum')
    ->getJson('/api/vendor/products');
```

**3. Factories non trouvées**
```bash
# Vérifier que les factories existent
ls database/factories/
```

## 📈 Métriques de performance

### Tests lents

```bash
# Identifier les tests lents
php artisan test --profile

# Exécuter en parallèle
php artisan test --parallel --processes=4
```

### Optimisation

```php
// ✅ Bon - Utilise transactions
use RefreshDatabase;

// ❌ Lent - Recrée la DB à chaque test
use DatabaseMigrations;
```

## 📚 Ressources

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Pest PHP (alternative)](https://pestphp.com/)
- [Laravel Dusk (E2E)](https://laravel.com/docs/dusk)

## 🎯 Checklist avant production

- [ ] Tous les tests passent (`php artisan test`)
- [ ] Couverture >= 80% (`php artisan test --coverage --min=80`)
- [ ] Pas de tests marqués `@skip` ou `@incomplete`
- [ ] Style de code respecté (`./vendor/bin/pint`)
- [ ] Pas de `dd()` ou `dump()` dans le code
- [ ] Tests de sécurité (CSRF, XSS, SQL Injection)
- [ ] Tests de permissions (Admin, Vendor, Guest)
- [ ] Tests de validation des entrées
- [ ] Tests des cas limites (edge cases)

---

**Bonne chance avec vos tests! 🚀**
