<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'
import { showSuccess, showError, showWarning, showConfirm } from '../services/swal'

interface MasterStudent {
  id: number
  nis: string
  nama_lengkap: string
  class_id?: number | null
  status: string
}

interface PpdbRecord {
  id: number
  nomor_pendaftaran: string
  tahun_ajaran: string
  gelombang: string
  jenjang: string
  nama_lengkap: string
  nama_panggilan: string
  jenis_kelamin: string
  tempat_lahir: string
  tanggal_lahir: string
  usia_daftar: string
  jumlah_saudara: number
  anak_ke: number
  alamat_lengkap: string
  tinggal_dengan: string
  asal_sekolah: string | null
  alamat_asal_sekolah: string | null
  pindah_ke_kelas: string | null
  alasan_pindah: string | null
  nama_ayah: string
  ttl_ayah: string | null
  pendidikan_ayah: string | null
  pekerjaan_ayah: string | null
  penghasilan_ayah: string | null
  alamat_ayah: string | null
  no_hp_ayah: string
  nama_ibu: string
  ttl_ibu: string | null
  pendidikan_ibu: string | null
  pekerjaan_ibu: string | null
  penghasilan_ibu: string | null
  alamat_ibu: string | null
  no_hp_ibu: string
  pas_foto_path: string | null
  pas_foto_url: string | null
  akta_kelahiran_path: string | null
  akta_kelahiran_url: string | null
  kartu_keluarga_path: string | null
  kartu_keluarga_url: string | null
  status: 'menunggu' | 'diterima' | 'cadangan' | 'ditolak'
  status_label: string
  catatan_admin: string | null
  verified_by: any
  verified_at: string | null
  student_id?: number | null
  student?: MasterStudent | null
  created_at: string
}

// Navigation View State: 'list' | 'detail'
const activeView = ref<'list' | 'detail'>('list')

// Records & Loading
const records = ref<PpdbRecord[]>([])
const loading = ref(false)
const selectedRecord = ref<PpdbRecord | null>(null)
const updatingStatus = ref(false)

const statusForm = ref({
  status: 'menunggu',
  catatan_admin: '',
})

// Summary Cards
const summary = ref({
  total: 0,
  kb: 0,
  tka: 0,
  tkb: 0,
  sd: 0,
  menunggu: 0,
  diterima: 0,
  cadangan: 0,
  ditolak: 0,
})

// Filter state
const filters = ref({
  search: '',
  jenjang: 'all',
  status: 'all',
  page: 1,
  per_page: 20,
})

const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0,
})

// Public form link
const publicFormUrl = computed(() => {
  if (typeof window !== 'undefined') {
    return `${window.location.origin}/ppdb`
  }
  return '/ppdb'
})

const copyPublicLink = () => {
  navigator.clipboard.writeText(publicFormUrl.value)
  showSuccess('Tersalin!', 'Tautan formulir PPDB online publik berhasil disalin. Anda dapat membagikannya ke WhatsApp atau media sosial.')
}

const copyText = (text: string, label: string = 'Teks') => {
  navigator.clipboard.writeText(text)
  showSuccess('Tersalin!', `${label} berhasil disalin.`)
}

const openPublicForm = () => {
  window.open(publicFormUrl.value, '_blank')
}

// Fetch Records
const fetchRecords = async () => {
  loading.value = true
  try {
    const params: any = {
      page: filters.value.page,
      per_page: filters.value.per_page,
    }
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.jenjang !== 'all') params.jenjang = filters.value.jenjang
    if (filters.value.status !== 'all') params.status = filters.value.status

    const res = await api.get('/ppdb', { params })
    if (res.data?.success) {
      records.value = res.data.data?.data || []
      pagination.value = {
        currentPage: res.data.data?.current_page || 1,
        lastPage: res.data.data?.last_page || 1,
        total: res.data.data?.total || 0,
      }
      if (res.data.summary) {
        summary.value = res.data.summary
      }
    }
  } catch (err: any) {
    showError('Gagal', err.response?.data?.message || 'Gagal memuat data pendaftar PPDB.')
  } finally {
    loading.value = false
  }
}

// Buka Tampilan Detail In-Page (Tanpa Pop-up Modal)
const openDetail = (record: PpdbRecord) => {
  selectedRecord.value = record
  statusForm.value = {
    status: record.status,
    catatan_admin: record.catatan_admin || '',
  }
  activeView.value = 'detail'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Kembali ke Tampilan Daftar
const backToList = () => {
  activeView.value = 'list'
  selectedRecord.value = null
}

// Simpan Perubahan Status & Catatan Panitia
const saveStatus = async () => {
  if (!selectedRecord.value) return
  updatingStatus.value = true
  try {
    const res = await api.put(`/ppdb/${selectedRecord.value.id}/status`, {
      status: statusForm.value.status,
      catatan_admin: statusForm.value.catatan_admin,
    })

    if (res.data?.success) {
      showSuccess('Berhasil!', res.data.message || 'Status pendaftaran berhasil diperbarui.')
      // Sinkronkan data di tampilan detail aktif
      if (res.data.data) {
        selectedRecord.value = res.data.data
      } else {
        selectedRecord.value.status = statusForm.value.status as any
        selectedRecord.value.catatan_admin = statusForm.value.catatan_admin
      }
      
      await fetchRecords()
    }
  } catch (err: any) {
    showError('Gagal', err.response?.data?.message || 'Gagal memperbarui status.')
  } finally {
    updatingStatus.value = false
  }
}

// Hapus Data Pendaftar
const deleteRecord = async (record: PpdbRecord) => {
  const confirmed = await showConfirm(
    'Hapus Data Pendaftar?',
    `Data pendaftaran ananda ${record.nama_lengkap} (${record.nomor_pendaftaran}) beserta berkas yang diunggah akan dihapus secara permanen.`
  )
  if (!confirmed) return

  try {
    const res = await api.delete(`/ppdb/${record.id}`)
    if (res.data?.success) {
      showSuccess('Berhasil', 'Data pendaftar PPDB berhasil dihapus.')
      if (activeView.value === 'detail' && selectedRecord.value?.id === record.id) {
        backToList()
      }
      await fetchRecords()
    }
  } catch (err: any) {
    showError('Gagal', err.response?.data?.message || 'Gagal menghapus data.')
  }
}

// Export Data ke Excel
const exportExcel = async () => {
  try {
    const params = new URLSearchParams()
    if (filters.value.jenjang !== 'all') params.append('jenjang', filters.value.jenjang)
    if (filters.value.status !== 'all') params.append('status', filters.value.status)
    if (filters.value.search) params.append('search', filters.value.search)

    const url = `/api/v1/ppdb/export?${params.toString()}`

    const res = await api.get(url, { responseType: 'blob' })
    const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
    const link = document.createElement('a')
    link.href = window.URL.createObjectURL(blob)
    link.download = `Data_PPDB_BIM_${new Date().toISOString().slice(0, 10)}.xlsx`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    showSuccess('Berhasil!', 'File Excel data PPDB berhasil diunduh.')
  } catch (err) {
    showError('Gagal', 'Terjadi kesalahan saat mengekspor data Excel.')
  }
}

// Format Tanggal
const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }).format(new Date(dateStr))
}

const formatDateTime = (dateStr: string) => {
  if (!dateStr) return '-'
  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(dateStr))
}

// Hubungi Orang Tua via WhatsApp
const chatWhatsApp = (phone: string, namaAnak: string, noDaftar: string, jenisOrtu: string = 'Orang Tua') => {
  if (!phone) {
    showWarning('Nomor Kosong', `Nomor kontak ${jenisOrtu} belum diisi.`)
    return
  }
  let cleanPhone = phone.replace(/[^0-9]/g, '')
  if (cleanPhone.startsWith('0')) {
    cleanPhone = '62' + cleanPhone.slice(1)
  }
  const text = encodeURIComponent(
    `Assalamu'alaikum Warahmatullahi Wabarakatuh.\n` +
    `Bapak/Ibu ${jenisOrtu} dari Ananda *${namaAnak}* (No. Registrasi PPDB: *${noDaftar}*),\n\n` +
    `Kami dari Panitia Penerimaan Peserta Didik Baru (PPDB) SIT Bina Insan Mandiri Purwokerto ingin menyampaikan informasi terkait proses pendaftaran ananda...\n\n` +
    `Terima kasih. Wassalamu'alaikum Warahmatullahi Wabarakatuh.`
  )
  window.open(`https://wa.me/${cleanPhone}?text=${text}`, '_blank')
}

// Cetak Bukti Pendaftaran
const printDetailPage = () => {
  window.print()
}

onMounted(() => {
  fetchRecords()
})
</script>

<template>
  <div class="space-y-6">

    <!-- ========================================== -->
    <!-- TAMPILAN 1: DAFTAR PENDAFTAR (LIST VIEW)   -->
    <!-- ========================================== -->
    <div v-if="activeView === 'list'" class="space-y-6">
      <!-- Header Banner -->
      <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-800 via-teal-800 to-slate-900 p-6 sm:p-8 text-white shadow-xl">
        <div class="relative z-10 max-w-3xl space-y-2">
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-emerald-300 text-xs font-bold tracking-wider uppercase border border-white/10">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            Penerimaan Murid Baru
          </span>
          <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
            Manajemen PPDB Online Terpadu (KB, TK, SD)
          </h1>
          <p class="text-xs sm:text-sm text-emerald-100/90 leading-relaxed">
            Kelola data pendaftaran murid baru dari formulir publik tanpa login, verifikasi berkas, hubungi orang tua via WhatsApp, dan sinkronisasi otomatis ke Data Master saat siswa diterima.
          </p>
        </div>
        <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
      </div>

      <!-- BANNER SHARE LINK FORMULIR PUBLIK -->
      <div class="bg-white rounded-2xl p-5 shadow-xs border border-emerald-200/80 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-start gap-3.5">
          <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
          </div>
          <div>
            <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
              <span>Tautan Formulir PPDB Online Publik (Tanpa Login)</span>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Siap Bagikan</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
              Calon wali murid dapat langsung mendaftar secara mandiri tanpa login melalui link berikut:
            </p>
            <div class="mt-1.5 font-mono text-xs text-emerald-700 bg-emerald-50 px-3 py-1 rounded-lg border border-emerald-200 inline-block break-all">
              {{ publicFormUrl }}
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <button
            @click="copyPublicLink"
            type="button"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            <span>Salin Link Form</span>
          </button>

          <button
            @click="openPublicForm"
            type="button"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition border border-slate-300 cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
            <span>Buka Form</span>
          </button>
        </div>
      </div>

      <!-- METRIC SUMMARY CARDS -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Pendaftar</div>
          <div class="text-2xl font-black text-slate-900 mt-1 font-mono">{{ summary.total }}</div>
          <div class="text-[10px] text-slate-500 mt-0.5">Semua Jenjang</div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
          <div class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">Jenjang SD</div>
          <div class="text-2xl font-black text-emerald-800 mt-1 font-mono">{{ summary.sd }}</div>
          <div class="text-[10px] text-emerald-600 mt-0.5">Calon Siswa SDIT</div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
          <div class="text-[11px] font-bold text-teal-700 uppercase tracking-wider">Jenjang TK</div>
          <div class="text-2xl font-black text-teal-800 mt-1 font-mono">{{ summary.tka + summary.tkb }}</div>
          <div class="text-[10px] text-teal-600 mt-0.5">TK A: {{ summary.tka }} • TK B: {{ summary.tkb }}</div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
          <div class="text-[11px] font-bold text-amber-700 uppercase tracking-wider">Jenjang KB</div>
          <div class="text-2xl font-black text-amber-800 mt-1 font-mono">{{ summary.kb }}</div>
          <div class="text-[10px] text-amber-600 mt-0.5">Kelompok Bermain</div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
          <div class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Menunggu</div>
          <div class="text-2xl font-black text-amber-600 mt-1 font-mono">{{ summary.menunggu }}</div>
          <div class="text-[10px] text-amber-500 mt-0.5">Perlu Observasi</div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
          <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Diterima</div>
          <div class="text-2xl font-black text-emerald-600 mt-1 font-mono">{{ summary.diterima }}</div>
          <div class="text-[10px] text-emerald-500 mt-0.5">Masuk Data Master</div>
        </div>
      </div>

      <!-- FILTER & ACTION BAR -->
      <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-xs border border-slate-200/80 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
        <!-- Search Box -->
        <div class="flex-1 relative">
          <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </span>
          <input
            v-model="filters.search"
            @keyup.enter="fetchRecords"
            type="text"
            placeholder="Cari nama calon siswa, no pendaftaran, orang tua, no WA..."
            class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-xs sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
          />
        </div>

        <!-- Filter Jenjang & Status -->
        <div class="flex flex-wrap items-center gap-2">
          <select
            v-model="filters.jenjang"
            @change="fetchRecords"
            class="px-3 py-2 rounded-xl border border-slate-200 text-xs sm:text-sm bg-white font-medium text-slate-700 focus:outline-hidden focus:ring-2 focus:ring-emerald-500"
          >
            <option value="all">Semua Jenjang</option>
            <option value="SD">SD</option>
            <option value="TK A">TK A</option>
            <option value="TK B">TK B</option>
            <option value="KB">KB</option>
          </select>

          <select
            v-model="filters.status"
            @change="fetchRecords"
            class="px-3 py-2 rounded-xl border border-slate-200 text-xs sm:text-sm bg-white font-medium text-slate-700 focus:outline-hidden focus:ring-2 focus:ring-emerald-500"
          >
            <option value="all">Semua Status</option>
            <option value="menunggu">Menunggu Verifikasi</option>
            <option value="diterima">Diterima (Data Master)</option>
            <option value="cadangan">Cadangan</option>
            <option value="ditolak">Tidak Diterima</option>
          </select>

          <!-- Tombol Export Excel -->
          <button
            @click="exportExcel"
            type="button"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold transition border border-emerald-200 cursor-pointer shrink-0"
            title="Download Rekap Data PPDB (.xlsx)"
          >
            <svg class="w-4 h-4 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="hidden sm:inline">Export Excel</span>
          </button>
        </div>
      </div>

      <!-- TABEL DATA PENDAFTAR -->
      <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div v-if="loading" class="p-12 text-center text-slate-400">
          <div class="w-8 h-8 border-3 border-emerald-600 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
          <p class="text-xs font-medium">Memuat data pendaftar PPDB...</p>
        </div>

        <div v-else-if="records.length === 0" class="p-12 text-center text-slate-500">
          <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-2">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <p class="text-sm font-bold text-slate-700">Belum ada data pendaftar yang sesuai filter.</p>
          <p class="text-xs text-slate-400 mt-1">Bagikan tautan formulir PPDB online kepada calon wali murid.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                <th class="py-3.5 px-4">No. Pendaftaran</th>
                <th class="py-3.5 px-3">Tanggal</th>
                <th class="py-3.5 px-3">Jenjang</th>
                <th class="py-3.5 px-4">Nama Calon Siswa</th>
                <th class="py-3.5 px-4">TTL &amp; Usia</th>
                <th class="py-3.5 px-4">Orang Tua / Kontak</th>
                <th class="py-3.5 px-3">Berkas</th>
                <th class="py-3.5 px-3 text-center">Status</th>
                <th class="py-3.5 px-4 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr 
                v-for="record in records" 
                :key="record.id" 
                class="hover:bg-slate-50/80 transition-colors"
              >
                <!-- No Pendaftaran -->
                <td class="py-3.5 px-4 font-mono font-bold text-emerald-800 whitespace-nowrap">
                  <div>{{ record.nomor_pendaftaran }}</div>
                  <div v-if="record.student_id && record.student" class="text-[10px] text-emerald-600 font-bold">
                    NIS: {{ record.student.nis }}
                  </div>
                </td>

                <!-- Tanggal Daftar -->
                <td class="py-3.5 px-3 text-slate-500 whitespace-nowrap">
                  {{ formatDate(record.created_at) }}
                </td>

                <!-- Jenjang -->
                <td class="py-3.5 px-3 whitespace-nowrap">
                  <span 
                    class="px-2.5 py-0.5 rounded-full font-bold text-[10px]"
                    :class="{
                      'bg-emerald-100 text-emerald-800': record.jenjang === 'SD',
                      'bg-teal-100 text-teal-800': record.jenjang.startsWith('TK'),
                      'bg-amber-100 text-amber-800': record.jenjang === 'KB',
                    }"
                  >
                    {{ record.jenjang }}
                  </span>
                </td>

                <!-- Nama Calon Siswa -->
                <td class="py-3.5 px-4 font-semibold text-slate-900">
                  <div class="flex items-center gap-2.5">
                    <div 
                      class="w-7 h-7 rounded-full text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs"
                      :class="record.jenis_kelamin === 'L' ? 'bg-blue-600' : 'bg-pink-600'"
                    >
                      {{ record.jenis_kelamin }}
                    </div>
                    <div>
                      <div class="text-xs sm:text-sm font-bold text-slate-900 flex items-center gap-1.5 flex-wrap">
                        <span>{{ record.nama_lengkap }}</span>
                        <span 
                          v-if="record.student_id && record.student"
                          class="px-1.5 py-0.5 rounded text-[9px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300"
                          title="Siswa telah terdaftar di Data Master"
                        >
                          Tersinkron
                        </span>
                      </div>
                      <div class="text-[10px] text-slate-400">Panggilan: {{ record.nama_panggilan }}</div>
                    </div>
                  </div>
                </td>

                <!-- TTL & Usia -->
                <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">
                  <div>{{ record.tempat_lahir }}, {{ formatDate(record.tanggal_lahir) }}</div>
                  <div class="text-[10px] text-emerald-700 font-semibold">{{ record.usia_daftar || '-' }}</div>
                </td>

                <!-- Orang Tua & Kontak -->
                <td class="py-3.5 px-4 text-slate-700">
                  <div class="font-bold text-slate-800">{{ record.nama_ayah }}</div>
                  <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="font-mono text-[11px] text-slate-500">{{ record.no_hp_ayah }}</span>
                    <button
                      @click="chatWhatsApp(record.no_hp_ayah, record.nama_lengkap, record.nomor_pendaftaran, 'Ayah')"
                      type="button"
                      class="px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-[10px] font-bold hover:bg-emerald-100 transition cursor-pointer flex items-center gap-1"
                      title="Chat WhatsApp Ayah"
                    >
                      <span>WA</span>
                    </button>
                  </div>
                </td>

                <!-- Berkas Lampiran -->
                <td class="py-3.5 px-3 whitespace-nowrap">
                  <div class="flex items-center gap-1">
                    <span 
                      class="px-1.5 py-0.5 rounded text-[10px] font-medium"
                      :class="record.pas_foto_url ? 'bg-emerald-50 text-emerald-700 font-bold border border-emerald-200' : 'bg-slate-100 text-slate-400'"
                      title="Pas Foto 3x4"
                    >
                      Foto
                    </span>
                    <span 
                      class="px-1.5 py-0.5 rounded text-[10px] font-medium"
                      :class="record.akta_kelahiran_url ? 'bg-emerald-50 text-emerald-700 font-bold border border-emerald-200' : 'bg-slate-100 text-slate-400'"
                      title="Akta Kelahiran"
                    >
                      Akta
                    </span>
                    <span 
                      class="px-1.5 py-0.5 rounded text-[10px] font-medium"
                      :class="record.kartu_keluarga_url ? 'bg-emerald-50 text-emerald-700 font-bold border border-emerald-200' : 'bg-slate-100 text-slate-400'"
                      title="Kartu Keluarga"
                    >
                      KK
                    </span>
                  </div>
                </td>

                <!-- Status -->
                <td class="py-3.5 px-3 text-center whitespace-nowrap">
                  <span 
                    class="px-2.5 py-1 rounded-full text-[10px] font-bold"
                    :class="{
                      'bg-amber-100 text-amber-800': record.status === 'menunggu',
                      'bg-emerald-100 text-emerald-800': record.status === 'diterima',
                      'bg-blue-100 text-blue-800': record.status === 'cadangan',
                      'bg-rose-100 text-rose-800': record.status === 'ditolak',
                    }"
                  >
                    {{ record.status_label }}
                  </span>
                </td>

                <!-- Aksi -->
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                  <div class="flex items-center justify-center gap-1.5">
                    <button
                      @click="openDetail(record)"
                      type="button"
                      class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition cursor-pointer shadow-xs"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      <span>Detail</span>
                    </button>

                    <button
                      @click="deleteRecord(record)"
                      type="button"
                      class="p-1.5 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 transition cursor-pointer"
                      title="Hapus Data Pendaftar"
                    >
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Footer -->
        <div v-if="pagination.lastPage > 1" class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
          <div>Halaman {{ pagination.currentPage }} dari {{ pagination.lastPage }} (Total {{ pagination.total }} data)</div>
          <div class="flex items-center gap-1">
            <button
              :disabled="pagination.currentPage <= 1"
              @click="filters.page--; fetchRecords()"
              class="px-3 py-1.5 rounded-lg border border-slate-200 disabled:opacity-40 hover:bg-slate-50 cursor-pointer"
            >
              &larr; Sebelumnya
            </button>
            <button
              :disabled="pagination.currentPage >= pagination.lastPage"
              @click="filters.page++; fetchRecords()"
              class="px-3 py-1.5 rounded-lg border border-slate-200 disabled:opacity-40 hover:bg-slate-50 cursor-pointer"
            >
              Berikutnya &rarr;
            </button>
          </div>
        </div>
      </div>
    </div>


    <!-- ============================================================== -->
    <!-- TAMPILAN 2: RINCIAN DETAIL PENDAFTAR (IN-PAGE / BUKAN POPUP)   -->
    <!-- ============================================================== -->
    <div v-else-if="activeView === 'detail' && selectedRecord" class="space-y-6">

      <!-- Breadcrumb & Navigasi Kembali -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <button
          @click="backToList"
          type="button"
          class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs sm:text-sm border border-slate-200 shadow-xs transition cursor-pointer"
        >
          <svg class="w-4 h-4 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Kembali ke Daftar Pendaftar</span>
        </button>

        <div class="flex items-center gap-2">
          <!-- Tombol Cetak / Print -->
          <button
            @click="printDetailPage"
            type="button"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-bold transition shadow-xs cursor-pointer"
            title="Cetak Rincian Formulir Pendaftaran"
          >
            <svg class="w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            <span>Cetak Formulir</span>
          </button>

          <!-- Tombol Hapus Pendaftar -->
          <button
            @click="deleteRecord(selectedRecord)"
            type="button"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition cursor-pointer"
            title="Hapus data pendaftar ini"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span class="hidden sm:inline">Hapus</span>
          </button>
        </div>
      </div>

      <!-- BANNER NOTIFIKASI SINKRONISASI DATA MASTER SISWA -->
      <div 
        v-if="selectedRecord.status === 'diterima' && selectedRecord.student" 
        class="bg-gradient-to-r from-emerald-600 via-teal-700 to-emerald-800 rounded-3xl p-5 sm:p-6 text-white flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-lg shadow-emerald-900/20"
      >
        <div class="flex items-start sm:items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md text-white flex items-center justify-center shrink-0 border border-white/20">
            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="space-y-0.5">
            <div class="flex items-center gap-2 flex-wrap">
              <h3 class="text-sm sm:text-base font-black tracking-tight text-white">
                Otomatis Tercatat di Data Master Siswa
              </h3>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-white text-emerald-800 uppercase tracking-wider">
                Status: {{ selectedRecord.student.status }}
              </span>
            </div>
            <p class="text-xs text-emerald-100 leading-relaxed">
              Data ananda beserta orang tua (Ayah &amp; Ibu) telah otomatis masuk ke sistem master sekolah dengan 
              <strong class="font-mono bg-white/20 px-2 py-0.5 rounded-md border border-white/20 text-white">NIS: {{ selectedRecord.student.nis }}</strong>.
            </p>
          </div>
        </div>

        <RouterLink
          to="/students"
          class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-white text-emerald-800 hover:bg-emerald-50 text-xs font-black transition shadow-sm cursor-pointer shrink-0"
        >
          <span>Buka Master Data Siswa</span>
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
          </svg>
        </RouterLink>
      </div>

      <!-- HEADER PROFIL CALON SISWA -->
      <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-sm relative overflow-hidden">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
          <div class="space-y-3">
            <!-- Badges -->
            <div class="flex flex-wrap items-center gap-2">
              <span 
                class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider shadow-xs"
                :class="{
                  'bg-emerald-600 text-white': selectedRecord.jenjang === 'SD',
                  'bg-teal-600 text-white': selectedRecord.jenjang.startsWith('TK'),
                  'bg-amber-500 text-white': selectedRecord.jenjang === 'KB',
                }"
              >
                Jenjang {{ selectedRecord.jenjang }}
              </span>

              <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                T.A {{ selectedRecord.tahun_ajaran }} • Gelombang {{ selectedRecord.gelombang }}
              </span>

              <button 
                @click="copyText(selectedRecord.nomor_pendaftaran, 'Nomor Pendaftaran')"
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-mono font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 transition cursor-pointer"
                title="Klik untuk menyalin nomor registrasi"
              >
                <span>{{ selectedRecord.nomor_pendaftaran }}</span>
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
              </button>
            </div>

            <!-- Nama Utama -->
            <div>
              <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                {{ selectedRecord.nama_lengkap }}
              </h1>
              <p class="text-sm font-semibold text-emerald-700 mt-0.5">
                Nama Panggilan: <span class="text-slate-800 font-bold">{{ selectedRecord.nama_panggilan || '-' }}</span>
              </p>
            </div>

            <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500">
              <span class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Terdaftar: {{ formatDateTime(selectedRecord.created_at) }}
              </span>
              <span v-if="selectedRecord.verified_at" class="flex items-center gap-1.5 text-emerald-700 font-medium">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Diverifikasi: {{ formatDateTime(selectedRecord.verified_at) }}
              </span>
            </div>
          </div>

          <!-- Status Card Highlight & Quick Action -->
          <div class="flex flex-col sm:flex-row lg:flex-col items-start lg:items-end gap-3 shrink-0">
            <div 
              class="px-5 py-2.5 rounded-2xl font-black text-sm flex items-center gap-2 border shadow-xs"
              :class="{
                'bg-amber-50 text-amber-900 border-amber-200': selectedRecord.status === 'menunggu',
                'bg-emerald-50 text-emerald-900 border-emerald-300': selectedRecord.status === 'diterima',
                'bg-blue-50 text-blue-900 border-blue-200': selectedRecord.status === 'cadangan',
                'bg-rose-50 text-rose-900 border-rose-200': selectedRecord.status === 'ditolak',
              }"
            >
              <span 
                class="w-2.5 h-2.5 rounded-full animate-pulse"
                :class="{
                  'bg-amber-500': selectedRecord.status === 'menunggu',
                  'bg-emerald-500': selectedRecord.status === 'diterima',
                  'bg-blue-500': selectedRecord.status === 'cadangan',
                  'bg-rose-500': selectedRecord.status === 'ditolak',
                }"
              ></span>
              <span>Status: {{ selectedRecord.status_label }}</span>
            </div>

            <!-- Quick WhatsApp Hubungi Ortu -->
            <div class="flex items-center gap-2">
              <button
                @click="chatWhatsApp(selectedRecord.no_hp_ayah, selectedRecord.nama_lengkap, selectedRecord.nomor_pendaftaran, 'Ayah')"
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs cursor-pointer"
                title="Kirim pesan WhatsApp ke Ayah"
              >
                <span>Chat WA Ayah</span>
              </button>
              <button
                @click="chatWhatsApp(selectedRecord.no_hp_ibu, selectedRecord.nama_lengkap, selectedRecord.nomor_pendaftaran, 'Ibu')"
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold transition shadow-xs cursor-pointer"
                title="Kirim pesan WhatsApp ke Ibu"
              >
                <span>Chat WA Ibu</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- MAIN DETAIL GRID (2 KOLOM: KIRI FOTO & STATUS, KANAN IDENTITAS LENGKAP & ORTU & BERKAS) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- KOLOM KIRI (LEBAR 4/12) -->
        <div class="lg:col-span-4 space-y-6">

          <!-- KARTU PAS FOTO 3X4 -->
          <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
              <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span>Pas Foto Calon Siswa (3x4)</span>
            </h3>

            <!-- Frame Foto Proporsional -->
            <div class="w-full max-w-[240px] mx-auto aspect-3/4 rounded-2xl overflow-hidden bg-slate-100 border-2 border-slate-200 shadow-inner flex flex-col items-center justify-center relative group">
              <img
                v-if="selectedRecord.pas_foto_url"
                :src="selectedRecord.pas_foto_url"
                alt="Pas Foto 3x4 Calon Siswa"
                class="w-full h-full object-cover"
              />
              <div v-else class="text-center p-4 text-slate-400">
                <svg class="w-12 h-12 mx-auto mb-2 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="text-xs font-semibold block">Belum ada foto</span>
                <span class="text-[10px] text-slate-400">Dapat diserahkan fisik saat observasi</span>
              </div>

              <!-- Overlay preview jika ada foto -->
              <a 
                v-if="selectedRecord.pas_foto_url"
                :href="selectedRecord.pas_foto_url" 
                target="_blank"
                class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white font-bold text-xs gap-1"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                <span>Lihat Foto Penuh</span>
              </a>
            </div>

            <!-- Info Cepat di Bawah Foto -->
            <div class="grid grid-cols-2 gap-2 pt-2 text-center text-xs">
              <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-[10px] text-slate-400 block uppercase font-bold">Jenis Kelamin</span>
                <span class="font-bold text-slate-800">
                  {{ selectedRecord.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
                </span>
              </div>
              <div class="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-100">
                <span class="text-[10px] text-emerald-600 block uppercase font-bold">Usia Saat Daftar</span>
                <span class="font-bold text-emerald-800">{{ selectedRecord.usia_daftar || '-' }}</span>
              </div>
            </div>
          </div>

          <!-- KARTU FORMULIR VERIFIKASI & UPDATE STATUS PANITIA -->
          <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span>Verifikasi Panitia</span>
              </h3>
              <span class="text-[10px] font-bold text-slate-400">Panel Admin</span>
            </div>

            <form @submit.prevent="saveStatus" class="space-y-4">
              <!-- Pilihan Status -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                  Tentukan Status Pendaftaran *
                </label>
                <select
                  v-model="statusForm.status"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm font-bold bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
                >
                  <option value="menunggu">🟡 Menunggu Verifikasi / Observasi</option>
                  <option value="diterima">🟢 Diterima Sebagai Siswa Baru (Masuk Master)</option>
                  <option value="cadangan">🔵 Cadangan</option>
                  <option value="ditolak">🔴 Tidak Diterima</option>
                </select>
                <p v-if="statusForm.status === 'diterima'" class="text-[11px] text-emerald-700 font-semibold mt-1.5 flex items-start gap-1 leading-relaxed">
                  <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>Data siswa, orang tua, dan rombel otomatis terdaftar di Data Master dengan nomor NIS unik.</span>
                </p>
              </div>

              <!-- Input Catatan Admin -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                  Catatan Panitia / Observasi
                </label>
                <textarea
                  v-model="statusForm.catatan_admin"
                  rows="3"
                  placeholder="Misal: Sudah ikut sesi observasi, berkas KK asli diverifikasi, siap daftar ulang..."
                  class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
                ></textarea>
                <p class="text-[11px] text-slate-400 mt-1">
                  Catatan ini dapat dibaca oleh calon wali murid saat cek status pendaftaran.
                </p>
              </div>

              <!-- Tombol Simpan -->
              <button
                type="submit"
                :disabled="updatingStatus"
                class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-md shadow-emerald-600/20 cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50"
              >
                <span v-if="updatingStatus" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span>{{ updatingStatus ? 'Menyimpan Perubahan...' : 'Simpan Status & Catatan' }}</span>
              </button>
            </form>
          </div>

        </div>


        <!-- KOLOM KANAN (LEBAR 8/12) -->
        <div class="lg:col-span-8 space-y-6">

          <!-- KARTU 1: IDENTITAS CALON SISWA -->
          <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs space-y-5">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
              <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <div>
                <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider">Identitas Lengkap Calon Siswa</h3>
                <p class="text-[11px] text-slate-400">Data pokok calon peserta didik yang didaftarkan</p>
              </div>
            </div>

            <!-- Grid Identitas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-xs">
              <div class="p-3 bg-slate-50/80 rounded-2xl border border-slate-100">
                <span class="text-slate-400 block text-[11px]">Nama Lengkap</span>
                <strong class="text-slate-900 text-sm block mt-0.5">{{ selectedRecord.nama_lengkap }}</strong>
              </div>

              <div class="p-3 bg-slate-50/80 rounded-2xl border border-slate-100">
                <span class="text-slate-400 block text-[11px]">Nama Panggilan</span>
                <strong class="text-slate-900 text-sm block mt-0.5">{{ selectedRecord.nama_panggilan || '-' }}</strong>
              </div>

              <div class="p-3 bg-slate-50/80 rounded-2xl border border-slate-100">
                <span class="text-slate-400 block text-[11px]">Jenis Kelamin</span>
                <strong class="text-slate-900 text-sm block mt-0.5">
                  {{ selectedRecord.jenis_kelamin === 'L' ? 'Laki-laki (Ikhwan)' : 'Perempuan (Akhwat)' }}
                </strong>
              </div>

              <div class="p-3 bg-slate-50/80 rounded-2xl border border-slate-100">
                <span class="text-slate-400 block text-[11px]">Tempat, Tanggal Lahir</span>
                <strong class="text-slate-900 text-sm block mt-0.5">
                  {{ selectedRecord.tempat_lahir }}, {{ formatDate(selectedRecord.tanggal_lahir) }}
                </strong>
              </div>

              <div class="p-3 bg-slate-50/80 rounded-2xl border border-slate-100">
                <span class="text-slate-400 block text-[11px]">Usia Saat Mendaftar</span>
                <strong class="text-emerald-700 text-sm block mt-0.5">{{ selectedRecord.usia_daftar || '-' }}</strong>
              </div>

              <div class="p-3 bg-slate-50/80 rounded-2xl border border-slate-100">
                <span class="text-slate-400 block text-[11px]">Anak Ke / Jumlah Saudara</span>
                <strong class="text-slate-900 text-sm block mt-0.5">
                  Anak ke-{{ selectedRecord.anak_ke }} dari {{ selectedRecord.jumlah_saudara }} bersaudara
                </strong>
              </div>

              <div class="p-3 bg-slate-50/80 rounded-2xl border border-slate-100 sm:col-span-2 md:col-span-3">
                <span class="text-slate-400 block text-[11px]">Alamat Lengkap Tempat Tinggal</span>
                <strong class="text-slate-800 text-sm block mt-0.5 leading-relaxed">{{ selectedRecord.alamat_lengkap }}</strong>
              </div>

              <div class="p-3 bg-slate-50/80 rounded-2xl border border-slate-100 sm:col-span-2 md:col-span-3">
                <span class="text-slate-400 block text-[11px]">Tinggal Bersama</span>
                <strong class="text-slate-800 text-sm block mt-0.5">{{ selectedRecord.tinggal_dengan }}</strong>
              </div>
            </div>

            <!-- Asal Sekolah & Info Pindahan -->
            <div class="pt-2 border-t border-slate-100 space-y-3">
              <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Riwayat Sekolah Sebelumnya</h4>
              
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="p-3 bg-slate-50/80 rounded-2xl border border-slate-100">
                  <span class="text-slate-400 block text-[11px]">Nama Asal Sekolah</span>
                  <strong class="text-slate-800 text-sm block mt-0.5">{{ selectedRecord.asal_sekolah || 'Belum pernah sekolah / Langsung' }}</strong>
                </div>

                <div class="p-3 bg-slate-50/80 rounded-2xl border border-slate-100">
                  <span class="text-slate-400 block text-[11px]">Alamat Asal Sekolah</span>
                  <strong class="text-slate-800 text-sm block mt-0.5">{{ selectedRecord.alamat_asal_sekolah || '-' }}</strong>
                </div>
              </div>

              <!-- Banner Khusus Siswa Pindahan -->
              <div 
                v-if="selectedRecord.pindah_ke_kelas"
                class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 space-y-1"
              >
                <div class="flex items-center gap-2 font-bold text-xs uppercase tracking-wider text-amber-800">
                  <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>Pendaftaran Siswa Pindahan (Mutasi)</span>
                </div>
                <div class="text-xs">
                  Mendaftar untuk masuk ke: <strong class="font-bold">Kelas {{ selectedRecord.pindah_ke_kelas }}</strong>
                </div>
                <div class="text-xs text-amber-800/90">
                  Alasan Kepindahan: <em>"{{ selectedRecord.alasan_pindah || '-' }}"</em>
                </div>
              </div>
            </div>
          </div>


          <!-- KARTU 2: DATA ORANG TUA / WALI -->
          <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs space-y-5">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
              <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>
              <div>
                <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider">Data Orang Tua / Wali Calon Siswa</h3>
                <p class="text-[11px] text-slate-400">Rincian profil ayah dan ibu beserta nomor kontak</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              
              <!-- KARTU AYAH -->
              <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/70 space-y-3.5">
                <div class="flex items-center justify-between border-b border-slate-200/70 pb-2.5">
                  <span class="font-black text-xs text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                    <span>Data Ayah Kandung</span>
                  </span>
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">Ayah</span>
                </div>

                <div class="space-y-2 text-xs">
                  <div>
                    <span class="text-slate-400 text-[11px] block">Nama Lengkap Ayah:</span>
                    <strong class="text-slate-900 text-sm block">{{ selectedRecord.nama_ayah }}</strong>
                  </div>
                  <div>
                    <span class="text-slate-400 text-[11px] block">Tempat, Tgl Lahir:</span>
                    <span class="text-slate-700 font-medium">{{ selectedRecord.ttl_ayah || '-' }}</span>
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <span class="text-slate-400 text-[11px] block">Pendidikan:</span>
                      <span class="text-slate-700 font-semibold">{{ selectedRecord.pendidikan_ayah || '-' }}</span>
                    </div>
                    <div>
                      <span class="text-slate-400 text-[11px] block">Pekerjaan:</span>
                      <span class="text-slate-700 font-semibold">{{ selectedRecord.pekerjaan_ayah || '-' }}</span>
                    </div>
                  </div>
                  <div>
                    <span class="text-slate-400 text-[11px] block">Penghasilan Rata-rata:</span>
                    <span class="text-emerald-700 font-bold">{{ selectedRecord.penghasilan_ayah || '-' }}</span>
                  </div>
                  <div>
                    <span class="text-slate-400 text-[11px] block">Alamat Ayah:</span>
                    <span class="text-slate-700 text-xs">{{ selectedRecord.alamat_ayah || '-' }}</span>
                  </div>
                </div>

                <!-- Kontak WA Ayah -->
                <div class="pt-2 border-t border-slate-200/70 flex items-center justify-between">
                  <div>
                    <span class="text-[10px] text-slate-400 block">No. WhatsApp Ayah</span>
                    <strong class="font-mono text-emerald-800 text-xs font-bold">{{ selectedRecord.no_hp_ayah }}</strong>
                  </div>
                  <button
                    @click="chatWhatsApp(selectedRecord.no_hp_ayah, selectedRecord.nama_lengkap, selectedRecord.nomor_pendaftaran, 'Ayah')"
                    type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition shadow-xs cursor-pointer"
                  >
                    <span>Kirim WA</span>
                  </button>
                </div>
              </div>

              <!-- KARTU IBU -->
              <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/70 space-y-3.5">
                <div class="flex items-center justify-between border-b border-slate-200/70 pb-2.5">
                  <span class="font-black text-xs text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-pink-500"></span>
                    <span>Data Ibu Kandung</span>
                  </span>
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-pink-100 text-pink-800">Ibu</span>
                </div>

                <div class="space-y-2 text-xs">
                  <div>
                    <span class="text-slate-400 text-[11px] block">Nama Lengkap Ibu:</span>
                    <strong class="text-slate-900 text-sm block">{{ selectedRecord.nama_ibu }}</strong>
                  </div>
                  <div>
                    <span class="text-slate-400 text-[11px] block">Tempat, Tgl Lahir:</span>
                    <span class="text-slate-700 font-medium">{{ selectedRecord.ttl_ibu || '-' }}</span>
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <span class="text-slate-400 text-[11px] block">Pendidikan:</span>
                      <span class="text-slate-700 font-semibold">{{ selectedRecord.pendidikan_ibu || '-' }}</span>
                    </div>
                    <div>
                      <span class="text-slate-400 text-[11px] block">Pekerjaan:</span>
                      <span class="text-slate-700 font-semibold">{{ selectedRecord.pekerjaan_ibu || '-' }}</span>
                    </div>
                  </div>
                  <div>
                    <span class="text-slate-400 text-[11px] block">Penghasilan Rata-rata:</span>
                    <span class="text-emerald-700 font-bold">{{ selectedRecord.penghasilan_ibu || '-' }}</span>
                  </div>
                  <div>
                    <span class="text-slate-400 text-[11px] block">Alamat Ibu:</span>
                    <span class="text-slate-700 text-xs">{{ selectedRecord.alamat_ibu || '-' }}</span>
                  </div>
                </div>

                <!-- Kontak WA Ibu -->
                <div class="pt-2 border-t border-slate-200/70 flex items-center justify-between">
                  <div>
                    <span class="text-[10px] text-slate-400 block">No. WhatsApp Ibu</span>
                    <strong class="font-mono text-emerald-800 text-xs font-bold">{{ selectedRecord.no_hp_ibu }}</strong>
                  </div>
                  <button
                    @click="chatWhatsApp(selectedRecord.no_hp_ibu, selectedRecord.nama_lengkap, selectedRecord.nomor_pendaftaran, 'Ibu')"
                    type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs transition shadow-xs cursor-pointer"
                  >
                    <span>Kirim WA</span>
                  </button>
                </div>
              </div>

            </div>
          </div>


          <!-- KARTU 3: BERKAS & DOKUMEN PERSYARATAN -->
          <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs space-y-5">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
              <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <div>
                <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider">Berkas Lampiran Persyaratan</h3>
                <p class="text-[11px] text-slate-400">Dokumen digital yang telah diunggah oleh pendaftar</p>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              
              <!-- Akta Kelahiran -->
              <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0">
                  <svg class="w-5 h-5 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                  </svg>
                </div>
                <div class="flex-1 space-y-1">
                  <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-slate-800">Akta Kelahiran</h4>
                    <span 
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                      :class="selectedRecord.akta_kelahiran_url ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-500'"
                    >
                      {{ selectedRecord.akta_kelahiran_url ? 'Sudah Diunggah' : 'Belum Ada' }}
                    </span>
                  </div>
                  <p class="text-[11px] text-slate-500">Bukti keabsahan nama dan tanggal lahir ananda</p>
                  <div class="pt-2">
                    <a
                      v-if="selectedRecord.akta_kelahiran_url"
                      :href="selectedRecord.akta_kelahiran_url"
                      target="_blank"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition cursor-pointer shadow-xs"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                      </svg>
                      <span>Buka File Akta</span>
                    </a>
                    <span v-else class="text-[11px] text-slate-400 italic">
                      Calon siswa belum melampirkan file akta.
                    </span>
                  </div>
                </div>
              </div>

              <!-- Kartu Keluarga -->
              <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-teal-100 text-teal-800 flex items-center justify-center shrink-0">
                  <svg class="w-5 h-5 text-teal-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <div class="flex-1 space-y-1">
                  <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-slate-800">Kartu Keluarga (KK)</h4>
                    <span 
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                      :class="selectedRecord.kartu_keluarga_url ? 'bg-teal-100 text-teal-800' : 'bg-slate-200 text-slate-500'"
                    >
                      {{ selectedRecord.kartu_keluarga_url ? 'Sudah Diunggah' : 'Belum Ada' }}
                    </span>
                  </div>
                  <p class="text-[11px] text-slate-500">Bukti domisili dan susunan anggota keluarga</p>
                  <div class="pt-2">
                    <a
                      v-if="selectedRecord.kartu_keluarga_url"
                      :href="selectedRecord.kartu_keluarga_url"
                      target="_blank"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs transition cursor-pointer shadow-xs"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                      </svg>
                      <span>Buka File KK</span>
                    </a>
                    <span v-else class="text-[11px] text-slate-400 italic">
                      Calon siswa belum melampirkan file kartu keluarga.
                    </span>
                  </div>
                </div>
              </div>

            </div>

            <!-- Catatan Verifikasi Fisik -->
            <div class="p-3.5 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 text-emerald-900 text-xs flex items-start gap-2.5">
              <svg class="w-5 h-5 text-emerald-700 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div>
                <strong>Petunjuk Verifikasi Berkas:</strong> Dokumen fisik asli (Akta Kelahiran &amp; KK) harap dicocokkan kembali oleh tim panitia saat sesi observasi calon siswa dan wawancara orang tua di sekolah.
              </div>
            </div>
          </div>

        </div>

      </div>

    </div>

  </div>
</template>
