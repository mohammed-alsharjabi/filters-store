<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (config('site.service_featured_images', []) as $name => $path) {
            DB::table('services')->where('name', $name)->update([
                'featured_image' => $path,
                'featured_image_alt' => $name.' في الرياض',
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $previous = [
            'الخيام' => 'services/tents-bayt-alshaar-riyadh.webp',
            'جلسات زجاجية' => 'services/geometric-tensile-shade-riyadh.webp',
            'الشترات والأبواب الإلكترونية' => 'service-images/62/shutters-riyadh-01.webp',
        ];

        foreach ($previous as $name => $path) {
            DB::table('services')->where('name', $name)->update([
                'featured_image' => $path,
                'updated_at' => now(),
            ]);
        }
    }
};
