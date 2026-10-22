<template>
  <div class="p-5 sm:p-7 space-y-6">
    <div class="border-b border-slate-100 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h3 class="text-sm sm:text-base font-bold text-slate-900 flex items-center gap-2">
          <span>📑</span>
          <span>Dokumen Resmi E-Rapor Hasil Belajar Ananda</span>
        </h3>
        <p class="text-xs text-slate-500 mt-0.5">
          Daftar buku rapor yang telah divalidasi dan disahkan secara resmi oleh Kepala Sekolah SDI Bina Insan Mandiri Purwokerto.
        </p>
      </div>
      <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full text-xs font-bold">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          <span>Hanya Dokumen Sah (Final)</span>
        </span>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="py-12 text-center text-slate-400">
      <div class="inline-block animate-spin w-8 h-8 border-3 border-emerald-600 border-t-transparent rounded-full mb-3"></div>
      <p class="text-xs text-slate-500 font-medium">Mengambil berkas e-rapor ananda...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="reportCardList.length === 0" class="py-12 text-center bg-slate-50/80 rounded-3xl border border-dashed border-slate-200 p-8 space-y-3">
      <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto text-2xl font-bold">
        📂
      </div>
      <div>
        <h4 class="text-sm font-bold text-slate-800">Belum Ada E-Rapor Resmi yang Diterbitkan</h4>
        <p class="text-xs text-slate-500 max-w-md mx-auto mt-1 leading-relaxed">
          Buku rapor ananda akan otomatis muncul dan dapat diunduh di sini setelah nilai selesai direkap oleh Wali Kelas dan divalidasi resmi oleh Kepala Sekolah.
        </p>
      </div>
    </div>

    <!-- Cards Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="rc in reportCardList"
        :key="rc.id"
        class="bg-white rounded-3xl border border-slate-200/90 shadow-xs hover:shadow-md transition-all p-5 flex flex-col justify-between"
      >
        <div>
          <!-- Badge Jenis Rapor -->
          <div class="flex items-center justify-between gap-2 mb-3">
            <span
              class="px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider"
              :class="{
                'bg-indigo-100 text-indigo-800 border border-indigo-200': rc.jenis_rapor === 'kurikulum_merdeka',
                'bg-emerald-100 text-emerald-800 border border-emerald-200': rc.jenis_rapor === 'triwulan_tematik',
                'bg-purple-100 text-purple-800 border border-purple-200': rc.jenis_rapor === 'karakter_lifeskill',
              }"
            >
              {{ rc.jenis_rapor === 'kurikulum_merdeka' ? 'Kurikulum Merdeka' : (rc.jenis_rapor === 'triwulan_tematik' ? 'Tematik Terpadu BIM' : 'Karakter & Life Skill') }}
            </span>

            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
              <span>✓</span>
              <span>Sah / Final</span>
            </span>
          </div>

          <h4 class="text-base font-black text-slate-900 leading-snug">
            {{ rc.periode || (rc.semester === 'gasal' ? 'Semester Gasal' : 'Semester Genap') }}
          </h4>
          <div class="text-xs text-slate-500 mt-1">
            Tahun Pelajaran {{ rc.academic_year?.nama || '-' }} • {{ rc.school_class?.nama_kelas || '-' }}
          </div>

          <!-- Snapshot Penandatangan & Pengesahan -->
          <div class="mt-4 pt-3 border-t border-slate-100 space-y-1 text-xs">
            <div class="flex items-center justify-between text-slate-600">
              <span class="text-slate-400">Wali Kelas:</span>
              <strong class="text-slate-800 truncate max-w-[150px]">{{ rc.wali_kelas_nama || '-' }}</strong>
            </div>
            <div class="flex items-center justify-between text-slate-600">
              <span class="text-slate-400">Kepala Sekolah:</span>
              <strong class="text-slate-800 truncate max-w-[150px]">{{ rc.kepala_sekolah_nama || '-' }}</strong>
            </div>
            <div class="flex items-center justify-between text-emerald-700 text-[11px] pt-1 font-semibold">
              <span>Status Validasi:</span>
              <span>Telah Divalidasi KS</span>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
          <button
            type="button"
            @click="$emit('open-detail', rc)"
            class="flex-1 py-2 px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center justify-center gap-1.5 cursor-pointer"
          >
            <span>👁️</span>
            <span>Lihat & Unduh</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ReportCardSummary } from './types';

defineProps<{
  reportCardList: ReportCardSummary[];
  loading: boolean;
}>();

defineEmits<{
  (e: 'open-detail', rc: ReportCardSummary): void;
}>();
</script>
