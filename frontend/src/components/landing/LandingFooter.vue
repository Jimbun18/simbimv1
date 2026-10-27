<script setup lang="ts">
import { useAuthStore } from '../../stores/auth'
import FloatingWhatsApp from './FloatingWhatsApp.vue'

const props = defineProps<{
  userPortalLabel?: string
}>()

const emit = defineEmits<{
  (e: 'open-portal'): void
}>()

const auth = useAuthStore()

const getPortalLabel = () => {
  if (props.userPortalLabel) return props.userPortalLabel
  if (!auth.isAuthenticated) return 'Masuk SIM-BIM'
  if (auth.isAdmin) return 'Buka Dashboard Admin'
  if (auth.isGuru) return 'Buka Presensi & KBM'
  if (auth.isWali) return 'Buka Portal Wali Murid'
  return 'Buka Dashboard'
}
</script>

<template>
  <footer class="bg-[#021f18] text-white border-t border-emerald-900/60 pt-16 pb-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
      
      <!-- Top Grid Footer -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10">
        
        <!-- Col 1: Identitas Sekolah -->
        <div class="lg:col-span-5 space-y-4">
          <router-link to="/" class="flex items-center gap-3">
            <img 
              src="/logo.png" 
              alt="Logo SDI Bina Insan Mandiri Purwokerto" 
              class="w-12 h-12 object-contain"
            />
            <div>
              <span class="font-black text-xl text-white tracking-tight">SDI Bina Insan Mandiri</span>
              <p class="text-xs text-emerald-400 font-semibold">Yayasan Bina Insan Mandiri Purwokerto</p>
            </div>
          </router-link>

          <p class="text-xs sm:text-sm text-emerald-100/80 leading-relaxed font-light">
            Sekolah Dasar Islam unggulan berbasis deep learning yang berfokus membentuk generasi pembelajar bertauhid kokoh, berakhlak mulia, hafidz Al-Qur'an, dan berjiwa mandiri pembina peradaban.
          </p>

          <div class="space-y-2.5 text-xs text-emerald-200/90 pt-1">
            <div class="flex items-start gap-2.5">
              <span class="text-emerald-400 shrink-0">📍</span>
              <span>Jl. Wadas Kelir RT.04 RW.05 Kel. Karangklesem, Kec. Purwokerto Selatan, Kab. Banyumas 53144</span>
            </div>
            <div class="flex items-center gap-2.5">
              <span class="text-emerald-400 shrink-0">📱</span>
              <a 
                href="https://wa.me/6285138568694?text=Bismillah,%20Assalamu'alaikum%20Sekretariat%20SDI%20BIM" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="hover:text-amber-300 transition"
              >
                <strong>0851-3856-8694</strong> (Sekretariat)
              </a>
              <span class="text-emerald-500">•</span>
              <a 
                href="https://wa.me/6285150725032?text=Bismillah,%20Assalamu'alaikum%20Panitia%20SPMB%20SDI%20BIM" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="hover:text-amber-300 transition"
              >
                <strong>0851-5072-5032</strong> (SPMB)
              </a>
            </div>
            <div class="flex items-center gap-2.5">
              <span class="text-emerald-400 shrink-0">✉️</span>
              <a href="mailto:sdibim.pwt@gmail.com" class="hover:text-amber-300 transition">sdibim.pwt@gmail.com</a>
            </div>
          </div>

          <!-- Media Sosial Resmi SDI BIM -->
          <div class="pt-3 flex flex-wrap items-center gap-2">
            <a 
              href="https://instagram.com/sdislambim_pwt" 
              target="_blank" 
              rel="noopener noreferrer" 
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/10 hover:bg-gradient-to-r hover:from-purple-600 hover:to-pink-600 text-white text-[11px] font-semibold border border-white/15 transition"
            >
              <span>📷</span>
              <span>@sdislambim_pwt</span>
            </a>
            <a 
              href="https://facebook.com/SDbimpwt" 
              target="_blank" 
              rel="noopener noreferrer" 
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/10 hover:bg-blue-600 text-white text-[11px] font-semibold border border-white/15 transition"
            >
              <span>👥</span>
              <span>SDbimpwt</span>
            </a>
            <a 
              href="https://bit.ly/SPMB_BIMS" 
              target="_blank" 
              rel="noopener noreferrer" 
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-400/20 hover:bg-amber-400 hover:text-slate-950 text-amber-300 text-[11px] font-bold border border-amber-400/30 transition"
            >
              <span>📝</span>
              <span>bit.ly/SPMB_BIMS</span>
            </a>
          </div>
        </div>

        <!-- Col 2: Navigasi Cepat Halaman -->
        <div class="lg:col-span-3 space-y-3">
          <h4 class="font-bold text-sm text-emerald-300 uppercase tracking-wider">Halaman Resmi</h4>
          <ul class="space-y-2 text-xs text-emerald-100/80 font-medium">
            <li>
              <router-link to="/" class="hover:text-amber-300 transition">
                • Beranda
              </router-link>
            </li>
            <li>
              <router-link to="/profil" class="hover:text-amber-300 transition">
                • Profil Sekolah
              </router-link>
            </li>
            <li>
              <router-link to="/pendidikan" class="hover:text-amber-300 transition">
                • Pendidikan & Deep Learning
              </router-link>
            </li>
            <li>
              <router-link to="/artikel" class="hover:text-amber-300 transition">
                • Artikel & Warta Sekolah
              </router-link>
            </li>
            <li>
              <router-link to="/galeri" class="hover:text-amber-300 transition">
                • Galeri Kegiatan Siswa
              </router-link>
            </li>
            <li>
              <router-link to="/prestasi" class="hover:text-amber-300 transition">
                • Prestasi Siswa & Sekolah
              </router-link>
            </li>
            <li>
              <router-link to="/spmb" class="hover:text-amber-300 transition">
                • Info SPMB 2026/2027
              </router-link>
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
            <span>{{ getPortalLabel() }}</span>
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
          <span>Purwokerto Selatan, Banyumas</span>
          <span>•</span>
          <span class="text-amber-300 font-bold">Pendidikan Berbasis Deep Learning</span>
        </div>
      </div>

    </div>

    <!-- FLOATING WHATSAPP BUTTON RESMI (Sekretariat & SPMB) -->
    <FloatingWhatsApp />
  </footer>
</template>
