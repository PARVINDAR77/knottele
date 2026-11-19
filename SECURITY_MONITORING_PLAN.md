# KNOTELLE INDIA — SECURITY LOGGING, MONITORING & INCIDENT RESPONSE PLAN

**Target:** `knotelleindia.com` / `knottele-fullstack`  
**Architecture:** Next.js Static Client + Laravel 12 API + Alpine Nginx + MariaDB 10.11 on Ubuntu 24.04 LTS VPS  
**Standard:** OWASP Security Logging and Monitoring Guidelines, CIS Controls v8 (Control 8: Audit Log Management)

---

## 1. SECURITY LOGGING STRATEGY

### 1.1 Mandatory Security Events to Log

```
+---------------------------------------------------------------------------------------+
|                                    EVENT CATEGORIES                                   |
+--------------------------+----------------------------+-------------------------------+
|  1. AUTHENTICATION       |  2. AUTHORIZATION & ACCESS |  3. API & INFRASTRUCTURE ABUSE|
|  - Login Success         |  - Forbidden Admin Access  |  - Rate Limit Exceeded (429)  |
|  - Login Failure         |  - BOLA / IDOR Mismatches  |  - Suspicious File Uploads    |
|  - Password Reset Request|  - Repeated 403 Forbidden  |  - Webhook Signature Failures |
|  - Password Reset Success|  - Cross-Account Attempts  |  - SQL / Parameter Anomalies  |
|  - OTP Verification Fail |  - Privilege Escalation    |  - High-Volume Bot Traffic    |
+--------------------------+----------------------------+-------------------------------+
```

### 1.2 Prohibited Logging Items (Data Minimization)
Under no circumstances should the application log:
- Plaintext passwords or password reset tokens.
- Plaintext OTP codes (Registration, Login, or Reset OTPs).
- Full credit card, CVV, or UPI PIN numbers.
- Secret API credentials (Razorpay Key Secret, Sanctum Bearer tokens, Database passwords).
- Full customer identity documents or unredacted government IDs.

### 1.3 Structured JSON Security Log Schema
All security events must be formatted as structured JSON:

```json
{
  "timestamp": "2026-11-19T14:45:00.123Z",
  "environment": "production",
  "app": "knotelle-backend",
  "event_type": "AUTH_LOGIN_FAILED",
  "severity": "WARNING",
  "actor": {
    "type": "guest",
    "ip": "203.0.113.45",
    "user_agent": "Mozilla/5.0 ...",
    "email": "victim@example.com"
  },
  "resource": {
    "method": "POST",
    "route": "/admin/signin",
    "target_id": null
  },
  "details": {
    "reason": "Invalid credentials",
    "attempt_count": 3
  }
}
```

---

## 2. REAL-TIME ALERTING THRESHOLDS

| Alert Name | Detection Condition | Threshold | Severity | Immediate Action |
|---|---|---|---|---|
| **Admin Brute-Force Alert** | Failed login attempts against `/admin/signin` | >= 5 failures in 2 minutes | **CRITICAL** | Temporary IP block (15 min) via fail2ban / notify admin via Telegram or Email |
| **Customer Credential Stuffing** | Failed logins across multiple accounts | >= 20 failures in 5 minutes | **HIGH** | Trigger progressive delays + IP rate limit throttle |
| **BOLA / IDOR Probe Pattern** | 403 Forbidden responses on `/api/customer/orders/*` | >= 5 forbidden hits in 1 minute | **HIGH** | Flag actor IP; log incident; review access token |
| **High-Volume OTP Abuse** | OTP generation requests from same IP / email | >= 5 requests in 5 minutes | **MEDIUM** | Enforce 1-minute cooldown + block IP for 30 minutes |
| **Suspicious File Upload Attempt** | File upload rejected due to disallowed extension | >= 1 attempt | **HIGH** | Log IP and filename; alert security engineer |
| **Payment Signature Failure** | HMAC-SHA256 mismatch on Razorpay verification | >= 3 failures in 10 minutes | **CRITICAL** | Quarantine related order; inspect payload for tampering |
| **Server Resource Saturation** | CPU > 85% or RAM > 90% sustained | > 5 continuous minutes | **HIGH** | Container resource alert; restart PHP-FPM worker pool |

---

## 3. INCIDENT RESPONSE PLAN (8-STAGE WORKFLOW)

```
 [1. DETECTION] ---> [2. TRIAGE] ---> [3. CONTAINMENT] ---> [4. INVESTIGATION]
        |
        v
 [8. POST-INCIDENT] <--- [7. EVIDENCE PRESERVATION] <--- [6. RECOVERY] <--- [5. REMEDIATION]
```

### Stage 1: Detection
- Alerts triggered via email, Telegram webhook, or log monitors.
- Automated anomaly flags (e.g. rate limit bursts, database connection spikes).

### Stage 2: Triage & Classification
- Verify if the alert is a legitimate threat or a false positive.
- Classify severity: **P0 (Critical Outage/Breach)**, **P1 (Active Exploitation Attempt)**, **P2 (Suspicious Traffic)**.

### Stage 3: Immediate Containment
- **IP Quarantine:** Add offending IP to UFW firewall or Nginx deny table:
  ```bash
  sudo ufw insert 1 deny from <ATTACKER_IP> to any
  ```
- **Session Revocation:** Revoke compromised Sanctum tokens immediately:
  ```bash
  php artisan tinker --execute="App\Models\Admin::find(1)->tokens()->delete();"
  ```
- **Service Isolation:** If necessary, place the website in maintenance mode:
  ```bash
  php artisan down --secret="emergency-access-key"
  ```

### Stage 4: Deep Investigation & Root Cause Analysis
- Correlate Nginx access logs (`/var/log/nginx/access.log`), container logs (`docker logs knotelle-app`), and Laravel logs (`storage/logs/laravel.log`).
- Identify the exact attack vector, parameters used, and scope of data accessed.

### Stage 5: Remediation
- Develop and safely deploy a targeted hotfix for the exploited vulnerability.
- Execute automated regression test suites to guarantee zero side effects.

### Stage 6: Recovery & Safe Restoration
- Lift maintenance mode.
- Verify system health, response latencies, and business workflows.
- Monitor active connections for recurrence.

### Stage 7: Evidence Preservation
- Archive raw log files, packet captures, and attack artifacts securely with cryptographic checksums (`sha256sum`).
- Document chronological timeline of events.

### Stage 8: Post-Incident Review & Hardening
- Conduct blameless retrospective.
- Update WAF rules, rate limits, and unit tests to prevent future recurrence.
