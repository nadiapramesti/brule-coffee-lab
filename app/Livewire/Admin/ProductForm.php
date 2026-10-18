<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductForm extends Component
{
    use WithFileUploads;

    public ?int $productId = null;

    public string $name = '';
    public string $name_en = '';
    public string $slug = '';
    public string $category = 'brewer';
    public string $description = '';
    public string $description_en = '';
    public string $price = '';
    public string $stock = '0';
    public string $option_label = '';
    /** Varian dipisah koma, mis. "Hitam, Putih, Krem". */
    public string $options = '';
    public bool $is_active = true;

    public $photo = null;
    public ?string $currentImage = null;
    public bool $removePhoto = false;

    public function mount(?Product $product = null): void
    {
        if (! $product?->exists) {
            return;
        }

        $this->productId = $product->id;
        $this->name = $product->name;
        $this->name_en = (string) $product->name_en;
        $this->slug = $product->slug;
        $this->category = $product->category;
        $this->description = $product->description;
        $this->description_en = (string) $product->description_en;
        $this->price = (string) $product->price;
        $this->stock = (string) $product->stock;
        $this->option_label = (string) $product->option_label;
        $this->options = implode(', ', $product->optionList());
        $this->is_active = $product->is_active;
        $this->currentImage = $product->image;
    }

    public function updatedName(): void
    {
        if (! $this->productId) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function updatedPhoto(): void
    {
        $this->removePhoto = false;
        try {
            $this->validateOnly('photo');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->photo = null;
            throw $e;
        }
    }

    public function clearPhoto(): void
    {
        if ($this->photo) {
            $this->photo = null;
            $this->resetErrorBag('photo');

            return;
        }
        $this->removePhoto = $this->currentImage !== null;
    }

    public function currentImageUrl(): ?string
    {
        if (! $this->currentImage || $this->removePhoto) {
            return null;
        }

        return Str::startsWith($this->currentImage, ['http://', 'https://', '/']) ? $this->currentImage : asset('storage/'.ltrim($this->currentImage, '/'));
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:90', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('products', 'slug')->ignore($this->productId)],
            'category' => ['required', Rule::in(array_keys(Product::CATEGORIES))],
            'description' => ['required', 'string', 'max:400'],
            'description_en' => ['nullable', 'string', 'max:400'],
            'price' => ['required', 'integer', 'between:1000,50000000'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'option_label' => ['nullable', 'string', 'max:30'],
            'options' => ['nullable', 'string', 'max:200'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ];
    }

    protected function messages(): array
    {
        return [
            'slug.regex' => __('Slug hanya huruf kecil, angka, dan tanda hubung.'),
            'slug.unique' => __('Slug ini sudah dipakai produk lain.'),
            'required' => __('Wajib diisi.'),
            'integer' => __('Harus angka bulat.'),
            'between' => __('Nilai di luar rentang yang diizinkan.'),
            'max' => __('Terlalu panjang.'),
            'photo.image' => __('File harus berupa gambar.'),
            'photo.mimes' => __('Format harus JPG, PNG, atau WebP.'),
            'photo.max' => __('Ukuran foto maksimal 3 MB.'),
        ];
    }

    public function save(): void
    {
        $this->validate();

        $options = collect(explode(',', $this->options))->map(fn ($o) => trim($o))->filter()->unique()->values();
        if ($options->count() > 12 || $options->contains(fn ($o) => mb_strlen($o) > 30)) {
            $this->addError('options', __('Maksimal 12 varian, masing-masing paling panjang 30 karakter.'));

            return;
        }

        $wasNew = $this->productId === null;
        $oldImage = $this->currentImage;
        $newImage = $oldImage;
        if ($this->photo) {
            $newImage = $this->photo->store('products', 'public');
        } elseif ($this->removePhoto) {
            $newImage = null;
        }

        $product = Product::updateOrCreate(['id' => $this->productId], [
            'slug' => $this->slug,
            'name' => trim($this->name),
            'name_en' => trim($this->name_en) ?: null,
            'category' => $this->category,
            'description' => trim($this->description),
            'description_en' => trim($this->description_en) ?: null,
            'price' => (int) $this->price,
            'stock' => (int) $this->stock,
            'option_label' => $options->isNotEmpty() ? (trim($this->option_label) ?: null) : null,
            'options' => $options->isNotEmpty() ? $options->all() : null,
            'image' => $newImage,
            'is_active' => $this->is_active,
        ]);

        if ($oldImage && $oldImage !== $newImage && ! Str::startsWith($oldImage, ['http://', 'https://', '/'])) {
            Storage::disk('public')->delete($oldImage);
        }
        $this->currentImage = $newImage;
        $this->photo = null;
        $this->removePhoto = false;

        ActivityLog::record($wasNew ? 'product.created' : 'product.updated', $product, $wasNew ? 'Produk :n ditambahkan' : 'Produk :n diperbarui', ['n' => $product->name]);

        $this->dispatch('product-saved', name: $product->name, created: $wasNew);
    }

    public function render()
    {
        return view('livewire.admin.product-form');
    }
}
