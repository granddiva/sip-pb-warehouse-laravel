<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = trim($request->username);
        $user = User::where('username', strtolower($username))->first();

        if ($user && Hash::check($request->password, $user->password)) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            ActivityLog::log('login', "User {$user->name} ({$user->role}) berhasil login ke sistem", $user->id);

            return response()->json([
                'success' => true,
                'user'    => $user,
                'message' => 'Selamat datang, ' . $user->name,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Username atau password salah.',
        ], 422);
    }

    public function me(Request $request)
    {
        $user = Auth::user();
        return response()->json([
            'authenticated' => (bool) $user,
            'user'          => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            ActivityLog::log('logout', "User {$user->name} keluar dari sistem", $user->id);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Anda telah keluar dari sistem.',
        ]);
    }
}
