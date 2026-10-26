<script setup lang="ts">
import type { FeeType } from './types'
import { formatRupiah } from './types'

defineProps<{
  feeTypes: FeeType[]
}>()

const emit = defineEmits<{
  (e: 'add-fee-type'): void
  (e: 'edit-fee-type', fee: FeeType): void
  (e: 'delete-fee-type', fee: FeeType): void
}>()
</script>

<template>
  <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 space-y-4">
    <div class="border-b border-slate-100 pb-4">
      <div>
        <h3 class="font-extrabold text-base text-slate-900">Master Tarif & Jenis Biaya</h3>
        <p class="text-xs text-slate-500">Daftar tarif resmi SPP bulanan, gedung, seragam, kegiatan dll.</p>
      </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="f in feeTypes" :key="f.id" class="p-4 border border-slate-200 rounded-2xl bg-slate-50 space-y-2 hover:bg-white hover:shadow-xs transition">
        <div class="flex justify-between items-start">
          <span class="text-xs font-mono bg-white px-2 py-0.5 rounded-lg border border-slate-200 font-bold text-slate-600">{{ f.kode }}</span>
          <span class="text-[10px] uppercase font-bold text-emerald-800 bg-emerald-100 px-2.5 py-0.5 rounded-full">{{ f.periode }}</span>
        </div>
        <h4 class="font-bold text-slate-800 text-sm">{{ f.nama }}</h4>
        <div class="text-lg font-black text-emerald-700">{{ formatRupiah(f.nominal) }}</div>
        <div class="text-[11px] text-slate-500">{{ f.keterangan || '-' }}</div>
        <div class="pt-2 border-t border-slate-200 flex justify-end gap-3">
          <button 
            @click="emit('edit-fee-type', f)"
            class="text-xs text-emerald-700 font-bold hover:underline cursor-pointer"
          >
            Edit Tarif
          </button>
          <button 
            @click="emit('delete-fee-type', f)"
            class="text-xs text-rose-600 font-bold hover:underline cursor-pointer"
          >
            Hapus
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
