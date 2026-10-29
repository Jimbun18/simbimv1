<script setup lang="ts">
import { GOOGLE_MAPS_URL } from './types'

defineProps<{
  schoolLat: number
  schoolLng: number
  schoolRadius: number
  currentLat: number | null
  currentLng: number | null
  distanceMeter: number | null
  inGeofence: boolean
  gpsLoading: boolean
  gpsError?: string | null
}>()

const emit = defineEmits<{
  (e: 'refresh-gps'): void
}>()
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

    <!-- Alert Geofence Radius -->
    <div 
      v-if="currentLat === null || currentLng === null"
      class="p-3.5 rounded-xl border border-rose-200 bg-rose-50/80 text-xs space-y-1.5 transition-all"
    >
      <div class="flex items-center justify-between text-rose-800">
        <div class="flex items-center gap-2 font-bold uppercase text-[11px]">
          <span>🔴</span>
          <span>LOKASI GPS BELUM DIDAPATKAN</span>
        </div>
        <span class="text-[10px] font-bold bg-rose-100 text-rose-800 px-2 py-0.5 rounded">
          GPS Tidak Terdeteksi
        </span>
      </div>
      <p class="text-[11px] leading-relaxed text-rose-700">
        {{ gpsError || 'Layanan lokasi (GPS) pada perangkat Anda belum aktif atau izin browser belum diberikan. Silakan aktifkan GPS dan klik "Refresh GPS" di bawah ini.' }}
      </p>
    </div>

    <div 
      v-else
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
          Jarak: {{ distanceMeter !== null ? distanceMeter.toLocaleString('id-ID') : '-' }} m
        </span>
      </div>
      <p class="text-[11px] leading-relaxed" :class="inGeofence ? 'text-emerald-700' : 'text-amber-700'">
        {{ inGeofence 
          ? 'Lokasi Anda berada di dalam radius presensi (maksimum ' + schoolRadius + 'm). Presensi masuk/pulang dapat dilakukan.' 
          : 'Posisi saat ini terdeteksi ' + (distanceMeter !== null ? distanceMeter.toLocaleString('id-ID') : '-') + 'm dari pusat geofence. Presensi masuk membutuhkan jarak maksimum ' + schoolRadius + 'm.' 
        }}
      </p>
    </div>

    <!-- Koordinat GPS & Refresh Button -->
    <div class="flex items-center justify-between text-[11px] text-slate-500 font-mono pt-1">
      <div>
        <span v-if="currentLat !== null && currentLng !== null">
          Lat: {{ currentLat.toFixed(6) }} • Lng: {{ currentLng.toFixed(6) }}
        </span>
        <span v-else class="text-rose-600 font-sans font-medium text-[11px]">
          Menunggu koordinat GPS aktif...
        </span>
      </div>
      <button 
        type="button"
        @click="emit('refresh-gps')" 
        :disabled="gpsLoading"
        class="text-emerald-700 hover:text-emerald-800 font-semibold flex items-center gap-1 cursor-pointer transition disabled:opacity-50"
      >
        <svg class="w-3.5 h-3.5" :class="{'animate-spin': gpsLoading}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <span>{{ gpsLoading ? 'Mencari...' : 'Refresh GPS' }}</span>
      </button>
    </div>
  </div>
</template>
