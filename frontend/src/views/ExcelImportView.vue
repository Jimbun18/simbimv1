<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Engine Migrasi Data & Import Excel</h1>
        <p class="text-xs text-gray-500 mt-0.5">
          Migrasikan data siswa, guru, wali, dan kelas dari format Excel lama sekolah dengan validasi otomatis & tombol rollback 1-klik.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <select v-model="selectedTemplate" class="px-3 py-2 bg-gray-50 text-xs rounded-xl border border-gray-200 outline-none text-gray-700">
          <option value="siswa">Template Siswa</option>
          <option value="guru">Template Guru</option>
          <option value="kelas">Template Kelas</option>
          <option value="wali">Template Wali Murid</option>
          <option value="mata_pelajaran">Template Mata Pelajaran</option>
        </select>
        <button
          @click="downloadTemplate"
          class="px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-xl border border-emerald-200 transition"
        >
          Unduh Template
        </button>
      </div>
    </div>

    <!-- Upload & Validate Section -->
    <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
      <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
        Unggah File Excel (.xlsx / .csv)
      </h2>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Pilih Jenis Data:</label>
          <select v-model="jenisData" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 outline-none">
            <option value="siswa">Data Siswa</option>
            <option value="guru">Data Guru</option>
            <option value="kelas">Data Kelas</option>
            <option value="wali">Data Wali Murid</option>
            <option value="mata_pelajaran">Data Mata Pelajaran</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Penanganan Duplikasi:</label>
          <select v-model="duplicateAction" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 outline-none">
            <option value="skip">Lewati yang Duplikat (Skip)</option>
            <option value="update">Timpa Data Lama (Update)</option>
            <option value="reject">Batalkan Batch Jika Ada Duplikat</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Pilih Berkas:</label>
          <input
            type="file"
            ref="fileInput"
            @change="onFileSelected"
            accept=".xlsx, .xls, .csv"
            class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer"
          />
        </div>
      </div>

      <div class="flex justify-end pt-2">
        <button
          @click="uploadAndValidate"
          :disabled="!selectedFile || validating"
          class="w-full sm:w-auto px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md transition disabled:opacity-50 flex items-center justify-center gap-2"
        >
          <span>{{ validating ? 'Memvalidasi File...' : 'Validasi & Pratinjau File' }}</span>
        </button>
      </div>
    </div>

    <!-- Preview & Commit Card -->
    <div v-if="validationResult" class="bg-white p-5 sm:p-6 rounded-2xl border border-emerald-100 shadow-md space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100">
        <div>
          <h2 class="text-sm font-bold text-gray-900">Hasil Validasi File: {{ validationResult.file_name }}</h2>
          <p class="text-xs text-gray-500">Periksa ringkasan baris valid dan baris bermasalah sebelum menyimpan.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button
            v-if="validationResult.total_error > 0"
            @click="exportErrors(validationResult.batch_id)"
            class="px-3 py-1.5 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-xl text-xs font-bold border border-rose-200 transition"
          >
            Unduh Laporan Error ({{ validationResult.total_error }})
          </button>
          <button
            @click="commitBatch(validationResult.batch_id)"
            :disabled="committing || validationResult.total_valid === 0"
            class="px-5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md transition disabled:opacity-50"
          >
            {{ committing ? 'Menyimpan Data...' : `Simpan (${validationResult.total_valid} Baris Valid)` }}
          </button>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-center">
        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
          <div class="text-lg font-bold text-gray-900">{{ validationResult.total_rows }}</div>
          <div class="text-[11px] text-gray-500 font-semibold uppercase">Total Baris File</div>
        </div>
        <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-100">
          <div class="text-lg font-bold text-emerald-800">{{ validationResult.total_valid }}</div>
          <div class="text-[11px] text-emerald-700 font-semibold uppercase">Baris Valid (Siap Impor)</div>
        </div>
        <div class="p-3 bg-rose-50 rounded-xl border border-rose-100">
          <div class="text-lg font-bold text-rose-800">{{ validationResult.total_error }}</div>
          <div class="text-[11px] text-rose-700 font-semibold uppercase">Baris Error / Tidak Cocok</div>
        </div>
      </div>
    </div>

    <!-- Riwayat Batch Import & Rollback -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="text-sm font-bold text-gray-900">Riwayat Batch Import (Dukungan Rollback 1-Klik)</h2>
        <button @click="fetchBatches" class="text-xs text-emerald-700 font-semibold hover:underline">Segarkan</button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-gray-600 min-w-[750px]">
          <thead class="bg-gray-50 text-[11px] font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
            <tr>
              <th class="py-3 px-4">Waktu</th>
              <th class="py-3 px-4">Jenis Data</th>
              <th class="py-3 px-4">Nama File</th>
              <th class="py-3 px-4">Total</th>
              <th class="py-3 px-4">Berhasil</th>
              <th class="py-3 px-4">Gagal</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="b in batches" :key="b.id" class="hover:bg-gray-50/70 transition">
              <td class="py-3 px-4 text-gray-500">{{ b.created_at }}</td>
              <td class="py-3 px-4 font-bold text-gray-900 uppercase">{{ b.jenis_data }}</td>
              <td class="py-3 px-4 font-mono">{{ b.nama_file }}</td>
              <td class="py-3 px-4 font-bold">{{ b.total_baris }}</td>
              <td class="py-3 px-4 text-emerald-700 font-bold">{{ b.baris_berhasil }}</td>
              <td class="py-3 px-4 text-rose-700 font-bold">{{ b.baris_gagal }}</td>
              <td class="py-3 px-4">
                <span :class="b.status === 'selesai' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : b.status === 'dirollback' ? 'bg-gray-100 text-gray-600 border-gray-200' : 'bg-amber-50 text-amber-700 border-amber-200'" class="px-2 py-0.5 rounded-full border text-[10px] font-bold uppercase">
                  {{ b.status }}
                </span>
              </td>
              <td class="py-3 px-4 text-right">
                <button
                  v-if="b.status === 'selesai'"
                  @click="rollbackBatch(b.id)"
                  class="px-2.5 py-1 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-lg text-[11px] font-semibold border border-rose-200 transition"
                  title="Batalkan seluruh data yang diimpor pada batch ini"
                >
                  Rollback 1-Klik
                </button>
                <span v-else class="text-gray-400 text-[10px]">Telah Dirollback</span>
              </td>
            </tr>
            <tr v-if="batches.length === 0">
              <td colspan="8" class="py-8 text-center text-gray-400">Belum ada riwayat batch import.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../services/api';
import { showSuccess, showError, confirmDeleteWithKeyword } from '../services/swal';

const selectedTemplate = ref('siswa');
const jenisData = ref('siswa');
const duplicateAction = ref('skip');
const selectedFile = ref<File | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const validating = ref(false);
const committing = ref(false);
const validationResult = ref<any>(null);
const batches = ref<any[]>([]);

const onFileSelected = (e: any) => {
  const files = e.target.files;
  if (files && files.length > 0) {
    selectedFile.value = files[0];
  }
};

const downloadTemplate = async () => {
  try {
    const res = await api.get(`/imports/templates/${selectedTemplate.value}`, { responseType: 'blob' });
    const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `template_import_${selectedTemplate.value}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => window.URL.revokeObjectURL(url), 1000);
    showSuccess('Berhasil!', `Template Excel ${selectedTemplate.value} berhasil diunduh.`);
  } catch (err: any) {
    console.error('Gagal mengunduh template:', err);
    showError('Gagal!', 'Gagal mengunduh template Excel. Silakan periksa koneksi atau coba lagi.');
  }
};

const uploadAndValidate = async () => {
  if (!selectedFile.value) return;
  validating.value = true;
  validationResult.value = null;

  const formData = new FormData();
  formData.append('file', selectedFile.value);
  formData.append('duplicate_action', duplicateAction.value);

  try {
    const res = await api.post(`/imports/${jenisData.value}/validate`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    if (res.data.success) {
      validationResult.value = res.data.data;
    }
  } catch (err: any) {
    showError('Validasi Gagal!', err.response?.data?.message || 'Gagal memvalidasi file.');
  } finally {
    validating.value = false;
  }
};

const commitBatch = async (batchId: number) => {
  committing.value = true;
  try {
    const res = await api.post(`/imports/${batchId}/commit`);
    if (res.data.success) {
      showSuccess('Berhasil!', res.data.message);
      validationResult.value = null;
      selectedFile.value = null;
      if (fileInput.value) fileInput.value.value = '';
      fetchBatches();
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err.response?.data?.message || 'Gagal menyimpan hasil impor.');
  } finally {
    committing.value = false;
  }
};

const exportErrors = async (batchId: number) => {
  try {
    const res = await api.get(`/imports/${batchId}/errors/export`, { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([res.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `error_report_batch_${batchId}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (err) {
    showError('Gagal!', 'Gagal mengunduh laporan error.');
  }
};

const fetchBatches = async () => {
  try {
    const res = await api.get('/imports/batches');
    if (res.data.success) {
      batches.value = res.data.data;
    }
  } catch (err) {
    console.error('Gagal mengambil daftar batch import', err);
  }
};

const rollbackBatch = async (batchId: number) => {
  const confirmed = await confirmDeleteWithKeyword(`batch impor ID #${batchId}`);
  if (!confirmed) return;

  try {
    const res = await api.post(`/imports/${batchId}/rollback`);
    if (res.data.success) {
      showSuccess('Berhasil Rollback!', res.data.message);
      fetchBatches();
    }
  } catch (err: any) {
    showError('Gagal!', err.response?.data?.message || 'Gagal melakukan rollback.');
  }
};

onMounted(() => {
  fetchBatches();
});
</script>
