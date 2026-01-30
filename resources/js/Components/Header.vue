<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const currentTime = ref('');
let interval = null;

const updateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
};

onMounted(() => {
    updateTime();
    interval = setInterval(updateTime, 1000);
});

onUnmounted(() => {
    clearInterval(interval);
});
</script>

<template>
    <header class="h-[100px] bg-[#00569c] flex items-center justify-between px-8 shadow-xl z-20 border-b-4 border-yellow-400 shrink-0">
        <div class="flex items-center gap-4">
            <div class="bg-white p-2 rounded-lg shadow-lg">
                <img src="/images/logo-asabri.png" alt="Logo" class="h-12 w-auto object-contain" onError="this.style.display='none'">
            </div>
            <div>
                <h1 class="text-2xl font-bold tracking-wide text-white">Kantor Cabang Malang PT ASABRI (Persero)</h1>
                <p class="text-white text-xs font-normal tracking-wide mt-1 opacity-90">Ruko Raden Intan Square Jl. Raden Intan No.Kav. 74/I, Arjosari,</p>
                <p class="text-white text-xs font-normal tracking-wide opacity-90">Kec. Blimbing, Kota Malang, Jawa Timur 65126</p>
            </div>
        </div>
        <div class="text-right flex items-center gap-4">
            <div class="bg-white/10 p-3 rounded-full border border-white/20">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                    <path d="M12 6v6l4 2" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="text-5xl font-bold text-white tracking-wider font-mono">
                {{ currentTime }}
            </div>
        </div>
    </header>
</template>