<template>
  <div v-if="guardian" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white rounded-3xl max-w-xl w-full max-h-[90vh] overflow-hidden shadow-2xl border border-gray-100 flex flex-col">
      <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between">
        <div>
          <h3 class="text-base font-bold text-gray-900">Kelola Anak Tanggungan</h3>
          <p class="text-xs text-gray-500">
            Hubungkan siswa (saudara kandung / kakak-adik) ke akun <strong>{{ guardian.nama_lengkap }}</strong>
          </p>
        </div>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 text-lg font-bold cursor-pointer">&times;</button>
      </div>

      <div class="p-4 border-b border-gray-100">
        <input
          v-model="studentSearchQuery"
          type="text"
          placeholder="Ketik nama atau NIS anak untuk mencari..."
          class="w-full px-3.5 py-2 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
        />
      </div>

      <div class="overflow-y-auto p-4 space-y-2 flex-1 max-h-96">
        <div
          v-for="s in filteredStudents"
          :key="s.id"
          @click="toggleStudent(s.id)"
          class="p-3 rounded-2xl border transition flex items-center justify-between cursor-pointer"
          :class="selectedIds.includes(s.id) ? 'bg-emerald-50/70 border-emerald-300 shadow-2xs' : 'bg-gray-50/50 border-gray-100 hover:bg-gray-100/60'"
        >
          <div class="flex items-center gap-3">
            <input
              type="checkbox"
              :checked="selectedIds.includes(s.id)"
              @click.stop="toggleStudent(s.id)"
              class="rounded text-emerald-600 focus:ring-emerald-500 cursor-pointer"
            />
            <div>
              <div class="font-bold text-gray-900 text-xs">{{ s.nama_lengkap }}</div>
              <div class="text-[11px] text-gray-500 font-mono">
                NIS: {{ s.nis }} • Kelas: {{ s.school_class?.nama || s.class_id || '-' }}
              </div>
            </div>
          </div>

          <span
            v-if="selectedIds.includes(s.id)"
            class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 text-[10px] font-bold"
          >
            Terhubung
          </span>
        </div>

        <div v-if="filteredStudents.length === 0" class="py-8 text-center text-gray-400 text-xs">
          Tidak ada data siswa yang cocok dengan pencarian.
        </div>
      </div>

      <div class="p-4 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
        <div class="text-xs font-semibold text-gray-600">
          Terpilih: <strong>{{ selectedIds.length }} Siswa</strong>
        </div>
        <div class="flex gap-2">
          <button
            @click="$emit('close')"
            class="px-4 py-2 text-gray-600 hover:bg-gray-200 rounded-xl text-xs font-semibold transition cursor-pointer"
          >
            Batal
          </button>
          <button
            @click="$emit('save', selectedIds)"
            :disabled="saving"
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md transition disabled:opacity-50 cursor-pointer"
          >
            {{ saving ? 'Menyimpan...' : 'Simpan Relasi' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import type { Guardian, LinkedStudent } from './types';

const props = defineProps<{
  guardian: Guardian | null;
  allStudents: LinkedStudent[];
  initialSelectedIds: number[];
  saving: boolean;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'save', ids: number[]): void;
}>();

const studentSearchQuery = ref('');
const selectedIds = ref<number[]>([]);

watch(
  () => props.initialSelectedIds,
  (newIds) => {
    selectedIds.value = [...newIds];
    studentSearchQuery.value = '';
  },
  { immediate: true }
);

const filteredStudents = computed(() => {
  if (!studentSearchQuery.value) return props.allStudents;
  const q = studentSearchQuery.value.toLowerCase();
  return props.allStudents.filter(s => {
    return s.nama_lengkap?.toLowerCase().includes(q) || s.nis?.includes(q);
  });
});

const toggleStudent = (studentId: number) => {
  const idx = selectedIds.value.indexOf(studentId);
  if (idx > -1) {
    selectedIds.value.splice(idx, 1);
  } else {
    selectedIds.value.push(studentId);
  }
};
</script>
