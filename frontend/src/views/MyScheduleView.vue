<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
import type { ScheduleItem, MergedScheduleItem, Teacher } from '../components/my-schedule/types'
import { isTodaySchedule } from '../components/my-schedule/types'
import ScheduleListTab from '../components/my-schedule/ScheduleListTab.vue'
import KbmEvidenceForm from '../components/my-schedule/KbmEvidenceForm.vue'
import HomeroomAttendanceForm from '../components/my-schedule/HomeroomAttendanceForm.vue'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

// View Mode: 'schedule_list' | 'kbm_evidence' | 'homeroom_attendance'
const currentViewMode = ref<'schedule_list' | 'kbm_evidence' | 'homeroom_attendance'>('schedule_list')

// State
const loading = ref(false)
const schedules = ref<ScheduleItem[]>([])
const teachersList = ref<Teacher[]>([])
const activeDate = ref<string>(new Date().toISOString().split('T')[0])
const todayHomeroomStatus = ref<any>(null)

// Child Form Targets
const activeSchedule = ref<MergedScheduleItem | null>(null)
const selectedHomeroomClass = ref<any>(null)

// Alert Notification State
const alertState = ref<{ show: boolean; title: string; message: string; type: 'success' | 'error' | 'warning' }>({
  show: false,
  title: '',
  message: '',
  type: 'success',
})

const showAlert = (title: string, message: string, type: 'success' | 'error' | 'warning' = 'success') => {
  alertState.value = { show: true, title, message, type }
  setTimeout(() => {
    alertState.value.show = false
  }, 4500)
}

const handleNotify = (payloadOrTitle: any, message?: string, type: 'success' | 'error' | 'warning' = 'success') => {
  if (typeof payloadOrTitle === 'object' && payloadOrTitle !== null) {
    showAlert(payloadOrTitle.title || '', payloadOrTitle.message || '', payloadOrTitle.type || 'success')
  } else {
    showAlert(payloadOrTitle, message || '', type)
  }
}

// Midnight & Interval Timers for Automatic Date Rollover
let midnightTimer: any = null
let intervalCheckTimer: any = null

const updateCurrentDate = () => {
  const now = new Date()
  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')
  const newDateStr = `${year}-${month}-${day}`

  if (activeDate.value !== newDateStr) {
    activeDate.value = newDateStr
    loadSchedules()
    checkTodayHomeroomStatus()
  }
}

const scheduleMidnightUpdate = () => {
  if (midnightTimer) clearTimeout(midnightTimer)
  const now = new Date()
  const tomorrow = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 0, 0, 2)
  const msUntilMidnight = tomorrow.getTime() - now.getTime()

  midnightTimer = setTimeout(() => {
    updateCurrentDate()
    scheduleMidnightUpdate()
  }, msUntilMidnight)
}

// Load Teachers (Admin only)
const loadTeachers = async () => {
  if (!auth.isAdmin) return
  try {
    const res = await api.get('/teachers')
    if (res.data.success) {
      teachersList.value = res.data.data || []
    }
  } catch (err) {
    console.error('Gagal memuat daftar guru', err)
  }
}

// Load Schedules
const loadSchedules = async () => {
  loading.value = true
  try {
    const params: any = {
      tanggal: activeDate.value,
    }

    if (auth.isGuru && !auth.isAdmin) {
      params.my_schedules = 1
    }

    const res = await api.get('/schedules', { params })
    if (res.data.success) {
      schedules.value = res.data.data || []
    }
  } catch (err: any) {
    console.error('Gagal mengambil jadwal KBM', err)
    showAlert('Gagal Mengambil Jadwal', err.response?.data?.message || 'Terjadi kesalahan jaringan.', 'error')
  } finally {
    loading.value = false
  }
}

// Check Homeroom Daily Status
const checkTodayHomeroomStatus = async () => {
  if (!auth.isWaliKelas || !auth.primaryHomeroomClass) return
  try {
    const res = await api.get('/attendance/students/session-sheet', {
      params: {
        class_id: auth.primaryHomeroomClass.id,
        tanggal: activeDate.value,
      },
    })
    if (res.data.success && res.data.data) {
      todayHomeroomStatus.value = res.data.data
    }
  } catch (err) {
    console.error('Gagal mengecek status presensi hari ini:', err)
  }
}

// Open KBM Evidence Form
const openKbmEvidenceForm = (schedule: MergedScheduleItem) => {
  if (!isTodaySchedule(schedule, activeDate.value) && !(schedule as any).today_evidence_count && !auth.isAdmin) {
    showAlert(
      'Jadwal Belum Dibuka',
      `Jadwal pelajaran ini berlangsung pada hari ${schedule.hari.toUpperCase()}. Pengunggahan bukti foto dan jurnal KBM hanya dapat dilakukan saat hari pelaksanaan KBM tersebut.`,
      'warning'
    )
    return
  }

  activeSchedule.value = schedule
  currentViewMode.value = 'kbm_evidence'
}

// Open Homeroom Form
const openHomeroomAttendance = (targetClass?: any) => {
  const cls = targetClass || auth.primaryHomeroomClass
  if (!cls) return
  selectedHomeroomClass.value = cls
  currentViewMode.value = 'homeroom_attendance'
}

const closeHomeroomView = () => {
  currentViewMode.value = 'schedule_list'
  if (route.query.mode === 'homeroom') {
    router.replace({ path: '/my-schedule' })
  }
}

const onEvidenceSaved = async () => {
  await loadSchedules()
}

const onHomeroomSaved = async () => {
  await checkTodayHomeroomStatus()
}

// Lifecycle Hooks
onMounted(async () => {
  updateCurrentDate()
  scheduleMidnightUpdate()
  intervalCheckTimer = setInterval(updateCurrentDate, 30000)

  await loadTeachers()
  await loadSchedules()
  await checkTodayHomeroomStatus()

  if (route.query.mode === 'homeroom' && (auth.isWaliKelas || auth.isAdmin)) {
    openHomeroomAttendance()
  }
})

watch(
  () => route.query.mode,
  (mode) => {
    if (mode === 'homeroom' && (auth.isWaliKelas || auth.isAdmin)) {
      openHomeroomAttendance()
    } else if (!mode && currentViewMode.value === 'homeroom_attendance') {
      currentViewMode.value = 'schedule_list'
    }
  }
)

onUnmounted(() => {
  if (midnightTimer) clearTimeout(midnightTimer)
  if (intervalCheckTimer) clearInterval(intervalCheckTimer)
})
</script>

<template>
  <div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Alert Floating Notification -->
    <transition
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="alertState.show"
        class="fixed top-5 right-5 z-50 max-w-sm w-full bg-white rounded-2xl shadow-2xl border p-4 flex items-start gap-3"
        :class="
          alertState.type === 'success'
            ? 'border-emerald-200 bg-emerald-50/90 text-emerald-900'
            : alertState.type === 'warning'
            ? 'border-amber-200 bg-amber-50/90 text-amber-900'
            : 'border-rose-200 bg-rose-50/90 text-rose-900'
        "
      >
        <div class="shrink-0 mt-0.5">
          <svg v-if="alertState.type === 'success'" class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <svg v-else-if="alertState.type === 'warning'" class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <svg v-else class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </div>
        <div class="flex-1">
          <h4 class="text-xs font-bold">{{ alertState.title }}</h4>
          <p class="text-xs mt-0.5 opacity-90 leading-relaxed">{{ alertState.message }}</p>
        </div>
        <button @click="alertState.show = false" class="text-gray-400 hover:text-gray-600 font-bold text-sm cursor-pointer">&times;</button>
      </div>
    </transition>

    <!-- VIEW MODE 1: SCHEDULE LIST (TABEL/GRID JADWAL MENGAJAR) -->
    <ScheduleListTab
      v-if="currentViewMode === 'schedule_list'"
      :schedules="schedules"
      :teachers-list="teachersList"
      :today-homeroom-status="todayHomeroomStatus"
      :active-date="activeDate"
      :loading="loading"
      @open-kbm="openKbmEvidenceForm"
      @open-homeroom="openHomeroomAttendance"
      @refresh="loadSchedules"
      @notify="handleNotify"
    />

    <!-- VIEW MODE 2: KBM EVIDENCE & JOURNAL FORM (GURU MATA PELAJARAN) -->
    <KbmEvidenceForm
      v-else-if="currentViewMode === 'kbm_evidence' && activeSchedule"
      :active-schedule="activeSchedule"
      :active-date="activeDate"
      @close="currentViewMode = 'schedule_list'"
      @saved="onEvidenceSaved"
      @notify="handleNotify"
    />

    <!-- VIEW MODE 3: HOMEROOM DAILY ATTENDANCE SHEET (WALI KELAS 1X SEHARI) -->
    <HomeroomAttendanceForm
      v-else-if="currentViewMode === 'homeroom_attendance'"
      :active-date="activeDate"
      :initial-class="selectedHomeroomClass"
      @close="closeHomeroomView"
      @saved="onHomeroomSaved"
      @notify="handleNotify"
    />
  </div>
</template>
