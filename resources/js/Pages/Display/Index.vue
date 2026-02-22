<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import { callQueue } from '@/utils/queueAudio';

// --- 1. CONFIG YOUTUBE PLAYER ---
const player = ref(null);
const playlistVideos = [
    "vHZhFmkINI8", 
    "H7fAevGRHXQ", 
    "7EJQy7gSk1c", 
    "1q9ijwlvMTQ" 
];
const videoId = ref(playlistVideos[0]);
const indonesiaRayaId = "HKrhbLJa1JA"; 
const isIndonesiaRayaPlaying = ref(false);
const hasPlayedToday = ref(false); 

// --- STATE UTAMA ---
const activeQueues = ref([]);
const processedState = ref(new Map()); 
const isAudioEnabled = ref(false);
const currentDisplayQueue = ref(null); 
const pendingAnnouncements = ref([]); 
const isSpeaking = ref(false); 

let isFirstLoad = true;
let interval = null;

// --- 2. LOAD YOUTUBE API ---
const initPlayer = () => {
    if (player.value) {
        try { player.value.destroy(); } catch(e) {}
    }

    player.value = new YT.Player('youtube-player', {
        width: '100%',  // Ubah ini
        height: '100%', // Ubah ini
        videoId: videoId.value,
        playerVars: {
            'autoplay': 1,
            'controls': 0,
            // ... sisa kode lainnya tetap sama
            'rel': 0, 
            'loop': 1, 
            'modestbranding': 1, 
            'iv_load_policy': 3, 
            'playlist': playlistVideos.join(','), 
            'playsinline': 1,
            'origin': window.location.origin
        },
        events: {
            'onReady': (event) => {
                event.target.setVolume(100);
                if(isAudioEnabled.value) {
                    event.target.unMute();
                    event.target.playVideo();
                }
            },
            // KUNCI: Deteksi otomatis saat video selesai
            'onStateChange': (event) => {
                // Jika video SELESAI (0) dan saat itu sedang putar Indonesia Raya
                if (event.data === YT.PlayerState.ENDED && isIndonesiaRayaPlaying.value) {
                    stopIndonesiaRayaAndPlayQueue();
                }
            }
        }
    });
};

// Fungsi transisi instan tanpa jeda
const stopIndonesiaRayaAndPlayQueue = () => {
    isIndonesiaRayaPlaying.value = false;
    if (player.value) {
        // Langsung balikkan ke playlist utama
        player.value.loadPlaylist({
            playlist: playlistVideos,
            listType: 'playlist',
            index: 0,
            startSeconds: 0
        });
        player.value.unMute();
        player.value.setVolume(100);
    }
};

const loadYoutubeAPI = () => {
    if (window.YT && window.YT.Player) {
        initPlayer();
    } else {
        window.onYouTubeIframeAPIReady = initPlayer;
        const existingScript = document.querySelector('script[src="https://www.youtube.com/iframe_api"]');
        if (!existingScript) {
            const tag = document.createElement('script');
            tag.src = "https://www.youtube.com/iframe_api";
            const firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        }
    }
};

const checkIndonesiaRayaTime = () => {
    const now = new Date();
    const hours = now.getHours();
    const minutes = now.getMinutes();
    
    // Jam tayang (Sesuaikan di sini)
    const targetHour = 4;    
    const targetMinute = 30;  

    if (hours === targetHour && minutes === targetMinute) {
        if (!isIndonesiaRayaPlaying.value && !hasPlayedToday.value) {
            if (player.value && typeof player.value.loadVideoById === 'function') {
                isIndonesiaRayaPlaying.value = true;
                hasPlayedToday.value = true; 
                
                player.value.loadVideoById(indonesiaRayaId);
                player.value.unMute();
                player.value.setVolume(100);
                player.value.playVideo();
            }
        }
    }
    if (minutes !== targetMinute) {
        hasPlayedToday.value = false;
    }
};

const playNextAnnouncement = () => {
    if (pendingAnnouncements.value.length === 0) {
        isSpeaking.value = false;
        if (player.value && typeof player.value.setVolume === 'function') {
            player.value.setVolume(100);
        }
        return;
    }
    isSpeaking.value = true;
    if (player.value && typeof player.value.setVolume === 'function') {
        player.value.setVolume(10); 
    }
    const queueData = pendingAnnouncements.value.shift();
    currentDisplayQueue.value = queueData.originalData;
    callQueue(queueData.announcement, () => {
        playNextAnnouncement();
    });
};

const fetchData = async () => {
    try {
        const { data } = await axios.get('/display/data');
        activeQueues.value = data;
        if (!data.length || !isAudioEnabled.value) return;
        if (isFirstLoad) {
            data.forEach(q => processedState.value.set(q.id, q.updated_at));
            isFirstLoad = false;
            return;
        }
        const newCandidates = [];
        data.forEach(queue => {
            const lastTime = processedState.value.get(queue.id);
            if ((!lastTime || queue.updated_at > lastTime) && queue.status === 'called') {
                newCandidates.push(queue);
                processedState.value.set(queue.id, queue.updated_at);
            }
        });
        if (newCandidates.length > 0) {
            newCandidates.sort((a, b) => new Date(a.updated_at) - new Date(b.updated_at));
            newCandidates.forEach(queue => {
                const rawCode = queue.ticket_code || queue.number || '000'; 
                const prefix = isNaN(rawCode.charAt(0)) ? rawCode.charAt(0) : ''; 
                const numberOnly = rawCode.replace(/\D/g, ''); 
                const cleanedNumber = parseInt(numberOnly, 10).toString();
                pendingAnnouncements.value.push({
                    announcement: {
                        prefix: prefix,
                        number: cleanedNumber,
                        counter: queue.counter ? queue.counter.name.replace(/\D/g, '') : '1'
                    },
                    originalData: queue 
                });
            });
            if (!isSpeaking.value) {
                playNextAnnouncement();
            }
        }
    } catch (e) {
        console.error("Gagal koneksi server", e);
    }
};

const enableAudio = () => {
    isAudioEnabled.value = true;
    const unlock = new Audio();
    unlock.play().catch(() => {});
    if (player.value && typeof player.value.unMute === 'function') {
        player.value.unMute();
        player.value.setVolume(100);
        player.value.playVideo();
    }
    document.documentElement.requestFullscreen().catch(() => {});
};

const nextQueues = computed(() => activeQueues.value.filter(q => q.status === 'waiting').slice(0, 3));
const currentTime = ref('');
const currentDate = ref('');
const updateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    currentDate.value = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    checkIndonesiaRayaTime();
};

onMounted(() => {
    loadYoutubeAPI();
    fetchData();
    interval = setInterval(fetchData, 3000);
    setInterval(updateTime, 1000);
    updateTime();
});

onUnmounted(() => {
    clearInterval(interval);
});
</script>

<template>
    <Head title="Display Antrian ASABRI" />

    <div class="h-screen w-screen bg-white text-white overflow-hidden flex flex-col font-sans select-none">
        
        <header class="h-[100px] bg-[#00569c] flex items-center justify-between px-8 shadow-xl z-20 border-b-4 border-yellow-400">
            <div class="flex items-center gap-4">
                <div class="bg-white p-2 rounded-lg shadow-lg">
                    <img src="/images/logo-asabri.png" alt="Logo" class="h-12 w-auto object-contain" onError="this.style.display='none'">
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-wide text-white">PT ASABRI KC MALANG (Persero)</h1>
                    <p class="text-white text-xs font-normal tracking-wide mt-1 opacity-90">Ruko Raden Intan Square Jl. Raden Intan No.Kav. 74/I, Arjosari,</p>
                    <p class="text-white text-xs font-normal tracking-wide opacity-90">Kec. Blimbing, Kota Malang, Jawa Timur 65126</p>
                </div>
            </div>
            <div class="text-right flex items-center gap-4">
                <div class="bg-white/10 p-3 rounded-full border border-white/20">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke-width="2"/>
                        <path d="M12 6v6l4 2" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="text-5xl font-bold text-white tracking-wider font-mono">
                    {{ currentTime }}
                </div>
            </div>
        </header>

        <main class="flex-1 flex overflow-hidden relative bg-[#f8f9fa]">
            <div v-if="!isIndonesiaRayaPlaying" class="w-[55%] flex flex-col p-6 gap-6 justify-center">
                <div class="bg-[#ffc107] rounded-[30px] border-[12px] border-[#00569c] shadow-2xl p-8 relative flex items-center justify-between h-[400px]">
                    <div class="flex flex-col items-start pl-4">
                        <h2 class="text-2xl font-black text-[#00569c] uppercase tracking-widest mb-2">NOMOR ANTRIAN</h2>
                        <div class="text-[12rem] leading-none font-black text-[#00569c] font-mono tracking-tighter drop-shadow-md">
                            {{ currentDisplayQueue ? currentDisplayQueue.ticket_code : 'A-000' }}
                        </div>
                    </div>

                    <div class="flex flex-col items-center pr-4">
                        <span class="text-[#00569c] text-2xl font-black uppercase mb-3">MENUJU</span>
                        <div class="bg-[#00569c] text-white p-8 rounded-[25px] flex flex-col items-center min-w-[200px] shadow-xl border-4 border-white/20">
                            <span class="text-3xl font-bold tracking-widest uppercase mb-1">LOKET</span>
                            <span class="text-8xl font-black leading-none">
                                {{ currentDisplayQueue ? currentDisplayQueue.counter?.name.replace(/\D/g, '') : '-' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[25px] shadow-xl border-2 border-gray-100 flex flex-col overflow-hidden h-[240px]">
                    <div class="bg-[#00569c] text-white py-3 px-6 text-center font-black uppercase tracking-[0.2em] text-sm">
                        ANTRIAN BERIKUTNYA
                    </div>
                    <div class="flex-1 p-6 flex gap-6 items-center justify-center">
                        <div v-for="queue in nextQueues" :key="queue.id" 
                             class="flex-1 bg-white border-2 border-gray-200 rounded-2xl p-4 flex flex-col items-center justify-center shadow-md transform hover:scale-105 transition-transform">
                            <div class="text-6xl font-black text-[#00569c] font-mono mb-1">
                                {{ queue.ticket_code }}
                            </div>
                            <div class="text-sm font-bold text-gray-500 uppercase">
                                {{ queue.counter?.name || '-' }}
                            </div>
                        </div>
                        <div v-if="nextQueues.length === 0" class="text-gray-400 italic font-bold">
                            Belum ada antrian menunggu
                        </div>
                    </div>
                </div>
            </div>

            <div :class="[isIndonesiaRayaPlaying ? 'fixed inset-0 z-[100] p-0 bg-black' : 'w-[45%] p-6 pl-0']" class="flex items-center justify-center transition-all duration-700 ease-in-out">
                <div :class="[isIndonesiaRayaPlaying ? 'rounded-0 border-0' : 'rounded-[30px] border-4 border-[#ffc107] shadow-2xl']" class="w-full h-full bg-black overflow-hidden relative flex items-center justify-center">
                    <div id="youtube-player" class="absolute pointer-events-none"></div>
                    <div class="absolute inset-0 bg-transparent z-10"></div>
                </div>
            </div>
        </main>

        <footer class="h-14 bg-[#00569c] flex items-center shadow-2xl z-30 relative overflow-hidden border-t-4 border-yellow-400">
            <div class="h-full bg-yellow-400 px-8 flex items-center font-black italic text-[#00569c] text-xl z-40 relative shadow-lg" style="clip-path: polygon(0 0, 90% 0, 100% 100%, 0% 100%);">
                PT ASABRI
            </div>
            <div class="flex-1 flex items-center overflow-hidden">
                <marquee class="text-xl font-bold text-white uppercase" scrollamount="8">
                    PT Asabri (Persero) l Melayani dengan Sepenuh Hati • PT Asabri (Persero) l Melayani dengan Sepenuh Hati • PT Asabri (Persero) l Melayani dengan Sepenuh Hati
                </marquee>
            </div>
        </footer>

        <div v-if="!isAudioEnabled" class="fixed inset-0 bg-[#00569c]/95 backdrop-blur-xl flex items-center justify-center z-50">
            <div class="text-center">
                <div class="mb-10 transform scale-125">
                    <div class="bg-white p-6 rounded-[30px] inline-block mb-4 shadow-2xl border-4 border-yellow-400">
                        <img src="/images/logo-asabri.png" alt="Logo" class="h-24 w-auto object-contain" onError="this.style.display='none'">
                    </div>
                    <h2 class="text-4xl font-black text-white mb-2 uppercase tracking-widest">SISTEM DISPLAY TV</h2>
                    <p class="text-yellow-400 text-xl font-bold">PT ASABRI KC MALANG</p>
                </div>
                <button @click="enableAudio" class="group relative inline-flex items-center justify-center px-16 py-6 text-2xl font-black text-[#00569c] transition-all duration-300 bg-yellow-400 font-sans rounded-full hover:scale-110 shadow-lg">
                    <span>MULAI DISPLAY TV</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.font-mono { font-family: 'Courier New', Courier, monospace; }

/* Menjamin container hitam tetap pada ukurannya */
.w-full.h-full.bg-black.overflow-hidden.relative {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Memaksa Iframe YouTube untuk memenuhi layar secara proporsional */
#youtube-player {
    position: absolute !important;
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) scale(1.3); /* Scale 1.3 untuk zoom & potong UI YouTube */
    width: 100% !important;
    height: 100% !important;
    min-width: 100% !important;
    min-height: 100% !important;
    aspect-ratio: 16 / 9; /* Menjaga rasio tetap 16:9 */
}

/* Memastikan elemen iframe yang digenerate otomatis juga kena */
:deep(iframe) {
    width: 100% !important;
    height: 100% !important;
    border: none;
}
</style>