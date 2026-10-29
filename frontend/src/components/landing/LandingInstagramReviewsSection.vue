<script setup lang="ts">
import { ref, computed } from 'vue'
import depanImg from '../../assets/depan.jpeg'
import galeriImg from '../../assets/galeri.jpeg'
import artikelImg from '../../assets/artikel.jpeg'
import leadershipImg from '../../assets/leadership.jpeg'
import prestasiImg from '../../assets/prestasi.jpeg'
import tahfidImg from '../../assets/tahfid.png'
import pendidikanImg from '../../assets/pendidikan.jpeg'

const GOOGLE_MAPS_URL = 'https://maps.app.goo.gl/2DjGnYuHWWaXNLX69'
const INSTAGRAM_URL = 'https://instagram.com/sdislambim_pwt'

interface InstagramPost {
  id: number
  type: 'video' | 'image'
  tag?: string
  subtitle?: string
  author?: string
  title: string
  overlayText?: string
  date?: string
  image: string
  aspectBgClass: string
  likes: string
  comments: string
}

const instagramPosts = ref<InstagramPost[]>([
  {
    id: 1,
    type: 'video',
    tag: 'REELS',
    subtitle: 'KELAS PERCOBAAN',
    author: 'Ustadz Syarif',
    title: 'Do\'a Iftitah',
    overlayText: 'NOA',
    image: tahfidImg,
    aspectBgClass: 'from-emerald-900 via-teal-800 to-emerald-950',
    likes: '142',
    comments: '18'
  },
  {
    id: 2,
    type: 'image',
    tag: 'POST',
    subtitle: 'PERINGATAN RESMI',
    title: 'SELAMAT HARI SANTRI',
    date: '22 OKTOBER 2026',
    overlayText: 'Santri Siaga Jiwa Raga, Merawat Jagat Membina Peradaban',
    image: galeriImg,
    aspectBgClass: 'from-[#0b4d3c] via-[#043d2f] to-[#01261d]',
    likes: '286',
    comments: '34'
  },
  {
    id: 3,
    type: 'image',
    tag: 'SPMB',
    subtitle: 'SPMB GELOMBANG 1',
    author: 'Panitia SPMB',
    title: 'BIM HEROES',
    overlayText: 'SPMB OKTOBER 2026/2027',
    image: leadershipImg,
    aspectBgClass: 'from-[#0d4f40] via-[#083b2f] to-[#022119]',
    likes: '198',
    comments: '22'
  },
  {
    id: 4,
    type: 'video',
    tag: 'REELS',
    subtitle: 'KEGIATAN OUTBOUND',
    author: 'Camping Kids',
    title: 'Rihlah & Camping Fitrah',
    overlayText: 'OUTDOOR',
    image: artikelImg,
    aspectBgClass: 'from-amber-900 via-emerald-900 to-slate-950',
    likes: '312',
    comments: '45'
  },
  {
    id: 5,
    type: 'image',
    tag: 'PRESTASI',
    subtitle: 'JUARA TAHFIDZ & SAINS',
    author: 'Tim Prestasi Siswa',
    title: 'Generasi Hafidz Mutqin',
    overlayText: 'PRESTASI',
    image: prestasiImg,
    aspectBgClass: 'from-teal-900 via-cyan-950 to-emerald-950',
    likes: '245',
    comments: '29'
  },
  {
    id: 6,
    type: 'image',
    tag: 'KBM',
    subtitle: 'DEEP LEARNING',
    author: 'Kelas Inspirasi',
    title: 'Belajar Sains Seru & Nyata',
    overlayText: 'DISCOVERY',
    image: pendidikanImg,
    aspectBgClass: 'from-emerald-950 via-teal-900 to-slate-900',
    likes: '175',
    comments: '16'
  }
])

// Instagram Carousel State
const currentInstaIndex = ref(0)
const maxInstaIndex = computed(() => {
  // on mobile: length - 1, desktop: length - 3
  return Math.max(0, instagramPosts.value.length - 3)
})

const nextInsta = () => {
  if (currentInstaIndex.value < maxInstaIndex.value) {
    currentInstaIndex.value++
  } else {
    currentInstaIndex.value = 0
  }
}

const prevInsta = () => {
  if (currentInstaIndex.value > 0) {
    currentInstaIndex.value--
  } else {
    currentInstaIndex.value = maxInstaIndex.value
  }
}

// Google Reviews Data
interface GoogleReview {
  id: number
  author: string
  timeAgo: string
  rating: number
  verified: boolean
  avatarBg: string
  avatarText: string
  avatarType?: 'text' | 'user'
  badgeColor?: string
  comment: string
  fullComment?: string
}

const googleReviews = ref<GoogleReview[]>([
  {
    id: 1,
    author: 'Pak Agus Sumarno',
    timeAgo: '3 years ago',
    rating: 5,
    verified: true,
    avatarBg: 'bg-amber-100 text-amber-800 border-amber-300',
    avatarText: 'AS',
    avatarType: 'user',
    badgeColor: 'bg-amber-500',
    comment: 'Mantabs',
    fullComment: 'Mantabs, sekolah Islam dengan metode belajar deep learning dan pembinaan adab yang sangat baik. Ustadz dan Ustadzahnya telaten serta perhatian membimbing setiap anak.'
  },
  {
    id: 2,
    author: 'Khalifi Tsabit',
    timeAgo: '3 years ago',
    rating: 5,
    verified: true,
    avatarBg: 'bg-slate-200 text-slate-700 border-slate-300',
    avatarText: 'KT',
    avatarType: 'user',
    badgeColor: 'bg-amber-500',
    comment: 'Barakallahufikum Ustadz dan Ustadzah',
    fullComment: 'Barakallahufikum Ustadz dan Ustadzah. Sangat recommended untuk sekolah dasar di Purwokerto. Anak kami berkembang pesat dalam hafalan Qur\'an dan akhlak terpuji.'
  },
  {
    id: 3,
    author: 'Wahyu Dewi Citra',
    timeAgo: '3 years ago',
    rating: 5,
    verified: true,
    avatarBg: 'bg-[#e91e63] text-white border-rose-300',
    avatarText: 'W',
    badgeColor: 'bg-amber-500',
    comment: 'Kesini pertama kali karena nganter anak ikut salah satu kegiatan, camping kids. Suasana sekolah sangat asri, tertib, dan nilai-nilai agamanya ditanamkan dengan hangat...',
    fullComment: 'Kesini pertama kali karena nganter anak ikut salah satu kegiatan, camping kids. Suasana sekolah sangat asri, tertib, dan nilai-nilai agamanya ditanamkan dengan hangat. Gurunya ramah-ramah dan programnya berbobot.'
  },
  {
    id: 4,
    author: 'Hendri Kurniawan',
    timeAgo: '2 years ago',
    rating: 5,
    verified: true,
    avatarBg: 'bg-blue-600 text-white border-blue-400',
    avatarText: 'H',
    badgeColor: 'bg-amber-500',
    comment: 'Sekolah yang mengutamakan adab sebelum ilmu. Fasilitas pembelajaran lengkap dan sistem komunikasi ke orang tua sangat transparan lewat aplikasi SIM-BIM.',
    fullComment: 'Sekolah yang mengutamakan adab sebelum ilmu. Fasilitas pembelajaran lengkap dan sistem komunikasi ke orang tua sangat transparan lewat aplikasi SIM-BIM.'
  },
  {
    id: 5,
    author: 'dr. Rizki Pratama',
    timeAgo: '1 year ago',
    rating: 5,
    verified: true,
    avatarBg: 'bg-emerald-600 text-white border-emerald-400',
    avatarText: 'RP',
    badgeColor: 'bg-amber-500',
    comment: 'Alhamdulillah pilihan terbaik untuk putra kami. Pembelajaran deep learning membuat anak kritis, ceria, dan selalu antusias datang ke sekolah.',
    fullComment: 'Alhamdulillah pilihan terbaik untuk putra kami. Pembelajaran deep learning membuat anak kritis, ceria, dan selalu antusias datang ke sekolah.'
  }
])

// Google Reviews Carousel State
const currentReviewIndex = ref(0)
const maxReviewIndex = computed(() => {
  return Math.max(0, googleReviews.value.length - 3)
})

const nextReview = () => {
  if (currentReviewIndex.value < maxReviewIndex.value) {
    currentReviewIndex.value++
  } else {
    currentReviewIndex.value = 0
  }
}

const prevReview = () => {
  if (currentReviewIndex.value > 0) {
    currentReviewIndex.value--
  } else {
    currentReviewIndex.value = maxReviewIndex.value
  }
}

// State untuk expand "Read more" pada review
const expandedReviewId = ref<number | null>(null)
const toggleExpand = (id: number) => {
  expandedReviewId.value = expandedReviewId.value === id ? null : id
}
</script>

<template>
  <section class="py-20 bg-white border-b border-slate-200/80 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">

      <!-- ========================================== -->
      <!-- BAGIAN 1: INSTAGRAM FEED SECTION           -->
      <!-- ========================================== -->
      <div class="space-y-8" v-reveal="{ type: 'fade-up', delay: 50 }">
        
        <!-- Header Instagram dengan Subtitle Garis & Judul Sekolah -->
        <div class="text-center space-y-2">
          <!-- Subtitle: — Instagram — -->
          <div class="inline-flex items-center justify-center gap-3">
            <span class="w-10 sm:w-16 h-0.5 bg-amber-500/60 rounded-full"></span>
            <span class="text-amber-600 text-xs sm:text-sm font-extrabold uppercase tracking-widest">
              Instagram
            </span>
            <span class="w-10 sm:w-16 h-0.5 bg-amber-500/60 rounded-full"></span>
          </div>

          <!-- Main Title: SD Islam Bina Insan Mandiri Purwokerto -->
          <h2 class="text-2xl sm:text-3xl md:text-4xl text-slate-800 tracking-tight font-medium">
            SD Islam <span class="font-black text-slate-950">Bina Insan Mandiri Purwokerto</span>
          </h2>
          <p class="text-xs sm:text-sm text-slate-500 max-w-xl mx-auto">
            Ikuti warta terkini, keceriaan kegiatan ananda, dan rekaman mutaba'ah fitrah di akun resmi kami.
          </p>
        </div>

        <!-- Carousel Post Instagram Container -->
        <div class="relative group/carousel">
          
          <!-- Tombol Navigasi Kiri (Prev) -->
          <button 
            @click="prevInsta"
            class="absolute -left-3 sm:-left-5 top-1/2 -translate-y-1/2 z-20 w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-white/90 hover:bg-white text-slate-800 shadow-xl border border-slate-200/80 flex items-center justify-center transition-all duration-200 hover:scale-110 active:scale-95 cursor-pointer opacity-90 group-hover/carousel:opacity-100"
            aria-label="Previous Instagram Post"
          >
            <svg class="w-5 h-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
            </svg>
          </button>

          <!-- Tombol Navigasi Kanan (Next) -->
          <button 
            @click="nextInsta"
            class="absolute -right-3 sm:-right-5 top-1/2 -translate-y-1/2 z-20 w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-white/90 hover:bg-white text-slate-800 shadow-xl border border-slate-200/80 flex items-center justify-center transition-all duration-200 hover:scale-110 active:scale-95 cursor-pointer opacity-90 group-hover/carousel:opacity-100"
            aria-label="Next Instagram Post"
          >
            <svg class="w-5 h-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
            </svg>
          </button>

          <!-- Slider Wrapper -->
          <div class="overflow-hidden rounded-3xl p-1">
            <div 
              class="flex transition-transform duration-500 ease-out gap-4 sm:gap-6"
              :style="{ transform: `translateX(-${currentInstaIndex * (100 / 3 + 2)}%)` }"
            >
              <div 
                v-for="post in instagramPosts" 
                :key="post.id"
                class="w-full sm:w-[calc(50%-12px)] lg:w-[calc(33.333%-16px)] shrink-0"
              >
                <a 
                  :href="INSTAGRAM_URL" 
                  target="_blank" 
                  rel="noopener noreferrer"
                  class="block relative aspect-square rounded-2xl sm:rounded-3xl overflow-hidden shadow-md hover:shadow-2xl transition-all duration-300 group hover:-translate-y-1.5 cursor-pointer bg-slate-900 border border-slate-200/60"
                >
                  <!-- Background Image -->
                  <img 
                    :src="post.image" 
                    :alt="post.title"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out" 
                  />

                  <!-- Gradient & Contrast Overlay Sesuai Gaya Referensi -->
                  <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/20 to-slate-950/60 group-hover:from-slate-950/90 transition-all"></div>

                  <!-- TOP CONTENT: Badge Play & Subtitle Header -->
                  <div class="absolute top-4 inset-x-4 flex items-center justify-between text-white z-10">
                    <div class="flex flex-col">
                      <span v-if="post.subtitle" class="text-[10px] sm:text-xs font-bold tracking-widest uppercase opacity-90 drop-shadow">
                        {{ post.subtitle }}
                      </span>
                      <span v-if="post.author" class="text-xs sm:text-sm font-semibold opacity-95 drop-shadow">
                        {{ post.author }}
                      </span>
                    </div>

                    <!-- Video Play Icon / Tag -->
                    <div v-if="post.type === 'video'" class="w-8 h-8 rounded-full bg-black/40 backdrop-blur-md border border-white/20 flex items-center justify-center text-white text-xs shadow-md">
                      <svg class="w-3.5 h-3.5 translate-x-0.5 fill-current" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                      </svg>
                    </div>
                  </div>

                  <!-- CENTER ARTISTIC OVERLAY TEXT (Mirip referensi "NOA", "JEAN GREY") -->
                  <div v-if="post.overlayText" class="absolute inset-0 flex items-center justify-center pointer-events-none select-none z-5">
                    <span class="text-4xl sm:text-5xl lg:text-6xl font-black text-white/20 tracking-tighter uppercase font-mono">
                      {{ post.overlayText }}
                    </span>
                  </div>

                  <!-- BOTTOM CONTENT: Main Title & Action Bar -->
                  <div class="absolute bottom-4 inset-x-4 text-white z-10 space-y-1">
                    <div v-if="post.date" class="text-[10px] text-amber-300 font-bold uppercase tracking-wider">
                      {{ post.date }}
                    </div>
                    <h3 class="font-black text-lg sm:text-xl md:text-2xl leading-tight text-white drop-shadow-md">
                      {{ post.title }}
                    </h3>

                    <!-- Hover Interactions Info (Likes & Comments) -->
                    <div class="flex items-center gap-4 pt-1 text-[11px] text-emerald-200 opacity-90 group-hover:opacity-100 transition-opacity">
                      <span class="flex items-center gap-1 font-semibold">
                        <span>❤️</span>
                        <span>{{ post.likes }}</span>
                      </span>
                      <span class="flex items-center gap-1 font-semibold">
                        <span>💬</span>
                        <span>{{ post.comments }}</span>
                      </span>
                      <span class="ml-auto text-xs text-white/80 group-hover:text-amber-300 transition-colors font-bold">
                        Buka Post ↗
                      </span>
                    </div>
                  </div>
                </a>
              </div>
            </div>
          </div>

        </div>

        <!-- Tombol "View on Instagram" Biru Khas Instagram -->
        <div class="text-center pt-2">
          <a 
            :href="INSTAGRAM_URL"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-2.5 px-6 py-3 rounded-xl bg-[#0095f6] hover:bg-[#1877f2] text-white font-bold text-xs sm:text-sm shadow-md hover:shadow-xl transition-all duration-200 cursor-pointer hover:-translate-y-0.5 active:translate-y-0"
          >
            <!-- Instagram Camera Icon SVG -->
            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
              <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
            </svg>
            <span>View on Instagram</span>
          </a>
        </div>

      </div>

      <!-- ========================================== -->
      <!-- BAGIAN 2: GOOGLE REVIEWS / KOMENTAR MAPS   -->
      <!-- ========================================== -->
      <div 
        v-reveal="{ type: 'fade-up', delay: 100 }"
        class="pt-6 border-t border-slate-100"
      >
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
          
          <!-- KARTU KIRI: Profil Google Bisnis & Write a Review -->
          <div class="lg:col-span-4 xl:col-span-3">
            <div class="h-full bg-white rounded-2xl sm:rounded-3xl p-5 border border-slate-200/80 shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
              
              <!-- Identitas Sekolah & Rating -->
              <div class="space-y-4">
                <div class="flex items-center gap-3.5">
                  <!-- Thumbnail Gedung Sekolah -->
                  <div class="w-16 h-16 sm:w-18 sm:h-18 rounded-xl overflow-hidden shrink-0 border border-slate-200 bg-slate-100 shadow-xs">
                    <img 
                      :src="depanImg" 
                      alt="Gedung SD Islam Bina Insan Mandiri Purwokerto" 
                      class="w-full h-full object-cover"
                    />
                  </div>

                  <!-- Nama Sekolah & Reviews Count -->
                  <div class="space-y-1 min-w-0">
                    <h3 class="font-extrabold text-sm sm:text-base text-slate-900 leading-snug">
                      SD Islam Bina Insan Mandiri Purwokerto
                    </h3>
                    
                    <!-- Bintang Rating 5.0 -->
                    <div class="flex items-center gap-0.5 text-amber-400 text-sm">
                      <span v-for="i in 5" :key="i">★</span>
                    </div>

                    <div class="text-xs text-slate-500 font-medium">
                      39 Google reviews
                    </div>
                  </div>
                </div>
              </div>

              <!-- Tombol Write a Review -->
              <div class="pt-5">
                <a 
                  :href="GOOGLE_MAPS_URL"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="w-full block py-2 px-4 rounded-xl border border-slate-800 hover:bg-slate-900 text-slate-800 hover:text-white font-bold text-xs text-center transition-all duration-200 shadow-xs hover:shadow"
                >
                  Write a review
                </a>
              </div>

            </div>
          </div>

          <!-- KARTU KANAN: Carousel Ulasan Google Maps -->
          <div class="lg:col-span-8 xl:col-span-9 relative group/review">
            
            <!-- Tombol Navigasi Kanan (Next) -->
            <button 
              @click="nextReview"
              class="absolute -right-2 sm:-right-4 top-1/2 -translate-y-1/2 z-20 w-9 h-9 sm:w-11 sm:h-11 rounded-full bg-white text-slate-700 shadow-xl border border-slate-200 flex items-center justify-center transition-all duration-200 hover:scale-110 active:scale-95 cursor-pointer opacity-90 hover:opacity-100"
              aria-label="Next Google Reviews"
            >
              <svg class="w-4 h-4 sm:w-5 sm:h-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
              </svg>
            </button>

            <!-- Slider Wrapper Review Cards -->
            <div class="overflow-hidden p-1">
              <div 
                class="flex transition-transform duration-500 ease-out gap-4 sm:gap-5"
                :style="{ transform: `translateX(-${currentReviewIndex * (100 / 3 + 2)}%)` }"
              >
                <div 
                  v-for="rev in googleReviews" 
                  :key="rev.id"
                  class="w-full sm:w-[calc(50%-10px)] lg:w-[calc(33.333%-14px)] shrink-0"
                >
                  <div class="h-full bg-[#f8fafc] hover:bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-200/70 hover:border-slate-300 shadow-xs hover:shadow-md transition-all duration-200 flex flex-col justify-between space-y-3">
                    
                    <!-- Header Ulasan: Avatar + Nama + Google "G" Icon -->
                    <div class="flex items-start justify-between gap-3">
                      
                      <!-- User Profile -->
                      <div class="flex items-center gap-2.5 min-w-0">
                        <div class="relative">
                          <!-- Avatar Container -->
                          <div 
                            class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs border shadow-xs"
                            :class="rev.avatarBg"
                          >
                            <template v-if="rev.avatarType === 'user'">
                              <!-- User Silhouette Icon -->
                              <svg class="w-5 h-5 opacity-70 fill-current" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                              </svg>
                            </template>
                            <template v-else>
                              {{ rev.avatarText }}
                            </template>
                          </div>

                          <!-- Small Local Guide Star Badge di sudut avatar -->
                          <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-amber-500 text-white flex items-center justify-center text-[9px] shadow-xs">
                            ★
                          </div>
                        </div>

                        <div class="min-w-0">
                          <h4 class="font-bold text-xs sm:text-sm text-slate-900 truncate">
                            {{ rev.author }}
                          </h4>
                          <span class="text-[10px] text-slate-400 font-mono">
                            {{ rev.timeAgo }}
                          </span>
                        </div>
                      </div>

                      <!-- Google Official Colored "G" Logo -->
                      <div class="shrink-0 pt-0.5">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" viewBox="0 0 24 24">
                          <!-- Blue -->
                          <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                          <!-- Green -->
                          <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                          <!-- Yellow -->
                          <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" />
                          <!-- Red -->
                          <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" />
                        </svg>
                      </div>

                    </div>

                    <!-- Stars & Blue Verified Checkmark -->
                    <div class="flex items-center gap-1.5">
                      <div class="flex items-center gap-0.5 text-amber-400 text-xs sm:text-sm">
                        <span v-for="s in rev.rating" :key="s">★</span>
                      </div>

                      <!-- Blue Verified Badge -->
                      <span class="inline-flex items-center justify-center w-3.5 h-3.5 rounded-full bg-blue-500 text-white text-[8px]" title="Terverifikasi Google">
                        ✓
                      </span>
                    </div>

                    <!-- Review Text Content -->
                    <div class="text-xs text-slate-700 leading-relaxed min-h-[48px]">
                      <template v-if="expandedReviewId === rev.id">
                        <p>{{ rev.fullComment || rev.comment }}</p>
                        <button 
                          @click="toggleExpand(rev.id)" 
                          class="text-slate-400 hover:text-slate-600 font-semibold text-[11px] mt-1 underline cursor-pointer"
                        >
                          Show less
                        </button>
                      </template>
                      <template v-else>
                        <p class="line-clamp-3">{{ rev.comment }}</p>
                        <button 
                          v-if="rev.fullComment && rev.fullComment !== rev.comment"
                          @click="toggleExpand(rev.id)" 
                          class="text-slate-400 hover:text-slate-600 font-semibold text-[11px] mt-1 underline cursor-pointer"
                        >
                          Read more
                        </button>
                      </template>
                    </div>

                  </div>
                </div>
              </div>
            </div>

          </div>

        </div>
      </div>

    </div>
  </section>
</template>

<style scoped>
/* Transisi halus dan estetika premium */
</style>
