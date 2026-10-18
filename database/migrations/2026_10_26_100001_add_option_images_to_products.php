<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Foto per pilihan (mis. dripper putih vs hitam): {"Hitam": "/products/v60-hitam-final.jpg"}. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', fn (Blueprint $t) => $t->json('option_images')->nullable()->after('image'));
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('option_images'));
    }
};
