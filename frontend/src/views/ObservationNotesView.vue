<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
import { showSuccess, showError, showConfirm } from '../services/swal'
import { formatDateIndo } from '../utils/date'

const auth = useAuthStore()

// Navigation View State: 'list' | 'form' | 'feedback'
const currentView = ref<'list' | 'form' | 'feedback'>('list')

// State
const loading = ref(false)
const observations = ref<any[]>([])
const classes = ref<any[]>([])
const students = ref<any[]>([])

// Filters
const filterClass = ref<string>('')
const filterStatus = ref<string>('')
const searchQuery = ref<string>('')
const filterDateFrom = ref<string>('')
const filterDateTo = ref<string>('')

// Pagination
const currentPage = ref(1)
const lastPage = ref(1)
const totalItems = ref(0)

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
  status_pemantauan: 'Dalam Pemantauan',
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
    const res = await api.get('/students?per_page=150')
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
    if (filterStatus.value) url += `&status_pemantauan=${filterStatus.value}`
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
        showSuccess('Berhasil!', 'Catatan observasi perilaku siswa berhasil disimpan.')
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
    status_pemantauan: obs.status_pemantauan || 'Dalam Pemantauan',
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
      showSuccess('Berhasil!', 'Rekomendasi Guru BK berhasil disimpan.')
      currentView.value = 'list'
      fetchObservations(currentPage.value)
    }
  } catch (err: any) {
    showError('Gagal Menyimpan Rekomendasi', err)
  } finally {
    submittingFeedback.value = false
  }
}

const escalateToBk = async (obs: any) => {
  const confirmed = await showConfirm(
    'Rujuk ke Kasus BK?',
    `Siswa ${obs.student?.nama_lengkap} akan dimasukkan ke dalam Sistem Analisis & Monitoring BK untuk pendampingan berkala.`,
    'Ya, Rujuk ke BK',
    'question'
  )
  if (!confirmed) return

  try {
    const res = await api.post(`/observations/${obs.id}/escalate-to-bk`, {
      fokus_pemantauan: 'Perilaku',
      sumber_awal: obs.observer?.jabatan || 'Wali Kelas',
    })
    if (res.data.success) {
      showSuccess('Berhasil Dirujuk!', res.data.message)
      fetchObservations(currentPage.value)
    }
  } catch (err: any) {
    showError('Gagal Merujuk ke BK', err)
  }
}

const deleteObservation = async (obs: any) => {
  const confirmed = await showConfirm(
    'Hapus Catatan Observasi?',
    'Data catatan observasi ini akan dihapus secara permanen.',
    'Ya, Hapus',
    'warning'
  )
  if (!confirmed) return

  try {
    const res = await api.delete(`/observations/${obs.id}`)
    if (res.data.success) {
      showSuccess('Berhasil Dihapus!', res.data.message)
      fetchObservations(currentPage.value)
    }
  } catch (err: any) {
    showError('Gagal Menghapus', err)
  }
}

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'Baru':
      return 'bg-blue-50 text-blue-700 border-blue-200'
    case 'Dalam Pemantauan':
      return 'bg-amber-50 text-amber-700 border-amber-200'
    case 'Menunjukkan Perkembangan':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200'
    case 'Sesuai Harapan':
      return 'bg-teal-50 text-teal-700 border-teal-200'
    case 'Selesai':
      return 'bg-slate-100 text-slate-700 border-slate-200'
    default:
      return 'bg-purple-50 text-purple-700 border-purple-200'
  }
}

const getInitials = (name?: string) => {
  if (!name) return '?'
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase()
  return name.substring(0, 2).toUpperCase()
}

onMounted(() => {
  fetchClasses()
  fetchStudents()
  fetchObservations()
})
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-200">

    <!-- ========================================== -->
    <!-- VIEW 1: DAFTAR OBSERVASI (LIST VIEW)        -->
    <!-- ========================================== -->
    <template v-if="currentView === 'list'">
      <!-- Header Card -->
      <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-sky-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20 shrink-0">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                Pencatatan Guru Kelas & Tema
              </span>
              <span v-if="auth.isGuruBk" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                Mode Guru BK Aktif
              </span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-gray-900 leading-tight mt-1">
              Catatan Observasi Perilaku Siswa
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              Format observasi faktual harian (Antecedent - Behavior - Consequence) untuk kolaborasi guru kelas & konsultasi bimbingan konseling.
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
            <span>Catat Observasi Baru</span>
          </button>
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
              placeholder="Cari siswa atau perilaku..."
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

          <!-- Filter Status -->
          <select
            v-model="filterStatus"
            @change="fetchObservations(1)"
            class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
          >
            <option value="">Semua Status</option>
            <option value="Baru">Baru</option>
            <option value="Dalam Pemantauan">Dalam Pemantauan</option>
            <option value="Menunjukkan Perkembangan">Menunjukkan Perkembangan</option>
            <option value="Sesuai Harapan">Sesuai Harapan</option>
            <option value="Selesai">Selesai</option>
          </select>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="fetchObservations(1)"
            type="button"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition cursor-pointer"
          >
            Terapkan Filter
          </button>
        </div>
      </div>

      <!-- Observations List -->
      <div v-if="loading" class="p-12 text-center text-slate-400 bg-white rounded-3xl border border-gray-100 shadow-xs">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-600 border-t-transparent mb-2"></div>
        <p class="text-xs font-medium">Memuat catatan observasi...</p>
      </div>

      <div v-else-if="observations.length === 0" class="p-12 text-center bg-white rounded-3xl border border-gray-100 shadow-xs">
        <div class="w-16 h-16 mx-auto bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 mb-3">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <h3 class="text-sm font-bold text-gray-800">Belum Ada Catatan Observasi</h3>
        <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">
          Gunakan tombol "Catat Observasi Baru" untuk mulai mendokumentasikan pengamatan perilaku siswa di kelas.
        </p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="obs in observations"
          :key="obs.id"
          class="bg-white p-5 sm:p-6 rounded-3xl border border-gray-100 shadow-xs hover:border-indigo-200 transition space-y-4"
        >
          <!-- Card Top Bar: Siswa Info & Status -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100">
            <div class="flex items-center gap-3.5">
              <div class="w-11 h-11 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
                <img
                  v-if="obs.student?.foto_path"
                  :src="obs.student.foto_path.startsWith('http') ? obs.student.foto_path : `/storage/${obs.student.foto_path}`"
                  alt="Foto"
                  class="w-full h-full object-cover"
                />
                <span v-else class="text-xs font-bold text-slate-400 uppercase">
                  {{ getInitials(obs.student?.nama_lengkap) }}
                </span>
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-sm font-bold text-gray-900 leading-snug">
                    {{ obs.student?.nama_lengkap || 'Siswa' }}
                  </h3>
                  <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-100 text-slate-700">
                    {{ obs.schoolClass?.nama || 'Kelas' }}
                  </span>
                  <span v-if="obs.is_escalated_to_bk" class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-rose-50 text-rose-700 border border-rose-200 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                    Tercatat di BK
                  </span>
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-400 mt-0.5">
                  <span>🗓️ {{ formatDateIndo(obs.tanggal) }}</span>
                  <span v-if="obs.waktu">⏰ {{ obs.waktu }}</span>
                  <span v-if="obs.tempat">📍 {{ obs.tempat }}</span>
                  <span v-if="obs.observer">👤 Pengamat: <strong class="text-gray-600">{{ obs.observer.nama_lengkap }}</strong></span>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2 self-end sm:self-center">
              <span class="px-3 py-1 rounded-full text-xs font-bold border" :class="getStatusBadge(obs.status_pemantauan)">
                {{ obs.status_pemantauan }}
              </span>
            </div>
          </div>

          <!-- Observasi ABC Grid (Antecedent, Behavior, Consequence) -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <!-- Antecedent -->
            <div class="p-3.5 bg-slate-50/70 rounded-2xl border border-slate-100 space-y-1">
              <div class="flex items-center gap-1.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                <span>1. Situasi Sebelum (Antecedent)</span>
              </div>
              <p class="text-xs text-gray-700 whitespace-pre-line leading-relaxed">
                {{ obs.situasi_sebelum || '— (Tidak dicatat)' }}
              </p>
            </div>

            <!-- Behavior -->
            <div class="p-3.5 bg-indigo-50/40 rounded-2xl border border-indigo-100/70 space-y-1">
              <div class="flex items-center gap-1.5 text-[11px] font-bold text-indigo-700 uppercase tracking-wider">
                <span>2. Perilaku yang Diamati (Behavior)</span>
              </div>
              <p class="text-xs text-gray-900 font-medium whitespace-pre-line leading-relaxed">
                {{ obs.perilaku_diamati }}
              </p>
            </div>

            <!-- Consequence -->
            <div class="p-3.5 bg-slate-50/70 rounded-2xl border border-slate-100 space-y-1">
              <div class="flex items-center gap-1.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                <span>3. Respons Lingkungan (Consequence)</span>
              </div>
              <p class="text-xs text-gray-700 whitespace-pre-line leading-relaxed">
                {{ obs.respons_lingkungan || '— (Tidak dicatat)' }}
              </p>
            </div>
          </div>

          <!-- Kolom Feedback / Rekomendasi Guru BK -->
          <div v-if="obs.rekomendasi_bk || obs.saran_untuk_guru" class="p-4 bg-gradient-to-r from-rose-50/50 via-amber-50/30 to-indigo-50/30 rounded-2xl border border-rose-200/60 space-y-2">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="p-1 rounded-lg bg-rose-100 text-rose-700">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </span>
                <h4 class="text-xs font-bold text-rose-900">
                  Umpan Balik Guru Bimbingan Konseling (BK)
                </h4>
              </div>
              <span v-if="obs.bkTeacher" class="text-[10px] text-gray-500 font-semibold">
                Oleh: {{ obs.bkTeacher.nama_lengkap }}
              </span>
            </div>

            <div v-if="obs.rekomendasi_bk" class="space-y-0.5">
              <span class="text-[10px] font-bold text-rose-800 uppercase tracking-wider">Rekomendasi BK:</span>
              <p class="text-xs text-gray-800 italic bg-white/70 p-2.5 rounded-xl border border-rose-100">
                "{{ obs.rekomendasi_bk }}"
              </p>
            </div>

            <div v-if="obs.saran_untuk_guru" class="space-y-0.5">
              <span class="text-[10px] font-bold text-amber-800 uppercase tracking-wider">Saran untuk Wali Kelas / Guru Tema:</span>
              <p class="text-xs text-gray-800 bg-white/70 p-2.5 rounded-xl border border-amber-100">
                {{ obs.saran_untuk_guru }}
              </p>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-gray-100">
            <div class="flex items-center gap-2">
              <!-- Tombol Beri Rekomendasi BK (Hanya Guru BK) -->
              <button
                v-if="auth.isGuruBk"
                @click="openFeedbackForm(obs)"
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold rounded-xl border border-rose-200 transition cursor-pointer"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                <span>{{ obs.rekomendasi_bk ? 'Perbarui Rekomendasi BK' : '+ Beri Rekomendasi BK' }}</span>
              </button>

              <!-- Tombol Eskalasi ke Kasus BK -->
              <button
                v-if="!obs.is_escalated_to_bk && (auth.isGuruBk || auth.isAdmin)"
                @click="escalateToBk(obs)"
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-xl border border-indigo-200 transition cursor-pointer"
                title="Masukkan siswa ini ke Sistem Pemantauan BK"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
                <span>Rujuk ke Kasus BK</span>
              </button>
            </div>

            <div class="flex items-center gap-1.5">
              <button
                @click="openEditForm(obs)"
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition cursor-pointer border border-slate-200"
                title="Edit Catatan Observasi"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                <span>Edit</span>
              </button>
              <button
                @click="deleteObservation(obs)"
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition cursor-pointer border border-slate-200"
                title="Hapus Catatan Observasi"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>Hapus</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="lastPage > 1" class="flex items-center justify-between bg-white px-5 py-3.5 rounded-2xl border border-gray-100 shadow-xs">
        <span class="text-xs text-gray-500">
          Menampilkan halaman {{ currentPage }} dari {{ lastPage }} (Total {{ totalItems }} catatan)
        </span>
        <div class="flex items-center gap-1.5">
          <button
            :disabled="currentPage <= 1"
            @click="fetchObservations(currentPage - 1)"
            type="button"
            class="px-3 py-1.5 text-xs font-bold rounded-lg border border-slate-200 disabled:opacity-40 cursor-pointer"
          >
            Sebelumnya
          </button>
          <button
            :disabled="currentPage >= lastPage"
            @click="fetchObservations(currentPage + 1)"
            type="button"
            class="px-3 py-1.5 text-xs font-bold rounded-lg border border-slate-200 disabled:opacity-40 cursor-pointer"
          >
            Berikutnya
          </button>
        </div>
      </div>
    </template>

    <!-- ======================================================== -->
    <!-- VIEW 2: HALAMAN FORM OBSERVASI PERILAKU (BUKAN POPUP)     -->
    <!-- ======================================================== -->
    <template v-else-if="currentView === 'form'">
      <!-- Top Navigation & Action Header -->
      <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <button
            @click="backToList"
            type="button"
            class="w-12 h-12 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition cursor-pointer shrink-0"
            title="Kembali ke Daftar Observasi"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                {{ isEditing ? 'Mode Edit Observasi' : 'Pencatatan Baru' }}
              </span>
              <span class="text-xs text-gray-400">/ Format ABC Faktual</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-gray-900 leading-tight mt-1">
              {{ isEditing ? 'Edit Catatan Observasi Siswa' : 'Tambah Catatan Observasi Perilaku Siswa' }}
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              Dokumentasikan pengamatan faktual dan netral tanpa asumsi subjektif untuk membantu asesmen guru dan bimbingan konseling.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2.5 self-start md:self-auto">
          <button
            @click="backToList"
            type="button"
            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
          >
            Batal & Kembali
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
            <span>{{ submitting ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Catatan Observasi') }}</span>
          </button>
        </div>
      </div>

      <!-- Main Form Container -->
      <form @submit.prevent="submitForm" class="space-y-6">
        <!-- Section 1: Identitas & Waktu -->
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-5">
          <div class="flex items-center gap-2.5 pb-3 border-b border-gray-100">
            <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
              1
            </span>
            <div>
              <h3 class="text-sm font-bold text-gray-900">Identitas Siswa & Lokasi Kejadian</h3>
              <p class="text-[11px] text-gray-400">Pilih subjek pengamatan serta waktu dan area terjadinya perilaku.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Siswa -->
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
                  {{ s.nama_lengkap }} (NIS: {{ s.nis }}) - {{ s.kelas?.nama || 'Tanpa Kelas' }}
                </option>
              </select>
            </div>

            <!-- Tanggal -->
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

            <!-- Waktu / Jam -->
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

            <!-- Tempat -->
            <div class="lg:col-span-2">
              <label class="block text-xs font-bold text-gray-700 mb-1.5">
                Tempat / Lokasi Spesifik
              </label>
              <input
                v-model="form.tempat"
                type="text"
                placeholder="Mis: Ruang Kelas 1, Pojok Baca, Kolong Meja, Lapangan"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
              />
            </div>

            <!-- Status Pemantauan -->
            <div class="lg:col-span-2">
              <label class="block text-xs font-bold text-gray-700 mb-1.5">
                Status Pemantauan
              </label>
              <select
                v-model="form.status_pemantauan"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
              >
                <option value="Baru">Baru</option>
                <option value="Dalam Pemantauan">Dalam Pemantauan</option>
                <option value="Menunjukkan Perkembangan">Menunjukkan Perkembangan</option>
                <option value="Sesuai Harapan">Sesuai Harapan</option>
                <option value="Selesai">Selesai</option>
              </select>
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
              <p class="text-[11px] text-gray-400">Deskripsikan kejadian apa adanya tanpa praduga agar mudah ditindaklanjuti.</p>
            </div>
          </div>

          <!-- Grid 3 Pilar ABC -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- 1. Antecedent -->
            <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-2.5 flex flex-col justify-between">
              <div>
                <div class="flex items-center gap-2 text-xs font-bold text-slate-700 uppercase tracking-wide">
                  <span class="w-6 h-6 rounded-lg bg-slate-200 text-slate-700 flex items-center justify-center text-[10px]">A</span>
                  <span>Situasi Sebelum (Antecedent)</span>
                </div>
                <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                  Apa pemicu atau aktivitas yang sedang dilakukan anak dan lingkungan sebelum perilaku muncul?
                </p>
              </div>
              <textarea
                v-model="form.situasi_sebelum"
                rows="5"
                placeholder="Contoh: Sedang mengerjakan modul matematika di meja, kemudian pensilnya terjatuh atau teman menyenggol..."
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
                  Tindakan atau ucapan spesifik yang dilakukan anak secara objektif dan terukur.
                </p>
              </div>
              <textarea
                v-model="form.perilaku_diamati"
                rows="5"
                placeholder="Contoh: Berteriak keras, meremas kertas, atau bersembunyi di bawah meja selama 10 menit..."
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
                  Bagaimana reaksi teman di sekitarnya, serta respon langsung guru yang mendampingi?
                </p>
              </div>
              <textarea
                v-model="form.respons_lingkungan"
                rows="5"
                placeholder="Contoh: Teman sekelas menjauh, guru mendekati dan mengajak menarik napas panjang di pojok tenang..."
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
              <h3 class="text-sm font-bold text-gray-900">Catatan Refleksi Guru Kelas (Opsional)</h3>
              <p class="text-[11px] text-gray-400">Tambahkan catatan khusus, pola yang mungkin berulang, atau hal yang perlu diwaspadai.</p>
            </div>
          </div>

          <textarea
            v-model="form.catatan_wali_kelas"
            rows="3"
            placeholder="Tuliskan catatan tambahan guru jika ada..."
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
          ></textarea>
        </div>

        <!-- Bottom Actions -->
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
            <span>{{ submitting ? 'Menyimpan Data...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Catatan Observasi') }}</span>
          </button>
        </div>
      </form>
    </template>

    <!-- ========================================================================= -->
    <!-- VIEW 3: HALAMAN FORM REKOMENDASI GURU BK (KHUSUS GURU BK - BUKAN POPUP)   -->
    <!-- ========================================================================= -->
    <template v-else-if="currentView === 'feedback'">
      <!-- Header Action -->
      <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <button
            @click="backToList"
            type="button"
            class="w-12 h-12 rounded-2xl bg-rose-50 hover:bg-rose-100 text-rose-700 flex items-center justify-center transition cursor-pointer shrink-0"
            title="Kembali ke Daftar Observasi"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                Layanan Bimbingan Konseling (BK)
              </span>
              <span class="text-xs text-gray-400">/ Umpan Balik Profesional</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-rose-950 leading-tight mt-1">
              Lembar Rekomendasi Guru Bimbingan Konseling
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              Memberikan telaah psikologis, arahan penanganan kelas, dan rekomendasi bagi siswa:
              <strong class="text-rose-700 font-bold">{{ feedbackObs?.student?.nama_lengkap }}</strong>
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2.5 self-start md:self-auto">
          <button
            @click="backToList"
            type="button"
            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
          >
            Batal & Kembali
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
            <span>{{ submittingFeedback ? 'Menyimpan...' : 'Simpan Rekomendasi BK' }}</span>
          </button>
        </div>
      </div>

      <!-- Ringkasan Kejadian yang Dilaporkan Guru -->
      <div class="bg-gradient-to-br from-slate-50 to-indigo-50/30 p-6 rounded-3xl border border-indigo-100/70 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-indigo-100">
          <div class="flex items-center gap-2">
            <span class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs">
              📋
            </span>
            <div>
              <h3 class="text-xs font-bold text-indigo-950 uppercase tracking-wider">
                Fakta Observasi Guru Pelapor
              </h3>
              <p class="text-[11px] text-gray-500">
                Dilaporkan oleh: <strong>{{ feedbackObs?.observer?.nama_lengkap }}</strong> pada {{ formatDateIndo(feedbackObs?.tanggal) }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2 text-xs">
            <span class="px-2.5 py-1 bg-white rounded-lg border border-slate-200 font-semibold text-slate-700">
              📍 {{ feedbackObs?.tempat || 'Ruang Kelas' }}
            </span>
            <span v-if="feedbackObs?.waktu" class="px-2.5 py-1 bg-white rounded-lg border border-slate-200 font-semibold text-slate-700">
              ⏰ {{ feedbackObs?.waktu }}
            </span>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
          <div class="bg-white p-4 rounded-2xl border border-slate-200/80">
            <span class="font-bold text-slate-500 uppercase tracking-wider text-[10px] block mb-1">
              Antecedent (Situasi Sebelum)
            </span>
            <p class="text-gray-700 whitespace-pre-line leading-relaxed">
              {{ feedbackObs?.situasi_sebelum || '— (Tidak dicatat)' }}
            </p>
          </div>

          <div class="bg-indigo-50/50 p-4 rounded-2xl border border-indigo-200">
            <span class="font-bold text-indigo-700 uppercase tracking-wider text-[10px] block mb-1">
              Behavior (Perilaku Faktual)
            </span>
            <p class="text-gray-900 font-semibold whitespace-pre-line leading-relaxed">
              {{ feedbackObs?.perilaku_diamati }}
            </p>
          </div>

          <div class="bg-white p-4 rounded-2xl border border-slate-200/80">
            <span class="font-bold text-slate-500 uppercase tracking-wider text-[10px] block mb-1">
              Consequence (Respons Lingkungan)
            </span>
            <p class="text-gray-700 whitespace-pre-line leading-relaxed">
              {{ feedbackObs?.respons_lingkungan || '— (Tidak dicatat)' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Main Feedback Form -->
      <form @submit.prevent="submitFeedback" class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs space-y-5">
        <div class="flex items-center gap-2.5 pb-3 border-b border-gray-100">
          <span class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-xs">
            🧠
          </span>
          <div>
            <h3 class="text-sm font-bold text-gray-900">Uraian Rekomendasi & Arahan Guru BK</h3>
            <p class="text-[11px] text-gray-400">Tuliskan analisis profesional, pendekatan psikologis, dan arahan praktis bagi guru pendamping.</p>
          </div>
        </div>

        <!-- Rekomendasi BK -->
        <div>
          <label class="block text-xs font-bold text-gray-800 mb-1.5">
            Rekomendasi / Analisis Psikologis BK <span class="text-rose-500">*</span>
          </label>
          <p class="text-[11px] text-gray-400 mb-2">
            Misal: Perlu observasi frekuensi kontak fisik, sediakan sensory break saat transisi, bangun kesepakatan personal di pagi hari.
          </p>
          <textarea
            v-model="feedbackForm.rekomendasi_bk"
            rows="4"
            placeholder="Tuliskan telaah profesional dan rekomendasi Guru BK..."
            class="w-full px-3.5 py-2.5 bg-rose-50/20 border border-rose-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
            required
          ></textarea>
        </div>

        <!-- Saran Guru Kelas -->
        <div>
          <label class="block text-xs font-bold text-gray-800 mb-1.5">
            Saran Praktis untuk Guru Kelas / Guru Tema
          </label>
          <p class="text-[11px] text-gray-400 mb-2">
            Langkah konkret yang dapat diterapkan guru di kelas ketika perilaku serupa muncul.
          </p>
          <textarea
            v-model="feedbackForm.saran_untuk_guru"
            rows="4"
            placeholder="Tuliskan arahan praktis untuk guru di ruang kelas..."
            class="w-full px-3.5 py-2.5 bg-amber-50/20 border border-amber-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
          ></textarea>
        </div>

        <!-- Update Status Pemantauan -->
        <div class="max-w-xs">
          <label class="block text-xs font-bold text-gray-800 mb-1.5">
            Perbarui Status Pemantauan
          </label>
          <select
            v-model="feedbackForm.status_pemantauan"
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
          >
            <option value="Dalam Pemantauan">Dalam Pemantauan</option>
            <option value="Menunjukkan Perkembangan">Menunjukkan Perkembangan</option>
            <option value="Sesuai Harapan">Sesuai Harapan</option>
            <option value="Selesai">Selesai</option>
          </select>
        </div>

        <!-- Action Buttons -->
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
            <span>{{ submittingFeedback ? 'Menyimpan...' : 'Simpan Rekomendasi BK' }}</span>
          </button>
        </div>
      </form>
    </template>

  </div>
</template>
