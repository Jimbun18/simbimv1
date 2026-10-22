<script setup lang="ts">
import { ref, computed } from 'vue';
import api from '../../services/api';
import { useAuthStore } from '../../stores/auth';
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
const submittingValidation = ref(false);
const validatingReport = ref(false);
const syncing = ref(false);

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
const saveReportCard = async (statusOverride?: 'draft' | 'menunggu_validasi' | 'final' | 'perlu_revisi') => {
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
    }
  } catch (err: any) {
    emit('notify', 'Gagal menyimpan rapor: ' + (err.response?.data?.message || err.message), 'error');
  } finally {
    saving.value = false;
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
      activeReportCard.value.status = 'menunggu_validasi';
      activeReportCard.value.submitted_at = res.data.data.submitted_at;
      emit('saved', activeReportCard.value);
    }
  } catch (err: any) {
    emit('notify', 'Gagal mengajukan rapor: ' + (err.response?.data?.message || err.message), 'error');
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
    alert('Harap masukkan catatan revisi untuk Wali Kelas.');
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

const removeGradeRow = (index: number) => {
  if (confirm('Hapus mata pelajaran/aspek ini dari rapor?')) {
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
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 space-y-6">
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
          <!-- WALI KELAS AJUKAN -->
          <button
            v-if="activeReportCard.status === 'draft' || activeReportCard.status === 'perlu_revisi'"
            @click="submitSingleForValidation(activeReportCard.id)"
            :disabled="saving || submittingValidation"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            <span>🚀</span>
            <span>Ajukan ke KS</span>
          </button>

          <!-- KS VALIDASI & REVISI -->
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

          <!-- SAVE BUTTON -->
          <button
            @click="saveReportCard()"
            :disabled="saving"
            class="px-4 py-2 bg-slate-900 hover:bg-black text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            <svg v-if="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ saving ? 'Menyimpan...' : 'Simpan Draf' }}</span>
          </button>

          <!-- PRINT BUTTON -->
          <button
            @click="emit('print', activeReportCard.student_id, activeReportCard.id)"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <span>🖨️</span>
            <span>Cetak</span>
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
                  type="number"
                  min="0"
                  max="100"
                  step="0.1"
                  placeholder="0-100"
                  class="w-20 text-center font-bold text-xs bg-white border border-slate-200 rounded-xl py-1.5 text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                />
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
    <div v-show="activeEditorTab === 'notes'" class="space-y-6">
      <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-6">
        <div class="border-b border-slate-100 pb-4">
          <h3 class="font-extrabold text-base text-slate-900">Catatan Wali Kelas & Lembar Pengesahan</h3>
          <p class="text-xs text-slate-500">Pesan motivasi, titimangsa rapor, dan nama pejabat pengesah.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="md:col-span-2">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Catatan Wali Kelas</label>
            <textarea
              v-model="activeReportCard.catatan_wali_kelas"
              rows="3"
              placeholder="Pesan bimbingan dan motivasi untuk ananda..."
              class="w-full bg-white border border-slate-200 rounded-xl p-3 text-xs text-slate-800 focus:outline-none"
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
