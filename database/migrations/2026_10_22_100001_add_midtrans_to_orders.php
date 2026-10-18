<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('snap_token', 100)->nullable()->after('payment_method');
            $table->string('midtrans_order_id', 60)->nullable()->unique()->after('snap_token');
            $table->string('payment_channel', 40)->nullable()->after('midtrans_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['midtrans_order_id']);
            $table->dropColumn(['snap_token', 'midtrans_order_id', 'payment_channel']);
        });
    }
};
