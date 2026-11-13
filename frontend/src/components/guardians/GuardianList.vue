<template>
  <div class="space-y-5 sm:space-y-6">
    <!-- 1. Header & Summary Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 sm:p-5 rounded-2xl border border-slate-100 shadow-xs">
      <div class="flex items-center gap-2.5">
        <h1 class="text-lg sm:text-xl font-bold text-slate-900 leading-tight">Wali Murid</h1>
        <span v-if="meta.total > 0" class="px-2.5 py-0.5 text-xs font-semibold bg-emerald-50 text-emerald-700 rounded-full border border-emerald-200">
          {{ meta.total }} Orang
        </span>
        <InfoTooltip text="Master orang tua/wali siswa, akun login portal wali murid, serta relasi anak bersaudara (kakak-adik)." />
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          @click="$emit('open-create')"
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer active:scale-95"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Tambah Wali</span>
        </button>
      </div>
    </div>

    <!-- 2. Stat Highlights (3 Kartu Ringkasan) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
      <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs flex items-center justify-between">
        <div>
          <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
            <span>Total Wali Murid</span>
            <InfoTooltip size="xs" text="Total wali murid yang terdaftar di basis data sekolah." />
          </div>
          <div class="text-xl font-black text-slate-900 mt-0.5">
            {{ stats.total_guardians || meta.total || guardians.length }} Orang
          </div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0">
          👨‍👩‍👧‍👦
        </div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs flex items-center justify-between">
        <div>
          <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
            <span>Akun Portal Aktif</span>
            <InfoTooltip size="xs" text="Wali murid yang sudah memiliki username & password login portal." />
          </div>
          <div class="text-xl font-black text-slate-900 mt-0.5">
            {{ stats.active_accounts }} Akun
          </div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg shrink-0">
          📱
        </div>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs flex items-center justify-between">
        <div>
          <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
            <span>Siswa Terhubung</span>
            <InfoTooltip size="xs" text="Jumlah siswa yang sudah ditautkan dengan akun orang tua." />
          </div>
          <div class="text-xl font-black text-slate-900 mt-0.5">
            {{ stats.connected_students || totalConnectedStudents }} Siswa
          </div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shrink-0">
          🎒
        </div>
      </div>
    </div>

    <!-- 3. Toolbar & Filter -->
    <div class="bg-white p-3.5 rounded-2xl border border-slate-100 shadow-xs flex flex-col md:flex-row gap-2.5 items-center justify-between">
      <div class="flex-1 w-full md:w-auto relative">
        <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          :value="search"
          @input="$emit('update:search', ($event.target as HTMLInputElement).value)"
          type="text"
          placeholder="Cari nama wali, NIK, siswa, atau WhatsApp..."
          class="w-full pl-9 pr-4 py-2 bg-slate-50 text-xs rounded-xl border border-slate-200 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none transition"
        />
      </div>

      <div class="flex flex-wrap gap-2 w-full md:w-auto">
        <select
          :value="filterHubungan"
          @change="$emit('update:filterHubungan', ($event.target as HTMLSelectElement).value)"
          class="px-3 py-2 bg-slate-50 text-xs rounded-xl border border-slate-200 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-slate-700"
        >
          <option value="">Semua Hubungan</option>
          <option value="ayah">Ayah</option>
          <option value="ibu">Ibu</option>
          <option value="wali">Wali</option>
        </select>

        <select
          :value="filterAkun"
          @change="$emit('update:filterAkun', ($event.target as HTMLSelectElement).value)"
          class="px-3 py-2 bg-slate-50 text-xs rounded-xl border border-slate-200 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-slate-700"
        >
          <option value="">Semua Status Akun</option>
          <option value="has_account">Sudah Punya Akun</option>
          <option value="no_account">Belum Ada Akun</option>
        </select>
      </div>
    </div>

    <!-- 4. Table Wali Murid Minimalis -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600 min-w-[760px]">
          <thead class="bg-slate-50/80 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
            <tr>
              <th class="py-3 px-3.5 w-10 text-center">No</th>
              <th class="py-3 px-4">
                <div class="inline-flex items-center gap-1.5">
                  <span>Wali Murid</span>
                  <InfoTooltip size="xs" text="Nama orang tua/wali dan hubungan keluarga." />
                </div>
              </th>
              <th class="py-3 px-4">Kontak & NIK</th>
              <th class="py-3 px-4">Akun Portal</th>
              <th class="py-3 px-4">Anak (Kakak-Adik)</th>
              <th class="py-3 px-4 text-right w-20">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="loading">
              <td colspan="6" class="py-10 text-center text-slate-400">
                <div class="inline-block animate-spin w-5 h-5 border-2 border-emerald-600 border-t-transparent rounded-full mb-1"></div>
                <div>Memuat data wali murid...</div>
              </td>
            </tr>

            <tr v-else-if="guardians.length === 0">
              <td colspan="6" class="py-10 text-center text-slate-400">
                Belum ada data wali murid yang sesuai dengan filter pencarian.
              </td>
            </tr>

            <tr v-for="(g, idx) in guardians" :key="g.id" class="hover:bg-slate-50/70 transition">
              <td class="py-2.5 px-3.5 text-slate-400 font-mono text-center text-[11px]">{{ idx + 1 }}</td>
              
              <!-- Nama & Hubungan -->
              <td class="py-2.5 px-4 whitespace-nowrap">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-xs shrink-0">
                    {{ g.nama_lengkap.charAt(0) }}
                  </div>
                  <div>
                    <div class="font-semibold text-slate-900 leading-tight">{{ g.nama_lengkap }}</div>
                    <span 
                      class="inline-block mt-0.5 px-1.5 py-0.2 rounded-full text-[9px] font-bold uppercase tracking-wider"
                      :class="g.hubungan === 'ayah' ? 'bg-sky-50 text-sky-700 border border-sky-200' : (g.hubungan === 'ibu' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200')"
                    >
                      {{ g.hubungan }}
                    </span>
                  </div>
                </div>
              </td>

              <!-- Kontak & NIK -->
              <td class="py-2.5 px-4 whitespace-nowrap">
                <div class="font-mono text-slate-800 text-[11px] font-medium flex items-center gap-1.5">
                  <span class="text-emerald-600 font-bold text-[10px]">WA:</span> {{ g.no_whatsapp || g.no_hp || '-' }}
                </div>
                <div class="font-mono text-[10px] text-slate-400">NIK: {{ g.nik || '-' }}</div>
              </td>

              <!-- Akun Portal SIM-BIM -->
              <td class="py-2.5 px-4 whitespace-nowrap">
                <div v-if="g.user" class="space-y-0.5">
                  <div class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span class="font-mono font-semibold text-slate-800 text-[11px]">@{{ g.user.username }}</span>
                  </div>
                  <button
                    @click="$emit('reset-password', g)"
                    class="text-[10px] text-sky-600 hover:text-sky-800 hover:underline block cursor-pointer"
                  >
                    Reset Password
                  </button>
                </div>
                <div v-else>
                  <button
                    @click="$emit('create-account', g)"
                    class="px-2 py-0.5 bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 rounded-lg text-[10px] font-bold transition cursor-pointer"
                  >
                    + Buat Akun
                  </button>
                </div>
              </td>

              <!-- Anak Tanggungan (Kakak-Adik) -->
              <td class="py-2.5 px-4">
                <div class="space-y-1">
                  <div v-if="g.students && g.students.length > 0" class="flex flex-wrap gap-1">
                    <button
                      v-for="s in g.students"
                      :key="s.id"
                      type="button"
                      @click="$emit('go-to-student', s)"
                      class="inline-flex items-center gap-1 px-2 py-0.5 bg-slate-50 hover:bg-emerald-50 text-slate-800 hover:text-emerald-900 border border-slate-200 hover:border-emerald-200 rounded-lg text-[10px] font-medium transition cursor-pointer"
                      :title="`Buka profil data siswa: ${s.nama_lengkap}`"
                    >
                      <span class="font-semibold">{{ s.nama_lengkap }}</span>
                      <span class="text-slate-400 font-mono text-[9px]">({{ s.nis }})</span>
                    </button>
                  </div>
                  <div v-else class="text-slate-400 text-[10px] italic">
                    Belum terhubung ke siswa
                  </div>
                  <button
                    @click="$emit('manage-students', g)"
                    class="text-[9px] font-bold text-emerald-700 hover:text-emerald-900 bg-white hover:bg-emerald-50 border border-emerald-200 rounded-md px-1.5 py-0.5 transition cursor-pointer inline-flex items-center gap-1"
                  >
                    <span>🔗 Hubungkan Anak</span>
                  </button>
                </div>
              </td>

              <!-- Aksi Icon Buttons Minimalis -->
              <td class="py-2.5 px-4 text-right whitespace-nowrap">
                <div class="inline-flex items-center gap-1 justify-end">
                  <button
                    @click="$emit('edit', g)"
                    class="p-1.5 text-slate-500 hover:text-amber-700 hover:bg-amber-50 rounded-lg transition cursor-pointer"
                    title="Edit Biodata Wali"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button
                    @click="$emit('delete', g)"
                    class="p-1.5 text-slate-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition cursor-pointer"
                    title="Hapus Wali Murid"
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

      <!-- 5. Footer Pagination & Baris Data -->
      <div class="px-5 py-3.5 bg-slate-50/80 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <div class="text-slate-500">
          Menampilkan <strong class="text-slate-800">{{ meta.total > 0 ? (meta.current_page - 1) * meta.per_page + 1 : 0 }} - {{ Math.min(meta.current_page * meta.per_page, meta.total) }}</strong> dari <strong class="text-slate-800">{{ meta.total }}</strong> wali murid
        </div>

        <div class="flex items-center gap-3">
          <div class="flex items-center gap-1.5 text-slate-500">
            <span>Baris per halaman:</span>
            <select
              :value="perPage"
              @change="$emit('update:perPage', Number(($event.target as HTMLSelectElement).value))"
              class="px-2 py-1 bg-white border border-slate-200 rounded-lg text-xs outline-none focus:ring-1 focus:ring-emerald-500"
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
              @click="$emit('fetch', meta.current_page - 1)"
              class="px-2.5 py-1 bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-40 disabled:hover:bg-white rounded-lg transition font-medium cursor-pointer"
            >
              Prev
            </button>
            <span class="px-2 font-mono font-bold text-slate-700">
              {{ meta.current_page }} / {{ meta.last_page || 1 }}
            </span>
            <button
              :disabled="meta.current_page >= meta.last_page"
              @click="$emit('fetch', meta.current_page + 1)"
              class="px-2.5 py-1 bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-40 disabled:hover:bg-white rounded-lg transition font-medium cursor-pointer"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Guardian, GuardianStats, GuardianMeta, LinkedStudent } from './types';
import InfoTooltip from '../common/InfoTooltip.vue';

defineProps<{
  guardians: Guardian[];
  loading: boolean;
  stats: GuardianStats;
  meta: GuardianMeta;
  totalConnectedStudents: number;
  search: string;
  filterHubungan: string;
  filterAkun: string;
  perPage: number;
}>();

defineEmits<{
  (e: 'update:search', val: string): void;
  (e: 'update:filterHubungan', val: string): void;
  (e: 'update:filterAkun', val: string): void;
  (e: 'update:perPage', val: number): void;
  (e: 'open-create'): void;
  (e: 'edit', guardian: Guardian): void;
  (e: 'delete', guardian: Guardian): void;
  (e: 'create-account', guardian: Guardian): void;
  (e: 'reset-password', guardian: Guardian): void;
  (e: 'manage-students', guardian: Guardian): void;
  (e: 'go-to-student', student: LinkedStudent): void;
  (e: 'fetch', page: number): void;
}>();
</script>
