<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'
import type { TeacherAttendance, SchoolConfig } from '../components/attendance/types'
import { calculateDistance } from '../components/attendance/types'
import GeofenceValidationCard from '../components/attendance/GeofenceValidationCard.vue'
import AttendanceActionCard from '../components/attendance/AttendanceActionCard.vue'
import AttendanceHistoryTable from '../components/attendance/AttendanceHistoryTable.vue'
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

// GPS & Geofence
const currentLat = ref(-7.4538894)
const currentLng = ref(109.2346745)
const distanceMeter = ref(0)
const inGeofence = ref(true)
const gpsLoading = ref(false)

// Camera / Selfie
const selectedPhoto = ref<File | null>(null)
const photoPreview = ref<string | null>(null)

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
  distanceMeter.value = calculateDistance(
    currentLat.value,
    currentLng.value,
    schoolLat.value,
    schoolLng.value
  )
  inGeofence.value = distanceMeter.value <= schoolRadius.value
}

// GPS Location
const getGeoLocation = () => {
  if (navigator.geolocation) {
    gpsLoading.value = true
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        currentLat.value = pos.coords.latitude
        currentLng.value = pos.coords.longitude
        gpsLoading.value = false
        updateGeofenceStatus()
      },
      (err) => {
        console.warn('Geolocation warning/error:', err.message)
        gpsLoading.value = false
        updateGeofenceStatus()
      },
      { enableHighAccuracy: true, timeout: 8000 }
    )
  }
}

// Camera / Selfie Handlers
const onPhotoSelected = (file: File) => {
  selectedPhoto.value = file
  photoPreview.value = URL.createObjectURL(file)
}

const clearPhoto = () => {
  selectedPhoto.value = null
  photoPreview.value = null
}

// Submit Check-in
const submitCheckIn = async () => {
  submitting.value = true
  message.value = ''
  try {
    const formData = new FormData()
    formData.append('lat', String(currentLat.value))
    formData.append('lng', String(currentLng.value))
    if (selectedPhoto.value) {
      formData.append('foto', selectedPhoto.value)
    }

    const res = await api.post('/attendance/teachers/check-in', formData)
    if (res.data.success) {
      isSuccess.value = true
      message.value = res.data.message || 'Presensi masuk berhasil dicatat!'
      clearPhoto()
      await fetchToday()
      fetchHistory()
    }
  } catch (err: any) {
    isSuccess.value = false
    message.value = err.response?.data?.message || 'Gagal melakukan check-in presensi masuk.'
  } finally {
    submitting.value = false
  }
}

// Submit Check-out
const submitCheckOut = async () => {
  submitting.value = true
  message.value = ''
  try {
    const formData = new FormData()
    formData.append('lat', String(currentLat.value))
    formData.append('lng', String(currentLng.value))
    if (selectedPhoto.value) {
      formData.append('foto', selectedPhoto.value)
    }

    const res = await api.post('/attendance/teachers/check-out', formData)
    if (res.data.success) {
      isSuccess.value = true
      message.value = res.data.message || 'Presensi pulang berhasil dicatat!'
      clearPhoto()
      await fetchToday()
      fetchHistory()
    }
  } catch (err: any) {
    isSuccess.value = false
    message.value = err.response?.data?.message || 'Gagal melakukan check-out presensi pulang.'
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
    alert(err.response?.data?.message || 'Gagal mengajukan izin/sakit.')
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
    const res = await api.get('/attendance/teachers', { params: { limit: 7 } })
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
        @refresh-gps="getGeoLocation"
      />

      <!-- COLUMN 2: PENCATATAN JAM KEHADIRAN -->
      <AttendanceActionCard
        :today-attendance="todayAttendance"
        :school-config="schoolConfig"
        :submitting="submitting"
        :gps-loading="gpsLoading"
        :photo-preview="photoPreview"
        @check-in="submitCheckIn"
        @check-out="submitCheckOut"
        @photo-selected="onPhotoSelected"
        @clear-photo="clearPhoto"
        @open-leave-modal="isLeaveModalOpen = true"
      />
    </div>

    <!-- 3. RIWAYAT PRESENSI TERBARU (FULL WIDTH) -->
    <AttendanceHistoryTable
      :history-list="historyList"
      :history-loading="historyLoading"
      @refresh="fetchHistory"
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
