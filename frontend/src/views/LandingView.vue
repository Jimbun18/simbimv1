<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

const isMobileMenuOpen = ref(false)
const activeTab = ref<'guru' | 'wali' | 'manajemen'>('guru')
const activeFaq = ref<number | null>(0)

// Quick redirect helper depending on login state & role
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

const toggleFaq = (index: number) => {
  activeFaq.value = activeFaq.value === index ? null : index
}

const scrollToSection = (id: string) => {
  isMobileMenuOpen.value = false
  const el = document.getElementById(id)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' })
  }
}

const userPortalLabel = computed(() => {
  if (!auth.isAuthenticated) return 'Masuk ke SIM-BIM'
  if (auth.isAdmin) return 'Buka Dashboard Admin'
  if (auth.isGuru) return 'Buka Portal Guru & KBM'
  if (auth.isWali) return 'Buka Portal Wali Murid'
  return 'Buka Dashboard'
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-800 font-sans selection:bg-emerald-500 selection:text-white">
    
    <!-- 1. STICKY TOP NAVIGATION BAR -->
    <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-emerald-100/70 shadow-xs transition-all">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        
        <!-- Brand Logo & School Title -->
        <div class="flex items-center gap-3.5 cursor-pointer" @click="scrollToSection('hero')">
          <img 
            src="/logo.png" 
            alt="Logo SDI Bina Insan Mandiri Purwokerto" 
            class="w-12 h-12 object-contain drop-shadow-sm hover:scale-105 transition-transform"
          />
          <div>
            <div class="flex items-center gap-2">
              <span class="font-black text-xl text-slate-900 tracking-tight">SIM-BIM</span>
              <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase tracking-wider">
                Smart School
              </span>
            </div>
            <p class="text-xs font-bold text-emerald-700 leading-tight">SDI Bina Insan Mandiri Purwokerto</p>
          </div>
        </div>

        <!-- Desktop Navigation Links -->
        <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-600">
          <button @click="scrollToSection('hero')" class="hover:text-emerald-700 transition-colors">Beranda</button>
          <button @click="scrollToSection('tentang')" class="hover:text-emerald-700 transition-colors">Tentang Kami</button>
          <button @click="scrollToSection('fitur')" class="hover:text-emerald-700 transition-colors">Fitur Unggulan</button>
          <button @click="scrollToSection('kurikulum')" class="hover:text-emerald-700 transition-colors">Kurikulum Tematik</button>
          <button @click="scrollToSection('faq')" class="hover:text-emerald-700 transition-colors">FAQ</button>
          <button @click="scrollToSection('kontak')" class="hover:text-emerald-700 transition-colors">Kontak</button>
        </nav>

        <!-- Header CTA Buttons -->
        <div class="hidden sm:flex items-center gap-3">
          <button 
            @click="handlePortalAccess"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white text-sm font-bold shadow-md shadow-emerald-700/20 hover:from-emerald-700 hover:to-teal-800 transition-all hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 cursor-pointer"
          >
            <span>{{ userPortalLabel }}</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
          </button>
        </div>

        <!-- Mobile Hamburger Button -->
        <div class="flex items-center md:hidden">
          <button 
            @click="isMobileMenuOpen = !isMobileMenuOpen"
            class="p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors"
            aria-label="Toggle Navigation Menu"
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

      <!-- Mobile Dropdown Menu -->
      <div 
        v-if="isMobileMenuOpen" 
        class="md:hidden border-t border-slate-200 bg-white/95 backdrop-blur-md px-4 pt-4 pb-6 space-y-3 animate-fadeIn"
      >
        <button @click="scrollToSection('hero')" class="block w-full text-left py-2 font-medium text-slate-700 hover:text-emerald-700">Beranda</button>
        <button @click="scrollToSection('tentang')" class="block w-full text-left py-2 font-medium text-slate-700 hover:text-emerald-700">Tentang Kami</button>
        <button @click="scrollToSection('fitur')" class="block w-full text-left py-2 font-medium text-slate-700 hover:text-emerald-700">Fitur Unggulan</button>
        <button @click="scrollToSection('kurikulum')" class="block w-full text-left py-2 font-medium text-slate-700 hover:text-emerald-700">Kurikulum Tematik</button>
        <button @click="scrollToSection('faq')" class="block w-full text-left py-2 font-medium text-slate-700 hover:text-emerald-700">FAQ</button>
        <button @click="scrollToSection('kontak')" class="block w-full text-left py-2 font-medium text-slate-700 hover:text-emerald-700">Kontak</button>
        
        <div class="pt-3 border-t border-slate-100">
          <button 
            @click="handlePortalAccess"
            class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20"
          >
            <span>{{ userPortalLabel }}</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
          </button>
        </div>
      </div>
    </header>

    <!-- 2. HERO SECTION -->
    <section id="hero" class="relative overflow-hidden pt-12 pb-20 md:pt-20 md:pb-32 bg-gradient-to-b from-emerald-950 via-emerald-900 to-teal-950 text-white">
      <!-- Ambient Lighting Blobs -->
      <div class="absolute -top-24 -left-24 w-96 h-96 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute top-1/2 -right-24 w-96 h-96 bg-teal-400/15 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-20 left-1/3 w-80 h-80 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
          
          <!-- Left Column: Headline & Action -->
          <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
            
            <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-white/10 border border-white/20 backdrop-blur-md text-emerald-200 text-xs sm:text-sm font-semibold">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
              <span>Sistem Informasi Manajemen Terpadu (SIM-BIM)</span>
            </div>

            <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black tracking-tight leading-tight sm:leading-tight">
              Mewujudkan Generasi <br class="hidden sm:inline" />
              <span class="bg-gradient-to-r from-emerald-300 via-teal-200 to-amber-200 bg-clip-text text-transparent">
                Qur'ani & Unggul
              </span>
              Berbasis Teknologi Digital
            </h1>

            <p class="text-sm sm:text-base md:text-lg text-emerald-100/90 max-w-2xl mx-auto lg:mx-0 font-normal leading-relaxed">
              Selamat datang di portal resmi <strong>SDI Bina Insan Mandiri Purwokerto</strong>. 
              Sistem pintar terintegrasi yang memudahkan manajemen KBM Tematik, presensi geofence real-time, transparansi portal wali murid, serta pencatatan mutaba'ah tahfidz dalam satu genggaman.
            </p>

            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-2">
              <button 
                @click="handlePortalAccess"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-3 px-8 py-4 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-black text-base shadow-xl shadow-emerald-500/30 transition-all hover:-translate-y-0.5 active:translate-y-0 cursor-pointer"
              >
                <span>Akses Portal SIM-BIM</span>
                <svg class="w-5 h-5 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
              </button>

              <button 
                @click="scrollToSection('fitur')"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-4 rounded-2xl bg-white/10 hover:bg-white/15 text-white font-semibold text-base border border-white/20 backdrop-blur-md transition-all cursor-pointer"
              >
                <span>Eksplorasi Fitur</span>
                <svg class="w-5 h-5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>
            </div>

            <!-- Highlights Badges -->
            <div class="pt-6 grid grid-cols-2 sm:grid-cols-4 gap-3 border-t border-white/10 text-left">
              <div class="p-3 rounded-xl bg-white/5 border border-white/10 backdrop-blur-xs">
                <div class="text-2xl font-black text-amber-300">450+</div>
                <div class="text-[11px] text-emerald-200 font-medium">Siswa & Siswi Aktif</div>
              </div>
              <div class="p-3 rounded-xl bg-white/5 border border-white/10 backdrop-blur-xs">
                <div class="text-2xl font-black text-emerald-300">30+</div>
                <div class="text-[11px] text-emerald-200 font-medium">Pendidik & Asatidz</div>
              </div>
              <div class="p-3 rounded-xl bg-white/5 border border-white/10 backdrop-blur-xs">
                <div class="text-2xl font-black text-teal-300">3+ Juz</div>
                <div class="text-[11px] text-emerald-200 font-medium">Target Mutqin Qur'an</div>
              </div>
              <div class="p-3 rounded-xl bg-white/5 border border-white/10 backdrop-blur-xs">
                <div class="text-2xl font-black text-rose-300">100%</div>
                <div class="text-[11px] text-emerald-200 font-medium">Geofence GPS Akurat</div>
              </div>
            </div>

          </div>

          <!-- Right Column: Interactive Mockup Card -->
          <div class="lg:col-span-5 relative">
            <div class="relative mx-auto max-w-md lg:max-w-none">
              
              <!-- Backdrop Glow Frame -->
              <div class="absolute -inset-1 bg-gradient-to-r from-emerald-500 to-amber-500 rounded-3xl blur-xl opacity-30 animate-pulse"></div>

              <!-- Main Glass Card Showcase -->
              <div class="relative bg-slate-900/90 border border-emerald-500/30 rounded-3xl p-6 shadow-2xl backdrop-blur-xl text-white space-y-5">
                
                <!-- Mockup Header -->
                <div class="flex items-center justify-between border-b border-white/10 pb-4">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600/30 border border-emerald-400/30 flex items-center justify-center">
                      <img src="/logo.png" alt="Logo SIM-BIM" class="w-7 h-7 object-contain" />
                    </div>
                    <div>
                      <h4 class="text-sm font-bold text-white">Live Monitoring KBM</h4>
                      <p class="text-[10px] text-emerald-300">SDI Bina Insan Mandiri Purwokerto</p>
                    </div>
                  </div>
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-bold border border-emerald-500/30">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    Aktif
                  </span>
                </div>

                <!-- Schedule Card (Today Highlight - Hijau) -->
                <div class="p-4 rounded-2xl bg-emerald-950/70 border border-emerald-500/40 relative overflow-hidden">
                  <div class="flex items-center justify-between mb-2">
                    <span class="px-2 py-0.5 rounded-md bg-emerald-500/30 text-emerald-200 text-[10px] font-black uppercase tracking-wider">
                      Jadwal Hari Ini (07:30 - 09:30)
                    </span>
                    <span class="text-[10px] text-emerald-300 font-semibold">Kelas 1-A Fatimah</span>
                  </div>
                  <h5 class="text-base font-black text-white">Tema 1: Diriku & Indahnya Kebersamaan</h5>
                  <p class="text-xs text-emerald-200/80 mt-1 line-clamp-1">Muatan: Matematika, Bhs Indonesia, PPKn</p>
                  
                  <div class="mt-3.5 flex items-center justify-between pt-3 border-t border-emerald-500/20 text-xs">
                    <div class="flex items-center gap-2 text-emerald-200">
                      <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                      </svg>
                      <span class="font-medium">Ustadzah Nurul H., S.Pd</span>
                    </div>
                    <button class="px-3 py-1 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs shadow-xs transition-colors">
                      Masuk Kelas
                    </button>
                  </div>
                </div>

                <!-- Live Attendance Mini Stats -->
                <div class="grid grid-cols-2 gap-3">
                  <div class="p-3.5 rounded-xl bg-white/5 border border-white/10">
                    <div class="text-xs text-slate-400 font-medium">Tingkat Hadir Siswa</div>
                    <div class="text-xl font-black text-emerald-300 mt-1">98.4%</div>
                    <div class="w-full bg-slate-800 rounded-full h-1.5 mt-2 overflow-hidden">
                      <div class="bg-emerald-400 h-full rounded-full" style="width: 98.4%"></div>
                    </div>
                  </div>
                  <div class="p-3.5 rounded-xl bg-white/5 border border-white/10">
                    <div class="text-xs text-slate-400 font-medium">Geofence Lokasi Guru</div>
                    <div class="text-xl font-black text-teal-300 mt-1">28 Terverifikasi</div>
                    <p class="text-[10px] text-teal-200/80 mt-1 truncate">Radius Kampus 50 meter</p>
                  </div>
                </div>

                <!-- Floating Wali Murid Pill -->
                <div class="p-3 rounded-xl bg-gradient-to-r from-amber-500/20 to-orange-500/20 border border-amber-500/30 flex items-center justify-between">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/30 flex items-center justify-center text-amber-300 font-black text-xs">
                      📲
                    </div>
                    <div>
                      <div class="text-xs font-bold text-amber-200">Portal Wali Murid Real-time</div>
                      <div class="text-[10px] text-slate-300">Notifikasi presensi & pembayaran SPP</div>
                    </div>
                  </div>
                  <span class="text-[10px] font-bold text-amber-300">Terkoneksi</span>
                </div>

              </div>

            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- 3. TENTANG SEKOLAH (PROFILE & VISI MISI) -->
    <section id="tentang" class="py-20 bg-white border-b border-slate-200/80">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-3xl mx-auto mb-16">
          <span class="px-3.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider">
            Profil Lembaga
          </span>
          <h2 class="mt-3 text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
            SDI Bina Insan Mandiri Purwokerto
          </h2>
          <p class="mt-3 text-base text-slate-600 leading-relaxed">
            Lembaga pendidikan Islam dasar unggulan di Kabupaten Banyumas yang memadukan kurikulum nasional, penguatan karakter Islami, dan tahfidz Al-Qur'an dengan pendampingan penuh kasih sayang.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          
          <!-- Card Visi -->
          <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200/80 hover:shadow-xl hover:border-emerald-300 transition-all group">
            <div class="w-14 h-14 rounded-2xl bg-emerald-600 text-white flex items-center justify-center mb-6 shadow-md shadow-emerald-600/30 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-3">Visi Mulia</h3>
            <p class="text-sm text-slate-600 leading-relaxed">
              "Terwujudnya generasi Muslim yang beraqidah lurus, berakhlak mulia, hafidz Al-Qur'an, berprestasi dalam ilmu pengetahuan, dan berjiwa mandiri."
            </p>
          </div>

          <!-- Card Misi -->
          <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200/80 hover:shadow-xl hover:border-teal-300 transition-all group">
            <div class="w-14 h-14 rounded-2xl bg-teal-600 text-white flex items-center justify-center mb-6 shadow-md shadow-teal-600/30 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-3">Misi Unggulan</h3>
            <ul class="text-sm text-slate-600 space-y-2">
              <li class="flex items-start gap-2">
                <span class="text-teal-600 font-bold">•</span>
                <span>Menyelenggarakan pembelajaran Qur'ani tahsin dan tahfidz secara intensif.</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-teal-600 font-bold">•</span>
                <span>Menerapkan kurikulum tematik terpadu yang memicu kreativitas & nalar kritis.</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-teal-600 font-bold">•</span>
                <span>Membudayakan adab Islami dan kemandirian dalam kehidupan sehari-hari.</span>
              </li>
            </ul>
          </div>

          <!-- Card Nilai Dasar -->
          <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200/80 hover:shadow-xl hover:border-amber-300 transition-all group">
            <div class="w-14 h-14 rounded-2xl bg-amber-500 text-white flex items-center justify-center mb-6 shadow-md shadow-amber-500/30 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
              </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-3">Nilai BIM</h3>
            <p class="text-sm text-slate-600 leading-relaxed mb-4">
              Integrasi nilai <strong class="text-slate-800">Qur'ani</strong> (pedoman hidup), <strong class="text-slate-800">Integritas</strong> (kejujuran & adab), dan <strong class="text-slate-800">Mandiri</strong> (keberanian & tanggung jawab).
            </p>
            <div class="inline-flex items-center gap-2 text-xs font-bold text-amber-700 bg-amber-50 px-3 py-1 rounded-full border border-amber-200">
              <span>Mendidik Sepenuh Hati</span>
            </div>
          </div>

        </div>

      </div>
    </section>

    <!-- 4. FITUR UTAMA & KEUNGGULAN SISTEM SIM-BIM -->
    <section id="fitur" class="py-20 bg-slate-50 border-b border-slate-200/80">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-3xl mx-auto mb-16">
          <span class="px-3.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider">
            Inovasi Digital
          </span>
          <h2 class="mt-3 text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
            Fitur Utama Sistem SIM-BIM
          </h2>
          <p class="mt-3 text-base text-slate-600 leading-relaxed">
            Dirancang khusus untuk mendukung operasional modern SDI Bina Insan Mandiri Purwokerto secara akurat, cepat, dan transparan.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          
          <!-- Fitur 1: Presensi Geofence GPS -->
          <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs hover:shadow-lg hover:border-emerald-300 transition-all flex flex-col justify-between">
            <div>
              <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-black text-xl mb-5">
                📍
              </div>
              <h3 class="text-lg font-bold text-slate-900 mb-2">Presensi GPS Geofencing</h3>
              <p class="text-xs text-slate-600 leading-relaxed">
                Verifikasi kehadiran guru dan pegawai secara otomatis berdasarkan titik koordinat radius sekolah, dilengkapi swafoto & anti-fake GPS.
              </p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center gap-2 text-xs font-bold text-emerald-700">
              <span>Radius Terverifikasi</span>
              <span>✓</span>
            </div>
          </div>

          <!-- Fitur 2: Pembelajaran Tematik & KBM -->
          <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs hover:shadow-lg hover:border-teal-300 transition-all flex flex-col justify-between">
            <div>
              <div class="w-12 h-12 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center font-black text-xl mb-5">
                📚
              </div>
              <h3 class="text-lg font-bold text-slate-900 mb-2">Kurikulum Tematik Terpadu</h3>
              <p class="text-xs text-slate-600 leading-relaxed">
                Dukungan penuh struktur Tema 1 s/d 5 yang terintegrasi muatan IPA, Matematika, dan Bahasa. Guru cukup klik "Masuk Kelas" sesuai jadwal harinya.
              </p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center gap-2 text-xs font-bold text-teal-700">
              <span>Tema 1-5 Terintegrasi</span>
              <span>✓</span>
            </div>
          </div>

          <!-- Fitur 3: Portal Wali Murid Transparan -->
          <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs hover:shadow-lg hover:border-amber-300 transition-all flex flex-col justify-between">
            <div>
              <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center font-black text-xl mb-5">
                👨‍👩‍👧
              </div>
              <h3 class="text-lg font-bold text-slate-900 mb-2">Portal Wali Murid Pintar</h3>
              <p class="text-xs text-slate-600 leading-relaxed">
                Akses langsung dari gawai orang tua untuk memantau kehadiran ananda, catatan guru, histori tagihan SPP, dan pengumuman resmi sekolah.
              </p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center gap-2 text-xs font-bold text-amber-700">
              <span>Akses 24 Jam Aman</span>
              <span>✓</span>
            </div>
          </div>

          <!-- Fitur 4: Rekap & Export Excel -->
          <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs hover:shadow-lg hover:border-indigo-300 transition-all flex flex-col justify-between">
            <div>
              <div class="w-12 h-12 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-black text-xl mb-5">
                📊
              </div>
              <h3 class="text-lg font-bold text-slate-900 mb-2">Rekap Presensi & Rapot</h3>
              <p class="text-xs text-slate-600 leading-relaxed">
                Rekapitulasi pertemuan KBM per kelas dan per mapel dengan filter tanggal fleksibel, sekali klik langsung export ke spreadsheet Excel resmi.
              </p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center gap-2 text-xs font-bold text-indigo-700">
              <span>Export Excel Sekali Klik</span>
              <span>✓</span>
            </div>
          </div>

        </div>

      </div>
    </section>

    <!-- 5. INTERACTIVE ROLE-BASED EXPERIENCE (TABBED DEMO) -->
    <section class="py-20 bg-white border-b border-slate-200/80">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-3xl mx-auto mb-12">
          <span class="px-3.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider">
            Pengalaman Pengguna
          </span>
          <h2 class="mt-3 text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
            Dirancang Khusus untuk Setiap Peran
          </h2>
          <p class="mt-3 text-base text-slate-600 leading-relaxed">
            Pilih peran Anda untuk melihat bagaimana SIM-BIM mempermudah aktivitas harian di lingkungan sekolah:
          </p>

          <!-- Tab Selection Buttons -->
          <div class="mt-8 inline-flex p-1.5 rounded-2xl bg-slate-100 border border-slate-200 gap-1">
            <button 
              @click="activeTab = 'guru'"
              class="px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all cursor-pointer"
              :class="activeTab === 'guru' ? 'bg-emerald-600 text-white shadow-md' : 'text-slate-600 hover:text-slate-900'"
            >
              👩‍🏫 Untuk Guru & Ustadz
            </button>
            <button 
              @click="activeTab = 'wali'"
              class="px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all cursor-pointer"
              :class="activeTab === 'wali' ? 'bg-emerald-600 text-white shadow-md' : 'text-slate-600 hover:text-slate-900'"
            >
              👨‍👩‍👧 Untuk Wali Murid
            </button>
            <button 
              @click="activeTab = 'manajemen'"
              class="px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all cursor-pointer"
              :class="activeTab === 'manajemen' ? 'bg-emerald-600 text-white shadow-md' : 'text-slate-600 hover:text-slate-900'"
            >
              🏛️ Untuk Kepala Sekolah & Staf
            </button>
          </div>
        </div>

        <!-- Tab Content: GURU -->
        <div v-if="activeTab === 'guru'" class="bg-gradient-to-br from-emerald-50/60 to-teal-50/60 p-8 sm:p-12 rounded-3xl border border-emerald-100 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center animate-fadeIn">
          <div class="lg:col-span-6 space-y-4">
            <span class="px-3 py-1 rounded-full bg-emerald-200 text-emerald-900 text-xs font-bold">Portal Pendidik Terintegrasi</span>
            <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
              Mengajar Lebih Fokus, Administrasi Tanpa Beban
            </h3>
            <p class="text-sm text-slate-600 leading-relaxed">
              Guru memiliki dasbor jadwal mandiri. Jadwal hari ini otomatis ditandai hijau mencolok dan tombol "Masuk Kelas" siap dipakai untuk mencatat kehadiran siswa secara cepat.
            </p>
            <ul class="space-y-2.5 text-sm text-slate-700 font-medium pt-2">
              <li class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs">✓</span>
                <span>Jadwal otomatis terfilter hanya untuk guru yang bersangkutan</span>
              </li>
              <li class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs">✓</span>
                <span>Indikator warna: Hijau untuk jadwal hari ini, Merah untuk hari lain</span>
              </li>
              <li class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs">✓</span>
                <span>Input presensi siswa langsung terhubung ke rekapitulasi KBM</span>
              </li>
            </ul>
          </div>
          <div class="lg:col-span-6 bg-white p-6 rounded-2xl shadow-xl border border-slate-200/80 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
              <span class="text-xs font-bold text-slate-500 uppercase">Jadwal Mengajar Saya</span>
              <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-bold">Aktif Hari Ini</span>
            </div>
            <div class="p-4 rounded-xl border-2 border-emerald-500 bg-emerald-50/50">
              <div class="flex justify-between items-start">
                <div>
                  <span class="px-2 py-0.5 rounded bg-emerald-600 text-white text-[10px] font-bold">HARI INI</span>
                  <h4 class="font-black text-slate-900 text-base mt-1">Tema 2: Selalu Berhemat Energi</h4>
                  <p class="text-xs text-slate-600">Kelas 4-A • 08:00 - 09:30 WIB</p>
                </div>
                <button class="px-3.5 py-1.5 rounded-lg bg-emerald-600 text-white font-bold text-xs shadow-xs hover:bg-emerald-700">
                  Masuk Kelas
                </button>
              </div>
            </div>
            <div class="p-4 rounded-xl border border-rose-200 bg-rose-50/30">
              <div class="flex justify-between items-start">
                <div>
                  <span class="px-2 py-0.5 rounded bg-rose-600 text-white text-[10px] font-bold">KAMIS</span>
                  <h4 class="font-bold text-slate-800 text-sm mt-1">Pendidikan Agama Islam & Budi Pekerti</h4>
                  <p class="text-xs text-slate-500">Kelas 4-B • 10:00 - 11:30 WIB</p>
                </div>
                <span class="text-[11px] font-semibold text-rose-600">Bukan Hari Ini</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab Content: WALI MURID -->
        <div v-if="activeTab === 'wali'" class="bg-gradient-to-br from-amber-50/60 to-orange-50/60 p-8 sm:p-12 rounded-3xl border border-amber-100 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center animate-fadeIn">
          <div class="lg:col-span-6 space-y-4">
            <span class="px-3 py-1 rounded-full bg-amber-200 text-amber-900 text-xs font-bold">Transparansi Wali Murid</span>
            <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
              Pantau Kehadiran & Progres Ananda dari Mana Saja
            </h3>
            <p class="text-sm text-slate-600 leading-relaxed">
              Orang tua tidak perlu cemas lagi. SIM-BIM memberikan informasi transparan mengenai kehadiran sekolah, riwayat izin/sakit, dan status pembayaran SPP tanpa perlu repot datang ke sekolah.
            </p>
            <ul class="space-y-2.5 text-sm text-slate-700 font-medium pt-2">
              <li class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-amber-500 text-white flex items-center justify-center text-xs">✓</span>
                <span>Notifikasi kedatangan dan kepulangan ananda</span>
              </li>
              <li class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-amber-500 text-white flex items-center justify-center text-xs">✓</span>
                <span>Riwayat pembayaran SPP dan tagihan transparan</span>
              </li>
              <li class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-amber-500 text-white flex items-center justify-center text-xs">✓</span>
                <span>Dapat diakses lewat smartphone kapan pun dibutuhkan</span>
              </li>
            </ul>
          </div>
          <div class="lg:col-span-6 bg-white p-6 rounded-2xl shadow-xl border border-slate-200/80 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
              <span class="text-xs font-bold text-slate-500 uppercase">Ringkasan Ananda</span>
              <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-bold">Hadir di Kelas</span>
            </div>
            <div class="flex items-center gap-4 p-3 bg-slate-50 rounded-xl">
              <div class="w-12 h-12 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-base">
                AF
              </div>
              <div>
                <h4 class="font-bold text-slate-900">Ahmad Faris Albani</h4>
                <p class="text-xs text-slate-500">Kelas 3-B • NISN: 3128910244</p>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3 text-xs">
              <div class="p-3 rounded-lg bg-emerald-50 border border-emerald-100">
                <div class="text-slate-500 font-medium">Presensi Bulan Ini</div>
                <div class="text-lg font-bold text-emerald-700 mt-1">100% Hadir</div>
              </div>
              <div class="p-3 rounded-lg bg-teal-50 border border-teal-100">
                <div class="text-slate-500 font-medium">Status SPP</div>
                <div class="text-lg font-bold text-teal-700 mt-1">Lunas (Okt)</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab Content: MANAJEMEN -->
        <div v-if="activeTab === 'manajemen'" class="bg-gradient-to-br from-indigo-50/60 to-purple-50/60 p-8 sm:p-12 rounded-3xl border border-indigo-100 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center animate-fadeIn">
          <div class="lg:col-span-6 space-y-4">
            <span class="px-3 py-1 rounded-full bg-indigo-200 text-indigo-900 text-xs font-bold">Eksekutif & Administrasi</span>
            <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
              Keputusan Tepat Berbasis Data Akurat Realtime
            </h3>
            <p class="text-sm text-slate-600 leading-relaxed">
              Kepala Sekolah dan jajaran staf admin memiliki visibilitas menyeluruh atas rombel kelas, jadwal KBM tematik, kedisiplinan guru, inventaris sarana prasarana, serta manajemen akun pengguna tanpa modal popup yang rumit.
            </p>
            <ul class="space-y-2.5 text-sm text-slate-700 font-medium pt-2">
              <li class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs">✓</span>
                <span>Dashboard ringkasan metrik sekolah terlengkap</span>
              </li>
              <li class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs">✓</span>
                <span>Ekspor laporan presensi & KBM ke format Excel resmi</span>
              </li>
              <li class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs">✓</span>
                <span>Pencadangan (backup) database sekolah sekali klik</span>
              </li>
            </ul>
          </div>
          <div class="lg:col-span-6 bg-white p-6 rounded-2xl shadow-xl border border-slate-200/80 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
              <span class="text-xs font-bold text-slate-500 uppercase">Indikator Mutu SIM-BIM</span>
              <span class="text-xs font-bold text-indigo-600">TA 2024/2025</span>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                <div class="text-[11px] text-slate-500">Rombongan Belajar</div>
                <div class="text-xl font-black text-slate-900 mt-1">12 Rombel</div>
              </div>
              <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                <div class="text-[11px] text-slate-500">Kehadiran Guru</div>
                <div class="text-xl font-black text-emerald-600 mt-1">99.1%</div>
              </div>
            </div>
            <div class="p-3 rounded-xl bg-indigo-50/60 border border-indigo-100 text-xs text-indigo-900 flex items-center justify-between">
              <span>Sistem Database Siap Sinkronisasi Rapot</span>
              <span class="font-bold text-indigo-700">Terverifikasi</span>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- 6. PROGRAM & KURIKULUM TEMATIK -->
    <section id="kurikulum" class="py-20 bg-slate-50 border-b border-slate-200/80">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-3xl mx-auto mb-16">
          <span class="px-3.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider">
            Kurikulum Terpadu
          </span>
          <h2 class="mt-3 text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
            Harmonisasi Kurikulum Nasional & Nilai Qur'ani
          </h2>
          <p class="mt-3 text-base text-slate-600 leading-relaxed">
            SDI Bina Insan Mandiri Purwokerto merancang pembelajaran tematik yang mengasah kecerdasan intelektual, emosional, dan spiritual secara seimbang.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          
          <div class="bg-white p-8 rounded-3xl border border-slate-200 hover:shadow-xl transition-all">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xl mb-6">
              📖
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-3">1. Tahfidz & Tartil Al-Qur'an</h3>
            <p class="text-xs text-slate-600 leading-relaxed mb-4">
              Program unggulan bimbingan talaqqi, ziyadah hafalan juz 'Amma, dan muroja'ah rutin harian dengan target kelulusan minimal 3 Juz mutqin.
            </p>
            <div class="text-xs font-semibold text-emerald-700 flex items-center gap-1.5">
              <span>Metode Utsmani & Talaqqi Bersanad</span>
            </div>
          </div>

          <div class="bg-white p-8 rounded-3xl border border-slate-200 hover:shadow-xl transition-all">
            <div class="w-12 h-12 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-xl mb-6">
              🔬
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-3">2. Pembelajaran Tematik Terpadu</h3>
            <p class="text-xs text-slate-600 leading-relaxed mb-4">
              Penyelarasan muatan sains, nalar matematika, literasi kebahasaan, dan pendidikan kewarganegaraan dalam tema kontekstual kehidupan anak.
            </p>
            <div class="text-xs font-semibold text-teal-700 flex items-center gap-1.5">
              <span>Kurikulum Tematik & Kurikulum Merdeka</span>
            </div>
          </div>

          <div class="bg-white p-8 rounded-3xl border border-slate-200 hover:shadow-xl transition-all">
            <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xl mb-6">
              🕌
            </div>
            <h3 class="text-xl font-bold text-slate-900 mb-3">3. Pembiasaan Adab & Karakter</h3>
            <p class="text-xs text-slate-600 leading-relaxed mb-4">
              Praktik ibadah praktis sholat dhuha & dzuhur berjamaah, adab terhadap guru & orang tua, sedekah jum'at, dan kemandirian sejak dini.
            </p>
            <div class="text-xs font-semibold text-amber-700 flex items-center gap-1.5">
              <span>Pendidikan Karakter Insan Mandiri</span>
            </div>
          </div>

        </div>

      </div>
    </section>

    <!-- 7. FREQUENTLY ASKED QUESTIONS (FAQ) -->
    <section id="faq" class="py-20 bg-white border-b border-slate-200/80">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center mb-16">
          <span class="px-3.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider">
            Tanya Jawab
          </span>
          <h2 class="mt-3 text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
            Pertanyaan yang Sering Diajukan
          </h2>
          <p class="mt-3 text-base text-slate-600 leading-relaxed">
            Informasi praktis seputar penggunaan sistem SIM-BIM SDI Bina Insan Mandiri Purwokerto.
          </p>
        </div>

        <div class="space-y-4">
          
          <div 
            class="border rounded-2xl transition-all overflow-hidden"
            :class="activeFaq === 0 ? 'border-emerald-500 bg-emerald-50/20 shadow-xs' : 'border-slate-200 bg-white'"
          >
            <button 
              @click="toggleFaq(0)"
              class="w-full px-6 py-4.5 text-left font-bold text-slate-900 flex justify-between items-center text-sm sm:text-base cursor-pointer"
            >
              <span>Bagaimana cara orang tua / wali murid masuk ke Portal SIM-BIM?</span>
              <svg 
                class="w-5 h-5 text-emerald-600 transition-transform duration-200"
                :class="activeFaq === 0 ? 'rotate-180' : ''"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
            <div v-show="activeFaq === 0" class="px-6 pb-5 text-xs sm:text-sm text-slate-600 leading-relaxed">
              Orang tua murid akan diberikan akun resmi berupa email atau nomor telepon yang terdaftar pada saat registrasi siswa. Setelah login, sistem akan otomatis mengarahkan ke <strong>Portal Wali Murid</strong> untuk melihat status kehadiran harian dan riwayat tagihan SPP ananda.
            </div>
          </div>

          <div 
            class="border rounded-2xl transition-all overflow-hidden"
            :class="activeFaq === 1 ? 'border-emerald-500 bg-emerald-50/20 shadow-xs' : 'border-slate-200 bg-white'"
          >
            <button 
              @click="toggleFaq(1)"
              class="w-full px-6 py-4.5 text-left font-bold text-slate-900 flex justify-between items-center text-sm sm:text-base cursor-pointer"
            >
              <span>Apakah sistem presensi guru harus dilakukan di lingkungan sekolah?</span>
              <svg 
                class="w-5 h-5 text-emerald-600 transition-transform duration-200"
                :class="activeFaq === 1 ? 'rotate-180' : ''"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
            <div v-show="activeFaq === 1" class="px-6 pb-5 text-xs sm:text-sm text-slate-600 leading-relaxed">
              Ya, sistem presensi guru & tendik SIM-BIM menggunakan teknologi <em>Geofence GPS</em> yang mewajibkan pengguna berada dalam radius kampus SDI Bina Insan Mandiri Purwokerto untuk memastikan validitas dan kedisiplinan kehadiran.
            </div>
          </div>

          <div 
            class="border rounded-2xl transition-all overflow-hidden"
            :class="activeFaq === 2 ? 'border-emerald-500 bg-emerald-50/20 shadow-xs' : 'border-slate-200 bg-white'"
          >
            <button 
              @click="toggleFaq(2)"
              class="w-full px-6 py-4.5 text-left font-bold text-slate-900 flex justify-between items-center text-sm sm:text-base cursor-pointer"
            >
              <span>Bagaimana cara guru mengatur dan mengajar pelajaran Tematik?</span>
              <svg 
                class="w-5 h-5 text-emerald-600 transition-transform duration-200"
                :class="activeFaq === 2 ? 'rotate-180' : ''"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
            <div v-show="activeFaq === 2" class="px-6 pb-5 text-xs sm:text-sm text-slate-600 leading-relaxed">
              Admin sekolah mengatur master data Tematik (Tema 1 sampai 5 beserta muatan pembelajarannya). Guru dapat melihat jadwal mengajarnya di menu <strong>Jadwal Saya</strong>, di mana jadwal hari ini diberi penanda hijau khusus beserta tombol instan <em>"Masuk Kelas"</em> untuk melakukan absensi murid.
            </div>
          </div>

          <div 
            class="border rounded-2xl transition-all overflow-hidden"
            :class="activeFaq === 3 ? 'border-emerald-500 bg-emerald-50/20 shadow-xs' : 'border-slate-200 bg-white'"
          >
            <button 
              @click="toggleFaq(3)"
              class="w-full px-6 py-4.5 text-left font-bold text-slate-900 flex justify-between items-center text-sm sm:text-base cursor-pointer"
            >
              <span>Apakah data kehadiran dan pembelajaran dapat diekspor untuk kebutuhan rapot?</span>
              <svg 
                class="w-5 h-5 text-emerald-600 transition-transform duration-200"
                :class="activeFaq === 3 ? 'rotate-180' : ''"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
            <div v-show="activeFaq === 3" class="px-6 pb-5 text-xs sm:text-sm text-slate-600 leading-relaxed">
              Tentu. Tersedia menu <strong>Rekap Presensi & KBM</strong> dengan filter kelas dan rentang tanggal yang lengkap, serta tombol ekspor ke format Microsoft Excel (.xlsx) yang siap disinkronkan ke pengisian rapot siswa.
            </div>
          </div>

        </div>

      </div>
    </section>

    <!-- 8. CALL TO ACTION BANNER -->
    <section class="py-16 bg-gradient-to-r from-emerald-900 via-teal-900 to-emerald-950 text-white relative overflow-hidden">
      <!-- Glow effect -->
      <div class="absolute -right-10 -bottom-10 w-80 h-80 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none"></div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <div class="max-w-3xl mx-auto space-y-6">
          <span class="inline-block px-4 py-1 rounded-full bg-white/10 text-emerald-200 text-xs font-bold border border-white/20">
            Akses Terpadu SDI BIM Purwokerto
          </span>
          <h2 class="text-3xl sm:text-4xl md:text-5xl font-black tracking-tight leading-tight">
            Siap Membangun Masa Depan Pendidikan Islam Terbaik?
          </h2>
          <p class="text-sm sm:text-base text-emerald-100/90 leading-relaxed">
            Akses portal SIM-BIM sekarang untuk kemudahan presensi guru, monitoring pembelajaran tematik, dan pendampingan putra-putri tercinta.
          </p>
          <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
            <button 
              @click="handlePortalAccess"
              class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-emerald-400 to-teal-400 hover:from-emerald-300 hover:to-teal-300 text-slate-950 font-black text-base shadow-xl shadow-emerald-500/30 transition-all hover:-translate-y-0.5 active:translate-y-0 cursor-pointer"
            >
              {{ userPortalLabel }}
            </button>
            <button 
              @click="scrollToSection('kontak')"
              class="w-full sm:w-auto px-6 py-4 rounded-2xl bg-white/10 hover:bg-white/15 text-white font-semibold text-base border border-white/20 transition-all cursor-pointer"
            >
              Hubungi Sekretariat
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- 9. FOOTER & KONTAK -->
    <footer id="kontak" class="bg-slate-950 text-slate-400 pt-16 pb-12 border-t border-slate-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 pb-12 border-b border-slate-800">
          
          <!-- Col 1: Identity & Logo (5 Cols) -->
          <div class="lg:col-span-5 space-y-4">
            <div class="flex items-center gap-3">
              <img src="/logo.png" alt="Logo SDI Bina Insan Mandiri" class="w-12 h-12 object-contain" />
              <div>
                <span class="font-black text-xl text-white tracking-tight">SIM-BIM</span>
                <p class="text-xs text-emerald-400 font-bold">SDI Bina Insan Mandiri Purwokerto</p>
              </div>
            </div>
            <p class="text-xs text-slate-400 leading-relaxed pr-6">
              Sistem Informasi Manajemen Terpadu yang mendukung kelancaran kegiatan belajar mengajar, pengelolaan kehadiran geofence, kurikulum tematik, serta transparansi komunikasi dengan wali murid.
            </p>
            <div class="pt-2 flex items-center gap-3 text-xs text-emerald-400 font-semibold">
              <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
              <span>Terakreditasi Baik • Smart Islamic Elementary School</span>
            </div>
          </div>

          <!-- Col 2: Navigasi Cepat (3 Cols) -->
          <div class="lg:col-span-3 space-y-3">
            <h4 class="text-sm font-bold text-white uppercase tracking-wider">Navigasi Portal</h4>
            <ul class="space-y-2 text-xs">
              <li><button @click="scrollToSection('hero')" class="hover:text-emerald-400 transition-colors">Beranda</button></li>
              <li><button @click="scrollToSection('tentang')" class="hover:text-emerald-400 transition-colors">Profil Sekolah</button></li>
              <li><button @click="scrollToSection('fitur')" class="hover:text-emerald-400 transition-colors">Fitur Unggulan</button></li>
              <li><button @click="scrollToSection('kurikulum')" class="hover:text-emerald-400 transition-colors">Kurikulum Tematik</button></li>
              <li><button @click="scrollToSection('faq')" class="hover:text-emerald-400 transition-colors">Tanya Jawab (FAQ)</button></li>
            </ul>
          </div>

          <!-- Col 3: Alamat & Kontak (4 Cols) -->
          <div class="lg:col-span-4 space-y-3">
            <h4 class="text-sm font-bold text-white uppercase tracking-wider">Kontak & Lokasi</h4>
            <div class="space-y-2.5 text-xs text-slate-300">
              <div class="flex items-start gap-2.5">
                <span class="text-emerald-400 font-bold shrink-0">📍</span>
                <span>Jl. Wadas Kelir RT.04 RW.05 Kel. Karangklesem, Kec. Purwokerto Selatan, Kab. Banyumas 53144</span>
              </div>
              <div class="flex items-center gap-2.5">
                <span class="text-emerald-400 font-bold shrink-0">📞</span>
                <span>(0281) 638-xxx / Sekretariat SDI BIM</span>
              </div>
              <div class="flex items-center gap-2.5">
                <span class="text-emerald-400 font-bold shrink-0">💬</span>
                <span>WhatsApp Humas: +62 812-3456-7890</span>
              </div>
              <div class="flex items-center gap-2.5">
                <span class="text-emerald-400 font-bold shrink-0">✉️</span>
                <span>info@sdibinainsanmandiri.sch.id</span>
              </div>
            </div>
          </div>

        </div>

        <!-- Copyright -->
        <div class="pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
          <p>© 2026 SDI Bina Insan Mandiri Purwokerto. Seluruh hak cipta dilindungi.</p>
          <p class="text-slate-400 font-medium">SIM-BIM v2.4 Enterprise Edition</p>
        </div>

      </div>
    </footer>

  </div>
</template>

<style scoped>
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

.animate-fadeIn {
  animation: fadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
