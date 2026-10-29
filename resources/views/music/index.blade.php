@extends('layouts.app')

@section('content')

    <!-- ==================== 1. BERANDA SECTION ==================== -->
    <section id="home" class="section active transition-opacity duration-300">
        <!-- Rekomendasi Teratas -->
        <div class="mb-14">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-3xl font-extrabold text-white tracking-tight flex items-center gap-2">
                        <span>Direkomendasikan</span>
                        <span class="text-xs bg-emerald-500/20 text-emerald-400 font-semibold px-2.5 py-0.5 rounded-full border border-emerald-500/30">Top Rated</span>
                    </h2>
                    <p class="text-sm text-slate-400 mt-1">Lagu pilihan dengan apresiasi dan rating tertinggi.</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" id="forYouGrid">
                @foreach($recommended as $song)
                    <div class="bg-slate-800/80 rounded-2xl p-4 border border-slate-700/60 hover:border-emerald-500/50 transition-all duration-300 hover:-translate-y-1 group shadow-lg flex flex-col justify-between">
                        <div>
                            <div class="relative overflow-hidden rounded-xl aspect-square mb-4 bg-slate-700 cursor-pointer" onclick="openMusicModal({{ $song->id }})">
                                <img src="{{ asset($song->image ?? 'image/queen1.jpg') }}" alt="{{ $song->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <!-- Overlay Play, 3D & Live Lyrics Buttons -->
                                <div class="absolute inset-0 bg-slate-950/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                    <button onclick="event.stopPropagation(); playSong({{ $song->id }}, '{{ addslashes($song->title) }}', '{{ addslashes($song->artist) }}', '{{ asset($song->image ?? 'image/queen1.jpg') }}', '{{ $song->audio_url }}', '{{ $song->video_id }}')" class="w-11 h-11 bg-emerald-500 hover:bg-emerald-400 text-slate-950 rounded-full flex items-center justify-center font-bold shadow-xl transition-transform hover:scale-110" title="Putar Musik">
                                        <svg class="w-5 h-5 fill-current translate-x-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </button>
                                    <button onclick="event.stopPropagation(); openVinylWithSong('{{ addslashes($song->title) }}', '{{ addslashes($song->artist) }}', '{{ asset($song->image ?? 'image/queen1.jpg') }}', {{ $song->id }}, '{{ $song->audio_url }}', '{{ $song->video_id }}')" class="w-9 h-9 bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-emerald-500/40 rounded-full flex items-center justify-center shadow-xl transition-transform hover:scale-110" title="Putar di Piringan 3D">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M16 3.937A9 9 0 1 0 21 12"/><path d="M11 12a1 1 0 1 0 2 0a1 1 0 1 0-2 0m8-8a1 1 0 1 0 2 0a1 1 0 1 0-2 0"/><path d="m20 4l-3.5 10l-2.5 2"/></g></svg>
                                    </button>
                                    <button onclick="event.stopPropagation(); openLyricsWithSong('{{ addslashes($song->title) }}', '{{ addslashes($song->artist) }}', '{{ asset($song->image ?? 'image/queen1.jpg') }}', {{ $song->id }}, '{{ $song->audio_url }}', '{{ $song->video_id }}')" class="w-9 h-9 bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-emerald-500/40 rounded-full flex items-center justify-center shadow-xl transition-transform hover:scale-110" title="Putar dengan Lirik">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M470.8 365.6c-.9-53.7-1.6-107.4-2.4-161.1c-.9-63.6-1.7-127.3-2.6-190.9c-.1-9.9-6.6-15.2-15.9-13.1c-25.8 5.9-51.7 11.7-77.5 17.5C306.7 32.7 241 47.5 175.3 62.2c-9.1 2-12.2 6.1-12.1 15.5c.2 7.5.3 14.9.4 22.4c1.3 90.7 2.5 181.4 3.7 272.1v9.8c-2.5-1.9-4.4-3.5-6.4-5c-17-12.6-36.1-17.4-56.9-14c-51.5 8.5-78.6 64.8-53.4 110.5c15.3 27.7 48.4 43.4 78.8 37.3c38-7.6 62.5-38.6 61.7-77.8c-.4-20.2-.8-40.4-1.1-60.7l-3-224.1c-.1-4.6 0-9.2 0-14c85.6-19.2 171.1-38.4 256.8-57.6c1.1 81.6 2.2 162.6 3.3 244.4c-1.7-1.3-2.6-2.1-3.6-2.9c-17.3-13.9-37.1-19.3-58.9-16c-54.2 8.2-83.1 71.4-49.5 117.3c17.9 24.4 42.6 35.1 72.6 30.4c30.3-4.8 50.1-23 59.9-52c3.6-10.6 3.4-21.4 3.2-32.2M115.6 489.4c-28.6.1-51.7-23.7-51.6-52.8s23.6-52.6 53-52.2c27.9.4 51.4 22.4 51.2 53.2c-.2 29.6-23.7 51.8-52.6 51.8m327.7-438c0 .9-2.1 2.3-3.4 2.6c-12.5 3-25 5.8-37.5 8.6c-20 4.5-40.1 8.9-60.1 13.4c-41.4 9.3-82.8 18.5-124.3 27.8c-9.6 2.2-19.3 4.3-28.9 6.5c-.6.1-1.2.1-3 .3c0-8.8-.1-17.4.2-25.9c0-.9 2.2-2.2 3.5-2.5c14.7-3.4 29.5-6.7 44.3-10c19.8-4.4 39.5-8.8 59.3-13.3l113.7-25.5c10.6-2.4 21.2-4.9 31.9-7.3c1.1-.2 2.2-.2 4.4-.4c.1 8.9.2 17.3-.1 25.7m-47.4 377c-28.1 0-52-24.1-52-52.4c0-28.6 23.8-52.7 52.1-52.7c28.9 0 52.3 23.7 52.3 53.1c-.2 28.3-24 52-52.4 52"/><path fill="currentColor" d="M64 436.6c.1-29.1 23.6-52.6 53-52.2c27.9.4 51.4 22.4 51.2 53.2c-.2 29.6-23.7 51.8-52.6 51.8c-28.6.1-51.7-23.7-51.6-52.8m384.2-60.2c0-29.4-23.3-53.1-52.3-53.1c-28.3 0-52.1 24.1-52.1 52.7c0 28.2 24 52.4 52 52.4c28.5 0 52.3-23.7 52.4-52m-259-266c9.6-2.2 19.3-4.3 28.9-6.5c41.4-9.3 82.8-18.6 124.3-27.8c20-4.5 40.1-8.9 60.1-13.4c12.5-2.8 25-5.6 37.5-8.6c1.3-.3 3.4-1.7 3.4-2.6c.3-8.4.2-16.9.2-25.8c-2.2.2-3.3.1-4.4.4c-10.6 2.4-21.2 4.9-31.9 7.3L293.6 58.9c-19.8 4.4-39.5 8.8-59.3 13.3c-14.8 3.3-29.5 6.6-44.3 10c-1.4.3-3.5 1.6-3.5 2.5c-.3 8.6-.2 17.1-.2 25.9c1.7-.1 2.3-.1 2.9-.2" opacity=".7"/></svg>
                                    </button>
                                </div>
                                <span class="absolute top-2.5 right-2.5 bg-black/70 backdrop-blur-md px-2 py-0.5 rounded-md text-xs font-bold text-yellow-400 flex items-center gap-1 border border-white/10">
                                    ★ {{ $song->rating }}
                                </span>
                            </div>
                            <h3 class="font-bold text-white text-base truncate cursor-pointer hover:text-emerald-400" onclick="openMusicModal({{ $song->id }})">{{ $song->title }}</h3>
                            <p class="text-xs text-slate-400 truncate mt-0.5">{{ $song->artist }} • {{ $song->genre }}</p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-700/50 flex items-center justify-between text-xs text-slate-400">
                            <span>{{ $song->year }}</span>
                            <button onclick="openMusicModal({{ $song->id }})" class="text-emerald-400 hover:underline font-medium">Detail & Analisis →</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Trending Minggu Ini -->
        <div class="mb-14">
            <h2 class="text-3xl font-extrabold text-white tracking-tight mb-2">Trending Minggu Ini</h2>
            <p class="text-sm text-slate-400 mb-6">Paling sering dieksplorasi penikmat musik.</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4" id="trendingGrid">
                @foreach($trending as $song)
                    <div class="bg-slate-800/60 rounded-xl p-3 border border-slate-700/50 hover:border-emerald-500/40 transition-all hover:-translate-y-1 group flex flex-col justify-between">
                        <div>
                            <div class="relative rounded-lg aspect-square overflow-hidden mb-3 bg-slate-700 cursor-pointer" onclick="openMusicModal({{ $song->id }})">
                                <img src="{{ asset($song->image ?? 'image/queen1.jpg') }}" alt="{{ $song->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <!-- Overlay Play, 3D & Live Lyrics Buttons -->
                                <div class="absolute inset-0 bg-slate-950/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                    <button onclick="event.stopPropagation(); playSong({{ $song->id }}, '{{ addslashes($song->title) }}', '{{ addslashes($song->artist) }}', '{{ asset($song->image ?? 'image/queen1.jpg') }}', '{{ $song->audio_url }}', '{{ $song->video_id }}')" class="w-9 h-9 bg-emerald-500 hover:bg-emerald-400 text-slate-950 rounded-full flex items-center justify-center shadow-lg transition-transform hover:scale-110" title="Putar Musik">
                                        <svg class="w-4 h-4 fill-current translate-x-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </button>
                                    <button onclick="event.stopPropagation(); openVinylWithSong('{{ addslashes($song->title) }}', '{{ addslashes($song->artist) }}', '{{ asset($song->image ?? 'image/queen1.jpg') }}', {{ $song->id }}, '{{ $song->audio_url }}', '{{ $song->video_id }}')" class="w-8 h-8 bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-emerald-500/40 rounded-full flex items-center justify-center shadow-lg transition-transform hover:scale-110" title="Putar di Piringan 3D">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M16 3.937A9 9 0 1 0 21 12"/><path d="M11 12a1 1 0 1 0 2 0a1 1 0 1 0-2 0m8-8a1 1 0 1 0 2 0a1 1 0 1 0-2 0"/><path d="m20 4l-3.5 10l-2.5 2"/></g></svg>
                                    </button>
                                    <button onclick="event.stopPropagation(); openLyricsWithSong('{{ addslashes($song->title) }}', '{{ addslashes($song->artist) }}', '{{ asset($song->image ?? 'image/queen1.jpg') }}', {{ $song->id }}, '{{ $song->audio_url }}', '{{ $song->video_id }}')" class="w-8 h-8 bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-emerald-500/40 rounded-full flex items-center justify-center shadow-lg transition-transform hover:scale-110" title="Putar dengan Lirik">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M470.8 365.6c-.9-53.7-1.6-107.4-2.4-161.1c-.9-63.6-1.7-127.3-2.6-190.9c-.1-9.9-6.6-15.2-15.9-13.1c-25.8 5.9-51.7 11.7-77.5 17.5C306.7 32.7 241 47.5 175.3 62.2c-9.1 2-12.2 6.1-12.1 15.5c.2 7.5.3 14.9.4 22.4c1.3 90.7 2.5 181.4 3.7 272.1v9.8c-2.5-1.9-4.4-3.5-6.4-5c-17-12.6-36.1-17.4-56.9-14c-51.5 8.5-78.6 64.8-53.4 110.5c15.3 27.7 48.4 43.4 78.8 37.3c38-7.6 62.5-38.6 61.7-77.8c-.4-20.2-.8-40.4-1.1-60.7l-3-224.1c-.1-4.6 0-9.2 0-14c85.6-19.2 171.1-38.4 256.8-57.6c1.1 81.6 2.2 162.6 3.3 244.4c-1.7-1.3-2.6-2.1-3.6-2.9c-17.3-13.9-37.1-19.3-58.9-16c-54.2 8.2-83.1 71.4-49.5 117.3c17.9 24.4 42.6 35.1 72.6 30.4c30.3-4.8 50.1-23 59.9-52c3.6-10.6 3.4-21.4 3.2-32.2M115.6 489.4c-28.6.1-51.7-23.7-51.6-52.8s23.6-52.6 53-52.2c27.9.4 51.4 22.4 51.2 53.2c-.2 29.6-23.7 51.8-52.6 51.8m327.7-438c0 .9-2.1 2.3-3.4 2.6c-12.5 3-25 5.8-37.5 8.6c-20 4.5-40.1 8.9-60.1 13.4c-41.4 9.3-82.8 18.5-124.3 27.8c-9.6 2.2-19.3 4.3-28.9 6.5c-.6.1-1.2.1-3 .3c0-8.8-.1-17.4.2-25.9c0-.9 2.2-2.2 3.5-2.5c14.7-3.4 29.5-6.7 44.3-10c19.8-4.4 39.5-8.8 59.3-13.3l113.7-25.5c10.6-2.4 21.2-4.9 31.9-7.3c1.1-.2 2.2-.2 4.4-.4c.1 8.9.2 17.3-.1 25.7m-47.4 377c-28.1 0-52-24.1-52-52.4c0-28.6 23.8-52.7 52.1-52.7c28.9 0 52.3 23.7 52.3 53.1c-.2 28.3-24 52-52.4 52"/><path fill="currentColor" d="M64 436.6c.1-29.1 23.6-52.6 53-52.2c27.9.4 51.4 22.4 51.2 53.2c-.2 29.6-23.7 51.8-52.6 51.8c-28.6.1-51.7-23.7-51.6-52.8m384.2-60.2c0-29.4-23.3-53.1-52.3-53.1c-28.3 0-52.1 24.1-52.1 52.7c0 28.2 24 52.4 52 52.4c28.5 0 52.3-23.7 52.4-52m-259-266c9.6-2.2 19.3-4.3 28.9-6.5c41.4-9.3 82.8-18.6 124.3-27.8c20-4.5 40.1-8.9 60.1-13.4c12.5-2.8 25-5.6 37.5-8.6c1.3-.3 3.4-1.7 3.4-2.6c.3-8.4.2-16.9.2-25.8c-2.2.2-3.3.1-4.4.4c-10.6 2.4-21.2 4.9-31.9 7.3L293.6 58.9c-19.8 4.4-39.5 8.8-59.3 13.3c-14.8 3.3-29.5 6.6-44.3 10c-1.4.3-3.5 1.6-3.5 2.5c-.3 8.6-.2 17.1-.2 25.9c1.7-.1 2.3-.1 2.9-.2" opacity=".7"/></svg>
                                    </button>
                                </div>
                                <span class="absolute top-2 right-2 bg-black/70 backdrop-blur-md px-1.5 py-0.5 rounded-md text-[11px] font-bold text-yellow-400 flex items-center gap-0.5 border border-white/10">
                                    ★ {{ $song->rating }}
                                </span>
                            </div>
                            <h4 class="font-semibold text-sm text-white truncate cursor-pointer hover:text-emerald-400 transition-colors" onclick="openMusicModal({{ $song->id }})">{{ $song->title }}</h4>
                            <p class="text-xs text-slate-400 truncate mt-0.5">{{ $song->artist }} • {{ $song->genre }}</p>
                        </div>
                        <div class="mt-3 pt-2.5 border-t border-slate-700/50 flex items-center justify-between text-[11px] text-slate-400">
                            <span>{{ $song->year }}</span>
                            <button onclick="openMusicModal({{ $song->id }})" class="text-emerald-400 hover:underline font-medium">Detail & Analisis →</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Ulasan Terbaru -->
        <div>
            <h2 class="text-3xl font-extrabold text-white tracking-tight mb-6">Ulasan Komunitas</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="recentReviews">
                @forelse($recentReviews as $rev)
                    <div class="bg-slate-800/70 p-5 rounded-2xl border border-slate-700/60 flex flex-col justify-between shadow">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-bold text-white text-base">{{ $rev->music_title }}</h4>
                                <div class="text-yellow-400 text-sm">
                                    {{ str_repeat('★', $rev->rating) }}{{ str_repeat('☆', 5 - $rev->rating) }}
                                </div>
                            </div>
                            <p class="text-xs text-emerald-400 mb-3 font-medium">{{ $rev->artist }}</p>
                            <p class="text-slate-300 text-sm leading-relaxed italic">"{{ $rev->review }}"</p>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-4">{{ $rev->created_at ? $rev->created_at->diffForHumans() : 'Baru saja' }}</p>
                    </div>
                @empty
                    <p class="text-slate-500 text-sm">Belum ada ulasan.</p>
                @endforelse
            </div>
        </div>
    </section>

    <!-- ==================== 2. TELUSURI SECTION ==================== -->
    <section id="discover" class="section hidden transition-opacity duration-300">
        <!-- Detail Artis jika sedang dipilih -->
        <div id="artistInfoContainer" class="hidden mb-8">
            <button onclick="handleBackToArtists()" class="text-slate-300 hover:text-white mb-4 inline-flex items-center text-sm font-semibold">
                <svg class="h-4 w-4 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                Kembali ke Daftar Artis
            </button>
            <div class="bg-gradient-to-r from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700 flex flex-col md:flex-row items-center gap-8 shadow-xl">
                <img id="artistDisplayImg" src="" class="w-36 h-36 rounded-2xl object-cover shadow-2xl border-2 border-emerald-500/40">
                <div class="text-center md:text-left flex-1">
                    <h2 id="artistNameDisplay" class="text-3xl md:text-4xl font-extrabold text-white mb-2">Nama Artis</h2>
                    <p class="text-emerald-400 font-semibold mb-3 text-sm">
                        <span id="artistListeners">0</span> pendengar bulanan
                    </p>
                    <p id="artistDescription" class="text-slate-300 text-sm leading-relaxed max-w-3xl">Deskripsi...</p>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h2 id="discoverTitle" class="text-3xl font-extrabold text-white tracking-tight">Katalog Musik & Artis</h2>
                <p class="text-sm text-slate-400 mt-1">Menampilkan seluruh koleksi musik (<span id="discoverTotalCount" class="text-emerald-400 font-bold">200 Lagu</span>) lengkap dari berbagai genre dan era.</p>
            </div>

            <!-- Sort By -->
            <div class="flex items-center gap-3">
                <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Urutkan:</label>
                <select id="discoverSortSelect" onchange="fetchMusics()" class="bg-slate-800 text-white text-sm border border-slate-700 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer">
                    <option value="rating">Rating Tertinggi</option>
                    <option value="az">Abjad (A - Z)</option>
                    <option value="za">Abjad (Z - A)</option>
                    <option value="year">Tahun Rilis Terbaru</option>
                </select>
            </div>
        </div>

        <!-- Filter Genre Buttons -->
        <div class="mb-8">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Pilih Genre</h3>
            <div class="flex flex-wrap gap-2.5" id="genreFilterButtons">
                <button onclick="setGenreFilter('All', this)" class="genre-btn px-4 py-1.5 rounded-full text-xs font-semibold transition-all bg-emerald-500 text-slate-950">Semua (All)</button>
                @foreach($genres as $genre)
                    <button onclick="setGenreFilter('{{ $genre }}', this)" class="genre-btn px-4 py-1.5 rounded-full text-xs font-semibold transition-all bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white border border-slate-700/60">{{ $genre }}</button>
                @endforeach
            </div>
        </div>

        <!-- Filter Decade Era -->
        <div class="mb-8">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Era / Dekade Rilis</h3>
            <div class="flex flex-wrap gap-2" id="decadeButtons">
                <button onclick="setDecadeFilter('all', this)" class="decade-btn px-3.5 py-1.5 rounded-lg text-xs font-medium transition-all bg-emerald-600/30 text-emerald-400 border border-emerald-500/40">Semua Era</button>
                <button onclick="setDecadeFilter('1960', this)" class="decade-btn px-3.5 py-1.5 rounded-lg text-xs font-medium transition-all bg-slate-800 text-slate-400 hover:text-white border border-slate-700">1960s</button>
                <button onclick="setDecadeFilter('1970', this)" class="decade-btn px-3.5 py-1.5 rounded-lg text-xs font-medium transition-all bg-slate-800 text-slate-400 hover:text-white border border-slate-700">1970s</button>
                <button onclick="setDecadeFilter('1980', this)" class="decade-btn px-3.5 py-1.5 rounded-lg text-xs font-medium transition-all bg-slate-800 text-slate-400 hover:text-white border border-slate-700">1980s</button>
                <button onclick="setDecadeFilter('1990', this)" class="decade-btn px-3.5 py-1.5 rounded-lg text-xs font-medium transition-all bg-slate-800 text-slate-400 hover:text-white border border-slate-700">1990s</button>
                <button onclick="setDecadeFilter('2000', this)" class="decade-btn px-3.5 py-1.5 rounded-lg text-xs font-medium transition-all bg-slate-800 text-slate-400 hover:text-white border border-slate-700">2000s</button>
                <button onclick="setDecadeFilter('2010', this)" class="decade-btn px-3.5 py-1.5 rounded-lg text-xs font-medium transition-all bg-slate-800 text-slate-400 hover:text-white border border-slate-700">2010s</button>
                <button onclick="setDecadeFilter('2020', this)" class="decade-btn px-3.5 py-1.5 rounded-lg text-xs font-medium transition-all bg-slate-800 text-slate-400 hover:text-white border border-slate-700">2020s & Baru</button>
            </div>
        </div>

        <!-- Dynamic Musics Grid Container -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="discoverGrid">
            <!-- Diisi lewat JavaScript fetch API -->
        </div>

        <!-- Navigasi Halaman (Pagination) -->
        <div id="discoverPagination" class="mt-10 border-t border-slate-800/80 pt-6">
            <!-- Diisi dinamis lewat JavaScript -->
        </div>
    </section>

    <!-- ==================== 3. PLAYLISTS SECTION ==================== -->
    <section id="playlists" class="section hidden transition-opacity duration-300">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-extrabold text-white tracking-tight">Koleksi Playlist</h2>
                <p class="text-sm text-slate-400 mt-1">Atur daftar putar multimedia sesuai suasana hatimu.</p>
            </div>
            <button onclick="openCreatePlaylistModal()" class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold rounded-xl text-sm transition-transform hover:scale-105 shadow-lg flex items-center gap-2">
                <span>+</span> Buat Playlist Baru
            </button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" id="playlistsGrid">
            <!-- Diisi lewat JavaScript -->
        </div>
    </section>

    <!-- ==================== 4. PLAYLIST DETAIL VIEW ==================== -->
    <section id="playlist-view" class="section hidden transition-opacity duration-300">
        <button onclick="showSection('playlists', document.querySelector('.nav-btn:nth-child(3)'))" class="mb-6 flex items-center text-sm font-semibold text-slate-400 hover:text-white transition-colors">
            <svg class="h-4 w-4 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Kembali ke Daftar Playlist
        </button>

        <div class="flex flex-col md:flex-row items-center md:items-end gap-8 mb-8 bg-gradient-to-b from-slate-800 to-slate-900/90 p-8 rounded-2xl border border-slate-700/60 shadow-2xl">
            <div class="relative group w-48 h-48 flex-shrink-0">
                <div id="playlistViewImage" class="w-full h-full bg-slate-700 rounded-2xl shadow-2xl flex items-center justify-center text-5xl overflow-hidden border-2 border-emerald-500/30">
                    🎵
                </div>
                <button type="button" onclick="triggerPlaylistImageUpload()" title="Ganti & Crop Gambar Sampul Playlist" class="absolute bottom-2.5 left-2.5 z-10 w-10 h-10 rounded-xl bg-slate-900/90 hover:bg-emerald-500 text-emerald-400 hover:text-slate-950 border border-emerald-500/40 hover:border-emerald-400 shadow-lg backdrop-blur-md flex items-center justify-center transition-all duration-200 hover:scale-110 active:scale-95">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                        <circle cx="12" cy="13" r="3"/>
                    </svg>
                </button>
                <input type="file" id="playlistImageInput" accept="image/*" class="hidden" onchange="handlePlaylistImageSelect(event)">
            </div>
            <div class="w-full text-center md:text-left">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-400 mb-1 inline-block">Playlist Komunitas</span>
                <h1 id="playlistViewTitle" class="text-3xl md:text-5xl font-black text-white mb-3">Nama Playlist</h1>
                <p id="playlistViewDescription" class="text-slate-300 text-sm mb-4">Deskripsi playlist...</p>
                <div class="flex items-center justify-center md:justify-start text-xs text-slate-400 gap-3">
                    <span class="bg-slate-800 px-3 py-1 rounded-full border border-slate-700 font-semibold text-slate-300"><span id="playlistViewCount">0</span> Lagu</span>
                    <button onclick="openEditPlaylistModal()" class="text-emerald-400 hover:text-emerald-300 underline font-medium">Edit Info Playlist</button>
                    <span class="text-slate-600">•</span>
                    <button id="deletePlaylistBtn" class="text-red-400 hover:text-red-300 underline font-medium">Hapus Playlist</button>
                </div>
            </div>
        </div>

        <div class="bg-slate-800/40 rounded-2xl p-6 border border-slate-800">
            <h3 class="text-lg font-bold text-white mb-4">Daftar Lagu dalam Playlist</h3>
            <div id="playlistViewSongs" class="space-y-2"></div>
        </div>
    </section>

    <!-- ==================== 5. ULASAN SAYA SECTION ==================== -->
    <section id="reviews" class="section hidden transition-opacity duration-300">
        <div class="mb-8">
            <h2 class="text-3xl font-extrabold text-white tracking-tight">Semua Ulasan & Rating</h2>
            <p class="text-sm text-slate-400 mt-1">Daftar ulasan yang Anda berikan terhadap lagu dan komposisi audio.</p>
        </div>
        <div class="space-y-4 max-w-4xl" id="myReviewsList">
            <!-- Diisi lewat JavaScript -->
        </div>
    </section>

    <!-- ==================== 6. TENTANG KAMI SECTION ==================== -->
    <section id="about" class="section hidden transition-opacity duration-300">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-extrabold text-white mb-4 tracking-tight">Tentang Minima</h2>
            <p class="text-slate-400 text-lg max-w-2xl mx-auto">
                Platform multimedia interaktif berbasis Web yang menggabungkan teks, audio digital, video klip, dan objek 3D piringan hitam (*vinyl*).
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
            <div class="bg-slate-800/80 rounded-2xl p-8 border border-slate-700 shadow-xl">
                <h3 class="text-2xl font-bold text-emerald-400 mb-3 flex items-center gap-2.5">
                    <svg class="w-6 h-6 text-emerald-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/>
                        <path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/>
                    </svg>
                    <span>Visi Proyek</span>
                </h3>
                <p class="text-slate-300 leading-relaxed text-sm text-justify">
                    Mengembangkan aplikasi multimedia interaktif berbasis Web yang inklusif dan edukatif, di mana musik lintas era dapat diapresiasi secara visual dan auditori melalui perpaduan audio resolusi tinggi, video klip, serta teknologi 3D WebGL modern.
                </p>
            </div>
            <div class="bg-slate-800/80 rounded-2xl p-8 border border-slate-700 shadow-xl">
                <h3 class="text-2xl font-bold text-emerald-400 mb-3 flex items-center gap-2.5">
                    <svg class="w-6 h-6 text-emerald-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <circle cx="12" cy="12" r="6"/>
                        <circle cx="12" cy="12" r="2"/>
                    </svg>
                    <span>Misi Proyek</span>
                </h3>
                <ul class="space-y-2.5 text-slate-300 text-sm">
                    <li class="flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                        <span>Menyajikan kurasi 200+ karya musik lintas 8 era genre lengkap dengan analisis latar belakang.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                        <span>Mengintegrasikan elemen multimedia esensial: Teks, Audio Player, Video Musik, dan Objek 3D Vinyl.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                        <span>Menerapkan arsitektur Model-View-Controller (MVC) menggunakan Laravel 11 dan database relasional.</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Profil Tim Pengembang (Kelompok 2 Kelas 17.5A.27) -->
        <div class="bg-gradient-to-r from-slate-800 to-slate-900 rounded-3xl p-10 border border-slate-700 text-center relative overflow-hidden shadow-2xl">
            <div class="relative z-10">
                <span class="text-xs font-bold text-emerald-400 uppercase tracking-widest bg-emerald-950 px-3 py-1 rounded-full border border-emerald-800">Project Akhir Sistem Multimedia</span>
                <h3 class="text-3xl font-extrabold text-white mt-3 mb-2">Tim Pengembang (Kelompok 2)</h3>
                <p class="text-slate-400 text-sm mb-10">Kelas 17.5A.27 • Universitas Bina Sarana Informatika</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 text-left">
                    <!-- Paul Siagian -->
                    <div class="bg-slate-900/60 p-5 rounded-2xl border border-slate-700/60 hover:border-emerald-500 transition-all hover:-translate-y-1">
                        <div class="w-12 h-12 bg-emerald-900/40 text-emerald-400 rounded-full flex items-center justify-center font-bold mb-3 text-lg">
                            PS
                        </div>
                        <h4 class="font-bold text-white text-sm">Paul Siagian</h4>
                        <p class="text-xs text-slate-400">NIM: 17240668</p>
                        <a href="https://instagram.com/ull_1141" target="_blank" class="text-[11px] text-emerald-400 hover:underline mt-2 inline-block">@ull_1141</a>
                    </div>

                    <!-- Arla Raesa Isalim -->
                    <div class="bg-slate-900/60 p-5 rounded-2xl border border-slate-700/60 hover:border-emerald-500 transition-all hover:-translate-y-1">
                        <div class="w-12 h-12 bg-pink-900/40 text-pink-400 rounded-full flex items-center justify-center font-bold mb-3 text-lg">
                            AR
                        </div>
                        <h4 class="font-bold text-white text-sm">Arla Raesa Isalim</h4>
                        <p class="text-xs text-slate-400">NIM: 17240410</p>
                        <a href="https://instagram.com/arlarsa._" target="_blank" class="text-[11px] text-pink-400 hover:underline mt-2 inline-block">@arlarsa._</a>
                    </div>

                    <!-- Muhammad Surya Triabadi -->
                    <div class="bg-slate-900/60 p-5 rounded-2xl border border-slate-700/60 hover:border-emerald-500 transition-all hover:-translate-y-1">
                        <div class="w-12 h-12 bg-blue-900/40 text-blue-400 rounded-full flex items-center justify-center font-bold mb-3 text-lg">
                            MS
                        </div>
                        <h4 class="font-bold text-white text-sm">M. Surya Triabadi</h4>
                        <p class="text-xs text-slate-400">NIM: 17240151</p>
                        <a href="https://instagram.com/surrddawg" target="_blank" class="text-[11px] text-blue-400 hover:underline mt-2 inline-block">@surrddawg</a>
                    </div>

                    <!-- Al Muzamil Sahri Ramadhan -->
                    <div class="bg-slate-900/60 p-5 rounded-2xl border border-slate-700/60 hover:border-emerald-500 transition-all hover:-translate-y-1">
                        <div class="w-12 h-12 bg-purple-900/40 text-purple-400 rounded-full flex items-center justify-center font-bold mb-3 text-lg">
                            AM
                        </div>
                        <h4 class="font-bold text-white text-sm">Al Muzamil S. R.</h4>
                        <p class="text-xs text-slate-400">NIM: 17240641</p>
                        <a href="https://instagram.com/almzmlsahrii_" target="_blank" class="text-[11px] text-purple-400 hover:underline mt-2 inline-block">@almzmlsahrii_</a>
                    </div>

                    <!-- Elrado Keni Sierra -->
                    <div class="bg-slate-900/60 p-5 rounded-2xl border border-slate-700/60 hover:border-emerald-500 transition-all hover:-translate-y-1">
                        <div class="w-12 h-12 bg-amber-900/40 text-amber-400 rounded-full flex items-center justify-center font-bold mb-3 text-lg">
                            EK
                        </div>
                        <h4 class="font-bold text-white text-sm">Elrado Keni Sierra</h4>
                        <p class="text-xs text-slate-400">NIM: 17240475</p>
                        <a href="https://instagram.com/elradokenii" target="_blank" class="text-[11px] text-amber-400 hover:underline mt-2 inline-block">@elradokenii</a>
                    </div>
                </div>

                <p class="text-slate-500 text-xs mt-10">© 2026 Minima Multimedia Project • Dilengkapi Elemen Teks, Audio, Video, dan Objek 3D</p>
            </div>
        </div>
    </section>

    <!-- ==================== MODAL: DETAIL MUSIK LENGKAP ==================== -->
    <div id="musicModal" class="modal" onclick="if(event.target === this) closeMusicModal()">
        <div class="modal-card bg-slate-900/95 rounded-3xl max-w-4xl w-full mx-4 max-h-[92vh] overflow-y-auto border border-slate-700/80 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85),0_0_50px_rgba(16,185,129,0.12)] relative p-6 sm:p-8 backdrop-blur-2xl">
            <!-- Modal Header -->
            <div class="flex justify-between items-start border-b border-slate-800 pb-5 mb-6">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 uppercase tracking-wider">Detail Komposisi & Analisis</span>
                    </div>
                    <h2 id="modalTitle" class="text-2xl sm:text-3xl font-black text-white tracking-tight">Judul Lagu</h2>
                    <p id="modalSubtitle" class="text-sm text-slate-300 font-semibold mt-0.5">Nama Artis • Album</p>
                </div>
                <button onclick="closeMusicModal()" class="w-9 h-9 rounded-full bg-slate-800/80 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center border border-slate-700 hover:border-slate-500 transition-all hover:rotate-90 duration-300 text-sm shadow-md" title="Tutup Modal (Esc)">✕</button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-[300px_1fr] gap-7">
                <!-- Kolom Kiri: Cover Art, Tombol Aksi & Simpan Playlist -->
                <div class="modal-card-col-left">
                    <div class="relative group mb-5">
                        <img id="modalCoverImg" src="" class="w-full aspect-square rounded-2xl object-cover border border-slate-700/80 shadow-2xl relative z-10 transition-transform duration-500 group-hover:scale-[1.02]">
                        <div id="modalCoverGlow" class="absolute inset-0 rounded-2xl bg-emerald-500/25 blur-2xl -z-0 scale-95 opacity-70 transition-opacity duration-500"></div>
                    </div>
                    
                    <!-- Quick Action Buttons: Audio, 3D Vinyl, Lyrics -->
                    <div class="space-y-2.5 mb-6">
                        <button id="modalPlayAudioBtn" class="w-full bg-gradient-to-r from-emerald-500 to-emerald-400 hover:from-emerald-400 hover:to-emerald-300 text-slate-950 font-black py-3 rounded-xl text-sm transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2.5 shadow-lg shadow-emerald-500/20">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            <span>Putar Musik Asli (Full Durasi)</span>
                        </button>
                        <button id="modalOpen3dBtn" class="w-full bg-slate-800/90 hover:bg-slate-700 text-emerald-400 hover:text-white font-bold py-2.5 rounded-xl text-sm transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2.5 border border-emerald-500/30 hover:border-emerald-400 shadow-md">
                            <svg class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M16 3.937A9 9 0 1 0 21 12"/><path d="M11 12a1 1 0 1 0 2 0a1 1 0 1 0-2 0m8-8a1 1 0 1 0 2 0a1 1 0 1 0-2 0"/><path d="m20 4l-3.5 10l-2.5 2"/></g></svg>
                            <span>Putar di Piringan 3D Vinyl</span>
                        </button>
                        <button id="modalOpenLyricsBtn" class="w-full bg-slate-800/90 hover:bg-slate-700 text-emerald-400 hover:text-white font-bold py-2.5 rounded-xl text-sm transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2.5 border border-emerald-500/30 hover:border-emerald-400 shadow-md">
                            <svg class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M470.8 365.6c-.9-53.7-1.6-107.4-2.4-161.1c-.9-63.6-1.7-127.3-2.6-190.9c-.1-9.9-6.6-15.2-15.9-13.1c-25.8 5.9-51.7 11.7-77.5 17.5C306.7 32.7 241 47.5 175.3 62.2c-9.1 2-12.2 6.1-12.1 15.5c.2 7.5.3 14.9.4 22.4c1.3 90.7 2.5 181.4 3.7 272.1v9.8c-2.5-1.9-4.4-3.5-6.4-5c-17-12.6-36.1-17.4-56.9-14c-51.5 8.5-78.6 64.8-53.4 110.5c15.3 27.7 48.4 43.4 78.8 37.3c38-7.6 62.5-38.6 61.7-77.8c-.4-20.2-.8-40.4-1.1-60.7l-3-224.1c-.1-4.6 0-9.2 0-14c85.6-19.2 171.1-38.4 256.8-57.6c1.1 81.6 2.2 162.6 3.3 244.4c-1.7-1.3-2.6-2.1-3.6-2.9c-17.3-13.9-37.1-19.3-58.9-16c-54.2 8.2-83.1 71.4-49.5 117.3c17.9 24.4 42.6 35.1 72.6 30.4c30.3-4.8 50.1-23 59.9-52c3.6-10.6 3.4-21.4 3.2-32.2M115.6 489.4c-28.6.1-51.7-23.7-51.6-52.8s23.6-52.6 53-52.2c27.9.4 51.4 22.4 51.2 53.2c-.2 29.6-23.7 51.8-52.6 51.8m327.7-438c0 .9-2.1 2.3-3.4 2.6c-12.5 3-25 5.8-37.5 8.6c-20 4.5-40.1 8.9-60.1 13.4c-41.4 9.3-82.8 18.5-124.3 27.8c-9.6 2.2-19.3 4.3-28.9 6.5c-.6.1-1.2.1-3 .3c0-8.8-.1-17.4.2-25.9c0-.9 2.2-2.2 3.5-2.5c14.7-3.4 29.5-6.7 44.3-10c19.8-4.4 39.5-8.8 59.3-13.3l113.7-25.5c10.6-2.4 21.2-4.9 31.9-7.3c1.1-.2 2.2-.2 4.4-.4c.1 8.9.2 17.3-.1 25.7m-47.4 377c-28.1 0-52-24.1-52-52.4c0-28.6 23.8-52.7 52.1-52.7c28.9 0 52.3 23.7 52.3 53.1c-.2 28.3-24 52-52.4 52"/><path fill="currentColor" d="M64 436.6c.1-29.1 23.6-52.6 53-52.2c27.9.4 51.4 22.4 51.2 53.2c-.2 29.6-23.7 51.8-52.6 51.8c-28.6.1-51.7-23.7-51.6-52.8m384.2-60.2c0-29.4-23.3-53.1-52.3-53.1c-28.3 0-52.1 24.1-52.1 52.7c0 28.2 24 52.4 52 52.4c28.5 0 52.3-23.7 52.4-52m-259-266c9.6-2.2 19.3-4.3 28.9-6.5c41.4-9.3 82.8-18.6 124.3-27.8c20-4.5 40.1-8.9 60.1-13.4c12.5-2.8 25-5.6 37.5-8.6c1.3-.3 3.4-1.7 3.4-2.6c.3-8.4.2-16.9.2-25.8c-2.2.2-3.3.1-4.4.4c-10.6 2.4-21.2 4.9-31.9 7.3L293.6 58.9c-19.8 4.4-39.5 8.8-59.3 13.3c-14.8 3.3-29.5 6.6-44.3 10c-1.4.3-3.5 1.6-3.5 2.5c-.3 8.6-.2 17.1-.2 25.9c1.7-.1 2.3-.1 2.9-.2" opacity=".7"/></svg>
                            <span>Putar dengan Lirik</span>
                        </button>
                    </div>

                    <!-- Add to Playlist dropdown -->
                    <div class="bg-slate-800/60 p-4 rounded-2xl border border-slate-700/60 backdrop-blur-sm">
                        <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <span>📑</span> Simpan ke Playlist:
                        </h4>
                        <select id="modalPlaylistSelect" class="w-full bg-slate-900 text-white text-xs px-3 py-2.5 rounded-xl border border-slate-700 mb-2 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="">Pilih Playlist...</option>
                        </select>
                        <button onclick="addCurrentSongToPlaylist()" class="w-full bg-slate-800 hover:bg-emerald-600 text-slate-300 hover:text-white text-xs font-semibold py-2 rounded-xl border border-slate-700/80 transition-all hover:scale-[1.02]">
                            + Masukkan ke Playlist
                        </button>
                    </div>
                </div>

                <!-- Kolom Kanan: Info Metadata, Analisis Makna & Ulasan Real Database -->
                <div class="modal-card-col-right space-y-6">
                    <!-- Metadata Info Badges -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div class="bg-slate-800/70 p-3.5 rounded-2xl border border-slate-700/60 hover:border-emerald-500/50 hover:bg-slate-800 hover:shadow-lg hover:shadow-emerald-500/10 hover:-translate-y-0.5 transition-all duration-300 backdrop-blur-md">
                            <span class="text-slate-400 block mb-0.5 text-[11px] font-medium flex items-center gap-1">
                                <span>📅</span> Tahun Rilis
                            </span>
                            <strong id="modalYear" class="text-white text-sm font-extrabold">1975</strong>
                        </div>
                        <div class="bg-slate-800/70 p-3.5 rounded-2xl border border-slate-700/60 hover:border-emerald-500/50 hover:bg-slate-800 hover:shadow-lg hover:shadow-emerald-500/10 hover:-translate-y-0.5 transition-all duration-300 backdrop-blur-md">
                            <span class="text-slate-400 block mb-0.5 text-[11px] font-medium flex items-center gap-1">
                                <span>🎸</span> Genre
                            </span>
                            <strong id="modalGenre" class="text-emerald-400 text-sm font-extrabold">Rock</strong>
                        </div>
                        <div class="bg-slate-800/70 p-3.5 rounded-2xl border border-slate-700/60 hover:border-emerald-500/50 hover:bg-slate-800 hover:shadow-lg hover:shadow-emerald-500/10 hover:-translate-y-0.5 transition-all duration-300 backdrop-blur-md">
                            <span class="text-slate-400 block mb-0.5 text-[11px] font-medium flex items-center gap-1">
                                <span>⏱️</span> Durasi
                            </span>
                            <strong id="modalDuration" class="text-white text-sm font-extrabold">5:55</strong>
                        </div>
                        <div class="bg-slate-800/70 p-3.5 rounded-2xl border border-slate-700/60 hover:border-emerald-500/50 hover:bg-slate-800 hover:shadow-lg hover:shadow-emerald-500/10 hover:-translate-y-0.5 transition-all duration-300 backdrop-blur-md">
                            <span class="text-slate-400 block mb-0.5 text-[11px] font-medium flex items-center gap-1">
                                <span>⭐</span> Rating
                            </span>
                            <strong id="modalRating" class="text-yellow-400 text-sm font-extrabold">★ 4.8</strong>
                        </div>
                    </div>

                    <!-- Analisis & Deskripsi Multimedia -->
                    <div class="bg-gradient-to-br from-slate-800/80 via-slate-800/40 to-slate-900/60 p-5 rounded-2xl border border-slate-700/70 shadow-inner">
                        <div class="flex items-center gap-2 mb-2.5">
                            <span class="w-6 h-6 rounded-lg bg-emerald-500/15 text-emerald-400 border border-emerald-500/25 flex items-center justify-center text-xs">📖</span>
                            <h4 class="text-xs font-bold text-slate-200 uppercase tracking-wider">Latar Belakang & Analisis Makna</h4>
                        </div>
                        <div id="modalDescriptionText" class="text-slate-300 text-sm leading-relaxed space-y-2 whitespace-pre-line"></div>
                    </div>

                    <!-- Review & Rating Input -->
                    <div class="border-t border-slate-800 pt-6">
                        <h4 class="text-sm font-bold text-white mb-3 flex items-center gap-2">
                            <span>⭐</span> Berikan Ulasan & Rating Anda:
                        </h4>
                        <div class="flex items-center gap-1.5 mb-3" id="inputStarContainer" onmouseleave="resetSongFormRatingPreview()">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button" 
                                    onclick="setSongFormRating({{ $i }})" 
                                    onmouseenter="previewSongFormRating({{ $i }})" 
                                    class="star-btn text-2xl text-slate-600 transition-all duration-150 transform hover:scale-125 focus:outline-none cursor-pointer select-none" 
                                    data-star="{{ $i }}"
                                    title="Rating {{ $i }} Bintang">★</button>
                            @endfor
                            <span id="starRatingLabel" class="text-xs text-slate-400 ml-2 font-medium select-none">Beri rating 1 - 5 bintang</span>
                        </div>
                        <textarea id="inputReviewText" rows="3" placeholder="Tulis pendapat atau apresiasi Anda tentang komposisi audio, instrumen, dan makna lagu ini..." class="w-full bg-slate-900 text-white text-sm p-3.5 rounded-xl border border-slate-700/80 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 resize-none transition-all placeholder:text-slate-500"></textarea>
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mt-2.5">
                            <span class="text-[11px] text-slate-500 italic">*Ulasan Anda langsung tersimpan permanen di database.</span>
                            <button id="btnSubmitSongReview" onclick="submitSongReview()" class="px-5 py-2 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold rounded-xl text-xs transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-md flex items-center gap-1.5">
                                <span>Kirim Ulasan</span>
                            </button>
                        </div>
                    </div>

                    <!-- Daftar Ulasan Komunitas (Asli dari Database, Bukan Dummy) -->
                    <div class="border-t border-slate-800 pt-6">
                        <div class="flex items-center justify-between mb-3.5">
                            <h4 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>💬</span> Ulasan Komunitas (<span id="modalReviewsCount">0</span>)
                            </h4>
                            <span class="text-[11px] text-emerald-400 font-semibold flex items-center gap-1.5 bg-emerald-950/70 border border-emerald-500/30 px-2.5 py-0.5 rounded-full shadow-sm">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
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
        <div class="bg-slate-800 rounded-2xl max-w-md w-full mx-4 p-6 border border-slate-700 shadow-2xl relative">
            <div class="flex justify-between items-center mb-4 border-b border-slate-700 pb-3">
                <h3 class="text-xl font-bold text-white">Buat Playlist Baru</h3>
                <button onclick="closeCreatePlaylistModal()" class="text-slate-400 hover:text-white text-2xl">&times;</button>
            </div>
            <form onsubmit="handleCreatePlaylistSubmit(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Nama Playlist</label>
                    <input type="text" id="newPlaylistName" required placeholder="Contoh: Playlist Santai Malam" class="w-full bg-slate-900 text-white text-sm px-4 py-2.5 rounded-xl border border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Deskripsi (Opsional)</label>
                    <textarea id="newPlaylistDesc" rows="3" placeholder="Ceritakan tentang tema playlist ini..." class="w-full bg-slate-900 text-white text-sm px-4 py-2.5 rounded-xl border border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none"></textarea>
                </div>
                <button type="submit" class="w-full py-2.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold rounded-xl text-sm transition-transform hover:scale-102 shadow-lg">
                    Simpan Playlist
                </button>
            </form>
        </div>
    </div>

    <!-- ==================== MODAL: EDIT PLAYLIST ==================== -->
    <div id="editPlaylistModal" class="modal">
        <div class="bg-slate-800 rounded-2xl max-w-md w-full mx-4 p-6 border border-slate-700 shadow-2xl relative">
            <div class="flex justify-between items-center mb-4 border-b border-slate-700 pb-3">
                <h3 class="text-xl font-bold text-white">Edit Info Playlist</h3>
                <button onclick="closeEditPlaylistModal()" class="text-slate-400 hover:text-white text-2xl">&times;</button>
            </div>
            <form onsubmit="handleEditPlaylistSubmit(event)" class="space-y-4">
                <input type="hidden" id="editPlaylistId">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Nama Playlist</label>
                    <input type="text" id="editPlaylistName" required placeholder="Nama playlist..." class="w-full bg-slate-900 text-white text-sm px-4 py-2.5 rounded-xl border border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Deskripsi (Opsional)</label>
                    <textarea id="editPlaylistDesc" rows="3" placeholder="Ceritakan tentang tema playlist ini..." class="w-full bg-slate-900 text-white text-sm px-4 py-2.5 rounded-xl border border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none"></textarea>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="closeEditPlaylistModal()" class="flex-1 py-2.5 bg-slate-700 hover:bg-slate-600 text-white rounded-xl text-sm font-medium">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold rounded-xl text-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== MODAL: CROP GAMBAR PLAYLIST (CROPPER.JS) ==================== -->
    <div id="cropPlaylistImageModal" class="modal" onclick="if(event.target === this) closeCropPlaylistModal()">
        <div class="bg-slate-800 rounded-2xl max-w-lg w-full mx-4 p-6 border border-slate-700 shadow-2xl relative">
            <div class="flex justify-between items-center mb-4 border-b border-slate-700 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-emerald-950/80 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2v14a2 2 0 0 0 2 2h14"/><path d="M18 22V8a2 2 0 0 0-2-2H2"/></svg>
                    </span>
                    <div>
                        <h3 class="text-lg font-bold text-white leading-tight">Sesuaikan & Crop Sampul Playlist</h3>
                        <p class="text-[11px] text-slate-400">Geser dan perbesar area gambar persegi (1:1) sesuai keinginan Anda</p>
                    </div>
                </div>
                <button type="button" onclick="closeCropPlaylistModal()" class="text-slate-400 hover:text-white text-2xl leading-none">&times;</button>
            </div>

            <!-- Cropper Canvas Area -->
            <div class="w-full h-72 sm:h-80 bg-slate-950 rounded-xl overflow-hidden border border-slate-700 flex items-center justify-center relative">
                <img id="cropperTargetImage" src="" alt="Preview Crop" class="max-w-full max-h-full block">
            </div>

            <!-- Crop Controls Toolbar -->
            <div class="flex items-center justify-between gap-2 mt-4 bg-slate-900/80 px-3 py-2 rounded-xl border border-slate-700/70">
                <div class="flex items-center gap-1.5">
                    <button type="button" onclick="cropperZoom(0.1)" title="Perbesar (Zoom In)" class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-emerald-400 border border-slate-700 transition-colors">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/><line x1="11" x2="11" y1="8" y2="14"/><line x1="8" x2="14" y1="11" y2="11"/></svg>
                    </button>
                    <button type="button" onclick="cropperZoom(-0.1)" title="Perkecil (Zoom Out)" class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-emerald-400 border border-slate-700 transition-colors">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/><line x1="8" x2="14" y1="11" y2="11"/></svg>
                    </button>
                    <button type="button" onclick="cropperRotate(-90)" title="Putar Kiri 90°" class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-emerald-400 border border-slate-700 transition-colors">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    </button>
                    <button type="button" onclick="cropperRotate(90)" title="Putar Kanan 90°" class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-emerald-400 border border-slate-700 transition-colors">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                    </button>
                    <button type="button" onclick="cropperReset()" title="Reset Posisi" class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 text-xs font-medium transition-colors">
                        Reset
                    </button>
                </div>
                <button type="button" onclick="triggerPlaylistImageUpload()" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-emerald-500/30 text-xs font-semibold transition-colors inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                    Pilih File Lain
                </button>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2.5 mt-5">
                <button type="button" onclick="closeCropPlaylistModal()" class="flex-1 py-2.5 bg-slate-700 hover:bg-slate-600 text-white rounded-xl text-sm font-semibold transition-colors">
                    Batal
                </button>
                <button type="button" id="saveCroppedPlaylistBtn" onclick="saveCroppedPlaylistImage()" class="flex-1 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold rounded-xl text-sm transition-all shadow-lg shadow-emerald-500/20">
                    Potong & Simpan Gambar
                </button>
            </div>
        </div>
    </div>

    <!-- ==================== MODAL: EDIT REVIEW ==================== -->
    <div id="editReviewModal" class="modal">
        <div class="bg-slate-800 rounded-2xl max-w-md w-full mx-4 p-6 border border-slate-700 shadow-2xl relative">
            <div class="flex justify-between items-center mb-4 border-b border-slate-700 pb-3">
                <h3 class="text-xl font-bold text-white">Edit Ulasan Anda</h3>
                <button onclick="closeEditReviewModal()" class="text-slate-400 hover:text-white text-2xl">&times;</button>
            </div>
            <form onsubmit="handleUpdateReviewSubmit(event)" class="space-y-4">
                <input type="hidden" id="editReviewId">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-2">Rating</label>
                    <div class="flex items-center gap-1.5" id="editStarContainer" onmouseleave="resetEditFormRatingPreview()">
                        @for($i = 1; $i <= 5; $i++)
                            <button type="button" 
                                onclick="setEditFormRating({{ $i }})" 
                                onmouseenter="previewEditFormRating({{ $i }})" 
                                class="edit-star-btn text-2xl text-slate-600 transition-all duration-150 transform hover:scale-125 focus:outline-none cursor-pointer select-none" 
                                data-star="{{ $i }}"
                                title="Rating {{ $i }} Bintang">★</button>
                        @endfor
                        <span id="editStarRatingLabel" class="text-xs text-slate-400 ml-2 font-medium select-none">5 Bintang</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Teks Ulasan</label>
                    <textarea id="editReviewText" rows="4" required class="w-full bg-slate-900 text-white text-sm p-3 rounded-xl border border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none"></textarea>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="closeEditReviewModal()" class="flex-1 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl text-sm font-medium">Batal</button>
                    <button type="submit" class="flex-1 py-2 bg-emerald-500 hover:bg-emerald-400 text-slate-950 rounded-xl text-sm font-bold">Perbarui</button>
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
