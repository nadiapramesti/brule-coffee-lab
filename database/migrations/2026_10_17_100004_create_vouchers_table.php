<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 24)->unique();
            $table->string('type', 10);                       // percent | fixed
            $table->unsignedInteger('value');                 // persen (1-100) atau rupiah
            $table->unsignedInteger('min_subtotal')->default(0);
            $table->unsignedInteger('max_discount')->nullable(); // plafon untuk tipe persen
            $table->unsignedInteger('quota')->nullable();     // null = tanpa batas
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('note', 120)->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('discount')->default(0)->after('shipping_cost');
            $table->foreignId('voucher_id')->nullable()->after('discount')->constrained()->nullOnDelete();
            $table->string('voucher_code', 24)->nullable()->after('voucher_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voucher_id');
            $table->dropColumn(['discount', 'voucher_code']);
        });
        Schema::dropIfExists('vouchers');
    }
};
