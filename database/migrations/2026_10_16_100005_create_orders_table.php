<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique(); // KRM-20261016-0001
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->enum('status', [
                'pending_payment',
                'queued',      // masuk antrian roastery
                'roasting',
                'grinding',
                'packing',
                'shipped',
                'completed',
                'cancelled',
            ])->default('pending_payment');

            $table->unsignedInteger('subtotal')->default(0);
            $table->unsignedInteger('shipping_cost')->default(0);
            $table->unsignedInteger('total')->default(0);

            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->text('shipping_address');
            $table->string('tracking_number')->nullable();

            $table->foreignId('roaster_id')->nullable()
                  ->constrained('users')->nullOnDelete();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('roasted_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
