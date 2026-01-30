<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import DisplayLayout from '@/Layouts/DisplayLayout.vue';

// PROPS (Data Awal dari Controller)
const props = defineProps({
  counter: Object,
  currentServing: Object,
  waitingList: Array,
  stats: Object,
  auth: Object
})

// STATE REAKTIF
const localServing = ref(props.currentServing)
const waitingList = ref(props.waitingList || [])
const stats = ref(props.stats || { total: 0, finished: 0 })
const isLoading = ref(false) // State loading tombol

let pollingInterval = null

// --- FUNGSI UPDATE REALTIME (POLLING) ---
const fetchUpdates = async () => {
  try {
      // PERBAIKAN: Tambahkan timestamp (?t=...) agar browser TIDAK MENYIMPAN CACHE
      const timestamp = new Date().getTime();
      const url = `/staff/${props.counter.id}/stats?t=${timestamp}`;
      
      const res = await axios.get(url);
      
      // Update Data Tampilan
      localServing.value = res.data.currentServing;
      waitingList.value = res.data.waitingList || [];
      stats.value = res.data.stats;

      // Debugging di Console (Cek tekan F12 > Console jika data tidak muncul)
      console.log('Realtime Update:', new Date().toLocaleTimeString());

  } catch (e) {
      console.error("Gagal polling data:", e);
  }
}

// --- LIFECYCLE ---
onMounted(() => {
  // 1. Ambil data segera saat halaman dimuat
  fetchUpdates();

  // 2. Jalankan Polling setiap 3 detik (3000ms)
  pollingInterval = setInterval(fetchUpdates, 3000);
})

onUnmounted(() => {
  // Bersihkan interval saat keluar halaman agar tidak memberatkan browser
  if (pollingInterval) clearInterval(pollingInterval);
})

// --- TOMBOL AKSI (TANPA RELOAD HALAMAN) ---

const handleAction = async (url, payload = {}) => {
    if (isLoading.value) return; // Cegah klik ganda
    isLoading.value = true;

    try {
        await axios.post(url, { ...payload, counter_id: props.counter.id });
        
        // PENTING: Langsung tarik data terbaru setelah aksi berhasil
        await fetchUpdates(); 
        
    } catch (e) {
        alert("Gagal memproses aksi. Periksa koneksi internet.");
    } finally {
        isLoading.value = false;
    }
}

// Wrapper Functions
const callNext = () => handleAction('/staff/call-next')
const recall = () => handleAction('/staff/recall')
const complete = () => {
    if (!localServing.value) return;
    // Optimis: Kosongkan layar dulu biar terasa cepat
    const tempId = localServing.value.id;
    localServing.value = null; 
    
    handleAction('/staff/complete', { queue_id: tempId });
}

// Navigasi Biasa
const changeCounter = () => router.get('/staff');
const logout = () => router.post('/logout');
</script>

<template>
  <DisplayLayout :title="`Loket ${counter.name}`">
    
    <div class="dashboard-fit-container">
        
        <section class="header-section">
          <div>
            <h2 class="text-xl font-bold text-blue-900">Halo, {{ auth?.user?.name }}</h2>
            <p class="text-sm text-gray-500">Bertugas di: <strong>{{ counter.name }}</strong></p>
          </div>
          <div class="header-actions">
              <div class="live-indicator">
                  <span class="dot"></span> Live
              </div>
              <button class="btn-text" @click="changeCounter">🔄 Ganti Loket</button>
              <button class="btn-logout" @click="logout">Logout</button>
          </div>
        </section>

        <section class="stats-grid">
          <div class="stat-card blue">
            <span class="icon">👥</span>
            <div>
                <h3>{{ stats.total }}</h3>
                <p>Total</p>
            </div>
          </div>
          <div class="stat-card green">
            <span class="icon">📈</span>
            <div>
                <h3>{{ stats.finished }}</h3>
                <p>Selesai</p>
            </div>
          </div>
          <div class="stat-card orange">
            <span class="icon">⏳</span>
            <div>
                <h3>{{ waitingList.length }}</h3>
                <p>Menunggu</p>
            </div>
          </div>
        </section>

        <section class="workspace">
            
            <div class="panel serving-panel">
                <div class="panel-title">SEDANG MELAYANI</div>
                
                <div class="serving-body">
                    <div v-if="localServing" class="w-full flex flex-col items-center gap-4">
                        <div class="ticket-display animate-pop">
                            {{ localServing.ticket_code }}
                        </div>

                        <span class="status-badge active">STATUS: DIPANGGIL</span>

                        <div class="guest-info">
                            <div class="info-col">
                                <small>Nama</small>
                                <p>{{ localServing.guest_name }}</p>
                            </div>
                            <div class="info-col border-x">
                                <small>NRP / ID</small>
                                <p>{{ localServing.identity_number }}</p>
                            </div>
                            <div class="info-col">
                                <small>Perihal</small>
                                <p>{{ localServing.purpose }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="empty-serving">
                        <div class="ticket-placeholder">---</div>
                        <span class="status-badge idle">MENUNGGU</span>
                        <p class="text-sm text-gray-400 mt-2">Silakan panggil antrian berikutnya</p>
                    </div>

                    <div class="control-buttons">
                        <button class="btn-ctrl success" :disabled="!localServing || isLoading" @click="complete">
                            ✔ Selesai
                        </button>
                        <button class="btn-ctrl warning" :disabled="!localServing || isLoading" @click="recall">
                            🔔 Panggil Ulang
                        </button>
                        <button class="btn-ctrl primary" :disabled="isLoading" @click="callNext">
                            <span v-if="isLoading">⏳ Memproses...</span>
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
                            <div class="q-header">
                                <span class="q-number">{{ item.ticket_code }}</span>
                                <span class="q-service">{{ item.purpose || 'Umum' }}</span>
                            </div>
                            <div class="q-body">
                                <p class="q-name">{{ item.guest_name }}</p>
                                <p class="q-id">ID: {{ item.identity_number }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="empty-state">
                        <div class="text-4xl mb-2">☕</div>
                        <p>Tidak ada antrian menunggu</p>
                    </div>
                </div>
            </div>

        </section>

    </div>
  </DisplayLayout>
</template>

<style src="/public/css/staff.css"></style>