<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * Get all addresses for the authenticated customer.
     */
    public function index(Request $request)
    {
        $customer = auth('customer_api')->user();
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $addresses = CustomerAddress::where('customer_id', $customer->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $formatted = $addresses->map(function ($addr) use ($customer) {
            return [
                'id' => (string) $addr->id,
                'fullName' => $addr->name ?: $customer->name,
                'email' => $customer->email,
                'phone' => $addr->mobile ?: $customer->mobile,
                'addressLine1' => $addr->address ?: '',
                'addressLine2' => '',
                'city' => $addr->city ?: '',
                'state' => $addr->state ?: '',
                'pincode' => $addr->pincode ?: '',
                'country' => $addr->country ?: 'India',
                'isDefault' => (bool) $addr->is_default,
                'type' => $addr->type ?: 'shipping',
            ];
        });

        return response()->json([
            'success' => true,
            'addresses' => $formatted,
            'data' => $formatted,
        ]);
    }

    /**
     * Store a new address for the authenticated customer.
     */
    public function store(Request $request)
    {
        $customer = auth('customer_api')->user();
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $request->validate([
            'addressLine1' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|string|max:20',
            'fullName' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:25',
            'type' => 'nullable|string|max:50',
            'isDefault' => 'nullable|boolean',
        ]);

        $count = CustomerAddress::where('customer_id', $customer->id)->count();
        $isDefault = $request->boolean('isDefault') || $count === 0;

        if ($isDefault) {
            CustomerAddress::where('customer_id', $customer->id)
                ->update(['is_default' => 0]);
        }

        $fullAddr = trim($request->addressLine1);
        if ($request->filled('addressLine2')) {
            $fullAddr .= ', ' . trim($request->addressLine2);
        }

        $address = CustomerAddress::create([
            'customer_id' => $customer->id,
            'name' => $request->filled('fullName') ? trim($request->fullName) : $customer->name,
            'mobile' => $request->filled('phone') ? trim($request->phone) : $customer->mobile,
            'address' => $fullAddr,
            'city' => trim($request->city),
            'state' => trim($request->state),
            'country' => 'IN',
            'pincode' => trim($request->pincode),
            'type' => $request->input('type', 'shipping'),
            'is_default' => $isDefault ? 1 : 0,
        ]);

        $formatted = [
            'id' => (string) $address->id,
            'fullName' => $address->name,
            'email' => $customer->email,
            'phone' => $address->mobile,
            'addressLine1' => $address->address,
            'addressLine2' => '',
            'city' => $address->city,
            'state' => $address->state,
            'pincode' => $address->pincode,
            'country' => $address->country,
            'isDefault' => (bool) $address->is_default,
            'type' => $address->type,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Address saved successfully.',
            'address' => $formatted,
            'data' => $formatted,
        ], 201);
    }

    /**
     * Update an address owned by the authenticated customer.
     */
    public function update(Request $request, $id)
    {
        $customer = auth('customer_api')->user();
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $address = CustomerAddress::where('customer_id', $customer->id)
            ->where('id', $id)
            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found or unauthorized.'
            ], 404);
        }

        $request->validate([
            'addressLine1' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|string|max:20',
        ]);

        $isDefault = $request->has('isDefault') ? $request->boolean('isDefault') : $address->is_default;

        if ($isDefault && !$address->is_default) {
            CustomerAddress::where('customer_id', $customer->id)
                ->where('id', '!=', $id)
                ->update(['is_default' => 0]);
        }

        $fullAddr = trim($request->addressLine1);
        if ($request->filled('addressLine2')) {
            $fullAddr .= ', ' . trim($request->addressLine2);
        }

        $address->update([
            'name' => $request->filled('fullName') ? trim($request->fullName) : $address->name,
            'mobile' => $request->filled('phone') ? trim($request->phone) : $address->mobile,
            'address' => $fullAddr,
            'city' => trim($request->city),
            'state' => trim($request->state),
            'pincode' => trim($request->pincode),
            'is_default' => $isDefault ? 1 : 0,
        ]);

        $formatted = [
            'id' => (string) $address->id,
            'fullName' => $address->name,
            'email' => $customer->email,
            'phone' => $address->mobile,
            'addressLine1' => $address->address,
            'addressLine2' => '',
            'city' => $address->city,
            'state' => $address->state,
            'pincode' => $address->pincode,
            'country' => $address->country,
            'isDefault' => (bool) $address->is_default,
            'type' => $address->type,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully.',
            'address' => $formatted,
            'data' => $formatted,
        ]);
    }

    /**
     * Delete an address owned by the authenticated customer.
     */
    public function destroy(Request $request, $id)
    {
        $customer = auth('customer_api')->user();
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $address = CustomerAddress::where('customer_id', $customer->id)
            ->where('id', $id)
            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found or unauthorized.'
            ], 404);
        }

        $address->delete();

        // If default address was deleted, promote another address if exists
        $nextDefault = CustomerAddress::where('customer_id', $customer->id)->first();
        if ($nextDefault && !CustomerAddress::where('customer_id', $customer->id)->where('is_default', 1)->exists()) {
            $nextDefault->update(['is_default' => 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully.'
        ]);
    }

    /**
     * Set default address for the authenticated customer.
     */
    public function setDefault(Request $request, $id)
    {
        $customer = auth('customer_api')->user();
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $address = CustomerAddress::where('customer_id', $customer->id)
            ->where('id', $id)
            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found or unauthorized.'
            ], 404);
        }

        CustomerAddress::where('customer_id', $customer->id)->update(['is_default' => 0]);
        $address->update(['is_default' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Default address set successfully.'
        ]);
    }
}
