<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '../services/api'
import { showSuccess, showWarning, showError, showConfirm } from '../services/swal'
import { compressImage } from '../utils/imageCompressor'

// Active Tab: 'gallery' | 'articles' (Warta & Prestasi)
const activeTab = ref<'gallery' | 'articles'>('articles')

// Loading states
const loading = ref(false)
const submitting = ref(false)
const compressing = ref(false)

// Helper format ukuran file
const formatBytes = (bytes: number): string => {
  if (bytes === 0) return '0 B'
  if (bytes > 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
  return Math.round(bytes / 1024) + ' KB'
}

// 1. DATA GALERI (Foto + Jenis Kegiatan)
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
const galleryCompressionInfo = ref<string>('')

// 2. DATA WARTA & PRESTASI (Foto + Kategori + Judul + Keterangan)
interface ArticleItem {
  id: number
  title: string
  category: string
  description: string
  image_url: string
  created_at: string
  creator?: {
    id: number
    nama: string
  }
}
const articles = ref<ArticleItem[]>([])
const articleFile = ref<File | null>(null)
const articlePreview = ref<string>('')
const articleCategory = ref<string>('Prestasi')
const articleTitle = ref<string>('')
const articleDesc = ref<string>('')
const articleFileInput = ref<HTMLInputElement | null>(null)
const articleCompressionInfo = ref<string>('')

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

const fetchArticles = async () => {
  try {
    const res = await api.get('/cms/articles')
    if (res.data?.success) {
      articles.value = res.data.data.data || res.data.data || []
    }
  } catch (err) {
    console.error('Gagal mengambil data warta & prestasi:', err)
  }
}

const loadData = async () => {
  loading.value = true
  if (activeTab.value === 'gallery') await fetchGalleries()
  else if (activeTab.value === 'articles') await fetchArticles()
  loading.value = false
}

// Handlers Kompresi Foto Galeri
const onGalleryFileChange = async (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    const rawFile = target.files[0]
    compressing.value = true
    try {
      const compressed = await compressImage(rawFile, 1400, 0.82)
      galleryFile.value = compressed
      galleryPreview.value = URL.createObjectURL(compressed)
      const hemat = Math.round((1 - compressed.size / rawFile.size) * 100)
      galleryCompressionInfo.value = `Ukuran dikompresi: ${formatBytes(rawFile.size)} ➔ ${formatBytes(compressed.size)} (Hemat ${hemat > 0 ? hemat : 0}%)`
    } catch {
      galleryFile.value = rawFile
      galleryPreview.value = URL.createObjectURL(rawFile)
      galleryCompressionInfo.value = `Ukuran asli: ${formatBytes(rawFile.size)}`
    } finally {
      compressing.value = false
    }
  }
}

// Handlers Kompresi Foto Warta & Prestasi
const onArticleFileChange = async (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    const rawFile = target.files[0]
    compressing.value = true
    try {
      const compressed = await compressImage(rawFile, 1400, 0.82)
      articleFile.value = compressed
      articlePreview.value = URL.createObjectURL(compressed)
      const hemat = Math.round((1 - compressed.size / rawFile.size) * 100)
      articleCompressionInfo.value = `Ukuran dikompresi: ${formatBytes(rawFile.size)} ➔ ${formatBytes(compressed.size)} (Hemat ${hemat > 0 ? hemat : 0}%)`
    } catch {
      articleFile.value = rawFile
      articlePreview.value = URL.createObjectURL(rawFile)
      articleCompressionInfo.value = `Ukuran asli: ${formatBytes(rawFile.size)}`
    } finally {
      compressing.value = false
    }
  }
}

// Submit Galeri
const submitGallery = async () => {
  if (!galleryFile.value) {
    showWarning('Perhatian', 'Silakan pilih foto kegiatan yang ingin diunggah.')
    return
  }
  if (!galleryType.value) {
    showWarning('Perhatian', 'Silakan pilih jenis kegiatan.')
    return
  }

  submitting.value = true
  const formData = new FormData()
  formData.append('foto', galleryFile.value)
  formData.append('activity_type', galleryType.value)

  try {
    const res = await api.post('/cms/galleries', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    if (res.data?.success) {
      showSuccess('Berhasil!', 'Foto kegiatan berhasil diunggah ke Galeri.')
      galleryFile.value = null
      galleryPreview.value = ''
      galleryCompressionInfo.value = ''
      if (galleryFileInput.value) galleryFileInput.value.value = ''
      await fetchGalleries()
    }
  } catch (err: any) {
    showError('Gagal', err.response?.data?.message || 'Gagal mengunggah foto kegiatan.')
  } finally {
    submitting.value = false
  }
}

// Hapus Galeri
const deleteGallery = async (id: number) => {
  const confirmed = await showConfirm(
    'Hapus Foto Galeri?',
    'Foto ini akan dihapus permanen dari galeri web publik.'
  )
  if (!confirmed) return

  try {
    const res = await api.delete(`/cms/galleries/${id}`)
    if (res.data?.success) {
      showSuccess('Berhasil', 'Foto kegiatan berhasil dihapus.')
      await fetchGalleries()
    }
  } catch (err: any) {
    showError('Gagal', err.response?.data?.message || 'Gagal menghapus foto.')
  }
}

// Submit Warta & Prestasi
const submitArticle = async () => {
  if (!articleFile.value) {
    showWarning('Perhatian', 'Silakan pilih foto utama untuk postingan ini.')
    return
  }
  if (!articleTitle.value.trim()) {
    showWarning('Perhatian', 'Silakan isi judul artikel atau prestasi.')
    return
  }
  if (!articleDesc.value.trim()) {
    showWarning('Perhatian', 'Silakan tuliskan keterangan / isi postingan.')
    return
  }

  submitting.value = true
  const formData = new FormData()
  formData.append('foto', articleFile.value)
  formData.append('title', articleTitle.value.trim())
  formData.append('category', articleCategory.value)
  formData.append('description', articleDesc.value.trim())

  try {
    const res = await api.post('/cms/articles', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    if (res.data?.success) {
      showSuccess('Berhasil!', `Postingan ${articleCategory.value} berhasil dipublikasikan.`)
      articleFile.value = null
      articlePreview.value = ''
      articleTitle.value = ''
      articleDesc.value = ''
      articleCompressionInfo.value = ''
      if (articleFileInput.value) articleFileInput.value.value = ''
      await fetchArticles()
    }
  } catch (err: any) {
    showError('Gagal', err.response?.data?.message || 'Gagal mempublikasikan postingan.')
  } finally {
    submitting.value = false
  }
}

// Hapus Warta & Prestasi
const deleteArticle = async (id: number) => {
  const confirmed = await showConfirm(
    'Hapus Postingan Ini?',
    'Postingan ini akan dihapus permanen dari web sekolah publik.'
  )
  if (!confirmed) return

  try {
    const res = await api.delete(`/cms/articles/${id}`)
    if (res.data?.success) {
      showSuccess('Berhasil', 'Postingan berhasil dihapus.')
      await fetchArticles()
    }
  } catch (err: any) {
    showError('Gagal', err.response?.data?.message || 'Gagal menghapus postingan.')
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

    <!-- Navigation Tabs (2 Tab Utama) -->
    <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-2">
      <button
        @click="activeTab = 'articles'; loadData()"
        type="button"
        class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer"
        :class="activeTab === 'articles' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
      >
        <span>🏆📰</span> Warta & Prestasi Siswa
      </button>

      <button
        @click="activeTab = 'gallery'; loadData()"
        type="button"
        class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer"
        :class="activeTab === 'gallery' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
      >
        <span>📸</span> Galeri Kegiatan Siswa
      </button>
    </div>

    <!-- TAB 1: WARTA & PRESTASI SISWA (GABUNGAN FORM ARTIKEL & PRESTASI) -->
    <div v-show="activeTab === 'articles'" class="space-y-6">
      <!-- Form Tambah Warta & Prestasi -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/80">
        <h2 class="text-base font-bold text-slate-800 mb-1 flex items-center gap-2">
          <span>➕</span> Publikasikan Warta atau Prestasi Siswa
        </h2>
        <p class="text-xs text-slate-500 mb-5">
          Formulir terpadu untuk menerbitkan artikel edukasi, berita sekolah, maupun kabar pencapaian prestasi juara siswa.
        </p>

        <form @submit.prevent="submitArticle" class="grid grid-cols-1 md:grid-cols-12 gap-5 items-start">
          
          <!-- Input File Foto Sampul & Preview -->
          <div class="md:col-span-5 space-y-2">
            <label class="block text-xs font-semibold text-slate-700">Foto Sampul Utama *</label>
            <div 
              @click="articleFileInput?.click()"
              class="border-2 border-dashed border-emerald-300 hover:border-emerald-500 bg-emerald-50/40 rounded-2xl p-4 flex flex-col items-center justify-center cursor-pointer min-h-[220px] text-center transition group overflow-hidden relative"
            >
              <img 
                v-if="articlePreview" 
                :src="articlePreview" 
                alt="Preview" 
                class="absolute inset-0 w-full h-full object-cover rounded-xl"
              />
              <div v-else class="space-y-1">
                <span class="text-4xl block mb-1">🖼️</span>
                <p class="text-xs font-semibold text-emerald-800">Klik untuk upload foto sampul</p>
                <p class="text-[10px] text-slate-400">Otomatis dikompresi ringan & tajam</p>
              </div>
            </div>
            <input 
              ref="articleFileInput" 
              type="file" 
              accept="image/*" 
              class="hidden" 
              @change="onArticleFileChange" 
            />
            <div v-if="articleCompressionInfo" class="mt-2 text-[11px] text-emerald-800 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200/80 flex items-center gap-1.5 font-medium">
              <span>⚡</span> <span>{{ articleCompressionInfo }}</span>
            </div>
          </div>

          <!-- Kategori, Judul, Keterangan & Tombol Submit -->
          <div class="md:col-span-7 space-y-3.5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori / Jenis Konten *</label>
                <select 
                  v-model="articleCategory"
                  class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 bg-white font-medium"
                >
                  <option value="Prestasi">🏆 Prestasi Siswa</option>
                  <option value="Artikel">📰 Artikel & Opini</option>
                  <option value="Warta Sekolah">📢 Warta & Kabar Sekolah</option>
                  <option value="Edukasi & Karakter">🌱 Edukasi & Karakter Fitrah</option>
                </select>
              </div>
              <div class="flex items-center text-[11px] text-slate-400 pt-5">
                <span>Pilih jenis konten agar badge warna dan filter landing page sesuai.</span>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Postingan *</label>
              <input 
                v-model="articleTitle"
                type="text" 
                placeholder="Contoh: Juara 1 Tahfidz Tingkat Provinsi / Seni Mengasuh Anak Fitrah"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan / Isi Lengkap *</label>
              <textarea 
                v-model="articleDesc"
                rows="5"
                placeholder="Tuliskan cerita prestasi atau artikel warta sekolah secara lengkap di sini. Paragraf akan tampil rapi saat dibuka di halaman baca..."
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500"
              ></textarea>
            </div>

            <div class="pt-2">
              <button
                type="submit"
                :disabled="submitting || compressing"
                class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold text-xs sm:text-sm transition flex items-center justify-center gap-2 shadow-md shadow-emerald-600/20 disabled:opacity-50 cursor-pointer"
              >
                <span v-if="submitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span>{{ submitting ? 'Mempublikasikan...' : 'Publikasikan Sekarang' }}</span>
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- Daftar Warta & Prestasi Terpublikasi -->
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
            <span>📚</span> Daftar Warta & Prestasi Terpublikasi ({{ articles.length }})
          </h3>
          <span class="text-xs text-slate-500">Urut dari waktu posting terbaru</span>
        </div>

        <div v-if="loading" class="p-8 text-center text-slate-400">
          <div class="w-6 h-6 border-2 border-emerald-600 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
          <p class="text-xs">Memuat data warta & prestasi...</p>
        </div>

        <div v-else-if="articles.length === 0" class="p-12 text-center bg-white rounded-2xl border border-slate-200">
          <span class="text-4xl block mb-2">✍️</span>
          <p class="text-sm font-semibold text-slate-700">Belum ada warta atau prestasi yang dipublikasikan.</p>
          <p class="text-xs text-slate-400 mt-1">Gunakan formulir di atas untuk mempublikasikan artikel atau prestasi perdana.</p>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
          <div 
            v-for="item in articles" 
            :key="item.id"
            class="group bg-white rounded-2xl overflow-hidden border border-slate-200 shadow-xs hover:shadow-md transition flex flex-col justify-between"
          >
            <div>
              <!-- Foto Sampul -->
              <div class="aspect-16/9 w-full bg-slate-100 relative overflow-hidden">
                <img 
                  :src="item.image_url" 
                  :alt="item.title" 
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                  loading="lazy" 
                />
                <div class="absolute top-2 left-2">
                  <span 
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider shadow-xs"
                    :class="item.category === 'Prestasi' 
                      ? 'bg-amber-400 text-amber-950 font-black' 
                      : 'bg-emerald-700 text-white'"
                  >
                    {{ item.category === 'Prestasi' ? '🏆 Prestasi' : (item.category || 'Warta') }}
                  </span>
                </div>
                <button
                  @click="deleteArticle(item.id)"
                  type="button"
                  class="absolute top-2 right-2 p-1.5 rounded-lg bg-red-600/90 text-white text-xs opacity-0 group-hover:opacity-100 hover:bg-red-700 transition shadow-sm cursor-pointer"
                  title="Hapus Postingan"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>

              <!-- Judul & Keterangan -->
              <div class="p-4 space-y-2">
                <div class="text-[10px] text-slate-400 font-mono flex items-center justify-between">
                  <span>🕒 {{ formatPostTime(item.created_at) }}</span>
                </div>
                <h4 class="font-bold text-xs sm:text-sm text-slate-900 group-hover:text-emerald-700 transition-colors line-clamp-2 leading-snug">
                  {{ item.title }}
                </h4>
                <p class="text-[11px] text-slate-500 line-clamp-2 leading-relaxed">
                  {{ item.description }}
                </p>
              </div>
            </div>

            <!-- Footer Card dengan Link ke Halaman Baru -->
            <div class="p-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
              <span class="text-[10px] text-slate-400 truncate max-w-[120px]">
                {{ item.creator?.nama || 'SDI BIM' }}
              </span>
              <router-link
                :to="`/artikel/${item.id}`"
                target="_blank"
                class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 hover:text-emerald-900 hover:underline"
              >
                <span>Lihat Halaman</span>
                <span>&nearr;</span>
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: GALERI KEGIATAN SISWA (FOTO + JENIS KEGIATAN) -->
    <div v-show="activeTab === 'gallery'" class="space-y-6">
      <!-- Form Tambah Foto Galeri -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/80">
        <h2 class="text-base font-bold text-slate-800 mb-1 flex items-center gap-2">
          <span>➕</span> Unggah Foto Kegiatan Siswa
        </h2>
        <p class="text-xs text-slate-500 mb-5">
          Unggah foto dokumentasi kegiatan siswa beserta jenis kegiatannya agar tampil di Galeri publik secara bersih.
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
                <span class="text-3xl block mb-1">📷</span>
                <p class="text-xs font-semibold text-emerald-800">Klik untuk upload foto</p>
                <p class="text-[10px] text-slate-400">JPG, PNG, atau WEBP (Maks 10MB)</p>
              </div>
            </div>
            <input 
              ref="galleryFileInput" 
              type="file" 
              accept="image/*" 
              class="hidden" 
              @change="onGalleryFileChange" 
            />
            <div v-if="galleryCompressionInfo" class="mt-2 text-[11px] text-emerald-800 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200/80 flex items-center gap-1.5 font-medium">
              <span>⚡</span> <span>{{ galleryCompressionInfo }}</span>
            </div>
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
                :disabled="submitting || compressing"
                class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold text-xs sm:text-sm transition flex items-center justify-center gap-2 shadow-md shadow-emerald-600/20 disabled:opacity-50 cursor-pointer"
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
            <!-- Foto Jelas Bersih -->
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
                class="absolute top-2 right-2 p-1.5 rounded-lg bg-red-600/90 text-white text-xs opacity-0 group-hover:opacity-100 hover:bg-red-700 transition shadow-sm cursor-pointer"
                title="Hapus Foto"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>

            <!-- Jenis Kegiatan & Waktu Posting (Tanpa Keterangan Berlebih) -->
            <div class="p-3 flex-1 flex flex-col justify-between space-y-1">
              <span class="inline-block px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 text-[11px] font-bold truncate">
                {{ item.activity_type }}
              </span>
              <p class="text-[10px] text-slate-400 flex items-center gap-1 font-mono">
                <span>🕒</span> {{ formatPostTime(item.created_at) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>
