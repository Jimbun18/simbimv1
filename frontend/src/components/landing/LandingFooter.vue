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
  <footer class="bg-[#021f18] text-white border-t border-emerald-900/60 pt-16 pb-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
      
      <!-- Top Grid Footer -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10">
        
        <!-- Col 1: Identitas Sekolah -->
        <div class="lg:col-span-5 space-y-4">
          <div class="flex items-center gap-3 cursor-pointer" @click="emit('navigate', 'hero')">
            <img 
              src="/logo.png" 
              alt="Logo SDI Bina Insan Mandiri Purwokerto" 
              class="w-12 h-12 object-contain"
            />
            <div>
              <span class="font-black text-xl text-white tracking-tight">SDI Bina Insan Mandiri</span>
              <p class="text-xs text-emerald-400 font-semibold">Yayasan Bina Insan Mandiri Purwokerto</p>
            </div>
          </div>

          <p class="text-xs sm:text-sm text-emerald-100/80 leading-relaxed font-light">
            Sekolah Dasar Islam unggulan berbasis fitrah yang berfokus membentuk generasi pembelajar bertauhid kokoh, berakhlak mulia, hafidz Al-Qur'an, dan berjiwa mandiri pembina peradaban.
          </p>

          <div class="space-y-2 text-xs text-emerald-200/90 pt-1">
            <div class="flex items-start gap-2.5">
              <span class="text-emerald-400 shrink-0">📍</span>
              <span>Jl. Wadas Kelir RT.04 RW.05 Kel. Karangklesem, Kec. Purwokerto Selatan, Kab. Banyumas 53144</span>
            </div>
            <div class="flex items-center gap-2.5">
              <span class="text-emerald-400 shrink-0">📞</span>
              <span>0281-635812 / +62 812-0000-0001 (SPMB)</span>
            </div>
            <div class="flex items-center gap-2.5">
              <span class="text-emerald-400 shrink-0">✉️</span>
              <span>sdibim.pwt@gmail.com</span>
            </div>
          </div>
        </div>

        <!-- Col 2: Navigasi Cepat Halaman -->
        <div class="lg:col-span-3 space-y-3">
          <h4 class="font-bold text-sm text-emerald-300 uppercase tracking-wider">Navigasi Utama</h4>
          <ul class="space-y-2 text-xs text-emerald-100/80 font-medium">
            <li>
              <button @click="emit('navigate', 'hero')" class="hover:text-amber-300 transition cursor-pointer">
                • Beranda Utama
              </button>
            </li>
            <li>
              <button @click="emit('navigate', 'profil')" class="hover:text-amber-300 transition cursor-pointer">
                • 1. Profil (Singkat, Visi-Misi, Guru, Janji)
              </button>
            </li>
            <li>
              <button @click="emit('navigate', 'pendidikan')" class="hover:text-amber-300 transition cursor-pointer">
                • 2. Pendidikan (Fitrah & Peradaban)
              </button>
            </li>
            <li>
              <button @click="emit('navigate', 'artikel')" class="hover:text-amber-300 transition cursor-pointer">
                • 3. Artikel & Warta Sekolah
              </button>
            </li>
            <li>
              <button @click="emit('navigate', 'galeri')" class="hover:text-amber-300 transition cursor-pointer">
                • 4. Galeri Kegiatan Santri
              </button>
            </li>
            <li>
              <button @click="emit('navigate', 'prestasi')" class="hover:text-amber-300 transition cursor-pointer">
                • 5. Prestasi Santri & Sekolah
              </button>
            </li>
            <li>
              <button @click="emit('navigate', 'spmb')" class="hover:text-amber-300 transition cursor-pointer">
                • 6. Info SPMB 2026/2027
              </button>
            </li>
          </ul>
        </div>

        <!-- Col 3: Portal SIM-BIM & Jam Layanan -->
        <div class="lg:col-span-4 space-y-4">
          <h4 class="font-bold text-sm text-emerald-300 uppercase tracking-wider">Sistem Digital SIM-BIM</h4>
          <p class="text-xs text-emerald-100/80 leading-relaxed font-light">
            Portal pintar terpadu untuk KBM Tematik, presensi geofence, mutaba'ah tahfidz, dan transparansi portal wali murid.
          </p>

          <button 
            @click="emit('open-portal')"
            class="w-full inline-flex items-center justify-center gap-2 py-3 px-5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white text-xs font-bold transition shadow-md cursor-pointer"
          >
            <span>{{ userPortalLabel }}</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
          </button>

          <div class="p-3 bg-white/5 rounded-xl border border-white/10 text-[11px] text-emerald-200 space-y-1">
            <div class="font-bold text-white">Jam Operasional Layanan Sekolah:</div>
            <div>Senin – Kamis : 07.00 – 14.30 WIB</div>
            <div>Jum'at : 07.00 – 11.30 WIB</div>
            <div>Sabtu : 07.00 – 13.00 WIB (Layanan SPMB & Ekskul)</div>
          </div>
        </div>

      </div>

      <!-- Bottom Copyright & Tag -->
      <div class="pt-8 border-t border-emerald-900/60 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-emerald-400/80">
        <div>
          &copy; {{ new Date().getFullYear() }} <strong>SDI Bina Insan Mandiri Purwokerto</strong>. Hak cipta dilindungi.
        </div>
        <div class="flex items-center gap-4 text-[11px]">
          <span>NPSN: 70036695</span>
          <span>•</span>
          <span>Akreditasi A</span>
          <span>•</span>
          <span class="text-amber-300 font-bold">Pendidikan Berbasis Fitrah</span>
        </div>
      </div>

    </div>
  </footer>
</template>
