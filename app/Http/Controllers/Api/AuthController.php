<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Tripline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $ip = $request->ip();

        // Tripline bilang IP ini lagi dianggap berisiko (brute-force/spraying terdeteksi dari
        // percobaan-percobaan sebelumnya, baik dari web maupun mobile -- keduanya lapor ke sini)
        // -- tolak SEBELUM password dicek, biar tidak buang waktu hash dan tidak bocor email valid.
        if (Tripline::check($ip)['blocked']) {
            return response()->json(['message' => 'Terlalu banyak percobaan mencurigakan dari jaringanmu. Coba lagi beberapa menit lagi.'], 429);
        }

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            Tripline::report($ip, $data['email'], success: false, reason: 'bad_password');

            return response()->json(['message' => 'Email atau password salah'], 422);
        }

        if ($user->blocked_at) {
            return response()->json(['message' => 'Akun diblokir, hubungi admin'], 403);
        }

        Tripline::report($ip, $data['email'], success: true);

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $this->payload($user),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($this->payload($request->user()));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'ok']);
    }

    /** Tukar kode sekali pakai dari login sosial menjadi token. */
    public function exchange(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:80']]);
        $id = Cache::pull('oauth_code:'.$data['code']);
        $user = $id ? User::find($id) : null;

        if (! $user || $user->blocked_at) {
            return response()->json(['message' => 'Kode login tidak valid atau kedaluwarsa. Coba lagi.'], 422);
        }

        // Sekadar tercatat di Tripline juga (bukan jalur yang rawan brute-force -- kodenya sekali
        // pakai dan acak -- jadi tidak perlu Tripline::check() di sini seperti di login email/password).
        Tripline::report($request->ip(), $user->email, success: true, reason: 'oauth_exchange');

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $this->payload($user),
        ]);
    }

    private function payload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
        ];
    }
}
