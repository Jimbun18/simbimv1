<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../services/api';
import { useAuthStore } from '../../stores/auth';
import { confirmDeleteWithKeyword, showSuccess, showError } from '../../services/swal';
import { formatDateIndo } from '../../utils/date';
import InfoTooltip from '../common/InfoTooltip.vue';

const emit = defineEmits<{
  (e: 'create'): void;
  (e: 'edit', student: any): void;
  (e: 'detail', student: any): void;
}>();

const auth = useAuthStore();
const route = useRoute();

const students = ref<any[]>([]);
const classes = ref<any[]>([]);
const search = ref('');
const filterClass = ref('');
const filterStatus = ref('aktif');
const loading = ref(false);
const exporting = ref(false);

// Pagination
const currentPage = ref(1);
const perPage = ref(20);
const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
});

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
const debouncedFetch = () => {
  if (debounceTimer) clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    fetchStudents(1);
  }, 300);
};

const getPhotoUrl = (url?: string, path?: string) => {
  if (url) return url;
  if (path) {
    return path.startsWith('http') ? path : `/storage/${path}`;
  }
  return '';
};

const getInitials = (name?: string) => {
  if (!name) return '?';
  const parts = name.trim().split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
};

const getStudentParentName = (student: any) => {
  if (!student.guardians || student.guardians.length === 0) return '';
  const primary = student.guardians.find((g: any) => g.pivot?.is_kontak_utama) || student.guardians[0];
  return primary ? `${primary.nama_lengkap} (${primary.hubungan || primary.pivot?.hubungan || 'Wali'})` : '';
};

const getStudentParentPhone = (student: any) => {
  if (!student.guardians || student.guardians.length === 0) return '';
  const primary = student.guardians.find((g: any) => g.pivot?.is_kontak_utama) || student.guardians[0];
  return primary?.no_hp || '';
};

const fetchStudents = async (page = 1) => {
  loading.value = true;
  currentPage.value = page;
  try {
    const params: any = {
      status: filterStatus.value,
      page: currentPage.value,
      per_page: perPage.value === -1 ? 9999 : perPage.value,
    };
    if (search.value) params.search = search.value;
    if (filterClass.value) params.class_id = filterClass.value;

    const res = await api.get('/students', { params });
    if (res.data.success) {
      students.value = res.data.data?.data || res.data.data || [];
      if (res.data.meta) {
        meta.value = res.data.meta;
      }
    }
  } catch (err) {
    console.error('Gagal mengambil data siswa', err);
  } finally {
    loading.value = false;
  }
};

const fetchClasses = async () => {
  try {
    const res = await api.get('/classes');
    if (res.data.success) {
      classes.value = res.data.data || [];
    }
  } catch (err) {
    console.error('Gagal mengambil data kelas', err);
  }
};

const exportToExcel = async () => {
  exporting.value = true;
  try {
    const params: any = { status: filterStatus.value };
    if (search.value) params.search = search.value;
    if (filterClass.value) params.class_id = filterClass.value;

    const res = await api.get('/students/export', {
      params,
      responseType: 'blob',
    });

    const blob = new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `Master_Data_Siswa_Dapodik_${new Date().toISOString().slice(0, 10)}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    showSuccess('Berhasil!', 'File Excel data siswa berhasil diunduh.');
  } catch (err: any) {
    showError('Gagal Export Excel!', err, 'Terjadi kendala saat mengunduh berkas Excel.');
  } finally {
    exporting.value = false;
  }
};

const handleDeleteStudent = async (student: any) => {
  const confirmed = await confirmDeleteWithKeyword(`siswa "${student.nama_lengkap}" (NIS: ${student.nis})`);
  if (!confirmed) return;

  try {
    const res = await api.delete(`/students/${student.id}`);
    if (res.data.success) {
      showSuccess('Berhasil Dihapus!', res.data.message);
      fetchStudents(currentPage.value);
    }
  } catch (err: any) {
    showError('Gagal Menghapus!', err, 'Data siswa tidak dapat dihapus.');
  }
};

onMounted(() => {
  if (route.query.search) {
    search.value = String(route.query.search);
  }
  fetchStudents(1);
  fetchClasses();
});

defineExpose({
  fetchStudents,
  exportToExcel,
  setSearch: (val: string) => {
    search.value = val;
    fetchStudents(1);
  }
});
</script>

<template>
  <div class="space-y-5 sm:space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 sm:p-5 rounded-2xl border border-slate-100 shadow-xs">
      <div class="flex items-center gap-2.5">
        <h1 class="text-lg sm:text-xl font-bold text-slate-900 leading-tight">Data Siswa</h1>
        <span v-if="meta.total > 0" class="px-2.5 py-0.5 text-xs font-semibold bg-emerald-50 text-emerald-700 rounded-full border border-emerald-200">
          {{ meta.total }} Siswa
        </span>
        <InfoTooltip text="Master data siswa aktif, identitas Dapodik, rombel kelas, dan kontak orang tua/wali murid." />
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          @click="exportToExcel"
          :disabled="exporting"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 transition disabled:opacity-50 cursor-pointer"
          title="Export Data Siswa ke Excel"
        >
          <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <span v-if="exporting">Mengekspor...</span>
          <span v-else>Export</span>
        </button>

        <router-link
          v-if="auth.isAdmin"
          to="/excel-imports"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 transition"
          title="Import Dapodik / Format Excel"
        >
          <svg class="w-4 h-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          <span>Import</span>
        </router-link>

        <button
          v-if="auth.isAdmin"
          @click="emit('create')"
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition active:scale-95 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Tambah Siswa</span>
        </button>
      </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-3.5 rounded-2xl border border-slate-100 shadow-xs flex flex-col md:flex-row gap-2.5 items-stretch md:items-center justify-between">
      <div class="flex-1 relative">
        <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="search"
          @input="debouncedFetch"
          type="text"
          placeholder="Cari NIS, NISN, atau Nama Siswa..."
          class="w-full pl-9 pr-4 py-2 bg-slate-50 text-xs rounded-xl border border-slate-200 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none transition"
        />
      </div>

      <div class="flex flex-wrap sm:nowrap gap-2">
        <select
          v-model="filterClass"
          @change="fetchStudents(1)"
          class="w-full sm:w-auto px-3 py-2 bg-slate-50 text-xs rounded-xl border border-slate-200 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-slate-700"
        >
          <option value="">Semua Rombel</option>
          <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nama_kelas || c.nama }}</option>
        </select>

        <select
          v-model="filterStatus"
          @change="fetchStudents(1)"
          class="w-full sm:w-auto px-3 py-2 bg-slate-50 text-xs rounded-xl border border-slate-200 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-slate-700"
        >
          <option value="aktif">Status: Aktif</option>
          <option value="lulus">Status: Lulus</option>
          <option value="pindah">Status: Pindah</option>
          <option value="mutasi_keluar">Status: Mutasi Keluar</option>
          <option value="drop_out">Status: Drop Out</option>
          <option value="">Semua Status</option>
        </select>
      </div>
    </div>

    <!-- Table Siswa (Minimalis & Rapi) -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600 min-w-[760px]">
          <thead class="bg-slate-50/80 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
            <tr>
              <th class="py-3 px-3.5 w-10 text-center">No</th>
              <th class="py-3 px-3 w-12 text-center">Foto</th>
              <th class="py-3 px-4">
                <div class="inline-flex items-center gap-1.5">
                  <span>Siswa</span>
                  <InfoTooltip size="xs" text="Nama lengkap, NIS, dan NIK/NISN siswa Dapodik." />
                </div>
              </th>
              <th class="py-3 px-3 text-center w-12">
                <div class="inline-flex items-center gap-1">
                  <span>L/P</span>
                  <InfoTooltip size="xs" text="Jenis Kelamin (L: Laki-laki, P: Perempuan)" />
                </div>
              </th>
              <th class="py-3 px-4">Rombel Kelas</th>
              <th class="py-3 px-4">TTL & Usia</th>
              <th class="py-3 px-4">Wali / Kontak</th>
              <th class="py-3 px-3 text-center w-24">Status</th>
              <th class="py-3 px-4 text-right w-28">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="loading">
              <td colspan="9" class="text-center py-8 text-slate-400">
                <div class="inline-block animate-spin w-5 h-5 border-2 border-emerald-600 border-t-transparent rounded-full mb-1"></div>
                <div>Memuat data kesiswaan...</div>
              </td>
            </tr>
            <tr v-else-if="students.length === 0">
              <td colspan="9" class="text-center py-8 text-slate-400">Tidak ada data siswa yang cocok dengan filter.</td>
            </tr>
            <tr v-for="(s, idx) in students" :key="s.id" class="hover:bg-slate-50/70 transition">
              <td class="py-2.5 px-3.5 text-slate-400 font-mono text-center text-[11px]">
                {{ meta.total > 0 ? (meta.current_page - 1) * meta.per_page + idx + 1 : idx + 1 }}
              </td>
              <td class="py-2.5 px-3 text-center">
                <div class="w-8 h-8 rounded-full overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center mx-auto shadow-2xs shrink-0">
                  <img
                    v-if="s.foto_url || s.foto_path"
                    :src="getPhotoUrl(s.foto_url, s.foto_path)"
                    :alt="s.nama_lengkap"
                    class="w-full h-full object-cover"
                    @error="(e: any) => e.target.style.display = 'none'"
                  />
                  <span v-else class="text-[10px] font-bold text-slate-500 uppercase">
                    {{ getInitials(s.nama_lengkap) }}
                  </span>
                </div>
              </td>
              <td class="py-2.5 px-4">
                <div class="font-semibold text-slate-900 leading-tight">{{ s.nama_lengkap }}</div>
                <div class="flex items-center gap-2 mt-0.5 text-[10px] text-slate-400 font-mono">
                  <span>NIS: {{ s.nis || '-' }}</span>
                  <span v-if="s.nisn" class="text-slate-300">•</span>
                  <span v-if="s.nisn">NISN: {{ s.nisn }}</span>
                </div>
              </td>
              <td class="py-2.5 px-3 text-center">
                <span 
                  :class="s.jenis_kelamin === 'L' ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-rose-50 text-rose-700 border-rose-200'" 
                  class="px-2 py-0.5 rounded-full border text-[10px] font-bold"
                  :title="s.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'"
                >
                  {{ s.jenis_kelamin }}
                </span>
              </td>
              <td class="py-2.5 px-4 whitespace-nowrap">
                <span class="px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-100 font-semibold text-[11px]">
                  {{ s.current_class?.nama || s.school_class?.nama || s.class?.nama || s.class?.nama_kelas || '-' }}
                </span>
              </td>
              <td class="py-2.5 px-4 whitespace-nowrap">
                <div class="font-medium text-slate-800 text-[11px]">{{ s.tempat_lahir || '-' }}</div>
                <div class="text-[10px] text-slate-400">{{ formatDateIndo(s.tgl_lahir) }}</div>
              </td>
              <td class="py-2.5 px-4 whitespace-nowrap">
                <div v-if="getStudentParentName(s)" class="font-medium text-slate-800 text-[11px]">
                  {{ getStudentParentName(s) }}
                </div>
                <div v-else class="text-slate-400 italic text-[10px]">-</div>
                <div v-if="s.no_hp_siswa || getStudentParentPhone(s)" class="text-[10px] text-emerald-600 font-mono">
                  {{ s.no_hp_siswa || getStudentParentPhone(s) }}
                </div>
              </td>
              <td class="py-2.5 px-3 text-center whitespace-nowrap">
                <span 
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold capitalize"
                  :class="s.status === 'aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="s.status === 'aktif' ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                  {{ s.status }}
                </span>
              </td>
              <!-- Aksi Icon Buttons Minimalis -->
              <td class="py-2.5 px-4 text-right whitespace-nowrap">
                <div class="inline-flex items-center gap-1 justify-end">
                  <button
                    @click="emit('detail', s)"
                    class="p-1.5 text-slate-500 hover:text-sky-700 hover:bg-sky-50 rounded-lg transition cursor-pointer"
                    title="Lihat Detail Profil"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                  <button
                    v-if="auth.isAdmin"
                    @click="emit('edit', s)"
                    class="p-1.5 text-slate-500 hover:text-amber-700 hover:bg-amber-50 rounded-lg transition cursor-pointer"
                    title="Edit Data Siswa"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button
                    v-if="auth.isAdmin"
                    @click="handleDeleteStudent(s)"
                    class="p-1.5 text-slate-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition cursor-pointer"
                    title="Hapus Siswa"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div class="px-5 py-3.5 bg-gray-50/80 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <div class="text-gray-500">
          Menampilkan <strong class="text-gray-800">{{ meta.total > 0 ? (meta.current_page - 1) * meta.per_page + 1 : 0 }} - {{ Math.min(meta.current_page * meta.per_page, meta.total) }}</strong> dari <strong class="text-gray-800">{{ meta.total }}</strong> siswa
        </div>

        <div class="flex items-center gap-3">
          <div class="flex items-center gap-1.5 text-gray-500">
            <span>Baris per halaman:</span>
            <select
              v-model="perPage"
              @change="fetchStudents(1)"
              class="px-2 py-1 bg-white border border-gray-200 rounded-lg text-xs outline-none focus:ring-1 focus:ring-emerald-500"
            >
              <option :value="10">10</option>
              <option :value="20">20</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
              <option :value="-1">Semua</option>
            </select>
          </div>

          <div class="flex items-center gap-1">
            <button
              :disabled="meta.current_page <= 1"
              @click="fetchStudents(meta.current_page - 1)"
              class="px-2.5 py-1 bg-white border border-gray-200 hover:bg-gray-100 disabled:opacity-40 disabled:hover:bg-white rounded-lg transition font-medium cursor-pointer"
            >
              Prev
            </button>
            <span class="px-2 font-mono font-bold text-gray-700">
              {{ meta.current_page }} / {{ meta.last_page || 1 }}
            </span>
            <button
              :disabled="meta.current_page >= meta.last_page"
              @click="fetchStudents(meta.current_page + 1)"
              class="px-2.5 py-1 bg-white border border-gray-200 hover:bg-gray-100 disabled:opacity-40 disabled:hover:bg-white rounded-lg transition font-medium cursor-pointer"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
