<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\AdminController;

// --- RUTAS PÚBLICAS ---
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/google', [AuthController::class, 'googleLogin']); 
Route::get('services', [ServiceController::class, 'index']);
Route::get('services/{id}', [ServiceController::class, 'show']); 
Route::get('categories', [ServiceController::class, 'categories']); 
Route::get('services/{id}/reviews', [ReviewController::class, 'index']); 

// 🚨 CRÍTICO: El Webhook de Stripe debe ser público porque Stripe te manda los eventos directamente.
// Ya lo excluimos de la validación CSRF en bootstrap/app.php
Route::post('webhooks/stripe', [PaymentController::class, 'handleWebhook']);

// --- RUTAS PROTEGIDAS (Auth:api) ---
Route::group(['middleware' => 'auth:api'], function () {
    
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    
    // --- GESTIÓN DE PERFIL ---
    Route::post('user/update', [AuthController::class, 'updateProfile']); 
    Route::post('user/complete-profile', [AuthController::class, 'completeProfile']); 

    // --- VERIFICACIÓN PROFESIONAL (KYC) ---
    Route::post('verification/submit', [VerificationController::class, 'submitVerification']);
    Route::get('verification/status', [VerificationController::class, 'getMyStatus']);

    // --- GESTIÓN DE SERVICIOS ---
    Route::get('my-services', [ServiceController::class, 'userServices']); 
    Route::post('services', [ServiceController::class, 'store']); 
    Route::put('services/{id}', [ServiceController::class, 'update']); 
    Route::post('services/{id}', [ServiceController::class, 'update']); // Soporte para FormData
    Route::delete('services/{id}', [ServiceController::class, 'destroy']); 

    // --- CONTRATACIONES Y PAGOS ---
    Route::post('contracts', [ContractController::class, 'store']); 
    Route::post('payments/process', [PaymentController::class, 'process']); // ✨ Enlazado al controlador real de Stripe
    Route::get('my-contracts', [ContractController::class, 'myContracts']); 
    Route::get('seller/orders', [ContractController::class, 'sellerOrders']);
    
    Route::patch('contracts/{id}/status', [ContractController::class, 'updateStatus']);
    Route::patch('contracts/{id}/cancel', [ContractController::class, 'cancel']);

    // --- RESEÑAS ---
    Route::post('services/{id}/reviews', [ReviewController::class, 'store']);

    // --- MENSAJERÍA ---
    Route::get('conversations', [MessageController::class, 'getConversations']);
    Route::get('messages/{userId}', [MessageController::class, 'conversation']); 
    Route::post('messages', [MessageController::class, 'store']); 

    // --- PANEL ADMIN (Protegido por IsAdmin) ---
    Route::group(['prefix' => 'admin', 'middleware' => 'is_admin'], function () {
        Route::get('metrics', [AdminController::class, 'metrics']);
        Route::get('users', [AdminController::class, 'users']);
        Route::get('services', [AdminController::class, 'services']);
        Route::get('transactions', [AdminController::class, 'transactions']);
        Route::patch('users/{id}', [AdminController::class, 'updateUser']);
        Route::patch('services/{id}', [AdminController::class, 'toggleService']);

        // ✨ NUEVAS RUTAS: Control de KYC para el Admin de Nexoly
        Route::get('verifications/pending', [VerificationController::class, 'indexPendings']);
        Route::post('verifications/{id}/approve', [VerificationController::class, 'approve']);
        Route::post('verifications/{id}/reject', [VerificationController::class, 'reject']);
    });
});