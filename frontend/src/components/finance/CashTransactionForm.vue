<script setup lang="ts">
import { ref } from 'vue'
import api from '../../services/api'

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved'): void
  (e: 'notify', msg: string, type?: 'success' | 'error' | 'warning'): void
}>()

const cashForm = ref({
  tipe: 'keluar',
  kategori: 'operasional',
  jumlah: 0,
  keterangan: '',
  tanggal: new Date().toISOString().split('T')[0]
})
const savingCash = ref(false)

const submitCashTransaction = async () => {
  savingCash.value = true
  try {
    await api.post('/finance-transactions', cashForm.value)
    emit('notify', 'Transaksi kas berhasil dicatat!', 'success')
    emit('saved')
    emit('close')
  } catch (err: any) {
    emit('notify', err.response?.data?.message || 'Gagal menyimpan transaksi kas', 'error')
  } finally {
    savingCash.value = false
  }
}
</script>

<template>
  <div class="max-w-xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
      <button 
        @click="emit('close')"
        class="p-2.5 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 rounded-2xl transition flex items-center gap-1.5 text-xs font-bold cursor-pointer"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        <span>Kembali ke Buku Kas</span>
      </button>
    </div>

    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-xs space-y-6">
      <div class="border-b border-slate-100 pb-4">
        <h2 class="text-xl font-black text-slate-900">Catat Transaksi Buku Kas</h2>
        <p class="text-xs text-slate-500 mt-1">Pencatatan pemasukan dana hibah, donasi, atau pengeluaran belanja operasional KBM.</p>
      </div>

      <form @submit.prevent="submitCashTransaction" class="space-y-4 text-xs">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tipe Transaksi</label>
            <select v-model="cashForm.tipe" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-semibold text-slate-800 focus:outline-hidden">
              <option value="keluar">Kas Keluar (Pengeluaran)</option>
              <option value="masuk">Kas Masuk (Penerimaan)</option>
            </select>
          </div>
          <div>
            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Kategori Transaksi</label>
            <select v-model="cashForm.kategori" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-semibold text-slate-800 focus:outline-hidden">
              <option value="operasional">Operasional KBM</option>
              <option value="listrik_air_internet">Listrik & Internet</option>
              <option value="atk">ATK & Percetakan</option>
              <option value="honor">Honorarium / Insentif</option>
              <option value="pemeliharaan">Pemeliharaan Sarpras</option>
              <option value="lainnya">Lainnya</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nominal Transaksi (Rp)</label>
          <input v-model.number="cashForm.jumlah" type="number" required min="1000" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-mono text-base font-black text-slate-800 focus:bg-white focus:outline-hidden" />
        </div>

        <div>
          <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Keterangan / Rincian Penggunaan</label>
          <textarea v-model="cashForm.keterangan" rows="3" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-800 focus:bg-white focus:outline-hidden" placeholder="Tuliskan tujuan dan rincian transaksi kas..."></textarea>
        </div>

        <div>
          <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Transaksi</label>
          <input v-model="cashForm.tanggal" type="date" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-semibold text-slate-800 focus:outline-hidden" />
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
          <button type="button" @click="emit('close')" class="px-5 py-2.5 text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl font-bold transition cursor-pointer">Batal</button>
          <button type="submit" :disabled="savingCash" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl shadow-md transition disabled:opacity-50 cursor-pointer">
            {{ savingCash ? 'Menyimpan...' : 'Simpan Transaksi Kas' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
