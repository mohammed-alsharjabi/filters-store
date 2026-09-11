<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // القيمة تُدار من لوحة الإدارة بعد ذلك، لذلك لا يُستبدل إعداد موجود.
        DB::table('settings')->insertOrIgnore([
            'key' => 'hero_image',
            'value' => config('site.hero_image'),
            'type' => 'string',
            'group' => 'business',
            'label' => 'صورة الهيرو في الصفحة الرئيسية',
            'is_public' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Cache::forget('site.settings.all');
        Cache::forget('site.settings.public');
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'hero_image')->delete();
        Cache::forget('site.settings.all');
        Cache::forget('site.settings.public');
    }
};
