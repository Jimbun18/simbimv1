<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
import { showSuccess, showError, showConfirm } from '../services/swal'
import { formatDateIndo } from '../utils/date'

const auth = useAuthStore()

// Tab Navigation
const activeTab = ref<'dashboard' | 'cases' | 'detail' | 'guide'>('dashboard')

// State
const loadingDashboard = ref(false)
const dashboardData = ref<any>(null)

const loadingCases = ref(false)
const casesList = ref<any[]>([])
const filterCaseStatus = ref<string>('')
const filterCaseFocus = ref<string>('')
const searchCaseQuery = ref<string>('')
const caseCurrentPage = ref(1)
const caseLastPage = ref(1)
const caseTotalItems = ref(0)

// Active Selected Case (for Tab Siswa)
const selectedCase = ref<any>(null)
const loadingCaseDetail = ref(false)

// Reference data
const students = ref<any[]>([])
const classes = ref<any[]>([])

// Modal Tambah Kasus Baru
const isCaseModalOpen = ref(false)
const submittingCase = ref(false)
const caseForm = ref({
  student_id: '',
  tanggal_masuk: new Date().toISOString().split('T')[0],
  sumber_awal: 'Wali Kelas',
  fokus_pemantauan: 'Emosi/Regulasi Diri',
  status_saat_ini: 'Dalam Pemantauan',
  review_berikutnya: '',
  catatan_umum: '',
})

// Modal Tambah Log / Sesi BK
const isLogModalOpen = ref(false)
const isEditingLog = ref(false)
const editingLogId = ref<number | null>(null)
const submittingLog = ref(false)
const logForm = ref({
  tanggal: new Date().toISOString().split('T')[0],
  tahap: 'Pemantauan',
  sumber_data: 'Guru BK',
  observasi_temuan: '',
  telaah_bk: '',
  kebutuhan_tujuan: '',
  tindak_lanjut: '',
  hasil_evaluasi: '',
  perubahan_pola: 'Belum Cukup Data',
  status: 'Dalam Pemantauan',
  catatan_konteks: '',
})

// Focus Categories (10 Fokus Resmi)
const focusList = [
  'Pribadi',
  'Sosial',
  'Belajar',
  'Perkembangan',
  'Emosi/Regulasi Diri',
  'Perilaku',
  'Kedisiplinan',
  'Kehadiran',
  'Potensi/Bakat',
  'Lainnya',
]

// Tahapan Alur BK (9 Tahap Resmi)
const tahapList = [
  'Informasi Masuk',
  'Identifikasi',
  'Observasi BK',
  'Telaah BK',
  'Kebutuhan & Tujuan',
  'Keputusan & Tindak Lanjut',
  'Hasil & Evaluasi',
  'Keputusan Berikutnya',
  'Ringkasan Kondisi',
  'Pemantauan',
]

// Status List
const statusList = [
  'Dalam Pemantauan',
  'Menunjukkan Perkembangan',
  'Sesuai Harapan',
  'Memerlukan Perhatian',
]

// Perubahan Pola List
const polaList = [
  'Belum Cukup Data',
  'Masih Berulang',
  'Mulai Berkurang',
  'Mulai Berubah',
  'Tidak Lagi Terlihat',
]

// Fetch Dashboard
const fetchDashboard = async () => {
  loadingDashboard.value = true
  try {
    const res = await api.get('/bk/dashboard')
    if (res.data.success) {
      dashboardData.value = res.data.data
    }
  } catch (err: any) {
    console.error('Gagal memuat dashboard BK', err)
  } finally {
    loadingDashboard.value = false
  }
}

// Fetch Cases
const fetchCases = async (page = 1) => {
  loadingCases.value = true
  try {
    let url = `/bk/cases?page=${page}`
    if (filterCaseStatus.value) url += `&status=${encodeURIComponent(filterCaseStatus.value)}`
    if (filterCaseFocus.value) url += `&fokus=${encodeURIComponent(filterCaseFocus.value)}`
    if (searchCaseQuery.value) url += `&search=${encodeURIComponent(searchCaseQuery.value)}`

    const res = await api.get(url)
    if (res.data.success) {
      casesList.value = res.data.data || []
      caseCurrentPage.value = res.data.meta?.current_page || 1
      caseLastPage.value = res.data.meta?.last_page || 1
      caseTotalItems.value = res.data.meta?.total || 0
    }
  } catch (err: any) {
    console.error('Gagal mengambil daftar kasus BK', err)
  } finally {
    loadingCases.value = false
  }
}

// Fetch Case Detail
const selectCaseForDetail = async (c: any) => {
  loadingCaseDetail.value = true
  activeTab.value = 'detail'
  try {
    const res = await api.get(`/bk/cases/${c.id}`)
    if (res.data.success) {
      selectedCase.value = res.data.data
    }
  } catch (err: any) {
    showError('Gagal Mengambil Detail', err)
  } finally {
    loadingCaseDetail.value = false
  }
}

const reloadCaseDetail = async () => {
  if (!selectedCase.value?.id) return
  try {
    const res = await api.get(`/bk/cases/${selectedCase.value.id}`)
    if (res.data.success) {
      selectedCase.value = res.data.data
    }
  } catch (err) {
    console.error(err)
  }
}

// Open Modals
const openNewCaseModal = () => {
  caseForm.value = {
    student_id: '',
    tanggal_masuk: new Date().toISOString().split('T')[0],
    sumber_awal: 'Wali Kelas',
    fokus_pemantauan: 'Emosi/Regulasi Diri',
    status_saat_ini: 'Dalam Pemantauan',
    review_berikutnya: new Date(Date.now() + 7 * 24 * 3600 * 1000).toISOString().split('T')[0],
    catatan_umum: '',
  }
  isCaseModalOpen.value = true
}

const submitNewCase = async () => {
  if (!caseForm.value.student_id) {
    showError('Validasi Gagal', null, 'Silakan pilih siswa yang akan dipantau.')
    return
  }

  submittingCase.value = true
  try {
    const res = await api.post('/bk/cases', caseForm.value)
    if (res.data.success) {
      showSuccess('Berhasil!', 'Siswa berhasil dimasukkan ke daftar pemantauan BK.')
      isCaseModalOpen.value = false
      fetchCases(1)
      fetchDashboard()
      selectCaseForDetail(res.data.data)
    }
  } catch (err: any) {
    showError('Gagal Menyimpan Kasus', err)
  } finally {
    submittingCase.value = false
  }
}

const openNewLogModal = () => {
  isEditingLog.value = false
  editingLogId.value = null
  logForm.value = {
    tanggal: new Date().toISOString().split('T')[0],
    tahap: 'Pemantauan',
    sumber_data: 'Guru BK',
    observasi_temuan: '',
    telaah_bk: '',
    kebutuhan_tujuan: '',
    tindak_lanjut: '',
    hasil_evaluasi: '',
    perubahan_pola: 'Belum Cukup Data',
    status: selectedCase.value?.status_saat_ini || 'Dalam Pemantauan',
    catatan_konteks: '',
  }
  isLogModalOpen.value = true
}

const openEditLogModal = (log: any) => {
  isEditingLog.value = true
  editingLogId.value = log.id
  logForm.value = {
    tanggal: log.tanggal ? log.tanggal.substring(0, 10) : new Date().toISOString().split('T')[0],
    tahap: log.tahap || 'Pemantauan',
    sumber_data: log.sumber_data || 'Guru BK',
    observasi_temuan: log.observasi_temuan || '',
    telaah_bk: log.telaah_bk || '',
    kebutuhan_tujuan: log.kebutuhan_tujuan || '',
    tindak_lanjut: log.tindak_lanjut || '',
    hasil_evaluasi: log.hasil_evaluasi || '',
    perubahan_pola: log.perubahan_pola || 'Belum Cukup Data',
    status: log.status || 'Dalam Pemantauan',
    catatan_konteks: log.catatan_konteks || '',
  }
  isLogModalOpen.value = true
}

const submitLog = async () => {
  if (!logForm.value.observasi_temuan) {
    showError('Validasi Gagal', null, 'Catatan observasi/temuan wajib diisi.')
    return
  }

  submittingLog.value = true
  try {
    if (isEditingLog.value && editingLogId.value) {
      const res = await api.put(`/bk/logs/${editingLogId.value}`, logForm.value)
      if (res.data.success) {
        showSuccess('Berhasil!', 'Log pemantauan berhasil diperbarui.')
        isLogModalOpen.value = false
        reloadCaseDetail()
        fetchDashboard()
      }
    } else {
      const res = await api.post(`/bk/cases/${selectedCase.value.id}/logs`, logForm.value)
      if (res.data.success) {
        showSuccess('Berhasil!', 'Log intervensi BK berhasil dicatat.')
        isLogModalOpen.value = false
        reloadCaseDetail()
        fetchDashboard()
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan Log', err)
  } finally {
    submittingLog.value = false
  }
}

const deleteLog = async (logId: number) => {
  const confirmed = await showConfirm(
    'Hapus Log Jurnal BK?',
    'Catatan sesi intervensi ini akan dihapus secara permanen.',
    'Ya, Hapus',
    'warning'
  )
  if (!confirmed) return

  try {
    const res = await api.delete(`/bk/logs/${logId}`)
    if (res.data.success) {
      showSuccess('Berhasil Dihapus!', res.data.message)
      reloadCaseDetail()
      fetchDashboard()
    }
  } catch (err: any) {
    showError('Gagal Menghapus Log', err)
  }
}

const updateCaseStatus = async (newStatus: string) => {
  if (!selectedCase.value?.id) return
  try {
    const res = await api.put(`/bk/cases/${selectedCase.value.id}`, {
      status_saat_ini: newStatus,
      review_terakhir: new Date().toISOString().split('T')[0],
    })
    if (res.data.success) {
      selectedCase.value.status_saat_ini = newStatus
      showSuccess('Status Diperbarui', `Status pemantauan diubah menjadi: ${newStatus}`)
      fetchDashboard()
      fetchCases(caseCurrentPage.value)
    }
  } catch (err: any) {
    showError('Gagal Update Status', err)
  }
}

const exportExcel = async () => {
  try {
    const res = await api.get('/bk/export', { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Rekap_Analisis_dan_Monitoring_BK_${new Date().toISOString().split('T')[0]}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    showSuccess('Berhasil!', 'File Excel rekap pemantauan BK berhasil diunduh.')
  } catch (err: any) {
    showError('Gagal Ekspor Excel', err)
  }
}

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'Dalam Pemantauan':
      return 'bg-amber-50 text-amber-700 border-amber-200'
    case 'Menunjukkan Perkembangan':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200'
    case 'Sesuai Harapan':
      return 'bg-teal-50 text-teal-700 border-teal-200'
    case 'Memerlukan Perhatian':
      return 'bg-rose-50 text-rose-700 border-rose-200'
    default:
      return 'bg-slate-100 text-slate-700 border-slate-200'
  }
}

const getPolaBadge = (pola: string) => {
  switch (pola) {
    case 'Belum Cukup Data':
      return 'bg-slate-100 text-slate-700 border-slate-200'
    case 'Masih Berulang':
      return 'bg-rose-50 text-rose-700 border-rose-200'
    case 'Mulai Berkurang':
      return 'bg-amber-50 text-amber-700 border-amber-200'
    case 'Mulai Berubah':
      return 'bg-sky-50 text-sky-700 border-sky-200'
    case 'Tidak Lagi Terlihat':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200'
    default:
      return 'bg-slate-100 text-slate-700 border-slate-200'
  }
}

const getInitials = (name?: string) => {
  if (!name) return '?'
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase()
  return name.substring(0, 2).toUpperCase()
}

onMounted(async () => {
  if (!auth.isGuruBk) return

  await Promise.all([
    fetchDashboard(),
    fetchCases(1),
    api.get('/students?per_page=150').then((res) => {
      if (res.data.success) students.value = res.data.data || []
    }),
    api.get('/classes').then((res) => {
      if (res.data.success) classes.value = res.data.data || []
    }),
  ])
})
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-200">
    <!-- Cek Otoritas Guru BK -->
    <div v-if="!auth.isGuruBk" class="bg-rose-50 border border-rose-200 p-8 rounded-3xl text-center space-y-3">
      <div class="w-16 h-16 mx-auto bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
      </div>
      <h2 class="text-lg font-black text-rose-900">Akses Terbatas: Ruang Bimbingan & Konseling (BK)</h2>
      <p class="text-xs text-rose-700 max-w-md mx-auto leading-relaxed">
        Sesuai asas kerahasiaan bimbingan konseling dan instruksi administrasi sekolah, menu Analisis & Monitoring BK hanya diperuntukkan bagi <strong>Guru Bimbingan Konseling (BK)</strong>.
      </p>
    </div>

    <!-- MAIN VIEW GURU BK -->
    <template v-else>
      <!-- Top Banner Header -->
      <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 flex items-center justify-center text-white shadow-md shadow-emerald-600/20 shrink-0">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                Menu Eksklusif Guru BK
              </span>
              <span class="text-xs text-gray-400 font-mono">T.A. 2026/2027</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-gray-900 leading-tight mt-1">
              Analisis & Monitoring Bimbingan Konseling (BK)
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              Sistem asesmen psikologi perkembangan, pemantauan kasus longitudinal, dan rekam jejak intervensi peserta didik.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2.5 self-start md:self-auto">
          <button
            @click="exportExcel"
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
            title="Download Rekap Workbook Excel"
          >
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Ekspor Excel</span>
          </button>
          <button
            @click="openNewCaseModal"
            type="button"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/20 transition active:scale-95 cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>+ Siswa Baru BK</span>
          </button>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex items-center gap-2 border-b border-gray-200 pb-2">
        <button
          @click="activeTab = 'dashboard'"
          type="button"
          class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-2"
          :class="activeTab === 'dashboard' ? 'bg-emerald-600 text-white shadow-xs' : 'text-gray-600 hover:bg-slate-100'"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          <span>Dashboard Analisis BK</span>
        </button>

        <button
          @click="activeTab = 'cases'"
          type="button"
          class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-2"
          :class="activeTab === 'cases' ? 'bg-emerald-600 text-white shadow-xs' : 'text-gray-600 hover:bg-slate-100'"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
          </svg>
          <span>Daftar Pemantauan ({{ caseTotalItems }})</span>
        </button>

        <button
          @click="activeTab = 'detail'"
          type="button"
          class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-2"
          :class="activeTab === 'detail' ? 'bg-emerald-600 text-white shadow-xs' : 'text-gray-600 hover:bg-slate-100'"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          <span>Tab Siswa ({{ selectedCase?.student?.nama_lengkap || 'Pilih Siswa' }})</span>
        </button>

        <button
          @click="activeTab = 'guide'"
          type="button"
          class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-2 ml-auto"
          :class="activeTab === 'guide' ? 'bg-slate-800 text-white' : 'text-gray-600 hover:bg-slate-100'"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
          </svg>
          <span>Buku Petunjuk & Prinsip BK</span>
        </button>
      </div>

      <!-- TAB 1: DASHBOARD ANALISIS BK -->
      <div v-if="activeTab === 'dashboard'" class="space-y-6">
        <!-- 1. Ringkasan Kondisi Terkini (4 Status Utama BK) -->
        <div>
          <h2 class="text-sm font-black text-gray-800 uppercase tracking-wider mb-3">
            1. Ringkasan Kondisi Terkini
          </h2>
          <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5">
            <!-- Total -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-xs space-y-2">
              <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Total Dalam Catatan BK</span>
              <div class="text-2xl sm:text-3xl font-black text-gray-900">
                {{ dashboardData?.ringkasan?.total_kasus ?? 0 }}
                <span class="text-xs font-normal text-gray-400">Siswa</span>
              </div>
              <div class="text-[10px] text-gray-400">Siswa binaan aktif</div>
            </div>

            <!-- Dalam Pemantauan -->
            <div class="bg-amber-50/70 p-4 sm:p-5 rounded-2xl border border-amber-200/80 shadow-xs space-y-2">
              <span class="text-[11px] font-bold text-amber-800 uppercase tracking-wider block">Dalam Pemantauan</span>
              <div class="text-2xl sm:text-3xl font-black text-amber-900">
                {{ dashboardData?.ringkasan?.dalam_pemantauan ?? 0 }}
                <span class="text-xs font-normal text-amber-700">Siswa</span>
              </div>
              <div class="text-[10px] text-amber-700">Proses intervensi awal</div>
            </div>

            <!-- Menunjukkan Perkembangan -->
            <div class="bg-emerald-50/70 p-4 sm:p-5 rounded-2xl border border-emerald-200/80 shadow-xs space-y-2">
              <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider block">Menunjukkan Perkembangan</span>
              <div class="text-2xl sm:text-3xl font-black text-emerald-900">
                {{ dashboardData?.ringkasan?.menunjukkan_perkembangan ?? 0 }}
                <span class="text-xs font-normal text-emerald-700">Siswa</span>
              </div>
              <div class="text-[10px] text-emerald-700">Perubahan positif terlihat</div>
            </div>

            <!-- Sesuai Harapan -->
            <div class="bg-teal-50/70 p-4 sm:p-5 rounded-2xl border border-teal-200/80 shadow-xs space-y-2">
              <span class="text-[11px] font-bold text-teal-800 uppercase tracking-wider block">Sesuai Harapan</span>
              <div class="text-2xl sm:text-3xl font-black text-teal-900">
                {{ dashboardData?.ringkasan?.sesuai_harapan ?? 0 }}
                <span class="text-xs font-normal text-teal-700">Siswa</span>
              </div>
              <div class="text-[10px] text-teal-700">Mencapai tujuan pemantauan</div>
            </div>

            <!-- Memerlukan Perhatian -->
            <div class="bg-rose-50/70 p-4 sm:p-5 rounded-2xl border border-rose-200/80 shadow-xs space-y-2 col-span-2 lg:col-span-1">
              <span class="text-[11px] font-bold text-rose-800 uppercase tracking-wider block">Memerlukan Perhatian</span>
              <div class="text-2xl sm:text-3xl font-black text-rose-900">
                {{ dashboardData?.ringkasan?.memerlukan_perhatian ?? 0 }}
                <span class="text-xs font-normal text-rose-700">Siswa</span>
              </div>
              <div class="text-[10px] text-rose-700">Perlu tindak lanjut khusus</div>
            </div>
          </div>
        </div>

        <!-- 2. Agenda Review & Distribusi Kelas Grid (2 Cols) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <!-- Agenda Review (7 Cols) -->
          <div class="lg:col-span-7 bg-white p-5 rounded-3xl border border-gray-100 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">
                  2. Agenda Review Pemantauan
                </h3>
                <p class="text-xs text-gray-400">Jadwal evaluasi berkala siswa binaan BK</p>
              </div>
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 text-xs font-bold">
                  {{ dashboardData?.agenda_review?.perlu_review ?? 0 }} Perlu Review
                </span>
                <span class="px-2.5 py-1 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 text-xs font-bold">
                  {{ dashboardData?.agenda_review?.review_minggu_ini ?? 0 }} Minggu Ini
                </span>
              </div>
            </div>

            <!-- Tabel Agenda Siswa -->
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="border-b border-gray-100 text-gray-400 text-[11px]">
                    <th class="py-2 font-bold">Nama Siswa</th>
                    <th class="py-2 font-bold">Kelas</th>
                    <th class="py-2 font-bold">Fokus</th>
                    <th class="py-2 font-bold">Status</th>
                    <th class="py-2 font-bold text-right">Jadwal Review</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  <tr
                    v-for="item in dashboardData?.agenda_review?.daftar || []"
                    :key="item.id"
                    class="hover:bg-slate-50 transition cursor-pointer"
                    @click="selectCaseForDetail(item)"
                  >
                    <td class="py-2.5 font-bold text-gray-800">
                      {{ item.student?.nama_lengkap }}
                    </td>
                    <td class="py-2.5 text-gray-600">
                      {{ item.school_class?.nama || 'Kelas' }}
                    </td>
                    <td class="py-2.5 text-gray-600">
                      {{ item.fokus_pemantauan }}
                    </td>
                    <td class="py-2.5">
                      <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border" :class="getStatusBadge(item.status_saat_ini)">
                        {{ item.status_saat_ini }}
                      </span>
                    </td>
                    <td class="py-2.5 text-right font-mono font-bold text-indigo-600">
                      {{ item.review_berikutnya ? formatDateIndo(item.review_berikutnya) : '—' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Distribusi Kelas (5 Cols) -->
          <div class="lg:col-span-5 bg-white p-5 rounded-3xl border border-gray-100 shadow-xs space-y-4">
            <div>
              <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">
                3. Distribusi Berdasarkan Kelas
              </h3>
              <p class="text-xs text-gray-400">Sebaran siswa dalam catatan BK per tingkat</p>
            </div>

            <div class="space-y-3 pt-2">
              <div
                v-for="item in dashboardData?.distribusi_kelas || []"
                :key="item.class_id"
                class="space-y-1"
              >
                <div class="flex items-center justify-between text-xs">
                  <span class="font-bold text-gray-700">{{ item.nama }}</span>
                  <span class="font-bold text-gray-900">{{ item.jumlah }} Siswa</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                  <div
                    class="bg-emerald-600 h-2 rounded-full transition-all duration-300"
                    :style="{ width: `${dashboardData?.ringkasan?.total_kasus ? (item.jumlah / dashboardData.ringkasan.total_kasus) * 100 : 0}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. Distribusi 10 Fokus Pemantauan & Sumber Informasi (Grid 2 Cols) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <!-- 10 Fokus Pemantauan (8 Cols) -->
          <div class="lg:col-span-8 bg-white p-5 rounded-3xl border border-gray-100 shadow-xs space-y-3">
            <div>
              <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">
                4. Distribusi Fokus Pemantauan (10 Kategori)
              </h3>
              <p class="text-xs text-gray-400">Kategori penyesuaian diri dan target perkembangan siswa</p>
            </div>

            <div class="divide-y divide-gray-100">
              <div
                v-for="fokus in dashboardData?.distribusi_fokus || []"
                :key="fokus.fokus"
                class="py-2.5 flex items-center justify-between gap-4"
              >
                <div>
                  <h4 class="text-xs font-bold text-gray-800">{{ fokus.fokus }}</h4>
                  <p class="text-[11px] text-gray-400">{{ fokus.keterangan }}</p>
                </div>
                <div class="flex items-center gap-2">
                  <span class="px-2.5 py-1 bg-slate-100 text-slate-800 text-xs font-mono font-bold rounded-xl min-w-9 text-center">
                    {{ fokus.jumlah }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Sumber Informasi Awal (4 Cols) -->
          <div class="lg:col-span-4 bg-white p-5 rounded-3xl border border-gray-100 shadow-xs space-y-3">
            <div>
              <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">
                5. Sumber Informasi Awal
              </h3>
              <p class="text-xs text-gray-400">Asal rujukan pelaporan pertama siswa</p>
            </div>

            <div class="space-y-3 pt-2">
              <div
                v-for="sumber in dashboardData?.distribusi_sumber || []"
                :key="sumber.sumber"
                class="p-3 bg-slate-50 rounded-2xl flex items-center justify-between"
              >
                <span class="text-xs font-bold text-gray-700">{{ sumber.sumber }}</span>
                <span class="px-2 py-0.5 bg-white border border-slate-200 text-xs font-bold rounded-lg text-emerald-700">
                  {{ sumber.jumlah }} Kasus
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: DAFTAR PEMANTAUAN (DAFTAR KASUS) -->
      <div v-if="activeTab === 'cases'" class="space-y-4">
        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-2.5 flex-1 min-w-[280px]">
            <div class="relative flex-1 min-w-[200px]">
              <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                v-model="searchCaseQuery"
                @keyup.enter="fetchCases(1)"
                type="text"
                placeholder="Cari nama siswa dalam pemantauan..."
                class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:outline-none"
              />
            </div>

            <select
              v-model="filterCaseStatus"
              @change="fetchCases(1)"
              class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-700 focus:bg-white focus:outline-none"
            >
              <option value="">Semua Status</option>
              <option v-for="st in statusList" :key="st" :value="st">{{ st }}</option>
            </select>

            <select
              v-model="filterCaseFocus"
              @change="fetchCases(1)"
              class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-700 focus:bg-white focus:outline-none"
            >
              <option value="">Semua Fokus Pemantauan</option>
              <option v-for="f in focusList" :key="f" :value="f">{{ f }}</option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="fetchCases(1)"
              type="button"
              class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition cursor-pointer"
            >
              Filter
            </button>
          </div>
        </div>

        <!-- Tabel Sheet: DAFTAR PEMANTAUAN -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 border-b border-gray-100 text-gray-500 uppercase tracking-wider text-[11px]">
                <tr>
                  <th class="py-3 px-4 font-bold">No.</th>
                  <th class="py-3 px-4 font-bold">Nama Siswa</th>
                  <th class="py-3 px-4 font-bold">Kelas</th>
                  <th class="py-3 px-4 font-bold">Tanggal Masuk</th>
                  <th class="py-3 px-4 font-bold">Sumber Awal</th>
                  <th class="py-3 px-4 font-bold">Fokus Pemantauan</th>
                  <th class="py-3 px-4 font-bold">Status Saat Ini</th>
                  <th class="py-3 px-4 font-bold">Review Berikutnya</th>
                  <th class="py-3 px-4 font-bold text-center">Aksi / Tab Siswa</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr
                  v-for="(c, idx) in casesList"
                  :key="c.id"
                  class="hover:bg-emerald-50/40 transition"
                >
                  <td class="py-3.5 px-4 font-mono font-bold text-gray-400">
                    {{ idx + 1 + (caseCurrentPage - 1) * 20 }}
                  </td>
                  <td class="py-3.5 px-4">
                    <div class="flex items-center gap-2.5">
                      <div class="w-8 h-8 rounded-lg bg-emerald-100/70 text-emerald-800 font-bold flex items-center justify-center shrink-0">
                        {{ getInitials(c.student?.nama_lengkap) }}
                      </div>
                      <div>
                        <strong class="text-gray-900 block leading-tight">{{ c.student?.nama_lengkap }}</strong>
                        <span class="text-[10px] text-gray-400 font-mono">NIS: {{ c.student?.nis || '-' }}</span>
                      </div>
                    </div>
                  </td>
                  <td class="py-3.5 px-4 font-bold text-gray-700">
                    {{ c.school_class?.nama || 'Kelas' }}
                  </td>
                  <td class="py-3.5 px-4 text-gray-600">
                    {{ formatDateIndo(c.tanggal_masuk) }}
                  </td>
                  <td class="py-3.5 px-4 text-gray-600">
                    {{ c.sumber_awal }}
                  </td>
                  <td class="py-3.5 px-4">
                    <span class="px-2.5 py-1 bg-slate-100 rounded-lg text-slate-800 font-semibold text-[11px]">
                      {{ c.fokus_pemantauan }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border" :class="getStatusBadge(c.status_saat_ini)">
                      {{ c.status_saat_ini }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4 font-mono text-gray-600">
                    {{ c.review_berikutnya ? formatDateIndo(c.review_berikutnya) : '—' }}
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <button
                      @click="selectCaseForDetail(c)"
                      type="button"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold rounded-xl border border-emerald-200 transition cursor-pointer"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      <span>Buka Tab Siswa ({{ c.logs_count || 0 }})</span>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 3: TAB SISWA (DETAIL KASUS LONGITUDINAL) -->
      <div v-if="activeTab === 'detail'" class="space-y-6">
        <div v-if="!selectedCase" class="p-12 text-center bg-white rounded-3xl border border-gray-100 shadow-xs">
          <p class="text-xs text-gray-400">Silakan pilih siswa dari Daftar Pemantauan terlebih dahulu.</p>
          <button
            @click="activeTab = 'cases'"
            type="button"
            class="mt-3 px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-bold cursor-pointer"
          >
            Lihat Daftar Pemantauan
          </button>
        </div>

        <template v-else>
          <!-- Header Informasi Siswa (Format Sheet Tab Siswa) -->
          <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-100">
              <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
                  <img
                    v-if="selectedCase.student?.foto_path"
                    :src="selectedCase.student.foto_path.startsWith('http') ? selectedCase.student.foto_path : `/storage/${selectedCase.student.foto_path}`"
                    alt="Foto Siswa"
                    class="w-full h-full object-cover"
                  />
                  <span v-else class="text-lg font-bold text-gray-400">
                    {{ getInitials(selectedCase.student?.nama_lengkap) }}
                  </span>
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <h2 class="text-lg sm:text-xl font-black text-gray-900">
                      {{ selectedCase.student?.nama_lengkap }}
                    </h2>
                    <span class="px-3 py-0.5 rounded-full text-xs font-bold border" :class="getStatusBadge(selectedCase.status_saat_ini)">
                      {{ selectedCase.status_saat_ini }}
                    </span>
                  </div>
                  <div class="text-xs text-gray-500 mt-1 flex flex-wrap gap-x-4 gap-y-1">
                    <span>Kelas: <strong>{{ selectedCase.school_class?.nama || 'Kelas' }}</strong></span>
                    <span>Wali Kelas: <strong>{{ selectedCase.homeroom_teacher?.nama_lengkap || 'Rasiti, S.Pd.' }}</strong></span>
                    <span>Guru BK: <strong>{{ selectedCase.bk_teacher?.nama_lengkap || 'Nur Kafi Ramadhani, S.Psi.' }}</strong></span>
                  </div>
                </div>
              </div>

              <!-- Quick Status Update Bar -->
              <div class="flex flex-wrap items-center gap-2 self-start md:self-auto">
                <button
                  @click="openNewLogModal"
                  type="button"
                  class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/20 transition active:scale-95 cursor-pointer"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                  <span>+ Catat Sesi / Tahapan BK</span>
                </button>
              </div>
            </div>

            <!-- Detail Grid Informasi Pokok -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
              <div class="p-3 bg-slate-50 rounded-2xl">
                <span class="text-gray-400 text-[10px] block">Tanggal Masuk:</span>
                <strong class="text-gray-800">{{ formatDateIndo(selectedCase.tanggal_masuk) }}</strong>
              </div>
              <div class="p-3 bg-slate-50 rounded-2xl">
                <span class="text-gray-400 text-[10px] block">Sumber Awal:</span>
                <strong class="text-gray-800">{{ selectedCase.sumber_awal }}</strong>
              </div>
              <div class="p-3 bg-slate-50 rounded-2xl">
                <span class="text-gray-400 text-[10px] block">Fokus Pemantauan:</span>
                <strong class="text-indigo-700">{{ selectedCase.fokus_pemantauan }}</strong>
              </div>
              <div class="p-3 bg-slate-50 rounded-2xl">
                <span class="text-gray-400 text-[10px] block">Ubah Status Terkini:</span>
                <select
                  :value="selectedCase.status_saat_ini"
                  @change="updateCaseStatus(($event.target as HTMLSelectElement).value)"
                  class="w-full mt-0.5 bg-white border border-slate-200 rounded-lg text-xs font-bold px-2 py-1 text-gray-800 focus:outline-none"
                >
                  <option v-for="st in statusList" :key="st" :value="st">{{ st }}</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Tabel Riwayat Pemantauan BK (Timeline Log Longitudinal) -->
          <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
              <div>
                <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">
                  Riwayat Pemantauan & Tahapan Intervensi BK
                </h3>
                <p class="text-xs text-gray-400">Total {{ selectedCase.logs?.length || 0 }} sesi dan catatan asesmen tercatat</p>
              </div>
            </div>

            <!-- List Card Log -->
            <div class="space-y-4">
              <div
                v-for="(log, idx) in selectedCase.logs || []"
                :key="log.id"
                class="p-4 sm:p-5 rounded-2xl border border-gray-200/80 bg-slate-50/50 hover:bg-white hover:border-emerald-300 transition space-y-3"
              >
                <!-- Log Header -->
                <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-gray-200/60">
                  <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-emerald-600 text-white text-[11px] font-bold flex items-center justify-center font-mono">
                      {{ (selectedCase.logs?.length || 0) - idx }}
                    </span>
                    <strong class="text-xs text-gray-900">{{ formatDateIndo(log.tanggal) }}</strong>
                    <span class="px-2.5 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-[10px] font-bold border border-indigo-100">
                      Tahap: {{ log.tahap }}
                    </span>
                    <span class="text-[11px] text-gray-400">Sumber: {{ log.sumber_data || 'Guru BK' }}</span>
                  </div>

                  <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border" :class="getPolaBadge(log.perubahan_pola)">
                      Pola: {{ log.perubahan_pola }}
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border" :class="getStatusBadge(log.status)">
                      {{ log.status }}
                    </span>
                    <button
                      @click="openEditLogModal(log)"
                      class="p-1 text-slate-400 hover:text-indigo-600 cursor-pointer"
                      title="Edit"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                      </svg>
                    </button>
                    <button
                      @click="deleteLog(log.id)"
                      class="p-1 text-slate-400 hover:text-rose-600 cursor-pointer"
                      title="Hapus"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </div>

                <!-- Log Content Grid -->
                <div class="space-y-2 text-xs">
                  <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Observasi / Temuan Fakta:</span>
                    <p class="text-gray-900 leading-relaxed font-medium mt-0.5">
                      {{ log.observasi_temuan }}
                    </p>
                  </div>

                  <div v-if="log.telaah_bk" class="p-3 bg-rose-50/40 rounded-xl border border-rose-100">
                    <span class="text-[10px] font-bold text-rose-800 uppercase tracking-wider block">Telaah Profesional BK:</span>
                    <p class="text-rose-950 mt-0.5 italic">
                      {{ log.telaah_bk }}
                    </p>
                  </div>

                  <div v-if="log.kebutuhan_tujuan || log.tindak_lanjut" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <div v-if="log.kebutuhan_tujuan" class="p-2.5 bg-amber-50/50 rounded-xl border border-amber-100">
                      <span class="text-[10px] font-bold text-amber-800 uppercase tracking-wider block">Kebutuhan / Tujuan:</span>
                      <p class="text-amber-950 mt-0.5">{{ log.kebutuhan_tujuan }}</p>
                    </div>
                    <div v-if="log.tindak_lanjut" class="p-2.5 bg-indigo-50/50 rounded-xl border border-indigo-100">
                      <span class="text-[10px] font-bold text-indigo-800 uppercase tracking-wider block">Tindak Lanjut / Intervensi:</span>
                      <p class="text-indigo-950 mt-0.5">{{ log.tindak_lanjut }}</p>
                    </div>
                  </div>

                  <div v-if="log.hasil_evaluasi" class="p-2.5 bg-emerald-50/50 rounded-xl border border-emerald-100">
                    <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider block">Hasil / Evaluasi Perilaku:</span>
                    <p class="text-emerald-950 mt-0.5">{{ log.hasil_evaluasi }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- TAB 4: BUKU PETUNJUK & PRINSIP BK -->
      <div v-if="activeTab === 'guide'" class="space-y-6">
        <!-- 8 Prinsip Pencatatan BK -->
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-emerald-100 text-emerald-700 rounded-xl">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <div>
              <h2 class="text-base font-black text-gray-900">8 Prinsip Pencatatan Bimbingan & Konseling (BK)</h2>
              <p class="text-xs text-gray-500">Pedoman etika profesional penanganan peserta didik.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
              <span class="font-bold text-emerald-700 block">1. Faktual</span>
              <p class="text-gray-600 text-[11px]">Berdasarkan kejadian yang benar-benar diamati, bukan opini atau label subjektif.</p>
            </div>
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
              <span class="font-bold text-emerald-700 block">2. Objektif</span>
              <p class="text-gray-600 text-[11px]">Tidak dipengaruhi prasangka atau memberi cap seperti "nakal", "malas", atau "pemalu".</p>
            </div>
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
              <span class="font-bold text-emerald-700 block">3. Berbasis Data</span>
              <p class="text-gray-600 text-[11px]">Kesimpulan dibuat berdasarkan beberapa informasi relevan, bukan satu kejadian sesaat.</p>
            </div>
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
              <span class="font-bold text-emerald-700 block">4. Bertahap</span>
              <p class="text-gray-600 text-[11px]">Fakta, pola yang berulang, dan telaah profesional dicatat secara terpisah.</p>
            </div>
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
              <span class="font-bold text-emerald-700 block">5. Berhati-hati</span>
              <p class="text-gray-600 text-[11px]">Memberikan ruang data yang cukup sebelum merumuskan kesimpulan psikologis.</p>
            </div>
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
              <span class="font-bold text-emerald-700 block">6. Catat Kekuatan</span>
              <p class="text-gray-600 text-[11px]">Tidak hanya mencatat kesulitan, tetapi juga potensi, minat, dan perubahan positif anak.</p>
            </div>
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
              <span class="font-bold text-emerald-700 block">7. Relevan</span>
              <p class="text-gray-600 text-[11px]">Hanya informasi yang berkaitan dengan kebutuhan pemantauan dan pendampingan.</p>
            </div>
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
              <span class="font-bold text-rose-700 block">8. Rahasia</span>
              <p class="text-gray-600 text-[11px]">Informasi profesional BK hanya digunakan dan dibagikan sesuai kebutuhan dan kewenangan.</p>
            </div>
          </div>
        </div>

        <!-- 9 Tahap Alur Pemantauan BK -->
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
          <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">
            Alur Pemantauan Kasus (9 Tahap)
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <div class="p-3 bg-slate-50 rounded-xl">
              <strong>1. Informasi Masuk:</strong> Laporan diterima dari wali kelas/guru/ortu.
            </div>
            <div class="p-3 bg-slate-50 rounded-xl">
              <strong>2. Identifikasi:</strong> BK mengidentifikasi kebutuhan informasi awal.
            </div>
            <div class="p-3 bg-slate-50 rounded-xl">
              <strong>3. Observasi BK:</strong> Pengamatan langsung bila diperlukan.
            </div>
            <div class="p-3 bg-slate-50 rounded-xl">
              <strong>4. Telaah BK:</strong> Menelaah pola perilaku dan data tersedia.
            </div>
            <div class="p-3 bg-slate-50 rounded-xl">
              <strong>5. Kebutuhan & Tujuan:</strong> Menentukan target pendampingan.
            </div>
            <div class="p-3 bg-slate-50 rounded-xl">
              <strong>6. Tindak Lanjut:</strong> Langkah konkret, mentoring & coaching.
            </div>
            <div class="p-3 bg-slate-50 rounded-xl">
              <strong>7. Hasil & Evaluasi:</strong> Mencatat hasil dan perubahan yang teramati.
            </div>
            <div class="p-3 bg-slate-50 rounded-xl">
              <strong>8. Keputusan Berikutnya:</strong> Menentukan kelanjutan pemantauan.
            </div>
            <div class="p-3 bg-slate-50 rounded-xl">
              <strong>9. Ringkasan Kondisi:</strong> Memperbarui kondisi terkini siswa.
            </div>
          </div>
        </div>
      </div>

      <!-- MODAL TAMBAH SISWA KE BK -->
      <div
        v-if="isCaseModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
      >
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 animate-in zoom-in-95 duration-150">
          <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <div>
              <h3 class="text-base font-black text-gray-900">Tambah Siswa ke Pemantauan BK</h3>
              <p class="text-xs text-gray-500">Membuat lembar kasus pemantauan baru untuk siswa.</p>
            </div>
            <button @click="isCaseModalOpen = false" class="text-gray-400 hover:text-gray-600 cursor-pointer p-1">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="submitNewCase" class="space-y-3.5 text-xs">
            <div>
              <label class="block font-bold text-gray-700 mb-1">Pilih Siswa *</label>
              <select
                v-model="caseForm.student_id"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-gray-800 focus:outline-none"
                required
              >
                <option value="" disabled>-- Pilih Siswa --</option>
                <option v-for="s in students" :key="s.id" :value="s.id">
                  {{ s.nama_lengkap }} (NIS: {{ s.nis }})
                </option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-bold text-gray-700 mb-1">Tanggal Masuk *</label>
                <input
                  v-model="caseForm.tanggal_masuk"
                  type="date"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-gray-800 focus:outline-none"
                  required
                />
              </div>
              <div>
                <label class="block font-bold text-gray-700 mb-1">Sumber Awal *</label>
                <select
                  v-model="caseForm.sumber_awal"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-gray-800 focus:outline-none"
                >
                  <option value="Wali Kelas">Wali Kelas</option>
                  <option value="Guru Tema">Guru Tema</option>
                  <option value="Guru BK">Guru BK</option>
                  <option value="Orang Tua">Orang Tua</option>
                  <option value="Siswa">Siswa</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-bold text-gray-700 mb-1">Fokus Pemantauan *</label>
                <select
                  v-model="caseForm.fokus_pemantauan"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-gray-800 focus:outline-none"
                >
                  <option v-for="f in focusList" :key="f" :value="f">{{ f }}</option>
                </select>
              </div>
              <div>
                <label class="block font-bold text-gray-700 mb-1">Status Awal *</label>
                <select
                  v-model="caseForm.status_saat_ini"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-gray-800 focus:outline-none"
                >
                  <option v-for="st in statusList" :key="st" :value="st">{{ st }}</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block font-bold text-gray-700 mb-1">Agenda Review Berikutnya</label>
              <input
                v-model="caseForm.review_berikutnya"
                type="date"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-gray-800 focus:outline-none"
              />
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100">
              <button
                @click="isCaseModalOpen = false"
                type="button"
                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer"
              >
                Batal
              </button>
              <button
                :disabled="submittingCase"
                type="submit"
                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md cursor-pointer disabled:opacity-50"
              >
                {{ submittingCase ? 'Menyimpan...' : 'Simpan Kasus BK' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- MODAL CATAT LOG / SESI INTERVENSI BK -->
      <div
        v-if="isLogModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
      >
        <div class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl space-y-4 animate-in zoom-in-95 duration-150 my-8">
          <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <div>
              <h3 class="text-base font-black text-gray-900">
                {{ isEditingLog ? 'Edit Sesi Pemantauan BK' : 'Catat Sesi / Intervensi BK Baru' }}
              </h3>
              <p class="text-xs text-gray-500">Untuk siswa: {{ selectedCase?.student?.nama_lengkap }}</p>
            </div>
            <button @click="isLogModalOpen = false" class="text-gray-400 hover:text-gray-600 cursor-pointer p-1">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="submitLog" class="space-y-3.5 text-xs">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label class="block font-bold text-gray-700 mb-1">Tanggal *</label>
                <input
                  v-model="logForm.tanggal"
                  type="date"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-gray-800 focus:outline-none"
                  required
                />
              </div>
              <div>
                <label class="block font-bold text-gray-700 mb-1">Tahap Alur BK *</label>
                <select
                  v-model="logForm.tahap"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-gray-800 focus:outline-none"
                >
                  <option v-for="t in tahapList" :key="t" :value="t">{{ t }}</option>
                </select>
              </div>
              <div>
                <label class="block font-bold text-gray-700 mb-1">Sumber Data</label>
                <input
                  v-model="logForm.sumber_data"
                  type="text"
                  placeholder="Mis: Guru BK, Wali kelas"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-gray-800 focus:outline-none"
                />
              </div>
            </div>

            <div>
              <label class="block font-bold text-gray-700 mb-1">Observasi / Temuan Fakta *</label>
              <textarea
                v-model="logForm.observasi_temuan"
                rows="2"
                placeholder="Catat fakta kejadian yang terlihat/terdengar secara objektif..."
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-gray-800 focus:outline-none"
                required
              ></textarea>
            </div>

            <div>
              <label class="block font-bold text-gray-700 mb-1">Telaah Profesional BK (Asesmen Psikologis)</label>
              <textarea
                v-model="logForm.telaah_bk"
                rows="2"
                placeholder="Analisis profesional BK terhadap data dan pola perilaku siswa..."
                class="w-full px-3 py-2 bg-rose-50/30 border border-rose-200 rounded-xl text-gray-800 focus:outline-none"
              ></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block font-bold text-gray-700 mb-1">Kebutuhan / Tujuan</label>
                <input
                  v-model="logForm.kebutuhan_tujuan"
                  type="text"
                  placeholder="Target perkembangan anak..."
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-gray-800 focus:outline-none"
                />
              </div>
              <div>
                <label class="block font-bold text-gray-700 mb-1">Tindak Lanjut / Coaching</label>
                <input
                  v-model="logForm.tindak_lanjut"
                  type="text"
                  placeholder="Intervensi yang dilakukan..."
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-gray-800 focus:outline-none"
                />
              </div>
            </div>

            <div>
              <label class="block font-bold text-gray-700 mb-1">Hasil & Evaluasi</label>
              <input
                v-model="logForm.hasil_evaluasi"
                type="text"
                placeholder="Perubahan perilaku setelah intervensi..."
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-gray-800 focus:outline-none"
              />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-bold text-gray-700 mb-1">Perubahan Pola *</label>
                <select
                  v-model="logForm.perubahan_pola"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-gray-800 focus:outline-none"
                >
                  <option v-for="p in polaList" :key="p" :value="p">{{ p }}</option>
                </select>
              </div>
              <div>
                <label class="block font-bold text-gray-700 mb-1">Status Pemantauan *</label>
                <select
                  v-model="logForm.status"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-gray-800 focus:outline-none"
                >
                  <option v-for="st in statusList" :key="st" :value="st">{{ st }}</option>
                </select>
              </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100">
              <button
                @click="isLogModalOpen = false"
                type="button"
                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer"
              >
                Batal
              </button>
              <button
                :disabled="submittingLog"
                type="submit"
                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md cursor-pointer disabled:opacity-50"
              >
                {{ submittingLog ? 'Menyimpan...' : (isEditingLog ? 'Simpan Perubahan' : 'Simpan Log BK') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </template>
  </div>
</template>
