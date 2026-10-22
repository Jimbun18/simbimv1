<template>
  <div>
    <!-- VIEW 1: DAFTAR TABEL WALI MURID (LIST VIEW) -->
    <GuardianList
      v-if="currentView === 'list'"
      :guardians="filteredGuardians"
      :loading="loading"
      :stats="stats"
      :meta="meta"
      :total-connected-students="totalConnectedStudents"
      :search="search"
      :filter-hubungan="filterHubungan"
      :filter-akun="filterAkun"
      :per-page="perPage"
      @update:search="onSearchUpdate"
      @update:filter-hubungan="onFilterHubunganUpdate"
      @update:filter-akun="filterAkun = $event"
      @update:per-page="onPerPageUpdate"
      @open-create="openCreateForm"
      @edit="openEditForm"
      @delete="handleDelete"
      @create-account="handleCreateAccount"
      @reset-password="handleResetPassword"
      @manage-students="openModalManageStudents"
      @go-to-student="goToStudent"
      @fetch="fetchGuardians"
    />

    <!-- VIEW 2: HALAMAN FORMULIR WALI PENUH (SUB-PAGE REPLACING LIST) -->
    <GuardianForm
      v-else-if="currentView === 'form'"
      :form="form"
      :is-editing="isEditing"
      :saving="saving"
      @submit="submitForm"
      @cancel="closeForm"
    />

    <!-- MODAL: KELOLA RELASI ANAK (KASUS KAKAK-ADIK) -->
    <LinkStudentModal
      :guardian="selectedGuardianForStudents"
      :all-students="allStudents"
      :initial-selected-ids="selectedStudentIds"
      :saving="savingRelations"
      @close="selectedGuardianForStudents = null"
      @save="saveStudentRelations"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';
import { confirmDeleteWithKeyword, showSuccess, showError } from '../services/swal';
import { toInputDate } from '../utils/date';
import GuardianList from '../components/guardians/GuardianList.vue';
import GuardianForm from '../components/guardians/GuardianForm.vue';
import LinkStudentModal from '../components/guardians/LinkStudentModal.vue';
import type {
  Guardian,
  LinkedStudent,
  GuardianFormData,
  GuardianStats,
  GuardianMeta
} from '../components/guardians/types';

const router = useRouter();

// View Mode: 'list' (tabel) atau 'form' (halaman formulir penuh menimpa tabel)
const currentView = ref<'list' | 'form'>('list');

const guardians = ref<Guardian[]>([]);
const allStudents = ref<LinkedStudent[]>([]);
const search = ref('');
const filterHubungan = ref('');
const filterAkun = ref('');
const loading = ref(false);
const saving = ref(false);
const savingRelations = ref(false);

// Pagination & Stats
const currentPage = ref(1);
const perPage = ref(20);
const meta = ref<GuardianMeta>({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
});
const stats = ref<GuardianStats>({
  total_guardians: 0,
  active_accounts: 0,
  connected_students: 0,
});

// Form State
const isEditing = ref(false);
const editingId = ref<number | null>(null);
const form = ref<GuardianFormData>({
  hubungan: 'ayah',
  nama_lengkap: '',
  nik: '',
  tempat_lahir: 'Purwokerto',
  tgl_lahir: '',
  status_hidup: 'hidup',
  no_hp: '',
  email: '',
  alamat_jalan: '',
  kab_kota: 'Banyumas',
  create_user_account: true,
});

// Student Linkage State
const selectedGuardianForStudents = ref<Guardian | null>(null);
const selectedStudentIds = ref<number[]>([]);

const goToStudent = (s: LinkedStudent) => {
  router.push({
    path: '/students',
    query: { search: s.nis, open_id: s.id }
  });
};

const totalConnectedStudents = computed(() => {
  if (stats.value.connected_students) return stats.value.connected_students;
  const set = new Set<number>();
  guardians.value.forEach(g => {
    if (g.students) {
      g.students.forEach((s) => set.add(s.id));
    }
  });
  return set.size;
});

const filteredGuardians = computed(() => {
  return guardians.value.filter(g => {
    if (filterAkun.value === 'has_account' && !g.user_id) return false;
    if (filterAkun.value === 'no_account' && g.user_id) return false;
    return true;
  });
});

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
const debouncedFetch = () => {
  if (debounceTimer) clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    fetchGuardians(1);
  }, 300);
};

const onSearchUpdate = (val: string) => {
  search.value = val;
  debouncedFetch();
};

const onFilterHubunganUpdate = (val: string) => {
  filterHubungan.value = val;
  fetchGuardians(1);
};

const onPerPageUpdate = (val: number) => {
  perPage.value = val;
  fetchGuardians(1);
};

const fetchGuardians = async (page = 1) => {
  loading.value = true;
  currentPage.value = page;
  try {
    const params: Record<string, any> = {
      page: currentPage.value,
      per_page: perPage.value,
    };
    if (search.value) params.search = search.value;
    if (filterHubungan.value) params.hubungan = filterHubungan.value;

    const res = await api.get('/guardians', { params });
    if (res.data.success) {
      guardians.value = res.data.data || [];
      if (res.data.meta) meta.value = res.data.meta;
      if (res.data.stats) stats.value = res.data.stats;
    }
  } catch (err) {
    console.error('Gagal mengambil data wali murid', err);
  } finally {
    loading.value = false;
  }
};

const fetchAllStudents = async () => {
  try {
    const res = await api.get('/students', { params: { all: 'true' } });
    if (res.data.success) {
      allStudents.value = res.data.data?.data || res.data.data || [];
    }
  } catch (err) {
    console.error('Gagal mengambil daftar siswa', err);
  }
};

// ==============================================================
// FORM VIEW TRANSITIONS (Halaman Form Baru Menimpa Tabel)
// ==============================================================
const openCreateForm = () => {
  isEditing.value = false;
  editingId.value = null;
  form.value = {
    hubungan: 'ayah',
    nama_lengkap: '',
    nik: '',
    tempat_lahir: 'Purwokerto',
    tgl_lahir: '',
    status_hidup: 'hidup',
    no_hp: '',
    email: '',
    alamat_jalan: '',
    kab_kota: 'Banyumas',
    create_user_account: true,
  };
  currentView.value = 'form';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const openEditForm = (g: Guardian) => {
  isEditing.value = true;
  editingId.value = g.id;
  form.value = {
    hubungan: g.hubungan || 'ayah',
    nama_lengkap: g.nama_lengkap || '',
    nik: g.nik || '',
    tempat_lahir: g.tempat_lahir || 'Purwokerto',
    tgl_lahir: toInputDate(g.tgl_lahir),
    status_hidup: g.status_hidup || 'hidup',
    no_hp: g.no_whatsapp || g.no_hp || '',
    email: g.email || '',
    alamat_jalan: g.alamat_jalan || '',
    kab_kota: g.kab_kota || 'Banyumas',
  };
  currentView.value = 'form';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const closeForm = () => {
  currentView.value = 'list';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const submitForm = async () => {
  saving.value = true;
  try {
    const payload: Partial<GuardianFormData> = { ...form.value };
    if (!payload.nik) delete payload.nik;
    if (!payload.email) delete payload.email;
    if (!payload.tgl_lahir) delete payload.tgl_lahir;

    if (isEditing.value && editingId.value) {
      delete payload.create_user_account;
      const res = await api.put(`/guardians/${editingId.value}`, payload);
      if (res.data.success) {
        showSuccess('Berhasil!', 'Data wali murid berhasil diperbarui.');
        closeForm();
        fetchGuardians();
      }
    } else {
      const res = await api.post('/guardians', payload);
      if (res.data.success) {
        showSuccess('Berhasil!', 'Wali murid baru berhasil ditambahkan.');
        closeForm();
        fetchGuardians();
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err, 'Terjadi kesalahan saat menyimpan data wali murid.');
  } finally {
    saving.value = false;
  }
};

const handleCreateAccount = async (g: Guardian) => {
  try {
    const res = await api.post(`/guardians/${g.id}/create-account`);
    if (res.data.success) {
      showSuccess('Akun Dibuat!', res.data.message);
      fetchGuardians();
    }
  } catch (err: any) {
    showError('Gagal Membuat Akun!', err, 'Terjadi kendala saat membuat akun wali murid.');
  }
};

const handleResetPassword = async (g: Guardian) => {
  if (!g.user_id) return;
  try {
    const res = await api.post(`/users/${g.user_id}/reset-password`);
    if (res.data.success) {
      showSuccess('Kata Sandi Direset!', res.data.message || 'Kata sandi berhasil direset ke Password123!');
    }
  } catch (err: any) {
    showError('Gagal Reset Password!', err, 'Terjadi kendala saat mereset kata sandi.');
  }
};

const openModalManageStudents = (g: Guardian) => {
  selectedGuardianForStudents.value = g;
  selectedStudentIds.value = g.students ? g.students.map((s) => s.id) : [];
};

const saveStudentRelations = async (ids: number[]) => {
  if (!selectedGuardianForStudents.value) return;
  savingRelations.value = true;
  try {
    const res = await api.post(`/guardians/${selectedGuardianForStudents.value.id}/sync-students`, {
      student_ids: ids,
    });
    if (res.data.success) {
      showSuccess('Berhasil!', 'Relasi anak tanggungan berhasil diperbarui.');
      selectedGuardianForStudents.value = null;
      fetchGuardians();
    }
  } catch (err: any) {
    showError('Gagal Menyimpan Relasi!', err, 'Terjadi kendala saat menghubungkan data anak.');
  } finally {
    savingRelations.value = false;
  }
};

const handleDelete = async (g: Guardian) => {
  const confirmed = await confirmDeleteWithKeyword(`wali murid "${g.nama_lengkap}"`);
  if (!confirmed) return;

  try {
    const res = await api.delete(`/guardians/${g.id}`);
    if (res.data.success) {
      showSuccess('Berhasil Dihapus!', res.data.message);
      fetchGuardians();
    }
  } catch (err: any) {
    showError('Gagal Menghapus!', err, 'Data wali murid tidak dapat dihapus.');
  }
};

onMounted(() => {
  fetchGuardians();
  fetchAllStudents();
});
</script>
