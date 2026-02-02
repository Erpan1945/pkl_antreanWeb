<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import DisplayLayout from '@/Layouts/DisplayLayout.vue';
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';
import { 
    Bars3Icon, 
    ArrowPathIcon, 
    ArrowDownTrayIcon, 
    PowerIcon,
    // IMPORT ICON BARU (HEROICONS)
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
const isLoading = ref(false)
const localWaiting = ref(props.waitingList ? props.waitingList.length : 0)

let pollingInterval = null

// --- POLLING REALTIME ---
const fetchUpdates = async () => {
  try {
      const time = new Date().getTime();
      const url = `/staff/${props.counter.id}/stats?t=${time}`; // /staff/stats/${props.counter.id}?t=${time} (jika url routenya ini, tidak bisa realtime refresh)
      const res = await axios.get(url);
      
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
      console.error("Gagal polling data: Pastikan Route /staff/{id}/stats ada di web.php", e);
  }
}

onMounted(() => {
  fetchUpdates();
  pollingInterval = setInterval(() => {
      fetchUpdates();
  }, 3000);
})

onUnmounted(() => { if (pollingInterval) clearInterval(pollingInterval); })

// --- TOMBOL AKSI ---
const handleAction = async (url, payload = {}) => {
    if (isLoading.value) return; 
    isLoading.value = true;
    try {
        await axios.post(url, { ...payload, counter_id: props.counter.id });
        await fetchUpdates(); 
    } catch (e) {
        alert("Gagal memproses aksi. Cek koneksi.");
    } finally {
        isLoading.value = false;
    }
}

const callNext = () => handleAction('/staff/call-next')
const recall = () => handleAction('/staff/recall')


const skip = () => {
    if (!localServing.value) return;
    const tempId = localServing.value.id;
    localServing.value = null; // Optimis UI
    handleAction('/staff/skip', { queue_id: tempId });
}

const complete = () => {
    if (!localServing.value) return;
    const tempId = localServing.value.id;
    localServing.value = null; 
    handleAction('/staff/complete', { queue_id: tempId });
}

// Navigasi Menu
const changeCounter = () => router.get('/staff');
const logout = () => router.post('/logout');
</script>

<template>
  <DisplayLayout :title="`Loket ${counter.name}`">
    
    <div class="dashboard-fit-container relative">
        
        <section class="header-section">
          <div>
            <h2 class="text-xl font-bold text-blue-900">Halo, {{ auth?.user?.name }}</h2>
            <p class="text-sm text-gray-500">Bertugas di: <strong>{{ counter.name }}</strong></p>
          </div>

          <div class="header-actions flex items-center gap-4">
              <div class="live-indicator">
                  <span class="dot"></span> Live
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
                                <a href="/admin/export" target="_blank" :class="[active ? 'bg-yellow-50 text-yellow-900' : 'text-gray-900', 'group flex w-full items-center rounded-md px-2 py-2 text-sm font-bold']">
                                    <ArrowDownTrayIcon class="mr-2 h-5 w-5 text-yellow-600" aria-hidden="true" />
                                    Export Excel Harian
                                </a>
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
                        <div class="text-5xl font-extrabold text-green-800">{{ stats.finished }}</div>
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
                            <div class="info-col"><small>Perihal</small><p>{{ localServing.purpose }}</p></div>
                        </div>
                    </div>
                    <div v-else class="empty-serving">
                        <div class="ticket-placeholder">---</div>
                        <span class="status-badge idle">MENUNGGU</span>
                        <p class="text-sm text-gray-400 mt-2">Silakan panggil antrian berikutnya</p>
                    </div>
                    <div class="control-buttons">
                        <button class="btn-ctrl success" :disabled="!localServing || isLoading" @click="complete">✔ Selesai</button>
                        <button class="btn-ctrl danger" :disabled="!localServing || isLoading" @click="skip">🚫 Lewati</button>
                        <button class="btn-ctrl warning" :disabled="!localServing || isLoading" @click="recall">🔔 Panggil Ulang</button>
                        <button class="btn-ctrl primary" :disabled="isLoading" @click="callNext">
                            <span v-if="isLoading">⏳...</span><span v-else>▶ Panggil Berikutnya</span>
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
                            <div class="q-body"><p class="q-name">{{ item.guest_name }}</p><p class="q-id">ID: {{ item.identity_number }}</p></div>
                        </div>
                    </div>
                    <div v-else class="empty-state"><div class="text-4xl mb-2">☕</div><p>Tidak ada antrian menunggu</p></div>
                </div>
            </div>

        </section>

    </div>
  </DisplayLayout>
</template>

<style src="/public/css/staff.css"></style>