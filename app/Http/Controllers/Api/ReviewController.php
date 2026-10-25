<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coffee;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /** Ulasan publik satu kopi. Kalau ada token, ikut dikirim ulasan milik sendiri dan boleh-tidaknya menulis. */
    public function index(Request $request, string $slug)
    {
        $coffee = Coffee::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $user = $request->user('sanctum');

        $reviews = Review::visible()->with('user')->where('coffee_id', $coffee->id)->latest('id')->limit(50)->get();
        $all = Review::visible()->where('coffee_id', $coffee->id);

        $mine = $user ? Review::where('user_id', $user->id)->where('coffee_id', $coffee->id)->first() : null;

        return response()->json([
            'average'    => round((float) ($all->avg('rating') ?? 0), 1),
            'count'      => (clone $all)->count(),
            'can_review' => $user && $user->role === 'customer' && Review::eligibleOrder($user, $coffee) !== null,
            'mine'       => $mine ? ['rating' => $mine->rating, 'body' => $mine->body] : null,
            'data'       => $reviews->map(fn (Review $r) => [
                'name'       => $r->displayName(),
                'rating'     => $r->rating,
                'body'       => $r->body,
                'created_at' => $r->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function store(Request $request, string $slug)
    {
        $coffee = Coffee::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $user = $request->user();

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'body'   => ['nullable', 'string', 'max:600'],
        ], [
            'rating.required' => 'Pilih jumlah bintang dulu.',
            'rating.between'  => 'Pilih jumlah bintang dulu.',
        ]);

        $order = Review::eligibleOrder($user, $coffee);
        if (! $order) {
            return response()->json(['message' => 'Ulasan hanya untuk pembeli yang pesanannya sudah selesai.'], 403);
        }

        $r = Review::updateOrCreate(
            ['user_id' => $user->id, 'coffee_id' => $coffee->id],
            ['order_id' => $order->id, 'rating' => $data['rating'], 'body' => trim((string) ($data['body'] ?? '')) ?: null],
        );

        return response()->json(['rating' => $r->rating, 'body' => $r->body], 201);
    }
}
