<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add-on checkout: kemasan hadiah dan kartu ucapan. Harga add-on disimpan di
 * pesanan (addon_cost), jadi mengubah harga di Pengaturan tidak mengubah
 * pesanan lama. total = subtotal + shipping_cost + addon_cost - discount.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('gift_wrap')->default(false)->after('shipping_cost');
            $table->string('card_message', 160)->nullable()->after('gift_wrap');
            $table->unsignedInteger('addon_cost')->default(0)->after('card_message');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['gift_wrap', 'card_message', 'addon_cost']);
        });
    }
};
