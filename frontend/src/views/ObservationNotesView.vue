<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
import { showSuccess, showError, showConfirm } from '../services/swal'
import { formatDateIndo } from '../utils/date'

const auth = useAuthStore()

// Navigation View State: 'list' | 'detail' | 'form' | 'feedback'
const currentView = ref<'list' | 'detail' | 'form' | 'feedback'>('list')

// State
const loading = ref(false)
const observations = ref<any[]>([])
const classes = ref<any[]>([])
const students = ref<any[]>([])

// Filters
const filterClass = ref<string>('')
const filterStatus = ref<string>('')
const filterBkStatus = ref<string>('')
const searchQuery = ref<string>('')
const filterDateFrom = ref<string>('')
const filterDateTo = ref<string>('')

// Pagination
const currentPage = ref(1)
const lastPage = ref(1)
const totalItems = ref(0)

// Selected for Detail
const selectedObs = ref<any>(null)

// Form State (Create / Edit)
const isEditing = ref(false)
const editingId = ref<number | null>(null)
const submitting = ref(false)

const form = ref({
  student_id: '',
  tanggal: new Date().toISOString().split('T')[0],
  waktu: '',
  tempat: '',
  situasi_sebelum: '',
  perilaku_diamati: '',
  respons_lingkungan: '',
  catatan_wali_kelas: '',
  status_pemantauan: 'Baru',
})

// Feedback BK State
const feedbackObs = ref<any>(null)
const submittingFeedback = ref(false)
const feedbackForm = ref({
  rekomendasi_bk: '',
  saran_untuk_guru: '',
  masuk_pemantauan_bk: true,
  fokus_pemantauan: 'Perilaku',
  status_pemantauan: 'Dalam Pemantauan',
})

// Focus Categories for BK
const focusList = [
  'Perilaku',
  'Emosi/Regulasi Diri',
  'Sosial',
  'Belajar',
  'Kedisiplinan',
  'Kehadiran',
  'Perkembangan',
  'Pribadi',
  'Potensi/Bakat',
  'Lainnya',
]

// Computed Stats
const countTotal = computed(() => totalItems.value)
const countBelumDitanggapi = computed(() => {
  return observations.value.filter(o => !o.rekomendasi_bk).length
})
const countMasukBk = computed(() => {
  return observations.value.filter(o => o.is_escalated_to_bk).length
})

// Methods
const fetchClasses = async () => {
  try {
    const res = await api.get('/classes')
    if (res.data.success) {
      classes.value = res.data.data || []
    }
  } catch (err) {
    console.error('Gagal mengambil data rombel', err)
  }
}

const fetchStudents = async () => {
  try {
    const res = await api.get('/students?per_page=200')
    if (res.data.success) {
      students.value = res.data.data || []
    }
  } catch (err) {
    console.error('Gagal mengambil data siswa', err)
  }
}

const fetchObservations = async (page = 1) => {
  loading.value = true
  try {
    let url = `/observations?page=${page}`
    if (filterClass.value) url += `&class_id=${filterClass.value}`
    if (filterStatus.value) url += `&status_pemantauan=${encodeURIComponent(filterStatus.value)}`
    if (searchQuery.value) url += `&search=${encodeURIComponent(searchQuery.value)}`
    if (filterDateFrom.value) url += `&tanggal_dari=${filterDateFrom.value}`
    if (filterDateTo.value) url += `&tanggal_sampai=${filterDateTo.value}`

    const res = await api.get(url)
    if (res.data.success) {
      observations.value = res.data.data || []
      currentPage.value = res.data.meta?.current_page || 1
      lastPage.value = res.data.meta?.last_page || 1
      totalItems.value = res.data.meta?.total || 0
    }
  } catch (err) {
    console.error('Gagal mengambil catatan observasi', err)
  } finally {
    loading.value = false
  }
}

const openCreateForm = () => {
  isEditing.value = false
  editingId.value = null
  form.value = {
    student_id: '',
    tanggal: new Date().toISOString().split('T')[0],
    waktu: 'Jam Pelajaran',
    tempat: 'Ruang Kelas',
    situasi_sebelum: '',
    perilaku_diamati: '',
    respons_lingkungan: '',
    catatan_wali_kelas: '',
    status_pemantauan: 'Baru',
  }
  currentView.value = 'form'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const openEditForm = (obs: any) => {
  isEditing.value = true
  editingId.value = obs.id
  form.value = {
    student_id: obs.student_id ? String(obs.student_id) : '',
    tanggal: obs.tanggal ? obs.tanggal.substring(0, 10) : new Date().toISOString().split('T')[0],
    waktu: obs.waktu || '',
    tempat: obs.tempat || '',
    situasi_sebelum: obs.situasi_sebelum || '',
    perilaku_diamati: obs.perilaku_diamati || '',
    respons_lingkungan: obs.respons_lingkungan || '',
    catatan_wali_kelas: obs.catatan_wali_kelas || '',
    status_pemantauan: obs.status_pemantauan || 'Baru',
  }
  currentView.value = 'form'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const openDetailView = (obs: any) => {
  selectedObs.value = obs
  currentView.value = 'detail'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const backToList = () => {
  currentView.value = 'list'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const submitForm = async () => {
  if (!form.value.student_id) {
    showError('Validasi Gagal', null, 'Silakan pilih siswa yang diobservasi.')
    return
  }
  if (!form.value.perilaku_diamati) {
    showError('Validasi Gagal', null, 'Deskripsi perilaku yang diamati wajib diisi.')
    return
  }

  submitting.value = true
  try {
    if (isEditing.value && editingId.value) {
      const res = await api.put(`/observations/${editingId.value}`, form.value)
      if (res.data.success) {
        showSuccess('Berhasil!', 'Catatan observasi berhasil diperbarui.')
        currentView.value = 'list'
        fetchObservations(currentPage.value)
      }
    } else {
      const res = await api.post('/observations', form.value)
      if (res.data.success) {
        showSuccess('Berhasil Dilaporkan!', 'Laporan catatan observasi berhasil dikirimkan ke Guru BK.')
        currentView.value = 'list'
        fetchObservations(1)
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan', err, 'Terjadi kesalahan saat menyimpan catatan observasi.')
  } finally {
    submitting.value = false
  }
}

const openFeedbackForm = (obs: any) => {
  feedbackObs.value = obs
  feedbackForm.value = {
    rekomendasi_bk: obs.rekomendasi_bk || '',
    saran_untuk_guru: obs.saran_untuk_guru || '',
    masuk_pemantauan_bk: obs.is_escalated_to_bk ? true : false,
    fokus_pemantauan: 'Perilaku',
    status_pemantauan: obs.status_pemantauan || (obs.is_escalated_to_bk ? 'Dalam Pemantauan' : 'Selesai / Cukup di Kelas'),
  }
  currentView.value = 'feedback'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const submitFeedback = async () => {
  if (!feedbackForm.value.rekomendasi_bk) {
    showError('Validasi Gagal', null, 'Rekomendasi dari Guru BK wajib diisi.')
    return
  }

  submittingFeedback.value = true
  try {
    const res = await api.post(`/observations/${feedbackObs.value.id}/feedback-bk`, feedbackForm.value)
    if (res.data.success) {
      showSuccess('Tanggapan Tersimpan!', res.data.message)
      currentView.value = 'list'
      fetchObservations(currentPage.value)
    }
  } catch (err: any) {
    showError('Gagal Menyimpan Rekomendasi', err)
  } finally {
    submittingFeedback.value = false
  }
}

const deleteObservation = async (obs: any) => {
  const confirmed = await showConfirm(
    'Hapus Catatan Observasi?',
    'Data catatan observasi kejadian ini akan dihapus secara permanen.',
    'Ya, Hapus',
    'warning'
  )
  if (!confirmed) return

  try {
    const res = await api.delete(`/observations/${obs.id}`)
    if (res.data.success) {
      showSuccess('Berhasil Dihapus!', res.data.message)
      if (currentView.value === 'detail') {
        currentView.value = 'list'
      }
      fetchObservations(currentPage.value)
    }
  } catch (err: any) {
    showError('Gagal Menghapus', err)
  }
}

const getInitials = (name?: string) => {
  if (!name) return '?'
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase()
  return name.substring(0, 2).toUpperCase()
}

const getClassName = (item: any) => {
  if (!item) return '-'
  return item.school_class?.nama ||
         item.schoolClass?.nama ||
         item.student?.school_class?.nama ||
         item.student?.current_class?.nama ||
         item.student?.kelas?.nama ||
         item.student?.class_name ||
         item.class_name ||
         item.kelas?.nama ||
         '-'
}

onMounted(() => {
  fetchClasses()
  fetchStudents()
  fetchObservations()
})
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-200">

    <!-- ======================================================== -->
    <!-- VIEW 1: TABEL DATA CATATAN OBSERVASI (TABLE VIEW)        -->
    <!-- ======================================================== -->
    <template v-if="currentView === 'list'">
      <!-- Header Banner Card -->
      <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-sky-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20 shrink-0">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span v-if="auth.isGuruBk" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                Layanan Guru BK (Seluruh Laporan Guru)
              </span>
              <span v-else class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                Pencatatan &amp; Pelaporan Guru ke BK
              </span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-gray-900 leading-tight mt-1">
              {{ auth.isGuruBk ? 'Daftar Laporan Observasi Perilaku Siswa' : 'Catatan Observasi & Laporan Kejadian Siswa' }}
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              {{ auth.isGuruBk 
                ? 'Laporan kejadian faktual kelas dari para guru untuk ditelaah, ditanggapi, dan ditentukan apakah masuk ke Analisis & Monitoring BK.' 
                : 'Mencatat dan melaporkan kejadian perilaku faktual di kelas secara langsung kepada Guru Bimbingan Konseling (BK).' }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2.5 self-start md:self-auto">
          <button
            @click="openCreateForm"
            type="button"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-600/20 transition active:scale-95 cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>+ Catat Laporan Kejadian</span>
          </button>
        </div>
      </div>

      <!-- Quick Metrics Ribbon (Khusus Guru BK) -->
      <div v-if="auth.isGuruBk" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Laporan Masuk</span>
            <div class="text-xl font-black text-gray-900 mt-0.5">{{ countTotal }}</div>
          </div>
          <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
            📥
          </div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold text-amber-500 uppercase tracking-wider">Menunggu Tanggapan BK</span>
            <div class="text-xl font-black text-amber-600 mt-0.5">{{ countBelumDitanggapi }}</div>
          </div>
          <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
            ⏳
          </div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold text-rose-500 uppercase tracking-wider">Masuk Pemantauan BK</span>
            <div class="text-xl font-black text-rose-600 mt-0.5">{{ countMasukBk }}</div>
          </div>
          <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold">
            🎯
          </div>
        </div>
      </div>

      <!-- Filter & Search Toolbar -->
      <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2.5 flex-1 min-w-[280px]">
          <!-- Search Input -->
          <div class="relative flex-1 min-w-[200px]">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="searchQuery"
              @keyup.enter="fetchObservations(1)"
              type="text"
              placeholder="Cari siswa, perilaku, atau tempat..."
              class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
            />
          </div>

          <!-- Filter Kelas -->
          <select
            v-model="filterClass"
            @change="fetchObservations(1)"
            class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
          >
            <option value="">Semua Rombel / Kelas</option>
            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nama }}</option>
          </select>

          <!-- Filter Status Pemantauan -->
          <select
            v-model="filterStatus"
            @change="fetchObservations(1)"
            class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
          >
            <option value="">Semua Status</option>
            <option value="Baru">Baru</option>
            <option value="Dalam Pemantauan">Dalam Pemantauan</option>
            <option value="Selesai / Cukup di Kelas">Selesai / Cukup di Kelas</option>
            <option value="Menunjukkan Perkembangan">Menunjukkan Perkembangan</option>
          </select>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="fetchObservations(1)"
            type="button"
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition cursor-pointer"
          >
            Segarkan
          </button>
        </div>
      </div>

      <!-- MAIN DATA TABLE -->
      <div class="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
        <div v-if="loading" class="p-12 text-center text-slate-400">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-600 border-t-transparent mb-2"></div>
          <p class="text-xs font-medium">Memuat data observasi...</p>
        </div>

        <div v-else-if="observations.length === 0" class="p-12 text-center">
          <div class="w-16 h-16 mx-auto bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 mb-3">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <h3 class="text-sm font-bold text-gray-800">Belum Ada Catatan Observasi</h3>
          <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">
            {{ auth.isGuruBk 
              ? 'Belum ada laporan observasi perilaku yang dikirimkan oleh guru ke Guru BK.' 
              : 'Anda belum mencatat laporan observasi. Klik tombol "+ Catat Laporan Kejadian" di atas untuk melaporkan kejadian siswa ke Guru BK.' }}
          </p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/80 border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                <th class="py-3.5 px-4 w-12 text-center">No.</th>
                <th class="py-3.5 px-4 min-w-[140px]">Tanggal &amp; Lokasi</th>
                <th class="py-3.5 px-4 min-w-[200px]">Peserta Didik</th>
                <th v-if="auth.isGuruBk" class="py-3.5 px-4 min-w-[150px]">Guru Pelapor</th>
                <th class="py-3.5 px-4 min-w-[240px]">Kejadian Faktual (Perilaku)</th>
                <th class="py-3.5 px-4 min-w-[200px]">Tanggapan &amp; Status BK</th>
                <th class="py-3.5 px-4 text-center w-28">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr 
                v-for="(obs, idx) in observations" 
                :key="obs.id"
                class="hover:bg-indigo-50/20 transition-colors"
              >
                <!-- 1. Nomor -->
                <td class="py-3.5 px-4 text-center font-mono text-slate-400">
                  {{ (currentPage - 1) * 15 + idx + 1 }}
                </td>

                <!-- 2. Tanggal & Lokasi -->
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <div class="font-bold text-gray-900">{{ formatDateIndo(obs.tanggal) }}</div>
                  <div class="text-[11px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                    <span v-if="obs.waktu">⏰ {{ obs.waktu }}</span>
                    <span v-if="obs.tempat">📍 {{ obs.tempat }}</span>
                  </div>
                </td>

                <!-- 3. Siswa & Kelas -->
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
                      <img
                        v-if="obs.student?.foto_path"
                        :src="obs.student.foto_path.startsWith('http') ? obs.student.foto_path : `/storage/${obs.student.foto_path}`"
                        alt="Foto"
                        class="w-full h-full object-cover"
                      />
                      <span v-else class="text-[11px] font-bold text-slate-500 uppercase">
                        {{ getInitials(obs.student?.nama_lengkap) }}
                      </span>
                    </div>
                    <div>
                      <div class="font-bold text-gray-900 leading-snug">
                        {{ obs.student?.nama_lengkap || 'Siswa' }}
                      </div>
                      <div class="flex items-center gap-1.5 text-[10px] text-gray-400 mt-0.5">
                        <span class="px-1.5 py-0.5 rounded bg-slate-100 font-bold text-slate-700">
                          {{ getClassName(obs) }}
                        </span>
                        <span>NIS: {{ obs.student?.nis || '-' }}</span>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- 4. Guru Pelapor (Khusus Tampilan BK) -->
                <td v-if="auth.isGuruBk" class="py-3.5 px-4">
                  <div class="font-semibold text-gray-900">
                    {{ obs.observer?.nama_lengkap || 'Guru' }}
                  </div>
                  <div class="text-[10px] text-slate-400">
                    {{ obs.observer?.jabatan || 'Wali / Guru Tema' }}
                  </div>
                </td>

                <!-- 5. Kejadian Faktual -->
                <td class="py-3.5 px-4">
                  <div class="line-clamp-2 font-medium text-gray-900 text-xs">
                    {{ obs.perilaku_diamati }}
                  </div>
                  <div v-if="obs.situasi_sebelum" class="text-[10px] text-slate-400 mt-0.5 line-clamp-1 italic">
                    Sebelum: {{ obs.situasi_sebelum }}
                  </div>
                </td>

                <!-- 6. Tanggapan & Status BK -->
                <td class="py-3.5 px-4">
                  <!-- Kasus: Sudah Ditanggapi BK -->
                  <div v-if="obs.rekomendasi_bk" class="space-y-1">
                    <div class="flex items-center gap-1.5">
                      <span 
                        v-if="obs.is_escalated_to_bk" 
                        class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200 flex items-center gap-1"
                      >
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                        Masuk Pantauan BK
                      </span>
                      <span 
                        v-else 
                        class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-teal-50 text-teal-700 border border-teal-200"
                      >
                        ✓ Cukup di Kelas
                      </span>
                    </div>
                    <p class="text-[11px] text-slate-600 line-clamp-1 italic">
                      "{{ obs.rekomendasi_bk }}"
                    </p>
                    <div v-if="obs.bkTeacher" class="text-[9px] text-gray-400">
                      Oleh: {{ obs.bkTeacher.nama_lengkap }}
                    </div>
                  </div>

                  <!-- Kasus: Belum Ditanggapi BK -->
                  <div v-else>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 inline-flex items-center gap-1">
                      <span>⏳</span>
                      Menunggu Tanggapan BK
                    </span>
                  </div>
                </td>

                <!-- 7. Aksi -->
                <td class="py-3.5 px-4 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <!-- Tombol Detail -->
                    <button
                      @click="openDetailView(obs)"
                      type="button"
                      class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition cursor-pointer"
                      title="Lihat Detail Kejadian & Rekomendasi"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>

                    <!-- Tombol Beri Tanggapan (Khusus Guru BK) -->
                    <button
                      v-if="auth.isGuruBk"
                      @click="openFeedbackForm(obs)"
                      type="button"
                      class="p-1.5 rounded-lg text-rose-600 hover:text-rose-800 hover:bg-rose-50 transition cursor-pointer"
                      :title="obs.rekomendasi_bk ? 'Edit Tanggapan & Status BK' : 'Beri Tanggapan & Status BK'"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>

                    <!-- Tombol Edit Observasi (Guru Pelapor / Admin) -->
                    <button
                      @click="openEditForm(obs)"
                      type="button"
                      class="p-1.5 rounded-lg text-slate-400 hover:text-amber-600 hover:bg-amber-50 transition cursor-pointer"
                      title="Edit Kejadian Observasi"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                      </svg>
                    </button>

                    <!-- Tombol Hapus -->
                    <button
                      @click="deleteObservation(obs)"
                      type="button"
                      class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                      title="Hapus Catatan"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Bar -->
        <div v-if="lastPage > 1" class="flex items-center justify-between bg-slate-50/50 px-5 py-3 border-t border-slate-100">
          <span class="text-xs text-gray-500">
            Halaman {{ currentPage }} dari {{ lastPage }} (Total {{ totalItems }} laporan)
          </span>
          <div class="flex items-center gap-1.5">
            <button
              :disabled="currentPage <= 1"
              @click="fetchObservations(currentPage - 1)"
              type="button"
              class="px-3 py-1.5 text-xs font-bold rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 cursor-pointer"
            >
              Sebelumnya
            </button>
            <button
              :disabled="currentPage >= lastPage"
              @click="fetchObservations(currentPage + 1)"
              type="button"
              class="px-3 py-1.5 text-xs font-bold rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 cursor-pointer"
            >
              Berikutnya
            </button>
          </div>
        </div>
      </div>
    </template>

    <!-- ======================================================== -->
    <!-- VIEW 2: HALAMAN DETAIL CATATAN OBSERVASI (FULL PAGE)     -->
    <!-- ======================================================== -->
    <template v-else-if="currentView === 'detail' && selectedObs">
      <!-- Top Action Bar -->
      <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <button
            @click="backToList"
            type="button"
            class="w-12 h-12 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition cursor-pointer shrink-0"
            title="Kembali ke Tabel Observasi"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                Detail Catatan Observasi
              </span>
              <span class="text-xs text-gray-400">/ Tanggal {{ formatDateIndo(selectedObs.tanggal) }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-gray-900 leading-tight mt-1">
              {{ selectedObs.student?.nama_lengkap || 'Siswa' }}
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              Rombel: <strong class="text-gray-800">{{ getClassName(selectedObs) }}</strong> |
              NIS: {{ selectedObs.student?.nis }} |
              Pelapor: <strong>{{ selectedObs.observer?.nama_lengkap || 'Guru' }}</strong> ({{ selectedObs.observer?.jabatan || 'Wali Kelas' }})
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2 self-start md:self-auto">
          <button
            v-if="auth.isGuruBk"
            @click="openFeedbackForm(selectedObs)"
            type="button"
            class="inline-flex items-center gap-1.5 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-md transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            <span>{{ selectedObs.rekomendasi_bk ? 'Edit Tanggapan BK' : '+ Beri Tanggapan BK' }}</span>
          </button>
          <button
            @click="openEditForm(selectedObs)"
            type="button"
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
          >
            Edit Laporan
          </button>
          <button
            @click="backToList"
            type="button"
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
          >
            Kembali
          </button>
        </div>
      </div>

      <!-- Detail Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri: 3 Pilar ABC Faktual -->
        <div class="lg:col-span-2 space-y-5">
          <!-- ABC Pillars Card -->
          <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
              <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center font-bold text-xs">
                  ABC
                </span>
                <div>
                  <h3 class="text-sm font-bold text-gray-900">Uraian Faktual Observasi</h3>
                  <p class="text-[11px] text-gray-400">Deskripsi kronologis tanpa asumsi subjektif.</p>
                </div>
              </div>
              <div class="text-xs text-gray-500">
                <span>📍 {{ selectedObs.tempat || 'Ruang Kelas' }}</span>
                <span v-if="selectedObs.waktu" class="ml-2">⏰ {{ selectedObs.waktu }}</span>
              </div>
            </div>

            <!-- Antecedent -->
            <div class="p-4 bg-slate-50/70 rounded-2xl border border-slate-100 space-y-1">
              <div class="flex items-center gap-1.5 text-xs font-bold text-slate-600 uppercase tracking-wide">
                <span class="w-5 h-5 rounded-md bg-slate-200 text-slate-700 flex items-center justify-center text-[10px]">A</span>
                <span>Situasi Sebelum (Antecedent)</span>
              </div>
              <p class="text-xs text-gray-800 whitespace-pre-line leading-relaxed pl-6">
                {{ selectedObs.situasi_sebelum || '— (Tidak dicatat oleh pelapor)' }}
              </p>
            </div>

            <!-- Behavior -->
            <div class="p-4 bg-indigo-50/50 rounded-2xl border border-indigo-200/80 space-y-1">
              <div class="flex items-center gap-1.5 text-xs font-bold text-indigo-900 uppercase tracking-wide">
                <span class="w-5 h-5 rounded-md bg-indigo-200 text-indigo-900 flex items-center justify-center text-[10px]">B</span>
                <span>Perilaku yang Diamati (Behavior)</span>
              </div>
              <p class="text-xs text-gray-900 font-semibold whitespace-pre-line leading-relaxed pl-6">
                {{ selectedObs.perilaku_diamati }}
              </p>
            </div>

            <!-- Consequence -->
            <div class="p-4 bg-slate-50/70 rounded-2xl border border-slate-100 space-y-1">
              <div class="flex items-center gap-1.5 text-xs font-bold text-slate-600 uppercase tracking-wide">
                <span class="w-5 h-5 rounded-md bg-slate-200 text-slate-700 flex items-center justify-center text-[10px]">C</span>
                <span>Respons Siswa Lain / Lingkungan (Consequence)</span>
              </div>
              <p class="text-xs text-gray-800 whitespace-pre-line leading-relaxed pl-6">
                {{ selectedObs.respons_lingkungan || '— (Tidak dicatat oleh pelapor)' }}
              </p>
            </div>

            <!-- Catatan Refleksi Pelapor -->
            <div v-if="selectedObs.catatan_wali_kelas" class="p-4 bg-amber-50/30 rounded-2xl border border-amber-100 space-y-1">
              <span class="text-[10px] font-bold text-amber-800 uppercase tracking-wider block">
                Catatan Tambahan Guru Pelapor:
              </span>
              <p class="text-xs text-gray-800 italic">
                {{ selectedObs.catatan_wali_kelas }}
              </p>
            </div>
          </div>
        </div>

        <!-- Kolom Kanan: Tanggapan & Keputusan Guru BK -->
        <div class="space-y-5">
          <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
              <span class="w-8 h-8 rounded-xl bg-rose-50 text-rose-700 flex items-center justify-center font-bold text-xs">
                🧠
              </span>
              <div>
                <h3 class="text-sm font-bold text-rose-950">Tanggapan &amp; Keputusan BK</h3>
                <p class="text-[11px] text-gray-400">Asesmen dan tindak lanjut Bimbingan Konseling.</p>
              </div>
            </div>

            <!-- Status Keputusan Pemantauan BK -->
            <div>
              <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">
                Keputusan Masuk Pemantauan BK
              </span>
              <div v-if="selectedObs.is_escalated_to_bk" class="p-3 bg-rose-50 rounded-2xl border border-rose-200 space-y-1">
                <div class="flex items-center gap-1.5 font-bold text-xs text-rose-900">
                  <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                  <span>MASUK DAFTAR PEMANTAUAN BK</span>
                </div>
                <p class="text-[11px] text-rose-700">
                  Siswa telah dimasukkan ke dalam menu Analisis &amp; Monitoring BK untuk pemantauan berkala.
                </p>
              </div>
              <div v-else-if="selectedObs.rekomendasi_bk" class="p-3 bg-teal-50 rounded-2xl border border-teal-200 space-y-1">
                <div class="flex items-center gap-1.5 font-bold text-xs text-teal-900">
                  <span>✓</span>
                  <span>CUKUP PENANGANAN DI KELAS</span>
                </div>
                <p class="text-[11px] text-teal-700">
                  Tidak dimasukkan ke daftar Analisis &amp; Monitoring BK. Guru cukup mendampingi sesuai saran praktis BK.
                </p>
              </div>
              <div v-else class="p-3 bg-amber-50 rounded-2xl border border-amber-200 text-xs text-amber-800">
                ⏳ Belum ditanggapi oleh Guru Bimbingan Konseling.
              </div>
            </div>

            <!-- Rekomendasi BK -->
            <div v-if="selectedObs.rekomendasi_bk" class="space-y-1">
              <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">
                Telaah &amp; Rekomendasi BK
              </span>
              <div class="p-3 bg-slate-50 rounded-2xl text-xs text-gray-800 whitespace-pre-line leading-relaxed">
                {{ selectedObs.rekomendasi_bk }}
              </div>
            </div>

            <!-- Saran untuk Guru -->
            <div v-if="selectedObs.saran_untuk_guru" class="space-y-1">
              <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">
                Saran Praktis untuk Guru Kelas
              </span>
              <div class="p-3 bg-amber-50/50 border border-amber-100 rounded-2xl text-xs text-gray-800 whitespace-pre-line leading-relaxed">
                {{ selectedObs.saran_untuk_guru }}
              </div>
            </div>

            <div v-if="selectedObs.bkTeacher" class="pt-2 border-t border-gray-100 text-[11px] text-gray-400">
              Ditanggapi oleh: <strong class="text-gray-700">{{ selectedObs.bkTeacher.nama_lengkap }}</strong>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- ======================================================== -->
    <!-- VIEW 3: FORM CATAT / EDIT OBSERVASI (FULL PAGE)          -->
    <!-- ======================================================== -->
    <template v-else-if="currentView === 'form'">
      <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <button
            @click="backToList"
            type="button"
            class="w-12 h-12 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition cursor-pointer shrink-0"
            title="Kembali ke Tabel Observasi"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                {{ isEditing ? 'Edit Catatan Observasi' : 'Pencatatan Kejadian Baru' }}
              </span>
              <span class="text-xs text-gray-400">/ Format ABC Faktual</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-gray-900 leading-tight mt-1">
              {{ isEditing ? 'Edit Laporan Catatan Observasi Siswa' : 'Catat Laporan Observasi Perilaku Siswa ke Guru BK' }}
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              Dokumentasikan apa adanya tanpa asumsi subjektif agar Guru Bimbingan Konseling (BK) dapat memberikan telaah objektif.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2.5 self-start md:self-auto">
          <button
            @click="backToList"
            type="button"
            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
          >
            Batal &amp; Kembali
          </button>
          <button
            @click="submitForm"
            :disabled="submitting"
            type="button"
            class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-600/20 transition disabled:opacity-50 cursor-pointer"
          >
            <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>{{ submitting ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Kirim Laporan ke BK') }}</span>
          </button>
        </div>
      </div>

      <!-- Main Form Container -->
      <form @submit.prevent="submitForm" class="space-y-6">
        <!-- Section 1: Siswa & Waktu -->
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-5">
          <div class="flex items-center gap-2.5 pb-3 border-b border-gray-100">
            <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
              1
            </span>
            <div>
              <h3 class="text-sm font-bold text-gray-900">Identitas Siswa &amp; Tempat Kejadian</h3>
              <p class="text-[11px] text-gray-400">Pilih subjek pengamatan serta waktu dan area terjadinya perilaku.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="lg:col-span-2">
              <label class="block text-xs font-bold text-gray-700 mb-1.5">
                Nama Siswa yang Diobservasi <span class="text-rose-500">*</span>
              </label>
              <select
                v-model="form.student_id"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                required
              >
                <option value="" disabled>-- Pilih Siswa --</option>
                <option v-for="s in students" :key="s.id" :value="s.id">
                  {{ s.nama_lengkap }} (NIS: {{ s.nis }}) - {{ getClassName(s) }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1.5">
                Tanggal Observasi <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.tanggal"
                type="date"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                required
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1.5">
                Waktu / Jam Kejadian
              </label>
              <input
                v-model="form.waktu"
                type="text"
                placeholder="Mis: Jam Lentera, 09.30"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
              />
            </div>

            <div class="lg:col-span-4">
              <label class="block text-xs font-bold text-gray-700 mb-1.5">
                Tempat / Lokasi Spesifik
              </label>
              <input
                v-model="form.tempat"
                type="text"
                placeholder="Mis: Ruang Kelas 1, Pojok Baca, Kolong Meja, Lapangan Olahraga"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
              />
            </div>
          </div>
        </div>

        <!-- Section 2: Tiga Pilar Pendekatan ABC -->
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-6">
          <div class="flex items-center gap-2.5 pb-3 border-b border-gray-100">
            <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
              2
            </span>
            <div>
              <h3 class="text-sm font-bold text-gray-900">Uraian Faktual Observasi (Antecedent - Behavior - Consequence)</h3>
              <p class="text-[11px] text-gray-400">Deskripsikan kejadian apa adanya tanpa praduga agar mudah ditindaklanjuti Guru BK.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- 1. Antecedent -->
            <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-2.5 flex flex-col justify-between">
              <div>
                <div class="flex items-center gap-2 text-xs font-bold text-slate-700 uppercase tracking-wide">
                  <span class="w-6 h-6 rounded-lg bg-slate-200 text-slate-700 flex items-center justify-center text-[10px]">A</span>
                  <span>Situasi Sebelum (Antecedent)</span>
                </div>
                <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                  Aktivitas apa yang sedang dilakukan anak atau lingkungan sebelum perilaku muncul?
                </p>
              </div>
              <textarea
                v-model="form.situasi_sebelum"
                rows="5"
                placeholder="Contoh: Sedang mengerjakan modul di meja kelompok, tiba-tiba pensilnya terjatuh..."
                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
              ></textarea>
            </div>

            <!-- 2. Behavior -->
            <div class="p-5 rounded-2xl bg-indigo-50/50 border border-indigo-200/80 space-y-2.5 flex flex-col justify-between">
              <div>
                <div class="flex items-center gap-2 text-xs font-bold text-indigo-900 uppercase tracking-wide">
                  <span class="w-6 h-6 rounded-lg bg-indigo-200 text-indigo-900 flex items-center justify-center text-[10px]">B</span>
                  <span>Perilaku yang Diamati (Behavior) *</span>
                </div>
                <p class="text-[11px] text-indigo-600 mt-1 leading-relaxed">
                  Tindakan atau ucapan spesifik yang dilakukan anak secara objektif.
                </p>
              </div>
              <textarea
                v-model="form.perilaku_diamati"
                rows="5"
                placeholder="Contoh: Berteriak keras, bersembunyi di bawah meja, atau menolak mengikuti instruksi..."
                class="w-full px-3.5 py-2.5 bg-white border border-indigo-300 rounded-xl text-xs text-gray-900 font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                required
              ></textarea>
            </div>

            <!-- 3. Consequence -->
            <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-2.5 flex flex-col justify-between">
              <div>
                <div class="flex items-center gap-2 text-xs font-bold text-slate-700 uppercase tracking-wide">
                  <span class="w-6 h-6 rounded-lg bg-slate-200 text-slate-700 flex items-center justify-center text-[10px]">C</span>
                  <span>Respons Lingkungan (Consequence)</span>
                </div>
                <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                  Reaksi teman sekelas serta respon langsung guru yang mendampingi di kelas.
                </p>
              </div>
              <textarea
                v-model="form.respons_lingkungan"
                rows="5"
                placeholder="Contoh: Teman sekelas menjauh, guru mendekati dan mengajak istirahat di ruang tenang..."
                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
              ></textarea>
            </div>
          </div>
        </div>

        <!-- Section 3: Catatan Tambahan Guru Kelas -->
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
          <div class="flex items-center gap-2.5 pb-3 border-b border-gray-100">
            <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
              3
            </span>
            <div>
              <h3 class="text-sm font-bold text-gray-900">Catatan Khusus Pelapor (Opsional)</h3>
              <p class="text-[11px] text-gray-400">Tambahkan catatan khusus atau pola yang dirasa penting bagi Guru BK.</p>
            </div>
          </div>

          <textarea
            v-model="form.catatan_wali_kelas"
            rows="3"
            placeholder="Tuliskan catatan tambahan jika ada..."
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
          ></textarea>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-2">
          <button
            @click="backToList"
            type="button"
            class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
          >
            Batal
          </button>
          <button
            :disabled="submitting"
            type="submit"
            class="inline-flex items-center gap-2 px-7 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-600/20 transition disabled:opacity-50 cursor-pointer"
          >
            <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>{{ submitting ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Kirim Laporan ke Guru BK') }}</span>
          </button>
        </div>
      </form>
    </template>

    <!-- ========================================================================= -->
    <!-- VIEW 4: FORM TANGGAPAN & STATUS GURU BK (KHUSUS GURU BK - FULL PAGE)      -->
    <!-- ========================================================================= -->
    <template v-else-if="currentView === 'feedback'">
      <!-- Header -->
      <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <button
            @click="backToList"
            type="button"
            class="w-12 h-12 rounded-2xl bg-rose-50 hover:bg-rose-100 text-rose-700 flex items-center justify-center transition cursor-pointer shrink-0"
            title="Kembali ke Tabel Observasi"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                Menu Khusus Guru Bimbingan Konseling (BK)
              </span>
              <span class="text-xs text-gray-400">/ Tanggapan &amp; Keputusan Pemantauan</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-rose-950 leading-tight mt-1">
              Lembar Tanggapan &amp; Status Kasus BK
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              Memberikan asesmen profesional untuk siswa: <strong class="text-rose-700 font-bold">{{ feedbackObs?.student?.nama_lengkap }}</strong>
              (Pelapor: {{ feedbackObs?.observer?.nama_lengkap }})
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2.5 self-start md:self-auto">
          <button
            @click="backToList"
            type="button"
            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
          >
            Batal &amp; Kembali
          </button>
          <button
            @click="submitFeedback"
            :disabled="submittingFeedback"
            type="button"
            class="inline-flex items-center gap-2 px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-md shadow-rose-600/20 transition disabled:opacity-50 cursor-pointer"
          >
            <svg v-if="submittingFeedback" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>{{ submittingFeedback ? 'Menyimpan...' : 'Simpan Tanggapan BK' }}</span>
          </button>
        </div>
      </div>

      <!-- Ringkasan Kejadian Faktual Pelapor -->
      <div class="bg-gradient-to-br from-slate-50 to-indigo-50/30 p-6 rounded-3xl border border-indigo-100/70 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-indigo-100">
          <div class="flex items-center gap-2">
            <span class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs">
              📋
            </span>
            <div>
              <h3 class="text-xs font-bold text-indigo-950 uppercase tracking-wider">
                Laporan Faktual Guru Pelapor
              </h3>
              <p class="text-[11px] text-gray-500">
                Oleh: <strong>{{ feedbackObs?.observer?.nama_lengkap }}</strong> pada {{ formatDateIndo(feedbackObs?.tanggal) }} ({{ feedbackObs?.tempat || 'Kelas' }})
              </p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
          <div class="bg-white p-4 rounded-2xl border border-slate-200/80">
            <span class="font-bold text-slate-500 uppercase tracking-wider text-[10px] block mb-1">
              1. Antecedent (Situasi Sebelum)
            </span>
            <p class="text-gray-700 whitespace-pre-line leading-relaxed">
              {{ feedbackObs?.situasi_sebelum || '— (Tidak dicatat)' }}
            </p>
          </div>

          <div class="bg-indigo-50/50 p-4 rounded-2xl border border-indigo-200">
            <span class="font-bold text-indigo-700 uppercase tracking-wider text-[10px] block mb-1">
              2. Behavior (Perilaku Faktual)
            </span>
            <p class="text-gray-900 font-semibold whitespace-pre-line leading-relaxed">
              {{ feedbackObs?.perilaku_diamati }}
            </p>
          </div>

          <div class="bg-white p-4 rounded-2xl border border-slate-200/80">
            <span class="font-bold text-slate-500 uppercase tracking-wider text-[10px] block mb-1">
              3. Consequence (Respons Lingkungan)
            </span>
            <p class="text-gray-700 whitespace-pre-line leading-relaxed">
              {{ feedbackObs?.respons_lingkungan || '— (Tidak dicatat)' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Main Feedback Form -->
      <form @submit.prevent="submitFeedback" class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-6">
        <!-- Keputusan Pemantauan BK (Radio Toggle) -->
        <div class="p-5 rounded-2xl bg-rose-50/40 border border-rose-200/80 space-y-3">
          <div>
            <label class="block text-xs font-black text-rose-950 uppercase tracking-wide">
              Keputusan: Apakah Kasus Ini Masuk ke Analisis &amp; Monitoring BK? *
            </label>
            <p class="text-[11px] text-rose-700 mt-0.5">
              Tentukan apakah data ini perlu disimpan dan dipantau berkala dalam sistem Analisis &amp; Monitoring BK, atau cukup diselesaikan di tingkat kelas.
            </p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
            <!-- Option 1: Masuk BK -->
            <label 
              class="flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition"
              :class="feedbackForm.masuk_pemantauan_bk ? 'bg-white border-rose-500 ring-2 ring-rose-500/20 shadow-xs' : 'bg-white/60 border-slate-200 hover:bg-white'"
            >
              <input
                v-model="feedbackForm.masuk_pemantauan_bk"
                :value="true"
                type="radio"
                name="masuk_pemantauan_bk"
                class="mt-1 text-rose-600 focus:ring-rose-500 cursor-pointer"
              />
              <div>
                <span class="block text-xs font-bold text-gray-900">
                  🎯 Ya, Masukkan ke Pemantauan &amp; Analisis BK
                </span>
                <span class="block text-[11px] text-gray-500 mt-0.5">
                  Data siswa otomatis tersimpan di menu Analisis &amp; Monitoring BK untuk pendampingan konseling bertahap.
                </span>
              </div>
            </label>

            <!-- Option 2: Cukup di Kelas -->
            <label 
              class="flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition"
              :class="!feedbackForm.masuk_pemantauan_bk ? 'bg-white border-teal-600 ring-2 ring-teal-500/20 shadow-xs' : 'bg-white/60 border-slate-200 hover:bg-white'"
            >
              <input
                v-model="feedbackForm.masuk_pemantauan_bk"
                :value="false"
                type="radio"
                name="masuk_pemantauan_bk"
                class="mt-1 text-teal-600 focus:ring-teal-500 cursor-pointer"
              />
              <div>
                <span class="block text-xs font-bold text-gray-900">
                  ✓ Tidak, Cukup Penanganan di Kelas
                </span>
                <span class="block text-[11px] text-gray-500 mt-0.5">
                  Data TIDAK disimpan di Analisis &amp; Monitoring BK. Guru kelas cukup menerapkan saran tindak lanjut BK.
                </span>
              </div>
            </label>
          </div>

          <!-- Jika Masuk BK: Pilih Fokus Pemantauan -->
          <div v-if="feedbackForm.masuk_pemantauan_bk" class="pt-2">
            <label class="block text-xs font-bold text-rose-900 mb-1">
              Fokus Pemantauan BK *
            </label>
            <select
              v-model="feedbackForm.fokus_pemantauan"
              class="w-full sm:w-72 px-3 py-2 bg-white border border-rose-300 rounded-xl text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-rose-500/20"
            >
              <option v-for="f in focusList" :key="f" :value="f">{{ f }}</option>
            </select>
          </div>
        </div>

        <!-- Uraian Rekomendasi BK -->
        <div>
          <label class="block text-xs font-bold text-gray-800 mb-1.5">
            Rekomendasi / Analisis Psikologis Guru BK <span class="text-rose-500">*</span>
          </label>
          <p class="text-[11px] text-gray-400 mb-2">
            Telaah objektif mengenai motif perilaku, dinamika regulasi emosi, atau pemicu respon anak.
          </p>
          <textarea
            v-model="feedbackForm.rekomendasi_bk"
            rows="4"
            placeholder="Tuliskan telaah profesional dan analisis Guru BK..."
            class="w-full px-3.5 py-2.5 bg-rose-50/20 border border-rose-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
            required
          ></textarea>
        </div>

        <!-- Saran Guru Kelas -->
        <div>
          <label class="block text-xs font-bold text-gray-800 mb-1.5">
            Saran Praktis untuk Guru Kelas / Guru Pelapor
          </label>
          <p class="text-[11px] text-gray-400 mb-2">
            Langkah konkret yang disarankan untuk diterapkan di kelas saat menghadapi situasi serupa.
          </p>
          <textarea
            v-model="feedbackForm.saran_untuk_guru"
            rows="4"
            placeholder="Tuliskan arahan praktis bagi guru pendamping..."
            class="w-full px-3.5 py-2.5 bg-amber-50/20 border border-amber-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
          ></textarea>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
          <button
            @click="backToList"
            type="button"
            class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
          >
            Batal
          </button>
          <button
            :disabled="submittingFeedback"
            type="submit"
            class="inline-flex items-center gap-2 px-7 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-md shadow-rose-600/20 transition disabled:opacity-50 cursor-pointer"
          >
            <svg v-if="submittingFeedback" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>{{ submittingFeedback ? 'Menyimpan...' : 'Simpan Tanggapan BK' }}</span>
          </button>
        </div>
      </form>
    </template>

  </div>
</template>
