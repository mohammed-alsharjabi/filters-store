<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_metadata', function (Blueprint $table) {
            $table->string('focus_keyword')->nullable()->index()->after('meta_description');
            $table->text('related_terms')->nullable()->after('focus_keyword');
            $table->longText('internal_links')->nullable()->after('related_terms');
        });

        foreach (['service_categories', 'services', 'materials', 'areas', 'articles'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('featured_image_alt')->nullable()->after('featured_image');
                $table->text('featured_image_caption')->nullable()->after('featured_image_alt');
            });
        }

        Schema::table('services', function (Blueprint $table) {
            $table->string('video_url', 2048)->nullable()->after('featured_image_caption');
            $table->string('video_title')->nullable()->after('video_url');
            $table->string('video_thumbnail')->nullable()->after('video_title');
            $table->unsignedInteger('video_duration_seconds')->nullable()->after('video_thumbnail');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->string('video_url', 2048)->nullable()->after('featured_image_caption');
            $table->string('video_title')->nullable()->after('video_url');
            $table->string('video_thumbnail')->nullable()->after('video_title');
            $table->unsignedInteger('video_duration_seconds')->nullable()->after('video_thumbnail');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('status', 30)->default('draft')->index()->after('is_published');
            $table->string('video_url', 2048)->nullable()->after('description');
            $table->string('video_title')->nullable()->after('video_url');
            $table->string('video_thumbnail')->nullable()->after('video_title');
            $table->unsignedInteger('video_duration_seconds')->nullable()->after('video_thumbnail');
        });
        DB::table('projects')->where('is_published', true)->update(['status' => 'published']);

        Schema::table('project_images', function (Blueprint $table) {
            $table->text('caption')->nullable()->after('alt_text');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->decimal('area_size', 10, 2)->nullable()->after('area');
            $table->longText('whatsapp_message')->nullable()->after('source_url');
        });
        DB::table('leads')->where('status', 'qualified')->update(['status' => 'inspection']);
        DB::table('leads')->where('status', 'spam')->update(['status' => 'closed']);

        Schema::create('lead_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_images');

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['area_size', 'whatsapp_message']);
        });

        Schema::table('project_images', function (Blueprint $table) {
            $table->dropColumn('caption');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['status', 'video_url', 'video_title', 'video_thumbnail', 'video_duration_seconds']);
        });

        foreach (['services', 'articles'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['video_url', 'video_title', 'video_thumbnail', 'video_duration_seconds']);
            });
        }

        foreach (['service_categories', 'services', 'materials', 'areas', 'articles'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['featured_image_alt', 'featured_image_caption']);
            });
        }

        Schema::table('seo_metadata', function (Blueprint $table) {
            $table->dropColumn(['focus_keyword', 'related_terms', 'internal_links']);
        });
    }
};
