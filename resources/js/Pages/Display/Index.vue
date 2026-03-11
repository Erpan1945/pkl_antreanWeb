<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { callQueue, preloadCommonAudio } from '@/utils/queueAudio';
import DisplayLayout from '@/Layouts/DisplayLayout.vue'; 
import { supabase } from '@/utils/supabase';

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
let realtimeChannel = null;

const isBackgroundRefreshing = ref(false); // Tambahkan ini
let debounceTimer = null; // Tambahkan ini

// --- YOUTUBE API LOGIC ---
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

const playNextAnnouncement = () => {
    // Tambahkan proteksi: Jika user belum klik 'Mulai', jangan putar suara
    if (!isAudioEnabled.value) {
        console.warn("Audio belum diaktifkan oleh user (tombol Mulai belum diklik).");
        return;
    }

    if (pendingAnnouncements.value.length === 0) {
        isSpeaking.value = false;
        if (player.value?.setVolume) player.value.setVolume(100);
        return;
    }

    isSpeaking.value = true;
    if (player.value?.setVolume) player.value.setVolume(10); 
    
    const queueData = pendingAnnouncements.value.shift();
    currentDisplayQueue.value = queueData.originalData;

    // Panggil audio dengan penanganan error yang lebih halus
    callQueue(queueData.announcement, () => {
        playNextAnnouncement();
    });
};

watch(() => props.queues, (newQueues) => {
    if (!newQueues || newQueues.length === 0) return;

    newQueues.forEach(fullData => {
        const lastTime = processedState.value.get(fullData.id);
        
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

const nextQueues = computed(() => (props.queues || []).filter(q => q.status === 'waiting').slice(0, 3));

onMounted(() => {
    preloadCommonAudio().catch(() => {});
    loadYoutubeAPI();
    timeInterval = setInterval(checkIndonesiaRayaTime, 1000); 

    // --- REVISI FINAL: SUPABASE REALTIME DENGAN PENGAMAN ---
    realtimeChannel = supabase
        .channel('public:queues_display_stable')
        .on('postgres_changes', { 
            event: '*', 
            schema: 'public', 
            table: 'queues' 
        }, (payload) => {
            // Indikator sinyal di Console
            console.log("SINYAL DITERIMA!", payload.eventType);

            // 1. Hapus rencana refresh sebelumnya (Peredam Kejut)
            if (debounceTimer) clearTimeout(debounceTimer);

            // 2. Buat rencana refresh baru setelah jeda 0.8 detik
            debounceTimer = setTimeout(() => {
                
                const performUpdate = () => {
                    // 3. Jika sedang ada proses tarik data, jangan dibatalkan tapi antre
                    if (isBackgroundRefreshing.value) {
                        console.log("Sabar, lagi narik data...");
                        setTimeout(performUpdate, 500);
                        return;
                    }

                    console.log("Memperbarui tampilan TV secara realtime...");
                    isBackgroundRefreshing.value = true;
                    
                    router.reload({
                        only: ['queues'],
                        preserveScroll: true,
                        // --- KUNCI: TRUE agar status 'Mulai' tidak hilang/reset ---
                        preserveState: true, 
                        onFinish: () => { 
                            isBackgroundRefreshing.value = false; 
                            console.log("Update TV Selesai!");
                        }
                    });
                };

                performUpdate();
                
            }, 800); 
        })
        .subscribe((status) => {
            console.log("STATUS KONEKSI SUPABASE:", status);
        });
});

onUnmounted(() => {
    clearInterval(timeInterval);
    if (realtimeChannel) {
        supabase.removeChannel(realtimeChannel);
    }
});
</script>

<template>
    <DisplayLayout title="Display Antrian ASABRI">
        <div class="w-screen h-screen flex overflow-hidden bg-[#f8f9fa] p-[2vh]">
            
            <div v-if="!isIndonesiaRayaPlaying" class="w-[55%] h-full flex flex-col gap-[2vh] pr-[2vh]">
                
                <div class="flex-1 bg-[#ffc107] rounded-[3vh] border-[1vh] border-[#00569c] shadow-2xl p-[3vh] relative flex items-center justify-between overflow-hidden">
                    
                    <div class="flex flex-col items-start justify-center w-[65%] h-full">
                        <h2 class="text-[2.5vh] font-black text-[#00569c] uppercase tracking-widest mb-[0.5vh] opacity-80 pl-[2vh]">
                            NOMOR ANTRIAN
                        </h2>
                        
                        <svg viewBox="0 0 500 200" preserveAspectRatio="xMidYMid meet" class="w-full h-full drop-shadow-2xl">
                            <text x="50%" y="55%" 
                                  font-size="160" 
                                  font-weight="900"
                                  dominant-baseline="central" 
                                  text-anchor="middle" 
                                  class="svg-ticket-text">
                                {{ currentDisplayQueue ? currentDisplayQueue.ticket_code : 'A-000' }}
                            </text>
                        </svg>
                    </div>

                    <div class="flex flex-col items-center justify-center w-[32%] h-full pr-[1vh]">
                        <span class="text-[#00569c] text-[3vh] font-black uppercase mb-[1vh]">MENUJU</span>
                        <div class="bg-[#00569c] text-white p-[3vh] rounded-[3vh] flex flex-col items-center w-full aspect-square justify-center shadow-xl border-[0.5vh] border-white/20">
                            <span class="text-[3.5vh] font-bold tracking-widest uppercase">LOKET</span>
                            <span class="text-[12vh] font-black leading-none">
                                {{ currentDisplayQueue ? currentDisplayQueue.counter?.name.replace(/\D/g, '') : '-' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="h-[30vh] bg-white rounded-[3vh] shadow-xl border-[0.3vh] border-gray-100 flex flex-col overflow-hidden">
                    <div class="bg-[#00569c] text-white py-[1.5vh] px-[3vh] text-center font-black uppercase tracking-[0.2em] text-[1.8vh]">
                        ANTRIAN BERIKUTNYA
                    </div>
                    <div class="flex-1 p-[2vh] flex gap-[2vw] items-center justify-center">
                        <template v-if="nextQueues.length > 0">
                            <div v-for="queue in nextQueues" :key="queue.id" 
                                 class="flex-1 h-full bg-white border-[0.3vh] border-gray-200 rounded-[2vh] p-[2vh] flex flex-col items-center justify-center shadow-md transform hover:scale-105 transition-transform">
                                <div class="text-[6vh] font-black text-[#00569c] font-mono mb-[0.5vh]">
                                    {{ queue.ticket_code }}
                                </div>
                                <div class="text-[1.8vh] font-bold text-gray-500 uppercase">
                                    {{ queue.counter?.name || '-' }}
                                </div>
                            </div>
                        </template>
                        <div v-else class="text-gray-400 italic font-bold text-[2vh]">
                            Belum ada antrian menunggu
                        </div>
                    </div>
                </div>
            </div>

            <div :class="[isIndonesiaRayaPlaying ? 'fixed inset-0 z-[100] p-0 bg-black' : 'flex-1 h-full']" class="flex items-center justify-center transition-all duration-700 ease-in-out">
                <div :class="[isIndonesiaRayaPlaying ? 'rounded-0' : 'rounded-[3vh] border-[0.5vh] border-[#ffc107] shadow-2xl']" class="w-full h-full bg-black overflow-hidden relative flex items-center justify-center">
                    <div id="youtube-player" class="absolute pointer-events-none"></div>
                    <div class="absolute inset-0 bg-transparent z-10"></div>
                </div>
            </div>
        </div>

        <div v-if="!isAudioEnabled" class="fixed inset-0 bg-[#00569c]/95 backdrop-blur-xl flex items-center justify-center z-[500]">
            <div class="flex flex-col items-center justify-center text-center p-[5vh] max-w-[90vw]">
                
                <div class="bg-white p-[3vh] rounded-[4vh] shadow-2xl border-[0.6vh] border-yellow-400 mb-[4vh] transition-transform duration-500 hover:scale-105">
                    <img src="/images/logo-asabri.png" alt="Logo" class="h-[15vh] w-auto object-contain">
                </div>

                <div class="flex flex-col gap-[1vh] mb-[8vh]">
                    <h2 class="text-[7vh] font-black text-white uppercase tracking-tighter leading-none drop-shadow-lg">
                        SISTEM DISPLAY TV
                    </h2>
                    <p class="text-yellow-400 text-[3vh] font-black tracking-[0.3em] uppercase opacity-90">
                        PT ASABRI KC MALANG
                    </p>
                </div>

                <button @click="enableAudio" 
                        class="group relative inline-flex items-center justify-center px-[8vw] py-[3.5vh] overflow-hidden font-black rounded-full shadow-2xl transition-all duration-300 bg-yellow-400 hover:bg-yellow-300 hover:scale-110 active:scale-95">
                    <span class="text-[3.5vh] text-[#00569c] tracking-widest">MULAI DISPLAY TV</span>
                    
                    <div class="absolute inset-0 w-full h-full bg-white/20 -skew-x-12 translate-x-full group-hover:translate-x-[-100%] transition-transform duration-1000"></div>
                </button>

            </div>
        </div>

    </DisplayLayout>
</template>

<style scoped>
:deep(body) {
    overflow: hidden;
    margin: 0;
}

.font-mono { font-family: 'Courier New', Courier, monospace; }

/* Styling Teks SVG agar pas dan tajam */
.svg-ticket-text {
    fill: #00569c;
    font-weight: 900;
    font-family: 'Courier New', Courier, monospace;
    letter-spacing: -2px;
}

/* --- PERBAIKAN VIDEO LANDSCAPE --- */
#youtube-player {
    width: 100% !important;
    height: 100% !important;
    /* Kita hapus scale(1.1) agar video tidak nge-zoom */
    transform: scale(1.0); 
    /* Memastikan video tetap di tengah kontainer */
    object-fit: contain; 
}

:deep(iframe) {
    /* Ganti 100vw/vh menjadi 100% agar mengikuti ukuran kotak flex-1 */
    width: 100% !important; 
    height: 100% !important;
    border: none;
    /* Menghilangkan margin default iframe */
    display: block;
}

/* Memastikan kontainer video tidak membocorkan gambar keluar */
.flex-1.h-full.bg-black {
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>