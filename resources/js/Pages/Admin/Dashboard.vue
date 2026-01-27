<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler } from 'chart.js';
import { Line, Bar } from 'vue-chartjs';

// --- IMPORT ICONS PROFESIONAL (HEROICONS) ---
import {
    BuildingLibraryIcon,
    CalendarDaysIcon,
    ArrowDownTrayIcon,
    UsersIcon,
    CheckCircleIcon,
    ClockIcon,
    TableCellsIcon
} from '@heroicons/vue/24/solid';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler);

const props = defineProps({
    stats: Object,
    chart_hourly: Object,
    chart_status: Object,
    queues: Array,
    current_date: String
});

// --- REALTIME CLOCK ---
const currentTime = ref('');
const updateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }).replace(/\./g, ':');
};

// --- CHART CONFIG ---
const lineChartData = {
    labels: props.chart_hourly.labels,
    datasets: [{
        label: 'Antrian',
        borderColor: '#1e3a8a', // Biru Asabri
        backgroundColor: 'rgba(30, 58, 138, 0.1)',
        data: props.chart_hourly.data,
        fill: true,
        tension: 0.4
    }]
};

const lineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, grid: { borderDash: [2, 4] } } }
};

const barChartData = {
    labels: props.chart_status.labels,
    datasets: [{
        label: 'Jumlah',
        backgroundColor: '#FBBF24', // Kuning Emas
        data: props.chart_status.data,
        borderRadius: 4
    }]
};

const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, grid: { borderDash: [2, 4] } } }
};

// --- AUTO REFRESH ---
let interval = null;
let clockInterval = null;

onMounted(() => {
    updateTime();
    clockInterval = setInterval(updateTime, 1000);
    // Refresh data antrian tiap 10 detik
    interval = setInterval(() => {
        router.reload({ only: ['stats', 'chart_hourly', 'chart_status', 'queues'] });
    }, 10000);
});

onUnmounted(() => {
    clearInterval(interval);
    clearInterval(clockInterval);
});

// Helper Status Color
const getStatusBadge = (status) => {
    const map = {
        'waiting': 'bg-yellow-100 text-yellow-700 border border-yellow-200',
        'called': 'bg-blue-100 text-blue-700 border border-blue-200',
        'serving': 'bg-blue-500 text-white',
        'completed': 'bg-green-100 text-green-700 border border-green-200',
        'skipped': 'bg-red-100 text-red-700 border border-red-200',
    };
    return map[status] || 'bg-gray-100 text-gray-600';
};
</script>

<template>
    <Head :title="`Dashboard Admin`" />
    <div class="min-h-screen bg-gray-50 font-sans">
        <Head title="Dashboard Admin" />

        <div class="bg-blue-900 text-white px-8 py-4 flex justify-between items-center shadow-lg sticky top-0 z-30">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white rounded-lg flex items-center justify-center p-1 overflow-hidden shadow-sm">
                    <img src="/images/logo-asabri.png" alt="Logo" class="w-full h-full object-contain">
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-wide leading-tight">PT ASABRI (Persero) KC Malang</h1>
                    <p class="text-xs text-blue-200">Jl. Raden Intan No.Kav 74, Malang</p>
                </div>
            </div>

            <div class="flex items-center gap-6">
                
                <div class="text-right hidden md:block">
                    <div class="text-3xl font-mono font-bold leading-none">{{ currentTime }}</div>
                    <div class="text-[10px] text-blue-300 uppercase tracking-widest mt-1">Waktu Server</div>
                </div>

                <div class="h-10 w-px bg-blue-700 hidden md:block"></div>

                <div class="flex items-center gap-3">
                    
                    <Link :href="route('staff.index')" class="group flex flex-col items-center justify-center bg-blue-800 hover:bg-blue-700 border border-blue-600 rounded-lg px-4 py-2 transition active:scale-95" title="Masuk ke Mode Staff/Loket">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-200 group-hover:text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                        </svg>
                        <span class="text-[10px] font-bold text-blue-100 mt-0.5">MODE STAFF</span>
                    </Link>

                    <Link :href="route('logout')" method="post" as="button" class="group flex flex-col items-center justify-center bg-red-600 hover:bg-red-500 border border-red-700 rounded-lg px-4 py-2 transition active:scale-95" title="Keluar Aplikasi">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                        </svg>
                        <span class="text-[10px] font-bold text-white mt-0.5">LOGOUT</span>
                    </Link>

                </div>
            </div>
        </div>

        <div class="h-2 bg-yellow-400 w-full shadow-md"></div>

        <div class="p-8 max-w-7xl mx-auto space-y-8">
            
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-bold text-blue-900">Dashboard Admin</h2>
                    <div class="flex items-center gap-2 text-gray-500 mt-1">
                        <CalendarDaysIcon class="w-5 h-5" />
                        <span>{{ current_date }}</span>
                    </div>
                </div>
                <a href="/admin/export" target="_blank" class="bg-yellow-400 hover:bg-yellow-500 text-blue-900 font-bold py-3 px-6 rounded-lg shadow-md transition flex items-center gap-2 border-b-4 border-yellow-600 active:border-b-0 active:translate-y-1">
                    <ArrowDownTrayIcon class="w-5 h-5" />
                    EXPORT KE EXCEL
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-blue-50 border-l-8 border-blue-500 p-6 rounded-xl shadow-sm hover:shadow-md transition">
                    <div class="flex flex-col h-full justify-between">
                        <div class="mb-2">
                            <UsersIcon class="w-12 h-12 text-blue-900" />
                        </div>
                        <div>
                            <div class="text-5xl font-extrabold text-blue-900">{{ stats.total }}</div>
                            <div class="text-sm font-bold text-gray-600 mt-1">Total Antrian</div>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 border-l-8 border-green-500 p-6 rounded-xl shadow-sm hover:shadow-md transition">
                    <div class="flex flex-col h-full justify-between">
                        <div class="mb-2">
                            <CheckCircleIcon class="w-12 h-12 text-green-800" />
                        </div>
                        <div>
                            <div class="text-5xl font-extrabold text-green-800">{{ stats.completed }}</div>
                            <div class="text-sm font-bold text-gray-600 mt-1">Selesai</div>
                        </div>
                    </div>
                </div>

                <div class="bg-orange-50 border-l-8 border-orange-400 p-6 rounded-xl shadow-sm hover:shadow-md transition">
                    <div class="flex flex-col h-full justify-between">
                        <div class="mb-2">
                            <ClockIcon class="w-12 h-12 text-orange-600" />
                        </div>
                        <div>
                            <div class="text-5xl font-extrabold text-orange-600">{{ stats.waiting }}</div>
                            <div class="text-sm font-bold text-gray-600 mt-1">Menunggu</div>
                        </div>
                    </div>
                </div>

                <div class="bg-purple-50 border-l-8 border-purple-500 p-6 rounded-xl shadow-sm hover:shadow-md transition">
                    <div class="flex flex-col h-full justify-between">
                        <div class="mb-2">
                            <ClockIcon class="w-12 h-12 text-purple-800" />
                        </div>
                        <div>
                            <div class="text-4xl font-extrabold text-purple-800 leading-tight">{{ stats.avg_time }}</div>
                            <div class="text-sm font-bold text-gray-600 mt-1">Rata-rata Waktu</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border-2 border-blue-900">
                    <h3 class="text-lg font-bold text-blue-900 mb-4">Grafik Antrian per Jam</h3>
                    <div class="h-64">
                        <Line :data="lineChartData" :options="lineOptions" />
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border-2 border-blue-900">
                    <h3 class="text-lg font-bold text-blue-900 mb-4">Statistik Status Antrian</h3>
                    <div class="h-64">
                        <Bar :data="barChartData" :options="barOptions" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md border-2 border-blue-900 overflow-hidden">
                <div class="bg-blue-900 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-white font-bold text-lg flex items-center gap-2">
                        <TableCellsIcon class="w-6 h-6" />
                        Data Antrian Hari Ini
                    </h3>
                    <span class="text-blue-200 text-sm">Total: {{ queues.length }} data</span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-blue-50 text-blue-900 font-bold uppercase text-xs border-b border-blue-100">
                            <tr>
                                <th class="px-6 py-4">No</th>
                                <th class="px-6 py-4">Kode Tiket</th>
                                <th class="px-6 py-4">Nama Peserta</th>
                                <th class="px-6 py-4">NRP/NIP</th>
                                <th class="px-6 py-4">Telepon</th>
                                <th class="px-6 py-4">Jam Datang</th>
                                <th class="px-6 py-4">Loket</th>
                                <th class="px-6 py-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(q, index) in queues" :key="q.id" class="hover:bg-blue-50/50 transition duration-150">
                                <td class="px-6 py-4 font-bold text-gray-500">{{ index + 1 }}</td>
                                <td class="px-6 py-4">
                                    <span class="bg-yellow-400 text-blue-900 font-bold px-3 py-1 rounded shadow-sm">{{ q.ticket_code }}</span>
                                </td>
                                <td class="px-6 py-4 font-bold text-gray-800">{{ q.guest_name }}</td>
                                <td class="px-6 py-4 font-mono text-gray-600">{{ q.identity_number }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ q.phone_number }}</td>
                                <td class="px-6 py-4 font-bold">{{ new Date(q.created_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'}) }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span v-if="q.counter" class="bg-blue-900 text-white w-8 h-8 flex items-center justify-center rounded-full font-bold text-xs shadow">{{ q.counter.number }}</span>
                                    <span v-else class="text-gray-300 text-xl">-</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="getStatusBadge(q.status)" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide shadow-sm">
                                        {{ q.status }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="queues.length === 0">
                                <td colspan="8" class="p-8 text-center text-gray-400 italic bg-gray-50">
                                    Belum ada antrian hari ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="bg-blue-900 text-white py-3 w-full border-t-4 border-yellow-400 relative overflow-hidden flex">
            
            <div class="animate-marquee whitespace-nowrap flex-shrink-0 flex items-center">
                <span class="mx-4 text-lg font-bold tracking-wide">PT ASABRI (Persero) | Melayani dengan Sepenuh Hati</span>
                <span class="mx-1 text-yellow-400">•</span>
                <span class="mx-4 text-lg font-bold tracking-wide">PT ASABRI (Persero) | Melayani dengan Sepenuh Hati</span>
                <span class="mx-1 text-yellow-400">•</span>
                <span class="mx-4 text-lg font-bold tracking-wide">PT ASABRI (Persero) | Melayani dengan Sepenuh Hati</span>
                <span class="mx-1 text-yellow-400">•</span>
                <span class="mx-4 text-lg font-bold tracking-wide">PT ASABRI (Persero) | Melayani dengan Sepenuh Hati</span>
                <span class="mx-1 text-yellow-400">•</span>
            </div>

            <div class="animate-marquee whitespace-nowrap flex-shrink-0 flex items-center">
                <span class="mx-4 text-lg font-bold tracking-wide">PT ASABRI (Persero) | Melayani dengan Sepenuh Hati</span>
                <span class="mx-1 text-yellow-400">•</span>
                <span class="mx-4 text-lg font-bold tracking-wide">PT ASABRI (Persero) | Melayani dengan Sepenuh Hati</span>
                <span class="mx-1 text-yellow-400">•</span>
                <span class="mx-4 text-lg font-bold tracking-wide">PT ASABRI (Persero) | Melayani dengan Sepenuh Hati</span>
                <span class="mx-1 text-yellow-400">•</span>
                <span class="mx-4 text-lg font-bold tracking-wide">PT ASABRI (Persero) | Melayani dengan Sepenuh Hati</span>
                <span class="mx-1 text-yellow-400">•</span>
            </div>

        </div>
    </div>
</template>

<style>
/* Animasi Bergerak ke Kiri Sejauh Lebar Dirinya Sendiri (-100%) */
@keyframes marquee {
    0% { transform: translateX(0); }
    100% { transform: translateX(-100%); }
}

.animate-marquee {
    /* flex-shrink-0 WAJIB agar div tidak mengecil/turun */
    flex-shrink: 0;
    min-width: 100%;
    /* Durasi 40s agar gerakan halus (sesuaikan jika ingin lebih cepat/lambat) */
    animation: marquee 40s linear infinite;
}
</style>