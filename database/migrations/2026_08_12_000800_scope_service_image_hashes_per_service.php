<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_images', function (Blueprint $table): void {
            $table->dropUnique(['content_hash']);
            $table->unique(['service_id', 'content_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('service_images', function (Blueprint $table): void {
            $table->dropUnique(['service_id', 'content_hash']);
            $table->unique('content_hash');
        });
    }
};
