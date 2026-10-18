<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Produk non-kopi (alat seduh, penggiling, merchandise, aksesori). Stok dalam unit, bukan gram. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 90)->unique();
            $t->string('name', 100);
            $t->string('name_en', 100)->nullable();
            $t->string('category', 20)->index();
            $t->text('description');
            $t->text('description_en')->nullable();
            $t->unsignedInteger('price');
            $t->unsignedInteger('stock')->default(0);
            // Pilihan varian opsional (mis. ukuran S/M/L). Stok dihitung total per produk, bukan per varian.
            $t->string('option_label', 30)->nullable();
            $t->json('options')->nullable();
            $t->string('image')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
