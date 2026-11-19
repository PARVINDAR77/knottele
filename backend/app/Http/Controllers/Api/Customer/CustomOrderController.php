<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomOrderRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CustomOrderController extends Controller
{
    /**
     * Ensure the custom_order_requests table exists on the database
     */
    public static function ensureTableExists(): void
    {
        try {
            if (!Schema::hasTable('custom_order_requests')) {
                Schema::create('custom_order_requests', function (Blueprint $table) {
                    $table->id();
                    $table->string('reference_id', 50)->unique();
                    $table->unsignedBigInteger('customer_id')->nullable()->index();
                    $table->string('customer_name');
                    $table->string('customer_email')->index();
                    $table->string('customer_phone', 50)->nullable();
                    $table->string('category', 100)->default('Bouquet');
                    $table->string('selected_palette', 100)->nullable();
                    $table->json('custom_colors')->nullable();
                    $table->text('custom_color_notes')->nullable();
                    $table->string('size_preference', 100)->nullable();
                    $table->text('personalization')->nullable();
                    $table->text('design_notes')->nullable();
                    $table->string('urgency', 100)->nullable();
                    $table->string('budget_range', 100)->nullable();
                    $table->string('reference_image_url', 1000)->nullable();
                    $table->string('reference_image_name', 255)->nullable();
                    $table->string('status', 50)->default('pending')->index();
                    $table->decimal('quoted_price', 10, 2)->nullable();
                    $table->text('admin_notes')->nullable();
                    $table->timestamps();
                });
            }
        } catch (\Throwable $t) {
            Log::warning('CustomOrder ensureTableExists: ' . $t->getMessage());
        }
    }

    /**
     * Submit a new custom crochet order request
     */
    public function store(Request $request): JsonResponse
    {
        self::ensureTableExists();

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'nullable|string|max:50',
                'category' => 'required|string|max:100',
                'selected_palette' => 'nullable|string|max:100',
                'custom_color_notes' => 'nullable|string|max:1000',
                'size_preference' => 'nullable|string|max:100',
                'personalization' => 'nullable|string|max:2000',
                'design_notes' => 'nullable|string|max:4000',
                'urgency' => 'nullable|string|max:100',
                'budget_range' => 'nullable|string|max:100',
                'reference_image' => 'nullable|file|mimes:jpeg,png,jpg,webp|max:10240',
                'reference_id' => 'nullable|string|max:50',
            ]);

            $customer = auth('customer_api')->user();

            // Reference ID generation with high-entropy randomness
            $referenceId = $request->input('reference_id');
            if (!$referenceId || CustomOrderRequest::where('reference_id', $referenceId)->exists()) {
                do {
                    $referenceId = 'KNT-CUSTOM-' . strtoupper(\Illuminate\Support\Str::random(10));
                } while (CustomOrderRequest::where('reference_id', $referenceId)->exists());
            }

            // Image handling
            $imageUrl = null;
            $imageName = null;
            if ($request->hasFile('reference_image')) {
                $file = $request->file('reference_image');
                $imageName = $file->getClientOriginalName();
                $dir = public_path('uploads/custom_orders');
                if (!file_exists($dir)) {
                    mkdir($dir, 0755, true);
                }
                $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                $fileName = 'custom_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $file->move($dir, $fileName);
                $imageUrl = asset('uploads/custom_orders/' . $fileName);
            }

            // Parse custom colors if passed as JSON string or array
            $customColors = $request->input('custom_colors');
            if (is_string($customColors)) {
                $decoded = json_decode($customColors, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $customColors = $decoded;
                } else {
                    $customColors = array_filter(array_map('trim', explode(',', $customColors)));
                }
            }

            $orderRequest = CustomOrderRequest::create([
                'reference_id' => $referenceId,
                'customer_id' => $customer ? $customer->id : null,
                'customer_name' => $request->input('name'),
                'customer_email' => strtolower(trim($request->input('email'))),
                'customer_phone' => $request->input('phone'),
                'category' => $request->input('category', 'Bouquet'),
                'selected_palette' => $request->input('selected_palette'),
                'custom_colors' => is_array($customColors) ? $customColors : [],
                'custom_color_notes' => $request->input('custom_color_notes'),
                'size_preference' => $request->input('size_preference'),
                'personalization' => $request->input('personalization'),
                'design_notes' => $request->input('design_notes'),
                'urgency' => $request->input('urgency'),
                'budget_range' => $request->input('budget_range'),
                'reference_image_url' => $imageUrl,
                'reference_image_name' => $imageName,
                'status' => 'pending',
                'admin_notes' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Your custom order request has been received! Our artisan will review your design specifications.',
                'data' => $orderRequest,
                'reference_id' => $orderRequest->reference_id,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => 'Please check the form inputs.',
                'errors' => $ve->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('CustomOrderController::store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save custom order request. Please try again later.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of custom orders for the customer
     */
    public function index(Request $request): JsonResponse
    {
        self::ensureTableExists();

        try {
            $customer = auth('customer_api')->user();
            $email = trim(strtolower($request->query('email', '')));

            $query = CustomOrderRequest::query();

            if ($customer) {
                $query->where(function ($q) use ($customer) {
                    $q->where('customer_id', $customer->id)
                      ->orWhere('customer_email', strtolower($customer->email));
                });
            } elseif (!empty($email)) {
                $query->where('customer_email', $email);
            } else {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }

            $orders = $query->latest()->get();

            return response()->json([
                'success' => true,
                'data' => $orders,
            ]);
        } catch (\Exception $e) {
            Log::error('CustomOrderController::index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load custom order requests.',
            ], 500);
        }
    }

    /**
     * Look up a custom order securely by unique Reference ID
     */
    public function show(Request $request, string $reference): JsonResponse
    {
        try {
            $customer = auth('customer_api')->user();
            $email = trim(strtolower($request->query('email', '')));
            $isAdmin = auth('admin')->check() || auth()->guard('admin')->check();

            // Look up strictly by high-entropy reference_id (never sequential integer id)
            $order = CustomOrderRequest::where('reference_id', $reference)->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Custom order request not found.',
                ], 404);
            }

            // Authorization check
            if (!$isAdmin) {
                if ($customer) {
                    $orderCustEmail = strtolower($order->customer_email ?? '');
                    $isOwner = ($order->customer_id == $customer->id) || ($orderCustEmail && $orderCustEmail === strtolower($customer->email));
                    if (!$isOwner) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Forbidden. You do not have permission to view this custom order.',
                        ], 403);
                    }
                } elseif (!empty($email)) {
                    if (strtolower($order->customer_email ?? '') !== $email) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Forbidden. Email does not match custom order record.',
                        ], 403);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized. Please log in or provide order verification email.',
                    ], 401);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve custom order.',
            ], 500);
        }
    }
}
