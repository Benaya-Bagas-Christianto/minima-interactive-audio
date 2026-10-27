<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Music;
use App\Models\Playlist;
use App\Models\Review;
use Illuminate\Http\Request;

class MusicController extends Controller
{
    /**
     * Tampilkan halaman utama Minima Multimedia.
     */
    public function index()
    {
        // 4 Lagu rekomendasi teratas (rating tertinggi)
        $recommended = Music::orderByDesc('rating')->take(4)->get();

        // 10 Lagu trending
        $trending = Music::inRandomOrder()->take(10)->get();

        // Ulasan terbaru
        $recentReviews = Review::latest()->take(5)->get();

        // Daftar Genre unik
        $genres = Music::select('genre')->distinct()->pluck('genre');

        // Daftar Artis
        $artists = Artist::orderBy('name')->get();

        // Playlist awal
        $playlists = Playlist::withCount('musics')->get();

        // Seluruh koleksi musik untuk instant client-side cache (0ms render modal)
        $allMusics = Music::all();

        return view('music.index', compact('recommended', 'trending', 'recentReviews', 'genres', 'artists', 'playlists', 'allMusics'));
    }

    /**
     * API untuk pencarian & filtering musik.
     */
    public function apiSearch(Request $request)
    {
        $query = Music::query();

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('artist', 'like', "%{$keyword}%")
                  ->orWhere('album', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('genre') && $request->genre !== 'All') {
            $query->where('genre', $request->genre);
        }

        if ($request->filled('artist')) {
            $query->where('artist', $request->artist);
        }

        if ($request->filled('decade') && $request->decade !== 'all') {
            $decade = (int) $request->decade;
            $query->whereBetween('year', [$decade, $decade + 9]);
        }

        // Sorting
        $sort = $request->get('sort', 'default');
        if ($sort === 'az') {
            $query->orderBy('title', 'asc');
        } elseif ($sort === 'za') {
            $query->orderBy('title', 'desc');
        } elseif ($sort === 'year') {
            $query->orderBy('year', 'desc');
        } else {
            $query->orderByDesc('rating')->orderBy('title', 'asc');
        }

        // Pagination per halaman (default 20 lagu = 5 baris x 4 kolom yang rapi)
        $perPage = (int) $request->get('per_page', 20);
        $musics = $query->paginate($perPage);

        return response()->json($musics);
    }

    /**
     * API untuk live autocomplete & rekomendasi pencarian (artis & lagu).
     */
    public function apiSuggestions(Request $request)
    {
        $keyword = trim($request->get('q', ''));
        if (empty($keyword)) {
            return response()->json([
                'keyword' => '',
                'artists' => [],
                'songs' => [],
            ]);
        }

        $lowerKeyword = strtolower($keyword);

        // 1. Cari Artis
        $artists = Artist::where('name', 'like', "%{$keyword}%")
            ->get(['id', 'name', 'image', 'listeners'])
            ->sortBy(function ($item) use ($lowerKeyword) {
                $lowerName = strtolower($item->name);
                if (str_starts_with($lowerName, $lowerKeyword)) return 1;
                if (str_contains($lowerName, ' ' . $lowerKeyword)) return 2;
                return 3;
            })
            ->values()
            ->take(4);

        // Jika tidak ada di tabel Artist, cari artis unik di tabel Music
        if ($artists->isEmpty()) {
            $artistNames = Music::where('artist', 'like', "%{$keyword}%")
                ->distinct()
                ->pluck('artist')
                ->take(4);

            $artists = $artistNames->map(function ($name) {
                return [
                    'id' => null,
                    'name' => $name,
                    'image' => null,
                    'listeners' => null,
                ];
            });
        }

        // 2. Cari Lagu (berdasarkan Judul atau Artis)
        $songs = Music::where('title', 'like', "%{$keyword}%")
            ->orWhere('artist', 'like', "%{$keyword}%")
            ->get(['id', 'title', 'artist', 'image', 'rating', 'genre', 'year', 'audio_url', 'video_id'])
            ->sortBy(function ($item) use ($lowerKeyword) {
                $lowerTitle = strtolower($item->title);
                $lowerArtist = strtolower($item->artist);
                if (str_starts_with($lowerArtist, $lowerKeyword)) return 1;
                if (str_starts_with($lowerTitle, $lowerKeyword)) return 2;
                return 3;
            })
            ->values()
            ->take(5);

        return response()->json([
            'keyword' => $keyword,
            'artists' => $artists,
            'songs' => $songs,
        ]);
    }

    /**
     * Detail musik untuk modal info / multimedia player.
     */
    public function show($id)
    {
        $music = Music::with(['reviews' => fn($q) => $q->latest()])->findOrFail($id);
        $localMp3 = public_path("audio/{$music->id}.mp3");
        if (file_exists($localMp3) && filesize($localMp3) > 500000) {
            $music->audio_url = "audio/{$music->id}.mp3";
        }
        return response()->json($music);
    }

    /**
     * Dapatkan URL audio preview resmi dari iTunes atau MP3 lokal jika sudah diunduh.
     */
    public function getRealAudio($id)
    {
        $music = Music::findOrFail($id);
        $localMp3 = public_path("audio/{$music->id}.mp3");

        if (file_exists($localMp3) && filesize($localMp3) > 500000) {
            $music->audio_url = "audio/{$music->id}.mp3";
            $music->save();
        } elseif (empty($music->audio_url) || !str_contains($music->audio_url, 'apple.com')) {
            try {
                $res = \Illuminate\Support\Facades\Http::timeout(4)->get('https://itunes.apple.com/search', [
                    'term' => $music->title . ' ' . $music->artist,
                    'entity' => 'song',
                    'limit' => 1,
                ]);

                if ($res->successful()) {
                    $preview = $res->json('results.0.previewUrl');
                    if (!empty($preview)) {
                        $music->audio_url = $preview;
                        $music->save();
                    }
                }
            } catch (\Exception $e) {
                // Return current fallback
            }
        }

        $audioUrl = $music->audio_url;
        if (str_starts_with($audioUrl, 'audio/')) {
            $audioUrl = '/' . $audioUrl;
        }

        return response()->json([
            'id' => $music->id,
            'title' => $music->title,
            'artist' => $music->artist,
            'audio_url' => $audioUrl,
            'video_id' => $music->video_id,
        ]);
    }

    /**
     * Stream file audio dengan dukungan penuh HTTP 206 Partial Content (Byte-Range Seeking).
     */
    public function streamAudio($id)
    {
        $music = Music::findOrFail($id);
        $localMp3 = public_path("audio/{$music->id}.mp3");

        // 1. Jika file MP3 lokal ada di komputer ini, putar langsung dengan HTTP 206 Byte-Range
        if (file_exists($localMp3) && filesize($localMp3) > 100000) {
            return response()->file($localMp3, [
                'Content-Type' => 'audio/mpeg',
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'no-cache',
            ]);
        }

        // 2. Jika file lokal belum ada (misal repo baru di-clone dari GitHub), putar preview resmi online
        if (!empty($music->audio_url) && str_starts_with($music->audio_url, 'http')) {
            return redirect($music->audio_url);
        }

        // 3. Cari URL preview audio resmi iTunes secara dinamis
        try {
            $res = \Illuminate\Support\Facades\Http::timeout(3)->get('https://itunes.apple.com/search', [
                'term' => $music->title . ' ' . $music->artist,
                'entity' => 'song',
                'limit' => 1,
            ]);
            if ($res->successful()) {
                $preview = $res->json('results.0.previewUrl');
                if (!empty($preview)) {
                    $music->audio_url = $preview;
                    $music->save();
                    return redirect($preview);
                }
            }
        } catch (\Exception $e) {}

        abort(404, 'Audio file not found.');
    }

    /**
     * Pastikan audio full durasi tersedia dalam format MP3 lokal.
     */
    public function prepareFullAudio($id)
    {
        $music = Music::findOrFail($id);
        $audioDir = public_path('audio');
        if (!is_dir($audioDir)) {
            mkdir($audioDir, 0777, true);
        }

        $mp3Path = "{$audioDir}/{$music->id}.mp3";
        if (file_exists($mp3Path) && filesize($mp3Path) > 500000) {
            $music->audio_url = "audio/{$music->id}.mp3";
            $music->save();
            return response()->json([
                'success' => true,
                'audio_url' => '/' . $music->audio_url,
                'duration' => $music->duration,
            ]);
        }

        // Unduh audio penuh kualitas tinggi via yt-dlp jika belum tersedia
        $vid = $music->video_id;
        if ($vid && strlen($vid) >= 8) {
            $url = "https://www.youtube.com/watch?v={$vid}";
            $cmd = "python -m yt_dlp -x --audio-format mp3 -o \"{$audioDir}/{$music->id}.%(ext)s\" \"{$url}\" 2>&1";
            exec($cmd, $out, $ret);

            if (file_exists($mp3Path) && filesize($mp3Path) > 500000) {
                $music->audio_url = "audio/{$music->id}.mp3";
                $music->save();
                return response()->json([
                    'success' => true,
                    'audio_url' => '/' . $music->audio_url,
                    'duration' => $music->duration,
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'audio_url' => $music->audio_url,
            'duration' => $music->duration,
        ]);
    }

    /**
     * Detail artis
     */
    public function showArtist($name)
    {
        $artist = Artist::where('name', $name)->first();
        $songs = Music::where('artist', $name)->get();

        return response()->json([
            'artist' => $artist,
            'songs' => $songs,
        ]);
    }
}
