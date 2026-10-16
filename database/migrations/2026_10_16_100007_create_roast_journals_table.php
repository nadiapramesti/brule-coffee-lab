<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roast_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()
                  ->constrained()->nullOnDelete();
            $table->foreignId('coffee_id')->constrained()->cascadeOnDelete();

            $table->string('brew_method')->nullable(); // V60, Espresso
            $table->unsignedSmallInteger('dose_gram')->nullable();
            $table->unsignedSmallInteger('water_ml')->nullable();
            $table->unsignedSmallInteger('water_temp')->nullable();
            $table->unsignedSmallInteger('brew_time_second')->nullable();

            $table->unsignedTinyInteger('rating')->nullable(); // 1-5
            $table->json('flavor_detected')->nullable(); // rasa yang user rasain
            $table->text('notes')->nullable();
            $table->boolean('is_public')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roast_journals');
    }
};
