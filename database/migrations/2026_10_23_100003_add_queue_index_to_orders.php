<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Antrian roastery menyaring per status lalu mengurutkan menurut waktu bayar, dan mencari lewat item pesanan.
 * Index ini menjaganya cepat saat pesanan ribuan. order_items.order_id hanya ditambah kalau belum ada
 * (MySQL sudah membuatnya lewat foreign key, SQLite tidak).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->index(['status', 'paid_at'], 'orders_status_paid_at_index'));

        if (! Schema::hasIndex('order_items', ['order_id'])) {
            Schema::table('order_items', fn (Blueprint $t) => $t->index('order_id', 'order_items_order_id_index'));
        }
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->dropIndex('orders_status_paid_at_index'));

        if (Schema::hasIndex('order_items', 'order_items_order_id_index')) {
            Schema::table('order_items', fn (Blueprint $t) => $t->dropIndex('order_items_order_id_index'));
        }
    }
};
