<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

import LandingNavbar from '../components/landing/LandingNavbar.vue'
import LandingHero from '../components/landing/LandingHero.vue'
import LandingFeaturedProgramsSection from '../components/landing/LandingFeaturedProgramsSection.vue'
import LandingFooter from '../components/landing/LandingFooter.vue'

import { 
  teachersData, 
  fitrahPillars, 
  articlesData, 
  galleryItems, 
  achievementsData, 
  spmbWaves 
} from '../components/landing/landingData'

const auth = useAuthStore()
const router = useRouter()

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
    
    <!-- STICKY TOP NAVBAR -->
    <LandingNavbar @open-portal="handlePortalAccess" />

    <main>
      <!-- 0. HERO SECTION UTAMA -->
      <LandingHero 
        :user-portal-label="userPortalLabel"
        @open-portal="handlePortalAccess"
      />

      <!-- 1. RINGKASAN PROFIL SEKOLAH -->
      <section class="py-20 bg-white border-b border-slate-200/80 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
          
          <div 
            v-reveal="{ type: 'fade-up', delay: 50 }"
            class="flex flex-col md:flex-row md:items-end justify-between gap-4"
          >
            <div>
              <span class="px-3.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider">
                Profil Sekolah
              </span>
              <h2 class="mt-2 text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                Mendidik dengan Hati, Menumbuhkan Fitrah
              </h2>
              <p class="mt-1 text-xs sm:text-sm text-slate-500 max-w-2xl">
                Sekilas tentang SDI Bina Insan Mandiri Purwokerto, cita-cita visi misi, tenaga pendidik, dan ikrar siswa.
              </p>
            </div>

            <router-link
              to="/profil"
              v-splash="'emerald'"
              class="btn-splash inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs sm:text-sm transition-all border border-emerald-200 self-start md:self-auto shrink-0 shadow-xs hover:shadow-md cursor-pointer hover:-translate-y-0.5 active:translate-y-0"
            >
              <span>Lihat Profil Lengkap</span>
              <span>&rarr;</span>
            </router-link>
          </div>

          <!-- Cards Preview Profil (3 Kolom Ringkas dengan Scroll Reveal Stagger) -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Profil & Sejarah Singkat -->
            <div 
              v-reveal="{ type: 'fade-up', delay: 100 }"
              class="p-6 rounded-3xl bg-slate-50 hover:bg-white border border-slate-200 hover:border-emerald-300 hover:shadow-xl transition-all duration-300 hover:-translate-y-1 flex flex-col justify-between"
            >
              <div class="space-y-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-xl font-bold">
                  🏛️
                </div>
                <h3 class="font-black text-base text-slate-900">Sejarah & Identitas</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                  Didirikan sejak 2012 di Karangklesem, Purwokerto Selatan di bawah naungan Yayasan Bina Insan Mandiri dengan kurikulum berbasis deep learning.
                </p>
                <div class="pt-2 text-[11px] font-mono text-emerald-700 font-semibold">
                  NPSN: 70036695 • Jenjang SD
                </div>
              </div>
              <router-link to="/profil?tab=profil" class="mt-4 text-xs font-bold text-emerald-700 hover:underline">
                Baca Profil Singkat &rarr;
              </router-link>
            </div>

            <!-- Visi & Misi Ringkas -->
            <div 
              v-reveal="{ type: 'fade-up', delay: 200 }"
              class="p-6 rounded-3xl bg-slate-50 hover:bg-white border border-slate-200 hover:border-teal-300 hover:shadow-xl transition-all duration-300 hover:-translate-y-1 flex flex-col justify-between"
            >
              <div class="space-y-3">
                <div class="w-10 h-10 rounded-xl bg-teal-100 text-teal-800 flex items-center justify-center text-xl font-bold">
                  🎯
                </div>
                <h3 class="font-black text-base text-slate-900">Visi & Misi Luhur</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                  "Terwujudnya Generasi Muslim Pembelajar yang Bertauhid Kokoh, Berakhlak Mulia, Tumbuh Sesuai Fitrahnya, Hafidz Al-Qur'an, dan Membangun Peradaban."
                </p>
                <div class="pt-2 text-[11px] text-teal-800 font-semibold">
                  4 Misi Berkelanjutan & Nilai BIM
                </div>
              </div>
              <router-link to="/profil?tab=visi" class="mt-4 text-xs font-bold text-teal-700 hover:underline">
                Pelajari Visi & Misi &rarr;
              </router-link>
            </div>

            <!-- Guru & Janji Siswa -->
            <div 
              v-reveal="{ type: 'fade-up', delay: 300 }"
              class="p-6 rounded-3xl bg-slate-50 hover:bg-white border border-slate-200 hover:border-amber-300 hover:shadow-xl transition-all duration-300 hover:-translate-y-1 flex flex-col justify-between"
            >
              <div class="space-y-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-xl font-bold">
                  📜
                </div>
                <h3 class="font-black text-base text-slate-900">Pendidik & Janji Siswa</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                  Didampingi {{ teachersData.length }}+ dewan asatidz dan pendidik kompeten, serta 6 ikrar janji siswa peradaban yang dihayati dalam keseharian sekolah.
                </p>
                <div class="pt-2 text-[11px] text-amber-800 font-semibold">
                  6 Ikrar Siswa • Guru Berdedikasi
                </div>
              </div>
              <router-link to="/profil?tab=janji" class="mt-4 text-xs font-bold text-amber-700 hover:underline">
                Buka Janji Siswa & Pendidik &rarr;
              </router-link>
            </div>
          </div>

        </div>
      </section>

      <!-- 2. RINGKASAN PENDIDIKAN (FITRAH & PERADABAN) -->
      <section class="py-20 bg-slate-50 border-b border-slate-200/80 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
          
          <div 
            v-reveal="{ type: 'fade-up', delay: 50 }"
            class="flex flex-col md:flex-row md:items-end justify-between gap-4"
          >
            <div>
              <span class="px-3.5 py-1 rounded-full bg-teal-100 text-teal-800 text-xs font-bold uppercase tracking-wider">
                Pendidikan & Deep Learning
              </span>
              <h2 class="mt-2 text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                Pendidikan Berbasis Deep Learning & Membangun Peradaban
              </h2>
              <p class="mt-1 text-xs sm:text-sm text-slate-500 max-w-2xl">
                Menyelenggarakan pembelajaran mendalam (Mindful, Meaningful, & Joyful Learning) untuk membentuk generasi pembelajar pembina peradaban.
              </p>
            </div>

            <router-link
              to="/pendidikan"
              v-splash="'emerald'"
              class="btn-splash btn-sheen inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs sm:text-sm transition-all shadow-sm hover:shadow-lg self-start md:self-auto shrink-0 cursor-pointer hover:-translate-y-0.5 active:translate-y-0"
            >
              <span>Pelajari Konsep Pendidikan</span>
              <span>&rarr;</span>
            </router-link>
          </div>

          <!-- Preview 4 Pilar Deep Learning Grid dengan Staggered Reveal -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div 
              v-for="(pillar, idx) in fitrahPillars" 
              :key="pillar.title"
              v-reveal="{ type: 'fade-up', delay: 100 + idx * 80 }"
              class="p-6 rounded-3xl bg-white border border-slate-200 hover:border-emerald-400 hover:shadow-xl transition-all duration-300 hover:-translate-y-1 flex flex-col justify-between space-y-3"
            >
              <div>
                <span class="text-3xl block mb-2">{{ pillar.icon }}</span>
                <h4 class="font-black text-sm text-slate-900 leading-snug">{{ pillar.title }}</h4>
                <p class="text-[11px] text-emerald-700 font-semibold mt-1">{{ pillar.subtitle }}</p>
                <p class="text-xs text-slate-600 mt-2 line-clamp-3 leading-relaxed">
                  {{ pillar.desc }}
                </p>
              </div>
              <router-link to="/pendidikan#pendidikan-deep-learning" class="text-xs font-bold text-emerald-700 hover:underline pt-2">
                Selengkapnya &rarr;
              </router-link>
            </div>
          </div>

          <!-- Banner Kutipan Peradaban Ringkas -->
          <div 
            v-reveal="{ type: 'scale', delay: 150 }"
            class="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-emerald-950 via-teal-900 to-slate-950 text-white flex flex-col sm:flex-row items-center justify-between gap-6 shadow-md hover:shadow-2xl transition-all duration-300"
          >
            <div class="space-y-1.5 text-center sm:text-left">
              <span class="text-xs font-bold text-amber-300 uppercase tracking-wider">Tujuan Hakiki</span>
              <h3 class="text-lg sm:text-xl font-black text-white">Tujuan Pendidikan adalah Membangun Sebuah Peradaban</h3>
              <p class="text-xs text-emerald-200/90 max-w-xl">
                Bukan sekadar mengejar angka rapor, tetapi membentuk manusia seutuhnya yang berakhlaq mulia dan bermanfaat bagi umat.
              </p>
            </div>
            <router-link
              to="/pendidikan#membangun-peradaban"
              v-splash="'amber'"
              class="btn-splash btn-sheen px-5 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs transition-all shadow-md hover:shadow-lg shrink-0 cursor-pointer hover:-translate-y-0.5 active:translate-y-0"
            >
              Eksplorasi Peradaban &rarr;
            </router-link>
          </div>

        </div>
      </section>

      <!-- 3. PROGRAM UNGGULAN SEKOLAH (8 PROGRAM) -->
      <LandingFeaturedProgramsSection :show-view-all-link="true" section-id="beranda-program-unggulan" />

      <!-- 4. RINGKASAN ARTIKEL & WARTA -->
      <section class="py-20 bg-slate-50 border-b border-slate-200/80 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
          
          <div 
            v-reveal="{ type: 'fade-up', delay: 50 }"
            class="flex flex-col md:flex-row md:items-end justify-between gap-4"
          >
            <div>
              <span class="px-3.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider">
                Artikel & Warta
              </span>
              <h2 class="mt-2 text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                Kabar Terbaru & Wawasan Parenting
              </h2>
              <p class="mt-1 text-xs sm:text-sm text-slate-500 max-w-2xl">
                Wawasan seputar pengasuhan fitrah anak, tips tahfidz, dan berita terkini kegiatan sekolah.
              </p>
            </div>

            <router-link
              to="/artikel"
              v-splash="'dark'"
              class="btn-splash btn-sheen inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs sm:text-sm transition-all shadow-sm hover:shadow-lg self-start md:self-auto shrink-0 cursor-pointer hover:-translate-y-0.5 active:translate-y-0"
            >
              <span>Lihat Semua Artikel ({{ articlesData.length }})</span>
              <span>&rarr;</span>
            </router-link>
          </div>

          <!-- Grid 3 Artikel Terpilih dengan Stagger Reveal -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <article
              v-for="(article, idx) in articlesData.slice(0, 3)"
              :key="article.id"
              v-reveal="{ type: 'fade-up', delay: 100 + idx * 100 }"
              class="bg-white rounded-3xl border border-slate-200 p-6 flex flex-col justify-between hover:border-emerald-300 hover:shadow-xl hover:-translate-y-1 transition-all duration-300"
            >
              <div class="space-y-2.5">
                <div class="flex items-center justify-between text-[11px] text-slate-400">
                  <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold uppercase text-[10px]">
                    {{ article.category }}
                  </span>
                  <span>{{ article.readTime }}</span>
                </div>
                <h3 class="font-black text-base text-slate-900 leading-snug line-clamp-2">
                  {{ article.title }}
                </h3>
                <p class="text-xs text-slate-600 line-clamp-3 leading-relaxed">
                  {{ article.summary }}
                </p>
              </div>

              <div class="mt-5 pt-3 border-t border-slate-200/80 flex items-center justify-between">
                <div class="text-[11px] text-slate-500 font-medium">{{ article.author }}</div>
                <router-link to="/artikel" class="text-xs font-bold text-emerald-700 hover:underline">
                  Baca Artikel &rarr;
                </router-link>
              </div>
            </article>
          </div>

        </div>
      </section>

      <!-- 4. RINGKASAN GALERI KEGIATAN -->
      <section class="py-20 bg-slate-50 border-b border-slate-200/80 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
          
          <div 
            v-reveal="{ type: 'fade-up', delay: 50 }"
            class="flex flex-col md:flex-row md:items-end justify-between gap-4"
          >
            <div>
              <span class="px-3.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider">
                Galeri Kegiatan
              </span>
              <h2 class="mt-2 text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                Dokumentasi Kegiatan Siswa
              </h2>
              <p class="mt-1 text-xs sm:text-sm text-slate-500 max-w-2xl">
                Potret keceriaan dan pengalaman belajar nyata siswa di dalam dan luar ruangan.
              </p>
            </div>

            <router-link
              to="/galeri"
              v-splash="'emerald'"
              class="btn-splash inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs sm:text-sm transition-all border border-emerald-200 self-start md:self-auto shrink-0 shadow-xs hover:shadow-md cursor-pointer hover:-translate-y-0.5 active:translate-y-0"
            >
              <span>Buka Galeri Lengkap</span>
              <span>&rarr;</span>
            </router-link>
          </div>

          <!-- Grid 3 Cuplikan Galeri dengan Stagger Reveal -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div 
              v-for="(item, idx) in galleryItems.slice(0, 3)"
              :key="item.id"
              v-reveal="{ type: 'fade-up', delay: 100 + idx * 100 }"
              class="rounded-3xl p-6 bg-gradient-to-tr text-white shadow-md hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between h-56 relative overflow-hidden group cursor-pointer"
              :class="item.color"
            >
              <div class="relative z-10 flex items-center justify-between">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-white/20 backdrop-blur-md">
                  {{ item.categoryLabel }}
                </span>
                <span class="text-2xl transform group-hover:scale-110 transition-transform">{{ item.icon }}</span>
              </div>
              <div class="relative z-10 space-y-1">
                <div class="text-[10px] text-emerald-200 font-mono">{{ item.date }}</div>
                <h3 class="font-bold text-base text-white leading-snug">{{ item.title }}</h3>
                <router-link to="/galeri" class="inline-block text-xs font-bold text-emerald-200 hover:text-white pt-1">
                  Lihat Dokumentasi &rarr;
                </router-link>
              </div>
            </div>
          </div>

        </div>
      </section>

      <!-- 5. RINGKASAN PRESTASI -->
      <section class="py-20 bg-white border-b border-slate-200/80 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
          
          <div 
            v-reveal="{ type: 'fade-up', delay: 50 }"
            class="flex flex-col md:flex-row md:items-end justify-between gap-4"
          >
            <div>
              <span class="px-3.5 py-1 rounded-full bg-amber-100 text-amber-900 text-xs font-bold uppercase tracking-wider">
                Prestasi Siswa
              </span>
              <h2 class="mt-2 text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                Pencapaian Prestasi Siswa
              </h2>
              <p class="mt-1 text-xs sm:text-sm text-slate-500 max-w-2xl">
                Bukti keunggulan siswa dalam bidang tahfidz Al-Qur'an, olimpiade sains, pidato, dan robotik.
              </p>
            </div>

            <router-link
              to="/prestasi"
              v-splash="'amber'"
              class="btn-splash inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-900 font-bold text-xs sm:text-sm transition-all border border-amber-300 self-start md:self-auto shrink-0 shadow-xs hover:shadow-md cursor-pointer hover:-translate-y-0.5 active:translate-y-0"
            >
              <span>Lihat Semua Prestasi</span>
              <span>&rarr;</span>
            </router-link>
          </div>

          <!-- Grid 3 Prestasi Teratas dengan Stagger Reveal -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div 
              v-for="(item, idx) in achievementsData.slice(0, 3)" 
              :key="item.id"
              v-reveal="{ type: 'fade-up', delay: 100 + idx * 100 }"
              class="p-6 rounded-3xl bg-slate-50 hover:bg-white border border-slate-200 hover:border-amber-300 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between"
            >
              <div class="space-y-2.5">
                <div class="flex items-center justify-between">
                  <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border" :class="item.badgeColor">
                    {{ item.rank }}
                  </span>
                  <span class="text-xs text-slate-400 font-mono font-bold">{{ item.year }}</span>
                </div>
                <h3 class="font-bold text-base text-slate-900 leading-snug">{{ item.title }}</h3>
                <div class="text-xs font-bold text-emerald-800">{{ item.recipient }}</div>
                <div class="text-[11px] text-slate-500">Tingkat {{ item.level }}</div>
              </div>
              <router-link to="/prestasi" class="mt-4 text-xs font-bold text-amber-800 hover:underline">
                Rincian Prestasi &rarr;
              </router-link>
            </div>
          </div>

        </div>
      </section>

      <!-- 6. RINGKASAN INFO SPMB -->
      <section class="py-20 bg-gradient-to-br from-[#022e23] via-[#044737] to-[#02261d] text-white overflow-hidden relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 relative z-10">
          
          <div 
            v-reveal="{ type: 'fade-up', delay: 50 }"
            class="max-w-3xl space-y-3"
          >
            <span class="px-3 py-1 rounded-full bg-amber-400/20 text-amber-300 border border-amber-400/30 text-xs font-bold uppercase tracking-wider">
              Penerimaan Murid Baru
            </span>
            <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
              Penerimaan Murid Baru (SPMB) TA 2026/2027
            </h2>
            <p class="text-xs sm:text-sm text-emerald-100/90 leading-relaxed">
              Pendaftaran telah dibuka untuk Gelombang 1 (Inden & Prioritas). Dapatkan kemudahan pendaftaran, observasi ramah anak, dan informasi beasiswa.
            </p>
          </div>

          <!-- Cards Gelombang Ringkas dengan Stagger Reveal -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div 
              v-for="(wave, idx) in spmbWaves" 
              :key="wave.name"
              v-reveal="{ type: 'fade-up', delay: 100 + idx * 100 }"
              class="p-5 rounded-2xl bg-white/10 hover:bg-white/15 border border-white/15 hover:border-amber-400/40 backdrop-blur-md space-y-2 hover:-translate-y-1 transition-all duration-300"
            >
              <div class="flex items-center justify-between text-[10px] font-bold uppercase">
                <span :class="wave.isOpen ? 'text-amber-300' : 'text-slate-400'">{{ wave.status }}</span>
                <span v-if="wave.isOpen" class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
              </div>
              <h4 class="font-bold text-sm text-white">{{ wave.name }}</h4>
              <div class="text-xs font-mono text-emerald-200">{{ wave.period }}</div>
            </div>
          </div>

          <!-- CTA Ke Halaman SPMB dengan Splash Effect -->
          <div 
            v-reveal="{ type: 'fade-up', delay: 300 }"
            class="pt-4 flex flex-col sm:flex-row items-center gap-4"
          >
            <router-link
              to="/spmb"
              v-splash="'amber'"
              class="btn-splash btn-sheen w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-8 py-4 rounded-2xl bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black text-sm sm:text-base shadow-xl hover:shadow-2xl transition-all cursor-pointer hover:-translate-y-0.5 active:translate-y-0"
            >
              <span>Buka Informasi & Pendaftaran SPMB Lengkap</span>
              <span>&rarr;</span>
            </router-link>

            <a
              href="https://wa.me/6285138568694?text=Bismillah,%20Assalamu'alaikum%20Sekretariat%20SDI%20Bina%20Insan%20Mandiri"
              target="_blank"
              rel="noopener noreferrer"
              v-splash="'light'"
              class="btn-splash w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-4 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold text-sm border border-white/20 transition-all cursor-pointer hover:-translate-y-0.5 active:translate-y-0"
            >
              <span>Chat WhatsApp Sekretariat</span>
              <span>💬</span>
            </a>
          </div>

        </div>
      </section>
    </main>

    <!-- FOOTER INFORMASI LENGKAP -->
    <LandingFooter @open-portal="handlePortalAccess" />

  </div>
</template>
