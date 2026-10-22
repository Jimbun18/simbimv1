<script setup lang="ts">
import { formatRupiah } from './types'

const props = defineProps<{
  classes: any[]
  feeTypes: any[]
  academicYears: any[]
  batchForm: {
    class_id: number | null
    fee_type_id: number | string
    academic_year_id: number | string
    periode: string
    jatuh_tempo: string
  }
  batchGenerating: boolean
}>()

const emit = defineEmits<{
  (e: 'update:batchForm', val: typeof props.batchForm): void
  (e: 'generate'): void
}>()
</script>

<template>
  <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 max-w-2xl mx-auto space-y-6">
    <div>
      <h3 class="text-lg font-black text-slate-900">Generator Tagihan SPP Massal</h3>
      <p class="text-xs text-slate-500 mt-1">Buat tagihan SPP secara otomatis untuk seluruh siswa dalam satu rombel kelas atau seluruh sekolah untuk periode bulan tertentu.</p>
    </div>

    <form @submit.prevent="emit('generate')" class="space-y-4 text-xs">
      <div>
        <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pilih Target Rombel Siswa</label>
        <select 
          :value="batchForm.class_id"
          @change="(e: any) => emit('update:batchForm', { ...batchForm, class_id: e.target.value === '' ? null : Number(e.target.value) })"
          class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
        >
          <option :value="null">Semua Rombel (Seluruh Siswa Aktif Sekolah)</option>
          <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nama }} (Tingkat {{ c.tingkat }})</option>
        </select>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Jenis Biaya</label>
          <select 
            :value="batchForm.fee_type_id"
            @change="(e: any) => emit('update:batchForm', { ...batchForm, fee_type_id: e.target.value })"
            required 
            class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
          >
            <option v-for="f in feeTypes" :key="f.id" :value="f.id">{{ f.nama }} ({{ formatRupiah(f.nominal) }})</option>
          </select>
        </div>
        <div>
          <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tahun Ajaran</label>
          <select 
            :value="batchForm.academic_year_id"
            @change="(e: any) => emit('update:batchForm', { ...batchForm, academic_year_id: e.target.value })"
            required 
            class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
          >
            <option v-for="y in academicYears" :key="y.id" :value="y.id">{{ y.nama }} ({{ y.status === 'aktif' ? 'Aktif' : 'Non-Aktif' }})</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Periode Bulan (YYYY-MM)</label>
          <input 
            type="month" 
            :value="batchForm.periode"
            @input="(e: any) => emit('update:batchForm', { ...batchForm, periode: e.target.value })"
            required 
            class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden" 
          />
        </div>
        <div>
          <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Batas Jatuh Tempo</label>
          <input 
            type="date" 
            :value="batchForm.jatuh_tempo"
            @input="(e: any) => emit('update:batchForm', { ...batchForm, jatuh_tempo: e.target.value })"
            required 
            class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden" 
          />
        </div>
      </div>

      <div class="pt-4 border-t flex justify-end">
        <button 
          type="submit" 
          :disabled="batchGenerating"
          class="bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs px-6 py-2.5 rounded-xl shadow-md transition disabled:opacity-50 flex items-center gap-2 cursor-pointer"
        >
          <span v-if="batchGenerating">Memproses Generate Tagihan...</span>
          <span v-else>Generate Tagihan Massal</span>
        </button>
      </div>
    </form>
  </div>
</template>
