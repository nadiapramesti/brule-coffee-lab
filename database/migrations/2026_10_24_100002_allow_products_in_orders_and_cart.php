<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Item pesanan dan baris keranjang boleh berupa produk non-kopi: tidak punya profil sangrai maupun gilingan.
 * grind jadi string supaya produk bisa kosong; baris lama tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $t) {
            $t->unsignedBigInteger('roast_profile_id')->nullable()->change();
            $t->string('grind', 20)->nullable()->default('whole_bean')->change();
            $t->foreignId('product_id')->nullable()->after('roast_profile_id')->constrained()->nullOnDelete();
        });

        Schema::table('cart_lines', function (Blueprint $t) {
            $t->unsignedBigInteger('roast_profile_id')->nullable()->change();
            $t->string('kind', 10)->default('coffee')->after('line_id');
            $t->foreignId('product_id')->nullable()->after('roast_profile_id')->constrained()->cascadeOnDelete();
            $t->string('option', 40)->nullable()->after('grind');
        });
    }

    public function down(): void
    {
        Schema::table('cart_lines', function (Blueprint $t) {
            $t->dropConstrainedForeignId('product_id');
            $t->dropColumn(['kind', 'option']);
        });
        Schema::table('order_items', function (Blueprint $t) {
            $t->dropConstrainedForeignId('product_id');
        });
    }
};
