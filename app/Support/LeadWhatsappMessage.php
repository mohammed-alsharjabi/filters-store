<?php

namespace App\Support;

use App\Models\Lead;

class LeadWhatsappMessage
{
    public static function make(Lead $lead): string
    {
        $quantity = $lead->area_size ? rtrim(rtrim((string) $lead->area_size, '0'), '.').' جهاز تقريبًا' : null;
        $source = $lead->source_url ?: url('/');
        $contactMethod = $lead->preferred_contact === 'phone' ? 'اتصال هاتفي' : 'واتساب';

        $lines = [
            '*طلب جديد من موقع فلاتر وتحلية المياه بالرياض*',
            'رقم الطلب: #'.$lead->id,
        ];

        if ($lead->name) {
            $lines[] = 'الاسم: '.$lead->name;
        }

        $lines[] = 'رقم الجوال: '.$lead->phone;

        if ($lead->service?->name) {
            $lines[] = 'الخدمة: '.$lead->service->name;
        }

        if ($lead->area) {
            $lines[] = 'الحي أو المنطقة: '.$lead->area;
        }

        if ($quantity) {
            $lines[] = 'عدد الأجهزة: '.$quantity;
        }

        $lines[] = 'التواصل المفضل: '.$contactMethod;

        if ($lead->message) {
            $lines[] = "\n*تفاصيل الطلب:*\n".$lead->message;
        }

        if (($lead->images_count ?? 0) > 0) {
            $lines[] = 'الصور المرفوعة: '.$lead->images_count.' (محفوظة في لوحة التحكم)';
        }

        $lines[] = "\nرابط الصفحة: ".$source;

        return implode("\n", $lines);
    }

    public static function url(string $message): string
    {
        $phone = preg_replace('/\D+/', '', (string) app(SettingsRepository::class)->all()['phone_e164']);

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }
}
