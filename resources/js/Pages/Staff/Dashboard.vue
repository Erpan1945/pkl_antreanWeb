<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';

// Props dari Controller (Data Awal)
const props = defineProps({
    counter: Object,
    currentServing: Object,
    waitingCount: Number,
    auth: Object
});

// State Lokal
const localServing = ref(props.currentServing);
const localWaiting = ref(props.waitingCount);
const processing = ref(false); // Agar tombol tidak dipencet dobel

// --- FUNGSI UPDATE DATA REALTIME (POLLING) ---
let pollingInterval = null;

const fetchUpdates = async () => {
    try {
        // Panggil API getStats yang sudah kita buat di Controller
        const response = await axios.get(route('staff.stats', props.counter.id));
        localServing.value = response.data.currentServing;
        localWaiting.value = response.data.waitingCount;
    } catch (error) {
        console.error("Gagal update data:", error);
    }
};

onMounted(() => {
    // Update data setiap 3 detik
    pollingInterval = setInterval(fetchUpdates, 3000);
});

onUnmounted(() => {
    clearInterval(pollingInterval);
});

// --- TOMBOL ACTIONS ---

// 1. Panggil Berikutnya
const callNext = () => {
    if (processing.value) return;
    processing.value = true;

    router.post(route('staff.callNext'), {
        counter_id: props.counter.id
    }, {
        onFinish: () => {
            processing.value = false;
            fetchUpdates(); // Langsung update tampilan
        }
    });
};

// 2. Panggil Ulang (Recall)
const recall = () => {
    if (!localServing.value) return;
    
    router.post(route('staff.recall'), {
        counter_id: props.counter.id
    });
};

// 3. Selesai (Complete)
const complete = () => {
    if (!localServing.value) return;
    
    router.post(route('staff.complete'), {
        queue_id: localServing.value.id
    }, {
        onSuccess: () => {
            localServing.value = null; // Kosongkan tampilan
        }
    });
};
</script>

<template>
    <Head :title="`${counter.name} - Dashboard Staff`" />
    
    <div class="min-h-screen bg-gray-50 font-sans flex flex-col">

        <div class="bg-blue-900 text-white px-6 py-4 flex justify-between items-center shadow-lg">
            <div>
                <h1 class="text-xl font-bold">{{ auth.user.name }}</h1>
                <p class="text-xs text-blue-200">Bertugas di: <span class="font-bold text-yellow-400">{{ counter.name }}</span></p>
            </div>
            <div class="flex items-center gap-3">
                
                <Link 
                    v-if="auth.user.role === 'admin'" 
                    :href="route('admin.dashboard')" 
                    class="bg-purple-600 hover:bg-purple-500 px-4 py-2 rounded text-sm font-bold transition flex items-center gap-2 border border-purple-400 shadow-sm"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm8.706-1.442c1.146-.573 2.437.463 2.126 1.706l-.709 2.836.042-.02a.75.75 0 01.67 1.34l-.04.022c-1.147.573-2.438-.463-2.127-1.706l.71-2.836-.042.02a.75.75 0 01-.671-1.34l.041-.022zM12 9a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" />
                    </svg>
                    <span class="hidden md:inline">Panel Admin</span>
                </Link>

                <a :href="route('admin.export')" target="_blank" class="bg-green-600 hover:bg-green-500 px-4 py-2 rounded text-sm font-bold transition flex items-center gap-2 shadow-sm border border-green-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                        <path fill-rule="evenodd" d="M12 2.25a.75.75 0 01.75.75v11.69l3.22-3.22a.75.75 0 111.06 1.06l-4.5 4.5a.75.75 0 01-1.06 0l-4.5-4.5a.75.75 0 111.06-1.06l3.22 3.22V3a.75.75 0 01.75-.75zm-9 13.5a.75.75 0 01.75.75v2.25a1.5 1.5 0 001.5 1.5h13.5a1.5 1.5 0 001.5-1.5V16.5a.75.75 0 011.5 0v2.25a3 3 0 01-3 3H5.25a3 3 0 01-3-3V16.5a.75.75 0 01.75-.75z" clip-rule="evenodd" />
                    </svg>
                    <span class="hidden md:inline">Excel</span>
                </a>

                <Link :href="route('staff.index')" class="bg-blue-800 hover:bg-blue-700 px-4 py-2 rounded text-sm font-bold transition border border-blue-600">
                    Ganti Loket
                </Link>
                
                <Link :href="route('logout')" method="post" as="button" class="bg-red-600 hover:bg-red-500 px-4 py-2 rounded text-sm font-bold transition border border-red-700">
                    Logout
                </Link>
            </div>
        </div>

        <div class="flex-1 p-6 flex flex-col items-center justify-center max-w-4xl mx-auto w-full gap-8">

            <div class="w-full grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl shadow-md border-2 border-blue-100 p-6 flex flex-col items-center justify-center min-h-[200px] relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full bg-blue-100 text-blue-800 text-center py-1 text-sm font-bold uppercase tracking-wider">
                        Sedang Dilayani
                    </div>
                    
                    <div v-if="localServing" class="text-center mt-4">
                        <span class="block text-7xl font-black text-blue-900 tracking-tighter">
                            {{ localServing.ticket_code }}
                        </span>
                        <div class="mt-2 text-gray-500 font-medium">
                            <p>{{ localServing.guest_name }}</p>
                            <p class="text-sm">{{ localServing.identity_number }}</p>
                        </div>
                    </div>

                    <div v-else class="text-center mt-4 text-gray-400 italic">
                        <span class="text-5xl block opacity-30">---</span>
                        <p>Belum ada antrian dipanggil</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-md border-2 border-orange-100 p-6 flex flex-col items-center justify-center min-h-[200px] relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full bg-orange-100 text-orange-800 text-center py-1 text-sm font-bold uppercase tracking-wider">
                        Menunggu Dipanggil
                    </div>
                    <div class="text-center mt-4">
                        <span class="block text-7xl font-black text-orange-500">
                            {{ localWaiting }}
                        </span>
                        <p class="text-gray-400 font-medium mt-2">Orang</p>
                    </div>
                </div>
            </div>

            <div class="w-full bg-white p-6 rounded-2xl shadow-lg border border-gray-200">
                <h2 class="text-center text-gray-400 font-bold text-sm uppercase mb-4 tracking-widest">Panel Kontrol</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    
                    <button 
                        @click="recall"
                        :disabled="!localServing || processing"
                        class="h-20 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 active:scale-95 transition disabled:opacity-50 disabled:cursor-not-allowed flex flex-col items-center justify-center gap-1"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75z" />
                        </svg>
                        PANGGIL ULANG
                    </button>

                    <button 
                        @click="callNext"
                        :disabled="processing || localWaiting === 0"
                        class="h-20 bg-blue-600 text-white font-black text-lg rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 active:scale-95 transition disabled:opacity-50 disabled:bg-gray-400 disabled:shadow-none flex flex-col items-center justify-center gap-1"
                    >
                        <span v-if="processing">MEMPROSES...</span>
                        <span v-else class="flex items-center gap-2">
                            PANGGIL BERIKUTNYA
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </span>
                    </button>

                    <button 
                        @click="complete"
                        :disabled="!localServing || processing"
                        class="h-20 bg-green-500 text-white font-bold rounded-xl hover:bg-green-600 active:scale-95 transition disabled:opacity-50 disabled:bg-gray-300 disabled:cursor-not-allowed flex flex-col items-center justify-center gap-1"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        SELESAIKAN ANTRIAN
                    </button>
                    
                </div>
            </div>

            <div class="text-gray-400 text-sm italic">
                *Tombol "Panggil Berikutnya" otomatis menyelesaikan antrian sebelumnya.
            </div>

        </div>
    </div>
</template>