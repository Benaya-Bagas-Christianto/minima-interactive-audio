<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PlaylistController extends Controller
{
    /**
     * Ambil semua playlist.
     */
    public function index()
    {
        $playlists = Playlist::withCount('musics')->latest()->get();
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
     * Simpan playlist baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'image' => 'nullable|string',
        ]);

        $playlist = Playlist::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image' => $validated['image'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Playlist berhasil dibuat!',
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
            return response()->json(['success' => true, 'message' => 'Lagu berhasil ditambahkan ke playlist!']);
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
     * Hapus playlist.
     */
    public function destroy($id)
    {
        $playlist = Playlist::findOrFail($id);
        $playlist->delete();

        return response()->json(['success' => true, 'message' => 'Playlist berhasil dihapus!']);
    }
}
