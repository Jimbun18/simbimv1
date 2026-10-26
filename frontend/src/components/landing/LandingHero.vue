<script setup lang="ts">
defineProps<{
  userPortalLabel: string
}>()

const emit = defineEmits<{
  (e: 'navigate', id: string): void
  (e: 'open-portal'): void
}>()
</script>

<template>
  <section id="hero" class="relative overflow-hidden pt-12 pb-20 md:pt-20 md:pb-32 bg-gradient-to-b from-[#032e23] via-[#044737] to-[#02261d] text-white">
    <!-- Ambient Blur Lighting Blobs -->
    <div class="absolute -top-24 -left-24 w-96 h-96 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute top-1/2 -right-24 w-96 h-96 bg-teal-400/15 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-20 left-1/3 w-80 h-80 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
        
        <!-- Left Column: Headline & Action -->
        <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
          
          <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-white/10 border border-white/20 backdrop-blur-md text-emerald-200 text-xs sm:text-sm font-semibold">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Sekolah Dasar Islam Unggulan • Purwokerto</span>
          </div>

          <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black tracking-tight leading-tight sm:leading-tight">
            Mewujudkan Generasi <br class="hidden sm:inline" />
            <span class="bg-gradient-to-r from-emerald-300 via-teal-200 to-amber-200 bg-clip-text text-transparent">
              Qur'ani & Unggul
            </span>
            Berbasis Fitrah
          </h1>

          <p class="text-sm sm:text-base md:text-lg text-emerald-100/90 max-w-2xl mx-auto lg:mx-0 font-normal leading-relaxed">
            Selamat datang di portal resmi <strong>SDI Bina Insan Mandiri Purwokerto</strong>. 
            Mendidik dengan cinta, menumbuhkan fitrah alami anak, dan membangun karakter peradaban islam yang kokoh, mandiri, dan berwawasan luas.
          </p>

          <!-- Action Buttons -->
          <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-2">
            <router-link 
              to="/spmb"
              class="w-full sm:w-auto inline-flex items-center justify-center gap-3 px-7 py-3.5 rounded-2xl bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black text-sm sm:text-base shadow-xl shadow-amber-500/20 transition-all hover:-translate-y-0.5 active:translate-y-0"
            >
              <span>Info Pendaftaran SPMB</span>
              <svg class="w-5 h-5 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
              </svg>
            </router-link>

            <button 
              @click="emit('open-portal')"
              class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-2xl bg-emerald-600/60 hover:bg-emerald-600/80 text-white font-bold text-sm sm:text-base border border-emerald-400/30 backdrop-blur-md transition-all cursor-pointer"
            >
              <span>{{ userPortalLabel }}</span>
              <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
              </svg>
            </button>
          </div>

          <!-- Highlights Badges -->
          <div class="pt-6 grid grid-cols-2 sm:grid-cols-4 gap-3 border-t border-white/10 text-left">
            <div class="p-3.5 rounded-xl bg-white/5 border border-white/10 backdrop-blur-xs">
              <div class="text-2xl font-black text-amber-300">Fitrah</div>
              <div class="text-[11px] text-emerald-200 font-medium">Pilar Pendidikan Utama</div>
            </div>
            <div class="p-3.5 rounded-xl bg-white/5 border border-white/10 backdrop-blur-xs">
              <div class="text-2xl font-black text-emerald-300">3+ Juz</div>
              <div class="text-[11px] text-emerald-200 font-medium">Target Mutqin Qur'an</div>
            </div>
            <div class="p-3.5 rounded-xl bg-white/5 border border-white/10 backdrop-blur-xs">
              <div class="text-2xl font-black text-teal-300">100%</div>
              <div class="text-[11px] text-emerald-200 font-medium">Pendidik Berdedikasi</div>
            </div>
            <div class="p-3.5 rounded-xl bg-white/5 border border-white/10 backdrop-blur-xs">
              <div class="text-2xl font-black text-rose-300">Ramah</div>
              <div class="text-[11px] text-emerald-200 font-medium">Lingkungan Belajar Anak</div>
            </div>
          </div>

        </div>

        <!-- Right Column: Interactive Feature Card Showcase -->
        <div class="lg:col-span-5 relative">
          <div class="relative mx-auto max-w-md lg:max-w-none">
            
            <!-- Glow Frame -->
            <div class="absolute -inset-1 bg-gradient-to-r from-emerald-500 to-amber-500 rounded-3xl blur-xl opacity-30 animate-pulse"></div>

            <!-- Main Showcase Card -->
            <div class="relative bg-slate-900/90 border border-emerald-500/30 rounded-3xl p-6 shadow-2xl backdrop-blur-xl text-white space-y-5">
              
              <div class="flex items-center justify-between border-b border-white/10 pb-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-emerald-600/30 border border-emerald-400/30 flex items-center justify-center">
                    <img src="/logo.png" alt="Logo SDI BIM" class="w-7 h-7 object-contain" />
                  </div>
                  <div>
                    <h4 class="text-sm font-bold text-white">SDI Bina Insan Mandiri</h4>
                    <p class="text-[10px] text-emerald-300">Purwokerto Selatan, Banyumas</p>
                  </div>
                </div>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-bold border border-emerald-500/30">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                  SPMB Dibuka
                </span>
              </div>

              <!-- Nilai Pokok Sekolah -->
              <div class="space-y-2.5">
                <div class="p-3 rounded-2xl bg-white/5 border border-white/10 flex items-start gap-3">
                  <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-300 flex items-center justify-center text-sm shrink-0">
                    🌱
                  </div>
                  <div>
                    <div class="text-xs font-bold text-white">Pendidikan Berbasis Fitrah</div>
                    <div class="text-[11px] text-slate-300 leading-tight">Menumbuhkan keimanan, akal, dan bakat unik anak tanpa paksaan.</div>
                  </div>
                </div>

                <div class="p-3 rounded-2xl bg-white/5 border border-white/10 flex items-start gap-3">
                  <div class="w-8 h-8 rounded-lg bg-teal-500/20 text-teal-300 flex items-center justify-center text-sm shrink-0">
                    📖
                  </div>
                  <div>
                    <div class="text-xs font-bold text-white">Generasi Qur'ani Tartil</div>
                    <div class="text-[11px] text-slate-300 leading-tight">Metode tahsin berulang dan bimbingan tahfidz mutqin beradab.</div>
                  </div>
                </div>

                <div class="p-3 rounded-2xl bg-white/5 border border-white/10 flex items-start gap-3">
                  <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-300 flex items-center justify-center text-sm shrink-0">
                    🏛️
                  </div>
                  <div>
                    <div class="text-xs font-bold text-white">Membangun Peradaban</div>
                    <div class="text-[11px] text-slate-300 leading-tight">Sinergi erat Tri Sentra: Keluarga di rumah, Guru di sekolah, dan Masjid.</div>
                  </div>
                </div>
              </div>

              <!-- Quick Link Button inside card -->
              <div class="pt-1">
                <router-link 
                  to="/profil"
                  class="w-full py-2.5 px-4 rounded-xl bg-white/10 hover:bg-white/15 text-emerald-200 text-xs font-bold transition flex items-center justify-between border border-white/10"
                >
                  <span>Kenali Lebih Dekat Profil SDI BIM</span>
                  <span>&rarr;</span>
                </router-link>
              </div>

            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
</template>
