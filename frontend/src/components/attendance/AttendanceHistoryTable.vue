<script setup lang="ts">
import { useRouter } from 'vue-router'
import { formatTimeDisplay, formatDateDisplay, formatKeteranganDisplay } from './types'

defineProps<{
  historyList: any[]
  historyLoading: boolean
}>()

const emit = defineEmits<{
  (e: 'refresh'): void
}>()

const router = useRouter()
</script>

<template>
  <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h3 class="font-bold text-slate-900 text-base">Riwayat Presensi Terbaru</h3>
        <p class="text-[11px] text-slate-400">Catatan kehadiran beberapa hari kerja terakhir</p>
      </div>
      <div class="flex items-center gap-2">
        <button 
          @click="emit('refresh')"
          class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-600 rounded-lg border border-slate-200 text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer"
        >
          <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>Segarkan</span>
        </button>
        <button 
          @click="router.push('/my-attendance-history')"
          class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg border border-emerald-200 text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer"
        >
          <span>Buku Riwayat Lengkap</span>
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="historyLoading" class="text-center py-8 text-slate-400 text-xs">
      Memuat riwayat kehadiran...
    </div>

    <!-- Empty state -->
    <div v-else-if="historyList.length === 0" class="text-center py-8 text-slate-400 text-xs bg-slate-50 rounded-xl border border-dashed border-slate-200">
      Belum ada riwayat kehadiran tercatat.
    </div>

    <!-- Responsive Table -->
    <div v-else class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead>
          <tr class="text-slate-500 font-bold uppercase tracking-wider text-[11px] border-b border-slate-100">
            <th class="py-3 px-4">TANGGAL</th>
            <th class="py-3 px-4">JAM MASUK</th>
            <th class="py-3 px-4">JAM PULANG</th>
            <th class="py-3 px-4">STATUS</th>
            <th class="py-3 px-4">KETERANGAN</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="h in historyList" :key="h.id" class="hover:bg-slate-50/50 transition">
            <td class="py-3.5 px-4 font-semibold text-slate-800 whitespace-nowrap">
              <div class="flex items-center gap-2">
                <span 
                  class="w-2 h-2 rounded-full shrink-0"
                  :class="(h.status === 'terlambat' || h.is_terlambat) ? 'bg-amber-500' : 'bg-emerald-500'"
                ></span>
                <span>{{ formatDateDisplay(h.tanggal) }}</span>
              </div>
            </td>
            <td class="py-3.5 px-4 font-mono font-bold text-slate-700 whitespace-nowrap">
              {{ h.jam_masuk ? formatTimeDisplay(h.jam_masuk) : '-' }}
            </td>
            <td class="py-3.5 px-4 font-mono font-bold text-slate-700 whitespace-nowrap">
              {{ h.jam_keluar ? formatTimeDisplay(h.jam_keluar) : '-' }}
            </td>
            <td class="py-3.5 px-4 whitespace-nowrap">
              <span 
                class="px-2.5 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border"
                :class="{
                  'border-emerald-300 text-emerald-600 bg-emerald-50/40': h.status === 'hadir' && !h.is_terlambat,
                  'border-amber-300 text-amber-600 bg-amber-50/40': h.status === 'terlambat' || h.is_terlambat,
                  'border-blue-300 text-blue-600 bg-blue-50/40': h.status === 'izin',
                  'border-purple-300 text-purple-600 bg-purple-50/40': h.status === 'sakit',
                  'border-rose-300 text-rose-600 bg-rose-50/40': h.status === 'alpa'
                }"
              >
                {{ (h.status === 'terlambat' || h.is_terlambat) ? 'TERLAMBAT' : (h.status ? h.status.toUpperCase() : 'HADIR') }}
              </span>
            </td>
            <td class="py-3.5 px-4 text-slate-500 text-xs">
              {{ formatKeteranganDisplay(h) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
