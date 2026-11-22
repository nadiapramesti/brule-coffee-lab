<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coffee;
use Illuminate\Http\Request;

/** Kopi favorit pelanggan (sama seperti tombol hati di katalog web). */
class FavoriteController extends Controller
{
    /** Daftar id kopi favorit milik pengguna yang login. */
    public function index(Request $request)
    {
        return response()->json(['data' => $this->ids($request)]);
    }

    /**
     * Toggle favorit. {coffee} boleh slug (seperti /coffees/{slug}) atau id.
     * Hanya kopi aktif; kopi nonaktif atau tidak ada dijawab 404.
     */
    public function toggle(Request $request, string $coffee)
    {
        $found = Coffee::active()
            ->where(fn ($q) => $q->where('slug', $coffee)->when(ctype_digit($coffee), fn ($q) => $q->orWhere('id', (int) $coffee)))
            ->firstOrFail();

        $result = $request->user()->favorites()->toggle($found->id);

        return response()->json([
            'favorited' => ! empty($result['attached']),
            'data'      => $this->ids($request),
        ]);
    }

    /** @return list<int> */
    private function ids(Request $request): array
    {
        return $request->user()->favorites()->pluck('coffees.id')->map(fn ($id) => (int) $id)->sort()->values()->all();
    }
}
