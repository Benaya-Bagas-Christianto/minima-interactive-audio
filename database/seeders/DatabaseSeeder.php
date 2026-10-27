<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Music;
use App\Models\Playlist;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/data.json');
        if (!File::exists($jsonPath)) {
            $this->command->error('data.json not found!');
            return;
        }

        $data = json_decode(File::get($jsonPath), true);
        $musicData = $data['musicData'] ?? [];
        $artistDetails = $data['artistDetails'] ?? [];

        // 1. Seed Artists
        $this->command->info('Seeding Artists...');
        foreach ($artistDetails as $name => $info) {
            Artist::updateOrCreate(
                ['name' => $name],
                [
                    'listeners' => $info['listeners'] ?? '1jt',
                    'description' => $info['description'] ?? null,
                    'image' => $info['image'] ?? null,
                ]
            );
        }

        // Mapping Video IDs for popular songs
        $videoMap = [
            'Bohemian Rhapsody' => 'fJ9rUzIMcZQ',
            'Don\'t Stop Me Now' => 'HgzGwKwLmgM',
            'Another One Bites the Dust' => 'rY0WxgSXdEE',
            'Radio Ga Ga' => 'azdwsXLmrHE',
            'Smells Like Teen Spirit' => 'hTWKbfoikeg',
            'Come as You Are' => 'vabnZ9-ex7o',
            'Lithium' => 'pkcJEvMcnEg',
            'Heart-Shaped Box' => 'n6P0SitRwy8',
            'In Bloom' => 'PbgKEjNBHqM',
            'Hotel California' => '09839DpTctU',
            'Take It Easy' => '4vxDuq3Bfl8',
            'Lyin\' Eyes' => '5-NlR54PqLw',
            'Desperado' => 'iNNz6_5fMpw',
            'Imagine' => 'YkgkThbgQUc',
            'Jealous Guy' => 'XgeekpA28_Y',
            'Woman' => 'ZhfUnUUqQd8',
            'Billie Jean' => 'Zi_XLORVo70',
            'Thriller' => 'sOnqjkJTMaA',
            'Beat It' => 'oRdxUFDoQe0',
            'Around the World' => 'K0HSD_i2DvA',
            'Save Your Tears' => 'XXYlFuWEuKi',
            'Do I Wanna Know?' => 'bpOSxM0rNPM',
            'Wake Me Up' => 'IcrbM1l_BoI',
            'Waiting For Love' => 'cHHLHGNpCSA',
            'Bangarang' => 'YJVmu6yttiw',
            'New Rules' => 'k2qgadSvNyU',
            'Levitating' => 'TUVcZfQe-Kw',
            'Rolling in the Deep' => 'rYEDA3JcQqw',
            'Someone Like You' => 'hLQl3WQQoQ0',
            'The Less I Know The Better' => 'sBzrzS1Ag_g',
            'Borderline' => '2g5xkLqIElU',
        ];

        // Sample audio previews (High quality royalty-free sound clips from Internet Archive / CDN)
        $sampleAudios = [
            'https://actions.google.com/sounds/v1/ambiences/outdoor_festival.ogg',
            'https://actions.google.com/sounds/v1/sports/skateboarding_skating.ogg',
            'https://actions.google.com/sounds/v1/science_fiction/alien_mothership_hum.ogg',
            'https://actions.google.com/sounds/v1/weather/light_rain.ogg',
            'https://cdn.pixabay.com/download/audio/2022/05/27/audio_1808fbf07a.mp3?filename=lofi-study-112191.mp3',
        ];

        // 2. Seed Musics
        $this->command->info('Seeding Musics (' . count($musicData) . ' songs)...');
        $createdMusicIds = [];
        foreach ($musicData as $index => $m) {
            $existing = Music::where('title', $m['title'])->where('artist', $m['artist'])->first();
            $audioUrl = ($existing && str_contains($existing->audio_url, 'apple.com')) 
                ? $existing->audio_url 
                : ($sampleAudios[$index % count($sampleAudios)]);
            $vidId = ($existing && !empty($existing->video_id)) 
                ? $existing->video_id 
                : ($videoMap[$m['title']] ?? 'fJ9rUzIMcZQ');

            $music = Music::updateOrCreate(
                ['title' => $m['title'], 'artist' => $m['artist']],
                [
                    'album' => $m['album'] ?? 'Single',
                    'year' => $m['year'] ?? 2020,
                    'genre' => $m['genre'] ?? 'Pop',
                    'duration' => $m['duration'] ?? '3:30',
                    'rating' => $m['rating'] ?? 4.5,
                    'description' => $m['description'] ?? '',
                    'image' => $m['image'] ?? null,
                    'audio_url' => $audioUrl,
                    'video_id' => $vidId,
                ]
            );
            $createdMusicIds[] = $music->id;
        }

        // 3. Seed Default Playlist
        $this->command->info('Seeding Default Playlist...');
        $playlist = Playlist::firstOrCreate(
            ['name' => 'Koleksi Legendaris Pilihan'],
            [
                'description' => 'Kurasi lagu-lagu rock klasik, pop, dan indie terbaik dengan pengalaman multimedia interaktif 3D.',
                'image' => 'image/queen1.jpg',
            ]
        );

        if (!empty($createdMusicIds)) {
            $playlist->musics()->sync(array_slice($createdMusicIds, 0, 8));
        }

        // 4. Seed Reviews
        $this->command->info('Seeding Sample Reviews...');
        if (!empty($createdMusicIds)) {
            Review::firstOrCreate(
                [
                    'music_id' => $createdMusicIds[0],
                    'music_title' => 'Bohemian Rhapsody',
                    'artist' => 'Queen',
                ],
                [
                    'rating' => 5,
                    'review' => 'Komposisi musik luar biasa! Bagian opera dan solo gitarnya tidak pernah lekang oleh waktu.',
                ]
            );

            Review::firstOrCreate(
                [
                    'music_id' => $createdMusicIds[4] ?? $createdMusicIds[0],
                    'music_title' => 'Smells Like Teen Spirit',
                    'artist' => 'Nirvana',
                ],
                [
                    'rating' => 5,
                    'review' => 'Riff gitar yang sangat bertenaga, merevolusi era musik grunge 90-an.',
                ]
            );
        }

        $this->command->info('Database seeding completed successfully!');
    }
}
