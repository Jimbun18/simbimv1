<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '../../services/api'
import { useAuthStore } from '../../stores/auth'
import type { HomeroomStudent, HomeroomMeta } from './types'
import { getInitials, getPhotoUrl } from './types'

const props = defineProps<{
  activeDate: string
  initialClass?: any
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved'): void
  (e: 'notify', payload: { title: string; message: string; type: 'success' | 'warning' | 'error' }): void
}>()

const auth = useAuthStore()

const selectedHomeroomClass = ref<any>(props.initialClass || auth.primaryHomeroomClass)
const homeroomAttendanceDate = ref<string>(props.activeDate)
const homeroomStudents = ref<HomeroomStudent[]>([])
const homeroomAttendances = ref<Record<number, 'hadir' | 'izin' | 'sakit' | 'alpa'>>({})
const homeroomNotes = ref<Record<number, string>>({})
const loadingHomeroom = ref(false)
const savingHomeroom = ref(false)
const homeroomSearchQuery = ref('')
const homeroomMeta = ref<HomeroomMeta | null>(null)

const isHomeroomAttendanceLocked = computed(() => {
  return Boolean(homeroomMeta.value?.already_recorded && !auth.isAdmin)
})

const loadHomeroomStudents = async () => {
  if (!selectedHomeroomClass.value) return
  loadingHomeroom.value = true
  try {
    const res = await api.get('/attendance/students/session-sheet', {
      params: {
        class_id: selectedHomeroomClass.value.id,
        tanggal: homeroomAttendanceDate.value,
      },
    })
    if (res.data.success && res.data.data) {
      const data = res.data.data
      homeroomMeta.value = {
        already_recorded: data.already_recorded,
        recorded_count: data.recorded_count,
        recorded_at: data.recorded_at,
        updated_at: data.updated_at,
        recorded_by: data.recorded_by,
        summary: data.summary,
      }
      homeroomStudents.value = data.students || []
      homeroomAttendances.value = {}
      homeroomNotes.value = {}
      homeroomStudents.value.forEach((s: any) => {
        homeroomAttendances.value[s.student_id] = s.status || 'hadir'
        homeroomNotes.value[s.student_id] = s.keterangan || ''
      })
    }
  } catch (err: any) {
    console.error('Gagal memuat lembar presensi wali kelas', err)
    emit('notify', {
      title: 'Gagal Memuat Presensi',
      message: err.response?.data?.message || 'Terjadi kesalahan sistem.',
      type: 'error',
    })
  } finally {
    loadingHomeroom.value = false
  }
}

const switchHomeroomClass = async (cls: any) => {
  selectedHomeroomClass.value = cls
  await loadHomeroomStudents()
}

const setAllHomeroomStatus = (status: 'hadir' | 'izin' | 'sakit' | 'alpa') => {
  homeroomStudents.value.forEach((s) => {
    homeroomAttendances.value[s.student_id] = status
  })
}

const submitHomeroomAttendance = async () => {
  if (isHomeroomAttendanceLocked.value) {
    emit('notify', {
      title: 'Presensi Terkunci',
      message: 'Presensi hari ini sudah divalidasi dan tidak dapat diubah kembali oleh Wali Kelas. Tombol telah dinonaktifkan.',
      type: 'warning',
    })
    return
  }
  if (!selectedHomeroomClass.value || homeroomStudents.value.length === 0) return
  savingHomeroom.value = true
  try {
    const attendances = homeroomStudents.value.map((s) => ({
      student_id: s.student_id,
      status: homeroomAttendances.value[s.student_id] || 'hadir',
      keterangan: homeroomNotes.value[s.student_id] || null,
    }))

    const res = await api.post('/attendance/students', {
      class_id: selectedHomeroomClass.value.id,
      tanggal: homeroomAttendanceDate.value,
      attendances,
    })

    if (res.data.success) {
      emit('notify', {
        title: 'Presensi Harian Disimpan & Tervalidasi!',
        message: `Presensi ${homeroomStudents.value.length} siswa rombel ${selectedHomeroomClass.value.nama} berhasil dicatat oleh Wali Kelas dan langsung masuk ke monitoring kepala sekolah & admin.`,
        type: 'success',
      })
      emit('saved')
      emit('close')
    }
  } catch (err: any) {
    console.error('Gagal menyimpan presensi harian', err)
    emit('notify', {
      title: 'Gagal Menyimpan Presensi',
      message: err.response?.data?.message || 'Terjadi kesalahan sistem.',
      type: 'error',
    })
  } finally {
    savingHomeroom.value = false
  }
}

const filteredHomeroomStudents = computed(() => {
  if (!homeroomSearchQuery.value.trim()) return homeroomStudents.value
  const q = homeroomSearchQuery.value.toLowerCase()
  return homeroomStudents.value.filter(
    (s) => s.nama_lengkap.toLowerCase().includes(q) || (s.nis && s.nis.toLowerCase().includes(q))
  )
})

const homeroomStats = computed(() => {
  const list = homeroomStudents.value
  const hadir = list.filter((s) => (homeroomAttendances.value[s.student_id] || s.status) === 'hadir').length
  const izin = list.filter((s) => (homeroomAttendances.value[s.student_id] || s.status) === 'izin').length
  const sakit = list.filter((s) => (homeroomAttendances.value[s.student_id] || s.status) === 'sakit').length
  const alpa = list.filter((s) => (homeroomAttendances.value[s.student_id] || s.status) === 'alpa').length
  return { hadir, izin, sakit, alpa, total: list.length }
})

onMounted(() => {
  loadHomeroomStudents()
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
        <span class="text-slate-400 font-medium">Tanggal Presensi:</span>
        <input
          v-model="homeroomAttendanceDate"
          @change="loadHomeroomStudents"
          type="date"
          class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500"
        />
      </div>
    </div>

    <!-- Main Homeroom Attendance Card -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-xs space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded-full uppercase tracking-wider">
              Presensi Harian Wali Kelas
            </span>
            <span class="text-xs text-slate-400">1x Sehari</span>
          </div>
          <h2 class="text-xl font-black text-slate-900 mt-1">
            Rombel Kelas: {{ selectedHomeroomClass?.nama }}
          </h2>
          <p class="text-xs text-slate-500">
            Catat kehadiran harian siswa rombel binaan Anda. Rekap ini langsung terhubung ke buku induk dan rapor.
          </p>

          <!-- Switcher jika Wali Kelas memegang lebih dari 1 rombel -->
          <div v-if="auth.homeroomClasses.length > 1" class="mt-3 flex items-center gap-2 flex-wrap">
            <span class="text-[11px] text-slate-400 font-medium">Pilih Rombel:</span>
            <button
              v-for="c in auth.homeroomClasses"
              :key="c.id"
              @click="switchHomeroomClass(c)"
              :class="selectedHomeroomClass?.id === c.id ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
              class="px-3 py-1 rounded-lg text-xs transition cursor-pointer"
            >
              {{ c.nama }}
            </button>
          </div>
        </div>

        <!-- Quick Hadirkan Semua Button -->
        <div v-if="!isHomeroomAttendanceLocked" class="flex items-center gap-2">
          <button
            @click="setAllHomeroomStatus('hadir')"
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition shadow-md shadow-emerald-600/20 flex items-center gap-1.5 cursor-pointer"
          >
            <span>✓</span>
            <span>Hadirkan Semua</span>
          </button>
        </div>
        <div v-else class="flex items-center gap-2">
          <span class="px-3.5 py-1.5 bg-slate-100 text-slate-600 rounded-xl text-xs font-bold flex items-center gap-1.5 border border-slate-200 shadow-2xs">
            <span>🔒 Mode Lihat Rekap (Terkunci)</span>
          </span>
        </div>
      </div>

      <!-- Status Validasi Presensi Hari Ini untuk Lembar Input -->
      <div
        v-if="homeroomMeta?.already_recorded"
        class="p-4.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-950 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs"
      >
        <div class="flex items-start gap-3">
          <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black text-sm shrink-0">
            ✓
          </div>
          <div>
            <h4 class="font-bold text-xs sm:text-sm text-emerald-950 flex items-center gap-2 flex-wrap">
              <span>Presensi Rombel Ini Sudah Tervalidasi & Tercatat Hari Ini</span>
              <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-200 text-emerald-900 border border-emerald-300">
                🔒 TERKUNCI
              </span>
            </h4>
            <p class="text-xs text-emerald-800 mt-1 leading-relaxed">
              Tercatat pada <strong>{{ homeroomMeta.updated_at ? new Date(homeroomMeta.updated_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB' : '-' }}</strong> 
              oleh <strong>{{ homeroomMeta.recorded_by || 'Wali Kelas' }}</strong>. 
              <span v-if="!auth.isAdmin">
                Sesuai ketentuan, pengabsenan hanya dapat dilakukan 1 kali dalam sehari. Tombol simpan dan ubah telah <strong>dinonaktifkan</strong>. Jika terdapat kekeliruan data, silakan hubungi Administrator atau Kepala Sekolah.
              </span>
              <span v-else>
                Mode Administrator / Kepala Sekolah: Anda memiliki hak akses supervisi penuh untuk mengoreksi data presensi rombel ini.
              </span>
            </p>
          </div>
        </div>
        <span class="px-3 py-1 bg-emerald-600 text-white rounded-xl text-[10px] font-extrabold uppercase shrink-0 text-center self-start sm:self-center">
          Status: Sudah Diabsen
        </span>
      </div>

      <div
        v-else
        class="p-4.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-950 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs"
      >
        <div class="flex items-start gap-3">
          <div class="w-8 h-8 rounded-xl bg-amber-400 text-slate-950 flex items-center justify-center font-black text-sm shrink-0">
            ⚠️
          </div>
          <div>
            <h4 class="font-bold text-xs sm:text-sm text-amber-950">
              Presensi Rombel Ini Belum Dicatat untuk Tanggal Terpilih
            </h4>
            <p class="text-xs text-amber-800 mt-0.5 leading-relaxed">
              Silakan periksa dan sesuaikan kehadiran siswa di bawah ini, lalu klik <strong>Simpan Presensi Harian Siswa</strong>.
            </p>
          </div>
        </div>
        <span class="px-3 py-1 bg-amber-500 text-slate-950 rounded-xl text-[10px] font-extrabold uppercase shrink-0 text-center self-start sm:self-center">
          Status: Menunggu Pengisian
        </span>
      </div>

      <!-- Attendance Stats Chips Bar -->
      <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <span class="font-bold text-slate-700">Total: {{ homeroomStats.total }} Siswa</span>
          <span class="text-slate-300">•</span>
          <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 font-bold rounded-lg text-[11px]">
            Hadir: {{ homeroomStats.hadir }}
          </span>
          <span class="px-2.5 py-1 bg-amber-100 text-amber-800 font-bold rounded-lg text-[11px]">
            Izin: {{ homeroomStats.izin }}
          </span>
          <span class="px-2.5 py-1 bg-sky-100 text-sky-800 font-bold rounded-lg text-[11px]">
            Sakit: {{ homeroomStats.sakit }}
          </span>
          <span class="px-2.5 py-1 bg-rose-100 text-rose-800 font-bold rounded-lg text-[11px]">
            Alpa: {{ homeroomStats.alpa }}
          </span>
        </div>

        <!-- Search Filter -->
        <div class="relative w-full sm:w-56">
          <input
            v-model="homeroomSearchQuery"
            type="text"
            placeholder="Cari siswa..."
            class="w-full pl-8 pr-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs focus:outline-hidden focus:ring-1 focus:ring-emerald-500"
          />
          <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>

      <!-- Student Attendance List Table -->
      <div v-if="loadingHomeroom" class="py-16 text-center text-slate-400 text-xs">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-3 border-emerald-500 border-t-transparent mb-2"></div>
        <p>Memuat lembar presensi kelas binaan...</p>
      </div>

      <div v-else class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-2xs">
        <div class="divide-y divide-slate-100">
          <div
            v-for="(st, sIdx) in filteredHomeroomStudents"
            :key="st.student_id"
            class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/70 transition"
          >
            <!-- Student Identity -->
            <div class="flex items-center gap-3">
              <span class="text-[11px] font-mono text-slate-400 w-6 text-center">{{ sIdx + 1 }}</span>
              <div class="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
                <img
                  v-if="st.foto_path"
                  :src="getPhotoUrl(st.foto_path)"
                  :alt="st.nama_lengkap"
                  class="w-full h-full object-cover"
                  @error="(e: any) => e.target.style.display = 'none'"
                />
                <span v-else class="text-[11px] font-bold text-slate-500">{{ getInitials(st.nama_lengkap) }}</span>
              </div>
              <div>
                <h5 class="text-xs font-bold text-slate-900">{{ st.nama_lengkap }}</h5>
                <div class="flex items-center gap-2 text-[10px] text-slate-400 mt-0.5">
                  <span class="font-mono">NIS: {{ st.nis || '-' }}</span>
                  <span>•</span>
                  <span :class="st.jenis_kelamin === 'L' ? 'text-sky-600 font-semibold' : 'text-rose-600 font-semibold'">
                    {{ st.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Radio Pill Buttons & Notes -->
            <div class="flex items-center gap-2 flex-wrap self-end sm:self-center">
              <!-- Hadir -->
              <button
                type="button"
                :disabled="isHomeroomAttendanceLocked"
                @click="!isHomeroomAttendanceLocked && (homeroomAttendances[st.student_id] = 'hadir')"
                :class="[
                  homeroomAttendances[st.student_id] === 'hadir' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600',
                  isHomeroomAttendanceLocked ? 'cursor-not-allowed opacity-90' : 'hover:bg-emerald-50 hover:text-emerald-700 cursor-pointer'
                ]"
                class="px-3 py-1.5 rounded-xl text-xs transition"
              >
                Hadir
              </button>
              <!-- Izin -->
              <button
                type="button"
                :disabled="isHomeroomAttendanceLocked"
                @click="!isHomeroomAttendanceLocked && (homeroomAttendances[st.student_id] = 'izin')"
                :class="[
                  homeroomAttendances[st.student_id] === 'izin' ? 'bg-amber-500 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600',
                  isHomeroomAttendanceLocked ? 'cursor-not-allowed opacity-90' : 'hover:bg-amber-50 hover:text-amber-700 cursor-pointer'
                ]"
                class="px-3 py-1.5 rounded-xl text-xs transition"
              >
                Izin
              </button>
              <!-- Sakit -->
              <button
                type="button"
                :disabled="isHomeroomAttendanceLocked"
                @click="!isHomeroomAttendanceLocked && (homeroomAttendances[st.student_id] = 'sakit')"
                :class="[
                  homeroomAttendances[st.student_id] === 'sakit' ? 'bg-sky-500 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600',
                  isHomeroomAttendanceLocked ? 'cursor-not-allowed opacity-90' : 'hover:bg-sky-50 hover:text-sky-700 cursor-pointer'
                ]"
                class="px-3 py-1.5 rounded-xl text-xs transition"
              >
                Sakit
              </button>
              <!-- Alpa -->
              <button
                type="button"
                :disabled="isHomeroomAttendanceLocked"
                @click="!isHomeroomAttendanceLocked && (homeroomAttendances[st.student_id] = 'alpa')"
                :class="[
                  homeroomAttendances[st.student_id] === 'alpa' ? 'bg-rose-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600',
                  isHomeroomAttendanceLocked ? 'cursor-not-allowed opacity-90' : 'hover:bg-rose-50 hover:text-rose-700 cursor-pointer'
                ]"
                class="px-3 py-1.5 rounded-xl text-xs transition"
              >
                Alpa
              </button>

              <!-- Optional Note input if not Hadir -->
              <input
                v-if="homeroomAttendances[st.student_id] !== 'hadir'"
                v-model="homeroomNotes[st.student_id]"
                :disabled="isHomeroomAttendanceLocked"
                type="text"
                placeholder="Keterangan..."
                class="w-28 px-2.5 py-1 text-xs border border-slate-200 rounded-xl bg-white disabled:bg-slate-100 disabled:cursor-not-allowed disabled:text-slate-500"
              />
            </div>
          </div>

          <div v-if="filteredHomeroomStudents.length === 0" class="py-12 text-center text-slate-400 text-xs">
            Tidak ada data siswa yang cocok dengan pencarian.
          </div>
        </div>
      </div>

      <!-- Submit Button Footer -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-slate-100">
        <div v-if="isHomeroomAttendanceLocked" class="text-xs text-amber-800 font-semibold flex items-center gap-1.5 bg-amber-50 px-3 py-2 rounded-xl border border-amber-200">
          <span>🔒</span>
          <span>Presensi hari ini telah tervalidasi. Tombol dinonaktifkan (Pengabsenan hanya 1x sehari).</span>
        </div>
        <div v-else class="text-xs text-slate-400">
          Pastikan data kehadiran murid sudah sesuai sebelum menekan tombol simpan.
        </div>

        <div class="flex items-center gap-3 self-end sm:self-auto">
          <button
            type="button"
            @click="emit('close')"
            class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-xs font-bold hover:bg-slate-50 transition cursor-pointer"
          >
            {{ isHomeroomAttendanceLocked ? 'Tutup' : 'Batal' }}
          </button>
          <button
            v-if="isHomeroomAttendanceLocked"
            type="button"
            disabled
            class="px-6 py-2.5 bg-slate-200 text-slate-400 border border-slate-300 rounded-xl text-xs font-bold shadow-none flex items-center gap-2 cursor-not-allowed"
            title="Wali kelas sudah mengabsen hari ini. Tombol dinonaktifkan."
          >
            <span>🔒</span>
            <span>Presensi Sudah Divalidasi (Terkunci)</span>
          </button>
          <button
            v-else
            type="button"
            @click="submitHomeroomAttendance"
            :disabled="savingHomeroom || homeroomStudents.length === 0"
            class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/25 transition disabled:opacity-50 flex items-center gap-2 cursor-pointer"
          >
            <svg v-if="savingHomeroom" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span v-else>💾</span>
            <span>{{ savingHomeroom ? 'Menyimpan Presensi...' : 'Simpan Presensi Harian Siswa' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
