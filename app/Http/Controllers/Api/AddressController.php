<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        $list = $request->user()->addresses()->get()->map(fn (Address $a) => $this->payload($a))->values();

        return response()->json(['data' => $list]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $d = $request->validate($this->rules(), $this->messages());

        if (Address::where('user_id', $user->id)->count() >= Address::MAX_PER_USER) {
            throw ValidationException::withMessages([
                'label' => 'Maksimal ' . Address::MAX_PER_USER . ' alamat tersimpan.',
            ]);
        }

        $a = $user->addresses()->create($d + ['is_default' => false]);

        if (Address::where('user_id', $user->id)->count() === 1) {
            $a->makeDefault();
        }

        return response()->json($this->payload($a->fresh()), 201);
    }

    public function update(Request $request, int $id)
    {
        $a = Address::where('user_id', $request->user()->id)->findOrFail($id);
        $a->update($request->validate($this->rules(), $this->messages()));

        return response()->json($this->payload($a->fresh()));
    }

    public function makeDefault(Request $request, int $id)
    {
        $a = Address::where('user_id', $request->user()->id)->findOrFail($id);
        $a->makeDefault();

        return response()->json($this->payload($a->fresh()));
    }

    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $a = Address::where('user_id', $user->id)->findOrFail($id);
        $wasDefault = $a->is_default;
        $a->delete();

        if ($wasDefault) {
            Address::where('user_id', $user->id)->orderBy('id')->first()?->makeDefault();
        }

        return response()->json(['message' => 'ok']);
    }

    private function rules(): array
    {
        return [
            'label'     => ['required', 'string', 'max:40'],
            'recipient' => ['required', 'string', 'max:80'],
            'phone'     => ['required', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'address'   => ['required', 'string', 'min:15', 'max:400'],
        ];
    }

    private function messages(): array
    {
        return [
            'phone.regex' => 'Nomor WhatsApp tidak valid. Contoh: 0812xxxxxxxx',
            'address.min' => 'Alamat terlalu pendek. Tulis jalan, nomor, kota, dan kode pos.',
        ];
    }

    private function payload(Address $a): array
    {
        return [
            'id'         => $a->id,
            'label'      => $a->label,
            'recipient'  => $a->recipient,
            'phone'      => $a->phone,
            'address'    => $a->address,
            'is_default' => (bool) $a->is_default,
        ];
    }
}
