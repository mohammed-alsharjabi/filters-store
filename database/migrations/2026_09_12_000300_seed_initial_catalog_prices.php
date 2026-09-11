<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $prices = [
            'mpure-white' => 750,
            'aqua-plus' => 650,
            'aqua-plus-gauge' => 695,
            'shower' => 70,
            'shower-angle' => 70,
            'mpure-blue' => 750,
            'pressure-tank' => 150,
            'aqua-commercial' => 2200,
            'mpure-installed' => 999,
            'aqua-kit' => 650,
            'enpure-jumbo' => 750,
            'angel-complete' => 695,
            'helsy' => 650,
            'aquapure-black' => 750,
            'aqua-taiwan-kit' => 699,
            'pureena' => 699,
            'super-pro-8' => 799,
            'ivlife' => 850,
            'bwater' => 650,
        ];

        foreach ($prices as $key => $price) {
            DB::table('products')
                ->where('catalog_source_key', $key)
                ->whereNull('price')
                ->update(['price' => $price, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // لا نحذف أسعارًا ربما اعتمدها أو عدّلها مدير المتجر بعد تشغيل الترحيل.
    }
};
