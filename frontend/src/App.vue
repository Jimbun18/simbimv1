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
}>({
  academicYear: '2026/2027',
  semester: 'Gasal',
  status: 'aktif',
  reopened: false,
})

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
          const lockedSem = res.data.data.find((s: any) => s.status === 'terkunci')
          if (lockedSem) {
            activeSemesterInfo.value = {
              academicYear: lockedSem.academic_year?.nama || '2026/2027',
              semester: lockedSem.semester_label || (lockedSem.semester === 'gasal' ? 'Gasal' : 'Genap'),
              status: 'terkunci',
              reopened: false,
            }
          } else {
            activeSemesterInfo.value = {
              academicYear: '2026/2027',
              semester: 'Libur',
              status: 'libur',
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

watch(
  () => auth.isAuthenticated,
  (isAuth) => {
    if (isAuth) {
      auth.fetchProfile()
      loadActivePeriod()
    }
  }
)

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

const toggleSidebar = () => {
  if (typeof window !== 'undefined' && window.innerWidth < 1024) {
    isMobileDrawerOpen.value = !isMobileDrawerOpen.value
  } else {
    isSidebarOpen.value = !isSidebarOpen.value
  }
}

// State for expanding/collapsing sub-menus
const expandedMenus = ref<Record<string, boolean>>({
  'classes-schedules': false,
  'attendance-management': false,
  'progress-activities': false,
  'rapor': false,
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
  if (newPath === '/rapor') expandedMenus.value['rapor'] = true
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
        class="hidden lg:flex flex-col sticky top-0 h-screen shrink-0 border-r border-slate-200/80 bg-white z-30 transition-all duration-300 shadow-xs no-print print:hidden"
        :class="isSidebarOpen ? 'w-64' : 'w-20'"
      >
        <!-- School Brand Header with Animated Sidebar Toggle Button matching target design -->
        <div 
          class="h-16 flex items-center border-b border-slate-100 shrink-0 px-3.5 bg-white transition-all duration-300"
          :class="isSidebarOpen ? 'justify-between' : 'justify-center'"
        >
          <!-- Logo + School Title (Visible when expanded) -->
          <div v-show="isSidebarOpen" class="flex items-center gap-2.5 min-w-0 overflow-hidden">
            <img 
              src="/logo.png" 
              alt="Logo SDI Bina Insan Mandiri" 
              class="w-9 h-9 object-contain shrink-0 drop-shadow-sm transition-transform hover:scale-105" 
            />
            <div class="overflow-hidden">
              <h1 class="font-black text-sm text-slate-900 tracking-tight leading-none truncate">SIM-BIM</h1>
              <p class="text-[10px] text-emerald-800 font-bold leading-tight mt-0.5 truncate">SDI Bina Insan Mandiri</p>
            </div>
          </div>

          <!-- Animated Panel Open/Close Toggle Button (Matching target design) -->
          <button 
            @click="isSidebarOpen = !isSidebarOpen"
            class="p-1.5 rounded-xl border border-slate-200/90 bg-slate-50/80 hover:bg-emerald-50 hover:border-emerald-200 text-slate-600 hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all active:scale-95 shrink-0"
            :title="isSidebarOpen ? 'Perkecil Sidebar' : 'Buka Sidebar'"
            :aria-label="isSidebarOpen ? 'Perkecil Sidebar' : 'Buka Sidebar'"
          >
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <!-- Outer Rounded Square Frame matching target image -->
              <rect x="3" y="3" width="18" height="18" rx="4.5" />
              <!-- Sidebar Left Vertical Bar -->
              <line x1="8.5" y1="7.5" x2="8.5" y2="16.5" />
              <!-- Animated Chevron Arrow (Smoothly flips between < and >) -->
              <path 
                d="M15.5 8.5L12 12l3.5 3.5" 
                class="transition-transform duration-300 ease-in-out"
                :style="{
                  transformOrigin: '13.75px 12px',
                  transform: isSidebarOpen ? 'rotate(0deg)' : 'rotate(180deg)'
                }"
              />
            </svg>
          </button>
        </div>

        <!-- Navigation Menu Items -->
        <div class="flex-1 overflow-y-auto px-3 py-4 space-y-5 text-xs scrollbar-thin">
          <!-- GROUP: UTAMA -->
          <div>
            <div v-show="isSidebarOpen" class="px-3 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
              Menu Utama
            </div>
            <div v-show="!isSidebarOpen" class="h-px bg-slate-100 my-1 mx-2"></div>
            <nav class="space-y-1">
              <!-- Dashboard Eksekutif -->
              <router-link
                v-if="auth.isAdmin"
                to="/dashboard"
                class="flex items-center rounded-xl font-medium transition-all group"
                :class="[
                  isSidebarOpen ? 'gap-3 px-3 py-2.5' : 'justify-center py-2.5 px-0 w-11 h-11 mx-auto',
                  route.path === '/dashboard' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                ]"
                title="Dashboard Eksekutif"
              >
                <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10-5a1 1 0 011-1h4a1 1 0 011 1v9a1 1 0 01-1 1h-4a1 1 0 01-1-1v-9z" />
                </svg>
                <span v-show="isSidebarOpen" class="truncate">Dashboard Eksekutif</span>
              </router-link>

              <!-- Portal Wali Murid -->
              <router-link
                v-if="auth.isWali"
                to="/portal-wali"
                class="flex items-center rounded-xl font-medium transition-all group"
                :class="[
                  isSidebarOpen ? 'gap-3 px-3 py-2.5' : 'justify-center py-2.5 px-0 w-11 h-11 mx-auto',
                  route.path === '/portal-wali' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                ]"
                title="Portal Wali Murid"
              >
                <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span v-show="isSidebarOpen" class="truncate">Portal Wali Murid</span>
              </router-link>

              <!-- Presensi Mandiri GPS Guru -->
              <router-link
                v-if="!auth.isWali"
                to="/presensi"
                class="flex items-center rounded-xl font-medium transition-all group"
                :class="[
                  isSidebarOpen ? 'gap-3 px-3 py-2.5' : 'justify-center py-2.5 px-0 w-11 h-11 mx-auto',
                  route.path === '/presensi' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                ]"
                title="Presensi Mandiri Guru (GPS)"
              >
                <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span v-show="isSidebarOpen" class="truncate">Presensi Mandiri Guru</span>
              </router-link>

              <!-- Presensi Siswa Harian (Wali Kelas) -->
              <router-link
                v-if="auth.isWaliKelas"
                to="/my-schedule?mode=homeroom"
                class="flex items-center rounded-xl font-medium transition-all group"
                :class="[
                  isSidebarOpen ? 'gap-3 px-3 py-2.5' : 'justify-center py-2.5 px-0 w-11 h-11 mx-auto',
                  route.path === '/my-schedule' && route.query.mode === 'homeroom' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                ]"
                title="Presensi Harian Siswa (Wali Kelas)"
              >
                <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span v-show="isSidebarOpen" class="truncate">Presensi Siswa (Wali Kelas)</span>
              </router-link>

              <!-- Profil Saya -->
              <router-link
                to="/profile"
                class="flex items-center rounded-xl font-medium transition-all group"
                :class="[
                  isSidebarOpen ? 'gap-3 px-3 py-2.5' : 'justify-center py-2.5 px-0 w-11 h-11 mx-auto',
                  route.path === '/profile' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                ]"
                title="Profil Saya"
              >
                <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span v-show="isSidebarOpen" class="truncate">Profil Saya</span>
              </router-link>
            </nav>
          </div>

          <!-- GROUP: MASTER DATA & AKADEMIK -->
          <div v-if="auth.isAdmin || auth.isGuru">
            <div v-show="isSidebarOpen" class="px-3 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
              Data & Akademik
            </div>
            <div v-show="!isSidebarOpen" class="h-px bg-slate-100 my-1 mx-2"></div>
            <nav class="space-y-1">
              <!-- Data Siswa -->
              <router-link
                to="/students"
                class="flex items-center rounded-xl font-medium transition-all group"
                :class="[
                  isSidebarOpen ? 'gap-3 px-3 py-2' : 'justify-center py-2 px-0 w-11 h-10 mx-auto',
                  route.path === '/students' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                ]"
                title="Data Siswa"
              >
                <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
                </svg>
                <span v-show="isSidebarOpen" class="truncate">Data Siswa</span>
              </router-link>

              <!-- Guru & Tendik -->
              <router-link
                v-if="auth.isAdmin"
                to="/teachers"
                class="flex items-center rounded-xl font-medium transition-all group"
                :class="[
                  isSidebarOpen ? 'gap-3 px-3 py-2' : 'justify-center py-2 px-0 w-11 h-10 mx-auto',
                  route.path === '/teachers' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                ]"
                title="Guru & Tenaga Kependidikan"
              >
                <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                </svg>
                <span v-show="isSidebarOpen" class="truncate">Guru & Tendik</span>
              </router-link>

              <!-- Wali Murid -->
              <router-link
                v-if="auth.isAdmin"
                to="/guardians"
                class="flex items-center rounded-xl font-medium transition-all group"
                :class="[
                  isSidebarOpen ? 'gap-3 px-3 py-2' : 'justify-center py-2 px-0 w-11 h-10 mx-auto',
                  route.path === '/guardians' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                ]"
                title="Data & Akun Wali Murid"
              >
                <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span v-show="isSidebarOpen" class="truncate">Wali Murid</span>
              </router-link>

              <!-- PPDB Online (Penerimaan Siswa Baru) -->
              <router-link
                v-if="auth.isAdmin"
                to="/ppdb-management"
                class="flex items-center rounded-xl font-medium transition-all group"
                :class="[
                  isSidebarOpen ? 'gap-3 px-3 py-2' : 'justify-center py-2 px-0 w-11 h-10 mx-auto',
                  route.path === '/ppdb-management' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                ]"
                title="PPDB Online (Penerimaan Murid Baru)"
              >
                <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span v-show="isSidebarOpen" class="truncate flex items-center justify-between flex-1">
                  <span>PPDB Online</span>
                  <span class="px-1.5 py-0.2 rounded-md bg-amber-400 text-amber-950 text-[9px] font-black uppercase">Baru</span>
                </span>
              </router-link>

              <!-- Kelas & Jadwal KBM (Dengan Sub-menu) -->
              <div v-if="auth.isAdmin" class="space-y-0.5">
                <div class="flex items-center">
                  <router-link
                    to="/classes-schedules"
                    class="flex-1 flex items-center justify-between rounded-xl font-medium transition-all group"
                    :class="[
                      isSidebarOpen ? 'px-3 py-2' : 'justify-center py-2 px-0 w-11 h-10 mx-auto',
                      route.path === '/classes-schedules' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                    ]"
                    title="Kelas & Jadwal KBM"
                    @click="expandedMenus['classes-schedules'] = true"
                  >
                    <div class="flex items-center gap-3 min-w-0">
                      <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <span v-show="isSidebarOpen" class="truncate">Kelas & Jadwal KBM</span>
                    </div>
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
                  <router-link
                    to="/classes-schedules?tab=academic_years"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/classes-schedules' && route.query.tab === 'academic_years' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/classes-schedules' && route.query.tab === 'academic_years' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Tahun Ajaran & Kenaikan Kelas</span>
                  </router-link>
                  <router-link
                    to="/classes-schedules?tab=calendar"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/classes-schedules' && route.query.tab === 'calendar' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/classes-schedules' && route.query.tab === 'calendar' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Kalender Akademik & Libur</span>
                  </router-link>
                </div>
              </div>

              <!-- Import Excel Master -->
              <router-link
                v-if="auth.isAdmin"
                to="/excel-imports"
                class="flex items-center rounded-xl font-medium transition-all group"
                :class="[
                  isSidebarOpen ? 'gap-3 px-3 py-2' : 'justify-center py-2 px-0 w-11 h-10 mx-auto',
                  route.path === '/excel-imports' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                ]"
                title="Import Excel Dapodik"
              >
                <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span v-show="isSidebarOpen" class="truncate">Import Excel Master</span>
              </router-link>
            </nav>
          </div>

          <!-- GROUP: MONITORING KBM & PROGRESS -->
          <div v-if="auth.isAdmin || auth.isGuru">
            <div v-show="isSidebarOpen" class="px-3 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
              KBM & Progres Siswa
            </div>
            <div v-show="!isSidebarOpen" class="h-px bg-slate-100 my-1 mx-2"></div>
            <nav class="space-y-1">
              <!-- Jadwal Mengajar Guru -->
              <router-link
                to="/my-schedule"
                class="flex items-center rounded-xl font-medium transition-all group"
                :class="[
                  isSidebarOpen ? 'gap-3 px-3 py-2' : 'justify-center py-2 px-0 w-11 h-10 mx-auto',
                  route.path === '/my-schedule' && route.query.mode !== 'homeroom' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                ]"
                title="Jadwal Mengajar & Masuk Kelas"
              >
                <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span v-show="isSidebarOpen" class="truncate">Jadwal Mengajar Guru</span>
              </router-link>

              <!-- Presensi Siswa Harian (Wali Kelas) -->
              <router-link
                v-if="auth.isWaliKelas"
                to="/my-schedule?mode=homeroom"
                class="flex items-center rounded-xl font-medium transition-all group"
                :class="[
                  isSidebarOpen ? 'gap-3 px-3 py-2' : 'justify-center py-2 px-0 w-11 h-10 mx-auto',
                  route.path === '/my-schedule' && route.query.mode === 'homeroom' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                ]"
                title="Presensi Harian Siswa (Wali Kelas 1x Sehari)"
              >
                <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span v-show="isSidebarOpen" class="truncate">Presensi Siswa (Wali Kelas)</span>
              </router-link>

              <!-- Rekap Presensi & KBM (Dengan Sub-menu untuk Admin) -->
              <div v-if="auth.isAdmin" class="space-y-0.5">
                <div class="flex items-center">
                  <router-link
                    to="/attendance-management"
                    class="flex-1 flex items-center justify-between rounded-xl font-medium transition-all group"
                    :class="[
                      isSidebarOpen ? 'px-3 py-2' : 'justify-center py-2 px-0 w-11 h-10 mx-auto',
                      route.path === '/attendance-management' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                    ]"
                    title="Rekap Presensi & KBM"
                    @click="expandedMenus['attendance-management'] = true"
                  >
                    <div class="flex items-center gap-3 min-w-0">
                      <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                      </svg>
                      <span v-show="isSidebarOpen" class="truncate">Rekap Presensi & KBM</span>
                    </div>
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
                    <span class="truncate">Rekap Kelas</span>
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

              <!-- Riwayat Presensi Guru (Khusus Guru) -->
              <router-link
                v-else-if="auth.isGuru"
                to="/my-attendance-history"
                class="flex items-center rounded-xl font-medium transition-all group"
                :class="[
                  isSidebarOpen ? 'gap-3 px-3 py-2' : 'justify-center py-2 px-0 w-11 h-10 mx-auto',
                  route.path === '/my-attendance-history' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                ]"
                title="Riwayat Presensi Guru"
              >
                <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span v-show="isSidebarOpen" class="truncate">Riwayat Presensi Guru</span>
              </router-link>

              <!-- Tahfidz & Kegiatan (Dengan Sub-menu) -->
              <div class="space-y-0.5">
                <div class="flex items-center">
                  <router-link
                    to="/progress-activities"
                    class="flex-1 flex items-center justify-between rounded-xl font-medium transition-all group"
                    :class="[
                      isSidebarOpen ? 'px-3 py-2' : 'justify-center py-2 px-0 w-11 h-10 mx-auto',
                      route.path === '/progress-activities' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                    ]"
                    title="Tahfidz & Kegiatan"
                    @click="expandedMenus['progress-activities'] = true"
                  >
                    <div class="flex items-center gap-3 min-w-0">
                      <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                      </svg>
                      <span v-show="isSidebarOpen" class="truncate">Tahfidz & Kegiatan</span>
                    </div>
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

              <!-- Buku Nilai (Di atas Rapor Siswa) -->
              <router-link
                v-if="auth.isAdmin || auth.isWaliKelas"
                to="/buku-nilai"
                class="flex items-center gap-3 rounded-xl font-medium transition-all group mb-0.5"
                :class="[
                  isSidebarOpen ? 'px-3 py-2' : 'justify-center py-2 px-0 w-11 h-10 mx-auto',
                  route.path === '/buku-nilai' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                ]"
                title="Buku Nilai"
              >
                <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <span v-show="isSidebarOpen" class="truncate">Buku Nilai</span>
              </router-link>

              <!-- Rapor Siswa (Dengan Sub-menu) -->
              <div v-if="auth.isAdmin || auth.isWaliKelas" class="space-y-0.5">
                <div class="flex items-center">
                  <router-link
                    to="/rapor?periode=triwulan_1"
                    class="flex-1 flex items-center justify-between rounded-xl font-medium transition-all group"
                    :class="[
                      isSidebarOpen ? 'px-3 py-2' : 'justify-center py-2 px-0 w-11 h-10 mx-auto',
                      route.path === '/rapor' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                    ]"
                    title="Rapor Siswa"
                    @click="expandedMenus['rapor'] = true"
                  >
                    <div class="flex items-center gap-3 min-w-0">
                      <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                      <span v-show="isSidebarOpen" class="truncate">Rapor Siswa</span>
                    </div>
                    <button
                      v-show="isSidebarOpen"
                      type="button"
                      @click.prevent.stop="toggleMenu('rapor')"
                      class="p-1 rounded-lg transition-transform focus:outline-none"
                      :class="route.path === '/rapor' ? 'text-white hover:bg-white/20' : 'text-slate-400 hover:text-slate-700 hover:bg-slate-200/60'"
                      :title="isMenuExpanded('rapor') ? 'Tutup Sub-menu' : 'Buka Sub-menu'"
                    >
                      <svg 
                        class="w-3.5 h-3.5 transition-transform duration-200" 
                        :class="isMenuExpanded('rapor') ? 'rotate-90' : ''"
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                  </router-link>
                </div>

                <!-- Sub-menu Rapor Siswa (4 Periode per Tahun) -->
                <div 
                  v-show="isSidebarOpen && isMenuExpanded('rapor')" 
                  class="pl-3.5 pr-1 py-1 ml-4 border-l-2 border-emerald-500/30 space-y-0.5"
                >
                  <!-- 1. Triwulan 1 (Pertengahan Semester 1) -->
                  <router-link
                    to="/rapor?periode=triwulan_1"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/rapor' && (route.query.periode === 'triwulan_1' || !route.query.periode) ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/rapor' && (route.query.periode === 'triwulan_1' || !route.query.periode) ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Triwulan 1 (Tengah Sem 1)</span>
                  </router-link>

                  <!-- 2. Semester 1 -->
                  <router-link
                    to="/rapor?periode=semester_1"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/rapor' && route.query.periode === 'semester_1' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/rapor' && route.query.periode === 'semester_1' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Semester 1</span>
                  </router-link>

                  <!-- 3. Triwulan 3 (Pertengahan Semester 2) -->
                  <router-link
                    to="/rapor?periode=triwulan_3"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/rapor' && route.query.periode === 'triwulan_3' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/rapor' && route.query.periode === 'triwulan_3' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Triwulan 3 (Tengah Sem 2)</span>
                  </router-link>

                  <!-- 4. Semester 2 -->
                  <router-link
                    to="/rapor?periode=semester_2"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] transition-all"
                    :class="route.path === '/rapor' && route.query.periode === 'semester_2' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/rapor' && route.query.periode === 'semester_2' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                    <span class="truncate">Semester 2</span>
                  </router-link>
                </div>
              </div>
            </nav>
          </div>

          <!-- GROUP: KEUANGAN & SARPRAS -->
          <div v-if="auth.isAdmin">
            <div v-show="isSidebarOpen" class="px-3 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
              Keuangan & Aset
            </div>
            <div v-show="!isSidebarOpen" class="h-px bg-slate-100 my-1 mx-2"></div>
            <nav class="space-y-1">
              <!-- Keuangan & SPP (Dengan Sub-menu) -->
              <div class="space-y-0.5">
                <div class="flex items-center">
                  <router-link
                    to="/finance"
                    class="flex-1 flex items-center justify-between rounded-xl font-medium transition-all group"
                    :class="[
                      isSidebarOpen ? 'px-3 py-2' : 'justify-center py-2 px-0 w-11 h-10 mx-auto',
                      route.path === '/finance' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                    ]"
                    title="Keuangan & SPP"
                    @click="expandedMenus['finance'] = true"
                  >
                    <div class="flex items-center gap-3 min-w-0">
                      <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>
                      <span v-show="isSidebarOpen" class="truncate">Keuangan & SPP</span>
                    </div>
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

              <!-- Sarpras & Aset -->
              <router-link
                to="/assets"
                class="flex items-center rounded-xl font-medium transition-all group"
                :class="[
                  isSidebarOpen ? 'gap-3 px-3 py-2' : 'justify-center py-2 px-0 w-11 h-10 mx-auto',
                  route.path === '/assets' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                ]"
                title="Sarana & Prasarana"
              >
                <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span v-show="isSidebarOpen" class="truncate">Sarpras & Aset</span>
              </router-link>
            </nav>
          </div>

          <!-- GROUP: PENGATURAN & KEAMANAN -->
          <div v-if="auth.isAdmin">
            <div v-show="isSidebarOpen" class="px-3 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
              Web & Sistem
            </div>
            <div v-show="!isSidebarOpen" class="h-px bg-slate-100 my-1 mx-2"></div>
            <nav class="space-y-1">
              <!-- CMS Web Landing Page -->
              <router-link
                to="/cms"
                class="flex items-center rounded-xl font-medium transition-all group"
                :class="[
                  isSidebarOpen ? 'gap-3 px-3 py-2' : 'justify-center py-2 px-0 w-11 h-10 mx-auto',
                  route.path === '/cms' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                ]"
                title="CMS Web Sekolah (Galeri, Prestasi, Artikel)"
              >
                <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                </svg>
                <span v-show="isSidebarOpen" class="truncate">CMS Web Sekolah</span>
              </router-link>

              <!-- Pengguna & Geofence (Dengan Sub-menu) -->
              <div class="space-y-0.5">
                <div class="flex items-center">
                  <router-link
                    to="/users-settings"
                    class="flex-1 flex items-center justify-between rounded-xl font-medium transition-all group"
                    :class="[
                      isSidebarOpen ? 'px-3 py-2' : 'justify-center py-2 px-0 w-11 h-10 mx-auto',
                      route.path === '/users-settings' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                    ]"
                    title="Pengguna & Sistem"
                    @click="expandedMenus['users-settings'] = true"
                  >
                    <div class="flex items-center gap-3 min-w-0">
                      <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      <span v-show="isSidebarOpen" class="truncate">Pengguna & Geofence</span>
                    </div>
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

              <!-- Backup Database -->
              <router-link
                to="/backup"
                class="flex items-center rounded-xl font-medium transition-all group"
                :class="[
                  isSidebarOpen ? 'gap-3 px-3 py-2' : 'justify-center py-2 px-0 w-11 h-10 mx-auto',
                  route.path === '/backup' ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-emerald-50/70 hover:text-emerald-800'
                ]"
                title="Backup & Restore"
              >
                <svg class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                </svg>
                <span v-show="isSidebarOpen" class="truncate">Backup Database</span>
              </router-link>
            </nav>
          </div>
        </div>

        <!-- Sidebar Compact Brand / Status Footer (Replaces old bottom collapse toggle) -->
        <div class="p-3 border-t border-slate-100 shrink-0 text-center">
          <div v-if="isSidebarOpen" class="text-[10px] text-slate-400 font-medium tracking-tight truncate">
            SIM-BIM <span class="font-bold text-emerald-600">v1.0</span> • SDI BIM
          </div>
          <div v-else class="flex justify-center" title="SIM-BIM Purwokerto">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          </div>
        </div>
      </aside>

      <!-- 2. MAIN VIEW AREA (Adaptive for Mobile, iPad, and Monitor) -->
      <div class="flex-1 flex flex-col min-w-0">
        <!-- TOPBAR: Optimized for all screens -->
        <header class="bg-white/95 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-20 shadow-xs h-16 flex items-center justify-between px-3 sm:px-6 no-print print:hidden">
          <!-- Left: Mobile Hamburger Button (< lg) + Brand + Academic Year -->
          <div class="flex items-center gap-2 sm:gap-4">
            <!-- Mobile Drawer Toggle Button (Visible only on Mobile & Tablet < 1024px) -->
            <button 
              @click="toggleSidebar"
              class="lg:hidden p-2 rounded-xl text-slate-600 hover:bg-emerald-50 hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all active:scale-95"
              title="Buka Menu Navigasi"
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
              <nav class="flex-1 overflow-y-auto p-3 space-y-5 text-xs">
                <!-- Group 1: Utama -->
                <div>
                  <div class="px-2 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Menu Utama</div>
                  <div class="space-y-1">
                    <router-link 
                      v-if="auth.isAdmin" 
                      to="/dashboard" 
                      class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all"
                      :class="route.path === '/dashboard' ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10-5a1 1 0 011-1h4a1 1 0 011 1v9a1 1 0 01-1 1h-4a1 1 0 01-1-1v-9z" />
                      </svg>
                      <span>Dashboard Eksekutif</span>
                    </router-link>

                    <router-link 
                      v-if="auth.isWali || auth.isAdmin" 
                      to="/portal-wali" 
                      class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all"
                      :class="route.path === '/portal-wali' ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>
                      <span>Portal Wali Murid</span>
                    </router-link>

                    <router-link 
                      v-if="!auth.isWali"
                      to="/presensi" 
                      class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all"
                      :class="route.path === '/presensi' ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      <span>Presensi Mandiri Guru</span>
                    </router-link>

                    <router-link 
                      v-if="auth.isWaliKelas"
                      to="/my-schedule?mode=homeroom" 
                      class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all"
                      :class="route.path === '/my-schedule' && route.query.mode === 'homeroom' ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                      </svg>
                      <span>Presensi Siswa (Wali Kelas)</span>
                    </router-link>

                    <router-link 
                      to="/profile" 
                      class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all"
                      :class="route.path === '/profile' ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                      <span>Profil Saya</span>
                    </router-link>
                  </div>
                </div>

                <!-- Group 2: Data & Akademik -->
                <div v-if="auth.isAdmin || auth.isGuru">
                  <div class="px-2 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Data & Akademik</div>
                  <div class="space-y-1">
                    <router-link 
                      to="/students" 
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all"
                      :class="route.path === '/students' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
                      </svg>
                      <span>Data Siswa</span>
                    </router-link>

                    <router-link 
                      v-if="auth.isAdmin"
                      to="/teachers" 
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all"
                      :class="route.path === '/teachers' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                      </svg>
                      <span>Guru & Tendik</span>
                    </router-link>

                    <router-link 
                      v-if="auth.isAdmin"
                      to="/guardians" 
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all"
                      :class="route.path === '/guardians' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                      </svg>
                      <span>Wali Murid</span>
                    </router-link>

                    <!-- PPDB Online (Mobile) -->
                    <router-link 
                      v-if="auth.isAdmin"
                      to="/ppdb-management" 
                      class="flex items-center justify-between px-3 py-2 rounded-xl font-medium transition-all"
                      :class="route.path === '/ppdb-management' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>PPDB Online</span>
                      </div>
                      <span class="px-1.5 py-0.2 rounded-md bg-amber-400 text-amber-950 text-[9px] font-black uppercase">Baru</span>
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
                          <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Kelas & Jadwal KBM</span>
                          </div>
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
                        <router-link
                          to="/classes-schedules?tab=academic_years"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/classes-schedules' && route.query.tab === 'academic_years' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/classes-schedules' && route.query.tab === 'academic_years' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Tahun Ajaran & Kenaikan Kelas</span>
                        </router-link>
                        <router-link
                          to="/classes-schedules?tab=calendar"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/classes-schedules' && route.query.tab === 'calendar' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/classes-schedules' && route.query.tab === 'calendar' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Kalender Akademik & Libur</span>
                        </router-link>
                      </div>
                    </div>

                    <router-link 
                      v-if="auth.isAdmin"
                      to="/excel-imports" 
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all"
                      :class="route.path === '/excel-imports' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                      <span>Import Excel Master</span>
                    </router-link>
                  </div>
                </div>

                <!-- Group 3: KBM & Progres -->
                <div v-if="auth.isAdmin || auth.isGuru">
                  <div class="px-2 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">KBM & Progres</div>
                  <div class="space-y-1">
                    <router-link 
                      to="/my-schedule" 
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all"
                      :class="route.path === '/my-schedule' && route.query.mode !== 'homeroom' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <span>Jadwal Mengajar Guru</span>
                    </router-link>

                    <router-link 
                      v-if="auth.isWaliKelas"
                      to="/my-schedule?mode=homeroom" 
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all"
                      :class="route.path === '/my-schedule' && route.query.mode === 'homeroom' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                      </svg>
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
                          <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <span>Rekap Presensi & KBM</span>
                          </div>
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
                          <span>Rekap Kelas</span>
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
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all"
                      :class="route.path === '/my-attendance-history' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
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
                          <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span>Tahfidz & Kegiatan</span>
                          </div>
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

                    <!-- Buku Nilai (Mobile) -->
                    <router-link
                      v-if="auth.isAdmin || auth.isWaliKelas"
                      to="/buku-nilai"
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all mb-0.5"
                      :class="route.path === '/buku-nilai' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                      </svg>
                      <span>Buku Nilai</span>
                    </router-link>

                    <!-- Rapor Siswa (Mobile Sub-menu) -->
                    <div v-if="auth.isAdmin || auth.isWaliKelas" class="space-y-0.5">
                      <div class="flex items-center justify-between px-3 py-2 rounded-xl font-medium transition-all"
                        :class="route.path === '/rapor' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                      >
                        <router-link 
                          to="/rapor?periode=triwulan_1"
                          class="flex items-center gap-3 flex-1"
                          @click="isMobileDrawerOpen = false; expandedMenus['rapor'] = true"
                        >
                          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                          </svg>
                          <span>Rapor Siswa</span>
                        </router-link>
                        <button 
                          type="button"
                          @click.stop="toggleMenu('rapor')"
                          class="p-1 rounded-lg"
                          :class="route.path === '/rapor' ? 'text-white hover:bg-white/20' : 'text-slate-400 hover:text-slate-700'"
                        >
                          <svg 
                            class="w-4 h-4 transition-transform duration-200" 
                            :class="isMenuExpanded('rapor') ? 'rotate-90' : ''"
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                          >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                          </svg>
                        </button>
                      </div>

                      <!-- Sub-menu Rapor Mobile -->
                      <div v-show="isMenuExpanded('rapor')" class="pl-4 pr-1 py-1 ml-4 border-l-2 border-emerald-500/30 space-y-0.5">
                        <router-link 
                          to="/rapor?periode=triwulan_1"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/rapor' && (route.query.periode === 'triwulan_1' || !route.query.periode) ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/rapor' && (route.query.periode === 'triwulan_1' || !route.query.periode) ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Triwulan 1 (Tengah Sem 1)</span>
                        </router-link>

                        <router-link 
                          to="/rapor?periode=semester_1"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/rapor' && route.query.periode === 'semester_1' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/rapor' && route.query.periode === 'semester_1' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Semester 1</span>
                        </router-link>

                        <router-link 
                          to="/rapor?periode=triwulan_3"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/rapor' && route.query.periode === 'triwulan_3' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/rapor' && route.query.periode === 'triwulan_3' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Triwulan 3 (Tengah Sem 2)</span>
                        </router-link>

                        <router-link 
                          to="/rapor?periode=semester_2"
                          class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px]"
                          :class="route.path === '/rapor' && route.query.periode === 'semester_2' ? 'bg-emerald-100/70 text-emerald-900 font-bold' : 'text-slate-600 hover:bg-slate-100'"
                          @click="isMobileDrawerOpen = false"
                        >
                          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="route.path === '/rapor' && route.query.periode === 'semester_2' ? 'bg-emerald-600' : 'bg-slate-300'"></span>
                          <span>Semester 2</span>
                        </router-link>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Group 4: Keuangan & Aset -->
                <div v-if="auth.isAdmin">
                  <div class="px-2 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Keuangan & Aset</div>
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
                          <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span>Keuangan & SPP</span>
                          </div>
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
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all"
                      :class="route.path === '/assets' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                      </svg>
                      <span>Sarpras & Aset</span>
                    </router-link>
                  </div>
                </div>

                <!-- Group 5: Web & Sistem -->
                <div v-if="auth.isAdmin">
                  <div class="px-2 pb-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Web & Sistem</div>
                  <div class="space-y-1">
                    <!-- CMS Web Sekolah -->
                    <router-link 
                      to="/cms" 
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all"
                      :class="route.path === '/cms' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                      </svg>
                      <span>CMS Web Sekolah</span>
                    </router-link>

                    <!-- Pengguna & Geofence (Mobile Sub-menu) -->
                    <div class="space-y-0.5">
                      <div class="flex items-center">
                        <router-link 
                          to="/users-settings" 
                          class="flex-1 flex items-center justify-between px-3 py-2 rounded-xl font-medium"
                          :class="route.path === '/users-settings' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                          @click="expandedMenus['users-settings'] = true; isMobileDrawerOpen = false"
                        >
                          <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Pengguna & Geofence</span>
                          </div>
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
                      class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium transition-all"
                      :class="route.path === '/backup' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-100'"
                      @click="isMobileDrawerOpen = false"
                    >
                      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                      </svg>
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
        <main class="flex-1 p-3.5 sm:p-6 lg:p-8 max-w-7xl 2xl:max-w-[1600px] w-full mx-auto pb-24 lg:pb-8 print:p-0 print:m-0 print:max-w-none print:w-full">
          <router-view />
        </main>

        <!-- 5. MOBILE BOTTOM NAVIGATION (Visible on Mobile & iPad Portrait < 1024px) -->
        <nav 
          class="lg:hidden fixed bottom-0 left-0 right-0 z-30 bg-white/95 backdrop-blur-md border-t border-slate-200/90 shadow-lg px-2 py-1 flex justify-around items-center text-[10px] font-semibold text-slate-500 pb-[max(0.375rem,env(safe-area-inset-bottom))] no-print print:hidden"
        >
          <!-- Dashboard button -->
          <router-link
            v-if="auth.isAdmin"
            to="/dashboard"
            class="flex flex-col items-center py-1.5 px-2 rounded-xl transition min-w-[52px]"
            :class="route.path === '/dashboard' ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-slate-800 font-medium'"
          >
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10-5a1 1 0 011-1h4a1 1 0 011 1v9a1 1 0 01-1 1h-4a1 1 0 01-1-1v-9z" />
            </svg>
            <span class="text-[10px] leading-tight">Dashboard</span>
          </router-link>

          <!-- Presensi button -->
          <router-link
            to="/presensi"
            class="flex flex-col items-center py-1.5 px-2 rounded-xl transition min-w-[52px]"
            :class="route.path === '/presensi' ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-slate-800 font-medium'"
          >
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            <span class="text-[10px] leading-tight">Presensi</span>
          </router-link>

          <!-- Siswa / Portal button -->
          <router-link
            :to="auth.isWali ? '/portal-wali' : '/students'"
            class="flex flex-col items-center py-1.5 px-2 rounded-xl transition min-w-[52px]"
            :class="(route.path === '/students' || route.path === '/portal-wali') ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-slate-800 font-medium'"
          >
            <svg v-if="auth.isWali" class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <svg v-else class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
            </svg>
            <span class="text-[10px] leading-tight">{{ auth.isWali ? 'Anak Saya' : 'Siswa' }}</span>
          </router-link>

          <!-- Keuangan button (for admin) or KBM -->
          <router-link
            v-if="auth.isAdmin"
            to="/finance"
            class="flex flex-col items-center py-1.5 px-2 rounded-xl transition min-w-[52px]"
            :class="route.path === '/finance' ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-slate-800 font-medium'"
          >
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span class="text-[10px] leading-tight">SPP/Kas</span>
          </router-link>
          <router-link
            v-else-if="auth.isGuru"
            to="/my-attendance-history"
            class="flex flex-col items-center py-1.5 px-2 rounded-xl transition min-w-[52px]"
            :class="route.path === '/my-attendance-history' ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-slate-800 font-medium'"
          >
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-[10px] leading-tight">Riwayat</span>
          </router-link>

          <!-- Profil button -->
          <router-link
            to="/profile"
            class="flex flex-col items-center py-1.5 px-2 rounded-xl transition min-w-[52px]"
            :class="route.path === '/profile' ? 'text-emerald-700 font-bold' : 'text-slate-500 hover:text-slate-800 font-medium'"
          >
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span class="text-[10px] leading-tight">Profil</span>
          </router-link>

          <!-- Menu Drawer Toggle -->
          <button
            @click="isMobileDrawerOpen = true"
            type="button"
            class="flex flex-col items-center py-1.5 px-2 rounded-xl transition min-w-[52px] text-slate-500 hover:text-slate-900 font-medium"
          >
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <span class="text-[10px] leading-tight">Menu</span>
          </button>
        </nav>
      </div>
    </div>
  </div>
</template>
