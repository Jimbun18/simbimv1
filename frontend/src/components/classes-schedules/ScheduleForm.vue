<template>
  <div class="space-y-6 animate-in fade-in duration-200">
    <!-- Top Navigation & Action Banner -->
    <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-3.5">
        <button
          @click="$emit('cancel')"
          type="button"
          class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center justify-center shrink-0 shadow-xs group cursor-pointer"
          title="Kembali ke Jadwal KBM"
        >
          <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </button>
        <div>
          <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
            <span>Akademik</span>
            <span>/</span>
            <button type="button" @click="$emit('cancel')" class="hover:text-purple-600 hover:underline cursor-pointer">Jadwal KBM</button>
            <span>/</span>
            <span class="text-purple-700 font-bold">{{ isEditing ? 'Edit Jadwal' : 'Tambah Jadwal Baru' }}</span>
          </div>
          <h1 class="text-lg sm:text-2xl font-black text-gray-900 leading-tight">
            {{ isEditing ? 'Edit Jadwal KBM' : 'Formulir Jadwal KBM Baru' }}
          </h1>
          <p class="text-xs text-gray-500 mt-0.5">
            Tentukan hari, alokasi jam/sesi, rombel kelas, mata pelajaran/tema, guru pengampu, dan ruangan.
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
          <span>{{ saving ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Buat Jadwal KBM') }}</span>
        </button>
      </div>
    </div>

    <!-- Main Form Grid: 12 Cols -->
    <form @submit.prevent="submitForm" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- LEFT COLUMN: Live Preview Card Jadwal (4 cols) -->
      <div class="lg:col-span-4 space-y-6">
        <div class="bg-gradient-to-br from-slate-900 via-purple-950 to-slate-900 p-5 rounded-2xl text-white shadow-lg space-y-4">
          <div class="flex items-center justify-between text-purple-300 text-xs font-bold">
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-purple-400 animate-ping"></span>
              KARTU JADWAL (PREVIEW)
            </span>
            <span class="font-mono text-[10px] text-slate-400 uppercase">{{ formData.hari }}</span>
          </div>

          <div class="pt-2">
            <span class="px-2.5 py-1 bg-white/20 text-white rounded-lg text-xs font-bold capitalize">
              {{ formData.hari }} • {{ getTimeSlotLabel(formData.time_slot_id) }}
            </span>

            <h3 class="text-lg font-black mt-3 text-white">
              {{ getSubjectName(formData.subject_id) }}
            </h3>
            <p class="text-xs text-purple-200 mt-0.5">
              Rombel: <strong class="text-white">{{ getClassName(formData.class_id) }}</strong>
            </p>
          </div>

          <div class="pt-3 border-t border-white/10 space-y-1.5 text-xs text-slate-300">
            <div class="flex justify-between">
              <span>Guru:</span>
              <span class="font-bold text-white">{{ getTeacherName(formData.teacher_id) }}</span>
            </div>
            <div class="flex justify-between">
              <span>Ruang:</span>
              <span class="font-bold text-white">{{ getRoomName(formData.room_id) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN: Form Inputs (8 cols) -->
      <div class="lg:col-span-8 space-y-6">
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
          <h3 class="text-sm font-bold text-gray-900 border-b pb-3">Alokasi Waktu & Rombel KBM</h3>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Hari Belajar *</label>
              <select
                v-model="formData.hari"
                required
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white font-bold capitalize focus:ring-2 focus:ring-purple-500 outline-none"
              >
                <option value="senin">Senin</option>
                <option value="selasa">Selasa</option>
                <option value="rabu">Rabu</option>
                <option value="kamis">Kamis</option>
                <option value="jumat">Jumat</option>
                <option value="sabtu">Sabtu</option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Sesi Jam Pelajaran (Time Slot) *</label>
              <select
                v-model="formData.time_slot_id"
                required
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white font-medium focus:ring-2 focus:ring-purple-500 outline-none"
              >
                <option v-for="ts in timeSlots" :key="ts.id" :value="ts.id">
                  {{ ts.nama }} ({{ ts.jam_mulai }} - {{ ts.jam_selesai }})
                </option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Rombel Kelas *</label>
              <select
                v-model="formData.class_id"
                required
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white font-bold text-gray-800 focus:ring-2 focus:ring-purple-500 outline-none"
              >
                <option v-for="c in classesList" :key="c.id" :value="c.id">{{ c.nama }} ({{ c.kode }})</option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Guru Pengampu *</label>
              <select
                v-model="formData.teacher_id"
                required
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white font-medium focus:ring-2 focus:ring-purple-500 outline-none"
              >
                <option v-for="t in teachersList" :key="t.id" :value="t.id">
                  {{ t.nama_lengkap }}{{ t.jenis_ptk === 'kepala_sekolah' ? ' (Kepala Sekolah)' : '' }}
                </option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 text-xs mb-1">Mata Pelajaran / Tema *</label>
            <select
              v-model="formData.subject_id"
              required
              class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white font-bold focus:ring-2 focus:ring-purple-500 outline-none"
            >
              <option :value="null" disabled>-- Pilih Pelajaran atau Tema --</option>
              <optgroup label="✨ Pelajaran Tematik Terpadu">
                <option v-for="sub in subjectsList.filter(s => s.is_tematik)" :key="sub.id" :value="sub.id">
                  {{ sub.display_name || sub.nama }} ({{ sub.kode }})
                </option>
              </optgroup>
              <optgroup label="📘 Mata Pelajaran Reguler / Mandiri">
                <option v-for="sub in subjectsList.filter(s => !s.is_tematik)" :key="sub.id" :value="sub.id">
                  {{ sub.nama }} ({{ sub.kode }})
                </option>
              </optgroup>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-gray-700 text-xs mb-1">Ruangan Khusus (Opsional)</label>
            <select
              v-model="formData.room_id"
              class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white focus:ring-2 focus:ring-purple-500 outline-none"
            >
              <option :value="null">-- Mengikuti Ruangan Kelas --</option>
              <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.nama }} ({{ r.kode }})</option>
            </select>
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
            <span>Kembali ke Jadwal</span>
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
              <span>{{ saving ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Buat Jadwal KBM') }}</span>
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
  scheduleItem?: any;
  activeYearId?: number | null;
  defaultClassId?: number | null;
}>();

const emit = defineEmits<{
  (e: 'saved'): void;
  (e: 'cancel'): void;
}>();

const isEditing = ref(false);
const editingId = ref<number | null>(null);
const saving = ref(false);

const classesList = ref<any[]>([]);
const timeSlots = ref<any[]>([]);
const teachersList = ref<any[]>([]);
const subjectsList = ref<any[]>([]);
const rooms = ref<any[]>([]);

const formData = ref<any>({
  academic_year_id: props.activeYearId || null,
  class_id: props.defaultClassId || null,
  subject_id: null,
  teacher_id: null,
  room_id: null,
  hari: 'senin',
  time_slot_id: null,
  status: 'aktif',
});

const initFormData = () => {
  const sch = props.initialData || props.scheduleItem;
  if (sch && sch.id) {
    isEditing.value = true;
    editingId.value = sch.id;
    formData.value = {
      academic_year_id: sch.academic_year_id || props.activeYearId || null,
      class_id: sch.class_id || sch.school_class?.id || null,
      subject_id: sch.subject_id || sch.subject?.id || null,
      teacher_id: sch.teacher_id || sch.teacher?.id || null,
      room_id: sch.room_id || sch.room?.id || null,
      hari: sch.hari || 'senin',
      time_slot_id: sch.time_slot_id || sch.time_slot?.id || null,
      status: sch.status || 'aktif',
    };
  } else {
    isEditing.value = false;
    editingId.value = null;
    formData.value = {
      academic_year_id: props.activeYearId || null,
      class_id: props.defaultClassId || null,
      subject_id: null,
      teacher_id: null,
      room_id: null,
      hari: 'senin',
      time_slot_id: null,
      status: 'aktif',
    };
  }
};

watch(
  () => props.initialData || props.scheduleItem,
  () => {
    initFormData();
  },
  { immediate: true }
);

const loadLookups = async () => {
  try {
    const [resClasses, resSlots, resTeachers, resSubjects, resRooms] = await Promise.all([
      api.get('/classes', { params: { all: true } }),
      api.get('/time-slots'),
      api.get('/teachers'),
      api.get('/subjects', { params: { all: true } }),
      api.get('/rooms'),
    ]);

    if (resClasses.data?.success) classesList.value = resClasses.data.data || [];
    if (resSlots.data?.success) timeSlots.value = resSlots.data.data || [];
    if (resTeachers.data?.success) teachersList.value = resTeachers.data.data || [];
    if (resSubjects.data?.success) subjectsList.value = resSubjects.data.data || [];
    if (resRooms.data?.success) rooms.value = resRooms.data.data || [];

    if (!isEditing.value) {
      if (!formData.value.class_id && classesList.value.length > 0) {
        formData.value.class_id = classesList.value[0].id;
      }
      if (!formData.value.time_slot_id && timeSlots.value.length > 0) {
        formData.value.time_slot_id = timeSlots.value[0].id;
      }
      if (!formData.value.teacher_id && teachersList.value.length > 0) {
        formData.value.teacher_id = teachersList.value[0].id;
      }
      if (!formData.value.subject_id && subjectsList.value.length > 0) {
        formData.value.subject_id = subjectsList.value[0].id;
      }
    }
  } catch (err) {
    console.error('Gagal mengambil lookup form jadwal:', err);
  }
};

const getTeacherName = (id: any) => {
  if (!id) return 'Belum Dipilih';
  const found = teachersList.value.find(t => t.id === id);
  return found ? found.nama_lengkap : 'Belum Dipilih';
};

const getRoomName = (id: any) => {
  if (!id) return 'Ruang Default';
  const found = rooms.value.find(r => r.id === id);
  return found ? `${found.nama} (${found.kode})` : 'Ruang Default';
};

const getClassName = (id: any) => {
  if (!id) return 'Belum Dipilih';
  const found = classesList.value.find(c => c.id === id);
  return found ? found.nama : 'Belum Dipilih';
};

const getSubjectName = (id: any) => {
  if (!id) return 'Pelajaran Belum Dipilih';
  const found = subjectsList.value.find(s => s.id === id);
  return found ? (found.display_name || found.nama) : 'Pelajaran Belum Dipilih';
};

const getTimeSlotLabel = (id: any) => {
  if (!id) return '';
  const found = timeSlots.value.find(ts => ts.id === id);
  return found ? `${found.nama} (${found.jam_mulai}-${found.jam_selesai})` : '';
};

const submitForm = async () => {
  saving.value = true;
  try {
    const payload = { ...formData.value };
    if (!payload.academic_year_id && props.activeYearId) {
      payload.academic_year_id = props.activeYearId;
    }

    if (isEditing.value && editingId.value) {
      const res = await api.put(`/schedules/${editingId.value}`, payload);
      if (res.data.success) {
        showSuccess('Berhasil!', 'Jadwal KBM berhasil diperbarui.');
        emit('saved');
      }
    } else {
      const res = await api.post('/schedules', payload);
      if (res.data.success) {
        showSuccess('Berhasil!', 'Jadwal KBM baru berhasil ditambahkan.');
        emit('saved');
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err, 'Terjadi konflik jadwal atau validasi gagal.');
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  await loadLookups();
  initFormData();
});
</script>
