<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class BusinessSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['site_name', 'فلاتر وتحلية المياه بالرياض', 'اسم الموقع'],
            ['phone_display', config('site.fallback.phone_display'), 'رقم التواصل الظاهر'],
            ['phone_e164', config('site.fallback.phone_e164'), 'رقم التواصل الدولي'],
            ['phone_tel', config('site.fallback.phone_tel'), 'رابط الاتصال'],
            ['whatsapp_url', config('site.fallback.whatsapp_url'), 'رابط واتساب'],
            ['address', config('site.fallback.address'), 'العنوان'],
            ['city', 'الرياض', 'المدينة'],
            ['country', 'المملكة العربية السعودية', 'الدولة'],
            ['locale', 'ar_SA', 'اللغة والمنطقة'],
            ['primary_service_area', 'حي المونسية وجميع أحياء الرياض', 'موقع النشاط الأساسي'],
            ['hero_eyebrow', 'فلاتر وتحلية المياه في الرياض', 'عبارة Hero العلوية'],
            ['hero_title', 'فلاتر وتحلية المياه بالرياض', 'عنوان Hero'],
            ['hero_description', 'تركيب وصيانة فلاتر ومحطات تحلية المياه', 'وصف Hero'],
            ['about_image', config('site.about_image'), 'صورة من نحن'],
            ['header_image', config('site.header_image'), 'صورة الهيدر بجوار اسم الموقع'],
            ['hero_image', config('site.hero_image'), 'صورة الهيرو في الصفحة الرئيسية'],
            ['inspection_cta_label', 'احجز الآن', 'نص زر الحجز'],
        ];

        foreach ($settings as [$key, $value, $label]) {
            Setting::query()->updateOrCreate(['key' => $key], [
                'value' => $value,
                'label' => $label,
                'group' => 'business',
                'type' => 'string',
                'is_public' => true,
            ]);
        }

        foreach ([
            ['bank_name', '', 'اسم البنك'],
            ['bank_account_name', '', 'اسم صاحب الحساب'],
            ['bank_account_number', '', 'رقم الحساب'],
            ['bank_iban', '', 'رقم الآيبان'],
            ['bank_transfer_instructions', 'بعد تسجيل الطلب حوّل المبلغ إلى الحساب المعتمد، ثم أرسل إشعار التحويل مع رقم الطلب عبر واتساب.', 'تعليمات التحويل البنكي'],
        ] as [$key, $value, $label]) {
            Setting::query()->firstOrCreate(['key' => $key], [
                'value' => $value,
                'label' => $label,
                'group' => 'checkout',
                'type' => 'string',
                'is_public' => true,
            ]);
        }

        foreach ([
            ['search_console_verification', '', 'رمز إثبات ملكية Google Search Console'],
            ['ga_measurement_id', '', 'معرّف Google Analytics 4'],
            ['logo_url', '', 'رابط شعار النشاط للبيانات المنظمة'],
        ] as [$key, $value, $label]) {
            Setting::query()->updateOrCreate(['key' => $key], [
                'value' => $value,
                'label' => $label,
                'group' => 'seo',
                'type' => 'string',
                'is_public' => true,
            ]);
        }

        foreach (config('theme.colors', []) as $key => $definition) {
            Setting::query()->updateOrCreate(['key' => $key], [
                'value' => $definition['default'],
                'label' => $definition['label'],
                'group' => 'appearance',
                'type' => 'string',
                'is_public' => true,
            ]);
        }
    }
}
