<script setup lang="ts">
import { ref, computed } from 'vue';
import api from '../../services/api';
import { useAuthStore } from '../../stores/auth';
import { showConfirm, showWarning } from '../../services/swal';
import type { ReportCardDetail, SubjectGrade } from './types';

const props = defineProps<{
  reportCard: ReportCardDetail;
}>();

const emit = defineEmits<{
  (e: 'back'): void;
  (e: 'print', studentId: number, reportCardId: number | null): void;
  (e: 'saved', updated: ReportCardDetail): void;
  (e: 'notify', text: string, type?: 'success' | 'error' | 'warning'): void;
}>();

const auth = useAuthStore();
const activeReportCard = ref<ReportCardDetail>(JSON.parse(JSON.stringify(props.reportCard)));

const activeEditorTab = ref<'grades' | 'koku' | 'ekstra' | 'notes'>('grades');
const saving = ref(false);
const validatingReport = ref(false);
const syncing = ref(false);

// Deteksi apakah rapor saat ini adalah Triwulan 1 atau Triwulan 3 (Karakter & Life Skill)
const isTriwulan1Or3 = computed(() => {
  const p = (activeReportCard.value?.periode || '').toLowerCase().trim();
  return (
    activeReportCard.value?.jenis_rapor === 'karakter_lifeskill' ||
    p.includes('triwulan 1') ||
    p.includes('triwulan i') ||
    p.includes('triwulan_1') ||
    p.includes('triwulan 3') ||
    p.includes('triwulan iii') ||
    p.includes('triwulan_3')
  );
});

// Fitur Catatan Wali & Pengesahan hanya aktif di luar Triwulan 1 & 3 (misal Kurikulum Merdeka Semester)
const showNotesTab = computed(() => {
  return !isTriwulan1Or3.value && activeReportCard.value?.jenis_rapor !== 'karakter_lifeskill';
});

// Modal Revisi
const showRevisionModal = ref(false);
const revisionNoteInput = ref('');

// Auto predicate calculation from score
const calculatePredicate = (score?: number | null | string): string => {
  if (score === null || score === undefined || score === '' || isNaN(Number(score))) return '-';
  const num = Number(score);
  if (num > 92) return 'A';
  if (num > 83) return 'B';
  if (num >= 75) return 'C';
  return 'D';
};

// Computed stats for the report card
const averageScore = computed(() => {
  if (!activeReportCard.value?.grades) return 0;
  const valid = activeReportCard.value.grades.filter(
    (g) => g.nilai_akhir !== null && g.nilai_akhir !== undefined && g.nilai_akhir !== '' && !isNaN(Number(g.nilai_akhir))
  );
  if (valid.length === 0) return 0;
  const sum = valid.reduce((acc, g) => acc + Number(g.nilai_akhir), 0);
  return Math.round((sum / valid.length) * 10) / 10;
});

const filledGradesCount = computed(() => {
  if (!activeReportCard.value?.grades) return 0;
  return activeReportCard.value.grades.filter(
    (g) => (g.nilai_akhir !== null && g.nilai_akhir !== undefined && g.nilai_akhir !== '') || (g.predikat && g.predikat !== '-')
  ).length;
});

// Group grades by Aspek for Karakter & Life Skill
const groupedCharacterGrades = computed(() => {
  if (!activeReportCard.value?.grades) return {};
  const groups: Record<string, SubjectGrade[]> = {};
  activeReportCard.value.grades.forEach((g) => {
    const key = g.aspek || 'Penguatan Karakter';
    if (!groups[key]) groups[key] = [];
    groups[key].push(g);
  });
  return groups;
});

// Save Report Card
const saveReportCard = async (navigateBack = true, statusOverride?: 'draft' | 'menunggu_validasi' | 'final' | 'perlu_revisi') => {
  if (!activeReportCard.value) return;
  saving.value = true;
  try {
    if (statusOverride) {
      activeReportCard.value.status = statusOverride;
    }

    const payload = {
      ...activeReportCard.value,
      grades: activeReportCard.value.grades,
      extracurriculars: activeReportCard.value.extracurriculars,
    };

    const res = await api.put(`/report-cards/${activeReportCard.value.id}`, payload);
    if (res.data.success) {
      activeReportCard.value = JSON.parse(JSON.stringify(res.data.data));
      emit('notify', res.data.message || 'Rapor berhasil disimpan.', 'success');
      emit('saved', activeReportCard.value);
      if (navigateBack) {
        emit('back');
      }
    }
  } catch (err: any) {
    emit('notify', 'Gagal menyimpan rapor: ' + (err.response?.data?.message || err.message), 'error');
  } finally {
    saving.value = false;
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
      activeReportCard.value.status = 'final';
      activeReportCard.value.validated_at = res.data.data.validated_at;
      emit('saved', activeReportCard.value);
    }
  } catch (err: any) {
    emit('notify', 'Gagal memvalidasi rapor: ' + (err.response?.data?.message || err.message), 'error');
  } finally {
    validatingReport.value = false;
  }
};

// Modal minta revisi
const openRevisionModal = () => {
  revisionNoteInput.value = '';
  showRevisionModal.value = true;
};

const handleSendRevision = async () => {
  if (!activeReportCard.value || !revisionNoteInput.value.trim()) {
    await showWarning('Catatan Wajib Diisi', 'Harap masukkan catatan revisi untuk Wali Kelas.');
    return;
  }

  validatingReport.value = true;
  try {
    const res = await api.post(`/report-cards/${activeReportCard.value.id}/request-revision`, {
      notes: revisionNoteInput.value.trim(),
    });
    if (res.data.success) {
      emit('notify', res.data.message, 'success');
      showRevisionModal.value = false;
      activeReportCard.value.status = 'perlu_revisi';
      activeReportCard.value.validation_notes = revisionNoteInput.value.trim();
      emit('saved', activeReportCard.value);
    }
  } catch (err: any) {
    emit('notify', 'Gagal mengirim permintaan revisi: ' + (err.response?.data?.message || err.message), 'error');
  } finally {
    validatingReport.value = false;
  }
};

// Sync Attendance
const syncAttendanceCurrent = async () => {
  if (!activeReportCard.value) return;
  syncing.value = true;
  try {
    const res = await api.post(`/report-cards/${activeReportCard.value.id}/sync-attendance`);
    if (res.data.success) {
      activeReportCard.value.sakit = res.data.data.sakit;
      activeReportCard.value.izin = res.data.data.izin;
      activeReportCard.value.alpa = res.data.data.alpa;
      emit('notify', res.data.message, 'success');
    }
  } catch (err: any) {
    emit('notify', 'Gagal sinkronisasi presensi: ' + (err.response?.data?.message || err.message), 'error');
  } finally {
    syncing.value = false;
  }
};

// Add/Remove Grade or Ekstra Row
const addGradeRow = () => {
  const nextUrutan = (activeReportCard.value.grades.length || 0) + 1;
  activeReportCard.value.grades.push({
    nama_mapel: 'Mata Pelajaran Baru',
    kelompok: 'Umum',
    nilai_akhir: null,
    predikat: null,
    capaian_kompetensi: null,
    urutan: nextUrutan,
  });
};

const removeGradeRow = async (index: number) => {
  const confirmed = await showConfirm(
    'Hapus Aspek / Mapel',
    'Hapus mata pelajaran/aspek ini dari rapor?',
    'Ya, Hapus'
  );
  if (confirmed) {
    activeReportCard.value.grades.splice(index, 1);
  }
};

const addExtracurricularRow = () => {
  const nextUrutan = (activeReportCard.value.extracurriculars.length || 0) + 1;
  activeReportCard.value.extracurriculars.push({
    kegiatan: '',
    predikat: 'Baik',
    keterangan: '',
    urutan: nextUrutan,
  });
};

const removeExtracurricularRow = (index: number) => {
  activeReportCard.value.extracurriculars.splice(index, 1);
};
</script>

<template>
  <div class="space-y-6">
    <!-- EDITOR HEADER CARD -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs font-bold px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-md uppercase">
              {{ activeReportCard.jenis_rapor.replace('_', ' ') }}
            </span>
            <h2 class="text-xl font-black text-slate-900">
              {{ activeReportCard.student?.nama_lengkap }}
            </h2>
            <span
              class="px-2.5 py-0.5 rounded-full text-xs font-extrabold border"
              :class="{
                'bg-amber-50 text-amber-800 border-amber-200': activeReportCard.status === 'draft',
                'bg-blue-50 text-blue-800 border-blue-200': activeReportCard.status === 'menunggu_validasi',
                'bg-emerald-50 text-emerald-800 border-emerald-200': activeReportCard.status === 'final',
                'bg-rose-50 text-rose-800 border-rose-200': activeReportCard.status === 'perlu_revisi',
              }"
            >
              {{ activeReportCard.status.toUpperCase() }}
            </span>
          </div>

          <div class="flex items-center gap-4 text-xs text-slate-500 mt-2 flex-wrap font-medium">
            <span>NISN: <strong class="text-slate-800">{{ activeReportCard.student?.nisn || '-' }}</strong></span>
            <span>Kelas: <strong class="text-slate-800">{{ activeReportCard.school_class?.nama }}</strong></span>
            <span>Fase: <strong class="text-slate-800">{{ activeReportCard.fase }}</strong></span>
            <span>Periode: <strong class="text-slate-800">{{ activeReportCard.periode || activeReportCard.semester }}</strong></span>
            <span v-if="activeReportCard.submitter">Diajukan: <strong class="text-slate-800">{{ activeReportCard.submitter.nama }}</strong></span>
            <span v-if="activeReportCard.validator">Divalidasi: <strong class="text-slate-800">{{ activeReportCard.validator.nama }}</strong></span>
          </div>

          <!-- STATS OVERVIEW CHIPS -->
          <div class="flex items-center gap-3 mt-3 flex-wrap">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-50 border border-slate-200 text-xs">
              <span class="text-slate-500 font-medium">Nilai Rata-rata:</span>
              <strong class="text-slate-900 font-black text-sm">{{ averageScore || '-' }}</strong>
            </div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-50 border border-slate-200 text-xs">
              <span class="text-slate-500 font-medium">Kelengkapan Nilai:</span>
              <strong class="text-emerald-700 font-bold">{{ filledGradesCount }} / {{ activeReportCard.grades?.length || 0 }} mapel</strong>
            </div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-50 border border-slate-200 text-xs">
              <span class="text-slate-500 font-medium">Presensi:</span>
              <span class="font-semibold text-slate-700">S:<strong>{{ activeReportCard.sakit }}</strong> I:<strong>{{ activeReportCard.izin }}</strong> A:<strong>{{ activeReportCard.alpa }}</strong></span>
            </div>
          </div>

          <!-- Catatan revisi alert -->
          <div v-if="activeReportCard.validation_notes" class="mt-3 p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800">
            <strong>Catatan dari Kepala Sekolah:</strong> {{ activeReportCard.validation_notes }}
          </div>

          <!-- KS VALIDASI CALLOUT BANNER -->
          <div v-if="auth.isAdmin && activeReportCard.status === 'menunggu_validasi'" class="mt-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-3">
              <span class="text-2xl">📋</span>
              <div>
                <div class="text-xs font-black text-blue-900">Menunggu Validasi & Persetujuan Kepala Sekolah</div>
                <div class="text-[11px] text-blue-700 mt-0.5">
                  Silakan tinjau seluruh nilai dan capaian siswa di bawah ini. Tekan tombol <strong class="text-emerald-700">Setujui</strong> jika sudah sesuai, atau <strong class="text-rose-700">Minta Revisi</strong> jika ada catatan perbaikan.
                </div>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <button
                @click="handleValidateSingle(activeReportCard.id)"
                :disabled="validatingReport"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black transition-all shadow-md flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
              >
                <span>✓</span>
                <span>Setujui & Sahkan Rapor</span>
              </button>
              <button
                @click="openRevisionModal"
                class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-black transition-all shadow-sm flex items-center gap-1.5 cursor-pointer"
              >
                <span>↩</span>
                <span>Minta Revisi ke Wali Kelas</span>
              </button>
            </div>
          </div>
        </div>

        <!-- ACTIONS IN EDITOR -->
        <div class="flex items-center gap-2 flex-wrap">
          <!-- BACK / CANCEL BUTTON -->
          <button
            type="button"
            @click="emit('back')"
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <span>←</span>
            <span>Kembali ke Daftar</span>
          </button>

          <!-- KS VALIDASI & REVISI (KHUSUS ADMIN / KEPALA SEKOLAH) -->
          <template v-if="auth.isAdmin">
            <button
              v-if="activeReportCard.status !== 'final'"
              @click="handleValidateSingle(activeReportCard.id)"
              :disabled="validatingReport"
              class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            >
              <span>✓</span>
              <span>Validasi & Setujui</span>
            </button>

            <button
              @click="openRevisionModal"
              class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition-all cursor-pointer"
            >
              Minta Revisi
            </button>
          </template>

          <!-- SAVE & BACK BUTTON -->
          <button
            type="button"
            @click="saveReportCard(true)"
            :disabled="saving"
            class="px-4.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black transition-all shadow-md flex items-center gap-2 cursor-pointer disabled:opacity-50"
          >
            <svg v-if="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span v-else>💾</span>
            <span>{{ saving ? 'Menyimpan...' : 'Simpan & Kembali ke Daftar' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- EDITOR NAVIGATION TABS -->
    <div class="flex border-b border-slate-200/80 gap-2 overflow-x-auto pb-1">
      <button
        type="button"
        @click="activeEditorTab = 'grades'"
        class="px-4 py-2 text-xs font-bold rounded-xl transition-all flex items-center gap-2 cursor-pointer"
        :class="activeEditorTab === 'grades' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
      >
        <span>📊</span>
        <span>Penilaian & Nilai ({{ activeReportCard.grades?.length || 0 }})</span>
      </button>

      <button
        v-if="activeReportCard.jenis_rapor === 'kurikulum_merdeka'"
        type="button"
        @click="activeEditorTab = 'koku'"
        class="px-4 py-2 text-xs font-bold rounded-xl transition-all flex items-center gap-2 cursor-pointer"
        :class="activeEditorTab === 'koku' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
      >
        <span>🌱</span>
        <span>Kokurikuler (P5)</span>
      </button>

      <button
        v-if="activeReportCard.jenis_rapor !== 'karakter_lifeskill'"
        type="button"
        @click="activeEditorTab = 'ekstra'"
        class="px-4 py-2 text-xs font-bold rounded-xl transition-all flex items-center gap-2 cursor-pointer"
        :class="activeEditorTab === 'ekstra' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
      >
        <span>⚽</span>
        <span>Ekstrakurikuler & Absensi</span>
      </button>

      <button
        v-if="showNotesTab"
        type="button"
        @click="activeEditorTab = 'notes'"
        class="px-4 py-2 text-xs font-bold rounded-xl transition-all flex items-center gap-2 cursor-pointer"
        :class="activeEditorTab === 'notes' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
      >
        <span>📝</span>
        <span>Catatan Wali & Pengesahan</span>
      </button>
    </div>

    <!-- TAB 1: GRADES / PENILAIAN -->
    <div v-show="activeEditorTab === 'grades'" class="space-y-6">
      <!-- SUB-VIEW A: KURIKULUM MERDEKA -->
      <div v-if="activeReportCard.jenis_rapor === 'kurikulum_merdeka'" class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
          <div>
            <h3 class="font-extrabold text-base text-slate-900">Nilai Capaian Intrakurikuler</h3>
            <p class="text-xs text-slate-500">Mata pelajaran standar Kurikulum Merdeka Fase {{ activeReportCard.fase }}</p>
          </div>
          <button
            type="button"
            @click="addGradeRow"
            class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold transition-all flex items-center gap-1 cursor-pointer"
          >
            <span>+</span>
            <span>Tambah Mapel</span>
          </button>
        </div>

        <div class="space-y-3">
          <div
            v-for="(g, idx) in activeReportCard.grades"
            :key="idx"
            class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-3"
          >
            <div class="flex items-center justify-between gap-3">
              <div class="flex items-center gap-2 flex-1">
                <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center text-xs font-black">
                  {{ idx + 1 }}
                </span>
                <input
                  v-model="g.nama_mapel"
                  type="text"
                  class="font-bold text-xs bg-white border border-slate-200 rounded-xl px-3 py-1.5 flex-1 text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                  placeholder="Nama Mata Pelajaran"
                />
                <select
                  v-model="g.kelompok"
                  class="text-xs bg-white border border-slate-200 rounded-xl px-2 py-1.5 text-slate-700 focus:outline-none"
                >
                  <option value="Umum">Umum</option>
                  <option value="Agama">Agama</option>
                  <option value="Muatan Lokal">Muatan Lokal</option>
                </select>
              </div>

              <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-500">Nilai:</span>
                <input
                  v-model.number="g.nilai_akhir"
                  @input="g.predikat = calculatePredicate(g.nilai_akhir)"
                  type="number"
                  min="0"
                  max="100"
                  step="0.1"
                  placeholder="0-100"
                  class="w-20 text-center font-bold text-xs bg-white border border-slate-200 rounded-xl py-1.5 text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                />
                <span
                  v-if="g.predikat || calculatePredicate(g.nilai_akhir) !== '-'"
                  class="text-[10px] font-black px-2 py-0.5 rounded-md uppercase"
                  :class="{
                    'bg-emerald-100 text-emerald-800 border border-emerald-200': (g.predikat || calculatePredicate(g.nilai_akhir)) === 'A',
                    'bg-blue-100 text-blue-800 border border-blue-200': (g.predikat || calculatePredicate(g.nilai_akhir)) === 'B',
                    'bg-amber-100 text-amber-800 border border-amber-200': (g.predikat || calculatePredicate(g.nilai_akhir)) === 'C',
                    'bg-rose-100 text-rose-800 border border-rose-200': (g.predikat || calculatePredicate(g.nilai_akhir)) === 'D',
                  }"
                >
                  {{ g.predikat || calculatePredicate(g.nilai_akhir) }}
                </span>
                <button
                  type="button"
                  @click="removeGradeRow(idx)"
                  class="text-slate-400 hover:text-rose-600 p-1 cursor-pointer"
                  title="Hapus mata pelajaran ini"
                >
                  &times;
                </button>
              </div>
            </div>

            <div>
              <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                Capaian Kompetensi / Narasi
              </label>
              <textarea
                v-model="g.capaian_kompetensi"
                rows="2"
                placeholder="Deskripsi pencapaian kompetensi siswa pada mapel ini..."
                class="w-full text-xs bg-white border border-slate-200 rounded-xl p-2.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
              ></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- SUB-VIEW B: RAPOR TRIWULAN TEMATIK (5 TEMA BESAR) -->
      <div v-else-if="activeReportCard.jenis_rapor === 'triwulan_tematik'" class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
          <div>
            <h3 class="font-extrabold text-base text-slate-900">5 Klaster Tema Rapor Triwulan Tematik BIM</h3>
            <p class="text-xs text-slate-500">Sesuai format resmi penilaian tematik terpadu SDI Bina Insan Mandiri</p>
          </div>
        </div>

        <div class="space-y-4">
          <div
            v-for="(g, idx) in activeReportCard.grades"
            :key="idx"
            class="p-5 rounded-2xl bg-slate-50/80 border border-slate-200/90 space-y-3"
          >
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200/60 pb-3">
              <div class="space-y-0.5">
                <div class="text-[10px] font-extrabold text-emerald-700 uppercase tracking-wider">
                  Tema {{ idx + 1 }}: {{ g.tema || 'Tematik' }}
                </div>
                <div class="font-black text-sm text-slate-900">
                  {{ g.nama_mapel }}
                </div>
              </div>

              <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5">
                  <span class="text-xs font-bold text-slate-600">Nilai Angka:</span>
                  <input
                    v-model.number="g.nilai_akhir"
                    @input="g.predikat = calculatePredicate(g.nilai_akhir)"
                    type="number"
                    min="0"
                    max="100"
                    class="w-20 text-center font-bold text-xs bg-white border border-slate-200 rounded-xl py-1.5 text-slate-900 focus:outline-none"
                    placeholder="0-100"
                  />
                </div>

                <div class="flex items-center gap-1.5">
                  <span class="text-xs font-bold text-slate-600">Predikat:</span>
                  <select
                    v-model="g.predikat"
                    class="font-black text-xs bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-emerald-800 focus:outline-none"
                  >
                    <option value="">-</option>
                    <option value="A">A (Sangat Baik)</option>
                    <option value="B">B (Baik)</option>
                    <option value="C">C (Cukup)</option>
                    <option value="D">D (Kurang)</option>
                  </select>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">
                Capaian Kompetensi / Tujuan Pembelajaran
              </label>
              <textarea
                v-model="g.capaian_kompetensi"
                rows="3"
                placeholder="Narasi pencapaian ananda dalam klaster tema ini..."
                class="w-full text-xs bg-white border border-slate-200 rounded-xl p-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 leading-relaxed"
              ></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- SUB-VIEW C: LAPORAN PENGUATAN KARAKTER & LIFE SKILL (17 BUTIR) -->
      <div v-else-if="activeReportCard.jenis_rapor === 'karakter_lifeskill'" class="space-y-6">
        <!-- INTERVAL NILAI REFERENSI -->
        <div class="bg-gradient-to-r from-emerald-600 to-teal-700 text-white rounded-3xl p-5 shadow-sm">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h4 class="font-black text-sm uppercase tracking-wider">Interval & Pedoman Nilai Karakter</h4>
              <p class="text-xs text-emerald-100 mt-0.5">Penilaian perkembangan sikap, pembiasaan ibadah, emosi, dan kecakapan hidup</p>
            </div>
            <div class="flex items-center gap-3 text-xs font-bold">
              <span class="bg-white/20 px-2.5 py-1 rounded-xl">A: >92-100 (Sangat Baik)</span>
              <span class="bg-white/20 px-2.5 py-1 rounded-xl">B: >83-92 (Baik)</span>
              <span class="bg-white/20 px-2.5 py-1 rounded-xl">C: 75-83 (Cukup)</span>
              <span class="bg-white/20 px-2.5 py-1 rounded-xl">D: &lt;75 (Kurang)</span>
            </div>
          </div>
        </div>

        <!-- GROUPED BY ASPEK -->
        <div
          v-for="(items, aspekTitle) in groupedCharacterGrades"
          :key="aspekTitle"
          class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4"
        >
          <div class="border-b border-slate-100 pb-3">
            <h3 class="font-black text-sm text-slate-900 uppercase tracking-wider text-emerald-800">
              {{ aspekTitle }}
            </h3>
          </div>

          <div class="space-y-3">
            <div
              v-for="(item, iIdx) in items"
              :key="iIdx"
              class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-2.5"
            >
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="font-bold text-xs text-slate-900 flex items-center gap-2">
                  <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center text-[10px] font-black shrink-0">
                    {{ item.urutan || iIdx + 1 }}
                  </span>
                  <span>{{ item.sub_aspek || item.nama_mapel }}</span>
                </div>

                <div class="flex items-center gap-3 self-end sm:self-auto">
                  <div class="flex items-center gap-1.5">
                    <span class="text-xs font-bold text-slate-500">Nilai:</span>
                    <select
                      v-model="item.predikat"
                      class="font-black text-xs bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-emerald-800 focus:outline-none"
                    >
                      <option value="A">A (Sangat Baik)</option>
                      <option value="B">B (Baik)</option>
                      <option value="C">C (Cukup)</option>
                      <option value="D">D (Kurang)</option>
                    </select>
                  </div>
                </div>
              </div>

              <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                  Pelaksanaan Implementasi
                </label>
                <textarea
                  v-model="item.capaian_kompetensi"
                  rows="2"
                  placeholder="Deskripsi bukti perilaku dan implementasi yang ditunjukkan siswa..."
                  class="w-full text-xs bg-white border border-slate-200 rounded-xl p-2.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                ></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: KOKURIKULER P5 (KHUSUS KURIKULUM MERDEKA) -->
    <div v-show="activeEditorTab === 'koku'" class="space-y-6">
      <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
        <div class="border-b border-slate-100 pb-4">
          <h3 class="font-extrabold text-base text-slate-900">Projek Penguatan Profil Pelajar Pancasila (P5)</h3>
          <p class="text-xs text-slate-500">Tema dan asesmen perkembangan kokurikuler siswa</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tema Projek</label>
            <input
              v-model="activeReportCard.kokurikuler_tema"
              type="text"
              placeholder="misal: Sampahku Bersih Lingkunganku Sehat"
              class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Dimensi Profil</label>
            <input
              v-model="activeReportCard.kokurikuler_dimensi"
              type="text"
              placeholder="misal: Kolaborasi, Kemandirian, Kreativitas"
              class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none"
            />
          </div>

          <div class="md:col-span-2">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Capaian Akhir</label>
            <select
              v-model="activeReportCard.kokurikuler_capaian"
              class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none"
            >
              <option value="Mulai Berkembang">Mulai Berkembang (MB)</option>
              <option value="Sedang Berkembang">Sedang Berkembang (SB)</option>
              <option value="Berkembang Sesuai Harapan">Berkembang Sesuai Harapan (BSH)</option>
              <option value="Sangat Berkembang">Sangat Berkembang (SAB)</option>
            </select>
          </div>

          <div class="md:col-span-2">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Deskripsi Kokurikuler</label>
            <textarea
              v-model="activeReportCard.kokurikuler_deskripsi"
              rows="3"
              placeholder="Deskripsi pencapaian projek kokurikuler..."
              class="w-full bg-white border border-slate-200 rounded-xl p-3 text-xs text-slate-800 focus:outline-none"
            ></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 3: EKSTRAKURIKULER & ABSENSI -->
    <div v-show="activeEditorTab === 'ekstra'" class="space-y-6">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
              <h3 class="font-extrabold text-sm text-slate-900">Kegiatan Ekstrakurikuler</h3>
              <p class="text-xs text-slate-500">Pramuka, Renang, Silat, Mading, Public Speaking, dll.</p>
            </div>
            <button
              type="button"
              @click="addExtracurricularRow"
              class="px-3 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-xl text-xs font-bold transition-all cursor-pointer"
            >
              + Tambah
            </button>
          </div>

          <div class="space-y-3">
            <div
              v-for="(e, eIdx) in activeReportCard.extracurriculars"
              :key="eIdx"
              class="flex items-center gap-3 p-3 bg-slate-50 rounded-2xl border border-slate-200 flex-wrap sm:flex-nowrap"
            >
              <div class="w-full sm:w-44">
                <input
                  v-model="e.kegiatan"
                  type="text"
                  placeholder="Nama Ekstrakurikuler"
                  class="w-full bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-800 focus:outline-none"
                />
              </div>
              <div class="w-32">
                <select
                  v-model="e.predikat"
                  class="w-full bg-white border border-slate-200 rounded-xl px-2 py-1.5 text-xs font-bold text-slate-800 focus:outline-none"
                >
                  <option value="A">A (Sangat Baik)</option>
                  <option value="B">B (Baik)</option>
                  <option value="C">C (Cukup)</option>
                  <option value="-">- (Belum Aktif)</option>
                </select>
              </div>
              <div class="flex-1">
                <input
                  v-model="e.keterangan"
                  type="text"
                  placeholder="Keterangan singkat..."
                  class="w-full bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-xs text-slate-700 focus:outline-none"
                />
              </div>
              <button
                type="button"
                @click="removeExtracurricularRow(eIdx)"
                class="text-slate-400 hover:text-rose-600 p-1 cursor-pointer"
              >
                &times;
              </button>
            </div>
          </div>
        </div>

        <!-- Kehadiran -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
              <h3 class="font-extrabold text-sm text-slate-900">Rekap Ketidakhadiran</h3>
              <p class="text-xs text-slate-500">Jumlah hari absen</p>
            </div>
            <button
              type="button"
              @click="syncAttendanceCurrent"
              :disabled="syncing"
              class="p-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-xl text-xs font-bold transition-all flex items-center gap-1 cursor-pointer"
              title="Tarik presensi dari sistem harian"
            >
              <svg class="w-4 h-4" :class="{ 'animate-spin': syncing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </button>
          </div>

          <div class="space-y-3">
            <div class="flex items-center justify-between p-3 bg-amber-50/60 rounded-2xl border border-amber-100">
              <span class="text-xs font-bold text-amber-900">Sakit (S)</span>
              <div class="flex items-center gap-2">
                <input
                  v-model.number="activeReportCard.sakit"
                  type="number"
                  min="0"
                  class="w-16 text-center font-bold bg-white border border-amber-200 rounded-xl py-1 text-xs"
                />
                <span class="text-xs text-amber-800">hari</span>
              </div>
            </div>

            <div class="flex items-center justify-between p-3 bg-sky-50/60 rounded-2xl border border-sky-100">
              <span class="text-xs font-bold text-sky-900">Izin (I)</span>
              <div class="flex items-center gap-2">
                <input
                  v-model.number="activeReportCard.izin"
                  type="number"
                  min="0"
                  class="w-16 text-center font-bold bg-white border border-sky-200 rounded-xl py-1 text-xs"
                />
                <span class="text-xs text-sky-800">hari</span>
              </div>
            </div>

            <div class="flex items-center justify-between p-3 bg-rose-50/60 rounded-2xl border border-rose-100">
              <span class="text-xs font-bold text-rose-900">Tanpa Keterangan (A)</span>
              <div class="flex items-center gap-2">
                <input
                  v-model.number="activeReportCard.alpa"
                  type="number"
                  min="0"
                  class="w-16 text-center font-bold bg-white border border-rose-200 rounded-xl py-1 text-xs"
                />
                <span class="text-xs text-rose-800">hari</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 4: CATATAN WALI KELAS & PENGESAHAN -->
    <div v-if="showNotesTab" v-show="activeEditorTab === 'notes'" class="space-y-6">
      <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-6">
        <div class="border-b border-slate-100 pb-4">
          <h3 class="font-extrabold text-base text-slate-900">Catatan Wali Kelas & Lembar Pengesahan</h3>
          <p class="text-xs text-slate-500">Pesan motivasi, titimangsa rapor, dan nama pejabat pengesah.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="md:col-span-2">
            <div class="flex items-center justify-between mb-1.5 flex-wrap gap-2">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Catatan Wali Kelas</label>
              <!-- Quick template buttons -->
              <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-[10px] font-bold text-slate-400">Template Cepat:</span>
                <button
                  type="button"
                  @click="activeReportCard.catatan_wali_kelas = `Alhamdulillah, ananda ${activeReportCard.student?.nama_lengkap || ''} menunjukkan perkembangan belajar dan adab yang sangat baik. Pertahankan prestasimu dan senantiasa bersemangat dalam menuntut ilmu. Baarakallahu fiik.`"
                  class="text-[10px] px-2 py-0.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-md border border-emerald-200 font-semibold cursor-pointer"
                >
                  ✨ Berprestasi
                </button>
                <button
                  type="button"
                  @click="activeReportCard.catatan_wali_kelas = `Alhamdulillah ananda ${activeReportCard.student?.nama_lengkap || ''} aktif dan tekun dalam pembelajaran. Tingkatkan terus minat literasi dan kedisiplinan belajar. Semoga Allah mudahkan langkahmu.`"
                  class="text-[10px] px-2 py-0.5 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-md border border-blue-200 font-semibold cursor-pointer"
                >
                  📚 Tekun Belajar
                </button>
                <button
                  type="button"
                  @click="activeReportCard.catatan_wali_kelas = `Ananda ${activeReportCard.student?.nama_lengkap || ''} memiliki potensi yang baik. Dengan fokus dan bimbingan lebih dalam belajar, insya Allah ananda dapat meraih capaian yang lebih optimal.`"
                  class="text-[10px] px-2 py-0.5 bg-amber-50 text-amber-700 hover:bg-amber-100 rounded-md border border-amber-200 font-semibold cursor-pointer"
                >
                  💪 Motivasi Peningkatan
                </button>
              </div>
            </div>
            <textarea
              v-model="activeReportCard.catatan_wali_kelas"
              rows="3"
              placeholder="Pesan bimbingan dan motivasi untuk ananda..."
              class="w-full bg-slate-50/50 border border-slate-200 rounded-xl p-3 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
            ></textarea>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Titimangsa Cetak</label>
            <input
              v-model="activeReportCard.tempat_tanggal"
              type="text"
              placeholder="misal: Purwokerto, 20 Desember 2025"
              class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Wali Kelas</label>
            <input
              v-model="activeReportCard.wali_kelas_nama"
              type="text"
              class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Kepala Sekolah</label>
            <input
              v-model="activeReportCard.kepala_sekolah_nama"
              type="text"
              class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">NIP Kepala Sekolah</label>
            <input
              v-model="activeReportCard.kepala_sekolah_nip"
              type="text"
              class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none"
            />
          </div>
        </div>
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
