<?php

use App\Http\Controllers\Payments\PesapalController;

Route::prefix('payments')->group(function () {
    Route::post('/pesapal/initiate', [PesapalController::class, 'initiatePayment']);
    Route::get('/pesapal/status/{orderId}', [PesapalController::class, 'paymentStatus']);
});
