<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::apiResource('products', ProductController::class);

// Razorpay Standard Checkout Endpoints
Route::post('/create-order', [\App\Http\Controllers\Api\Customer\RazorpayPaymentController::class, 'createOrder']);
Route::post('/verify-payment', [\App\Http\Controllers\Api\Customer\RazorpayPaymentController::class, 'verifyPayment']);



