<?php

use App\Livewire\Account;
use App\Livewire\Admin\Reviews as AdminReviews;
use App\Models\Coffee;
use App\Models\Order;
use App\Models\Review;
use App\Models\RoastProfile;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
    $this->user = User::where('email', 'customer@kromatik.test')->first();
    $this->profile = RoastProfile::first();
    $this->coffee = $this->profile->coffee;
    // pesanan seed milik customer bisa ikut berisi kopi ini, jadi kosongkan dulu
    Review::query()->delete();
});

function revOrder(User $u, RoastProfile $p, string $status): Order
{
    $o = Order::placeMany($u, [['profile' => $p, 'grams' => 200, 'grind' => 'whole_bean']],
        ['name' => $u->name, 'phone' => '081234567890', 'address' => 'Jl. Dago No. 25, Bandung 40135'], null, 'online');
    $o->update(['status' => $status]);

    return $o;
}

it('only lets buyers with a completed order review a coffee', function () {
    $stranger = User::factory()->create();
    Livewire::actingAs($stranger)->test(Account::class)->call('openReview', $this->coffee->id)->assertForbidden();

    revOrder($stranger, $this->profile, 'queued'); // belum selesai
    Livewire::actingAs($stranger)->test(Account::class)->call('openReview', $this->coffee->id)->assertForbidden();

    revOrder($stranger, $this->profile, 'completed');
    Livewire::actingAs($stranger)->test(Account::class)->call('openReview', $this->coffee->id)->assertOk();
});

it('saves one review per user per coffee and updates it on edit', function () {
    revOrder($this->user, $this->profile, 'completed');

    $c = Livewire::actingAs($this->user)->test(Account::class)
        ->call('openReview', $this->coffee->id)->set('rating', 4)->set('reviewBody', 'Wangi sekali')->call('saveReview')->assertHasNoErrors();
    $c->call('openReview', $this->coffee->id)->assertSet('rating', 4)->set('rating', 5)->call('saveReview');

    expect(Review::count())->toBe(1)->and(Review::first()->rating)->toBe(5);
});

it('validates the rating', function () {
    revOrder($this->user, $this->profile, 'completed');

    Livewire::actingAs($this->user)->test(Account::class)
        ->call('openReview', $this->coffee->id)->call('saveReview')->assertHasErrors(['rating'])
        ->set('rating', 9)->call('saveReview')->assertHasErrors(['rating'])
        ->set('rating', 3)->set('reviewBody', str_repeat('a', 601))->call('saveReview')->assertHasErrors(['reviewBody']);
});

it('shows rating and reviews in the catalog, with a shortened reviewer name', function () {
    $u = User::factory()->create(['name' => 'Nadia Pramesti']);
    Review::create(['user_id' => $u->id, 'coffee_id' => $this->coffee->id, 'rating' => 5, 'body' => 'Kopinya juara']);

    $this->get('/')->assertSee('Kopinya juara')->assertSee('Nadia P.')->assertSee('Pembeli terverifikasi')->assertDontSee('Pramesti');
});

it('hides moderated reviews from the shop and from the rating', function () {
    $u = User::factory()->create();
    $r = Review::create(['user_id' => $u->id, 'coffee_id' => $this->coffee->id, 'rating' => 1, 'body' => 'Konten tidak pantas']);
    $admin = User::where('email', 'admin@kromatik.test')->first();

    Livewire::actingAs($admin)->test(AdminReviews::class)->assertSee('Konten tidak pantas')->call('toggle', $r->id);

    expect($r->fresh()->is_hidden)->toBeTrue();
    $this->get('/')->assertDontSee('Konten tidak pantas');
    expect($this->coffee->visibleReviews()->count())->toBe(0);
});

it('keeps the reviews admin page away from non-admins', function () {
    $this->get('/admin/ulasan')->assertRedirect('/login');
    $this->actingAs($this->user)->get('/admin/ulasan')->assertForbidden();
    $this->actingAs(User::where('email', 'admin@kromatik.test')->first())->get('/admin/ulasan')->assertOk();
});
