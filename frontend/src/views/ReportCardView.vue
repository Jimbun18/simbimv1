<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
import type { ReportCardDetail, SchoolProfileData } from '../components/report-card/types'
import ReportCardList from '../components/report-card/ReportCardList.vue'
import ReportCardEditor from '../components/report-card/ReportCardEditor.vue'
import ReportCardPrint from '../components/report-card/ReportCardPrint.vue'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

// Sub-menu periode: 'triwulan_1' | 'semester_1' | 'triwulan_3' | 'semester_2'
const activeSubMenu = computed(() => (route.query.periode as string) || 'triwulan_1')

// View state: 'list' | 'editor' | 'print'
const currentView = ref<'list' | 'editor' | 'print'>('list')
const printMode = ref<'print_tematik' | 'print_karakter' | 'print_raport'>('print_karakter')

const loading = ref(false)
const message = ref<{ type: 'success' | 'error' | 'warning'; text: string } | null>(null)

// Shared filters & dropdowns
const classes = ref<any[]>([])
const academicYears = ref<any[]>([])
const selectedClassId = ref<string | number>('')
const selectedSemester = ref<'Gasal' | 'Genap'>('Gasal')
const selectedAcademicYearId = ref<string | number>('')
// Default jenis rapor untuk Triwulan 1 adalah Karakter & Life Skill (17 Butir) sesuai template resmi SDI BIM
const selectedJenisRapor = ref<'karakter_lifeskill' | 'triwulan_tematik' | 'kurikulum_merdeka'>('karakter_lifeskill')
const selectedPeriode = ref<string>('Triwulan I')

// Editor & Print states
const activeReportCard = ref<ReportCardDetail | null>(null)
const schoolProfile = ref<SchoolProfileData>({
  nama_sekolah: 'SDI Bina Insan Mandiri Purwokerto',
  yayasan: 'Yayasan Bina Insan Mandiri',
  alamat: 'Jl. Wadas Kelir RT.04 RW.05 Kel. Karangklesem, Kec. Purwokerto Selatan, Kab. Banyumas 53144',
  npsn: '70036695',
  nss: '102030405060',
  akreditasi: '',
  telepon: '0281-635812',
  email: 'sdibim.pwt@gmail.com',
  website: 'https://sdibim.sch.id',
  logo_path: '/logo.png',
})

const showNotification = (text: string, type: 'success' | 'error' | 'warning' = 'success') => {
  message.value = { type, text }
  setTimeout(() => {
    message.value = null
  }, 4500)
}

// Watch sub-menu query parameter changes
watch(
  activeSubMenu,
  (newSubMenu) => {
    currentView.value = 'list'
    activeReportCard.value = null

    if (newSubMenu === 'triwulan_1') {
      selectedSemester.value = 'Gasal'
      selectedPeriode.value = 'Triwulan I'
      selectedJenisRapor.value = 'karakter_lifeskill'
    } else if (newSubMenu === 'semester_1') {
      selectedSemester.value = 'Gasal'
      selectedPeriode.value = 'Semester Gasal'
      selectedJenisRapor.value = 'kurikulum_merdeka'
    } else if (newSubMenu === 'triwulan_3') {
      selectedSemester.value = 'Genap'
      selectedPeriode.value = 'Triwulan III'
      selectedJenisRapor.value = 'karakter_lifeskill'
    } else if (newSubMenu === 'semester_2') {
      selectedSemester.value = 'Genap'
      selectedPeriode.value = 'Semester Genap'
      selectedJenisRapor.value = 'kurikulum_merdeka'
    }
  },
  { immediate: true }
)

// Fetch master classes & academic years & school profile
const fetchMasterData = async () => {
  try {
    const [classesRes, yearsRes, profileRes] = await Promise.allSettled([
      api.get('/classes?all=true'),
      api.get('/academic-years'),
      api.get('/school-profile'),
    ])

    if (yearsRes.status === 'fulfilled' && yearsRes.value.data.success) {
      academicYears.value = yearsRes.value.data.data
      const activeYear = academicYears.value.find((y: any) => y.is_aktif || y.status === 'aktif')
      if (activeYear) {
        selectedAcademicYearId.value = activeYear.id
      } else if (academicYears.value.length > 0) {
        selectedAcademicYearId.value = academicYears.value[0].id
      }
    }

    if (classesRes.status === 'fulfilled' && classesRes.value.data.success) {
      let list = classesRes.value.data.data

      // Restrict Wali Kelas to homeroom classes if not admin
      if (!auth.isAdmin && auth.isWaliKelas && auth.homeroomClasses.length > 0) {
        const myClassIds = auth.homeroomClasses.map((c: any) => c.id)
        const myClasses = list.filter((c: any) => myClassIds.includes(c.id))
        if (myClasses.length > 0) {
          list = myClasses
        }
      }

      classes.value = list
      if (classes.value.length > 0 && !selectedClassId.value) {
        selectedClassId.value = classes.value[0].id
      }
    }

    if (profileRes.status === 'fulfilled' && profileRes.value.data.success) {
      const p = profileRes.value.data.data
      if (p) {
        schoolProfile.value = {
          nama_sekolah: p.nama_sekolah || schoolProfile.value.nama_sekolah,
          yayasan: p.yayasan || schoolProfile.value.yayasan,
          alamat: p.alamat_lengkap || schoolProfile.value.alamat,
          npsn: p.npsn || schoolProfile.value.npsn,
          nss: p.nss || schoolProfile.value.nss,
          akreditasi: p.akreditasi || schoolProfile.value.akreditasi,
          telepon: p.telepon || schoolProfile.value.telepon,
          email: p.email || schoolProfile.value.email,
          website: p.website || schoolProfile.value.website,
          logo_path: p.logo_path || '/logo.png',
        }
      }
    }
  } catch (err: any) {
    showNotification('Gagal memuat data master: ' + (err.response?.data?.message || err.message), 'error')
  }
}

// Open Editor
const handleOpenEditor = async (studentId: number, reportCardId?: number | null) => {
  loading.value = true
  try {
    let res
    if (reportCardId) {
      res = await api.get(`/report-cards/${reportCardId}`)
    } else {
      res = await api.post('/report-cards/student', {
        student_id: studentId,
        academic_year_id: selectedAcademicYearId.value || undefined,
        semester: selectedSemester.value,
        jenis_rapor: selectedJenisRapor.value,
        periode: selectedPeriode.value,
      })
    }

    if (res.data.success) {
      const data = res.data.data.report_card || res.data.data
      activeReportCard.value = JSON.parse(JSON.stringify(data))
      if (res.data.data.school_profile) {
        schoolProfile.value = res.data.data.school_profile
      }
      currentView.value = 'editor'
    }
  } catch (err: any) {
    showNotification('Gagal memuat rapor siswa: ' + (err.response?.data?.message || err.message), 'error')
  } finally {
    loading.value = false
  }
}

// Open Print View
const handleOpenPrint = async (studentId: number, reportCardId: number | null) => {
  await handleOpenEditor(studentId, reportCardId)
  if (activeReportCard.value) {
    printMode.value = selectedJenisRapor.value === 'karakter_lifeskill' ? 'print_karakter' : 'print_raport'
    currentView.value = 'print'
  }
}

// Print trigger from inside Editor
const handleEditorPrint = () => {
  printMode.value = selectedJenisRapor.value === 'karakter_lifeskill' ? 'print_karakter' : 'print_raport'
  currentView.value = 'print'
}

const handleBackToList = () => {
  currentView.value = 'list'
  activeReportCard.value = null
}

const handleBackFromPrint = () => {
  currentView.value = 'list'
  activeReportCard.value = null
}

const handleReportSaved = (updated: ReportCardDetail) => {
  activeReportCard.value = updated
}

const navigateToTriwulan1 = () => {
  router.push('/rapor?periode=triwulan_1')
}

onMounted(() => {
  fetchMasterData()
})
</script>

<template>
  <div class="space-y-6">
    <!-- NOTIFICATION TOAST -->
    <transition
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="message"
        class="fixed top-5 right-5 z-50 max-w-md w-full shadow-2xl rounded-2xl p-4 flex items-center gap-3 border text-xs font-semibold backdrop-blur-md"
        :class="{
          'bg-emerald-500/90 text-white border-emerald-400': message.type === 'success',
          'bg-rose-500/90 text-white border-rose-400': message.type === 'error',
          'bg-amber-500/90 text-white border-amber-400': message.type === 'warning',
        }"
      >
        <span class="text-base">🔔</span>
        <div class="flex-1 leading-snug">{{ message.text }}</div>
        <button @click="message = null" class="text-white/80 hover:text-white font-black cursor-pointer">&times;</button>
      </div>
    </transition>

    <!-- KONDISI 1: JIKA SUB-MENU = TRIWULAN 1 (SEDANG FOKUS DIKERJAKAN) -->
    <template v-if="activeSubMenu === 'triwulan_1'">
      <!-- HEADER TRIWULAN 1 WITH 2 FORMAT SWITCHER PILLS -->
      <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-4 no-print print:hidden">
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center text-2xl shrink-0">
            📗
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-lg font-black text-slate-900 tracking-tight">Rapor Triwulan 1</h1>
              <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded-full uppercase tracking-wider">
                Pertengahan Semester 1 (Gasal)
              </span>
            </div>
            <p class="text-xs text-slate-500 line-clamp-1">
              Laporan Perkembangan Penguatan Karakter & Life Skill (17 Butir Penilaian) pertengahan semester.
            </p>
          </div>
        </div>

        <!-- BACK TO LIST BUTTON IF IN EDITOR / PRINT -->
        <div v-if="currentView !== 'list'" class="flex items-center gap-2 shrink-0">
          <button
            @click="handleBackToList"
            class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Kembali ke Daftar Rapor</span>
          </button>
        </div>

        <!-- FORMAT BADGE INFO -->
        <div v-else class="flex items-center gap-2 shrink-0">
          <div class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-50 text-emerald-800 border border-emerald-200/80 rounded-xl text-xs font-bold shadow-xs">
            <span>📙</span>
            <span>Penguatan Karakter & Life Skill (17 Butir)</span>
          </div>
        </div>
      </div>

      <!-- LOADING OVERLAY -->
      <div v-if="loading" class="fixed inset-0 z-40 bg-white/60 backdrop-blur-xs flex items-center justify-center">
        <div class="bg-white p-4 rounded-2xl shadow-xl border border-slate-200 flex items-center gap-3">
          <svg class="animate-spin h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <span class="text-xs font-bold text-slate-700">Memuat data rapor Triwulan 1...</span>
        </div>
      </div>

      <!-- MAIN BODY TRIWULAN 1 -->
      <main>
        <!-- VIEW 1: STUDENT LIST & CLASS OVERVIEW -->
        <ReportCardList
          v-if="currentView === 'list'"
          current-sub-menu="triwulan_1"
          :jenis-rapor="selectedJenisRapor"
          :classes="classes"
          :academic-years="academicYears"
          v-model:selected-class-id="selectedClassId"
          v-model:selected-academic-year-id="selectedAcademicYearId"
          v-model:selected-semester="selectedSemester"
          v-model:selected-periode="selectedPeriode"
          @open-editor="handleOpenEditor"
          @open-print="handleOpenPrint"
          @notify="showNotification"
        />

        <!-- VIEW 2: MULTI-TAB REPORT CARD EDITOR -->
        <ReportCardEditor
          v-else-if="currentView === 'editor' && activeReportCard"
          :report-card="activeReportCard"
          @back="handleBackToList"
          @print="handleEditorPrint"
          @saved="handleReportSaved"
          @notify="showNotification"
        />

        <!-- VIEW 3: PRINT VIEWS (TEMATIK & KARAKTER) -->
        <ReportCardPrint
          v-else-if="currentView === 'print' && activeReportCard"
          :mode="printMode"
          :active-report-card="activeReportCard"
          :school-profile="schoolProfile"
          @back="handleBackFromPrint"
        />
      </main>
    </template>

    <!-- KONDISI 2: SUB-MENU PERIODE LAIN (SEMESTER 1, TRIWULAN 3, SEMESTER 2) -->
    <template v-else>
      <div class="bg-white rounded-3xl p-8 sm:p-12 border border-slate-200/80 shadow-xs text-center max-w-2xl mx-auto space-y-6">
        <div class="w-16 h-16 rounded-3xl bg-amber-500/10 text-amber-600 flex items-center justify-center text-3xl mx-auto">
          ⏳
        </div>

        <div class="space-y-2">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider"
            :class="{
              'bg-blue-100 text-blue-800': activeSubMenu === 'semester_1',
              'bg-purple-100 text-purple-800': activeSubMenu === 'triwulan_3',
              'bg-emerald-100 text-emerald-800': activeSubMenu === 'semester_2',
            }"
          >
            <span>
              {{ 
                activeSubMenu === 'semester_1' ? 'Periode: Semester 1 (Akhir Gasal)' : 
                activeSubMenu === 'triwulan_3' ? 'Periode: Triwulan 3 (Tengah Genap)' : 
                'Periode: Semester 2 (Akhir Genap / Kenaikan Kelas)' 
              }}
            </span>
          </div>

          <h2 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">
            {{ 
              activeSubMenu === 'semester_1' ? 'Rapor Semester 1 Sedang Dipersiapkan' : 
              activeSubMenu === 'triwulan_3' ? 'Rapor Triwulan 3 Dijadwalkan Semester Genap' : 
              'Rapor Semester 2 Dijadwalkan Akhir Tahun Ajaran' 
            }}
          </h2>

          <p class="text-xs sm:text-sm text-slate-500 max-w-lg mx-auto leading-relaxed">
            <template v-if="activeSubMenu === 'semester_1'">
              Sesuai alur kalender akademik SDI Bina Insan Mandiri, saat ini sistem memprioritaskan penuntasan 
              <strong>Rapor Triwulan 1 (Pertengahan Semester Gasal)</strong>. Rapor Semester 1 (Kurikulum Merdeka 18 Mata Pelajaran & Ekstrakurikuler) akan dibuka menjelang akhir semester Gasal.
            </template>
            <template v-else-if="activeSubMenu === 'triwulan_3'">
              Rapor Triwulan 3 merupakan penilaian pertengahan semester Genap yang akan dibuka pada jadwal pelaksanaan Triwulan 3.
            </template>
            <template v-else>
              Rapor Semester 2 merupakan evaluasi akhir tahun ajaran penentu kelulusan dan kenaikan kelas, yang akan dibuka pada akhir semester Genap.
            </template>
          </p>
        </div>

        <div class="pt-2">
          <button
            type="button"
            @click="navigateToTriwulan1"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition-all cursor-pointer"
          >
            <span>📗</span>
            <span>Buka Rapor Triwulan 1 Sekarang</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
          </button>
        </div>
      </div>
    </template>
  </div>
</template>
