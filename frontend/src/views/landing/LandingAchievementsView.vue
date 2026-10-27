<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

import LandingNavbar from '../../components/landing/LandingNavbar.vue'
import LandingFooter from '../../components/landing/LandingFooter.vue'
import LandingAchievementsSection from '../../components/landing/LandingAchievementsSection.vue'
import prestasiBg from '../../assets/prestasi.jpeg'

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
      <!-- Page Header Banner dengan Background Foto Santri Peraih Juara Haflatul Qur'an -->
      <section class="relative text-white py-20 sm:py-28 overflow-hidden">
        <!-- Background Image Prestasi -->
        <div class="absolute inset-0 z-0">
          <img 
            :src="prestasiBg" 
            alt="Santri Berprestasi SDI Bina Insan Mandiri Purwokerto" 
            class="w-full h-full object-cover object-center scale-105 filter blur-[1.5px]"
          />
          <!-- Overlay Transparan Lembut: Foto Santri Jelas & Teks Terbaca Tajam -->
          <div class="absolute inset-0 bg-gradient-to-r from-slate-950/85 via-slate-950/55 to-slate-950/35"></div>
          <div class="absolute inset-0 bg-gradient-to-t from-slate-950/65 via-transparent to-black/30"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
          <div class="flex items-center gap-2 text-xs font-semibold text-emerald-300 mb-3 drop-shadow">
            <router-link to="/" class="hover:text-white transition">Beranda</router-link>
            <span>/</span>
            <span class="text-white">Prestasi</span>
          </div>

          <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-white tracking-tight drop-shadow-lg">
            Prestasi Santri & Lembaga
          </h1>
          <p class="text-sm sm:text-base md:text-lg text-slate-100 max-w-3xl mt-3 leading-relaxed drop-shadow-md">
            Daftar raihan juara dan apresiasi membanggakan santri SDI Bina Insan Mandiri Purwokerto di kancah daerah hingga provinsi.
          </p>
        </div>
      </section>

      <!-- Achievements Content Section (Lengkap) -->
      <LandingAchievementsSection />
    </main>

    <LandingFooter @open-portal="handlePortalAccess" />
  </div>
</template>
