<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { articlesData as defaultArticlesData, type ArticleItem } from './landingData'
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
  content: string[]
  image?: string
}

const selectedCategory = ref<string>('Semua')
const selectedArticle = ref<DynamicArticle | null>(null)
const items = ref<DynamicArticle[]>([...defaultArticlesData])
const isCmsMode = ref(false)

const categories = ['Semua', 'Warta Sekolah', 'Edukasi & Karakter', 'Edukasi Qur\'ani', 'Kegiatan Siswa']

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
          hour: '2-digit',
          minute: '2-digit',
        }).format(d)

        const paragraphs = item.description ? item.description.split('\n').filter((p: string) => p.trim()) : []

        return {
          id: item.id,
          title: item.title,
          category: 'Warta Sekolah',
          author: 'Redaksi SDI BIM',
          authorRole: 'Humas & Publikasi',
          date: dateFormatted,
          readTime: '3 menit baca',
          summary: item.description,
          tags: ['Warta', 'SDI BIM'],
          content: paragraphs.length > 0 ? paragraphs : [item.description],
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

const openArticle = (article: DynamicArticle) => {
  selectedArticle.value = article
}

const closeArticle = () => {
  selectedArticle.value = null
}
</script>

<template>
  <section id="artikel" class="py-20 bg-white border-b border-slate-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
      
      <!-- Section Title Header -->
      <div class="text-center max-w-3xl mx-auto space-y-3">
        <span class="px-3.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider">
          Wawasan & Warta Sekolah
        </span>
        <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
          Artikel & Kabar Sekolah
        </h2>
        <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
          Kumpulan tulisan inspiratif seputar pembelajaran mendalam (deep learning), kiat muroja'ah Al-Qur'an, edukasi anak islami, serta dokumentasi warta kegiatan civitas SDI Bina Insan Mandiri.
        </p>

        <!-- Category Filter Badges -->
        <div class="flex flex-wrap items-center justify-center gap-2 pt-2">
          <button
            v-for="cat in categories"
            :key="cat"
            @click="selectedCategory = cat"
            class="px-4 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer border"
            :class="selectedCategory === cat ? 'bg-emerald-700 text-white border-emerald-700 shadow-xs' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
          >
            {{ cat }}
          </button>
        </div>
      </div>

      <!-- Grid Cards Artikel -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <article
          v-for="article in filteredArticles"
          :key="article.id"
          class="bg-slate-50 rounded-3xl border border-slate-200/80 p-5 hover:shadow-xl hover:border-emerald-300 transition-all flex flex-col justify-between group overflow-hidden"
        >
          <div>
            <!-- Foto Sampul (Jika dari CMS) -->
            <div 
              v-if="article.image" 
              @click="openArticle(article)"
              class="aspect-16/9 w-full bg-slate-200 rounded-2xl overflow-hidden mb-3.5 cursor-pointer relative"
            >
              <img 
                :src="article.image" 
                :alt="article.title" 
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                loading="lazy" 
              />
            </div>

            <div class="space-y-3">
              <div class="flex items-center justify-between text-[11px] text-slate-400 font-medium">
                <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold uppercase text-[10px]">
                  {{ article.category }}
                </span>
                <span>{{ article.readTime }}</span>
              </div>

              <h3 
                @click="openArticle(article)"
                class="font-black text-base text-slate-900 group-hover:text-emerald-700 transition-colors line-clamp-2 cursor-pointer leading-snug"
              >
                {{ article.title }}
              </h3>

              <p class="text-xs text-slate-600 leading-relaxed line-clamp-3">
                {{ article.summary }}
              </p>

              <!-- Tags -->
              <div class="flex flex-wrap gap-1.5 pt-1">
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

          <div class="mt-6 pt-4 border-t border-slate-200/80 flex items-center justify-between">
            <div>
              <div class="text-xs font-bold text-slate-800">{{ article.author }}</div>
              <div class="text-[10px] text-slate-400 font-mono">{{ article.date }}</div>
            </div>
            <button
              type="button"
              @click="openArticle(article)"
              class="text-xs font-bold text-emerald-700 hover:text-emerald-800 hover:underline flex items-center gap-1 cursor-pointer"
            >
              <span>Baca</span>
              <span>&rarr;</span>
            </button>
          </div>
        </article>
      </div>

      <!-- MODAL BACA ARTIKEL LENGKAP -->
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div 
          v-if="selectedArticle"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
          @click.self="closeArticle"
        >
          <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-100 p-6 sm:p-8 space-y-6 animate-scaleUp">
            
            <!-- Modal Header -->
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
              <div>
                <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold uppercase text-[10px]">
                  {{ selectedArticle.category }}
                </span>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-2 leading-tight">
                  {{ selectedArticle.title }}
                </h3>
                <div class="flex items-center gap-3 text-xs text-slate-400 mt-2">
                  <span class="font-bold text-slate-700">{{ selectedArticle.author }} ({{ selectedArticle.authorRole }})</span>
                  <span>•</span>
                  <span>{{ selectedArticle.date }}</span>
                  <span>•</span>
                  <span>{{ selectedArticle.readTime }}</span>
                </div>
              </div>

              <button
                type="button"
                @click="closeArticle"
                class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-lg font-bold shrink-0 cursor-pointer"
              >
                &times;
              </button>
            </div>

            <!-- Foto Sampul Modal Jika Ada -->
            <div v-if="selectedArticle.image" class="aspect-16/9 w-full bg-slate-100 rounded-2xl overflow-hidden shadow-xs">
              <img 
                :src="selectedArticle.image" 
                :alt="selectedArticle.title" 
                class="w-full h-full object-cover" 
              />
            </div>

            <!-- Modal Content Paragraphs -->
            <div class="space-y-3.5 text-sm sm:text-base text-slate-700 leading-relaxed whitespace-pre-line">
              <p v-for="(p, i) in selectedArticle.content" :key="i">
                {{ p }}
              </p>
            </div>

            <!-- Modal Footer -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
              <div class="flex flex-wrap gap-1.5">
                <span 
                  v-for="t in selectedArticle.tags" 
                  :key="t" 
                  class="text-xs bg-slate-100 px-2.5 py-1 rounded-lg text-slate-600 font-medium"
                >
                  #{{ t }}
                </span>
              </div>
              <button
                type="button"
                @click="closeArticle"
                class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition cursor-pointer"
              >
                Tutup Artikel
              </button>
            </div>

          </div>
        </div>
      </transition>

    </div>
  </section>
</template>
