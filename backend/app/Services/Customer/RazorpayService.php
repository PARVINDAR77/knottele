<?php

namespace App\Services\Customer;

use Razorpay\Api\Api;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RazorpayService
{
    protected $razorpay;
    protected $keyId;
    protected $keySecret;

    public function __construct()
    {
        $this->keyId = config('services.razorpay.key_id') ?: env('RAZORPAY_KEY_ID');
        $this->keySecret = config('services.razorpay.key_secret') ?: env('RAZORPAY_KEY_SECRET');

        if (empty($this->keyId) || empty($this->keySecret)) {
            try {
                $this->keyId = $this->keyId ?: \App\Helpers\SettingsHelper::get('razorpay_key_id');
                $this->keySecret = $this->keySecret ?: \App\Helpers\SettingsHelper::get('razorpay_key_secret');
            } catch (\Throwable $e) {
                // Ignore settings helper exception if DB unavailable
            }
        }

        if (empty($this->keyId) || empty($this->keySecret)) {
            throw new \Exception('Razorpay credentials not configured');
        }

        $this->razorpay = new Api($this->keyId, $this->keySecret);
    }

    public function getKeyId(): string
    {
        return $this->keyId;
    }

    public function getKeySecret(): string
    {
        return $this->keySecret;
    }

    /**
     * Create Razorpay order
     */
    public function createOrder($order, $amount)
    {
        DB::beginTransaction();

        try {
            Log::info('Creating Razorpay order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'amount' => $amount
            ]);

            // Create Razorpay order
            $razorpayOrder = $this->razorpay->order->create([
                'amount' => $amount * 100, // Convert to paise
                'currency' => 'INR',
                'receipt' => $order->order_number,
                'payment_capture' => 1, // Auto capture
                'notes' => [
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'order_number' => $order->order_number
                ]
            ]);

            // Log payment attempt
            PaymentAttempt::create([
                'order_id' => $order->id,
                'gateway' => 'razorpay',
                'gateway_order_id' => $razorpayOrder->id,
                'amount' => $amount,
                'currency' => 'INR',
                'status' => 'initiated',
                'request_data' => $razorpayOrder->toArray(),
            ]);

            DB::commit();

            Log::info('Razorpay order created successfully', [
                'razorpay_order_id' => $razorpayOrder->id,
                'order_id' => $order->id
            ]);

            return [
                'success' => true,
                'order_id' => $razorpayOrder->id,
                'amount' => $razorpayOrder->amount,
                'currency' => $razorpayOrder->currency,
                'key_id' => $this->keyId
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Razorpay Order Creation Failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create payment order. Please try again.'
            ];
        }
    }

    /**
     * Create Razorpay order by amount in paise
     */
    public function createOrderByAmount(int $amountInPaise, ?string $currency = 'INR', ?string $receipt = null): array
    {
        if ($amountInPaise < 100) {
            return [
                'success' => false,
                'message' => 'Amount must be at least 100 paise (₹1.00).',
                'code' => 400,
            ];
        }

        try {
            Log::info('Creating Razorpay order by amount', [
                'amount' => $amountInPaise,
                'currency' => $currency ?: 'INR',
            ]);

            $orderData = [
                'amount' => $amountInPaise, // in paise
                'currency' => $currency ?: 'INR',
                'payment_capture' => 1,
            ];

            if ($receipt) {
                $orderData['receipt'] = substr($receipt, 0, 40);
            } else {
                $orderData['receipt'] = 'rcpt_' . time() . '_' . substr(md5(uniqid()), 0, 6);
            }

            $order = $this->razorpay->order->create($orderData);

            $orderId = is_object($order) ? ($order->id ?? ($order['id'] ?? null)) : ($order['id'] ?? null);
            $orderAmount = is_object($order) ? ($order->amount ?? ($order['amount'] ?? $amountInPaise)) : ($order['amount'] ?? $amountInPaise);
            $orderCurrency = is_object($order) ? ($order->currency ?? ($order['currency'] ?? 'INR')) : ($order['currency'] ?? 'INR');
            $orderReceipt = is_object($order) ? ($order->receipt ?? ($order['receipt'] ?? $orderData['receipt'])) : ($order['receipt'] ?? $orderData['receipt']);

            return [
                'success' => true,
                'order_id' => $orderId,
                'key_id' => $this->keyId,
                'amount' => $orderAmount,
                'currency' => $orderCurrency,
                'receipt' => $orderReceipt,
            ];

        } catch (\Razorpay\Api\Errors\Error $e) {
            Log::error('Razorpay API error creating order: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'code' => $e->getCode() == 401 ? 401 : 500,
            ];
        } catch (\Throwable $e) {
            Log::error('Razorpay order creation exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to initialize Razorpay order: ' . $e->getMessage(),
                'code' => 500,
            ];
        }
    }


    /**
     * Verify payment signature strictly using HMAC-SHA256
     */
    public function verifyPayment($paymentId, $orderId, $signature): array
    {
        if (empty($paymentId) || empty($orderId) || empty($signature)) {
            return [
                'success' => false,
                'message' => 'Missing required payment verification fields.',
                'code' => 400,
            ];
        }

        try {
            Log::info('Verifying Razorpay payment signature', [
                'payment_id' => $paymentId,
                'order_id' => $orderId
            ]);

            // Algorithm: HMAC-SHA256(order_id + "|" + payment_id, KEY_SECRET)
            $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->keySecret);

            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('Razorpay signature mismatch', [
                    'order_id' => $orderId,
                    'payment_id' => $paymentId
                ]);
                return [
                    'success' => false,
                    'message' => 'Payment signature verification failed. Signatures do not match.',
                    'code' => 400,
                ];
            }

            // Optional: Fetch payment details from Razorpay to verify capture status
            $payment = null;
            try {
                $paymentObj = $this->razorpay->payment->fetch($paymentId);
                $payment = $paymentObj->toArray();
            } catch (\Throwable $fetchErr) {
                Log::info('Razorpay payment fetch notice: ' . $fetchErr->getMessage());
            }

            Log::info('Razorpay payment signature verified successfully', [
                'payment_id' => $paymentId,
                'order_id' => $orderId,
            ]);

            return [
                'success' => true,
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'payment' => $payment,
                'message' => 'Payment verified successfully',
            ];

        } catch (\Throwable $e) {
            Log::error('Razorpay payment verification exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment verification error: ' . $e->getMessage(),
                'code' => 400,
            ];
        }
    }

    /**
     * Process payment after verification
     */
    public function processPayment(Order $order, array $paymentData)
    {
        DB::beginTransaction();

        try {
            // Verify payment first
            $verification = $this->verifyPayment(
                $paymentData['razorpay_payment_id'],
                $paymentData['razorpay_order_id'],
                $paymentData['razorpay_signature']
            );

            if (!$verification['success']) {
                throw new \Exception($verification['message']);
            }

            // Get payment details
            $razorpayPayment = $this->razorpay->payment->fetch($paymentData['razorpay_payment_id']);

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method' => 'razorpay',
                'amount' => $order->grand_total,
                'currency' => 'INR',
                'status' => $razorpayPayment->status,
                'transaction_id' => $razorpayPayment->id,
                'gateway_order_id' => $paymentData['razorpay_order_id'],
                'payment_details' => [
                    'method' => $razorpayPayment->method ?? 'card',
                    'card_type' => $razorpayPayment->card->type ?? null,
                    'card_network' => $razorpayPayment->card->network ?? null,
                    'bank' => $razorpayPayment->bank ?? null,
                    'wallet' => $razorpayPayment->wallet ?? null,
                    'vpa' => $razorpayPayment->vpa ?? null,
                ],
                'gateway_response' => $razorpayPayment->toArray(),
            ]);

            // Update payment attempt
            PaymentAttempt::where('gateway_order_id', $paymentData['razorpay_order_id'])
                ->update([
                    'status' => $razorpayPayment->status,
                    'gateway_payment_id' => $razorpayPayment->id,
                    'response_data' => $razorpayPayment->toArray(),
                    'updated_at' => now()
                ]);

            // Update order payment status
            $order->update([
                'payment_status' => $razorpayPayment->status === 'captured' ? 'paid' : 'failed',
                'status' => $razorpayPayment->status === 'captured' ? 'confirmed' : 'failed'
            ]);

            DB::commit();

            Log::info('Razorpay payment processed successfully', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'razorpay_payment_id' => $razorpayPayment->id
            ]);

            return [
                'success' => true,
                'payment' => $payment,
                'order' => $order
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Razorpay Payment Processing Failed', [
                'order_id' => $order->id,
                'payment_data' => $paymentData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Refund payment
     */
    public function refundPayment($paymentId, $amount, $notes = [])
    {
        try {
            Log::info('Initiating Razorpay refund', [
                'payment_id' => $paymentId,
                'amount' => $amount
            ]);

            $refund = $this->razorpay->payment->fetch($paymentId)->refund([
                'amount' => $amount * 100,
                'speed' => 'normal',
                'notes' => array_merge($notes, [
                    'refund_initiated_at' => now()->toISOString()
                ])
            ]);

            Log::info('Razorpay refund successful', [
                'payment_id' => $paymentId,
                'refund_id' => $refund->id,
                'amount' => $amount
            ]);

            return [
                'success' => true,
                'refund' => $refund->toArray(),
                'message' => 'Refund initiated successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Razorpay Refund Failed', [
                'payment_id' => $paymentId,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Refund failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check payment status
     */
    public function checkPaymentStatus($paymentId)
    {
        try {
            $payment = $this->razorpay->payment->fetch($paymentId);

            return [
                'success' => true,
                'status' => $payment->status,
                'payment' => $payment->toArray()
            ];
        } catch (\Exception $e) {
            Log::error('Razorpay Status Check Failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function fetchOrder(string $razorpayOrderId)
    {
        return $this->razorpay->order->fetch($razorpayOrderId);
    }

}
