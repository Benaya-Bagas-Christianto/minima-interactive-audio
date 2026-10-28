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
     * Ambil lirik lagu tersinkronisasi waktu (Synced LRC Lyrics) dari LRCLIB dengan pencocokan durasi MP3 riil.
     */
    public function getLyrics(Request $request, $id)
    {
        $music = Music::findOrFail($id);
        $lyricsDir = public_path('lyrics');
        if (!is_dir($lyricsDir)) {
            mkdir($lyricsDir, 0777, true);
        }

        // 1. Dapatkan durasi riil dari file MP3 lokal (menggunakan ffprobe jika tersedia) agar 100% akurat dengan audio yang diputar
        $targetDuration = (float) $request->query('duration', 0);
        $localMp3 = public_path("audio/{$music->id}.mp3");
        if (file_exists($localMp3) && filesize($localMp3) > 100000) {
            $cmd = 'ffprobe -v quiet -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' . escapeshellarg($localMp3);
            $probeOut = @shell_exec($cmd);
            if ($probeOut && is_numeric(trim($probeOut))) {
                $realMp3Dur = (float) trim($probeOut);
                if ($realMp3Dur > 20) {
                    $targetDuration = $realMp3Dur;
                }
            }
        }

        if ($targetDuration <= 0 && !empty($music->duration)) {
            $parts = explode(':', trim($music->duration));
            if (count($parts) === 2) {
                $targetDuration = ((int) $parts[0] * 60) + (int) $parts[1];
            }
        }

        $cacheFile = "{$lyricsDir}/{$music->id}.json";
        if (file_exists($cacheFile) && !$request->boolean('refresh')) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached) && (!empty($cached['syncedLyrics']) || !empty($cached['plainLyrics'])) && ($cached['version'] ?? 0) >= 3) {
                return response()->json($cached);
            }
        }

        // Bersihkan nama judul & artis agar pencarian lirik akurat
        $cleanTitle = trim(preg_replace('/\s*[\(\[].*?[\)\]]/', '', $music->title));
        $cleanArtist = trim(preg_replace('/\s+(ft\.?|feat\.?|featuring|&|x|with)\s+.*$/i', '', $music->artist));

        $candidates = [];
        try {
            $resQ = \Illuminate\Support\Facades\Http::timeout(6)
                ->withHeaders(['User-Agent' => 'MinimaMultimedia/2.0'])
                ->get('https://lrclib.net/api/search', [
                    'q' => "{$cleanTitle} {$cleanArtist}",
                ]);
            if ($resQ->successful() && is_array($resQ->json())) {
                $candidates = array_merge($candidates, $resQ->json());
            }
        } catch (\Exception $e) {
        }

        try {
            $resTrack = \Illuminate\Support\Facades\Http::timeout(6)
                ->withHeaders(['User-Agent' => 'MinimaMultimedia/2.0'])
                ->get('https://lrclib.net/api/search', [
                    'track_name' => $cleanTitle,
                    'artist_name' => $cleanArtist,
                ]);
            if ($resTrack->successful() && is_array($resTrack->json())) {
                $candidates = array_merge($candidates, $resTrack->json());
            }
        } catch (\Exception $e) {
        }

        // Deduplikasi kandidat berdasarkan ID LRCLIB
        $uniqueCandidates = [];
        foreach ($candidates as $c) {
            if (is_array($c) && isset($c['id'])) {
                $uniqueCandidates[$c['id']] = $c;
            }
        }
        $candidates = array_values($uniqueCandidates);

        // Fungsi bantu untuk menganalisis timestamp awal & akhir di dalam teks LRC
        $analyzeLrc = function (?string $lrc, float $clipAfter = 0.0) {
            if (empty($lrc)) {
                return ['first' => 0.0, 'lastSung' => 0.0, 'maxTag' => 0.0, 'count' => 0, 'hasTrailingTag' => false];
            }
            preg_match_all('/\[(\d{1,2}):(\d{2})(?:\.(\d{1,3}))?\]\s*(.*)/', $lrc, $matches, PREG_SET_ORDER);
            $first = null;
            $lastSung = 0.0;
            $maxTag = 0.0;
            $count = 0;
            foreach ($matches as $m) {
                $sec = ((int) $m[1] * 60) + (int) $m[2] + (isset($m[3]) && $m[3] !== '' ? ((int) str_pad(substr($m[3], 0, 3), 3, '0')) / 1000.0 : 0.0);
                if ($clipAfter > 0.0 && $sec > $clipAfter) {
                    continue;
                }
                $text = trim($m[4] ?? '');
                if ($sec > $maxTag) {
                    $maxTag = $sec;
                }
                if ($text !== '') {
                    if ($first === null) {
                        $first = $sec;
                    }
                    if ($sec > $lastSung) {
                        $lastSung = $sec;
                    }
                    $count++;
                }
            }
            return [
                'first' => $first ?? 0.0,
                'lastSung' => $lastSung,
                'maxTag' => $maxTag,
                'count' => $count,
                'hasTrailingTag' => ($maxTag > $lastSung + 0.8),
            ];
        };

        $bestMatch = null;
        $syncedCandidates = array_filter($candidates, fn($item) => !empty($item['syncedLyrics']));

        if (!empty($syncedCandidates)) {
            // Hitung median timestamp baris pertama untuk mendeteksi kandidat outlier yang intronya meleset
            $firstTimes = [];
            foreach ($syncedCandidates as $sc) {
                $m0 = $analyzeLrc($sc['syncedLyrics']);
                if ($m0['count'] >= 3) {
                    $firstTimes[] = $m0['first'];
                }
            }
            sort($firstTimes);
            $medianFirst = !empty($firstTimes) ? $firstTimes[(int) floor(count($firstTimes) / 2)] : 0.0;

            $scored = [];
            foreach ($syncedCandidates as $cand) {
                $candDur = (float) ($cand['duration'] ?? 0);
                // Jika durasi metadata kandidat sangat cocok dengan MP3 kita (selisih <= 3.5 detik),
                // abaikan baris skit/outro tambahan yang melebihi durasi MP3 saat analisis
                $clipLimit = ($targetDuration > 0 && $candDur > 0 && abs($candDur - $targetDuration) <= 3.5)
                    ? ($targetDuration + 1.0)
                    : 0.0;

                $meta = $analyzeLrc($cand['syncedLyrics'], $clipLimit);
                if ($meta['count'] < 3) {
                    continue;
                }

                $score = 0.0;

                // Prioritaskan kecocokan nama artis & judul
                if (stripos($cand['artistName'] ?? '', $cleanArtist) === false) {
                    $score += 150.0;
                }
                if (stripos($cand['trackName'] ?? '', $cleanTitle) === false) {
                    $score += 100.0;
                }

                // Penalti jika baris pertama kandidat melenceng jauh (> 8 detik) dari konsensus mayoritas kandidat
                if (count($firstTimes) >= 3 && abs($meta['first'] - $medianFirst) > 8.0) {
                    $score += abs($meta['first'] - $medianFirst) * 8.0;
                }

                if ($targetDuration > 0) {
                    // Selisih durasi metadata dengan durasi file MP3 kita
                    $durDiff = abs($candDur - $targetDuration);
                    $score += $durDiff * 4.0;

                    // Jika LRC memiliki tag penutup kosong [mm:ss.xx], tag tersebut menandai akhir audio versi pembuat LRC
                    if ($meta['hasTrailingTag']) {
                        $score += abs($meta['maxTag'] - $targetDuration) * 3.0;
                    }

                    // KRUSIAL: Jika timestamp lirik terakhir melewati durasi MP3 kita, berarti itu LRC versi Album yang lebih panjang
                    if ($meta['lastSung'] > $targetDuration - 0.5) {
                        $score += 500.0 + (($meta['lastSung'] - $targetDuration) * 20.0);
                    }
                    if ($meta['maxTag'] > $targetDuration + 2.0) {
                        $score += 300.0 + (($meta['maxTag'] - $targetDuration) * 15.0);
                    }

                    // Idealnya lirik terakhir selesai sekitar 3 s/d 35 detik sebelum lagu habis (outro)
                    $outroGap = $targetDuration - $meta['lastSung'];
                    if ($outroGap >= 2.0 && $outroGap <= 40.0) {
                        $score -= 15.0;
                    }
                }

                $scored[] = [
                    'cand' => $cand,
                    'meta' => $meta,
                    'score' => $score,
                ];
            }

            if (!empty($scored)) {
                usort($scored, fn($a, $b) => $a['score'] <=> $b['score']);
                $winner = $scored[0];
                $bestMatch = $winner['cand'];
                $winMeta = $winner['meta'];

                // Potong baris lirik/skit yang melewati batas durasi MP3 kita agar tidak pernah meluber
                if ($targetDuration > 0 && !empty($bestMatch['syncedLyrics'])) {
                    $filteredLines = [];
                    foreach (preg_split('/\r?\n/', $bestMatch['syncedLyrics']) as $rawLine) {
                        if (preg_match('/\[(\d{1,2}):(\d{2})(?:\.(\d{1,3}))?\]/', $rawLine, $tm)) {
                            $sec = ((int) $tm[1] * 60) + (int) $tm[2] + (isset($tm[3]) && $tm[3] !== '' ? ((int) str_pad(substr($tm[3], 0, 3), 3, '0')) / 1000.0 : 0.0);
                            if ($sec > $targetDuration + 0.5) {
                                continue;
                            }
                        }
                        $filteredLines[] = $rawLine;
                    }
                    $bestMatch['syncedLyrics'] = implode("\n", $filteredLines);
                }
            }
        }

        if (!$bestMatch && !empty($candidates)) {
            $plainCandidates = array_filter($candidates, fn($item) => !empty($item['plainLyrics']));
            if (!empty($plainCandidates)) {
                $bestMatch = reset($plainCandidates);
            }
        }

        $payload = [
            'version' => 3,
            'id' => $music->id,
            'title' => $music->title,
            'artist' => $music->artist,
            'duration' => round($targetDuration > 0 ? $targetDuration : ($bestMatch['duration'] ?? 210), 2),
            'synced' => !empty($bestMatch['syncedLyrics']),
            'syncedLyrics' => $bestMatch['syncedLyrics'] ?? null,
            'plainLyrics' => $bestMatch['plainLyrics'] ?? null,
        ];

        if (!empty($payload['syncedLyrics']) || !empty($payload['plainLyrics'])) {
            file_put_contents($cacheFile, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return response()->json($payload);
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
