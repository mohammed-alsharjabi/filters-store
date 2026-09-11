<?php

namespace Database\Seeders;

use App\Models\Material;
use Illuminate\Database\Seeder;

class MaterialCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $materials = [
            ['name' => 'شمعة ترسيب PP', 'excerpt' => 'مرحلة أولية لحجز الرمل والصدأ والرواسب الظاهرة.', 'description' => 'يحدد موعد تغييرها بحسب جودة المياه ومعدل الاستخدام وحالة الشمعة، وليس بالمدة وحدها.'],
            ['name' => 'فلتر كربون نشط', 'excerpt' => 'مرحلة لمعالجة الروائح والطعم وبعض المركبات المؤثرة في المياه.', 'description' => 'تختلف كفاءة الكربون بحسب النوع والسعة ومعدل التدفق، لذلك يطابق مع الجهاز والاستخدام الفعلي.'],
            ['name' => 'غشاء تناضح عكسي RO', 'excerpt' => 'الغشاء المسؤول عن خفض الأملاح الذائبة في أجهزة التحلية المنزلية.', 'description' => 'يُفحص ضغط التشغيل وجودة المياه الداخلة ونسبة الرفض قبل الحكم على الغشاء أو استبداله.'],
            ['name' => 'مضخة جهاز التحلية', 'excerpt' => 'تساعد الجهاز على الوصول إلى ضغط التشغيل المناسب للغشاء.', 'description' => 'يُفحص المحول والحساسات ومسار المياه قبل استبدال المضخة للتأكد من سبب ضعف الضغط.'],
            ['name' => 'خزان وصنبور التحلية', 'excerpt' => 'ملحقات تخزين وتقديم المياه النقية داخل المنزل.', 'description' => 'تُراجع سعة الخزان وضغط الهواء وسلامة الصنبور والوصلات عند التركيب أو الصيانة.'],
            ['name' => 'هوزنج جامبو', 'excerpt' => 'حاوية كبيرة لشمعات تنقية مياه الخزان أو الخط الرئيسي.', 'description' => 'يُختار المقاس وعدد المراحل بحسب التدفق والمساحة المتاحة وطبيعة الرواسب في المياه.'],
            ['name' => 'فوهات رذاذ وضباب', 'excerpt' => 'فوهات تحول المياه إلى رذاذ دقيق ضمن النظام المخصص.', 'description' => 'يعتمد الأداء على مقاس الفوهة والضغط وجودة المياه وتوزيع النقاط وبرنامج التنظيف.'],
        ];

        Material::query()->whereNotIn('name', collect($materials)->pluck('name'))->update(['is_active' => false]);

        foreach ($materials as $material) {
            Material::query()->updateOrCreate(['name' => $material['name']], $material + [
                'is_active' => true,
                'is_price_published' => false,
                'price_from' => null,
                'price_to' => null,
            ]);
        }
    }
}
