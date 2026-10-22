<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import api from '../../services/api'
import { useAuthStore } from '../../stores/auth'

const props = defineProps<{
  classes: any[]
  selectedDate: string
  initialClassId?: string
}>()

const emit = defineEmits<{
  (e: 'notify', text: string, type?: 'success' | 'error' | 'warning'): void
  (e: 'saved'): void
}>()

const auth = useAuthStore()

const inputClassId = ref(props.initialClassId || '')
const inputStudentList = ref<any[]>([])
const inputAttendances = ref<Record<number, 'hadir' | 'izin' | 'sakit' | 'alpa'>>({})
const inputNotes = ref<Record<number, string>>({})
const savingStudentAttendance = ref(false)
const loading = ref(false)
const manualAttendanceMeta = ref<any>(null)

const isMyHomeroom = (classId: number | string) => {
  return auth.homeroomClasses.some(h => String(h.id) === String(classId))
}

const isManualAttendanceLocked = computed(() => {
  return Boolean(manualAttendanceMeta.value?.already_recorded && !auth.isAdmin)
})

const loadStudentsForManualInput = async () => {
  if (!inputClassId.value) return
  loading.value = true
  try {
    const res = await api.get('/attendance/students/session-sheet', {
      params: {
        class_id: inputClassId.value,
        tanggal: props.selectedDate,
      }
    })

    if (res.data.success && res.data.data) {
      const data = res.data.data
      manualAttendanceMeta.value = {
        already_recorded: data.already_recorded,
        recorded_by: data.recorded_by,
        recorded_at: data.recorded_at,
        updated_at: data.updated_at,
        summary: data.summary,
      }
      inputStudentList.value = data.students || []
      inputAttendances.value = {}
      inputNotes.value = {}

      inputStudentList.value.forEach((s: any) => {
        inputAttendances.value[s.student_id] = s.status || 'hadir'
        inputNotes.value[s.student_id] = s.keterangan || ''
      })
    }
  } catch (err: any) {
    console.error('Failed to load students for homeroom attendance:', err)
  } finally {
    loading.value = false
  }
}

const submitManualStudentAttendance = async () => {
  if (isManualAttendanceLocked.value) {
    emit('notify', 'Presensi hari ini sudah divalidasi dan tidak dapat diubah kembali oleh Wali Kelas. Tombol telah dinonaktifkan.', 'error')
    return
  }
  if (!inputClassId.value) {
    emit('notify', 'Pilih rombel kelas terlebih dahulu', 'error')
    return
  }
  savingStudentAttendance.value = true
  try {
    const attendances = inputStudentList.value.map((s: any) => ({
      student_id: s.student_id,
      status: inputAttendances.value[s.student_id] || 'hadir',
      keterangan: inputNotes.value[s.student_id] || null,
    }))

    const payload: any = {
      class_id: inputClassId.value,
      tanggal: props.selectedDate,
      attendances,
    }

    await api.post('/attendance/students', payload)

    emit('notify', 'Presensi harian siswa berhasil disimpan oleh Wali Kelas!', 'success')
    emit('saved')
    await loadStudentsForManualInput()
  } catch (err: any) {
    console.error('Gagal menyimpan presensi siswa', err)
    emit('notify', err.response?.data?.message || 'Gagal menyimpan presensi siswa', 'error')
  } finally {
    savingStudentAttendance.value = false
  }
}

const setAllManualStatus = (status: 'hadir' | 'izin' | 'sakit' | 'alpa') => {
  inputStudentList.value.forEach((s: any) => {
    inputAttendances.value[s.student_id] = status
  })
}

watch(() => props.selectedDate, () => {
  loadStudentsForManualInput()
})

watch(() => props.initialClassId, (newId) => {
  if (newId) {
    inputClassId.value = newId
    loadStudentsForManualInput()
  }
})

onMounted(() => {
  if (!inputClassId.value && props.classes.length > 0) {
    if (auth.isWaliKelas && auth.primaryHomeroomClass) {
      inputClassId.value = String(auth.primaryHomeroomClass.id)
    } else {
      inputClassId.value = String(props.classes[0].id)
    }
  }
  if (inputClassId.value) {
    loadStudentsForManualInput()
  }
})

defineExpose({
  loadStudentsForManualInput,
  setInputClassId: (id: string) => {
    inputClassId.value = id
    loadStudentsForManualInput()
  }
})
</script>

<template>
  <div class="space-y-6">
    <!-- Form Presensi Harian Siswa (Khusus Wali Kelas & Admin) -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-7 space-y-6">
      <div class="space-y-1">
        <div class="flex items-center gap-2">
          <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded-full uppercase tracking-wider">
            Khusus Wali Kelas & Admin
          </span>
          <span class="text-xs text-slate-400">1 Kali Sehari</span>
        </div>
        <h3 class="font-bold text-slate-900 text-base">Presensi Harian Siswa Rombel</h3>
        <p class="text-xs text-slate-500">
          Formulir pencatatan absensi harian siswa oleh Wali Kelas. Data ini menjadi rekap resmi kehadiran siswa untuk sekolah dan rapor.
        </p>
      </div>

      <!-- Status Validasi Presensi Hari Ini untuk Manual Input -->
      <div
        v-if="manualAttendanceMeta?.already_recorded"
        class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-950 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs"
      >
        <div class="flex items-start gap-3">
          <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black text-sm shrink-0">
            ✓
          </div>
          <div>
            <h4 class="font-bold text-xs sm:text-sm text-emerald-950 flex items-center gap-2 flex-wrap">
              <span>Presensi Rombel Ini Sudah Tervalidasi</span>
              <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-200 text-emerald-900 border border-emerald-300">
                🔒 TERKUNCI (1x SEHARI)
              </span>
            </h4>
            <p class="text-xs text-emerald-800 mt-1">
              Tercatat pada <strong>{{ manualAttendanceMeta.updated_at ? new Date(manualAttendanceMeta.updated_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB' : '-' }}</strong> 
              oleh <strong>{{ manualAttendanceMeta.recorded_by || 'Wali Kelas' }}</strong>. 
              <span v-if="!auth.isAdmin">
                Tombol pengabsenan dinonaktifkan karena wali kelas tidak dapat mengabsen untuk kedua kalinya. Hubungi Admin jika butuh perbaikan.
              </span>
              <span v-else>
                Mode Administrator / Kepala Sekolah: Anda memiliki hak supervisi untuk memperbarui data jika diperlukan.
              </span>
            </p>
          </div>
        </div>
        <span class="px-3 py-1 bg-emerald-600 text-white rounded-xl text-[10px] font-extrabold uppercase shrink-0 text-center self-start sm:self-center">
          Sudah Diabsen
        </span>
      </div>

      <!-- Selection Header Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-200/80 items-end">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Rombel Kelas Binaan *</label>
          <select 
            v-model="inputClassId" 
            @change="loadStudentsForManualInput"
            class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-emerald-500 bg-white"
          >
            <option v-for="c in props.classes" :key="c.id" :value="String(c.id)">
              {{ c.nama }} (Tingkat {{ c.tingkat }}) {{ isMyHomeroom(c.id) ? ' ⭐ (Binaan Saya)' : '' }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Absensi Harian</label>
          <input
            type="date"
            :value="props.selectedDate"
            disabled
            class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold bg-slate-100 text-slate-600 cursor-not-allowed"
          />
        </div>
        <div>
          <button 
            v-if="isManualAttendanceLocked"
            disabled
            class="w-full bg-slate-200 text-slate-400 font-bold py-2.5 px-4 rounded-xl border border-slate-300 transition text-xs flex items-center justify-center gap-2 cursor-not-allowed shadow-none"
            title="Presensi sudah divalidasi hari ini. Tombol dinonaktifkan."
          >
            <span>🔒 Presensi Terkunci</span>
          </button>
          <button 
            v-else
            @click="submitManualStudentAttendance"
            :disabled="savingStudentAttendance || inputStudentList.length === 0"
            class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl shadow-md shadow-emerald-600/20 transition disabled:opacity-50 text-xs flex items-center justify-center gap-2 cursor-pointer"
          >
            <span v-if="savingStudentAttendance">Menyimpan Presensi...</span>
            <span v-else>💾 Simpan Presensi Harian</span>
          </button>
        </div>
      </div>

      <!-- Quick Set Buttons -->
      <div v-if="!isManualAttendanceLocked" class="flex flex-wrap items-center justify-between gap-3 text-xs bg-slate-50/50 p-3 rounded-xl border border-slate-100">
        <div class="flex items-center gap-2">
          <span class="font-bold text-slate-600">Set Cepat:</span>
          <button 
            type="button"
            @click="setAllManualStatus('hadir')"
            class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold px-3 py-1 rounded-lg border border-emerald-200 transition cursor-pointer"
          >
            Hadir Semua (H)
          </button>
          <button 
            type="button"
            @click="setAllManualStatus('sakit')"
            class="bg-sky-50 hover:bg-sky-100 text-sky-800 font-bold px-3 py-1 rounded-lg border border-sky-200 transition cursor-pointer"
          >
            Sakit Semua (S)
          </button>
        </div>
        <span class="text-slate-400 font-mono text-[11px]">{{ inputStudentList.length }} Siswa di Rombel</span>
      </div>
      <div v-else class="flex items-center justify-between gap-3 text-xs bg-slate-50/70 p-3 rounded-xl border border-slate-200 text-slate-500">
        <span class="flex items-center gap-1.5 font-medium">
          <span>🔒</span>
          <span>Mode Hanya Lihat: Presensi rombel ini telah selesai divalidasi dan terkunci.</span>
        </span>
        <span class="font-mono text-[11px] text-slate-400">{{ inputStudentList.length }} Siswa di Rombel</span>
      </div>

      <!-- Student Input Table -->
      <div class="overflow-x-auto border border-slate-200 rounded-2xl">
        <table class="w-full text-left border-collapse text-xs min-w-[700px]">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase text-[11px]">
              <th class="p-3.5 w-12 text-center">No</th>
              <th class="p-3.5">Nama Siswa</th>
              <th class="p-3.5">NISN / NIS</th>
              <th class="p-3.5 text-center">Status Kehadiran</th>
              <th class="p-3.5">Catatan / Keterangan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="loading">
              <td colspan="5" class="text-center py-8 text-slate-400">Memuat data siswa kelas...</td>
            </tr>
            <tr v-else-if="inputStudentList.length === 0">
              <td colspan="5" class="text-center py-8 text-slate-400">Belum ada siswa terdaftar di rombel ini.</td>
            </tr>
            <tr v-for="(student, idx) in inputStudentList" :key="student.student_id" class="hover:bg-slate-50/70">
              <td class="p-3.5 text-center font-mono text-slate-400">{{ idx + 1 }}</td>
              <td class="p-3.5 font-bold text-slate-900">
                {{ student.nama_lengkap }}
              </td>
              <td class="p-3.5 font-mono text-slate-400 text-xs">{{ student.nisn || student.nis || '-' }}</td>
              <td class="p-3.5">
                <div class="flex justify-center gap-1.5">
                  <label 
                    class="px-2.5 py-1 rounded-lg text-xs font-bold border transition-all"
                    :class="[
                      inputAttendances[student.student_id] === 'hadir' ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs' : 'bg-slate-50 text-slate-600 border-slate-200',
                      isManualAttendanceLocked ? 'cursor-not-allowed opacity-90' : 'cursor-pointer hover:bg-slate-100'
                    ]"
                  >
                    <input type="radio" :disabled="isManualAttendanceLocked" :name="'att-' + student.student_id" value="hadir" v-model="inputAttendances[student.student_id]" class="hidden" />
                    Hadir
                  </label>
                  <label 
                    class="px-2.5 py-1 rounded-lg text-xs font-bold border transition-all"
                    :class="[
                      inputAttendances[student.student_id] === 'izin' ? 'bg-amber-500 text-white border-amber-500 shadow-xs' : 'bg-slate-50 text-slate-600 border-slate-200',
                      isManualAttendanceLocked ? 'cursor-not-allowed opacity-90' : 'cursor-pointer hover:bg-slate-100'
                    ]"
                  >
                    <input type="radio" :disabled="isManualAttendanceLocked" :name="'att-' + student.student_id" value="izin" v-model="inputAttendances[student.student_id]" class="hidden" />
                    Izin
                  </label>
                  <label 
                    class="px-2.5 py-1 rounded-lg text-xs font-bold border transition-all"
                    :class="[
                      inputAttendances[student.student_id] === 'sakit' ? 'bg-sky-500 text-white border-sky-500 shadow-xs' : 'bg-slate-50 text-slate-600 border-slate-200',
                      isManualAttendanceLocked ? 'cursor-not-allowed opacity-90' : 'cursor-pointer hover:bg-slate-100'
                    ]"
                  >
                    <input type="radio" :disabled="isManualAttendanceLocked" :name="'att-' + student.student_id" value="sakit" v-model="inputAttendances[student.student_id]" class="hidden" />
                    Sakit
                  </label>
                  <label 
                    class="px-2.5 py-1 rounded-lg text-xs font-bold border transition-all"
                    :class="[
                      inputAttendances[student.student_id] === 'alpa' ? 'bg-rose-600 text-white border-rose-600 shadow-xs' : 'bg-slate-50 text-slate-600 border-slate-200',
                      isManualAttendanceLocked ? 'cursor-not-allowed opacity-90' : 'cursor-pointer hover:bg-slate-100'
                    ]"
                  >
                    <input type="radio" :disabled="isManualAttendanceLocked" :name="'att-' + student.student_id" value="alpa" v-model="inputAttendances[student.student_id]" class="hidden" />
                    Alpa
                  </label>
                </div>
              </td>
              <td class="p-3.5">
                <input
                  v-model="inputNotes[student.student_id]"
                  :disabled="isManualAttendanceLocked"
                  type="text"
                  placeholder="Keterangan..."
                  class="w-full border border-slate-200 rounded-lg px-2.5 py-1 text-xs bg-white disabled:bg-slate-100 disabled:cursor-not-allowed disabled:text-slate-500"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
