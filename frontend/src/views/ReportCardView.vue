<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()

interface SubjectGrade {
  id?: number
  nama_mapel: string
  kelompok?: string
  tema?: string | null
  aspek?: string | null
  sub_aspek?: string | null
  nilai_akhir?: number | null | string
  predikat?: string | null
  capaian_kompetensi?: string | null
  urutan?: number
}

interface Extracurricular {
  id?: number
  kegiatan: string
  predikat: string
  keterangan: string | null
  urutan?: number
}

interface ReportCardDetail {
  id: number
  student_id: number
  class_id: number
  academic_year_id: number
  semester: string
  jenis_rapor: 'kurikulum_merdeka' | 'triwulan_tematik' | 'karakter_lifeskill'
  periode?: string | null
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
  status: 'draft' | 'menunggu_validasi' | 'final' | 'perlu_revisi'
  submitted_at?: string | null
  submitted_by?: number | null
  submitter?: { id: number; nama: string } | null
  validated_at?: string | null
  validated_by?: number | null
  validator?: { id: number; nama: string } | null
  validation_notes?: string | null
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

// View Modes: 'list' | 'editor' | 'print_raport' | 'print_tematik' | 'print_karakter' | 'print_identitas'
const currentView = ref<'list' | 'editor' | 'print_raport' | 'print_tematik' | 'print_karakter' | 'print_identitas'>('list')

const loading = ref(false)
const saving = ref(false)
const syncing = ref(false)
const submittingValidation = ref(false)
const validatingReport = ref(false)
const message = ref<{ type: 'success' | 'error' | 'warning'; text: string } | null>(null)

// Filters
const classes = ref<any[]>([])
const academicYears = ref<any[]>([])
const selectedClassId = ref<string | number>('')
const selectedSemester = ref<'Gasal' | 'Genap'>('Gasal')
const selectedAcademicYearId = ref<string | number>('')
const selectedJenisRapor = ref<'kurikulum_merdeka' | 'triwulan_tematik' | 'karakter_lifeskill'>('kurikulum_merdeka')
const selectedPeriode = ref<string>('Semester Gasal')
const statusFilter = ref<string>('all')
const searchQuery = ref('')

// Class Report Overview Data
const classReportData = ref<any | null>(null)

// Editor State
const activeEditorTab = ref<'grades' | 'koku' | 'ekstra' | 'notes'>('grades')
const activeReportCard = ref<ReportCardDetail | null>(null)
const schoolProfile = ref<any>({
  nama_sekolah: 'SD Islam Bina Insan Mandiri Purwokerto',
  alamat: 'Jl. Wadas Kelir RT.04 RW.05 Kel. Karangklesem, Kec. Purwokerto Selatan, Kab. Banyumas 53144',
  npsn: '70036695',
  logo_path: null,
})

// Modal Revisi (Kepala Sekolah)
const showRevisionModal = ref(false)
const revisionNoteInput = ref('')
const revisionTargetCardId = ref<number | null>(null)

// File import ref
const fileInput = ref<HTMLInputElement | null>(null)
const importingExcel = ref(false)

const showNotification = (text: string, type: 'success' | 'error' | 'warning' = 'success') => {
  message.value = { type, text }
  setTimeout(() => {
    message.value = null
  }, 4500)
}

// Periode options based on selected jenis rapor
const periodeOptions = computed(() => {
  if (selectedJenisRapor.value === 'kurikulum_merdeka') {
    return [
      { label: 'Semester Gasal', value: 'Semester Gasal' },
      { label: 'Semester Genap', value: 'Semester Genap' },
    ]
  }
  return [
    { label: 'Triwulan I', value: 'Triwulan I' },
    { label: 'Triwulan II', value: 'Triwulan II' },
    { label: 'Triwulan III', value: 'Triwulan III' },
    { label: 'Triwulan IV', value: 'Triwulan IV' },
    { label: 'Semester Gasal', value: 'Semester Gasal' },
    { label: 'Semester Genap', value: 'Semester Genap' },
  ]
})

// Sync selectedPeriode when jenis rapor changes
watch(selectedJenisRapor, (newVal) => {
  if (newVal === 'kurikulum_merdeka') {
    selectedPeriode.value = selectedSemester.value === 'Gasal' ? 'Semester Gasal' : 'Semester Genap'
  } else {
    selectedPeriode.value = 'Triwulan I'
  }
  loadClassReports()
})

// Sync semester when periode changes
watch(selectedPeriode, () => {
  loadClassReports()
})

// Dimensi Profil Lulusan (P5)
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
  let list = classReportData.value.students

  if (statusFilter.value !== 'all') {
    list = list.filter((st: any) => st.status === statusFilter.value)
  }

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase().trim()
    list = list.filter((st: any) =>
      st.nama_lengkap?.toLowerCase().includes(q) ||
      st.nis?.toLowerCase().includes(q) ||
      st.nisn?.toLowerCase().includes(q)
    )
  }

  return list
})

// Count pending validations
const pendingCount = computed(() => {
  if (!classReportData.value?.students) return 0
  return classReportData.value.students.filter((st: any) => st.status === 'menunggu_validasi').length
})

// Count draft or needs revision
const draftOrRevisionCount = computed(() => {
  if (!classReportData.value?.students) return 0
  return classReportData.value.students.filter((st: any) => st.status === 'draft' || st.status === 'perlu_revisi').length
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

// Group grades by Aspek for Karakter & Life Skill
const groupedCharacterGrades = computed(() => {
  if (!activeReportCard.value?.grades) return {}
  const groups: Record<string, SubjectGrade[]> = {}
  activeReportCard.value.grades.forEach((g) => {
    const key = g.aspek || 'Penguatan Karakter'
    if (!groups[key]) groups[key] = []
    groups[key].push(g)
  })
  return groups
})

// Fetch initial dropdowns
const fetchMasterData = async () => {
  try {
    const [classesRes, yearsRes] = await Promise.all([
      api.get('/classes'),
      api.get('/academic-years'),
    ])

    if (classesRes.data.success) {
      let list = classesRes.data.data

      // If user is Wali Kelas (and not admin), restrict to their assigned classes if available
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
        jenis_rapor: selectedJenisRapor.value,
        periode: selectedPeriode.value || undefined,
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
  const labelJenis = selectedJenisRapor.value === 'triwulan_tematik'
    ? 'Rapor Triwulan Tematik BIM (5 Tema Terpadu)'
    : selectedJenisRapor.value === 'karakter_lifeskill'
    ? 'Laporan Karakter & Life Skill (17 Butir Penilaian)'
    : 'Rapor Kurikulum Merdeka (18 Mapel Standar)'

  if (!confirm(overwrite
    ? `Perbarui seluruh format ${labelJenis} untuk semua siswa di kelas ini? Nilai yang ada tidak akan terhapus.`
    : `Inisialisasi draf ${labelJenis} untuk seluruh siswa di kelas ini periode ${selectedPeriode.value}?`
  )) {
    return
  }

  loading.value = true
  try {
    const res = await api.post('/report-cards/initialize-class', {
      class_id: selectedClassId.value,
      academic_year_id: selectedAcademicYearId.value || undefined,
      semester: selectedSemester.value,
      jenis_rapor: selectedJenisRapor.value,
      periode: selectedPeriode.value,
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
      activeEditorTab.value = 'grades'
      currentView.value = 'editor'
    }
  } catch (err: any) {
    showNotification('Gagal memuat rapor siswa: ' + (err.response?.data?.message || err.message), 'error')
  } finally {
    loading.value = false
  }
}

// Save Report Card
const saveReportCard = async (statusOverride?: 'draft' | 'menunggu_validasi' | 'final' | 'perlu_revisi') => {
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
      activeReportCard.value = JSON.parse(JSON.stringify(res.data.data))
      showNotification(res.data.message || 'Rapor berhasil disimpan.', 'success')
      await loadClassReports()
    }
  } catch (err: any) {
    showNotification('Gagal menyimpan rapor: ' + (err.response?.data?.message || err.message), 'error')
  } finally {
    saving.value = false
  }
}

// Submit single report card for validation (Wali Kelas -> KS)
const submitSingleForValidation = async (id: number) => {
  if (!confirm('Ajukan rapor ini ke Kepala Sekolah untuk validasi resmi?')) return
  submittingValidation.value = true
  try {
    const res = await api.post(`/report-cards/${id}/submit`)
    if (res.data.success) {
      showNotification(res.data.message, 'success')
      if (activeReportCard.value && activeReportCard.value.id === id) {
        activeReportCard.value.status = 'menunggu_validasi'
        activeReportCard.value.submitted_at = res.data.data.submitted_at
      }
      await loadClassReports()
    }
  } catch (err: any) {
    showNotification('Gagal mengajukan rapor: ' + (err.response?.data?.message || err.message), 'error')
  } finally {
    submittingValidation.value = false
  }
}

// Bulk submit class for validation (Wali Kelas -> KS)
const handleBulkSubmitClass = async () => {
  if (!selectedClassId.value) return
  if (!confirm(`Ajukan seluruh rapor siswa kelas ini (${draftOrRevisionCount.value} draf) kepada Kepala Sekolah untuk validasi?`)) return

  submittingValidation.value = true
  try {
    const res = await api.post('/report-cards/submit-class', {
      class_id: selectedClassId.value,
      academic_year_id: selectedAcademicYearId.value || undefined,
      semester: selectedSemester.value,
      jenis_rapor: selectedJenisRapor.value,
      periode: selectedPeriode.value,
    })
    if (res.data.success) {
      showNotification(res.data.message, 'success')
      await loadClassReports()
    }
  } catch (err: any) {
    showNotification('Gagal mengajukan rapor kelas: ' + (err.response?.data?.message || err.message), 'error')
  } finally {
    submittingValidation.value = false
  }
}

// Validate & Approve single report card (KS)
const handleValidateSingle = async (id: number) => {
  if (!confirm('Validasi dan setujui rapor ini sebagai dokumen resmi (Final)?')) return
  validatingReport.value = true
  try {
    const res = await api.post(`/report-cards/${id}/validate`)
    if (res.data.success) {
      showNotification(res.data.message, 'success')
      if (activeReportCard.value && activeReportCard.value.id === id) {
        activeReportCard.value.status = 'final'
        activeReportCard.value.validated_at = res.data.data.validated_at
      }
      await loadClassReports()
    }
  } catch (err: any) {
    showNotification('Gagal memvalidasi rapor: ' + (err.response?.data?.message || err.message), 'error')
  } finally {
    validatingReport.value = false
  }
}

// Bulk validate class (KS)
const handleBulkValidateClass = async () => {
  if (!selectedClassId.value) return
  if (!confirm(`Validasi dan setujui secara massal ${pendingCount.value} rapor siswa yang telah diajukan di kelas ini?`)) return

  validatingReport.value = true
  try {
    const res = await api.post('/report-cards/validate-class', {
      class_id: selectedClassId.value,
      academic_year_id: selectedAcademicYearId.value || undefined,
      semester: selectedSemester.value,
      jenis_rapor: selectedJenisRapor.value,
      periode: selectedPeriode.value,
    })
    if (res.data.success) {
      showNotification(res.data.message, 'success')
      await loadClassReports()
    }
  } catch (err: any) {
    showNotification('Gagal memvalidasi rapor massal: ' + (err.response?.data?.message || err.message), 'error')
  } finally {
    validatingReport.value = false
  }
}

// Open modal minta revisi (KS)
const openRevisionModal = (reportCardId: number) => {
  revisionTargetCardId.value = reportCardId
  revisionNoteInput.value = ''
  showRevisionModal.value = true
}

// Send revision request (KS -> Wali Kelas)
const handleSendRevision = async () => {
  if (!revisionTargetCardId.value || !revisionNoteInput.value.trim()) {
    alert('Harap masukkan catatan revisi untuk Wali Kelas.')
    return
  }

  validatingReport.value = true
  try {
    const res = await api.post(`/report-cards/${revisionTargetCardId.value}/request-revision`, {
      notes: revisionNoteInput.value.trim(),
    })
    if (res.data.success) {
      showNotification(res.data.message, 'success')
      showRevisionModal.value = false
      if (activeReportCard.value && activeReportCard.value.id === revisionTargetCardId.value) {
        activeReportCard.value.status = 'perlu_revisi'
        activeReportCard.value.validation_notes = revisionNoteInput.value.trim()
      }
      await loadClassReports()
    }
  } catch (err: any) {
    showNotification('Gagal mengirim permintaan revisi: ' + (err.response?.data?.message || err.message), 'error')
  } finally {
    validatingReport.value = false
  }
}

// Sync Attendance
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
    showNotification('Gagal sinkronisasi presensi: ' + (err.response?.data?.message || err.message), 'error')
  } finally {
    syncing.value = false
  }
}

// Auto predicate calculation from score
const calculatePredicate = (score: number | null | string): string => {
  if (score === null || score === '' || isNaN(Number(score))) return '-'
  const num = Number(score)
  if (num > 92) return 'A'
  if (num > 83) return 'B'
  if (num >= 75) return 'C'
  return 'D'
}

// Add/Remove Grade or Ekstra Row
const addGradeRow = () => {
  if (!activeReportCard.value) return
  const nextUrutan = (activeReportCard.value.grades.length || 0) + 1
  activeReportCard.value.grades.push({
    nama_mapel: 'Mata Pelajaran Baru',
    kelompok: 'Umum',
    nilai_akhir: null,
    predikat: null,
    capaian_kompetensi: null,
    urutan: nextUrutan,
  })
}

const removeGradeRow = (index: number) => {
  if (!activeReportCard.value) return
  if (confirm('Hapus mata pelajaran/aspek ini dari rapor?')) {
    activeReportCard.value.grades.splice(index, 1)
  }
}

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

// Open Print View
const openPrintView = (studentId: number, reportCardId: number | null) => {
  openEditor(studentId, reportCardId).then(() => {
    if (selectedJenisRapor.value === 'triwulan_tematik') {
      currentView.value = 'print_tematik'
    } else if (selectedJenisRapor.value === 'karakter_lifeskill') {
      currentView.value = 'print_karakter'
    } else {
      currentView.value = 'print_raport'
    }
  })
}

const openIdentityPrint = (studentId: number, reportCardId: number | null) => {
  openEditor(studentId, reportCardId).then(() => {
    currentView.value = 'print_identitas'
  })
}

const handlePrintBrowser = () => {
  window.print()
}

// Export / Import Excel
const handleExportExcel = () => {
  if (!selectedClassId.value) return
  const url = `/api/v1/report-cards/export-excel?class_id=${selectedClassId.value}&semester=${selectedSemester.value}&academic_year_id=${selectedAcademicYearId.value || ''}&jenis_rapor=${selectedJenisRapor.value}&periode=${encodeURIComponent(selectedPeriode.value || '')}`
  window.open(url, '_blank')
}

const triggerFileInput = () => {
  fileInput.value?.click()
}

const handleFileChange = async (event: Event) => {
  const target = event.target as HTMLInputElement
  if (!target.files || !target.files[0]) return

  const file = target.files[0]
  if (!confirm(`Import data nilai dari file "${file.name}" untuk kelas ini?`)) {
    target.value = ''
    return
  }

  importingExcel.value = true
  const formData = new FormData()
  formData.append('file', file)
  formData.append('class_id', String(selectedClassId.value))
  formData.append('semester', selectedSemester.value)
  formData.append('jenis_rapor', selectedJenisRapor.value)
  if (selectedAcademicYearId.value) {
    formData.append('academic_year_id', String(selectedAcademicYearId.value))
  }

  try {
    const res = await api.post('/report-cards/import-excel', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    if (res.data.success) {
      showNotification(res.data.message, 'success')
      await loadClassReports()
    }
  } catch (err: any) {
    showNotification('Import Excel gagal: ' + (err.response?.data?.message || err.message), 'error')
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
        <button @click="message = null" class="text-white/80 hover:text-white font-black">&times;</button>
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
              @click="currentView = 'list'"
              class="px-3.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs transition-all flex items-center gap-1.5"
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
            class="px-4 py-2.5 text-xs font-bold transition-all border-b-2 flex items-center gap-2 whitespace-nowrap"
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
            class="px-4 py-2.5 text-xs font-bold transition-all border-b-2 flex items-center gap-2 whitespace-nowrap"
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
            class="px-4 py-2.5 text-xs font-bold transition-all border-b-2 flex items-center gap-2 whitespace-nowrap"
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

    <!-- VIEW 1: STUDENT LIST & CLASS OVERVIEW -->
    <div v-if="currentView === 'list'" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 space-y-6">
      <!-- FILTER TOOLBAR -->
      <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 items-end">
          <!-- Class Selector -->
          <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
              Rombongan Belajar
            </label>
            <select
              v-model="selectedClassId"
              @change="loadClassReports"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
            >
              <option v-for="c in classes" :key="c.id" :value="c.id">
                {{ c.nama }} (Tingkat {{ c.tingkat }})
              </option>
            </select>
          </div>

          <!-- Academic Year -->
          <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
              Tahun Ajaran
            </label>
            <select
              v-model="selectedAcademicYearId"
              @change="loadClassReports"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
            >
              <option v-for="y in academicYears" :key="y.id" :value="y.id">
                {{ y.nama }} {{ y.is_aktif ? '★ (Aktif)' : '' }}
              </option>
            </select>
          </div>

          <!-- Semester -->
          <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
              Semester
            </label>
            <select
              v-model="selectedSemester"
              @change="loadClassReports"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
            >
              <option value="Gasal">Semester Gasal (1)</option>
              <option value="Genap">Semester Genap (2)</option>
            </select>
          </div>

          <!-- Periode / Triwulan -->
          <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
              Periode Rapor
            </label>
            <select
              v-model="selectedPeriode"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
            >
              <option v-for="opt in periodeOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </div>

          <!-- Search & Filter Status -->
          <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
              Filter Status
            </label>
            <select
              v-model="statusFilter"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
            >
              <option value="all">Semua Status ({{ classReportData?.total_students || 0 }})</option>
              <option value="belum_ada">Belum Dibuat ({{ classReportData?.belum_ada_count || 0 }})</option>
              <option value="draft">Draft Wali Kelas ({{ classReportData?.draft_count || 0 }})</option>
              <option value="menunggu_validasi">Menunggu Validasi ({{ classReportData?.submitted_count || 0 }})</option>
              <option value="final">Disetujui / Final ({{ classReportData?.completed_count || 0 }})</option>
              <option value="perlu_revisi">Perlu Revisi ({{ classReportData?.revision_count || 0 }})</option>
            </select>
          </div>
        </div>

        <!-- SEARCH BAR & ACTION BUTTONS -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-slate-100">
          <div class="relative w-full sm:w-72">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari nama siswa / NIS / NISN..."
              class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
            />
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>

          <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-end">
            <!-- WALI KELAS: SUBMIT CLASS BULK -->
            <button
              v-if="draftOrRevisionCount > 0"
              @click="handleBulkSubmitClass"
              :disabled="submittingValidation"
              class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1.5"
              title="Ajukan seluruh draf rapor siswa kelas ini ke Kepala Sekolah"
            >
              <span>🚀</span>
              <span>Ajukan Semua ke KS ({{ draftOrRevisionCount }})</span>
            </button>

            <!-- KEPALA SEKOLAH / ADMIN: VALIDATE CLASS BULK -->
            <button
              v-if="auth.isAdmin && pendingCount > 0"
              @click="handleBulkValidateClass"
              :disabled="validatingReport"
              class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1.5"
              title="Validasi dan setujui massal semua rapor yang diajukan di kelas ini"
            >
              <span>✓✓</span>
              <span>Validasi Massal 1 Kelas ({{ pendingCount }})</span>
            </button>

            <!-- INITIALIZE BUTTON -->
            <button
              @click="handleInitializeClass(false)"
              :disabled="loading"
              class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1.5"
            >
              <span>⚡</span>
              <span>Inisialisasi Kelas</span>
            </button>

            <!-- EXPORT EXCEL -->
            <button
              @click="handleExportExcel"
              class="px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
              title="Download rekap nilai rapor kelas dalam format Excel"
            >
              <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span>Export Excel</span>
            </button>

            <!-- IMPORT EXCEL -->
            <input ref="fileInput" type="file" accept=".xlsx,.xls" class="hidden" @change="handleFileChange" />
            <button
              @click="triggerFileInput"
              :disabled="importingExcel"
              class="px-3 py-2 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
              title="Upload file spreadsheet nilai siswa"
            >
              <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
              </svg>
              <span>{{ importingExcel ? 'Mengimpor...' : 'Import Excel' }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- CLASS INFO & KPI CARDS -->
      <div v-if="classReportData" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
          <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Siswa</div>
          <div class="text-xl font-black text-slate-900 mt-1">{{ classReportData.total_students || 0 }}</div>
          <div class="text-[10px] text-slate-500 mt-0.5">{{ classReportData.class?.nama }}</div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
          <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Wali Kelas</div>
          <div class="text-xs font-bold text-slate-800 mt-1 truncate" :title="classReportData.class?.wali_kelas">
            {{ classReportData.class?.wali_kelas || '-' }}
          </div>
          <div class="text-[10px] text-slate-400 mt-0.5">NIP: {{ classReportData.class?.wali_kelas_nip || '-' }}</div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-amber-200/80 bg-amber-50/20 shadow-xs">
          <div class="text-[10px] font-bold text-amber-700 uppercase tracking-wider">Draft Wali Kelas</div>
          <div class="text-xl font-black text-amber-800 mt-1">{{ classReportData.draft_count || 0 }}</div>
          <div class="text-[10px] text-amber-600 mt-0.5">Dalam pengisian</div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-blue-200/80 bg-blue-50/20 shadow-xs">
          <div class="text-[10px] font-bold text-blue-700 uppercase tracking-wider">Menunggu Validasi</div>
          <div class="text-xl font-black text-blue-800 mt-1">{{ classReportData.submitted_count || 0 }}</div>
          <div class="text-[10px] text-blue-600 mt-0.5">Diajukan ke KS</div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-emerald-200/80 bg-emerald-50/20 shadow-xs">
          <div class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Disetujui / Final</div>
          <div class="text-xl font-black text-emerald-800 mt-1">{{ classReportData.completed_count || 0 }}</div>
          <div class="text-[10px] text-emerald-600 mt-0.5">Siap cetak resmi</div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-rose-200/80 bg-rose-50/20 shadow-xs">
          <div class="text-[10px] font-bold text-rose-700 uppercase tracking-wider">Perlu Revisi</div>
          <div class="text-xl font-black text-rose-800 mt-1">{{ classReportData.revision_count || 0 }}</div>
          <div class="text-[10px] text-rose-600 mt-0.5">Catatan dari KS</div>
        </div>
      </div>

      <!-- STUDENTS TABLE -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/90 border-b border-slate-200/80 text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
                <th class="py-3 px-4 w-12 text-center">No</th>
                <th class="py-3 px-4">Nama Siswa</th>
                <th class="py-3 px-4 w-32">NIS / NISN</th>
                <th class="py-3 px-4 w-40 text-center">Kelengkapan Nilai</th>
                <th class="py-3 px-4 w-44 text-center">Status Validasi</th>
                <th class="py-3 px-4 w-40 text-center">Kehadiran (S/I/A)</th>
                <th class="py-3 px-4 w-52 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
              <tr v-if="loading" class="text-center py-10">
                <td colspan="7" class="py-10 text-slate-400">
                  <div class="flex items-center justify-center gap-2">
                    <svg class="animate-spin w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Memuat daftar rapor siswa...</span>
                  </div>
                </td>
              </tr>
              <tr v-else-if="filteredStudents.length === 0" class="text-center py-10">
                <td colspan="7" class="py-10 text-slate-400">
                  Tidak ada data siswa ditemukan untuk kriteria filter ini.
                </td>
              </tr>
              <tr
                v-for="st in filteredStudents"
                :key="st.student_id"
                class="hover:bg-slate-50/70 transition-colors"
              >
                <td class="py-3 px-4 text-center text-slate-400 font-bold">
                  {{ st.no_urut }}
                </td>
                <td class="py-3 px-4">
                  <div class="font-bold text-slate-900 flex items-center gap-2">
                    <span>{{ st.nama_lengkap }}</span>
                    <span v-if="st.jenis_kelamin === 'L'" class="text-[10px] text-blue-600 font-bold bg-blue-50 px-1.5 py-0.2 rounded">L</span>
                    <span v-else class="text-[10px] text-pink-600 font-bold bg-pink-50 px-1.5 py-0.2 rounded">P</span>
                  </div>
                  <div class="text-[10px] text-slate-400 mt-0.5">
                    Periode: {{ st.periode }}
                  </div>
                  <!-- Catatan revisi alert jika ada -->
                  <div v-if="st.status === 'perlu_revisi' && st.validation_notes" class="mt-1 text-[10px] text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                    ⚠️ Revisi: {{ st.validation_notes }}
                  </div>
                </td>
                <td class="py-3 px-4 text-slate-600 font-mono text-[11px]">
                  <div>{{ st.nis || '-' }}</div>
                  <div class="text-[10px] text-slate-400">NISN: {{ st.nisn || '-' }}</div>
                </td>
                <td class="py-3 px-4 text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <span class="font-bold text-slate-900">{{ st.mapel_terisi }}</span>
                    <span class="text-slate-400">/</span>
                    <span class="text-slate-500">{{ st.total_mapel }}</span>
                  </div>
                  <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1.5 overflow-hidden">
                    <div
                      class="h-full rounded-full transition-all duration-300"
                      :class="st.mapel_terisi === st.total_mapel && st.total_mapel > 0 ? 'bg-emerald-500' : 'bg-amber-500'"
                      :style="{ width: `${st.total_mapel > 0 ? (st.mapel_terisi / st.total_mapel) * 100 : 0}%` }"
                    ></div>
                  </div>
                </td>
                <td class="py-3 px-4 text-center">
                  <span
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold border"
                    :class="{
                      'bg-slate-100 text-slate-600 border-slate-200': st.status === 'belum_ada',
                      'bg-amber-50 text-amber-800 border-amber-200': st.status === 'draft',
                      'bg-blue-50 text-blue-800 border-blue-200': st.status === 'menunggu_validasi',
                      'bg-emerald-50 text-emerald-800 border-emerald-200': st.status === 'final',
                      'bg-rose-50 text-rose-800 border-rose-200': st.status === 'perlu_revisi',
                    }"
                  >
                    <span v-if="st.status === 'belum_ada'">⚪ Belum Dibuat</span>
                    <span v-else-if="st.status === 'draft'">🟡 Draft Wali Kelas</span>
                    <span v-else-if="st.status === 'menunggu_validasi'">🔵 Menunggu KS</span>
                    <span v-else-if="st.status === 'final'">🟢 Disetujui (Final)</span>
                    <span v-else-if="st.status === 'perlu_revisi'">🔴 Perlu Revisi</span>
                  </span>
                  <div v-if="st.validated_at" class="text-[9px] text-slate-400 mt-0.5">
                    Oleh: {{ st.validated_by_name || 'Kepala Sekolah' }}
                  </div>
                </td>
                <td class="py-3 px-4 text-center text-xs">
                  <div v-if="st.kehadiran" class="flex items-center justify-center gap-2">
                    <span class="text-amber-700 font-bold" title="Sakit">S:{{ st.kehadiran.sakit }}</span>
                    <span class="text-blue-700 font-bold" title="Izin">I:{{ st.kehadiran.izin }}</span>
                    <span class="text-rose-700 font-bold" title="Alpa">A:{{ st.kehadiran.alpa }}</span>
                  </div>
                  <span v-else class="text-slate-400">-</span>
                </td>
                <td class="py-3 px-4 text-right">
                  <div class="flex items-center justify-end gap-1.5 flex-wrap">
                    <!-- TAMPILAN UNTUK KEPALA SEKOLAH & ADMIN (VALIDATOR) -->
                    <template v-if="auth.isAdmin">
                      <!-- KONDISI 1: MENUNGGU VALIDASI (PRIORITAS REVIEW KS) -->
                      <template v-if="st.status === 'menunggu_validasi'">
                        <button
                          @click="openEditor(st.student_id, st.report_card_id)"
                          class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1"
                          title="Periksa dan evaluasi capaian rapor siswa"
                        >
                          <span>🔍</span>
                          <span>Cek Rapor</span>
                        </button>
                        <button
                          @click="handleValidateSingle(st.report_card_id)"
                          :disabled="validatingReport"
                          class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1"
                          title="Validasi & setujui langsung rapor ini"
                        >
                          <span>✓</span>
                          <span>Setujui</span>
                        </button>
                        <button
                          @click="openRevisionModal(st.report_card_id)"
                          class="px-2 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold transition-all flex items-center gap-1"
                          title="Kembalikan ke wali kelas dengan catatan perbaikan"
                        >
                          <span>↩</span>
                          <span>Revisi</span>
                        </button>
                      </template>

                      <!-- KONDISI 2: SUDAH FINAL / DISETUJUI -->
                      <template v-else-if="st.status === 'final'">
                        <button
                          @click="openEditor(st.student_id, st.report_card_id)"
                          class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-lg text-xs font-bold transition-all flex items-center gap-1"
                          title="Lihat detail rapor yang telah disahkan"
                        >
                          <span>👁️</span>
                          <span>Lihat</span>
                        </button>
                        <button
                          @click="openRevisionModal(st.report_card_id)"
                          class="px-2 py-1 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg text-xs font-bold transition-all"
                          title="Batalkan pengesahan untuk perbaikan darurat"
                        >
                          ↩ Revisi
                        </button>
                      </template>

                      <!-- KONDISI 3: PERLU REVISI -->
                      <template v-else-if="st.status === 'perlu_revisi'">
                        <button
                          @click="openEditor(st.student_id, st.report_card_id)"
                          class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold transition-all flex items-center gap-1"
                          title="Lihat status perbaikan wali kelas"
                        >
                          <span>🔍</span>
                          <span>Cek Revisi</span>
                        </button>
                      </template>

                      <!-- KONDISI 4: DRAFT / BELUM ADA -->
                      <template v-else>
                        <button
                          @click="openEditor(st.student_id, st.report_card_id)"
                          class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition-all flex items-center gap-1"
                          title="Tinjau atau bantu pengisian rapor siswa"
                        >
                          <span>✏️</span>
                          <span>Tinjau / Edit</span>
                        </button>
                      </template>
                    </template>

                    <!-- TAMPILAN UNTUK WALI KELAS (PENGISI RAPOR) -->
                    <template v-else>
                      <button
                        @click="openEditor(st.student_id, st.report_card_id)"
                        class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1"
                        :class="st.status === 'final'
                          ? 'bg-slate-100 hover:bg-slate-200 text-slate-800'
                          : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs'"
                        :title="st.status === 'final' ? 'Lihat rapor siswa' : 'Isi capaian nilai dan catatan rapor siswa'"
                      >
                        <span v-if="st.status === 'final'">👁️ Lihat</span>
                        <span v-else>✏️ Isi Nilai</span>
                      </button>

                      <!-- AJUKAN KE KS JIKA STATUS DRAFT / REVISI -->
                      <button
                        v-if="st.report_card_id && (st.status === 'draft' || st.status === 'perlu_revisi')"
                        @click="submitSingleForValidation(st.report_card_id)"
                        :disabled="submittingValidation"
                        class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1"
                        title="Ajukan rapor ini ke Kepala Sekolah untuk divalidasi"
                      >
                        <span>🚀</span>
                        <span>Ajukan ke KS</span>
                      </button>
                    </template>

                    <!-- PRINT BUTTON (JIKA RAPOR SUDAH DIINISIALISASI) -->
                    <button
                      v-if="st.report_card_id"
                      @click="openPrintView(st.student_id, st.report_card_id)"
                      class="p-1.5 hover:bg-slate-100 rounded-lg text-slate-600 hover:text-emerald-700 transition-all"
                      title="Cetak lembar cetak rapor resmi"
                    >
                      🖨️
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- VIEW 2: REPORT CARD EDITOR -->
    <div v-else-if="currentView === 'editor' && activeReportCard" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 space-y-6">
      <!-- EDITOR HEADER CARD -->
      <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-xs font-bold px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-md uppercase">
                {{ activeReportCard.jenis_rapor.replace('_', ' ') }}
              </span>
              <h2 class="text-xl font-black text-slate-900">
                {{ activeReportCard.student?.nama_lengkap }}
              </h2>
              <span
                class="px-2.5 py-0.5 rounded-full text-xs font-extrabold border"
                :class="{
                  'bg-amber-50 text-amber-800 border-amber-200': activeReportCard.status === 'draft',
                  'bg-blue-50 text-blue-800 border-blue-200': activeReportCard.status === 'menunggu_validasi',
                  'bg-emerald-50 text-emerald-800 border-emerald-200': activeReportCard.status === 'final',
                  'bg-rose-50 text-rose-800 border-rose-200': activeReportCard.status === 'perlu_revisi',
                }"
              >
                {{ activeReportCard.status.toUpperCase() }}
              </span>
            </div>

            <div class="flex items-center gap-4 text-xs text-slate-500 mt-2 flex-wrap font-medium">
              <span>NISN: <strong class="text-slate-800">{{ activeReportCard.student?.nisn || '-' }}</strong></span>
              <span>Kelas: <strong class="text-slate-800">{{ activeReportCard.school_class?.nama }}</strong></span>
              <span>Fase: <strong class="text-slate-800">{{ activeReportCard.fase }}</strong></span>
              <span>Periode: <strong class="text-slate-800">{{ activeReportCard.periode || activeReportCard.semester }}</strong></span>
              <span v-if="activeReportCard.submitter">Diajukan: <strong class="text-slate-800">{{ activeReportCard.submitter.nama }}</strong></span>
              <span v-if="activeReportCard.validator">Divalidasi: <strong class="text-slate-800">{{ activeReportCard.validator.nama }}</strong></span>
            </div>

            <!-- Catatan revisi alert -->
            <div v-if="activeReportCard.validation_notes" class="mt-3 p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800">
              <strong>Catatan dari Kepala Sekolah:</strong> {{ activeReportCard.validation_notes }}
            </div>

            <!-- KS VALIDASI CALLOUT BANNER -->
            <div v-if="auth.isAdmin && activeReportCard.status === 'menunggu_validasi'" class="mt-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl flex items-center justify-between gap-4 flex-wrap">
              <div class="flex items-center gap-3">
                <span class="text-2xl">📋</span>
                <div>
                  <div class="text-xs font-black text-blue-900">Menunggu Validasi & Persetujuan Kepala Sekolah</div>
                  <div class="text-[11px] text-blue-700 mt-0.5">
                    Silakan tinjau seluruh nilai dan capaian siswa di bawah ini. Tekan tombol <strong class="text-emerald-700">Setujui</strong> jika sudah sesuai, atau <strong class="text-rose-700">Minta Revisi</strong> jika ada catatan perbaikan.
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <button
                  @click="handleValidateSingle(activeReportCard.id)"
                  :disabled="validatingReport"
                  class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black transition-all shadow-md flex items-center gap-1.5"
                >
                  <span>✓</span>
                  <span>Setujui & Sahkan Rapor</span>
                </button>
                <button
                  @click="openRevisionModal(activeReportCard.id)"
                  class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-black transition-all shadow-sm flex items-center gap-1.5"
                >
                  <span>↩</span>
                  <span>Minta Revisi ke Wali Kelas</span>
                </button>
              </div>
            </div>
          </div>

          <!-- ACTIONS IN EDITOR -->
          <div class="flex items-center gap-2 flex-wrap">
            <!-- WALI KELAS AJUKAN -->
            <button
              v-if="activeReportCard.status === 'draft' || activeReportCard.status === 'perlu_revisi'"
              @click="submitSingleForValidation(activeReportCard.id)"
              :disabled="saving || submittingValidation"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1.5"
            >
              <span>🚀</span>
              <span>Ajukan ke KS</span>
            </button>

            <!-- KS VALIDASI & REVISI -->
            <template v-if="auth.isAdmin">
              <button
                v-if="activeReportCard.status !== 'final'"
                @click="handleValidateSingle(activeReportCard.id)"
                :disabled="validatingReport"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1.5"
              >
                <span>✓</span>
                <span>Validasi & Setujui</span>
              </button>

              <button
                @click="openRevisionModal(activeReportCard.id)"
                class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition-all"
              >
                Minta Revisi
              </button>
            </template>

            <!-- SAVE BUTTON -->
            <button
              @click="saveReportCard()"
              :disabled="saving"
              class="px-4 py-2 bg-slate-900 hover:bg-black text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1.5"
            >
              <svg v-if="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ saving ? 'Menyimpan...' : 'Simpan Draf' }}</span>
            </button>

            <!-- PRINT BUTTON -->
            <button
              @click="openPrintView(activeReportCard.student_id, activeReportCard.id)"
              class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
            >
              <span>🖨️</span>
              <span>Cetak</span>
            </button>
          </div>
        </div>
      </div>

      <!-- EDITOR NAVIGATION TABS -->
      <div class="flex border-b border-slate-200/80 gap-2 overflow-x-auto pb-1">
        <button
          type="button"
          @click="activeEditorTab = 'grades'"
          class="px-4 py-2 text-xs font-bold rounded-xl transition-all flex items-center gap-2"
          :class="activeEditorTab === 'grades' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
        >
          <span>📊</span>
          <span>Penilaian & Nilai ({{ activeReportCard.grades?.length || 0 }})</span>
        </button>

        <button
          v-if="activeReportCard.jenis_rapor === 'kurikulum_merdeka'"
          type="button"
          @click="activeEditorTab = 'koku'"
          class="px-4 py-2 text-xs font-bold rounded-xl transition-all flex items-center gap-2"
          :class="activeEditorTab === 'koku' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
        >
          <span>🌱</span>
          <span>Kokurikuler (P5)</span>
        </button>

        <button
          v-if="activeReportCard.jenis_rapor !== 'karakter_lifeskill'"
          type="button"
          @click="activeEditorTab = 'ekstra'"
          class="px-4 py-2 text-xs font-bold rounded-xl transition-all flex items-center gap-2"
          :class="activeEditorTab === 'ekstra' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
        >
          <span>⚽</span>
          <span>Ekstrakurikuler & Absensi</span>
        </button>

        <button
          type="button"
          @click="activeEditorTab = 'notes'"
          class="px-4 py-2 text-xs font-bold rounded-xl transition-all flex items-center gap-2"
          :class="activeEditorTab === 'notes' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
        >
          <span>📝</span>
          <span>Catatan Wali & Pengesahan</span>
        </button>
      </div>

      <!-- TAB 1: GRADES / PENILAIAN -->
      <div v-show="activeEditorTab === 'grades'" class="space-y-6">
        <!-- SUB-VIEW A: KURIKULUM MERDEKA -->
        <div v-if="activeReportCard.jenis_rapor === 'kurikulum_merdeka'" class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
              <h3 class="font-extrabold text-base text-slate-900">Nilai Capaian Intrakurikuler</h3>
              <p class="text-xs text-slate-500">Mata pelajaran standar Kurikulum Merdeka Fase {{ activeReportCard.fase }}</p>
            </div>
            <button
              type="button"
              @click="addGradeRow"
              class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold transition-all flex items-center gap-1"
            >
              <span>+</span>
              <span>Tambah Mapel</span>
            </button>
          </div>

          <div class="space-y-3">
            <div
              v-for="(g, idx) in activeReportCard.grades"
              :key="idx"
              class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-3"
            >
              <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 flex-1">
                  <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center text-xs font-black">
                    {{ idx + 1 }}
                  </span>
                  <input
                    v-model="g.nama_mapel"
                    type="text"
                    class="font-bold text-xs bg-white border border-slate-200 rounded-xl px-3 py-1.5 flex-1 text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                    placeholder="Nama Mata Pelajaran"
                  />
                  <select
                    v-model="g.kelompok"
                    class="text-xs bg-white border border-slate-200 rounded-xl px-2 py-1.5 text-slate-700 focus:outline-none"
                  >
                    <option value="Umum">Umum</option>
                    <option value="Agama">Agama</option>
                    <option value="Muatan Lokal">Muatan Lokal</option>
                  </select>
                </div>

                <div class="flex items-center gap-2">
                  <span class="text-xs font-bold text-slate-500">Nilai:</span>
                  <input
                    v-model.number="g.nilai_akhir"
                    type="number"
                    min="0"
                    max="100"
                    step="0.1"
                    placeholder="0-100"
                    class="w-20 text-center font-bold text-xs bg-white border border-slate-200 rounded-xl py-1.5 text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                  />
                  <button
                    type="button"
                    @click="removeGradeRow(idx)"
                    class="text-slate-400 hover:text-rose-600 p-1"
                    title="Hapus mata pelajaran ini"
                  >
                    &times;
                  </button>
                </div>
              </div>

              <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                  Capaian Kompetensi / Narasi
                </label>
                <textarea
                  v-model="g.capaian_kompetensi"
                  rows="2"
                  placeholder="Deskripsi pencapaian kompetensi siswa pada mapel ini..."
                  class="w-full text-xs bg-white border border-slate-200 rounded-xl p-2.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                ></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- SUB-VIEW B: RAPOR TRIWULAN TEMATIK (5 TEMA BESAR) -->
        <div v-else-if="activeReportCard.jenis_rapor === 'triwulan_tematik'" class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
              <h3 class="font-extrabold text-base text-slate-900">5 Klaster Tema Rapor Triwulan Tematik BIM</h3>
              <p class="text-xs text-slate-500">Sesuai format resmi penilaian tematik terpadu SDI Bina Insan Mandiri</p>
            </div>
          </div>

          <div class="space-y-4">
            <div
              v-for="(g, idx) in activeReportCard.grades"
              :key="idx"
              class="p-5 rounded-2xl bg-slate-50/80 border border-slate-200/90 space-y-3"
            >
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200/60 pb-3">
                <div class="space-y-0.5">
                  <div class="text-[10px] font-extrabold text-emerald-700 uppercase tracking-wider">
                    Tema {{ idx + 1 }}: {{ g.tema || 'Tematik' }}
                  </div>
                  <div class="font-black text-sm text-slate-900">
                    {{ g.nama_mapel }}
                  </div>
                </div>

                <div class="flex items-center gap-3">
                  <div class="flex items-center gap-1.5">
                    <span class="text-xs font-bold text-slate-600">Nilai Angka:</span>
                    <input
                      v-model.number="g.nilai_akhir"
                      @input="g.predikat = calculatePredicate(g.nilai_akhir)"
                      type="number"
                      min="0"
                      max="100"
                      class="w-20 text-center font-bold text-xs bg-white border border-slate-200 rounded-xl py-1.5 text-slate-900 focus:outline-none"
                      placeholder="0-100"
                    />
                  </div>

                  <div class="flex items-center gap-1.5">
                    <span class="text-xs font-bold text-slate-600">Predikat:</span>
                    <select
                      v-model="g.predikat"
                      class="font-black text-xs bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-emerald-800 focus:outline-none"
                    >
                      <option value="">-</option>
                      <option value="A">A (Sangat Baik)</option>
                      <option value="B">B (Baik)</option>
                      <option value="C">C (Cukup)</option>
                      <option value="D">D (Kurang)</option>
                    </select>
                  </div>
                </div>
              </div>

              <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">
                  Capaian Kompetensi / Tujuan Pembelajaran
                </label>
                <textarea
                  v-model="g.capaian_kompetensi"
                  rows="3"
                  placeholder="Narasi pencapaian ananda dalam klaster tema ini..."
                  class="w-full text-xs bg-white border border-slate-200 rounded-xl p-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 leading-relaxed"
                ></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- SUB-VIEW C: LAPORAN PENGUATAN KARAKTER & LIFE SKILL (17 BUTIR) -->
        <div v-else-if="activeReportCard.jenis_rapor === 'karakter_lifeskill'" class="space-y-6">
          <!-- INTERVAL NILAI REFERENSI -->
          <div class="bg-gradient-to-r from-emerald-600 to-teal-700 text-white rounded-3xl p-5 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div>
                <h4 class="font-black text-sm uppercase tracking-wider">Interval & Pedoman Nilai Karakter</h4>
                <p class="text-xs text-emerald-100 mt-0.5">Penilaian perkembangan sikap, pembiasaan ibadah, emosi, dan kecakapan hidup</p>
              </div>
              <div class="flex items-center gap-3 text-xs font-bold">
                <span class="bg-white/20 px-2.5 py-1 rounded-xl">A: >92-100 (Sangat Baik)</span>
                <span class="bg-white/20 px-2.5 py-1 rounded-xl">B: >83-92 (Baik)</span>
                <span class="bg-white/20 px-2.5 py-1 rounded-xl">C: 75-83 (Cukup)</span>
                <span class="bg-white/20 px-2.5 py-1 rounded-xl">D: &lt;75 (Kurang)</span>
              </div>
            </div>
          </div>

          <!-- GROUPED BY ASPEK -->
          <div
            v-for="(items, aspekTitle) in groupedCharacterGrades"
            :key="aspekTitle"
            class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4"
          >
            <div class="border-b border-slate-100 pb-3">
              <h3 class="font-black text-sm text-slate-900 uppercase tracking-wider text-emerald-800">
                {{ aspekTitle }}
              </h3>
            </div>

            <div class="space-y-3">
              <div
                v-for="(item, iIdx) in items"
                :key="iIdx"
                class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-2.5"
              >
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <div class="font-bold text-xs text-slate-900 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center text-[10px] font-black shrink-0">
                      {{ item.urutan || iIdx + 1 }}
                    </span>
                    <span>{{ item.sub_aspek || item.nama_mapel }}</span>
                  </div>

                  <div class="flex items-center gap-3 self-end sm:self-auto">
                    <div class="flex items-center gap-1.5">
                      <span class="text-xs font-bold text-slate-500">Nilai:</span>
                      <select
                        v-model="item.predikat"
                        class="font-black text-xs bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-emerald-800 focus:outline-none"
                      >
                        <option value="A">A (Sangat Baik)</option>
                        <option value="B">B (Baik)</option>
                        <option value="C">C (Cukup)</option>
                        <option value="D">D (Kurang)</option>
                      </select>
                    </div>
                  </div>
                </div>

                <div>
                  <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                    Pelaksanaan Implementasi
                  </label>
                  <textarea
                    v-model="item.capaian_kompetensi"
                    rows="2"
                    placeholder="Deskripsi bukti perilaku dan implementasi yang ditunjukkan siswa..."
                    class="w-full text-xs bg-white border border-slate-200 rounded-xl p-2.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                  ></textarea>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: KOKURIKULER P5 (KHUSUS KURIKULUM MERDEKA) -->
      <div v-show="activeEditorTab === 'koku'" class="space-y-6">
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
          <div class="border-b border-slate-100 pb-4">
            <h3 class="font-extrabold text-base text-slate-900">Projek Penguatan Profil Pelajar Pancasila (P5)</h3>
            <p class="text-xs text-slate-500">Tema dan asesmen perkembangan kokurikuler siswa</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tema Projek</label>
              <input
                v-model="activeReportCard.kokurikuler_tema"
                type="text"
                placeholder="misal: Sampahku Bersih Lingkunganku Sehat"
                class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Dimensi Profil</label>
              <input
                v-model="activeReportCard.kokurikuler_dimensi"
                type="text"
                placeholder="misal: Kolaborasi, Kemandirian, Kreativitas"
                class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none"
              />
            </div>

            <div class="md:col-span-2">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Capaian Akhir</label>
              <select
                v-model="activeReportCard.kokurikuler_capaian"
                class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none"
              >
                <option value="Mulai Berkembang">Mulai Berkembang (MB)</option>
                <option value="Sedang Berkembang">Sedang Berkembang (SB)</option>
                <option value="Berkembang Sesuai Harapan">Berkembang Sesuai Harapan (BSH)</option>
                <option value="Sangat Berkembang">Sangat Berkembang (SAB)</option>
              </select>
            </div>

            <div class="md:col-span-2">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Deskripsi Kokurikuler</label>
              <textarea
                v-model="activeReportCard.kokurikuler_deskripsi"
                rows="3"
                placeholder="Deskripsi pencapaian projek kokurikuler..."
                class="w-full bg-white border border-slate-200 rounded-xl p-3 text-xs text-slate-800 focus:outline-none"
              ></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 3: EKSTRAKURIKULER & ABSENSI -->
      <div v-show="activeEditorTab === 'ekstra'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <div>
                <h3 class="font-extrabold text-sm text-slate-900">Kegiatan Ekstrakurikuler</h3>
                <p class="text-xs text-slate-500">Pramuka, Renang, Silat, Mading, Public Speaking, dll.</p>
              </div>
              <button
                type="button"
                @click="addExtracurricularRow"
                class="px-3 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-xl text-xs font-bold transition-all"
              >
                + Tambah
              </button>
            </div>

            <div class="space-y-3">
              <div
                v-for="(e, eIdx) in activeReportCard.extracurriculars"
                :key="eIdx"
                class="flex items-center gap-3 p-3 bg-slate-50 rounded-2xl border border-slate-200 flex-wrap sm:flex-nowrap"
              >
                <div class="w-full sm:w-44">
                  <input
                    v-model="e.kegiatan"
                    type="text"
                    placeholder="Nama Ekstrakurikuler"
                    class="w-full bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-800 focus:outline-none"
                  />
                </div>
                <div class="w-32">
                  <select
                    v-model="e.predikat"
                    class="w-full bg-white border border-slate-200 rounded-xl px-2 py-1.5 text-xs font-bold text-slate-800 focus:outline-none"
                  >
                    <option value="A">A (Sangat Baik)</option>
                    <option value="B">B (Baik)</option>
                    <option value="C">C (Cukup)</option>
                    <option value="-">- (Belum Aktif)</option>
                  </select>
                </div>
                <div class="flex-1">
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

          <!-- Kehadiran -->
          <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <div>
                <h3 class="font-extrabold text-sm text-slate-900">Rekap Ketidakhadiran</h3>
                <p class="text-xs text-slate-500">Jumlah hari absen</p>
              </div>
              <button
                type="button"
                @click="syncAttendanceCurrent"
                :disabled="syncing"
                class="p-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-xl text-xs font-bold transition-all flex items-center gap-1"
                title="Tarik presensi dari sistem harian"
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
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-6">
          <div class="border-b border-slate-100 pb-4">
            <h3 class="font-extrabold text-base text-slate-900">Catatan Wali Kelas & Lembar Pengesahan</h3>
            <p class="text-xs text-slate-500">Pesan motivasi, titimangsa rapor, dan nama pejabat pengesah.</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Catatan Wali Kelas</label>
              <textarea
                v-model="activeReportCard.catatan_wali_kelas"
                rows="3"
                placeholder="Pesan bimbingan dan motivasi untuk ananda..."
                class="w-full bg-white border border-slate-200 rounded-xl p-3 text-xs text-slate-800 focus:outline-none"
              ></textarea>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Titimangsa Cetak</label>
              <input
                v-model="activeReportCard.tempat_tanggal"
                type="text"
                placeholder="misal: Purwokerto, 20 Desember 2025"
                class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Wali Kelas</label>
              <input
                v-model="activeReportCard.wali_kelas_nama"
                type="text"
                class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Kepala Sekolah</label>
              <input
                v-model="activeReportCard.kepala_sekolah_nama"
                type="text"
                class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">NIP Kepala Sekolah</label>
              <input
                v-model="activeReportCard.kepala_sekolah_nip"
                type="text"
                class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- VIEW 3: PRINT VIEWS -->
    <!-- (A) PRINT TEMATIK (ABRISAM 1 - TRIWULAN TEMATIK) -->
    <div v-else-if="currentView === 'print_tematik' && activeReportCard" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 space-y-4">
      <div class="flex items-center justify-between print:hidden bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <button @click="currentView = 'editor'" class="px-4 py-2 border border-slate-200 bg-white rounded-xl text-xs font-bold text-slate-700">
          ← Kembali ke Editor
        </button>
        <button @click="handlePrintBrowser" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-extrabold shadow-md flex items-center gap-2">
          <span>🖨️</span>
          <span>Cetak Dokumen (Ctrl + P)</span>
        </button>
      </div>

      <div class="bg-white p-8 sm:p-12 shadow-xl border border-slate-200 rounded-xl text-black font-serif print:shadow-none print:border-none print:p-0">
        <!-- School Header -->
        <div class="text-center pb-3 border-b-2 border-black mb-5">
          <h3 class="text-xs font-bold uppercase tracking-wider font-sans">Sekolah Dasar Islam Bina Insan Mandiri Purwokerto</h3>
          <h2 class="text-lg sm:text-xl font-black uppercase tracking-wider font-sans mt-1">
            LAPORAN HASIL BELAJAR - {{ (activeReportCard.periode || 'TRIWULAN').toUpperCase() }}
          </h2>
        </div>

        <!-- Student Meta Box -->
        <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-xs mb-5 font-sans">
          <div class="flex"><span class="w-24 font-bold">Nama</span><span class="mr-2">:</span><span class="font-bold">{{ activeReportCard.student?.nama_lengkap }}</span></div>
          <div class="flex"><span class="w-24 font-bold">Semester</span><span class="mr-2">:</span><span>{{ activeReportCard.semester }}</span></div>
          <div class="flex"><span class="w-24 font-bold">NIS / NISN</span><span class="mr-2">:</span><span>{{ activeReportCard.student?.nis || '-' }} / {{ activeReportCard.student?.nisn || '-' }}</span></div>
          <div class="flex"><span class="w-24 font-bold">Fase</span><span class="mr-2">:</span><span>{{ activeReportCard.fase || 'A' }}</span></div>
          <div class="flex"><span class="w-24 font-bold">Kelas</span><span class="mr-2">:</span><span>{{ activeReportCard.school_class?.nama || '-' }}</span></div>
          <div class="flex"><span class="w-24 font-bold">Tahun Ajaran</span><span class="mr-2">:</span><span>{{ activeReportCard.academic_year?.nama || '2025/2026' }}</span></div>
        </div>

        <!-- Tabel Muatan Tematik (5 Tema) -->
        <div class="mb-5">
          <table class="w-full border-collapse border border-black text-xs font-sans">
            <thead>
              <tr class="bg-slate-100 text-center font-bold">
                <th class="border border-black py-2 px-1 w-8">No</th>
                <th class="border border-black py-2 px-2 text-left w-36">Tema</th>
                <th class="border border-black py-2 px-2 text-left w-48">Muatan Pelajaran</th>
                <th class="border border-black py-2 px-1 w-14">Nilai</th>
                <th class="border border-black py-2 px-1 w-12">Predikat</th>
                <th class="border border-black py-2 px-2 text-left">Capaian Kompetensi / Tujuan Pembelajaran</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(g, idx) in activeReportCard.grades" :key="idx">
                <td class="border border-black py-2 px-1 text-center align-top">{{ idx + 1 }}</td>
                <td class="border border-black py-2 px-2 align-top font-bold text-[11px]">{{ g.tema || 'Tematik' }}</td>
                <td class="border border-black py-2 px-2 align-top text-[11px] font-semibold">{{ g.nama_mapel }}</td>
                <td class="border border-black py-2 px-1 text-center align-top font-bold">{{ g.nilai_akhir ?? '-' }}</td>
                <td class="border border-black py-2 px-1 text-center align-top font-bold text-emerald-800">{{ g.predikat || '-' }}</td>
                <td class="border border-black py-2 px-2 align-top text-[11px] leading-relaxed text-justify">
                  {{ g.capaian_kompetensi || '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Ekstrakurikuler -->
        <div class="mb-5 font-sans">
          <h4 class="font-bold text-xs uppercase tracking-wider mb-1.5 border-b border-black pb-1">
            Ekstrakurikuler
          </h4>
          <table class="w-full border-collapse border border-black text-xs">
            <thead>
              <tr class="bg-slate-100 text-center font-bold">
                <th class="border border-black py-1.5 px-2 w-8">No</th>
                <th class="border border-black py-1.5 px-3 text-left w-48">Ekstrakurikuler</th>
                <th class="border border-black py-1.5 px-2 text-center w-20">Predikat</th>
                <th class="border border-black py-1.5 px-3 text-left">Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(e, eIdx) in activeReportCard.extracurriculars" :key="eIdx">
                <td class="border border-black py-1.5 px-2 text-center">{{ eIdx + 1 }}</td>
                <td class="border border-black py-1.5 px-3 font-semibold">{{ e.kegiatan }}</td>
                <td class="border border-black py-1.5 px-2 text-center font-bold">{{ e.predikat }}</td>
                <td class="border border-black py-1.5 px-3 text-[11px]">{{ e.keterangan || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Catatan Guru & Absensi -->
        <div class="grid grid-cols-2 gap-4 mb-6 font-sans">
          <div class="border border-black p-3">
            <h5 class="font-bold text-xs uppercase mb-1">Catatan Guru</h5>
            <p class="text-xs italic leading-relaxed">
              {{ activeReportCard.catatan_wali_kelas || 'Pertahankan prestasi dan terus bersemangat dalam belajar. Baarakallahu fiik.' }}
            </p>
          </div>

          <div class="border border-black p-3">
            <h5 class="font-bold text-xs uppercase mb-1">Absensi</h5>
            <div class="text-xs space-y-0.5">
              <div class="flex justify-between"><span>Sakit:</span><strong>{{ activeReportCard.sakit }} hari</strong></div>
              <div class="flex justify-between"><span>Izin:</span><strong>{{ activeReportCard.izin }} hari</strong></div>
              <div class="flex justify-between"><span>Tanpa Keterangan:</span><strong>{{ activeReportCard.alpa }} hari</strong></div>
            </div>
          </div>
        </div>

        <!-- Lembar Tanda Tangan (3 Kolom) -->
        <div class="font-sans text-xs pt-4">
          <div class="text-right mb-6">{{ activeReportCard.tempat_tanggal || 'Purwokerto' }}</div>
          <div class="grid grid-cols-3 text-center gap-4">
            <div>
              <p>Mengetahui,</p>
              <p class="font-bold">Orang Tua / Wali</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-36 mx-auto">................................</p>
            </div>
            <div>
              <p>&nbsp;</p>
              <p class="font-bold">Wali Kelas</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-44 mx-auto font-bold">{{ activeReportCard.wali_kelas_nama || 'Wali Kelas' }}</p>
            </div>
            <div>
              <p>Mengetahui,</p>
              <p class="font-bold">Kepala Sekolah</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-48 mx-auto font-bold">{{ activeReportCard.kepala_sekolah_nama || 'Kepala Sekolah' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- (B) PRINT KARAKTER & LIFE SKILL (ABRISAM 2) -->
    <div v-else-if="currentView === 'print_karakter' && activeReportCard" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 space-y-4">
      <div class="flex items-center justify-between print:hidden bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <button @click="currentView = 'editor'" class="px-4 py-2 border border-slate-200 bg-white rounded-xl text-xs font-bold text-slate-700">
          ← Kembali ke Editor
        </button>
        <button @click="handlePrintBrowser" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-extrabold shadow-md flex items-center gap-2">
          <span>🖨️</span>
          <span>Cetak Dokumen (Ctrl + P)</span>
        </button>
      </div>

      <div class="bg-white p-8 sm:p-12 shadow-xl border border-slate-200 rounded-xl text-black font-serif print:shadow-none print:border-none print:p-0">
        <!-- Title -->
        <div class="text-center pb-3 border-b-2 border-black mb-5">
          <h3 class="text-xs font-bold uppercase tracking-wider font-sans">Sekolah Dasar Islam Bina Insan Mandiri Purwokerto</h3>
          <h2 class="text-base sm:text-lg font-black uppercase tracking-wider font-sans mt-1">
            LAPORAN PERKEMBANGAN PENGUATAN KARAKTER DAN LIFE SKILL
          </h2>
          <p class="text-xs font-bold tracking-widest uppercase font-sans mt-0.5">
            {{ activeReportCard.periode || 'TRIWULAN I' }}
          </p>
        </div>

        <!-- Student Meta -->
        <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-xs mb-5 font-sans">
          <div class="flex"><span class="w-24 font-bold">Nama</span><span class="mr-2">:</span><span class="font-bold">{{ activeReportCard.student?.nama_lengkap }}</span></div>
          <div class="flex"><span class="w-24 font-bold">Semester</span><span class="mr-2">:</span><span>{{ activeReportCard.semester }}</span></div>
          <div class="flex"><span class="w-24 font-bold">NIS / NISN</span><span class="mr-2">:</span><span>{{ activeReportCard.student?.nis || '-' }} / {{ activeReportCard.student?.nisn || '-' }}</span></div>
          <div class="flex"><span class="w-24 font-bold">Fase</span><span class="mr-2">:</span><span>{{ activeReportCard.fase || 'A' }}</span></div>
          <div class="flex"><span class="w-24 font-bold">Kelas</span><span class="mr-2">:</span><span>{{ activeReportCard.school_class?.nama || '-' }}</span></div>
          <div class="flex"><span class="w-24 font-bold">Tahun Ajaran</span><span class="mr-2">:</span><span>{{ activeReportCard.academic_year?.nama || '2025/2026' }}</span></div>
        </div>

        <!-- Tabel 17 Butir Karakter & Life Skill -->
        <div class="mb-5">
          <table class="w-full border-collapse border border-black text-xs font-sans">
            <thead>
              <tr class="bg-slate-100 text-center font-bold">
                <th class="border border-black py-2 px-1 w-8">No</th>
                <th class="border border-black py-2 px-2 text-left w-44">Aspek Penguatan</th>
                <th class="border border-black py-2 px-2 text-left w-48">Butir Karakter & Life Skill</th>
                <th class="border border-black py-2 px-2 text-left">Pelaksanaan Implementasi</th>
                <th class="border border-black py-2 px-1 w-12 text-center">Nilai</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(g, idx) in activeReportCard.grades" :key="idx">
                <td class="border border-black py-2 px-1 text-center align-top">{{ idx + 1 }}</td>
                <td class="border border-black py-2 px-2 align-top font-bold text-[10px] text-slate-800">{{ g.aspek || '-' }}</td>
                <td class="border border-black py-2 px-2 align-top font-semibold text-[11px]">{{ g.sub_aspek || g.nama_mapel }}</td>
                <td class="border border-black py-2 px-2 align-top text-[11px] leading-relaxed text-justify">
                  {{ g.capaian_kompetensi || '-' }}
                </td>
                <td class="border border-black py-2 px-1 text-center align-top font-black text-emerald-800">{{ g.predikat || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pedoman Interval Nilai -->
        <div class="mb-6 font-sans">
          <h5 class="font-bold text-[11px] uppercase mb-1">INTERVAL NILAI:</h5>
          <table class="w-64 border-collapse border border-black text-[10px]">
            <thead>
              <tr class="bg-slate-100 text-center font-bold">
                <th class="border border-black py-1 px-1">Interval Nilai</th>
                <th class="border border-black py-1 px-1">Predikat</th>
                <th class="border border-black py-1 px-1">Keterangan</th>
              </tr>
            </thead>
            <tbody class="text-center">
              <tr><td class="border border-black py-0.5 px-1">&gt; 92 - 100</td><td class="border border-black font-bold">A</td><td class="border border-black">Sangat Baik</td></tr>
              <tr><td class="border border-black py-0.5 px-1">&gt; 83 - 92</td><td class="border border-black font-bold">B</td><td class="border border-black">Baik</td></tr>
              <tr><td class="border border-black py-0.5 px-1">75 - 83</td><td class="border border-black font-bold">C</td><td class="border border-black">Cukup</td></tr>
              <tr><td class="border border-black py-0.5 px-1">&lt; 75</td><td class="border border-black font-bold">D</td><td class="border border-black">Kurang</td></tr>
            </tbody>
          </table>
        </div>

        <!-- Tanda Tangan -->
        <div class="font-sans text-xs pt-3">
          <div class="text-right mb-6">{{ activeReportCard.tempat_tanggal || 'Purwokerto' }}</div>
          <div class="grid grid-cols-3 text-center gap-4">
            <div>
              <p>Mengetahui,</p>
              <p class="font-bold">Orang Tua / Wali</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-36 mx-auto">................................</p>
            </div>
            <div>
              <p>&nbsp;</p>
              <p class="font-bold">Wali Kelas</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-44 mx-auto font-bold">{{ activeReportCard.wali_kelas_nama || 'Wali Kelas' }}</p>
            </div>
            <div>
              <p>Mengetahui,</p>
              <p class="font-bold">Kepala Sekolah</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-48 mx-auto font-bold">{{ activeReportCard.kepala_sekolah_nama || 'Kepala Sekolah' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- (C) PRINT KURIKULUM MERDEKA & IDENTITAS (EXISTING FORMAL REPORT) -->
    <div v-else-if="currentView === 'print_raport' && activeReportCard" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 space-y-4">
      <div class="flex items-center justify-between print:hidden bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <button @click="currentView = 'editor'" class="px-4 py-2 border border-slate-200 bg-white rounded-xl text-xs font-bold text-slate-700">
          ← Kembali ke Editor
        </button>
        <button @click="handlePrintBrowser" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-extrabold shadow-md flex items-center gap-2">
          <span>🖨️</span>
          <span>Cetak Dokumen (Ctrl + P)</span>
        </button>
      </div>

      <div class="bg-white p-8 sm:p-12 shadow-xl border border-slate-200 rounded-xl text-black font-serif print:shadow-none print:border-none print:p-0">
        <div class="text-center pb-4 border-b-2 border-black mb-6">
          <h2 class="text-xl sm:text-2xl font-bold uppercase tracking-wider">LAPORAN HASIL BELAJAR</h2>
          <p class="text-xs tracking-widest uppercase font-sans mt-0.5 font-semibold">
            SD ISLAM BINA INSAN MANDIRI PURWOKERTO
          </p>
        </div>

        <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-xs mb-6 font-sans">
          <div class="flex"><span class="w-28 font-semibold">Nama Murid</span><span class="mr-2">:</span><span class="font-bold uppercase">{{ activeReportCard.student?.nama_lengkap }}</span></div>
          <div class="flex"><span class="w-28 font-semibold">Kelas</span><span class="mr-2">:</span><span>{{ activeReportCard.school_class?.nama || '-' }}</span></div>
          <div class="flex"><span class="w-28 font-semibold">NISN</span><span class="mr-2">:</span><span>{{ activeReportCard.student?.nisn || '-' }}</span></div>
          <div class="flex"><span class="w-28 font-semibold">Fase</span><span class="mr-2">:</span><span>{{ activeReportCard.fase || 'A' }}</span></div>
          <div class="flex"><span class="w-28 font-semibold">Sekolah</span><span class="mr-2">:</span><span>{{ schoolProfile?.nama_sekolah || 'SD Islam Bina Insan Mandiri' }}</span></div>
          <div class="flex"><span class="w-28 font-semibold">Semester</span><span class="mr-2">:</span><span>{{ activeReportCard.semester }}</span></div>
          <div class="flex"><span class="w-28 font-semibold">Alamat</span><span class="mr-2">:</span><span class="truncate">{{ activeReportCard.student?.alamat_jalan || schoolProfile?.alamat || 'Purwokerto' }}</span></div>
          <div class="flex"><span class="w-28 font-semibold">Tahun Ajaran</span><span class="mr-2">:</span><span>{{ activeReportCard.academic_year?.nama || '2025/2026' }}</span></div>
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

        <!-- Ketidakhadiran & Catatan -->
        <div class="grid grid-cols-2 gap-6 mb-8 font-sans">
          <div>
            <h4 class="font-bold text-xs uppercase tracking-wider mb-2 border-b border-black pb-1">
              D. Ketidakhadiran
            </h4>
            <table class="w-full border-collapse border border-black text-xs">
              <tbody>
                <tr>
                  <td class="border border-black py-1 px-3 w-36">Sakit</td>
                  <td class="border border-black py-1 px-3">: {{ activeReportCard.sakit }} hari</td>
                </tr>
                <tr>
                  <td class="border border-black py-1 px-3">Izin</td>
                  <td class="border border-black py-1 px-3">: {{ activeReportCard.izin }} hari</td>
                </tr>
                <tr>
                  <td class="border border-black py-1 px-3">Tanpa Keterangan</td>
                  <td class="border border-black py-1 px-3">: {{ activeReportCard.alpa }} hari</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div>
            <h4 class="font-bold text-xs uppercase tracking-wider mb-2 border-b border-black pb-1">
              E. Catatan Wali Kelas
            </h4>
            <div class="border border-black p-3 text-xs italic leading-relaxed min-h-[75px]">
              {{ activeReportCard.catatan_wali_kelas || 'Pertahankan prestasimu dan terus bersemangat dalam menuntut ilmu.' }}
            </div>
          </div>
        </div>

        <!-- Tanda Tangan -->
        <div class="font-sans text-xs pt-4">
          <div class="text-right mb-6">{{ activeReportCard.tempat_tanggal || 'Purwokerto' }}</div>
          <div class="grid grid-cols-3 text-center gap-4">
            <div>
              <p>Mengetahui,</p>
              <p class="font-bold">Orang Tua / Wali</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-36 mx-auto">................................</p>
            </div>
            <div>
              <p>&nbsp;</p>
              <p class="font-bold">Wali Kelas</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-44 mx-auto font-bold">{{ activeReportCard.wali_kelas_nama || 'Wali Kelas' }}</p>
            </div>
            <div>
              <p>Mengetahui,</p>
              <p class="font-bold">Kepala Sekolah</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-48 mx-auto font-bold">{{ activeReportCard.kepala_sekolah_nama || 'Kepala Sekolah' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL CATATAN REVISI (KEPALA SEKOLAH) -->
    <div
      v-if="showRevisionModal"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2 text-rose-600 font-black">
            <span>⚠️</span>
            <h3 class="text-base text-slate-900 font-extrabold">Kembalikan Rapor untuk Direvisi</h3>
          </div>
          <button @click="showRevisionModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">
            &times;
          </button>
        </div>

        <p class="text-xs text-slate-600 leading-relaxed">
          Tuliskan catatan perbaikan atau koreksi nilai untuk Wali Kelas agar dapat diperbaiki sebelum divalidasi kembali.
        </p>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Catatan Revisi dari Kepala Sekolah
          </label>
          <textarea
            v-model="revisionNoteInput"
            rows="4"
            placeholder="misal: Mohon lengkapi narasi capaian pada tema Art & Language, serta sesuaikan nilai muatan lokal."
            class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500/20"
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <button
            type="button"
            @click="showRevisionModal = false"
            class="px-4 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl text-xs font-bold"
          >
            Batal
          </button>
          <button
            type="button"
            @click="handleSendRevision"
            :disabled="validatingReport"
            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-md"
          >
            {{ validatingReport ? 'Mengirim...' : 'Kirim Catatan Revisi' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
@media print {
  @page {
    size: A4 portrait;
    margin: 15mm 15mm 15mm 15mm;
  }
  body {
    background: white !important;
    color: black !important;
  }
}
</style>
