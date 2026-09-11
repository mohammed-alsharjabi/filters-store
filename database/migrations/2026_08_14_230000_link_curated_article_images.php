<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (config('site.article_featured_images', []) as $title => $path) {
            DB::table('articles')->where('title', $title)->update([
                'featured_image' => $path,
                'featured_image_alt' => $title,
                'featured_image_caption' => 'صورة توضيحية لمقال '.$title,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        foreach (config('site.article_featured_images', []) as $title => $path) {
            DB::table('articles')
                ->where('title', $title)
                ->where('featured_image', $path)
                ->update([
                    'featured_image' => null,
                    'featured_image_alt' => null,
                    'featured_image_caption' => null,
                    'updated_at' => now(),
                ]);
        }
    }
};
