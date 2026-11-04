<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import api from '../services/api';
import { useAuthStore } from '../stores/auth';
import { showConfirm } from '../services/swal';

const auth = useAuthStore();

// 18 Mata Pelajaran Resmi Kurikulum Merdeka SDI Bina Insan Mandiri
const FALLBACK_SUBJECTS = [
  { nama: 'IPS', kelompok: 'Umum' },
  { nama: 'Pendidikan Pancasila', kelompok: 'Umum' },
  { nama: 'Bahasa Indonesia', kelompok: 'Umum' },
  { nama: 'Bahasa Inggris', kelompok: 'Umum' },
  { nama: 'Bahasa Arab', kelompok: 'Umum' },
  { nama: 'Matematika', kelompok: 'Umum' },
  { nama: 'Ilmu Pengetahuan Alam', kelompok: 'Umum' },
  { nama: 'Pendidikan Jasmani Olahraga dan Kesehatan', kelompok: 'Umum' },
  { nama: 'Life Skill', kelompok: 'Umum' },
  { nama: 'Gardening', kelompok: 'Umum' },
  { nama: 'Crafting', kelompok: 'Umum' },
  { nama: 'Ekonomi Jasa', kelompok: 'Umum' },
  { nama: 'Fiqih', kelompok: 'Agama' },
  { nama: "Qur'an Hadist", kelompok: 'Agama' },
  { nama: 'Aqidah Akhlak', kelompok: 'Agama' },
  { nama: 'SKI', kelompok: 'Agama' },
  { nama: 'Seni Rupa', kelompok: 'Umum' },
  { nama: 'Bahasa Jawa', kelompok: 'Muatan Lokal' },
];

// Filter State
const classes = ref<any[]>([]);
const academicYears = ref<any[]>([]);
const selectedClassId = ref<string | number>('');
const selectedAcademicYearId = ref<string | number>('');
const selectedSemester = ref<'Gasal' | 'Genap'>('Gasal');
const defaultSubjects = ref<any[]>([...FALLBACK_SUBJECTS]);
const selectedSubjectName = ref<string>('Matematika');

// Status & Role Access State
const isWaliKelas = ref<boolean>(false);
const assignedSubjects = ref<string[]>([]);
const subjectOverview = ref<Record<string, any>>({});
const showOverviewPanel = ref<boolean>(true);

// Dynamic UH Columns (Default 3 sesuai permintaan user)
const uhColumnsCount = ref<number>(3);

// Data State
const students = ref<any[]>([]);
const loading = ref(false);
const saving = ref(false);
const notification = ref<{ message: string; type: 'success' | 'error' | 'warning' } | null>(null);

const showNotification = (message: string, type: 'success' | 'error' | 'warning' = 'success') => {
  notification.value = { message, type };
  setTimeout(() => {
    notification.value = null;
  }, 4500);
};

// Current Subject Submission Info
const currentSubjectSubmission = computed(() => {
  return subjectOverview.value[selectedSubjectName.value] || null;
});

// Apakah formulir saat ini terkunci bagi guru yang sedang login
const isLockedForCurrentTeacher = computed(() => {
  // Jika Wali Kelas atau Admin, tidak pernah terkunci
  if (isWaliKelas.value) return false;
  // Jika Guru Mapel biasa dan nilai sudah berstatus 'submitted' -> otomatis TERKUNCI!
  return currentSubjectSubmission.value?.status === 'submitted';
});

// Predicate calculation
const calculatePredicate = (score?: number | null): string => {
  if (score === null || score === undefined || isNaN(score)) return '-';
  if (score > 92) return 'A';
  if (score > 83) return 'B';
  if (score >= 75) return 'C';
  return 'D';
};

// Formula NR = (3 * RUH + 2 * PSAS) / 5
const calculateStudentGrade = (st: any) => {
  const validUH = (st.nilai_uh || [])
    .map((v: any) => (v !== null && v !== undefined && v !== '' ? Number(v) : null))
    .filter((v: any) => v !== null && !isNaN(v) && v >= 0);

  const ruh =
    validUH.length > 0
      ? Math.round((validUH.reduce((a: number, b: number) => a + b, 0) / validUH.length) * 10) / 10
      : null;
  st.rata_uh = ruh;

  const psas =
    st.nilai_sas !== null && st.nilai_sas !== undefined && st.nilai_sas !== ''
      ? Number(st.nilai_sas)
      : null;
  st.nilai_sas = psas;

  if (typeof ruh === 'number' && typeof psas === 'number') {
    st.nilai_akhir = Math.round((3 * ruh + 2 * psas) / 5);
  } else if (typeof ruh === 'number') {
    st.nilai_akhir = Math.round(ruh);
  } else if (typeof psas === 'number') {
    st.nilai_akhir = Math.round(psas);
  } else {
    st.nilai_akhir = null;
  }

  st.predikat = st.nilai_akhir !== null ? calculatePredicate(st.nilai_akhir) : '-';
};

// Fetch initial master data
const fetchMasterData = async () => {
  try {
    const [clsRes, ayRes, subRes] = await Promise.all([
      api.get('/classes'),
      api.get('/academic-years'),
      api.get('/report-cards/default-subjects'),
    ]);

    classes.value = clsRes.data.data || [];
    academicYears.value = ayRes.data.data || [];

    // Ekstraksi daftar mata pelajaran dari API atau fallback
    const rawSubj = subRes.data?.data;
    if (Array.isArray(rawSubj) && rawSubj.length > 0) {
      defaultSubjects.value = rawSubj;
    } else if (rawSubj?.kurikulum_merdeka && Array.isArray(rawSubj.kurikulum_merdeka)) {
      defaultSubjects.value = rawSubj.kurikulum_merdeka;
    } else if (!defaultSubjects.value || defaultSubjects.value.length === 0) {
      defaultSubjects.value = [...FALLBACK_SUBJECTS];
    }

    if (classes.value.length > 0) {
      selectedClassId.value = classes.value[0].id;
    }
    const activeAy = academicYears.value.find((ay: any) => ay.status === 'aktif');
    if (activeAy) {
      selectedAcademicYearId.value = activeAy.id;
    } else if (academicYears.value.length > 0) {
      selectedAcademicYearId.value = academicYears.value[0].id;
    }

    if (defaultSubjects.value.length > 0) {
      const defaultSubj = defaultSubjects.value.find((s: any) => s.nama === 'Matematika') || defaultSubjects.value[0];
      selectedSubjectName.value = defaultSubj.nama;
    }

    await loadGradeBook();
  } catch (err: any) {
    showNotification('Gagal memuat data master: ' + (err.response?.data?.message || err.message), 'error');
  }
};

// Load Grade Book for selected class, semester, subject
const loadGradeBook = async () => {
  if (!selectedClassId.value) return;
  loading.value = true;
  try {
    const res = await api.get('/report-cards/class-grade-book', {
      params: {
        class_id: selectedClassId.value,
        academic_year_id: selectedAcademicYearId.value || undefined,
        semester: selectedSemester.value,
        subject_name: selectedSubjectName.value,
      },
    });

    if (res.data.success) {
      if (res.data.data.default_subjects && Array.isArray(res.data.data.default_subjects) && res.data.data.default_subjects.length > 0) {
        defaultSubjects.value = res.data.data.default_subjects;
      }
      isWaliKelas.value = Boolean(res.data.data.is_wali_kelas);
      assignedSubjects.value = res.data.data.assigned_subjects || [];
      subjectOverview.value = res.data.data.subject_overview || {};

      // Jika guru mapel belum memilih mapel yang dia ampu, otomatis arahkan ke mapel pertamanya jika ada
      if (!isWaliKelas.value && assignedSubjects.value.length > 0 && !assignedSubjects.value.includes(selectedSubjectName.value)) {
        selectedSubjectName.value = assignedSubjects.value[0];
      }

      const rawStudents = res.data.data.students || [];

      // Cari jumlah UH terbanyak yang pernah disimpan untuk mapel terpilih (minimal 3 sesuai default)
      let maxSavedUH = 3;
      rawStudents.forEach((st: any) => {
        const eg = st.grades?.[selectedSubjectName.value];
        if (eg && Array.isArray(eg.nilai_uh)) {
          maxSavedUH = Math.max(maxSavedUH, eg.nilai_uh.length);
        }
      });
      uhColumnsCount.value = Math.max(3, maxSavedUH);

      students.value = rawStudents.map((st: any) => {
        const existingGrade = st.grades?.[selectedSubjectName.value] || {};
        const uhList = Array.isArray(existingGrade.nilai_uh) && existingGrade.nilai_uh.length > 0
          ? [...existingGrade.nilai_uh]
          : [];

        // Pastikan terisi minimal sebanyak uhColumnsCount
        while (uhList.length < uhColumnsCount.value) {
          uhList.push(null);
        }

        const row = {
          student_id: st.student_id,
          nama_lengkap: st.nama_lengkap,
          nis: st.nis,
          nisn: st.nisn,
          report_card_id: st.report_card_id,
          nilai_uh: uhList,
          rata_uh: existingGrade.rata_uh ?? null,
          nilai_sas: existingGrade.nilai_sas ?? null,
          nilai_akhir: existingGrade.nilai_akhir ?? null,
          predikat: existingGrade.predikat ?? null,
          capaian_kompetensi: existingGrade.capaian_kompetensi ?? '',
        };

        calculateStudentGrade(row);
        return row;
      });
    }
  } catch (err: any) {
    showNotification('Gagal memuat buku catatan nilai: ' + (err.response?.data?.message || err.message), 'error');
  } finally {
    loading.value = false;
  }
};

// Select Subject Helper
const selectSubject = (subjName: string) => {
  selectedSubjectName.value = subjName;
};

// Dynamic UH column controls
const addUHColumn = () => {
  if (isLockedForCurrentTeacher.value) return;
  uhColumnsCount.value++;
  students.value.forEach((st) => {
    if (!st.nilai_uh) st.nilai_uh = [];
    st.nilai_uh.push(null);
  });
  showNotification(`Kolom UH ${uhColumnsCount.value} berhasil ditambahkan.`, 'success');
};

const removeUHColumn = () => {
  if (isLockedForCurrentTeacher.value) return;
  if (uhColumnsCount.value <= 3) {
    showNotification('Default kolom Ulangan Harian minimal 3 (UH 1 s/d UH 3).', 'warning');
    return;
  }
  uhColumnsCount.value--;
  students.value.forEach((st) => {
    if (st.nilai_uh && st.nilai_uh.length > uhColumnsCount.value) {
      st.nilai_uh.pop();
    }
    calculateStudentGrade(st);
  });
  showNotification(`Kolom UH dikurangi menjadi ${uhColumnsCount.value} kolom.`, 'success');
};

// Save Grade Book to Database (save_draft | submit_to_homeroom | reopen)
const saveGradeBook = async (action: 'save_draft' | 'submit_to_homeroom' | 'reopen' = 'save_draft') => {
  if (!selectedClassId.value) return;

  if (action === 'submit_to_homeroom') {
    const homeroomName = currentClass.value?.homeroom_teacher?.nama_lengkap || 'Wali Kelas';
    const confirmed = await showConfirm(
      'Kirim Nilai ke Wali Kelas',
      `Kirimkan rincian nilai ulangan harian mata pelajaran "${selectedSubjectName.value}" (${students.value.length} siswa) ke Wali Kelas (${homeroomName})? Setelah dikirim, nilai akan TERKUNCI untuk mencegah perubahan sepihak.`,
      'Ya, Kirim ke Wali Kelas'
    );
    if (!confirmed) return;
  } else if (action === 'reopen') {
    const confirmed = await showConfirm(
      'Buka Kunci / Minta Revisi',
      `Buka kembali kunci pengisian nilai "${selectedSubjectName.value}" agar guru pengampu dapat mengedit dan memperbarui nilai?`,
      'Ya, Buka Kunci',
      'warning'
    );
    if (!confirmed) return;
  }

  saving.value = true;
  try {
    const payload = {
      class_id: selectedClassId.value,
      academic_year_id: selectedAcademicYearId.value || undefined,
      semester: selectedSemester.value,
      subject_name: selectedSubjectName.value,
      action,
      grades: students.value.map((st) => ({
        student_id: st.student_id,
        nilai_uh: st.nilai_uh.filter((v: any) => v !== null && v !== undefined && v !== ''),
        rata_uh: st.rata_uh,
        nilai_sas: st.nilai_sas,
        nilai_akhir: st.nilai_akhir,
        predikat: st.predikat !== '-' ? st.predikat : null,
        capaian_kompetensi: st.capaian_kompetensi,
      })),
    };

    const res = await api.post('/report-cards/class-grade-book', payload);
    if (res.data.success) {
      showNotification(res.data.message, action === 'reopen' ? 'warning' : 'success');
      await loadGradeBook();
    }
  } catch (err: any) {
    showNotification(err.response?.data?.message || 'Gagal menyimpan nilai: ' + err.message, 'error');
  } finally {
    saving.value = false;
  }
};

// Summary Statistics
const classStats = computed(() => {
  // Jika Guru Mapel, evaluasi berdasarkan Rata-rata UH (RUH)
  // Jika Wali Kelas, evaluasi berdasarkan Nilai Akhir Rapor (NR)
  const validScores = students.value
    .map((s) => (isWaliKelas.value ? s.nilai_akhir : s.rata_uh))
    .filter((v): v is number => typeof v === 'number' && !isNaN(v));

  if (validScores.length === 0) {
    return { avg: '-', max: '-', min: '-', passed: 0, total: students.value.length };
  }

  const avg = Math.round((validScores.reduce((a, b) => a + b, 0) / validScores.length) * 10) / 10;
  const max = Math.max(...validScores);
  const min = Math.min(...validScores);
  const passed = validScores.filter((v) => v >= 75).length;

  return { avg, max, min, passed, total: students.value.length };
});

const currentClass = computed(() => {
  return classes.value.find((cl: any) => String(cl.id) === String(selectedClassId.value));
});

const currentClassName = computed(() => {
  const c = currentClass.value;
  return c ? c.nama_kelas || c.nama : 'Kelas';
});

// Overview Counting
const overviewCounts = computed(() => {
  let submitted = 0;
  let draft = 0;
  let unsubmitted = 0;

  defaultSubjects.value.forEach((s) => {
    const info = subjectOverview.value[s.nama];
    const status = info?.status || 'unsubmitted';
    if (status === 'submitted') submitted++;
    else if (status === 'draft') draft++;
    else unsubmitted++;
  });

  return {
    submitted,
    draft,
    unsubmitted,
    total: defaultSubjects.value.length,
  };
});

// Watchers for filter change
watch([selectedClassId, selectedAcademicYearId, selectedSemester], () => {
  loadGradeBook();
});

watch(selectedSubjectName, () => {
  loadGradeBook();
});

onMounted(() => {
  fetchMasterData();
});
</script>

<template>
  <div class="space-y-6">
    <!-- NOTIFICATION TOAST -->
    <transition
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="notification"
        class="fixed top-5 right-5 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl border text-sm font-bold backdrop-blur-md"
        :class="{
          'bg-emerald-500/90 text-white border-emerald-400': notification.type === 'success',
          'bg-rose-500/90 text-white border-rose-400': notification.type === 'error',
          'bg-amber-500/90 text-white border-amber-400': notification.type === 'warning',
        }"
      >
        <span>{{ notification.type === 'success' ? '✓' : (notification.type === 'warning' ? 'ℹ️' : '⚠️') }}</span>
        <span>{{ notification.message }}</span>
      </div>
    </transition>

    <!-- HEADER MENU: BUKU NILAI -->
    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-4">
      <div class="flex items-center gap-3.5">
        <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center text-2xl shadow-md shadow-indigo-600/20 shrink-0">
          📒
        </div>
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
              Buku Nilai Siswa
            </h1>
            <span
              v-if="isWaliKelas"
              class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-200"
            >
              👑 Mode Wali Kelas (Lihat Seluruh Nilai Mapel)
            </span>
            <span
              v-else
              class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-sky-100 text-sky-800 border border-sky-200"
            >
              👨‍🏫 Mode Guru Mapel (Input Nilai Ulangan Harian)
            </span>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
            {{ isWaliKelas 
              ? 'Wali Kelas memantau seluruh nilai Ulangan Harian (UH) yang dikirim oleh Guru Mapel, serta melengkapi nilai SAS/Rapor.' 
              : 'Guru Mata Pelajaran bertugas menginput nilai Ulangan Harian (UH 1, UH 2, dst.), lalu mengirimkannya ke Wali Kelas.' }}
          </p>
        </div>
      </div>

      <!-- FORMULA CALLOUT PILL -->
      <div class="self-start lg:self-center shrink-0 bg-gradient-to-r from-indigo-50 to-emerald-50 border border-indigo-200/80 rounded-2xl px-4 py-2 text-xs flex items-center gap-2.5 shadow-2xs">
        <span class="text-slate-600 font-bold">Rumus Nilai Rapor:</span>
        <span class="font-mono font-black text-indigo-900 bg-white px-2.5 py-1 rounded-lg border border-indigo-200 shadow-2xs text-xs">
          NR = (3 &times; RUH + 2 &times; PSAS) : 5
        </span>
      </div>
    </div>

    <!-- FILTER & TOOLBAR CARD -->
    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs space-y-5">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. PILIH KELAS -->
        <div>
          <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
            Kelas
          </label>
          <select
            v-model="selectedClassId"
            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 shadow-2xs"
          >
            <option v-for="c in classes" :key="c.id" :value="c.id">
              {{ c.nama_kelas || c.nama }}
            </option>
          </select>
        </div>

        <!-- 2. PEMISAH SEMESTER 1 / SEMESTER 2 -->
        <div>
          <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
            Pilihan Semester
          </label>
          <div class="inline-flex w-full bg-slate-100 p-1 rounded-xl border border-slate-200">
            <button
              type="button"
              @click="selectedSemester = 'Gasal'"
              class="flex-1 py-1.5 rounded-lg text-xs font-black transition-all flex items-center justify-center gap-1.5 cursor-pointer"
              :class="selectedSemester === 'Gasal' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
            >
              <span>📘</span>
              <span>Semester 1 (Gasal)</span>
            </button>
            <button
              type="button"
              @click="selectedSemester = 'Genap'"
              class="flex-1 py-1.5 rounded-lg text-xs font-black transition-all flex items-center justify-center gap-1.5 cursor-pointer"
              :class="selectedSemester === 'Genap' ? 'bg-teal-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
            >
              <span>📗</span>
              <span>Semester 2 (Genap)</span>
            </button>
          </div>
        </div>

        <!-- 3. PILIH MATA PELAJARAN -->
        <div>
          <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
            Mata Pelajaran Aktif
          </label>
          <select
            v-model="selectedSubjectName"
            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 shadow-2xs"
          >
            <option v-for="s in defaultSubjects" :key="s.nama" :value="s.nama">
              {{ s.nama }} ({{ s.kelompok }}) {{ assignedSubjects.includes(s.nama) ? '★ Mapel Anda' : '' }}
            </option>
          </select>
        </div>

        <!-- 4. TAHUN AJARAN -->
        <div>
          <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
            Tahun Ajaran
          </label>
          <select
            v-model="selectedAcademicYearId"
            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 shadow-2xs"
          >
            <option v-for="ay in academicYears" :key="ay.id" :value="ay.id">
              {{ ay.nama }} {{ ay.status === 'aktif' ? '(Aktif)' : '' }}
            </option>
          </select>
        </div>
      </div>

      <!-- ACTION ROW: DYNAMIC UH COLUMNS & SAVE/SUBMIT BUTTONS -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-slate-100">
        <!-- UH Column Controller -->
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-xs font-bold text-slate-700">Kolom Nilai Ulangan Harian:</span>
          <span class="px-2 py-0.5 rounded-md font-mono font-black text-xs bg-amber-100 text-amber-900 border border-amber-300">
            UH 1 s/d UH {{ uhColumnsCount }}
          </span>

          <div v-if="!isLockedForCurrentTeacher" class="inline-flex items-center gap-1.5 ml-1">
            <button
              type="button"
              @click="addUHColumn"
              class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 rounded-xl text-xs font-bold transition-all flex items-center gap-1 cursor-pointer shadow-2xs"
              title="Tambah kolom nilai Ulangan Harian (UH)"
            >
              <span>+</span>
              <span>Tambah Kolom UH</span>
            </button>

            <button
              v-if="uhColumnsCount > 3"
              type="button"
              @click="removeUHColumn"
              class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-300 rounded-xl text-xs font-bold transition-all flex items-center gap-1 cursor-pointer shadow-2xs"
              title="Hapus kolom UH terakhir (minimal 3)"
            >
              <span>&minus;</span>
              <span>Kurang Kolom</span>
            </button>
          </div>
        </div>

        <!-- SAVE & SUBMISSION BUTTONS -->
        <div class="flex items-center gap-2 flex-wrap shrink-0 self-end sm:self-auto">
          <!-- Buka Kunci / Minta Revisi (Hanya untuk Wali Kelas / Admin saat nilai berstatus submitted) -->
          <button
            v-if="isWaliKelas && currentSubjectSubmission?.status === 'submitted'"
            type="button"
            @click="saveGradeBook('reopen')"
            :disabled="saving || loading"
            class="px-3.5 py-2.5 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer shadow-2xs disabled:opacity-50"
            title="Buka kembali kunci agar nilai dapat diperbaiki oleh guru pengampu"
          >
            <span>🔄</span>
            <span>Buka Kunci / Minta Revisi</span>
          </button>

          <!-- Tampilan jika Guru Mapel sedang TERKUNCI -->
          <div
            v-if="isLockedForCurrentTeacher"
            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 border border-slate-300 text-slate-700 text-xs font-bold shadow-2xs"
          >
            <span class="text-sm">🔒</span>
            <span>Nilai Terkunci (Sudah Terkirim)</span>
          </div>

          <!-- Tombol Simpan Draft (Jika tidak terkunci) -->
          <button
            v-if="!isLockedForCurrentTeacher"
            type="button"
            @click="saveGradeBook('save_draft')"
            :disabled="saving || loading || students.length === 0"
            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50 shadow-2xs"
            title="Simpan perubahan nilai sementara (Bisa diedit kembali kapan saja)"
          >
            <span>💾</span>
            <span>Simpan Draft</span>
          </button>

          <!-- Tombol Kirim ke Wali Kelas / Sahkan Nilai (Jika tidak terkunci) -->
          <button
            v-if="!isLockedForCurrentTeacher"
            type="button"
            @click="saveGradeBook('submit_to_homeroom')"
            :disabled="saving || loading || students.length === 0"
            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black transition-all shadow-md shadow-emerald-600/20 flex items-center gap-2 cursor-pointer disabled:opacity-50"
          >
            <svg v-if="saving" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span v-else>🚀</span>
            <span>{{ isWaliKelas ? 'Sahkan Nilai ke Rapor' : 'Kirim ke Wali Kelas' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- MONITORING PENGUMPULAN NILAI 18 MAPEL (STATUS TERKUMPUL DI WALI KELAS) -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-3.5">
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <span class="text-lg">📊</span>
          <h2 class="text-sm font-black text-slate-900 tracking-tight">
            Status Pengumpulan Nilai per Mata Pelajaran (Kelas {{ currentClassName }})
          </h2>
        </div>
        <div class="flex items-center gap-2">
          <!-- Summary Chips -->
          <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
            🟢 {{ overviewCounts.submitted }} Terkumpul
          </span>
          <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-amber-50 text-amber-800 border border-amber-200">
            🟡 {{ overviewCounts.draft }} Draft
          </span>
          <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-slate-100 text-slate-700 border border-slate-200">
            ⚪ {{ overviewCounts.unsubmitted }} Belum Ada
          </span>
          <button
            type="button"
            @click="showOverviewPanel = !showOverviewPanel"
            class="text-xs text-indigo-600 hover:text-indigo-800 font-bold ml-1 cursor-pointer"
          >
            {{ showOverviewPanel ? 'Sembunyikan ▲' : 'Tampilkan ▼' }}
          </button>
        </div>
      </div>

      <!-- Subject Cards Grid -->
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div v-show="showOverviewPanel" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5 pt-2 border-t border-slate-100">
          <button
            v-for="subj in defaultSubjects"
            :key="'ov-' + subj.nama"
            type="button"
            @click="selectSubject(subj.nama)"
            class="p-2.5 rounded-2xl border text-left transition-all cursor-pointer relative flex flex-col justify-between"
            :class="[
              selectedSubjectName === subj.nama
                ? 'bg-indigo-50/80 border-indigo-400 ring-2 ring-indigo-400/30 shadow-xs'
                : 'bg-slate-50/70 hover:bg-white border-slate-200 hover:border-slate-300'
            ]"
          >
            <!-- Top: Subject Name & Star if assigned -->
            <div>
              <div class="flex items-start justify-between gap-1">
                <span class="font-extrabold text-[11px] text-slate-900 leading-snug line-clamp-1" :title="subj.nama">
                  {{ subj.nama }}
                </span>
                <span
                  v-if="assignedSubjects.includes(subj.nama)"
                  class="text-[9px] font-black text-amber-700 bg-amber-100 px-1 rounded"
                  title="Mata Pelajaran yang Anda Ampu"
                >
                  Anda
                </span>
              </div>
              <div class="text-[9px] text-slate-400 font-medium">
                {{ subj.kelompok }}
              </div>
            </div>

            <!-- Bottom: Status Badge & Graded Count -->
            <div class="mt-2 pt-1.5 border-t border-slate-200/50 flex items-center justify-between">
              <span
                class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-wider"
                :class="{
                  'bg-emerald-100 text-emerald-800 border border-emerald-300': subjectOverview[subj.nama]?.status === 'submitted',
                  'bg-amber-100 text-amber-800 border border-amber-300': subjectOverview[subj.nama]?.status === 'draft',
                  'bg-slate-200/70 text-slate-600': !subjectOverview[subj.nama] || subjectOverview[subj.nama]?.status === 'unsubmitted',
                }"
              >
                {{ subjectOverview[subj.nama]?.status === 'submitted' ? '✓ Terkumpul' : (subjectOverview[subj.nama]?.status === 'draft' ? '⏳ Draft' : '⚪ Kosong') }}
              </span>

              <span class="text-[9px] font-mono font-bold text-slate-500">
                {{ subjectOverview[subj.nama]?.graded_count || 0 }}/{{ students.length }}
              </span>
            </div>
          </button>
        </div>
      </transition>
    </div>

    <!-- STATUS BANNER UNTUK MAPEL TERPILIH (LENGKAP DENGAN INDIKATOR KUNCI) -->
    <div
      class="p-4 rounded-2xl border flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
      :class="{
        'bg-emerald-50/90 border-emerald-300 text-emerald-950': currentSubjectSubmission?.status === 'submitted',
        'bg-amber-50/90 border-amber-300 text-amber-950': currentSubjectSubmission?.status === 'draft',
        'bg-slate-50 border-slate-200 text-slate-700': !currentSubjectSubmission || currentSubjectSubmission?.status === 'unsubmitted',
      }"
    >
      <div class="flex items-center gap-3">
        <span class="text-2xl">
          {{ currentSubjectSubmission?.status === 'submitted' ? (isLockedForCurrentTeacher ? '🔒' : '✅') : (currentSubjectSubmission?.status === 'draft' ? '⏳' : 'ℹ️') }}
        </span>
        <div>
          <div class="font-black text-sm flex items-center gap-2 flex-wrap">
            <span>Status Nilai {{ selectedSubjectName }}:</span>
            <span
              class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider"
              :class="{
                'bg-emerald-200 text-emerald-950': currentSubjectSubmission?.status === 'submitted',
                'bg-amber-200 text-amber-950': currentSubjectSubmission?.status === 'draft',
                'bg-slate-200 text-slate-800': !currentSubjectSubmission || currentSubjectSubmission?.status === 'unsubmitted',
              }"
            >
              {{ currentSubjectSubmission?.status === 'submitted' ? 'Sudah Terkumpul di Wali Kelas' : (currentSubjectSubmission?.status === 'draft' ? 'Draft Guru (Bisa Diedit)' : 'Belum Ada Nilai') }}
            </span>
          </div>

          <p class="text-xs mt-1 leading-relaxed opacity-90">
            <template v-if="currentSubjectSubmission?.status === 'submitted'">
              <span v-if="isLockedForCurrentTeacher" class="text-emerald-900 font-semibold">
                🔒 <strong>Nilai Terkunci:</strong> Nilai telah resmi diserahkan ke Wali Kelas pada <strong>{{ currentSubjectSubmission.submitted_at || '-' }}</strong> oleh <strong>{{ currentSubjectSubmission.teacher_name || 'Anda' }}</strong>. Formulir saat ini terkunci demi keamanan data. Jika ingin mengubah nilai, silakan hubungi Wali Kelas untuk membuka kunci perbaikan.
              </span>
              <span v-else>
                Nilai Ulangan Harian telah diserahkan oleh <strong>{{ currentSubjectSubmission.teacher_name || 'Guru Pengampu' }}</strong> pada <strong>{{ currentSubjectSubmission.submitted_at || '-' }}</strong>. Rincian nilai setiap murid tercantum di bawah ini.
              </span>
            </template>
            <template v-else-if="currentSubjectSubmission?.status === 'draft'">
              Nilai disimpan sebagai draft lokal. Guru <strong>masih bebas mengedit</strong> data kapan saja. Klik <strong>"Kirim ke Wali Kelas"</strong> jika pengisian nilai sudah final.
            </template>
            <template v-else>
              Guru belum menginput nilai ulangan harian untuk mata pelajaran ini. Silakan input nilai pada tabel di bawah ini.
            </template>
          </p>
        </div>
      </div>

      <!-- Quick Action if Submitted (Wali Kelas Only) -->
      <div v-if="isWaliKelas && currentSubjectSubmission?.status === 'submitted'" class="shrink-0 flex items-center gap-2">
        <button
          type="button"
          @click="saveGradeBook('reopen')"
          :disabled="saving || loading"
          class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl text-xs transition shadow-xs cursor-pointer flex items-center gap-1"
        >
          <span>🔄</span>
          <span>Buka Kunci Perbaikan</span>
        </button>
      </div>
    </div>

    <!-- STATS SUMMARY CARDS -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs">
      <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-2xs">
        <span class="text-slate-400 font-bold block text-[10px] uppercase">Rata-rata {{ isWaliKelas ? 'Kelas' : 'RUH' }}</span>
        <span class="text-xl font-black text-slate-800 tracking-tight">{{ classStats.avg }}</span>
      </div>
      <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-2xs">
        <span class="text-slate-400 font-bold block text-[10px] uppercase">Nilai Tertinggi</span>
        <span class="text-xl font-black text-emerald-600 tracking-tight">{{ classStats.max }}</span>
      </div>
      <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-2xs">
        <span class="text-slate-400 font-bold block text-[10px] uppercase">Nilai Terendah</span>
        <span class="text-xl font-black text-rose-600 tracking-tight">{{ classStats.min }}</span>
      </div>
      <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-2xs">
        <span class="text-slate-400 font-bold block text-[10px] uppercase">Tuntas (&ge; 75)</span>
        <span class="text-xl font-black text-indigo-600 tracking-tight">{{ classStats.passed }} / {{ classStats.total }}</span>
      </div>
      <div class="bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-2xs col-span-2 sm:col-span-1">
        <span class="text-slate-400 font-bold block text-[10px] uppercase">Mata Pelajaran Aktif</span>
        <span class="text-sm font-extrabold text-slate-900 truncate block">{{ selectedSubjectName }}</span>
      </div>
    </div>

    <!-- MAIN GRADE BOOK TABLE: RINCIAN NILAI SETIAP SISWA -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
      <!-- Loading State -->
      <div v-if="loading" class="py-24 text-center text-slate-400 space-y-3">
        <div class="animate-spin w-8 h-8 border-3 border-indigo-600 border-t-transparent rounded-full mx-auto"></div>
        <p class="text-xs font-bold text-slate-600">Memuat rincian nilai siswa {{ selectedSubjectName }}...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="students.length === 0" class="py-24 text-center text-slate-400 space-y-2">
        <span class="text-4xl block">👥</span>
        <h4 class="font-bold text-slate-700 text-sm">Tidak Ada Siswa di Kelas Ini</h4>
        <p class="text-xs text-slate-400">Pilih kelas yang memiliki data siswa aktif.</p>
      </div>

      <!-- Table Content: FULL STUDENT DETAILS -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs min-w-[950px]">
          <thead>
            <tr class="bg-slate-100 border-b border-slate-300 font-bold text-slate-800">
              <th class="py-3 px-3 text-center w-12 border-r border-slate-200">No</th>
              <th class="py-3 px-3 w-56 border-r border-slate-200">Nama Murid</th>

              <!-- DYNAMIC UH COLUMNS (UH 1, UH 2, UH 3, ...) -->
              <th
                v-for="uIdx in uhColumnsCount"
                :key="'th-uh-' + uIdx"
                class="py-3 px-2 text-center w-18 border-r border-slate-200 bg-amber-50/70 text-amber-950 font-black"
              >
                UH {{ uIdx }}
              </th>

              <!-- RUH (RATA-RATA ULANGAN HARIAN OTOMATIS) -->
              <th class="py-3 px-2 text-center w-20 border-r border-slate-200 bg-amber-100/80 text-amber-950 font-black" title="Rata-rata Ulangan Harian dihitung otomatis">
                RUH
              </th>

              <!-- KOLOM KHUSUS WALI KELAS: PSAS, NR, PREDIKAT -->
              <template v-if="isWaliKelas">
                <!-- PSAS (PENILAIAN SUMATIF AKHIR SEMESTER) -->
                <th class="py-3 px-2 text-center w-24 border-r border-slate-200 bg-sky-50 text-sky-950 font-black" title="Penilaian Sumatif Akhir Semester (Wali Kelas)">
                  PSAS
                </th>

                <!-- NR (NILAI RAPOR OTOMATIS RUMUS) -->
                <th class="py-3 px-2 text-center w-28 border-r border-slate-200 bg-emerald-50 text-emerald-950 font-black" title="Rumus: (3 * RUH + 2 * PSAS) / 5">
                  NR (Rapor)
                </th>

                <!-- PREDIKAT -->
                <th class="py-3 px-2 text-center w-16 border-r border-slate-200">Predikat</th>
              </template>

              <!-- CAPAIAN KOMPETENSI / CATATAN -->
              <th class="py-3 px-3">Catatan / Capaian Pembelajaran</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr
              v-for="(st, idx) in students"
              :key="st.student_id"
              class="hover:bg-slate-50/70 transition-colors"
            >
              <!-- No -->
              <td class="py-2.5 px-3 text-center font-bold text-slate-600 border-r border-slate-200">
                {{ idx + 1 }}
              </td>

              <!-- Nama Murid -->
              <td class="py-2.5 px-3 border-r border-slate-200">
                <div class="font-extrabold text-slate-900 leading-tight">{{ st.nama_lengkap }}</div>
                <div class="text-[10px] text-slate-400 font-mono mt-0.5">{{ st.nisn || st.nis || '-' }}</div>
              </td>

              <!-- Kolom Ulangan Harian (UH 1..n) -->
              <td
                v-for="uIdx in uhColumnsCount"
                :key="'td-uh-' + uIdx"
                class="py-2 px-1 text-center border-r border-slate-200 bg-amber-50/20"
              >
                <input
                  v-model.number="st.nilai_uh[uIdx - 1]"
                  @input="calculateStudentGrade(st)"
                  type="number"
                  min="0"
                  max="100"
                  :disabled="isLockedForCurrentTeacher"
                  class="w-14 text-center font-bold text-xs rounded-lg py-1 focus:outline-none shadow-2xs transition-colors"
                  :class="[
                    isLockedForCurrentTeacher 
                      ? 'bg-slate-100 text-slate-500 border border-slate-200 cursor-not-allowed' 
                      : 'bg-white border border-slate-300 hover:border-amber-400 focus:border-amber-500 text-slate-900'
                  ]"
                  placeholder="-"
                />
              </td>

              <!-- Rata-rata Ulangan Harian (RUH) Otomatis -->
              <td class="py-2 px-2 text-center border-r border-slate-200 bg-amber-50/40">
                <span
                  class="font-mono font-bold text-xs px-2 py-0.5 rounded-md"
                  :class="st.rata_uh !== null ? 'bg-amber-100 text-amber-950 font-black' : 'text-slate-400'"
                >
                  {{ st.rata_uh !== null ? st.rata_uh : '-' }}
                </span>
              </td>

              <!-- KOLOM WALI KELAS: PSAS, NR, PREDIKAT -->
              <template v-if="isWaliKelas">
                <!-- Nilai Sumatif Akhir Semester (PSAS) -->
                <td class="py-2 px-2 text-center border-r border-slate-200 bg-sky-50/30">
                  <input
                    v-model.number="st.nilai_sas"
                    @input="calculateStudentGrade(st)"
                    type="number"
                    min="0"
                    max="100"
                    class="w-16 text-center font-bold text-xs bg-white border border-slate-300 hover:border-sky-400 focus:border-sky-500 rounded-lg py-1 text-slate-900 focus:outline-none shadow-2xs transition-colors"
                    placeholder="-"
                  />
                </td>

                <!-- Nilai Rapor (NR = 3RUH + 2PSAS : 5) Otomatis -->
                <td class="py-2 px-2 text-center border-r border-slate-200 bg-emerald-50/40">
                  <span
                    class="font-mono font-black text-xs px-2.5 py-1 rounded-lg inline-block"
                    :class="st.nilai_akhir !== null ? 'bg-emerald-100 text-emerald-950 border border-emerald-300 shadow-2xs' : 'text-slate-400'"
                    :title="'NR = (3 * ' + (st.rata_uh || 0) + ' + 2 * ' + (st.nilai_sas || 0) + ') / 5'"
                  >
                    {{ st.nilai_akhir !== null ? st.nilai_akhir : '-' }}
                  </span>
                </td>

                <!-- Predikat -->
                <td class="py-2 px-2 text-center border-r border-slate-200">
                  <span
                    v-if="st.predikat && st.predikat !== '-'"
                    class="text-[10px] font-black px-2 py-0.5 rounded-md uppercase"
                    :class="{
                      'bg-emerald-100 text-emerald-800 border border-emerald-200': st.predikat === 'A',
                      'bg-blue-100 text-blue-800 border border-blue-200': st.predikat === 'B',
                      'bg-amber-100 text-amber-800 border border-amber-200': st.predikat === 'C',
                      'bg-rose-100 text-rose-800 border border-rose-200': st.predikat === 'D',
                    }"
                  >
                    {{ st.predikat }}
                  </span>
                  <span v-else class="text-slate-300">-</span>
                </td>
              </template>

              <!-- Capaian Kompetensi -->
              <td class="py-1.5 px-2.5 align-middle">
                <input
                  v-model="st.capaian_kompetensi"
                  type="text"
                  :disabled="isLockedForCurrentTeacher"
                  placeholder="Deskripsi capaian kompetensi..."
                  class="w-full text-xs rounded-lg px-2.5 py-1 transition-colors focus:outline-none"
                  :class="[
                    isLockedForCurrentTeacher 
                      ? 'bg-slate-100 text-slate-500 border border-slate-200 cursor-not-allowed' 
                      : 'bg-slate-50/50 hover:bg-white focus:bg-white border border-slate-200 text-slate-800 focus:ring-1 focus:ring-indigo-500'
                  ]"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- FOOTER TABLE INFO & ACTION BUTTONS -->
      <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-500">
        <div class="flex items-center gap-2 flex-wrap">
          <span>Menampilkan <strong>{{ students.length }} Siswa</strong> di <strong>{{ currentClassName }}</strong></span>
          <span class="text-slate-300">&bull;</span>
          <span :class="selectedSemester === 'Gasal' ? 'text-blue-700 font-bold' : 'text-teal-700 font-bold'">
            {{ selectedSemester === 'Gasal' ? '📘 Semester 1 (Gasal)' : '📗 Semester 2 (Genap)' }}
          </span>
          <span class="text-slate-300">&bull;</span>
          <span>Mata Pelajaran: <strong>{{ selectedSubjectName }}</strong></span>
        </div>

        <div class="flex items-center gap-2 self-end sm:self-auto">
          <!-- Tampilan jika Guru Mapel sedang TERKUNCI -->
          <div
            v-if="isLockedForCurrentTeacher"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-200/80 text-slate-700 text-xs font-bold"
          >
            <span>🔒 Terkunci (Hubungi Wali Kelas untuk Revisi)</span>
          </div>

          <!-- Tombol Simpan Draft (Jika tidak terkunci) -->
          <button
            v-if="!isLockedForCurrentTeacher"
            type="button"
            @click="saveGradeBook('save_draft')"
            :disabled="saving || loading || students.length === 0"
            class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-xl text-xs font-bold transition-all cursor-pointer disabled:opacity-50"
          >
            <span>Simpan Draft</span>
          </button>

          <!-- Tombol Kirim ke Wali Kelas / Sahkan Nilai (Jika tidak terkunci) -->
          <button
            v-if="!isLockedForCurrentTeacher"
            type="button"
            @click="saveGradeBook('submit_to_homeroom')"
            :disabled="saving || loading || students.length === 0"
            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-emerald-600/20 flex items-center gap-2 cursor-pointer disabled:opacity-50"
          >
            <span>{{ isWaliKelas ? 'Sahkan Nilai' : 'Kirim ke Wali Kelas' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
