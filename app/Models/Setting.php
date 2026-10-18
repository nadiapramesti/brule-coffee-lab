<?php

namespace App\Models;

use ArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/** Pengaturan toko (key-value). Nilai bawaan ada di DEFAULTS, jadi toko tetap jalan walau tabel masih kosong. */
class Setting extends Model
{
    public $incrementing = false;
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'store_name' => 'BRÛLÉ',
        'store_tagline' => 'Specialty Coffee Lab',
        'store_city' => 'Bandung',
        'store_address' => 'Roastery BRÛLÉ, Bandung, Jawa Barat',
        'contact_email' => 'halo@brule.test',
        'contact_whatsapp' => '6281200000000',
        'instagram' => 'brule.coffeelab',
        'shipping_flat' => '15000',
        'free_shipping_from' => '500000',
        'low_stock_gram' => '5000',
        'cod_enabled' => '1',
        'cod_max' => '0',
    ];

    /** Dimuat sekali per request/test (hidup di container, jadi ikut reset antar test). */
    private static function bag(): ArrayObject
    {
        if (! app()->bound('krm.settings')) {
            $stored = [];
            try {
                $stored = Schema::hasTable('settings') ? self::pluck('value', 'key')->all() : [];
            } catch (\Throwable) {
            }
            app()->instance('krm.settings', new ArrayObject(array_merge(self::DEFAULTS, array_filter($stored, fn ($v) => $v !== null))));
        }

        return app('krm.settings');
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::bag()[$key] ?? $default ?? self::DEFAULTS[$key] ?? null;
    }

    public static function int(string $key): int
    {
        return (int) self::get($key);
    }

    /** @param array<string, scalar|null> $values */
    public static function put(array $values): void
    {
        foreach ($values as $k => $v) {
            if (array_key_exists($k, self::DEFAULTS)) {
                self::updateOrCreate(['key' => $k], ['value' => (string) $v]);
            }
        }
        app()->forgetInstance('krm.settings');
    }

    public static function whatsappUrl(): string
    {
        return 'https://wa.me/' . preg_replace('/\D/', '', self::get('contact_whatsapp'));
    }
}
