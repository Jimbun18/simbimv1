<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { showConfirm } from '../../services/swal'
import {
  type ScheduleItem,
  type MergedScheduleItem,
  type Teacher,
  getIndonesianDayName,
  isTodaySchedule,
  getCardTheme,
  getScheduleStatusInfo,
  getScheduleTimeStatus,
  mergeConsecutiveSchedules
} from './types'

const props = defineProps<{
  schedules: ScheduleItem[]
  teachersList: Teacher[]
  todayHomeroomStatus: any
  todayHoliday?: any
  activeDate: string
  loading: boolean
}>()

const emit = defineEmits<{
  (e: 'open-kbm', schedule: MergedScheduleItem): void
  (e: 'open-homeroom', cls?: any): void
  (e: 'refresh'): void
  (e: 'notify', title: string, message: string, type?: 'success' | 'error' | 'warning'): void
}>()

const auth = useAuthStore()

const selectedHari = ref<string>('')
const selectedTeacherId = ref<string>('')
const searchQuery = ref<string>('')
const sortByTodayFirst = ref<boolean>(true)

const currentTodayName = computed(() => {
  return getIndonesianDayName(props.activeDate)
})

const allMergedSchedules = computed<MergedScheduleItem[]>(() => {
  return mergeConsecutiveSchedules(props.schedules)
})

const filteredSchedules = computed<MergedScheduleItem[]>(() => {
  let list = allMergedSchedules.value

  // Day filter
  if (selectedHari.value) {
    list = list.filter((s) => s.hari.toLowerCase() === selectedHari.value.toLowerCase())
  }

  // Teacher filter (for Admin)
  if (auth.isAdmin && selectedTeacherId.value) {
    if (selectedTeacherId.value === 'me' && auth.teacherProfile) {
      list = list.filter((s) => s.teacher_id === auth.teacherProfile.id)
    } else if (selectedTeacherId.value !== 'me') {
      list = list.filter((s) => String(s.teacher_id) === String(selectedTeacherId.value))
    }
  }

  // Search filter
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter((s) => {
      const subjectName = s.subject?.nama?.toLowerCase() || ''
      const subjectCode = s.subject?.kode?.toLowerCase() || ''
      const className = s.school_class?.nama?.toLowerCase() || ''
      const teacherName = s.teacher?.nama_lengkap?.toLowerCase() || ''
      const roomName = s.room?.nama?.toLowerCase() || ''
      return (
        subjectName.includes(q) ||
        subjectCode.includes(q) ||
        className.includes(q) ||
        teacherName.includes(q) ||
        roomName.includes(q)
      )
    })
  }

  // Sort today first if no day selected
  if (!selectedHari.value && sortByTodayFirst.value) {
    list.sort((a, b) => {
      const aToday = isTodaySchedule(a, props.activeDate) ? 0 : 1
      const bToday = isTodaySchedule(b, props.activeDate) ? 0 : 1
      return aToday - bToday
    })
  }

  return list
})

const todayMergedSchedules = computed(() => {
  return allMergedSchedules.value.filter(
    (s) => s.hari.toLowerCase() === currentTodayName.value
  )
})

const todaySchedulesCount = computed(() => todayMergedSchedules.value.length)
const completedTodayCount = computed(() => todayMergedSchedules.value.filter(s => s.today_evidence_count > 0).length)

// Reactive live clock
const currentTime = ref(new Date())
let clockTimer: any = null

onMounted(() => {
  clockTimer = setInterval(() => {
    currentTime.value = new Date()
  }, 10000)
})

onUnmounted(() => {
  if (clockTimer) clearInterval(clockTimer)
})

const handleCardClick = async (sch: MergedScheduleItem, forceAdmin: boolean = false) => {
  // 1. Guru sudah pernah mengunggah bukti: selalu izinkan untuk melihat bukti
  if ((sch as any).today_evidence_count > 0) {
    emit('open-kbm', sch)
    return
  }

  // 2. Jika hari ini adalah hari libur (Minggu atau Libur Kalender)
  if (props.todayHoliday?.is_holiday) {
    if (auth.isAdmin && forceAdmin) {
      emit('open-kbm', sch)
      return
    }
    if (auth.isAdmin) {
      const confirmed = await showConfirm(
        'Akses Khusus Administrator',
        `Hari ini tercatat sebagai hari libur (${props.todayHoliday.reason || 'Libur Sekolah'}).\n\nSebagai Administrator, apakah Anda ingin tetap membuka form bukti KBM ini secara khusus?`,
        'Ya, Buka Form',
        'question'
      )
      if (confirmed) {
        emit('open-kbm', sch)
      }
      return
    }
    emit(
      'notify',
      'Hari Libur Sekolah',
      `Hari ini libur (${props.todayHoliday.reason || 'Hari Libur'}). Kegiatan KBM dan pengunggahan bukti dinonaktifkan.`,
      'warning'
    )
    return
  }

  const info = getScheduleStatusInfo(sch, props.activeDate, currentTime.value)

  // 3. Jika jadwal sedang berlangsung: izinkan upload
  if (info.canUpload) {
    emit('open-kbm', sch)
    return
  }

  // 4. Jika admin mengklik override tombol admin
  if (auth.isAdmin && forceAdmin) {
    emit('open-kbm', sch)
    return
  }

  // 5. Jika admin mengklik kartu yang terkunci, tanyakan konfirmasi override
  if (auth.isAdmin) {
    const confirmed = await showConfirm(
      'Akses Khusus Administrator',
      `Jadwal KBM ini saat ini di luar jam pelajaran aktif (${sch.jam_mulai_efektif} - ${sch.jam_selesai_efektif} WIB).\n\nSebagai Administrator, apakah Anda ingin membuka form bukti KBM ini secara khusus?`,
      'Ya, Buka Form',
      'question'
    )
    if (confirmed) {
      emit('open-kbm', sch)
    }
    return
  }

  // 6. Bukan admin dan di luar jam pelajaran: BLOKIR DENGAN NOTIFIKASI
  emit(
    'notify',
    info.status === 'belum_mulai' ? 'Jam Pelajaran Belum Dimulai' : 'Jam Pelajaran Telah Berakhir',
    info.disabledReason || 'Pengunggahan bukti KBM hanya dapat diakses saat jam pelajaran sedang berlangsung sesuai jadwal.',
    'warning'
  )
}
</script>

<template>
  <div class="space-y-6">
    <!-- Banner Hari Libur Sekolah / Akhir Pekan -->
    <div
      v-if="todayHoliday?.is_holiday"
      class="bg-amber-500/10 border-2 border-amber-500/40 rounded-3xl p-5 sm:p-6 text-amber-950 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-xs"
    >
      <div class="flex items-start sm:items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-amber-100 border border-amber-300 text-amber-900 flex items-center justify-center text-2xl shrink-0">
          🏖️
        </div>
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-200 text-amber-900 border border-amber-300">
              {{ todayHoliday?.holiday_info?.is_sunday ? 'Hari Minggu' : 'Kalender Libur' }}
            </span>
            <span class="text-xs text-amber-800 font-bold">
              KBM & Presensi Tidak Aktif
            </span>
          </div>
          <h3 class="text-base font-bold text-amber-950 mt-1">
            {{ todayHoliday?.reason || 'Hari ini adalah hari libur sekolah.' }}
          </h3>
          <p class="text-xs text-amber-800/90 mt-0.5">
            Sesuai kalender akademik, kegiatan KBM diliburkan sehingga pengunggahan jurnal dan bukti KBM dinonaktifkan.
          </p>
        </div>
      </div>
      <router-link
        to="/classes-schedules?tab=calendar"
        class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs transition shrink-0 shadow-xs"
      >
        <span>Lihat Kalender Akademik</span>
        <span>&rarr;</span>
      </router-link>
    </div>

    <!-- Wali Kelas Special Banner & Shortcut to Daily Homeroom Attendance -->
    <div
      v-if="auth.isWaliKelas"
      class="bg-gradient-to-r from-emerald-900 to-slate-900 text-white rounded-3xl p-5 sm:p-6 shadow-md border border-emerald-500/30 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
    >
      <div class="flex items-start sm:items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-400/40 text-emerald-300 flex items-center justify-center text-2xl shrink-0">
          ⭐
        </div>
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-400/20 text-emerald-300 border border-emerald-400/30">
              Wali Kelas Rombel
            </span>
            <span class="text-xs text-slate-300 font-semibold">
              Kelas: <strong class="text-white font-bold">{{ auth.primaryHomeroomClass?.nama }}</strong>
            </span>

            <!-- Status Validasi Presensi Hari Ini -->
            <span
              v-if="todayHomeroomStatus?.already_recorded"
              class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-500 text-slate-950 flex items-center gap-1 shadow-xs"
            >
              <span>✓ Sudah Diabsen Hari Ini (Terkunci)</span>
            </span>
            <span
              v-else
              class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-amber-400 text-slate-950 flex items-center gap-1 shadow-xs animate-pulse"
            >
              <span>⚠️ Belum Mengabsen Hari Ini</span>
            </span>
          </div>
          <h2 class="text-base font-bold text-white mt-1">Presensi Harian Siswa (Wali Kelas)</h2>
          <p v-if="todayHomeroomStatus?.already_recorded" class="text-xs text-emerald-200 mt-0.5">
            Presensi hari ini tervalidasi: <strong>{{ todayHomeroomStatus.summary?.hadir }} Hadir</strong>, 
            <strong>{{ todayHomeroomStatus.summary?.izin }} Izin</strong>, 
            <strong>{{ todayHomeroomStatus.summary?.sakit }} Sakit</strong>, 
            <strong>{{ todayHomeroomStatus.summary?.alpa }} Alpa</strong>.
            Tercatat oleh {{ todayHomeroomStatus.recorded_by || 'Wali Kelas' }} • Otomatis masuk ke monitoring admin & kepala sekolah.
            <span class="block text-emerald-300 font-semibold mt-0.5">🔒 Pengabsenan telah selesai dan terkunci (hanya 1x sehari).</span>
          </p>
          <p v-else class="text-xs text-slate-300 mt-0.5">
            Presensi kehadiran siswa rombel dilakukan sehari sekali oleh Anda sebagai Wali Kelas. Silakan lakukan pengabsenan hari ini.
          </p>
        </div>
      </div>
      <div class="shrink-0 flex items-center gap-2">
        <button
          type="button"
          @click="emit('open-homeroom')"
          :class="todayHomeroomStatus?.already_recorded 
            ? 'bg-slate-800 hover:bg-slate-700 text-emerald-300 border border-emerald-500/40 shadow-md' 
            : 'bg-emerald-500 hover:bg-emerald-400 text-slate-950 shadow-lg shadow-emerald-500/20'"
          class="px-5 py-2.5 font-bold rounded-xl text-xs transition flex items-center gap-2 cursor-pointer"
        >
          <span>{{ todayHomeroomStatus?.already_recorded ? '👁️' : '📋' }}</span>
          <span>{{ todayHomeroomStatus?.already_recorded ? 'Lihat Rekap Presensi (Terkunci)' : 'Catat Presensi Siswa Sekarang' }}</span>
        </button>
      </div>
    </div>

    <!-- Top Hero Header -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-100 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-5">
      <div class="space-y-1.5">
        <div class="flex items-center gap-2">
          <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[11px] font-bold rounded-full uppercase tracking-wider">
            Portal KBM Guru
          </span>
          <span v-if="auth.isGuru && !auth.isAdmin" class="text-xs text-slate-500 font-medium">
            Jadwal Mengajar Anda
          </span>
          <span v-else-if="auth.isAdmin" class="text-xs text-purple-700 font-medium bg-purple-50 px-2 py-0.5 rounded-full border border-purple-200">
            Mode Administrator & Kepala Sekolah
          </span>
        </div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">
          Jadwal Mengajar & Jurnal KBM
        </h1>
        <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
          Tampilan kartu jadwal pelajaran untuk guru pengampu. Klik tombol <strong>Unggah Bukti KBM & Jurnal</strong> untuk melampirkan dokumentasi kegiatan kelas dan materi ajar tatap muka.
        </p>
      </div>

      <!-- Quick Metrics Bar -->
      <div class="flex items-center gap-3 shrink-0">
        <div class="bg-slate-50 border border-slate-200/60 rounded-2xl p-3.5 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold text-lg">
            {{ todaySchedulesCount }}
          </div>
          <div>
            <div class="text-[11px] text-slate-400 font-medium uppercase tracking-wider">Jadwal Hari Ini</div>
            <div class="text-xs font-bold text-slate-800">
              <span class="text-emerald-600">{{ completedTodayCount }}</span> dari {{ todaySchedulesCount }} Bukti Terunggah
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter & Toolbar Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
      <!-- Filter Hari (Pill Selector) -->
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 scrollbar-none text-xs">
        <button
          type="button"
          @click="selectedHari = ''"
          :class="selectedHari === '' ? 'bg-slate-900 text-white font-bold shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          class="px-3.5 py-2 rounded-xl transition whitespace-nowrap cursor-pointer"
        >
          Semua Hari ({{ allMergedSchedules.length }})
        </button>
        <button
          type="button"
          @click="selectedHari = currentTodayName"
          :class="selectedHari === currentTodayName ? 'bg-emerald-500 text-white font-bold shadow-md shadow-emerald-500/20' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 font-semibold'"
          class="px-3.5 py-2 rounded-xl transition whitespace-nowrap flex items-center gap-1.5 cursor-pointer"
        >
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          Hari Ini ({{ currentTodayName.toUpperCase() }})
        </button>
        <button
          v-for="hari in ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu']"
          :key="hari"
          type="button"
          @click="selectedHari = hari"
          :class="selectedHari === hari ? 'bg-slate-900 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          class="px-3.5 py-2 rounded-xl transition whitespace-nowrap capitalize font-medium cursor-pointer"
        >
          {{ hari }}
        </button>
      </div>

      <!-- Controls Right: Teacher Selector (for Admin) & Search Box -->
      <div class="flex flex-wrap items-center gap-2.5">
        <!-- Admin Teacher Filter -->
        <div v-if="auth.isAdmin" class="min-w-[200px]">
          <select
            v-model="selectedTeacherId"
            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500"
          >
            <option value="">Semua Guru Pengampu</option>
            <option value="me" v-if="auth.teacherProfile">Jadwal Mengajar Saya ({{ auth.userName }})</option>
            <option v-for="t in teachersList" :key="t.id" :value="String(t.id)">
              {{ t.nama_lengkap }}
            </option>
          </select>
        </div>

        <!-- Search Input -->
        <div class="relative flex-1 sm:w-64">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari mapel, kelas, atau ruang..."
            class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 transition"
          />
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>

        <button
          type="button"
          @click="emit('refresh')"
          :disabled="props.loading"
          class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition cursor-pointer"
          title="Segarkan Jadwal"
        >
          <svg class="w-4 h-4" :class="{ 'animate-spin': props.loading }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Indikator Makna Warna & Status Waktu KBM -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
      <div class="flex flex-wrap items-center gap-2 sm:gap-3">
        <span class="font-bold text-slate-800 flex items-center gap-1.5">
          <span>🎨</span>
          <span>Status KBM:</span>
        </span>
        <div class="flex items-center gap-1.5 bg-emerald-50 text-emerald-900 border border-emerald-200 px-2.5 py-1 rounded-xl font-bold">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-xs inline-block animate-pulse"></span>
          <span>SEDANG BERLANGSUNG = Upload Aktif Sesuai Jam</span>
        </div>
        <div class="flex items-center gap-1.5 bg-amber-50 text-amber-900 border border-amber-200 px-2.5 py-1 rounded-xl font-bold">
          <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shadow-xs inline-block"></span>
          <span>BELUM MULAI / SELESAI = Tombol Terkunci 🔒</span>
        </div>
        <div class="flex items-center gap-1.5 bg-red-50 text-red-900 border border-red-200 px-2.5 py-1 rounded-xl font-bold">
          <span class="w-2.5 h-2.5 rounded-full bg-[#b3261e] shadow-xs inline-block"></span>
          <span>BUKAN HARI INI</span>
        </div>
      </div>

      <div class="flex items-center gap-3 text-[11px] text-slate-500">
        <button
          v-if="!selectedHari"
          @click="sortByTodayFirst = !sortByTodayFirst"
          type="button"
          class="hover:text-emerald-700 font-semibold underline transition cursor-pointer"
        >
          {{ sortByTodayFirst ? 'Urutan: Hari Ini Teratas' : 'Urutan: Sesuai Urutan Hari' }}
        </button>
        <span class="hidden sm:inline text-slate-300">•</span>
        <span class="flex items-center gap-1 text-slate-500 font-mono font-bold">
          <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Jam: {{ String(currentTime.getHours()).padStart(2, '0') }}:{{ String(currentTime.getMinutes()).padStart(2, '0') }} WIB
        </span>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="props.loading" class="py-24 text-center">
      <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-emerald-500 border-t-transparent mb-3"></div>
      <p class="text-xs font-semibold text-slate-500">Memuat kartu jadwal mengajar...</p>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="filteredSchedules.length === 0"
      class="bg-white rounded-3xl p-12 border border-dashed border-slate-200 text-center space-y-4 shadow-xs"
    >
      <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto text-2xl font-bold">
        📅
      </div>
      <div class="max-w-md mx-auto space-y-1">
        <h3 class="text-base font-bold text-slate-800">Tidak Ada Jadwal Mengajar</h3>
        <p class="text-xs text-slate-500 leading-relaxed">
          {{
            auth.isGuru && !auth.isAdmin
              ? 'Anda belum memiliki jadwal mengajar pada pilihan filter ini. Silakan koordinasi dengan Bagian Kurikulum/Staf Admin jika ada penyesuaian jadwal.'
              : 'Tidak ditemukan jadwal KBM untuk kriteria yang dipilih. Anda dapat membuat jadwal baru di menu Manajemen Kelas & Jadwal.'
          }}
        </p>
      </div>
      <router-link
        v-if="auth.isAdmin"
        to="/classes-schedules"
        class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md transition"
      >
        + Buat Jadwal KBM Baru
      </router-link>
    </div>

    <!-- GRID CARDS KBM -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="sch in filteredSchedules"
        :key="sch.id"
        class="bg-white rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-lg transition-all duration-300 flex flex-col justify-between overflow-hidden group"
      >
        <div>
          <!-- CARD HEADER (Color banner) -->
          <div
            class="px-4 py-3.5 text-white relative flex flex-col justify-between min-h-[96px] transition-colors"
            :class="getCardTheme(sch, props.activeDate, currentTime).bg"
          >
            <!-- Top Row: Hari, Waktu Efektif & Status Waktu Real-Time -->
            <div class="flex items-center justify-between gap-2 mb-1.5">
              <div class="flex items-center gap-1.5 text-xs font-bold tracking-wide">
                <svg class="w-3.5 h-3.5 opacity-90 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ sch.hari.toUpperCase() }} • {{ sch.jam_mulai_efektif }} - {{ sch.jam_selesai_efektif }}</span>
              </div>

              <!-- Status Badge Waktu KBM Real-Time -->
              <div
                :class="getScheduleStatusInfo(sch, props.activeDate, currentTime).badgeClass"
                class="px-2.5 py-0.5 text-[10px] uppercase tracking-wider rounded-full flex items-center gap-1 shrink-0"
              >
                <span>{{ getScheduleStatusInfo(sch, props.activeDate, currentTime).badgeText }}</span>
              </div>
            </div>

            <!-- Subject Title & Duration Badge -->
            <div class="flex items-start justify-between gap-2">
              <h2 class="text-sm sm:text-base font-black tracking-wide uppercase line-clamp-2 leading-snug drop-shadow-xs">
                {{ sch.subject?.nama || 'MATA PELAJARAN' }}
              </h2>
              <span
                class="shrink-0 px-2 py-0.5 rounded-md text-[10px] sm:text-[11px] font-black bg-white/20 backdrop-blur-xs text-white border border-white/30 whitespace-nowrap shadow-xs"
              >
                {{ sch.durasi_label }}
              </span>
            </div>

            <!-- Tematik Muatan Pelajaran (Mini Chips) -->
            <div
              v-if="sch.subject?.is_tematik && sch.subject?.muatan_pelajaran?.length"
              class="mt-2 flex flex-wrap items-center gap-1"
            >
              <span
                v-for="muatan in sch.subject.muatan_pelajaran"
                :key="muatan"
                class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-black/25 text-white/95 border border-white/20"
              >
                {{ muatan }}
              </span>
            </div>
          </div>

          <!-- CARD BODY (Compact 2-Column Info Grid) -->
          <div class="p-4 space-y-3 text-xs text-slate-700 bg-white">
            <div class="grid grid-cols-2 gap-2">
              <!-- Rombel Kelas -->
              <div class="p-2.5 bg-slate-50/80 rounded-xl border border-slate-100 flex items-start gap-2">
                <span class="text-purple-600 text-sm shrink-0">👥</span>
                <div class="min-w-0">
                  <p class="text-[9px] uppercase font-bold text-slate-400 leading-tight">Kelas / Rombel</p>
                  <p class="font-bold text-purple-700 truncate text-xs">{{ sch.school_class?.nama || '-' }}</p>
                  <p class="text-[10px] text-slate-400">Tingkat {{ sch.school_class?.tingkat ?? 1 }}</p>
                </div>
              </div>

              <!-- No Ruangan & Siswa -->
              <div class="p-2.5 bg-slate-50/80 rounded-xl border border-slate-100 flex items-start gap-2">
                <span class="text-emerald-600 text-sm shrink-0">📍</span>
                <div class="min-w-0">
                  <p class="text-[9px] uppercase font-bold text-slate-400 leading-tight">Ruangan</p>
                  <p class="font-bold text-slate-800 truncate text-xs">{{ sch.room?.nama || sch.room?.kode || 'Ruang Belajar' }}</p>
                  <p class="text-[10px] text-slate-400">{{ sch.school_class?.active_students_count ?? 0 }} Siswa</p>
                </div>
              </div>

              <!-- Guru Pengampu -->
              <div class="p-2.5 bg-slate-50/80 rounded-xl border border-slate-100 flex items-start gap-2 col-span-2">
                <span class="text-indigo-600 text-sm shrink-0">👤</span>
                <div class="min-w-0 flex-1 flex items-center justify-between gap-2">
                  <div class="min-w-0">
                    <p class="text-[9px] uppercase font-bold text-slate-400 leading-tight">Guru Pengampu</p>
                    <p class="font-bold text-slate-900 truncate text-xs">{{ sch.teacher?.nama_lengkap || '-' }}</p>
                    <p v-if="sch.teacher?.nip" class="text-[10px] text-slate-400 font-mono truncate">NIP: {{ sch.teacher.nip }}</p>
                  </div>
                  <span
                    v-if="sch.subject?.is_tematik"
                    class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-black bg-purple-100 text-purple-700 border border-purple-200"
                  >
                    TEMA {{ sch.subject?.tema_nomor || '' }}
                  </span>
                </div>
              </div>

              <!-- Sesi / Jam Pelajaran Gabungan -->
              <div class="p-2.5 bg-slate-50/80 rounded-xl border border-slate-100 flex items-start gap-2 col-span-2">
                <span class="text-amber-600 text-sm shrink-0">⏱️</span>
                <div class="min-w-0 flex-1 flex items-center justify-between gap-2">
                  <div>
                    <p class="text-[9px] uppercase font-bold text-slate-400 leading-tight">Sesi & Jam Pelajaran</p>
                    <p class="font-bold text-slate-800 text-xs">{{ sch.sesi_label }}</p>
                    <p class="text-[10px] text-amber-700 font-bold mt-0.5">
                      {{ sch.durasi_jp }} Jam Pelajaran ({{ sch.durasi_jp }} x 35 menit)
                    </p>
                  </div>
                  <span class="text-[11px] font-mono font-bold text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200 shrink-0 shadow-2xs">
                    {{ sch.jam_mulai_efektif }} - {{ sch.jam_selesai_efektif }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Status Bukti KBM Hari Ini -->
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
              <span class="text-[11px] text-slate-400 font-medium">Status KBM:</span>
              <span
                v-if="(sch as any).today_evidence_count > 0"
                class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[11px] font-bold flex items-center gap-1"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Bukti Terunggah
              </span>
              <span
                v-else-if="isTodaySchedule(sch, props.activeDate)"
                class="px-2.5 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-[11px] font-bold flex items-center gap-1.5"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                Belum Unggah Bukti
              </span>
              <span
                v-else
                class="px-2.5 py-0.5 bg-slate-100 text-slate-500 rounded-full text-[11px] font-medium"
              >
                Jadwal Hari {{ sch.hari.toUpperCase() }}
              </span>
            </div>
          </div>
        </div>

        <!-- CARD FOOTER ACTION BAR -->
        <div class="p-4 pt-0 bg-white space-y-1.5">
          <!-- BISA AKSES: Sedang berlangsung ATAU sudah ada bukti yang terunggah (untuk lihat) -->
          <button
            v-if="getScheduleStatusInfo(sch, props.activeDate, currentTime).canUpload || (getScheduleStatusInfo(sch, props.activeDate, currentTime).canView && (sch as any).today_evidence_count > 0)"
            @click="handleCardClick(sch)"
            :class="getScheduleStatusInfo(sch, props.activeDate, currentTime).buttonClass"
            class="w-full py-2.5 px-3.5 font-bold text-xs rounded-xl transition-all flex items-center justify-center gap-2 cursor-pointer"
          >
            <svg v-if="getScheduleStatusInfo(sch, props.activeDate, currentTime).canUpload" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <span>{{ getScheduleStatusInfo(sch, props.activeDate, currentTime).buttonText }}</span>
          </button>

          <!-- TERKUNCI: DI LUAR JAM PELAJARAN (Belum mulai, sudah selesai, atau bukan hari ini) -->
          <div
            v-else
            @click="handleCardClick(sch)"
            :class="getScheduleStatusInfo(sch, props.activeDate, currentTime).buttonClass"
            class="w-full py-2.5 px-3 text-xs rounded-xl flex items-center justify-center gap-1.5 select-none text-center cursor-pointer transition hover:opacity-90"
            :title="getScheduleStatusInfo(sch, props.activeDate, currentTime).disabledReason"
          >
            <svg class="w-3.5 h-3.5 shrink-0 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <span class="truncate">{{ getScheduleStatusInfo(sch, props.activeDate, currentTime).buttonText }}</span>
          </div>

          <!-- Opsi Khusus Admin saat terkunci -->
          <div v-if="auth.isAdmin && !getScheduleStatusInfo(sch, props.activeDate, currentTime).canUpload && !(sch as any).today_evidence_count" class="text-center pt-0.5">
            <button
              type="button"
              @click.stop="handleCardClick(sch, true)"
              class="text-[10.5px] text-purple-700 hover:text-purple-900 font-semibold underline cursor-pointer"
            >
              Mode Admin: Buka Form KBM Khusus
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
