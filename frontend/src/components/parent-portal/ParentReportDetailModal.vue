<template>
  <div
    v-if="show && reportDetail"
    class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5"
  >
    <div class="bg-white rounded-3xl max-w-4xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
      <!-- Modal Header -->
      <div class="p-5 bg-gradient-to-r from-emerald-800 to-teal-900 text-white flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <span class="text-2xl">📑</span>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs uppercase font-extrabold px-2 py-0.5 rounded bg-white/20 text-emerald-100">
                {{ reportDetail.jenis_rapor?.replace('_', ' ') }}
              </span>
              <span class="text-xs text-emerald-200 font-bold">
                {{ reportDetail.periode || reportDetail.semester }}
              </span>
            </div>
            <h3 class="text-lg font-black text-white mt-0.5">
              Rapor {{ studentName || 'Ananda' }}
            </h3>
          </div>
        </div>
        <button
          @click="$emit('close')"
          class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center font-black cursor-pointer"
        >
          ✕
        </button>
      </div>

      <!-- Modal Body Content -->
      <div class="p-6 overflow-y-auto space-y-6 text-xs text-slate-700">
        <!-- Summary Info Bar -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 bg-slate-50 rounded-2xl border border-slate-200/80">
          <div>
            <span class="text-slate-400 block text-[10px] uppercase font-semibold">Tahun Pelajaran</span>
            <strong class="text-slate-900">{{ reportDetail.academic_year?.nama || '-' }}</strong>
          </div>
          <div>
            <span class="text-slate-400 block text-[10px] uppercase font-semibold">Rombel Kelas</span>
            <strong class="text-slate-900">{{ reportDetail.school_class?.nama_kelas || '-' }}</strong>
          </div>
          <div>
            <span class="text-slate-400 block text-[10px] uppercase font-semibold">Fase Kurikulum</span>
            <strong class="text-slate-900">Fase {{ reportDetail.fase || 'A' }}</strong>
          </div>
          <div>
            <span class="text-slate-400 block text-[10px] uppercase font-semibold">Kehadiran (S/I/A)</span>
            <strong class="text-emerald-800">
              S: {{ reportDetail.sakit || 0 }} • I: {{ reportDetail.izin || 0 }} • A: {{ reportDetail.alpa || 0 }}
            </strong>
          </div>
        </div>

        <!-- Grades Table -->
        <div>
          <h4 class="font-bold text-slate-900 text-sm mb-2 flex items-center gap-1.5">
            <span>📊</span>
            <span>Capaian Penilaian & Nilai Akhir</span>
          </h4>
          <div class="border border-slate-200 rounded-2xl overflow-hidden">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-100/80 text-slate-700 font-extrabold uppercase text-[10px] border-b border-slate-200">
                <tr>
                  <th class="py-2.5 px-3 w-10 text-center">No</th>
                  <th class="py-2.5 px-3">Mata Pelajaran / Aspek Penilaian</th>
                  <th class="py-2.5 px-3 w-20 text-center">Nilai</th>
                  <th class="py-2.5 px-3 w-20 text-center">Predikat</th>
                  <th class="py-2.5 px-3">Deskripsi Capaian Kompetensi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-if="!reportDetail.grades || reportDetail.grades.length === 0">
                  <td colspan="5" class="py-6 text-center text-slate-400 italic">Belum ada daftar nilai tercatat.</td>
                </tr>
                <tr v-for="(g, idx) in reportDetail.grades" :key="idx" class="hover:bg-slate-50/50">
                  <td class="py-2.5 px-3 text-center text-slate-400 font-mono">{{ Number(idx) + 1 }}</td>
                  <td class="py-2.5 px-3 font-bold text-slate-800">
                    {{ g.nama_mapel || g.sub_aspek || g.tema }}
                  </td>
                  <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-900">
                    {{ g.nilai_akhir ?? '-' }}
                  </td>
                  <td class="py-2.5 px-3 text-center">
                    <span v-if="g.predikat" class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-100 text-emerald-800">
                      {{ g.predikat }}
                    </span>
                    <span v-else class="text-slate-400">-</span>
                  </td>
                  <td class="py-2.5 px-3 text-slate-600 leading-relaxed text-[11px]">
                    {{ g.capaian_kompetensi || '-' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Catatan Wali Kelas -->
        <div v-if="reportDetail.catatan_wali_kelas" class="p-4 bg-emerald-50/50 border border-emerald-200 rounded-2xl">
          <h5 class="font-bold text-emerald-950 text-xs mb-1">Catatan & Pesan Wali Kelas:</h5>
          <p class="text-xs text-emerald-900 leading-relaxed italic">
            "{{ reportDetail.catatan_wali_kelas }}"
          </p>
        </div>

        <!-- Validasi & Tanda Tangan -->
        <div class="grid grid-cols-2 gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-200 text-center">
          <div>
            <span class="text-slate-400 text-[10px] block">Wali Kelas Pengampu:</span>
            <strong class="text-slate-900 text-xs block mt-1">{{ reportDetail.wali_kelas_nama || '-' }}</strong>
            <span class="text-slate-400 text-[10px] font-mono">NIP: {{ reportDetail.wali_kelas_nip || '-' }}</span>
          </div>
          <div>
            <span class="text-slate-400 text-[10px] block">Disahkan oleh Kepala Sekolah:</span>
            <strong class="text-slate-900 text-xs block mt-1">{{ reportDetail.kepala_sekolah_nama || '-' }}</strong>
            <span class="text-slate-400 text-[10px] font-mono">NIP: {{ reportDetail.kepala_sekolah_nip || '-' }}</span>
          </div>
        </div>
      </div>

      <!-- Modal Footer Actions -->
      <div class="p-4 bg-slate-100/80 border-t border-slate-200 flex items-center justify-between gap-3">
        <button
          @click="$emit('close')"
          class="px-4 py-2 bg-white hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 transition cursor-pointer"
        >
          Tutup
        </button>
        <button
          @click="printReport"
          class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black rounded-xl shadow-md transition flex items-center gap-1.5 cursor-pointer"
        >
          <span>🖨️</span>
          <span>Cetak / Unduh PDF Dokumen Resmi</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ReportCardDetail } from './types';

defineProps<{
  show: boolean;
  reportDetail: ReportCardDetail | null;
  studentName?: string;
}>();

defineEmits<{
  (e: 'close'): void;
}>();

const printReport = () => {
  window.print();
};
</script>
