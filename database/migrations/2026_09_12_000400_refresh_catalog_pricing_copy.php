<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $keys = [
            'mpure-white', 'aqua-plus', 'aqua-plus-gauge', 'shower', 'shower-angle',
            'mpure-blue', 'pressure-tank', 'aqua-commercial', 'mpure-installed', 'aqua-kit',
            'enpure-jumbo', 'angel-complete', 'helsy', 'aquapure-black', 'aqua-taiwan-kit',
            'pureena', 'super-pro-8', 'ivlife', 'bwater',
        ];

        $products = DB::table('products')->whereIn('catalog_source_key', $keys)->get(['id', 'excerpt', 'description']);
        foreach ($products as $product) {
            $excerpt = str_replace(
                'السعر والتوفر والمواصفات النهائية عند الطلب.',
                'السعر المعروض قابل للتحديث، ويُؤكد المتجر التوفر والمواصفات قبل تنفيذ الطلب.',
                (string) $product->excerpt
            );
            $description = str_replace(
                'بيانات السعر والمخزون والمواصفات التفصيلية متروكة للإدارة حتى اعتمادها، ويمكن تعديلها بالكامل من لوحة التحكم.',
                'السعر المعروض قابل للتعديل من لوحة التحكم، وتُؤكد المواصفات والتوفر قبل تنفيذ الطلب.',
                (string) $product->description
            );
            DB::table('products')->where('id', $product->id)->update([
                'excerpt' => $excerpt,
                'description' => $description,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // تحديث تحريري آمن؛ لا نعيد نصوصًا قديمة فوق تعديلات مدير المتجر.
    }
};
