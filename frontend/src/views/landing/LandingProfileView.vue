<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

import LandingNavbar from '../../components/landing/LandingNavbar.vue'
import LandingFooter from '../../components/landing/LandingFooter.vue'
import LandingProfileSection from '../../components/landing/LandingProfileSection.vue'
import profileBg from '../../assets/depan.jpeg'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const activeTab = ref<'profil' | 'visi' | 'guru' | 'janji'>('profil')

// Sync tab from route query
const syncTabFromQuery = () => {
  const q = route.query.tab as string
  if (q && ['profil', 'visi', 'guru', 'janji'].includes(q)) {
    activeTab.value = q as any
  }
}

watch(() => route.query.tab, () => {
  syncTabFromQuery()
})

onMounted(() => {
  syncTabFromQuery()
  window.scrollTo({ top: 0, behavior: 'smooth' })
})

const handlePortalAccess = () => {
  if (!auth.isAuthenticated) {
    router.push('/login')
  } else if (auth.isAdmin) {
    router.push('/dashboard')
  } else if (auth.isGuru) {
    router.push('/presensi')
  } else if (auth.isWali) {
    router.push('/portal-wali')
  } else {
    router.push('/dashboard')
  }
}
</script>

<template>
  <div class="min-h-screen bg-white text-slate-800 font-sans selection:bg-emerald-500 selection:text-white">
    <LandingNavbar @open-portal="handlePortalAccess" />

    <main>
      <!-- Page Header Banner dengan Background Foto Gedung Depan SDI BIM -->
      <section class="relative text-white py-20 sm:py-28 overflow-hidden">
        <!-- Background Image Gedung Depan -->
        <div class="absolute inset-0 z-0">
          <img 
            :src="profileBg" 
            alt="Gedung SDI Bina Insan Mandiri Purwokerto" 
            class="w-full h-full object-cover object-center scale-105"
          />
          <!-- Overlay Transparan Lembut: Foto Gedung Jelas & Teks Terbaca Tajam -->
          <div class="absolute inset-0 bg-gradient-to-r from-slate-950/85 via-slate-950/55 to-slate-950/35"></div>
          <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-black/30"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
          <div class="flex items-center gap-2 text-xs font-semibold text-emerald-300 mb-3 drop-shadow">
            <router-link to="/" class="hover:text-white transition">Beranda</router-link>
            <span>/</span>
            <span class="text-white">Profil Sekolah</span>
          </div>

          <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-white tracking-tight drop-shadow-lg">
            Profil SDI Bina Insan Mandiri
          </h1>
          <p class="text-sm sm:text-base md:text-lg text-slate-100 max-w-3xl mt-3 leading-relaxed drop-shadow-md">
            Menelusuri sejarah pendirian, visi luhur, dedikasi tenaga pendidik, dan 6 ikrar janji santri peradaban di SDI Bina Insan Mandiri Purwokerto.
          </p>
        </div>
      </section>

      <!-- Profile Content Section (Lengkap) -->
      <LandingProfileSection :active-tab-prop="activeTab" />
    </main>

    <LandingFooter @open-portal="handlePortalAccess" />
  </div>
</template>
