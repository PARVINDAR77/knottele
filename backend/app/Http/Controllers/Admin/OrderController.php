<?php
// app/Http/Controllers/Admin/OrderController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Display listing page
     */
    public function index()
    {
        return view('admin.orders.index');
    }

    /**
     * Show order details page
     */
    public function view(Order $order)
    {
        $order->load(['customer', 'items', 'items.product', 'items.variant', 'payments', 'shipments']);
        return view('admin.orders.view', compact('order'));
    }

    /**
     * AJAX: Get orders data
     */
    public function getOrders(Request $request)
    {
        $query = Order::with(['customer', 'items', 'payments'])
            ->withCount(['items'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%");
                    })
                    ->orWhere('shipping_address', 'like', "%{$search}%");
            });
        }


        // Sorting
        if ($request->has('sort')) {
            $sortField = $request->sort;
            $sortDirection = $request->direction ?? 'desc';
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->latest();
        }

        $orders = $query->paginate($request->per_page ?? 10)
            ->through(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer->name ?? (!empty($order->shipping_address['name']) ? $order->shipping_address['name'] : 'Guest'),
                    'customer_email' => $order->customer->email ?? (!empty($order->shipping_address['email']) ? $order->shipping_address['email'] : 'N/A'),
                    'customer_mobile' => $order->customer->mobile ?? (!empty($order->shipping_address['phone']) ? $order->shipping_address['phone'] : 'N/A'),
                    'date' => $order->created_at ? $order->created_at->format('Y-m-d') : '-',
                    'created_at' => $order->created_at,
                    'items_count' => $order->items_count,
                    'grand_total' => $order->grand_total,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'payment_method' => match ($order->payment_method) {
                        'cod' => 'Cash on Delivery',
                        'upi_qr', 'upi' => 'UPI QR Payment',
                        'online' => 'UPI QR Online Payment',
                        'card' => 'Credit/Debit Card',
                        'netbanking' => 'Net Banking',
                        default => ucfirst(str_replace('_', ' ', $order->payment_method ?? 'N/A')),
                    },

                    'shipping_address' => is_array($order->shipping_address)
                        ? implode(', ', array_filter(array_map(fn($v) => is_scalar($v) ? (string)$v : '', $order->shipping_address)))
                        : (is_string($order->shipping_address) ? $order->shipping_address : 'N/A'),
                    'tracking_number' => $order->shipments->first()?->tracking_number ?? null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem(),
            ]
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $order->status;
            $order->status = $validated['status'];

            // Sync payment status when refunded
            if ($validated['status'] === 'refunded') {
                $order->payment_status = 'refunded';
            }

            // Set timestamps based on status
            switch ($validated['status']) {
                case 'confirmed':
                    $order->confirmed_at = now();
                    break;
                case 'processing':
                    $order->processing_at = now();
                    break;
                case 'shipped':
                    $order->shipped_at = now();
                    break;
                case 'delivered':
                    $order->delivered_at = now();
                    break;
                case 'cancelled':
                    $order->cancelled_at = now();
                    break;
            }

            $order->save();

            // Create status history
            $order->statusHistory()->create([
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'admin_id' => auth('admin')->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully!',
                'status' => $order->status,
                'old_status' => $oldStatus,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error updating order status: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error updating order status: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_status' => 'required|string|in:pending,pending_verification,paid,partially_paid,partially_refunded,failed,refunded',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $oldPaymentStatus = $order->payment_status;
            $newPaymentStatus = $validated['payment_status'];
            $order->payment_status = $newPaymentStatus;

            // Sync order status when payment is set to refunded
            if ($newPaymentStatus === 'refunded') {
                $order->status = 'refunded';
            }

            // Record notes if provided
            if (!empty($validated['notes'])) {
                $noteEntry = '[' . now()->format('d M Y H:i') . '] Payment changed to ' . ucfirst(str_replace('_', ' ', $newPaymentStatus)) . ': ' . trim($validated['notes']);
                $order->admin_notes = $order->admin_notes ? ($order->admin_notes . "\n" . $noteEntry) : $noteEntry;
            }

            // Save order with fallback if MySQL enum does not yet include partially_paid
            try {
                $order->save();
            } catch (\Illuminate\Database\QueryException $qe) {
                if (str_contains($qe->getMessage(), 'payment_status')) {
                    try {
                        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'pending'");
                        $order->save();
                    } catch (\Throwable $alterEx) {
                        if ($newPaymentStatus === 'partially_paid') {
                            $order->payment_status = 'partially_refunded';
                            $order->save();
                        } else {
                            throw $qe;
                        }
                    }
                } else {
                    throw $qe;
                }
            }

            // Record status history if refunded
            if ($newPaymentStatus === 'refunded') {
                try {
                    $order->statusHistory()->create([
                        'status' => 'refunded',
                        'notes' => $validated['notes'] ?? 'Payment status updated to refunded',
                        'admin_id' => auth('admin')->id(),
                    ]);
                } catch (\Throwable $th) {
                    Log::warning('Could not record status history for order ' . $order->id . ': ' . $th->getMessage());
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment status updated successfully!',
                'payment_status' => $order->payment_status,
                'old_payment_status' => $oldPaymentStatus,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error updating payment status: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error updating payment status: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve UPI QR payment and confirm order.
     */
    public function approvePayment(Request $request, Order $order)
    {
        try {
            DB::beginTransaction();

            $order->payment_status = 'paid';
            if (in_array($order->status, ['pending', 'processing'])) {
                $order->status = 'confirmed';
                $order->confirmed_at = now();
            }

            $adminName = auth('admin')->user()?->name ?? 'Admin';
            $note = '[' . now()->format('d M Y H:i') . "] UPI Payment approved by {$adminName}. Order confirmed.";
            $order->admin_notes = $order->admin_notes ? ($order->admin_notes . "\n" . $note) : $note;
            $order->save();

            // Update payment record if exists
            $payment = $order->payments()->latest()->first();
            if ($payment) {
                $payment->update([
                    'status' => 'success',
                    'paid_at' => now(),
                    'response_data' => array_merge($payment->response_data ?? [], [
                        'approved_by' => $adminName,
                        'approved_at' => now()->toIso8601String(),
                    ])
                ]);
            }

            // Create status history
            $order->statusHistory()->create([
                'status' => $order->status,
                'notes' => 'UPI Payment verified & approved by ' . $adminName . '. Order confirmed.',
                'admin_id' => auth('admin')->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'UPI payment approved successfully! Order status is now Confirmed.',
                'order' => [
                    'id' => $order->id,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error approving payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject UPI payment and mark order as cancelled/failed.
     */
    public function rejectPayment(Request $request, Order $order)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $order->payment_status = 'failed';
            $order->status = 'cancelled';
            $order->cancelled_at = now();

            $adminName = auth('admin')->user()?->name ?? 'Admin';
            $reason = $validated['reason'] ?? 'Payment verification failed / UTR not found';
            $note = '[' . now()->format('d M Y H:i') . "] UPI Payment rejected by {$adminName}. Reason: {$reason}";
            $order->admin_notes = $order->admin_notes ? ($order->admin_notes . "\n" . $note) : $note;
            $order->cancellation_reason = 'Payment Verification Rejected: ' . $reason;
            $order->save();

            // Update payment record if exists
            $payment = $order->payments()->latest()->first();
            if ($payment) {
                $payment->update([
                    'status' => 'failed',
                    'failure_reason' => $reason,
                    'response_data' => array_merge($payment->response_data ?? [], [
                        'rejected_by' => $adminName,
                        'rejected_at' => now()->toIso8601String(),
                        'reason' => $reason
                    ])
                ]);
            }

            // Create status history
            $order->statusHistory()->create([
                'status' => 'cancelled',
                'notes' => 'UPI Payment rejected by admin. Reason: ' . $reason,
                'admin_id' => auth('admin')->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'UPI payment rejected. Order marked as Cancelled.',
                'order' => [
                    'id' => $order->id,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error rejecting payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete order
     */
    public function destroy(Order $order)
    {
        try {
            DB::beginTransaction();

            // Soft delete the order
            $order->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting order: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error deleting order. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Bulk delete orders
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:orders,id'
        ]);

        try {
            DB::beginTransaction();

            $count = Order::whereIn('id', $request->ids)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$count} order(s) deleted successfully!"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error bulk deleting orders: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error deleting orders. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Export orders
     */
    public function export(Request $request)
    {
        $query = Order::with(['customer', 'items']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%");
                    })
                    ->orWhere('shipping_address', 'like', "%{$search}%");
            });
        }

        $orders = $query->get()->map(function ($order) {
            return [
                'Order ID' => $order->order_number,
                'Customer' => $order->customer->name ?? ($order->shipping_address['name'] ?? 'Guest'),
                'Email' => $order->customer->email ?? ($order->shipping_address['email'] ?? 'N/A'),
                'Phone' => $order->customer->mobile ?? ($order->shipping_address['phone'] ?? 'N/A'),
                'Order Date' => $order->created_at ? $order->created_at->format('Y-m-d H:i') : '-',
                'Status' => ucfirst($order->status),
                'Payment Status' => ucfirst(str_replace('_', ' ', $order->payment_status)),
                'Payment Method' => match ($order->payment_method) {
                    'cod' => 'Cash on Delivery',
                    'online' => 'Online Payment',
                    'upi' => 'UPI',
                    'card' => 'Credit/Debit Card',
                    'netbanking' => 'Net Banking',
                    default => ucfirst($order->payment_method ?? 'N/A'),
                },

                'Items Count' => $order->items->count(),
                'Subtotal' => number_format((float) $order->subtotal, 2),
                'Tax' => number_format((float) $order->tax_total, 2),
                'Shipping' => number_format((float) $order->shipping_total, 2),
                'Discount' => number_format((float) $order->discount_total, 2),
                'Grand Total' => number_format((float) $order->grand_total, 2),
                'Shipping Address' => $order->shipping_address ? implode(', ', array_filter($order->shipping_address)) : 'N/A',
            ];
        });

        $filename = 'orders_' . date('Y-m-d_H-i') . '.csv';

        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            if (count($orders) > 0) {
                fputcsv($file, array_keys($orders[0]));
            }

            // Add data rows
            foreach ($orders as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Print invoice
     */
    public function printInvoice(Order $order, $filename = null)
    {
        $order->load(['customer', 'items', 'items.product', 'items.variant']);

        // Generate clean PDF filename: e.g. 22-09-2026-0001.pdf
        $pdfFilename = $filename ?: ($order->created_at->format('d-m-Y') . '-' . sprintf('%04d', $order->id) . '.pdf');

        // Return view for printing
        return view('admin.orders.invoice', compact('order', 'pdfFilename'));
    }

    /**
     * Update tracking number
     */
    public function updateTracking(Request $request, Order $order)
    {
        $validated = $request->validate([
            'tracking_number' => 'required|string|max:100',
            'carrier' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $statusToSet = $validated['status'] ?? 'shipped';
            $order->status = $statusToSet;
            if ($statusToSet === 'shipped' && !$order->shipped_at) {
                $order->shipped_at = now();
            }
            $order->save();

            // Find existing shipment or create new one safely
            $shipment = $order->shipments()->first();
            if ($shipment) {
                $shipment->update([
                    'tracking_number' => $validated['tracking_number'],
                    'carrier' => $validated['carrier'] ?? $shipment->carrier ?? 'Standard',
                    'status' => $statusToSet,
                    'shipped_at' => $shipment->shipped_at ?? now(),
                    'delivery_notes' => $validated['notes'] ?? $shipment->delivery_notes,
                ]);
            } else {
                $shipment = $order->shipments()->create([
                    'tracking_number' => $validated['tracking_number'],
                    'carrier' => $validated['carrier'] ?? 'Standard',
                    'status' => $statusToSet,
                    'shipped_at' => now(),
                    'delivery_notes' => $validated['notes'] ?? null,
                ]);

                foreach ($order->items as $item) {
                    $shipment->items()->create([
                        'order_item_id' => $item->id,
                        'quantity' => $item->quantity,
                    ]);
                }
            }

            // Record status history
            $order->statusHistory()->create([
                'status' => $statusToSet,
                'notes' => 'Tracking updated: ' . $validated['tracking_number'],
                'admin_id' => auth('admin')->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tracking information updated successfully!',
                'tracking_number' => $validated['tracking_number'],
                'status' => $order->status,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating tracking number: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating tracking information. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
