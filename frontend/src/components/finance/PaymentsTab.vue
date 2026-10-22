<script setup lang="ts">
import { computed } from 'vue'
import type { Payment } from './types'
import { formatRupiah } from './types'

const props = defineProps<{
  payments: Payment[]
  loading: boolean
  filter: {
    metode: string
    search: string
  }
}>()

const emit = defineEmits<{
  (e: 'update:filter', val: typeof props.filter): void
  (e: 'filter-change'): void
  (e: 'view-receipt', paymentId: number): void
  (e: 'cancel-payment', payment: Payment): void
}>()

const filteredPayments = computed(() => {
  if (!props.filter.search) return props.payments
  const q = props.filter.search.toLowerCase().trim()
  return props.payments.filter((p) =>
    p.no_kuitansi?.toLowerCase().includes(q) ||
    p.invoice?.no_tagihan?.toLowerCase().includes(q) ||
    p.invoice?.student?.nama_lengkap?.toLowerCase().includes(q) ||
    p.invoice?.student?.nis?.toLowerCase().includes(q)
  )
})
</script>

<template>
  <div class="space-y-4">
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-wrap gap-4 items-center justify-between">
      <div>
        <h3 class="font-extrabold text-sm text-slate-900">Riwayat Pembayaran & Kuitansi Kasir</h3>
        <p class="text-xs text-slate-500">Seluruh bukti transaksi pembayaran yang telah dicatat dan kuitansi resmi sekolah.</p>
      </div>
      <div class="flex items-center gap-3">
        <select 
          :value="filter.metode"
          @change="(e: any) => { emit('update:filter', { ...filter, metode: e.target.value }); emit('filter-change'); }"
          class="border border-slate-200 bg-slate-50 rounded-xl px-3 py-1.5 text-xs font-semibold text-slate-700"
        >
          <option value="">Semua Metode</option>
          <option value="tunai">Tunai</option>
          <option value="transfer">Transfer</option>
          <option value="qris">QRIS</option>
        </select>
        <input 
          :value="filter.search"
          @input="(e: any) => emit('update:filter', { ...filter, search: e.target.value })"
          type="text" 
          placeholder="Cari no. kuitansi atau siswa..." 
          class="border border-slate-200 bg-slate-50 rounded-xl px-3 py-1.5 text-xs text-slate-700 w-56"
        />
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[800px]">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
              <th class="p-3">No. Kuitansi</th>
              <th class="p-3">Tanggal</th>
              <th class="p-3">Nama Siswa</th>
              <th class="p-3">Biaya / Tagihan</th>
              <th class="p-3 text-center">Metode</th>
              <th class="p-3 text-right">Jumlah Dibayar</th>
              <th class="p-3">Petugas Kasir</th>
              <th class="p-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-if="loading">
              <td colspan="8" class="text-center py-8 text-slate-400 animate-pulse">Memuat riwayat pembayaran...</td>
            </tr>
            <tr v-else-if="filteredPayments.length === 0">
              <td colspan="8" class="text-center py-10 text-slate-400">Belum ada data pembayaran kuitansi.</td>
            </tr>
            <tr v-for="p in filteredPayments" :key="p.id" class="hover:bg-slate-50/80 transition" :class="{ 'opacity-60 bg-rose-50/30': p.is_batal }">
              <td class="p-3 font-mono text-xs font-bold whitespace-nowrap">
                <div :class="p.is_batal ? 'text-slate-400 line-through' : 'text-emerald-800'">{{ p.no_kuitansi }}</div>
                <div v-if="p.is_batal" class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-rose-100 text-rose-700 border border-rose-200">
                  DIBATALKAN
                </div>
              </td>
              <td class="p-3 font-mono text-xs text-slate-500 whitespace-nowrap">{{ p.tanggal }}</td>
              <td class="p-3 whitespace-nowrap">
                <div class="font-extrabold text-slate-900">{{ p.invoice?.student?.nama_lengkap }}</div>
                <div class="text-[10px] text-slate-400">{{ p.invoice?.student?.school_class?.nama || '-' }}</div>
              </td>
              <td class="p-3 whitespace-nowrap">
                <div class="font-bold text-slate-800">{{ p.invoice?.fee_type?.nama }}</div>
                <div class="text-slate-400 text-[10px]">No. Tagihan: {{ p.invoice?.no_tagihan }}</div>
              </td>
              <td class="p-3 text-center whitespace-nowrap">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-700">
                  {{ p.metode }}
                </span>
              </td>
              <td class="p-3 font-mono text-xs font-extrabold text-right whitespace-nowrap" :class="p.is_batal ? 'text-slate-400 line-through' : 'text-emerald-700'">
                {{ formatRupiah(p.jumlah) }}
              </td>
              <td class="p-3 text-xs text-slate-500 whitespace-nowrap">{{ p.recorder?.nama || 'Admin' }}</td>
              <td class="p-3 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-2">
                  <button 
                    @click="emit('view-receipt', p.id)"
                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer"
                    title="Buka Lembar Kuitansi"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Kuitansi</span>
                  </button>
                  <button 
                    v-if="!p.is_batal"
                    @click="emit('cancel-payment', p)"
                    class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl text-xs font-bold transition flex items-center gap-1 border border-rose-200 cursor-pointer"
                    title="Batalkan Pembayaran (Jurnal Pembalik)"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>Batal</span>
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
