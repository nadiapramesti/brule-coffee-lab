<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone' => ['nullable', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
        ], [
            'phone.regex'  => 'Nomor WhatsApp tidak valid. Contoh: 0812xxxxxxxx',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
        ]);

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role'  => $user->role,
        ]);
    }

    public function password(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ], [
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        // Akun yang daftar lewat Google/Facebook belum punya password, jadi tidak ditanya yang lama.
        if ($user->password) {
            if (! Hash::check((string) $request->input('current_password'), $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => 'Password saat ini salah.',
                ]);
            }
        }

        $user->update(['password' => $data['password']]);

        // Perangkat lain keluar otomatis, perangkat ini tetap masuk.
        $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'ok']);
    }
}
