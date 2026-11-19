# KNOTELLE INDIA — SECURITY TEST PLAN & REPRODUCIBLE TEST SUITE

**Target:** `knotelleindia.com` / `knottele-fullstack`  
**Purpose:** Verify existing security weaknesses safely without disrupting production, and validate fixes post-remediation.  
**Execution Safety Rule:** Safe, non-destructive, non-DoS, synthetic test accounts only.

---

## 1. TEST SUITE OVERVIEW

| Test Case ID | Target Finding | Objective | Method | Expected Vulnerable Result | Expected Secure Result |
|---|---|---|---|---|---|
| **TC-SEC-01** | **SEC-CRIT-001** | Verify Unverified Password Reset | HTTP POST to `/api/customer/reset-password` | HTTP 200 (Password changed without OTP) | HTTP 422 (Missing/invalid OTP required) |
| **TC-SEC-02** | **SEC-CRIT-002** | Verify Payment Status Bypass | HTTP POST to `/api/customer/orders` with `payment_method: "razorpay"` | HTTP 201 (Order created with `payment_status: "paid"`) | HTTP 201 (Order created with `payment_status: "pending"`) |
| **TC-SEC-03** | **SEC-CRIT-003** | Verify Price Tampering | HTTP POST to `/api/customer/orders` with `items[0][unit_price]: 1` | Server calculates total using ₹1 | Server calculates total using database price |
| **TC-SEC-04** | **SEC-CRIT-004** | Verify Anonymous Order Access (BOLA) | HTTP GET to `/api/customer/orders/1` without auth or email | HTTP 200 with full customer PII | HTTP 401 Unauthorized |
| **TC-SEC-05** | **SEC-CRIT-005** | Verify Custom Order Scraping (BOLA) | HTTP GET to `/api/customer/custom-orders/1` | HTTP 200 with custom order details | HTTP 404 or 403 Forbidden |
| **TC-SEC-06** | **SEC-HIGH-001** | Verify File Upload Restrictions | HTTP POST to `/admin/media/upload` with `.html`/`.php` | HTTP 200 (File stored with executable ext) | HTTP 422 (File must be an image) |
| **TC-SEC-07** | **SEC-HIGH-002** | Verify Token Exposure in URL | Submit admin sign-in form | Redirect to `/admin/dashboard?auth_token=...` | Redirect to `/admin/dashboard` (no query token) |
| **TC-SEC-08** | **SEC-HIGH-003** | Verify Rate Limiting | Send 20 rapid POST requests to `/admin/signin` | All 20 requests accepted | HTTP 429 Too Many Requests after 5 attempts |
| **TC-SEC-09** | **SEC-MED-001** | Verify OTP Cleartext Logging | Trigger OTP generation and inspect `laravel.log` | Plaintext OTP printed in log line | OTP redacted / only event logged |
| **TC-SEC-10** | **SEC-MED-003** | Verify Security Headers | HTTP GET `/` inspecting response headers | Missing CSP, HSTS, X-Content-Type | All defense headers present |

---

## 2. REPRODUCIBLE TEST SCRIPTS

### Script 1: Automated API Security Verification Suite (Node.js / cURL)

Save as `tests/security/test_security_posture.js`:

```javascript
/**
 * Automated Non-Destructive Security Verification Suite
 * Run with: node tests/security/test_security_posture.js [baseUrl]
 */

const https = require('https');
const http = require('http');

const BASE_URL = process.argv[2] || 'http://127.0.0.1:8095';

function request(method, path, body = null, headers = {}) {
  return new Promise((resolve, reject) => {
    const isHttps = BASE_URL.startsWith('https');
    const client = isHttps ? https : http;
    const url = new URL(path, BASE_URL);

    const options = {
      method,
      hostname: url.hostname,
      port: url.port || (isHttps ? 443 : 80),
      path: url.pathname + url.search,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...headers
      }
    };

    const req = client.request(options, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => {
        try {
          resolve({ status: res.statusCode, headers: res.headers, body: JSON.parse(data) });
        } catch (e) {
          resolve({ status: res.statusCode, headers: res.headers, body: data });
        }
      });
    });

    req.on('error', reject);
    if (body) req.write(typeof body === 'string' ? body : JSON.stringify(body));
    req.end();
  });
}

async function runTests() {
  console.log('====================================================');
  console.log(`🔍 Running Security Verification against: ${BASE_URL}`);
  console.log('====================================================\n');

  // TEST 1: Password Reset Endpoint (SEC-CRIT-001)
  try {
    const res = await request('POST', '/api/customer/reset-password', {
      email: 'security_audit_nonexistent@example.com',
      password: 'new_password_123'
    });
    console.log(`[TC-SEC-01] Password Reset Check: HTTP ${res.status}`);
    if (res.status === 404 && res.body.message && !res.body.errors?.otp) {
      console.log('  ⚠️ VULNERABLE: Endpoint checked email existence without requiring OTP parameter.');
    } else if (res.status === 422 && JSON.stringify(res.body).includes('otp')) {
      console.log('  ✅ SECURE: Endpoint rejected request due to missing OTP.');
    }
  } catch (err) {
    console.log(`[TC-SEC-01] Error: ${err.message}`);
  }

  // TEST 2: Anonymous Order Lookup (SEC-CRIT-004)
  try {
    const res = await request('GET', '/api/customer/orders/1');
    console.log(`\n[TC-SEC-04] Anonymous Order Detail Lookup: HTTP ${res.status}`);
    if (res.status === 200 && res.body.data && res.body.data.shipping_address) {
      console.log('  ⚠️ VULNERABLE: Order details and PII returned to anonymous requester.');
    } else if (res.status === 401 || res.status === 403 || res.status === 404) {
      console.log('  ✅ SECURE: Anonymous access blocked or resource protected.');
    }
  } catch (err) {
    console.log(`[TC-SEC-04] Error: ${err.message}`);
  }

  // TEST 3: Custom Order Sequential Enumeration (SEC-CRIT-005)
  try {
    const res = await request('GET', '/api/customer/custom-orders/1');
    console.log(`\n[TC-SEC-05] Custom Order Lookup by Numeric ID: HTTP ${res.status}`);
    if (res.status === 200 && res.body.data && res.body.data.customer_email) {
      console.log('  ⚠️ VULNERABLE: Custom order PII returned via sequential numeric ID.');
    } else {
      console.log('  ✅ SECURE: Numeric ID lookup rejected.');
    }
  } catch (err) {
    console.log(`[TC-SEC-05] Error: ${err.message}`);
  }

  // TEST 4: Security Headers Inspection (SEC-MED-003)
  try {
    const res = await request('GET', '/');
    console.log(`\n[TC-SEC-10] Security Headers Inspection:`);
    const headers = res.headers;
    console.log(`  - Content-Security-Policy: ${headers['content-security-policy'] ? '✅ Present' : '❌ Missing'}`);
    console.log(`  - X-Frame-Options: ${headers['x-frame-options'] ? '✅ Present' : '❌ Missing'}`);
    console.log(`  - X-Content-Type-Options: ${headers['x-content-type-options'] ? '✅ Present' : '❌ Missing'}`);
    console.log(`  - Strict-Transport-Security: ${headers['strict-transport-security'] ? '✅ Present' : '❌ Missing'}`);
  } catch (err) {
    console.log(`[TC-SEC-10] Error: ${err.message}`);
  }
}

runTests();
```

---

## 3. MANUAL TEST INSTRUCTIONS (cURL Commands)

### 1. Test Password Reset Vulnerability (SEC-CRIT-001)
```bash
curl -X POST "http://127.0.0.1:8095/api/customer/reset-password" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "test@example.com", "password": "NewSecretPassword123"}'
```
- **If Vulnerable:** Returns HTTP 200 with `"Password reset successfully!"` or HTTP 404 `"No account registered"`, without demanding an OTP code.
- **If Fixed:** Returns HTTP 422 with validation errors demanding `'otp'`.

### 2. Test Anonymous Order PII Exposure (SEC-CRIT-004)
```bash
curl -s "http://127.0.0.1:8095/api/customer/orders/1" \
  -H "Accept: application/json"
```
- **If Vulnerable:** Returns full JSON with `shipping_address`, `customer_name`, phone number, and items.
- **If Fixed:** Returns HTTP 401 Unauthorized (`"message": "Unauthorized"`).

### 3. Test Security Headers (SEC-MED-003)
```bash
curl -I "https://knotelleindia.com/"
```
- **Verify:** Look for `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, and `Strict-Transport-Security`.
