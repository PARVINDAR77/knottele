<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\Customer\RazorpayService;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RazorpayPaymentController extends Controller
{
    protected ?RazorpayService $razorpayService = null;

    public function __construct()
    {
        // Lazy-loaded to prevent constructor failures during DI routing resolution
    }

    protected function getRazorpayService(): RazorpayService
    {
        if (!$this->razorpayService) {
            $this->razorpayService = new RazorpayService();
        }
        return $this->razorpayService;
    }

    /**
     * Create a Razorpay Order for Checkout.
     * POST /api/create-order or /api/customer/payment/razorpay/order
     *
     * Request: { amount (paise), currency, receipt }
     * Minimum amount: 100 paise
     * Return: { order_id, amount, currency, key_id }
     */
    public function createOrder(Request $request)
    {
        try {
            if (!$request->has('amount') || !is_numeric($request->amount)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The amount field is required and must be a number.',
                    'error' => 'Validation error'
                ], 400);
            }

            // Support both paise (amount >= 100) or explicit rupees (amount_in_rupees)
            $rawAmount = (float) $request->amount;
            if ($request->has('amount_in_rupees') || ($request->has('is_rupees') && $request->is_rupees)) {
                $amountInPaise = (int) round($rawAmount * 100);
            } else {
                $amountInPaise = (int) round($rawAmount);
            }

            // Validate minimum amount: 100 paise (₹1.00)
            if ($amountInPaise < 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Amount must be at least 100 paise (₹1.00).',
                    'error' => 'Minimum amount is 100 paise'
                ], 400);
            }

            $currency = $request->input('currency', 'INR');
            $receipt = $request->input('receipt');

            $service = $this->getRazorpayService();
            $result = $service->createOrderByAmount($amountInPaise, $currency, $receipt);

            if (!$result['success']) {
                $code = $result['code'] ?? 500;
                return response()->json($result, $code);
            }

            return response()->json([
                'success' => true,
                'order_id' => $result['order_id'],
                'id' => $result['order_id'],
                'amount' => $result['amount'],
                'currency' => $result['currency'],
                'key_id' => $result['key_id'],
                'receipt' => $result['receipt'] ?? null,
            ], 200);

        } catch (\Razorpay\Api\Errors\Error $e) {
            Log::error('Razorpay API error: ' . $e->getMessage());
            $code = ($e->getCode() == 401 || str_contains(strtolower($e->getMessage()), 'auth')) ? 401 : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ], $code);
        } catch (\Throwable $e) {
            Log::error('Razorpay order creation endpoint error: ' . $e->getMessage());
            $code = str_contains(strtolower($e->getMessage()), 'credentials') ? 401 : 500;
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize payment: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], $code);
        }
    }

    /**
     * Verify Razorpay payment signature.
     * POST /api/verify-payment or /api/customer/payment/razorpay/verify
     *
     * Algorithm: HMAC-SHA256(order_id + "|" + payment_id, KEY_SECRET)
     * Return 400 on signature mismatch or missing fields.
     */
    public function verifyPayment(Request $request)
    {
        try {
            $paymentId = $request->input('razorpay_payment_id') ?: $request->input('payment_id');
            $orderId = $request->input('razorpay_order_id') ?: $request->input('order_id');
            $signature = $request->input('razorpay_signature') ?: $request->input('signature');

            if (empty($paymentId) || empty($orderId) || empty($signature)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required payment verification fields (razorpay_payment_id, razorpay_order_id, razorpay_signature).',
                    'error' => 'Missing required fields'
                ], 400);
            }

            $service = $this->getRazorpayService();
            $result = $service->verifyPayment($paymentId, $orderId, $signature);

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment signature verified successfully',
                'order_id' => $orderId,
                'payment_id' => $paymentId,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Razorpay payment verification endpoint error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Payment signature verification failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Razorpay Webhook Handler for automatic payment status update.
     * POST /api/payment/razorpay/webhook
     */
    public function handleWebhook(Request $request)
    {
        try {
            $payload = $request->getContent();
            $data = json_decode($payload, true);

            if (!$data || !isset($data['event'])) {
                return response()->json(['status' => 'ignored'], 200);
            }

            Log::info('Razorpay webhook event received: ' . $data['event']);

            if (in_array($data['event'], ['payment.captured', 'order.paid'])) {
                $paymentEntity = $data['payload']['payment']['entity'] ?? null;
                $orderEntity = $data['payload']['order']['entity'] ?? null;

                $paymentId = $paymentEntity['id'] ?? null;
                $rzpOrderId = $paymentEntity['order_id'] ?? ($orderEntity['id'] ?? null);
                $receipt = $orderEntity['receipt'] ?? null;

                $order = null;
                if ($receipt) {
                    $order = Order::where('order_number', $receipt)->first();
                }
                if (!$order && $rzpOrderId) {
                    $attempt = PaymentAttempt::where('gateway_order_id', $rzpOrderId)->first();
                    if ($attempt) {
                        $order = Order::find($attempt->order_id);
                    }
                }
                if (!$order && $paymentId) {
                    $existingPayment = Payment::where('transaction_id', $paymentId)->first();
                    if ($existingPayment) {
                        $order = Order::find($existingPayment->order_id);
                    }
                }

                if ($order) {
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => ($order->status === 'pending') ? 'confirmed' : $order->status,
                        'confirmed_at' => $order->confirmed_at ?: now(),
                    ]);

                    if ($paymentId) {
                        Payment::updateOrCreate(
                            ['order_id' => $order->id, 'transaction_id' => $paymentId],
                            [
                                'amount' => $order->grand_total,
                                'payment_gateway' => 'razorpay',
                                'status' => 'completed',
                                'paid_at' => now(),
                                'response_data' => $paymentEntity,
                            ]
                        );
                    }

                    Log::info("Order #{$order->order_number} automatically confirmed via Razorpay webhook.");
                }
            }

            return response()->json(['status' => 'success'], 200);
        } catch (\Throwable $e) {
            Log::error('Razorpay Webhook Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 200);
        }
    }
}
