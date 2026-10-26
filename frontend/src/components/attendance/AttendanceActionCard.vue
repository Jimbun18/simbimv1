<script setup lang="ts">
import { ref } from 'vue'
import { formatSpacedTime } from './types'

defineProps<{
  todayAttendance: any
  schoolConfig: any
  submitting: boolean
  gpsLoading: boolean
  photoPreview: string | null
  isHoliday?: boolean
  holidayReason?: string
}>()

const emit = defineEmits<{
  (e: 'check-in'): void
  (e: 'check-out'): void
  (e: 'photo-selected', file: File): void
  (e: 'clear-photo'): void
  (e: 'open-leave-modal'): void
}>()

const cameraInputRef = ref<HTMLInputElement | null>(null)

const triggerCamera = () => {
  cameraInputRef.value?.click()
}

const onPhotoSelected = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    emit('photo-selected', target.files[0])
  }
}
</script>

<template>
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
            @click="emit('clear-photo')" 
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
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
          </svg>
          <span>{{ photoPreview ? 'Ganti Selfie' : 'Ambil Selfie' }}</span>
        </button>
      </div>
    </div>

    <!-- Tombol Utama Presensi -->
    <div class="space-y-3 pt-1">
      <!-- 0. Banner Jika Hari Libur -->
      <div
        v-if="isHoliday"
        class="w-full py-4 px-5 bg-amber-50 border border-amber-200/90 rounded-xl text-center space-y-1.5"
      >
        <div class="inline-flex items-center gap-1.5 text-amber-800 font-bold text-xs">
          <span>🏖️</span>
          <span>Presensi Tidak Aktif (Hari Libur)</span>
        </div>
        <p class="text-[11px] text-amber-700">
          {{ holidayReason || 'Hari ini adalah hari libur sekolah. Presensi tidak dibuka.' }}
        </p>
      </div>

      <!-- 1. Tombol Absen Masuk -->
      <button
        v-else-if="!todayAttendance?.jam_masuk"
        @click="emit('check-in')"
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
        @click="emit('check-out')"
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
          @click="emit('open-leave-modal')"
          class="font-bold text-emerald-700 hover:text-emerald-800 hover:underline cursor-pointer"
        >
          Ajukan Izin / Cuti &gt;
        </button>
      </div>
    </div>
  </div>
</template>
