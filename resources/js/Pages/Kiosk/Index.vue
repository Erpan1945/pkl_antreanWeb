<script setup>
import { ref, nextTick, onMounted } from 'vue';
import axios from 'axios';
import DisplayLayout from '@/Layouts/DisplayLayout.vue'; 

const props = defineProps({ services: Array });
const printing = ref(false);
const ticketData = ref(null);

// Kita hanya butuh Service ID saja
const form = ref({
    service_id: '', 
});

onMounted(() => {
    if (props.services && props.services.length > 0) {
        form.value.service_id = props.services[0].id;
    }
});

const submitTicket = async () => {
    printing.value = true;

    try {
        // Kirim request minimal ke server
        const response = await axios.post('/kiosk/ticket', {
            service_id: form.value.service_id,
            guest_name: 'Tamu Umum', // Default jika database mewajibkan isi
            identity_number: '-',
            purpose: 'Layanan Mandiri'
        });
        
        ticketData.value = response.data;
        
        await nextTick(); 

        // Proses Cetak
        setTimeout(() => {
            window.print();
            
            setTimeout(() => {
                ticketData.value = null;
                printing.value = false;
            }, 4000); 
        }, 7000);

    } catch (error) {
        console.error(error);
        alert('Gagal mengambil antrean. Silakan coba lagi.');
        printing.value = false;
    }
};
</script>

<template>
    <DisplayLayout title="Ambil Antrean">
        
        <div class="h-full w-full flex flex-col items-center justify-center bg-[#f8f9fa] overflow-hidden p-6">
            
            <div class="text-center mb-10">
                <img src="/images/logo-asabri.png" alt="Logo" class="h-28 mx-auto mb-6">
                <h1 class="text-4xl font-black text-[#00569c] uppercase tracking-tighter">SELAMAT DATANG</h1>
                <p class="text-lg text-gray-500 font-bold uppercase tracking-[0.2em]">PT ASABRI (Persero) KC MALANG</p>
            </div>

            <div class="w-full max-w-lg">
                <button 
                    @click="submitTicket" 
                    :disabled="printing" 
                    class="w-full bg-white border-4 border-[#ffc107] rounded-[40px] p-12 shadow-2xl transition-all transform active:scale-95 group relative overflow-hidden"
                >
                    <div class="absolute inset-0 bg-[#ffc107] opacity-0 group-active:opacity-10 transition-opacity"></div>

                    <div v-if="!printing" class="flex flex-col items-center">
                        <div class="bg-[#00569c] p-6 rounded-full mb-6 shadow-lg">
                            <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                            </svg>
                        </div>
                        <span class="text-3xl font-black text-[#00569c] tracking-tight">AMBIL NOMOR ANTREAN</span>
                        <span class="text-sm text-gray-400 font-bold mt-2 uppercase tracking-widest">Sentuh di sini untuk mencetak</span>
                    </div>

                    <div v-else class="flex flex-col items-center animate-pulse">
                        <div class="h-16 w-16 border-8 border-[#00569c] border-t-transparent rounded-full animate-spin mb-4"></div>
                        <span class="text-2xl font-black text-[#00569c]">MENCETAK...</span>
                    </div>
                </button>
            </div>

            <p class="mt-12 text-gray-400 font-bold uppercase tracking-widest text-xs">Silakan ambil struk setelah dicetak</p>
        </div>

        <div v-if="printing" class="fixed inset-0 bg-[#00569c]/95 z-[60] flex items-center justify-center flex-col backdrop-blur-md">
            <div class="animate-bounce text-8xl mb-6">🖨️</div>
            <p class="text-4xl font-black text-white tracking-widest uppercase">Sedang Mencetak...</p>
            <p class="text-yellow-400 mt-4 text-xl font-bold">Harap tunggu sebentar</p>
        </div>

        <div v-if="ticketData" class="print-only hidden">
            <div class="ticket-container">
                <img src="/images/logo-asabri-dark.png" alt="Logo" class="ticket-logo" /> 
                <h2 class="instansi">PT ASABRI (Persero)</h2>
                <h3 class="cabang">KC MALANG</h3>
                <p class="date">{{ ticketData.date }}</p>
                <hr class="dashed" />
                <p class="label">NOMOR ANTRIAN</p>
                <h1 class="big-number">{{ ticketData.ticket.ticket_code }}</h1>
                <hr class="dashed" />
                <p class="footer-note">Mohon menunggu dengan tertib.</p>
                <p class="footer-note">Terima Kasih.</p>
            </div>
        </div>

    </DisplayLayout>
</template>

<style>
/* CSS KHUSUS PRINT (WAJIB SEPERTI INI) */
@media print {
    /* 1. RESET HALAMAN GLOBAL */
    html, body {
        width: 58mm; /* Ubah ke lebar thermal */
        margin: 0 !important;
        padding: 0 !important;
    }

    /* 2. SEMBUNYIKAN SEMUA KONTEN WEBSITE */
    body * {
        visibility: hidden;
    }

    /* 3. PAKSA TIKET TAMPIL & LEPAS DARI STRUKTUR HALAMAN */
    .print-only {
        position: fixed; 
        left: 0;
        top: 0;
        width: 100%;
        height: auto;
        visibility: visible !important;
        z-index: 9999; 
        background: white; 
        display: flex !important;
        justify-content: center;
        align-items: flex-start;
    }

    /* Pastikan isi tiket juga terlihat */
    .print-only * {
        visibility: visible !important;
    }

   /* 4. SETTING KERTAS (STRUK THERMAL 58mm) */
    .ticket-container {
        width: 100%;
        max-width: 58mm;
        margin: 0;
        padding: 2mm; /* Kecilkan padding agar tidak memakan banyak kertas */
        text-align: center;
        font-family: 'Courier New', Courier, monospace; 
        color: black;
    }

    .ticket-logo {
        /* 1. Gunakan lebar pasti agar tidak kekecilan (Sekitar 30-40mm) */
        width: 35mm; 
        height: auto;
        margin: 0 auto 2mm;
        display: block !important;
        
        /* 2. PAKSA browser menampilkan grafis (Kunci Utama) */
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        /* 3. Pastikan visibility benar-benar tembus */
        visibility: visible !important;
    }       

    /* Styling Teks Struk */
    .instansi { font-size: 12pt; font-weight: bold; margin-bottom: 2mm; text-transform: uppercase; margin-top: 1mm;}
    .date { font-size: 8pt; margin-bottom: 4mm; }
    .dashed { border-top: 1px dashed black !important; margin: 3mm 0; border-bottom: none; display: block; }
    
    .label { font-size: 10pt; margin-top: 2mm; }
    .big-number { font-size: 40pt; font-weight: 900; margin: 0; line-height: 1; }
    .service-name { font-size: 12pt; font-weight: bold; margin-bottom: 4mm; }

    .guest-info { 
        text-align: left; 
        font-size: 9pt; 
        margin: 4mm 0;
        width: 100%;
        font-weight: bold;
    }
    .guest-info p { margin: 1mm 0; }

    .footer-note { font-size: 8pt; margin-top: 4mm; font-style: italic; }

    /* 5. HAPUS MARGIN BROWSER & ATUR UKURAN KERTAS */
    @page {
        size: 58mm auto;
        margin: 0mm; 
    }
}
</style>