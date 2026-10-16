<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('roast_profile_id')->constrained()->restrictOnDelete();

            $table->unsignedSmallInteger('grams'); // 200 / 500 / 1000
            $table->enum('grind', [
                'whole_bean', 'v60', 'espresso', 'french_press', 'tubruk', 'aeropress'
            ])->default('whole_bean');

            // snapshot harga & nama, biar riwayat order gak berubah
            // kalau harga/produk diedit admin nanti
            $table->string('coffee_name_snapshot');
            $table->string('roast_level_snapshot');
            $table->unsignedInteger('price_per_100g_snapshot');
            $table->unsignedInteger('line_total');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
