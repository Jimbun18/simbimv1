<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import api from '../../services/api'
import { getPhotoUrl, getInitials } from './types'

const props = defineProps<{
  selectedDate: string
}>()

const emit = defineEmits<{
  (e: 'notify', text: string, type?: 'success' | 'error' | 'warning'): void
}>()

const loading = ref(false)
const teacherViewMode = ref<'daily' | 'period'>('daily')

// Daily monitoring state
const teacherAttendances = ref<any[]>([])
const teacherStatusFilter = ref('')
const teacherCategoryFilter = ref('')
const teacherSearchQuery = ref('')
const selectedTeacherSelfie = ref<string | null>(null)
const selectedTeacherSelfieName = ref('')
const teacherAttendanceSummary = ref({
  total_record: 0,
  total_ptk: 0,
  total_teachers: 0,
  total_staff: 0,
  hadir: 0,
  hadir_tepat_waktu: 0,
  terlambat: 0,
  izin: 0,
  sakit: 0,
  dinas: 0,
  alpa: 0,
  belum_absen: 0,
  total_hadir_fisik: 0,
  persentase_kehadiran: 0,
  kepatuhan_rate: 0,
})

// Period recap state
const teacherRecapStartDate = ref('')
const teacherRecapEndDate = ref('')
const teacherRecapCategory = ref('')
const teacherRecapSearch = ref('')
const teacherRecapData = ref<any | null>(null)
const loadingTeacherRecap = ref(false)
const exportingTeacherRecapExcel = ref(false)
const selectedTeacherLogDetail = ref<any | null>(null)

const initTeacherRecapDates = () => {
  const now = new Date()
  const pad = (n: number) => String(n).padStart(2, '0')
  const start = new Date(now.getFullYear(), now.getMonth(), 1)
  const end = new Date(now.getFullYear(), now.getMonth() + 1, 0)
  teacherRecapStartDate.value = `${start.getFullYear()}-${pad(start.getMonth() + 1)}-${pad(start.getDate())}`
  teacherRecapEndDate.value = `${end.getFullYear()}-${pad(end.getMonth() + 1)}-${pad(end.getDate())}`
}

// 1. Fetch Teacher Attendance (Daily)
const fetchTeacherAttendances = async () => {
  loading.value = true
  try {
    const params: any = {
      tanggal: props.selectedDate,
      with_all_ptk: true,
    }
    if (teacherStatusFilter.value) {
      params.status = teacherStatusFilter.value
    }
    if (teacherCategoryFilter.value) {
      params.kategori = teacherCategoryFilter.value
    }
    if (teacherSearchQuery.value) {
      params.search = teacherSearchQuery.value
    }

    const res = await api.get('/attendance/teachers', { params })
    teacherAttendances.value = res.data.data?.data || res.data.data || []
    if (res.data.summary) {
      teacherAttendanceSummary.value = {
        ...teacherAttendanceSummary.value,
        ...res.data.summary,
      }
    }
  } catch (err: any) {
    console.error('Failed to fetch teacher attendance:', err)
    emit('notify', 'Gagal memuat presensi guru & tendik', 'error')
  } finally {
    loading.value = false
  }
}

// 2. Fetch Teacher Recap (Period)
const fetchTeacherRecap = async () => {
  loadingTeacherRecap.value = true
  try {
    const params: any = {
      start_date: teacherRecapStartDate.value,
      end_date: teacherRecapEndDate.value,
    }
    if (teacherRecapCategory.value) {
      params.kategori = teacherRecapCategory.value
    }
    if (teacherRecapSearch.value) {
      params.search = teacherRecapSearch.value
    }

    const res = await api.get('/attendance/teachers/recap', { params })
    teacherRecapData.value = res.data.data || null
  } catch (err: any) {
    console.error('Failed to fetch teacher recap:', err)
    emit('notify', 'Gagal memuat rekapitulasi presensi guru & tendik.', 'error')
  } finally {
    loadingTeacherRecap.value = false
  }
}

const setTeacherRecapPreset = (preset: 'this_month' | 'last_month' | 'last_30_days' | 'this_semester') => {
  const now = new Date()
  const pad = (n: number) => String(n).padStart(2, '0')

  if (preset === 'this_month') {
    const start = new Date(now.getFullYear(), now.getMonth(), 1)
    const end = new Date(now.getFullYear(), now.getMonth() + 1, 0)
    teacherRecapStartDate.value = `${start.getFullYear()}-${pad(start.getMonth() + 1)}-${pad(start.getDate())}`
    teacherRecapEndDate.value = `${end.getFullYear()}-${pad(end.getMonth() + 1)}-${pad(end.getDate())}`
  } else if (preset === 'last_month') {
    const start = new Date(now.getFullYear(), now.getMonth() - 1, 1)
    const end = new Date(now.getFullYear(), now.getMonth(), 0)
    teacherRecapStartDate.value = `${start.getFullYear()}-${pad(start.getMonth() + 1)}-${pad(start.getDate())}`
    teacherRecapEndDate.value = `${end.getFullYear()}-${pad(end.getMonth() + 1)}-${pad(end.getDate())}`
  } else if (preset === 'last_30_days') {
    const past = new Date()
    past.setDate(past.getDate() - 30)
    teacherRecapStartDate.value = `${past.getFullYear()}-${pad(past.getMonth() + 1)}-${pad(past.getDate())}`
    teacherRecapEndDate.value = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`
  } else if (preset === 'this_semester') {
    const m = now.getMonth() + 1
    const y = now.getFullYear()
    if (m >= 7) {
      teacherRecapStartDate.value = `${y}-07-01`
      teacherRecapEndDate.value = `${y}-12-31`
    } else {
      teacherRecapStartDate.value = `${y}-01-01`
      teacherRecapEndDate.value = `${y}-06-30`
    }
  }
  fetchTeacherRecap()
}

const exportTeacherRecapExcel = async () => {
  exportingTeacherRecapExcel.value = true
  try {
    const params: any = {
      start_date: teacherRecapStartDate.value,
      end_date: teacherRecapEndDate.value,
    }
    if (teacherRecapCategory.value) {
      params.kategori = teacherRecapCategory.value
    }
    if (teacherRecapSearch.value) {
      params.search = teacherRecapSearch.value
    }

    const res = await api.get('/attendance/teachers/recap/export', {
      params,
      responseType: 'blob',
    })

    const filename = `Rekap_Presensi_Guru_Tendik_${teacherRecapStartDate.value}_${teacherRecapEndDate.value}.xlsx`
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

    emit('notify', 'Berkas Excel rekap presensi guru & tendik berhasil diunduh.', 'success')
  } catch (err: any) {
    console.error('Failed to export teacher recap:', err)
    emit('notify', 'Terjadi kendala saat mengekspor data ke Excel.', 'error')
  } finally {
    exportingTeacherRecapExcel.value = false
  }
}

const approveLeave = async (id: number, status: 'disetujui' | 'ditolak') => {
  try {
    await api.post(`/attendance/teachers/${id}/approve-leave`, { status_approval: status })
    emit('notify', `Pengajuan cuti/izin berhasil di-${status}`)
    fetchTeacherAttendances()
  } catch (err: any) {
    emit('notify', err.response?.data?.message || 'Gagal mengubah status izin', 'error')
  }
}

watch(() => props.selectedDate, () => {
  if (teacherViewMode.value === 'daily') {
    fetchTeacherAttendances()
  }
})

onMounted(() => {
  initTeacherRecapDates()
  fetchTeacherAttendances()
})

defineExpose({
  fetchTeacherAttendances,
  fetchTeacherRecap
})
</script>

<template>
  <div class="space-y-6">
    <!-- Switcher Mode Tampilan: Monitoring Harian vs Rekap Bulanan/Rentang Waktu -->
    <div class="flex items-center justify-between flex-wrap gap-3 bg-white p-3 rounded-3xl border border-slate-100 shadow-sm">
      <div class="flex items-center gap-1.5 p-1 bg-slate-100/80 rounded-2xl">
        <button
          type="button"
          @click="teacherViewMode = 'daily'; fetchTeacherAttendances()"
          class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer"
          :class="teacherViewMode === 'daily' ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
        >
          <span>📅 Monitoring Harian</span>
        </button>
        <button
          type="button"
          @click="teacherViewMode = 'period'; if (!teacherRecapData) fetchTeacherRecap()"
          class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer"
          :class="teacherViewMode === 'period' ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
        >
          <span>📊 Rekap Bulanan & Rentang Waktu</span>
        </button>
      </div>

      <div class="text-xs text-slate-500 font-medium px-2">
        <span v-if="teacherViewMode === 'daily'">Pantau kehadiran real-time GPS hari ini</span>
        <span v-else>Akumulasi kehadiran, keterlambatan & dispensasi per periode / bulan</span>
      </div>
    </div>

    <!-- VIEW A: MONITORING HARIAN PTK (GPS CHECK-IN) -->
    <div v-if="teacherViewMode === 'daily'" class="space-y-6">
      <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase tracking-wider rounded-full">
                Monitoring Presensi Guru & Tendik
              </span>
              <span class="text-xs text-slate-400">Presensi GPS Mandiri Pendidik & Staf</span>
            </div>
            <h2 class="text-lg font-black text-slate-900 mt-1">
              Status Kehadiran Guru & Tenaga Kependidikan Hari Ini
            </h2>
            <p class="text-xs text-slate-500">
              Pantau kepatuhan check-in, ketepatan waktu hadir, dan koordinat GPS guru & staf secara real-time.
            </p>
          </div>
          <div class="flex items-center gap-2.5 shrink-0">
            <span
              class="px-3.5 py-2 rounded-2xl text-xs font-bold flex items-center gap-2 shadow-xs"
              :class="(teacherAttendanceSummary.kepatuhan_rate || 0) >= 80
                ? 'bg-emerald-50 text-emerald-800 border border-emerald-300'
                : 'bg-amber-50 text-amber-900 border border-amber-300'"
            >
              <span class="w-2.5 h-2.5 rounded-full" :class="(teacherAttendanceSummary.kepatuhan_rate || 0) >= 80 ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse'"></span>
              <span>
                <strong>{{ teacherAttendanceSummary.total_record || 0 }} dari {{ teacherAttendanceSummary.total_ptk || 10 }} PTK</strong> Sudah Absen ({{ teacherAttendanceSummary.kepatuhan_rate || 0 }}%)
              </span>
            </span>
          </div>
        </div>

        <!-- STAT CARDS RINGKASAN KEHADIRAN GURU & TENDIK -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
          <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total PTK</span>
            <span class="text-xl font-black text-slate-800 mt-1 block">{{ teacherAttendanceSummary.total_ptk || 10 }}</span>
            <span class="text-[10px] text-slate-400">Guru & Tendik</span>
          </div>
          <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
            <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">% Kehadiran</span>
            <span class="text-xl font-black text-emerald-700 mt-1 block">{{ teacherAttendanceSummary.persentase_kehadiran || 0 }}%</span>
            <span class="text-[10px] text-emerald-600/70">Tingkat Hadir</span>
          </div>
          <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
            <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Tepat Waktu</span>
            <span class="text-xl font-black text-emerald-700 mt-1 block">{{ teacherAttendanceSummary.hadir_tepat_waktu || 0 }}</span>
            <span class="text-[10px] text-slate-400">Pendidik / Staf</span>
          </div>
          <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
            <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider block">Terlambat</span>
            <span class="text-xl font-black text-amber-700 mt-1 block">{{ teacherAttendanceSummary.terlambat || 0 }}</span>
            <span class="text-[10px] text-slate-400">Lewat Toleransi</span>
          </div>
          <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
            <span class="text-[10px] font-bold text-sky-600 uppercase tracking-wider block">Izin / Sakit</span>
            <span class="text-xl font-black text-sky-700 mt-1 block">{{ (teacherAttendanceSummary.izin || 0) + (teacherAttendanceSummary.sakit || 0) + (teacherAttendanceSummary.dinas || 0) }}</span>
            <span class="text-[10px] text-slate-400">Keterangan Resmi</span>
          </div>
          <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
            <span class="text-[10px] font-bold text-rose-600 uppercase tracking-wider block">Belum Absen</span>
            <span class="text-xl font-black text-rose-700 mt-1 block">{{ teacherAttendanceSummary.belum_absen ?? teacherAttendanceSummary.alpa ?? 0 }}</span>
            <span class="text-[10px] text-slate-400">Belum Check-In</span>
          </div>
        </div>
      </div>

      <!-- FILTER CONTROLS TOOLBAR -->
      <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
        <div class="flex flex-wrap items-center gap-2.5 flex-1">
          <!-- Filter Kategori PTK -->
          <div class="w-44">
            <select
              v-model="teacherCategoryFilter"
              @change="fetchTeacherAttendances"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500"
            >
              <option value="">Semua Guru & Tendik</option>
              <option value="guru">Hanya Guru (Pendidik)</option>
              <option value="tendik">Hanya Tendik (Staf)</option>
            </select>
          </div>

          <!-- Filter Status Kehadiran -->
          <div class="w-48">
            <select
              v-model="teacherStatusFilter"
              @change="fetchTeacherAttendances"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500"
            >
              <option value="">Semua Status Kehadiran</option>
              <option value="hadir">Hadir (Tepat Waktu)</option>
              <option value="terlambat">Terlambat</option>
              <option value="izin">Izin</option>
              <option value="sakit">Sakit</option>
              <option value="dinas">Dinas Luar</option>
              <option value="belum_absen">Belum Absen</option>
            </select>
          </div>

          <!-- Pencarian Guru / Tendik Input -->
          <div class="relative flex-1 min-w-[200px]">
            <input
              v-model="teacherSearchQuery"
              @keyup.enter="fetchTeacherAttendances"
              type="text"
              placeholder="Cari nama guru/staf atau NIP lalu tekan Enter..."
              class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:ring-2 focus:ring-emerald-500"
            />
            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <button
            type="button"
            @click="fetchTeacherAttendances"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition flex items-center gap-1.5 cursor-pointer"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>Refresh</span>
          </button>
        </div>
      </div>

      <!-- TABEL DAFTAR KEHADIRAN PENDIDIK & TENAGA KEPENDIDIKAN -->
      <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden p-5 sm:p-7 space-y-4">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 pb-2 border-b border-slate-100">
          <div>
            <h3 class="font-bold text-slate-900 text-sm sm:text-base flex items-center gap-2">
              <span>Daftar Kehadiran Pendidik & Tenaga Kependidikan</span>
              <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-black rounded-full">
                {{ teacherAttendances.length }} PTK Ditampilkan
              </span>
            </h3>
            <p class="text-xs text-slate-500">Rekap status check-in / check-out GPS mandiri guru dan staf per tanggal terpilih.</p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs min-w-[850px]">
            <thead>
              <tr class="bg-slate-50/80 border-b border-slate-100 text-slate-500 font-bold uppercase text-[11px]">
                <th class="p-3.5 w-12 text-center">No</th>
                <th class="p-3.5 min-w-[220px]">Guru / Tendik</th>
                <th class="p-3.5 text-center w-32">Status</th>
                <th class="p-3.5 text-center w-24">Jam Masuk</th>
                <th class="p-3.5 text-center w-24">Jam Pulang</th>
                <th class="p-3.5 text-center w-28">Jarak GPS</th>
                <th class="p-3.5 text-center w-20">Foto</th>
                <th class="p-3.5 min-w-[180px]">Keterangan</th>
                <th class="p-3.5 text-right w-32">Aksi / Approval</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-if="loading">
                <td colspan="9" class="text-center py-12 text-slate-400">
                  <div class="flex flex-col items-center justify-center gap-2">
                    <svg class="animate-spin w-6 h-6 text-emerald-600" viewBox="0 0 24 24" fill="none">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Memuat data presensi guru & tendik...</span>
                  </div>
                </td>
              </tr>
              <tr v-else-if="teacherAttendances.length === 0">
                <td colspan="9" class="text-center py-12 text-slate-400">
                  <div class="flex flex-col items-center justify-center gap-1">
                    <span class="text-3xl">📭</span>
                    <span class="font-bold text-slate-600 mt-2">Tidak ada data presensi yang sesuai</span>
                    <span class="text-xs text-slate-400">Coba sesuaikan filter status atau kata kunci pencarian</span>
                  </div>
                </td>
              </tr>
              <tr v-for="(att, idx) in teacherAttendances" :key="att.id || `ptk-${idx}`" class="hover:bg-slate-50/70 transition-colors">
                <td class="p-3.5 text-center font-bold text-slate-400">{{ Number(idx) + 1 }}</td>
                <td class="p-3.5">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full overflow-hidden bg-slate-100 border border-slate-200 shrink-0 flex items-center justify-center font-bold text-[10px] text-slate-500 shadow-2xs">
                      <img
                        v-if="att.teacher?.foto_path || att.staff?.foto_path"
                        :src="getPhotoUrl(att.teacher?.foto_path || att.staff?.foto_path)"
                        class="w-full h-full object-cover"
                      />
                      <span v-else>{{ getInitials(att.teacher?.nama_lengkap || att.staff?.nama_lengkap || att.user?.nama || 'PTK') }}</span>
                    </div>
                    <div>
                      <span class="font-bold text-slate-800 block text-xs">
                        {{ att.teacher?.nama_lengkap || att.staff?.nama_lengkap || att.user?.nama || 'Guru/Staf' }}
                      </span>
                      <div class="flex items-center gap-2 text-[10px] text-slate-400 mt-0.5">
                        <span>{{ att.teacher?.nip ? `NIP: ${att.teacher.nip}` : (att.staff?.nik ? `NIK: ${att.staff.nik}` : 'NIP: -') }}</span>
                        <span>•</span>
                        <span class="px-1.5 py-0.2 rounded font-bold uppercase text-[9px]" :class="att.staff ? 'bg-purple-100 text-purple-700' : 'bg-emerald-100 text-emerald-700'">
                          {{ att.staff ? (att.staff.jabatan || 'Tendik') : (att.teacher?.jenis_ptk || 'Guru') }}
                        </span>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="p-3.5 text-center">
                  <span 
                    class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider inline-block"
                    :class="{
                      'bg-emerald-100 text-emerald-800 border border-emerald-200': att.status === 'hadir' && !att.is_terlambat,
                      'bg-amber-100 text-amber-900 border border-amber-300': att.status === 'terlambat' || att.is_terlambat,
                      'bg-sky-100 text-sky-800 border border-sky-200': att.status === 'izin',
                      'bg-purple-100 text-purple-800 border border-purple-200': att.status === 'sakit',
                      'bg-indigo-100 text-indigo-800 border border-indigo-200': att.status === 'dinas',
                      'bg-rose-100 text-rose-800 border border-rose-200': att.status === 'alpa',
                      'bg-slate-100 text-slate-500 border border-slate-200': att.status === 'belum_absen'
                    }"
                  >
                    {{ att.status === 'belum_absen' ? 'Belum Absen' : att.status }}
                    <span v-if="(att.status === 'terlambat' || att.is_terlambat) && att.menit_keterlambatan" class="text-[9px] font-bold block sm:inline">
                      (+{{ att.menit_keterlambatan }}m)
                    </span>
                  </span>
                </td>
                <td class="p-3.5 text-center font-mono text-slate-700 text-xs">
                  <span v-if="att.jam_masuk" class="font-bold text-slate-800">{{ att.jam_masuk }}</span>
                  <span v-else class="text-slate-300">-</span>
                </td>
                <td class="p-3.5 text-center font-mono text-slate-700 text-xs">
                  <span v-if="att.jam_keluar || att.jam_pulang" class="font-bold text-slate-800">{{ att.jam_keluar || att.jam_pulang }}</span>
                  <span v-else class="text-slate-300">-</span>
                </td>
                <td class="p-3.5 text-center font-mono text-xs">
                  <span v-if="att.jarak_masuk_m !== null && att.jarak_masuk_m !== undefined" class="px-2 py-0.5 rounded-md font-bold text-[11px]" :class="att.jarak_masuk_m <= 100 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'">
                    {{ att.jarak_masuk_m }} m
                  </span>
                  <span v-else class="text-slate-300">-</span>
                </td>
                <td class="p-3.5 text-center">
                  <button
                    v-if="att.foto_masuk"
                    type="button"
                    @click="selectedTeacherSelfie = getPhotoUrl(att.foto_masuk); selectedTeacherSelfieName = (att.teacher?.nama_lengkap || att.staff?.nama_lengkap || 'PTK')"
                    class="w-7 h-7 rounded-lg overflow-hidden border border-slate-200 hover:ring-2 hover:ring-emerald-500 transition mx-auto cursor-pointer"
                    title="Klik untuk melihat foto selfie presensi"
                  >
                    <img :src="getPhotoUrl(att.foto_masuk)" class="w-full h-full object-cover" alt="Selfie" />
                  </button>
                  <span v-else class="text-slate-300">-</span>
                </td>
                <td class="p-3.5 text-xs text-slate-600 max-w-xs truncate">
                  {{ att.keterangan || att.catatan || att.alasan_izin || '-' }}
                </td>
                <td class="p-3.5 text-right">
                  <div v-if="(att.status === 'izin' || att.status === 'sakit') && att.id" class="flex justify-end gap-1.5">
                    <button 
                      type="button"
                      @click="approveLeave(att.id, 'disetujui')"
                      class="bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] px-2.5 py-1 rounded-lg font-bold shadow-xs cursor-pointer"
                    >
                      Setujui
                    </button>
                    <button 
                      type="button"
                      @click="approveLeave(att.id, 'ditolak')"
                      class="bg-rose-600 hover:bg-rose-700 text-white text-[10px] px-2.5 py-1 rounded-lg font-bold shadow-xs cursor-pointer"
                    >
                      Tolak
                    </button>
                  </div>
                  <span v-else-if="att.status === 'belum_absen'" class="text-[11px] text-slate-400 font-medium">
                    Belum Check-In
                  </span>
                  <span v-else class="text-[11px] text-emerald-600 font-bold flex items-center justify-end gap-1">
                    <span>✓</span>
                    <span>Terverifikasi</span>
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- VIEW B: REKAP BULANAN & RENTANG WAKTU PTK -->
    <div v-else-if="teacherViewMode === 'period'" class="space-y-6">
      <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 flex-1">
            <div>
              <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Kategori PTK</label>
              <select
                v-model="teacherRecapCategory"
                @change="fetchTeacherRecap"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500"
              >
                <option value="">Semua Guru & Tendik</option>
                <option value="guru">Hanya Guru (Pendidik)</option>
                <option value="tendik">Hanya Tendik (Staf)</option>
              </select>
            </div>

            <div class="sm:col-span-2">
              <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Cari Guru / Tendik</label>
              <div class="relative">
                <input
                  v-model="teacherRecapSearch"
                  @keyup.enter="fetchTeacherRecap"
                  type="text"
                  placeholder="Ketik nama lengkap atau NIP lalu tekan Enter..."
                  class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:ring-2 focus:ring-emerald-500"
                />
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
            </div>
          </div>

          <div class="flex items-end gap-2.5 shrink-0 pt-2 lg:pt-0">
            <button
              type="button"
              @click="fetchTeacherRecap"
              :disabled="loadingTeacherRecap"
              class="px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold text-xs transition shadow-xs flex items-center gap-1.5 cursor-pointer"
            >
              <svg v-if="loadingTeacherRecap" class="animate-spin w-3.5 h-3.5" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span>Terapkan</span>
            </button>

            <button
              type="button"
              @click="exportTeacherRecapExcel"
              :disabled="exportingTeacherRecapExcel"
              class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-xl font-bold text-xs transition shadow-xs flex items-center gap-2 cursor-pointer"
              title="Download file Excel Rekap Presensi PTK"
            >
              <svg v-if="exportingTeacherRecapExcel" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <svg v-else class="w-4 h-4 text-emerald-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span>{{ exportingTeacherRecapExcel ? 'Mengekspor...' : 'Export Excel (.xlsx)' }}</span>
            </button>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
          <div class="flex flex-wrap items-center gap-2">
            <span class="text-slate-500 font-medium">Rentang Waktu:</span>
            <input
              type="date"
              v-model="teacherRecapStartDate"
              @change="fetchTeacherRecap"
              class="border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs font-semibold bg-slate-50 text-slate-800 focus:ring-2 focus:ring-emerald-500"
            />
            <span class="text-slate-400">s/d</span>
            <input
              type="date"
              v-model="teacherRecapEndDate"
              @change="fetchTeacherRecap"
              class="border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs font-semibold bg-slate-50 text-slate-800 focus:ring-2 focus:ring-emerald-500"
            />
          </div>

          <div class="flex items-center gap-1.5 flex-wrap">
            <span class="text-slate-400 font-medium text-[11px] mr-1">Preset:</span>
            <button
              type="button"
              @click="setTeacherRecapPreset('this_month')"
              class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-semibold transition cursor-pointer"
            >
              Bulan Ini
            </button>
            <button
              type="button"
              @click="setTeacherRecapPreset('last_month')"
              class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-semibold transition cursor-pointer"
            >
              Bulan Lalu
            </button>
            <button
              type="button"
              @click="setTeacherRecapPreset('last_30_days')"
              class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-semibold transition cursor-pointer"
            >
              30 Hari Terakhir
            </button>
            <button
              type="button"
              @click="setTeacherRecapPreset('this_semester')"
              class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-semibold transition cursor-pointer"
            >
              Semester Ini
            </button>
          </div>
        </div>
      </div>

      <!-- STAT CARDS AKUMULASI PERIODE -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Hari Kerja Efektif</span>
          <span class="text-xl font-black text-slate-800 mt-1 block">{{ teacherRecapData?.summary?.total_hari_kerja || 0 }}</span>
          <span class="text-[10px] text-slate-400">Hari dalam Periode</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
          <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Rata-Rata Kehadiran</span>
          <span class="text-xl font-black text-emerald-700 mt-1 block">{{ teacherRecapData?.summary?.rata_rata_kehadiran || 0 }}%</span>
          <span class="text-[10px] text-emerald-600/70">Kedisiplinan PTK</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
          <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Tepat Waktu</span>
          <span class="text-xl font-black text-emerald-700 mt-1 block">{{ teacherRecapData?.summary?.total_hadir_tepat_waktu || 0 }}</span>
          <span class="text-[10px] text-slate-400">Total Sesi Hadir</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
          <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider block">Total Terlambat</span>
          <span class="text-xl font-black text-amber-700 mt-1 block">{{ teacherRecapData?.summary?.total_terlambat || 0 }}</span>
          <span class="text-[10px] text-amber-600/70">{{ teacherRecapData?.summary?.total_menit_terlambat || 0 }} Menit Akumulasi</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
          <span class="text-[10px] font-bold text-sky-600 uppercase tracking-wider block">Izin / Cuti</span>
          <span class="text-xl font-black text-sky-700 mt-1 block">{{ teacherRecapData?.summary?.total_izin || 0 }}</span>
          <span class="text-[10px] text-slate-400">Dispensasi Resmi</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs">
          <span class="text-[10px] font-bold text-purple-600 uppercase tracking-wider block">Sakit & Dinas</span>
          <span class="text-xl font-black text-purple-700 mt-1 block">{{ (teacherRecapData?.summary?.total_sakit || 0) + (teacherRecapData?.summary?.total_dinas || 0) }}</span>
          <span class="text-[10px] text-slate-400">Surat Dokter & Tugas</span>
        </div>
      </div>

      <!-- TABEL LEDGER REKAPITULASI PRESENSI PTK -->
      <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden p-5 sm:p-7 space-y-4">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 pb-2 border-b border-slate-100">
          <div>
            <h3 class="font-bold text-slate-900 text-sm sm:text-base flex items-center gap-2">
              <span>Buku Rekap Presensi Pendidik & Tenaga Kependidikan</span>
              <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-black rounded-full">
                {{ teacherRecapData?.ptk_list?.length || 0 }} PTK Terdaftar
              </span>
            </h3>
            <p class="text-xs text-slate-500">
              Periode: <strong class="text-slate-700">{{ teacherRecapStartDate }} s/d {{ teacherRecapEndDate }}</strong> • 
              Total Hari Kerja: <strong class="text-slate-700">{{ teacherRecapData?.period?.total_hari_kerja || 0 }} Hari</strong>
            </p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs min-w-[950px]">
            <thead>
              <tr class="bg-slate-50/80 border-b border-slate-100 text-slate-500 font-bold uppercase text-[11px]">
                <th class="p-3.5 w-12 text-center">No</th>
                <th class="p-3.5 min-w-[220px]">Guru / Tendik</th>
                <th class="p-3.5 text-center w-24 text-emerald-700 bg-emerald-50/40">Hadir</th>
                <th class="p-3.5 text-center w-28 text-amber-700 bg-amber-50/40">Terlambat</th>
                <th class="p-3.5 text-center w-16 text-sky-700 bg-sky-50/40">Izin</th>
                <th class="p-3.5 text-center w-16 text-purple-700 bg-purple-50/40">Sakit</th>
                <th class="p-3.5 text-center w-16 text-indigo-700 bg-indigo-50/40">Dinas</th>
                <th class="p-3.5 text-center w-16 text-rose-700 bg-rose-50/40">Alpa</th>
                <th class="p-3.5 text-center w-24">Total Hadir</th>
                <th class="p-3.5 text-center min-w-[140px]">% Kehadiran</th>
                <th class="p-3.5 text-center w-28">Predikat</th>
                <th class="p-3.5 text-center w-28">Rincian Log</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-if="loadingTeacherRecap">
                <td colspan="12" class="text-center py-12 text-slate-400">
                  <div class="flex flex-col items-center justify-center gap-2">
                    <svg class="animate-spin w-6 h-6 text-emerald-600" viewBox="0 0 24 24" fill="none">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Memuat rekapitulasi presensi guru & tendik...</span>
                  </div>
                </td>
              </tr>
              <tr v-else-if="!teacherRecapData?.ptk_list || teacherRecapData.ptk_list.length === 0">
                <td colspan="12" class="text-center py-12 text-slate-400">
                  <div class="flex flex-col items-center justify-center gap-1">
                    <span class="text-3xl">📭</span>
                    <span class="font-bold text-slate-600 mt-2">Belum ada data presensi pada rentang waktu ini</span>
                    <span class="text-xs text-slate-400">Silakan sesuaikan pilihan tanggal atau filter</span>
                  </div>
                </td>
              </tr>
              <tr
                v-for="(ptk, idx) in (teacherRecapData?.ptk_list || [])"
                :key="`recap-${ptk.ptk_type}-${ptk.ptk_id}`"
                class="hover:bg-slate-50/70 transition-colors"
              >
                <td class="p-3.5 text-center font-bold text-slate-400">{{ Number(idx) + 1 }}</td>
                <td class="p-3.5">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full overflow-hidden bg-slate-100 border border-slate-200 shrink-0 flex items-center justify-center font-bold text-[10px] text-slate-500 shadow-2xs">
                      <img v-if="ptk.foto_path" :src="getPhotoUrl(ptk.foto_path)" class="w-full h-full object-cover" />
                      <span v-else>{{ getInitials(ptk.nama_lengkap) }}</span>
                    </div>
                    <div>
                      <span class="font-bold text-slate-800 block text-xs">{{ ptk.nama_lengkap }}</span>
                      <div class="flex items-center gap-2 text-[10px] text-slate-400 mt-0.5">
                        <span>NIP: {{ ptk.nip || ptk.nik || '-' }}</span>
                        <span>•</span>
                        <span class="px-1.5 py-0.2 rounded font-bold uppercase text-[9px]" :class="ptk.ptk_type === 'tendik' ? 'bg-purple-100 text-purple-700' : 'bg-emerald-100 text-emerald-700'">
                          {{ ptk.peran }}
                        </span>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="p-3.5 text-center bg-emerald-50/30">
                  <span class="px-2 py-0.5 rounded-full font-bold text-xs bg-emerald-100 text-emerald-800">
                    {{ ptk.hadir_tepat_waktu }}
                  </span>
                </td>
                <td class="p-3.5 text-center bg-amber-50/30">
                  <span class="px-2 py-0.5 rounded-full font-bold text-xs" :class="ptk.terlambat > 0 ? 'bg-amber-100 text-amber-900 font-black' : 'text-slate-400'">
                    {{ ptk.terlambat }}
                  </span>
                  <span v-if="ptk.menit_terlambat > 0" class="text-[9px] text-amber-700 font-semibold block">
                    {{ ptk.menit_terlambat }}m
                  </span>
                </td>
                <td class="p-3.5 text-center bg-sky-50/30">
                  <span class="px-2 py-0.5 rounded-full font-bold text-xs" :class="ptk.izin > 0 ? 'bg-sky-100 text-sky-800 font-bold' : 'text-slate-400'">
                    {{ ptk.izin }}
                  </span>
                </td>
                <td class="p-3.5 text-center bg-purple-50/30">
                  <span class="px-2 py-0.5 rounded-full font-bold text-xs" :class="ptk.sakit > 0 ? 'bg-purple-100 text-purple-800 font-bold' : 'text-slate-400'">
                    {{ ptk.sakit }}
                  </span>
                </td>
                <td class="p-3.5 text-center bg-indigo-50/30">
                  <span class="px-2 py-0.5 rounded-full font-bold text-xs" :class="ptk.dinas > 0 ? 'bg-indigo-100 text-indigo-800 font-bold' : 'text-slate-400'">
                    {{ ptk.dinas }}
                  </span>
                </td>
                <td class="p-3.5 text-center bg-rose-50/30">
                  <span class="px-2 py-0.5 rounded-full font-bold text-xs" :class="ptk.alpa > 0 ? 'bg-rose-100 text-rose-800 font-black' : 'text-slate-400'">
                    {{ ptk.alpa }}
                  </span>
                </td>
                <td class="p-3.5 text-center font-bold text-slate-800">
                  {{ ptk.total_hadir_fisik }} Hari
                </td>
                <td class="p-3.5">
                  <div class="flex flex-col gap-1 items-center">
                    <span class="text-xs font-black" :class="ptk.persentase_kehadiran >= 85 ? 'text-emerald-700' : ptk.persentase_kehadiran >= 75 ? 'text-amber-700' : 'text-rose-700'">
                      {{ ptk.persentase_kehadiran }}%
                    </span>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                      <div
                        class="h-full rounded-full transition-all duration-300"
                        :class="ptk.persentase_kehadiran >= 85 ? 'bg-emerald-500' : ptk.persentase_kehadiran >= 75 ? 'bg-amber-500' : 'bg-rose-500'"
                        :style="{ width: `${Math.min(ptk.persentase_kehadiran, 100)}%` }"
                      ></div>
                    </div>
                  </div>
                </td>
                <td class="p-3.5 text-center">
                  <span
                    class="px-2 py-0.5 text-[10px] font-bold rounded-full inline-block"
                    :class="{
                      'bg-emerald-100 text-emerald-800': ptk.persentase_kehadiran >= 95,
                      'bg-teal-100 text-teal-800': ptk.persentase_kehadiran >= 85 && ptk.persentase_kehadiran < 95,
                      'bg-amber-100 text-amber-800': ptk.persentase_kehadiran >= 75 && ptk.persentase_kehadiran < 85,
                      'bg-rose-100 text-rose-800 font-black': ptk.persentase_kehadiran < 75,
                    }"
                  >
                    {{ ptk.predikat }}
                  </span>
                </td>
                <td class="p-3.5 text-center">
                  <button
                    type="button"
                    @click="selectedTeacherLogDetail = ptk"
                    class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-emerald-100 text-slate-700 hover:text-emerald-800 font-bold text-[11px] transition flex items-center justify-center gap-1 w-full cursor-pointer"
                    title="Lihat riwayat log kehadiran harian guru ini"
                  >
                    <span>📋</span>
                    <span>Rincian Log</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL RINCIAN LOG PRESENSI HARIAN PTK -->
    <div
      v-if="selectedTeacherLogDetail"
      class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4 backdrop-blur-xs"
      @click="selectedTeacherLogDetail = null"
    >
      <div class="max-w-2xl w-full bg-white rounded-3xl overflow-hidden p-6 shadow-2xl space-y-5" @click.stop>
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 shrink-0 flex items-center justify-center font-bold text-sm text-slate-500 shadow-2xs">
              <img v-if="selectedTeacherLogDetail.foto_path" :src="getPhotoUrl(selectedTeacherLogDetail.foto_path)" class="w-full h-full object-cover" />
              <span v-else>{{ getInitials(selectedTeacherLogDetail.nama_lengkap) }}</span>
            </div>
            <div>
              <h4 class="text-base font-black text-slate-900">{{ selectedTeacherLogDetail.nama_lengkap }}</h4>
              <p class="text-xs text-slate-400">
                NIP: <span class="font-semibold text-slate-700">{{ selectedTeacherLogDetail.nip || selectedTeacherLogDetail.nik || '-' }}</span> •
                Peran: <span class="font-semibold text-slate-700">{{ selectedTeacherLogDetail.peran }}</span>
              </p>
            </div>
          </div>
          <button @click="selectedTeacherLogDetail = null" class="text-slate-400 hover:text-slate-700 font-bold text-2xl p-1 cursor-pointer">&times;</button>
        </div>

        <div class="grid grid-cols-4 gap-2 text-center text-xs">
          <div class="p-2.5 bg-emerald-50 rounded-xl border border-emerald-100">
            <span class="text-[10px] font-bold text-emerald-600 block">Hadir Tepat Waktu</span>
            <span class="text-base font-black text-emerald-800">{{ selectedTeacherLogDetail.hadir_tepat_waktu }}</span>
          </div>
          <div class="p-2.5 bg-amber-50 rounded-xl border border-amber-100">
            <span class="text-[10px] font-bold text-amber-600 block">Terlambat</span>
            <span class="text-base font-black text-amber-800">{{ selectedTeacherLogDetail.terlambat }}</span>
          </div>
          <div class="p-2.5 bg-sky-50 rounded-xl border border-sky-100">
            <span class="text-[10px] font-bold text-sky-600 block">Izin / Sakit</span>
            <span class="text-base font-black text-sky-800">{{ selectedTeacherLogDetail.izin + selectedTeacherLogDetail.sakit }}</span>
          </div>
          <div class="p-2.5 bg-purple-50 rounded-xl border border-purple-100">
            <span class="text-[10px] font-bold text-purple-600 block">% Kehadiran</span>
            <span class="text-base font-black text-purple-800">{{ selectedTeacherLogDetail.persentase_kehadiran }}%</span>
          </div>
        </div>

        <div>
          <span class="text-xs font-bold text-slate-700 block mb-2">Riwayat Log Kehadiran pada Periode Terpilih:</span>
          <div class="max-h-60 overflow-y-auto border border-slate-100 rounded-2xl">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 text-[10px] font-bold uppercase text-slate-400 sticky top-0">
                <tr>
                  <th class="p-2.5 text-center">Tanggal</th>
                  <th class="p-2.5 text-center">Status</th>
                  <th class="p-2.5 text-center">Jam Masuk</th>
                  <th class="p-2.5 text-center">Jam Pulang</th>
                  <th class="p-2.5 text-center">Jarak GPS</th>
                  <th class="p-2.5">Keterangan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-if="!selectedTeacherLogDetail.logs || selectedTeacherLogDetail.logs.length === 0">
                  <td colspan="6" class="p-6 text-center text-slate-400">Belum ada catatan presensi dalam rentang waktu ini.</td>
                </tr>
                <tr v-for="log in selectedTeacherLogDetail.logs" :key="`log-${log.id}`" class="hover:bg-slate-50">
                  <td class="p-2.5 text-center font-bold text-slate-700">{{ log.tanggal }}</td>
                  <td class="p-2.5 text-center">
                    <span
                      class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase"
                      :class="{
                        'bg-emerald-100 text-emerald-800': log.status === 'hadir' && !log.is_terlambat,
                        'bg-amber-100 text-amber-900': log.status === 'terlambat' || log.is_terlambat,
                        'bg-sky-100 text-sky-800': log.status === 'izin',
                        'bg-purple-100 text-purple-800': log.status === 'sakit',
                        'bg-indigo-100 text-indigo-800': log.status === 'dinas',
                        'bg-rose-100 text-rose-800': log.status === 'alpa',
                      }"
                    >
                      {{ log.status }}
                      <span v-if="log.is_terlambat && log.menit_keterlambatan" class="text-[8px]">
                        (+{{ log.menit_keterlambatan }}m)
                      </span>
                    </span>
                  </td>
                  <td class="p-2.5 text-center font-mono text-slate-600">{{ log.jam_masuk || '-' }}</td>
                  <td class="p-2.5 text-center font-mono text-slate-600">{{ log.jam_keluar || '-' }}</td>
                  <td class="p-2.5 text-center font-mono text-xs">
                    <span v-if="log.jarak_masuk_m !== null" :class="log.jarak_masuk_m <= 100 ? 'text-emerald-600 font-bold' : 'text-rose-600 font-bold'">
                      {{ log.jarak_masuk_m }} m
                    </span>
                    <span v-else class="text-slate-300">-</span>
                  </td>
                  <td class="p-2.5 text-slate-500 max-w-xs truncate">{{ log.keterangan || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button
            @click="selectedTeacherLogDetail = null"
            class="px-5 py-2.5 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition cursor-pointer"
          >
            Tutup Rincian
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL FOTO SELFIE PRESENSI -->
    <div
      v-if="selectedTeacherSelfie"
      class="fixed inset-0 bg-black/85 flex items-center justify-center z-50 p-4 backdrop-blur-xs"
      @click="selectedTeacherSelfie = null"
    >
      <div class="max-w-md w-full bg-slate-900 rounded-3xl overflow-hidden p-4 shadow-2xl space-y-3" @click.stop>
        <div class="flex items-center justify-between text-white border-b border-slate-800 pb-2">
          <div>
            <h4 class="text-sm font-bold text-white">Foto Masuk Presensi</h4>
            <p class="text-xs text-slate-400">{{ selectedTeacherSelfieName }}</p>
          </div>
          <button @click="selectedTeacherSelfie = null" class="text-white hover:text-slate-300 font-bold text-xl p-1 cursor-pointer">&times;</button>
        </div>
        <div class="flex items-center justify-center max-h-[70vh] overflow-hidden rounded-2xl bg-black">
          <img :src="selectedTeacherSelfie" alt="Selfie Presensi" class="w-full h-auto max-h-[70vh] object-contain rounded-xl" />
        </div>
        <div class="flex justify-end pt-1">
          <button @click="selectedTeacherSelfie = null" class="px-4 py-1.5 rounded-xl bg-slate-800 text-white font-bold hover:bg-slate-700 text-xs cursor-pointer">
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
