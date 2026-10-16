<?php

namespace Database\Seeders;

use App\Models\Voucher;
use Illuminate\Database\Seeder;

class VoucherSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['KOPIBARU10', 'percent', 10, 100000, 50000, 100, 0, null, now()->addMonths(2), true, 'Diskon 10% pelanggan baru'],
            ['HEMAT25K', 'fixed', 25000, 200000, null, null, 0, null, null, true, 'Potongan Rp25.000 untuk belanja Rp200.000+'],
            ['AKHIRBULAN', 'percent', 15, 150000, 75000, 50, 0, now()->addDays(5), now()->addDays(12), true, 'Promo akhir bulan (belum mulai)'],
            ['LAUNCHING20', 'percent', 20, 0, 40000, 30, 12, now()->subMonth(), now()->subDays(3), true, 'Promo peluncuran (sudah berakhir)'],
            ['HABIS5', 'fixed', 10000, 0, null, 5, 5, null, null, true, 'Kuota 5 orang (habis)'],
        ];
        foreach ($rows as [$c, $t, $v, $min, $max, $q, $used, $s, $e, $a, $n]) {
            Voucher::updateOrCreate(['code' => $c], ['type' => $t, 'value' => $v, 'min_subtotal' => $min, 'max_discount' => $max, 'quota' => $q, 'used_count' => $used, 'starts_at' => $s, 'expires_at' => $e, 'is_active' => $a, 'note' => $n]);
        }
    }
}
