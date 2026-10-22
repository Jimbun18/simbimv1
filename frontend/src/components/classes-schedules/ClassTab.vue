<template>
  <div class="space-y-4">
    <!-- Loading State -->
    <div v-if="loading" class="py-12 text-center text-gray-400 bg-white rounded-2xl border border-gray-100">
      <div class="inline-block animate-spin w-6 h-6 border-2 border-purple-600 border-t-transparent rounded-full mb-2"></div>
      <p class="text-xs font-medium text-gray-600">Memuat data rombel kelas...</p>
    </div>

    <!-- Cards Grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="c in classes"
        :key="c.id"
        class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm space-y-3 hover:shadow-md transition flex flex-col justify-between"
      >
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <span class="px-2.5 py-1 bg-purple-50 text-purple-700 border border-purple-200 rounded-lg text-xs font-bold font-mono">
              {{ c.kode }}
            </span>
            <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-[10px] font-bold uppercase">
              {{ c.status }}
            </span>
          </div>

          <div>
            <h3 class="text-base font-bold text-gray-900">{{ c.nama }}</h3>
            <p class="text-xs text-gray-500">Tingkat {{ c.tingkat }} (Paralel {{ c.paralel ?? 'A' }})</p>
          </div>

          <div class="pt-3 border-t border-gray-50 space-y-1.5 text-xs text-gray-600">
            <div class="flex items-center justify-between">
              <span class="text-gray-400">Wali Kelas:</span>
              <span class="font-bold text-gray-800">{{ c.homeroom_teacher?.nama_lengkap || c.wali_kelas?.nama_lengkap || 'Belum Ditugaskan' }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-400">Ruangan:</span>
              <span class="font-medium text-gray-700">{{ c.room?.nama ?? '-' }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-400">Kapasitas:</span>
              <span class="font-medium text-gray-700">{{ c.kapasitas ?? 28 }} Siswa</span>
            </div>
          </div>
        </div>

        <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
          <button
            @click="$emit('edit', c)"
            class="px-3 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg text-xs font-semibold transition border border-amber-200 cursor-pointer"
          >
            Edit
          </button>
          <button
            @click="handleDeleteClass(c)"
            class="px-3 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-semibold transition border border-rose-200 cursor-pointer"
          >
            Hapus
          </button>
        </div>
      </div>

      <div v-if="classes.length === 0" class="col-span-full py-12 text-center text-gray-400 bg-white rounded-2xl border border-gray-100">
        Belum ada data rombel kelas. Klik "+ Tambah Rombel Kelas" untuk memulai.
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../services/api';
import { confirmDeleteWithKeyword, showSuccess, showError } from '../../services/swal';

const emit = defineEmits<{
  (e: 'create'): void;
  (e: 'edit', classItem: any): void;
  (e: 'updated', classes: any[]): void;
}>();

const classes = ref<any[]>([]);
const loading = ref(false);
const exporting = ref(false);

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
