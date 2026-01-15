<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    counter: Object,
    currentServing: Object, // Data orang yang sedang dilayani
    waitingCount: Number,   // Jumlah antrian sisa
});

const recall = () => {
    if (!props.currentServing) return;
    router.post(route('staff.recall'), { 
        counter_id: props.counter.id 
    });
};

// Fungsi Panggil Next
const callNext = () => {
    router.post(route('staff.callNext'), { 
        counter_id: props.counter.id 
    });
};

// Fungsi Selesai
const complete = () => {
    if (!props.currentServing) return;
    router.post(route('staff.complete'), { 
        queue_id: props.currentServing.id 
    });
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 p-6">
        <div class="flex justify-between items-center mb-8 bg-white p-4 rounded shadow">
            <h1 class="text-xl font-bold">Halo, Petugas {{ counter.name }} 👋</h1>
            <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-full font-bold">
                Sisa Antrian: {{ waitingCount }}
            </div>
        </div>

        <div class="max-w-2xl mx-auto text-center space-y-8">
            
            <div class="bg-white p-10 rounded-2xl shadow-lg border-2 border-blue-100">
                <h2 class="text-gray-500 text-lg uppercase tracking-widest mb-4">Sedang Melayani</h2>
                
                <div v-if="currentServing">
                    <div class="text-8xl font-black text-blue-600 mb-2">
                        {{ currentServing.ticket_code }}
                    </div>
                    <div class="text-xl text-gray-600">
                        Status: <span class="text-green-600 font-bold">DIPANGGIL</span>
                    </div>
                </div>

                <div v-else class="py-10">
                    <p class="text-gray-400 italic text-xl">Belum ada antrian dipanggil</p>
                    <p class="text-sm text-gray-400 mt-2">Tekan tombol di bawah untuk memanggil</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <button 
                    @click="complete"
                    :disabled="!currentServing"
                    :class="!currentServing ? 'opacity-50 cursor-not-allowed' : 'hover:bg-green-600'"
                    class="bg-green-500 text-white py-4 rounded-xl text-xl font-bold shadow transition"
                >
                    ✅ Selesai
                </button>

                <button 
                    @click="recall"
                    :disabled="!currentServing"
                    :class="!currentServing ? 'opacity-50 cursor-not-allowed' : 'hover:bg-yellow-500'"
                    class="bg-yellow-400 text-white py-4 rounded-xl text-xl font-bold shadow transition flex items-center justify-center gap-2"
                >
                    🔔 Panggil Ulang
                </button>

                <button 
                    @click="callNext"
                    :disabled="waitingCount === 0"
                    :class="waitingCount === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'"
                    class="bg-blue-600 text-white py-4 rounded-xl text-xl font-bold shadow transition flex items-center justify-center gap-2"
                >
                    🔊 Panggil Berikutnya
                </button>
            </div>

        </div>
    </div>
</template>