<template>
  <div class="space-y-5 sm:space-y-6">
    <!-- 1. Header & Summary Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
      <div>
        <h1 class="text-lg sm:text-xl font-bold text-gray-900 leading-tight">Manajemen Wali Murid & Akun Portal</h1>
        <p class="text-xs text-gray-500 mt-0.5">Kelola data orang tua/wali siswa, akun login portal wali murid, serta relasi saudara kandung (kakak-adik).</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          @click="$emit('open-create')"
          class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/20 transition cursor-pointer active:scale-95"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Tambah Wali Murid</span>
        </button>
      </div>
    </div>

    <!-- 2. Stat Highlights (3 Kartu Ringkasan Riil) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
          👨‍👩‍👧‍👦
        </div>
        <div>
          <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Total Wali Murid</div>
          <div class="text-xl font-black text-gray-900 mt-0.5">
            {{ stats.total_guardians || meta.total || guardians.length }} Orang
          </div>
        </div>
      </div>

      <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shrink-0">
          📱
        </div>
        <div>
          <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Akun Portal Aktif</div>
          <div class="text-xl font-black text-gray-900 mt-0.5">
            {{ stats.active_accounts }} Akun
          </div>
        </div>
      </div>

      <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
          🎒
        </div>
        <div>
          <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Siswa Terhubung</div>
          <div class="text-xl font-black text-gray-900 mt-0.5">
            {{ stats.connected_students || totalConnectedStudents }} Siswa
          </div>
        </div>
      </div>
    </div>

    <!-- 3. Toolbar & Filter -->
    <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex flex-col md:flex-row gap-3 items-center justify-between">
      <div class="flex-1 w-full md:w-auto relative">
        <input
          :value="search"
          @input="$emit('update:search', ($event.target as HTMLInputElement).value)"
          type="text"
          placeholder="Cari berdasarkan nama wali, NIK, nama siswa/NIS, atau WhatsApp..."
          class="w-full px-4 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none transition"
        />
      </div>

      <div class="flex flex-wrap gap-2 w-full md:w-auto">
        <select
          :value="filterHubungan"
          @change="$emit('update:filterHubungan', ($event.target as HTMLSelectElement).value)"
          class="px-3 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-gray-700"
        >
          <option value="">Semua Hubungan</option>
          <option value="ayah">Ayah</option>
          <option value="ibu">Ibu</option>
          <option value="wali">Wali</option>
        </select>

        <select
          :value="filterAkun"
          @change="$emit('update:filterAkun', ($event.target as HTMLSelectElement).value)"
          class="px-3 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-gray-700"
        >
          <option value="">Semua Status Akun</option>
          <option value="has_account">Sudah Punya Akun</option>
          <option value="no_account">Belum Ada Akun</option>
        </select>
      </div>
    </div>

    <!-- 4. Table Wali Murid -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-gray-600 min-w-[850px]">
          <thead class="bg-gray-50 text-[11px] font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
            <tr>
              <th class="py-3.5 px-4 w-12 text-center">No</th>
              <th class="py-3.5 px-4">Nama Wali Murid</th>
              <th class="py-3.5 px-4">Kontak & NIK</th>
              <th class="py-3.5 px-4">Akun Portal SIM-BIM</th>
              <th class="py-3.5 px-4">Anak Tanggungan (Kakak-Adik)</th>
              <th class="py-3.5 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="loading">
              <td colspan="6" class="py-12 text-center text-gray-400">
                <div class="inline-block animate-spin w-6 h-6 border-2 border-emerald-600 border-t-transparent rounded-full mb-2"></div>
                <div>Memuat data wali murid...</div>
              </td>
            </tr>

            <tr v-else-if="guardians.length === 0">
              <td colspan="6" class="py-12 text-center text-gray-400">
                Belum ada data wali murid yang sesuai dengan filter pencarian.
              </td>
            </tr>

            <tr v-for="(g, idx) in guardians" :key="g.id" class="hover:bg-gray-50/70 transition">
              <td class="py-3.5 px-4 text-gray-400 font-mono text-center">{{ idx + 1 }}</td>
              
              <!-- Nama & Hubungan -->
              <td class="py-3.5 px-4">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-xs shrink-0">
                    {{ g.nama_lengkap.charAt(0) }}
                  </div>
                  <div>
                    <div class="font-bold text-gray-900">{{ g.nama_lengkap }}</div>
                    <span 
                      class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                      :class="g.hubungan === 'ayah' ? 'bg-blue-50 text-blue-700 border border-blue-200' : (g.hubungan === 'ibu' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200')"
                    >
                      {{ g.hubungan }}
                    </span>
                  </div>
                </div>
              </td>

              <!-- Kontak & NIK -->
              <td class="py-3.5 px-4">
                <div class="space-y-0.5">
                  <div class="font-mono text-gray-800 font-medium flex items-center gap-1.5">
                    <span class="text-emerald-600 font-bold">WA:</span> {{ g.no_whatsapp || g.no_hp || '-' }}
                  </div>
                  <div class="font-mono text-[11px] text-gray-400">NIK: {{ g.nik || '-' }}</div>
                  <div class="text-[11px] text-gray-500 truncate max-w-xs">{{ g.alamat_jalan || '-' }}</div>
                </div>
              </td>

              <!-- Akun Portal SIM-BIM -->
              <td class="py-3.5 px-4">
                <div v-if="g.user" class="space-y-1">
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="font-mono font-bold text-gray-800 text-[11px]">@{{ g.user.username }}</span>
                  </div>
                  <button
                    @click="$emit('reset-password', g)"
                    class="text-[10px] text-sky-600 hover:text-sky-800 underline block cursor-pointer"
                  >
                    Reset Password
                  </button>
                </div>
                <div v-else>
                  <button
                    @click="$emit('create-account', g)"
                    class="px-2.5 py-1 bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 rounded-lg text-[10px] font-bold transition cursor-pointer"
                  >
                    + Buat Akun
                  </button>
                </div>
              </td>

              <!-- Anak Tanggungan (Kakak-Adik) -->
              <td class="py-3.5 px-4">
                <div class="space-y-1.5">
                  <div v-if="g.students && g.students.length > 0" class="flex flex-wrap gap-1.5">
                    <button
                      v-for="s in g.students"
                      :key="s.id"
                      type="button"
                      @click="$emit('go-to-student', s)"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 hover:text-emerald-900 border border-emerald-200/80 hover:border-emerald-300 rounded-lg text-[11px] font-semibold transition cursor-pointer group shadow-2xs"
                      :title="`Klik untuk membuka profil data siswa: ${s.nama_lengkap} (${s.nis})`"
                    >
                      <span>🎒</span>
                      <span class="group-hover:underline font-bold">{{ s.nama_lengkap }}</span>
                      <span class="text-emerald-600 font-mono text-[10px]">({{ s.nis }})</span>
                      <svg class="w-3 h-3 text-emerald-600 transition-transform group-hover:translate-x-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                      </svg>
                    </button>
                  </div>
                  <div v-else class="text-gray-400 text-[11px] italic">
                    Belum terhubung ke siswa
                  </div>
                  <button
                    @click="$emit('manage-students', g)"
                    class="text-[10px] font-bold text-emerald-700 hover:text-emerald-900 bg-white hover:bg-emerald-50 border border-emerald-200 rounded-lg px-2 py-0.5 transition cursor-pointer flex items-center gap-1"
                  >
                    <span>🔗 Kelola Anak (Kakak-Adik)</span>
                  </button>
                </div>
              </td>

              <!-- Aksi -->
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="inline-flex items-center gap-1.5">
                  <button
                    @click="$emit('edit', g)"
                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-semibold transition cursor-pointer"
                    title="Edit Biodata Wali"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Edit</span>
                  </button>

                  <button
                    @click="$emit('delete', g)"
                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-semibold transition cursor-pointer"
                    title="Hapus Data Wali"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Hapus</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- 5. Footer Pagination & Baris Data -->
      <div class="px-5 py-3.5 bg-gray-50/80 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <div class="text-gray-500">
          Menampilkan <strong class="text-gray-800">{{ meta.total > 0 ? (meta.current_page - 1) * meta.per_page + 1 : 0 }} - {{ Math.min(meta.current_page * meta.per_page, meta.total) }}</strong> dari <strong class="text-gray-800">{{ meta.total }}</strong> wali murid
        </div>

        <div class="flex items-center gap-3">
          <div class="flex items-center gap-1.5 text-gray-500">
            <span>Baris per halaman:</span>
            <select
              :value="perPage"
              @change="$emit('update:perPage', Number(($event.target as HTMLSelectElement).value))"
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
              @click="$emit('fetch', meta.current_page - 1)"
              class="px-2.5 py-1 bg-white border border-gray-200 hover:bg-gray-100 disabled:opacity-40 disabled:hover:bg-white rounded-lg transition font-medium cursor-pointer"
            >
              Prev
            </button>
            <span class="px-2 font-mono font-bold text-gray-700">
              {{ meta.current_page }} / {{ meta.last_page || 1 }}
            </span>
            <button
              :disabled="meta.current_page >= meta.last_page"
              @click="$emit('fetch', meta.current_page + 1)"
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

<script setup lang="ts">
import type { Guardian, GuardianStats, GuardianMeta, LinkedStudent } from './types';

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
