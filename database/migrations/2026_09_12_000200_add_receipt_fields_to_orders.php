<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('receipt_path')->nullable()->after('payment_method');
            $table->string('receipt_original_name')->nullable()->after('receipt_path');
            $table->string('receipt_mime_type', 100)->nullable()->after('receipt_original_name');
            $table->unsignedBigInteger('receipt_size')->nullable()->after('receipt_mime_type');
            $table->timestamp('receipt_uploaded_at')->nullable()->after('receipt_size');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'receipt_path',
                'receipt_original_name',
                'receipt_mime_type',
                'receipt_size',
                'receipt_uploaded_at',
            ]);
        });
    }
};
