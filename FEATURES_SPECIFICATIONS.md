# 🛠️ Spécifications Techniques - Fonctionnalités Prioritaires

**Version**: 1.0
**Date**: Janvier 2025
**Statut**: Spécifications pour implémentation

---

## 📋 Table des Matières

1. [CSV Upload & Quick Order](#1-csv-upload--quick-order)
2. [Termes de Paiement NET 30/60/90](#2-termes-de-paiement-net-306090)
3. [RFQ System (Request for Quotation)](#3-rfq-system-request-for-quotation)
4. [Multi-Comptes & Workflow Approbation](#4-multi-comptes--workflow-approbation)
5. [Moteur de Recommandations IA](#5-moteur-de-recommandations-ia)
6. [Quick Wins Techniques](#6-quick-wins-techniques)

---

## 1. CSV Upload & Quick Order

### 📊 Vue d'ensemble

Permettre aux vendeurs de passer des commandes en masse via:
- Upload fichier CSV/XLSX
- Copy/paste depuis Excel
- Quick Order Form (SKU + quantité)
- Templates de commandes réutilisables

### 🗂️ Modèles de Données

#### Migration: `order_templates`

```php
Schema::create('order_templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');
    $table->string('name'); // "Commande hebdomadaire", "Réassort standard"
    $table->text('description')->nullable();
    $table->boolean('is_default')->default(false);
    $table->timestamps();
});
```

#### Migration: `order_template_items`

```php
Schema::create('order_template_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_template_id')->constrained()->onDelete('cascade');
    $table->foreignId('product_id')->constrained()->onDelete('cascade');
    $table->integer('quantity');
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

#### Migration: `csv_import_logs`

```php
Schema::create('csv_import_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');
    $table->string('filename');
    $table->integer('total_rows');
    $table->integer('success_count')->default(0);
    $table->integer('error_count')->default(0);
    $table->json('errors')->nullable(); // Détails des erreurs
    $table->enum('status', ['pending', 'processing', 'completed', 'failed']);
    $table->timestamps();
});
```

### 🎯 API Endpoints

#### 1. Upload CSV

```http
POST /api/vendor/orders/import-csv
Content-Type: multipart/form-data
Authorization: Bearer {token}

{
  "file": <csv_file>,
  "auto_create_order": true|false  // Créer commande directement ou valider d'abord
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "import_id": 123,
    "total_rows": 45,
    "valid_rows": 42,
    "invalid_rows": 3,
    "errors": [
      {
        "row": 5,
        "sku": "PROD-999",
        "error": "Product not found or not visible"
      },
      {
        "row": 12,
        "sku": "PROD-456",
        "error": "Insufficient stock (requested: 100, available: 50)"
      }
    ],
    "preview": [
      {
        "sku": "PROD-001",
        "product_name": "Product A",
        "quantity": 10,
        "unit_price": 25.500,
        "subtotal": 255.000
      }
    ],
    "totals": {
      "subtotal": 12450.250,
      "tax": 2490.050,
      "total": 14940.300
    }
  }
}
```

#### 2. Confirmer Import CSV

```http
POST /api/vendor/orders/confirm-csv-import
Content-Type: application/json

{
  "import_id": 123
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "order_id": 789,
    "order_number": "ORD-2025-00789",
    "items_count": 42,
    "total": 14940.300
  }
}
```

#### 3. Quick Order Form

```http
POST /api/vendor/orders/quick-order
Content-Type: application/json

{
  "items": [
    {"sku": "PROD-001", "quantity": 10},
    {"sku": "PROD-002", "quantity": 25},
    {"sku": "PROD-003", "quantity": 5}
  ]
}
```

#### 4. Save Template

```http
POST /api/vendor/order-templates
Content-Type: application/json

{
  "name": "Commande hebdomadaire",
  "description": "Réassort standard tous les lundis",
  "is_default": false,
  "items": [
    {"product_id": 1, "quantity": 50, "notes": "Urgent"},
    {"product_id": 3, "quantity": 100}
  ]
}
```

#### 5. Use Template

```http
POST /api/vendor/order-templates/{id}/create-order
```

**Response:** Crée une commande basée sur le template

#### 6. Download CSV Template

```http
GET /api/vendor/orders/csv-template
```

**Response:** Fichier CSV avec colonnes: SKU, Quantity, Notes

### 💻 Service Layer

#### `app/Services/CsvOrderService.php`

```php
<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\CsvImportLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class CsvOrderService
{
    public function __construct(
        private PricingService $pricingService,
        private StockService $stockService,
        private OrderService $orderService
    ) {}

    /**
     * Process CSV file upload
     */
    public function processCsvUpload(User $vendor, $file): array
    {
        $importLog = CsvImportLog::create([
            'vendor_id' => $vendor->id,
            'filename' => $file->getClientOriginalName(),
            'status' => 'processing',
        ]);

        try {
            $data = Excel::toArray([], $file)[0];

            // Remove header row
            array_shift($data);

            $importLog->update(['total_rows' => count($data)]);

            $results = $this->validateAndPrepareItems($vendor, $data);

            $importLog->update([
                'success_count' => count($results['valid']),
                'error_count' => count($results['errors']),
                'errors' => $results['errors'],
                'status' => 'completed',
            ]);

            return [
                'import_id' => $importLog->id,
                'total_rows' => count($data),
                'valid_rows' => count($results['valid']),
                'invalid_rows' => count($results['errors']),
                'errors' => $results['errors'],
                'preview' => array_slice($results['valid'], 0, 10),
                'totals' => $this->calculateTotals($results['valid']),
            ];

        } catch (\Exception $e) {
            $importLog->update([
                'status' => 'failed',
                'errors' => [['error' => $e->getMessage()]],
            ]);

            throw $e;
        }
    }

    /**
     * Validate and prepare items from CSV
     */
    private function validateAndPrepareItems(User $vendor, array $rows): array
    {
        $valid = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because header + 0-index

            // Expected format: [SKU, Quantity, Notes]
            $sku = $row[0] ?? null;
            $quantity = $row[1] ?? null;
            $notes = $row[2] ?? null;

            // Validate
            $validator = Validator::make([
                'sku' => $sku,
                'quantity' => $quantity,
            ], [
                'sku' => 'required|string',
                'quantity' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                $errors[] = [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'error' => $validator->errors()->first(),
                ];
                continue;
            }

            // Find product
            $product = Product::where('sku', $sku)
                ->where('is_active', true)
                ->first();

            if (!$product) {
                $errors[] = [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'error' => 'Product not found or not active',
                ];
                continue;
            }

            // Check visibility
            $visibleProducts = app(CatalogService::class)->getVisibleProducts($vendor);
            if (!$visibleProducts->contains($product)) {
                $errors[] = [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'error' => 'Product not visible for your account',
                ];
                continue;
            }

            // Check stock
            $availableStock = $this->stockService->getAvailableStock($product);
            if ($availableStock < $quantity) {
                $errors[] = [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'error' => "Insufficient stock (requested: {$quantity}, available: {$availableStock})",
                ];
                continue;
            }

            // Check minimum quantity
            if ($product->minimum_order_quantity && $quantity < $product->minimum_order_quantity) {
                $errors[] = [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'error' => "Minimum quantity is {$product->minimum_order_quantity}",
                ];
                continue;
            }

            // Check order multiple
            if ($product->order_multiple && $quantity % $product->order_multiple !== 0) {
                $errors[] = [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'error' => "Quantity must be multiple of {$product->order_multiple}",
                ];
                continue;
            }

            // Get price
            $unitPrice = $this->pricingService->calculatePrice($product, $vendor, $quantity);

            $valid[] = [
                'product_id' => $product->id,
                'sku' => $sku,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $unitPrice * $quantity,
                'notes' => $notes,
            ];
        }

        return [
            'valid' => $valid,
            'errors' => $errors,
        ];
    }

    /**
     * Calculate totals
     */
    private function calculateTotals(array $items): array
    {
        $subtotal = collect($items)->sum('subtotal');
        $tax = $subtotal * 0.19; // TVA 19%

        return [
            'subtotal' => round($subtotal, 3),
            'tax' => round($tax, 3),
            'total' => round($subtotal + $tax, 3),
        ];
    }

    /**
     * Create order from validated CSV import
     */
    public function createOrderFromImport(User $vendor, int $importId): \App\Models\Order
    {
        $importLog = CsvImportLog::findOrFail($importId);

        if ($importLog->vendor_id !== $vendor->id) {
            throw new \Exception('Unauthorized');
        }

        if ($importLog->status !== 'completed') {
            throw new \Exception('Import not completed or failed');
        }

        // Re-process to get valid items (they're not stored in DB)
        // In production, you might want to cache these in Redis
        $file = storage_path("app/csv_imports/{$importLog->filename}");
        $data = Excel::toArray([], $file)[0];
        array_shift($data);

        $results = $this->validateAndPrepareItems($vendor, $data);

        if (empty($results['valid'])) {
            throw new \Exception('No valid items to process');
        }

        // Convert to cart format
        $cartItems = collect($results['valid'])->map(function ($item) {
            return [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ];
        })->toArray();

        // Use existing OrderService
        return $this->orderService->createOrder($vendor, $cartItems);
    }
}
```

### 🎨 Frontend Example (Vue.js)

```vue
<template>
  <div class="csv-upload">
    <h2>Commande Rapide - Upload CSV</h2>

    <!-- CSV Upload -->
    <div class="upload-section">
      <input
        type="file"
        @change="handleFileUpload"
        accept=".csv,.xlsx"
        ref="fileInput"
      />
      <button @click="$refs.fileInput.click()">
        Choisir Fichier CSV/Excel
      </button>

      <a href="/api/vendor/orders/csv-template" download>
        📥 Télécharger Template CSV
      </a>
    </div>

    <!-- Copy/Paste -->
    <div class="paste-section">
      <h3>Ou coller depuis Excel:</h3>
      <textarea
        v-model="pasteData"
        placeholder="SKU    Quantité&#10;PROD-001    10&#10;PROD-002    25"
        rows="10"
      ></textarea>
      <button @click="processPasteData">Traiter</button>
    </div>

    <!-- Preview -->
    <div v-if="preview.length" class="preview">
      <h3>Aperçu ({{ preview.length }} produits)</h3>

      <table>
        <thead>
          <tr>
            <th>SKU</th>
            <th>Produit</th>
            <th>Quantité</th>
            <th>Prix Unitaire</th>
            <th>Sous-total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in preview" :key="item.sku">
            <td>{{ item.sku }}</td>
            <td>{{ item.product_name }}</td>
            <td>{{ item.quantity }}</td>
            <td>{{ formatPrice(item.unit_price) }}</td>
            <td>{{ formatPrice(item.subtotal) }}</td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4"><strong>Total</strong></td>
            <td><strong>{{ formatPrice(totals.total) }}</strong></td>
          </tr>
        </tfoot>
      </table>

      <!-- Errors -->
      <div v-if="errors.length" class="errors">
        <h4>⚠️ Erreurs ({{ errors.length }})</h4>
        <ul>
          <li v-for="(error, index) in errors" :key="index">
            Ligne {{ error.row }}, SKU {{ error.sku }}: {{ error.error }}
          </li>
        </ul>
      </div>

      <button @click="confirmOrder" :disabled="preview.length === 0">
        Créer Commande ({{ preview.length }} produits)
      </button>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      pasteData: '',
      preview: [],
      errors: [],
      totals: {},
      importId: null,
    }
  },

  methods: {
    async handleFileUpload(event) {
      const file = event.target.files[0]
      if (!file) return

      const formData = new FormData()
      formData.append('file', file)
      formData.append('auto_create_order', false)

      try {
        const response = await fetch('/api/vendor/orders/import-csv', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${this.token}`,
          },
          body: formData,
        })

        const data = await response.json()

        this.preview = data.data.preview
        this.errors = data.data.errors
        this.totals = data.data.totals
        this.importId = data.data.import_id

      } catch (error) {
        alert('Erreur lors du traitement du fichier')
      }
    },

    async processPasteData() {
      // Convert paste data to CSV format
      const lines = this.pasteData.trim().split('\n')
      const items = lines.map(line => {
        const [sku, quantity] = line.split(/\s+/)
        return { sku, quantity: parseInt(quantity) }
      })

      // Use quick order endpoint
      const response = await fetch('/api/vendor/orders/quick-order', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ items }),
      })

      const data = await response.json()
      this.preview = data.data.preview
      this.totals = data.data.totals
    },

    async confirmOrder() {
      const response = await fetch('/api/vendor/orders/confirm-csv-import', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ import_id: this.importId }),
      })

      const data = await response.json()

      if (data.status === 'success') {
        alert(`Commande créée: ${data.data.order_number}`)
        this.$router.push(`/orders/${data.data.order_id}`)
      }
    },

    formatPrice(price) {
      return new Intl.NumberFormat('fr-TN', {
        style: 'currency',
        currency: 'TND',
        minimumFractionDigits: 3,
      }).format(price)
    },
  },
}
</script>
```

### 📦 Dependencies

```bash
composer require maatwebsite/excel
```

---

## 2. Termes de Paiement NET 30/60/90

### 📊 Vue d'ensemble

Permettre aux vendeurs de commander avec paiement différé (NET 15/30/60/90 jours) avec:
- Limite de crédit par vendeur
- Suivi balance crédit disponible
- Génération factures avec échéances
- Rappels automatiques de paiement
- Remises d'escompte (early payment discount)

### 🗂️ Modèles de Données

#### Migration: Ajouter colonnes à `vendor_profiles`

```php
Schema::table('vendor_profiles', function (Blueprint $table) {
    $table->enum('payment_terms', ['immediate', 'net_15', 'net_30', 'net_60', 'net_90'])
        ->default('immediate')
        ->after('vendor_group_id');

    $table->decimal('credit_limit', 15, 3)->default(0)->after('payment_terms');
    $table->decimal('credit_used', 15, 3)->default(0)->after('credit_limit');
    $table->decimal('credit_available', 15, 3)->virtualAs('credit_limit - credit_used');

    $table->boolean('credit_hold')->default(false)->after('status');
    $table->text('credit_hold_reason')->nullable();

    $table->decimal('early_payment_discount', 5, 2)->default(0)
        ->comment('Discount % if paid early (e.g., 2.00 for 2%)');
    $table->integer('early_payment_days')->default(10)
        ->comment('Days to get discount (e.g., 10 for 2/10 NET 30)');
});
```

#### Migration: `invoices`

```php
Schema::create('invoices', function (Blueprint $table) {
    $table->id();
    $table->string('invoice_number')->unique(); // INV-2025-00001
    $table->foreignId('order_id')->constrained()->onDelete('cascade');
    $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');

    // Dates
    $table->date('invoice_date');
    $table->date('due_date');
    $table->date('paid_date')->nullable();

    // Amounts
    $table->decimal('subtotal', 15, 3);
    $table->decimal('tax', 15, 3);
    $table->decimal('total', 15, 3);
    $table->decimal('paid_amount', 15, 3)->default(0);
    $table->decimal('balance', 15, 3)->virtualAs('total - paid_amount');

    // Payment terms
    $table->enum('payment_terms', ['immediate', 'net_15', 'net_30', 'net_60', 'net_90']);
    $table->decimal('early_payment_discount', 5, 2)->default(0);
    $table->integer('early_payment_days')->default(0);
    $table->date('early_payment_deadline')->nullable();

    // Status
    $table->enum('status', ['pending', 'paid', 'partial', 'overdue', 'cancelled'])
        ->default('pending');

    $table->text('notes')->nullable();
    $table->timestamps();

    $table->index(['vendor_id', 'status']);
    $table->index('due_date');
});
```

#### Migration: `invoice_payments`

```php
Schema::create('invoice_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
    $table->string('payment_reference')->unique(); // PAY-2025-00001

    $table->decimal('amount', 15, 3);
    $table->date('payment_date');
    $table->enum('payment_method', ['bank_transfer', 'check', 'cash', 'card', 'other']);
    $table->string('transaction_id')->nullable();

    $table->boolean('applied_early_discount')->default(false);
    $table->decimal('discount_amount', 15, 3)->default(0);

    $table->text('notes')->nullable();
    $table->timestamps();
});
```

#### Migration: `payment_reminders`

```php
Schema::create('payment_reminders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('invoice_id')->constrained()->onDelete('cascade');

    $table->enum('type', ['7_days_before', '3_days_before', 'due_date', '7_days_after', '14_days_after']);
    $table->dateTime('sent_at');
    $table->enum('channel', ['email', 'sms', 'notification']);
    $table->boolean('opened')->default(false);

    $table->timestamps();
});
```

### 🎯 API Endpoints

#### 1. Get Credit Info

```http
GET /api/vendor/credit
Authorization: Bearer {token}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "payment_terms": "net_30",
    "credit_limit": 50000.000,
    "credit_used": 23450.500,
    "credit_available": 26549.500,
    "credit_hold": false,
    "early_payment_discount": 2.00,
    "early_payment_days": 10,
    "pending_invoices": [
      {
        "id": 45,
        "invoice_number": "INV-2025-00045",
        "order_number": "ORD-2025-00123",
        "invoice_date": "2025-01-15",
        "due_date": "2025-02-14",
        "total": 5600.750,
        "paid_amount": 0,
        "balance": 5600.750,
        "status": "pending",
        "days_until_due": 12,
        "is_overdue": false,
        "early_payment_deadline": "2025-01-25",
        "can_get_discount": true,
        "potential_savings": 112.015
      }
    ],
    "overdue_invoices": [],
    "total_pending": 23450.500
  }
}
```

#### 2. Get Invoices List

```http
GET /api/vendor/invoices?status=pending&page=1
```

#### 3. Download Invoice PDF

```http
GET /api/vendor/invoices/{id}/pdf
Authorization: Bearer {token}
```

**Response:** PDF file download

#### 4. Record Payment (Admin only)

```http
POST /api/admin/invoices/{id}/payment
Content-Type: application/json

{
  "amount": 5600.750,
  "payment_date": "2025-01-20",
  "payment_method": "bank_transfer",
  "transaction_id": "TRX-123456",
  "notes": "Virement bancaire reçu"
}
```

### 💻 Service Layer

#### `app/Services/CreditService.php`

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\Invoice;
use App\Models\Order;
use Carbon\Carbon;

class CreditService
{
    /**
     * Check if vendor can place order (has enough credit)
     */
    public function canPlaceOrder(User $vendor, float $orderTotal): bool
    {
        $profile = $vendor->vendorProfile;

        // If immediate payment, always allow
        if ($profile->payment_terms === 'immediate') {
            return true;
        }

        // Check credit hold
        if ($profile->credit_hold) {
            throw new \Exception("Account on credit hold: {$profile->credit_hold_reason}");
        }

        // Check credit limit
        $availableCredit = $profile->credit_limit - $profile->credit_used;

        if ($orderTotal > $availableCredit) {
            throw new \Exception(
                "Insufficient credit. Available: " . number_format($availableCredit, 3) .
                " TND, Required: " . number_format($orderTotal, 3) . " TND"
            );
        }

        return true;
    }

    /**
     * Create invoice for order
     */
    public function createInvoice(Order $order): Invoice
    {
        $vendor = $order->vendor;
        $profile = $vendor->vendorProfile;

        // Calculate due date based on payment terms
        $invoiceDate = now();
        $dueDate = $this->calculateDueDate($invoiceDate, $profile->payment_terms);

        // Calculate early payment deadline
        $earlyPaymentDeadline = null;
        if ($profile->early_payment_days > 0) {
            $earlyPaymentDeadline = $invoiceDate->copy()->addDays($profile->early_payment_days);
        }

        $invoice = Invoice::create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'subtotal' => $order->subtotal,
            'tax' => $order->tax,
            'total' => $order->total,
            'payment_terms' => $profile->payment_terms,
            'early_payment_discount' => $profile->early_payment_discount,
            'early_payment_days' => $profile->early_payment_days,
            'early_payment_deadline' => $earlyPaymentDeadline,
            'status' => 'pending',
        ]);

        // Reserve credit
        $this->reserveCredit($vendor, $order->total);

        // Schedule payment reminders
        $this->schedulePaymentReminders($invoice);

        return $invoice;
    }

    /**
     * Calculate due date based on payment terms
     */
    private function calculateDueDate(Carbon $invoiceDate, string $paymentTerms): Carbon
    {
        return match ($paymentTerms) {
            'immediate' => $invoiceDate,
            'net_15' => $invoiceDate->copy()->addDays(15),
            'net_30' => $invoiceDate->copy()->addDays(30),
            'net_60' => $invoiceDate->copy()->addDays(60),
            'net_90' => $invoiceDate->copy()->addDays(90),
            default => $invoiceDate,
        };
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $lastInvoice = Invoice::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, -5)) + 1 : 1;

        return sprintf('INV-%d-%05d', $year, $number);
    }

    /**
     * Reserve credit for vendor
     */
    private function reserveCredit(User $vendor, float $amount): void
    {
        $vendor->vendorProfile()->increment('credit_used', $amount);
    }

    /**
     * Release credit (when invoice is paid or cancelled)
     */
    public function releaseCredit(Invoice $invoice): void
    {
        $invoice->vendor->vendorProfile()->decrement('credit_used', $invoice->balance);
    }

    /**
     * Record payment
     */
    public function recordPayment(
        Invoice $invoice,
        float $amount,
        string $paymentMethod,
        ?string $transactionId = null,
        ?Carbon $paymentDate = null
    ): void {
        $paymentDate = $paymentDate ?? now();

        // Check if eligible for early payment discount
        $applyDiscount = false;
        $discountAmount = 0;

        if ($invoice->early_payment_deadline &&
            $paymentDate->lte($invoice->early_payment_deadline) &&
            $amount >= $invoice->balance) {

            $applyDiscount = true;
            $discountAmount = $invoice->total * ($invoice->early_payment_discount / 100);
            $amount -= $discountAmount;
        }

        // Create payment record
        InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'payment_reference' => $this->generatePaymentReference(),
            'amount' => $amount,
            'payment_date' => $paymentDate,
            'payment_method' => $paymentMethod,
            'transaction_id' => $transactionId,
            'applied_early_discount' => $applyDiscount,
            'discount_amount' => $discountAmount,
        ]);

        // Update invoice
        $invoice->increment('paid_amount', $amount);

        // Update status
        if ($invoice->paid_amount >= $invoice->total) {
            $invoice->update([
                'status' => 'paid',
                'paid_date' => $paymentDate,
            ]);
        } else {
            $invoice->update(['status' => 'partial']);
        }

        // Release credit
        $this->releaseCredit($invoice);

        // Send payment confirmation
        $invoice->vendor->notify(new PaymentReceivedNotification($invoice, $amount));
    }

    /**
     * Schedule payment reminders
     */
    private function schedulePaymentReminders(Invoice $invoice): void
    {
        // 7 days before due date
        SendPaymentReminderJob::dispatch($invoice, '7_days_before')
            ->delay($invoice->due_date->copy()->subDays(7));

        // 3 days before due date
        SendPaymentReminderJob::dispatch($invoice, '3_days_before')
            ->delay($invoice->due_date->copy()->subDays(3));

        // On due date
        SendPaymentReminderJob::dispatch($invoice, 'due_date')
            ->delay($invoice->due_date);

        // 7 days after due date
        SendPaymentReminderJob::dispatch($invoice, '7_days_after')
            ->delay($invoice->due_date->copy()->addDays(7));
    }
}
```

### 📄 PDF Invoice Generation

#### `app/Services/InvoicePdfService.php`

```php
<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdfService
{
    public function generate(Invoice $invoice): string
    {
        $data = [
            'invoice' => $invoice,
            'vendor' => $invoice->vendor,
            'order' => $invoice->order,
            'items' => $invoice->order->items()->with('product')->get(),
        ];

        $pdf = Pdf::loadView('invoices.pdf', $data);

        return $pdf->download($invoice->invoice_number . '.pdf');
    }
}
```

#### `resources/views/invoices/pdf.blade.php`

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Facture {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; }
        .company-info { text-align: right; margin-bottom: 20px; }
        .invoice-info { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .totals { text-align: right; }
        .payment-terms { background-color: #fffacd; padding: 15px; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>FACTURE</h1>
        <h2>{{ $invoice->invoice_number }}</h2>
    </div>

    <div class="company-info">
        <strong>B2B Wholesale Platform</strong><br>
        123 Avenue Habib Bourguiba<br>
        Tunis, Tunisie<br>
        Tél: +216 XX XXX XXX<br>
        Email: facturation@b2b-platform.tn
    </div>

    <div class="invoice-info">
        <strong>Client:</strong><br>
        {{ $vendor->vendorProfile->company_name }}<br>
        {{ $vendor->vendorProfile->address }}<br>
        TVA: {{ $vendor->vendorProfile->tax_id }}<br>
        Tél: {{ $vendor->vendorProfile->phone }}
    </div>

    <table>
        <tr>
            <td><strong>Date Facture:</strong></td>
            <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
            <td><strong>Date Échéance:</strong></td>
            <td>{{ $invoice->due_date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td><strong>N° Commande:</strong></td>
            <td>{{ $order->order_number }}</td>
            <td><strong>Conditions:</strong></td>
            <td>{{ strtoupper(str_replace('_', ' ', $invoice->payment_terms)) }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th>SKU</th>
                <th>Quantité</th>
                <th>Prix Unitaire</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item->product->name }}</td>
                <td>{{ $item->product->sku }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 3) }} TND</td>
                <td>{{ number_format($item->subtotal, 3) }} TND</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <p><strong>Sous-total:</strong> {{ number_format($invoice->subtotal, 3) }} TND</p>
        <p><strong>TVA (19%):</strong> {{ number_format($invoice->tax, 3) }} TND</p>
        <h3><strong>TOTAL:</strong> {{ number_format($invoice->total, 3) }} TND</h3>
    </div>

    @if($invoice->early_payment_discount > 0)
    <div class="payment-terms">
        <strong>⚡ Remise Escompte:</strong> {{ $invoice->early_payment_discount }}% si paiement avant le
        {{ $invoice->early_payment_deadline->format('d/m/Y') }}<br>
        <strong>Économie potentielle:</strong> {{ number_format($invoice->total * $invoice->early_payment_discount / 100, 3) }} TND
    </div>
    @endif

    <div style="margin-top: 40px; text-align: center; font-size: 10px; color: #666;">
        Merci pour votre confiance. Pour toute question, contactez facturation@b2b-platform.tn
    </div>
</body>
</html>
```

### 📦 Dependencies

```bash
composer require barryvdh/laravel-dompdf
```

---

## 3. RFQ System (Request for Quotation)

### 📊 Vue d'ensemble

Système de demande de devis permettant:
- Vendeurs: soumettre demandes de devis
- Admin: répondre avec prix personnalisés
- Négociation en plusieurs tours
- Conversion en commande

### 🗂️ Modèles de Données

#### Migration: `rfqs`

```php
Schema::create('rfqs', function (Blueprint $table) {
    $table->id();
    $table->string('rfq_number')->unique(); // RFQ-2025-00001
    $table->foreignId('vendor_id')->constrained('users')->onDelete('cascade');

    $table->string('subject');
    $table->text('description');
    $table->date('required_date')->nullable();

    $table->enum('status', ['draft', 'submitted', 'in_review', 'quoted', 'negotiating', 'accepted', 'declined', 'expired'])
        ->default('draft');

    $table->date('expires_at')->nullable();
    $table->timestamps();

    $table->index(['vendor_id', 'status']);
});
```

#### Migration: `rfq_items`

```php
Schema::create('rfq_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rfq_id')->constrained()->onDelete('cascade');
    $table->foreignId('product_id')->nullable()->constrained(); // Null if custom product

    $table->string('product_name'); // Can be custom
    $table->string('sku')->nullable();
    $table->text('description')->nullable();
    $table->integer('quantity');
    $table->decimal('target_price', 15, 3)->nullable(); // Vendor's target price

    $table->timestamps();
});
```

#### Migration: `rfq_quotes`

```php
Schema::create('rfq_quotes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rfq_id')->constrained()->onDelete('cascade');
    $table->foreignId('quoted_by')->constrained('users'); // Admin user

    $table->integer('quote_version')->default(1); // For negotiation rounds

    $table->json('items_pricing'); // Array of [item_id => [unit_price, notes]]
    $table->decimal('subtotal', 15, 3);
    $table->decimal('tax', 15, 3);
    $table->decimal('total', 15, 3);

    $table->text('notes')->nullable();
    $table->text('terms')->nullable(); // Special terms/conditions

    $table->date('valid_until');
    $table->boolean('is_active')->default(true); // Latest quote

    $table->timestamps();
});
```

#### Migration: `rfq_negotiations`

```php
Schema::create('rfq_negotiations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rfq_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained(); // Can be vendor or admin

    $table->text('message');
    $table->json('proposed_changes')->nullable(); // Price changes, quantity changes

    $table->timestamps();
});
```

### 🎯 API Endpoints

#### Vendor Endpoints

```http
POST /api/vendor/rfqs
GET /api/vendor/rfqs
GET /api/vendor/rfqs/{id}
PUT /api/vendor/rfqs/{id}
POST /api/vendor/rfqs/{id}/submit
POST /api/vendor/rfqs/{id}/accept-quote
POST /api/vendor/rfqs/{id}/decline-quote
POST /api/vendor/rfqs/{id}/negotiate
POST /api/vendor/rfqs/{id}/convert-to-order
```

#### Admin Endpoints

```http
GET /api/admin/rfqs
GET /api/admin/rfqs/{id}
POST /api/admin/rfqs/{id}/quote
PUT /api/admin/rfqs/{id}/quote
POST /api/admin/rfqs/{id}/respond
```

### 💻 Service Layer

#### `app/Services/RfqService.php`

```php
<?php

namespace App\Services;

use App\Models\Rfq;
use App\Models\RfqQuote;
use App\Models\User;

class RfqService
{
    /**
     * Create RFQ from vendor
     */
    public function createRfq(User $vendor, array $data): Rfq
    {
        $rfq = Rfq::create([
            'rfq_number' => $this->generateRfqNumber(),
            'vendor_id' => $vendor->id,
            'subject' => $data['subject'],
            'description' => $data['description'],
            'required_date' => $data['required_date'] ?? null,
            'status' => 'draft',
        ]);

        // Add items
        foreach ($data['items'] as $itemData) {
            $rfq->items()->create([
                'product_id' => $itemData['product_id'] ?? null,
                'product_name' => $itemData['product_name'],
                'sku' => $itemData['sku'] ?? null,
                'description' => $itemData['description'] ?? null,
                'quantity' => $itemData['quantity'],
                'target_price' => $itemData['target_price'] ?? null,
            ]);
        }

        return $rfq;
    }

    /**
     * Submit RFQ for review
     */
    public function submitRfq(Rfq $rfq): void
    {
        if ($rfq->items()->count() === 0) {
            throw new \Exception('RFQ must have at least one item');
        }

        $rfq->update([
            'status' => 'submitted',
            'expires_at' => now()->addDays(30),
        ]);

        // Notify admins
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new NewRfqSubmittedNotification($rfq));
        }
    }

    /**
     * Create quote from admin
     */
    public function createQuote(Rfq $rfq, User $admin, array $data): RfqQuote
    {
        // Deactivate previous quotes
        $rfq->quotes()->update(['is_active' => false]);

        // Get latest version number
        $latestVersion = $rfq->quotes()->max('quote_version') ?? 0;

        $quote = RfqQuote::create([
            'rfq_id' => $rfq->id,
            'quoted_by' => $admin->id,
            'quote_version' => $latestVersion + 1,
            'items_pricing' => $data['items_pricing'],
            'subtotal' => $data['subtotal'],
            'tax' => $data['tax'],
            'total' => $data['total'],
            'notes' => $data['notes'] ?? null,
            'terms' => $data['terms'] ?? null,
            'valid_until' => $data['valid_until'] ?? now()->addDays(14),
            'is_active' => true,
        ]);

        $rfq->update(['status' => 'quoted']);

        // Notify vendor
        $rfq->vendor->notify(new RfqQuoteReceivedNotification($rfq, $quote));

        return $quote;
    }

    /**
     * Convert RFQ to order
     */
    public function convertToOrder(Rfq $rfq, OrderService $orderService): Order
    {
        $activeQuote = $rfq->quotes()->where('is_active', true)->first();

        if (!$activeQuote) {
            throw new \Exception('No active quote found');
        }

        if ($rfq->status !== 'accepted') {
            throw new \Exception('RFQ must be accepted before converting to order');
        }

        // Build cart items from RFQ items with quoted prices
        $cartItems = [];
        $itemsPricing = $activeQuote->items_pricing;

        foreach ($rfq->items as $item) {
            if (!$item->product_id) {
                throw new \Exception('Cannot convert RFQ with custom products');
            }

            $cartItems[] = [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'custom_price' => $itemsPricing[$item->id]['unit_price'], // Use quoted price
            ];
        }

        // Create order with custom prices
        $order = $orderService->createOrderWithCustomPrices(
            $rfq->vendor,
            $cartItems,
            "Converted from RFQ {$rfq->rfq_number}"
        );

        $rfq->update(['status' => 'converted']);

        return $order;
    }

    private function generateRfqNumber(): string
    {
        $year = now()->year;
        $last = Rfq::whereYear('created_at', $year)->max('id') ?? 0;
        return sprintf('RFQ-%d-%05d', $year, $last + 1);
    }
}
```

---

## 4. Multi-Comptes & Workflow Approbation

*(Specifications for multi-user accounts and approval workflows...)*

---

## 5. Moteur de Recommandations IA

### 📊 Vue d'ensemble

Système de recommandations basé sur:
- Historique achats vendeur
- Produits fréquemment achetés ensemble
- Tendances saisonnières
- Popularité globale

### 🧠 Algorithme de Recommandation

```php
<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RecommendationService
{
    /**
     * Get personalized product recommendations for vendor
     */
    public function getRecommendations(User $vendor, int $limit = 10): Collection
    {
        $cacheKey = "recommendations:vendor:{$vendor->id}";

        return Cache::remember($cacheKey, 3600, function () use ($vendor, $limit) {
            $recommendations = collect();

            // 1. Frequently bought together (40% weight)
            $frequentlyBoughtTogether = $this->getFrequentlyBoughtTogether($vendor);
            $recommendations = $recommendations->merge($frequentlyBoughtTogether->take(4));

            // 2. Reorder suggestions (30% weight)
            $reorderSuggestions = $this->getReorderSuggestions($vendor);
            $recommendations = $recommendations->merge($reorderSuggestions->take(3));

            // 3. Trending in your group (20% weight)
            $trendingInGroup = $this->getTrendingInGroup($vendor);
            $recommendations = $recommendations->merge($trendingInGroup->take(2));

            // 4. New arrivals (10% weight)
            $newArrivals = $this->getNewArrivals($vendor);
            $recommendations = $recommendations->merge($newArrivals->take(1));

            return $recommendations->unique('id')->take($limit);
        });
    }

    /**
     * Collaborative filtering: Products bought together
     */
    private function getFrequentlyBoughtTogether(User $vendor): Collection
    {
        // Get products vendor has bought
        $purchasedProducts = $vendor->orders()
            ->with('items.product')
            ->get()
            ->pluck('items.*.product_id')
            ->flatten()
            ->unique();

        if ($purchasedProducts->isEmpty()) {
            return collect();
        }

        // Find products frequently bought together by other vendors
        return DB::table('order_items as oi1')
            ->join('order_items as oi2', 'oi1.order_id', '=', 'oi2.order_id')
            ->join('products', 'oi2.product_id', '=', 'products.id')
            ->whereIn('oi1.product_id', $purchasedProducts)
            ->where('oi2.product_id', '!=', DB::raw('oi1.product_id'))
            ->whereNotIn('oi2.product_id', $purchasedProducts)
            ->select('products.*', DB::raw('COUNT(*) as frequency'))
            ->groupBy('products.id')
            ->orderByDesc('frequency')
            ->limit(10)
            ->get();
    }

    /**
     * Suggest products vendor should reorder
     */
    private function getReorderSuggestions(User $vendor): Collection
    {
        // Products ordered in last 90 days but not in last 30 days
        $oldOrders = $vendor->orders()
            ->whereBetween('created_at', [now()->subDays(90), now()->subDays(30)])
            ->with('items.product')
            ->get()
            ->pluck('items.*.product')
            ->flatten()
            ->unique('id');

        $recentOrders = $vendor->orders()
            ->where('created_at', '>=', now()->subDays(30))
            ->with('items')
            ->get()
            ->pluck('items.*.product_id')
            ->flatten();

        return $oldOrders->filter(function ($product) use ($recentOrders) {
            return !$recentOrders->contains($product->id);
        });
    }

    /**
     * Trending products in vendor's group
     */
    private function getTrendingInGroup(User $vendor): Collection
    {
        $groupId = $vendor->vendorProfile->vendor_group_id;

        return DB::table('orders')
            ->join('users', 'orders.vendor_id', '=', 'users.id')
            ->join('vendor_profiles', 'users.id', '=', 'vendor_profiles.user_id')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('vendor_profiles.vendor_group_id', $groupId)
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->select('products.*', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('products.id')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();
    }

    /**
     * New arrivals
     */
    private function getNewArrivals(User $vendor): Collection
    {
        $catalogService = app(CatalogService::class);
        $visibleProducts = $catalogService->getVisibleProducts($vendor);

        return $visibleProducts
            ->where('created_at', '>=', now()->subDays(14))
            ->sortByDesc('created_at')
            ->take(10);
    }
}
```

---

## 6. Quick Wins Techniques

### ⚡ 1. Factures PDF (3-5 jours)

Déjà couvert dans la section NET 30/60/90.

### ⚡ 2. Export Commandes CSV (2-3 jours)

```php
// app/Exports/OrdersExport.php
<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrdersExport implements FromQuery, WithHeadings, WithMapping
{
    protected $vendor;

    public function __construct($vendor)
    {
        $this->vendor = $vendor;
    }

    public function query()
    {
        return Order::where('vendor_id', $this->vendor->id)
            ->with('items.product');
    }

    public function headings(): array
    {
        return [
            'N° Commande',
            'Date',
            'Statut',
            'Total HT',
            'TVA',
            'Total TTC',
            'Produits',
        ];
    }

    public function map($order): array
    {
        $products = $order->items->map(function ($item) {
            return "{$item->product->sku} x{$item->quantity}";
        })->join(', ');

        return [
            $order->order_number,
            $order->created_at->format('d/m/Y'),
            $order->status,
            $order->subtotal,
            $order->tax,
            $order->total,
            $products,
        ];
    }
}
```

**Endpoint:**
```http
GET /api/vendor/orders/export?format=csv|xlsx
```

### ⚡ 3. Quick Reorder 1-Click (3-5 jours)

```php
// API Endpoint
POST /api/vendor/orders/{id}/reorder
```

```php
public function reorder(Order $originalOrder, OrderService $orderService)
{
    // Duplicate order items
    $cartItems = $originalOrder->items->map(function ($item) {
        return [
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
        ];
    })->toArray();

    // Create new order
    $newOrder = $orderService->createOrder(auth()->user(), $cartItems);

    return response()->json([
        'status' => 'success',
        'data' => $newOrder,
        'message' => 'Order duplicated successfully',
    ]);
}
```

---

## 📦 Installation des dépendances

```bash
# CSV/Excel
composer require maatwebsite/excel

# PDF
composer require barryvdh/laravel-dompdf

# API Resources
composer require spatie/laravel-query-builder

# Optional: Laravel Horizon for queue monitoring
composer require laravel/horizon
```

---

## 🎯 Priorités d'implémentation

### Sprint 1 (Semaine 1-2): CSV Upload
1. Migrations (csv_import_logs, order_templates)
2. CsvOrderService
3. API endpoints
4. Tests

### Sprint 2 (Semaine 3-5): NET 30/60/90
1. Migrations (invoices, invoice_payments, payment_reminders)
2. CreditService
3. InvoicePdfService
4. API endpoints
5. Scheduled jobs pour rappels
6. Tests

### Sprint 3 (Semaine 6-7): RFQ
1. Migrations (rfqs, rfq_items, rfq_quotes, rfq_negotiations)
2. RfqService
3. API endpoints
4. Tests

### Sprint 4 (Semaine 8-9): Quick Wins
1. Export commandes CSV/Excel
2. Reorder 1-click
3. Tests

---

## 🧪 Tests à créer

Pour chaque fonctionnalité, créer:

**Unit Tests:**
- Service methods
- Business logic
- Calculations

**Feature Tests:**
- API endpoints
- Request validation
- Responses
- Permissions

**Exemple:**
```php
// tests/Unit/Services/CreditServiceTest.php
public function test_can_check_if_vendor_has_sufficient_credit()
{
    $vendor = User::factory()->vendor()->create();
    $vendor->vendorProfile->update([
        'payment_terms' => 'net_30',
        'credit_limit' => 10000,
        'credit_used' => 5000,
    ]);

    $creditService = app(CreditService::class);

    $this->assertTrue($creditService->canPlaceOrder($vendor, 4000));
    $this->assertFalse($creditService->canPlaceOrder($vendor, 6000));
}
```

---

**Prochaine étape**: Valider les priorités et commencer l'implémentation avec Sprint 1 (CSV Upload).
