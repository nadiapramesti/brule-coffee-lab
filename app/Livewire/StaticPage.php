<?php

namespace App\Livewire;

use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Halaman bantuan (FAQ) dan kebijakan. Isinya di lang/{id,en}/pages.php. */
#[Layout('layouts.kromatik')]
class StaticPage extends Component
{
    public const PAGES = [
        'faq' => 'help',
        'shipping' => 'policy.shipping',
        'terms' => 'policy.terms',
        'privacy' => 'policy.privacy',
    ];

    public string $page = 'faq';

    public function mount(string $page = 'faq'): void
    {
        abort_unless(array_key_exists($page, self::PAGES), 404);
        $this->page = $page;
    }

    /** Isi halaman dengan angka dan kontak terbaru dari Pengaturan toko. */
    private function content(): array
    {
        $free = Setting::int('free_shipping_from');
        $max = Setting::int('cod_max');
        $wa = '0'.ltrim(preg_replace('/^62/', '', Setting::get('contact_whatsapp')), '0');
        $rp = fn (int $n) => number_format($n, 0, ',', '.');

        $cod = ! Setting::int('cod_enabled')
            ? __('COD sedang tidak tersedia.')
            : ($max > 0 ? __('COD tersedia untuk pesanan sampai Rp:n.', ['n' => $rp($max)]) : __('COD tersedia untuk semua pesanan.'));

        $replace = [
            ':flat' => $rp(Setting::int('shipping_flat')),
            ':free' => $free > 0 ? __('Gratis ongkir untuk belanja mulai Rp:n.', ['n' => $rp($free)]) : '',
            ':cod' => $cod,
            ':wa' => $wa,
            ':email' => (string) Setting::get('contact_email'),
            ':address' => (string) Setting::get('store_address'),
        ];

        $data = trans('pages.'.$this->page);
        array_walk_recursive($data, function (&$v) use ($replace) {
            if (is_string($v)) {
                $v = trim(strtr($v, $replace));
            }
        });

        return $data;
    }

    public function render()
    {
        $c = $this->content();

        return view('livewire.static-page', ['c' => $c])->title($c['title'].' - BRÛLÉ');
    }
}
