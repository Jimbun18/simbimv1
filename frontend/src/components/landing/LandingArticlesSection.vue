<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { articlesData as defaultArticlesData } from './landingData'
import api from '../../services/api'

interface DynamicArticle {
  id: number
  title: string
  category: string
  author: string
  authorRole: string
  date: string
  readTime: string
  summary: string
  tags: string[]
  image?: string
}

const router = useRouter()
const selectedCategory = ref<string>('Semua')
const items = ref<DynamicArticle[]>([...defaultArticlesData])
const isCmsMode = ref(false)

const categories = ['Semua', 'Prestasi Siswa', 'Warta Sekolah', 'Edukasi & Karakter']

const fetchCmsArticles = async () => {
  try {
    const res = await api.get('/public/articles')
    if (res.data?.success && Array.isArray(res.data.data) && res.data.data.length > 0) {
      items.value = res.data.data.map((item: any) => {
        const d = new Date(item.created_at)
        const dateFormatted = new Intl.DateTimeFormat('id-ID', {
          day: 'numeric',
          month: 'short',
          year: 'numeric',
        }).format(d)

        const isPrestasi = item.category === 'Prestasi'

        return {
          id: item.id,
          title: item.title,
          category: isPrestasi ? 'Prestasi Siswa' : (item.category || 'Warta Sekolah'),
          author: item.creator?.nama || 'SDI BIM PWT',
          authorRole: isPrestasi ? 'Pembina Prestasi' : 'Humas & Publikasi',
          date: dateFormatted,
          readTime: '3 menit baca',
          summary: item.description || '',
          tags: isPrestasi ? ['Prestasi', 'Juara', 'SDI BIM'] : ['Warta', 'Edukasi', 'SDI BIM'],
          image: item.image_url,
        }
      })
      isCmsMode.value = true
    }
  } catch (e) {
    // Fallback default
  }
}

onMounted(() => {
  fetchCmsArticles()
})

const filteredArticles = computed(() => {
  if (selectedCategory.value === 'Semua') return items.value
  return items.value.filter(a => a.category === selectedCategory.value)
})

const goToArticleDetail = (article: DynamicArticle) => {
  router.push(`/artikel/${article.id}`)
}
</script>

<template>
  <section id="artikel" class="py-20 bg-white border-b border-slate-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
      
      <!-- Section Title Header -->
      <div 
        v-reveal="{ type: 'fade-up', delay: 50 }"
        class="text-center max-w-3xl mx-auto space-y-3"
      >
        <span class="px-3.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider">
          Pusat Informasi & Prestasi
        </span>
        <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
          Warta & Prestasi Siswa
        </h2>
        <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
          Kumpulan kabar terbaru, catatan inspiratif pembelajaran fitrah, serta deretan torehan prestasi membanggakan siswa SDI Bina Insan Mandiri Purwokerto.
        </p>

        <!-- Category Filter Badges with Splash Animation -->
        <div class="flex flex-wrap items-center justify-center gap-2 pt-2">
          <button
            v-for="cat in categories"
            :key="cat"
            @click="selectedCategory = cat"
            v-splash
            class="btn-splash px-4 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer border"
            :class="selectedCategory === cat ? 'bg-emerald-700 text-white border-emerald-700 shadow-xs' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
          >
            {{ cat }}
          </button>
        </div>
      </div>

      <!-- Grid Cards Artikel (2 Kolom Mobile, 4 Desktop) -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
        <article
          v-for="(article, idx) in filteredArticles"
          :key="article.id"
          v-reveal="{ type: 'fade-up', delay: 60 + (idx % 4) * 80 }"
          class="bg-slate-50 hover:bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 hover:border-emerald-300 hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between group overflow-hidden p-3.5 sm:p-5"
        >
          <div>
            <!-- Foto Sampul -->
            <div 
              v-if="article.image" 
              @click="goToArticleDetail(article)"
              class="aspect-16/9 w-full bg-slate-200 rounded-xl sm:rounded-2xl overflow-hidden mb-2.5 sm:mb-3.5 cursor-pointer relative"
            >
              <img 
                :src="article.image" 
                :alt="article.title" 
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                loading="lazy" 
              />
            </div>

            <div class="space-y-1.5 sm:space-y-3">
              <div class="flex items-center justify-between text-[10px] sm:text-[11px] text-slate-400 font-medium">
                <span 
                  class="px-2 py-0.5 rounded-full font-bold uppercase text-[8px] sm:text-[10px]"
                  :class="article.category.includes('Prestasi') 
                    ? 'bg-amber-100 text-amber-900 border border-amber-200' 
                    : 'bg-emerald-100 text-emerald-800 border border-emerald-200'"
                >
                  {{ article.category }}
                </span>
                <span class="hidden sm:inline">{{ article.readTime }}</span>
              </div>

              <h3 
                @click="goToArticleDetail(article)"
                class="font-black text-xs sm:text-base text-slate-900 group-hover:text-emerald-700 transition-colors line-clamp-2 cursor-pointer leading-snug"
              >
                {{ article.title }}
              </h3>

              <p class="text-[11px] sm:text-xs text-slate-600 leading-relaxed line-clamp-2 sm:line-clamp-3">
                {{ article.summary }}
              </p>

              <!-- Tags (sembunyikan di layar sangat kecil agar tetap rapi) -->
              <div class="hidden sm:flex flex-wrap gap-1.5 pt-1">
                <span 
                  v-for="tag in article.tags" 
                  :key="tag" 
                  class="text-[10px] bg-white px-2 py-0.5 rounded-md text-slate-500 border border-slate-200/70"
                >
                  #{{ tag }}
                </span>
              </div>
            </div>
          </div>

          <div class="mt-3 sm:mt-6 pt-2.5 sm:pt-4 border-t border-slate-200/80 flex items-center justify-between">
            <div class="truncate max-w-[80px] sm:max-w-none">
              <div class="text-[11px] sm:text-xs font-bold text-slate-800 truncate">{{ article.author }}</div>
              <div class="text-[9px] sm:text-[10px] text-slate-400 font-mono">{{ article.date }}</div>
            </div>
            <button
              type="button"
              @click="goToArticleDetail(article)"
              class="text-[11px] sm:text-xs font-bold text-emerald-700 hover:text-emerald-800 hover:underline flex items-center gap-1 cursor-pointer shrink-0"
            >
              <span>Buka Halaman &rarr;</span>
            </button>
          </div>
        </article>
      </div>

    </div>
  </section>
</template>
