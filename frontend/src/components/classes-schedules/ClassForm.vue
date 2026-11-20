<template>
  <div class="space-y-6 animate-in fade-in duration-200">
    <!-- Top Navigation & Action Banner -->
    <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-3.5">
        <button
          @click="$emit('cancel')"
          type="button"
          class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center justify-center shrink-0 shadow-xs group cursor-pointer"
          title="Kembali ke Daftar Rombel Kelas"
        >
          <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </button>
        <div>
          <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
            <span>Akademik</span>
            <span>/</span>
            <button type="button" @click="$emit('cancel')" class="hover:text-purple-600 hover:underline cursor-pointer">Rombel Kelas</button>
            <span>/</span>
            <span class="text-purple-700 font-bold">{{ isEditing ? 'Edit Rombel' : 'Tambah Paralel Baru' }}</span>
          </div>
          <h1 class="text-lg sm:text-2xl font-black text-gray-900 leading-tight">
            {{ isEditing ? `Edit Rombel: ${formData.nama || '...'}` : `Tambah Paralel Baru (Kelas ${formData.tingkat || 1})` }}
          </h1>
          <p class="text-xs text-gray-500 mt-0.5">
            {{ isEditing ? 'Perbarui identitas rombel paralel, wali kelas penanggung jawab, dan ruangan.' : 'Daftarkan rombel paralel baru untuk jenjang SD (Tingkat 1 s/d 6).' }}
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2.5 self-end sm:self-center">
        <button
          @click="$emit('cancel')"
          type="button"
          class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition cursor-pointer"
        >
          Batal
        </button>
        <button
          @click="submitForm"
          :disabled="saving"
          type="button"
          class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md shadow-purple-600/25 transition disabled:opacity-50 active:scale-95 cursor-pointer"
        >
          <svg v-if="saving" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <span v-else>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
          </span>
          <span>{{ saving ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Buat Rombel Paralel') }}</span>
        </button>
      </div>
    </div>

    <!-- Form & Preview Container -->
    <form @submit.prevent="submitForm" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- LEFT COLUMN: Live Preview Card (4 cols) -->
      <div class="lg:col-span-4 space-y-6">
        <div class="bg-gradient-to-br from-slate-900 via-purple-950 to-slate-900 p-5 rounded-2xl text-white shadow-lg space-y-4">
          <div class="flex items-center justify-between text-purple-400 text-xs font-bold">
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-purple-400 animate-ping"></span>
              KARTU ROMBEL (PREVIEW)
            </span>
            <span class="font-mono text-[10px] text-slate-400">SDI BINA INSAN MANDIRI</span>
          </div>

          <div class="pt-2">
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-1 bg-purple-500/30 text-purple-200 border border-purple-400/30 rounded-lg text-xs font-mono font-bold">
                {{ formData.kode || 'KODE' }}
              </span>
              <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 rounded-md text-[10px] font-bold">
                Paralel {{ formData.paralel || 'A' }}
              </span>
            </div>
            <h3 class="text-xl font-black mt-2 text-white leading-snug">
              {{ formData.nama || 'Nama Rombel Kelas' }}
            </h3>
            <p class="text-xs text-slate-300 mt-0.5">
              Tingkat {{ formData.tingkat || 1 }} ({{ getFaseLabel(formData.tingkat) }})
            </p>
          </div>

          <div class="pt-3 border-t border-white/10 space-y-1.5 text-xs text-slate-300">
            <div class="flex justify-between">
              <span>Tahun Ajaran:</span>
              <span class="font-bold text-white">{{ getAcademicYearName(formData.academic_year_id) }}</span>
            </div>
            <div class="flex justify-between">
              <span>Wali Kelas:</span>
              <span class="font-bold text-white text-right truncate max-w-[160px]">{{ getTeacherName(formData.wali_kelas_id) }}</span>
            </div>
            <div class="flex justify-between">
              <span>Ruang Belajar:</span>
              <span class="font-bold text-white">{{ getRoomName(formData.room_id) }}</span>
            </div>
            <div class="flex justify-between">
              <span>Kapasitas:</span>
              <span class="font-bold text-purple-300">{{ formData.kapasitas || 28 }} Siswa</span>
            </div>
          </div>
        </div>

        <!-- Quick Help Note -->
        <div class="bg-purple-50/70 border border-purple-200/80 rounded-2xl p-4 text-xs text-purple-900 space-y-1.5">
          <p class="font-bold flex items-center gap-1.5">
            <span>💡</span> Panduan Tingkat & Paralel SD
          </p>
          <p class="text-purple-800 leading-relaxed text-[11px]">
            Tingkat terkunci pada jenjang SD (1 s/d 6). Jika rombel di tingkat ini bertambah, gunakan huruf paralel (misal: <strong>A, B, C</strong>). Anda bebas memberi nama unik pada rombel (contoh: <em>Kelas 1 Abu Bakar</em> atau <em>Kelas 1-A</em>).
          </p>
        </div>
      </div>

      <!-- RIGHT COLUMN: Form Inputs (8 cols) -->
      <div class="lg:col-span-8 space-y-6">
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
          <h3 class="text-sm font-bold text-gray-900 border-b pb-3 flex items-center justify-between">
            <span>Identitas Tingkat & Paralel Rombel</span>
            <span class="text-[11px] text-purple-600 font-normal">Tingkat 1 s/d 6 (Permanen SD)</span>
          </h3>

          <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
            <!-- Tahun Ajaran -->
            <div class="sm:col-span-6">
              <label class="block font-semibold text-gray-700 text-xs mb-1">Tahun Ajaran *</label>
              <select
                v-model="formData.academic_year_id"
                required
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold bg-white focus:ring-2 focus:ring-purple-500 outline-none"
              >
                <option v-for="y in academicYearsList" :key="y.id" :value="y.id">
                  {{ y.nama }} {{ (y.is_aktif || y.status === 'aktif') ? '★ (Aktif)' : '' }}
                </option>
              </select>
            </div>

            <!-- Tingkat Kelas (1-6 SD) -->
            <div class="sm:col-span-6">
              <label class="block font-semibold text-gray-700 text-xs mb-1">Tingkat Kelas (Jenjang SD) *</label>
              <select
                v-model.number="formData.tingkat"
                @change="handleTingkatChange"
                required
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold bg-white focus:ring-2 focus:ring-purple-500 outline-none"
              >
                <option :value="1">Kelas 1 — Fase A (Fondasi Dasar)</option>
                <option :value="2">Kelas 2 — Fase A (Pemantapan Dasar)</option>
                <option :value="3">Kelas 3 — Fase B (Transisi Madya)</option>
                <option :value="4">Kelas 4 — Fase B (Pemantapan Madya)</option>
                <option :value="5">Kelas 5 — Fase C (Tingkat Lanjut)</option>
                <option :value="6">Kelas 6 — Fase C (Kelulusan)</option>
              </select>
            </div>

            <!-- Paralel / Huruf Rombel -->
            <div class="sm:col-span-4">
              <label class="block font-semibold text-gray-700 text-xs mb-1">Huruf Paralel *</label>
              <div class="space-y-1.5">
                <input
                  v-model="formData.paralel"
                  @input="handleParalelInput"
                  type="text"
                  required
                  placeholder="Contoh: A"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold uppercase focus:ring-2 focus:ring-purple-500 outline-none"
                />
                <!-- Quick Selection Pills -->
                <div class="flex items-center gap-1">
                  <button
                    v-for="letter in ['A', 'B', 'C', 'D']"
                    :key="letter"
                    type="button"
                    @click="selectParalel(letter)"
                    :class="[
                      'px-2 py-0.5 rounded text-[10px] font-bold border transition cursor-pointer',
                      (formData.paralel || '').toUpperCase() === letter
                        ? 'bg-purple-600 text-white border-purple-600'
                        : 'bg-slate-50 text-slate-600 hover:bg-purple-50 border-slate-200'
                    ]"
                  >
                    {{ letter }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Kode Kelas -->
            <div class="sm:col-span-4">
              <label class="block font-semibold text-gray-700 text-xs mb-1">Kode Kelas *</label>
              <input
                v-model="formData.kode"
                type="text"
                required
                placeholder="Contoh: 1-A"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono uppercase font-bold focus:ring-2 focus:ring-purple-500 outline-none"
              />
              <p class="text-[10px] text-gray-400 mt-1">Otomatis disarankan format: [Tingkat]-[Paralel]</p>
            </div>

            <!-- Kapasitas Maksimal -->
            <div class="sm:col-span-4">
              <label class="block font-semibold text-gray-700 text-xs mb-1">Kapasitas (Siswa)</label>
              <input
                v-model.number="formData.kapasitas"
                type="number"
                min="1"
                placeholder="28"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>

            <!-- Nama Rombel -->
            <div class="sm:col-span-12">
              <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Rombel Kelas *</label>
              <input
                v-model="formData.nama"
                type="text"
                required
                placeholder="Contoh: Kelas 1 Abu Bakar atau Kelas 1-A"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-purple-500 outline-none"
              />
              <p class="text-[10px] text-gray-400 mt-1">Bisa menggunakan nama standar (Kelas 1-A) atau nama kehormatan/sahabat/tokoh Islam.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t">
            <!-- Wali Kelas -->
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Wali Kelas Penanggung Jawab</label>
              <select
                v-model="formData.wali_kelas_id"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white focus:ring-2 focus:ring-purple-500 outline-none"
              >
                <option :value="null">-- Belum Ditugaskan --</option>
                <option v-for="t in teachersList" :key="t.id" :value="t.id">
                  {{ t.nama_lengkap }}{{ t.jenis_ptk === 'kepala_sekolah' ? ' (Kepala Sekolah)' : '' }}
                </option>
              </select>
            </div>

            <!-- Ruangan Kelas -->
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Ruangan Kelas</label>
              <select
                v-model="formData.room_id"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white focus:ring-2 focus:ring-purple-500 outline-none"
              >
                <option :value="null">-- Tanpa Ruangan Tertentu --</option>
                <option v-for="r in rooms" :key="r.id" :value="r.id">
                  {{ r.nama }} (Kapasitas: {{ r.kapasitas || '-' }})
                </option>
              </select>
            </div>
          </div>

          <div class="pt-2 flex items-center justify-between">
            <label class="text-xs font-semibold text-gray-700">Status Keaktifan Rombel</label>
            <div class="flex items-center gap-3">
              <label class="inline-flex items-center gap-1.5 text-xs cursor-pointer">
                <input type="radio" value="aktif" v-model="formData.status" class="text-purple-600 focus:ring-purple-500" />
                <span class="font-medium text-emerald-700">Aktif</span>
              </label>
              <label class="inline-flex items-center gap-1.5 text-xs cursor-pointer">
                <input type="radio" value="nonaktif" v-model="formData.status" class="text-purple-600 focus:ring-purple-500" />
                <span class="font-medium text-gray-500">Nonaktif</span>
              </label>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import api from '../../services/api';
import { showSuccess, showError } from '../../services/swal';

const props = defineProps<{
  initialData?: any;
  classItem?: any;
  activeYearId?: number | null;
  defaultTingkat?: number | null;
  suggestedParalel?: string | null;
}>();

const emit = defineEmits<{
  (e: 'saved'): void;
  (e: 'cancel'): void;
}>();

const isEditing = ref(false);
const editingId = ref<number | null>(null);
const saving = ref(false);

const teachersList = ref<any[]>([]);
const rooms = ref<any[]>([]);
const academicYearsList = ref<any[]>([]);

const formData = ref<any>({
  academic_year_id: props.activeYearId || null,
  kode: '',
  nama: '',
  tingkat: 1,
  paralel: 'A',
  wali_kelas_id: null,
  room_id: null,
  kapasitas: 28,
  status: 'aktif',
});

// Penanda apakah nama diisi manual oleh user atau masih otomatis
const isCustomName = ref(false);

const getFaseLabel = (tingkat: number) => {
  if (tingkat <= 2) return 'Fase A';
  if (tingkat <= 4) return 'Fase B';
  return 'Fase C';
};

const selectParalel = (letter: string) => {
  formData.value.paralel = letter;
  handleParalelInput();
};

const handleParalelInput = () => {
  const p = (formData.value.paralel || '').toUpperCase().trim();
  formData.value.paralel = p;

  if (!isEditing.value) {
    formData.value.kode = `${formData.value.tingkat}-${p || 'A'}`;
    if (!isCustomName.value) {
      formData.value.nama = `Kelas ${formData.value.tingkat}-${p || 'A'}`;
    }
  }
};

const handleTingkatChange = () => {
  if (!isEditing.value) {
    const p = (formData.value.paralel || 'A').toUpperCase().trim();
    formData.value.kode = `${formData.value.tingkat}-${p}`;
    if (!isCustomName.value) {
      formData.value.nama = `Kelas ${formData.value.tingkat}-${p}`;
    }
  }
};

const initFormData = () => {
  const c = props.initialData || props.classItem;
  if (c && c.id) {
    isEditing.value = true;
    editingId.value = c.id;
    isCustomName.value = true;
    formData.value = {
      academic_year_id: c.academic_year_id || props.activeYearId || (academicYearsList.value.find(y => y.is_aktif || y.status === 'aktif')?.id ?? null),
      kode: c.kode || '',
      nama: c.nama || '',
      tingkat: c.tingkat || 1,
      paralel: c.paralel || 'A',
      wali_kelas_id: c.wali_kelas_id || c.wali_kelas?.id || null,
      room_id: c.room_id || c.room?.id || null,
      kapasitas: c.kapasitas || 28,
      status: c.status || 'aktif',
    };
  } else {
    isEditing.value = false;
    editingId.value = null;
    isCustomName.value = false;

    const tingkat = props.defaultTingkat || 1;
    const paralel = (props.suggestedParalel || 'A').toUpperCase();
    const defaultYear = props.activeYearId || (academicYearsList.value.find(y => y.is_aktif || y.status === 'aktif')?.id ?? academicYearsList.value[0]?.id ?? null);

    formData.value = {
      academic_year_id: defaultYear,
      kode: `${tingkat}-${paralel}`,
      nama: `Kelas ${tingkat}-${paralel}`,
      tingkat: tingkat,
      paralel: paralel,
      wali_kelas_id: null,
      room_id: rooms.value.length > 0 ? rooms.value[0].id : null,
      kapasitas: 28,
      status: 'aktif',
    };
  }
};

watch(
  () => [props.initialData, props.classItem, props.defaultTingkat, props.suggestedParalel],
  () => {
    initFormData();
  },
  { immediate: true }
);

const loadLookups = async () => {
  try {
    const [resTeachers, resRooms, resYears] = await Promise.all([
      api.get('/teachers'),
      api.get('/rooms'),
      api.get('/academic-years'),
    ]);
    if (resTeachers.data?.success) teachersList.value = resTeachers.data.data || [];
    if (resRooms.data?.success) rooms.value = resRooms.data.data || [];
    if (resYears.data?.success) academicYearsList.value = resYears.data.data || [];

    // Fallback otomatis isi academic_year_id jika masih kosong
    if (!formData.value.academic_year_id && academicYearsList.value.length > 0) {
      const activeYear = academicYearsList.value.find(y => y.is_aktif || y.status === 'aktif');
      formData.value.academic_year_id = props.activeYearId || activeYear?.id || academicYearsList.value[0].id;
    }
  } catch (err) {
    console.error('Gagal mengambil lookup form kelas:', err);
  }
};

const getAcademicYearName = (id: any) => {
  if (!id) return '-';
  const y = academicYearsList.value.find(item => item.id === id);
  return y ? y.nama : '-';
};

const getTeacherName = (id: any) => {
  if (!id) return 'Belum Ditugaskan';
  const t = teachersList.value.find(item => item.id === id);
  return t ? t.nama_lengkap : 'Terhubung';
};

const getRoomName = (id: any) => {
  if (!id) return '-';
  const r = rooms.value.find(item => item.id === id);
  return r ? r.nama : '-';
};

const submitForm = async () => {
  saving.value = true;
  try {
    const payload = { ...formData.value };
    if (!payload.academic_year_id) {
      const activeYear = academicYearsList.value.find(y => y.is_aktif || y.status === 'aktif');
      payload.academic_year_id = props.activeYearId || activeYear?.id || academicYearsList.value[0]?.id;
    }

    if (isEditing.value && editingId.value) {
      const res = await api.put(`/classes/${editingId.value}`, payload);
      if (res.data.success) {
        showSuccess('Berhasil!', 'Data rombel kelas berhasil diperbarui.');
        emit('saved');
      }
    } else {
      const res = await api.post('/classes', payload);
      if (res.data.success) {
        showSuccess('Berhasil!', 'Rombel kelas baru berhasil dibuat.');
        emit('saved');
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err, 'Terjadi kesalahan saat menyimpan rombel.');
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  await loadLookups();
  initFormData();
});
</script>
