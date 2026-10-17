<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'

interface SubjectGrade {
  id?: number
  nama_mapel: string
  kelompok: string
  nilai_akhir: number | null | string
  capaian_kompetensi: string | null
  urutan: number
}

interface Extracurricular {
  id?: number
  kegiatan: string
  predikat: string
  keterangan: string | null
  urutan: number
}

interface ReportCardDetail {
  id: number
  student_id: number
  class_id: number
  academic_year_id: number
  semester: string
  fase: string
  tempat_tanggal: string
  tanggal_rapor?: string | null
  wali_kelas_nama?: string | null
  wali_kelas_nip?: string | null
  kepala_sekolah_nama?: string | null
  kepala_sekolah_nip?: string | null
  kokurikuler_tema?: string | null
  kokurikuler_dimensi?: string | null
  kokurikuler_target?: string | null
  kokurikuler_capaian?: string | null
  kokurikuler_deskripsi?: string | null
  sakit: number
  izin: number
  alpa: number
  catatan_wali_kelas?: string | null
  tanggapan_orang_tua?: string | null
  status: 'draft' | 'final'
  student: {
    id: number
    nis: string
    nisn: string
    nama_lengkap: string
    jenis_kelamin: string
    tempat_lahir?: string
    tgl_lahir?: string
    alamat_jalan?: string
    desa_kelurahan?: string
    kecamatan?: string
    kab_kota?: string
    agama?: any
    religion?: { nama: string }
    asal_sekolah_tk?: string
    tgl_masuk?: string
    foto_path?: string | null
    guardians?: any[]
  }
  school_class?: {
    id: number
    nama: string
    tingkat: number
  }
  academic_year?: {
    id: number
    nama: string
  }
  grades: SubjectGrade[]
  extracurriculars: Extracurricular[]
}

// View Modes: 'list' | 'editor' | 'print_raport' | 'print_identitas'
const currentView = ref<'list' | 'editor' | 'print_raport' | 'print_identitas'>('list')

const loading = ref(false)
const saving = ref(false)
const syncing = ref(false)
const message = ref<{ type: 'success' | 'error' | 'warning'; text: string } | null>(null)

// Filters
const classes = ref<any[]>([])
const academicYears = ref<any[]>([])
const selectedClassId = ref<string | number>('')
const selectedSemester = ref<'Gasal' | 'Genap'>('Gasal')
const selectedAcademicYearId = ref<string | number>('')
const searchQuery = ref('')

// Class Report Overview Data
const classReportData = ref<any | null>(null)

// Editor State
const activeEditorTab = ref<'intra' | 'koku' | 'ekstra' | 'notes'>('intra')
const activeReportCard = ref<ReportCardDetail | null>(null)
const schoolProfile = ref<any>({
  nama_sekolah: 'SD Islam Bina Insan Mandiri',
  alamat: 'Jl. Wadas Kelir RT.04 RW.05 Kel. Karangklesem, Kec. Purwokerto Selatan, Kab. Banyumas 53144',
  npsn: '70036695',
  logo_path: null,
})

// File import ref
const fileInput = ref<HTMLInputElement | null>(null)
const importingExcel = ref(false)

const showNotification = (text: string, type: 'success' | 'error' | 'warning' = 'success') => {
  message.value = { type, text }
  setTimeout(() => {
    message.value = null
  }, 4500)
}

// Profil Lulusan Dimensi Options
const dimensiOptions = [
  'Keimanan dan Ketakwaan',
  'Kewargaan',
  'Penalaran Kritis',
  'Kreativitas',
  'Kolaborasi',
  'Kemandirian',
  'Kesehatan',
  'Komunikasi',
]

// Computed list of filtered students in class
const filteredStudents = computed(() => {
  if (!classReportData.value?.students) return []
  if (!searchQuery.value) return classReportData.value.students

  const q = searchQuery.value.toLowerCase().trim()
  return classReportData.value.students.filter((st: any) =>
    st.nama_lengkap?.toLowerCase().includes(q) ||
    st.nis?.toLowerCase().includes(q) ||
    st.nisn?.toLowerCase().includes(q)
  )
})

// Average score in editor
const averageScore = computed(() => {
  if (!activeReportCard.value?.grades?.length) return 0
  const validGrades = activeReportCard.value.grades.filter(
    (g) => g.nilai_akhir !== null && g.nilai_akhir !== '' && !isNaN(Number(g.nilai_akhir))
  )
  if (!validGrades.length) return 0
  const total = validGrades.reduce((sum, g) => sum + Number(g.nilai_akhir), 0)
  return Math.round((total / validGrades.length) * 10) / 10
})

// Fetch initial dropdowns
const fetchMasterData = async () => {
  try {
    const [classesRes, yearsRes] = await Promise.all([
      api.get('/classes'),
      api.get('/academic-years'),
    ])

    if (classesRes.data.success) {
      classes.value = classesRes.data.data
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

    if (selectedClassId.value) {
      await loadClassReports()
    }
  } catch (err: any) {
    showNotification('Gagal memuat data master kelas & tahun ajaran: ' + (err.response?.data?.message || err.message), 'error')
  }
}

// Load class report card overview
const loadClassReports = async () => {
  if (!selectedClassId.value) return
  loading.value = true
  try {
    const res = await api.get('/report-cards', {
      params: {
        class_id: selectedClassId.value,
        academic_year_id: selectedAcademicYearId.value || undefined,
        semester: selectedSemester.value,
      },
    })
    if (res.data.success) {
      classReportData.value = res.data.data
    }
  } catch (err: any) {
    showNotification('Gagal mengambil data rapor kelas: ' + (err.response?.data?.message || err.message), 'error')
  } finally {
    loading.value = false
  }
}

// Batch Initialize Class
const handleInitializeClass = async (overwrite = false) => {
  if (!selectedClassId.value) return
  if (!confirm(overwrite
    ? 'Perbarui seluruh format rapor untuk semua siswa di kelas ini? Nilai yang sudah ada tidak akan hilang, informasi pengesahan dan mapel standar akan disinkronkan.'
    : 'Inisialisasi draf rapor untuk seluruh siswa di kelas ini dengan 18 mata pelajaran standar SDI Bina Insan Mandiri?'
  )) {
    return
  }

  loading.value = true
  try {
    const res = await api.post('/report-cards/initialize-class', {
      class_id: selectedClassId.value,
      academic_year_id: selectedAcademicYearId.value || undefined,
      semester: selectedSemester.value,
      overwrite_existing: overwrite,
    })
    if (res.data.success) {
      showNotification(res.data.message, 'success')
      await loadClassReports()
    }
  } catch (err: any) {
    showNotification('Inisialisasi gagal: ' + (err.response?.data?.message || err.message), 'error')
  } finally {
    loading.value = false
  }
}

// Open Editor for a Student
const openEditor = async (studentId: number, reportCardId?: number | null) => {
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
      })
    }

    if (res.data.success) {
      const data = res.data.data.report_card || res.data.data
      activeReportCard.value = JSON.parse(JSON.stringify(data))
      if (res.data.data.school_profile) {
        schoolProfile.value = res.data.data.school_profile
      }
      activeEditorTab.value = 'intra'
      currentView.value = 'editor'
      window.scrollTo({ top: 0, behavior: 'smooth' })
    }
  } catch (err: any) {
    showNotification('Gagal membuka rapor siswa: ' + (err.response?.data?.message || err.message), 'error')
  } finally {
    loading.value = false
  }
}

// Save Active Report Card
const saveReportCard = async (statusOverride?: 'draft' | 'final') => {
  if (!activeReportCard.value) return
  saving.value = true
  try {
    if (statusOverride) {
      activeReportCard.value.status = statusOverride
    }

    const payload = {
      ...activeReportCard.value,
      grades: activeReportCard.value.grades,
      extracurriculars: activeReportCard.value.extracurriculars,
    }

    const res = await api.put(`/report-cards/${activeReportCard.value.id}`, payload)
    if (res.data.success) {
      showNotification(res.data.message, 'success')
      activeReportCard.value = JSON.parse(JSON.stringify(res.data.data))
      await loadClassReports()
    }
  } catch (err: any) {
    showNotification('Gagal menyimpan rapor: ' + (err.response?.data?.message || err.message), 'error')
  } finally {
    saving.value = false
  }
}

// Sync Attendance for Student
const syncAttendanceCurrent = async () => {
  if (!activeReportCard.value) return
  syncing.value = true
  try {
    const res = await api.post(`/report-cards/${activeReportCard.value.id}/sync-attendance`)
    if (res.data.success) {
      activeReportCard.value.sakit = res.data.data.sakit
      activeReportCard.value.izin = res.data.data.izin
      activeReportCard.value.alpa = res.data.data.alpa
      showNotification(res.data.message, 'success')
    }
  } catch (err: any) {
    showNotification('Gagal menyinkronkan presensi: ' + (err.response?.data?.message || err.message), 'error')
  } finally {
    syncing.value = false
  }
}

// Smart Auto-Generators
const generateCompetencyDescription = (grade: SubjectGrade) => {
  const score = Number(grade.nilai_akhir)
  const mapel = grade.nama_mapel
  if (isNaN(score) || grade.nilai_akhir === null || grade.nilai_akhir === '') {
    grade.capaian_kompetensi = `Menunjukkan partisipasi aktif dalam pembelajaran ${mapel}.`
    return
  }

  if (score >= 90) {
    grade.capaian_kompetensi = `Menunjukkan penguasaan materi ${mapel} yang sangat unggul dan istimewa dalam penerapan konsep serta pemecahan masalah.`
  } else if (score >= 80) {
    grade.capaian_kompetensi = `Menguasai materi ${mapel} dengan kemampuan baik dalam pemahaman konsep dan penerapannya.`
  } else if (score >= 70) {
    grade.capaian_kompetensi = `Cukup menguasai materi ${mapel}, perlu peningkatan konsistensi dan latihan berkelanjutan.`
  } else {
    grade.capaian_kompetensi = `Perlu bimbingan dan pendampingan intensif untuk mencapai ketuntasan materi ${mapel}.`
  }
}

const generateAllCompetencies = () => {
  if (!activeReportCard.value?.grades) return
  activeReportCard.value.grades.forEach((g) => {
    if (!g.capaian_kompetensi || g.capaian_kompetensi.trim() === '' || g.capaian_kompetensi.startsWith('INI DIISI')) {
      generateCompetencyDescription(g)
    }
  })
  showNotification('Deskripsi capaian kompetensi berhasil digenerate otomatis berdasarkan nilai.', 'success')
}

const generateKokurikulerNarrative = () => {
  if (!activeReportCard.value) return
  const nama = activeReportCard.value.student?.nama_lengkap || 'Ananda'
  const tema = activeReportCard.value.kokurikuler_tema || 'Sampahku bersih lingkunganku sehat'
  const dimensi = activeReportCard.value.kokurikuler_dimensi || 'Kolaborasi dan Kemandirian'
  const target = activeReportCard.value.kokurikuler_target || 'Peserta didik mampu mengolah sampah dan peduli lingkungan'
  const capaian = activeReportCard.value.kokurikuler_capaian || 'Berkembang Sesuai Harapan'

  activeReportCard.value.kokurikuler_deskripsi = `${nama} telah menunjukkan sikap aktif dan antusias dalam tema "${tema}". Berdasarkan asesmen asesmen kokurikuler, ananda menunjukkan capaian ${capaian} terutama dalam dimensi ${dimensi}. Kegiatan ini mendukung target berupa: ${target}.`
  showNotification('Narasi kokurikuler berhasil disusun otomatis.', 'success')
}

// Add/Remove Grade Row
const addSubjectRow = () => {
  if (!activeReportCard.value) return
  const nextUrutan = (activeReportCard.value.grades.length || 0) + 1
  activeReportCard.value.grades.push({
    nama_mapel: '',
    kelompok: 'Umum',
    nilai_akhir: null,
    capaian_kompetensi: '',
    urutan: nextUrutan,
  })
}

const removeSubjectRow = (index: number) => {
  if (!activeReportCard.value) return
  if (confirm('Hapus mata pelajaran ini dari daftar rapor?')) {
    activeReportCard.value.grades.splice(index, 1)
  }
}

// Add/Remove Extracurricular Row
const addExtracurricularRow = () => {
  if (!activeReportCard.value) return
  const nextUrutan = (activeReportCard.value.extracurriculars.length || 0) + 1
  activeReportCard.value.extracurriculars.push({
    kegiatan: '',
    predikat: 'Baik',
    keterangan: '',
    urutan: nextUrutan,
  })
}

const removeExtracurricularRow = (index: number) => {
  if (!activeReportCard.value) return
  activeReportCard.value.extracurriculars.splice(index, 1)
}

// Print Views
const openPrintRaport = async (studentId: number, reportCardId?: number) => {
  await openEditor(studentId, reportCardId)
  currentView.value = 'print_raport'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const openPrintIdentitas = async (studentId: number, reportCardId?: number) => {
  await openEditor(studentId, reportCardId)
  currentView.value = 'print_identitas'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handlePrintBrowser = () => {
  window.print()
}

// Excel Export & Import
const handleExportExcel = () => {
  if (!selectedClassId.value) return
  const url = `/api/v1/report-cards/export-excel?class_id=${selectedClassId.value}&semester=${selectedSemester.value}&academic_year_id=${selectedAcademicYearId.value || ''}`
  window.open(url, '_blank')
}

const triggerFileInput = () => {
  fileInput.value?.click()
}

const handleFileUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  if (!confirm(`Import nilai rapor dari file "${file.name}" untuk kelas yang dipilih?`)) {
    target.value = ''
    return
  }

  const formData = new FormData()
  formData.append('file', file)
  formData.append('class_id', String(selectedClassId.value))
  formData.append('semester', selectedSemester.value)
  if (selectedAcademicYearId.value) {
    formData.append('academic_year_id', String(selectedAcademicYearId.value))
  }

  importingExcel.value = true
  try {
    const res = await api.post('/report-cards/import-excel', formData)
    if (res.data.success) {
      showNotification(res.data.message, 'success')
      await loadClassReports()
    }
  } catch (err: any) {
    showNotification('Gagal mengimpor Excel: ' + (err.response?.data?.message || err.message), 'error')
  } finally {
    importingExcel.value = false
    target.value = ''
  }
}

onMounted(() => {
  fetchMasterData()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-800 pb-16">
    <!-- Toast Notification -->
    <div
      v-if="message"
      class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl text-sm font-semibold transition-all transform animate-bounce"
      :class="{
        'bg-emerald-600 text-white shadow-emerald-600/30': message.type === 'success',
        'bg-rose-600 text-white shadow-rose-600/30': message.type === 'error',
        'bg-amber-500 text-white shadow-amber-500/30': message.type === 'warning'
      }"
    >
      <span>{{ message.text }}</span>
      <button @click="message = null" class="ml-2 text-white/80 hover:text-white">&times;</button>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 1: DAFTAR RAPOR KELAS (LIST OVERVIEW) -->
    <!-- ========================================================================= -->
    <div v-if="currentView === 'list'" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 space-y-6">
      <!-- Header Banner -->
      <div class="relative overflow-hidden bg-gradient-to-br from-emerald-800 via-emerald-700 to-teal-800 rounded-3xl p-6 sm:p-8 text-white shadow-xl shadow-emerald-950/10">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="space-y-2">
            <div class="flex items-center gap-2">
              <span class="px-3 py-1 bg-emerald-500/30 backdrop-blur-md rounded-full text-xs font-bold tracking-wider uppercase border border-emerald-300/30 text-emerald-100">
                Kurikulum Merdeka & Deep Learning
              </span>
              <span class="px-2.5 py-1 bg-amber-400 text-amber-950 rounded-full text-[10px] font-extrabold uppercase shadow-sm">
                Akses Khusus Admin & Kepala Sekolah
              </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
              E-Rapor & Buku Hasil Belajar Siswa
            </h1>
            <p class="text-emerald-100/90 text-sm max-w-2xl leading-relaxed">
              Sistem database rapor terpadu SDI Bina Insan Mandiri. Pengolahan nilai intrakurikuler (18 mapel), kokurikuler P5, ekstrakurikuler, dan rekapitulasi kehadiran siap cetak.
            </p>
          </div>

          <!-- Quick Action Buttons on Banner -->
          <div class="flex flex-wrap items-center gap-2.5">
            <button
              @click="handleInitializeClass(false)"
              :disabled="loading"
              class="px-4 py-2.5 bg-white text-emerald-900 rounded-xl font-bold text-xs hover:bg-emerald-50 active:scale-95 transition-all shadow-md flex items-center gap-2"
              title="Buat draf rapor untuk semua siswa di rombel ini"
            >
              <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Inisialisasi Rapor Kelas
            </button>

            <button
              @click="handleExportExcel"
              :disabled="loading || !selectedClassId"
              class="px-3.5 py-2.5 bg-emerald-900/60 hover:bg-emerald-900/80 text-white rounded-xl font-bold text-xs transition-all border border-emerald-400/30 flex items-center gap-2"
              title="Export database rapor ke Excel format SDI BIM"
            >
              <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              Unduh Excel Rapor
            </button>

            <button
              @click="triggerFileInput"
              :disabled="importingExcel || loading"
              class="px-3.5 py-2.5 bg-emerald-900/60 hover:bg-emerald-900/80 text-white rounded-xl font-bold text-xs transition-all border border-emerald-400/30 flex items-center gap-2"
              title="Unggah spreadsheet nilai"
            >
              <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
              </svg>
              <span>{{ importingExcel ? 'Mengunggah...' : 'Import Nilai' }}</span>
            </button>
            <input
              ref="fileInput"
              type="file"
              accept=".xlsx,.xls"
              class="hidden"
              @change="handleFileUpload"
            />
          </div>
        </div>
      </div>

      <!-- Filters & Control Bar -->
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200/80 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
          <!-- Pilih Rombel -->
          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Rombel / Kelas</label>
            <select
              v-model="selectedClassId"
              @change="loadClassReports"
              class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            >
              <option v-for="c in classes" :key="c.id" :value="c.id">
                {{ c.nama }} (Tingkat {{ c.tingkat }})
              </option>
            </select>
          </div>

          <!-- Pilih Semester -->
          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Semester</label>
            <select
              v-model="selectedSemester"
              @change="loadClassReports"
              class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            >
              <option value="Gasal">Semester Gasal (Ganjil)</option>
              <option value="Genap">Semester Genap</option>
            </select>
          </div>

          <!-- Tahun Ajaran -->
          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Tahun Ajaran</label>
            <select
              v-model="selectedAcademicYearId"
              @change="loadClassReports"
              class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            >
              <option v-for="y in academicYears" :key="y.id" :value="y.id">
                {{ y.nama }} {{ y.is_aktif ? '(Aktif)' : '' }}
              </option>
            </select>
          </div>
        </div>

        <!-- Search Bar -->
        <div class="w-full md:w-72">
          <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Cari Siswa</label>
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari nama, NISN, atau NIS..."
              class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            />
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Quick KPI Stats Cards -->
      <div v-if="classReportData" class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Siswa Rombel</div>
          <div class="text-2xl font-black text-slate-800 mt-1">{{ classReportData.total_students || 0 }}</div>
          <div class="text-[10px] text-emerald-600 font-semibold mt-1">Kelas {{ classReportData.class?.nama }} • Fase {{ classReportData.class?.fase }}</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Rapor Selesai (Final)</div>
          <div class="text-2xl font-black text-emerald-600 mt-1">{{ classReportData.completed_count || 0 }}</div>
          <div class="text-[10px] text-slate-500 font-medium mt-1">Siap dicetak resmi</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Dalam Proses (Draft)</div>
          <div class="text-2xl font-black text-amber-500 mt-1">{{ classReportData.draft_count || 0 }}</div>
          <div class="text-[10px] text-slate-500 font-medium mt-1">Pengisian nilai & catatan</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Wali Kelas Pengampu</div>
          <div class="text-sm font-extrabold text-slate-800 mt-1 truncate" :title="classReportData.class?.wali_kelas">
            {{ classReportData.class?.wali_kelas || '-' }}
          </div>
          <div class="text-[10px] text-slate-400 font-mono mt-1">NIP: {{ classReportData.class?.wali_kelas_nip || '-' }}</div>
        </div>
      </div>

      <!-- Student Rapor Table -->
      <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
          <div class="font-extrabold text-sm text-slate-800">
            Daftar Nilai & Rapor Siswa — {{ classReportData?.class?.nama }} ({{ selectedSemester }})
          </div>
          <div class="text-xs text-slate-500">
            Menampilkan <strong class="text-slate-800">{{ filteredStudents.length }}</strong> siswa
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
              <tr>
                <th class="py-3.5 px-4 w-12 text-center">No</th>
                <th class="py-3.5 px-4">Nama Lengkap & NISN</th>
                <th class="py-3.5 px-4 text-center">JK</th>
                <th class="py-3.5 px-4 text-center">Kelengkapan Mapel</th>
                <th class="py-3.5 px-4 text-center">Rata-Rata</th>
                <th class="py-3.5 px-4 text-center">Presensi (S/I/A)</th>
                <th class="py-3.5 px-4 text-center">Status</th>
                <th class="py-3.5 px-4 text-right">Aksi Manajemen</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-if="loading" class="animate-pulse">
                <td colspan="8" class="py-8 text-center text-slate-400">
                  Memuat data rapor siswa...
                </td>
              </tr>
              <tr v-else-if="filteredStudents.length === 0">
                <td colspan="8" class="py-12 text-center">
                  <div class="text-slate-400 font-semibold text-sm">Tidak ada siswa yang ditemukan di kelas ini.</div>
                  <div class="text-slate-400 text-xs mt-1">Gunakan tombol "Inisialisasi Rapor Kelas" untuk membuat draf awal.</div>
                </td>
              </tr>
              <tr
                v-for="st in filteredStudents"
                :key="st.student_id"
                class="hover:bg-slate-50/80 transition-colors"
              >
                <td class="py-3.5 px-4 text-center font-bold text-slate-400">{{ st.no_urut }}</td>
                <td class="py-3.5 px-4">
                  <div class="font-extrabold text-slate-900">{{ st.nama_lengkap }}</div>
                  <div class="text-[10px] text-slate-400 font-mono">NISN: {{ st.nisn || '-' }} • NIS: {{ st.nis || '-' }}</div>
                </td>
                <td class="py-3.5 px-4 text-center">
                  <span
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="st.jenis_kelamin === 'L' ? 'bg-sky-100 text-sky-800' : 'bg-pink-100 text-pink-800'"
                  >
                    {{ st.jenis_kelamin === 'L' ? 'L' : 'P' }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center">
                  <span
                    v-if="st.total_mapel > 0"
                    class="px-2 py-1 rounded-lg text-[10px] font-bold"
                    :class="st.mapel_terisi === st.total_mapel ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                  >
                    {{ st.mapel_terisi }} / {{ st.total_mapel }} Mapel
                  </span>
                  <span v-else class="text-slate-400 text-[10px]">Belum dibuat</span>
                </td>
                <td class="py-3.5 px-4 text-center">
                  <span v-if="st.rata_rata" class="font-extrabold text-slate-800 text-xs px-2 py-0.5 rounded-md bg-slate-100">
                    {{ st.rata_rata }}
                  </span>
                  <span v-else class="text-slate-400">-</span>
                </td>
                <td class="py-3.5 px-4 text-center">
                  <div v-if="st.kehadiran" class="text-[10px] font-mono space-x-1">
                    <span class="text-amber-700" title="Sakit">S:{{ st.kehadiran.sakit }}</span>
                    <span class="text-sky-700" title="Izin">I:{{ st.kehadiran.izin }}</span>
                    <span class="text-rose-700 font-bold" title="Alpa">A:{{ st.kehadiran.alpa }}</span>
                  </div>
                  <span v-else class="text-slate-400 text-[10px]">-</span>
                </td>
                <td class="py-3.5 px-4 text-center">
                  <span
                    v-if="st.status === 'final'"
                    class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300"
                  >
                    Final
                  </span>
                  <span
                    v-else-if="st.status === 'draft'"
                    class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-amber-100 text-amber-800 border border-amber-300"
                  >
                    Draft
                  </span>
                  <span
                    v-else
                    class="px-2 py-0.5 rounded-full text-[10px] font-semibold text-slate-400 bg-slate-100"
                  >
                    Belum Ada
                  </span>
                </td>
                <td class="py-3.5 px-4 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <!-- Buka Form Input / Edit Rapor (Full-Page Mode) -->
                    <button
                      @click="openEditor(st.student_id, st.report_card_id)"
                      class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-xs transition-all shadow-xs flex items-center gap-1"
                      title="Buka form input nilai & catatan rapor"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                      <span>{{ st.report_card_id ? 'Isi / Edit' : 'Buat Rapor' }}</span>
                    </button>

                    <!-- Cetak Rapor -->
                    <button
                      v-if="st.report_card_id"
                      @click="openPrintRaport(st.student_id, st.report_card_id)"
                      class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-all"
                      title="Cetak Rapor Siswa"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                      </svg>
                    </button>

                    <!-- Cetak Biodata Identitas -->
                    <button
                      v-if="st.report_card_id"
                      @click="openPrintIdentitas(st.student_id, st.report_card_id)"
                      class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-all"
                      title="Cetak Lembar Identitas Murid"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 2: FORM INPUT / EDITOR RAPOR SISWA (FULL PAGE, NO POPUPS) -->
    <!-- ========================================================================= -->
    <div v-else-if="currentView === 'editor' && activeReportCard" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 space-y-6">
      <!-- Editor Top Bar -->
      <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <button
            @click="currentView = 'list'"
            class="p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl transition-all flex items-center gap-2 text-xs font-bold"
            title="Kembali ke Daftar Siswa"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Daftar Rapor</span>
          </button>

          <div>
            <div class="flex items-center gap-2">
              <h2 class="text-xl font-black text-slate-900 leading-tight">
                {{ activeReportCard.student?.nama_lengkap }}
              </h2>
              <span
                class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase border"
                :class="activeReportCard.status === 'final' ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-amber-100 text-amber-800 border-amber-300'"
              >
                {{ activeReportCard.status }}
              </span>
            </div>
            <div class="text-xs text-slate-500 mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1">
              <span>NISN: <strong class="text-slate-700">{{ activeReportCard.student?.nisn || '-' }}</strong></span>
              <span>Kelas: <strong class="text-slate-700">{{ activeReportCard.school_class?.nama }}</strong></span>
              <span>Fase: <strong class="text-slate-700">{{ activeReportCard.fase }}</strong></span>
              <span>Semester: <strong class="text-slate-700">{{ activeReportCard.semester }}</strong></span>
              <span>Rata-Rata: <strong class="text-emerald-700 font-bold">{{ averageScore }}</strong></span>
            </div>
          </div>
        </div>

        <!-- Action Controls -->
        <div class="flex items-center gap-2">
          <!-- Status Selector -->
          <select
            v-model="activeReportCard.status"
            class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 focus:outline-none"
          >
            <option value="draft">Status: DRAFT</option>
            <option value="final">Status: FINAL</option>
          </select>

          <!-- Cetak Rapor Langsung -->
          <button
            @click="currentView = 'print_raport'"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs transition-all flex items-center gap-1.5"
            title="Pratinjau cetak rapor"
          >
            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            <span>Pratinjau Cetak</span>
          </button>

          <!-- Simpan Button -->
          <button
            @click="saveReportCard()"
            :disabled="saving"
            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-extrabold text-xs transition-all shadow-md flex items-center gap-2 active:scale-95"
          >
            <svg v-if="saving" class="animate-spin w-4 h-4" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>{{ saving ? 'Menyimpan...' : 'Simpan Rapor' }}</span>
          </button>
        </div>
      </div>

      <!-- Navigation Tabs (Full-Page Sub-Sections) -->
      <div class="flex border-b border-slate-200 gap-2 sm:gap-4 overflow-x-auto">
        <button
          @click="activeEditorTab = 'intra'"
          class="pb-3 px-3 text-xs sm:text-sm font-bold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap"
          :class="activeEditorTab === 'intra' ? 'border-emerald-600 text-emerald-700 font-extrabold' : 'border-transparent text-slate-500 hover:text-slate-800'"
        >
          <span>1. Nilai Intrakurikuler</span>
          <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-700 font-mono font-normal">
            {{ activeReportCard.grades?.length || 0 }} Mapel
          </span>
        </button>

        <button
          @click="activeEditorTab = 'koku'"
          class="pb-3 px-3 text-xs sm:text-sm font-bold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap"
          :class="activeEditorTab === 'koku' ? 'border-emerald-600 text-emerald-700 font-extrabold' : 'border-transparent text-slate-500 hover:text-slate-800'"
        >
          <span>2. Kokurikuler (P5 & Deep Learning)</span>
        </button>

        <button
          @click="activeEditorTab = 'ekstra'"
          class="pb-3 px-3 text-xs sm:text-sm font-bold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap"
          :class="activeEditorTab === 'ekstra' ? 'border-emerald-600 text-emerald-700 font-extrabold' : 'border-transparent text-slate-500 hover:text-slate-800'"
        >
          <span>3. Ekstrakurikuler & Kehadiran</span>
        </button>

        <button
          @click="activeEditorTab = 'notes'"
          class="pb-3 px-3 text-xs sm:text-sm font-bold border-b-2 transition-all flex items-center gap-2 whitespace-nowrap"
          :class="activeEditorTab === 'notes' ? 'border-emerald-600 text-emerald-700 font-extrabold' : 'border-transparent text-slate-500 hover:text-slate-800'"
        >
          <span>4. Catatan & Pengesahan</span>
        </button>
      </div>

      <!-- TAB 1: NILAI INTRAKURIKULER -->
      <div v-show="activeEditorTab === 'intra'" class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-emerald-50/60 p-4 rounded-2xl border border-emerald-100">
          <div>
            <h3 class="font-extrabold text-sm text-emerald-950">Mata Pelajaran & Capaian Kompetensi</h3>
            <p class="text-xs text-emerald-800">
              Input nilai akhir skala 0-100 dan deskripsi capaian pembelajaran sesuai acuan Kurikulum Merdeka.
            </p>
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="generateAllCompetencies"
              class="px-3 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1.5"
              title="Isi otomatis deskripsi capaian yang masih kosong berdasarkan nilai"
            >
              <span>✨ Generate Semua Capaian</span>
            </button>
            <button
              @click="addSubjectRow"
              class="px-3 py-1.5 bg-white border border-emerald-300 hover:bg-emerald-50 text-emerald-900 rounded-xl text-xs font-bold transition-all"
            >
              + Tambah Mapel
            </button>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow-xs border border-slate-200 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
              <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                <tr>
                  <th class="py-3 px-3 w-10 text-center">No</th>
                  <th class="py-3 px-4 w-64">Mata Pelajaran</th>
                  <th class="py-3 px-3 w-28 text-center">Kelompok</th>
                  <th class="py-3 px-3 w-24 text-center">Nilai Akhir</th>
                  <th class="py-3 px-4">Capaian Kompetensi / Deskripsi Belajar</th>
                  <th class="py-3 px-3 w-12 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr
                  v-for="(g, idx) in activeReportCard.grades"
                  :key="idx"
                  class="hover:bg-slate-50/50 transition-colors"
                >
                  <td class="py-3 px-3 text-center text-slate-400 font-bold">{{ idx + 1 }}</td>
                  <td class="py-3 px-4">
                    <input
                      v-model="g.nama_mapel"
                      type="text"
                      class="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-800 focus:bg-white focus:ring-1 focus:ring-emerald-500 focus:outline-none"
                      placeholder="Nama Mata Pelajaran..."
                    />
                  </td>
                  <td class="py-3 px-3 text-center">
                    <select
                      v-model="g.kelompok"
                      class="bg-slate-50 border border-slate-200 rounded-lg px-2 py-1.5 text-[11px] text-slate-700 focus:outline-none"
                    >
                      <option value="Umum">Umum</option>
                      <option value="Agama">Agama</option>
                      <option value="Muatan Lokal">Muatan Lokal</option>
                      <option value="Pilihan">Pilihan</option>
                    </select>
                  </td>
                  <td class="py-3 px-3 text-center">
                    <input
                      v-model.number="g.nilai_akhir"
                      type="number"
                      min="0"
                      max="100"
                      step="0.1"
                      placeholder="0"
                      class="w-20 text-center font-black bg-slate-50 border border-slate-200 rounded-lg py-1.5 text-xs focus:bg-white focus:ring-1 focus:ring-emerald-500 focus:outline-none"
                      :class="{
                        'text-emerald-700 bg-emerald-50/30': Number(g.nilai_akhir) >= 85,
                        'text-blue-700': Number(g.nilai_akhir) >= 75 && Number(g.nilai_akhir) < 85,
                        'text-amber-700': Number(g.nilai_akhir) < 75 && g.nilai_akhir !== null && g.nilai_akhir !== ''
                      }"
                    />
                  </td>
                  <td class="py-3 px-4">
                    <div class="space-y-1">
                      <textarea
                        v-model="g.capaian_kompetensi"
                        rows="2"
                        class="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:bg-white focus:ring-1 focus:ring-emerald-500 focus:outline-none"
                        placeholder="Deskripsi pencapaian kompetensi siswa..."
                      ></textarea>
                      <div class="flex items-center justify-between text-[10px]">
                        <button
                          type="button"
                          @click="generateCompetencyDescription(g)"
                          class="text-emerald-700 hover:text-emerald-900 font-bold hover:underline flex items-center gap-1"
                        >
                          <span>✨ Saran Otomatis</span>
                        </button>
                        <span class="text-slate-400">{{ (g.capaian_kompetensi || '').length }} karakter</span>
                      </div>
                    </div>
                  </td>
                  <td class="py-3 px-3 text-center">
                    <button
                      type="button"
                      @click="removeSubjectRow(idx)"
                      class="text-slate-400 hover:text-rose-600 p-1"
                      title="Hapus baris mapel"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 2: KOKURIKULER (P5 & DEEP LEARNING) -->
      <div v-show="activeEditorTab === 'koku'" class="space-y-6">
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-6">
          <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
              <h3 class="font-extrabold text-base text-slate-900">Projek Kokurikuler & Dimensi Profil Lulusan</h3>
              <p class="text-xs text-slate-500 mt-0.5">
                Evaluasi pembelajaran mendalam (Deep Learning) dan Projek Penguatan Profil Pelajar Pancasila (P5).
              </p>
            </div>
            <button
              type="button"
              @click="generateKokurikulerNarrative"
              class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1.5"
            >
              <span>✨ Susun Narasi Otomatis</span>
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tema Projek Kokurikuler</label>
              <input
                v-model="activeReportCard.kokurikuler_tema"
                type="text"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs text-slate-800 font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                placeholder="Contoh: Sampahku bersih lingkunganku sehat"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Capaian Akhir / Predikat</label>
              <select
                v-model="activeReportCard.kokurikuler_capaian"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs text-slate-800 font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
              >
                <option value="Sangat Berkembang">Sangat Berkembang (SB)</option>
                <option value="Berkembang Sesuai Harapan">Berkembang Sesuai Harapan (BSH)</option>
                <option value="Mulai Berkembang">Mulai Berkembang (MB)</option>
                <option value="Perlu Bimbingan">Perlu Bimbingan (PB)</option>
              </select>
            </div>

            <div class="md:col-span-2">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Dimensi Profil yang Dinilai</label>
              <input
                v-model="activeReportCard.kokurikuler_dimensi"
                type="text"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs text-slate-800 font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                placeholder="Contoh: Kolaborasi, Kemandirian, dan Kreativitas"
              />
              <div class="flex flex-wrap gap-1.5 mt-2">
                <span
                  v-for="dim in dimensiOptions"
                  :key="dim"
                  @click="activeReportCard.kokurikuler_dimensi = activeReportCard.kokurikuler_dimensi ? activeReportCard.kokurikuler_dimensi + ', ' + dim : dim"
                  class="cursor-pointer px-2.5 py-1 bg-slate-100 hover:bg-emerald-100 hover:text-emerald-900 text-slate-600 rounded-lg text-[10px] font-bold transition-all"
                >
                  + {{ dim }}
                </span>
              </div>
            </div>

            <div class="md:col-span-2">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Target Pembelajaran / Capaian Akhir Target</label>
              <input
                v-model="activeReportCard.kokurikuler_target"
                type="text"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs text-slate-800 font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                placeholder="Contoh: Peserta didik mampu memilah sampah organik dan membuat kompos sederhana secara mandiri."
              />
            </div>

            <div class="md:col-span-2">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Narasi Lengkap Rapor Kokurikuler</label>
              <textarea
                v-model="activeReportCard.kokurikuler_deskripsi"
                rows="4"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-800 leading-relaxed focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                placeholder="Paragraf evaluasi kokurikuler yang akan dicetak di lembar rapor..."
              ></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 3: EKSTRAKURIKULER & KEHADIRAN -->
      <div v-show="activeEditorTab === 'ekstra'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Ekstrakurikuler (2/3 width) -->
          <div class="lg:col-span-2 bg-white rounded-3xl p-5 border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <div>
                <h3 class="font-extrabold text-sm text-slate-900">Kegiatan Ekstrakurikuler (Maks. 5)</h3>
                <p class="text-xs text-slate-500">Pramuka, Renang, Pencak Silat, Mading, Publikasi, dsb.</p>
              </div>
              <button
                type="button"
                @click="addExtracurricularRow"
                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl text-xs font-bold transition-all"
              >
                + Tambah Ekstra
              </button>
            </div>

            <div class="space-y-3">
              <div
                v-for="(e, eIdx) in activeReportCard.extracurriculars"
                :key="eIdx"
                class="flex flex-col sm:flex-row items-start sm:items-center gap-3 p-3 bg-slate-50 rounded-2xl border border-slate-100"
              >
                <div class="w-full sm:w-1/3">
                  <input
                    v-model="e.kegiatan"
                    type="text"
                    placeholder="Nama Ekstrakurikuler..."
                    class="w-full bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-800 focus:outline-none"
                  />
                </div>
                <div class="w-full sm:w-1/4">
                  <select
                    v-model="e.predikat"
                    class="w-full bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-800 focus:outline-none"
                  >
                    <option value="Sangat Baik">Sangat Baik</option>
                    <option value="Baik">Baik</option>
                    <option value="Cukup">Cukup</option>
                    <option value="Kurang">Kurang</option>
                  </select>
                </div>
                <div class="w-full sm:flex-1">
                  <input
                    v-model="e.keterangan"
                    type="text"
                    placeholder="Keterangan singkat..."
                    class="w-full bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-xs text-slate-700 focus:outline-none"
                  />
                </div>
                <button
                  type="button"
                  @click="removeExtracurricularRow(eIdx)"
                  class="text-slate-400 hover:text-rose-600 p-1"
                >
                  &times;
                </button>
              </div>
            </div>
          </div>

          <!-- Kehadiran (1/3 width) -->
          <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <div>
                <h3 class="font-extrabold text-sm text-slate-900">Rekap Ketidakhadiran</h3>
                <p class="text-xs text-slate-500">Jumlah hari absen dalam semester</p>
              </div>
              <button
                type="button"
                @click="syncAttendanceCurrent"
                :disabled="syncing"
                class="p-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-xl text-xs font-bold transition-all flex items-center gap-1"
                title="Tarik data presensi dari modul KBM harian"
              >
                <svg class="w-4 h-4" :class="{ 'animate-spin': syncing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
              </button>
            </div>

            <div class="space-y-3">
              <div class="flex items-center justify-between p-3 bg-amber-50/60 rounded-2xl border border-amber-100">
                <span class="text-xs font-bold text-amber-900">Sakit (S)</span>
                <div class="flex items-center gap-2">
                  <input
                    v-model.number="activeReportCard.sakit"
                    type="number"
                    min="0"
                    class="w-16 text-center font-bold bg-white border border-amber-200 rounded-xl py-1 text-xs"
                  />
                  <span class="text-xs text-amber-800">hari</span>
                </div>
              </div>

              <div class="flex items-center justify-between p-3 bg-sky-50/60 rounded-2xl border border-sky-100">
                <span class="text-xs font-bold text-sky-900">Izin (I)</span>
                <div class="flex items-center gap-2">
                  <input
                    v-model.number="activeReportCard.izin"
                    type="number"
                    min="0"
                    class="w-16 text-center font-bold bg-white border border-sky-200 rounded-xl py-1 text-xs"
                  />
                  <span class="text-xs text-sky-800">hari</span>
                </div>
              </div>

              <div class="flex items-center justify-between p-3 bg-rose-50/60 rounded-2xl border border-rose-100">
                <span class="text-xs font-bold text-rose-900">Tanpa Keterangan (A)</span>
                <div class="flex items-center gap-2">
                  <input
                    v-model.number="activeReportCard.alpa"
                    type="number"
                    min="0"
                    class="w-16 text-center font-bold bg-white border border-rose-200 rounded-xl py-1 text-xs"
                  />
                  <span class="text-xs text-rose-800">hari</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 4: CATATAN WALI KELAS & PENGESAHAN -->
      <div v-show="activeEditorTab === 'notes'" class="space-y-6">
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-6">
          <div class="border-b border-slate-100 pb-4">
            <h3 class="font-extrabold text-base text-slate-900">Catatan Wali Kelas & Lembar Pengesahan Rapor</h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Pesan motivasi, feedback orang tua, dan identitas penandatangan rapor.
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Catatan Wali Kelas</label>
              <textarea
                v-model="activeReportCard.catatan_wali_kelas"
                rows="3"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-800 leading-relaxed focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                placeholder="Tuliskan motivasi atau catatan perkembangan belajar siswa..."
              ></textarea>
              <div class="flex flex-wrap gap-2 mt-2">
                <button
                  type="button"
                  @click="activeReportCard.catatan_wali_kelas = 'Alhamdulillah, ananda menunjukkan perkembangan belajar dan adab yang baik. Pertahankan prestasimu dan terus bersemangat menuntut ilmu.'"
                  class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 rounded-lg text-[10px] font-bold text-slate-600"
                >
                  Preset Motivasi Umum
                </button>
                <button
                  type="button"
                  @click="activeReportCard.catatan_wali_kelas = 'Tingkatkan ketekunan belajar dan keaktifan di kelas. Terus asah potensi yang dimiliki untuk hasil yang lebih baik.'"
                  class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 rounded-lg text-[10px] font-bold text-slate-600"
                >
                  Preset Peningkatan
                </button>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tempat & Tanggal Penerbitan Rapor</label>
              <input
                v-model="activeReportCard.tempat_tanggal"
                type="text"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none"
                placeholder="Contoh: Purwokerto, 20 Desember 2025"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Fase Kurikulum</label>
              <input
                v-model="activeReportCard.fase"
                type="text"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none"
                placeholder="A / B / C"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Wali Kelas</label>
              <input
                v-model="activeReportCard.wali_kelas_nama"
                type="text"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">NIP Wali Kelas</label>
              <input
                v-model="activeReportCard.wali_kelas_nip"
                type="text"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono text-slate-800 focus:outline-none"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Kepala Sekolah</label>
              <input
                v-model="activeReportCard.kepala_sekolah_nama"
                type="text"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">NIP Kepala Sekolah</label>
              <input
                v-model="activeReportCard.kepala_sekolah_nip"
                type="text"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono text-slate-800 focus:outline-none"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 3: PRATINJAU CETAK RAPOR HASIL BELAJAR (PRINT LAYOUT A4) -->
    <!-- ========================================================================= -->
    <div v-else-if="currentView === 'print_raport' && activeReportCard" class="max-w-4xl mx-auto px-4 py-8">
      <!-- Print Actions (Hidden on Print) -->
      <div class="print:hidden mb-6 flex items-center justify-between bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
        <button
          @click="currentView = 'editor'"
          class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
        >
          ← Kembali ke Editor
        </button>
        <div class="flex items-center gap-3">
          <button
            @click="currentView = 'print_identitas'"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all"
          >
            Lihat Lembar Identitas Murid
          </button>
          <button
            @click="handlePrintBrowser"
            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-extrabold shadow-md transition-all flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            <span>Cetak Dokumen (Ctrl + P)</span>
          </button>
        </div>
      </div>

      <!-- OFFICIAL REPORT SHEET (A4 PRINT CONTENT) -->
      <div class="bg-white p-8 sm:p-12 shadow-xl border border-slate-200 rounded-xl text-black font-serif print:shadow-none print:border-none print:p-0">
        <!-- Title -->
        <div class="text-center pb-4 border-b-2 border-black mb-6">
          <h2 class="text-xl sm:text-2xl font-bold uppercase tracking-wider">LAPORAN HASIL BELAJAR</h2>
          <p class="text-xs tracking-widest uppercase font-sans mt-0.5 font-semibold">
            SD ISLAM BINA INSAN MANDIRI PURWOKERTO
          </p>
        </div>

        <!-- Student Header Info (2 Columns) -->
        <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-xs mb-6 font-sans">
          <div class="flex">
            <span class="w-28 font-semibold">Nama Murid</span>
            <span class="mr-2">:</span>
            <span class="font-bold uppercase">{{ activeReportCard.student?.nama_lengkap }}</span>
          </div>
          <div class="flex">
            <span class="w-28 font-semibold">Kelas</span>
            <span class="mr-2">:</span>
            <span>{{ activeReportCard.school_class?.nama || '-' }}</span>
          </div>
          <div class="flex">
            <span class="w-28 font-semibold">NISN</span>
            <span class="mr-2">:</span>
            <span>{{ activeReportCard.student?.nisn || '-' }}</span>
          </div>
          <div class="flex">
            <span class="w-28 font-semibold">Fase</span>
            <span class="mr-2">:</span>
            <span>{{ activeReportCard.fase || 'A' }}</span>
          </div>
          <div class="flex">
            <span class="w-28 font-semibold">Sekolah</span>
            <span class="mr-2">:</span>
            <span>{{ schoolProfile?.nama_sekolah || 'SD Islam Bina Insan Mandiri' }}</span>
          </div>
          <div class="flex">
            <span class="w-28 font-semibold">Semester</span>
            <span class="mr-2">:</span>
            <span>{{ activeReportCard.semester }}</span>
          </div>
          <div class="flex">
            <span class="w-28 font-semibold">Alamat</span>
            <span class="mr-2">:</span>
            <span class="truncate">{{ activeReportCard.student?.alamat_jalan || schoolProfile?.alamat || 'Purwokerto' }}</span>
          </div>
          <div class="flex">
            <span class="w-28 font-semibold">Tahun Ajaran</span>
            <span class="mr-2">:</span>
            <span>{{ activeReportCard.academic_year?.nama || '2025/2026' }}</span>
          </div>
        </div>

        <!-- Tabel Nilai Intrakurikuler -->
        <div class="mb-6">
          <h4 class="font-bold text-xs uppercase tracking-wider mb-2 font-sans border-b border-black pb-1">
            A. Nilai Intrakurikuler
          </h4>
          <table class="w-full border-collapse border border-black text-xs font-sans">
            <thead>
              <tr class="bg-slate-100 text-center font-bold">
                <th class="border border-black py-2 px-2 w-8">No.</th>
                <th class="border border-black py-2 px-3 text-left w-64">Mata Pelajaran</th>
                <th class="border border-black py-2 px-2 w-20">Nilai Akhir</th>
                <th class="border border-black py-2 px-3 text-left">Capaian Kompetensi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(g, gIdx) in activeReportCard.grades" :key="gIdx">
                <td class="border border-black py-1.5 px-2 text-center align-top">{{ gIdx + 1 }}</td>
                <td class="border border-black py-1.5 px-3 align-top font-semibold">{{ g.nama_mapel }}</td>
                <td class="border border-black py-1.5 px-2 text-center align-top font-bold">{{ g.nilai_akhir ?? '-' }}</td>
                <td class="border border-black py-1.5 px-3 align-top text-[11px] leading-relaxed">
                  {{ g.capaian_kompetensi || '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Kokurikuler (P5) -->
        <div class="mb-6 font-sans">
          <h4 class="font-bold text-xs uppercase tracking-wider mb-2 border-b border-black pb-1">
            B. Kokurikuler
          </h4>
          <div class="border border-black p-3 rounded-none text-xs leading-relaxed">
            <div class="font-bold text-slate-900 mb-1">
              Tema: {{ activeReportCard.kokurikuler_tema || '-' }}
            </div>
            <p class="text-[11px] text-justify">
              {{ activeReportCard.kokurikuler_deskripsi || 'Ananda telah berpartisipasi aktif dalam kegiatan kokurikuler dan menunjukkan capaian berkembang sesuai harapan.' }}
            </p>
          </div>
        </div>

        <!-- Ekstrakurikuler -->
        <div class="mb-6 font-sans">
          <h4 class="font-bold text-xs uppercase tracking-wider mb-2 border-b border-black pb-1">
            C. Ekstrakurikuler
          </h4>
          <table class="w-full border-collapse border border-black text-xs">
            <thead>
              <tr class="bg-slate-100 text-center font-bold">
                <th class="border border-black py-1.5 px-2 w-8">No.</th>
                <th class="border border-black py-1.5 px-3 text-left w-56">Ekstrakurikuler</th>
                <th class="border border-black py-1.5 px-3 text-left">Keterangan / Capaian</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(e, eIdx) in activeReportCard.extracurriculars" :key="eIdx">
                <td class="border border-black py-1.5 px-2 text-center">{{ eIdx + 1 }}</td>
                <td class="border border-black py-1.5 px-3 font-semibold">{{ e.kegiatan }}</td>
                <td class="border border-black py-1.5 px-3">{{ e.predikat }} - {{ e.keterangan || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Ketidakhadiran & Catatan Wali Kelas Side-by-Side -->
        <div class="grid grid-cols-2 gap-4 mb-6 font-sans">
          <!-- Ketidakhadiran -->
          <div>
            <h4 class="font-bold text-xs uppercase tracking-wider mb-2 border-b border-black pb-1">
              D. Ketidakhadiran
            </h4>
            <table class="w-full border-collapse border border-black text-xs">
              <tbody>
                <tr>
                  <td class="border border-black py-1.5 px-3 w-40">Sakit</td>
                  <td class="border border-black py-1.5 px-3 text-center font-bold">{{ activeReportCard.sakit || 0 }} hari</td>
                </tr>
                <tr>
                  <td class="border border-black py-1.5 px-3">Izin</td>
                  <td class="border border-black py-1.5 px-3 text-center font-bold">{{ activeReportCard.izin || 0 }} hari</td>
                </tr>
                <tr>
                  <td class="border border-black py-1.5 px-3">Tanpa Keterangan</td>
                  <td class="border border-black py-1.5 px-3 text-center font-bold">{{ activeReportCard.alpa || 0 }} hari</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Catatan Wali Kelas -->
          <div>
            <h4 class="font-bold text-xs uppercase tracking-wider mb-2 border-b border-black pb-1">
              E. Catatan Wali Kelas
            </h4>
            <div class="border border-black p-3 h-24 text-[11px] leading-relaxed">
              {{ activeReportCard.catatan_wali_kelas || '-' }}
            </div>
          </div>
        </div>

        <!-- Tanggapan Orang Tua -->
        <div class="mb-8 font-sans">
          <h4 class="font-bold text-xs uppercase tracking-wider mb-2 border-b border-black pb-1">
            F. Tanggapan Orang Tua / Wali Murid
          </h4>
          <div class="border border-black p-3 h-16 border-dashed"></div>
        </div>

        <!-- Tanda Tangan Pengesahan -->
        <div class="grid grid-cols-3 gap-4 text-center text-xs font-sans mt-8">
          <div>
            <div>Mengetahui,</div>
            <div class="font-semibold">Orang Tua / Wali Murid</div>
            <div class="h-20"></div>
            <div class="border-b border-black w-36 mx-auto"></div>
          </div>

          <div>
            <div class="invisible">Spacer</div>
            <div class="font-semibold">Wali Kelas</div>
            <div class="h-20"></div>
            <div class="font-bold">{{ activeReportCard.wali_kelas_nama || 'Rasiti, S.Pd.' }}</div>
            <div class="text-[10px]">NIP. {{ activeReportCard.wali_kelas_nip || '199707202024211001' }}</div>
          </div>

          <div>
            <div>{{ activeReportCard.tempat_tanggal || 'Purwokerto, 20 Desember 2025' }}</div>
            <div class="font-semibold">Kepala Sekolah</div>
            <div class="h-20"></div>
            <div class="font-bold">{{ activeReportCard.kepala_sekolah_nama || 'Yuniatul Inayah, A.Md., S.Pd.' }}</div>
            <div class="text-[10px]">NIP. {{ activeReportCard.kepala_sekolah_nip || '197707202024211001' }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW 4: PRATINJAU CETAK IDENTITAS MURID (BIODATA RESMI A4) -->
    <!-- ========================================================================= -->
    <div v-else-if="currentView === 'print_identitas' && activeReportCard" class="max-w-4xl mx-auto px-4 py-8">
      <!-- Print Actions -->
      <div class="print:hidden mb-6 flex items-center justify-between bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
        <button
          @click="currentView = 'editor'"
          class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
        >
          ← Kembali ke Editor
        </button>
        <div class="flex items-center gap-3">
          <button
            @click="currentView = 'print_raport'"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all"
          >
            Lihat Lembar Nilai Rapor
          </button>
          <button
            @click="handlePrintBrowser"
            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-extrabold shadow-md transition-all flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            <span>Cetak Identitas (Ctrl + P)</span>
          </button>
        </div>
      </div>

      <!-- OFFICIAL STUDENT BIODATA SHEET -->
      <div class="bg-white p-8 sm:p-12 shadow-xl border border-slate-200 rounded-xl text-black font-serif print:shadow-none print:border-none print:p-0">
        <div class="text-center pb-4 mb-8">
          <h2 class="text-xl sm:text-2xl font-bold uppercase tracking-wider">IDENTITAS MURID</h2>
          <div class="w-32 border-b-2 border-black mx-auto mt-1"></div>
        </div>

        <table class="w-full text-xs font-sans leading-relaxed">
          <tbody>
            <tr>
              <td class="w-8 py-1.5 align-top">1.</td>
              <td class="w-64 py-1.5 align-top">Nama Murid</td>
              <td class="w-4 py-1.5 align-top">:</td>
              <td class="py-1.5 align-top font-bold uppercase">{{ activeReportCard.student?.nama_lengkap }}</td>
            </tr>
            <tr>
              <td class="py-1.5 align-top">2.</td>
              <td class="py-1.5 align-top">Nomor Induk Siswa (NIS)</td>
              <td class="py-1.5 align-top">:</td>
              <td class="py-1.5 align-top font-mono">{{ activeReportCard.student?.nis || '-' }}</td>
            </tr>
            <tr>
              <td class="py-1.5 align-top">3.</td>
              <td class="py-1.5 align-top">Nomor Induk Siswa Nasional (NISN)</td>
              <td class="py-1.5 align-top">:</td>
              <td class="py-1.5 align-top font-mono">{{ activeReportCard.student?.nisn || '-' }}</td>
            </tr>
            <tr>
              <td class="py-1.5 align-top">4.</td>
              <td class="py-1.5 align-top">Tempat, Tanggal Lahir</td>
              <td class="py-1.5 align-top">:</td>
              <td class="py-1.5 align-top">
                {{ activeReportCard.student?.tempat_lahir || '-' }}, {{ activeReportCard.student?.tgl_lahir || '-' }}
              </td>
            </tr>
            <tr>
              <td class="py-1.5 align-top">5.</td>
              <td class="py-1.5 align-top">Jenis Kelamin</td>
              <td class="py-1.5 align-top">:</td>
              <td class="py-1.5 align-top">
                {{ activeReportCard.student?.jenis_kelamin === 'L' ? 'Laki-Laki' : 'Perempuan' }}
              </td>
            </tr>
            <tr>
              <td class="py-1.5 align-top">6.</td>
              <td class="py-1.5 align-top">Agama</td>
              <td class="py-1.5 align-top">:</td>
              <td class="py-1.5 align-top">
                {{ activeReportCard.student?.religion?.nama || 'Islam' }}
              </td>
            </tr>
            <tr>
              <td class="py-1.5 align-top">7.</td>
              <td class="py-1.5 align-top">Pendidikan Sebelumnya</td>
              <td class="py-1.5 align-top">:</td>
              <td class="py-1.5 align-top">
                {{ activeReportCard.student?.asal_sekolah_tk || 'TK / RA' }}
              </td>
            </tr>
            <tr>
              <td class="py-1.5 align-top">8.</td>
              <td class="py-1.5 align-top">Alamat Murid</td>
              <td class="py-1.5 align-top">:</td>
              <td class="py-1.5 align-top">
                {{ activeReportCard.student?.alamat_jalan || '-' }},
                {{ activeReportCard.student?.desa_kelurahan || '' }},
                {{ activeReportCard.student?.kecamatan || '' }},
                {{ activeReportCard.student?.kab_kota || '' }}
              </td>
            </tr>
            <tr>
              <td class="py-1.5 align-top">9.</td>
              <td class="py-1.5 align-top">Diterima di Sekolah Ini Tanggal</td>
              <td class="py-1.5 align-top">:</td>
              <td class="py-1.5 align-top">
                {{ activeReportCard.student?.tgl_masuk || '15 Juli 2024' }}
              </td>
            </tr>
            <tr>
              <td class="py-1.5 align-top">10.</td>
              <td class="py-1.5 align-top">Nama Orang Tua</td>
              <td class="py-1.5 align-top">:</td>
              <td class="py-1.5 align-top"></td>
            </tr>
            <tr>
              <td></td>
              <td class="py-1 pl-4">a. Ayah</td>
              <td>:</td>
              <td class="py-1">
                {{ activeReportCard.student?.guardians?.find((g: any) => g.pivot?.hubungan === 'ayah')?.nama_lengkap || '-' }}
              </td>
            </tr>
            <tr>
              <td></td>
              <td class="py-1 pl-4">b. Ibu</td>
              <td>:</td>
              <td class="py-1">
                {{ activeReportCard.student?.guardians?.find((g: any) => g.pivot?.hubungan === 'ibu')?.nama_lengkap || '-' }}
              </td>
            </tr>
            <tr>
              <td class="py-1.5 align-top">11.</td>
              <td class="py-1.5 align-top">Pekerjaan Orang Tua</td>
              <td class="py-1.5 align-top">:</td>
              <td class="py-1.5 align-top">Karyawan / Wiraswasta</td>
            </tr>
            <tr>
              <td class="py-1.5 align-top">12.</td>
              <td class="py-1.5 align-top">Alamat Orang Tua</td>
              <td class="py-1.5 align-top">:</td>
              <td class="py-1.5 align-top">
                {{ activeReportCard.student?.alamat_jalan || 'Purwokerto' }}
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Foto 3x4 Frame & Tanda Tangan -->
        <div class="mt-16 flex items-end justify-between font-sans text-xs">
          <!-- Foto Box -->
          <div class="w-28 h-36 border-2 border-dashed border-slate-400 flex items-center justify-center text-slate-400 text-center p-2 font-mono text-[10px]">
            <span>Pas Foto Siswa<br>3 x 4 cm</span>
          </div>

          <!-- Tanda Tangan Kepala Sekolah -->
          <div class="text-center w-64">
            <div>{{ activeReportCard.tempat_tanggal || 'Purwokerto, 20 Desember 2025' }}</div>
            <div class="font-semibold">Kepala Sekolah,</div>
            <div class="h-20"></div>
            <div class="font-bold underline">{{ activeReportCard.kepala_sekolah_nama || 'Yuniatul Inayah, A.Md., S.Pd.' }}</div>
            <div class="text-[10px]">NIP. {{ activeReportCard.kepala_sekolah_nip || '197707202024211001' }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@media print {
  @page {
    size: A4;
    margin: 1.5cm;
  }
  body {
    background: white !important;
    color: black !important;
  }
}
</style>
