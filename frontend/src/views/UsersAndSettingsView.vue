<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
import { showConfirm, showSuccess, showError, confirmDeleteWithKeyword } from '../services/swal'
import { formatDateTimeIndo } from '../utils/date'

const route = useRoute()
const auth = useAuthStore()
const activeTab = ref<'users' | 'logs' | 'settings'>('users')
const loading = ref(false)
const message = ref<{ type: 'success' | 'error'; text: string } | null>(null)

// Users State
const users = ref<any[]>([])
const searchUser = ref('')
const filterRole = ref('')
const userViewMode = ref<'list' | 'form'>('list')
const userForm = ref({
  nama: '',
  username: '',
  email: '',
  no_hp: '',
  role: 'guru',
  password: 'Password123!'
})

// Activity Logs State
const activityLogs = ref<any[]>([])
const logSearch = ref('')
const logFilterModul = ref('')
const logFilterAksi = ref('')
const logCurrentPage = ref(1)
const logPerPage = ref(20)
const logMeta = ref<{
  current_page: number
  last_page: number
  per_page: number
  total: number
}>({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
})
const selectedLogForDetail = ref<any | null>(null)

// Settings State
const schoolProfile = ref<any>({})
const settings = ref<any>({
  geofence_latitude: -7.453839,
  geofence_longitude: 109.234446,
  geofence_radius_meters: 100,
  jam_masuk_guru: '07:00',
  jam_pulang_guru: '14:30',
  toleransi_terlambat_menit: 15
})
const savingSettings = ref(false)

const showMessage = (text: string, type: 'success' | 'error' = 'success') => {
  message.value = { type, text }
  setTimeout(() => (message.value = null), 4000)
}

const fetchUsers = async () => {
  loading.value = true
  try {
    const params: any = {}
    if (searchUser.value) params.search = searchUser.value
    if (filterRole.value) params.role = filterRole.value

    const res = await api.get('/users', { params })
    users.value = res.data.data || []
  } catch (err) {
    console.error('Failed to load users', err)
  } finally {
    loading.value = false
  }
}

// Form mode
const isEditingUser = ref(false)
const editingUserId = ref<number | null>(null)

const openCreateUserForm = () => {
  isEditingUser.value = false
  editingUserId.value = null
  userForm.value = {
    nama: '',
    username: '',
    email: '',
    no_hp: '',
    role: 'guru',
    password: 'Password123!'
  }
  userViewMode.value = 'form'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const openEditUserForm = (user: any) => {
  isEditingUser.value = true
  editingUserId.value = user.id
  userForm.value = {
    nama: user.nama || '',
    username: user.username || '',
    email: user.email || '',
    no_hp: user.no_hp || '',
    role: user.role?.nama || 'guru',
    password: ''
  }
  userViewMode.value = 'form'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const closeUserForm = () => {
  userViewMode.value = 'list'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const saveUser = async () => {
  try {
    const payload: any = { ...userForm.value }
    if (!payload.email) delete payload.email
    if (!payload.no_hp) delete payload.no_hp
    if (isEditingUser.value && !payload.password) delete payload.password

    if (isEditingUser.value && editingUserId.value) {
      await api.put(`/users/${editingUserId.value}`, payload)
      showSuccess('Berhasil!', 'Data akun pengguna berhasil diperbarui.')
    } else {
      await api.post('/users', payload)
      showSuccess('Berhasil!', 'Pengguna baru berhasil ditambahkan!')
    }
    closeUserForm()
    fetchUsers()
  } catch (err: any) {
    showError('Gagal!', err.response?.data?.message || 'Gagal menyimpan data pengguna')
  }
}

const deleteUser = async (user: any) => {
  const confirmed = await confirmDeleteWithKeyword(`akun pengguna "${user.nama}" (@${user.username})`)
  if (!confirmed) return

  try {
    const res = await api.delete(`/users/${user.id}`)
    if (res.data.success) {
      showSuccess('Berhasil Dinonaktifkan!', res.data.message)
      fetchUsers()
    }
  } catch (err: any) {
    showError('Gagal!', err.response?.data?.message || 'Gagal menonaktifkan pengguna.')
  }
}

const resetPassword = async (user: any) => {
  const confirmed = await showConfirm(
    'Reset Kata Sandi?',
    `Kata sandi untuk @${user.username} akan direset menjadi default (Password123!). Lanjutkan?`,
    'Ya, Reset Sandi'
  )
  if (!confirmed) return

  try {
    await api.post(`/users/${user.id}/reset-password`)
    showSuccess('Berhasil!', 'Kata sandi berhasil direset ke Password123!')
  } catch (err: any) {
    showError('Gagal!', err.response?.data?.message || 'Gagal mereset kata sandi.')
  }
}

const fetchLogs = async (page = 1) => {
  loading.value = true
  logCurrentPage.value = page
  try {
    const params: any = {
      page,
      per_page: logPerPage.value,
    }
    if (logSearch.value.trim()) params.search = logSearch.value.trim()
    if (logFilterModul.value) params.modul = logFilterModul.value
    if (logFilterAksi.value) params.aksi = logFilterAksi.value

    const res = await api.get('/activity-logs', { params })
    activityLogs.value = res.data.data?.data || res.data.data || []
    if (res.data.meta) {
      logMeta.value = res.data.meta
    }
  } catch (err) {
    console.error('Failed to load logs', err)
  } finally {
    loading.value = false
  }
}

const resetLogFilters = () => {
  logSearch.value = ''
  logFilterModul.value = ''
  logFilterAksi.value = ''
  fetchLogs(1)
}

const getActionBadgeClass = (aksi: string | undefined) => {
  const a = (aksi || '').toLowerCase()
  if (a.includes('login') || a.includes('auth')) return 'bg-blue-100 text-blue-800 border-blue-200'
  if (a.includes('logout')) return 'bg-slate-100 text-slate-700 border-slate-200'
  if (a.includes('create') || a.includes('store') || a.includes('tambah') || a.includes('publish')) return 'bg-emerald-100 text-emerald-800 border-emerald-200'
  if (a.includes('update') || a.includes('edit') || a.includes('ubah') || a.includes('sync')) return 'bg-amber-100 text-amber-800 border-amber-200'
  if (a.includes('delete') || a.includes('hapus') || a.includes('destroy') || a.includes('reject')) return 'bg-rose-100 text-rose-800 border-rose-200'
  if (a.includes('import') || a.includes('export') || a.includes('download')) return 'bg-purple-100 text-purple-800 border-purple-200'
  return 'bg-gray-100 text-gray-700 border-gray-200'
}

const getModuleBadgeClass = (modul: string | undefined) => {
  const m = (modul || '').toLowerCase()
  if (m === 'auth') return 'bg-indigo-50 text-indigo-700 border-indigo-200'
  if (m === 'students') return 'bg-emerald-50 text-emerald-700 border-emerald-200'
  if (m === 'teachers') return 'bg-teal-50 text-teal-700 border-teal-200'
  if (m === 'attendance') return 'bg-cyan-50 text-cyan-700 border-cyan-200'
  if (m === 'finance') return 'bg-amber-50 text-amber-700 border-amber-200'
  if (m === 'announcements') return 'bg-orange-50 text-orange-700 border-orange-200'
  if (m === 'settings' || m === 'users') return 'bg-violet-50 text-violet-700 border-violet-200'
  return 'bg-slate-50 text-slate-700 border-slate-200'
}

const fetchSettingsAndProfile = async () => {
  loading.value = true
  try {
    const resProf = await api.get('/school-profile')
    if (resProf.data?.data) {
      const prof = resProf.data.data
      schoolProfile.value = prof
      settings.value = {
        ...settings.value,
        geofence_latitude: prof.latitude ?? -7.4538894,
        geofence_longitude: prof.longitude ?? 109.2346745,
        geofence_radius_meters: prof.radius_geofence_m ?? 100,
        jam_masuk_guru: prof.jam_masuk_default ? prof.jam_masuk_default.substring(0, 5) : '07:00',
        jam_pulang_guru: prof.jam_pulang_default ? prof.jam_pulang_default.substring(0, 5) : '14:30',
        toleransi_terlambat_menit: prof.toleransi_terlambat_menit ?? 15,
      }
    }
  } catch (err) {
    console.error('Failed to load settings', err)
  } finally {
    loading.value = false
  }
}

const saveSettings = async () => {
  savingSettings.value = true
  try {
    // 1. Simpan ke SchoolProfile agar langsung tersambung ke jam masuk/pulang, geofence, dan kalkulasi presensi guru
    const profilePayload = {
      latitude: settings.value.geofence_latitude,
      longitude: settings.value.geofence_longitude,
      radius_geofence_m: settings.value.geofence_radius_meters,
      jam_masuk_default: settings.value.jam_masuk_guru,
      jam_pulang_default: settings.value.jam_pulang_guru,
      toleransi_terlambat_menit: settings.value.toleransi_terlambat_menit,
    }
    await api.put('/school-profile', profilePayload)

    // 2. Simpan juga ke settings table
    const settingsPayload = [
      { key: 'attendance.geofence.latitude', value: String(settings.value.geofence_latitude) },
      { key: 'attendance.geofence.longitude', value: String(settings.value.geofence_longitude) },
      { key: 'attendance.geofence.radius_meters', value: String(settings.value.geofence_radius_meters) },
      { key: 'attendance.work_hours.start', value: settings.value.jam_masuk_guru },
      { key: 'attendance.work_hours.end', value: settings.value.jam_pulang_guru },
    ]
    try {
      await api.put('/settings', { settings: settingsPayload })
    } catch (e) {
      console.warn('Sync to settings table:', e)
    }

    showMessage('Pengaturan jam kerja guru dan geofence GPS berhasil diperbarui dan disinkronkan!')
  } catch (err: any) {
    showMessage(err.response?.data?.message || 'Gagal menyimpan pengaturan', 'error')
  } finally {
    savingSettings.value = false
  }
}

watch(() => route.query.tab, (newTab) => {
  if (newTab && ['users', 'logs', 'settings'].includes(newTab as string)) {
    if (activeTab.value !== newTab) {
      activeTab.value = newTab as any
      userViewMode.value = 'list'
      if (newTab === 'users') fetchUsers()
      else if (newTab === 'logs') fetchLogs()
      else if (newTab === 'settings') fetchSettingsAndProfile()
    }
  }
})

onMounted(() => {
  if (route.query.tab && ['users', 'logs', 'settings'].includes(route.query.tab as string)) {
    activeTab.value = route.query.tab as any
  }
  if (activeTab.value === 'users') fetchUsers()
  else if (activeTab.value === 'logs') fetchLogs()
  else if (activeTab.value === 'settings') fetchSettingsAndProfile()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Manajemen Pengguna & Pengaturan Sistem</h1>
        <p class="text-sm text-gray-500 mt-1">Kelola hak akses role pengguna, audit trail aktivitas, radius geofence presensi, dan konfigurasi sekolah.</p>
      </div>
      <button 
        v-if="activeTab === 'users' && userViewMode === 'list'"
        @click="openCreateUserForm"
        class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-md transition flex items-center gap-2 cursor-pointer active:scale-95"
      >
        <span>+ Tambah Akun Pengguna</span>
      </button>
    </div>

    <!-- Alert Message -->
    <div 
      v-if="message" 
      :class="message.type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200'"
      class="p-4 rounded-xl border text-sm flex items-center justify-between shadow-sm transition-all"
    >
      <span>{{ message.text }}</span>
      <button @click="message = null" class="font-bold opacity-60 hover:opacity-100">&times;</button>
    </div>

    <!-- TAB 1: USERS -->
    <div v-if="activeTab === 'users'">
      <!-- SUB-VIEW 1.1: TABEL PENGGUNA (LIST VIEW) -->
      <div v-if="userViewMode === 'list'" class="space-y-4">
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-wrap gap-4 items-center justify-between">
          <div class="flex flex-wrap items-center gap-3">
            <input 
              v-model="searchUser" 
              @input="fetchUsers"
              type="text" 
              placeholder="Cari nama, username, email..." 
              class="border border-gray-200 rounded-xl px-4 py-2 text-sm w-64 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            />
            <select 
              v-model="filterRole" 
              @change="fetchUsers"
              class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            >
              <option value="">Semua Role</option>
              <option value="kepala_sekolah">Kepala Sekolah</option>
              <option value="staf_admin">Staf Admin</option>
              <option value="guru">Guru</option>
              <option value="wali_murid">Wali Murid</option>
            </select>
          </div>
          <button 
            @click="fetchUsers"
            class="text-xs font-semibold bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-700 px-3 py-2 rounded-xl cursor-pointer"
          >
            Refresh
          </button>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[650px]">
              <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 font-semibold">
                  <th class="p-3">Nama Lengkap</th>
                  <th class="p-3">Username</th>
                  <th class="p-3">Role</th>
                  <th class="p-3">Kontak</th>
                  <th class="p-3 text-center">Status</th>
                  <th class="p-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-if="loading">
                  <td colspan="6" class="text-center py-8 text-gray-400">Memuat data pengguna...</td>
                </tr>
                <tr v-else-if="users.length === 0">
                  <td colspan="6" class="text-center py-8 text-gray-400">Tidak ada data pengguna.</td>
                </tr>
                <tr v-for="u in users" :key="u.id" class="hover:bg-gray-50/70 transition">
                  <td class="p-3 font-semibold text-gray-800 whitespace-nowrap">
                    {{ u.nama }}
                    <div v-if="u.id === auth.user?.id" class="text-[10px] text-emerald-600 font-bold">(Akun Anda)</div>
                  </td>
                  <td class="p-3 font-mono text-xs text-gray-600 whitespace-nowrap">{{ u.username }}</td>
                  <td class="p-3 whitespace-nowrap">
                    <span 
                      class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider"
                      :class="{
                        'bg-emerald-100 text-emerald-800': u.role?.nama === 'kepala_sekolah' || u.role?.nama === 'staf_admin',
                        'bg-blue-100 text-blue-800': u.role?.nama === 'guru',
                        'bg-purple-100 text-purple-800': u.role?.nama === 'wali_murid'
                      }"
                    >
                      {{ u.role?.nama?.replace('_', ' ') }}
                    </span>
                  </td>
                  <td class="p-3 text-xs text-gray-600 whitespace-nowrap">
                    <div>{{ u.email || '-' }}</div>
                    <div class="text-gray-400">{{ u.no_hp || '-' }}</div>
                  </td>
                  <td class="p-3 text-center whitespace-nowrap">
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">
                      {{ u.status || 'Aktif' }}
                    </span>
                  </td>
                  <td class="p-3 text-right whitespace-nowrap space-x-1.5">
                    <button 
                      @click="openEditUserForm(u)"
                      class="inline-flex items-center gap-1 bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs px-2.5 py-1 rounded-lg font-semibold border border-amber-200 cursor-pointer transition"
                      title="Edit Akun Pengguna"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                      <span>Edit</span>
                    </button>
                    <button 
                      @click="resetPassword(u)"
                      class="inline-flex items-center gap-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs px-2.5 py-1 rounded-lg font-semibold border border-indigo-200 cursor-pointer transition"
                      title="Reset Kata Sandi"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                      </svg>
                      <span>Reset Sandi</span>
                    </button>
                    <button 
                      v-if="u.id !== auth.user?.id"
                      @click="deleteUser(u)"
                      class="inline-flex items-center gap-1 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs px-2.5 py-1 rounded-lg font-semibold border border-rose-200 cursor-pointer transition"
                      title="Nonaktifkan Akun"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                      </svg>
                      <span>Nonaktifkan</span>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SUB-VIEW 1.2: HALAMAN FORMULIR USER PENUH (DEDICATED FULL-PAGE) -->
      <div v-else-if="userViewMode === 'form'" class="space-y-6 animate-in fade-in duration-200">
        <!-- Top Navigation & Action Banner -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-center gap-3.5">
            <button
              @click="closeUserForm"
              type="button"
              class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center justify-center shrink-0 shadow-xs group cursor-pointer"
              title="Kembali ke Daftar Akun"
            >
              <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
            </button>
            <div>
              <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
                <span>Pengaturan</span>
                <span>/</span>
                <button type="button" @click="closeUserForm" class="hover:text-emerald-600 hover:underline cursor-pointer">Manajemen Pengguna</button>
                <span>/</span>
                <span class="text-emerald-700 font-bold">{{ isEditingUser ? 'Edit Akun' : 'Tambah Akun Baru' }}</span>
              </div>
              <h1 class="text-lg sm:text-2xl font-black text-gray-900 leading-tight">
                {{ isEditingUser ? `Edit Akun: ${userForm.nama || '...'}` : 'Formulir Akun Pengguna Baru' }}
              </h1>
              <p class="text-xs text-gray-500 mt-0.5">
                {{ isEditingUser ? 'Perbarui nama, username, hak akses role, dan kontak akun login.' : 'Lengkapi kredensial dan hak akses role untuk mendaftarkan akun SIM-BIM baru.' }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2.5 self-end sm:self-center">
            <button
              @click="closeUserForm"
              type="button"
              class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition cursor-pointer"
            >
              Batal
            </button>
            <button
              @click="saveUser"
              type="button"
              class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/25 transition active:scale-95 cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <span>{{ isEditingUser ? 'Simpan Perubahan' : 'Buat Akun Pengguna' }}</span>
            </button>
          </div>
        </div>

        <!-- Main Form Grid: 12 Cols -->
        <form @submit.prevent="saveUser" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <!-- LEFT COLUMN: Live Preview Card (4 cols) -->
          <div class="lg:col-span-4 space-y-6">
            <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-950 p-5 rounded-2xl text-white shadow-lg space-y-4">
              <div class="flex items-center justify-between text-emerald-400 text-xs font-bold">
                <span class="flex items-center gap-1.5">
                  <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                  KARTU PENGGUNA (PREVIEW)
                </span>
                <span class="font-mono text-[10px] text-slate-400">SIM-BIM</span>
              </div>

              <div class="flex items-center gap-3 pt-2">
                <div class="w-12 h-12 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-lg font-bold text-emerald-300 uppercase">
                  {{ userForm.nama ? userForm.nama.charAt(0) : 'U' }}
                </div>
                <div>
                  <h3 class="font-bold text-base text-white truncate max-w-[200px]">
                    {{ userForm.nama || 'Nama Pengguna' }}
                  </h3>
                  <p class="font-mono text-xs text-emerald-300">
                    @{{ userForm.username || 'username' }}
                  </p>
                </div>
              </div>

              <div class="pt-3 border-t border-white/10 space-y-1.5 text-xs text-slate-300">
                <div class="flex justify-between items-center">
                  <span>Role Akses:</span>
                  <span class="font-bold uppercase tracking-wider text-white px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300">
                    {{ userForm.role.replace('_', ' ') }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span>Kontak HP:</span>
                  <span class="font-mono text-white">{{ userForm.no_hp || '-' }}</span>
                </div>
              </div>
            </div>

            <!-- Panduan Role -->
            <div class="bg-emerald-50/70 p-4 rounded-2xl border border-emerald-100 text-xs text-emerald-950 space-y-2">
              <div class="font-bold text-emerald-800">💡 Penjelasan Hak Akses Role</div>
              <ul class="space-y-1 text-[11px] text-emerald-700 list-disc list-inside">
                <li><strong>Kepala Sekolah:</strong> Akses penuh dashboard, monitoring, rekap, & laporan.</li>
                <li><strong>Staf Admin:</strong> Manajemen data master siswa, guru, jadwal, dan inventaris.</li>
                <li><strong>Guru:</strong> Presensi kelas, jurnal bukti mengajar, dan jadwal pribadi.</li>
                <li><strong>Wali Murid:</strong> Portal kehadiran anak dan komunikasi sekolah.</li>
              </ul>
            </div>
          </div>

          <!-- RIGHT COLUMN: Form Inputs (8 cols) -->
          <div class="lg:col-span-8 space-y-6">
            <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
              <h3 class="text-sm font-bold text-gray-900 border-b pb-3">Kredensial & Identitas Akun</h3>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Lengkap *</label>
                  <input
                    v-model="userForm.nama"
                    type="text"
                    required
                    placeholder="Nama lengkap pengguna"
                    class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold text-gray-800 focus:ring-2 focus:ring-emerald-500 outline-none"
                  />
                </div>

                <div>
                  <label class="block font-semibold text-gray-700 text-xs mb-1">Username Login *</label>
                  <input
                    v-model="userForm.username"
                    type="text"
                    required
                    placeholder="Contoh: ahmad_fauzi"
                    class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono font-bold focus:ring-2 focus:ring-emerald-500 outline-none"
                  />
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block font-semibold text-gray-700 text-xs mb-1">Role Hak Akses *</label>
                  <select
                    v-model="userForm.role"
                    required
                    class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white font-bold text-gray-800 focus:ring-2 focus:ring-emerald-500 outline-none"
                  >
                    <option value="guru">Guru Pengampu</option>
                    <option value="staf_admin">Staf Administrasi</option>
                    <option value="kepala_sekolah">Kepala Sekolah</option>
                    <option value="wali_murid">Wali Murid</option>
                  </select>
                </div>

                <div>
                  <label class="block font-semibold text-gray-700 text-xs mb-1">No. WhatsApp / HP</label>
                  <input
                    v-model="userForm.no_hp"
                    type="text"
                    placeholder="081234567890"
                    class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                  />
                </div>
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Alamat Email (Opsional)</label>
                <input
                  v-model="userForm.email"
                  type="email"
                  placeholder="user@simbim.sch.id"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>

              <div v-if="!isEditingUser">
                <label class="block font-semibold text-gray-700 text-xs mb-1">Kata Sandi Default</label>
                <input
                  v-model="userForm.password"
                  type="text"
                  placeholder="Password123!"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono bg-gray-50 focus:ring-2 focus:ring-emerald-500 outline-none"
                />
                <p class="text-[10px] text-gray-400 mt-1">Pengguna dapat mengganti kata sandi setelah masuk ke sistem.</p>
              </div>
            </div>

            <!-- Bottom Action Bar -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between gap-4">
              <button
                @click="closeUserForm"
                type="button"
                class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition flex items-center gap-2 cursor-pointer"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Daftar Akun</span>
              </button>

              <div class="flex items-center gap-3">
                <button
                  @click="closeUserForm"
                  type="button"
                  class="px-4 py-2.5 text-gray-500 hover:text-gray-700 text-xs font-semibold transition cursor-pointer"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/25 transition active:scale-95 cursor-pointer"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  <span>{{ isEditingUser ? 'Simpan Perubahan' : 'Buat Akun Pengguna' }}</span>
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- TAB 2: AUDIT LOGS -->
    <div v-else-if="activeTab === 'logs'" class="space-y-4">
      <!-- Filter Bar -->
      <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-center flex-1">
          <!-- Search input -->
          <div class="relative flex-1 max-w-sm">
            <input 
              v-model="logSearch" 
              @keyup.enter="fetchLogs(1)"
              type="text" 
              placeholder="Cari aktivitas, nama, keterangan, IP..." 
              class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            />
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>

          <!-- Modul filter -->
          <select 
            v-model="logFilterModul" 
            @change="fetchLogs(1)"
            class="border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none text-gray-700"
          >
            <option value="">Semua Modul</option>
            <option value="auth">Auth (Login/Logout)</option>
            <option value="users">Pengguna</option>
            <option value="students">Siswa</option>
            <option value="teachers">Guru & Tendik</option>
            <option value="attendance">Presensi</option>
            <option value="classes">Kelas & Jadwal</option>
            <option value="finance">Keuangan & SPP</option>
            <option value="announcements">Papan Pengumuman</option>
            <option value="settings">Pengaturan Sistem</option>
            <option value="ppdb">PPDB Online</option>
            <option value="cms">CMS Web Sekolah</option>
          </select>

          <!-- Aksi filter -->
          <select 
            v-model="logFilterAksi" 
            @change="fetchLogs(1)"
            class="border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none text-gray-700"
          >
            <option value="">Semua Aksi</option>
            <option value="login">Login</option>
            <option value="logout">Logout</option>
            <option value="create">Tambah (Create)</option>
            <option value="update">Ubah (Update)</option>
            <option value="delete">Hapus (Delete)</option>
            <option value="import">Import Excel</option>
            <option value="export">Export Data</option>
          </select>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <button 
            v-if="logSearch || logFilterModul || logFilterAksi"
            @click="resetLogFilters" 
            class="text-xs font-semibold text-rose-600 hover:text-rose-700 px-3 py-2 rounded-xl hover:bg-rose-50 transition cursor-pointer"
          >
            Reset
          </button>
          <button 
            @click="fetchLogs(logCurrentPage)" 
            class="inline-flex items-center gap-1.5 text-xs font-semibold bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-800 px-3 py-2 rounded-xl transition cursor-pointer"
          >
            <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Muat Ulang
          </button>
        </div>
      </div>

      <!-- Table Container -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[750px]">
            <thead>
              <tr class="bg-gray-50/80 border-b border-gray-100 text-gray-600 font-semibold">
                <th class="p-3.5 w-44">Waktu</th>
                <th class="p-3.5 w-48">Pengguna</th>
                <th class="p-3.5 w-40">Modul & Aksi</th>
                <th class="p-3.5">Keterangan Aktivitas</th>
                <th class="p-3.5 w-32">IP Address</th>
                <th class="p-3.5 w-16 text-center">Detail</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="loading && activityLogs.length === 0">
                <td colspan="6" class="text-center py-10 text-gray-400">
                  <div class="flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Memuat log aktivitas...</span>
                  </div>
                </td>
              </tr>
              <tr v-else-if="activityLogs.length === 0">
                <td colspan="6" class="text-center py-10 text-gray-400">
                  Belum ada riwayat aktivitas yang tercatat.
                </td>
              </tr>
              <tr v-for="log in activityLogs" :key="log.id" class="hover:bg-gray-50/70 transition">
                <!-- Waktu -->
                <td class="p-3.5 text-xs whitespace-nowrap">
                  <div class="font-semibold text-gray-800">{{ formatDateTimeIndo(log.created_at) }}</div>
                  <div class="text-[10px] text-gray-400 font-mono">{{ log.created_at ? new Date(log.created_at).toISOString().substring(11, 19) + ' UTC' : '' }}</div>
                </td>

                <!-- Pengguna -->
                <td class="p-3.5 whitespace-nowrap">
                  <div class="font-bold text-gray-900">{{ log.user?.nama || 'Sistem' }}</div>
                  <div class="text-[11px] text-gray-400 font-mono">@{{ log.user?.username || 'system' }}</div>
                </td>

                <!-- Modul & Aksi -->
                <td class="p-3.5 whitespace-nowrap">
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span 
                      v-if="log.modul" 
                      class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase tracking-wider"
                      :class="getModuleBadgeClass(log.modul)"
                    >
                      {{ log.modul }}
                    </span>
                    <span 
                      class="px-2 py-0.5 rounded text-[11px] font-bold border font-mono uppercase"
                      :class="getActionBadgeClass(log.aksi || log.action)"
                    >
                      {{ log.aksi || log.action || '-' }}
                    </span>
                  </div>
                </td>

                <!-- Keterangan -->
                <td class="p-3.5">
                  <p class="text-gray-800 font-medium text-xs sm:text-sm leading-relaxed">
                    {{ log.keterangan || log.description || '-' }}
                  </p>
                  <p v-if="log.target_type" class="text-[11px] text-gray-400 font-mono mt-0.5">
                    Target: {{ log.target_type }} {{ log.target_id ? '#' + log.target_id : '' }}
                  </p>
                </td>

                <!-- IP Address -->
                <td class="p-3.5 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1 font-mono text-xs text-gray-600 bg-gray-50 px-2 py-0.5 rounded border border-gray-200">
                    {{ log.ip || log.ip_address || '-' }}
                  </span>
                </td>

                <!-- Detail Modal Button -->
                <td class="p-3.5 text-center whitespace-nowrap">
                  <button 
                    v-if="log.data_sebelum || log.data_sesudah"
                    @click="selectedLogForDetail = log"
                    class="p-1.5 text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 rounded-lg transition cursor-pointer"
                    title="Lihat Data Perubahan"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                  <span v-else class="text-gray-300 text-xs">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Bar -->
        <div v-if="logMeta.total > 0" class="p-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-600 bg-gray-50/50">
          <div>
            Menampilkan 
            <span class="font-bold text-gray-800">{{ ((logMeta.current_page - 1) * logMeta.per_page) + 1 }}</span>
            -
            <span class="font-bold text-gray-800">{{ Math.min(logMeta.current_page * logMeta.per_page, logMeta.total) }}</span>
            dari 
            <span class="font-bold text-gray-800">{{ logMeta.total }}</span>
            aktivitas
          </div>

          <div class="flex items-center gap-1.5">
            <button 
              :disabled="logMeta.current_page <= 1"
              @click="fetchLogs(logMeta.current_page - 1)"
              class="px-2.5 py-1.5 rounded-lg border border-gray-200 bg-white font-medium hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
            >
              Sebelumnya
            </button>
            <span class="px-2 font-bold text-gray-700">Halaman {{ logMeta.current_page }} / {{ logMeta.last_page }}</span>
            <button 
              :disabled="logMeta.current_page >= logMeta.last_page"
              @click="fetchLogs(logMeta.current_page + 1)"
              class="px-2.5 py-1.5 rounded-lg border border-gray-200 bg-white font-medium hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>

      <!-- Detail Modal Data Sebelum/Sesudah -->
      <div 
        v-if="selectedLogForDetail" 
        class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center z-50 p-4"
        @click.self="selectedLogForDetail = null"
      >
        <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-xl border border-gray-100 space-y-4 max-h-[85vh] flex flex-col">
          <div class="flex items-center justify-between pb-3 border-b">
            <div>
              <h3 class="font-bold text-gray-900 text-base">Detail Rekam Jejak Data</h3>
              <p class="text-xs text-gray-500">Aksi: {{ selectedLogForDetail.aksi || selectedLogForDetail.action }} | Modul: {{ selectedLogForDetail.modul }}</p>
            </div>
            <button 
              @click="selectedLogForDetail = null" 
              class="p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition cursor-pointer"
            >
              ✕
            </button>
          </div>

          <div class="overflow-y-auto space-y-4 flex-1 pr-1 text-xs font-mono">
            <div v-if="selectedLogForDetail.data_sebelum">
              <div class="font-bold text-rose-600 mb-1 flex items-center gap-1 font-sans text-xs">
                <span>Data Sebelum Perubahan:</span>
              </div>
              <pre class="bg-rose-50 text-rose-950 p-3 rounded-xl border border-rose-100 overflow-x-auto whitespace-pre-wrap">{{ JSON.stringify(selectedLogForDetail.data_sebelum, null, 2) }}</pre>
            </div>

            <div v-if="selectedLogForDetail.data_sesudah">
              <div class="font-bold text-emerald-600 mb-1 flex items-center gap-1 font-sans text-xs">
                <span>Data Sesudah Perubahan:</span>
              </div>
              <pre class="bg-emerald-50 text-emerald-950 p-3 rounded-xl border border-emerald-100 overflow-x-auto whitespace-pre-wrap">{{ JSON.stringify(selectedLogForDetail.data_sesudah, null, 2) }}</pre>
            </div>
          </div>

          <div class="pt-3 border-t flex justify-end">
            <button 
              @click="selectedLogForDetail = null" 
              class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl text-xs transition cursor-pointer"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 3: SETTINGS & GEOFENCE -->
    <div v-else-if="activeTab === 'settings'" class="space-y-6">
      <form @submit.prevent="saveSettings" class="space-y-6">
        <!-- Geofence & Jam Kerja -->
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
          <div class="border-b pb-3">
            <h3 class="font-bold text-gray-800 text-base">Radius Geofence GPS & Jam Kerja Guru</h3>
            <p class="text-xs text-gray-500 mt-0.5">Konfigurasi batasan titik lokasi radius presensi mobile guru menggunakan Google Maps / GPS.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block font-semibold text-xs text-gray-700 mb-1">Latitude Sekolah</label>
              <input v-model.number="settings.geofence_latitude" type="number" step="any" required class="w-full border rounded-xl p-2.5 text-xs font-mono" />
            </div>
            <div>
              <label class="block font-semibold text-xs text-gray-700 mb-1">Longitude Sekolah</label>
              <input v-model.number="settings.geofence_longitude" type="number" step="any" required class="w-full border rounded-xl p-2.5 text-xs font-mono" />
            </div>
            <div>
              <label class="block font-semibold text-xs text-gray-700 mb-1">Radius Presensi (Meter)</label>
              <input v-model.number="settings.geofence_radius_meters" type="number" min="10" required class="w-full border rounded-xl p-2.5 text-xs font-mono" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
            <div>
              <label class="block font-semibold text-xs text-gray-700 mb-1">Jam Masuk Standar</label>
              <input v-model="settings.jam_masuk_guru" type="time" required class="w-full border rounded-xl p-2.5 text-xs font-mono" />
            </div>
            <div>
              <label class="block font-semibold text-xs text-gray-700 mb-1">Jam Pulang Standar</label>
              <input v-model="settings.jam_pulang_guru" type="time" required class="w-full border rounded-xl p-2.5 text-xs font-mono" />
            </div>
            <div>
              <label class="block font-semibold text-xs text-gray-700 mb-1">Toleransi Terlambat (Menit)</label>
              <input v-model.number="settings.toleransi_terlambat_menit" type="number" min="0" required class="w-full border rounded-xl p-2.5 text-xs font-mono" />
            </div>
          </div>
        </div>

        <div class="flex justify-end">
          <button 
            type="submit" 
            :disabled="savingSettings"
            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-2.5 rounded-xl shadow-md transition disabled:opacity-50 cursor-pointer"
          >
            <span v-if="savingSettings">Menyimpan...</span>
            <span v-else>Simpan Perubahan Pengaturan</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
