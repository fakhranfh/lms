# Phase 2: Subscription Schema & Pricing + Payment Gateway

**Status:** ⏳ In Progress  
**Date Started:** 2026-07-14  
**Prerequisites:** Phase 1 ✅ Complete

---

## 📋 Phase 2.0A: Database & Models

### Payment Gateway Types Table
- [ ] Create migration: `CreatePaymentGatewayTypesTable`
  - `id` (BIGINT, PK)
  - `name` (VARCHAR, unique) - e.g., `midtrans`, `xendit`, `stripe`
  - `label` (VARCHAR) - Display name
  - `description` (TEXT, nullable)
  - `is_active` (BOOLEAN, default true)
  - `created_at`, `updated_at`
- [ ] Create model: `app/Models/PaymentGatewayType.php`
- [ ] Create seeder: `database/seeders/PaymentGatewayTypeSeeder.php` (Midtrans, Xendit)

### School Payment Gateways Table
- [ ] Create migration: `CreateSchoolPaymentGatewaysTable`
  - `id` (UUID, PK)
  - `school_id` (UUID, FK→schools.id, CASCADE)
  - `gateway_type_id` (BIGINT, FK→payment_gateway_types.id)
  - `is_enabled` (BOOLEAN, default false)
  - `is_sandbox_mode` (BOOLEAN, default true)
  - `webhook_secret` (VARCHAR, nullable)
  - `created_at`, `updated_at`
- [ ] Create model: `app/Models/SchoolPaymentGateway.php`
- [ ] Add relationship to `School` model

### Payment Gateway Credentials Table
- [ ] Create migration: `CreatePaymentGatewayCredentialsTable`
  - `id` (UUID, PK)
  - `school_payment_gateway_id` (UUID, FK, CASCADE)
  - `credential_key` (VARCHAR) - e.g., `server_key`, `api_key`, `client_id`
  - `credential_value` (TEXT, encrypted)
  - `is_sensitive` (BOOLEAN, default true)
  - `created_at`, `updated_at`
- [ ] Create model: `app/Models/PaymentGatewayCredential.php`
- [ ] Add relationship to `SchoolPaymentGateway`

### Payment Transactions Table
- [ ] Create migration: `CreatePaymentTransactionsTable`
  - `id` (UUID, PK)
  - `school_id` (UUID, FK, CASCADE)
  - `subscription_id` (UUID, FK→subscriptions.id, nullable)
  - `school_payment_gateway_id` (UUID, FK)
  - `transaction_id` (VARCHAR, unique) - External gateway transaction ID
  - `amount` (DECIMAL 15,2)
  - `currency` (VARCHAR, default `IDR`)
  - `status` (VARCHAR) - pending, completed, failed, refunded
  - `metadata` (JSON, nullable)
  - `created_at`, `updated_at`
- [ ] Create model: `app/Models/PaymentTransaction.php`
- [ ] Add relationships to `School`, `Subscription`, `SchoolPaymentGateway`

### Payment Webhooks Table
- [ ] Create migration: `CreatePaymentWebhooksTable`
  - `id` (UUID, PK)
  - `school_payment_gateway_id` (UUID, FK)
  - `event_type` (VARCHAR) - e.g., `transaction.success`, `transaction.failed`
  - `payload` (TEXT, encrypted)
  - `processed` (BOOLEAN, default false)
  - `processed_at` (TIMESTAMP, nullable)
  - `created_at`
- [ ] Create model: `app/Models/PaymentWebhook.php`

### Subscription Tiers Table
- [ ] Create migration: `CreateSubscriptionTiersTable`
  - `id` (BIGINT, PK)
  - `name` (VARCHAR) - Starter, Professional, Enterprise
  - `slug` (VARCHAR, unique) - starter, professional, enterprise
  - `description` (TEXT, nullable)
  - `price` (DECIMAL 15,2)
  - `currency` (VARCHAR, default `IDR`)
  - `billing_period` (VARCHAR) - monthly, annual
  - `features` (JSON) - Array of feature keys
  - `max_users` (INT, nullable) - NULL = unlimited
  - `storage_gb` (INT, nullable) - NULL = unlimited
  - `is_active` (BOOLEAN, default true)
  - `created_at`, `updated_at`
- [ ] Create model: `app/Models/SubscriptionTier.php`
- [ ] Create seeder: `database/seeders/SubscriptionTierSeeder.php`
  - Starter: IDR 199,000/month, max 100 users, 5GB
  - Professional: IDR 499,000/month, max 500 users, 50GB
  - Enterprise: Custom pricing, unlimited

### Subscriptions Table
- [ ] Create migration: `CreateSubscriptionsTable`
  - `id` (UUID, PK)
  - `school_id` (UUID, FK→schools.id, CASCADE)
  - `tier_id` (BIGINT, FK→subscription_tiers.id)
  - `status` (VARCHAR) - trial, active, expired, cancelled, suspended
  - `started_at` (TIMESTAMP)
  - `expires_at` (TIMESTAMP)
  - `renewal_date` (TIMESTAMP, nullable)
  - `auto_renew` (BOOLEAN, default true)
  - `payment_method` (VARCHAR, nullable) - gateway name used
  - `created_at`, `updated_at`
- [ ] Create model: `app/Models/Subscription.php`
- [ ] Add relationships to `School`, `SubscriptionTier`, `PaymentTransaction`

### Demo LMS Access Table
- [ ] Create migration: `CreateDemoLmsAccessTable`
  - `id` (UUID, PK)
  - `school_id` (UUID, FK→schools.id, CASCADE)
  - `user_id` (UUID, FK→users.id, CASCADE)
  - `access_token` (VARCHAR, unique)
  - `expires_at` (TIMESTAMP)
  - `accessed_at` (TIMESTAMP, nullable)
  - `created_at`
- [ ] Create model: `app/Models/DemoLmsAccess.php`
- [ ] Add relationships to `School`, `User`

---

## 📋 Phase 2.0B: Core Infrastructure

### Credential Encryption Service
- [ ] Create `app/Services/CredentialEncryption.php`
  - `encrypt(string $value): string`
  - `decrypt(string $encrypted): string`
  - Uses Laravel's `Crypt` facade (AES-256-CBC)
- [ ] Add tests for encryption/decryption

### Payment Gateway Contract
- [ ] Create `app/Contracts/PaymentGateway.php` interface
  - `createInvoice(array $data): array`
  - `handleWebhook(array $payload): bool`
  - `checkTransactionStatus(string $transactionId): array`
  - `refund(string $transactionId, float $amount): bool`

### Payment Gateway Registry
- [ ] Create `app/Services/PaymentGatewayRegistry.php`
  - Load available gateways from `payment_gateway_types` table
  - Cache gateway types for performance
  - Provide method: `getGatewayTypes(): Collection`
  - Provide method: `getSchoolGateways(School $school): Collection`

### Payment Gateway Factory
- [ ] Create `app/Services/PaymentGatewayFactory.php`
  - `make(string $gatewayName, SchoolPaymentGateway $config): PaymentGateway`
  - Dynamic instantiation based on gateway type
  - Load and decrypt credentials from DB
  - Return appropriate gateway implementation

### Subscription Payment Service
- [ ] Create `app/Services/SubscriptionPaymentService.php`
  - `createPaymentInvoice(Subscription $subscription, SchoolPaymentGateway $gateway): array`
  - `processWebhook(PaymentWebhook $webhook): bool`
  - `completeSubscription(PaymentTransaction $transaction): void`
  - `handleFailedPayment(PaymentTransaction $transaction): void`
  - `refundTransaction(PaymentTransaction $transaction, ?float $amount): bool`

### Service Container Bindings
- [ ] Register services in `app/Providers/AppServiceProvider.php`
  - Bind `CredentialEncryption` singleton
  - Bind `PaymentGatewayRegistry` singleton
  - Bind `PaymentGatewayFactory` singleton
  - Bind `SubscriptionPaymentService` singleton

---

## 📋 Phase 2.0C: Gateway Implementations

### Midtrans Gateway Implementation
- [ ] Create `app/Services/PaymentGateways/MidtransGateway.php`
  - Implement `PaymentGateway` contract
  - `createInvoice()` - Call Midtrans API to generate transaction
  - `handleWebhook()` - Process Midtrans notification webhook
  - `checkTransactionStatus()` - Query Midtrans for transaction status
  - `refund()` - Process refund via Midtrans API
  - Configuration: server_key, client_key
- [ ] Add tests for Midtrans gateway

### Xendit Gateway Implementation
- [ ] Create `app/Services/PaymentGateways/XenditGateway.php`
  - Implement `PaymentGateway` contract
  - `createInvoice()` - Call Xendit API to generate invoice
  - `handleWebhook()` - Process Xendit webhook
  - `checkTransactionStatus()` - Query Xendit for invoice status
  - `refund()` - Process refund via Xendit API
  - Configuration: api_key, callback_token
- [ ] Add tests for Xendit gateway

### Gateway Manager
- [ ] Create `app/Services/GatewayManager.php`
  - Centralized gateway loading/instantiation
  - Handle missing/disabled gateways gracefully
  - Provide fallback to default gateway

---

## 📋 Phase 2.0D: Webhooks & Controllers

### Payment Webhook Controller
- [ ] Create `app/Http/Controllers/PaymentWebhookController.php`
  - `handleMidtrans(Request $request): Response` - Route `/webhooks/midtrans`
  - `handleXendit(Request $request): Response` - Route `/webhooks/xendit`
  - Verify webhook signature per gateway
  - Store payload in `payment_webhooks` table (encrypted)
  - Dispatch job to process webhook asynchronously

### Webhook Processing Job
- [ ] Create `app/Jobs/ProcessPaymentWebhook.php`
  - Load webhook from DB
  - Verify signature authenticity
  - Call `SubscriptionPaymentService::processWebhook()`
  - Update transaction & subscription status
  - Mark webhook as processed

### Webhook Routes
- [ ] Add routes in `routes/web.php` or dedicated webhook route file
  - `POST /webhooks/midtrans`
  - `POST /webhooks/xendit`
  - Add rate limiting (burst: 100, throttle: 1000/min)

### Admin Gateway Management Controller
- [ ] Create `app/Http/Controllers/Admin/GatewayConfigController.php`
  - `index()` - List configured gateways for school
  - `create()` - Show form to add new gateway
  - `store()` - Save gateway configuration with encrypted credentials
  - `edit()` - Show form to edit gateway
  - `update()` - Update gateway config
  - `destroy()` - Remove gateway configuration
  - `testConnection()` - Test gateway credentials (POST)

### Admin Gateway Management Views
- [ ] Create `resources/views/admin/gateways/index.blade.php` - List gateways
- [ ] Create `resources/views/admin/gateways/create.blade.php` - Add gateway form
- [ ] Create `resources/views/admin/gateways/edit.blade.php` - Edit gateway form

### Form Requests
- [ ] Create `app/Http/Requests/StoreGatewayConfigRequest.php`
  - Validate gateway_type_id exists
  - Validate credential keys match expected format
  - Validate required credentials for each gateway type
  - Unique constraint: one gateway type per school (initially)

---

## 📋 Phase 2.1: Pricing Tiers Configuration

### Tier Features Setup
- [ ] Define feature flags/keys in config or constant
  ```
  - class_management
  - assignment_submission
  - basic_grading
  - email_notifications
  - basic_reporting
  - advanced_analytics
  - custom_branding
  - api_access
  - lti_integration
  - video_hosting_25hrs
  - unlimited_video
  - custom_integration
  - sso_saml
  - dedicated_server
  - custom_sla
  ```

### Tier Pricing Data
- [ ] Seed subscription_tiers with:
  - **Starter**: IDR 199,000/month, 100 users max, 5GB storage
    - Features: class_management, assignment_submission, basic_grading, email_notifications, basic_reporting
  - **Professional**: IDR 499,000/month, 500 users max, 50GB storage
    - All Starter + advanced_analytics, custom_branding, api_access, lti_integration, video_hosting_25hrs
  - **Enterprise**: Custom pricing, unlimited users, unlimited storage
    - All Professional + unlimited_video, custom_integration, sso_saml, dedicated_server, custom_sla

### Annual Billing Option
- [ ] Add annual pricing (15% discount typically)
  - Starter Annual: IDR 2,028,000/year (vs 2,388,000)
  - Professional Annual: IDR 5,088,000/year (vs 5,988,000)
  - Enterprise: Custom

---

## 📋 Phase 2.2: Demo LMS Setup

### Demo Access Generation
- [ ] Create `app/Services/DemoLmsAccessService.php`
  - `generateAccessToken(School $school): string` - Generate unique 32-char token
  - `createDemoUser(School $school): User` - Create demo admin account
  - `grantDemoAccess(School $school, User $user): DemoLmsAccess` - Grant 14-day access
  - `isDemoAccessValid(DemoLmsAccess $access): bool` - Check if still valid

### Demo LMS Controller
- [ ] Create `app/Http/Controllers/DemoLmsController.php`
  - `show()` - Show demo LMS access info
  - `generate()` - Generate new demo credentials (POST)
  - `login()` - Auto-login with demo token

### Demo LMS Routes
- [ ] Add routes for authenticated schools:
  - `GET  /demo-lms` - Show demo access
  - `POST /demo-lms/generate` - Create demo credentials
  - `GET  /demo-lms/login/:token` - Auto-login

### Demo Data Seeder
- [ ] Create `database/seeders/DemoLmsDataSeeder.php`
  - Sample classes (Math, Science, English)
  - Sample students (10-15 per class)
  - Sample assignments (2-3 per class)
  - Sample grades
  - Apply watermark indicator to all pages

---

## 📋 Testing Requirements

### Unit Tests
- [ ] `Tests/Unit/Services/CredentialEncryptionTest.php`
  - Test encryption/decryption roundtrip
  - Test different value types
  
- [ ] `Tests/Unit/Services/PaymentGatewayRegistryTest.php`
  - Test loading gateways from DB
  - Test caching behavior

- [ ] `Tests/Unit/Services/SubscriptionPaymentServiceTest.php`
  - Test invoice creation
  - Test subscription completion
  - Test failed payment handling

### Feature Tests
- [ ] `Tests/Feature/PaymentGateway/MidtransGatewayTest.php`
  - Test invoice generation (mock API)
  - Test webhook handling
  - Test refund processing

- [ ] `Tests/Feature/PaymentGateway/XenditGatewayTest.php`
  - Test invoice generation (mock API)
  - Test webhook handling
  - Test refund processing

- [ ] `Tests/Feature/PaymentWebhookTest.php`
  - Test webhook route accepts requests
  - Test signature verification
  - Test transaction status update

- [ ] `Tests/Feature/Admin/GatewayConfigTest.php`
  - Test adding gateway configuration
  - Test editing gateway
  - Test removing gateway
  - Test credential encryption

- [ ] `Tests/Feature/DemoLmsAccessTest.php`
  - Test token generation
  - Test expiry validation
  - Test auto-login with token

---

## 📋 Security Checklist

- [ ] Credentials encrypted at rest in DB (AES-256)
- [ ] Credentials only decrypted in-memory when needed
- [ ] Webhook signature verification implemented per gateway
- [ ] Webhook payloads encrypted in storage
- [ ] Access control: only school admin can view/edit their credentials
- [ ] No credentials in logs or error messages
- [ ] Rate limiting on webhook endpoints (burst: 100, throttle: 1000/min)
- [ ] SQL injection protection in all queries
- [ ] CSRF protection on POST routes
- [ ] Demo access tokens are cryptographically secure (32+ chars)
- [ ] Demo access expires after 14 days

---

## 📋 Code Style & Format

- [ ] Run `vendor/bin/pint --dirty --format agent` before committing
- [ ] Ensure all type hints are present
- [ ] Follow existing code conventions

---

## 🎯 Commits & Integration

After completing each sub-phase, commit with:
- `feat(payment): add gateway database schema & models` (after 2.0A)
- `feat(payment): implement gateway registry, factory, and services` (after 2.0B)
- `feat(payment): implement Midtrans and Xendit gateways` (after 2.0C)
- `feat(payment): add webhook handling and admin configuration` (after 2.0D)

---

## 📝 Notes

- All migrations should use `--no-interaction` flag
- Models should use `HasUuid` trait for UUID PKs
- All models scoped by school should use `BelongsToSchool` trait (to create)
- Gateway credentials should never be logged or exposed in stack traces
- Consider eventual need for gateway sandbox/production separation
