<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { supabase } from '@/utils/supabase'; // Ganti Axios dengan Supabase
import { callQueue, preloadCommonAudio } from '@/utils/queueAudio';
import DisplayLayout from '@/Layouts/DisplayLayout.vue'; 

// --- 1. CONFIG YOUTUBE PLAYER ---
const player = ref(null);
const videoId = ref("vHZhFmkINI8"); 
const indonesiaRayaId = "HKrhbLJa1JA"; 
const isIndonesiaRayaPlaying = ref(false);
const hasPlayedToday = ref(false); 

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
let timeInterval = null;
let realtimeChannel = null; // Channel Realtime Supabase

// --- YOUTUBE API LOGIC (TETAP) ---
const initPlayer = () => {
    if (player.value && typeof player.value.destroy === 'function') { 
        try { player.value.destroy(); } catch(e) { console.error(e); } 
    }
    
    player.value = new YT.Player('youtube-player', {
        videoId: videoId.value,
        playerVars: { 'autoplay': 1, 'controls': 0, 'rel': 0, 'loop': 1, 'playlist': videoId.value, 'playsinline': 1, 'origin': window.location.origin },
        events: {
            'onReady': (event) => {
                event.target.setVolume(100);
                event.target.mute();
                if(isAudioEnabled.value) { event.target.unMute(); event.target.playVideo(); }
            },
            'onStateChange': (event) => {
                if (event.data === YT.PlayerState.ENDED && !isIndonesiaRayaPlaying.value) { player.value.playVideo(); }
            }
        }
    });
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

// --- LOGIKA INDONESIA RAYA (TETAP) ---
const checkIndonesiaRayaTime = () => {
    const now = new Date();
    const hours = now.getHours();
    const minutes = now.getMinutes();
    const targetHour = 10; const targetMinute = 0;  

    if (hours === targetHour && minutes === targetMinute) {
        if (!isIndonesiaRayaPlaying.value && !hasPlayedToday.value) {
            if (player.value && typeof player.value.loadVideoById === 'function') {
                console.log("MEMUTAR INDONESIA RAYA...");
                isIndonesiaRayaPlaying.value = true;
                hasPlayedToday.value = true; 
                player.value.loadVideoById(indonesiaRayaId);
                player.value.unMute();
                player.value.setVolume(100);
                player.value.playVideo();
                setTimeout(() => {
                    console.log("KEMBALI KE ASABRI DAN LOCK PLAYLIST");
                    if (player.value) {
                        player.value.loadPlaylist({ playlist: [videoId.value], listType: 'playlist', index: 0, startSeconds: 0 });
                        player.value.setLoop(true);
                        player.value.unMute();
                        player.value.setVolume(100);
                    }
                    isIndonesiaRayaPlaying.value = false;
                }, 133000); 
            }
        }
    }
    if (minutes !== targetMinute) { hasPlayedToday.value = false; }
};

// --- PROCESSOR ANTRIAN (TETAP) ---
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

const fetchInitialData = async () => {
    try {
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