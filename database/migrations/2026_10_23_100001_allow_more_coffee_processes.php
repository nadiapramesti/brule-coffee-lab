<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom proses dulunya enum berisi lima nilai, jadi proses baru (mis. Carbonic
 * Maceration) ditolak database. Dijadikan string; daftar yang boleh dipilih
 * sekarang dijaga validasi di CoffeeForm::PROCESSES, bukan oleh skema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('traceabilities', function (Blueprint $table) {
            $table->string('process', 40)->change();
        });
    }

    public function down(): void
    {
        Schema::table('traceabilities', function (Blueprint $table) {
            $table->enum('process', ['Washed', 'Honey', 'Natural', 'Anaerobic', 'Wet-Hulled'])->change();
        });
    }
};
