<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { supabase } from '@/utils/supabase'; // Ganti Axios dengan Supabase
import { callQueue, preloadCommonAudio } from '@/utils/queueAudio';
import DisplayLayout from '@/Layouts/DisplayLayout.vue'; 

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
let timeInterval = null;
let realtimeChannel = null; // Channel Realtime Supabase

// --- YOUTUBE API LOGIC (TETAP) ---
const initPlayer = () => {
    if (player.value && typeof player.value.destroy === 'function') { 
        try { player.value.destroy(); } catch(e) { console.error(e); } 
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
        if (player.value && typeof player.value.setVolume === 'function') player.value.setVolume(100);
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
const fetchInitialData = async () => {
    try {
        const { data } = await axios.get('/display/data');
        activeQueues.value = data;
        if (!data.length || !isAudioEnabled.value) return;
        if (isFirstLoad) {
        console.log("🔍 Mengambil data antrian hari ini...");

        // 1. Buat format tanggal hari ini (YYYY-MM-DD)
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const todayStr = `${year}-${month}-${day}`; // Contoh: "2023-10-27"

        const { data, error } = await supabase
            .from('queues')
            .select('*, counter:counters ( id, name )')
            .gte('created_at', `${todayStr}T00:00:00`) // <--- FILTER PENTING: Hanya data mulai hari ini
            .order('created_at', { ascending: true });

        if (error) {
            console.error("❌ Error Supabase:", error.message);
            return;
        }

        activeQueues.value = data || [];
        
        // Logic agar audio tidak bunyi semua saat refresh
        if (data && data.length > 0 && isFirstLoad) {
            data.forEach(q => processedState.value.set(q.id, q.updated_at));
            isFirstLoad = false;
        }

    } catch (e) {
        console.error("❌ Gagal koneksi:", e);
    }
};

// --- SETUP REALTIME LISTENER ---
const setupRealtime = () => {
    realtimeChannel = supabase
        .channel('public:queues')
        .on('postgres_changes', { event: '*', schema: 'public', table: 'queues' }, async (payload) => {
            const { eventType, new: newData, old: oldData } = payload;

            if (eventType === 'DELETE') {
                activeQueues.value = activeQueues.value.filter(q => q.id !== oldData.id);
                return;
            }

            // Jika INSERT/UPDATE, kita fetch ulang row tsb supaya dapat data relasi 'counter'
            // Karena payload realtime raw biasanya tidak membawa data relasi
           const { data: fullData } = await supabase
                .from('queues')
                .select('*, counter:counters ( id, name )') 
                .eq('id', newData.id)
                .single();

            if (!fullData) return;

            if (eventType === 'INSERT') {
                activeQueues.value.push(fullData);
            } 
            else if (eventType === 'UPDATE') {
                const index = activeQueues.value.findIndex(q => q.id === fullData.id);
                if (index !== -1) {
                    activeQueues.value[index] = fullData;
                }

                // --- LOGIC AUDIO ANNOUNCEMENT (TETAP) ---
                const lastTime = processedState.value.get(fullData.id);
                
                // Cek jika status 'called' DAN (belum pernah bunyi ATAU waktu update baru)
                if ((!lastTime || fullData.updated_at > lastTime) && fullData.status === 'called') {
                    
                    processedState.value.set(fullData.id, fullData.updated_at);
                    
                    // Parsing nomor tiket (sesuai logic asli Anda)
                    const rawCode = fullData.ticket_code || fullData.number || '000'; 
                    const prefix = isNaN(rawCode.charAt(0)) ? rawCode.charAt(0) : ''; 
                    const numberOnly = rawCode.replace(/\D/g, ''); 
                    const cleanedNumber = parseInt(numberOnly, 10).toString();
                    
                    // Masukkan ke antrian bunyi
                    pendingAnnouncements.value.push({
                        announcement: { 
                            prefix, 
                            number: cleanedNumber, 
                            counter: fullData.counter ? fullData.counter.name.replace(/\D/g, '') : '1' 
                        },
                        originalData: fullData
                    });

                    // Bunyikan jika tidak sedang bicara
                    if (!isSpeaking.value) playNextAnnouncement();
                }
            }
        })
        .subscribe();
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

const currentTime = ref('');
const currentDate = ref('');
const updateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    currentDate.value = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    checkIndonesiaRayaTime();
};

onMounted(() => {
    // Preload audio assets
    preloadCommonAudio().catch(() => {});

    loadYoutubeAPI();
    
    // GANTI POLLING DENGAN REALTIME
    fetchInitialData(); // 1. Ambil data awal sekali saja
    setupRealtime();    // 2. Aktifkan listener realtime

    // Interval YouTube tetap jalan
    timeInterval = setInterval(checkIndonesiaRayaTime, 1000); 
});

onUnmounted(() => {
    // Hapus interval YouTube
    clearInterval(timeInterval);
    
    // Matikan Realtime
    if (realtimeChannel) {
        supabase.removeChannel(realtimeChannel);
    }
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