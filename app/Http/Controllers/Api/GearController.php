<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\ApiImage;
use Illuminate\Http\Request;

class GearController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->where('is_active', true);

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }
        if ($search = trim((string) $request->query('q'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        return response()->json([
            'data' => $query->orderBy('name')->get()->map(fn ($p) => $this->payload($p, $request)),
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $product = Product::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return response()->json($this->payload($product, $request));
    }

    private function payload(Product $p, Request $request): array
    {
        $en = app()->getLocale() === 'en' || $request->query('lang') === 'en';

        return [
            'id'            => $p->id,
            'slug'          => $p->slug,
            'name'          => $en && $p->name_en ? $p->name_en : $p->name,
            'category'      => $p->category,
            'description'   => $en && $p->description_en ? $p->description_en : $p->description,
            'price'         => $p->price,
            'stock'         => $p->stock,
            'in_stock'      => $p->stock > 0,
            'option_label'  => $p->option_label,
            'options'       => $p->options,
            'image'         => ApiImage::url($p->image),
            'option_images' => collect($p->option_images ?? [])->map(fn ($i) => ApiImage::url($i))->all(),
        ];
    }
}