<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
import { showSuccess, showError, showConfirm } from '../services/swal'
import { formatDateIndo } from '../utils/date'

const auth = useAuthStore()

// Navigation View State: 'student-list' | 'student-history' | 'all-incidents' | 'form' | 'feedback'
const currentView = ref<'student-list' | 'student-history' | 'all-incidents' | 'form' | 'feedback'>('student-list')
const returnView = ref<'student-list' | 'student-history' | 'all-incidents'>('student-list')

// State: Student Summaries (Daftar Siswa dengan Catatan Observasi)
const loadingSummaries = ref(false)
const studentSummaries = ref<any[]>([])
const summaryMeta = ref({
  total_students: 0,
  total_unseen: 0,
  total_unreviewed: 0,
})

// State: All Incidents (Semua Kejadian Mentah)
const loadingIncidents = ref(false)
const rawObservations = ref<any[]>([])
const incidentsCurrentPage = ref(1)
const incidentsLastPage = ref(1)
const incidentsTotal = ref(0)

// State: Single Student History (Rekam Jejak Siswa Terpilih)
const selectedStudent = ref<any>(null)
const studentHistoryObservations = ref<any[]>([])
const loadingStudentHistory = ref(false)

// Reference Data
const classes = ref<any[]>([])
const students = ref<any[]>([])

// Filters
const filterClass = ref<string>('')
const filterTinjauan = ref<string>('')
const searchQuery = ref<string>('')

// Form State (Create / Edit Observation)
const isEditing = ref(false)
const editingId = ref<number | null>(null)
const submitting = ref(false)

const form = ref({
  student_id: '',
  tanggal: new Date().toISOString().split('T')[0],
  waktu: '',
  tempat: '',
  situasi_sebelum: '',
  perilaku_diamati: '',
  respons_lingkungan: '',
  catatan_wali_kelas: '',
  status_pemantauan: 'Baru',
})

// Feedback BK State
const feedbackObs = ref<any>(null)
const submittingFeedback = ref(false)
const feedbackForm = ref({
  rekomendasi_bk: '',
  saran_untuk_guru: '',
  masuk_pemantauan_bk: true,
  fokus_pemantauan: 'Perilaku',
  status_pemantauan: 'Dalam Pemantauan',
})

// Focus Categories for BK
const focusList = [
  'Perilaku',
  'Emosi/Regulasi Diri',
  'Sosial',
  'Belajar',
  'Kedisiplinan',
  'Kehadiran',
  'Perkembangan',
  'Pribadi',
  'Potensi/Bakat',
  'Lainnya',
]

// Helpers
const getInitials = (name?: string) => {
  if (!name) return '?'
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase()
  return name.substring(0, 2).toUpperCase()
}

const getClassName = (item: any) => {
  if (!item) return '-'
  return item.school_class?.nama ||
         item.schoolClass?.nama ||
         item.student?.school_class?.nama ||
         item.student?.current_class?.nama ||
         item.student?.kelas?.nama ||
         item.student?.class_name ||
         item.class_name ||
         item.kelas?.nama ||
         '-'
}

// Fetch Master Reference
const fetchClasses = async () => {
  try {
    const res = await api.get('/classes')
    if (res.data.success) {
      classes.value = res.data.data || []
    }
  } catch (err) {
    console.error('Gagal mengambil data rombel', err)
  }
}

const fetchStudents = async () => {
  try {
    const res = await api.get('/students?per_page=200')
    if (res.data.success) {
      students.value = res.data.data || []
    }
  } catch (err) {
    console.error('Gagal mengambil data siswa', err)
  }
}

// Fetch Grouped Student Summaries
const fetchStudentSummaries = async () => {
  loadingSummaries.value = true
  try {
    let url = '/observations/students?'
    if (filterClass.value) url += `&class_id=${filterClass.value}`
    if (filterTinjauan.value) url += `&status_tinjauan=${encodeURIComponent(filterTinjauan.value)}`
    if (searchQuery.value) url += `&search=${encodeURIComponent(searchQuery.value)}`

    const res = await api.get(url)
    if (res.data.success) {
      studentSummaries.value = res.data.data || []
      summaryMeta.value = res.data.meta || { total_students: 0, total_unseen: 0, total_unreviewed: 0 }
    }
  } catch (err) {
    console.error('Gagal memuat rekap siswa observasi', err)
  } finally {
    loadingSummaries.value = false
  }
}

// Fetch Flat Incidents List
const fetchRawIncidents = async (page = 1) => {
  loadingIncidents.value = true
  try {
    let url = `/observations?page=${page}`
    if (filterClass.value) url += `&class_id=${filterClass.value}`
    if (searchQuery.value) url += `&search=${encodeURIComponent(searchQuery.value)}`

    const res = await api.get(url)
    if (res.data.success) {
      rawObservations.value = res.data.data || []
      incidentsCurrentPage.value = res.data.meta?.current_page || 1
      incidentsLastPage.value = res.data.meta?.last_page || 1
      incidentsTotal.value = res.data.meta?.total || 0
    }
  } catch (err) {
    console.error('Gagal memuat daftar mentah observasi', err)
  } finally {
    loadingIncidents.value = false
  }
}

// Open Dedicated Student History Page
const openStudentHistory = async (student: any) => {
  selectedStudent.value = student
  loadingStudentHistory.value = true
  currentView.value = 'student-history'
  window.scrollTo({ top: 0, behavior: 'smooth' })

  try {
    const res = await api.get(`/observations/students/${student.id}`)
    if (res.data.success) {
      selectedStudent.value = res.data.data?.student || student
      studentHistoryObservations.value = res.data.data?.observations || []

      // Refresh summaries counter in background if Guru BK viewed
      if (auth.isGuruBk) {
        fetchStudentSummaries()
      }
    }
  } catch (err) {
    showError('Gagal Memuat Riwayat Catatan', err)
  } finally {
    loadingStudentHistory.value = false
  }
}

// Form Handlers
const openCreateForm = (preselectedStudentId?: string | number) => {
  isEditing.value = false
  editingId.value = null
  form.value = {
    student_id: preselectedStudentId ? String(preselectedStudentId) : '',
    tanggal: new Date().toISOString().split('T')[0],
    waktu: 'Jam Pelajaran',
    tempat: 'Ruang Kelas',
    situasi_sebelum: '',
    perilaku_diamati: '',
    respons_lingkungan: '',
    catatan_wali_kelas: '',
    status_pemantauan: 'Baru',
  }
  returnView.value = currentView.value === 'student-history' ? 'student-history' : 'student-list'
  currentView.value = 'form'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const openEditForm = (obs: any) => {
  isEditing.value = true
  editingId.value = obs.id
  form.value = {
    student_id: obs.student_id ? String(obs.student_id) : '',
    tanggal: obs.tanggal ? obs.tanggal.substring(0, 10) : new Date().toISOString().split('T')[0],
    waktu: obs.waktu || '',
    tempat: obs.tempat || '',
    situasi_sebelum: obs.situasi_sebelum || '',
    perilaku_diamati: obs.perilaku_diamati || '',
    respons_lingkungan: obs.respons_lingkungan || '',
    catatan_wali_kelas: obs.catatan_wali_kelas || '',
    status_pemantauan: obs.status_pemantauan || 'Baru',
  }
  returnView.value = currentView.value === 'student-history' ? 'student-history' : 'student-list'
  currentView.value = 'form'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const submitForm = async () => {
  if (!form.value.student_id) {
    showError('Validasi Gagal', null, 'Silakan pilih siswa yang diobservasi.')
    return
  }
  if (!form.value.perilaku_diamati) {
    showError('Validasi Gagal', null, 'Deskripsi perilaku yang diamati wajib diisi.')
    return
  }

  submitting.value = true
  try {
    if (isEditing.value && editingId.value) {
      const res = await api.put(`/observations/${editingId.value}`, form.value)
      if (res.data.success) {
        showSuccess('Berhasil!', 'Catatan observasi berhasil diperbarui.')
        handleReturnAfterSave()
      }
    } else {
      const res = await api.post('/observations', form.value)
      if (res.data.success) {
        showSuccess('Berhasil Dilaporkan!', 'Laporan kejadian berhasil disimpan dan dikirimkan ke Guru BK.')
        handleReturnAfterSave()
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan', err, 'Terjadi kesalahan saat menyimpan catatan observasi.')
  } finally {
    submitting.value = false
  }
}

const handleReturnAfterSave = () => {
  if (returnView.value === 'student-history' && selectedStudent.value?.id) {
    openStudentHistory(selectedStudent.value)
  } else {
    currentView.value = 'student-list'
    fetchStudentSummaries()
  }
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// BK Feedback Handlers
const openFeedbackForm = (obs: any) => {
  feedbackObs.value = obs
  feedbackForm.value = {
    rekomendasi_bk: obs.rekomendasi_bk || '',
    saran_untuk_guru: obs.saran_untuk_guru || '',
    masuk_pemantauan_bk: obs.is_escalated_to_bk ? true : false,
    fokus_pemantauan: 'Perilaku',
    status_pemantauan: obs.status_pemantauan || (obs.is_escalated_to_bk ? 'Dalam Pemantauan' : 'Selesai / Cukup di Kelas'),
  }
  returnView.value = currentView.value === 'student-history' ? 'student-history' : 'student-list'
  currentView.value = 'feedback'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const submitFeedback = async () => {
  if (!feedbackForm.value.rekomendasi_bk) {
    showError('Validasi Gagal', null, 'Rekomendasi dari Guru BK wajib diisi.')
    return
  }

  submittingFeedback.value = true
  try {
    const res = await api.post(`/observations/${feedbackObs.value.id}/feedback-bk`, feedbackForm.value)
    if (res.data.success) {
      showSuccess('Tanggapan Tersimpan!', res.data.message)
      handleReturnAfterSave()
    }
  } catch (err: any) {
    showError('Gagal Menyimpan Rekomendasi', err)
  } finally {
    submittingFeedback.value = false
  }
}

// Delete Observation
const deleteObservation = async (obs: any) => {
  const confirmed = await showConfirm(
    'Hapus Catatan Observasi?',
    'Data catatan observasi kejadian ini akan dihapus secara permanen.',
    'Ya, Hapus',
    'warning'
  )
  if (!confirmed) return

  try {
    const res = await api.delete(`/observations/${obs.id}`)
    if (res.data.success) {
      showSuccess('Berhasil Dihapus!', res.data.message)
      if (currentView.value === 'student-history' && selectedStudent.value?.id) {
        openStudentHistory(selectedStudent.value)
      } else {
        fetchStudentSummaries()
      }
    }
  } catch (err: any) {
    showError('Gagal Menghapus', err)
  }
}

const backToStudentList = () => {
  currentView.value = 'student-list'
  fetchStudentSummaries()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(() => {
  fetchClasses()
  fetchStudents()
  fetchStudentSummaries()
})
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-200">

    <!-- ========================================================================= -->
    <!-- VIEW 1: DAFTAR PESERTA DIDIK DENGAN CATATAN OBSERVASI (STUDENT LIST VIEW) -->
    <!-- ========================================================================= -->
    <template v-if="currentView === 'student-list' || currentView === 'all-incidents'">
      <!-- Header Banner Card -->
      <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-sky-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20 shrink-0">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span v-if="auth.isGuruBk" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                Mode Layanan Guru BK
              </span>
              <span v-else class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                Pencatatan Guru Kelas
              </span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-gray-900 leading-tight mt-1">
              Catatan Observasi Perilaku Siswa
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              Daftar peserta didik yang memiliki rekam catatan observasi kelas. Klik <strong>"Lihat Catatan"</strong> untuk membuka seluruh rekam jejak kejadian dan tindak lanjut BK.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2.5 self-start md:self-auto">
          <button
            @click="openCreateForm()"
            type="button"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-600/20 transition active:scale-95 cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>+ Catat Kejadian Baru</span>
          </button>
        </div>
      </div>

      <!-- Quick Metrics Ribbon: Pengingat Status Tinjauan BK -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <!-- Metric 1: Total Siswa -->
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Siswa Memiliki Catatan</span>
            <div class="text-xl font-black text-gray-900 mt-0.5">{{ summaryMeta.total_students }} Siswa</div>
          </div>
          <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-base">
            👥
          </div>
        </div>

        <!-- Metric 2: Belum Dilihat BK (Pengingat Utama) -->
        <div class="bg-white p-4 rounded-2xl border border-rose-100 shadow-xs flex items-center justify-between" :class="summaryMeta.total_unseen > 0 ? 'ring-2 ring-rose-400/30 bg-rose-50/10' : ''">
          <div>
            <div class="flex items-center gap-1.5">
              <span class="text-[11px] font-bold text-rose-600 uppercase tracking-wider">Belum Dilihat Guru BK</span>
              <span v-if="summaryMeta.total_unseen > 0" class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
            </div>
            <div class="text-xl font-black text-rose-600 mt-0.5">{{ summaryMeta.total_unseen }} Siswa</div>
          </div>
          <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-base">
            🔔
          </div>
        </div>

        <!-- Metric 3: Menunggu Rekomendasi BK -->
        <div class="bg-white p-4 rounded-2xl border border-amber-100 shadow-xs flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Menunggu Rekomendasi BK</span>
            <div class="text-xl font-black text-amber-600 mt-0.5">{{ summaryMeta.total_unreviewed }} Siswa</div>
          </div>
          <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-base">
            ⏳
          </div>
        </div>
      </div>

      <!-- Navigation Switcher Tab (Siswa vs Semua Kejadian) -->
      <div class="flex items-center justify-between border-b border-gray-200 pb-1">
        <div class="flex items-center gap-2">
          <button
            @click="currentView = 'student-list'"
            type="button"
            class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-2"
            :class="currentView === 'student-list' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          >
            <span>👤 Daftar Peserta Didik</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/20">
              {{ summaryMeta.total_students }}
            </span>
          </button>
          <button
            @click="currentView = 'all-incidents'; fetchRawIncidents(1)"
            type="button"
            class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-2"
            :class="currentView === 'all-incidents' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          >
            <span>📋 Semua Catatan Kejadian Faktual</span>
          </button>
        </div>
      </div>

      <!-- Filter Toolbar -->
      <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2.5 flex-1 min-w-[280px]">
          <!-- Search Input -->
          <div class="relative flex-1 min-w-[200px]">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="searchQuery"
              @keyup.enter="currentView === 'student-list' ? fetchStudentSummaries() : fetchRawIncidents(1)"
              type="text"
              placeholder="Cari nama siswa, NIS, atau perilaku..."
              class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
            />
          </div>

          <!-- Filter Kelas -->
          <select
            v-model="filterClass"
            @change="currentView === 'student-list' ? fetchStudentSummaries() : fetchRawIncidents(1)"
            class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
          >
            <option value="">Semua Rombel / Kelas</option>
            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nama }}</option>
          </select>

          <!-- Filter Status Tinjauan BK (Untuk Tampilan Siswa) -->
          <select
            v-if="currentView === 'student-list'"
            v-model="filterTinjauan"
            @change="fetchStudentSummaries()"
            class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
          >
            <option value="">Semua Status Tinjauan</option>
            <option value="belum_dilihat">🔔 Belum Dilihat BK</option>
            <option value="menunggu_tanggapan">⏳ Menunggu Rekomendasi BK</option>
            <option value="masuk_bk">🎯 Masuk Pantauan BK</option>
            <option value="sudah_ditanggapi">✓ Cukup di Kelas</option>
          </select>
        </div>

        <button
          @click="currentView === 'student-list' ? fetchStudentSummaries() : fetchRawIncidents(1)"
          type="button"
          class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition cursor-pointer"
        >
          Segarkan
        </button>
      </div>

      <!-- ============================================== -->
      <!-- SUBTAB A: TABEL PESERTA DIDIK (GROUPED)        -->
      <!-- ============================================== -->
      <div v-if="currentView === 'student-list'" class="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
        <div v-if="loadingSummaries" class="p-12 text-center text-slate-400">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-600 border-t-transparent mb-2"></div>
          <p class="text-xs font-medium">Memuat daftar peserta didik...</p>
        </div>

        <div v-else-if="studentSummaries.length === 0" class="p-12 text-center">
          <div class="w-16 h-16 mx-auto bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 mb-3">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
          <h3 class="text-sm font-bold text-gray-800">Belum Ada Peserta Didik dengan Catatan</h3>
          <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">
            Klik tombol "+ Catat Kejadian Baru" untuk mendokumentasikan observasi perilaku pertama bagi peserta didik.
          </p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/80 border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                <th class="py-3.5 px-4 w-12 text-center">No.</th>
                <th class="py-3.5 px-4 min-w-[220px]">Peserta Didik</th>
                <th class="py-3.5 px-4 text-center min-w-[120px]">Total Catatan</th>
                <th class="py-3.5 px-4 min-w-[260px]">Kejadian Terakhir &amp; Pelapor</th>
                <th class="py-3.5 px-4 min-w-[200px]">Status Tinjauan BK</th>
                <th class="py-3.5 px-4 text-center w-40">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="(row, idx) in studentSummaries"
                :key="row.student.id"
                class="hover:bg-indigo-50/30 transition-colors"
                :class="row.unseen_count > 0 ? 'bg-rose-50/20' : ''"
              >
                <!-- 1. Nomor -->
                <td class="py-3.5 px-4 text-center font-mono text-slate-400">
                  {{ idx + 1 }}
                </td>

                <!-- 2. Peserta Didik -->
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
                      <img
                        v-if="row.student?.foto_path"
                        :src="row.student.foto_path.startsWith('http') ? row.student.foto_path : `/storage/${row.student.foto_path}`"
                        alt="Foto"
                        class="w-full h-full object-cover"
                      />
                      <span v-else class="text-xs font-bold text-slate-500 uppercase">
                        {{ getInitials(row.student?.nama_lengkap) }}
                      </span>
                    </div>
                    <div>
                      <div class="font-bold text-gray-900 leading-snug flex items-center gap-1.5">
                        <span>{{ row.student?.nama_lengkap }}</span>
                      </div>
                      <div class="flex items-center gap-1.5 text-[10px] text-gray-400 mt-0.5">
                        <span class="px-1.5 py-0.5 rounded bg-slate-100 font-bold text-slate-700">
                          {{ getClassName(row.student) }}
                        </span>
                        <span>NIS: {{ row.student?.nis || '-' }}</span>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- 3. Total Catatan -->
                <td class="py-3.5 px-4 text-center">
                  <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-black bg-indigo-50 text-indigo-700 border border-indigo-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>{{ row.total_observations }} Catatan</span>
                  </span>
                </td>

                <!-- 4. Kejadian Terakhir & Pelapor -->
                <td class="py-3.5 px-4">
                  <div v-if="row.latest_observation" class="space-y-0.5">
                    <div class="flex items-center gap-2 text-[11px] font-bold text-gray-700">
                      <span>🗓️ {{ formatDateIndo(row.latest_observation.tanggal) }}</span>
                      <span v-if="row.latest_observation.tempat" class="text-gray-400 font-normal">📍 {{ row.latest_observation.tempat }}</span>
                    </div>
                    <p class="text-xs text-gray-900 font-medium line-clamp-1">
                      {{ row.latest_observation.perilaku_diamati }}
                    </p>
                    <div class="text-[10px] text-slate-400">
                      Pelapor: <strong>{{ row.latest_observation.observer?.nama_lengkap || 'Guru' }}</strong>
                    </div>
                  </div>
                  <div v-else class="text-slate-400 text-xs italic">
                    Belum ada kejadian tercatat
                  </div>
                </td>

                <!-- 5. Status Tinjauan BK (PENGINGAT SUDAH DILIHAT / BELUM) -->
                <td class="py-3.5 px-4">
                  <!-- Kondisi 1: Belum Dilihat oleh BK -->
                  <div v-if="row.unseen_count > 0" class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-300 animate-pulse">
                      <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                      <span>Belum Dilihat BK ({{ row.unseen_count }} Baru)</span>
                    </span>
                    <p class="text-[10px] text-rose-700">Perlu ditinjau Guru BK</p>
                  </div>

                  <!-- Kondisi 2: Sudah Dilihat tapi Menunggu Rekomendasi BK -->
                  <div v-else-if="row.unreviewed_count > 0" class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                      <span>⏳</span>
                      <span>Menunggu Rekomendasi BK</span>
                    </span>
                    <p class="text-[10px] text-slate-400">Sudah dilihat oleh Guru BK</p>
                  </div>

                  <!-- Kondisi 3: Masuk ke Kasus BK -->
                  <div v-else-if="row.escalated_count > 0" class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                      <span>🎯</span>
                      <span>Masuk Pantauan BK</span>
                    </span>
                    <p class="text-[10px] text-slate-400">Tercatat di sistem monitoring</p>
                  </div>

                  <!-- Kondisi 4: Sudah Ditanggapi & Selesai di Kelas -->
                  <div v-else class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-teal-50 text-teal-800 border border-teal-200">
                      <span>✓</span>
                      <span>Cukup di Kelas</span>
                    </span>
                    <p class="text-[10px] text-slate-400">Semua catatan telah ditanggapi</p>
                  </div>
                </td>

                <!-- 6. Aksi: Lihat Catatan & Catat Baru -->
                <td class="py-3.5 px-4 text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <button
                      @click="openStudentHistory(row.student)"
                      type="button"
                      class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition active:scale-95 cursor-pointer"
                      title="Buka Halaman Rekam Catatan Lengkap Siswa"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      <span>Lihat Catatan</span>
                    </button>

                    <button
                      @click="openCreateForm(row.student.id)"
                      type="button"
                      class="p-1.5 rounded-xl border border-slate-200 text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 transition cursor-pointer"
                      title="Tambah Catatan untuk Siswa Ini"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ======================================================= -->
      <!-- SUBTAB B: TABEL SEMUA CATATAN KEJADIAN FAKTUAL (RAW)    -->
      <!-- ======================================================= -->
      <div v-else-if="currentView === 'all-incidents'" class="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
        <div v-if="loadingIncidents" class="p-12 text-center text-slate-400">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-600 border-t-transparent mb-2"></div>
          <p class="text-xs font-medium">Memuat data semua kejadian...</p>
        </div>

        <div v-else-if="rawObservations.length === 0" class="p-12 text-center">
          <p class="text-xs text-gray-400">Tidak ada catatan kejadian yang sesuai filter.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/80 border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                <th class="py-3.5 px-4 w-12 text-center">No.</th>
                <th class="py-3.5 px-4 min-w-[140px]">Tanggal &amp; Lokasi</th>
                <th class="py-3.5 px-4 min-w-[200px]">Peserta Didik</th>
                <th v-if="auth.isGuruBk" class="py-3.5 px-4 min-w-[150px]">Guru Pelapor</th>
                <th class="py-3.5 px-4 min-w-[240px]">Kejadian Faktual</th>
                <th class="py-3.5 px-4 min-w-[180px]">Status Tinjauan BK</th>
                <th class="py-3.5 px-4 text-center w-28">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(obs, idx) in rawObservations" :key="obs.id" class="hover:bg-slate-50/80">
                <td class="py-3.5 px-4 text-center font-mono text-slate-400">
                  {{ (incidentsCurrentPage - 1) * 15 + idx + 1 }}
                </td>
                <td class="py-3.5 px-4">
                  <div class="font-bold text-gray-900">{{ formatDateIndo(obs.tanggal) }}</div>
                  <div class="text-[10px] text-gray-400">{{ obs.tempat }} {{ obs.waktu ? '• ' + obs.waktu : '' }}</div>
                </td>
                <td class="py-3.5 px-4">
                  <div class="font-bold text-gray-900">{{ obs.student?.nama_lengkap }}</div>
                  <div class="text-[10px] text-gray-400">{{ getClassName(obs) }}</div>
                </td>
                <td v-if="auth.isGuruBk" class="py-3.5 px-4">
                  <div class="font-medium text-gray-800">{{ obs.observer?.nama_lengkap || 'Guru' }}</div>
                </td>
                <td class="py-3.5 px-4">
                  <p class="line-clamp-2 text-xs text-gray-900">{{ obs.perilaku_diamati }}</p>
                </td>
                <td class="py-3.5 px-4">
                  <span v-if="!obs.dilihat_bk_at" class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-rose-100 text-rose-800">
                    Belum Dilihat BK
                  </span>
                  <span v-else-if="!obs.rekomendasi_bk" class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 text-amber-800">
                    Menunggu Rekomendasi
                  </span>
                  <span v-else-if="obs.is_escalated_to_bk" class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                    Masuk Pantauan BK
                  </span>
                  <span v-else class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
                    Cukup di Kelas
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <button
                      @click="openStudentHistory(obs.student)"
                      class="px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-lg font-bold text-[11px] hover:bg-indigo-100 cursor-pointer"
                    >
                      Buka Siswa
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- ========================================================================= -->
    <!-- VIEW 2: HALAMAN BARU REKAM JEJAK LENGKAP SISWA (STUDENT HISTORY VIEW)     -->
    <!-- ========================================================================= -->
    <template v-else-if="currentView === 'student-history' && selectedStudent">
      <!-- Top Action Bar & Profil Siswa Header -->
      <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex items-center gap-4">
            <button
              @click="backToStudentList"
              type="button"
              class="w-12 h-12 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition cursor-pointer shrink-0"
              title="Kembali ke Daftar Peserta Didik"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
            </button>
            <div class="flex items-center gap-3.5">
              <div class="w-14 h-14 rounded-2xl bg-indigo-50 border border-indigo-200 overflow-hidden flex items-center justify-center shrink-0">
                <img
                  v-if="selectedStudent.foto_path"
                  :src="selectedStudent.foto_path.startsWith('http') ? selectedStudent.foto_path : `/storage/${selectedStudent.foto_path}`"
                  alt="Foto"
                  class="w-full h-full object-cover"
                />
                <span v-else class="text-base font-black text-indigo-700 uppercase">
                  {{ getInitials(selectedStudent.nama_lengkap) }}
                </span>
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                    Rekam Jejak Observasi Siswa
                  </span>
                  <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                    {{ getClassName(selectedStudent) }}
                  </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-gray-900 leading-tight mt-1">
                  {{ selectedStudent.nama_lengkap }}
                </h1>
                <p class="text-xs text-gray-500 mt-0.5">
                  NIS: <strong>{{ selectedStudent.nis || '-' }}</strong> | Total Kejadian: <strong>{{ studentHistoryObservations.length }} Catatan</strong>
                </p>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-2.5 self-start md:self-auto">
            <button
              @click="backToStudentList"
              type="button"
              class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
            >
              Kembali ke Daftar
            </button>
            <button
              @click="openCreateForm(selectedStudent.id)"
              type="button"
              class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-600/20 transition active:scale-95 cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
              </svg>
              <span>+ Catat Kejadian Siswa Ini</span>
            </button>
          </div>
        </div>

        <!-- Notification Banner: Status Dilihat BK -->
        <div class="p-3.5 rounded-2xl border text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-gradient-to-r from-slate-50 to-indigo-50/20 border-slate-200">
          <div class="flex items-center gap-2">
            <span class="p-1 rounded-lg bg-indigo-100 text-indigo-700 font-bold">ℹ️</span>
            <span class="text-gray-700">
              <strong v-if="auth.isGuruBk">Mode Guru BK Aktif:</strong>
              <strong v-else>Status di Layanan BK:</strong>
              Catatan siswa ini dikompilasi secara longitudinal agar pola perilaku anak terpantau secara utuh.
            </span>
          </div>
          <div class="text-[11px] font-semibold text-gray-500">
            Terakhir diperbarui: {{ studentHistoryObservations[0] ? formatDateIndo(studentHistoryObservations[0].tanggal) : 'Hari ini' }}
          </div>
        </div>
      </div>

      <!-- Timeline Records of Student Observations -->
      <div v-if="loadingStudentHistory" class="p-12 text-center text-slate-400 bg-white rounded-3xl border border-gray-100">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-600 border-t-transparent mb-2"></div>
        <p class="text-xs font-medium">Memuat seluruh rekam kejadian siswa...</p>
      </div>

      <div v-else-if="studentHistoryObservations.length === 0" class="p-12 text-center bg-white rounded-3xl border border-gray-100 shadow-xs">
        <h3 class="text-sm font-bold text-gray-800">Belum Ada Catatan Kejadian</h3>
        <p class="text-xs text-gray-400 mt-1">Gunakan tombol "+ Catat Kejadian Siswa Ini" untuk menambahkan observasi.</p>
      </div>

      <div v-else class="space-y-5">
        <div
          v-for="(obs, idx) in studentHistoryObservations"
          :key="obs.id"
          class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4 hover:border-indigo-200 transition"
        >
          <!-- Card Header: No Urut Kejadian, Tanggal, Pelapor & Status -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100">
            <div class="flex items-center gap-3">
              <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-700 font-black text-xs flex items-center justify-center">
                #{{ studentHistoryObservations.length - idx }}
              </span>
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-sm font-black text-gray-900">
                    Kejadian {{ formatDateIndo(obs.tanggal) }}
                  </h3>
                  <!-- Badge Status Dilihat BK -->
                  <span
                    v-if="!obs.dilihat_bk_at"
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300 animate-pulse"
                  >
                    🔔 Belum Dilihat BK
                  </span>
                  <span
                    v-else
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200"
                  >
                    ✓ Dilihat BK
                  </span>
                </div>
                <div class="flex items-center gap-2 text-[11px] text-gray-400 mt-0.5">
                  <span v-if="obs.waktu">⏰ {{ obs.waktu }}</span>
                  <span v-if="obs.tempat">📍 {{ obs.tempat }}</span>
                  <span>👤 Pelapor: <strong class="text-gray-700">{{ obs.observer?.nama_lengkap || 'Guru' }}</strong></span>
                </div>
              </div>
            </div>

            <!-- Keputusan BK Badge -->
            <div class="flex items-center gap-2 self-start sm:self-auto">
              <span
                v-if="obs.is_escalated_to_bk"
                class="px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200"
              >
                🎯 Masuk Kasus BK
              </span>
              <span
                v-else-if="obs.rekomendasi_bk"
                class="px-3 py-1 rounded-full text-xs font-bold bg-teal-50 text-teal-700 border border-teal-200"
              >
                ✓ Cukup di Kelas
              </span>
              <span
                v-else
                class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200"
              >
                ⏳ Menunggu Rekomendasi BK
              </span>
            </div>
          </div>

          <!-- Uraian 3 Pilar ABC Faktual -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="p-3.5 bg-slate-50/70 rounded-2xl border border-slate-100 space-y-1">
              <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                1. Situasi Sebelum (Antecedent)
              </div>
              <p class="text-xs text-gray-700 whitespace-pre-line leading-relaxed">
                {{ obs.situasi_sebelum || '— (Tidak dicatat)' }}
              </p>
            </div>

            <div class="p-3.5 bg-indigo-50/40 rounded-2xl border border-indigo-100 space-y-1">
              <div class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider">
                2. Perilaku yang Diamati (Behavior) *
              </div>
              <p class="text-xs text-gray-900 font-semibold whitespace-pre-line leading-relaxed">
                {{ obs.perilaku_diamati }}
              </p>
            </div>

            <div class="p-3.5 bg-slate-50/70 rounded-2xl border border-slate-100 space-y-1">
              <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                3. Respons Lingkungan (Consequence)
              </div>
              <p class="text-xs text-gray-700 whitespace-pre-line leading-relaxed">
                {{ obs.respons_lingkungan || '— (Tidak dicatat)' }}
              </p>
            </div>
          </div>

          <!-- Bagian Tanggapan & Rekomendasi Guru BK -->
          <div v-if="obs.rekomendasi_bk || obs.saran_untuk_guru" class="p-4 bg-gradient-to-r from-rose-50/50 via-amber-50/30 to-indigo-50/30 rounded-2xl border border-rose-200/60 space-y-2">
            <div class="flex items-center justify-between pb-1 border-b border-rose-100/60">
              <div class="flex items-center gap-2">
                <span class="p-1 rounded-lg bg-rose-100 text-rose-700 font-bold text-xs">🧠</span>
                <h4 class="text-xs font-bold text-rose-950">
                  Tanggapan &amp; Telaah Guru Bimbingan Konseling (BK)
                </h4>
              </div>
              <span v-if="obs.bkTeacher" class="text-[10px] text-gray-500">
                Oleh: <strong>{{ obs.bkTeacher.nama_lengkap }}</strong>
              </span>
            </div>

            <div v-if="obs.rekomendasi_bk" class="space-y-0.5">
              <span class="text-[10px] font-bold text-rose-800 uppercase tracking-wider">Telaah / Analisis Psikologis BK:</span>
              <p class="text-xs text-gray-800 italic bg-white/80 p-2.5 rounded-xl border border-rose-100">
                "{{ obs.rekomendasi_bk }}"
              </p>
            </div>

            <div v-if="obs.saran_untuk_guru" class="space-y-0.5">
              <span class="text-[10px] font-bold text-amber-800 uppercase tracking-wider">Saran Praktis untuk Guru di Kelas:</span>
              <p class="text-xs text-gray-800 bg-white/80 p-2.5 rounded-xl border border-amber-100">
                {{ obs.saran_untuk_guru }}
              </p>
            </div>
          </div>

          <div v-else class="p-3 bg-amber-50 rounded-2xl border border-amber-200 text-xs text-amber-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span>⏳</span>
              <span>Laporan kejadian ini belum mendapatkan tanggapan/rekomendasi tertulis dari Guru BK.</span>
            </div>
            <button
              v-if="auth.isGuruBk"
              @click="openFeedbackForm(obs)"
              type="button"
              class="px-3 py-1 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-[11px] cursor-pointer"
            >
              + Beri Rekomendasi BK Sekarang
            </button>
          </div>

          <!-- Action Buttons per Incident Record -->
          <div class="flex items-center justify-between pt-2 border-t border-gray-100">
            <div class="flex items-center gap-2">
              <button
                v-if="auth.isGuruBk"
                @click="openFeedbackForm(obs)"
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold rounded-xl border border-rose-200 transition cursor-pointer"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                <span>{{ obs.rekomendasi_bk ? 'Edit Rekomendasi BK' : '+ Beri Rekomendasi BK' }}</span>
              </button>
            </div>

            <div class="flex items-center gap-1.5">
              <button
                @click="openEditForm(obs)"
                type="button"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition cursor-pointer border border-slate-200"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                <span>Edit</span>
              </button>
              <button
                @click="deleteObservation(obs)"
                type="button"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition cursor-pointer border border-slate-200"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>Hapus</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- ======================================================== -->
    <!-- VIEW 3: FORM CATAT / EDIT OBSERVASI (FULL PAGE)          -->
    <!-- ======================================================== -->
    <template v-else-if="currentView === 'form'">
      <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <button
            @click="handleReturnAfterSave"
            type="button"
            class="w-12 h-12 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition cursor-pointer shrink-0"
            title="Kembali"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                {{ isEditing ? 'Edit Catatan Observasi' : 'Pencatatan Kejadian Baru' }}
              </span>
              <span class="text-xs text-gray-400">/ Format ABC Faktual</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-gray-900 leading-tight mt-1">
              {{ isEditing ? 'Edit Laporan Catatan Observasi Siswa' : 'Catat Laporan Observasi Perilaku Siswa ke Guru BK' }}
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              Dokumentasikan apa adanya tanpa asumsi subjektif agar Guru Bimbingan Konseling (BK) dapat memberikan telaah objektif.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2.5 self-start md:self-auto">
          <button
            @click="handleReturnAfterSave"
            type="button"
            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
          >
            Batal &amp; Kembali
          </button>
          <button
            @click="submitForm"
            :disabled="submitting"
            type="button"
            class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-600/20 transition disabled:opacity-50 cursor-pointer"
          >
            <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>{{ submitting ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Kirim Laporan ke Guru BK') }}</span>
          </button>
        </div>
      </div>

      <!-- Main Form Container -->
      <form @submit.prevent="submitForm" class="space-y-6">
        <!-- Section 1: Siswa & Waktu -->
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-5">
          <div class="flex items-center gap-2.5 pb-3 border-b border-gray-100">
            <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
              1
            </span>
            <div>
              <h3 class="text-sm font-bold text-gray-900">Identitas Siswa &amp; Tempat Kejadian</h3>
              <p class="text-[11px] text-gray-400">Pilih subjek pengamatan serta waktu dan area terjadinya perilaku.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="lg:col-span-2">
              <label class="block text-xs font-bold text-gray-700 mb-1.5">
                Nama Siswa yang Diobservasi <span class="text-rose-500">*</span>
              </label>
              <select
                v-model="form.student_id"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                required
              >
                <option value="" disabled>-- Pilih Siswa --</option>
                <option v-for="s in students" :key="s.id" :value="s.id">
                  {{ s.nama_lengkap }} (NIS: {{ s.nis }}) - {{ getClassName(s) }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1.5">
                Tanggal Observasi <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.tanggal"
                type="date"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                required
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1.5">
                Waktu / Jam Kejadian
              </label>
              <input
                v-model="form.waktu"
                type="text"
                placeholder="Mis: Jam Lentera, 09.30"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
              />
            </div>

            <div class="lg:col-span-4">
              <label class="block text-xs font-bold text-gray-700 mb-1.5">
                Tempat / Lokasi Spesifik
              </label>
              <input
                v-model="form.tempat"
                type="text"
                placeholder="Mis: Ruang Kelas 1, Pojok Baca, Kolong Meja, Lapangan Olahraga"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
              />
            </div>
          </div>
        </div>

        <!-- Section 2: Tiga Pilar Pendekatan ABC -->
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-6">
          <div class="flex items-center gap-2.5 pb-3 border-b border-gray-100">
            <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
              2
            </span>
            <div>
              <h3 class="text-sm font-bold text-gray-900">Uraian Faktual Observasi (Antecedent - Behavior - Consequence)</h3>
              <p class="text-[11px] text-gray-400">Deskripsikan kejadian apa adanya tanpa praduga agar mudah ditindaklanjuti Guru BK.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- 1. Antecedent -->
            <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-2.5 flex flex-col justify-between">
              <div>
                <div class="flex items-center gap-2 text-xs font-bold text-slate-700 uppercase tracking-wide">
                  <span class="w-6 h-6 rounded-lg bg-slate-200 text-slate-700 flex items-center justify-center text-[10px]">A</span>
                  <span>Situasi Sebelum (Antecedent)</span>
                </div>
                <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                  Aktivitas apa yang sedang dilakukan anak atau lingkungan sebelum perilaku muncul?
                </p>
              </div>
              <textarea
                v-model="form.situasi_sebelum"
                rows="5"
                placeholder="Contoh: Sedang mengerjakan modul di meja kelompok, tiba-tiba pensilnya terjatuh..."
                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
              ></textarea>
            </div>

            <!-- 2. Behavior -->
            <div class="p-5 rounded-2xl bg-indigo-50/50 border border-indigo-200/80 space-y-2.5 flex flex-col justify-between">
              <div>
                <div class="flex items-center gap-2 text-xs font-bold text-indigo-900 uppercase tracking-wide">
                  <span class="w-6 h-6 rounded-lg bg-indigo-200 text-indigo-900 flex items-center justify-center text-[10px]">B</span>
                  <span>Perilaku yang Diamati (Behavior) *</span>
                </div>
                <p class="text-[11px] text-indigo-600 mt-1 leading-relaxed">
                  Tindakan atau ucapan spesifik yang dilakukan anak secara objektif.
                </p>
              </div>
              <textarea
                v-model="form.perilaku_diamati"
                rows="5"
                placeholder="Contoh: Berteriak keras, bersembunyi di bawah meja, atau menolak mengikuti instruksi..."
                class="w-full px-3.5 py-2.5 bg-white border border-indigo-300 rounded-xl text-xs text-gray-900 font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                required
              ></textarea>
            </div>

            <!-- 3. Consequence -->
            <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-2.5 flex flex-col justify-between">
              <div>
                <div class="flex items-center gap-2 text-xs font-bold text-slate-700 uppercase tracking-wide">
                  <span class="w-6 h-6 rounded-lg bg-slate-200 text-slate-700 flex items-center justify-center text-[10px]">C</span>
                  <span>Respons Lingkungan (Consequence)</span>
                </div>
                <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                  Reaksi teman sekelas serta respon langsung guru yang mendampingi di kelas.
                </p>
              </div>
              <textarea
                v-model="form.respons_lingkungan"
                rows="5"
                placeholder="Contoh: Teman sekelas menjauh, guru mendekati dan mengajak istirahat di ruang tenang..."
                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
              ></textarea>
            </div>
          </div>
        </div>

        <!-- Section 3: Catatan Tambahan Guru Kelas -->
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
          <div class="flex items-center gap-2.5 pb-3 border-b border-gray-100">
            <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
              3
            </span>
            <div>
              <h3 class="text-sm font-bold text-gray-900">Catatan Khusus Pelapor (Opsional)</h3>
              <p class="text-[11px] text-gray-400">Tambahkan catatan khusus atau pola yang dirasa penting bagi Guru BK.</p>
            </div>
          </div>

          <textarea
            v-model="form.catatan_wali_kelas"
            rows="3"
            placeholder="Tuliskan catatan tambahan jika ada..."
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
          ></textarea>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-2">
          <button
            @click="handleReturnAfterSave"
            type="button"
            class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
          >
            Batal
          </button>
          <button
            :disabled="submitting"
            type="submit"
            class="inline-flex items-center gap-2 px-7 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-600/20 transition disabled:opacity-50 cursor-pointer"
          >
            <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>{{ submitting ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Kirim Laporan ke Guru BK') }}</span>
          </button>
        </div>
      </form>
    </template>

    <!-- ========================================================================= -->
    <!-- VIEW 4: FORM TANGGAPAN & STATUS GURU BK (KHUSUS GURU BK - FULL PAGE)      -->
    <!-- ========================================================================= -->
    <template v-else-if="currentView === 'feedback'">
      <!-- Header -->
      <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <button
            @click="handleReturnAfterSave"
            type="button"
            class="w-12 h-12 rounded-2xl bg-rose-50 hover:bg-rose-100 text-rose-700 flex items-center justify-center transition cursor-pointer shrink-0"
            title="Kembali"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                Menu Khusus Guru Bimbingan Konseling (BK)
              </span>
              <span class="text-xs text-gray-400">/ Tanggapan &amp; Keputusan Pemantauan</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-rose-950 leading-tight mt-1">
              Lembar Tanggapan &amp; Status Kasus BK
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              Memberikan asesmen profesional untuk siswa: <strong class="text-rose-700 font-bold">{{ feedbackObs?.student?.nama_lengkap }}</strong>
              (Pelapor: {{ feedbackObs?.observer?.nama_lengkap }})
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2.5 self-start md:self-auto">
          <button
            @click="handleReturnAfterSave"
            type="button"
            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
          >
            Batal &amp; Kembali
          </button>
          <button
            @click="submitFeedback"
            :disabled="submittingFeedback"
            type="button"
            class="inline-flex items-center gap-2 px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-md shadow-rose-600/20 transition disabled:opacity-50 cursor-pointer"
          >
            <svg v-if="submittingFeedback" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>{{ submittingFeedback ? 'Menyimpan...' : 'Simpan Tanggapan BK' }}</span>
          </button>
        </div>
      </div>

      <!-- Ringkasan Kejadian Faktual Pelapor -->
      <div class="bg-gradient-to-br from-slate-50 to-indigo-50/30 p-6 rounded-3xl border border-indigo-100/70 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-indigo-100">
          <div class="flex items-center gap-2">
            <span class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs">
              📋
            </span>
            <div>
              <h3 class="text-xs font-bold text-indigo-950 uppercase tracking-wider">
                Laporan Faktual Guru Pelapor
              </h3>
              <p class="text-[11px] text-gray-500">
                Oleh: <strong>{{ feedbackObs?.observer?.nama_lengkap }}</strong> pada {{ formatDateIndo(feedbackObs?.tanggal) }} ({{ feedbackObs?.tempat || 'Kelas' }})
              </p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
          <div class="bg-white p-4 rounded-2xl border border-slate-200/80">
            <span class="font-bold text-slate-500 uppercase tracking-wider text-[10px] block mb-1">
              1. Antecedent (Situasi Sebelum)
            </span>
            <p class="text-gray-700 whitespace-pre-line leading-relaxed">
              {{ feedbackObs?.situasi_sebelum || '— (Tidak dicatat)' }}
            </p>
          </div>

          <div class="bg-indigo-50/50 p-4 rounded-2xl border border-indigo-200">
            <span class="font-bold text-indigo-700 uppercase tracking-wider text-[10px] block mb-1">
              2. Behavior (Perilaku Faktual)
            </span>
            <p class="text-gray-900 font-semibold whitespace-pre-line leading-relaxed">
              {{ feedbackObs?.perilaku_diamati }}
            </p>
          </div>

          <div class="bg-white p-4 rounded-2xl border border-slate-200/80">
            <span class="font-bold text-slate-500 uppercase tracking-wider text-[10px] block mb-1">
              3. Consequence (Respons Lingkungan)
            </span>
            <p class="text-gray-700 whitespace-pre-line leading-relaxed">
              {{ feedbackObs?.respons_lingkungan || '— (Tidak dicatat)' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Main Feedback Form -->
      <form @submit.prevent="submitFeedback" class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-6">
        <!-- Keputusan Pemantauan BK (Radio Toggle) -->
        <div class="p-5 rounded-2xl bg-rose-50/40 border border-rose-200/80 space-y-3">
          <div>
            <label class="block text-xs font-black text-rose-950 uppercase tracking-wide">
              Keputusan: Apakah Kasus Ini Masuk ke Analisis &amp; Monitoring BK? *
            </label>
            <p class="text-[11px] text-rose-700 mt-0.5">
              Tentukan apakah data ini perlu disimpan dan dipantau berkala dalam sistem Analisis &amp; Monitoring BK, atau cukup diselesaikan di tingkat kelas.
            </p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
            <!-- Option 1: Masuk BK -->
            <label 
              class="flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition"
              :class="feedbackForm.masuk_pemantauan_bk ? 'bg-white border-rose-500 ring-2 ring-rose-500/20 shadow-xs' : 'bg-white/60 border-slate-200 hover:bg-white'"
            >
              <input
                v-model="feedbackForm.masuk_pemantauan_bk"
                :value="true"
                type="radio"
                name="masuk_pemantauan_bk"
                class="mt-1 text-rose-600 focus:ring-rose-500 cursor-pointer"
              />
              <div>
                <span class="block text-xs font-bold text-gray-900">
                  🎯 Ya, Masukkan ke Pemantauan &amp; Analisis BK
                </span>
                <span class="block text-[11px] text-gray-500 mt-0.5">
                  Data siswa otomatis tersimpan di menu Analisis &amp; Monitoring BK untuk pendampingan konseling bertahap.
                </span>
              </div>
            </label>

            <!-- Option 2: Cukup di Kelas -->
            <label 
              class="flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition"
              :class="!feedbackForm.masuk_pemantauan_bk ? 'bg-white border-teal-600 ring-2 ring-teal-500/20 shadow-xs' : 'bg-white/60 border-slate-200 hover:bg-white'"
            >
              <input
                v-model="feedbackForm.masuk_pemantauan_bk"
                :value="false"
                type="radio"
                name="masuk_pemantauan_bk"
                class="mt-1 text-teal-600 focus:ring-teal-500 cursor-pointer"
              />
              <div>
                <span class="block text-xs font-bold text-gray-900">
                  ✓ Tidak, Cukup Penanganan di Kelas
                </span>
                <span class="block text-[11px] text-gray-500 mt-0.5">
                  Data TIDAK disimpan di Analisis &amp; Monitoring BK. Guru kelas cukup menerapkan saran tindak lanjut BK.
                </span>
              </div>
            </label>
          </div>

          <!-- Jika Masuk BK: Pilih Fokus Pemantauan -->
          <div v-if="feedbackForm.masuk_pemantauan_bk" class="pt-2">
            <label class="block text-xs font-bold text-rose-900 mb-1">
              Fokus Pemantauan BK *
            </label>
            <select
              v-model="feedbackForm.fokus_pemantauan"
              class="w-full sm:w-72 px-3 py-2 bg-white border border-rose-300 rounded-xl text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-rose-500/20"
            >
              <option v-for="f in focusList" :key="f" :value="f">{{ f }}</option>
            </select>
          </div>
        </div>

        <!-- Uraian Rekomendasi BK -->
        <div>
          <label class="block text-xs font-bold text-gray-800 mb-1.5">
            Rekomendasi / Analisis Psikologis Guru BK <span class="text-rose-500">*</span>
          </label>
          <p class="text-[11px] text-gray-400 mb-2">
            Telaah objektif mengenai motif perilaku, dinamika regulasi emosi, atau pemicu respon anak.
          </p>
          <textarea
            v-model="feedbackForm.rekomendasi_bk"
            rows="4"
            placeholder="Tuliskan telaah profesional dan analisis Guru BK..."
            class="w-full px-3.5 py-2.5 bg-rose-50/20 border border-rose-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
            required
          ></textarea>
        </div>

        <!-- Saran Guru Kelas -->
        <div>
          <label class="block text-xs font-bold text-gray-800 mb-1.5">
            Saran Praktis untuk Guru Kelas / Guru Pelapor
          </label>
          <p class="text-[11px] text-gray-400 mb-2">
            Langkah konkret yang disarankan untuk diterapkan di kelas saat menghadapi situasi serupa.
          </p>
          <textarea
            v-model="feedbackForm.saran_untuk_guru"
            rows="4"
            placeholder="Tuliskan arahan praktis bagi guru pendamping..."
            class="w-full px-3.5 py-2.5 bg-amber-50/20 border border-amber-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
          ></textarea>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
          <button
            @click="handleReturnAfterSave"
            type="button"
            class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
          >
            Batal
          </button>
          <button
            :disabled="submittingFeedback"
            type="submit"
            class="inline-flex items-center gap-2 px-7 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-md shadow-rose-600/20 transition disabled:opacity-50 cursor-pointer"
          >
            <svg v-if="submittingFeedback" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>{{ submittingFeedback ? 'Menyimpan...' : 'Simpan Tanggapan BK' }}</span>
          </button>
        </div>
      </form>
    </template>

  </div>
</template>
