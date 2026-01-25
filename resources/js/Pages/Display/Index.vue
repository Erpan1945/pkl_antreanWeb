<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import { callQueue } from '@/utils/queueAudio';

// --- 1. CONFIG YOUTUBE PLAYER ---
const player = ref(null);
const videoId = "vHZhFmkINI8"; // Video Portrait ASABRI

// --- STATE UTAMA ---
const activeQueues = ref([]);
const processedState = ref(new Map()); 
const isAudioEnabled = ref(false);

// --- STATE ANTRIAN LOKAL ---
const pendingAnnouncements = ref([]); 
const isSpeaking = ref(false); 

let isFirstLoad = true;
let interval = null;

// --- 2. LOAD YOUTUBE API (VERSI ANTI-ERROR) ---
const initPlayer = () => {
    // Hapus player lama jika ada biar ga double
    if (player.value) {
        try { player.value.destroy(); } catch(e) {}
    }

    player.value = new YT.Player('youtube-player', {
        videoId: videoId,
        playerVars: {
            'autoplay': 1, 'controls': 0, 'rel': 0, 
            'loop': 1, 'playlist': videoId, 'playsinline': 1,
            'origin': window.location.origin // Penting agar tidak diblokir
        },
        events: {
            'onReady': (event) => {
                console.log("✅ Video Siap!");
                event.target.setVolume(100);
                event.target.mute(); // Mute dulu biar autoplay jalan di Chrome
                if(isAudioEnabled.value) {
                    event.target.unMute();
                    event.target.playVideo();
                } else {
                    event.target.playVideo(); // Play silent background
                }
            },
            'onError': (e) => {
                console.error("❌ YouTube Error:", e);
            }
        }
    });
};

const loadYoutubeAPI = () => {
    // Cek 1: Apakah API sudah siap pakai? (Misal dari halaman sebelumnya)
    if (window.YT && window.YT.Player) {
        initPlayer();
    } else {
        // Cek 2: Belum siap, pasang antrian callback
        window.onYouTubeIframeAPIReady = initPlayer;

        // Cek 3: Apakah script tag-nya sudah ada di HTML?
        const existingScript = document.querySelector('script[src="https://www.youtube.com/iframe_api"]');
        if (!existingScript) {
            const tag = document.createElement('script');
            tag.src = "https://www.youtube.com/iframe_api";
            const firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        }
    }
};

// --- 3. PROCESSOR ANTRIAN ---
const playNextAnnouncement = () => {
    if (pendingAnnouncements.value.length === 0) {
        console.log("✅ Semua panggilan selesai. Volume normal.");
        isSpeaking.value = false;
        if (player.value && typeof player.value.setVolume === 'function') {
            player.value.setVolume(100);
        }
        return;
    }

    isSpeaking.value = true;
    
    // Kecilkan Volume Video
    if (player.value && typeof player.value.setVolume === 'function') {
        player.value.setVolume(10); 
    }

    const queueItem = pendingAnnouncements.value.shift();

    callQueue(queueItem, () => {
        playNextAnnouncement();
    });
};

// --- 4. FETCH DATA (POLLING) ---
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

                pendingAnnouncements.value.push({
                    prefix: prefix,
                    number: numberOnly,
                    counter: queue.counter ? queue.counter.name.replace(/\D/g, '') : '1'
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

// --- 5. TOMBOL MULAI ---
const enableAudio = () => {
    isAudioEnabled.value = true;
    
    // Pancing Audio Browser
    const unlock = new Audio();
    unlock.play().catch(() => {});

    // Unmute YouTube
    if (player.value && typeof player.value.unMute === 'function') {
        player.value.unMute();
        player.value.setVolume(100);
        player.value.playVideo();
    }
    
    document.documentElement.requestFullscreen().catch(() => {});
};

// --- 6. HELPER UI ---
const currentQueue = computed(() => activeQueues.value.find(q => q.status === 'called') || null);
const nextQueues = computed(() => activeQueues.value.filter(q => q.status === 'waiting').slice(0, 5));

const currentTime = ref('');
const currentDate = ref('');
const updateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }).replace(/\./g, ':');
    currentDate.value = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

onMounted(() => {
    loadYoutubeAPI(); // Panggil fungsi perbaikan
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

    <div class="h-screen w-screen bg-gray-900 text-white overflow-hidden flex flex-col font-sans select-none">
        
        <header class="h-24 bg-gradient-to-r from-blue-900 to-indigo-900 flex items-center justify-between px-8 border-b-4 border-yellow-400 shadow-xl z-20">
            <div class="flex items-center gap-4">
                <div class="bg-white p-2 rounded-lg shadow-lg">
                    <img src="/images/logo-asabri.png" alt="Logo" class="h-14 w-auto object-contain" onError="this.style.display='none'">
                </div>
                <div>
                    <h1 class="text-3xl font-black tracking-wider uppercase drop-shadow-md">PT ASABRI KC Malang (Persero) </h1>
                    <p class="text-blue-200 text-sm font-semibold tracking-widest uppercase">Ruko Raden Intan Square Jl. Raden Intan No.Kav. 74/I, Arjosari,</p>
                     <p class="text-blue-200 text-sm font-semibold tracking-widest uppercase">Kec. Blimbing, Kota Malang, Jawa Timur 65126</p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-5xl font-mono font-black text-yellow-400 tracking-widest drop-shadow-md">
                    {{ currentTime }}
                </div>
                <div class="text-blue-200 text-sm font-medium uppercase tracking-wider mt-1">
                    {{ currentDate }}
                </div>
            </div>
        </header>

        <main class="flex-1 flex overflow-hidden relative">
            <div class="w-[60%] flex flex-col bg-slate-100 border-r-4 border-yellow-500 relative">
                <div class="absolute inset-0 opacity-5 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>

                <div class="flex-1 flex flex-col justify-center items-center text-center p-6 z-10">
                    <h2 class="text-2xl font-bold text-gray-500 uppercase tracking-[0.3em] mb-4">Nomor Antrian</h2>
                    <div v-if="currentQueue" class="scale-100 transition-transform duration-500">
                        <div class="text-[10rem] leading-none font-black text-blue-900 drop-shadow-2xl font-mono">
                            {{ currentQueue.ticket_code }}
                        </div>
                        <div class="mt-6 inline-block bg-blue-900 text-white px-10 py-4 rounded-full text-4xl font-bold uppercase shadow-xl border-4 border-yellow-400">
                            {{ currentQueue.counter?.name || 'LOKET PELAYANAN' }}
                        </div>
                    </div>
                    <div v-else class="animate-pulse opacity-50">
                        <span class="text-[8rem] font-black text-gray-300">---</span>
                        <p class="text-xl text-gray-400 font-semibold mt-2">Menunggu panggilan...</p>
                    </div>
                </div>

                <div class="h-[35%] bg-blue-900 p-5 flex flex-col border-t-4 border-blue-800 shadow-inner">
                    <div class="flex items-center mb-3">
                        <div class="w-2 h-8 bg-yellow-400 mr-3 rounded-full"></div>
                        <h3 class="text-white text-xl font-bold uppercase tracking-wider">Antrian Selanjutnya</h3>
                    </div>
                    <div class="flex-1 space-y-3 overflow-y-auto pr-2 custom-scroll">
                        <div v-for="queue in nextQueues" :key="queue.id" 
                             class="bg-white/10 backdrop-blur-sm p-3 rounded-lg flex justify-between items-center border-l-4 border-yellow-400">
                            <span class="text-3xl font-black text-white font-mono">{{ queue.ticket_code }}</span>
                            <span class="text-sm font-bold text-blue-200 uppercase bg-blue-950 px-3 py-1 rounded">{{ queue.service?.name }}</span>
                        </div>
                        <div v-if="nextQueues.length === 0" class="text-center text-blue-300/50 italic mt-6">Belum ada antrian menunggu.</div>
                    </div>
                </div>
            </div>

            <div class="w-[40%] bg-black relative shadow-2xl overflow-hidden flex items-center justify-center">
                <div class="absolute inset-0 w-full h-full">
                    <div id="youtube-player" class="w-full h-full scale-[1.35] pointer-events-none"></div>
                    <div class="absolute inset-0 bg-transparent z-10"></div>
                </div>
            </div>
        </main>

        <footer class="h-16 bg-yellow-400 flex items-center shadow-[0_-4px_10px_rgba(0,0,0,0.3)] z-30 relative">
            <div class="bg-blue-900 text-white px-8 h-full flex items-center font-black text-xl italic tracking-widest z-20 shadow-lg skew-x-[-12deg] ml-[-10px]">
                <span class="skew-x-[12deg]">INFO</span>
            </div>
            <div class="flex-1 overflow-hidden flex items-center">
                <marquee class="text-2xl font-bold text-blue-900 uppercase" scrollamount="10">
                    Selamat Datang di PT ASABRI (Persero). Melayani dengan Hati, Menjaga Negeri. Pastikan kelengkapan dokumen Anda sebelum menuju loket.PT Asabri (Persero) l Melayani dengan Sepenuh Hati - PT Asabri (Persero) l Melayani dengan Sepenuh Hati - PT Asabri (Persero) l Melayani dengan Sepenuh Hati - PT Asabri (Persero).
                </marquee>
            </div>
        </footer>

        <div v-if="!isAudioEnabled" class="fixed inset-0 bg-black/95 backdrop-blur-xl flex items-center justify-center z-50">
            <div class="text-center">
                <button @click="enableAudio" 
                    class="group relative inline-flex items-center justify-center px-10 py-5 text-2xl font-bold text-white transition-all duration-200 bg-blue-600 font-sans rounded-full hover:bg-blue-500 animate-bounce shadow-2xl">
                    <span>▶ MULAI DISPLAY TV</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.font-mono { font-family: 'Courier New', Courier, monospace; }
.custom-scroll::-webkit-scrollbar { width: 6px; }
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(250, 204, 21, 0.5); border-radius: 10px; }
</style>