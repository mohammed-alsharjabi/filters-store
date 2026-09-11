<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->longText('types')->nullable()->after('content');
            $table->longText('use_cases')->nullable()->after('types');
            $table->longText('price_factors')->nullable()->after('use_cases');
            $table->longText('installation_steps')->nullable()->after('price_factors');
            $table->boolean('is_popular')->default(false)->index()->after('is_featured');
        });

        Schema::table('project_images', function (Blueprint $table) {
            $table->json('variants')->nullable()->after('path');
            $table->unsignedInteger('width')->nullable()->after('variants');
            $table->unsignedInteger('height')->nullable()->after('width');
            $table->string('stage', 30)->default('gallery')->index()->after('alt_text');
        });

        Schema::create('trust_items', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('value')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trust_items');
        Schema::table('project_images', function (Blueprint $table) {
            $table->dropColumn(['variants', 'width', 'height', 'stage']);
        });
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['types', 'use_cases', 'price_factors', 'installation_steps', 'is_popular']);
        });
    }
};
