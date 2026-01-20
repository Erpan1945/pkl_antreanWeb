<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
// Import helper yang baru kita buat
import { callQueue } from '@/utils/queueAudio';

const activeQueues = ref([]);
const processedState = ref(new Map()); // Menyimpan state terakhir setiap ID: Map<id, updated_at>
const isAudioEnabled = ref(false);
let isFirstLoad = true;

let interval = null;

const processAudio = (queue) => {
    // --- PERSIAPAN DATA UNTUK AUDIO ---
    // Asumsi format: "A-001"
    const rawCode = queue.ticket_code; 
    
    // Ambil Huruf Depan (A)
    const prefix = rawCode.charAt(0); 
    
    // Ambil Angka Saja (001) - Buang tanda strip/huruf
    const numberOnly = rawCode.replace(/\D/g, ''); 

    // Panggil Fungsi Audio Helper
    callQueue({
        prefix: prefix,       // "A"
        number: numberOnly,   // "001"
        counter: queue.counter ? queue.counter.name.replace(/\D/g, '') : '1' // Ambil angka loketnya saja
    });
};

const fetchData = async () => {
    try {
        const { data } = await axios.get('/display/data');
        activeQueues.value = data;

        // Jangan proses jika data kosong atau audio belum diizinkan user
        if (!data.length || !isAudioEnabled.value) return;

        // --- LOGIKA BARU: MULTI-QUEUE HANDLING ---
        
        // 1. Jika ini load pertama, tandai semua sebagai "sudah diproses"
        // agar tidak bunyi semua sekaligus. Bunyikan HANYA yang paling baru (index 0).
        if (isFirstLoad) {
            data.forEach(q => processedState.value.set(q.id, q.updated_at));
            
            // Bunyikan yang paling atas (terbaru) sebagai tanda sistem aktif
            processAudio(data[0]);
            
            isFirstLoad = false;
            return;
        }

        // 2. Filter antrian yang BARU atau DI-UPDATE (Recall)
        const candidates = [];
        data.forEach(queue => {
            const lastTime = processedState.value.get(queue.id);
            
            // Jika ID belum ada, atau updated_at lebih baru dari yang tersimpan
            // Perbandingan string tanggal ISO8601 aman secara leksikal
            if (!lastTime || queue.updated_at > lastTime) {
                candidates.push(queue);
                // Update map segera agar tidak diproses ulang di loop berikutnya (jika ada overlap)
                processedState.value.set(queue.id, queue.updated_at);
            }
        });

        // 3. Jika ada kandidat, urutkan berdasarkan WAKTU TERLAMA ke TERBARU
        // Supaya yang menekan duluan, dipanggil duluan.
        if (candidates.length > 0) {
            candidates.sort((a, b) => new Date(a.updated_at) - new Date(b.updated_at));

            // 4. Masukkan ke antrian audio
            candidates.forEach(queue => {
                processAudio(queue);
            });
        }

    } catch (e) {
        console.error("Gagal mengambil data antrian:", e);
    }
};

const enableAudio = () => {
    isAudioEnabled.value = true;
    // Pancing audio dummy agar browser mengizinkan autoplay selanjutnya
    const unlock = new Audio();
    unlock.play().catch(() => {});
};

onMounted(() => {
    fetchData();
    interval = setInterval(fetchData, 3000);
});

onUnmounted(() => {
    clearInterval(interval);
});
</script>

<template>
    <div class="min-h-screen bg-gray-900 text-white overflow-hidden font-sans">
        
        <div v-if="!isAudioEnabled" class="fixed inset-0 bg-black/90 z-50 flex items-center justify-center flex-col">
            <h1 class="text-4xl font-bold mb-4">Siap Menampilkan Display</h1>
            <p class="mb-8 text-gray-400">Klik tombol di bawah agar suara panggilan bisa berbunyi</p>
            <button @click="enableAudio" class="px-8 py-4 bg-green-600 rounded-full text-2xl font-bold hover:bg-green-500 transition shadow-[0_0_30px_rgba(0,255,0,0.5)]">
                🔊 MULAI SISTEM LAYAR
            </button>
        </div>

        <div class="h-20 bg-blue-800 flex items-center justify-between px-10 shadow-lg relative z-10">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-blue-800 font-bold text-xl">
                    LOGO
                </div>
                <h1 class="text-2xl font-bold tracking-wider">PT ASABRI</h1>
            </div>
            
            <div class="flex flex-col items-end">
                <div class="text-xl font-mono uppercase">
                    {{ new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                </div>
            </div>
        </div>

        <div class="flex h-[calc(100vh-80px)]">
            
            <div class="w-7/12 bg-gray-800 p-6 flex flex-col gap-4 border-r border-gray-700">
                <div class="flex justify-between text-gray-400 px-4 uppercase text-sm tracking-widest mb-2">
                    <span>Nomor Antrian</span>
                    <span>Tujuan Loket</span>
                </div>

                <transition-group name="list" tag="div" class="flex flex-col gap-4">
                    <div 
                        v-for="(queue, index) in activeQueues" 
                        :key="queue.id"
                        class="relative bg-white text-gray-900 rounded-2xl p-6 flex items-center justify-between shadow-lg transform transition-all duration-500"
                        :class="{'scale-105 ring-4 ring-yellow-400 z-10': index === 0, 'opacity-70': index > 0}"
                    >
                        <div v-if="index === 0" class="absolute -top-3 -right-3 bg-red-600 text-white px-4 py-1 rounded-full text-xs font-bold shadow animate-pulse">
                            SEDANG DIPANGGIL
                        </div>

                        <div class="flex flex-col">
                            <span class="text-sm text-gray-500 font-bold uppercase">{{ queue.service.name }}</span>
                            <span class="text-7xl font-black tracking-tighter">{{ queue.ticket_code }}</span>
                        </div>

                        <div class="text-6xl text-blue-600">➝</div>

                        <div class="text-right">
                            <span class="text-sm text-gray-500 font-bold uppercase">Silakan Menuju</span>
                            <span class="block text-5xl font-bold text-blue-800">{{ queue.counter.name }}</span>
                        </div>
                    </div>
                </transition-group>

                <div v-if="activeQueues.length === 0" class="flex-1 flex items-center justify-center text-gray-500 flex-col opacity-50">
                    <div class="text-6xl mb-4">☕</div>
                    <p class="text-2xl">Menunggu antrian dipanggil...</p>
                </div>
            </div>

            <div class="w-5/12 bg-black relative flex items-center justify-center overflow-hidden">
                <!-- <iframe 
                    class="w-full h-full absolute inset-0 object-cover opacity-80"
                    src="" 
                    title="Company Profile" 
                    frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen>
                </iframe> -->
                (Area ini bisa diisi Video Profil Instansi, Slide Iklan, atau Pengumuman)
                
                <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-black opacity-60"></div>
                
                <div class="absolute bottom-0 w-full bg-blue-900/90 py-4 overflow-hidden whitespace-nowrap z-20 backdrop-blur-md border-t border-blue-500">
                    <div class="animate-marquee inline-block text-white font-bold text-xl tracking-wide">
                        Selamat Datang di Pelayanan Terpadu. Budayakan antri untuk kenyamanan bersama. Jam operasional: 08.00 - 15.00 WIB.
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<style>
.list-enter-active, .list-leave-active { transition: all 0.5s ease; }
.list-enter-from, .list-leave-to { opacity: 0; transform: translateX(-30px); }

@keyframes marquee {
    0% { transform: translateX(100%); }
    100% { transform: translateX(-100%); }
}
.animate-marquee {
    animation: marquee 25s linear infinite;
    padding-left: 100%;
}
</style>