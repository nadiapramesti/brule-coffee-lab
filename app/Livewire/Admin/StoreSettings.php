<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.panel')]
#[Title('Pengaturan - KROMATIK')]
class StoreSettings extends Component
{
    public string $storeName = '';
    public string $tagline = '';
    public string $city = '';
    public string $address = '';
    public string $email = '';
    public string $whatsapp = '';
    public string $instagram = '';
    public string $shippingFlat = '';
    public string $freeFrom = '';
    public string $lowStock = '';
    public ?string $notice = null;

    public function mount(): void
    {
        $this->fill([
            'storeName' => Setting::get('store_name'), 'tagline' => Setting::get('store_tagline'),
            'city' => Setting::get('store_city'), 'address' => Setting::get('store_address'),
            'email' => Setting::get('contact_email'),
            'whatsapp' => '0' . ltrim(preg_replace('/^62/', '', Setting::get('contact_whatsapp')), '0'),
            'instagram' => Setting::get('instagram'),
            'shippingFlat' => Setting::get('shipping_flat'), 'freeFrom' => Setting::get('free_shipping_from'),
            'lowStock' => Setting::get('low_stock_gram'),
        ]);
    }

    public function save(): void
    {
        // Rapikan input sebelum validasi: @handle / URL Instagram, +62 / 62 -> 08 pada WhatsApp.
        $this->instagram = preg_replace('#^(https?://)?(www\.)?instagram\.com/#i', '', trim($this->instagram));
        $this->instagram = trim(ltrim($this->instagram, '@'), '/ ');
        $wa = preg_replace('/[\s\-().]/', '', $this->whatsapp);
        $this->whatsapp = preg_match('/^(\+62|62)8/', $wa) ? '0' . preg_replace('/^(\+62|62)/', '', $wa) : $wa;

        $d = $this->validate([
            'storeName' => ['required', 'string', 'max:40'],
            'tagline' => ['required', 'string', 'max:60'],
            'city' => ['required', 'string', 'max:40'],
            'address' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:80'],
            'whatsapp' => ['required', 'regex:/^08[0-9]{8,12}$/'],
            'instagram' => ['required', 'regex:/^[A-Za-z0-9._]{1,30}$/'],
            'shippingFlat' => ['required', 'integer', 'min:0', 'max:1000000'],
            'freeFrom' => ['required', 'integer', 'min:0', 'max:100000000'],
            'lowStock' => ['required', 'integer', 'min:0', 'max:1000000'],
        ], [
            'required' => __('Wajib diisi.'),
            'email.email' => __('Format email belum benar. Contoh: halo@kromatik.id'),
            'whatsapp.regex' => __('Nomor WhatsApp tidak valid. Contoh: 0812xxxxxxxx'),
            'instagram.regex' => __('Username Instagram hanya huruf, angka, titik, dan garis bawah.'),
            'integer' => __('Harus angka bulat.'),
            'min' => __('Tidak boleh negatif.'),
            'max' => __('Angkanya terlalu besar.'),
        ]);

        Setting::put([
            'store_name' => trim($d['storeName']), 'store_tagline' => trim($d['tagline']), 'store_city' => trim($d['city']),
            'store_address' => trim($d['address']), 'contact_email' => trim($d['email']),
            'contact_whatsapp' => '62' . substr($d['whatsapp'], 1), 'instagram' => $d['instagram'],
            'shipping_flat' => (int) $d['shippingFlat'], 'free_shipping_from' => (int) $d['freeFrom'], 'low_stock_gram' => (int) $d['lowStock'],
        ]);
        ActivityLog::record('settings.updated', null, 'Pengaturan toko diperbarui');

        $this->notice = __('Pengaturan tersimpan. Footer, checkout, dan invoice langsung memakai nilai baru.');
    }

    public function render()
    {
        return view('livewire.admin.store-settings');
    }
}
