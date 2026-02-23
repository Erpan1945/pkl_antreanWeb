<script setup>
import { ref, nextTick, onMounted } from 'vue';
import axios from 'axios';
import DisplayLayout from '@/Layouts/DisplayLayout.vue'; 

const props = defineProps({ services: Array });
const printing = ref(false);
const ticketData = ref(null);

// Definisikan Opsi Dropdown di sini agar bisa dipakai untuk reset
const defaultPurpose = 'pengurusan pensiun'; 

const form = ref({
    service_id: '', 
    guest_name: '',
    identity_number: '',
    phone_number: '',
    purpose: defaultPurpose // Inisialisasi awal agar tidak null
});

// Daftar Opsi
const purposeOptions = [
    { value: 'pengurusan pensiun', label: 'Pengurusan Pensiun' },
    { value: 'pdth', label: 'PDTH' },
    { value: 'bppp', label: 'BPPP' },
    { value: 'bpi', label: 'BPI' },
    { value: 'bps', label: 'BPS' },
    { value: 'bpa', label: 'BPA' },
    { value: 'lainnya', label: 'Lainnya' },
];

onMounted(() => {
    // Set service ID pertama jika ada
    if (props.services && props.services.length > 0) {
        form.value.service_id = props.services[0].id;
    }
    // Pastikan purpose tidak kosong saat mount
    if (!form.value.purpose) {
        form.value.purpose = defaultPurpose;
    }
});

const submitTicket = async () => {
    // Validasi input
    if (!form.value.guest_name || !form.value.identity_number) {
        alert("Mohon lengkapi Nama dan NRP/Identitas.");
        return;
    }

    printing.value = true;

    try {
        const response = await axios.post('/kiosk/ticket', {
            guest_name: form.value.guest_name,
            identity_number: form.value.identity_number,
            phone_number: form.value.phone_number,
            purpose: form.value.purpose 
        });
        
        // Simpan data tiket dari server
        ticketData.value = response.data;
        
        // Tunggu Vue selesai render struk tiket ke HTML sebelum print
        await nextTick(); 

        // Reset form segera agar siap untuk orang berikutnya
        form.value.guest_name = '';
        form.value.identity_number = '';
        form.value.phone_number = '';
        form.value.purpose = defaultPurpose; 
        
        // Beri jeda sedikit agar CSS print ter-load sempurna
        setTimeout(() => {
            window.print();
            
            // Hilangkan data tiket setelah dialog print ditutup/selesai
            setTimeout(() => {
                ticketData.value = null;
                printing.value = false;
            }, 500); 
        }, 1000);

    } catch (error) {
        console.error(error);
        alert('Terjadi kesalahan sistem: ' + (error.response?.data?.message || error.message));
        printing.value = false;
    }
};
</script>

<template>
    <DisplayLayout title="Pendaftaran Mandiri">
        
        <div class="h-full w-full flex flex-col items-center justify-center bg-[#f8f9fa] overflow-hidden p-4">
            
            <div class="text-center mb-6 shrink-0">
                <h1 class="text-3xl font-black text-[#00569c] uppercase tracking-wide">PENDAFTARAN MANDIRI</h1>
                <p class="text-sm text-gray-500 font-bold">Silakan isi data diri Anda di bawah ini</p>
            </div>

            <div class="w-full max-w-xl bg-white border-4 border-[#ffc107] rounded-[25px] px-8 py-6 shadow-2xl relative flex flex-col justify-center">
                
                <h2 class="text-center text-[#00569c] font-black text-lg mb-4 uppercase tracking-wider border-b-2 border-gray-100 pb-2">
                    FORMULIR TAMU
                </h2>

                <div class="flex flex-col gap-5">
                    
                    <div>
                        <label class="flex items-center gap-2 text-xs font-bold text-[#00569c] mb-1 uppercase tracking-wide">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Nama Lengkap
                        </label>
                        <input v-model="form.guest_name" type="text" placeholder="Ketik nama..." class="w-full bg-gray-50 border-2 border-gray-200 rounded-xl p-3 font-bold text-sm">
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-xs font-bold text-[#00569c] mb-1 uppercase tracking-wide">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                            NRP / NIP / Identitas
                        </label>
                        <input v-model="form.identity_number" type="text" placeholder="Ketik NRP/Identitas..." class="w-full bg-gray-50 border-2 border-gray-200 rounded-xl p-3 font-bold text-sm">
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-xs font-bold text-[#00569c] mb-1 uppercase tracking-wide">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            Nomor Telepon
                        </label>
                        <input v-model="form.phone_number" type="text" placeholder="08xx..." class="w-full bg-gray-50 border-2 border-gray-200 rounded-xl p-3 font-bold text-sm">
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-xs font-bold text-[#00569c] mb-1 uppercase tracking-wide">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Keperluan
                        </label>
                        <select 
                            v-model="form.purpose" 
                            class="w-full bg-gray-50 border-2 border-gray-200 rounded-xl p-3 text-gray-800 focus:border-[#00569c] focus:outline-none font-bold text-sm appearance-none cursor-pointer"
                        >
                            <option v-for="option in purposeOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <div class="pt-2 mt-1">
                        <button @click="submitTicket" :disabled="printing" class="w-full bg-[#ffc107] hover:bg-yellow-400 text-[#00569c] font-black py-3.5 rounded-xl shadow-md border-b-4 border-yellow-600 active:border-b-0 active:translate-y-1 transition-all">
                            <span v-if="!printing" class="text-lg tracking-wider">CETAK TIKET ANTRIAN</span>
                            <span v-else class="text-lg tracking-wider animate-pulse">SEDANG MEMPROSES...</span>
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <div v-if="printing" class="fixed inset-0 bg-[#00569c]/90 z-[60] flex items-center justify-center flex-col backdrop-blur-md">
            <div class="animate-spin text-7xl mb-6 text-[#ffc107]">⏳</div>
            <p class="text-3xl font-black text-white tracking-widest uppercase">Sedang Mencetak Tiket...</p>
            <p class="text-white mt-4 font-bold">Silakan ambil struk Anda di mesin printer</p>
        </div>

        <div v-if="ticketData" class="print-only hidden">
            <div class="ticket-container">
                <img src="/images/logo-asabri-dark.png" alt="Logol ASABRI" class="ticket-logo" /> 
                <h2 class="instansi">PT ASABRI KC MALANG</h2>
                <p class="date">{{ ticketData.date }}</p>
                <hr class="dashed" />
                <p class="label">Nomor Antrian:</p>
                <h1 class="big-number">{{ ticketData.ticket.ticket_code }}</h1>
                <p class="service-name">{{ ticketData.service_name }}</p>
                <hr class="dashed" />
                <div class="guest-info">
                    <p><strong>Nama:</strong> {{ ticketData.ticket.guest_name }}</p>
                    <p><strong>NRP:</strong> {{ ticketData.ticket.identity_number }}</p>
                    <p><strong>Perihal:</strong> {{ ticketData.ticket.purpose.toUpperCase() }}</p>
                </div>
                <hr class="dashed" />
                <p class="footer-note">Simpan struk ini hingga dipanggil.</p>
                <p class="footer-note">Terima Kasih.</p>
            </div>
        </div>

        <img src="/images/logo-asabri-dark.png" style="display: none;" alt="preload logo" />

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
        width: 50%;         /* Atur lebar logo sekitar setengah lebar kertas (bisa disesuaikan: 40%-70%) */
        height: auto;        /* Jaga proporsi gambar */
        margin: 0 auto 2mm;  /* Posisi tengah horizontal, beri jarak 2mm di bawahnya */
        display: block;      /* Memastikan margin auto berfungsi untuk centering */
        
        /* Opsional: Jika printer thermal Anda hitam putih dan hasil cetak warna kurang bagus, 
           Anda bisa memaksa gambar jadi grayscale */
        /* filter: grayscale(100%); */
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