<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantities' => ['required', 'array', 'max:100'],
            'quantities.*' => ['required', 'integer', 'min:0', 'max:99'],
        ];
    }
}
