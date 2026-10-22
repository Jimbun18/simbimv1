<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
import type { ReportCardDetail } from '../components/report-card/types'
import ReportCardList from '../components/report-card/ReportCardList.vue'
import ReportCardEditor from '../components/report-card/ReportCardEditor.vue'
import ReportCardPrint from '../components/report-card/ReportCardPrint.vue'

const auth = useAuthStore()

// View state: 'list' | 'editor' | 'print'
const currentView = ref<'list' | 'editor' | 'print'>('list')
const printMode = ref<'print_tematik' | 'print_karakter' | 'print_raport'>('print_raport')

const loading = ref(false)
const message = ref<{ type: 'success' | 'error' | 'warning'; text: string } | null>(null)

// Shared filters & dropdowns
const classes = ref<any[]>([])
const academicYears = ref<any[]>([])
const selectedClassId = ref<string | number>('')
const selectedSemester = ref<'Gasal' | 'Genap'>('Gasal')
const selectedAcademicYearId = ref<string | number>('')
const selectedJenisRapor = ref<'kurikulum_merdeka' | 'triwulan_tematik' | 'karakter_lifeskill'>('kurikulum_merdeka')
const selectedPeriode = ref<string>('Semester Gasal')

// Editor & Print states
const activeReportCard = ref<ReportCardDetail | null>(null)
const schoolProfile = ref<any>({
  nama_sekolah: 'SD Islam Bina Insan Mandiri Purwokerto',
  alamat: 'Jl. Wadas Kelir RT.04 RW.05 Kel. Karangklesem, Kec. Purwokerto Selatan, Kab. Banyumas 53144',
  npsn: '70036695',
  logo_path: null,
})

const showNotification = (text: string, type: 'success' | 'error' | 'warning' = 'success') => {
  message.value = { type, text }
  setTimeout(() => {
    message.value = null
  }, 4500)
}

// Watchers for jenis rapor & semester sync
watch(selectedJenisRapor, (newVal) => {
  if (newVal === 'kurikulum_merdeka') {
    selectedPeriode.value = selectedSemester.value === 'Gasal' ? 'Semester Gasal' : 'Semester Genap'
  } else {
    selectedPeriode.value = 'Triwulan I'
  }
  if (currentView.value !== 'list') {
    currentView.value = 'list'
    activeReportCard.value = null
  }
})

watch(selectedSemester, (newVal) => {
  if (selectedJenisRapor.value === 'kurikulum_merdeka') {
    selectedPeriode.value = newVal === 'Gasal' ? 'Semester Gasal' : 'Semester Genap'
  }
})

// Fetch master classes & academic years
const fetchMasterData = async () => {
  try {
    const [classesRes, yearsRes] = await Promise.all([
      api.get('/classes'),
      api.get('/academic-years'),
    ])

    if (classesRes.data.success) {
      let list = classesRes.data.data

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

    if (yearsRes.data.success) {
      academicYears.value = yearsRes.data.data
      const activeYear = academicYears.value.find((y: any) => y.is_aktif)
      if (activeYear) {
        selectedAcademicYearId.value = activeYear.id
      } else if (academicYears.value.length > 0) {
        selectedAcademicYearId.value = academicYears.value[0].id
      }
    }
  } catch (err: any) {
    showNotification('Gagal memuat data master kelas & tahun ajaran: ' + (err.response?.data?.message || err.message), 'error')
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
    if (selectedJenisRapor.value === 'triwulan_tematik') {
      printMode.value = 'print_tematik'
    } else if (selectedJenisRapor.value === 'karakter_lifeskill') {
      printMode.value = 'print_karakter'
    } else {
      printMode.value = 'print_raport'
    }
    currentView.value = 'print'
  }
}

// Print trigger from inside Editor
const handleEditorPrint = () => {
  if (selectedJenisRapor.value === 'triwulan_tematik') {
    printMode.value = 'print_tematik'
  } else if (selectedJenisRapor.value === 'karakter_lifeskill') {
    printMode.value = 'print_karakter'
  } else {
    printMode.value = 'print_raport'
  }
  currentView.value = 'print'
}

const handleBackToList = () => {
  currentView.value = 'list'
  activeReportCard.value = null
}

const handleBackFromPrint = () => {
  if (activeReportCard.value) {
    currentView.value = 'editor'
  } else {
    currentView.value = 'list'
  }
}

const handleReportSaved = (updated: ReportCardDetail) => {
  activeReportCard.value = updated
}

onMounted(() => {
  fetchMasterData()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50/80 pb-20 font-sans antialiased text-slate-800">
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

    <!-- TOP HEADER -->
    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-30 shadow-xs print:hidden">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between py-4 gap-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-emerald-600 flex items-center justify-center text-white shadow-md shadow-emerald-600/20 text-lg font-black shrink-0">
              📑
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h1 class="text-lg font-black tracking-tight text-slate-900">
                  Manajemen & Pengolahan Rapor Siswa
                </h1>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">
                  3 Format Rapor
                </span>
              </div>
              <p class="text-xs text-slate-500 font-medium">
                Alur pengisian oleh Wali Kelas & validasi resmi oleh Kepala Sekolah SDI Bina Insan Mandiri.
              </p>
            </div>
          </div>

          <!-- BACK TO LIST BUTTON IF IN EDITOR / PRINT -->
          <div v-if="currentView !== 'list'" class="flex items-center gap-2 self-start sm:self-auto">
            <button
              @click="handleBackToList"
              class="px-3.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              <span>Kembali ke Daftar Siswa</span>
            </button>
          </div>
        </div>

        <!-- 3 REPORT CARD TABS SWITCHER (TOP NAVIGATION) -->
        <div class="flex border-b border-slate-200/80 gap-2 overflow-x-auto scrollbar-none pt-1">
          <button
            type="button"
            @click="selectedJenisRapor = 'kurikulum_merdeka'"
            class="px-4 py-2.5 text-xs font-bold transition-all border-b-2 flex items-center gap-2 whitespace-nowrap cursor-pointer"
            :class="selectedJenisRapor === 'kurikulum_merdeka'
              ? 'border-emerald-600 text-emerald-800 bg-emerald-50/50'
              : 'border-transparent text-slate-500 hover:text-slate-900 hover:bg-slate-50'"
          >
            <span class="w-2 h-2 rounded-full" :class="selectedJenisRapor === 'kurikulum_merdeka' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
            <span>📘 Rapor Kurikulum Merdeka (Intra & P5)</span>
          </button>

          <button
            type="button"
            @click="selectedJenisRapor = 'triwulan_tematik'"
            class="px-4 py-2.5 text-xs font-bold transition-all border-b-2 flex items-center gap-2 whitespace-nowrap cursor-pointer"
            :class="selectedJenisRapor === 'triwulan_tematik'
              ? 'border-emerald-600 text-emerald-800 bg-emerald-50/50'
              : 'border-transparent text-slate-500 hover:text-slate-900 hover:bg-slate-50'"
          >
            <span class="w-2 h-2 rounded-full" :class="selectedJenisRapor === 'triwulan_tematik' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
            <span>📗 Rapor Triwulan Tematik BIM (5 Tema)</span>
          </button>

          <button
            type="button"
            @click="selectedJenisRapor = 'karakter_lifeskill'"
            class="px-4 py-2.5 text-xs font-bold transition-all border-b-2 flex items-center gap-2 whitespace-nowrap cursor-pointer"
            :class="selectedJenisRapor === 'karakter_lifeskill'
              ? 'border-emerald-600 text-emerald-800 bg-emerald-50/50'
              : 'border-transparent text-slate-500 hover:text-slate-900 hover:bg-slate-50'"
          >
            <span class="w-2 h-2 rounded-full" :class="selectedJenisRapor === 'karakter_lifeskill' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
            <span>📙 Laporan Karakter & Life Skill (Triwulan)</span>
          </button>
        </div>
      </div>
    </header>

    <!-- LOADING OVERLAY -->
    <div v-if="loading" class="fixed inset-0 z-40 bg-white/60 backdrop-blur-xs flex items-center justify-center">
      <div class="bg-white p-4 rounded-2xl shadow-xl border border-slate-200 flex items-center gap-3">
        <svg class="animate-spin h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
        <span class="text-xs font-bold text-slate-700">Memuat data rapor siswa...</span>
      </div>
    </div>

    <!-- MAIN BODY -->
    <main>
      <!-- VIEW 1: STUDENT LIST & CLASS OVERVIEW -->
      <ReportCardList
        v-if="currentView === 'list'"
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

      <!-- VIEW 3: PRINT VIEWS (3 FORMATS) -->
      <ReportCardPrint
        v-else-if="currentView === 'print' && activeReportCard"
        :mode="printMode"
        :active-report-card="activeReportCard"
        :school-profile="schoolProfile"
        @back="handleBackFromPrint"
      />
    </main>
  </div>
</template>
