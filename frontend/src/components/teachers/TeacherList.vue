<script setup lang="ts">
import type { Teacher } from './types'
import { getPhotoUrl, getInitials, calculateTenure, formatDateIndo } from './types'
import InfoTooltip from '../common/InfoTooltip.vue'

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
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 sm:p-5 rounded-2xl border border-slate-100 shadow-xs">
      <div class="flex items-center gap-2.5">
        <h1 class="text-lg sm:text-xl font-bold text-slate-900 leading-tight">Guru & Tendik</h1>
        <span v-if="teachers.length > 0" class="px-2.5 py-0.5 text-xs font-semibold bg-emerald-50 text-emerald-700 rounded-full border border-emerald-200">
          {{ teachers.length }} Orang
        </span>
        <InfoTooltip text="Master data pendidik (guru kelas/mapel/tahfidz) dan tenaga kependidikan SDI Bina Insan Mandiri." />
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          @click="emit('export')"
          :disabled="exporting"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 transition disabled:opacity-50 cursor-pointer"
          title="Export Data Guru & Tendik ke Excel"
        >
          <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <span v-if="exporting">Mengekspor...</span>
          <span v-else>Export</span>
        </button>
        <router-link
          to="/excel-imports"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 transition"
          title="Import Dapodik Guru"
        >
          <svg class="w-4 h-4 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          <span>Import</span>
        </router-link>
        <button
          @click="emit('add')"
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-xs transition active:scale-95 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Tambah PTK</span>
        </button>
      </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-3.5 rounded-2xl border border-slate-100 shadow-xs flex flex-col md:flex-row gap-2.5 items-center justify-between">
      <div class="flex-1 w-full md:w-auto relative">
        <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          :value="search"
          @input="(e: any) => onSearchInput(e.target.value)"
          type="text"
          placeholder="Cari NIP, NIK, atau Nama Guru..."
          class="w-full pl-9 pr-4 py-2 bg-slate-50 text-xs rounded-xl border border-slate-200 focus:bg-white focus:ring-2 focus:ring-sky-500 outline-none transition"
        />
      </div>

      <div class="flex gap-2 w-full md:w-auto">
        <select
          :value="filterJenisPtk"
          @change="(e: any) => { emit('update:filterJenisPtk', e.target.value); emit('filter-change'); }"
          class="px-3 py-2 bg-slate-50 text-xs rounded-xl border border-slate-200 focus:bg-white focus:ring-2 focus:ring-sky-500 outline-none text-slate-700"
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

    <!-- Table Guru Minimalis & Rapi -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600 min-w-[800px]">
          <thead class="bg-slate-50/80 text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
            <tr>
              <th class="py-3 px-3.5 w-10 text-center">No</th>
              <th class="py-3 px-3 w-12 text-center">Foto</th>
              <th class="py-3 px-4">
                <div class="inline-flex items-center gap-1.5">
                  <span>Guru / Tendik</span>
                  <InfoTooltip size="xs" text="Nama lengkap, gelar, NIP / NUPTK, dan email akun guru." />
                </div>
              </th>
              <th class="py-3 px-4">Tugas & Mengajar</th>
              <th class="py-3 px-3 text-center">Jenis PTK</th>
              <th class="py-3 px-4">Kepegawaian</th>
              <th class="py-3 px-4">No. HP</th>
              <th class="py-3 px-3 text-center w-20">Status</th>
              <th class="py-3 px-4 text-right w-24">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="loading">
              <td colspan="9" class="text-center py-10 text-slate-400">
                <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-sky-500 border-t-transparent mb-2"></div>
                <p>Memuat data guru & tenaga kependidikan...</p>
              </td>
            </tr>
            <tr v-else-if="teachers.length === 0">
              <td colspan="9" class="text-center py-10 text-slate-400">
                Tidak ada data guru yang sesuai dengan pencarian / filter.
              </td>
            </tr>
            <tr v-for="(t, idx) in teachers" :key="t.id" class="hover:bg-slate-50/70 transition">
              <td class="py-2.5 px-3.5 text-slate-400 font-mono text-center text-[11px]">{{ idx + 1 }}</td>
              <td class="py-2.5 px-3 text-center">
                <div class="w-8 h-8 rounded-full overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center mx-auto shadow-2xs shrink-0">
                  <img
                    v-if="t.foto_url || t.foto_path"
                    :src="getPhotoUrl(t.foto_url, t.foto_path)"
                    :alt="t.nama_lengkap"
                    class="w-full h-full object-cover"
                    @error="(e: any) => e.target.style.display = 'none'"
                  />
                  <span v-else class="text-[10px] font-bold text-slate-500 uppercase">
                    {{ getInitials(t.nama_lengkap) }}
                  </span>
                </div>
              </td>
              <td class="py-2.5 px-4 whitespace-nowrap">
                <button @click="emit('detail', t)" class="text-left font-semibold text-slate-900 hover:text-sky-600 transition block cursor-pointer">
                  {{ t.nama_lengkap }}
                </button>
                <div class="flex items-center gap-2 mt-0.5 text-[10px] text-slate-400 font-mono">
                  <span>NIP: {{ t.nip || '-' }}</span>
                  <span v-if="t.email" class="text-slate-300">•</span>
                  <span v-if="t.email">{{ t.email }}</span>
                </div>
              </td>
              <td class="py-2.5 px-4">
                <div class="flex flex-col gap-1 items-start max-w-xs">
                  <!-- Tag Wali Kelas -->
                  <div v-if="t.homeroom_classes && t.homeroom_classes.length > 0" class="flex flex-wrap gap-1">
                    <span v-for="c in t.homeroom_classes" :key="c.id" class="px-2 py-0.5 bg-purple-50 text-purple-700 rounded-md border border-purple-200 text-[10px] font-bold">
                      Wali: {{ c.nama }}
                    </span>
                  </div>
                  <!-- Tag Mapel Utama -->
                  <div v-if="t.assignments && t.assignments.length > 0" class="flex flex-wrap gap-1">
                    <span v-for="asg in t.assignments.slice(0, 2)" :key="asg.id" class="px-1.5 py-0.5 bg-slate-100 text-slate-700 rounded text-[9px]">
                      {{ asg.subject?.nama }}
                    </span>
                    <span v-if="t.assignments.length > 2" class="text-[9px] text-slate-400 font-semibold">
                      +{{ t.assignments.length - 2 }} lainnya
                    </span>
                  </div>
                  <span v-if="(!t.homeroom_classes || t.homeroom_classes.length === 0) && (!t.assignments || t.assignments.length === 0)" class="text-slate-400 italic text-[10px]">-</span>
                </div>
              </td>
              <td class="py-2.5 px-3 text-center whitespace-nowrap">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-700">
                  {{ t.jenis_ptk?.replace('_', ' ') }}
                </span>
              </td>
              <td class="py-2.5 px-4 whitespace-nowrap">
                <div class="font-bold text-slate-900 uppercase text-[11px]">{{ t.status_kepegawaian }}</div>
                <div class="text-[10px] text-slate-500 mt-0.5">
                  Masa Kerja: <strong class="text-slate-700">{{ calculateTenure(t.tgl_mulai_tugas) }}</strong>
                </div>
              </td>
              <td class="py-2.5 px-4 font-mono text-slate-700 text-[11px] whitespace-nowrap">
                {{ t.no_hp || '-' }}
              </td>
              <td class="py-2.5 px-3 text-center whitespace-nowrap">
                <span
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold capitalize"
                  :class="t.status === 'aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="t.status === 'aktif' ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                  {{ t.status }}
                </span>
              </td>
              <!-- Aksi Icon Buttons Minimalis -->
              <td class="py-2.5 px-4 text-right whitespace-nowrap">
                <div class="inline-flex items-center gap-1 justify-end">
                  <button
                    @click="emit('detail', t)"
                    class="p-1.5 text-slate-500 hover:text-sky-700 hover:bg-sky-50 rounded-lg transition cursor-pointer"
                    title="Lihat Detail Profil Guru"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                  <button
                    @click="emit('edit', t)"
                    class="p-1.5 text-slate-500 hover:text-amber-700 hover:bg-amber-50 rounded-lg transition cursor-pointer"
                    title="Edit Data Guru"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button
                    @click="emit('delete', t)"
                    class="p-1.5 text-slate-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition cursor-pointer"
                    title="Hapus Guru"
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
    </div>
  </div>
</template>
