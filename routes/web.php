<?php

use App\Http\Controllers\Payments\PesapalController;

Route::get('/payment/callback', [PesapalController::class, 'paymentCallback'])
    ->name('pesapal.callback');
