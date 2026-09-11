<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (config('theme.colors', []) as $key => $definition) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $definition['default'],
                    'type' => 'string',
                    'group' => 'appearance',
                    'label' => $definition['label'],
                    'is_public' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        foreach ([
            'hero_eyebrow' => ['حلول تظليل وخصوصية داخل الرياض', 'حلول هندسية للمساحات الخارجية'],
            'hero_title' => ['مظلات وسواتر الرياض بتصاميم عصرية وتنفيذ احترافي', 'نصمم الظل كجزء من المكان'],
            'hero_description' => ['خدمات المظلات والسواتر والإنشاءات المعدنية تبدأ بفهم الموقع والمقاسات وتنتهي بعرض واضح لنطاق العمل.', 'مظلات وسواتر وهياكل مصممة لمناخ الرياض ومساحة مشروعك.'],
        ] as $key => [$previous, $updated]) {
            DB::table('settings')->where('key', $key)->where('value', $previous)->update(['value' => $updated, 'updated_at' => $now]);
        }

        if (Schema::hasTable('services')) {
            DB::table('services')
                ->whereIn('name', config('site.launch_services', []))
                ->where('status', 'draft')
                ->update(['status' => 'published', 'published_at' => $now, 'updated_at' => $now]);
        }

        if (Schema::hasTable('articles')) {
            DB::table('articles')
                ->where('status', 'draft')
                ->update(['status' => 'published', 'published_at' => $now, 'updated_at' => $now]);
        }

        Cache::forget('site.settings.all');
        Cache::forget('site.settings.public');
        Cache::forget('navigation.service-categories');
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', array_keys(config('theme.colors', [])))->delete();
        Cache::forget('site.settings.all');
        Cache::forget('site.settings.public');
    }
};
