<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

interface SummaryStats {
  total_record: number
  hadir: number
  terlambat: number
  izin: number
  sakit: number
  dinas: number
  alpa: number
}

const auth = useAuthStore()
const router = useRouter()

const loading = ref(false)
const historyList = ref<any[]>([])
const summary = ref<SummaryStats>({
  total_record: 0,
  hadir: 0,
  terlambat: 0,
  izin: 0,
  sakit: 0,
  dinas: 0,
  alpa: 0,
})

const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 25,
})

// Filters
const now = new Date()
const selectedMonth = ref(String(now.getMonth() + 1))
const selectedYear = ref(String(now.getFullYear()))
const selectedStatus = ref('')
const selectedPhotoModal = ref<{ url: string; title: string; subtitle: string } | null>(null)

// Daftar Bulan Indonesia
const months = [
  { value: '1', label: 'Januari' },
  { value: '2', label: 'Februari' },
  { value: '3', label: 'Maret' },
  { value: '4', label: 'April' },
  { value: '5', label: 'Mei' },
  { value: '6', label: 'Juni' },
  { value: '7', label: 'Juli' },
  { value: '8', label: 'Agustus' },
  { value: '9', label: 'September' },
  { value: '10', label: 'Oktober' },
  { value: '11', label: 'November' },
  { value: '12', label: 'Desember' },
]

const years = computed(() => {
  const currentYear = new Date().getFullYear()
  return [currentYear - 1, currentYear, currentYear + 1].map(String)
})

const attendancePercentage = computed(() => {
  const total = summary.value.total_record
  if (!total) return 0
  const effectivePresent = summary.value.hadir + summary.value.terlambat + summary.value.dinas
  return Math.round((effectivePresent / total) * 100)
})

const getPhotoUrl = (path?: string | null) => {
  if (!path) return ''
  return path.startsWith('http') ? path : `/storage/${path}`
}

const formatDateDisplay = (dateStr?: string) => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    return d.toLocaleDateString('id-ID', {
      weekday: 'long',
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    })
  } catch {
    return dateStr
  }
}

const formatTimeDisplay = (timeStr?: string) => {
  if (!timeStr) return '-'
  return timeStr.substring(0, 5) + ' WIB'
}

const setPresetMonth = (preset: 'this_month' | 'last_month') => {
  const d = new Date()
  if (preset === 'this_month') {
    selectedMonth.value = String(d.getMonth() + 1)
    selectedYear.value = String(d.getFullYear())
  } else {
    d.setMonth(d.getMonth() - 1)
    selectedMonth.value = String(d.getMonth() + 1)
    selectedYear.value = String(d.getFullYear())
  }
  fetchHistory(1)
}

const fetchHistory = async (page = 1) => {
  loading.value = true
  try {
    const params: any = {
      bulan: selectedMonth.value,
      tahun: selectedYear.value,
      page,
      per_page: pagination.value.per_page,
    }

    if (selectedStatus.value) {
      params.status = selectedStatus.value
    }

    const res = await api.get('/attendance/teachers', { params })
    if (res.data.success) {
      historyList.value = res.data.data || []
      if (res.data.summary) {
        summary.value = res.data.summary
      }
      if (res.data.meta) {
        pagination.value = {
          current_page: res.data.meta.current_page || page,
          last_page: res.data.meta.last_page || 1,
          total: res.data.meta.total || 0,
          per_page: res.data.meta.per_page || 25,
        }
      }
    }
  } catch (err) {
    console.error('Gagal mengambil riwayat presensi:', err)
  } finally {
    loading.value = false
  }
}

const openPhoto = (url: string, title: string, subtitle: string) => {
  selectedPhotoModal.value = { url, title, subtitle }
}

onMounted(() => {
  fetchHistory(1)
})
</script>

<template>
  <div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Header Page -->
    <div class="bg-white p-6 sm:p-7 rounded-3xl border border-slate-100 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-5">
      <div class="space-y-1">
        <div class="flex items-center gap-2">
          <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[11px] font-bold rounded-full uppercase tracking-wider">
            Portal Guru & Pendidik
          </span>
          <span class="text-slate-400 text-xs">•</span>
          <span class="text-xs text-slate-500 font-medium">Buku Riwayat Kehadiran Pribadi</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
          Riwayat Presensi Saya
        </h1>
        <p class="text-xs sm:text-sm text-slate-500">
          Catatan lengkap kehadiran mandiri, jam masuk/pulang, foto selfie, verifikasi GPS, serta status izin/cuti Anda.
        </p>
      </div>

      <!-- Action Button -->
      <div class="flex flex-wrap items-center gap-3">
        <button
          @click="router.push('/presensi')"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-2xl shadow-md shadow-emerald-600/20 transition active:scale-95 cursor-pointer"
        >
          <span>📸</span>
          <span>Presensi Hari Ini (GPS)</span>
        </button>
        <button
          @click="fetchHistory(1)"
          class="p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl transition cursor-pointer"
          title="Muat Ulang Data"
        >
          <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </button>
      </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
      <!-- Total Rekaman -->
      <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Hari Kerja</span>
        <span class="text-2xl font-black text-slate-800 mt-1 block">{{ summary.total_record }}</span>
        <span class="text-[10px] text-slate-400">Periode terpilih</span>
      </div>

      <!-- Hadir Tepat Waktu -->
      <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs border-l-4 border-l-emerald-500">
        <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Hadir Tepat Waktu</span>
        <span class="text-2xl font-black text-emerald-700 mt-1 block">{{ summary.hadir }}</span>
        <span class="text-[10px] text-emerald-600/70 font-medium">Hari</span>
      </div>

      <!-- Terlambat -->
      <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs border-l-4 border-l-amber-500">
        <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider block">Terlambat</span>
        <span class="text-2xl font-black text-amber-700 mt-1 block">{{ summary.terlambat }}</span>
        <span class="text-[10px] text-amber-600/70 font-medium">Hari</span>
      </div>

      <!-- Izin / Cuti -->
      <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs border-l-4 border-l-sky-500">
        <span class="text-[10px] font-bold text-sky-600 uppercase tracking-wider block">Izin / Cuti</span>
        <span class="text-2xl font-black text-sky-700 mt-1 block">{{ summary.izin }}</span>
        <span class="text-[10px] text-sky-600/70 font-medium">Hari</span>
      </div>

      <!-- Sakit -->
      <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs border-l-4 border-l-purple-500">
        <span class="text-[10px] font-bold text-purple-600 uppercase tracking-wider block">Sakit</span>
        <span class="text-2xl font-black text-purple-700 mt-1 block">{{ summary.sakit }}</span>
        <span class="text-[10px] text-purple-600/70 font-medium">Hari</span>
      </div>

      <!-- Persentase Kehadiran -->
      <div class="bg-gradient-to-br from-emerald-700 to-teal-800 text-white p-4 rounded-2xl shadow-xs">
        <span class="text-[10px] font-bold text-emerald-200 uppercase tracking-wider block">Tingkat Hadir</span>
        <span class="text-2xl font-black text-white mt-1 block">{{ attendancePercentage }}%</span>
        <div class="w-full bg-emerald-950/40 rounded-full h-1.5 mt-1.5 overflow-hidden">
          <div
            class="bg-emerald-300 h-full rounded-full transition-all duration-300"
            :style="{ width: `${attendancePercentage}%` }"
          ></div>
        </div>
      </div>
    </div>

    <!-- Filter Controls Toolbar -->
    <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-xs space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <!-- Pilihan Periode Bulan & Tahun -->
        <div class="flex flex-wrap items-center gap-3">
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Bulan</label>
            <select
              v-model="selectedMonth"
              @change="fetchHistory(1)"
              class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
            >
              <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>
          </div>

          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Tahun</label>
            <select
              v-model="selectedYear"
              @change="fetchHistory(1)"
              class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
            >
              <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
          </div>

          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Status Kehadiran</label>
            <select
              v-model="selectedStatus"
              @change="fetchHistory(1)"
              class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
            >
              <option value="">Semua Status</option>
              <option value="hadir">Hadir</option>
              <option value="terlambat">Terlambat</option>
              <option value="izin">Izin</option>
              <option value="sakit">Sakit</option>
              <option value="dinas">Dinas Luar</option>
              <option value="alpa">Alpa</option>
            </select>
          </div>
        </div>

        <!-- Preset Buttons -->
        <div class="flex items-center gap-2 self-start sm:self-end">
          <button
            type="button"
            @click="setPresetMonth('this_month')"
            class="px-3 py-2 text-xs rounded-xl border border-slate-200 hover:bg-slate-100 font-semibold text-slate-600 transition cursor-pointer"
          >
            Bulan Ini
          </button>
          <button
            type="button"
            @click="setPresetMonth('last_month')"
            class="px-3 py-2 text-xs rounded-xl border border-slate-200 hover:bg-slate-100 font-semibold text-slate-600 transition cursor-pointer"
          >
            Bulan Lalu
          </button>
        </div>
      </div>
    </div>

    <!-- Table Riwayat Presensi -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-xs overflow-hidden">
      <div class="p-5 border-b border-slate-100 flex items-center justify-between">
        <div>
          <h3 class="font-bold text-slate-900 text-sm">Daftar Kehadiran Harian</h3>
          <p class="text-xs text-slate-400 mt-0.5">
            Periode: {{ months.find(m => m.value === selectedMonth)?.label }} {{ selectedYear }}
          </p>
        </div>
        <span class="text-xs text-slate-500 font-medium">
          Total: <strong class="text-slate-800">{{ pagination.total }}</strong> log tercatat
        </span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
              <th class="py-3 px-4">Hari & Tanggal</th>
              <th class="py-3 px-3 text-center">Masuk</th>
              <th class="py-3 px-3 text-center">Pulang</th>
              <th class="py-3 px-3 text-center">Status</th>
              <th class="py-3 px-3 text-center">Radius GPS</th>
              <th class="py-3 px-3 text-center">Foto Masuk</th>
              <th class="py-3 px-3 text-center">Foto Pulang</th>
              <th class="py-3 px-4">Keterangan / Catatan</th>
              <th class="py-3 px-3 text-center">Status Verifikasi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <!-- Loading -->
            <tr v-if="loading">
              <td colspan="9" class="py-12 text-center text-slate-400">
                <div class="flex items-center justify-center gap-2">
                  <div class="w-4 h-4 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                  <span>Memuat data riwayat kehadiran...</span>
                </div>
              </td>
            </tr>

            <!-- Empty -->
            <tr v-else-if="historyList.length === 0">
              <td colspan="9" class="py-12 text-center text-slate-400">
                <div class="flex flex-col items-center justify-center gap-2">
                  <span class="text-3xl">📭</span>
                  <span class="font-bold text-slate-700">Belum Ada Catatan Presensi</span>
                  <span class="text-xs text-slate-400">Tidak ada data kehadiran yang tercatat pada filter bulan ini.</span>
                </div>
              </td>
            </tr>

            <!-- Data Rows -->
            <tr
              v-for="att in historyList"
              :key="att.id"
              class="hover:bg-slate-50/80 transition"
            >
              <!-- Tanggal -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <span class="font-bold text-slate-800 block">{{ formatDateDisplay(att.tanggal) }}</span>
                <span class="text-[10px] text-slate-400 font-mono">{{ att.tanggal }}</span>
              </td>

              <!-- Jam Masuk -->
              <td class="py-3.5 px-3 text-center font-mono font-bold whitespace-nowrap">
                <span v-if="att.jam_masuk" :class="att.is_terlambat ? 'text-amber-700' : 'text-emerald-700'">
                  {{ formatTimeDisplay(att.jam_masuk) }}
                </span>
                <span v-else class="text-slate-300">-</span>
                <span v-if="att.is_terlambat && att.menit_keterlambatan" class="block text-[10px] font-semibold text-amber-600">
                  +{{ att.menit_keterlambatan }} mnt
                </span>
              </td>

              <!-- Jam Pulang -->
              <td class="py-3.5 px-3 text-center font-mono font-bold text-slate-700 whitespace-nowrap">
                <span v-if="att.jam_keluar">{{ formatTimeDisplay(att.jam_keluar) }}</span>
                <span v-else class="text-slate-300">-</span>
              </td>

              <!-- Status Badge -->
              <td class="py-3.5 px-3 text-center whitespace-nowrap">
                <span
                  class="px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase tracking-wider inline-block"
                  :class="{
                    'bg-emerald-100 text-emerald-800 border border-emerald-200': att.status === 'hadir',
                    'bg-amber-100 text-amber-800 border border-amber-200': att.status === 'terlambat' || att.is_terlambat,
                    'bg-sky-100 text-sky-800 border border-sky-200': att.status === 'izin',
                    'bg-purple-100 text-purple-800 border border-purple-200': att.status === 'sakit',
                    'bg-indigo-100 text-indigo-800 border border-indigo-200': att.status === 'dinas',
                    'bg-rose-100 text-rose-800 border border-rose-200 font-black': att.status === 'alpa',
                  }"
                >
                  {{ att.status }}
                </span>
              </td>

              <!-- Radius GPS -->
              <td class="py-3.5 px-3 text-center font-mono text-[11px] text-slate-500 whitespace-nowrap">
                <span v-if="att.jarak_masuk_m !== null && att.jarak_masuk_m !== undefined" class="flex items-center justify-center gap-1">
                  <span>📍</span>
                  <span>{{ att.jarak_masuk_m }}m</span>
                </span>
                <span v-else class="text-slate-300">-</span>
              </td>

              <!-- Foto Masuk -->
              <td class="py-3.5 px-3 text-center">
                <button
                  v-if="att.foto_masuk"
                  @click="openPhoto(getPhotoUrl(att.foto_masuk), 'Foto Presensi Masuk', `${formatDateDisplay(att.tanggal)} • ${formatTimeDisplay(att.jam_masuk)}`)"
                  class="w-8 h-8 rounded-xl overflow-hidden border border-slate-200 hover:border-emerald-500 transition inline-block cursor-pointer shadow-2xs hover:scale-105"
                  title="Lihat Foto Masuk"
                >
                  <img :src="getPhotoUrl(att.foto_masuk)" class="w-full h-full object-cover" />
                </button>
                <span v-else class="text-slate-300">-</span>
              </td>

              <!-- Foto Pulang -->
              <td class="py-3.5 px-3 text-center">
                <button
                  v-if="att.foto_pulang"
                  @click="openPhoto(getPhotoUrl(att.foto_pulang), 'Foto Presensi Pulang', `${formatDateDisplay(att.tanggal)} • ${formatTimeDisplay(att.jam_keluar)}`)"
                  class="w-8 h-8 rounded-xl overflow-hidden border border-slate-200 hover:border-emerald-500 transition inline-block cursor-pointer shadow-2xs hover:scale-105"
                  title="Lihat Foto Pulang"
                >
                  <img :src="getPhotoUrl(att.foto_pulang)" class="w-full h-full object-cover" />
                </button>
                <span v-else class="text-slate-300">-</span>
              </td>

              <!-- Keterangan -->
              <td class="py-3.5 px-4 text-slate-600 max-w-xs">
                <span class="line-clamp-2">{{ att.keterangan || '-' }}</span>
                <span v-if="att.alasan_koreksi" class="text-[10px] text-amber-600 font-semibold block mt-0.5">
                  Koreksi: {{ att.alasan_koreksi }}
                </span>
              </td>

              <!-- Status Verifikasi -->
              <td class="py-3.5 px-3 text-center whitespace-nowrap">
                <span
                  v-if="att.status === 'izin' || att.status === 'sakit'"
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                  :class="{
                    'bg-emerald-50 text-emerald-700 border border-emerald-200': att.status_persetujuan === 'disetujui',
                    'bg-amber-50 text-amber-700 border border-amber-200': att.status_persetujuan === 'menunggu' || !att.status_persetujuan,
                    'bg-rose-50 text-rose-700 border border-rose-200': att.status_persetujuan === 'ditolak',
                  }"
                >
                  {{ att.status_persetujuan ? att.status_persetujuan.toUpperCase() : 'MENUNGGU' }}
                </span>
                <span v-else class="text-[11px] text-slate-400 font-medium">
                  Tervalidasi
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="pagination.last_page > 1"
        class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500"
      >
        <span>Halaman {{ pagination.current_page }} dari {{ pagination.last_page }}</span>
        <div class="flex items-center gap-1.5">
          <button
            :disabled="pagination.current_page <= 1"
            @click="fetchHistory(pagination.current_page - 1)"
            class="px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 disabled:opacity-40 font-semibold cursor-pointer"
          >
            Sebelumnya
          </button>
          <button
            :disabled="pagination.current_page >= pagination.last_page"
            @click="fetchHistory(pagination.current_page + 1)"
            class="px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 disabled:opacity-40 font-semibold cursor-pointer"
          >
            Selanjutnya
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Viewer Foto Selfie -->
    <div
      v-if="selectedPhotoModal"
      class="fixed inset-0 bg-black/85 flex items-center justify-center z-50 p-4 backdrop-blur-xs"
      @click="selectedPhotoModal = null"
    >
      <div class="max-w-xl w-full bg-slate-900 rounded-3xl overflow-hidden p-4 shadow-2xl space-y-4" @click.stop>
        <div class="flex items-center justify-between text-white border-b border-slate-800 pb-3">
          <div>
            <h4 class="text-sm font-bold text-white">{{ selectedPhotoModal.title }}</h4>
            <p class="text-xs text-slate-400 mt-0.5">{{ selectedPhotoModal.subtitle }}</p>
          </div>
          <button @click="selectedPhotoModal = null" class="text-slate-400 hover:text-white font-bold text-2xl p-1 cursor-pointer">
            &times;
          </button>
        </div>

        <div class="flex items-center justify-center max-h-[70vh] overflow-hidden rounded-2xl bg-black">
          <img :src="selectedPhotoModal.url" alt="Preview Selfie" class="w-full h-auto max-h-[70vh] object-contain rounded-xl" />
        </div>

        <div class="flex justify-end pt-1">
          <button
            @click="selectedPhotoModal = null"
            class="px-5 py-2 rounded-xl bg-slate-800 text-white text-xs font-bold hover:bg-slate-700 transition cursor-pointer"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
