# 📡 API Documentation - B2B Wholesale Platform

Documentation complète de l'API REST de la plateforme B2B.

## 🌐 Base URL

```
http://localhost:8000/api
```

## 🔐 Authentication

L'API utilise **Laravel Sanctum** pour l'authentification par tokens.

### Login

```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "vendor@example.com",
  "password": "password123"
}
```

**Response 200 OK:**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "vendor@example.com",
    "role": "vendor",
    "status": "active"
  },
  "token": "1|abc123def456ghi789...",
  "token_type": "Bearer"
}
```

### Authenticated Requests

```http
GET /api/vendor/products
Authorization: Bearer 1|abc123def456ghi789...
```

### Logout

```http
POST /api/auth/logout
Authorization: Bearer {token}
```

### Logout All Sessions

```http
POST /api/auth/logout-all
Authorization: Bearer {token}
```

## 👤 User Profile

### Get Current User

```http
GET /api/auth/me
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "vendor@example.com",
  "role": "vendor",
  "status": "active",
  "vendor_profile": {
    "company_name": "ABC Trading",
    "tax_id": "TAX123456",
    "phone": "+21612345678",
    "vendor_group": {
      "name": "VIP",
      "discount_percentage": 15.000
    }
  }
}
```

### Update Profile

```http
PUT /api/auth/profile
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "John Smith",
  "phone": "+21698765432"
}
```

### Change Password

```http
POST /api/auth/change-password
Authorization: Bearer {token}
Content-Type: application/json

{
  "current_password": "old_password",
  "password": "new_password",
  "password_confirmation": "new_password"
}
```

---

## 🛍️ VENDOR API

### Products

#### List Products

```http
GET /api/vendor/products
Authorization: Bearer {token}

Query Parameters:
  - category_id      (optional) Filter by category
  - search           (optional) Search by name or SKU
  - in_stock         (optional) Only in-stock products (0|1)
  - sort_by          (optional) price|name|created_at (default: name)
  - sort_order       (optional) asc|desc (default: asc)
  - per_page         (optional) Results per page (default: 15)
  - page             (optional) Page number
```

**Example:**
```http
GET /api/vendor/products?category_id=5&in_stock=1&per_page=20
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "sku": "PROD-001",
      "name": "iPhone 15 Pro",
      "category": {
        "id": 5,
        "name": "Smartphones"
      },
      "base_price": 4500.000,
      "stock_quantity": 25,
      "minimum_order_quantity": 1,
      "order_multiple": 1,
      "is_in_stock": true,
      "is_low_stock": false
    }
  ],
  "current_page": 1,
  "per_page": 20,
  "total": 45,
  "last_page": 3
}
```

#### Get Product Details

```http
GET /api/vendor/products/{product_id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": 1,
  "sku": "PROD-001",
  "name": "iPhone 15 Pro",
  "description": "Latest flagship smartphone",
  "category": {
    "id": 5,
    "name": "Smartphones",
    "full_path": "Electronics > Smartphones"
  },
  "base_price": 4500.000,
  "stock_quantity": 25,
  "minimum_order_quantity": 1,
  "order_multiple": 1,
  "allow_backorder": false,
  "images": [
    {
      "id": 1,
      "url": "http://localhost/storage/products/iphone.jpg",
      "is_primary": true
    }
  ],
  "pricing_tiers": [
    {
      "min_quantity": 1,
      "max_quantity": 9,
      "price": 4500.000
    },
    {
      "min_quantity": 10,
      "max_quantity": 49,
      "price": 4300.000
    },
    {
      "min_quantity": 50,
      "max_quantity": null,
      "price": 4100.000
    }
  ]
}
```

#### Search Products

```http
GET /api/vendor/products/search?q=iPhone
Authorization: Bearer {token}
```

#### Get Categories

```http
GET /api/vendor/products/categories
Authorization: Bearer {token}
```

**Response:**
```json
[
  {
    "id": 1,
    "name": "Electronics",
    "products_count": 145
  },
  {
    "id": 5,
    "name": "Smartphones",
    "products_count": 45,
    "parent_id": 1
  }
]
```

#### Calculate Price

```http
POST /api/vendor/products/{product_id}/calculate-price
Authorization: Bearer {token}
Content-Type: application/json

{
  "quantity": 10
}
```

**Response:**
```json
{
  "base_price": 4500.000,
  "discount_percentage": 4.44,
  "promotion_discount": 200.000,
  "final_price": 4300.000,
  "unit_price": 4300.000,
  "quantity": 10,
  "total_price": 43000.000
}
```

### Orders

#### List Orders

```http
GET /api/vendor/orders
Authorization: Bearer {token}

Query Parameters:
  - status           (optional) pending|confirmed|processing|shipped|delivered|cancelled
  - from_date        (optional) YYYY-MM-DD
  - to_date          (optional) YYYY-MM-DD
  - search           (optional) Search by order number
  - per_page         (optional) Results per page
```

**Example:**
```http
GET /api/vendor/orders?status=pending&from_date=2025-01-01
```

**Response:**
```json
{
  "data": [
    {
      "id": 42,
      "order_number": "ORD-20250116-ABC123",
      "status": "pending",
      "subtotal": 43000.000,
      "discount_amount": 2000.000,
      "total": 41000.000,
      "items_count": 2,
      "created_at": "2025-01-16T10:30:00Z"
    }
  ]
}
```

#### Create Order

```http
POST /api/vendor/orders
Authorization: Bearer {token}
Content-Type: application/json

{
  "cart_items": [
    {
      "product_id": 1,
      "quantity": 10
    },
    {
      "product_id": 5,
      "quantity": 20
    }
  ],
  "shipping_address": "123 Main St, Tunis",
  "billing_address": "123 Main St, Tunis",
  "notes": "Please deliver before weekend"
}
```

**Response 201 Created:**
```json
{
  "id": 42,
  "order_number": "ORD-20250116-ABC123",
  "status": "pending",
  "subtotal": 43000.000,
  "discount_amount": 2000.000,
  "total": 41000.000,
  "items": [
    {
      "product_id": 1,
      "product_name": "iPhone 15 Pro",
      "product_sku": "PROD-001",
      "quantity": 10,
      "unit_price": 4300.000,
      "subtotal": 43000.000
    }
  ],
  "shipping_address": "123 Main St, Tunis",
  "vendor_notes": "Please deliver before weekend",
  "created_at": "2025-01-16T10:30:00Z"
}
```

#### Get Order Details

```http
GET /api/vendor/orders/{order_id}
Authorization: Bearer {token}
```

#### Cancel Order

```http
POST /api/vendor/orders/{order_id}/cancel
Authorization: Bearer {token}
Content-Type: application/json

{
  "reason": "Changed my mind"
}
```

**Note:** Only pending/confirmed orders can be cancelled.

#### Order Statistics

```http
GET /api/vendor/orders/stats
Authorization: Bearer {token}
```

**Response:**
```json
{
  "total_orders": 156,
  "pending_orders": 12,
  "processing_orders": 8,
  "delivered_orders": 120,
  "cancelled_orders": 16,
  "total_spent": 450000.000,
  "average_order_value": 3750.000
}
```

### Cart

#### Validate Cart

```http
POST /api/vendor/cart/validate
Authorization: Bearer {token}
Content-Type: application/json

{
  "cart_items": [
    {
      "product_id": 1,
      "quantity": 10
    }
  ]
}
```

**Response:**
```json
{
  "valid": true,
  "errors": []
}
```

**Or if invalid:**
```json
{
  "valid": false,
  "errors": [
    "Item #0: Minimum quantity for PROD-001 is 5",
    "Item #1: Insufficient stock for PROD-002"
  ]
}
```

#### Calculate Cart Total

```http
POST /api/vendor/cart/calculate
Authorization: Bearer {token}
Content-Type: application/json

{
  "cart_items": [
    {
      "product_id": 1,
      "quantity": 10
    },
    {
      "product_id": 5,
      "quantity": 20
    }
  ]
}
```

**Response:**
```json
{
  "items": [
    {
      "product_id": 1,
      "product_name": "iPhone 15 Pro",
      "quantity": 10,
      "unit_price": 4300.000,
      "promotion_discount": 200.000,
      "subtotal": 43000.000
    }
  ],
  "subtotal": 85000.000,
  "total_promotion_discount": 4000.000,
  "total": 81000.000,
  "minimum_order_amount": 100.000,
  "meets_minimum": true
}
```

#### CSV Import / Quick Order

##### Download CSV Template

```http
GET /api/vendor/orders/csv-template
Authorization: Bearer {token}
```

**Response:** CSV file download
```csv
SKU,Quantity,Notes
PROD-001,10,Urgent
PROD-002,25,
PROD-003,5,Standard delivery
```

##### Upload CSV File

```http
POST /api/vendor/orders/import-csv
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
  "file": <csv_file>
}
```

**Response 200 OK:**
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
      "total": 14940.300,
      "items_count": 42
    }
  }
}
```

##### Confirm CSV Import & Create Order

```http
POST /api/vendor/orders/confirm-csv-import
Authorization: Bearer {token}
Content-Type: application/json

{
  "import_id": 123
}
```

**Response 201 Created:**
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

##### Quick Order (Manual Entry / Copy-Paste)

```http
POST /api/vendor/orders/quick-order
Authorization: Bearer {token}
Content-Type: application/json

{
  "items": [
    {"sku": "PROD-001", "quantity": 10, "notes": "Urgent"},
    {"sku": "PROD-002", "quantity": 25},
    {"sku": "PROD-003", "quantity": 5}
  ]
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "valid_items": [
      {
        "product_id": 1,
        "sku": "PROD-001",
        "product_name": "Product A",
        "quantity": 10,
        "unit_price": 25.500,
        "subtotal": 255.000,
        "notes": "Urgent"
      }
    ],
    "errors": [],
    "totals": {
      "subtotal": 1275.500,
      "tax": 255.100,
      "total": 1530.600,
      "items_count": 3
    }
  }
}
```

##### Create Order from Quick Order

```http
POST /api/vendor/orders/create-from-quick-order
Authorization: Bearer {token}
Content-Type: application/json

{
  "items": [
    {"sku": "PROD-001", "quantity": 10},
    {"sku": "PROD-002", "quantity": 25}
  ]
}
```

**Response 201 Created:**
```json
{
  "status": "success",
  "data": {
    "order_id": 790,
    "order_number": "ORD-2025-00790",
    "items_count": 2,
    "total": 1530.600
  }
}
```

#### Reorder (Duplicate Order)

```http
POST /api/vendor/orders/{order_id}/reorder
Authorization: Bearer {token}
```

**Response 201 Created:**
```json
{
  "status": "success",
  "message": "Commande dupliquée avec succès",
  "data": {
    "order_id": 791,
    "order_number": "ORD-2025-00791",
    "items_count": 5,
    "total": 5430.750
  }
}
```

**Error if items unavailable (422):**
```json
{
  "status": "warning",
  "message": "Certains articles ne sont plus disponibles",
  "errors": [
    "Product PROD-001 out of stock",
    "Minimum quantity for PROD-002 is 10"
  ],
  "valid_items": [...]
}
```

#### Download Invoice PDF

```http
GET /api/vendor/orders/{order_id}/invoice
Authorization: Bearer {token}
```

**Response:** PDF file download
- Content-Type: application/pdf
- Content-Disposition: attachment; filename="invoice-ORD-2025-00789.pdf"

#### Export Orders (CSV/Excel)

```http
GET /api/vendor/orders/export
Authorization: Bearer {token}

Query Parameters:
  - format          (optional) csv|xlsx (default: xlsx)
  - status          (optional) pending|confirmed|processing|shipped|delivered|cancelled
  - start_date      (optional) YYYY-MM-DD
  - end_date        (optional) YYYY-MM-DD
```

**Example:**
```http
GET /api/vendor/orders/export?format=xlsx&status=delivered&start_date=2025-01-01&end_date=2025-01-31
```

**Response:** Excel/CSV file download
- Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet (for xlsx)
- Content-Type: text/csv (for csv)
- Content-Disposition: attachment; filename="commandes_2025-01-16_143025.xlsx"

**File Contents:**
| N° Commande | Date | Statut | Nombre Articles | Sous-total HT (TND) | TVA (TND) | Total TTC (TND) | Produits |
|-------------|------|--------|-----------------|---------------------|-----------|-----------------|----------|
| ORD-2025-00789 | 16/01/2025 14:30 | DELIVERED | 3 | 12450.250 | 2490.050 | 14940.300 | PROD-001 (Product A) x10; PROD-002 (Product B) x25 |

### Chat

#### Get Conversation

```http
GET /api/vendor/chat
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": 15,
  "vendor_id": 3,
  "is_active": true,
  "unread_vendor_count": 2,
  "last_message_at": "2025-01-16T10:30:00Z",
  "latest_message": {
    "id": 245,
    "message": "Your order has been confirmed",
    "sender": {
      "id": 1,
      "name": "Admin",
      "role": "admin"
    },
    "created_at": "2025-01-16T10:30:00Z"
  }
}
```

#### Send Message

```http
POST /api/vendor/chat/send
Authorization: Bearer {token}
Content-Type: application/json

{
  "message": "Hello, I have a question about my order #ORD-123",
  "attachments": [
    {
      "path": "chat-attachments/3/file.pdf",
      "original_name": "invoice.pdf",
      "url": "http://localhost/storage/chat-attachments/3/file.pdf"
    }
  ]
}
```

**Response 201 Created:**
```json
{
  "id": 246,
  "conversation_id": 15,
  "sender_id": 3,
  "message": "Hello, I have a question about my order #ORD-123",
  "attachments": [...],
  "is_read": false,
  "created_at": "2025-01-16T10:35:00Z",
  "sender": {
    "id": 3,
    "name": "John Doe",
    "role": "vendor"
  }
}
```

#### Get Messages

```http
GET /api/vendor/chat/messages?per_page=50
Authorization: Bearer {token}
```

#### Get Recent Messages

```http
GET /api/vendor/chat/recent?limit=20
Authorization: Bearer {token}
```

#### Mark as Read

```http
POST /api/vendor/chat/mark-as-read
Authorization: Bearer {token}
```

#### Get Unread Count

```http
GET /api/vendor/chat/unread-count
Authorization: Bearer {token}
```

**Response:**
```json
{
  "count": 2
}
```

---

## 🔧 ADMIN API

### Vendors

#### List Vendors

```http
GET /api/admin/vendors
Authorization: Bearer {token}

Query Parameters:
  - status           (optional) active|inactive
  - vendor_group_id  (optional) Filter by group
  - search           (optional) Search by name/email/company
  - per_page         (optional) Results per page
```

#### Create Vendor

```http
POST /api/admin/vendors
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "John Doe",
  "email": "vendor@example.com",
  "password": "SecurePassword123!",
  "password_confirmation": "SecurePassword123!",
  "company_name": "ABC Trading LLC",
  "tax_id": "TAX123456",
  "phone": "+21612345678",
  "vendor_group_id": 2,
  "credit_limit": 10000.000,
  "shipping_address": "123 Main St, Tunis",
  "billing_address": "123 Main St, Tunis"
}
```

#### Update Vendor

```http
PUT /api/admin/vendors/{vendor_id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "John Smith",
  "status": "active",
  "vendor_group_id": 1,
  "credit_limit": 15000.000
}
```

#### Delete Vendor

```http
DELETE /api/admin/vendors/{vendor_id}
Authorization: Bearer {token}
```

#### Get Vendor Groups

```http
GET /api/admin/vendors/groups
Authorization: Bearer {token}
```

**Response:**
```json
[
  {
    "id": 1,
    "name": "VIP",
    "discount_percentage": 15.000,
    "minimum_order_amount": 500.000
  },
  {
    "id": 2,
    "name": "Gold",
    "discount_percentage": 10.000,
    "minimum_order_amount": 200.000
  }
]
```

### Products

#### List Products

```http
GET /api/admin/products
Authorization: Bearer {token}

Query Parameters:
  - category_id      (optional) Filter by category
  - is_active        (optional) Filter active/inactive (0|1)
  - search           (optional) Search by name/SKU
  - per_page         (optional) Results per page
```

#### Create Product

```http
POST /api/admin/products
Authorization: Bearer {token}
Content-Type: application/json

{
  "sku": "NEW-PROD-001",
  "name_fr": "Nouveau Produit",
  "name_ar": "منتج جديد",
  "description_fr": "Description en français",
  "description_ar": "وصف بالعربية",
  "category_id": 5,
  "base_price": 1500.000,
  "stock_quantity": 100,
  "minimum_order_quantity": 1,
  "order_multiple": 1,
  "low_stock_threshold": 10,
  "allow_backorder": false,
  "is_active": true
}
```

#### Update Product

```http
PUT /api/admin/products/{product_id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "base_price": 1600.000,
  "stock_quantity": 150,
  "is_active": true
}
```

#### Adjust Stock

```http
POST /api/admin/products/{product_id}/adjust-stock
Authorization: Bearer {token}
Content-Type: application/json

{
  "new_quantity": 200,
  "notes": "Physical inventory count"
}
```

#### Get Stock History

```http
GET /api/admin/products/{product_id}/stock-history
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": [
    {
      "type": "adjustment",
      "quantity": 50,
      "quantity_before": 150,
      "quantity_after": 200,
      "notes": "Physical inventory count",
      "created_by": {
        "name": "Admin User"
      },
      "created_at": "2025-01-16T09:00:00Z"
    }
  ]
}
```

#### Set Vendor-Specific Pricing

```http
POST /api/admin/products/{product_id}/vendor-pricing
Authorization: Bearer {token}
Content-Type: application/json

{
  "vendor_id": 3,
  "price": 1400.000
}
```

#### Set Group Pricing

```http
POST /api/admin/products/{product_id}/group-pricing
Authorization: Bearer {token}
Content-Type: application/json

{
  "vendor_group_id": 1,
  "price": 1350.000
}
```

#### Set Vendor Visibility

```http
POST /api/admin/products/{product_id}/vendor-visibility
Authorization: Bearer {token}
Content-Type: application/json

{
  "vendor_id": 3,
  "is_visible": false
}
```

#### Get Inventory Statistics

```http
GET /api/admin/products/inventory-stats
Authorization: Bearer {token}
```

**Response:**
```json
{
  "total_products": 245,
  "in_stock_products": 210,
  "low_stock_products": 15,
  "out_of_stock_products": 20,
  "total_stock_value": 1250000.000,
  "in_stock_percentage": 85.71
}
```

#### Get Low Stock Products

```http
GET /api/admin/products/low-stock
Authorization: Bearer {token}
```

### Orders

#### List Orders

```http
GET /api/admin/orders
Authorization: Bearer {token}

Query Parameters:
  - status           (optional) Filter by status
  - vendor_id        (optional) Filter by vendor
  - from_date        (optional) YYYY-MM-DD
  - to_date          (optional) YYYY-MM-DD
  - is_priority      (optional) Priority orders (0|1)
  - search           (optional) Search by order number
```

#### Confirm Order

```http
POST /api/admin/orders/{order_id}/confirm
Authorization: Bearer {token}
Content-Type: application/json

{
  "notes": "Order confirmed, will process tomorrow"
}
```

#### Start Processing

```http
POST /api/admin/orders/{order_id}/start-processing
Authorization: Bearer {token}
Content-Type: application/json

{
  "notes": "Started preparing order items"
}
```

#### Ship Order

```http
POST /api/admin/orders/{order_id}/ship
Authorization: Bearer {token}
Content-Type: application/json

{
  "tracking_number": "TRACK123456789",
  "carrier": "DHL Express",
  "notes": "Shipped via DHL, estimated delivery 2 days"
}
```

**Note:** This automatically deducts stock from inventory.

#### Deliver Order

```http
POST /api/admin/orders/{order_id}/deliver
Authorization: Bearer {token}
Content-Type: application/json

{
  "notes": "Delivered successfully, signed by customer"
}
```

#### Cancel Order

```http
POST /api/admin/orders/{order_id}/cancel
Authorization: Bearer {token}
Content-Type: application/json

{
  "reason": "Product out of stock, cannot fulfill"
}
```

**Note:** This automatically releases reserved stock.

#### Update Notes

```http
PUT /api/admin/orders/{order_id}/notes
Authorization: Bearer {token}
Content-Type: application/json

{
  "admin_notes": "Special handling required for fragile items"
}
```

#### Order Statistics

```http
GET /api/admin/orders/stats?from_date=2025-01-01&to_date=2025-01-31
Authorization: Bearer {token}
```

**Response:**
```json
{
  "total_orders": 356,
  "pending_orders": 24,
  "confirmed_orders": 18,
  "processing_orders": 15,
  "shipped_orders": 12,
  "delivered_orders": 265,
  "cancelled_orders": 22,
  "total_revenue": 1850000.000,
  "average_order_value": 6981.132
}
```

### Chat

#### List Conversations

```http
GET /api/admin/chat
Authorization: Bearer {token}

Query Parameters:
  - has_unread        (optional) Only with unread messages (0|1)
  - is_active         (optional) Active/archived (0|1)
  - search            (optional) Search by vendor name/email
```

#### Get Unread Conversations

```http
GET /api/admin/chat/unread
Authorization: Bearer {token}
```

#### Send Message

```http
POST /api/admin/chat/{conversation_id}/send
Authorization: Bearer {token}
Content-Type: application/json

{
  "message": "Hello! How can I help you today?"
}
```

#### Archive Conversation

```http
POST /api/admin/chat/{conversation_id}/archive
Authorization: Bearer {token}
```

#### Reactivate Conversation

```http
POST /api/admin/chat/{conversation_id}/reactivate
Authorization: Bearer {token}
```

#### Chat Statistics

```http
GET /api/admin/chat/stats
Authorization: Bearer {token}
```

**Response:**
```json
{
  "total_conversations": 125,
  "active_conversations": 98,
  "conversations_with_unread": 15,
  "total_messages": 4562,
  "unread_messages": 45,
  "total_unread_for_admin": 45
}
```

---

## 📊 Response Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request format |
| 401 | Unauthorized | Authentication required |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation failed |
| 500 | Server Error | Internal server error |

## ⚠️ Error Responses

### Validation Error (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ],
    "password": [
      "The password must be at least 8 characters."
    ]
  }
}
```

### Unauthorized (401)

```json
{
  "message": "Unauthenticated."
}
```

### Forbidden (403)

```json
{
  "message": "This action is unauthorized."
}
```

### Not Found (404)

```json
{
  "message": "Resource not found."
}
```

---

# 💳 Invoices & Credit Management

## GET /vendor/invoices

Get list of vendor's invoices with pagination and filters.

**Auth**: Required (Vendor)

**Query Parameters**:
- `status`: Filter by status (pending, paid, partial, overdue, cancelled)
- `start_date`: Filter from date (YYYY-MM-DD)
- `end_date`: Filter to date (YYYY-MM-DD)
- `per_page`: Items per page (default: 20)

**Response**:
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "invoice_number": "INV-2025-00001",
        "order_id": 123,
        "vendor_id": 5,
        "invoice_date": "2025-01-15",
        "due_date": "2025-02-14",
        "paid_date": null,
        "subtotal": "1000.000",
        "tax": "190.000",
        "total": "1190.000",
        "paid_amount": "0.000",
        "payment_terms": "net_30",
        "early_payment_discount": "2.00",
        "early_payment_days": 10,
        "early_payment_deadline": "2025-01-25",
        "status": "pending",
        "notes": null,
        "created_at": "2025-01-15T10:30:00.000000Z",
        "order": {
          "id": 123,
          "order_number": "ORD-2025-00123"
        },
        "payments": []
      }
    ],
    "total": 15
  }
}
```

## GET /vendor/invoices/{invoice}

Get specific invoice details including payments and early payment information.

**Auth**: Required (Vendor)

**Response**:
```json
{
  "status": "success",
  "data": {
    "invoice": {
      "id": 1,
      "invoice_number": "INV-2025-00001",
      "order": {...},
      "payments": [...],
      "reminders": [...]
    },
    "early_payment_info": {
      "eligible": true,
      "discount_percentage": 2.00,
      "discount_amount": "23.800",
      "amount_to_pay": "1166.200",
      "savings": "23.800",
      "deadline": "2025-01-25T00:00:00.000000Z",
      "days_remaining": 5
    }
  }
}
```

## POST /vendor/invoices/{invoice}/payment

Record a payment on an invoice.

**Auth**: Required (Vendor)

**Request**:
```json
{
  "amount": 500.000,
  "payment_method": "bank_transfer",
  "transaction_reference": "TRANS-123456",
  "notes": "Partial payment"
}
```

**Validation**:
- `amount`: required, numeric, min:0.001
- `payment_method`: required, in:[bank_transfer,check,cash,card,other]
- `transaction_reference`: optional, string, max:255
- `notes`: optional, string

**Response**:
```json
{
  "status": "success",
  "message": "Payment recorded successfully",
  "data": {
    "invoice": {
      "id": 1,
      "paid_amount": "500.000",
      "status": "partial",
      "payments": [
        {
          "id": 1,
          "payment_number": "PAY-2025-00001",
          "amount": "500.000",
          "payment_method": "bank_transfer",
          "transaction_reference": "TRANS-123456",
          "payment_date": "2025-01-16"
        }
      ]
    },
    "remaining_amount": "690.000"
  }
}
```

## GET /vendor/invoices/credit-stats

Get vendor's credit statistics and overdue/upcoming invoices.

**Auth**: Required (Vendor)

**Response**:
```json
{
  "status": "success",
  "data": {
    "credit": {
      "credit_limit": "10000.000",
      "credit_used": "3500.000",
      "available_credit": "6500.000",
      "credit_utilization": 35.00,
      "is_on_hold": false,
      "hold_reason": null,
      "payment_terms": "net_30"
    },
    "overdue_invoices": [...],
    "upcoming_invoices": [...]
  }
}
```

## GET /vendor/invoices/overdue

Get all overdue invoices.

**Auth**: Required (Vendor)

## GET /vendor/invoices/upcoming

Get upcoming invoices (due within specified days).

**Auth**: Required (Vendor)

**Query Parameters**:
- `days`: Number of days ahead (default: 7)

## GET /vendor/invoices/payment-history

Get vendor's complete payment history.

**Auth**: Required (Vendor)

**Query Parameters**:
- `start_date`: Filter from date
- `end_date`: Filter to date

---

# 📋 RFQ System (Request for Quotation)

## POST /vendor/rfqs

Create a new RFQ.

**Auth**: Required (Vendor)

**Request**:
```json
{
  "title": "Bulk Order Request - 1000 Units",
  "description": "Need special pricing for large order",
  "target_budget": 50000.000,
  "required_delivery_date": "2025-03-15",
  "priority": "high",
  "expires_in_days": 30,
  "items": [
    {
      "product_id": 10,
      "sku": "PROD-001",
      "name": "Product Name",
      "description": "Special requirements",
      "quantity": 1000,
      "unit": "pcs",
      "specifications": {
        "color": "blue",
        "size": "large"
      }
    }
  ]
}
```

**Validation**:
- `title`: required, string, max:255
- `description`: optional, string
- `target_budget`: optional, numeric, min:0
- `required_delivery_date`: optional, date, after:today
- `priority`: required, in:[low,medium,high,urgent]
- `expires_in_days`: optional, integer, min:1, max:90
- `items`: required, array, min:1
- `items.*.product_id`: optional, exists:products
- `items.*.name`: required, string
- `items.*.quantity`: required, integer, min:1

**Response**:
```json
{
  "status": "success",
  "message": "RFQ created successfully",
  "data": {
    "id": 1,
    "rfq_number": "RFQ-2025-00001",
    "vendor_id": 5,
    "title": "Bulk Order Request - 1000 Units",
    "description": "Need special pricing for large order",
    "target_budget": "50000.000",
    "required_delivery_date": "2025-03-15",
    "status": "draft",
    "priority": "high",
    "expires_at": "2025-02-15T12:00:00.000000Z",
    "items": [...]
  }
}
```

## GET /vendor/rfqs

Get list of vendor's RFQs.

**Auth**: Required (Vendor)

**Query Parameters**:
- `status`: Filter by status
- `active_only`: Boolean, only active RFQs
- `per_page`: Items per page

## GET /vendor/rfqs/{rfq}

Get specific RFQ details.

**Auth**: Required (Vendor)

**Response**:
```json
{
  "status": "success",
  "data": {
    "rfq": {
      "id": 1,
      "rfq_number": "RFQ-2025-00001",
      "title": "Bulk Order Request",
      "status": "quoted",
      "items": [...],
      "quotes": [
        {
          "id": 1,
          "quote_number": "QUO-2025-00001",
          "subtotal": "45000.000",
          "tax": "8550.000",
          "total": "53550.000",
          "payment_terms": "net_30",
          "delivery_days": 20,
          "status": "sent",
          "valid_until": "2025-02-15T00:00:00.000000Z"
        }
      ],
      "negotiations": [...]
    },
    "unread_count": 2
  }
}
```

## PUT /vendor/rfqs/{rfq}

Update RFQ (only draft status).

**Auth**: Required (Vendor)

## POST /vendor/rfqs/{rfq}/submit

Submit RFQ for review.

**Auth**: Required (Vendor)

**Response**:
```json
{
  "status": "success",
  "message": "RFQ submitted successfully",
  "data": {
    "id": 1,
    "status": "submitted",
    "submitted_at": "2025-01-16T10:30:00.000000Z"
  }
}
```

## POST /vendor/rfqs/{rfq}/quotes/{quote}/accept

Accept a quote.

**Auth**: Required (Vendor)

**Response**:
```json
{
  "status": "success",
  "message": "Quote accepted successfully",
  "data": {...}
}
```

## POST /vendor/rfqs/{rfq}/quotes/{quote}/reject

Reject a quote.

**Auth**: Required (Vendor)

**Request**:
```json
{
  "reason": "Price is higher than budget"
}
```

## POST /vendor/rfqs/{rfq}/negotiations

Add negotiation message or counter-offer.

**Auth**: Required (Vendor)

**Request**:
```json
{
  "message": "Can you provide a better price for this quantity?",
  "is_counter_offer": true,
  "proposed_price": 48000.000,
  "proposed_terms": "net_30",
  "proposed_delivery_days": 25
}
```

**Response**:
```json
{
  "status": "success",
  "message": "Message added successfully",
  "data": {
    "id": 1,
    "rfq_id": 1,
    "user_id": 5,
    "message": "Can you provide a better price?",
    "is_counter_offer": true,
    "proposed_price": "48000.000",
    "created_at": "2025-01-16T10:30:00.000000Z"
  }
}
```

## GET /vendor/rfqs/{rfq}/negotiations

Get all negotiations for an RFQ.

**Auth**: Required (Vendor)

## POST /vendor/rfqs/{rfq}/convert-to-order

Convert accepted RFQ to order.

**Auth**: Required (Vendor)

**Response**:
```json
{
  "status": "success",
  "message": "RFQ converted to order successfully",
  "data": {
    "order": {
      "id": 150,
      "order_number": "ORD-2025-00150",
      "total": "53550.000",
      "items": [...]
    },
    "rfq": {
      "id": 1,
      "status": "converted"
    }
  }
}
```

## POST /vendor/rfqs/{rfq}/cancel

Cancel RFQ.

**Auth**: Required (Vendor)

**Request**:
```json
{
  "reason": "Requirements changed"
}
```

---

# 🔧 Admin RFQ Management

## GET /admin/rfqs

Get all RFQs (admin view).

**Auth**: Required (Admin)

**Query Parameters**:
- `status`: Filter by status
- `priority`: Filter by priority
- `pending_review`: Boolean, only pending RFQs
- `active_only`: Boolean, only active RFQs

## GET /admin/rfqs/stats

Get RFQ statistics.

**Auth**: Required (Admin)

**Response**:
```json
{
  "status": "success",
  "data": {
    "total": 45,
    "pending_review": 8,
    "quoted": 12,
    "negotiating": 5,
    "accepted": 15,
    "active": 25,
    "high_priority": 3
  }
}
```

## POST /admin/rfqs/{rfq}/quote

Create quote for RFQ.

**Auth**: Required (Admin)

**Request**:
```json
{
  "items": [
    {
      "id": 1,
      "unit_price": 45.000,
      "notes": "Bulk discount applied"
    }
  ],
  "payment_terms": "net_30",
  "delivery_days": 20,
  "valid_for_days": 30,
  "terms_and_conditions": "Standard terms apply",
  "notes": "Best price for this quantity"
}
```

**Response**:
```json
{
  "status": "success",
  "message": "Quote created successfully",
  "data": {
    "id": 1,
    "quote_number": "QUO-2025-00001",
    "rfq_id": 1,
    "subtotal": "45000.000",
    "tax": "8550.000",
    "total": "53550.000",
    "status": "draft"
  }
}
```

## POST /admin/rfqs/quotes/{quote}/send

Send quote to vendor.

**Auth**: Required (Admin)

**Response**:
```json
{
  "status": "success",
  "message": "Quote sent to vendor successfully",
  "data": {...}
}
```

## POST /admin/rfqs/{rfq}/negotiations

Add admin message to negotiation.

**Auth**: Required (Admin)

## GET /admin/rfqs/{rfq}/negotiations

Get negotiations for RFQ.

**Auth**: Required (Admin)

---

# 👥 Multi-Account Management

## POST /vendor/account

Create a new account user (sub-account).

**Auth**: Required (Vendor)

**Request**:
```json
{
  "name": "John Doe",
  "email": "john@company.com",
  "phone": "+21612345678",
  "position": "Purchasing Manager",
  "department": "Procurement",
  "permission_level": "manage",
  "budget": {
    "budget_period": "monthly",
    "budget_limit": 5000.000,
    "alert_threshold": 80.00
  }
}
```

**Validation**:
- `name`: required, string, max:255
- `email`: required, email, unique
- `permission_level`: required, in:[full,manage,view]
- `budget.budget_period`: required_with:budget, in:[daily,weekly,monthly,yearly,unlimited]
- `budget.budget_limit`: required_with:budget, numeric, min:0

**Response**:
```json
{
  "status": "success",
  "message": "Account user created successfully",
  "data": {
    "id": 1,
    "vendor_id": 5,
    "user_id": 150,
    "name": "John Doe",
    "email": "john@company.com",
    "position": "Purchasing Manager",
    "status": "inactive",
    "is_primary": false,
    "permissions": [...],
    "budget": {
      "budget_period": "monthly",
      "budget_limit": "5000.000",
      "budget_used": "0.000"
    }
  }
}
```

## GET /vendor/account

Get all account users for vendor.

**Auth**: Required (Vendor)

**Query Parameters**:
- `status`: Filter by status (active, inactive, suspended)

**Response**:
```json
{
  "status": "success",
  "data": {
    "account_users": [
      {
        "id": 1,
        "name": "John Doe",
        "email": "john@company.com",
        "position": "Purchasing Manager",
        "status": "active",
        "is_primary": false,
        "permissions": [...],
        "budget": {...}
      }
    ],
    "stats": {
      "total": 5,
      "active": 3,
      "inactive": 1,
      "suspended": 1,
      "with_budget": 3
    }
  }
}
```

## GET /vendor/account/{accountUser}

Get specific account user details.

**Auth**: Required (Vendor)

**Response**:
```json
{
  "status": "success",
  "data": {
    "account_user": {...},
    "budget_status": {
      "budget_limit": "5000.000",
      "budget_used": "1250.000",
      "budget_remaining": "3750.000",
      "usage_percentage": 25.00,
      "is_over_budget": false,
      "period_type": "monthly",
      "period_start": "2025-01-01",
      "period_end": "2025-01-31"
    }
  }
}
```

## PUT /vendor/account/{accountUser}

Update account user details.

**Auth**: Required (Vendor)

**Request**:
```json
{
  "name": "John Doe",
  "phone": "+21612345678",
  "position": "Senior Purchasing Manager",
  "department": "Procurement"
}
```

## POST /vendor/account/{accountUser}/activate

Activate account user.

**Auth**: Required (Vendor)

## POST /vendor/account/{accountUser}/suspend

Suspend account user.

**Auth**: Required (Vendor)

**Request**:
```json
{
  "reason": "Policy violation"
}
```

## DELETE /vendor/account/{accountUser}

Delete account user (cannot delete primary).

**Auth**: Required (Vendor)

## POST /vendor/account/{accountUser}/permissions

Update permissions for account user.

**Auth**: Required (Vendor)

**Request**:
```json
{
  "permission_type": "orders",
  "can_view": true,
  "can_create": true,
  "can_edit": true,
  "can_delete": false,
  "can_approve": false
}
```

**Permission Types**: orders, products, invoices, rfqs, analytics, account_management, chat

## POST /vendor/account/{accountUser}/budget

Update budget for account user.

**Auth**: Required (Vendor)

**Request**:
```json
{
  "budget_period": "monthly",
  "budget_limit": 10000.000,
  "alert_threshold": 85.00
}
```

---

# 📊 Analytics Dashboard

## GET /vendor/analytics/dashboard

Get comprehensive analytics dashboard.

**Auth**: Required (Vendor)

**Query Parameters**:
- `period`: daily, weekly, monthly (default), yearly
- `limit`: Number of periods to return (default: 30)

**Response**:
```json
{
  "status": "success",
  "data": {
    "current_period": {
      "date": "2025-01-01",
      "period_type": "monthly",
      "orders": {
        "count": 45,
        "total": "125000.000",
        "average": "2777.778",
        "completed": 42,
        "cancelled": 3,
        "conversion_rate": 93.33
      },
      "products": {
        "viewed": 1250,
        "added_to_cart": 180,
        "unique_ordered": 95
      },
      "rfqs": {
        "submitted": 8,
        "quoted": 7,
        "accepted": 5,
        "conversion_rate": 62.50
      },
      "invoices": {
        "created": 45,
        "paid": 38,
        "total": "125000.000",
        "paid_total": "110000.000",
        "payment_rate": 84.44
      },
      "engagement": {
        "logins": 35,
        "page_views": 890,
        "chat_messages": 120,
        "active_sessions": 28,
        "engagement_score": 78.5
      },
      "performance": {
        "avg_processing_time": 24.5,
        "customer_satisfaction": 4.5
      }
    },
    "previous_period": {...},
    "trends": {
      "orders_count": {
        "value": 45,
        "change": 12.50,
        "direction": "up"
      },
      "orders_total": {
        "value": "125000.000",
        "change": 8.70,
        "direction": "up"
      }
    },
    "historical": [...],
    "summary": {
      "total_orders": 450,
      "total_revenue": "1250000.000",
      "total_rfqs": 95,
      "total_invoices": 450,
      "avg_order_value": "2777.778",
      "avg_engagement": 75.5
    }
  }
}
```

## GET /vendor/analytics/events

Get event statistics.

**Auth**: Required (Vendor)

**Query Parameters**:
- `period`: day, week (default), month, year

**Response**:
```json
{
  "status": "success",
  "data": {
    "total_events": 2450,
    "by_type": {
      "page_view": 890,
      "product_view": 1250,
      "order_created": 45,
      "login": 35
    },
    "by_category": {
      "products": 1430,
      "orders": 185,
      "navigation": 835
    },
    "total_value": "125000.000",
    "unique_sessions": 28
  }
}
```

## GET /vendor/analytics/top-products

Get top performing products.

**Auth**: Required (Vendor)

**Query Parameters**:
- `limit`: Number of products (default: 10)

**Response**:
```json
{
  "status": "success",
  "data": [
    {
      "product_id": 10,
      "views": 145,
      "unique_sessions": 28
    },
    {
      "product_id": 25,
      "views": 132,
      "unique_sessions": 24
    }
  ]
}
```

## POST /vendor/analytics/track

Track custom event.

**Auth**: Required (Vendor)

**Request**:
```json
{
  "event_type": "custom_action",
  "event_category": "products",
  "event_action": "share",
  "event_label": "Product #10",
  "event_data": {
    "product_id": 10,
    "share_method": "email"
  },
  "event_value": 100.000
}
```

**Response**:
```json
{
  "status": "success",
  "message": "Event tracked",
  "data": {
    "id": 1,
    "event_type": "custom_action",
    "event_time": "2025-01-16T10:30:00.000000Z"
  }
}
```

---

## 🔒 Rate Limiting

L'API est limitée à:
- **60 requêtes par minute** pour les utilisateurs authentifiés
- **10 requêtes par minute** pour les requêtes non authentifiées

Headers de réponse:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 58
X-RateLimit-Reset: 1642334400
```

## 🌍 Localization

Toutes les requêtes peuvent spécifier la langue via le header:

```http
Accept-Language: fr
Accept-Language: ar
```

Les champs multilingues (name, description) seront retournés selon la langue demandée.

## 📝 Postman Collection

Importez la collection Postman pour tester l'API:

```bash
POSTMAN_COLLECTION.json
```

Variables d'environnement:
- `base_url`: http://localhost:8000/api
- `access_token`: (auto-rempli après login)

---

**Documentation générée le 16/01/2025**
