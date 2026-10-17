<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from './stores/auth'
import api from './services/api'
import { showConfirm } from './services/swal'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const activeSemesterInfo = ref<{
  academicYear: string
  semester: string
  status: string
  reopened: boolean
} | null>(null)

const loadActivePeriod = async () => {
  try {
    const res = await api.get('/semesters')
    if (res.data?.success && Array.isArray(res.data.data)) {
      const activeSem = res.data.data.find((s: any) => s.status === 'aktif')
      if (activeSem) {
        activeSemesterInfo.value = {
          academicYear: activeSem.academic_year?.nama || '2026/2027',
          semester: activeSem.semester_label || (activeSem.semester === 'gasal' ? 'Gasal' : 'Genap'),
          status: 'aktif',
          reopened: false,
        }
      } else {
        const reopenedSem = res.data.data.find((s: any) => s.status === 'terkunci' && s.reopen_until && new Date(s.reopen_until) > new Date())
        if (reopenedSem) {
          activeSemesterInfo.value = {
            academicYear: reopenedSem.academic_year?.nama || '2026/2027',
            semester: reopenedSem.semester_label || (reopenedSem.semester === 'gasal' ? 'Gasal' : 'Genap'),
            status: 'reopened',
            reopened: true,
          }
        } else {
          const latestSem = res.data.data[0]
          if (latestSem) {
            activeSemesterInfo.value = {
              academicYear: latestSem.academic_year?.nama || '2026/2027',
              semester: latestSem.semester_label || (latestSem.semester === 'gasal' ? 'Gasal' : 'Genap'),
              status: latestSem.status,
              reopened: false,
            }
          }
        }
      }
    }
  } catch (e) {
    // Ignore fallback
  }
}

onMounted(async () => {
  if (auth.isAuthenticated) {
    try {
      await Promise.all([
        auth.fetchProfile(),
        loadActivePeriod(),
      ])
    } catch (e) {
      // Ignore
    }
  }
})

const isSidebarOpen = ref(true)
const isMobileDrawerOpen = ref(false)

// State for expanding/collapsing sub-menus
const expandedMenus = ref<Record<string, boolean>>({
  'classes-schedules': false,
  'attendance-management': false,
  'progress-activities': false,
  'finance': false,
  'users-settings': false,
})

const toggleMenu = (key: string) => {
  expandedMenus.value[key] = !expandedMenus.value[key]
}

const isMenuExpanded = (key: string) => {
  return !!expandedMenus.value[key]
}

// Close mobile drawer whenever route changes and auto-expand matching menu
watch(() => route.path, (newPath) => {
  isMobileDrawerOpen.value = false
  if (newPath === '/classes-schedules') expandedMenus.value['classes-schedules'] = true
  if (newPath === '/attendance-management') expandedMenus.value['attendance-management'] = true
  if (newPath === '/progress-activities') expandedMenus.value['progress-activities'] = true
  if (newPath === '/finance') expandedMenus.value['finance'] = true
  if (newPath === '/users-settings') expandedMenus.value['users-settings'] = true
}, { immediate: true })

const handleLogout = async () => {
  const confirmed = await showConfirm(
    'Konfirmasi Keluar',
    'Apakah Anda yakin ingin keluar dari sistem SIM-BIM Purwokerto?',
    'Ya, Keluar'
  )
  if (confirmed) {
    await auth.logout()
    router.push('/login')
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50/70 text-slate-800 flex flex-col font-sans antialiased">
    <!-- LAYOUT FOR LANDING OR GUEST (NO DASHBOARD SIDEBAR) -->
    <div v-if="route.meta?.layout === 'landing' || !auth.isAuthenticated" class="min-h-screen flex flex-col">
      <router-view />
    </div>

    <!-- FULL RESPONSIVE ENTERPRISE LAYOUT FOR AUTHENTICATED USERS -->
    <div v-else class="min-h-screen flex flex-col lg:flex-row">
      <!-- 1. DESKTOP SIDEBAR (Visible only on Monitor / Laptop screens >= 1024px) -->
      <aside 
        class="hidden lg:flex flex-col sticky top-0 h-screen shrink-0 border-r border-slate-200/80 bg-white z-30 transition-all duration-300 shadow-xs"
        :class="isSidebarOpen ? 'w-64' : 'w-20'"
      >
        <!-- School Brand Header -->
        <div class="h-20 flex items-center px-4 border-b border-slate-100 gap-3 shrink-0">
          <img 
            src="/logo.png" 
            alt="Logo SDI Bina Insan Mandiri" 
            class="w-11 h-11 object-contain shrink-0 drop-shadow-sm transition-transform hover:scale-105" 
          />
          <div v-show="isSidebarOpen" class="overflow-hidden transition-all duration-300">
            <h1 class="font-black text-sm text-slate-900 tracking-tight leading-none">SIM-BIM</h1>
            <p class="text-[11px] text-emerald-800 font-bold leading-tight mt-1 line-clamp-1">SDI Bina Insan Mandiri</p>
            <p class="text-[9px] text-slate-400 font-medium">Purwokerto</p>
          </div>
        </div>

        <!-- Navigation Menu Items -->
        <div class="flex-1 overflow-y-auto px-3 py-4 space-y-6 text-xs scrollbar-thin">
          <!-- GROUP: UTAMA -->
          <div>
            <div v-show="isSidebarOpen" class="px-3 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
              Menu Utama
            </div>
            <nav class="space-y-1">
              <router-link
                v-if="auth.isAdmin"
                to="/dashboard"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all"
                :class="route.path === '/dashboard' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                title="Dashboard Eksekutif"
              >
                <span v-show="isSidebarOpen" class="truncate">Dashboard Eksekutif</span>
              </router-link>

              <!-- Portal Wali Murid (Khusus Pengguna dengan Peran Wali Murid) -->
              <router-link
                v-if="auth.isWali"
                to="/portal-wali"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all"
                :class="route.path === '/portal-wali' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                title="Portal Wali Murid"
              >
                <span v-show="isSidebarOpen" class="truncate">Portal Wali Murid</span>
              </router-link>

              <!-- Presensi Mandiri GPS (Khusus Guru / Karyawan / Admin, bukan Wali Murid) -->
              <router-link
                v-if="!auth.isWali"
                to="/presensi"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all"
                :class="route.path === '/presensi' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                title="Presensi Mandiri Guru (GPS)"
              >
                <span v-show="isSidebarOpen" class="truncate">Presensi Mandiri Guru</span>
              </router-link>

              <!-- Presensi Siswa Harian (Khusus Wali Kelas) -->
              <router-link
                v-if="auth.isWaliKelas"
                to="/my-schedule?mode=homeroom"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all"
                :class="route.path === '/my-schedule' && route.query.mode === 'homeroom' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                title="Presensi Harian Siswa (Wali Kelas)"
              >
                <span v-show="isSidebarOpen" class="truncate">Presensi Siswa (Wali Kelas)</span>
              </router-link>

              <router-link
                to="/profile"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all"
                :class="route.path === '/profile' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                title="Profil Saya"
              >
                <span v-show="isSidebarOpen" class="truncate">Profil Saya</span>
              </router-link>
            </nav>
          </div>

          <!-- GROUP: MASTER DATA & AKADEMIK -->
          <div v-if="auth.isAdmin || auth.isGuru">
            <div v-show="isSidebarOpen" class="px-3 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
              Data & Akademik
            </div>
            <nav class="space-y-1">
              <router-link
                to="/students"
                class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all"
                :class="route.path === '/students' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                title="Data Siswa"
              >
                <span v-show="isSidebarOpen" class="truncate">Data Siswa</span>
              </router-link>

              <router-link
                v-if="auth.isAdmin"
                to="/teachers"
                class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all"
                :class="route.path === '/teachers' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                title="Guru & Tenaga Kependidikan"
              >
                <span v-show="isSidebarOpen" class="truncate">Guru & Tendik</span>
              </router-link>

              <!-- Manajemen Data & Akun Wali Murid (Khusus Admin Sekolah) -->
              <router-link
                v-if="auth.isAdmin"
                to="/guardians"
                class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all"
                :class="route.path === '/guardians' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                title="Data & Akun Wali Murid"
              >
                <span v-show="isSidebarOpen" class="truncate">Wali Murid</span>
              </router-link>

              <!-- Kelas & Jadwal KBM (Dengan Sub-menu) -->
              <div v-if="auth.isAdmin" class="space-y-0.5">
                <div class="flex items-center">
                  <router-link
                    to="/classes-schedules"
                    class="flex-1 flex items-center justify-between px-3 py-2 rounded-xl font-medium transition-all group"
                    :class="route.path === '/classes-schedules' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                    title="Kelas & Jadwal KBM"
                    @click="expandedMenus['classes-schedules'] = true"
                  >
                    <span v-show="isSidebarOpen" class="truncate">Kelas & Jadwal KBM</span>
                    <button
                      v-show="isSidebarOpen"
                      type="button"
                      @click.prevent.stop="toggleMenu('classes-schedules')"
                      class="p-1 rounded-lg transition-transform focus:outline-none"
                      :class="route.path === '/classes-schedules' ? 'text-white hover:bg-white/20' : 'text-slate-400 hover:text-slate-700 hover:bg-slate-200/60'"
                      :title="isMenuExpanded('classes-schedules') ? 'Tutup Sub-menu' : 'Buka Sub-menu'"
                    >
                      <svg 
                        class="w-3.5 h-3.5 transition-transform duration-200" 
                        :class="isMenuExpanded('classes-schedules') ? 'rotate-90' : ''"
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                  </router-link>
                </div>

                <!-- Sub-menu Kelas & Jadwal KBM -->
                <div 
                  v-show="isSidebarOpen && isMenuExpanded('classes-schedules')" 
                  class="pl-3.5 pr-1 py-1 ml-4 border-l-2 border-emerald-500/30 space-y-0.5"
                >
                  <router-link
                    to="/classes-schedules?tab=classes"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/classes-schedules' && (route.query.tab === 'classes' || !route.query.tab) ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/classes-schedules' && (route.query.tab === 'classes' || !route.query.tab) ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Rombel Kelas</span>
                  </router-link>
                  <router-link
                    to="/classes-schedules?tab=subjects"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/classes-schedules' && route.query.tab === 'subjects' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/classes-schedules' && route.query.tab === 'subjects' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Mata Pelajaran & Tematik</span>
                  </router-link>
                  <router-link
                    to="/classes-schedules?tab=assignments"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/classes-schedules' && route.query.tab === 'assignments' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/classes-schedules' && route.query.tab === 'assignments' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Pembagian Mengajar Guru</span>
                  </router-link>
                  <router-link
                    to="/classes-schedules?tab=schedules"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/classes-schedules' && route.query.tab === 'schedules' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/classes-schedules' && route.query.tab === 'schedules' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Jadwal Mingguan KBM</span>
                  </router-link>
                  <router-link
                    to="/classes-schedules?tab=slots"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/classes-schedules' && route.query.tab === 'slots' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/classes-schedules' && route.query.tab === 'slots' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Jam Pelajaran</span>
                  </router-link>
                  <router-link
                    to="/classes-schedules?tab=rooms"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/classes-schedules' && route.query.tab === 'rooms' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/classes-schedules' && route.query.tab === 'rooms' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Ruang Kelas</span>
                  </router-link>
                </div>
              </div>

              <router-link
                v-if="auth.isAdmin"
                to="/excel-imports"
                class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all"
                :class="route.path === '/excel-imports' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                title="Import Excel Dapodik"
              >
                <span v-show="isSidebarOpen" class="truncate">Import Excel Master</span>
              </router-link>
            </nav>
          </div>

          <!-- GROUP: MONITORING KBM & PROGRESS -->
          <div v-if="auth.isAdmin || auth.isGuru">
            <div v-show="isSidebarOpen" class="px-3 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
              KBM & Progres Siswa
            </div>
            <nav class="space-y-1">
              <router-link
                to="/my-schedule"
                class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all"
                :class="route.path === '/my-schedule' && route.query.mode !== 'homeroom' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                title="Jadwal Mengajar & Masuk Kelas"
              >
                <span v-show="isSidebarOpen" class="truncate">Jadwal Mengajar Guru</span>
              </router-link>

              <!-- Presensi Siswa Harian (Wali Kelas) -->
              <router-link
                v-if="auth.isWaliKelas"
                to="/my-schedule?mode=homeroom"
                class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all"
                :class="route.path === '/my-schedule' && route.query.mode === 'homeroom' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                title="Presensi Harian Siswa (Wali Kelas 1x Sehari)"
              >
                <span v-show="isSidebarOpen" class="truncate">Presensi Siswa (Wali Kelas)</span>
              </router-link>

              <!-- Rekap Presensi & KBM (Dengan Sub-menu untuk Admin) -->
              <div v-if="auth.isAdmin" class="space-y-0.5">
                <div class="flex items-center">
                  <router-link
                    to="/attendance-management"
                    class="flex-1 flex items-center justify-between px-3 py-2 rounded-xl font-medium transition-all group"
                    :class="route.path === '/attendance-management' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                    title="Rekap Presensi & KBM"
                    @click="expandedMenus['attendance-management'] = true"
                  >
                    <span v-show="isSidebarOpen" class="truncate">Rekap Presensi & KBM</span>
                    <button
                      v-show="isSidebarOpen"
                      type="button"
                      @click.prevent.stop="toggleMenu('attendance-management')"
                      class="p-1 rounded-lg transition-transform focus:outline-none"
                      :class="route.path === '/attendance-management' ? 'text-white hover:bg-white/20' : 'text-slate-400 hover:text-slate-700 hover:bg-slate-200/60'"
                      :title="isMenuExpanded('attendance-management') ? 'Tutup Sub-menu' : 'Buka Sub-menu'"
                    >
                      <svg 
                        class="w-3.5 h-3.5 transition-transform duration-200" 
                        :class="isMenuExpanded('attendance-management') ? 'rotate-90' : ''"
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                  </router-link>
                </div>

                <!-- Sub-menu Rekap Presensi & KBM -->
                <div 
                  v-show="isSidebarOpen && isMenuExpanded('attendance-management')" 
                  class="pl-3.5 pr-1 py-1 ml-4 border-l-2 border-emerald-500/30 space-y-0.5"
                >
                  <router-link
                    to="/attendance-management?tab=class_recap"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/attendance-management' && (route.query.tab === 'class_recap' || !route.query.tab) ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/attendance-management' && (route.query.tab === 'class_recap' || !route.query.tab) ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Rekap Kelas & KBM</span>
                  </router-link>
                  <router-link
                    to="/attendance-management?tab=student_recap"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/attendance-management' && route.query.tab === 'student_recap' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/attendance-management' && route.query.tab === 'student_recap' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Log Presensi Harian</span>
                  </router-link>
                  <router-link
                    to="/attendance-management?tab=kbm"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/attendance-management' && route.query.tab === 'kbm' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/attendance-management' && route.query.tab === 'kbm' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Bukti KBM & Jurnal Guru</span>
                  </router-link>
                  <router-link
                    to="/attendance-management?tab=manual_input"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/attendance-management' && route.query.tab === 'manual_input' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/attendance-management' && route.query.tab === 'manual_input' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Presensi Harian Siswa</span>
                  </router-link>
                  <router-link
                    to="/attendance-management?tab=teachers"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/attendance-management' && route.query.tab === 'teachers' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/attendance-management' && route.query.tab === 'teachers' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Presensi Guru & Tendik</span>
                  </router-link>
                </div>
              </div>

              <router-link
                v-else-if="auth.isGuru"
                to="/my-attendance-history"
                class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all"
                :class="route.path === '/my-attendance-history' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                title="Riwayat Presensi Guru"
              >
                <span v-show="isSidebarOpen" class="truncate">Riwayat Presensi Guru</span>
              </router-link>

              <!-- Tahfidz & Kegiatan (Dengan Sub-menu) -->
              <div class="space-y-0.5">
                <div class="flex items-center">
                  <router-link
                    to="/progress-activities"
                    class="flex-1 flex items-center justify-between px-3 py-2 rounded-xl font-medium transition-all group"
                    :class="route.path === '/progress-activities' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                    title="Tahfidz & Kegiatan"
                    @click="expandedMenus['progress-activities'] = true"
                  >
                    <span v-show="isSidebarOpen" class="truncate">Tahfidz & Kegiatan</span>
                    <button
                      v-show="isSidebarOpen"
                      type="button"
                      @click.prevent.stop="toggleMenu('progress-activities')"
                      class="p-1 rounded-lg transition-transform focus:outline-none"
                      :class="route.path === '/progress-activities' ? 'text-white hover:bg-white/20' : 'text-slate-400 hover:text-slate-700 hover:bg-slate-200/60'"
                      :title="isMenuExpanded('progress-activities') ? 'Tutup Sub-menu' : 'Buka Sub-menu'"
                    >
                      <svg 
                        class="w-3.5 h-3.5 transition-transform duration-200" 
                        :class="isMenuExpanded('progress-activities') ? 'rotate-90' : ''"
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                  </router-link>
                </div>

                <!-- Sub-menu Tahfidz & Kegiatan -->
                <div 
                  v-show="isSidebarOpen && isMenuExpanded('progress-activities')" 
                  class="pl-3.5 pr-1 py-1 ml-4 border-l-2 border-emerald-500/30 space-y-0.5"
                >
                  <router-link
                    to="/progress-activities?tab=progress"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/progress-activities' && (route.query.tab === 'progress' || !route.query.tab) ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/progress-activities' && (route.query.tab === 'progress' || !route.query.tab) ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Progres Tahfidz</span>
                  </router-link>
                  <router-link
                    to="/progress-activities?tab=activities"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/progress-activities' && route.query.tab === 'activities' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/progress-activities' && route.query.tab === 'activities' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Kegiatan Harian Sekolah</span>
                  </router-link>
                  <router-link
                    to="/progress-activities?tab=announcements"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/progress-activities' && route.query.tab === 'announcements' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/progress-activities' && route.query.tab === 'announcements' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Papan Pengumuman</span>
                  </router-link>
                </div>
              </div>

              <router-link
                v-if="auth.isAdmin"
                to="/rapor"
                class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all"
                :class="route.path === '/rapor' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                title="Rapor Siswa Kurikulum Merdeka"
              >
                <span v-show="isSidebarOpen" class="truncate">Rapor Siswa</span>
              </router-link>
            </nav>
          </div>

          <!-- GROUP: KEUANGAN & SARPRAS -->
          <div v-if="auth.isAdmin">
            <div v-show="isSidebarOpen" class="px-3 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
              Keuangan & Aset
            </div>
            <nav class="space-y-1">
              <!-- Keuangan & SPP (Dengan Sub-menu) -->
              <div class="space-y-0.5">
                <div class="flex items-center">
                  <router-link
                    to="/finance"
                    class="flex-1 flex items-center justify-between px-3 py-2 rounded-xl font-medium transition-all group"
                    :class="route.path === '/finance' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                    title="Keuangan & SPP"
                    @click="expandedMenus['finance'] = true"
                  >
                    <span v-show="isSidebarOpen" class="truncate">Keuangan & SPP</span>
                    <button
                      v-show="isSidebarOpen"
                      type="button"
                      @click.prevent.stop="toggleMenu('finance')"
                      class="p-1 rounded-lg transition-transform focus:outline-none"
                      :class="route.path === '/finance' ? 'text-white hover:bg-white/20' : 'text-slate-400 hover:text-slate-700 hover:bg-slate-200/60'"
                      :title="isMenuExpanded('finance') ? 'Tutup Sub-menu' : 'Buka Sub-menu'"
                    >
                      <svg 
                        class="w-3.5 h-3.5 transition-transform duration-200" 
                        :class="isMenuExpanded('finance') ? 'rotate-90' : ''"
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                  </router-link>
                </div>

                <!-- Sub-menu Keuangan & SPP -->
                <div 
                  v-show="isSidebarOpen && isMenuExpanded('finance')" 
                  class="pl-3.5 pr-1 py-1 ml-4 border-l-2 border-emerald-500/30 space-y-0.5"
                >
                  <router-link
                    to="/finance?tab=invoices"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/finance' && (route.query.tab === 'invoices' || !route.query.tab) ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/finance' && (route.query.tab === 'invoices' || !route.query.tab) ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Tagihan Siswa (Invoices)</span>
                  </router-link>
                  <router-link
                    to="/finance?tab=payments"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/finance' && route.query.tab === 'payments' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/finance' && route.query.tab === 'payments' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Riwayat Kuitansi & Bayar</span>
                  </router-link>
                  <router-link
                    to="/finance?tab=batch"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/finance' && route.query.tab === 'batch' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/finance' && route.query.tab === 'batch' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Generator SPP Massal</span>
                  </router-link>
                  <router-link
                    to="/finance?tab=cashbook"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/finance' && route.query.tab === 'cashbook' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/finance' && route.query.tab === 'cashbook' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Buku Kas Masuk/Keluar</span>
                  </router-link>
                  <router-link
                    to="/finance?tab=fee_types"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/finance' && route.query.tab === 'fee_types' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/finance' && route.query.tab === 'fee_types' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Tarif & Jenis Biaya</span>
                  </router-link>
                </div>
              </div>

              <router-link
                to="/assets"
                class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all"
                :class="route.path === '/assets' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                title="Sarana & Prasarana"
              >
                <span v-show="isSidebarOpen" class="truncate">Sarpras & Aset</span>
              </router-link>
            </nav>
          </div>

          <!-- GROUP: PENGATURAN & KEAMANAN -->
          <div v-if="auth.isAdmin">
            <div v-show="isSidebarOpen" class="px-3 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
              Sistem
            </div>
            <nav class="space-y-1">
              <!-- Pengguna & Geofence (Dengan Sub-menu) -->
              <div class="space-y-0.5">
                <div class="flex items-center">
                  <router-link
                    to="/users-settings"
                    class="flex-1 flex items-center justify-between px-3 py-2 rounded-xl font-medium transition-all group"
                    :class="route.path === '/users-settings' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                    title="Pengguna & Sistem"
                    @click="expandedMenus['users-settings'] = true"
                  >
                    <span v-show="isSidebarOpen" class="truncate">Pengguna & Geofence</span>
                    <button
                      v-show="isSidebarOpen"
                      type="button"
                      @click.prevent.stop="toggleMenu('users-settings')"
                      class="p-1 rounded-lg transition-transform focus:outline-none"
                      :class="route.path === '/users-settings' ? 'text-white hover:bg-white/20' : 'text-slate-400 hover:text-slate-700 hover:bg-slate-200/60'"
                      :title="isMenuExpanded('users-settings') ? 'Tutup Sub-menu' : 'Buka Sub-menu'"
                    >
                      <svg 
                        class="w-3.5 h-3.5 transition-transform duration-200" 
                        :class="isMenuExpanded('users-settings') ? 'rotate-90' : ''"
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                  </router-link>
                </div>

                <!-- Sub-menu Pengguna & Geofence -->
                <div 
                  v-show="isSidebarOpen && isMenuExpanded('users-settings')" 
                  class="pl-3.5 pr-1 py-1 ml-4 border-l-2 border-emerald-500/30 space-y-0.5"
                >
                  <router-link
                    to="/users-settings?tab=users"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/users-settings' && (route.query.tab === 'users' || !route.query.tab) ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/users-settings' && (route.query.tab === 'users' || !route.query.tab) ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Akun & Hak Akses</span>
                  </router-link>
                  <router-link
                    to="/users-settings?tab=logs"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/users-settings' && route.query.tab === 'logs' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/users-settings' && route.query.tab === 'logs' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Log Audit Aktivitas</span>
                  </router-link>
                  <router-link
                    to="/users-settings?tab=settings"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/users-settings' && route.query.tab === 'settings' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/users-settings' && route.query.tab === 'settings' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Pengaturan & Geofence GPS</span>
                  </router-link>
                </div>
              </div>

              <router-link
                to="/backup"
                class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all"
                :class="route.path === '/backup' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                title="Backup & Restore"
              >
                <span v-show="isSidebarOpen" class="truncate">Backup Database</span>
              </router-link>
            </nav>
          </div>
        </div>

        <!-- Sidebar Collapse Toggle Button -->
        <div class="p-3 border-t border-slate-100 shrink-0">
          <button 
            @click="isSidebarOpen = !isSidebarOpen"
            class="flex items-center justify-center p-2 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition w-full text-xs font-semibold"
            :title="isSidebarOpen ? 'Perkecil Sidebar' : 'Buka Sidebar'"
          >
            <span v-if="isSidebarOpen" class="flex items-center gap-2">◀ Perkecil Menu</span>
            <span v-else>▶</span>
          </button>
        </div>
      </aside>

      <!-- 2. MAIN VIEW AREA (Adaptive for Mobile, iPad, and Monitor) -->
      <div class="flex-1 flex flex-col min-w-0">
        <!-- TOPBAR: Optimized for all screens -->
        <header class="bg-white/95 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-20 shadow-xs h-16 flex items-center justify-between px-3 sm:px-6">
          <!-- Left: Hamburger (for mobile & iPad) + Title/Brand -->
          <div class="flex items-center gap-2 sm:gap-4">
            <!-- Mobile/iPad Drawer Trigger -->
            <button 
              @click="isMobileDrawerOpen = true"
              class="lg:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500"
              aria-label="Buka Menu Navigasi"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>

            <!-- Brand visible on Mobile/iPad -->
            <div class="flex items-center gap-2.5 lg:hidden">
              <img src="/logo.png" alt="Logo" class="w-8 h-8 object-contain drop-shadow-sm" />
              <div>
                <span class="font-extrabold text-sm text-slate-900 leading-none block">SIM-BIM</span>
                <span class="text-[10px] text-emerald-800 font-semibold leading-tight line-clamp-1">SDI Bina Insan Mandiri</span>
              </div>
            </div>

            <!-- Academic Year Pill Badge -->
            <div class="hidden sm:flex items-center gap-2">
              <span 
                v-if="activeSemesterInfo?.status === 'aktif'"
                class="px-3 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full text-xs font-bold tracking-tight flex items-center gap-1.5 shadow-xs"
              >
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                TA {{ activeSemesterInfo.academicYear }} • {{ activeSemesterInfo.semester }}
              </span>
              <span 
                v-else-if="activeSemesterInfo?.status === 'reopened'"
                class="px-3 py-1 bg-amber-50 text-amber-800 border border-amber-300 rounded-full text-xs font-bold tracking-tight flex items-center gap-1.5 shadow-xs"
              >
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                TA {{ activeSemesterInfo.academicYear }} • {{ activeSemesterInfo.semester }} (Buka Sementara)
              </span>
              <span 
                v-else-if="activeSemesterInfo?.status === 'terkunci'"
                class="px-3 py-1 bg-slate-100 text-slate-700 border border-slate-300 rounded-full text-xs font-bold tracking-tight flex items-center gap-1.5 shadow-xs"
              >
                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                TA {{ activeSemesterInfo.academicYear }} • {{ activeSemesterInfo.semester }} (Terkunci)
              </span>
              <span 
                v-else
                class="px-3 py-1 bg-sky-50 text-sky-800 border border-sky-200 rounded-full text-xs font-bold tracking-tight flex items-center gap-1.5 shadow-xs"
              >
                <span class="w-2 h-2 rounded-full bg-sky-400"></span>
                Masa Libur Antar Semester
              </span>
              <span class="text-xs text-slate-400 hidden xl:inline">• Purwokerto, Banyumas</span>
            </div>
          </div>

          <!-- Right: User Profile & Logout -->
          <div class="flex items-center gap-2 sm:gap-4">
            <!-- User Info Badge (Clickable link to profile) -->
            <router-link 
              to="/profile" 
              class="flex items-center gap-2.5 hover:opacity-85 transition p-1 rounded-xl"
              title="Lihat Profil Saya"
            >
              <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-emerald-100 text-emerald-800 font-bold text-xs flex items-center justify-center border border-emerald-300 shadow-xs shrink-0 overflow-hidden">
                <img 
                  v-if="auth.user?.foto_path" 
                  :src="auth.user.foto_path.startsWith('http') ? auth.user.foto_path : `/storage/${auth.user.foto_path.replace(/^\/?(storage\/)?/, '')}`" 
                  alt="Avatar" 
                  class="w-full h-full object-cover" 
                />
                <span v-else>{{ auth.userName.charAt(0).toUpperCase() }}</span>
              </div>
              <div class="text-right hidden sm:block">
                <div class="text-xs font-bold text-slate-900 leading-tight max-w-[160px] truncate">{{ auth.userName }}</div>
                <div class="text-[10px] text-emerald-700 uppercase font-extrabold tracking-wider">{{ auth.role?.replace('_', ' ') }}</div>
              </div>
            </router-link>

            <!-- Logout Button -->
            <button
              @click="handleLogout"
              title="Keluar dari Akun"
              class="px-2.5 sm:px-3 py-1.5 text-slate-600 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition border border-slate-200 hover:border-rose-200 text-xs font-semibold flex items-center gap-1.5 shadow-xs"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              <span class="hidden md:inline">Keluar</span>
            </button>
          </div>
        </header>

        <!-- 3. SLIDE-OVER DRAWER MENU (For Mobile & iPad/Tablet) -->
        <transition
          enter-active-class="transition-opacity duration-300"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="transition-opacity duration-300"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div 
            v-if="isMobileDrawerOpen" 
            class="lg:hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex"
            @click="isMobileDrawerOpen = false"
          >
            <!-- Drawer Content -->
            <div 
              class="w-72 sm:w-80 bg-white h-full shadow-2xl flex flex-col transform transition-transform duration-300 ease-out"
              @click.stop
            >
              <!-- Drawer Header -->
              <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-3">
                  <img src="/logo.png" alt="Logo" class="w-10 h-10 object-contain drop-shadow-sm" />
                  <div>
                    <h3 class="font-bold text-slate-900 text-sm leading-tight">SIM-BIM</h3>
                    <p class="text-[10px] text-emerald-800 font-semibold">SDI Bina Insan Mandiri</p>
                  </div>
                </div>
                <button 
                  @click="isMobileDrawerOpen = false"
                  class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 rounded-xl"
                  aria-label="Tutup Menu"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Drawer User Banner -->
              <div class="px-4 py-3 bg-emerald-50/60 border-b border-emerald-100 flex items-center justify-between">
                <div>
                  <div class="text-xs font-bold text-emerald-950">{{ auth.userName }}</div>
                  <div class="text-[10px] text-emerald-700 uppercase font-extrabold">{{ auth.role?.replace('_', ' ') }}</div>
                </div>
                <span class="text-[10px] px-2 py-0.5 bg-emerald-200/60 text-emerald-900 rounded font-mono font-bold">ONLINE</span>
              </div>

              <!-- Drawer Nav Items -->
              <nav class="flex-1 overflow-y-auto p-3 space-y-4 text-xs">
                <!-- Group 1 -->
                <div>
                  <div class="px-2 pb-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Utama</div>
                  <div class="space-y-1">
                    <router-link 
                      v-if="auth.isAdmin" 
                      to="/dashboard" 
                      class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium"
                      :class="route.path === '/dashboard' ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'text-slate-700 hover:bg-slate-100'"
                    >
                      <span>Dashboard Eksekutif</span>
                    </router-link>
                    <router-link 
                      v-if="!auth.isWali"
                      to="/presensi" 
                      class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium"
                      :class="route.path === '/presensi' ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <span>Presensi Mandiri Guru</span>
                    </router-link>
                    <router-link 
                      v-if="auth.isWaliKelas"
                      to="/my-schedule?mode=homeroom" 
                      class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium"
                      :class="route.path === '/my-schedule' && route.query.mode === 'homeroom' ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <span>Presensi Siswa (Wali Kelas)</span>
                    </router-link>
                    <router-link 
                      to="/profile" 
                      class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium"
                      :class="route.path === '/profile' ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'text-slate-700 hover:bg-slate-100'"
                    >
                      <span>Profil Saya</span>
                    </router-link>
                    <router-link 
                      v-if="auth.isWali || auth.isAdmin" 
                      to="/portal-wali" 
                      class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium"
                      :class="route.path === '/portal-wali' ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'text-slate-700 hover:bg-slate-100'"
                    >
                      <span>Portal Wali Murid</span>
                    </router-link>
                  </div>
                </div>

                <!-- Group 2 -->
                <div v-if="auth.isAdmin || auth.isGuru">
                  <div class="px-2 pb-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Data & Akademik</div>
                  <div class="space-y-1">
                    <router-link 
                      to="/students" 
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium"
                      :class="route.path === '/students' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                    >
                      <span>Data Siswa</span>
                    </router-link>
                    <router-link 
                      v-if="auth.isAdmin"
                      to="/teachers" 
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium"
                      :class="route.path === '/teachers' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                    >
                      <span>Guru & Tendik</span>
                    </router-link>
                    <router-link 
                      v-if="auth.isAdmin"
                      to="/guardians" 
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium"
                      :class="route.path === '/guardians' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <span>Wali Murid</span>
                    </router-link>

                    <!-- Kelas & Jadwal KBM (Mobile Sub-menu) -->
                    <div v-if="auth.isAdmin" class="space-y-0.5">
                      <div class="flex items-center">
                        <router-link 
                          to="/classes-schedules" 
                          class="flex-1 flex items-center justify-between px-3 py-2 rounded-xl font-medium"
                          :class="route.path === '/classes-schedules' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                          @click="expandedMenus['classes-schedules'] = true; isMobileDrawerOpen = false"
                        >
                          <span>Kelas & Jadwal KBM</span>
                          <button
                            type="button"
                            @click.prevent.stop="toggleMenu('classes-schedules')"
                            class="p-1 rounded-lg transition-transform focus:outline-none"
                            :class="route.path === '/classes-schedules' ? 'text-white hover:bg-white/20' : 'text-slate-400 hover:text-slate-700 hover:bg-slate-200/60'"
                          >
                            <svg 
                              class="w-3.5 h-3.5 transition-transform duration-200" 
                              :class="isMenuExpanded('classes-schedules') ? 'rotate-90' : ''"
                              fill="none" 
                              stroke="currentColor" 
                              viewBox="0 0 24 24"
                            >
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                          </button>
                        </router-link>
                      </div>

                      <div v-show="isMenuExpanded('classes-schedules')" class="pl-3.5 pr-1 py-1 ml-3 border-l-2 border-emerald-500/30 space-y-0.5">
                        <router-link
                          to="/classes-schedules?tab=classes"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/classes-schedules' && (route.query.tab === 'classes' || !route.query.tab) ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/classes-schedules' && (route.query.tab === 'classes' || !route.query.tab) ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Rombel Kelas</span>
                        </router-link>
                        <router-link
                          to="/classes-schedules?tab=subjects"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/classes-schedules' && route.query.tab === 'subjects' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/classes-schedules' && route.query.tab === 'subjects' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Mata Pelajaran & Tematik</span>
                        </router-link>
                        <router-link
                          to="/classes-schedules?tab=assignments"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/classes-schedules' && route.query.tab === 'assignments' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/classes-schedules' && route.query.tab === 'assignments' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Pembagian Mengajar Guru</span>
                        </router-link>
                        <router-link
                          to="/classes-schedules?tab=schedules"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/classes-schedules' && route.query.tab === 'schedules' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/classes-schedules' && route.query.tab === 'schedules' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Jadwal Mingguan KBM</span>
                        </router-link>
                        <router-link
                          to="/classes-schedules?tab=slots"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/classes-schedules' && route.query.tab === 'slots' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/classes-schedules' && route.query.tab === 'slots' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Jam Pelajaran</span>
                        </router-link>
                        <router-link
                          to="/classes-schedules?tab=rooms"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/classes-schedules' && route.query.tab === 'rooms' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/classes-schedules' && route.query.tab === 'rooms' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Ruang Kelas</span>
                        </router-link>
                      </div>
                    </div>

                    <router-link 
                      v-if="auth.isAdmin"
                      to="/excel-imports" 
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium"
                      :class="route.path === '/excel-imports' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <span>Import Excel Master</span>
                    </router-link>
                  </div>
                </div>

                <!-- Group 3 -->
                <div v-if="auth.isAdmin || auth.isGuru">
                  <div class="px-2 pb-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">KBM & Progres</div>
                  <div class="space-y-1">
                    <router-link 
                      to="/my-schedule" 
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium"
                      :class="route.path === '/my-schedule' && route.query.mode !== 'homeroom' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <span>Jadwal Mengajar Guru</span>
                    </router-link>

                    <router-link 
                      v-if="auth.isWaliKelas"
                      to="/my-schedule?mode=homeroom" 
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium"
                      :class="route.path === '/my-schedule' && route.query.mode === 'homeroom' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <span>Presensi Siswa (Wali Kelas)</span>
                    </router-link>
                    <!-- Rekap Presensi & KBM (Mobile Sub-menu) -->
                    <div v-if="auth.isAdmin" class="space-y-0.5">
                      <div class="flex items-center">
                        <router-link 
                          to="/attendance-management" 
                          class="flex-1 flex items-center justify-between px-3 py-2 rounded-xl font-medium"
                          :class="route.path === '/attendance-management' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                          @click="expandedMenus['attendance-management'] = true; isMobileDrawerOpen = false"
                        >
                          <span>Rekap Presensi & KBM</span>
                          <button
                            type="button"
                            @click.prevent.stop="toggleMenu('attendance-management')"
                            class="p-1 rounded-lg transition-transform focus:outline-none"
                            :class="route.path === '/attendance-management' ? 'text-white hover:bg-white/20' : 'text-slate-400 hover:text-slate-700 hover:bg-slate-200/60'"
                          >
                            <svg 
                              class="w-3.5 h-3.5 transition-transform duration-200" 
                              :class="isMenuExpanded('attendance-management') ? 'rotate-90' : ''"
                              fill="none" 
                              stroke="currentColor" 
                              viewBox="0 0 24 24"
                            >
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                          </button>
                        </router-link>
                      </div>

                      <div v-show="isMenuExpanded('attendance-management')" class="pl-3.5 pr-1 py-1 ml-3 border-l-2 border-emerald-500/30 space-y-0.5">
                        <router-link
                          to="/attendance-management?tab=class_recap"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/attendance-management' && (route.query.tab === 'class_recap' || !route.query.tab) ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/attendance-management' && (route.query.tab === 'class_recap' || !route.query.tab) ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Rekap Kelas & KBM</span>
                        </router-link>
                        <router-link
                          to="/attendance-management?tab=student_recap"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/attendance-management' && route.query.tab === 'student_recap' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/attendance-management' && route.query.tab === 'student_recap' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Log Presensi Harian</span>
                        </router-link>
                        <router-link
                          to="/attendance-management?tab=kbm"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/attendance-management' && route.query.tab === 'kbm' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/attendance-management' && route.query.tab === 'kbm' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Bukti KBM & Jurnal Guru</span>
                        </router-link>
                        <router-link
                          to="/attendance-management?tab=manual_input"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/attendance-management' && route.query.tab === 'manual_input' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/attendance-management' && route.query.tab === 'manual_input' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Presensi Harian Siswa</span>
                        </router-link>
                        <router-link
                          to="/attendance-management?tab=teachers"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/attendance-management' && route.query.tab === 'teachers' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/attendance-management' && route.query.tab === 'teachers' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Presensi Guru & Tendik</span>
                        </router-link>
                      </div>
                    </div>

                    <router-link 
                      v-else-if="auth.isGuru"
                      to="/my-attendance-history" 
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium"
                      :class="route.path === '/my-attendance-history' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <span>Riwayat Presensi Guru</span>
                    </router-link>

                    <!-- Tahfidz & Kegiatan (Mobile Sub-menu) -->
                    <div class="space-y-0.5">
                      <div class="flex items-center">
                        <router-link 
                          to="/progress-activities" 
                          class="flex-1 flex items-center justify-between px-3 py-2 rounded-xl font-medium"
                          :class="route.path === '/progress-activities' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                          @click="expandedMenus['progress-activities'] = true; isMobileDrawerOpen = false"
                        >
                          <span>Tahfidz & Kegiatan</span>
                          <button
                            type="button"
                            @click.prevent.stop="toggleMenu('progress-activities')"
                            class="p-1 rounded-lg transition-transform focus:outline-none"
                            :class="route.path === '/progress-activities' ? 'text-white hover:bg-white/20' : 'text-slate-400 hover:text-slate-700 hover:bg-slate-200/60'"
                          >
                            <svg 
                              class="w-3.5 h-3.5 transition-transform duration-200" 
                              :class="isMenuExpanded('progress-activities') ? 'rotate-90' : ''"
                              fill="none" 
                              stroke="currentColor" 
                              viewBox="0 0 24 24"
                            >
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                          </button>
                        </router-link>
                      </div>

                      <div v-show="isMenuExpanded('progress-activities')" class="pl-3.5 pr-1 py-1 ml-3 border-l-2 border-emerald-500/30 space-y-0.5">
                        <router-link
                          to="/progress-activities?tab=progress"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/progress-activities' && (route.query.tab === 'progress' || !route.query.tab) ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/progress-activities' && (route.query.tab === 'progress' || !route.query.tab) ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Progres Tahfidz</span>
                        </router-link>
                        <router-link
                          to="/progress-activities?tab=activities"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/progress-activities' && route.query.tab === 'activities' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/progress-activities' && route.query.tab === 'activities' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Kegiatan Harian Sekolah</span>
                        </router-link>
                        <router-link
                          to="/progress-activities?tab=announcements"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/progress-activities' && route.query.tab === 'announcements' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/progress-activities' && route.query.tab === 'announcements' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Papan Pengumuman</span>
                        </router-link>
                      </div>
                    </div>

                    <router-link 
                      v-if="auth.isAdmin"
                      to="/rapor" 
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium"
                      :class="route.path === '/rapor' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <span>Rapor Siswa</span>
                    </router-link>
                  </div>
                </div>

                <!-- Group 4 -->
                <div v-if="auth.isAdmin">
                  <div class="px-2 pb-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Keuangan & Aset</div>
                  <div class="space-y-1">
                    <!-- Keuangan & SPP (Mobile Sub-menu) -->
                    <div class="space-y-0.5">
                      <div class="flex items-center">
                        <router-link 
                          to="/finance" 
                          class="flex-1 flex items-center justify-between px-3 py-2 rounded-xl font-medium"
                          :class="route.path === '/finance' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                          @click="expandedMenus['finance'] = true; isMobileDrawerOpen = false"
                        >
                          <span>Keuangan & SPP</span>
                          <button
                            type="button"
                            @click.prevent.stop="toggleMenu('finance')"
                            class="p-1 rounded-lg transition-transform focus:outline-none"
                            :class="route.path === '/finance' ? 'text-white hover:bg-white/20' : 'text-slate-400 hover:text-slate-700 hover:bg-slate-200/60'"
                          >
                            <svg 
                              class="w-3.5 h-3.5 transition-transform duration-200" 
                              :class="isMenuExpanded('finance') ? 'rotate-90' : ''"
                              fill="none" 
                              stroke="currentColor" 
                              viewBox="0 0 24 24"
                            >
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                          </button>
                        </router-link>
                      </div>

                      <div v-show="isMenuExpanded('finance')" class="pl-3.5 pr-1 py-1 ml-3 border-l-2 border-emerald-500/30 space-y-0.5">
                        <router-link
                          to="/finance?tab=invoices"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/finance' && (route.query.tab === 'invoices' || !route.query.tab) ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/finance' && (route.query.tab === 'invoices' || !route.query.tab) ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Tagihan Siswa</span>
                        </router-link>
                        <router-link
                          to="/finance?tab=payments"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/finance' && route.query.tab === 'payments' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/finance' && route.query.tab === 'payments' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Riwayat Kuitansi & Bayar</span>
                        </router-link>
                        <router-link
                          to="/finance?tab=batch"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/finance' && route.query.tab === 'batch' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/finance' && route.query.tab === 'batch' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Generator SPP Massal</span>
                        </router-link>
                        <router-link
                          to="/finance?tab=cashbook"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/finance' && route.query.tab === 'cashbook' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/finance' && route.query.tab === 'cashbook' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Buku Kas Masuk/Keluar</span>
                        </router-link>
                        <router-link
                          to="/finance?tab=fee_types"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/finance' && route.query.tab === 'fee_types' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/finance' && route.query.tab === 'fee_types' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Tarif & Jenis Biaya</span>
                        </router-link>
                      </div>
                    </div>

                    <router-link 
                      to="/assets" 
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium"
                      :class="route.path === '/assets' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <span>Sarpras & Aset</span>
                    </router-link>
                  </div>
                </div>

                <!-- Group 5 -->
                <div v-if="auth.isAdmin">
                  <div class="px-2 pb-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sistem</div>
                  <div class="space-y-1">
                    <!-- Pengguna & Geofence (Mobile Sub-menu) -->
                    <div class="space-y-0.5">
                      <div class="flex items-center">
                        <router-link 
                          to="/users-settings" 
                          class="flex-1 flex items-center justify-between px-3 py-2 rounded-xl font-medium"
                          :class="route.path === '/users-settings' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                          @click="expandedMenus['users-settings'] = true; isMobileDrawerOpen = false"
                        >
                          <span>Pengguna & Geofence</span>
                          <button
                            type="button"
                            @click.prevent.stop="toggleMenu('users-settings')"
                            class="p-1 rounded-lg transition-transform focus:outline-none"
                            :class="route.path === '/users-settings' ? 'text-white hover:bg-white/20' : 'text-slate-400 hover:text-slate-700 hover:bg-slate-200/60'"
                          >
                            <svg 
                              class="w-3.5 h-3.5 transition-transform duration-200" 
                              :class="isMenuExpanded('users-settings') ? 'rotate-90' : ''"
                              fill="none" 
                              stroke="currentColor" 
                              viewBox="0 0 24 24"
                            >
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                          </button>
                        </router-link>
                      </div>

                      <div v-show="isMenuExpanded('users-settings')" class="pl-3.5 pr-1 py-1 ml-3 border-l-2 border-emerald-500/30 space-y-0.5">
                        <router-link
                          to="/users-settings?tab=users"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/users-settings' && (route.query.tab === 'users' || !route.query.tab) ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/users-settings' && (route.query.tab === 'users' || !route.query.tab) ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Akun & Hak Akses</span>
                        </router-link>
                        <router-link
                          to="/users-settings?tab=logs"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/users-settings' && route.query.tab === 'logs' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/users-settings' && route.query.tab === 'logs' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Log Audit Aktivitas</span>
                        </router-link>
                        <router-link
                          to="/users-settings?tab=settings"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/users-settings' && route.query.tab === 'settings' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/users-settings' && route.query.tab === 'settings' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Pengaturan & Geofence GPS</span>
                        </router-link>
                      </div>
                    </div>
                    <router-link 
                      to="/backup" 
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium"
                      :class="route.path === '/backup' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                    >
                      <span>Backup Database</span>
                    </router-link>
                  </div>
                </div>
              </nav>

              <!-- Drawer Footer -->
              <div class="p-4 border-t border-slate-100 bg-slate-50">
                <button
                  @click="handleLogout"
                  class="w-full py-2.5 px-3 bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold rounded-xl text-xs flex items-center justify-center gap-2 border border-rose-200 transition"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                  </svg>
                  <span>Keluar dari Akun</span>
                </button>
              </div>
            </div>
          </div>
        </transition>

        <!-- 4. MAIN ROUTE CONTENT CONTAINER (Optimized padding for all devices) -->
        <main class="flex-1 p-3.5 sm:p-6 lg:p-8 max-w-7xl 2xl:max-w-[1600px] w-full mx-auto pb-24 lg:pb-8">
          <router-view />
        </main>

        <!-- 5. MOBILE BOTTOM NAVIGATION (Visible on Mobile & iPad Portrait < 1024px) -->
        <nav 
          class="lg:hidden fixed bottom-0 left-0 right-0 z-30 bg-white/95 backdrop-blur-md border-t border-slate-200/90 shadow-lg px-2 py-1.5 flex justify-around items-center text-[10px] font-semibold text-slate-500 pb-[max(0.375rem,env(safe-area-inset-bottom))]"
        >
          <!-- Dashboard button -->
          <router-link
            v-if="auth.isAdmin"
            to="/dashboard"
            class="flex flex-col items-center py-2 px-2.5 rounded-xl transition min-w-[56px]"
            :class="route.path === '/dashboard' ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-slate-800'"
          >
            <span>Dashboard</span>
          </router-link>

          <!-- Presensi button -->
          <router-link
            to="/presensi"
            class="flex flex-col items-center py-2 px-2.5 rounded-xl transition min-w-[56px]"
            :class="route.path === '/presensi' ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-slate-800'"
          >
            <span>Presensi</span>
          </router-link>

          <!-- Siswa / Portal button -->
          <router-link
            :to="auth.isWali ? '/portal-wali' : '/students'"
            class="flex flex-col items-center py-2 px-2.5 rounded-xl transition min-w-[56px]"
            :class="(route.path === '/students' || route.path === '/portal-wali') ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-slate-800'"
          >
            <span>{{ auth.isWali ? 'Anak Saya' : 'Siswa' }}</span>
          </router-link>

          <!-- Keuangan button (for admin) or KBM -->
          <router-link
            v-if="auth.isAdmin"
            to="/finance"
            class="flex flex-col items-center py-2 px-2.5 rounded-xl transition min-w-[56px]"
            :class="route.path === '/finance' ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-slate-800'"
          >
            <span>SPP/Kas</span>
          </router-link>
          <router-link
            v-else-if="auth.isGuru"
            to="/my-attendance-history"
            class="flex flex-col items-center py-2 px-2.5 rounded-xl transition min-w-[56px]"
            :class="route.path === '/my-attendance-history' ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-slate-800'"
          >
            <span>Riwayat</span>
          </router-link>

          <!-- Profil button -->
          <router-link
            to="/profile"
            class="flex flex-col items-center py-2 px-2.5 rounded-xl transition min-w-[56px]"
            :class="route.path === '/profile' ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-slate-800'"
          >
            <span>Profil</span>
          </router-link>

          <!-- Menu Drawer Toggle -->
          <button
            @click="isMobileDrawerOpen = true"
            class="flex flex-col items-center py-2 px-2.5 rounded-xl transition min-w-[56px] text-slate-500 hover:text-slate-900"
          >
            <span class="text-base leading-tight font-bold">☰</span>
            <span class="text-[9px]">Menu</span>
          </button>
        </nav>
      </div>
    </div>
  </div>
</template>
