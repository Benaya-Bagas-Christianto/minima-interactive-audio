<?php

namespace App\Http\Controllers;

use App\Models\Music;
use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PlaylistController extends Controller
{
    /**
     * Ambil daftar playlist (menampilkan playlist milik user yang sedang login + playlist komunitas).
     */
    public function index()
    {
        $query = Playlist::withCount('musics');

        if (Auth::check()) {
            $userId = Auth::id();
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereNull('user_id');
            })->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$userId]);
        } else {
            $query->whereNull('user_id');
        }

        $playlists = $query->latest()->get();
        return response()->json($playlists);
    }

    /**
     * Tampilkan detail playlist dan daftar lagu di dalamnya.
     */
    public function show($id)
    {
        $playlist = Playlist::with('musics')->findOrFail($id);
        return response()->json($playlist);
    }

    /**
     * Simpan playlist baru ke database (terhubung dengan akun user yang sedang login).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'image' => 'nullable|string',
        ]);

        $user = Auth::user();

        $playlist = Playlist::create([
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : 'Komunitas Minima',
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image' => $validated['image'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => $user
                ? "Playlist \"{$playlist->name}\" berhasil disimpan ke akun {$user->name}!"
                : 'Playlist berhasil dibuat!',
            'playlist' => $playlist,
        ]);
    }

    /**
     * Tambahkan lagu ke dalam playlist.
     */
    public function addSong(Request $request, $id)
    {
        $request->validate([
            'music_id' => 'required|exists:musics,id',
        ]);

        $playlist = Playlist::findOrFail($id);

        if (!$playlist->musics()->where('musics.id', $request->music_id)->exists()) {
            $playlist->musics()->attach($request->music_id);
            $music = Music::find($request->music_id);
            return response()->json([
                'success' => true,
                'message' => 'Lagu berhasil ditambahkan ke playlist!',
                'music' => $music,
                'musics_count' => $playlist->musics()->count(),
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Lagu sudah ada di dalam playlist ini.']);
    }

    /**
     * Hapus lagu dari playlist.
     */
    public function removeSong($id, $musicId)
    {
        $playlist = Playlist::findOrFail($id);
        $playlist->musics()->detach($musicId);

        return response()->json(['success' => true, 'message' => 'Lagu telah dihapus dari playlist.']);
    }

    /**
     * Perbarui nama dan deskripsi playlist.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $playlist = Playlist::findOrFail($id);
        $playlist->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Playlist berhasil diperbarui!',
            'playlist' => $playlist,
        ]);
    }

    /**
     * Perbarui gambar sampul playlist (hasil crop dari file manager).
     */
    public function updateImage(Request $request, $id)
    {
        $request->validate([
            'cropped_image' => 'required|string',
        ]);

        $playlist = Playlist::findOrFail($id);
        $dataUrl = $request->input('cropped_image');

        if (!preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $matches)) {
            return response()->json([
                'success' => false,
                'message' => 'Format gambar tidak valid.',
            ], 422);
        }

        $imageData = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1));
        if ($imageData === false) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses data gambar.',
            ], 422);
        }

        $dir = public_path('image/playlists');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Hapus file kover kustom sebelumnya jika ada
        if ($playlist->image && str_starts_with($playlist->image, 'image/playlists/')) {
            $oldPath = public_path($playlist->image);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $filename = 'playlist_' . $playlist->id . '_' . time() . '.jpg';
        file_put_contents($dir . DIRECTORY_SEPARATOR . $filename, $imageData);

        $relativePath = 'image/playlists/' . $filename;
        $playlist->update(['image' => $relativePath]);

        return response()->json([
            'success' => true,
            'message' => 'Gambar sampul playlist berhasil diperbarui!',
            'image' => $relativePath,
            'playlist' => $playlist,
        ]);
    }

    /**
     * Hapus playlist.
     */
    public function destroy($id)
    {
        $playlist = Playlist::findOrFail($id);
        $playlist->delete();

        return response()->json(['success' => true, 'message' => 'Playlist berhasil dihapus!']);
    }
}
