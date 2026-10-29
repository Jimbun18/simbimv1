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
    <div v-if="validationResult" class="bg-white p-5 sm:p-6 rounded-2xl border border-emerald-100 shadow-md space-y-5">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100">
        <div>
          <h2 class="text-sm font-bold text-gray-900">Hasil Validasi File: {{ validationResult.file_name }}</h2>
          <p class="text-xs text-gray-500">Periksa ringkasan baris valid dan baris bermasalah sebelum menyimpan.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button
            v-if="validationResult.total_error > 0"
            @click="exportErrors(validationResult.batch_id)"
            class="px-3 py-1.5 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-xl text-xs font-bold border border-rose-200 transition flex items-center gap-1.5"
          >
            <span>Unduh Laporan Error ({{ validationResult.total_error }})</span>
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
          <div class="text-[11px] text-gray-500 font-semibold uppercase">Total Baris Terisi</div>
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

      <!-- Detail Baris Error (Jika Ada) -->
      <div v-if="validationResult.errors_preview && validationResult.errors_preview.length > 0" class="p-4 bg-rose-50/70 border border-rose-200 rounded-xl space-y-2">
        <div class="flex items-center justify-between">
          <h3 class="text-xs font-bold text-rose-800 flex items-center gap-1.5">
            Detail Baris Bermasalah ({{ validationResult.total_error }} baris tidak memenuhi syarat)
          </h3>
          <span class="text-[11px] text-rose-600 font-medium">Baris ini akan dilewati dan tidak diimpor</span>
        </div>
        <div class="max-h-48 overflow-y-auto space-y-1.5 pr-1">
          <div
            v-for="(err, idx) in validationResult.errors_preview"
            :key="idx"
            class="text-xs bg-white/90 p-2.5 rounded-lg border border-rose-100 flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-rose-900"
          >
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 bg-rose-100 text-rose-800 font-bold rounded text-[10px]">Baris {{ err.nomor_baris }}</span>
              <span class="font-semibold text-gray-700">Kolom: <span class="font-mono text-rose-700">{{ err.kolom || '-' }}</span></span>
            </div>
            <span class="text-xs text-rose-700 font-medium">{{ err.pesan }}</span>
          </div>
        </div>
      </div>

      <!-- Pratinjau Data Valid (Preview Samples) -->
      <div v-if="validationResult.preview_samples && validationResult.preview_samples.length > 0" class="space-y-2 pt-2">
        <h3 class="text-xs font-bold text-gray-800 flex items-center gap-1.5">
          Pratinjau Data Valid yang Siap Diimpor ({{ validationResult.preview_samples.length }} baris)
        </h3>
        <div class="overflow-x-auto border border-gray-100 rounded-xl">
          <table class="w-full text-left text-xs text-gray-600">
            <thead class="bg-gray-50 text-[10px] font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
              <tr>
                <th class="py-2.5 px-3">No. Baris Excel</th>
                <th class="py-2.5 px-3">Nama Lengkap</th>
                <th class="py-2.5 px-3">NIK / Identitas</th>
                <th class="py-2.5 px-3">Jenis Kelamin</th>
                <th class="py-2.5 px-3">Jabatan / Kelas</th>
                <th class="py-2.5 px-3">Kontak / HP</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(sample, sIdx) in validationResult.preview_samples" :key="sIdx" class="hover:bg-emerald-50/30 transition">
                <td class="py-2.5 px-3 font-mono font-bold text-emerald-700">Baris {{ sample.row }}</td>
                <td class="py-2.5 px-3 font-semibold text-gray-900">{{ sample.data.nama_lengkap || '-' }}</td>
                <td class="py-2.5 px-3 font-mono text-gray-600">{{ sample.data.nik || sample.data.nis || '-' }}</td>
                <td class="py-2.5 px-3">{{ sample.data.jenis_kelamin || '-' }}</td>
                <td class="py-2.5 px-3 text-gray-600">{{ sample.data.jabatan || sample.data.kode_kelas || '-' }}</td>
                <td class="py-2.5 px-3 font-mono text-gray-500">{{ sample.data.no_hp || sample.data.no_hp_wali || '-' }}</td>
              </tr>
            </tbody>
          </table>
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
              <td class="py-3 px-4 font-bold">{{ b.total ?? b.total_baris ?? 0 }}</td>
              <td class="py-3 px-4 text-emerald-700 font-bold">{{ b.berhasil ?? b.baris_berhasil ?? 0 }}</td>
              <td class="py-3 px-4 text-rose-700 font-bold">{{ b.gagal ?? b.baris_gagal ?? 0 }}</td>
              <td class="py-3 px-4">
                <span :class="b.status === 'committed' || b.status === 'selesai' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : b.status === 'rolled_back' || b.status === 'dirollback' ? 'bg-gray-100 text-gray-600 border-gray-200' : 'bg-amber-50 text-amber-700 border-amber-200'" class="px-2 py-0.5 rounded-full border text-[10px] font-bold uppercase">
                  {{ b.status === 'committed' ? 'SELESAI' : b.status === 'rolled_back' ? 'DIROLLBACK' : b.status }}
                </span>
              </td>
              <td class="py-3 px-4 text-right">
                <button
                  v-if="b.status === 'committed' || b.status === 'selesai'"
                  @click="rollbackBatch(b.id)"
                  class="px-2.5 py-1 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-lg text-[11px] font-semibold border border-rose-200 transition"
                  title="Batalkan seluruh data yang diimpor pada batch ini"
                >
                  Rollback 1-Klik
                </button>
                <span v-else class="text-gray-400 text-[10px]">{{ b.status === 'rolled_back' ? 'Telah Dirollback' : '-' }}</span>
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
      const d = res.data.data;
      validationResult.value = {
        batch_id: d.batch_id,
        file_name: d.file_name || selectedFile.value?.name,
        total_rows: d.total_rows ?? 0,
        total_valid: d.total_valid ?? d.valid_count ?? 0,
        total_error: d.total_error ?? d.error_count ?? 0,
        duplicate_count: d.duplicate_count ?? 0,
        preview_samples: d.preview_samples ?? [],
        errors_preview: d.errors_preview ?? [],
      };
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
    const res = await api.post(`/imports/${batchId}/commit`, {
      duplicate_strategy: duplicateAction.value,
    });
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
