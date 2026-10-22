<script setup lang="ts">
import { computed } from 'vue'
import type { Invoice } from './types'
import { formatRupiah } from './types'

const props = defineProps<{
  invoices: Invoice[]
  classes: any[]
  academicYears: any[]
  loading: boolean
  filter: {
    status: string
    class_id: string
    academic_year_id: string
    search: string
  }
}>()

const emit = defineEmits<{
  (e: 'update:filter', val: typeof props.filter): void
  (e: 'filter-change'): void
  (e: 'pay', inv: Invoice): void
  (e: 'view-receipt', paymentId: number): void
  (e: 'send-wa', inv: Invoice): void
  (e: 'delete-invoice', inv: Invoice): void
}>()

const filteredInvoices = computed(() => {
  if (!props.filter.search) return props.invoices
  const q = props.filter.search.toLowerCase().trim()
  return props.invoices.filter((inv) =>
    inv.no_tagihan?.toLowerCase().includes(q) ||
    inv.student?.nama_lengkap?.toLowerCase().includes(q) ||
    inv.student?.nis?.toLowerCase().includes(q) ||
    inv.student?.nisn?.toLowerCase().includes(q)
  )
})
</script>

<template>
  <div class="space-y-4">
    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-wrap gap-4 items-center justify-between">
      <div class="flex flex-wrap items-center gap-3">
        <div>
          <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Status Tagihan</label>
          <select 
            :value="filter.status"
            @change="(e: any) => { emit('update:filter', { ...filter, status: e.target.value }); emit('filter-change'); }"
            class="border border-slate-200 bg-slate-50 rounded-xl px-3 py-1.5 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
          >
            <option value="">Semua Status</option>
            <option value="belum_bayar">Belum Bayar</option>
            <option value="sebagian">Sebagian</option>
            <option value="lunas">Lunas</option>
            <option value="jatuh_tempo">Jatuh Tempo</option>
          </select>
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Filter Rombel Kelas</label>
          <select 
            :value="filter.class_id"
            @change="(e: any) => { emit('update:filter', { ...filter, class_id: e.target.value }); emit('filter-change'); }"
            class="border border-slate-200 bg-slate-50 rounded-xl px-3 py-1.5 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
          >
            <option value="">Semua Kelas</option>
            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nama }} (Tingkat {{ c.tingkat }})</option>
          </select>
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Tahun Ajaran</label>
          <select 
            :value="filter.academic_year_id"
            @change="(e: any) => { emit('update:filter', { ...filter, academic_year_id: e.target.value }); emit('filter-change'); }"
            class="border border-slate-200 bg-slate-50 rounded-xl px-3 py-1.5 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
          >
            <option value="">Semua Tahun Ajaran</option>
            <option v-for="y in academicYears" :key="y.id" :value="y.id">{{ y.nama }}</option>
          </select>
        </div>
      </div>
      <div class="w-full sm:w-64">
        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Cari Tagihan</label>
        <input 
          :value="filter.search"
          @input="(e: any) => emit('update:filter', { ...filter, search: e.target.value })"
          type="text" 
          placeholder="Cari siswa atau no. tagihan..." 
          class="w-full border border-slate-200 bg-slate-50 rounded-xl px-3 py-1.5 text-xs text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
        />
      </div>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[800px]">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
              <th class="p-3 text-center w-12">No.</th>
              <th class="p-3">No. Tagihan</th>
              <th class="p-3">Nama Siswa</th>
              <th class="p-3">Kelas</th>
              <th class="p-3">Jenis Biaya / Periode</th>
              <th class="p-3 text-right">Nominal</th>
              <th class="p-3 text-right">Sisa Tagihan</th>
              <th class="p-3 text-center">Status</th>
              <th class="p-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-if="loading">
              <td colspan="9" class="text-center py-8 text-slate-400 animate-pulse">Memuat data tagihan...</td>
            </tr>
            <tr v-else-if="filteredInvoices.length === 0">
              <td colspan="9" class="text-center py-10 text-slate-400">Tidak ada tagihan yang sesuai dengan filter.</td>
            </tr>
            <tr v-for="(inv, index) in filteredInvoices" :key="inv.id" class="hover:bg-slate-50/80 transition">
              <td class="p-3 text-center font-mono text-xs font-bold text-slate-400 whitespace-nowrap">{{ index + 1 }}</td>
              <td class="p-3 font-mono text-xs font-bold text-slate-700 whitespace-nowrap">{{ inv.no_tagihan }}</td>
              <td class="p-3 whitespace-nowrap">
                <div class="font-extrabold text-slate-900">{{ inv.student?.nama_lengkap }}</div>
                <div class="text-[10px] text-slate-400 font-mono">NIS: {{ inv.student?.nis || '-' }} • NISN: {{ inv.student?.nisn || '-' }}</div>
              </td>
              <td class="p-3 text-xs text-slate-600 whitespace-nowrap">{{ inv.student?.school_class?.nama || '-' }}</td>
              <td class="p-3 text-xs text-slate-700 whitespace-nowrap">
                <div class="font-bold text-slate-800">{{ inv.fee_type?.nama }}</div>
                <div class="text-slate-400 text-[10px]">Periode: {{ inv.periode }}</div>
              </td>
              <td class="p-3 font-mono text-xs font-semibold text-right whitespace-nowrap">{{ formatRupiah(inv.jumlah) }}</td>
              <td class="p-3 font-mono text-xs font-bold text-right whitespace-nowrap" :class="inv.sisa > 0 ? 'text-rose-600' : 'text-emerald-600'">
                {{ formatRupiah(inv.sisa) }}
              </td>
              <td class="p-3 text-center whitespace-nowrap">
                <span 
                  class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider border"
                  :class="{
                    'bg-emerald-100 text-emerald-800 border-emerald-300': inv.status === 'lunas',
                    'bg-amber-100 text-amber-800 border-amber-300': inv.status === 'sebagian',
                    'bg-rose-100 text-rose-800 border-rose-300': inv.status === 'belum_bayar' || inv.status === 'jatuh_tempo'
                  }"
                >
                  {{ inv.status.replace('_', ' ') }}
                </span>
              </td>
              <td class="p-3 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-2">
                  <!-- Tombol Kasir Bayar (Buka Form Satu Halaman Penuh, TANPA POPUP) -->
                  <button 
                    v-if="inv.status !== 'lunas'"
                    @click="emit('pay', inv)"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-3 py-1.5 rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                    title="Bayar tagihan di kasir"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>Kasir Bayar</span>
                  </button>

                  <!-- Tombol Lihat Kuitansi jika ada pembayaran -->
                  <button 
                    v-if="inv.payments && inv.payments.length > 0"
                    @click="emit('view-receipt', inv.payments[0].id)"
                    class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 cursor-pointer"
                    title="Lihat Kuitansi Pembayaran Terakhir"
                  >
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Lihat Kuitansi</span>
                  </button>

                  <!-- Tombol WhatsApp: Penagihan (Belum Lunas) atau Apresiasi (Lunas) -->
                  <button 
                    @click="emit('send-wa', inv)"
                    class="px-2.5 py-1.5 rounded-xl transition flex items-center gap-1.5 font-bold text-xs shadow-xs cursor-pointer"
                    :class="inv.status === 'lunas' 
                      ? 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300' 
                      : 'bg-[#25D366] hover:bg-[#20ba59] text-white shadow-emerald-900/10'"
                    :title="inv.status === 'lunas' ? 'Kirim Pesan Apresiasi / Terima Kasih via WhatsApp' : 'Kirim Penagihan Tagihan via WhatsApp'"
                  >
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                    </svg>
                    <span>{{ inv.status === 'lunas' ? 'Apresiasi' : 'Kirim Tagihan' }}</span>
                  </button>

                  <!-- Tombol Hapus Tagihan (Hanya jika belum pernah dibayar) -->
                  <button 
                    v-if="inv.status === 'belum_bayar' && (!inv.payments || inv.payments.length === 0)"
                    @click="emit('delete-invoice', inv)"
                    class="bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-bold px-2.5 py-1.5 rounded-xl transition flex items-center gap-1 border border-rose-200 cursor-pointer"
                    title="Hapus Tagihan"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
