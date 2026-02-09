<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import { callQueue } from '@/utils/queueAudio';

// --- 1. CONFIG YOUTUBE PLAYER ---
const player = ref(null);
const videoId = ref("vHZhFmkINI8"); // Video Default ASABRI
const indonesiaRayaId = "HKrhbLJa1JA"; // ID Video Indonesia Raya
const isIndonesiaRayaPlaying = ref(false);
const hasPlayedToday = ref(false); // Variable baru untuk pengunci

// --- STATE UTAMA ---
const activeQueues = ref([]);
const processedState = ref(new Map()); 
const isAudioEnabled = ref(false);

// --- STATE DINAMIS LAYAR UTAMA ---
const currentDisplayQueue = ref(null); 

// --- STATE ANTRIAN LOKAL ---
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
        videoId: videoId.value,
        playerVars: {
            'autoplay': 1, 'controls': 0, 'rel': 0, 
            'loop': 1, 'playlist': videoId.value, 'playsinline': 1,
            'origin': window.location.origin
        },
        events: {
            'onReady': (event) => {
                event.target.setVolume(100);
                event.target.mute();
                if(isAudioEnabled.value) {
                    event.target.unMute();
                    event.target.playVideo();
                }
            }
        }
    });
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

// --- LOGIKA INDONESIA RAYA (PERBAIKAN LOOP OTOMATIS) ---
const checkIndonesiaRayaTime = () => {
    const now = new Date();
    const hours = now.getHours();
    const minutes = now.getMinutes();

    // Sesuaikan dengan jam target Anda (contoh 23:34)
    const targetHour = 7;    
    const targetMinute = 27;  

    if (hours === targetHour && minutes === targetMinute) {
        if (!isIndonesiaRayaPlaying.value && !hasPlayedToday.value) {
            if (player.value && typeof player.value.loadVideoById === 'function') {
                console.log("MEMUTAR INDONESIA RAYA...");
                
                isIndonesiaRayaPlaying.value = true;
                hasPlayedToday.value = true; 
                
                // Putar Indonesia Raya
                player.value.loadVideoById(indonesiaRayaId);
                player.value.unMute();
                player.value.setVolume(100);
                player.value.playVideo();

                // --- Di dalam fungsi checkIndonesiaRayaTime ---

               // --- Di dalam fungsi checkIndonesiaRayaTime ---

                                // --- Di dalam fungsi checkIndonesiaRayaTime ---

                setTimeout(() => {
                    console.log("KEMBALI KE ASABRI DAN LOCK PLAYLIST");
                    
                    if (player.value) {
                        // Kita paksa muat ulang sebagai PLAYLIST tunggal agar YouTube tidak punya pilihan video lain
                        player.value.loadPlaylist({
                            playlist: [videoId.value], // Hanya isi ID video ASABRI
                            listType: 'playlist',
                            index: 0,
                            startSeconds: 0
                        });

                        // Setel ulang agar terus mengulang playlist yang isinya cuma 1 video ini
                        player.value.setLoop(true);
                        
                        // Pastikan suara kembali normal jika tadi sempat dikecilkan
                        player.value.unMute();
                        player.value.setVolume(100);
                    }

                    isIndonesiaRayaPlaying.value = false;
                }, 133000); // 132 detik (2:12)
            }
        }
    }

    if (minutes !== targetMinute) {
        hasPlayedToday.value = false;
    }
};

// --- 3. PROCESSOR ANTRIAN ---
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

// --- 4. FETCH DATA ---
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
            <div class="w-[55%] flex flex-col p-6 gap-6 justify-center">
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

            <div class="w-[45%] p-6 pl-0 flex items-center">
                <div class="w-full h-full bg-black rounded-[30px] overflow-hidden shadow-2xl border-4 border-[#ffc107] relative">
                    <div id="youtube-player" class="w-full h-full scale-[1.3] pointer-events-none"></div>
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
                    PT Asabri (Persero) l Melayani dengan Sepenuh Hati • PT Asabri (Persero) l Melayani dengan Sepenuh Hati • PT Asabri (Persero) l Melayani dengan Sepenuh Hati • PT Asabri (Persero) l Melayani dengan Sepenuh Hati • PT Asabri (Persero) l Melayani dengan Sepenuh Hati • PT Asabri (Persero) l Melayani dengan Sepenuh Hati • PT Asabri (Persero) l Melayani dengan Sepenuh Hati • PT Asabri (Persero) l Melayani dengan Sepenuh Hati
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
                <button @click="enableAudio" 
                    class="group relative inline-flex items-center justify-center px-16 py-6 text-2xl font-black text-[#00569c] transition-all duration-300 bg-yellow-400 font-sans rounded-full hover:scale-110 shadow-[0_0_50px_rgba(255,255,255,0.2)]">
                    <svg class="w-10 h-10 mr-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
                    </svg>
                    <span>MULAI DISPLAY TV</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.font-mono { font-family: 'Courier New', Courier, monospace; }
</style>