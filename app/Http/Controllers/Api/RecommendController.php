<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Livewire\Recommend;
use App\Support\ApiImage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Kuis tiga pertanyaan: logika yang sama dengan halaman "Temukan kopimu" di web. */
class RecommendController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'drink' => ['required', Rule::in(array_keys(Recommend::DRINK))],
            'taste' => ['required', Rule::in(array_keys(Recommend::TASTE))],
            'acid'  => ['required', Rule::in(array_keys(Recommend::ACID))],
        ]);

        $data = Recommend::rank($data)->map(fn ($r) => [
            'slug'           => $r['coffee']->slug,
            'name'           => $r['coffee']->name,
            'image'          => ApiImage::url($r['coffee']->image),
            'profile_id'     => $r['profile']->id,
            'level'          => $r['profile']->level,
            'level_label'    => $r['profile']->level_label,
            'price_per_100g' => $r['profile']->price_per_100g,
            'tasting_notes'  => array_values($r['profile']->tasting_notes ?? []),
            'why'            => $r['why'],
        ])->values();

        return response()->json(['data' => $data]);
    }
}
