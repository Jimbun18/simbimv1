<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const route = useRoute()
const activeTab = ref<'progress' | 'activities' | 'announcements'>('progress')
const loading = ref(false)
const message = ref<{ type: 'success' | 'error'; text: string } | null>(null)

// References
const classes = ref<any[]>([])
const students = ref<any[]>([])
const selectedClass = ref('')

// Progress State
const progressList = ref<any[]>([])
const selectedCategory = ref('semua')
const showProgressModal = ref(false)
const progressForm = ref({
  student_id: '',
  kategori: 'tahfidz',
  capaian: '',
  nilai: 'A',
  catatan: '',
  tanggal: new Date().toISOString().split('T')[0]
})

// Activities State
const activitiesList = ref<any[]>([])
const showActivityModal = ref(false)
const activityForm = ref({
  class_id: '',
  judul: '',
  deskripsi: '',
  kategori: 'kbm',
  tanggal: new Date().toISOString().split('T')[0]
})

// Announcements State
const announcementsList = ref<any[]>([])
const showAnnouncementModal = ref(false)
const announcementForm = ref({
  judul: '',
  konten: '',
  kategori: 'akademik',
  target_role: 'semua',
  is_pinned: false
})

const showMessage = (text: string, type: 'success' | 'error' = 'success') => {
  message.value = { type, text }
  setTimeout(() => (message.value = null), 4000)
}

const isMyHomeroom = (classId: number | string) => {
  return auth.homeroomClasses.some(h => String(h.id) === String(classId))
}

const selectMyHomeroom = () => {
  if (auth.primaryHomeroomClass) {
    selectedClass.value = String(auth.primaryHomeroomClass.id)
    loadStudents()
    fetchProgress()
  }
}

const selectAllClasses = () => {
  selectedClass.value = ''
  students.value = []
  fetchProgress()
}

const openActivityModal = () => {
  if (!activityForm.value.class_id) {
    if (selectedClass.value) {
      activityForm.value.class_id = selectedClass.value
    } else if (auth.isWaliKelas && auth.primaryHomeroomClass) {
      activityForm.value.class_id = String(auth.primaryHomeroomClass.id)
    }
  }
  showActivityModal.value = true
}

const fetchClassesAndStudents = async () => {
  try {
    const cRes = await api.get('/classes')
    classes.value = cRes.data.data || []
    if (auth.isWaliKelas && auth.primaryHomeroomClass) {
      selectedClass.value = String(auth.primaryHomeroomClass.id)
    } else if (classes.value.length > 0) {
      selectedClass.value = String(classes.value[0].id)
    }
    if (selectedClass.value) {
      loadStudents()
    }
  } catch (err) {
    console.error('Failed to load classes', err)
  }
}

const loadStudents = async () => {
  if (!selectedClass.value) return
  try {
    const res = await api.get('/students', { params: { class_id: selectedClass.value, limit: 100 } })
    students.value = res.data.data?.data || res.data.data || []
    if (students.value.length > 0) {
      progressForm.value.student_id = students.value[0].id
    }
  } catch (err) {
    console.error('Failed to load students', err)
  }
}

const fetchProgress = async () => {
  loading.value = true
  try {
    const params: any = {}
    if (selectedCategory.value !== 'semua') params.kategori = selectedCategory.value
    if (selectedClass.value) params.class_id = selectedClass.value

    const res = await api.get('/progress', { params })
    progressList.value = res.data.data || []
  } catch (err) {
    console.error('Failed to load progress', err)
  } finally {
    loading.value = false
  }
}

const submitProgress = async () => {
  try {
    await api.post('/progress', progressForm.value)
    showMessage('Jurnal progres belajar siswa berhasil disimpan!')
    showProgressModal.value = false
    fetchProgress()
  } catch (err: any) {
    showMessage(err.response?.data?.message || 'Gagal menyimpan progres', 'error')
  }
}

const fetchActivities = async () => {
  loading.value = true
  try {
    const res = await api.get('/activities')
    activitiesList.value = res.data.data?.data || res.data.data || []
  } catch (err) {
    console.error('Failed to load activities', err)
  } finally {
    loading.value = false
  }
}

const submitActivity = async () => {
  try {
    await api.post('/activities', activityForm.value)
    showMessage('Kegiatan harian berhasil dipublikasikan!')
    showActivityModal.value = false
    fetchActivities()
  } catch (err: any) {
    showMessage(err.response?.data?.message || 'Gagal menyimpan kegiatan', 'error')
  }
}

const fetchAnnouncements = async () => {
  loading.value = true
  try {
    const res = await api.get('/announcements')
    announcementsList.value = res.data.data?.data || res.data.data || []
  } catch (err) {
    console.error('Failed to load announcements', err)
  } finally {
    loading.value = false
  }
}

const submitAnnouncement = async () => {
  try {
    await api.post('/announcements', announcementForm.value)
    showMessage('Pengumuman sekolah berhasil diterbitkan!')
    showAnnouncementModal.value = false
    fetchAnnouncements()
  } catch (err: any) {
    showMessage(err.response?.data?.message || 'Gagal menerbitkan pengumuman', 'error')
  }
}

watch(() => route.query.tab, (newTab) => {
  if (newTab && ['progress', 'activities', 'announcements'].includes(newTab as string)) {
    if (activeTab.value !== newTab) {
      activeTab.value = newTab as any
      if (newTab === 'progress') fetchProgress()
      else if (newTab === 'activities') fetchActivities()
      else if (newTab === 'announcements') fetchAnnouncements()
    }
  }
})

onMounted(() => {
  if (route.query.tab && ['progress', 'activities', 'announcements'].includes(route.query.tab as string)) {
    activeTab.value = route.query.tab as any
  }
  fetchClassesAndStudents()
  if (activeTab.value === 'progress') fetchProgress()
  else if (activeTab.value === 'activities') fetchActivities()
  else if (activeTab.value === 'announcements') fetchAnnouncements()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Progres Siswa, Kegiatan & Pengumuman</h1>
        <p class="text-sm text-gray-500 mt-1">Pantau capaian hafalan Al-Qur'an, pembiasaan ibadah, kegiatan harian, dan informasi resmi sekolah.</p>
      </div>
      <div>
        <button 
          v-if="activeTab === 'progress'"
          @click="showProgressModal = true"
          class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm px-4 py-2.5 rounded-xl shadow-md transition flex items-center gap-2"
        >
          <span>Input Jurnal Tahfidz / Karakter</span>
        </button>
        <button 
          v-else-if="activeTab === 'activities'"
          @click="openActivityModal"
          class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm px-4 py-2.5 rounded-xl shadow-md transition flex items-center gap-2"
        >
          <span>Tambah Kegiatan Harian</span>
        </button>
        <button 
          v-else-if="activeTab === 'announcements' && auth.role !== 'wali'"
          @click="showAnnouncementModal = true"
          class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm px-4 py-2.5 rounded-xl shadow-md transition flex items-center gap-2"
        >
          <span>Buat Pengumuman Baru</span>
        </button>
      </div>
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

    <!-- TAB 1: PROGRESS (Tahfidz, Sholat, Karakter) -->
    <div v-if="activeTab === 'progress'" class="space-y-4">
      <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-wrap gap-4 items-center justify-between">
        <div class="flex flex-wrap items-center gap-3">
          <!-- Shortcut Cepat Kelas Binaan untuk Wali Kelas / Kepsek -->
          <div v-if="auth.isWaliKelas" class="flex flex-wrap items-center gap-1.5 bg-emerald-50/80 border border-emerald-200/80 p-1 rounded-xl">
            <button
              type="button"
              @click="selectMyHomeroom"
              :class="selectedClass === String(auth.primaryHomeroomClass?.id) ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-white text-emerald-800 hover:bg-emerald-100 font-medium'"
              class="px-2.5 py-1 rounded-lg text-xs flex items-center gap-1 transition"
              title="Fokus ke kelas binaan sendiri"
            >
              <span>⭐ Binaan: {{ auth.primaryHomeroomClass?.nama }}</span>
            </button>
            <button
              type="button"
              @click="selectAllClasses"
              :class="!selectedClass ? 'bg-slate-800 text-white font-bold shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 font-medium'"
              class="px-2.5 py-1 rounded-lg text-xs flex items-center gap-1 transition"
              title="Lihat seluruh kelas di sekolah"
            >
              <span>Semua Rombel</span>
            </button>
          </div>

          <div>
            <label class="text-xs font-semibold text-gray-500 mr-2">Filter Kelas:</label>
            <select 
              v-model="selectedClass" 
              @change="loadStudents(); fetchProgress()"
              class="border border-gray-200 rounded-xl px-3 py-1.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            >
              <option value="">Semua Kelas</option>
              <option v-for="c in classes" :key="c.id" :value="String(c.id)">
                {{ c.nama || c.nama_kelas }} {{ isMyHomeroom(c.id) ? ' ⭐ (Binaan Saya)' : '' }}
              </option>
            </select>
          </div>
          <div>
            <label class="text-xs font-semibold text-gray-500 mr-2">Kategori:</label>
            <select 
              v-model="selectedCategory" 
              @change="fetchProgress"
              class="border border-gray-200 rounded-xl px-3 py-1.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            >
              <option value="semua">Semua Kategori</option>
              <option value="tahfidz">Tahfidz & Qur'an</option>
              <option value="sholat">Sholat & Ibadah</option>
              <option value="karakter">Karakter & Akhlak</option>
              <option value="akademik">Akademik</option>
              <option value="ekskul">Ekstrakurikuler</option>
            </select>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[700px]">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 font-semibold">
                <th class="p-3">Tanggal</th>
                <th class="p-3">Nama Siswa</th>
                <th class="p-3">Kelas</th>
                <th class="p-3">Kategori</th>
                <th class="p-3">Capaian / Target</th>
                <th class="p-3 text-center">Predikat / Nilai</th>
                <th class="p-3">Catatan Guru</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="loading">
                <td colspan="7" class="text-center py-8 text-gray-400">Memuat jurnal progres siswa...</td>
              </tr>
              <tr v-else-if="progressList.length === 0">
                <td colspan="7" class="text-center py-8 text-gray-400">Belum ada data jurnal progres.</td>
              </tr>
            <tr v-for="p in progressList" :key="p.id" class="hover:bg-gray-50/70">
              <td class="p-3 font-mono text-xs text-gray-500 whitespace-nowrap">{{ p.tanggal }}</td>
              <td class="p-3 font-semibold text-gray-800">{{ p.student?.nama_lengkap || 'Siswa' }}</td>
              <td class="p-3 text-xs text-gray-600">{{ p.school_class?.nama || p.student?.class?.nama_kelas || '-' }}</td>
              <td class="p-3">
                <span 
                  class="px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize"
                  :class="{
                    'bg-emerald-100 text-emerald-800': p.kategori === 'tahfidz',
                    'bg-blue-100 text-blue-800': p.kategori === 'sholat',
                    'bg-purple-100 text-purple-800': p.kategori === 'karakter',
                    'bg-amber-100 text-amber-800': p.kategori === 'akademik',
                    'bg-gray-100 text-gray-800': p.kategori === 'ekskul'
                  }"
                >
                  {{ p.kategori }}
                </span>
              </td>
              <td class="p-3 font-medium text-gray-700">{{ p.capaian }}</td>
              <td class="p-3 text-center">
                <span class="inline-block px-2 py-0.5 rounded font-bold text-xs bg-emerald-50 text-emerald-700 border border-emerald-200">
                  {{ p.nilai || 'Baik' }}
                </span>
              </td>
              <td class="p-3 text-xs text-gray-500 max-w-xs truncate">{{ p.catatan || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

    <!-- TAB 2: ACTIVITIES -->
    <div v-if="activeTab === 'activities'" class="space-y-4">
      <div v-if="loading" class="text-center py-12 text-gray-400">Memuat kegiatan harian...</div>
      <div v-else-if="activitiesList.length === 0" class="text-center py-12 text-gray-400 bg-white rounded-2xl border border-dashed border-gray-200">
        Belum ada kegiatan harian yang tercatat.
      </div>
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="act in activitiesList" :key="act.id" class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm space-y-3 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between text-xs text-gray-400 mb-2">
              <span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded font-medium capitalize">{{ act.kategori }}</span>
              <span>{{ act.tanggal }}</span>
            </div>
            <h3 class="font-bold text-gray-800 text-base">{{ act.judul }}</h3>
            <p class="text-xs text-gray-600 mt-2 line-clamp-3 leading-relaxed">{{ act.deskripsi }}</p>
          </div>
          <div class="pt-3 border-t border-gray-100 text-xs text-gray-400 flex justify-between">
            <span>Kelas: {{ act.school_class?.nama || 'Seluruh Sekolah' }}</span>
            <span>Oleh: {{ act.teacher?.nama_lengkap || 'Guru' }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 3: ANNOUNCEMENTS -->
    <div v-if="activeTab === 'announcements'" class="space-y-4">
      <div v-if="loading" class="text-center py-12 text-gray-400">Memuat pengumuman...</div>
      <div v-else-if="announcementsList.length === 0" class="text-center py-12 text-gray-400 bg-white rounded-2xl border border-dashed border-gray-200">
        Belum ada pengumuman yang aktif.
      </div>
      <div v-else class="space-y-4">
        <div 
          v-for="anc in announcementsList" 
          :key="anc.id"
          class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm transition hover:shadow-md space-y-3"
          :class="anc.is_pinned ? 'border-l-4 border-l-amber-500' : ''"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span v-if="anc.is_pinned" class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded">SEMATAN</span>
              <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded uppercase">{{ anc.target_role }}</span>
              <span class="text-xs text-gray-400">{{ anc.tgl_terbit }}</span>
            </div>
            <span class="text-xs text-gray-400">Oleh: {{ anc.creator?.nama || 'Admin SDI BIM' }}</span>
          </div>
          <h3 class="text-lg font-bold text-gray-800">{{ anc.judul }}</h3>
          <div class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ anc.konten }}</div>
        </div>
      </div>
    </div>

    <!-- MODAL INPUT JURNAL TAHFIDZ/PROGRES -->
    <div v-if="showProgressModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-3 sm:p-4">
      <div class="bg-white rounded-3xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-5 sm:p-6 shadow-2xl space-y-4">
        <div class="flex justify-between items-center border-b pb-3">
          <h3 class="font-bold text-gray-800 text-base sm:text-lg">Input Jurnal Capaian Siswa</h3>
          <button @click="showProgressModal = false" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
        <form @submit.prevent="submitProgress" class="space-y-4 text-sm">
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Pilih Siswa</label>
            <select v-model="progressForm.student_id" required class="w-full border rounded-xl p-2.5">
              <option v-for="s in students" :key="s.id" :value="s.id">{{ s.nama_lengkap }} ({{ s.nisn || s.nis }})</option>
            </select>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-gray-700 mb-1">Kategori</label>
              <select v-model="progressForm.kategori" class="w-full border rounded-xl p-2.5">
                <option value="tahfidz">Tahfidz & Qur'an</option>
                <option value="sholat">Sholat & Ibadah</option>
                <option value="karakter">Karakter Islami</option>
                <option value="akademik">Akademik</option>
                <option value="ekskul">Ekstrakurikuler</option>
              </select>
            </div>
            <div>
              <label class="block font-semibold text-gray-700 mb-1">Predikat / Nilai</label>
              <select v-model="progressForm.nilai" class="w-full border rounded-xl p-2.5">
                <option value="Mumtaz (A+)">Mumtaz (A+)</option>
                <option value="Jayyid Jiddan (A)">Jayyid Jiddan (A)</option>
                <option value="Jayyid (B)">Jayyid (B)</option>
                <option value="Maqbul (C)">Maqbul (C)</option>
              </select>
            </div>
          </div>
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Capaian / Materi</label>
            <input 
              v-model="progressForm.capaian" 
              type="text" 
              placeholder="Contoh: Surat An-Naba' Ayat 1-20 (Mutqin)" 
              required 
              class="w-full border rounded-xl p-2.5" 
            />
          </div>
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Catatan / Evaluasi Pembimbing</label>
            <textarea 
              v-model="progressForm.catatan" 
              rows="2" 
              placeholder="Catatan makhraj, tajwid, atau ketekunan..." 
              class="w-full border rounded-xl p-2.5"
            ></textarea>
          </div>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showProgressModal = false" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-xl">Batal</button>
            <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md">Simpan Jurnal</button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL ACTIVITY -->
    <div v-if="showActivityModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-3 sm:p-4">
      <div class="bg-white rounded-3xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-5 sm:p-6 shadow-2xl space-y-4">
        <div class="flex justify-between items-center border-b pb-3">
          <h3 class="font-bold text-gray-800 text-base sm:text-lg">Tambah Kegiatan Harian</h3>
          <button @click="showActivityModal = false" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
        <form @submit.prevent="submitActivity" class="space-y-4 text-sm">
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Judul Kegiatan</label>
            <input v-model="activityForm.judul" type="text" required placeholder="Contoh: Belajar Tajwid & Praktek Sholat Dhuha" class="w-full border rounded-xl p-2.5" />
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-gray-700 mb-1">Kelas (Opsional)</label>
              <select v-model="activityForm.class_id" class="w-full border rounded-xl p-2.5">
                <option value="">Semua / Seluruh Sekolah</option>
                <option v-for="c in classes" :key="c.id" :value="String(c.id)">
                  {{ c.nama || c.nama_kelas }} {{ isMyHomeroom(c.id) ? ' ⭐ (Binaan Saya)' : '' }}
                </option>
              </select>
            </div>
            <div>
              <label class="block font-semibold text-gray-700 mb-1">Kategori</label>
              <select v-model="activityForm.kategori" class="w-full border rounded-xl p-2.5">
                <option value="kbm">KBM / Belajar Mengajar</option>
                <option value="ibadah">Ibadah Bersama</option>
                <option value="ekskul">Ekstrakurikuler</option>
                <option value="outing">Outing / Kunjungan</option>
                <option value="umum">Lainnya / Umum</option>
              </select>
            </div>
          </div>
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Tanggal</label>
            <input v-model="activityForm.tanggal" type="date" required class="w-full border rounded-xl p-2.5" />
          </div>
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Deskripsi Kegiatan</label>
            <textarea v-model="activityForm.deskripsi" rows="3" required placeholder="Jelaskan ringkasan jalannya kegiatan..." class="w-full border rounded-xl p-2.5"></textarea>
          </div>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showActivityModal = false" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-xl">Batal</button>
            <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md">Publikasikan</button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL ANNOUNCEMENT -->
    <div v-if="showAnnouncementModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-3 sm:p-4">
      <div class="bg-white rounded-3xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-5 sm:p-6 shadow-2xl space-y-4">
        <div class="flex justify-between items-center border-b pb-3">
          <h3 class="font-bold text-gray-800 text-base sm:text-lg">Buat Pengumuman Baru</h3>
          <button @click="showAnnouncementModal = false" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
        <form @submit.prevent="submitAnnouncement" class="space-y-4 text-sm">
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Judul Pengumuman</label>
            <input v-model="announcementForm.judul" type="text" required class="w-full border rounded-xl p-2.5" />
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-gray-700 mb-1">Target Pembaca</label>
              <select v-model="announcementForm.target_role" class="w-full border rounded-xl p-2.5">
                <option value="semua">Semua (Guru, Staf, & Wali)</option>
                <option value="wali_murid">Khusus Wali Murid</option>
                <option value="guru">Khusus Dewan Guru</option>
              </select>
            </div>
            <div>
              <label class="block font-semibold text-gray-700 mb-1">Kategori</label>
              <select v-model="announcementForm.kategori" class="w-full border rounded-xl p-2.5">
                <option value="akademik">Akademik</option>
                <option value="kegiatan">Kegiatan</option>
                <option value="keuangan">Keuangan / SPP</option>
                <option value="umum">Umum</option>
              </select>
            </div>
          </div>
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Isi Pengumuman</label>
            <textarea v-model="announcementForm.konten" rows="4" required class="w-full border rounded-xl p-2.5"></textarea>
          </div>
          <div class="flex items-center gap-2">
            <input type="checkbox" v-model="announcementForm.is_pinned" id="pinned" class="rounded text-emerald-600" />
            <label for="pinned" class="font-semibold text-gray-700">Sematkan di bagian atas (Pinned)</label>
          </div>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showAnnouncementModal = false" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-xl">Batal</button>
            <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md">Terbitkan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
