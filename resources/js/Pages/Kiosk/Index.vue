<script setup>
import { ref, nextTick } from 'vue';
import axios from 'axios';

defineProps({ services: Array });

const printing = ref(false);
const showModal = ref(false);
const ticketData = ref(null);

// Data Form (Istilah umum)
const form = ref({
    service_id: null,
    service_name: '',
    guest_name: '',
    identity_number: ''
});

const openForm = (service) => {
    form.value.service_id = service.id;
    form.value.service_name = service.name;
    form.value.guest_name = '';
    form.value.identity_number = '';
    showModal.value = true;
};

const submitTicket = async () => {
    // Validasi sederhana
    if (!form.value.guest_name || !form.value.identity_number) {
        alert("Mohon lengkapi Nama dan Nomor Identitas (HP/NIK).");
        return;
    }

    printing.value = true;
    showModal.value = false;

    try {
        const response = await axios.post('/kiosk/ticket', { 
            service_id: form.value.service_id,
            guest_name: form.value.guest_name,
            identity_number: form.value.identity_number
        });
        
        ticketData.value = response.data;
        await nextTick(); 

        setTimeout(() => {
            window.print();
            setTimeout(() => {
                ticketData.value = null;
                printing.value = false;
            }, 500);
        }, 300);

    } catch (error) {
        console.error(error);
        alert('Terjadi kesalahan sistem. Silakan coba lagi.');
        printing.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-6 relative font-sans">
        
        <div class="mb-12 text-center">
            <h1 class="text-5xl font-extrabold text-gray-900 mb-4">Selamat Datang</h1>
            <p class="text-2xl text-gray-600">Silakan sentuh layanan yang Anda butuhkan</p>
        </div>

        <div class="grid grid-cols-1 gap-8 w-full max-w-5xl">
            <button 
                v-for="service in services" 
                :key="service.id"
                @click="openForm(service)"
                :disabled="printing"
                class="p-12 bg-white rounded-3xl shadow-lg border-l-8 border-blue-600 hover:bg-blue-50 transition transform active:scale-95 text-left group flex items-center justify-between"
            >
                <div>
                    <span class="block text-4xl font-bold text-gray-800 mb-2 group-hover:text-blue-700">
                        {{ service.name }}
                    </span>
                    <span class="text-xl text-gray-500">Sentuh untuk ambil antrian</span>
                </div>
                <div class="text-6xl font-black text-gray-200 group-hover:text-blue-200">
                    {{ service.code }}
                </div>
            </button>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 backdrop-blur-sm p-4">
            <div class="bg-white p-8 rounded-3xl shadow-2xl w-full max-w-lg">
                
                <div class="text-center mb-8 border-b pb-4">
                    <p class="text-gray-500 text-lg">Layanan Dipilih:</p>
                    <h2 class="text-3xl font-bold text-blue-700">{{ form.service_name }}</h2>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="block text-xl font-bold text-gray-800 mb-2">Nama Lengkap (Bapak/Ibu)</label>
                        <input 
                            v-model="form.guest_name" 
                            type="text" 
                            placeholder="Ketik Nama Anda..." 
                            class="w-full border-2 border-gray-300 rounded-xl focus:border-blue-600 focus:ring-4 focus:ring-blue-200 p-5 text-2xl"
                        >
                    </div>

                    <div>
                        <label class="block text-xl font-bold text-gray-800 mb-2">Nomor NRP</label>
                        <input 
                            v-model="form.identity_number" 
                            type="number" 
                            placeholder="Contoh: 081234..." 
                            class="w-full border-2 border-gray-300 rounded-xl focus:border-blue-600 focus:ring-4 focus:ring-blue-200 p-5 text-2xl font-mono"
                        >
                        <p class="text-gray-500 text-sm mt-2">*Cukup masukkan angka saja</p>
                    </div>
                </div>

                <div class="mt-10 flex flex-col gap-4">
                    <button @click="submitTicket" class="w-full py-6 bg-blue-700 text-white text-2xl font-bold rounded-2xl hover:bg-blue-800 shadow-xl transition">
                        🖨️ CETAK TIKET
                    </button>
                    <button @click="showModal = false" class="w-full py-4 text-gray-600 text-xl font-semibold rounded-2xl hover:bg-gray-200 transition">
                        Batal / Kembali
                    </button>
                </div>
            </div>
        </div>

        <div v-if="printing" class="fixed inset-0 bg-white/95 z-[60] flex items-center justify-center flex-col">
            <div class="animate-spin text-7xl mb-6 text-blue-600">⏳</div>
            <p class="text-3xl font-bold text-gray-800">Sedang Mencetak Tiket...</p>
            <p class="text-xl text-gray-500 mt-2">Mohon tunggu sebentar</p>
        </div>

        <div v-if="ticketData" class="print-only hidden">
            <div class="ticket-container">
                <h2 class="instansi">INSTANSI PELAYANAN</h2>
                <p class="date">{{ ticketData.date }}</p>
                <hr class="dashed" />
                
                <p class="label">Nomor Antrian:</p>
                <h1 class="big-number">{{ ticketData.ticket.ticket_code }}</h1>
                <p class="service-name">{{ ticketData.service_name }}</p>
                
                <hr class="dashed" />
                <div class="guest-info">
                    <p style="font-size: 14pt; font-weight: bold; margin-bottom: 5px;">{{ ticketData.ticket.guest_name }}</p>
                    <p>ID: {{ ticketData.ticket.identity_number }}</p>
                </div>
                <hr class="dashed" />
                
                <p class="footer-note">Mohon menunggu hingga nomor dipanggil.</p>
            </div>
        </div>

    </div>
</template>

<style>
/* ... CSS PRINT SAMA SEPERTI SEBELUMNYA ... */
@media print {
    body * { visibility: hidden; }
    .print-only, .print-only * { visibility: visible !important; display: block !important;}
    .print-only { position: absolute; left: 0; top: 0; width: 100%; padding: 10px; background: white; }
    .ticket-container { text-align: center; font-family: sans-serif; }
    .big-number { font-size: 42pt; font-weight: 900; margin: 10px 0; }
    .guest-info { text-align: left; font-size: 11pt; margin: 15px 0; border: 1px solid #000; padding: 10px; border-radius: 5px; }
    .dashed { border-top: 2px dashed black; margin: 10px 0; }
    @page { size: 80mm auto; margin: 0; }
}
</style>