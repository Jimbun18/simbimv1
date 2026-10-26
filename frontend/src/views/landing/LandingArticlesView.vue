<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

import LandingNavbar from '../../components/landing/LandingNavbar.vue'
import LandingFooter from '../../components/landing/LandingFooter.vue'
import LandingArticlesSection from '../../components/landing/LandingArticlesSection.vue'

const auth = useAuthStore()
const router = useRouter()

onMounted(() => {
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

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
          <div class="flex items-center gap-2 text-xs font-semibold text-emerald-300 mb-3">
            <router-link to="/" class="hover:text-white transition">Beranda</router-link>
            <span>/</span>
            <span class="text-white">Artikel & Warta</span>
          </div>

          <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-white tracking-tight">
            Artikel, Opini & Warta Sekolah
          </h1>
          <p class="text-sm sm:text-base text-emerald-100/90 max-w-3xl mt-3 leading-relaxed">
            Wawasan seputar pembelajaran mendalam (deep learning), edukasi Al-Qur'an ramah anak, dan liputan aktivitas KBM tematik SDI Bina Insan Mandiri Purwokerto.
          </p>
        </div>
      </section>

      <!-- Articles Content Section (Lengkap) -->
      <LandingArticlesSection />
    </main>

    <LandingFooter @open-portal="handlePortalAccess" />
  </div>
</template>
