<template>
  <div class="space-y-4">
    <!-- Quick Stats Cards for Pelajaran & Tematik -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center text-xl shrink-0">
          📚
        </div>
        <div>
          <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Pelajaran</div>
          <div class="text-lg font-black text-gray-900">{{ subjectsList.length }} Mapel</div>
        </div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-purple-100/80 shadow-2xs flex items-center gap-3.5 bg-gradient-to-br from-purple-50/40 to-white">
        <div class="w-11 h-11 rounded-xl bg-purple-600 text-white flex items-center justify-center text-xl shrink-0 shadow-2xs">
          ✨
        </div>
        <div>
          <div class="text-[11px] font-bold text-purple-600 uppercase tracking-wider">Tematik Terpadu</div>
          <div class="text-lg font-black text-purple-950">{{ tematikCount }} Tema</div>
        </div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl shrink-0">
          📘
        </div>
        <div>
          <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Mapel Reguler</div>
          <div class="text-lg font-black text-gray-900">{{ regulerCount }} Mapel</div>
        </div>
      </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
      <div class="flex-1 relative">
        <input
          v-model="searchSubject"
          type="text"
          placeholder="Cari kode, nama pelajaran/tema, atau muatan..."
          class="w-full px-4 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-purple-500 outline-none transition"
        />
      </div>

      <div class="flex flex-wrap sm:flex-nowrap gap-2">
        <select
          v-model="filterSubjectType"
          class="px-3 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-purple-500 outline-none text-gray-700"
        >
          <option value="all">Semua Tipe Pelajaran</option>
          <option value="tematik">✨ Khusus Tematik Terpadu</option>
          <option value="reguler">📘 Khusus Reguler / Mandiri</option>
        </select>

        <select
          v-model="filterSubjectLevel"
          class="px-3 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-purple-500 outline-none text-gray-700"
        >
          <option value="">Semua Tingkat Kelas</option>
          <option value="Kelas 1">Kelas 1</option>
          <option value="Kelas 2">Kelas 2</option>
          <option value="Kelas 3">Kelas 3</option>
          <option value="Kelas 4">Kelas 4</option>
          <option value="Kelas 5">Kelas 5</option>
          <option value="Kelas 6">Kelas 6</option>
        </select>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="py-12 text-center text-gray-400 bg-white rounded-2xl border border-gray-100">
      <div class="inline-block animate-spin w-6 h-6 border-2 border-purple-600 border-t-transparent rounded-full mb-2"></div>
      <p class="text-xs font-medium text-gray-600">Memuat mata pelajaran & tematik...</p>
    </div>

    <!-- Cards Grid Mata Pelajaran & Tematik -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="sub in filteredSubjects"
        :key="sub.id"
        :class="sub.is_tematik ? 'border-purple-200/80 bg-gradient-to-br from-purple-50/20 via-white to-white' : 'border-gray-100 bg-white'"
        class="p-5 rounded-2xl border shadow-sm hover:shadow-md transition flex flex-col justify-between"
      >
        <div class="space-y-3">
          <!-- Top Badges -->
          <div class="flex items-center justify-between">
            <span
              v-if="sub.is_tematik"
              class="px-2.5 py-1 bg-purple-600 text-white rounded-lg text-[10px] font-black uppercase tracking-wider shadow-2xs flex items-center gap-1"
            >
              <span>✨</span>
              <span>TEMATIK (Tema {{ sub.tema_nomor || 1 }})</span>
            </span>
            <span
              v-else
              class="px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg text-[10px] font-bold uppercase tracking-wider"
            >
              📘 {{ sub.kelompok }}
            </span>

            <span class="px-2 py-0.5 font-mono text-xs font-bold bg-gray-100 text-gray-800 rounded-md">
              {{ sub.kode }}
            </span>
          </div>

          <!-- Title & Target Level -->
          <div>
            <h3 class="text-base font-black text-gray-900 leading-snug">
              {{ sub.nama }}
            </h3>
            <p class="text-xs text-gray-500 mt-0.5">
              Berlaku: <strong class="text-gray-700">{{ sub.tingkat_berlaku || 'Semua Tingkat' }}</strong>
            </p>
          </div>

          <!-- Muatan Campuran Pelajaran (Khusus Tematik) -->
          <div v-if="sub.is_tematik && sub.muatan_pelajaran && sub.muatan_pelajaran.length" class="space-y-1.5 pt-1">
            <span class="text-[10px] font-bold text-purple-700 uppercase tracking-wider block">
              Muatan Pelajaran Terintegrasi:
            </span>
            <div class="flex flex-wrap gap-1">
              <span
                v-for="muatan in sub.muatan_pelajaran"
                :key="muatan"
                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200"
              >
                {{ muatan }}
              </span>
            </div>
          </div>

          <!-- Deskripsi Ringkas -->
          <p v-if="sub.deskripsi" class="text-xs text-gray-500 italic line-clamp-2">
            {{ sub.deskripsi }}
          </p>

          <!-- Meta Footer Info -->
          <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
            <span>JP / Minggu: <strong class="text-gray-800">{{ sub.jam_per_minggu ?? 4 }} JP</strong></span>
            <span>KKM: <strong class="text-gray-800">{{ sub.kkm ?? 75 }}</strong></span>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2 mt-4">
          <button
            @click="$emit('edit', sub)"
            class="px-3 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg text-xs font-semibold transition border border-amber-200 cursor-pointer"
          >
            Edit
          </button>
          <button
            @click="handleDeleteSubject(sub)"
            class="px-3 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-semibold transition border border-rose-200 cursor-pointer"
          >
            Hapus
          </button>
        </div>
      </div>

      <div v-if="filteredSubjects.length === 0" class="col-span-full py-12 text-center text-gray-400 bg-white rounded-2xl border border-gray-100">
        Tidak ada mata pelajaran atau tema yang sesuai dengan filter.
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '../../services/api';
import { confirmDeleteWithKeyword, showSuccess, showError } from '../../services/swal';

const emit = defineEmits<{
  (e: 'create'): void;
  (e: 'edit', subject: any): void;
  (e: 'updated', subjects: any[]): void;
}>();

const subjectsList = ref<any[]>([]);
const loading = ref(false);
const searchSubject = ref('');
const filterSubjectType = ref('all');
const filterSubjectLevel = ref('');

const fetchSubjects = async () => {
  loading.value = true;
  try {
    const res = await api.get('/subjects', { params: { all: true } });
    if (res.data.success) {
      subjectsList.value = res.data.data || [];
      emit('updated', subjectsList.value);
    }
  } catch (err: any) {
    showError('Gagal Mengambil Pelajaran', err, 'Terjadi kendala saat mengambil data mata pelajaran.');
  } finally {
    loading.value = false;
  }
};

const tematikCount = computed(() => {
  return subjectsList.value.filter(s => s.is_tematik).length;
});

const regulerCount = computed(() => {
  return subjectsList.value.filter(s => !s.is_tematik).length;
});

const filteredSubjects = computed(() => {
  return subjectsList.value.filter(s => {
    if (searchSubject.value) {
      const q = searchSubject.value.toLowerCase().trim();
      const matchName = (s.nama || '').toLowerCase().includes(q);
      const matchCode = (s.kode || '').toLowerCase().includes(q);
      const matchMuatan = Array.isArray(s.muatan_pelajaran) && s.muatan_pelajaran.some((m: string) => (m || '').toLowerCase().includes(q));
      if (!matchName && !matchCode && !matchMuatan) return false;
    }
    if (filterSubjectType.value === 'tematik' && !s.is_tematik) return false;
    if (filterSubjectType.value === 'reguler' && s.is_tematik) return false;
    if (filterSubjectLevel.value && s.tingkat_berlaku !== filterSubjectLevel.value && s.tingkat_berlaku !== 'Semua Tingkat') {
      return false;
    }
    return true;
  });
});

const handleDeleteSubject = async (sub: any) => {
  const confirmed = await confirmDeleteWithKeyword(`pelajaran ${sub.nama} (${sub.kode})`);
  if (!confirmed) return;

  try {
    const res = await api.delete(`/subjects/${sub.id}`);
    if (res.data.success) {
      showSuccess('Terhapus!', 'Mata pelajaran berhasil dihapus.');
      fetchSubjects();
    }
  } catch (err: any) {
    showError('Gagal Menghapus!', err, 'Mata pelajaran masih digunakan pada jadwal.');
  }
};

defineExpose({
  fetchSubjects,
});

onMounted(() => {
  fetchSubjects();
});
</script>
