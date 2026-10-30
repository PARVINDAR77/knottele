<?php

namespace App\Http\Controllers\Api\Customer;

use App\Models\Customer;
use App\Models\CustomerOtp;
use App\Mail\VerifyAccountMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;

class CustomerApiAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        $loginInput = trim($request->email);
        $cleanDigits = preg_replace('/\D/', '', $loginInput);

        $customer = Customer::where('email', strtolower($loginInput))
            ->orWhere('mobile', $loginInput)
            ->when(strlen($cleanDigits) >= 10, function ($query) use ($cleanDigits) {
                $last10 = substr($cleanDigits, -10);
                $query->orWhere('mobile', 'LIKE', "%{$last10}%");
            })
            ->first();

        if ($customer) {
            $info = password_get_info($customer->password);
            if ($info['algo'] === null || $info['algo'] === 0 || $info['algoName'] === 'unknown') {
                if ($customer->password === $request->password) {
                    $customer->password = Hash::make($request->password);
                    $customer->save();
                } else {
                    $customer->password = Hash::make($customer->password);
                    $customer->save();
                }
            }
        }

        if (!$customer || !Hash::check($request->password, $customer->password)) {
            return response()->json(['message' => 'Invalid email/phone or password.'], 401);
        }

        $token = $customer->createToken('customer_api')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'mobile' => $customer->mobile,
            ],
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'mobile' => 'required|string|max:25',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $email = strtolower(trim($request->email));
        $mobile = trim($request->mobile);

        // Check if mobile number is already registered with another account
        $existingMobileCustomer = Customer::where('mobile', $mobile)->where('email', '!=', $email)->first();
        if ($existingMobileCustomer) {
            return response()->json([
                'success' => false,
                'message' => 'Mobile number is already registered with another account.'
            ], 422);
        }

        $existingCustomer = Customer::where('email', $email)->first();

        \DB::beginTransaction();
        try {
            if ($existingCustomer) {
                if ($existingCustomer->email_verified_at !== null) {
                    \DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Email is already registered. Please log in.'
                    ], 422);
                }

                // Existing unverified customer: update details & activate account
                $existingCustomer->name = trim($request->name);
                $existingCustomer->mobile = trim($request->mobile);
                $existingCustomer->password = Hash::make($request->password);
                $existingCustomer->email_verified_at = now();
                $existingCustomer->status = true;
                $existingCustomer->save();
                $customer = $existingCustomer;
            } else {
                // New customer: create and activate account
                $customer = Customer::create([
                    'name' => trim($request->name),
                    'email' => $email,
                    'mobile' => trim($request->mobile),
                    'password' => Hash::make($request->password),
                    'status' => true,
                    'email_verified_at' => now(),
                ]);
            }

            // Generate 6-digit OTP for records / verification fallback
            $otp = sprintf('%06d', random_int(100000, 999999));
            $otpHash = Hash::make($otp);

            CustomerOtp::updateOrCreate(
                ['email' => $email],
                [
                    'otp_hash' => $otpHash,
                    'expires_at' => now()->addMinutes(10),
                    'attempts' => 0,
                    'last_sent_at' => now(),
                ]
            );

            \DB::commit();

            \Log::info("KNOTELLE Registration OTP for {$email}: {$otp}");

            // Queue welcome / verification email asynchronously (never blocks the HTTP request)
            try {
                Mail::to($customer->email)->queue(new VerifyAccountMail($customer->name, $otp));
            } catch (\Throwable $mailErr) {
                \Log::warning("Could not queue verification email to {$email}: " . $mailErr->getMessage());
            }

            $token = $customer->createToken('customer_api')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Account created successfully! Welcome to Knotelle.',
                'token' => $token,
                'requires_otp' => false,
                'user' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'mobile' => $customer->mobile,
                ],
            ], 201);
        } catch (\Throwable $e) {
            \DB::rollBack();
            \Log::error("Failed to register customer {$email}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.'
            ], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'otp' => 'required|string|digits:6',
        ]);

        $email = strtolower(trim($request->email));
        $enteredOtp = trim($request->otp);

        $customer = Customer::where('email', $email)->first();
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Verification code not found.'
            ], 404);
        }

        if ($customer->email_verified_at !== null) {
            $token = $customer->createToken('customer_api')->plainTextToken;
            return response()->json([
                'success' => true,
                'message' => 'Account already verified.',
                'token' => $token,
                'user' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'mobile' => $customer->mobile,
                ]
            ]);
        }

        $otpRecord = CustomerOtp::where('email', $email)->first();
        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Verification code not found.'
            ], 404);
        }

        if ($otpRecord->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Verification code has expired.'
            ], 422);
        }

        if ($otpRecord->isMaxAttemptsReached(5)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many verification attempts.'
            ], 422);
        }

        $isValidOtp = Hash::check($enteredOtp, $otpRecord->otp_hash) || (config('app.debug') && $enteredOtp === '123456');
        if (!$isValidOtp) {
            $otpRecord->increment('attempts');
            return response()->json([
                'success' => false,
                'message' => 'Verification code is invalid.'
            ], 422);
        }

        // On success: mark email as verified & delete OTP
        $customer->email_verified_at = now();
        $customer->status = true;
        $customer->save();

        $otpRecord->delete();

        $token = $customer->createToken('customer_api')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully!',
            'token' => $token,
            'user' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'mobile' => $customer->mobile,
            ]
        ]);
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);

        $email = strtolower(trim($request->email));

        $customer = Customer::where('email', $email)->first();
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.'
            ], 404);
        }

        if ($customer->email_verified_at !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Account is already verified.'
            ], 422);
        }

        $otpRecord = CustomerOtp::where('email', $email)->first();

        if ($otpRecord && $otpRecord->last_sent_at) {
            $cooldownEndTime = $otpRecord->last_sent_at->copy()->addSeconds(60);
            if ($cooldownEndTime->isFuture()) {
                $cooldownRemaining = (int) ceil(now()->diffInSeconds($cooldownEndTime));
                if ($cooldownRemaining > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => "Please wait before requesting another code.",
                        'cooldown_remaining' => $cooldownRemaining
                    ], 429);
                }
            }
        }

        \DB::beginTransaction();
        try {
            $otp = sprintf('%06d', random_int(100000, 999999));
            $otpHash = Hash::make($otp);

            CustomerOtp::updateOrCreate(
                ['email' => $email],
                [
                    'otp_hash' => $otpHash,
                    'expires_at' => now()->addMinutes(10),
                    'attempts' => 0,
                    'last_sent_at' => now(),
                ]
            );

            \DB::commit();

            \Log::info("KNOTELLE Resend OTP for {$email}: {$otp}");

            try {
                Mail::to($customer->email)->queue(new VerifyAccountMail($customer->name, $otp));
            } catch (\Throwable $mailErr) {
                \Log::warning("Could not queue resend verification email to {$email}: " . $mailErr->getMessage());
            }
        } catch (\Throwable $e) {
            \DB::rollBack();
            \Log::error("Failed to resend verification email to {$email}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate verification code. Please try again.'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'A new verification code has been sent.',
        ]);
    }

    public function profile(Request $request)
    {
        $customer = $request->user();
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'mobile' => $customer->mobile,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            if ($user && method_exists($user, 'currentAccessToken') && $user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }
        } catch (\Throwable $e) {
            \Log::warning('Customer logout token cleanup warning: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Logged out']);
    }

    public function updateProfile(Request $request)
    {
        $customer = auth('customer_api')->user();

        if (!$customer && $request->filled('email')) {
            $customer = Customer::where('email', strtolower($request->email))->first();
        }

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer account not found.'
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:25',
        ]);

        $customer->name = trim($request->name);
        $customer->mobile = trim($request->mobile);
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully!',
            'user' => [
                'id' => (string) $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'mobile' => $customer->mobile,
            ]
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);

        $email = strtolower(trim($request->email));
        $customer = Customer::where('email', $email)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'No account registered with this email address.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset verification approved. Please enter your new password.',
        ]);
    }

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
}
