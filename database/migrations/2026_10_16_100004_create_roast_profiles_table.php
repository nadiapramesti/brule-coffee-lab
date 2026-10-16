<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roast_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coffee_id')->constrained()->cascadeOnDelete();
            $table->enum('level', [
                'light', 'medium_light', 'medium', 'medium_dark', 'dark'
            ]);
            $table->unsignedSmallInteger('agtron')->nullable(); // 95 = light, 45 = dark
            $table->json('flavor_values');  // {floral,citrus,berry,chocolate,nutty,spice} 0-100
            $table->json('tasting_notes');  // ["Jasmine","Blueberry","Bergamot"]
            $table->json('roast_curve')->nullable(); // [{t:0,temp:200},...]
            $table->unsignedInteger('price_per_100g'); // rupiah, integer
            $table->timestamps();

            $table->unique(['coffee_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roast_profiles');
    }
};
