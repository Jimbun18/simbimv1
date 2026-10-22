<template>
  <div class="p-5 sm:p-7 space-y-4">
    <div class="border-b border-slate-100 pb-3">
      <h3 class="text-sm sm:text-base font-bold text-slate-900">Pengumuman & Informasi Resmi Sekolah</h3>
      <p class="text-xs text-slate-500">Pemberitahuan resmi, surat edaran, dan kalender kegiatan dari pihak sekolah</p>
    </div>

    <div v-if="announcementList.length > 0" class="space-y-4">
      <div 
        v-for="anc in announcementList" 
        :key="anc.id" 
        class="p-5 rounded-2xl border transition space-y-2.5"
        :class="anc.is_pinned ? 'bg-amber-50/40 border-amber-200 shadow-2xs' : 'bg-white border-slate-200/80 shadow-xs'"
      >
        <div class="flex items-center justify-between text-xs">
          <div class="flex items-center gap-2">
            <span v-if="anc.is_pinned" class="bg-amber-100 text-amber-900 font-extrabold text-[10px] px-2 py-0.5 rounded">
              📌 SEMATAN
            </span>
            <span class="bg-emerald-100 text-emerald-800 font-bold text-[10px] px-2 py-0.5 rounded uppercase">
              {{ anc.kategori || 'Umum' }}
            </span>
          </div>
          <span class="text-slate-400 font-mono text-[11px]">{{ formatDate(anc.tgl_terbit) }}</span>
        </div>

        <h4 class="font-bold text-slate-900 text-base">{{ anc.judul }}</h4>
        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed whitespace-pre-line">{{ anc.konten }}</p>
      </div>
    </div>

    <div v-else class="text-center py-12 text-slate-400 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
      <p class="text-xs">Belum ada pengumuman baru untuk wali murid saat ini.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { AnnouncementItem } from './types';

defineProps<{
  announcementList: AnnouncementItem[];
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
