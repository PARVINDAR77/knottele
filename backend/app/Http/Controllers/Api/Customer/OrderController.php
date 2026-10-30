<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Validate cart items and return fresh prices and tax rates
     */
    public function validateCart(Request $request)
    {
        try {
            $items = $request->items ?? [];
            $validatedItems = [];
            
            foreach ($items as $item) {
                if (isset($item['variant']) && isset($item['variant']['id'])) {
                    $variant = \App\Models\ProductVariant::with('product.taxClass')->find($item['variant']['id']);
                    if ($variant && $variant->product) {
                        $item['variant']['price'] = (float) $variant->price;
                        $item['variant']['compare_price'] = $variant->compare_price ? (float) $variant->compare_price : null;
                        $item['variant']['stock_quantity'] = $variant->stock_quantity;
                        $item['tax_rate'] = $variant->product->taxClass ? (float) $variant->product->taxClass->total_rate : 0;
                        $validatedItems[] = $item;
                    }
                }
            }
            
            return $this->apiResponse(true, $validatedItems, 'Cart validated successfully');
        } catch (\Exception $e) {
            Log::error('Cart validate error: ' . $e->getMessage());
            return $this->apiResponse(false, null, 'Failed to validate cart', 500);
        }
    }

    /**
     * Get a list of orders for the authenticated customer or email query.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $customer = auth('customer_api')->user();
            $email = trim($request->query('email') ?? '');

            if (!$customer && empty($email)) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $query = Order::with(['items.variant.product', 'items.variant.images'])
                ->orderBy('created_at', 'desc');

            if ($customer) {
                $customerId = $customer->id;
                $customerEmail = strtolower(trim($customer->email));
                $query->where(function ($q) use ($customerId, $customerEmail) {
                    $q->where('customer_id', $customerId);
                    if (!empty($customerEmail)) {
                        $q->orWhere('shipping_address->email', $customerEmail);
                    }
                });
            } else {
                $cleanEmail = strtolower($email);
                $query->where('shipping_address->email', $cleanEmail);
            }

            $orders = $query->get();

            $formattedOrders = $orders->map(fn($order) => $this->formatOrder($order));

            return response()->json([
                'success' => true,
                'data' => $formattedOrders
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching customer orders: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders.'
            ], 500);
        }
    }

    /**
     * Get single order details for the authenticated customer.
     *
     * @param \Illuminate\Http\Request $request
     * @param string|int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $customer = auth('customer_api')->user();
            $email = trim($request->query('email') ?? '');

            $order = Order::with(['items.variant.product', 'items.variant.images'])
                ->where(function ($q) use ($id) {
                    $q->where('id', $id)->orWhere('order_number', $id);
                })
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.'
                ], 404);
            }

            // Security check: Order MUST belong to the authenticated customer if customer is logged in
            if ($customer) {
                $orderCustomerEmail = strtolower($order->shipping_address['email'] ?? '');
                $authCustomerEmail = strtolower($customer->email);
                $isOwner = ($order->customer_id == $customer->id) || ($orderCustomerEmail && $orderCustomerEmail === $authCustomerEmail);

                if (!$isOwner) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Forbidden. You cannot view orders belonging to another account.'
                    ], 403);
                }
            } elseif (!empty($email)) {
                $orderCustomerEmail = strtolower($order->shipping_address['email'] ?? '');
                if ($orderCustomerEmail !== strtolower($email)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Forbidden.'
                    ], 403);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatOrder($order)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching order detail: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order details.'
            ], 500);
        }
    }

    /**
     * Print or Download Customer Invoice.
     * Uses the exact boutique invoice layout from the admin panel.
     *
     * @param \Illuminate\Http\Request $request
     * @param string|int $id
     * @param string|null $filename
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function printInvoice(Request $request, $id, $filename = null)
    {
        try {
            $cleanId = str_replace('ord-', '', $id);

            $order = Order::with(['customer', 'items.product', 'items.variant.product', 'items.variant.images'])
                ->where(function ($q) use ($id, $cleanId) {
                    $q->where('id', $id)
                      ->orWhere('id', $cleanId)
                      ->orWhere('order_number', $id)
                      ->orWhere('order_number', $cleanId);
                })
                ->first();

            if (!$order) {
                abort(404, 'Order not found.');
            }

            // Security check: If authenticated customer, verify ownership
            $customer = auth('customer_api')->user() ?? auth('customer')->user() ?? auth()->user();
            $email = trim($request->query('email') ?? '');

            if ($customer) {
                $orderCustomerEmail = strtolower($order->shipping_address['email'] ?? ($order->customer->email ?? ''));
                $authCustomerEmail = strtolower($customer->email ?? '');
                $isOwner = ($order->customer_id == $customer->id) || (!empty($orderCustomerEmail) && !empty($authCustomerEmail) && $orderCustomerEmail === $authCustomerEmail);
                $isAdmin = auth('admin')->check() || auth()->guard('admin')->check();

                if (!$isOwner && !$isAdmin) {
                    abort(403, 'Unauthorized access to this order invoice.');
                }
            } elseif (!empty($email)) {
                $orderCustomerEmail = strtolower($order->shipping_address['email'] ?? ($order->customer->email ?? ''));
                if (!empty($orderCustomerEmail) && $orderCustomerEmail !== strtolower($email)) {
                    abort(403, 'Unauthorized access to this order invoice.');
                }
            }

            $order->loadMissing(['customer', 'items', 'items.product', 'items.variant']);

            // Set clean PDF filename: e.g. Invoice-KN6AB24D3A8A49E.pdf
            $pdfFilename = $filename ?: ('Invoice-' . ($order->order_number ?: sprintf('%04d', $order->id)) . '.pdf');

            return view('admin.orders.invoice', compact('order', 'pdfFilename'));
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error rendering customer invoice: ' . $e->getMessage());
            abort(500, 'Unable to generate order invoice.');
        }
    }

    /**
     * Store a newly created order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'nullable',
            'items.*.product_id' => 'nullable',
            'items.*.product_code' => 'nullable',
            'items.*.name' => 'nullable|string',
            'items.*.unit_price' => 'nullable|numeric',
            'items.*.quantity' => 'required|integer|min:1',
            
            'shipping_address' => 'required|array',
            'shipping_address.name' => 'required|string|max:255',
            'shipping_address.email' => 'required|email|max:255',
            'shipping_address.phone' => 'required|string|max:20',
            'shipping_address.address_line_1' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.pin_code' => 'required|string',

            'payment_method' => 'required|string|in:razorpay,online,upi,card,netbanking,cod',
            'offer_code' => 'nullable|string',
            'discount_total' => 'nullable|numeric',
            'discount_amount' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'shipping_total' => 'nullable|numeric',
            'shipping' => 'nullable|numeric',
            'grand_total' => 'nullable|numeric',
            'total' => 'nullable|numeric',
        ]);

        try {
            DB::beginTransaction();

            // 1. Calculate totals securely
            $subtotal = 0;
            $totalTaxAmount = 0;
            $orderItemsData = [];

            foreach ($request->items as $item) {
                $variant = null;
                $itemName = trim($item['name'] ?? '');
                
                if (!empty($item['variant_id']) && is_numeric($item['variant_id'])) {
                    $foundVariant = ProductVariant::with('product.taxClass')->find($item['variant_id']);
                    if ($foundVariant && ($foundVariant->product && (!empty($itemName) ? (strcasecmp(trim($foundVariant->product->name), $itemName) === 0) : true))) {
                        $variant = $foundVariant;
                    }
                }

                if (!$variant && !empty($item['product_id'])) {
                    $code = $item['product_id'];
                    $product = \App\Models\Product::where('product_code', $code)->orWhere('id', $code)->orWhere('slug', $code)->first();
                    if ($product) {
                        $variant = ProductVariant::with('product.taxClass')
                            ->where('product_id', $product->id)
                            ->first();
                    }
                }

                if (!$variant && !empty($itemName)) {
                    $product = \App\Models\Product::where('name', $itemName)
                        ->orWhere('name', 'LIKE', '%' . $itemName . '%')
                        ->first();
                    if ($product) {
                        $variant = ProductVariant::with('product.taxClass')
                            ->where('product_id', $product->id)
                            ->first();
                    }
                }

                if (!$variant) {
                    $variant = ProductVariant::with('product.taxClass')->first();
                }

                $quantity = (int) $item['quantity'];
                $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : ($variant ? (float) $variant->price : 0);
                $productName = !empty($itemName) ? $itemName : ($variant->product->name ?? 'Handmade Product');

                $total = $unitPrice * $quantity;
                $subtotal += $total;

                $taxAmount = 0;
                if ($variant && $variant->product && $variant->product->taxClass) {
                    $taxAmount = ($total * (float) $variant->product->taxClass->total_rate) / 100;
                    $totalTaxAmount += $taxAmount;
                }

                $orderItemsData[] = [
                    'product_variant_id' => $variant ? $variant->id : 1,
                    'product_name' => $productName,
                    'sku' => $variant ? $variant->sku : 'KNT-ITEM',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $total,
                ];
            }

            // Check for offer code
            $discountTotal = 0;
            $appliedOfferId = null;
            if ($request->filled('offer_code')) {
                $code = trim($request->offer_code);
                $offer = Offer::active()
                    ->where(function ($q) use ($code) {
                        $q->where('code', $code)
                          ->orWhereRaw('LOWER(code) = ?', [strtolower($code)]);
                    })
                    ->first();

                if ($offer && (!$offer->min_cart_amount || $subtotal >= $offer->min_cart_amount)) {
                    if (!$offer->max_uses || $offer->used_count < $offer->max_uses) {
                        if ($offer->offer_type === 'percentage') {
                            $discountTotal = ($subtotal * (float) $offer->discount_value) / 100;
                            if ($offer->max_discount && $discountTotal > $offer->max_discount) {
                                $discountTotal = (float) $offer->max_discount;
                            }
                        } elseif ($offer->offer_type === 'fixed') {
                            $discountTotal = (float) $offer->discount_value;
                        }
                        if ($discountTotal > $subtotal) {
                            $discountTotal = $subtotal;
                        }
                        $appliedOfferId = $offer->id;
                    }
                }
            }

            // Fallback: If discount value is explicitly sent from frontend
            if ($discountTotal == 0 && ($request->filled('discount_total') || $request->filled('discount_amount') || $request->filled('discount'))) {
                $discountTotal = (float) ($request->discount_total ?? $request->discount_amount ?? $request->discount ?? 0);
                if ($discountTotal > $subtotal) {
                    $discountTotal = $subtotal;
                }
            }

            // Shipping rule: Free (0rs) for subtotal >= 999rs or 0rs; otherwise 99rs
            if ($request->has('shipping_total') || $request->has('shipping')) {
                $shippingTotal = (float) ($request->shipping_total ?? $request->shipping ?? 0);
            } elseif ($subtotal == 0 || $subtotal >= 999) {
                $shippingTotal = 0;
            } else {
                $shippingTotal = 99;
            }

            $grandTotal = max(0, round($subtotal - $discountTotal + $shippingTotal, 2));

            // Sync with frontend total if provided and matches within minor rounding
            if ($request->filled('grand_total') || $request->filled('total')) {
                $frontendTotal = (float) ($request->grand_total ?? $request->total);
                if (abs($grandTotal - $frontendTotal) <= 1.0) {
                    $grandTotal = $frontendTotal;
                }
            }

            // Associate customer ID if authenticated or matching email found (or create new customer profile)
            $authCustomer = auth('customer_api')->user();
            $customerId = $authCustomer ? $authCustomer->id : null;
            $customerEmail = $request->shipping_address['email'] ?? ($authCustomer ? $authCustomer->email : null);
            $customerName = $request->shipping_address['name'] ?? ($authCustomer ? $authCustomer->name : 'Customer');
            $customerPhone = $request->shipping_address['phone'] ?? ($authCustomer ? $authCustomer->mobile : null);

            if (!$customerId && $customerEmail) {
                $existingCust = \App\Models\Customer::where('email', $customerEmail)->first();
                if ($existingCust) {
                    $customerId = $existingCust->id;
                } else {
                    try {
                        $newCust = \App\Models\Customer::create([
                            'name' => $customerName,
                            'email' => $customerEmail,
                            'mobile' => $customerPhone,
                            'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(12)),
                            'status' => true,
                        ]);
                        $customerId = $newCust->id;
                    } catch (\Exception $e) {
                        Log::warning('Customer auto-create on order placement fallback:', ['error' => $e->getMessage()]);
                    }
                }
            }

            // 2. Create the Order
            $order = Order::create([
                'order_number' => 'KN' . strtoupper(uniqid()),
                'customer_id' => $customerId,
                'payment_method' => $request->payment_method,
                'shipping_method' => 'custom',
                'currency' => 'INR',
                'status' => 'pending',
                'payment_status' => $request->payment_method === 'cod' ? 'pending' : 'paid',
                'subtotal' => $subtotal,
                'tax_total' => $totalTaxAmount,
                'shipping_total' => $shippingTotal,
                'discount_total' => $discountTotal,
                'offer_id' => $appliedOfferId,
                'grand_total' => $grandTotal,
                'shipping_address' => collect($request->shipping_address)->filter()->toArray(),
                'billing_address' => collect($request->shipping_address)->filter()->toArray(),
            ]);

            // 3. Create Order Items
            foreach ($orderItemsData as $itemData) {
                $order->items()->create($itemData);
            }

            // 4. Create initial status history
            $order->statusHistory()->create([
                'status' => 'pending',
                'notes' => 'Order placed successfully by customer.',
            ]);

            // 5. Increment offer usage if applied
            if ($appliedOfferId) {
                $offer->incrementUsage($customerId, $order->id, $discountTotal);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'data' => [
                    'order_id' => (string) $order->id,
                    'order_number' => $order->order_number,
                    'grand_total' => (float) $order->grand_total,
                    'subtotal' => (float) $order->subtotal,
                    'shipping_total' => (float) $order->shipping_total,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error placing order: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to place order. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper to resolve product & image for an order item dynamically.
     */
    protected function resolveProductAndImageForItem($item): array
    {
        $productName = trim($item->product_name ?? '');
        $product = null;

        if ($item->variant && $item->variant->product) {
            $variantProduct = $item->variant->product;
            if (empty($productName) || 
                strcasecmp(trim($variantProduct->name), $productName) === 0 || 
                str_contains(strtolower($productName), strtolower($variantProduct->name)) || 
                str_contains(strtolower($variantProduct->name), strtolower($productName))) {
                $product = $variantProduct;
            }
        }

        if (!$product && !empty($productName)) {
            $product = \App\Models\Product::where('name', $productName)
                ->orWhere('name', 'LIKE', '%' . $productName . '%')
                ->first();
        }

        if (!$product && !empty($productName)) {
            $slug = \Illuminate\Support\Str::slug($productName);
            $product = \App\Models\Product::where('slug', $slug)->first();
        }

        $image = null;
        if ($product) {
            $image = $product->main_image;
        }

        if (!$image || (str_contains($image, 'bunny-keychain') && !str_contains(strtolower($productName), 'bunny'))) {
            $lowerName = strtolower($productName);
            if (str_contains($lowerName, 'sunflower')) {
                $image = '/images/products/sunflower-stem.jpg';
            } elseif (str_contains($lowerName, 'bunny') || str_contains($lowerName, 'amigurumi')) {
                $image = '/images/products/bunny-keychain.jpg';
            } elseif (str_contains($lowerName, 'rose') || str_contains($lowerName, 'bouquet') || str_contains($lowerName, 'tulip wrap')) {
                $image = '/images/products/rose-bouquet.jpg';
            } elseif (str_contains($lowerName, 'teddy') || str_contains($lowerName, 'bear')) {
                $image = '/images/products/teddy-bear.jpg';
            } elseif (str_contains($lowerName, 'phone') || str_contains($lowerName, 'sleeve') || str_contains($lowerName, 'daisy crossbody')) {
                $image = '/images/products/daisy-phone-cover.jpg';
            } elseif (str_contains($lowerName, 'tote') || str_contains($lowerName, 'bag') || str_contains($lowerName, 'granny')) {
                $image = '/images/products/granny-square-bag.jpg';
            } elseif (str_contains($lowerName, 'purse') || str_contains($lowerName, 'coin') || str_contains($lowerName, 'strawberry')) {
                $image = '/images/products/strawberry-coin-purse.jpg';
            } elseif (str_contains($lowerName, 'cozy') || str_contains($lowerName, 'mug') || str_contains($lowerName, 'coaster')) {
                $image = '/images/products/tulip-mug-cozy.jpg';
            } elseif (str_contains($lowerName, 'bookmark') || str_contains($lowerName, 'sprout')) {
                $image = '/images/products/sprout-bookmark.jpg';
            } elseif (str_contains($lowerName, 'scrunchie') || str_contains($lowerName, 'hair') || str_contains($lowerName, 'clip')) {
                $image = '/images/products/floral-scrunchies.jpg';
            } elseif (str_contains($lowerName, 'vest')) {
                $image = '/images/products/crochet-vest.jpg';
            } elseif (str_contains($lowerName, 'potted') || str_contains($lowerName, 'plant')) {
                $image = '/images/products/potted-tulips.jpg';
            } else {
                $image = '/images/products/bunny-keychain.jpg';
            }
        }

        return [
            'product' => $product,
            'image' => $image,
            'slug' => $product ? $product->slug : \Illuminate\Support\Str::slug($productName ?: 'product'),
        ];
    }

    /**
     * Helper to format an Order model for frontend consumption.
     */
    protected function formatOrder(Order $order): array
    {
        $statusLabel = match (strtolower($order->status)) {
            'pending' => 'Order Placed',
            'confirmed' => 'Order Confirmed',
            'processing' => 'Crafting Your Order',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            default => ucfirst($order->status ?? 'Order Placed'),
        };

        return [
            'id' => (string) $order->id,
            'orderNumber' => $order->order_number,
            'orderDate' => $order->created_at ? $order->created_at->format('d M Y') : '',
            'estimatedDelivery' => $order->created_at ? $order->created_at->addDays(6)->format('d M Y') : '',
            'items' => $order->items->map(function ($item) {
                $resolved = $this->resolveProductAndImageForItem($item);
                $productObj = $resolved['product'];
                $itemImage = $resolved['image'];
                $itemSlug = $resolved['slug'];

                return [
                    'id' => (string) $item->id,
                    'productId' => (string) ($productObj ? $productObj->id : ($item->product_variant_id ?? 'prod-1')),
                    'product' => [
                        'id' => (string) ($productObj ? $productObj->id : ($item->product_variant_id ?? 'prod-1')),
                        'name' => $item->product_name ?? 'Handmade Product',
                        'price' => (float) $item->unit_price,
                        'main_image' => $itemImage,
                        'images' => [$itemImage],
                        'category' => 'Handmade Crochet',
                        'slug' => $itemSlug,
                    ],
                    'quantity' => (int) $item->quantity,
                    'price' => (float) $item->unit_price,
                ];
            })->values()->toArray(),
            'shippingAddress' => [
                'fullName' => $order->customer->name ?? ($order->shipping_address['name'] ?? 'Customer'),
                'email' => $order->customer->email ?? ($order->shipping_address['email'] ?? ''),
                'phone' => $order->customer->mobile ?? ($order->shipping_address['phone'] ?? ''),
                'addressLine1' => $order->shipping_address['address_line_1'] ?? '',
                'addressLine2' => $order->shipping_address['address_line_2'] ?? '',
                'city' => $order->shipping_address['city'] ?? '',
                'state' => $order->shipping_address['state'] ?? '',
                'pincode' => $order->shipping_address['pin_code'] ?? '',
                'country' => 'India',
            ],
            'paymentMethod' => match ($order->payment_method) {
                'cod' => 'Cash on Delivery',
                'razorpay', 'online' => 'Razorpay Online Payment',
                'upi' => 'UPI',
                'card' => 'Credit/Debit Card',
                'netbanking' => 'Net Banking',
                default => ucfirst($order->payment_method ?? 'Razorpay'),
            },
            'paymentStatus' => ucfirst($order->payment_status ?? 'Pending'),
            'subtotal' => (float) $order->subtotal,
            'shipping' => (float) $order->shipping_total,
            'discount' => (float) $order->discount_total,
            'total' => (float) max(0, (float) $order->subtotal - (float) $order->discount_total + (float) $order->shipping_total),
            'status' => $statusLabel,
            'timeline' => [
                [
                    'status' => 'Order Placed',
                    'date' => $order->created_at ? $order->created_at->format('d M Y, h:i A') : 'Just now',
                    'description' => 'Order received and queued for artisan assignment.',
                    'completed' => true,
                    'current' => $order->status === 'pending',
                ],
                [
                    'status' => 'Order Confirmed',
                    'date' => $order->confirmed_at ? $order->confirmed_at->format('d M Y') : 'Upcoming',
                    'description' => 'Natural cotton yarns prepared for crafting.',
                    'completed' => in_array($order->status, ['confirmed', 'processing', 'shipped', 'delivered']),
                    'current' => $order->status === 'confirmed',
                ],
                [
                    'status' => 'Crafting Your Order',
                    'date' => $order->processing_at ? $order->processing_at->format('d M Y') : 'Upcoming',
                    'description' => 'Master artisan is hand-crocheting your order.',
                    'completed' => in_array($order->status, ['processing', 'shipped', 'delivered']),
                    'current' => $order->status === 'processing',
                ],
                [
                    'status' => 'Shipped',
                    'date' => $order->shipped_at ? $order->shipped_at->format('d M Y') : 'Upcoming',
                    'description' => 'Handed over to express courier.',
                    'completed' => in_array($order->status, ['shipped', 'delivered']),
                    'current' => $order->status === 'shipped',
                ],
                [
                    'status' => 'Delivered',
                    'date' => $order->delivered_at ? $order->delivered_at->format('d M Y') : 'Upcoming',
                    'description' => 'Delivered to your doorstep.',
                    'completed' => $order->status === 'delivered',
                    'current' => $order->status === 'delivered',
                ],
            ]
        ];
    }
}
