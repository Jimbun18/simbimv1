<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'
import { showSuccess, showError, showWarning, showConfirm } from '../services/swal'

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
  created_at: string
}

// State
const records = ref<PpdbRecord[]>([])
const loading = ref(false)
const selectedRecord = ref<PpdbRecord | null>(null)
const showDetailModal = ref(false)
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
  showSuccess('Tersalin!', 'Tautan formulir PPDB online berhasil disalin. Anda dapat membagikannya ke media sosial atau pesan WhatsApp.')
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

// Buka Detail Modal
const openDetail = (record: PpdbRecord) => {
  selectedRecord.value = record
  statusForm.value = {
    status: record.status,
    catatan_admin: record.catatan_admin || '',
  }
  showDetailModal.value = true
}

// Simpan Perubahan Status
const saveStatus = async () => {
  if (!selectedRecord.value) return
  updatingStatus.value = true
  try {
    const res = await api.put(`/ppdb/${selectedRecord.value.id}/status`, {
      status: statusForm.value.status,
      catatan_admin: statusForm.value.catatan_admin,
    })

    if (res.data?.success) {
      showSuccess('Berhasil!', 'Status pendaftaran dan catatan berhasil diperbarui.')
      showDetailModal.value = false
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
    `Data pendaftaran ${record.nama_lengkap} (${record.nomor_pendaftaran}) beserta berkasnya akan dihapus permanen.`
  )
  if (!confirmed) return

  try {
    const res = await api.delete(`/ppdb/${record.id}`)
    if (res.data?.success) {
      showSuccess('Berhasil', 'Data pendaftar PPDB berhasil dihapus.')
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

    // Download via link
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

// Hubungi Orang Tua via WhatsApp
const chatWhatsApp = (phone: string, namaAnak: string, noDaftar: string) => {
  if (!phone) return
  let cleanPhone = phone.replace(/[^0-9]/g, '')
  if (cleanPhone.startsWith('0')) {
    cleanPhone = '62' + cleanPhone.slice(1)
  }
  const text = encodeURIComponent(
    `Assalamu'alaikum Warahmatullahi Wabarakatuh.\n` +
    `Bapak/Ibu Orang Tua dari Ananda *${namaAnak}* (No. Registrasi: *${noDaftar}*),\n` +
    `Kami dari Panitia PPDB SIT Bina Insan Mandiri Purwokerto...`
  )
  window.open(`https://wa.me/${cleanPhone}?text=${text}`, '_blank')
}

onMounted(() => {
  fetchRecords()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-800 via-teal-800 to-slate-900 p-6 sm:p-8 text-white shadow-xl">
      <div class="relative z-10 max-w-3xl space-y-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-emerald-300 text-xs font-bold tracking-wider uppercase border border-white/10">
          <span>📋</span> Penerimaan Murid Baru
        </span>
        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
          Manajemen PPDB Online Terpadu (KB, TK, SD)
        </h1>
        <p class="text-xs sm:text-sm text-emerald-100/90 leading-relaxed">
          Kelola data pendaftaran murid baru dari formulir publik tanpa login, verifikasi berkas, hubungi orang tua, dan ekspor data arsip pendaftaran.
        </p>
      </div>
      <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- BANNER SHARE LINK FORMULIR PUBLIK -->
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-emerald-200/80 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex items-start gap-3.5">
        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-xl shrink-0">
          🔗
        </div>
        <div>
          <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
            <span>Tautan Formulir PPDB Online Publik (Tanpa Login)</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Siap Bagikan</span>
          </h2>
          <p class="text-xs text-slate-500 mt-0.5">
            Calon wali murid dapat langsung mendaftar tanpa login melalui tautan ini:
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
          <span>📋</span>
          <span>Salin Link Form</span>
        </button>

        <button
          @click="openPublicForm"
          type="button"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition border border-slate-300 cursor-pointer"
        >
          <span>↗️</span>
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
        <div class="text-[10px] text-emerald-500 mt-0.5">Siap Masuk</div>
      </div>
    </div>

    <!-- FILTER & ACTION BAR -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-xs border border-slate-200/80 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
      <!-- Search Box -->
      <div class="flex-1 relative">
        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
          🔍
        </span>
        <input
          v-model="filters.search"
          @keyup.enter="fetchRecords"
          type="text"
          placeholder="Cari nama calon siswa, no pendaftaran, orang tua, no WA..."
          class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-xs sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
        />
      </div>

      <!-- Filter Jenjang -->
      <div class="flex items-center gap-2">
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

        <!-- Filter Status -->
        <select
          v-model="filters.status"
          @change="fetchRecords"
          class="px-3 py-2 rounded-xl border border-slate-200 text-xs sm:text-sm bg-white font-medium text-slate-700 focus:outline-hidden focus:ring-2 focus:ring-emerald-500"
        >
          <option value="all">Semua Status</option>
          <option value="menunggu">Menunggu Verifikasi</option>
          <option value="diterima">Diterima</option>
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
          <span>📊</span>
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
        <span class="text-4xl block mb-2">📋</span>
        <p class="text-sm font-bold text-slate-700">Belum ada data pendaftar yang sesuai filter.</p>
        <p class="text-xs text-slate-400 mt-1">Bagikan tautan formulir PPDB online kepada calon wali murid.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
              <th class="py-3 px-4">No. Pendaftaran</th>
              <th class="py-3 px-3">Tanggal</th>
              <th class="py-3 px-3">Jenjang</th>
              <th class="py-3 px-4">Nama Calon Siswa</th>
              <th class="py-3 px-4">TTL &amp; Usia</th>
              <th class="py-3 px-4">Orang Tua / Kontak</th>
              <th class="py-3 px-3">Berkas</th>
              <th class="py-3 px-3 text-center">Status</th>
              <th class="py-3 px-4 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr 
              v-for="record in records" 
              :key="record.id" 
              class="hover:bg-slate-50/80 transition-colors"
            >
              <!-- No Pendaftaran -->
              <td class="py-3 px-4 font-mono font-bold text-emerald-800 whitespace-nowrap">
                {{ record.nomor_pendaftaran }}
              </td>

              <!-- Tanggal Daftar -->
              <td class="py-3 px-3 text-slate-500 whitespace-nowrap">
                {{ formatDate(record.created_at) }}
              </td>

              <!-- Jenjang -->
              <td class="py-3 px-3 whitespace-nowrap">
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
              <td class="py-3 px-4 font-semibold text-slate-900">
                <div class="flex items-center gap-2">
                  <div 
                    class="w-7 h-7 rounded-full text-white flex items-center justify-center font-bold text-xs shrink-0"
                    :class="record.jenis_kelamin === 'L' ? 'bg-blue-600' : 'bg-pink-600'"
                  >
                    {{ record.jenis_kelamin }}
                  </div>
                  <div>
                    <div class="text-xs sm:text-sm font-bold">{{ record.nama_lengkap }}</div>
                    <div class="text-[10px] text-slate-400">Panggilan: {{ record.nama_panggilan }}</div>
                  </div>
                </div>
              </td>

              <!-- TTL & Usia -->
              <td class="py-3 px-4 text-slate-600 whitespace-nowrap">
                <div>{{ record.tempat_lahir }}, {{ formatDate(record.tanggal_lahir) }}</div>
                <div class="text-[10px] text-emerald-700 font-medium">{{ record.usia_daftar || '-' }}</div>
              </td>

              <!-- Orang Tua & Kontak -->
              <td class="py-3 px-4 text-slate-700">
                <div class="font-bold text-slate-800">{{ record.nama_ayah }}</div>
                <div class="flex items-center gap-1.5 mt-0.5">
                  <span class="font-mono text-[11px] text-slate-500">{{ record.no_hp_ayah }}</span>
                  <button
                    @click="chatWhatsApp(record.no_hp_ayah, record.nama_lengkap, record.nomor_pendaftaran)"
                    type="button"
                    class="px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-[10px] font-bold hover:bg-emerald-100 transition cursor-pointer"
                    title="Kirim pesan WhatsApp ke orang tua"
                  >
                    💬 WA
                  </button>
                </div>
              </td>

              <!-- Berkas Lampiran -->
              <td class="py-3 px-3 whitespace-nowrap">
                <div class="flex items-center gap-1">
                  <span 
                    class="p-1 rounded-md text-[10px]"
                    :class="record.pas_foto_url ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-slate-300'"
                    title="Pas Foto"
                  >
                    📸
                  </span>
                  <span 
                    class="p-1 rounded-md text-[10px]"
                    :class="record.akta_kelahiran_url ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-slate-300'"
                    title="Akta Kelahiran"
                  >
                    📄
                  </span>
                  <span 
                    class="p-1 rounded-md text-[10px]"
                    :class="record.kartu_keluarga_url ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-slate-300'"
                    title="Kartu Keluarga"
                  >
                    📑
                  </span>
                </div>
              </td>

              <!-- Status -->
              <td class="py-3 px-3 text-center whitespace-nowrap">
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
              <td class="py-3 px-4 text-center whitespace-nowrap">
                <div class="flex items-center justify-center gap-1.5">
                  <button
                    @click="openDetail(record)"
                    type="button"
                    class="px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-800 hover:bg-emerald-100 font-bold text-xs transition cursor-pointer"
                  >
                    Detail &amp; Berkas
                  </button>

                  <button
                    @click="deleteRecord(record)"
                    type="button"
                    class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition cursor-pointer"
                    title="Hapus Data"
                  >
                    🗑️
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

    <!-- MODAL DETAIL & VERIFIKASI PENDAFTAR -->
    <div 
      v-if="showDetailModal && selectedRecord"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-fadeIn"
    >
      <div class="bg-white rounded-3xl max-w-3xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-6 max-h-[92vh] overflow-y-auto">
        <div class="flex items-start justify-between border-b border-slate-100 pb-4">
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-full text-xs font-black uppercase tracking-wider bg-emerald-100 text-emerald-800">
                {{ selectedRecord.jenjang }}
              </span>
              <span class="text-xs font-mono font-bold text-slate-400">{{ selectedRecord.nomor_pendaftaran }}</span>
            </div>
            <h3 class="text-xl font-black text-slate-900 mt-1">
              {{ selectedRecord.nama_lengkap }} ({{ selectedRecord.nama_panggilan }})
            </h3>
            <p class="text-xs text-slate-500">Mendaftar pada {{ formatDate(selectedRecord.created_at) }}</p>
          </div>

          <button 
            @click="showDetailModal = false"
            class="p-1 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 text-xl font-bold cursor-pointer"
          >
            ✕
          </button>
        </div>

        <!-- 3 Kolom Rincian Identitas -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
          <!-- Pas Foto -->
          <div class="md:col-span-3 text-center space-y-2">
            <div class="aspect-4/5 w-full rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 shadow-inner flex items-center justify-center">
              <img 
                v-if="selectedRecord.pas_foto_url" 
                :src="selectedRecord.pas_foto_url" 
                alt="Pas Foto" 
                class="w-full h-full object-cover" 
              />
              <span v-else class="text-3xl text-slate-300">📸</span>
            </div>
            <span class="text-[10px] text-slate-400 block">Pas Foto 3x4 Calon Siswa</span>
          </div>

          <!-- Rincian Siswa -->
          <div class="md:col-span-9 space-y-3 text-xs">
            <div class="grid grid-cols-2 gap-2 p-3 bg-slate-50 rounded-2xl">
              <div><span class="text-slate-400">Jenis Kelamin:</span> <strong class="text-slate-800">{{ selectedRecord.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</strong></div>
              <div><span class="text-slate-400">Usia Saat Daftar:</span> <strong class="text-emerald-700">{{ selectedRecord.usia_daftar || '-' }}</strong></div>
              <div><span class="text-slate-400">Tempat, Tgl Lahir:</span> <strong class="text-slate-800">{{ selectedRecord.tempat_lahir }}, {{ formatDate(selectedRecord.tanggal_lahir) }}</strong></div>
              <div><span class="text-slate-400">Anak Ke / Jml Saudara:</span> <strong class="text-slate-800">{{ selectedRecord.anak_ke }} dari {{ selectedRecord.jumlah_saudara }} bersaudara</strong></div>
              <div class="col-span-2"><span class="text-slate-400">Alamat:</span> <strong class="text-slate-800">{{ selectedRecord.alamat_lengkap }}</strong></div>
              <div><span class="text-slate-400">Tinggal Dengan:</span> <strong class="text-slate-800">{{ selectedRecord.tinggal_dengan }}</strong></div>
              <div><span class="text-slate-400">Asal Sekolah:</span> <strong class="text-slate-800">{{ selectedRecord.asal_sekolah || '-' }}</strong></div>
              <div v-if="selectedRecord.pindah_ke_kelas" class="col-span-2 p-2 bg-emerald-100/60 rounded-xl text-emerald-900">
                <strong>Siswa Pindahan:</strong> Pindah ke Kelas {{ selectedRecord.pindah_ke_kelas }} (Alasan: {{ selectedRecord.alasan_pindah || '-' }})
              </div>
            </div>

            <!-- Data Orang Tua -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="p-3 bg-slate-50 rounded-2xl space-y-1">
                <div class="font-bold text-slate-900 flex items-center justify-between">
                  <span>👨 Ayah: {{ selectedRecord.nama_ayah }}</span>
                </div>
                <div class="text-[11px] text-slate-600">Pekerjaan: {{ selectedRecord.pekerjaan_ayah || '-' }} ({{ selectedRecord.pendidikan_ayah || '-' }})</div>
                <div class="text-[11px] text-slate-600">Penghasilan: {{ selectedRecord.penghasilan_ayah || '-' }}</div>
                <div class="pt-1 flex items-center justify-between">
                  <span class="font-mono text-emerald-800 font-bold">{{ selectedRecord.no_hp_ayah }}</span>
                  <button
                    @click="chatWhatsApp(selectedRecord.no_hp_ayah, selectedRecord.nama_lengkap, selectedRecord.nomor_pendaftaran)"
                    type="button"
                    class="px-2 py-0.5 rounded-lg bg-emerald-600 text-white font-bold text-[10px]"
                  >
                    Chat WA
                  </button>
                </div>
              </div>

              <div class="p-3 bg-slate-50 rounded-2xl space-y-1">
                <div class="font-bold text-slate-900 flex items-center justify-between">
                  <span>🧕 Ibu: {{ selectedRecord.nama_ibu }}</span>
                </div>
                <div class="text-[11px] text-slate-600">Pekerjaan: {{ selectedRecord.pekerjaan_ibu || '-' }} ({{ selectedRecord.pendidikan_ibu || '-' }})</div>
                <div class="text-[11px] text-slate-600">Penghasilan: {{ selectedRecord.penghasilan_ibu || '-' }}</div>
                <div class="pt-1 flex items-center justify-between">
                  <span class="font-mono text-emerald-800 font-bold">{{ selectedRecord.no_hp_ibu }}</span>
                  <button
                    @click="chatWhatsApp(selectedRecord.no_hp_ibu, selectedRecord.nama_lengkap, selectedRecord.nomor_pendaftaran)"
                    type="button"
                    class="px-2 py-0.5 rounded-lg bg-emerald-600 text-white font-bold text-[10px]"
                  >
                    Chat WA
                  </button>
                </div>
              </div>
            </div>

            <!-- Lampiran Berkas -->
            <div class="p-3 bg-slate-50 rounded-2xl">
              <span class="font-bold text-slate-800 block mb-2">📁 Berkas Lampiran Pendaftar:</span>
              <div class="flex flex-wrap gap-2">
                <a
                  v-if="selectedRecord.akta_kelahiran_url"
                  :href="selectedRecord.akta_kelahiran_url"
                  target="_blank"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-300 text-emerald-800 font-bold text-xs hover:bg-emerald-50 transition"
                >
                  <span>📜</span> Lihat Akta Kelahiran
                </a>
                <span v-else class="text-[11px] text-slate-400 italic">Akta belum diunggah</span>

                <a
                  v-if="selectedRecord.kartu_keluarga_url"
                  :href="selectedRecord.kartu_keluarga_url"
                  target="_blank"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-300 text-emerald-800 font-bold text-xs hover:bg-emerald-50 transition"
                >
                  <span>📑</span> Lihat Kartu Keluarga
                </a>
                <span v-else class="text-[11px] text-slate-400 italic ml-2">KK belum diunggah</span>
              </div>
            </div>
          </div>
        </div>

        <!-- FORM UPDATE STATUS & CATATAN PANITIA -->
        <form @submit.prevent="saveStatus" class="pt-4 border-t border-slate-100 space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Status Pendaftaran *</label>
              <select
                v-model="statusForm.status"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm font-bold bg-white focus:ring-2 focus:ring-emerald-500"
              >
                <option value="menunggu">Menunggu Verifikasi</option>
                <option value="diterima">Diterima</option>
                <option value="cadangan">Cadangan</option>
                <option value="ditolak">Tidak Diterima</option>
              </select>
            </div>

            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Panitia / Observasi</label>
              <input
                v-model="statusForm.catatan_admin"
                type="text"
                placeholder="Misal: Sudah observasi, siap daftar ulang / Berkas akta menyusul"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:ring-2 focus:ring-emerald-500"
              />
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-2">
            <button
              @click="showDetailModal = false"
              type="button"
              class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer"
            >
              Tutup
            </button>

            <button
              type="submit"
              :disabled="updatingStatus"
              class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-md shadow-emerald-600/20 cursor-pointer disabled:opacity-50"
            >
              <span v-if="updatingStatus" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span>{{ updatingStatus ? 'Menyimpan...' : 'Simpan Status' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
