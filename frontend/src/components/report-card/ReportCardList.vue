<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import api from '../../services/api';
import { useAuthStore } from '../../stores/auth';

const props = defineProps<{
  jenisRapor: 'kurikulum_merdeka' | 'triwulan_tematik' | 'karakter_lifeskill';
  classes: any[];
  academicYears: any[];
  selectedClassId: string | number;
  selectedAcademicYearId: string | number;
  selectedSemester: 'Gasal' | 'Genap';
  selectedPeriode: string;
}>();

const emit = defineEmits<{
  (e: 'update:selectedClassId', val: string | number): void;
  (e: 'update:selectedAcademicYearId', val: string | number): void;
  (e: 'update:selectedSemester', val: 'Gasal' | 'Genap'): void;
  (e: 'update:selectedPeriode', val: string): void;
  (e: 'open-editor', studentId: number, reportCardId?: number | null): void;
  (e: 'open-print', studentId: number, reportCardId: number | null): void;
  (e: 'notify', text: string, type?: 'success' | 'error' | 'warning'): void;
}>();

const auth = useAuthStore();

const loading = ref(false);
const submittingValidation = ref(false);
const validatingReport = ref(false);
const importingExcel = ref(false);

const statusFilter = ref<string>('all');
const searchQuery = ref('');
const classReportData = ref<any | null>(null);

// Modal Revisi (Kepala Sekolah)
const showRevisionModal = ref(false);
const revisionNoteInput = ref('');
const revisionTargetCardId = ref<number | null>(null);

const fileInput = ref<HTMLInputElement | null>(null);

// Periode options based on selected jenis rapor
const periodeOptions = computed(() => {
  if (props.jenisRapor === 'kurikulum_merdeka') {
    return [
      { label: 'Semester Gasal', value: 'Semester Gasal' },
      { label: 'Semester Genap', value: 'Semester Genap' },
    ];
  }
  return [
    { label: 'Triwulan I', value: 'Triwulan I' },
    { label: 'Triwulan II', value: 'Triwulan II' },
    { label: 'Triwulan III', value: 'Triwulan III' },
    { label: 'Triwulan IV', value: 'Triwulan IV' },
    { label: 'Semester Gasal', value: 'Semester Gasal' },
    { label: 'Semester Genap', value: 'Semester Genap' },
  ];
});

// Computed list of filtered students in class
const filteredStudents = computed(() => {
  if (!classReportData.value?.students) return [];
  let list = classReportData.value.students;

  if (statusFilter.value !== 'all') {
    list = list.filter((st: any) => st.status === statusFilter.value);
  }

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase().trim();
    list = list.filter(
      (st: any) =>
        st.nama_lengkap?.toLowerCase().includes(q) ||
        st.nis?.toLowerCase().includes(q) ||
        st.nisn?.toLowerCase().includes(q)
    );
  }

  return list;
});

// Count pending validations
const pendingCount = computed(() => {
  if (!classReportData.value?.students) return 0;
  return classReportData.value.students.filter((st: any) => st.status === 'menunggu_validasi').length;
});

// Count draft or needs revision
const draftOrRevisionCount = computed(() => {
  if (!classReportData.value?.students) return 0;
  return classReportData.value.students.filter(
    (st: any) => st.status === 'draft' || st.status === 'perlu_revisi'
  ).length;
});

// Load class report card overview
const loadClassReports = async () => {
  if (!props.selectedClassId) return;
  loading.value = true;
  try {
    const res = await api.get('/report-cards', {
      params: {
        class_id: props.selectedClassId,
        academic_year_id: props.selectedAcademicYearId || undefined,
        semester: props.selectedSemester,
        jenis_rapor: props.jenisRapor,
        periode: props.selectedPeriode || undefined,
      },
    });
    if (res.data.success) {
      classReportData.value = res.data.data;
    }
  } catch (err: any) {
    emit('notify', 'Gagal mengambil data rapor kelas: ' + (err.response?.data?.message || err.message), 'error');
  } finally {
    loading.value = false;
  }
};

// Batch Initialize Class
const handleInitializeClass = async (overwrite = false) => {
  if (!props.selectedClassId) return;
  const labelJenis =
    props.jenisRapor === 'triwulan_tematik'
      ? 'Rapor Triwulan Tematik BIM (5 Tema Terpadu)'
      : props.jenisRapor === 'karakter_lifeskill'
      ? 'Laporan Karakter & Life Skill (17 Butir Penilaian)'
      : 'Rapor Kurikulum Merdeka (18 Mapel Standar)';

  if (
    !confirm(
      overwrite
        ? `Perbarui seluruh format ${labelJenis} untuk semua siswa di kelas ini? Nilai yang ada tidak akan terhapus.`
        : `Inisialisasi draf ${labelJenis} untuk seluruh siswa di kelas ini periode ${props.selectedPeriode}?`
    )
  ) {
    return;
  }

  loading.value = true;
  try {
    const res = await api.post('/report-cards/initialize-class', {
      class_id: props.selectedClassId,
      academic_year_id: props.selectedAcademicYearId || undefined,
      semester: props.selectedSemester,
      jenis_rapor: props.jenisRapor,
      periode: props.selectedPeriode,
      overwrite_existing: overwrite,
    });
    if (res.data.success) {
      emit('notify', res.data.message, 'success');
      await loadClassReports();
    }
  } catch (err: any) {
    emit('notify', 'Inisialisasi gagal: ' + (err.response?.data?.message || err.message), 'error');
  } finally {
    loading.value = false;
  }
};

// Submit single report card for validation (Wali Kelas -> KS)
const submitSingleForValidation = async (id: number) => {
  if (!confirm('Ajukan rapor ini ke Kepala Sekolah untuk validasi resmi?')) return;
  submittingValidation.value = true;
  try {
    const res = await api.post(`/report-cards/${id}/submit`);
    if (res.data.success) {
      emit('notify', res.data.message, 'success');
      await loadClassReports();
    }
  } catch (err: any) {
    emit('notify', 'Gagal mengajukan rapor: ' + (err.response?.data?.message || err.message), 'error');
  } finally {
    submittingValidation.value = false;
  }
};

// Bulk submit class for validation (Wali Kelas -> KS)
const handleBulkSubmitClass = async () => {
  if (!props.selectedClassId) return;
  if (!confirm(`Ajukan seluruh rapor siswa kelas ini (${draftOrRevisionCount.value} draf) kepada Kepala Sekolah untuk validasi?`)) return;

  submittingValidation.value = true;
  try {
    const res = await api.post('/report-cards/submit-class', {
      class_id: props.selectedClassId,
      academic_year_id: props.selectedAcademicYearId || undefined,
      semester: props.selectedSemester,
      jenis_rapor: props.jenisRapor,
      periode: props.selectedPeriode,
    });
    if (res.data.success) {
      emit('notify', res.data.message, 'success');
      await loadClassReports();
    }
  } catch (err: any) {
    emit('notify', 'Gagal mengajukan rapor kelas: ' + (err.response?.data?.message || err.message), 'error');
  } finally {
    submittingValidation.value = false;
  }
};

// Validate & Approve single report card (KS)
const handleValidateSingle = async (id: number) => {
  if (!confirm('Validasi dan setujui rapor ini sebagai dokumen resmi (Final)?')) return;
  validatingReport.value = true;
  try {
    const res = await api.post(`/report-cards/${id}/validate`);
    if (res.data.success) {
      emit('notify', res.data.message, 'success');
      await loadClassReports();
    }
  } catch (err: any) {
    emit('notify', 'Gagal memvalidasi rapor: ' + (err.response?.data?.message || err.message), 'error');
  } finally {
    validatingReport.value = false;
  }
};

// Bulk validate class (KS)
const handleBulkValidateClass = async () => {
  if (!props.selectedClassId) return;
  if (!confirm(`Validasi dan setujui secara massal ${pendingCount.value} rapor siswa yang telah diajukan di kelas ini?`)) return;

  validatingReport.value = true;
  try {
    const res = await api.post('/report-cards/validate-class', {
      class_id: props.selectedClassId,
      academic_year_id: props.selectedAcademicYearId || undefined,
      semester: props.selectedSemester,
      jenis_rapor: props.jenisRapor,
      periode: props.selectedPeriode,
    });
    if (res.data.success) {
      emit('notify', res.data.message, 'success');
      await loadClassReports();
    }
  } catch (err: any) {
    emit('notify', 'Gagal memvalidasi rapor massal: ' + (err.response?.data?.message || err.message), 'error');
  } finally {
    validatingReport.value = false;
  }
};

// Modal minta revisi
const openRevisionModal = (reportCardId: number) => {
  revisionTargetCardId.value = reportCardId;
  revisionNoteInput.value = '';
  showRevisionModal.value = true;
};

const handleSendRevision = async () => {
  if (!revisionTargetCardId.value || !revisionNoteInput.value.trim()) {
    alert('Harap masukkan catatan revisi untuk Wali Kelas.');
    return;
  }

  validatingReport.value = true;
  try {
    const res = await api.post(`/report-cards/${revisionTargetCardId.value}/request-revision`, {
      notes: revisionNoteInput.value.trim(),
    });
    if (res.data.success) {
      emit('notify', res.data.message, 'success');
      showRevisionModal.value = false;
      await loadClassReports();
    }
  } catch (err: any) {
    emit('notify', 'Gagal mengirim permintaan revisi: ' + (err.response?.data?.message || err.message), 'error');
  } finally {
    validatingReport.value = false;
  }
};

// Export & Import Excel
const handleExportExcel = () => {
  if (!props.selectedClassId) return;
  const url = `/api/report-cards/export-excel?class_id=${props.selectedClassId}&semester=${props.selectedSemester}&academic_year_id=${props.selectedAcademicYearId || ''}&jenis_rapor=${props.jenisRapor}&periode=${encodeURIComponent(props.selectedPeriode || '')}`;
  window.open(url, '_blank');
};

const triggerFileInput = () => {
  fileInput.value?.click();
};

const handleFileChange = async (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (!target.files || !target.files[0]) return;

  const file = target.files[0];
  if (!confirm(`Import data nilai dari file "${file.name}" untuk kelas ini?`)) {
    target.value = '';
    return;
  }

  importingExcel.value = true;
  const formData = new FormData();
  formData.append('file', file);
  formData.append('class_id', String(props.selectedClassId));
  formData.append('semester', props.selectedSemester);
  formData.append('jenis_rapor', props.jenisRapor);
  if (props.selectedAcademicYearId) {
    formData.append('academic_year_id', String(props.selectedAcademicYearId));
  }

  try {
    const res = await api.post('/report-cards/import-excel', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    if (res.data.success) {
      emit('notify', res.data.message, 'success');
      await loadClassReports();
    }
  } catch (err: any) {
    emit('notify', 'Import Excel gagal: ' + (err.response?.data?.message || err.message), 'error');
  } finally {
    importingExcel.value = false;
    target.value = '';
  }
};

watch(
  () => [props.selectedClassId, props.selectedAcademicYearId, props.selectedSemester, props.selectedPeriode, props.jenisRapor],
  () => {
    loadClassReports();
  }
);

onMounted(() => {
  loadClassReports();
});

defineExpose({
  loadClassReports,
});
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 space-y-6">
    <!-- FILTER TOOLBAR -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 items-end">
        <!-- Class Selector -->
        <div>
          <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
            Rombongan Belajar
          </label>
          <select
            :value="selectedClassId"
            @change="emit('update:selectedClassId', ($event.target as HTMLSelectElement).value)"
            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
          >
            <option v-for="c in classes" :key="c.id" :value="c.id">
              {{ c.nama }} (Tingkat {{ c.tingkat }})
            </option>
          </select>
        </div>

        <!-- Academic Year -->
        <div>
          <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
            Tahun Ajaran
          </label>
          <select
            :value="selectedAcademicYearId"
            @change="emit('update:selectedAcademicYearId', ($event.target as HTMLSelectElement).value)"
            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
          >
            <option v-for="y in academicYears" :key="y.id" :value="y.id">
              {{ y.nama }} {{ y.is_aktif ? '★ (Aktif)' : '' }}
            </option>
          </select>
        </div>

        <!-- Semester -->
        <div>
          <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
            Semester
          </label>
          <select
            :value="selectedSemester"
            @change="emit('update:selectedSemester', ($event.target as HTMLSelectElement).value as 'Gasal' | 'Genap')"
            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
          >
            <option value="Gasal">Semester Gasal (1)</option>
            <option value="Genap">Semester Genap (2)</option>
          </select>
        </div>

        <!-- Periode / Triwulan -->
        <div>
          <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
            Periode Rapor
          </label>
          <select
            :value="selectedPeriode"
            @change="emit('update:selectedPeriode', ($event.target as HTMLSelectElement).value)"
            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
          >
            <option v-for="opt in periodeOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </div>

        <!-- Search & Filter Status -->
        <div>
          <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
            Filter Status
          </label>
          <select
            v-model="statusFilter"
            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
          >
            <option value="all">Semua Status ({{ classReportData?.total_students || 0 }})</option>
            <option value="belum_ada">Belum Dibuat ({{ classReportData?.belum_ada_count || 0 }})</option>
            <option value="draft">Draft Wali Kelas ({{ classReportData?.draft_count || 0 }})</option>
            <option value="menunggu_validasi">Menunggu Validasi ({{ classReportData?.submitted_count || 0 }})</option>
            <option value="final">Disetujui / Final ({{ classReportData?.completed_count || 0 }})</option>
            <option value="perlu_revisi">Perlu Revisi ({{ classReportData?.revision_count || 0 }})</option>
          </select>
        </div>
      </div>

      <!-- SEARCH BAR & ACTION BUTTONS -->
      <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-slate-100">
        <div class="relative w-full sm:w-72">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari nama siswa / NIS / NISN..."
            class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
          />
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-end">
          <!-- WALI KELAS: SUBMIT CLASS BULK -->
          <button
            v-if="draftOrRevisionCount > 0"
            @click="handleBulkSubmitClass"
            :disabled="submittingValidation"
            class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            title="Ajukan seluruh draf rapor siswa kelas ini ke Kepala Sekolah"
          >
            <span>🚀</span>
            <span>Ajukan Semua ke KS ({{ draftOrRevisionCount }})</span>
          </button>

          <!-- KEPALA SEKOLAH / ADMIN: VALIDATE CLASS BULK -->
          <button
            v-if="auth.isAdmin && pendingCount > 0"
            @click="handleBulkValidateClass"
            :disabled="validatingReport"
            class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            title="Validasi dan setujui massal semua rapor yang diajukan di kelas ini"
          >
            <span>✓✓</span>
            <span>Validasi Massal 1 Kelas ({{ pendingCount }})</span>
          </button>

          <!-- INITIALIZE BUTTON -->
          <button
            @click="handleInitializeClass(false)"
            :disabled="loading"
            class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            <span>⚡</span>
            <span>Inisialisasi Kelas</span>
          </button>

          <!-- EXPORT EXCEL -->
          <button
            @click="handleExportExcel"
            class="px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
            title="Download rekap nilai rapor kelas dalam format Excel"
          >
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Export Excel</span>
          </button>

          <!-- IMPORT EXCEL -->
          <input ref="fileInput" type="file" accept=".xlsx,.xls" class="hidden" @change="handleFileChange" />
          <button
            @click="triggerFileInput"
            :disabled="importingExcel"
            class="px-3 py-2 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            title="Upload file spreadsheet nilai siswa"
          >
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            <span>{{ importingExcel ? 'Mengimpor...' : 'Import Excel' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- CLASS INFO & KPI CARDS -->
    <div v-if="classReportData" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
      <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Siswa</div>
        <div class="text-xl font-black text-slate-900 mt-1">{{ classReportData.total_students || 0 }}</div>
        <div class="text-[10px] text-slate-500 mt-0.5">{{ classReportData.class?.nama }}</div>
      </div>

      <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Wali Kelas</div>
        <div class="text-xs font-bold text-slate-800 mt-1 truncate" :title="classReportData.class?.wali_kelas">
          {{ classReportData.class?.wali_kelas || '-' }}
        </div>
        <div class="text-[10px] text-slate-400 mt-0.5">NIP: {{ classReportData.class?.wali_kelas_nip || '-' }}</div>
      </div>

      <div class="bg-white rounded-2xl p-4 border border-amber-200/80 bg-amber-50/20 shadow-xs">
        <div class="text-[10px] font-bold text-amber-700 uppercase tracking-wider">Draft Wali Kelas</div>
        <div class="text-xl font-black text-amber-800 mt-1">{{ classReportData.draft_count || 0 }}</div>
        <div class="text-[10px] text-amber-600 mt-0.5">Dalam pengisian</div>
      </div>

      <div class="bg-white rounded-2xl p-4 border border-blue-200/80 bg-blue-50/20 shadow-xs">
        <div class="text-[10px] font-bold text-blue-700 uppercase tracking-wider">Menunggu Validasi</div>
        <div class="text-xl font-black text-blue-800 mt-1">{{ classReportData.submitted_count || 0 }}</div>
        <div class="text-[10px] text-blue-600 mt-0.5">Diajukan ke KS</div>
      </div>

      <div class="bg-white rounded-2xl p-4 border border-emerald-200/80 bg-emerald-50/20 shadow-xs">
        <div class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Disetujui / Final</div>
        <div class="text-xl font-black text-emerald-800 mt-1">{{ classReportData.completed_count || 0 }}</div>
        <div class="text-[10px] text-emerald-600 mt-0.5">Siap cetak resmi</div>
      </div>

      <div class="bg-white rounded-2xl p-4 border border-rose-200/80 bg-rose-50/20 shadow-xs">
        <div class="text-[10px] font-bold text-rose-700 uppercase tracking-wider">Perlu Revisi</div>
        <div class="text-xl font-black text-rose-800 mt-1">{{ classReportData.revision_count || 0 }}</div>
        <div class="text-[10px] text-rose-600 mt-0.5">Catatan dari KS</div>
      </div>
    </div>

    <!-- STUDENTS TABLE -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50/90 border-b border-slate-200/80 text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
              <th class="py-3 px-4 w-12 text-center">No</th>
              <th class="py-3 px-4">Nama Siswa</th>
              <th class="py-3 px-4 w-32">NIS / NISN</th>
              <th class="py-3 px-4 w-40 text-center">Kelengkapan Nilai</th>
              <th class="py-3 px-4 w-44 text-center">Status Validasi</th>
              <th class="py-3 px-4 w-40 text-center">Kehadiran (S/I/A)</th>
              <th class="py-3 px-4 w-52 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
            <tr v-if="loading" class="text-center py-10">
              <td colspan="7" class="py-10 text-slate-400">
                <div class="flex items-center justify-center gap-2">
                  <svg class="animate-spin w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>Memuat daftar rapor siswa...</span>
                </div>
              </td>
            </tr>
            <tr v-else-if="filteredStudents.length === 0" class="text-center py-10">
              <td colspan="7" class="py-10 text-slate-400">
                Tidak ada data siswa ditemukan untuk kriteria filter ini.
              </td>
            </tr>
            <tr
              v-for="st in filteredStudents"
              :key="st.student_id"
              class="hover:bg-slate-50/70 transition-colors"
            >
              <td class="py-3 px-4 text-center text-slate-400 font-bold">
                {{ st.no_urut }}
              </td>
              <td class="py-3 px-4">
                <div class="font-bold text-slate-900 flex items-center gap-2">
                  <span>{{ st.nama_lengkap }}</span>
                  <span v-if="st.jenis_kelamin === 'L'" class="text-[10px] text-blue-600 font-bold bg-blue-50 px-1.5 py-0.2 rounded">L</span>
                  <span v-else class="text-[10px] text-pink-600 font-bold bg-pink-50 px-1.5 py-0.2 rounded">P</span>
                </div>
                <div class="text-[10px] text-slate-400 mt-0.5">
                  Periode: {{ st.periode }}
                </div>
                <!-- Catatan revisi alert jika ada -->
                <div v-if="st.status === 'perlu_revisi' && st.validation_notes" class="mt-1 text-[10px] text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                  ⚠️ Revisi: {{ st.validation_notes }}
                </div>
              </td>
              <td class="py-3 px-4 text-slate-600 font-mono text-[11px]">
                <div>{{ st.nis || '-' }}</div>
                <div class="text-[10px] text-slate-400">NISN: {{ st.nisn || '-' }}</div>
              </td>
              <td class="py-3 px-4 text-center">
                <div class="flex items-center justify-center gap-1.5">
                  <span class="font-bold text-slate-900">{{ st.mapel_terisi }}</span>
                  <span class="text-slate-400">/</span>
                  <span class="text-slate-500">{{ st.total_mapel }}</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1.5 overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-300"
                    :class="st.mapel_terisi === st.total_mapel && st.total_mapel > 0 ? 'bg-emerald-500' : 'bg-amber-500'"
                    :style="{ width: `${st.total_mapel > 0 ? (st.mapel_terisi / st.total_mapel) * 100 : 0}%` }"
                  ></div>
                </div>
              </td>
              <td class="py-3 px-4 text-center">
                <span
                  class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold border"
                  :class="{
                    'bg-slate-100 text-slate-600 border-slate-200': st.status === 'belum_ada',
                    'bg-amber-50 text-amber-800 border-amber-200': st.status === 'draft',
                    'bg-blue-50 text-blue-800 border-blue-200': st.status === 'menunggu_validasi',
                    'bg-emerald-50 text-emerald-800 border-emerald-200': st.status === 'final',
                    'bg-rose-50 text-rose-800 border-rose-200': st.status === 'perlu_revisi',
                  }"
                >
                  <span v-if="st.status === 'belum_ada'">⚪ Belum Dibuat</span>
                  <span v-else-if="st.status === 'draft'">🟡 Draft Wali Kelas</span>
                  <span v-else-if="st.status === 'menunggu_validasi'">🔵 Menunggu KS</span>
                  <span v-else-if="st.status === 'final'">🟢 Disetujui (Final)</span>
                  <span v-else-if="st.status === 'perlu_revisi'">🔴 Perlu Revisi</span>
                </span>
                <div v-if="st.validated_at" class="text-[9px] text-slate-400 mt-0.5">
                  Oleh: {{ st.validated_by_name || 'Kepala Sekolah' }}
                </div>
              </td>
              <td class="py-3 px-4 text-center text-xs">
                <div v-if="st.kehadiran" class="flex items-center justify-center gap-2">
                  <span class="text-amber-700 font-bold" title="Sakit">S:{{ st.kehadiran.sakit }}</span>
                  <span class="text-blue-700 font-bold" title="Izin">I:{{ st.kehadiran.izin }}</span>
                  <span class="text-rose-700 font-bold" title="Alpa">A:{{ st.kehadiran.alpa }}</span>
                </div>
                <span v-else class="text-slate-400">-</span>
              </td>
              <td class="py-3 px-4 text-right">
                <div class="flex items-center justify-end gap-1.5 flex-wrap">
                  <!-- TAMPILAN UNTUK KEPALA SEKOLAH & ADMIN (VALIDATOR) -->
                  <template v-if="auth.isAdmin">
                    <!-- KONDISI 1: MENUNGGU VALIDASI (PRIORITAS REVIEW KS) -->
                    <template v-if="st.status === 'menunggu_validasi'">
                      <button
                        @click="emit('open-editor', st.student_id, st.report_card_id)"
                        class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1 cursor-pointer"
                        title="Periksa dan evaluasi capaian rapor siswa"
                      >
                        <span>🔍</span>
                        <span>Cek Rapor</span>
                      </button>
                      <button
                        @click="handleValidateSingle(st.report_card_id)"
                        :disabled="validatingReport"
                        class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1 cursor-pointer disabled:opacity-50"
                        title="Validasi & setujui langsung rapor ini"
                      >
                        <span>✓</span>
                        <span>Setujui</span>
                      </button>
                      <button
                        @click="openRevisionModal(st.report_card_id)"
                        class="px-2 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold transition-all flex items-center gap-1 cursor-pointer"
                        title="Kembalikan ke wali kelas dengan catatan perbaikan"
                      >
                        <span>↩</span>
                        <span>Revisi</span>
                      </button>
                    </template>

                    <!-- KONDISI 2: SUDAH FINAL / DISETUJUI -->
                    <template v-else-if="st.status === 'final'">
                      <button
                        @click="emit('open-editor', st.student_id, st.report_card_id)"
                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-lg text-xs font-bold transition-all flex items-center gap-1 cursor-pointer"
                        title="Lihat detail rapor yang telah disahkan"
                      >
                        <span>👁️</span>
                        <span>Lihat</span>
                      </button>
                      <button
                        @click="openRevisionModal(st.report_card_id)"
                        class="px-2 py-1 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg text-xs font-bold transition-all cursor-pointer"
                        title="Batalkan pengesahan untuk perbaikan darurat"
                      >
                        ↩ Revisi
                      </button>
                    </template>

                    <!-- KONDISI 3: PERLU REVISI -->
                    <template v-else-if="st.status === 'perlu_revisi'">
                      <button
                        @click="emit('open-editor', st.student_id, st.report_card_id)"
                        class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold transition-all flex items-center gap-1 cursor-pointer"
                        title="Lihat status perbaikan wali kelas"
                      >
                        <span>🔍</span>
                        <span>Cek Revisi</span>
                      </button>
                    </template>

                    <!-- KONDISI 4: DRAFT / BELUM ADA -->
                    <template v-else>
                      <button
                        @click="emit('open-editor', st.student_id, st.report_card_id)"
                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition-all flex items-center gap-1 cursor-pointer"
                        title="Tinjau atau bantu pengisian rapor siswa"
                      >
                        <span>✏️</span>
                        <span>Tinjau / Edit</span>
                      </button>
                    </template>
                  </template>

                  <!-- TAMPILAN UNTUK WALI KELAS (PENGISI RAPOR) -->
                  <template v-else>
                    <button
                      @click="emit('open-editor', st.student_id, st.report_card_id)"
                      class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1 cursor-pointer"
                      :class="st.status === 'final'
                        ? 'bg-slate-100 hover:bg-slate-200 text-slate-800'
                        : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs'"
                      :title="st.status === 'final' ? 'Lihat rapor siswa' : 'Isi capaian nilai dan catatan rapor siswa'"
                    >
                      <span v-if="st.status === 'final'">👁️ Lihat</span>
                      <span v-else>✏️ Isi Nilai</span>
                    </button>

                    <!-- AJUKAN KE KS JIKA STATUS DRAFT / REVISI -->
                    <button
                      v-if="st.report_card_id && (st.status === 'draft' || st.status === 'perlu_revisi')"
                      @click="submitSingleForValidation(st.report_card_id)"
                      :disabled="submittingValidation"
                      class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition-all shadow-xs flex items-center gap-1 cursor-pointer disabled:opacity-50"
                      title="Ajukan rapor ini ke Kepala Sekolah untuk divalidasi"
                    >
                      <span>🚀</span>
                      <span>Ajukan ke KS</span>
                    </button>
                  </template>

                  <!-- PRINT BUTTON (JIKA RAPOR SUDAH DIINISIALISASI) -->
                  <button
                    v-if="st.report_card_id"
                    @click="emit('open-print', st.student_id, st.report_card_id)"
                    class="p-1.5 hover:bg-slate-100 rounded-lg text-slate-600 hover:text-emerald-700 transition-all cursor-pointer"
                    title="Cetak lembar cetak rapor resmi"
                  >
                    🖨️
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL CATATAN REVISI (KEPALA SEKOLAH) -->
    <div
      v-if="showRevisionModal"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2 text-rose-600 font-black">
            <span>⚠️</span>
            <h3 class="text-base text-slate-900 font-extrabold">Kembalikan Rapor untuk Direvisi</h3>
          </div>
          <button @click="showRevisionModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold cursor-pointer">
            &times;
          </button>
        </div>

        <p class="text-xs text-slate-600 leading-relaxed">
          Tuliskan catatan perbaikan atau koreksi nilai untuk Wali Kelas agar dapat diperbaiki sebelum divalidasi kembali.
        </p>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Catatan Revisi dari Kepala Sekolah
          </label>
          <textarea
            v-model="revisionNoteInput"
            rows="4"
            placeholder="misal: Mohon lengkapi narasi capaian pada tema Art & Language, serta sesuaikan nilai muatan lokal."
            class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500/20"
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <button
            type="button"
            @click="showRevisionModal = false"
            class="px-4 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl text-xs font-bold cursor-pointer"
          >
            Batal
          </button>
          <button
            type="button"
            @click="handleSendRevision"
            :disabled="validatingReport"
            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-md cursor-pointer disabled:opacity-50"
          >
            {{ validatingReport ? 'Mengirim...' : 'Kirim Catatan Revisi' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
