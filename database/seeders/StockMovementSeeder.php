<?php

namespace Database\Seeders;

use App\Models\Coffee;
use App\Models\OrderItem;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;

/**
 * Membangun ulang riwayat stok dari pesanan yang ada, supaya saldo akhirnya
 * persis sama dengan stok kopi sekarang. Aman dijalankan berulang.
 */
class StockMovementSeeder extends Seeder
{
    public function run(): void
    {
        StockMovement::query()->delete();

        foreach (Coffee::all() as $coffee) {
            $events = [];

            $items = OrderItem::with('order')
                ->whereHas('roastProfile', fn ($q) => $q->where('coffee_id', $coffee->id))->get();

            foreach ($items as $it) {
                $need = $coffee->greenBeanNeeded($it->grams);
                $events[] = ['sale', -$need, $it->order->created_at, $it->order_id];

                if ($it->order->status === 'cancelled') {
                    $events[] = ['cancel', $need, $it->order->cancelled_at ?? $it->order->created_at->copy()->addHour(), $it->order_id];
                }
            }

            usort($events, fn ($a, $b) => $a[2] <=> $b[2]);

            $net = array_sum(array_column($events, 1));
            $balance = max(0, $coffee->stock_green_bean_gram - $net);
            $start = ($events[0][2] ?? now())->copy()->subDays(2);

            StockMovement::create([
                'coffee_id' => $coffee->id, 'type' => 'initial', 'grams' => $balance, 'balance_after' => $balance,
                'reason' => 'Stok awal', 'created_at' => $start, 'updated_at' => $start,
            ]);

            foreach ($events as [$type, $grams, $at, $orderId]) {
                $balance += $grams;
                StockMovement::create([
                    'coffee_id' => $coffee->id, 'order_id' => $orderId, 'type' => $type, 'grams' => $grams,
                    'balance_after' => max(0, $balance), 'created_at' => $at, 'updated_at' => $at,
                ]);
            }
        }
    }
}
