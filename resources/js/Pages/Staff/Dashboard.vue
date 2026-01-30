<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  counter: Object,
  currentServing: Object,
  waitingCount: Number,
  waitingList: Array,
  stats: Object
})

const localServing = ref(props.currentServing)
const localWaiting = ref(props.waitingCount)
const waitingList = ref(props.waitingList)
const stats = ref(props.stats)

const clock = ref('00:00:00 WIB')
let interval = null

// JAM WIB
const updateClock = () => {
  const now = new Date()
  const utc = now.getTime() + now.getTimezoneOffset() * 60000
  const wib = new Date(utc + 7 * 60 * 60000)
  clock.value = wib.toLocaleTimeString('id-ID') + ' WIB'
}

// FETCH DATA REALTIME
const fetchUpdates = async () => {
  const res = await axios.get(`/staff/stats/${props.counter.id}`)
  localServing.value = res.data.currentServing
  localWaiting.value = res.data.waitingCount
  waitingList.value = res.data.waitingList
  stats.value = res.data.stats
}

onMounted(() => {
  updateClock()
  interval = setInterval(() => {
    updateClock()
    fetchUpdates()
  }, 3000)
})

onUnmounted(() => clearInterval(interval))

// ACTIONS
const callNext = () => {
  router.post('/staff/next', {}, {
    onSuccess: () => fetchUpdates()
  })
}

const recall = () => {
  fetchUpdates()
}

const complete = () => {
  if (!localServing.value) return

  router.post('/staff/complete', {
    queue_id: localServing.value.id
  }, {
    onSuccess: () => {
      localServing.value = null
      fetchUpdates()
    }
  })
}
</script>

<template>
  <Head title="Admin Loket Antrean - PT ASABRI">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap"
    rel="stylesheet"
  >
</Head>


  <!-- HEADER -->
  <header class="topbar">
    <div class="brand">
      <div class="logo-box">
        <img src="/images/logo-asabri.png" alt="ASABRI" />
      </div>
      <div>
        <h1>Kancab PT ASABRI (Persero) Malang</h1>
        <p>Ruko Raden Intan Square Blok 1-2</p>
        <p>Kota Malang, Jawa Timur</p>
      </div>
    </div>
    <div class="clock">{{ clock }}</div>
  </header>

  <!-- GREETING -->
  <section class="greeting">
    <h2>Halo, Petugas Loket</h2>
    <p>Selamat bekerja 👋</p>
    <span class="badge">Sisa Antrian: {{ localWaiting }}</span>
  </section>

  <!-- CARDS -->
  <section class="cards">
    <div class="card blue">
      <div class="card-icon blue">👥</div>
      <h3>{{ stats.total }}</h3>
      <p>Total Antrian</p>
    </div>

    <div class="card green">
      <div class="card-icon green">📈</div>
      <h3>{{ stats.finished }}</h3>
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

  <!-- MAIN -->
  <section class="main">
    <!-- CURRENT -->
    <div class="current">
      <h4>SEDANG MELAYANI</h4>

      <div class="queue-number">
        {{ localServing?.ticket_code ?? '-' }}
      </div>

      <span class="status">
        Status: {{ localServing ? localServing.status.toUpperCase() : '-' }}
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
  class="btn success"
  :disabled="!localServing"
  @click="complete"
>
  ✔ Selesai
</button>


        <button class="btn warning" @click="recall">
          🔔 Panggil Ulang
        </button>

        <button class="btn primary" @click="callNext">
          ▶ Panggil Berikutnya
        </button>
      </div>
    </div>

    <!-- WAITING -->
    <div class="waiting">
      <h4>ANTRIAN MENUNGGU</h4>

      <div
        v-for="item in waitingList"
        :key="item.id"
        class="wait-item"
      >
        <strong>{{ item.ticket_code }}</strong>
        <p>{{ item.guest_name }}</p>
        <small>{{ item.purpose }}</small>
      </div>

      <p v-if="waitingList.length === 0" style="text-align:center;color:#777;">
        Tidak ada antrian menunggu
      </p>
    </div>
    <!-- FOOTER -->
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
