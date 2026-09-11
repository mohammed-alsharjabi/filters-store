<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class ServiceAreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            [
                'name' => 'حي المونسية',
                'excerpt' => 'موقع النشاط الأساسي ونقطة الانطلاق لخدمات تركيب وصيانة فلاتر وتحلية المياه في الرياض.',
                'content' => "يقع موقع النشاط في حي المونسية بمدينة الرياض، ونستقبل طلبات تركيب وصيانة فلاتر المياه وأجهزة ومحطات التحلية من مختلف أحياء المدينة.\n\nأرسل نوع الجهاز أو صورة واضحة له، ووصف المشكلة إن كانت الخدمة صيانة، لنحدد المعلومات المطلوبة قبل الزيارة.\n\nتُحدد الخدمة والموعد بعد مراجعة التفاصيل والتواصل مع العميل.",
                'is_primary' => true,
                'status' => 'published',
                'meta_title' => 'فلاتر وتحلية المياه في حي المونسية بالرياض',
            ],
            [
                'name' => 'شمال الرياض',
                'excerpt' => 'تركيب وصيانة فلاتر وأجهزة تحلية المياه في أحياء شمال الرياض حسب الموعد المتاح.',
                'content' => 'أرسل الحي ونوع الجهاز وصورة الفلتر أو محطة التحلية لتجهيز طلب الخدمة قبل تحديد موعد الزيارة.',
                'is_primary' => false,
                'status' => 'draft',
                'meta_title' => 'فلاتر مياه في شمال الرياض',
            ],
            [
                'name' => 'شرق الرياض',
                'excerpt' => 'طلبات تركيب وصيانة الفلاتر والتحلية في شرق الرياض، مع انطلاق الخدمة من حي المونسية.',
                'content' => 'نراجع نوع الفلتر والمشكلة وموقع العميل في شرق الرياض قبل تأكيد موعد الزيارة ومتطلبات الصيانة.',
                'is_primary' => false,
                'status' => 'draft',
                'meta_title' => 'فلاتر مياه في شرق الرياض',
            ],
            [
                'name' => 'غرب الرياض',
                'excerpt' => 'خدمات فلاتر وتحلية المياه في غرب الرياض بعد مراجعة نوع الطلب وتوفر الموعد.',
                'content' => 'تساعد صورة الجهاز واسم الحي ووصف العطل على تحديد القطع والفحص المطلوبين قبل الزيارة.',
                'is_primary' => false,
                'status' => 'draft',
                'meta_title' => 'فلاتر مياه في غرب الرياض',
            ],
            [
                'name' => 'جنوب الرياض',
                'excerpt' => 'تركيب وصيانة فلاتر المياه المنزلية ومحطات التحلية في جنوب الرياض.',
                'content' => 'أرسل موقعك ونوع الفلتر أو المحطة والخدمة المطلوبة ليتم ترتيب التواصل والموعد المناسب.',
                'is_primary' => false,
                'status' => 'draft',
                'meta_title' => 'فلاتر مياه في جنوب الرياض',
            ],
        ];

        Area::query()->update(['is_primary' => false]);

        foreach ($areas as $data) {
            $area = Area::query()->updateOrCreate(['name' => $data['name']], [
                'excerpt' => $data['excerpt'],
                'content' => $data['content'],
                'is_active' => true,
                'is_primary' => $data['is_primary'],
                'status' => $data['status'],
                'published_at' => $data['status'] === 'published' ? now() : null,
            ]);

            $area->seo()->updateOrCreate([], [
                'meta_title' => $data['meta_title'],
                'meta_description' => $data['excerpt'],
                'robots' => $data['status'] === 'published' ? 'index,follow,max-image-preview:large' : 'noindex,follow',
                'schema_type' => 'WebPage',
            ]);
        }
    }
}
