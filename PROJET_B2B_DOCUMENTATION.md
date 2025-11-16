# Documentation Technique - Plateforme B2B Grossiste

## Table des Matières

1. [Vue d'ensemble](#vue-densemble)
2. [Architecture](#architecture)
3. [Base de données](#base-de-données)
4. [Couche Service](#couche-service)
5. [API Endpoints](#api-endpoints)
6. [Authentification et Autorisation](#authentification-et-autorisation)
7. [Notifications](#notifications)
8. [Broadcasting](#broadcasting)
9. [Multilingue](#multilingue)
10. [Seeders](#seeders)

---

## Vue d'ensemble

Cette plateforme B2B est conçue pour connecter un grossiste avec ses clients professionnels (vendeurs/détaillants) en Tunisie. Elle offre:

- **Catalogues personnalisés** par vendeur ou groupe
- **Tarification différenciée** avec remises volumiques
- **Gestion complète des commandes** avec workflow
- **Chat en temps réel** entre vendeurs et admin
- **Système RMA** pour les retours
- **Support multilingue** (FR/AR)

### Devise et Précision
- **Devise**: Dinar Tunisien (TND)
- **Précision**: 3 décimales (ex: 123.456 TND)

---

## Architecture

### Pattern Architectural

Le projet suit une architecture en couches:

```
Requête → Routes → Middleware → Controller → Service → Model → Database
                                     ↓
                                 Policies
```

### Couches Principales

1. **Routes** (`routes/api.php`, `routes/channels.php`)
   - Définition des endpoints API
   - Groupement par rôle (vendor/admin)

2. **Middleware** (`app/Http/Middleware/`)
   - `EnsureUserIsAdmin`: Vérifie les droits admin
   - `EnsureUserIsVendor`: Vérifie les droits vendeur
   - `SetLocale`: Configure la langue

3. **Controllers** (`app/Http/Controllers/Api/`)
   - `Auth/AuthController`: Authentification
   - `Vendor/*`: Endpoints vendeurs
   - `Admin/*`: Endpoints admin

4. **Services** (`app/Services/`)
   - **CatalogService**: Gestion catalogue et visibilité
   - **PricingService**: Calcul des prix
   - **OrderService**: Gestion des commandes
   - **StockService**: Gestion des stocks
   - **ChatService**: Messagerie en temps réel

5. **Models** (`app/Models/`)
   - 17 modèles Eloquent avec relations complètes

6. **Policies** (`app/Policies/`)
   - Autorisation fine par ressource

---

## Base de données

### Schéma Relationnel

#### Tables Utilisateurs

**users**
- Utilisateurs système (admin + vendors)
- Colonnes: id, name, email, password, role, status, phone, locale
- Soft deletes activé

**vendor_profiles**
- Profils complets des vendeurs
- Relations: belongsTo User, belongsTo VendorGroup
- Champs: company_name, tax_id, credit_limit, payment_term, shipping_address, etc.

**vendor_groups**
- Segmentation des vendeurs (VIP, Gold, Standard, Bronze)
- Champs: name, slug, default_discount_percentage, minimum_order_amount, priority_level

#### Tables Produits

**products**
- Catalogue produits avec support multilingue
- Champs: sku, name_fr, name_ar, description_fr, description_ar, base_price, stock_quantity
- Relations: category, images, pricing, visibility, stockMovements

**categories**
- Hiérarchie de catégories (parent/children)
- Support multilingue (name_fr, name_ar)
- Auto-slug generation

**product_images**
- Images multiples par produit
- Champ is_primary pour image principale

**product_pricing**
- Tarification différenciée (vendor/group/volume)
- Index composite: (product_id, vendor_group_id, min_quantity)
- Calcul prix final avec discount_percentage

**product_vendor_visibility**
- Contrôle visibilité par vendor/group
- Index: (product_id, vendor_id), (product_id, vendor_group_id)

#### Tables Commandes

**orders**
- Commandes avec cycle de vie complet
- Status: pending → confirmed → processing → shipped → delivered
- Champs calculés: subtotal, discount_amount, total
- Timestamps: confirmed_at, shipped_at, delivered_at

**order_items**
- Lignes de commande
- Snapshot des données produit (sku, name, price)
- Relations: order, product, returnItems

#### Tables Promotions

**promotions**
- Campagnes promotionnelles
- Types: percentage, fixed_amount
- Dates: starts_at, ends_at

**promotion_eligibility**
- Ciblage des promotions
- Par: product, category, vendor, vendor_group

#### Tables Retours

**return_requests**
- Demandes RMA
- Status: pending, approved, rejected, completed
- Types: refund, exchange, credit
- Numéro unique: rma_number

**return_items**
- Articles à retourner
- Quantité et raison

#### Tables Chat

**chat_conversations**
- Conversations par vendeur
- Compteurs: unread_vendor_count, unread_admin_count
- Relations: vendor, messages

**chat_messages**
- Messages avec pièces jointes (JSON)
- Broadcasting pour temps réel
- Relations: conversation, sender

#### Table Stock

**stock_movements**
- Audit trail complet des mouvements
- Types: in, out, adjustment, reserved, released
- Référence polymorphique (Order, ReturnRequest, etc.)

---

## Couche Service

### PricingService

**Responsabilités:**
- Calcul des prix avec hiérarchie: vendor specific > group > base
- Application des promotions
- Calcul total panier
- Gestion des paliers de prix

**Méthodes principales:**
```php
calculatePrice(Product $product, User $vendor, int $quantity): array
calculateCartTotal(User $vendor, array $cartItems): array
getPricingTiers(Product $product, User $vendor): Collection
setVendorPricing(Product $product, User $vendor, array $pricingData)
```

### CatalogService

**Responsabilités:**
- Filtrage des produits visibles par vendeur
- Gestion de la visibilité (vendor/group)
- Récupération des catégories avec comptage

**Méthodes principales:**
```php
getVisibleProductsForVendor(User $vendor, array $filters): Builder
isProductVisible(Product $product, User $vendor): bool
setProductVisibilityForVendor(Product $product, User $vendor, bool $isVisible)
getCategoriesForVendor(User $vendor): Collection
```

### OrderService

**Responsabilités:**
- Création de commandes avec validation
- Gestion du workflow de commande
- Validation panier
- Statistiques

**Méthodes principales:**
```php
createOrder(User $vendor, array $cartItems, array $additionalData): Order
updateOrderStatus(Order $order, string $newStatus, ?string $notes): Order
confirmOrder(Order $order): Order
shipOrder(Order $order, array $shippingData): Order
cancelOrder(Order $order, string $reason, bool $byVendor): Order
```

**Transitions de statut:**
```
pending → confirmed → processing → shipped → delivered
    ↓
cancelled (seulement depuis pending/confirmed/processing)
```

### StockService

**Responsabilités:**
- Gestion des entrées/sorties de stock
- Réservation/libération pour commandes
- Historique des mouvements
- Alertes stock bas

**Méthodes principales:**
```php
addStock(Product $product, int $quantity): StockMovement
removeStock(Product $product, int $quantity): StockMovement
reserveStock(Product $product, int $quantity, Order $order): StockMovement
releaseStock(Product $product, int $quantity, Order $order): StockMovement
confirmStockDeduction(Product $product, int $quantity, Order $order): StockMovement
```

**Workflow réservation:**
1. Commande créée → `reserveStock()` (pas de changement stock_quantity)
2. Commande annulée → `releaseStock()` (libère réservation)
3. Commande expédiée → `confirmStockDeduction()` (réduit stock_quantity)

### ChatService

**Responsabilités:**
- Création/récupération conversations
- Envoi de messages
- Marquage lu/non lu
- Upload pièces jointes

**Méthodes principales:**
```php
getOrCreateConversation(User $vendor): ChatConversation
sendMessage(ChatConversation $conversation, User $sender, string $message): ChatMessage
markAsRead(ChatConversation $conversation, User $user)
uploadAttachments(array $files, User $user): array
```

---

## API Endpoints

### Format des Réponses

Succès:
```json
{
  "data": { ... },
  "message": "Opération réussie"
}
```

Erreur:
```json
{
  "message": "Message d'erreur",
  "errors": { ... }
}
```

### Authentification

**POST /api/auth/login**
```json
Request:
{
  "email": "vendor@example.com",
  "password": "password",
  "device_name": "iPhone 12" // optionnel
}

Response:
{
  "user": { ... },
  "token": "1|abc123...",
  "token_type": "Bearer"
}
```

**POST /api/auth/logout**
- Révoque le token actuel

**POST /api/auth/logout-all**
- Révoque tous les tokens de l'utilisateur

### Endpoints Vendeur

#### Produits

**GET /api/vendor/products**
- Paramètres: category_id, search, in_stock, sort_by, sort_order, per_page
- Retourne: Liste paginée avec prix personnalisés

**GET /api/vendor/products/{id}**
- Détails produit avec pricing_tiers

**POST /api/vendor/products/{id}/calculate-price**
```json
Request:
{
  "quantity": 50
}

Response:
{
  "base_price": 100.000,
  "promotion_discount": 10.000,
  "final_price": 90.000,
  "total": 4500.000,
  "quantity": 50
}
```

#### Commandes

**POST /api/vendor/orders**
```json
Request:
{
  "items": [
    {
      "product_id": 1,
      "quantity": 10
    }
  ],
  "shipping_address": "...",
  "notes": "Livraison urgente"
}

Response:
{
  "message": "Commande créée avec succès",
  "order": { ... }
}
```

**POST /api/vendor/cart/calculate**
```json
Request:
{
  "items": [
    { "product_id": 1, "quantity": 10 },
    { "product_id": 2, "quantity": 5 }
  ]
}

Response:
{
  "items": [ ... ],
  "subtotal": 1000.000,
  "total_promotion_discount": 100.000,
  "total": 900.000,
  "minimum_order_amount": 100.000,
  "meets_minimum": true
}
```

#### Chat

**POST /api/vendor/chat/send**
```json
Request (multipart/form-data):
{
  "message": "Bonjour, j'ai une question...",
  "attachments[]": File
}
```

### Endpoints Admin

#### Gestion Produits

**POST /api/admin/products/{id}/adjust-stock**
```json
Request:
{
  "quantity": 100,
  "notes": "Réapprovisionnement"
}
```

**POST /api/admin/products/{id}/vendor-pricing**
```json
Request:
{
  "vendor_id": 5,
  "price": 95.000,
  "discount_percentage": 5.00,
  "min_quantity": 10,
  "max_quantity": 50
}
```

**POST /api/admin/products/{id}/group-pricing**
```json
Request:
{
  "vendor_group_id": 1,
  "price": 90.000,
  "min_quantity": 20
}
```

#### Gestion Commandes

**POST /api/admin/orders/{id}/ship**
```json
Request:
{
  "tracking_number": "TN123456789",
  "carrier": "Aramex",
  "notes": "Livraison express"
}
```

---

## Authentification et Autorisation

### Laravel Sanctum

- Tokens API personnels
- Un token par device (device_name)
- Stockés dans `personal_access_tokens`

### Middleware

**admin**: `app/Http/Middleware/EnsureUserIsAdmin.php`
```php
if (!$user->isAdmin()) {
    return response()->json(['message' => 'Accès refusé'], 403);
}
```

**vendor**: `app/Http/Middleware/EnsureUserIsVendor.php`

### Policies

**ProductPolicy**:
- `view`: Admin ou vendeur avec visibilité
- `create/update/delete`: Admin seulement
- `manageStock/managePricing`: Admin seulement

**OrderPolicy**:
- `view`: Admin ou vendeur propriétaire
- `create`: Vendeur actif seulement
- `cancel`: Admin toujours, vendeur si pending

**ChatConversationPolicy**:
- `view`: Admin ou vendeur propriétaire
- `sendMessage`: Si conversation active
- `archive/reactivate`: Admin seulement

---

## Notifications

### Notifications Email + Database

1. **OrderCreatedNotification**
   - À: Vendeur + Admins
   - Quand: Nouvelle commande créée
   - Canaux: mail, database

2. **OrderStatusUpdatedNotification**
   - À: Vendeur
   - Quand: Changement de statut
   - Canaux: mail, database

3. **OrderShippedNotification**
   - À: Vendeur
   - Quand: Commande expédiée
   - Canaux: mail, database

4. **NewChatMessageNotification**
   - À: Admins (si vendeur envoie) ou Vendeur (si admin envoie)
   - Quand: Nouveau message
   - Canaux: database

5. **LowStockAlertNotification**
   - À: Admins
   - Quand: Stock bas
   - Canaux: mail, database

### Event Listeners

**EventServiceProvider** (`app/Providers/EventServiceProvider.php`):
```php
protected $listen = [
    OrderCreated::class => [SendOrderCreatedNotifications::class],
    OrderStatusUpdated::class => [SendOrderStatusUpdatedNotifications::class],
    NewChatMessage::class => [SendNewChatMessageNotification::class],
];
```

---

## Broadcasting

### Channels

**Private Channel: chat.{conversationId}**
```php
Broadcast::channel('chat.{conversationId}', function (User $user, int $conversationId) {
    if ($user->isAdmin()) return true;
    if ($user->isVendor()) {
        $conversation = ChatConversation::find($conversationId);
        return $conversation && $conversation->vendor_id === $user->id;
    }
    return false;
});
```

**Private Channel: user.{userId}**
- Notifications personnelles

**Private Channel: admin-notifications**
- Notifications admin globales

### Event Broadcasting

**NewChatMessage** (`app/Events/NewChatMessage.php`):
```php
public function broadcastOn(): array
{
    return [new PrivateChannel('chat.' . $this->message->conversation_id)];
}

public function broadcastAs(): string
{
    return 'message.new';
}
```

### Configuration

**.env**:
```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
```

Alternative: Laravel Reverb (self-hosted)

---

## Multilingue

### Configuration

**Langues supportées**: Français (fr), Arabe (ar)

**Locale par défaut**: fr

### Structure

```
lang/
├── fr/
│   ├── messages.php
│   └── validation.php
└── ar/
    ├── messages.php
    └── validation.php
```

### Middleware SetLocale

```php
public function handle(Request $request, Closure $next)
{
    $locale = $request->user()?->locale ?? $request->header('Accept-Language') ?? 'fr';

    if (in_array($locale, ['fr', 'ar'])) {
        App::setLocale($locale);
    }

    return $next($request);
}
```

### Utilisation dans Models

```php
// Product model
public function getName(string $locale = 'fr'): string
{
    return $locale === 'ar' && $this->name_ar
        ? $this->name_ar
        : $this->name_fr;
}
```

### Frontend (à venir)

Pour l'arabe, implémenter RTL (right-to-left):
```css
[dir="rtl"] {
    direction: rtl;
    text-align: right;
}
```

---

## Seeders

### Ordre d'exécution

1. **VendorGroupsSeeder**: Crée 4 groupes (VIP, Gold, Standard, Bronze)
2. **AdminUserSeeder**: Crée admin principal
3. **CategoriesSeeder**: Crée catégories avec sous-catégories
4. **DemoVendorsSeeder**: Crée 3 vendeurs de démo

### Commandes

```bash
# Exécuter tous les seeders
php artisan db:seed

# Seeder spécifique
php artisan db:seed --class=VendorGroupsSeeder

# Refresh + seed
php artisan migrate:fresh --seed
```

### Comptes de démo

**Admin:**
- Email: admin@b2bplatform.com
- Password: password

**Vendeurs:**
- vendor1@example.com (Groupe VIP)
- vendor2@example.com (Groupe Gold)
- vendor3@example.com (Groupe Standard)
- Password: password

---

## Prochaines Étapes

### Phase 2 - Mobile App
- React Native
- Notifications push
- Scanner codes-barres

### Phase 3 - Analytics
- Dashboard analytics avancé
- Rapports PDF/Excel
- Prévisions de stock IA

### Phase 4 - Intégrations
- ERP (SAP, Odoo)
- Transporteurs (API tracking)
- Paiement en ligne (Tunisie: Paymee, ClickToPay)

---

## Support Technique

Pour toute question:
- Email: support@b2bplatform.com
- Documentation Laravel: https://laravel.com/docs/11.x
