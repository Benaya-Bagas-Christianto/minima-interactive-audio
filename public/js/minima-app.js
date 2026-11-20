/**
 * Minima Multimedia App - Three.js 3D Vinyl, Audio Player, Video MV & Full MVC Integration
 */

// Global State
let currentSong = null;

let currentFilter = {
    genre: 'All',
    decade: 'all',
    keyword: '',
    sort: 'rating'
};

let isAudioPlaying = false;
let userRatingInput = 0;
let editRatingInput = 5;
let activePlaylistId = null;
let currentSongQueue = [];

// Three.js 3D Scene Variables
let threeScene, threeCamera, threeRenderer;
let turntableGroup, vinylMesh, tonearmMesh;
let isDragging3D = false;
let previousMousePosition = { x: 0, y: 0 };
let vinylRotationSpeed = 0.025;
let turntableYaw = 0.2;
let turntablePitch = 0.32;

// Audio Context & YouTube Player System (Full-Duration)
let ytAudioPlayer = null;
let isYtReady = false;
let pendingYtVideoId = null;
let ytProgressTimer = null;
let currentPlaybackMode = 'youtube'; // 'youtube' | 'html5'

let audioCtx = null;
let synthOscillator = null;

document.addEventListener('DOMContentLoaded', () => {
    initNavigation();
    initAudioPlayer();
    initYouTubeAudio();
    initThreeJSVinyl();
    initSearchAutocomplete();
    initModalBackdropEvents();
    fetchMusics();
    loadPlaylists();
    loadReviews();
});

/* ==================== MODAL SCROLL LOCK & BACKDROP ==================== */
function isModalTrulyOpen(modal) {
    return Boolean(modal && modal.classList.contains('active') && !modal.classList.contains('modal-closing'));
}

function lockBodyScroll() {
    document.body.classList.add('modal-open');
}

function unlockBodyScroll() {
    const openModals = document.querySelectorAll('.modal.active');
    if (openModals.length === 0) {
        document.body.classList.remove('modal-open');
    }
}

function initModalBackdropEvents() {
    // Tutup modal ketika klik di area luar dialog (backdrop gelap) dengan animasi keluar halus
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                if (modal.id === 'musicModal') closeMusicModal();
                else if (modal.id === 'vinylModal') closeVinylModal();
                else if (modal.id === 'lyricsModal') closeLyricsModal();
                else if (modal.id === 'videoModal') closeVideoModal();
                else {
                    if (modal.classList.contains('modal-closing')) return;
                    modal.classList.add('modal-closing');
                    setTimeout(() => {
                        modal.classList.remove('active', 'modal-closing');
                        unlockBodyScroll();
                    }, 220);
                }
            }
        });
    });

    // Tutup modal jika menekan tombol Escape dengan animasi penutup halus
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const activeModals = document.querySelectorAll('.modal.active');
            activeModals.forEach(m => {
                if (m.id === 'musicModal') closeMusicModal();
                else if (m.id === 'vinylModal') closeVinylModal();
                else if (m.id === 'lyricsModal') closeLyricsModal();
                else if (m.id === 'videoModal') closeVideoModal();
                else {
                    if (m.classList.contains('modal-closing')) return;
                    m.classList.add('modal-closing');
                    setTimeout(() => {
                        m.classList.remove('active', 'modal-closing');
                        unlockBodyScroll();
                    }, 220);
                }
            });
        }
    });
}

/* ==================== 1. NAVIGATION & TABS ==================== */
function updateNavSlider(btn) {
    const slider = document.getElementById('nav-slider');
    if (!slider || !btn) return;
    const inset = 10;
    const barLeft = btn.offsetLeft + inset;
    const barWidth = Math.max(20, btn.offsetWidth - (inset * 2));
    slider.style.opacity = '1';
    slider.style.width = `${barWidth}px`;
    slider.style.transform = `translate3d(${barLeft}px, 0, 0)`;
}

function initNavigation() {
    const activeBtn = document.querySelector('.nav-btn.active') || document.querySelector('.nav-btn');
    if (activeBtn) {
        updateNavSlider(activeBtn);
        // Pastikan ulang setelah font selesai dimuat agar ukuran 100% presisi
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(() => {
                const currentActive = document.querySelector('.nav-btn.active') || activeBtn;
                updateNavSlider(currentActive);
            });
        }
    }

    window.addEventListener('resize', () => {
        const currentActive = document.querySelector('.nav-btn.active');
        if (currentActive) updateNavSlider(currentActive);
    });
}

let sectionTransitionTimer = null;

function showSection(sectionId, clickedBtn) {
    const target = document.getElementById(sectionId);
    if (!target) return;

    const current = document.querySelector('.section.active');

    // Update navbar buttons & slider immediately for instant, springy visual response
    const allBtns = document.querySelectorAll('.nav-btn');
    allBtns.forEach(btn => btn.classList.remove('active', 'text-white'));

    // Fallback: Jika clickedBtn tidak dipassing, temukan tombol yang sesuai
    if (!clickedBtn && allBtns.length > 0) {
        if (sectionId === 'home') clickedBtn = allBtns[0];
        else if (sectionId === 'discover') clickedBtn = allBtns[1];
        else if (sectionId === 'playlists' || sectionId === 'playlist-view') clickedBtn = allBtns[2];
        else if (sectionId === 'reviews') clickedBtn = allBtns[3];
        else if (sectionId === 'about') clickedBtn = document.getElementById('btn-about') || allBtns[4];
    }

    if (clickedBtn) {
        clickedBtn.classList.add('active', 'text-white');
        updateNavSlider(clickedBtn);
    }

    // Jika sudah di section yang sama, cukup scroll ke atas dengan mulus
    if (current && current.id === sectionId) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

    // Bersihkan timer transisi sebelumnya jika user mengklik cepat
    if (sectionTransitionTimer) {
        clearTimeout(sectionTransitionTimer);
        sectionTransitionTimer = null;
    }

    if (current) {
        // 1. Animasi keluar untuk section aktif (fade out halus + melayang naik sedikit)
        current.classList.add('section-exiting');
        current.classList.remove('active');

        sectionTransitionTimer = setTimeout(() => {
            current.classList.remove('section-exiting');
            current.classList.add('hidden');

            // 2. Persiapkan section target
            target.classList.remove('hidden');

            // Scroll ke atas dengan mulus
            window.scrollTo({ top: 0, behavior: 'smooth' });

            // Trigger reflow agar animasi entrance browser terpicu secara mulus
            void target.offsetWidth;

            // 3. Masuk dengan animasi lembut (fade in + melayang ke posisi normal)
            target.classList.add('active');
            sectionTransitionTimer = null;
        }, 150);
    } else {
        // Kasus inisialisasi awal tanpa section aktif
        document.querySelectorAll('.section').forEach(s => s.classList.add('hidden'));
        target.classList.remove('hidden');
        target.classList.add('active');
    }
}

/* ==================== 2. AUDIO PLAYER SYSTEM (FULL DURATION) ==================== */
function initYouTubeAudio() {
    // Load YouTube IFrame API script dynamically if not yet on page
    if (!window.YT) {
        const tag = document.createElement('script');
        tag.src = "https://www.youtube.com/iframe_api";
        const firstScriptTag = document.getElementsByTagName('script')[0];
        if (firstScriptTag && firstScriptTag.parentNode) {
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        } else {
            document.head.appendChild(tag);
        }
    }

    // Callback when API is ready
    window.onYouTubeIframeAPIReady = function() {
        try {
            ytAudioPlayer = new YT.Player('ytAudioPlayer', {
                height: '200',
                width: '200',
                playerVars: {
                    'playsinline': 1,
                    'controls': 0,
                    'disablekb': 1,
                    'fs': 0,
                    'rel': 0,
                    'autoplay': 0
                },
                events: {
                    'onReady': onYtPlayerReady,
                    'onStateChange': onYtPlayerStateChange,
                    'onError': onYtPlayerError
                }
            });
        } catch (e) {
            console.warn('YouTube IFrame Player init notice:', e);
        }
    };
}

function onYtPlayerReady(event) {
    isYtReady = true;
    if (pendingYtVideoId) {
        playYouTubeTrack(pendingYtVideoId);
        pendingYtVideoId = null;
    }
}

function onYtPlayerStateChange(event) {
    // YT.PlayerState.PLAYING = 1
    // YT.PlayerState.PAUSED = 2
    // YT.PlayerState.ENDED = 0
    if (event.data === 1) { // PLAYING
        isAudioPlaying = true;
        updatePlayIcons(true);
        startYtProgressTimer();
    } else if (event.data === 2) { // PAUSED
        isAudioPlaying = false;
        updatePlayIcons(false);
        stopYtProgressTimer();
    } else if (event.data === 0) { // ENDED
        isAudioPlaying = false;
        updatePlayIcons(false);
        stopYtProgressTimer();
        playNextSong();
    }
}

function onYtPlayerError(event) {
    console.warn('YouTube player notice (code ' + (event ? event.data : 'unknown') + '). Mengalihkan ke audio penuh lokal...');
    if (!currentSong) return;

    // Alihkan langsung ke audio penuh lokal tanpa menampilkan notifikasi error
    fetchFullAudioAndPlay(currentSong.id, currentSong.title, currentSong.artist, currentSong.audioUrl);
}

let isSeekingAudio = false;

function updateProgressBarVisual(percent) {
    const p = Math.max(0, Math.min(100, parseFloat(percent) || 0));
    const progressBar = document.getElementById('playerProgressBar');
    if (progressBar) {
        progressBar.style.background = `linear-gradient(to right, #00b24b 0%, #00b24b ${p}%, #2d2f33 ${p}%, #2d2f33 100%)`;
    }
    const vinylProgressBar = document.getElementById('vinylProgressBar');
    if (vinylProgressBar) {
        vinylProgressBar.value = p;
        vinylProgressBar.style.background = `linear-gradient(to right, #00b24b 0%, #00b24b ${p}%, #2d2f33 ${p}%, #2d2f33 100%)`;
    }
    const lyricsProgressBar = document.getElementById('lyricsProgressBar');
    if (lyricsProgressBar) {
        lyricsProgressBar.value = p;
        lyricsProgressBar.style.background = `linear-gradient(to right, #00b24b 0%, #00b24b ${p}%, #2d2f33 ${p}%, #2d2f33 100%)`;
    }
    const visProgressBar = document.getElementById('visProgressBar');
    if (visProgressBar) {
        visProgressBar.value = p;
        visProgressBar.style.background = `linear-gradient(to right, #00b24b 0%, #00b24b ${p}%, #2d2f33 ${p}%, #2d2f33 100%)`;
    }
}

function updateTimeDisplays(cur, dur) {
    const curStr = formatTime(cur);
    const durStr = formatTime(dur);
    const curTime = document.getElementById('playerCurrentTime');
    const durTime = document.getElementById('playerDuration');
    const vinylCur = document.getElementById('vinylCurrentTime');
    const vinylDur = document.getElementById('vinylDuration');
    const lyricsCur = document.getElementById('lyricsCurrentTime');
    const lyricsDur = document.getElementById('lyricsDuration');
    const visCur = document.getElementById('visCurrentTime');
    const visDur = document.getElementById('visDuration');

    if (curTime) curTime.textContent = curStr;
    if (durTime) durTime.textContent = durStr;
    if (vinylCur) vinylCur.textContent = curStr;
    if (vinylDur) vinylDur.textContent = durStr;
    if (lyricsCur) lyricsCur.textContent = curStr;
    if (lyricsDur) lyricsDur.textContent = durStr;
    if (visCur) visCur.textContent = curStr;
    if (visDur) visDur.textContent = durStr;
}

function startYtProgressTimer() {
    stopYtProgressTimer();
    const progressBar = document.getElementById('playerProgressBar');
    const vinylProgressBar = document.getElementById('vinylProgressBar');
    const lyricsProgressBar = document.getElementById('lyricsProgressBar');
    const visProgressBar = document.getElementById('visProgressBar');

    ytProgressTimer = setInterval(() => {
        if (!isSeekingAudio && ytAudioPlayer && isYtReady && isAudioPlaying) {
            try {
                const cur = ytAudioPlayer.getCurrentTime() || 0;
                const dur = ytAudioPlayer.getDuration() || 0;
                if (dur > 0) {
                    const progress = (cur / dur) * 100;
                    if (progressBar) progressBar.value = progress;
                    if (vinylProgressBar) vinylProgressBar.value = progress;
                    if (lyricsProgressBar) lyricsProgressBar.value = progress;
                    if (visProgressBar) visProgressBar.value = progress;
                    updateProgressBarVisual(progress);
                    updateTimeDisplays(cur, dur);
                    syncActiveLyricLine(cur);
                }
            } catch (e) {}
        }
    }, 200);
}

function stopYtProgressTimer() {
    if (ytProgressTimer) {
        clearInterval(ytProgressTimer);
        ytProgressTimer = null;
    }
}

function initAudioPlayer() {
    const audio = document.getElementById('globalAudioPlayer');
    const progressBar = document.getElementById('playerProgressBar');
    const vinylProgressBar = document.getElementById('vinylProgressBar');
    const lyricsProgressBar = document.getElementById('lyricsProgressBar');
    const visProgressBar = document.getElementById('visProgressBar');

    audio.addEventListener('timeupdate', () => {
        if (!isSeekingAudio && currentPlaybackMode === 'html5' && audio.duration) {
            const progress = (audio.currentTime / audio.duration) * 100;
            if (progressBar) progressBar.value = progress;
            if (vinylProgressBar) vinylProgressBar.value = progress;
            if (lyricsProgressBar) lyricsProgressBar.value = progress;
            if (visProgressBar) visProgressBar.value = progress;
            updateProgressBarVisual(progress);
            updateTimeDisplays(audio.currentTime, audio.duration);
            syncActiveLyricLine(audio.currentTime);
        }
    });

    audio.addEventListener('loadedmetadata', () => {
        if (currentPlaybackMode === 'html5' && audio.duration) {
            updateTimeDisplays(audio.currentTime, audio.duration);
        }
    });

    audio.addEventListener('play', () => {
        if (currentPlaybackMode === 'html5' && currentSong) {
            isAudioPlaying = true;
            updatePlayIcons(true);
        }
    });

    audio.addEventListener('playing', () => {
        if (currentPlaybackMode === 'html5' && currentSong) {
            isAudioPlaying = true;
            updatePlayIcons(true);
        }
    });

    audio.addEventListener('pause', () => {
        const videoModalEl = document.getElementById('videoModal');
        const inYoutubeVideoModal = isModalTrulyOpen(videoModalEl) && currentVideoModalMode === 'video';
        if (
            currentPlaybackMode === 'html5' &&
            currentSong &&
            audio.dataset.songId === String(currentSong.id) &&
            !audio.ended &&
            !audio.seeking &&
            !isSwitchingTrackInternal &&
            !isTransitioningAudioToVideo &&
            !inYoutubeVideoModal
        ) {
            isAudioPlaying = false;
            updatePlayIcons(false);
        }
    });

    audio.addEventListener('ended', () => {
        if (currentPlaybackMode === 'html5') {
            isAudioPlaying = false;
            updatePlayIcons(false);
            playNextSong();
        }
    });

    audio.addEventListener('error', () => {
        console.warn('Audio streaming notice: using interactive synthesized preview.');
    });

    // Kontrol Geser & Klik Interaktif (Click & Drag Scrubbing)
    function attachSeekEvents(slider) {
        if (!slider) return;

        const onSeekStart = () => {
            isSeekingAudio = true;
        };

        const onSeekInput = () => {
            isSeekingAudio = true;
            const percent = parseFloat(slider.value) || 0;
            updateProgressBarVisual(percent);

            let duration = 0;
            if (currentPlaybackMode === 'html5' && audio && audio.duration) {
                duration = audio.duration;
            } else if (ytAudioPlayer && isYtReady) {
                try { duration = ytAudioPlayer.getDuration() || 0; } catch (err) {}
            }

            if (duration > 0) {
                const targetSec = (percent / 100) * duration;
                updateTimeDisplays(targetSec, duration);
                syncActiveLyricLine(targetSec, true);
            }
        };

        const onSeekEnd = () => {
            if (isSeekingAudio) {
                const percent = parseFloat(slider.value) || 0;
                seekAudio(percent);
                setTimeout(() => {
                    isSeekingAudio = false;
                }, 60);
            }
        };

        // Desktop
        slider.addEventListener('mousedown', onSeekStart);
        slider.addEventListener('input', onSeekInput);
        slider.addEventListener('change', onSeekEnd);
        slider.addEventListener('mouseup', onSeekEnd);

        // Mobile / Touch
        slider.addEventListener('touchstart', onSeekStart, { passive: true });
        slider.addEventListener('touchmove', onSeekInput, { passive: true });
        slider.addEventListener('touchend', onSeekEnd);
    }

    attachSeekEvents(progressBar);
    attachSeekEvents(vinylProgressBar);
    attachSeekEvents(lyricsProgressBar);
    attachSeekEvents(visProgressBar);
}

function isLocalFullAudio(url) {
    if (!url) return false;
    return url.includes('audio/') || url.includes('.mp3') || url.startsWith('/audio');
}

function fetchFullAudioAndPlay(id, title, artist, fallbackAudioUrl) {
    fetch(`/api/musics/${id}/prepare-audio`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.audio_url) {
                currentPlaybackMode = 'html5';
                if (currentSong && currentSong.id === id) {
                    currentSong.audioUrl = data.audio_url;
                }
                startAudioPlayback(data.audio_url);
            } else {
                currentPlaybackMode = 'html5';
                if (fallbackAudioUrl) {
                    startAudioPlayback(fallbackAudioUrl);
                }
            }
        })
        .catch(err => {
            console.warn('Error fetching full audio:', err);
            currentPlaybackMode = 'html5';
            startAudioPlayback(`/audio/${id}.mp3`);
        });
}

function prewarmSongLyrics(song) {
    if (!song || !song.id) return;
    const cacheKey = song.id;
    if (window.lyricsCache && window.lyricsCache[cacheKey]) return;
    fetch(`/lyrics/${song.id}.json?v=5`)
        .then(r => r.ok ? r.json() : null)
        .then(data => {
            if (data && (data.syncedLyrics || data.plainLyrics || data.instrumental)) {
                window.lyricsCache = window.lyricsCache || {};
                window.lyricsCache[cacheKey] = data;
            }
        })
        .catch(() => {});
}

let isSwitchingTrackInternal = false;
let isTransitioningAudioToVideo = false;

/**
 * Tampilkan bottom audio player dan sinkronkan metadata lagu aktif ke player bar, vinyl, dan lirik.
 */
function syncCurrentSongMetadataUI(song, options = {}) {
    if (!song) return;
    const finalCover = normalizeImageUrl(song.cover);
    const playerBar = document.getElementById('bottomAudioPlayer');
    if (playerBar) {
        playerBar.classList.remove('translate-y-full', 'opacity-0', 'pointer-events-none');
        playerBar.classList.add('translate-y-0', 'opacity-100');
    }

    const pTitle = document.getElementById('playerTitle');
    const pArtist = document.getElementById('playerArtist');
    const pCover = document.getElementById('playerCover');
    if (pTitle) pTitle.textContent = song.title || 'Putar Musik';
    if (pArtist) pArtist.textContent = song.artist || '-';
    if (pCover) pCover.src = finalCover;

    updateVinylDisplay(song.title, song.artist, finalCover, options);
}

/**
 * Pastikan seek ke target detik berjalan aman baik sebelum maupun sesudah metadata audio siap.
 */
function applyHtml5SeekSafely(audio, targetTimeSec) {
    if (!audio || typeof targetTimeSec !== 'number' || isNaN(targetTimeSec) || targetTimeSec < 0) return;
    const applySeek = () => {
        try {
            const maxDur = (audio.duration && !isNaN(audio.duration) && audio.duration > 0.3) ? audio.duration - 0.2 : null;
            audio.currentTime = maxDur !== null ? Math.min(targetTimeSec, maxDur) : targetTimeSec;
        } catch (e) {}
    };
    if (audio.readyState >= 1) {
        if (Math.abs((audio.currentTime || 0) - targetTimeSec) > 0.35) {
            applySeek();
        }
    } else {
        audio.addEventListener('loadedmetadata', applySeek, { once: true });
    }
}

function ensureSongContinuingSmoothly(id, title, artist, cover, audioUrl, videoId, targetTimeSec = null) {
    const finalCover = normalizeImageUrl(cover);
    const html5Audio = document.getElementById('globalAudioPlayer');
    const isSameHtml5Song = Boolean(
        id &&
        currentPlaybackMode === 'html5' &&
        currentSong &&
        Number(currentSong.id) === Number(id) &&
        html5Audio &&
        html5Audio.dataset.songId === String(id) &&
        html5Audio.src &&
        !html5Audio.ended
    );
    const isSameYtSong = Boolean(
        id &&
        currentPlaybackMode === 'youtube' &&
        currentSong &&
        Number(currentSong.id) === Number(id) &&
        ytAudioPlayer &&
        isYtReady
    );

    if (isSameHtml5Song || isSameYtSong) {
        currentSong.title = title || currentSong.title;
        currentSong.artist = artist || currentSong.artist;
        currentSong.cover = finalCover || currentSong.cover;
        if (videoId) currentSong.videoId = videoId;

        syncCurrentSongMetadataUI(currentSong);

        if (isSameYtSong) {
            try {
                if (typeof targetTimeSec === 'number' && !isNaN(targetTimeSec) && targetTimeSec >= 0) {
                    const curYt = ytAudioPlayer.getCurrentTime() || 0;
                    if (Math.abs(curYt - targetTimeSec) > 0.5) {
                        ytAudioPlayer.seekTo(targetTimeSec, true);
                    }
                }
                if (ytAudioPlayer.getPlayerState() !== 1) {
                    ytAudioPlayer.playVideo();
                }
                isAudioPlaying = true;
                updatePlayIcons(true);
                startYtProgressTimer();
            } catch (e) {}
        } else {
            applyHtml5SeekSafely(html5Audio, targetTimeSec);

            if (html5Audio.paused) {
                html5Audio.play().then(() => {
                    isAudioPlaying = true;
                    updatePlayIcons(true);
                }).catch(() => {});
            } else {
                isAudioPlaying = true;
                updatePlayIcons(true);
            }
        }

        if (currentLyricsSongId === Number(id) && currentLyricsLines.length > 0) {
            syncActiveLyricLine(getCurrentAudioTime(), true);
        }
        return;
    }

    playSong(id, title, artist, finalCover, audioUrl, videoId, {
        startTime: (typeof targetTimeSec === 'number' && !isNaN(targetTimeSec) && targetTimeSec > 0) ? targetTimeSec : 0
    });
}

function playSong(id, title, artist, cover, audioUrl, videoId, options = {}) {
    // Format audioUrl jika menggunakan format path lokal relatif
    let resolvedAudioUrl = audioUrl;
    if (resolvedAudioUrl && (resolvedAudioUrl.startsWith('audio/') || resolvedAudioUrl.includes('/audio/'))) {
        resolvedAudioUrl = resolvedAudioUrl.startsWith('/') ? resolvedAudioUrl : '/' + resolvedAudioUrl;
    }

    const finalCover = normalizeImageUrl(cover);
    const html5Audio = document.getElementById('globalAudioPlayer');

    // Jika lagu yang sama sudah sedang dimuat/diputar dan bukan forceRestart, lanjutkan mulus tanpa mengulang dari 0:00
    if (
        !options.forceRestart &&
        id &&
        currentSong &&
        Number(currentSong.id) === Number(id) &&
        (
            (currentPlaybackMode === 'html5' && html5Audio && html5Audio.dataset.songId === String(id) && html5Audio.src && !html5Audio.ended) ||
            (currentPlaybackMode === 'youtube' && ytAudioPlayer && isYtReady)
        )
    ) {
        ensureSongContinuingSmoothly(id, title, artist, finalCover, resolvedAudioUrl, videoId, options.startTime);
        return;
    }

    if (activeVideoSong && id && Number(activeVideoSong.id) !== Number(id)) {
        activeVideoSong = null;
    }

    currentSong = { id, title, artist, cover: finalCover, audioUrl: resolvedAudioUrl, videoId };

    if (!currentSongQueue.some(s => s.id === id)) {
        currentSongQueue.push({ id, title, artist, image: finalCover, audio_url: resolvedAudioUrl, video_id: videoId });
    }

    // Pre-warm lirik statis di latar belakang agar saat pindah ke mode Lirik langsung tampil 0ms
    prewarmSongLyrics(currentSong);

    isSwitchingTrackInternal = true;

    // Hentikan pemutar HTML5 & tandai songId aktif sebelum memuat lirik agar durasi lagu lama tidak terbawa
    if (html5Audio) {
        if (!html5Audio.paused) {
            html5Audio.pause();
        }
        try { html5Audio.currentTime = 0; } catch (e) {}
        html5Audio.dataset.songId = id ? String(id) : '';
    }
    // Hentikan YouTube Player jika sedang berjalan
    if (ytAudioPlayer && isYtReady) {
        try { ytAudioPlayer.stopVideo(); } catch (e) {}
    }
    stopYtProgressTimer();
    stopSynthesizedPreview();

    // Tampilkan player bar & sinkronkan data 3D vinyl/modal secara terpusat tanpa race condition
    syncCurrentSongMetadataUI(currentSong);

    // PRIORITAS 1: Gunakan Stream Audio Server Lokal dengan dukungan penuh HTTP 206 (Byte-Ranges) & memory cache
    // agar pemutaran instan tanpa delay serta fitur klik lirik / pindah mode berjalan 100% mulus
    if (id) {
        currentPlaybackMode = 'html5';
        const streamUrl = `/api/musics/${id}/stream`;
        currentSong.audioUrl = streamUrl;
        startAudioPlayback(streamUrl, options.startTime || 0);
        setTimeout(() => { isSwitchingTrackInternal = false; }, 120);
        return;
    }

    setTimeout(() => { isSwitchingTrackInternal = false; }, 120);

    // PRIORITAS 2: Putar stream YouTube jika ada videoId
    const vid = videoId || (currentSong && currentSong.videoId);
    if (vid && vid.length >= 8) {
        currentPlaybackMode = 'youtube';
        if (isYtReady && ytAudioPlayer) {
            playYouTubeTrack(vid);
        } else {
            pendingYtVideoId = vid;
        }
    } else {
        // Fallback otomatis siapkan audio full
        fetchFullAudioAndPlay(id, title, artist, resolvedAudioUrl);
    }
}

function playYouTubeTrack(videoId) {
    try {
        ytAudioPlayer.loadVideoById(videoId);
        ytAudioPlayer.playVideo();
        isAudioPlaying = true;
        updatePlayIcons(true);
        startYtProgressTimer();
    } catch (e) {
        console.warn('Error playing YouTube track, fallback to HTML5 preview:', e);
        currentPlaybackMode = 'html5';
        if (currentSong && currentSong.audioUrl) {
            startAudioPlayback(currentSong.audioUrl);
        }
    }
}

function handleAudioPlaybackFallback() {
    // 1. Coba putar stream YouTube online jika lagu memiliki videoId
    if (currentSong && currentSong.videoId && currentSong.videoId.length >= 8) {
        console.warn('Mengalihkan ke pemutaran stream online YouTube...');
        currentPlaybackMode = 'youtube';
        if (isYtReady && ytAudioPlayer) {
            playYouTubeTrack(currentSong.videoId);
            return;
        }
    }

    // 2. Coba fetch preview resmi dari Apple iTunes CDN
    if (currentSong && currentSong.id) {
        fetch(`/api/musics/${currentSong.id}/audio`)
            .then(res => res.json())
            .then(data => {
                if (data.audio_url && data.audio_url.startsWith('http')) {
                    const audio = document.getElementById('globalAudioPlayer');
                    audio.src = data.audio_url;
                    audio.play().catch(() => playSynthesizedPreview());
                } else {
                    playSynthesizedPreview();
                }
            })
            .catch(() => playSynthesizedPreview());
        return;
    }

    playSynthesizedPreview();
}

function startAudioPlayback(audioUrl, startTimeSec = 0) {
    const audio = document.getElementById('globalAudioPlayer');
    if (audioUrl && audioUrl.trim() !== '') {
        audio.src = audioUrl;
        if (typeof startTimeSec === 'number' && !isNaN(startTimeSec) && startTimeSec > 0) {
            applyHtml5SeekSafely(audio, startTimeSec);
        }
        audio.play().then(() => {
            isAudioPlaying = true;
            updatePlayIcons(true);
            if (currentLyricsSongId && currentSong && Number(currentLyricsSongId) === Number(currentSong.id)) {
                syncActiveLyricLine(getCurrentAudioTime(), true);
            }
        }).catch((err) => {
            if (err && err.name === 'AbortError') {
                return;
            }
            console.warn('Audio play notice, switching to online stream fallback:', err);
            handleAudioPlaybackFallback();
        });
    } else {
        handleAudioPlaybackFallback();
    }
}

function togglePlayAudio() {
    if (!currentSong) {
        if (currentSongQueue && currentSongQueue.length > 0) {
            const first = currentSongQueue[0];
            playSong(first.id, first.title, first.artist, first.image ? (first.image.startsWith('/') ? first.image : '/' + first.image) : '/image/queen1.jpg', first.audio_url || '', first.video_id || '');
            return;
        } else {
            playSong(1, 'Bohemian Rhapsody', 'Queen', '/image/queen1.jpg', '/audio/1.mp3', 'fJ9rUzIMcZQ');
            return;
        }
    }

    if (currentPlaybackMode === 'youtube' && ytAudioPlayer && isYtReady) {
        try {
            const state = ytAudioPlayer.getPlayerState();
            if (state === 1) { // Playing
                ytAudioPlayer.pauseVideo();
                isAudioPlaying = false;
                updatePlayIcons(false);
                stopYtProgressTimer();
            } else {
                ytAudioPlayer.playVideo();
                isAudioPlaying = true;
                updatePlayIcons(true);
                startYtProgressTimer();
            }
            return;
        } catch (e) {
            console.warn(e);
        }
    }

    // HTML5 fallback
    const audio = document.getElementById('globalAudioPlayer');
    if (isAudioPlaying) {
        audio.pause();
        stopSynthesizedPreview();
        isAudioPlaying = false;
        updatePlayIcons(false);
    } else {
        if (audio.src) {
            audio.play().then(() => {
                isAudioPlaying = true;
                updatePlayIcons(true);
            }).catch(() => {
                playSynthesizedPreview();
            });
        } else {
            playSynthesizedPreview();
        }
    }
}

function closeAudioPlayer() {
    if (typeof stopVideoModalSyncTimer === 'function') {
        stopVideoModalSyncTimer();
    }
    // 1. Hentikan pemutar HTML5 dan bersihkan sumber audio
    const html5Audio = document.getElementById('globalAudioPlayer');
    if (html5Audio) {
        html5Audio.pause();
        html5Audio.currentTime = 0;
        html5Audio.dataset.songId = '';
        html5Audio.removeAttribute('src');
    }

    // 2. Hentikan pemutar YouTube jika sedang berjalan
    if (ytAudioPlayer && isYtReady) {
        try {
            ytAudioPlayer.stopVideo();
        } catch (e) {}
    }
    stopYtProgressTimer();

    // 3. Hentikan synthesizer cadangan
    stopSynthesizedPreview();

    // 4. Update status pemutaran dan ikon
    isAudioPlaying = false;
    updatePlayIcons(false);

    // 5. Reset progress bar dan durasi
    const progressBar = document.getElementById('playerProgressBar');
    const curTime = document.getElementById('playerCurrentTime');
    const vinylProgressBar = document.getElementById('vinylProgressBar');
    const vinylCurTime = document.getElementById('vinylCurrentTime');
    const lyricsProgressBar = document.getElementById('lyricsProgressBar');
    const lyricsCurTime = document.getElementById('lyricsCurrentTime');
    if (progressBar) {
        progressBar.value = 0;
        updateProgressBarVisual(0);
    }
    if (vinylProgressBar) {
        vinylProgressBar.value = 0;
    }
    if (lyricsProgressBar) {
        lyricsProgressBar.value = 0;
    }
    if (curTime) curTime.textContent = '0:00';
    if (vinylCurTime) vinylCurTime.textContent = '0:00';
    if (lyricsCurTime) lyricsCurTime.textContent = '0:00';

    // 6. Sembunyikan bottom player bar dengan animasi mulus
    const playerBar = document.getElementById('bottomAudioPlayer');
    if (playerBar) {
        playerBar.classList.remove('translate-y-0', 'opacity-100');
        playerBar.classList.add('translate-y-full', 'opacity-0', 'pointer-events-none');
    }

    // 7. Bersihkan data lagu yang sedang diputar
    activeVideoSong = null;
    currentSong = null;
}

function updatePlayIcons(playing) {
    const playIcon = document.getElementById('playIcon');
    const pauseIcon = document.getElementById('pauseIcon');
    const vinylPlayIcon = document.getElementById('vinylModalPlayIcon');
    const vinylPauseIcon = document.getElementById('vinylModalPauseIcon');
    const lyricsPlayIcon = document.getElementById('lyricsModalPlayIcon');
    const lyricsPauseIcon = document.getElementById('lyricsModalPauseIcon');
    const visPlayIcon = document.getElementById('visPlayIcon');
    const visPauseIcon = document.getElementById('visPauseIcon');
    const visBars = document.querySelectorAll('.visualizer-bar');
    const vinylBadge = document.getElementById('vinylStatusBadge');
    const visFallback = document.getElementById('videoVisualizerFallback');

    if (playing) {
        if (playIcon) playIcon.classList.add('hidden');
        if (pauseIcon) pauseIcon.classList.remove('hidden');
        if (vinylPlayIcon) vinylPlayIcon.classList.add('hidden');
        if (vinylPauseIcon) vinylPauseIcon.classList.remove('hidden');
        if (lyricsPlayIcon) lyricsPlayIcon.classList.add('hidden');
        if (lyricsPauseIcon) lyricsPauseIcon.classList.remove('hidden');
        if (visPlayIcon) visPlayIcon.classList.add('hidden');
        if (visPauseIcon) visPauseIcon.classList.remove('hidden');
        visBars.forEach(b => b.classList.remove('paused'));
        if (vinylBadge) {
            vinylBadge.textContent = 'Status: Berputar';
            vinylBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#00b24b] text-white font-semibold text-[12px] leading-none whitespace-nowrap';
        }
        if (visFallback) {
            visFallback.classList.remove('is-paused');
            if (typeof handleVisualizerActivity === 'function') {
                handleVisualizerActivity();
            }
        }
    } else {
        if (playIcon) playIcon.classList.remove('hidden');
        if (pauseIcon) pauseIcon.classList.add('hidden');
        if (vinylPlayIcon) vinylPlayIcon.classList.remove('hidden');
        if (vinylPauseIcon) vinylPauseIcon.classList.add('hidden');
        if (lyricsPlayIcon) lyricsPlayIcon.classList.remove('hidden');
        if (lyricsPauseIcon) lyricsPauseIcon.classList.add('hidden');
        if (visPlayIcon) visPlayIcon.classList.remove('hidden');
        if (visPauseIcon) visPauseIcon.classList.add('hidden');
        visBars.forEach(b => b.classList.add('paused'));
        if (vinylBadge) {
            vinylBadge.textContent = 'Status: Berhenti';
            vinylBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#141415] text-[#9aa1b1] font-semibold border border-[#2d2f33] text-[12px] leading-none whitespace-nowrap';
        }
        if (visFallback) {
            visFallback.classList.add('is-paused');
            visFallback.classList.add('show-controls');
            if (typeof visControlsTimeout !== 'undefined' && visControlsTimeout) {
                clearTimeout(visControlsTimeout);
            }
        }
    }
}

function seekAudio(percent) {
    const val = Math.max(0, Math.min(100, parseFloat(percent) || 0));
    updateProgressBarVisual(val);

    if (currentPlaybackMode === 'youtube' && ytAudioPlayer && isYtReady) {
        try {
            const dur = ytAudioPlayer.getDuration();
            if (dur > 0) {
                const targetSec = (val / 100) * dur;
                ytAudioPlayer.seekTo(targetSec, true);
                updateTimeDisplays(targetSec, dur);
                syncActiveLyricLine(targetSec, true);
                return;
            }
        } catch (e) {}
    }

    const audio = document.getElementById('globalAudioPlayer');
    if (audio && audio.duration) {
        const targetSec = (val / 100) * audio.duration;
        audio.currentTime = targetSec;
        updateTimeDisplays(targetSec, audio.duration);
        syncActiveLyricLine(targetSec, true);
    }
}

function formatTime(seconds) {
    if (isNaN(seconds)) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
}

// Interactive Web Audio Sound Synthesizer Fallback
function playSynthesizedPreview() {
    try {
        if (!audioCtx) {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        stopSynthesizedPreview();

        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();

        const songTitle = (currentSong && currentSong.title) ? currentSong.title : 'Preview';
        const baseFreq = 220 + (songTitle.length * 12);
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(baseFreq, audioCtx.currentTime);

        gain.gain.setValueAtTime(0.08, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 3);

        osc.connect(gain);
        gain.connect(audioCtx.destination);

        osc.start();
        synthOscillator = osc;
        isAudioPlaying = true;
        updatePlayIcons(true);
    } catch (e) {
        console.log(e);
    }
}

function stopSynthesizedPreview() {
    if (synthOscillator) {
        try {
            synthOscillator.stop();
            synthOscillator.disconnect();
        } catch (e) {}
        synthOscillator = null;
    }
}

let currentAudioVolume = 1;
let isAudioMuted = false;

function setAudioVolume(val) {
    const volume = Math.max(0, Math.min(1, parseFloat(val) || 0));
    currentAudioVolume = volume;
    isAudioMuted = (volume === 0);

    const audio = document.getElementById('globalAudioPlayer');
    if (audio) {
        audio.volume = volume;
        audio.muted = isAudioMuted;
    }

    if (ytAudioPlayer && isYtReady) {
        try {
            ytAudioPlayer.setVolume(Math.round(volume * 100));
            if (isAudioMuted) {
                ytAudioPlayer.mute();
            } else {
                ytAudioPlayer.unMute();
            }
        } catch (e) {}
    }

    updateVolumeVisuals();
}

function toggleMuteAudio() {
    const slider = document.getElementById('playerVolumeSlider');
    if (isAudioMuted || currentAudioVolume === 0) {
        const restoreVol = currentAudioVolume > 0 ? currentAudioVolume : 0.8;
        isAudioMuted = false;
        currentAudioVolume = restoreVol;
        const audio = document.getElementById('globalAudioPlayer');
        if (audio) {
            audio.muted = false;
            audio.volume = restoreVol;
        }
        if (ytAudioPlayer && isYtReady) {
            try {
                ytAudioPlayer.unMute();
                ytAudioPlayer.setVolume(Math.round(restoreVol * 100));
            } catch (e) {}
        }
        if (slider) slider.value = restoreVol;
    } else {
        isAudioMuted = true;
        const audio = document.getElementById('globalAudioPlayer');
        if (audio) audio.muted = true;
        if (ytAudioPlayer && isYtReady) {
            try { ytAudioPlayer.mute(); } catch (e) {}
        }
        if (slider) slider.value = 0;
    }
    updateVolumeVisuals();
}

function updateVolumeVisuals() {
    const highIcon = document.getElementById('volumeHighIcon');
    const muteIcon = document.getElementById('volumeMuteIcon');
    const slider = document.getElementById('playerVolumeSlider');

    const effectiveVol = isAudioMuted ? 0 : currentAudioVolume;
    const pct = Math.max(0, Math.min(100, Math.round(effectiveVol * 100)));
    if (slider) {
        slider.style.background = `linear-gradient(to right, #00b24b 0%, #00b24b ${pct}%, #2d2f33 ${pct}%, #2d2f33 100%)`;
    }

    if (!highIcon || !muteIcon) return;

    if (isAudioMuted || currentAudioVolume === 0) {
        highIcon.classList.add('hidden');
        muteIcon.classList.remove('hidden');
    } else {
        highIcon.classList.remove('hidden');
        muteIcon.classList.add('hidden');
    }
}

function playPrevSong() {
    if (!currentSongQueue || currentSongQueue.length === 0) {
        showToast('Antrean lagu kosong.', 'info');
        return;
    }
    let currentIndex = currentSongQueue.findIndex(s => s.id === (currentSong ? currentSong.id : -1));
    if (currentIndex <= 0) {
        currentIndex = currentSongQueue.length;
    }
    let prevIndex = (currentIndex - 1) % currentSongQueue.length;
    let prevSong = currentSongQueue[prevIndex];
    if (prevSong) {
        playSong(
            prevSong.id,
            prevSong.title,
            prevSong.artist,
            prevSong.image ? (prevSong.image.startsWith('/') ? prevSong.image : '/' + prevSong.image) : '/image/queen1.jpg',
            prevSong.audio_url || '',
            prevSong.video_id || ''
        );
    }
}

function playNextSong() {
    if (!currentSongQueue || currentSongQueue.length === 0) {
        return;
    }
    let currentIndex = currentSongQueue.findIndex(s => s.id === (currentSong ? currentSong.id : -1));
    let nextIndex = (currentIndex + 1) % currentSongQueue.length;
    let nextSong = currentSongQueue[nextIndex];
    if (nextSong) {
        playSong(
            nextSong.id,
            nextSong.title,
            nextSong.artist,
            nextSong.image ? (nextSong.image.startsWith('/') ? nextSong.image : '/' + nextSong.image) : '/image/queen1.jpg',
            nextSong.audio_url || '',
            nextSong.video_id || ''
        );
    }
}

/* ==================== 3. THREE.JS 3D VINYL PLAYER ==================== */
function initThreeJSVinyl() {
    const container = document.getElementById('vinyl3dContainer');
    if (!container) return;

    const width = container.clientWidth || 600;
    const height = container.clientHeight || 320;

    // 1. Scene
    threeScene = new THREE.Scene();

    // 2. Camera
    threeCamera = new THREE.PerspectiveCamera(42, width / height, 0.1, 1000);
    threeCamera.position.set(0, 5.2, 7.8);
    threeCamera.lookAt(0, 0, 0);

    // 3. Renderer with high sharpness & antialiasing
    threeRenderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
    threeRenderer.setSize(width, height);
    threeRenderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    threeRenderer.shadowMap.enabled = true;
    threeRenderer.shadowMap.type = THREE.PCFSoftShadowMap;

    container.innerHTML = '';
    container.appendChild(threeRenderer.domElement);

    // 4. Lights
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.95);
    threeScene.add(ambientLight);

    const dirLight = new THREE.DirectionalLight(0xffffff, 1.25);
    dirLight.position.set(5, 12, 7);
    threeScene.add(dirLight);

    const emeraldRimLight = new THREE.PointLight(0x00b24b, 1.4, 25);
    emeraldRimLight.position.set(-5, 4, 3);
    threeScene.add(emeraldRimLight);

    // 5. UNIFIED 3D TURNTABLE GROUP (All parts belong here so dragging rotates the whole solid unit!)
    turntableGroup = new THREE.Group();
    turntableGroup.rotation.x = turntablePitch;
    turntableGroup.rotation.y = turntableYaw;
    threeScene.add(turntableGroup);

    // A. Turntable Solid Chassis / Deck (design.md Canvas/Surface dark neutral)
    const deckGeo = new THREE.CylinderGeometry(3.5, 3.6, 0.35, 64);
    const deckMat = new THREE.MeshStandardMaterial({
        color: 0x141415,
        roughness: 0.5,
        metalness: 0.7
    });
    const deckMesh = new THREE.Mesh(deckGeo, deckMat);
    deckMesh.position.y = -0.2;
    turntableGroup.add(deckMesh);

    // Bottom solid plate to guarantee zero see-through from any angle
    const bottomPlateGeo = new THREE.CylinderGeometry(3.6, 3.6, 0.08, 64);
    const bottomPlateMat = new THREE.MeshStandardMaterial({ color: 0x0c0c0d, roughness: 0.9 });
    const bottomPlate = new THREE.Mesh(bottomPlateGeo, bottomPlateMat);
    bottomPlate.position.y = -0.38;
    turntableGroup.add(bottomPlate);

    // B. Die-cast Metal Platter
    const platterGeo = new THREE.CylinderGeometry(3.2, 3.25, 0.18, 64);
    const platterMat = new THREE.MeshStandardMaterial({
        color: 0x2e3035,
        roughness: 0.25,
        metalness: 0.9
    });
    const platterMesh = new THREE.Mesh(platterGeo, platterMat);
    platterMesh.position.y = -0.02;
    turntableGroup.add(platterMesh);

    // C. Rubber Mat
    const matGeo = new THREE.CylinderGeometry(3.08, 3.08, 0.02, 64);
    const matMat = new THREE.MeshStandardMaterial({ color: 0x111827, roughness: 0.95 });
    const slipmat = new THREE.Mesh(matGeo, matMat);
    slipmat.position.y = 0.07;
    turntableGroup.add(slipmat);

    // D. 3D Vinyl Record (Spinning Disc)
    const vinylGeo = new THREE.CylinderGeometry(2.95, 2.95, 0.05, 64);
    const vinylMat = new THREE.MeshStandardMaterial({
        color: 0x070707,
        roughness: 0.25,
        metalness: 0.85
    });
    vinylMesh = new THREE.Mesh(vinylGeo, vinylMat);
    vinylMesh.position.y = 0.1;
    turntableGroup.add(vinylMesh);

    // Concentric Grooves (Placed flush on vinyl top face, normals pointing straight UP)
    for (let r = 1.35; r < 2.85; r += 0.22) {
        const ringGeo = new THREE.RingGeometry(r, r + 0.028, 48);
        const ringMat = new THREE.MeshBasicMaterial({
            color: 0x2d2f33,
            side: THREE.FrontSide,
            depthWrite: true
        });
        const ringMesh = new THREE.Mesh(ringGeo, ringMat);
        ringMesh.rotation.x = -Math.PI / 2; // NORMAL FACING UP (+Y)
        ringMesh.position.y = 0.026; // flush with top of vinyl cylinder (0.05/2 = 0.025)
        vinylMesh.add(ringMesh);
    }

    // E. Center Chrome Spindle
    const spindleGeo = new THREE.CylinderGeometry(0.09, 0.09, 0.45, 32);
    const spindleMat = new THREE.MeshStandardMaterial({ color: 0xf0f2f5, roughness: 0.1, metalness: 0.95 });
    const spindleMesh = new THREE.Mesh(spindleGeo, spindleMat);
    spindleMesh.position.y = 0.24;
    turntableGroup.add(spindleMesh);

    // F. 3D Mechanical Tonearm Assembly
    const tonearmGroup = new THREE.Group();
    tonearmGroup.position.set(2.7, 0.12, 1.8);

    const baseArmGeo = new THREE.CylinderGeometry(0.32, 0.36, 0.35, 24);
    const baseArmMat = new THREE.MeshStandardMaterial({ color: 0x2e3035, roughness: 0.35, metalness: 0.85 });
    const baseArm = new THREE.Mesh(baseArmGeo, baseArmMat);
    tonearmGroup.add(baseArm);

    // Counterweight
    const counterGeo = new THREE.CylinderGeometry(0.24, 0.24, 0.35, 24);
    const counterMat = new THREE.MeshStandardMaterial({ color: 0x141415, roughness: 0.4, metalness: 0.9 });
    const counter = new THREE.Mesh(counterGeo, counterMat);
    counter.rotation.x = Math.PI / 2;
    counter.position.set(0.35, 0.15, 0.65);
    tonearmGroup.add(counter);

    // Chrome Arm Rod
    const rodGeo = new THREE.CylinderGeometry(0.045, 0.045, 3.1, 16);
    const rodMat = new THREE.MeshStandardMaterial({ color: 0xf0f2f5, roughness: 0.1, metalness: 0.95 });
    const rod = new THREE.Mesh(rodGeo, rodMat);
    rod.rotation.z = Math.PI / 2.3;
    rod.rotation.y = -Math.PI / 6.2;
    rod.position.set(-1.1, 0.26, -0.9);
    tonearmGroup.add(rod);

    // Action Green Stylus Cartridge
    const cartGeo = new THREE.BoxGeometry(0.18, 0.12, 0.35);
    const cartMat = new THREE.MeshStandardMaterial({ color: 0x00b24b, roughness: 0.3, metalness: 0.8 });
    const cart = new THREE.Mesh(cartGeo, cartMat);
    cart.position.set(-2.25, 0.11, -1.8);
    cart.rotation.y = -Math.PI / 6;
    tonearmGroup.add(cart);

    tonearmMesh = tonearmGroup;
    turntableGroup.add(tonearmMesh);

    // Initial Vinyl Center Label
    if (currentSong && currentSong.cover) {
        updateVinylTexture(currentSong.cover);
    } else {
        updateVinylTexture('/image/queen1.jpg');
    }

    // G. MOUSE & TOUCH: ORBIT DRAG + CLICK TO PLAY/PAUSE
    let isDragging = false;
    let dragStart = { x: 0, y: 0 };
    let hasMoved = false;

    container.addEventListener('mousedown', (e) => {
        isDragging = true;
        hasMoved = false;
        dragStart = { x: e.clientX, y: e.clientY };
    });

    window.addEventListener('mouseup', () => {
        const vinylModal = document.getElementById('vinylModal');
        if (isDragging && !hasMoved && isModalTrulyOpen(vinylModal)) {
            // USER CLICKED ON THE VINYL TURNTABLE -> TOGGLE PLAY / PAUSE!
            togglePlayAudio();
        }
        isDragging = false;
    });

    container.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        const deltaX = e.clientX - dragStart.x;
        const deltaY = e.clientY - dragStart.y;

        if (Math.abs(deltaX) > 3 || Math.abs(deltaY) > 3) {
            hasMoved = true;
        }

        turntableYaw += deltaX * 0.008;
        turntablePitch += deltaY * 0.008;

        // CLAMP VERTICAL PITCH: Prevents seeing through or flipping upside down!
        turntablePitch = Math.max(0.05, Math.min(0.85, turntablePitch));

        turntableGroup.rotation.y = turntableYaw;
        turntableGroup.rotation.x = turntablePitch;

        dragStart = { x: e.clientX, y: e.clientY };
    });

    // Mobile Touch
    container.addEventListener('touchstart', (e) => {
        if (e.touches.length === 1) {
            isDragging = true;
            hasMoved = false;
            dragStart = { x: e.touches[0].clientX, y: e.touches[0].clientY };
        }
    }, { passive: true });

    container.addEventListener('touchmove', (e) => {
        if (!isDragging || e.touches.length !== 1) return;
        const touch = e.touches[0];
        const deltaX = touch.clientX - dragStart.x;
        const deltaY = touch.clientY - dragStart.y;

        if (Math.abs(deltaX) > 3 || Math.abs(deltaY) > 3) {
            hasMoved = true;
        }

        turntableYaw += deltaX * 0.01;
        turntablePitch += deltaY * 0.01;
        turntablePitch = Math.max(0.05, Math.min(0.85, turntablePitch));

        turntableGroup.rotation.y = turntableYaw;
        turntableGroup.rotation.x = turntablePitch;

        dragStart = { x: touch.clientX, y: touch.clientY };
    }, { passive: true });

    container.addEventListener('touchend', () => {
        if (isDragging && !hasMoved) {
            togglePlayAudio();
        }
        isDragging = false;
    });

    // 6. Animation Loop
    function animate() {
        requestAnimationFrame(animate);

        // Spin Vinyl when playing
        if (isAudioPlaying && vinylMesh) {
            vinylMesh.rotation.y += vinylRotationSpeed;
            if (tonearmMesh) {
                tonearmMesh.rotation.y = THREE.MathUtils.lerp(tonearmMesh.rotation.y, 0.36, 0.05);
            }
        } else if (tonearmMesh) {
            tonearmMesh.rotation.y = THREE.MathUtils.lerp(tonearmMesh.rotation.y, 0.0, 0.05);
        }

        threeRenderer.render(threeScene, threeCamera);
    }
    animate();

    window.addEventListener('resize', onWindowResize);
}

/* ==================== 3.1 THREE.JS VINYL TEXTURE & DISPLAY SYNC ==================== */
let vinylTextureCache = new Map();
let activeVinylRequestId = 0;
let vinylLabelMesh = null;
let vinylLabelGroup = null;
const threeTextureLoader = new THREE.TextureLoader();
threeTextureLoader.crossOrigin = 'anonymous';

function normalizeImageUrl(url) {
    if (!url || typeof url !== 'string' || url.trim() === '') {
        return '/image/queen1.jpg';
    }
    const trimmed = url.trim();
    if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
        return trimmed;
    }
    return trimmed.startsWith('/') ? trimmed : '/' + trimmed;
}

function configureThreeTexture(texture) {
    texture.generateMipmaps = true;
    texture.minFilter = THREE.LinearMipmapLinearFilter;
    texture.magFilter = THREE.LinearFilter;
    if (threeRenderer && threeRenderer.capabilities) {
        texture.anisotropy = Math.min(16, threeRenderer.capabilities.getMaxAnisotropy() || 1);
    }
    texture.center.set(0.5, 0.5);
    texture.needsUpdate = true;
}

function ensureVinylLabelStructure() {
    if (!vinylMesh) return;
    if (vinylLabelMesh && vinylLabelGroup && vinylLabelGroup.parent === vinylMesh) return;

    // Bersihkan label lama jika ada
    const old = vinylMesh.getObjectByName('vinylLabel');
    if (old) vinylMesh.remove(old);

    vinylLabelGroup = new THREE.Group();
    vinylLabelGroup.name = 'vinylLabel';

    // 1. Piringan tengah kover album melingkar (Circular Artwork Center)
    const labelGeo = new THREE.CircleGeometry(1.15, 64);
    const labelMat = new THREE.MeshBasicMaterial({
        color: 0xffffff,
        side: THREE.FrontSide,
        depthWrite: true
    });
    vinylLabelMesh = new THREE.Mesh(labelGeo, labelMat);
    vinylLabelMesh.rotation.x = -Math.PI / 2;
    vinylLabelMesh.position.y = 0.027;
    vinylLabelGroup.add(vinylLabelMesh);

    // 2. Lingkaran luar beraksen action green metalik mengkilap
    const rimGeo = new THREE.RingGeometry(1.14, 1.18, 64);
    const rimMat = new THREE.MeshStandardMaterial({
        color: 0x00b24b,
        metalness: 0.9,
        roughness: 0.2,
        side: THREE.FrontSide
    });
    const rimMesh = new THREE.Mesh(rimGeo, rimMat);
    rimMesh.rotation.x = -Math.PI / 2;
    rimMesh.position.y = 0.028;
    vinylLabelGroup.add(rimMesh);

    // 3. Cincin lubang poros tengah spindle krom
    const holeGeo = new THREE.RingGeometry(0.09, 0.16, 32);
    const holeMat = new THREE.MeshStandardMaterial({
        color: 0xe2e8f0,
        metalness: 0.95,
        roughness: 0.1,
        side: THREE.FrontSide
    });
    const holeMesh = new THREE.Mesh(holeGeo, holeMat);
    holeMesh.rotation.x = -Math.PI / 2;
    holeMesh.position.y = 0.028;
    vinylLabelGroup.add(holeMesh);

    vinylMesh.add(vinylLabelGroup);
}

function updateVinylTexture(imageUrl) {
    if (!vinylMesh) return;
    ensureVinylLabelStructure();

    const normalizedUrl = normalizeImageUrl(imageUrl);
    const requestId = ++activeVinylRequestId;

    const applyTextureToMesh = (texture) => {
        // Guard token: Abaikan respon yang terlambat jika user sudah berpindah ke lagu lain
        if (requestId !== activeVinylRequestId) {
            return;
        }

        configureThreeTexture(texture);

        if (vinylLabelMesh) {
            vinylLabelMesh.material.map = texture;
            vinylLabelMesh.material.needsUpdate = true;
            vinylLabelMesh.visible = true;
        }
    };

    // 1. Cek Memory Cache (0 milidetik swap seketika tanpa delay)
    if (vinylTextureCache.has(normalizedUrl)) {
        applyTextureToMesh(vinylTextureCache.get(normalizedUrl));
        return;
    }

    // 2. Cek apakah kover gambar sudah pernah dimuat oleh DOM peramban di halaman
    // Jika gambar sudah ada di memori browser, buat tekstur seketika (0ms)
    const cleanPath = normalizedUrl.replace(/^\//, '');
    const domImg = document.querySelector(`img[src="${normalizedUrl}"], img[src="/${cleanPath}"], img[src="${cleanPath}"]`);
    if (domImg && domImg.complete && domImg.naturalWidth > 0) {
        try {
            const instantTex = new THREE.CanvasTexture(domImg);
            vinylTextureCache.set(normalizedUrl, instantTex);
            applyTextureToMesh(instantTex);
            return;
        } catch (e) {
            // fallback jika ada batasan cross-origin lokal
        }
    }

    // 3. Muat tekstur via TextureLoader dengan request ID guarding ketat
    threeTextureLoader.load(
        normalizedUrl,
        (loadedTexture) => {
            // Jika user sudah berpindah lagu saat gambar ini masih loading, buang tekstur lama
            if (requestId !== activeVinylRequestId) {
                loadedTexture.dispose();
                return;
            }
            vinylTextureCache.set(normalizedUrl, loadedTexture);
            applyTextureToMesh(loadedTexture);
        },
        undefined,
        (err) => {
            console.warn('Gagal memuat tekstur vinyl:', normalizedUrl, err);
            if (requestId === activeVinylRequestId && normalizedUrl !== '/image/queen1.jpg') {
                updateVinylTexture('/image/queen1.jpg');
            }
        }
    );
}

// Sinkronisasi seluruh informasi visual 3D Vinyl & Live Lyrics (judul, artis, kover, tekstur piringan) secara terpusat
function updateVinylDisplay(title, artist, cover, options = {}) {
    const finalCover = normalizeImageUrl(cover);
    const vinylTitle = document.getElementById('vinylSongTitle');
    const vinylArtist = document.getElementById('vinylSongArtist');
    const vinylCover = document.getElementById('vinylModalCover');
    const lyricsTitle = document.getElementById('lyricsSongTitle');
    const lyricsArtist = document.getElementById('lyricsSongArtist');
    const lyricsCover = document.getElementById('lyricsModalCover');

    if (vinylTitle) vinylTitle.textContent = title || 'Putar Musik';
    if (vinylArtist) vinylArtist.textContent = artist || '-';
    if (vinylCover) vinylCover.src = finalCover;
    if (lyricsTitle) lyricsTitle.textContent = title || 'Putar Musik';
    if (lyricsArtist) lyricsArtist.textContent = artist || '-';
    if (lyricsCover) lyricsCover.src = finalCover;

    updateVinylTexture(finalCover);

    if (currentSong && !options.skipLyricsLoad) {
        if (currentLyricsSongId !== currentSong.id || currentLyricsLines.length === 0) {
            loadSongLyrics(currentSong);
        } else {
            syncActiveLyricLine(getCurrentAudioTime(), true);
        }
    }
}

function onWindowResize() {
    const container = document.getElementById('vinyl3dContainer');
    if (!container || !threeCamera || !threeRenderer) return;
    const width = container.clientWidth;
    const height = container.clientHeight;
    threeCamera.aspect = width / height;
    threeCamera.updateProjectionMatrix();
    threeRenderer.setSize(width, height);
}

function closeOtherMultimediaModals(targetModalId) {
    let capturedVideoTime = null;
    const videoModal = document.getElementById('videoModal');
    if (targetModalId !== 'videoModal' && isModalTrulyOpen(videoModal)) {
        const wasVideoIframeMode = (currentVideoModalMode === 'video');
        capturedVideoTime = wasVideoIframeMode ? getActiveVideoModalTime() : null;
        closeVideoModal(true);
    }
    const vinylModal = document.getElementById('vinylModal');
    if (targetModalId !== 'vinylModal' && isModalTrulyOpen(vinylModal)) {
        closeVinylModal();
    }
    const lyricsModal = document.getElementById('lyricsModal');
    if (targetModalId !== 'lyricsModal' && isModalTrulyOpen(lyricsModal)) {
        closeLyricsModal();
    }
    const musicModal = document.getElementById('musicModal');
    if (targetModalId !== 'musicModal' && isModalTrulyOpen(musicModal)) {
        closeMusicModal();
    }
    return capturedVideoTime;
}

function openVinylModal() {
    const modal = document.getElementById('vinylModal');
    if (!modal) return;

    const resumeTime = closeOtherMultimediaModals('vinylModal');
    if (resumeTime !== null && currentSong && currentSong.id) {
        ensureSongContinuingSmoothly(currentSong.id, currentSong.title, currentSong.artist, currentSong.cover, currentSong.audioUrl, currentSong.videoId, resumeTime);
    }

    modal.classList.remove('modal-closing');
    modal.classList.add('active');
    lockBodyScroll();

    if (!currentSong) {
        if (currentSongQueue && currentSongQueue.length > 0) {
            const first = currentSongQueue[0];
            playSong(first.id, first.title, first.artist, first.image ? (first.image.startsWith('/') ? first.image : '/' + first.image) : '/image/queen1.jpg', first.audio_url || '', first.video_id || '');
        } else {
            playSong(1, 'Bohemian Rhapsody', 'Queen', '/image/queen1.jpg', '/audio/1.mp3', 'fJ9rUzIMcZQ');
        }
    } else {
        updateVinylDisplay(currentSong.title, currentSong.artist, currentSong.cover);
        updatePlayIcons(isAudioPlaying);
    }

    setTimeout(onWindowResize, 50);
}

function closeVinylModal() {
    const modal = document.getElementById('vinylModal');
    if (!isModalTrulyOpen(modal)) return;
    modal.classList.add('modal-closing');
    updatePlayIcons(isAudioPlaying);
    setTimeout(() => {
        modal.classList.remove('active', 'modal-closing');
        unlockBodyScroll();
    }, 220);
}

function openVinylWithSong(title, artist, cover, id, audioUrl, videoId) {
    const finalCover = normalizeImageUrl(cover);
    const sameAsVideo = Boolean(activeVideoSong && id && Number(activeVideoSong.id) === Number(id));
    const resumeTime = closeOtherMultimediaModals('vinylModal');

    // 1. Lanjutkan lagu tanpa restart jika sudah diputar, atau mulai putar jika lagu baru
    if (id) {
        ensureSongContinuingSmoothly(id, title, artist, finalCover, audioUrl, videoId, sameAsVideo ? resumeTime : null);
    } else {
        currentSong = { id: null, title, artist, cover: finalCover, audioUrl, videoId };
        updateVinylDisplay(title, artist, finalCover);
    }

    // 2. Buka modal piringan hitam 3D
    openVinylModal();
}

/* ==================== 3.5 LIVE SYNCED LYRICS MODAL (SPOTIFY / YT MUSIC STYLE) ==================== */
window.lyricsCache = window.lyricsCache || {};
let currentLyricsLines = [];
let currentLyricsSongId = null;
let activeLyricIndex = -1;
let lyricsSyncInterval = null;
let activeLyricsRequestId = 0;

function openLyricsWithSong(title, artist, cover, id, audioUrl, videoId) {
    const finalCover = normalizeImageUrl(cover);
    const sameAsVideo = Boolean(activeVideoSong && id && Number(activeVideoSong.id) === Number(id));
    const resumeTime = closeOtherMultimediaModals('lyricsModal');

    // 1. Lanjutkan lagu dari detik & baris lirik yang sedang didengarkan (tanpa mengulang dari awal)
    if (id) {
        ensureSongContinuingSmoothly(id, title, artist, finalCover, audioUrl, videoId, sameAsVideo ? resumeTime : null);
    } else if (!currentSong) {
        currentSong = { id: id || 1, title, artist, cover: finalCover, audioUrl, videoId };
        updateVinylDisplay(title, artist, finalCover);
    }

    // 2. Buka modal Live Synced Lyrics
    openLyricsModal();
}

function openLyricsModal() {
    const modal = document.getElementById('lyricsModal');
    if (!modal) return;

    const resumeTime = closeOtherMultimediaModals('lyricsModal');
    if (resumeTime !== null && currentSong && currentSong.id) {
        ensureSongContinuingSmoothly(currentSong.id, currentSong.title, currentSong.artist, currentSong.cover, currentSong.audioUrl, currentSong.videoId, resumeTime);
    }

    modal.classList.remove('modal-closing');
    modal.classList.add('active');
    lockBodyScroll();

    if (!currentSong) {
        // Jika belum ada lagu yang dipilih, ambil lagu pertama dari antrean atau Bohemian Rhapsody
        if (currentSongQueue && currentSongQueue.length > 0) {
            const first = currentSongQueue[0];
            playSong(first.id, first.title, first.artist, first.image ? (first.image.startsWith('/') ? first.image : '/' + first.image) : '/image/queen1.jpg', first.audio_url || '', first.video_id || '');
        } else {
            playSong(1, 'Bohemian Rhapsody', 'Queen', '/image/queen1.jpg', '/audio/1.mp3', 'fJ9rUzIMcZQ');
        }
    } else {
        const lyricsTitle = document.getElementById('lyricsSongTitle');
        const lyricsArtist = document.getElementById('lyricsSongArtist');
        const lyricsCover = document.getElementById('lyricsModalCover');
        if (lyricsTitle) lyricsTitle.textContent = currentSong.title || 'Putar Musik';
        if (lyricsArtist) lyricsArtist.textContent = currentSong.artist || '-';
        if (lyricsCover) lyricsCover.src = normalizeImageUrl(currentSong.cover);

        if (currentLyricsSongId !== currentSong.id || currentLyricsLines.length === 0) {
            loadSongLyrics(currentSong);
        } else {
            syncActiveLyricLine(getCurrentAudioTime(), true);
        }
        updatePlayIcons(isAudioPlaying);
    }

    startLyricsSyncLoop();
}

function closeLyricsModal() {
    const modal = document.getElementById('lyricsModal');
    if (!isModalTrulyOpen(modal)) return;
    stopLyricsSyncLoop();
    modal.classList.add('modal-closing');
    // Pertahankan audio tetap menyala di bottom player saat keluar dari modal lirik
    updatePlayIcons(isAudioPlaying);
    setTimeout(() => {
        modal.classList.remove('active', 'modal-closing');
        unlockBodyScroll();
    }, 220);
}

function getCurrentAudioTime() {
    if (currentPlaybackMode === 'youtube' && ytAudioPlayer && isYtReady) {
        try {
            return ytAudioPlayer.getCurrentTime() || 0;
        } catch (e) {}
    }
    const audio = document.getElementById('globalAudioPlayer');
    if (audio && !isNaN(audio.currentTime)) {
        return audio.currentTime || 0;
    }
    return 0;
}

function getCurrentAudioDuration() {
    if (currentPlaybackMode === 'youtube' && ytAudioPlayer && isYtReady) {
        try {
            const d = ytAudioPlayer.getDuration();
            if (d && d > 0) return d;
        } catch (e) {}
    }
    const audio = document.getElementById('globalAudioPlayer');
    const sameSongLoaded = !currentSong || !currentSong.id || (audio && audio.dataset.songId === String(currentSong.id) && audio.readyState >= 1);
    if (sameSongLoaded && audio && audio.duration && !isNaN(audio.duration)) {
        return audio.duration;
    }
    if (currentSong && currentSong.id && window.songDataCache && window.songDataCache[currentSong.id]) {
        const dStr = window.songDataCache[currentSong.id].duration;
        if (dStr && dStr.includes(':')) {
            const parts = dStr.split(':');
            return (parseInt(parts[0]) || 0) * 60 + (parseInt(parts[1]) || 0);
        }
    }
    return 210;
}

function startLyricsSyncLoop() {
    stopLyricsSyncLoop();
    lyricsSyncInterval = setInterval(() => {
        const modal = document.getElementById('lyricsModal');
        if (!modal || !modal.classList.contains('active')) {
            stopLyricsSyncLoop();
            return;
        }
        if (isAudioPlaying && !isSeekingAudio) {
            syncActiveLyricLine(getCurrentAudioTime(), false);
        }
    }, 80);
}

function stopLyricsSyncLoop() {
    if (lyricsSyncInterval) {
        clearInterval(lyricsSyncInterval);
        lyricsSyncInterval = null;
    }
}

function parseLrcLyrics(lrcText, plainText, totalDuration) {
    const lines = [];
    if (lrcText && typeof lrcText === 'string') {
        const rawLines = lrcText.split(/\r?\n/);
        const timeRegex = /\[(\d{1,2}):(\d{2})(?:\.(\d{1,3}))?\]/g;

        rawLines.forEach(raw => {
            const matches = [...raw.matchAll(timeRegex)];
            if (matches.length > 0) {
                const text = raw.replace(timeRegex, '').trim();
                matches.forEach(m => {
                    const mins = parseInt(m[1], 10) || 0;
                    const secs = parseInt(m[2], 10) || 0;
                    let ms = 0;
                    if (m[3]) {
                        ms = parseInt(m[3].padEnd(3, '0').slice(0, 3), 10) / 1000;
                    }
                    const timeSec = mins * 60 + secs + ms;
                    lines.push({
                        time: timeSec,
                        text: text || '♪ • • • ♪',
                        isInstrumental: !text
                    });
                });
            }
        });

        lines.sort((a, b) => a.time - b.time);

        // Jika baris lirik pertama baru mulai di atas detik ke-4, tambahkan baris Intro di 0.0s
        if (lines.length > 0 && lines[0].time > 4.0) {
            lines.unshift({
                time: 0,
                text: '♪ Intro Musik ♪',
                isInstrumental: true
            });
        }
    }

    if (lines.length === 0 && plainText && typeof plainText === 'string') {
        const rawPlain = plainText.split(/\r?\n/).map(s => s.trim()).filter(Boolean);
        const dur = totalDuration > 30 ? totalDuration : 200;
        const step = Math.max(3.5, (dur - 12) / Math.max(1, rawPlain.length));
        lines.push({ time: 0, text: '♪ Intro Musik ♪', isInstrumental: true });
        rawPlain.forEach((txt, idx) => {
            lines.push({
                time: Math.round((5 + idx * step) * 10) / 10,
                text: txt,
                isInstrumental: false
            });
        });
    }

    return lines;
}

function loadSongLyrics(song) {
    if (!song || !song.title) return;
    const container = document.getElementById('lyricsScrollContainer');

    const cacheKey = song.id || `${song.title}_${song.artist}`;
    if (window.lyricsCache[cacheKey]) {
        applyLyricsPayload(window.lyricsCache[cacheKey], song);
        return;
    }

    if (container) {
        container.innerHTML = `
            <div class="py-16 flex flex-col items-center justify-center text-[#9aa1b1]">
                <div class="w-8 h-8 border-2 border-[#00b24b] border-t-transparent rounded-full animate-spin mb-3"></div>
                <p class="text-xs font-semibold">Menyinkronkan lirik asli untuk <span class="text-[#f0f2f5] font-semibold">${escapeHtml(song.title)}</span>...</p>
            </div>
        `;
    }

    const reqId = ++activeLyricsRequestId;
    const dur = Math.round(getCurrentAudioDuration() || 0);

    // Prioritaskan file JSON lirik statis (/lyrics/{id}.json) untuk respon instan <1ms tanpa antrean PHP
    const fetchLyricsData = song.id
        ? fetch(`/lyrics/${song.id}.json?v=5`)
            .then(r => (r.ok ? r.json() : Promise.reject()))
            .then(staticData => {
                if (staticData && (staticData.syncedLyrics || staticData.plainLyrics || staticData.instrumental)) {
                    return staticData;
                }
                return fetch(`/api/musics/${song.id}/lyrics?duration=${dur}`).then(r => r.json());
            })
            .catch(() => fetch(`/api/musics/${song.id}/lyrics?duration=${dur}`).then(r => r.json()))
        : fetch(`https://lrclib.net/api/search?q=${encodeURIComponent(song.title + ' ' + song.artist)}`).then(r => r.json());

    fetchLyricsData
        .then(data => {
            if (reqId !== activeLyricsRequestId) return;

            // Jika response dari file statis atau endpoint Laravel kita
            if (data && (data.syncedLyrics || data.plainLyrics || data.instrumental)) {
                window.lyricsCache[cacheKey] = data;
                applyLyricsPayload(data, song);
                return;
            }

            // Fallback langsung ke LRCLIB dari browser jika diperlukan
            const cleanTitle = song.title.replace(/\s*[\(\[].*?[\)\]]/g, '').trim();
            const cleanArtist = (song.artist || '').replace(/\s+(ft\.?|feat\.?|featuring|&|x|with)\s+.*$/i, '').trim();
            return fetch(`https://lrclib.net/api/search?q=${encodeURIComponent(cleanTitle + ' ' + cleanArtist)}`)
                .then(r => r.json())
                .then(list => {
                    if (reqId !== activeLyricsRequestId) return;
                    if (Array.isArray(list) && list.length > 0) {
                        const syncedList = list.filter(item => item.syncedLyrics);
                        const chosen = syncedList.length > 0 ? syncedList[0] : list[0];
                        const payload = {
                            id: song.id,
                            title: song.title,
                            artist: song.artist,
                            synced: Boolean(chosen.syncedLyrics),
                            syncedLyrics: chosen.syncedLyrics || null,
                            plainLyrics: chosen.plainLyrics || null
                        };
                        window.lyricsCache[cacheKey] = payload;
                        applyLyricsPayload(payload, song);
                    } else {
                        applyLyricsPayload({ synced: false, syncedLyrics: null, plainLyrics: null }, song);
                    }
                });
        })
        .catch(() => {
            if (reqId !== activeLyricsRequestId) return;
            applyLyricsPayload({ synced: false, syncedLyrics: null, plainLyrics: null }, song);
        });
}

function applyLyricsPayload(payload, song) {
    currentLyricsSongId = song.id;
    const dur = getCurrentAudioDuration();

    currentLyricsLines = parseLrcLyrics(payload.syncedLyrics, payload.plainLyrics, dur);

    renderLyricsLines(song);
    activeLyricIndex = -1;
    syncActiveLyricLine(getCurrentAudioTime(), true);
}

function renderLyricsLines(song) {
    const container = document.getElementById('lyricsScrollContainer');
    if (!container) return;

    if (!currentLyricsLines || currentLyricsLines.length === 0) {
        container.innerHTML = `
            <div class="py-14 px-4 text-center space-y-2">
                <div class="w-12 h-12 mx-auto mb-2 text-[#0075ff]">
                    <svg class="w-full h-full" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
                </div>
                <p class="text-[#f0f2f5] font-extrabold text-base">${escapeHtml(song.title)} — ${escapeHtml(song.artist)}</p>
                <p class="text-[#9aa1b1] text-xs max-w-md mx-auto leading-relaxed">
                    Komposisi ini merupakan karya instrumental atau lirik berwaktu belum tersedia untuk lagu ini. Nikmati alunan instrumen musiknya!
                </p>
            </div>
        `;
        return;
    }

    container.innerHTML = currentLyricsLines.map((line, index) => `
        <div id="lyric-line-${index}"
             onclick="seekToLyricTime(${line.time}, ${index})"
             title="Klik untuk memutar dari baris ini"
             class="lyric-line cursor-pointer px-4 py-2 rounded-xl transition-colors duration-200 flex items-center justify-center text-slate-500 hover:text-slate-200 hover:bg-white/5">
            <p class="lyric-text text-center font-semibold text-base sm:text-lg leading-snug transition-all duration-200 text-slate-500 ${line.isInstrumental ? 'italic tracking-widest' : ''}">
                ${escapeHtml(line.text)}
            </p>
        </div>
    `).join('');
}

function syncActiveLyricLine(currentSeconds, forceInstantScroll = false, forcedIndex = null) {
    if (!currentLyricsLines || currentLyricsLines.length === 0) return;

    let newIndex = 0;
    if (typeof forcedIndex === 'number' && forcedIndex >= 0 && forcedIndex < currentLyricsLines.length) {
        newIndex = forcedIndex;
    } else {
        // Waktu efektif dengan antisipasi 0.32 detik agar highlight lirik tepat bersamaan saat penyanyi mulai membuka suara tanpa delay
        const effectiveTime = Math.max(0, (parseFloat(currentSeconds) || 0) + 0.32);
        for (let i = 0; i < currentLyricsLines.length; i++) {
            if (effectiveTime >= currentLyricsLines[i].time) {
                newIndex = i;
            } else {
                break;
            }
        }
    }

    if (newIndex === activeLyricIndex && !forceInstantScroll) {
        return;
    }

    activeLyricIndex = newIndex;
    const container = document.getElementById('lyricsScrollContainer');
    if (!container) return;

    currentLyricsLines.forEach((_, idx) => {
        const el = document.getElementById(`lyric-line-${idx}`);
        if (!el) return;
        const textEl = el.querySelector('.lyric-text');

        if (idx === activeLyricIndex) {
            el.className = 'lyric-line active-lyric-line cursor-pointer px-4 py-2.5 rounded-xl transition-colors duration-200 flex items-center justify-center bg-white/[0.03]';
            if (textEl) {
                textEl.className = 'lyric-text text-center font-black text-xl sm:text-2xl text-white scale-[1.03] leading-snug transition-all duration-200';
            }
        } else {
            el.className = 'lyric-line cursor-pointer px-4 py-2 rounded-xl transition-colors duration-200 flex items-center justify-center hover:bg-white/5 group';
            if (textEl) {
                textEl.className = 'lyric-text text-center font-semibold text-base sm:text-lg text-slate-500 group-hover:text-slate-200 leading-snug transition-all duration-200';
            }
        }
    });

    // Auto-scroll baris lirik yang sedang dinyanyikan tepat ke tengah-tengah layar modal
    const activeEl = document.getElementById(`lyric-line-${activeLyricIndex}`);
    if (activeEl && container) {
        const targetScrollTop = activeEl.offsetTop - (container.clientHeight / 2) + (activeEl.clientHeight / 2);
        container.scrollTo({
            top: Math.max(0, targetScrollTop),
            behavior: forceInstantScroll ? 'smooth' : 'smooth'
        });
    }
}

let lyricSeekUnlockTimer = null;

function seekToLyricTime(lyricTimeSec, clickedIndex = null) {
    const rawTarget = Math.max(0, parseFloat(lyricTimeSec) || 0);

    // Kunci sync loop sejenak selama proses HTTP 206 byte-range seek agar highlight tidak melompat balik ke baris lama
    isSeekingAudio = true;
    if (lyricSeekUnlockTimer) clearTimeout(lyricSeekUnlockTimer);
    const releaseSeekLock = () => {
        if (lyricSeekUnlockTimer) {
            clearTimeout(lyricSeekUnlockTimer);
            lyricSeekUnlockTimer = null;
        }
        isSeekingAudio = false;
    };
    lyricSeekUnlockTimer = setTimeout(releaseSeekLock, 350);

    if (currentPlaybackMode === 'youtube' && ytAudioPlayer && isYtReady) {
        try {
            const dur = ytAudioPlayer.getDuration() || getCurrentAudioDuration();
            const targetSec = dur > 0 ? Math.min(rawTarget, Math.max(0, dur - 0.2)) : rawTarget;
            ytAudioPlayer.seekTo(targetSec, true);
            if (!isAudioPlaying) {
                ytAudioPlayer.playVideo();
                isAudioPlaying = true;
                updatePlayIcons(true);
                startYtProgressTimer();
            }
            if (dur > 0) {
                const p = (targetSec / dur) * 100;
                const progressBar = document.getElementById('playerProgressBar');
                if (progressBar) progressBar.value = p;
                updateProgressBarVisual(p);
                updateTimeDisplays(targetSec, dur);
            }
            syncActiveLyricLine(targetSec, true, clickedIndex);
            return;
        } catch (e) {}
    }

    const audio = document.getElementById('globalAudioPlayer');
    if (audio) {
        const applyHtml5Seek = () => {
            const dur = (audio.duration && !isNaN(audio.duration)) ? audio.duration : getCurrentAudioDuration();
            const targetSec = dur > 0 ? Math.min(rawTarget, Math.max(0, dur - 0.1)) : rawTarget;

            const onSeeked = () => {
                audio.removeEventListener('seeked', onSeeked);
                releaseSeekLock();
            };
            audio.addEventListener('seeked', onSeeked, { once: true });

            try {
                audio.currentTime = targetSec;
            } catch (err) {
                console.warn('Audio seek notice:', err);
            }

            if (audio.paused) {
                audio.play().then(() => {
                    isAudioPlaying = true;
                    updatePlayIcons(true);
                }).catch(() => {});
            }

            if (dur > 0) {
                const p = (targetSec / dur) * 100;
                const progressBar = document.getElementById('playerProgressBar');
                if (progressBar) progressBar.value = p;
                updateProgressBarVisual(p);
                updateTimeDisplays(targetSec, dur);
            }
            syncActiveLyricLine(targetSec, true, clickedIndex);
        };

        if (!audio.src && currentSong && currentSong.id) {
            currentPlaybackMode = 'html5';
            audio.src = `/api/musics/${currentSong.id}/stream`;
            audio.addEventListener('loadedmetadata', applyHtml5Seek, { once: true });
            audio.load();
            return;
        }

        if (audio.readyState < 1) {
            audio.addEventListener('loadedmetadata', applyHtml5Seek, { once: true });
            audio.load();
            return;
        }

        applyHtml5Seek();
    } else {
        releaseSeekLock();
    }
}

/* ==================== 4. CATALOG & FILTER API (PAGINATION MULTI-HALAMAN) ==================== */
let currentCatalogPage = 1;

function fetchMusics(page = 1, smoothScroll = false) {
    currentCatalogPage = page;
    const sort = document.getElementById('discoverSortSelect')?.value || currentFilter.sort;
    currentFilter.sort = sort;

    if (smoothScroll) {
        const target = document.getElementById('discoverTitle') || document.getElementById('discoverGrid');
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    const url = `/api/musics/search?keyword=${encodeURIComponent(currentFilter.keyword)}&genre=${encodeURIComponent(currentFilter.genre)}&decade=${encodeURIComponent(currentFilter.decade)}&sort=${encodeURIComponent(sort)}&page=${page}&per_page=20`;

    const grid = document.getElementById('discoverGrid');
    if (!grid) return;
    grid.innerHTML = '<div class="col-span-full py-16 text-center text-[#9aa1b1]"><div class="inline-block w-8 h-8 border-2 border-[#00b24b] border-t-transparent rounded-full animate-spin mb-3"></div><p class="text-sm font-semibold">Memuat lagu untuk halaman ini...</p></div>';

    fetch(url)
        .then(res => res.json())
        .then(data => {
            const songs = data.data || [];
            currentSongQueue = songs;
            window.songDataCache = window.songDataCache || {};
            songs.forEach(s => {
                window.songDataCache[s.id] = s;
            });

            // Pre-warm tekstur vinyl 3D secara hening di latar belakang
            if (typeof window.requestIdleCallback === 'function') {
                window.requestIdleCallback(() => {
                    songs.slice(0, 8).forEach(s => {
                        if (s.image) {
                            const norm = normalizeImageUrl(s.image);
                            if (!vinylTextureCache.has(norm)) {
                                threeTextureLoader.load(norm, (tex) => {
                                    configureThreeTexture(tex);
                                    vinylTextureCache.set(norm, tex);
                                }, undefined, () => {});
                            }
                        }
                    });
                });
            }

            const countBadge = document.getElementById('discoverTotalCount');
            if (countBadge) {
                countBadge.textContent = `${data.total || songs.length} Lagu`;
            }

            if (songs.length === 0) {
                grid.innerHTML = '<div class="col-span-full py-12 text-center text-slate-400">Tidak ada musik yang cocok dengan filter pencarian.</div>';
                const pag = document.getElementById('discoverPagination');
                if (pag) pag.innerHTML = '';
                return;
            }

            grid.innerHTML = songs.map((song, idx) => `
                <div class="game-tile song-card-animated bg-[#2e3035] rounded-[16px] border border-[#2d2f33] overflow-hidden transition-colors duration-200 group flex flex-col justify-between" style="animation-delay: ${Math.min(idx * 22, 260)}ms;">
                    <!-- Top: Full-Bleed Square Artwork inside 16px rounded corners (design.md game-tile) -->
                    <div class="relative w-full aspect-square bg-[#222428] overflow-hidden cursor-pointer" onclick="openMusicModal(${song.id})">
                        <img src="${song.image ? '/' + song.image : '/image/queen1.jpg'}" alt="${song.title}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <!-- Overlay Play, 3D, Live Lyrics & Video MV Buttons -->
                        <div class="absolute inset-0 bg-black/65 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <button onclick="event.stopPropagation(); playSong(${song.id}, '${escapeQuote(song.title)}', '${escapeQuote(song.artist)}', '/${song.image || 'image/queen1.jpg'}', '${song.audio_url || ''}', '${song.video_id || ''}')" class="w-11 h-11 p-0 leading-none bg-[#00b24b] hover:bg-[#00c853] text-white rounded-full flex items-center justify-center font-bold transition-transform hover:scale-110" title="Putar Lagu">
                                <svg class="w-5 h-5 fill-current block m-auto" viewBox="0 0 24 24"><path d="M7.5 5.5v13l10-6.5z"/></svg>
                            </button>
                            <button onclick="event.stopPropagation(); openVinylWithSong('${escapeQuote(song.title)}', '${escapeQuote(song.artist)}', '/${song.image || 'image/queen1.jpg'}', ${song.id}, '${song.audio_url || ''}', '${song.video_id || ''}')" class="w-9 h-9 bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] rounded-full flex items-center justify-center transition-transform hover:scale-110" title="Putar di Piringan 3D">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M16 3.937A9 9 0 1 0 21 12"/><path d="M11 12a1 1 0 1 0 2 0a1 1 0 1 0-2 0m8-8a1 1 0 1 0 2 0a1 1 0 1 0-2 0"/><path d="m20 4l-3.5 10l-2.5 2"/></g></svg>
                            </button>
                            <button onclick="event.stopPropagation(); openLyricsWithSong('${escapeQuote(song.title)}', '${escapeQuote(song.artist)}', '/${song.image || 'image/queen1.jpg'}', ${song.id}, '${song.audio_url || ''}', '${song.video_id || ''}')" class="w-9 h-9 bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] rounded-full flex items-center justify-center transition-transform hover:scale-110" title="Putar dengan Lirik">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M470.8 365.6c-.9-53.7-1.6-107.4-2.4-161.1c-.9-63.6-1.7-127.3-2.6-190.9c-.1-9.9-6.6-15.2-15.9-13.1c-25.8 5.9-51.7 11.7-77.5 17.5C306.7 32.7 241 47.5 175.3 62.2c-9.1 2-12.2 6.1-12.1 15.5c.2 7.5.3 14.9.4 22.4c1.3 90.7 2.5 181.4 3.7 272.1v9.8c-2.5-1.9-4.4-3.5-6.4-5c-17-12.6-36.1-17.4-56.9-14c-51.5 8.5-78.6 64.8-53.4 110.5c15.3 27.7 48.4 43.4 78.8 37.3c38-7.6 62.5-38.6 61.7-77.8c-.4-20.2-.8-40.4-1.1-60.7l-3-224.1c-.1-4.6 0-9.2 0-14c85.6-19.2 171.1-38.4 256.8-57.6c1.1 81.6 2.2 162.6 3.3 244.4c-1.7-1.3-2.6-2.1-3.6-2.9c-17.3-13.9-37.1-19.3-58.9-16c-54.2 8.2-83.1 71.4-49.5 117.3c17.9 24.4 42.6 35.1 72.6 30.4c30.3-4.8 50.1-23 59.9-52c3.6-10.6 3.4-21.4 3.2-32.2M115.6 489.4c-28.6.1-51.7-23.7-51.6-52.8s23.6-52.6 53-52.2c27.9.4 51.4 22.4 51.2 53.2c-.2 29.6-23.7 51.8-52.6 51.8m327.7-438c0 .9-2.1 2.3-3.4 2.6c-12.5 3-25 5.8-37.5 8.6c-20 4.5-40.1 8.9-60.1 13.4c-41.4 9.3-82.8 18.5-124.3 27.8c-9.6 2.2-19.3 4.3-28.9 6.5c-.6.1-1.2.1-3 .3c0-8.8-.1-17.4.2-25.9c0-.9 2.2-2.2 3.5-2.5c14.7-3.4 29.5-6.7 44.3-10c19.8-4.4 39.5-8.8 59.3-13.3l113.7-25.5c10.6-2.4 21.2-4.9 31.9-7.3c1.1-.2 2.2-.2 4.4-.4c.1 8.9.2 17.3-.1 25.7m-47.4 377c-28.1 0-52-24.1-52-52.4c0-28.6 23.8-52.7 52.1-52.7c28.9 0 52.3 23.7 52.3 53.1c-.2 28.3-24 52-52.4 52"/><path fill="currentColor" d="M64 436.6c.1-29.1 23.6-52.6 53-52.2c27.9.4 51.4 22.4 51.2 53.2c-.2 29.6-23.7 51.8-52.6 51.8c-28.6.1-51.7-23.7-51.6-52.8m384.2-60.2c0-29.4-23.3-53.1-52.3-53.1c-28.3 0-52.1 24.1-52.1 52.7c0 28.2 24 52.4 52 52.4c28.5 0 52.3-23.7 52.4-52m-259-266c9.6-2.2 19.3-4.3 28.9-6.5c41.4-9.3 82.8-18.6 124.3-27.8c20-4.5 40.1-8.9 60.1-13.4c12.5-2.8 25-5.6 37.5-8.6c1.3-.3 3.4-1.7 3.4-2.6c.3-8.4.2-16.9.2-25.8c-2.2.2-3.3.1-4.4.4c-10.6 2.4-21.2 4.9-31.9 7.3L293.6 58.9c-19.8 4.4-39.5 8.8-59.3 13.3c-14.8 3.3-29.5 6.6-44.3 10c-1.4.3-3.5 1.6-3.5 2.5c-.3 8.6-.2 17.1-.2 25.9c1.7-.1 2.3-.1 2.9-.2" opacity=".7"/></svg>
                            </button>
                            <button onclick="event.stopPropagation(); openVideoModal(${song.id}, '${escapeQuote(song.title)}', '${escapeQuote(song.artist)}', '${song.video_id || ''}', '${song.image ? (song.image.startsWith('/') ? song.image : '/' + song.image) : '/image/queen1.jpg'}')" class="w-9 h-9 bg-[#2e3035] hover:bg-red-600 text-red-400 hover:text-white border border-[#2d2f33] rounded-full flex items-center justify-center transition-transform hover:scale-110" title="Tonton Video MV">
                                <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>youtube</title><path fill="currentColor" d="m10 15l5.19-3L10 9zm11.56-7.83c.13.47.22 1.1.28 1.9c.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83c-.25.9-.83 1.48-1.73 1.73c-.47.13-1.33.22-2.65.28c-1.3.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44c-.9-.25-1.48-.83-1.73-1.73c-.13-.47-.22-1.1-.28-1.9c-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83c.25-.9.83-1.48 1.73-1.73c.47-.13 1.33-.22 2.65-.28c1.3-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44c.9.25 1.48.83 1.73 1.73"/></svg>
                            </button>
                        </div>
                        <span class="absolute top-2.5 right-2.5 bg-[#141415]/90 px-2 py-0.5 rounded-[4px] text-[12px] font-semibold text-yellow-400 border border-[#2d2f33]">★ ${song.rating}</span>
                    </div>

                    <!-- Bottom Metadata Interior (p-3.5) -->
                    <div class="p-3.5 flex-1 flex flex-col justify-between">
                        <div>
                            <h3 class="font-semibold text-[#f0f2f5] text-[16px] leading-[1.3] truncate cursor-pointer hover:text-[#00b24b] transition-colors" onclick="openMusicModal(${song.id})">${song.title}</h3>
                            <p class="text-[12px] font-semibold text-[#9aa1b1] leading-[1.3] truncate mt-1">${song.artist} • ${song.genre}</p>
                        </div>
                        <div class="mt-3 pt-2.5 border-t border-[#2d2f33] flex items-center justify-between text-[12px] font-semibold text-[#9aa1b1]">
                            <span>${song.year}</span>
                            <button onclick="openMusicModal(${song.id})" class="text-[#0075ff] hover:underline font-semibold">Detail & Analisis →</button>
                        </div>
                    </div>
                </div>
            `).join('');

            // Render Navigasi Halaman (Pagination)
            renderDiscoverPagination(data);
        })
        .catch(err => {
            console.error(err);
            grid.innerHTML = '<div class="col-span-full py-12 text-center text-red-400">Gagal memuat musik.</div>';
        });
}

function renderDiscoverPagination(data) {
    const pag = document.getElementById('discoverPagination');
    if (!pag) return;

    const total = data.total || 0;
    const cur = data.current_page || 1;
    const last = data.last_page || 1;
    const from = data.from || 1;
    const to = data.to || total;

    if (last <= 1) {
        pag.innerHTML = `
            <div class="flex items-center justify-between text-xs text-slate-400 w-full">
                <span>Menampilkan seluruh <strong>${total}</strong> lagu</span>
            </div>
        `;
        return;
    }

    // Tampilkan maksimal 4 nomor halaman saja (sliding window murni tanpa "... 10")
    const maxVisiblePages = 4;
    let startPage = Math.max(1, cur - 1);
    let endPage = Math.min(last, startPage + maxVisiblePages - 1);
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    const pages = [];
    for (let i = startPage; i <= endPage; i++) {
        pages.push(i);
    }

    const pagesHtml = pages.map(p => {
        if (p === '...') {
            return `<span class="px-2 py-1 text-[#53575f] font-bold select-none text-xs">...</span>`;
        }
        if (p === cur) {
            return `
                <button class="w-9 h-9 rounded-[10px] bg-[#00b24b] text-white font-extrabold text-[13px] transition-colors" title="Halaman ${p}">
                    ${p}
                </button>
            `;
        }
        return `
            <button onclick="fetchMusics(${p}, true)" class="w-9 h-9 rounded-[10px] bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] font-semibold text-[13px] transition-colors" title="Ke Halaman ${p}">
                ${p}
            </button>
        `;
    }).join('');

    const prevDisabled = cur <= 1;
    const nextDisabled = cur >= last;

    pag.innerHTML = `
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 w-full">
            <!-- Info Rentang Lagu & Halaman Aktif -->
            <div class="text-xs text-[#9aa1b1] text-center sm:text-left">
                Menampilkan <strong class="text-[#f0f2f5]">${from} - ${to}</strong> dari <strong class="text-[#00b24b] font-bold">${total}</strong> lagu 
                <span class="text-[#53575f] ml-1.5">(Halaman ${cur} dari ${last})</span>
            </div>

            <!-- Tombol Navigasi Halaman -->
            <div class="flex items-center space-x-1.5 select-none">
                <!-- Tombol Sebelumnya -->
                <button onclick="fetchMusics(${cur - 1}, true)" 
                    ${prevDisabled ? 'disabled' : ''} 
                    class="px-3 py-2 rounded-[10px] text-[13px] font-semibold flex items-center gap-1 transition-colors ${prevDisabled ? 'bg-[#2e3035]/40 text-[#53575f] border border-[#2d2f33] cursor-not-allowed opacity-50' : 'bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] cursor-pointer'}">
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    <span>Sebelumnya</span>
                </button>

                <!-- Angka Halaman -->
                <div class="flex items-center space-x-1">
                    ${pagesHtml}
                </div>

                <!-- Tombol Selanjutnya -->
                <button onclick="fetchMusics(${cur + 1}, true)" 
                    ${nextDisabled ? 'disabled' : ''} 
                    class="px-3 py-2 rounded-[10px] text-[13px] font-semibold flex items-center gap-1 transition-colors ${nextDisabled ? 'bg-[#2e3035]/40 text-[#53575f] border border-[#2d2f33] cursor-not-allowed opacity-50' : 'bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] cursor-pointer'}">
                    <span>Selanjutnya</span>
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                </button>
            </div>
        </div>
    `;
}

function setGenreFilter(genre, btn) {
    currentFilter.genre = genre;
    document.querySelectorAll('.genre-btn').forEach(b => {
        b.className = 'genre-btn px-3.5 py-1.5 rounded-full text-[13px] font-semibold transition-colors bg-[#2e3035] text-[#f0f2f5] hover:bg-[#383b42] border border-[#2d2f33]';
    });
    if (btn) {
        btn.className = 'genre-btn px-3.5 py-1.5 rounded-full text-[13px] font-semibold transition-colors bg-[#00b24b] text-white border border-[#00b24b]';
    }
    fetchMusics();
}

function setDecadeFilter(decade, btn) {
    currentFilter.decade = decade;
    document.querySelectorAll('.decade-btn').forEach(b => {
        b.className = 'decade-btn px-3 py-2 rounded-[8px] text-[13px] font-semibold transition-colors bg-[#2e3035] text-[#f0f2f5] hover:bg-[#383b42] border border-[#2d2f33]';
    });
    if (btn) {
        btn.className = 'decade-btn px-3 py-2 rounded-[8px] text-[13px] font-semibold transition-colors bg-[#0075ff] text-white border border-[#0075ff]';
    }
    fetchMusics();
}

function triggerSearch() {
    hideSearchSuggestions();
    const input = document.getElementById('searchInput');
    currentFilter.keyword = input ? input.value.trim() : '';
    showSection('discover', document.querySelectorAll('.nav-btn')[1]);
    fetchMusics();
}

/* ==================== 5.1 LIVE SEARCH AUTOCOMPLETE & RECOMMENDATIONS ==================== */
let searchDebounceTimer = null;
let activeSuggestionIndex = -1;

function initSearchAutocomplete() {
    const input = document.getElementById('searchInput');
    const dropdown = document.getElementById('searchSuggestions');
    const wrapper = document.getElementById('searchWrapper');
    if (!input || !dropdown) return;

    // Event input: mendeteksi setiap huruf/kata yang diketik pengguna
    input.addEventListener('input', (e) => {
        const query = e.target.value.trim();
        clearTimeout(searchDebounceTimer);
        activeSuggestionIndex = -1;

        if (query.length === 0) {
            dropdown.innerHTML = '';
            dropdown.classList.add('hidden');
            return;
        }

        // Tampilkan indikator loading halus saat mengetik
        dropdown.classList.remove('hidden');
        dropdown.innerHTML = `
            <div class="px-4 py-3 flex items-center gap-2.5 text-xs text-[#9aa1b1] bg-[#2e3035]">
                <svg class="animate-spin h-4 w-4 text-[#00b24b]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>Mencari rekomendasi untuk "${escapeHtml(query)}"...</span>
            </div>
        `;

        // Debounce 180ms agar respon cepat dan tidak membebani server
        searchDebounceTimer = setTimeout(() => {
            fetchSearchSuggestions(query);
        }, 180);
    });

    // Event focus: tampilkan kembali rekomendasi jika input terisi
    input.addEventListener('focus', () => {
        const query = input.value.trim();
        if (query.length > 0 && dropdown.classList.contains('hidden')) {
            fetchSearchSuggestions(query);
        }
    });

    // Navigasi keyboard: ArrowDown, ArrowUp, Escape, Enter
    input.addEventListener('keydown', (e) => {
        const items = dropdown.querySelectorAll('.suggestion-item');
        if (e.key === 'ArrowDown') {
            if (dropdown.classList.contains('hidden') || items.length === 0) return;
            e.preventDefault();
            activeSuggestionIndex = (activeSuggestionIndex + 1) % items.length;
            updateActiveSuggestion(items);
        } else if (e.key === 'ArrowUp') {
            if (dropdown.classList.contains('hidden') || items.length === 0) return;
            e.preventDefault();
            activeSuggestionIndex = (activeSuggestionIndex - 1 + items.length) % items.length;
            updateActiveSuggestion(items);
        } else if (e.key === 'Escape') {
            dropdown.classList.add('hidden');
            activeSuggestionIndex = -1;
        } else if (e.key === 'Enter') {
            if (!dropdown.classList.contains('hidden') && activeSuggestionIndex >= 0 && items[activeSuggestionIndex]) {
                e.preventDefault();
                items[activeSuggestionIndex].click();
            } else {
                dropdown.classList.add('hidden');
                triggerSearch();
            }
        }
    });

    // Tutup dropdown jika user klik di luar area search input
    document.addEventListener('click', (e) => {
        if (wrapper && !wrapper.contains(e.target)) {
            dropdown.classList.add('hidden');
            activeSuggestionIndex = -1;
        }
    });
}

function updateActiveSuggestion(items) {
    items.forEach((item, index) => {
        if (index === activeSuggestionIndex) {
            item.classList.add('bg-[#141415]', 'ring-1', 'ring-[#0075ff]');
            item.scrollIntoView({ block: 'nearest' });
        } else {
            item.classList.remove('bg-[#141415]', 'ring-1', 'ring-[#0075ff]');
        }
    });
}

function fetchSearchSuggestions(query) {
    const dropdown = document.getElementById('searchSuggestions');
    if (!dropdown) return;

    fetch(`/api/musics/suggestions?q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            renderSearchSuggestions(data, query);
        })
        .catch(err => {
            console.error('Error fetching search suggestions:', err);
            dropdown.innerHTML = `<div class="p-3 text-xs text-red-400 text-center bg-[#2e3035]">Gagal memuat rekomendasi.</div>`;
        });
}

function renderSearchSuggestions(data, query) {
    const dropdown = document.getElementById('searchSuggestions');
    if (!dropdown) return;

    const artists = data.artists || [];
    const songs = data.songs || [];

    if (artists.length === 0 && songs.length === 0) {
        dropdown.innerHTML = `
            <div class="px-4 py-5 text-center bg-[#2e3035]">
                <p class="text-xs text-[#9aa1b1]">Tidak ada rekomendasi yang cocok untuk "<span class="text-[#f0f2f5] font-semibold">${escapeHtml(query)}</span>"</p>
                <button onclick="triggerSearch()" class="mt-2.5 text-xs text-[#0075ff] hover:underline font-semibold inline-flex items-center gap-1">
                    <span>Cari semua di katalog</span>
                    <span>→</span>
                </button>
            </div>
        `;
        dropdown.classList.remove('hidden');
        return;
    }

    let html = '';

    // 1. Rekomendasi Artis / Musisi
    if (artists.length > 0) {
        html += `
            <div class="p-2 border-b border-[#2d2f33] bg-[#2e3035]">
                <div class="px-2.5 py-1 text-[10px] font-bold tracking-wider text-[#9aa1b1] uppercase flex items-center gap-1.5">
                    <svg class="w-3 h-3 text-[#0075ff]" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                        <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                    </svg>
                    <span>Artis / Musisi</span>
                </div>
                <div class="space-y-0.5 mt-1">
        `;

        artists.forEach(artist => {
            const imgSrc = artist.image ? `/${artist.image}` : '/image/queen1.jpg';
            html += `
                <div onclick="selectSearchRecommendation('${escapeQuote(artist.name)}', 'artist')"
                    class="suggestion-item flex items-center justify-between p-2 rounded-[10px] hover:bg-[#141415] cursor-pointer transition-colors group">
                    <div class="flex items-center gap-3 truncate">
                        <img src="${imgSrc}" alt="${artist.name}" onerror="this.src='/image/queen1.jpg'" class="w-9 h-9 rounded-full object-cover border border-[#2d2f33] flex-shrink-0">
                        <div class="truncate">
                            <h4 class="text-[14px] font-semibold text-[#f0f2f5] group-hover:text-[#00b24b] transition-colors truncate">
                                ${escapeHtml(artist.name)}
                            </h4>
                            <p class="text-[11px] text-[#9aa1b1] truncate">
                                Artis ${artist.listeners ? `• ${artist.listeners} pendengar` : ''}
                            </p>
                        </div>
                    </div>
                    <span class="text-xs text-[#9aa1b1] group-hover:text-[#0075ff] ml-2 flex-shrink-0">Lihat Lagu →</span>
                </div>
            `;
        });

        html += `
                </div>
            </div>
        `;
    }

    // 2. Rekomendasi Lagu
    if (songs.length > 0) {
        window.songDataCache = window.songDataCache || {};
        songs.forEach(s => {
            if (!window.songDataCache[s.id]) window.songDataCache[s.id] = s;
        });
        html += `
            <div class="p-2 bg-[#2e3035]">
                <div class="px-2.5 py-1 text-[10px] font-bold tracking-wider text-[#9aa1b1] uppercase flex items-center gap-1.5">
                    <svg class="w-3 h-3 text-[#00b24b]" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
                    </svg>
                    <span>Lagu & Komposisi</span>
                </div>
                <div class="space-y-0.5 mt-1">
        `;

        songs.forEach(song => {
            const imgSrc = song.image ? `/${song.image}` : '/image/queen1.jpg';
            html += `
                <div onclick="selectSearchRecommendation('${escapeQuote(song.title)}', 'song', ${song.id})"
                    class="suggestion-item flex items-center justify-between p-2 rounded-[10px] hover:bg-[#141415] cursor-pointer transition-colors group">
                    <div class="flex items-center gap-3 truncate">
                        <img src="${imgSrc}" alt="${song.title}" onerror="this.src='/image/queen1.jpg'" class="w-9 h-9 rounded-[6px] object-cover border border-[#2d2f33] flex-shrink-0">
                        <div class="truncate">
                            <h4 class="text-[14px] font-semibold text-[#f0f2f5] group-hover:text-[#00b24b] transition-colors truncate">
                                ${escapeHtml(song.title)}
                            </h4>
                            <p class="text-[11px] text-[#9aa1b1] truncate">
                                ${escapeHtml(song.artist)} • <span class="text-[#53575f]">${song.genre} (${song.year})</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                        <span class="text-[11px] font-semibold text-yellow-400">★ ${song.rating}</span>
                        <button onclick="event.stopPropagation(); playSong(${song.id}, '${escapeQuote(song.title)}', '${escapeQuote(song.artist)}', '/${song.image || 'image/queen1.jpg'}', '${song.audio_url || ''}', '${song.video_id || ''}'); hideSearchSuggestions();"
                            class="w-7 h-7 p-0 leading-none rounded-full text-[#9aa1b1] hover:text-white hover:bg-[#00b24b] flex items-center justify-center transition-colors" title="Putar Langsung">
                            <svg class="w-4 h-4 fill-current block m-auto" viewBox="0 0 24 24"><path d="M7.5 5.5v13l10-6.5z"/></svg>
                        </button>
                    </div>
                </div>
            `;
        });

        html += `
                </div>
            </div>
        `;
    }

    // 3. Footer Dropdown (design.md canvas #141415)
    html += `
        <div class="p-2.5 bg-[#141415] border-t border-[#2d2f33] flex items-center justify-between text-xs">
            <span class="text-[#9aa1b1] text-[11px]">Tekan <kbd class="px-1.5 py-0.5 rounded-[4px] bg-[#2e3035] border border-[#2d2f33] text-[10px] text-[#f0f2f5] font-mono">Enter</kbd> untuk hasil lengkap</span>
            <button onclick="triggerSearch()" class="text-[#0075ff] hover:underline font-semibold flex items-center gap-1">
                <span>Cari "${escapeHtml(query)}"</span>
                <span>→</span>
            </button>
        </div>
    `;

    dropdown.innerHTML = html;
    dropdown.classList.remove('hidden');
}

function selectSearchRecommendation(text, type, songId) {
    const input = document.getElementById('searchInput');
    if (input) {
        input.value = text;
    }
    hideSearchSuggestions();

    if (type === 'song' && songId) {
        triggerSearch();
        setTimeout(() => openMusicModal(songId), 300);
    } else {
        triggerSearch();
    }
}

function hideSearchSuggestions() {
    const dropdown = document.getElementById('searchSuggestions');
    if (dropdown) {
        dropdown.classList.add('hidden');
    }
    activeSuggestionIndex = -1;
}

function highlightMatch(text, query) {
    return escapeHtml(text || '');
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/* ==================== 6. MUSIC DETAIL MODAL (INSTANT ZERO-DELAY & ULTRA SMOOTH) ==================== */
let currentDetailSong = null;

function renderModalSongData(song) {
    if (!song) return;
    currentDetailSong = song;

    // Judul & Subtitle
    const titleEl = document.getElementById('modalTitle');
    if (titleEl) titleEl.textContent = song.title || 'Tanpa Judul';

    const subEl = document.getElementById('modalSubtitle');
    if (subEl) subEl.textContent = `${song.artist || 'Artis'} • ${song.album || 'Single'}`;

    // Cover Image & Glow Ambient
    const coverImg = document.getElementById('modalCoverImg');
    if (coverImg) {
        const rawImg = song.image || 'image/queen1.jpg';
        const finalImg = rawImg.startsWith('http') || rawImg.startsWith('/') ? rawImg : '/' + rawImg;
        coverImg.src = finalImg;
        coverImg.alt = song.title || 'Cover Art';
    }

    const coverGlow = document.getElementById('modalCoverGlow');
    if (coverGlow) {
        coverGlow.style.opacity = '0.7';
    }

    // Metadata Badges
    const yearEl = document.getElementById('modalYear');
    if (yearEl) yearEl.textContent = song.year || '-';

    const genreEl = document.getElementById('modalGenre');
    if (genreEl) genreEl.textContent = song.genre || '-';

    const durEl = document.getElementById('modalDuration');
    if (durEl) durEl.textContent = song.duration || '-';

    const ratingEl = document.getElementById('modalRating');
    if (ratingEl) ratingEl.textContent = `★ ${song.rating || '5.0'}`;

    // Deskripsi & Analisis Makna
    const descText = document.getElementById('modalDescriptionText');
    if (descText) {
        descText.textContent = song.description || 'Analisis deskripsi komposisi dan makna lagu ini sedang dalam proses penyusunan.';
    }

    // Action Buttons
    const btnAudio = document.getElementById('modalPlayAudioBtn');
    if (btnAudio) {
        btnAudio.onclick = () => {
            const rawImg = song.image || 'image/queen1.jpg';
            const finalImg = rawImg.startsWith('http') || rawImg.startsWith('/') ? rawImg : '/' + rawImg;
            playSong(song.id, song.title, song.artist, finalImg, song.audio_url, song.video_id);
        };
    }

    const btn3d = document.getElementById('modalOpen3dBtn');
    if (btn3d) {
        btn3d.onclick = () => {
            const rawImg = song.image || 'image/queen1.jpg';
            const finalImg = rawImg.startsWith('http') || rawImg.startsWith('/') ? rawImg : '/' + rawImg;
            openVinylWithSong(song.title, song.artist, finalImg, song.id, song.audio_url, song.video_id);
        };
    }

    const btnLyrics = document.getElementById('modalOpenLyricsBtn');
    if (btnLyrics) {
        btnLyrics.onclick = () => {
            const rawImg = song.image || 'image/queen1.jpg';
            const finalImg = rawImg.startsWith('http') || rawImg.startsWith('/') ? rawImg : '/' + rawImg;
            openLyricsWithSong(song.title, song.artist, finalImg, song.id, song.audio_url, song.video_id);
        };
    }

    const btnVideo = document.getElementById('modalOpenVideoBtn');
    if (btnVideo) {
        btnVideo.onclick = () => {
            const rawImg = song.image || 'image/queen1.jpg';
            const finalImg = rawImg.startsWith('http') || rawImg.startsWith('/') ? rawImg : '/' + rawImg;
            openVideoModal(song.id, song.title, song.artist, song.video_id, finalImg);
        };
    }

    // Reset Review input form
    userRatingInput = 0;
    resetSongFormRatingPreview();
    const inputReview = document.getElementById('inputReviewText');
    if (inputReview) inputReview.value = '';

    // Render review jika sudah ada di cache
    if (song.reviews && Array.isArray(song.reviews)) {
        renderSongReviews(song.reviews);
    } else {
        const reviewList = document.getElementById('modalSongReviewsList');
        if (reviewList) {
            reviewList.innerHTML = '<div class="space-y-2 py-1"><div class="h-14 bg-[#141415] rounded-[12px] animate-pulse"></div></div>';
        }
    }

    loadPlaylistsDropdown();
}

function openMusicModal(songId) {
    const modal = document.getElementById('musicModal');
    if (!modal) return;

    // 1. Cek Instant Local Memory Cache (0ms latency, tidak ada delay ngerender!)
    const cached = window.songDataCache && window.songDataCache[songId];
    if (cached) {
        renderModalSongData(cached);
    } else {
        // Fallback jika belum tercache
        const descText = document.getElementById('modalDescriptionText');
        if (descText) {
            descText.innerHTML = '<div class="space-y-2 py-1"><div class="h-3.5 bg-[#2e3035] rounded animate-pulse w-full"></div><div class="h-3.5 bg-[#2e3035] rounded animate-pulse w-5/6"></div></div>';
        }
    }

    // 2. Munculkan modal seketika dengan animasi hardware-accelerated 60fps
    modal.classList.remove('modal-closing');
    modal.classList.add('active');
    lockBodyScroll();

    // 3. Ambil data ulasan terbaru secara background tanpa flickering
    fetch(`/api/musics/${songId}`)
        .then(res => res.json())
        .then(song => {
            if (!song || !song.id) return;
            // Simpan ke cache
            window.songDataCache = window.songDataCache || {};
            window.songDataCache[song.id] = song;

            // Jika modal saat ini masih menampilkan lagu yang sama, sinkronkan ulasan & data terbaru
            if (currentDetailSong && currentDetailSong.id === song.id) {
                currentDetailSong = song;
                if (song.reviews) {
                    renderSongReviews(song.reviews);
                }
                if (song.description) {
                    const descText = document.getElementById('modalDescriptionText');
                    if (descText && (!cached || !cached.description)) {
                        descText.textContent = song.description;
                    }
                }
            }
        })
        .catch(err => {
            console.warn('Background sync song reviews:', err);
        });
}

function closeMusicModal() {
    const modal = document.getElementById('musicModal');
    if (!isModalTrulyOpen(modal)) return;

    modal.classList.add('modal-closing');
    setTimeout(() => {
        modal.classList.remove('active', 'modal-closing');
        unlockBodyScroll();
    }, 200);
}

/* ==================== INTERACTIVE STAR RATING (HOVER & SELECTION) ==================== */
const starRatingDescriptions = {
    1: '1 Bintang (Kurang Bagus)',
    2: '2 Bintang (Cukup)',
    3: '3 Bintang (Bagus)',
    4: '4 Bintang (Sangat Bagus)',
    5: '5 Bintang (Luar Biasa!)'
};

// Preview warna bintang saat di-hover (misal hover ke bintang 3 maka bintang 1, 2, dan 3 menyala kuning)
function previewSongFormRating(star) {
    const buttons = document.querySelectorAll('#inputStarContainer .star-btn');
    buttons.forEach((btn, index) => {
        if (index < star) {
            btn.classList.add('text-yellow-400');
            btn.classList.remove('text-slate-600');
        } else {
            btn.classList.remove('text-yellow-400');
            btn.classList.add('text-slate-600');
        }
    });
    const label = document.getElementById('starRatingLabel');
    if (label) {
        label.textContent = starRatingDescriptions[star] || `${star} Bintang`;
        label.classList.add('text-yellow-400');
        label.classList.remove('text-slate-400');
    }
}

// Kembalikan visual bintang saat kursor mouse keluar dari container bintang
function resetSongFormRatingPreview() {
    const buttons = document.querySelectorAll('#inputStarContainer .star-btn');
    buttons.forEach((btn, index) => {
        if (index < userRatingInput) {
            btn.classList.add('text-yellow-400');
            btn.classList.remove('text-slate-600');
        } else {
            btn.classList.remove('text-yellow-400');
            btn.classList.add('text-slate-600');
        }
    });
    const label = document.getElementById('starRatingLabel');
    if (label) {
        if (userRatingInput > 0) {
            label.textContent = `${userRatingInput} Bintang (Dipilih)`;
            label.classList.add('text-yellow-400');
            label.classList.remove('text-slate-400');
        } else {
            label.textContent = 'Beri rating 1 - 5 bintang';
            label.classList.remove('text-yellow-400');
            label.classList.add('text-slate-400');
        }
    }
}

// Tetapkan rating saat bintang di-klik oleh user
function setSongFormRating(star) {
    userRatingInput = star;
    resetSongFormRatingPreview();
}

// Render ulasan spesifik lagu di dalam modal detail lagu (Data asli dari DB, bukan dummy)
function renderSongReviews(reviews) {
    const list = document.getElementById('modalSongReviewsList');
    const countBadge = document.getElementById('modalReviewsCount');
    if (countBadge) countBadge.textContent = reviews ? reviews.length : 0;
    if (!list) return;

    if (!reviews || reviews.length === 0) {
        list.innerHTML = `
            <div class="bg-[#141415] border border-dashed border-[#2d2f33] rounded-[12px] p-4 text-center select-none">
                <p class="text-xs text-[#9aa1b1]">Belum ada ulasan untuk lagu ini di database.</p>
                <p class="text-[11px] text-[#0075ff] mt-1 font-semibold">Jadilah pendengar pertama yang memberikan penilaian & komentar di atas!</p>
            </div>
        `;
        return;
    }

    list.innerHTML = reviews.map(r => {
        const rating = Math.max(1, Math.min(5, parseInt(r.rating) || 5));
        const starsHtml = '★'.repeat(rating) + '☆'.repeat(5 - rating);
        const reviewerName = r.user_name || 'Pendengar Minima';
        const initials = reviewerName.split(/\s+/).map(w => w.charAt(0)).join('').slice(0, 2).toUpperCase() || 'PM';
        const dateStr = r.created_at ? new Date(r.created_at).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        }) : 'Baru saja';

        return `
            <div class="bg-[#141415] p-3.5 rounded-[12px] border border-[#2d2f33]">
                <div class="flex items-center justify-between mb-1.5">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-[#0075ff] text-white text-[10px] font-extrabold flex items-center justify-center">
                            ${escapeHtml(initials)}
                        </span>
                        <span class="text-xs font-semibold text-[#f0f2f5]">${escapeHtml(reviewerName)}</span>
                        <span class="text-[10px] text-[#9aa1b1]">• ${dateStr}</span>
                    </div>
                    <div class="text-yellow-400 text-xs font-bold tracking-wider" title="${rating} Bintang">
                        ${starsHtml}
                    </div>
                </div>
                <p class="text-xs text-[#9aa1b1] leading-relaxed pl-8 break-words whitespace-pre-wrap">"${escapeHtml(r.review)}"</p>
            </div>
        `;
    }).join('');
}

// Ambil ulang ulasan lagu dari API
function refreshModalSongReviews(musicId) {
    if (!musicId) return;
    fetch(`/api/reviews?music_id=${musicId}`)
        .then(res => res.json())
        .then(reviews => {
            renderSongReviews(reviews);
        })
        .catch(err => console.error('Error fetching song reviews:', err));
}

// Perbarui ulasan komunitas di halaman beranda secara live
function refreshHomepageRecentReviews() {
    const grid = document.getElementById('recentReviews');
    if (!grid) return;
    fetch('/api/reviews')
        .then(res => res.json())
        .then(reviews => {
            if (!reviews || reviews.length === 0) {
                grid.innerHTML = '<p class="text-slate-500 text-sm">Belum ada ulasan di database.</p>';
                return;
            }
            grid.innerHTML = reviews.slice(0, 6).map(rev => {
                const rating = Math.max(1, Math.min(5, parseInt(rev.rating) || 5));
                const stars = '★'.repeat(rating) + '☆'.repeat(5 - rating);
                const reviewerName = rev.user_name || 'Pendengar Minima';
                const dateStr = rev.created_at ? new Date(rev.created_at).toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                }) : 'Baru saja';

                return `
                    <div class="bg-[#2e3035] p-5 rounded-[16px] border border-[#2d2f33] flex flex-col justify-between transition-colors">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-extrabold text-[#f0f2f5] text-base">${escapeHtml(rev.music_title)}</h4>
                                <div class="text-yellow-400 text-sm">
                                    ${stars}
                                </div>
                            </div>
                            <p class="text-xs text-[#0075ff] mb-3 font-semibold">${escapeHtml(rev.artist)}</p>
                            <p class="text-[#f0f2f5]/90 text-sm leading-relaxed italic">"${escapeHtml(rev.review)}"</p>
                        </div>
                        <div class="flex items-center justify-between mt-4 pt-3 border-t border-[#2d2f33] text-[11px] text-[#9aa1b1]">
                            <span class="font-semibold text-[#f0f2f5] flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-[#00b24b]"></span>
                                ${escapeHtml(reviewerName)}
                            </span>
                            <span class="text-[#53575f]">${dateStr}</span>
                        </div>
                    </div>
                `;
            }).join('');
        })
        .catch(err => console.error('Error refreshing recent reviews:', err));
}

// Kirim ulasan ke database
function submitSongReview() {
    if (!currentDetailSong) {
        showToast('Pilih lagu terlebih dahulu.', 'error');
        return;
    }
    if (!userRatingInput || userRatingInput < 1) {
        showToast('Silakan pilih rating bintang terlebih dahulu (1 - 5)!', 'error');
        const starContainer = document.getElementById('inputStarContainer');
        if (starContainer) {
            starContainer.classList.add('ring-2', 'ring-yellow-400', 'rounded-lg', 'p-1');
            setTimeout(() => starContainer.classList.remove('ring-2', 'ring-yellow-400', 'rounded-lg', 'p-1'), 1500);
        }
        return;
    }
    const text = document.getElementById('inputReviewText').value.trim();
    if (!text) {
        showToast('Tulis pendapat atau komentar ulasan Anda terlebih dahulu!', 'error');
        document.getElementById('inputReviewText').focus();
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const submitBtn = document.getElementById('btnSubmitSongReview');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>Menyimpan ke DB...</span>';
    }

    fetch('/api/reviews', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            music_id: currentDetailSong.id,
            rating: userRatingInput,
            review: text
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Ulasan berhasil disimpan ke database!');
            document.getElementById('inputReviewText').value = '';
            userRatingInput = 0;
            resetSongFormRatingPreview();

            // Refresh ulasan lagu ini langsung dari database
            refreshModalSongReviews(currentDetailSong.id);

            // Perbarui nilai rating lagu yang ditampilkan di modal
            if (data.new_rating) {
                const modalRating = document.getElementById('modalRating');
                if (modalRating) modalRating.textContent = `★ ${data.new_rating}`;
            }

            // Perbarui list ulasan global di section Ulasan
            loadReviews();

            // Perbarui grid ulasan di halaman Beranda
            refreshHomepageRecentReviews();

            // Perbarui katalog jika fungsi pencarian tersedia
            if (typeof fetchMusics === 'function') {
                fetchMusics();
            }
        } else {
            showToast(data.message || 'Gagal mengirim ulasan.', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Terjadi kesalahan saat menyimpan ulasan.', 'error');
    })
    .finally(() => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span>Kirim Ulasan</span>';
        }
    });
}

/* ==================== 7. PLAYLIST CRUD ==================== */
function loadPlaylists() {
    fetch('/api/playlists')
        .then(res => res.json())
        .then(playlists => {
            const grid = document.getElementById('playlistsGrid');
            if (!grid) return;

            if (playlists.length === 0) {
                grid.innerHTML = '<div class="col-span-full py-12 text-center text-slate-400">Belum ada playlist. Klik tombol di atas untuk membuat.</div>';
                return;
            }

            grid.innerHTML = playlists.map(p => `
                <div onclick="viewPlaylistDetail(${p.id})" class="game-tile bg-[#2e3035] rounded-[16px] border border-[#2d2f33] overflow-hidden transition-colors cursor-pointer group flex flex-col justify-between">
                    <!-- Top: Full-Bleed Playlist Cover inside 16px rounded corners -->
                    <div class="relative w-full aspect-square bg-[#222428] overflow-hidden flex items-center justify-center text-4xl">
                        ${p.image ? `<img src="/${p.image}?v=${Date.now()}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">` : '<svg class="w-10 h-10 text-[#53575f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>'}
                        <button type="button" onclick="event.stopPropagation(); triggerPlaylistImageUpload(${p.id})" title="Ganti & Crop Gambar Sampul Playlist" class="absolute bottom-2.5 left-2.5 z-10 w-9 h-9 rounded-[10px] bg-[#141415]/90 hover:bg-[#00b24b] text-[#00b24b] hover:text-white border border-[#2d2f33] flex items-center justify-center transition-colors">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                                <circle cx="12" cy="13" r="3"/>
                            </svg>
                        </button>
                        ${p.user_name ? `<span class="absolute top-2.5 right-2.5 bg-[#0075ff] px-2.5 py-0.5 rounded-full text-[11px] font-semibold text-white truncate max-w-[75%]">Oleh: ${escapeHtml(p.user_name)}</span>` : ''}
                    </div>
                    <!-- Bottom Metadata Interior -->
                    <div class="p-3.5 flex-1 flex flex-col justify-between">
                        <div>
                            <h3 class="font-semibold text-[#f0f2f5] text-[16px] leading-[1.3] truncate group-hover:text-[#00b24b] transition-colors">${escapeHtml(p.name)}</h3>
                            <p class="text-[12px] font-semibold text-[#9aa1b1] mt-1 line-clamp-2">${escapeHtml(p.description || 'Tidak ada deskripsi.')}</p>
                        </div>
                        <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-[#2d2f33]">
                            <span class="text-[12px] text-[#00b24b] font-semibold">${p.musics_count || 0} Lagu</span>
                            <span class="text-[12px] text-[#0075ff] font-semibold truncate max-w-[130px]">Buka Playlist →</span>
                        </div>
                    </div>
                </div>
            `).join('');
        });
}

function loadPlaylistsDropdown() {
    fetch('/api/playlists')
        .then(res => res.json())
        .then(playlists => {
            const select = document.getElementById('modalPlaylistSelect');
            if (!select) return;
            select.innerHTML = '<option value="">Pilih Playlist...</option>' + 
                playlists.map(p => `<option value="${p.id}">${escapeHtml(p.name)}${p.user_name ? ' (' + escapeHtml(p.user_name) + ')' : ''}</option>`).join('');
        });
}

let activePlaylistMusics = [];
let lastPlaylistSearchResults = [];
let playlistSongSearchTimer = null;

function postAddSongToPlaylist(playlistId, musicId) {
    if (!window.currentUser) {
        showToast('Silakan Masuk atau Daftar akun terlebih dahulu untuk menyimpan lagu ke Playlist!', 'info');
        openAuthModal('login');
        return Promise.resolve(null);
    }
    if (!playlistId) {
        showToast('Pilih playlist terlebih dahulu!', 'error');
        return Promise.resolve(null);
    }

    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    return fetch(`/api/playlists/${playlistId}/add`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ music_id: musicId })
    }).then(res => res.json());
}

function addCurrentSongToPlaylist() {
    if (!currentDetailSong) return;
    const select = document.getElementById('modalPlaylistSelect');
    const playlistId = select ? select.value : '';

    postAddSongToPlaylist(playlistId, currentDetailSong.id)
        .then(data => {
            if (!data) return;
            showToast(data.message, data.success ? 'success' : 'error');
            loadPlaylists();
            if (data.success && activePlaylistId && Number(activePlaylistId) === Number(playlistId)) {
                const addedSong = data.music || currentDetailSong;
                if (addedSong && !activePlaylistMusics.some(s => Number(s.id) === Number(addedSong.id))) {
                    activePlaylistMusics.push(addedSong);
                    renderPlaylistViewSongs(activePlaylistId, activePlaylistMusics);
                    renderPlaylistAddSongResults(lastPlaylistSearchResults);
                }
            }
        });
}

function openCreatePlaylistModal() {
    if (!window.currentUser) {
        window.pendingOpenCreatePlaylistAfterAuth = true;
        showToast('Silakan Masuk atau Daftar akun terlebih dahulu untuk membuat Playlist pribadi!', 'info');
        openAuthModal('login');
        return;
    }
    document.getElementById('createPlaylistModal').classList.add('active');
    lockBodyScroll();
}

function closeCreatePlaylistModal() {
    document.getElementById('createPlaylistModal').classList.remove('active');
    unlockBodyScroll();
}

function handleCreatePlaylistSubmit(e) {
    e.preventDefault();
    const name = document.getElementById('newPlaylistName').value.trim();
    const desc = document.getElementById('newPlaylistDesc').value.trim();
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    fetch('/api/playlists', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ name, description: desc, image: 'image/queen1.jpg' })
    })
    .then(res => res.json())
    .then(data => {
        showToast(data.message);
        closeCreatePlaylistModal();
        loadPlaylists();
        loadPlaylistsDropdown();
        document.getElementById('newPlaylistName').value = '';
        document.getElementById('newPlaylistDesc').value = '';
    });
}

function renderPlaylistViewSongs(playlistId, musics) {
    activePlaylistMusics = Array.isArray(musics) ? musics : [];
    currentSongQueue = activePlaylistMusics;

    const countEl = document.getElementById('playlistViewCount');
    if (countEl) countEl.textContent = activePlaylistMusics.length;

    const songsContainer = document.getElementById('playlistViewSongs');
    if (!songsContainer) return;

    if (activePlaylistMusics.length === 0) {
        songsContainer.innerHTML = '<p class="text-[#9aa1b1] text-sm py-4">Belum ada lagu di playlist ini. Gunakan pencarian <strong class="text-[#f0f2f5]">Cari &amp; Tambahkan Lagu</strong> di bawah untuk memasukkan lagu ke playlist ini.</p>';
        return;
    }

    songsContainer.innerHTML = activePlaylistMusics.map((song, idx) => {
        const imgUrl = song.image ? (song.image.startsWith('/') ? song.image : '/' + song.image) : '/image/queen1.jpg';
        return `
            <div id="playlist-song-${song.id}" data-playlist-song-id="${song.id}" class="flex items-center justify-between gap-3 p-3 rounded-[12px] bg-[#141415] hover:bg-[#383b42] transition-colors border border-[#2d2f33]">
                <div class="flex items-center space-x-3 min-w-0 truncate">
                    <span class="playlist-song-idx text-xs font-bold text-[#9aa1b1] w-5 flex-shrink-0">${idx + 1}</span>
                    <img src="${imgUrl}" alt="${escapeHtml(song.title)}" class="w-10 h-10 rounded-[6px] object-cover border border-[#2d2f33] flex-shrink-0">
                    <div class="min-w-0 truncate">
                        <h4 class="font-semibold text-[#f0f2f5] text-sm truncate hover:text-[#00b24b] cursor-pointer" onclick="openMusicModal(${song.id})">${escapeHtml(song.title)}</h4>
                        <p class="text-xs text-[#9aa1b1] truncate">${escapeHtml(song.artist)}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2 flex-shrink-0">
                    <button onclick="playSong(${song.id}, '${escapeQuote(song.title)}', '${escapeQuote(song.artist)}', '${imgUrl}', '${song.audio_url || ''}', '${song.video_id || ''}')" class="px-3 py-1.5 bg-[#00b24b] hover:bg-[#00c853] text-white font-semibold rounded-[8px] text-[12px] transition-colors">
                        Putar
                    </button>
                    <button onclick="removeSongFromPlaylist(${playlistId}, ${song.id})" class="text-[#9aa1b1] hover:text-red-400 p-1.5 transition-colors" title="Hapus lagu">
                        ✕
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

function focusPlaylistSongSearch() {
    const section = document.getElementById('playlistAddSongsSection');
    const input = document.getElementById('playlistSongSearchInput');
    if (section) {
        section.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    if (input) {
        setTimeout(() => input.focus({ preventScroll: true }), 180);
    }
}

function clearPlaylistSongSearch() {
    const input = document.getElementById('playlistSongSearchInput');
    if (input) {
        input.value = '';
        input.focus();
    }
    searchSongsForPlaylist('');
}

function searchSongsForPlaylist(keyword = '') {
    const clearBtn = document.getElementById('playlistSongSearchClear');
    if (clearBtn) {
        clearBtn.classList.toggle('hidden', !keyword || keyword.trim() === '');
    }

    if (playlistSongSearchTimer) {
        clearTimeout(playlistSongSearchTimer);
    }
    playlistSongSearchTimer = setTimeout(() => {
        loadPlaylistSongCatalog(keyword);
    }, 180);
}

function loadPlaylistSongCatalog(keyword = '') {
    const container = document.getElementById('playlistAddSongResults');
    if (!container) return;

    const kw = (keyword || '').trim();
    const params = new URLSearchParams({ sort: 'rating', per_page: '18' });
    if (kw !== '') params.set('keyword', kw);

    fetch(`/api/musics/search?${params.toString()}`)
        .then(res => res.json())
        .then(data => {
            const songs = data.data || [];
            window.songDataCache = window.songDataCache || {};
            songs.forEach(s => {
                window.songDataCache[s.id] = s;
            });
            lastPlaylistSearchResults = songs;
            renderPlaylistAddSongResults(songs);
        })
        .catch(() => {
            container.innerHTML = '<p class="text-red-400 text-sm py-3">Gagal memuat katalog lagu.</p>';
        });
}

function renderPlaylistAddSongResults(songs) {
    const container = document.getElementById('playlistAddSongResults');
    if (!container) return;

    if (!songs || songs.length === 0) {
        container.innerHTML = '<p class="text-[#9aa1b1] text-sm py-4">Tidak ada lagu yang cocok dengan kata kunci pencarian.</p>';
        return;
    }

    const existingIds = new Set(activePlaylistMusics.map(m => Number(m.id)));

    container.innerHTML = songs.map(song => {
        const imgUrl = song.image ? (song.image.startsWith('/') ? song.image : '/' + song.image) : '/image/queen1.jpg';
        const alreadyAdded = existingIds.has(Number(song.id));

        return `
            <div class="flex items-center justify-between gap-3 p-3 rounded-[12px] bg-[#141415] hover:bg-[#383b42] transition-colors border border-[#2d2f33]">
                <div class="flex items-center space-x-3 min-w-0 truncate">
                    <img src="${imgUrl}" alt="${escapeHtml(song.title)}" class="w-11 h-11 rounded-[8px] object-cover border border-[#2d2f33] flex-shrink-0">
                    <div class="min-w-0 truncate">
                        <h4 class="font-semibold text-[#f0f2f5] text-sm truncate hover:text-[#00b24b] cursor-pointer" onclick="openMusicModal(${song.id})">${escapeHtml(song.title)}</h4>
                        <p class="text-xs text-[#9aa1b1] truncate">${escapeHtml(song.artist)} • ${escapeHtml(song.genre || '')}${song.duration ? ' • ' + escapeHtml(song.duration) : ''}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button type="button" onclick="playSong(${song.id}, '${escapeQuote(song.title)}', '${escapeQuote(song.artist)}', '${imgUrl}', '${song.audio_url || ''}', '${song.video_id || ''}')" class="w-8 h-8 rounded-[8px] bg-[#2e3035] hover:bg-[#383b42] text-[#f0f2f5] border border-[#2d2f33] flex items-center justify-center transition-colors" title="Pratinjau / Putar Lagu">
                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M7.5 5.5v13l10-6.5z"/></svg>
                    </button>
                    ${alreadyAdded
                        ? `<button type="button" disabled class="h-8 px-3 rounded-[8px] bg-[#2e3035] text-[#00b24b] border border-[#2d2f33] text-[12px] font-semibold inline-flex items-center gap-1 cursor-default whitespace-nowrap">✓ Di Playlist</button>`
                        : `<button type="button" onclick="addSongToActivePlaylist(${song.id}, this)" class="h-8 px-3 rounded-[8px] bg-[#00b24b] hover:bg-[#00c853] text-white text-[12px] font-semibold transition-colors whitespace-nowrap">+ Tambah</button>`
                    }
                </div>
            </div>
        `;
    }).join('');
}

function addSongToActivePlaylist(musicId, btnEl = null) {
    if (!activePlaylistId) {
        showToast('Pilih playlist terlebih dahulu.', 'error');
        return;
    }

    if (btnEl) {
        btnEl.disabled = true;
        btnEl.textContent = 'Menambah...';
    }

    postAddSongToPlaylist(activePlaylistId, musicId)
        .then(data => {
            if (!data) {
                if (btnEl) {
                    btnEl.disabled = false;
                    btnEl.textContent = '+ Tambah';
                }
                return;
            }
            showToast(data.message, data.success ? 'success' : 'error');

            if (data.success) {
                const cachedSong = data.music || (window.songDataCache && window.songDataCache[musicId]) || lastPlaylistSearchResults.find(s => Number(s.id) === Number(musicId));
                if (cachedSong && !activePlaylistMusics.some(s => Number(s.id) === Number(cachedSong.id))) {
                    activePlaylistMusics.push(cachedSong);
                }
                renderPlaylistViewSongs(activePlaylistId, activePlaylistMusics);
                renderPlaylistAddSongResults(lastPlaylistSearchResults);
                loadPlaylists();
            } else if (btnEl) {
                btnEl.disabled = false;
                btnEl.textContent = '+ Tambah';
            }
        })
        .catch(() => {
            showToast('Terjadi kesalahan saat menambahkan lagu ke playlist.', 'error');
            if (btnEl) {
                btnEl.disabled = false;
                btnEl.textContent = '+ Tambah';
            }
        });
}

function viewPlaylistDetail(playlistId) {
    activePlaylistId = playlistId;
    fetch(`/api/playlists/${playlistId}`)
        .then(res => res.json())
        .then(p => {
            document.getElementById('playlistViewTitle').textContent = p.name;
            document.getElementById('playlistViewDescription').textContent = p.description || 'Tidak ada deskripsi.';
            const ownerBadge = document.getElementById('playlistViewOwnerBadge');
            if (ownerBadge) {
                ownerBadge.textContent = p.user_name ? `Playlist Pribadi • Oleh ${p.user_name}` : 'Playlist Komunitas';
            }

            const imgContainer = document.getElementById('playlistViewImage');
            imgContainer.innerHTML = p.image ? `<img src="/${p.image}?v=${Date.now()}" class="w-full h-full object-cover">` : '<svg class="w-16 h-16 text-slate-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>';

            document.getElementById('deletePlaylistBtn').onclick = () => deletePlaylist(p.id);

            renderPlaylistViewSongs(p.id, p.musics || []);

            const searchInput = document.getElementById('playlistSongSearchInput');
            const clearBtn = document.getElementById('playlistSongSearchClear');
            if (searchInput) searchInput.value = '';
            if (clearBtn) clearBtn.classList.add('hidden');
            loadPlaylistSongCatalog('');

            showSection('playlist-view');
        });
}

function removeSongFromPlaylist(playlistId, songId) {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const rowEl = document.getElementById(`playlist-song-${songId}`);
    const containerEl = document.getElementById('playlistViewSongs');

    fetch(`/api/playlists/${playlistId}/songs/${songId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf }
    })
    .then(res => res.json())
    .then(data => {
        showToast(data.message || 'Lagu dihapus dari playlist.', 'success');
        activePlaylistMusics = activePlaylistMusics.filter(s => Number(s.id) !== Number(songId));
        currentSongQueue = activePlaylistMusics;
        const countEl = document.getElementById('playlistViewCount');
        if (countEl) countEl.textContent = activePlaylistMusics.length;
        renderPlaylistAddSongResults(lastPlaylistSearchResults);

        if (rowEl && containerEl) {
            animateCardDeleteAndReflow(rowEl, containerEl, () => {
                renderPlaylistViewSongs(playlistId, activePlaylistMusics);
            }, () => {
                containerEl.querySelectorAll('.playlist-song-idx').forEach((el, i) => {
                    el.textContent = i + 1;
                });
            });
        } else {
            viewPlaylistDetail(playlistId);
        }
        loadPlaylists();
    });
}

function deletePlaylist(playlistId) {
    showConfirmAlert({
        title: 'Hapus Playlist',
        description: 'Apakah Anda yakin ingin menghapus playlist ini?',
        confirmText: 'Hapus',
        cancelText: 'Batal',
        variant: 'warning',
        onConfirm: () => {
            const viewEl = document.getElementById('playlist-view');
            if (viewEl) viewEl.classList.add('minima-card-deleting');

            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            fetch(`/api/playlists/${playlistId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf }
            })
            .then(res => res.json())
            .then(data => {
                setTimeout(() => {
                    if (viewEl) viewEl.classList.remove('minima-card-deleting');
                    showToast(data.message || 'Playlist berhasil dihapus!', 'success');
                    showSection('playlists');
                    loadPlaylists();
                }, 380);
            });
        }
    });
}

function openEditPlaylistModal() {
    if (!activePlaylistId) {
        showToast('Pilih playlist terlebih dahulu.', 'error');
        return;
    }
    const currentName = document.getElementById('playlistViewTitle').textContent;
    const currentDesc = document.getElementById('playlistViewDescription').textContent;

    document.getElementById('editPlaylistId').value = activePlaylistId;
    document.getElementById('editPlaylistName').value = currentName !== 'Nama Playlist' ? currentName : '';
    document.getElementById('editPlaylistDesc').value = (currentDesc !== 'Tidak ada deskripsi.' && currentDesc !== 'Deskripsi playlist...') ? currentDesc : '';

    document.getElementById('editPlaylistModal').classList.add('active');
    lockBodyScroll();
}

function closeEditPlaylistModal() {
    document.getElementById('editPlaylistModal').classList.remove('active');
    unlockBodyScroll();
}

function handleEditPlaylistSubmit(e) {
    e.preventDefault();
    const id = document.getElementById('editPlaylistId').value;
    const name = document.getElementById('editPlaylistName').value.trim();
    const description = document.getElementById('editPlaylistDesc').value.trim();
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    fetch(`/api/playlists/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ name, description })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            closeEditPlaylistModal();
            viewPlaylistDetail(id);
            loadPlaylists();
        } else {
            showToast(data.message || 'Gagal memperbarui playlist.', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Terjadi kesalahan saat menyimpan playlist.', 'error');
    });
}

let playlistCropper = null;
let targetCropPlaylistId = null;

function triggerPlaylistImageUpload(playlistId = null) {
    targetCropPlaylistId = playlistId || activePlaylistId;
    if (!targetCropPlaylistId) {
        showToast('Pilih playlist terlebih dahulu.', 'error');
        return;
    }
    const fileInput = document.getElementById('playlistImageInput');
    if (fileInput) {
        fileInput.value = '';
        fileInput.click();
    }
}

function handlePlaylistImageSelect(event) {
    const file = event.target.files && event.target.files[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
        showToast('Silakan pilih file gambar yang valid (JPG, PNG, WEBP).', 'error');
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const imgEl = document.getElementById('cropperTargetImage');
        const modal = document.getElementById('cropPlaylistImageModal');
        if (!imgEl || !modal) return;

        if (playlistCropper) {
            playlistCropper.destroy();
            playlistCropper = null;
        }

        imgEl.src = e.target.result;
        modal.classList.add('active');
        lockBodyScroll();

        setTimeout(() => {
            if (typeof Cropper === 'function') {
                playlistCropper = new Cropper(imgEl, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 0.9,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                    background: false,
                    responsive: true
                });
            }
        }, 60);
    };
    reader.readAsDataURL(file);
}

function closeCropPlaylistModal() {
    const modal = document.getElementById('cropPlaylistImageModal');
    if (modal) modal.classList.remove('active');
    if (playlistCropper) {
        playlistCropper.destroy();
        playlistCropper = null;
    }
    unlockBodyScroll();
}

function cropperZoom(ratio) {
    if (playlistCropper) playlistCropper.zoom(ratio);
}

function cropperRotate(deg) {
    if (playlistCropper) playlistCropper.rotate(deg);
}

function cropperReset() {
    if (playlistCropper) playlistCropper.reset();
}

function saveCroppedPlaylistImage() {
    const playlistId = targetCropPlaylistId || activePlaylistId;
    if (!playlistCropper || !playlistId) {
        showToast('Tidak ada gambar untuk disimpan.', 'error');
        return;
    }

    const btn = document.getElementById('saveCroppedPlaylistBtn');
    const originalText = btn ? btn.textContent : 'Potong & Simpan Gambar';
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Menyimpan...';
    }

    const canvas = playlistCropper.getCroppedCanvas({
        width: 600,
        height: 600,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high'
    });

    if (!canvas) {
        if (btn) {
            btn.disabled = false;
            btn.textContent = originalText;
        }
        showToast('Gagal memotong gambar.', 'error');
        return;
    }

    const croppedDataUrl = canvas.toDataURL('image/jpeg', 0.92);
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    fetch(`/api/playlists/${playlistId}/image`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ cropped_image: croppedDataUrl })
    })
    .then(res => res.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.textContent = originalText;
        }
        if (data.success) {
            closeCropPlaylistModal();
            showToast(data.message || 'Gambar sampul playlist berhasil diperbarui!', 'success');
            if (activePlaylistId && String(activePlaylistId) === String(playlistId)) {
                const imgContainer = document.getElementById('playlistViewImage');
                if (imgContainer && data.image) {
                    imgContainer.innerHTML = `<img src="/${data.image}?v=${Date.now()}" class="w-full h-full object-cover">`;
                }
            }
            loadPlaylists();
        } else {
            showToast(data.message || 'Gagal memperbarui gambar playlist.', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        if (btn) {
            btn.disabled = false;
            btn.textContent = originalText;
        }
        showToast('Terjadi kesalahan saat mengunggah gambar.', 'error');
    });
}

/* ==================== 8. REVIEWS CRUD ==================== */
function loadReviews() {
    fetch('/api/reviews')
        .then(res => res.json())
        .then(reviews => {
            const list = document.getElementById('myReviewsList');
            if (!list) return;

            if (!reviews || reviews.length === 0) {
                list.innerHTML = `
                    <div class="bg-[#2e3035] p-8 rounded-[16px] border border-dashed border-[#2d2f33] text-center select-none song-card-animated">
                        <p class="text-[#9aa1b1] text-sm">Belum ada ulasan yang tersimpan di database.</p>
                        <p class="text-xs text-[#0075ff] mt-1 font-semibold">Buka detail lagu mana saja di katalog lalu kirim ulasan pertama Anda!</p>
                    </div>
                `;
                return;
            }

            list.innerHTML = reviews.map((r, idx) => {
                const rating = Math.max(1, Math.min(5, parseInt(r.rating) || 5));
                const dateStr = r.created_at ? new Date(r.created_at).toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                }) : 'Baru saja';

                return `
                    <div id="review-card-${r.id}" data-review-id="${r.id}" style="animation-delay: ${Math.min(idx * 45, 250)}ms" class="song-card-animated bg-[#2e3035] p-5 rounded-[16px] border border-[#2d2f33] flex flex-col justify-between transition-colors">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-extrabold text-[#f0f2f5] text-base">${escapeHtml(r.music_title)}</h4>
                                <div class="text-yellow-400 text-sm font-semibold tracking-wider">
                                    ${'★'.repeat(rating)}${'☆'.repeat(5 - rating)}
                                </div>
                            </div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-xs text-[#0075ff] font-semibold">${escapeHtml(r.artist)}</span>
                                <span class="text-[11px] text-[#53575f]">• ${dateStr}</span>
                            </div>
                            <p class="text-[#f0f2f5]/90 text-sm leading-relaxed italic break-words">"${escapeHtml(r.review)}"</p>
                        </div>
                        <div class="flex items-center justify-between pt-4 mt-4 border-t border-[#2d2f33] text-xs">
                            <span class="text-[#9aa1b1] flex items-center gap-1.5 font-semibold text-[11px]">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#00b24b]"></span>
                                Oleh: ${escapeHtml(r.user_name || 'Pendengar Minima')} • Tersimpan di Database
                            </span>
                            <div class="flex space-x-2">
                                <button onclick="openEditReviewModal(${r.id}, ${rating}, '${escapeQuote(r.review)}')" class="px-3 py-1.5 bg-[#141415] hover:bg-[#0075ff] text-[#f0f2f5] hover:text-white border border-[#2d2f33] rounded-[10px] transition-colors font-semibold">Edit</button>
                                <button onclick="deleteReview(${r.id})" class="px-3 py-1.5 bg-[#141415] hover:bg-red-600 text-[#f0f2f5] hover:text-white border border-[#2d2f33] rounded-[10px] transition-colors font-semibold">Hapus</button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        })
        .catch(err => {
            console.error('Error fetching reviews:', err);
        });
}

function animateCardDeleteAndReflow(targetEl, containerEl, onEmptyCallback = null, onAfterReflow = null) {
    if (!targetEl || !containerEl) return;

    const siblings = Array.from(containerEl.children).filter(el => el !== targetEl);
    const firstRects = new Map();
    siblings.forEach(el => firstRects.set(el, el.getBoundingClientRect()));

    targetEl.classList.add('minima-card-deleting');

    setTimeout(() => {
        targetEl.remove();

        const remaining = Array.from(containerEl.children);
        if (remaining.length === 0) {
            if (typeof onEmptyCallback === 'function') onEmptyCallback();
            return;
        }

        remaining.forEach(el => {
            const first = firstRects.get(el);
            if (!first) return;
            const last = el.getBoundingClientRect();
            const dx = first.left - last.left;
            const dy = first.top - last.top;
            if (Math.abs(dx) > 1 || Math.abs(dy) > 1) {
                el.style.transition = 'none';
                el.style.transform = `translate(${dx}px, ${dy}px)`;
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        el.style.transition = 'transform 0.44s cubic-bezier(0.16, 1, 0.3, 1)';
                        el.style.transform = 'translate(0, 0)';
                        setTimeout(() => {
                            el.style.transition = '';
                            el.style.transform = '';
                        }, 450);
                    });
                });
            }
        });

        if (typeof onAfterReflow === 'function') onAfterReflow();
    }, 420);
}

function openEditReviewModal(id, rating, reviewText) {
    document.getElementById('editReviewId').value = id;
    document.getElementById('editReviewText').value = reviewText;
    setEditFormRating(rating || 5);
    document.getElementById('editReviewModal').classList.add('active');
    lockBodyScroll();
}

function closeEditReviewModal() {
    document.getElementById('editReviewModal').classList.remove('active');
    unlockBodyScroll();
}

function previewEditFormRating(star) {
    const buttons = document.querySelectorAll('#editStarContainer .edit-star-btn');
    buttons.forEach((btn, index) => {
        if (index < star) {
            btn.classList.add('text-yellow-400');
            btn.classList.remove('text-slate-600');
        } else {
            btn.classList.remove('text-yellow-400');
            btn.classList.add('text-slate-600');
        }
    });
    const label = document.getElementById('editStarRatingLabel');
    if (label) {
        label.textContent = starRatingDescriptions[star] || `${star} Bintang`;
        label.classList.add('text-yellow-400');
        label.classList.remove('text-slate-400');
    }
}

function resetEditFormRatingPreview() {
    const buttons = document.querySelectorAll('#editStarContainer .edit-star-btn');
    buttons.forEach((btn, index) => {
        if (index < editRatingInput) {
            btn.classList.add('text-yellow-400');
            btn.classList.remove('text-slate-600');
        } else {
            btn.classList.remove('text-yellow-400');
            btn.classList.add('text-slate-600');
        }
    });
    const label = document.getElementById('editStarRatingLabel');
    if (label) {
        label.textContent = `${editRatingInput} Bintang`;
        label.classList.add('text-yellow-400');
        label.classList.remove('text-slate-400');
    }
}

function setEditFormRating(star) {
    editRatingInput = star;
    resetEditFormRatingPreview();
}

function handleUpdateReviewSubmit(e) {
    e.preventDefault();
    const id = document.getElementById('editReviewId').value;
    const text = document.getElementById('editReviewText').value.trim();
    if (!text) {
        showToast('Teks ulasan tidak boleh kosong.', 'error');
        return;
    }
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    fetch(`/api/reviews/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ rating: editRatingInput, review: text })
    })
    .then(res => res.json())
    .then(data => {
        showToast(data.message || 'Ulasan berhasil diperbarui di database!');
        closeEditReviewModal();
        loadReviews();
        if (currentDetailSong) {
            refreshModalSongReviews(currentDetailSong.id);
            if (data.new_rating) {
                const modalRating = document.getElementById('modalRating');
                if (modalRating) modalRating.textContent = `★ ${data.new_rating}`;
            }
        }
        if (typeof fetchMusics === 'function') fetchMusics();
        refreshHomepageRecentReviews();
    })
    .catch(err => {
        console.error(err);
        showToast('Gagal memperbarui ulasan.', 'error');
    });
}

function deleteReview(id) {
    showConfirmAlert({
        title: 'Hapus Ulasan',
        description: 'Apakah Anda yakin ingin menghapus ulasan ini dari database?',
        confirmText: 'Hapus',
        cancelText: 'Batal',
        variant: 'warning',
        onConfirm: () => {
            const cardEl = document.getElementById(`review-card-${id}`);
            const listEl = document.getElementById('myReviewsList');
            const csrf = document.querySelector('meta[name="csrf-token"]').content;

            fetch(`/api/reviews/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                showToast(data.message || 'Ulasan berhasil dihapus dari database!', 'success');

                if (cardEl && listEl) {
                    animateCardDeleteAndReflow(cardEl, listEl, () => {
                        listEl.innerHTML = `
                            <div class="bg-[#2e3035] p-8 rounded-[16px] border border-dashed border-[#2d2f33] text-center select-none song-card-animated">
                                <p class="text-[#9aa1b1] text-sm">Belum ada ulasan yang tersimpan di database.</p>
                                <p class="text-xs text-[#0075ff] mt-1 font-semibold">Buka detail lagu mana saja di katalog lalu kirim ulasan pertama Anda!</p>
                            </div>
                        `;
                    });
                } else {
                    loadReviews();
                }

                if (currentDetailSong) {
                    refreshModalSongReviews(currentDetailSong.id);
                }
                if (typeof fetchMusics === 'function') fetchMusics();
                refreshHomepageRecentReviews();
            })
            .catch(err => {
                console.error(err);
                showToast('Gagal menghapus ulasan.', 'error');
            });
        }
    });
}

/* ==================== 9. SWEETALERT NOTIFICATION & CONFIRMATION (GAMEVAULT STYLE) ==================== */
function closeSweetAlert(callback = null) {
    const overlay = document.getElementById('sweet-alert-overlay');
    if (overlay) {
        overlay.classList.remove('open');
        const modal = overlay.querySelector('.swal-modal-box');
        if (modal) modal.style.transform = 'scale(0.9) translateY(10px)';
        setTimeout(() => {
            overlay.remove();
            if (typeof callback === 'function') callback();
        }, 260);
    } else if (typeof callback === 'function') {
        callback();
    }
}

function getSweetAlertIconHtml(type = 'success') {
    if (type === 'warning') {
        return `<svg class="svg-warning-circle" viewBox="-2 -2 56 56" style="width:72px; height:72px; display:inline-block; fill:none;">
            <circle cx="26" cy="26" r="24" fill="none"/>
            <path class="svg-warning-mark" fill="none" d="M26 14v13M26 35.5h.02"/>
        </svg>`;
    }
    if (type === 'error') {
        return `<svg class="svg-error-circle" viewBox="-2 -2 56 56" style="width:72px; height:72px; display:inline-block; fill:none;">
            <circle cx="26" cy="26" r="24" fill="none"/>
            <path class="svg-error-cross" fill="none" d="M18 18l16 16M34 18L18 34"/>
        </svg>`;
    }
    return `<svg class="svg-success-circle" viewBox="-2 -2 56 56" style="width:72px; height:72px; display:inline-block; fill:none;">
        <circle cx="26" cy="26" r="24" fill="none"/>
        <path class="svg-success-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
    </svg>`;
}

function showSweetAlert(title, message, type = 'success') {
    let overlay = document.getElementById('sweet-alert-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'sweet-alert-overlay';
        document.body.appendChild(overlay);
    }

    const iconHtml = getSweetAlertIconHtml(type);

    overlay.innerHTML = `
        <div class="swal-modal-box">
            <div style="font-size: 64px; margin-bottom: 24px;">
                ${iconHtml}
            </div>
            <h3 style="font-size: 20px; font-weight: 800; margin: 0 0 10px 0; color: #f0f2f5;">${escapeHtml(title)}</h3>
            <p style="color: #9aa1b1; margin: 0 0 28px 0; line-height: 1.5; font-size: 14px; font-weight: 600;">${escapeHtml(message)}</p>
            <button type="button" class="w-full py-2.5 px-3 bg-[#00b24b] hover:bg-[#00c853] text-white font-semibold rounded-[10px] text-[14px] transition-colors flex items-center justify-center" onclick="closeSweetAlert()">
                OK
            </button>
        </div>
    `;

    overlay.onclick = function(e) {
        if (e.target === overlay) closeSweetAlert();
    };

    void overlay.offsetWidth;
    overlay.classList.add('open');
}

function showToast(message, type = 'success') {
    if (!message) return;
    const titles = {
        success: 'Berhasil!',
        error: 'Gagal!',
        warning: 'Perhatian!',
        info: 'Informasi'
    };
    showSweetAlert(titles[type] || 'Berhasil!', message, type);
}

function showConfirmAlert({
    title = 'Perhatian!',
    description = 'Apakah Anda yakin ingin melanjutkan?',
    confirmText = 'Ya, Hapus',
    cancelText = 'Batal',
    variant = 'warning',
    onConfirm = null
} = {}) {
    let overlay = document.getElementById('sweet-alert-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'sweet-alert-overlay';
        document.body.appendChild(overlay);
    }

    const iconHtml = getSweetAlertIconHtml(variant);

    overlay.innerHTML = `
        <div class="swal-modal-box">
            <div style="font-size: 64px; margin-bottom: 24px;">
                ${iconHtml}
            </div>
            <h3 style="font-size: 20px; font-weight: 800; margin: 0 0 10px 0; color: #f0f2f5;">${escapeHtml(title)}</h3>
            <p style="color: #9aa1b1; margin: 0 0 24px 0; line-height: 1.5; font-size: 14px; font-weight: 600;">${escapeHtml(description)}</p>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <button type="button" id="swalConfirmOkBtn" class="w-full py-2.5 px-3 bg-[#00b24b] hover:bg-[#00c853] text-white font-semibold rounded-[10px] text-[14px] transition-colors flex items-center justify-center">
                    ${escapeHtml(confirmText)}
                </button>
                <button type="button" id="swalConfirmCancelBtn" class="w-full py-2.5 px-3 bg-[#141415] hover:bg-[#383b42] text-[#f0f2f5] font-semibold rounded-[10px] text-[14px] border border-[#2d2f33] transition-colors flex items-center justify-center">
                    ${escapeHtml(cancelText)}
                </button>
            </div>
        </div>
    `;

    overlay.onclick = function(e) {
        if (e.target === overlay) closeSweetAlert();
    };

    overlay.querySelector('#swalConfirmCancelBtn').addEventListener('click', () => closeSweetAlert());
    overlay.querySelector('#swalConfirmOkBtn').addEventListener('click', () => {
        closeSweetAlert(() => {
            if (typeof onConfirm === 'function') onConfirm();
        });
    });

    void overlay.offsetWidth;
    overlay.classList.add('open');
}

function escapeQuote(str) {
    if (!str) return '';
    return str.replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

/* ==================== 6. VIDEO MV GALLERY & YOUTUBE PLAYER HD SYSTEM ==================== */
let activeVideoSong = null;
let currentVideoFilter = {
    keyword: '',
    genre: 'All',
    page: 1,
    perPage: 16
};

function pauseActiveAudio() {
    // 1. Hentikan YouTube audio player jika aktif
    if (currentPlaybackMode === 'youtube' && ytAudioPlayer && isYtReady) {
        try {
            ytAudioPlayer.pauseVideo();
        } catch (e) {}
    }

    // 2. Hentikan HTML5 audio player
    const audio = document.getElementById('globalAudioPlayer');
    if (audio) {
        audio.pause();
    }

    // 3. Hentikan synthesizer fallback
    stopSynthesizedPreview();
    isAudioPlaying = false;
    updatePlayIcons(false);
    stopYtProgressTimer();
}

let currentVideoModalMode = 'video';
let videoModalYTPlayer = null;
let hasActivatedVisualizerFallback = false;
let visControlsTimeout = null;
let videoCurtainFallbackTimer = null;
let videoModalSyncTimer = null;
let videoModalYTState = -1;

function clearVideoCurtainTimer() {
    if (videoCurtainFallbackTimer) {
        clearTimeout(videoCurtainFallbackTimer);
        videoCurtainFallbackTimer = null;
    }
}

function startVideoModalSyncTimer() {
    stopVideoModalSyncTimer();
    videoModalSyncTimer = setInterval(() => {
        const modal = document.getElementById('videoModal');
        if (!isModalTrulyOpen(modal) || currentVideoModalMode !== 'video' || !videoModalYTPlayer) {
            stopVideoModalSyncTimer();
            return;
        }
        try {
            const cur = videoModalYTPlayer.getCurrentTime ? (videoModalYTPlayer.getCurrentTime() || 0) : 0;
            const dur = videoModalYTPlayer.getDuration ? (videoModalYTPlayer.getDuration() || 0) : 0;
            if (activeVideoSong && cur > 0) {
                activeVideoSong.resumeTime = cur;
            }
            if (dur > 0) {
                const progress = (cur / dur) * 100;
                const progressBar = document.getElementById('playerProgressBar');
                if (progressBar && !isSeekingAudio) progressBar.value = progress;
                if (!isSeekingAudio) {
                    updateProgressBarVisual(progress);
                    updateTimeDisplays(cur, dur);
                }
            }
        } catch (e) {}
    }, 250);
}

function stopVideoModalSyncTimer() {
    if (videoModalSyncTimer) {
        clearInterval(videoModalSyncTimer);
        videoModalSyncTimer = null;
    }
}

function getActiveVideoModalTime() {
    if (currentVideoModalMode === 'visualizer') {
        return getCurrentAudioTime();
    }
    if (videoModalYTPlayer && typeof videoModalYTPlayer.getCurrentTime === 'function') {
        try {
            const t = videoModalYTPlayer.getCurrentTime();
            if (typeof t === 'number' && !isNaN(t) && t > 0) {
                return t;
            }
        } catch (e) {}
    }
    if (activeVideoSong && typeof activeVideoSong.resumeTime === 'number' && activeVideoSong.resumeTime > 0) {
        return activeVideoSong.resumeTime;
    }
    return getCurrentAudioTime();
}

function mountYouTubeModalPlayer(videoId, startSeconds = 0) {
    const mainMount = document.getElementById('mainVideoMount');
    const loadingCurtain = document.getElementById('videoLoadingCurtain');
    const initialStart = Math.max(0, Math.floor(parseFloat(startSeconds) || 0));

    clearVideoCurtainTimer();
    stopVideoModalSyncTimer();
    videoModalYTState = -1;

    if (videoModalYTPlayer) {
        try { videoModalYTPlayer.destroy(); } catch (e) {}
        videoModalYTPlayer = null;
    }

    if (mainMount) {
        mainMount.innerHTML = '<div id="ytPlayerContainer" class="w-full h-full"></div>';
    }

    const createPlayer = () => {
        if (currentVideoModalMode !== 'video' || hasActivatedVisualizerFallback) return;
        const container = document.getElementById('ytPlayerContainer');
        if (!container) return;

        const playerVars = {
            autoplay: 1,
            playsinline: 1,
            enablejsapi: 1,
            rel: 0,
            origin: window.location.origin
        };
        if (initialStart > 0) {
            playerVars.start = initialStart;
        }

        try {
            videoModalYTPlayer = new YT.Player('ytPlayerContainer', {
                width: '100%',
                height: '100%',
                videoId: videoId,
                playerVars: playerVars,
                events: {
                    'onReady': function(event) {
                        try {
                            const iframe = event.target.getIframe && event.target.getIframe();
                            if (iframe) {
                                iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
                                iframe.classList.add('w-full', 'h-full');
                            }
                            if (startSeconds >= 1 && typeof event.target.seekTo === 'function') {
                                event.target.seekTo(startSeconds, true);
                            }
                            event.target.playVideo();
                        } catch (e) {}

                        // Jika browser memblokir autoplay (state tetap cued/unstarted) namun video tidak error,
                        // buka curtain setelah 1.5 detik agar pengguna tetap dapat menekan Play pada pemutar YouTube.
                        clearVideoCurtainTimer();
                        videoCurtainFallbackTimer = setTimeout(() => {
                            if (currentVideoModalMode === 'video' && !hasActivatedVisualizerFallback && loadingCurtain) {
                                loadingCurtain.classList.add('opacity-0', 'pointer-events-none');
                            }
                        }, 1500);
                    },
                    'onStateChange': function(event) {
                        videoModalYTState = event.data;
                        if (event.data === 1 || (window.YT && event.data === YT.PlayerState.PLAYING)) {
                            clearVideoCurtainTimer();
                            if (loadingCurtain) {
                                loadingCurtain.classList.add('opacity-0', 'pointer-events-none');
                            }
                            isAudioPlaying = true;
                            updatePlayIcons(true);
                            startVideoModalSyncTimer();
                        } else if (event.data === 2 || (window.YT && event.data === YT.PlayerState.PAUSED)) {
                            stopVideoModalSyncTimer();
                        }
                    },
                    'onError': function(event) {
                        clearVideoCurtainTimer();
                        stopVideoModalSyncTimer();
                        console.warn('YouTube Video Modal error:', event.data);
                        if (event.data === 101 || event.data === 150 || event.data === 153 || event.data === 100 || event.data === 2) {
                            activateAudiovisualizerMode(true);
                        }
                    }
                }
            });
        } catch (err) {
            console.warn('Notice: YT.Player creation error:', err);
        }
    };

    if (window.YT && window.YT.Player) {
        createPlayer();
    } else {
        let attempts = 0;
        const waitYt = setInterval(() => {
            attempts++;
            if (window.YT && window.YT.Player) {
                clearInterval(waitYt);
                createPlayer();
            } else if (attempts >= 20) {
                clearInterval(waitYt);
            }
        }, 200);
    }
}

function handleVisualizerActivity() {
    const fallback = document.getElementById('videoVisualizerFallback');
    if (!fallback) return;
    fallback.classList.add('show-controls');
    if (visControlsTimeout) {
        clearTimeout(visControlsTimeout);
    }
    if (isAudioPlaying) {
        visControlsTimeout = setTimeout(() => {
            fallback.classList.remove('show-controls');
        }, 2500);
    }
}

function openVideoModal(id, title, artist, videoId, cover) {
    const validVideoId = (videoId && videoId.trim() !== '') ? videoId.trim() : 'fJ9rUzIMcZQ';
    const finalCover = normalizeImageUrl(cover || '/image/queen1.jpg');
    const cached = (id && window.songDataCache) ? window.songDataCache[id] : null;
    const resolvedStreamUrl = id ? `/api/musics/${id}/stream` : (cached?.audio_url || '');

    // Simpan detik lagu jika lagu yang sama sedang diputar sebelum berpindah ke Video MV
    const isSameCurrentSong = Boolean(id && currentSong && Number(currentSong.id) === Number(id));
    const savedAudioTime = isSameCurrentSong ? getCurrentAudioTime() : 0;

    closeOtherMultimediaModals('videoModal');

    // Sinkronkan currentSong & tampilkan bottom player agar tetap aktif setelah Video MV ditutup
    if (id) {
        currentSong = { id, title, artist, cover: finalCover, audioUrl: resolvedStreamUrl, videoId: validVideoId };
        if (!currentSongQueue.some(s => Number(s.id) === Number(id))) {
            currentSongQueue.push({ id, title, artist, image: finalCover, audio_url: resolvedStreamUrl, video_id: validVideoId });
        }
        prewarmSongLyrics(currentSong);
        syncCurrentSongMetadataUI(currentSong, { skipLyricsLoad: true });
    }

    // 1. Pause sementara audio latar saat iframe YouTube aktif agar suara tidak bertumpuk, namun posisi currentTime tetap tersimpan
    isTransitioningAudioToVideo = true;
    pauseActiveAudio();
    isTransitioningAudioToVideo = false;
    hasActivatedVisualizerFallback = false;

    // 2. Set informasi lagu pada modal video
    const titleEl = document.getElementById('videoModalTitle');
    const artistEl = document.getElementById('videoModalArtist');
    const coverEl = document.getElementById('videoModalCover');
    const ytLink = document.getElementById('videoModalYtLink');

    if (titleEl) titleEl.textContent = title;
    if (artistEl) artistEl.textContent = artist;
    if (coverEl) coverEl.src = finalCover;
    if (ytLink) ytLink.href = `https://www.youtube.com/watch?v=${validVideoId}`;

    activeVideoSong = { id, title, artist, videoId: validVideoId, cover: finalCover, resumeTime: savedAudioTime };

    // Set visualizer backdrop
    const isBlinding = (title && title.toLowerCase().includes('blinding lights')) || (artist && artist.toLowerCase().includes('the weeknd'));
    const backdropImg = isBlinding ? '/image/theweeknd_blinding.jpg' : finalCover;

    const visBackdrop = document.getElementById('visualizerBackdrop');
    const visCenterImg = document.getElementById('visualizerCenterImg');
    const visTitle = document.getElementById('visualizerTitle');
    const visArtist = document.getElementById('visualizerArtist');

    if (visBackdrop) visBackdrop.style.backgroundImage = `url('${backdropImg}')`;
    if (visCenterImg) visCenterImg.src = backdropImg;
    if (visTitle) visTitle.textContent = title;
    if (visArtist) visArtist.textContent = `${artist} • Audio HD & Efek Visualizer`;

    // 3. Konfigurasi mode video awal & loading curtain
    currentVideoModalMode = 'video';
    const iframeWrap = document.getElementById('videoIframeWrapper');
    const mainMount = document.getElementById('mainVideoMount');
    const loadingCurtain = document.getElementById('videoLoadingCurtain');
    const visFallback = document.getElementById('videoVisualizerFallback');
    const toggleBtn = document.getElementById('videoToggleModeBtn');
    const toggleBtnText = document.getElementById('videoToggleModeText');
    const modeBadge = document.getElementById('videoModalModeBadge');

    // Aktifkan loading curtain pelindung layar agar error YouTube tidak bocor ke layar
    if (loadingCurtain) {
        loadingCurtain.classList.remove('opacity-0', 'pointer-events-none');
    }
    if (iframeWrap) iframeWrap.classList.remove('hidden');
    if (visFallback) visFallback.classList.add('hidden');
    if (toggleBtn) toggleBtn.classList.remove('hidden');
    if (toggleBtnText) toggleBtnText.textContent = 'Mode Visualizer';
    if (modeBadge) modeBadge.textContent = 'YouTube Player HD';

    mountYouTubeModalPlayer(validVideoId, savedAudioTime);

    // 4. Buka modal dengan animasi mulus
    const modal = document.getElementById('videoModal');
    if (modal) {
        modal.classList.remove('modal-closing');
        modal.classList.add('active');
        lockBodyScroll();
    }
}

// Global listener untuk deteksi postMessage dari YouTube iframe modal jika diblokir
window.addEventListener('message', function onYtEmbedMessage(e) {
    if (hasActivatedVisualizerFallback || currentVideoModalMode === 'visualizer') return;
    const modal = document.getElementById('videoModal');
    if (!modal || !modal.classList.contains('active')) return;
    if (!videoModalYTPlayer || typeof videoModalYTPlayer.getIframe !== 'function') return;
    try {
        const modalIframe = videoModalYTPlayer.getIframe();
        if (!modalIframe || e.source !== modalIframe.contentWindow) return;
    } catch (err) {
        return;
    }
    if (!e.data || typeof e.data !== 'string') return;
    try {
        const data = JSON.parse(e.data);
        if (data.event === 'onError' && (data.info === 101 || data.info === 150 || data.info === 153 || data.info === 100 || data.info === 2)) {
            activateAudiovisualizerMode(true);
        }
    } catch (err) {}
});

function toggleVideoVisualizerMode() {
    if (currentVideoModalMode === 'video') {
        activateAudiovisualizerMode(false);
    } else {
        switchToVideoMode();
    }
}

function activateAudiovisualizerMode(isAutoFallback = false) {
    if (hasActivatedVisualizerFallback || currentVideoModalMode === 'visualizer') {
        return;
    }
    clearVideoCurtainTimer();
    stopVideoModalSyncTimer();

    // Ambil detik terakhir dari YouTube Player (atau detik terakhir sebelum buka Video Modal) agar Visualizer lanjut tanpa mengulang dari 0:00
    let resumeSec = (activeVideoSong && typeof activeVideoSong.resumeTime === 'number') ? activeVideoSong.resumeTime : 0;
    if (videoModalYTPlayer && typeof videoModalYTPlayer.getCurrentTime === 'function') {
        try {
            const ytTime = videoModalYTPlayer.getCurrentTime();
            if (typeof ytTime === 'number' && !isNaN(ytTime) && ytTime > 0) {
                resumeSec = ytTime;
            }
        } catch (e) {}
    }
    if (activeVideoSong) {
        activeVideoSong.resumeTime = resumeSec;
    }

    hasActivatedVisualizerFallback = true;
    currentVideoModalMode = 'visualizer';

    const iframeWrap = document.getElementById('videoIframeWrapper');
    const mainMount = document.getElementById('mainVideoMount');
    const loadingCurtain = document.getElementById('videoLoadingCurtain');
    const visFallback = document.getElementById('videoVisualizerFallback');
    const toggleBtn = document.getElementById('videoToggleModeBtn');
    const toggleBtnText = document.getElementById('videoToggleModeText');
    const modeBadge = document.getElementById('videoModalModeBadge');

    // 1. Hentikan dan bersihkan iframe agar YouTube berhenti memicu pesan error
    if (videoModalYTPlayer) {
        try { videoModalYTPlayer.destroy(); } catch (e) {}
        videoModalYTPlayer = null;
    }
    if (mainMount) {
        mainMount.innerHTML = '';
    }
    if (iframeWrap) {
        iframeWrap.classList.add('hidden');
    }
    if (loadingCurtain) {
        loadingCurtain.classList.add('opacity-0', 'pointer-events-none');
    }

    // 2. Tampilkan panggung audiovisualizer
    if (visFallback) {
        visFallback.classList.remove('hidden');
        handleVisualizerActivity();
    }

    if (modeBadge) {
        modeBadge.textContent = 'Audiovisualizer HD';
    }

    // 3. Jika video dibatasi hak cipta oleh studio (isAutoFallback), sembunyikan tombol switch ke video
    // karena video tidak bisa di-embed di website luar. Jika switch manual, tombol tetap ada.
    if (isAutoFallback) {
        if (toggleBtn) toggleBtn.classList.add('hidden');
    } else {
        if (toggleBtn) {
            toggleBtn.classList.remove('hidden');
            if (toggleBtnText) toggleBtnText.textContent = 'Mode Video YouTube';
        }
    }

    // 4. Lanjutkan pemutaran audio lokal tepat dari detik terakhir (tanpa restart dari awal)
    if (activeVideoSong && activeVideoSong.id) {
        const songId = activeVideoSong.id;
        const audioUrl = `/api/musics/${songId}/stream`;
        ensureSongContinuingSmoothly(songId, activeVideoSong.title, activeVideoSong.artist, activeVideoSong.cover, audioUrl, activeVideoSong.videoId, resumeSec);
    }
}

function switchToVideoMode() {
    const resumeSec = getCurrentAudioTime();
    if (activeVideoSong) {
        activeVideoSong.resumeTime = resumeSec;
    }

    currentVideoModalMode = 'video';
    hasActivatedVisualizerFallback = false;
    const iframeWrap = document.getElementById('videoIframeWrapper');
    const mainMount = document.getElementById('mainVideoMount');
    const loadingCurtain = document.getElementById('videoLoadingCurtain');
    const visFallback = document.getElementById('videoVisualizerFallback');
    const toggleBtn = document.getElementById('videoToggleModeBtn');
    const toggleBtnText = document.getElementById('videoToggleModeText');
    const modeBadge = document.getElementById('videoModalModeBadge');

    if (visFallback) visFallback.classList.add('hidden');
    if (iframeWrap) iframeWrap.classList.remove('hidden');
    if (loadingCurtain) loadingCurtain.classList.remove('opacity-0', 'pointer-events-none');
    if (toggleBtn) toggleBtn.classList.remove('hidden');
    if (toggleBtnText) toggleBtnText.textContent = 'Mode Visualizer';
    if (modeBadge) modeBadge.textContent = 'YouTube Player HD';

    // Pause audio HTML5 sementara saat kembali ke video YouTube, dan lanjutkan video dari detik yang sama
    isTransitioningAudioToVideo = true;
    pauseActiveAudio();
    isTransitioningAudioToVideo = false;

    if (activeVideoSong && activeVideoSong.videoId) {
        mountYouTubeModalPlayer(activeVideoSong.videoId, resumeSec);
    }
}

function toggleVisualizerPlayback() {
    togglePlayAudio();
}

function seekVisualizerAudio(percent) {
    seekAudio(percent);
}

function closeVideoModal(keepAudioPlaying = false) {
    const modal = document.getElementById('videoModal');
    if (!isModalTrulyOpen(modal)) return;

    const wasVisualizer = (currentVideoModalMode === 'visualizer');
    const resumeSec = getActiveVideoModalTime();
    const songToContinue = activeVideoSong ? { ...activeVideoSong } : (currentSong ? { ...currentSong } : null);
    if (activeVideoSong) {
        activeVideoSong.resumeTime = resumeSec;
    }

    modal.classList.add('modal-closing');
    hasActivatedVisualizerFallback = false;
    clearVideoCurtainTimer();
    stopVideoModalSyncTimer();

    if (visControlsTimeout) {
        clearTimeout(visControlsTimeout);
        visControlsTimeout = null;
    }

    // Hentikan iframe YouTube & bersihkan player agar suara tidak bertumpuk
    if (videoModalYTPlayer) {
        try {
            videoModalYTPlayer.destroy();
        } catch (e) {}
        videoModalYTPlayer = null;
    }

    const mainMount = document.getElementById('mainVideoMount');
    if (mainMount) {
        mainMount.innerHTML = '';
    }

    const loadingCurtain = document.getElementById('videoLoadingCurtain');
    if (loadingCurtain) {
        loadingCurtain.classList.remove('opacity-0', 'pointer-events-none');
    }

    // Saat modal video ditutup langsung (bukan switch ke modal lain), lanjutkan lagu tetap menyala di bottom player!
    if (!keepAudioPlaying) {
        activeVideoSong = null;
        if (!wasVisualizer && songToContinue && songToContinue.id) {
            const audioUrl = `/api/musics/${songToContinue.id}/stream`;
            ensureSongContinuingSmoothly(
                songToContinue.id,
                songToContinue.title,
                songToContinue.artist,
                songToContinue.cover,
                audioUrl,
                songToContinue.videoId,
                resumeSec
            );
        } else {
            updatePlayIcons(isAudioPlaying);
        }
    }

    setTimeout(() => {
        modal.classList.remove('active', 'modal-closing');
        unlockBodyScroll();
        currentVideoModalMode = 'video';
        hasActivatedVisualizerFallback = false;
        const iframeWrap = document.getElementById('videoIframeWrapper');
        if (iframeWrap) iframeWrap.classList.remove('hidden');
        const visFallback = document.getElementById('videoVisualizerFallback');
        if (visFallback) visFallback.classList.add('hidden');
        const toggleBtn = document.getElementById('videoToggleModeBtn');
        if (toggleBtn) toggleBtn.classList.remove('hidden');
    }, 220);
}

function switchFromVideoTo3D() {
    if (!activeVideoSong) return;
    const { title, artist, cover, id, videoId } = activeVideoSong;
    const resumeSec = getActiveVideoModalTime();
    const audioUrl = id ? `/api/musics/${id}/stream` : '';
    closeVideoModal(true);
    activeVideoSong = null;
    ensureSongContinuingSmoothly(id, title, artist, cover, audioUrl, videoId, resumeSec);
    openVinylModal();
}

function switchFromVideoToLyrics() {
    if (!activeVideoSong) return;
    const { title, artist, cover, id, videoId } = activeVideoSong;
    const resumeSec = getActiveVideoModalTime();
    const audioUrl = id ? `/api/musics/${id}/stream` : '';
    closeVideoModal(true);
    activeVideoSong = null;
    ensureSongContinuingSmoothly(id, title, artist, cover, audioUrl, videoId, resumeSec);
    openLyricsModal();
}

function openVideoModalForCurrentSong() {
    if (!currentSong) {
        showToast('Pilih dan putar lagu terlebih dahulu atau pilih dari Galeri Video MV.', 'info');
        return;
    }
    const songId = currentSong.id;
    const cached = window.songDataCache && window.songDataCache[songId];
    const videoId = currentSong.videoId || (cached ? cached.video_id : 'fJ9rUzIMcZQ');
    const title = currentSong.title;
    const artist = currentSong.artist;
    const cover = currentSong.cover || currentSong.image || (cached ? cached.image : '/image/queen1.jpg');
    openVideoModal(songId, title, artist, videoId, cover);
}

function openVideoForCurrentModalSong() {
    if (!activeModalSongId) return;
    const cached = window.songDataCache && window.songDataCache[activeModalSongId];
    if (!cached) return;
    closeMusicModal();
    setTimeout(() => {
        const cover = cached.image ? (cached.image.startsWith('/') ? cached.image : '/' + cached.image) : '/image/queen1.jpg';
        openVideoModal(cached.id, cached.title, cached.artist, cached.video_id, cover);
    }, 250);
}

function renderVideosSection(page = 1) {
    currentVideoFilter.page = page;
    const grid = document.getElementById('videosGrid');
    if (!grid) return;

    // Ambil data lagu dari cache memori klien
    let allSongs = Object.values(window.songDataCache || {});
    if (allSongs.length === 0) {
        grid.innerHTML = '<div class="col-span-full py-16 text-center text-slate-400"><div class="inline-block w-8 h-8 border-3 border-red-500 border-t-transparent rounded-full animate-spin mb-3"></div><p class="text-sm font-medium">Memuat katalog video musik...</p></div>';
        fetch('/api/musics/search?per_page=200')
            .then(res => res.json())
            .then(data => {
                const songs = data.data || [];
                songs.forEach(s => {
                    window.songDataCache[s.id] = s;
                });
                renderVideosSection(page);
            })
            .catch(() => {
                grid.innerHTML = '<div class="col-span-full py-12 text-center text-red-400">Gagal memuat katalog video.</div>';
            });
        return;
    }

    // Filter berdasarkan kata kunci dan genre
    const kw = (currentVideoFilter.keyword || '').toLowerCase().trim();
    const selGenre = (currentVideoFilter.genre || 'All').toLowerCase();

    let filtered = allSongs.filter(s => {
        const matchGenre = (selGenre === 'all' || (s.genre && s.genre.toLowerCase() === selGenre));
        const matchKw = !kw || 
            (s.title && s.title.toLowerCase().includes(kw)) || 
            (s.artist && s.artist.toLowerCase().includes(kw)) ||
            (s.album && s.album.toLowerCase().includes(kw));
        return matchGenre && matchKw;
    });

    const totalCountEl = document.getElementById('videosTotalCount');
    if (totalCountEl) totalCountEl.textContent = `${filtered.length} Video Klip`;

    if (filtered.length === 0) {
        grid.innerHTML = '<div class="col-span-full py-16 text-center text-slate-400"><p class="text-base font-semibold">Tidak ada video musik yang sesuai dengan pencarian.</p><p class="text-xs text-slate-500 mt-1">Coba gunakan kata kunci lain atau pilih genre "Semua Video".</p></div>';
        const pag = document.getElementById('videosPagination');
        if (pag) pag.innerHTML = '';
        return;
    }

    // Paginasi
    const total = filtered.length;
    const perPage = currentVideoFilter.perPage;
    const totalPages = Math.ceil(total / perPage);
    const start = (page - 1) * perPage;
    const paged = filtered.slice(start, start + perPage);

    grid.innerHTML = paged.map((song, idx) => {
        const videoId = (song.video_id && song.video_id.trim() !== '') ? song.video_id.trim() : 'fJ9rUzIMcZQ';
        const thumbUrl = `https://img.youtube.com/vi/${videoId}/hqdefault.jpg`;
        const coverImg = song.image ? (song.image.startsWith('/') ? song.image : '/' + song.image) : '/image/queen1.jpg';

        return `
            <div class="video-card game-tile song-card-animated bg-[#2e3035] rounded-[16px] border border-[#2d2f33] overflow-hidden flex flex-col justify-between group" style="animation-delay: ${Math.min(idx * 20, 240)}ms;">
                <div>
                    <!-- Full-bleed Video Thumbnail 16:9 -->
                    <div class="relative overflow-hidden aspect-video bg-black cursor-pointer" onclick="openVideoModal(${song.id}, '${escapeQuote(song.title)}', '${escapeQuote(song.artist)}', '${videoId}', '${coverImg}')">
                        <img src="${thumbUrl}" alt="${escapeHtml(song.title)}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" onerror="this.onerror=null; this.src='${coverImg}'">
                        
                        <!-- Red Play Button Overlay -->
                        <div class="absolute inset-0 bg-black/45 group-hover:bg-black/65 transition-colors flex items-center justify-center">
                            <div class="w-12 h-12 rounded-full bg-red-600 text-white flex items-center justify-center transition-transform duration-300 group-hover:scale-110">
                                <svg class="w-5 h-5 fill-current ml-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            </div>
                        </div>

                        <!-- Duration Badge -->
                        <span class="absolute bottom-2 right-2 bg-[#141415]/90 px-2 py-0.5 rounded-[4px] text-[11px] font-mono font-semibold text-white border border-[#2d2f33]">
                            ${song.duration || '3:30'}
                        </span>
                        
                        <!-- YouTube HD Badge -->
                        <span class="absolute top-2 left-2 bg-[#141415]/90 text-[#f0f2f5] text-[10px] font-semibold px-2 py-0.5 rounded-[4px] flex items-center gap-1 border border-[#2d2f33]">
                            <svg class="w-3 h-3 text-red-500 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>youtube</title><path fill="currentColor" d="m10 15l5.19-3L10 9zm11.56-7.83c.13.47.22 1.1.28 1.9c.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83c-.25.9-.83 1.48-1.73 1.73c-.47.13-1.33.22-2.65.28c-1.3.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44c-.9-.25-1.48-.83-1.73-1.73c-.13-.47-.22-1.1-.28-1.9c-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83c.25-.9.83-1.48 1.73-1.73c.47-.13 1.33-.22 2.65-.28c1.3-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44c.9.25 1.48.83 1.73 1.73"/></svg>
                            HD
                        </span>
                    </div>

                    <!-- Song Info -->
                    <div class="p-3.5 pb-2">
                        <h3 class="font-semibold text-[#f0f2f5] text-[16px] truncate cursor-pointer hover:text-[#00b24b] transition-colors" onclick="openVideoModal(${song.id}, '${escapeQuote(song.title)}', '${escapeQuote(song.artist)}', '${videoId}', '${coverImg}')">${escapeHtml(song.title)}</h3>
                        <p class="text-[12px] font-semibold text-[#9aa1b1] truncate mt-0.5">${escapeHtml(song.artist)} • ${song.year}</p>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="text-[11px] bg-[#0075ff] text-white font-semibold px-2 py-0.5 rounded-full">${escapeHtml(song.genre)}</span>
                            <span class="text-[11px] text-yellow-400 font-semibold">★ ${song.rating}</span>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="px-3.5 py-2.5 border-t border-[#2d2f33] flex items-center justify-between text-[12px] font-semibold">
                    <button onclick="openVideoModal(${song.id}, '${escapeQuote(song.title)}', '${escapeQuote(song.artist)}', '${videoId}', '${coverImg}')" class="text-red-400 hover:underline inline-flex items-center gap-1.5 transition-colors">
                        <svg class="w-3.5 h-3.5 text-red-500 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>youtube</title><path fill="currentColor" d="m10 15l5.19-3L10 9zm11.56-7.83c.13.47.22 1.1.28 1.9c.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83c-.25.9-.83 1.48-1.73 1.73c-.47.13-1.33.22-2.65.28c-1.3.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44c-.9-.25-1.48-.83-1.73-1.73c-.13-.47-.22-1.1-.28-1.9c-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83c.25-.9.83-1.48 1.73-1.73c.47-.13 1.33-.22 2.65-.28c1.3-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44c.9.25 1.48.83 1.73 1.73"/></svg>
                        <span>Tonton Video</span>
                    </button>
                    <button onclick="openMusicModal(${song.id})" class="text-[#0075ff] hover:underline">
                        Detail & Analisis →
                    </button>
                </div>
            </div>
        `;
    }).join('');

    renderVideosPagination(total, page, perPage);
}

function filterVideosLive(val) {
    currentVideoFilter.keyword = val || '';
    renderVideosSection(1);
}

function filterVideosByGenre(genre, btn) {
    currentVideoFilter.genre = genre;
    const select = document.getElementById('videoGenreSelect');
    if (select) select.value = genre;

    // Update pill buttons active state
    const pills = document.querySelectorAll('.video-pill-btn');
    pills.forEach(p => {
        p.classList.remove('bg-[#0075ff]', 'text-white', 'border-[#0075ff]');
        p.classList.add('bg-[#2e3035]', 'text-[#f0f2f5]', 'border', 'border-[#2d2f33]');
    });
    if (btn) {
        btn.classList.remove('bg-[#2e3035]', 'text-[#f0f2f5]', 'border-[#2d2f33]');
        btn.classList.add('bg-[#0075ff]', 'text-white', 'border-[#0075ff]');
    }
    renderVideosSection(1);
}

function renderVideosPagination(total, curPage, perPage) {
    const pag = document.getElementById('videosPagination');
    if (!pag) return;

    const totalPages = Math.ceil(total / perPage);
    if (totalPages <= 1) {
        pag.innerHTML = `<div class="text-xs text-[#9aa1b1]">Menampilkan seluruh <strong class="text-[#f0f2f5]">${total}</strong> video musik resmi</div>`;
        return;
    }

    let btns = '';
    const maxButtons = 6;
    let startPage = Math.max(1, curPage - 2);
    let endPage = Math.min(totalPages, startPage + maxButtons - 1);
    if (endPage - startPage < maxButtons - 1) {
        startPage = Math.max(1, endPage - maxButtons + 1);
    }

    if (startPage > 1) {
        btns += `<button onclick="renderVideosSection(1)" class="w-8 h-8 rounded-[8px] text-xs font-semibold bg-[#2e3035] text-[#f0f2f5] border border-[#2d2f33]">1</button>`;
        if (startPage > 2) btns += `<span class="px-1 text-[#53575f]">...</span>`;
    }

    for (let i = startPage; i <= endPage; i++) {
        if (i === curPage) {
            btns += `<button class="w-8 h-8 rounded-[8px] text-xs font-bold bg-[#00b24b] text-white">${i}</button>`;
        } else {
            btns += `<button onclick="renderVideosSection(${i})" class="w-8 h-8 rounded-[8px] text-xs font-semibold bg-[#2e3035] text-[#f0f2f5] hover:bg-[#383b42] border border-[#2d2f33] transition-colors">${i}</button>`;
        }
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) btns += `<span class="px-1 text-[#53575f]">...</span>`;
        btns += `<button onclick="renderVideosSection(${totalPages})" class="w-8 h-8 rounded-[8px] text-xs font-semibold bg-[#2e3035] text-[#f0f2f5] border border-[#2d2f33]">${totalPages}</button>`;
    }

    pag.innerHTML = `
        <div class="flex items-center justify-between text-xs text-[#9aa1b1] w-full flex-wrap gap-3">
            <span>Halaman <strong class="text-[#f0f2f5]">${curPage}</strong> dari <strong class="text-[#f0f2f5]">${totalPages}</strong> (Total ${total} video)</span>
            <div class="flex items-center gap-1.5">${btns}</div>
        </div>
    `;
}

/* ==================== 9. USER AUTHENTICATION SYSTEM (SIGN IN / SIGN UP / LOGOUT AJAX) ==================== */
window.currentUser = window.currentUser || null;
window.pendingOpenCreatePlaylistAfterAuth = false;

function updateCsrfToken(newToken) {
    if (!newToken) return;
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) {
        meta.setAttribute('content', newToken);
    }
}

function getUserInitials(name) {
    if (!name) return 'U';
    return String(name)
        .trim()
        .split(/\s+/)
        .map(w => w.charAt(0))
        .join('')
        .slice(0, 2)
        .toUpperCase() || 'U';
}

function renderNavAuthState() {
    const container = document.getElementById('navAuthContainer');
    if (!container) return;

    if (window.currentUser && window.currentUser.id) {
        const initials = getUserInitials(window.currentUser.name);
        const username = window.currentUser.username || 'member';
        container.innerHTML = `
            <div class="flex items-center gap-2 bg-[#2e3035] border border-[#2d2f33] rounded-[12px] px-2.5 py-1.5">
                <div class="w-7 h-7 rounded-[8px] bg-[#00b24b] text-white font-extrabold text-xs flex items-center justify-center uppercase">
                    ${escapeHtml(initials)}
                </div>
                <div class="hidden sm:block text-left max-w-[120px]">
                    <p class="text-xs font-extrabold text-[#f0f2f5] truncate leading-tight">${escapeHtml(window.currentUser.name)}</p>
                    <p class="text-[10px] font-semibold text-[#00b24b] truncate leading-tight">@<span>${escapeHtml(username)}</span></p>
                </div>
                <button onclick="handleLogout()" class="ml-1 px-2.5 py-1 rounded-[8px] bg-[#141415] hover:bg-red-500/20 text-[#9aa1b1] hover:text-red-400 border border-[#2d2f33] hover:border-red-500/40 text-[11px] font-semibold transition-colors" title="Keluar dari Akun">
                    Keluar
                </button>
            </div>
        `;
    } else {
        container.innerHTML = `
            <div class="flex items-center gap-2">
                <button onclick="openAuthModal('login')" class="px-3 py-2 rounded-[10px] bg-[#0075ff] hover:bg-[#0062d6] text-white font-semibold text-[13px] leading-[1.3] transition-colors inline-flex items-center gap-1.5 whitespace-nowrap">
                    <span>Masuk</span>
                </button>
                <button onclick="openAuthModal('register')" class="px-3 py-2 rounded-[10px] bg-[#00b24b] hover:bg-[#00c853] text-white font-semibold text-[13px] leading-[1.3] transition-colors inline-flex items-center gap-1.5 whitespace-nowrap">
                    <span>Daftar</span>
                </button>
            </div>
        `;
    }
}

function openAuthModal(tab = 'login') {
    const modal = document.getElementById('authModal');
    if (!modal) return;
    switchAuthTab(tab);
    hideAuthError();
    modal.classList.remove('modal-closing');
    modal.classList.add('active');
    lockBodyScroll();
}

function closeAuthModal() {
    const modal = document.getElementById('authModal');
    if (!modal || !modal.classList.contains('active')) return;
    modal.classList.add('modal-closing');
    setTimeout(() => {
        modal.classList.remove('active', 'modal-closing');
        unlockBodyScroll();
    }, 200);
}

function switchAuthTab(tab) {
    const loginForm = document.getElementById('authLoginForm');
    const regForm = document.getElementById('authRegisterForm');
    const heading = document.getElementById('authModalHeading');
    const subheading = document.getElementById('authModalSubheading');
    hideAuthError();

    if (tab === 'register') {
        if (loginForm) loginForm.classList.add('hidden');
        if (regForm) regForm.classList.remove('hidden');
        if (heading) heading.textContent = 'Buat Akun Baru';
        if (subheading) {
            subheading.innerHTML = `Sudah punya akun? <button type="button" onclick="switchAuthTab('login')" class="text-[#0075ff] hover:underline font-semibold ml-1">Masuk di sini (Sign in)</button>`;
        }
    } else {
        if (regForm) regForm.classList.add('hidden');
        if (loginForm) loginForm.classList.remove('hidden');
        if (heading) heading.textContent = 'Welcome Back';
        if (subheading) {
            subheading.innerHTML = `Belum punya akun? <button type="button" onclick="switchAuthTab('register')" class="text-[#0075ff] hover:underline font-semibold ml-1">Daftar akun (Sign up)</button>`;
        }
    }
}

function showAuthError(msg) {
    const banner = document.getElementById('authErrorBanner');
    if (!banner) return;
    banner.textContent = msg;
    banner.classList.remove('hidden');
}

function hideAuthError() {
    const banner = document.getElementById('authErrorBanner');
    if (!banner) return;
    banner.textContent = '';
    banner.classList.add('hidden');
}

function toggleAuthPasswordVisibility(inputId, eyeIconId = null, eyeOffIconId = null) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isNowText = input.type === 'password';
    input.type = isNowText ? 'text' : 'password';

    if (eyeIconId && eyeOffIconId) {
        const eye = document.getElementById(eyeIconId);
        const eyeOff = document.getElementById(eyeOffIconId);
        if (eye && eyeOff) {
            if (isNowText) {
                eye.classList.add('hidden');
                eyeOff.classList.remove('hidden');
            } else {
                eye.classList.remove('hidden');
                eyeOff.classList.add('hidden');
            }
        }
    }
}

function fillDemoAccount(username, password) {
    switchAuthTab('login');
    const loginInput = document.getElementById('loginIdentifier');
    const passInput = document.getElementById('loginPassword');
    if (loginInput) loginInput.value = username;
    if (passInput) passInput.value = password;
}

function handleLoginSubmit(e) {
    e.preventDefault();
    hideAuthError();

    const login = document.getElementById('loginIdentifier')?.value.trim();
    const password = document.getElementById('loginPassword')?.value;
    if (!login || !password) {
        showAuthError('Harap isi username/email dan password Anda.');
        return;
    }

    const submitBtn = document.getElementById('authLoginSubmitBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Memverifikasi Akun...';
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    fetch('/api/auth/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ login, password })
    })
    .then(async res => {
        const data = await res.json();
        return { ok: res.ok, data };
    })
    .then(({ ok, data }) => {
        if (!ok || !data.success) {
            const firstErr = data.errors ? Object.values(data.errors)[0]?.[0] : null;
            showAuthError(firstErr || data.message || 'Gagal masuk. Periksa kembali username/email dan password.');
            return;
        }

        window.currentUser = data.user;
        updateCsrfToken(data.csrf_token);
        renderNavAuthState();
        closeAuthModal();
        showToast(data.message || `Selamat datang, ${data.user.name}!`, 'success');
        loadPlaylists();
        loadPlaylistsDropdown();

        if (window.pendingOpenCreatePlaylistAfterAuth) {
            window.pendingOpenCreatePlaylistAfterAuth = false;
            setTimeout(() => openCreatePlaylistModal(), 240);
        }
    })
    .catch(() => {
        showAuthError('Terjadi kesalahan jaringan saat menghubungi server.');
    })
    .finally(() => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Masuk ke Akun';
        }
    });
}

function handleRegisterSubmit(e) {
    e.preventDefault();
    hideAuthError();

    const username = document.getElementById('registerUsername')?.value.trim();
    const email = document.getElementById('registerEmail')?.value.trim();
    const password = document.getElementById('registerPassword')?.value;

    if (!username || !email || !password) {
        showAuthError('Harap lengkapi username, email, dan password.');
        return;
    }

    const submitBtn = document.getElementById('authRegisterSubmitBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Mendaftarkan Akun...';
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    fetch('/api/auth/register', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ username, email, password })
    })
    .then(async res => {
        const data = await res.json();
        return { ok: res.ok, data };
    })
    .then(({ ok, data }) => {
        if (!ok || !data.success) {
            const firstErr = data.errors ? Object.values(data.errors)[0]?.[0] : null;
            showAuthError(firstErr || data.message || 'Gagal mendaftarkan akun.');
            return;
        }

        window.currentUser = data.user;
        updateCsrfToken(data.csrf_token);
        renderNavAuthState();
        closeAuthModal();
        showToast(data.message || `Akun berhasil dibuat! Selamat datang, ${data.user.name}!`, 'success');
        loadPlaylists();
        loadPlaylistsDropdown();

        document.getElementById('registerUsername').value = '';
        document.getElementById('registerEmail').value = '';
        document.getElementById('registerPassword').value = '';

        if (window.pendingOpenCreatePlaylistAfterAuth) {
            window.pendingOpenCreatePlaylistAfterAuth = false;
            setTimeout(() => openCreatePlaylistModal(), 240);
        }
    })
    .catch(() => {
        showAuthError('Terjadi kesalahan jaringan saat mendaftarkan akun.');
    })
    .finally(() => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Buat Akun & Masuk';
        }
    });
}

function handleLogout() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    fetch('/api/auth/logout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        window.currentUser = null;
        updateCsrfToken(data.csrf_token);
        renderNavAuthState();
        showToast(data.message || 'Anda telah keluar dari akun.', 'info');
        loadPlaylists();
        loadPlaylistsDropdown();
    })
    .catch(() => {
        window.currentUser = null;
        renderNavAuthState();
    });
}

/* ==================== 10. RECOMMENDED HORIZONTAL CAROUSEL SLIDER (6 VISIBLE + SLIDE TO 10) ==================== */
function slideRecommended(direction) {
    const container = document.getElementById('forYouGrid');
    if (!container) return;
    const card = container.firstElementChild;
    const step = card ? (card.getBoundingClientRect().width + 16) : (container.clientWidth / 6);
    container.scrollBy({
        left: direction * step,
        behavior: 'smooth'
    });
}

function seekRecommendedStep(percent) {
    const container = document.getElementById('forYouGrid');
    if (!container) return;
    const maxScroll = Math.max(0, container.scrollWidth - container.clientWidth);
    const pct = Math.max(0, Math.min(100, parseFloat(percent) || 0));
    container.scrollTo({
        left: (pct / 100) * maxScroll,
        behavior: 'smooth'
    });
}

function seekRecommendedSlider(percent) {
    const container = document.getElementById('forYouGrid');
    if (!container) return;
    const maxScroll = Math.max(0, container.scrollWidth - container.clientWidth);
    const pct = Math.max(0, Math.min(100, parseFloat(percent) || 0));
    container.scrollLeft = (pct / 100) * maxScroll;
    updateRecommendedSliderVisuals(pct);
}

function onRecommendedScroll() {
    const container = document.getElementById('forYouGrid');
    if (!container) return;
    const maxScroll = Math.max(1, container.scrollWidth - container.clientWidth);
    const pct = Math.max(0, Math.min(100, (container.scrollLeft / maxScroll) * 100));
    updateRecommendedSliderVisuals(pct);
}

function updateRecommendedSliderVisuals(pct) {
    const thumb = document.getElementById('recommendedScrollbarThumb');
    const prevBtn = document.getElementById('recPrevBtn');
    const nextBtn = document.getElementById('recNextBtn');

    // Thumb memiliki lebar 60% dari track (6 dari 10 kartu), sehingga translateX bergerak dari 0% hingga 66.67% dari lebar dirinya sendiri
    if (thumb) {
        const maxTranslatePct = ((100 - 60) / 60) * 100; // 66.6667%
        const translatePct = (pct / 100) * maxTranslatePct;
        thumb.style.transform = `translateX(${translatePct.toFixed(2)}%)`;
    }

    if (prevBtn) {
        const atStart = pct <= 1;
        prevBtn.disabled = atStart;
        prevBtn.className = atStart
            ? 'w-9 h-9 rounded-full bg-[#2e3035] border border-[#2d2f33] text-[#53575f] cursor-not-allowed flex items-center justify-center transition-all'
            : 'w-9 h-9 rounded-full bg-[#2e3035] hover:bg-[#383b42] border border-[#2d2f33] text-[#f0f2f5] flex items-center justify-center transition-all active:scale-95';
    }

    if (nextBtn) {
        const atEnd = pct >= 99;
        nextBtn.disabled = atEnd;
        nextBtn.className = atEnd
            ? 'w-9 h-9 rounded-full bg-[#2e3035] border border-[#2d2f33] text-[#53575f] cursor-not-allowed flex items-center justify-center transition-all'
            : 'w-9 h-9 rounded-full bg-[#2e3035] hover:bg-[#383b42] border border-[#2d2f33] text-[#f0f2f5] flex items-center justify-center transition-all active:scale-95';
    }
}

// Inisialisasi drag interaktif pada track scrollbar & drag-to-scroll pada deretan kartu rekomendasi
document.addEventListener('DOMContentLoaded', () => {
    const track = document.getElementById('recommendedScrollbarTrack');
    const thumb = document.getElementById('recommendedScrollbarThumb');
    const container = document.getElementById('forYouGrid');

    if (track && thumb && container) {
        let isDraggingThumb = false;
        let grabOffsetX = 0;

        // Jika diklik pada bagian hijau (thumb) di mana pun, JANGAN pindah posisi kecuali digeser (drag)
        thumb.addEventListener('mousedown', (e) => {
            e.stopPropagation();
            e.preventDefault();
            isDraggingThumb = true;
            const thumbRect = thumb.getBoundingClientRect();
            grabOffsetX = e.clientX - thumbRect.left;
        });

        // Jika diklik pada bagian track kosong (di luar bagian hijau), geser ke posisi tersebut
        track.addEventListener('mousedown', (e) => {
            if (e.target === thumb || thumb.contains(e.target)) return;
            const trackRect = track.getBoundingClientRect();
            const thumbRect = thumb.getBoundingClientRect();
            const thumbWidth = thumbRect.width || (trackRect.width * 0.6);
            const usableWidth = Math.max(1, trackRect.width - thumbWidth);
            const offset = e.clientX - trackRect.left - (thumbWidth / 2);
            const pct = Math.max(0, Math.min(100, (offset / usableWidth) * 100));
            seekRecommendedStep(pct);
        });

        window.addEventListener('mousemove', (e) => {
            if (!isDraggingThumb) return;
            e.preventDefault();
            const trackRect = track.getBoundingClientRect();
            const thumbRect = thumb.getBoundingClientRect();
            const thumbWidth = thumbRect.width || (trackRect.width * 0.6);
            const usableWidth = Math.max(1, trackRect.width - thumbWidth);
            const newThumbLeft = e.clientX - trackRect.left - grabOffsetX;
            const pct = Math.max(0, Math.min(100, (newThumbLeft / usableWidth) * 100));
            seekRecommendedSlider(pct);
        });

        window.addEventListener('mouseup', () => {
            isDraggingThumb = false;
        });

        // Drag-to-scroll langsung pada kartu #forYouGrid
        let isDraggingCards = false;
        let startX = 0;
        let startScrollLeft = 0;

        container.addEventListener('mousedown', (e) => {
            if (e.target.closest('button')) return;
            isDraggingCards = true;
            startX = e.pageX;
            startScrollLeft = container.scrollLeft;
        });

        window.addEventListener('mousemove', (e) => {
            if (!isDraggingCards) return;
            const dx = e.pageX - startX;
            if (Math.abs(dx) > 4) {
                container.scrollLeft = startScrollLeft - dx;
            }
        });

        window.addEventListener('mouseup', () => {
            isDraggingCards = false;
        });
    }
});
