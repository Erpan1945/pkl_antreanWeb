let announcementQueue = []; // Antrian data pemanggilan (bukan file audio)
let isPlaying = false;

const BASE_PATH = '/audio';

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
    const audio = new Audio(src);

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