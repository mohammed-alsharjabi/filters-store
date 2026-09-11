<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['سواتر حديد', 'سواتر خشب', 'سواتر قماش'] as $name) {
            $path = config('site.service_featured_images.'.$name);

            if (! $path) {
                continue;
            }

            DB::table('services')->where('name', $name)->update([
                'featured_image' => $path,
                'featured_image_alt' => $name.' في الرياض',
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('services')
            ->whereIn('name', ['سواتر حديد', 'سواتر خشب', 'سواتر قماش'])
            ->update([
                'featured_image' => 'services/sawater-riyadh.webp',
                'updated_at' => now(),
            ]);
    }
};
