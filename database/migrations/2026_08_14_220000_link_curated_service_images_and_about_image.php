<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (config('site.service_featured_images', []) as $serviceName => $path) {
            DB::table('services')->where('name', $serviceName)->update([
                'featured_image' => $path,
                'featured_image_alt' => $serviceName.' في الرياض',
                'featured_image_caption' => 'خدمة '.$serviceName.' في الرياض',
                'updated_at' => $now,
            ]);
        }

        DB::table('settings')->updateOrInsert(
            ['key' => 'about_image'],
            [
                'value' => config('site.about_image'),
                'type' => 'string',
                'group' => 'business',
                'label' => 'صورة من نحن',
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        Cache::forget('site.settings.all');
        Cache::forget('site.settings.public');
    }

    public function down(): void
    {
        DB::table('services')
            ->whereIn('name', array_keys(config('site.service_featured_images', [])))
            ->update([
                'featured_image' => null,
                'featured_image_alt' => null,
                'featured_image_caption' => null,
                'updated_at' => now(),
            ]);

        DB::table('settings')->where('key', 'about_image')->delete();
        Cache::forget('site.settings.all');
        Cache::forget('site.settings.public');
    }
};
