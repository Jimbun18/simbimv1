<template>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="p-4 border-b border-gray-100 flex items-center justify-between">
      <div>
        <h2 class="text-sm font-bold text-gray-900">Alokasi Waktu Sesi Pembelajaran (Time Slots)</h2>
        <p class="text-xs text-gray-500 mt-0.5">Daftar sesi belajar dan istirahat yang berlaku pada jadwal KBM mingguan.</p>
      </div>
      <button
        @click="fetchSlots"
        class="p-2 rounded-xl bg-gray-50 hover:bg-gray-100 text-gray-600 transition cursor-pointer"
        title="Muat Ulang Time Slots"
      >
        <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="py-12 text-center text-gray-400">
      <div class="inline-block animate-spin w-6 h-6 border-2 border-purple-600 border-t-transparent rounded-full mb-2"></div>
      <p class="text-xs font-medium text-gray-600">Memuat alokasi waktu sesi...</p>
    </div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-left text-xs text-gray-600 min-w-[550px]">
        <thead class="bg-gray-50 text-[11px] font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
          <tr>
            <th class="py-3 px-4">Urutan</th>
            <th class="py-3 px-4">Hari</th>
            <th class="py-3 px-4">Nama Sesi</th>
            <th class="py-3 px-4">Jenis</th>
            <th class="py-3 px-4">Jam Mulai</th>
            <th class="py-3 px-4">Jam Selesai</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="ts in timeSlots" :key="ts.id" class="hover:bg-gray-50/70 transition">
            <td class="py-2.5 px-4 font-mono font-bold">{{ ts.urutan }}</td>
            <td class="py-2.5 px-4 capitalize font-semibold">{{ ts.hari }}</td>
            <td class="py-2.5 px-4 font-bold text-gray-900">{{ ts.nama }}</td>
            <td class="py-2.5 px-4">
              <span :class="ts.jenis === 'belajar' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase">
                {{ ts.jenis }}
              </span>
            </td>
            <td class="py-2.5 px-4 font-mono">{{ ts.jam_mulai }}</td>
            <td class="py-2.5 px-4 font-mono">{{ ts.jam_selesai }}</td>
          </tr>
          <tr v-if="timeSlots.length === 0">
            <td colspan="6" class="py-12 text-center text-gray-400">
              Belum ada data time slot terdaftar.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../services/api';

const timeSlots = ref<any[]>([]);
const loading = ref(false);

const fetchSlots = async () => {
  loading.value = true;
  try {
    const res = await api.get('/time-slots');
    if (res.data.success) {
      timeSlots.value = res.data.data || [];
    }
  } catch (err) {
    console.error('Gagal mengambil data time slot:', err);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchSlots();
});
</script>
