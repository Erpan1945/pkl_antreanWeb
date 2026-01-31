<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'

// Menerima data dari StaffController::dashboard()
const props = defineProps({
  counter: Object,        // Data Loket (ID, Nama)
  currentServing: Object, // Data Antrian yg sedang dipanggil
  waitingCount: Number,   // Jumlah sisa antrian
  auth: Object            // Data User Login
})

// State Lokal untuk UI Realtime
const localServing = ref(props.currentServing)
const localWaiting = ref(props.waitingCount)
// Tambahan: Stats Total & Selesai (Nanti diambil dari API getStats)
const stats = ref({ total: 0, finished: 0 }) 
const waitingList = ref([]) // Nanti diambil dari API jika perlu

const clock = ref('00:00:00 WIB')
let interval = null

// JAM WIB
const updateClock = () => {
  const now = new Date()
  const utc = now.getTime() + now.getTimezoneOffset() * 60000
  const wib = new Date(utc + 7 * 60 * 60000)
  clock.value = wib.toLocaleTimeString('id-ID') + ' WIB'
}

// FETCH DATA REALTIME (Polling ke StaffController::getStats)
const fetchUpdates = async () => {
  try {
    // Panggil Route: /staff/{counterId}/stats
    const res = await axios.get(route('staff.stats', props.counter.id))
    
    // Update State Lokal dari Response Controller
    localServing.value = res.data.currentServing
    localWaiting.value = res.data.waitingCount
    
    // Note: StaffController::getStats Anda saat ini belum mengembalikan 'waitingList' & 'stats' lengkap.
    // Jadi untuk sementara kita pakai data yang ada dulu, atau Anda bisa update Controller nanti.
    // Jika controller sudah diupdate, bisa uncomment baris bawah:
    // waitingList.value = res.data.waitingList || []
    // stats.value = res.data.stats || { total: 0, finished: 0 }

  } catch (error) {
    console.error("Gagal update data:", error)
  }
}

onMounted(() => {
  updateClock()
  // Interval polling tiap 3 detik
  interval = setInterval(() => {
    updateClock()
    fetchUpdates()
  }, 3000)
})

onUnmounted(() => clearInterval(interval))

// --- ACTIONS (Terhubung ke Route StaffController) ---

const callNext = () => {
  // Post ke: /staff/call-next
  router.post(route('staff.callNext'), {
    counter_id: props.counter.id // Wajib kirim ID Loket
  }, {
    preserveScroll: true,
    onSuccess: () => fetchUpdates()
  })
}

const recall = () => {
  // Post ke: /staff/recall
  router.post(route('staff.recall'), {
    counter_id: props.counter.id // Wajib kirim ID Loket
  }, {
    preserveScroll: true,
    onSuccess: () => fetchUpdates()
  })
}

const complete = () => {
  if (!localServing.value) return

  // Post ke: /staff/complete
  router.post(route('staff.complete'), {
    queue_id: localServing.value.id // Wajib kirim ID Antrian
  }, {
    preserveScroll: true,
    onSuccess: () => {
      localServing.value = null
      fetchUpdates()
    }
  })
}
</script>

<template>
  <Head :title="`Loket ${counter.name} - Admin Antrean`">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap"
      rel="stylesheet"
    >
  </Head>

  <header class="topbar">
    <div class="brand">
      <div class="logo-box">
        <img src="/images/logo-asabri.png" alt="ASABRI" />
      </div>
      <div>
        <h1>Kancab PT ASABRI (Persero) Malang</h1>
        <p>Ruko Raden Intan Square Blok 1-2, Jl. Raden Intan Kav.74</p>
        <p>Kel. Arjosari, Kec. Blimbing, Kota Malang, Jawa Timur 65126</p>
      </div>
    </div>
    <div class="clock">{{ clock }}</div>
  </header>

  <section class="greeting">
    <h2>Halo, {{ auth.user.name }}</h2>
    <p>Bertugas di: <strong>{{ counter.name }}</strong> 👋</p>
    <span class="badge">Sisa Antrian: {{ localWaiting }}</span>
  </section>

  <section class="cards">
    <div class="card blue">
      <div class="card-icon blue">👥</div>
      <h3>{{ stats.total || '-' }}</h3>
      <p>Total Antrian</p>
    </div>

    <div class="card green">
      <div class="card-icon green">📈</div>
      <h3>{{ stats.finished || '-' }}</h3>
      <p>Selesai</p>
    </div>

    <div class="card orange">
      <div class="card-icon orange">⏳</div>
      <h3>{{ localWaiting }}</h3>
      <p>Menunggu</p>
    </div>

    <div class="card purple">
      <div class="card-icon purple">⏱️</div>
      <h3>-</h3>
      <p>Rata-rata Waktu</p>
    </div>
  </section>

  <section class="main">
    <div class="current">
      <h4>SEDANG MELAYANI</h4>

      <div class="queue-number">
        {{ localServing?.ticket_code ?? '-' }}
      </div>

      <span class="status">
        Status: {{ localServing ? localServing.status.toUpperCase() : 'MENUNGGU' }}
      </span>

      <div class="info">
        <div>
          <small>Nama</small>
          <p>{{ localServing?.guest_name ?? '-' }}</p>
        </div>
        <div>
          <small>Identitas</small>
          <p>{{ localServing?.identity_number ?? '-' }}</p>
        </div>
        <div>
          <small>Perihal</small>
          <p>{{ localServing?.purpose ?? '-' }}</p>
        </div>
      </div>

      <div class="actions">
        <button
<<<<<<< HEAD
          class="btn success"
          :disabled="!localServing"
          @click="complete"
        >
          ✔ Selesai
        </button>

        <button class="btn warning" :disabled="!localServing" @click="recall">
=======
  class="btn success"
  :disabled="!localServing"
  @click="complete"
>
  ✔ Selesai
</button>
        <button class="btn warning" @click="recall">
>>>>>>> 4b59f9d (feat: add skip queue button and staff dashboard updates)
          🔔 Panggil Ulang
        </button>

        <button class="btn primary" @click="callNext">
          ▶ Panggil Berikutnya
        </button>

          <button class="btn danger" :disabled="!localServing" @click="skip">
            ⏭ Lewati
          </button>
      </div>
    </div>

    <div class="waiting">
      <h4>ANTRIAN MENUNGGU</h4>
      
      <div v-if="waitingList.length > 0">
          <div
            v-for="item in waitingList"
            :key="item.id"
            class="wait-item"
          >
            <strong>{{ item.ticket_code }}</strong>
            <p>{{ item.guest_name }}</p>
            <small>{{ item.purpose }}</small>
          </div>
      </div>

      <p v-if="waitingList.length === 0" style="text-align:center;color:#777; margin-top: 20px; font-style: italic;">
              Tidak ada antrian menunggu
      </p>
    </div>

    <footer class="news-footer">
      <div class="ticker">
        <div class="ticker-track">
          <span v-for="i in 5" :key="i">
            PT ASABRI (Persero) | Melayani dengan Sepenuh Hati • Jam Layanan: Senin–Jumat 08.00–15.00 •
          </span>
        </div>
      </div>
    </footer>

  </section>
</template>

<style src="/public/css/staff.css"></style>