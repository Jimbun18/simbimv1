<script setup lang="ts">
import { ref, watch, onUnmounted, nextTick } from 'vue'

const props = defineProps<{
  isOpen: boolean
  type: 'masuk' | 'pulang'
  submitting: boolean
  distanceMeter: number | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'confirm', photoFile: File): void
}>()

const videoRef = ref<HTMLVideoElement | null>(null)
const canvasRef = ref<HTMLCanvasElement | null>(null)
const fileInputRef = ref<HTMLInputElement | null>(null)

const mediaStream = ref<MediaStream | null>(null)
const cameraLoading = ref(false)
const cameraError = ref<string | null>(null)
const isCaptured = ref(false)
const capturedPhotoUrl = ref<string | null>(null)
const capturedPhotoFile = ref<File | null>(null)
const facingMode = ref<'user' | 'environment'>('user')
const hasMultipleCameras = ref(false)

// Start camera stream
const startCamera = async () => {
  stopCamera()
  cameraLoading.value = true
  cameraError.value = null
  isCaptured.value = false
  capturedPhotoUrl.value = null
  capturedPhotoFile.value = null

  try {
    // Check available video devices
    if (navigator.mediaDevices?.enumerateDevices) {
      const devices = await navigator.mediaDevices.enumerateDevices()
      const videoDevices = devices.filter(d => d.kind === 'videoinput')
      hasMultipleCameras.value = videoDevices.length > 1
    }

    const constraints: MediaStreamConstraints = {
      video: {
        facingMode: facingMode.value,
        width: { ideal: 720 },
        height: { ideal: 720 },
      },
      audio: false,
    }

    const stream = await navigator.mediaDevices.getUserMedia(constraints)
    mediaStream.value = stream

    await nextTick()
    if (videoRef.value) {
      videoRef.value.srcObject = stream
      await videoRef.value.play()
    }
  } catch (err: any) {
    console.warn('Camera access error:', err)
    let msg = 'Tidak dapat mengakses kamera perangkat.'
    if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
      msg = 'Izin akses kamera ditolak. Silakan izinkan akses kamera pada browser Anda.'
    } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
      msg = 'Tidak ada perangkat kamera yang terdeteksi di perangkat Anda.'
    } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
      msg = 'Kamera sedang digunakan oleh aplikasi lain.'
    }
    cameraError.value = msg
  } finally {
    cameraLoading.value = false
  }
}

// Stop camera stream
const stopCamera = () => {
  if (mediaStream.value) {
    mediaStream.value.getTracks().forEach((track) => track.stop())
    mediaStream.value = null
  }
  if (videoRef.value) {
    videoRef.value.srcObject = null
  }
}

// Switch front / rear camera
const toggleCamera = () => {
  facingMode.value = facingMode.value === 'user' ? 'environment' : 'user'
  startCamera()
}

// Capture photo from video stream
const capturePhoto = () => {
  if (!videoRef.value) return

  const video = videoRef.value
  const canvas = canvasRef.value || document.createElement('canvas')
  canvas.width = video.videoWidth || 640
  canvas.height = video.videoHeight || 480

  const ctx = canvas.getContext('2d')
  if (!ctx) return

  // Flip horizontally if front camera for natural selfie look
  if (facingMode.value === 'user') {
    ctx.translate(canvas.width, 0)
    ctx.scale(-1, 1)
  }

  ctx.drawImage(video, 0, 0, canvas.width, canvas.height)

  canvas.toBlob((blob) => {
    if (!blob) return
    const filename = `selfie_${props.type}_${Date.now()}.jpg`
    const file = new File([blob], filename, { type: 'image/jpeg' })
    capturedPhotoFile.value = file
    capturedPhotoUrl.value = URL.createObjectURL(blob)
    isCaptured.value = true
    stopCamera()
  }, 'image/jpeg', 0.88)
}

// Retake photo
const retakePhoto = () => {
  if (capturedPhotoUrl.value) {
    URL.revokeObjectURL(capturedPhotoUrl.value)
  }
  capturedPhotoUrl.value = null
  capturedPhotoFile.value = null
  isCaptured.value = false
  startCamera()
}

// Handle fallback file upload
const handleFileInput = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    const file = target.files[0]
    capturedPhotoFile.value = file
    capturedPhotoUrl.value = URL.createObjectURL(file)
    isCaptured.value = true
    stopCamera()
  }
}

const triggerFileInput = () => {
  fileInputRef.value?.click()
}

// Confirm and submit
const handleConfirm = () => {
  if (!capturedPhotoFile.value) return
  emit('confirm', capturedPhotoFile.value)
}

const handleClose = () => {
  if (props.submitting) return
  stopCamera()
  if (capturedPhotoUrl.value) {
    URL.revokeObjectURL(capturedPhotoUrl.value)
  }
  emit('close')
}

// Watch modal open/close
watch(
  () => props.isOpen,
  (val) => {
    if (val) {
      startCamera()
    } else {
      stopCamera()
    }
  },
  { immediate: true }
)

onUnmounted(() => {
  stopCamera()
  if (capturedPhotoUrl.value) {
    URL.revokeObjectURL(capturedPhotoUrl.value)
  }
})
</script>

<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 bg-slate-900/75 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
  >
    <div
      class="bg-white rounded-3xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden flex flex-col transition-all my-auto"
    >
      <!-- MODAL HEADER -->
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
        <div class="flex items-center gap-2.5">
          <div
            class="w-9 h-9 rounded-2xl flex items-center justify-center text-white shadow-xs font-bold"
            :class="type === 'masuk' ? 'bg-emerald-600' : 'bg-teal-600'"
          >
            📸
          </div>
          <div>
            <h3 class="text-sm font-extrabold text-slate-900">
              {{ type === 'masuk' ? 'Foto Presensi Masuk' : 'Foto Presensi Pulang' }}
            </h3>
            <p class="text-[11px] text-slate-500">
              Validasi kehadiran di lingkungan sekolah
            </p>
          </div>
        </div>
        <button
          type="button"
          @click="handleClose"
          :disabled="submitting"
          class="w-8 h-8 rounded-full bg-slate-200/80 hover:bg-slate-300 text-slate-700 flex items-center justify-center font-bold text-sm transition cursor-pointer disabled:opacity-50"
        >
          &times;
        </button>
      </div>

      <!-- MODAL BODY: CAMERA / PREVIEW -->
      <div class="p-5 space-y-4">
        <!-- Geofence info badge -->
        <div class="flex items-center justify-between px-3 py-1.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-semibold text-emerald-800">
          <div class="flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Lokasi GPS Terverifikasi</span>
          </div>
          <span v-if="distanceMeter !== null" class="font-mono text-[11px]">
            Jarak: {{ distanceMeter }}m
          </span>
        </div>

        <!-- CAMERA CONTAINER -->
        <div class="relative w-full aspect-square rounded-2xl overflow-hidden bg-slate-900 shadow-inner flex items-center justify-center border-2 border-slate-200">
          <!-- LIVE VIDEO STREAM -->
          <video
            v-show="!isCaptured && !cameraLoading && !cameraError"
            ref="videoRef"
            autoplay
            playsinline
            muted
            class="w-full h-full object-cover"
            :class="facingMode === 'user' ? '-scale-x-100' : ''"
          ></video>

          <!-- CAPTURED PHOTO PREVIEW -->
          <img
            v-if="isCaptured && capturedPhotoUrl"
            :src="capturedPhotoUrl"
            alt="Hasil Selfie"
            class="w-full h-full object-cover"
          />

          <!-- OVAL FACE GUIDE (HANYA MUNCUL SAAT LIVE VIDEO) -->
          <div
            v-if="!isCaptured && !cameraLoading && !cameraError"
            class="absolute inset-0 pointer-events-none flex items-center justify-center"
          >
            <div class="w-48 h-64 border-2 border-white/60 border-dashed rounded-full shadow-[0_0_0_9999px_rgba(0,0,0,0.25)]"></div>
            <span class="absolute bottom-4 px-3 py-1 rounded-full bg-black/60 text-white text-[11px] font-medium backdrop-blur-xs">
              Posisikan wajah Anda di dalam lingkaran
            </span>
          </div>

          <!-- LOADING STATE -->
          <div v-if="cameraLoading" class="text-center p-6 space-y-2 text-white">
            <svg class="animate-spin h-8 w-8 mx-auto text-emerald-400" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-xs font-semibold">Mengaktifkan kamera...</p>
          </div>

          <!-- CAMERA ERROR / FALLBACK -->
          <div v-if="cameraError" class="text-center p-6 space-y-3 text-white max-w-xs">
            <span class="text-3xl block">📷⚠️</span>
            <p class="text-xs text-rose-200 leading-relaxed font-medium">
              {{ cameraError }}
            </p>
            <button
              type="button"
              @click="triggerFileInput"
              class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md transition cursor-pointer"
            >
              Unggah Foto dari Galeri / Kamera
            </button>
          </div>

          <!-- TOGGLE FRONT/BACK CAMERA BUTTON -->
          <button
            v-if="!isCaptured && hasMultipleCameras && !cameraLoading && !cameraError"
            type="button"
            @click="toggleCamera"
            class="absolute top-3 right-3 p-2 rounded-full bg-black/50 hover:bg-black/75 text-white transition backdrop-blur-xs cursor-pointer shadow-md"
            title="Ganti Kamera Depan/Belakang"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>
        </div>

        <input
          ref="fileInputRef"
          type="file"
          accept="image/*"
          capture="user"
          class="hidden"
          @change="handleFileInput"
        />

        <!-- INSTRUCTION -->
        <p class="text-[11px] text-slate-500 text-center">
          <span v-if="!isCaptured">Pastikan wajah terlihat jelas dan berada di lingkungan sekolah SDI BIM.</span>
          <span v-else class="text-emerald-700 font-semibold">Foto siap dikirim. Klik konfirmasi untuk menyelesaikan presensi.</span>
        </p>
      </div>

      <!-- MODAL FOOTER ACTIONS -->
      <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center gap-2 justify-end">
        <!-- MODE 1: LIVE VIDEO (SHUTTER) -->
        <template v-if="!isCaptured">
          <button
            type="button"
            @click="triggerFileInput"
            class="px-3.5 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition cursor-pointer"
            title="Pilih file foto manual"
          >
            Pilih File
          </button>

          <button
            type="button"
            @click="capturePhoto"
            :disabled="cameraLoading || !!cameraError"
            class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-md transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
          >
            <span class="text-base">📸</span>
            <span>Ambil Foto Kehadiran</span>
          </button>
        </template>

        <!-- MODE 2: CAPTURED (CONFIRMATION) -->
        <template v-else>
          <button
            type="button"
            @click="retakePhoto"
            :disabled="submitting"
            class="px-4 py-2.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            <span>🔄</span>
            <span>Foto Ulang</span>
          </button>

          <button
            type="button"
            @click="handleConfirm"
            :disabled="submitting"
            class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-lg shadow-emerald-600/25 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
          >
            <svg v-if="submitting" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span v-else class="text-base">✓</span>
            <span>{{ submitting ? 'Mengirim Presensi...' : 'Konfirmasi Kirim Presensi' }}</span>
          </button>
        </template>
      </div>
    </div>
  </div>
</template>
