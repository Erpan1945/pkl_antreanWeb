<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';
import { callQueue, preloadCommonAudio } from '@/utils/queueAudio';
import DisplayLayout from '@/Layouts/DisplayLayout.vue'; // Import Layout

// --- 1. CONFIG YOUTUBE PLAYER ---
const player = ref(null);
const videoId = ref("vHZhFmkINI8"); 
const indonesiaRayaId = "h_7SSTIn88E"; 
const isIndonesiaRayaPlaying = ref(false);

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
let dataInterval = null;
let timeInterval = null;

// --- YOUTUBE API LOGIC ---
const initPlayer = () => {
    if (player.value && typeof player.value.destroy === 'function') { 
        try { player.value.destroy(); } catch(e) { console.error(e); } 
    }
    
    player.value = new YT.Player('youtube-player', {
        videoId: videoId.value,
        playerVars: { 
            'autoplay': 1, 
            'controls': 0, 
            'rel': 0, 
            'loop': 1, 
            'playlist': videoId.value, 
            'playsinline': 1, 
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
            },
            'onStateChange': (event) => {
                // Jika video utama selesai, putar lagi (loop manual jika playlist gagal)
                if (event.data === YT.PlayerState.ENDED && !isIndonesiaRayaPlaying.value) {
                    player.value.playVideo();
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
        if (!document.querySelector('script[src="https://www.youtube.com/iframe_api"]')) {
            const tag = document.createElement('script');
            tag.src = "https://www.youtube.com/iframe_api";
            const firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        }
    }
};

// --- LOGIKA INDONESIA RAYA ---
const checkIndonesiaRayaTime = () => {
    const now = new Date();
    const day = now.getDay(); // 1-5 (Senin-Jumat)
    const hours = now.getHours();
    const minutes = now.getMinutes();
    const seconds = now.getSeconds();

    // Cek jam 10:00:00 tepat
    if (day >= 1 && day <= 5 && hours === 10 && minutes === 0 && seconds === 0) {
        if (!isIndonesiaRayaPlaying.value && player.value && typeof player.value.loadVideoById === 'function') {
            isIndonesiaRayaPlaying.value = true;
            player.value.loadVideoById(indonesiaRayaId);
            player.value.unMute();
            player.value.setVolume(100);
            player.value.playVideo();
            
            // Durasi Indonesia Raya ~2 menit 15 detik
            setTimeout(() => {
                player.value.loadVideoById(videoId.value);
                isIndonesiaRayaPlaying.value = false;
            }, 135000);
        }
    }
};

// --- PROCESSOR ANTRIAN ---
const playNextAnnouncement = () => {
    if (pendingAnnouncements.value.length === 0) {
        isSpeaking.value = false;
        if (player.value && typeof player.value.setVolume === 'function') player.value.setVolume(100);
        return;
    }

    isSpeaking.value = true;
    if (player.value && typeof player.value.setVolume === 'function') player.value.setVolume(10); 

    const queueData = pendingAnnouncements.value.shift();
    currentDisplayQueue.value = queueData.originalData;

    callQueue(queueData.announcement, () => {
        playNextAnnouncement();
    });
};

// --- FETCH DATA ---
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
                        prefix, 
                        number: cleanedNumber, 
                        counter: queue.counter ? queue.counter.name.replace(/\D/g, '') : '1' 
                    },
                    originalData: queue 
                });
            });

            if (!isSpeaking.value) playNextAnnouncement();
        }
    } catch (e) { 
        console.error("Gagal koneksi server", e); 
    }
};

// --- UTILS ---
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

onMounted(() => {
    // Preload audio assets to reduce gaps saat pemanggilan
    preloadCommonAudio().catch(() => {});

    loadYoutubeAPI();
    fetchData();
    dataInterval = setInterval(fetchData, 3000);
    timeInterval = setInterval(checkIndonesiaRayaTime, 1000); 
});

onUnmounted(() => {
    clearInterval(dataInterval);
    clearInterval(timeInterval);
});
</script>

<template>
    <DisplayLayout title="Display Antrian ASABRI">
        
        <div class="w-full h-full flex bg-gray-50">
            
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
                        <template v-if="nextQueues.length > 0">
                            <div v-for="queue in nextQueues" :key="queue.id" 
                                 class="flex-1 bg-white border-2 border-gray-200 rounded-2xl p-4 flex flex-col items-center justify-center shadow-md transform hover:scale-105 transition-transform">
                                <div class="text-6xl font-black text-[#00569c] font-mono mb-1">
                                    {{ queue.ticket_code }}
                                </div>
                                <div class="text-sm font-bold text-gray-500 uppercase">
                                    {{ queue.counter?.name || '-' }}
                                </div>
                            </div>
                        </template>
                        <div v-else class="text-gray-400 italic font-bold">
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
        </div>

        <div v-if="!isAudioEnabled" class="fixed inset-0 bg-[#00569c]/95 backdrop-blur-xl flex items-center justify-center z-50">
            <div class="text-center">
                <div class="mb-10 transform scale-125">
                    <div class="bg-white p-6 rounded-[30px] inline-block mb-4 shadow-2xl border-4 border-yellow-400">
                        <img src="/images/logo-asabri.png" alt="Logo" class="h-24 w-auto object-contain" @error="(e) => e.target.style.display='none'">
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

    </DisplayLayout>
</template>

<style scoped>
.font-mono { font-family: 'Courier New', Courier, monospace; }
</style>