<script setup lang="ts">
import heroBg from '../../assets/gradasi.jpeg'

defineProps<{
  userPortalLabel: string
}>()

const emit = defineEmits<{
  (e: 'navigate', id: string): void
  (e: 'open-portal'): void
}>()
</script>

<template>
  <section id="hero" class="relative overflow-hidden pt-16 pb-24 md:pt-24 md:pb-36 text-white">
    <!-- Background Image Foto Dokumentasi Siswa SDI BIM -->
    <div class="absolute inset-0 z-0">
      <img 
        :src="heroBg" 
        alt="Siswa SDI Bina Insan Mandiri Purwokerto" 
        class="w-full h-full object-cover object-center scale-105 filter blur-[2.5px]"
      />
      <!-- Soft Vignette & Subtle Overlay agar Teks di Tengah Terbaca Nyaman -->
      <div class="absolute inset-0 bg-slate-950/45"></div>
      <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-slate-950/50"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
      <div class="max-w-4xl mx-auto space-y-6 text-center">
        
        <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-white/10 border border-white/20 backdrop-blur-md text-emerald-200 text-xs sm:text-sm font-semibold shadow-xs">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          <span>Sekolah Dasar Islam Unggulan • Purwokerto</span>
        </div>

        <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black tracking-tight leading-tight sm:leading-tight drop-shadow-lg mx-auto">
          Mewujudkan Generasi <br class="hidden sm:inline" />
          <span class="bg-gradient-to-r from-emerald-300 via-teal-200 to-amber-200 bg-clip-text text-transparent">
            Qur'ani & Unggul
          </span>
          Berbasis Deep Learning
        </h1>

        <p class="text-sm sm:text-base md:text-lg text-slate-100 max-w-2xl mx-auto font-normal leading-relaxed drop-shadow-md">
          Selamat datang di portal resmi <strong>SDI Bina Insan Mandiri Purwokerto</strong>. 
          Mendidik dengan cinta, menerapkan pembelajaran mendalam (<em>deep learning</em>), dan membangun karakter peradaban islam yang kokoh, mandiri, dan berwawasan luas.
        </p>

        <!-- Action Buttons (Centered) -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
          <router-link 
            to="/spmb"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-3 px-8 py-3.5 rounded-2xl bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black text-sm sm:text-base shadow-xl shadow-amber-500/20 transition-all hover:-translate-y-0.5 active:translate-y-0"
          >
            <span>Info Pendaftaran SPMB</span>
            <svg class="w-5 h-5 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
          </router-link>

          <button 
            @click="emit('open-portal')"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-7 py-3.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm sm:text-base border border-emerald-400/40 shadow-lg shadow-emerald-950/30 transition-all cursor-pointer"
          >
            <span>{{ userPortalLabel }}</span>
            <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
          </button>
        </div>

      </div>
    </div>
  </section>
</template>
