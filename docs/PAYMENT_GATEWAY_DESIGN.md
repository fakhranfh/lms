> **REMOVED:** The payment gateway subsystem described in this document has been fully removed from the codebase (services, models, repositories, admin panel, and database schema). This document is kept for historical reference only.

# Payment Gateway Integration Design (v2.0)

**Architecture:** Flexible, Plugin-Based, Database-Driven  
**Date:** 2026-07-14  
**Version:** 2.0 (Updated: Support Unlimited Payment Gateways)

---

## 🎯 Overview

**Unlimited Payment Gateway Support with Secure Credential Management**

- Support **any payment gateway** (unlimited, not hardcoded to 3)
- Credentials stored securely in database (encrypted with AES-256)
- Each school configures own gateway preferences
- Plugin-based architecture for extensibility
- No code changes needed to add new gateways
- Sandbox/production mode per gateway

**Default Pre-Configured Gateways:**
- **Midtrans** - Indonesia (paling populer)
- **Xendit** - Indonesia payment aggregator
- (Easily add more: Stripe, Doku, PayPal, Razorpay, 2Checkout, Square, etc.)

---

## 🏗️ Architecture Design

### Layer 1: Credential Management (Secure Storage)

```
PaymentGatewayCredential (DB Model)
├─ school_id
├─ gateway_type (midtrans, stripe, doku, etc)
├─ key (server_key, client_key, api_key, etc)
├─ value (encrypted with AES-256)
├─ is_sensitive (boolean - for UI display)
└─ environment (sandbox/production)

CredentialEncryption Service
├─ encrypt(data) → encrypted data
├─ decrypt(encryptedData) → plaintext
└─ (Uses Laravel's Crypt facade)
```

### Layer 2: Gateway Registry (Dynamic Loading)

```
PaymentGatewayRegistry
├─ Load all available gateway types from DB
├─ Load school's configured gateways
├─ Get credentials for specific gateway
└─ Manage gateway lifecycle (add/remove/update)

SchoolPaymentGateway (DB Model)
├─ school_id
├─ gateway_type_id
├─ is_enabled (boolean)
├─ is_sandbox_mode (boolean)
├─ webhook_secret
└─ last_webhook_received_at
```

### Layer 3: Factory Pattern (Dynamic Instantiation)

```
PaymentGatewayFactory
├─ make(gatewayType, credentials) → PaymentGateway instance
├─ makeForSchool(schoolId, gatewayType) → loads from DB
└─ getAvailableGatewaysForSchool(schoolId) → list

PaymentGatewayInterface
├─ createInvoice(subscription) → invoice
├─ handleWebhook(payload) → void
├─ getPaymentStatus(transactionId) → status
├─ refund(transactionId, amount?) → bool
├─ getPaymentMethods() → array
└─ verifyWebhookSignature(payload, signature) → bool
```

### Layer 4: Gateway Implementations

```
Abstract BasePaymentGateway implements PaymentGatewayInterface
├─ Protected properties (credentials, baseUrl, etc)
├─ Abstract methods (gateway-specific)
└─ Shared utilities

Default Implementations:
├─ MidtransGateway
└─ XenditGateway

Optional Implementations (add as needed):
├─ [StripeGateway]
├─ [DokuGateway]
├─ [PayPalGateway]
├─ [RazorpayGateway]
└─ [Any new gateway...]
```

### Layer 5: Business Logic Service

```
SubscriptionPaymentService
├─ createSubscriptionPayment(subscription, gatewayType)
├─ upgradeSubscription(subscription, newTier)
├─ refundSubscription(subscription, amount?)
└─ getSubscriptionPaymentStatus(subscription)

PaymentWebhookController (removed — the school-payment checkout/webhook flow was decommissioned)
├─ handle(request) → Dynamic routing
├─ Signature verification (per-gateway)
├─ Webhook processing
└─ Subscription status update
```

---

## 📊 Database Schema

### 1. Payment Gateway Types (Seeded - Configuration)

```sql
CREATE TABLE payment_gateway_types (
    id UUID PRIMARY KEY,
    name VARCHAR(50) UNIQUE,              -- midtrans, stripe, doku, paypal, razorpay
    label VARCHAR(100),                   -- Display name
    description TEXT,
    website_url VARCHAR(255),
    documentation_url VARCHAR(255),
    payment_methods JSON,                 -- ['credit_card', 'bank_transfer', ...]
    supported_currencies JSON,            -- ['idr', 'usd', 'eur', ...]
    requires_webhook_secret BOOLEAN,
    webhook_url_format VARCHAR(255),      -- "/webhooks/payment/{gateway}"
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Example seeds:
-- ('midtrans', 'Midtrans', 'Indonesia payment gateway', ...)
-- ('stripe', 'Stripe', 'International payment processor', ...)
-- ('doku', 'Doku', 'Indonesia payment aggregator', ...)
```

### 2. School Payment Gateways (Per-School Configuration)

```sql
CREATE TABLE school_payment_gateways (
    id UUID PRIMARY KEY,
    school_id UUID NOT NULL,
    payment_gateway_type_id UUID NOT NULL,
    is_enabled BOOLEAN DEFAULT true,
    is_sandbox_mode BOOLEAN DEFAULT true,  -- sandbox or production
    webhook_secret VARCHAR(255),           -- Generated for webhook verification
    webhook_url VARCHAR(255),              -- Full webhook URL
    last_webhook_received_at TIMESTAMP NULL,
    last_tested_at TIMESTAMP NULL,
    test_status VARCHAR(50),               -- success, failed, pending
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (school_id) REFERENCES schools(id) CASCADE,
    FOREIGN KEY (payment_gateway_type_id) REFERENCES payment_gateway_types(id) CASCADE,
    UNIQUE KEY unique_school_gateway (school_id, payment_gateway_type_id)
);
```

### 3. Payment Gateway Credentials (Encrypted)

```sql
CREATE TABLE payment_gateway_credentials (
    id UUID PRIMARY KEY,
    school_payment_gateway_id UUID NOT NULL,
    credential_key VARCHAR(100),           -- server_key, client_key, api_key, api_secret
    credential_value LONGTEXT,             -- Encrypted with AES-256 (Laravel Crypt)
    is_sensitive BOOLEAN DEFAULT true,     -- Hide in UI if true
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (school_payment_gateway_id) REFERENCES school_payment_gateways(id) CASCADE,
    UNIQUE KEY unique_gateway_credential (school_payment_gateway_id, credential_key)
);
```

### 4. Payment Transactions

```sql
CREATE TABLE payment_transactions (
    id UUID PRIMARY KEY,
    school_id UUID NOT NULL,
    subscription_id UUID NOT NULL,
    school_payment_gateway_id UUID NOT NULL,
    gateway_type VARCHAR(50),                  -- Cache
    external_transaction_id VARCHAR(255),      -- Gateway's transaction ID
    external_customer_id VARCHAR(255) NULL,
    amount DECIMAL(12, 2),
    currency VARCHAR(3),
    status ENUM('pending', 'success', 'failed', 'refunded'),
    payment_method VARCHAR(50),                -- credit_card, bank_transfer, etc
    metadata JSON,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (school_id) REFERENCES schools(id) CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) CASCADE,
    FOREIGN KEY (school_payment_gateway_id) REFERENCES school_payment_gateways(id) CASCADE
);
```

### 5. Payment Webhooks (Audit Trail)

```sql
CREATE TABLE payment_webhooks (
    id UUID PRIMARY KEY,
    school_payment_gateway_id UUID NOT NULL,
    gateway_type VARCHAR(50),
    event_type VARCHAR(100),                   -- charge.success, payment.settled, etc
    external_event_id VARCHAR(255),
    payload LONGTEXT,                          -- Encrypted if sensitive
    signature VARCHAR(500),
    is_verified BOOLEAN DEFAULT false,
    is_processed BOOLEAN DEFAULT false,
    processed_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (school_payment_gateway_id) REFERENCES school_payment_gateways(id) CASCADE
);
```

### 6. Update Subscriptions Table

```sql
ALTER TABLE subscriptions ADD (
    payment_gateway_type VARCHAR(50) NULL,
    school_payment_gateway_id UUID NULL,
    gateway_transaction_id VARCHAR(255) NULL,
    gateway_customer_id VARCHAR(255) NULL
);
```

---

## 🔐 Credential Encryption Strategy

### Encryption at Rest

```
User enters credential (plaintext)
         ↓
CredentialEncryption::encrypt(value)
         ↓
Crypt::encryptString(value)  // Uses APP_KEY (AES-256-CBC)
         ↓
Store encrypted value in database
```

### Decryption in Memory

```
Load encrypted value from database
         ↓
CredentialEncryption::decrypt(encryptedValue)
         ↓
Crypt::decryptString(encryptedValue)
         ↓
Use plaintext ONLY in memory
         ↓
Never log, never display, never cache to disk
```

### Security Checklist

- ✅ Credentials encrypted at rest (AES-256)
- ✅ Only decrypt when actually needed
- ✅ Keep decrypted value in-memory only
- ✅ Never log full credentials (mask sensitive values)
- ✅ No credentials in error messages
- ✅ Access control: only school admin can manage
- ✅ Audit log for credential changes
- ✅ Webhook payload can be encrypted if sensitive
- ✅ Regular key rotation capability

---

## 💻 Implementation Examples

### 1. Add a New Payment Gateway (NO CODE CHANGES!)

**Admin Panel:**
1. Go to Settings → Payment Gateways
2. Click "Add Gateway"
3. Select gateway type (Stripe, Midtrans, Doku, etc.)
4. Enter credentials:
   - Secret Key: sk_test_...
   - Public Key: pk_test_...
   - Webhook Secret: whsec_...
5. Toggle: Sandbox/Production
6. Save (auto-encrypted)
7. Enable/Disable toggle

**Result:** Gateway immediately available for subscriptions

**No PHP code changes needed!**

### 2. Create Payment with Dynamic Gateway

```php
// Factory loads credentials from DB automatically
$gateway = PaymentGatewayFactory::makeForSchool($schoolId, 'stripe');

$paymentService = new SubscriptionPaymentService($gateway);
$invoice = $paymentService->createSubscriptionPayment($subscription);

// Returns: ['transaction_id' => '...', 'payment_url' => 'https://...']
```

### 3. Handle Webhook Dynamically

```php
// Route: POST /webhooks/payment/{gateway_type}
// Automatically routes to correct school/gateway

public function handle($gatewayType, Request $request) {
    // Find school configured with this gateway
    $schoolGateway = SchoolPaymentGateway::whereHas(
        'gatewayType', 
        fn($q) => $q->where('name', $gatewayType)
    )->first();
    
    // Load gateway with decrypted credentials
    $gateway = PaymentGatewayFactory::makeForSchool(
        $schoolGateway->school_id, 
        $gatewayType
    );
    
    // Process webhook (gateway-specific logic)
    $gateway->handleWebhook($request->all());
}
```

---

## 🚀 Adding a New Payment Gateway

### Step 1: Create in Database (Admin Panel or Seeder)

**Option A: Via Admin Panel**
1. Settings → Payment Gateways → Add Gateway Type
2. Fill: name, label, description, documentation URL, etc.
3. Specify required credentials
4. Save

**Option B: Via Seeder**
```php
// Default gateways seeded in PaymentGatewayTypeSeeder:
PaymentGatewayType::create([
    'name' => 'midtrans',
    'label' => 'Midtrans',
    'description' => 'Indonesia payment gateway',
    'payment_methods' => ['credit_card', 'bank_transfer', 'gopay', 'ovo'],
    'supported_currencies' => ['idr'],
    'requires_webhook_secret' => true
]);

PaymentGatewayType::create([
    'name' => 'xendit',
    'label' => 'Xendit',
    'description' => 'Indonesia payment aggregator',
    'payment_methods' => ['credit_card', 'bank_transfer', 'ewallet', 'qr_code'],
    'supported_currencies' => ['idr'],
    'requires_webhook_secret' => true
]);

// Example: Add additional gateway later
PaymentGatewayType::create([
    'name' => 'stripe',
    'label' => 'Stripe',
    'description' => 'International payment processor',
    'payment_methods' => ['credit_card', 'ach', 'ideal', 'bancontact'],
    'supported_currencies' => ['usd', 'eur'],
    'requires_webhook_secret' => true
]);
```

### Step 2: Implement Gateway Class

```php
// app/Services/PaymentGateways/PayPalGateway.php
class PayPalGateway extends BasePaymentGateway implements PaymentGateway {
    public function createInvoice(Subscription $subscription): array {
        // PayPal-specific implementation
    }
    
    public function handleWebhook(array $payload): void {
        // PayPal webhook handling
    }
    
    // Implement other interface methods...
}
```

### Step 3: Register in Factory

```php
// app/Services/PaymentGatewayFactory.php
public static function makeForSchool($schoolId, $gatewayType) {
    $schoolGateway = SchoolPaymentGateway::with('credentials')
        ->whereHas('gatewayType', fn($q) => $q->where('name', $gatewayType))
        ->where('school_id', $schoolId)
        ->first();
    
    if (!$schoolGateway) {
        throw new GatewayNotConfiguredException("Gateway not configured");
    }
    
    $credentials = $schoolGateway->getCredentialsArray();
    
    return match($schoolGateway->gatewayType->name) {
        'midtrans' => new MidtransGateway($credentials),
        'xendit' => new XenditGateway($credentials),
        'stripe' => new StripeGateway($credentials),       // Optional
        'doku' => new DokuGateway($credentials),           // Optional
        'paypal' => new PayPalGateway($credentials),       // Optional
        default => throw new UnknownGatewayException("Gateway not supported: {$gatewayType}")
    };
}
```

**Done! PayPal now works system-wide.**

---

## 🎯 Payment Flow (Complete)

```
User Registration
       ↓
Select Subscription Tier (e.g., Professional)
       ↓
Show Available Gateways (loaded from school's config)
       ↓
User selects gateway (e.g., "Pay with Stripe")
       ↓
PaymentGatewayFactory::makeForSchool()
├─ Load SchoolPaymentGateway record
├─ Decrypt credentials
├─ Create StripeGateway instance
└─ Return ready-to-use gateway
       ↓
SubscriptionPaymentService::createSubscriptionPayment()
├─ $gateway->createInvoice($subscription)
├─ Store in payment_transactions table
└─ Return payment URL
       ↓
Redirect to Stripe checkout
       ↓
User completes payment on Stripe
       ↓
Stripe sends webhook to /webhooks/payment/stripe
       ↓
PaymentWebhookController (removed — the school-payment checkout/webhook flow was decommissioned)
├─ Verify signature
├─ Load credentials from DB
├─ Process webhook
├─ Update subscription status → 'active'
└─ Send confirmation email
       ↓
Success! Subscription active
```

---

## 📋 Models Structure

```php
class PaymentGatewayType extends Model {
    public function schoolGateways() {
        return $this->hasMany(SchoolPaymentGateway::class);
    }
}

class SchoolPaymentGateway extends Model {
    public function school() {
        return $this->belongsTo(School::class);
    }
    
    public function gatewayType() {
        return $this->belongsTo(PaymentGatewayType::class);
    }
    
    public function credentials() {
        return $this->hasMany(PaymentGatewayCredential::class);
    }
    
    // Returns decrypted credentials array
    public function getCredentialsArray(): array {
        return $this->credentials
            ->pluck('credential_value', 'credential_key')
            ->mapWithKeys(fn($value, $key) => [
                $key => Crypt::decryptString($value)
            ])->toArray();
    }
}

class PaymentGatewayCredential extends Model {
    // Credentials stored encrypted in DB
    
    public function getDecryptedValueAttribute() {
        return Crypt::decryptString($this->credential_value);
    }
}

class PaymentTransaction extends Model {
    public function schoolGateway() {
        return $this->belongsTo(SchoolPaymentGateway::class);
    }
}

class PaymentWebhook extends Model {
    public function schoolGateway() {
        return $this->belongsTo(SchoolPaymentGateway::class);
    }
}
```

---

## 🔒 Comparison: Before vs After

| Aspect | v1.0 (Hardcoded) | v2.0 (Flexible) |
|--------|-----------------|-----------------|
| **# Gateways** | 3 (Midtrans, Stripe, Doku) | Unlimited |
| **Add Gateway** | Code + Deploy | Admin UI + Instant |
| **Credentials** | Config files | Encrypted DB |
| **Update Creds** | Code + Deploy | Admin UI + Instant |
| **Security** | Lower | Higher (AES-256) |
| **Multi-School** | No | Yes (per-school) |
| **Sandbox/Prod** | Global | Per-gateway |
| **Flexibility** | Rigid | Highly Flexible |

---

## 🛠️ Implementation Phases

**Phase 2.0A:** Database tables & models (6 migrations)
**Phase 2.0B:** Factory & registry (3 classes)
**Phase 2.0C:** Gateway implementations (3-5 classes)
**Phase 2.0D:** Admin panel for credential management (UI forms)
**Phase 2.0E:** Webhook handling & testing

---

## ✨ Key Benefits

- ✅ **No Code Changes** to add new payment gateways
- ✅ **Secure Encryption** (AES-256) for all credentials
- ✅ **Per-School Configuration** (multi-school)
- ✅ **Sandbox & Production** modes
- ✅ **Webhook Management** with audit trail
- ✅ **Easy Credential Rotation**
- ✅ **Extensible** - Add PayPal, Razorpay, etc. easily
- ✅ **Admin Panel** for self-service configuration
