@extends('layouts.app')

@section('content')

    <!-- ==================== 1. BERANDA SECTION ==================== -->
    <section id="home" class="section active transition-opacity duration-300">
        <!-- Rekomendasi Teratas (10 Lagu: Tampil 6 Awal + Horizontal Slide Gaya YouTube Music) -->
        <div class="mb-14">
            <div class="flex items-center justify-between gap-4 mb-5">
                <div>
                    <h2 class="text-3xl font-extrabold text-[#f0f2f5] tracking-tight flex items-center gap-2.5">
                        <span>Direkomendasikan</span>
                        <span class="text-xs bg-[#0075ff] text-white font-bold px-2.5 py-0.5 rounded-full">Top 10 Rated</span>
                    </h2>
                    <p class="text-sm text-slate-400 mt-1">Lagu pilihan dengan apresiasi dan rating tertinggi.</p>
                </div>

                <!-- Kontrol Kanan Atas Gaya YouTube Music: Tombol "Selengkapnya" (button-secondary / pill-badge) + Panah Bulat < > -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button type="button" onclick="showSection('discover', document.querySelectorAll('.nav-btn')[1])" class="px-3.5 py-1.5 rounded-full bg-[#0075ff] hover:bg-[#0062d6] text-[13px] font-semibold text-white transition-colors">
                        Selengkapnya
                    </button>
                    <button type="button" id="recPrevBtn" onclick="slideRecommended(-1)" disabled class="w-9 h-9 rounded-full bg-[#2e3035] border border-[#2d2f33] text-[#53575f] cursor-not-allowed flex items-center justify-center transition-all" title="Sebelumnya">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" id="recNextBtn" onclick="slideRecommended(1)" class="w-9 h-9 rounded-full bg-[#2e3035] hover:bg-[#383b42] border border-[#2d2f33] text-[#f0f2f5] flex items-center justify-center transition-all active:scale-95" title="Selanjutnya">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>

            <!-- Horizontal Carousel Container (design.md game-tile: Full-bleed artwork inside 16px rounded corners, flat #2e3035 surface + #2d2f33 hairline) -->
            <div class="relative">
                <div id="forYouGrid" onscroll="onRecommendedScroll()" class="flex items-stretch gap-4 overflow-x-auto no-scrollbar py-1 select-none cursor-grab active:cursor-grabbing">
                    @foreach($recommended as $song)
                        <div class="game-tile flex-shrink-0 w-[calc((100%-1rem)/2)] sm:w-[calc((100%-2rem)/3)] md:w-[calc((100%-3rem)/4)] lg:w-[calc((100%-5rem)/6)] bg-[#2e3035] rounded-[16px] border border-[#2d2f33] overflow-hidden transition-colors duration-200 group flex flex-col justify-between">
                            <!-- Top: Full-Bleed Artwork (No internal padding around image per design.md) -->
                            <div class="relative w-full aspect-square bg-[#222428] overflow-hidden cursor-pointer" onclick="openMusicModal({{ $song->id }})">
                                <img src="{{ asset($song->image ?? 'image/queen1.jpg') }}" alt="{{ $song->title }}" draggable="false" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <!-- Overlay Play, 3D, Live Lyrics & Video MV Buttons -->
                                <div class="absolute inset-0 bg-black/65 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1.5 px-1">
                                    <button onclick="event.stopPropagation(); playSong({{ $song->id }}, '{{ addslashes($song->title) }}', '{{ addslashes($song->artist) }}', '{{ asset($song->image ?? 'image/queen1.jpg') }}', '{{ $song->audio_url }}', '{{ $song->video_id }}')" class="w-9 h-9 p-0 leading-none bg-[#00b24b] hover:bg-[#00c853] text-white rounded-full flex items-center justify-center font-bold transition-transform hover:scale-110 flex-shrink-0" title="Putar Musik">
                                        <svg class="w-4 h-4 fill-current block m-auto" viewBox="0 0 24 24"><path d="M7.5 5.5v13l10-6.5z"/></svg>
                                    </button>
                                    <button onclick="event.stopPropagation(); openVinylWithSong('{{ addslashes($song->title) }}', '{{ addslashes($song->artist) }}', '{{ asset($song->image ?? 'image/queen1.jpg') }}', {{ $song->id }}, '{{ $song->audio_url }}', '{{ $song->video_id }}')" class="w-8 h-8 bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] rounded-full flex items-center justify-center transition-transform hover:scale-110 flex-shrink-0" title="Putar di Piringan 3D">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M16 3.937A9 9 0 1 0 21 12"/><path d="M11 12a1 1 0 1 0 2 0a1 1 0 1 0-2 0m8-8a1 1 0 1 0 2 0a1 1 0 1 0-2 0"/><path d="m20 4l-3.5 10l-2.5 2"/></g></svg>
                                    </button>
                                    <button onclick="event.stopPropagation(); openLyricsWithSong('{{ addslashes($song->title) }}', '{{ addslashes($song->artist) }}', '{{ asset($song->image ?? 'image/queen1.jpg') }}', {{ $song->id }}, '{{ $song->audio_url }}', '{{ $song->video_id }}')" class="w-8 h-8 bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] rounded-full flex items-center justify-center transition-transform hover:scale-110 flex-shrink-0" title="Putar dengan Lirik">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M470.8 365.6c-.9-53.7-1.6-107.4-2.4-161.1c-.9-63.6-1.7-127.3-2.6-190.9c-.1-9.9-6.6-15.2-15.9-13.1c-25.8 5.9-51.7 11.7-77.5 17.5C306.7 32.7 241 47.5 175.3 62.2c-9.1 2-12.2 6.1-12.1 15.5c.2 7.5.3 14.9.4 22.4c1.3 90.7 2.5 181.4 3.7 272.1v9.8c-2.5-1.9-4.4-3.5-6.4-5c-17-12.6-36.1-17.4-56.9-14c-51.5 8.5-78.6 64.8-53.4 110.5c15.3 27.7 48.4 43.4 78.8 37.3c38-7.6 62.5-38.6 61.7-77.8c-.4-20.2-.8-40.4-1.1-60.7l-3-224.1c-.1-4.6 0-9.2 0-14c85.6-19.2 171.1-38.4 256.8-57.6c1.1 81.6 2.2 162.6 3.3 244.4c-1.7-1.3-2.6-2.1-3.6-2.9c-17.3-13.9-37.1-19.3-58.9-16c-54.2 8.2-83.1 71.4-49.5 117.3c17.9 24.4 42.6 35.1 72.6 30.4c30.3-4.8 50.1-23 59.9-52c3.6-10.6 3.4-21.4 3.2-32.2M115.6 489.4c-28.6.1-51.7-23.7-51.6-52.8s23.6-52.6 53-52.2c27.9.4 51.4 22.4 51.2 53.2c-.2 29.6-23.7 51.8-52.6 51.8m327.7-438c0 .9-2.1 2.3-3.4 2.6c-12.5 3-25 5.8-37.5 8.6c-20 4.5-40.1 8.9-60.1 13.4c-41.4 9.3-82.8 18.5-124.3 27.8c-9.6 2.2-19.3 4.3-28.9 6.5c-.6.1-1.2.1-3 .3c0-8.8-.1-17.4.2-25.9c0-.9 2.2-2.2 3.5-2.5c14.7-3.4 29.5-6.7 44.3-10c19.8-4.4 39.5-8.8 59.3-13.3l113.7-25.5c10.6-2.4 21.2-4.9 31.9-7.3c1.1-.2 2.2-.2 4.4-.4c.1 8.9.2 17.3-.1 25.7m-47.4 377c-28.1 0-52-24.1-52-52.4c0-28.6 23.8-52.7 52.1-52.7c28.9 0 52.3 23.7 52.3 53.1c-.2 28.3-24 52-52.4 52"/><path fill="currentColor" d="M64 436.6c.1-29.1 23.6-52.6 53-52.2c27.9.4 51.4 22.4 51.2 53.2c-.2 29.6-23.7 51.8-52.6 51.8c-28.6.1-51.7-23.7-51.6-52.8m384.2-60.2c0-29.4-23.3-53.1-52.3-53.1c-28.3 0-52.1 24.1-52.1 52.7c0 28.2 24 52.4 52 52.4c28.5 0 52.3-23.7 52.4-52m-259-266c9.6-2.2 19.3-4.3 28.9-6.5c41.4-9.3 82.8-18.6 124.3-27.8c20-4.5 40.1-8.9 60.1-13.4c12.5-2.8 25-5.6 37.5-8.6c1.3-.3 3.4-1.7 3.4-2.6c.3-8.4.2-16.9.2-25.8c-2.2.2-3.3.1-4.4.4c-10.6 2.4-21.2 4.9-31.9 7.3L293.6 58.9c-19.8 4.4-39.5 8.8-59.3 13.3c-14.8 3.3-29.5 6.6-44.3 10c-1.4.3-3.5 1.6-3.5 2.5c-.3 8.6-.2 17.1-.2 25.9c1.7-.1 2.3-.1 2.9-.2" opacity=".7"/></svg>
                                    </button>
                                    <button onclick="event.stopPropagation(); openVideoModal({{ $song->id }}, '{{ addslashes($song->title) }}', '{{ addslashes($song->artist) }}', '{{ $song->video_id }}', '{{ asset($song->image ?? 'image/queen1.jpg') }}')" class="w-8 h-8 bg-[#2e3035] hover:bg-red-600 text-red-400 hover:text-white border border-[#2d2f33] rounded-full flex items-center justify-center transition-transform hover:scale-110 flex-shrink-0" title="Tonton Video MV">
                                        <svg class="w-3.5 h-3.5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>youtube</title><path fill="currentColor" d="m10 15l5.19-3L10 9zm11.56-7.83c.13.47.22 1.1.28 1.9c.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83c-.25.9-.83 1.48-1.73 1.73c-.47.13-1.33.22-2.65.28c-1.3.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44c-.9-.25-1.48-.83-1.73-1.73c-.13-.47-.22-1.1-.28-1.9c-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83c.25-.9.83-1.48 1.73-1.73c.47-.13 1.33-.22 2.65-.28c1.3-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44c.9.25 1.48.83 1.73 1.73"/></svg>
                                    </button>
                                </div>
                                <span class="absolute top-2.5 right-2.5 bg-[#141415]/90 px-2 py-0.5 rounded-[4px] text-[12px] font-semibold text-yellow-400 flex items-center gap-0.5 border border-[#2d2f33]">
                                    ★ {{ $song->rating }}
                                </span>
                            </div>

                            <!-- Bottom Metadata Interior (p-3 / 12px spacing.md) -->
                            <div class="p-3 flex-1 flex flex-col justify-between">
                                <div>
                                    <h3 class="font-semibold text-[#f0f2f5] text-[15px] leading-[1.3] truncate cursor-pointer hover:text-[#00b24b] transition-colors" onclick="openMusicModal({{ $song->id }})">{{ $song->title }}</h3>
                                    <p class="text-[12px] font-semibold text-[#9aa1b1] leading-[1.3] truncate mt-1">{{ $song->artist }} • {{ $song->genre }}</p>
                                </div>
                                <div class="mt-2.5 pt-2 border-t border-[#2d2f33] flex items-center justify-between text-[12px] font-semibold text-[#9aa1b1]">
                                    <span>{{ $song->year }}</span>
                                    <button onclick="openMusicModal({{ $song->id }})" class="text-[#0075ff] hover:underline font-semibold">Detail & Analisis →</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Scrollbar Horizontal Penuh Gaya YouTube Music dengan Warna Hijau Action (◄ [═══════════] ►) -->
                <div class="mt-2.5 flex items-center gap-2 w-full select-none">
                    <button type="button" onclick="slideRecommended(-1)" class="text-[#00b24b] hover:text-[#00c853] transition-colors p-0.5 flex-shrink-0" title="Geser Kiri">
                        <svg class="w-2.5 h-2.5 fill-current" viewBox="0 0 10 10"><polygon points="8,1 2,5 8,9"/></svg>
                    </button>
                    <div id="recommendedScrollbarTrack" class="relative flex-1 h-2 bg-[#2e3035] border border-[#2d2f33] rounded-full overflow-hidden cursor-pointer transition-colors" title="Tarik atau klik untuk menggeser">
                        <div id="recommendedScrollbarThumb" class="absolute top-0 bottom-0 left-0 rounded-full bg-[#00b24b] hover:bg-[#00c853] transition-colors" style="width: 60%; transform: translateX(0%);"></div>
                    </div>
                    <button type="button" onclick="slideRecommended(1)" class="text-[#00b24b] hover:text-[#00c853] transition-colors p-0.5 flex-shrink-0" title="Geser Kanan">
                        <svg class="w-2.5 h-2.5 fill-current" viewBox="0 0 10 10"><polygon points="2,1 8,5 2,9"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Trending Minggu Ini (Gaya Tangga Lagu / Top 10 Weekly Chart #01 - #10) -->
        <div class="mb-14">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2 mb-6">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="text-3xl font-extrabold text-[#f0f2f5] tracking-tight">Trending Minggu Ini</h2>
                        <span class="text-xs bg-[#0075ff] text-white font-bold px-2.5 py-0.5 rounded-full">Top 10 Chart</span>
                    </div>
                    <p class="text-sm text-slate-400 mt-1">Tangga lagu paling sering dieksplorasi penikmat musik minggu ini.</p>
                </div>
                <span class="text-xs text-slate-500 font-mono hidden sm:inline">Peringkat 01 – 10</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 lg:grid-rows-5 lg:grid-flow-col gap-3.5" id="trendingGrid">
                @foreach($trending as $index => $song)
                    @php
                        $rank = $index + 1;
                        $rankFormatted = str_pad($rank, 2, '0', STR_PAD_LEFT);
                        if ($rank === 1) {
                            $rankStyle = 'text-yellow-400';
                        } elseif ($rank === 2) {
                            $rankStyle = 'text-slate-200';
                        } elseif ($rank === 3) {
                            $rankStyle = 'text-amber-500';
                        } else {
                            $rankStyle = 'text-[#9aa1b1]';
                        }
                    @endphp
                    <div class="bg-[#2e3035] hover:bg-[#34373d] rounded-[16px] p-3 border border-[#2d2f33] transition-colors duration-200 group flex items-center justify-between gap-3">
                        <!-- Left: Rank Badge + Cover + Song Metadata -->
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <!-- Rank Number -->
                            <span class="w-7 text-center {{ $rankStyle }} font-extrabold text-[16px] leading-none flex-shrink-0">{{ $rankFormatted }}</span>

                            <!-- Album Cover with Play Overlay -->
                            <div class="relative w-14 h-14 rounded-[10px] overflow-hidden bg-[#222428] flex-shrink-0 cursor-pointer border border-[#2d2f33]" onclick="openMusicModal({{ $song->id }})">
                                <img src="{{ asset($song->image ?? 'image/queen1.jpg') }}" alt="{{ $song->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                <button onclick="event.stopPropagation(); playSong({{ $song->id }}, '{{ addslashes($song->title) }}', '{{ addslashes($song->artist) }}', '{{ asset($song->image ?? 'image/queen1.jpg') }}', '{{ $song->audio_url }}', '{{ $song->video_id }}')" class="absolute inset-0 bg-black/65 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-[#00b24b]" title="Putar Lagu">
                                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M7.5 5.5v13l10-6.5z"/></svg>
                                </button>
                            </div>

                            <!-- Title, Artist & Badges -->
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-semibold text-[15px] leading-[1.3] text-[#f0f2f5] truncate cursor-pointer hover:text-[#00b24b] transition-colors" onclick="openMusicModal({{ $song->id }})">
                                        {{ $song->title }}
                                    </h4>
                                    <span class="px-1.5 py-0.5 rounded-[4px] bg-[#141415] border border-[#2d2f33] text-[11px] font-semibold text-yellow-400 flex-shrink-0">
                                        ★ {{ $song->rating }}
                                    </span>
                                </div>
                                <p class="text-[12px] font-semibold text-[#9aa1b1] truncate mt-0.5">
                                    <span class="text-[#f0f2f5]">{{ $song->artist }}</span> • {{ $song->genre }} • {{ $song->year }}
                                </p>
                                <div class="flex items-center gap-2 mt-1 text-[12px] font-semibold text-[#9aa1b1]">
                                    <span class="font-mono">{{ $song->duration ?? '3:30' }}</span>
                                    <span>•</span>
                                    <button onclick="openMusicModal({{ $song->id }})" class="text-[#0075ff] hover:underline font-semibold">Detail & Analisis →</button>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Quick Multimedia Mode Buttons (Play | 3D | Lirik | MV) -->
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <button onclick="playSong({{ $song->id }}, '{{ addslashes($song->title) }}', '{{ addslashes($song->artist) }}', '{{ asset($song->image ?? 'image/queen1.jpg') }}', '{{ $song->audio_url }}', '{{ $song->video_id }}')" class="w-8 h-8 rounded-[10px] bg-[#00b24b] hover:bg-[#00c853] text-white flex items-center justify-center transition-transform hover:scale-105" title="Putar Musik">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M7.5 5.5v13l10-6.5z"/></svg>
                            </button>
                            <button onclick="openVinylWithSong('{{ addslashes($song->title) }}', '{{ addslashes($song->artist) }}', '{{ asset($song->image ?? 'image/queen1.jpg') }}', {{ $song->id }}, '{{ $song->audio_url }}', '{{ $song->video_id }}')" class="w-8 h-8 rounded-[10px] bg-[#141415] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] flex items-center justify-center transition-transform hover:scale-105" title="Putar di Piringan 3D">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M16 3.937A9 9 0 1 0 21 12"/><path d="M11 12a1 1 0 1 0 2 0a1 1 0 1 0-2 0m8-8a1 1 0 1 0 2 0a1 1 0 1 0-2 0"/><path d="m20 4l-3.5 10l-2.5 2"/></g></svg>
                            </button>
                            <button onclick="openLyricsWithSong('{{ addslashes($song->title) }}', '{{ addslashes($song->artist) }}', '{{ asset($song->image ?? 'image/queen1.jpg') }}', {{ $song->id }}, '{{ $song->audio_url }}', '{{ $song->video_id }}')" class="w-8 h-8 rounded-[10px] bg-[#141415] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] flex items-center justify-center transition-transform hover:scale-105" title="Putar dengan Lirik">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M470.8 365.6c-.9-53.7-1.6-107.4-2.4-161.1c-.9-63.6-1.7-127.3-2.6-190.9c-.1-9.9-6.6-15.2-15.9-13.1c-25.8 5.9-51.7 11.7-77.5 17.5C306.7 32.7 241 47.5 175.3 62.2c-9.1 2-12.2 6.1-12.1 15.5c.2 7.5.3 14.9.4 22.4c1.3 90.7 2.5 181.4 3.7 272.1v9.8c-2.5-1.9-4.4-3.5-6.4-5c-17-12.6-36.1-17.4-56.9-14c-51.5 8.5-78.6 64.8-53.4 110.5c15.3 27.7 48.4 43.4 78.8 37.3c38-7.6 62.5-38.6 61.7-77.8c-.4-20.2-.8-40.4-1.1-60.7l-3-224.1c-.1-4.6 0-9.2 0-14c85.6-19.2 171.1-38.4 256.8-57.6c1.1 81.6 2.2 162.6 3.3 244.4c-1.7-1.3-2.6-2.1-3.6-2.9c-17.3-13.9-37.1-19.3-58.9-16c-54.2 8.2-83.1 71.4-49.5 117.3c17.9 24.4 42.6 35.1 72.6 30.4c30.3-4.8 50.1-23 59.9-52c3.6-10.6 3.4-21.4 3.2-32.2M115.6 489.4c-28.6.1-51.7-23.7-51.6-52.8s23.6-52.6 53-52.2c27.9.4 51.4 22.4 51.2 53.2c-.2 29.6-23.7 51.8-52.6 51.8m327.7-438c0 .9-2.1 2.3-3.4 2.6c-12.5 3-25 5.8-37.5 8.6c-20 4.5-40.1 8.9-60.1 13.4c-41.4 9.3-82.8 18.5-124.3 27.8c-9.6 2.2-19.3 4.3-28.9 6.5c-.6.1-1.2.1-3 .3c0-8.8-.1-17.4.2-25.9c0-.9 2.2-2.2 3.5-2.5c14.7-3.4 29.5-6.7 44.3-10c19.8-4.4 39.5-8.8 59.3-13.3l113.7-25.5c10.6-2.4 21.2-4.9 31.9-7.3c1.1-.2 2.2-.2 4.4-.4c.1 8.9.2 17.3-.1 25.7m-47.4 377c-28.1 0-52-24.1-52-52.4c0-28.6 23.8-52.7 52.1-52.7c28.9 0 52.3 23.7 52.3 53.1c-.2 28.3-24 52-52.4 52"/><path fill="currentColor" d="M64 436.6c.1-29.1 23.6-52.6 53-52.2c27.9.4 51.4 22.4 51.2 53.2c-.2 29.6-23.7 51.8-52.6 51.8c-28.6.1-51.7-23.7-51.6-52.8m384.2-60.2c0-29.4-23.3-53.1-52.3-53.1c-28.3 0-52.1 24.1-52.1 52.7c0 28.2 24 52.4 52 52.4c28.5 0 52.3-23.7 52.4-52m-259-266c9.6-2.2 19.3-4.3 28.9-6.5c41.4-9.3 82.8-18.6 124.3-27.8c20-4.5 40.1-8.9 60.1-13.4c12.5-2.8 25-5.6 37.5-8.6c1.3-.3 3.4-1.7 3.4-2.6c.3-8.4.2-16.9.2-25.8c-2.2.2-3.3.1-4.4.4c-10.6 2.4-21.2 4.9-31.9 7.3L293.6 58.9c-19.8 4.4-39.5 8.8-59.3 13.3c-14.8 3.3-29.5 6.6-44.3 10c-1.4.3-3.5 1.6-3.5 2.5c-.3 8.6-.2 17.1-.2 25.9c1.7-.1 2.3-.1 2.9-.2" opacity=".7"/></svg>
                            </button>
                            <button onclick="openVideoModal({{ $song->id }}, '{{ addslashes($song->title) }}', '{{ addslashes($song->artist) }}', '{{ $song->video_id }}', '{{ asset($song->image ?? 'image/queen1.jpg') }}')" class="w-8 h-8 rounded-[10px] bg-[#141415] hover:bg-red-600 text-red-400 hover:text-white border border-[#2d2f33] flex items-center justify-center transition-transform hover:scale-105" title="Tonton Video MV">
                                <svg class="w-3.5 h-3.5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="m10 15l5.19-3L10 9zm11.56-7.83c.13.47.22 1.1.28 1.9c.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83c-.25.9-.83 1.48-1.73 1.73c-.47.13-1.33.22-2.65.28c-1.3.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44c-.9-.25-1.48-.83-1.73-1.73c-.13-.47-.22-1.1-.28-1.9c-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83c.25-.9.83-1.48 1.73-1.73c.47-.13 1.33-.22 2.65-.28c1.3-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44c.9.25 1.48.83 1.73 1.73"/></svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Footer Navigations (design.md footer: canvas #141415 + hairline #2d2f33) -->
        <footer class="mt-16 pt-10 pb-6 border-t border-[#2d2f33] text-sm text-[#9aa1b1]">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-10">
                <!-- Kolom 1: Brand & Deskripsi -->
                <div class="space-y-3">
                    <a href="javascript:void(0)" onclick="showSection('home', document.querySelectorAll('.nav-btn')[0]); window.scrollTo({ top: 0, behavior: 'smooth' });" class="inline-flex items-center space-x-2">
                        <svg class="h-7 w-7 text-[#00b24b]" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="3" y="8" width="4" height="8" rx="1" class="opacity-70"></rect>
                            <rect x="9" y="4" width="6" height="16" rx="2"></rect>
                            <rect x="17" y="8" width="4" height="8" rx="1" class="opacity-70"></rect>
                        </svg>
                        <span class="text-xl font-extrabold tracking-wide text-[#f0f2f5]">Minima</span>
                    </a>
                    <p class="text-xs text-[#9aa1b1] leading-relaxed">
                        Platform jelajah musik interaktif berbasis Web dengan integrasi 5 elemen multimedia: Teks Analisis & Lirik, Gambar, Audio Digital Full, Video MV, dan Objek 3D Piringan Hitam.
                    </p>
                </div>

                <!-- Kolom 2: Navigasi Utama -->
                <div>
                    <h4 class="text-xs font-extrabold text-[#f0f2f5] uppercase tracking-wider mb-3.5">Navigasi Halaman</h4>
                    <ul class="space-y-2 text-xs">
                        <li>
                            <button type="button" onclick="showSection('home', document.querySelectorAll('.nav-btn')[0]); window.scrollTo({ top: 0, behavior: 'smooth' });" class="text-[#9aa1b1] hover:text-[#f0f2f5] transition-colors">Beranda Utama</button>
                        </li>
                        <li>
                            <button type="button" onclick="showSection('discover', document.querySelectorAll('.nav-btn')[1]); window.scrollTo({ top: 0, behavior: 'smooth' });" class="text-[#9aa1b1] hover:text-[#f0f2f5] transition-colors">Telusuri Katalog (200 Lagu)</button>
                        </li>
                        <li>
                            <button type="button" onclick="showSection('playlists', document.querySelectorAll('.nav-btn')[2]); window.scrollTo({ top: 0, behavior: 'smooth' });" class="text-[#9aa1b1] hover:text-[#f0f2f5] transition-colors">Koleksi Playlist</button>
                        </li>
                        <li>
                            <button type="button" onclick="showSection('about', document.getElementById('btn-about')); window.scrollTo({ top: 0, behavior: 'smooth' });" class="text-[#9aa1b1] hover:text-[#f0f2f5] transition-colors">Tentang Kami (Kelompok 2)</button>
                        </li>
                    </ul>
                </div>

                <!-- Kolom 3: Jelajah Genre Cepat -->
                <div>
                    <h4 class="text-xs font-extrabold text-[#f0f2f5] uppercase tracking-wider mb-3.5">Jelajah Genre Musik</h4>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($genres as $genre)
                            <button type="button" onclick="showSection('discover', document.querySelectorAll('.nav-btn')[1]); setGenreFilter('{{ $genre }}', null); window.scrollTo({ top: 0, behavior: 'smooth' });" class="px-2.5 py-1 rounded-[8px] bg-[#2e3035] hover:bg-[#0075ff] text-[#f0f2f5] hover:text-white border border-[#2d2f33] hover:border-[#0075ff] text-xs font-semibold transition-colors">
                                {{ $genre }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Kolom 4: Fitur Multimedia Interaktif (Pajangan / Non-Interaktif) -->
                <div>
                    <h4 class="text-xs font-extrabold text-[#f0f2f5] uppercase tracking-wider mb-3.5">Fitur Multimedia</h4>
                    <ul class="space-y-2 text-xs">
                        <li>
                            <span class="text-[#9aa1b1] hover:text-[#f0f2f5] transition-colors inline-flex items-center gap-2 cursor-default select-none">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#00b24b]"></span>
                                <span>3D Vinyl Turntable (Three.js)</span>
                            </span>
                        </li>
                        <li>
                            <span class="text-[#9aa1b1] hover:text-[#f0f2f5] transition-colors inline-flex items-center gap-2 cursor-default select-none">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#0075ff]"></span>
                                <span>Live Synced Lyrics Interaktif</span>
                            </span>
                        </li>
                        <li>
                            <span class="text-[#9aa1b1] hover:text-[#f0f2f5] transition-colors inline-flex items-center gap-2 cursor-default select-none">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#00b24b]"></span>
                                <span>Video MV & Audiovisualizer HD</span>
                            </span>
                        </li>
                        <li>
                            <span class="text-[#9aa1b1] hover:text-[#f0f2f5] transition-colors inline-flex items-center gap-2 cursor-default select-none">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#0075ff]"></span>
                                <span>Akun & Playlist Pribadi</span>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Copyright Bar -->
            <div class="pt-6 border-t border-[#2d2f33] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-[#53575f]">
                <p>© 2026 <strong class="text-[#9aa1b1]">Minima Interactive Audio</strong> • Proyek Sistem Multimedia Kelompok 2 (Kelas 17.5A.27 - UBSI)</p>
                <div class="flex items-center gap-4">
                    <button type="button" onclick="showSection('about', document.getElementById('btn-about')); window.scrollTo({ top: 0, behavior: 'smooth' });" class="text-[#9aa1b1] hover:text-[#f0f2f5] transition-colors">Profil Tim</button>
                    <span>•</span>
                    <button type="button" onclick="showSection('discover', document.querySelectorAll('.nav-btn')[1]); window.scrollTo({ top: 0, behavior: 'smooth' });" class="text-[#9aa1b1] hover:text-[#f0f2f5] transition-colors">Katalog Musik</button>
                    <span>•</span>
                    <button type="button" onclick="window.scrollTo({ top: 0, behavior: 'smooth' });" class="text-[#0075ff] hover:underline font-semibold">Kembali ke Atas ↑</button>
                </div>
            </div>
        </footer>
    </section>

    <!-- ==================== 2. TELUSURI SECTION ==================== -->
    <section id="discover" class="section hidden transition-opacity duration-300">
        <!-- Detail Artis jika sedang dipilih -->
        <div id="artistInfoContainer" class="hidden mb-8">
            <button onclick="handleBackToArtists()" class="text-[#9aa1b1] hover:text-[#f0f2f5] mb-4 inline-flex items-center text-sm font-semibold transition-colors">
                <svg class="h-4 w-4 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Kembali ke Daftar Artis
            </button>
            <div class="bg-[#2e3035] rounded-[16px] p-6 sm:p-8 border border-[#2d2f33] flex flex-col md:flex-row items-center gap-8">
                <img id="artistDisplayImg" src="" class="w-36 h-36 rounded-[16px] object-cover border border-[#2d2f33]">
                <div class="text-center md:text-left flex-1">
                    <h2 id="artistNameDisplay" class="text-3xl md:text-4xl font-extrabold text-[#f0f2f5] mb-2">Nama Artis</h2>
                    <p class="text-[#0075ff] font-semibold mb-3 text-sm">
                        <span id="artistListeners">0</span> pendengar bulanan
                    </p>
                    <p id="artistDescription" class="text-[#9aa1b1] text-sm leading-relaxed max-w-3xl">Deskripsi...</p>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h2 id="discoverTitle" class="text-3xl font-extrabold text-[#f0f2f5] tracking-tight">Katalog Musik & Artis</h2>
                <p class="text-sm text-[#9aa1b1] mt-1">Menampilkan seluruh koleksi musik (<span id="discoverTotalCount" class="text-[#00b24b] font-bold">200 Lagu</span>) lengkap dari berbagai genre dan era.</p>
            </div>

            <!-- Sort By (design.md text-input: #2e3035 + #2d2f33 + 8px rounded) -->
            <div class="flex items-center gap-3">
                <label class="text-xs font-semibold text-[#9aa1b1] uppercase tracking-wider">Urutkan:</label>
                <select id="discoverSortSelect" onchange="fetchMusics()" class="bg-[#2e3035] text-[#f0f2f5] text-[14px] font-semibold border border-[#2d2f33] rounded-[8px] px-3 py-2 focus:outline-none focus:border-[#0075ff] cursor-pointer">
                    <option value="rating">Rating Tertinggi</option>
                    <option value="az">Abjad (A - Z)</option>
                    <option value="za">Abjad (Z - A)</option>
                    <option value="year">Tahun Rilis Terbaru</option>
                </select>
            </div>
        </div>

        <!-- Filter Genre Buttons (design.md pill-outline: rounded-full + #2d2f33 border, active in #00b24b + white text) -->
        <div class="mb-7">
            <h3 class="text-xs font-bold text-[#9aa1b1] uppercase tracking-wider mb-3">Pilih Genre</h3>
            <div class="flex flex-wrap gap-2" id="genreFilterButtons">
                <button onclick="setGenreFilter('All', this)" class="genre-btn px-3.5 py-1.5 rounded-full text-[13px] font-semibold transition-colors bg-[#00b24b] text-white border border-[#00b24b]">Semua (All)</button>
                @foreach($genres as $genre)
                    <button onclick="setGenreFilter('{{ $genre }}', this)" class="genre-btn px-3.5 py-1.5 rounded-full text-[13px] font-semibold transition-colors bg-[#2e3035] text-[#f0f2f5] hover:bg-[#383b42] border border-[#2d2f33]">{{ $genre }}</button>
                @endforeach
            </div>
        </div>

        <!-- Filter Decade Era (design.md bet-odds-button: rounded-[8px] + #2e3035, active state in #0075ff electric blue + white text) -->
        <div class="mb-8">
            <h3 class="text-xs font-bold text-[#9aa1b1] uppercase tracking-wider mb-3">Era / Dekade Rilis</h3>
            <div class="flex flex-wrap gap-2" id="decadeButtons">
                <button onclick="setDecadeFilter('all', this)" class="decade-btn px-3 py-2 rounded-[8px] text-[13px] font-semibold transition-colors bg-[#0075ff] text-white border border-[#0075ff]">Semua Era</button>
                <button onclick="setDecadeFilter('1960', this)" class="decade-btn px-3 py-2 rounded-[8px] text-[13px] font-semibold transition-colors bg-[#2e3035] text-[#f0f2f5] hover:bg-[#383b42] border border-[#2d2f33]">1960s</button>
                <button onclick="setDecadeFilter('1970', this)" class="decade-btn px-3 py-2 rounded-[8px] text-[13px] font-semibold transition-colors bg-[#2e3035] text-[#f0f2f5] hover:bg-[#383b42] border border-[#2d2f33]">1970s</button>
                <button onclick="setDecadeFilter('1980', this)" class="decade-btn px-3 py-2 rounded-[8px] text-[13px] font-semibold transition-colors bg-[#2e3035] text-[#f0f2f5] hover:bg-[#383b42] border border-[#2d2f33]">1980s</button>
                <button onclick="setDecadeFilter('1990', this)" class="decade-btn px-3 py-2 rounded-[8px] text-[13px] font-semibold transition-colors bg-[#2e3035] text-[#f0f2f5] hover:bg-[#383b42] border border-[#2d2f33]">1990s</button>
                <button onclick="setDecadeFilter('2000', this)" class="decade-btn px-3 py-2 rounded-[8px] text-[13px] font-semibold transition-colors bg-[#2e3035] text-[#f0f2f5] hover:bg-[#383b42] border border-[#2d2f33]">2000s</button>
                <button onclick="setDecadeFilter('2010', this)" class="decade-btn px-3 py-2 rounded-[8px] text-[13px] font-semibold transition-colors bg-[#2e3035] text-[#f0f2f5] hover:bg-[#383b42] border border-[#2d2f33]">2010s</button>
                <button onclick="setDecadeFilter('2020', this)" class="decade-btn px-3 py-2 rounded-[8px] text-[13px] font-semibold transition-colors bg-[#2e3035] text-[#f0f2f5] hover:bg-[#383b42] border border-[#2d2f33]">2020s & Baru</button>
            </div>
        </div>

        <!-- Dynamic Musics Grid Container -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5" id="discoverGrid">
            <!-- Diisi lewat JavaScript fetch API -->
        </div>

        <!-- Navigasi Halaman (Pagination) -->
        <div id="discoverPagination" class="mt-10 border-t border-[#2d2f33] pt-6">
            <!-- Diisi dinamis lewat JavaScript -->
        </div>
    </section>


    <!-- ==================== 3. PLAYLISTS SECTION ==================== -->
    <section id="playlists" class="section hidden transition-opacity duration-300">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-extrabold text-[#f0f2f5] tracking-tight">Koleksi Playlist</h2>
                <p class="text-sm text-[#9aa1b1] mt-1">Atur daftar putar multimedia sesuai suasana hatimu.</p>
            </div>
            <button onclick="openCreatePlaylistModal()" class="px-3.5 py-2 bg-[#00b24b] hover:bg-[#00c853] text-white font-semibold rounded-[10px] text-[14px] leading-[1.4] transition-colors flex items-center gap-2">
                <span>+</span> Buat Playlist Baru
            </button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5" id="playlistsGrid">
            <!-- Diisi lewat JavaScript -->
        </div>
    </section>

    <!-- ==================== 4. PLAYLIST DETAIL VIEW ==================== -->
    <section id="playlist-view" class="section hidden transition-opacity duration-300">
        <button onclick="showSection('playlists', document.querySelector('.nav-btn:nth-child(3)'))" class="mb-6 flex items-center text-sm font-semibold text-[#9aa1b1] hover:text-[#f0f2f5] transition-colors">
            <svg class="h-4 w-4 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Kembali ke Daftar Playlist
        </button>

        <div class="flex flex-col md:flex-row items-center md:items-end gap-8 mb-8 bg-[#2e3035] p-6 sm:p-8 rounded-[16px] border border-[#2d2f33]">
            <div class="relative group w-48 h-48 flex-shrink-0">
                <div id="playlistViewImage" class="w-full h-full bg-[#141415] rounded-[16px] flex items-center justify-center overflow-hidden border border-[#2d2f33]">
                    <svg class="w-16 h-16 text-[#53575f]" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
                </div>
                <button type="button" onclick="triggerPlaylistImageUpload()" title="Ganti & Crop Gambar Sampul Playlist" class="absolute bottom-2.5 left-2.5 z-10 w-10 h-10 rounded-[10px] bg-[#141415]/90 hover:bg-[#00b24b] text-[#f0f2f5] hover:text-white border border-[#2d2f33] flex items-center justify-center transition-colors">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                        <circle cx="12" cy="13" r="3"/>
                    </svg>
                </button>
                <input type="file" id="playlistImageInput" accept="image/*" class="hidden" onchange="handlePlaylistImageSelect(event)">
            </div>
            <div class="w-full text-center md:text-left">
                <span id="playlistViewOwnerBadge" class="text-[11px] font-semibold uppercase tracking-wider bg-[#0075ff] text-white px-2.5 py-0.5 rounded-full mb-2 inline-block">Playlist Komunitas</span>
                <h1 id="playlistViewTitle" class="text-3xl md:text-5xl font-extrabold text-[#f0f2f5] mb-3">Nama Playlist</h1>
                <p id="playlistViewDescription" class="text-[#9aa1b1] text-sm mb-4">Deskripsi playlist...</p>
                <div class="flex items-center justify-center md:justify-start text-xs text-[#9aa1b1] gap-3">
                    <span class="bg-[#141415] px-3 py-1 rounded-full border border-[#2d2f33] font-semibold text-[#f0f2f5]"><span id="playlistViewCount">0</span> Lagu</span>
                    <button onclick="openEditPlaylistModal()" class="text-[#0075ff] hover:underline font-semibold">Edit Info Playlist</button>
                    <span class="text-[#53575f]">•</span>
                    <button id="deletePlaylistBtn" class="text-red-400 hover:underline font-semibold">Hapus Playlist</button>
                </div>
            </div>
        </div>

        <div class="bg-[#2e3035] rounded-[16px] p-6 border border-[#2d2f33]">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <h3 class="text-[18px] font-extrabold text-[#f0f2f5]">Daftar Lagu dalam Playlist</h3>
                <button type="button" onclick="focusPlaylistSongSearch()" class="px-3.5 py-2 bg-[#00b24b] hover:bg-[#00c853] text-white font-semibold rounded-[10px] text-[13px] leading-[1.4] transition-colors inline-flex items-center justify-center gap-1.5 whitespace-nowrap self-start sm:self-auto">
                    <span>+ Tambah Lagu</span>
                </button>
            </div>
            <div id="playlistViewSongs" class="space-y-2"></div>
        </div>

        <!-- Inline Song Picker: Cari & Tambahkan Lagu Langsung ke Playlist -->
        <div id="playlistAddSongsSection" class="mt-6 bg-[#2e3035] rounded-[16px] p-6 border border-[#2d2f33]">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                <div>
                    <h3 class="text-[18px] font-extrabold text-[#f0f2f5]">Cari &amp; Tambahkan Lagu</h3>
                    <p class="text-[13px] text-[#9aa1b1] mt-0.5">Cari lagu berdasarkan judul atau nama artis, lalu klik <strong class="text-[#f0f2f5]">+ Tambah</strong> untuk memasukkannya ke playlist ini.</p>
                </div>
            </div>

            <div class="relative mb-4">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#53575f] pointer-events-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input
                    type="text"
                    id="playlistSongSearchInput"
                    oninput="searchSongsForPlaylist(this.value)"
                    placeholder="Ketik judul lagu, artis, atau album..."
                    autocomplete="off"
                    class="w-full pl-10 pr-10 py-2.5 bg-[#141415] text-[#f0f2f5] placeholder-[#53575f] text-[14px] font-semibold rounded-[8px] border border-[#2d2f33] focus:outline-none focus:border-[#0075ff] transition-colors"
                />
                <button
                    type="button"
                    id="playlistSongSearchClear"
                    onclick="clearPlaylistSongSearch()"
                    title="Bersihkan pencarian"
                    class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-[#2e3035] hover:bg-[#383b42] text-[#9aa1b1] hover:text-white flex items-center justify-center text-xs transition-colors"
                >&times;</button>
            </div>

            <div id="playlistAddSongResults" class="space-y-2 max-h-[380px] overflow-y-auto pr-1">
                <p class="text-[#9aa1b1] text-sm py-3">Memuat daftar katalog lagu...</p>
            </div>
        </div>
    </section>

    <!-- ==================== 5. ULASAN SAYA SECTION ==================== -->
    <section id="reviews" class="section hidden transition-opacity duration-300">
        <div class="mb-8">
            <h2 class="text-3xl font-extrabold text-[#f0f2f5] tracking-tight">Semua Ulasan & Rating</h2>
            <p class="text-sm text-[#9aa1b1] mt-1">Daftar ulasan yang Anda berikan terhadap lagu dan komposisi audio.</p>
        </div>
        <div class="space-y-4 max-w-4xl" id="myReviewsList">
            <!-- Diisi lewat JavaScript -->
        </div>
    </section>

    <!-- ==================== 6. TENTANG KAMI SECTION ==================== -->
    <section id="about" class="section hidden transition-opacity duration-300">
        <div class="text-center mb-14">
            <h2 class="text-4xl md:text-5xl font-extrabold text-[#f0f2f5] mb-4 tracking-tight">Tentang Minima</h2>
            <p class="text-[#9aa1b1] text-lg max-w-2xl mx-auto">
                Platform multimedia interaktif berbasis Web yang menggabungkan teks, audio digital, video klip, dan objek 3D piringan hitam (*vinyl*).
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-14">
            <div class="bg-[#2e3035] rounded-[16px] p-7 border border-[#2d2f33]">
                <h3 class="text-[20px] font-extrabold text-[#f0f2f5] mb-3 flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-[#00b24b] flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/>
                        <path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/>
                    </svg>
                    <span>Visi Proyek</span>
                </h3>
                <p class="text-[#9aa1b1] leading-relaxed text-[14px] text-justify">
                    Mengembangkan aplikasi multimedia interaktif berbasis Web yang inklusif dan edukatif, di mana musik lintas era dapat diapresiasi secara visual dan auditori melalui perpaduan audio resolusi tinggi, video klip, serta teknologi 3D WebGL modern.
                </p>
            </div>
            <div class="bg-[#2e3035] rounded-[16px] p-7 border border-[#2d2f33]">
                <h3 class="text-[20px] font-extrabold text-[#f0f2f5] mb-3 flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-[#0075ff] flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <circle cx="12" cy="12" r="6"/>
                        <circle cx="12" cy="12" r="2"/>
                    </svg>
                    <span>Misi Proyek</span>
                </h3>
                <ul class="space-y-2.5 text-[#9aa1b1] text-[14px]">
                    <li class="flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-[#00b24b] mt-0.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                        <span>Menyajikan kurasi 200+ karya musik lintas 8 era genre lengkap dengan analisis latar belakang.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-[#00b24b] mt-0.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                        <span>Mengintegrasikan elemen multimedia esensial: Teks, Audio Player, Video Musik, dan Objek 3D Vinyl.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-[#00b24b] mt-0.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                        <span>Menerapkan arsitektur Model-View-Controller (MVC) menggunakan Laravel 11 dan database relasional.</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Profil Tim Pengembang (Kelompok 2 Kelas 17.5A.27) -->
        <div class="bg-[#2e3035] rounded-[16px] p-8 sm:p-10 border border-[#2d2f33] text-center relative overflow-hidden">
            <div class="relative z-10">
                <span class="inline-block text-[12px] font-semibold text-white uppercase tracking-wider bg-[#0075ff] px-3 py-1 rounded-full">Project Akhir Sistem Multimedia</span>
                <h3 class="text-3xl font-extrabold text-[#f0f2f5] mt-3 mb-2">Tim Pengembang (Kelompok 2)</h3>
                <p class="text-[#9aa1b1] text-sm mb-10">Kelas 17.5A.27 • Universitas Bina Sarana Informatika</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-left">
                    <!-- Muhammad Surya Triabadi -->
                    <div class="bg-[#141415] p-5 rounded-[16px] border border-[#2d2f33] hover:border-[#0075ff] transition-colors">
                        <div class="w-11 h-11 bg-[#0075ff] text-white rounded-full flex items-center justify-center font-extrabold mb-3 text-base">
                            MS
                        </div>
                        <h4 class="font-semibold text-[#f0f2f5] text-[15px]">Muhammad Surya Triabadi</h4>
                        <p class="text-xs text-[#9aa1b1]">NIM: 17240151</p>
                        <a href="https://instagram.com/surrddawg" target="_blank" class="text-[12px] text-[#0075ff] hover:underline mt-2 inline-block">@surrddawg</a>
                    </div>

                    <!-- Al Muzamil Sahri Ramadhan -->
                    <div class="bg-[#141415] p-5 rounded-[16px] border border-[#2d2f33] hover:border-[#00b24b] transition-colors">
                        <div class="w-11 h-11 bg-[#00b24b] text-white rounded-full flex items-center justify-center font-extrabold mb-3 text-base">
                            AM
                        </div>
                        <h4 class="font-semibold text-[#f0f2f5] text-[15px]">Al Muzamil Sahri Ramadhan</h4>
                        <p class="text-xs text-[#9aa1b1]">NIM: 17240641</p>
                        <a href="https://instagram.com/almzmlsahrii_" target="_blank" class="text-[12px] text-[#0075ff] hover:underline mt-2 inline-block">@almzmlsahrii_</a>
                    </div>

                    <!-- Benaya Bagas Christianto -->
                    <div class="bg-[#141415] p-5 rounded-[16px] border border-[#2d2f33] hover:border-[#0075ff] transition-colors">
                        <div class="w-11 h-11 bg-[#0075ff] text-white rounded-full flex items-center justify-center font-extrabold mb-3 text-base">
                            BB
                        </div>
                        <h4 class="font-semibold text-[#f0f2f5] text-[15px]">Benaya Bagas Christianto</h4>
                        <p class="text-xs text-[#9aa1b1]">NIM: 17240313</p>
                        <a href="https://www.instagram.com/nothingyoucanfoundhere/" target="_blank" class="text-[12px] text-[#0075ff] hover:underline mt-2 inline-block">@nothingyoucanfoundhere</a>
                    </div>

                    <!-- Reihan Rismana -->
                    <div class="bg-[#141415] p-5 rounded-[16px] border border-[#2d2f33] hover:border-[#00b24b] transition-colors">
                        <div class="w-11 h-11 bg-[#00b24b] text-white rounded-full flex items-center justify-center font-extrabold mb-3 text-base">
                            RR
                        </div>
                        <h4 class="font-semibold text-[#f0f2f5] text-[15px]">Reihan Rismana</h4>
                        <p class="text-xs text-[#9aa1b1]">NIM: 17240196</p>
                        <a href="https://www.instagram.com/rehanrismana/" target="_blank" class="text-[12px] text-[#0075ff] hover:underline mt-2 inline-block">@rehanrismana</a>
                    </div>
                </div>

                <p class="text-[#53575f] text-xs mt-10">© 2026 Minima Multimedia Project • Dilengkapi Elemen Teks, Audio, Video, dan Objek 3D</p>
            </div>
        </div>
    </section>

    <!-- ==================== MODAL: DETAIL MUSIK LENGKAP ==================== -->
    <div id="musicModal" class="modal" onclick="if(event.target === this) closeMusicModal()">
        <div class="ex-modal-card modal-card bg-[#2e3035] rounded-[16px] max-w-4xl w-full mx-4 max-h-[92vh] overflow-y-auto border border-[#2d2f33] relative p-6 sm:p-8">
            <!-- Modal Header -->
            <div class="flex justify-between items-start border-b border-[#2d2f33] pb-5 mb-6">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#0075ff] text-white uppercase tracking-wider">Detail Komposisi & Analisis</span>
                    </div>
                    <h2 id="modalTitle" class="text-2xl sm:text-3xl font-extrabold text-[#f0f2f5] tracking-tight">Judul Lagu</h2>
                    <p id="modalSubtitle" class="text-sm text-[#9aa1b1] font-semibold mt-0.5">Nama Artis • Album</p>
                </div>
                <button onclick="closeMusicModal()" class="w-9 h-9 rounded-full bg-[#141415] hover:bg-[#383b42] text-[#9aa1b1] hover:text-white flex items-center justify-center border border-[#2d2f33] transition-colors text-sm" title="Tutup Modal (Esc)">✕</button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-[300px_1fr] gap-7">
                <!-- Kolom Kiri: Cover Art, Tombol Aksi & Simpan Playlist -->
                <div class="modal-card-col-left">
                    <div class="relative group mb-5">
                        <img id="modalCoverImg" src="" class="w-full aspect-square rounded-[16px] object-cover border border-[#2d2f33] relative z-10">
                    </div>
                    
                    <!-- Quick Action Buttons: Audio, 3D Vinyl, Lyrics, Video MV (design.md button-primary & button-tertiary) -->
                    <div class="space-y-2.5 mb-6">
                        <button id="modalPlayAudioBtn" class="w-full bg-[#00b24b] hover:bg-[#00c853] text-white font-semibold py-2.5 px-3 rounded-[10px] text-[14px] leading-[1.4] transition-colors flex items-center justify-center gap-2.5">
                            <svg class="w-4 h-4 fill-current block" viewBox="0 0 24 24"><path d="M7.5 5.5v13l10-6.5z"/></svg>
                            <span>Putar Musik Asli (Full Durasi)</span>
                        </button>
                        <button id="modalOpen3dBtn" class="w-full bg-[#141415] hover:bg-[#383b42] text-[#f0f2f5] font-semibold py-2.5 px-3 rounded-[12px] text-[14px] leading-[1.4] transition-colors flex items-center justify-center gap-2.5 border border-[#2d2f33]">
                            <svg class="w-4 h-4 text-[#00b24b] flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M16 3.937A9 9 0 1 0 21 12"/><path d="M11 12a1 1 0 1 0 2 0a1 1 0 1 0-2 0m8-8a1 1 0 1 0 2 0a1 1 0 1 0-2 0"/><path d="m20 4l-3.5 10l-2.5 2"/></g></svg>
                            <span>Putar di Piringan 3D Vinyl</span>
                        </button>
                        <button id="modalOpenLyricsBtn" class="w-full bg-[#141415] hover:bg-[#383b42] text-[#f0f2f5] font-semibold py-2.5 px-3 rounded-[12px] text-[14px] leading-[1.4] transition-colors flex items-center justify-center gap-2.5 border border-[#2d2f33]">
                            <svg class="w-4 h-4 text-[#0075ff] flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M470.8 365.6c-.9-53.7-1.6-107.4-2.4-161.1c-.9-63.6-1.7-127.3-2.6-190.9c-.1-9.9-6.6-15.2-15.9-13.1c-25.8 5.9-51.7 11.7-77.5 17.5C306.7 32.7 241 47.5 175.3 62.2c-9.1 2-12.2 6.1-12.1 15.5c.2 7.5.3 14.9.4 22.4c1.3 90.7 2.5 181.4 3.7 272.1v9.8c-2.5-1.9-4.4-3.5-6.4-5c-17-12.6-36.1-17.4-56.9-14c-51.5 8.5-78.6 64.8-53.4 110.5c15.3 27.7 48.4 43.4 78.8 37.3c38-7.6 62.5-38.6 61.7-77.8c-.4-20.2-.8-40.4-1.1-60.7l-3-224.1c-.1-4.6 0-9.2 0-14c85.6-19.2 171.1-38.4 256.8-57.6c1.1 81.6 2.2 162.6 3.3 244.4c-1.7-1.3-2.6-2.1-3.6-2.9c-17.3-13.9-37.1-19.3-58.9-16c-54.2 8.2-83.1 71.4-49.5 117.3c17.9 24.4 42.6 35.1 72.6 30.4c30.3-4.8 50.1-23 59.9-52c3.6-10.6 3.4-21.4 3.2-32.2M115.6 489.4c-28.6.1-51.7-23.7-51.6-52.8s23.6-52.6 53-52.2c27.9.4 51.4 22.4 51.2 53.2c-.2 29.6-23.7 51.8-52.6 51.8m327.7-438c0 .9-2.1 2.3-3.4 2.6c-12.5 3-25 5.8-37.5 8.6c-20 4.5-40.1 8.9-60.1 13.4c-41.4 9.3-82.8 18.5-124.3 27.8c-9.6 2.2-19.3 4.3-28.9 6.5c-.6.1-1.2.1-3 .3c0-8.8-.1-17.4.2-25.9c0-.9 2.2-2.2 3.5-2.5c14.7-3.4 29.5-6.7 44.3-10c19.8-4.4 39.5-8.8 59.3-13.3l113.7-25.5c10.6-2.4 21.2-4.9 31.9-7.3c1.1-.2 2.2-.2 4.4-.4c.1 8.9.2 17.3-.1 25.7m-47.4 377c-28.1 0-52-24.1-52-52.4c0-28.6 23.8-52.7 52.1-52.7c28.9 0 52.3 23.7 52.3 53.1c-.2 28.3-24 52-52.4 52"/><path fill="currentColor" d="M64 436.6c.1-29.1 23.6-52.6 53-52.2c27.9.4 51.4 22.4 51.2 53.2c-.2 29.6-23.7 51.8-52.6 51.8c-28.6.1-51.7-23.7-51.6-52.8m384.2-60.2c0-29.4-23.3-53.1-52.3-53.1c-28.3 0-52.1 24.1-52.1 52.7c0 28.2 24 52.4 52 52.4c28.5 0 52.3-23.7 52.4-52m-259-266c9.6-2.2 19.3-4.3 28.9-6.5c41.4-9.3 82.8-18.6 124.3-27.8c20-4.5 40.1-8.9 60.1-13.4c12.5-2.8 25-5.6 37.5-8.6c1.3-.3 3.4-1.7 3.4-2.6c.3-8.4.2-16.9.2-25.8c-2.2.2-3.3.1-4.4.4c-10.6 2.4-21.2 4.9-31.9 7.3L293.6 58.9c-19.8 4.4-39.5 8.8-59.3 13.3c-14.8 3.3-29.5 6.6-44.3 10c-1.4.3-3.5 1.6-3.5 2.5c-.3 8.6-.2 17.1-.2 25.9c1.7-.1 2.3-.1 2.9-.2" opacity=".7"/></svg>
                            <span>Putar dengan Lirik</span>
                        </button>
                        <button id="modalOpenVideoBtn" class="w-full bg-[#141415] hover:bg-[#383b42] text-[#f0f2f5] font-semibold py-2.5 px-3 rounded-[12px] text-[14px] leading-[1.4] transition-colors flex items-center justify-center gap-2.5 border border-[#2d2f33]">
                            <svg class="w-4 h-4 text-red-500 fill-current flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="m10 15l5.19-3L10 9zm11.56-7.83c.13.47.22 1.1.28 1.9c.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83c-.25.9-.83 1.48-1.73 1.73c-.47.13-1.33.22-2.65.28c-1.3.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44c-.9-.25-1.48-.83-1.73-1.73c-.13-.47-.22-1.1-.28-1.9c-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83c.25-.9.83-1.48 1.73-1.73c.47-.13 1.33-.22 2.65-.28c1.3-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44c.9.25 1.48.83 1.73 1.73"/></svg>
                            <span>Tonton Video Musik (MV)</span>
                        </button>
                    </div>

                    <!-- Add to Playlist dropdown -->
                    <div class="bg-[#141415] p-4 rounded-[16px] border border-[#2d2f33]">
                        <h4 class="text-xs font-bold text-[#f0f2f5] uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-[#00b24b] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            <span>Simpan ke Playlist:</span>
                        </h4>
                        <select id="modalPlaylistSelect" class="w-full bg-[#2e3035] text-[#f0f2f5] text-[13px] font-semibold px-3 py-2 rounded-[8px] border border-[#2d2f33] mb-2 focus:outline-none focus:border-[#0075ff]">
                            <option value="">Pilih Playlist...</option>
                        </select>
                        <button onclick="addCurrentSongToPlaylist()" class="w-full bg-[#0075ff] hover:bg-[#0062d6] text-white text-[13px] font-semibold py-2 px-3 rounded-[10px] transition-colors">
                            + Masukkan ke Playlist
                        </button>
                    </div>
                </div>

                <!-- Kolom Kanan: Info Metadata, Analisis Makna & Ulasan Real Database -->
                <div class="modal-card-col-right space-y-6">
                    <!-- Metadata Info Badges -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div class="bg-[#141415] p-3.5 rounded-[12px] border border-[#2d2f33]">
                            <span class="text-[#9aa1b1] block mb-0.5 text-[11px] font-semibold flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-[#9aa1b1] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>Tahun Rilis</span>
                            </span>
                            <strong id="modalYear" class="text-[#f0f2f5] text-sm font-extrabold">1975</strong>
                        </div>
                        <div class="bg-[#141415] p-3.5 rounded-[12px] border border-[#2d2f33]">
                            <span class="text-[#9aa1b1] block mb-0.5 text-[11px] font-semibold flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-[#00b24b] flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
                                <span>Genre</span>
                            </span>
                            <strong id="modalGenre" class="text-[#00b24b] text-sm font-extrabold">Rock</strong>
                        </div>
                        <div class="bg-[#141415] p-3.5 rounded-[12px] border border-[#2d2f33]">
                            <span class="text-[#9aa1b1] block mb-0.5 text-[11px] font-semibold flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-[#9aa1b1] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Durasi</span>
                            </span>
                            <strong id="modalDuration" class="text-[#f0f2f5] text-sm font-extrabold">5:55</strong>
                        </div>
                        <div class="bg-[#141415] p-3.5 rounded-[12px] border border-[#2d2f33]">
                            <span class="text-[#9aa1b1] block mb-0.5 text-[11px] font-semibold flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-yellow-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                <span>Rating</span>
                            </span>
                            <strong id="modalRating" class="text-yellow-400 text-sm font-extrabold">★ 4.8</strong>
                        </div>
                    </div>

                    <!-- Analisis & Deskripsi Multimedia -->
                    <div class="bg-[#141415] p-5 rounded-[16px] border border-[#2d2f33]">
                        <div class="flex items-center gap-2 mb-2.5">
                            <span class="w-6 h-6 rounded-[6px] bg-[#0075ff] text-white flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            </span>
                            <h4 class="text-xs font-extrabold text-[#f0f2f5] uppercase tracking-wider">Latar Belakang & Analisis Makna</h4>
                        </div>
                        <div id="modalDescriptionText" class="text-[#9aa1b1] text-[14px] leading-relaxed space-y-2 whitespace-pre-line"></div>
                    </div>

                    <!-- Review & Rating Input -->
                    <div class="border-t border-[#2d2f33] pt-6">
                        <h4 class="text-sm font-extrabold text-[#f0f2f5] mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-yellow-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <span>Berikan Ulasan & Rating Anda:</span>
                        </h4>
                        <div class="flex items-center gap-1.5 mb-3" id="inputStarContainer" onmouseleave="resetSongFormRatingPreview()">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button" 
                                    onclick="setSongFormRating({{ $i }})" 
                                    onmouseenter="previewSongFormRating({{ $i }})" 
                                    class="star-btn text-2xl text-[#53575f] transition-all duration-150 transform hover:scale-125 focus:outline-none cursor-pointer select-none" 
                                    data-star="{{ $i }}"
                                    title="Rating {{ $i }} Bintang">★</button>
                            @endfor
                            <span id="starRatingLabel" class="text-xs text-[#9aa1b1] ml-2 font-semibold select-none">Beri rating 1 - 5 bintang</span>
                        </div>
                        <textarea id="inputReviewText" rows="3" placeholder="Tulis pendapat atau apresiasi Anda tentang komposisi audio, instrumen, dan makna lagu ini..." class="w-full bg-[#141415] text-[#f0f2f5] text-[14px] font-semibold p-3 rounded-[8px] border border-[#2d2f33] focus:outline-none focus:border-[#0075ff] resize-none transition-all placeholder:text-[#53575f]"></textarea>
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mt-2.5">
                            <span class="text-[11px] text-[#9aa1b1] italic">*Ulasan Anda langsung tersimpan permanen di database.</span>
                            <button id="btnSubmitSongReview" onclick="submitSongReview()" class="px-3.5 py-2 bg-[#00b24b] hover:bg-[#00c853] text-white font-semibold rounded-[10px] text-[13px] transition-colors flex items-center gap-1.5">
                                <span>Kirim Ulasan</span>
                            </button>
                        </div>
                    </div>

                    <!-- Daftar Ulasan Komunitas -->
                    <div class="border-t border-[#2d2f33] pt-6">
                        <div class="flex items-center justify-between mb-3.5">
                            <h4 class="text-sm font-extrabold text-[#f0f2f5] flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#0075ff] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                <span>Ulasan Komunitas (<span id="modalReviewsCount">0</span>)</span>
                            </h4>
                            <span class="text-[11px] text-[#f0f2f5] font-semibold flex items-center gap-1.5 bg-[#141415] border border-[#2d2f33] px-2.5 py-0.5 rounded-full">
                                <span class="w-2 h-2 rounded-full bg-[#00b24b]"></span>
                                Tersimpan di Database
                            </span>
                        </div>
                        <div id="modalSongReviewsList" class="space-y-2.5 max-h-56 overflow-y-auto pr-1">
                            <!-- Diisi dinamis dari database via JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== MODAL: BUAT PLAYLIST ==================== -->
    <div id="createPlaylistModal" class="modal">
        <div class="ex-modal-card bg-[#2e3035] rounded-[16px] max-w-md w-full mx-4 p-6 border border-[#2d2f33] relative">
            <div class="flex justify-between items-center mb-4 border-b border-[#2d2f33] pb-3">
                <h3 class="text-[20px] font-extrabold text-[#f0f2f5]">Buat Playlist Baru</h3>
                <button onclick="closeCreatePlaylistModal()" class="text-[#9aa1b1] hover:text-white text-2xl">&times;</button>
            </div>
            <form onsubmit="handleCreatePlaylistSubmit(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-[#9aa1b1] uppercase mb-1.5">Nama Playlist</label>
                    <input type="text" id="newPlaylistName" required placeholder="Contoh: Playlist Santai Malam" class="w-full bg-[#141415] text-[#f0f2f5] placeholder-[#53575f] text-[14px] font-semibold px-3 py-2 rounded-[8px] border border-[#2d2f33] focus:outline-none focus:border-[#0075ff]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#9aa1b1] uppercase mb-1.5">Deskripsi (Opsional)</label>
                    <textarea id="newPlaylistDesc" rows="3" placeholder="Ceritakan tentang tema playlist ini..." class="w-full bg-[#141415] text-[#f0f2f5] placeholder-[#53575f] text-[14px] font-semibold px-3 py-2 rounded-[8px] border border-[#2d2f33] focus:outline-none focus:border-[#0075ff] resize-none"></textarea>
                </div>
                <button type="submit" class="w-full py-2.5 px-3 bg-[#00b24b] hover:bg-[#00c853] text-white font-semibold rounded-[10px] text-[14px] transition-colors">
                    Simpan Playlist
                </button>
            </form>
        </div>
    </div>

    <!-- ==================== MODAL: EDIT PLAYLIST ==================== -->
    <div id="editPlaylistModal" class="modal">
        <div class="ex-modal-card bg-[#2e3035] rounded-[16px] max-w-md w-full mx-4 p-6 border border-[#2d2f33] relative">
            <div class="flex justify-between items-center mb-4 border-b border-[#2d2f33] pb-3">
                <h3 class="text-[20px] font-extrabold text-[#f0f2f5]">Edit Info Playlist</h3>
                <button onclick="closeEditPlaylistModal()" class="text-[#9aa1b1] hover:text-white text-2xl">&times;</button>
            </div>
            <form onsubmit="handleEditPlaylistSubmit(event)" class="space-y-4">
                <input type="hidden" id="editPlaylistId">
                <div>
                    <label class="block text-xs font-semibold text-[#9aa1b1] uppercase mb-1.5">Nama Playlist</label>
                    <input type="text" id="editPlaylistName" required placeholder="Nama playlist..." class="w-full bg-[#141415] text-[#f0f2f5] placeholder-[#53575f] text-[14px] font-semibold px-3 py-2 rounded-[8px] border border-[#2d2f33] focus:outline-none focus:border-[#0075ff]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#9aa1b1] uppercase mb-1.5">Deskripsi (Opsional)</label>
                    <textarea id="editPlaylistDesc" rows="3" placeholder="Ceritakan tentang tema playlist ini..." class="w-full bg-[#141415] text-[#f0f2f5] placeholder-[#53575f] text-[14px] font-semibold px-3 py-2 rounded-[8px] border border-[#2d2f33] focus:outline-none focus:border-[#0075ff] resize-none"></textarea>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="closeEditPlaylistModal()" class="flex-1 py-2 px-3 bg-[#141415] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] rounded-[12px] text-[14px] font-semibold">Batal</button>
                    <button type="submit" class="flex-1 py-2 px-3 bg-[#00b24b] hover:bg-[#00c853] text-white font-semibold rounded-[10px] text-[14px]">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== MODAL: CROP GAMBAR PLAYLIST (CROPPER.JS) ==================== -->
    <div id="cropPlaylistImageModal" class="modal" onclick="if(event.target === this) closeCropPlaylistModal()">
        <div class="ex-modal-card bg-[#2e3035] rounded-[16px] max-w-lg w-full mx-4 p-6 border border-[#2d2f33] relative">
            <div class="flex justify-between items-center mb-4 border-b border-[#2d2f33] pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-[10px] bg-[#0075ff] flex items-center justify-center text-white">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2v14a2 2 0 0 0 2 2h14"/><path d="M18 22V8a2 2 0 0 0-2-2H2"/></svg>
                    </span>
                    <div>
                        <h3 class="text-lg font-extrabold text-[#f0f2f5] leading-tight">Sesuaikan & Crop Sampul Playlist</h3>
                        <p class="text-[11px] text-[#9aa1b1]">Geser dan perbesar area gambar persegi (1:1) sesuai keinginan Anda</p>
                    </div>
                </div>
                <button type="button" onclick="closeCropPlaylistModal()" class="text-[#9aa1b1] hover:text-white text-2xl leading-none">&times;</button>
            </div>

            <!-- Cropper Canvas Area -->
            <div class="w-full h-72 sm:h-80 bg-[#141415] rounded-[12px] overflow-hidden border border-[#2d2f33] flex items-center justify-center relative">
                <img id="cropperTargetImage" src="" alt="Preview Crop" class="max-w-full max-h-full block">
            </div>

            <!-- Crop Controls Toolbar -->
            <div class="flex items-center justify-between gap-2 mt-4 bg-[#141415] px-3 py-2 rounded-[12px] border border-[#2d2f33]">
                <div class="flex items-center gap-1.5">
                    <button type="button" onclick="cropperZoom(0.1)" title="Perbesar (Zoom In)" class="p-2 rounded-[8px] bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] transition-colors">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/><line x1="11" x2="11" y1="8" y2="14"/><line x1="8" x2="14" y1="11" y2="11"/></svg>
                    </button>
                    <button type="button" onclick="cropperZoom(-0.1)" title="Perkecil (Zoom Out)" class="p-2 rounded-[8px] bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] transition-colors">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/><line x1="8" x2="14" y1="11" y2="11"/></svg>
                    </button>
                    <button type="button" onclick="cropperRotate(-90)" title="Putar Kiri 90°" class="p-2 rounded-[8px] bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] transition-colors">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    </button>
                    <button type="button" onclick="cropperRotate(90)" title="Putar Kanan 90°" class="p-2 rounded-[8px] bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] transition-colors">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                    </button>
                    <button type="button" onclick="cropperReset()" title="Reset Posisi" class="px-2.5 py-1.5 rounded-[8px] bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] text-xs font-semibold transition-colors">
                        Reset
                    </button>
                </div>
                <button type="button" onclick="triggerPlaylistImageUpload()" class="px-3 py-1.5 rounded-[8px] bg-[#0075ff] hover:bg-[#0062d6] text-white text-xs font-semibold transition-colors inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                    Pilih File Lain
                </button>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2.5 mt-5">
                <button type="button" onclick="closeCropPlaylistModal()" class="flex-1 py-2.5 px-3 bg-[#141415] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] rounded-[12px] text-[14px] font-semibold transition-colors">
                    Batal
                </button>
                <button type="button" id="saveCroppedPlaylistBtn" onclick="saveCroppedPlaylistImage()" class="flex-1 py-2.5 px-3 bg-[#00b24b] hover:bg-[#00c853] text-white font-semibold rounded-[10px] text-[14px] transition-colors">
                    Potong & Simpan Gambar
                </button>
            </div>
        </div>
    </div>

    <!-- ==================== MODAL: EDIT REVIEW ==================== -->
    <div id="editReviewModal" class="modal">
        <div class="ex-modal-card bg-[#2e3035] rounded-[16px] max-w-md w-full mx-4 p-6 border border-[#2d2f33] relative">
            <div class="flex justify-between items-center mb-4 border-b border-[#2d2f33] pb-3">
                <h3 class="text-[20px] font-extrabold text-[#f0f2f5]">Edit Ulasan Anda</h3>
                <button onclick="closeEditReviewModal()" class="text-[#9aa1b1] hover:text-white text-2xl">&times;</button>
            </div>
            <form onsubmit="handleUpdateReviewSubmit(event)" class="space-y-4">
                <input type="hidden" id="editReviewId">
                <div>
                    <label class="block text-xs font-semibold text-[#9aa1b1] uppercase mb-2">Rating</label>
                    <div class="flex items-center gap-1.5" id="editStarContainer" onmouseleave="resetEditFormRatingPreview()">
                        @for($i = 1; $i <= 5; $i++)
                            <button type="button" 
                                onclick="setEditFormRating({{ $i }})" 
                                onmouseenter="previewEditFormRating({{ $i }})" 
                                class="edit-star-btn text-2xl text-[#53575f] transition-all duration-150 transform hover:scale-125 focus:outline-none cursor-pointer select-none" 
                                data-star="{{ $i }}"
                                title="Rating {{ $i }} Bintang">★</button>
                        @endfor
                        <span id="editStarRatingLabel" class="text-xs text-[#9aa1b1] ml-2 font-semibold select-none">5 Bintang</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#9aa1b1] uppercase mb-1.5">Teks Ulasan</label>
                    <textarea id="editReviewText" rows="4" required class="w-full bg-[#141415] text-[#f0f2f5] text-[14px] font-semibold p-3 rounded-[8px] border border-[#2d2f33] focus:outline-none focus:border-[#0075ff] resize-none"></textarea>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="closeEditReviewModal()" class="flex-1 py-2.5 px-3 bg-[#141415] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] rounded-[12px] text-[14px] font-semibold">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 px-3 bg-[#00b24b] hover:bg-[#00c853] text-white rounded-[10px] text-[14px] font-semibold">Perbarui</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.songDataCache = window.songDataCache || {};
        @if(isset($allMusics))
            @foreach($allMusics as $song)
                window.songDataCache[{{ $song->id }}] = {!! json_encode($song) !!};
            @endforeach
        @else
            @foreach($recommended as $song)
                window.songDataCache[{{ $song->id }}] = {!! json_encode($song) !!};
            @endforeach
            @foreach($trending as $song)
                window.songDataCache[{{ $song->id }}] = {!! json_encode($song) !!};
            @endforeach
        @endif
    </script>
@endsection
