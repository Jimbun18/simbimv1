<template>
  <div class="space-y-6">
    <!-- Top Stats & Filter Toolbar -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
      <!-- Quick Filter per Tingkat -->
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none">
        <button
          type="button"
          @click="selectedTingkatFilter = null"
          :class="[
            'px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer',
            selectedTingkatFilter === null
              ? 'bg-emerald-600 text-white shadow-xs'
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          ]"
        >
          Semua Kelas (1–6)
          <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px]" :class="selectedTingkatFilter === null ? 'bg-emerald-700 text-emerald-100' : 'bg-slate-200 text-slate-700'">
            {{ classes.length }}
          </span>
        </button>

        <button
          v-for="g in SD_GRADES"
          :key="g.tingkat"
          type="button"
          @click="selectedTingkatFilter = g.tingkat"
          :class="[
            'px-3 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer flex items-center gap-1.5',
            selectedTingkatFilter === g.tingkat
              ? 'bg-emerald-600 text-white shadow-xs'
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          ]"
        >
          <span>{{ g.label }}</span>
          <span
            class="px-1.5 py-0.2 rounded-full text-[10px]"
            :class="selectedTingkatFilter === g.tingkat ? 'bg-emerald-700 text-emerald-100' : 'bg-slate-200 text-slate-700'"
          >
            {{ getClassesByTingkat(g.tingkat).length }}
          </span>
        </button>
      </div>

      <!-- Quick Action: Generate Standar 1-6 jika ada yang kosong -->
      <div class="flex items-center gap-2 shrink-0">
        <button
          v-if="hasMissingGrades"
          type="button"
          @click="handleGenerateDefaults"
          :disabled="generatingDefaults"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 text-xs font-bold rounded-xl border border-amber-200 transition cursor-pointer disabled:opacity-50"
          title="Otomatis siapkan rombel dasar Kelas 1-A s/d 6-A untuk kelas yang belum ada"
        >
          <svg v-if="generatingDefaults" class="w-3.5 h-3.5 animate-spin text-amber-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <span v-else>⚡</span>
          <span>{{ generatingDefaults ? 'Menyiapkan...' : 'Siapkan Rombel 1–6 (A)' }}</span>
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="py-16 text-center text-gray-400 bg-white rounded-2xl border border-gray-100">
      <div class="inline-block animate-spin w-7 h-7 border-3 border-emerald-600 border-t-transparent rounded-full mb-3"></div>
      <p class="text-xs font-semibold text-gray-600">Memuat data tingkatan & rombel paralel...</p>
    </div>

    <!-- 6 Tingkat Permanen SD (Kelas 1 s/d 6) -->
    <div v-else class="space-y-6">
      <div
        v-for="grade in filteredGrades"
        :key="grade.tingkat"
        class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden transition hover:border-gray-200"
      >
        <!-- Header Tingkat Kelas (Permanen) -->
        <div class="p-4 sm:p-5 bg-gradient-to-r from-slate-50 via-white to-slate-50/50 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="flex items-center gap-3.5">
            <!-- Grade Number Badge -->
            <div :class="['w-11 h-11 rounded-xl flex items-center justify-center font-black text-base shadow-xs shrink-0', grade.iconBg]">
              {{ grade.tingkat }}
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h2 class="text-base sm:text-lg font-black text-gray-900 leading-tight">
                  {{ grade.label }}
                </h2>
                <span :class="['px-2.5 py-0.5 rounded-full text-[11px] font-bold border', grade.faseBadge]">
                  {{ grade.fase }}
                </span>
              </div>
              <p class="text-xs text-gray-500 mt-0.5">
                {{ grade.description }} •
                <span class="font-bold text-gray-700">
                  {{ getClassesByTingkat(grade.tingkat).length }} Rombel Paralel
                </span>
                <span v-if="getTotalStudentsByTingkat(grade.tingkat) > 0" class="text-emerald-600 font-semibold">
                  ({{ getTotalStudentsByTingkat(grade.tingkat) }} siswa)
                </span>
              </p>
            </div>
          </div>

          <!-- Tombol Cepat: Tambah Paralel di Tingkat Ini -->
          <div class="flex items-center gap-2 self-start sm:self-center shrink-0">
            <button
              type="button"
              @click="handleAddParallel(grade.tingkat)"
              class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer active:scale-95"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
              </svg>
              <span>+ Tambah Paralel {{ grade.label }}</span>
            </button>
          </div>
        </div>

        <!-- Daftar Rombel Paralel dalam Tingkat Ini -->
        <div class="p-4 sm:p-5">
          <!-- Jika ada Rombel Paralel -->
          <div
            v-if="getClassesByTingkat(grade.tingkat).length > 0"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
          >
            <div
              v-for="c in getClassesByTingkat(grade.tingkat)"
              :key="c.id"
              class="bg-slate-50/70 hover:bg-white rounded-xl border border-slate-200/80 hover:border-emerald-300 p-4 transition shadow-2xs hover:shadow-sm flex flex-col justify-between group"
            >
              <div class="space-y-3">
                <!-- Baris Atas: Kode & Paralel Badge -->
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-1.5">
                    <span class="px-2.5 py-1 bg-white text-emerald-800 border border-emerald-200/80 rounded-lg text-xs font-black font-mono shadow-2xs">
                      {{ c.kode }}
                    </span>
                    <span class="px-2 py-0.5 bg-emerald-100/70 text-emerald-800 rounded-md text-[10px] font-bold uppercase tracking-wide">
                      Paralel {{ c.paralel || 'A' }}
                    </span>
                  </div>
                  <span
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                    :class="c.status === 'aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-500'"
                  >
                    {{ c.status }}
                  </span>
                </div>

                <!-- Nama Rombel -->
                <div>
                  <h3 class="text-sm sm:text-base font-bold text-gray-900 group-hover:text-emerald-700 transition leading-snug">
                    {{ c.nama }}
                  </h3>
                </div>

                <!-- Detail Wali Kelas, Ruang & Kapasitas -->
                <div class="pt-2.5 border-t border-slate-200/70 space-y-1.5 text-xs text-gray-600">
                  <div class="flex items-center justify-between">
                    <span class="text-gray-400">Wali Kelas:</span>
                    <span class="font-bold text-gray-800 text-right truncate max-w-[170px]" :title="c.homeroom_teacher?.nama_lengkap || c.wali_kelas?.nama_lengkap || 'Belum Ditugaskan'">
                      {{ c.homeroom_teacher?.nama_lengkap || c.wali_kelas?.nama_lengkap || 'Belum Ditugaskan' }}
                    </span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-gray-400">Ruangan:</span>
                    <span class="font-medium text-gray-700 truncate max-w-[170px]">{{ c.room?.nama ?? '-' }}</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-gray-400">Kapasitas / Siswa:</span>
                    <span class="font-medium text-gray-700">
                      <span class="font-bold text-emerald-700">{{ c.active_students_count ?? c.enrollments_count ?? 0 }}</span>
                      / {{ c.kapasitas ?? 28 }} Siswa
                    </span>
                  </div>
                </div>
              </div>

              <!-- Footer Actions -->
              <div class="pt-3 mt-3 border-t border-slate-200/70 flex items-center justify-between">
                <span class="text-[11px] text-gray-400">
                  ID: #{{ c.id }}
                </span>
                <div class="flex items-center gap-1.5">
                  <button
                    type="button"
                    @click="$emit('edit', c)"
                    class="p-1.5 bg-white hover:bg-amber-50 text-amber-700 rounded-lg transition border border-gray-200 hover:border-amber-300 cursor-pointer shadow-2xs"
                    title="Edit Rombel Paralel"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button
                    type="button"
                    @click="handleDeleteClass(c)"
                    class="p-1.5 bg-white hover:bg-rose-50 text-rose-700 rounded-lg transition border border-gray-200 hover:border-rose-300 cursor-pointer shadow-2xs"
                    title="Hapus Rombel Paralel"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State untuk Tingkat ini -->
          <div
            v-else
            class="py-8 px-4 rounded-xl border-2 border-dashed border-slate-200 text-center bg-slate-50/50 flex flex-col items-center justify-center gap-2"
          >
            <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
            </div>
            <p class="text-xs font-semibold text-gray-700">
              Belum ada rombel paralel terdaftar di {{ grade.label }}.
            </p>
            <p class="text-[11px] text-gray-400 max-w-sm">
              Tambahkan rombel pertama (misal Paralel A) untuk mulai mengalokasikan wali kelas, jadwal, dan siswa.
            </p>
            <button
              type="button"
              @click="handleAddParallel(grade.tingkat)"
              class="mt-1 inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold rounded-lg border border-emerald-200 transition cursor-pointer"
            >
              <span>+ Buat Rombel Pertama (Kelas {{ grade.tingkat }}-A)</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '../../services/api';
import { confirmDeleteWithKeyword, showSuccess, showError } from '../../services/swal';

const emit = defineEmits<{
  (e: 'create', payload?: { tingkat?: number; suggestedParalel?: string }): void;
  (e: 'edit', classItem: any): void;
  (e: 'updated', classes: any[]): void;
}>();

interface SDGrade {
  tingkat: number;
  label: string;
  fase: string;
  description: string;
  iconBg: string;
  faseBadge: string;
}

const SD_GRADES: SDGrade[] = [
  {
    tingkat: 1,
    label: 'Kelas 1',
    fase: 'Fase A',
    description: 'Fondasi Karakter, Adab & Calistung Terpadu',
    iconBg: 'bg-emerald-100 text-emerald-800',
    faseBadge: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  },
  {
    tingkat: 2,
    label: 'Kelas 2',
    fase: 'Fase A',
    description: 'Pemantapan Membaca, Tahsin & Ibadah Mandiri',
    iconBg: 'bg-teal-100 text-teal-800',
    faseBadge: 'bg-teal-50 text-teal-700 border-teal-200',
  },
  {
    tingkat: 3,
    label: 'Kelas 3',
    fase: 'Fase B',
    description: 'Transisi Madya, Tahfidz Juz 30 & Sains Tematik',
    iconBg: 'bg-blue-100 text-blue-800',
    faseBadge: 'bg-blue-50 text-blue-700 border-blue-200',
  },
  {
    tingkat: 4,
    label: 'Kelas 4',
    fase: 'Fase B',
    description: 'Penguatan Logika, Bahasa Arab & Proyek Kolaboratif',
    iconBg: 'bg-indigo-100 text-indigo-800',
    faseBadge: 'bg-indigo-50 text-indigo-700 border-indigo-200',
  },
  {
    tingkat: 5,
    label: 'Kelas 5',
    fase: 'Fase C',
    description: 'Tingkat Lanjut, Kepemimpinan & Riset Tematik Terpadu',
    iconBg: 'bg-purple-100 text-purple-800',
    faseBadge: 'bg-purple-50 text-purple-700 border-purple-200',
  },
  {
    tingkat: 6,
    label: 'Kelas 6',
    fase: 'Fase C',
    description: 'Kelulusan, Kematangan Adab & Pembekalan Jenjang Menengah',
    iconBg: 'bg-amber-100 text-amber-800',
    faseBadge: 'bg-amber-50 text-amber-700 border-amber-200',
  },
];

const classes = ref<any[]>([]);
const loading = ref(false);
const exporting = ref(false);
const generatingDefaults = ref(false);
const selectedTingkatFilter = ref<number | null>(null);

const filteredGrades = computed(() => {
  if (selectedTingkatFilter.value === null) {
    return SD_GRADES;
  }
  return SD_GRADES.filter(g => g.tingkat === selectedTingkatFilter.value);
});

const getClassesByTingkat = (tingkat: number) => {
  return classes.value.filter(c => Number(c.tingkat) === tingkat);
};

const getTotalStudentsByTingkat = (tingkat: number) => {
  const list = getClassesByTingkat(tingkat);
  return list.reduce((acc, c) => acc + (c.active_students_count || c.enrollments_count || 0), 0);
};

// Cek apakah ada tingkat 1 s/d 6 yang belum memiliki rombel sama sekali
const hasMissingGrades = computed(() => {
  return SD_GRADES.some(g => getClassesByTingkat(g.tingkat).length === 0);
});

// Hitung huruf paralel berikutnya (misal jika sudah ada A -> B, jika sudah ada A & B -> C)
const getNextParalelForTingkat = (tingkat: number): string => {
  const existing = getClassesByTingkat(tingkat).map(c => (c.paralel || '').toUpperCase().trim());
  const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
  for (const letter of alphabet) {
    if (!existing.includes(letter)) {
      return letter;
    }
  }
  return 'A';
};

const handleAddParallel = (tingkat: number) => {
  const nextParalel = getNextParalelForTingkat(tingkat);
  emit('create', { tingkat, suggestedParalel: nextParalel });
};

const fetchClasses = async () => {
  loading.value = true;
  try {
    const res = await api.get('/classes', { params: { all: true } });
    if (res.data.success) {
      classes.value = res.data.data || [];
      emit('updated', classes.value);
    }
  } catch (err: any) {
    showError('Gagal Memuat Kelas', err, 'Terjadi kendala saat mengambil data rombel kelas.');
  } finally {
    loading.value = false;
  }
};

const handleGenerateDefaults = async () => {
  generatingDefaults.value = true;
  try {
    const res = await api.post('/classes/generate-defaults');
    if (res.data.success) {
      showSuccess('Berhasil!', res.data.message || 'Rombel standar berhasil disiapkan.');
      await fetchClasses();
    }
  } catch (err: any) {
    showError('Gagal Menyiapkan Rombel', err, 'Terjadi kendala saat menyiapkan rombel standar.');
  } finally {
    generatingDefaults.value = false;
  }
};

const handleDeleteClass = async (c: any) => {
  const confirmed = await confirmDeleteWithKeyword(`rombel "${c.nama}" (${c.kode})`);
  if (!confirmed) return;

  try {
    const res = await api.delete(`/classes/${c.id}`);
    if (res.data.success) {
      showSuccess('Berhasil Dihapus!', res.data.message || 'Rombel berhasil dihapus.');
      fetchClasses();
    }
  } catch (err: any) {
    showError('Gagal Menghapus!', err, 'Rombel tidak dapat dihapus.');
  }
};

const exportExcel = async () => {
  exporting.value = true;
  try {
    const res = await api.get('/classes/export', {
      responseType: 'blob',
    });

    const blob = new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `Master_Data_Kelas_Rombel_${new Date().toISOString().slice(0, 10)}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    showSuccess('Berhasil!', 'File Excel data kelas & rombel berhasil diunduh.');
  } catch (err: any) {
    showError('Gagal Export Excel!', err, 'Terjadi kendala saat mengunduh berkas Excel data kelas.');
  } finally {
    exporting.value = false;
  }
};

defineExpose({
  fetchClasses,
  exportExcel,
  exportClassesExcel: exportExcel,
  exporting,
});

onMounted(() => {
  fetchClasses();
});
</script>
