<template>
  <div class="p-5 sm:p-7 space-y-6">
    <div class="border-b border-slate-100 pb-3">
      <h3 class="text-sm sm:text-base font-bold text-slate-900">Rekapitulasi Kehadiran Siswa</h3>
      <p class="text-xs text-slate-500">Rincian kehadiran ananda di kelas selama bulan berjalan</p>
    </div>

    <!-- 4 Grid Breakdown Status Kehadiran -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
      <div class="p-4 bg-emerald-50 rounded-2xl border border-emerald-100">
        <div class="text-2xl font-black text-emerald-800 font-mono">{{ attendanceSummary.hadir ?? 0 }}</div>
        <div class="text-xs font-bold text-emerald-700 mt-1">Hadir (Hari)</div>
      </div>
      <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100">
        <div class="text-2xl font-black text-amber-800 font-mono">{{ attendanceSummary.sakit ?? 0 }}</div>
        <div class="text-xs font-bold text-amber-700 mt-1">Sakit</div>
      </div>
      <div class="p-4 bg-sky-50 rounded-2xl border border-sky-100">
        <div class="text-2xl font-black text-sky-800 font-mono">{{ attendanceSummary.izin ?? 0 }}</div>
        <div class="text-xs font-bold text-sky-700 mt-1">Izin</div>
      </div>
      <div class="p-4 bg-rose-50 rounded-2xl border border-rose-100">
        <div class="text-2xl font-black text-rose-800 font-mono">{{ attendanceSummary.alpa ?? 0 }}</div>
        <div class="text-xs font-bold text-rose-700 mt-1">Tanpa Keterangan</div>
      </div>
    </div>

    <!-- Riwayat Presensi Terakhir -->
    <div class="space-y-3 pt-2">
      <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">
        Catatan Presensi 7 Hari Terakhir:
      </h4>

      <div v-if="recentAttendanceList.length > 0" class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="bg-slate-50 text-slate-600 font-bold border-b border-slate-100">
              <th class="py-2.5 px-3 rounded-l-xl">Tanggal</th>
              <th class="py-2.5 px-3">Status Kehadiran</th>
              <th class="py-2.5 px-3 rounded-r-xl">Catatan / Keterangan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="att in recentAttendanceList" :key="att.id" class="hover:bg-slate-50/60 transition">
              <td class="py-2.5 px-3 font-medium text-slate-800 whitespace-nowrap">{{ formatDate(att.tanggal) }}</td>
              <td class="py-2.5 px-3">
                <span 
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase"
                  :class="{
                    'bg-emerald-100 text-emerald-800': att.status === 'hadir',
                    'bg-amber-100 text-amber-800': att.status === 'sakit',
                    'bg-sky-100 text-sky-800': att.status === 'izin',
                    'bg-rose-100 text-rose-800': att.status === 'alpa'
                  }"
                >
                  {{ att.status }}
                </span>
              </td>
              <td class="py-2.5 px-3 text-slate-500">{{ att.keterangan || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="text-center py-6 text-slate-400 text-xs bg-slate-50 rounded-2xl">
        Belum ada riwayat harian yang tercatat minggu ini.
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { AttendanceSummary, AttendanceRecord } from './types';

defineProps<{
  attendanceSummary: AttendanceSummary;
  recentAttendanceList: AttendanceRecord[];
}>();

const formatDate = (dateStr: string | null | undefined): string => {
  if (!dateStr) return '-';
  try {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return String(dateStr).substring(0, 10);
    return d.toLocaleDateString('id-ID', {
      weekday: 'short',
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    });
  } catch {
    return String(dateStr);
  }
};
</script>
