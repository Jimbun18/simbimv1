<template>
  <div class="space-y-6 animate-in fade-in duration-200">
    <!-- Top Navigation & Action Banner -->
    <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-3.5">
        <button
          @click="$emit('cancel')"
          type="button"
          class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center justify-center shrink-0 shadow-xs group cursor-pointer"
          title="Kembali ke Mata Pelajaran & Tematik"
        >
          <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </button>
        <div>
          <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
            <span>Kurikulum</span>
            <span>/</span>
            <button type="button" @click="$emit('cancel')" class="hover:text-purple-600 hover:underline cursor-pointer">Mata Pelajaran & Tematik</button>
            <span>/</span>
            <span class="text-purple-700 font-bold">{{ isEditing ? 'Edit Tema/Mapel' : 'Tambah Baru' }}</span>
          </div>
          <h1 class="text-lg sm:text-2xl font-black text-gray-900 leading-tight">
            {{ isEditing ? `Edit: ${formData.nama || '...'}` : 'Formulir Pelajaran & Tematik Baru' }}
          </h1>
          <p class="text-xs text-gray-500 mt-0.5">
            Kelola kurikulum Tematik Terpadu (Tema 1-5+) maupun mata pelajaran reguler mandiri.
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
          <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span>{{ saving ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Buat Pelajaran / Tema') }}</span>
        </button>
      </div>
    </div>

    <!-- Main Form Grid: 12 Cols -->
    <form @submit.prevent="submitForm" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- LEFT COLUMN: Live Preview Card (4 cols) -->
      <div class="lg:col-span-4 space-y-6">
        <div
          :class="formData.is_tematik ? 'from-slate-900 via-purple-950 to-slate-900' : 'from-slate-900 via-emerald-950 to-slate-900'"
          class="bg-gradient-to-br p-5 rounded-2xl text-white shadow-lg space-y-4"
        >
          <div class="flex items-center justify-between text-xs font-bold" :class="formData.is_tematik ? 'text-purple-300' : 'text-emerald-300'">
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full animate-ping" :class="formData.is_tematik ? 'bg-purple-400' : 'bg-emerald-400'"></span>
              PREVIEW KARTU KURIKULUM
            </span>
            <span class="font-mono text-[10px] text-slate-400">{{ formData.kode || 'KODE' }}</span>
          </div>

          <div class="pt-2">
            <span
              v-if="formData.is_tematik"
              class="px-2.5 py-1 bg-purple-500/30 text-purple-200 border border-purple-400/30 rounded-lg text-xs font-bold"
            >
              ✨ TEMATIK (Tema {{ formData.tema_nomor || 1 }})
            </span>
            <span
              v-else
              class="px-2.5 py-1 bg-emerald-500/30 text-emerald-200 border border-emerald-400/30 rounded-lg text-xs font-bold uppercase"
            >
              📘 {{ formData.kelompok || 'REGULER' }}
            </span>

            <h3 class="text-xl font-black mt-2 text-white">
              {{ formData.nama || 'Nama Pelajaran / Tema' }}
            </h3>
            <p class="text-xs text-slate-300 mt-0.5">
              Target: {{ formData.tingkat_berlaku || 'Semua Tingkat' }}
            </p>
          </div>

          <!-- Muatan Chips di Preview -->
          <div v-if="formData.is_tematik && formData.muatan_pelajaran && formData.muatan_pelajaran.length" class="space-y-1 pt-1">
            <span class="text-[10px] font-bold text-purple-300 uppercase">Muatan Campuran:</span>
            <div class="flex flex-wrap gap-1">
              <span
                v-for="m in formData.muatan_pelajaran"
                :key="m"
                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white"
              >
                {{ m }}
              </span>
            </div>
          </div>

          <div class="pt-3 border-t border-white/10 flex justify-between text-xs text-slate-300">
            <span>Beban: <strong>{{ formData.jam_per_minggu || 4 }} JP / Minggu</strong></span>
            <span>KKM: <strong>{{ formData.kkm || 75 }}</strong></span>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN: Form Inputs (8 cols) -->
      <div class="lg:col-span-8 space-y-6">
        <!-- Model Switcher Card -->
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm space-y-3">
          <label class="block font-bold text-gray-800 text-xs">Pilih Model Kurikulum *</label>
          <div class="grid grid-cols-2 gap-3">
            <button
              type="button"
              @click="formData.is_tematik = true; formData.kelompok = 'tematik'; onTemaNomorChange()"
              :class="formData.is_tematik ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/20' : 'bg-gray-50 text-gray-700 border border-gray-200 hover:bg-gray-100 font-semibold'"
              class="py-3 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition cursor-pointer"
            >
              <span>✨</span>
              <span>Tematik Terpadu (Tema 1 s.d 5+)</span>
            </button>
            <button
              type="button"
              @click="formData.is_tematik = false; formData.kelompok = 'umum'"
              :class="!formData.is_tematik ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'bg-gray-50 text-gray-700 border border-gray-200 hover:bg-gray-100 font-semibold'"
              class="py-3 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition cursor-pointer"
            >
              <span>📘</span>
              <span>Reguler / Mandiri (PAI, PJOK, dll)</span>
            </button>
          </div>
        </div>

        <!-- Section: Jika Tematik -->
        <div v-if="formData.is_tematik" class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
          <h3 class="text-sm font-bold text-purple-900 border-b pb-3">Konfigurasi Tematik Terpadu</h3>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Nomor Tema *</label>
              <select
                v-model.number="formData.tema_nomor"
                @change="onTemaNomorChange"
                required
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white font-bold text-purple-700 focus:ring-2 focus:ring-purple-500 outline-none"
              >
                <option v-for="n in 12" :key="n" :value="n">Tema {{ n }}</option>
              </select>
            </div>

            <div class="sm:col-span-2">
              <label class="block font-semibold text-gray-700 text-xs mb-1">Kode Tema *</label>
              <input
                v-model="formData.kode"
                type="text"
                required
                placeholder="Contoh: TEMA-1"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs uppercase font-mono font-bold focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 text-xs mb-1">Judul / Nama Tema Lengkap *</label>
            <input
              v-model="formData.nama"
              type="text"
              required
              placeholder="Contoh: Tema 1: Indahnya Kebersamaan"
              class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold text-gray-900 focus:ring-2 focus:ring-purple-500 outline-none"
            />
          </div>

          <!-- Muatan Campuran Pelajaran Selector -->
          <div class="space-y-2 pt-2">
            <label class="block font-bold text-gray-800 text-xs">
              Muatan Campuran Pelajaran (Sub-Pelajaran) *
            </label>
            <p class="text-[11px] text-gray-500">
              Pilih muatan yang digabung di dalam tema ini (contoh: Tema 1 gabungan Matematika dan IPA):
            </p>

            <!-- Quick Badges -->
            <div class="flex flex-wrap gap-1.5 pt-1">
              <button
                type="button"
                v-for="opt in commonMuatanOptions"
                :key="opt"
                @click="toggleMuatan(opt)"
                :class="formData.muatan_pelajaran.includes(opt) ? 'bg-purple-600 text-white font-bold shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                class="px-3 py-1.5 rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5"
              >
                <span>{{ formData.muatan_pelajaran.includes(opt) ? '✓' : '+' }}</span>
                <span>{{ opt }}</span>
              </button>
            </div>

            <!-- Custom Muatan Input -->
            <div class="flex gap-2 pt-1">
              <input
                v-model="customMuatanInput"
                @keyup.enter.prevent="addCustomMuatan"
                type="text"
                placeholder="Ketik nama muatan lain lalu tekan Tambah..."
                class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-purple-500 outline-none"
              />
              <button
                type="button"
                @click="addCustomMuatan"
                class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold text-xs transition cursor-pointer"
              >
                + Tambah Muatan
              </button>
            </div>

            <!-- Active Muatan Chips -->
            <div v-if="formData.muatan_pelajaran.length" class="mt-2 flex flex-wrap gap-1.5 p-3 bg-purple-50/50 rounded-xl border border-purple-100">
              <span class="text-[11px] text-purple-700 font-bold self-center mr-1">Terpilih:</span>
              <span
                v-for="(m, idx) in formData.muatan_pelajaran"
                :key="m"
                class="px-2.5 py-1 bg-white text-purple-900 rounded-lg font-bold text-xs border border-purple-200 shadow-2xs flex items-center gap-1.5"
              >
                <span>{{ m }}</span>
                <button type="button" @click="removeMuatan(Number(idx))" class="text-rose-500 hover:text-rose-700 font-black cursor-pointer">&times;</button>
              </span>
            </div>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 text-xs mb-1">Deskripsi / Ruang Lingkup Tema</label>
            <textarea
              v-model="formData.deskripsi"
              rows="2"
              placeholder="Catatan kompetensi dasar materi tema..."
              class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-purple-500 outline-none"
            ></textarea>
          </div>
        </div>

        <!-- Section: Jika Reguler -->
        <div v-else class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
          <h3 class="text-sm font-bold text-gray-900 border-b pb-3">Informasi Mata Pelajaran Reguler</h3>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
              <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Mata Pelajaran *</label>
              <input
                v-model="formData.nama"
                type="text"
                required
                placeholder="Contoh: Pendidikan Agama Islam & Budi Pekerti"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold text-gray-900 focus:ring-2 focus:ring-emerald-500 outline-none"
              />
            </div>
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Kode Mapel *</label>
              <input
                v-model="formData.kode"
                type="text"
                required
                placeholder="Contoh: PAI"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs uppercase font-mono font-bold focus:ring-2 focus:ring-emerald-500 outline-none"
              />
            </div>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 text-xs mb-1">Kelompok Mata Pelajaran *</label>
            <select v-model="formData.kelompok" required class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white focus:ring-2 focus:ring-emerald-500 outline-none">
              <option value="umum">Umum</option>
              <option value="agama">Agama</option>
              <option value="muatan_lokal">Muatan Lokal</option>
              <option value="ekstrakurikuler">Ekstrakurikuler</option>
            </select>
          </div>
        </div>

        <!-- Tingkat Berlaku, JP & KKM -->
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Tingkat Kelas</label>
              <select v-model="formData.tingkat_berlaku" class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white focus:ring-2 focus:ring-purple-500 outline-none">
                <option value="Semua Tingkat">Semua Tingkat</option>
                <option value="Kelas 1">Kelas 1</option>
                <option value="Kelas 2">Kelas 2</option>
                <option value="Kelas 3">Kelas 3</option>
                <option value="Kelas 4">Kelas 4</option>
                <option value="Kelas 5">Kelas 5</option>
                <option value="Kelas 6">Kelas 6</option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Beban Jam / Minggu (JP)</label>
              <input
                v-model.number="formData.jam_per_minggu"
                type="number"
                min="1"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">KKM Standar</label>
              <input
                v-model.number="formData.kkm"
                type="number"
                min="0"
                max="100"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>
          </div>
        </div>

        <!-- Bottom Action Bar -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between gap-4">
          <button
            @click="$emit('cancel')"
            type="button"
            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition flex items-center gap-2 cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Kembali ke Pelajaran</span>
          </button>

          <div class="flex items-center gap-3">
            <button
              @click="$emit('cancel')"
              type="button"
              class="px-4 py-2.5 text-gray-500 hover:text-gray-700 text-xs font-semibold transition cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md shadow-purple-600/25 transition disabled:opacity-50 active:scale-95 cursor-pointer"
            >
              <svg v-if="saving" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <span>{{ saving ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Buat Pelajaran / Tema') }}</span>
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import api from '../../services/api';
import { showSuccess, showError } from '../../services/swal';

const props = defineProps<{
  initialData?: any;
  subjectItem?: any;
}>();

const emit = defineEmits<{
  (e: 'saved'): void;
  (e: 'cancel'): void;
}>();

const isEditing = ref(false);
const editingId = ref<number | null>(null);
const saving = ref(false);
const customMuatanInput = ref('');
const commonMuatanOptions = [
  'Matematika', 'IPA', 'Bahasa Indonesia', 'PPKn', 'SBdP', 'IPS', 'PJOK', 'Pendidikan Agama Islam', 'Bahasa Inggris', 'Bahasa Jawa'
];

const formData = ref<any>({
  is_tematik: true,
  tema_nomor: 1,
  nama: 'Tema 1: Indahnya Kebersamaan',
  kode: 'TEMA-1',
  muatan_pelajaran: ['Matematika', 'IPA'],
  deskripsi: '',
  kelompok: 'tematik',
  tingkat_berlaku: 'Kelas 1',
  jam_per_minggu: 6,
  kkm: 75,
  status: 'aktif',
});

const initFormData = () => {
  const s = props.initialData || props.subjectItem;
  if (s && s.id) {
    isEditing.value = true;
    editingId.value = s.id;
    formData.value = {
      is_tematik: Boolean(s.is_tematik),
      tema_nomor: s.tema_nomor || 1,
      nama: s.nama || '',
      kode: s.kode || '',
      muatan_pelajaran: Array.isArray(s.muatan_pelajaran) ? [...s.muatan_pelajaran] : [],
      deskripsi: s.deskripsi || '',
      kelompok: String(s.kelompok || (s.is_tematik ? 'tematik' : 'umum')).toLowerCase(),
      tingkat_berlaku: s.tingkat_berlaku || 'Semua Tingkat',
      jam_per_minggu: s.jam_per_minggu || 4,
      kkm: s.kkm || 75,
      status: String(s.status || 'aktif').toLowerCase(),
    };
  } else {
    isEditing.value = false;
    editingId.value = null;
    formData.value = {
      is_tematik: true,
      tema_nomor: 1,
      nama: 'Tema 1: Indahnya Kebersamaan',
      kode: 'TEMA-1',
      muatan_pelajaran: ['Matematika', 'IPA'],
      deskripsi: '',
      kelompok: 'tematik',
      tingkat_berlaku: 'Kelas 1',
      jam_per_minggu: 6,
      kkm: 75,
      status: 'aktif',
    };
  }
};

watch(
  () => props.initialData || props.subjectItem,
  () => {
    initFormData();
  },
  { immediate: true }
);

const onTemaNomorChange = () => {
  if (formData.value.is_tematik) {
    formData.value.kode = `TEMA-${formData.value.tema_nomor}`;
    if (!isEditing.value) {
      formData.value.nama = `Tema ${formData.value.tema_nomor}: `;
    }
  }
};

const toggleMuatan = (opt: string) => {
  if (!formData.value.muatan_pelajaran) formData.value.muatan_pelajaran = [];
  const idx = formData.value.muatan_pelajaran.indexOf(opt);
  if (idx >= 0) {
    formData.value.muatan_pelajaran.splice(idx, 1);
  } else {
    formData.value.muatan_pelajaran.push(opt);
  }
};

const addCustomMuatan = () => {
  const val = customMuatanInput.value.trim();
  if (val && !formData.value.muatan_pelajaran.includes(val)) {
    formData.value.muatan_pelajaran.push(val);
    customMuatanInput.value = '';
  }
};

const removeMuatan = (index: number | string) => {
  formData.value.muatan_pelajaran.splice(Number(index), 1);
};

const submitForm = async () => {
  saving.value = true;
  try {
    const payload = { ...formData.value };
    if (payload.is_tematik) {
      payload.kelompok = 'tematik';
    } else if (payload.kelompok) {
      payload.kelompok = String(payload.kelompok).toLowerCase();
    }
    if (payload.status) {
      payload.status = String(payload.status).toLowerCase();
    }

    if (isEditing.value && editingId.value) {
      const res = await api.put(`/subjects/${editingId.value}`, payload);
      if (res.data.success) {
        showSuccess('Berhasil!', 'Mata pelajaran / Tema berhasil diperbarui.');
        emit('saved');
      }
    } else {
      const res = await api.post('/subjects', payload);
      if (res.data.success) {
        showSuccess('Berhasil!', 'Mata pelajaran / Tema baru berhasil ditambahkan.');
        emit('saved');
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err, 'Periksa kembali kelengkapan formulir.');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  initFormData();
});
</script>
