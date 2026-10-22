<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import api from '../../services/api'
import { useAuthStore } from '../../stores/auth'
import { getPhotoUrl, getInitials } from './types'

const props = defineProps<{
  classes: any[]
  subjects: any[]
  initialClassId?: string
}>()

const emit = defineEmits<{
  (e: 'notify', text: string, type?: 'success' | 'error' | 'warning'): void
  (e: 'switch-tab', tab: string, classId?: string): void
}>()

const auth = useAuthStore()

const ledgerClassId = ref(props.initialClassId || '')
const ledgerStartDate = ref('')
const ledgerEndDate = ref('')
const ledgerSubjectId = ref('')
const ledgerSearchQuery = ref('')
const classRecapData = ref<any | null>(null)
const loadingClassRecap = ref(false)
const exportingRecapExcel = ref(false)
const selectedStudentDetail = ref<any | null>(null)

const isMyHomeroom = (classId: number | string) => {
  return auth.homeroomClasses.some(h => String(h.id) === String(classId))
}

const selectMyHomeroom = () => {
  if (auth.primaryHomeroomClass) {
    ledgerClassId.value = String(auth.primaryHomeroomClass.id)
    fetchClassRecap()
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
    emit('notify', err.response?.data?.message || 'Gagal memuat rekap presensi kelas', 'error')
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

    emit('notify', 'Berkas Excel rekap presensi berhasil diunduh.', 'success')
  } catch (err: any) {
    console.error('Gagal mengekspor berkas Excel:', err)
    emit('notify', 'Terjadi kendala saat mengekspor data ke Excel.', 'error')
  } finally {
    exportingRecapExcel.value = false
  }
}

const openStudentDetail = (stu: any) => {
  selectedStudentDetail.value = stu
}

watch(() => props.initialClassId, (newId) => {
  if (newId && newId !== ledgerClassId.value) {
    ledgerClassId.value = newId
    fetchClassRecap()
  }
})

watch(() => props.classes, (newClasses) => {
  if (!ledgerClassId.value && newClasses && newClasses.length > 0) {
    if (auth.isWaliKelas && auth.primaryHomeroomClass) {
      ledgerClassId.value = String(auth.primaryHomeroomClass.id)
    } else {
      ledgerClassId.value = String(newClasses[0].id)
    }
    fetchClassRecap()
  }
}, { deep: true })

onMounted(() => {
  initLedgerDates()
  if (!ledgerClassId.value && props.classes.length > 0) {
    if (auth.isWaliKelas && auth.primaryHomeroomClass) {
      ledgerClassId.value = String(auth.primaryHomeroomClass.id)
    } else {
      ledgerClassId.value = String(props.classes[0].id)
    }
  }
  if (ledgerClassId.value) {
    fetchClassRecap()
  }
})

defineExpose({
  fetchClassRecap,
  setLedgerClassId: (id: string) => {
    ledgerClassId.value = id
    fetchClassRecap()
  }
})
</script>

<template>
  <div class="space-y-6">
    <!-- Shortcut Cepat Wali Kelas -->
    <div v-if="auth.isWaliKelas" class="flex flex-wrap items-center justify-between gap-3 bg-emerald-50/80 border border-emerald-200/80 p-3 rounded-2xl">
      <div class="flex items-center gap-2">
        <span class="text-xs font-bold text-emerald-800">Mode Cepat Wali Kelas:</span>
        <button
          type="button"
          @click="selectMyHomeroom"
          :class="ledgerClassId === String(auth.primaryHomeroomClass?.id) ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-white text-emerald-800 hover:bg-emerald-100 font-medium border border-emerald-200'"
          class="px-3 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer"
        >
          <span>⭐ Fokus Kelas Binaan: {{ auth.primaryHomeroomClass?.nama }}</span>
        </button>
      </div>
      <span class="text-[11px] text-emerald-700 font-medium">Buku rekap presensi otomatis terisi untuk kelas binaan Anda.</span>
    </div>

    <!-- Filter Controls Toolbar -->
    <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm space-y-4">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <!-- Filter Dropdowns & Inputs -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 flex-1">
          <!-- Pilihan Kelas -->
          <div>
            <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Rombel / Kelas</label>
            <select
              v-model="ledgerClassId"
              @change="fetchClassRecap"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500"
            >
              <option value="" disabled>Pilih Kelas</option>
              <option v-for="c in props.classes" :key="c.id" :value="String(c.id)">
                {{ c.nama }} {{ isMyHomeroom(c.id) ? '⭐ (Binaan Saya)' : '' }}
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
            class="px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold text-xs transition shadow-xs flex items-center gap-1.5 cursor-pointer"
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
            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-xl font-bold text-xs transition shadow-xs flex items-center gap-2 cursor-pointer"
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
            type="button"
            @click="setLedgerDatePreset('this_month')"
            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-semibold transition cursor-pointer"
          >
            Bulan Ini
          </button>
          <button
            type="button"
            @click="setLedgerDatePreset('last_month')"
            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-semibold transition cursor-pointer"
          >
            Bulan Lalu
          </button>
          <button
            type="button"
            @click="setLedgerDatePreset('this_semester')"
            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-semibold transition cursor-pointer"
          >
            Semester Ini
          </button>
          <button
            type="button"
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
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Pertemuan</span>
          <div class="flex items-baseline gap-1.5">
            <span class="text-2xl font-black text-slate-900">{{ classRecapData?.total_meetings || 0 }}</span>
            <span class="text-xs text-slate-500 font-semibold">Sesi</span>
          </div>
          <span class="text-[10px] text-purple-600 font-bold block mt-0.5">Semua Pertemuan Kelas</span>
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

    <!-- Tabel Ledger Rekapitulasi Presensi Siswa -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
      <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <span>Buku Rekap Presensi Siswa</span>
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
              <th class="py-3 px-4 text-center min-w-[140px]">% Kehadiran</th>
              <th class="py-3 px-3 text-center w-28">Predikat</th>
              <th class="py-3 px-4 text-center w-28">Rincian Mapel</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-if="loadingClassRecap">
              <td colspan="10" class="py-12 text-center text-slate-400">
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
              <td colspan="10" class="py-12 text-center text-slate-400">
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
                  type="button"
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

    <!-- MODAL RINCIAN PRESENSI SISWA PER MATA PELAJARAN (SIAP RAPOR) -->
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
