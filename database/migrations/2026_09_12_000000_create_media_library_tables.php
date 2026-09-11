<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table): void {
            $table->id();
            $table->string('source_key')->unique();
            $table->string('name');
            $table->string('path')->unique();
            $table->string('alt_text');
            $table->text('caption')->nullable();
            $table->text('usage_notes')->nullable();
            $table->string('mime_type', 100)->default('image/webp');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('content_hash', 64)->unique();
            $table->json('variants')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('media_asset_service', function (Blueprint $table): void {
            $table->foreignId('media_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('context', 80)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['media_asset_id', 'service_id']);
        });

        Schema::create('article_media_asset', function (Blueprint $table): void {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_asset_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['article_id', 'media_asset_id']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('media_asset_id')->nullable()->after('product_category_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('media_asset_id');
        });
        Schema::dropIfExists('article_media_asset');
        Schema::dropIfExists('media_asset_service');
        Schema::dropIfExists('media_assets');
    }
};
