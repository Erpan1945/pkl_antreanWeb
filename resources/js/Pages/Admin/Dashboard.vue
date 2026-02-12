<script setup>
import { onMounted, onUnmounted, ref, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { supabase } from '@/utils/supabase'; 
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler } from 'chart.js';
import { Line, Bar } from 'vue-chartjs';
import DisplayLayout from '@/Layouts/DisplayLayout.vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';
import { Menu, MenuButton, MenuItems, MenuItem, Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue';

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
    PowerIcon,
    ArrowPathIcon 
} from '@heroicons/vue/24/solid';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler);

const props = defineProps({
    stats: Object,
    chart_hourly: Object,
    chart_status: Object,
    queues: Array,
    current_date: String,
    auth: Object 
});

// --- CHART CONFIG ---
const lineChartData = computed(() => ({
    labels: props.chart_hourly.labels,
    datasets: [{
        label: 'Antrian',
        borderColor: '#1e3a8a', 
        backgroundColor: 'rgba(30, 58, 138, 0.1)',
        data: props.chart_hourly.data,
        fill: true,
        tension: 0.4
    }]
}));

const barChartData = computed(() => ({
    labels: props.chart_status.labels,
    datasets: [{
        label: 'Jumlah',
        backgroundColor: ['#FBBF24', '#3B82F6', '#10B981', '#EF4444'], 
        data: props.chart_status.data,
        borderRadius: 4
    }]
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: false, 
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { borderDash: [2, 4] } } }
};

// --- LOGIC EXPORT CUSTOM ---
const showExportModal = ref(false);
const exportConfig = ref({
    type: 'daily', // daily, monthly, yearly
    date: new Date().toISOString().split('T')[0],
    month: new Date().getMonth() + 1,
    year: new Date().getFullYear()
});

const openExportModal = () => {
    showExportModal.value = true;
};

const closeExportModal = () => {
    showExportModal.value = false;
};

const processExport = () => {
    // Bangun URL dengan Query Params
    const params = new URLSearchParams();
    params.append('type', exportConfig.value.type);

    if (exportConfig.value.type === 'daily') {
        params.append('date', exportConfig.value.date);
    } else if (exportConfig.value.type === 'monthly') {
        params.append('month', exportConfig.value.month);
        params.append('year', exportConfig.value.year);
    } else if (exportConfig.value.type === 'yearly') {
        params.append('year', exportConfig.value.year);
    }

    // Trigger download via browser 
    window.location.href = `/admin/export?${params.toString()}`;
    
    closeExportModal();
};

// --- REALTIME & LOADING LOGIC ---
let realtimeChannel = null;
const inertiaIsLoading = ref(false);        
const isBackgroundRefreshing = ref(false);  

// Listener Inertia Global
document.addEventListener('inertia:start', (event) => {
    if (!isBackgroundRefreshing.value) {
        inertiaIsLoading.value = true;
    }
});

document.addEventListener('inertia:finish', () => {
    inertiaIsLoading.value = false;
});

// Fungsi Debounce
const debounce = (func, wait) => {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
};

const refreshData = debounce(() => {
    console.log("⚡ Refreshing Admin Data (Background Mode)...");
    isBackgroundRefreshing.value = true;

    router.reload({
        only: ['stats', 'chart_hourly', 'chart_status', 'queues'],
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isBackgroundRefreshing.value = false;
        }
    });
}, 1000); 

onMounted(() => {
    // Setup Realtime Listener
    realtimeChannel = supabase
        .channel('admin-dashboard')
        .on('postgres_changes', { event: '*', schema: 'public', table: 'queues' }, (payload) => {
            console.log("🔔 REALTIME EVENT DITERIMA:", payload); // Log event masuk
            refreshData();
        })
        .subscribe((status) => {
            console.log("🔌 Status Koneksi Realtime:", status); // Log status koneksi (SUBSCRIBED / CHANNEL_ERROR)
        });
});

onUnmounted(() => {
    if (realtimeChannel) supabase.removeChannel(realtimeChannel);
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
    <DisplayLayout title="Dashboard Admin">
        <LoadingOverlay :show="inertiaIsLoading" message="Memuat halaman..." />
        
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
                        <MenuItems class="absolute right-0 mt-2 w-56 origin-top-right divide-y divide-gray-100 rounded-md bg-white shadow-xl ring-1 ring-black/5 focus:outline-none border border-gray-100 z-50">
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
                                    <button 
                                        @click="openExportModal" 
                                        :class="[active ? 'bg-yellow-50 text-yellow-900' : 'text-gray-900', 'group flex w-full items-center rounded-md px-2 py-2 text-sm font-bold']"
                                    >
                                        <ArrowDownTrayIcon class="mr-2 h-5 w-5 text-yellow-600" aria-hidden="true" />
                                        Export Laporan
                                    </button>
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
                        
                        <div class="flex items-center gap-4 mt-1">
                            <div class="flex items-center gap-2 text-gray-500">
                                <CalendarDaysIcon class="w-5 h-5" />
                                <span>{{ current_date }}</span>
                                <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full font-bold ml-2 animate-pulse">● Live</span>
                            </div>

                            <div v-if="isBackgroundRefreshing" class="flex items-center gap-2 text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded-md animate-pulse transition-all">
                                <ArrowPathIcon class="w-3.5 h-3.5 animate-spin" />
                                Menyinkronkan data...
                            </div>
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
                        <div class="h-64"><Line :data="lineChartData" :options="chartOptions" /></div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border-2 border-blue-900">
                        <h3 class="text-lg font-bold text-blue-900 mb-4">Statistik Status Antrian</h3>
                        <div class="h-64"><Bar :data="barChartData" :options="chartOptions" /></div>
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
                                    <th class="px-6 py-4 bg-blue-50">Perihal</th> 
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
                                    <td class="px-6 py-4 text-gray-600 capitalize">{{ q.purpose || '-' }}</td> 
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
                                    <td colspan="9" class="p-8 text-center text-gray-400 italic bg-gray-50">Belum ada antrian hari ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <TransitionRoot appear :show="showExportModal" as="template">
            <Dialog as="div" @close="closeExportModal" class="relative z-[60]">
                <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-black/25 backdrop-blur-sm" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center">
                        <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100" leave="duration-200 ease-in" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
                            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all border-t-8 border-yellow-500">
                                <DialogTitle as="h3" class="text-lg font-bold leading-6 text-gray-900 mb-4">
                                    Pilih Periode Export
                                </DialogTitle>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Laporan</label>
                                        <select v-model="exportConfig.type" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                            <option value="daily">Harian (Per Tanggal)</option>
                                            <option value="monthly">Bulanan (Per Bulan)</option>
                                            <option value="yearly">Tahunan (Per Tahun)</option>
                                        </select>
                                    </div>

                                    <div v-if="exportConfig.type === 'daily'">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Tanggal</label>
                                        <input type="date" v-model="exportConfig.date" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                    </div>

                                    <div v-if="exportConfig.type === 'monthly'" class="flex gap-2">
                                        <div class="w-1/2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                                            <select v-model="exportConfig.month" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                                <option v-for="n in 12" :key="n" :value="n">{{ new Date(0, n-1).toLocaleString('id-ID', {month:'long'}) }}</option>
                                            </select>
                                        </div>
                                        <div class="w-1/2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                                            <input type="number" v-model="exportConfig.year" min="2020" max="2030" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                        </div>
                                    </div>

                                    <div v-if="exportConfig.type === 'yearly'">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                                        <input type="number" v-model="exportConfig.year" min="2020" max="2030" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-end gap-3">
                                    <button type="button" class="inline-flex justify-center rounded-md border border-transparent bg-gray-100 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 focus:outline-none" @click="closeExportModal">
                                        Batal
                                    </button>
                                    <button type="button" class="inline-flex justify-center rounded-md border border-transparent bg-yellow-500 px-4 py-2 text-sm font-bold text-white hover:bg-yellow-600 focus:outline-none" @click="processExport">
                                        <ArrowDownTrayIcon class="w-5 h-5 mr-2"/>
                                        Download Excel
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

    </DisplayLayout>
</template>