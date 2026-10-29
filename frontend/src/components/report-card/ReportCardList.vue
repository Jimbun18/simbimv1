<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import api from '../../services/api';
import { useAuthStore } from '../../stores/auth';
import { showConfirm, showWarning } from '../../services/swal';

const props = defineProps<{
  currentSubMenu?: string;
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

// Rekapitulasi Presensi Triwulan
const attendanceStartDate = ref('2026-07-15');
const attendanceEndDate = ref('2026-09-30');

watch(
  () => [props.currentSubMenu, props.selectedSemester],
  ([menu, sem]) => {
    if (menu === 'semester_1') {
      attendanceStartDate.value = '2026-07-15';
      attendanceEndDate.value = '2026-12-20';
    } else if (menu === 'semester_2') {
      attendanceStartDate.value = '2027-01-05';
      attendanceEndDate.value = '2027-06-20';
    } else if (menu === 'triwulan_3' || sem === 'Genap') {
      attendanceStartDate.value = '2027-01-05';
      attendanceEndDate.value = '2027-03-31';
    } else {
      attendanceStartDate.value = '2026-07-15';
      attendanceEndDate.value = '2026-09-30';
    }
  },
  { immediate: true }
);
const syncingAttendance = ref(false);
const lastSyncResult = ref<any | null>(null);

// Modal Edit Presensi Cepat
const showQuickAttModal = ref(false);
const quickAttStudent = ref<any | null>(null);
const quickAttSakit = ref(0);
const quickAttIzin = ref(0);
const quickAttAlpa = ref(0);
const savingQuickAtt = ref(false);

const formatDateDisplay = (dateStr?: string) => {
  if (!dateStr) return '-';
  const parts = dateStr.split('-');
  if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
  return dateStr;
};

const handleSyncClassAttendance = async () => {
  if (!props.selectedClassId) {
    emit('notify', 'Silakan pilih kelas terlebih dahulu.', 'warning');
    return;
  }
  if (!attendanceStartDate.value || !attendanceEndDate.value) {
    emit('notify', 'Tentukan tanggal mulai dan selesai absensi.', 'warning');
    return;
  }
  syncingAttendance.value = true;
  try {
    const res = await api.post('/report-cards/sync-class-attendance', {
      class_id: props.selectedClassId,
      academic_year_id: props.selectedAcademicYearId || undefined,
      semester: props.selectedSemester,
      periode: props.selectedPeriode,
      jenis_rapor: props.jenisRapor,
      tgl_mulai: attendanceStartDate.value,
      tgl_selesai: attendanceEndDate.value,
    });
    if (res.data.success) {
      emit('notify', res.data.message, 'success');
      lastSyncResult.value = res.data.data;
      await loadClassReports();
    }
  } catch (err: any) {
    emit('notify', 'Gagal menyinkronkan presensi: ' + (err.response?.data?.message || err.message), 'error');
  } finally {
    syncingAttendance.value = false;
  }
};



const openQuickAttendanceEdit = (st: any) => {
  if (!st.report_card_id) {
    emit('notify', 'Draf rapor siswa ini belum dibuat. Klik Siapkan Draf Kelas terlebih dahulu.', 'warning');
    return;
  }
  quickAttStudent.value = st;
  quickAttSakit.value = st.kehadiran?.sakit ?? 0;
  quickAttIzin.value = st.kehadiran?.izin ?? 0;
  quickAttAlpa.value = st.kehadiran?.alpa ?? 0;
  showQuickAttModal.value = true;
};

const saveQuickAttendance = async () => {
  if (!quickAttStudent.value?.report_card_id) return;
  savingQuickAtt.value = true;
  try {
    const res = await api.put(`/report-cards/${quickAttStudent.value.report_card_id}`, {
      sakit: Number(quickAttSakit.value) || 0,
      izin: Number(quickAttIzin.value) || 0,
      alpa: Number(quickAttAlpa.value) || 0,
    });
    if (res.data.success) {
      emit('notify', `Presensi ${quickAttStudent.value.nama_lengkap} berhasil diperbarui.`, 'success');
      showQuickAttModal.value = false;
      await loadClassReports();
    }
  } catch (err: any) {
    emit('notify', 'Gagal memperbarui presensi: ' + (err.response?.data?.message || err.message), 'error');
  } finally {
    savingQuickAtt.value = false;
  }
};

// Unified Periode & Semester options based on selected jenis rapor
const unifiedPeriodOptions = computed(() => {
  if (props.jenisRapor === 'kurikulum_merdeka') {
    return [
      { label: 'Semester Gasal (1)', semester: 'Gasal', periode: 'Semester Gasal', value: 'Gasal|Semester Gasal' },
      { label: 'Semester Genap (2)', semester: 'Genap', periode: 'Semester Genap', value: 'Genap|Semester Genap' },
    ];
  }
  return [
    { label: 'Triwulan I (Semester Gasal)', semester: 'Gasal', periode: 'Triwulan I', value: 'Gasal|Triwulan I' },
    { label: 'Triwulan II (Semester Gasal)', semester: 'Gasal', periode: 'Triwulan II', value: 'Gasal|Triwulan II' },
    { label: 'Triwulan III (Semester Genap)', semester: 'Genap', periode: 'Triwulan III', value: 'Genap|Triwulan III' },
    { label: 'Triwulan IV (Semester Genap)', semester: 'Genap', periode: 'Triwulan IV', value: 'Genap|Triwulan IV' },
    { label: 'Semester Gasal (Rekap Gasal)', semester: 'Gasal', periode: 'Semester Gasal', value: 'Gasal|Semester Gasal' },
    { label: 'Semester Genap (Rekap Genap)', semester: 'Genap', periode: 'Semester Genap', value: 'Genap|Semester Genap' },
  ];
});

const currentPeriodValue = computed(() => {
  const match = unifiedPeriodOptions.value.find(
    (opt) => opt.semester === props.selectedSemester && opt.periode === props.selectedPeriode
  );
  if (match) {
    return match.value;
  }
  return unifiedPeriodOptions.value[0]?.value || `${props.selectedSemester}|${props.selectedPeriode}`;
});

const handlePeriodChange = (val: string) => {
  const [sem, per] = val.split('|');
  if (sem && (sem === 'Gasal' || sem === 'Genap')) {
    emit('update:selectedSemester', sem as 'Gasal' | 'Genap');
  }
  if (per) {
    emit('update:selectedPeriode', per);
  }
};

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

// Kelengkapan pengisian rapor seluruh siswa di kelas yang diampu
const classCompleteness = computed(() => {
  const students = classReportData.value?.students || [];
  if (students.length === 0) {
    return {
      total: 0,
      completed: 0,
      isAllCompleted: false,
      incompleteList: [] as string[],
    };
  }

  const incompleteList: string[] = [];
  let completed = 0;

  students.forEach((st: any) => {
    if (st.report_card_id) {
      const isComplete = st.total_mapel > 0 && st.mapel_terisi >= st.total_mapel;
      if (isComplete) {
        completed++;
      } else {
        incompleteList.push(`${st.nama_lengkap} (${st.mapel_terisi}/${st.total_mapel} nilai)`);
      }
    } else {
      incompleteList.push(`${st.nama_lengkap} (Belum dibuatkan rapor)`);
    }
  });

  return {
    total: students.length,
    completed,
    isAllCompleted: students.length > 0 && completed === students.length,
    incompleteList,
  };
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
    props.jenisRapor === 'karakter_lifeskill'
      ? 'Laporan Karakter & Life Skill (17 Butir Penilaian)'
      : 'Rapor Kurikulum Merdeka (18 Mapel Standar)';

  const confirmedInit = await showConfirm(
    'Inisialisasi Rapor Kelas',
    overwrite
      ? `Perbarui seluruh format ${labelJenis} untuk semua siswa di kelas ini? Nilai yang ada tidak akan terhapus.`
      : `Inisialisasi draf ${labelJenis} untuk seluruh siswa di kelas ini periode ${props.selectedPeriode}?`,
    overwrite ? 'Ya, Perbarui' : 'Ya, Inisialisasi'
  );
  if (!confirmedInit) {
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

// Bulk submit class for validation (Wali Kelas -> KS)
// HANYA BISA DILAKUKAN JIKA SELURUH RAPOR SELURUH SISWA YANG DIAMPU SUDAH DIISI LENGKAP
const handleBulkSubmitClass = async () => {
  if (!props.selectedClassId) return;

  if (!classCompleteness.value.isAllCompleted) {
    const sisa = classCompleteness.value.total - classCompleteness.value.completed;
    await showWarning(
      'Rapor Belum Lengkap',
      `Pengajuan ke Kepala Sekolah hanya dapat dilakukan jika seluruh rapor seluruh siswa yang diampu sudah diisi lengkap.\n\nSaat ini baru ${classCompleteness.value.completed} dari ${classCompleteness.value.total} siswa yang sudah terisi lengkap (masih ada ${sisa} siswa yang belum lengkap).`
    );
    return;
  }

  const confirmed = await showConfirm(
    'Pengajuan Rapor Kelas ke KS',
    `Seluruh rapor siswa (${classCompleteness.value.total} siswa) sudah terisi lengkap. Ajukan sekarang ke Kepala Sekolah untuk validasi resmi?`,
    'Ya, Ajukan Semua'
  );
  if (!confirmed) return;

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
  const confirmed = await showConfirm(
    'Validasi Rapor',
    'Validasi dan setujui rapor ini sebagai dokumen resmi (Final)?',
    'Ya, Setujui'
  );
  if (!confirmed) return;
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
  const confirmed = await showConfirm(
    'Validasi Massal',
    `Validasi dan setujui secara massal ${pendingCount.value} rapor siswa yang telah diajukan di kelas ini?`,
    'Ya, Setujui Semua'
  );
  if (!confirmed) return;

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
    await showWarning('Catatan Wajib Diisi', 'Harap masukkan catatan revisi untuk Wali Kelas.');
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

const exportingExcel = ref(false);

// Export & Import Excel (Authenticated Blob Download)
const handleExportExcel = async () => {
  if (!props.selectedClassId) return;
  exportingExcel.value = true;
  try {
    const res = await api.get('/report-cards/export-excel', {
      params: {
        class_id: props.selectedClassId,
        semester: props.selectedSemester,
        academic_year_id: props.selectedAcademicYearId || undefined,
        jenis_rapor: props.jenisRapor,
        periode: props.selectedPeriode || undefined,
      },
      responseType: 'blob',
    });

    const blob = new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    const className = classReportData.value?.class?.nama?.replace(/[^a-zA-Z0-9_-]/g, '_') || 'Kelas';
    link.download = `Rapor_${className}_${props.jenisRapor}_${props.selectedSemester}.xlsx`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
    emit('notify', 'File Excel rekap nilai berhasil diunduh.', 'success');
  } catch (err: any) {
    emit('notify', 'Gagal mengunduh file Excel: ' + (err.response?.data?.message || err.message), 'error');
  } finally {
    exportingExcel.value = false;
  }
};

const triggerFileInput = () => {
  fileInput.value?.click();
};

const handleFileChange = async (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (!target.files || !target.files[0]) return;

  const file = target.files[0];
  const confirmed = await showConfirm(
    'Konfirmasi Impor Nilai',
    `Import data nilai dari file "${file.name}" untuk kelas ini?`,
    'Ya, Impor'
  );
  if (!confirmed) {
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
  <div class="space-y-6">
    <!-- FILTER & ACTION TOOLBAR -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs space-y-4">
      <!-- 3 Primary Filters -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 items-end">
        <!-- Class Selector -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">
              Rombongan Belajar
            </label>
            <span v-if="classReportData?.class?.wali_kelas" class="text-[10px] text-slate-400 font-medium truncate max-w-[170px]" :title="'Wali: ' + classReportData.class.wali_kelas">
              Wali: {{ classReportData.class.wali_kelas }}
            </span>
          </div>
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

        <!-- Unified Periode / Semester -->
        <div v-if="currentSubMenu === 'triwulan_1'">
          <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
            Periode Penilaian
          </label>
          <div class="h-[38px] flex items-center gap-2 bg-emerald-50 border border-emerald-200/80 rounded-xl px-3 text-xs font-bold text-emerald-900 shadow-xs">
            <span class="w-2 h-2 rounded-full bg-emerald-600 shrink-0"></span>
            <span class="truncate">Triwulan 1 (Tengah Semester Gasal)</span>
          </div>
        </div>

        <div v-else-if="currentSubMenu === 'semester_1'">
          <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
            Periode Penilaian
          </label>
          <div class="h-[38px] flex items-center gap-2 bg-blue-50 border border-blue-200/80 rounded-xl px-3 text-xs font-bold text-blue-900 shadow-xs">
            <span class="w-2 h-2 rounded-full bg-blue-600 shrink-0"></span>
            <span class="truncate">Semester 1 (Akhir Semester Gasal)</span>
          </div>
        </div>

        <div v-else-if="currentSubMenu === 'triwulan_3'">
          <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
            Periode Penilaian
          </label>
          <div class="h-[38px] flex items-center gap-2 bg-purple-50 border border-purple-200/80 rounded-xl px-3 text-xs font-bold text-purple-900 shadow-xs">
            <span class="w-2 h-2 rounded-full bg-purple-600 shrink-0"></span>
            <span class="truncate">Triwulan 3 (Tengah Semester Genap)</span>
          </div>
        </div>

        <div v-else-if="currentSubMenu === 'semester_2'">
          <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
            Periode Penilaian
          </label>
          <div class="h-[38px] flex items-center gap-2 bg-teal-50 border border-teal-200/80 rounded-xl px-3 text-xs font-bold text-teal-900 shadow-xs">
            <span class="w-2 h-2 rounded-full bg-teal-600 shrink-0"></span>
            <span class="truncate">Semester 2 (Akhir Semester Genap)</span>
          </div>
        </div>

        <div v-else>
          <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
            Periode Penilaian
          </label>
          <select
            :value="currentPeriodValue"
            @change="handlePeriodChange(($event.target as HTMLSelectElement).value)"
            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
          >
            <option v-for="opt in unifiedPeriodOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </div>
      </div>

      <!-- Action Row: Search & Actions -->
      <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-slate-100">
        <div class="relative w-full sm:w-80">
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
            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            :class="classCompleteness.isAllCompleted
              ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-blue-500/20'
              : 'bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300'"
            :title="classCompleteness.isAllCompleted
              ? 'Ajukan seluruh rapor siswa kelas ini ke Kepala Sekolah untuk validasi'
              : 'Pengajuan hanya bisa dilakukan setelah seluruh rapor seluruh siswa yang diampu sudah diisi lengkap'"
          >
            <span>🚀</span>
            <span v-if="classCompleteness.isAllCompleted">Ajukan ke KS (Lengkap 100%)</span>
            <span v-else>Ajukan ke KS ({{ classCompleteness.completed }}/{{ classCompleteness.total }} Siswa Lengkap)</span>
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
            <span>Validasi Massal ({{ pendingCount }})</span>
          </button>

          <!-- INITIALIZE / PREPARE DRAFT BUTTON -->
          <button
            @click="handleInitializeClass(false)"
            :disabled="loading"
            class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            title="Buat draf rapor untuk seluruh siswa di kelas ini yang belum memiliki draf"
          >
            <span>+</span>
            <span>Siapkan Draf Kelas</span>
          </button>

          <!-- EXPORT EXCEL -->
          <button
            @click="handleExportExcel"
            :disabled="exportingExcel"
            class="px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            title="Download rekap nilai rapor kelas dalam format Excel"
          >
            <svg v-if="exportingExcel" class="animate-spin w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>{{ exportingExcel ? 'Mengunduh...' : 'Export Excel' }}</span>
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

    <!-- REKAP PRESENSI DENGAN RENTANG TANGGAL KUSTOM -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-3.5">
      <!-- Header: Judul & Deskripsi Lengkap -->
      <div class="flex items-start sm:items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-700 flex items-center justify-center text-xl shrink-0">
          📅
        </div>
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2.5 flex-wrap">
            <h3 class="font-extrabold text-sm sm:text-base text-slate-900 tracking-tight">
              Rekapitulasi Kehadiran Siswa (Presensi)
            </h3>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100/80 text-amber-800 border border-amber-200">
              Terhubung ke Rapor {{ selectedPeriode || (currentSubMenu === 'semester_2' ? 'Semester Genap' : currentSubMenu === 'semester_1' ? 'Semester Gasal' : currentSubMenu === 'triwulan_3' ? 'Triwulan 3' : 'Triwulan 1') }}
            </span>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">
            Tentukan rentang tanggal absensi untuk menghitung otomatis jumlah Sakit, Izin, dan Alpa ke dalam rapor siswa kelas ini.
          </p>
        </div>
      </div>

      <!-- Form Toolbar: Input Tanggal & Tombol Sinkronisasi -->
      <div class="p-3 bg-slate-50/80 border border-slate-200/80 rounded-xl flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center gap-3 flex-wrap">
          <div class="flex items-center gap-2">
            <label class="text-xs font-bold text-slate-600 whitespace-nowrap">Tanggal Mulai:</label>
            <input
              v-model="attendanceStartDate"
              type="date"
              class="text-xs font-semibold bg-white border border-slate-200 hover:border-slate-300 focus:border-emerald-500 rounded-xl px-3 py-1.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all shadow-2xs"
            />
          </div>

          <span class="text-xs font-bold text-slate-400">s/d</span>

          <div class="flex items-center gap-2">
            <label class="text-xs font-bold text-slate-600 whitespace-nowrap">Tanggal Selesai:</label>
            <input
              v-model="attendanceEndDate"
              type="date"
              class="text-xs font-semibold bg-white border border-slate-200 hover:border-slate-300 focus:border-emerald-500 rounded-xl px-3 py-1.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all shadow-2xs"
            />
          </div>
        </div>

        <button
          type="button"
          @click="handleSyncClassAttendance"
          :disabled="syncingAttendance || !props.selectedClassId"
          class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 whitespace-nowrap self-start md:self-auto"
          title="Tarik dan perbarui rekap presensi seluruh siswa di kelas ini sesuai rentang tanggal"
        >
          <svg v-if="syncingAttendance" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>{{ syncingAttendance ? 'Menyinkronkan Presensi...' : 'Tarik & Sinkronkan Presensi Kelas' }}</span>
        </button>
      </div>

      <!-- Hasil Singkat Sinkronisasi -->
      <div v-if="lastSyncResult" class="p-3 bg-emerald-50/80 border border-emerald-200 rounded-xl text-xs text-emerald-950 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
        <div class="flex items-center gap-2">
          <span class="text-base">✅</span>
          <span>
            <strong>Presensi Berhasil Ditarik:</strong>
            {{ lastSyncResult.updated_students }} rapor siswa disinkronkan untuk periode
            <strong>{{ formatDateDisplay(lastSyncResult.range?.tgl_mulai) }} s/d {{ formatDateDisplay(lastSyncResult.range?.tgl_selesai) }}</strong>.
          </span>
        </div>
        <div class="flex items-center gap-2 font-bold text-[11px] self-start sm:self-auto flex-wrap">
          <span class="text-emerald-800 bg-white border border-emerald-200 px-2.5 py-1 rounded-lg">Hadir: {{ lastSyncResult.summary?.total_hadir || 0 }}</span>
          <span class="text-amber-800 bg-white border border-amber-200 px-2.5 py-1 rounded-lg">Sakit: {{ lastSyncResult.summary?.total_sakit || 0 }}</span>
          <span class="text-blue-800 bg-white border border-blue-200 px-2.5 py-1 rounded-lg">Izin: {{ lastSyncResult.summary?.total_izin || 0 }}</span>
          <span class="text-rose-800 bg-white border border-rose-200 px-2.5 py-1 rounded-lg">Alpa: {{ lastSyncResult.summary?.total_alpa || 0 }}</span>
        </div>
      </div>
    </div>

    <!-- STUDENTS TABLE CONTAINER -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <!-- INTERACTIVE STATUS TABS & CLASS SUMMARY BAR -->
      <div v-if="classReportData" class="px-4 py-3 bg-slate-50/70 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-3">
        <!-- Status Filter Pills -->
        <div class="flex items-center gap-1.5 overflow-x-auto text-xs font-bold scrollbar-none py-0.5">
          <button
            @click="statusFilter = 'all'"
            class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
            :class="statusFilter === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80'"
          >
            <span>Semua Siswa</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="statusFilter === 'all' ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-600'">
              {{ classReportData.total_students || 0 }}
            </span>
          </button>

          <button
            @click="statusFilter = 'belum_ada'"
            class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
            :class="statusFilter === 'belum_ada' ? 'bg-slate-700 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80'"
          >
            <span>Belum Dibuat</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="statusFilter === 'belum_ada' ? 'bg-slate-600 text-white' : 'bg-slate-100 text-slate-600'">
              {{ classReportData.belum_ada_count || 0 }}
            </span>
          </button>

          <button
            @click="statusFilter = 'draft'"
            class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
            :class="statusFilter === 'draft' ? 'bg-amber-600 text-white shadow-xs' : 'bg-white text-amber-700 hover:bg-amber-50 border border-amber-200/80'"
          >
            <span class="w-1.5 h-1.5 rounded-full" :class="statusFilter === 'draft' ? 'bg-white' : 'bg-amber-500'"></span>
            <span>Draft Wali</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="statusFilter === 'draft' ? 'bg-amber-700 text-white' : 'bg-amber-100 text-amber-800'">
              {{ classReportData.draft_count || 0 }}
            </span>
          </button>

          <button
            @click="statusFilter = 'menunggu_validasi'"
            class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
            :class="statusFilter === 'menunggu_validasi' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-blue-700 hover:bg-blue-50 border border-blue-200/80'"
          >
            <span class="w-1.5 h-1.5 rounded-full" :class="statusFilter === 'menunggu_validasi' ? 'bg-white' : 'bg-blue-500'"></span>
            <span>Menunggu KS</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="statusFilter === 'menunggu_validasi' ? 'bg-blue-700 text-white' : 'bg-blue-100 text-blue-800'">
              {{ classReportData.submitted_count || 0 }}
            </span>
          </button>

          <button
            @click="statusFilter = 'final'"
            class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
            :class="statusFilter === 'final' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-emerald-700 hover:bg-emerald-50 border border-emerald-200/80'"
          >
            <span class="w-1.5 h-1.5 rounded-full" :class="statusFilter === 'final' ? 'bg-white' : 'bg-emerald-500'"></span>
            <span>Disetujui / Final</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="statusFilter === 'final' ? 'bg-emerald-700 text-white' : 'bg-emerald-100 text-emerald-800'">
              {{ classReportData.completed_count || 0 }}
            </span>
          </button>

          <button
            @click="statusFilter = 'perlu_revisi'"
            class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
            :class="statusFilter === 'perlu_revisi' ? 'bg-rose-600 text-white shadow-xs' : 'bg-white text-rose-700 hover:bg-rose-50 border border-rose-200/80'"
          >
            <span class="w-1.5 h-1.5 rounded-full" :class="statusFilter === 'perlu_revisi' ? 'bg-white' : 'bg-rose-500'"></span>
            <span>Perlu Revisi</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="statusFilter === 'perlu_revisi' ? 'bg-rose-700 text-white' : 'bg-rose-100 text-rose-800'">
              {{ classReportData.revision_count || 0 }}
            </span>
          </button>
        </div>

        <!-- Class & Wali Info Summary -->
        <div class="text-xs text-slate-500 flex items-center gap-2">
          <span class="font-bold text-slate-800">{{ classReportData.class?.nama }}</span>
          <span class="text-slate-300">•</span>
          <span>Wali: <strong class="text-slate-700">{{ classReportData.class?.wali_kelas || '-' }}</strong></span>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50/90 border-b border-slate-200/80 text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
              <th class="py-3 px-4 w-12 text-center">No</th>
              <th class="py-3 px-4">Nama Peserta Didik</th>
              <th class="py-3 px-4 w-32">NIS / NISN</th>
              <th class="py-3 px-4 w-40 text-center">Kelengkapan Nilai</th>
              <th class="py-3 px-4 w-44 text-center">Status Validasi</th>
              <th class="py-3 px-4 w-36 text-center">Kehadiran (S/I/A)</th>
              <th class="py-3 px-4 w-56 text-right">Aksi</th>
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
              <td class="py-3.5 px-4 text-center text-slate-400 font-bold">
                {{ st.no_urut }}
              </td>
              <td class="py-3.5 px-4">
                <div class="flex items-center gap-3">
                  <div
                    class="w-8 h-8 rounded-full flex items-center justify-center font-black text-xs shrink-0 shadow-2xs"
                    :class="st.jenis_kelamin === 'L' ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700'"
                  >
                    {{ st.nama_lengkap.charAt(0) }}
                  </div>
                  <div>
                    <div class="font-bold text-slate-900 flex items-center gap-1.5">
                      <span>{{ st.nama_lengkap }}</span>
                      <span v-if="st.jenis_kelamin === 'L'" class="text-[9px] text-blue-600 font-bold bg-blue-50 px-1 py-0.2 rounded border border-blue-200">L</span>
                      <span v-else class="text-[9px] text-pink-600 font-bold bg-pink-50 px-1 py-0.2 rounded border border-pink-200">P</span>
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">
                      Periode: {{ st.periode }}
                    </div>
                    <!-- Catatan revisi alert jika ada -->
                    <div v-if="st.status === 'perlu_revisi' && st.validation_notes" class="mt-1 text-[10px] text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                      ⚠️ Revisi: {{ st.validation_notes }}
                    </div>
                  </div>
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
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-semibold transition-all shadow-xs cursor-pointer"
                        title="Periksa dan evaluasi capaian rapor siswa"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span>Cek Rapor</span>
                      </button>
                      <button
                        @click="handleValidateSingle(st.report_card_id)"
                        :disabled="validatingReport"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold transition-all shadow-xs cursor-pointer disabled:opacity-50"
                        title="Validasi & setujui langsung rapor ini"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Setujui</span>
                      </button>
                      <button
                        @click="openRevisionModal(st.report_card_id)"
                        class="inline-flex items-center gap-1 px-2 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                        title="Kembalikan ke wali kelas dengan catatan perbaikan"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                        <span>Revisi</span>
                      </button>
                    </template>

                    <!-- KONDISI 2: SUDAH FINAL / DISETUJUI -->
                    <template v-else-if="st.status === 'final'">
                      <button
                        @click="emit('open-editor', st.student_id, st.report_card_id)"
                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                        title="Lihat detail rapor yang telah disahkan"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span>Lihat</span>
                      </button>
                      <button
                        @click="openRevisionModal(st.report_card_id)"
                        class="inline-flex items-center gap-1 px-2 py-1 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                        title="Batalkan pengesahan untuk perbaikan darurat"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                        <span>Revisi</span>
                      </button>
                    </template>

                    <!-- KONDISI 3: PERLU REVISI -->
                    <template v-else-if="st.status === 'perlu_revisi'">
                      <button
                        @click="emit('open-editor', st.student_id, st.report_card_id)"
                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                        title="Lihat status perbaikan wali kelas"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span>Cek Revisi</span>
                      </button>
                    </template>

                    <!-- KONDISI 4: DRAFT / BELUM ADA -->
                    <template v-else>
                      <button
                        @click="emit('open-editor', st.student_id, st.report_card_id)"
                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                        title="Tinjau atau bantu pengisian rapor siswa"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>Tinjau / Edit</span>
                      </button>
                    </template>
                  </template>

                  <!-- TAMPILAN UNTUK WALI KELAS (PENGISI RAPOR) -->
                  <template v-else>
                    <button
                      @click="emit('open-editor', st.student_id, st.report_card_id)"
                      class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1 cursor-pointer"
                      :class="st.status === 'final'
                        ? 'bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200'
                        : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs'"
                      :title="st.status === 'final' ? 'Lihat rapor siswa' : 'Isi capaian nilai dan catatan rapor siswa'"
                    >
                      <svg v-if="st.status === 'final'" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                      <span v-if="st.status === 'final'">Lihat</span>
                      <span v-else>Isi Nilai</span>
                    </button>
                  </template>

                  <!-- PRINT BUTTON (JIKA RAPOR SUDAH DIINISIALISASI) -->
                  <button
                    v-if="st.report_card_id"
                    @click="emit('open-print', st.student_id, st.report_card_id)"
                    class="p-1.5 bg-slate-100 hover:bg-slate-200 border border-slate-200 rounded-lg text-slate-700 hover:text-emerald-700 transition-all cursor-pointer"
                    title="Cetak lembar cetak rapor resmi"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
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
