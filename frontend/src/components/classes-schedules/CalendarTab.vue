<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import api from '../../services/api'
import { useAuthStore } from '../../stores/auth'
import { showConfirm } from '../../services/swal'

export interface CalendarEvent {
  id: number
  judul: string
  tanggal_mulai: string
  tanggal_selesai: string
  tipe: 'libur_nasional' | 'libur_sekolah' | 'libur_semester' | 'kbm_khusus' | 'kegiatan_sekolah'
  is_libur: boolean
  warna: string
  deskripsi?: string
  academic_year_id?: number
  semester_id?: number
  creator?: { id: number; nama: string }
}

const auth = useAuthStore()

// State
const loading = ref(false)
const events = ref<CalendarEvent[]>([])
const viewType = ref<'calendar' | 'table'>('calendar')
const filterType = ref<string>('')
const filterIsLibur = ref<string>('')
const searchQuery = ref('')

// Calendar Navigation
const currentYear = ref(new Date().getFullYear())
const currentMonth = ref(new Date().getMonth() + 1) // 1-12

// Modal Form State
const showModal = ref(false)
const isEditing = ref(false)
const editingId = ref<number | null>(null)
const submitting = ref(false)
const seeding = ref(false)
const notification = ref<{ text: string; type: 'success' | 'error' } | null>(null)

const form = ref({
  judul: '',
  tanggal_mulai: '',
  tanggal_selesai: '',
  tipe: 'libur_nasional' as CalendarEvent['tipe'],
  is_libur: true,
  warna: '#ef4444',
  deskripsi: '',
})

const showNotify = (text: string, type: 'success' | 'error' = 'success') => {
  notification.value = { text, type }
  setTimeout(() => {
    notification.value = null
  }, 4000)
}

// Fetch events from API
const fetchEvents = async () => {
  loading.value = true
  try {
    const res = await api.get('/calendar', {
      params: {
        bulan: currentMonth.value,
        tahun: currentYear.value,
        tipe: filterType.value || undefined,
        is_libur: filterIsLibur.value !== '' ? filterIsLibur.value : undefined,
        search: searchQuery.value || undefined,
      },
    })
    if (res.data.success) {
      events.value = res.data.data
    }
  } catch (err: any) {
    showNotify(err.response?.data?.message || 'Gagal memuat data kalender akademik.', 'error')
  } finally {
    loading.value = false
  }
}

// Navigation methods
const nextMonth = () => {
  if (currentMonth.value === 12) {
    currentMonth.value = 1
    currentYear.value++
  } else {
    currentMonth.value++
  }
}

const prevMonth = () => {
  if (currentMonth.value === 1) {
    currentMonth.value = 12
    currentYear.value--
  } else {
    currentMonth.value--
  }
}

const goToToday = () => {
  const now = new Date()
  currentYear.value = now.getFullYear()
  currentMonth.value = now.getMonth() + 1
}

watch([currentYear, currentMonth, filterType, filterIsLibur], () => {
  fetchEvents()
})

// Month & Calendar Grid Calculations
const monthNames = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
]

const currentMonthLabel = computed(() => {
  return `${monthNames[currentMonth.value - 1]} ${currentYear.value}`
})

// Build Calendar Grid (Senin - Minggu)
interface DayCell {
  date: Date
  dateString: string
  dayNumber: number
  isCurrentMonth: boolean
  isToday: boolean
  isSunday: boolean
  events: CalendarEvent[]
  isHoliday: boolean
}

const calendarDays = computed(() => {
  const days: DayCell[] = []
  const year = currentYear.value
  const month = currentMonth.value - 1 // 0-indexed

  const firstDayOfMonth = new Date(year, month, 1)
  const lastDayOfMonth = new Date(year, month + 1, 0)
  const todayStr = new Date().toISOString().substring(0, 10)

  // Start on Monday: dayOfWeek 0 is Sunday, so Monday is 1
  let firstDayIndex = firstDayOfMonth.getDay() // 0 = Sun, 1 = Mon, ..., 6 = Sat
  let leadingDays = firstDayIndex === 0 ? 6 : firstDayIndex - 1

  // Leading days from previous month
  const prevMonthLastDate = new Date(year, month, 0).getDate()
  for (let i = leadingDays - 1; i >= 0; i--) {
    const d = new Date(year, month - 1, prevMonthLastDate - i)
    const dateStr = d.toISOString().substring(0, 10)
    const dayEvents = events.value.filter(e => {
      const start = e.tanggal_mulai.substring(0, 10)
      const end = e.tanggal_selesai.substring(0, 10)
      return dateStr >= start && dateStr <= end
    })
    days.push({
      date: d,
      dateString: dateStr,
      dayNumber: d.getDate(),
      isCurrentMonth: false,
      isToday: dateStr === todayStr,
      isSunday: d.getDay() === 0,
      events: dayEvents,
      isHoliday: d.getDay() === 0 || dayEvents.some(e => e.is_libur),
    })
  }

  // Days of current month
  for (let i = 1; i <= lastDayOfMonth.getDate(); i++) {
    const d = new Date(year, month, i)
    const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`
    const dayEvents = events.value.filter(e => {
      const start = e.tanggal_mulai.substring(0, 10)
      const end = e.tanggal_selesai.substring(0, 10)
      return dateStr >= start && dateStr <= end
    })
    days.push({
      date: d,
      dateString: dateStr,
      dayNumber: i,
      isCurrentMonth: true,
      isToday: dateStr === todayStr,
      isSunday: d.getDay() === 0,
      events: dayEvents,
      isHoliday: d.getDay() === 0 || dayEvents.some(e => e.is_libur),
    })
  }

  // Trailing days from next month to make complete 7-column rows
  const remaining = 7 - (days.length % 7)
  if (remaining < 7) {
    for (let i = 1; i <= remaining; i++) {
      const d = new Date(year, month + 1, i)
      const dateStr = d.toISOString().substring(0, 10)
      const dayEvents = events.value.filter(e => {
        const start = e.tanggal_mulai.substring(0, 10)
        const end = e.tanggal_selesai.substring(0, 10)
        return dateStr >= start && dateStr <= end
      })
      days.push({
        date: d,
        dateString: dateStr,
        dayNumber: i,
        isCurrentMonth: false,
        isToday: dateStr === todayStr,
        isSunday: d.getDay() === 0,
        events: dayEvents,
        isHoliday: d.getDay() === 0 || dayEvents.some(e => e.is_libur),
      })
    }
  }

  return days
})

// Statistics for the Month
const monthStats = computed(() => {
  const currentMonthDays = calendarDays.value.filter(d => d.isCurrentMonth)
  const totalDays = currentMonthDays.length
  const sundays = currentMonthDays.filter(d => d.isSunday).length
  const calendarHolidays = currentMonthDays.filter(d => !d.isSunday && d.events.some(e => e.is_libur)).length
  const effectiveWorkDays = totalDays - sundays - calendarHolidays

  return {
    totalDays,
    effectiveWorkDays: Math.max(0, effectiveWorkDays),
    totalHolidays: sundays + calendarHolidays,
    calendarHolidayCount: calendarHolidays,
    eventsCount: events.value.length,
  }
})

// Modal Actions
const openCreateModal = (presetDate?: string) => {
  isEditing.value = false
  editingId.value = null
  const defaultDate = presetDate || `${currentYear.value}-${String(currentMonth.value).padStart(2, '0')}-01`
  form.value = {
    judul: '',
    tanggal_mulai: defaultDate,
    tanggal_selesai: defaultDate,
    tipe: 'libur_nasional',
    is_libur: true,
    warna: '#ef4444',
    deskripsi: '',
  }
  showModal.value = true
}

const openEditModal = (event: CalendarEvent) => {
  isEditing.value = true
  editingId.value = event.id
  form.value = {
    judul: event.judul,
    tanggal_mulai: event.tanggal_mulai.substring(0, 10),
    tanggal_selesai: event.tanggal_selesai.substring(0, 10),
    tipe: event.tipe,
    is_libur: event.is_libur,
    warna: event.warna || '#ef4444',
    deskripsi: event.deskripsi || '',
  }
  showModal.value = true
}

const handleTypeChange = () => {
  const t = form.value.tipe
  if (t === 'libur_nasional') {
    form.value.is_libur = true
    form.value.warna = '#ef4444'
  } else if (t === 'libur_sekolah') {
    form.value.is_libur = true
    form.value.warna = '#f97316'
  } else if (t === 'libur_semester') {
    form.value.is_libur = true
    form.value.warna = '#dc2626'
  } else if (t === 'kbm_khusus') {
    form.value.is_libur = false
    form.value.warna = '#10b981'
  } else {
    form.value.is_libur = false
    form.value.warna = '#3b82f6'
  }
}

const saveEvent = async () => {
  if (!form.value.judul.trim() || !form.value.tanggal_mulai) {
    showNotify('Mohon lengkapi judul dan tanggal agenda.', 'error')
    return
  }

  submitting.value = true
  try {
    if (isEditing.value && editingId.value) {
      const res = await api.put(`/calendar/${editingId.value}`, form.value)
      if (res.data.success) {
        showNotify('Agenda kalender berhasil diperbarui.')
        showModal.value = false
        fetchEvents()
      }
    } else {
      const res = await api.post('/calendar', form.value)
      if (res.data.success) {
        showNotify('Agenda kalender berhasil ditambahkan.')
        showModal.value = false
        fetchEvents()
      }
    }
  } catch (err: any) {
    showNotify(err.response?.data?.message || 'Gagal menyimpan agenda kalender.', 'error')
  } finally {
    submitting.value = false
  }
}

const deleteEvent = async (event: CalendarEvent) => {
  const confirmed = await showConfirm(
    'Hapus Agenda Kalender',
    `Hapus agenda "${event.judul}" dari kalender?`,
    'Ya, Hapus'
  )
  if (!confirmed) return

  try {
    const res = await api.delete(`/calendar/${event.id}`)
    if (res.data.success) {
      showNotify(res.data.message || 'Agenda berhasil dihapus.')
      fetchEvents()
    }
  } catch (err: any) {
    showNotify(err.response?.data?.message || 'Gagal menghapus agenda.', 'error')
  }
}

// Seed Template
const seedDefaultTemplate = async () => {
  const confirmed = await showConfirm(
    'Muat Agenda Standar',
    `Muat agenda standar & hari libur nasional untuk tahun ${currentYear.value}? Agenda yang sudah ada tidak akan diduplikasi.`,
    'Ya, Muat Agenda',
    'question'
  )
  if (!confirmed) return

  seeding.value = true
  try {
    const res = await api.post('/calendar/seed-template', { year: currentYear.value })
    if (res.data.success) {
      showNotify(res.data.message)
      fetchEvents()
    }
  } catch (err: any) {
    showNotify(err.response?.data?.message || 'Gagal memuat template kalender.', 'error')
  } finally {
    seeding.value = false
  }
}

const formatEventDate = (start: string, end: string) => {
  const s = start.substring(0, 10)
  const e = end.substring(0, 10)
  if (s === e) return s
  return `${s} s.d. ${e}`
}

onMounted(() => {
  fetchEvents()
})

defineExpose({
  openCreateModal,
  openSeedModal: seedDefaultTemplate,
  fetchEvents,
})
</script>

<template>
  <div class="space-y-4">
    <!-- Notification Toast -->
    <transition
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="notification"
        class="fixed top-5 right-5 z-50 max-w-md w-full shadow-2xl rounded-2xl p-4 flex items-center gap-3 border text-xs font-semibold backdrop-blur-md"
        :class="notification.type === 'success' ? 'bg-emerald-500/95 text-white border-emerald-400' : 'bg-rose-500/95 text-white border-rose-400'"
      >
        <span class="text-base">{{ notification.type === 'success' ? '✅' : '⚠️' }}</span>
        <div class="flex-1 leading-snug">{{ notification.text }}</div>
        <button @click="notification = null" class="text-white/80 hover:text-white font-black cursor-pointer">&times;</button>
      </div>
    </transition>

    <!-- Compact Unified Bar (Navigasi Bulan, Indikator Hari, Switcher & Filter) -->
    <div class="bg-white rounded-2xl p-3 sm:p-3.5 border border-slate-200/80 shadow-xs flex flex-col xl:flex-row xl:items-center justify-between gap-3">
      <!-- Sisi Kiri: Navigasi Bulan, Label, & Indikator Hari Efektif / Libur -->
      <div class="flex flex-wrap items-center gap-3">
        <!-- Month Navigator -->
        <div class="flex items-center bg-slate-100 rounded-xl p-0.5 border border-slate-200">
          <button
            type="button"
            @click="prevMonth"
            class="p-1.5 hover:bg-white rounded-lg transition text-slate-700 font-bold hover:shadow-xs cursor-pointer"
            title="Bulan Sebelumnya"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <button
            type="button"
            @click="goToToday"
            class="px-2.5 py-1 hover:bg-white rounded-lg text-xs font-bold text-slate-800 transition hover:shadow-xs cursor-pointer"
          >
            Bulan Ini
          </button>
          <button
            type="button"
            @click="nextMonth"
            class="p-1.5 hover:bg-white rounded-lg transition text-slate-700 font-bold hover:shadow-xs cursor-pointer"
            title="Bulan Berikutnya"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>

        <!-- Nama Bulan Aktif -->
        <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight shrink-0">
          {{ currentMonthLabel }}
        </h2>

        <!-- Compact Metrics Chips -->
        <div class="flex items-center gap-1.5">
          <span
            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200/80 shadow-2xs"
            title="Hari Efektif KBM (Presensi Terbuka)"
          >
            <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
            <span><strong>{{ monthStats.effectiveWorkDays }}</strong> Hari Efektif</span>
          </span>

          <span
            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-800 border border-rose-200/80 shadow-2xs"
            title="Hari Libur & Akhir Pekan (Presensi Tertutup)"
          >
            <span class="w-2 h-2 rounded-full bg-rose-500 shrink-0"></span>
            <span><strong>{{ monthStats.totalHolidays }}</strong> Libur</span>
          </span>
        </div>
      </div>

      <!-- Sisi Kanan: Switcher Tab Kalender/Agenda & Filter -->
      <div class="flex flex-wrap items-center gap-2">
        <!-- View Switcher -->
        <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-xl text-xs font-bold">
          <button
            type="button"
            @click="viewType = 'calendar'"
            :class="viewType === 'calendar' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
            class="px-2.5 py-1 rounded-lg transition flex items-center gap-1 cursor-pointer"
          >
            <span>📅</span>
            <span>Kalender</span>
          </button>
          <button
            type="button"
            @click="viewType = 'table'"
            :class="viewType === 'table' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
            class="px-2.5 py-1 rounded-lg transition flex items-center gap-1 cursor-pointer"
          >
            <span>📋</span>
            <span>Agenda ({{ events.length }})</span>
          </button>
        </div>

        <!-- Filter Dropdown & Search -->
        <select
          v-model="filterType"
          class="bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
        >
          <option value="">Semua Kategori</option>
          <option value="libur_nasional">Libur Nasional</option>
          <option value="libur_sekolah">Libur Sekolah</option>
          <option value="libur_semester">Libur Semester</option>
          <option value="kbm_khusus">KBM Khusus / Ujian</option>
          <option value="kegiatan_sekolah">Kegiatan Sekolah</option>
        </select>

        <select
          v-model="filterIsLibur"
          class="bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
        >
          <option value="">Semua Status</option>
          <option value="true">🔴 Libur</option>
          <option value="false">🟢 KBM</option>
        </select>

        <div class="relative">
          <input
            v-model="searchQuery"
            @keyup.enter="fetchEvents"
            type="text"
            placeholder="Cari..."
            class="pl-7 pr-2.5 py-1 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 w-28 sm:w-36"
          />
          <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2 top-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>
    </div>

    <!-- VIEW 1: MONTHLY CALENDAR GRID -->
    <div v-if="viewType === 'calendar'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <!-- Days of Week Header -->
      <div class="grid grid-cols-7 bg-slate-50 border-b border-slate-200 text-center text-[10px] sm:text-[11px] font-black uppercase tracking-wider py-2">
        <div class="text-slate-700">Senin</div>
        <div class="text-slate-700">Selasa</div>
        <div class="text-slate-700">Rabu</div>
        <div class="text-slate-700">Kamis</div>
        <div class="text-slate-700">Jumat</div>
        <div class="text-slate-700">Sabtu</div>
        <div class="text-rose-600">Minggu</div>
      </div>

      <!-- Calendar Days Grid -->
      <div class="grid grid-cols-7 auto-rows-fr divide-x divide-y divide-slate-100">
        <div
          v-for="(day, idx) in calendarDays"
          :key="idx"
          class="min-h-[64px] sm:min-h-[74px] p-1.5 flex flex-col justify-between transition-colors relative group"
          :class="{
            'bg-slate-50/40 text-slate-400': !day.isCurrentMonth,
            'bg-white': day.isCurrentMonth && !day.isHoliday,
            'bg-rose-50/20': day.isCurrentMonth && day.isHoliday,
            'ring-2 ring-emerald-500 ring-inset z-10': day.isToday,
          }"
        >
          <!-- Top Row in Cell: Date Number & Badges -->
          <div class="flex items-center justify-between">
            <span
              class="w-5 h-5 rounded-full flex items-center justify-center text-[11px] font-bold"
              :class="{
                'bg-emerald-600 text-white font-black': day.isToday,
                'text-rose-600 font-extrabold': day.isSunday || day.isHoliday,
                'text-slate-700': !day.isSunday && !day.isHoliday && day.isCurrentMonth,
                'text-slate-400': !day.isCurrentMonth,
              }"
            >
              {{ day.dayNumber }}
            </span>

            <!-- Holiday / Sunday Label -->
            <span
              v-if="day.isSunday"
              class="text-[8px] font-black uppercase text-rose-500 bg-rose-50 px-1 py-0.5 rounded border border-rose-200/60 leading-none"
            >
              Libur
            </span>
            <span
              v-else-if="day.events.some(e => e.is_libur)"
              class="text-[8px] font-black uppercase text-rose-700 bg-rose-100/70 px-1 py-0.5 rounded border border-rose-200 leading-none"
            >
              Libur
            </span>

            <!-- Add shortcut on hover (Admin only) -->
            <button
              v-if="auth.isAdmin && day.isCurrentMonth"
              type="button"
              @click.stop="openCreateModal(day.dateString)"
              class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-emerald-700 p-0.5 rounded text-[11px] leading-none transition"
              title="Tambah agenda pada tanggal ini"
            >
              +
            </button>
          </div>

          <!-- Events Pill List in Day Cell -->
          <div class="space-y-0.5 my-0.5 overflow-y-auto max-h-[34px] scrollbar-none">
            <div
              v-for="ev in day.events"
              :key="ev.id"
              @click="auth.isAdmin ? openEditModal(ev) : null"
              class="px-1.5 py-0.5 rounded text-[9px] font-bold text-white truncate shadow-2xs transition hover:brightness-95 leading-tight"
              :class="auth.isAdmin ? 'cursor-pointer' : 'cursor-default'"
              :style="{ backgroundColor: ev.warna || '#ef4444' }"
              :title="`${ev.judul} (${ev.is_libur ? 'Libur' : 'KBM'}): ${ev.deskripsi || '-'}`"
            >
              <span v-if="ev.is_libur">🔴</span>
              <span v-else>🟢</span>
              {{ ev.judul }}
            </div>
          </div>

          <!-- Bottom Status Indicator -->
          <div class="text-[8px] text-right font-medium text-slate-400 leading-none">
            <span v-if="day.isHoliday" class="text-rose-600 font-bold">🔒 Tutup</span>
            <span v-else class="text-emerald-600 font-semibold">✓ KBM</span>
          </div>
        </div>
      </div>
    </div>

    <!-- VIEW 2: TABLE VIEW -->
    <div v-else class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-extrabold uppercase tracking-wider text-[11px]">
              <th class="py-3.5 px-4">Tanggal Pelaksanaan</th>
              <th class="py-3.5 px-4">Nama Agenda / Kegiatan</th>
              <th class="py-3.5 px-4">Kategori</th>
              <th class="py-3.5 px-4 text-center">Status Absensi</th>
              <th class="py-3.5 px-4">Keterangan</th>
              <th v-if="auth.isAdmin" class="py-3.5 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="events.length === 0">
              <td colspan="6" class="text-center py-12 text-slate-400 font-medium">
                Belum ada agenda atau hari libur yang tercatat pada bulan ini.
              </td>
            </tr>
            <tr
              v-for="ev in events"
              :key="ev.id"
              class="hover:bg-slate-50/70 transition"
            >
              <td class="py-3 px-4 font-mono font-bold text-slate-800 whitespace-nowrap">
                {{ formatEventDate(ev.tanggal_mulai, ev.tanggal_selesai) }}
              </td>
              <td class="py-3 px-4">
                <div class="flex items-center gap-2">
                  <span
                    class="w-3 h-3 rounded-full shrink-0 shadow-xs"
                    :style="{ backgroundColor: ev.warna || '#ef4444' }"
                  ></span>
                  <span class="font-bold text-slate-900">{{ ev.judul }}</span>
                </div>
              </td>
              <td class="py-3 px-4 capitalize font-semibold text-slate-600">
                <span v-if="ev.tipe === 'libur_nasional'">Libur Nasional</span>
                <span v-else-if="ev.tipe === 'libur_sekolah'">Libur Khusus Sekolah</span>
                <span v-else-if="ev.tipe === 'libur_semester'">Libur Semester</span>
                <span v-else-if="ev.tipe === 'kbm_khusus'">KBM Khusus / Asesmen</span>
                <span v-else>Kegiatan Sekolah</span>
              </td>
              <td class="py-3 px-4 text-center">
                <span
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider inline-flex items-center gap-1 border"
                  :class="ev.is_libur ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
                >
                  <span>{{ ev.is_libur ? '🔒 DITUTUP (LIBUR)' : '🟢 DIBUKA (KBM)' }}</span>
                </span>
              </td>
              <td class="py-3 px-4 text-slate-500 max-w-xs truncate">
                {{ ev.deskripsi || '-' }}
              </td>
              <td v-if="auth.isAdmin" class="py-3 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    type="button"
                    @click="openEditModal(ev)"
                    class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-bold text-[11px] transition cursor-pointer"
                  >
                    Edit
                  </button>
                  <button
                    type="button"
                    @click="deleteEvent(ev)"
                    class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg font-bold text-[11px] transition cursor-pointer"
                  >
                    Hapus
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL FORM: CREATE / EDIT AGENDA -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
      @click.self="showModal = false"
    >
      <div class="bg-white rounded-3xl p-6 sm:p-7 max-w-lg w-full shadow-2xl border border-slate-100 space-y-5 animate-in fade-in zoom-in-95 duration-200">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="text-base font-black text-slate-900">
              {{ isEditing ? 'Edit Agenda / Hari Libur' : 'Tambah Agenda / Hari Libur' }}
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Kelola hari libur dan jadwal kegiatan khusus sekolah.
            </p>
          </div>
          <button
            type="button"
            @click="showModal = false"
            class="p-1.5 text-slate-400 hover:text-slate-700 rounded-xl hover:bg-slate-100 cursor-pointer"
          >
            ✕
          </button>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="saveEvent" class="space-y-4 text-xs">
          <!-- Judul -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Nama Agenda / Hari Libur <span class="text-rose-500">*</span></label>
            <input
              v-model="form.judul"
              type="text"
              required
              placeholder="Contoh: Hari Kemerdekaan RI, Libur Awal Ramadhan, ASTS Gasal"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500"
            />
          </div>

          <!-- Tanggal Mulai & Selesai -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Tanggal Mulai <span class="text-rose-500">*</span></label>
              <input
                v-model="form.tanggal_mulai"
                type="date"
                required
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Tanggal Selesai</label>
              <input
                v-model="form.tanggal_selesai"
                type="date"
                :min="form.tanggal_mulai"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500"
              />
              <span class="text-[10px] text-slate-400">Biarkan sama jika hanya 1 hari</span>
            </div>
          </div>

          <!-- Kategori & Warna -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Kategori Agenda <span class="text-rose-500">*</span></label>
              <select
                v-model="form.tipe"
                @change="handleTypeChange"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500"
              >
                <option value="libur_nasional">Libur Nasional / Cuti Bersama</option>
                <option value="libur_sekolah">Libur Khusus SDI BIM / Yayasan</option>
                <option value="libur_semester">Libur Akhir Semester</option>
                <option value="kbm_khusus">KBM Khusus / Asesmen (PTS/PAS)</option>
                <option value="kegiatan_sekolah">Kegiatan Sekolah / Upacara / Rapat</option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Warna Label</label>
              <div class="flex items-center gap-2">
                <input
                  v-model="form.warna"
                  type="color"
                  class="w-9 h-9 p-0.5 rounded-xl border border-slate-200 cursor-pointer"
                />
                <span class="font-mono text-slate-600 font-bold uppercase">{{ form.warna }}</span>
              </div>
            </div>
          </div>

          <!-- Status Libur Toggle (Absen Buka vs Tutup) -->
          <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 flex items-center justify-between gap-3">
            <div>
              <div class="font-bold text-slate-800">Tandai Sebagai Hari Libur</div>
              <div class="text-[11px] text-slate-500">
                {{ form.is_libur ? '🔴 Absensi Guru & Siswa akan DITUTUP. Tidak dihitung mangkir/alpa.' : '🟢 Absensi tetap DIBUKA (Hari Efektif KBM).' }}
              </div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                v-model="form.is_libur"
                type="checkbox"
                class="sr-only peer"
              />
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600"></div>
            </label>
          </div>

          <!-- Deskripsi -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Catatan / Keterangan</label>
            <textarea
              v-model="form.deskripsi"
              rows="2"
              placeholder="Catatan tambahan mengenai agenda ini..."
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-normal text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500"
            ></textarea>
          </div>

          <!-- Modal Actions -->
          <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
            <button
              type="button"
              @click="showModal = false"
              class="px-4 py-2 border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold rounded-xl transition cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md transition cursor-pointer disabled:opacity-50"
            >
              {{ submitting ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Agenda') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
