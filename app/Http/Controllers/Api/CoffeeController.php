<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coffee;
use App\Support\ApiImage;
use Illuminate\Http\Request;

class CoffeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Coffee::query()
            ->where('is_active', true)
            ->whereHas('roastProfiles')
            ->with('roastProfiles');

        if ($search = trim((string) $request->query('q'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        return response()->json([
            'data' => $query->orderBy('name')->get()->map(fn ($c) => $this->payload($c, $request)),
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $coffee = Coffee::where('slug', $slug)
            ->where('is_active', true)
            ->with('roastProfiles')
            ->firstOrFail();

        return response()->json($this->payload($coffee, $request));
    }

    private function payload(Coffee $coffee, Request $request): array
    {
        $en = $request->query('lang') === 'en';

        return [
            'id'            => $coffee->id,
            'slug'          => $coffee->slug,
            'name'          => $coffee->name,
            'story'         => $en && $coffee->story_en ? $coffee->story_en : $coffee->story,
            'image'         => ApiImage::url($coffee->image),
            'cupping_score' => $coffee->cupping_score,
            'in_stock'      => $coffee->stock_green_bean_gram > 0,
            'profiles'      => $coffee->roastProfiles->map(fn ($p) => [
                'id'             => $p->id,
                'level'          => $p->level,
                'level_label'    => $p->level_label,
                'agtron'         => $p->agtron,
                'tasting_notes'  => $p->tasting_notes,
                'flavor_values'  => $p->flavor_values,
                'price_per_100g' => $p->price_per_100g,
            ])->values(),
        ];
    }
}