{{-- resources/views/admin/orders/index.blade.php --}}
@extends('admin.layouts.master')

@section('title', 'Order Management - Admin Panel')

@section('content')
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-stone-800 mb-2">Order Management</h2>
            <p class="text-stone-600">Manage customer orders and track order status</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <select id="statusFilter" class="border border-stone-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 text-sm text-stone-900">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
                <option value="refunded">Refunded</option>
            </select>
            <select id="paymentFilter" class="border border-stone-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 text-sm text-stone-900">
                <option value="">All Payment</option>
                <option value="pending">Pending</option>
                <option value="paid">Paid</option>
                <option value="partially_paid">Partially Paid</option>
                <option value="failed">Failed</option>
                <option value="refunded">Refunded</option>
            </select>
        </div>
    </div>
</div>

<!-- Orders Table -->
<div class="bg-white rounded-2xl shadow-sm border border-red-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-stone-200 bg-stone-50/50">
        <h3 class="text-lg font-semibold text-stone-800">All Orders</h3>
    </div>
    <div class="p-6">
        <!-- Toolbar -->
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
            <div class="order-2 sm:order-1 w-full sm:w-auto">
                <div class="relative w-full sm:w-[260px]">
                    <input type="text" id="searchInput" placeholder="Search orders..."
                        class="pl-10 pr-4 py-2 rounded-xl border border-stone-300 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent w-full text-stone-900 placeholder-stone-400 text-xs sm:text-sm shadow-2xs">
                    <i class="fas fa-search absolute left-3.5 top-3 text-stone-400 text-xs sm:text-sm"></i>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 order-1 sm:order-2 w-full sm:w-auto">
                <!-- Bulk Actions Button (Small) -->
                <button onclick="showBulkActions()" class="btn-secondary btn-sm" title="Bulk Actions">
                    <i class="fas fa-tasks text-xs"></i>
                    <span>Bulk Actions</span>
                </button>
                <!-- Print Dropdown (with PDF Option) -->
                <div class="relative inline-block text-left" id="printDropdownWrapper">
                    <button type="button" onclick="togglePrintDropdown(event)" id="printDropdownBtn"
                        class="btn-secondary btn-sm hover:text-red-600 hover:bg-stone-50 flex items-center gap-1.5"
                        title="Print & PDF Options">
                        <i class="fas fa-print text-xs"></i>
                        <span>Print</span>
                        <i class="fas fa-chevron-down text-[10px] ml-0.5 transition-transform duration-200" id="printChevron"></i>
                    </button>
                    <!-- Dropdown Menu -->
                    <div id="printDropdownMenu"
                        class="hidden absolute right-0 mt-1.5 w-36 bg-white rounded-xl shadow-lg border border-stone-200 py-1 z-50 text-xs font-medium text-stone-700">
                        <button type="button" onclick="printTable('print'); closePrintDropdown();"
                            class="w-full text-left px-3.5 py-2 hover:bg-stone-50 flex items-center gap-2.5 text-stone-700 transition-colors">
                            <i class="fas fa-print text-stone-500 text-xs w-4 text-center"></i>
                            <span>Print</span>
                        </button>
                        <button type="button" onclick="printTable('pdf'); closePrintDropdown();"
                            class="w-full text-left px-3.5 py-2 hover:bg-red-50 hover:text-red-600 flex items-center gap-2.5 text-stone-700 transition-colors">
                            <i class="fas fa-file-pdf text-red-500 text-xs w-4 text-center"></i>
                            <span>PDF</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="overflow-x-auto">
            <table id="ordersTable" class="min-w-full divide-y divide-stone-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 bg-stone-50 text-left text-xs font-medium text-stone-500 uppercase tracking-wider text-center">
                            <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)" class="rounded text-red-600 focus:ring-red-500">
                        </th>
                        <th class="px-6 py-3 bg-stone-50 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Order ID</th>
                        <th class="px-6 py-3 bg-stone-50 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 bg-stone-50 text-left text-xs font-medium text-stone-500 uppercase tracking-wider text-center">Date</th>
                        <th class="px-6 py-3 bg-stone-50 text-left text-xs font-medium text-stone-500 uppercase tracking-wider text-center">Items</th>
                        <th class="px-6 py-3 bg-stone-50 text-left text-xs font-medium text-stone-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 bg-stone-50 text-left text-xs font-medium text-stone-500 uppercase tracking-wider text-center">Status</th>
                        <th class="px-6 py-3 bg-stone-50 text-left text-xs font-medium text-stone-500 uppercase tracking-wider text-center">Payment</th>
                        <th class="px-6 py-3 bg-stone-50 text-right text-xs font-medium text-stone-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody" class="bg-white divide-y divide-stone-200">
                    <!-- Dynamic content will be loaded here -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="paginationContainer" class="mt-4">
            <!-- Pagination will be loaded here -->
        </div>
    </div>
</div>

<!-- Loading Spinner -->
<div id="loadingSpinner" class="hidden fixed inset-0 bg-stone-900 bg-opacity-50 flex items-center justify-center z-50 backdrop-blur-sm">
    <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-red-600"></div>
</div>
@endsection

@push('styles')
<style>
    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
    }

    input:checked + .slider {
        background-color: #10b981;
    }

    input:checked + .slider:before {
        transform: translateX(26px);
    }

    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .status-pending {
        background-color: #fef3c7;
        color: #92400e;
    }

    .status-confirmed {
        background-color: #e0f2fe;
        color: #0369a1;
    }

    .status-processing {
        background-color: #f0f9ff;
        color: #0ea5e9;
    }

    .status-shipped {
        background-color: #ecfeff;
        color: #0891b2;
    }

    .status-delivered {
        background-color: #d1fae5;
        color: #065f46;
    }

    .status-cancelled {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .status-refunded {
        background-color: #fafaf9;
        color: #57534e;
    }

    .payment-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 600;
        text-align: center;
        justify-content: center;
        min-width: 100px;
    }

    .payment-pending {
        background-color: #fef3c7;
        color: #92400e;
    }

    .payment-paid {
        background-color: #d1fae5;
        color: #065f46;
    }

    .payment-partially_paid {
        background-color: #f0fdf4;
        color: #15803d;
    }

    .payment-failed {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .payment-refunded {
        background-color: #fafaf9;
        color: #57534e;
    }
</style>
@endpush

@push('scripts')
<script>
    // Route templates
    const routeTemplates = {
        view: '{{ route("admin.orders.view", ["order" => ":id"]) }}',
        updateStatus: '{{ route("admin.orders.update-status", ["order" => ":id"]) }}',
        updatePaymentStatus: '{{ route("admin.orders.update-payment-status", ["order" => ":id"]) }}',
        updateTracking: '{{ route("admin.orders.update-tracking", ["order" => ":id"]) }}',
        destroy: '{{ route("admin.orders.destroy", ["order" => ":id"]) }}',
        invoice: '{{ route("admin.orders.invoice", ["order" => ":id"]) }}'
    };

    // Helper function to replace route parameters
    function getRoute(routeName, id) {
        let url = routeTemplates[routeName] || '';
        return url.replace(/%3Aid|%253Aid|:id/gi, id);
    }

    let currentPage = 1;
    let currentStatusFilter = '';
    let currentPaymentFilter = '';
    let currentSearch = '';
    let selectedOrders = new Set();

    document.addEventListener('DOMContentLoaded', function() {
        loadOrders();

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        let searchTimeout;

        searchInput.addEventListener('keyup', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadOrders();
            }, 500);
        });

        // Status filter
        const statusFilter = document.getElementById('statusFilter');
        statusFilter.addEventListener('change', function() {
            currentStatusFilter = this.value;
            currentPage = 1;
            loadOrders();
        });

        // Payment filter
        const paymentFilter = document.getElementById('paymentFilter');
        paymentFilter.addEventListener('change', function() {
            currentPaymentFilter = this.value;
            currentPage = 1;
            loadOrders();
        });

        // Select all checkbox
        document.getElementById('selectAll').addEventListener('change', function(e) {
            const checkboxes = document.querySelectorAll('.order-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = e.target.checked;
                if (e.target.checked) {
                    selectedOrders.add(checkbox.value);
                } else {
                    selectedOrders.delete(checkbox.value);
                }
            });
        });
    });

    function loadOrders() {
        showLoading();

        const token = new URLSearchParams(window.location.search).get('auth_token')
                   || localStorage.getItem('admin_auth_token')
                   || '{{ session("admin_api_token") }}';

        const params = new URLSearchParams({
            page: currentPage,
            status: currentStatusFilter,
            payment_status: currentPaymentFilter,
            search: currentSearch,
            per_page: 10
        });

        if (token) {
            params.set('auth_token', token);
        }

        fetch(`{{ route('admin.orders.data') }}?${params}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Admin-Token': token || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderOrders(data.data);
                renderPagination(data.pagination);
            } else {
                toastr.error('Error loading orders');
            }
            hideLoading();
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('Error loading orders');
            hideLoading();
        });
    }

    function renderOrders(orders) {
        const tbody = document.getElementById('ordersTableBody');
        tbody.innerHTML = '';

        if (orders.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                        No orders found
                    </td>
                </tr>
            `;
            return;
        }

        orders.forEach(order => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-stone-50';

            const statusClass = `status-${order.status}`;
            const statusText = order.status.charAt(0).toUpperCase() + order.status.slice(1).replace('_', ' ');

            const paymentClass = `payment-${order.payment_status}`;
            const paymentText = order.payment_status.charAt(0).toUpperCase() + order.payment_status.slice(1).replace('_', ' ');

            const date = new Date(order.created_at);
            const formattedDate = date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });

            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="checkbox" class="order-checkbox" value="${order.id}"
                           onchange="toggleOrderSelection(${order.id}, this.checked)">
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-red-600">
                    ${order.order_number}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="h-10 w-10 flex-shrink-0">
                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center text-white font-semibold shadow-sm">
                                ${order.customer_name.charAt(0).toUpperCase()}
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-stone-900">
                                ${order.customer_name}
                            </div>
                            <div class="text-sm text-stone-500 font-medium flex items-center mt-1">
                                ${order.customer_mobile}
                            </div>
                            <div class="text-sm text-stone-500">
                                ${order.customer_email}
                            </div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-500">
                    ${formattedDate}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-stone-500 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-800">
                        <i class="fas fa-shopping-bag mr-1"></i>${order.items_count}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-stone-900">
                    ${parseFloat(order.grand_total).toFixed(2)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="status-badge ${statusClass}">
                        ${statusText}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="payment-badge ${paymentClass}">
                        ${paymentText}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium flex justify-end">
                    <div class="flex space-x-2">
                        <button onclick="viewOrder(${order.id})" class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="updateOrderStatus(${order.id}, '${order.status}')" class="p-2 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 transition-colors" title="Update Status">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="updatePaymentStatus(${order.id}, '${order.payment_status}')" class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors" title="Update Payment">
                            <i class="fas fa-credit-card"></i>
                        </button>
                        <button onclick="updateTracking(${order.id})" class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors" title="Add Tracking">
                            <i class="fas fa-truck"></i>
                        </button>
                        <button onclick="deleteOrder(${order.id})" class="p-2 bg-rose-50 text-rose-600 rounded-lg hover:bg-rose-100 transition-colors" title="Delete Order">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;

            tbody.appendChild(row);
        });
    }

    function renderPagination(pagination) {
        const container = document.getElementById('paginationContainer');

        let html = `
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <span class="font-medium">${pagination.from}</span> to
                    <span class="font-medium">${pagination.to}</span> of
                    <span class="font-medium">${pagination.total}</span> results
                </div>
                <div class="flex space-x-2">
        `;

        // Previous button
        html += `
            <button onclick="changePage(${currentPage - 1})"
                    ${currentPage === 1 ? 'disabled' : ''}
                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}">
                <span class="sr-only">Previous</span>
                <i class="fas fa-chevron-left"></i>
            </button>
        `;

        // Page numbers
        for (let i = 1; i <= pagination.last_page; i++) {
            if (i === 1 || i === pagination.last_page || (i >= currentPage - 2 && i <= currentPage + 2)) {
                html += `
                    <button onclick="changePage(${i})"
                            class="relative inline-flex items-center px-4 py-2 border ${currentPage === i ? 'border-red-500 bg-red-50 text-red-600' : 'border-stone-300 bg-white text-stone-700 hover:bg-stone-50'} text-sm font-medium">
                        ${i}
                    </button>
                `;
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                html += `
                    <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                        ...
                    </span>
                `;
            }
        }

        // Next button
        html += `
            <button onclick="changePage(${currentPage + 1})"
                    ${currentPage === pagination.last_page ? 'disabled' : ''}
                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${currentPage === pagination.last_page ? 'opacity-50 cursor-not-allowed' : ''}">
                <span class="sr-only">Next</span>
                <i class="fas fa-chevron-right"></i>
            </button>
        `;

        html += `
                </div>
            </div>
        `;

        container.innerHTML = html;
    }

    function changePage(page) {
        currentPage = page;
        loadOrders();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function toggleSelectAll(checkbox) {
        const checkboxes = document.querySelectorAll('.order-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = checkbox.checked;
            if (checkbox.checked) {
                selectedOrders.add(cb.value);
            } else {
                selectedOrders.delete(cb.value);
            }
        });
    }

    function toggleOrderSelection(id, isChecked) {
        if (isChecked) {
            selectedOrders.add(id);
        } else {
            selectedOrders.delete(id);
            document.getElementById('selectAll').checked = false;
        }
    }

    function viewOrder(id) {
        const url = getRoute('view', id);
        window.location.href = url;
    }

    function updateOrderStatus(id, currentStatus = 'pending') {
        const statuses = [
            { val: 'pending', label: '1. Order Placed' },
            { val: 'confirmed', label: '2. Order Confirmed' },
            { val: 'processing', label: '3. Crafting Your Order' },
            { val: 'shipped', label: '4. Shipped' },
            { val: 'delivered', label: '5. Delivered' },
            { val: 'cancelled', label: 'Cancelled' },
            { val: 'refunded', label: 'Refunded' },
        ];
        const optionsHtml = statuses.map(s => `<option value="${s.val}" ${s.val === currentStatus ? 'selected' : ''}>${s.label}</option>`).join('');

        Swal.fire({
            title: 'Update Order Status',
            html: `
                <div class="text-left space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-stone-700 mb-2">Order Status</label>
                        <select id="orderStatus" class="w-full border border-stone-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                            ${optionsHtml}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                        <textarea id="orderNotes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Add notes about status change"></textarea>
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
                showLoading();

                const url = getRoute('updateStatus', id);
                fetch(url, {
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
                        loadOrders();
                    } else {
                        toastr.error(data.message || 'Error updating order status');
                    }
                    hideLoading();
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error('Error updating order status');
                    hideLoading();
                });
            }
        });
    }

    function updatePaymentStatus(id, currentPaymentStatus = 'pending') {
        const paymentStatuses = [
            { val: 'pending', label: 'Pending' },
            { val: 'paid', label: 'Paid' },
            { val: 'partially_paid', label: 'Partially Paid' },
            { val: 'partially_refunded', label: 'Partially Refunded' },
            { val: 'failed', label: 'Failed' },
            { val: 'refunded', label: 'Refunded' },
        ];
        const optionsHtml = paymentStatuses.map(s => `<option value="${s.val}" ${s.val === currentPaymentStatus ? 'selected' : ''}>${s.label}</option>`).join('');

        Swal.fire({
            title: 'Update Payment Status',
            html: `
                <div class="text-left space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-stone-700 mb-2">Payment Status</label>
                        <select id="paymentStatus" class="w-full border border-stone-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                            ${optionsHtml}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                        <textarea id="paymentNotes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Add notes about payment status"></textarea>
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
                showLoading();

                const url = getRoute('updatePaymentStatus', id);
                fetch(url, {
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
                        loadOrders();
                    } else {
                        toastr.error(data.message || data.error || 'Error updating payment status');
                    }
                    hideLoading();
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error(error.message || 'Error updating payment status');
                    hideLoading();
                });
            }
        });
    }

    function updateTracking(id) {
        Swal.fire({
            title: 'Update Tracking Information',
            html: `
                <div class="text-left space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-stone-700 mb-2">Tracking Number *</label>
                        <input type="text" id="trackingNumber" class="w-full border border-stone-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500" required placeholder="e.g. TRK987654321">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Carrier / Courier</label>
                        <input type="text" id="carrier" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="e.g., Delhivery, BlueDart, FedEx">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-stone-700 mb-2">Order Stage</label>
                        <select id="trackingStatus" class="w-full border border-stone-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="shipped" selected>4. Shipped</option>
                            <option value="delivered">5. Delivered</option>
                            <option value="processing">3. Crafting Your Order</option>
                            <option value="confirmed">2. Order Confirmed</option>
                            <option value="pending">1. Order Placed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                        <textarea id="trackingNotes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Add notes about shipment"></textarea>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Update Tracking',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn-primary',
                cancelButton: 'btn-secondary'
            },
            preConfirm: () => {
                const trackingNumber = document.getElementById('trackingNumber').value;
                if (!trackingNumber) {
                    Swal.showValidationMessage('Please enter tracking number');
                    return false;
                }
                return {
                    tracking_number: trackingNumber,
                    carrier: document.getElementById('carrier').value,
                    status: document.getElementById('trackingStatus').value,
                    notes: document.getElementById('trackingNotes').value
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading();

                const url = getRoute('updateTracking', id);
                fetch(url, {
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
                        loadOrders();
                    } else {
                        toastr.error(data.message || 'Error updating tracking information');
                    }
                    hideLoading();
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error('Error updating tracking information');
                    hideLoading();
                });
            }
        });
    }

    function printInvoice(id) {
        const url = getRoute('invoice', id);
        window.open(url, '_blank');
    }

    function deleteOrder(id) {
        Swal.fire({
            title: 'Are you sure?',
            html: `
                <p>You are about to delete this order.</p>
                <p class="text-red-600 font-semibold mt-2">This action cannot be undone!</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete!',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#ef4444',
            customClass: {
                cancelButton: 'btn-secondary'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading();

                const url = getRoute('destroy', id);
                fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastr.success(data.message);
                        loadOrders();
                    } else {
                        toastr.error(data.message || 'Error deleting order');
                    }
                    hideLoading();
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error('Error deleting order');
                    hideLoading();
                });
            }
        });
    }

    function exportToCSV() {
        showLoading();

        const params = new URLSearchParams({
            status: currentStatusFilter,
            payment_status: currentPaymentFilter,
            search: currentSearch,
            export: 'csv'
        });

        window.location.href = `{{ route('admin.orders.export') }}?${params}`;
        setTimeout(hideLoading, 1000);
    }

    function exportToExcel() {
        showLoading();

        const params = new URLSearchParams({
            status: currentStatusFilter,
            payment_status: currentPaymentFilter,
            search: currentSearch,
            export: 'excel'
        });

        window.location.href = `{{ route('admin.orders.export') }}?${params}`;
        setTimeout(hideLoading, 1000);
    }

    /* Print & PDF Dropdown Handlers */
    function togglePrintDropdown(event) {
        if (event) event.stopPropagation();
        const menu = document.getElementById('printDropdownMenu');
        const chevron = document.getElementById('printChevron');
        if (!menu) return;
        const isHidden = menu.classList.contains('hidden');
        if (isHidden) {
            menu.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        } else {
            menu.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
        }
    }

    function closePrintDropdown() {
        const menu = document.getElementById('printDropdownMenu');
        const chevron = document.getElementById('printChevron');
        if (menu) menu.classList.add('hidden');
        if (chevron) chevron.classList.remove('rotate-180');
    }

    document.addEventListener('click', function(e) {
        const wrapper = document.getElementById('printDropdownWrapper');
        if (wrapper && !wrapper.contains(e.target)) {
            closePrintDropdown();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePrintDropdown();
        }
    });

    function printTable(format = 'print') {
        const table = document.getElementById('ordersTable');
        if (!table) return;

        // Clone table to exclude checkbox column and action column for a clean printed document
        const clone = table.cloneNode(true);
        clone.querySelectorAll('th:first-child, td:first-child, th:last-child, td:last-child').forEach(el => el.remove());

        const docTitle = format === 'pdf' ? 'KNOTELLE_Orders_Report.pdf' : 'Orders Report - KNOTELLE';
        const badgeText = format === 'pdf' ? 'Orders PDF Export' : 'Orders Report';

        const printFrame = document.createElement('iframe');
        printFrame.style.position = 'fixed';
        printFrame.style.right = '0';
        printFrame.style.bottom = '0';
        printFrame.style.width = '0';
        printFrame.style.height = '0';
        printFrame.style.border = '0';
        document.body.appendChild(printFrame);

        const frameDoc = printFrame.contentWindow.document;
        frameDoc.open();
        frameDoc.write(`
            <!DOCTYPE html>
            <html>
                <head>
                    <title>${docTitle}</title>
                    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
                    <style>
                        @page { size: auto; margin: 12mm 10mm; }
                        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 16px; color: #1e293b; font-size: 11px; }
                        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #dc2626; padding-bottom: 12px; margin-bottom: 16px; }
                        .brand { display: flex; align-items: flex-start; gap: 12px; }
                        .brand img { height: 48px; width: auto; object-fit: contain; }
                        .brand h2 { margin: 0; font-size: 18px; color: #dc2626; font-weight: 800; letter-spacing: 0.5px; }
                        .brand p { margin: 3px 0 0 0; font-size: 11px; color: #475569; line-height: 1.4; }
                        .meta { text-align: right; }
                        .badge { display: inline-block; padding: 3px 10px; font-size: 11px; font-weight: 700; background: #fee2e2; color: #dc2626; border-radius: 9999px; text-transform: uppercase; }
                        .meta p { margin: 4px 0 0 0; font-size: 10px; color: #64748b; }
                        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
                        th, td { border: 1px solid #e2e8f0; padding: 7px 10px; text-align: left; }
                        th { background-color: #f8fafc; font-weight: 700; color: #475569; text-transform: uppercase; font-size: 10px; }
                        tr:nth-child(even) { background-color: #fafaf9; }
                        .text-center { text-align: center; }
                        .text-right { text-align: right; }
                        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 600; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <div class="brand">
                            <img src="{{ asset('images/logo/knotelle-logo.png') }}?v=2" alt="KNOTELLE">
                            <div>
                                <h2>KNOTELLE</h2>
                                <p>
                                    Handcrafted with Love India<br>
                                    support@knotelle.in &bull; +91 97730 39243
                                </p>
                            </div>
                        </div>
                        <div class="meta">
                            <span class="badge">${badgeText}</span>
                            <p>Generated: ${new Date().toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</p>
                        </div>
                    </div>
                    ${clone.outerHTML}
                </body>
            </html>
        `);
        frameDoc.close();

        printFrame.contentWindow.focus();
        setTimeout(() => {
            printFrame.contentWindow.print();
            setTimeout(() => {
                if (document.body.contains(printFrame)) {
                    document.body.removeChild(printFrame);
                }
            }, 1000);
        }, 350);
    }

    function showLoading() {
        document.getElementById('loadingSpinner').classList.remove('hidden');
    }

    function hideLoading() {
        document.getElementById('loadingSpinner').classList.add('hidden');
    }

    function showBulkActions() {
        if (selectedOrders.size === 0) {
            toastr.warning('Please select at least one order');
            return;
        }

        Swal.fire({
            title: 'Bulk Actions',
            html: `
                <div class="space-y-3">
                    <button onclick="bulkDelete()" class="w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-red-600 border border-gray-200 rounded-lg">
                        <i class="fas fa-trash mr-2"></i>Delete Selected (${selectedOrders.size})
                    </button>
                </div>
            `,
            showCancelButton: true,
            cancelButtonText: 'Cancel',
            showConfirmButton: false,
            customClass: {
                container: 'bulk-actions-modal'
            }
        });
    }

    function bulkDelete() {
        if (selectedOrders.size === 0) {
            toastr.warning('Please select at least one order');
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            html: `
                <p>You are about to delete ${selectedOrders.size} order(s).</p>
                <p class="text-red-600 font-semibold mt-2">This action cannot be undone!</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete!',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#ef4444',
            customClass: {
                cancelButton: 'btn-secondary'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading();

                fetch('{{ route("admin.orders.bulk-delete") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ ids: Array.from(selectedOrders) })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastr.success(data.message);
                        selectedOrders.clear();
                        document.getElementById('selectAll').checked = false;
                        loadOrders();
                    } else {
                        toastr.error(data.message || 'Error deleting orders');
                    }
                    hideLoading();
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error('Error deleting orders');
                    hideLoading();
                });
            }
        });
    }
</script>
@endpush
