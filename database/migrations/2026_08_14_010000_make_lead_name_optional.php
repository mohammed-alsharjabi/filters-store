<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->string('name')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('leads')->whereNull('name')->update(['name' => 'زائر الموقع']);

        Schema::table('leads', function (Blueprint $table): void {
            $table->string('name')->nullable(false)->change();
        });
    }
};
