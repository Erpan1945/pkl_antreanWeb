<script setup>
import { ref, nextTick, onMounted } from 'vue';
import axios from 'axios';
import DisplayLayout from '@/Layouts/DisplayLayout.vue'; 

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

onMounted(() => {
    if (props.services && props.services.length > 0) {
        form.value.service_id = props.services[0].id;
    }
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
                        <input 
                            v-model="form.guest_name" 
                            type="text" 
                            placeholder="Ketik nama lengkap..." 
                            class="w-full bg-gray-50 border-2 border-gray-200 rounded-xl p-3 text-gray-800 focus:ring-2 focus:ring-[#00569c]/20 focus:border-[#00569c] focus:outline-none font-bold transition-all text-sm"
                        >
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-xs font-bold text-[#00569c] mb-1 uppercase tracking-wide">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                            NRP / NIP / Identitas
                        </label>
                        <input 
                            v-model="form.identity_number" 
                            type="text" 
                            placeholder="Ketik Nomor Identitas..." 
                            class="w-full bg-gray-50 border-2 border-gray-200 rounded-xl p-3 text-gray-800 focus:ring-2 focus:ring-[#00569c]/20 focus:border-[#00569c] focus:outline-none font-bold transition-all text-sm"
                        >
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-xs font-bold text-[#00569c] mb-1 uppercase tracking-wide">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            Nomor Telepon
                        </label>
                        <input 
                            v-model="form.phone_number" 
                            type="text" 
                            placeholder="08xx-xxxx-xxxx" 
                            class="w-full bg-gray-50 border-2 border-gray-200 rounded-xl p-3 text-gray-800 focus:ring-2 focus:ring-[#00569c]/20 focus:border-[#00569c] focus:outline-none font-bold transition-all text-sm"
                        >
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-xs font-bold text-[#00569c] mb-1 uppercase tracking-wide">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Keperluan
                        </label>
                        <input 
                            v-model="form.purpose" 
                            type="text"
                            placeholder="Contoh: Pengurusan Pensiun" 
                            class="w-full bg-gray-50 border-2 border-gray-200 rounded-xl p-3 text-gray-800 focus:ring-2 focus:ring-[#00569c]/20 focus:border-[#00569c] focus:outline-none font-bold transition-all text-sm"
                        >
                    </div>

                    <div class="pt-2 mt-1">
                        <button 
                            @click="submitTicket" 
                            :disabled="printing"
                            class="w-full bg-[#ffc107] hover:bg-yellow-400 text-[#00569c] font-black py-3.5 rounded-xl shadow-md hover:shadow-lg transition-all transform active:scale-[0.98] flex items-center justify-center gap-3 border-b-4 border-yellow-600 active:border-b-0 active:translate-y-1"
                        >
                            <svg v-if="!printing" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            <span v-if="!printing" class="text-lg tracking-wider">CETAK TIKET ANTRIAN</span>
                            <span v-else class="text-lg tracking-wider animate-pulse">SEDANG MEMPROSES...</span>
                        </button>
                    </div>

                </div>
            </div>

            <div class="mt-4 text-[#00569c] font-bold opacity-60 text-s shrink-0">
                *Pastikan data yang diisi sesuai dengan kartu identitas
            </div>

        </div>

        <div v-if="printing" class="fixed inset-0 bg-[#00569c]/90 z-[60] flex items-center justify-center flex-col backdrop-blur-md">
            <div class="animate-spin text-7xl mb-6 text-[#ffc107]">⏳</div>
            <p class="text-3xl font-black text-white tracking-widest uppercase">Sedang Mencetak Tiket...</p>
            <p class="text-white mt-4 font-bold">Silakan ambil struk Anda di mesin printer</p>
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
                <p class="footer-note">Terima Kasih.</p>
            </div>
        </div>

    </DisplayLayout>
</template>

<style>
@media print {
    body * { visibility: hidden; }
    .print-only, .print-only * { visibility: visible !important; display: block !important;}
    .print-only { position: absolute; left: 0; top: 0; width: 100%; padding: 0; margin: 0; background: white; }
    
    .ticket-container { 
        width: 100%; max-width: 80mm; margin: 0 auto; text-align: center; 
        font-family: 'Courier New', Courier, monospace;
        color: black;
    }
    .instansi { font-size: 14pt; font-weight: bold; margin-bottom: 5px; }
    .date { font-size: 9pt; margin-bottom: 10px; }
    .big-number { font-size: 40pt; font-weight: 900; margin: 5px 0; }
    .service-name { font-size: 12pt; font-weight: bold; margin-bottom: 10px; }
    .guest-info { text-align: left; font-size: 10pt; margin: 10px 0; }
    .guest-info p { margin: 2px 0; }
    .dashed { border-top: 1px dashed black; margin: 10px 0; border-bottom: none; }
    .footer-note { font-size: 9pt; margin-top: 5px; font-style: italic; }
    @page { size: auto; margin: 0; }
}
</style>