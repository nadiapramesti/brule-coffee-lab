<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('line_id', 16);
            $table->foreignId('roast_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('grams');
            $table->string('grind', 20);
            $table->unsignedTinyInteger('qty')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'line_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_lines');
    }
};
