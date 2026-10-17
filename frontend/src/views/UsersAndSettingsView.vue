<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
import { showConfirm, showSuccess, showError, confirmDeleteWithKeyword } from '../services/swal'

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

const fetchLogs = async () => {
  loading.value = true
  try {
    const res = await api.get('/activity-logs', { params: { search: logSearch.value } })
    activityLogs.value = res.data.data?.data || res.data.data || []
  } catch (err) {
    console.error('Failed to load logs', err)
  } finally {
    loading.value = false
  }
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
                      class="bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs px-2.5 py-1 rounded-lg font-medium border border-amber-200 cursor-pointer"
                    >
                      Edit
                    </button>
                    <button 
                      @click="resetPassword(u)"
                      class="bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs px-2.5 py-1 rounded-lg font-medium border border-blue-200 cursor-pointer"
                    >
                      Reset Password
                    </button>
                    <button 
                      v-if="u.id !== auth.user?.id"
                      @click="deleteUser(u)"
                      class="bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs px-2.5 py-1 rounded-lg font-medium border border-rose-200 cursor-pointer"
                    >
                      Nonaktifkan
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
      <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex gap-4 items-center">
        <input 
          v-model="logSearch" 
          @input="fetchLogs"
          type="text" 
          placeholder="Cari aktivitas log..." 
          class="border border-gray-200 rounded-xl px-4 py-2 text-sm w-72 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
        />
        <button 
          @click="fetchLogs" 
          class="text-xs font-semibold bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-700 px-3 py-2 rounded-xl cursor-pointer"
        >
          Muat Ulang
        </button>
      </div>

      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[650px]">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 font-semibold">
                <th class="p-3">Waktu</th>
                <th class="p-3">Pengguna</th>
                <th class="p-3">Aksi</th>
                <th class="p-3">Keterangan Aktivitas</th>
                <th class="p-3">IP Address</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="loading">
                <td colspan="5" class="text-center py-8 text-gray-400">Memuat log aktivitas...</td>
              </tr>
              <tr v-else-if="activityLogs.length === 0">
                <td colspan="5" class="text-center py-8 text-gray-400">Belum ada riwayat aktivitas yang tercatat.</td>
              </tr>
              <tr v-for="log in activityLogs" :key="log.id" class="hover:bg-gray-50/70 transition">
                <td class="p-3 text-xs text-gray-500 whitespace-nowrap">{{ log.created_at }}</td>
                <td class="p-3 font-semibold text-gray-800 whitespace-nowrap">{{ log.user?.nama || 'Sistem' }}</td>
                <td class="p-3 whitespace-nowrap">
                  <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-gray-100 text-gray-700 font-mono uppercase">
                    {{ log.action }}
                  </span>
                </td>
                <td class="p-3 text-gray-700">{{ log.description }}</td>
                <td class="p-3 font-mono text-xs text-gray-400 whitespace-nowrap">{{ log.ip_address || '-' }}</td>
              </tr>
            </tbody>
          </table>
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
