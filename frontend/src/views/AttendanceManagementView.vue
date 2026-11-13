<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
import ClassRecapTab from '../components/attendance-management/ClassRecapTab.vue'
import StudentRecapTab from '../components/attendance-management/StudentRecapTab.vue'
import KbmGalleryTab from '../components/attendance-management/KbmGalleryTab.vue'
import ManualInputTab from '../components/attendance-management/ManualInputTab.vue'
import TeacherAttendanceTab from '../components/attendance-management/TeacherAttendanceTab.vue'
import InfoTooltip from '../components/common/InfoTooltip.vue'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const activeTab = ref<'class_recap' | 'student_recap' | 'teachers' | 'manual_input' | 'kbm'>('class_recap')
const loading = ref(false)
const message = ref<{ type: 'success' | 'error' | 'warning'; text: string } | null>(null)

// Date & Filter State
const selectedDate = ref(new Date().toISOString().split('T')[0])
const manualInputClassId = ref<string>('')
const selectedClassId = ref<string>('')

// Master Data Dropdowns
const classes = ref<any[]>([])
const teachersList = ref<any[]>([])
const subjects = ref<any[]>([])

// Helper Notification
const showMessage = (text: string, type: 'success' | 'error' | 'warning' = 'success') => {
  message.value = { type, text }
  setTimeout(() => (message.value = null), 4500)
}

const handleSwitchTab = (tab: string, classId?: string) => {
  if (classId) {
    manualInputClassId.value = classId
  }
  activeTab.value = tab as any
  router.replace({ query: { ...route.query, tab } })
}

const handleManualInputSaved = () => {
  // Additional sync if needed
}

// Fetch Master Classes & Subjects
const fetchMasterData = async () => {
  loading.value = true
  try {
    const [cRes, sRes, tRes] = await Promise.all([
      api.get('/classes?all=true'),
      api.get('/subjects?all=true'),
      auth.isAdmin ? api.get('/teachers') : Promise.resolve({ data: { data: [] } }),
    ])
    classes.value = cRes.data.data || []
    subjects.value = sRes.data.data || []
    teachersList.value = tRes.data.data || []

    if (auth.isWaliKelas && auth.primaryHomeroomClass) {
      selectedClassId.value = String(auth.primaryHomeroomClass.id)
      manualInputClassId.value = String(auth.primaryHomeroomClass.id)
    } else if (classes.value.length > 0) {
      selectedClassId.value = String(classes.value[0].id)
      manualInputClassId.value = String(classes.value[0].id)
    }
  } catch (err) {
    console.error('Failed to load master data:', err)
  } finally {
    loading.value = false
  }
}

watch(() => route.query.tab, (newTab) => {
  if (newTab && ['class_recap', 'student_recap', 'teachers', 'manual_input', 'kbm'].includes(newTab as string)) {
    if (activeTab.value !== newTab) {
      activeTab.value = newTab as any
    }
  }
})

onMounted(async () => {
  if (route.query.tab && ['class_recap', 'student_recap', 'teachers', 'manual_input', 'kbm'].includes(route.query.tab as string)) {
    activeTab.value = route.query.tab as any
  }
  await fetchMasterData()
})
</script>

<template>
  <div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Header Minimalis & Rapi -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-100 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-2.5">
        <h1 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">
          {{ activeTab === 'class_recap' ? 'Rekap Kelas' : (activeTab === 'teachers' ? 'Presensi Guru & Tendik' : (activeTab === 'student_recap' ? 'Log Presensi Harian' : (activeTab === 'kbm' ? 'Bukti KBM & Jurnal Guru' : 'Presensi Harian Siswa'))) }}
        </h1>
        <InfoTooltip 
          :text="activeTab === 'class_recap' 
            ? 'Buku rekapitulasi kehadiran siswa per rombel: pantau akumulasi hadir, sakit, izin, alpa, dan persentase kehadiran untuk rapor.'
            : (activeTab === 'teachers'
              ? 'Monitoring absensi mandiri guru & tendik: check-in/out GPS, foto selfie, dan persentase kehadiran harian.'
              : 'Pemantauan absensi terpadu: verifikasi foto bukti KBM tatap muka, jurnal materi mengajar, dan presensi harian siswa.')" 
        />
      </div>

      <!-- Date Filter in Top Bar -->
      <div v-if="activeTab !== 'class_recap'" class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-xl border border-slate-200/80 shrink-0">
        <label class="text-xs font-semibold text-slate-600 pl-1.5">Tanggal:</label>
        <input 
          type="date" 
          v-model="selectedDate" 
          class="border border-slate-200 rounded-lg px-2.5 py-1 text-xs font-medium bg-white text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
        />
      </div>
    </div>

    <!-- Alert Message -->
    <transition
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="message" 
        :class="message.type === 'success' ? 'bg-emerald-50 text-emerald-900 border-emerald-200' : 'bg-rose-50 text-rose-900 border-rose-200'"
        class="p-4 rounded-2xl border text-xs font-medium flex items-center justify-between shadow-sm transition-all"
      >
        <div class="flex items-center gap-2">
          <span class="text-base">{{ message.type === 'success' ? '✓' : '⚠️' }}</span>
          <span>{{ message.text }}</span>
        </div>
        <button @click="message = null" class="font-bold opacity-60 hover:opacity-100 text-sm cursor-pointer">&times;</button>
      </div>
    </transition>

    <!-- Alert Khusus Guru Jika Belum Memiliki Kelas Binaan / Jadwal KBM -->
    <div 
      v-if="!auth.isAdmin && classes.length === 0 && !loading"
      class="bg-amber-50 border border-amber-200 text-amber-800 p-5 rounded-2xl flex items-start gap-3.5 text-xs shadow-xs"
    >
      <span class="text-2xl shrink-0">📋</span>
      <div>
        <h4 class="font-bold text-amber-900 text-sm">Tidak Ada Rombel Kelas yang Diampu</h4>
        <p class="mt-1 text-amber-700 leading-relaxed">
          Akun Anda saat ini belum tercatat sebagai wali kelas maupun guru pengampu jadwal KBM pada tahun ajaran ini. 
          Sistem hanya menampilkan rekap presensi untuk kelas yang Anda ampu. Silakan hubungi bagian kurikulum atau staf admin sekolah jika jadwal Anda belum ditugaskan.
        </p>
      </div>
    </div>

    <!-- MAIN BODY BY TAB -->
    <main>
      <!-- TAB 0: REKAP KELAS & KBM (LEDGER AKUMULASI, MAPEL & RAPOR) -->
      <ClassRecapTab
        v-if="activeTab === 'class_recap'"
        :classes="classes"
        :subjects="subjects"
        :initial-class-id="selectedClassId"
        @notify="showMessage"
        @switch-tab="handleSwitchTab"
      />

      <!-- TAB 1: REKAP PRESENSI SISWA (HASIL INPUT KBM GURU & WALI) -->
      <StudentRecapTab
        v-else-if="activeTab === 'student_recap'"
        :classes="classes"
        :selected-date="selectedDate"
        @notify="showMessage"
        @switch-tab="handleSwitchTab"
      />

      <!-- TAB 2: GALERI BUKTI KBM & JURNAL MENGAJAR GURU -->
      <KbmGalleryTab
        v-else-if="activeTab === 'kbm'"
        :classes="classes"
        :selected-date="selectedDate"
        @notify="showMessage"
      />

      <!-- TAB 3: PRESENSI HARIAN SISWA (WALI KELAS & ADMIN) -->
      <ManualInputTab
        v-else-if="activeTab === 'manual_input' && (auth.isAdmin || auth.isWaliKelas)"
        :classes="classes"
        :selected-date="selectedDate"
        :initial-class-id="manualInputClassId"
        @notify="showMessage"
        @saved="handleManualInputSaved"
      />

      <!-- TAB 4: PRESENSI GURU & TENDIK MANDIRI (GPS CHECK-IN - ADMIN ONLY) -->
      <TeacherAttendanceTab
        v-else-if="auth.isAdmin && activeTab === 'teachers'"
        :selected-date="selectedDate"
        @notify="showMessage"
      />
    </main>
  </div>
</template>
