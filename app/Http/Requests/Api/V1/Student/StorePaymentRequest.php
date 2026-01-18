<?php

namespace App\Http\Requests\Api\V1\Student;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.001',
            'payment_method_id' => 'required|string',
            'gateway_reference' => 'nullable|string|max:255',
            'installment_id' => 'nullable|integer|exists:ar_installments,id',
        ];
    }
}
