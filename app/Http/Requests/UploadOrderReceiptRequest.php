<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadOrderReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receipt' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'receipt.required' => 'اختر صورة سند التحويل أو ملف PDF.',
            'receipt.mimes' => 'الصيغ المسموحة: JPG وPNG وWebP وPDF.',
            'receipt.mimetypes' => 'نوع الملف غير مدعوم.',
            'receipt.max' => 'حجم السند يجب ألا يتجاوز 5 ميجابايت.',
        ];
    }

    public function attributes(): array
    {
        return ['receipt' => 'سند التحويل'];
    }
}
