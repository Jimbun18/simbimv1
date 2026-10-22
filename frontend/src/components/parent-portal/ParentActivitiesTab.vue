<template>
  <div class="p-5 sm:p-7 space-y-4">
    <div class="border-b border-slate-100 pb-3">
      <h3 class="text-sm sm:text-base font-bold text-slate-900">Kegiatan Harian Kelas Ananda</h3>
      <p class="text-xs text-slate-500">Aktivitas pembelajaran tematik, ibadah bersama, dan proyek kelas yang berlangsung</p>
    </div>

    <div v-if="latestActivitiesList.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div 
        v-for="act in latestActivitiesList" 
        :key="act.id" 
        class="p-5 rounded-2xl border border-slate-100 bg-slate-50/50 space-y-2.5 flex flex-col justify-between"
      >
        <div>
          <div class="flex items-center justify-between text-xs text-slate-400 mb-1.5">
            <span class="bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-bold uppercase text-[10px]">{{ act.kategori }}</span>
            <span class="font-mono">{{ formatDate(act.tanggal) }}</span>
          </div>
          <h4 class="font-bold text-slate-900 text-sm">{{ act.judul }}</h4>
          <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ act.deskripsi }}</p>
        </div>
        <div class="pt-2 border-t border-slate-200/60 text-[11px] text-slate-400">
          <span>Guru Pembimbing: <strong class="text-slate-700">{{ act.teacher?.nama_lengkap || 'Guru Kelas' }}</strong></span>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-12 text-slate-400 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
      <p class="text-xs">Belum ada agenda kegiatan kelas yang dipublikasikan baru-baru ini.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ActivityItem } from './types';

defineProps<{
  latestActivitiesList: ActivityItem[];
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
