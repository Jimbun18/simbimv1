<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '../services/api'
import { showSuccess, showWarning, showError, showConfirm } from '../services/swal'

// Active Tab: 'gallery' | 'achievements' | 'articles'
const activeTab = ref<'gallery' | 'achievements' | 'articles'>('gallery')

// Loading states
const loading = ref(false)
const submitting = ref(false)

// 1. DATA GALERI
interface GalleryItem {
  id: number
  image_url: string
  activity_type: string
  created_at: string
}
const galleries = ref<GalleryItem[]>([])
const galleryFile = ref<File | null>(null)
const galleryPreview = ref<string>('')
const galleryType = ref<string>('KBM Sains & Eksplorasi')
const galleryFileInput = ref<HTMLInputElement | null>(null)

// 2. DATA PRESTASI
interface AchievementItem {
  id: number
  title: string
  image_url: string
  created_at: string
}
const achievements = ref<AchievementItem[]>([])
const achievementFile = ref<File | null>(null)
const achievementPreview = ref<string>('')
const achievementTitle = ref<string>('')
const achievementFileInput = ref<HTMLInputElement | null>(null)

// 3. DATA ARTIKEL
interface ArticleItem {
  id: number
  title: string
  description: string
  image_url: string
  created_at: string
}
const articles = ref<ArticleItem[]>([])
const articleFile = ref<File | null>(null)
const articlePreview = ref<string>('')
const articleTitle = ref<string>('')
const articleDesc = ref<string>('')
const articleFileInput = ref<HTMLInputElement | null>(null)

// Format Waktu Posting
const formatPostTime = (dateStr: string) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(d)
}

// Fetch Functions
const fetchGalleries = async () => {
  try {
    const res = await api.get('/cms/galleries')
    if (res.data?.success) {
      galleries.value = res.data.data.data || res.data.data || []
    }
  } catch (err) {
    console.error('Gagal mengambil data galeri:', err)
  }
}

const fetchAchievements = async () => {
  try {
    const res = await api.get('/cms/achievements')
    if (res.data?.success) {
      achievements.value = res.data.data.data || res.data.data || []
    }
  } catch (err) {
    console.error('Gagal mengambil data prestasi:', err)
  }
}

const fetchArticles = async () => {
  try {
    const res = await api.get('/cms/articles')
    if (res.data?.success) {
      articles.value = res.data.data.data || res.data.data || []
    }
  } catch (err) {
    console.error('Gagal mengambil data artikel:', err)
  }
}

const loadData = async () => {
  loading.value = true
  if (activeTab.value === 'gallery') await fetchGalleries()
  else if (activeTab.value === 'achievements') await fetchAchievements()
  else if (activeTab.value === 'articles') await fetchArticles()
  loading.value = false
}

// File Change Handlers
const onGalleryFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    galleryFile.value = target.files[0]
    galleryPreview.value = URL.createObjectURL(target.files[0])
  }
}

const onAchievementFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    achievementFile.value = target.files[0]
    achievementPreview.value = URL.createObjectURL(target.files[0])
  }
}

const onArticleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    articleFile.value = target.files[0]
    articlePreview.value = URL.createObjectURL(target.files[0])
  }
}

// Submit Functions
const submitGallery = async () => {
  if (!galleryFile.value) {
    showWarning('Perhatian', 'Silakan pilih foto kegiatan terlebih dahulu.')
    return
  }
  if (!galleryType.value) {
    showWarning('Perhatian', 'Silakan pilih atau isi jenis kegiatan.')
    return
  }

  submitting.value = true
  try {
    const formData = new FormData()
    formData.append('foto', galleryFile.value)
    formData.append('activity_type', galleryType.value)

    const res = await api.post('/cms/galleries', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    if (res.data?.success) {
      showSuccess('Berhasil', 'Foto kegiatan berhasil ditambahkan ke Galeri.')
      galleryFile.value = null
      galleryPreview.value = ''
      if (galleryFileInput.value) galleryFileInput.value.value = ''
      await fetchGalleries()
    }
  } catch (err: any) {
    showError('Gagal', err.response?.data?.message || 'Gagal mengunggah foto kegiatan.')
  } finally {
    submitting.value = false
  }
}

const deleteGallery = async (id: number) => {
  const confirmed = await showConfirm(
    'Hapus Foto Galeri?',
    'Foto kegiatan ini akan dihapus permanen dari web publik.'
  )
  if (!confirmed) return

  try {
    const res = await api.delete(`/cms/galleries/${id}`)
    if (res.data?.success) {
      showSuccess('Berhasil', 'Foto galeri berhasil dihapus.')
      await fetchGalleries()
    }
  } catch (err: any) {
    showError('Gagal', err.response?.data?.message || 'Gagal menghapus foto.')
  }
}

const submitAchievement = async () => {
  if (!achievementFile.value) {
    showWarning('Perhatian', 'Silakan pilih foto prestasi terlebih dahulu.')
    return
  }
  if (!achievementTitle.value.trim()) {
    showWarning('Perhatian', 'Silakan isi judul prestasi siswa.')
    return
  }

  submitting.value = true
  try {
    const formData = new FormData()
    formData.append('foto', achievementFile.value)
    formData.append('title', achievementTitle.value.trim())

    const res = await api.post('/cms/achievements', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    if (res.data?.success) {
      showSuccess('Berhasil', 'Prestasi siswa berhasil ditambahkan.')
      achievementFile.value = null
      achievementPreview.value = ''
      achievementTitle.value = ''
      if (achievementFileInput.value) achievementFileInput.value.value = ''
      await fetchAchievements()
    }
  } catch (err: any) {
    showError('Gagal', err.response?.data?.message || 'Gagal menyimpan prestasi.')
  } finally {
    submitting.value = false
  }
}

const deleteAchievement = async (id: number) => {
  const confirmed = await showConfirm(
    'Hapus Prestasi?',
    'Data prestasi siswa ini akan dihapus permanen dari web publik.'
  )
  if (!confirmed) return

  try {
    const res = await api.delete(`/cms/achievements/${id}`)
    if (res.data?.success) {
      showSuccess('Berhasil', 'Prestasi siswa berhasil dihapus.')
      await fetchAchievements()
    }
  } catch (err: any) {
    showError('Gagal', err.response?.data?.message || 'Gagal menghapus prestasi.')
  }
}

const submitArticle = async () => {
  if (!articleFile.value) {
    showWarning('Perhatian', 'Silakan pilih foto warta/artikel.')
    return
  }
  if (!articleTitle.value.trim()) {
    showWarning('Perhatian', 'Silakan isi judul warta/artikel.')
    return
  }
  if (!articleDesc.value.trim()) {
    showWarning('Perhatian', 'Silakan isi keterangan warta/artikel.')
    return
  }

  submitting.value = true
  try {
    const formData = new FormData()
    formData.append('foto', articleFile.value)
    formData.append('title', articleTitle.value.trim())
    formData.append('description', articleDesc.value.trim())

    const res = await api.post('/cms/articles', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    if (res.data?.success) {
      showSuccess('Berhasil', 'Warta & artikel berhasil dipublikasikan.')
      articleFile.value = null
      articlePreview.value = ''
      articleTitle.value = ''
      articleDesc.value = ''
      if (articleFileInput.value) articleFileInput.value.value = ''
      await fetchArticles()
    }
  } catch (err: any) {
    showError('Gagal', err.response?.data?.message || 'Gagal mempublikasikan artikel.')
  } finally {
    submitting.value = false
  }
}

const deleteArticle = async (id: number) => {
  const confirmed = await showConfirm(
    'Hapus Warta/Artikel?',
    'Warta atau artikel ini akan dihapus permanen dari web publik.'
  )
  if (!confirmed) return

  try {
    const res = await api.delete(`/cms/articles/${id}`)
    if (res.data?.success) {
      showSuccess('Berhasil', 'Artikel berhasil dihapus.')
      await fetchArticles()
    }
  } catch (err: any) {
    showError('Gagal', err.response?.data?.message || 'Gagal menghapus artikel.')
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-800 via-teal-800 to-slate-900 p-6 sm:p-8 text-white shadow-xl">
      <div class="relative z-10 max-w-2xl space-y-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-emerald-300 text-xs font-bold tracking-wider uppercase border border-white/10">
          <span>🌐</span> Content Management System
        </span>
        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
          Manajemen Konten Web Sekolah
        </h1>
        <p class="text-xs sm:text-sm text-emerald-100/90 leading-relaxed">
          Kelola galeri kegiatan siswa, prestasi siswa, serta warta & artikel sekolah yang tampil secara live di website publik SDI Bina Insan Mandiri.
        </p>
      </div>
      <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-2">
      <button
        @click="activeTab = 'gallery'; loadData()"
        type="button"
        class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all"
        :class="activeTab === 'gallery' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
      >
        <span>📸</span> Galeri Kegiatan Siswa
      </button>

      <button
        @click="activeTab = 'achievements'; loadData()"
        type="button"
        class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all"
        :class="activeTab === 'achievements' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
      >
        <span>🏆</span> Prestasi Siswa
      </button>

      <button
        @click="activeTab = 'articles'; loadData()"
        type="button"
        class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all"
        :class="activeTab === 'articles' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
      >
        <span>📰</span> Warta & Artikel Sekolah
      </button>
    </div>

    <!-- TAB 1: GALERI KEGIATAN SISWA -->
    <div v-show="activeTab === 'gallery'" class="space-y-6">
      <!-- Form Tambah Foto Galeri -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/80">
        <h2 class="text-base font-bold text-slate-800 mb-1 flex items-center gap-2">
          <span>➕</span> Unggah Foto Kegiatan Siswa
        </h2>
        <p class="text-xs text-slate-500 mb-5">
          Unggah foto dokumentasi kegiatan siswa beserta jenis kegiatannya agar tampil di Galeri publik.
        </p>

        <form @submit.prevent="submitGallery" class="grid grid-cols-1 md:grid-cols-12 gap-5 items-start">
          <!-- Input File Foto & Preview -->
          <div class="md:col-span-5 space-y-2">
            <label class="block text-xs font-semibold text-slate-700">Pilih Foto Kegiatan *</label>
            <div 
              @click="galleryFileInput?.click()"
              class="border-2 border-dashed border-emerald-300 hover:border-emerald-500 bg-emerald-50/40 rounded-2xl p-4 flex flex-col items-center justify-center cursor-pointer min-h-[170px] text-center transition group overflow-hidden relative"
            >
              <img 
                v-if="galleryPreview" 
                :src="galleryPreview" 
                alt="Preview" 
                class="absolute inset-0 w-full h-full object-cover rounded-xl"
              />
              <div v-else class="space-y-1">
                <span class="text-3xl">📷</span>
                <p class="text-xs font-semibold text-emerald-800">Klik untuk upload foto</p>
                <p class="text-[10px] text-slate-400">JPG, PNG, atau WEBP (Maks 5MB)</p>
              </div>
            </div>
            <input 
              ref="galleryFileInput" 
              type="file" 
              accept="image/*" 
              class="hidden" 
              @change="onGalleryFileChange" 
            />
          </div>

          <!-- Jenis Kegiatan & Tombol Upload -->
          <div class="md:col-span-7 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jenis Kegiatan *</label>
              <select 
                v-model="galleryType"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 bg-white"
              >
                <option value="KBM Sains & Eksplorasi">KBM Sains & Eksplorasi</option>
                <option value="Tahfidz & Tasmi' Qur'an">Tahfidz & Tasmi' Qur'an</option>
                <option value="Market Day Cilik">Market Day Cilik</option>
                <option value="Outbound & Rihlah">Outbound & Rihlah</option>
                <option value="Pentas Seni & Kaligrafi Islami">Pentas Seni & Kaligrafi Islami</option>
                <option value="Robotik & Teknologi">Robotik & Teknologi</option>
                <option value="Kegiatan Kesiswaan Lainnya">Kegiatan Kesiswaan Lainnya</option>
              </select>
            </div>

            <div class="pt-2">
              <button
                type="submit"
                :disabled="submitting"
                class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold text-xs sm:text-sm transition flex items-center justify-center gap-2 shadow-md shadow-emerald-600/20 disabled:opacity-50"
              >
                <span v-if="submitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span>{{ submitting ? 'Mengunggah...' : 'Unggah ke Galeri' }}</span>
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- Grid Foto Galeri Yang Sudah Terupload -->
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
            <span>🖼️</span> Foto Galeri Terpublikasi ({{ galleries.length }})
          </h3>
          <span class="text-xs text-slate-500">Urut dari waktu posting terbaru</span>
        </div>

        <div v-if="loading" class="p-8 text-center text-slate-400">
          <div class="w-6 h-6 border-2 border-emerald-600 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
          <p class="text-xs">Memuat galeri...</p>
        </div>

        <div v-else-if="galleries.length === 0" class="p-12 text-center bg-white rounded-2xl border border-slate-200">
          <span class="text-4xl block mb-2">📸</span>
          <p class="text-sm font-semibold text-slate-700">Belum ada foto galeri yang diunggah.</p>
          <p class="text-xs text-slate-400 mt-1">Gunakan formulir di atas untuk mengunggah foto kegiatan siswa perdana.</p>
        </div>

        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
          <div 
            v-for="item in galleries" 
            :key="item.id"
            class="group bg-white rounded-2xl overflow-hidden border border-slate-200 shadow-xs hover:shadow-md transition flex flex-col"
          >
            <!-- Foto Besar Jelas -->
            <div class="aspect-4/3 w-full bg-slate-100 relative overflow-hidden">
              <img 
                :src="item.image_url" 
                :alt="item.activity_type" 
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                loading="lazy"
              />
              <button
                @click="deleteGallery(item.id)"
                type="button"
                class="absolute top-2 right-2 p-1.5 rounded-lg bg-red-600/90 text-white text-xs opacity-0 group-hover:opacity-100 hover:bg-red-700 transition shadow-sm"
                title="Hapus Foto"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>

            <!-- Jenis Kegiatan & Waktu Posting -->
            <div class="p-3 flex-1 flex flex-col justify-between space-y-1">
              <span class="inline-block px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 text-[11px] font-bold truncate">
                {{ item.activity_type }}
              </span>
              <p class="text-[10px] text-slate-400 flex items-center gap-1">
                <span>🕒</span> {{ formatPostTime(item.created_at) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: PRESTASI SISWA -->
    <div v-show="activeTab === 'achievements'" class="space-y-6">
      <!-- Form Tambah Prestasi -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/80">
        <h2 class="text-base font-bold text-slate-800 mb-1 flex items-center gap-2">
          <span>➕</span> Tambah Prestasi Siswa
        </h2>
        <p class="text-xs text-slate-500 mb-5">
          Unggah foto piala/piagam beserta judul prestasi siswa untuk ditampilkan di web sekolah.
        </p>

        <form @submit.prevent="submitAchievement" class="grid grid-cols-1 md:grid-cols-12 gap-5 items-start">
          <!-- Input File Foto & Preview -->
          <div class="md:col-span-5 space-y-2">
            <label class="block text-xs font-semibold text-slate-700">Foto Prestasi / Dokumentasi *</label>
            <div 
              @click="achievementFileInput?.click()"
              class="border-2 border-dashed border-amber-300 hover:border-amber-500 bg-amber-50/40 rounded-2xl p-4 flex flex-col items-center justify-center cursor-pointer min-h-[170px] text-center transition group overflow-hidden relative"
            >
              <img 
                v-if="achievementPreview" 
                :src="achievementPreview" 
                alt="Preview" 
                class="absolute inset-0 w-full h-full object-cover rounded-xl"
              />
              <div v-else class="space-y-1">
                <span class="text-3xl">🏆</span>
                <p class="text-xs font-semibold text-amber-900">Klik untuk upload foto prestasi</p>
                <p class="text-[10px] text-slate-400">JPG, PNG, atau WEBP (Maks 5MB)</p>
              </div>
            </div>
            <input 
              ref="achievementFileInput" 
              type="file" 
              accept="image/*" 
              class="hidden" 
              @change="onAchievementFileChange" 
            />
          </div>

          <!-- Judul Prestasi & Tombol Simpan -->
          <div class="md:col-span-7 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Judul Prestasi *</label>
              <input 
                v-model="achievementTitle" 
                type="text" 
                required
                placeholder="Contoh: Juara 1 Tahfidz Qur'an 1 Juz Tingkat Kabupaten"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-amber-500"
              />
            </div>

            <div class="pt-2">
              <button
                type="submit"
                :disabled="submitting"
                class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 active:scale-95 text-white font-bold text-xs sm:text-sm transition flex items-center justify-center gap-2 shadow-md shadow-amber-600/20 disabled:opacity-50"
              >
                <span v-if="submitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span>{{ submitting ? 'Menyimpan...' : 'Simpan Prestasi Siswa' }}</span>
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- Grid Prestasi Siswa -->
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
            <span>🎖️</span> Daftar Prestasi Siswa Terpublikasi ({{ achievements.length }})
          </h3>
          <span class="text-xs text-slate-500">Urut dari waktu posting terbaru</span>
        </div>

        <div v-if="loading" class="p-8 text-center text-slate-400">
          <div class="w-6 h-6 border-2 border-amber-600 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
          <p class="text-xs">Memuat prestasi...</p>
        </div>

        <div v-else-if="achievements.length === 0" class="p-12 text-center bg-white rounded-2xl border border-slate-200">
          <span class="text-4xl block mb-2">🏆</span>
          <p class="text-sm font-semibold text-slate-700">Belum ada prestasi siswa yang diunggah.</p>
          <p class="text-xs text-slate-400 mt-1">Tambahkan foto dan judul kejuaraan siswa melalui form di atas.</p>
        </div>

        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
          <div 
            v-for="item in achievements" 
            :key="item.id"
            class="group bg-white rounded-2xl overflow-hidden border border-slate-200 shadow-xs hover:shadow-md transition flex flex-col"
          >
            <!-- Foto Prestasi -->
            <div class="aspect-4/3 w-full bg-slate-100 relative overflow-hidden">
              <img 
                :src="item.image_url" 
                :alt="item.title" 
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                loading="lazy"
              />
              <button
                @click="deleteAchievement(item.id)"
                type="button"
                class="absolute top-2 right-2 p-1.5 rounded-lg bg-red-600/90 text-white text-xs opacity-0 group-hover:opacity-100 hover:bg-red-700 transition shadow-sm"
                title="Hapus Prestasi"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>

            <!-- Judul & Waktu Posting -->
            <div class="p-3 flex-1 flex flex-col justify-between space-y-1.5">
              <h4 class="text-xs font-bold text-slate-800 line-clamp-2 leading-snug">
                {{ item.title }}
              </h4>
              <p class="text-[10px] text-slate-400 flex items-center gap-1">
                <span>🕒</span> {{ formatPostTime(item.created_at) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 3: WARTA & ARTIKEL SEKOLAH -->
    <div v-show="activeTab === 'articles'" class="space-y-6">
      <!-- Form Tambah Warta / Artikel -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/80">
        <h2 class="text-base font-bold text-slate-800 mb-1 flex items-center gap-2">
          <span>➕</span> Tulis Warta & Artikel Baru
        </h2>
        <p class="text-xs text-slate-500 mb-5">
          Unggah foto sampul, masukkan judul dan keterangan isi berita untuk dipublikasikan.
        </p>

        <form @submit.prevent="submitArticle" class="grid grid-cols-1 md:grid-cols-12 gap-5 items-start">
          <!-- Input File Foto Sampul & Preview -->
          <div class="md:col-span-4 space-y-2">
            <label class="block text-xs font-semibold text-slate-700">Foto Sampul Warta *</label>
            <div 
              @click="articleFileInput?.click()"
              class="border-2 border-dashed border-teal-300 hover:border-teal-500 bg-teal-50/40 rounded-2xl p-4 flex flex-col items-center justify-center cursor-pointer min-h-[210px] text-center transition group overflow-hidden relative"
            >
              <img 
                v-if="articlePreview" 
                :src="articlePreview" 
                alt="Preview" 
                class="absolute inset-0 w-full h-full object-cover rounded-xl"
              />
              <div v-else class="space-y-1">
                <span class="text-3xl">📰</span>
                <p class="text-xs font-semibold text-teal-900">Klik untuk upload sampul</p>
                <p class="text-[10px] text-slate-400">JPG, PNG, atau WEBP (Maks 5MB)</p>
              </div>
            </div>
            <input 
              ref="articleFileInput" 
              type="file" 
              accept="image/*" 
              class="hidden" 
              @change="onArticleFileChange" 
            />
          </div>

          <!-- Judul & Keterangan Artikel -->
          <div class="md:col-span-8 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Judul Warta / Artikel *</label>
              <input 
                v-model="articleTitle" 
                type="text" 
                required
                placeholder="Contoh: Semarak Market Day Cilik SDI Bina Insan Mandiri Purwokerto"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-teal-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Keterangan / Isi Warta *</label>
              <textarea 
                v-model="articleDesc" 
                required
                rows="4"
                placeholder="Tuliskan keterangan lengkap kegiatan, rincian peristiwa, atau isi warta sekolah..."
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-teal-500 leading-relaxed"
              ></textarea>
            </div>

            <div class="pt-1">
              <button
                type="submit"
                :disabled="submitting"
                class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 active:scale-95 text-white font-bold text-xs sm:text-sm transition flex items-center justify-center gap-2 shadow-md shadow-teal-600/20 disabled:opacity-50"
              >
                <span v-if="submitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span>{{ submitting ? 'Memublikasikan...' : 'Publikasikan Warta' }}</span>
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- List Warta & Artikel Terpublikasi -->
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
            <span>📚</span> Daftar Warta & Artikel Terpublikasi ({{ articles.length }})
          </h3>
          <span class="text-xs text-slate-500">Urut dari waktu posting terbaru</span>
        </div>

        <div v-if="loading" class="p-8 text-center text-slate-400">
          <div class="w-6 h-6 border-2 border-teal-600 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
          <p class="text-xs">Memuat warta & artikel...</p>
        </div>

        <div v-else-if="articles.length === 0" class="p-12 text-center bg-white rounded-2xl border border-slate-200">
          <span class="text-4xl block mb-2">📰</span>
          <p class="text-sm font-semibold text-slate-700">Belum ada warta atau artikel yang dipublikasikan.</p>
          <p class="text-xs text-slate-400 mt-1">Tulis dan terbitkan berita kegiatan sekolah melalui formulir di atas.</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div 
            v-for="item in articles" 
            :key="item.id"
            class="group bg-white rounded-2xl overflow-hidden border border-slate-200 shadow-xs hover:shadow-md transition flex flex-col sm:flex-row gap-4 p-4"
          >
            <!-- Foto Sampul -->
            <div class="w-full sm:w-40 aspect-4/3 sm:aspect-square shrink-0 bg-slate-100 rounded-xl overflow-hidden relative">
              <img 
                :src="item.image_url" 
                :alt="item.title" 
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                loading="lazy"
              />
            </div>

            <!-- Konten Artikel & Waktu Posting -->
            <div class="flex-1 flex flex-col justify-between space-y-2">
              <div class="space-y-1">
                <div class="flex items-start justify-between gap-2">
                  <h4 class="text-sm font-bold text-slate-900 group-hover:text-teal-700 transition leading-snug">
                    {{ item.title }}
                  </h4>
                  <button
                    @click="deleteArticle(item.id)"
                    type="button"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition shrink-0"
                    title="Hapus Artikel"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
                <p class="text-xs text-slate-600 line-clamp-3 leading-relaxed whitespace-pre-line font-light">
                  {{ item.description }}
                </p>
              </div>

              <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                <span class="flex items-center gap-1">
                  <span>🕒 Diposting:</span> {{ formatPostTime(item.created_at) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
