<script setup>
import { ref, nextTick, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({ services: Array });

const printing = ref(false);
const ticketData = ref(null);
const currentTime = ref('');
let clockInterval = null;

const form = ref({
    service_id: '', 
    guest_name: '',
    identity_number: '',
    phone_number: '',
    purpose: ''
});

// --- JAM REALTIME ---
const updateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('id-ID', { 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit' 
    }).replace(/\./g, ':');
};

onMounted(() => {
    updateTime();
    clockInterval = setInterval(updateTime, 1000);

    if (props.services && props.services.length > 0) {
        form.value.service_id = props.services[0].id;
    }
});

onUnmounted(() => {
    clearInterval(clockInterval);
});

const submitTicket = async () => {
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
                form.value.guest_name = '';
                form.value.identity_number = '';
                form.value.phone_number = '';
                form.value.purpose = '';
                ticketData.value = null;
                printing.value = false;
            }, 500);
        }, 300);

    } catch (error) {
        console.error(error);
        alert('Terjadi kesalahan sistem.');
        printing.value = false;
    }
};
</script>

<template>
    <div class="h-screen w-screen bg-white flex flex-col font-sans overflow-hidden">
        
        <div class="bg-blue-900 text-white px-8 py-3 flex justify-between items-center relative shadow-md flex-shrink-0 z-20 border-b-4 border-yellow-400">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white rounded-lg flex items-center justify-center p-1 overflow-hidden">
                    <img src="/images/logo-asabri.png" alt="Logo" class="w-full h-full object-contain">
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-wide leading-tight">PT Asabri (Persero) KC Malang</h1>
                    <p class="text-xs text-blue-200 mt-0.5 leading-tight">
                        Ruko Raden Intan Square Jl. Raden Intan No.Kav. 74/I, Malang
                    </p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-white">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-3xl font-bold tracking-widest font-mono">{{ currentTime }}</div>
            </div>
        </div>

        <div class="flex-1 flex flex-col items-center justify-center p-4 relative bg-white">
            
            <div class="text-center mb-4">
                <h1 class="text-3xl font-bold text-blue-900 uppercase tracking-wide">PENDAFTARAN MANDIRI</h1>
                <p class="text-sm text-gray-500 font-medium">PT Asabri (Persero) KC Malang</p>
            </div>

            <div class="w-full max-w-xl border-2 border-blue-900 rounded-xl px-8 py-6 shadow-xl bg-white relative">
                
                <h2 class="text-center text-blue-900 font-bold text-lg mb-5 uppercase tracking-wider">
                    FORMULIR PENDAFTARAN
                </h2>

                <div class="flex flex-col gap-4">
                    
                    <div>
                        <label class="flex items-center gap-2 text-sm font-bold text-blue-900 mb-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Nama Lengkap
                        </label>
                        <input 
                            v-model="form.guest_name" 
                            type="text" 
                            placeholder="Masukkan nama lengkap" 
                            class="w-full bg-gray-100 border border-gray-300 rounded-lg p-3 text-gray-700 focus:ring-2 focus:ring-blue-900 focus:outline-none placeholder-gray-400 font-medium"
                        >
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm font-bold text-blue-900 mb-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                            NRP (Nomor Registrasi Pokok)
                        </label>
                        <input 
                            v-model="form.identity_number" 
                            type="text" 
                            placeholder="Masukkan NRP" 
                            class="w-full bg-gray-100 border border-gray-300 rounded-lg p-3 text-gray-700 focus:ring-2 focus:ring-blue-900 focus:outline-none placeholder-gray-400 font-medium"
                        >
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm font-bold text-blue-900 mb-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            Nomor Telepon
                        </label>
                        <input 
                            v-model="form.phone_number" 
                            type="text" 
                            placeholder="08xx-xxxx-xxxx" 
                            class="w-full bg-gray-100 border border-gray-300 rounded-lg p-3 text-gray-700 focus:ring-2 focus:ring-blue-900 focus:outline-none placeholder-gray-400 font-medium"
                        >
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm font-bold text-blue-900 mb-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Perihal
                        </label>
                        <input 
                            v-model="form.purpose" 
                            type="text"
                            placeholder="Keperluan Anda..." 
                            class="w-full bg-gray-100 border border-gray-300 rounded-lg p-3 text-gray-700 focus:ring-2 focus:ring-blue-900 focus:outline-none placeholder-gray-400 font-medium"
                        >
                    </div>

                    <div class="pt-2">
                        <button 
                            @click="submitTicket" 
                            :disabled="printing"
                            class="w-full bg-yellow-400 hover:bg-yellow-500 text-blue-900 font-extrabold py-3.5 rounded-lg shadow-md transition transform active:scale-[0.98] flex items-center justify-center gap-2 border border-yellow-500"
                        >
                            <svg v-if="!printing" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            <span v-if="!printing" class="text-xl tracking-wide">CETAK NOMOR ANTRIAN</span>
                            <span v-else class="text-xl tracking-wide">MEMPROSES...</span>
                        </button>
                    </div>

                </div>
            </div>

            <div class="mt-4 w-full max-w-xl border-2 border-blue-900 bg-blue-50 py-3 px-4 rounded-lg text-center shadow-sm">
                <p class="text-blue-900 font-bold text-sm">
                    Pastikan data yang dimasukkan sudah benar sebelum mencetak tiket
                </p>
            </div>

        </div>

        <div class="bg-blue-900 text-white py-2 overflow-hidden border-t-4 border-yellow-400 relative flex-shrink-0 z-20">
            <div class="animate-marquee whitespace-nowrap font-bold text-lg tracking-wide">
                PT Asabri (Persero) | Melayani dengan Sepenuh Hati - PT Asabri (Persero) | Melayani dengan Sepenuh Hati - PT Asabri (Persero) | Melayani dengan Sepenuh Hati
            </div>
        </div>

        <div v-if="printing" class="fixed inset-0 bg-white/90 z-[60] flex items-center justify-center flex-col backdrop-blur-sm">
            <div class="animate-spin text-7xl mb-6 text-yellow-500">⏳</div>
            <p class="text-3xl font-bold text-blue-900">Sedang Mencetak Tiket...</p>
        </div>

        <div v-if="ticketData" class="print-only hidden">
            <div class="ticket-container">
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
                    <p><strong>Perihal:</strong> {{ ticketData.ticket.purpose }}</p>
                </div>
                <hr class="dashed" />
                <p class="footer-note">Simpan struk ini hingga dipanggil.</p>
            </div>
        </div>

    </div>
</template>

<style>
/* Animasi Marquee */
@keyframes marquee {
    0% { transform: translateX(100%); }
    100% { transform: translateX(-100%); }
}
.animate-marquee {
    animation: marquee 25s linear infinite;
    display: inline-block;
    padding-left: 100%; 
}

/* CSS PRINT */
@media print {
    body * { visibility: hidden; }
    .print-only, .print-only * { visibility: visible !important; display: block !important;}
    .print-only { position: absolute; left: 0; top: 0; width: 100%; padding: 0; margin: 0; background: white; }
    
    .ticket-container { 
        width: 100%; max-width: 80mm; margin: 0 auto; text-align: center; 
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