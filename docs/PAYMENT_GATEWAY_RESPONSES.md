# Payment Gateway Response Format Standard

**Status:** ✅ Standardized
**Date:** 2026-07-14
**Applies to:** All PaymentGateway implementations (Midtrans, Xendit, future gateways)

---

## Overview

All payment gateway implementations must follow a consistent response format regardless of the underlying API differences. This ensures uniform handling across the application.

---

## Standard Response Formats

### 1. createInvoice() - Success Response

```php
[
    'success' => true,                    // bool - always true on success
    'transaction_id' => 'tx_12345',       // string - gateway's transaction/invoice ID
    'order_id' => 'ord-abc123',           // string - merchant's order ID
    'status' => 'pending',                // string - lowercase: pending, settlement, paid, etc
    'payment_url' => 'https://...',       // string|null - URL for customer to complete payment
    'amount' => 199000,                   // int - amount in base currency unit (smallest denomination)
    'currency' => 'IDR',                  // string - ISO 4217 code (IDR, USD, etc)
]
```

### 1. createInvoice() - Error Response

```php
[
    'success' => false,
    'error' => 'HTTP request returned status code 401: Invalid API key',  // string - error message
    'status_code' => 401,                 // int|null - HTTP status code from gateway
]
```

---

### 2. checkTransactionStatus() - Success Response

```php
[
    'success' => true,
    'transaction_id' => 'tx_12345',       // string - gateway's transaction ID
    'status' => 'settlement',             // string - lowercase status value
    'amount' => 199000,                   // int - transaction amount
    'currency' => 'IDR',                  // string - ISO 4217 code
    'payment_method' => 'credit_card',    // string|null - payment method used
]
```

### 2. checkTransactionStatus() - Error Response

```php
[
    'success' => false,
    'transaction_id' => 'tx_12345',
    'error' => 'Transaction not found',
    'status_code' => 404,                 // int|null - HTTP status code from gateway
]
```

---

### 3. handleWebhook() - Response

```php
true   // bool - true if webhook processed successfully
false  // bool - false if signature invalid or processing failed
```

**Note:** Webhook handling should:
- Verify signature authenticity (SHA-512 for Midtrans, X-Callback-Token for Xendit)
- Return false if signature invalid
- Return false if required fields missing
- Return true only if validation passed and webhook is authentic

---

### 4. refund() - Response

```php
true   // bool - true if refund initiated successfully
false  // bool - false if refund failed
```

---

## Status Value Normalization

All status values must be normalized to **lowercase**:

### Midtrans Status → Standard
- `capture` → `settlement`
- `settlement` → `settlement`
- `pending` → `pending`
- `expire` → `expired`
- `cancel` → `cancelled`
- `deny` → `failed`
- `failure` → `failed`

### Xendit Status → Standard
- `PAID` → `paid`
- `PENDING` → `pending`
- `EXPIRED` → `expired`

### Standard Status Values
- `pending` - Awaiting payment
- `settlement` - Payment confirmed/settled
- `paid` - Payment successful (Xendit)
- `expired` - Invoice/transaction expired
- `cancelled` - Cancelled by merchant/user
- `failed` - Payment failed
- `refunded` - Refunded to customer

---

## Field Specifications

### success (bool)
- `true` on successful API operation
- `false` on failure or error

### transaction_id (string)
- Gateway's transaction/invoice identifier
- Must be present on success
- Used to track transaction lifecycle

### order_id (string)
- Merchant's order identifier
- Returned in createInvoice for reference
- Used in webhook handling

### status (string)
- Must be lowercase
- One of the standard status values
- Represents current transaction state

### payment_url (string|null)
- Checkout/payment page URL
- Only in createInvoice response
- Null if not applicable (e.g., webhook responses)

### amount (int)
- Always an integer (no decimals)
- In the base currency unit (e.g., smallest denomination)
- For IDR: 1 = 1 Rupiah (no cents)
- For USD: 100 = $1.00 (100 cents)

### currency (string)
- ISO 4217 3-letter code
- Uppercase: IDR, USD, EUR, etc
- Default: IDR

### payment_method (string|null)
- Optional, identifies payment method used
- Examples: credit_card, bank_transfer, ewallet, etc
- Can be null if not available

### error (string)
- Human-readable error message
- Returned only on failure

### status_code (int|null)
- HTTP status code from gateway API
- Examples: 401, 404, 500, etc
- Can be null for non-HTTP errors

---

## Gateway-Specific Implementation Notes

### MidtransGateway
- API responds with `transaction_status` → normalized to `status`
- API responds with `status_code` → checked to determine success (201 = success)
- API responds with `gross_amount` → mapped to `amount` and cast to int
- Webhook signature: SHA-512(order_id + status_code + gross_amount + server_key)

### XenditGateway
- API responds with `status` (uppercase) → normalized to lowercase
- API responds with `id` → mapped to `transaction_id`
- API responds with `external_id` → mapped to `order_id`
- Amount already received as int from API
- Webhook signature: X-Callback-Token header verification

---

## Best Practices

1. **Always check `success` flag first** before accessing other fields
2. **Amount is always an integer** - handle calculations in base units
3. **Status is always lowercase** - no need for additional normalization
4. **Treat null payment_url as expected** - some gateways may not provide it
5. **Error responses only include:** success, error, status_code (no other fields)
6. **Cache HTTP calls where appropriate** but respect webhook freshness
7. **Log full API responses separately** - don't include in structured response

---

## Testing

All gateway implementations must include tests verifying:
- ✅ Success response structure and types
- ✅ Error response structure and types
- ✅ Status normalization (lowercase)
- ✅ Amount as integer
- ✅ Currency default and override
- ✅ Sandbox/production URL switching
- ✅ Webhook signature verification (success and failure cases)
- ✅ Request retry logic (3 attempts, 100ms backoff)
- ✅ Timeout handling (30 second timeout)

Current test coverage: **20 tests** across Midtrans and Xendit

---

## Migration Guide for Future Gateways

When adding a new payment gateway:

1. Create `app/Services/PaymentGateways/NewGateway.php`
2. Implement `PaymentGateway` contract
3. Transform API responses to **standard format**:
   - Map API status → lowercase standard status
   - Map API ID → transaction_id
   - Cast amount to integer
   - Include all required fields
4. Include tests validating response format
5. Update PaymentGatewayFactory to include new gateway
6. Document any gateway-specific quirks

The consumer code doesn't need to know about gateway-specific response formats.
