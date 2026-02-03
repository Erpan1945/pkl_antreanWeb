<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const currentTime = ref('');
let interval = null;

const updateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('id-ID', { 
        hour: '2-digit', minute: '2-digit', second: '2-digit' 
    }).replace(/\./g, ':');
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
    <header class="w-full bg-[#00569c] flex flex-col md:flex-row items-center justify-between px-4 md:px-8 py-3 md:py-0 h-auto md:h-[100px] shadow-xl z-20 border-b-4 border-yellow-400 shrink-0 gap-4">
        
        <div class="flex flex-col sm:flex-row items-center gap-4 text-center sm:text-left">
            <div class="bg-white p-2 rounded-lg shadow-lg shrink-0">
                <img src="/images/logo-asabri.png" alt="Logo" class="h-12 w-auto object-contain">
            </div>
            <div>
                <h1 class="text-base md:text-2xl font-bold tracking-wide text-white leading-tight">
                    Kantor Cabang Malang PT ASABRI (Persero)
                </h1>
                <div class="hidden sm:block">
                    <p class="text-white text-[10px] md:text-xs font-normal tracking-wide mt-1 opacity-90">
                        Ruko Raden Intan Square Jl. Raden Intan No.Kav. 74/I, Arjosari
                    </p>
                    <p class="text-white text-[10px] md:text-xs font-normal tracking-wide opacity-90">
                        Kec. Blimbing, Kota Malang, Jawa Timur 65126
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="hidden md:block bg-white/10 p-2 rounded-full border border-white/20">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                    <path d="M12 6v6l4 2" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="text-3xl md:text-5xl font-bold text-white tracking-wider font-mono">
                {{ currentTime }}
            </div>
        </div>
    </header>
</template>