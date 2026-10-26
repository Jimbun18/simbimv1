<script setup lang="ts">
import { computed } from 'vue'
import type { CashTransaction } from './types'
import { formatRupiah } from './types'

const props = defineProps<{
  transactions: CashTransaction[]
  loading: boolean
  filter: {
    tipe: string
    search: string
  }
}>()

const emit = defineEmits<{
  (e: 'update:filter', val: typeof props.filter): void
  (e: 'filter-change'): void
  (e: 'open-cash-form'): void
}>()

const filteredTransactions = computed(() => {
  if (!props.filter.search) return props.transactions
  const q = props.filter.search.toLowerCase().trim()
  return props.transactions.filter((t) =>
    t.no_transaksi?.toLowerCase().includes(q) ||
    t.keterangan?.toLowerCase().includes(q) ||
    t.kategori?.toLowerCase().includes(q) ||
    t.recorder?.nama?.toLowerCase().includes(q)
  )
})
</script>

<template>
  <div class="space-y-4">
    <!-- Filter Bar Cashbook -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-wrap gap-4 items-center justify-between">
      <div class="flex flex-wrap items-center gap-3">
        <div>
          <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Arus Kas</label>
          <select 
            :value="filter.tipe"
            @change="(e: any) => { emit('update:filter', { ...filter, tipe: e.target.value }); emit('filter-change'); }"
            class="border border-slate-200 bg-slate-50 rounded-xl px-3 py-1.5 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
          >
            <option value="">Semua Arus Kas</option>
            <option value="masuk">Kas Masuk (Penerimaan)</option>
            <option value="keluar">Kas Keluar (Pengeluaran)</option>
          </select>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <div class="w-56 sm:w-64">
          <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Cari Transaksi</label>
          <input 
            :value="filter.search"
            @input="(e: any) => emit('update:filter', { ...filter, search: e.target.value })"
            type="text" 
            placeholder="Cari keterangan, bukti..." 
            class="w-full border border-slate-200 bg-slate-50 rounded-xl px-3 py-1.5 text-xs text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
          />
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[700px]">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
              <th class="p-3">Tanggal</th>
              <th class="p-3">No. Bukti / Transaksi</th>
              <th class="p-3">Tipe</th>
              <th class="p-3">Kategori</th>
              <th class="p-3">Keterangan</th>
              <th class="p-3 text-right">Jumlah</th>
              <th class="p-3">Dicatat Oleh</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-if="loading">
              <td colspan="7" class="text-center py-8 text-slate-400 animate-pulse">Memuat buku kas...</td>
            </tr>
            <tr v-else-if="filteredTransactions.length === 0">
              <td colspan="7" class="text-center py-8 text-slate-400">Belum ada transaksi kas yang sesuai.</td>
            </tr>
            <tr v-for="t in filteredTransactions" :key="t.id" class="hover:bg-slate-50/70">
              <td class="p-3 font-mono text-xs text-slate-500 whitespace-nowrap">{{ t.tanggal }}</td>
              <td class="p-3 font-mono text-xs font-bold text-slate-700 whitespace-nowrap">{{ t.no_transaksi }}</td>
              <td class="p-3 whitespace-nowrap">
                <span 
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                  :class="t.tipe === 'masuk' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                >
                  {{ t.tipe }}
                </span>
              </td>
              <td class="p-3 text-xs text-slate-700 capitalize whitespace-nowrap">{{ t.kategori.replace('_', ' ') }}</td>
              <td class="p-3 text-xs text-slate-600 max-w-sm truncate">{{ t.keterangan }}</td>
              <td 
                class="p-3 font-mono text-xs font-bold text-right whitespace-nowrap"
                :class="t.tipe === 'masuk' ? 'text-emerald-600' : 'text-rose-600'"
              >
                {{ t.tipe === 'masuk' ? '+' : '-' }} {{ formatRupiah(t.jumlah) }}
              </td>
              <td class="p-3 text-xs text-slate-500 whitespace-nowrap">{{ t.recorder?.nama || 'Admin' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
