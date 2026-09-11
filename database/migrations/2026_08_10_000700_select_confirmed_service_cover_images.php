<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $preferred = DB::table('service_images')
            ->where('file_name', 'tensile-structure-shade-riyadh-04.webp')
            ->first();

        if (! $preferred) {
            return;
        }

        DB::transaction(function () use ($preferred): void {
            DB::table('service_images')->where('service_id', $preferred->service_id)->update(['is_cover' => false]);
            DB::table('service_images')->where('id', $preferred->id)->update(['is_cover' => true]);
        });
    }

    public function down(): void
    {
        $preferred = DB::table('service_images')
            ->where('file_name', 'tensile-structure-shade-riyadh-01.webp')
            ->first();

        if (! $preferred) {
            return;
        }

        DB::transaction(function () use ($preferred): void {
            DB::table('service_images')->where('service_id', $preferred->service_id)->update(['is_cover' => false]);
            DB::table('service_images')->where('id', $preferred->id)->update(['is_cover' => true]);
        });
    }
};
