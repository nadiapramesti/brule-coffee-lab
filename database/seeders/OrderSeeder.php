<?php

namespace Database\Seeders;

use App\Models\Coffee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RoastJournal;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 12 pesanan contoh dengan status campur, supaya antrian roastery
 * dan riwayat customer kelihatan hidup sejak migrate:fresh --seed.
 */
class OrderSeeder extends Seeder
{
    private const SHIPPING = 15_000;

    public function run(): void
    {
        $roaster = User::where('email', 'roaster@kromatik.test')->first();

        // [email, status, hari lalu, [[slug, level, gram, grind], ...]]
        $orders = [
            ['customer@kromatik.test', 'pending_payment', 0, [['ethiopia-yirgacheffe-washed', 'light', 200, 'v60']]],
            ['raka@kromatik.test',     'queued',          0, [['colombia-huila-honey', 'medium', 500, 'espresso']]],
            ['sinta@kromatik.test',    'queued',          0, [['kenya-aa-nyeri-washed', 'light', 200, 'v60'], ['flores-bajawa-washed', 'medium_light', 200, 'whole_bean']]],
            ['customer@kromatik.test', 'queued',          1, [['sumatra-mandheling-natural', 'dark', 1000, 'french_press']]],
            ['raka@kromatik.test',     'roasting',        1, [['java-preanger-anaerobic-natural', 'medium_light', 200, 'aeropress']]],
            ['sinta@kromatik.test',    'grinding',        1, [['gayo-aceh-wet-hulled', 'medium_dark', 500, 'tubruk']]],
            ['customer@kromatik.test', 'packing',         2, [['toraja-sapan-wet-hulled', 'medium', 200, 'v60']]],
            ['raka@kromatik.test',     'shipped',         3, [['ethiopia-yirgacheffe-washed', 'medium_light', 500, 'v60']]],
            ['customer@kromatik.test', 'completed',       6, [['colombia-huila-honey', 'medium_light', 200, 'v60'], ['kenya-aa-nyeri-washed', 'medium', 200, 'v60']]],
            ['sinta@kromatik.test',    'completed',       8, [['flores-bajawa-washed', 'medium', 500, 'espresso']]],
            ['raka@kromatik.test',     'completed',      11, [['sumatra-mandheling-natural', 'medium_dark', 1000, 'whole_bean']]],
            ['sinta@kromatik.test',    'cancelled',       5, [['java-preanger-anaerobic-natural', 'light', 200, 'v60']]],
        ];

        $createdItems = [];

        foreach ($orders as $i => [$email, $status, $daysAgo, $lines]) {
            $user = User::where('email', $email)->firstOrFail();
            $at   = now()->subDays($daysAgo)->setTime(9 + ($i % 8), 15 + $i * 3);
            $code = sprintf('KRM-%s-%04d', $at->format('Ymd'), $i + 1);

            $order = Order::firstOrNew(['order_code' => $code]);
            $order->items()->delete();

            $subtotal = 0;
            $rows = [];
            foreach ($lines as [$slug, $level, $grams, $grind]) {
                $coffee  = Coffee::where('slug', $slug)->firstOrFail();
                $profile = $coffee->roastProfiles()->where('level', $level)->firstOrFail();
                $line    = (int) ($profile->price_per_100g * $grams / 100);
                $subtotal += $line;
                $rows[] = [$coffee, $profile, $grams, $grind, $line];
            }

            $past = ! in_array($status, ['pending_payment', 'cancelled'], true);
            $step = array_search($status, Order::FLOW, true);

            $order->forceFill([
                'user_id'          => $user->id,
                'status'           => $status,
                'subtotal'         => $subtotal,
                'shipping_cost'    => self::SHIPPING,
                'total'            => $subtotal + self::SHIPPING,
                'recipient_name'   => $user->name,
                'recipient_phone'  => $user->phone,
                'shipping_address' => 'Jl. Contoh No. ' . (10 + $i) . ', Kota Contoh',
                'roaster_id'       => $step !== false && $step >= 1 ? $roaster?->id : null,
                'paid_at'          => $past ? $at->copy()->addMinutes(8) : null,
                'roasted_at'       => $step !== false && $step >= 2 ? $at->copy()->addHours(3) : null,
                'shipped_at'       => $step !== false && $step >= 4 ? $at->copy()->addDay() : null,
                'tracking_number'  => $step !== false && $step >= 4 ? 'JNE' . (8800000000 + $i * 137) : null,
                'created_at'       => $at,
                'updated_at'       => $at,
            ])->save();

            foreach ($rows as [$coffee, $profile, $grams, $grind, $line]) {
                $item = OrderItem::create([
                    'order_id'                => $order->id,
                    'roast_profile_id'        => $profile->id,
                    'grams'                   => $grams,
                    'grind'                   => $grind,
                    'coffee_name_snapshot'    => $coffee->name,
                    'roast_level_snapshot'    => $profile->level,
                    'price_per_100g_snapshot' => $profile->price_per_100g,
                    'line_total'              => $line,
                ]);

                if ($status === 'completed') {
                    $createdItems[] = [$user, $item, $coffee, $profile];
                }
            }
        }

        $this->journals($createdItems);
    }

    private function journals(array $items): void
    {
        $entries = [
            ['V60',       15, 250, 92, 170, 5, ['Jeruk', 'Bunga'],  'Bersih banget, asamnya manis. Seduhan kedua malah lebih enak.'],
            ['V60',       15, 250, 93, 175, 4, ['Beri'],            'Agak asam kalau airnya terlalu panas, turunin ke 91 derajat.'],
            ['Espresso',  18,  36, 93,  28, 4, ['Cokelat', 'Kacang'], 'Crema tebal, cocok buat latte.'],
            ['French Press', 30, 500, 94, 240, 5, ['Cokelat', 'Rempah'], 'Body tebal, pas buat pagi hari.'],
        ];

        foreach ($items as $n => [$user, $item, $coffee]) {
            [$method, $dose, $water, $temp, $time, $rating, $flavors, $note] = $entries[$n % count($entries)];

            RoastJournal::updateOrCreate(
                ['user_id' => $user->id, 'order_item_id' => $item->id],
                [
                    'coffee_id'        => $coffee->id,
                    'brew_method'      => $method,
                    'dose_gram'        => $dose,
                    'water_ml'         => $water,
                    'water_temp'       => $temp,
                    'brew_time_second' => $time,
                    'rating'           => $rating,
                    'flavor_detected'  => $flavors,
                    'notes'            => $note,
                    'is_public'        => true,
                ]
            );
        }
    }
}
