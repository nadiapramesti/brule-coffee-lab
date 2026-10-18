<?php

use App\Livewire\Builder;
use App\Livewire\Cart;
use App\Livewire\Checkout;
use App\Livewire\Roastery;
use App\Livewire\Track;
use App\Livewire\Landing;
use Illuminate\Support\Facades\Route;

Route::get('/', Landing::class)->name('landing');
Route::get('/builder', Builder::class)->name('builder');

Route::get('/bantuan', \App\Livewire\StaticPage::class)->defaults('page', 'faq')->name('help');
Route::get('/pengiriman-pengembalian', \App\Livewire\StaticPage::class)->defaults('page', 'shipping')->name('policy.shipping');
Route::get('/syarat-ketentuan', \App\Livewire\StaticPage::class)->defaults('page', 'terms')->name('policy.terms');
Route::get('/kebijakan-privasi', \App\Livewire\StaticPage::class)->defaults('page', 'privacy')->name('policy.privacy');
Route::get('/sitemap.xml', function () {
    $urls = collect([
        route('landing'), route('builder'), route('gear'), route('track'), route('help'),
        route('policy.shipping'), route('policy.terms'), route('policy.privacy'),
    ])->merge(\App\Models\Coffee::active()->orderBy('id')->pluck('slug')->map(fn ($s) => route('builder', ['kopi' => $s])));

    return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
})->name('sitemap');
Route::get('/perlengkapan', \App\Livewire\Gear::class)->name('gear');
Route::get('/keranjang', Cart::class)->name('cart');
Route::get('/checkout', Checkout::class)->middleware('auth')->name('checkout');
Route::get('/akun', \App\Livewire\Account::class)->middleware('auth')->name('account');
Route::post('/midtrans/notification', \App\Http\Controllers\MidtransNotificationController::class)->name('midtrans.notification');
Route::get('/lacak/{code?}', Track::class)->name('track');
Route::get('/roastery/dashboard', Roastery::class)->middleware(['auth', 'roaster'])->name('roastery');
Route::get('/roastery/label', \App\Http\Controllers\Roastery\LabelController::class)->middleware(['auth', 'roaster'])->name('roastery.labels');

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', \App\Livewire\Admin\Dashboard::class)->name('admin.dashboard');
    Route::get('/pesanan', \App\Livewire\Admin\Orders::class)->name('admin.orders');
    Route::get('/laporan', \App\Livewire\Admin\Reports::class)->name('admin.reports');
    Route::get('/laporan/export', \App\Http\Controllers\Admin\ReportExportController::class)->name('admin.reports.export');
    Route::get('/pengguna', \App\Livewire\Admin\Users::class)->name('admin.users');
    Route::get('/log', \App\Livewire\Admin\ActivityLogs::class)->name('admin.logs');
    Route::get('/pengaturan', \App\Livewire\Admin\StoreSettings::class)->name('admin.settings');
    Route::get('/voucher', \App\Livewire\Admin\Vouchers::class)->name('admin.vouchers');
    Route::get('/ulasan', \App\Livewire\Admin\Reviews::class)->name('admin.reviews');
    Route::get('/stok', \App\Livewire\Admin\Stock::class)->name('admin.stock');
    Route::get('/pelanggan', \App\Livewire\Admin\Customers::class)->name('admin.customers');
    Route::get('/pesanan/export', \App\Http\Controllers\Admin\OrderExportController::class)->name('admin.orders.export');
    Route::get('/pesanan/{order:order_code}/cetak', \App\Http\Controllers\Admin\OrderPrintController::class)->name('admin.orders.print');
    Route::get('/kopi', \App\Livewire\Admin\CoffeeIndex::class)->name('admin.coffees');
    Route::get('/produk', \App\Livewire\Admin\ProductIndex::class)->name('admin.products');
});

Route::middleware('guest')->group(function () {
    Route::get('/auth/{provider}/redirect', [\App\Http\Controllers\Auth\SocialAuthController::class, 'redirect'])->name('social.redirect');
    Route::get('/auth/{provider}/callback', [\App\Http\Controllers\Auth\SocialAuthController::class, 'callback'])->name('social.callback');
});

Route::get('/bahasa/{locale}', function (string $locale) {
    if (in_array($locale, \App\Http\Middleware\SetLocale::SUPPORTED, true)) {
        session(['locale' => $locale]);
    }

    return redirect()->back(fallback: route('landing'));
})->where('locale', 'id|en')->name('locale');

// Tidak ada dasbor generik: tiap peran punya tempat kerjanya sendiri.
Route::get('dashboard', fn () => redirect()->route(match (true) {
    auth()->user()->isAdmin() => 'admin.dashboard',
    auth()->user()->isRoaster() => 'roastery',
    default => 'account',
}))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('profile', \App\Livewire\Profile::class)
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';

Route::post('/logout', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('landing');
})->middleware('auth')->name('logout');
