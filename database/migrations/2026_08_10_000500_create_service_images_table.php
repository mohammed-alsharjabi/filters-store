<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('file_name')->nullable();
            $table->string('source_folder')->nullable();
            $table->string('original_path');
            $table->string('optimized_path')->nullable();
            $table->char('content_hash', 64)->unique();
            $table->string('title')->nullable();
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->unsignedInteger('original_width')->nullable();
            $table->unsignedInteger('original_height')->nullable();
            $table->unsignedBigInteger('original_file_size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->json('variants')->nullable();
            $table->unsignedTinyInteger('quality_score')->nullable();
            $table->string('quality_status', 40)->default('pending');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->string('processing_status', 40)->default('pending');
            $table->text('processing_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['service_id', 'processing_status', 'sort_order']);
            $table->index(['service_id', 'is_cover']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_images');
    }
};
