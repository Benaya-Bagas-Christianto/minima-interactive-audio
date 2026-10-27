<?php

namespace App\Http\Controllers;

use App\Models\Music;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Ambil daftar ulasan pengguna (bisa difilter per lagu jika ada parameter music_id).
     */
    public function index(Request $request)
    {
        $query = Review::with('music')->latest();
        if ($request->filled('music_id')) {
            $query->where('music_id', $request->music_id);
        }
        $reviews = $query->get();
        return response()->json($reviews);
    }

    /**
     * Simpan ulasan baru dan perbarui rata-rata rating lagu di database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'music_id' => 'required|exists:musics,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string|max:1000',
        ]);

        $music = Music::findOrFail($validated['music_id']);

        $review = Review::create([
            'music_id' => $music->id,
            'music_title' => $music->title,
            'artist' => $music->artist,
            'rating' => $validated['rating'],
            'review' => $validated['review'],
        ]);

        // Hitung ulang rata-rata rating lagu secara nyata di database
        $avgRating = round(Review::where('music_id', $music->id)->avg('rating'), 1);
        if ($avgRating > 0) {
            $music->update(['rating' => $avgRating]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ulasan berhasil disimpan ke database!',
            'review' => $review->load('music'),
            'new_rating' => $music->fresh()->rating,
        ]);
    }

    /**
     * Perbarui ulasan.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string|max:1000',
        ]);

        $review = Review::findOrFail($id);
        $review->update($validated);

        // Hitung ulang rata-rata rating lagu
        $music = Music::find($review->music_id);
        if ($music) {
            $avgRating = round(Review::where('music_id', $music->id)->avg('rating'), 1);
            if ($avgRating > 0) {
                $music->update(['rating' => $avgRating]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Ulasan berhasil diperbarui di database!',
            'review' => $review,
            'new_rating' => $music ? $music->fresh()->rating : null,
        ]);
    }

    /**
     * Hapus ulasan.
     */
    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        $musicId = $review->music_id;
        $review->delete();

        // Hitung ulang rata-rata rating lagu
        $music = Music::find($musicId);
        if ($music) {
            $avgRating = round(Review::where('music_id', $musicId)->avg('rating'), 1);
            if ($avgRating > 0) {
                $music->update(['rating' => $avgRating]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Ulasan berhasil dihapus dari database!',
        ]);
    }
}
