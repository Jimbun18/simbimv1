<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'
import { showSuccess, showError, showConfirm } from '../services/swal'
import { getStorageUrl } from '../utils/storage'
import InfoTooltip from '../components/common/InfoTooltip.vue'

interface PersonelOption {
  id: number
  user_id?: number | null
  nama_lengkap: string
  nip?: string | null
  nik?: string | null
  jenis_ptk?: string | null
  jabatan?: string | null
  foto_path?: string | null
}

interface StructuralAssignment {
  id: number
  posisi_kode: string
  posisi_nama: string
  posisi_kategori: string
  parent_kode?: string | null
  user_id?: number | null
  teacher_id?: number | null
  staff_id?: number | null
  academic_year_id?: number | null
  no_sk?: string | null
  keterangan?: string | null
  menu_permissions?: string[] | null
  is_active: boolean
  teacher?: PersonelOption | null
  staff?: PersonelOption | null
  user?: {
    id: number
    nama: string
    username: string
    foto_path?: string | null
  } | null
}

const auth = useAuthStore()

const loading = ref(false)
const saving = ref(false)
const assignments = ref<StructuralAssignment[]>([])
const teachers = ref<PersonelOption[]>([])
const staffList = ref<PersonelOption[]>([])
const activeAcademicYear = ref<any>(null)

// Modal edit assignment
const isModalOpen = ref(false)
const activeAssignment = ref<StructuralAssignment | null>(null)
const selectedType = ref<'teacher' | 'staff'>('teacher')
const selectedPersonelId = ref<number | ''>('')
const formNoSk = ref('')
const formKeterangan = ref('')

const fetchStructure = async () => {
  loading.value = true
  try {
    const res = await api.get('/organization-structure')
    if (res.data?.success) {
      assignments.value = res.data.data.assignments || []
      teachers.value = res.data.data.teachers || []
      staffList.value = res.data.data.staff || []
      activeAcademicYear.value = res.data.data.active_academic_year || null
    }
  } catch (err: any) {
    showError('Gagal Memuat Data', 'Tidak dapat memuat struktur organisasi sekolah: ' + (err.response?.data?.message || err.message))
  } finally {
    loading.value = false
  }
}

// Map helper untuk mengambil posisi dengan cepat
const getPos = (kode: string): StructuralAssignment | undefined => {
  return assignments.value.find((a) => a.posisi_kode === kode)
}

const getPersonelDisplayName = (a?: StructuralAssignment): string => {
  if (!a) return '-'
  if (a.teacher) return a.teacher.nama_lengkap
  if (a.staff) return a.staff.nama_lengkap
  if (a.user) return a.user.nama
  return 'Belum Ditugaskan'
}

const getPersonelFoto = (a?: StructuralAssignment): string | null => {
  if (!a) return null
  const path = a.teacher?.foto_path || a.staff?.foto_path || a.user?.foto_path
  return path ? getStorageUrl(path) : null
}

const getPersonelNipNik = (a?: StructuralAssignment): string => {
  if (!a) return ''
  if (a.teacher) {
    if (a.teacher.nip) return `NIP: ${a.teacher.nip}`
    if (a.teacher.nik) return `NIK: ${a.teacher.nik}`
  }
  if (a.staff && a.staff.nik) {
    return `NIK: ${a.staff.nik}`
  }
  return ''
}

// Buka modal untuk menetapkan personel ke posisi
const openAssignModal = (posisiKode: string) => {
  if (!auth.isAdmin) return

  const target = getPos(posisiKode)
  if (!target) return

  activeAssignment.value = target
  formNoSk.value = target.no_sk || ''
  formKeterangan.value = target.keterangan || ''

  if (target.staff_id) {
    selectedType.value = 'staff'
    selectedPersonelId.value = target.staff_id
  } else if (target.teacher_id) {
    selectedType.value = 'teacher'
    selectedPersonelId.value = target.teacher_id
  } else {
    selectedType.value = 'teacher'
    selectedPersonelId.value = ''
  }

  isModalOpen.value = true
}

// Simpan penugasan ke backend
const handleSaveAssignment = async () => {
  if (!activeAssignment.value) return

  saving.value = true
  try {
    const payload: any = {
      posisi_kode: activeAssignment.value.posisi_kode,
      no_sk: formNoSk.value || null,
      keterangan: formKeterangan.value || null,
    }

    if (selectedType.value === 'teacher') {
      payload.teacher_id = selectedPersonelId.value || null
      payload.staff_id = null
    } else {
      payload.staff_id = selectedPersonelId.value || null
      payload.teacher_id = null
    }

    const res = await api.post('/organization-structure/assign', payload)
    if (res.data?.success) {
      showSuccess('Berhasil Disimpan', res.data.message)
      isModalOpen.value = false
      await fetchStructure()

      // Jika user yang mengedit adalah orang yang ditugaskan, refresh profil store
      if (auth.user?.id) {
        auth.fetchProfile()
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan', err.response?.data?.message || 'Terjadi kesalahan saat menyimpan penugasan.')
  } finally {
    saving.value = false
  }
}

// Kosongkan penugasan
const handleUnassign = async (assignment: StructuralAssignment) => {
  if (!auth.isAdmin) return

  const confirmed = await showConfirm(
    'Kosongkan Posisi Ini?',
    `Apakah Anda yakin ingin mencabut pejabat pada posisi "${assignment.posisi_nama}"? Hak akses menu terkait akan otomatis dicabut.`
  )

  if (!confirmed) return

  saving.value = true
  try {
    const res = await api.delete(`/organization-structure/assign/${assignment.id}`)
    if (res.data?.success) {
      showSuccess('Posisi Dikosongkan', res.data.message)
      isModalOpen.value = false
      await fetchStructure()
      if (auth.user?.id) {
        auth.fetchProfile()
      }
    }
  } catch (err: any) {
    showError('Gagal Mencabut', err.response?.data?.message || 'Terjadi kesalahan.')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchStructure()
})
</script>

<template>
  <div class="space-y-6 pb-16">
    <!-- 1. HEADER HALAMAN -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-100 shadow-xs">
      <div>
        <div class="flex items-center gap-2">
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
            Tata Kelola & Hak Akses
          </span>
          <span v-if="activeAcademicYear" class="text-xs text-slate-500 font-medium">
            TP {{ activeAcademicYear.nama }}
          </span>
        </div>
        <div class="flex items-center gap-2 mt-1">
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Struktur Organisasi Sekolah</h1>
          <InfoTooltip text="Bagan resmi struktur organisasi SDI Bina Insan Mandiri Purwokerto. Penugasan posisi di sini otomatis membagi hak akses modul menu kepada guru dan staf yang ditunjuk." />
        </div>
        <p class="text-xs text-slate-500 mt-1 max-w-2xl">
          Penetapan penugasan struktural (seperti Bendahara, BK, Waka Kurikulum, Waka Kesiswaan, dan Kepala TU) secara otomatis membuka modul kerja terkait.
        </p>
      </div>

      <div class="flex items-center gap-2.5 shrink-0">
        <button
          type="button"
          @click="fetchStructure"
          :disabled="loading"
          class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
        >
          <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>Segarkan</span>
        </button>
      </div>
    </div>

    <!-- 2. BAGAN VISUAL STRUKTUR ORGANISASI SEKOLAH -->
    <div class="bg-slate-50/70 border border-slate-200/80 rounded-3xl p-6 sm:p-8 shadow-xs overflow-x-auto">
      <div class="min-w-[950px] flex flex-col items-center select-none">
        
        <!-- ================= LEVEL 1: KEPALA SEKOLAH ================= -->
        <div class="w-72 relative group">
          <div
            @click="openAssignModal('kepala_sekolah')"
            class="p-4 rounded-2xl bg-white border-2 border-indigo-200/90 shadow-md hover:shadow-lg transition-all text-center relative cursor-pointer hover:border-indigo-500"
          >
            <div class="text-[10px] font-black uppercase tracking-wider text-indigo-600 mb-1">
              Pimpinan Tertinggi
            </div>
            <h3 class="text-base font-extrabold text-slate-900 leading-tight">
              {{ getPos('kepala_sekolah')?.posisi_nama || 'Kepala Sekolah' }}
            </h3>
            
            <!-- Personel Card Preview -->
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-center gap-2.5">
              <div class="w-10 h-10 rounded-full overflow-hidden bg-indigo-100 border border-indigo-200 shrink-0 flex items-center justify-center text-xs font-bold text-indigo-700">
                <img
                  v-if="getPersonelFoto(getPos('kepala_sekolah'))"
                  :src="getPersonelFoto(getPos('kepala_sekolah'))!"
                  class="w-full h-full object-cover"
                />
                <span v-else>KS</span>
              </div>
              <div class="text-left min-w-0">
                <div class="text-xs font-bold text-slate-900 truncate">
                  {{ getPersonelDisplayName(getPos('kepala_sekolah')) }}
                </div>
                <div class="text-[10px] text-slate-500 font-mono">
                  {{ getPersonelNipNik(getPos('kepala_sekolah')) || 'Super Admin SIM-BIM' }}
                </div>
              </div>
            </div>

            <!-- Permission badge -->
            <div class="mt-2.5 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9.5px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
              <span>Akses Penuh Seluruh Modul</span>
            </div>

            <span v-if="auth.isAdmin" class="absolute top-2 right-2 text-slate-300 group-hover:text-indigo-600 transition text-xs">
              ✎
            </span>
          </div>

          <!-- Garis Vertikal Turun ke Bawah -->
          <div class="w-0.5 h-8 bg-slate-300 mx-auto"></div>
        </div>

        <!-- ================= LEVEL 2: BK, BENDAHARA, KEPALA TU ================= -->
        <div class="w-full max-w-4xl relative">
          <!-- Garis Horizontal Penghubung 3 Kotak Operasional -->
          <div class="absolute top-0 left-[16%] right-[16%] h-0.5 bg-slate-300"></div>

          <!-- Grid 3 Kotak: BK, Bendahara, Kepala TU -->
          <div class="grid grid-cols-3 gap-6 pt-6">
            
            <!-- 1. BK (Bimbingan Konseling) -->
            <div class="relative group">
              <!-- Garis Vertikal dari Cabang Atas -->
              <div class="absolute -top-6 left-1/2 -translate-x-1/2 w-0.5 h-6 bg-slate-300"></div>

              <div
                @click="openAssignModal('bk')"
                class="p-4 rounded-2xl bg-white border-2 border-emerald-200 shadow-sm hover:shadow-md transition-all text-center relative cursor-pointer hover:border-emerald-500"
              >
                <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 mb-0.5">
                  Layanan Siswa
                </div>
                <h4 class="text-sm font-extrabold text-slate-900">
                  {{ getPos('bk')?.posisi_nama || 'BK' }}
                </h4>
                
                <div class="mt-2.5 pt-2.5 border-t border-slate-100 flex items-center justify-center gap-2">
                  <div class="w-8 h-8 rounded-full overflow-hidden bg-emerald-100 border border-emerald-200 shrink-0 flex items-center justify-center text-[10px] font-bold text-emerald-800">
                    <img
                      v-if="getPersonelFoto(getPos('bk'))"
                      :src="getPersonelFoto(getPos('bk'))!"
                      class="w-full h-full object-cover"
                    />
                    <span v-else>BK</span>
                  </div>
                  <div class="text-left min-w-0">
                    <div class="text-xs font-bold text-slate-900 truncate">
                      {{ getPersonelDisplayName(getPos('bk')) }}
                    </div>
                    <div class="text-[9.5px] text-slate-500 font-mono">
                      {{ getPersonelNipNik(getPos('bk')) || 'Konselor Sekolah' }}
                    </div>
                  </div>
                </div>

                <div class="mt-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                  <span>Modul: Monitoring BK & Observasi</span>
                </div>

                <span v-if="auth.isAdmin" class="absolute top-2 right-2 text-slate-300 group-hover:text-emerald-600 transition text-xs">
                  ✎
                </span>
              </div>
            </div>

            <!-- 2. BENDAHARA (KEUANGAN & SPP) -->
            <div class="relative group">
              <!-- Garis Vertikal dari Cabang Atas -->
              <div class="absolute -top-6 left-1/2 -translate-x-1/2 w-0.5 h-6 bg-slate-300"></div>

              <div
                @click="openAssignModal('bendahara')"
                class="p-4 rounded-2xl bg-white border-2 border-emerald-300 shadow-md hover:shadow-lg transition-all text-center relative cursor-pointer hover:border-emerald-600 ring-2 ring-emerald-500/10"
              >
                <div class="text-[10px] font-black uppercase tracking-wider text-emerald-700 mb-0.5 flex items-center justify-center gap-1">
                  <span>💰</span>
                  <span>Otoritas Keuangan</span>
                </div>
                <h4 class="text-sm font-extrabold text-slate-900">
                  {{ getPos('bendahara')?.posisi_nama || 'Bendahara' }}
                </h4>
                
                <div class="mt-2.5 pt-2.5 border-t border-slate-100 flex items-center justify-center gap-2">
                  <div class="w-8 h-8 rounded-full overflow-hidden bg-emerald-100 border border-emerald-200 shrink-0 flex items-center justify-center text-[10px] font-bold text-emerald-800">
                    <img
                      v-if="getPersonelFoto(getPos('bendahara'))"
                      :src="getPersonelFoto(getPos('bendahara'))!"
                      class="w-full h-full object-cover"
                    />
                    <span v-else>BD</span>
                  </div>
                  <div class="text-left min-w-0">
                    <div class="text-xs font-bold text-slate-900 truncate">
                      {{ getPersonelDisplayName(getPos('bendahara')) }}
                    </div>
                    <div class="text-[9.5px] text-slate-500 font-mono">
                      {{ getPersonelNipNik(getPos('bendahara')) || 'Bendahara Sekolah' }}
                    </div>
                  </div>
                </div>

                <div class="mt-2 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-emerald-600 text-white shadow-xs">
                  <span>Akses: Keuangan & SPP Siswa</span>
                </div>

                <span v-if="auth.isAdmin" class="absolute top-2 right-2 text-slate-300 group-hover:text-emerald-600 transition text-xs">
                  ✎
                </span>
              </div>
            </div>

            <!-- 3. KEPALA TU (TATA USAHA) -->
            <div class="relative group">
              <!-- Garis Vertikal dari Cabang Atas -->
              <div class="absolute -top-6 left-1/2 -translate-x-1/2 w-0.5 h-6 bg-slate-300"></div>

              <div
                @click="openAssignModal('kepala_tu')"
                class="p-4 rounded-2xl bg-white border-2 border-emerald-200 shadow-sm hover:shadow-md transition-all text-center relative cursor-pointer hover:border-emerald-500"
              >
                <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 mb-0.5">
                  Administrasi & Aset
                </div>
                <h4 class="text-sm font-extrabold text-slate-900">
                  {{ getPos('kepala_tu')?.posisi_nama || 'Kepala TU' }}
                </h4>
                
                <div class="mt-2.5 pt-2.5 border-t border-slate-100 flex items-center justify-center gap-2">
                  <div class="w-8 h-8 rounded-full overflow-hidden bg-emerald-100 border border-emerald-200 shrink-0 flex items-center justify-center text-[10px] font-bold text-emerald-800">
                    <img
                      v-if="getPersonelFoto(getPos('kepala_tu'))"
                      :src="getPersonelFoto(getPos('kepala_tu'))!"
                      class="w-full h-full object-cover"
                    />
                    <span v-else>TU</span>
                  </div>
                  <div class="text-left min-w-0">
                    <div class="text-xs font-bold text-slate-900 truncate">
                      {{ getPersonelDisplayName(getPos('kepala_tu')) }}
                    </div>
                    <div class="text-[9.5px] text-slate-500 font-mono">
                      {{ getPersonelNipNik(getPos('kepala_tu')) || 'Staf Tata Usaha' }}
                    </div>
                  </div>
                </div>

                <div class="mt-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                  <span>Modul: Sarpras & Tendik</span>
                </div>

                <span v-if="auth.isAdmin" class="absolute top-2 right-2 text-slate-300 group-hover:text-emerald-600 transition text-xs">
                  ✎
                </span>
              </div>
            </div>

          </div>

          <!-- Garis Vertikal Tengah Turun ke Level Wakil Kepala -->
          <div class="w-0.5 h-10 bg-slate-300 mx-auto"></div>
        </div>

        <!-- ================= LEVEL 3: WAKA KURIKULUM & WAKA KESISWAAN ================= -->
        <div class="w-full max-w-2xl relative">
          <!-- Garis Horizontal Cabang Dua Waka -->
          <div class="absolute top-0 left-[25%] right-[25%] h-0.5 bg-slate-300"></div>

          <!-- Garis Koordinasi Putus-putus Antara Waka Kurikulum & Waka Kesiswaan -->
          <div class="absolute top-1/2 left-[38%] right-[38%] -translate-y-1/2 border-t-2 border-dashed border-indigo-400 z-10 flex items-center justify-center">
            <span class="bg-indigo-50 border border-indigo-200 text-indigo-700 text-[9px] font-bold px-2 py-0.5 rounded-full -mt-2">
              koordinasi
            </span>
          </div>

          <div class="grid grid-cols-2 gap-12 pt-6">
            
            <!-- WAKA KURIKULUM -->
            <div class="relative group">
              <!-- Garis Vertikal dari Cabang Atas -->
              <div class="absolute -top-6 left-1/2 -translate-x-1/2 w-0.5 h-6 bg-slate-300"></div>

              <div
                @click="openAssignModal('waka_kurikulum')"
                class="p-4 rounded-2xl bg-white border-2 border-indigo-300 shadow-md hover:shadow-lg transition-all text-center relative cursor-pointer hover:border-indigo-600"
              >
                <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-700 mb-0.5">
                  Wakil Kepala
                </div>
                <h4 class="text-sm font-extrabold text-slate-900">
                  {{ getPos('waka_kurikulum')?.posisi_nama || 'Waka Kurikulum' }}
                </h4>
                
                <div class="mt-2.5 pt-2.5 border-t border-slate-100 flex items-center justify-center gap-2">
                  <div class="w-8 h-8 rounded-full overflow-hidden bg-indigo-100 border border-indigo-200 shrink-0 flex items-center justify-center text-[10px] font-bold text-indigo-800">
                    <img
                      v-if="getPersonelFoto(getPos('waka_kurikulum'))"
                      :src="getPersonelFoto(getPos('waka_kurikulum'))!"
                      class="w-full h-full object-cover"
                    />
                    <span v-else>WK</span>
                  </div>
                  <div class="text-left min-w-0">
                    <div class="text-xs font-bold text-slate-900 truncate">
                      {{ getPersonelDisplayName(getPos('waka_kurikulum')) }}
                    </div>
                    <div class="text-[9.5px] text-slate-500 font-mono">
                      {{ getPersonelNipNik(getPos('waka_kurikulum')) || 'Manajemen Akademik' }}
                    </div>
                  </div>
                </div>

                <div class="mt-2 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-semibold bg-indigo-50 text-indigo-800 border border-indigo-200">
                  <span>Modul: Jadwal KBM & Buku Nilai</span>
                </div>

                <span v-if="auth.isAdmin" class="absolute top-2 right-2 text-slate-300 group-hover:text-indigo-600 transition text-xs">
                  ✎
                </span>
              </div>

              <!-- Garis Turun ke Bawah (Guru Tema & Extra) -->
              <div class="w-0.5 h-8 bg-slate-300 mx-auto"></div>
            </div>

            <!-- WAKA KESISWAAN -->
            <div class="relative group">
              <!-- Garis Vertikal dari Cabang Atas -->
              <div class="absolute -top-6 left-1/2 -translate-x-1/2 w-0.5 h-6 bg-slate-300"></div>

              <div
                @click="openAssignModal('waka_kesiswaan')"
                class="p-4 rounded-2xl bg-white border-2 border-indigo-300 shadow-md hover:shadow-lg transition-all text-center relative cursor-pointer hover:border-indigo-600"
              >
                <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-700 mb-0.5">
                  Wakil Kepala
                </div>
                <h4 class="text-sm font-extrabold text-slate-900">
                  {{ getPos('waka_kesiswaan')?.posisi_nama || 'Waka Kesiswaan' }}
                </h4>
                
                <div class="mt-2.5 pt-2.5 border-t border-slate-100 flex items-center justify-center gap-2">
                  <div class="w-8 h-8 rounded-full overflow-hidden bg-indigo-100 border border-indigo-200 shrink-0 flex items-center justify-center text-[10px] font-bold text-indigo-800">
                    <img
                      v-if="getPersonelFoto(getPos('waka_kesiswaan'))"
                      :src="getPersonelFoto(getPos('waka_kesiswaan'))!"
                      class="w-full h-full object-cover"
                    />
                    <span v-else>WS</span>
                  </div>
                  <div class="text-left min-w-0">
                    <div class="text-xs font-bold text-slate-900 truncate">
                      {{ getPersonelDisplayName(getPos('waka_kesiswaan')) }}
                    </div>
                    <div class="text-[9.5px] text-slate-500 font-mono">
                      {{ getPersonelNipNik(getPos('waka_kesiswaan')) || 'Pembinaan Santri' }}
                    </div>
                  </div>
                </div>

                <div class="mt-2 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-semibold bg-indigo-50 text-indigo-800 border border-indigo-200">
                  <span>Modul: Siswa, Wali, PPDB</span>
                </div>

                <span v-if="auth.isAdmin" class="absolute top-2 right-2 text-slate-300 group-hover:text-indigo-600 transition text-xs">
                  ✎
                </span>
              </div>

              <!-- Garis Turun ke Bawah (Pelaksana Kesiswaan) -->
              <div class="w-0.5 h-8 bg-slate-300 mx-auto"></div>
            </div>

          </div>
        </div>

        <!-- ================= LEVEL 4: JALUR PELAKSANA KURIKULUM & KESISWAAN ================= -->
        <div class="w-full grid grid-cols-12 gap-4">
          
          <!-- SISI KIRI: Di bawah Waka Kurikulum (Span 5) -->
          <div class="col-span-5 relative">
            <!-- Garis Horizontal Atas -->
            <div class="absolute -top-0 left-[25%] right-[25%] h-0.5 bg-slate-300"></div>

            <div class="grid grid-cols-2 gap-3 pt-4">
              <!-- Guru Tema -->
              <div class="relative group">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-0.5 h-4 bg-slate-300"></div>
                <div class="p-3 bg-white rounded-xl border border-slate-200/90 text-center shadow-2xs">
                  <div class="text-[9.5px] font-bold text-slate-400 uppercase">Pelaksana KBM</div>
                  <div class="text-xs font-extrabold text-slate-800 mt-0.5">Guru Tema</div>
                  <p class="text-[9.5px] text-slate-500 mt-1">Guru Kelas / Mata Pelajaran Kurikulum</p>
                  <router-link to="/teachers" class="text-[9px] text-emerald-600 font-bold hover:underline mt-1 inline-block">
                    Lihat {{ teachers.length }} Pendidik &rarr;
                  </router-link>
                </div>
              </div>

              <!-- Guru Extra -->
              <div class="relative group">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-0.5 h-4 bg-slate-300"></div>
                <div class="p-3 bg-white rounded-xl border border-slate-200/90 text-center shadow-2xs">
                  <div class="text-[9.5px] font-bold text-slate-400 uppercase">Pelaksana KBM</div>
                  <div class="text-xs font-extrabold text-slate-800 mt-0.5">Guru Extra</div>
                  <p class="text-[9.5px] text-slate-500 mt-1">Instruktur Ekstrakurikuler & Life Skill</p>
                  <router-link to="/classes-schedules" class="text-[9px] text-emerald-600 font-bold hover:underline mt-1 inline-block">
                    Jadwal KBM &rarr;
                  </router-link>
                </div>
              </div>
            </div>
          </div>

          <!-- SISI KANAN: Di bawah Waka Kesiswaan (Span 7) -->
          <div class="col-span-7 relative">
            <!-- Garis Horizontal Atas -->
            <div class="absolute -top-0 left-[12%] right-[12%] h-0.5 bg-slate-300"></div>

            <div class="grid grid-cols-4 gap-2.5 pt-4">
              
              <!-- 1. Wali Kelas -->
              <div class="relative group">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-0.5 h-4 bg-slate-300"></div>
                <div class="p-3 bg-white rounded-xl border border-slate-200/90 text-center shadow-2xs">
                  <div class="text-[9.5px] font-bold text-slate-400 uppercase">Binaan</div>
                  <div class="text-xs font-extrabold text-slate-800 mt-0.5">Wali Kelas</div>
                  <p class="text-[9px] text-slate-500 mt-1">Pendamping Rombel & Rapor Kelas</p>
                  <router-link to="/classes-schedules" class="text-[9px] text-indigo-600 font-bold hover:underline mt-1 inline-block">
                    Rombel Kelas &rarr;
                  </router-link>
                </div>
              </div>

              <!-- 2. Bina Kesiswaan -->
              <div class="relative group">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-0.5 h-4 bg-slate-300"></div>
                <div
                  @click="openAssignModal('bina_kesiswaan')"
                  class="p-3 bg-white rounded-xl border border-slate-200/90 text-center shadow-2xs hover:border-indigo-400 cursor-pointer transition relative"
                >
                  <div class="text-[9.5px] font-bold text-slate-400 uppercase">Sub Bidang</div>
                  <div class="text-xs font-extrabold text-slate-800 mt-0.5 truncate">
                    {{ getPos('bina_kesiswaan')?.posisi_nama || 'Bina Kesiswaan' }}
                  </div>
                  <div class="text-[9.5px] font-bold text-indigo-900 mt-1 truncate">
                    {{ getPersonelDisplayName(getPos('bina_kesiswaan')) }}
                  </div>
                  <p class="text-[8.5px] text-slate-400 truncate">Kedisiplinan & Tata Tertib</p>
                </div>
              </div>

              <!-- 3. Bina Potensi -->
              <div class="relative group">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-0.5 h-4 bg-slate-300"></div>
                <div
                  @click="openAssignModal('bina_potensi')"
                  class="p-3 bg-white rounded-xl border border-slate-200/90 text-center shadow-2xs hover:border-indigo-400 cursor-pointer transition relative"
                >
                  <div class="text-[9.5px] font-bold text-slate-400 uppercase">Sub Bidang</div>
                  <div class="text-xs font-extrabold text-slate-800 mt-0.5 truncate">
                    {{ getPos('bina_potensi')?.posisi_nama || 'Bina Potensi' }}
                  </div>
                  <div class="text-[9.5px] font-bold text-indigo-900 mt-1 truncate">
                    {{ getPersonelDisplayName(getPos('bina_potensi')) }}
                  </div>
                  <p class="text-[8.5px] text-slate-400 truncate">Minat, Bakat & Ekskul</p>
                </div>
              </div>

              <!-- 4. Bina Prestasi -->
              <div class="relative group">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-0.5 h-4 bg-slate-300"></div>
                <div
                  @click="openAssignModal('bina_prestasi')"
                  class="p-3 bg-white rounded-xl border border-slate-200/90 text-center shadow-2xs hover:border-indigo-400 cursor-pointer transition relative"
                >
                  <div class="text-[9.5px] font-bold text-slate-400 uppercase">Sub Bidang</div>
                  <div class="text-xs font-extrabold text-slate-800 mt-0.5 truncate">
                    {{ getPos('bina_prestasi')?.posisi_nama || 'Bina Prestasi' }}
                  </div>
                  <div class="text-[9.5px] font-bold text-indigo-900 mt-1 truncate">
                    {{ getPersonelDisplayName(getPos('bina_prestasi')) }}
                  </div>
                  <p class="text-[8.5px] text-slate-400 truncate">Kejuaraan & Apresiasi</p>
                </div>
              </div>

            </div>
          </div>

        </div>

      </div>
    </div>

    <!-- 3. TABEL DAFTAR PENUGASAN STRUKTURAL & HAK AKSES -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-xs p-6 space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
        <div>
          <h3 class="font-extrabold text-slate-900 text-base">Rekapitulasi Penugasan & Delegasi Hak Akses</h3>
          <p class="text-xs text-slate-400">Rincian personel yang memegang posisi struktural beserta menu yang terbuka.</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="text-slate-500 font-bold uppercase tracking-wider text-[11px] border-b border-slate-100 bg-slate-50/70">
              <th class="py-3 px-4">POSISI JABATAN</th>
              <th class="py-3 px-4">PEJABAT / PERSONEL</th>
              <th class="py-3 px-4">NO. SK / KETERANGAN</th>
              <th class="py-3 px-4">HAK AKSES MODUL</th>
              <th v-if="auth.isAdmin" class="py-3 px-4 text-center">AKSI</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="a in assignments" :key="a.id" class="hover:bg-slate-50/50 transition">
              <td class="py-3.5 px-4 font-bold text-slate-900 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full" :class="a.user_id || a.teacher_id || a.staff_id ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                  <span>{{ a.posisi_nama }}</span>
                </div>
              </td>
              <td class="py-3.5 px-4">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-full overflow-hidden bg-slate-100 border border-slate-200 shrink-0 flex items-center justify-center font-bold text-[10px] text-slate-600">
                    <img v-if="getPersonelFoto(a)" :src="getPersonelFoto(a)!" class="w-full h-full object-cover" />
                    <span v-else>{{ a.posisi_nama.substring(0, 2).toUpperCase() }}</span>
                  </div>
                  <div>
                    <div class="font-bold text-slate-900 text-xs">
                      {{ getPersonelDisplayName(a) }}
                    </div>
                    <div class="text-[10px] text-slate-400 font-mono">
                      {{ getPersonelNipNik(a) }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="py-3.5 px-4 text-slate-600 text-[11px] max-w-xs">
                <div v-if="a.no_sk" class="font-semibold text-slate-800">SK: {{ a.no_sk }}</div>
                <div class="text-slate-400 text-[10px] truncate">{{ a.keterangan || '-' }}</div>
              </td>
              <td class="py-3.5 px-4 whitespace-nowrap">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="p in (a.menu_permissions || [])"
                    :key="p"
                    class="px-2 py-0.5 rounded-md text-[9.5px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200"
                  >
                    {{ p }}
                  </span>
                  <span v-if="!a.menu_permissions || a.menu_permissions.length === 0" class="text-slate-400 text-[10px]">
                    Standar
                  </span>
                </div>
              </td>
              <td v-if="auth.isAdmin" class="py-3.5 px-4 text-center whitespace-nowrap">
                <button
                  type="button"
                  @click="openAssignModal(a.posisi_kode)"
                  class="px-3 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg transition text-xs cursor-pointer border border-indigo-200"
                >
                  Tugaskan
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 4. MODAL PENUGASAN STRUKTURAL (ADMIN ONLY) -->
    <div
      v-if="isModalOpen && activeAssignment"
      class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4 backdrop-blur-xs"
      @click="isModalOpen = false"
    >
      <div class="max-w-md w-full bg-white rounded-3xl p-6 shadow-2xl space-y-4" @click.stop>
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <div class="text-[10px] font-bold text-indigo-600 uppercase">Penugasan Struktural</div>
            <h3 class="font-black text-slate-900 text-base">{{ activeAssignment.posisi_nama }}</h3>
          </div>
          <button @click="isModalOpen = false" class="text-slate-400 hover:text-slate-700 text-2xl font-bold p-1 cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="handleSaveAssignment" class="space-y-4 text-xs">
          <!-- Pilih Sumber Personel: Guru atau Tenaga Kependidikan -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Kategori Personel</label>
            <div class="grid grid-cols-2 gap-2">
              <button
                type="button"
                @click="selectedType = 'teacher'; selectedPersonelId = ''"
                class="py-2 px-3 rounded-xl border text-center font-bold transition cursor-pointer"
                :class="selectedType === 'teacher' ? 'bg-indigo-50 border-indigo-500 text-indigo-700' : 'bg-slate-50 border-slate-200 text-slate-600'"
              >
                Pendidik (Guru)
              </button>
              <button
                type="button"
                @click="selectedType = 'staff'; selectedPersonelId = ''"
                class="py-2 px-3 rounded-xl border text-center font-bold transition cursor-pointer"
                :class="selectedType === 'staff' ? 'bg-indigo-50 border-indigo-500 text-indigo-700' : 'bg-slate-50 border-slate-200 text-slate-600'"
              >
                Tendik (Staf TU)
              </button>
            </div>
          </div>

          <!-- Dropdown Personel -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">
              Pilih {{ selectedType === 'teacher' ? 'Guru' : 'Staf Tendik' }} yang Ditugaskan
            </label>
            <select
              v-model="selectedPersonelId"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-semibold text-slate-800 focus:ring-2 focus:ring-indigo-500"
            >
              <option value="">-- Pilih Personel --</option>
              <template v-if="selectedType === 'teacher'">
                <option v-for="t in teachers" :key="t.id" :value="t.id">
                  {{ t.nama_lengkap }} {{ t.nip ? `(NIP: ${t.nip})` : '' }}
                </option>
              </template>
              <template v-else>
                <option v-for="s in staffList" :key="s.id" :value="s.id">
                  {{ s.nama_lengkap }} ({{ s.jabatan || 'Tendik' }})
                </option>
              </template>
            </select>
          </div>

          <!-- No SK -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Nomor SK Penugasan (Opsional)</label>
            <input
              v-model="formNoSk"
              type="text"
              placeholder="Contoh: 045/SK-YAS/BIM/VII/2026"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <!-- Keterangan / Catatan -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Catatan Tugas & Wewenang</label>
            <textarea
              v-model="formKeterangan"
              rows="2"
              placeholder="Deskripsi tugas tambahan atau wewenang khusus..."
              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-slate-800 focus:ring-2 focus:ring-indigo-500"
            ></textarea>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center justify-between pt-3 border-t border-slate-100">
            <button
              v-if="activeAssignment.teacher_id || activeAssignment.staff_id"
              type="button"
              @click="handleUnassign(activeAssignment)"
              :disabled="saving"
              class="px-3 py-2 rounded-xl text-rose-600 hover:bg-rose-50 font-bold transition text-xs cursor-pointer"
            >
              Kosongkan Pejabat
            </button>
            <div v-else></div>

            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="isModalOpen = false"
                class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 cursor-pointer"
              >
                Batal
              </button>
              <button
                type="submit"
                :disabled="saving"
                class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold transition disabled:opacity-50 cursor-pointer shadow-xs"
              >
                {{ saving ? 'Menyimpan...' : 'Simpan Penugasan' }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
