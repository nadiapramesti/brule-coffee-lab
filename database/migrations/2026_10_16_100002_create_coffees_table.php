<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coffees', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('story')->nullable();
            $table->text('story_en')->nullable(); // terjemahan Inggris dari story
            $table->string('image')->nullable();
            $table->unsignedInteger('stock_green_bean_gram')->default(0);
            $table->decimal('cupping_score', 4, 1)->nullable(); // 88.2
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coffees');
    }
};
