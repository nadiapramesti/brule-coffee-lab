<?php

use App\Livewire\Recommend;
use App\Models\Coffee;
use Database\Seeders\CoffeeSeeder;

/** Kuis rekomendasi lewat API: logika sama dengan Recommend::results() di web. */
beforeEach(function () {
    $this->seed([CoffeeSeeder::class]);
});

test('jawaban lengkap: maksimal 3 hasil dengan slug, level, harga per 100 g dan catatan rasa', function () {
    $res = $this->postJson('/api/recommend', ['drink' => 'hitam', 'taste' => 'buah', 'acid' => 'suka'])->assertOk();

    $rows = $res->json('data');
    expect($rows)->toBeArray()->not->toBeEmpty()->and(count($rows))->toBeLessThanOrEqual(3);
    foreach ($rows as $r) {
        expect($r)->toHaveKeys(['slug', 'name', 'profile_id', 'level', 'level_label', 'price_per_100g', 'tasting_notes', 'why', 'image']);
        expect(Coffee::where('slug', $r['slug'])->exists())->toBeTrue();
        expect($r['price_per_100g'])->toBeInt()->toBeGreaterThan(0);
        expect($r['tasting_notes'])->toBeArray();
    }
    // satu profil terbaik per kopi, jadi slug tidak berulang
    expect(collect($rows)->pluck('slug')->unique()->count())->toBe(count($rows));
});

test('urutan dan isi sama persis dengan Recommend::rank() di web untuk semua kombinasi', function () {
    foreach (array_keys(Recommend::DRINK) as $drink) {
        foreach (array_keys(Recommend::TASTE) as $taste) {
            foreach (array_keys(Recommend::ACID) as $acid) {
                $expected = Recommend::rank(compact('drink', 'taste', 'acid'))
                    ->map(fn ($r) => [$r['coffee']->slug, $r['profile']->level, $r['profile']->price_per_100g])->all();

                $got = collect($this->postJson('/api/recommend', compact('drink', 'taste', 'acid'))->assertOk()->json('data'))
                    ->map(fn ($r) => [$r['slug'], $r['level'], $r['price_per_100g']])->all();

                expect($got)->toBe($expected);
            }
        }
    }
});

test('minum susu dan tidak suka asam menggeser level lebih gelap daripada hitam dan suka asam', function () {
    $order = array_keys(\App\Models\RoastProfile::LEVELS);
    $idx = fn (array $a) => array_search($this->postJson('/api/recommend', $a)->json('data.0.level'), $order, true);

    $terang = $idx(['drink' => 'hitam', 'taste' => 'bunga', 'acid' => 'suka']);
    $gelap = $idx(['drink' => 'susu', 'taste' => 'bunga', 'acid' => 'tidak']);

    expect($gelap)->toBeGreaterThanOrEqual($terang);
});

test('kopi nonaktif tidak direkomendasikan', function () {
    $first = $this->postJson('/api/recommend', ['drink' => 'hitam', 'taste' => 'cokelat', 'acid' => 'sedikit'])->json('data.0.slug');
    Coffee::where('slug', $first)->update(['is_active' => false]);

    $slugs = collect($this->postJson('/api/recommend', ['drink' => 'hitam', 'taste' => 'cokelat', 'acid' => 'sedikit'])->json('data'))->pluck('slug');
    expect($slugs)->not->toContain($first);
});

test('validasi: kosong, nilai di luar pilihan, dan sebagian jawaban ditolak 422', function () {
    $this->postJson('/api/recommend', [])->assertStatus(422)->assertJsonValidationErrors(['drink', 'taste', 'acid']);
    $this->postJson('/api/recommend', ['drink' => 'teh', 'taste' => 'buah', 'acid' => 'suka'])->assertStatus(422)->assertJsonValidationErrors('drink');
    $this->postJson('/api/recommend', ['drink' => 'hitam', 'taste' => 'pedas', 'acid' => 'suka'])->assertStatus(422)->assertJsonValidationErrors('taste');
    $this->postJson('/api/recommend', ['drink' => 'hitam', 'taste' => 'buah', 'acid' => 'banget'])->assertStatus(422)->assertJsonValidationErrors('acid');
    $this->postJson('/api/recommend', ['drink' => 'hitam', 'taste' => 'buah'])->assertStatus(422)->assertJsonValidationErrors('acid');
});

test('bisa dipakai tanpa login (halaman kuis publik)', function () {
    $this->postJson('/api/recommend', ['drink' => 'hitam', 'taste' => 'buah', 'acid' => 'suka'])->assertOk();
});
