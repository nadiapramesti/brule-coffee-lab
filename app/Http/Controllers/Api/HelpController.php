<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Livewire\StaticPage;

/** Halaman bantuan untuk aplikasi: FAQ, pengiriman, syarat, privasi. Isinya sama dengan web, mengikuti bahasa aplikasi. */
class HelpController extends Controller
{
    public function show(string $page)
    {
        abort_unless(array_key_exists($page, StaticPage::PAGES), 404);

        return ['data' => StaticPage::data($page)];
    }
}
