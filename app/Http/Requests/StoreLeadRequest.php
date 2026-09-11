<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['quote', 'contact'])],
            'submission_channel' => ['nullable', Rule::in(['web', 'whatsapp'])],
            'name' => ['nullable', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'string', 'max:25', 'regex:/^(?:\+?966|0)?5\d{8}$/'],
            'area' => ['nullable', 'string', 'max:120'],
            'area_size' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'service_id' => ['nullable', 'integer', Rule::exists('services', 'id')->where('status', 'published')->where('is_active', true)],
            'preferred_contact' => ['nullable', Rule::in(['phone', 'whatsapp'])],
            'message' => ['nullable', 'string', 'max:2000'],
            'site_images' => ['nullable', 'array', 'max:5'],
            'site_images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=200,min_height=200,max_width=12000,max_height=12000'],
            'website' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'أدخل رقم جوال سعودي صحيحًا مثل 05xxxxxxxx.',
            'service_id.exists' => 'الخدمة المختارة غير متاحة للنشر حاليًا.',
            'area_size.integer' => 'اكتب عدد الأجهزة برقم صحيح.',
            'site_images.max' => 'يمكن رفع خمس صور كحد أقصى.',
            'site_images.*.image' => 'يجب أن يكون كل مرفق صورة صالحة.',
            'site_images.*.mimes' => 'الصيغ المسموحة للصور: JPG وPNG وWebP.',
            'site_images.*.max' => 'حجم الصورة الواحدة يجب ألا يتجاوز 5 ميجابايت.',
            'site_images.*.dimensions' => 'أبعاد الصورة غير مناسبة. الحد الأدنى 200×200 بكسل.',
            'website.prohibited' => 'تعذر إرسال الطلب. أعد تحميل الصفحة وحاول مرة أخرى.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'الاسم',
            'phone' => 'رقم الجوال',
            'area' => 'الحي أو المنطقة',
            'area_size' => 'عدد الأجهزة',
            'service_id' => 'الخدمة',
            'preferred_contact' => 'طريقة التواصل المفضلة',
            'message' => 'تفاصيل الطلب',
            'site_images' => 'صور الجهاز أو الموقع',
            'site_images.*' => 'صورة الجهاز أو الموقع',
        ];
    }

    protected function prepareForValidation(): void
    {
        $phone = preg_replace('/[\s\-()]/', '', (string) $this->phone);
        $type = $this->input('type', 'quote');
        $this->merge([
            'phone' => $phone,
            'type' => $type,
            'submission_channel' => $this->input('submission_channel', 'web'),
            'preferred_contact' => $this->input('preferred_contact', $type === 'contact' ? 'whatsapp' : 'phone'),
        ]);
    }
}
