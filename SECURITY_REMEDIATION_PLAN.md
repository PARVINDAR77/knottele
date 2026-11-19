# KNOTELLE INDIA — SECURITY REMEDIATION PLAN

**Target:** `knotelleindia.com` / `knottele-fullstack`  
**Execution Strategy:** Phased rollout, smallest safe changes, zero customer-facing disruption, automated test verification, and complete rollback safety.

---

## 1. REMEDIATION PRIORITY MATRIX

| Priority | Finding ID | Description | Impact Area | Risk Level |
|---|---|---|---|---|
| **P0** | **SEC-CRIT-001** | Fix Unverified Password Reset Endpoint | Authentication | High / Immediate |
| **P0** | **SEC-CRIT-002** | Fix Payment Status Bypass on Checkout | Financial / Business Logic | High / Immediate |
| **P0** | **SEC-CRIT-003** | Fix Arbitrary Price & Discount Manipulation | Financial / Integrity | High / Immediate |
| **P0** | **SEC-CRIT-004** | Fix Anonymous Order & PII Disclosure / BOLA | Data Privacy / DPDP | High / Immediate |
| **P0** | **SEC-CRIT-005** | Fix Unauthenticated Custom Order Scraping | Data Privacy / DPDP | High / Immediate |
| **P1** | **SEC-HIGH-001** | Enforce Strict Media Upload File Type Validation | System Safety / Storage | Medium |
| **P1** | **SEC-HIGH-002** | Remove Admin Bearer Token from Query String | Credential Exposure | Low |
| **P1** | **SEC-HIGH-003** | Add Comprehensive Rate Limiting on Auth & Public APIs | Availability / Anti-Abuse | Low |
| **P1** | **SEC-HIGH-004** | Restrict Trusted Proxies to Prevent IP Table Flooding | Infrastructure / DoS | Low |
| **P2** | **SEC-MED-001** | Redact Plaintext OTPs from System Logs | Privacy / Logging | Low |
| **P2** | **SEC-MED-002** | Enforce `Secure` Cookie Flag in Production | Session Security | Low |
| **P2** | **SEC-MED-003** | Inject Essential HTTP Security Headers in Nginx | Browser Hardening | Low |
| **P2** | **SEC-MED-004** | Add Performance Indexes for Orders & Lookups | Database Health | Low |
| **P3** | **SEC-LOW-001** | Pin Composer Dependencies (`pusher-php-server`) | Supply Chain | Low |
| **P3** | **SEC-LOW-002** | Deprecate Unpaginated Legacy `/api/products` Dump | API Optimization | Low |

---

## 2. DETAILED ACTION PLANS

### TASK P0-1: Remediate Password Reset Account Takeover (SEC-CRIT-001)
- **Objective:** Prevent unauthorized password resets by requiring a valid, verified 6-digit OTP code before any password change is permitted.
- **Affected File:** `backend/app/Http/Controllers/Api/Customer/CustomerApiAuthController.php`
- **Root Cause:** `resetPassword` accepted `email` and `password` without verifying any token or OTP.
- **Proposed Change:**
  1. Update `resetPassword` to validate:
     ```php
     $request->validate([
         'email' => 'required|email',
         'otp' => 'required|string|digits:6',
         'password' => 'required|string|min:8|confirmed',
     ]);
     ```
  2. Verify that a valid `CustomerOtp` record exists for the email, check expiration (`expires_at`), attempt count (`attempts < 5`), and cryptographic hash (`Hash::check($enteredOtp, $otpRecord->otp_hash)`).
  3. Once verified, update the password, purge the OTP record, and revoke all existing Sanctum tokens (`$customer->tokens()->delete()`).
- **Risk:** Low (Standard authentication behavior).
- **Dependencies:** None.
- **Test Plan:**
  - Test resetting password without OTP (expect 422).
  - Test resetting with expired/invalid OTP (expect 422).
  - Test resetting with valid OTP (expect 200, verified password change).
- **Rollback Plan:** Revert controller changes via `git checkout`.
- **Completion Criteria:** No password can be changed without valid OTP verification.

---

### TASK P0-2: Remediate Payment Bypass & Forced "Paid" Order Creation (SEC-CRIT-002)
- **Objective:** Ensure no non-COD order is marked as "paid" without cryptographic verification of payment signatures.
- **Affected File:** `backend/app/Http/Controllers/Api/Customer/OrderController.php`
- **Root Cause:** Line 439 blindly assigned `'payment_status' => $request->payment_method === 'cod' ? 'pending' : 'paid'`.
- **Proposed Change:**
  1. Change default status for non-COD orders:
     ```php
     $paymentStatus = 'pending';
     if ($request->payment_method !== 'cod') {
         // Verify Razorpay transaction if payment payload provided
         $paymentId = $request->input('razorpay_payment_id');
         $orderId = $request->input('razorpay_order_id');
         $signature = $request->input('razorpay_signature');

         if ($paymentId && $orderId && $signature) {
             $razorpayService = app(\App\Services\Payment\RazorpayService::class);
             $verification = $razorpayService->verifyPayment($paymentId, $orderId, $signature);
             if ($verification['success']) {
                 $paymentStatus = 'paid';
             }
         }
     }
     ```
  2. Record the verified `razorpay_payment_id` and transaction details in the order notes or transactions table.
- **Risk:** Low (Ensures orders without real payments remain pending until webhook or verification confirms).
- **Dependencies:** `RazorpayService`.
- **Test Plan:**
  - Submit order with `payment_method = 'razorpay'` without payment ID (expect `payment_status = 'pending'`).
  - Submit order with valid Razorpay payment signature (expect `payment_status = 'paid'`).
- **Rollback Plan:** Revert `OrderController.php`.
- **Completion Criteria:** Unverified online orders cannot be recorded as `'paid'`.

---

### TASK P0-3: Remediate Price & Discount Manipulation in Checkout (SEC-CRIT-003)
- **Objective:** Enforce server-side price calculation directly from the product variant database.
- **Affected File:** `backend/app/Http/Controllers/Api/Customer/OrderController.php`
- **Root Cause:** Controller trusted client-supplied `unit_price` and arbitrary `discount_total`/`discount_amount`.
- **Proposed Change:**
  1. Remove `$unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : ...`.
  2. Always enforce `$unitPrice = (float) $variant->price`.
  3. Remove the arbitrary fallback block accepting `discount_total` or `discount_amount` from the request payload.
  4. Only calculate discounts based on validated, active database offers matching `offer_code`.
- **Risk:** Low.
- **Dependencies:** None.
- **Test Plan:**
  - Send checkout request with `unit_price: 1` on a ₹1,500 item (expect server to calculate total using ₹1,500).
  - Send checkout request with `discount_amount: 1000` without valid offer (expect discount to be ₹0).
- **Rollback Plan:** Revert `OrderController.php`.
- **Completion Criteria:** Total calculation is 100% authoritative on the server.

---

### TASK P0-4: Remediate Anonymous Order & PII Disclosure / BOLA (SEC-CRIT-004)
- **Objective:** Prevent unauthenticated users from accessing order details, customer addresses, and invoices.
- **Affected File:** `backend/app/Http/Controllers/Api/Customer/OrderController.php`
- **Root Cause:** If neither authenticated user nor `?email=` is provided, code fell through and returned order data.
- **Proposed Change:**
  1. In `show` and `printInvoice`:
     ```php
     if (!$customer && empty($email)) {
         return response()->json([
             'success' => false,
             'message' => 'Unauthorized. Please sign in or provide verification credentials.'
         ], 401);
     }
     ```
  2. For guest orders, verify that the order's shipping address email matches the query email, AND require the high-entropy alphanumeric `order_number` (not incremental numeric `id`).
  3. Require signed URL tokens or guest order tokens for invoice rendering.
- **Risk:** Low.
- **Dependencies:** None.
- **Test Plan:**
  - `GET /api/customer/orders/1` without authentication or email (expect 401 Unauthorized).
  - `GET /api/customer/orders/1/invoice` without authentication (expect 401/403).
  - `GET /api/customer/orders/1` with matching authenticated customer (expect 200).
- **Rollback Plan:** Revert `OrderController.php`.
- **Completion Criteria:** Zero anonymous or cross-account access to orders and invoices.

---

### TASK P0-5: Remediate Custom Order Scraping & BOLA (SEC-CRIT-005)
- **Objective:** Prevent sequential scraping of custom order requests and customer PII.
- **Affected File:** `backend/app/Http/Controllers/Api/Customer/CustomOrderController.php`
- **Root Cause:** `show` searched `orWhere('id', $reference)` without ownership or auth checks.
- **Proposed Change:**
  1. Remove `orWhere('id', $reference)`. Only allow lookup by high-entropy `reference_id`.
  2. If the user is authenticated, ensure the order belongs to them.
  3. If guest, require `email` parameter matching the order's `customer_email`.
- **Risk:** Low.
- **Dependencies:** None.
- **Test Plan:**
  - `GET /api/customer/custom-orders/1` (expect 404/403).
  - `GET /api/customer/custom-orders/KNT-CUSTOM-XXXX` without matching email (expect 403).
- **Rollback Plan:** Revert `CustomOrderController.php`.
- **Completion Criteria:** Incremental ID lookups are completely disabled.

---

### TASK P1-1: Enforce Strict Media Upload Validation (SEC-HIGH-001)
- **Objective:** Ensure only genuine image formats can be uploaded and stored.
- **Affected File:** `backend/app/Http/Controllers/Admin/MediaController.php`
- **Proposed Change:**
  1. Replace `'files.*' => 'file|max:20480'` with:
     ```php
     'files.*' => 'required|file|mimes:jpeg,jpg,png,webp,gif|max:20480',
     ```
  2. Use `$file->guessExtension()` instead of raw client extension.
  3. Restrict SVG uploads or sanitize with XML entity stripping.
- **Risk:** Low.
- **Dependencies:** None.
- **Completion Criteria:** Non-image extensions (.php, .html, .sh, .phar) are rejected with HTTP 422.

---

### TASK P1-2: Eliminate Plaintext Admin Token in URL (SEC-HIGH-002)
- **Objective:** Prevent admin API tokens from leaking into logs and browser history.
- **Affected Files:**
  - `backend/app/Http/Controllers/Admin/AuthController.php`
  - `backend/app/Http/Middleware/AdminAuth.php`
- **Proposed Change:**
  1. In `AuthController.php:75`, remove `['auth_token' => $token]` from redirect.
  2. Store token in session or deliver via standard Authorization headers.
- **Risk:** Low.
- **Dependencies:** None.
- **Completion Criteria:** No URLs contain `?auth_token=`.

---

### TASK P1-3: Implement Rate Limiting Across Auth & Public APIs (SEC-HIGH-003)
- **Objective:** Protect endpoints against brute force, credential stuffing, and spam flooding.
- **Affected Files:** `backend/bootstrap/app.php`, `backend/routes/web.php`, `backend/routes/customer_api.php`
- **Proposed Change:**
  1. Define named rate limiters:
     - `RateLimiter::for('auth', fn($request) => Limit::perMinute(5)->by($request->ip()));`
     - `RateLimiter::for('api-orders', fn($request) => Limit::perMinute(15)->by($request->ip()));`
     - `RateLimiter::for('contact', fn($request) => Limit::perMinute(5)->by($request->ip()));`
  2. Apply `middleware('throttle:auth')` to signin, login, register, and reset-password routes.
- **Risk:** Low.
- **Dependencies:** Laravel Cache driver.
- **Completion Criteria:** Excessive requests receive HTTP 429 Too Many Requests.

---

### TASK P1-4: Configure Strict Trusted Proxies (SEC-HIGH-004)
- **Objective:** Prevent client IP spoofing and database flooding via `X-Forwarded-For`.
- **Affected File:** `backend/bootstrap/app.php`
- **Proposed Change:**
  Change `$middleware->trustProxies(at: '*');` to:
  ```php
  $middleware->trustProxies(at: [
      '127.0.0.1',
      '::1',
      '172.16.0.0/12',
      '10.0.0.0/8',
  ]);
  ```
- **Risk:** Low.
- **Dependencies:** None.
- **Completion Criteria:** Spoofed `X-Forwarded-For` from untrusted external IPs is discarded.

---

### TASK P2: Security Hardening (P2-1 to P2-4)
1. **Redact OTPs from logs (SEC-MED-001):** Replace `$otp` in `Log::info()` with generic completion messages.
2. **Session Cookie Flags (SEC-MED-002):** Set `SESSION_SECURE_COOKIE=true` in `backend/.env` and update `AuthController.php`.
3. **HTTP Security Headers (SEC-MED-003):** Add CSP, HSTS, X-Content-Type-Options, X-Frame-Options, Referrer-Policy to `docker/nginx.conf`.
4. **Database Indexes (SEC-MED-004):** Create non-destructive migration adding indexes on `orders(customer_id, created_at)` and `orders(status, created_at)`.
