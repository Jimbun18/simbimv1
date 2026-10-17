<template>
  <div class="space-y-5 sm:space-y-6">
    <!-- Header Greeting Banner -->
    <div class="bg-gradient-to-r from-emerald-800 via-emerald-700 to-teal-800 rounded-2xl p-4 sm:p-6 text-white shadow-xl relative overflow-hidden">
      <div class="relative z-10 max-w-2xl">
        <span class="inline-block px-3 py-1 bg-white/10 backdrop-blur-md rounded-full text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-emerald-100 mb-2 border border-white/20">
          Dashboard Eksekutif
        </span>
        <h1 class="text-xl sm:text-2xl md:text-3xl font-black tracking-tight leading-tight">
          Selamat Datang, {{ auth.userName }}!
        </h1>
        <p class="text-emerald-100 text-xs sm:text-sm mt-1 leading-relaxed">
          Pantau seluruh aktivitas operasional, kehadiran, kesiswaan, keuangan, dan inventaris SDI Bina Insan Mandiri Purwokerto secara *realtime*.
        </p>
        <div class="mt-4 flex flex-wrap gap-2 sm:gap-3 text-[11px] sm:text-xs font-medium text-emerald-100">
          <span class="flex items-center gap-1.5 bg-black/20 backdrop-blur-xs px-3 py-1.5 rounded-xl border border-white/10">
            {{ data?.hari }}, {{ formatDate(data?.tanggal) }}
          </span>
          <span class="flex items-center gap-1.5 bg-black/20 backdrop-blur-xs px-3 py-1.5 rounded-xl border border-white/10">
            Update: {{ lastUpdated }}
          </span>
        </div>
      </div>
      <div class="absolute -right-8 -bottom-10 opacity-10 pointer-events-none hidden sm:block">
        <svg class="w-64 h-64 text-white" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM3.45 9L12 4.34 20.55 9 12 13.66 3.45 9zM5 12.06v4.67l7 3.82 7-3.82v-4.67L12 15.88l-7-3.82z" />
        </svg>
      </div>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 animate-pulse">
      <div v-for="i in 4" :key="i" class="h-28 bg-gray-200 rounded-2xl"></div>
    </div>

    <!-- Section 1: KPI Metrics Cards (1 col on mobile, 2 on tablet, 4 on desktop) -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
      <!-- Siswa Aktif -->
      <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition">
        <div class="flex items-center justify-between text-gray-500 mb-2">
          <span class="text-xs font-semibold uppercase tracking-wider">Siswa Aktif</span>
        </div>
        <div class="text-2xl sm:text-3xl font-black text-gray-900">{{ data?.akademik?.total_siswa ?? 0 }}</div>
        <div class="mt-2 text-xs text-gray-500 flex items-center justify-between border-t border-gray-50 pt-2">
          <span>{{ data?.akademik?.siswa_laki ?? 0 }} Laki-laki</span>
          <span>{{ data?.akademik?.siswa_perempuan ?? 0 }} Perempuan</span>
        </div>
      </div>

      <!-- Guru & Tendik -->
      <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition">
        <div class="flex items-center justify-between text-gray-500 mb-2">
          <span class="text-xs font-semibold uppercase tracking-wider">Guru & Tendik</span>
        </div>
        <div class="text-2xl sm:text-3xl font-black text-gray-900">
          {{ (data?.akademik?.total_guru ?? 0) + (data?.akademik?.total_tendik ?? 0) }}
        </div>
        <div class="mt-2 text-xs text-gray-500 flex items-center justify-between border-t border-gray-50 pt-2">
          <span>Guru: {{ data?.akademik?.total_guru ?? 0 }}</span>
          <span>Tendik: {{ data?.akademik?.total_tendik ?? 0 }}</span>
        </div>
      </div>

      <!-- Kehadiran Guru Hari Ini -->
      <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition">
        <div class="flex items-center justify-between text-gray-500 mb-2">
          <span class="text-xs font-semibold uppercase tracking-wider">Presensi Guru</span>
        </div>
        <div class="flex items-baseline gap-2">
          <span class="text-2xl sm:text-3xl font-black text-gray-900">{{ data?.kehadiran_hari_ini?.guru?.persentase_kehadiran ?? 0 }}%</span>
          <span class="text-xs text-gray-500 font-medium">({{ data?.kehadiran_hari_ini?.guru?.hadir ?? 0 }}/{{ data?.kehadiran_hari_ini?.guru?.total ?? 0 }})</span>
        </div>
        <div class="mt-2 text-xs text-emerald-600 font-medium border-t border-gray-50 pt-2 flex justify-between">
          <span>Tepat Waktu: {{ data?.kehadiran_hari_ini?.guru?.tepat_waktu ?? 0 }}</span>
          <span class="text-amber-600">Terlambat: {{ data?.kehadiran_hari_ini?.guru?.terlambat ?? 0 }}</span>
        </div>
      </div>

      <!-- Kepatuhan Bukti Mengajar -->
      <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition">
        <div class="flex items-center justify-between text-gray-500 mb-2">
          <span class="text-xs font-semibold uppercase tracking-wider">Bukti KBM</span>
        </div>
        <div class="flex items-baseline gap-2">
          <span class="text-2xl sm:text-3xl font-black text-gray-900">{{ data?.kehadiran_hari_ini?.bukti_mengajar?.persentase_kepatuhan ?? 0 }}%</span>
          <span class="text-xs text-gray-500 font-medium">({{ data?.kehadiran_hari_ini?.bukti_mengajar?.sudah_upload ?? 0 }} sesi)</span>
        </div>
        <div class="mt-2 text-xs text-gray-500 border-t border-gray-50 pt-2">
          Total Sesi Jadwal Hari Ini: {{ data?.kehadiran_hari_ini?.bukti_mengajar?.total_sesi ?? 0 }}
        </div>
      </div>
    </div>

    <!-- Section 2: Keuangan & Presensi Siswa (1 col on mobile, 2 col on tablet/desktop) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 sm:gap-6">
      <!-- Card Keuangan Bulan Berjalan -->
      <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
            <h2 class="text-sm sm:text-base font-bold text-gray-900 flex items-center gap-2">
              Realisasi Keuangan Bulan Ini
            </h2>
            <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
              {{ data?.keuangan_bulan_ini?.periode }}
            </span>
          </div>

          <div class="space-y-4">
            <div>
              <div class="flex justify-between text-xs font-semibold mb-1.5">
                <span class="text-gray-600">Penerimaan Tagihan SPP</span>
                <span class="text-emerald-700 font-bold">{{ data?.keuangan_bulan_ini?.persentase_tercapai ?? 0 }}% Tercapai</span>
              </div>
              <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                <div
                  class="bg-emerald-500 h-2.5 rounded-full transition-all duration-500"
                  :style="{ width: `${data?.keuangan_bulan_ini?.persentase_tercapai ?? 0}%` }"
                ></div>
              </div>
              <div class="flex flex-col sm:flex-row justify-between text-[11px] sm:text-xs text-gray-500 mt-1.5 gap-1">
                <span>Terbayar: <strong>Rp {{ formatRupiah(data?.keuangan_bulan_ini?.total_terbayar) }}</strong></span>
                <span>Target: <strong>Rp {{ formatRupiah(data?.keuangan_bulan_ini?.total_tagihan) }}</strong></span>
              </div>
            </div>

            <!-- Stacks cleanly on mobile, 3 columns on tablet/desktop -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-2">
              <div class="p-3 bg-emerald-50/70 rounded-xl border border-emerald-100">
                <div class="text-[11px] text-emerald-800 font-medium">Kas Masuk</div>
                <div class="text-sm sm:text-base font-bold text-emerald-900 mt-0.5">Rp {{ formatRupiah(data?.keuangan_bulan_ini?.kas_masuk) }}</div>
              </div>
              <div class="p-3 bg-rose-50/70 rounded-xl border border-rose-100">
                <div class="text-[11px] text-rose-800 font-medium">Kas Keluar</div>
                <div class="text-sm sm:text-base font-bold text-rose-900 mt-0.5">Rp {{ formatRupiah(data?.keuangan_bulan_ini?.kas_keluar) }}</div>
              </div>
              <div class="p-3 bg-sky-50/70 rounded-xl border border-sky-100">
                <div class="text-[11px] text-sky-800 font-medium">Surplus/Saldo</div>
                <div class="text-sm sm:text-base font-bold text-sky-900 mt-0.5">Rp {{ formatRupiah(data?.keuangan_bulan_ini?.saldo_kas) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card Presensi Siswa & Sarpras -->
      <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
            <h2 class="text-sm sm:text-base font-bold text-gray-900 flex items-center gap-2">
              Rekap Presensi Siswa Hari Ini
            </h2>
            <span class="text-xs font-semibold text-gray-500">
              {{ data?.kehadiran_hari_ini?.siswa?.total_tercatat ?? 0 }} Data Terinput
            </span>
          </div>

          <!-- Responsive 2 or 4 cols -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center mb-5">
            <div class="p-2.5 bg-emerald-50 rounded-xl border border-emerald-100">
              <div class="text-xl sm:text-2xl font-black text-emerald-700">{{ data?.kehadiran_hari_ini?.siswa?.hadir ?? 0 }}</div>
              <div class="text-[11px] font-semibold text-emerald-800 uppercase mt-0.5">Hadir</div>
            </div>
            <div class="p-2.5 bg-amber-50 rounded-xl border border-amber-100">
              <div class="text-xl sm:text-2xl font-black text-amber-700">{{ data?.kehadiran_hari_ini?.siswa?.sakit ?? 0 }}</div>
              <div class="text-[11px] font-semibold text-amber-800 uppercase mt-0.5">Sakit</div>
            </div>
            <div class="p-2.5 bg-sky-50 rounded-xl border border-sky-100">
              <div class="text-xl sm:text-2xl font-black text-sky-700">{{ data?.kehadiran_hari_ini?.siswa?.izin ?? 0 }}</div>
              <div class="text-[11px] font-semibold text-sky-800 uppercase mt-0.5">Izin</div>
            </div>
            <div class="p-2.5 bg-rose-50 rounded-xl border border-rose-100">
              <div class="text-xl sm:text-2xl font-black text-rose-700">{{ data?.kehadiran_hari_ini?.siswa?.alpa ?? 0 }}</div>
              <div class="text-[11px] font-semibold text-rose-800 uppercase mt-0.5">Alpa</div>
            </div>
          </div>
        </div>

        <!-- Sarpras info -->
        <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200/80 flex flex-col sm:flex-row items-start sm:items-center justify-between text-xs gap-2">
          <div>
            <span class="font-bold text-gray-800">Inventaris Sarpras:</span>
            <span class="text-gray-600 ml-1">{{ data?.sarpras?.total_aset ?? 0 }} Aset (Rp {{ formatRupiah(data?.sarpras?.total_nilai) }})</span>
          </div>
          <div class="flex gap-2">
            <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded-md font-medium text-[11px]">Rusak: {{ (data?.sarpras?.rusak_ringan ?? 0) + (data?.sarpras?.rusak_berat ?? 0) }}</span>
            <span class="px-2 py-0.5 bg-sky-100 text-sky-800 rounded-md font-medium text-[11px]">Dipinjam: {{ data?.sarpras?.sedang_dipinjam ?? 0 }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Live Feed: 10 Aktivitas Log Terkini -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-6">
      <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
        <h2 class="text-sm sm:text-base font-bold text-gray-900 flex items-center gap-2">
          Log Aktivitas Terkini (Jejak Audit)
        </h2>
        <span class="text-xs text-gray-500 font-medium">10 Tindakan Terakhir</span>
      </div>

      <div class="divide-y divide-gray-100">
        <div
          v-for="log in data?.aktivitas_terbaru"
          :key="log.id"
          class="py-3 flex flex-col sm:flex-row items-start sm:items-center justify-between text-xs hover:bg-gray-50/70 rounded-lg px-2 transition gap-1 sm:gap-4"
        >
          <div class="space-y-0.5 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="font-bold text-gray-900">{{ log.user }}</span>
              <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded-md font-mono text-[10px]">{{ log.modul }}.{{ log.aksi }}</span>
            </div>
            <p class="text-gray-600 truncate max-w-xl">{{ log.keterangan }}</p>
          </div>
          <span class="text-gray-400 whitespace-nowrap text-[11px] self-end sm:self-center">{{ log.waktu }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import api from '../services/api';

const auth = useAuthStore();
const data = ref<any>(null);
const loading = ref(true);
const lastUpdated = ref('');

const formatRupiah = (val: number | undefined) => {
  if (val === undefined || val === null) return '0';
  return new Intl.NumberFormat('id-ID').format(Math.round(val));
};

const formatDate = (dateStr: string | undefined) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const fetchDashboard = async () => {
  loading.value = true;
  try {
    const res = await api.get('/dashboard/summary');
    if (res.data.success) {
      data.value = res.data.data;
      lastUpdated.value = new Date().toLocaleTimeString('id-ID');
    }
  } catch (err) {
    console.error('Gagal mengambil data dashboard', err);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchDashboard();
});
</script>
