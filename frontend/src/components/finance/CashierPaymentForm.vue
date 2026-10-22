<script setup lang="ts">
import { ref } from 'vue'
import api from '../../services/api'
import type { Invoice } from './types'
import { formatRupiah } from './types'

const props = defineProps<{
  selectedInvoice: Invoice
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'paid', paymentId: number): void
  (e: 'notify', msg: string, type?: 'success' | 'error' | 'warning'): void
}>()

const payForm = ref({
  invoice_id: props.selectedInvoice.id,
  jumlah: props.selectedInvoice.sisa,
  metode: 'tunai',
  catatan: `Pembayaran tagihan ${props.selectedInvoice.no_tagihan}`
})
const paying = ref(false)

const submitPayment = async () => {
  paying.value = true
  try {
    const res = await api.post('/payments', payForm.value)
    emit('notify', 'Pembayaran berhasil dicatat & kuitansi resmi diterbitkan!', 'success')
    if (res.data.data?.id) {
      emit('paid', res.data.data.id)
    } else {
      emit('close')
    }
  } catch (err: any) {
    emit('notify', err.response?.data?.message || 'Gagal mencatat pembayaran', 'error')
  } finally {
    paying.value = false
  }
}
</script>

<template>
  <div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
      <button 
        @click="emit('close')"
        class="p-2.5 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 rounded-2xl transition flex items-center gap-1.5 text-xs font-bold cursor-pointer"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        <span>Kembali ke Daftar Tagihan</span>
      </button>
    </div>

    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-xs space-y-6">
      <div class="border-b border-slate-100 pb-4">
        <h2 class="text-xl font-black text-slate-900">Kasir Pembayaran Tagihan</h2>
        <p class="text-xs text-slate-500 mt-1">Catat setoran pembayaran siswa secara langsung dan terbitkan kuitansi resmi sekolah.</p>
      </div>

      <!-- Student & Invoice Banner -->
      <div class="bg-emerald-50/70 p-4 rounded-2xl border border-emerald-100 space-y-2">
        <div class="flex justify-between items-start">
          <div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800">Nama Siswa:</span>
            <h3 class="text-base font-extrabold text-emerald-950">{{ selectedInvoice.student?.nama_lengkap }}</h3>
            <p class="text-xs text-emerald-700">NIS: {{ selectedInvoice.student?.nis || '-' }} • Kelas: {{ selectedInvoice.student?.school_class?.nama || '-' }}</p>
          </div>
          <div class="text-right">
            <span class="text-[10px] font-mono font-bold text-emerald-700 bg-white px-2 py-0.5 rounded-lg border border-emerald-200">{{ selectedInvoice.no_tagihan }}</span>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-emerald-200/60 text-xs">
          <div>
            <span class="text-slate-500">Biaya:</span> <strong class="text-slate-800">{{ selectedInvoice.fee_type?.nama }}</strong>
          </div>
          <div>
            <span class="text-slate-500">Periode:</span> <strong class="text-slate-800">{{ selectedInvoice.periode }}</strong>
          </div>
          <div>
            <span class="text-slate-500">Total Tagihan:</span> <strong class="text-slate-800">{{ formatRupiah(selectedInvoice.jumlah) }}</strong>
          </div>
          <div>
            <span class="text-slate-500">Sisa Tagihan:</span> <strong class="text-rose-700 font-mono font-bold">{{ formatRupiah(selectedInvoice.sisa) }}</strong>
          </div>
        </div>
      </div>

      <form @submit.prevent="submitPayment" class="space-y-4 text-xs">
        <div>
          <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nominal Pembayaran (Rp)</label>
          <input 
            v-model.number="payForm.jumlah" 
            type="number" 
            min="1000"
            :max="selectedInvoice.sisa" 
            required 
            class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-mono text-lg font-black text-emerald-700 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-hidden" 
          />
          <span class="text-[10px] text-slate-400 mt-1 block">Maksimal pembayaran sesuai sisa tagihan: {{ formatRupiah(selectedInvoice.sisa) }}</span>
        </div>

        <div>
          <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Metode Pembayaran</label>
          <select v-model="payForm.metode" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
            <option value="tunai">Tunai / Cash di Kasir</option>
            <option value="transfer">Transfer Bank Sekolah</option>
            <option value="qris">QRIS Standar</option>
          </select>
        </div>

        <div>
          <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Catatan Tambahan</label>
          <input v-model="payForm.catatan" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden" />
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
          <button 
            type="button" 
            @click="emit('close')" 
            class="px-5 py-2.5 text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl font-bold transition cursor-pointer"
          >
            Batal
          </button>
          <button 
            type="submit" 
            :disabled="paying"
            class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl shadow-md transition disabled:opacity-50 flex items-center gap-2 cursor-pointer"
          >
            <svg v-if="paying" class="animate-spin w-4 h-4" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>{{ paying ? 'Memproses...' : 'Konfirmasi Pembayaran & Terbitkan Kuitansi' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
