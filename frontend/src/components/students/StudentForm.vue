<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import api from '../../services/api';
import { showSuccess, showError, confirmAction } from '../../services/swal';
import { toInputDate } from '../../utils/date';

const props = defineProps<{
  initialStudent?: any;
  student?: any;
  initialData?: any;
}>();

const emit = defineEmits<{
  (e: 'cancel'): void;
  (e: 'saved'): void;
}>();

const isEditing = ref(false);
const editingId = ref<number | null>(null);
const saving = ref(false);

const classes = ref<any[]>([]);
const religions = ref<any[]>([]);
const transports = ref<any[]>([]);
const educationList = ref<any[]>([]);
const occupationList = ref<any[]>([]);

// Photo Upload State
const fileInputRef = ref<HTMLInputElement | null>(null);
const photoFile = ref<File | null>(null);
const photoPreview = ref<string>('');
const isPhotoRemoved = ref(false);
const deletingPhoto = ref(false);

const getPhotoUrl = (url?: string, path?: string) => {
  if (url) return url;
  if (path) {
    return path.startsWith('http') ? path : `/storage/${path}`;
  }
  return '';
};

const getInitials = (name?: string) => {
  if (!name) return '?';
  const parts = name.trim().split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
};

const handlePhotoChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    if (file.size > 5 * 1024 * 1024) {
      showError('File Terlalu Besar', null, 'Ukuran foto maksimal 5 MB.');
      target.value = '';
      return;
    }
    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
    isPhotoRemoved.value = false;
  }
};

const clearPhoto = async () => {
  if (isEditing.value && editingId.value && !photoFile.value && photoPreview.value) {
    const confirmed = await confirmAction(
      'Hapus Pas Foto Siswa?',
      'Foto siswa yang tersimpan di server akan dihapus secara permanen.',
      'Ya, Hapus Foto',
      'warning'
    );
    if (!confirmed) return;

    deletingPhoto.value = true;
    try {
      const res = await api.delete('/students/' + editingId.value + '/photo');
      if (res.data.success) {
        showSuccess('Berhasil Dihapus!', 'Foto siswa berhasil dihapus.');
        photoFile.value = null;
        photoPreview.value = '';
        isPhotoRemoved.value = true;
        if (fileInputRef.value) fileInputRef.value.value = '';
      }
    } catch (err: any) {
      showError('Gagal Menghapus Foto', err, 'Terjadi kesalahan saat menghapus foto siswa.');
    } finally {
      deletingPhoto.value = false;
    }
    return;
  }

  photoFile.value = null;
  photoPreview.value = '';
  isPhotoRemoved.value = true;
  if (fileInputRef.value) fileInputRef.value.value = '';
};

// Form State with all 66 Dapodik columns
const form = ref<any>({
  nis: '',
  nisn: '',
  nik: '',
  no_kk: '',
  no_akta_lahir: '',
  nama_lengkap: '',
  nama_panggilan: '',
  jenis_kelamin: 'L',
  tempat_lahir: 'Banyumas',
  tgl_lahir: '2018-01-01',
  agama: 1,
  kewarganegaraan: 'WNI',
  anak_ke: 1,
  jumlah_saudara: 0,
  kebutuhan_khusus: '',

  current_class_id: null,
  tgl_masuk: new Date().toISOString().split('T')[0],
  status: 'aktif',
  jenis_pendaftaran: 'baru',
  asal_sekolah_tk: '',
  skhun: '',
  no_peserta_un: '',
  no_seri_ijazah: '',

  alamat_jalan: '',
  rt: '',
  rw: '',
  dusun: '',
  desa_kelurahan: '',
  kecamatan: '',
  kab_kota: 'Banyumas',
  provinsi: 'Jawa Tengah',
  kode_pos: '',
  tinggal_bersama: 'orang_tua',
  transportasi: null,
  jarak_ke_sekolah_km: '',
  waktu_tempuh_menit: '',
  latitude: '',
  longitude: '',
  no_hp_siswa: '',
  telepon: '',
  email: '',

  // Data Ayah
  nama_ayah: '',
  nik_ayah: '',
  pekerjaan_ayah: '',
  pendidikan_ayah: '',
  penghasilan_ayah: '',
  no_hp_ayah: '',

  // Data Ibu
  nama_ibu: '',
  nik_ibu: '',
  pekerjaan_ibu: '',
  pendidikan_ibu: '',
  penghasilan_ibu: '',
  no_hp_ibu: '',

  // Data Wali
  nama_wali: '',
  nik_wali: '',
  pekerjaan_wali: '',
  pendidikan_wali: '',
  penghasilan_wali: '',
  no_hp_wali: '',

  // Fisik & Periodik
  tinggi_cm: '',
  berat_kg: '',
  lingkar_kepala: '',
  golongan_darah: '',
  riwayat_penyakit: '',
  alergi: '',

  // Kesejahteraan & Bank
  penerima_kps: 'Tidak',
  no_kps: '',
  penerima_kip: 'Tidak',
  nomor_kip: '',
  nama_di_kip: '',
  nomor_kks: '',
  layak_pip: 'Tidak',
  alasan_layak_pip: '',
  bank: '',
  nomor_rekening_bank: '',
  rekening_atas_nama: '',
});

// Computed: Umur Siswa Otomatis dari Tanggal Lahir
const studentAge = computed(() => {
  if (!form.value.tgl_lahir) return '';
  const birth = new Date(form.value.tgl_lahir);
  if (isNaN(birth.getTime())) return '';
  const today = new Date();
  let years = today.getFullYear() - birth.getFullYear();
  let months = today.getMonth() - birth.getMonth();
  if (months < 0 || (months === 0 && today.getDate() < birth.getDate())) {
    years--;
    months += 12;
  }
  if (years < 0) return '';
  return `${years} Tahun ${months} Bulan`;
});

// Helper: Nama Rombel Kelas Terpilih untuk Live ID Card
const getSelectedClassName = () => {
  if (!form.value.current_class_id) return 'Belum Ada Kelas';
  const found = classes.value.find((c: any) => c.id === form.value.current_class_id);
  return found ? (found.nama_kelas || found.nama) : 'Belum Ada Kelas';
};

const getGuardianByRelation = (student: any, hubungan: string) => {
  if (!student || !student.guardians) return null;
  return student.guardians.find((g: any) => g.hubungan === hubungan || g.pivot?.hubungan === hubungan);
};

const fetchReferences = async () => {
  try {
    const res = await api.get('/references');
    if (res.data.success && res.data.data) {
      if (res.data.data.religions) religions.value = res.data.data.religions;
      if (res.data.data.transports) transports.value = res.data.data.transports;
      if (res.data.data.education) educationList.value = res.data.data.education;
      if (res.data.data.occupations) occupationList.value = res.data.data.occupations;
    }
  } catch (err) {
    console.error('Gagal mengambil data referensi', err);
  }
};

const fetchClasses = async () => {
  try {
    const res = await api.get('/classes');
    if (res.data.success) {
      classes.value = res.data.data || [];
    }
  } catch (err) {
    console.error('Gagal mengambil data kelas', err);
  }
};

const initForm = async () => {
  const target = props.initialStudent || props.student || props.initialData;
  if (!target || !target.id) {
    isEditing.value = false;
    editingId.value = null;
    photoFile.value = null;
    photoPreview.value = '';
    isPhotoRemoved.value = false;
    if (fileInputRef.value) fileInputRef.value.value = '';
    return;
  }

  isEditing.value = true;
  editingId.value = target.id;

  let s = target;
  try {
    const res = await api.get(`/students/${target.id}`);
    if (res.data.success && res.data.data) {
      s = res.data.data;
    }
  } catch (err) {
    console.error('Gagal mengambil data lengkap untuk edit:', err);
  }

  const ayah = getGuardianByRelation(s, 'ayah');
  const ibu = getGuardianByRelation(s, 'ibu');
  const wali = getGuardianByRelation(s, 'wali');

  photoFile.value = null;
  photoPreview.value = getPhotoUrl(s.foto_url, s.foto_path);
  isPhotoRemoved.value = false;
  if (fileInputRef.value) fileInputRef.value.value = '';

  form.value = {
    nis: s.nis || '',
    nisn: s.nisn || '',
    nik: s.nik || '',
    no_kk: s.no_kk || '',
    no_akta_lahir: s.no_akta_lahir || '',
    nama_lengkap: s.nama_lengkap || '',
    nama_panggilan: s.nama_panggilan || '',
    jenis_kelamin: s.jenis_kelamin || 'L',
    tempat_lahir: s.tempat_lahir || 'Banyumas',
    tgl_lahir: toInputDate(s.tgl_lahir),
    agama: s.agama || religions.value[0]?.id || 1,
    kewarganegaraan: s.kewarganegaraan || 'WNI',
    anak_ke: s.anak_ke || 1,
    jumlah_saudara: s.jumlah_saudara || 0,
    kebutuhan_khusus: s.kebutuhan_khusus || '',

    current_class_id: s.class_id || s.current_class_id || s.current_class?.id || s.school_class?.id || null,
    tgl_masuk: toInputDate(s.tgl_masuk) || new Date().toISOString().split('T')[0],
    status: s.status || 'aktif',
    jenis_pendaftaran: s.jenis_pendaftaran || 'baru',
    asal_sekolah_tk: s.asal_sekolah_tk || '',
    skhun: s.skhun || '',
    no_peserta_un: s.no_peserta_un || '',
    no_seri_ijazah: s.no_seri_ijazah || '',

    alamat_jalan: s.alamat_jalan || '',
    rt: s.rt || '',
    rw: s.rw || '',
    dusun: s.dusun || '',
    desa_kelurahan: s.desa_kelurahan || '',
    kecamatan: s.kecamatan || '',
    kab_kota: s.kab_kota || 'Banyumas',
    provinsi: s.provinsi || 'Jawa Tengah',
    kode_pos: s.kode_pos || '',
    tinggal_bersama: s.tinggal_bersama || 'orang_tua',
    transportasi: s.transportasi || null,
    jarak_ke_sekolah_km: s.jarak_ke_sekolah_km || '',
    waktu_tempuh_menit: s.waktu_tempuh_menit || '',
    latitude: s.latitude || '',
    longitude: s.longitude || '',
    no_hp_siswa: s.no_hp_siswa || '',
    telepon: s.telepon || '',
    email: s.email || '',

    // Data Ayah
    nama_ayah: ayah?.nama_lengkap || '',
    nik_ayah: ayah?.nik || '',
    pekerjaan_ayah: ayah?.pekerjaan || '',
    pendidikan_ayah: ayah?.pendidikan_terakhir || '',
    penghasilan_ayah: ayah?.penghasilan_bulanan || '',
    no_hp_ayah: ayah?.no_hp || '',

    // Data Ibu
    nama_ibu: ibu?.nama_lengkap || '',
    nik_ibu: ibu?.nik || '',
    pekerjaan_ibu: ibu?.pekerjaan || '',
    pendidikan_ibu: ibu?.pendidikan_terakhir || '',
    penghasilan_ibu: ibu?.penghasilan_bulanan || '',
    no_hp_ibu: ibu?.no_hp || '',

    // Data Wali
    nama_wali: wali?.nama_lengkap || '',
    nik_wali: wali?.nik || '',
    pekerjaan_wali: wali?.pekerjaan || '',
    pendidikan_wali: wali?.pendidikan_terakhir || '',
    penghasilan_wali: wali?.penghasilan_bulanan || '',
    no_hp_wali: wali?.no_hp || '',

    // Fisik
    tinggi_cm: s.tinggi_cm || '',
    berat_kg: s.berat_kg || '',
    lingkar_kepala: s.lingkar_kepala || '',
    golongan_darah: s.golongan_darah || '',
    riwayat_penyakit: s.riwayat_penyakit || '',
    alergi: s.alergi || '',

    // Kesejahteraan & Bank
    penerima_kps: s.penerima_kps || 'Tidak',
    no_kps: s.no_kps || '',
    penerima_kip: s.penerima_kip || 'Tidak',
    nomor_kip: s.nomor_kip || '',
    nama_di_kip: s.nama_di_kip || '',
    nomor_kks: s.nomor_kks || '',
    layak_pip: s.layak_pip || 'Tidak',
    alasan_layak_pip: s.alasan_layak_pip || '',
    bank: s.bank || '',
    nomor_rekening_bank: s.nomor_rekening_bank || '',
    rekening_atas_nama: s.rekening_atas_nama || '',
  };
};

const submitForm = async () => {
  saving.value = true;
  try {
    const payload: any = { ...form.value };
    if (!payload.nisn) delete payload.nisn;
    if (!payload.nik) delete payload.nik;
    if (!payload.no_kk) delete payload.no_kk;
    if (!payload.no_akta_lahir) delete payload.no_akta_lahir;
    if (!payload.nama_panggilan) delete payload.nama_panggilan;
    if (!payload.no_hp_siswa) delete payload.no_hp_siswa;
    if (!payload.telepon) delete payload.telepon;
    if (!payload.email) delete payload.email;

    if (payload.tgl_masuk) {
      payload.tahun_masuk = new Date(payload.tgl_masuk).getFullYear();
    }

    if (payload.current_class_id) {
      payload.class_id = payload.current_class_id;
    }

    const formData = new FormData();
    for (const key in payload) {
      if (payload[key] !== null && payload[key] !== undefined && payload[key] !== '') {
        formData.append(key, payload[key]);
      } else if (key === 'current_class_id' || key === 'class_id') {
        formData.append(key, '');
      }
    }

    if (photoFile.value) {
      formData.append('foto', photoFile.value);
    } else if (isPhotoRemoved.value) {
      formData.append('hapus_foto', '1');
      formData.append('remove_photo', '1');
    }

    if (isEditing.value && editingId.value) {
      formData.append('_method', 'PUT');
      const res = await api.post(`/students/${editingId.value}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      if (res.data.success) {
        showSuccess('Berhasil!', 'Data siswa (format Dapodik) berhasil diperbarui.');
        emit('saved');
      }
    } else {
      const res = await api.post('/students', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      if (res.data.success) {
        showSuccess('Berhasil!', 'Data siswa baru (format Dapodik) berhasil ditambahkan.');
        emit('saved');
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err, 'Terjadi kesalahan saat menyimpan data siswa.');
  } finally {
    saving.value = false;
  }
};

watch(
  () => props.initialStudent || props.student || props.initialData,
  () => {
    initForm();
  }
);

onMounted(async () => {
  await Promise.all([fetchClasses(), fetchReferences()]);
  await initForm();
});
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-200">
    <!-- Top Navigation & Action Banner -->
    <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-3.5">
        <button
          @click="emit('cancel')"
          type="button"
          class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center justify-center shrink-0 shadow-xs group cursor-pointer"
          title="Kembali ke Daftar Siswa"
        >
          <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </button>
        <div>
          <!-- Breadcrumbs -->
          <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
            <span>Kesiswaan</span>
            <span>/</span>
            <button type="button" @click="emit('cancel')" class="hover:text-emerald-600 hover:underline cursor-pointer">Data Siswa</button>
            <span>/</span>
            <span class="text-emerald-700 font-bold">{{ isEditing ? 'Edit Siswa (Format Dapodik)' : 'Tambah Siswa Baru (Format Dapodik)' }}</span>
          </div>
          <h1 class="text-lg sm:text-2xl font-black text-gray-900 leading-tight">
            {{ isEditing ? `Edit Data Siswa: ${form.nama_lengkap || '...'}` : 'Formulir Pendaftaran Siswa (Standar Dapodik Kemdikbud)' }}
          </h1>
          <p class="text-xs text-gray-500 mt-0.5">
            {{ isEditing ? 'Perbarui seluruh field biodata kesiswaan sesuai kolom resmi Dapodik Kemdikbud.' : 'Lengkapi formulir terpadu sesuai standar Dapodik untuk menambahkan siswa baru ke sistem.' }}
          </p>
        </div>
      </div>

      <!-- Top Action Buttons -->
      <div class="flex items-center gap-2.5 self-end sm:self-center">
        <button
          @click="emit('cancel')"
          type="button"
          class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition cursor-pointer"
        >
          Batal
        </button>
        <button
          @click="submitForm"
          :disabled="saving"
          type="button"
          class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/25 transition disabled:opacity-50 active:scale-95 cursor-pointer"
        >
          <svg v-if="saving" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span>{{ saving ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Siswa Baru') }}</span>
        </button>
      </div>
    </div>

    <!-- Main Form Grid: 12 Cols -->
    <form @submit.prevent="submitForm" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      
      <!-- LEFT COLUMN: Foto Profil & ID Card Preview (4 cols) -->
      <div class="lg:col-span-4 space-y-6">
        <!-- Photo Upload Card -->
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900">Pas Foto Siswa</h3>
            <span class="text-[10px] text-gray-400 font-semibold uppercase">Opsional</span>
          </div>

          <div class="flex flex-col items-center justify-center p-6 bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl group hover:border-emerald-400 transition">
            <div class="w-28 h-28 rounded-2xl overflow-hidden bg-white border border-slate-200 shadow-sm flex items-center justify-center relative mb-3">
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
              <label class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold rounded-xl border border-emerald-200 cursor-pointer transition shadow-xs">
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
                :disabled="deletingPhoto"
                @click="clearPhoto"
                class="px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 border border-rose-200 rounded-xl transition cursor-pointer disabled:opacity-50 inline-flex items-center gap-1"
              >
                <svg v-if="deletingPhoto" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>{{ deletingPhoto ? 'Menghapus...' : 'Hapus' }}</span>
              </button>
            </div>
            <p class="text-[10px] text-gray-400 text-center mt-2">JPG, PNG, atau WebP. Maks 5 MB.</p>
          </div>
        </div>

        <!-- Live ID Card Preview Card -->
        <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-950 p-5 rounded-2xl text-white shadow-lg space-y-4">
          <div class="flex items-center justify-between text-emerald-400 text-xs font-bold">
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
              KARTU SISWA (PREVIEW)
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
              <span v-else class="text-sm font-black text-emerald-300 uppercase">
                {{ getInitials(form.nama_lengkap || 'Siswa') }}
              </span>
            </div>
            <div class="min-w-0 flex-1">
              <h4 class="text-sm font-bold truncate text-white">
                {{ form.nama_lengkap || 'Nama Lengkap Siswa' }}
              </h4>
              <p class="text-[11px] font-mono text-emerald-300">
                NIS: {{ form.nis || '-------' }} • NISN: {{ form.nisn || '-' }}
              </p>
              <div class="flex items-center gap-1.5 mt-1">
                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-white/15 text-slate-200">
                  {{ getSelectedClassName() }}
                </span>
                <span :class="form.jenis_kelamin === 'L' ? 'bg-sky-500/20 text-sky-300' : 'bg-rose-500/20 text-rose-300'" class="px-2 py-0.5 rounded-full text-[9px] font-bold">
                  {{ form.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
                </span>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-white/10 text-[11px] text-slate-300 flex justify-between items-center">
            <span>Status Kesiswaan:</span>
            <span class="font-bold uppercase tracking-wider text-emerald-400">{{ form.status }}</span>
          </div>
        </div>

        <!-- Quick Navigation Card -->
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm space-y-2 text-xs">
          <div class="font-bold text-gray-800 pb-1 border-b border-gray-100 flex items-center justify-between">
            <span>Formulir Kolom Dapodik</span>
            <span class="text-[10px] text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md font-bold">6 Bagian</span>
          </div>
          <ul class="space-y-1.5 text-gray-500">
            <li class="flex items-center gap-2 hover:text-emerald-700 py-1">
              <span class="w-5 h-5 rounded-md bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-[10px]">1</span>
              <span>Identitas Pokok Siswa</span>
            </li>
            <li class="flex items-center gap-2 hover:text-emerald-700 py-1">
              <span class="w-5 h-5 rounded-md bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-[10px]">2</span>
              <span>Rombel & Status Pendaftaran</span>
            </li>
            <li class="flex items-center gap-2 hover:text-emerald-700 py-1">
              <span class="w-5 h-5 rounded-md bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-[10px]">3</span>
              <span>Alamat Domisili & Transportasi</span>
            </li>
            <li class="flex items-center gap-2 hover:text-emerald-700 py-1">
              <span class="w-5 h-5 rounded-md bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-[10px]">4</span>
              <span>Orang Tua & Wali Murid</span>
            </li>
            <li class="flex items-center gap-2 hover:text-emerald-700 py-1">
              <span class="w-5 h-5 rounded-md bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-[10px]">5</span>
              <span>Data Fisik & Kesehatan</span>
            </li>
            <li class="flex items-center gap-2 hover:text-emerald-700 py-1">
              <span class="w-5 h-5 rounded-md bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-[10px]">6</span>
              <span>Bantuan & Rekening Bank</span>
            </li>
          </ul>
        </div>
      </div>

      <!-- RIGHT COLUMN: 6 Form Sections Matching Dapodik Columns (8 cols) -->
      <div class="lg:col-span-8 space-y-6">
        
        <!-- SEKSI 1: IDENTITAS POKOK SISWA (Dapodik Kolom 1-10) -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
          <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
            <div>
              <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">1</span>
                Identitas Pokok Siswa (Dapodik)
              </h3>
              <p class="text-xs text-gray-500 mt-0.5 ml-8">Nama, NIPD/NIS, NISN, NIK, No. KK, Akta Lahir, Tempat/Tgl Lahir, dan Agama.</p>
            </div>
            <span class="text-[10px] text-rose-500 font-semibold">* Wajib diisi</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Lengkap Siswa <span class="text-rose-500">*</span></label>
              <input
                v-model="form.nama_lengkap"
                type="text"
                required
                placeholder="Nama sesuai Akta Lahir / KK"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold text-gray-800 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Panggilan</label>
              <input
                v-model="form.nama_panggilan"
                type="text"
                placeholder="Nama sapaan sehari-hari"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">NIPD / Nomor Induk Siswa (NIS) <span class="text-rose-500">*</span></label>
              <input
                v-model="form.nis"
                type="text"
                required
                placeholder="Contoh: BIM0001"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono font-medium focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">NISN (10 Digit)</label>
              <input
                v-model="form.nisn"
                type="text"
                maxlength="10"
                placeholder="Contoh: 3163985785"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Jenis Kelamin <span class="text-rose-500">*</span></label>
              <div class="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  @click="form.jenis_kelamin = 'L'"
                  :class="form.jenis_kelamin === 'L' ? 'bg-sky-50 border-sky-400 text-sky-700 font-bold shadow-xs' : 'bg-gray-50 border-gray-200 text-gray-600'"
                  class="py-2 px-3 rounded-xl border text-xs flex items-center justify-center gap-1.5 transition cursor-pointer"
                >
                  <span>👦</span>
                  <span>L</span>
                </button>
                <button
                  type="button"
                  @click="form.jenis_kelamin = 'P'"
                  :class="form.jenis_kelamin === 'P' ? 'bg-rose-50 border-rose-400 text-rose-700 font-bold shadow-xs' : 'bg-gray-50 border-gray-200 text-gray-600'"
                  class="py-2 px-3 rounded-xl border text-xs flex items-center justify-center gap-1.5 transition cursor-pointer"
                >
                  <span>👧</span>
                  <span>P</span>
                </button>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">NIK Siswa (16 Digit)</label>
              <input
                v-model="form.nik"
                type="text"
                maxlength="16"
                placeholder="3302..."
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Nomor Kartu Keluarga (No. KK)</label>
              <input
                v-model="form.no_kk"
                type="text"
                maxlength="16"
                placeholder="3302..."
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">No. Registrasi Akta Lahir</label>
              <input
                v-model="form.no_akta_lahir"
                type="text"
                placeholder="Contoh: 3302-LT-..."
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Tempat Lahir <span class="text-rose-500">*</span></label>
              <input
                v-model="form.tempat_lahir"
                type="text"
                required
                placeholder="Contoh: Banyumas"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Tanggal Lahir <span class="text-rose-500">*</span></label>
              <input
                v-model="form.tgl_lahir"
                type="date"
                required
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
              <span v-if="studentAge" class="text-[10px] text-emerald-600 font-semibold block mt-1">
                Usia: {{ studentAge }}
              </span>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Agama & Kepercayaan <span class="text-rose-500">*</span></label>
              <select
                v-model="form.agama"
                required
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white transition"
              >
                <option v-for="r in religions" :key="r.id" :value="r.id">{{ r.nama }}</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Kewarganegaraan</label>
              <select
                v-model="form.kewarganegaraan"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white transition"
              >
                <option value="WNI">WNI (Warga Negara Indonesia)</option>
                <option value="WNA">WNA (Warga Negara Asing)</option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Anak Ke-berapa</label>
              <input
                v-model="form.anak_ke"
                type="number"
                min="1"
                placeholder="1"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Jumlah Saudara Kandung</label>
              <input
                v-model="form.jumlah_saudara"
                type="number"
                min="0"
                placeholder="0"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 text-xs mb-1">Kebutuhan Khusus Siswa (Jika Ada)</label>
            <input
              v-model="form.kebutuhan_khusus"
              type="text"
              placeholder="Kosongkan jika tidak ada / Normal"
              class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
            />
          </div>
        </div>

        <!-- SEKSI 2: PENEMPATAN ROMBEL & STATUS SEKOLAH (Dapodik Kolom 22, 28-30, 39) -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
          <div class="border-b border-gray-100 pb-3">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
              <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">2</span>
              Rombel Saat Ini & Riwayat Pendaftaran
            </h3>
            <p class="text-xs text-gray-500 mt-0.5 ml-8">Rombel Dapodik, tanggal masuk, dan asal sekolah.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Rombel Saat Ini (Kelas)</label>
              <select
                v-model="form.current_class_id"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white transition"
              >
                <option :value="null">-- Belum Masuk Rombel --</option>
                <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nama_kelas || c.nama }}</option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Tanggal Masuk / Bergabung <span class="text-rose-500">*</span></label>
              <input
                v-model="form.tgl_masuk"
                type="date"
                required
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Status Kesiswaan <span class="text-rose-500">*</span></label>
              <select
                v-model="form.status"
                required
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white transition"
              >
                <option value="aktif">Aktif</option>
                <option value="lulus">Lulus</option>
                <option value="mutasi_keluar">Mutasi Keluar</option>
                <option value="drop_out">Drop Out</option>
                <option value="meninggal">Meninggal</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Sekolah Asal (TK / PAUD)</label>
              <input
                v-model="form.asal_sekolah_tk"
                type="text"
                placeholder="Contoh: TK BINA INSAN MANDIRI SCHOOL"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Jenis Pendaftaran</label>
              <select
                v-model="form.jenis_pendaftaran"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white transition"
              >
                <option value="baru">Siswa Baru</option>
                <option value="pindahan">Siswa Pindahan</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">SKHUN (Jika Ada)</label>
              <input
                v-model="form.skhun"
                type="text"
                placeholder="Nomor SKHUN"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">No. Peserta Ujian Nasional</label>
              <input
                v-model="form.no_peserta_un"
                type="text"
                placeholder="Nomor Peserta UN"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">No. Seri Ijazah</label>
              <input
                v-model="form.no_seri_ijazah"
                type="text"
                placeholder="Nomor Seri Ijazah"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>
          </div>
        </div>

        <!-- SEKSI 3: ALAMAT DOMISILI & TRANSPORTASI (Dapodik Kolom 11-21, 41) -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
          <div class="border-b border-gray-100 pb-3">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
              <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">3</span>
              Alamat Domisili, Transportasi & Kontak
            </h3>
            <p class="text-xs text-gray-500 mt-0.5 ml-8">Alamat tempat tinggal, RT/RW, Dusun, Desa/Kelurahan, Kecamatan, Jarak ke Sekolah, dan No. HP.</p>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 text-xs mb-1">Alamat Jalan / Tempat Tinggal <span class="text-rose-500">*</span></label>
            <input
              v-model="form.alamat_jalan"
              type="text"
              required
              placeholder="Jl. / Gang / Blok / No. Rumah"
              class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
            />
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">RT</label>
              <input
                v-model="form.rt"
                type="text"
                placeholder="01"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">RW</label>
              <input
                v-model="form.rw"
                type="text"
                placeholder="02"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Dusun</label>
              <input
                v-model="form.dusun"
                type="text"
                placeholder="Nama Dusun"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Kode Pos</label>
              <input
                v-model="form.kode_pos"
                type="text"
                maxlength="5"
                placeholder="53144"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Kelurahan / Desa</label>
              <input
                v-model="form.desa_kelurahan"
                type="text"
                placeholder="Contoh: Karangklesem"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Kecamatan</label>
              <input
                v-model="form.kecamatan"
                type="text"
                placeholder="Contoh: Purwokerto Selatan"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Kabupaten / Kota <span class="text-rose-500">*</span></label>
              <input
                v-model="form.kab_kota"
                type="text"
                required
                placeholder="Contoh: Banyumas"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Jenis Tinggal</label>
              <select
                v-model="form.tinggal_bersama"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white transition"
              >
                <option value="orang_tua">Bersama Orang Tua</option>
                <option value="wali">Bersama Wali</option>
                <option value="asrama">Asrama / Pesantren</option>
                <option value="kost">Kost</option>
                <option value="lainnya">Lainnya</option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Alat Transportasi</label>
              <select
                v-model="form.transportasi"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white transition"
              >
                <option :value="null">-- Pilih Transportasi --</option>
                <option v-for="t in transports" :key="t.id" :value="t.id">{{ t.nama }}</option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Jarak ke Sekolah (KM)</label>
              <input
                v-model="form.jarak_ke_sekolah_km"
                type="number"
                step="0.1"
                placeholder="Contoh: 1.5"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">No. HP Siswa / Kontak Utama</label>
              <input
                v-model="form.no_hp_siswa"
                type="text"
                placeholder="081234567890"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Telepon Rumah</label>
              <input
                v-model="form.telepon"
                type="text"
                placeholder="0281..."
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">E-Mail</label>
              <input
                v-model="form.email"
                type="email"
                placeholder="nama@email.com"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Titik Lintang (Latitude)</label>
              <input
                v-model="form.latitude"
                type="text"
                placeholder="-7.4243"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Titik Bujur (Longitude)</label>
              <input
                v-model="form.longitude"
                type="text"
                placeholder="109.2301"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 outline-none transition"
              />
            </div>
          </div>
        </div>

        <!-- SEKSI 4: DATA ORANG TUA & WALI (Dapodik Kolom 25-27) -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-5">
          <div class="border-b border-gray-100 pb-3">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
              <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">4</span>
              Data Orang Tua & Wali (Dapodik)
            </h3>
            <p class="text-xs text-gray-500 mt-0.5 ml-8">Profil Ayah Kandung, Ibu Kandung, dan Wali Murid sesuai dokumen resmi.</p>
          </div>

          <!-- Sub-kartu: Data Ayah -->
          <div class="p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
              <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                <span class="text-blue-600">👨</span> DATA AYAH KANDUNG
              </h4>
              <span class="text-[10px] text-slate-500">Kolom Y - AD</span>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Lengkap Ayah</label>
                <input
                  v-model="form.nama_ayah"
                  type="text"
                  placeholder="Nama Lengkap Ayah"
                  class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs font-medium focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">NIK Ayah (16 Digit)</label>
                <input
                  v-model="form.nik_ayah"
                  type="text"
                  maxlength="16"
                  placeholder="3302..."
                  class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Jenjang Pendidikan Ayah</label>
                <input
                  v-model="form.pendidikan_ayah"
                  type="text"
                  placeholder="Contoh: S1 / SMA"
                  class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Pekerjaan Ayah</label>
                <input
                  v-model="form.pekerjaan_ayah"
                  type="text"
                  placeholder="Contoh: Wiraswasta / Karyawan"
                  class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">No. HP / WA Ayah</label>
                <input
                  v-model="form.no_hp_ayah"
                  type="text"
                  placeholder="081234567890"
                  class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
            </div>
          </div>

          <!-- Sub-kartu: Data Ibu -->
          <div class="p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
              <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                <span class="text-rose-500">👩</span> DATA IBU KANDUNG
              </h4>
              <span class="text-[10px] text-slate-500">Kolom AE - AJ</span>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Lengkap Ibu</label>
                <input
                  v-model="form.nama_ibu"
                  type="text"
                  placeholder="Nama Lengkap Ibu"
                  class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs font-medium focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">NIK Ibu (16 Digit)</label>
                <input
                  v-model="form.nik_ibu"
                  type="text"
                  maxlength="16"
                  placeholder="3302..."
                  class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Jenjang Pendidikan Ibu</label>
                <input
                  v-model="form.pendidikan_ibu"
                  type="text"
                  placeholder="Contoh: S1 / D3 / SMA"
                  class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Pekerjaan Ibu</label>
                <input
                  v-model="form.pekerjaan_ibu"
                  type="text"
                  placeholder="Contoh: Ibu Rumah Tangga / Karyawan"
                  class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">No. HP / WA Ibu</label>
                <input
                  v-model="form.no_hp_ibu"
                  type="text"
                  placeholder="081234567890"
                  class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
            </div>
          </div>

          <!-- Sub-kartu: Data Wali (Opsional) -->
          <div class="p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
              <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                <span class="text-amber-500">👥</span> DATA WALI (JIKA TINGGAL BERSAMA WALI)
              </h4>
              <span class="text-[10px] text-slate-400 uppercase">Opsional</span>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Lengkap Wali</label>
                <input
                  v-model="form.nama_wali"
                  type="text"
                  placeholder="Nama Lengkap Wali"
                  class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">NIK Wali</label>
                <input
                  v-model="form.nik_wali"
                  type="text"
                  maxlength="16"
                  placeholder="3302..."
                  class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Pekerjaan Wali</label>
                <input
                  v-model="form.pekerjaan_wali"
                  type="text"
                  placeholder="Pekerjaan Wali"
                  class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- SEKSI 5: DATA FISIK & PERIODIK (Dapodik Kolom 43) -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
          <div class="border-b border-gray-100 pb-3">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
              <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">5</span>
              Data Periodik & Kondisi Fisik Siswa
            </h3>
            <p class="text-xs text-gray-500 mt-0.5 ml-8">Tinggi badan, berat badan, lingkar kepala, dan catatan kesehatan.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 pt-1">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Tinggi Badan (cm)</label>
              <input
                v-model="form.tinggi_cm"
                type="number"
                step="0.1"
                placeholder="Contoh: 125"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Berat Badan (kg)</label>
              <input
                v-model="form.berat_kg"
                type="number"
                step="0.1"
                placeholder="Contoh: 24"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Lingkar Kepala (cm)</label>
              <input
                v-model="form.lingkar_kepala"
                type="number"
                step="0.1"
                placeholder="Contoh: 50"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Golongan Darah</label>
              <select
                v-model="form.golongan_darah"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none bg-white transition"
              >
                <option value="">-- Pilih --</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="AB">AB</option>
                <option value="O">O</option>
                <option value="tidak_tahu">Tidak Tahu</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Riwayat Penyakit (Jika Ada)</label>
              <input
                v-model="form.riwayat_penyakit"
                type="text"
                placeholder="Riwayat penyakit khusus..."
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Alergi (Makanan / Obat)</label>
              <input
                v-model="form.alergi"
                type="text"
                placeholder="Alergi udang, dingin, dll."
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition"
              />
            </div>
          </div>
        </div>

        <!-- SEKSI 6: KESEJAHTERAAN PESERTA DIDIK & BANK (Dapodik Kolom 23-24, 31-37) -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
          <div class="border-b border-gray-100 pb-3">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
              <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">6</span>
              Kesejahteraan Peserta Didik & Rekening Bank (Dapodik)
            </h3>
            <p class="text-xs text-gray-500 mt-0.5 ml-8">Penerima KPS, KIP, PIP, Nomor KKS, dan rekening bank penyalur bantuan.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Penerima KPS / PKH</label>
              <select
                v-model="form.penerima_kps"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none bg-white transition"
              >
                <option value="Tidak">Tidak</option>
                <option value="Ya">Ya (Penerima KPS)</option>
              </select>
            </div>

            <div class="sm:col-span-2">
              <label class="block font-semibold text-gray-700 text-xs mb-1">No. KPS (Jika Menerima)</label>
              <input
                v-model="form.no_kps"
                type="text"
                :disabled="form.penerima_kps === 'Tidak'"
                placeholder="Nomor kartu KPS / PKH"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition disabled:bg-gray-100 disabled:text-gray-400"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Penerima KIP (Kartu Indonesia Pintar)</label>
              <select
                v-model="form.penerima_kip"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none bg-white transition"
              >
                <option value="Tidak">Tidak</option>
                <option value="Ya">Ya (Penerima KIP)</option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Nomor KIP</label>
              <input
                v-model="form.nomor_kip"
                type="text"
                :disabled="form.penerima_kip === 'Tidak'"
                placeholder="Nomor Kartu KIP"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition disabled:bg-gray-100 disabled:text-gray-400"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Tertera di KIP</label>
              <input
                v-model="form.nama_di_kip"
                type="text"
                :disabled="form.penerima_kip === 'Tidak'"
                placeholder="Nama sesuai KIP"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition disabled:bg-gray-100 disabled:text-gray-400"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Nomor KKS</label>
              <input
                v-model="form.nomor_kks"
                type="text"
                placeholder="Nomor Kartu Keluarga Sejahtera"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Layak PIP (Usulan Sekolah)</label>
              <select
                v-model="form.layak_pip"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none bg-white transition"
              >
                <option value="Tidak">Tidak</option>
                <option value="Ya">Ya (Diusulkan Layak PIP)</option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Alasan Layak PIP</label>
              <input
                v-model="form.alasan_layak_pip"
                type="text"
                placeholder="Alasan pengusulan PIP"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition"
              />
            </div>
          </div>

          <!-- Rekening Bank -->
          <div class="p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
              <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                <span>🏦</span> REKENING BANK SISWA (KOLOM AY - BA)
              </h4>
              <span class="text-[10px] text-slate-400 uppercase">Opsional</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Bank</label>
                <input
                  v-model="form.bank"
                  type="text"
                  placeholder="Contoh: BRI / BNI / BSI"
                  class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Nomor Rekening Bank</label>
                <input
                  v-model="form.nomor_rekening_bank"
                  type="text"
                  placeholder="Contoh: 0123-01-..."
                  class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Rekening Atas Nama</label>
                <input
                  v-model="form.rekening_atas_nama"
                  type="text"
                  placeholder="Nama pemilik rekening"
                  class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Bottom Sticky Action Bar -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between gap-4">
          <button
            @click="emit('cancel')"
            type="button"
            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition flex items-center gap-2 cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Kembali ke Daftar Siswa</span>
          </button>

          <div class="flex items-center gap-3">
            <button
              @click="emit('cancel')"
              type="button"
              class="px-4 py-2.5 text-gray-500 hover:text-gray-700 text-xs font-semibold transition cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/25 transition disabled:opacity-50 active:scale-95 cursor-pointer"
            >
              <svg v-if="saving" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <span>{{ saving ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Siswa Baru') }}</span>
            </button>
          </div>
        </div>

      </div>
    </form>
  </div>
</template>
