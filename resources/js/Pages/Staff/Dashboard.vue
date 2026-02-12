<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import axios from 'axios'
import DisplayLayout from '@/Layouts/DisplayLayout.vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';
import { supabase } from '@/utils/supabase'; 
import { Menu, MenuButton, MenuItems, MenuItem, Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue';
import { 
    Bars3Icon, 
    ArrowPathIcon, 
    ArrowDownTrayIcon, 
    PowerIcon,
    UsersIcon, 
    CheckCircleIcon, 
    ClockIcon 
} from '@heroicons/vue/24/solid';

const props = defineProps({
  counter: Object,
  currentServing: Object,
  waitingList: Array,
  stats: Object,
  auth: Object
})

const localServing = ref(props.currentServing)
const waitingList = ref(props.waitingList || [])
const stats = ref(props.stats || { total: 0, finished: 0 })
const localWaiting = ref(props.waitingList ? props.waitingList.length : 0)

let realtimeChannel = null;

// --- STATE LOADING TOMBOL (Anti-Spam) ---
const processingAction = ref(null); 

// --- LOGIC EXPORT CUSTOM ---
const showExportModal = ref(false);
const exportConfig = ref({
    type: 'daily',
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

    // Download via Browser
    window.location.href = `/admin/export?${params.toString()}`;
    
    closeExportModal();
};

// --- DATA SYNC & REALTIME ---
const fetchUpdates = async () => {
  try {
      const res = await axios.get(`/staff/${props.counter.id}/stats`);
      
      localServing.value = res.data.currentServing;
      if (res.data.waitingList) {
          waitingList.value = res.data.waitingList;
          localWaiting.value = res.data.waitingList.length;
      } else {
          waitingList.value = [];
          localWaiting.value = 0;
      }

      if (res.data.stats) {
        stats.value = res.data.stats;
      }

  } catch (e) {
      console.error("Gagal sync data", e);
  }
}

const setupRealtime = () => {
    realtimeChannel = supabase
        .channel('staff-realtime')
        .on('postgres_changes', { event: '*', schema: 'public', table: 'queues' }, () => {
            console.log("⚡ Data berubah, menyinkronkan...");
            fetchUpdates();
        })
        .subscribe();
};

onMounted(() => {
  fetchUpdates(); 
  setupRealtime(); 
})

onUnmounted(() => { 
    if (realtimeChannel) supabase.removeChannel(realtimeChannel);
})

// --- TOMBOL AKSI ---
const handleAction = async (actionName, url, payload = {}) => {
    if (processingAction.value) return; 
    processingAction.value = actionName;

    try {
        await axios.post(url, { ...payload, counter_id: props.counter.id });
    } catch (e) {
        console.error(`Gagal aksi ${actionName}:`, e);
        alert("Gagal melakukan aksi. Cek koneksi internet.");
    } finally {
        setTimeout(() => {
            processingAction.value = null;
        }, 500);
    } 
};

// Wrapper Functions
const callNext = () => handleAction('next', '/staff/call-next');
const recall = () => handleAction('recall', '/staff/recall');

const skip = () => {
    if (!localServing.value) return;
    const tempId = localServing.value.id;
    localServing.value = null; 
    handleAction('skip', '/staff/skip', { queue_id: tempId });
}

const complete = () => {
    if (!localServing.value) return;
    const tempId = localServing.value.id;
    localServing.value = null; 
    handleAction('complete', '/staff/complete', { queue_id: tempId });
}

// Navigasi
const inertiaIsLoading = ref(false);
document.addEventListener('inertia:start', () => (inertiaIsLoading.value = true));
document.addEventListener('inertia:finish', () => (inertiaIsLoading.value = false));

const changeCounter = () => router.get('/staff');
const logout = () => router.post('/logout');
</script>

<template>
  <DisplayLayout :title="`Loket ${counter.name}`">
    <LoadingOverlay :show="inertiaIsLoading" message="Memuat..." />
    
    <div class="dashboard-fit-container relative">
        
        <section class="header-section">
          <div>
            <h2 class="text-xl font-bold text-blue-900">Halo, {{ auth?.user?.name }}</h2>
            <p class="text-sm text-gray-500">Bertugas di: <strong>{{ counter.name }}</strong></p>
          </div>

          <div class="header-actions flex items-center gap-4">
              <div class="live-indicator">
                  <span class="dot"></span> Live (Realtime)
              </div>

              <Menu as="div" class="relative inline-block text-left z-50">
                <div>
                    <MenuButton class="inline-flex justify-center rounded-lg bg-blue-900 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none shadow-md items-center gap-2 border border-blue-700 transition active:scale-95">
                        <span class="hidden md:inline">Menu Staff</span>
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
                                <button @click="changeCounter" :class="[active ? 'bg-blue-50 text-blue-900' : 'text-gray-900', 'group flex w-full items-center rounded-md px-2 py-2 text-sm font-bold']">
                                    <ArrowPathIcon class="mr-2 h-5 w-5 text-blue-600" aria-hidden="true" />
                                    Ganti Loket
                                </button>
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
                                <button @click="logout" :class="[active ? 'bg-red-50 text-red-900' : 'text-gray-900', 'group flex w-full items-center rounded-md px-2 py-2 text-sm font-bold text-red-600']">
                                    <PowerIcon class="mr-2 h-5 w-5 text-red-600" aria-hidden="true" />
                                    Logout
                                </button>
                            </MenuItem>
                        </div>
                    </MenuItems>
                </transition>
              </Menu>
          </div>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-3 gap-6 flex-shrink-0">
            <div class="bg-blue-50 border-l-8 border-blue-500 p-6 rounded-xl shadow-sm hover:shadow-md transition">
                <div class="flex flex-col h-full justify-between">
                    <div class="mb-2"><UsersIcon class="w-12 h-12 text-blue-900" /></div>
                    <div>
                        <div class="text-5xl font-extrabold text-blue-900">{{ stats.total }}</div>
                        <div class="text-sm font-bold text-gray-600 mt-1">Total Antrian</div>
                    </div>
                </div>
            </div>
            <div class="bg-green-50 border-l-8 border-green-500 p-6 rounded-xl shadow-sm hover:shadow-md transition">
                <div class="flex flex-col h-full justify-between">
                    <div class="mb-2"><CheckCircleIcon class="w-12 h-12 text-green-800" /></div>
                    <div>
                        <div class="text-5xl font-extrabold text-green-800">{{ stats.finished }}</div>
                        <div class="text-sm font-bold text-gray-600 mt-1">Selesai</div>
                    </div>
                </div>
            </div>
            <div class="bg-orange-50 border-l-8 border-orange-400 p-6 rounded-xl shadow-sm hover:shadow-md transition">
                <div class="flex flex-col h-full justify-between">
                    <div class="mb-2"><ClockIcon class="w-12 h-12 text-orange-600" /></div>
                    <div>
                        <div class="text-5xl font-extrabold text-orange-600">{{ localWaiting }}</div>
                        <div class="text-sm font-bold text-gray-600 mt-1">Menunggu</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="workspace">
            
            <div class="panel serving-panel">
                <div class="panel-title">SEDANG MELAYANI</div>
                
                <div class="serving-body">
                    <div v-if="localServing" class="w-full flex flex-col items-center gap-4">
                        <div class="ticket-display animate-pop">{{ localServing.ticket_code }}</div>
                        <span class="status-badge active">STATUS: DIPANGGIL</span>
                        <div class="guest-info">
                            <div class="info-col"><small>Nama</small><p>{{ localServing.guest_name }}</p></div>
                            <div class="info-col border-x"><small>NRP / ID</small><p>{{ localServing.identity_number }}</p></div>
                            <div class="info-col"><small>Perihal</small><p class="capitalize">{{ localServing.purpose }}</p></div>
                        </div>
                    </div>
                    <div v-else class="empty-serving">
                        <div class="ticket-placeholder">---</div>
                        <span class="status-badge idle">MENUNGGU</span>
                        <p class="text-sm text-gray-400 mt-2">Silakan panggil antrian berikutnya</p>
                    </div>
                    
                    <div class="control-buttons">
                        <button class="btn-ctrl success" :disabled="!localServing || processingAction" @click="complete">
                            <span v-if="processingAction === 'complete'" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </span>
                            <span v-else>✔ Selesai</span>
                        </button>

                        <button class="btn-ctrl danger" :disabled="!localServing || processingAction" @click="skip">
                            <span v-if="processingAction === 'skip'" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </span>
                            <span v-else>🚫 Lewati</span>
                        </button>

                        <button class="btn-ctrl warning" :disabled="!localServing || processingAction" @click="recall">
                            <span v-if="processingAction === 'recall'" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </span>
                            <span v-else>🔔 Panggil Ulang</span>
                        </button>

                        <button class="btn-ctrl primary" :class="{ 'opacity-75 cursor-not-allowed': processingAction === 'next' }" :disabled="processingAction" @click="callNext">
                            <div v-if="processingAction === 'next'" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Memproses...</span>
                            </div>
                            <span v-else>▶ Panggil Berikutnya</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="panel list-panel">
                <div class="panel-title text-left pl-4 border-b flex justify-between pr-4">
                    <span>👤 Antrian Menunggu</span>
                    <span class="bg-orange-100 text-orange-600 px-2 rounded text-xs py-1">{{ waitingList.length }}</span>
                </div>
                <div class="list-scroll">
                    <div v-if="waitingList.length > 0" class="queue-list">
                        <div v-for="(item, index) in waitingList" :key="item.id" class="queue-card" :class="{'top-card': index === 0}">
                            <div class="q-header"><span class="q-number">{{ item.ticket_code }}</span><span class="q-service">{{ item.purpose || 'Umum' }}</span></div>
                            <div class="q-body"><p class="q-name">{{ item.guest_name }}</p><p class="q-id">NRP: {{ item.identity_number }}</p></div>
                        </div>
                    </div>
                    <div v-else class="empty-state"><div class="text-4xl mb-2">☕</div><p>Tidak ada antrian menunggu</p></div>
                </div>
            </div>

        </section>

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

<style src="/public/css/staff.css"></style>