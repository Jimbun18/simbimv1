<script setup lang="ts">
import { onMounted, onUnmounted, nextTick, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { GOOGLE_MAPS_URL } from './types'

const props = defineProps<{
  schoolLat: number
  schoolLng: number
  schoolRadius: number
  currentLat: number
  currentLng: number
  distanceMeter: number
  inGeofence: boolean
  gpsLoading: boolean
}>()

const emit = defineEmits<{
  (e: 'refresh-gps'): void
}>()

// Leaflet Map References
let map: L.Map | null = null
let schoolMarker: L.Marker | null = null
let userMarker: L.Marker | null = null
let geofenceCircle: L.Circle | null = null
let connectionLine: L.Polyline | null = null

const initMap = () => {
  const mapContainer = document.getElementById('attendance-map')
  if (!mapContainer || map) return

  map = L.map('attendance-map', {
    center: [props.schoolLat, props.schoolLng],
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

  schoolMarker = L.marker([props.schoolLat, props.schoolLng], { icon: schoolIcon }).addTo(map)
  schoolMarker.bindPopup(`
    <div class="text-xs font-sans p-1">
      <strong class="text-emerald-800 block text-sm font-black">SD Islam Bina Insan Mandiri</strong>
      <span class="text-slate-500">Pusat Titik Presensi & Geofence</span><br/>
      <span class="text-[11px] text-slate-400 font-semibold">Radius Validasi: ${props.schoolRadius} meter</span>
      <div class="mt-2 pt-1.5 border-t border-slate-100">
        <a href="${GOOGLE_MAPS_URL}" target="_blank" class="text-emerald-700 font-bold hover:underline inline-flex items-center gap-1">
          Buka di Google Maps ↗
        </a>
      </div>
    </div>
  `)

  // Geofence Circle
  geofenceCircle = L.circle([props.schoolLat, props.schoolLng], {
    radius: props.schoolRadius,
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
    geofenceCircle.setLatLng([props.schoolLat, props.schoolLng])
    geofenceCircle.setRadius(props.schoolRadius)
    geofenceCircle.setStyle({
      color: props.inGeofence ? '#059669' : '#d97706',
      fillColor: props.inGeofence ? '#10b981' : '#f59e0b',
      fillOpacity: props.inGeofence ? 0.18 : 0.12,
    })
  }

  // Update School Marker
  if (schoolMarker) {
    schoolMarker.setLatLng([props.schoolLat, props.schoolLng])
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
    userMarker = L.marker([props.currentLat, props.currentLng], { icon: userIcon }).addTo(map)
    userMarker.bindPopup('<strong class="text-xs font-sans">Posisi GPS Anda</strong>')
  } else {
    userMarker.setLatLng([props.currentLat, props.currentLng])
    userMarker.setIcon(userIcon)
  }

  // Connection Line
  if (connectionLine) {
    connectionLine.setLatLngs([
      [props.currentLat, props.currentLng],
      [props.schoolLat, props.schoolLng],
    ])
    connectionLine.setStyle({
      color: props.inGeofence ? '#059669' : '#d97706',
    })
  } else {
    connectionLine = L.polyline([
      [props.currentLat, props.currentLng],
      [props.schoolLat, props.schoolLng],
    ], {
      color: props.inGeofence ? '#059669' : '#d97706',
      weight: 2,
      dashArray: '5, 8',
      opacity: 0.7,
    }).addTo(map)
  }
}

const centerSchool = () => {
  if (map) {
    map.setView([props.schoolLat, props.schoolLng], 18, { animate: true })
  }
}

const centerUser = () => {
  if (map) {
    map.setView([props.currentLat, props.currentLng], 18, { animate: true })
  }
}

watch(
  () => [props.currentLat, props.currentLng, props.schoolLat, props.schoolLng, props.schoolRadius, props.inGeofence],
  () => {
    updateMapMarkers()
  }
)

onMounted(() => {
  nextTick(() => {
    initMap()
  })
})

onUnmounted(() => {
  if (map) {
    map.remove()
    map = null
  }
})
</script>

<template>
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
      <div class="absolute bottom-2 left-2 z-20 flex gap-1.5">
        <button
          type="button"
          @click="centerSchool"
          class="px-2 py-1 bg-white/90 hover:bg-white text-[10px] font-bold text-slate-700 rounded-lg shadow-xs border border-slate-200 transition cursor-pointer"
          title="Fokus ke Lokasi Sekolah"
        >
          🏫 Sekolah
        </button>
        <button
          type="button"
          @click="centerUser"
          class="px-2 py-1 bg-white/90 hover:bg-white text-[10px] font-bold text-slate-700 rounded-lg shadow-xs border border-slate-200 transition cursor-pointer"
          title="Fokus ke Posisi Saya"
        >
          📍 Posisi Saya
        </button>
      </div>
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
        @click="emit('refresh-gps')" 
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
</template>
