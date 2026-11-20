<!DOCTYPE html>
<html lang="id" class="min-h-screen">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Minima - Platform Jelajah Musik Interaktif & Objek 3D</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('favicon.svg') }}?v=2">

    <!-- Google Fonts: Inter (600 Semibold & 800 Extrabold per design.md) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (Configured with design.md Color & Radius Tokens) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            500: '#00b24b',
                            600: '#009e42',
                            700: '#008236'
                        },
                        secondary: {
                            500: '#0075ff',
                            600: '#0062d6'
                        },
                        emerald: {
                            300: '#33d17a',
                            400: '#00c853',
                            500: '#00b24b',
                            600: '#009e42',
                            800: '#004d20',
                            950: '#071f12'
                        },
                        slate: {
                            100: '#f0f2f5',
                            200: '#e2e5ec',
                            300: '#c5cad6',
                            400: '#9aa1b1',
                            500: '#53575f',
                            600: '#3a3d44',
                            700: '#2d2f33',
                            750: '#26282c',
                            800: '#2e3035',
                            900: '#141415',
                            950: '#0c0c0d'
                        }
                    },
                    borderRadius: {
                        'xs': '4px',
                        'sm': '6px',
                        'md': '8px',
                        'lg': '10px',
                        'xl': '12px',
                        '2xl': '16px',
                        '3xl': '16px',
                        'card': '16px',
                        'full': '999px'
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
            background: radial-gradient(circle, #222428 0%, #141415 100%);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #2d2f33;
        }
        .audio-bar {
            backdrop-filter: blur(16px);
            background: rgba(20, 20, 21, 0.96);
        }
    </style>
</head>
<body class="bg-[#141415] text-[#f0f2f5] font-sans font-semibold min-h-screen flex flex-col antialiased selection:bg-[#00b24b] selection:text-white">

    <!-- Top Navigation Bar (design.md top-nav: canvas #141415 + hairline #2d2f33) -->
    <nav class="bg-[#141415] border-b border-[#2d2f33] fixed top-0 left-0 w-full z-50">
        <div class="w-full px-4 sm:px-6 lg:px-10">
            <div class="flex justify-between items-center h-16">
                <!-- Brand Logo & Navigation Links -->
                <div class="flex items-center space-x-6">
                    <a href="javascript:void(0)" onclick="showSection('home', document.querySelector('.nav-btn'))" class="flex items-center space-x-2">
                        <svg class="h-8 w-8 text-[#00b24b]" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="3" y="8" width="4" height="8" rx="1" class="opacity-70"></rect>
                            <rect x="9" y="4" width="6" height="16" rx="2"></rect>
                            <rect x="17" y="8" width="4" height="8" rx="1" class="opacity-70"></rect>
                        </svg>
                        <span class="text-2xl font-extrabold tracking-wide text-[#f0f2f5]">Minima</span>
                    </a>
                    
                    <div class="hidden md:flex relative items-center gap-1" id="nav-buttons-container">
                        <button onclick="showSection('home', this)" class="nav-btn inline-flex items-center justify-center px-4 py-2 text-[14px] font-semibold text-[#9aa1b1] hover:text-[#f0f2f5] rounded-[10px] transition-colors z-10 active">Beranda</button> 
                        <button onclick="showSection('discover', this)" class="nav-btn inline-flex items-center justify-center px-4 py-2 text-[14px] font-semibold text-[#9aa1b1] hover:text-[#f0f2f5] rounded-[10px] transition-colors z-10">Telusuri</button>
                        <button onclick="showSection('playlists', this)" class="nav-btn inline-flex items-center justify-center px-4 py-2 text-[14px] font-semibold text-[#9aa1b1] hover:text-[#f0f2f5] rounded-[10px] transition-colors z-10">Playlist</button>
                        <div class="h-6 w-px bg-[#2d2f33] mx-2 z-10"></div>
                        <button id="btn-about" onclick="showSection('about', this)" class="nav-btn inline-flex items-center justify-center px-4 py-2 text-[14px] font-semibold text-[#9aa1b1] hover:text-[#f0f2f5] rounded-[10px] transition-colors z-10">Tentang Kami</button>
                        <div id="nav-slider" class="absolute bottom-0 left-0 h-[3.5px] rounded-full pointer-events-none"></div>
                    </div>
                </div>

                <!-- Search Input, Live Recommendations Dropdown & User Account Auth -->
                <div class="flex items-center space-x-3">
                    <div class="relative w-48 sm:w-64 lg:w-80" id="searchWrapper">
                        <input type="text" id="searchInput" placeholder="Cari musik, artis, album..." autocomplete="off"
                            class="bg-[#2e3035] text-[#f0f2f5] placeholder-[#53575f] text-[14px] font-semibold px-3 py-2 rounded-[8px] w-full border border-[#2d2f33] focus:outline-none focus:border-[#0075ff] pr-9 transition-all"> 
                        <button id="searchButton" onclick="triggerSearch()" class="absolute right-2 top-1/2 -translate-y-1/2 text-[#9aa1b1] hover:text-[#f0f2f5] p-1 rounded-full hover:bg-[#383b42] transition-colors" title="Cari">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </button>

                        <!-- Live Search Recommendations Dropdown (design.md feature-card-elevated: #2e3035 + #2d2f33 + 16px rounded) -->
                        <div id="searchSuggestions" class="hidden absolute top-full left-0 right-0 mt-2 bg-[#2e3035] border border-[#2d2f33] rounded-[16px] overflow-hidden z-50 max-h-[420px] overflow-y-auto text-left">
                            <!-- Diisi via JavaScript -->
                        </div>
                    </div>

                    <!-- User Account Auth Button / Profile Badge -->
                    <div id="navAuthContainer" class="flex items-center flex-shrink-0">
                        @auth
                            <div class="flex items-center gap-2 bg-[#2e3035] border border-[#2d2f33] rounded-[12px] px-2.5 py-1.5">
                                <div class="w-7 h-7 rounded-[8px] bg-[#00b24b] text-white font-extrabold text-xs flex items-center justify-center uppercase">
                                    {{ collect(explode(' ', Auth::user()->name))->map(fn($w) => substr($w, 0, 1))->take(2)->implode('') }}
                                </div>
                                <div class="hidden sm:block text-left max-w-[120px]">
                                    <p class="text-xs font-extrabold text-[#f0f2f5] truncate leading-tight">{{ Auth::user()->name }}</p>
                                    <p class="text-[10px] font-semibold text-[#00b24b] truncate leading-tight">@<span>{{ Auth::user()->username ?? 'member' }}</span></p>
                                </div>
                                <button onclick="handleLogout()" class="ml-1 px-2.5 py-1 rounded-[8px] bg-[#141415] hover:bg-red-500/20 text-[#9aa1b1] hover:text-red-400 border border-[#2d2f33] hover:border-red-500/40 text-[11px] font-semibold transition-colors" title="Keluar dari Akun">
                                    Keluar
                                </button>
                            </div>
                        @else
                            <div class="flex items-center gap-2">
                                <button onclick="openAuthModal('login')" class="px-3 py-2 rounded-[10px] bg-[#0075ff] hover:bg-[#0062d6] text-white font-semibold text-[13px] leading-[1.3] transition-colors inline-flex items-center gap-1.5 whitespace-nowrap">
                                    <span>Masuk</span>
                                </button>
                                <button onclick="openAuthModal('register')" class="px-3 py-2 rounded-[10px] bg-[#00b24b] hover:bg-[#00c853] text-white font-semibold text-[13px] leading-[1.3] transition-colors inline-flex items-center gap-1.5 whitespace-nowrap">
                                    <span>Daftar</span>
                                </button>
                            </div>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="flex-1 w-full px-4 sm:px-6 lg:px-10 py-8 pt-24 pb-32">
        @yield('content')
    </main>

    <!-- Sticky Bottom Audio Player Bar with 3D Vinyl Indicator (Hidden initially until a song is played) -->
    <div id="bottomAudioPlayer" class="fixed bottom-0 left-0 w-full z-40 border-t border-[#2d2f33] bg-[#141415] py-3 px-4 sm:px-6 lg:px-8 transition-all duration-500 ease-in-out transform translate-y-full opacity-0 pointer-events-none">
        <div class="w-full flex flex-col gap-2.5 md:flex-row md:items-center md:justify-between xl:grid xl:grid-cols-[minmax(0,1fr)_minmax(300px,420px)_minmax(0,1fr)] md:gap-4">
            <!-- Left: Song Info & Cover -->
            <div class="flex items-center space-x-3 min-w-0 w-full md:w-auto xl:max-w-full xl:justify-self-start">
                <div class="relative group flex-shrink-0">
                    <img id="playerCover" src="{{ asset('image/queen1.jpg') }}" class="w-12 h-12 sm:w-14 sm:h-14 rounded-[10px] object-cover border border-[#2d2f33]">
                    <button onclick="openVinylModal()" title="Buka 3D Vinyl Player" class="absolute inset-0 bg-black/65 rounded-[10px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-[#00b24b]">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M16 3.937A9 9 0 1 0 21 12"/><path d="M11 12a1 1 0 1 0 2 0a1 1 0 1 0-2 0m8-8a1 1 0 1 0 2 0a1 1 0 1 0-2 0"/><path d="m20 4l-3.5 10l-2.5 2"/></g></svg>
                    </button>
                </div>
                <div class="min-w-0 truncate">
                    <h4 id="playerTitle" class="text-[14px] font-semibold text-[#f0f2f5] truncate hover:underline cursor-pointer" onclick="openVinylModal()">Pilih Lagu</h4>
                    <p id="playerArtist" class="text-[12px] font-semibold text-[#9aa1b1] truncate">-</p>
                </div>
            </div>

            <!-- Center: Audio Controls & Progress (Centered symmetrically across player bar) -->
            <div class="flex flex-col items-center w-full md:flex-1 md:max-w-md xl:max-w-none mx-auto">
                <div class="flex items-center space-x-4 mb-1">
                    <button onclick="playPrevSong()" class="text-[#9aa1b1] hover:text-[#f0f2f5] transition-colors" title="Lagu Sebelumnya">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>
                    </button>
                    <button id="mainPlayBtn" onclick="togglePlayAudio()" class="w-10 h-10 p-0 leading-none bg-[#00b24b] hover:bg-[#00c853] rounded-full flex items-center justify-center text-white font-bold transition-transform hover:scale-105" title="Play/Pause">
                        <svg id="playIcon" class="w-5 h-5 fill-current block m-auto" viewBox="0 0 24 24"><path d="M7.5 5.5v13l10-6.5z"/></svg>
                        <svg id="pauseIcon" class="w-5 h-5 fill-current hidden m-auto" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                    </button>
                    <button onclick="playNextSong()" class="text-[#9aa1b1] hover:text-[#f0f2f5] transition-colors" title="Lagu Selanjutnya">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>
                    </button>
                </div>
                <div class="w-full flex items-center space-x-2.5 text-xs text-[#9aa1b1] select-none">
                    <span id="playerCurrentTime" class="w-10 text-right font-mono font-semibold text-[#f0f2f5]">0:00</span>
                    <div class="relative w-full flex items-center py-2 cursor-pointer">
                        <input type="range" id="playerProgressBar" min="0" max="100" value="0" step="0.1" class="player-seek-slider" title="Geser atau klik untuk memilih menit lagu">
                    </div>
                    <span id="playerDuration" class="w-10 text-left font-mono font-semibold text-[#9aa1b1]">3:30</span>
                </div>
            </div>

            <!-- Right: Volume, Unified Multimedia Mode Dock (3D Vinyl | Lirik | Video MV) & Close Button -->
            <div class="flex items-center justify-between md:justify-end gap-2 w-full md:w-auto xl:justify-self-end flex-shrink-0">
                <!-- Volume Control Pill -->
                <div class="hidden lg:flex items-center gap-2 h-10 px-2.5 bg-[#2e3035] rounded-[12px] border border-[#2d2f33] flex-shrink-0">
                    <button id="playerVolumeBtn" onclick="toggleMuteAudio()" class="text-[#9aa1b1] hover:text-[#f0f2f5] transition-colors flex items-center justify-center" title="Mute/Unmute Volume">
                        <svg id="volumeHighIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                        </svg>
                        <svg id="volumeMuteIcon" class="w-4 h-4 hidden text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 14l4-4m0 4l-4-4" />
                        </svg>
                    </button>
                    <input type="range" id="playerVolumeSlider" min="0" max="1" step="0.02" value="1" oninput="setAudioVolume(this.value)" class="w-16 2xl:w-20 player-volume-slider" title="Atur Volume Suara">
                </div>

                <!-- Unified Multimedia Mode Switcher Dock -->
                <div class="h-10 p-1 bg-[#2e3035] rounded-[12px] border border-[#2d2f33] inline-flex items-center gap-1 flex-shrink-0">
                    <button onclick="openVinylModal()" class="h-8 px-2.5 rounded-[8px] bg-[#141415] hover:bg-[#383b42] border border-[#2d2f33] text-[13px] font-semibold text-[#f0f2f5] transition-colors inline-flex items-center justify-center gap-1.5 whitespace-nowrap" title="Tampilkan Piringan Hitam 3D">
                        <svg class="w-4 h-4 text-[#00b24b] flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M16 3.937A9 9 0 1 0 21 12"/><path d="M11 12a1 1 0 1 0 2 0a1 1 0 1 0-2 0m8-8a1 1 0 1 0 2 0a1 1 0 1 0-2 0"/><path d="m20 4l-3.5 10l-2.5 2"/></g></svg>
                        <span class="hidden sm:inline">Piringan 3D</span>
                    </button>

                    <button onclick="openLyricsModal()" class="h-8 px-2.5 rounded-[8px] bg-[#141415] hover:bg-[#383b42] border border-[#2d2f33] text-[13px] font-semibold text-[#f0f2f5] transition-colors inline-flex items-center justify-center gap-1.5 whitespace-nowrap" title="Tampilkan Lirik">
                        <svg class="w-3.5 h-3.5 text-[#0075ff] flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M470.8 365.6c-.9-53.7-1.6-107.4-2.4-161.1c-.9-63.6-1.7-127.3-2.6-190.9c-.1-9.9-6.6-15.2-15.9-13.1c-25.8 5.9-51.7 11.7-77.5 17.5C306.7 32.7 241 47.5 175.3 62.2c-9.1 2-12.2 6.1-12.1 15.5c.2 7.5.3 14.9.4 22.4c1.3 90.7 2.5 181.4 3.7 272.1v9.8c-2.5-1.9-4.4-3.5-6.4-5c-17-12.6-36.1-17.4-56.9-14c-51.5 8.5-78.6 64.8-53.4 110.5c15.3 27.7 48.4 43.4 78.8 37.3c38-7.6 62.5-38.6 61.7-77.8c-.4-20.2-.8-40.4-1.1-60.7l-3-224.1c-.1-4.6 0-9.2 0-14c85.6-19.2 171.1-38.4 256.8-57.6c1.1 81.6 2.2 162.6 3.3 244.4c-1.7-1.3-2.6-2.1-3.6-2.9c-17.3-13.9-37.1-19.3-58.9-16c-54.2 8.2-83.1 71.4-49.5 117.3c17.9 24.4 42.6 35.1 72.6 30.4c30.3-4.8 50.1-23 59.9-52c3.6-10.6 3.4-21.4 3.2-32.2M115.6 489.4c-28.6.1-51.7-23.7-51.6-52.8s23.6-52.6 53-52.2c27.9.4 51.4 22.4 51.2 53.2c-.2 29.6-23.7 51.8-52.6 51.8m327.7-438c0 .9-2.1 2.3-3.4 2.6c-12.5 3-25 5.8-37.5 8.6c-20 4.5-40.1 8.9-60.1 13.4c-41.4 9.3-82.8 18.5-124.3 27.8c-9.6 2.2-19.3 4.3-28.9 6.5c-.6.1-1.2.1-3 .3c0-8.8-.1-17.4.2-25.9c0-.9 2.2-2.2 3.5-2.5c14.7-3.4 29.5-6.7 44.3-10c19.8-4.4 39.5-8.8 59.3-13.3l113.7-25.5c10.6-2.4 21.2-4.9 31.9-7.3c1.1-.2 2.2-.2 4.4-.4c.1 8.9.2 17.3-.1 25.7m-47.4 377c-28.1 0-52-24.1-52-52.4c0-28.6 23.8-52.7 52.1-52.7c28.9 0 52.3 23.7 52.3 53.1c-.2 28.3-24 52-52.4 52"/><path fill="currentColor" d="M64 436.6c.1-29.1 23.6-52.6 53-52.2c27.9.4 51.4 22.4 51.2 53.2c-.2 29.6-23.7 51.8-52.6 51.8c-28.6.1-51.7-23.7-51.6-52.8m384.2-60.2c0-29.4-23.3-53.1-52.3-53.1c-28.3 0-52.1 24.1-52.1 52.7c0 28.2 24 52.4 52 52.4c28.5 0 52.3-23.7 52.4-52m-259-266c9.6-2.2 19.3-4.3 28.9-6.5c41.4-9.3 82.8-18.6 124.3-27.8c20-4.5 40.1-8.9 60.1-13.4c12.5-2.8 25-5.6 37.5-8.6c1.3-.3 3.4-1.7 3.4-2.6c.3-8.4.2-16.9.2-25.8c-2.2.2-3.3.1-4.4.4c-10.6 2.4-21.2 4.9-31.9 7.3L293.6 58.9c-19.8 4.4-39.5 8.8-59.3 13.3c-14.8 3.3-29.5 6.6-44.3 10c-1.4.3-3.5 1.6-3.5 2.5c-.3 8.6-.2 17.1-.2 25.9c1.7-.1 2.3-.1 2.9-.2" opacity=".7"/></svg>
                        <span class="hidden sm:inline">Lirik</span>
                    </button>

                    <button onclick="openVideoModalForCurrentSong()" class="h-8 px-2.5 rounded-[8px] bg-[#141415] hover:bg-red-600 border border-[#2d2f33] text-[13px] font-semibold text-[#f0f2f5] hover:text-white transition-colors inline-flex items-center justify-center gap-1.5 whitespace-nowrap group" title="Tonton Video Musik Resmi">
                        <svg class="w-4 h-4 text-red-500 group-hover:text-white fill-current flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>youtube</title><path fill="currentColor" d="m10 15l5.19-3L10 9zm11.56-7.83c.13.47.22 1.1.28 1.9c.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83c-.25.9-.83 1.48-1.73 1.73c-.47.13-1.33.22-2.65.28c-1.3.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44c-.9-.25-1.48-.83-1.73-1.73c-.13-.47-.22-1.1-.28-1.9c-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83c.25-.9.83-1.48 1.73-1.73c.47-.13 1.33-.22 2.65-.28c1.3-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44c.9.25 1.48.83 1.73 1.73"/></svg>
                        <span class="hidden sm:inline">Video MV</span>
                    </button>
                </div>

                <!-- Close Audio Player Button -->
                <button onclick="closeAudioPlayer()" class="w-10 h-10 flex-shrink-0 inline-flex items-center justify-center text-[#9aa1b1] hover:text-white bg-[#2e3035] hover:bg-red-600 rounded-[12px] border border-[#2d2f33] transition-colors" title="Hentikan & Tutup Pemutar Musik">
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
        <div class="ex-modal-card bg-[#2e3035] border border-[#2d2f33] rounded-[16px] max-w-2xl w-full mx-4 p-6 relative overflow-hidden">
            <!-- Modal Header -->
            <div class="flex justify-between items-center gap-3 mb-4 border-b border-[#2d2f33] pb-3">
                <div class="flex items-center space-x-2.5 min-w-0">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#00b24b] flex-shrink-0"></span>
                    <h3 class="text-[20px] font-extrabold text-[#f0f2f5] flex items-center gap-2 flex-wrap">
                        <span>Objek 3D Piringan Hitam</span>
                        <span class="bg-[#0075ff] text-white text-[11px] font-semibold px-2.5 py-1 rounded-full leading-none whitespace-nowrap">Turntable Interaktif</span>
                    </h3>
                </div>
                <div class="flex items-center gap-2.5 flex-shrink-0">
                    <span id="vinylStatusBadge" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#141415] text-[#9aa1b1] text-[12px] font-semibold border border-[#2d2f33] leading-none whitespace-nowrap">Status: Berhenti</span>
                    <button onclick="closeVinylModal()" class="text-[#9aa1b1] hover:text-white text-2xl transition-colors p-1 leading-none" title="Tutup">&times;</button>
                </div>
            </div>

            <!-- 3D Canvas Container -->
            <div id="vinyl3dContainer" class="vinyl-canvas-container flex items-center justify-center relative cursor-grab active:cursor-grabbing select-none">
                <!-- Hint badge overlay -->
                <div class="absolute top-3 left-3 z-10 bg-[#141415]/90 px-3 py-1.5 rounded-[8px] border border-[#2d2f33] text-[12px] font-semibold text-[#9aa1b1] flex items-center gap-1.5 pointer-events-none">
                    <svg class="w-3.5 h-3.5 text-[#0075ff] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span><strong class="text-[#f0f2f5]">Klik piringan</strong> untuk Play/Pause • <strong class="text-[#f0f2f5]">Geser</strong> untuk rotasi 3D</span>
                </div>
                <p id="vinylLoadingText" class="text-[#9aa1b1] text-sm">Memuat model 3D Three.js...</p>
            </div>

            <!-- Interactive Player Controls Inside 3D Modal -->
            <div class="mt-4 bg-[#141415] rounded-[16px] p-4 border border-[#2d2f33]">
                <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3 sm:gap-4">
                    <!-- Current Song Info -->
                    <div class="flex items-center space-x-3 min-w-0 w-full justify-center sm:justify-start text-left">
                        <img id="vinylModalCover" src="{{ asset('image/queen1.jpg') }}" class="w-12 h-12 rounded-[10px] object-cover border border-[#2d2f33] flex-shrink-0">
                        <div class="min-w-0 truncate">
                            <h4 id="vinylSongTitle" class="text-[14px] font-semibold text-[#f0f2f5] truncate">Bohemian Rhapsody</h4>
                            <p id="vinylSongArtist" class="text-[12px] font-semibold text-[#9aa1b1] truncate">Queen</p>
                        </div>
                    </div>

                    <!-- Center Play/Pause & Prev/Next Controls -->
                    <div class="flex items-center justify-center space-x-3 flex-shrink-0">
                        <button onclick="playPrevSong()" class="w-9 h-9 rounded-full bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] flex items-center justify-center transition-transform hover:scale-105" title="Lagu Sebelumnya">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>
                        </button>
                        <button id="vinylPlayToggleBtn" onclick="togglePlayAudio()" class="w-12 h-12 p-0 leading-none bg-[#00b24b] hover:bg-[#00c853] text-white rounded-full flex items-center justify-center font-bold transition-transform hover:scale-105" title="Play / Pause Piringan">
                            <svg id="vinylModalPlayIcon" class="w-6 h-6 fill-current block m-auto" viewBox="0 0 24 24"><path d="M7.5 5.5v13l10-6.5z"/></svg>
                            <svg id="vinylModalPauseIcon" class="w-6 h-6 fill-current hidden m-auto" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                        </button>
                        <button onclick="playNextSong()" class="w-9 h-9 rounded-full bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] flex items-center justify-center transition-transform hover:scale-105" title="Lagu Selanjutnya">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>
                        </button>
                    </div>

                    <!-- Switch to Lyrics / Video MV Shortcuts -->
                    <div class="flex items-center justify-center sm:justify-end gap-2 flex-shrink-0">
                        <button onclick="openLyricsModal()" class="h-9 px-3 rounded-[10px] bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] text-[13px] font-semibold border border-[#2d2f33] transition-colors inline-flex items-center gap-1.5 whitespace-nowrap" title="Beralih ke Lirik tanpa menghentikan lagu">
                            <svg class="w-3.5 h-3.5 text-[#0075ff] flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M470.8 365.6c-.9-53.7-1.6-107.4-2.4-161.1c-.9-63.6-1.7-127.3-2.6-190.9c-.1-9.9-6.6-15.2-15.9-13.1c-25.8 5.9-51.7 11.7-77.5 17.5C306.7 32.7 241 47.5 175.3 62.2c-9.1 2-12.2 6.1-12.1 15.5c.2 7.5.3 14.9.4 22.4c1.3 90.7 2.5 181.4 3.7 272.1v9.8c-2.5-1.9-4.4-3.5-6.4-5c-17-12.6-36.1-17.4-56.9-14c-51.5 8.5-78.6 64.8-53.4 110.5c15.3 27.7 48.4 43.4 78.8 37.3c38-7.6 62.5-38.6 61.7-77.8c-.4-20.2-.8-40.4-1.1-60.7l-3-224.1c-.1-4.6 0-9.2 0-14c85.6-19.2 171.1-38.4 256.8-57.6c1.1 81.6 2.2 162.6 3.3 244.4c-1.7-1.3-2.6-2.1-3.6-2.9c-17.3-13.9-37.1-19.3-58.9-16c-54.2 8.2-83.1 71.4-49.5 117.3c17.9 24.4 42.6 35.1 72.6 30.4c30.3-4.8 50.1-23 59.9-52c3.6-10.6 3.4-21.4 3.2-32.2M115.6 489.4c-28.6.1-51.7-23.7-51.6-52.8s23.6-52.6 53-52.2c27.9.4 51.4 22.4 51.2 53.2c-.2 29.6-23.7 51.8-52.6 51.8m327.7-438c0 .9-2.1 2.3-3.4 2.6c-12.5 3-25 5.8-37.5 8.6c-20 4.5-40.1 8.9-60.1 13.4c-41.4 9.3-82.8 18.5-124.3 27.8c-9.6 2.2-19.3 4.3-28.9 6.5c-.6.1-1.2.1-3 .3c0-8.8-.1-17.4.2-25.9c0-.9 2.2-2.2 3.5-2.5c14.7-3.4 29.5-6.7 44.3-10c19.8-4.4 39.5-8.8 59.3-13.3l113.7-25.5c10.6-2.4 21.2-4.9 31.9-7.3c1.1-.2 2.2-.2 4.4-.4c.1 8.9.2 17.3-.1 25.7m-47.4 377c-28.1 0-52-24.1-52-52.4c0-28.6 23.8-52.7 52.1-52.7c28.9 0 52.3 23.7 52.3 53.1c-.2 28.3-24 52-52.4 52"/><path fill="currentColor" d="M64 436.6c.1-29.1 23.6-52.6 53-52.2c27.9.4 51.4 22.4 51.2 53.2c-.2 29.6-23.7 51.8-52.6 51.8c-28.6.1-51.7-23.7-51.6-52.8m384.2-60.2c0-29.4-23.3-53.1-52.3-53.1c-28.3 0-52.1 24.1-52.1 52.7c0 28.2 24 52.4 52 52.4c28.5 0 52.3-23.7 52.4-52m-259-266c9.6-2.2 19.3-4.3 28.9-6.5c41.4-9.3 82.8-18.6 124.3-27.8c20-4.5 40.1-8.9 60.1-13.4c12.5-2.8 25-5.6 37.5-8.6c1.3-.3 3.4-1.7 3.4-2.6c.3-8.4.2-16.9.2-25.8c-2.2.2-3.3.1-4.4.4c-10.6 2.4-21.2 4.9-31.9 7.3L293.6 58.9c-19.8 4.4-39.5 8.8-59.3 13.3c-14.8 3.3-29.5 6.6-44.3 10c-1.4.3-3.5 1.6-3.5 2.5c-.3 8.6-.2 17.1-.2 25.9c1.7-.1 2.3-.1 2.9-.2" opacity=".7"/></svg>
                            <span>Lirik</span>
                        </button>
                        <button onclick="openVideoModalForCurrentSong()" class="h-9 px-3 rounded-[10px] bg-[#2e3035] hover:bg-red-600 text-[#f0f2f5] hover:text-white text-[13px] font-semibold border border-[#2d2f33] transition-colors inline-flex items-center gap-1.5 whitespace-nowrap group" title="Tonton Video Musik (MV)">
                            <svg class="w-4 h-4 text-red-500 group-hover:text-white fill-current flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="m10 15l5.19-3L10 9zm11.56-7.83c.13.47.22 1.1.28 1.9c.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83c-.25.9-.83 1.48-1.73 1.73c-.47.13-1.33.22-2.65.28c-1.3.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44c-.9-.25-1.48-.83-1.73-1.73c-.13-.47-.22-1.1-.28-1.9c-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83c.25-.9.83-1.48 1.73-1.73c.47-.13 1.33-.22 2.65-.28c1.3-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44c.9.25 1.48.83 1.73 1.73"/></svg>
                            <span>Video MV</span>
                        </button>
                    </div>
                </div>

                <!-- Progress Bar Inside 3D Modal -->
                <div class="mt-3 pt-3 border-t border-[#2d2f33] flex items-center space-x-2.5 text-xs text-[#9aa1b1] select-none">
                    <span id="vinylCurrentTime" class="w-10 text-right font-mono font-semibold text-[#f0f2f5]">0:00</span>
                    <div class="relative w-full flex items-center py-1 cursor-pointer">
                        <input type="range" id="vinylProgressBar" min="0" max="100" value="0" step="0.1" class="player-seek-slider" title="Geser untuk memilih menit lagu">
                    </div>
                    <span id="vinylDuration" class="w-10 text-left font-mono font-semibold text-[#9aa1b1]">3:30</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Synced Lyrics Modal (Spotify / YT Music Style) -->
    <div id="lyricsModal" class="modal">
        <div class="ex-modal-card bg-[#2e3035] border border-[#2d2f33] rounded-[16px] max-w-2xl w-full mx-4 p-6 relative overflow-hidden flex flex-col max-h-[88vh]">
            <!-- Modal Header -->
            <div class="flex justify-between items-center mb-4 border-b border-[#2d2f33] pb-3 flex-shrink-0">
                <div class="flex items-center">
                    <h3 class="text-[20px] font-extrabold text-[#f0f2f5]">Lirik</h3>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="closeLyricsModal()" class="text-[#9aa1b1] hover:text-white text-2xl transition-colors p-1 leading-none" title="Tutup">&times;</button>
                </div>
            </div>

            <!-- Scrollable Synced Lyrics Viewport -->
            <div id="lyricsScrollContainer" class="relative flex-1 min-h-[290px] max-h-[380px] overflow-y-auto rounded-[16px] bg-[#141415] border border-[#2d2f33] px-6 py-10 space-y-5 text-center select-none scroll-smooth">
                <div class="py-16 text-[#9aa1b1] text-sm">Pilih lagu untuk menampilkan lirik tersinkronisasi...</div>
            </div>

            <!-- Interactive Player Controls Inside Lyrics Modal -->
            <div class="mt-4 bg-[#141415] rounded-[16px] p-4 border border-[#2d2f33] flex-shrink-0">
                <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3 sm:gap-4">
                    <!-- Current Song Info -->
                    <div class="flex items-center space-x-3 min-w-0 w-full justify-center sm:justify-start text-left">
                        <img id="lyricsModalCover" src="{{ asset('image/queen1.jpg') }}" class="w-12 h-12 rounded-[10px] object-cover border border-[#2d2f33] flex-shrink-0">
                        <div class="min-w-0 truncate">
                            <h4 id="lyricsSongTitle" class="text-[14px] font-semibold text-[#f0f2f5] truncate">Bohemian Rhapsody</h4>
                            <p id="lyricsSongArtist" class="text-[12px] font-semibold text-[#9aa1b1] truncate">Queen</p>
                        </div>
                    </div>

                    <!-- Center Play/Pause & Prev/Next Controls -->
                    <div class="flex items-center justify-center space-x-3 flex-shrink-0">
                        <button onclick="playPrevSong()" class="w-9 h-9 rounded-full bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] flex items-center justify-center transition-transform hover:scale-105" title="Lagu Sebelumnya">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>
                        </button>
                        <button onclick="togglePlayAudio()" class="w-12 h-12 p-0 leading-none bg-[#00b24b] hover:bg-[#00c853] text-white rounded-full flex items-center justify-center font-bold transition-transform hover:scale-105" title="Play / Pause Lagu">
                            <svg id="lyricsModalPlayIcon" class="w-6 h-6 fill-current block m-auto" viewBox="0 0 24 24"><path d="M7.5 5.5v13l10-6.5z"/></svg>
                            <svg id="lyricsModalPauseIcon" class="w-6 h-6 fill-current hidden m-auto" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                        </button>
                        <button onclick="playNextSong()" class="w-9 h-9 rounded-full bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] flex items-center justify-center transition-transform hover:scale-105" title="Lagu Selanjutnya">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>
                        </button>
                    </div>

                    <!-- Switch to 3D Vinyl & Video MV shortcuts -->
                    <div class="flex items-center justify-center sm:justify-end gap-2 flex-shrink-0">
                        <button onclick="openVinylModal()" class="h-9 px-3 rounded-[10px] bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] text-[13px] font-semibold border border-[#2d2f33] transition-colors inline-flex items-center gap-1.5 whitespace-nowrap" title="Beralih ke 3D Vinyl tanpa menghentikan lagu">
                            <svg class="w-4 h-4 text-[#00b24b] flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M16 3.937A9 9 0 1 0 21 12"/><path d="M11 12a1 1 0 1 0 2 0a1 1 0 1 0-2 0m8-8a1 1 0 1 0 2 0a1 1 0 1 0-2 0"/><path d="m20 4l-3.5 10l-2.5 2"/></g></svg>
                            <span>3D Vinyl</span>
                        </button>
                        <button onclick="openVideoModalForCurrentSong()" class="h-9 px-3 rounded-[10px] bg-[#2e3035] hover:bg-red-600 text-[#f0f2f5] hover:text-white text-[13px] font-semibold border border-[#2d2f33] transition-colors inline-flex items-center gap-1.5 whitespace-nowrap group" title="Tonton Video Musik (MV)">
                            <svg class="w-4 h-4 text-red-500 group-hover:text-white fill-current flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="m10 15l5.19-3L10 9zm11.56-7.83c.13.47.22 1.1.28 1.9c.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83c-.25.9-.83 1.48-1.73 1.73c-.47.13-1.33.22-2.65.28c-1.3.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44c-.9-.25-1.48-.83-1.73-1.73c-.13-.47-.22-1.1-.28-1.9c-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83c.25-.9.83-1.48 1.73-1.73c.47-.13 1.33-.22 2.65-.28c1.3-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44c.9.25 1.48.83 1.73 1.73"/></svg>
                            <span>Video MV</span>
                        </button>
                    </div>
                </div>

                <!-- Progress Bar Inside Lyrics Modal -->
                <div class="mt-3 pt-3 border-t border-[#2d2f33] flex items-center space-x-2.5 text-xs text-[#9aa1b1] select-none">
                    <span id="lyricsCurrentTime" class="w-10 text-right font-mono font-semibold text-[#f0f2f5]">0:00</span>
                    <div class="relative w-full flex items-center py-1 cursor-pointer">
                        <input type="range" id="lyricsProgressBar" min="0" max="100" value="0" step="0.1" class="player-seek-slider" title="Geser untuk memilih menit lagu">
                    </div>
                    <span id="lyricsDuration" class="w-10 text-left font-mono font-semibold text-[#9aa1b1]">3:30</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Dedicated Video MV Modal (YouTube Player HD) -->
    <div id="videoModal" class="modal">
        <div class="ex-modal-card bg-[#2e3035] border border-[#2d2f33] rounded-[16px] max-w-4xl w-full mx-4 p-5 sm:p-6 relative overflow-hidden flex flex-col max-h-[92vh]">
            <!-- Modal Header -->
            <div class="flex justify-between items-center mb-4 border-b border-[#2d2f33] pb-3 flex-shrink-0">
                <div class="flex items-center space-x-2.5">
                    <svg class="w-6 h-6 text-red-500 fill-current flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>youtube</title><path fill="currentColor" d="m10 15l5.19-3L10 9zm11.56-7.83c.13.47.22 1.1.28 1.9c.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83c-.25.9-.83 1.48-1.73 1.73c-.47.13-1.33.22-2.65.28c-1.3.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44c-.9-.25-1.48-.83-1.73-1.73c-.13-.47-.22-1.1-.28-1.9c-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83c.25-.9.83-1.48 1.73-1.73c.47-.13 1.33-.22 2.65-.28c1.3-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44c.9.25 1.48.83 1.73 1.73"/></svg>
                    <h3 class="text-[20px] font-extrabold text-[#f0f2f5] flex items-center gap-2">
                        <span id="videoModalTitleHeader">Video Musik Resmi</span>
                        <span id="videoModalModeBadge" class="bg-[#0075ff] text-white text-[11px] font-semibold px-2.5 py-0.5 rounded-full">
                            YouTube Player HD
                        </span>
                    </h3>
                </div>
                <div class="flex items-center gap-2">
                    <button id="videoToggleModeBtn" onclick="toggleVideoVisualizerMode()" class="h-8 px-3 rounded-[10px] bg-[#141415] hover:bg-[#383b42] text-[13px] font-semibold text-[#f0f2f5] border border-[#2d2f33] flex items-center gap-1.5 transition-colors" title="Ganti antara Video dan Visualizer">
                        <svg class="w-3.5 h-3.5 text-[#00b24b]" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
                        <span id="videoToggleModeText">Mode Visualizer</span>
                    </button>
                    <button onclick="closeVideoModal()" class="text-[#9aa1b1] hover:text-white text-2xl transition-colors p-1 leading-none" title="Tutup">&times;</button>
                </div>
            </div>

            <!-- Video Player Iframe & Visualizer Container (16:9 Responsive) -->
            <div class="relative w-full aspect-video rounded-[16px] overflow-hidden bg-black border border-[#2d2f33] flex-shrink-0">
                <!-- 1. Video Iframe Layer with Loading Shield -->
                <div id="videoIframeWrapper" class="w-full h-full relative flex items-center justify-center">
                    <div id="videoLoadingCurtain" class="absolute inset-0 bg-[#141415] flex flex-col items-center justify-center z-10 transition-opacity duration-300">
                        <div class="w-8 h-8 border-2 border-red-500 border-t-transparent rounded-full animate-spin mb-2"></div>
                        <p class="text-xs text-[#9aa1b1] font-semibold">Menyiapkan video musik...</p>
                    </div>
                    <div id="mainVideoMount" class="w-full h-full"></div>
                </div>

                <!-- 2. Audiovisualizer Fallback Layer -->
                <div id="videoVisualizerFallback" onmousemove="handleVisualizerActivity()" onclick="handleVisualizerActivity()" class="hidden absolute inset-0 w-full h-full flex flex-col justify-between p-5 sm:p-6 select-none overflow-hidden z-20">
                    <!-- Dynamic Backdrop Image with blur & vignette -->
                    <div id="visualizerBackdrop" class="absolute inset-0 bg-cover bg-center transition-all duration-700 transform scale-105"></div>
                    <div class="absolute inset-0 bg-[#141415]/80"></div>

                    <!-- Center Artwork & Title -->
                    <div class="relative z-10 flex flex-col items-center justify-center text-center my-auto">
                        <div class="relative group mb-3 cursor-pointer select-none" onclick="toggleVisualizerPlayback()" title="Klik untuk Play / Pause">
                            <img id="visualizerCenterImg" src="{{ asset('image/queen1.jpg') }}" class="w-24 h-24 sm:w-28 sm:h-28 rounded-[16px] object-cover border border-[#2d2f33] transition-transform group-hover:scale-105">
                            <button id="visPlayPauseBtn" onclick="event.stopPropagation(); toggleVisualizerPlayback()" class="absolute inset-0 m-auto w-11 h-11 rounded-full bg-[#00b24b] hover:bg-[#00c853] text-white flex items-center justify-center" title="Play / Pause Audio">
                                <svg id="visPlayIcon" class="w-5 h-5 fill-current ml-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                <svg id="visPauseIcon" class="w-5 h-5 fill-current hidden" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                            </button>
                        </div>
                        <h3 id="visualizerTitle" class="text-lg sm:text-2xl font-extrabold text-[#f0f2f5] truncate max-w-md">Blinding Lights</h3>
                        <p id="visualizerArtist" class="text-xs sm:text-sm text-[#00b24b] font-semibold truncate max-w-md mt-0.5">The Weeknd</p>
                    </div>

                    <!-- Bottom Equalizer Bars & Seeker -->
                    <div class="relative z-10">
                        <div id="visEqualizerContainer" class="flex items-end justify-center gap-1 sm:gap-1.5 h-8 sm:h-9 mb-2">
                            <!-- 16 animated visualizer bars -->
                            <div class="visualizer-bar" style="animation-duration: 0.8s; animation-delay: 0.1s;"></div>
                            <div class="visualizer-bar" style="animation-duration: 1.1s; animation-delay: 0.2s;"></div>
                            <div class="visualizer-bar" style="animation-duration: 0.9s; animation-delay: 0.3s;"></div>
                            <div class="visualizer-bar" style="animation-duration: 1.3s; animation-delay: 0.15s;"></div>
                            <div class="visualizer-bar" style="animation-duration: 0.7s; animation-delay: 0.25s;"></div>
                            <div class="visualizer-bar" style="animation-duration: 1.2s; animation-delay: 0.05s;"></div>
                            <div class="visualizer-bar" style="animation-duration: 1.0s; animation-delay: 0.35s;"></div>
                            <div class="visualizer-bar" style="animation-duration: 0.85s; animation-delay: 0.18s;"></div>
                            <div class="visualizer-bar" style="animation-duration: 1.25s; animation-delay: 0.22s;"></div>
                            <div class="visualizer-bar" style="animation-duration: 0.95s; animation-delay: 0.08s;"></div>
                            <div class="visualizer-bar" style="animation-duration: 1.15s; animation-delay: 0.3s;"></div>
                            <div class="visualizer-bar" style="animation-duration: 0.75s; animation-delay: 0.12s;"></div>
                            <div class="visualizer-bar" style="animation-duration: 1.35s; animation-delay: 0.28s;"></div>
                            <div class="visualizer-bar" style="animation-duration: 0.9s; animation-delay: 0.16s;"></div>
                            <div class="visualizer-bar" style="animation-duration: 1.05s; animation-delay: 0.24s;"></div>
                            <div class="visualizer-bar" style="animation-duration: 0.8s; animation-delay: 0.32s;"></div>
                        </div>

                        <!-- Progress Bar Inside Visualizer -->
                        <div class="flex items-center space-x-2 text-xs text-slate-300 font-mono select-none">
                            <span id="visCurrentTime" class="w-10 text-right">0:00</span>
                            <div class="relative w-full flex items-center py-1">
                                <input type="range" id="visProgressBar" min="0" max="100" value="0" step="0.1" class="player-seek-slider" oninput="seekVisualizerAudio(this.value)">
                            </div>
                            <span id="visDuration" class="w-10 text-left">3:20</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Video Info & Multi-Pillar Controls -->
            <div class="mt-4 bg-[#141415] rounded-[16px] p-4 border border-[#2d2f33] flex-shrink-0">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center space-x-3 text-center sm:text-left truncate max-w-xs w-full sm:w-auto">
                        <img id="videoModalCover" src="{{ asset('image/queen1.jpg') }}" class="w-12 h-12 rounded-[10px] object-cover border border-[#2d2f33] flex-shrink-0">
                        <div class="truncate">
                            <h4 id="videoModalTitle" class="text-[14px] font-semibold text-[#f0f2f5] truncate">Bohemian Rhapsody</h4>
                            <p id="videoModalArtist" class="text-[12px] font-semibold text-[#9aa1b1] truncate">Queen</p>
                        </div>
                    </div>

                    <div class="flex items-center flex-wrap justify-center sm:justify-end gap-2">
                        <button id="videoBtnSwitch3D" onclick="switchFromVideoTo3D()" class="h-9 px-3 rounded-[10px] bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] text-[13px] font-semibold border border-[#2d2f33] transition-colors inline-flex items-center gap-1.5 whitespace-nowrap" title="Beralih ke Piringan Hitam 3D">
                            <svg class="w-4 h-4 text-[#00b24b] flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M16 3.937A9 9 0 1 0 21 12"/><path d="M11 12a1 1 0 1 0 2 0a1 1 0 1 0-2 0m8-8a1 1 0 1 0 2 0a1 1 0 1 0-2 0"/><path d="m20 4l-3.5 10l-2.5 2"/></g></svg>
                            <span>Piringan 3D</span>
                        </button>
                        <button id="videoBtnSwitchLyrics" onclick="switchFromVideoToLyrics()" class="h-9 px-3 rounded-[10px] bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] text-[13px] font-semibold border border-[#2d2f33] transition-colors inline-flex items-center gap-1.5 whitespace-nowrap" title="Buka Lirik Lagu">
                            <svg class="w-4 h-4 text-[#0075ff] flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M470.8 365.6c-.9-53.7-1.6-107.4-2.4-161.1c-.9-63.6-1.7-127.3-2.6-190.9c-.1-9.9-6.6-15.2-15.9-13.1c-25.8 5.9-51.7 11.7-77.5 17.5C306.7 32.7 241 47.5 175.3 62.2c-9.1 2-12.2 6.1-12.1 15.5c.2 7.5.3 14.9.4 22.4c1.3 90.7 2.5 181.4 3.7 272.1v9.8c-2.5-1.9-4.4-3.5-6.4-5c-17-12.6-36.1-17.4-56.9-14c-51.5 8.5-78.6 64.8-53.4 110.5c15.3 27.7 48.4 43.4 78.8 37.3c38-7.6 62.5-38.6 61.7-77.8c-.4-20.2-.8-40.4-1.1-60.7l-3-224.1c-.1-4.6 0-9.2 0-14c85.6-19.2 171.1-38.4 256.8-57.6c1.1 81.6 2.2 162.6 3.3 244.4c-1.7-1.3-2.6-2.1-3.6-2.9c-17.3-13.9-37.1-19.3-58.9-16c-54.2 8.2-83.1 71.4-49.5 117.3c17.9 24.4 42.6 35.1 72.6 30.4c30.3-4.8 50.1-23 59.9-52c3.6-10.6 3.4-21.4 3.2-32.2M115.6 489.4c-28.6.1-51.7-23.7-51.6-52.8s23.6-52.6 53-52.2c27.9.4 51.4 22.4 51.2 53.2c-.2 29.6-23.7 51.8-52.6 51.8m327.7-438c0 .9-2.1 2.3-3.4 2.6c-12.5 3-25 5.8-37.5 8.6c-20 4.5-40.1 8.9-60.1 13.4c-41.4 9.3-82.8 18.5-124.3 27.8c-9.6 2.2-19.3 4.3-28.9 6.5c-.6.1-1.2.1-3 .3c0-8.8-.1-17.4.2-25.9c0-.9 2.2-2.2 3.5-2.5c14.7-3.4 29.5-6.7 44.3-10c19.8-4.4 39.5-8.8 59.3-13.3l113.7-25.5c10.6-2.4 21.2-4.9 31.9-7.3c1.1-.2 2.2-.2 4.4-.4c.1 8.9.2 17.3-.1 25.7m-47.4 377c-28.1 0-52-24.1-52-52.4c0-28.6 23.8-52.7 52.1-52.7c28.9 0 52.3 23.7 52.3 53.1c-.2 28.3-24 52-52.4 52"/><path fill="currentColor" d="M64 436.6c.1-29.1 23.6-52.6 53-52.2c27.9.4 51.4 22.4 51.2 53.2c-.2 29.6-23.7 51.8-52.6 51.8c-28.6.1-51.7-23.7-51.6-52.8m384.2-60.2c0-29.4-23.3-53.1-52.3-53.1c-28.3 0-52.1 24.1-52.1 52.7c0 28.2 24 52.4 52 52.4c28.5 0 52.3-23.7 52.4-52m-259-266c9.6-2.2 19.3-4.3 28.9-6.5c41.4-9.3 82.8-18.6 124.3-27.8c20-4.5 40.1-8.9 60.1-13.4c12.5-2.8 25-5.6 37.5-8.6c1.3-.3 3.4-1.7 3.4-2.6c.3-8.4.2-16.9.2-25.8c-2.2.2-3.3.1-4.4.4c-10.6 2.4-21.2 4.9-31.9 7.3L293.6 58.9c-19.8 4.4-39.5 8.8-59.3 13.3c-14.8 3.3-29.5 6.6-44.3 10c-1.4.3-3.5 1.6-3.5 2.5c-.3 8.6-.2 17.1-.2 25.9c1.7-.1 2.3-.1 2.9-.2" opacity=".7"/></svg>
                            <span>Lirik</span>
                        </button>
                        <a id="videoModalYtLink" href="#" target="_blank" rel="noopener noreferrer" class="h-9 px-3 rounded-[10px] bg-red-600 hover:bg-red-500 text-white text-[13px] font-semibold transition-colors inline-flex items-center gap-1.5 whitespace-nowrap" title="Buka langsung di YouTube">
                            <svg class="w-4 h-4 text-white fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>youtube</title><path fill="currentColor" d="m10 15l5.19-3L10 9zm11.56-7.83c.13.47.22 1.1.28 1.9c.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83c-.25.9-.83 1.48-1.73 1.73c-.47.13-1.33.22-2.65.28c-1.3.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44c-.9-.25-1.48-.83-1.73-1.73c-.13-.47-.22-1.1-.28-1.9c-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83c.25-.9.83-1.48 1.73-1.73c.47-.13 1.33-.22 2.65-.28c1.3-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44c.9.25 1.48.83 1.73 1.73"/></svg>
                            <span>Tonton di YouTube</span>
                            <svg class="w-3.5 h-3.5 text-white ml-0.5 inline flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Helpful Hint -->
                <div class="mt-2.5 pt-2 border-t border-[#2d2f33] flex items-center justify-between text-[11px] text-[#9aa1b1]">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-[#53575f] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Jika pemutaran video dibatasi oleh label hak cipta lagu, gunakan tombol <strong class="text-[#f0f2f5]">Tonton di YouTube</strong> atau beralih ke <strong class="text-[#0075ff]">Mode Visualizer</strong>.</span>
                    </span>
                    <span class="text-[#53575f] hidden sm:inline font-mono">YouTube Official</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Full-Screen Split-Panel Authentication Page (design.md Canvas & Two-Signal Action System) -->
    <div id="authModal" class="modal" onclick="if(event.target === this) closeAuthModal()">
        <div class="h-screen w-screen bg-[#141415] flex overflow-hidden">
            <!-- Left Panel - Image Section -->
            <div class="hidden lg:flex flex-1 relative overflow-hidden bg-[#141415]">
                <!-- Back Button -->
                <div class="absolute top-6 left-6 z-20">
                    <button type="button" onclick="closeAuthModal()" class="w-10 h-10 bg-[#141415]/85 hover:bg-[#2e3035] border border-[#2d2f33] rounded-full flex items-center justify-center text-[#f0f2f5] transition-colors" title="Kembali ke Beranda">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>
                        </svg>
                    </button>
                </div>

                <!-- Full HD Background Image + Lapisan Gelap Netral Halus -->
                <div class="absolute inset-0">
                    <img src="{{ asset('image/login-bg-hd.jpg') }}?v={{ filemtime(public_path('image/login-bg-hd.jpg')) }}" alt="Minima Audio Visual" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-[#141415]/60"></div>
                </div>

                <!-- Centered Headline & Tagline -->
                <div class="relative z-10 flex flex-col items-center justify-center text-center px-12 w-full h-full select-none">
                    <div class="max-w-md">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-[#0075ff] text-white mb-5">
                            <svg class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                                <rect x="3" y="8" width="4" height="8" rx="1" class="opacity-75"></rect>
                                <rect x="9" y="4" width="6" height="16" rx="2"></rect>
                                <rect x="17" y="8" width="4" height="8" rx="1" class="opacity-75"></rect>
                            </svg>
                            <span class="text-[11px] font-semibold text-white tracking-wider uppercase">Minima Interactive Audio</span>
                        </div>
                        <h2 class="text-4xl xl:text-5xl font-extrabold text-[#f0f2f5] leading-tight tracking-tight drop-shadow-[0_4px_24px_rgba(0,0,0,0.65)] mb-4">
                            Rasakan Musik Secara <span class="text-[#00b24b]">Lebih Hidup.</span>
                        </h2>
                        <p class="text-[#f0f2f5]/90 text-sm leading-relaxed font-semibold drop-shadow-[0_2px_10px_rgba(0,0,0,0.6)]">
                            Eksplorasi 200+ komposisi musik lintas era dengan Piringan Hitam 3D interaktif, lirik sinkronisasi waktu nyata, dan koleksi playlist pribadi Anda.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right Panel - Form Section (design.md canvas #141415 + #2d2f33) -->
            <div class="flex-1 flex flex-col justify-center items-center bg-[#141415] border-l border-[#2d2f33] px-6 py-10 overflow-y-auto relative">
                <!-- Mobile / Tablet Back Button & Top-Right Close Button -->
                <div class="w-full max-w-md flex items-center justify-between lg:justify-end mb-4">
                    <button type="button" onclick="closeAuthModal()" class="lg:hidden inline-flex items-center gap-2 px-3 py-2 rounded-[10px] bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] text-[13px] font-semibold border border-[#2d2f33] transition-colors">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>
                        </svg>
                        <span>Kembali</span>
                    </button>
                    <button type="button" onclick="closeAuthModal()" class="w-9 h-9 rounded-full bg-[#2e3035] hover:bg-[#383b42] text-[#9aa1b1] hover:text-white border border-[#2d2f33] flex items-center justify-center transition-colors" title="Tutup (Esc)">
                        ✕
                    </button>
                </div>

                <div class="w-full max-w-md p-2 sm:p-4">
                    <!-- Dynamic Heading & Switch Link -->
                    <div class="mb-7">
                        <div class="inline-flex items-center gap-2 mb-3">
                            <svg class="h-7 w-7 text-[#00b24b]" viewBox="0 0 24 24" fill="currentColor">
                                <rect x="3" y="8" width="4" height="8" rx="1" class="opacity-70"></rect>
                                <rect x="9" y="4" width="6" height="16" rx="2"></rect>
                                <rect x="17" y="8" width="4" height="8" rx="1" class="opacity-70"></rect>
                            </svg>
                            <span class="text-lg font-extrabold tracking-wide text-[#f0f2f5]">Minima</span>
                        </div>
                        <h1 id="authModalHeading" class="text-[33px] leading-[1.1] font-extrabold text-[#f0f2f5] mb-2">
                            Welcome Back
                        </h1>
                        <p id="authModalSubheading" class="text-[14px] font-semibold text-[#9aa1b1]">
                            Belum punya akun?
                            <button type="button" onclick="switchAuthTab('register')" class="text-[#0075ff] hover:underline font-semibold ml-1">
                                Daftar akun (Sign up)
                            </button>
                        </p>
                    </div>

                    <!-- Error / Info Banner -->
                    <div id="authErrorBanner" class="hidden mb-5 px-4 py-3 rounded-xl bg-red-500/15 border border-red-500/40 text-red-300 text-xs font-medium"></div>

                    <!-- FORM 1: SIGN IN (MASUK) -->
                    <form id="authLoginForm" onsubmit="handleLoginSubmit(event)" class="space-y-5">
                        <!-- Username / Email -->
                        <div>
                            <label class="block text-[13px] font-semibold text-[#f0f2f5] mb-1.5">
                                Username atau Alamat Email
                            </label>
                            <input
                                type="text"
                                id="loginIdentifier"
                                required
                                placeholder="Masukkan username atau email Anda"
                                class="w-full px-3 py-2.5 bg-[#2e3035] text-[#f0f2f5] placeholder-[#53575f] border border-[#2d2f33] rounded-[8px] focus:border-[#0075ff] outline-none text-[14px] font-semibold transition-all"
                            />
                        </div>

                        <!-- Password with Eye / EyeOff SVG Icon -->
                        <div>
                            <label class="block text-[13px] font-semibold text-[#f0f2f5] mb-1.5">
                                Password
                            </label>
                            <div class="relative">
                                <input
                                    type="password"
                                    id="loginPassword"
                                    required
                                    placeholder="Masukkan password"
                                    class="w-full px-3 py-2.5 pr-11 bg-[#2e3035] text-[#f0f2f5] placeholder-[#53575f] border border-[#2d2f33] rounded-[8px] focus:border-[#0075ff] outline-none text-[14px] font-semibold transition-all"
                                />
                                <button
                                    type="button"
                                    onclick="toggleAuthPasswordVisibility('loginPassword', 'loginEyeIcon', 'loginEyeOffIcon')"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1.5 text-[#9aa1b1] hover:text-white hover:bg-[#383b42] rounded-full transition-colors"
                                    title="Tampilkan / Sembunyikan Password"
                                >
                                    <!-- Lucide Eye Icon -->
                                    <svg id="loginEyeIcon" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <!-- Lucide EyeOff Icon -->
                                    <svg id="loginEyeOffIcon" class="w-4 h-4 hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Remember Me + Forgot Password -->
                        <div class="flex items-center justify-between">
                            <label class="flex items-center space-x-2 text-[13px] font-semibold text-[#9aa1b1] cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    checked
                                    class="w-4 h-4 accent-[#00b24b] bg-[#2e3035] border-[#2d2f33] rounded-[4px] cursor-pointer"
                                />
                                <span>Ingat saya</span>
                            </label>
                            <button
                                type="button"
                                onclick="switchAuthTab('register')"
                                class="text-[13px] text-[#0075ff] hover:underline font-semibold"
                            >
                                Buat akun baru?
                            </button>
                        </div>

                        <!-- Submit Button (design.md button-primary: #00b24b + #ffffff + 10px rounded) -->
                        <button
                            type="submit"
                            id="authLoginSubmitBtn"
                            class="w-full bg-[#00b24b] hover:bg-[#00c853] text-white py-2.5 px-3 rounded-[10px] font-semibold text-[14px] leading-[1.4] transition-all"
                        >
                            Sign In (Masuk)
                        </button>

                        <!-- Divider -->
                        <div class="relative my-6">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-[#2d2f33]"></div>
                            </div>
                            <div class="relative flex justify-center text-xs uppercase tracking-wider">
                                <span class="px-3 bg-[#141415] text-[#53575f] font-semibold">atau</span>
                            </div>
                        </div>

                        <!-- Social / Quick Access Buttons (design.md button-tertiary: #2e3035 + #2d2f33 + 12px rounded) -->
                        <div class="grid grid-cols-2 gap-3">
                            <button
                                type="button"
                                onclick="switchAuthTab('register'); document.getElementById('registerUsername')?.focus();"
                                class="flex items-center justify-center px-3 py-2.5 border border-[#2d2f33] bg-[#2e3035] hover:bg-[#383b42] rounded-[12px] transition-all"
                            >
                                <svg class="w-4 h-4 mr-2 flex-shrink-0" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                                <span class="text-[13px] font-semibold text-[#f0f2f5]">
                                    Google
                                </span>
                            </button>

                            <a
                                href="https://github.com/Benaya-Bagas-Christianto/minima-interactive-audio"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center justify-center px-3 py-2.5 border border-[#2d2f33] bg-[#2e3035] hover:bg-[#383b42] rounded-[12px] transition-all"
                            >
                                <svg class="w-4 h-4 mr-2 flex-shrink-0 text-white fill-current" viewBox="0 0 24 24">
                                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                                </svg>
                                <span class="text-[13px] font-semibold text-[#f0f2f5]">
                                    GitHub
                                </span>
                            </a>
                        </div>
                    </form>

                    <!-- FORM 2: SIGN UP (DAFTAR AKUN BARU) -->
                    <form id="authRegisterForm" onsubmit="handleRegisterSubmit(event)" class="space-y-5 hidden">
                        <div>
                            <label class="block text-[13px] font-semibold text-[#f0f2f5] mb-1.5">
                                Username / Nama Tampilan
                            </label>
                            <input
                                type="text"
                                id="registerUsername"
                                required
                                minlength="3"
                                placeholder="Masukkan username atau nama tampilan"
                                class="w-full px-3 py-2.5 bg-[#2e3035] text-[#f0f2f5] placeholder-[#53575f] border border-[#2d2f33] rounded-[8px] focus:border-[#0075ff] outline-none text-[14px] font-semibold transition-all"
                            />
                        </div>
                        <div>
                            <label class="block text-[13px] font-semibold text-[#f0f2f5] mb-1.5">
                                Alamat Email
                            </label>
                            <input
                                type="email"
                                id="registerEmail"
                                required
                                placeholder="Contoh: nama@email.com"
                                class="w-full px-3 py-2.5 bg-[#2e3035] text-[#f0f2f5] placeholder-[#53575f] border border-[#2d2f33] rounded-[8px] focus:border-[#0075ff] outline-none text-[14px] font-semibold transition-all"
                            />
                        </div>
                        <div>
                            <label class="block text-[13px] font-semibold text-[#f0f2f5] mb-1.5">
                                Password (Min. 6 Karakter)
                            </label>
                            <div class="relative">
                                <input
                                    type="password"
                                    id="registerPassword"
                                    required
                                    minlength="6"
                                    placeholder="Buat password akun..."
                                    class="w-full px-3 py-2.5 pr-11 bg-[#2e3035] text-[#f0f2f5] placeholder-[#53575f] border border-[#2d2f33] rounded-[8px] focus:border-[#0075ff] outline-none text-[14px] font-semibold transition-all"
                                />
                                <button
                                    type="button"
                                    onclick="toggleAuthPasswordVisibility('registerPassword', 'registerEyeIcon', 'registerEyeOffIcon')"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1.5 text-[#9aa1b1] hover:text-white hover:bg-[#383b42] rounded-full transition-colors"
                                    title="Tampilkan / Sembunyikan Password"
                                >
                                    <svg id="registerEyeIcon" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <svg id="registerEyeOffIcon" class="w-4 h-4 hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <button
                            type="submit"
                            id="authRegisterSubmitBtn"
                            class="w-full bg-[#00b24b] hover:bg-[#00c853] text-white py-2.5 px-3 rounded-[10px] font-semibold text-[14px] leading-[1.4] transition-all"
                        >
                            Sign Up (Buat Akun & Masuk)
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        window.currentUser = @json(Auth::user());
    </script>
    <script src="{{ asset('js/minima-app.js') }}?v={{ filemtime(public_path('js/minima-app.js')) }}"></script>
</body>
</html>