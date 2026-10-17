<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Manajemen Backup & Restore Database</h1>
        <p class="text-xs text-gray-500 mt-1">
          Pencadangan rutin otomatis harian (01:00 WIB) dan manual dengan arsip terkompresi ZIP, checksum SHA-256, serta proteksi keamanan berlapis saat restorasi.
        </p>
      </div>
      <button
        @click="createManualBackup"
        :disabled="creating"
        class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-md shadow-emerald-600/20 transition disabled:opacity-50"
      >
        <span v-if="creating">Memproses Backup...</span>
        <span v-else>Buat Backup Manual Sekarang</span>
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total File Arsip</span>
        <div class="text-2xl font-bold text-gray-900 mt-1">{{ summary?.total_backup ?? 0 }}</div>
        <p class="text-xs text-gray-400 mt-1">Batas retensi otomatis: 30 file</p>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Kapasitas Terpakai</span>
        <div class="text-2xl font-bold text-emerald-700 mt-1">{{ summary?.total_ukuran_formatted ?? '0 B' }}</div>
        <p class="text-xs text-gray-400 mt-1">Penyimpanan lokal: storage/app/backups</p>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Pencadangan Terakhir</span>
        <div class="text-sm font-bold text-gray-900 mt-2">{{ summary?.terakhir_backup ?? 'Belum ada backup' }}</div>
        <p class="text-xs text-gray-400 mt-1">Terjadwal setiap hari pukul 01:00</p>
      </div>
    </div>

    <!-- Alert Success / Error -->
    <div v-if="alertMessage" :class="alertSuccess ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'" class="p-4 rounded-xl border text-xs flex justify-between items-center">
      <span>{{ alertMessage }}</span>
      <button @click="alertMessage = ''" class="font-bold underline ml-4">Tutup</button>
    </div>

    <!-- Backup List Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="text-sm font-bold text-gray-900">Riwayat File Cadangan (ZIP Archive)</h2>
        <button @click="fetchBackups" class="text-xs text-emerald-700 hover:text-emerald-800 font-medium">Segarkan</button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-gray-600">
          <thead class="bg-gray-50 text-[11px] font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
            <tr>
              <th class="py-3 px-4">Nama File</th>
              <th class="py-3 px-4">Tipe</th>
              <th class="py-3 px-4">Ukuran</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4">Waktu Dibuat</th>
              <th class="py-3 px-4">Pembuat</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="b in backups" :key="b.id" class="hover:bg-gray-50/60 transition">
              <td class="py-3 px-4 font-mono font-medium text-gray-900">{{ b.nama_file }}</td>
              <td class="py-3 px-4">
                <span :class="b.tipe === 'otomatis' ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-amber-50 text-amber-700 border-amber-200'" class="px-2 py-0.5 rounded-full border text-[10px] font-bold uppercase">
                  {{ b.tipe }}
                </span>
              </td>
              <td class="py-3 px-4">{{ formatBytes(b.ukuran) }}</td>
              <td class="py-3 px-4">
                <span :class="b.status === 'sukses' ? 'text-emerald-700' : b.status === 'dipulihkan' ? 'text-purple-700 font-bold' : 'text-rose-700'" class="font-semibold">
                  {{ b.status }}
                </span>
              </td>
              <td class="py-3 px-4">{{ formatDate(b.created_at) }}</td>
              <td class="py-3 px-4">{{ b.creator?.nama ?? 'Sistem Cron' }}</td>
              <td class="py-3 px-4 text-right space-x-2">
                <button
                  @click="downloadBackup(b.id, b.nama_file)"
                  class="px-2.5 py-1 bg-gray-100 hover:bg-emerald-50 hover:text-emerald-700 rounded-lg text-[11px] font-medium transition"
                >
                  Unduh
                </button>
                <button
                  @click="openRestoreModal(b)"
                  class="px-2.5 py-1 bg-amber-50 text-amber-800 hover:bg-amber-100 rounded-lg text-[11px] font-medium transition border border-amber-200"
                >
                  Pulihkan
                </button>
                <button
                  @click="deleteBackup(b)"
                  class="px-2.5 py-1 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-lg text-[11px] font-medium transition"
                >
                  Hapus
                </button>
              </td>
            </tr>
            <tr v-if="backups.length === 0">
              <td colspan="7" class="py-8 text-center text-gray-400">Belum ada file backup yang tersimpan.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Konfirmasi Restore Berlapis -->
    <div v-if="showRestoreModal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-rose-100 space-y-4">
        <div class="flex items-center gap-3 text-rose-600">
          <div>
            <h3 class="text-base font-bold text-gray-900">Konfirmasi Pemulihan Database</h3>
            <p class="text-xs text-rose-600 font-semibold">Tindakan ini berisiko tinggi!</p>
          </div>
        </div>

        <p class="text-xs text-gray-600 leading-relaxed">
          Anda akan memulihkan data sekolah dari arsip <strong class="font-mono text-gray-900">{{ selectedBackup?.nama_file }}</strong>.
          Data aktif saat ini akan digantikan dengan data pada saat pencadangan tersebut.
          Sistem akan membuat snapshot darurat otomatis sebelum restorasi dieksekusi.
        </p>

        <div>
          <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
            Ketik Password Administrator Anda:
          </label>
          <input
            v-model="adminPassword"
            type="password"
            placeholder="Masukkan kata sandi admin"
            class="w-full px-3.5 py-2 text-xs rounded-xl border border-gray-300 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none"
          />
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
          <button
            @click="closeRestoreModal"
            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition"
          >
            Batal
          </button>
          <button
            @click="confirmRestore"
            :disabled="restoring || !adminPassword"
            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl shadow-md transition disabled:opacity-50"
          >
            {{ restoring ? 'Memulihkan Data...' : 'Konfirmasi & Pulihkan Database' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../services/api';
import { confirmDeleteWithKeyword, showSuccess, showError } from '../services/swal';

const backups = ref<any[]>([]);
const summary = ref<any>(null);
const loading = ref(false);
const creating = ref(false);
const restoring = ref(false);

const alertMessage = ref('');
const alertSuccess = ref(true);

const showRestoreModal = ref(false);
const selectedBackup = ref<any>(null);
const adminPassword = ref('');

const formatBytes = (bytes: number) => {
  if (!bytes) return '0 B';
  if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
  if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
  if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
  return bytes + ' B';
};

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
};

const fetchBackups = async () => {
  loading.value = true;
  try {
    const res = await api.get('/backups');
    if (res.data.success) {
      backups.value = res.data.data;
      summary.value = res.data.summary;
    }
  } catch (err) {
    console.error('Gagal mengambil daftar backup', err);
  } finally {
    loading.value = false;
  }
};

const createManualBackup = async () => {
  creating.value = true;
  alertMessage.value = '';
  try {
    const res = await api.post('/backups', { catatan: 'Manual backup dari Web UI' });
    if (res.data.success) {
      alertSuccess.value = true;
      alertMessage.value = res.data.message;
      fetchBackups();
    }
  } catch (err: any) {
    alertSuccess.value = false;
    alertMessage.value = err.response?.data?.message || 'Gagal membuat backup.';
  } finally {
    creating.value = false;
  }
};

const downloadBackup = async (id: number, fileName: string) => {
  try {
    const response = await api.get(`/backups/${id}/download`, {
      responseType: 'blob',
    });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', fileName);
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (err) {
    alertSuccess.value = false;
    alertMessage.value = 'Gagal mengunduh file backup.';
  }
};

const openRestoreModal = (backup: any) => {
  selectedBackup.value = backup;
  adminPassword.value = '';
  showRestoreModal.value = true;
};

const closeRestoreModal = () => {
  showRestoreModal.value = false;
  selectedBackup.value = null;
  adminPassword.value = '';
};

const confirmRestore = async () => {
  if (!selectedBackup.value || !adminPassword.value) return;
  restoring.value = true;
  try {
    const res = await api.post(`/backups/${selectedBackup.value.id}/restore`, {
      password: adminPassword.value,
    });
    if (res.data.success) {
      alertSuccess.value = true;
      alertMessage.value = res.data.message;
      closeRestoreModal();
      fetchBackups();
    }
  } catch (err: any) {
    alertSuccess.value = false;
    alertMessage.value = err.response?.data?.message || 'Gagal memulihkan database.';
  } finally {
    restoring.value = false;
  }
};

const deleteBackup = async (backup: any) => {
  const confirmed = await confirmDeleteWithKeyword(`file backup "${backup.nama_file}"`);
  if (!confirmed) return;

  try {
    const res = await api.delete(`/backups/${backup.id}`);
    if (res.data.success) {
      showSuccess('Berhasil!', res.data.message);
      fetchBackups();
    }
  } catch (err: any) {
    showError('Gagal!', err.response?.data?.message || 'Gagal menghapus backup.');
  }
};

onMounted(() => {
  fetchBackups();
});
</script>
