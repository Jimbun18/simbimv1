<script setup lang="ts">
import { ref, computed } from 'vue'
import { galleryItems, type GalleryItem } from './landingData'

const selectedCategory = ref<string>('all')
const activeModalItem = ref<GalleryItem | null>(null)

const filteredItems = computed(() => {
  if (selectedCategory.value === 'all') return galleryItems
  return galleryItems.filter(item => item.category === selectedCategory.value)
})

const openLightbox = (item: GalleryItem) => {
  activeModalItem.value = item
}

const closeLightbox = () => {
  activeModalItem.value = null
}
</script>

<template>
  <section id="galeri" class="py-20 bg-slate-50 border-b border-slate-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
      
      <!-- Section Title Header -->
      <div class="text-center max-w-3xl mx-auto space-y-3">
        <span class="px-3.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider">
          Bagian 4 • Dokumentasi Sekolah
        </span>
        <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
          Galeri Kegiatan Santri
        </h2>
        <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
          Potret keceriaan, semangat belajar fitrah, dan ragam aktivitas bermakna santri SDI Bina Insan Mandiri Purwokerto di dalam maupun luar kelas.
        </p>

        <!-- Category Filters -->
        <div class="flex flex-wrap items-center justify-center gap-2 pt-2">
          <button
            @click="selectedCategory = 'all'"
            class="px-4 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer border"
            :class="selectedCategory === 'all' ? 'bg-emerald-700 text-white border-emerald-700 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100'"
          >
            Semua Foto
          </button>
          <button
            @click="selectedCategory = 'kbm'"
            class="px-4 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer border"
            :class="selectedCategory === 'kbm' ? 'bg-emerald-700 text-white border-emerald-700 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100'"
          >
            🌿 KBM Sains & Alam
          </button>
          <button
            @click="selectedCategory = 'tahfidz'"
            class="px-4 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer border"
            :class="selectedCategory === 'tahfidz' ? 'bg-emerald-700 text-white border-emerald-700 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100'"
          >
            📖 Tahfidz Al-Qur'an
          </button>
          <button
            @click="selectedCategory = 'marketday'"
            class="px-4 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer border"
            :class="selectedCategory === 'marketday' ? 'bg-emerald-700 text-white border-emerald-700 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100'"
          >
            🛍️ Market Day
          </button>
          <button
            @click="selectedCategory = 'outbound'"
            class="px-4 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer border"
            :class="selectedCategory === 'outbound' ? 'bg-emerald-700 text-white border-emerald-700 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100'"
          >
            ⛺ Outbound & Rihlah
          </button>
        </div>
      </div>

      <!-- Gallery Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="item in filteredItems" 
          :key="item.id"
          @click="openLightbox(item)"
          class="group relative rounded-3xl overflow-hidden bg-gradient-to-tr text-white p-7 h-64 sm:h-72 flex flex-col justify-between shadow-md hover:shadow-2xl transition-all duration-300 cursor-pointer transform hover:-translate-y-1"
          :class="item.color"
        >
          <!-- Background Abstract Pattern -->
          <div class="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition-colors"></div>
          <div class="absolute -right-8 -top-8 w-36 h-36 bg-white/10 rounded-full blur-xl group-hover:scale-125 transition-transform"></div>

          <!-- Top Badge & Icon -->
          <div class="relative z-10 flex items-center justify-between">
            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-white/20 backdrop-blur-md border border-white/20 text-white">
              {{ item.categoryLabel }}
            </span>
            <span class="text-3xl filter drop-shadow">{{ item.icon }}</span>
          </div>

          <!-- Bottom Content -->
          <div class="relative z-10 space-y-1.5">
            <div class="text-[10px] text-emerald-200 font-mono">{{ item.date }}</div>
            <h3 class="font-black text-lg sm:text-xl text-white leading-snug group-hover:text-emerald-200 transition-colors">
              {{ item.title }}
            </h3>
            <p class="text-xs text-white/80 line-clamp-2 leading-relaxed">
              {{ item.desc }}
            </p>
            <div class="pt-2 flex items-center gap-1.5 text-xs font-bold text-emerald-300 opacity-90 group-hover:opacity-100">
              <span>Klik untuk melihat dokumentasi</span>
              <span>&rarr;</span>
            </div>
          </div>
        </div>
      </div>

      <!-- LIGHTBOX MODAL DOKUMENTASI -->
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div 
          v-if="activeModalItem"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm"
          @click.self="closeLightbox"
        >
          <div class="bg-white rounded-3xl max-w-xl w-full overflow-hidden shadow-2xl border border-slate-100 animate-scaleUp">
            
            <!-- Modal Header Media Banner -->
            <div 
              class="h-56 bg-gradient-to-tr text-white p-6 flex flex-col justify-between relative overflow-hidden"
              :class="activeModalItem.color"
            >
              <div class="flex items-center justify-between relative z-10">
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-white/20 backdrop-blur-md">
                  {{ activeModalItem.categoryLabel }}
                </span>
                <button
                  type="button"
                  @click="closeLightbox"
                  class="w-8 h-8 rounded-full bg-black/30 hover:bg-black/50 text-white flex items-center justify-center text-lg font-bold cursor-pointer"
                >
                  &times;
                </button>
              </div>

              <div class="relative z-10">
                <span class="text-4xl block mb-2">{{ activeModalItem.icon }}</span>
                <h3 class="text-xl sm:text-2xl font-black text-white leading-tight">
                  {{ activeModalItem.title }}
                </h3>
              </div>
            </div>

            <!-- Modal Body Description -->
            <div class="p-6 space-y-4">
              <div class="text-xs font-mono text-slate-400">
                Waktu Pelaksanaan: {{ activeModalItem.date }} • Lokasi: SDI Bina Insan Mandiri Purwokerto
              </div>
              <p class="text-sm text-slate-700 leading-relaxed">
                {{ activeModalItem.desc }}
              </p>
              <p class="text-xs text-slate-500 leading-relaxed">
                Kegiatan ini dirancang untuk mewujudkan keseimbangan antara perkembangan akal (intelektual), hati (spiritualitas dan ketenangan batin), serta raga (keterampilan motorik dan kemandirian) ananda tercinta.
              </p>

              <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-800">Dokumentasi Resmi SDI BIM</span>
                <button
                  type="button"
                  @click="closeLightbox"
                  class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition cursor-pointer"
                >
                  Tutup
                </button>
              </div>
            </div>

          </div>
        </div>
      </transition>

    </div>
  </section>
</template>
