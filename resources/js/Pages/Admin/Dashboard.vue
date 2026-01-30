<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler } from 'chart.js';
import { Line, Bar } from 'vue-chartjs';
import DisplayLayout from '@/Layouts/DisplayLayout.vue';
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';

// --- IMPORT ICONS ---
import {
    CalendarDaysIcon,
    ArrowDownTrayIcon,
    UsersIcon,
    CheckCircleIcon,
    ClockIcon,
    TableCellsIcon,
    Bars3Icon,          
    UserGroupIcon,      
    PowerIcon           
} from '@heroicons/vue/24/solid';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler);

const props = defineProps({
    stats: Object,
    chart_hourly: Object,
    chart_status: Object,
    queues: Array,
    current_date: String
});

// --- CHART CONFIG ---
const lineChartData = {
    labels: props.chart_hourly.labels,
    datasets: [{
        label: 'Antrian',
        borderColor: '#1e3a8a', 
        backgroundColor: 'rgba(30, 58, 138, 0.1)',
        data: props.chart_hourly.data,
        fill: true,
        tension: 0.4
    }]
};
const lineOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { borderDash: [2, 4] } } } };
const barChartData = {
    labels: props.chart_status.labels,
    datasets: [{ label: 'Jumlah', backgroundColor: '#FBBF24', data: props.chart_status.data, borderRadius: 4 }]
};
const barOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { borderDash: [2, 4] } } } };

// --- AUTO REFRESH ---
let interval = null;
onMounted(() => {
    interval = setInterval(() => {
        router.reload({ only: ['stats', 'chart_hourly', 'chart_status', 'queues'] });
    }, 10000);
});
onUnmounted(() => clearInterval(interval));

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
    <DisplayLayout title="Dashboard Admin">
        
        <div class="h-full w-full bg-gray-50 overflow-y-auto p-8 relative">
            
            <div class="absolute top-8 right-8 z-50">
                <Menu as="div" class="relative inline-block text-left">
                    <div>
                        <MenuButton class="inline-flex w-full justify-center rounded-lg bg-blue-900 px-4 py-3 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/75 shadow-lg items-center gap-2 border border-blue-700">
                            <span class="hidden md:inline">Menu Admin</span>
                            <Bars3Icon class="h-6 w-6" aria-hidden="true" />
                        </MenuButton>
                    </div>

                    <transition
                        enter-active-class="transition duration-100 ease-out"
                        enter-from-class="transform scale-95 opacity-0"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                    >
                        <MenuItems class="absolute right-0 mt-2 w-56 origin-top-right divide-y divide-gray-100 rounded-md bg-white shadow-xl ring-1 ring-black/5 focus:outline-none border border-gray-100">
                            <div class="px-1 py-1">
                                <MenuItem v-slot="{ active }">
                                    <Link :href="route('staff.index')" :class="[active ? 'bg-blue-50 text-blue-900' : 'text-gray-900', 'group flex w-full items-center rounded-md px-2 py-2 text-sm font-bold']">
                                        <UserGroupIcon class="mr-2 h-5 w-5 text-blue-600" aria-hidden="true" />
                                        Mode Loket / Staff
                                    </Link>
                                </MenuItem>
                            </div>
                            <div class="px-1 py-1">
                                <MenuItem v-slot="{ active }">
                                    <a href="/admin/export" target="_blank" :class="[active ? 'bg-yellow-50 text-yellow-900' : 'text-gray-900', 'group flex w-full items-center rounded-md px-2 py-2 text-sm font-bold']">
                                        <ArrowDownTrayIcon class="mr-2 h-5 w-5 text-yellow-600" aria-hidden="true" />
                                        Export Excel Harian
                                    </a>
                                </MenuItem>
                            </div>
                            <div class="px-1 py-1">
                                <MenuItem v-slot="{ active }">
                                    <Link :href="route('logout')" method="post" as="button" :class="[active ? 'bg-red-50 text-red-900' : 'text-gray-900', 'group flex w-full items-center rounded-md px-2 py-2 text-sm font-bold text-red-600']">
                                        <PowerIcon class="mr-2 h-5 w-5 text-red-600" aria-hidden="true" />
                                        Logout / Keluar
                                    </Link>
                                </MenuItem>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>
            </div>
            
            <div class="max-w-7xl mx-auto space-y-8 pb-10 mt-4">
                
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-blue-900">Dashboard Admin</h2>
                        <div class="flex items-center gap-2 text-gray-500 mt-1">
                            <CalendarDaysIcon class="w-5 h-5" />
                            <span>{{ current_date }}</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-blue-50 border-l-8 border-blue-500 p-6 rounded-xl shadow-sm hover:shadow-md transition">
                        <div class="flex flex-col h-full justify-between">
                            <div class="mb-2"><UsersIcon class="w-12 h-12 text-blue-900" /></div>
                            <div><div class="text-5xl font-extrabold text-blue-900">{{ stats.total }}</div><div class="text-sm font-bold text-gray-600 mt-1">Total Antrian</div></div>
                        </div>
                    </div>
                    <div class="bg-green-50 border-l-8 border-green-500 p-6 rounded-xl shadow-sm hover:shadow-md transition">
                        <div class="flex flex-col h-full justify-between">
                            <div class="mb-2"><CheckCircleIcon class="w-12 h-12 text-green-800" /></div>
                            <div><div class="text-5xl font-extrabold text-green-800">{{ stats.completed }}</div><div class="text-sm font-bold text-gray-600 mt-1">Selesai</div></div>
                        </div>
                    </div>
                    <div class="bg-orange-50 border-l-8 border-orange-400 p-6 rounded-xl shadow-sm hover:shadow-md transition">
                        <div class="flex flex-col h-full justify-between">
                            <div class="mb-2"><ClockIcon class="w-12 h-12 text-orange-600" /></div>
                            <div><div class="text-5xl font-extrabold text-orange-600">{{ stats.waiting }}</div><div class="text-sm font-bold text-gray-600 mt-1">Menunggu</div></div>
                        </div>
                    </div>
                    <div class="bg-purple-50 border-l-8 border-purple-500 p-6 rounded-xl shadow-sm hover:shadow-md transition">
                        <div class="flex flex-col h-full justify-between">
                            <div class="mb-2"><ClockIcon class="w-12 h-12 text-purple-800" /></div>
                            <div><div class="text-4xl font-extrabold text-purple-800 leading-tight">{{ stats.avg_time }}</div><div class="text-sm font-bold text-gray-600 mt-1">Rata-rata Waktu</div></div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-xl shadow-sm border-2 border-blue-900">
                        <h3 class="text-lg font-bold text-blue-900 mb-4">Grafik Antrian per Jam</h3>
                        <div class="h-64"><Line :data="lineChartData" :options="lineOptions" /></div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border-2 border-blue-900">
                        <h3 class="text-lg font-bold text-blue-900 mb-4">Statistik Status Antrian</h3>
                        <div class="h-64"><Bar :data="barChartData" :options="barOptions" /></div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md border-2 border-blue-900 overflow-hidden flex flex-col">
                    <div class="bg-blue-900 px-6 py-4 flex justify-between items-center shrink-0">
                        <h3 class="text-white font-bold text-lg flex items-center gap-2">
                            <TableCellsIcon class="w-6 h-6" />
                            Data Antrian Hari Ini
                        </h3>
                        <span class="text-blue-200 text-sm">Total: {{ queues.length }} data</span>
                    </div>
                    
                    <div class="overflow-y-auto max-h-[600px]">
                        <table class="w-full text-left text-sm relative border-collapse">
                            
                            <thead class="bg-blue-50 text-blue-900 font-bold uppercase text-xs sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th class="px-6 py-4 bg-blue-50">No</th>
                                    <th class="px-6 py-4 bg-blue-50">Kode Tiket</th>
                                    <th class="px-6 py-4 bg-blue-50">Nama Peserta</th>
                                    <th class="px-6 py-4 bg-blue-50">NRP/NIP</th>
                                    <th class="px-6 py-4 bg-blue-50">Telepon</th>
                                    <th class="px-6 py-4 bg-blue-50">Jam Datang</th>
                                    <th class="px-6 py-4 bg-blue-50">Loket</th>
                                    <th class="px-6 py-4 bg-blue-50 text-center">Status</th>
                                </tr>
                            </thead>
                            
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(q, index) in queues" :key="q.id" class="hover:bg-blue-50/50 transition duration-150">
                                    <td class="px-6 py-4 font-bold text-gray-500">{{ index + 1 }}</td>
                                    <td class="px-6 py-4"><span class="bg-yellow-400 text-blue-900 font-bold px-3 py-1 rounded shadow-sm">{{ q.ticket_code }}</span></td>
                                    <td class="px-6 py-4 font-bold text-gray-800">{{ q.guest_name }}</td>
                                    <td class="px-6 py-4 font-mono text-gray-600">{{ q.identity_number }}</td>
                                    <td class="px-6 py-4 text-gray-600">{{ q.phone_number }}</td>
                                    <td class="px-6 py-4 font-bold">{{ new Date(q.created_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'}) }}</td>
                                    
                                    <td class="px-6 py-4">
                                        <span v-if="q.counter" class="bg-blue-100 text-blue-800 border border-blue-200 px-3 py-1 rounded-full font-bold text-xs whitespace-nowrap shadow-sm">
                                            {{ q.counter.name }}
                                        </span>
                                        <span v-else class="text-gray-300 text-center block">-</span>
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        <span :class="getStatusBadge(q.status)" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide shadow-sm">{{ q.status }}</span>
                                    </td>
                                </tr>
                                <tr v-if="queues.length === 0">
                                    <td colspan="8" class="p-8 text-center text-gray-400 italic bg-gray-50">Belum ada antrian hari ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

    </DisplayLayout>
</template>