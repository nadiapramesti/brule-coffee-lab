<?php

namespace Database\Seeders;

use App\Models\Notice;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Beberapa notifikasi contoh supaya bel tidak kosong saat demo. */
class NoticeSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@brule.test')->first();
        $customer = User::where('email', 'customer@brule.test')->first();
        $order = Order::latest('id')->first();
        if (! $admin || ! $order) {
            return;
        }

        $admin_url = route('admin.orders', ['order' => $order->order_code], false);
        $rows = [
            [$admin, 'order', 'Pesanan baru masuk', ':code · :n · Rp :t', ['code' => $order->order_code, 'n' => 'Raka', 't' => '128.000'], $admin_url, now()->subMinutes(6)],
            [$admin, 'stock', 'Stok menipis', ':n tersisa :kg kg', ['n' => 'Gayo Aceh', 'kg' => '4,6'], route('admin.stock', [], false), now()->subHours(3)],
            [$admin, 'queue', 'Pesanan masuk antrian', ':code · :item', ['code' => $order->order_code, 'item' => 'Toraja · Medium 250 g'], $admin_url, now()->subDay()],
        ];
        if ($customer) {
            $mine = Order::where('user_id', $customer->id)->latest('id')->first();
            $rows[] = [$customer, 'ship', 'Pesananmu dikirim', ':code · resi :r', ['code' => $mine?->order_code ?? 'BRL-0000', 'r' => 'JNE0012345678'], $mine ? route('track', $mine->order_code, false) : null, now()->subHours(2)];
        }

        foreach ($rows as [$u, $type, $title, $body, $params, $url, $at]) {
            Notice::create(['user_id' => $u->id, 'type' => $type, 'title' => $title, 'body' => $body, 'params' => $params, 'url' => $url, 'created_at' => $at, 'updated_at' => $at]);
        }
    }
}
