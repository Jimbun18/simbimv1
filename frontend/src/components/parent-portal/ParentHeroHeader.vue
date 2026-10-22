<template>
  <div class="bg-gradient-to-br from-emerald-900 via-emerald-800 to-teal-950 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden border border-emerald-700/40">
    <!-- Decorative ambient glows -->
    <div class="absolute -right-12 -top-12 w-48 h-48 bg-emerald-500/15 rounded-full blur-2xl pointer-events-none"></div>
    <div class="absolute -left-12 -bottom-12 w-48 h-48 bg-teal-500/15 rounded-full blur-2xl pointer-events-none"></div>

    <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <div>
        <div class="flex items-center gap-2">
          <span class="px-3.5 py-1 bg-white/10 backdrop-blur-md rounded-full text-xs font-bold uppercase tracking-wider text-emerald-200 border border-white/20">
            Portal Terpadu Wali Murid
          </span>
          <span class="text-xs text-emerald-300 font-semibold">• TA 2024/2025</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight mt-2">
          Bapak/Ibu {{ userName }}
        </h1>
        <p class="text-xs sm:text-sm text-emerald-200/90 mt-1 font-medium">
          Pantau kehadiran, capaian tahfidz, kegiatan harian, dan administrasi ananda di SDI Bina Insan Mandiri Purwokerto.
        </p>
      </div>

      <div class="hidden sm:flex items-center gap-2 px-3.5 py-2 bg-black/20 backdrop-blur-md rounded-2xl border border-white/10 text-xs text-emerald-200">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
        <span>Sistem Informasi Terhubung</span>
      </div>
    </div>

    <!-- Child Switcher Tabs (If parent has 1 or more children) -->
    <div v-if="children.length > 0" class="mt-6 pt-5 border-t border-white/10">
      <div class="text-[11px] font-bold text-emerald-300 uppercase tracking-wider mb-2.5">
        Pilih Ananda Tercinta:
      </div>
      <div class="flex gap-2.5 overflow-x-auto pb-1 scrollbar-thin">
        <button
          v-for="c in children"
          :key="c.id"
          type="button"
          @click="$emit('select-child', c.id)"
          :class="selectedChildId === c.id 
            ? 'bg-white text-emerald-950 font-black shadow-lg scale-102 border-white' 
            : 'bg-white/10 text-white hover:bg-white/20 border-white/15'"
          class="px-4 py-2.5 rounded-2xl text-xs whitespace-nowrap transition-all duration-200 flex items-center gap-2.5 border cursor-pointer"
        >
          <div 
            class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[10px]"
            :class="selectedChildId === c.id ? 'bg-emerald-100 text-emerald-800' : 'bg-white/20 text-white'"
          >
            {{ c.nama_lengkap.charAt(0) }}
          </div>
          <span>{{ c.nama_lengkap }}</span>
          <span 
            class="px-2 py-0.5 rounded-md text-[10px] font-bold"
            :class="selectedChildId === c.id ? 'bg-emerald-100 text-emerald-900' : 'bg-black/20 text-emerald-200'"
          >
            {{ c.school_class?.nama || c.school_class?.nama_kelas || '-' }}
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Child } from './types';

defineProps<{
  userName: string;
  children: Child[];
  selectedChildId: number | null;
}>();

defineEmits<{
  (e: 'select-child', id: number): void;
}>();
</script>
