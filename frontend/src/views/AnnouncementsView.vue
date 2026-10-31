<template>
  <div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span class="p-2 rounded-xl bg-emerald-50 text-emerald-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
            </svg>
          </span>
          <h1 class="text-2xl font-black text-slate-900 tracking-tight">Papan Pengumuman Resmi</h1>
        </div>
        <p class="text-xs sm:text-sm text-slate-500 font-medium">
          Pusat informasi, instruksi pimpinan, edaran kegiatan, dan kalender penting SDI Bina Insan Mandiri.
        </p>
      </div>

      <!-- Tombol Tambah (Khusus Kepala Sekolah & Staf Admin) -->
      <button 
        v-if="auth.isAdmin"
        @click="openCreateModal"
        class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm px-5 py-2.5 rounded-2xl shadow-md shadow-emerald-600/20 transition-all flex items-center gap-2 cursor-pointer shrink-0 active:scale-95"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Buat Pengumuman Baru</span>
      </button>
    </div>

    <!-- Alert Notifikasi Feedback -->
    <div 
      v-if="message" 
      :class="message.type === 'success' ? 'bg-emerald-50 text-emerald-900 border-emerald-200' : 'bg-rose-50 text-rose-900 border-rose-200'"
      class="p-4 rounded-2xl border text-sm flex items-center justify-between shadow-xs transition-all"
    >
      <div class="flex items-center gap-2.5 font-medium">
        <svg v-if="message.type === 'success'" class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        <svg v-else class="w-5 h-5 text-rose-600" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <span>{{ message.text }}</span>
      </div>
      <button @click="message = null" class="text-slate-400 hover:text-slate-600">&times;</button>
    </div>

    <!-- Filter & Pencarian -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-wrap items-center justify-between gap-3 text-sm">
      <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
        <!-- Search Box -->
        <div class="relative w-full sm:w-64">
          <input 
            v-model="searchQuery" 
            @input="filterAnnouncements"
            type="text" 
            placeholder="Cari pengumuman..." 
            class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-xs text-slate-800"
          />
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>

        <!-- Filter Target Role -->
        <select 
          v-model="filterTarget" 
          @change="filterAnnouncements"
          class="border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 text-slate-700 outline-none"
        >
          <option value="">Semua Target Pembaca</option>
          <option value="semua">Target: Semua</option>
          <option value="guru">Target: Khusus Guru</option>
          <option value="wali">Target: Khusus Wali</option>
        </select>

        <!-- Filter Kategori -->
        <select 
          v-model="filterCategory" 
          @change="filterAnnouncements"
          class="border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 text-slate-700 outline-none"
        >
          <option value="">Semua Kategori</option>
          <option value="akademik">Akademik</option>
          <option value="kegiatan">Kegiatan</option>
          <option value="keuangan">Keuangan / SPP</option>
          <option value="libur">Hari Libur</option>
          <option value="umum">Umum</option>
        </select>
      </div>

      <div class="text-xs text-slate-400 font-semibold">
        Total: {{ filteredList.length }} Pengumuman
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-16 text-slate-400 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
      <svg class="w-8 h-8 animate-spin mx-auto text-emerald-600 mb-2" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <span>Memuat data pengumuman...</span>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredList.length === 0" class="text-center py-16 px-4 bg-white rounded-3xl border border-dashed border-slate-300 shadow-xs">
      <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-3">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
        </svg>
      </div>
      <h3 class="text-base font-bold text-slate-800">Belum Ada Pengumuman</h3>
      <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
        Tidak ada pengumuman yang sesuai dengan filter pencarian Anda saat ini.
      </p>
    </div>

    <!-- Daftar Pengumuman Cards -->
    <div v-else class="space-y-4">
      <div 
        v-for="anc in filteredList" 
        :key="anc.id"
        class="bg-white p-6 rounded-3xl border border-slate-200/90 shadow-xs hover:shadow-md transition-all relative overflow-hidden"
        :class="anc.is_pinned ? 'border-l-4 border-l-amber-500 bg-amber-50/15' : ''"
      >
        <!-- Top Metadata Bar -->
        <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100">
          <div class="flex flex-wrap items-center gap-2">
            <!-- Pinned Badge -->
            <span v-if="anc.is_pinned" class="bg-amber-100 text-amber-900 border border-amber-300 text-[10px] font-black px-2.5 py-0.5 rounded-full flex items-center gap-1">
              📌 DISEMATKAN
            </span>
            <!-- Kategori Badge -->
            <span 
              :class="getCategoryBadgeClass(anc.kategori)"
              class="text-[10px] font-bold px-2.5 py-0.5 rounded-full capitalize"
            >
              {{ anc.kategori || 'Umum' }}
            </span>
            <!-- Target Role Badge -->
            <span class="bg-slate-100 text-slate-700 border border-slate-200 text-[10px] font-extrabold px-2 py-0.5 rounded-full uppercase">
              Target: {{ formatTargetRole(anc.target_role) }}
            </span>
          </div>

          <div class="flex items-center gap-3 text-xs text-slate-400">
            <span>📅 {{ formatDate(anc.tgl_terbit) }}</span>
            <span>• Oleh: <strong class="text-slate-600">{{ anc.creator?.nama || 'Kepala Sekolah / Admin' }}</strong></span>
            
            <!-- Tombol Aksi Admin (Edit & Hapus) -->
            <div v-if="auth.isAdmin" class="flex items-center gap-1 ml-2">
              <button 
                @click="openEditModal(anc)"
                class="p-1.5 text-slate-400 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition"
                title="Edit Pengumuman"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button 
                @click="deleteAnnouncement(anc)"
                class="p-1.5 text-slate-400 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition"
                title="Hapus Pengumuman"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Judul & Isi -->
        <div class="mt-4 space-y-2.5">
          <h3 class="text-lg font-black text-slate-900 leading-snug">
            {{ anc.judul }}
          </h3>
          <div class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line bg-slate-50/60 p-4 rounded-2xl border border-slate-100">
            {{ anc.isi || anc.konten }}
          </div>
        </div>

        <!-- Lampiran Dokumen File (Jika Ada) -->
        <div v-if="anc.lampiran_path" class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2 text-xs font-semibold text-emerald-800">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
            </svg>
            <span>Berkas Lampiran Pengumuman</span>
          </div>
          <a 
            :href="getStorageUrl(anc.lampiran_path)" 
            target="_blank" 
            class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-bold rounded-xl transition flex items-center gap-1.5"
          >
            <span>Unduh Berkas</span>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
          </a>
        </div>
      </div>
    </div>

    <!-- MODAL FORM BUAT / EDIT PENGUMUMAN (Admin Only) -->
    <div 
      v-if="showModal && auth.isAdmin" 
      class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-3 sm:p-4 animate-fade-in"
    >
      <div class="bg-white rounded-3xl max-w-xl w-full max-h-[92vh] overflow-y-auto p-6 sm:p-7 shadow-2xl border border-slate-200 space-y-5">
        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2">
            <span class="p-2 bg-emerald-50 text-emerald-800 rounded-xl">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
              </svg>
            </span>
            <h3 class="font-black text-slate-900 text-lg">
              {{ isEditing ? 'Edit Pengumuman Sekolah' : 'Terbitkan Pengumuman Resmi' }}
            </h3>
          </div>
          <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 text-2xl cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="submitAnnouncement" class="space-y-4 text-sm">
          <!-- Judul -->
          <div>
            <label class="block font-bold text-slate-700 text-xs uppercase tracking-wider mb-1.5">
              Judul Pengumuman <span class="text-rose-500">*</span>
            </label>
            <input 
              v-model="form.judul" 
              type="text" 
              placeholder="Contoh: Jadwal Libur Awal Ramadhan 1448 H"
              required 
              class="w-full border border-slate-300 rounded-2xl p-3 text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm font-semibold" 
            />
          </div>

          <!-- Target & Kategori -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div>
              <label class="block font-bold text-slate-700 text-xs uppercase tracking-wider mb-1.5">
                Target Pembaca <span class="text-rose-500">*</span>
              </label>
              <select 
                v-model="form.target_role" 
                required
                class="w-full border border-slate-300 rounded-2xl p-3 text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm"
              >
                <option value="semua">Semua (Guru, Staf, & Wali)</option>
                <option value="guru">Khusus Dewan Guru</option>
                <option value="wali">Khusus Wali Murid</option>
                <option value="staf_admin">Khusus Staf Administrasi</option>
                <option value="kepala_sekolah">Khusus Kepala Sekolah</option>
              </select>
            </div>
            <div>
              <label class="block font-bold text-slate-700 text-xs uppercase tracking-wider mb-1.5">
                Kategori <span class="text-rose-500">*</span>
              </label>
              <select 
                v-model="form.kategori" 
                required
                class="w-full border border-slate-300 rounded-2xl p-3 text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm"
              >
                <option value="akademik">Akademik</option>
                <option value="kegiatan">Kegiatan Sekolah</option>
                <option value="keuangan">Keuangan / SPP</option>
                <option value="libur">Hari Libur / Cuti Bersama</option>
                <option value="umum">Umum</option>
              </select>
            </div>
          </div>

          <!-- Isi Pengumuman -->
          <div>
            <label class="block font-bold text-slate-700 text-xs uppercase tracking-wider mb-1.5">
              Isi Pengumuman Lengkap <span class="text-rose-500">*</span>
            </label>
            <textarea 
              v-model="form.isi" 
              rows="5" 
              placeholder="Tuliskan isi pengumuman atau instruksi secara lengkap dan terperinci..." 
              required 
              class="w-full border border-slate-300 rounded-2xl p-3 text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm leading-relaxed"
            ></textarea>
          </div>

          <!-- Lampiran Berkas PDF / Gambar -->
          <div>
            <label class="block font-bold text-slate-700 text-xs uppercase tracking-wider mb-1.5">
              Lampiran Dokumen (Opsional, PDF/Gambar maks 5MB)
            </label>
            <input 
              type="file" 
              accept=".pdf,.jpg,.jpeg,.png"
              @change="handleFileUpload" 
              class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-800 hover:file:bg-emerald-100 cursor-pointer"
            />
            <p v-if="isEditing && existingLampiran" class="text-[11px] text-slate-400 mt-1">
              File saat ini tersimpan: <em>{{ existingLampiran }}</em> (Pilih file baru jika ingin mengganti).
            </p>
          </div>

          <!-- Opsi Pinned (Sematkan) -->
          <div class="flex items-center gap-2.5 p-3 bg-amber-50/70 border border-amber-200/80 rounded-2xl">
            <input 
              type="checkbox" 
              v-model="form.is_pinned" 
              id="pinned" 
              class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-gray-300 cursor-pointer" 
            />
            <label for="pinned" class="text-xs font-bold text-amber-900 cursor-pointer">
              Sematkan di posisi teratas (Pinned Announcement)
            </label>
          </div>

          <!-- Tombol Aksi -->
          <div class="flex justify-end items-center gap-2.5 pt-3 border-t border-slate-100">
            <button 
              type="button" 
              @click="showModal = false" 
              class="px-5 py-2.5 text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl font-bold text-xs transition cursor-pointer"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="saving"
              class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <svg v-if="saving" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ saving ? 'Menyimpan & Menyebarkan...' : (isEditing ? 'Simpan Perubahan' : 'Terbitkan & Kirim Notif') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'
import { showConfirm } from '../services/swal'

const auth = useAuthStore()

const announcementsList = ref<any[]>([])
const filteredList = ref<any[]>([])
const loading = ref(false)
const saving = ref(false)
const showModal = ref(false)
const isEditing = ref(false)
const editingId = ref<number | null>(null)
const existingLampiran = ref<string | null>(null)
const selectedFile = ref<File | null>(null)

const searchQuery = ref('')
const filterTarget = ref('')
const filterCategory = ref('')

const message = ref<{ text: string; type: 'success' | 'error' } | null>(null)

const form = ref({
  judul: '',
  isi: '',
  kategori: 'umum',
  target_role: 'semua',
  is_pinned: false,
})

const showMessage = (text: string, type: 'success' | 'error' = 'success') => {
  message.value = { text, type }
  setTimeout(() => {
    if (message.value?.text === text) {
      message.value = null
    }
  }, 5000)
}

const fetchAnnouncements = async () => {
  try {
    loading.value = true
    const res = await api.get('/announcements')
    if (res.data?.success) {
      const rawData = res.data.data?.data || res.data.data || []
      announcementsList.value = rawData
      filterAnnouncements()
    }
  } catch (err: any) {
    showMessage(err.response?.data?.message || 'Gagal memuat daftar pengumuman.', 'error')
  } finally {
    loading.value = false
  }
}

const filterAnnouncements = () => {
  let list = [...announcementsList.value]

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter((a) =>
      (a.judul || '').toLowerCase().includes(q) ||
      (a.isi || a.konten || '').toLowerCase().includes(q)
    )
  }

  if (filterTarget.value) {
    list = list.filter((a) => a.target_role === filterTarget.value)
  }

  if (filterCategory.value) {
    list = list.filter((a) => (a.kategori || 'umum') === filterCategory.value)
  }

  // Sort pinned first, then newest
  list.sort((a, b) => {
    if (a.is_pinned && !b.is_pinned) return -1
    if (!a.is_pinned && b.is_pinned) return 1
    return new Date(b.created_at || b.tgl_terbit).getTime() - new Date(a.created_at || a.tgl_terbit).getTime()
  })

  filteredList.value = list
}

const openCreateModal = () => {
  isEditing.value = false
  editingId.value = null
  existingLampiran.value = null
  selectedFile.value = null
  form.value = {
    judul: '',
    isi: '',
    kategori: 'umum',
    target_role: 'semua',
    is_pinned: false,
  }
  showModal.value = true
}

const openEditModal = (anc: any) => {
  isEditing.value = true
  editingId.value = anc.id
  existingLampiran.value = anc.lampiran_path
  selectedFile.value = null
  form.value = {
    judul: anc.judul,
    isi: anc.isi || anc.konten || '',
    kategori: anc.kategori || 'umum',
    target_role: anc.target_role || 'semua',
    is_pinned: Boolean(anc.is_pinned),
  }
  showModal.value = true
}

const handleFileUpload = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    selectedFile.value = target.files[0]
  }
}

const submitAnnouncement = async () => {
  try {
    saving.value = true

    const formData = new FormData()
    formData.append('judul', form.value.judul)
    formData.append('isi', form.value.isi)
    formData.append('kategori', form.value.kategori)
    formData.append('target_role', form.value.target_role)
    formData.append('is_pinned', form.value.is_pinned ? '1' : '0')

    if (selectedFile.value) {
      formData.append('lampiran', selectedFile.value)
    }

    if (isEditing.value && editingId.value) {
      formData.append('_method', 'PUT')
      await api.post(`/announcements/${editingId.value}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      showMessage('Pengumuman berhasil diperbarui!')
    } else {
      await api.post('/announcements', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      showMessage('Pengumuman resmi berhasil diterbitkan serta disebarkan melalui notifikasi dashboard & email!')
    }

    showModal.value = false
    fetchAnnouncements()
  } catch (err: any) {
    const errorMsg = err.response?.data?.message || 'Gagal menyimpan pengumuman. Pastikan semua data telah diisi.'
    showMessage(errorMsg, 'error')
  } finally {
    saving.value = false
  }
}

const deleteAnnouncement = async (anc: any) => {
  const confirmed = await showConfirm(
    'Hapus Pengumuman Ini?',
    `Pengumuman "${anc.judul}" akan dihapus dari papan pengumuman.`
  )

  if (!confirmed) return

  try {
    await api.delete(`/announcements/${anc.id}`)
    showMessage('Pengumuman berhasil dihapus.')
    fetchAnnouncements()
  } catch (err: any) {
    showMessage(err.response?.data?.message || 'Gagal menghapus pengumuman.', 'error')
  }
}

const getCategoryBadgeClass = (kat: string) => {
  switch (kat) {
    case 'akademik':
      return 'bg-blue-100 text-blue-900 border border-blue-200'
    case 'kegiatan':
      return 'bg-emerald-100 text-emerald-900 border border-emerald-200'
    case 'keuangan':
      return 'bg-purple-100 text-purple-900 border border-purple-200'
    case 'libur':
      return 'bg-amber-100 text-amber-900 border border-amber-200'
    default:
      return 'bg-slate-100 text-slate-800 border border-slate-200'
  }
}

const formatTargetRole = (role: string) => {
  switch (role) {
    case 'semua':
      return 'Semua (Guru, Staf, & Wali)'
    case 'guru':
      return 'Dewan Guru'
    case 'wali':
      return 'Wali Murid'
    case 'staf_admin':
      return 'Staf Administrasi'
    case 'kepala_sekolah':
      return 'Kepala Sekolah'
    default:
      return role
  }
}

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

const getStorageUrl = (path: string) => {
  if (!path) return '#'
  if (path.startsWith('http')) return path
  return `/storage/${path.replace(/^\/?(storage\/)?/, '')}`
}

onMounted(() => {
  fetchAnnouncements()
})
</script>

