<script setup lang="ts">
import { ref } from 'vue'

defineProps<{
  userPortalLabel: string
}>()

const emit = defineEmits<{
  (e: 'navigate', id: string): void
  (e: 'open-portal'): void
}>()

const isMobileMenuOpen = ref(false)
const isProfileDropdownOpen = ref(false)
const isEducationDropdownOpen = ref(false)

const handleNav = (id: string) => {
  isMobileMenuOpen.value = false
  isProfileDropdownOpen.value = false
  isEducationDropdownOpen.value = false
  emit('navigate', id)
}
</script>

<template>
  <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-emerald-100 shadow-xs transition-all">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
      
      <!-- Logo & Brand Header -->
      <div class="flex items-center gap-3 cursor-pointer select-none" @click="handleNav('hero')">
        <img 
          src="/logo.png" 
          alt="Logo SDI Bina Insan Mandiri Purwokerto" 
          class="w-11 h-11 object-contain drop-shadow-xs hover:scale-105 transition-transform"
        />
        <div>
          <div class="flex items-center gap-2">
            <span class="font-black text-lg sm:text-xl text-slate-900 tracking-tight">SIM-BIM</span>
            <span class="px-2 py-0.5 text-[9px] font-black rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase tracking-wider">
              Smart School
            </span>
          </div>
          <p class="text-xs font-bold text-emerald-700 leading-tight">SDI Bina Insan Mandiri Purwokerto</p>
        </div>
      </div>

      <!-- Desktop Navigation Links -->
      <nav class="hidden lg:flex items-center gap-7 text-sm font-semibold text-slate-600">
        <!-- 0. Beranda -->
        <button 
          @click="handleNav('hero')" 
          class="hover:text-emerald-700 transition-colors cursor-pointer py-2"
        >
          Beranda
        </button>

        <!-- 1. Profil (Dropdown) -->
        <div 
          class="relative group"
          @mouseenter="isProfileDropdownOpen = true"
          @mouseleave="isProfileDropdownOpen = false"
        >
          <button 
            @click="handleNav('profil')"
            class="flex items-center gap-1.5 hover:text-emerald-700 transition-colors cursor-pointer py-2"
          >
            <span>Profil</span>
            <svg class="w-4 h-4 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>

          <!-- Dropdown Menu Profil -->
          <transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="transform opacity-0 scale-95 -translate-y-1"
            enter-to-class="transform opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="transform opacity-100 scale-100 translate-y-0"
            leave-to-class="transform opacity-0 scale-95 -translate-y-1"
          >
            <div 
              v-show="isProfileDropdownOpen"
              class="absolute left-0 top-full w-56 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 px-1 text-xs font-medium z-50 space-y-0.5"
            >
              <button 
                @click="handleNav('profil-singkat')"
                class="w-full text-left px-3.5 py-2.5 rounded-xl hover:bg-emerald-50 hover:text-emerald-800 transition flex items-center gap-2.5 cursor-pointer"
              >
                <span class="text-emerald-600">🏛️</span>
                <div>
                  <div class="font-bold text-slate-800">Profil Singkat</div>
                  <div class="text-[10px] text-slate-400">Sejarah & identitas sekolah</div>
                </div>
              </button>
              <button 
                @click="handleNav('visi-misi')"
                class="w-full text-left px-3.5 py-2.5 rounded-xl hover:bg-emerald-50 hover:text-emerald-800 transition flex items-center gap-2.5 cursor-pointer"
              >
                <span class="text-teal-600">🎯</span>
                <div>
                  <div class="font-bold text-slate-800">Visi & Misi</div>
                  <div class="text-[10px] text-slate-400">Arah & nilai dasar BIM</div>
                </div>
              </button>
              <button 
                @click="handleNav('tenaga-pendidik')"
                class="w-full text-left px-3.5 py-2.5 rounded-xl hover:bg-emerald-50 hover:text-emerald-800 transition flex items-center gap-2.5 cursor-pointer"
              >
                <span class="text-amber-600">👨‍🏫</span>
                <div>
                  <div class="font-bold text-slate-800">Tenaga Pendidik</div>
                  <div class="text-[10px] text-slate-400">Guru & asatidz berdedikasi</div>
                </div>
              </button>
              <button 
                @click="handleNav('janji-siswa')"
                class="w-full text-left px-3.5 py-2.5 rounded-xl hover:bg-emerald-50 hover:text-emerald-800 transition flex items-center gap-2.5 cursor-pointer"
              >
                <span class="text-emerald-600">📜</span>
                <div>
                  <div class="font-bold text-slate-800">Janji Siswa</div>
                  <div class="text-[10px] text-slate-400">6 Ikrar santri peradaban</div>
                </div>
              </button>
            </div>
          </transition>
        </div>

        <!-- 2. Pendidikan (Dropdown) -->
        <div 
          class="relative group"
          @mouseenter="isEducationDropdownOpen = true"
          @mouseleave="isEducationDropdownOpen = false"
        >
          <button 
            @click="handleNav('pendidikan')"
            class="flex items-center gap-1.5 hover:text-emerald-700 transition-colors cursor-pointer py-2"
          >
            <span>Pendidikan</span>
            <svg class="w-4 h-4 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>

          <!-- Dropdown Menu Pendidikan -->
          <transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="transform opacity-0 scale-95 -translate-y-1"
            enter-to-class="transform opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="transform opacity-100 scale-100 translate-y-0"
            leave-to-class="transform opacity-0 scale-95 -translate-y-1"
          >
            <div 
              v-show="isEducationDropdownOpen"
              class="absolute left-0 top-full w-64 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 px-1 text-xs font-medium z-50 space-y-0.5"
            >
              <button 
                @click="handleNav('pendidikan-fitrah')"
                class="w-full text-left px-3.5 py-2.5 rounded-xl hover:bg-emerald-50 hover:text-emerald-800 transition flex items-center gap-2.5 cursor-pointer"
              >
                <span class="text-emerald-600">🌱</span>
                <div>
                  <div class="font-bold text-slate-800">Pendidikan Berbasis Fitrah</div>
                  <div class="text-[10px] text-slate-400">Menyuburkan potensi alami anak</div>
                </div>
              </button>
              <button 
                @click="handleNav('membangun-peradaban')"
                class="w-full text-left px-3.5 py-2.5 rounded-xl hover:bg-emerald-50 hover:text-emerald-800 transition flex items-center gap-2.5 cursor-pointer"
              >
                <span class="text-teal-600">🌍</span>
                <div>
                  <div class="font-bold text-slate-800">Membangun Peradaban</div>
                  <div class="text-[10px] text-slate-400">Tujuan hakiki pendidikan islam</div>
                </div>
              </button>
            </div>
          </transition>
        </div>

        <!-- 3. Artikel -->
        <button 
          @click="handleNav('artikel')" 
          class="hover:text-emerald-700 transition-colors cursor-pointer py-2"
        >
          Artikel
        </button>

        <!-- 4. Galeri -->
        <button 
          @click="handleNav('galeri')" 
          class="hover:text-emerald-700 transition-colors cursor-pointer py-2"
        >
          Galeri
        </button>

        <!-- 5. Prestasi -->
        <button 
          @click="handleNav('prestasi')" 
          class="hover:text-emerald-700 transition-colors cursor-pointer py-2"
        >
          Prestasi
        </button>

        <!-- 6. Info SPMB -->
        <button 
          @click="handleNav('spmb')" 
          class="relative hover:text-emerald-700 transition-colors cursor-pointer py-2 flex items-center gap-1.5"
        >
          <span>Info SPMB</span>
          <span class="px-1.5 py-0.2 rounded-full text-[9px] font-black bg-amber-400 text-amber-950 uppercase tracking-tight">
            Baru
          </span>
        </button>
      </nav>

      <!-- CTA Masuk Sistem SIM-BIM -->
      <div class="hidden sm:flex items-center gap-3">
        <button 
          @click="emit('open-portal')"
          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white text-xs sm:text-sm font-bold shadow-md shadow-emerald-700/20 hover:from-emerald-700 hover:to-teal-800 transition-all hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 cursor-pointer"
        >
          <span>{{ userPortalLabel }}</span>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
          </svg>
        </button>
      </div>

      <!-- Hamburger Mobile Menu Button -->
      <div class="flex items-center lg:hidden">
        <button 
          @click="isMobileMenuOpen = !isMobileMenuOpen"
          class="p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer"
          aria-label="Buka Menu"
        >
          <svg v-if="!isMobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
          <svg v-else class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Mobile Dropdown Navigation -->
    <div 
      v-if="isMobileMenuOpen" 
      class="lg:hidden border-t border-slate-200 bg-white/95 backdrop-blur-md px-5 pt-3 pb-6 space-y-2 text-sm shadow-xl"
    >
      <button @click="handleNav('hero')" class="block w-full text-left py-2 font-semibold text-slate-700 hover:text-emerald-700">
        Beranda
      </button>

      <!-- Mobile Profil Accordion -->
      <div class="py-1 border-y border-slate-100">
        <div class="font-bold text-xs uppercase tracking-wider text-slate-400 py-1">1. Profil Sekolah</div>
        <div class="pl-3 space-y-1.5 pt-1">
          <button @click="handleNav('profil-singkat')" class="block w-full text-left py-1 text-slate-600 hover:text-emerald-700 text-xs font-medium">
            • Profil Singkat Sekolah
          </button>
          <button @click="handleNav('visi-misi')" class="block w-full text-left py-1 text-slate-600 hover:text-emerald-700 text-xs font-medium">
            • Visi & Misi
          </button>
          <button @click="handleNav('tenaga-pendidik')" class="block w-full text-left py-1 text-slate-600 hover:text-emerald-700 text-xs font-medium">
            • Tenaga Pendidik & Kependidikan
          </button>
          <button @click="handleNav('janji-siswa')" class="block w-full text-left py-1 text-slate-600 hover:text-emerald-700 text-xs font-medium">
            • Janji Siswa SDI BIM
          </button>
        </div>
      </div>

      <!-- Mobile Pendidikan Accordion -->
      <div class="py-1 border-b border-slate-100">
        <div class="font-bold text-xs uppercase tracking-wider text-slate-400 py-1">2. Pendidikan</div>
        <div class="pl-3 space-y-1.5 pt-1">
          <button @click="handleNav('pendidikan-fitrah')" class="block w-full text-left py-1 text-slate-600 hover:text-emerald-700 text-xs font-medium">
            • Pendidikan Berbasis Fitrah
          </button>
          <button @click="handleNav('membangun-peradaban')" class="block w-full text-left py-1 text-slate-600 hover:text-emerald-700 text-xs font-medium">
            • Tujuan Pendidikan: Membangun Peradaban
          </button>
        </div>
      </div>

      <button @click="handleNav('artikel')" class="block w-full text-left py-2 font-semibold text-slate-700 hover:text-emerald-700">
        3. Artikel & Warta
      </button>
      <button @click="handleNav('galeri')" class="block w-full text-left py-2 font-semibold text-slate-700 hover:text-emerald-700">
        4. Galeri Kegiatan
      </button>
      <button @click="handleNav('prestasi')" class="block w-full text-left py-2 font-semibold text-slate-700 hover:text-emerald-700">
        5. Prestasi Santri
      </button>
      <button @click="handleNav('spmb')" class="block w-full text-left py-2 font-bold text-emerald-800 hover:text-emerald-950 flex items-center justify-between">
        <span>6. Info SPMB 2026/2027</span>
        <span class="px-2 py-0.5 rounded-full text-[10px] bg-amber-400 text-amber-950 font-bold">Dibuka</span>
      </button>

      <div class="pt-3">
        <button 
          @click="emit('open-portal')"
          class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20 text-xs"
        >
          <span>{{ userPortalLabel }}</span>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
          </svg>
        </button>
      </div>
    </div>
  </header>
</template>
