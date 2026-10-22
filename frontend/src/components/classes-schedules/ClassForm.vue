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
            <span class="text-purple-700 font-bold">{{ isEditing ? 'Edit Rombel' : 'Tambah Rombel Baru' }}</span>
          </div>
          <h1 class="text-lg sm:text-2xl font-black text-gray-900 leading-tight">
            {{ isEditing ? `Edit Rombel: ${formData.nama || '...'}` : 'Formulir Rombel Kelas Baru' }}
          </h1>
          <p class="text-xs text-gray-500 mt-0.5">
            {{ isEditing ? 'Perbarui tingkat, nama rombel, wali kelas penanggung jawab, dan ruangan.' : 'Daftarkan rombel kelas baru dan tetapkan wali kelasnya.' }}
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
          <span>{{ saving ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Buat Rombel Kelas') }}</span>
        </button>
      </div>
    </div>

    <!-- Main Form Grid: 12 Cols -->
    <form @submit.prevent="submitForm" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- LEFT COLUMN: Live Preview Card (4 cols) -->
      <div class="lg:col-span-4 space-y-6">
        <div class="bg-gradient-to-br from-slate-900 via-purple-950 to-slate-900 p-5 rounded-2xl text-white shadow-lg space-y-4">
          <div class="flex items-center justify-between text-purple-400 text-xs font-bold">
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-purple-400 animate-ping"></span>
              KARTU ROMBEL (PREVIEW)
            </span>
            <span class="font-mono text-[10px] text-slate-400">SIM-BIM</span>
          </div>

          <div class="pt-2">
            <span class="px-2.5 py-1 bg-purple-500/30 text-purple-200 border border-purple-400/30 rounded-lg text-xs font-mono font-bold">
              {{ formData.kode || 'KODE' }}
            </span>
            <h3 class="text-xl font-black mt-2 text-white">
              {{ formData.nama || 'Nama Rombel Kelas' }}
            </h3>
            <p class="text-xs text-slate-300 mt-0.5">
              Tingkat {{ formData.tingkat || 1 }} • Paralel {{ formData.paralel || 'A' }}
            </p>
          </div>

          <div class="pt-3 border-t border-white/10 space-y-1.5 text-xs text-slate-300">
            <div class="flex justify-between">
              <span>Wali Kelas:</span>
              <span class="font-bold text-white">{{ getTeacherName(formData.wali_kelas_id) }}</span>
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
      </div>

      <!-- RIGHT COLUMN: Form Inputs (8 cols) -->
      <div class="lg:col-span-8 space-y-6">
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
          <h3 class="text-sm font-bold text-gray-900 border-b pb-3">Identitas & Spesifikasi Rombel</h3>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Kode Kelas *</label>
              <input
                v-model="formData.kode"
                type="text"
                required
                placeholder="Contoh: 1A"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono uppercase font-bold focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Rombel *</label>
              <input
                v-model="formData.nama"
                type="text"
                required
                placeholder="Contoh: Kelas 1 A (Al-Fatih)"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Tingkat (1 - 6) *</label>
              <input
                v-model.number="formData.tingkat"
                type="number"
                min="1"
                max="6"
                required
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Paralel / Huruf</label>
              <input
                v-model="formData.paralel"
                type="text"
                placeholder="A / B"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs uppercase focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Kapasitas Maksimal (Siswa)</label>
              <input
                v-model.number="formData.kapasitas"
                type="number"
                min="1"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
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

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Ruangan Kelas</label>
              <select
                v-model="formData.room_id"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white focus:ring-2 focus:ring-purple-500 outline-none"
              >
                <option :value="null">-- Pilih Ruangan --</option>
                <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.nama }} ({{ r.kode }})</option>
              </select>
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
            <span>Kembali ke Rombel</span>
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
              <span>{{ saving ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Buat Rombel Kelas') }}</span>
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
  classItem?: any;
  activeYearId?: number | null;
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

const initFormData = () => {
  const c = props.initialData || props.classItem;
  if (c && c.id) {
    isEditing.value = true;
    editingId.value = c.id;
    formData.value = {
      academic_year_id: c.academic_year_id || props.activeYearId || null,
      kode: c.kode || '',
      nama: c.nama || '',
      tingkat: c.tingkat || 1,
      paralel: c.paralel || '',
      wali_kelas_id: c.wali_kelas_id || c.wali_kelas?.id || null,
      room_id: c.room_id || c.room?.id || null,
      kapasitas: c.kapasitas || 28,
      status: c.status || 'aktif',
    };
  } else {
    isEditing.value = false;
    editingId.value = null;
    formData.value = {
      academic_year_id: props.activeYearId || null,
      kode: '',
      nama: '',
      tingkat: 1,
      paralel: 'A',
      wali_kelas_id: null,
      room_id: rooms.value.length > 0 ? rooms.value[0].id : null,
      kapasitas: 28,
      status: 'aktif',
    };
  }
};

watch(
  () => props.initialData || props.classItem,
  () => {
    initFormData();
  },
  { immediate: true }
);

const loadLookups = async () => {
  try {
    const [resTeachers, resRooms] = await Promise.all([
      api.get('/teachers'),
      api.get('/rooms'),
    ]);
    if (resTeachers.data?.success) teachersList.value = resTeachers.data.data || [];
    if (resRooms.data?.success) rooms.value = resRooms.data.data || [];
  } catch (err) {
    console.error('Gagal mengambil lookup form kelas:', err);
  }
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
    if (!payload.academic_year_id && props.activeYearId) {
      payload.academic_year_id = props.activeYearId;
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
