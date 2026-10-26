<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

import LandingNavbar from '../../components/landing/LandingNavbar.vue'
import LandingFooter from '../../components/landing/LandingFooter.vue'
import LandingProfileSection from '../../components/landing/LandingProfileSection.vue'

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
      <!-- Page Header Banner -->
      <section class="bg-gradient-to-b from-[#032e23] via-[#044737] to-[#02261d] text-white py-16 sm:py-20 relative overflow-hidden">
        <div class="absolute -top-24 -left-24 w-80 h-80 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-20 -right-20 w-80 h-80 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
          <div class="flex items-center gap-2 text-xs font-semibold text-emerald-300 mb-3">
            <router-link to="/" class="hover:text-white transition">Beranda</router-link>
            <span>/</span>
            <span class="text-white">Profil Sekolah</span>
          </div>

          <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-white tracking-tight">
            Profil SDI Bina Insan Mandiri
          </h1>
          <p class="text-sm sm:text-base text-emerald-100/90 max-w-3xl mt-3 leading-relaxed">
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
