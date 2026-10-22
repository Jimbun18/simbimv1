<script setup lang="ts">
import { ref, watch } from 'vue'
import { showError } from '../../services/swal'
import type { Teacher, TeacherFormData } from './types'
import { getPhotoUrl, getInitials, toInputDate } from './types'

const props = defineProps<{
  initialData?: Teacher | null
  isEditing: boolean
  saving: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'submit', payload: { formData: FormData; isEditing: boolean; id?: number }): void
}>()

const fileInputRef = ref<HTMLInputElement | null>(null)
const photoFile = ref<File | null>(null)
const photoPreview = ref<string>('')

const form = ref<TeacherFormData>({
  nik: '',
  nip: '',
  nama_lengkap: '',
  jenis_kelamin: 'L',
  tempat_lahir: 'Purwokerto',
  tgl_lahir: '1990-01-01',
  agama: 1,
  alamat_jalan: '',
  kab_kota: 'Banyumas',
  no_hp: '',
  email: '',
  jenis_ptk: 'guru_kelas',
  jabatan: '',
  tugas_tambahan: '',
  status_kepegawaian: 'gty',
  status: 'aktif',
  tgl_mulai_tugas: new Date().toISOString().split('T')[0],
  create_user_account: true,
})

watch(
  () => props.initialData,
  (t) => {
    if (t) {
      photoPreview.value = getPhotoUrl(t.foto_url, t.foto_path)
      form.value = {
        nik: t.nik || '',
        nip: t.nip || '',
        nama_lengkap: t.nama_lengkap || '',
        jenis_kelamin: t.jenis_kelamin || 'L',
        tempat_lahir: t.tempat_lahir || 'Purwokerto',
        tgl_lahir: t.tgl_lahir ? toInputDate(t.tgl_lahir) : '1990-01-01',
        agama: t.agama || 1,
        alamat_jalan: t.alamat_jalan || '',
        kab_kota: t.kab_kota || 'Banyumas',
        no_hp: t.no_hp || '',
        email: t.email || '',
        jenis_ptk: t.jenis_ptk || 'guru_kelas',
        jabatan: t.jabatan || '',
        tugas_tambahan: t.tugas_tambahan || '',
        status_kepegawaian: t.status_kepegawaian || 'gty',
        status: t.status || 'aktif',
        tgl_mulai_tugas: t.tgl_mulai_tugas ? toInputDate(t.tgl_mulai_tugas) : new Date().toISOString().split('T')[0],
        create_user_account: false,
      }
    } else {
      photoPreview.value = ''
      form.value = {
        nik: '',
        nip: '',
        nama_lengkap: '',
        jenis_kelamin: 'L',
        tempat_lahir: 'Purwokerto',
        tgl_lahir: '1990-01-01',
        agama: 1,
        alamat_jalan: '',
        kab_kota: 'Banyumas',
        no_hp: '',
        email: '',
        jenis_ptk: 'guru_kelas',
        jabatan: '',
        tugas_tambahan: '',
        status_kepegawaian: 'gty',
        status: 'aktif',
        tgl_mulai_tugas: new Date().toISOString().split('T')[0],
        create_user_account: true,
      }
    }
  },
  { immediate: true }
)

const handlePhotoChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) {
    if (file.size > 5 * 1024 * 1024) {
      showError('File Terlalu Besar', 'Ukuran foto maksimal 5 MB.')
      target.value = ''
      return
    }
    photoFile.value = file
    photoPreview.value = URL.createObjectURL(file)
  }
}

const clearPhoto = () => {
  photoFile.value = null
  photoPreview.value = ''
  if (fileInputRef.value) fileInputRef.value.value = ''
}

const handleSubmit = () => {
  const payload: any = { ...form.value }
  if (!payload.nip) delete payload.nip
  if (!payload.email) delete payload.email

  const formData = new FormData()
  for (const key in payload) {
    if (payload[key] !== null && payload[key] !== undefined) {
      if (key === 'create_user_account') {
        formData.append('create_user_account', payload[key] ? '1' : '0')
      } else {
        formData.append(key, payload[key])
      }
    }
  }

  if (photoFile.value) {
    formData.append('foto', photoFile.value)
  }

  emit('submit', {
    formData,
    isEditing: props.isEditing,
    id: props.initialData?.id,
  })
}
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-200">
    <!-- Top Action Banner -->
    <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-3.5">
        <button
          @click="emit('close')"
          type="button"
          class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center justify-center shrink-0 shadow-2xs group cursor-pointer"
          title="Kembali ke Daftar Guru & Tendik"
        >
          <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </button>
        <div>
          <!-- Breadcrumbs -->
          <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
            <span>Kepegawaian</span>
            <span>/</span>
            <button type="button" @click="emit('close')" class="hover:text-sky-600 hover:underline cursor-pointer">Data Guru & Tendik</button>
            <span>/</span>
            <span class="text-sky-700 font-bold">{{ isEditing ? 'Edit Guru' : 'Tambah Guru Baru' }}</span>
          </div>
          <h1 class="text-lg sm:text-2xl font-black text-gray-900 leading-tight">
            {{ isEditing ? `Edit Pendidik: ${form.nama_lengkap || '...'}` : 'Formulir Pendidik & Tendik Baru' }}
          </h1>
          <p class="text-xs text-gray-500 mt-0.5">
            {{ isEditing ? 'Perbarui identitas kepegawaian, penugasan jenis PTK, dan kontak guru.' : 'Lengkapi formulir di bawah ini untuk mendaftarkan pendidik atau tenaga kependidikan baru.' }}
          </p>
        </div>
      </div>

      <!-- Top Action Buttons -->
      <div class="flex items-center gap-2.5 self-end sm:self-center">
        <button
          @click="emit('close')"
          type="button"
          class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition cursor-pointer"
        >
          Batal
        </button>
        <button
          @click="handleSubmit"
          :disabled="saving"
          type="button"
          class="inline-flex items-center gap-2 px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-md shadow-sky-600/25 transition disabled:opacity-50 active:scale-95 cursor-pointer"
        >
          <svg v-if="saving" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span>{{ saving ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Data Guru') }}</span>
        </button>
      </div>
    </div>

    <!-- Main Form Grid: 12 Cols -->
    <form @submit.prevent="handleSubmit" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      
      <!-- LEFT COLUMN: Foto Profil & ID Card Guru Preview (4 cols) -->
      <div class="lg:col-span-4 space-y-6">
        <!-- Photo Upload Card -->
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-xs space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900">Pas Foto Guru / Tendik</h3>
            <span class="text-[10px] text-gray-400 font-semibold uppercase">Opsional</span>
          </div>

          <div class="flex flex-col items-center justify-center p-6 bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl group hover:border-sky-400 transition">
            <div class="w-28 h-28 rounded-2xl overflow-hidden bg-white border border-slate-200 shadow-xs flex items-center justify-center relative mb-3">
              <img
                v-if="photoPreview"
                :src="photoPreview"
                alt="Preview Foto"
                class="w-full h-full object-cover"
              />
              <div v-else class="text-center p-2 text-slate-400">
                <svg class="w-10 h-10 mx-auto text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="text-[10px] font-semibold mt-1 block">Belum ada foto</span>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <label class="px-3 py-1.5 bg-sky-50 hover:bg-sky-100 text-sky-700 text-xs font-bold rounded-xl border border-sky-200 cursor-pointer transition shadow-2xs">
                Pilih Foto
                <input
                  ref="fileInputRef"
                  type="file"
                  accept="image/png, image/jpeg, image/jpg, image/webp"
                  @change="handlePhotoChange"
                  class="hidden"
                />
              </label>
              <button
                v-if="photoPreview || photoFile"
                type="button"
                @click="clearPhoto"
                class="px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 border border-rose-200 rounded-xl transition cursor-pointer"
              >
                Hapus
              </button>
            </div>
            <p class="text-[10px] text-gray-400 text-center mt-2">JPG, PNG, atau WebP. Maks 5 MB.</p>
          </div>
        </div>

        <!-- Live ID Card Preview Card (Teacher Profile) -->
        <div class="bg-gradient-to-br from-slate-900 via-sky-950 to-slate-900 p-5 rounded-2xl text-white shadow-lg space-y-4">
          <div class="flex items-center justify-between text-sky-400 text-xs font-bold">
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-sky-400 animate-ping"></span>
              KARTU PENDIDIK (PREVIEW)
            </span>
            <span class="font-mono text-[10px] text-slate-400">SIM-BIM</span>
          </div>

          <div class="flex items-center gap-3.5 pt-1">
            <div class="w-14 h-14 rounded-xl overflow-hidden bg-white/10 border border-white/20 shrink-0 flex items-center justify-center">
              <img
                v-if="photoPreview"
                :src="photoPreview"
                alt="Preview"
                class="w-full h-full object-cover"
              />
              <span v-else class="text-sm font-black text-sky-300 uppercase">
                {{ getInitials(form.nama_lengkap || 'Guru') }}
              </span>
            </div>
            <div class="min-w-0 flex-1">
              <h4 class="text-sm font-bold truncate text-white">
                {{ form.nama_lengkap || 'Nama Lengkap & Gelar' }}
              </h4>
              <p class="text-[11px] font-mono text-sky-300">
                NIP: {{ form.nip || '-------' }}
              </p>
              <div class="flex items-center gap-1.5 mt-1">
                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-white/15 text-slate-200 uppercase">
                  {{ form.jenis_ptk?.replace('_', ' ') }}
                </span>
                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-sky-500/20 text-sky-300 uppercase">
                  {{ form.status_kepegawaian }}
                </span>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-white/10 text-[11px] text-slate-300 flex justify-between items-center">
            <span>Status Keaktifan:</span>
            <span class="font-bold uppercase tracking-wider text-emerald-400">{{ form.status }}</span>
          </div>
        </div>

        <!-- Petunjuk Card -->
        <div class="bg-sky-50/70 p-4 rounded-2xl border border-sky-100 text-xs text-sky-950 space-y-1.5">
          <div class="font-bold flex items-center gap-1.5 text-sky-800">
            <span>💡</span> Catatan Petugas Kepegawaian
          </div>
          <p class="text-[11px] text-sky-700 leading-relaxed">
            Nomor WhatsApp digunakan untuk autentikasi dan komunikasi jadwal mengajar. Centang opsi pembuatan akun agar guru otomatis mendapatkan hak akses masuk ke jadwal dan presensi kelas.
          </p>
        </div>
      </div>

      <!-- RIGHT COLUMN: Form Sections (8 cols) -->
      <div class="lg:col-span-8 space-y-6">
        
        <!-- CARD 1: Identitas Pokok Pendidik -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
          <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
            <div>
              <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center text-xs font-black">1</span>
                Identitas Pokok Pendidik / Tendik
              </h3>
              <p class="text-xs text-gray-500 mt-0.5 ml-8">Informasi nama lengkap beserta gelar, NIK, NIP, jenis kelamin, dan tanggal lahir.</p>
            </div>
            <span class="text-[10px] text-rose-500 font-semibold">* Wajib diisi</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Lengkap & Gelar <span class="text-rose-500">*</span></label>
              <input
                v-model="form.nama_lengkap"
                type="text"
                required
                placeholder="Contoh: Ustadz Ahmad Fauzi, S.Pd.I."
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold text-gray-800 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-hidden transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">NIK (16 Digit KTP) <span class="text-rose-500">*</span></label>
              <input
                v-model="form.nik"
                type="text"
                required
                maxlength="16"
                placeholder="Contoh: 330201..."
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-hidden transition"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">NIP / NUPTK</label>
              <input
                v-model="form.nip"
                type="text"
                placeholder="Contoh: 19850101..."
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-hidden transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Jenis Kelamin <span class="text-rose-500">*</span></label>
              <div class="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  @click="form.jenis_kelamin = 'L'"
                  :class="form.jenis_kelamin === 'L' ? 'bg-sky-50 border-sky-400 text-sky-700 font-bold shadow-2xs' : 'bg-gray-50 border-gray-200 text-gray-600'"
                  class="py-2 px-3 rounded-xl border text-xs flex items-center justify-center gap-1.5 transition cursor-pointer"
                >
                  <span>👨</span>
                  <span>Laki-laki</span>
                </button>
                <button
                  type="button"
                  @click="form.jenis_kelamin = 'P'"
                  :class="form.jenis_kelamin === 'P' ? 'bg-rose-50 border-rose-400 text-rose-700 font-bold shadow-2xs' : 'bg-gray-50 border-gray-200 text-gray-600'"
                  class="py-2 px-3 rounded-xl border text-xs flex items-center justify-center gap-1.5 transition cursor-pointer"
                >
                  <span>👩</span>
                  <span>Perempuan</span>
                </button>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Tempat Lahir <span class="text-rose-500">*</span></label>
              <input
                v-model="form.tempat_lahir"
                type="text"
                required
                placeholder="Purwokerto"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-hidden transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Tanggal Lahir <span class="text-rose-500">*</span></label>
              <input
                v-model="form.tgl_lahir"
                type="date"
                required
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-hidden transition"
              />
            </div>
          </div>
        </div>

        <!-- CARD 2: Penugasan & Status Kepegawaian -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
          <div class="border-b border-gray-100 pb-3">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
              <span class="w-6 h-6 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center text-xs font-black">2</span>
              Penugasan & Status Kepegawaian
            </h3>
            <p class="text-xs text-gray-500 mt-0.5 ml-8">Tentukan tugas pokok PTK, status ikatan kerja yayasan, dan masa tugas.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Jenis PTK <span class="text-rose-500">*</span></label>
              <select
                v-model="form.jenis_ptk"
                required
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-hidden bg-white font-semibold text-sky-800 transition"
              >
                <option value="guru_kelas">Guru Kelas</option>
                <option value="guru_mapel">Guru Mapel</option>
                <option value="guru_agama">Guru Agama / Tahfidz</option>
                <option value="guru_olahraga">Guru Olahraga</option>
                <option value="kepala_sekolah">Kepala Sekolah</option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Status Kepegawaian <span class="text-rose-500">*</span></label>
              <select
                v-model="form.status_kepegawaian"
                required
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-hidden bg-white transition"
              >
                <option value="gty">GTY (Tetap Yayasan)</option>
                <option value="gtt">GTT (Tidak Tetap)</option>
                <option value="pns">PNS DPK</option>
                <option value="pppk">PPPK</option>
                <option value="honorer">Honorer</option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Status Keaktifan <span class="text-rose-500">*</span></label>
              <select
                v-model="form.status"
                required
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-hidden bg-white transition"
              >
                <option value="aktif">Aktif</option>
                <option value="cuti">Cuti</option>
                <option value="nonaktif">Nonaktif</option>
                <option value="pensiun">Pensiun</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Jabatan Struktural / Utama</label>
              <input
                v-model="form.jabatan"
                type="text"
                placeholder="Contoh: Kepala Sekolah, Guru Kelas 1, Staf Tata Usaha, Guru BK"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-hidden transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Tugas Tambahan</label>
              <input
                v-model="form.tugas_tambahan"
                type="text"
                placeholder="Contoh: Wali Kelas 1, Guru Tema 2, Guru Tema 4"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-hidden transition"
              />
            </div>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 text-xs mb-1">Masa Bergabung / TMT Mulai Tugas <span class="text-rose-500">*</span></label>
            <input
              v-model="form.tgl_mulai_tugas"
              type="date"
              required
              class="w-full sm:w-1/2 border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-hidden transition"
            />
          </div>
        </div>

        <!-- CARD 3: Alamat Domisili & Kontak -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
          <div class="border-b border-gray-100 pb-3">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
              <span class="w-6 h-6 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center text-xs font-black">3</span>
              Alamat Domisili & Kontak PTK
            </h3>
            <p class="text-xs text-gray-500 mt-0.5 ml-8">Kontak aktif untuk pengiriman jadwal mengajar dan koordinasi sekolah.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Alamat Domisili Lengkap <span class="text-rose-500">*</span></label>
              <input
                v-model="form.alamat_jalan"
                type="text"
                required
                placeholder="Jl. / RT / RW / Desa..."
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-hidden transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Kabupaten / Kota <span class="text-rose-500">*</span></label>
              <input
                v-model="form.kab_kota"
                type="text"
                required
                placeholder="Contoh: Banyumas"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-hidden transition"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">No. WhatsApp / HP Aktif <span class="text-rose-500">*</span></label>
              <input
                v-model="form.no_hp"
                type="text"
                required
                placeholder="081234567890"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-hidden transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Email</label>
              <input
                v-model="form.email"
                type="email"
                placeholder="guru@simbim.sch.id"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-hidden transition"
              />
            </div>
          </div>

          <!-- Akun Login Otomatis (Saat Tambah Baru) -->
          <div v-if="!isEditing" class="p-4 bg-sky-50 rounded-2xl border border-sky-100 flex items-center gap-3">
            <input
              type="checkbox"
              id="createUser"
              v-model="form.create_user_account"
              class="w-4 h-4 rounded text-sky-600 focus:ring-sky-500 border-gray-300 cursor-pointer"
            />
            <label for="createUser" class="text-xs font-semibold text-sky-900 cursor-pointer">
              Buatkan otomatis akun login SIM-BIM dengan password default (<code>Password123!</code>)
            </label>
          </div>
        </div>

        <!-- Bottom Sticky Action Bar -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-xs flex items-center justify-between gap-4">
          <button
            @click="emit('close')"
            type="button"
            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition flex items-center gap-2 cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Kembali ke Daftar Guru</span>
          </button>

          <div class="flex items-center gap-3">
            <button
              @click="emit('close')"
              type="button"
              class="px-4 py-2.5 text-gray-500 hover:text-gray-700 text-xs font-semibold transition cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="inline-flex items-center gap-2 px-6 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-md shadow-sky-600/25 transition disabled:opacity-50 active:scale-95 cursor-pointer"
            >
              <svg v-if="saving" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <span>{{ saving ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Data Guru') }}</span>
            </button>
          </div>
        </div>

      </div>
    </form>
  </div>
</template>
