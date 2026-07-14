# School Registration Development Plan

**Status:** In Progress  
**Date:** 2026-07-14  
**Version:** 1.0  
**Last Updated:** Phase 1 (Multi-Tenancy Foundation) ✅ Mostly Complete

---

## 🚀 Progress Summary

| Phase | Status | Notes |
|-------|--------|-------|
| **Phase 1: Database & Multi-Tenancy** | ✅ **95% Complete** | UUID PKs, Tenants, TenantScope, Domain Resolution implemented |
| **Phase 2A: Payment Gateway** | ⏳ **Not Started** | Architecture designed, awaiting implementation |
| **Phase 2B: Subscription & Pricing** | ⏳ **Not Started** | Schema designed, awaiting implementation |
| **Phase 3: Auth & Verification** | ⏳ **Partial** | Admin-only login implemented, email verification pending |
| **Phase 4-13: Registration & Beyond** | ⏳ **Not Started** | Ready for implementation |

---

## 📋 Requirements Overview

| Aspek | Deskripsi |
|-------|-----------|
| **User Type** | Admin Sekolah (registrasi utama) |
| **Multi-School** | Ya - satu user bisa di multiple sekolah, kecuali siswa |
| **Data** | Lengkap (sekolah, admin, verifikasi) |
| **Email Verification** | Ya, kecuali untuk admin |
| **Approval** | Auto-approve (instant activation) |
| **Subscription** | Ada - dengan tier berbeda |
| **Trial** | Tidak ada, tapi akses demo LMS gratis |
| **Roles** | Admin Sekolah, Staff, Siswa |
| **Onboarding** | Ya - guided setup wizard |

---

## 📊 Phase 1 Completion Status (From phase-1-tasklist.md)

### ✅ COMPLETED ITEMS

**1.1 UUID Primary Key Configuration**
- [x] Enable `uuid-ossp` PostgreSQL extension
- [x] Create `HasUuid` trait (`app/Traits/HasUuid.php`)
- [x] Migrations use UUID PKs (`$table->uuid('id')->primary()`)
- [x] Unit test for UUID generation

**1.2 Tenants Migration & Model**
- [x] Create `tenants` table migration
- [x] Create `Tenant` model with `HasUuid`
- [x] Define `hasMany(User)` relationship
- [x] Create `TenantFactory`
- [x] Feature test for tenant creation

**1.3 Users Table Tenant Scoping**
- [x] Convert `users.id` to UUID PK
- [x] Add `tenant_id` foreign key (CASCADE)
- [x] Update `User` model with `belongsTo(Tenant)`
- [x] Update `UserFactory` to associate Tenant
- [x] Verify Fortify auth flows still work

**1.4 Global Tenant Scope**
- [x] Create `TenantScope.php` implementing `Illuminate\Database\Eloquent\Scope`
- [x] Create `BelongsToTenant` trait
- [x] Apply to `User` model
- [x] Create `CurrentTenant` singleton class
- [x] Tests proving tenant isolation & auto-fill

**1.5 Middleware & Domain Resolution**
- [x] Create `ResolveTenantFromDomain` middleware
- [x] Create `TenantDomainResolver.php` utility
- [x] Three-tier domain routing (lms.local / admin.lms.local / schoolN.lms.local)
- [x] Register globally in `bootstrap/app.php`

**1.6 School Registration & Landing Page (lms.local)**
- [x] Create `StoreTenantRequest` form request
- [x] Create `TenantController@store` action
- [x] Create public landing/registration page
- [x] Form posts to `TenantController@store`
- [x] Redirect to new school subdomain

**1.7 Admin Panel Routes (admin.lms.local)**
- [x] Create admin-only route group on `admin.lms.local`
- [x] Admin login with role-based validation
- [x] Create `AdminLoginController` & custom `admin-login.blade.php`
- [x] Prevent non-admin users from accessing admin panel (403 Forbidden)
- [x] Admin users can bypass email verification requirement
- [x] Configure Fortify domain to root only

**1.8 Refactor Registration Flow**
- [x] Refactor `CreateNewUser` to use `CurrentTenant`
- [x] Remove `Tenant::create()` call from registration
- [x] Throw error if `CurrentTenant::getTenantId()` null on regular user signup

### ⏳ PENDING/WRAP-UP ITEMS

**1.9 Verification & Wrap-Up**
- [ ] Run `php artisan migrate:fresh --no-interaction` & verify schema with `database-schema` Boost tool
- [ ] Run full test suite: `php artisan test --compact`
- [ ] Run `vendor/bin/pint --dirty --format agent`
- [ ] Confirm no other models still use auto-incrementing PKs

### 📝 Key Commits
- ✅ **7c84f65**: `feat(tenant): add global tenant scope and domain-based resolution`
- ✅ **6f0232a**: `refactor(tenant): move register URL building into service`
- ✅ **73f7bdd**: `fix(auth): prevent tenant_id override in user registration`
- ✅ **1122835**: `feat(auth): add admin-only login on admin subdomain`
- ✅ **b9fe060**: `fix(auth): allow admin users to bypass email verification`

### 🎯 Current Architecture (Implemented)

```
Three-Tier Domain Routing:

lms.local (root/landing)
├─ Public area (no tenant)
├─ School registration form
└─ Landing page

admin.lms.local (admin panel)
├─ Admin-only access (role='admin', tenant_id=NULL)
├─ Admin login with validation
└─ Admin dashboard/settings

schoolN.lms.local (school apps)
├─ School-scoped app (CurrentTenant=schoolN_tenant_id)
├─ User registration/login
└─ School dashboard
```

**Tenant Resolution Flow:**
```
Request → ResolveTenantFromDomain Middleware
  ↓
Extract subdomain → Query tenants table
  ↓
Set CurrentTenant singleton
  ↓
BelongsToTenant trait auto-fills tenant_id on model creation
  ↓
TenantScope filters all queries by tenant_id
```

---

## Phase 1: Multi-Tenancy Foundation (✅ 95% Complete)

> **STATUS:** Core implementation done. Only verification/wrap-up pending (see checklist at end).

### 1.1 Existing Database Tables (Already Implemented)

#### `tenants` Table ✅
```sql
id (UUID), name (VARCHAR), domain (VARCHAR, unique, nullable), 
created_at, updated_at
```
- Domain-based tenant resolution via `ResolveTenantFromDomain` middleware
- Supports: `admin.lms.local`, `lms.local`, `schoolN.lms.local`

#### `users` Table ✅
```sql
id (UUID), tenant_id (UUID, FK→tenants.id), email, password, 
email_verified_at, remember_token, created_at, updated_at
```
- UUID primary key (not auto-increment)
- `tenant_id` scoped via global `TenantScope`
- Admin users: `tenant_id=NULL` (opsi C invariant)

### 1.2 New Tables to Create (For School Registration Phase)

#### `schools` Table
```sql
id (UUID), tenant_id (UUID, FK), name, short_name, email, phone, 
address, city, province, postal_code, country, npsn (nullable), 
logo_url, description, is_active, subscription_tier_id, 
created_at, updated_at
```

#### `school_admins` Table (Pivot)
```sql
id (UUID), school_id (UUID, FK), user_id (UUID, FK), 
role (owner/admin/manager), assigned_at
```

#### `subscriptions` Table
```sql
id (UUID), school_id (UUID, FK), tier_id, status, 
started_at, expires_at, renewal_date, auto_renew, payment_method, 
created_at, updated_at
```

#### `subscription_tiers` Table
```sql
id (BIGINT), name (Starter/Professional/Enterprise), price, 
currency, features (JSON), billing_period, created_at, updated_at
```

#### `demo_lms_access` Table
```sql
id (UUID), school_id (UUID, FK), user_id (UUID, FK), 
access_token, expires_at, accessed_at, created_at
```

### 1.3 Models to Create
- [ ] `School` - with relationships: tenant, admins, subscription, users
- [ ] `SchoolAdmin` - pivot model
- [ ] `Subscription` - with school & tier relationships
- [ ] `SubscriptionTier` - pricing tiers
- [ ] `DemoLmsAccess` - demo account management

---

## Phase 2: Subscription Schema & Pricing + Payment Gateway (⏳ Not Started)

**Prerequisites:** Phase 1 wrap-up ✅

**References (Design Documents):**
- [PAYMENT_GATEWAY_DESIGN.md](PAYMENT_GATEWAY_DESIGN.md) — **V2.0** - Unlimited gateways, encrypted DB credentials, no hardcoding

### 2.0 Payment Gateway Architecture ✨ (Flexible Multi-Gateway with Secure Credentials)

**Design:** Plugin-based architecture with database-stored credentials

**Default Gateways:**
- **Midtrans** - Primary (Indonesia)
- **Xendit** - Alternative (Indonesia)

**Key Features:**
- Support **unlimited payment gateways** (easily add Stripe, Doku, PayPal, etc.)
- Store credentials securely in database (encrypted AES-256)
- No hardcoded gateway configurations
- Add/update/remove gateways without code changes (admin panel only)
- Each school can select which gateways they support
- Per-gateway sandbox/production credentials

**Architecture Layers:**

```
Database (Credentials + Config)
         ↓
PaymentGatewayRegistry (Load gateways from DB)
         ↓
PaymentGatewayFactory (Create instances dynamically)
         ↓
PaymentGateway Implementations (Midtrans, Xendit, + optional others)
         ↓
SubscriptionPaymentService (Business logic)
```

**Database Schema for Gateway Management:**

```sql
-- Store available payment gateway types
payment_gateway_types
  id, name (midtrans, stripe, doku, etc)
  label, description, is_active

-- Store school's gateway configurations
school_payment_gateways
  id, school_id, gateway_type_id, is_enabled
  is_sandbox_mode, webhook_secret

-- Store encrypted credentials securely
payment_gateway_credentials
  id, school_payment_gateway_id
  credential_key (e.g., "server_key", "api_key")
  credential_value (encrypted)
  is_sensitive (boolean - for UI display)

-- Payment transaction history
payment_transactions
  id, school_id, subscription_id, payment_gateway_id
  transaction_id, amount, status, metadata

-- Webhook audit trail
payment_webhooks
  id, payment_gateway_id, event_type
  payload (encrypted), processed, processed_at
```

**Payment Flow:**
```
1. Admin selects tier + billing period
2. Show available payment gateways (configured for school)
3. Admin picks a gateway
4. Load credentials from DB (decrypt)
5. Call PaymentGatewayFactory::make('gateway_name', credentials)
6. Generate payment invoice
7. Redirect to gateway
8. Gateway sends webhook
9. Update subscription status to 'active'
```

**Implementation Requirements:**

**Phase 2.0A: Database & Models**
- [ ] Create `payment_gateway_types` table (seeded with Midtrans, Stripe, Doku, etc)
- [ ] Create `school_payment_gateways` table
- [ ] Create `payment_gateway_credentials` table (encrypted)
- [ ] Create `payment_transactions` table
- [ ] Create `payment_webhooks` table
- [ ] Update `subscriptions` table (add gateway fields)
- [ ] Create models: `PaymentGatewayType`, `SchoolPaymentGateway`, `PaymentGatewayCredential`, `PaymentTransaction`, `PaymentWebhook`

**Phase 2.0B: Core Infrastructure**
- [ ] Create `PaymentGateway` contract/interface
- [ ] Create `PaymentGatewayRegistry` (load gateways from DB)
- [ ] Create `PaymentGatewayFactory` (dynamic instantiation)
- [ ] Create `CredentialEncryption` service (encrypt/decrypt)
- [ ] Create `SubscriptionPaymentService` (business logic)

**Phase 2.0C: Gateway Implementations (Default)**
- [ ] Implement `MidtransGateway`
- [ ] Implement `XenditGateway`
- [ ] (Optional: Add more gateways later - implement interface + register in DB)

**Phase 2.0D: Webhooks & Controllers**
- [ ] Create `PaymentWebhookController` (dynamic routing)
- [ ] Create admin panel for gateway management (add/edit/delete credentials)
- [ ] Add webhook routes

**Credential Encryption Strategy:**
- Use Laravel's `Crypt` facade (AES-256-CBC by default)
- Credentials encrypted before storing in DB
- Decrypted only when needed (in memory)
- Never log or display full credentials
- Rotation mechanism for periodic re-encryption

**Security Checklist:**
- [ ] Credentials encrypted at rest (DB)
- [ ] Only decrypt in-memory when needed
- [ ] Webhook signature verification per gateway
- [ ] Webhook payload encrypted storage
- [ ] Access control: only school admin can view/edit their credentials
- [ ] Audit log for credential changes
- [ ] Rate limiting on webhook endpoints
- [ ] No credentials in logs/error messages

### 2.1 Pricing Tiers

| Tier | Harga | Pengguna | Storage | Fitur |
|------|-------|---------|---------|-------|
| **Starter** | IDR 199.000/bulan | Max 100 users | 5 GB | Dasar LMS, Email support |
| **Professional** | IDR 499.000/bulan | Max 500 users | 50 GB | Advanced LMS, API, Priority support |
| **Enterprise** | Custom | Unlimited | Unlimited | Custom features, Dedicated support |

### 2.2 Features by Tier

**Starter:**
- [ ] Class Management
- [ ] Assignment & Submission
- [ ] Basic Grading
- [ ] Email Notifications
- [ ] Basic Reporting

**Professional:**
- [ ] Semua Starter
- [ ] Advanced Analytics
- [ ] Custom Branding
- [ ] API Access
- [ ] LTI Integration
- [ ] Video Hosting (25 hrs/month)

**Enterprise:**
- [ ] Semua Professional
- [ ] Unlimited Video
- [ ] Custom Integration
- [ ] SSO/SAML
- [ ] Dedicated Server Option
- [ ] Custom SLA

### 2.3 Demo LMS

**Access:**
- [ ] 14 hari akses penuh ke Professional tier
- [ ] Max 50 test users
- [ ] Dengan watermark "Demo"
- [ ] Auto-expire setelah 14 hari
- [ ] Generate unique demo token per school

---

## Phase 3: Authentication & Verification (⏳ Partial - In Progress)

**Prerequisites:** Phase 1 ✅ | Phase 2 (Subscription models)

**Status:** Admin-only login implemented ✅ | Email verification pending

### 3.1 Registration Flow (Admin)

```
Admin Input Form
  ↓
Validate Data (tidak ada validasi khusus, basic validation saja)
  ↓
Create School + Admin User
  ↓
Email Verification (optional untuk admin - bisa skip)
  ↓
Auto-Approve School
  ↓
Generate Demo LMS Token
  ↓
Redirect ke Onboarding
```

### 3.2 Email Verification

- [ ] Admin: **Optional** - bisa langsung login
- [ ] Staff & Siswa: **Required** (jika di-invite)
- [ ] Verification link valid 24 jam
- [ ] Resend option tersedia

### 3.3 Demo LMS Access

- [ ] Generate unique demo credentials
- [ ] Demo account dengan role admin demo
- [ ] Demo data pre-loaded (sample classes, students, assignments)
- [ ] Watermark di semua pages
- [ ] Access logs tracked

---

## Phase 4: Registration Form Data (⏳ Not Started)

### 4.1 School Information
- [ ] School Name (required)
- [ ] Short Name (required, untuk URL subdomain)
- [ ] Email (required, school email)
- [ ] Phone (required)
- [ ] Address (required)
- [ ] City (required)
- [ ] Province (required)
- [ ] Postal Code (required)
- [ ] Country (required, default Indonesia)
- [ ] NPSN/School ID (optional)
- [ ] Logo/Banner (optional image upload)
- [ ] Description (optional, untuk public profile)

### 4.2 Admin Information
- [ ] Full Name (required)
- [ ] Email (required)
- [ ] Password (required, min 8 chars)
- [ ] Confirm Password (required)
- [ ] Phone (required)
- [ ] Title/Position (required)

### 4.3 Subscription Selection
- [ ] Pilih tier (Starter/Professional/Enterprise)
- [ ] Billing period (monthly/annual)
- [ ] Accept terms & conditions

---

## Phase 5: Onboarding Flow (Wizard) (⏳ Not Started)

### 5.1 Step 1: Welcome
- [ ] Welcome message
- [ ] School name confirmation
- [ ] Quick stats overview

### 5.2 Step 2: Setup Basics
- [ ] Import CSV untuk staff/teachers
- [ ] Create first class/subject
- [ ] Set academic calendar/term

### 5.3 Step 3: Invite Users
- [ ] Bulk invite teachers (email)
- [ ] Bulk invite students (dengan class)
- [ ] Set their roles

### 5.4 Step 4: Customize
- [ ] School branding (logo, colors)
- [ ] Email templates customization
- [ ] Notification preferences

### 5.5 Step 5: Demo & Tour
- [ ] Interactive product tour
- [ ] Demo LMS access button
- [ ] Documentation links

### 5.6 Step 6: Complete
- [ ] Dashboard redirect
- [ ] Success message
- [ ] Next action suggestions

---

## Phase 6: Multi-School & Role Management (⏳ Not Started)

### 6.1 Admin Multi-School Access

- [ ] Admin dapat:
  - Switch between schools
  - Manage multiple schools
  - Share resources across schools (optional feature)
  
- [ ] UI indicator:
  - Current active school in navbar
  - Dropdown to switch schools
  - Quick access menu

### 6.2 Role & Permission Assignment

| Role | Permission Scope |
|------|-----------------|
| **School Owner** | Full school access, billing, staff management |
| **School Admin** | Full school access except billing |
| **Staff/Teacher** | Class & student management, grading |
| **Siswa** | Assignment submission, grades viewing |

### 6.3 Constraints
- [ ] Siswa HANYA bisa di 1 sekolah
- [ ] Prevent siswa dari multiple school registration
- [ ] Validation di registration form

---

## Phase 7: Database Migrations (⏳ Not Started)

### 7.1 Migrations to Create
- [ ] Create `schools` table
- [ ] Create `school_admins` pivot table
- [ ] Create `subscriptions` table
- [ ] Create `subscription_tiers` table (seeder)
- [ ] Create `demo_lms_access` table
- [ ] Add `school_id` to `users` table
- [ ] Add subscription relation migrations

### 7.2 Seeders
- [ ] `SubscriptionTierSeeder` - populate starter/professional/enterprise
- [ ] Demo data seeder untuk demo LMS

---

## Phase 8: API & Routes (⏳ Not Started)

### 8.1 Public Routes
```
POST   /register                    - Show registration form
POST   /register                    - Submit registration
GET    /verify-email/:token         - Email verification
POST   /resend-verification         - Resend verification email
```

### 8.2 Admin Routes (Authenticated)
```
GET    /dashboard                   - Main dashboard
GET    /onboarding                  - Onboarding wizard
POST   /onboarding/:step            - Save onboarding step
GET    /schools                     - List user's schools
POST   /schools/{id}/switch         - Switch active school
GET    /schools/{id}/settings       - School settings
POST   /schools/{id}/settings       - Update settings
GET    /subscription                - Subscription details
POST   /subscription/upgrade        - Upgrade tier
GET    /demo-lms                    - Demo LMS access
POST   /demo-lms/generate           - Generate demo credentials
```

### 8.3 Demo LMS Routes
```
GET    /demo-lms/dashboard          - Demo dashboard
GET    /demo-lms/classes            - Demo classes
GET    /demo-lms/students           - Demo students
```

---

## Phase 9: Frontend Components (⏳ Not Started)

### 9.1 Registration Page
- [ ] Multi-step form (school info → admin info → subscription)
- [ ] Form validation & error messages
- [ ] Progress indicator
- [ ] Terms & conditions modal

### 9.2 Onboarding Wizard
- [ ] Step-by-step component
- [ ] Side progress bar
- [ ] Next/Back navigation
- [ ] Save progress indication
- [ ] Skip option (dengan confirm)

### 9.3 Dashboard
- [ ] School overview card
- [ ] Subscription status widget
- [ ] Quick action buttons
- [ ] Recent activity log
- [ ] School switcher dropdown

### 9.4 Settings Pages
- [ ] School profile editor
- [ ] Billing & subscription management
- [ ] User management
- [ ] Integration settings

---

## Phase 10: Notifications & Email (⏳ Not Started)

### 10.1 Email Templates
- [ ] Welcome email (after registration)
- [ ] Email verification (if not skipped)
- [ ] Subscription confirmation
- [ ] Demo LMS access credentials
- [ ] Onboarding reminders
- [ ] Subscription expiry warning (7 days before)

### 10.2 Notifications
- [ ] In-app notifications untuk subscription status
- [ ] Dashboard alerts untuk pending actions
- [ ] Email notifications untuk admin actions

---

## Phase 11: Security & Validation (⏳ Not Started)

### 11.1 Registration Security
- [ ] CSRF protection
- [ ] Rate limiting (max 5 registrations per IP per hour)
- [ ] Duplicate email/school name check
- [ ] Password hashing & requirements
- [ ] Honeypot field untuk spam prevention

### 11.2 Demo LMS Security
- [ ] Unique token generation
- [ ] Token expiry validation
- [ ] Access logging
- [ ] IP whitelisting option (future)

### 11.3 Subscription Validation
- [ ] Verify payment method integration (Stripe/Doku)
- [ ] Failed payment handling
- [ ] Subscription state machine validation

---

## Phase 12: Testing (⏳ Not Started)

### 12.1 Unit Tests
- [ ] School model relationships
- [ ] Subscription tier logic
- [ ] Role & permission checks

### 12.2 Feature Tests
- [ ] Registration flow end-to-end
- [ ] Email verification process
- [ ] Onboarding completion
- [ ] Multi-school switching
- [ ] Demo LMS access
- [ ] Subscription upgrade

### 12.3 Integration Tests
- [ ] Payment gateway integration
- [ ] Email sending
- [ ] Demo data seeding

---

## Phase 13: Implementation Checklist (⏳ Pending)

### Priority 1: Core Registration
- [ ] Database migrations & models
- [ ] Registration form & validation
- [ ] Admin creation & authentication
- [ ] School creation & activation
- [ ] Demo LMS token generation

### Priority 2: Verification & Onboarding
- [ ] Email verification flow
- [ ] Onboarding wizard
- [ ] Step persistence
- [ ] Admin dashboard

### Priority 3: Subscription & Payment
- [ ] Subscription tier selection
- [ ] Payment integration (Stripe/Doku)
- [ ] Subscription management UI
- [ ] Billing history

### Priority 4: Multi-School & Advanced
- [ ] School switcher UI
- [ ] Multi-school permissions
- [ ] Bulk user import
- [ ] Advanced settings

### Priority 5: Polish & Optimization
- [ ] Email templates design
- [ ] Performance optimization
- [ ] Analytics tracking
- [ ] Security audit

---

## 🎯 Immediate Next Steps (After Phase 1 Wrap-Up)

### STEP 1: Finalize Phase 1 (⏳ Current Priority)
```bash
# 1. Verify Phase 1 completion
php artisan migrate:fresh --no-interaction
php artisan test --compact

# 2. Check database schema matches plan
# Use: mcp__laravel-boost__database-schema

# 3. Fix code style
vendor/bin/pint --dirty --format agent

# 4. Git commit if any pending changes
git status
git add -A
git commit -m "phase-1: finalize multi-tenancy foundation"
```

### STEP 2: Create Subscription Models (Phase 2)
**Order:** 
1. `CreateSubscriptionTiersTable` migration + seed (Starter/Professional/Enterprise)
2. `CreateSubscriptionsTable` migration
3. `SubscriptionTier` & `Subscription` models
4. Add relationships to `School` model

**Files to create:**
- `database/migrations/XXXX_create_subscription_tiers_table.php`
- `database/migrations/XXXX_create_subscriptions_table.php`
- `app/Models/SubscriptionTier.php`
- `app/Models/Subscription.php`
- `database/seeders/SubscriptionTierSeeder.php`

### STEP 3: Create School Models (Phase 7)
**Order:**
1. `CreateSchoolsTable` migration
2. `CreateSchoolAdminsTable` migration
3. `CreateDemoLmsAccessTable` migration
4. `School`, `SchoolAdmin`, `DemoLmsAccess` models
5. Add relationships to `Tenant` & `Subscription`

### STEP 4: Build Registration Form (Phase 4-8)
**Order:**
1. Create `SchoolRegistrationRequest` form request
2. Create `SchoolController@create` (show form)
3. Create `SchoolController@store` (handle registration)
4. Create registration view (multi-step form)
5. Wire routes on school subdomain

### STEP 5: Email Verification & Onboarding (Phase 3, 5)
**Order:**
1. Implement email verification for staff/students
2. Create onboarding wizard controller & views
3. Implement 6-step onboarding flow
4. Demo LMS token generation

---

## Notes & Decisions

- Email verification optional untuk admin untuk mempercepat onboarding
- Auto-approve mengurangi friction, tapi bisa ditambah admin approval di future
- Demo LMS dengan Professional tier features untuk showcase capabilities
- Multi-school constraint: siswa HANYA 1 sekolah, admin bisa multiple untuk flexibility
