<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Blend dua biji: profil kedua dan porsi (%) biji pertama. Tanpa FK supaya riwayat order tidak terkunci. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $t) {
            $t->unsignedBigInteger('blend_profile_id')->nullable()->after('roast_profile_id');
            $t->unsignedTinyInteger('blend_ratio')->nullable()->after('blend_profile_id');
        });
        Schema::table('cart_lines', function (Blueprint $t) {
            $t->unsignedBigInteger('blend_profile_id')->nullable()->after('roast_profile_id');
            $t->unsignedTinyInteger('blend_ratio')->nullable()->after('blend_profile_id');
        });
    }

    public function down(): void
    {
        Schema::table('cart_lines', fn (Blueprint $t) => $t->dropColumn(['blend_profile_id', 'blend_ratio']));
        Schema::table('order_items', fn (Blueprint $t) => $t->dropColumn(['blend_profile_id', 'blend_ratio']));
    }
};
