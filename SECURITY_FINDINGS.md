# KNOTELLE INDIA — COMPREHENSIVE SECURITY AUDIT FINDINGS

**Target Scope:** `knotelleindia.com` / `knottele-fullstack`  
**Assessment Standard:** OWASP Top 10 (2021), OWASP API Security Top 10 (2023), OWASP ASVS 4.0  
**Audit Date:** November 2026  
**Auditor:** Senior DevSecOps & Application Security Engineering Team  

---

## 1. FINDINGS SUMMARY TABLE

| Finding ID | Severity | Category | Title | OWASP Mapping | Status |
|---|---|---|---|---|---|
| **SEC-CRIT-001** | **CRITICAL** | Broken Authentication | Total Account Takeover via Unverified Password Reset Endpoint | OWASP API2:2023 / A07:2021 | **CONFIRMED VULNERABILITY** |
| **SEC-CRIT-002** | **CRITICAL** | Business Logic / Payments | Payment Bypass & Forced "Paid" Order Creation Without Verification | OWASP API6:2023 / A04:2021 | **CONFIRMED VULNERABILITY** |
| **SEC-CRIT-003** | **CRITICAL** | Business Logic / Data Integrity | Arbitrary Price & Discount Manipulation in Checkout Pipeline | OWASP API6:2023 / A04:2021 | **CONFIRMED VULNERABILITY** |
| **SEC-CRIT-004** | **CRITICAL** | Broken Object Level Auth (BOLA) | Anonymous Full Order History & PII Disclosure / Direct Invoice Leak | OWASP API1:2023 / API3:2023 | **CONFIRMED VULNERABILITY** |
| **SEC-CRIT-005** | **CRITICAL** | Broken Object Level Auth (BOLA) | Unauthenticated Custom Order Enumeration & PII Scraping | OWASP API1:2023 / API3:2023 | **CONFIRMED VULNERABILITY** |
| **SEC-HIGH-001** | **HIGH** | Unrestricted Uploads | Unrestricted File Type Upload in Admin Media Controller | OWASP A03:2021 / A04:2021 | **CONFIRMED VULNERABILITY** |
| **SEC-HIGH-002** | **HIGH** | Credential Exposure | Admin Bearer Token Transmitted in URL Query Parameter | OWASP A02:2021 / A07:2021 | **CONFIRMED VULNERABILITY** |
| **SEC-HIGH-003** | **HIGH** | Rate Limiting & Abuse | Total Absence of Rate Limiting on Authentication & Public Endpoints | OWASP API4:2023 / API8:2023 | **CONFIRMED VULNERABILITY** |
| **SEC-HIGH-004** | **HIGH** | Network Trust & Abuse | Spoofed IP Table Flooding via Overly Permissive Proxy Trust | OWASP A05:2021 / API8:2023 | **CONFIRMED VULNERABILITY** |
| **SEC-MED-001** | **MEDIUM** | Sensitive Data Exposure | Cleartext Registration & Resend OTP Logged to Application Disk | OWASP A09:2021 / A02:2021 | **CONFIRMED VULNERABILITY** |
| **SEC-MED-002** | **MEDIUM** | Session Security | Insecure Session Cookie Flags in Production Configuration | OWASP A07:2021 / A05:2021 | **CONFIRMED VULNERABILITY** |
| **SEC-MED-003** | **MEDIUM** | Server & Header Hardening | Missing Essential HTTP Security Headers (CSP, HSTS, X-Frame, MIME) | OWASP A05:2021 | **CONFIRMED VULNERABILITY** |
| **SEC-MED-004** | **MEDIUM** | Performance & Database Health | Missing Critical Database Indexes on Orders & Lookups | CWE-400 / Reliability | **VERIFIED WEAKNESS** |
| **SEC-LOW-001** | **LOW** | Supply Chain Security | Unpinned Wildcard Package Dependency in Composer | OWASP A06:2021 | **VERIFIED WEAKNESS** |
| **SEC-LOW-002** | **LOW** | Excessive Data Exposure | Unpaginated Public Product Dump Endpoint | OWASP API3:2023 | **VERIFIED WEAKNESS** |

---

## 2. DETAILED EVIDENCE-BASED FINDINGS

### SEC-CRIT-001: Total Account Takeover via Unverified Password Reset Endpoint
- **Severity:** CRITICAL (CVSS:4.0/AV:N/AC:L/AT:N/PR:N/UI:N/VC:H/VI:H/VA:H/SC:H/SI:H/SA:H — Score: 9.8)
- **Category:** Broken Authentication & Authorization
- **Status:** CONFIRMED VULNERABILITY
- **Affected File:** `backend/app/Http/Controllers/Api/Customer/CustomerApiAuthController.php` (Lines 417–441)
- **Affected Route:** `POST /api/customer/reset-password` (declared in `backend/routes/customer_api.php:21`)
- **Technical Explanation:**
  The `resetPassword` endpoint accepts `email` and `password` without verifying any reset token, OTP code, old password, or email confirmation link:
  ```php
  public function resetPassword(Request $request)
  {
      $request->validate([
          'email' => 'required|string|email',
          'password' => 'required|string|min:6',
      ]);

      $email = strtolower(trim($request->email));
      $customer = Customer::where('email', $email)->first();

      if (!$customer) {
          return response()->json([
              'success' => false,
              'message' => 'No account registered with this email address.'
          ], 404);
      }

      $customer->password = Hash::make($request->password);
      $customer->save();

      return response()->json([
          'success' => true,
          'message' => 'Password reset successfully! You can now log in with your new password.'
      ]);
  }
  ```
- **Attack Scenario:**
  1. Attacker obtains or guesses any registered customer email (e.g., from order notifications, reviews, or previous data).
  2. Attacker sends a simple HTTP POST request:
     ```http
     POST /api/customer/reset-password HTTP/1.1
     Host: knotelleindia.com
     Content-Type: application/json

     {"email": "victim@example.com", "password": "AttackerControlledPassword123!"}
     ```
  3. The victim's password in the database is instantly overwritten with the attacker's password.
  4. Attacker logs in as the victim, accesses their stored addresses, order history, and personal data.
- **Recommended Remediation:**
  1. Deprecate direct unverified resets. Require an OTP or cryptographically signed time-limited reset token sent strictly to the customer's email.
  2. The `resetPassword` endpoint must validate: `['email' => 'required|email', 'otp' => 'required|string|digits:6', 'password' => 'required|string|min:8']`.
  3. Verify against a hashed OTP record in `CustomerOtp` with expiration and attempt limits before updating the password.
  4. Invalidate all active Sanctum tokens (`$customer->tokens()->delete()`) upon password reset.

---

### SEC-CRIT-002: Payment Bypass & Forced "Paid" Order Creation Without Verification
- **Severity:** CRITICAL (CVSS:4.0/AV:N/AC:L/AT:N/PR:N/UI:N/VC:L/VI:H/VA:N/SC:L/SI:H/SA:N — Score: 9.2)
- **Category:** Business Logic & Financial Security
- **Status:** CONFIRMED VULNERABILITY
- **Affected File:** `backend/app/Http/Controllers/Api/Customer/OrderController.php` (Line 439)
- **Affected Route:** `POST /api/customer/orders` (declared in `backend/routes/customer_api.php:52`)
- **Technical Explanation:**
  When an order is created, the payment status logic is implemented as follows:
  ```php
  'payment_status' => $request->payment_method === 'cod' ? 'pending' : 'paid',
  ```
  If an attacker sends `payment_method: 'razorpay'` or `'online'`, the order is marked in the database as `'paid'` immediately, without verifying that any payment was actually completed through Razorpay!
  Neither `razorpay_payment_id`, `razorpay_order_id`, nor `razorpay_signature` is validated inside `OrderController.php:store`.
- **Attack Scenario:**
  1. Attacker adds items worth ₹10,000 to their cart.
  2. Attacker invokes `POST /api/customer/orders` with `payment_method: "razorpay"`.
  3. The order is committed with `payment_status = 'paid'` and `status = 'pending'`.
  4. The merchant sees a confirmed "paid" order in the admin order dashboard and ships goods without receiving funds.
- **Recommended Remediation:**
  1. For non-COD orders (`razorpay`, `online`, `upi`, `card`), initial status must NEVER be `'paid'`. It must be `'pending'` or `'awaiting_payment'`.
  2. If the client claims the order was paid via Razorpay, require `razorpay_payment_id`, `razorpay_order_id`, and `razorpay_signature`.
  3. Validate the signature cryptographically server-side using Razorpay Key Secret: `hash_hmac('sha256', $orderId . '|' . $paymentId, $secret)`.
  4. Only mark `payment_status = 'paid'` after cryptographic signature validation succeeds and the transaction is recorded.

---

### SEC-CRIT-003: Arbitrary Price & Discount Manipulation in Checkout Pipeline
- **Severity:** CRITICAL (CVSS:4.0/AV:N/AC:L/AT:N/PR:N/UI:N/VC:N/VI:H/VA:N/SC:N/SI:H/SA:N — Score: 8.7)
- **Category:** Business Logic & Input Validation
- **Status:** CONFIRMED VULNERABILITY
- **Affected File:** `backend/app/Http/Controllers/Api/Customer/OrderController.php` (Lines 325, 378–383)
- **Affected Route:** `POST /api/customer/orders`
- **Technical Explanation:**
  1. Line 325 accepts client-supplied unit prices:
     ```php
     $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : ($variant ? (float) $variant->price : 0);
     ```
     If the user sends `unit_price: 1`, the server calculates totals based on ₹1 rather than the product's actual price from `ProductVariant::find($variantId)->price`.
  2. Lines 378–383 accept arbitrary client-supplied discounts:
     ```php
     if ($discountTotal == 0 && ($request->filled('discount_total') || $request->filled('discount_amount') || $request->filled('discount'))) {
         $discountTotal = (float) ($request->discount_total ?? $request->discount_amount ?? $request->discount ?? 0);
         if ($discountTotal > $subtotal) {
             $discountTotal = $subtotal;
         }
     }
     ```
     Any user can pass `discount_amount: 5000` without entering a valid offer code.
- **Attack Scenario:**
  1. Attacker sends an order payload with `items[0][variant_id] = 12` (a ₹2,500 handbag), but specifies `"unit_price": 1`.
  2. The server records the subtotal as ₹1 and grand total as ₹1 (or ₹100 including shipping).
- **Recommended Remediation:**
  1. Never trust client-provided unit prices. Always fetch the canonical price directly from `ProductVariant::findOrFail($variantId)->price`.
  2. Reject client-provided `discount_total`, `discount_amount`, or `discount` fields. Discounts must only be calculated server-side through validated active offers in the database.

---

### SEC-CRIT-004: Anonymous Full Order History & PII Disclosure / Direct Invoice Leak
- **Severity:** CRITICAL (CVSS:4.0/AV:N/AC:L/AT:N/PR:N/UI:N/VC:H/VI:N/VA:N/SC:H/SI:N/SA:N — Score: 8.6)
- **Category:** Broken Object Level Authorization (BOLA / IDOR)
- **Status:** CONFIRMED VULNERABILITY
- **Affected File:** `backend/app/Http/Controllers/Api/Customer/OrderController.php` (Lines 124–149, 186–204)
- **Affected Routes:**
  - `GET /api/customer/orders/{id}`
  - `GET /api/customer/orders/{id}/invoice`
  - `GET /api/customer/orders?email=...`
- **Technical Explanation:**
  In `show` and `printInvoice`:
  ```php
  if ($customer) {
      // ownership check
  } elseif (!empty($email)) {
      // email check
  }

  // If both $customer is null and $email is empty, it proceeds to return the order!
  return response()->json(['success' => true, 'data' => $this->formatOrder($order)]);
  ```
  When an anonymous user queries `/api/customer/orders/1` without providing an email, both conditions evaluate to false, skipping all authorization checks!
  Furthermore, `GET /api/customer/orders?email=victim@example.com` returns the entire order history of that email address without requiring authentication or OTP proof.
- **Attack Scenario:**
  1. An unauthorized third party scripts a loop from `id=1` to `id=10000` against `/api/customer/orders/{id}` and `/api/customer/orders/{id}/invoice`.
  2. The script extracts full customer names, phone numbers, complete shipping addresses, emails, and transaction amounts.
- **Recommended Remediation:**
  1. Restrict `/api/customer/orders/{id}` and invoices to authenticated customers (`auth('customer_api')->check()`), verifying `$order->customer_id === $authCustomer->id`.
  2. For guest orders, do not allow sequential integer lookups. Require both the secure random alphanumeric order number (`order_number`) AND an order lookup token (or signed URL with HMAC verification) sent to the customer's email upon order completion.

---

### SEC-CRIT-005: Unauthenticated Custom Order Enumeration & PII Scraping
- **Severity:** CRITICAL (CVSS:4.0/AV:N/AC:L/AT:N/PR:N/UI:N/VC:H/VI:N/VA:N/SC:H/SI:N/SA:N — Score: 8.6)
- **Category:** Broken Object Level Authorization (BOLA / IDOR)
- **Status:** CONFIRMED VULNERABILITY
- **Affected File:** `backend/app/Http/Controllers/Api/Customer/CustomOrderController.php` (Lines 200–224)
- **Affected Route:** `GET /api/customer/custom-orders/{reference}`
- **Technical Explanation:**
  The `show` method searches by integer `id` in addition to `reference_id`:
  ```php
  $order = CustomOrderRequest::where('reference_id', $reference)
      ->orWhere('id', $reference)
      ->first();
  ```
  There is zero authentication and zero ownership verification. Anyone can retrieve custom order details by querying `/api/customer/custom-orders/1`, `/api/customer/custom-orders/2`, etc.
- **Attack Scenario:**
  1. Attacker queries `/api/customer/custom-orders/1`.
  2. Server responds with customer's full name, email, phone number, design requirements, budget range, and uploaded reference images.
- **Recommended Remediation:**
  1. Remove `->orWhere('id', $reference)` completely.
  2. Ensure `reference_id` is a high-entropy cryptographically secure string (e.g., `Str::random(32)` or UUIDv4) rather than `KNT-CUSTOM-` + 4-digit number.
  3. If authenticated, verify `$order->customer_id === auth('customer_api')->id()`. If guest, verify email or secure token.

---

### SEC-HIGH-001: Unrestricted File Type Upload in Admin Media Controller
- **Severity:** HIGH (CVSS:4.0/AV:N/AC:L/AT:N/PR:H/UI:N/VC:H/VI:H/VA:H/SC:H/SI:H/SA:H — Score: 8.5)
- **Category:** Unrestricted File Upload & Remote Code Execution Risk
- **Status:** CONFIRMED VULNERABILITY
- **Affected File:** `backend/app/Http/Controllers/Admin/MediaController.php` (Lines 1809, 1857–1870)
- **Affected Route:** `POST /admin/media/upload`
- **Technical Explanation:**
  Validation rule only specifies `'file|max:20480'`:
  ```php
  $request->validate([
      'files' => 'nullable|array',
      'files.*' => 'file|max:20480',
      'file' => 'nullable|file|max:20480',
  ]);
  ```
  The code extracts the client's original extension:
  ```php
  $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
  $uniqueName = ($cleanSlug ?: 'knotelle-media') . '_' . time() . '_' . Str::random(4) . '.' . $extension;
  $file->move($targetDirectory, $uniqueName);
  ```
  Files are moved directly into web-accessible directories (`public/images/products`, `public/images/hero`, or `storage/app/public/...`).
  While Nginx has `^~ /images/` configured, any configuration drift or alternative path allows uploaded files with `.html`, `.svg`, `.sh`, or script extensions to execute client-side (Stored XSS) or server-side.
- **Recommended Remediation:**
  1. Enforce strict MIME-type and extension validation:
     ```php
     'mimes:jpg,jpeg,png,webp,gif|max:20480'
     ```
  2. Disallow SVG uploads unless sanitized through an XML parser (e.g., `enshrined/svg-sanitize`).
  3. Never use `$file->getClientOriginalExtension()`; use `$file->guessExtension()`.
  4. Ensure Nginx configuration blocks PHP/script execution across all upload folders (`uploads`, `images`, `storage`).

---

### SEC-HIGH-002: Admin Bearer Token Transmitted in URL Query Parameter
- **Severity:** HIGH (CVSS:4.0/AV:N/AC:L/AT:P/PR:N/UI:R/VC:H/VI:H/VA:N/SC:H/SI:H/SA:N — Score: 7.8)
- **Category:** Credential Exposure & Session Security
- **Status:** CONFIRMED VULNERABILITY
- **Affected Files:**
  - `backend/app/Http/Controllers/Admin/AuthController.php` (Lines 74–77)
  - `backend/app/Http/Middleware/AdminAuth.php` (Line 15)
- **Technical Explanation:**
  Upon successful admin sign-in, the controller redirects to the dashboard with the plaintext personal access token in the query string:
  ```php
  $redirectUrl = !empty($token)
      ? route('admin.dashboard', ['auth_token' => $token])
      : route('admin.dashboard');
  return redirect($redirectUrl);
  ```
  The middleware reads this query parameter:
  ```php
  $tokenParam = urldecode($request->query('auth_token') ?: $request->header('X-Admin-Token', ''));
  ```
  Tokens in URL query parameters are automatically recorded in web server access logs (Nginx `access.log`), browser history, proxy logs, and sent via `Referer` headers to third-party assets.
- **Recommended Remediation:**
  1. Remove `auth_token` query parameter from the redirect URL.
  2. Use standard `Authorization: Bearer <token>` headers or secure HTTP-only cookies to handle authentication.
  3. Revoke any token passed via query parameters.

---

### SEC-HIGH-003: Total Absence of Rate Limiting on Authentication & Public Endpoints
- **Severity:** HIGH (CVSS:4.0/AV:N/AC:L/AT:N/PR:N/UI:N/VC:L/VI:L/VA:H/SC:L/SI:L/SA:H — Score: 7.7)
- **Category:** Abuse & Brute-Force Prevention
- **Status:** CONFIRMED VULNERABILITY
- **Affected Routes:**
  - `POST /admin/signin` and `POST /admin/login` (Admin Brute-Force)
  - `POST /api/customer/login` (Customer Credential Stuffing)
  - `POST /api/customer/register` (Spam Registration)
  - `POST /api/customer/reset-password` (Account Takeover / Spam)
  - `POST /api/customer/orders` (Order Flooding)
  - `POST /api/contact` (Contact Form Mail Bombing)
- **Technical Explanation:**
  None of these routes are wrapped in Laravel's `throttle` middleware (e.g. `throttle:5,1` or `throttle:60,1`). An attacker can send thousands of requests per second from automated botnets to crack passwords, spam emails, or exhaust server resources.
- **Recommended Remediation:**
  1. Add strict rate limiters in `bootstrap/app.php` or route files:
     - Admin Signin: `throttle:5,1` (5 attempts per minute)
     - Customer Login: `throttle:10,1` (10 attempts per minute)
     - OTP & Password Reset: `throttle:3,1` (3 attempts per minute)
     - Checkout & Orders: `throttle:15,1` (15 requests per minute)
     - Contact Form: `throttle:5,1` (5 submissions per minute)

---

### SEC-HIGH-004: Spoofed IP Table Flooding via Overly Permissive Proxy Trust
- **Severity:** HIGH (CVSS:4.0/AV:N/AC:L/AT:N/PR:N/UI:N/VC:N/VI:L/VA:H/SC:N/SI:L/SA:H — Score: 7.5)
- **Category:** Denial of Service & Infrastructure Integrity
- **Status:** CONFIRMED VULNERABILITY
- **Affected Files:**
  - `backend/bootstrap/app.php` (Line 31)
  - `backend/app/Http/Middleware/TrackVisitors.php` (Lines 16–32)
- **Technical Explanation:**
  In `bootstrap/app.php`:
  ```php
  $middleware->trustProxies(at: '*');
  ```
  Trusting proxies at `'*'` means Laravel accepts any `X-Forwarded-For` header sent by any external client.
  In `TrackVisitors.php`, executed on every web request:
  ```php
  \App\Models\Visitor::firstOrCreate([
      'ip_address' => $request->ip(),
      'visit_date' => now()->format('Y-m-d')
  ]);
  ```
  An attacker can send millions of requests with random `X-Forwarded-For: <random_ip>` headers. Each request triggers a database query and row insertion into the `visitors` table, exhausting database storage, table lock queues, and memory.
- **Recommended Remediation:**
  1. Restrict trusted proxies to loopback and Docker network ranges:
     ```php
     $middleware->trustProxies(at: ['127.0.0.1', '::1', '172.16.0.0/12']);
     ```
  2. Implement an in-memory Redis or Cache gate for visitor tracking rather than executing a direct DB query on every request.

---

### SEC-MED-001: Cleartext Registration & Resend OTP Logged to Application Disk
- **Severity:** MEDIUM (CVSS:4.0/AV:L/AC:L/AT:N/PR:L/UI:N/VC:H/VI:N/VA:N/SC:H/SI:N/SA:N — Score: 6.8)
- **Category:** Sensitive Data Exposure
- **Status:** CONFIRMED VULNERABILITY
- **Affected File:** `backend/app/Http/Controllers/Api/Customer/CustomerApiAuthController.php` (Lines 134, 309)
- **Technical Explanation:**
  The registration and resend OTP methods write plaintext OTPs to the application log:
  ```php
  \Log::info("KNOTELLE Registration OTP for {$email}: {$otp}");
  \Log::info("KNOTELLE Resend OTP for {$email}: {$otp}");
  ```
  If server logs are exposed, read by lower-privileged processes, or forwarded to external log aggregators, OTPs are fully visible in plaintext.
- **Recommended Remediation:**
  Remove the plaintext OTP from log statements:
  ```php
  \Log::info("KNOTELLE Registration OTP generated for {$email}");
  ```

---

### SEC-MED-002: Insecure Session Cookie Flags in Production Configuration
- **Severity:** MEDIUM (CVSS:4.0/AV:N/AC:L/AT:P/PR:N/UI:R/VC:L/VI:L/VA:N/SC:L/SI:L/SA:N — Score: 5.3)
- **Category:** Session Security
- **Status:** CONFIRMED VULNERABILITY
- **Affected Files:**
  - `backend/config/session.php` (Line 172)
  - `backend/app/Http/Controllers/Admin/AuthController.php` (Line 86)
- **Technical Explanation:**
  In `config/session.php`:
  `'secure' => env('SESSION_SECURE_COOKIE'),`
  `SESSION_SECURE_COOKIE` is absent from `backend/.env`, defaulting to `null` (false).
  Furthermore, in `AuthController.php:86`, the cookie is manually constructed with:
  ```php
  $response->withCookie(cookie($cookieName, session()->getId(), 120, '/', config('session.domain'), false, true, false, 'lax'));
  ```
  The `secure` parameter is explicitly passed as `false`.
- **Recommended Remediation:**
  1. Add `SESSION_SECURE_COOKIE=true` to `backend/.env`.
  2. Update `AuthController.php` to use `true` for the `secure` cookie flag in production.

---

### SEC-MED-003: Missing Essential HTTP Security Headers
- **Severity:** MEDIUM (CVSS:4.0/AV:N/AC:L/AT:N/PR:N/UI:R/VC:N/VI:L/VA:N/SC:N/SI:L/SA:N — Score: 5.1)
- **Category:** Browser & Transport Hardening
- **Status:** CONFIRMED VULNERABILITY
- **Affected Files:**
  - `docker/nginx.conf`
  - Host reverse proxy configuration (`setup_new_server.sh`)
- **Technical Explanation:**
  Neither the container Nginx nor the host reverse proxy serves modern defense-in-depth security headers:
  - `Content-Security-Policy` (CSP)
  - `Strict-Transport-Security` (HSTS)
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: SAMEORIGIN`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Permissions-Policy`
- **Recommended Remediation:**
  Configure these headers in `docker/nginx.conf` and host Nginx reverse proxy.

---

### SEC-MED-004: Missing Critical Database Indexes on Orders & Lookups
- **Severity:** MEDIUM (CWE-400 / Reliability & Performance)
- **Status:** VERIFIED WEAKNESS
- **Affected File:** `backend/database/migrations/2025_12_21_033034_create_ecommerecedb_table.php` (Lines 848–875)
- **Technical Explanation:**
  The `orders` table lacks indexes on:
  - `customer_id`
  - `status`
  - `payment_status`
  - `created_at`
  Every lookup in customer dashboard, admin orders list, and reports triggers a full table scan, degrading performance and increasing CPU consumption as order volume grows.
- **Recommended Remediation:**
  Add a non-destructive migration creating composite and single indexes on `(customer_id, created_at)`, `(status, created_at)`, and `(payment_status)`.

---

### SEC-LOW-001: Unpinned Wildcard Package Dependency in Composer
- **Severity:** LOW (Supply Chain Reliability)
- **Status:** VERIFIED WEAKNESS
- **Affected File:** `backend/composer.json` (Line 15)
- **Technical Explanation:**
  `"pusher/pusher-php-server": "*"` is unpinned. Any future upstream breaking update or compromised release could automatically be pulled during automated builds.
- **Recommended Remediation:**
  Pin the dependency to a specific known-good semantic version (e.g. `^7.2`).

---

### SEC-LOW-002: Unpaginated Public Product Dump Endpoint
- **Severity:** LOW (Information & Resource Exposure)
- **Status:** VERIFIED WEAKNESS
- **Affected Files:**
  - `backend/routes/api.php` (Line 15)
  - `backend/app/Http/Controllers/ProductController.php` (Line 16)
- **Technical Explanation:**
  `Route::apiResource('products', ProductController::class);` exposes `GET /api/products` which calls `Product::all()`, dumping every product record and attribute unpaginated without rate limiting or authentication.
- **Recommended Remediation:**
  Remove the legacy redundant endpoint or enforce pagination (`Product::paginate(20)`) and resource transformation.
