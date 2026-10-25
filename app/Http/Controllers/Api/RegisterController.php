<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /** Daftar akun pelanggan baru, langsung masuk (token dikembalikan seperti login). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'phone'    => ['nullable', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ], [
            'email.unique'       => 'Email ini sudah terdaftar. Silakan masuk.',
            'phone.regex'        => 'Nomor WhatsApp tidak valid. Contoh: 0812xxxxxxxx',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $data['password'] = Hash::make($data['password']);

        // role tidak diterima dari request: akun dari aplikasi selalu pelanggan.
        $user = User::create($data);
        event(new Registered($user));

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role'  => $user->role ?: 'customer',
            ],
        ], 201);
    }
}
