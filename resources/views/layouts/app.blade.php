<!DOCTYPE html>
<html lang="id" class="min-h-screen">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Minima - Platform Jelajah Musik Interaktif & Objek 3D</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857'
                        }
                    }
                }
            }
        }
    </script>

    <!-- Three.js untuk Objek 3D Vinyl Player -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

    <!-- Cropper.js untuk foto playlist -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('style.css') }}?v={{ filemtime(public_path('style.css')) }}">
    <style>
        .vinyl-canvas-container {
            position: relative;
            width: 100%;
            height: 320px;
            background: radial-gradient(circle, #1e293b 0%, #0f172a 100%);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.6);
        }
        .audio-bar {
            backdrop-filter: blur(16px);
            background: rgba(15, 23, 42, 0.92);
        }
    </style>
</head>
<body class="bg-slate-900 text-white min-h-screen flex flex-col antialiased selection:bg-emerald-500 selection:text-white">

    <!-- Top Navigation Bar -->
    <nav class="bg-slate-800/90 backdrop-blur-md border-b border-slate-700/80 fixed top-0 left-0 w-full z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Brand Logo & Navigation Links -->
                <div class="flex items-center space-x-6">
                    <a href="javascript:void(0)" onclick="showSection('home', document.querySelector('.nav-btn'))" class="flex items-center space-x-2">
                        <svg class="h-8 w-8 text-emerald-500 animate-pulse" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="3" y="8" width="4" height="8" rx="1" class="opacity-70"></rect>
                            <rect x="9" y="4" width="6" height="16" rx="2"></rect>
                            <rect x="17" y="8" width="4" height="8" rx="1" class="opacity-70"></rect>
                        </svg>
                        <span class="text-2xl font-black tracking-wider text-white">Minima<span class="text-emerald-400 text-sm font-semibold ml-1.5 px-2 py-0.5 bg-emerald-950/80 border border-emerald-500/30 rounded-full">3D & Media</span></span>
                    </a>
                    
                    <div class="hidden md:flex relative items-center space-x-1" id="nav-buttons-container">
                        <button onclick="showSection('home', this)" class="nav-btn px-4 py-2 text-sm font-medium text-slate-300 hover:text-white rounded-lg transition-colors z-10 active">Beranda</button> 
                        <button onclick="showSection('discover', this)" class="nav-btn px-4 py-2 text-sm font-medium text-slate-300 hover:text-white rounded-lg transition-colors z-10">Telusuri</button>
                        <button onclick="showSection('playlists', this)" class="nav-btn px-4 py-2 text-sm font-medium text-slate-300 hover:text-white rounded-lg transition-colors z-10">Playlist</button>
                        <button onclick="showSection('reviews', this)" class="nav-btn px-4 py-2 text-sm font-medium text-slate-300 hover:text-white rounded-lg transition-colors z-10">Ulasan</button>
                        <div class="h-6 w-px bg-slate-700 mx-2 z-10"></div>
                        <button id="btn-about" onclick="showSection('about', this)" class="nav-btn px-4 py-2 text-sm font-medium text-slate-300 hover:text-white rounded-lg transition-colors z-10">Tentang Kami</button>
                        <div id="nav-slider" class="absolute bg-slate-700/80 h-9 rounded-lg transition-all duration-300 ease-in-out top-1 left-0"></div>
                    </div>
                </div>

                <!-- Search Input & Live Recommendations Dropdown -->
                <div class="flex items-center space-x-4">
                    <div class="relative w-64 sm:w-80 md:w-96" id="searchWrapper">
                        <input type="text" id="searchInput" placeholder="Cari musik, artis, album..." autocomplete="off"
                            class="bg-slate-800/90 text-white placeholder-slate-400 text-sm px-4 py-2 rounded-xl w-full border border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 pr-10 transition-all"> 
                        <button id="searchButton" onclick="triggerSearch()" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white p-1 rounded-full hover:bg-slate-700 transition-colors" title="Cari">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </button>

                        <!-- Live Search Recommendations Dropdown (Solid Non-Transparent) -->
                        <div id="searchSuggestions" class="hidden absolute top-full left-0 right-0 mt-2 bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl overflow-hidden z-50 max-h-[420px] overflow-y-auto text-left" style="background-color: #0f172a;">
                            <!-- Diisi via JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pt-24 pb-32 w-full">
        @yield('content')
    </main>

    <!-- Sticky Bottom Audio Player Bar with 3D Vinyl Indicator (Hidden initially until a song is played) -->
    <div id="bottomAudioPlayer" class="fixed bottom-0 left-0 w-full z-40 border-t border-slate-700/80 audio-bar py-3 px-4 sm:px-8 shadow-2xl transition-all duration-500 ease-in-out transform translate-y-full opacity-0 pointer-events-none">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
            <!-- Left: Current Song Info -->
            <div class="flex items-center space-x-3 w-1/4 min-w-[200px]">
                <div class="relative group">
                    <img id="playerCover" src="{{ asset('image/queen1.jpg') }}" class="w-14 h-14 rounded-lg object-cover shadow-md border border-slate-700">
                    <button onclick="openVinylModal()" title="Buka 3D Vinyl Player" class="absolute inset-0 bg-black/60 rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="text-xs font-bold text-emerald-400 uppercase tracking-tighter">3D</span>
                    </button>
                </div>
                <div class="truncate">
                    <h4 id="playerTitle" class="text-sm font-bold text-white truncate hover:underline cursor-pointer" onclick="openVinylModal()">Pilih Lagu</h4>
                    <p id="playerArtist" class="text-xs text-slate-400 truncate">-</p>
                </div>
            </div>

            <!-- Center: Audio Controls & Progress -->
            <div class="flex flex-col items-center w-2/4 max-w-md">
                <div class="flex items-center space-x-4 mb-1">
                    <button onclick="playPrevSong()" class="text-slate-400 hover:text-white transition-colors" title="Lagu Sebelumnya">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>
                    </button>
                    <button id="mainPlayBtn" onclick="togglePlayAudio()" class="w-10 h-10 bg-emerald-500 hover:bg-emerald-400 rounded-full flex items-center justify-center text-slate-950 font-bold shadow-lg transition-transform hover:scale-105" title="Play/Pause">
                        <svg id="playIcon" class="w-5 h-5 fill-current translate-x-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        <svg id="pauseIcon" class="w-5 h-5 fill-current hidden" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                    </button>
                    <button onclick="playNextSong()" class="text-slate-400 hover:text-white transition-colors" title="Lagu Selanjutnya">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>
                    </button>
                </div>
                <div class="w-full flex items-center space-x-2.5 text-xs text-slate-400 select-none">
                    <span id="playerCurrentTime" class="w-10 text-right font-mono font-medium text-slate-300">0:00</span>
                    <div class="relative w-full flex items-center py-2 cursor-pointer">
                        <input type="range" id="playerProgressBar" min="0" max="100" value="0" step="0.1" class="player-seek-slider" title="Geser atau klik untuk memilih menit lagu">
                    </div>
                    <span id="playerDuration" class="w-10 text-left font-mono font-medium text-slate-400">3:30</span>
                </div>
            </div>

            <!-- Right: Volume, 3D Vinyl Button, Video MV Button & Close Button -->
            <div class="flex items-center justify-end space-x-2 sm:space-x-2.5 w-1/4">
                <!-- Volume Control -->
                <div class="hidden md:flex items-center space-x-1.5 px-2.5 py-1 bg-slate-800/90 rounded-xl border border-slate-700/80 shadow-inner">
                    <button id="playerVolumeBtn" onclick="toggleMuteAudio()" class="text-slate-400 hover:text-white transition-colors p-0.5" title="Mute/Unmute Volume">
                        <svg id="volumeHighIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                        </svg>
                        <svg id="volumeMuteIcon" class="w-4 h-4 hidden text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 14l4-4m0 4l-4-4" />
                        </svg>
                    </button>
                    <input type="range" id="playerVolumeSlider" min="0" max="1" step="0.05" value="1" oninput="setAudioVolume(this.value)" class="w-14 sm:w-16 h-1 bg-slate-700 rounded-lg appearance-none cursor-pointer accent-emerald-500" title="Atur Volume Suara">
                </div>

                <button onclick="openVinylModal()" class="flex items-center space-x-1.5 px-3 py-1.5 bg-slate-800 hover:bg-emerald-950/80 border border-slate-700 hover:border-emerald-500/50 rounded-xl text-xs font-semibold text-emerald-400 transition-all shadow" title="Tampilkan Piringan Hitam 3D">
                    <svg class="w-4 h-4 text-emerald-400 animate-spin" style="animation-duration: 6s;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <span class="hidden sm:inline">Piringan 3D</span>
                </button>
                <button onclick="openVideoModal()" class="flex items-center space-x-1.5 px-3 py-1.5 bg-red-950/50 hover:bg-red-900/60 border border-red-700/50 rounded-xl text-xs font-semibold text-red-400 transition-all shadow" title="Tonton Video Musik">
                    <svg class="w-4 h-4 fill-current text-red-500" viewBox="0 0 24 24">
                        <path d="M10 15l5.19-3L10 9v6m11.56-7.83c.13.47.22 1.1.28 1.9.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83-.25.9-.83 1.48-1.73 1.73-.47.13-1.33.22-2.65.28-1.3.07-2.49.1-3.59.1L12 22c-4.19 0-6.8-.16-7.83-.44-.9-.25-1.48-.83-1.73-1.73-.13-.47-.22-1.1-.28-1.9-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83.25-.9.83-1.48 1.73-1.73.47-.13 1.33-.22 2.65-.28 1.3-.07 2.49-.1 3.59-.1L12 2c4.19 0 6.8.16 7.83.44.9.25 1.48.83 1.73 1.73z"/>
                    </svg>
                    <span class="hidden sm:inline">Video MV</span>
                </button>
                <button onclick="closeAudioPlayer()" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-red-400 hover:bg-slate-800 rounded-full border border-slate-700/70 hover:border-red-500/50 transition-all shadow ml-1" title="Hentikan & Tutup Pemutar Musik">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden HTML5 Audio Element (Fallback) -->
    <audio id="globalAudioPlayer" preload="auto"></audio>

    <!-- Background YouTube Player Container for Full-Duration Music Playback -->
    <div id="ytAudioPlayerContainer" class="fixed bottom-0 left-0 w-[200px] h-[200px] pointer-events-none opacity-[0.001] -z-50 overflow-hidden" aria-hidden="true">
        <div id="ytAudioPlayer"></div>
    </div>

    <!-- 3D Vinyl Interactive Modal (Three.js) -->
    <div id="vinylModal" class="modal">
        <div class="bg-slate-900 border border-slate-700/80 rounded-3xl max-w-2xl w-full mx-4 p-6 shadow-2xl relative overflow-hidden">
            <!-- Modal Header -->
            <div class="flex justify-between items-center mb-4 border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2.5">
                    <span class="w-3 h-3 rounded-full bg-emerald-500 animate-ping"></span>
                    <h3 class="text-xl font-bold text-white tracking-wide">Objek 3D Piringan Hitam <span class="text-emerald-400 text-xs font-semibold ml-1.5 px-2.5 py-0.5 bg-emerald-950/80 border border-emerald-500/30 rounded-full">Turntable Interaktif</span></h3>
                </div>
                <button onclick="closeVinylModal()" class="text-slate-400 hover:text-white text-2xl transition-colors p-1" title="Tutup">&times;</button>
            </div>

            <!-- 3D Canvas Container -->
            <div id="vinyl3dContainer" class="vinyl-canvas-container flex items-center justify-center relative cursor-grab active:cursor-grabbing select-none">
                <!-- Hint badge overlay -->
                <div class="absolute top-3 left-3 z-10 bg-slate-950/80 backdrop-blur-md px-3 py-1.5 rounded-xl border border-slate-700/60 text-[11px] font-medium text-slate-300 flex items-center gap-1.5 pointer-events-none shadow-lg">
                    <span class="text-emerald-400">💡</span>
                    <span><strong>Klik piringan</strong> untuk Play/Pause • <strong>Geser</strong> untuk rotasi sudut pandang 3D</span>
                </div>
                <p id="vinylLoadingText" class="text-slate-400 text-sm">Memuat model 3D Three.js...</p>
            </div>

            <!-- Interactive Player Controls Inside 3D Modal -->
            <div class="mt-4 bg-slate-800/80 rounded-2xl p-4 border border-slate-700/70 shadow-lg">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <!-- Current Song Info -->
                    <div class="flex items-center space-x-3 text-center sm:text-left truncate max-w-xs w-full sm:w-auto">
                        <img id="vinylModalCover" src="{{ asset('image/queen1.jpg') }}" class="w-12 h-12 rounded-xl object-cover shadow-md border border-slate-600 flex-shrink-0">
                        <div class="truncate">
                            <h4 id="vinylSongTitle" class="text-sm font-bold text-white truncate">Bohemian Rhapsody</h4>
                            <p id="vinylSongArtist" class="text-xs text-slate-400 truncate">Queen</p>
                        </div>
                    </div>

                    <!-- Center Play/Pause & Prev/Next Controls -->
                    <div class="flex items-center space-x-3">
                        <button onclick="playPrevSong()" class="w-9 h-9 rounded-full bg-slate-700/80 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center transition-transform hover:scale-105" title="Lagu Sebelumnya">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>
                        </button>
                        <button id="vinylPlayToggleBtn" onclick="togglePlayAudio()" class="w-12 h-12 bg-emerald-500 hover:bg-emerald-400 text-slate-950 rounded-full flex items-center justify-center font-bold shadow-xl transition-transform hover:scale-110" title="Play / Pause Piringan">
                            <svg id="vinylModalPlayIcon" class="w-6 h-6 fill-current translate-x-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            <svg id="vinylModalPauseIcon" class="w-6 h-6 fill-current hidden" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                        </button>
                        <button onclick="playNextSong()" class="w-9 h-9 rounded-full bg-slate-700/80 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center transition-transform hover:scale-105" title="Lagu Selanjutnya">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>
                        </button>
                    </div>

                    <!-- Status Badge -->
                    <div class="flex items-center">
                        <span id="vinylStatusBadge" class="px-3 py-1.5 rounded-full bg-slate-800 text-slate-400 text-xs font-semibold border border-slate-700">Status: Berhenti (Paused)</span>
                    </div>
                </div>

                <!-- Progress Bar Inside 3D Modal -->
                <div class="mt-3 pt-3 border-t border-slate-700/50 flex items-center space-x-2.5 text-xs text-slate-400 select-none">
                    <span id="vinylCurrentTime" class="w-10 text-right font-mono font-medium text-slate-300">0:00</span>
                    <div class="relative w-full flex items-center py-1 cursor-pointer">
                        <input type="range" id="vinylProgressBar" min="0" max="100" value="0" step="0.1" class="player-seek-slider" title="Geser untuk memilih menit lagu">
                    </div>
                    <span id="vinylDuration" class="w-10 text-left font-mono font-medium text-slate-400">3:30</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Video Modal (YouTube Music Video Embed) -->
    <div id="videoModal" class="modal">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-4xl w-full mx-4 p-6 shadow-2xl relative">
            <div class="flex justify-between items-center mb-4 border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2 text-red-500">
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M10 15l5.19-3L10 9v6m11.56-7.83c.13.47.22 1.1.28 1.9.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83-.25.9-.83 1.48-1.73 1.73-.47.13-1.33.22-2.65.28-1.3.07-2.49.1-3.59.1L12 22c-4.19 0-6.8-.16-7.83-.44-.9-.25-1.48-.83-1.73-1.73-.13-.47-.22-1.1-.28-1.9-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83.25-.9.83-1.48 1.73-1.73.47-.13 1.33-.22 2.65-.28 1.3-.07 2.49-.1 3.59-.1L12 2c4.19 0 6.8.16 7.83.44.9.25 1.48.83 1.73 1.73z"/></svg>
                    <h3 id="videoModalTitle" class="text-xl font-bold text-white">Pemutar Video Musik</h3>
                </div>
                <button onclick="closeVideoModal()" class="text-slate-400 hover:text-white text-2xl transition-colors">&times;</button>
            </div>
            
            <div class="relative w-full aspect-video bg-black rounded-xl overflow-hidden shadow-2xl border border-slate-800">
                <iframe id="videoIframe" class="w-full h-full" src="" title="Music Video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>

            <p class="text-xs text-slate-400 mt-3 text-center">Elemen Multimedia Video terintegrasi secara dinamis berdasarkan data lagu.</p>
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('js/minima-app.js') }}?v={{ filemtime(public_path('js/minima-app.js')) }}"></script>
</body>
</