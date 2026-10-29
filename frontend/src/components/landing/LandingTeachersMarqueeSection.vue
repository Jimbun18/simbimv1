<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '../../services/api'

interface TeacherItem {
  id: number
  name: string
  role: string
  foto_url?: string | null
}

const teachers = ref<TeacherItem[]>([])
const loading = ref(false)

const fallbackTeachers: TeacherItem[] = [
  { id: 1, name: 'Rasiti, S.Pd.', role: 'Kepala Sekolah', foto_url: null },
  { id: 7, name: 'Hilda Anika Ramadhani, S.Pd.', role: 'Guru Kelas 2', foto_url: null },
  { id: 8, name: 'Elsa Rahmawati, S.Pd.', role: 'Guru Kelas 3', foto_url: null },
  { id: 13, name: 'Lulu Farihati, S.Pd.', role: 'Guru Kelas 4', foto_url: null },
  { id: 9, name: 'Rusydah Hasnaa Taqiyyah Hastuti, S.Pd.', role: 'Guru Kelas', foto_url: null },
  { id: 10, name: 'Abdul Rosyid Hamid, S.Pd.', role: 'Guru Kelas', foto_url: null },
  { id: 12, name: 'Nur Kafi Ramadhani, S.Psi.', role: 'Guru BK', foto_url: null },
  { id: 11, name: 'Bayu Caesario Nor Saputra', role: 'Staf Tata Usaha', foto_url: null },
]

const fetchTeachers = async () => {
  loading.value = true
  try {
    const res = await api.get('/public/teachers')
    if (res.data?.success && Array.isArray(res.data.data) && res.data.data.length > 0) {
      teachers.value = res.data.data
    } else {
      teachers.value = fallbackTeachers
    }
  } catch (err) {
    console.error('Gagal mengambil data tenaga pendidik untuk beranda:', err)
    teachers.value = fallbackTeachers
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchTeachers()
})
</script>

<template>
  <section class="py-14 sm:py-18 bg-white border-b border-slate-200/80 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-8 sm:mb-10">
      <div 
        v-reveal="{ type: 'fade-up', delay: 50 }"
        class="flex flex-col sm:flex-row sm:items-end justify-between gap-4"
      >
        <div>
          <span class="px-3.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider">
            👨‍🏫 Tenaga Pendidik
          </span>
          <h2 class="mt-2 text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
            Dewan Asatidz & Tenaga Kependidikan
          </h2>
          <p class="mt-1 text-xs sm:text-sm text-slate-500 max-w-2xl">
            Pendidik berjiwa pengasuh yang berdedikasi mendampingi fitrah dan potensi unggul ananda tercinta.
          </p>
        </div>

        <router-link
          to="/profil?tab=guru"
          v-splash="'emerald'"
          class="btn-splash inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs sm:text-sm transition-all border border-emerald-200 self-start sm:self-auto shrink-0 shadow-xs hover:shadow-md cursor-pointer hover:-translate-y-0.5 active:translate-y-0"
        >
          <span>Lihat Semua Pendidik</span>
          <span>&rarr;</span>
        </router-link>
      </div>
    </div>

    <!-- Infinite Auto Scroll Carousel Kesamping -->
    <div class="relative w-full overflow-hidden py-3">
      <!-- Gradient Fade Edge di kiri & kanan -->
      <div class="pointer-events-none absolute left-0 top-0 bottom-0 w-12 sm:w-28 bg-gradient-to-r from-white via-white/80 to-transparent z-10"></div>
      <div class="pointer-events-none absolute right-0 top-0 bottom-0 w-12 sm:w-28 bg-gradient-to-l from-white via-white/80 to-transparent z-10"></div>

      <!-- Marquee Track Wrapper -->
      <div class="marquee-track flex gap-4 sm:gap-6 hover:[animation-play-state:paused]">
        
        <!-- SET 1 -->
        <div class="flex gap-4 sm:gap-6 shrink-0">
          <div 
            v-for="teacher in teachers" 
            :key="'set1-' + teacher.id"
            class="w-52 sm:w-60 p-3.5 sm:p-4 rounded-3xl bg-white border border-slate-200/90 shadow-xs hover:shadow-xl hover:border-emerald-300 transition-all duration-300 flex flex-col group select-none shrink-0"
          >
            <!-- 1. FOTO -->
            <div class="aspect-4/5 w-full rounded-2xl overflow-hidden bg-slate-100 relative mb-3 shadow-xs border border-slate-100">
              <img 
                v-if="teacher.foto_url" 
                :src="teacher.foto_url" 
                :alt="teacher.name"
                class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500" 
                loading="lazy"
              />
              <div 
                v-else 
                class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-emerald-800 via-teal-800 to-slate-900 text-white p-4 text-center"
              >
                <div class="w-14 h-14 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-xl font-black text-emerald-200 shadow-inner mb-1.5 group-hover:scale-110 transition-transform">
                  {{ teacher.name.charAt(0) }}
                </div>
                <span class="text-[9px] tracking-wider font-semibold text-emerald-200 uppercase">SDI BIM</span>
              </div>
            </div>

            <!-- 2. NAMA -->
            <h4 class="font-bold text-xs sm:text-sm text-slate-900 group-hover:text-emerald-800 transition-colors leading-snug truncate">
              {{ teacher.name }}
            </h4>

            <!-- 3. JABATAN -->
            <p class="text-[11px] sm:text-xs font-semibold text-emerald-700 leading-tight mt-0.5 truncate">
              {{ teacher.role }}
            </p>
          </div>
        </div>

        <!-- SET 2 (Duplikasi untuk Seamless Infinite Loop) -->
        <div class="flex gap-4 sm:gap-6 shrink-0" aria-hidden="true">
          <div 
            v-for="teacher in teachers" 
            :key="'set2-' + teacher.id"
            class="w-52 sm:w-60 p-3.5 sm:p-4 rounded-3xl bg-white border border-slate-200/90 shadow-xs hover:shadow-xl hover:border-emerald-300 transition-all duration-300 flex flex-col group select-none shrink-0"
          >
            <!-- 1. FOTO -->
            <div class="aspect-4/5 w-full rounded-2xl overflow-hidden bg-slate-100 relative mb-3 shadow-xs border border-slate-100">
              <img 
                v-if="teacher.foto_url" 
                :src="teacher.foto_url" 
                :alt="teacher.name"
                class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500" 
                loading="lazy"
              />
              <div 
                v-else 
                class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-emerald-800 via-teal-800 to-slate-900 text-white p-4 text-center"
              >
                <div class="w-14 h-14 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-xl font-black text-emerald-200 shadow-inner mb-1.5 group-hover:scale-110 transition-transform">
                  {{ teacher.name.charAt(0) }}
                </div>
                <span class="text-[9px] tracking-wider font-semibold text-emerald-200 uppercase">SDI BIM</span>
              </div>
            </div>

            <!-- 2. NAMA -->
            <h4 class="font-bold text-xs sm:text-sm text-slate-900 group-hover:text-emerald-800 transition-colors leading-snug truncate">
              {{ teacher.name }}
            </h4>

            <!-- 3. JABATAN -->
            <p class="text-[11px] sm:text-xs font-semibold text-emerald-700 leading-tight mt-0.5 truncate">
              {{ teacher.role }}
            </p>
          </div>
        </div>

      </div>
    </div>
  </section>
</template>

<style scoped>
@keyframes marqueeHorizontal {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(-50%);
  }
}

.marquee-track {
  display: flex;
  width: max-content;
  animation: marqueeHorizontal 30s linear infinite;
  will-change: transform;
}

.marquee-track:hover {
  animation-play-state: paused;
}
</style>
