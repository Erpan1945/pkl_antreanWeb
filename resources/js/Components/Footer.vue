<template>
    <footer class="h-10 md:h-14 bg-[#00569c] flex items-center shadow-2xl z-30 relative overflow-hidden border-t-4 border-yellow-400 shrink-0">
        
        <div class="h-full bg-yellow-400 px-3 md:px-8 flex items-center font-black italic text-[#00569c] text-xs md:text-xl z-40 relative shadow-lg shrink-0 clip-miring">
            PT ASABRI
        </div>
        
        <div class="flex-1 overflow-hidden relative flex items-center h-full">
            <div class="animate-marquee whitespace-nowrap flex items-center">
                <span class="text-xs md:text-lg font-bold text-white uppercase mx-4">
                    PT ASABRI (Persero) Melayani dengan Sepenuh Hati
                </span>
                <span class="text-xs md:text-lg font-bold text-white uppercase mx-4">
                    PT ASABRI (Persero) Melayani dengan Sepenuh Hati
                </span>
                <span class="text-xs md:text-lg font-bold text-white uppercase mx-4">
                    PT ASABRI (Persero) Melayani dengan Sepenuh Hati
                </span>
                <span class="text-xs md:text-lg font-bold text-white uppercase mx-4">
                    PT ASABRI (Persero) Melayani dengan Sepenuh Hati
                </span>
                <span class="text-xs md:text-lg font-bold text-white uppercase mx-4">
                    PT ASABRI (Persero) Melayani dengan Sepenuh Hati
                </span>
                <span class="text-xs md:text-lg font-bold text-white uppercase mx-4">
                    PT ASABRI (Persero) Melayani dengan Sepenuh Hati
                </span>
                <span class="text-xs md:text-lg font-bold text-white uppercase mx-4">
                    PT ASABRI (Persero) Melayani dengan Sepenuh Hati
                </span>
                <span class="text-xs md:text-lg font-bold text-white uppercase mx-4">
                    PT ASABRI (Persero) Melayani dengan Sepenuh Hati
                </span>
            </div>
        </div>
    </footer>
</template>

<style scoped>
/* Animasi Running Text Smooth */
.animate-marquee {
    display: inline-block;
    animation: marquee 50s linear infinite;
}

@keyframes marquee {
    0% { transform: translateX(0); }
    100% { transform: translateX(-100%); }
}

/* Clip Path Miring (Default Desktop) */
.clip-miring {
    clip-path: polygon(0 0, 90% 0, 100% 100%, 0% 100%);
}

/* Media Query: Hilangkan miring di HP agar teks "PT ASABRI" muat */
@media (max-width: 640px) {
    .clip-miring {
        clip-path: none !important;
        border-right: 3px solid #00447c;
    }
}
</style>