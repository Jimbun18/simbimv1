<script setup lang="ts">
import { ref, watch } from 'vue'
import api from '../../services/api'
import type { FeeType } from './types'

const props = defineProps<{
  initialData?: FeeType | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved'): void
  (e: 'notify', msg: string, type?: 'success' | 'error' | 'warning'): void
}>()

const feeTypeForm = ref({
  id: 0,
  kode: '',
  nama: '',
  nominal: 0,
  periode: 'bulanan',
  is_aktif: true,
  keterangan: ''
})

watch(
  () => props.initialData,
  (f) => {
    if (f) {
      feeTypeForm.value = {
        id: f.id || 0,
        kode: f.kode || '',
        nama: f.nama || '',
        nominal: f.nominal || 0,
        periode: f.periode || 'bulanan',
        is_aktif: f.is_aktif ?? true,
        keterangan: f.keterangan || ''
      }
    } else {
      feeTypeForm.value = {
        id: 0,
        kode: '',
        nama: '',
        nominal: 0,
        periode: 'bulanan',
        is_aktif: true,
        keterangan: ''
      }
    }
  },
  { immediate: true }
)

const savingFeeType = ref(false)

const submitFeeType = async () => {
  savingFeeType.value = true
  try {
    if (feeTypeForm.value.id) {
      await api.put(`/fee-types/${feeTypeForm.value.id}`, feeTypeForm.value)
      emit('notify', 'Tarif biaya berhasil diperbarui!', 'success')
    } else {
      await api.post('/fee-types', feeTypeForm.value)
      emit('notify', 'Tarif biaya baru berhasil ditambahkan!', 'success')
    }
    emit('saved')
    emit('close')
  } catch (err: any) {
    emit('notify', err.response?.data?.message || 'Gagal menyimpan tarif biaya', 'error')
  } finally {
    savingFeeType.value = false
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
        <span>Kembali ke Master Tarif</span>
      </button>
    </div>

    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-xs space-y-6">
      <div class="border-b border-slate-100 pb-4">
        <h2 class="text-xl font-black text-slate-900">
          {{ feeTypeForm.id ? 'Edit Tarif & Jenis Biaya' : 'Tambah Jenis Biaya Baru' }}
        </h2>
        <p class="text-xs text-slate-500 mt-1">Tentukan kode, nama jenis biaya, periode tagihan, dan besaran nominal default.</p>
      </div>

      <form @submit.prevent="submitFeeType" class="space-y-4 text-xs">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Kode Biaya</label>
            <input v-model="feeTypeForm.kode" type="text" required placeholder="Contoh: SPP, GEDUNG" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono uppercase font-bold text-slate-800 focus:outline-hidden" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Periode Tagihan</label>
            <select v-model="feeTypeForm.periode" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-semibold text-slate-800 focus:outline-hidden">
              <option value="bulanan">Bulanan</option>
              <option value="semester">Semester</option>
              <option value="tahunan">Tahunan</option>
              <option value="sekali">Sekali Bayar</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Jenis Biaya</label>
          <input v-model="feeTypeForm.nama" type="text" required placeholder="Contoh: SPP Bulanan Kelas 1-6" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold text-slate-800 focus:outline-hidden" />
        </div>

        <div>
          <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nominal Standar (Rp)</label>
          <input v-model.number="feeTypeForm.nominal" type="number" required min="0" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-mono text-base font-black text-emerald-700 focus:bg-white focus:outline-hidden" />
        </div>

        <div>
          <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Keterangan Tambahan</label>
          <textarea v-model="feeTypeForm.keterangan" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-800 focus:outline-hidden"></textarea>
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
          <button type="button" @click="emit('close')" class="px-5 py-2.5 text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl font-bold transition cursor-pointer">Batal</button>
          <button type="submit" :disabled="savingFeeType" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl shadow-md transition disabled:opacity-50 cursor-pointer">
            {{ savingFeeType ? 'Menyimpan...' : 'Simpan Jenis Biaya' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
