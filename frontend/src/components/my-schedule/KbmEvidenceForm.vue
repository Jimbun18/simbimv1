<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '../../services/api'
import { useAuthStore } from '../../stores/auth'
import type { MergedScheduleItem } from './types'
import { isTodaySchedule } from './types'

const props = defineProps<{
  activeSchedule: MergedScheduleItem
  activeDate: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved'): void
  (e: 'notify', payload: { title: string; message: string; type: 'success' | 'warning' | 'error' }): void
}>()

const auth = useAuthStore()

const attendanceDate = ref<string>(props.activeDate)
const requireEvidence = ref(true)
const existingEvidence = ref<any>(null)
const evidencePhotoFile = ref<File | null>(null)
const evidencePhotoPreview = ref<string>('')
const evidenceMateriAjar = ref('')
const evidenceCatatan = ref('')
const savingEvidence = ref(false)
const loadingEvidence = ref(false)

const handleEvidencePhotoChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    const file = target.files[0]
    evidencePhotoFile.value = file
    evidencePhotoPreview.value = URL.createObjectURL(file)
  }
}

const loadExistingEvidence = async () => {
  loadingEvidence.value = true
  try {
    const targetIds = (props.activeSchedule.schedule_ids && props.activeSchedule.schedule_ids.length > 0)
      ? props.activeSchedule.schedule_ids
      : [props.activeSchedule.id]

    let foundEvidence: any = null
    for (const sid of targetIds) {
      const res = await api.get('/attendance/evidence', {
        params: {
          schedule_id: sid,
          tanggal: attendanceDate.value,
        },
      })

      if (res.data.success && res.data.data && res.data.data.length > 0) {
        foundEvidence = res.data.data[0]
        break
      }
    }

    if (foundEvidence) {
      existingEvidence.value = foundEvidence
      evidenceMateriAjar.value = foundEvidence.materi_ajar || ''
      evidenceCatatan.value = foundEvidence.catatan || ''
      if (foundEvidence.photo_path) {
        evidencePhotoPreview.value = foundEvidence.photo_path.startsWith('http')
          ? foundEvidence.photo_path
          : `/storage/${foundEvidence.photo_path}`
      }
    }
  } catch (err: any) {
    console.error('Gagal memuat bukti KBM', err)
  } finally {
    loadingEvidence.value = false
  }
}

const submitKbmEvidence = async () => {
  if (!props.activeSchedule) return

  // Validasi: Bukti KBM hanya boleh disimpan pada hari yang sesuai dengan jadwal
  if (!isTodaySchedule(props.activeSchedule, attendanceDate.value) && !auth.isAdmin) {
    emit('notify', {
      title: 'Pengunggahan Tidak Diizinkan',
      message: `Bukti KBM dan jurnal hanya dapat disimpan pada hari yang sesuai dengan jadwal (${props.activeSchedule.hari.toUpperCase()}).`,
      type: 'error',
    })
    return
  }

  if (requireEvidence.value && !existingEvidence.value && !evidencePhotoFile.value) {
    emit('notify', {
      title: 'Foto Bukti Mengajar Wajib',
      message: 'Silakan lampirkan foto suasana belajar / aktivitas kelas sebagai bukti KBM tatap muka.',
      type: 'warning',
    })
    return
  }

  savingEvidence.value = true
  try {
    const targetIds = (props.activeSchedule.schedule_ids && props.activeSchedule.schedule_ids.length > 0)
      ? props.activeSchedule.schedule_ids
      : [props.activeSchedule.id]

    // Simpan bukti KBM untuk semua sesi yang digabung (misal 2x35m sekaligus)
    await Promise.all(
      targetIds.map(async (schId) => {
        const formData = new FormData()
        formData.append('schedule_id', String(schId))
        formData.append('tanggal', attendanceDate.value)
        if (evidencePhotoFile.value) {
          formData.append('foto', evidencePhotoFile.value)
        }
        if (evidenceMateriAjar.value) formData.append('materi_ajar', evidenceMateriAjar.value)
        if (evidenceCatatan.value) formData.append('catatan', evidenceCatatan.value)

        return api.post('/attendance/evidence', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
      })
    )

    emit('notify', {
      title: 'Bukti KBM & Jurnal Berhasil Disimpan!',
      message: `Dokumentasi KBM untuk kelas ${props.activeSchedule.school_class?.nama} - ${props.activeSchedule.subject?.nama} (${props.activeSchedule.durasi_label}) telah tersimpan.`,
      type: 'success',
    })
    emit('saved')
    emit('close')
  } catch (err: any) {
    console.error('Gagal menyimpan bukti KBM', err)
    emit('notify', {
      title: 'Gagal Menyimpan Bukti KBM',
      message: err.response?.data?.message || 'Terjadi kendala sistem.',
      type: 'error',
    })
  } finally {
    savingEvidence.value = false
  }
}

onMounted(() => {
  loadExistingEvidence()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Top Action Bar -->
    <div class="flex items-center justify-between bg-white p-4 sm:p-5 rounded-3xl border border-slate-100 shadow-xs">
      <button
        @click="emit('close')"
        class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl transition cursor-pointer"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        <span>Kembali ke Jadwal</span>
      </button>
      <div class="flex items-center gap-2 text-xs">
        <span class="text-slate-400 font-medium">Tanggal KBM:</span>
        <input
          v-model="attendanceDate"
          type="date"
          :disabled="!auth.isAdmin"
          class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 disabled:opacity-75 disabled:cursor-not-allowed"
        />
      </div>
    </div>

    <!-- Main Evidence & Journal Form Card -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-xs space-y-6">
      <!-- Schedule Details Header Banner -->
      <div class="p-5 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 text-white flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="px-2.5 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-full text-[10px] font-bold uppercase tracking-wider">
              Dokumentasi KBM
            </span>
            <span class="text-xs text-slate-300">
              Ruang: {{ activeSchedule.room?.nama || 'Ruang Belajar' }}
            </span>
          </div>
          <h2 class="text-xl font-black text-white tracking-tight flex items-center gap-2 flex-wrap">
            <span>{{ activeSchedule.subject?.nama }} — {{ activeSchedule.school_class?.nama }}</span>
            <span
              v-if="activeSchedule.subject?.is_tematik"
              class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-purple-500/25 text-purple-300 border border-purple-400/30"
            >
              TEMATIK TEMA {{ activeSchedule.subject?.tema_nomor }}
            </span>
          </h2>
          <p class="text-xs text-slate-300">
            Guru Pengampu: <strong class="text-white">{{ activeSchedule.teacher?.nama_lengkap }}</strong> •
            Sesi: <strong>{{ activeSchedule.sesi_label }}</strong> ({{ activeSchedule.jam_mulai_efektif }} - {{ activeSchedule.jam_selesai_efektif }})
          </p>
        </div>
        <div class="shrink-0 flex items-center gap-2">
          <span
            v-if="existingEvidence || evidencePhotoPreview"
            class="px-3 py-1 bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 rounded-full text-xs font-bold flex items-center gap-1.5"
          >
            <span>✓</span>
            <span>Bukti Tersimpan</span>
          </span>
        </div>
      </div>

      <!-- Form Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Left: Photo Upload & Preview -->
        <div class="space-y-3">
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
            Foto Suasana Kelas / Kegiatan KBM *
          </label>
          <div
            class="border-2 border-dashed border-slate-300 rounded-3xl p-4 text-center bg-slate-50 hover:bg-slate-100/70 hover:border-emerald-500 transition relative overflow-hidden flex flex-col items-center justify-center min-h-[220px]"
          >
            <img
              v-if="evidencePhotoPreview"
              :src="evidencePhotoPreview"
              alt="Preview Bukti Mengajar"
              class="w-full h-56 object-cover rounded-2xl shadow-xs"
            />
            <div v-else class="space-y-2 p-4">
              <div class="w-14 h-14 bg-emerald-100 text-emerald-700 rounded-2xl flex items-center justify-center text-2xl mx-auto">
                📸
              </div>
              <span class="text-xs font-bold text-slate-800 block">Ambil / Unggah Foto Bukti Kelas</span>
              <span class="text-[11px] text-slate-400 block max-w-xs mx-auto">
                Lampirkan foto suasana belajar mengajar tatap muka sebagai bukti fisik pelaksanaan KBM.
              </span>
              <span class="inline-block px-3 py-1 bg-white border border-slate-200 rounded-xl text-[11px] font-semibold text-slate-600 shadow-2xs">
                Pilih File (JPG, PNG, WebP maks 5MB)
              </span>
            </div>
            <input
              type="file"
              accept="image/*"
              capture="environment"
              @change="handleEvidencePhotoChange"
              class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
            />
          </div>
        </div>

        <!-- Right: Materi & Catatan Jurnal -->
        <div class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">
              Pokok Bahasan / Materi Ajar *
            </label>
            <input
              v-model="evidenceMateriAjar"
              type="text"
              placeholder="Contoh: Bab 2 - Perkalian & Pembagian / Surah At-Tin Ayat 1-4"
              class="w-full border border-slate-300 rounded-2xl px-4 py-3 text-xs font-semibold focus:ring-2 focus:ring-emerald-500 bg-white shadow-2xs"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">
              Catatan KBM / Evaluasi Pembelajaran
            </label>
            <textarea
              v-model="evidenceCatatan"
              rows="5"
              placeholder="Contoh: Siswa aktif berdiskusi kelompok, evaluasi tugas berjalan tertib, materi tersampaikan sesuai RPP..."
              class="w-full border border-slate-300 rounded-2xl p-4 text-xs font-medium focus:ring-2 focus:ring-emerald-500 bg-white shadow-2xs resize-none"
            ></textarea>
          </div>
          <div class="p-3.5 bg-emerald-50 rounded-2xl border border-emerald-200/80 text-xs text-emerald-800 flex items-start gap-2.5">
            <span class="text-base shrink-0">ℹ️</span>
            <p class="text-[11px] leading-relaxed">
              Presensi kehadiran siswa dilakukan sekali sehari oleh Wali Kelas masing-masing. Guru mapel cukup mengunggah foto bukti KBM dan jurnal materi ajar di atas.
            </p>
          </div>
        </div>
      </div>

      <!-- Action Footer -->
      <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
        <button
          type="button"
          @click="emit('close')"
          class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-xs font-bold hover:bg-slate-50 transition cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          @click="submitKbmEvidence"
          :disabled="savingEvidence || loadingEvidence"
          class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/25 transition disabled:opacity-50 flex items-center gap-2 cursor-pointer"
        >
          <svg v-if="savingEvidence" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <span v-else>💾</span>
          <span>{{ savingEvidence ? 'Menyimpan Bukti KBM...' : 'Simpan Bukti KBM & Jurnal' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>
