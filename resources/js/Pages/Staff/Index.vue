<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';

const inertiaIsLoading = ref(false);

document.addEventListener('inertia:start', () => (inertiaIsLoading.value = true));
document.addEventListener('inertia:finish', () => (inertiaIsLoading.value = false));

const props = defineProps({
    counters: Array,
    auth: Object
});
</script>

<template>
    <div class="min-h-screen bg-gray-100 flex flex-col items-center justify-center p-6 font-sans">
        <Head title="Pilih Loket Bertugas" />
        
        <LoadingOverlay :show="inertiaIsLoading" message="Memuat..." />

        <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-lg text-center border-t-8 border-blue-900 relative">
            
            <h1 class="text-2xl font-bold text-blue-900 mb-2">
                Halo, {{ auth.user.name }}
            </h1>
            <p class="text-gray-500 mb-8">
                Silakan pilih loket tempat Anda bertugas hari ini.
            </p>

            <div class="grid gap-4">
                <div v-for="counter in counters" :key="counter.id">
                    <Link 
                        :href="route('staff.dashboard', counter.id)"
                        class="group w-full py-4 px-6 bg-blue-50 border-2 border-blue-200 rounded-xl text-blue-900 font-bold hover:bg-blue-600 hover:text-white hover:border-blue-600 hover:shadow-lg transition-all transform active:scale-95 flex justify-between items-center"
                    >
                        <span>{{ counter.name }}</span>
                        <span class="bg-blue-200 text-blue-800 group-hover:bg-white group-hover:text-blue-600 py-1 px-3 rounded-lg text-xs">
                            Loket {{ counter.number }}
                        </span>
                    </Link>
                </div>

                <div v-if="counters.length === 0" class="p-4 bg-red-50 text-red-600 rounded-lg border border-red-200 text-sm">
                    ⚠️ Belum ada data Master Loket.<br>Hubungi Admin untuk menambahkan loket.
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-100 flex flex-col gap-3">
                
                <Link 
                    v-if="auth.user.role === 'admin'"
                    :href="route('admin.dashboard')" 
                    class="w-full py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-bold text-sm transition flex items-center justify-center gap-2 shadow-md"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                    </svg>
                    KEMBALI KE ADMIN DASHBOARD
                </Link>

                <Link :href="route('logout')" method="post" as="button" class="text-red-500 hover:text-red-700 font-bold text-sm transition mt-2 py-2 hover:bg-red-50 rounded-lg">
                    Keluar / Logout
                </Link>
            </div>
        </div>

        <p class="mt-6 text-xs text-gray-400">PT Asabri (Persero) KC Malang</p>
    </div>
</template>