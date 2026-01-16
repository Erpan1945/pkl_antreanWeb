<script setup>
import { ref, nextTick, onMounted } from 'vue';
import axios from 'axios';

// Terima data dari Controller
const props = defineProps({ services: Array });

const printing = ref(false);
const ticketData = ref(null);

const form = ref({
    service_id: '', 
    guest_name: '',
    identity_number: '',
    phone_number: '',
    purpose: ''
});

// OTOMATIS PILIH LAYANAN PERTAMA SAAT HALAMAN DIBUKA
onMounted(() => {
    if (props.services && props.services.length > 0) {
        // Ambil ID layanan pertama dari database otomatis
        form.value.service_id = props.services[0].id;
    } else {
        alert("PERINGATAN: Belum ada Data Layanan di Database!");
    }
});

const submitTicket = async () => {
    // Validasi Nama & NRP saja (Layanan sudah otomatis)
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
        
        ticketData.value = response.data;
        await nextTick(); 

        setTimeout(() => {
            window.print();
            setTimeout(() => {
                // Reset Form (Kecuali Service ID)
                form.value.guest_name = '';
                form.value.identity_number = '';
                form.value.phone_number = '';
                form.value.purpose = '';
                // Service ID biarkan tetap terisi otomatis
                
                ticketData.value = null;
                printing.value = false;
            }, 500);
        }, 300);

    } catch (error) {
        if (error.response) {
            // Log validation errors if they exist
            console.error('Validation Error:', error.response.data);
        }
        console.error(error);
        alert('Terjadi kesalahan sistem. Silakan coba lagi.');
        printing.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-gray-100 flex flex-col items-center justify-center p-4 font-sans">
        
        <div class="bg-white p-8 rounded-3xl shadow-2xl w-full max-w-2xl border-t-8 border-blue-600">
            
            <div class="text-center mb-8">
                <h1 class="text-4xl font-extrabold text-gray-900 mb-2">Ambil Antrian</h1>
                <p class="text-gray-500 text-lg">Silakan isi data diri Anda</p>
            </div>

            <div class="space-y-6">
                
                <div>
                    <label class="block text-lg font-bold text-gray-800 mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input 
                        v-model="form.guest_name" 
                        type="text" 
                        placeholder="Nama Anda..." 
                        class="w-full border-2 border-gray-300 rounded-xl focus:border-blue-600 p-4 text-xl"
                    >
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-lg font-bold text-gray-800 mb-2">NRP<span class="text-red-500">*</span></label>
                        <input 
                            v-model="form.identity_number" 
                            type="text" 
                            placeholder="Nomor Identitas..." 
                            class="w-full border-2 border-gray-300 rounded-xl focus:border-blue-600 p-4 text-xl font-mono"
                        >
                    </div>
                    <div>
                        <label class="block text-lg font-bold text-gray-800 mb-2">No. WhatsApp</label>
                        <input 
                            v-model="form.phone_number" 
                            type="text"
                            pattern="[0-9]*"
                            inputmode="numeric"
                            maxlength="15"
                            placeholder="08xxxxx" 
                            class="w-full border-2 border-gray-300 rounded-xl focus:border-blue-600 p-4 text-xl font-mono"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-lg font-bold text-gray-800 mb-2">Perihal</label>
                    <textarea 
                        v-model="form.purpose" 
                        rows="2"
                        placeholder="Contoh: Konsultasi / Legalisir..." 
                        class="w-full border-2 border-gray-300 rounded-xl focus:border-blue-600 p-4 text-xl"
                    ></textarea>
                </div>
            </div>

            <div class="mt-10">
                <button 
                    @click="submitTicket" 
                    :disabled="printing"
                    class="w-full py-5 bg-blue-700 text-white text-2xl font-bold rounded-2xl hover:bg-blue-800 shadow-xl transition transform active:scale-95 disabled:bg-gray-400 disabled:cursor-not-allowed"
                >
                    <span v-if="!printing">🖨️ CETAK TIKET</span>
                    <span v-else>Sedang Memproses...</span>
                </button>
            </div>

        </div>

        <div v-if="printing" class="fixed inset-0 bg-white/95 z-[60] flex items-center justify-center flex-col">
            <div class="animate-spin text-7xl mb-6 text-blue-600">⏳</div>
            <p class="text-3xl font-bold text-gray-800">Sedang Mencetak Tiket...</p>
        </div>

        <div v-if="ticketData" class="print-only hidden">
            <div class="ticket-container">
                <h2 class="instansi">FILKOM UB</h2>
                <p class="date">{{ ticketData.date }}</p>
                <hr class="dashed" />
                
                <p class="label">Nomor Antrian:</p>
                <h1 class="big-number">{{ ticketData.ticket.ticket_code }}</h1>
                <p class="service-name">{{ ticketData.service_name }}</p>
                
                <hr class="dashed" />
                <div class="guest-info">
                    <p><strong>Nama:</strong> {{ ticketData.ticket.guest_name }}</p>
                    <p><strong>NRP:</strong> {{ ticketData.ticket.identity_number }}</p>
                    <p><strong>Perihal:</strong> {{ ticketData.ticket.purpose }}</p>
                </div>
                <hr class="dashed" />
                
                <p class="footer-note">Simpan struk ini hingga dipanggil.</p>
            </div>
        </div>

    </div>
</template>

<style>
/* CSS PRINT */
@media print {
    body * { visibility: hidden; }
    .print-only, .print-only * { visibility: visible !important; display: block !important;}
    .print-only { position: absolute; left: 0; top: 0; width: 100%; padding: 0; margin: 0; background: white; }
    
    .ticket-container { 
        width: 100%; 
        max-width: 80mm; 
        margin: 0 auto; 
        text-align: center; 
        font-family: 'Courier New', Courier, monospace;
    }
    .instansi { font-size: 14pt; font-weight: bold; margin-bottom: 5px; }
    .date { font-size: 9pt; margin-bottom: 10px; }
    .big-number { font-size: 40pt; font-weight: 900; margin: 5px 0; }
    .service-name { font-size: 12pt; font-weight: bold; margin-bottom: 10px; }
    .guest-info { text-align: left; font-size: 10pt; margin: 10px 0; }
    .guest-info p { margin: 2px 0; }
    .dashed { border-top: 1px dashed black; margin: 10px 0; border-bottom: none; }
    .footer-note { font-size: 9pt; margin-top: 10px; font-style: italic; }
    
    @page { size: auto; margin: 0; }
}
</style>