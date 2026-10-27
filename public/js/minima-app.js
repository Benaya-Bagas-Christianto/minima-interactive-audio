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
                else if (modal.id === 'videoModal') closeVideoModal();
                else {
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
                else if (m.id === 'videoModal') closeVideoModal();
                else {
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
function initNavigation() {
    const navButtonsContainer = document.getElementById('nav-buttons-container');
    const slider = document.getElementById('nav-slider');
    const homeBtn = document.querySelector('.nav-btn');

    if (slider && homeBtn) {
        slider.style.left = `${homeBtn.offsetLeft}px`;
        slider.style.width = `${homeBtn.offsetWidth}px`;
    }
}

let sectionTransitionTimer = null;

function showSection(sectionId, clickedBtn) {
    const target = document.getElementById(sectionId);
    if (!target) return;

    const current = document.querySelector('.section.active');

    // Update navbar buttons & slider immediately for instant, springy visual response
    const slider = document.getElementById('nav-slider');
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

    if (clickedBtn && slider) {
        clickedBtn.classList.add('active', 'text-white');
        slider.style.opacity = '1';
        slider.style.left = `${clickedBtn.offsetLeft}px`;
        slider.style.width = `${clickedBtn.offsetWidth}px`;
    }

    if (sectionId === 'about') {
        const aboutBtn = document.getElementById('btn-about');
        if (aboutBtn && slider) slider.style.opacity = '0';
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
        progressBar.style.background = `linear-gradient(to right, #10b981 0%, #10b981 ${p}%, #334155 ${p}%, #334155 100%)`;
    }
    const vinylProgressBar = document.getElementById('vinylProgressBar');
    if (vinylProgressBar) {
        vinylProgressBar.value = p;
        vinylProgressBar.style.background = `linear-gradient(to right, #10b981 0%, #10b981 ${p}%, #334155 ${p}%, #334155 100%)`;
    }
}

function updateTimeDisplays(cur, dur) {
    const curStr = formatTime(cur);
    const durStr = formatTime(dur);
    const curTime = document.getElementById('playerCurrentTime');
    const durTime = document.getElementById('playerDuration');
    const vinylCur = document.getElementById('vinylCurrentTime');
    const vinylDur = document.getElementById('vinylDuration');

    if (curTime) curTime.textContent = curStr;
    if (durTime) durTime.textContent = durStr;
    if (vinylCur) vinylCur.textContent = curStr;
    if (vinylDur) vinylDur.textContent = durStr;
}

function startYtProgressTimer() {
    stopYtProgressTimer();
    const progressBar = document.getElementById('playerProgressBar');
    const vinylProgressBar = document.getElementById('vinylProgressBar');

    ytProgressTimer = setInterval(() => {
        if (!isSeekingAudio && ytAudioPlayer && isYtReady && isAudioPlaying) {
            try {
                const cur = ytAudioPlayer.getCurrentTime() || 0;
                const dur = ytAudioPlayer.getDuration() || 0;
                if (dur > 0) {
                    const progress = (cur / dur) * 100;
                    if (progressBar) progressBar.value = progress;
                    if (vinylProgressBar) vinylProgressBar.value = progress;
                    updateProgressBarVisual(progress);
                    updateTimeDisplays(cur, dur);
                }
            } catch (e) {}
        }
    }, 250);
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

    audio.addEventListener('timeupdate', () => {
        if (!isSeekingAudio && currentPlaybackMode === 'html5' && audio.duration) {
            const progress = (audio.currentTime / audio.duration) * 100;
            if (progressBar) progressBar.value = progress;
            if (vinylProgressBar) vinylProgressBar.value = progress;
            updateProgressBarVisual(progress);
            updateTimeDisplays(audio.currentTime, audio.duration);
        }
    });

    audio.addEventListener('loadedmetadata', () => {
        if (currentPlaybackMode === 'html5' && audio.duration) {
            updateTimeDisplays(audio.currentTime, audio.duration);
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
}

function isLocalFullAudio(url) {
    if (!url) return false;
    return url.includes('audio/') || url.includes('.mp3') || url.startsWith('/audio');
}

function fetchFullAudioAndPlay(id, title, artist, fallbackAudioUrl) {
    showToast(`Memuat audio penuh: ${title}...`, 'info');
    fetch(`/api/musics/${id}/prepare-audio`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.audio_url) {
                currentPlaybackMode = 'html5';
                if (currentSong && currentSong.id === id) {
                    currentSong.audioUrl = data.audio_url;
                }
                startAudioPlayback(data.audio_url);
                showToast(`Memutar Full Durasi: ${title} - ${artist}`, 'success');
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

function playSong(id, title, artist, cover, audioUrl, videoId) {
    // Format audioUrl jika menggunakan format path lokal relatif
    let resolvedAudioUrl = audioUrl;
    if (resolvedAudioUrl && (resolvedAudioUrl.startsWith('audio/') || resolvedAudioUrl.includes('/audio/'))) {
        resolvedAudioUrl = resolvedAudioUrl.startsWith('/') ? resolvedAudioUrl : '/' + resolvedAudioUrl;
    }

    currentSong = { id, title, artist, cover, audioUrl: resolvedAudioUrl, videoId };

    if (!currentSongQueue.some(s => s.id === id)) {
        currentSongQueue.push({ id, title, artist, image: cover, audio_url: resolvedAudioUrl, video_id: videoId });
    }

    // Tampilkan player bar di bagian bawah layar saat lagu pertama kali diklik
    const playerBar = document.getElementById('bottomAudioPlayer');
    if (playerBar) {
        playerBar.classList.remove('translate-y-full', 'opacity-0', 'pointer-events-none');
        playerBar.classList.add('translate-y-0', 'opacity-100');
    }

    document.getElementById('playerTitle').textContent = title;
    document.getElementById('playerArtist').textContent = artist;
    document.getElementById('playerCover').src = cover;

    const vinylTitle = document.getElementById('vinylSongTitle');
    const vinylArtist = document.getElementById('vinylSongArtist');
    const vinylCover = document.getElementById('vinylModalCover');
    if (vinylTitle) vinylTitle.textContent = title;
    if (vinylArtist) vinylArtist.textContent = artist;
    if (vinylCover) vinylCover.src = cover;

    updateVinylTexture(cover);

    // Hentikan pemutar HTML5 jika sedang berjalan
    const html5Audio = document.getElementById('globalAudioPlayer');
    if (html5Audio && !html5Audio.paused) {
        html5Audio.pause();
        html5Audio.currentTime = 0;
    }
    // Hentikan YouTube Player jika sedang berjalan
    if (ytAudioPlayer && isYtReady) {
        try { ytAudioPlayer.stopVideo(); } catch (e) {}
    }
    stopYtProgressTimer();
    stopSynthesizedPreview();

    // PRIORITAS 1: Gunakan Stream Audio Server Lokal dengan Dukungan HTTP 206 (Byte-Ranges)
    // agar fitur klik & seret (seek/scrub) ke menit lagu berjalan 100% mulus dan instan
    if (id) {
        currentPlaybackMode = 'html5';
        const streamUrl = `/api/musics/${id}/stream`;
        currentSong.audioUrl = streamUrl;
        startAudioPlayback(streamUrl);
        showToast(`Memutar Full Durasi: ${title} - ${artist}`, 'success');
        return;
    }

    // PRIORITAS 2: Putar stream YouTube jika ada videoId
    const vid = videoId || (currentSong && currentSong.videoId);
    if (vid && vid.length >= 8) {
        currentPlaybackMode = 'youtube';
        if (isYtReady && ytAudioPlayer) {
            playYouTubeTrack(vid);
            showToast(`Memutar Full Durasi: ${title} - ${artist}`);
        } else {
            pendingYtVideoId = vid;
            showToast(`Memuat pemutar musik: ${title} - ${artist}`);
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
            showToast(`Memutar Audio Online: ${currentSong.title} - ${currentSong.artist}`);
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

function startAudioPlayback(audioUrl) {
    const audio = document.getElementById('globalAudioPlayer');
    if (audioUrl && audioUrl.trim() !== '') {
        audio.src = audioUrl;
        audio.play().then(() => {
            isAudioPlaying = true;
            updatePlayIcons(true);
        }).catch((err) => {
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
                showToast('Musik dijeda (Paused)');
            } else {
                ytAudioPlayer.playVideo();
                isAudioPlaying = true;
                updatePlayIcons(true);
                startYtProgressTimer();
                showToast('Memutar musik...');
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
        showToast('Audio dijeda (Paused)');
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
        showToast('Memutar audio...');
    }
}

function closeAudioPlayer() {
    // 1. Hentikan pemutar HTML5 dan bersihkan sumber audio
    const html5Audio = document.getElementById('globalAudioPlayer');
    if (html5Audio) {
        html5Audio.pause();
        html5Audio.currentTime = 0;
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
    if (progressBar) {
        progressBar.value = 0;
        updateProgressBarVisual(0);
    }
    if (vinylProgressBar) {
        vinylProgressBar.value = 0;
    }
    if (curTime) curTime.textContent = '0:00';
    if (vinylCurTime) vinylCurTime.textContent = '0:00';

    // 6. Sembunyikan bottom player bar dengan animasi mulus
    const playerBar = document.getElementById('bottomAudioPlayer');
    if (playerBar) {
        playerBar.classList.remove('translate-y-0', 'opacity-100');
        playerBar.classList.add('translate-y-full', 'opacity-0', 'pointer-events-none');
    }

    // 7. Bersihkan data lagu yang sedang diputar
    currentSong = null;

    showToast('Pemutar musik dimatikan.');
}

function updatePlayIcons(playing) {
    const playIcon = document.getElementById('playIcon');
    const pauseIcon = document.getElementById('pauseIcon');
    const vinylPlayIcon = document.getElementById('vinylModalPlayIcon');
    const vinylPauseIcon = document.getElementById('vinylModalPauseIcon');
    const vinylBadge = document.getElementById('vinylStatusBadge');

    if (playing) {
        if (playIcon) playIcon.classList.add('hidden');
        if (pauseIcon) pauseIcon.classList.remove('hidden');
        if (vinylPlayIcon) vinylPlayIcon.classList.add('hidden');
        if (vinylPauseIcon) vinylPauseIcon.classList.remove('hidden');
        if (vinylBadge) {
            vinylBadge.textContent = 'Status: Berputar (Playing)';
            vinylBadge.className = 'px-3 py-1.5 rounded-full bg-emerald-950 text-emerald-400 font-semibold border border-emerald-800 text-xs shadow';
        }
    } else {
        if (playIcon) playIcon.classList.remove('hidden');
        if (pauseIcon) pauseIcon.classList.add('hidden');
        if (vinylPlayIcon) vinylPlayIcon.classList.remove('hidden');
        if (vinylPauseIcon) vinylPauseIcon.classList.add('hidden');
        if (vinylBadge) {
            vinylBadge.textContent = 'Status: Berhenti (Paused)';
            vinylBadge.className = 'px-3 py-1.5 rounded-full bg-slate-800 text-slate-400 font-semibold border border-slate-700 text-xs shadow';
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
                const curTime = document.getElementById('playerCurrentTime');
                if (curTime) curTime.textContent = formatTime(targetSec);
                return;
            }
        } catch (e) {}
    }

    const audio = document.getElementById('globalAudioPlayer');
    if (audio && audio.duration) {
        const targetSec = (val / 100) * audio.duration;
        audio.currentTime = targetSec;
        const curTime = document.getElementById('playerCurrentTime');
        if (curTime) curTime.textContent = formatTime(targetSec);
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
        showToast(`Memutar: ${prevSong.title} - ${prevSong.artist}`, 'info');
    }
}

function playNextSong() {
    if (!currentSongQueue || currentSongQueue.length === 0) {
        showToast('Antrean lagu kosong.', 'info');
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
        showToast(`Memutar: ${nextSong.title} - ${nextSong.artist}`, 'info');
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

    const emeraldRimLight = new THREE.PointLight(0x34d399, 1.6, 25);
    emeraldRimLight.position.set(-5, 4, 3);
    threeScene.add(emeraldRimLight);

    // 5. UNIFIED 3D TURNTABLE GROUP (All parts belong here so dragging rotates the whole solid unit!)
    turntableGroup = new THREE.Group();
    turntableGroup.rotation.x = turntablePitch;
    turntableGroup.rotation.y = turntableYaw;
    threeScene.add(turntableGroup);

    // A. Turntable Solid Chassis / Deck (Dark Slate with subtle metallic bevel)
    const deckGeo = new THREE.CylinderGeometry(3.5, 3.6, 0.35, 64);
    const deckMat = new THREE.MeshStandardMaterial({
        color: 0x0f172a,
        roughness: 0.5,
        metalness: 0.7
    });
    const deckMesh = new THREE.Mesh(deckGeo, deckMat);
    deckMesh.position.y = -0.2;
    turntableGroup.add(deckMesh);

    // Bottom solid plate to guarantee zero see-through from any angle
    const bottomPlateGeo = new THREE.CylinderGeometry(3.6, 3.6, 0.08, 64);
    const bottomPlateMat = new THREE.MeshStandardMaterial({ color: 0x020617, roughness: 0.9 });
    const bottomPlate = new THREE.Mesh(bottomPlateGeo, bottomPlateMat);
    bottomPlate.position.y = -0.38;
    turntableGroup.add(bottomPlate);

    // B. Die-cast Metal Platter
    const platterGeo = new THREE.CylinderGeometry(3.2, 3.25, 0.18, 64);
    const platterMat = new THREE.MeshStandardMaterial({
        color: 0x334155,
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
            color: 0x1e293b,
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
    const spindleMat = new THREE.MeshStandardMaterial({ color: 0xf8fafc, roughness: 0.1, metalness: 0.95 });
    const spindleMesh = new THREE.Mesh(spindleGeo, spindleMat);
    spindleMesh.position.y = 0.24;
    turntableGroup.add(spindleMesh);

    // F. 3D Mechanical Tonearm Assembly
    const tonearmGroup = new THREE.Group();
    tonearmGroup.position.set(2.7, 0.12, 1.8);

    const baseArmGeo = new THREE.CylinderGeometry(0.32, 0.36, 0.35, 24);
    const baseArmMat = new THREE.MeshStandardMaterial({ color: 0x334155, roughness: 0.35, metalness: 0.85 });
    const baseArm = new THREE.Mesh(baseArmGeo, baseArmMat);
    tonearmGroup.add(baseArm);

    // Counterweight
    const counterGeo = new THREE.CylinderGeometry(0.24, 0.24, 0.35, 24);
    const counterMat = new THREE.MeshStandardMaterial({ color: 0x0f172a, roughness: 0.4, metalness: 0.9 });
    const counter = new THREE.Mesh(counterGeo, counterMat);
    counter.rotation.x = Math.PI / 2;
    counter.position.set(0.35, 0.15, 0.65);
    tonearmGroup.add(counter);

    // Chrome Arm Rod
    const rodGeo = new THREE.CylinderGeometry(0.045, 0.045, 3.1, 16);
    const rodMat = new THREE.MeshStandardMaterial({ color: 0xe2e8f0, roughness: 0.1, metalness: 0.95 });
    const rod = new THREE.Mesh(rodGeo, rodMat);
    rod.rotation.z = Math.PI / 2.3;
    rod.rotation.y = -Math.PI / 6.2;
    rod.position.set(-1.1, 0.26, -0.9);
    tonearmGroup.add(rod);

    // Emerald Stylus Cartridge
    const cartGeo = new THREE.BoxGeometry(0.18, 0.12, 0.35);
    const cartMat = new THREE.MeshStandardMaterial({ color: 0x10b981, roughness: 0.3, metalness: 0.8 });
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
        if (isDragging && !hasMoved) {
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

function updateVinylTexture(imageUrl) {
    if (!vinylMesh) return;

    const loader = new THREE.TextureLoader();
    loader.crossOrigin = 'anonymous';
    loader.load(imageUrl, (texture) => {
        // High-definition texture rendering
        texture.generateMipmaps = true;
        texture.minFilter = THREE.LinearMipmapLinearFilter;
        texture.magFilter = THREE.LinearFilter;
        if (threeRenderer && threeRenderer.capabilities) {
            texture.anisotropy = Math.min(16, threeRenderer.capabilities.getMaxAnisotropy() || 1);
        }
        texture.center.set(0.5, 0.5);
        texture.needsUpdate = true;

        const oldLabel = vinylMesh.getObjectByName('vinylLabel');
        if (oldLabel) vinylMesh.remove(oldLabel);

        const labelGroup = new THREE.Group();
        labelGroup.name = 'vinylLabel';

        // 1. High-resolution circular image label (Right-side up with -Math.PI / 2)
        const labelGeo = new THREE.CircleGeometry(1.15, 64);
        const labelMat = new THREE.MeshBasicMaterial({
            map: texture,
            side: THREE.FrontSide
        });
        const labelMesh = new THREE.Mesh(labelGeo, labelMat);
        labelMesh.rotation.x = -Math.PI / 2; // NORMAL POINTS STRAIGHT UP (+Y)!
        labelMesh.position.y = 0.027; // sits flush on top of vinyl disc
        labelGroup.add(labelMesh);

        // 2. Shiny metallic emerald outer rim ring
        const rimGeo = new THREE.RingGeometry(1.14, 1.18, 64);
        const rimMat = new THREE.MeshStandardMaterial({
            color: 0x34d399,
            metalness: 0.9,
            roughness: 0.2,
            side: THREE.FrontSide
        });
        const rimMesh = new THREE.Mesh(rimGeo, rimMat);
        rimMesh.rotation.x = -Math.PI / 2;
        rimMesh.position.y = 0.028;
        labelGroup.add(rimMesh);

        // 3. Center chrome spindle hole ring
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
        labelGroup.add(holeMesh);

        vinylMesh.add(labelGroup);
    }, undefined, (err) => {
        console.warn('Failed to load vinyl texture:', err);
    });
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

function openVinylModal() {
    const modal = document.getElementById('vinylModal');
    modal.classList.add('active');
    lockBodyScroll();
    if (currentSong) {
        if (currentSong.title) document.getElementById('vinylSongTitle').textContent = currentSong.title;
        if (currentSong.artist) document.getElementById('vinylSongArtist').textContent = currentSong.artist;
        const vinylCover = document.getElementById('vinylModalCover');
        if (vinylCover && currentSong.cover) vinylCover.src = currentSong.cover;
        if (currentSong.cover) updateVinylTexture(currentSong.cover);
    }
    setTimeout(onWindowResize, 50);
}

function closeVinylModal() {
    const modal = document.getElementById('vinylModal');
    if (!modal || !modal.classList.contains('active')) return;
    modal.classList.add('modal-closing');
    setTimeout(() => {
        modal.classList.remove('active', 'modal-closing');
        unlockBodyScroll();
    }, 220);
}

function openVinylWithSong(title, artist, cover, id, audioUrl, videoId) {
    document.getElementById('vinylSongTitle').textContent = title;
    document.getElementById('vinylSongArtist').textContent = artist;
    const vinylCover = document.getElementById('vinylModalCover');
    if (vinylCover) vinylCover.src = cover;

    updateVinylTexture(cover);
    openVinylModal();

    // Jika lagu ini memiliki ID dan belum diputar / berbeda lagu, langsung mulai putar!
    if (id) {
        playSong(id, title, artist, cover, audioUrl, videoId);
    }
}

/* ==================== 4. VIDEO MV PLAYER MODAL ==================== */
function openVideoModal(videoId, title, artist) {
    let song = currentSong || {};
    if (title && artist) {
        song = { title, artist, video_id: videoId, videoId };
    } else if (currentDetailSong && (!currentSong || currentDetailSong.id === currentSong.id)) {
        song = currentDetailSong;
    }

    const songTitle = song.title ? `${song.title} (${song.artist})` : 'Musik';
    document.getElementById('videoModalTitle').textContent = `Video Klip: ${songTitle}`;

    // Hentikan pemutaran audio latar belakang agar suaranya tidak bertabrakan dengan video klip
    if (currentPlaybackMode === 'youtube' && ytAudioPlayer && isYtReady) {
        try { ytAudioPlayer.pauseVideo(); } catch (e) {}
    }
    const audio = document.getElementById('globalAudioPlayer');
    if (audio && !audio.paused) {
        audio.pause();
    }
    isAudioPlaying = false;
    updatePlayIcons(false);
    stopYtProgressTimer();

    const iframe = document.getElementById('videoIframe');

    // Cek jika ada videoId spesifik dari YouTube
    const vid = videoId || (song && (song.video_id || song.videoId));
    const isBohemian = song.title && song.title.toLowerCase().includes('bohemian');

    let startSec = 0;
    if (currentPlaybackMode === 'youtube' && ytAudioPlayer && isYtReady) {
        try {
            startSec = Math.floor(ytAudioPlayer.getCurrentTime() || 0);
        } catch (e) {}
    }
    const startParam = startSec > 0 ? `&start=${startSec}` : '';

    if (vid && (vid !== 'fJ9rUzIMcZQ' || isBohemian) && vid.length >= 8) {
        iframe.src = `https://www.youtube.com/embed/${vid}?autoplay=1&enablejsapi=1${startParam}`;
    } else if (song.title && song.artist) {
        // Embed YouTube pencarian otomatis MV resmi untuk lagu & artis ini
        const query = encodeURIComponent(`${song.artist} ${song.title} official music video`);
        iframe.src = `https://www.youtube.com/embed?listType=search&list=${query}&autoplay=1`;
    } else {
        iframe.src = `https://www.youtube.com/embed/fJ9rUzIMcZQ?autoplay=1&enablejsapi=1`;
    }

    document.getElementById('videoModal').classList.add('active');
    lockBodyScroll();
}

function closeVideoModal() {
    const modal = document.getElementById('videoModal');
    const iframe = document.getElementById('videoIframe');
    iframe.src = '';
    if (!modal || !modal.classList.contains('active')) return;
    modal.classList.add('modal-closing');
    setTimeout(() => {
        modal.classList.remove('active', 'modal-closing');
        unlockBodyScroll();
    }, 220);
}

/* ==================== 5. CATALOG & FILTER API (PAGINATION MULTI-HALAMAN) ==================== */
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
    grid.innerHTML = '<div class="col-span-full py-16 text-center text-slate-400"><div class="inline-block w-8 h-8 border-3 border-emerald-500 border-t-transparent rounded-full animate-spin mb-3"></div><p class="text-sm font-medium">Memuat lagu untuk halaman ini...</p></div>';

    fetch(url)
        .then(res => res.json())
        .then(data => {
            const songs = data.data || [];
            currentSongQueue = songs;
            window.songDataCache = window.songDataCache || {};
            songs.forEach(s => {
                window.songDataCache[s.id] = s;
            });

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
                <div class="song-card-animated bg-slate-800/80 rounded-2xl p-4 border border-slate-700/60 hover:border-emerald-500/50 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-emerald-500/10 group shadow-lg flex flex-col justify-between" style="animation-delay: ${Math.min(idx * 22, 260)}ms;">
                    <div>
                        <div class="relative overflow-hidden rounded-xl aspect-square mb-4 bg-slate-700">
                            <img src="${song.image ? '/' + song.image : '/image/queen1.jpg'}" alt="${song.title}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <div class="absolute inset-0 bg-slate-950/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                <button onclick="playSong(${song.id}, '${escapeQuote(song.title)}', '${escapeQuote(song.artist)}', '/${song.image || 'image/queen1.jpg'}', '${song.audio_url || ''}', '${song.video_id || ''}')" class="w-11 h-11 bg-emerald-500 hover:bg-emerald-400 text-slate-950 rounded-full flex items-center justify-center font-bold shadow-xl transition-transform hover:scale-110" title="Putar Lagu">
                                    <svg class="w-5 h-5 fill-current translate-x-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                </button>
                                <button onclick="openVinylWithSong('${escapeQuote(song.title)}', '${escapeQuote(song.artist)}', '/${song.image || 'image/queen1.jpg'}', ${song.id}, '${song.audio_url || ''}', '${song.video_id || ''}')" class="w-9 h-9 bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-emerald-500/40 rounded-full flex items-center justify-center shadow-xl transition-transform hover:scale-110" title="Putar di Piringan 3D">
                                    <span class="text-xs font-black">3D</span>
                                </button>
                            </div>
                            <span class="absolute top-2.5 right-2.5 bg-black/70 backdrop-blur-md px-2 py-0.5 rounded-md text-xs font-bold text-yellow-400 border border-white/10">★ ${song.rating}</span>
                        </div>
                        <h3 class="font-bold text-white text-base truncate cursor-pointer hover:text-emerald-400" onclick="openMusicModal(${song.id})">${song.title}</h3>
                        <p class="text-xs text-slate-400 truncate mt-0.5">${song.artist} • ${song.genre}</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-700/50 flex items-center justify-between text-xs text-slate-400">
                        <span>${song.year}</span>
                        <button onclick="openMusicModal(${song.id})" class="text-emerald-400 hover:underline font-medium">Detail & Analisis →</button>
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

    // Hitung nomor halaman dengan sliding window (awalnya berhenti di 4 tanpa ... 10, lalu muncul saat digeser / klik halaman berikutnya)
    let pages = [];
    if (last <= 6) {
        pages = Array.from({ length: last }, (_, i) => i + 1);
    } else if (cur === 1) {
        // Halaman awal: berhenti tepat di 4 (1 2 3 4), tanpa "... 10"
        for (let i = 1; i <= Math.min(4, last); i++) {
            pages.push(i);
        }
    } else {
        let startPage, endPage;
        if (cur <= 2) {
            startPage = 1;
            endPage = 4;
        } else if (cur >= last - 1) {
            startPage = last - 3;
            endPage = last;
        } else {
            startPage = cur - 1;
            endPage = cur + 2;
            if (endPage > last) {
                endPage = last;
                startPage = last - 3;
            }
        }

        if (startPage > 1) {
            pages.push(1);
            if (startPage > 2) {
                pages.push('...');
            }
        }
        for (let i = startPage; i <= endPage; i++) {
            pages.push(i);
        }
        if (endPage < last) {
            if (endPage < last - 1) {
                pages.push('...');
            }
            pages.push(last);
        }
    }

    const pagesHtml = pages.map(p => {
        if (p === '...') {
            return `<span class="px-2 py-1 text-slate-500 font-bold select-none text-xs">...</span>`;
        }
        if (p === cur) {
            return `
                <button class="w-9 h-9 rounded-xl bg-emerald-500 text-slate-950 font-black text-xs shadow-lg shadow-emerald-500/25 transition-all transform scale-105" title="Halaman ${p}">
                    ${p}
                </button>
            `;
        }
        return `
            <button onclick="fetchMusics(${p}, true)" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/60 font-semibold text-xs transition-all hover:scale-105" title="Ke Halaman ${p}">
                ${p}
            </button>
        `;
    }).join('');

    const prevDisabled = cur <= 1;
    const nextDisabled = cur >= last;

    pag.innerHTML = `
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 w-full">
            <!-- Info Rentang Lagu & Halaman Aktif -->
            <div class="text-xs text-slate-400 text-center sm:text-left">
                Menampilkan <strong class="text-white">${from} - ${to}</strong> dari <strong class="text-emerald-400 font-bold">${total}</strong> lagu 
                <span class="text-slate-500 ml-1.5">(Halaman ${cur} dari ${last})</span>
            </div>

            <!-- Tombol Navigasi Halaman -->
            <div class="flex items-center space-x-1.5 select-none">
                <!-- Tombol Sebelumnya -->
                <button onclick="fetchMusics(${cur - 1}, true)" 
                    ${prevDisabled ? 'disabled' : ''} 
                    class="px-3.5 py-2 rounded-xl text-xs font-semibold flex items-center gap-1 transition-all ${prevDisabled ? 'bg-slate-800/40 text-slate-600 border border-slate-800/60 cursor-not-allowed opacity-50' : 'bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 hover:scale-105 cursor-pointer'}">
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
                    class="px-3.5 py-2 rounded-xl text-xs font-semibold flex items-center gap-1 transition-all ${nextDisabled ? 'bg-slate-800/40 text-slate-600 border border-slate-800/60 cursor-not-allowed opacity-50' : 'bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 hover:scale-105 cursor-pointer'}">
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
        b.className = 'genre-btn px-4 py-1.5 rounded-full text-xs font-semibold transition-all duration-300 bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white border border-slate-700/60';
    });
    if (btn) {
        btn.className = 'genre-btn px-4 py-1.5 rounded-full text-xs font-semibold transition-all duration-300 bg-emerald-500 text-slate-950 shadow-md shadow-emerald-500/25 scale-105';
    }
    fetchMusics();
}

function setDecadeFilter(decade, btn) {
    currentFilter.decade = decade;
    document.querySelectorAll('.decade-btn').forEach(b => {
        b.className = 'decade-btn px-3.5 py-1.5 rounded-lg text-xs font-medium transition-all duration-300 bg-slate-800 text-slate-400 hover:text-white border border-slate-700';
    });
    if (btn) {
        btn.className = 'decade-btn px-3.5 py-1.5 rounded-lg text-xs font-medium transition-all duration-300 bg-emerald-600/30 text-emerald-400 border border-emerald-500/40 shadow-sm shadow-emerald-500/20 scale-105';
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
            <div class="px-4 py-3 flex items-center gap-2.5 text-xs text-slate-400 bg-[#0f172a]">
                <svg class="animate-spin h-4 w-4 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
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
            item.classList.add('bg-slate-800', 'ring-1', 'ring-emerald-500/50');
            item.scrollIntoView({ block: 'nearest' });
        } else {
            item.classList.remove('bg-slate-800', 'ring-1', 'ring-emerald-500/50');
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
            dropdown.innerHTML = `<div class="p-3 text-xs text-red-400 text-center">Gagal memuat rekomendasi.</div>`;
        });
}

function renderSearchSuggestions(data, query) {
    const dropdown = document.getElementById('searchSuggestions');
    if (!dropdown) return;

    const artists = data.artists || [];
    const songs = data.songs || [];

    if (artists.length === 0 && songs.length === 0) {
        dropdown.innerHTML = `
            <div class="px-4 py-5 text-center bg-[#0f172a]">
                <p class="text-xs text-slate-400">Tidak ada rekomendasi yang cocok untuk "<span class="text-white font-medium">${escapeHtml(query)}</span>"</p>
                <button onclick="triggerSearch()" class="mt-2.5 text-xs text-emerald-400 hover:text-emerald-300 font-medium inline-flex items-center gap-1">
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
            <div class="p-2 border-b border-slate-800 bg-[#0f172a]">
                <div class="px-2.5 py-1 text-[10px] font-bold tracking-wider text-slate-400 uppercase flex items-center gap-1.5">
                    <svg class="w-3 h-3 text-emerald-400" viewBox="0 0 24 24" fill="currentColor">
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
                    class="suggestion-item flex items-center justify-between p-2 rounded-xl hover:bg-slate-800 cursor-pointer transition-colors group">
                    <div class="flex items-center gap-3 truncate">
                        <img src="${imgSrc}" alt="${artist.name}" onerror="this.src='/image/queen1.jpg'" class="w-9 h-9 rounded-full object-cover border border-slate-700 group-hover:border-emerald-500/50 flex-shrink-0">
                        <div class="truncate">
                            <h4 class="text-sm font-semibold text-white group-hover:text-emerald-400 transition-colors truncate">
                                ${escapeHtml(artist.name)}
                            </h4>
                            <p class="text-[11px] text-slate-400 truncate">
                                Artis ${artist.listeners ? `• ${artist.listeners} pendengar` : ''}
                            </p>
                        </div>
                    </div>
                    <span class="text-xs text-slate-500 group-hover:text-emerald-400 ml-2 flex-shrink-0">Lihat Lagu →</span>
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
            <div class="p-2 bg-[#0f172a]">
                <div class="px-2.5 py-1 text-[10px] font-bold tracking-wider text-slate-400 uppercase flex items-center gap-1.5">
                    <svg class="w-3 h-3 text-emerald-400" viewBox="0 0 24 24" fill="currentColor">
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
                    class="suggestion-item flex items-center justify-between p-2 rounded-xl hover:bg-slate-800 cursor-pointer transition-colors group">
                    <div class="flex items-center gap-3 truncate">
                        <img src="${imgSrc}" alt="${song.title}" onerror="this.src='/image/queen1.jpg'" class="w-9 h-9 rounded-lg object-cover border border-slate-700 group-hover:border-emerald-500/50 flex-shrink-0">
                        <div class="truncate">
                            <h4 class="text-sm font-semibold text-white group-hover:text-emerald-400 transition-colors truncate">
                                ${escapeHtml(song.title)}
                            </h4>
                            <p class="text-[11px] text-slate-400 truncate">
                                ${escapeHtml(song.artist)} • <span class="text-slate-500">${song.genre} (${song.year})</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                        <span class="text-[11px] font-semibold text-yellow-400">★ ${song.rating}</span>
                        <button onclick="event.stopPropagation(); playSong(${song.id}, '${escapeQuote(song.title)}', '${escapeQuote(song.artist)}', '/${song.image || 'image/queen1.jpg'}', '${song.audio_url || ''}', '${song.video_id || ''}'); hideSearchSuggestions();"
                            class="p-1.5 rounded-full text-slate-400 hover:text-emerald-400 hover:bg-slate-700 transition-colors" title="Putar Langsung">
                            <svg class="w-4 h-4 fill-current translate-x-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
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

    // 3. Footer Dropdown (Solid Opaque)
    html += `
        <div class="p-2.5 bg-[#020617] border-t border-slate-800 flex items-center justify-between text-xs">
            <span class="text-slate-400 text-[11px]">Tekan <kbd class="px-1.5 py-0.5 rounded bg-slate-800 border border-slate-700 text-[10px] text-slate-300 font-mono">Enter</kbd> untuk hasil lengkap</span>
            <button onclick="triggerSearch()" class="text-emerald-400 hover:text-emerald-300 font-semibold flex items-center gap-1">
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

    const btnVideo = document.getElementById('modalOpenVideoBtn');
    if (btnVideo) {
        btnVideo.onclick = () => {
            openVideoModal(song.video_id, song.title, song.artist);
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
            reviewList.innerHTML = '<div class="space-y-2 py-1"><div class="h-14 bg-slate-800/40 rounded-xl animate-pulse"></div></div>';
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
            descText.innerHTML = '<div class="space-y-2 py-1"><div class="h-3.5 bg-slate-800/80 rounded animate-pulse w-full"></div><div class="h-3.5 bg-slate-800/80 rounded animate-pulse w-5/6"></div></div>';
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
    if (!modal || !modal.classList.contains('active')) return;

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
            <div class="bg-slate-900/40 border border-dashed border-slate-700/60 rounded-xl p-4 text-center select-none">
                <p class="text-xs text-slate-400">Belum ada ulasan untuk lagu ini di database.</p>
                <p class="text-[11px] text-emerald-400 mt-1 font-medium">Jadilah pendengar pertama yang memberikan penilaian & komentar di atas!</p>
            </div>
        `;
        return;
    }

    list.innerHTML = reviews.map(r => {
        const rating = Math.max(1, Math.min(5, parseInt(r.rating) || 5));
        const starsHtml = '★'.repeat(rating) + '☆'.repeat(5 - rating);
        const dateStr = r.created_at ? new Date(r.created_at).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        }) : 'Baru saja';

        return `
            <div class="bg-slate-900/70 p-3.5 rounded-xl border border-slate-700/60 hover:border-slate-600 transition-all shadow-sm">
                <div class="flex items-center justify-between mb-1.5">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-bold flex items-center justify-center">
                            🎵
                        </span>
                        <span class="text-xs font-semibold text-white">Pendengar Minima</span>
                        <span class="text-[10px] text-slate-400">• ${dateStr}</span>
                    </div>
                    <div class="text-yellow-400 text-xs font-bold tracking-wider" title="${rating} Bintang">
                        ${starsHtml}
                    </div>
                </div>
                <p class="text-xs text-slate-300 leading-relaxed pl-8 break-words whitespace-pre-wrap">"${escapeHtml(r.review)}"</p>
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
                const dateStr = rev.created_at ? new Date(rev.created_at).toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                }) : 'Baru saja';

                return `
                    <div class="bg-slate-800/70 p-5 rounded-2xl border border-slate-700/60 flex flex-col justify-between shadow hover:border-slate-600 transition-all">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-bold text-white text-base">${escapeHtml(rev.music_title)}</h4>
                                <div class="text-yellow-400 text-sm">
                                    ${stars}
                                </div>
                            </div>
                            <p class="text-xs text-emerald-400 mb-3 font-medium">${escapeHtml(rev.artist)}</p>
                            <p class="text-slate-300 text-sm leading-relaxed italic">"${escapeHtml(rev.review)}"</p>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-4">${dateStr}</p>
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
                <div onclick="viewPlaylistDetail(${p.id})" class="bg-slate-800/80 p-5 rounded-2xl border border-slate-700/60 hover:border-emerald-500/50 transition-all hover:-translate-y-1 cursor-pointer group shadow-xl">
                    <div class="aspect-square bg-slate-700 rounded-xl mb-4 overflow-hidden flex items-center justify-center text-4xl shadow-inner border border-slate-700">
                        ${p.image ? `<img src="/${p.image}" class="w-full h-full object-cover">` : '🎧'}
                    </div>
                    <h3 class="font-bold text-white text-base truncate group-hover:text-emerald-400">${p.name}</h3>
                    <p class="text-xs text-slate-400 mt-1 line-clamp-2">${p.description || 'Tidak ada deskripsi.'}</p>
                    <p class="text-xs text-emerald-400 font-semibold mt-3">${p.musics_count || 0} Lagu</p>
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
                playlists.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
        });
}

function addCurrentSongToPlaylist() {
    if (!currentDetailSong) return;
    const select = document.getElementById('modalPlaylistSelect');
    const playlistId = select.value;

    if (!playlistId) {
        showToast('Pilih playlist terlebih dahulu!', 'error');
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    fetch(`/api/playlists/${playlistId}/add`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ music_id: currentDetailSong.id })
    })
    .then(res => res.json())
    .then(data => {
        showToast(data.message, data.success ? 'success' : 'error');
        loadPlaylists();
    });
}

function openCreatePlaylistModal() {
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
        document.getElementById('newPlaylistName').value = '';
        document.getElementById('newPlaylistDesc').value = '';
    });
}

function viewPlaylistDetail(playlistId) {
    activePlaylistId = playlistId;
    fetch(`/api/playlists/${playlistId}`)
        .then(res => res.json())
        .then(p => {
            currentSongQueue = p.musics || [];
            document.getElementById('playlistViewTitle').textContent = p.name;
            document.getElementById('playlistViewDescription').textContent = p.description || 'Tidak ada deskripsi.';
            document.getElementById('playlistViewCount').textContent = p.musics.length;

            const imgContainer = document.getElementById('playlistViewImage');
            imgContainer.innerHTML = p.image ? `<img src="/${p.image}" class="w-full h-full object-cover">` : '🎵';

            document.getElementById('deletePlaylistBtn').onclick = () => deletePlaylist(p.id);

            const songsContainer = document.getElementById('playlistViewSongs');
            if (p.musics.length === 0) {
                songsContainer.innerHTML = '<p class="text-slate-400 text-sm py-4">Belum ada lagu di playlist ini. Telusuri katalog dan tambahkan lagu ke playlist ini.</p>';
            } else {
                songsContainer.innerHTML = p.musics.map((song, idx) => `
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-900/40 hover:bg-slate-800 transition-colors border border-slate-700/40">
                        <div class="flex items-center space-x-3 truncate">
                            <span class="text-xs font-bold text-slate-500 w-5">${idx + 1}</span>
                            <img src="${song.image ? '/' + song.image : '/image/queen1.jpg'}" class="w-10 h-10 rounded-lg object-cover">
                            <div class="truncate">
                                <h4 class="font-bold text-white text-sm truncate hover:text-emerald-400 cursor-pointer" onclick="openMusicModal(${song.id})">${song.title}</h4>
                                <p class="text-xs text-slate-400 truncate">${song.artist}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button onclick="playSong(${song.id}, '${escapeQuote(song.title)}', '${escapeQuote(song.artist)}', '/${song.image || 'image/queen1.jpg'}', '${song.audio_url || ''}', '${song.video_id || ''}')" class="px-3 py-1 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold rounded-lg text-xs">
                                Putar
                            </button>
                            <button onclick="removeSongFromPlaylist(${p.id}, ${song.id})" class="text-slate-500 hover:text-red-400 p-1" title="Hapus lagu">
                                ✕
                            </button>
                        </div>
                    </div>
                `).join('');
            }

            showSection('playlist-view');
        });
}

function removeSongFromPlaylist(playlistId, songId) {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    fetch(`/api/playlists/${playlistId}/songs/${songId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf }
    })
    .then(res => res.json())
    .then(data => {
        showToast(data.message);
        viewPlaylistDetail(playlistId);
        loadPlaylists();
    });
}

function deletePlaylist(playlistId) {
    if (!confirm('Apakah Anda yakin ingin menghapus playlist ini?')) return;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    fetch(`/api/playlists/${playlistId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf }
    })
    .then(res => res.json())
    .then(data => {
        showToast(data.message);
        showSection('playlists');
        loadPlaylists();
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

/* ==================== 8. REVIEWS CRUD ==================== */
function loadReviews() {
    fetch('/api/reviews')
        .then(res => res.json())
        .then(reviews => {
            const list = document.getElementById('myReviewsList');
            if (!list) return;

            if (!reviews || reviews.length === 0) {
                list.innerHTML = `
                    <div class="bg-slate-800/40 p-8 rounded-2xl border border-dashed border-slate-700/60 text-center select-none">
                        <p class="text-slate-400 text-sm">Belum ada ulasan yang tersimpan di database.</p>
                        <p class="text-xs text-emerald-400 mt-1 font-medium">Buka detail lagu mana saja di katalog lalu kirim ulasan pertama Anda!</p>
                    </div>
                `;
                return;
            }

            list.innerHTML = reviews.map(r => {
                const rating = Math.max(1, Math.min(5, parseInt(r.rating) || 5));
                const dateStr = r.created_at ? new Date(r.created_at).toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                }) : 'Baru saja';

                return `
                    <div class="bg-slate-800/80 p-5 rounded-2xl border border-slate-700/60 shadow-lg flex flex-col justify-between hover:border-slate-600 transition-all">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-bold text-white text-base">${escapeHtml(r.music_title)}</h4>
                                <div class="text-yellow-400 text-sm font-semibold tracking-wider">
                                    ${'★'.repeat(rating)}${'☆'.repeat(5 - rating)}
                                </div>
                            </div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-xs text-emerald-400 font-semibold">${escapeHtml(r.artist)}</span>
                                <span class="text-[11px] text-slate-500">• ${dateStr}</span>
                            </div>
                            <p class="text-slate-300 text-sm leading-relaxed italic break-words">"${escapeHtml(r.review)}"</p>
                        </div>
                        <div class="flex items-center justify-between pt-4 mt-4 border-t border-slate-700/40 text-xs">
                            <span class="text-emerald-400/80 flex items-center gap-1.5 font-medium text-[11px]">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                Tersimpan di Database
                            </span>
                            <div class="flex space-x-2">
                                <button onclick="openEditReviewModal(${r.id}, ${rating}, '${escapeQuote(r.review)}')" class="px-3.5 py-1.5 bg-slate-700 hover:bg-emerald-600 text-white rounded-xl transition-colors font-semibold">Edit</button>
                                <button onclick="deleteReview(${r.id})" class="px-3.5 py-1.5 bg-slate-700 hover:bg-red-600 text-white rounded-xl transition-colors font-semibold">Hapus</button>
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
    if (!confirm('Apakah Anda yakin ingin menghapus ulasan ini dari database?')) return;
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
        showToast(data.message || 'Ulasan berhasil dihapus dari database!');
        loadReviews();
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

/* ==================== 9. TOAST NOTIFICATION ==================== */
function showToast(message, type = 'success') {
    const existing = document.getElementById('minimaToast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.id = 'minimaToast';
    toast.className = `fixed bottom-24 right-6 z-50 px-4 py-3 rounded-xl shadow-2xl flex items-center space-x-2 text-xs font-bold text-white transition-all transform translate-y-4 opacity-0 border border-white/20 backdrop-blur-md ${type === 'error' ? 'bg-red-600' : 'bg-emerald-600'}`;
    toast.innerHTML = `<span>${type === 'error' ? '⚠️' : '✅'}</span><span>${message}</span>`;

    document.body.appendChild(toast);
    setTimeout(() => {
        toast.classList.remove('translate-y-4', 'opacity-0');
    }, 10);

    setTimeout(() => {
        toast.classList.add('translate-y-4', 'opacity-0');
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

function escapeQuote(str) {
    if (!str) return '';
    return str.replace(/'/g, "\\'").replace(/"/g, '&quot;');
}
