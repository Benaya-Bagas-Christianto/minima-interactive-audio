<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Cek status login user saat ini.
     */
    public function me()
    {
        return response()->json([
            'authenticated' => Auth::check(),
            'user' => Auth::user(),
        ]);
    }

    /**
     * Daftar akun baru (Sign Up) dengan username, email, dan password.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|min:3|max:50',
            'email' => 'required|email|max:120|unique:users,email',
            'password' => 'required|string|min:6|max:100',
        ], [
            'username.required' => 'Username wajib diisi.',
            'username.min' => 'Username minimal 3 karakter.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan gunakan email lain atau langsung Masuk.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        $rawUsername = trim($validated['username']);
        $cleanSlug = Str::slug($rawUsername, '_');
        if ($cleanSlug === '') {
            $cleanSlug = 'user_' . strtolower(Str::random(5));
        }

        // Cek apakah username sudah dipakai
        if (User::whereRaw('LOWER(username) = ?', [strtolower($cleanSlug)])->orWhereRaw('LOWER(username) = ?', [strtolower($rawUsername)])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Username "' . $rawUsername . '" sudah digunakan. Silakan pilih username lain.',
            ], 422);
        }

        $user = User::create([
            'name' => $rawUsername,
            'username' => $cleanSlug,
            'email' => strtolower(trim($validated['email'])),
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user, true);

        return response()->json([
            'success' => true,
            'message' => 'Akun berhasil dibuat! Selamat datang, ' . $user->name . '!',
            'user' => $user,
            'csrf_token' => csrf_token(),
        ]);
    }

    /**
     * Masuk ke akun (Sign In) menggunakan Username atau Email + Password.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required' => 'Masukkan username atau email Anda.',
            'password.required' => 'Masukkan password Anda.',
        ]);

        $identifier = trim($validated['login']);
        $lowerIdentifier = strtolower($identifier);

        $user = User::whereRaw('LOWER(email) = ?', [$lowerIdentifier])
            ->orWhereRaw('LOWER(username) = ?', [$lowerIdentifier])
            ->orWhereRaw('LOWER(name) = ?', [$lowerIdentifier])
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Username/email atau password tidak sesuai.',
            ], 401);
        }

        Auth::login($user, true);

        return response()->json([
            'success' => true,
            'message' => 'Selamat datang kembali, ' . $user->name . '!',
            'user' => $user,
            'csrf_token' => csrf_token(),
        ]);
    }

    /**
     * Keluar dari akun (Logout).
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Anda telah keluar dari akun.',
            'csrf_token' => csrf_token(),
        ]);
    }
}
