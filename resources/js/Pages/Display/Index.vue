<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

// State Data
const activeQueues = ref([]);
const previousQueueId = ref(null); // Untuk melacak apakah ada nomor baru
const previousUpdatedAt = ref(null);
const isAudioEnabled = ref(false); // Browser butuh izin klik user untuk mulai suara

// 1. Fungsi Mengambil Data Terbaru
const fetchData = async () => {
    try {
        const response = await axios.get('/display/data');
        const data = response.data;
        activeQueues.value = data;

        // Cek apakah ada antrian baru yang dipanggil?
        // Logic: Jika data paling atas (index 0) berbeda ID-nya dengan yang tersimpan, berarti baru.
        if (data.length > 0) {
            const latest = data[0];
            
            // Logic Baru: Cek ID ATAU Cek Timestamp
            const isNewQueue = latest.id !== previousQueueId.value;
            const isRecalled = latest.updated_at !== previousUpdatedAt.value;

            // Jika antrian baru ATAU antrian lama dipanggil ulang
            if (isNewQueue || isRecalled) {
                
                // Update tracker kita
                previousQueueId.value = latest.id;
                previousUpdatedAt.value = latest.updated_at;
                
                // Mainkan suara
                if (isAudioEnabled.value) {
                    playVoice(latest);
                }
            }
        }
    } catch (error) {
        console.error("Gagal update data", error);
    }
};

// 2. Fungsi Text-to-Speech (Bahasa Indonesia)
const playVoice = (queue) => {
    // Format kalimat: "Nomor Antrian, A, Kosong Kosong Satu. Silakan Ke, Loket 3"
    // Tips: Kita eja nomornya agar terdengar natural (001 dibaca "Kosong Kosong Satu")
    
    let text = `Nomor Antrian, ${queue.ticket_code}. Silakan ke, ${queue.counter.name}`;
    
    // Ganti strip "-" dengan jeda koma agar tidak dibaca "Strip"
    text = text.replace('-', ', ');

    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'id-ID'; // Set Bahasa Indonesia
    utterance.rate = 0.9; // Kecepatan bicara (sedikit lambat biar jelas)
    utterance.pitch = 1;
    
    window.speechSynthesis.speak(utterance);
};

// 3. Lifecycle (Jalan saat halaman dibuka)
let interval = null;

onMounted(() => {
    fetchData(); // Ambil data pertama kali
    // Set Interval: Cek database setiap 3 detik (3000ms)
    interval = setInterval(fetchData, 3000);
});

onUnmounted(() => {
    clearInterval(interval); // Matikan interval kalau halaman ditutup
});

// Fungsi Tombol "Mulai Display" (Browser Policy)
const enableAudio = () => {
    isAudioEnabled.value = true;
    // Pancing audio dummy agar browser mengizinkan
    const dummy = new SpeechSynthesisUtterance("");
    window.speechSynthesis.speak(dummy);
};
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
                <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-blue-800 font-bold text-xl">LOGO</div>
                <h1 class="text-2xl font-bold tracking-wider">PT ASABRI</h1>
            </div>
            <div class="text-xl font-mono">{{ new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</div>
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

                        <div class="text-6xl text-blue-600">
                            ➝
                        </div>

                        <div class="text-right">
                            <span class="text-sm text-gray-500 font-bold uppercase">Silakan Menuju</span>
                            <span class="block text-5xl font-bold text-blue-800">{{ queue.counter.name }}</span>
                        </div>
                    </div>
                </transition-group>

                <div v-if="activeQueues.length === 0" class="flex-1 flex items-center justify-center text-gray-500 flex-col opacity-50">
                    <div class="text-6xl mb-4">☕</div>
                    <p class="text-2xl">Belum ada panggilan antrian.</p>
                </div>
            </div>

            <div class="w-5/12 bg-black relative">
                <div class="absolute inset-0 flex items-center justify-center">
                    <p class="text-gray-600 text-center px-10">
                        (Area ini bisa diisi Video Profil Instansi, Slide Iklan, atau Pengumuman)
                    </p>
                </div>
                
                <div class="absolute bottom-0 w-full bg-blue-900 py-3 overflow-hidden whitespace-nowrap">
                    <div class="animate-marquee inline-block text-white font-bold text-lg">
                        Selamat Datang di Pelayanan Terpadu. Budayakan antri untuk kenyamanan bersama. Jam operasional: 08.00 - 15.00 WIB.
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<style>
/* Animasi Masuk List */
.list-enter-active,
.list-leave-active {
  transition: all 0.5s ease;
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}

/* Animasi Running Text */
@keyframes marquee {
    0% { transform: translateX(100%); }
    100% { transform: translateX(-100%); }
}
.animate-marquee {
    animation: marquee 20s linear infinite;
    padding-left: 100%; /* Start dari luar layar */
}
</style>