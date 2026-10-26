<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../services/api';
import { useAuthStore } from '../../stores/auth';
import { confirmDeleteWithKeyword, showSuccess, showError } from '../../services/swal';
import { formatDateIndo } from '../../utils/date';

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
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm">
      <div>
        <h1 class="text-lg sm:text-xl font-bold text-gray-900 leading-tight">Manajemen Data Siswa</h1>
        <p class="text-xs text-gray-500 mt-0.5">Daftar seluruh siswa aktif, identitas lengkap Dapodik, rombel kelas, dan kontak orang tua/wali murid.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          @click="exportToExcel"
          :disabled="exporting"
          class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-xl border border-emerald-200 transition disabled:opacity-50 cursor-pointer"
          title="Export Data Siswa ke Excel"
        >
          <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <span v-if="exporting">Mengekspor...</span>
          <span v-else>Export Excel</span>
        </button>

        <router-link
          v-if="auth.isAdmin"
          to="/excel-imports"
          class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-xl border border-emerald-200 transition"
        >
          Import Dapodik / Excel
        </router-link>

        <button
          v-if="auth.isAdmin"
          @click="emit('create')"
          class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/20 transition active:scale-95 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Tambah Siswa Baru</span>
        </button>
      </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
      <div class="flex-1 relative">
        <input
          v-model="search"
          @input="debouncedFetch"
          type="text"
          placeholder="Cari NIS, NISN, atau Nama Siswa..."
          class="w-full px-4 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none transition"
        />
      </div>

      <div class="flex flex-wrap sm:nowrap gap-2">
        <select
          v-model="filterClass"
          @change="fetchStudents(1)"
          class="w-full sm:w-auto px-3 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-gray-700"
        >
          <option value="">Semua Rombel</option>
          <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nama_kelas || c.nama }}</option>
        </select>

        <select
          v-model="filterStatus"
          @change="fetchStudents(1)"
          class="w-full sm:w-auto px-3 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-gray-700"
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

    <!-- Table Siswa -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-gray-600 min-w-[850px]">
          <thead class="bg-gray-50 text-[11px] font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
            <tr>
              <th class="py-3.5 px-4 w-12 text-center">No</th>
              <th class="py-3.5 px-4 w-14 text-center">Foto</th>
              <th class="py-3.5 px-4">NIS / NISN</th>
              <th class="py-3.5 px-4">Nama Lengkap & NIK</th>
              <th class="py-3.5 px-4 text-center">L/P</th>
              <th class="py-3.5 px-4">Rombel Kelas</th>
              <th class="py-3.5 px-4">TTL & Usia</th>
              <th class="py-3.5 px-4">Orang Tua / Wali</th>
              <th class="py-3.5 px-4 text-center">Status</th>
              <th class="py-3.5 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="loading">
              <td colspan="10" class="text-center py-8 text-gray-400">
                <div class="inline-block animate-spin w-5 h-5 border-2 border-emerald-600 border-t-transparent rounded-full mb-1"></div>
                <div>Memuat data kesiswaan...</div>
              </td>
            </tr>
            <tr v-else-if="students.length === 0">
              <td colspan="10" class="text-center py-8 text-gray-400">Tidak ada data siswa yang cocok dengan filter.</td>
            </tr>
            <tr v-for="(s, idx) in students" :key="s.id" class="hover:bg-gray-50/70 transition">
              <td class="py-3 px-4 text-gray-400 font-mono text-center">
                {{ meta.total > 0 ? (meta.current_page - 1) * meta.per_page + idx + 1 : idx + 1 }}
              </td>
              <td class="py-2.5 px-4 text-center">
                <div class="w-9 h-9 rounded-full overflow-hidden bg-gray-100 border border-gray-200 flex items-center justify-center mx-auto shadow-xs shrink-0">
                  <img
                    v-if="s.foto_url || s.foto_path"
                    :src="getPhotoUrl(s.foto_url, s.foto_path)"
                    :alt="s.nama_lengkap"
                    class="w-full h-full object-cover"
                    @error="(e: any) => e.target.style.display = 'none'"
                  />
                  <span v-else class="text-[11px] font-bold text-gray-500 uppercase">
                    {{ getInitials(s.nama_lengkap) }}
                  </span>
                </div>
              </td>
              <td class="py-3 px-4 font-mono font-medium text-gray-900 whitespace-nowrap">
                <div>{{ s.nis }}</div>
                <div class="text-[10px] text-gray-400">NISN: {{ s.nisn ?? '-' }}</div>
              </td>
              <td class="py-3 px-4">
                <div class="font-bold text-gray-900">{{ s.nama_lengkap }}</div>
                <div class="text-[10px] text-gray-400 font-mono">NIK: {{ s.nik ?? '-' }}</div>
              </td>
              <td class="py-3 px-4 text-center">
                <span :class="s.jenis_kelamin === 'L' ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-rose-50 text-rose-700 border-rose-200'" class="px-2 py-0.5 rounded-full border text-[10px] font-bold">
                  {{ s.jenis_kelamin }}
                </span>
              </td>
              <td class="py-3 px-4 font-medium text-emerald-800 whitespace-nowrap">
                <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-100 font-semibold text-[11px]">
                  {{ s.current_class?.nama || s.school_class?.nama || s.class?.nama || s.class?.nama_kelas || '-' }}
                </span>
              </td>
              <td class="py-3 px-4 whitespace-nowrap">
                <div class="font-medium text-gray-900 text-xs">{{ s.tempat_lahir || '-' }}</div>
                <div class="text-[10px] text-gray-400">{{ formatDateIndo(s.tgl_lahir) }}</div>
              </td>
              <td class="py-3 px-4 whitespace-nowrap">
                <div v-if="getStudentParentName(s)" class="font-medium text-gray-900">
                  {{ getStudentParentName(s) }}
                </div>
                <div v-else class="text-gray-400 italic text-[11px]">-</div>
                <div class="text-[10px] text-gray-400">{{ s.no_hp_siswa || getStudentParentPhone(s) || '-' }}</div>
              </td>
              <td class="py-3 px-4 text-center whitespace-nowrap">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                  {{ s.status }}
                </span>
              </td>
              <td class="py-3 px-4 text-right whitespace-nowrap space-x-1.5">
                <button
                  @click="emit('detail', s)"
                  class="inline-flex items-center gap-1 px-2.5 py-1 bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 rounded-lg text-xs font-semibold transition cursor-pointer"
                  title="Lihat Detail Profil Siswa"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <span>Detail</span>
                </button>
                <button
                  v-if="auth.isAdmin"
                  @click="emit('edit', s)"
                  class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-semibold transition cursor-pointer"
                  title="Edit Data Siswa"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                  <span>Edit</span>
                </button>
                <button
                  v-if="auth.isAdmin"
                  @click="handleDeleteStudent(s)"
                  class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-semibold transition cursor-pointer"
                  title="Hapus Siswa"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  <span>Hapus</span>
                </button>
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
