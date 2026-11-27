<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerOtp;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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
     * Send OTP SMS via DLT Gateway
     */
    public static function sendOtp(string $rawMobile, string $customerName = 'Customer', string $purpose = 'login', ?string $clientIp = null): array
    {
        $cleanMobile = self::cleanMobile($rawMobile);
        if (!$cleanMobile) {
            return [
                'success' => false,
                'message' => 'Please enter a valid 10-digit Indian mobile number.'
            ];
        }

        // Cooldown check (60 seconds)
        $existingOtp = CustomerOtp::where('mobile', $cleanMobile)->first();
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

        // Rate limit: max 6 OTP requests in 15 minutes per phone
        $recentAttempts = SmsLog::where('to', $cleanMobile)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        if ($recentAttempts >= 6) {
            return [
                'success' => false,
                'message' => 'Too many OTP requests. Please wait 15 minutes before trying again.'
            ];
        }

        // Generate 6-digit OTP
        $otpCode = sprintf('%06d', random_int(100000, 999999));

        // Format customer name for DLT template (letters and spaces only, max 20 chars)
        $cleanName = preg_replace('/[^a-zA-Z\s]/', '', $customerName);
        $cleanName = trim(substr($cleanName, 0, 20)) ?: 'Customer';

        // Approved TRAI DLT template text:
        // Dear {#var#} Your OTP for verification is {#var#}. Please enter this code to complete the process.
        // Regards {#var#}
        // TEXT2
        $finalMessage = "Dear {$cleanName} Your OTP for verification is {$otpCode}. Please enter this code to complete the process.\nRegards Knotelle\nTEXT2";

        $dltConfig = config('services.dlt', []);
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

        // Record SMS log for admin dashboard & tracking
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

        // Store OTP hash for verification
        CustomerOtp::updateOrCreate(
            ['mobile' => $cleanMobile],
            [
                'otp_hash'     => Hash::make($otpCode),
                'expires_at'   => now()->addMinutes(10),
                'attempts'     => 0,
                'last_sent_at' => now(),
            ]
        );

        Log::info("KNOTELLE Mobile OTP generated for +91 {$cleanMobile}: " . ($isSuccess ? "Sent (MsgID: {$messageId})" : "Failed: {$statusDesc}"));

        return [
            'success'  => true,
            'message'  => "OTP has been sent to +91 {$cleanMobile}",
            'mobile'   => $cleanMobile,
            'cooldown' => 60,
        ];
    }

    /**
     * Verify Mobile OTP and Login/Register Customer
     */
    public static function verifyOtp(string $rawMobile, string $otpCode, ?string $customerName = null): array
    {
        $cleanMobile = self::cleanMobile($rawMobile);
        if (!$cleanMobile) {
            return [
                'success' => false,
                'message' => 'Invalid mobile number.'
            ];
        }

        $otpRecord = CustomerOtp::where('mobile', $cleanMobile)->first();
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
            $customer = Customer::create([
                'name'               => trim($customerName) ?: 'Customer',
                'mobile'             => $formattedMobile,
                'email'              => null,
                'password'           => null,
                'status'             => 1,
                'mobile_verified_at' => now(),
            ]);
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
    }
}
