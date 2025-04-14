<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function rules()
    {
        return [
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string',
        ];
    }
}
