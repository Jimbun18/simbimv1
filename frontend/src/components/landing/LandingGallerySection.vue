<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { galleryItems as defaultGalleryItems } from './landingData'
import api from '../../services/api'

import galeriImg from '../../assets/galeri.jpeg'
import leadershipImg from '../../assets/leadership.jpeg'
import pendidikanImg from '../../assets/pendidikan.jpeg'
import tahfidImg from '../../assets/tahfid.png'
import artikelImg from '../../assets/artikel.jpeg'
import enterprenerImg from '../../assets/enterprener.png'

const props = withDefaults(defineProps<{
  isHome?: boolean
  limit?: number
}>(), {
  isHome: false,
  limit: 6
})

interface DynamicGalleryItem {
  id: number
  title: string
  category: string
  categoryLabel: string
  date: string
  desc: string
  color: string
  icon: string
  image?: string
}

const fallbackPhotos = [
  pendidikanImg,
  tahfidImg,
  enterprenerImg,
  galeriImg,
  artikelImg,
  leadershipImg,
]

// Default items dengan foto sekolah riil
const initialItems: DynamicGalleryItem[] = defaultGalleryItems.map((item, index) => ({
  ...item,
  image: fallbackPhotos[index % fallbackPhotos.length]
}))

const selectedCategory = ref<string>('all')
const activeModalItem = ref<DynamicGalleryItem | null>(null)
const items = ref<DynamicGalleryItem[]>([...initialItems])

const fetchCmsGalleries = async () => {
  try {
    const res = await api.get('/public/galleries')
    if (res.data?.success && Array.isArray(res.data.data) && res.data.data.length > 0) {
      const cmsList: DynamicGalleryItem[] = res.data.data.map((item: any, idx: number) => {
        const d = new Date(item.created_at)
        const dateFormatted = new Intl.DateTimeFormat('id-ID', {
          day: 'numeric',
          month: 'short',
          year: 'numeric',
        }).format(d)

        return {
          id: item.id,
          title: item.activity_type || 'Kegiatan Siswa',
          category: 'all',
          categoryLabel: item.activity_type || 'Kegiatan Siswa',
          date: dateFormatted,
          desc: 'Dokumentasi kegiatan siswa SDI Bina Insan Mandiri Purwokerto.',
          color: 'from-emerald-800 to-teal-950',
          icon: '📸',
          image: item.image_url || fallbackPhotos[idx % fallbackPhotos.length],
        }
      })
      items.value = cmsList
    }
  } catch (e) {
    // Fallback ke default items
  }
}

onMounted(() => {
  fetchCmsGalleries()
})

const filteredItems = computed(() => {
  if (selectedCategory.value === 'all') return items.value
  return items.value.filter(item => item.category === selectedCategory.value)
})

const displayItems = computed(() => {
  if (props.isHome && props.limit) {
    return filteredItems.value.slice(0, props.limit)
  }
  return filteredItems.value
})

const openLightbox = (item: DynamicGalleryItem) => {
  activeModalItem.value = item
}

const closeLightbox = () => {
  activeModalItem.value = null
}
</script>

<template>
  <section id="galeri" class="py-20 bg-slate-50 border-b border-slate-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
      
      <!-- ============================================== -->
      <!-- HEADER JIKA DI TAMPILKAN DI BERANDA (isHome)   -->
      <!-- ============================================== -->
      <div 
        v-if="isHome"
        v-reveal="{ type: 'fade-up', delay: 50 }"
        class="flex flex-col md:flex-row md:items-end justify-between gap-4"
      >
        <div>
          <span class="px-3.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider">
            Dokumentasi Kegiatan
          </span>
          <h2 class="mt-2 text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
            Galeri Kegiatan Siswa
          </h2>
          <p class="mt-1 text-xs sm:text-sm text-slate-500 max-w-2xl">
            Potret keceriaan, semangat belajar fitrah, dan ragam aktivitas bermakna siswa SDI Bina Insan Mandiri Purwokerto.
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

      <!-- ============================================== -->
      <!-- HEADER JIKA DI HALAMAN PENUH /galeri           -->
      <!-- ============================================== -->
      <div 
        v-else
        v-reveal="{ type: 'fade-up', delay: 50 }"
        class="text-center max-w-3xl mx-auto space-y-3"
      >
        <span class="px-3.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider">
          Dokumentasi Sekolah
        </span>
        <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
          Galeri Kegiatan Siswa
        </h2>
        <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
          Potret keceriaan, semangat belajar fitrah, dan ragam aktivitas bermakna siswa SDI Bina Insan Mandiri Purwokerto di dalam maupun luar kelas.
        </p>

        <!-- Category Filters with Splash Animation -->
        <div class="flex flex-wrap items-center justify-center gap-2 pt-2">
          <button
            @click="selectedCategory = 'all'"
            v-splash
            class="btn-splash px-4 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer border"
            :class="selectedCategory === 'all' ? 'bg-emerald-700 text-white border-emerald-700 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100'"
          >
            Semua Foto
          </button>
          <button
            @click="selectedCategory = 'kbm'"
            v-splash
            class="btn-splash px-4 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer border"
            :class="selectedCategory === 'kbm' ? 'bg-emerald-700 text-white border-emerald-700 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100'"
          >
            🌿 KBM Sains & Alam
          </button>
          <button
            @click="selectedCategory = 'tahfidz'"
            v-splash
            class="btn-splash px-4 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer border"
            :class="selectedCategory === 'tahfidz' ? 'bg-emerald-700 text-white border-emerald-700 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100'"
          >
            📖 Tahfidz Al-Qur'an
          </button>
          <button
            @click="selectedCategory = 'marketday'"
            v-splash
            class="btn-splash px-4 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer border"
            :class="selectedCategory === 'marketday' ? 'bg-emerald-700 text-white border-emerald-700 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100'"
          >
            🛍️ Market Day
          </button>
          <button
            @click="selectedCategory = 'outbound'"
            v-splash
            class="btn-splash px-4 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer border"
            :class="selectedCategory === 'outbound' ? 'bg-emerald-700 text-white border-emerald-700 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100'"
          >
            ⛺ Outbound & Rihlah
          </button>
        </div>
      </div>

      <!-- Gallery Grid with Staggered Scroll Reveal (2 Kolom Mobile, 3 Kolom Desktop) -->
      <div class="grid grid-cols-2 lg:grid-cols-3 gap-3.5 sm:gap-6">
        <div 
          v-for="(item, idx) in displayItems" 
          :key="item.id"
          v-reveal="{ type: 'fade-up', delay: 60 + (idx % 3) * 90 }"
          @click="openLightbox(item)"
          class="btn-splash group relative rounded-2xl sm:rounded-3xl overflow-hidden bg-gradient-to-tr text-white p-3.5 sm:p-7 h-48 sm:h-72 flex flex-col justify-between shadow-md hover:shadow-2xl transition-all duration-300 cursor-pointer transform hover:-translate-y-1.5"
          :class="item.color || 'from-emerald-800 to-teal-950'"
        >
          <!-- Background Real Photo (from CMS or Assets) -->
          <img 
            v-if="item.image" 
            :src="item.image" 
            :alt="item.title"
            class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" 
          />
          <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-900/40 to-black/30 group-hover:from-slate-950/80 transition-colors"></div>
          <div v-if="!item.image" class="absolute -right-8 -top-8 w-36 h-36 bg-white/10 rounded-full blur-xl group-hover:scale-125 transition-transform"></div>

          <!-- Top Badge & Icon -->
          <div class="relative z-10 flex items-center justify-between">
            <span class="px-2 sm:px-3 py-0.5 sm:py-1 rounded-full text-[8px] sm:text-[10px] font-bold uppercase tracking-wider bg-white/20 backdrop-blur-md border border-white/20 text-white truncate max-w-[90px] sm:max-w-none">
              {{ item.categoryLabel }}
            </span>
            <span class="text-xl sm:text-3xl filter drop-shadow">{{ item.icon || '📸' }}</span>
          </div>

          <!-- Bottom Content (Bersih: Hanya Tanggal & Jenis Kegiatan) -->
          <div class="relative z-10 space-y-0.5 sm:space-y-1">
            <div class="text-[9px] sm:text-[10px] text-emerald-300 font-mono flex items-center gap-1">
              <span>🕒</span> {{ item.date }}
            </div>
            <h3 class="font-black text-xs sm:text-lg text-white leading-snug group-hover:text-emerald-200 transition-colors line-clamp-2 drop-shadow-sm">
              {{ item.title }}
            </h3>
            <div class="pt-1 flex items-center gap-1.5 text-[10px] sm:text-xs font-bold text-emerald-300 opacity-90 group-hover:opacity-100">
              <span>Buka Foto</span>
              <span>&rarr;</span>
            </div>
          </div>
        </div>
      </div>

      <!-- LIGHTBOX MODAL FOTO BERSIH (Murni Foto Tanpa Keterangan) -->
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
          class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/90 backdrop-blur-md"
          @click.self="closeLightbox"
        >
          <!-- Tombol Tutup Mengambang di Pojok Kanan Atas Layar -->
          <button
            type="button"
            @click="closeLightbox"
            class="fixed top-4 right-4 z-50 w-11 h-11 rounded-full bg-white/20 hover:bg-white/40 text-white flex items-center justify-center text-2xl font-bold backdrop-blur-md transition-all shadow-xl cursor-pointer"
            aria-label="Tutup Foto"
          >
            &times;
          </button>

          <!-- Frame Foto Bersih -->
          <div class="relative max-w-5xl w-full flex flex-col items-center animate-scaleUp">
            
            <!-- Foto Utama Bersih -->
            <div class="relative rounded-2xl overflow-hidden shadow-2xl bg-black/50 border border-white/10 max-h-[82vh] flex items-center justify-center">
              <img 
                v-if="activeModalItem.image" 
                :src="activeModalItem.image" 
                :alt="activeModalItem.title" 
                class="w-auto h-auto max-h-[82vh] max-w-full object-contain" 
              />
              <div 
                v-else 
                class="w-80 h-80 sm:w-96 sm:h-96 flex flex-col items-center justify-center p-6 text-center text-white"
                :class="activeModalItem.color"
              >
                <span class="text-6xl mb-4">{{ activeModalItem.icon }}</span>
                <h3 class="text-xl font-bold">{{ activeModalItem.title }}</h3>
              </div>
            </div>

            <!-- Keterangan Minimalis Mengambang di Bawah (Hanya Judul & Badge) -->
            <div class="mt-3.5 flex flex-wrap items-center justify-center gap-2.5 text-center px-4">
              <span class="px-3 py-1 rounded-full text-xs font-bold bg-white/20 backdrop-blur-md text-white border border-white/20">
                {{ activeModalItem.categoryLabel }}
              </span>
              <span class="text-xs sm:text-sm text-white/90 font-medium drop-shadow-md">
                {{ activeModalItem.title }}
              </span>
            </div>

          </div>
        </div>
      </transition>

    </div>
  </section>
</template>

<style scoped>
@keyframes scaleUp {
  from {
    transform: scale(0.95);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}
.animate-scaleUp {
  animation: scaleUp 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
