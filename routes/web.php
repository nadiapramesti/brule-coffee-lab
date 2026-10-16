<?php

use App\Livewire\Builder;
use App\Livewire\Checkout;
use App\Livewire\Roastery;
use App\Livewire\Track;
use App\Livewire\Landing;
use Illuminate\Support\Facades\Route;

Route::get('/', Landing::class)->name('landing');
Route::get('/builder', Builder::class)->name('builder');

Route::get('/checkout', Checkout::class)->middleware('auth')->name('checkout');
Route::get('/lacak/{code?}', Track::class)->name('track');
Route::get('/roastery/dashboard', Roastery::class)->middleware(['auth', 'roaster'])->name('roastery');

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
Route::get('dashboard', fn () => redirect()->route(auth()->user()->isRoaster() ? 'roastery' : 'track'))
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
