<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'
import { showSuccess, showError } from '../services/swal'
import Swal from 'sweetalert2'
import type { TeacherAttendance, SchoolConfig } from '../components/attendance/types'
import { calculateDistance } from '../components/attendance/types'
import GeofenceValidationCard from '../components/attendance/GeofenceValidationCard.vue'
import AttendanceActionCard from '../components/attendance/AttendanceActionCard.vue'
import AttendanceHistoryTable from '../components/attendance/AttendanceHistoryTable.vue'
import AttendanceCameraModal from '../components/attendance/AttendanceCameraModal.vue'
import LeaveRequestModal from '../components/attendance/LeaveRequestModal.vue'

const auth = useAuthStore()

// State
const todayData = ref<any>(null)
const schoolConfig = ref<SchoolConfig | null>(null)
const historyList = ref<TeacherAttendance[]>([])
const historyLoading = ref(false)

const submitting = ref(false)
const message = ref('')
const isSuccess = ref(true)

const currentTime = ref('')
const currentDateStr = ref('')
let timer: any = null

// GPS & Geofence (Default null, tidak boleh fallback ke koordinat sekolah jika GPS mati)
const currentLat = ref<number | null>(null)
const currentLng = ref<number | null>(null)
const distanceMeter = ref<number | null>(null)
const inGeofence = ref(false)
const gpsLoading = ref(false)
const gpsError = ref<string | null>(null)

// Camera / Selfie Modal
const isCameraModalOpen = ref(false)
const cameraActionType = ref<'masuk' | 'pulang'>('masuk')

// Leave Request Modal
const isLeaveModalOpen = ref(false)
const submittingLeave = ref(false)

// Computed
const todayAttendance = computed(() => {
  if (!todayData.value) return null
  return todayData.value.attendance || (todayData.value.id ? todayData.value : null)
})

const schoolLat = computed(() => {
  return Number(schoolConfig.value?.latitude) || -7.4538894
})

const schoolLng = computed(() => {
  return Number(schoolConfig.value?.longitude) || 109.2346745
})

const schoolRadius = computed(() => {
  return Number(schoolConfig.value?.radius_geofence_m) || 100
})

const updateClock = () => {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }).replace(/\./g, ':')
  currentDateStr.value = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}

const updateGeofenceStatus = () => {
  if (currentLat.value === null || currentLng.value === null) {
    distanceMeter.value = null
    inGeofence.value = false
    return
  }
  distanceMeter.value = calculateDistance(
    currentLat.value,
    currentLng.value,
    schoolLat.value,
    schoolLng.value
  )
  inGeofence.value = distanceMeter.value <= schoolRadius.value
}

// GPS Location (Real-time GPS, no stale cache / no fallback)
const getGeoLocation = (showFeedback = false) => {
  if (!navigator.geolocation) {
    const msg = 'Browser Anda tidak mendukung deteksi lokasi (Geolocation).'
    gpsError.value = msg
    currentLat.value = null
    currentLng.value = null
    distanceMeter.value = null
    inGeofence.value = false
    if (showFeedback) showError('GPS Tidak Didukung', msg)
    return
  }

  gpsLoading.value = true
  gpsError.value = null

  navigator.geolocation.getCurrentPosition(
    (pos) => {
      currentLat.value = pos.coords.latitude
      currentLng.value = pos.coords.longitude
      gpsLoading.value = false
      gpsError.value = null
      updateGeofenceStatus()
    },
    (err) => {
      console.warn('Geolocation warning/error:', err.message)
      gpsLoading.value = false
      currentLat.value = null
      currentLng.value = null
      distanceMeter.value = null
      inGeofence.value = false

      let errorMsg = 'Gagal mendeteksi lokasi GPS. Pastikan izin lokasi (GPS) pada perangkat dan browser Anda telah diaktifkan.'
      if (err.code === 1) { // PERMISSION_DENIED
        errorMsg = 'Akses lokasi ditolak. Silakan izinkan akses lokasi (GPS) pada pengaturan browser Anda untuk melakukan presensi.'
      } else if (err.code === 2) { // POSITION_UNAVAILABLE
        errorMsg = 'Sinyal GPS tidak ditemukan. Pastikan fitur Lokasi/GPS di perangkat Anda sudah menyala.'
      } else if (err.code === 3) { // TIMEOUT
        errorMsg = 'Pencarian sinyal GPS melebihi batas waktu (timeout). Silakan coba segarkan GPS kembali.'
      }
      gpsError.value = errorMsg
      if (showFeedback) {
        showError('Lokasi Tidak Terdeteksi', errorMsg)
      }
    },
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
  )
}

// Validasi sebelum membuka modal kamera
const validateBeforeAttendance = (_type: 'masuk' | 'pulang'): boolean => {
  if (currentLat.value === null || currentLng.value === null) {
    showError(
      'Lokasi GPS Wajib Aktif',
      'Lokasi Anda belum terdeteksi. Silakan aktifkan GPS perangkat Anda dan izinkan akses lokasi di browser untuk melakukan presensi.'
    )
    getGeoLocation(false)
    return false
  }

  if (!inGeofence.value) {
    showError(
      'Di Luar Radius Sekolah',
      `Posisi Anda terdeteksi berjarak ${distanceMeter.value !== null ? distanceMeter.value.toLocaleString('id-ID') : '-'} meter dari sekolah (maksimal ${schoolRadius.value} meter). Presensi hanya dapat dilakukan di lingkungan sekolah SDI Bina Insan Mandiri.`
    )
    return false
  }

  return true
}

// Trigger tombol absen masuk -> buka kamera swafoto
const handleTriggerCheckIn = () => {
  if (!validateBeforeAttendance('masuk')) return
  cameraActionType.value = 'masuk'
  isCameraModalOpen.value = true
}

// Trigger tombol absen pulang -> langsung konfirmasi tanpa kamera swafoto
const handleTriggerCheckOut = async () => {
  if (!validateBeforeAttendance('pulang')) return
  if (currentLat.value === null || currentLng.value === null) {
    showError('GPS Tidak Terdeteksi', 'Lokasi GPS terputus. Pastikan GPS tetap aktif sebelum mengirim presensi.')
    return
  }

  const result = await Swal.fire({
    title: 'Konfirmasi Absen Pulang',
    text: 'Catat jam kepulangan Anda sekarang? (Absen pulang tidak memerlukan foto swafoto)',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#059669',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: 'Ya, Absen Pulang',
    cancelButtonText: 'Batal',
  })

  if (!result.isConfirmed) return

  submitting.value = true
  message.value = ''

  try {
    const formData = new FormData()
    formData.append('lat', String(currentLat.value))
    formData.append('lng', String(currentLng.value))
    // Absen pulang tanpa melampirkan foto

    const res = await api.post('/attendance/teachers/check-out', formData)
    if (res.data.success) {
      isSuccess.value = true
      const successMsg = res.data.message || 'Presensi pulang berhasil dicatat!'
      message.value = successMsg
      showSuccess('Presensi Pulang Berhasil', successMsg)
      await fetchToday()
      fetchHistory()
    }
  } catch (err: any) {
    isSuccess.value = false
    const errMsg = err.response?.data?.message || 'Gagal melakukan presensi pulang.'
    message.value = errMsg
    showError('Presensi Gagal', errMsg)
  } finally {
    submitting.value = false
  }
}

// Konfirmasi foto & submit presensi masuk
const handleConfirmPhoto = async (photoFile: File) => {
  if (currentLat.value === null || currentLng.value === null) {
    showError('GPS Tidak Terdeteksi', 'Lokasi GPS terputus. Pastikan GPS tetap aktif sebelum mengirim presensi.')
    return
  }

  submitting.value = true
  message.value = ''

  try {
    const formData = new FormData()
    formData.append('lat', String(currentLat.value))
    formData.append('lng', String(currentLng.value))
    formData.append('foto', photoFile)

    const res = await api.post('/attendance/teachers/check-in', formData)
    if (res.data.success) {
      isSuccess.value = true
      const successMsg = res.data.message || 'Presensi masuk berhasil dicatat!'
      message.value = successMsg
      isCameraModalOpen.value = false
      showSuccess('Presensi Masuk Berhasil', successMsg)
      await fetchToday()
      fetchHistory()
    }
  } catch (err: any) {
    isSuccess.value = false
    const errMsg = err.response?.data?.message || 'Gagal melakukan presensi masuk.'
    message.value = errMsg
    showError('Presensi Gagal', errMsg)
  } finally {
    submitting.value = false
  }
}

// Submit Leave Request
const submitLeave = async (formData: FormData) => {
  submittingLeave.value = true
  try {
    const res = await api.post('/attendance/teachers/leave', formData)
    if (res.data.success) {
      isSuccess.value = true
      message.value = 'Permohonan izin/sakit berhasil diajukan dan menunggu persetujuan.'
      isLeaveModalOpen.value = false
      await fetchToday()
      fetchHistory()
    }
  } catch (err: any) {
    showError('Permohonan Gagal', err.response?.data?.message || 'Gagal mengajukan izin/sakit.')
  } finally {
    submittingLeave.value = false
  }
}

// API Fetch Data
const fetchToday = async () => {
  try {
    const res = await api.get('/attendance/teachers/today')
    if (res.data.success) {
      todayData.value = res.data.data
      if (res.data.data?.school_config) {
        schoolConfig.value = res.data.data.school_config
      }
      updateGeofenceStatus()
    }
  } catch (err) {
    console.error('Gagal mengambil data presensi hari ini', err)
  }
}

const fetchHistory = async () => {
  historyLoading.value = true
  try {
    const res = await api.get('/attendance/teachers', { params: { limit: 10, personal: true } })
    if (res.data.success) {
      historyList.value = res.data.data?.data || res.data.data || []
    }
  } catch (err) {
    console.error('Gagal mengambil riwayat presensi', err)
  } finally {
    historyLoading.value = false
  }
}

onMounted(async () => {
  updateClock()
  timer = setInterval(updateClock, 1000)
  getGeoLocation()
  await fetchToday()
  fetchHistory()
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})
</script>

<template>
  <div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Top Notification Alert -->
    <transition
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="message" 
        :class="isSuccess ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900'" 
        class="p-4 rounded-2xl border text-xs sm:text-sm flex justify-between items-center shadow-xs transition-all"
      >
        <div class="flex items-center gap-2.5">
          <span class="text-base font-bold">{{ isSuccess ? '✓' : '⚠️' }}</span>
          <span class="font-semibold">{{ message }}</span>
        </div>
        <button @click="message = ''" class="font-bold opacity-60 hover:opacity-100 text-base px-2 py-0.5 cursor-pointer">&times;</button>
      </div>
    </transition>

    <!-- BANNER KHUSUS WALI KELAS: SHORTCUT PRESENSI SISWA -->
    <div
      v-if="auth.isWaliKelas"
      class="bg-gradient-to-r from-emerald-800 to-teal-900 rounded-2xl p-4 sm:p-5 text-white shadow-md border border-emerald-600/40 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
    >
      <div class="flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-2xl shrink-0">
          📋
        </div>
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-400/20 text-emerald-300 border border-emerald-400/30">
              Wali Kelas
            </span>
            <span class="text-xs text-emerald-200 font-semibold">
              Rombel: <strong class="text-white">{{ auth.primaryHomeroomClass?.nama }}</strong>
            </span>
          </div>
          <h3 class="text-sm sm:text-base font-bold text-white mt-1">Presensi Harian Siswa</h3>
          <p class="text-xs text-emerald-200/90 mt-0.5">
            Lakukan pencatatan kehadiran siswa rombel binaan Anda sekali sehari (Hadir, Izin, Sakit, Alpa).
          </p>
        </div>
      </div>
      <router-link
        to="/my-schedule?mode=homeroom"
        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold rounded-xl text-xs transition shadow-lg shadow-emerald-950/20 shrink-0"
      >
        <span>Buka Presensi Siswa</span>
        <span>&rarr;</span>
      </router-link>
    </div>

    <!-- 1. TOP HEADER BANNER (WAKTU SERVER PRESENSI) -->
    <div class="bg-gradient-to-r from-[#03362a] via-[#044737] to-[#032e23] text-white rounded-2xl p-6 sm:p-7 shadow-xs border border-emerald-900/40 flex flex-col md:flex-row md:items-center justify-between gap-6">
      <!-- Left side: Digital Clock & Date -->
      <div>
        <div class="text-[11px] font-black tracking-wider text-emerald-400 uppercase">
          WAKTU SERVER PRESENSI
        </div>
        <div class="text-3xl sm:text-4xl lg:text-5xl font-mono font-black tracking-tight text-white mt-1 flex items-baseline gap-1.5">
          <span>{{ currentTime || '00:00:00' }}</span>
          <span class="text-xs sm:text-sm font-bold text-emerald-300">WIB</span>
        </div>
        <div class="text-xs sm:text-sm text-emerald-200/90 font-medium mt-2 flex items-center gap-1.5">
          <svg class="w-4 h-4 text-emerald-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <span class="capitalize">{{ currentDateStr }}</span>
        </div>
      </div>

      <!-- Right side: Status Cards -->
      <div class="flex flex-wrap items-center gap-3">
        <!-- Status Kehadiran Hari Ini -->
        <div class="bg-black/25 border border-emerald-700/60 rounded-xl px-4 py-2.5 min-w-[170px] backdrop-blur-2xs">
          <div class="text-[10px] text-emerald-200/80 font-medium">Status Kehadiran Hari Ini</div>
          <div class="text-xs sm:text-sm font-bold text-white mt-1 flex items-center gap-1.5">
            <span 
              class="w-2 h-2 rounded-full shrink-0" 
              :class="todayAttendance?.status === 'hadir' && !todayAttendance?.is_terlambat ? 'bg-emerald-400' : 'bg-amber-400'"
            ></span>
            <span>
              {{ todayAttendance ? (todayAttendance.status === 'terlambat' || todayAttendance.is_terlambat ? 'Terlambat' : (todayAttendance.status === 'hadir' ? 'Hadir' : todayAttendance.status)) : 'Belum Presensi Masuk' }}
            </span>
          </div>
        </div>

        <!-- Toleransi Kedatangan -->
        <div class="bg-black/25 border border-emerald-700/60 rounded-xl px-4 py-2.5 min-w-[130px] backdrop-blur-2xs">
          <div class="text-[10px] text-emerald-200/80 font-medium">Toleransi Kedatangan</div>
          <div class="text-xs sm:text-sm font-bold text-white mt-1">
            {{ (schoolConfig?.jam_masuk_default || '07:00').substring(0, 5) }} WIB
          </div>
        </div>
      </div>
    </div>

    <!-- BANNER HARI LIBUR KALENDER / AKHIR PEKAN -->
    <div
      v-if="todayData?.is_holiday"
      class="bg-amber-500/10 border-2 border-amber-500/40 rounded-2xl p-4 sm:p-5 text-amber-950 flex items-start gap-4 shadow-xs"
    >
      <div class="w-11 h-11 rounded-xl bg-amber-100 border border-amber-300 flex items-center justify-center text-2xl shrink-0">
        🏖️
      </div>
      <div>
        <div class="flex items-center gap-2">
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-200 text-amber-900 border border-amber-300">
            {{ todayData?.holiday_info?.is_sunday ? 'Hari Minggu' : 'Hari Libur Kalender' }}
          </span>
          <span class="text-xs text-amber-800 font-bold">
            Presensi & KBM Ditutup
          </span>
        </div>
        <h3 class="text-sm sm:text-base font-bold text-amber-950 mt-1">
          {{ todayData?.holiday_reason || 'Hari ini adalah hari libur sekolah.' }}
        </h3>
        <p class="text-xs text-amber-800/90 mt-0.5">
          Pada hari libur atau akhir pekan, pendidik tidak diwajibkan melakukan presensi datang/pulang dan tidak dikenakan pemotongan kehadiran / alpa.
        </p>
      </div>
    </div>

    <!-- 2. MIDDLE TWO-COLUMN GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
      <!-- COLUMN 1: VALIDASI GEOFENCE RADIUS -->
      <GeofenceValidationCard
        :school-lat="schoolLat"
        :school-lng="schoolLng"
        :school-radius="schoolRadius"
        :current-lat="currentLat"
        :current-lng="currentLng"
        :distance-meter="distanceMeter"
        :in-geofence="inGeofence"
        :gps-loading="gpsLoading"
        :gps-error="gpsError"
        @refresh-gps="() => getGeoLocation(true)"
      />

      <!-- COLUMN 2: PENCATATAN JAM KEHADIRAN -->
      <AttendanceActionCard
        :today-attendance="todayAttendance"
        :school-config="schoolConfig"
        :submitting="submitting"
        :gps-loading="gpsLoading"
        :is-holiday="todayData?.is_holiday"
        :holiday-reason="todayData?.holiday_reason"
        @check-in="handleTriggerCheckIn"
        @check-out="handleTriggerCheckOut"
        @open-leave-modal="isLeaveModalOpen = true"
      />
    </div>

    <!-- 3. RIWAYAT PRESENSI TERBARU (FULL WIDTH) -->
    <AttendanceHistoryTable
      :history-list="historyList"
      :history-loading="historyLoading"
      @refresh="fetchHistory"
    />

    <!-- MODAL KAMERA SWAFOTO & KONFIRMASI PRESENSI -->
    <AttendanceCameraModal
      :is-open="isCameraModalOpen"
      :type="cameraActionType"
      :submitting="submitting"
      :distance-meter="distanceMeter"
      @close="isCameraModalOpen = false"
      @confirm="handleConfirmPhoto"
    />

    <!-- MODAL PENGAJUAN IZIN / CUTI -->
    <LeaveRequestModal
      :is-open="isLeaveModalOpen"
      :submitting="submittingLeave"
      @close="isLeaveModalOpen = false"
      @submit="submitLeave"
    />
  </div>
</template>
