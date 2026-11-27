<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerOtp;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SmsService
{
    /**
     * Clean and format Indian mobile number to 10 digits
     */
    public static function cleanMobile(string $rawMobile): ?string
    {
        $digits = preg_replace('/\D/', '', $rawMobile);
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) === 10 && preg_match('/^[6-9]\d{9}$/', $digits)) {
            return $digits;
        }

        return null;
    }

    /**
     * Dynamically verify and prepare database tables to prevent schema exceptions
     */
    public static function ensureTablesReady(): void
    {
        try {
            // 1. Ensure customer_otps has mobile column
            if (Schema::hasTable('customer_otps')) {
                if (!Schema::hasColumn('customer_otps', 'mobile')) {
                    Schema::table('customer_otps', function ($table) {
                        $table->string('mobile', 25)->nullable()->after('id')->index();
                    });
                }
                if (Schema::hasColumn('customer_otps', 'email')) {
                    Schema::table('customer_otps', function ($table) {
                        $table->string('email')->nullable()->change();
                    });
                }
            }

            // 2. Ensure sms_logs exists and has required columns
            if (Schema::hasTable('sms_logs')) {
                Schema::table('sms_logs', function ($table) {
                    if (!Schema::hasColumn('sms_logs', 'otp_code')) {
                        $table->string('otp_code', 10)->nullable();
                    }
                    if (!Schema::hasColumn('sms_logs', 'purpose')) {
                        $table->string('purpose', 50)->default('login');
                    }
                    if (!Schema::hasColumn('sms_logs', 'http_code')) {
                        $table->integer('http_code')->nullable();
                    }
                    if (!Schema::hasColumn('sms_logs', 'duration_ms')) {
                        $table->decimal('duration_ms', 8, 2)->nullable();
                    }
                    if (!Schema::hasColumn('sms_logs', 'ip_address')) {
                        $table->string('ip_address', 45)->nullable();
                    }
                    if (!Schema::hasColumn('sms_logs', 'error_message')) {
                        $table->text('error_message')->nullable();
                    }
                    if (!Schema::hasColumn('sms_logs', 'response_payload')) {
                        $table->longText('response_payload')->nullable();
                    }
                    if (!Schema::hasColumn('sms_logs', 'verified_at')) {
                        $table->timestamp('verified_at')->nullable();
                    }
                });
            } else {
                Schema::create('sms_logs', function ($table) {
                    $table->id();
                    $table->string('to', 25)->index();
                    $table->string('from', 25)->nullable()->default('TXXTOO');
                    $table->text('message')->nullable();
                    $table->string('otp_code', 10)->nullable();
                    $table->string('purpose', 50)->default('login')->index();
                    $table->string('status', 50)->default('queued')->index();
                    $table->string('message_id', 150)->nullable()->index();
                    $table->integer('http_code')->nullable();
                    $table->decimal('duration_ms', 8, 2)->nullable();
                    $table->string('ip_address', 45)->nullable();
                    $table->text('error_message')->nullable();
                    $table->longText('response_payload')->nullable();
                    $table->json('metadata')->nullable();
                    $table->timestamp('sent_at')->nullable()->useCurrent();
                    $table->timestamp('delivered_at')->nullable();
                    $table->timestamp('verified_at')->nullable();
                    $table->timestamps();
                });
            }
        } catch (\Throwable $migErr) {
            Log::warning("Dynamic schema setup notice: " . $migErr->getMessage());
        }
    }

    /**
     * Send OTP SMS via DLT Gateway
     */
    public static function sendOtp(string $rawMobile, string $customerName = 'Customer', string $purpose = 'login', ?string $clientIp = null): array
    {
        try {
            self::ensureTablesReady();

            $cleanMobile = self::cleanMobile($rawMobile);
            if (!$cleanMobile) {
                return [
                    'success' => false,
                    'message' => 'Please enter a valid 10-digit Indian mobile number.'
                ];
            }

            // Cooldown check (60 seconds) with resilient column fallback
            try {
                $hasMobileCol = Schema::hasColumn('customer_otps', 'mobile');
                $existingOtp = $hasMobileCol
                    ? CustomerOtp::where('mobile', $cleanMobile)->first()
                    : CustomerOtp::where('email', "{$cleanMobile}@phone.knotelle.internal")->first();

                if ($existingOtp && $existingOtp->last_sent_at) {
                    $cooldownEnds = $existingOtp->last_sent_at->copy()->addSeconds(60);
                    if ($cooldownEnds->isFuture()) {
                        $remaining = (int) ceil(now()->diffInSeconds($cooldownEnds));
                        return [
                            'success' => false,
                            'message' => "Please wait {$remaining} seconds before requesting a new OTP.",
                            'cooldown' => $remaining
                        ];
                    }
                }
            } catch (\Throwable $coolErr) {
                Log::warning("Cooldown query notice: " . $coolErr->getMessage());
            }

            // Rate limit check: max 6 OTP requests in 15 minutes per phone
            try {
                $recentAttempts = SmsLog::where('to', $cleanMobile)
                    ->where('created_at', '>=', now()->subMinutes(15))
                    ->count();

                if ($recentAttempts >= 6) {
                    return [
                        'success' => false,
                        'message' => 'Too many OTP requests. Please wait 15 minutes before trying again.'
                    ];
                }
            } catch (\Throwable $rateErr) {
                Log::warning("Rate limit query notice: " . $rateErr->getMessage());
            }

            // Generate 6-digit OTP
            $otpCode = sprintf('%06d', random_int(100000, 999999));

            // Format customer name for DLT template (letters and spaces only, max 20 chars)
            $cleanName = preg_replace('/[^a-zA-Z\s]/', '', $customerName);
            $cleanName = trim(substr($cleanName, 0, 20)) ?: 'Customer';

            $dltConfig = config('services.dlt', []);
            $var3 = $dltConfig['var3'] ?? 'Team Emperor';

            // Approved TRAI DLT template text (matching entity EMPEROR / TXXTOO):
            // Dear {#var#} Your OTP for verification is {#var#}. Please enter this code to complete the process.
            // Regards {#var#}
            // TEXT2
            $finalMessage = "Dear {$cleanName} Your OTP for verification is {$otpCode}. Please enter this code to complete the process.\nRegards {$var3}\nTEXT2";
            $uri = $dltConfig['uri'] ?? 'https://allsmssolution.in/sms-panel/api/http/index.php';
            $username = $dltConfig['username'] ?? 'EMPEROR';
            $apikey = $dltConfig['apikey'] ?? '62CEF-9A446';
            $senderId = $dltConfig['sender_id'] ?? 'TXXTOO';
            $templateId = $dltConfig['template_id'] ?? '1607100000000313742';
            $entityId = $dltConfig['entity_id'] ?? '1401805350000060162';
            $route = $dltConfig['route'] ?? 'TRANS';
            $apirequest = $dltConfig['apirequest'] ?? 'Text';

            $postData = [
                'username'   => $username,
                'apikey'     => $apikey,
                'apirequest' => $apirequest,
                'sender'     => $senderId,
                'route'      => $route,
                'format'     => 'JSON',
                'message'    => $finalMessage,
                'mobile'     => $cleanMobile,
                'TemplateID' => $templateId,
                'EntityID'   => $entityId,
                'PE_ID'      => $entityId,
            ];

            // Execute cURL request to SMS gateway
            $startTime = microtime(true);
            $ch = curl_init($uri);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_FAILONERROR, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            $rawResponse = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);
            curl_close($ch);

            $isSuccess = false;
            $messageId = null;
            $statusDesc = 'Queued';

            if (!empty($rawResponse)) {
                $parsed = json_decode($rawResponse, true);
                if (is_array($parsed)) {
                    if (isset($parsed['message-id'])) {
                        $messageId = is_array($parsed['message-id']) ? implode(', ', $parsed['message-id']) : (string)$parsed['message-id'];
                    } elseif (isset($parsed['msgid'])) {
                        $messageId = (string)$parsed['msgid'];
                    }

                    $statusField = strtolower((string)($parsed['status'] ?? $parsed['status_code'] ?? $parsed['response'] ?? ''));
                    if (in_array($statusField, ['success', 'queued', 'submitted', '1', '200', 'ok', 'sent'])) {
                        $isSuccess = true;
                        $statusDesc = $parsed['message'] ?? 'OTP Sent successfully';
                    } else {
                        $statusDesc = $parsed['message'] ?? $parsed['desc'] ?? $parsed['error'] ?? 'Gateway returned an error';
                    }
                } else {
                    if (stripos($rawResponse, 'success') !== false || stripos($rawResponse, 'msgid') !== false || stripos($rawResponse, 'submitted') !== false) {
                        $isSuccess = true;
                        $statusDesc = 'OTP Sent successfully';
                    } else {
                        $statusDesc = $rawResponse;
                    }
                }
            } else {
                $statusDesc = 'SMS Gateway timed out: ' . ($curlError ?: 'No response received');
            }

            // Record SMS log for admin dashboard & tracking (non-blocking)
            try {
                SmsLog::create([
                    'to'               => $cleanMobile,
                    'from'             => $senderId,
                    'message'          => $finalMessage,
                    'otp_code'         => $otpCode,
                    'purpose'          => $purpose,
                    'status'           => $isSuccess ? 'sent' : 'failed',
                    'message_id'       => $messageId,
                    'http_code'        => $httpCode,
                    'duration_ms'      => $durationMs,
                    'ip_address'       => $clientIp,
                    'error_message'    => $isSuccess ? null : $statusDesc,
                    'response_payload' => $rawResponse,
                    'metadata'         => [
                        'recipient'   => $cleanMobile,
                        'customer'    => $cleanName,
                        'template_id' => $templateId,
                    ],
                    'sent_at'          => now(),
                ]);
            } catch (\Throwable $logErr) {
                Log::error("Failed to record SMS log: " . $logErr->getMessage());
            }

            // Store OTP hash for verification (with resilient column fallback)
            try {
                $hasMobile = Schema::hasColumn('customer_otps', 'mobile');
                if ($hasMobile) {
                    CustomerOtp::updateOrCreate(
                        ['mobile' => $cleanMobile],
                        [
                            'otp_hash'     => Hash::make($otpCode),
                            'expires_at'   => now()->addMinutes(10),
                            'attempts'     => 0,
                            'last_sent_at' => now(),
                        ]
                    );
                } else {
                    CustomerOtp::updateOrCreate(
                        ['email' => "{$cleanMobile}@phone.knotelle.internal"],
                        [
                            'otp_hash'     => Hash::make($otpCode),
                            'expires_at'   => now()->addMinutes(10),
                            'attempts'     => 0,
                            'last_sent_at' => now(),
                        ]
                    );
                }
            } catch (\Throwable $saveErr) {
                Log::error("Failed to store CustomerOtp: " . $saveErr->getMessage());
            }

            Log::info("KNOTELLE Mobile OTP generated for +91 {$cleanMobile}: " . ($isSuccess ? "Sent (MsgID: {$messageId})" : "Failed: {$statusDesc}"));

            $returnPayload = [
                'success'  => true,
                'message'  => "OTP has been sent to +91 {$cleanMobile}",
                'mobile'   => $cleanMobile,
                'cooldown' => 60,
            ];

            if (config('app.debug')) {
                $returnPayload['debug_otp'] = $otpCode;
            }

            return $returnPayload;
        } catch (\Throwable $e) {
            Log::error("SmsService::sendOtp unexpected exception: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            return [
                'success' => false,
                'message' => 'Unable to send OTP: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify Mobile OTP and Login/Register Customer
     */
    public static function verifyOtp(string $rawMobile, string $otpCode, ?string $customerName = null): array
    {
        try {
            self::ensureTablesReady();

            $cleanMobile = self::cleanMobile($rawMobile);
            if (!$cleanMobile) {
                return [
                    'success' => false,
                    'message' => 'Invalid mobile number.'
                ];
            }

            $otpRecord = null;
            try {
                if (Schema::hasColumn('customer_otps', 'mobile')) {
                    $otpRecord = CustomerOtp::where('mobile', $cleanMobile)->first();
                }
            } catch (\Throwable $e) {}

            if (!$otpRecord) {
                try {
                    $otpRecord = CustomerOtp::where('email', "{$cleanMobile}@phone.knotelle.internal")->first();
                } catch (\Throwable $e) {}
            }

            if (!$otpRecord) {
                return [
                    'success' => false,
                    'message' => 'No active OTP found for this mobile number. Please click "Resend OTP".'
                ];
            }

            if ($otpRecord->isExpired()) {
                return [
                    'success' => false,
                    'message' => 'The OTP code has expired. Please request a new one.'
                ];
            }

            if ($otpRecord->isMaxAttemptsReached(5)) {
                return [
                    'success' => false,
                    'message' => 'Too many invalid attempts. Please request a new OTP.'
                ];
            }

            if (!Hash::check(trim($otpCode), $otpRecord->otp_hash)) {
                $otpRecord->increment('attempts');
                $remainingAttempts = max(0, 5 - $otpRecord->attempts);
                return [
                    'success' => false,
                    'message' => "Invalid OTP entered. {$remainingAttempts} attempt(s) remaining."
                ];
            }

            // Valid OTP! Mark log verified & delete record
            try {
                SmsLog::where('to', $cleanMobile)
                    ->where('status', 'sent')
                    ->latest()
                    ->first()
                    ?->update([
                        'verified_at' => now(),
                        'status'      => 'verified',
                    ]);
            } catch (\Throwable $e) {
                Log::warning("Failed to update SmsLog on verify: " . $e->getMessage());
            }

            $otpRecord->delete();

            // Find or create customer
            $formattedMobile = "+91 {$cleanMobile}";
            $customer = Customer::where('mobile', $formattedMobile)
                ->orWhere('mobile', $cleanMobile)
                ->orWhere('mobile', 'LIKE', "%{$cleanMobile}")
                ->first();

            $isNewUser = false;
            if (!$customer) {
                $isNewUser = true;
                $customerData = [
                    'name'               => trim($customerName) ?: 'Customer',
                    'mobile'             => $formattedMobile,
                    'email'              => "user_{$cleanMobile}@knotelleindia.com",
                    'password'           => Hash::make(bin2hex(random_bytes(16))),
                    'status'             => 1,
                    'mobile_verified_at' => now(),
                ];
                $customer = Customer::create($customerData);
            } else {
                $customer->mobile_verified_at = now();
                if (!empty($customerName) && ($customer->name === 'Customer' || empty($customer->name))) {
                    $customer->name = trim($customerName);
                }
                $customer->save();
            }

            $token = $customer->createToken('customer_api')->plainTextToken;

            return [
                'success'     => true,
                'message'     => $isNewUser ? 'Account created successfully! Welcome to Knotelle.' : 'Welcome back to Knotelle!',
                'token'       => $token,
                'is_new_user' => $isNewUser,
                'user'        => [
                    'id'     => $customer->id,
                    'name'   => $customer->name,
                    'email'  => $customer->email,
                    'mobile' => $customer->mobile,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error("SmsService::verifyOtp unexpected exception: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            return [
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage(),
            ];
        }
    }
}
