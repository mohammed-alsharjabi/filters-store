<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['quantity' => ['nullable', 'integer', 'min:1', 'max:99']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['quantity' => $this->input('quantity', 1)]);
    }
}
