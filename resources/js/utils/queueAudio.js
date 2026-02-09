let announcementQueue = []; // Antrian data pemanggilan (bukan file audio)
let isPlaying = false;

const BASE_PATH = '/audio';

// --- AUDIO CACHE / PRELOADER (Untuk mengurangi jeda) ---
const audioCache = new Map(); // src -> HTMLAudio element (base)

const preloadFile = (src) => {
    if (audioCache.has(src)) return audioCache.get(src).promise;

    const audio = new Audio(src);
    audio.preload = 'auto';

    let resolveFn;
    const p = new Promise((resolve) => { resolveFn = resolve; });

    const cleanup = () => {
        audio.removeEventListener('canplaythrough', onLoad);
        audio.removeEventListener('error', onError);
    };

    const onLoad = () => { cleanup(); resolveFn(true); };
    const onError = () => { cleanup(); resolveFn(false); };

    audio.addEventListener('canplaythrough', onLoad, { once: true });
    audio.addEventListener('error', onError, { once: true });

    // Start loading
    audio.load();

    audioCache.set(src, { element: audio, promise: p });
    return p;
};

// Preload a set of commonly-used files (numbers, frasa, tone, huruf)
export const preloadCommonAudio = async () => {
    const files = new Set();
    // tone + frasa
    files.add(`${BASE_PATH}/tone/ding.mp3`);
    files.add(`${BASE_PATH}/frasa/nomor_antrian.mp3`);
    files.add(`${BASE_PATH}/frasa/silakan_menuju.mp3`);
    files.add(`${BASE_PATH}/frasa/loket.mp3`);

    // angka 0-9
    for (let i = 0; i <= 9; i++) files.add(`${BASE_PATH}/angka/${i}.mp3`);

    // huruf a-z (beberapa project hanya pakai A-C, tapi kita preload lebih banyak)
    'abcdefghijklmnopqrstuvwxyz'.split('').forEach(ch => files.add(`${BASE_PATH}/huruf/${ch}.mp3`));

    const promises = Array.from(files).map(src => preloadFile(src).catch(() => false));
    // Wait but don't block too long: return when all done or 2s timeout
    const timeout = new Promise(resolve => setTimeout(resolve, 2000, 'timeout'));
    await Promise.race([Promise.all(promises), timeout]);
};

// Helper: Bangun playlist audio berdasarkan data antrian
const buildPlaylist = ({ prefix, number, counter }) => {
    const playlist = [];

    // 0. Nada panggil
    playlist.push(`${BASE_PATH}/tone/ding.mp3`);

    // 1. "Nomor antrian"
    playlist.push(`${BASE_PATH}/frasa/nomor_antrian.mp3`);

    // 2. Huruf (A, B, C)
    if (prefix) {
        playlist.push(`${BASE_PATH}/huruf/${prefix.toLowerCase()}.mp3`);
    }

    // 3. Angka (001 → 0 0 1)
    if (number) {
        number.toString().split('').forEach(digit => {
            playlist.push(`${BASE_PATH}/angka/${digit}.mp3`);
        });
    }

    // 4. "Silakan menuju"
    playlist.push(`${BASE_PATH}/frasa/silakan_menuju.mp3`);

    // 5. Loket
    playlist.push(`${BASE_PATH}/frasa/loket.mp3`);
    if (counter) {
        playlist.push(`${BASE_PATH}/angka/${counter}.mp3`);
    }

    return playlist;
};

// Helper: Mainkan satu set playlist sampai habis
const playPlaylist = (playlist, onComplete) => {
    if (playlist.length === 0) {
        onComplete();
        return;
    }

    const src = playlist.shift();

    // Gunakan elemen audio yang telah dipreload jika ada (clone agar bisa overlap jika perlu)
    const cached = audioCache.get(src);
    let audio;
    if (cached && cached.element) {
        try {
            audio = cached.element.cloneNode(true);
        } catch (e) {
            audio = new Audio(src);
            audio.preload = 'auto';
        }
    } else {
        audio = new Audio(src);
        audio.preload = 'auto';
        // Start loading early to reduce latency for un-preloaded sources
        try { audio.load(); } catch (e) {}
    }

    // Kecepatan 1.25 agar suara lebih sigap
    audio.playbackRate = 1.25; 

    let hasTriggeredNext = false;

    const playNext = () => {
        if (!hasTriggeredNext) {
            hasTriggeredNext = true;
            playPlaylist(playlist, onComplete);
        }
    };

    // --- TEKNIK TANPA JEDA (GAPLESS) ---
    // Sisa durasi 0.2 detik langsung panggil audio berikutnya untuk memotong jeda MP3
    audio.ontimeupdate = () => {
        if (audio.duration > 0 && (audio.duration - audio.currentTime < 0.2)) {
            playNext();
        }
    };

    // Fallback tetap ada agar antrian tidak macet
    audio.onended = playNext;
    audio.onerror = playNext;

    // Play immediately — preloaded audio should start with minimal gap
    audio.play().catch(err => {
        console.error("Gagal memutar audio:", src, err);
        playNext();
    });
};

// Processor utama: Cek antrian pengumuman
const processAnnouncementQueue = () => {
    if (isPlaying || announcementQueue.length === 0) return;

    isPlaying = true;
    
    // --- PERUBAHAN DI SINI: Ambil data BESERTA callback-nya ---
    const { data, onFinish } = announcementQueue.shift(); 

    const playlist = buildPlaylist(data);

    // Preload playlist files (non-blocking; prefer first file) to reduce gaps
    // Prioritize first two segments
    preloadFile(playlist[0]).catch(() => {});
    if (playlist[1]) preloadFile(playlist[1]).catch(() => {});
    // Start playback — other files may continue loading in background
    playPlaylist(playlist, () => {
        // --- PERUBAHAN DI SINI: Panggil callback saat audio selesai ---
        if (onFinish) onFinish();

        isPlaying = false;
        processAnnouncementQueue(); // Cek apakah ada antrian berikutnya
    });
};

// ================= PUBLIC API =================
// --- PERUBAHAN DI SINI: Terima parameter onFinishCallback ---
export const callQueue = (data, onFinishCallback = null) => {
    // Masukkan object { data, onFinish } ke antrian
    announcementQueue.push({ 
        data: data, 
        onFinish: onFinishCallback 
    });
    
    // Trigger processor
    processAnnouncementQueue();
};