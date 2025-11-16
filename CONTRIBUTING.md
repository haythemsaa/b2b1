# 🤝 Contributing to B2B Wholesale Platform

Merci de votre intérêt pour contribuer à la B2B Wholesale Platform! Ce document fournit les directives pour contribuer au projet.

## 📋 Table des matières

- [Code de conduite](#code-de-conduite)
- [Comment contribuer](#comment-contribuer)
- [Standards de code](#standards-de-code)
- [Processus de pull request](#processus-de-pull-request)
- [Tests](#tests)
- [Reporting de bugs](#reporting-de-bugs)
- [Suggestions de fonctionnalités](#suggestions-de-fonctionnalités)

## 🤖 Code de conduite

Ce projet adhère à un code de conduite. En participant, vous êtes attendu de respecter ce code.

### Nos standards

- Utiliser un langage accueillant et inclusif
- Être respectueux des points de vue et expériences différents
- Accepter gracieusement les critiques constructives
- Se concentrer sur ce qui est meilleur pour la communauté
- Faire preuve d'empathie envers les autres membres de la communauté

## 🚀 Comment contribuer

### Reporting de bugs

Les bugs sont suivis comme des GitHub issues. Créez une issue et fournissez les informations suivantes:

**Template de bug report:**

```markdown
**Description du bug**
Une description claire et concise du bug.

**Pour reproduire**
Étapes pour reproduire le comportement:
1. Aller à '...'
2. Cliquer sur '....'
3. Scroller vers le bas jusqu'à '....'
4. Voir l'erreur

**Comportement attendu**
Description claire de ce qui devrait se passer.

**Screenshots**
Si applicable, ajoutez des screenshots pour aider à expliquer le problème.

**Environnement:**
 - OS: [e.g. Ubuntu 22.04]
 - PHP Version: [e.g. 8.2.1]
 - Laravel Version: [e.g. 11.46.1]
 - Docker Version (si applicable): [e.g. 24.0.0]

**Contexte additionnel**
Tout autre contexte pertinent au problème.
```

### Suggestions de fonctionnalités

Les suggestions de fonctionnalités sont également suivies comme des GitHub issues.

**Template de feature request:**

```markdown
**Le problème lié à votre demande**
Description claire du problème. Ex: Je suis toujours frustré quand [...]

**Solution souhaitée**
Description claire de ce que vous voulez qu'il se passe.

**Alternatives considérées**
Description des solutions ou fonctionnalités alternatives que vous avez considérées.

**Contexte additionnel**
Tout autre contexte ou screenshots pertinents.
```

## 💻 Standards de code

### Style de code PHP

Ce projet utilise **Laravel Pint** pour le formatage du code.

```bash
# Vérifier le style
./vendor/bin/pint --test

# Corriger automatiquement
./vendor/bin/pint
```

### Conventions de nommage

**Modèles (Models):**
- Singulier, PascalCase
- Exemples: `User`, `Product`, `VendorProfile`

**Contrôleurs (Controllers):**
- PascalCase avec suffixe "Controller"
- Exemples: `ProductController`, `OrderController`

**Services:**
- PascalCase avec suffixe "Service"
- Exemples: `PricingService`, `StockService`

**Méthodes:**
- camelCase, descriptif
- Exemples: `calculatePrice()`, `getVisibleProducts()`

**Variables:**
- camelCase, descriptif
- Exemples: `$vendorProfile`, `$orderItems`

**Base de données:**
- Tables: snake_case, pluriel (ex: `vendor_profiles`)
- Colonnes: snake_case (ex: `vendor_group_id`)
- Clés étrangères: `{model}_id` (ex: `user_id`)

### Documentation du code

```php
/**
 * Calculate price for a product considering all discount rules
 *
 * Priority: vendor-specific > group > promotion > base
 *
 * @param Product $product The product to price
 * @param User $vendor The vendor requesting the price
 * @param int $quantity Quantity being ordered
 * @return array Price breakdown with discounts
 * @throws \InvalidArgumentException If quantity is invalid
 */
public function calculatePrice(Product $product, User $vendor, int $quantity): array
{
    // Implementation
}
```

## 🔄 Processus de pull request

### Avant de soumettre

1. **Fork le repository**
2. **Créer une branche** depuis `develop`:
   ```bash
   git checkout -b feature/ma-nouvelle-fonctionnalite
   ```

   Conventions de nommage des branches:
   - `feature/description` - Nouvelles fonctionnalités
   - `bugfix/description` - Corrections de bugs
   - `hotfix/description` - Corrections urgentes
   - `refactor/description` - Refactoring
   - `docs/description` - Documentation

3. **Faire vos changements**

4. **Écrire/Mettre à jour les tests**
   ```bash
   php artisan test
   ```

5. **Vérifier le style de code**
   ```bash
   ./vendor/bin/pint --test
   ```

6. **Commiter vos changements**
   ```bash
   git commit -m "feat: Ajouter fonctionnalité X"
   ```

   Format des commits (Conventional Commits):
   - `feat:` - Nouvelle fonctionnalité
   - `fix:` - Correction de bug
   - `docs:` - Documentation uniquement
   - `style:` - Formatage, point-virgules manquants, etc.
   - `refactor:` - Refactoring de code
   - `test:` - Ajout de tests
   - `chore:` - Maintenance, configuration

7. **Pusher vers votre fork**
   ```bash
   git push origin feature/ma-nouvelle-fonctionnalite
   ```

8. **Ouvrir une Pull Request**

### Checklist PR

Assurez-vous que votre PR:

- [ ] Suit les standards de code du projet
- [ ] Inclut des tests pour les nouvelles fonctionnalités
- [ ] Tous les tests passent (`php artisan test`)
- [ ] La couverture de tests est maintenue/améliorée
- [ ] Le code est documenté (PHPDoc)
- [ ] Le CHANGELOG.md est mis à jour (si applicable)
- [ ] Aucun conflit avec la branche `develop`
- [ ] La PR a une description claire

### Template de Pull Request

```markdown
## Description
Brève description des changements.

## Type de changement
- [ ] Bug fix (changement non-breaking qui corrige un problème)
- [ ] Nouvelle fonctionnalité (changement non-breaking qui ajoute une fonctionnalité)
- [ ] Breaking change (fix ou fonctionnalité qui casserait la compatibilité)
- [ ] Documentation

## Comment a été testé?
Description des tests effectués.

## Checklist
- [ ] Mon code suit le style du projet
- [ ] J'ai effectué une auto-review de mon code
- [ ] J'ai commenté les parties complexes
- [ ] J'ai mis à jour la documentation
- [ ] Mes changements ne génèrent pas de warnings
- [ ] J'ai ajouté des tests
- [ ] Les tests unitaires et d'intégration passent
```

## 🧪 Tests

### Exécuter les tests

```bash
# Tous les tests
php artisan test

# Tests spécifiques
php artisan test --filter=StockServiceTest

# Avec couverture
php artisan test --coverage

# Tests parallèles
php artisan test --parallel
```

### Écrire des tests

**Tests unitaires** (tests/Unit/)
```php
/** @test */
public function it_calculates_price_correctly()
{
    // Arrange
    $product = Product::factory()->create(['base_price' => 100.000]);

    // Act
    $result = $this->pricingService->calculatePrice($product, $this->vendor, 10);

    // Assert
    $this->assertEquals(90.000, $result['final_price']);
}
```

**Tests de fonctionnalités** (tests/Feature/)
```php
/** @test */
public function vendor_can_create_order()
{
    $response = $this->actingAs($this->vendor, 'sanctum')
        ->postJson('/api/vendor/orders', [
            'cart_items' => [
                ['product_id' => 1, 'quantity' => 10],
            ],
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id', 'order_number']);
}
```

### Couverture de tests

Le projet vise une couverture minimale de **80%**. Les nouvelles fonctionnalités doivent maintenir ou améliorer ce niveau.

## 🏗️ Architecture

### Structure du projet

```
app/
├── Http/Controllers/    # Controllers API
├── Models/              # Eloquent models
├── Services/            # Business logic
├── Policies/            # Authorization
├── Observers/           # Model observers
├── Events/              # Event classes
├── Listeners/           # Event listeners
└── Notifications/       # Notification classes

tests/
├── Unit/               # Unit tests
└── Feature/            # Integration tests
```

### Principes

- **Single Responsibility**: Une classe, une responsabilité
- **DRY**: Don't Repeat Yourself
- **SOLID**: Suivre les principes SOLID
- **Service Layer**: Logique métier dans les services
- **Thin Controllers**: Controllers minimalistes

## 📝 Documentation

### Mettre à jour la documentation

Si vos changements affectent:

- **API**: Mettre à jour `API_DOCUMENTATION.md`
- **Installation**: Mettre à jour `README.md` ou `QUICK_START.md`
- **Tests**: Mettre à jour `TESTING_GUIDE.md`
- **Docker**: Mettre à jour `DOCKER_GUIDE.md`

### Changelog

Ajoutez vos changements dans `CHANGELOG.md`:

```markdown
## [Unreleased]

### Added
- Nouvelle fonctionnalité X (#123)

### Changed
- Amélioration de Y (#124)

### Fixed
- Correction du bug Z (#125)
```

## 🔍 Review de code

### Ce que nous recherchons

- **Qualité**: Code propre, lisible, maintenable
- **Tests**: Couverture adéquate
- **Performance**: Pas de requêtes N+1, utilisation de cache appropriée
- **Sécurité**: Validation des entrées, protection CSRF, SQL injection
- **Documentation**: Code bien documenté

### Timeline de review

- Les PRs sont généralement reviewées sous 48-72 heures
- Les corrections demandées doivent être apportées dans les 7 jours
- Les PRs inactives pendant 14+ jours peuvent être fermées

## 🎯 Priorités actuelles

Consultez le [Project Board](../../projects) pour voir les priorités actuelles.

### Roadmap v2.0

- Interface web admin (React/Vue.js)
- Application mobile (React Native)
- Intégration paiement
- Rapports avancés
- Module RMA complet

## 💬 Questions?

- Ouvrez une [Discussion](../../discussions)
- Rejoignez notre [Discord](https://discord.gg/example) (si applicable)
- Contactez les mainteneurs

## 🙏 Reconnaissance

Les contributeurs seront ajoutés au fichier `CONTRIBUTORS.md` et mentionnés dans les release notes.

Merci de contribuer à rendre cette plateforme meilleure! 🚀

---

**Dernière mise à jour**: Janvier 2025
