<template>
  <div class="p-5 sm:p-7 space-y-4">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
      <div>
        <h3 class="text-sm sm:text-base font-bold text-slate-900">Catatan Capaian Belajar & Tahfidz Al-Qur'an</h3>
        <p class="text-xs text-slate-500">Pantau perkembangan hafalan surat, sholat, dan pembiasaan akhlak ananda</p>
      </div>
      <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-100">
        SDI Bina Insan Mandiri
      </span>
    </div>

    <div v-if="latestProgressList.length > 0" class="divide-y divide-slate-100">
      <div 
        v-for="p in latestProgressList" 
        :key="p.id" 
        class="py-4 flex flex-col sm:flex-row sm:items-start justify-between gap-3 hover:bg-slate-50/70 p-3 rounded-2xl transition"
      >
        <div class="space-y-1.5 flex-1">
          <div class="flex flex-wrap items-center gap-2">
            <span 
              class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase"
              :class="{
                'bg-emerald-100 text-emerald-800': p.kategori === 'tahfidz',
                'bg-blue-100 text-blue-800': p.kategori === 'sholat',
                'bg-purple-100 text-purple-800': p.kategori === 'karakter',
                'bg-amber-100 text-amber-800': p.kategori === 'akademik',
                'bg-gray-100 text-gray-800': p.kategori === 'ekskul'
              }"
            >
              {{ p.kategori }}
            </span>
            <span class="font-bold text-slate-900 text-sm">{{ p.capaian }}</span>
            <span v-if="p.nilai" class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
              Nilai: {{ p.nilai }}
            </span>
          </div>
          <p class="text-xs text-slate-600 leading-relaxed pl-1">
            {{ p.catatan || 'Capaian materi dicatat oleh ustadz pembimbing, alhamdulillah ananda menunjukkan ketekunan belajar.' }}
          </p>
          <div class="text-[11px] text-slate-400 pl-1">
            <span>Pembimbing: {{ p.teacher?.nama_lengkap || 'Ustadz/Ustadzah Pengampu' }}</span>
          </div>
        </div>

        <div class="sm:text-right shrink-0">
          <span class="text-xs font-mono font-medium text-slate-500 bg-slate-100 px-2.5 py-1 rounded-xl">
            {{ formatDate(p.tanggal) }}
          </span>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-12 text-slate-400 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
      <p class="text-xs">Belum ada catatan jurnal progres untuk ananda pada periode ini.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ProgressItem } from './types';

defineProps<{
  latestProgressList: ProgressItem[];
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
