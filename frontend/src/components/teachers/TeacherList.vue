<script setup lang="ts">
import type { Teacher } from './types'
import { getPhotoUrl, getInitials, calculateTenure, formatDateIndo } from './types'

const props = defineProps<{
  teachers: Teacher[]
  loading: boolean
  exporting: boolean
  search: string
  filterJenisPtk: string
}>()

const emit = defineEmits<{
  (e: 'update:search', val: string): void
  (e: 'update:filterJenisPtk', val: string): void
  (e: 'filter-change'): void
  (e: 'add'): void
  (e: 'edit', teacher: Teacher): void
  (e: 'detail', teacher: Teacher): void
  (e: 'delete', teacher: Teacher): void
  (e: 'export'): void
}>()

let debounceTimer: ReturnType<typeof setTimeout> | null = null
const onSearchInput = (val: string) => {
  emit('update:search', val)
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    emit('filter-change')
  }, 300)
}
</script>

<template>
  <div class="space-y-5 sm:space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
      <div>
        <h1 class="text-lg sm:text-xl font-bold text-gray-900 leading-tight">Manajemen Pendidik & Tenaga Kependidikan (PTK)</h1>
        <p class="text-xs text-gray-500 mt-0.5">Daftar Guru dan Staf Administrasi/Tendik SDI Bina Insan Mandiri Purwokerto.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          @click="emit('export')"
          :disabled="exporting"
          class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-xl border border-emerald-200 transition disabled:opacity-50 cursor-pointer"
          title="Export Data Guru & Tendik ke Excel"
        >
          <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <span v-if="exporting">Mengekspor...</span>
          <span v-else>Export Excel</span>
        </button>
        <router-link
          to="/excel-imports"
          class="inline-flex items-center gap-2 px-3.5 py-2 bg-sky-50 hover:bg-sky-100 text-sky-700 text-xs font-semibold rounded-xl border border-sky-200 transition"
        >
          Import Excel Guru
        </router-link>
        <button
          @click="emit('add')"
          class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-md shadow-sky-600/20 transition active:scale-95 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Tambah Guru / Tendik</span>
        </button>
      </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex flex-col md:flex-row gap-3 items-center justify-between">
      <div class="flex-1 w-full md:w-auto relative">
        <input
          :value="search"
          @input="(e: any) => onSearchInput(e.target.value)"
          type="text"
          placeholder="Cari berdasarkan NIP, NIK, atau Nama Guru..."
          class="w-full px-4 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-sky-500 outline-hidden transition"
        />
      </div>

      <div class="flex gap-2 w-full md:w-auto">
        <select
          :value="filterJenisPtk"
          @change="(e: any) => { emit('update:filterJenisPtk', e.target.value); emit('filter-change'); }"
          class="px-3 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-sky-500 outline-hidden text-gray-700"
        >
          <option value="">Semua Jenis PTK</option>
          <option value="guru_kelas">Guru Kelas</option>
          <option value="guru_mapel">Guru Mapel</option>
          <option value="guru_agama">Guru Agama / Tahfidz</option>
          <option value="guru_olahraga">Guru Olahraga</option>
          <option value="kepala_sekolah">Kepala Sekolah</option>
        </select>
      </div>
    </div>

    <!-- Table Guru -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-gray-600 min-w-[950px]">
          <thead class="bg-gray-50 text-[11px] font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
            <tr>
              <th class="py-3.5 px-4 w-12 text-center">No</th>
              <th class="py-3.5 px-4 w-14 text-center">Foto</th>
              <th class="py-3.5 px-4">NIP / NUPTK</th>
              <th class="py-3.5 px-4">Nama Lengkap & Gelar</th>
              <th class="py-3.5 px-4">Jabatan & Tugas Utama</th>
              <th class="py-3.5 px-4">Penugasan / Mengajar</th>
              <th class="py-3.5 px-4">Jenis PTK</th>
              <th class="py-3.5 px-4">Status & Masa Kerja</th>
              <th class="py-3.5 px-4">No. HP / WA</th>
              <th class="py-3.5 px-4 text-center">Status</th>
              <th class="py-3.5 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="loading">
              <td colspan="11" class="text-center py-10 text-gray-400">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-3 border-sky-500 border-t-transparent mb-2"></div>
                <p>Memuat data guru & tenaga kependidikan...</p>
              </td>
            </tr>
            <tr v-else-if="teachers.length === 0">
              <td colspan="11" class="text-center py-12 text-gray-400">
                Tidak ada data guru yang sesuai dengan pencarian / filter.
              </td>
            </tr>
            <tr v-for="(t, idx) in teachers" :key="t.id" class="hover:bg-gray-50/70 transition">
              <td class="py-3 px-4 text-gray-400 font-mono text-center">{{ idx + 1 }}</td>
              <td class="py-2.5 px-4 text-center">
                <div class="w-9 h-9 rounded-full overflow-hidden bg-gray-100 border border-gray-200 flex items-center justify-center mx-auto shadow-2xs shrink-0">
                  <img
                    v-if="t.foto_url || t.foto_path"
                    :src="getPhotoUrl(t.foto_url, t.foto_path)"
                    :alt="t.nama_lengkap"
                    class="w-full h-full object-cover"
                    @error="(e: any) => e.target.style.display = 'none'"
                  />
                  <span v-else class="text-[11px] font-bold text-gray-500 uppercase">
                    {{ getInitials(t.nama_lengkap) }}
                  </span>
                </div>
              </td>
              <td class="py-3 px-4 font-mono font-medium text-gray-700 whitespace-nowrap">
                {{ t.nip ?? '-' }}
              </td>
              <td class="py-3 px-4 font-bold text-gray-900 whitespace-nowrap">
                <button @click="emit('detail', t)" class="text-left hover:text-sky-600 transition font-bold block cursor-pointer">
                  {{ t.nama_lengkap }}
                </button>
                <div class="text-[10px] text-gray-400 font-normal">{{ t.email ?? '-' }}</div>
              </td>
              <td class="py-3 px-4 whitespace-nowrap">
                <div class="flex flex-col gap-1 items-start">
                  <span v-if="t.jabatan" class="px-2 py-0.5 bg-sky-50 text-sky-800 rounded-md border border-sky-200 text-[10px] font-bold">
                    {{ t.jabatan }}
                  </span>
                  <span v-if="t.tugas_tambahan" class="px-2 py-0.5 bg-amber-50 text-amber-800 rounded-md border border-amber-200 text-[10px] font-semibold">
                    {{ t.tugas_tambahan }}
                  </span>
                  <span v-if="!t.jabatan && !t.tugas_tambahan" class="text-gray-400">-</span>
                </div>
              </td>
              <td class="py-3 px-4">
                <div class="flex flex-col gap-1 items-start max-w-xs">
                  <!-- Tag jika Wali Kelas -->
                  <div v-if="t.homeroom_classes && t.homeroom_classes.length > 0" class="flex flex-wrap gap-1">
                    <span v-for="c in t.homeroom_classes" :key="c.id" class="px-2 py-0.5 bg-purple-50 text-purple-700 rounded-md border border-purple-200 text-[10px] font-bold">
                      Wali: {{ c.nama }}
                    </span>
                  </div>
                  <!-- Tag Mata Pelajaran yang diampu -->
                  <div v-if="t.assignments && t.assignments.length > 0" class="flex flex-wrap gap-1">
                    <span v-for="asg in t.assignments.slice(0, 2)" :key="asg.id" class="px-1.5 py-0.5 bg-gray-100 text-gray-700 rounded text-[9px]">
                      {{ asg.subject?.nama }}
                    </span>
                    <span v-if="t.assignments.length > 2" class="text-[9px] text-gray-400 font-semibold">
                      +{{ t.assignments.length - 2 }} lainnya
                    </span>
                  </div>
                  <span v-if="(!t.homeroom_classes || t.homeroom_classes.length === 0) && (!t.assignments || t.assignments.length === 0)" class="text-gray-400">-</span>
                </div>
              </td>
              <td class="py-3 px-4 whitespace-nowrap">
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-700">
                  {{ t.jenis_ptk?.replace('_', ' ') }}
                </span>
              </td>
              <td class="py-3 px-4 whitespace-nowrap">
                <div class="font-bold text-gray-900 uppercase text-[11px]">{{ t.status_kepegawaian }}</div>
                <div class="text-[10px] text-gray-500 mt-0.5">
                  Masa Kerja: <strong class="text-gray-800">{{ calculateTenure(t.tgl_mulai_tugas) }}</strong>
                </div>
                <div class="text-[9px] text-gray-400">TMT: {{ formatDateIndo(t.tgl_mulai_tugas) }}</div>
              </td>
              <td class="py-3 px-4 font-mono text-gray-700 whitespace-nowrap">
                {{ t.no_hp }}
              </td>
              <td class="py-3 px-4 text-center whitespace-nowrap">
                <span
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                  :class="t.status === 'aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-600'"
                >
                  {{ t.status }}
                </span>
              </td>
              <td class="py-3 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="emit('detail', t)"
                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 rounded-lg text-xs font-semibold transition cursor-pointer"
                    title="Lihat Detail Profil Guru"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span>Detail</span>
                  </button>
                  <button
                    @click="emit('edit', t)"
                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-semibold transition cursor-pointer"
                    title="Edit Data Guru"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Edit</span>
                  </button>
                  <button
                    @click="emit('delete', t)"
                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-semibold transition cursor-pointer"
                    title="Hapus Guru"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
    </div>
  </div>
</template>
