<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const auth = useAuthStore()
const router = useRouter()

const GOOGLE_MAPS_URL = 'https://maps.app.goo.gl/2DjGnYuHWWaXNLX69'

// State
const todayData = ref<any>(null)
const schoolConfig = ref<any>(null)
const historyList = ref<any[]>([])
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

// Leaflet Map References
let map: L.Map | null = null
let schoolMarker: L.Marker | null = null
let userMarker: L.Marker | null = null
let geofenceCircle: L.Circle | null = null
let connectionLine: L.Polyline | null = null

// Camera / Selfie
const cameraInputRef = ref<HTMLInputElement | null>(null)
const selectedPhoto = ref<File | null>(null)
const photoPreview = ref<string | null>(null)

// Leave Request Modal
const isLeaveModalOpen = ref(false)
const submittingLeave = ref(false)
const leaveForm = ref({
  status: 'izin' as 'izin' | 'sakit' | 'dinas',
  tanggal_mulai: new Date().toISOString().split('T')[0],
  tanggal_selesai: new Date().toISOString().split('T')[0],
  keterangan: '',
})
const leaveAttachment = ref<File | null>(null)

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

const formatTimeDisplay = (timeStr: string | null | undefined): string => {
  if (!timeStr) return '--:--'
  const clean = timeStr.substring(0, 5)
  return `${clean} WIB`
}

const formatSpacedTime = (timeStr: string | null | undefined): string => {
  if (!timeStr) return '--  :  --'
  const clean = timeStr.substring(0, 5)
  return clean.replace(':', '  :  ')
}

const formatKeteranganDisplay = (h: any): string => {
  if (!h) return '-'
  if (h.keterangan) {
    if (h.keterangan.includes('Terlambat') && !h.keterangan.includes('toleransi')) {
      return `${h.keterangan} dari toleransi`
    }
    return h.keterangan
  }
  if (h.is_terlambat && h.menit_keterlambatan) {
    return `Terlambat ${h.menit_keterlambatan} menit dari toleransi`
  }
  if (h.status === 'hadir') return 'Tepat waktu'
  return '-'
}

const formatDateDisplay = (dateStr: string | null | undefined): string => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return dateStr
    return d.toLocaleDateString('id-ID', {
      weekday: 'short',
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    })
  } catch {
    return dateStr
  }
}

const updateClock = () => {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }).replace(/\./g, ':')
  currentDateStr.value = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}

// Haversine Distance
const calculateDistance = (lat1: number, lon1: number, lat2: number, lon2: number) => {
  const R = 6371e3
  const phi1 = (lat1 * Math.PI) / 180
  const phi2 = (lat2 * Math.PI) / 180
  const deltaPhi = ((lat2 - lat1) * Math.PI) / 180
  const deltaLambda = ((lon2 - lon1) * Math.PI) / 180

  const a = Math.sin(deltaPhi / 2) * Math.sin(deltaPhi / 2) +
            Math.cos(phi1) * Math.cos(phi2) *
            Math.sin(deltaLambda / 2) * Math.sin(deltaLambda / 2)
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))

  return Math.round(R * c)
}

const updateGeofenceStatus = () => {
  distanceMeter.value = calculateDistance(
    currentLat.value,
    currentLng.value,
    schoolLat.value,
    schoolLng.value
  )
  inGeofence.value = distanceMeter.value <= schoolRadius.value
  updateMapMarkers()
}

// Map Initialization
const initMap = () => {
  const mapContainer = document.getElementById('attendance-map')
  if (!mapContainer || map) return

  map = L.map('attendance-map', {
    center: [schoolLat.value, schoolLng.value],
    zoom: 17,
    zoomControl: false,
  })

  L.control.zoom({ position: 'bottomright' }).addTo(map)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map)

  // Custom School Icon
  const schoolIcon = L.divIcon({
    className: 'custom-school-icon',
    html: `
      <div style="transform: translate(-50%, -100%);" class="flex flex-col items-center pointer-events-auto">
        <div class="px-2 py-0.5 bg-[#064e3b] text-white text-[10px] font-bold rounded shadow-md whitespace-nowrap mb-1">
          SDI Bina Insan Mandiri
        </div>
        <div class="w-6 h-6 rounded-full bg-emerald-800 text-white flex items-center justify-center shadow-md border-2 border-white text-xs">
          📍
        </div>
      </div>
    `,
    iconSize: [120, 50],
    iconAnchor: [60, 48],
  })

  schoolMarker = L.marker([schoolLat.value, schoolLng.value], { icon: schoolIcon }).addTo(map)
  schoolMarker.bindPopup(`
    <div class="text-xs font-sans p-1">
      <strong class="text-emerald-800 block text-sm font-black">SD Islam Bina Insan Mandiri</strong>
      <span class="text-slate-500">Pusat Titik Presensi & Geofence</span><br/>
      <span class="text-[11px] text-slate-400 font-semibold">Radius Validasi: ${schoolRadius.value} meter</span>
      <div class="mt-2 pt-1.5 border-t border-slate-100">
        <a href="${GOOGLE_MAPS_URL}" target="_blank" class="text-emerald-700 font-bold hover:underline inline-flex items-center gap-1">
          Buka di Google Maps ↗
        </a>
      </div>
    </div>
  `)

  // Geofence Circle
  geofenceCircle = L.circle([schoolLat.value, schoolLng.value], {
    radius: schoolRadius.value,
    color: '#059669',
    fillColor: '#10b981',
    fillOpacity: 0.18,
    weight: 2,
  }).addTo(map)

  updateMapMarkers()
}

const updateMapMarkers = () => {
  if (!map) return

  // Update Geofence Circle
  if (geofenceCircle) {
    geofenceCircle.setLatLng([schoolLat.value, schoolLng.value])
    geofenceCircle.setRadius(schoolRadius.value)
    geofenceCircle.setStyle({
      color: inGeofence.value ? '#059669' : '#d97706',
      fillColor: inGeofence.value ? '#10b981' : '#f59e0b',
      fillOpacity: inGeofence.value ? 0.18 : 0.12,
    })
  }

  // Update School Marker
  if (schoolMarker) {
    schoolMarker.setLatLng([schoolLat.value, schoolLng.value])
  }

  // User Marker Icon
  const userIcon = L.divIcon({
    className: 'custom-user-icon',
    html: `
      <div style="transform: translate(-50%, -100%);" class="flex flex-col items-center pointer-events-auto">
        <div class="px-2 py-0.5 bg-amber-600 text-white text-[10px] font-bold rounded shadow-md whitespace-nowrap mb-1">
          Posisi Anda
        </div>
        <div class="w-5 h-5 rounded-full bg-amber-500 flex items-center justify-center shadow-md border-2 border-white">
          <div class="w-1.5 h-1.5 rounded-full bg-white"></div>
        </div>
      </div>
    `,
    iconSize: [80, 44],
    iconAnchor: [40, 42],
  })

  if (!userMarker) {
    userMarker = L.marker([currentLat.value, currentLng.value], { icon: userIcon }).addTo(map)
    userMarker.bindPopup('<strong class="text-xs font-sans">Posisi GPS Anda</strong>')
  } else {
    userMarker.setLatLng([currentLat.value, currentLng.value])
    userMarker.setIcon(userIcon)
  }

  // Connection Line
  if (connectionLine) {
    connectionLine.setLatLngs([
      [currentLat.value, currentLng.value],
      [schoolLat.value, schoolLng.value],
    ])
    connectionLine.setStyle({
      color: inGeofence.value ? '#059669' : '#d97706',
    })
  } else {
    connectionLine = L.polyline([
      [currentLat.value, currentLng.value],
      [schoolLat.value, schoolLng.value],
    ], {
      color: inGeofence.value ? '#059669' : '#d97706',
      weight: 2,
      dashArray: '5, 8',
      opacity: 0.7,
    }).addTo(map)
  }
}

const centerSchool = () => {
  if (map) {
    map.setView([schoolLat.value, schoolLng.value], 18, { animate: true })
  }
}

const centerUser = () => {
  if (map) {
    map.setView([currentLat.value, currentLng.value], 18, { animate: true })
  }
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
const triggerCamera = () => {
  cameraInputRef.value?.click()
}

const onPhotoSelected = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    const file = target.files[0]
    selectedPhoto.value = file
    photoPreview.value = URL.createObjectURL(file)
  }
}

const clearPhoto = () => {
  selectedPhoto.value = null
  photoPreview.value = null
  if (cameraInputRef.value) cameraInputRef.value.value = ''
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
const onLeaveFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    leaveAttachment.value = target.files[0]
  }
}

const submitLeave = async () => {
  if (!leaveForm.value.keterangan.trim()) {
    alert('Silakan tuliskan alasan permohonan izin/sakit.')
    return
  }
  submittingLeave.value = true
  try {
    const formData = new FormData()
    formData.append('status', leaveForm.value.status)
    formData.append('tanggal_mulai', leaveForm.value.tanggal_mulai)
    formData.append('tanggal_selesai', leaveForm.value.tanggal_selesai)
    formData.append('keterangan', leaveForm.value.keterangan)
    if (leaveAttachment.value) {
      formData.append('lampiran', leaveAttachment.value)
    }

    const res = await api.post('/attendance/teachers/leave', formData)
    if (res.data.success) {
      isSuccess.value = true
      message.value = 'Permohonan izin/sakit berhasil diajukan dan menunggu persetujuan.'
      isLeaveModalOpen.value = false
      leaveForm.value.keterangan = ''
      leaveAttachment.value = null
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
  nextTick(() => {
    initMap()
  })
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
  if (map) {
    map.remove()
    map = null
  }
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
        <button @click="message = ''" class="font-bold opacity-60 hover:opacity-100 text-base px-2 py-0.5">&times;</button>
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
    <div class="bg-gradient-to-r from-[#03362a] via-[#044737] to-[#032e23] text-white rounded-2xl p-6 sm:p-7 shadow-sm border border-emerald-900/40 flex flex-col md:flex-row md:items-center justify-between gap-6">
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
        <div class="bg-black/25 border border-emerald-700/60 rounded-xl px-4 py-2.5 min-w-[170px] backdrop-blur-xs">
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
        <div class="bg-black/25 border border-emerald-700/60 rounded-xl px-4 py-2.5 min-w-[130px] backdrop-blur-xs">
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
      <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between gap-2">
          <div>
            <h3 class="text-sm sm:text-base font-bold text-slate-900 leading-tight">Validasi Geofence Radius</h3>
            <p class="text-[11px] text-slate-400">Pusat SDI Bina Insan Mandiri Purwokerto</p>
          </div>
          <a
            :href="GOOGLE_MAPS_URL"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg text-xs font-semibold transition border border-emerald-200"
          >
            <span>Google Maps</span>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
          </a>
        </div>

        <!-- Interactive Leaflet Map Container -->
        <div class="relative w-full h-56 sm:h-60 rounded-xl overflow-hidden border border-slate-200/80 shadow-2xs">
          <div id="attendance-map" class="w-full h-full z-10"></div>
        </div>

        <!-- Map Legend -->
        <div class="flex items-center justify-between text-[10px] text-slate-500 font-medium px-1">
          <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              <span>Radius Aman ({{ schoolRadius }}m)</span>
            </span>
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-amber-500"></span>
              <span>Anda</span>
            </span>
          </div>
          <span class="text-slate-400">OpenStreetMap</span>
        </div>

        <!-- Alert Geofence Radius -->
        <div 
          class="p-3.5 rounded-xl border text-xs space-y-1.5 transition-all"
          :class="inGeofence ? 'bg-emerald-50/70 border-emerald-200' : 'bg-amber-50/70 border-amber-200'"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 font-bold uppercase text-[11px]" :class="inGeofence ? 'text-emerald-800' : 'text-amber-800'">
              <span v-if="!inGeofence">⚠️</span>
              <span v-else>✓</span>
              <span>{{ inGeofence ? 'DALAM RADIUS SEKOLAH' : 'DI LUAR RADIUS SEKOLAH' }}</span>
            </div>
            <span 
              class="font-mono text-[11px] font-bold px-2 py-0.5 rounded-md"
              :class="inGeofence ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
            >
              Jarak: {{ distanceMeter.toLocaleString('id-ID') }} m
            </span>
          </div>
          <p class="text-[11px] leading-relaxed" :class="inGeofence ? 'text-emerald-700' : 'text-amber-700'">
            {{ inGeofence 
              ? 'Lokasi Anda berada di dalam radius presensi (maksimum ' + schoolRadius + 'm). Presensi masuk/pulang dapat dilakukan.' 
              : 'Posisi saat ini terdeteksi ' + distanceMeter.toLocaleString('id-ID') + 'm dari pusat geofence. Presensi masuk membutuhkan jarak maksimum ' + schoolRadius + 'm.' 
            }}
          </p>
        </div>

        <!-- Koordinat GPS & Refresh Button -->
        <div class="flex items-center justify-between text-[11px] text-slate-500 font-mono pt-1">
          <div>
            <span>Lat: {{ currentLat.toFixed(6) }}</span> • <span>Lng: {{ currentLng.toFixed(6) }}</span>
          </div>
          <button 
            @click="getGeoLocation" 
            :disabled="gpsLoading"
            class="text-slate-600 hover:text-slate-900 font-semibold flex items-center gap-1 cursor-pointer transition"
          >
            <svg class="w-3.5 h-3.5" :class="{'animate-spin': gpsLoading}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>Refresh GPS</span>
          </button>
        </div>
      </div>

      <!-- COLUMN 2: PENCATATAN JAM KEHADIRAN -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 space-y-4">
        <div>
          <h3 class="text-base font-bold text-slate-900">Pencatatan Jam Kehadiran</h3>
          <p class="text-[11px] text-slate-400">Rekapitulasi jam presensi harian pendidik</p>
        </div>

        <!-- Grid Jam Masuk & Jam Pulang -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
          <!-- JAM MASUK -->
          <div class="border border-slate-200/80 rounded-xl p-4 bg-white space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">JAM MASUK</span>
              <span 
                v-if="todayAttendance?.jam_masuk"
                class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase"
                :class="(todayAttendance?.status === 'terlambat' || todayAttendance?.is_terlambat) ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'"
              >
                {{ (todayAttendance?.status === 'terlambat' || todayAttendance?.is_terlambat) ? 'Terlambat' : (todayAttendance?.status || 'Hadir') }}
              </span>
              <span v-else class="text-[10px] text-slate-400 font-bold bg-slate-100 px-2 py-0.5 rounded-md">
                Belum Absen
              </span>
            </div>
            <div class="text-xl sm:text-2xl font-mono font-bold text-slate-700 tracking-wider">
              {{ formatSpacedTime(todayAttendance?.jam_masuk) }}
            </div>
            <div class="text-[11px] text-slate-500 font-medium flex items-center gap-1.5 pt-1">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              <span>Batas standar: {{ (schoolConfig?.jam_masuk_default || '07:00').substring(0, 5) }} WIB</span>
            </div>
          </div>

          <!-- JAM PULANG -->
          <div class="border border-slate-200/80 rounded-xl p-4 bg-white space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">JAM PULANG</span>
              <span 
                v-if="todayAttendance?.jam_keluar"
                class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-teal-100 text-teal-800"
              >
                Selesai
              </span>
              <span v-else-if="todayAttendance?.jam_masuk" class="text-[10px] text-amber-700 font-bold bg-amber-50 px-2 py-0.5 rounded-md">
                Sedang Mengajar
              </span>
              <span v-else class="text-[10px] text-slate-400 font-bold bg-slate-100 px-2 py-0.5 rounded-md">
                Belum Absen
              </span>
            </div>
            <div class="text-xl sm:text-2xl font-mono font-bold text-slate-700 tracking-wider">
              {{ formatSpacedTime(todayAttendance?.jam_keluar) }}
            </div>
            <div class="text-[11px] text-slate-500 font-medium flex items-center gap-1.5 pt-1">
              <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
              <span>Standar kepulangan: {{ (schoolConfig?.jam_pulang_default || '14:30').substring(0, 5) }} WIB</span>
            </div>
          </div>
        </div>

        <!-- Foto Selfie Presensi Box -->
        <div class="p-3.5 border border-slate-200/80 rounded-xl flex items-center justify-between gap-3 bg-white">
          <div class="flex items-center gap-3">
            <div v-if="photoPreview" class="relative w-10 h-10 rounded-xl overflow-hidden border border-emerald-400 shrink-0 shadow-xs">
              <img :src="photoPreview" alt="Preview Foto" class="w-full h-full object-cover" />
              <button 
                type="button"
                @click="clearPhoto" 
                class="absolute top-0 right-0 bg-rose-600 text-white rounded-bl text-[9px] w-3.5 h-3.5 flex items-center justify-center font-bold cursor-pointer"
                title="Hapus foto"
              >&times;</button>
            </div>
            <div v-else class="w-10 h-10 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <div>
              <h4 class="font-bold text-xs text-slate-800">Foto Selfie Presensi</h4>
              <p class="text-[10px] text-slate-400 leading-tight">Ambil swafoto validasi di lingkungan sekolah</p>
            </div>
          </div>

          <div>
            <input 
              type="file" 
              ref="cameraInputRef" 
              accept="image/*" 
              capture="user" 
              class="hidden" 
              @change="onPhotoSelected" 
            />
            <button 
              type="button" 
              @click="triggerCamera" 
              class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition shadow-2xs cursor-pointer"
            >
              <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span>{{ photoPreview ? 'Ganti Selfie' : 'Ambil Selfie' }}</span>
            </button>
          </div>
        </div>

        <!-- Tombol Utama Presensi -->
        <div class="space-y-3 pt-1">
          <!-- 1. Tombol Absen Masuk -->
          <button
            v-if="!todayAttendance?.jam_masuk"
            @click="submitCheckIn"
            :disabled="submitting || gpsLoading"
            class="w-full py-3.5 px-6 bg-[#059669] hover:bg-[#047857] active:scale-[0.99] text-white font-bold rounded-xl shadow-xs text-sm transition disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
          >
            <svg v-if="submitting" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <template v-else>
              <svg class="w-5 h-5 text-white/90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>Absen Masuk Sekarang</span>
            </template>
          </button>

          <!-- 2. Tombol Absen Pulang -->
          <button
            v-else-if="!todayAttendance?.jam_keluar"
            @click="submitCheckOut"
            :disabled="submitting || gpsLoading"
            class="w-full py-3.5 px-6 bg-[#059669] hover:bg-[#047857] active:scale-[0.99] text-white font-bold rounded-xl shadow-xs text-sm transition disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
          >
            <svg v-if="submitting" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <template v-else>
              <svg class="w-5 h-5 text-white/90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>Absen Pulang Sekarang</span>
            </template>
          </button>

          <!-- 3. Selesai Hari Ini Banner -->
          <div v-else class="text-center p-3.5 bg-emerald-50 rounded-xl border border-emerald-200 text-xs font-semibold text-emerald-900 flex items-center justify-center gap-2">
            <span class="text-base">🎉</span>
            <span>Alhamdulillah, presensi hari ini telah lengkap. Terima kasih atas dedikasi Anda mendidik siswa!</span>
          </div>

          <!-- Tombol Pengajuan Izin / Cuti -->
          <div class="text-center pt-1 text-xs text-slate-500">
            <span>Berhalangan hadir atau sedang sakit? </span>
            <button
              type="button"
              @click="isLeaveModalOpen = true"
              class="font-bold text-emerald-700 hover:text-emerald-800 hover:underline cursor-pointer"
            >
              Ajukan Izin / Cuti &gt;
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. RIWAYAT PRESENSI TERBARU (FULL WIDTH) -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h3 class="font-bold text-slate-900 text-base">Riwayat Presensi Terbaru</h3>
          <p class="text-[11px] text-slate-400">Catatan kehadiran beberapa hari kerja terakhir</p>
        </div>
        <div class="flex items-center gap-2">
          <button 
            @click="fetchHistory"
            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-600 rounded-lg border border-slate-200 text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer"
          >
            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>Segarkan</span>
          </button>
          <button 
            @click="router.push('/my-attendance-history')"
            class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg border border-emerald-200 text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer"
          >
            <span>Buku Riwayat Lengkap</span>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Loading state -->
      <div v-if="historyLoading" class="text-center py-8 text-slate-400 text-xs">
        Memuat riwayat kehadiran...
      </div>

      <!-- Empty state -->
      <div v-else-if="historyList.length === 0" class="text-center py-8 text-slate-400 text-xs bg-slate-50 rounded-xl border border-dashed border-slate-200">
        Belum ada riwayat kehadiran tercatat.
      </div>

      <!-- Responsive Table -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="text-slate-500 font-bold uppercase tracking-wider text-[11px] border-b border-slate-100">
              <th class="py-3 px-4">TANGGAL</th>
              <th class="py-3 px-4">JAM MASUK</th>
              <th class="py-3 px-4">JAM PULANG</th>
              <th class="py-3 px-4">STATUS</th>
              <th class="py-3 px-4">KETERANGAN</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="h in historyList" :key="h.id" class="hover:bg-slate-50/50 transition">
              <td class="py-3.5 px-4 font-semibold text-slate-800 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <span 
                    class="w-2 h-2 rounded-full shrink-0"
                    :class="(h.status === 'terlambat' || h.is_terlambat) ? 'bg-amber-500' : 'bg-emerald-500'"
                  ></span>
                  <span>{{ formatDateDisplay(h.tanggal) }}</span>
                </div>
              </td>
              <td class="py-3.5 px-4 font-mono font-bold text-slate-700 whitespace-nowrap">
                {{ h.jam_masuk ? formatTimeDisplay(h.jam_masuk) : '-' }}
              </td>
              <td class="py-3.5 px-4 font-mono font-bold text-slate-700 whitespace-nowrap">
                {{ h.jam_keluar ? formatTimeDisplay(h.jam_keluar) : '-' }}
              </td>
              <td class="py-3.5 px-4 whitespace-nowrap">
                <span 
                  class="px-2.5 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border"
                  :class="{
                    'border-emerald-300 text-emerald-600 bg-emerald-50/40': h.status === 'hadir' && !h.is_terlambat,
                    'border-amber-300 text-amber-600 bg-amber-50/40': h.status === 'terlambat' || h.is_terlambat,
                    'border-blue-300 text-blue-600 bg-blue-50/40': h.status === 'izin',
                    'border-purple-300 text-purple-600 bg-purple-50/40': h.status === 'sakit',
                    'border-rose-300 text-rose-600 bg-rose-50/40': h.status === 'alpa'
                  }"
                >
                  {{ (h.status === 'terlambat' || h.is_terlambat) ? 'TERLAMBAT' : (h.status ? h.status.toUpperCase() : 'HADIR') }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-slate-500 text-xs">
                {{ formatKeteranganDisplay(h) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL PENGAJUAN IZIN / CUTI -->
    <div
      v-if="isLeaveModalOpen"
      class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4 backdrop-blur-xs"
      @click="isLeaveModalOpen = false"
    >
      <div class="max-w-md w-full bg-white rounded-3xl p-6 shadow-2xl space-y-4" @click.stop>
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-black text-slate-900 text-base">Pengajuan Izin / Cuti</h3>
            <p class="text-xs text-slate-400">Kirim permohonan ke Kepala Sekolah</p>
          </div>
          <button @click="isLeaveModalOpen = false" class="text-slate-400 hover:text-slate-700 text-2xl font-bold p-1 cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="submitLeave" class="space-y-4 text-xs">
          <!-- Jenis Izin -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Jenis Ketidakhadiran</label>
            <select
              v-model="leaveForm.status"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500"
            >
              <option value="izin">Izin Kepentingan Pribadi/Keluarga</option>
              <option value="sakit">Sakit</option>
              <option value="dinas">Dinas Luar / Penugasan</option>
            </select>
          </div>

          <!-- Rentang Tanggal -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Mulai Tanggal</label>
              <input
                v-model="leaveForm.tanggal_mulai"
                type="date"
                required
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Sampai Tanggal</label>
              <input
                v-model="leaveForm.tanggal_selesai"
                type="date"
                required
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500"
              />
            </div>
          </div>

          <!-- Keterangan -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Alasan / Keterangan</label>
            <textarea
              v-model="leaveForm.keterangan"
              required
              rows="3"
              placeholder="Jelaskan alasan izin / sakit..."
              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500"
            ></textarea>
          </div>

          <!-- Lampiran File -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Lampiran Dokumen / Surat Dokter (Opsional)</label>
            <input
              type="file"
              accept=".pdf,.jpg,.jpeg,.png"
              @change="onLeaveFileChange"
              class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
            />
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center justify-end gap-2 pt-2">
            <button
              type="button"
              @click="isLeaveModalOpen = false"
              class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="submittingLeave"
              class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition disabled:opacity-50 cursor-pointer"
            >
              {{ submittingLeave ? 'Mengirim...' : 'Kirim Pengajuan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
