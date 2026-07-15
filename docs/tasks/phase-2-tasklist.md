# Phase 2: Subscription Schema & Pricing + Payment Gateway

**Status:** ⏳ In Progress (Phase 2.0A ✅ Complete, Phase 2.0B ✅ Complete, Phase 2.0C ✅ Complete, Phase 2.0D ✅ Complete, Phase 2.1.1 ✅ Complete, Phase 2.1.2 ✅ Complete, Phase 2.1.3 ✅ Complete, Phase 2.1.4 ✅ Complete, Phase 2.1.5 ✅ Complete)
**Date Started:** 2026-07-14  
**Prerequisites:** Phase 1 ✅ Complete
**Phase 2.0A Completed:** 2026-07-14
**Phase 2.0B Completed:** 2026-07-14
**Phase 2.0C Completed:** 2026-07-14
**Phase 2.0D Completed:** 2026-07-14
**Phase 2.1.1 Completed:** 2026-07-14
**Phase 2.1.2 Completed:** 2026-07-15
**Phase 2.1.3 Completed:** 2026-07-15
**Phase 2.1.4 Completed:** 2026-07-15
**Phase 2.1.5 Completed:** 2026-07-15

---

## 📋 Phase 2.0A: Database & Models ✅ COMPLETE

### Payment Gateway Types Table
- [x] Create migration: `CreatePaymentGatewayTypesTable`
  - `id` (BIGINT, PK)
  - `name` (VARCHAR, unique) - e.g., `midtrans`, `xendit`, `stripe`
  - `label` (VARCHAR) - Display name
  - `description` (TEXT, nullable)
  - `is_active` (BOOLEAN, default true)
  - `created_at`, `updated_at`
- [x] Create model: `app/Models/PaymentGatewayType.php`
- [x] Create seeder: `database/seeders/PaymentGatewayTypeSeeder.php` (Midtrans, Xendit)

### School Payment Gateways Table
- [x] Create migration: `CreateSchoolPaymentGatewaysTable` (table: `tenant_payment_gateways`)
  - `id` (UUID, PK)
  - `tenant_id` (UUID, FK→tenants.id, CASCADE)
  - `gateway_type_id` (BIGINT, FK→payment_gateway_types.id)
  - `is_enabled` (BOOLEAN, default false)
  - `is_sandbox_mode` (BOOLEAN, default true)
  - `webhook_secret` (VARCHAR, nullable)
  - `created_at`, `updated_at`
- [x] Create model: `app/Models/SchoolPaymentGateway.php`
- [x] Add relationship to `School` model (`paymentGateways()`)

### Payment Gateway Credentials Table
- [x] Create migration: `CreatePaymentGatewayCredentialsTable`
  - `id` (UUID, PK)
  - `school_payment_gateway_id` (UUID, FK, CASCADE)
  - `credential_key` (VARCHAR) - e.g., `server_key`, `api_key`, `client_id`
  - `credential_value` (TEXT, encrypted with Laravel's `encrypted` cast)
  - `is_sensitive` (BOOLEAN, default true)
  - `created_at`, `updated_at`
- [x] Create model: `app/Models/PaymentGatewayCredential.php`
- [x] Add relationship to `SchoolPaymentGateway` (`credentials()`)

### Payment Transactions Table
- [x] Create migration: `CreatePaymentTransactionsTable`
  - `id` (UUID, PK)
  - `tenant_id` (UUID, FK, CASCADE)
  - `subscription_id` (UUID, FK→subscriptions.id, nullable)
  - `school_payment_gateway_id` (UUID, FK)
  - `transaction_id` (VARCHAR, unique) - External gateway transaction ID
  - `amount` (DECIMAL 15,2)
  - `currency` (VARCHAR, default `IDR`)
  - `status` (VARCHAR) - pending, completed, failed, refunded
  - `metadata` (JSON, nullable)
  - `created_at`, `updated_at`
- [x] Create model: `app/Models/PaymentTransaction.php`
- [x] Add relationships to `School`, `Subscription`, `SchoolPaymentGateway`

### Payment Webhooks Table
- [x] Create migration: `CreatePaymentWebhooksTable`
  - `id` (UUID, PK)
  - `school_payment_gateway_id` (UUID, FK)
  - `event_type` (VARCHAR) - e.g., `transaction.success`, `transaction.failed`
  - `payload` (TEXT, encrypted with Laravel's `encrypted` cast)
  - `processed` (BOOLEAN, default false)
  - `processed_at` (TIMESTAMP, nullable)
  - `created_at` (TIMESTAMP only, no updated_at)
- [x] Create model: `app/Models/PaymentWebhook.php`

### Subscription Tiers Table
- [x] Create migration: `CreateSubscriptionTiersTable`
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
- [x] Create model: `app/Models/SubscriptionTier.php`
- [x] Create seeder: `database/seeders/SubscriptionTierSeeder.php`
  - Starter: IDR 199,000/month, max 100 users, 5GB
  - Professional: IDR 499,000/month, max 500 users, 50GB
  - Enterprise: Custom pricing, unlimited

### Subscriptions Table
- [x] Create migration: `CreateSubscriptionsTable`
  - `id` (UUID, PK)
  - `tenant_id` (UUID, FK→tenants.id, CASCADE)
  - `tier_id` (BIGINT, FK→subscription_tiers.id)
  - `status` (VARCHAR) - trial, active, expired, cancelled, suspended
  - `started_at` (TIMESTAMP)
  - `expires_at` (TIMESTAMP)
  - `renewal_date` (TIMESTAMP, nullable)
  - `auto_renew` (BOOLEAN, default true)
  - `payment_method` (VARCHAR, nullable) - gateway name used
  - `created_at`, `updated_at`
- [x] Create model: `app/Models/Subscription.php`
- [x] Add relationships to `School`, `SubscriptionTier`, `PaymentTransaction`

### Demo LMS Access Table
- [x] Create migration: `CreateDemoLmsAccessTable`
  - `id` (UUID, PK)
  - `tenant_id` (UUID, FK→tenants.id, CASCADE)
  - `user_id` (UUID, FK→users.id, CASCADE)
  - `access_token` (VARCHAR, unique)
  - `expires_at` (TIMESTAMP)
  - `accessed_at` (TIMESTAMP, nullable)
  - `created_at` (TIMESTAMP only, no updated_at)
- [x] Create model: `app/Models/DemoLmsAccess.php`
- [x] Add relationships to `School`, `User`

### Additional Notes
- All models use `HasUuid` trait for UUID primary keys (except PaymentGatewayType and SubscriptionTier which use bigint auto-increment)
- All school-scoped models use `BelongsToTenant` trait for automatic school isolation
- Encrypted columns use Laravel's built-in `encrypted` cast (AES-256-CBC)
- All 8 factories created for test data generation
- Migrations verified to run cleanly; seeders tested and populate initial data
- Code formatted with Laravel Pint

---

## 📋 Phase 2.0B: Core Infrastructure ✅ COMPLETE

### Credential Encryption Service
- [x] Create `app/Services/CredentialEncryption.php`
  - `encrypt(string $value): string`
  - `decrypt(string $encrypted): string`
  - Uses Laravel's `Crypt` facade (AES-256-CBC)
- [x] Add tests for encryption/decryption

### Payment Gateway Contract
- [x] Create `app/Contracts/PaymentGateway.php` interface
  - `createInvoice(array $data): array`
  - `handleWebhook(array $payload): bool`
  - `checkTransactionStatus(string $transactionId): array`
  - `refund(string $transactionId, float $amount): bool`

### Payment Gateway Registry
- [x] Create `app/Services/PaymentGatewayRegistry.php`
  - Load available gateways from `payment_gateway_types` table
  - Cache gateway types for performance (1 hour TTL)
  - Provide method: `getGatewayTypes(): Collection`
  - Provide method: `getSchoolGateways(School $school): Collection`
  - Provide method: `clearCache(): void`

### Payment Gateway Factory
- [x] Create `app/Services/PaymentGatewayFactory.php`
  - `make(string $gatewayName, SchoolPaymentGateway $config): PaymentGateway`
  - Dynamic instantiation based on gateway type
  - Load and decrypt credentials from DB
  - Return appropriate gateway implementation (Midtrans, Xendit)
  - Throws `InvalidArgumentException` for unknown gateways

### Subscription Payment Service
- [x] Create `app/Services/SubscriptionPaymentService.php`
  - `createPaymentInvoice(Subscription $subscription, SchoolPaymentGateway $gateway): array`
  - `processWebhook(PaymentWebhook $webhook): bool`
  - `completeSubscription(PaymentTransaction $transaction): void`
  - `handleFailedPayment(PaymentTransaction $transaction): void`
  - `refundTransaction(PaymentTransaction $transaction, ?float $amount): bool`

### Service Container Bindings
- [x] Register services in `app/Providers/AppServiceProvider.php`
  - Bind `CredentialEncryption` singleton
  - Bind `PaymentGatewayRegistry` singleton
  - Bind `PaymentGatewayFactory` singleton
  - Bind `SubscriptionPaymentService` singleton

### Testing
- [x] Unit tests for CredentialEncryption (2 tests)
  - Encryption/decryption roundtrip
  - Different ciphertexts for same value
- [x] Unit tests for PaymentGatewayRegistry (3 tests)
  - Load active gateway types from database
  - Cache gateway types for performance
  - Clear cache functionality
- [x] Feature tests for SubscriptionPaymentService (2 tests)
  - Create payment invoice for subscription
  - Handle failed payments by expiring subscription

### Additional Notes
- Fixed SchoolPaymentGateway model to map to `tenant_payment_gateways` table
- All 7 tests passing
- Code formatted with Laravel Pint
- Commit: `feat(payment): implement gateway registry, factory, and services`

---

## 📋 Phase 2.0C: Gateway Implementations ✅ COMPLETE

### Midtrans Gateway Implementation ✅
- [x] Create `app/Services/PaymentGateways/MidtransGateway.php`
  - [x] Implement `PaymentGateway` contract
  - [x] `createInvoice()` - Call Midtrans API to generate transaction
  - [x] `handleWebhook()` - Process Midtrans notification webhook & verify signature
  - [x] `checkTransactionStatus()` - Query Midtrans for transaction status
  - [x] `refund()` - Process refund via Midtrans API
  - [x] Configuration: server_key, client_key
  - [x] Support sandbox/production modes
- [x] Add tests for Midtrans gateway (8 tests)

### Xendit Gateway Implementation ✅
- [x] Create `app/Services/PaymentGateways/XenditGateway.php`
  - [x] Implement `PaymentGateway` contract
  - [x] `createInvoice()` - Call Xendit API to generate invoice
  - [x] `handleWebhook()` - Process Xendit webhook & verify callback token
  - [x] `checkTransactionStatus()` - Query Xendit for invoice status
  - [x] `refund()` - Process refund via Xendit API
  - [x] Configuration: api_key, callback_token
  - [x] Support sandbox/production modes
- [x] Add tests for Xendit gateway (10 tests)

### Test Coverage ✅
- [x] 20 comprehensive feature tests
- [x] Invoice creation & error handling
- [x] Transaction status checking
- [x] Refund processing
- [x] Webhook signature verification
- [x] Sandbox/production mode URLs
- [x] HTTP client mocking & assertions
- [x] All tests passing

### Additional Notes ✅
- MidtransGateway uses Basic Auth with server_key
- XenditGateway uses Bearer token authentication with api_key
- Both support sandbox mode (configurable via `is_sandbox_mode`)
- Midtrans base URL: `https://app.sandbox.midtrans.com/api/v2` (sandbox)
- Xendit base URL: `https://api.sandbox.xendit.co` (sandbox)
- HTTP requests include timeout (30s) and retry (3 times with 100ms backoff)
- Webhook signatures verified using SHA-512 (Midtrans) and header tokens (Xendit)
- Error handling with try-catch returning structured error responses
- Code formatted with Laravel Pint
- Commit: `feat(payment): implement Midtrans and Xendit gateways`

---

## 📋 Phase 2.0D: Webhooks & Controllers ✅ COMPLETE

### Payment Webhook Controller ✅
- [x] Create `app/Http/Controllers/PaymentWebhookController.php`
  - [x] `handleMidtrans(Request $request): Response` - Route `/webhooks/midtrans`
  - [x] `handleXendit(Request $request): Response` - Route `/webhooks/xendit`
  - [x] Verify webhook signature per gateway
  - [x] Store payload in `payment_webhooks` table (encrypted)
  - [x] Dispatch job to process webhook asynchronously

### Webhook Processing Job ✅
- [x] Create `app/Jobs/ProcessPaymentWebhook.php`
  - [x] Load webhook from DB
  - [x] Verify signature authenticity
  - [x] Call `SubscriptionPaymentService::processWebhook()`
  - [x] Update transaction & subscription status
  - [x] Mark webhook as processed

### Webhook Routes ✅
- [x] Add routes in `routes/web.php` or dedicated webhook route file
  - [x] `POST /webhooks/midtrans`
  - [x] `POST /webhooks/xendit`
  - [x] Add rate limiting (burst: 100, throttle: 1000/min)

### Admin Gateway Management Controller ✅
- [x] Create `app/Http/Controllers/Admin/GatewayConfigController.php`
  - [x] `index()` - List configured gateways for school
  - [x] `create()` - Show form to add new gateway
  - [x] `store()` - Save gateway configuration with encrypted credentials
  - [x] `edit()` - Show form to edit gateway
  - [x] `update()` - Update gateway config
  - [x] `destroy()` - Remove gateway configuration
  - [x] `testConnection()` - Test gateway credentials (POST)

### Admin Gateway Management Views ✅
- [x] Create `resources/views/admin/gateways/index.blade.php` - List gateways
- [x] Create `resources/views/admin/gateways/create.blade.php` - Add gateway form
- [x] Create `resources/views/admin/gateways/edit.blade.php` - Edit gateway form

### Form Requests ✅
- [x] Create `app/Http/Requests/StoreGatewayConfigRequest.php`
  - [x] Validate gateway_type_id exists
  - [x] Validate credential keys match expected format
  - [x] Validate required credentials for each gateway type
  - [x] Unique constraint: one gateway type per school (initially)
- [x] Create `app/Http/Requests/UpdateGatewayConfigRequest.php`

---

## 📋 Phase 2.1: Pricing Tiers Configuration

**Status:** ⏳ In Progress (Phase 2.1.1 ✅ Complete)  
**Scope:** Per-school LMS pricing tier system with 4 default tiers  
**Timeline Estimate:** 3-4 weeks  
**Proposal Document:** `docs/phase-2-1-proposal.html`
**Phase 2.1.1 Completed:** 2026-07-14

### Overview
Per-school pricing tier system where the school (as customer) chooses a tier that determines features and capacity limits for all users within that school. Integration with existing payment gateway (Midtrans/Xendit from Phase 2.0C).

**Default Tiers:**
- **Basic** (Free) - No advanced features, 500 students/course max, 100GB storage
- **Plus** - Analytics + Live Session, 1,000 students/course max, 500GB storage
- **Pro** - Advanced Analytics, API Access, Recording, 5,000 students/course max, 2,000GB storage
- **Max** - Enterprise features (Custom Branding, SSO, Priority Support), Unlimited

---

### Phase 2.1.1: Core Data Structure ✅ COMPLETE

#### Migrations ✅
- [x] Create migration: `rename_subscription_tables_to_pricing`
  - Renamed `subscription_tiers` → `pricing_tiers` (dropped `features`, `max_users`, `storage_gb`)
  - Created `tier_features` table with unique constraint on (pricing_tier_id, feature_key)
  - Created `tier_limits` table with unique constraint on (pricing_tier_id, limit_key)
  - Renamed `subscriptions` → `school_tiers`
  - Created `tier_changes` table for audit trail
  - Seeding logic moved from seeder to migration

#### Eloquent Models ✅
- [x] Create `app/Models/PricingTier.php`
  - Relationships: `features()`, `limits()`
  - Enum casting: `billing_period` → BillingPeriod

- [x] Create `app/Models/TierFeature.php`
  - Relationship: `tier()` (belongsTo)

- [x] Create `app/Models/TierLimit.php`
  - Relationship: `tier()` (belongsTo)

- [x] Create `app/Models/SchoolTier.php`
  - Relationships: `school()`, `tier()`, `tierChanges()`
  - Traits: `BelongsToSchool`, `HasUuid`
  - Enum casting: `status` → SubscriptionStatus

- [x] Create `app/Models/TierChange.php`
  - Relationships: `schoolTier()`, `fromTier()`, `toTier()`
  - Trait: `HasUuid`
  - Enum casting: `change_type` → TierChangeType

- [x] Update `app/Models/PaymentTransaction.php`
  - Updated relation to point to `SchoolTier` (subscription_id FK)
  - Enum casting: `status` → PaymentStatus

#### Factories & Seeders ✅
- [x] Create factory: `database/factories/PricingTierFactory.php` (with BillingPeriod enum)
- [x] Create factory: `database/factories/SchoolTierFactory.php` (with SubscriptionStatus enum)
- [x] Create factory: `database/factories/TierFeatureFactory.php`
- [x] Create factory: `database/factories/TierLimitFactory.php`
- [x] Create factory: `database/factories/TierChangeFactory.php` (with TierChangeType enum)
- [x] Update factory: `database/factories/PaymentTransactionFactory.php` (with PaymentStatus enum)

- [x] Create seeder: `database/seeders/PricingTierSeeder.php`
  - 4 default tiers: Basic (free), Plus (299K IDR), Pro (799K IDR), Max (1999K IDR)
  - Features per tier: analytics, live_session, live_session_recording, api_access, etc.
  - Limits per tier: student_capacity_per_course, video_storage_gb, live_session_duration_minutes

- [x] Update seeder logic moved into migration

#### Enums ✅
- [x] Create `app/Enums/SubscriptionStatus.php` (pending, active, expired)
- [x] Create `app/Enums/PaymentStatus.php` (pending, completed, failed, refunded)
- [x] Create `app/Enums/TierChangeType.php` (initial, upgrade, downgrade)
- [x] Create `app/Enums/BillingPeriod.php` (monthly, yearly)
- [x] Add enum casting to all relevant models
- [x] Update services to use enum values

#### Tests ✅
- [x] Update `tests/Feature/PaymentWebhookTest.php` (11 tests pass)
- [x] Update `tests/Feature/Services/SubscriptionPaymentServiceTest.php` (2 tests pass)
- [x] Create `tests/Feature/PricingTierTest.php` (4 tests pass)
- [x] All 167 tests passing

---

### Phase 2.1.2: Admin Panel & Configuration ✅ COMPLETE

#### Controller & Routes ✅
- [x] Create Livewire components: `app/Livewire/PricingTiers/`
  - [x] `PricingTierIndex.php` - List all tiers
  - [x] `PricingTierCreate.php` - Show create form
  - [x] `PricingTierEdit.php` - Show edit form

- [x] Add routes in `routes/web.php` (admin namespace)
  - [x] `GET  /admin/pricing-tiers` - List tiers
  - [x] `GET  /admin/pricing-tiers/create` - Create form
  - [x] `GET  /admin/pricing-tiers/{tier}/edit` - Edit form

#### Views & Livewire Components ✅
- [x] Create Livewire component: `app/Livewire/PricingTiers/PricingTierIndex.php`
  - [x] Fetch and display all pricing tiers in table
  - [x] Handle delete action (with confirmation modal)
  - [x] Pagination support
  - [x] View: `resources/views/livewire/pricing-tiers/pricing-tier-index.blade.php`

- [x] Create Livewire component: `app/Livewire/PricingTiers/PricingTierCreate.php`
  - [x] Form to create pricing tier
  - [x] Feature toggles (checkboxes for each feature from enum)
  - [x] Limit inputs (form fields for each limit from enum)
  - [x] Real-time validation & pricing display in Rp format
  - [x] View: `resources/views/livewire/pricing-tiers/pricing-tier-create.blade.php`

- [x] Create Livewire component: `app/Livewire/PricingTiers/PricingTierEdit.php`
  - [x] Form to edit pricing tier
  - [x] Feature toggles and limits with current values pre-filled
  - [x] Real-time validation
  - [x] View: `resources/views/livewire/pricing-tiers/pricing-tier-edit.blade.php`

#### Services & Repository ✅
- [x] Create `app/Services/PricingTierService.php`
  - [x] `listAllTiers()` - Get all pricing tiers
  - [x] `getTierById($id)` - Get tier by ID
  - [x] `createTier(array $data)` - Create new tier with features and limits
  - [x] `updateTier($id, array $data)` - Update tier with features and limits
  - [x] `deleteTier($id)` - Delete tier
  - [x] `getTierBySlug(string $slug)` - Get tier by slug
  - [x] `isFeatureAvailable(School $school, string $feature): bool`
  - [x] `getLimit(School $school, string $limitKey): ?int`
  - [x] `getDefaultTier(): PricingTier`
  - [x] `assignTierToSchool(School $school, PricingTier $tier): SchoolTier`

- [x] Create `app/Repositories/PricingTier/PricingTierRepository.php`
  - [x] `all()` - Get all pricing tiers
  - [x] `find($id)` - Get tier by ID
  - [x] `create(array $data)` - Create new tier with features and limits
  - [x] `update($id, array $data)` - Update tier with features and limits
  - [x] `delete($id)` - Delete tier
  - [x] `findBySlug($slug)` - Get tier by slug
  
- [x] Create `app/Repositories/PricingTier/PricingTierRepositoryInterface.php`

#### Form Requests ✅
- [x] Create `app/Http/Requests/PricingTier/StorePricingTierRequest.php`
  - [x] Validate tier name (required, string, max 255)
  - [x] Validate slug (required, unique, regex)
  - [x] Validate price (required, numeric, min 0)
  - [x] Validate billing_period (required, in enum values)
  - [x] Validate features & limits (from predefined enums)

- [x] Create `app/Http/Requests/PricingTier/UpdatePricingTierRequest.php`
  - [x] Same validations as store, with unique slug exclusion

#### Permissions ✅
- [x] Add permission: `manage_pricing_tiers` (admin only)
  - [x] Includes: create_pricing_tiers, view_pricing_tiers, edit_pricing_tiers, delete_pricing_tiers
  - [x] Created via migration: `2026_07_14_125456_create_pricing_tier_permissions.php`
- [x] Protect routes with `authorize()` checks in Livewire components

#### Feature Tests ✅
- [x] Test: Tier CRUD operations (`tests/Feature/PricingTier/PricingTierManagementTest.php`)
  - [x] Test list tiers
  - [x] Test create tier with validation
  - [x] Test update tier with validation
  - [x] Test delete tier with confirmation
  - [x] Test unauthorized access

- [x] Test: Feature & limit management
  - [x] Test enable/disable features on tier
  - [x] Test set and update tier limits
  
#### Additional Notes ✅
- Features and limits use static enum choices, not user-customizable
- Price display and input in Rp format (IDR) without division
- Delete confirmation uses dark overlay modal pattern (x-cloak prevents flash)
- All tests passing
- Code formatted with Laravel Pint
- Commit: `feat(pricing-tiers): implement admin CRUD panel with repository pattern` (and related fixes)

---

### Phase 2.1.3: School Tier Assignment ✅ COMPLETE

#### Migrations ✅
- [x] Add `tier_id` column to `schools` table (migration)
  - FK to `pricing_tiers.id`
  - Default to Basic tier ID
- [x] Create migration to assign tier to existing schools
  - Backfilled all existing schools with Basic tier
  - Created SchoolTier and TierChange audit records

#### Model Updates ✅
- [x] Update `app/Models/School.php`
  - Add relationship: `tier()` (belongs_to PricingTier)
  - Add method: `getCurrentTierLimit(string $key): ?int`
  - Add method: `isFeatureEnabled(string $feature): bool`
  - Add method: `getCurrentSchoolTier()` - Get most recent subscription

#### Seeding & Defaults ✅
- [x] Update school creation to assign default tier
  - When school is created, automatically create `SchoolTier` record with Basic tier
  - SchoolService.assignDefaultTier() creates audit trail

#### Livewire Admin Components ✅
- [x] Create SchoolIndex - List all schools with tier, domain, created date
  - Pagination (15 per page), search by name/domain, filter by tier
- [x] Create SchoolEdit - Edit school and change tier
  - Show tier features/limits, tier change dropdown with confirmation modal
- [x] Create SchoolTierHistory - Display tier change audit trail

#### Views & Routes ✅
- [x] Add routes: GET /admin/schools, /admin/schools/{school}/edit, /admin/schools/{school}/tier-history
- [x] Create blade views with page titles
- [x] Add sidebar navigation link to Schools management

#### Factory Updates ✅
- [x] Update SchoolFactory to include tier_id
- [x] afterCreating hook creates SchoolTier and TierChange records
- [x] withTier() state method for custom tier selection

#### Tests ✅
- [x] Test: Default tier assignment on school creation (14 tests, all passing)
- [x] Test: School can query current tier
- [x] Test: School tier relationships work correctly

#### Bug Fixes (2026-07-15) ✅
- [x] Fixed Livewire computed property caching corruption in schools() and availableTiers()
- [x] Fixed migration down() method syntax (dropForeignKey → dropForeign)
- [x] Added page titles to all school views

---

### Phase 2.1.4: Feature Gating ✅ COMPLETE

#### Core Service ✅
- [x] Create `app/Services/FeatureGateService.php` with:
  - [x] `can(User|School, TierFeature): bool` - Check feature access
  - [x] `limit(User|School, TierLimit): ?int` - Get tier limit (null = unlimited)
  - [x] `requireFeature(User|School, TierFeature): void` - Enforce with exception
  - [x] `isLimitExceeded(User|School, TierLimit, int): bool` - Check usage vs limit

#### Exception Handling ✅
- [x] Create `app/Exceptions/FeatureNotAvailableException.php`
  - [x] With descriptive error messages including feature label

#### Middleware ✅
- [x] Create `app/Http/Middleware/CheckFeatureAccess.php`
  - [x] Route-level feature enforcement: `->middleware('feature:analytics')`
  - [x] Returns 401 (unauthenticated), 403 (forbidden), 400 (invalid feature)
  - [x] Registered in bootstrap/app.php

#### Authorization Gates ✅
- [x] Dynamic gate registration for each TierFeature
  - [x] Gates named: `use-{feature_key}` (e.g., 'use-analytics')
  - [x] Work with `Gate::forUser()->allows()` pattern
  - [x] Can be used in controllers: `$user->can('use-analytics')`

#### Tests ✅
- [x] 23 comprehensive feature gating tests (all passing)
  - [x] Feature availability checks (true/false cases, tier combinations)
  - [x] Limit retrieval (set values, unlimited, tier comparisons)
  - [x] Requirement enforcement (exceptions, messages)
  - [x] Limit exceeded detection
  - [x] Authorization gates (creation, access, denial)
  - [x] School model helper methods

#### Additional Notes ✅
- All 212 tests passing, 462 assertions checked
- Code formatted with Laravel Pint
- Commit: `feat(feature-gating): implement comprehensive feature gating system`

---

### Phase 2.1.5: Payment & Upgrade Flow

#### Services ✅
- [x] Create `app/Services/TierChangeService.php`
  - [x] `canUpgrade(School $school, PricingTier $newTier): bool`
  - [x] `canDowngrade(School $school, PricingTier $newTier): bool`
  - [x] `calculateProration(School $school, PricingTier $newTier): float`
  - [x] `initiateTierChange(School $school, PricingTier $newTier, string $gatewayName): ?array`

#### Tier Change Flow UI ✅
- [x] Create tier selection/comparison view
  - [x] Show current tier and available upgrade/downgrade options
  - [x] Display pricing and proration calculations

- [x] Create tier change confirmation view
  - [x] Show prorated amount
  - [x] Show payment method selection (if multiple gateways enabled)

#### Proration Logic ✅
- [x] Implement proration calculation
  - [x] Calculate remaining days in current subscription
  - [x] Calculate credit/charge for tier change
  - [x] Handle same-month changes

#### Payment Integration ✅
- [x] Integrate tier changes with payment gateway
  - [x] On tier upgrade: create payment invoice via gateway
  - [x] On tier downgrade: process refund via gateway
  - [x] Use existing `SubscriptionPaymentService` (Phase 2.0B)

- [x] Webhook handling for tier payment
  - [x] When payment succeeds: update SchoolTier status to active
  - [x] When payment fails: keep current tier, show error

- [x] Create `TierChangeJob` for async processing
  - [x] Process tier change after payment confirmed

#### Audit Trail ✅
- [x] Create `TierChange` record on successful upgrade/downgrade
  - [x] Track old_tier, new_tier, change_type, proration_amount
  - [x] Create `tier_changes` audit log

#### Controllers ✅
- [x] Create/Update `app/Http/Controllers/TierChangeController.php`
  - [x] `show()` - Show current tier & upgrade/downgrade options
  - [x] `initiate(Request $request)` - Initiate tier change with payment
  - [x] `cancel(Request $request)` - Cancel pending tier change

#### Routes ✅
- [x] Add routes:
  - [x] `GET  /tier-management` - Show tier options & history
  - [x] `POST /tier-management/change` - Initiate tier change
  - [x] `POST /tier-management/cancel` - Cancel pending change

#### Tests ✅
- [x] Test: Upgrade tier flow (success & failure)
- [x] Test: Downgrade tier flow with proration
- [x] Test: Proration calculation accuracy
- [x] Test: Payment webhook handles tier changes
- [x] Test: Tier change audit trail created
- [x] Test: Cannot change tier while payment pending
- [x] Test: Concurrent tier changes prevented

---

### Phase 2.1.6: Testing & Finalization

#### Test Coverage
- [ ] Run full test suite: `php artisan test --compact`
- [ ] Verify 95%+ code coverage for tier-related code
- [ ] Check: Unit tests for services
- [ ] Check: Feature tests for controllers
- [ ] Check: Integration tests with payment gateway

#### Code Quality
- [ ] Run `vendor/bin/pint --dirty --format agent`
- [ ] Fix any code style issues
- [ ] Review type hints on all methods
- [ ] Verify no secrets in code/logs

#### Documentation
- [ ] Add PHPDoc comments to all public methods
- [ ] Document tier feature keys in config or constant
- [ ] Document tier limit keys
- [ ] Add usage examples for TierService

#### Git Commits
- [ ] Commit Phase 2.1.1: `feat(pricing): add tier database schema and models`
- [ ] Commit Phase 2.1.2: `feat(pricing): implement tier admin panel and configuration`
- [ ] Commit Phase 2.1.3: `feat(pricing): add school tier assignment and defaults`
- [ ] Commit Phase 2.1.4: `feat(pricing): implement feature gating and limit enforcement`
- [ ] Commit Phase 2.1.5: `feat(pricing): add tier upgrade/downgrade with payment integration`

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
