<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->longText('materials_details')->nullable()->after('use_cases');
            $table->longText('advantages')->nullable()->after('materials_details');
            $table->longText('disadvantages')->nullable()->after('advantages');
            $table->longText('selection_tips')->nullable()->after('installation_steps');
            $table->text('cta')->nullable()->after('selection_tips');
            $table->string('status', 30)->default('draft')->index()->after('is_active');
            $table->unsignedInteger('sort_order')->default(0)->index()->after('is_popular');
        });

        Schema::table('areas', function (Blueprint $table) {
            $table->string('status', 30)->default('draft')->index()->after('is_active');
            $table->boolean('is_primary')->default(false)->index()->after('status');
            $table->timestamp('published_at')->nullable()->index()->after('is_primary');
        });

        // Preserve content that had already been explicitly dated for publication.
        DB::table('services')->whereNotNull('published_at')->update(['status' => 'published']);
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropColumn(['status', 'is_primary', 'published_at']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['materials_details', 'advantages', 'disadvantages', 'selection_tips', 'cta', 'status', 'sort_order']);
        });
    }
};
