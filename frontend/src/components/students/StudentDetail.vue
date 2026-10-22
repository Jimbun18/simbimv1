<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../services/api';
import { useAuthStore } from '../../stores/auth';
import { formatDateIndo } from '../../utils/date';

const props = defineProps<{
  student: any;
}>();

const emit = defineEmits<{
  (e: 'back'): void;
  (e: 'edit', student: any): void;
}>();

const auth = useAuthStore();
const selectedStudent = ref<any>(props.student);
const detailTab = ref<'identitas' | 'rombel' | 'alamat' | 'ortu' | 'fisik' | 'bantuan' | 'rapor'>('identitas');
const studentReportHistory = ref<any[]>([]);
const loadingReportHistory = ref(false);

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

const getGuardianByRelation = (student: any, hubungan: string) => {
  if (!student || !student.guardians) return null;
  return student.guardians.find((g: any) => g.hubungan === hubungan || g.pivot?.hubungan === hubungan);
};

const getStudentParentPhone = (student: any) => {
  if (!student.guardians || student.guardians.length === 0) return '';
  const primary = student.guardians.find((g: any) => g.pivot?.is_kontak_utama) || student.guardians[0];
  return primary?.no_hp || '';
};

const fetchStudentDetail = async () => {
  try {
    const res = await api.get(`/students/${props.student.id}`);
    if (res.data.success && res.data.data) {
      selectedStudent.value = res.data.data;
    }
  } catch (err) {
    console.error('Gagal memuat detail lengkap siswa:', err);
  }
};

const fetchStudentReportHistory = async () => {
  loadingReportHistory.value = true;
  try {
    const res = await api.get(`/report-cards/student/${props.student.id}/history`);
    if (res.data.success && res.data.data) {
      studentReportHistory.value = res.data.data.timeline || [];
    }
  } catch (err) {
    console.error('Gagal mengambil riwayat rapor siswa:', err);
    studentReportHistory.value = [];
  } finally {
    loadingReportHistory.value = false;
  }
};

onMounted(() => {
  fetchStudentDetail();
  fetchStudentReportHistory();
});
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-200">
    <!-- Top Navigation & Action Banner -->
    <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-3.5">
        <button
          @click="emit('back')"
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
            <button type="button" @click="emit('back')" class="hover:text-emerald-600 hover:underline cursor-pointer">Data Siswa</button>
            <span>/</span>
            <span class="text-emerald-700 font-bold">Detail Profil Siswa (Format Dapodik)</span>
          </div>
          <h1 class="text-lg sm:text-2xl font-black text-gray-900 leading-tight">
            Detail Siswa: {{ selectedStudent.nama_lengkap }}
          </h1>
          <p class="text-xs text-gray-500 mt-0.5">
            Berkas biodata lengkap dan data terpadu kesiswaan resmi sesuai 66 kolom Dapodik Kemdikbud.
          </p>
        </div>
      </div>

      <!-- Top Action Buttons -->
      <div class="flex items-center gap-2.5 self-end sm:self-center">
        <button
          @click="emit('back')"
          type="button"
          class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition flex items-center gap-1.5 cursor-pointer"
        >
          <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Kembali ke Daftar</span>
        </button>
        <button
          v-if="auth.isAdmin"
          @click="emit('edit', selectedStudent)"
          type="button"
          class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl shadow-md shadow-amber-500/25 transition active:scale-95 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
          </svg>
          <span>Edit Siswa Ini</span>
        </button>
      </div>
    </div>

    <!-- Hero Header Card: Ringkasan Siswa -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
      <div class="flex items-center gap-5">
        <!-- Avatar / Foto -->
        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl overflow-hidden bg-slate-100 border-2 border-emerald-500/20 shrink-0 shadow-sm flex items-center justify-center">
          <img
            v-if="selectedStudent.foto_url || selectedStudent.foto_path"
            :src="getPhotoUrl(selectedStudent.foto_url, selectedStudent.foto_path)"
            :alt="selectedStudent.nama_lengkap"
            class="w-full h-full object-cover"
          />
          <span v-else class="text-2xl font-black text-gray-400 uppercase">
            {{ getInitials(selectedStudent.nama_lengkap) }}
          </span>
        </div>

        <!-- Info Pokok -->
        <div class="space-y-1.5">
          <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-xl sm:text-2xl font-black text-gray-900 leading-tight">
              {{ selectedStudent.nama_lengkap }}
            </h2>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
              {{ selectedStudent.status || 'AKTIF' }}
            </span>
            <span
              class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
              :class="selectedStudent.jenis_kelamin === 'L' ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-pink-50 text-pink-700 border-pink-200'"
            >
              {{ selectedStudent.jenis_kelamin === 'L' ? '👦 Laki-laki (L)' : '👧 Perempuan (P)' }}
            </span>
          </div>

          <div class="flex flex-wrap items-center gap-y-1 gap-x-4 text-xs text-gray-600">
            <span class="inline-flex items-center gap-1 font-mono">
              <span class="text-gray-400 font-normal">NIS:</span>
              <strong class="text-gray-800">{{ selectedStudent.nis }}</strong>
            </span>
            <span class="inline-flex items-center gap-1 font-mono">
              <span class="text-gray-400 font-normal">NISN:</span>
              <strong class="text-gray-800">{{ selectedStudent.nisn || '-' }}</strong>
            </span>
            <span class="inline-flex items-center gap-1 font-mono">
              <span class="text-gray-400 font-normal">NIK:</span>
              <strong class="text-gray-800">{{ selectedStudent.nik || '-' }}</strong>
            </span>
            <span class="inline-flex items-center gap-1">
              <span class="text-gray-400">Rombel:</span>
              <strong class="text-emerald-700">{{ selectedStudent.school_class?.nama_kelas || selectedStudent.current_class?.nama_kelas || '-' }}</strong>
            </span>
          </div>

          <p class="text-xs text-gray-400">
            ID Database: #{{ selectedStudent.id }} • Terdaftar sejak: {{ formatDateIndo(selectedStudent.tgl_masuk) || '-' }}
          </p>
        </div>
      </div>

      <div class="flex flex-wrap sm:flex-nowrap gap-3 self-stretch md:self-auto justify-end">
        <div class="px-4 py-3 bg-slate-50 border border-slate-100 rounded-2xl text-center min-w-[120px]">
          <span class="text-[10px] text-gray-400 uppercase font-semibold block">Tempat & Tanggal Lahir</span>
          <span class="text-xs font-bold text-gray-800 block mt-0.5">
            {{ selectedStudent.tempat_lahir || '-' }}, {{ formatDateIndo(selectedStudent.tgl_lahir) }}
          </span>
        </div>
        <div class="px-4 py-3 bg-slate-50 border border-slate-100 rounded-2xl text-center min-w-[120px]">
          <span class="text-[10px] text-gray-400 uppercase font-semibold block">Kontak Utama</span>
          <span class="text-xs font-bold text-emerald-700 block mt-0.5">
            {{ selectedStudent.no_hp_siswa || getStudentParentPhone(selectedStudent) || '-' }}
          </span>
        </div>
      </div>
    </div>

    <!-- Tab Navigation & Content Container -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
      <!-- Tab Navigation Bar -->
      <div class="border-b border-gray-100 bg-slate-50/50 px-4 sm:px-6 flex overflow-x-auto gap-2 text-xs font-semibold">
        <button
          @click="detailTab = 'identitas'"
          :class="detailTab === 'identitas' ? 'border-emerald-600 text-emerald-700 bg-white shadow-xs font-bold' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100/60'"
          class="py-3.5 px-4 border-b-2 rounded-t-xl whitespace-nowrap transition flex items-center gap-2 cursor-pointer"
        >
          <span>👤 Identitas Pokok</span>
        </button>
        <button
          @click="detailTab = 'rombel'"
          :class="detailTab === 'rombel' ? 'border-emerald-600 text-emerald-700 bg-white shadow-xs font-bold' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100/60'"
          class="py-3.5 px-4 border-b-2 rounded-t-xl whitespace-nowrap transition flex items-center gap-2 cursor-pointer"
        >
          <span>🏫 Rombel & Riwayat</span>
        </button>
        <button
          @click="detailTab = 'alamat'"
          :class="detailTab === 'alamat' ? 'border-emerald-600 text-emerald-700 bg-white shadow-xs font-bold' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100/60'"
          class="py-3.5 px-4 border-b-2 rounded-t-xl whitespace-nowrap transition flex items-center gap-2 cursor-pointer"
        >
          <span>📍 Alamat & Kontak</span>
        </button>
        <button
          @click="detailTab = 'ortu'"
          :class="detailTab === 'ortu' ? 'border-emerald-600 text-emerald-700 bg-white shadow-xs font-bold' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100/60'"
          class="py-3.5 px-4 border-b-2 rounded-t-xl whitespace-nowrap transition flex items-center gap-2 cursor-pointer"
        >
          <span>👨‍👩‍👧 Orang Tua & Wali</span>
        </button>
        <button
          @click="detailTab = 'fisik'"
          :class="detailTab === 'fisik' ? 'border-emerald-600 text-emerald-700 bg-white shadow-xs font-bold' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100/60'"
          class="py-3.5 px-4 border-b-2 rounded-t-xl whitespace-nowrap transition flex items-center gap-2 cursor-pointer"
        >
          <span>🩺 Fisik & Periodik</span>
        </button>
        <button
          @click="detailTab = 'bantuan'"
          :class="detailTab === 'bantuan' ? 'border-emerald-600 text-emerald-700 bg-white shadow-xs font-bold' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100/60'"
          class="py-3.5 px-4 border-b-2 rounded-t-xl whitespace-nowrap transition flex items-center gap-2 cursor-pointer"
        >
          <span>💳 Bantuan & Bank</span>
        </button>
        <button
          @click="detailTab = 'rapor'"
          :class="detailTab === 'rapor' ? 'border-emerald-600 text-emerald-700 bg-white shadow-xs font-bold' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100/60'"
          class="py-3.5 px-4 border-b-2 rounded-t-xl whitespace-nowrap transition flex items-center gap-2 cursor-pointer"
        >
          <span>📑 Arsip & Riwayat Rapor</span>
          <span v-if="studentReportHistory.length > 0" class="px-1.5 py-0.5 rounded-full text-[10px] bg-emerald-100 text-emerald-800 font-black">
            {{ studentReportHistory.reduce((acc: number, y: any) => acc + (y.report_cards?.length || 0), 0) }}
          </span>
        </button>
      </div>

      <!-- Tab Content Body (Full Page Height, Spacious) -->
      <div class="p-6 sm:p-8 space-y-6 text-xs">
        <!-- TAB 1: IDENTITAS POKOK -->
        <div v-if="detailTab === 'identitas'" class="space-y-6">
          <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
            <div>
              <h3 class="text-sm font-bold text-gray-900">Identitas Pokok Siswa</h3>
              <p class="text-gray-500 text-[11px]">Sesuai Dapodik Kemdikbud Kolom 1 - 10</p>
            </div>
            <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-[11px] font-bold">Kolom 1 - 10</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Nama Lengkap</span>
              <strong class="text-gray-900 block text-sm mt-0.5">{{ selectedStudent.nama_lengkap }}</strong>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Nama Panggilan</span>
              <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.nama_panggilan || '-' }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Jenis Kelamin</span>
              <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.jenis_kelamin === 'L' ? 'Laki-laki (L)' : 'Perempuan (P)' }}</div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">NIS / NIPD</span>
              <div class="text-gray-900 font-mono font-bold text-sm mt-0.5">{{ selectedStudent.nis }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">NISN</span>
              <div class="text-gray-900 font-mono font-medium text-sm mt-0.5">{{ selectedStudent.nisn || '-' }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">NIK Siswa</span>
              <div class="text-gray-900 font-mono font-medium text-sm mt-0.5">{{ selectedStudent.nik || '-' }}</div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Tempat, Tanggal Lahir</span>
              <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.tempat_lahir || '-' }}, {{ formatDateIndo(selectedStudent.tgl_lahir) }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Agama & Kepercayaan</span>
              <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.religion?.nama || 'Islam' }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Kewarganegaraan</span>
              <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.kewarganegaraan || 'WNI' }}</div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Nomor Kartu Keluarga (KK)</span>
              <div class="text-gray-800 font-mono text-sm mt-0.5">{{ selectedStudent.no_kk || '-' }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">No. Registrasi Akta Lahir</span>
              <div class="text-gray-800 font-mono text-sm mt-0.5">{{ selectedStudent.no_akta_lahir || '-' }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Anak Ke-</span>
              <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.anak_ke ?? 1 }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Jumlah Saudara Kandung</span>
              <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.jumlah_saudara ?? 0 }}</div>
            </div>
          </div>

          <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
            <span class="text-gray-400 block text-[10px] uppercase font-semibold">Kebutuhan Khusus</span>
            <div class="text-gray-800 font-medium mt-1">{{ selectedStudent.kebutuhan_khusus || 'Tidak ada / Normal' }}</div>
          </div>
        </div>

        <!-- TAB 2: ROMBEL & PENDAFTARAN -->
        <div v-if="detailTab === 'rombel'" class="space-y-6">
          <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
            <div>
              <h3 class="text-sm font-bold text-gray-900">Rombongan Belajar & Riwayat Pendaftaran</h3>
              <p class="text-gray-500 text-[11px]">Sesuai Dapodik Kemdikbud Kolom 22, 28-30, 39</p>
            </div>
            <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-[11px] font-bold">Kolom 22, 28-30, 39</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Rombel Saat Ini</span>
              <div class="text-emerald-700 font-bold text-base mt-0.5">
                {{ selectedStudent.school_class?.nama_kelas || selectedStudent.current_class?.nama_kelas || '-' }}
              </div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Tanggal Masuk Sekolah</span>
              <div class="text-gray-800 font-medium text-sm mt-0.5">{{ formatDateIndo(selectedStudent.tgl_masuk) || '-' }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Status Kesiswaan</span>
              <div class="mt-0.5">
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800">
                  {{ selectedStudent.status }}
                </span>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Jenis Pendaftaran</span>
              <div class="text-gray-800 font-medium text-sm mt-0.5 capitalize">{{ selectedStudent.jenis_pendaftaran || 'Siswa Baru' }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Sekolah Asal (TK / PAUD)</span>
              <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.asal_sekolah_tk || '-' }}</div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">SKHUN (Jika Ada)</span>
              <div class="text-gray-800 font-mono text-sm mt-0.5">{{ selectedStudent.skhun || '-' }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">No. Peserta Ujian Nasional</span>
              <div class="text-gray-800 font-mono text-sm mt-0.5">{{ selectedStudent.no_peserta_un || '-' }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">No. Seri Ijazah</span>
              <div class="text-gray-800 font-mono text-sm mt-0.5">{{ selectedStudent.no_seri_ijazah || '-' }}</div>
            </div>
          </div>
        </div>

        <!-- TAB 3: ALAMAT & TRANSPORTASI -->
        <div v-if="detailTab === 'alamat'" class="space-y-6">
          <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
            <div>
              <h3 class="text-sm font-bold text-gray-900">Alamat Domisili, Transportasi & Kontak</h3>
              <p class="text-gray-500 text-[11px]">Sesuai Dapodik Kemdikbud Kolom 11 - 21, 41</p>
            </div>
            <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-[11px] font-bold">Kolom 11 - 21, 41</span>
          </div>

          <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
            <span class="text-gray-400 block text-[10px] uppercase font-semibold">Alamat Jalan Lengkap</span>
            <div class="text-gray-900 font-medium text-sm mt-0.5">{{ selectedStudent.alamat_jalan || '-' }}</div>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">RT / RW</span>
              <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.rt || '-' }} / {{ selectedStudent.rw || '-' }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Dusun</span>
              <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.dusun || '-' }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Kelurahan / Desa</span>
              <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.desa_kelurahan || '-' }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Kecamatan</span>
              <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.kecamatan || '-' }}</div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Kabupaten / Kota</span>
              <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.kab_kota || 'Banyumas' }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Provinsi</span>
              <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.provinsi || 'Jawa Tengah' }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Kode Pos</span>
              <div class="text-gray-800 font-mono text-sm mt-0.5">{{ selectedStudent.kode_pos || '-' }}</div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Tempat Tinggal Bersama</span>
              <div class="text-gray-800 font-medium text-sm mt-0.5 capitalize">{{ selectedStudent.tinggal_bersama?.replace('_', ' ') || 'Orang Tua' }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Moda Transportasi</span>
              <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.transportation?.nama || 'Sepeda Motor / Mobil Pribadi' }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">No. HP / WA Siswa</span>
              <div class="text-emerald-700 font-mono font-bold text-sm mt-0.5">{{ selectedStudent.no_hp_siswa || '-' }}</div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Telepon Rumah</span>
              <div class="text-gray-800 font-mono text-sm mt-0.5">{{ selectedStudent.telepon || '-' }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Email Siswa</span>
              <div class="text-gray-800 text-sm mt-0.5">{{ selectedStudent.email || '-' }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Jarak & Waktu Tempuh</span>
              <div class="text-gray-800 text-sm mt-0.5">
                {{ selectedStudent.jarak_ke_sekolah_km ? `${selectedStudent.jarak_ke_sekolah_km} km` : '-' }} /
                {{ selectedStudent.waktu_tempuh_menit ? `${selectedStudent.waktu_tempuh_menit} menit` : '-' }}
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 4: ORANG TUA & WALI -->
        <div v-if="detailTab === 'ortu'" class="space-y-6">
          <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
            <div>
              <h3 class="text-sm font-bold text-gray-900">Data Orang Tua & Wali Siswa</h3>
              <p class="text-gray-500 text-[11px]">Sesuai Dapodik Kemdikbud Kolom 25 - 27 (Ayah Y-AD, Ibu AE-AJ, Wali AK-AP)</p>
            </div>
            <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-[11px] font-bold">Kolom 25 - 27</span>
          </div>

          <!-- AYAH KANDUNG -->
          <div class="p-5 bg-sky-50/40 rounded-2xl border border-sky-100">
            <div class="flex items-center justify-between border-b border-sky-100 pb-2.5 mb-3">
              <span class="font-bold text-sky-900 text-sm flex items-center gap-2">
                <span>👨</span> Data Ayah Kandung
              </span>
              <span class="text-[10px] text-sky-600 bg-sky-100/60 px-2 py-0.5 rounded-full font-mono">Dapodik Kolom Y-AD</span>
            </div>
            <div v-if="getGuardianByRelation(selectedStudent, 'ayah')" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Nama Lengkap</span>
                <div class="font-bold text-gray-900 text-sm">{{ getGuardianByRelation(selectedStudent, 'ayah')?.nama_lengkap }}</div>
              </div>
              <div>
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">NIK Ayah</span>
                <div class="text-gray-800 font-mono text-sm">{{ getGuardianByRelation(selectedStudent, 'ayah')?.nik || '-' }}</div>
              </div>
              <div>
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">No. HP / WA</span>
                <div class="text-emerald-700 font-mono font-bold text-sm">{{ getGuardianByRelation(selectedStudent, 'ayah')?.no_hp || '-' }}</div>
              </div>
              <div>
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Pekerjaan</span>
                <div class="text-gray-800 text-sm">{{ getGuardianByRelation(selectedStudent, 'ayah')?.pekerjaan || '-' }}</div>
              </div>
              <div>
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Pendidikan Terakhir</span>
                <div class="text-gray-800 text-sm">{{ getGuardianByRelation(selectedStudent, 'ayah')?.pendidikan_terakhir || '-' }}</div>
              </div>
              <div>
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Penghasilan Bulanan</span>
                <div class="text-gray-800 text-sm">{{ getGuardianByRelation(selectedStudent, 'ayah')?.penghasilan_bulanan || '-' }}</div>
              </div>
            </div>
            <div v-else class="text-gray-400 text-xs italic py-2">
              Belum ada data ayah kandung tersimpan.
            </div>
          </div>

          <!-- IBU KANDUNG -->
          <div class="p-5 bg-rose-50/40 rounded-2xl border border-rose-100">
            <div class="flex items-center justify-between border-b border-rose-100 pb-2.5 mb-3">
              <span class="font-bold text-rose-900 text-sm flex items-center gap-2">
                <span>👩</span> Data Ibu Kandung
              </span>
              <span class="text-[10px] text-rose-600 bg-rose-100/60 px-2 py-0.5 rounded-full font-mono">Dapodik Kolom AE-AJ</span>
            </div>
            <div v-if="getGuardianByRelation(selectedStudent, 'ibu')" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Nama Lengkap</span>
                <div class="font-bold text-gray-900 text-sm">{{ getGuardianByRelation(selectedStudent, 'ibu')?.nama_lengkap }}</div>
              </div>
              <div>
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">NIK Ibu</span>
                <div class="text-gray-800 font-mono text-sm">{{ getGuardianByRelation(selectedStudent, 'ibu')?.nik || '-' }}</div>
              </div>
              <div>
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">No. HP / WA</span>
                <div class="text-emerald-700 font-mono font-bold text-sm">{{ getGuardianByRelation(selectedStudent, 'ibu')?.no_hp || '-' }}</div>
              </div>
              <div>
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Pekerjaan</span>
                <div class="text-gray-800 text-sm">{{ getGuardianByRelation(selectedStudent, 'ibu')?.pekerjaan || '-' }}</div>
              </div>
              <div>
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Pendidikan Terakhir</span>
                <div class="text-gray-800 text-sm">{{ getGuardianByRelation(selectedStudent, 'ibu')?.pendidikan_terakhir || '-' }}</div>
              </div>
              <div>
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Penghasilan Bulanan</span>
                <div class="text-gray-800 text-sm">{{ getGuardianByRelation(selectedStudent, 'ibu')?.penghasilan_bulanan || '-' }}</div>
              </div>
            </div>
            <div v-else class="text-gray-400 text-xs italic py-2">
              Belum ada data ibu kandung tersimpan.
            </div>
          </div>

          <!-- WALI MURID -->
          <div class="p-5 bg-amber-50/40 rounded-2xl border border-amber-100">
            <div class="flex items-center justify-between border-b border-amber-100 pb-2.5 mb-3">
              <span class="font-bold text-amber-900 text-sm flex items-center gap-2">
                <span>🧑‍💼</span> Data Wali Murid
              </span>
              <span class="text-[10px] text-amber-600 bg-amber-100/60 px-2 py-0.5 rounded-full font-mono">Dapodik Kolom AK-AP</span>
            </div>
            <div v-if="getGuardianByRelation(selectedStudent, 'wali')" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Nama Lengkap</span>
                <div class="font-bold text-gray-900 text-sm">{{ getGuardianByRelation(selectedStudent, 'wali')?.nama_lengkap }}</div>
              </div>
              <div>
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">NIK Wali</span>
                <div class="text-gray-800 font-mono text-sm">{{ getGuardianByRelation(selectedStudent, 'wali')?.nik || '-' }}</div>
              </div>
              <div>
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">No. HP / WA</span>
                <div class="text-emerald-700 font-mono font-bold text-sm">{{ getGuardianByRelation(selectedStudent, 'wali')?.no_hp || '-' }}</div>
              </div>
              <div>
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Pekerjaan</span>
                <div class="text-gray-800 text-sm">{{ getGuardianByRelation(selectedStudent, 'wali')?.pekerjaan || '-' }}</div>
              </div>
              <div>
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Pendidikan Terakhir</span>
                <div class="text-gray-800 text-sm">{{ getGuardianByRelation(selectedStudent, 'wali')?.pendidikan_terakhir || '-' }}</div>
              </div>
              <div>
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Penghasilan Bulanan</span>
                <div class="text-gray-800 text-sm">{{ getGuardianByRelation(selectedStudent, 'wali')?.penghasilan_bulanan || '-' }}</div>
              </div>
            </div>
            <div v-else class="text-gray-400 text-xs italic py-2">
              Tidak ada data wali murid (siswa tinggal bersama orang tua kandung).
            </div>
          </div>
        </div>

        <!-- TAB 5: FISIK & PERIODIK -->
        <div v-if="detailTab === 'fisik'" class="space-y-6">
          <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
            <div>
              <h3 class="text-sm font-bold text-gray-900">Data Periodik & Kondisi Fisik Siswa</h3>
              <p class="text-gray-500 text-[11px]">Sesuai Dapodik Kemdikbud Kolom 43</p>
            </div>
            <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-[11px] font-bold">Kolom 43</span>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100 text-center">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Tinggi Badan</span>
              <div class="text-emerald-700 font-black text-xl mt-1">
                {{ selectedStudent.tinggi_cm ? `${selectedStudent.tinggi_cm} cm` : '-' }}
              </div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100 text-center">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Berat Badan</span>
              <div class="text-emerald-700 font-black text-xl mt-1">
                {{ selectedStudent.berat_kg ? `${selectedStudent.berat_kg} kg` : '-' }}
              </div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100 text-center">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Lingkar Kepala</span>
              <div class="text-emerald-700 font-black text-xl mt-1">
                {{ selectedStudent.lingkar_kepala ? `${selectedStudent.lingkar_kepala} cm` : '-' }}
              </div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100 text-center">
              <span class="text-rose-600 font-black text-xl mt-1">
                {{ selectedStudent.golongan_darah || '-' }}
              </span>
              <span class="text-gray-400 block text-[10px] uppercase font-semibold mt-0.5">Gol. Darah</span>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Riwayat Penyakit</span>
              <div class="text-gray-800 text-sm mt-1">{{ selectedStudent.riwayat_penyakit || 'Tidak ada riwayat penyakit berat' }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Alergi Makanan / Obat</span>
              <div class="text-gray-800 text-sm mt-1">{{ selectedStudent.alergi || 'Tidak ada alergi' }}</div>
            </div>
          </div>
        </div>

        <!-- TAB 6: BANTUAN & BANK -->
        <div v-if="detailTab === 'bantuan'" class="space-y-6">
          <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
            <div>
              <h3 class="text-sm font-bold text-gray-900">Kesejahteraan Peserta Didik & Rekening Bank</h3>
              <p class="text-gray-500 text-[11px]">Sesuai Dapodik Kemdikbud Kolom 23-24, 31-37</p>
            </div>
            <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-[11px] font-bold">Kolom 23-24, 31-37</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Penerima KPS / PKH</span>
              <div class="text-gray-900 font-bold text-sm mt-0.5">{{ selectedStudent.penerima_kps || 'Tidak' }}</div>
              <div v-if="selectedStudent.no_kps" class="text-xs text-gray-500 font-mono mt-1">No. KPS: {{ selectedStudent.no_kps }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Penerima KIP</span>
              <div class="text-gray-900 font-bold text-sm mt-0.5">{{ selectedStudent.penerima_kip || 'Tidak' }}</div>
              <div v-if="selectedStudent.nomor_kip" class="text-xs text-gray-500 font-mono mt-1">No. KIP: {{ selectedStudent.nomor_kip }}</div>
              <div v-if="selectedStudent.nama_di_kip" class="text-xs text-gray-500 mt-0.5">Nama di KIP: {{ selectedStudent.nama_di_kip }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Nomor KKS</span>
              <div class="text-gray-900 font-mono text-sm mt-0.5">{{ selectedStudent.nomor_kks || '-' }}</div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Layak PIP</span>
              <div class="text-gray-900 font-bold text-sm mt-0.5">{{ selectedStudent.layak_pip || 'Tidak' }}</div>
            </div>
            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Alasan Layak PIP</span>
              <div class="text-gray-800 text-sm mt-0.5">{{ selectedStudent.alasan_layak_pip || '-' }}</div>
            </div>
          </div>

          <!-- Rekening Bank -->
          <div class="p-5 bg-emerald-50/30 rounded-2xl border border-emerald-100 space-y-3">
            <div class="flex items-center gap-2 text-emerald-800 font-bold text-sm">
              <span>🏦 Rekening Bank Penyalur</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="bg-white p-3.5 rounded-xl border border-emerald-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Bank Penyalur</span>
                <div class="text-gray-900 font-bold text-sm mt-0.5">{{ selectedStudent.bank || '-' }}</div>
              </div>
              <div class="bg-white p-3.5 rounded-xl border border-emerald-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Nomor Rekening</span>
                <div class="text-emerald-700 font-mono font-bold text-sm mt-0.5">{{ selectedStudent.nomor_rekening_bank || '-' }}</div>
              </div>
              <div class="bg-white p-3.5 rounded-xl border border-emerald-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Atas Nama</span>
                <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.rekening_atas_nama || '-' }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 7: ARSIP & RIWAYAT RAPOR (TIMELINE PORTFOLIO) -->
        <div v-if="detailTab === 'rapor'" class="space-y-6">
          <div class="border-b border-gray-100 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
              <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span>📑</span>
                <span>Buku Induk & Arsip Rapor Digital</span>
              </h3>
              <p class="text-gray-500 text-[11px] mt-0.5">
                Rekam jejak riwayat perkembangan belajar, intrakurikuler, P5, tematik terpadu, dan karakter siswa per tahun ajaran.
              </p>
            </div>
            <router-link
              to="/rapor"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-xl font-bold text-[11px] border border-emerald-200 transition"
            >
              <span>Buka Menu Olah Rapor</span>
              <span>↗</span>
            </router-link>
          </div>

          <!-- Loading State -->
          <div v-if="loadingReportHistory" class="py-12 text-center text-slate-400">
            <div class="inline-block animate-spin w-6 h-6 border-2 border-emerald-600 border-t-transparent rounded-full mb-2"></div>
            <div>Memuat arsip riwayat rapor siswa...</div>
          </div>

          <!-- Empty State -->
          <div v-else-if="studentReportHistory.length === 0" class="py-12 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
            <div class="text-3xl mb-2">📂</div>
            <div class="text-xs font-bold text-slate-700">Belum Ada Arsip Rapor Tercatat</div>
            <p class="text-[11px] text-slate-400 mt-1 max-w-sm mx-auto">
              Rapor untuk siswa ini belum diinisialisasi atau belum dibuat di menu Rapor Siswa.
            </p>
          </div>

          <!-- Timeline Years -->
          <div v-else class="space-y-6">
            <div
              v-for="yearBlock in studentReportHistory"
              :key="yearBlock.academic_year.id"
              class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden"
            >
              <!-- Year Header -->
              <div class="p-4 bg-gradient-to-r from-slate-50 to-slate-100/70 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-xs shadow-xs">
                    TA
                  </div>
                  <div>
                    <div class="flex items-center gap-2">
                      <span class="text-sm font-black text-slate-900">
                        Tahun Pelajaran {{ yearBlock.academic_year.nama }}
                      </span>
                      <span
                        class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase border"
                        :class="yearBlock.academic_year.status === 'aktif' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'"
                      >
                        {{ yearBlock.academic_year.status }}
                      </span>
                    </div>
                    <div class="text-[11px] text-slate-500 mt-0.5">
                      Rombel: <strong>{{ yearBlock.class?.nama_kelas || yearBlock.class?.nama || '-' }}</strong>
                      <span v-if="yearBlock.nomor_absen"> • No. Absen: {{ yearBlock.nomor_absen }}</span>
                      • Status: <strong class="capitalize">{{ yearBlock.status_kelas }}</strong>
                    </div>
                  </div>
                </div>

                <div class="text-right text-[11px]">
                  <span class="text-slate-500">Total Rapor: </span>
                  <strong class="text-slate-900">{{ yearBlock.report_cards?.length || 0 }}</strong>
                  <span class="mx-1">•</span>
                  <span class="text-emerald-700 font-bold">{{ yearBlock.final_count || 0 }} Disahkan KS</span>
                </div>
              </div>

              <!-- Cards for each report card in this year -->
              <div class="p-4 sm:p-5">
                <div v-if="!yearBlock.report_cards || yearBlock.report_cards.length === 0" class="text-slate-400 text-xs italic py-2">
                  Belum ada rapor yang dibuat pada tahun pelajaran ini.
                </div>
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                  <div
                    v-for="rc in yearBlock.report_cards"
                    :key="rc.id"
                    class="p-4 rounded-2xl border transition-all hover:shadow-xs flex flex-col justify-between"
                    :class="{
                      'bg-emerald-50/30 border-emerald-200': rc.status === 'final',
                      'bg-blue-50/30 border-blue-200': rc.status === 'menunggu_validasi',
                      'bg-amber-50/30 border-amber-200': rc.status === 'draft',
                      'bg-rose-50/30 border-rose-200': rc.status === 'perlu_revisi',
                    }"
                  >
                    <div>
                      <!-- Header badge -->
                      <div class="flex items-center justify-between gap-2 mb-2">
                        <span
                          class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider"
                          :class="{
                            'bg-indigo-100 text-indigo-800': rc.jenis_rapor === 'kurikulum_merdeka',
                            'bg-amber-100 text-amber-800': rc.jenis_rapor === 'triwulan_tematik',
                            'bg-purple-100 text-purple-800': rc.jenis_rapor === 'karakter_lifeskill',
                          }"
                        >
                          {{ rc.jenis_rapor === 'kurikulum_merdeka' ? 'Kurikulum Merdeka' : (rc.jenis_rapor === 'triwulan_tematik' ? 'Tematik Terpadu' : 'Karakter & Life Skill') }}
                        </span>

                        <span
                          class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                          :class="{
                            'bg-emerald-100 text-emerald-800 border-emerald-300': rc.status === 'final',
                            'bg-blue-100 text-blue-800 border-blue-300': rc.status === 'menunggu_validasi',
                            'bg-amber-100 text-amber-800 border-amber-300': rc.status === 'draft',
                            'bg-rose-100 text-rose-800 border-rose-300': rc.status === 'perlu_revisi',
                          }"
                        >
                          {{ rc.status === 'final' ? '✓ Disahkan KS' : (rc.status === 'menunggu_validasi' ? '⏳ Menunggu KS' : (rc.status === 'perlu_revisi' ? '⚠️ Perlu Revisi' : 'Draf')) }}
                        </span>
                      </div>

                      <div class="text-xs font-black text-slate-800">
                        {{ rc.periode || (rc.semester === 'gasal' ? 'Semester Gasal' : 'Semester Genap') }}
                      </div>
                      <div class="text-[11px] text-slate-500 mt-0.5">
                        Fase {{ rc.fase || 'A' }} • {{ rc.grades_count || 0 }} Nilai Aspek/Mapel
                      </div>

                      <div class="mt-2.5 pt-2.5 border-t border-slate-200/60 text-[10px] text-slate-500 space-y-0.5">
                        <div v-if="rc.wali_kelas_nama">Wali Kelas: <strong class="text-slate-700">{{ rc.wali_kelas_nama }}</strong></div>
                        <div v-if="rc.kepala_sekolah_nama">Kepala Sekolah: <strong class="text-slate-700">{{ rc.kepala_sekolah_nama }}</strong></div>
                        <div v-if="rc.validated_at" class="text-emerald-700">Divalidasi: {{ formatDateIndo(rc.validated_at) }}</div>
                      </div>
                    </div>

                    <div class="mt-3 pt-2 flex items-center justify-between gap-2 border-t border-slate-200/60">
                      <router-link
                        :to="`/rapor?class_id=${rc.class_id}&semester=${rc.semester}&jenis_rapor=${rc.jenis_rapor}`"
                        class="px-2.5 py-1 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg text-[11px] font-bold text-slate-700 transition"
                      >
                        Buka di Menu Rapor
                      </router-link>

                      <router-link
                        :to="`/rapor?class_id=${rc.class_id}&semester=${rc.semester}&jenis_rapor=${rc.jenis_rapor}`"
                        class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold shadow-xs transition flex items-center gap-1 cursor-pointer"
                        title="Cetak format resmi dokumen rapor ini"
                      >
                        <span>🖨️</span>
                        <span>Cetak</span>
                      </router-link>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab Bottom Bar -->
      <div class="p-4 sm:p-6 border-t border-gray-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-3">
        <div class="text-xs text-gray-400 font-mono">
          ID Database: #{{ selectedStudent.id }} • Terakhir diperbarui: {{ selectedStudent.updated_at ? formatDateIndo(selectedStudent.updated_at) : '-' }}
        </div>
        <div class="flex items-center gap-3">
          <button
            @click="emit('back')"
            class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition cursor-pointer"
          >
            Kembali ke Daftar
          </button>
          <button
            v-if="auth.isAdmin"
            @click="emit('edit', selectedStudent)"
            class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer"
          >
            Edit Siswa Ini
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
