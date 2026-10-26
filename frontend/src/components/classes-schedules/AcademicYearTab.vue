<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../services/api';
import { showSuccess, showError, showConfirm } from '../../services/swal';

const emit = defineEmits<{
  (e: 'year-changed', yearId: number): void;
  (e: 'updated'): void;
}>();

const academicYearsList = ref<any[]>([]);
const classesList = ref<any[]>([]);
const loadingAcademic = ref(false);

const fetchAcademicYearsData = async () => {
  loadingAcademic.value = true;
  try {
    const res = await api.get('/academic-years');
    if (res.data.success && res.data.data) {
      academicYearsList.value = res.data.data;
      const activeYear = res.data.data.find((y: any) => y.is_aktif || y.status === 'aktif');
      if (activeYear) {
        if (!promoteTargetYearId.value) {
          promoteTargetYearId.value = activeYear.id;
        }
      }
    }
  } catch (err: any) {
    console.error('Gagal mengambil data tahun ajaran', err);
  } finally {
    loadingAcademic.value = false;
  }
};

const fetchClasses = async () => {
  try {
    const res = await api.get('/classes', { params: { all: true } });
    if (res.data.success && res.data.data) {
      classesList.value = Array.isArray(res.data.data) ? res.data.data : (res.data.data.data || []);
    }
  } catch (err: any) {
    console.error('Gagal mengambil data rombel untuk wizard', err);
  }
};

const handleSetActiveAcademicYear = async (year: any) => {
  const confirmed = await showConfirm(
    `Aktifkan Tahun Ajaran ${year.nama}?`,
    `Tahun ajaran ini akan dijadikan tahun ajaran aktif sistem SIMBIM. Rombel, presensi, dan e-rapor akan menyesuaikan tahun ajaran ini.`,
    'Ya, Aktifkan'
  );
  if (!confirmed) return;

  try {
    const res = await api.post(`/academic-years/${year.id}/set-active`);
    if (res.data.success) {
      showSuccess('Berhasil!', res.data.message || `Tahun ajaran ${year.nama} kini aktif.`);
      await fetchAcademicYearsData();
      emit('year-changed', year.id);
      emit('updated');
    }
  } catch (err: any) {
    showError('Gagal Mengaktifkan!', err, 'Gagal mengatur tahun ajaran aktif.');
  }
};

const handleActivateSemester = async (sem: any) => {
  const confirmed = await showConfirm(
    `Aktifkan ${sem.nama}?`,
    `Semester aktif akan dipindahkan ke ${sem.nama}. Semester sebelumnya akan ditutup status aktifnya.`,
    'Ya, Aktifkan Semester'
  );
  if (!confirmed) return;

  try {
    const res = await api.post(`/semesters/${sem.id}/activate`);
    if (res.data.success) {
      showSuccess('Berhasil!', res.data.message || `Semester ${sem.nama} kini aktif.`);
      await fetchAcademicYearsData();
      emit('updated');
    }
  } catch (err: any) {
    showError('Gagal Mengaktifkan Semester!', err, 'Gagal mengubah semester aktif.');
  }
};

// Modal Buat Tahun Ajaran
const showCreateYearModal = ref(false);
const newYearForm = ref({
  nama: '',
  tgl_mulai: '',
  tgl_selesai: '',
  status: 'rencana',
});

const formatDate = (dateStr?: string) => {
  if (!dateStr) return '-';
  const clean = dateStr.substring(0, 10);
  try {
    const [y, m, d] = clean.split('-');
    if (y && m && d) {
      const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
      return `${parseInt(d, 10)} ${monthNames[parseInt(m, 10) - 1] || m} ${y}`;
    }
    return clean;
  } catch {
    return clean;
  }
};

const openCreateYearModal = () => {
  newYearForm.value = {
    nama: '',
    tgl_mulai: '',
    tgl_selesai: '',
    status: 'rencana',
  };
  showCreateYearModal.value = true;
};

const submitCreateYearForm = async () => {
  try {
    const res = await api.post('/academic-years', newYearForm.value);
    if (res.data.success) {
      showSuccess('Berhasil!', 'Tahun ajaran baru dan semester ganjil/genap berhasil dibuat.');
      showCreateYearModal.value = false;
      await fetchAcademicYearsData();
      emit('updated');
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err, 'Periksa format tahun ajaran dan tanggal.');
  }
};

// Wizard Kenaikan Kelas
const promoteSourceClassId = ref<number | null>(null);
const promoteTargetYearId = ref<number | null>(null);
const promoteTargetClassId = ref<number | null>(null);
const studentsToPromote = ref<any[]>([]);
const loadingStudentsToPromote = ref(false);
const promoting = ref(false);

const fetchStudentsForPromotion = async () => {
  if (!promoteSourceClassId.value) {
    studentsToPromote.value = [];
    return;
  }
  loadingStudentsToPromote.value = true;
  try {
    let list: any[] = [];
    const res = await api.get('/students', {
      params: { class_id: promoteSourceClassId.value, all: true, status: 'aktif' }
    });
    if (res.data.success && Array.isArray(res.data.data) && res.data.data.length > 0) {
      list = res.data.data;
    } else {
      const resClass = await api.get(`/classes/${promoteSourceClassId.value}`);
      if (resClass.data.success && resClass.data.data) {
        list = resClass.data.data.active_students || resClass.data.data.students || [];
      }
    }

    studentsToPromote.value = list.map((s: any, idx: number) => ({
      student_id: s.id,
      nama_lengkap: s.nama_lengkap,
      nis: s.nis || '',
      nisn: s.nisn || '',
      status_kelas: 'naik',
      nomor_absen: s.nomor_absen ?? (idx + 1),
    }));
  } catch (err: any) {
    showError('Gagal Memuat Siswa!', err, 'Gagal mengambil daftar siswa dari rombel asal.');
  } finally {
    loadingStudentsToPromote.value = false;
  }
};

const setAllPromotionStatus = (status: string) => {
  studentsToPromote.value.forEach(s => {
    s.status_kelas = status;
  });
};

const handleExecutePromotion = async () => {
  if (!promoteSourceClassId.value || !promoteTargetYearId.value) {
    showError('Validasi Gagal', null, 'Harap pilih kelas asal dan tahun ajaran tujuan.');
    return;
  }

  const hasNaikOrTinggal = studentsToPromote.value.some(s => ['naik', 'tinggal_kelas'].includes(s.status_kelas));
  if (hasNaikOrTinggal && !promoteTargetClassId.value) {
    showError('Validasi Gagal', null, 'Untuk siswa yang naik atau tinggal kelas, kelas tujuan wajib dipilih.');
    return;
  }

  const confirmed = await showConfirm(
    'Konfirmasi Kenaikan / Kelulusan Kelas',
    `Yakin ingin mengeksekusi proses untuk ${studentsToPromote.value.length} siswa? Data riwayat kelas lama akan diarsipkan dan siswa akan dialokasikan ke kelas baru.`,
    'Ya, Eksekusi Sekarang'
  );
  if (!confirmed) return;

  promoting.value = true;
  try {
    const payload = {
      source_class_id: promoteSourceClassId.value,
      target_year_id: promoteTargetYearId.value,
      target_class_id: promoteTargetClassId.value || null,
      students: studentsToPromote.value.map(s => ({
        student_id: s.student_id,
        status_kelas: s.status_kelas,
        nomor_absen: s.nomor_absen || null,
      }))
    };

    const res = await api.post('/classes/promote-students', payload);
    if (res.data.success) {
      showSuccess('Berhasil!', res.data.message || 'Kenaikan kelas massal berhasil dieksekusi.');
      await fetchStudentsForPromotion();
      emit('updated');
    }
  } catch (err: any) {
    showError('Gagal Eksekusi!', err, 'Gagal memproses kenaikan kelas siswa.');
  } finally {
    promoting.value = false;
  }
};

onMounted(() => {
  fetchAcademicYearsData();
  fetchClasses();
});

defineExpose({
  openCreateYearModal,
  fetchAcademicYearsData
});
</script>

<template>
  <div class="space-y-6">
    <!-- Panel 1: Master Tahun Ajaran & Semester Aktif -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-xs space-y-5">
      <div class="border-b border-slate-100 pb-4">
        <div>
          <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
            <span>🗓️</span>
            <span>Tahun Ajaran & Semester Aktif</span>
          </h2>
          <p class="text-xs text-slate-500 mt-0.5">
            Pengaturan manual pergantian semester kalender sekolah dan aktivasi tahun ajaran baru.
          </p>
        </div>
      </div>

      <!-- Cards List of Academic Years -->
      <div v-if="loadingAcademic" class="py-8 text-center text-slate-400">
        <div class="inline-block animate-spin w-6 h-6 border-2 border-emerald-600 border-t-transparent rounded-full mb-2"></div>
        <div class="text-xs">Memuat data tahun ajaran...</div>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="ay in academicYearsList"
          :key="ay.id"
          class="p-5 rounded-3xl border transition-all flex flex-col justify-between"
          :class="ay.status === 'aktif' ? 'bg-emerald-50/40 border-emerald-300 ring-2 ring-emerald-500/20 shadow-xs' : 'bg-white border-slate-200 hover:border-slate-300'"
        >
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-lg font-black text-slate-900 font-mono tracking-tight">
                {{ ay.nama }}
              </span>
              <span
                class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase border"
                :class="{
                  'bg-emerald-100 text-emerald-800 border-emerald-200': ay.status === 'aktif',
                  'bg-blue-100 text-blue-800 border-blue-200': ay.status === 'rencana',
                  'bg-slate-100 text-slate-600 border-slate-200': ay.status === 'selesai',
                }"
              >
                {{ ay.status === 'aktif' ? '● Aktif' : ay.status }}
              </span>
            </div>

            <div class="text-xs text-slate-500 font-medium">
              Periode: {{ formatDate(ay.tgl_mulai) }} s.d. {{ formatDate(ay.tgl_selesai) }}
            </div>

            <!-- Semesters in this year -->
            <div class="pt-2 border-t border-slate-200/60 space-y-2">
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status Semester:</div>
              <div
                v-for="sem in ay.semesters"
                :key="sem.id"
                class="flex items-center justify-between p-2 rounded-xl text-xs bg-slate-50 border border-slate-100"
              >
                <div>
                  <span class="font-bold uppercase text-slate-800">Semester {{ sem.semester }}</span>
                  <span
                    class="ml-2 px-1.5 py-0.2 rounded text-[10px] font-bold capitalize"
                    :class="sem.status === 'aktif' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'"
                  >
                    {{ sem.status }}
                  </span>
                </div>

                <button
                  v-if="sem.status !== 'aktif'"
                  @click="handleActivateSemester(sem)"
                  class="px-2 py-0.5 bg-white hover:bg-slate-200 text-slate-700 rounded-lg text-[10px] font-bold border border-slate-200 transition cursor-pointer"
                >
                  Aktifkan
                </button>
                <span v-else class="text-[10px] font-black text-emerald-700">Aktif Sekarang</span>
              </div>
            </div>
          </div>

          <div class="mt-4 pt-3 border-t border-slate-200/60 flex items-center justify-between">
            <span class="text-xs text-slate-500">
              {{ ay.classes_count ?? 0 }} Rombel
            </span>
            <button
              v-if="ay.status !== 'aktif'"
              @click="handleSetActiveAcademicYear(ay)"
              class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer"
            >
              Set Jadi Tahun Aktif
            </button>
            <span v-else class="text-xs font-black text-emerald-700">✓ Sedang Berjalan</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Panel 2: Wizard Kenaikan Kelas & Kelulusan Massal -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-xs space-y-6">
      <div class="border-b border-slate-100 pb-4">
        <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
          <span>🎓</span>
          <span>Wizard Kenaikan Kelas & Kelulusan Massal</span>
        </h2>
        <p class="text-xs text-slate-500 mt-0.5">
          Proses perpindahan siswa ke rombel tahun ajaran baru secara massal, penetapan tinggal kelas, atau kelulusan alumni.
        </p>
      </div>

      <!-- Wizard Selectors -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-5 bg-slate-50 rounded-2xl border border-slate-200/80">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">
            1. Rombel Kelas Asal (Sumber) *
          </label>
          <select
            v-model="promoteSourceClassId"
            @change="fetchStudentsForPromotion"
            class="w-full bg-white border border-slate-200 rounded-xl p-2.5 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500 outline-none"
          >
            <option :value="null">-- Pilih Kelas Asal --</option>
            <option v-for="c in classesList" :key="c.id" :value="c.id">
              {{ c.nama }} (Tingkat {{ c.tingkat }})
            </option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">
            2. Tahun Ajaran Tujuan *
          </label>
          <select
            v-model="promoteTargetYearId"
            class="w-full bg-white border border-slate-200 rounded-xl p-2.5 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500 outline-none"
          >
            <option :value="null">-- Pilih Tahun Ajaran Baru --</option>
            <option v-for="ay in academicYearsList" :key="ay.id" :value="ay.id">
              {{ ay.nama }} ({{ ay.status }})
            </option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">
            3. Rombel Kelas Tujuan *
          </label>
          <select
            v-model="promoteTargetClassId"
            class="w-full bg-white border border-slate-200 rounded-xl p-2.5 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500 outline-none"
          >
            <option :value="null">-- Pilih Kelas Tujuan (Kosongkan jika Lulus Semua) --</option>
            <option v-for="c in classesList" :key="c.id" :value="c.id">
              {{ c.nama }} (Tingkat {{ c.tingkat }})
            </option>
          </select>
        </div>
      </div>

      <!-- Quick Action Buttons -->
      <div v-if="studentsToPromote.length > 0" class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <span class="text-xs font-bold text-slate-600">Aksi Cepat:</span>
          <button
            @click="setAllPromotionStatus('naik')"
            type="button"
            class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold transition cursor-pointer"
          >
            Set Semua Naik Kelas
          </button>
          <button
            @click="setAllPromotionStatus('lulus')"
            type="button"
            class="px-3 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-800 border border-purple-200 rounded-xl text-xs font-bold transition cursor-pointer"
          >
            Set Semua Lulus (Alumni)
          </button>
        </div>

        <div class="text-xs font-bold text-slate-700">
          Total Siswa: <strong class="text-emerald-700">{{ studentsToPromote.length }} Siswa</strong>
        </div>
      </div>

      <!-- Table of Students for Promotion -->
      <div v-if="loadingStudentsToPromote" class="py-12 text-center text-slate-400">
        <div class="inline-block animate-spin w-6 h-6 border-2 border-emerald-600 border-t-transparent rounded-full mb-2"></div>
        <div class="text-xs">Memuat daftar siswa kelas terpilih...</div>
      </div>

      <div v-else-if="studentsToPromote.length === 0" class="py-12 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-xs text-slate-400">
        Pilih kelas asal di atas untuk menampilkan daftar siswa yang akan diproses kenaikan kelasnya.
      </div>

      <div v-else class="border border-slate-200 rounded-2xl overflow-hidden">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-100/80 text-slate-700 font-extrabold uppercase text-[10px] border-b border-slate-200">
            <tr>
              <th class="py-3 px-4 w-12 text-center">No</th>
              <th class="py-3 px-4 w-28">NIS / NISN</th>
              <th class="py-3 px-4">Nama Lengkap Siswa</th>
              <th class="py-3 px-4 w-44">Status Kenaikan</th>
              <th class="py-3 px-4 w-28 text-center">No. Absen Baru</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            <tr v-for="(s, idx) in studentsToPromote" :key="s.student_id" class="hover:bg-slate-50/70">
              <td class="py-2.5 px-4 text-center text-slate-400 font-mono">{{ idx + 1 }}</td>
              <td class="py-2.5 px-4 font-mono text-[11px] text-slate-600">
                <div>{{ s.nis || '-' }}</div>
              </td>
              <td class="py-2.5 px-4 font-bold text-slate-900">
                {{ s.nama_lengkap }}
              </td>
              <td class="py-2.5 px-4">
                <select
                  v-model="s.status_kelas"
                  class="w-full border rounded-xl p-1.5 text-xs font-bold outline-none"
                  :class="{
                    'bg-emerald-50 text-emerald-800 border-emerald-200': s.status_kelas === 'naik',
                    'bg-purple-50 text-purple-800 border-purple-200': s.status_kelas === 'lulus',
                    'bg-amber-50 text-amber-800 border-amber-200': s.status_kelas === 'tinggal_kelas',
                    'bg-rose-50 text-rose-800 border-rose-200': s.status_kelas === 'pindah',
                  }"
                >
                  <option value="naik">Naik Kelas</option>
                  <option value="tinggal_kelas">Tinggal Kelas</option>
                  <option value="lulus">Lulus / Alumni</option>
                  <option value="pindah">Pindah / Mutasi</option>
                </select>
              </td>
              <td class="py-2.5 px-4 text-center">
                <input
                  v-model.number="s.nomor_absen"
                  type="number"
                  placeholder="Absen"
                  class="w-20 text-center border border-slate-200 rounded-lg p-1 text-xs font-mono font-bold"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Bottom Execute Button -->
      <div v-if="studentsToPromote.length > 0" class="pt-4 border-t border-slate-100 flex items-center justify-end">
        <button
          @click="handleExecutePromotion"
          :disabled="promoting"
          type="button"
          class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-md transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
        >
          <span v-if="promoting">Memproses...</span>
          <span v-else>🚀 Eksekusi Kenaikan Kelas ({{ studentsToPromote.length }} Siswa)</span>
        </button>
      </div>
    </div>
  </div>

  <!-- MODAL BUAT TAHUN AJARAN BARU -->
  <div
    v-if="showCreateYearModal"
    class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5"
  >
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4 animate-in fade-in zoom-in-95">
      <div class="flex items-center justify-between border-b pb-3">
        <h3 class="text-sm font-bold text-slate-900">Buat Tahun Ajaran Baru</h3>
        <button @click="showCreateYearModal = false" class="text-slate-400 hover:text-slate-700 font-bold cursor-pointer">✕</button>
      </div>

      <form @submit.prevent="submitCreateYearForm" class="space-y-4 text-xs">
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Format Nama Tahun Ajaran (YYYY/YYYY) *</label>
          <input
            v-model="newYearForm.nama"
            type="text"
            required
            placeholder="Contoh: 2025/2026"
            class="w-full border border-slate-200 rounded-xl p-2.5 text-xs font-mono font-bold focus:ring-2 focus:ring-emerald-500 outline-none"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Tanggal Mulai *</label>
            <input
              v-model="newYearForm.tgl_mulai"
              type="date"
              required
              class="w-full border border-slate-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
            />
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Tanggal Selesai *</label>
            <input
              v-model="newYearForm.tgl_selesai"
              type="date"
              required
              class="w-full border border-slate-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
            />
          </div>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1">Status Awal</label>
          <select
            v-model="newYearForm.status"
            class="w-full border border-slate-200 rounded-xl p-2.5 text-xs bg-white font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500 outline-none"
          >
            <option value="rencana">Rencana (Belum Diaktifkan)</option>
            <option value="aktif">Langsung Aktifkan Sekarang</option>
          </select>
        </div>

        <div class="pt-3 border-t flex items-center justify-end gap-2">
          <button
            @click="showCreateYearModal = false"
            type="button"
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold cursor-pointer"
          >
            Batal
          </button>
          <button
            type="submit"
            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold shadow-xs cursor-pointer"
          >
            Simpan Tahun Ajaran
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
