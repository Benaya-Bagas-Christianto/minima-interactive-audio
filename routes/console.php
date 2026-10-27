<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('music:sync-audio {--limit=50}', function () {
    $limit = (int) $this->option('limit');
    $this->info("Memulai sinkronisasi audio asli dari iTunes API (Limit: {$limit})...");

    $query = \App\Models\Music::where(function ($q) {
        $q->whereNull('audio_url')
          ->orWhere('audio_url', 'not like', '%apple.com%');
    });
    if ($limit > 0) {
        $query->take($limit);
    }

    $musics = $query->get();
    $count = 0;

    foreach ($musics as $music) {
        $searchTerm = $music->title . ' ' . $music->artist;
        try {
            $res = \Illuminate\Support\Facades\Http::timeout(5)->get('https://itunes.apple.com/search', [
                'term' => $searchTerm,
                'entity' => 'song',
                'limit' => 1,
            ]);

            if ($res->successful()) {
                $previewUrl = $res->json('results.0.previewUrl');
                if (!empty($previewUrl)) {
                    $music->audio_url = $previewUrl;
                    $music->save();
                    $count++;
                    $this->line("✓ [{$count}] {$music->title} - {$music->artist}: Audio Asli Terhubung!");
                } else {
                    $this->warn("⚠ Tidak ada preview untuk: {$music->title} - {$music->artist}");
                }
            }
        } catch (\Exception $e) {
            $this->error("✕ Error fetching {$music->title}: " . $e->getMessage());
        }

        // Delay 100ms agar sopan terhadap rate limit
        usleep(100000);
    }

    $this->info("Sinkronisasi selesai! {$count} lagu berhasil diperbarui dengan rekaman suara asli.");
})->purpose('Sinkronisasi audio preview asli dari iTunes API');

Artisan::command('music:download-audio', function () {
    $this->info("Memeriksa dan mengunduh seluruh file audio MP3 offline ke public/audio/...");
    $audioDir = public_path('audio');
    if (!is_dir($audioDir)) {
        mkdir($audioDir, 0777, true);
    }

    $songs = \App\Models\Music::all();
    $downloaded = 0;

    foreach ($songs as $s) {
        $mp3Path = "{$audioDir}/{$s->id}.mp3";
        if (file_exists($mp3Path) && filesize($mp3Path) > 500000) {
            $s->audio_url = "audio/{$s->id}.mp3";
            $s->save();
            continue;
        }

        $vid = $s->video_id;
        if (!$vid || strlen($vid) < 8) continue;

        $url = "https://www.youtube.com/watch?v={$vid}";
        $cmd = "python -m yt_dlp -x --audio-format mp3 -o \"{$audioDir}/{$s->id}.%(ext)s\" \"{$url}\" 2>&1";
        exec($cmd, $out, $ret);

        if (file_exists($mp3Path) && filesize($mp3Path) > 500000) {
            $s->audio_url = "audio/{$s->id}.mp3";
            $s->save();
            $downloaded++;
            $this->info("✓ [{$s->id}] {$s->title} - {$s->artist} berhasil diunduh.");
        }
    }

    $this->info("Proses selesai! Audio MP3 lokal siap diputar.");
})->purpose('Unduh seluruh MP3 durasi penuh secara otomatis untuk offline player');
