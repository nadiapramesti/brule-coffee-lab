<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coffee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);            // initial, restock, sale, cancel, adjustment, waste
            $table->integer('grams');              // bertanda: + masuk, - keluar (gram biji mentah)
            $table->unsignedInteger('balance_after');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['coffee_id', 'created_at']);
            $table->index('type');
        });

        // Stok yang sudah ada dicatat sebagai saldo awal supaya riwayat tidak mulai dari nol.
        foreach (DB::table('coffees')->get(['id', 'stock_green_bean_gram']) as $c) {
            DB::table('stock_movements')->insert([
                'coffee_id' => $c->id, 'type' => 'initial', 'grams' => $c->stock_green_bean_gram,
                'balance_after' => $c->stock_green_bean_gram, 'reason' => 'Saldo awal saat fitur stok diaktifkan',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
