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
    public function index(Request $request)
    {
        // YouTube memblokir embed Video MV resmi (Error 150) jika diakses melalui raw IP 127.0.0.1.
        // Alihkan otomatis ke hostname localhost dengan port yang sama agar Video MV berjalan normal.
        if (!app()->runningUnitTests() && $request->getHost() === '127.0.0.1') {
            $scheme = $request->getScheme();
            $port = $request->getPort();
            $portSuffix = ($port && !in_array($port, [80, 443], true)) ? ":{$port}" : '';
            $query = $request->getQueryString();
            $target = "{$scheme}://localhost{$portSuffix}{$request->getPathInfo()}" . ($query ? "?{$query}" : '');

            return redirect()->away($target);
        }

        // 10 Lagu rekomendasi teratas (rating tertinggi) untuk Horizontal Slider (tampil 6 awal + slide)
        $recommended = Music::orderByDesc('rating')->take(10)->get();

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
        $safeId = (int) $id;
        $localMp3 = public_path("audio/{$safeId}.mp3");

        // 1. Jika file MP3 lokal ada, langsung kirim dengan HTTP 206 Byte-Range tanpa query database (0ms overhead)
        if ($safeId > 0 && file_exists($localMp3) && filesize($localMp3) > 100000) {
            return response()->file($localMp3, [
                'Content-Type' => 'audio/mpeg',
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        $music = Music::findOrFail($id);

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
     * Hitung durasi riil file MP3 lokal dalam detik menggunakan pure PHP (tanpa perlu ffprobe).
     */
    private function getLocalMp3Duration(string $path): float
    {
        if (!file_exists($path) || filesize($path) < 10000) {
            return 0.0;
        }

        $fp = @fopen($path, 'rb');
        if (!$fp) {
            return 0.0;
        }

        $fileSize = filesize($path);
        $header10 = fread($fp, 10);
        $offset = 0;
        if (strlen($header10) === 10 && substr($header10, 0, 3) === 'ID3') {
            $b = unpack('C4', substr($header10, 6, 4));
            $offset = 10 + ((($b[1] & 0x7F) << 21) | (($b[2] & 0x7F) << 14) | (($b[3] & 0x7F) << 7) | ($b[4] & 0x7F));
        }

        fseek($fp, $offset);
        $sampleRates = [
            3 => [44100, 48000, 32000],
            2 => [22050, 24000, 16000],
            0 => [11025, 12000, 8000],
        ];
        $bitratesV1 = [0, 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320, 0];
        $bitratesV2 = [0, 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160, 0];

        $buf = fread($fp, min(65536, max(0, $fileSize - $offset)));
        $bufLen = strlen($buf);
        $frameStart = -1;

        for ($i = 0; $i < $bufLen - 4; $i++) {
            if (ord($buf[$i]) === 0xFF && (ord($buf[$i + 1]) & 0xE0) === 0xE0) {
                $ver = (ord($buf[$i + 1]) >> 3) & 3;
                $layer = (ord($buf[$i + 1]) >> 1) & 3;
                $brIdx = (ord($buf[$i + 2]) >> 4) & 15;
                $srIdx = (ord($buf[$i + 2]) >> 2) & 3;
                if ($ver !== 1 && $layer === 1 && $brIdx > 0 && $brIdx < 15 && $srIdx < 3) {
                    $frameStart = $offset + $i;
                    $sr = $sampleRates[$ver][$srIdx];
                    $spf = ($ver === 3) ? 1152 : 576;
                    $channelMode = (ord($buf[$i + 3]) >> 6) & 3;
                    $sideInfo = ($ver === 3) ? ($channelMode === 3 ? 17 : 32) : ($channelMode === 3 ? 9 : 17);
                    $xingPos = $i + 4 + $sideInfo;
                    if ($xingPos + 12 <= $bufLen && in_array(substr($buf, $xingPos, 4), ['Xing', 'Info'], true)) {
                        $flags = unpack('N', substr($buf, $xingPos + 4, 4))[1];
                        if ($flags & 1) {
                            $frames = unpack('N', substr($buf, $xingPos + 8, 4))[1];
                            fclose($fp);
                            return ($frames * $spf) / $sr;
                        }
                    }
                    $vbriPos = $i + 4 + 32;
                    if ($vbriPos + 18 <= $bufLen && substr($buf, $vbriPos, 4) === 'VBRI') {
                        $frames = unpack('N', substr($buf, $vbriPos + 14, 4))[1];
                        fclose($fp);
                        return ($frames * $spf) / $sr;
                    }
                    break;
                }
            }
        }

        if ($frameStart < 0) {
            fclose($fp);
            return 0.0;
        }

        fseek($fp, $frameStart);
        $dur = 0.0;
        $pos = $frameStart;
        while ($pos + 4 < $fileSize) {
            $hdr = fread($fp, 4);
            if (strlen($hdr) < 4) {
                break;
            }
            if (ord($hdr[0]) !== 0xFF || (ord($hdr[1]) & 0xE0) !== 0xE0) {
                if (substr($hdr, 0, 3) === 'TAG') {
                    break;
                }
                $pos++;
                fseek($fp, $pos);
                continue;
            }
            $ver = (ord($hdr[1]) >> 3) & 3;
            $layer = (ord($hdr[1]) >> 1) & 3;
            $brIdx = (ord($hdr[2]) >> 4) & 15;
            $srIdx = (ord($hdr[2]) >> 2) & 3;
            $pad = (ord($hdr[2]) >> 1) & 1;
            if ($ver === 1 || $layer !== 1 || $brIdx === 0 || $brIdx === 15 || $srIdx === 3) {
                $pos++;
                fseek($fp, $pos);
                continue;
            }
            $sr = $sampleRates[$ver][$srIdx];
            $br = (($ver === 3) ? $bitratesV1[$brIdx] : $bitratesV2[$brIdx]) * 1000;
            $spf = ($ver === 3) ? 1152 : 576;
            $frameLen = (int) floor((($ver === 3 ? 144 : 72) * $br) / $sr) + $pad;
            if ($frameLen < 4) {
                break;
            }
            $dur += $spf / $sr;
            $pos += $frameLen;
            fseek($fp, $pos);
        }

        fclose($fp);
        return $dur;
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

        // 1. Dapatkan durasi riil langsung dari file MP3 lokal (pure PHP, akurat milidetik tanpa butuh ffprobe)
        $targetDuration = 0.0;
        $localMp3 = public_path("audio/{$music->id}.mp3");
        if (file_exists($localMp3) && filesize($localMp3) > 100000) {
            $realMp3Dur = $this->getLocalMp3Duration($localMp3);
            if ($realMp3Dur > 20) {
                $targetDuration = $realMp3Dur;
            }
        }

        if ($targetDuration <= 0 && !empty($music->duration)) {
            $parts = explode(':', trim($music->duration));
            if (count($parts) === 2) {
                $targetDuration = ((int) $parts[0] * 60) + (int) $parts[1];
            }
        }

        if ($targetDuration <= 0) {
            $targetDuration = (float) $request->query('duration', 0);
        }

        $cacheFile = "{$lyricsDir}/{$music->id}.json";
        if (file_exists($cacheFile) && !$request->boolean('refresh')) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            $cachedDur = (float) ($cached['duration'] ?? 0);
            $durMatches = ($targetDuration <= 0 || $cachedDur <= 0 || abs($cachedDur - $targetDuration) <= 4.0);
            if (is_array($cached) && ($cached['version'] ?? 0) >= 5 && $durMatches && (!empty($cached['syncedLyrics']) || !empty($cached['plainLyrics']) || !empty($cached['instrumental']))) {
                return response()->json($cached);
            }
        }

        // Bersihkan nama judul & artis agar pencarian lirik akurat
        $cleanTitle = trim(preg_replace('/\s*[\(\[].*?[\)\]]/', '', $music->title));
        $cleanArtist = trim(preg_replace('/\s+(ft\.?|feat\.?|featuring|&|x|with|,)\s+.*$/i', '', $music->artist));

        $candidates = [];

        // 1. Coba endpoint exact match LRCLIB /api/get terlebih dahulu (sangat akurat untuk durasi studio)
        if ($targetDuration > 0) {
            try {
                $resExact = \Illuminate\Support\Facades\Http::timeout(6)
                    ->withHeaders(['User-Agent' => 'MinimaMultimedia/3.0'])
                    ->get('https://lrclib.net/api/get', [
                        'track_name' => $cleanTitle,
                        'artist_name' => $cleanArtist,
                        'duration' => (int) round($targetDuration),
                    ]);
                if ($resExact->successful() && is_array($resExact->json()) && isset($resExact->json()['id'])) {
                    $candidates[] = $resExact->json();
                }
            } catch (\Exception $e) {
            }
        }

        try {
            $resTrack = \Illuminate\Support\Facades\Http::timeout(6)
                ->withHeaders(['User-Agent' => 'MinimaMultimedia/3.0'])
                ->get('https://lrclib.net/api/search', [
                    'track_name' => $cleanTitle,
                    'artist_name' => $cleanArtist,
                ]);
            if ($resTrack->successful() && is_array($resTrack->json())) {
                $candidates = array_merge($candidates, $resTrack->json());
            }
        } catch (\Exception $e) {
        }

        try {
            $resQ = \Illuminate\Support\Facades\Http::timeout(6)
                ->withHeaders(['User-Agent' => 'MinimaMultimedia/3.0'])
                ->get('https://lrclib.net/api/search', [
                    'q' => "{$cleanTitle} {$cleanArtist}",
                ]);
            if ($resQ->successful() && is_array($resQ->json())) {
                $candidates = array_merge($candidates, $resQ->json());
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

        $normalize = function (?string $s): string {
            $s = mb_strtolower((string) $s);
            $s = preg_replace('/[^a-z0-9\s]/u', ' ', $s);
            return trim(preg_replace('/\s+/', ' ', $s));
        };
        $normTitle = $normalize($cleanTitle);
        $normArtist = $normalize($cleanArtist);
        $badVersionWords = ['remix', 'live', 'karaoke', 'instrumental', 'slowed', 'sped up', 'nightcore', 'acoustic', 'cover', 'tribute', '8d', 'mashup', 'demo', 'radio edit'];

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
            $firstTimes = [];
            foreach ($syncedCandidates as $sc) {
                $scTitle = $normalize($sc['trackName'] ?? '');
                $scArtist = $normalize($sc['artistName'] ?? '');
                if (($normArtist && str_contains($scArtist, $normArtist)) && ($normTitle && str_contains($scTitle, $normTitle))) {
                    $m0 = $analyzeLrc($sc['syncedLyrics']);
                    if ($m0['count'] >= 3) {
                        $firstTimes[] = $m0['first'];
                    }
                }
            }
            sort($firstTimes);
            $medianFirst = !empty($firstTimes) ? $firstTimes[(int) floor(count($firstTimes) / 2)] : 0.0;

            $scored = [];
            foreach ($syncedCandidates as $cand) {
                $candDur = (float) ($cand['duration'] ?? 0);
                $candTitleNorm = $normalize($cand['trackName'] ?? '');
                $candArtistNorm = $normalize($cand['artistName'] ?? '');
                $candAlbumNorm = $normalize($cand['albumName'] ?? '');

                $clipLimit = ($targetDuration > 0 && $candDur > 0 && abs($candDur - $targetDuration) <= 3.5)
                    ? ($targetDuration + 1.0)
                    : 0.0;

                $meta = $analyzeLrc($cand['syncedLyrics'], $clipLimit);
                if ($meta['count'] < 3) {
                    continue;
                }

                $score = 0.0;

                // Kecocokan ketat nama artis & judul
                if ($normArtist && !str_contains($candArtistNorm, $normArtist) && !str_contains($normArtist, $candArtistNorm)) {
                    $score += 300.0;
                }
                if ($normTitle && !str_contains($candTitleNorm, $normTitle) && !str_contains($normTitle, $candTitleNorm)) {
                    $score += 250.0;
                } elseif ($candTitleNorm === $normTitle) {
                    $score -= 20.0;
                }

                // Penalti jika kandidat adalah versi remix/live/karaoke/slowed padahal judul asli bukan
                foreach ($badVersionWords as $bw) {
                    if (str_contains($candTitleNorm, $bw) && !str_contains($normTitle, $bw)) {
                        $score += 180.0;
                    }
                }

                // Bonus kecil jika album cocok (tidak boleh mengalahkan konsensus timing)
                $musicAlbumNorm = $normalize($music->album ?? '');
                if ($musicAlbumNorm && $candAlbumNorm && str_contains($candAlbumNorm, $musicAlbumNorm)) {
                    $score -= 6.0;
                }

                // Bonus konsensus klaster waktu baris pertama (memastikan timing studio mayoritas menang atas versi MV/edisi khusus)
                $clusterSize = 0;
                foreach ($firstTimes as $ft) {
                    if (abs($ft - $meta['first']) <= 0.85) {
                        $clusterSize++;
                    }
                }
                if ($clusterSize >= 2) {
                    $score -= min($clusterSize, 6) * 8.0;
                }

                // Penalti tegas jika baris pertama kandidat melenceng (> 1.2 detik) dari median konsensus mayoritas
                if (count($firstTimes) >= 3 && abs($meta['first'] - $medianFirst) > 1.2) {
                    $score += abs($meta['first'] - $medianFirst) * 18.0;
                }

                if ($targetDuration > 0) {
                    $durDiff = abs($candDur - $targetDuration);
                    $score += $durDiff * 6.0;
                    if ($durDiff <= 2.5) {
                        $score -= 30.0;
                    }

                    if ($meta['lastSung'] > $targetDuration + 1.0) {
                        $score += 500.0 + (($meta['lastSung'] - $targetDuration) * 20.0);
                    }
                    if ($meta['maxTag'] > $targetDuration + 3.0) {
                        $score += 250.0 + (($meta['maxTag'] - $targetDuration) * 15.0);
                    }

                    $outroGap = $targetDuration - $meta['lastSung'];
                    if ($outroGap >= 1.0 && $outroGap <= 45.0) {
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
                // Hanya terima kandidat jika skor cukup wajar (tidak nyasar ke lagu lain)
                if ($winner['score'] < 250.0) {
                    $bestMatch = $winner['cand'];

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
        }

        if (!$bestMatch && !empty($candidates)) {
            $plainCandidates = array_filter($candidates, function ($item) use ($normalize, $normArtist, $normTitle) {
                if (empty($item['plainLyrics'])) return false;
                $a = $normalize($item['artistName'] ?? '');
                $t = $normalize($item['trackName'] ?? '');
                return (!$normArtist || str_contains($a, $normArtist) || str_contains($normArtist, $a))
                    && (!$normTitle || str_contains($t, $normTitle) || str_contains($normTitle, $t));
            });
            if (!empty($plainCandidates)) {
                $bestMatch = reset($plainCandidates);
            }
        }

        $isInstrumental = false;
        if (!$bestMatch && !empty($candidates)) {
            foreach ($candidates as $c) {
                if (!empty($c['instrumental'])) {
                    $isInstrumental = true;
                    break;
                }
            }
        }

        $payload = [
            'version' => 5,
            'id' => $music->id,
            'title' => $music->title,
            'artist' => $music->artist,
            'duration' => round($targetDuration > 0 ? $targetDuration : ($bestMatch['duration'] ?? 210), 2),
            'synced' => !empty($bestMatch['syncedLyrics']),
            'instrumental' => $isInstrumental,
            'syncedLyrics' => $bestMatch['syncedLyrics'] ?? null,
            'plainLyrics' => $bestMatch['plainLyrics'] ?? null,
        ];

        if (!empty($payload['syncedLyrics']) || !empty($payload['plainLyrics']) || $isInstrumental) {
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
