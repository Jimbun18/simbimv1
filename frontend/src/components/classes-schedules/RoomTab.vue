<template>
  <div class="space-y-4">
    <!-- Filter & Search Bar -->
    <div class="bg-white p-3.5 rounded-2xl border border-gray-100 shadow-2xs flex flex-wrap items-center justify-between gap-3 text-xs">
      <div class="flex flex-wrap items-center gap-2 flex-1">
        <div class="relative min-w-[200px] flex-1 sm:flex-initial">
          <input
            v-model="searchRoom"
            type="text"
            placeholder="Cari nama, kode, atau gedung..."
            class="w-full border border-gray-200 rounded-xl pl-8 pr-3 py-1.5 text-xs bg-white text-gray-700 focus:ring-2 focus:ring-purple-500 outline-none"
          />
          <span class="absolute left-2.5 top-2 text-gray-400">🔍</span>
        </div>

        <select
          v-model="filterRoomJenis"
          class="border border-gray-200 rounded-xl px-3 py-1.5 text-xs bg-white text-gray-700 focus:ring-2 focus:ring-purple-500 outline-none font-medium"
        >
          <option value="">-- Semua Jenis Ruangan --</option>
          <option value="ruang_kelas">Ruang Kelas</option>
          <option value="lab">Laboratorium</option>
          <option value="perpustakaan">Perpustakaan</option>
          <option value="aula">Aula</option>
          <option value="mushola">Mushola</option>
          <option value="lainnya">Lainnya</option>
        </select>

        <select
          v-model="filterRoomKondisi"
          class="border border-gray-200 rounded-xl px-3 py-1.5 text-xs bg-white text-gray-700 focus:ring-2 focus:ring-purple-500 outline-none font-medium"
        >
          <option value="">-- Semua Kondisi --</option>
          <option value="baik">Kondisi Baik</option>
          <option value="rusak_ringan">Rusak Ringan</option>
          <option value="rusak_berat">Rusak Berat</option>
        </select>
      </div>

      <span class="text-gray-400 font-mono text-[11px] shrink-0">
        Menampilkan {{ filteredRooms.length }} dari {{ rooms.length }} Ruangan
      </span>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="py-12 text-center text-gray-400 bg-white rounded-2xl border border-gray-100">
      <div class="inline-block animate-spin w-6 h-6 border-2 border-purple-600 border-t-transparent rounded-full mb-2"></div>
      <p class="text-xs font-medium text-gray-600">Memuat data ruangan & fasilitas...</p>
    </div>

    <!-- Room Cards Grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="r in filteredRooms"
        :key="r.id"
        class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm space-y-3 hover:shadow-md transition flex flex-col justify-between"
      >
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-1.5">
              <span class="font-mono font-bold text-xs bg-gray-100 px-2 py-0.5 rounded text-gray-700 border border-gray-200">
                {{ r.kode }}
              </span>
              <span
                class="text-[10px] font-bold px-2 py-0.5 rounded-full capitalize border"
                :class="{
                  'bg-purple-50 text-purple-700 border-purple-200': r.jenis === 'ruang_kelas',
                  'bg-blue-50 text-blue-700 border-blue-200': r.jenis === 'lab',
                  'bg-amber-50 text-amber-700 border-amber-200': r.jenis === 'perpustakaan',
                  'bg-indigo-50 text-indigo-700 border-indigo-200': r.jenis === 'aula',
                  'bg-emerald-50 text-emerald-700 border-emerald-200': r.jenis === 'mushola',
                  'bg-slate-50 text-slate-700 border-slate-200': !r.jenis || r.jenis === 'lainnya',
                }"
              >
                {{ r.jenis ? r.jenis.replace('_', ' ') : 'Ruang' }}
              </span>
            </div>

            <span
              class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase"
              :class="{
                'bg-emerald-50 text-emerald-700': (r.kondisi ?? 'baik') === 'baik',
                'bg-amber-50 text-amber-700': r.kondisi === 'rusak_ringan',
                'bg-rose-50 text-rose-700': r.kondisi === 'rusak_berat',
              }"
            >
              {{ r.kondisi ? r.kondisi.replace('_', ' ') : 'Baik' }}
            </span>
          </div>

          <div>
            <h3 class="text-sm font-bold text-gray-900 leading-snug">{{ r.nama }}</h3>
            <p class="text-xs text-gray-500 mt-1">
              {{ r.gedung_lantai || 'Gedung Sekolah' }} • Kapasitas: <strong class="text-gray-700">{{ r.kapasitas ?? 30 }} Orang</strong>
            </p>
          </div>
        </div>

        <!-- Card Actions -->
        <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
          <button
            @click="openEditModal(r)"
            type="button"
            class="px-3 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg text-xs font-semibold transition border border-amber-200 cursor-pointer"
          >
            Edit
          </button>
          <button
            @click="handleDeleteRoom(r)"
            type="button"
            class="px-3 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-semibold transition border border-rose-200 cursor-pointer"
          >
            Hapus
          </button>
        </div>
      </div>

      <div v-if="filteredRooms.length === 0" class="col-span-full py-12 text-center text-gray-400 bg-white rounded-2xl border border-gray-100">
        Belum ada data ruangan yang cocok. Klik "+ Tambah Ruang Kelas" untuk mendaftarkan ruangan baru.
      </div>
    </div>

    <!-- Modal Form Ruang Kelas & Fasilitas -->
    <div
      v-if="isModalOpen"
      class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5"
    >
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4 animate-in fade-in zoom-in-95">
        <div class="flex items-center justify-between border-b pb-3">
          <div>
            <h3 class="text-sm font-bold text-slate-900">
              {{ isEditing ? `Edit Ruangan: ${roomForm.nama || '...'}` : 'Tambah Ruang Kelas / Fasilitas Baru' }}
            </h3>
            <p class="text-[11px] text-slate-500">
              {{ isEditing ? 'Perbarui informasi kode, jenis, kapasitas, atau kondisi ruangan.' : 'Daftarkan ruangan kelas atau fasilitas sekolah baru ke dalam sistem.' }}
            </p>
          </div>
          <button @click="isModalOpen = false" class="text-slate-400 hover:text-slate-700 font-bold p-1 cursor-pointer">✕</button>
        </div>

        <form @submit.prevent="submitRoomForm" class="space-y-4 text-xs">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Kode Ruangan *</label>
              <input
                v-model="roomForm.kode"
                type="text"
                required
                maxlength="15"
                placeholder="Contoh: R103 / LAB02"
                class="w-full border border-slate-200 rounded-xl p-2.5 text-xs font-mono font-bold uppercase focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Jenis Ruangan *</label>
              <select
                v-model="roomForm.jenis"
                required
                class="w-full border border-slate-200 rounded-xl p-2.5 text-xs bg-white font-bold text-slate-800 focus:ring-2 focus:ring-purple-500 outline-none"
              >
                <option value="ruang_kelas">Ruang Kelas Pembelajaran</option>
                <option value="lab">Laboratorium (Komputer/IPA)</option>
                <option value="perpustakaan">Perpustakaan</option>
                <option value="aula">Aula / Gedung Serbaguna</option>
                <option value="mushola">Mushola / Ibadah</option>
                <option value="lainnya">Fasilitas Lainnya</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Nama Ruangan / Fasilitas *</label>
            <input
              v-model="roomForm.nama"
              type="text"
              required
              maxlength="100"
              placeholder="Contoh: Ruang 3 Ali bin Abi Thalib"
              class="w-full border border-slate-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-purple-500 outline-none"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Gedung & Lantai</label>
              <input
                v-model="roomForm.gedung_lantai"
                type="text"
                maxlength="50"
                placeholder="Contoh: Gedung A Lantai 1"
                class="w-full border border-slate-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Kapasitas (Orang / Siswa)</label>
              <input
                v-model.number="roomForm.kapasitas"
                type="number"
                min="1"
                placeholder="Contoh: 28"
                class="w-full border border-slate-200 rounded-xl p-2.5 text-xs font-mono font-bold focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Kondisi Ruangan</label>
            <select
              v-model="roomForm.kondisi"
              class="w-full border border-slate-200 rounded-xl p-2.5 text-xs bg-white font-bold text-slate-800 focus:ring-2 focus:ring-purple-500 outline-none"
            >
              <option value="baik">Baik (Siap Digunakan)</option>
              <option value="rusak_ringan">Rusak Ringan (Perlu Perawatan)</option>
              <option value="rusak_berat">Rusak Berat (Tidak Layak)</option>
            </select>
          </div>

          <div class="pt-3 border-t flex items-center justify-end gap-2">
            <button
              @click="isModalOpen = false"
              type="button"
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="savingRoom"
              class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            >
              <svg v-if="savingRoom" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span>{{ savingRoom ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Buat Ruangan') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '../../services/api';
import { confirmDeleteWithKeyword, showSuccess, showError } from '../../services/swal';

const rooms = ref<any[]>([]);
const loading = ref(false);
const searchRoom = ref('');
const filterRoomJenis = ref('');
const filterRoomKondisi = ref('');

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);
const savingRoom = ref(false);

const roomForm = ref({
  kode: '',
  nama: '',
  jenis: 'ruang_kelas',
  gedung_lantai: '',
  kapasitas: 28,
  kondisi: 'baik',
});

const fetchRooms = async () => {
  loading.value = true;
  try {
    const res = await api.get('/rooms');
    if (res.data.success) {
      rooms.value = res.data.data || [];
    }
  } catch (err) {
    console.error('Gagal mengambil data ruangan:', err);
  } finally {
    loading.value = false;
  }
};

const filteredRooms = computed(() => {
  return rooms.value.filter((r) => {
    if (searchRoom.value.trim()) {
      const q = searchRoom.value.toLowerCase();
      const matchName = r.nama?.toLowerCase().includes(q);
      const matchCode = r.kode?.toLowerCase().includes(q);
      const matchGedung = r.gedung_lantai?.toLowerCase().includes(q);
      if (!matchName && !matchCode && !matchGedung) return false;
    }
    if (filterRoomJenis.value && r.jenis !== filterRoomJenis.value) return false;
    if (filterRoomKondisi.value && r.kondisi !== filterRoomKondisi.value) return false;
    return true;
  });
});

const openCreateModal = () => {
  isEditing.value = false;
  editingId.value = null;
  roomForm.value = {
    kode: '',
    nama: '',
    jenis: 'ruang_kelas',
    gedung_lantai: '',
    kapasitas: 28,
    kondisi: 'baik',
  };
  isModalOpen.value = true;
};

const openEditModal = (r: any) => {
  isEditing.value = true;
  editingId.value = r.id;
  roomForm.value = {
    kode: r.kode || '',
    nama: r.nama || '',
    jenis: r.jenis || 'ruang_kelas',
    gedung_lantai: r.gedung_lantai || '',
    kapasitas: r.kapasitas ?? 28,
    kondisi: r.kondisi || 'baik',
  };
  isModalOpen.value = true;
};

const submitRoomForm = async () => {
  savingRoom.value = true;
  try {
    const payload = {
      kode: roomForm.value.kode?.trim().toUpperCase(),
      nama: roomForm.value.nama?.trim(),
      jenis: roomForm.value.jenis,
      gedung_lantai: roomForm.value.gedung_lantai?.trim() || null,
      kapasitas: roomForm.value.kapasitas ? Number(roomForm.value.kapasitas) : 28,
      kondisi: roomForm.value.kondisi || 'baik',
    };

    if (isEditing.value && editingId.value) {
      const res = await api.put(`/rooms/${editingId.value}`, payload);
      if (res.data.success) {
        showSuccess('Berhasil!', res.data.message || 'Data ruangan berhasil diperbarui.');
        isModalOpen.value = false;
        fetchRooms();
      }
    } else {
      const res = await api.post('/rooms', payload);
      if (res.data.success) {
        showSuccess('Berhasil!', res.data.message || 'Ruangan baru berhasil ditambahkan.');
        isModalOpen.value = false;
        fetchRooms();
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err, 'Periksa kelengkapan kode dan nama ruangan.');
  } finally {
    savingRoom.value = false;
  }
};

const handleDeleteRoom = async (r: any) => {
  const confirmed = await confirmDeleteWithKeyword(`ruangan "${r.nama}" (${r.kode})`);
  if (!confirmed) return;

  try {
    const res = await api.delete(`/rooms/${r.id}`);
    if (res.data.success) {
      showSuccess('Terhapus!', 'Ruangan berhasil dihapus.');
      fetchRooms();
    }
  } catch (err: any) {
    showError('Gagal Menghapus!', err, 'Ruangan masih digunakan.');
  }
};

defineExpose({
  fetchRooms,
  openCreateModal,
  openCreateRoomModal: openCreateModal,
});

onMounted(() => {
  fetchRooms();
});
</script>
