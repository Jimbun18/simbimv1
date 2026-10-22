<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import api from '../../services/api'
import { useAuthStore } from '../../stores/auth'
import { getPhotoUrl, getInitials, type SummaryStats, type StudentPagination } from './types'

const props = defineProps<{
  classes: any[]
  selectedDate: string
}>()

const emit = defineEmits<{
  (e: 'notify', text: string, type?: 'success' | 'error' | 'warning'): void
  (e: 'switch-tab', tab: string, classId?: string): void
}>()

const auth = useAuthStore()

const loading = ref(false)
const selectedClassFilter = ref('')
const selectedStatusFilter = ref('')
const studentSearchQuery = ref('')

const studentAttendanceRecords = ref<any[]>([])
const studentAttendanceSummary = ref<SummaryStats>({
  total_record: 0,
  hadir: 0,
  izin: 0,
  sakit: 0,
  alpa: 0,
  persentase_kehadiran: 0,
})

const studentPagination = ref<StudentPagination>({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 25,
})

const homeroomMonitoringData = ref<any | null>(null)
const loadingHomeroomMonitoring = ref(false)

const isMyHomeroom = (classId: number | string) => {
  return auth.homeroomClasses.some(h => String(h.id) === String(classId))
}

const selectMyHomeroom = () => {
  if (auth.primaryHomeroomClass) {
    selectedClassFilter.value = String(auth.primaryHomeroomClass.id)
    fetchStudentAttendanceRecap(1)
  }
}

// 1. Fetch Student Attendance Recap
const fetchStudentAttendanceRecap = async (page = 1) => {
  loading.value = true
  try {
    const params: any = {
      tanggal: props.selectedDate,
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
    emit('notify', err.response?.data?.message || 'Gagal memuat rekap presensi siswa', 'error')
  } finally {
    loading.value = false
  }
}

// Fetch Homeroom Attendance Monitoring Status (Admin / KS)
const fetchHomeroomMonitoring = async () => {
  if (!auth.isAdmin) return
  loadingHomeroomMonitoring.value = true
  try {
    const res = await api.get('/attendance/students/homeroom-monitoring', {
      params: { tanggal: props.selectedDate }
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

watch(() => props.selectedDate, () => {
  fetchStudentAttendanceRecap(1)
  if (auth.isAdmin) {
    fetchHomeroomMonitoring()
  }
})

onMounted(() => {
  fetchStudentAttendanceRecap(1)
  if (auth.isAdmin) {
    fetchHomeroomMonitoring()
  }
})

defineExpose({
  fetchStudentAttendanceRecap,
  fetchHomeroomMonitoring
})
</script>

<template>
  <div class="space-y-6">
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
              @click="emit('switch-tab', 'manual_input', String(c.class_id))"
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
          class="px-3 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer"
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
            <option v-for="c in props.classes" :key="c.id" :value="String(c.id)">
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
          type="button"
          @click="emit('switch-tab', 'manual_input')"
          class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold transition shadow-xs flex items-center gap-1.5 cursor-pointer"
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
            type="button"
            :disabled="studentPagination.current_page <= 1"
            @click="fetchStudentAttendanceRecap(studentPagination.current_page - 1)"
            class="px-3 py-1 bg-white border border-slate-200 rounded-lg hover:bg-slate-100 disabled:opacity-40 cursor-pointer"
          >
            Sebelumnya
          </button>
          <button
            type="button"
            :disabled="studentPagination.current_page >= studentPagination.last_page"
            @click="fetchStudentAttendanceRecap(studentPagination.current_page + 1)"
            class="px-3 py-1 bg-white border border-slate-200 rounded-lg hover:bg-slate-100 disabled:opacity-40 cursor-pointer"
          >
            Berikutnya
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
