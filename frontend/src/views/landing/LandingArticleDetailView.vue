<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'
import LandingNavbar from '../../components/landing/LandingNavbar.vue'
import LandingFooter from '../../components/landing/LandingFooter.vue'

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

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const loading = ref(true)
const article = ref<ArticleItem | null>(null)
const related = ref<ArticleItem[]>([])
const copySuccess = ref(false)

const formatFullDate = (dateStr?: string) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(d) + ' WIB'
}

const formatSimpleDate = (dateStr?: string) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }).format(d)
}

const calculateReadTime = (text?: string) => {
  if (!text) return '1 menit baca'
  const words = text.trim().split(/\s+/).length
  const minutes = Math.max(1, Math.ceil(words / 180))
  return `${minutes} menit baca`
}

const fetchArticleDetail = async (id: string | number) => {
  loading.value = true
  try {
    const res = await api.get(`/public/articles/${id}`)
    if (res.data?.success && res.data.data) {
      article.value = res.data.data.article
      related.value = res.data.data.related || []
      // Update page title
      document.title = `${article.value?.title || 'Artikel & Prestasi'} - SDI Bina Insan Mandiri`
    } else {
      article.value = null
    }
  } catch (err) {
    console.error('Gagal memuat detail artikel:', err)
    article.value = null
  } finally {
    loading.value = false
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

onMounted(() => {
  if (route.params.id) {
    fetchArticleDetail(route.params.id as string)
  }
})

watch(
  () => route.params.id,
  (newId) => {
    if (newId) {
      fetchArticleDetail(newId as string)
    }
  }
)

const shareToWhatsapp = () => {
  const url = window.location.href
  const text = encodeURIComponent(`*${article.value?.title}*\n\nBaca selengkapnya di SDI Bina Insan Mandiri Purwokerto:\n${url}`)
  window.open(`https://api.whatsapp.com/send?text=${text}`, '_blank')
}

const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(window.location.href)
    copySuccess.value = true
    setTimeout(() => {
      copySuccess.value = false
    }, 2500)
  } catch {
    copySuccess.value = false
  }
}

const handlePortalAccess = () => {
  if (!auth.isAuthenticated) {
    router.push('/login')
  } else if (auth.isAdmin) {
    router.push('/dashboard')
  } else if (auth.isGuru) {
    router.push('/presensi')
  } else if (auth.isWali) {
    router.push('/portal-wali')
  } else {
    router.push('/dashboard')
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-800 font-sans selection:bg-emerald-500 selection:text-white flex flex-col justify-between">
    <LandingNavbar @open-portal="handlePortalAccess" />

    <main class="grow py-8 sm:py-14">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumb Navigasi -->
        <nav class="flex items-center gap-2 text-xs sm:text-sm text-slate-500 mb-6">
          <router-link to="/" class="hover:text-emerald-700 transition">Beranda</router-link>
          <span>/</span>
          <router-link to="/artikel" class="hover:text-emerald-700 transition">Warta & Prestasi</router-link>
          <span>/</span>
          <span class="text-slate-800 font-semibold truncate max-w-[200px] sm:max-w-xs">
            {{ article?.title || 'Detail' }}
          </span>
        </nav>

        <!-- Loading State Skeleton -->
        <div v-if="loading" class="space-y-6 animate-pulse bg-white p-6 sm:p-10 rounded-3xl shadow-xs border border-slate-200/80">
          <div class="h-6 bg-slate-200 rounded-full w-28"></div>
          <div class="h-10 bg-slate-200 rounded-2xl w-3/4"></div>
          <div class="h-4 bg-slate-200 rounded-md w-1/2"></div>
          <div class="h-80 bg-slate-200 rounded-2xl w-full"></div>
          <div class="space-y-3 pt-4">
            <div class="h-4 bg-slate-200 rounded-md w-full"></div>
            <div class="h-4 bg-slate-200 rounded-md w-11/12"></div>
            <div class="h-4 bg-slate-200 rounded-md w-4/5"></div>
          </div>
        </div>

        <!-- 404 Not Found State -->
        <div 
          v-else-if="!article" 
          class="bg-white p-10 sm:p-16 rounded-3xl text-center shadow-xs border border-slate-200/80 space-y-4"
        >
          <div class="w-16 h-16 mx-auto rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-2xl font-bold">
            !
          </div>
          <h2 class="text-xl sm:text-2xl font-black text-slate-900">Artikel atau Prestasi Tidak Ditemukan</h2>
          <p class="text-sm text-slate-500 max-w-md mx-auto">
            Halaman yang Anda tuju mungkin telah dipindahkan atau dihapus dari sistem publikasi warta sekolah.
          </p>
          <div class="pt-4">
            <router-link
              to="/artikel"
              class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-sm shadow-md transition"
            >
              &larr; Kembali ke Daftar Warta & Prestasi
            </router-link>
          </div>
        </div>

        <!-- Article Content Card -->
        <article v-else class="bg-white rounded-3xl shadow-sm border border-slate-200/70 overflow-hidden">
          
          <!-- Header Area -->
          <header class="p-6 sm:p-10 pb-4 sm:pb-6 space-y-4 border-b border-slate-100">
            <!-- Badge & Metadata -->
            <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
              <span 
                class="px-3.5 py-1 rounded-full font-bold uppercase tracking-wider text-[11px] shadow-xs"
                :class="article.category === 'Prestasi' 
                  ? 'bg-amber-100 text-amber-900 border border-amber-300' 
                  : 'bg-emerald-100 text-emerald-900 border border-emerald-300'"
              >
                {{ article.category === 'Prestasi' ? '🏆 Prestasi Siswa' : (article.category || '📰 Warta Sekolah') }}
              </span>
              <div class="flex items-center gap-2 text-slate-400 font-mono text-[11px] sm:text-xs">
                <span>⏱️ {{ calculateReadTime(article.description) }}</span>
              </div>
            </div>

            <!-- Main Title -->
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-black text-slate-900 leading-tight tracking-tight">
              {{ article.title }}
            </h1>

            <!-- Author & Date Info -->
            <div class="flex items-center justify-between text-xs sm:text-sm text-slate-500 pt-2 border-t border-slate-100">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-emerald-700 text-white font-black flex items-center justify-center text-xs shadow-xs">
                  BIM
                </div>
                <div>
                  <div class="font-bold text-slate-900">{{ article.creator?.nama || 'SDI Bina Insan Mandiri' }}</div>
                  <div class="text-[11px] text-slate-400">Tim Publikasi & Humas</div>
                </div>
              </div>
              <div class="text-right font-mono text-[11px] sm:text-xs text-slate-400">
                {{ formatFullDate(article.created_at) }}
              </div>
            </div>
          </header>

          <!-- Featured Image (Besar, Tajam, Proporsional) -->
          <div v-if="article.image_url" class="relative bg-slate-950 max-h-[480px] overflow-hidden flex items-center justify-center">
            <img 
              :src="article.image_url" 
              :alt="article.title"
              class="w-full h-auto max-h-[480px] object-contain sm:object-cover"
            />
          </div>

          <!-- Body Text (Formatting Rapi, Nyaman Dibaca) -->
          <div class="p-6 sm:p-10 space-y-6 text-slate-700 text-sm sm:text-base sm:leading-relaxed">
            <div 
              class="prose prose-slate max-w-none whitespace-pre-line text-[15px] sm:text-[17px] leading-relaxed sm:leading-8 font-normal"
            >
              {{ article.description }}
            </div>

            <!-- Kutipan Nilai Fitrah / Karakter -->
            <div class="my-8 p-5 sm:p-6 rounded-2xl bg-emerald-50/80 border-l-4 border-emerald-600 text-emerald-950 space-y-1">
              <p class="font-bold text-sm sm:text-base">Mendidik Generasi Beradab & Berprestasi</p>
              <p class="text-xs sm:text-sm text-emerald-800 leading-relaxed">
                SDI Bina Insan Mandiri Purwokerto terus berkomitmen mengawal tumbuh kembang ananda sesuai fitrah ilahiah, memadukan adab, sains, dan keterampilan hidup nyata.
              </p>
            </div>
          </div>

          <!-- Share & Back Actions Bar -->
          <footer class="p-6 sm:p-8 bg-slate-50/90 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-4">
            <router-link
              to="/artikel"
              class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-white hover:bg-slate-100 text-slate-800 font-bold text-xs sm:text-sm border border-slate-300 shadow-xs transition cursor-pointer"
            >
              &larr; Semua Warta & Prestasi
            </router-link>

            <!-- Tombol Bagikan -->
            <div class="w-full sm:w-auto flex items-center justify-end gap-2.5">
              <span class="text-xs font-bold text-slate-500 hidden sm:inline">Bagikan:</span>
              <button
                type="button"
                @click="shareToWhatsapp"
                class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition cursor-pointer"
              >
                <span>💬 WhatsApp</span>
              </button>
              <button
                type="button"
                @click="copyLink"
                class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs shadow-xs transition cursor-pointer"
              >
                <span>🔗 {{ copySuccess ? 'Tersalin!' : 'Salin Tautan' }}</span>
              </button>
            </div>
          </footer>
        </article>

        <!-- Rekomendasi / Artikel & Prestasi Lainnya (2 Kolom Mobile, 3 Kolom Desktop) -->
        <section v-if="related.length > 0" class="mt-12 sm:mt-16 space-y-6">
          <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <h2 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
              Warta & Prestasi Lainnya
            </h2>
            <router-link to="/artikel" class="text-xs sm:text-sm font-bold text-emerald-700 hover:underline">
              Lihat Semua &rarr;
            </router-link>
          </div>

          <div class="grid grid-cols-2 md:grid-cols-3 gap-3.5 sm:gap-6">
            <div
              v-for="item in related.slice(0, 3)"
              :key="item.id"
              @click="router.push(`/artikel/${item.id}`)"
              class="group bg-white rounded-2xl border border-slate-200/80 p-3 sm:p-4 shadow-xs hover:shadow-lg transition-all duration-300 cursor-pointer flex flex-col justify-between"
            >
              <div class="space-y-2">
                <div class="aspect-16/10 rounded-xl overflow-hidden bg-slate-100 relative">
                  <img
                    v-if="item.image_url"
                    :src="item.image_url"
                    :alt="item.title"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                  />
                  <div class="absolute top-2 left-2">
                    <span 
                      class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-white/90 backdrop-blur-xs text-slate-800 shadow-xs"
                    >
                      {{ item.category === 'Prestasi' ? 'Prestasi' : 'Warta' }}
                    </span>
                  </div>
                </div>

                <div class="text-[10px] text-slate-400 font-mono">
                  {{ formatSimpleDate(item.created_at) }}
                </div>

                <h3 class="font-bold text-xs sm:text-sm text-slate-900 group-hover:text-emerald-700 line-clamp-2 leading-snug">
                  {{ item.title }}
                </h3>
              </div>

              <div class="pt-3 border-t border-slate-100 flex items-center text-[11px] font-bold text-emerald-700">
                <span>Baca Selengkapnya &rarr;</span>
              </div>
            </div>
          </div>
        </section>

      </div>
    </main>

    <LandingFooter @open-portal="handlePortalAccess" />
  </div>
</template>
