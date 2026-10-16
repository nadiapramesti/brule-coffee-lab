<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traceabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coffee_id')->constrained()->cascadeOnDelete();
            $table->string('farmer');
            $table->string('region');
            $table->unsignedSmallInteger('altitude'); // simpan angka: 1950
            $table->string('varietal');
            $table->enum('process', ['Washed', 'Honey', 'Natural', 'Anaerobic']);
            $table->date('harvest_date')->nullable();
            $table->string('q_grader')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traceabilities');
    }
};
