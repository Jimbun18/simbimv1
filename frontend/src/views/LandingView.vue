<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

import LandingNavbar from '../components/landing/LandingNavbar.vue'
import LandingHero from '../components/landing/LandingHero.vue'
import LandingProfileSection from '../components/landing/LandingProfileSection.vue'
import LandingEducationSection from '../components/landing/LandingEducationSection.vue'
import LandingArticlesSection from '../components/landing/LandingArticlesSection.vue'
import LandingGallerySection from '../components/landing/LandingGallerySection.vue'
import LandingAchievementsSection from '../components/landing/LandingAchievementsSection.vue'
import LandingSpmbSection from '../components/landing/LandingSpmbSection.vue'
import LandingFooter from '../components/landing/LandingFooter.vue'

const auth = useAuthStore()
const router = useRouter()

const targetProfileTab = ref<'profil' | 'visi' | 'guru' | 'janji'>('profil')

// Label dinamis tombol portal SIM-BIM
const userPortalLabel = computed(() => {
  if (!auth.isAuthenticated) return 'Masuk SIM-BIM'
  if (auth.isAdmin) return 'Buka Dashboard Admin'
  if (auth.isGuru) return 'Buka Presensi & KBM'
  if (auth.isWali) return 'Buka Portal Wali Murid'
  return 'Buka Dashboard'
})

// Handler akses portal
const handlePortalAccess = () => {
  if (!auth.isAuthenticated) {
    router.push('/login')
    return
  }
  if (auth.isAdmin) {
    router.push('/dashboard')
  } else if (auth.isGuru) {
    router.push('/presensi')
  } else if (auth.isWali) {
    router.push('/portal-wali')
  } else {
    router.push('/dashboard')
  }
}

// Smooth scroll ke section tujuan
const scrollToSection = (id: string) => {
  if (id === 'profil-singkat') {
    targetProfileTab.value = 'profil'
    id = 'profil'
  } else if (id === 'visi-misi') {
    targetProfileTab.value = 'visi'
    id = 'profil'
  } else if (id === 'tenaga-pendidik') {
    targetProfileTab.value = 'guru'
    id = 'profil'
  } else if (id === 'janji-siswa') {
    targetProfileTab.value = 'janji'
    id = 'profil'
  }
  const el = document.getElementById(id)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}
</script>

<template>
  <div class="min-h-screen bg-white text-slate-800 font-sans selection:bg-emerald-500 selection:text-white scroll-smooth">
    
    <!-- 1. STICKY TOP NAVBAR -->
    <LandingNavbar 
      :user-portal-label="userPortalLabel"
      @navigate="scrollToSection"
      @open-portal="handlePortalAccess"
    />

    <main>
      <!-- 0. BERANDA (HERO SECTION) -->
      <LandingHero 
        :user-portal-label="userPortalLabel"
        @navigate="scrollToSection"
        @open-portal="handlePortalAccess"
      />

      <!-- 1. PROFIL (Profil Singkat, Visi Misi, Tenaga Pendidik, Janji Siswa) -->
      <LandingProfileSection :active-tab-prop="targetProfileTab" />

      <!-- 2. PENDIDIKAN (Pendidikan Berbasis Fitrah & Membangun Peradaban) -->
      <LandingEducationSection />

      <!-- 3. ARTIKEL (Warta Sekolah & Parenting Fitrah) -->
      <LandingArticlesSection />

      <!-- 4. GALERI (Dokumentasi Kegiatan Siswa & Lightbox) -->
      <LandingGallerySection />

      <!-- 5. PRESTASI (Pencapaian Santri & Testimoni Wali Murid) -->
      <LandingAchievementsSection />

      <!-- 6. INFO SPMB (Penerimaan Murid Baru 2026/2027) -->
      <LandingSpmbSection />
    </main>

    <!-- FOOTER INFORMASI LENGKAP -->
    <LandingFooter 
      :user-portal-label="userPortalLabel"
      @navigate="scrollToSection"
      @open-portal="handlePortalAccess"
    />

  </div>
</template>

<style>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes scaleUp {
  from {
    opacity: 0;
    transform: scale(0.96);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.25s ease-out forwards;
}

.animate-scaleUp {
  animation: scaleUp 0.2s ease-out forwards;
}
</style>
