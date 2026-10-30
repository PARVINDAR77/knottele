{{-- resources/views/admin/orders/view.blade.php --}}
@extends('admin.layouts.master')

@section('title', 'Order Details - Admin Panel')

@section('content')
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-stone-800 mb-2">Order Details</h2>
            <p class="text-stone-600">Order #{{ $order->order_number }}</p>
        </div>
        <div class="flex items-center gap-2 sm:gap-3 w-full sm:w-auto">
            <a href="{{ route('admin.orders.index') }}" class="btn-secondary flex-1 sm:flex-initial inline-flex items-center justify-center text-center py-2 px-3 sm:px-4 text-xs sm:text-sm font-semibold rounded-xl">
                <i class="fas fa-arrow-left mr-2"></i>Back to Orders
            </a>
            @php
                $invoicePdfFilename = $order->created_at->format('d-m-Y') . '-' . sprintf('%04d', $order->id) . '.pdf';
            @endphp
            <a href="{{ route('admin.orders.invoice', ['order' => $order, 'filename' => $invoicePdfFilename]) }}" target="_blank" class="btn-primary flex-1 sm:flex-initial inline-flex items-center justify-center text-center py-2 px-3 sm:px-4 text-xs sm:text-sm font-semibold rounded-xl shadow-md">
                <i class="fas fa-print mr-2"></i>Print Invoice
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Order Summary -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl shadow-sm border border-red-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-stone-200 bg-stone-50/50">
                <h3 class="text-lg font-semibold text-stone-800">Order Summary</h3>
            </div>
            <div class="p-6">
                <!-- Order Status -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-4">
                            <div class="text-sm text-stone-600">Order Status:</div>
                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                @if($order->status == 'pending') bg-amber-100 text-amber-800
                                @elseif($order->status == 'processing') bg-red-100 text-red-800
                                @elseif($order->status == 'shipped') bg-cyan-100 text-cyan-800
                                @elseif($order->status == 'delivered') bg-red-100 text-red-800
                                @elseif($order->status == 'cancelled') bg-rose-100 text-rose-800
                                @else bg-stone-100 text-stone-800 @endif">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                        <button onclick="updateStatus({{ $order->id }})" class="text-red-600 hover:text-red-900 text-sm font-medium">
                            <i class="fas fa-edit mr-1"></i>Update Status
                        </button>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="text-sm text-stone-600">Payment Status:</div>
                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                @if($order->payment_status == 'pending') bg-amber-100 text-amber-800
                                @elseif($order->payment_status == 'paid') bg-red-100 text-red-800
                                @elseif($order->payment_status == 'failed') bg-rose-100 text-rose-800
                                @else bg-stone-100 text-stone-800 @endif">
                                {{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}
                            </span>
                        </div>
                        <button onclick="updatePaymentStatus({{ $order->id }})" class="text-red-600 hover:text-red-900 text-sm font-medium">
                            <i class="fas fa-edit mr-1"></i>Update Payment
                        </button>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-stone-800 mb-4">Order Items</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 bg-stone-50 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Product</th>
                                    <th class="px-4 py-3 bg-stone-50 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Price</th>
                                    <th class="px-4 py-3 bg-stone-50 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Qty</th>
                                    <th class="px-4 py-3 bg-stone-50 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-stone-200">
                                @foreach($order->items as $item)
                                <tr>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-stone-900">{{ $item->product_name }}</div>
                                                <div class="text-sm text-stone-500">SKU: {{ $item->sku }}</div>
                                                @if($item->attributes)
                                                    <div class="text-xs text-stone-500">
                                                        @foreach(json_decode($item->attributes, true) as $key => $value)
                                                            {{ ucfirst($key) }}: {{ $value }}@if(!$loop->last), @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($item->unit_price, 2) }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $item->quantity }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                        {{ number_format($item->total, 2) }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Order Totals -->
                <div class="border-t border-stone-200 pt-6">
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-stone-600">Subtotal</span>
                            <span class="font-medium text-stone-900">{{ number_format($order->subtotal, 2) }}</span>
                        </div>
                        @if($order->tax_total > 0)
                        <div class="flex justify-between">
                            <span class="text-stone-600">Tax</span>
                            <span class="font-medium text-stone-900">{{ number_format($order->tax_total, 2) }}</span>
                        </div>
                        @endif
                        @if($order->shipping_total > 0)
                        <div class="flex justify-between">
                            <span class="text-stone-600">Shipping</span>
                            <span class="font-medium text-stone-900">{{ number_format($order->shipping_total, 2) }}</span>
                        </div>
                        @endif
                        @if($order->discount_total > 0)
                        <div class="flex justify-between">
                            <span class="text-stone-600">Discount</span>
                            <span class="font-medium text-red-600">-{{ number_format($order->discount_total, 2) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between border-t border-stone-200 pt-4">
                            <span class="text-lg font-semibold text-stone-900">Grand Total</span>
                            <span class="text-lg font-bold text-red-600">{{ number_format($order->grand_total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Information -->
    <div class="space-y-6">
        <!-- Customer Information -->
        <div class="bg-white rounded-2xl shadow-sm border border-red-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-stone-200 bg-stone-50/50">
                <h3 class="text-lg font-semibold text-stone-800">Customer Information</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <div class="text-sm text-stone-600">Customer Name</div>
                        <div class="font-medium text-stone-900">{{ $order->customer->name ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-stone-600">Email</div>
                        <div class="font-medium text-stone-900">{{ $order->customer->email ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-stone-600">Phone</div>
                        <div class="font-medium text-stone-900">{{ $order->customer->mobile ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping Information -->
        <div class="bg-white rounded-2xl shadow-sm border border-red-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-stone-200 bg-stone-50/50">
                <h3 class="text-lg font-semibold text-stone-800">Shipping Information</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @if($order->shipping_address && is_array($order->shipping_address))
                        @foreach($order->shipping_address as $key => $value)
                            @if($value)
                                <div>
                                    <div class="text-sm text-stone-600">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>
                                    <div class="font-medium text-stone-900">{{ $value }}</div>
                                </div>
                            @endif
                        @endforeach
                    @else
                        <div class="text-stone-500 Italics">No shipping address provided</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Payment Information -->
        <div class="bg-white rounded-2xl shadow-sm border border-red-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-stone-200 bg-stone-50/50">
                <h3 class="text-lg font-semibold text-stone-800">Payment Information</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <div class="text-sm text-stone-600">Payment Method</div>
                        <div class="font-medium text-stone-900">{{ $order->paymentMethod->name ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-stone-600">Payment Status</div>
                        <div class="font-medium text-stone-900">{{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tracking Information -->
        @if($order->shipments->isNotEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-stone-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-stone-200">
                <h3 class="text-lg font-semibold text-stone-800">Tracking Information</h3>
            </div>
            <div class="p-6">
                @foreach($order->shipments as $shipment)
                <div class="space-y-4">
                    <div>
                        <div class="text-sm text-stone-600">Tracking Number</div>
                        <div class="font-medium text-stone-900">{{ $shipment->tracking_number }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-stone-600">Carrier</div>
                        <div class="font-medium text-stone-900">{{ $shipment->carrier }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-stone-600">Status</div>
                        <div class="font-medium text-stone-900">{{ ucfirst($shipment->status) }}</div>
                    </div>
                    @if($shipment->shipped_at)
                    <div>
                        <div class="text-sm text-stone-600">Shipped Date</div>
                        <div class="font-medium text-stone-900">{{ $shipment->shipped_at->format('M d, Y H:i') }}</div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    function updateStatus(orderId) {
        Swal.fire({
            title: 'Update Order Status',
            html: `
                <div class="text-left space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-stone-700 mb-2">Order Status</label>
                        <select id="orderStatus" class="w-full border border-stone-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ $order->status == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="shipped" {{ $order->status == 'shipped' ? 'selected' : '' }}>Shipped</option>
                            <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            <option value="refunded" {{ $order->status == 'refunded' ? 'selected' : '' }}>Refunded</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-stone-700 mb-2">Notes (Optional)</label>
                        <textarea id="orderNotes" rows="3" class="w-full border border-stone-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="Add notes about status change"></textarea>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Update Status',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn-primary',
                cancelButton: 'btn-secondary'
            },
            preConfirm: () => {
                return {
                    status: document.getElementById('orderStatus').value,
                    notes: document.getElementById('orderNotes').value
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`{{ route('admin.orders.update-status', $order) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(result.value)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastr.success(data.message);
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        toastr.error(data.message || 'Error updating order status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error('Error updating order status');
                });
            }
        });
    }

    function updatePaymentStatus(orderId) {
        Swal.fire({
            title: 'Update Payment Status',
            html: `
                <div class="text-left space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-stone-700 mb-2">Payment Status</label>
                        <select id="paymentStatus" class="w-full border border-stone-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="pending" {{ $order->payment_status == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="paid" {{ $order->payment_status == 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="partially_paid" {{ $order->payment_status == 'partially_paid' ? 'selected' : '' }}>Partially Paid</option>
                            <option value="partially_refunded" {{ $order->payment_status == 'partially_refunded' ? 'selected' : '' }}>Partially Refunded</option>
                            <option value="failed" {{ $order->payment_status == 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="refunded" {{ $order->payment_status == 'refunded' ? 'selected' : '' }}>Refunded</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-stone-700 mb-2">Notes (Optional)</label>
                        <textarea id="paymentNotes" rows="3" class="w-full border border-stone-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="Add notes about payment status"></textarea>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Update Payment',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn-primary',
                cancelButton: 'btn-secondary'
            },
            preConfirm: () => {
                return {
                    payment_status: document.getElementById('paymentStatus').value,
                    notes: document.getElementById('paymentNotes').value
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`{{ route('admin.orders.update-payment-status', $order) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(result.value)
                })
                .then(response => {
                    return response.json().then(data => ({ ok: response.ok, status: response.status, data }));
                })
                .then(({ ok, status, data }) => {
                    if (ok && data.success) {
                        toastr.success(data.message);
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        toastr.error(data.message || data.error || 'Error updating payment status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error(error.message || 'Error updating payment status');
                });
            }
        });
    }
</script>
@endpush
