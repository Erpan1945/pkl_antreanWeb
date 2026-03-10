<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { callQueue, preloadCommonAudio } from '@/utils/queueAudio';
import DisplayLayout from '@/Layouts/DisplayLayout.vue'; 
import { supabase } from '@/supabase';

// --- 0. TERIMA DATA DARI CONTROLLER (INERTIA PROPS) ---
const props = defineProps({
    queues: {
        type: Array,
        default: () => []
    }
});

// --- 1. CONFIG YOUTUBE PLAYER ---
const player = ref(null);
const playlistVideos = ["vHZhFmkINI8", "H7fAevGRHXQ", "7EJQy7gSk1c", "1q9ijwlvMTQ"];
const videoId = ref(playlistVideos[0]);
const indonesiaRayaId = "HKrhbLJa1JA"; 
const isIndonesiaRayaPlaying = ref(false);
const hasPlayedToday = ref(false); 

// --- STATE UTAMA ---
const processedState = ref(new Map()); 
const isAudioEnabled = ref(false);
const currentDisplayQueue = ref(null); 
const pendingAnnouncements = ref([]); 
const isSpeaking = ref(false); 

let timeInterval = null;
let realtimeChannel = null; // Pengganti variabel polling

// --- YOUTUBE API LOGIC (TIDAK ADA YANG BERUBAH) ---
const initPlayer = () => {
    if (player.value && typeof player.value.destroy === 'function') { 
        try { player.value.destroy(); } catch(e) { console.error(e); } 
    }
    
    player.value = new YT.Player('youtube-player', {
        width: '100%',
        height: '100%',
        videoId: videoId.value,
        playerVars: {
            'autoplay': 1, 'controls': 0, 'rel': 0, 'loop': 1, 
            'modestbranding': 1, 'iv_load_policy': 3, 
            'playlist': playlistVideos.join(','), 
            'playsinline': 1, 'origin': window.location.origin
        },
        events: {
            'onReady': (event) => {
                event.target.setVolume(100);
                if(isAudioEnabled.value) {
                    event.target.unMute();
                    event.target.playVideo();
                }
            },
            'onStateChange': (event) => {
                if (event.data === YT.PlayerState.ENDED && isIndonesiaRayaPlaying.value) {
                    stopIndonesiaRayaAndPlayQueue();
                }
            }
        }
    });
};

const stopIndonesiaRayaAndPlayQueue = () => {
    isIndonesiaRayaPlaying.value = false;
    if (player.value) {
        player.value.loadPlaylist({ playlist: playlistVideos, listType: 'playlist', index: 0, startSeconds: 0 });
        player.value.unMute();
        player.value.setVolume(100);
    }
};

const loadYoutubeAPI = () => {
    if (window.YT && window.YT.Player) { initPlayer(); } 
    else {
        window.onYouTubeIframeAPIReady = initPlayer;
        if (!document.querySelector('script[src="https://www.youtube.com/iframe_api"]')) {
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
    const targetHour = 10;    
    const targetMinute = 0;  

    if (hours === targetHour && minutes === targetMinute) {
        if (!isIndonesiaRayaPlaying.value && !hasPlayedToday.value) {
            if (player.value && typeof player.value.loadVideoById === 'function') {
                isIndonesiaRayaPlaying.value = true;
                hasPlayedToday.value = true; 
                player.value.loadVideoById(indonesiaRayaId);
                player.value.unMute();
                player.value.setVolume(100);
                player.value.playVideo();
                
                setTimeout(() => {
                    if (isIndonesiaRayaPlaying.value) stopIndonesiaRayaAndPlayQueue();
                }, 133000); 
            }
        }
    }
    if (minutes !== targetMinute) hasPlayedToday.value = false;
};

// --- PROCESSOR AUDIO (TIDAK BERUBAH) ---
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

// --- LOGIC AUDIO TRIGGER ---
// Akan otomatis berjalan jika Supabase menyuruh Inertia me-reload props.queues
watch(() => props.queues, (newQueues) => {
    if (!newQueues || newQueues.length === 0) return;

    newQueues.forEach(fullData => {
        const lastTime = processedState.value.get(fullData.id);
        
        // Jika ada antrean yang statusnya "called" dan waktu updatenya lebih baru
        if ((!lastTime || fullData.updated_at > lastTime) && fullData.status === 'called') {
            
            processedState.value.set(fullData.id, fullData.updated_at);
            
            const rawCode = fullData.ticket_code || fullData.number || '000'; 
            const prefix = isNaN(rawCode.charAt(0)) ? rawCode.charAt(0) : ''; 
            const numberOnly = rawCode.replace(/\D/g, ''); 
            const cleanedNumber = parseInt(numberOnly, 10).toString();
            
            pendingAnnouncements.value.push({
                announcement: { 
                    prefix, 
                    number: cleanedNumber, 
                    counter: fullData.counter ? fullData.counter.name.replace(/\D/g, '') : '1' 
                },
                originalData: fullData
            });

            if (!isSpeaking.value) playNextAnnouncement();
        }
    });
}, { deep: true, immediate: true });


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

// Menggunakan props.queues langsung karena datanya disuplai oleh Inertia
const nextQueues = computed(() => (props.queues || []).filter(q => q.status === 'waiting').slice(0, 3));

onMounted(() => {
    preloadCommonAudio().catch(() => {});
    loadYoutubeAPI();
    
    timeInterval = setInterval(checkIndonesiaRayaTime, 1000); 

    // --- FITUR SUPABASE REALTIME ---
    realtimeChannel = supabase
        .channel('public:queues_display') // Nama channel khusus display
        .on('postgres_changes', { event: '*', schema: 'public', table: 'queues' }, (payload) => {
            console.log('Sinyal Panggilan Diterima dari Supabase:', payload);
            
            // Tarik data antrean terbaru dari Laravel untuk mendapatkan update nama loket dll
            router.reload({
                only: ['queues'],
                preserveScroll: true, 
                preserveState: true   
            });
        })
        .subscribe();
});

onUnmounted(() => {
    clearInterval(timeInterval);
    // Hapus memori listener saat pindah halaman
    if (realtimeChannel) {
        supabase.removeChannel(realtimeChannel);
    }
});
</script>

<template>
    <DisplayLayout title="Display Antrian ASABRI">
        
        <div class="w-full h-full flex overflow-hidden relative bg-[#f8f9fa]">
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

            <div :class="[isIndonesiaRayaPlaying ? 'fixed inset-0 z-[100] p-0 bg-black' : 'w-[45%] p-6 pl-0']" class="flex items-center justify-center transition-all duration-700 ease-in-out">
                <div :class="[isIndonesiaRayaPlaying ? 'rounded-0 border-0' : 'rounded-[30px] border-4 border-[#ffc107] shadow-2xl']" class="w-full h-full bg-black overflow-hidden relative flex items-center justify-center">
                    <div id="youtube-player" class="absolute pointer-events-none"></div>
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
                <button @click="enableAudio" class="group relative inline-flex items-center justify-center px-16 py-6 text-2xl font-black text-[#00569c] transition-all duration-300 bg-yellow-400 font-sans rounded-full hover:scale-110 shadow-lg">
                    <span>MULAI DISPLAY TV</span>
                </button>
            </div>
        </div>

    </DisplayLayout>
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