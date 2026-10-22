<template>
  <div class="space-y-5">
    <!-- Control Header & Toolbar -->
    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <span class="px-2.5 py-0.5 bg-purple-50 text-purple-700 text-[10px] font-black uppercase tracking-wider rounded-md border border-purple-200">
            Alokasi Mengajar
          </span>
          <span class="text-xs text-gray-400">TP 2026/2027 (Ganjil)</span>
        </div>
        <h2 class="text-lg font-black text-gray-900 mt-1">
          Pembagian Mengajar Guru (Mata Pelajaran & Tematik)
        </h2>
        <p class="text-xs text-gray-500">
          Kelola data tema apa saja, siapa guru pengampunya, dan di kelas mana saja mengajar. Perubahan di sini otomatis terhubung langsung ke jadwal KBM mingguan.
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-2.5 shrink-0">
        <button
          @click="syncAssignmentsWithSchedule"
          :disabled="syncingAssignments || assignmentsList.length === 0"
          class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-md shadow-emerald-600/20 flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          title="Sinkronkan seluruh penugasan guru ini ke jadwal mingguan"
        >
          <svg v-if="syncingAssignments" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <span v-else>⚡</span>
          <span>{{ syncingAssignments ? 'Menyinkronkan...' : 'Sinkronkan ke Jadwal Mingguan' }}</span>
        </button>
        <button
          @click="openCreateModal"
          class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition shadow-md shadow-purple-600/20 flex items-center gap-1.5 cursor-pointer"
        >
          <span>+</span>
          <span>Tambah Penugasan</span>
        </button>
      </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
      <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs">
        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Total Penugasan</span>
        <div class="text-xl font-black text-gray-900 mt-0.5">{{ assignmentsList.length }}</div>
        <span class="text-[10px] text-purple-600 font-semibold">Tema & Mapel</span>
      </div>
      <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs">
        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Guru Terlibat</span>
        <div class="text-xl font-black text-emerald-600 mt-0.5">{{ uniqueTeachersCount }}</div>
        <span class="text-[10px] text-gray-500">Pendidik Aktif</span>
      </div>
      <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs">
        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Rombel Tercover</span>
        <div class="text-xl font-black text-blue-600 mt-0.5">{{ uniqueClassesCount }}</div>
        <span class="text-[10px] text-gray-500">Kelas Aktif</span>
      </div>
      <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs">
        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Keterangan Khusus</span>
        <div class="text-xs font-bold text-amber-700 mt-1">Sesi Terpadu</div>
        <span class="text-[10px] text-gray-400">Pagi, Siang & Reguler</span>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-3.5 rounded-2xl border border-gray-100 shadow-2xs flex flex-wrap items-center justify-between gap-3 text-xs">
      <div class="flex flex-wrap items-center gap-2 flex-1">
        <select
          v-model="filterSubject"
          class="border border-gray-200 rounded-xl px-3 py-1.5 text-xs bg-white text-gray-700 focus:ring-2 focus:ring-purple-500 outline-none"
        >
          <option value="">-- Semua Tema / Mapel --</option>
          <option v-for="sub in subjectsList" :key="sub.id" :value="sub.id">
            {{ sub.kode }} - {{ sub.nama }}
          </option>
        </select>

        <select
          v-model="filterTeacher"
          class="border border-gray-200 rounded-xl px-3 py-1.5 text-xs bg-white text-gray-700 focus:ring-2 focus:ring-purple-500 outline-none"
        >
          <option value="">-- Semua Guru --</option>
          <option v-for="t in teachersList" :key="t.id" :value="t.id">
            {{ t.nama_lengkap }}
          </option>
        </select>

        <select
          v-model="filterClass"
          class="border border-gray-200 rounded-xl px-3 py-1.5 text-xs bg-white text-gray-700 focus:ring-2 focus:ring-purple-500 outline-none"
        >
          <option value="">-- Semua Rombel Kelas --</option>
          <option v-for="c in classesList" :key="c.id" :value="c.id">
            {{ c.nama }}
          </option>
        </select>
      </div>

      <span class="text-gray-400 font-mono text-[11px] shrink-0">
        Menampilkan {{ filteredAssignments.length }} dari {{ assignmentsList.length }} Penugasan
      </span>
    </div>

    <!-- Assignments Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs min-w-[760px]">
          <thead class="bg-gray-50/80 border-b border-gray-100 text-gray-500 font-bold uppercase text-[10px] tracking-wider">
            <tr>
              <th class="py-3 px-4 w-12 text-center">No</th>
              <th class="py-3 px-4">Mata Pelajaran / Tema</th>
              <th class="py-3 px-4">Guru Pengampu</th>
              <th class="py-3 px-4">Rombel Kelas</th>
              <th class="py-3 px-4">Sesi Waktu / Keterangan</th>
              <th class="py-3 px-4 text-center">Beban Jam</th>
              <th class="py-3 px-4 text-center w-28">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="loadingAssignments">
              <td colspan="7" class="py-12 text-center text-gray-400">
                <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-purple-500 border-t-transparent mb-1"></div>
                <div>Memuat pembagian mengajar guru...</div>
              </td>
            </tr>
            <tr v-else-if="filteredAssignments.length === 0">
              <td colspan="7" class="py-12 text-center text-gray-400">
                Belum ada penugasan mengajar yang cocok dengan filter.
              </td>
            </tr>
            <tr
              v-for="(item, idx) in filteredAssignments"
              :key="item.id"
              class="hover:bg-purple-50/30 transition group"
            >
              <td class="py-3.5 px-4 text-center font-mono text-gray-400">{{ idx + 1 }}</td>
              <td class="py-3.5 px-4">
                <div class="flex items-center gap-2">
                  <span
                    class="px-2 py-0.5 rounded-md font-black font-mono text-[10px] tracking-wider shrink-0"
                    :class="item.subject?.is_tematik ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-slate-100 text-slate-700 border border-slate-200'"
                  >
                    {{ item.subject?.kode || 'MAPEL' }}
                  </span>
                  <div>
                    <span class="font-bold text-gray-900 block">{{ item.subject?.nama }}</span>
                    <span v-if="item.subject?.is_tematik" class="text-[10px] text-purple-600 font-semibold">Tematik Terpadu</span>
                  </div>
                </div>
              </td>
              <td class="py-3.5 px-4">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-full bg-purple-100 text-purple-700 font-bold flex items-center justify-center text-xs shrink-0">
                    {{ (item.teacher?.nama_lengkap || '?').substring(0, 2).toUpperCase() }}
                  </div>
                  <div>
                    <strong class="text-gray-900 font-bold block">{{ item.teacher?.nama_lengkap }}</strong>
                    <span class="text-[10px] text-gray-400 font-mono">{{ item.teacher?.nip ? `NIP. ${item.teacher.nip}` : 'Pendidik' }}</span>
                  </div>
                </div>
              </td>
              <td class="py-3.5 px-4">
                <span class="px-2.5 py-1 rounded-xl bg-blue-50 text-blue-800 font-bold text-xs border border-blue-200 flex items-center gap-1 w-fit">
                  <span>🏫</span>
                  <span>{{ item.school_class?.nama || item.class?.nama || 'Kelas' }}</span>
                </span>
              </td>
              <td class="py-3.5 px-4">
                <span
                  class="px-2.5 py-1 rounded-lg text-[11px] font-semibold flex items-center gap-1 w-fit"
                  :class="item.keterangan && item.keterangan.includes('Pagi')
                    ? 'bg-amber-50 text-amber-900 border border-amber-300 font-bold'
                    : item.keterangan && item.keterangan.includes('Siang')
                    ? 'bg-sky-50 text-sky-900 border border-sky-300 font-bold'
                    : 'bg-emerald-50 text-emerald-800 border border-emerald-200'"
                >
                  <span>{{ item.keterangan && item.keterangan.includes('Pagi') ? '☀️' : item.keterangan && item.keterangan.includes('Siang') ? '⛅' : '📖' }}</span>
                  <span>{{ item.keterangan || 'Reguler' }}</span>
                </span>
              </td>
              <td class="py-3.5 px-4 text-center font-bold text-gray-700">
                {{ item.jam_per_minggu || 4 }} JP
              </td>
              <td class="py-3.5 px-4 text-center">
                <div class="flex items-center justify-center gap-1.5">
                  <button
                    @click="openEditModal(item)"
                    class="p-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg transition border border-amber-200 cursor-pointer"
                    title="Edit Penugasan / Ganti Guru Pengampu"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                  </button>
                  <button
                    @click="handleDeleteAssignment(item)"
                    class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg transition border border-rose-200 cursor-pointer"
                    title="Hapus Penugasan Ini"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form Tambah / Edit Pembagian Mengajar Guru -->
    <div
      v-if="isModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs animate-fade-in"
    >
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg border border-purple-100 overflow-hidden animate-scale-up">
        <!-- Modal Header -->
        <div class="px-6 py-5 bg-gradient-to-r from-purple-700 via-indigo-700 to-purple-800 text-white flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-lg shadow-inner">
              👥
            </div>
            <div>
              <h3 class="text-base font-extrabold tracking-tight">
                {{ isEditing ? 'Edit Pembagian Mengajar Guru' : 'Tambah Pembagian Mengajar Guru' }}
              </h3>
              <p class="text-xs text-purple-200">
                Atur guru pengampu mata pelajaran / tema untuk rombel kelas
              </p>
            </div>
          </div>
          <button
            @click="isModalOpen = false"
            class="text-white/80 hover:text-white p-1.5 rounded-xl hover:bg-white/10 transition cursor-pointer"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Modal Body Form -->
        <form @submit.prevent="saveAssignmentForm" class="p-6 space-y-4">
          <!-- Guru Pengampu -->
          <div>
            <label class="block text-xs font-bold text-gray-700 mb-1.5">
              Guru Pengampu <span class="text-rose-500">*</span>
            </label>
            <select
              v-model="assignmentForm.teacher_id"
              required
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs bg-white text-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition font-medium"
            >
              <option value="" disabled>-- Pilih Guru Pendidik --</option>
              <option v-for="t in teachersList" :key="t.id" :value="t.id">
                {{ t.nama_lengkap }} ({{ t.nip ? `NIP. ${t.nip}` : 'Pendidik' }})
              </option>
            </select>
          </div>

          <!-- Mata Pelajaran / Tema -->
          <div>
            <label class="block text-xs font-bold text-gray-700 mb-1.5">
              Mata Pelajaran / Tema Tematik <span class="text-rose-500">*</span>
            </label>
            <select
              v-model="assignmentForm.subject_id"
              required
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs bg-white text-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition font-medium"
            >
              <option value="" disabled>-- Pilih Mata Pelajaran / Tema --</option>
              <option v-for="s in subjectsList" :key="s.id" :value="s.id">
                {{ s.kode }} - {{ s.nama }} {{ s.is_tematik ? '(Tematik)' : '' }}
              </option>
            </select>
          </div>

          <!-- Rombel Kelas -->
          <div>
            <label class="block text-xs font-bold text-gray-700 mb-1.5">
              Rombel Kelas <span class="text-rose-500">*</span>
            </label>
            <select
              v-model="assignmentForm.class_id"
              required
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs bg-white text-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition font-medium"
            >
              <option value="" disabled>-- Pilih Rombel Kelas --</option>
              <option v-for="c in classesList" :key="c.id" :value="c.id">
                {{ c.nama }} ({{ c.kode }})
              </option>
            </select>
          </div>

          <!-- Sesi Waktu / Keterangan Khusus & Beban Jam -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1.5">
                Beban Jam (JP / Minggu)
              </label>
              <input
                v-model.number="assignmentForm.jam_per_minggu"
                type="number"
                min="1"
                max="40"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs bg-white text-gray-800 focus:ring-2 focus:ring-purple-500 outline-none transition font-medium"
                placeholder="Contoh: 4"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1.5">
                Sesi Waktu / Keterangan
              </label>
              <select
                v-model="assignmentForm.keterangan"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs bg-white text-gray-800 focus:ring-2 focus:ring-purple-500 outline-none transition font-medium"
              >
                <option value="Reguler (Seluruh Sesi)">Reguler (Seluruh Sesi)</option>
                <option value="Pagi: Jam ke-1 s/d 4 (Sebelum Istirahat)">Pagi: Jam ke-1 s/d 4 (Sebelum Istirahat)</option>
                <option value="Siang: Jam ke-5 s/d 7 (Sesudah Istirahat)">Siang: Jam ke-5 s/d 7 (Sesudah Istirahat)</option>
                <option value="Sesi Tertentu">Sesi Tertentu (Kustom)</option>
              </select>
            </div>
          </div>

          <!-- Opsi Keterangan Custom jika Sesi Tertentu -->
          <div v-if="assignmentForm.keterangan === 'Sesi Tertentu'">
            <label class="block text-xs font-bold text-gray-700 mb-1.5">
              Catatan Sesi Kustom
            </label>
            <input
              v-model="assignmentForm.keterangan_custom"
              type="text"
              placeholder="Contoh: Khusus hari Jumat / Sesi 1-2"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs bg-white text-gray-800 focus:ring-2 focus:ring-purple-500 outline-none transition"
            />
          </div>

          <!-- Info Box Sinkronisasi Jadwal -->
          <div class="p-3.5 bg-purple-50 rounded-2xl border border-purple-200/80 flex items-start gap-2.5 text-xs text-purple-900">
            <span class="text-base shrink-0">⚡</span>
            <div class="leading-relaxed">
              <strong>Tersambung Langsung ke Jadwal Mingguan:</strong>
              <p class="text-[11px] text-purple-700 mt-0.5">
                Menyimpan data ini akan otomatis memperbarui pengampu tema di jadwal pelajaran mingguan kelas yang bersangkutan sesuai sesi waktunya.
              </p>
            </div>
          </div>

          <!-- Modal Footer Actions -->
          <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2.5">
            <button
              type="button"
              @click="isModalOpen = false"
              class="px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-600 hover:bg-gray-50 transition cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="savingAssignment"
              class="inline-flex items-center gap-1.5 px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md shadow-purple-600/20 transition disabled:opacity-50 cursor-pointer active:scale-95"
            >
              <svg v-if="savingAssignment" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span>{{ savingAssignment ? 'Menyimpan...' : (isEditing ? 'Perbarui Penugasan' : 'Simpan Penugasan') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '../../services/api';
import { confirmDeleteWithKeyword, showSuccess, showError, showConfirm } from '../../services/swal';

const props = defineProps<{
  activeYearId?: number | null;
}>();

const assignmentsList = ref<any[]>([]);
const teachersList = ref<any[]>([]);
const subjectsList = ref<any[]>([]);
const classesList = ref<any[]>([]);

const loadingAssignments = ref(false);
const syncingAssignments = ref(false);
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);
const savingAssignment = ref(false);

const filterSubject = ref('');
const filterTeacher = ref('');
const filterClass = ref('');

const assignmentForm = ref<any>({
  teacher_id: '',
  subject_id: '',
  class_id: '',
  academic_year_id: null,
  jam_per_minggu: 4,
  keterangan: 'Reguler (Seluruh Sesi)',
  keterangan_custom: '',
});

const loadLookups = async () => {
  try {
    const [resTeachers, resSubjects, resClasses] = await Promise.all([
      api.get('/teachers'),
      api.get('/subjects', { params: { all: true } }),
      api.get('/classes', { params: { all: true } }),
    ]);
    if (resTeachers.data?.success) teachersList.value = resTeachers.data.data || [];
    if (resSubjects.data?.success) subjectsList.value = resSubjects.data.data || [];
    if (resClasses.data?.success) classesList.value = resClasses.data.data || [];
  } catch (err) {
    console.error('Gagal mengambil lookup assignment:', err);
  }
};

const fetchAssignments = async () => {
  loadingAssignments.value = true;
  try {
    const res = await api.get('/teacher-assignments', {
      params: { academic_year_id: props.activeYearId || undefined }
    });
    if (res.data.success) {
      assignmentsList.value = res.data.data || [];
    }
  } catch (err) {
    console.error('Gagal memuat pembagian mengajar guru:', err);
  } finally {
    loadingAssignments.value = false;
  }
};

const uniqueTeachersCount = computed(() => {
  const ids = new Set(assignmentsList.value.map((a: any) => a.teacher_id));
  return ids.size;
});

const uniqueClassesCount = computed(() => {
  const ids = new Set(assignmentsList.value.map((a: any) => a.class_id));
  return ids.size;
});

const filteredAssignments = computed(() => {
  return assignmentsList.value.filter((item: any) => {
    if (filterSubject.value && item.subject_id !== Number(filterSubject.value)) return false;
    if (filterTeacher.value && item.teacher_id !== Number(filterTeacher.value)) return false;
    if (filterClass.value && item.class_id !== Number(filterClass.value)) return false;
    return true;
  });
});

const openCreateModal = () => {
  isEditing.value = false;
  editingId.value = null;
  assignmentForm.value = {
    teacher_id: teachersList.value[0]?.id || '',
    subject_id: subjectsList.value[0]?.id || '',
    class_id: classesList.value[0]?.id || '',
    academic_year_id: props.activeYearId || null,
    jam_per_minggu: 4,
    keterangan: 'Reguler (Seluruh Sesi)',
    keterangan_custom: '',
  };
  isModalOpen.value = true;
};

const openEditModal = (item: any) => {
  isEditing.value = true;
  editingId.value = item.id;
  
  let ket = item.keterangan || 'Reguler (Seluruh Sesi)';
  let custom = '';
  if (ket !== 'Reguler (Seluruh Sesi)' && !ket.includes('Pagi') && !ket.includes('Siang')) {
    custom = ket;
    ket = 'Sesi Tertentu';
  }

  assignmentForm.value = {
    teacher_id: item.teacher_id,
    subject_id: item.subject_id,
    class_id: item.class_id,
    academic_year_id: item.academic_year_id || props.activeYearId || null,
    jam_per_minggu: item.jam_per_minggu || 4,
    keterangan: ket,
    keterangan_custom: custom,
  };
  isModalOpen.value = true;
};

const saveAssignmentForm = async () => {
  savingAssignment.value = true;
  try {
    const payload = { ...assignmentForm.value };
    if (!payload.academic_year_id && props.activeYearId) {
      payload.academic_year_id = props.activeYearId;
    }
    if (payload.keterangan === 'Sesi Tertentu') {
      payload.keterangan = payload.keterangan_custom || 'Sesi Tertentu';
    }

    if (isEditing.value && editingId.value) {
      const res = await api.put(`/teacher-assignments/${editingId.value}`, payload);
      if (res.data.success) {
        showSuccess('Berhasil!', 'Pembagian mengajar berhasil diperbarui dan disinkronkan ke jadwal.');
        isModalOpen.value = false;
        fetchAssignments();
      }
    } else {
      const res = await api.post('/teacher-assignments', payload);
      if (res.data.success) {
        showSuccess('Berhasil!', 'Penugasan mengajar baru berhasil disimpan.');
        isModalOpen.value = false;
        fetchAssignments();
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err, 'Terjadi kendala saat menyimpan penugasan guru.');
  } finally {
    savingAssignment.value = false;
  }
};

const handleDeleteAssignment = async (item: any) => {
  const confirmed = await confirmDeleteWithKeyword(`penugasan ${item.teacher?.nama_lengkap || 'guru'} pada ${item.subject?.nama || 'mapel'}`);
  if (!confirmed) return;

  try {
    const res = await api.delete(`/teacher-assignments/${item.id}`);
    if (res.data.success) {
      showSuccess('Berhasil Dihapus!', res.data.message || 'Penugasan mengajar berhasil dihapus.');
      fetchAssignments();
    }
  } catch (err: any) {
    showError('Gagal Menghapus!', err, 'Penugasan gagal dihapus.');
  }
};

const syncAssignmentsWithSchedule = async () => {
  const confirmed = await showConfirm(
    'Sinkronisasi Penugasan ke Jadwal',
    'Apakah Anda ingin menerapkan guru pengampu tema/pelajaran dari daftar ini ke seluruh slot jadwal mingguan yang cocok?',
    'Ya, Sinkronkan Sekarang'
  );
  if (!confirmed) return;

  syncingAssignments.value = true;
  try {
    const res = await api.post('/teacher-assignments/sync-to-schedules', {
      academic_year_id: props.activeYearId || undefined
    });
    if (res.data.success) {
      showSuccess('Sinkronisasi Sukses!', res.data.message || 'Seluruh pengampu tema telah diperbarui di jadwal mingguan.');
      fetchAssignments();
    }
  } catch (err: any) {
    showError('Gagal Sinkronisasi!', err, 'Terjadi kendala saat memperbarui jadwal.');
  } finally {
    syncingAssignments.value = false;
  }
};

defineExpose({
  fetchAssignments,
  openCreateModal,
  openCreateAssignmentModal: openCreateModal,
});

onMounted(async () => {
  await Promise.all([
    fetchAssignments(),
    loadLookups(),
  ]);
});
</script>
