<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const props = defineProps<{
  userPortalLabel?: string
}>()

const emit = defineEmits<{
  (e: 'open-portal'): void
}>()

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const isMobileMenuOpen = ref(false)

const getPortalLabel = () => {
  if (props.userPortalLabel) return props.userPortalLabel
  if (!auth.isAuthenticated) return 'Masuk SIM-BIM'
  if (auth.isAdmin) return 'Buka Dashboard Admin'
  if (auth.isGuru) return 'Buka Presensi & KBM'
  if (auth.isWali) return 'Buka Portal Wali Murid'
  return 'Buka Dashboard'
}

const navigateTo = (path: string) => {
  isMobileMenuOpen.value = false
  router.push(path)
}

const handlePortalClick = () => {
  isMobileMenuOpen.value = false
  emit('open-portal')
}
</script>

<template>
  <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-emerald-100 shadow-xs transition-all">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
      
      <!-- Logo & Brand Header -->
      <router-link to="/" class="flex items-center gap-3 select-none">
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
      </router-link>

      <!-- Desktop Navigation Links -->
      <nav class="hidden lg:flex items-center gap-7 text-sm font-semibold text-slate-600">
        <!-- 0. Beranda -->
        <router-link 
          to="/" 
          class="hover:text-emerald-700 transition-colors py-2 relative"
          :class="route.path === '/' ? 'text-emerald-700 font-bold' : ''"
        >
          <span>Beranda</span>
          <span v-if="route.path === '/'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-emerald-600 rounded-full"></span>
        </router-link>

        <!-- 1. Profil (Link Langsung) -->
        <router-link 
          to="/profil"
          class="hover:text-emerald-700 transition-colors py-2 relative"
          :class="route.path.startsWith('/profil') ? 'text-emerald-700 font-bold' : ''"
        >
          <span>Profil</span>
          <span v-if="route.path.startsWith('/profil')" class="absolute bottom-0 left-0 right-0 h-0.5 bg-emerald-600 rounded-full"></span>
        </router-link>

        <!-- 2. Pendidikan (Link Langsung) -->
        <router-link 
          to="/pendidikan"
          class="hover:text-emerald-700 transition-colors py-2 relative"
          :class="route.path.startsWith('/pendidikan') ? 'text-emerald-700 font-bold' : ''"
        >
          <span>Pendidikan</span>
          <span v-if="route.path.startsWith('/pendidikan')" class="absolute bottom-0 left-0 right-0 h-0.5 bg-emerald-600 rounded-full"></span>
        </router-link>

        <!-- 3. Artikel -->
        <router-link 
          to="/artikel" 
          class="hover:text-emerald-700 transition-colors py-2 relative"
          :class="route.path.startsWith('/artikel') ? 'text-emerald-700 font-bold' : ''"
        >
          <span>Artikel</span>
          <span v-if="route.path.startsWith('/artikel')" class="absolute bottom-0 left-0 right-0 h-0.5 bg-emerald-600 rounded-full"></span>
        </router-link>

        <!-- 4. Galeri -->
        <router-link 
          to="/galeri" 
          class="hover:text-emerald-700 transition-colors py-2 relative"
          :class="route.path.startsWith('/galeri') ? 'text-emerald-700 font-bold' : ''"
        >
          <span>Galeri</span>
          <span v-if="route.path.startsWith('/galeri')" class="absolute bottom-0 left-0 right-0 h-0.5 bg-emerald-600 rounded-full"></span>
        </router-link>

        <!-- 5. Prestasi -->
        <router-link 
          to="/prestasi" 
          class="hover:text-emerald-700 transition-colors py-2 relative"
          :class="route.path.startsWith('/prestasi') ? 'text-emerald-700 font-bold' : ''"
        >
          <span>Prestasi</span>
          <span v-if="route.path.startsWith('/prestasi')" class="absolute bottom-0 left-0 right-0 h-0.5 bg-emerald-600 rounded-full"></span>
        </router-link>

        <!-- 6. Info SPMB -->
        <router-link 
          to="/spmb" 
          class="hover:text-emerald-700 transition-colors py-2 flex items-center gap-1.5 relative"
          :class="route.path.startsWith('/spmb') ? 'text-emerald-700 font-bold' : ''"
        >
          <span>Info SPMB</span>
          <span class="px-1.5 py-0.2 rounded-full text-[9px] font-black bg-amber-400 text-amber-950 uppercase tracking-tight">
            Baru
          </span>
          <span v-if="route.path.startsWith('/spmb')" class="absolute bottom-0 left-0 right-0 h-0.5 bg-emerald-600 rounded-full"></span>
        </router-link>
      </nav>

      <!-- CTA Masuk Sistem SIM-BIM -->
      <div class="hidden sm:flex items-center gap-3">
        <button 
          @click="handlePortalClick"
          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white text-xs sm:text-sm font-bold shadow-md shadow-emerald-700/20 hover:from-emerald-700 hover:to-teal-800 transition-all hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 cursor-pointer"
        >
          <span>{{ getPortalLabel() }}</span>
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
      <button @click="navigateTo('/')" class="block w-full text-left py-2 font-semibold text-slate-700 hover:text-emerald-700">
        Beranda
      </button>

      <button @click="navigateTo('/profil')" class="block w-full text-left py-2 font-semibold text-slate-700 hover:text-emerald-700">
        Profil
      </button>

      <button @click="navigateTo('/pendidikan')" class="block w-full text-left py-2 font-semibold text-slate-700 hover:text-emerald-700">
        Pendidikan
      </button>

      <button @click="navigateTo('/artikel')" class="block w-full text-left py-2 font-semibold text-slate-700 hover:text-emerald-700">
        Artikel & Warta
      </button>
      <button @click="navigateTo('/galeri')" class="block w-full text-left py-2 font-semibold text-slate-700 hover:text-emerald-700">
        Galeri Kegiatan
      </button>
      <button @click="navigateTo('/prestasi')" class="block w-full text-left py-2 font-semibold text-slate-700 hover:text-emerald-700">
        Prestasi Santri
      </button>
      <button @click="navigateTo('/spmb')" class="block w-full text-left py-2 font-bold text-emerald-800 hover:text-emerald-950 flex items-center justify-between">
        <span>Info SPMB 2026/2027</span>
        <span class="px-2 py-0.5 rounded-full text-[10px] bg-amber-400 text-amber-950 font-bold">Dibuka</span>
      </button>

      <div class="pt-3">
        <button 
          @click="handlePortalClick"
          class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20 text-xs"
        >
          <span>{{ getPortalLabel() }}</span>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
          </svg>
        </button>
      </div>
    </div>
  </header>
</template>
