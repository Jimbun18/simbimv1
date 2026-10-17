<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

interface SummaryStats {
  total_record: number
  hadir: number
  izin: number
  sakit: number
  alpa: number
  persentase_kehadiran: number
}

const auth = useAuthStore()
const route = useRoute()
const activeTab = ref<'class_recap' | 'student_recap' | 'teachers' | 'manual_input' | 'kbm'>('class_recap')
const loading = ref(false)
const message = ref<{ type: 'success' | 'error' | 'warning'; text: string } | null>(null)

// Date & Global Filter
const selectedDate = ref(new Date().toISOString().split('T')[0])
const selectedClassFilter = ref('')
const selectedStatusFilter = ref('')
const studentSearchQuery = ref('')

// Master Data Dropdowns
const classes = ref<any[]>([])
const teachersList = ref<any[]>([])
const subjects = ref<any[]>([])

// ==========================================
// 0. REKAP PRESENSI KELAS & KBM (LEDGER & RAPOR)
// ==========================================
const ledgerClassId = ref('')
const ledgerStartDate = ref('')
const ledgerEndDate = ref('')
const ledgerSubjectId = ref('')
const ledgerSearchQuery = ref('')
const classRecapData = ref<any | null>(null)
const loadingClassRecap = ref(false)
const exportingRecapExcel = ref(false)
const selectedStudentDetail = ref<any | null>(null)
const openStudentDetail = (stu: any) => {
  selectedStudentDetail.value = stu
}

// ==========================================
// 1. REKAP PRESENSI SISWA STATE (HASIL KBM)
// ==========================================
const studentAttendanceRecords = ref<any[]>([])
const studentAttendanceSummary = ref<SummaryStats>({
  total_record: 0,
  hadir: 0,
  izin: 0,
  sakit: 0,
  alpa: 0,
  persentase_kehadiran: 0,
})
const studentPagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 25,
})

// ==========================================
// 2. INPUT / KOREKSI PRESENSI MANUAL
// ==========================================
const inputClassId = ref('')
const inputStudentList = ref<any[]>([])
const inputAttendances = ref<Record<number, 'hadir' | 'izin' | 'sakit' | 'alpa'>>({})
const inputNotes = ref<Record<number, string>>({})
const savingStudentAttendance = ref(false)

// ==========================================
// 3. REKAP PRESENSI GURU & TENDIK
// ==========================================
const teacherAttendances = ref<any[]>([])

// ==========================================
// 4. GALERI BUKTI KBM & JURNAL MENGAJAR
// ==========================================
const kbmEvidences = ref<any[]>([])
const selectedEvidenceImage = ref<string | null>(null)
const selectedEvidenceItem = ref<any | null>(null)

// Helper Notification
const showMessage = (text: string, type: 'success' | 'error' | 'warning' = 'success') => {
  message.value = { type, text }
  setTimeout(() => (message.value = null), 4500)
}

const getPhotoUrl = (path?: string | null) => {
  if (!path) return ''
  return path.startsWith('http') ? path : `/storage/${path}`
}

const getInitials = (name?: string) => {
  if (!name) return '?'
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
}

const isMyHomeroom = (classId: number | string) => {
  return auth.homeroomClasses.some(h => String(h.id) === String(classId))
}

const selectMyHomeroom = () => {
  if (auth.primaryHomeroomClass) {
    selectedClassFilter.value = String(auth.primaryHomeroomClass.id)
    inputClassId.value = String(auth.primaryHomeroomClass.id)
    ledgerClassId.value = String(auth.primaryHomeroomClass.id)
    if (activeTab.value === 'class_recap') {
      fetchClassRecap()
    } else {
      fetchStudentAttendanceRecap()
      loadStudentsForManualInput()
    }
  }
}

// Inisialisasi Rentang Tanggal Ledger
const initLedgerDates = () => {
  const now = new Date()
  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  ledgerStartDate.value = `${year}-${month}-01`
  ledgerEndDate.value = now.toISOString().split('T')[0]
}

// Preset Rentang Tanggal Cepat
const setLedgerDatePreset = (preset: 'this_month' | 'last_month' | 'this_semester' | 'last_30_days') => {
  const now = new Date()
  const year = now.getFullYear()
  const month = now.getMonth() // 0-indexed

  if (preset === 'this_month') {
    ledgerStartDate.value = `${year}-${String(month + 1).padStart(2, '0')}-01`
    ledgerEndDate.value = now.toISOString().split('T')[0]
  } else if (preset === 'last_month') {
    const prevMonthDate = new Date(year, month - 1, 1)
    const prevYear = prevMonthDate.getFullYear()
    const prevMonth = prevMonthDate.getMonth()
    const lastDay = new Date(prevYear, prevMonth + 1, 0).getDate()
    ledgerStartDate.value = `${prevYear}-${String(prevMonth + 1).padStart(2, '0')}-01`
    ledgerEndDate.value = `${prevYear}-${String(prevMonth + 1).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`
  } else if (preset === 'last_30_days') {
    const d = new Date()
    d.setDate(d.getDate() - 30)
    ledgerStartDate.value = d.toISOString().split('T')[0]
    ledgerEndDate.value = now.toISOString().split('T')[0]
  } else if (preset === 'this_semester') {
    if (month >= 6) {
      ledgerStartDate.value = `${year}-07-01`
      ledgerEndDate.value = `${year}-12-31`
    } else {
      ledgerStartDate.value = `${year}-01-01`
      ledgerEndDate.value = `${year}-06-30`
    }
  }
  fetchClassRecap()
}

// Fetch Rekap Presensi Per Kelas & Mapel
const fetchClassRecap = async () => {
  if (!ledgerClassId.value) return
  loadingClassRecap.value = true
  try {
    const params: any = {
      class_id: ledgerClassId.value,
      start_date: ledgerStartDate.value,
      end_date: ledgerEndDate.value,
    }
    if (ledgerSubjectId.value) {
      params.subject_id = ledgerSubjectId.value
    }
    if (ledgerSearchQuery.value.trim()) {
      params.search = ledgerSearchQuery.value.trim()
    }

    const res = await api.get('/attendance/students/recap-class', { params })
    if (res.data.success) {
      classRecapData.value = res.data.data
    }
  } catch (err: any) {
    console.error('Gagal mengambil rekap presensi kelas:', err)
    showMessage(err.response?.data?.message || 'Gagal memuat rekap presensi kelas', 'error')
  } finally {
    loadingClassRecap.value = false
  }
}

// Export Rekap Presensi Kelas ke Excel (.xlsx)
const exportClassRecapExcel = async () => {
  if (!ledgerClassId.value) return
  exportingRecapExcel.value = true
  try {
    const params: any = {
      class_id: ledgerClassId.value,
      start_date: ledgerStartDate.value,
      end_date: ledgerEndDate.value,
    }
    if (ledgerSubjectId.value) {
      params.subject_id = ledgerSubjectId.value
    }

    const res = await api.get('/attendance/students/recap-class/export', {
      params,
      responseType: 'blob'
    })

    const className = classRecapData.value?.class?.nama || 'Kelas'
    const filename = `Rekap_Presensi_${className.replace(/[^a-zA-Z0-9_-]/g, '_')}_${new Date().toISOString().split('T')[0]}.xlsx`

    const url = window.URL.createObjectURL(new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)

    showMessage('Berkas Excel rekap presensi berhasil diunduh.', 'success')
  } catch (err: any) {
    console.error('Gagal mengekspor berkas Excel:', err)
    showMessage('Terjadi kendala saat mengekspor data ke Excel.', 'error')
  } finally {
    exportingRecapExcel.value = false
  }
}

// ==========================================
// API FETCH METHODS
// ==========================================

// 1. Fetch Student Attendance Recap (Hasil absensi dari guru / masuk kelas)
const fetchStudentAttendanceRecap = async (page = 1) => {
  loading.value = true
  try {
    const params: any = {
      tanggal: selectedDate.value,
      page,
      per_page: studentPagination.value.per_page,
    }

    if (selectedClassFilter.value) {
      params.class_id = selectedClassFilter.value
    }
    if (selectedStatusFilter.value) {
      params.status = selectedStatusFilter.value
    }
    if (studentSearchQuery.value.trim()) {
      params.search = studentSearchQuery.value.trim()
    }

    const res = await api.get('/attendance/students', { params })
    if (res.data.success) {
      studentAttendanceRecords.value = res.data.data || []
      studentAttendanceSummary.value = res.data.summary || {
        total_record: 0,
        hadir: 0,
        izin: 0,
        sakit: 0,
        alpa: 0,
        persentase_kehadiran: 0,
      }
      if (res.data.meta) {
        studentPagination.value = {
          current_page: res.data.meta.current_page,
          last_page: res.data.meta.last_page,
          total: res.data.meta.total,
          per_page: res.data.meta.per_page,
        }
      }
    }
  } catch (err: any) {
    console.error('Gagal mengambil rekap presensi siswa', err)
    showMessage(err.response?.data?.message || 'Gagal memuat rekap presensi siswa', 'error')
  } finally {
    loading.value = false
  }
}

// 1.5. Fetch Homeroom Attendance Monitoring Status (Monitoring Wali Kelas untuk Admin & Kepala Sekolah)
const homeroomMonitoringData = ref<any | null>(null)
const loadingHomeroomMonitoring = ref(false)

const fetchHomeroomMonitoring = async () => {
  if (!auth.isAdmin) return
  loadingHomeroomMonitoring.value = true
  try {
    const res = await api.get('/attendance/students/homeroom-monitoring', {
      params: { tanggal: selectedDate.value }
    })
    if (res.data.success) {
      homeroomMonitoringData.value = res.data.data
    }
  } catch (err: any) {
    console.error('Gagal mengambil data monitoring presensi rombel:', err)
  } finally {
    loadingHomeroomMonitoring.value = false
  }
}

const filterByHomeroomClass = (classId: number) => {
  selectedClassFilter.value = String(classId)
  fetchStudentAttendanceRecap(1)
  const el = document.getElementById('student-recap-table')
  if (el) el.scrollIntoView({ behavior: 'smooth' })
}

// 2. Fetch Teacher Attendance (Presensi Guru & Tendik)
const fetchTeacherAttendances = async () => {
  loading.value = true
  try {
    const res = await api.get('/attendance/teachers', {
      params: { tanggal: selectedDate.value }
    })
    teacherAttendances.value = res.data.data?.data || res.data.data || []
  } catch (err: any) {
    console.error('Failed to fetch teacher attendance:', err)
  } finally {
    loading.value = false
  }
}

// 3. Approve leave for teacher
const approveLeave = async (id: number, status: 'disetujui' | 'ditolak') => {
  try {
    await api.post(`/attendance/teachers/${id}/approve-leave`, { status_approval: status })
    showMessage(`Pengajuan cuti/izin berhasil di-${status}`)
    fetchTeacherAttendances()
  } catch (err: any) {
    showMessage(err.response?.data?.message || 'Gagal mengubah status izin', 'error')
  }
}

// 4. Fetch KBM Evidence & Teaching Journals (Foto selfie/kegiatan & materi)
const fetchKbmEvidence = async () => {
  loading.value = true
  try {
    const params: any = {
      tanggal: selectedDate.value,
    }
    if (selectedClassFilter.value) {
      params.class_id = selectedClassFilter.value
    }

    const res = await api.get('/attendance/evidence', { params })
    kbmEvidences.value = res.data.data || []
  } catch (err: any) {
    console.error('Failed to fetch KBM evidence:', err)
  } finally {
    loading.value = false
  }
}

// 5. Fetch Master Classes & Subjects
const fetchMasterData = async () => {
  try {
    const [cRes, sRes, tRes] = await Promise.all([
      api.get('/classes?all=true'),
      api.get('/subjects?all=true'),
      auth.isAdmin ? api.get('/teachers') : Promise.resolve({ data: { data: [] } }),
    ])
    classes.value = cRes.data.data || []
    subjects.value = sRes.data.data || []
    teachersList.value = tRes.data.data || []

    if (auth.isWaliKelas && auth.primaryHomeroomClass) {
      inputClassId.value = String(auth.primaryHomeroomClass.id)
      selectedClassFilter.value = String(auth.primaryHomeroomClass.id)
      ledgerClassId.value = String(auth.primaryHomeroomClass.id)
    } else if (classes.value.length > 0) {
      inputClassId.value = String(classes.value[0].id)
      ledgerClassId.value = String(classes.value[0].id)
    }

    if (ledgerClassId.value) {
      fetchClassRecap()
    }

    if (inputClassId.value && (auth.isAdmin || auth.isWaliKelas)) {
      loadStudentsForManualInput()
    }
  } catch (err) {
    console.error('Failed to load master data:', err)
  }
}

// 6. Load Students for Daily Homeroom Attendance (Pre-populates existing status!)
const manualAttendanceMeta = ref<any>(null)
const isManualAttendanceLocked = computed(() => {
  return Boolean(manualAttendanceMeta.value?.already_recorded && !auth.isAdmin)
})

const loadStudentsForManualInput = async () => {
  if (!inputClassId.value) return
  loading.value = true
  try {
    const res = await api.get('/attendance/students/session-sheet', {
      params: {
        class_id: inputClassId.value,
        tanggal: selectedDate.value,
      }
    })

    if (res.data.success && res.data.data) {
      const data = res.data.data
      manualAttendanceMeta.value = {
        already_recorded: data.already_recorded,
        recorded_by: data.recorded_by,
        recorded_at: data.recorded_at,
        updated_at: data.updated_at,
        summary: data.summary,
      }
      inputStudentList.value = data.students || []
      inputAttendances.value = {}
      inputNotes.value = {}

      inputStudentList.value.forEach((s: any) => {
        // Pre-populate with existing status from database ('hadir', 'izin', 'sakit', 'alpa')
        inputAttendances.value[s.student_id] = s.status || 'hadir'
        inputNotes.value[s.student_id] = s.keterangan || ''
      })
    }
  } catch (err: any) {
    console.error('Failed to load students for homeroom attendance:', err)
  } finally {
    loading.value = false
  }
}

// 7. Submit Daily Homeroom Attendance (Dilakukan 1x sehari oleh Wali Kelas)
const submitManualStudentAttendance = async () => {
  if (isManualAttendanceLocked.value) {
    showMessage('Presensi hari ini sudah divalidasi dan tidak dapat diubah kembali oleh Wali Kelas. Tombol telah dinonaktifkan.', 'error')
    return
  }
  if (!inputClassId.value) {
    showMessage('Pilih rombel kelas terlebih dahulu', 'error')
    return
  }
  savingStudentAttendance.value = true
  try {
    const attendances = inputStudentList.value.map((s: any) => ({
      student_id: s.student_id,
      status: inputAttendances.value[s.student_id] || 'hadir',
      keterangan: inputNotes.value[s.student_id] || null,
    }))

    const payload: any = {
      class_id: inputClassId.value,
      tanggal: selectedDate.value,
      attendances,
    }

    await api.post('/attendance/students', payload)

    showMessage('Presensi harian siswa berhasil disimpan oleh Wali Kelas!', 'success')
    // Refresh student recap tab
    fetchStudentAttendanceRecap()
  } catch (err: any) {
    console.error('Gagal menyimpan presensi siswa', err)
    showMessage(err.response?.data?.message || 'Gagal menyimpan presensi siswa', 'error')
  } finally {
    savingStudentAttendance.value = false
  }
}

// Set All Status in Manual Input Tab
const setAllManualStatus = (status: 'hadir' | 'izin' | 'sakit' | 'alpa') => {
  inputStudentList.value.forEach((s: any) => {
    inputAttendances.value[s.student_id] = status
  })
}

// Open Evidence Fullscreen Viewer
const openEvidenceViewer = (ev: any) => {
  selectedEvidenceItem.value = ev
  selectedEvidenceImage.value = ev.foto_url || getPhotoUrl(ev.photo_path)
}

// On Date / Filter Change Handler
const onDateOrFilterChange = () => {
  if (auth.isAdmin) {
    fetchHomeroomMonitoring()
  }
  if (activeTab.value === 'class_recap') {
    fetchClassRecap()
  } else if (activeTab.value === 'student_recap') {
    fetchStudentAttendanceRecap(1)
  } else if (activeTab.value === 'teachers') {
    fetchTeacherAttendances()
  } else if (activeTab.value === 'kbm') {
    fetchKbmEvidence()
  } else if (activeTab.value === 'manual_input') {
    loadStudentsForManualInput()
  }
}

watch(() => route.query.tab, (newTab) => {
  if (newTab && ['class_recap', 'student_recap', 'teachers', 'manual_input', 'kbm'].includes(newTab as string)) {
    if (activeTab.value !== newTab) {
      activeTab.value = newTab as any
      if (newTab === 'class_recap') fetchClassRecap()
      else if (newTab === 'student_recap') {
        fetchStudentAttendanceRecap(1)
        if (auth.isAdmin) fetchHomeroomMonitoring()
      }
      else if (newTab === 'teachers') fetchTeacherAttendances()
      else if (newTab === 'kbm') fetchKbmEvidence()
      else if (newTab === 'manual_input') loadStudentsForManualInput()
    }
  }
})

onMounted(async () => {
  if (route.query.tab && ['class_recap', 'student_recap', 'teachers', 'manual_input', 'kbm'].includes(route.query.tab as string)) {
    activeTab.value = route.query.tab as any
  }
  initLedgerDates()
  await fetchMasterData()
  if (auth.isAdmin) {
    fetchHomeroomMonitoring()
  }
  if (activeTab.value === 'class_recap') {
    await fetchClassRecap()
  } else if (activeTab.value === 'student_recap') {
    await fetchStudentAttendanceRecap()
  } else if (activeTab.value === 'teachers' && auth.isAdmin) {
    await fetchTeacherAttendances()
  } else if (activeTab.value === 'kbm') {
    await fetchKbmEvidence()
  } else if (activeTab.value === 'manual_input') {
    await loadStudentsForManualInput()
  }
})
</script>

<template>
  <div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Header -->
    <div class="bg-white p-6 sm:p-7 rounded-3xl border border-slate-100 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-5">
      <div class="space-y-1">
        <div class="flex items-center gap-2">
          <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[11px] font-bold rounded-full uppercase tracking-wider">
            Monitoring & Rekap
          </span>
          <span class="text-xs text-slate-400">
            Terhubung langsung dengan sesi "Masuk Kelas" guru
          </span>
        </div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">
          Rekap Presensi & KBM
        </h1>
        <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
          {{ auth.isAdmin ? 'Pusat pemantauan absensi terpadu: pantau kehadiran siswa dari hasil mengajar guru, verifikasi foto bukti KBM tatap muka, dan kelola presensi pendidik.' : 'Pusat pemantauan absensi terpadu: pantau kehadiran siswa dari hasil mengajar guru, verifikasi foto bukti KBM tatap muka, dan rekapitulasi presensi rombel.' }}
        </p>
      </div>

      <!-- Date Filter in Top Bar -->
      <div class="flex items-center gap-2.5 bg-slate-50 p-2 rounded-2xl border border-slate-200/80 shrink-0">
        <label class="text-xs font-bold text-slate-600 pl-2">Tanggal:</label>
        <input 
          type="date" 
          v-model="selectedDate" 
          @change="onDateOrFilterChange"
          class="border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-semibold bg-white text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
        />
        <button
          @click="onDateOrFilterChange"
          class="p-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition shadow-xs"
          title="Muat Ulang Data"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Alert Message -->
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
        :class="message.type === 'success' ? 'bg-emerald-50 text-emerald-900 border-emerald-200' : 'bg-rose-50 text-rose-900 border-rose-200'"
        class="p-4 rounded-2xl border text-xs font-medium flex items-center justify-between shadow-sm transition-all"
      >
        <div class="flex items-center gap-2">
          <span class="text-base">{{ message.type === 'success' ? '✓' : '⚠️' }}</span>
          <span>{{ message.text }}</span>
        </div>
        <button @click="message = null" class="font-bold opacity-60 hover:opacity-100 text-sm">&times;</button>
      </div>
    </transition>

    <!-- Alert Khusus Guru Jika Belum Memiliki Kelas Binaan / Jadwal KBM -->
    <div 
      v-if="!auth.isAdmin && classes.length === 0 && !loading"
      class="bg-amber-50 border border-amber-200 text-amber-800 p-5 rounded-2xl flex items-start gap-3.5 text-xs shadow-xs"
    >
      <span class="text-2xl shrink-0">📋</span>
      <div>
        <h4 class="font-bold text-amber-900 text-sm">Tidak Ada Rombel Kelas yang Diampu</h4>
        <p class="mt-1 text-amber-700 leading-relaxed">
          Akun Anda saat ini belum tercatat sebagai wali kelas maupun guru pengampu jadwal KBM pada tahun ajaran ini. 
          Sistem hanya menampilkan rekap presensi untuk kelas yang Anda ampu. Silakan hubungi bagian kurikulum atau staf admin sekolah jika jadwal Anda belum ditugaskan.
        </p>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- TAB 0: REKAP KELAS & KBM (LEDGER AKUMULASI, MAPEL & RAPOR)      -->
    <!-- ============================================================== -->
    <div v-if="activeTab === 'class_recap'" class="space-y-6">
      <!-- Shortcut Cepat Wali Kelas -->
      <div v-if="auth.isWaliKelas" class="flex flex-wrap items-center justify-between gap-3 bg-emerald-50/80 border border-emerald-200/80 p-3 rounded-2xl">
        <div class="flex items-center gap-2">
          <span class="text-xs font-bold text-emerald-800">Mode Cepat Wali Kelas:</span>
          <button
            type="button"
            @click="selectMyHomeroom"
            :class="ledgerClassId === String(auth.primaryHomeroomClass?.id) ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-white text-emerald-800 hover:bg-emerald-100 font-medium border border-emerald-200'"
            class="px-3 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition"
          >
            <span>⭐ Fokus Kelas Binaan: {{ auth.primaryHomeroomClass?.nama }}</span>
          </button>
        </div>
        <span class="text-[11px] text-emerald-700 font-medium">Buku presensi dan keterlaksanaan KBM otomatis terisi untuk kelas binaan Anda.</span>
      </div>

      <!-- Filter Controls Toolbar -->
      <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <!-- Filter Dropdowns & Inputs -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 flex-1">
            <!-- Pilihan Kelas -->
            <div>
              <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Rombel / Kelas</label>
              <select
                v-model="ledgerClassId"
                @change="fetchClassRecap"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500"
              >
                <option value="" disabled>Pilih Kelas</option>
                <option v-for="c in classes" :key="c.id" :value="String(c.id)">
                  {{ c.nama }} {{ isMyHomeroom(c.id) ? '⭐ (Binaan Saya)' : '' }}
                </option>
              </select>
            </div>

            <!-- Pilihan Mata Pelajaran -->
            <div>
              <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Mata Pelajaran</label>
              <select
                v-model="ledgerSubjectId"
                @change="fetchClassRecap"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500"
              >
                <option value="">Semua Mata Pelajaran (Akumulatif & Rincian)</option>
                <option v-for="sub in classRecapData?.subjects || subjects" :key="sub.subject_id || sub.id" :value="String(sub.subject_id || sub.id)">
                  {{ sub.subject_nama || sub.nama }} {{ sub.teacher_nama ? `(${sub.teacher_nama})` : '' }}
                </option>
              </select>
            </div>

            <!-- Pencarian Siswa -->
            <div>
              <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Cari Siswa</label>
              <div class="relative">
                <input
                  v-model="ledgerSearchQuery"
                  @keyup.enter="fetchClassRecap"
                  type="text"
                  placeholder="Ketik NIS / Nama & Enter..."
                  class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:ring-2 focus:ring-emerald-500"
                />
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-end gap-2.5 shrink-0 pt-2 lg:pt-0">
            <button
              @click="fetchClassRecap"
              :disabled="loadingClassRecap"
              class="px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold text-xs transition shadow-xs flex items-center gap-1.5"
            >
              <svg v-if="loadingClassRecap" class="animate-spin w-3.5 h-3.5" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span>Terapkan</span>
            </button>

            <!-- Export Excel Button -->
            <button
              @click="exportClassRecapExcel"
              :disabled="exportingRecapExcel || !ledgerClassId"
              class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-xl font-bold text-xs transition shadow-xs flex items-center gap-2"
              title="Download file Excel Rekap Presensi"
            >
              <svg v-if="exportingRecapExcel" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <svg v-else class="w-4 h-4 text-emerald-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span>{{ exportingRecapExcel ? 'Mengekspor...' : 'Export Excel (.xlsx)' }}</span>
            </button>
          </div>
        </div>

        <!-- Date Range Filter & Quick Presets -->
        <div class="pt-3 border-t border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
          <div class="flex flex-wrap items-center gap-2">
            <span class="text-slate-500 font-medium">Rentang Tanggal:</span>
            <input
              type="date"
              v-model="ledgerStartDate"
              @change="fetchClassRecap"
              class="border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs font-semibold bg-slate-50 text-slate-800 focus:ring-2 focus:ring-emerald-500"
            />
            <span class="text-slate-400">s/d</span>
            <input
              type="date"
              v-model="ledgerEndDate"
              @change="fetchClassRecap"
              class="border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs font-semibold bg-slate-50 text-slate-800 focus:ring-2 focus:ring-emerald-500"
            />
          </div>

          <div class="flex flex-wrap items-center gap-1.5">
            <span class="text-slate-400 text-[11px] font-medium mr-1">Preset Cepat:</span>
            <button
              @click="setLedgerDatePreset('this_month')"
              class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-semibold transition cursor-pointer"
            >
              Bulan Ini
            </button>
            <button
              @click="setLedgerDatePreset('last_month')"
              class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-semibold transition cursor-pointer"
            >
              Bulan Lalu
            </button>
            <button
              @click="setLedgerDatePreset('this_semester')"
              class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-semibold transition cursor-pointer"
            >
              Semester Ini
            </button>
            <button
              @click="setLedgerDatePreset('last_30_days')"
              class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-semibold transition cursor-pointer"
            >
              30 Hari Terakhir
            </button>
          </div>
        </div>
      </div>

      <!-- KPI Summary Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
        <!-- Card 1: Total Pertemuan -->
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
            <span class="text-xl">📅</span>
          </div>
          <div>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pertemuan KBM</span>
            <div class="flex items-baseline gap-1.5">
              <span class="text-2xl font-black text-slate-900">{{ classRecapData?.total_meetings || 0 }}</span>
              <span class="text-xs text-slate-500 font-semibold">Sesi</span>
            </div>
            <span class="text-[10px] text-purple-600 font-bold block mt-0.5">
              {{ ledgerSubjectId ? 'Mata Pelajaran Ini' : 'Semua Mapel Terlaksana' }}
            </span>
          </div>
        </div>

        <!-- Card 2: Total Siswa Aktif -->
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
            <span class="text-xl">👥</span>
          </div>
          <div>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Siswa Aktif</span>
            <div class="flex items-baseline gap-1.5">
              <span class="text-2xl font-black text-slate-900">{{ classRecapData?.total_students || 0 }}</span>
              <span class="text-xs text-slate-500 font-semibold">Murid</span>
            </div>
            <span class="text-[10px] text-slate-400 font-medium block mt-0.5">Kelas {{ classRecapData?.class?.nama || '-' }}</span>
          </div>
        </div>

        <!-- Card 3: Rata-Rata Kehadiran -->
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
            <span class="text-xl">📈</span>
          </div>
          <div>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Rata-Rata Kelas</span>
            <div class="flex items-baseline gap-1.5">
              <span class="text-2xl font-black" :class="(classRecapData?.summary?.persentase_kehadiran || 0) >= 80 ? 'text-emerald-700' : 'text-amber-700'">
                {{ classRecapData?.summary?.persentase_kehadiran || 0 }}%
              </span>
            </div>
            <span class="text-[10px] font-bold block mt-0.5" :class="(classRecapData?.summary?.persentase_kehadiran || 0) >= 80 ? 'text-emerald-600' : 'text-amber-600'">
              {{ (classRecapData?.summary?.persentase_kehadiran || 0) >= 85 ? 'Disiplin Sangat Baik' : 'Perlu Monitoring' }}
            </span>
          </div>
        </div>

        <!-- Card 4: Akumulasi Kehadiran -->
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs flex flex-col justify-between">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Akumulasi Absensi</span>
          <div class="grid grid-cols-4 gap-1 text-center mt-1">
            <div class="p-1 bg-emerald-50 rounded-lg">
              <span class="text-[9px] font-bold text-emerald-600 block">H</span>
              <span class="text-xs font-black text-emerald-800">{{ classRecapData?.summary?.total_hadir || 0 }}</span>
            </div>
            <div class="p-1 bg-sky-50 rounded-lg">
              <span class="text-[9px] font-bold text-sky-600 block">S</span>
              <span class="text-xs font-black text-sky-800">{{ classRecapData?.summary?.total_sakit || 0 }}</span>
            </div>
            <div class="p-1 bg-amber-50 rounded-lg">
              <span class="text-[9px] font-bold text-amber-600 block">I</span>
              <span class="text-xs font-black text-amber-800">{{ classRecapData?.summary?.total_izin || 0 }}</span>
            </div>
            <div class="p-1 bg-rose-50 rounded-lg">
              <span class="text-[9px] font-bold text-rose-600 block">A</span>
              <span class="text-xs font-black text-rose-800">{{ classRecapData?.summary?.total_alpa || 0 }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Keterlaksanaan KBM Per Mata Pelajaran (Chips Grid) -->
      <div v-if="!ledgerSubjectId && classRecapData?.subjects?.length > 0" class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm space-y-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-900">Keterlaksanaan KBM Per Mata Pelajaran:</span>
            <span class="text-[11px] text-slate-400">Klik salah satu mapel untuk memfilter tampilan</span>
          </div>
          <span class="text-[10px] font-bold px-2 py-0.5 bg-slate-100 text-slate-600 rounded-md">
            {{ classRecapData.subjects.length }} Mata Pelajaran Terjadwal
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5">
          <button
            v-for="sub in classRecapData.subjects"
            :key="sub.subject_id"
            @click="ledgerSubjectId = String(sub.subject_id); fetchClassRecap()"
            class="text-left p-3 rounded-2xl border transition group flex items-start justify-between gap-2 cursor-pointer"
            :class="ledgerSubjectId === String(sub.subject_id) ? 'bg-emerald-50 border-emerald-300 ring-2 ring-emerald-500' : 'bg-slate-50/70 border-slate-200/80 hover:bg-emerald-50/50 hover:border-emerald-200'"
          >
            <div class="min-w-0">
              <span class="text-xs font-bold text-slate-800 block truncate group-hover:text-emerald-800">{{ sub.subject_nama }}</span>
              <span class="text-[10px] text-slate-400 block truncate mt-0.5">Guru: {{ sub.teacher_nama }}</span>
            </div>
            <div class="px-2 py-1 rounded-xl bg-white border border-slate-200 text-center shrink-0">
              <span class="text-xs font-black text-purple-700 block">{{ sub.total_pertemuan }}</span>
              <span class="text-[8px] text-slate-400 font-bold block uppercase">Sesi</span>
            </div>
          </button>
        </div>
      </div>

      <!-- Tabel Ledger Rekapitulasi Presensi Siswa -->
      <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <span>Buku Rekap Presensi & KBM</span>
              <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-black rounded-full">
                {{ classRecapData?.students?.length || 0 }} Siswa Terdaftar
              </span>
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Kelas: <span class="font-bold text-slate-700">{{ classRecapData?.class?.nama || '-' }}</span> •
              Wali Kelas: <span class="font-bold text-slate-700">{{ classRecapData?.class?.homeroom_teacher?.nama_lengkap || '-' }}</span> •
              Periode: <span class="font-bold text-slate-700">{{ ledgerStartDate }} s/d {{ ledgerEndDate }}</span>
            </p>
          </div>

          <div class="flex items-center gap-2">
            <span class="text-[11px] text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full font-bold flex items-center gap-1.5 border border-emerald-200">
              <span>✓</span> Format Siap Terhubung ke Rapor
            </span>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
              <tr>
                <th class="py-3 px-4 w-12 text-center">No</th>
                <th class="py-3 px-4 min-w-[200px]">Data Siswa</th>
                <th class="py-3 px-3 text-center w-14 text-emerald-700 bg-emerald-50/40">Hadir</th>
                <th class="py-3 px-3 text-center w-14 text-sky-700 bg-sky-50/40">Sakit</th>
                <th class="py-3 px-3 text-center w-14 text-amber-700 bg-amber-50/40">Izin</th>
                <th class="py-3 px-3 text-center w-14 text-rose-700 bg-rose-50/40">Alpa</th>
                <th class="py-3 px-3 text-center w-20">Total Absen</th>
                <th class="py-3 px-3 text-center w-24">Pertemuan Kelas</th>
                <th class="py-3 px-4 text-center min-w-[140px]">% Kehadiran</th>
                <th class="py-3 px-3 text-center w-28">Predikat</th>
                <th class="py-3 px-4 text-center w-28">Rincian Mapel</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-if="loadingClassRecap">
                <td colspan="11" class="py-12 text-center text-slate-400">
                  <div class="flex flex-col items-center justify-center gap-2">
                    <svg class="animate-spin w-6 h-6 text-emerald-600" viewBox="0 0 24 24" fill="none">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Memuat rekapitulasi data presensi kelas...</span>
                  </div>
                </td>
              </tr>
              <tr v-else-if="!classRecapData?.students || classRecapData.students.length === 0">
                <td colspan="11" class="py-12 text-center text-slate-400">
                  <div class="flex flex-col items-center justify-center gap-1">
                    <span class="text-3xl">📭</span>
                    <span class="font-bold text-slate-600 mt-2">Belum ada siswa atau data presensi</span>
                    <span class="text-xs text-slate-400">Silakan pilih kelas lain atau periksa rentang tanggal filter</span>
                  </div>
                </td>
              </tr>
              <tr
                v-for="(stu, idx) in (classRecapData?.students || [])"
                :key="stu.student_id"
                class="hover:bg-slate-50/70 transition"
              >
                <td class="py-3 px-4 text-center font-bold text-slate-400">{{ Number(idx) + 1 }}</td>
                <td class="py-3 px-4">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full overflow-hidden bg-slate-100 border border-slate-200 shrink-0 flex items-center justify-center font-bold text-[10px] text-slate-500">
                      <img v-if="stu.foto_path" :src="getPhotoUrl(stu.foto_path)" class="w-full h-full object-cover" />
                      <span v-else>{{ getInitials(stu.nama_lengkap) }}</span>
                    </div>
                    <div>
                      <span class="font-bold text-slate-800 block text-xs">{{ stu.nama_lengkap }}</span>
                      <div class="flex items-center gap-2 text-[10px] text-slate-400">
                        <span>NIS: {{ stu.nis || '-' }}</span>
                        <span>•</span>
                        <span class="uppercase">{{ stu.jenis_kelamin || '-' }}</span>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-3 text-center bg-emerald-50/30">
                  <span class="px-2 py-0.5 rounded-full font-bold text-xs bg-emerald-100 text-emerald-800">
                    {{ stu.hadir }}
                  </span>
                </td>
                <td class="py-3 px-3 text-center bg-sky-50/30">
                  <span class="px-2 py-0.5 rounded-full font-bold text-xs" :class="stu.sakit > 0 ? 'bg-sky-100 text-sky-800' : 'text-slate-400'">
                    {{ stu.sakit }}
                  </span>
                </td>
                <td class="py-3 px-3 text-center bg-amber-50/30">
                  <span class="px-2 py-0.5 rounded-full font-bold text-xs" :class="stu.izin > 0 ? 'bg-amber-100 text-amber-800' : 'text-slate-400'">
                    {{ stu.izin }}
                  </span>
                </td>
                <td class="py-3 px-3 text-center bg-rose-50/30">
                  <span class="px-2 py-0.5 rounded-full font-bold text-xs" :class="stu.alpa > 0 ? 'bg-rose-100 text-rose-800 font-black' : 'text-slate-400'">
                    {{ stu.alpa }}
                  </span>
                </td>
                <td class="py-3 px-3 text-center font-semibold text-slate-700">
                  {{ stu.total_recorded }} Sesi
                </td>
                <td class="py-3 px-3 text-center font-bold text-purple-700">
                  {{ classRecapData?.total_meetings || 0 }} Sesi
                </td>
                <td class="py-3 px-4">
                  <div class="flex flex-col gap-1 items-center">
                    <span class="text-xs font-black" :class="stu.persentase_kehadiran >= 85 ? 'text-emerald-700' : stu.persentase_kehadiran >= 75 ? 'text-amber-700' : 'text-rose-700'">
                      {{ stu.persentase_kehadiran }}%
                    </span>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                      <div
                        class="h-full rounded-full transition-all duration-300"
                        :class="stu.persentase_kehadiran >= 85 ? 'bg-emerald-500' : stu.persentase_kehadiran >= 75 ? 'bg-amber-500' : 'bg-rose-500'"
                        :style="{ width: `${Math.min(stu.persentase_kehadiran, 100)}%` }"
                      ></div>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-3 text-center">
                  <span
                    class="px-2 py-0.5 text-[10px] font-bold rounded-full inline-block"
                    :class="{
                      'bg-emerald-100 text-emerald-800': stu.persentase_kehadiran >= 90,
                      'bg-teal-100 text-teal-800': stu.persentase_kehadiran >= 80 && stu.persentase_kehadiran < 90,
                      'bg-amber-100 text-amber-800': stu.persentase_kehadiran >= 70 && stu.persentase_kehadiran < 80,
                      'bg-rose-100 text-rose-800 font-black': stu.persentase_kehadiran < 70,
                    }"
                  >
                    {{ stu.persentase_kehadiran >= 90 ? 'Sangat Baik' : stu.persentase_kehadiran >= 80 ? 'Baik' : stu.persentase_kehadiran >= 70 ? 'Cukup' : 'Perlu Binaan' }}
                  </span>
                </td>
                <td class="py-3 px-4 text-center">
                  <button
                    @click="openStudentDetail(stu)"
                    class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-emerald-100 text-slate-700 hover:text-emerald-800 font-bold text-[11px] transition flex items-center justify-center gap-1 w-full cursor-pointer"
                    title="Lihat rincian kehadiran per mata pelajaran siswa ini"
                  >
                    <span>📋</span>
                    <span>Rapor Mapel</span>
                  </button>
                </td>
              </tr>
            </tbody>
            <!-- Footer Total Kelas -->
            <tfoot v-if="classRecapData?.students && classRecapData.students.length > 0" class="bg-slate-50 font-bold text-slate-800 border-t border-slate-200">
              <tr>
                <td colspan="2" class="py-3.5 px-4 text-right uppercase text-[11px] text-slate-500 font-extrabold tracking-wider">
                  Total Akumulasi Se-Kelas:
                </td>
                <td class="py-3.5 px-3 text-center text-emerald-800 font-black bg-emerald-100/40">
                  {{ classRecapData?.summary?.total_hadir || 0 }}
                </td>
                <td class="py-3.5 px-3 text-center text-sky-800 font-black bg-sky-100/40">
                  {{ classRecapData?.summary?.total_sakit || 0 }}
                </td>
                <td class="py-3.5 px-3 text-center text-amber-800 font-black bg-amber-100/40">
                  {{ classRecapData?.summary?.total_izin || 0 }}
                </td>
                <td class="py-3.5 px-3 text-center text-rose-800 font-black bg-rose-100/40">
                  {{ classRecapData?.summary?.total_alpa || 0 }}
                </td>
                <td class="py-3.5 px-3 text-center text-slate-700 font-black">
                  {{ classRecapData?.summary?.total_recorded || 0 }} Sesi
                </td>
                <td class="py-3.5 px-3 text-center text-purple-700 font-black">
                  {{ classRecapData?.total_meetings || 0 }} Sesi
                </td>
                <td class="py-3.5 px-4 text-center text-emerald-800 font-black">
                  {{ classRecapData?.summary?.persentase_kehadiran || 0 }}%
                </td>
                <td colspan="2" class="py-3.5 px-4 text-center text-xs text-slate-400 font-normal">
                  Rata-rata Kehadiran
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- TAB 1: REKAP PRESENSI SISWA (HASIL INPUT KBM GURU & WALI)      -->
    <!-- ============================================================== -->
    <div v-if="activeTab === 'student_recap'" class="space-y-6">
      <!-- SECTION MONITORING PRESENSI HARIAN WALI KELAS (UNTUK ADMIN & KEPALA SEKOLAH) -->
      <div v-if="auth.isAdmin && homeroomMonitoringData" class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase tracking-wider rounded-full">
                Monitoring Presensi Rombel
              </span>
              <span class="text-xs text-slate-400">Pengisian oleh Seluruh Wali Kelas</span>
            </div>
            <h2 class="text-lg font-black text-slate-900 mt-1">
              Status Pengisian Presensi Siswa Hari Ini (Wali Kelas)
            </h2>
            <p class="text-xs text-slate-500">
              Pantau kepatuhan dan validasi pengisian absensi harian dari tiap wali kelas rombel secara real-time.
            </p>
          </div>
          <div class="flex items-center gap-2.5 shrink-0">
            <span
              class="px-3.5 py-2 rounded-2xl text-xs font-bold flex items-center gap-2 shadow-xs"
              :class="homeroomMonitoringData.summary?.completed_classes === homeroomMonitoringData.summary?.total_classes
                ? 'bg-emerald-50 text-emerald-800 border border-emerald-300'
                : 'bg-amber-50 text-amber-900 border border-amber-300'"
            >
              <span class="w-2.5 h-2.5 rounded-full" :class="homeroomMonitoringData.summary?.completed_classes === homeroomMonitoringData.summary?.total_classes ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse'"></span>
              <span><strong>{{ homeroomMonitoringData.summary?.completed_classes }} dari {{ homeroomMonitoringData.summary?.total_classes }} Rombel</strong> Sudah Mengabsen ({{ homeroomMonitoringData.summary?.completion_rate }}%)</span>
            </span>
          </div>
        </div>

        <!-- Grid Kartu Status Per Rombel Kelas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="c in homeroomMonitoringData.classes"
            :key="c.class_id"
            class="rounded-2xl border p-4.5 flex flex-col justify-between transition-all"
            :class="c.is_recorded 
              ? 'bg-emerald-50/40 border-emerald-200/90 hover:border-emerald-300 hover:shadow-md' 
              : 'bg-rose-50/40 border-rose-200/90 hover:border-rose-300 hover:shadow-md'"
          >
            <div>
              <div class="flex items-start justify-between gap-2">
                <div>
                  <span class="text-[10px] font-black tracking-wider uppercase text-slate-400">Rombel</span>
                  <h3 class="font-black text-slate-900 text-sm">{{ c.class_nama }}</h3>
                  <div class="text-[11px] text-slate-600 font-medium mt-0.5 flex items-center gap-1">
                    <span>👤 Wali:</span>
                    <strong class="text-slate-800">{{ c.wali_kelas_nama }}</strong>
                  </div>
                </div>
                <span
                  class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider shrink-0 flex items-center gap-1 shadow-2xs"
                  :class="c.is_recorded 
                    ? 'bg-emerald-600 text-white' 
                    : 'bg-rose-600 text-white animate-pulse'"
                >
                  <span v-if="c.is_recorded">✓ Sudah Diabsen</span>
                  <span v-else>⚠️ Belum Diabsen</span>
                </span>
              </div>

              <!-- Metrics jika sudah diabsen -->
              <div v-if="c.is_recorded" class="mt-3 pt-3 border-t border-emerald-200/60 text-xs space-y-1.5">
                <div class="flex items-center justify-between text-slate-700">
                  <span class="text-[11px] text-slate-500 font-medium">Tingkat Hadir:</span>
                  <span class="font-extrabold text-emerald-800">{{ c.persentase_kehadiran }}% ({{ c.hadir }}/{{ c.total_students }} Siswa)</span>
                </div>
                <div class="flex items-center gap-1.5 flex-wrap text-[10px] font-bold">
                  <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800">Hadir: {{ c.hadir }}</span>
                  <span class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-800">Izin: {{ c.izin }}</span>
                  <span class="px-2 py-0.5 rounded-md bg-sky-100 text-sky-800">Sakit: {{ c.sakit }}</span>
                  <span class="px-2 py-0.5 rounded-md bg-rose-100 text-rose-800">Alpa: {{ c.alpa }}</span>
                </div>
                <div class="text-[10px] text-slate-400 mt-1 flex items-center justify-between pt-1">
                  <span>Waktu: {{ c.updated_at ? new Date(c.updated_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB' : '-' }}</span>
                  <span>Oleh: {{ c.recorded_by || 'Wali Kelas' }}</span>
                </div>
              </div>

              <!-- Peringatan jika belum diabsen -->
              <div v-else class="mt-3 pt-3 border-t border-rose-200/60 text-xs">
                <p class="text-[11px] text-rose-700 leading-relaxed font-medium">
                  Wali kelas belum menginput presensi siswa untuk tanggal ini.
                </p>
                <span class="text-[10px] text-slate-400 mt-1 block">Total Terdaftar: {{ c.total_students }} Siswa</span>
              </div>
            </div>

            <!-- Action Button -->
            <div class="mt-3.5 pt-2 flex items-center justify-between gap-2 border-t border-slate-100">
              <button
                type="button"
                @click="filterByHomeroomClass(c.class_id)"
                class="text-[11px] font-bold text-emerald-700 hover:text-emerald-900 flex items-center gap-1 transition cursor-pointer"
              >
                <span>🔍 Filter Log Siswa</span>
              </button>
              <button
                type="button"
                @click="inputClassId = String(c.class_id); activeTab = 'manual_input'; loadStudentsForManualInput()"
                class="text-[11px] font-semibold text-slate-500 hover:text-slate-800 transition cursor-pointer"
              >
                <span>Koreksi Presensi &rarr;</span>
              </button>
            </div>
          </div>
        </div>
      </div>
      <!-- Shortcut Cepat Kelas Binaan untuk Wali Kelas / Kepsek -->
      <div v-if="auth.isWaliKelas" class="flex flex-wrap items-center justify-between gap-3 bg-emerald-50/80 border border-emerald-200/80 p-3 rounded-2xl">
        <div class="flex items-center gap-2">
          <span class="text-xs font-bold text-emerald-800">Mode Cepat Wali Kelas:</span>
          <button
            type="button"
            @click="selectMyHomeroom"
            :class="selectedClassFilter === String(auth.primaryHomeroomClass?.id) ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-white text-emerald-800 hover:bg-emerald-100 font-medium border border-emerald-200'"
            class="px-3 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition"
          >
            <span>⭐ Fokus Kelas Binaan: {{ auth.primaryHomeroomClass?.nama }}</span>
          </button>
        </div>
        <span class="text-[11px] text-emerald-700 font-medium">Klik untuk memfilter otomatis hasil presensi kelas binaan Anda.</span>
      </div>

      <!-- Stat Cards Ringkasan Kehadiran Hari Ini -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Absensi</span>
          <span class="text-xl font-black text-slate-800 mt-1 block">{{ studentAttendanceSummary.total_record }}</span>
          <span class="text-[10px] text-slate-400">Catatan Siswa</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
          <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">% Kehadiran</span>
          <span class="text-xl font-black text-emerald-700 mt-1 block">{{ studentAttendanceSummary.persentase_kehadiran }}%</span>
          <span class="text-[10px] text-emerald-600/70">Tingkat Hadir</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
          <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Hadir</span>
          <span class="text-xl font-black text-emerald-700 mt-1 block">{{ studentAttendanceSummary.hadir }}</span>
          <span class="text-[10px] text-slate-400">Siswa</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
          <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider block">Izin</span>
          <span class="text-xl font-black text-amber-700 mt-1 block">{{ studentAttendanceSummary.izin }}</span>
          <span class="text-[10px] text-slate-400">Siswa</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
          <span class="text-[10px] font-bold text-sky-600 uppercase tracking-wider block">Sakit</span>
          <span class="text-xl font-black text-sky-700 mt-1 block">{{ studentAttendanceSummary.sakit }}</span>
          <span class="text-[10px] text-slate-400">Siswa</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
          <span class="text-[10px] font-bold text-rose-600 uppercase tracking-wider block">Alpa</span>
          <span class="text-xl font-black text-rose-700 mt-1 block">{{ studentAttendanceSummary.alpa }}</span>
          <span class="text-[10px] text-slate-400">Tanpa Keterangan</span>
        </div>
      </div>

      <!-- Filter Controls Toolbar -->
      <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
        <div class="flex flex-wrap items-center gap-2.5 flex-1">
          <!-- Filter Rombel Kelas -->
          <div class="w-48">
            <select
              v-model="selectedClassFilter"
              @change="fetchStudentAttendanceRecap(1)"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500"
            >
              <option value="">{{ auth.isAdmin ? 'Semua Rombel Kelas' : 'Semua Rombel yang Diampu' }}</option>
              <option v-for="c in classes" :key="c.id" :value="String(c.id)">
                {{ c.nama }} {{ isMyHomeroom(c.id) ? '⭐ (Binaan Saya)' : '' }}
              </option>
            </select>
          </div>

          <!-- Filter Status Kehadiran -->
          <div class="w-40">
            <select
              v-model="selectedStatusFilter"
              @change="fetchStudentAttendanceRecap(1)"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500"
            >
              <option value="">Semua Status Kehadiran</option>
              <option value="hadir">Hanya Hadir</option>
              <option value="izin">Hanya Izin</option>
              <option value="sakit">Hanya Sakit</option>
              <option value="alpa">Hanya Alpa</option>
            </select>
          </div>

          <!-- Search Siswa Input -->
          <div class="relative flex-1 min-w-[200px]">
            <input
              v-model="studentSearchQuery"
              @keyup.enter="fetchStudentAttendanceRecap(1)"
              type="text"
              placeholder="Cari nama atau NIS siswa lalu tekan Enter..."
              class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:ring-2 focus:ring-emerald-500"
            />
            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <button
            @click="activeTab = 'manual_input'; loadStudentsForManualInput()"
            class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold transition shadow-xs flex items-center gap-1.5"
          >
            <span>+</span>
            <span>Input / Koreksi Presensi</span>
          </button>
        </div>
      </div>

      <!-- Tabel Rekap Data Presensi Siswa -->
      <div id="student-recap-table" class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden scroll-mt-24">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs min-w-[850px]">
            <thead>
              <tr class="bg-slate-50/80 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
                <th class="p-3.5 w-12 text-center">No</th>
                <th class="p-3.5">Siswa</th>
                <th class="p-3.5">Kelas / Rombel</th>
                <th class="p-3.5">Sesi KBM / Mata Pelajaran</th>
                <th class="p-3.5 text-center">Status</th>
                <th class="p-3.5">Keterangan</th>
                <th class="p-3.5">Dicatat Oleh</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="loading">
                <td colspan="7" class="text-center py-12 text-slate-400">
                  <div class="inline-block animate-spin rounded-full h-8 w-8 border-3 border-emerald-500 border-t-transparent mb-2"></div>
                  <p class="font-medium">Memuat data rekap presensi siswa...</p>
                </td>
              </tr>
              <tr v-else-if="studentAttendanceRecords.length === 0">
                <td colspan="7" class="text-center py-12 text-slate-400">
                  <div class="text-3xl mb-2">📋</div>
                  <p class="font-semibold text-slate-700">Belum ada data presensi pada tanggal ini.</p>
                  <p class="text-[11px] text-slate-400 mt-0.5">
                    Data akan otomatis tercatat saat guru menekan tombol <strong>Masuk Kelas</strong> di menu Jadwal Mengajar, atau melalui tab Input Presensi.
                  </p>
                </td>
              </tr>
              <tr 
                v-for="(rec, idx) in studentAttendanceRecords" 
                :key="rec.id" 
                class="hover:bg-slate-50/80 transition-colors"
              >
                <td class="p-3.5 text-center font-mono text-slate-400">
                  {{ (studentPagination.current_page - 1) * studentPagination.per_page + idx + 1 }}
                </td>
                <td class="p-3.5">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
                      <img
                        v-if="rec.student?.foto_path"
                        :src="getPhotoUrl(rec.student?.foto_path)"
                        :alt="rec.student?.nama_lengkap"
                        class="w-full h-full object-cover"
                        @error="(e: any) => e.target.style.display = 'none'"
                      />
                      <span v-else class="text-[10px] font-bold text-slate-500">
                        {{ getInitials(rec.student?.nama_lengkap) }}
                      </span>
                    </div>
                    <div>
                      <span class="font-bold text-slate-900 block">{{ rec.student?.nama_lengkap || 'Siswa' }}</span>
                      <span class="text-[10px] text-slate-400 font-mono">NIS: {{ rec.student?.nis || '-' }}</span>
                    </div>
                  </div>
                </td>
                <td class="p-3.5 font-semibold text-purple-700 whitespace-nowrap">
                  {{ rec.school_class?.nama || '-' }}
                </td>
                <td class="p-3.5">
                  <div v-if="rec.schedule">
                    <span class="font-bold text-slate-800 block">{{ rec.schedule?.subject?.nama }}</span>
                    <span class="text-[10px] text-slate-400">
                      {{ rec.schedule?.teacher?.nama_lengkap || 'Guru Pengampu' }} • {{ rec.schedule?.time_slot?.nama || '' }}
                    </span>
                  </div>
                  <div v-else>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1">
                      <span>📋 Presensi Harian (Wali Kelas)</span>
                    </span>
                  </div>
                </td>
                <td class="p-3.5 text-center">
                  <span
                    class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider inline-flex items-center gap-1"
                    :class="{
                      'bg-emerald-100 text-emerald-800 border border-emerald-200': rec.status === 'hadir',
                      'bg-amber-100 text-amber-800 border border-amber-200': rec.status === 'izin',
                      'bg-sky-100 text-sky-800 border border-sky-200': rec.status === 'sakit',
                      'bg-rose-100 text-rose-800 border border-rose-200': rec.status === 'alpa',
                    }"
                  >
                    <span>{{ rec.status }}</span>
                  </span>
                </td>
                <td class="p-3.5 text-slate-600 max-w-xs truncate">
                  {{ rec.keterangan || '-' }}
                </td>
                <td class="p-3.5 text-slate-500 text-[11px]">
                  <span class="font-semibold text-slate-700 block">{{ rec.recorder?.nama || 'Guru' }}</span>
                  <span class="text-[10px] text-slate-400">{{ rec.created_at ? new Date(rec.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB' : '-' }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Bar -->
        <div 
          v-if="studentPagination.total > studentPagination.per_page"
          class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between text-xs text-slate-600"
        >
          <span>Halaman {{ studentPagination.current_page }} dari {{ studentPagination.last_page }} (Total {{ studentPagination.total }} Catatan)</span>
          <div class="flex items-center gap-1.5">
            <button
              :disabled="studentPagination.current_page <= 1"
              @click="fetchStudentAttendanceRecap(studentPagination.current_page - 1)"
              class="px-3 py-1 bg-white border border-slate-200 rounded-lg hover:bg-slate-100 disabled:opacity-40"
            >
              Sebelumnya
            </button>
            <button
              :disabled="studentPagination.current_page >= studentPagination.last_page"
              @click="fetchStudentAttendanceRecap(studentPagination.current_page + 1)"
              class="px-3 py-1 bg-white border border-slate-200 rounded-lg hover:bg-slate-100 disabled:opacity-40"
            >
              Berikutnya
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- TAB 2: GALERI BUKTI KBM & JURNAL MENGAJAR GURU                 -->
    <!-- ============================================================== -->
    <div v-else-if="activeTab === 'kbm'" class="space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-white p-5 rounded-2xl border border-slate-100 shadow-xs">
        <div>
          <h3 class="font-bold text-slate-900 text-sm sm:text-base">Galeri Bukti Mengajar & Jurnal KBM Guru</h3>
          <p class="text-xs text-slate-500 mt-0.5">Dokumentasi foto aktivitas kelas, pokok materi ajar, dan koordinat GPS dari sesi Masuk Kelas guru.</p>
        </div>
        <button 
          @click="fetchKbmEvidence" 
          class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3.5 py-2 rounded-xl transition flex items-center gap-1.5"
        >
          <span>🔄</span>
          <span>Refresh Galeri</span>
        </button>
      </div>

      <div v-if="loading" class="text-center py-16 text-slate-400 text-xs">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-3 border-emerald-500 border-t-transparent mb-2"></div>
        <p>Memuat dokumentasi bukti KBM...</p>
      </div>

      <div v-else-if="kbmEvidences.length === 0" class="text-center py-16 bg-white rounded-3xl border border-dashed border-slate-200 shadow-xs space-y-2">
        <div class="text-3xl">📷</div>
        <h4 class="font-bold text-slate-800 text-sm">Belum Ada Bukti KBM</h4>
        <p class="text-xs text-slate-400 max-w-md mx-auto">
          Belum ada foto bukti mengajar yang diunggah untuk tanggal terpilih. Guru dapat mengunggahnya langsung saat mengklik <strong>"Masuk Kelas"</strong> pada kartu jadwal KBM.
        </p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="ev in kbmEvidences" 
          :key="ev.id"
          class="border border-slate-200/90 rounded-3xl overflow-hidden hover:shadow-xl transition-all duration-300 bg-white flex flex-col justify-between group"
        >
          <div>
            <!-- Photo Thumbnail with Zoom Icon -->
            <div 
              class="h-52 bg-slate-900 relative overflow-hidden group cursor-pointer"
              @click="openEvidenceViewer(ev)"
            >
              <img 
                v-if="ev.foto_url || ev.photo_path" 
                :src="ev.foto_url || getPhotoUrl(ev.photo_path)" 
                alt="Bukti KBM" 
                class="w-full h-full object-cover group-hover:scale-105 transition-all duration-300 opacity-90 group-hover:opacity-100"
              />
              <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-400 text-xs gap-1">
                <span class="text-2xl">📷</span>
                <span>Tidak ada foto</span>
              </div>

              <!-- Time Badge -->
              <div class="absolute bottom-2.5 right-2.5 bg-black/70 text-white text-[10px] font-mono font-bold px-2.5 py-1 rounded-lg backdrop-blur-xs flex items-center gap-1">
                <svg class="w-3 h-3 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ ev.captured_at ? new Date(ev.captured_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB' : '-' }}</span>
              </div>

              <!-- Fullscreen prompt badge -->
              <div class="absolute top-2.5 left-2.5 bg-black/60 text-white text-[10px] font-bold px-2 py-0.5 rounded-lg opacity-0 group-hover:opacity-100 transition">
                🔍 Klik untuk perbesar
              </div>
            </div>

            <!-- Content Details -->
            <div class="p-5 space-y-3 text-xs">
              <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-lg">
                  {{ ev.schedule?.school_class?.nama || 'Kelas' }}
                </span>
                <span class="text-[11px] text-purple-700 bg-purple-50 px-2 py-0.5 rounded-lg font-semibold">
                  {{ ev.schedule?.subject?.nama || 'Mata Pelajaran' }}
                </span>
              </div>

              <div class="space-y-1">
                <h4 class="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                  <span>👨‍🏫</span>
                  <span>{{ ev.teacher?.nama_lengkap || 'Guru Pengampu' }}</span>
                </h4>
                <p class="text-[11px] text-slate-400">
                  {{ ev.schedule?.time_slot?.nama || '' }} ({{ ev.schedule?.time_slot?.jam_mulai || '07:30' }} - {{ ev.schedule?.time_slot?.jam_selesai || '09:00' }})
                </p>
              </div>

              <!-- Materi Pokok -->
              <div class="p-3 bg-slate-50 rounded-xl space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pokok Materi Ajar:</span>
                <p class="text-xs text-slate-800 font-semibold leading-relaxed">
                  {{ ev.materi_ajar || 'Materi KBM reguler' }}
                </p>
              </div>

              <!-- Catatan KBM -->
              <div v-if="ev.catatan" class="text-slate-600 text-xs italic">
                "{{ ev.catatan }}"
              </div>
            </div>
          </div>

          <!-- Card Footer (GPS status) -->
          <div class="p-4 pt-3 border-t border-slate-100 flex justify-between items-center text-[11px] text-slate-400 font-mono bg-slate-50/50">
            <span>{{ ev.lat ? `${Number(ev.lat).toFixed(4)}, ${Number(ev.lng).toFixed(4)}` : 'Lokasi Sekolah' }}</span>
            <span class="text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-md flex items-center gap-1">
              <span>📍</span>
              <span>Tervalidasi KBM</span>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- TAB 3: PRESENSI HARIAN SISWA (WALI KELAS & ADMIN)             -->
    <!-- ============================================================== -->
    <div v-else-if="activeTab === 'manual_input' && (auth.isAdmin || auth.isWaliKelas)" class="space-y-6">
      <!-- Form Presensi Harian Siswa (Khusus Wali Kelas & Admin) -->
      <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-7 space-y-6">
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded-full uppercase tracking-wider">
              Khusus Wali Kelas & Admin
            </span>
            <span class="text-xs text-slate-400">1 Kali Sehari</span>
          </div>
          <h3 class="font-bold text-slate-900 text-base">Presensi Harian Siswa Rombel</h3>
          <p class="text-xs text-slate-500">
            Formulir pencatatan absensi harian siswa oleh Wali Kelas. Data ini menjadi rekap resmi kehadiran siswa untuk sekolah dan rapor.
          </p>
        </div>

        <!-- Status Validasi Presensi Hari Ini untuk Manual Input -->
        <div
          v-if="manualAttendanceMeta?.already_recorded"
          class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-950 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs"
        >
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black text-sm shrink-0">
              ✓
            </div>
            <div>
              <h4 class="font-bold text-xs sm:text-sm text-emerald-950 flex items-center gap-2 flex-wrap">
                <span>Presensi Rombel Ini Sudah Tervalidasi</span>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-200 text-emerald-900 border border-emerald-300">
                  🔒 TERKUNCI (1x SEHARI)
                </span>
              </h4>
              <p class="text-xs text-emerald-800 mt-1">
                Tercatat pada <strong>{{ manualAttendanceMeta.updated_at ? new Date(manualAttendanceMeta.updated_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB' : '-' }}</strong> 
                oleh <strong>{{ manualAttendanceMeta.recorded_by || 'Wali Kelas' }}</strong>. 
                <span v-if="!auth.isAdmin">
                  Tombol pengabsenan dinonaktifkan karena wali kelas tidak dapat mengabsen untuk kedua kalinya. Hubungi Admin jika butuh perbaikan.
                </span>
                <span v-else>
                  Mode Administrator / Kepala Sekolah: Anda memiliki hak supervisi untuk memperbarui data jika diperlukan.
                </span>
              </p>
            </div>
          </div>
          <span class="px-3 py-1 bg-emerald-600 text-white rounded-xl text-[10px] font-extrabold uppercase shrink-0 text-center self-start sm:self-center">
            Sudah Diabsen
          </span>
        </div>

        <!-- Selection Header Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-200/80 items-end">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Rombel Kelas Binaan *</label>
            <select 
              v-model="inputClassId" 
              @change="loadStudentsForManualInput"
              class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-emerald-500 bg-white"
            >
              <option v-for="c in classes" :key="c.id" :value="String(c.id)">
                {{ c.nama }} (Tingkat {{ c.tingkat }}) {{ isMyHomeroom(c.id) ? ' ⭐ (Binaan Saya)' : '' }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Absensi Harian</label>
            <input
              type="date"
              v-model="selectedDate"
              @change="loadStudentsForManualInput"
              class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-emerald-500 bg-white"
            />
          </div>
          <div>
            <button 
              v-if="isManualAttendanceLocked"
              disabled
              class="w-full bg-slate-200 text-slate-400 font-bold py-2.5 px-4 rounded-xl border border-slate-300 transition text-xs flex items-center justify-center gap-2 cursor-not-allowed shadow-none"
              title="Presensi sudah divalidasi hari ini. Tombol dinonaktifkan."
            >
              <span>🔒 Presensi Terkunci</span>
            </button>
            <button 
              v-else
              @click="submitManualStudentAttendance"
              :disabled="savingStudentAttendance || inputStudentList.length === 0"
              class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl shadow-md shadow-emerald-600/20 transition disabled:opacity-50 text-xs flex items-center justify-center gap-2 cursor-pointer"
            >
              <span v-if="savingStudentAttendance">Menyimpan Presensi...</span>
              <span v-else>💾 Simpan Presensi Harian</span>
            </button>
          </div>
        </div>

      <!-- Quick Set Buttons -->
      <div v-if="!isManualAttendanceLocked" class="flex flex-wrap items-center justify-between gap-3 text-xs bg-slate-50/50 p-3 rounded-xl border border-slate-100">
        <div class="flex items-center gap-2">
          <span class="font-bold text-slate-600">Set Cepat:</span>
          <button 
            @click="setAllManualStatus('hadir')"
            class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold px-3 py-1 rounded-lg border border-emerald-200 transition"
          >
            Hadir Semua (H)
          </button>
          <button 
            @click="setAllManualStatus('sakit')"
            class="bg-sky-50 hover:bg-sky-100 text-sky-800 font-bold px-3 py-1 rounded-lg border border-sky-200 transition"
          >
            Sakit Semua (S)
          </button>
        </div>
        <span class="text-slate-400 font-mono text-[11px]">{{ inputStudentList.length }} Siswa di Rombel</span>
      </div>
      <div v-else class="flex items-center justify-between gap-3 text-xs bg-slate-50/70 p-3 rounded-xl border border-slate-200 text-slate-500">
        <span class="flex items-center gap-1.5 font-medium">
          <span>🔒</span>
          <span>Mode Hanya Lihat: Presensi rombel ini telah selesai divalidasi dan terkunci.</span>
        </span>
        <span class="font-mono text-[11px] text-slate-400">{{ inputStudentList.length }} Siswa di Rombel</span>
      </div>

      <!-- Student Input Table -->
      <div class="overflow-x-auto border border-slate-200 rounded-2xl">
        <table class="w-full text-left border-collapse text-xs min-w-[700px]">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase text-[11px]">
              <th class="p-3.5 w-12 text-center">No</th>
              <th class="p-3.5">Nama Siswa</th>
              <th class="p-3.5">NISN / NIS</th>
              <th class="p-3.5 text-center">Status Kehadiran</th>
              <th class="p-3.5">Catatan / Keterangan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="loading">
              <td colspan="5" class="text-center py-8 text-slate-400">Memuat data siswa kelas...</td>
            </tr>
            <tr v-else-if="inputStudentList.length === 0">
              <td colspan="5" class="text-center py-8 text-slate-400">Belum ada siswa terdaftar di rombel ini.</td>
            </tr>
            <tr v-for="(student, idx) in inputStudentList" :key="student.student_id" class="hover:bg-slate-50/70">
              <td class="p-3.5 text-center font-mono text-slate-400">{{ idx + 1 }}</td>
              <td class="p-3.5 font-bold text-slate-900">
                {{ student.nama_lengkap }}
              </td>
              <td class="p-3.5 font-mono text-slate-400 text-xs">{{ student.nisn || student.nis || '-' }}</td>
              <td class="p-3.5">
                <div class="flex justify-center gap-1.5">
                  <label 
                    class="px-2.5 py-1 rounded-lg text-xs font-bold border transition-all"
                    :class="[
                      inputAttendances[student.student_id] === 'hadir' ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs' : 'bg-slate-50 text-slate-600 border-slate-200',
                      isManualAttendanceLocked ? 'cursor-not-allowed opacity-90' : 'cursor-pointer hover:bg-slate-100'
                    ]"
                  >
                    <input type="radio" :disabled="isManualAttendanceLocked" :name="'att-' + student.student_id" value="hadir" v-model="inputAttendances[student.student_id]" class="hidden" />
                    Hadir
                  </label>
                  <label 
                    class="px-2.5 py-1 rounded-lg text-xs font-bold border transition-all"
                    :class="[
                      inputAttendances[student.student_id] === 'izin' ? 'bg-amber-500 text-white border-amber-500 shadow-xs' : 'bg-slate-50 text-slate-600 border-slate-200',
                      isManualAttendanceLocked ? 'cursor-not-allowed opacity-90' : 'cursor-pointer hover:bg-slate-100'
                    ]"
                  >
                    <input type="radio" :disabled="isManualAttendanceLocked" :name="'att-' + student.student_id" value="izin" v-model="inputAttendances[student.student_id]" class="hidden" />
                    Izin
                  </label>
                  <label 
                    class="px-2.5 py-1 rounded-lg text-xs font-bold border transition-all"
                    :class="[
                      inputAttendances[student.student_id] === 'sakit' ? 'bg-sky-500 text-white border-sky-500 shadow-xs' : 'bg-slate-50 text-slate-600 border-slate-200',
                      isManualAttendanceLocked ? 'cursor-not-allowed opacity-90' : 'cursor-pointer hover:bg-slate-100'
                    ]"
                  >
                    <input type="radio" :disabled="isManualAttendanceLocked" :name="'att-' + student.student_id" value="sakit" v-model="inputAttendances[student.student_id]" class="hidden" />
                    Sakit
                  </label>
                  <label 
                    class="px-2.5 py-1 rounded-lg text-xs font-bold border transition-all"
                    :class="[
                      inputAttendances[student.student_id] === 'alpa' ? 'bg-rose-600 text-white border-rose-600 shadow-xs' : 'bg-slate-50 text-slate-600 border-slate-200',
                      isManualAttendanceLocked ? 'cursor-not-allowed opacity-90' : 'cursor-pointer hover:bg-slate-100'
                    ]"
                  >
                    <input type="radio" :disabled="isManualAttendanceLocked" :name="'att-' + student.student_id" value="alpa" v-model="inputAttendances[student.student_id]" class="hidden" />
                    Alpa
                  </label>
                </div>
              </td>
              <td class="p-3.5">
                <input
                  v-model="inputNotes[student.student_id]"
                  :disabled="isManualAttendanceLocked"
                  type="text"
                  placeholder="Keterangan..."
                  class="w-full border border-slate-200 rounded-lg px-2.5 py-1 text-xs bg-white disabled:bg-slate-100 disabled:cursor-not-allowed disabled:text-slate-500"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

    <!-- ============================================================== -->
    <!-- TAB 4: PRESENSI GURU & TENDIK MANDIRI (GPS CHECK-IN - ADMIN ONLY)-->
    <!-- ============================================================== -->
    <div v-else-if="auth.isAdmin && activeTab === 'teachers'" class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden p-5 sm:p-7 space-y-4">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-2">
        <div>
          <h3 class="font-bold text-slate-900 text-sm sm:text-base">Daftar Kehadiran Pendidik & Tenaga Kependidikan</h3>
          <p class="text-xs text-slate-500">Rekap check-in / check-out GPS mandiri guru dan staf hari ini.</p>
        </div>
        <button 
          @click="fetchTeacherAttendances" 
          class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3 py-1.5 rounded-xl transition"
        >
          Refresh
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs min-w-[700px]">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase text-[11px]">
              <th class="p-3.5">Guru / Tendik</th>
              <th class="p-3.5">Status</th>
              <th class="p-3.5">Jam Masuk</th>
              <th class="p-3.5">Jam Pulang</th>
              <th class="p-3.5">Jarak GPS (m)</th>
              <th class="p-3.5">Keterangan</th>
              <th class="p-3.5 text-right">Aksi Approval</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="loading">
              <td colspan="7" class="text-center py-8 text-slate-400">Memuat data presensi guru...</td>
            </tr>
            <tr v-else-if="teacherAttendances.length === 0">
              <td colspan="7" class="text-center py-8 text-slate-400">Belum ada data presensi pada tanggal ini.</td>
            </tr>
            <tr v-for="att in teacherAttendances" :key="att.id" class="hover:bg-slate-50/70 transition-colors">
              <td class="p-3.5 font-bold text-slate-800 whitespace-nowrap">
                {{ att.teacher?.nama_lengkap || att.user?.nama || 'Guru' }}
                <div class="text-[10px] text-slate-400 font-normal">NIP: {{ att.teacher?.nip || '-' }}</div>
              </td>
              <td class="p-3.5">
                <span 
                  class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                  :class="{
                    'bg-emerald-100 text-emerald-800 border border-emerald-200': att.status === 'hadir',
                    'bg-amber-100 text-amber-800 border border-amber-200': att.status === 'terlambat',
                    'bg-blue-100 text-blue-800 border border-blue-200': att.status === 'izin',
                    'bg-purple-100 text-purple-800 border border-purple-200': att.status === 'sakit',
                    'bg-rose-100 text-rose-800 border border-rose-200': att.status === 'alpa'
                  }"
                >
                  {{ att.status }}
                </span>
              </td>
              <td class="p-3.5 font-mono text-slate-700">{{ att.jam_masuk || '-' }}</td>
              <td class="p-3.5 font-mono text-slate-700">{{ att.jam_pulang || '-' }}</td>
              <td class="p-3.5 font-mono text-xs">
                <span v-if="att.jarak_masuk_m !== null" :class="att.jarak_masuk_m <= 100 ? 'text-emerald-600 font-bold' : 'text-rose-600 font-bold'">
                  {{ att.jarak_masuk_m }} m
                </span>
                <span v-else class="text-slate-400">-</span>
              </td>
              <td class="p-3.5 text-xs text-slate-600 max-w-xs truncate">{{ att.catatan || att.alasan_izin || '-' }}</td>
              <td class="p-3.5 text-right">
                <div v-if="att.status === 'izin' || att.status === 'sakit'" class="flex justify-end gap-1.5">
                  <button 
                    @click="approveLeave(att.id, 'disetujui')"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-2.5 py-1 rounded-lg font-bold shadow-xs"
                  >
                    Setujui
                  </button>
                  <button 
                    @click="approveLeave(att.id, 'ditolak')"
                    class="bg-rose-600 hover:bg-rose-700 text-white text-xs px-2.5 py-1 rounded-lg font-bold shadow-xs"
                  >
                    Tolak
                  </button>
                </div>
                <span v-else class="text-xs text-slate-400">Terverifikasi</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- IMAGE MODAL VIEWER (FULLSCREEN BUKTI KBM)                      -->
    <!-- ============================================================== -->
    <div 
      v-if="selectedEvidenceImage" 
      class="fixed inset-0 bg-black/85 flex items-center justify-center z-50 p-4 backdrop-blur-xs"
      @click="selectedEvidenceImage = null"
    >
      <div class="max-w-3xl w-full bg-slate-900 rounded-3xl overflow-hidden p-3 shadow-2xl space-y-3" @click.stop>
        <div class="p-3 flex items-center justify-between text-white border-b border-slate-800">
          <div>
            <h4 class="text-sm font-bold text-white">
              {{ selectedEvidenceItem?.schedule?.subject?.nama }} — {{ selectedEvidenceItem?.schedule?.school_class?.nama }}
            </h4>
            <p class="text-xs text-slate-400">
              Guru: {{ selectedEvidenceItem?.teacher?.nama_lengkap }} • {{ selectedEvidenceItem?.materi_ajar || 'Materi KBM' }}
            </p>
          </div>
          <button @click="selectedEvidenceImage = null" class="text-white hover:text-slate-300 font-bold text-xl p-1">&times;</button>
        </div>

        <div class="flex items-center justify-center max-h-[75vh] overflow-hidden rounded-2xl bg-black">
          <img :src="selectedEvidenceImage" alt="Preview Bukti KBM" class="w-full h-auto max-h-[75vh] object-contain rounded-xl" />
        </div>

        <div class="p-2 flex justify-between items-center text-xs text-slate-400">
          <span>{{ selectedEvidenceItem?.captured_at ? new Date(selectedEvidenceItem.captured_at).toLocaleString('id-ID') : '' }}</span>
          <button @click="selectedEvidenceImage = null" class="px-4 py-1.5 rounded-xl bg-slate-800 text-white font-bold hover:bg-slate-700">
            Tutup
          </button>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL RINCIAN PRESENSI SISWA PER MATA PELAJARAN (SIAP RAPOR)   -->
    <!-- ============================================================== -->
    <div
      v-if="selectedStudentDetail"
      class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4 backdrop-blur-xs"
      @click="selectedStudentDetail = null"
    >
      <div class="max-w-2xl w-full bg-white rounded-3xl overflow-hidden p-6 shadow-2xl space-y-5" @click.stop>
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 shrink-0 flex items-center justify-center font-bold text-sm text-slate-500">
              <img v-if="selectedStudentDetail.foto_path" :src="getPhotoUrl(selectedStudentDetail.foto_path)" class="w-full h-full object-cover" />
              <span v-else>{{ getInitials(selectedStudentDetail.nama_lengkap) }}</span>
            </div>
            <div>
              <h4 class="text-base font-black text-slate-900">{{ selectedStudentDetail.nama_lengkap }}</h4>
              <p class="text-xs text-slate-400">
                NIS: <span class="font-semibold text-slate-700">{{ selectedStudentDetail.nis || '-' }}</span> •
                NISN: <span class="font-semibold text-slate-700">{{ selectedStudentDetail.nisn || '-' }}</span> •
                Kelas: <span class="font-bold text-emerald-700">{{ classRecapData?.class?.nama }}</span>
              </p>
            </div>
          </div>
          <button @click="selectedStudentDetail = null" class="text-slate-400 hover:text-slate-700 font-bold text-2xl p-1 cursor-pointer">&times;</button>
        </div>

        <!-- Mini Stats Summary -->
        <div class="grid grid-cols-4 gap-2 text-center">
          <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-100">
            <span class="text-[10px] font-bold text-emerald-600 uppercase block">Hadir</span>
            <span class="text-lg font-black text-emerald-800">{{ selectedStudentDetail.hadir }}</span>
          </div>
          <div class="p-3 bg-sky-50 rounded-2xl border border-sky-100">
            <span class="text-[10px] font-bold text-sky-600 uppercase block">Sakit</span>
            <span class="text-lg font-black text-sky-800">{{ selectedStudentDetail.sakit }}</span>
          </div>
          <div class="p-3 bg-amber-50 rounded-2xl border border-amber-100">
            <span class="text-[10px] font-bold text-amber-600 uppercase block">Izin</span>
            <span class="text-lg font-black text-amber-800">{{ selectedStudentDetail.izin }}</span>
          </div>
          <div class="p-3 bg-rose-50 rounded-2xl border border-rose-100">
            <span class="text-[10px] font-bold text-rose-600 uppercase block">Alpa</span>
            <span class="text-lg font-black text-rose-800">{{ selectedStudentDetail.alpa }}</span>
          </div>
        </div>

        <!-- Table Breakdown Per Mata Pelajaran -->
        <div>
          <h5 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2 flex items-center justify-between">
            <span>Rincian Kehadiran Per Mata Pelajaran</span>
            <span class="text-[11px] font-normal text-slate-400">Periode: {{ ledgerStartDate }} s/d {{ ledgerEndDate }}</span>
          </h5>

          <div class="border border-slate-200 rounded-2xl overflow-hidden max-h-60 overflow-y-auto">
            <table class="w-full text-left text-xs text-slate-600">
              <thead class="bg-slate-50 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                <tr>
                  <th class="py-2.5 px-3">Mata Pelajaran</th>
                  <th class="py-2.5 px-2 text-center w-12">Sesi</th>
                  <th class="py-2.5 px-2 text-center w-10 text-emerald-700">H</th>
                  <th class="py-2.5 px-2 text-center w-10 text-sky-700">S</th>
                  <th class="py-2.5 px-2 text-center w-10 text-amber-700">I</th>
                  <th class="py-2.5 px-2 text-center w-10 text-rose-700">A</th>
                  <th class="py-2.5 px-3 text-center w-20">% Hadir</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr v-if="!selectedStudentDetail.subjects || selectedStudentDetail.subjects.length === 0">
                  <td colspan="7" class="py-6 text-center text-slate-400">Belum ada rincian mata pelajaran</td>
                </tr>
                <tr
                  v-for="sub in selectedStudentDetail.subjects"
                  :key="sub.subject_id"
                  class="hover:bg-slate-50 transition"
                >
                  <td class="py-2.5 px-3">
                    <span class="font-bold text-slate-800 block">{{ sub.subject_nama }}</span>
                    <span class="text-[10px] text-slate-400">{{ sub.subject_kode || '-' }}</span>
                  </td>
                  <td class="py-2.5 px-2 text-center font-bold text-purple-700">{{ sub.total_pertemuan }}</td>
                  <td class="py-2.5 px-2 text-center font-bold text-emerald-800">{{ sub.hadir }}</td>
                  <td class="py-2.5 px-2 text-center" :class="sub.sakit > 0 ? 'font-bold text-sky-800' : 'text-slate-400'">{{ sub.sakit }}</td>
                  <td class="py-2.5 px-2 text-center" :class="sub.izin > 0 ? 'font-bold text-amber-800' : 'text-slate-400'">{{ sub.izin }}</td>
                  <td class="py-2.5 px-2 text-center" :class="sub.alpa > 0 ? 'font-bold text-rose-800' : 'text-slate-400'">{{ sub.alpa }}</td>
                  <td class="py-2.5 px-3 text-center">
                    <span
                      class="px-2 py-0.5 rounded-full font-bold text-[11px]"
                      :class="sub.persentase >= 80 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                    >
                      {{ sub.persentase }}%
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Rapor Integration Note -->
        <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200/80 flex items-start gap-2.5 text-xs text-emerald-800">
          <span class="text-base shrink-0">🎓</span>
          <div>
            <span class="font-bold block">Sinkronisasi Rapor Siswa</span>
            <span class="text-[11px] text-emerald-700 leading-relaxed block mt-0.5">
              Data akumulasi ini secara otomatis dihubungkan ke lembar Rapor Siswa untuk memenuhi syarat presensi mata pelajaran serta rekap absen semester.
            </span>
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button
            @click="selectedStudentDetail = null"
            class="px-5 py-2.5 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition cursor-pointer"
          >
            Tutup Rincian
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
