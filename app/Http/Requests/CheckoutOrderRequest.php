<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['store', 'whatsapp'])],
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'max:25', 'regex:/^(?:\+?966|0)?5\d{8}$/'],
            'area' => ['required', 'string', 'min:2', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['accepted'],
            'website' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'أدخل رقم جوال سعودي صحيحًا مثل 05xxxxxxxx.',
            'terms.accepted' => 'يجب الموافقة على الشروط قبل إكمال الطلب.',
            'website.prohibited' => 'تعذر إرسال الطلب. أعد تحميل الصفحة وحاول مرة أخرى.',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'الاسم', 'phone' => 'رقم الجوال', 'area' => 'الحي', 'address' => 'العنوان', 'notes' => 'الملاحظات'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['phone' => preg_replace('/[\s\-()]/', '', (string) $this->phone)]);
    }
}
