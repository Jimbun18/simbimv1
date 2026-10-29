<script setup lang="ts">
import { ref, computed } from 'vue'
import { extracurricularActivities, type ExtracurricularItem } from './landingData'

const props = withDefaults(defineProps<{
  sectionId?: string
  showViewAllLink?: boolean
}>(), {
  sectionId: 'ekstrakurikuler',
  showViewAllLink: false
})

const activeFilter = ref<'all' | 'wajib' | 'pilihan'>('all')

const filteredList = computed<ExtracurricularItem[]>(() => {
  if (activeFilter.value === 'all') {
    return extracurricularActivities
  }
  return extracurricularActivities.filter(item => item.type === activeFilter.value)
})

const wajibCount = computed(() => extracurricularActivities.filter(e => e.type === 'wajib').length)
const pilihanCount = computed(() => extracurricularActivities.filter(e => e.type === 'pilihan').length)
</script>

<template>
  <section :id="sectionId" class="py-20 bg-slate-50 border-b border-slate-200/80 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
      
      <!-- Section Header -->
      <div 
        v-reveal="{ type: 'fade-up', delay: 50 }"
        class="flex flex-col md:flex-row md:items-end justify-between gap-6"
      >
        <div class="max-w-3xl space-y-3">
          <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider">
            <span>🏆</span>
            <span>Pengembangan Minat, Bakat & Karakter</span>
          </div>
          
          <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight">
            Ekstrakurikuler <br class="hidden sm:inline" />
            <span class="text-emerald-700">SDI Bina Insan Mandiri Purwokerto</span>
          </h2>
          
          <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
            Menyalurkan energi positif, menggali fitrah keunikan bakat ananda, serta melatih kecakapan fisik jasmani, seni budaya islami, dan kepemimpinan santun melalui ragam ekstrakurikuler <strong>Wajib</strong> dan <strong>Pilihan</strong>.
          </p>
        </div>

        <!-- Tombol Link Navigasi jika di Beranda -->
        <div v-if="showViewAllLink" class="shrink-0">
          <router-link
            to="/pendidikan#ekstrakurikuler"
            v-splash="'emerald'"
            class="btn-splash inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs sm:text-sm transition-all border border-emerald-200 shadow-xs hover:shadow-md cursor-pointer hover:-translate-y-0.5 active:translate-y-0"
          >
            <span>Pelajari di Halaman Pendidikan</span>
            <span>&rarr;</span>
          </router-link>
        </div>
      </div>

      <!-- Filter Kategori Tabs (Semua, Wajib, Pilihan) -->
      <div 
        v-reveal="{ type: 'fade-up', delay: 80 }"
        class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5 pt-1"
      >
        <button
          @click="activeFilter = 'all'"
          v-splash
          class="btn-splash px-4 sm:px-5 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer border flex items-center gap-2 shadow-xs"
          :class="activeFilter === 'all' 
            ? 'bg-slate-900 text-white border-slate-900 shadow-sm' 
            : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100'"
        >
          <span>Semua Ekstrakurikuler</span>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-black" :class="activeFilter === 'all' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'">
            {{ extracurricularActivities.length }}
          </span>
        </button>

        <button
          @click="activeFilter = 'wajib'"
          v-splash="'emerald'"
          class="btn-splash px-4 sm:px-5 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer border flex items-center gap-2 shadow-xs"
          :class="activeFilter === 'wajib' 
            ? 'bg-emerald-700 text-white border-emerald-700 shadow-sm' 
            : 'bg-white text-slate-700 border-slate-200 hover:bg-emerald-50 hover:text-emerald-800'"
        >
          <span>⭐ Ekstrakurikuler Wajib</span>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-black" :class="activeFilter === 'wajib' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-800'">
            {{ wajibCount }}
          </span>
        </button>

        <button
          @click="activeFilter = 'pilihan'"
          v-splash="'teal'"
          class="btn-splash px-4 sm:px-5 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer border flex items-center gap-2 shadow-xs"
          :class="activeFilter === 'pilihan' 
            ? 'bg-teal-700 text-white border-teal-700 shadow-sm' 
            : 'bg-white text-slate-700 border-slate-200 hover:bg-teal-50 hover:text-teal-800'"
        >
          <span>🎯 Ekstrakurikuler Pilihan</span>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-black" :class="activeFilter === 'pilihan' ? 'bg-white/20 text-white' : 'bg-teal-100 text-teal-800'">
            {{ pilihanCount }}
          </span>
        </button>
      </div>

      <!-- Grid Cards Ekstrakurikuler (2 Kolom Mobile, 3 Kolom Tablet, 4 Kolom Desktop) -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5 sm:gap-6">
        <div 
          v-for="(ekskul, idx) in filteredList" 
          :key="ekskul.id"
          v-reveal="{ type: 'fade-up', delay: 70 + (idx % 4) * 60 }"
          class="group bg-white hover:bg-slate-50/50 rounded-2xl sm:rounded-3xl border border-slate-200/90 hover:border-emerald-300 shadow-xs hover:shadow-xl transition-all duration-300 hover:-translate-y-1.5 flex flex-col overflow-hidden"
        >
          <!-- VISUAL HEADER CONTAINER (Foto atau Estetika Gradasi Modern) -->
          <div class="relative h-32 sm:h-44 w-full overflow-hidden bg-slate-900">
            <!-- Jika foto sudah ada -->
            <template v-if="ekskul.image">
              <img 
                :src="ekskul.image" 
                :alt="ekskul.name" 
                class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-500"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-black/30 pointer-events-none"></div>

              <!-- Badge Tipe & Icon Mengambang di Atas Foto -->
              <div class="absolute top-2.5 sm:top-3.5 inset-x-2.5 sm:inset-x-3.5 flex items-center justify-between z-10 pointer-events-none">
                <span 
                  class="px-2 sm:px-2.5 py-0.5 rounded-full text-[9px] sm:text-[10px] font-black uppercase tracking-wider backdrop-blur-md shadow-md"
                  :class="ekskul.type === 'wajib' 
                    ? 'bg-amber-400 text-slate-950' 
                    : 'bg-white/90 text-slate-900'"
                >
                  {{ ekskul.type === 'wajib' ? '⭐ Wajib' : '🎯 Pilihan' }}
                </span>

                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-black/40 backdrop-blur-md flex items-center justify-center text-sm sm:text-base shadow-md border border-white/20 text-white">
                  {{ ekskul.icon }}
                </span>
              </div>
            </template>

            <!-- Placeholder Gradasi Elegan Jika Foto Belum Diupload ("Fotonya Menyusul") -->
            <div 
              v-else 
              class="w-full h-full bg-gradient-to-tr flex flex-col justify-between p-3.5 sm:p-5 relative select-none"
              :class="ekskul.gradient"
            >
              <!-- Watermark Icon Artistik di Belakang -->
              <span class="absolute -right-3 -bottom-4 text-6xl sm:text-7xl opacity-20 pointer-events-none transform group-hover:scale-110 group-hover:rotate-6 transition-transform duration-500">
                {{ ekskul.icon }}
              </span>

              <!-- Badge Tipe (Wajib / Pilihan) di Kiri Atas -->
              <div class="relative z-10 flex items-center justify-between">
                <span 
                  class="px-2 sm:px-2.5 py-0.5 rounded-full text-[9px] sm:text-[10px] font-black uppercase tracking-wider backdrop-blur-md shadow-xs"
                  :class="ekskul.type === 'wajib' 
                    ? 'bg-amber-400 text-slate-950 font-black' 
                    : 'bg-white/20 text-white border border-white/20'"
                >
                  {{ ekskul.type === 'wajib' ? '⭐ Wajib' : '🎯 Pilihan' }}
                </span>

                <!-- Icon Bulat Ekskul -->
                <span class="w-8 h-8 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center text-base sm:text-lg shadow-xs border border-white/20">
                  {{ ekskul.icon }}
                </span>
              </div>

              <!-- Tag Status: Foto Menyusul -->
              <div class="relative z-10 flex items-center gap-1.5 text-[9px] sm:text-[10px] text-white/80 font-mono">
                <span>📸</span>
                <span>Dokumentasi Segera Hadir</span>
              </div>
            </div>

            <!-- Soft Overlay Vignette -->
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/40 via-transparent to-transparent pointer-events-none"></div>
          </div>

          <!-- CARD CONTENT -->
          <div class="p-3.5 sm:p-5 flex-1 flex flex-col justify-between space-y-3">
            
            <div class="space-y-1.5 sm:space-y-2">
              <!-- Label Kategori & Target -->
              <div class="flex items-center justify-between text-[10px] sm:text-[11px] text-slate-400">
                <span 
                  class="px-2 py-0.5 rounded-full text-[8px] sm:text-[9px] font-bold uppercase tracking-wider border"
                  :class="ekskul.badgeColor"
                >
                  {{ ekskul.type === 'wajib' ? 'Semua Siswa' : 'Peminatan' }}
                </span>

                <span class="text-[9px] sm:text-[10px] font-mono text-slate-500">
                  {{ ekskul.schedule || 'Pekan ini' }}
                </span>
              </div>

              <!-- Judul Ekskul -->
              <h3 class="font-black text-sm sm:text-base text-slate-900 group-hover:text-emerald-700 transition-colors leading-snug">
                {{ ekskul.name }}
              </h3>

              <p class="text-[10px] sm:text-xs font-semibold text-emerald-700 leading-snug">
                {{ ekskul.subtitle }}
              </p>

              <!-- Deskripsi Ringkas -->
              <p class="text-[11px] sm:text-xs text-slate-600 line-clamp-3 leading-relaxed">
                {{ ekskul.desc }}
              </p>
            </div>

            <!-- Manfaat Utama (3 Poin) -->
            <div class="pt-2 sm:pt-3 border-t border-slate-100 space-y-1 sm:space-y-1.5">
              <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-slate-400">
                Fokus Pengembangan:
              </div>
              <ul class="text-[10px] sm:text-xs text-slate-700 space-y-1">
                <li v-for="(b, bIdx) in ekskul.benefits" :key="bIdx" class="flex items-start gap-1.5">
                  <span class="text-emerald-600 font-bold mt-0.5 shrink-0 text-[10px]">✓</span>
                  <span class="line-clamp-1 text-[10px] sm:text-[11px]">{{ b }}</span>
                </li>
              </ul>
            </div>

          </div>

        </div>
      </div>

      <!-- BANNER EDUKASI: KETENTUAN EKSTRAKURIKULER WAJIB & PILIHAN -->
      <div 
        v-reveal="{ type: 'fade-up', delay: 150 }"
        class="rounded-3xl p-6 sm:p-8 bg-gradient-to-r from-[#022f24] via-[#044c3b] to-[#02281e] text-white shadow-xl relative overflow-hidden"
      >
        <div class="absolute -right-10 -bottom-10 w-60 h-60 bg-emerald-400/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="space-y-2 max-w-3xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-400/20 text-amber-300 border border-amber-400/30 text-[11px] font-bold uppercase tracking-wider">
              <span>💡</span>
              <span>Sinergi Pengembangan Karakter & Kebugaran</span>
            </div>
            
            <h3 class="text-lg sm:text-xl md:text-2xl font-black text-white leading-snug">
              Ketentuan Keikutsertaan Ekstrakurikuler Siswa
            </h3>
            
            <p class="text-xs sm:text-sm text-emerald-100/90 leading-relaxed">
              Seluruh siswa SDI Bina Insan Mandiri mengikuti <strong>4 Ekstrakurikuler Wajib</strong> (Silat, Renang, BIM Voice, & Pramuka SIT) untuk mengasah ketangkasan fisik dan karakter dasar, serta dapat memilih <strong>1–2 Ekstrakurikuler Pilihan</strong> sesuai minat, bakat, dan kecenderungan fitrah ananda.
            </p>
          </div>

          <div class="shrink-0 flex items-center gap-3">
            <router-link
              to="/spmb"
              v-splash="'amber'"
              class="btn-splash px-6 py-3 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs sm:text-sm shadow-md hover:shadow-lg transition-all cursor-pointer"
            >
              Info Pendaftaran Siswa &rarr;
            </router-link>
          </div>
        </div>
      </div>

    </div>
  </section>
</template>

<style scoped>
/* Estetika kartu ekstrakurikuler responsif */
</style>
