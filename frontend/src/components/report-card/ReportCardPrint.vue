<script setup lang="ts">
import type { ReportCardDetail } from './types'

const props = defineProps<{
  mode: 'print_tematik' | 'print_karakter' | 'print_raport'
  activeReportCard: ReportCardDetail
  schoolProfile?: {
    nama_sekolah?: string
    alamat?: string
  }
}>()

const emit = defineEmits<{
  (e: 'back'): void
}>()

const handlePrintBrowser = () => {
  window.print()
}
</script>

<template>
  <div>
    <!-- (A) PRINT TEMATIK (TRIWULAN TEMATIK) -->
    <div v-if="props.mode === 'print_tematik'" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 space-y-4">
      <div class="flex items-center justify-between print:hidden bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <button
          @click="emit('back')"
          class="px-4 py-2 border border-slate-200 bg-white hover:bg-slate-50 cursor-pointer rounded-xl text-xs font-bold text-slate-700 transition-colors"
        >
          ← Kembali ke Editor
        </button>
        <button
          @click="handlePrintBrowser"
          class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-extrabold shadow-md flex items-center gap-2 cursor-pointer transition-colors"
        >
          <span>🖨️</span>
          <span>Cetak Dokumen (Ctrl + P)</span>
        </button>
      </div>

      <div class="bg-white p-8 sm:p-12 shadow-xl border border-slate-200 rounded-xl text-black font-serif print:shadow-none print:border-none print:p-0">
        <!-- School Header -->
        <div class="text-center pb-3 border-b-2 border-black mb-5">
          <h3 class="text-xs font-bold uppercase tracking-wider font-sans">Sekolah Dasar Islam Bina Insan Mandiri Purwokerto</h3>
          <h2 class="text-lg sm:text-xl font-black uppercase tracking-wider font-sans mt-1">
            LAPORAN HASIL BELAJAR - {{ (props.activeReportCard.periode || 'TRIWULAN').toUpperCase() }}
          </h2>
        </div>

        <!-- Student Meta Box -->
        <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-xs mb-5 font-sans">
          <div class="flex"><span class="w-24 font-bold">Nama</span><span class="mr-2">:</span><span class="font-bold">{{ props.activeReportCard.student?.nama_lengkap }}</span></div>
          <div class="flex"><span class="w-24 font-bold">Semester</span><span class="mr-2">:</span><span>{{ props.activeReportCard.semester }}</span></div>
          <div class="flex"><span class="w-24 font-bold">NIS / NISN</span><span class="mr-2">:</span><span>{{ props.activeReportCard.student?.nis || '-' }} / {{ props.activeReportCard.student?.nisn || '-' }}</span></div>
          <div class="flex"><span class="w-24 font-bold">Fase</span><span class="mr-2">:</span><span>{{ props.activeReportCard.fase || 'A' }}</span></div>
          <div class="flex"><span class="w-24 font-bold">Kelas</span><span class="mr-2">:</span><span>{{ props.activeReportCard.school_class?.nama || '-' }}</span></div>
          <div class="flex"><span class="w-24 font-bold">Tahun Ajaran</span><span class="mr-2">:</span><span>{{ props.activeReportCard.academic_year?.nama || '2025/2026' }}</span></div>
        </div>

        <!-- Tabel Muatan Tematik (5 Tema) -->
        <div class="mb-5">
          <table class="w-full border-collapse border border-black text-xs font-sans">
            <thead>
              <tr class="bg-slate-100 text-center font-bold">
                <th class="border border-black py-2 px-1 w-8">No</th>
                <th class="border border-black py-2 px-2 text-left w-36">Tema</th>
                <th class="border border-black py-2 px-2 text-left w-48">Muatan Pelajaran</th>
                <th class="border border-black py-2 px-1 w-14">Nilai</th>
                <th class="border border-black py-2 px-1 w-12">Predikat</th>
                <th class="border border-black py-2 px-2 text-left">Capaian Kompetensi / Tujuan Pembelajaran</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(g, idx) in props.activeReportCard.grades" :key="idx">
                <td class="border border-black py-2 px-1 text-center align-top">{{ idx + 1 }}</td>
                <td class="border border-black py-2 px-2 align-top font-bold text-[11px]">{{ g.tema || 'Tematik' }}</td>
                <td class="border border-black py-2 px-2 align-top text-[11px] font-semibold">{{ g.nama_mapel }}</td>
                <td class="border border-black py-2 px-1 text-center align-top font-bold">{{ g.nilai_akhir ?? '-' }}</td>
                <td class="border border-black py-2 px-1 text-center align-top font-bold text-emerald-800">{{ g.predikat || '-' }}</td>
                <td class="border border-black py-2 px-2 align-top text-[11px] leading-relaxed text-justify">
                  {{ g.capaian_kompetensi || '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Ekstrakurikuler -->
        <div class="mb-5 font-sans">
          <h4 class="font-bold text-xs uppercase tracking-wider mb-1.5 border-b border-black pb-1">
            Ekstrakurikuler
          </h4>
          <table class="w-full border-collapse border border-black text-xs">
            <thead>
              <tr class="bg-slate-100 text-center font-bold">
                <th class="border border-black py-1.5 px-2 w-8">No</th>
                <th class="border border-black py-1.5 px-3 text-left w-48">Ekstrakurikuler</th>
                <th class="border border-black py-1.5 px-2 text-center w-20">Predikat</th>
                <th class="border border-black py-1.5 px-3 text-left">Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(e, eIdx) in props.activeReportCard.extracurriculars" :key="eIdx">
                <td class="border border-black py-1.5 px-2 text-center">{{ eIdx + 1 }}</td>
                <td class="border border-black py-1.5 px-3 font-semibold">{{ e.kegiatan }}</td>
                <td class="border border-black py-1.5 px-2 text-center font-bold">{{ e.predikat }}</td>
                <td class="border border-black py-1.5 px-3 text-[11px]">{{ e.keterangan || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Catatan Guru & Absensi -->
        <div class="grid grid-cols-2 gap-4 mb-6 font-sans">
          <div class="border border-black p-3">
            <h5 class="font-bold text-xs uppercase mb-1">Catatan Guru</h5>
            <p class="text-xs italic leading-relaxed">
              {{ props.activeReportCard.catatan_wali_kelas || 'Pertahankan prestasi dan terus bersemangat dalam belajar. Baarakallahu fiik.' }}
            </p>
          </div>

          <div class="border border-black p-3">
            <h5 class="font-bold text-xs uppercase mb-1">Absensi</h5>
            <div class="text-xs space-y-0.5">
              <div class="flex justify-between"><span>Sakit:</span><strong>{{ props.activeReportCard.sakit }} hari</strong></div>
              <div class="flex justify-between"><span>Izin:</span><strong>{{ props.activeReportCard.izin }} hari</strong></div>
              <div class="flex justify-between"><span>Tanpa Keterangan:</span><strong>{{ props.activeReportCard.alpa }} hari</strong></div>
            </div>
          </div>
        </div>

        <!-- Lembar Tanda Tangan (3 Kolom) -->
        <div class="font-sans text-xs pt-4">
          <div class="text-right mb-6">{{ props.activeReportCard.tempat_tanggal || 'Purwokerto' }}</div>
          <div class="grid grid-cols-3 text-center gap-4">
            <div>
              <p>Mengetahui,</p>
              <p class="font-bold">Orang Tua / Wali</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-36 mx-auto">................................</p>
            </div>
            <div>
              <p>&nbsp;</p>
              <p class="font-bold">Wali Kelas</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-44 mx-auto font-bold">{{ props.activeReportCard.wali_kelas_nama || 'Wali Kelas' }}</p>
            </div>
            <div>
              <p>Mengetahui,</p>
              <p class="font-bold">Kepala Sekolah</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-48 mx-auto font-bold">{{ props.activeReportCard.kepala_sekolah_nama || 'Kepala Sekolah' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- (B) PRINT KARAKTER & LIFE SKILL -->
    <div v-else-if="props.mode === 'print_karakter'" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 space-y-4">
      <div class="flex items-center justify-between print:hidden bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <button
          @click="emit('back')"
          class="px-4 py-2 border border-slate-200 bg-white hover:bg-slate-50 cursor-pointer rounded-xl text-xs font-bold text-slate-700 transition-colors"
        >
          ← Kembali ke Editor
        </button>
        <button
          @click="handlePrintBrowser"
          class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-extrabold shadow-md flex items-center gap-2 cursor-pointer transition-colors"
        >
          <span>🖨️</span>
          <span>Cetak Dokumen (Ctrl + P)</span>
        </button>
      </div>

      <div class="bg-white p-8 sm:p-12 shadow-xl border border-slate-200 rounded-xl text-black font-serif print:shadow-none print:border-none print:p-0">
        <!-- Title -->
        <div class="text-center pb-3 border-b-2 border-black mb-5">
          <h3 class="text-xs font-bold uppercase tracking-wider font-sans">Sekolah Dasar Islam Bina Insan Mandiri Purwokerto</h3>
          <h2 class="text-base sm:text-lg font-black uppercase tracking-wider font-sans mt-1">
            LAPORAN PERKEMBANGAN PENGUATAN KARAKTER DAN LIFE SKILL
          </h2>
          <p class="text-xs font-bold tracking-widest uppercase font-sans mt-0.5">
            {{ props.activeReportCard.periode || 'TRIWULAN I' }}
          </p>
        </div>

        <!-- Student Meta -->
        <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-xs mb-5 font-sans">
          <div class="flex"><span class="w-24 font-bold">Nama</span><span class="mr-2">:</span><span class="font-bold">{{ props.activeReportCard.student?.nama_lengkap }}</span></div>
          <div class="flex"><span class="w-24 font-bold">Semester</span><span class="mr-2">:</span><span>{{ props.activeReportCard.semester }}</span></div>
          <div class="flex"><span class="w-24 font-bold">NIS / NISN</span><span class="mr-2">:</span><span>{{ props.activeReportCard.student?.nis || '-' }} / {{ props.activeReportCard.student?.nisn || '-' }}</span></div>
          <div class="flex"><span class="w-24 font-bold">Fase</span><span class="mr-2">:</span><span>{{ props.activeReportCard.fase || 'A' }}</span></div>
          <div class="flex"><span class="w-24 font-bold">Kelas</span><span class="mr-2">:</span><span>{{ props.activeReportCard.school_class?.nama || '-' }}</span></div>
          <div class="flex"><span class="w-24 font-bold">Tahun Ajaran</span><span class="mr-2">:</span><span>{{ props.activeReportCard.academic_year?.nama || '2025/2026' }}</span></div>
        </div>

        <!-- Tabel 17 Butir Karakter & Life Skill -->
        <div class="mb-5">
          <table class="w-full border-collapse border border-black text-xs font-sans">
            <thead>
              <tr class="bg-slate-100 text-center font-bold">
                <th class="border border-black py-2 px-1 w-8">No</th>
                <th class="border border-black py-2 px-2 text-left w-44">Aspek Penguatan</th>
                <th class="border border-black py-2 px-2 text-left w-48">Butir Karakter & Life Skill</th>
                <th class="border border-black py-2 px-2 text-left">Pelaksanaan Implementasi</th>
                <th class="border border-black py-2 px-1 w-12 text-center">Nilai</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(g, idx) in props.activeReportCard.grades" :key="idx">
                <td class="border border-black py-2 px-1 text-center align-top">{{ idx + 1 }}</td>
                <td class="border border-black py-2 px-2 align-top font-bold text-[10px] text-slate-800">{{ g.aspek || '-' }}</td>
                <td class="border border-black py-2 px-2 align-top font-semibold text-[11px]">{{ g.sub_aspek || g.nama_mapel }}</td>
                <td class="border border-black py-2 px-2 align-top text-[11px] leading-relaxed text-justify">
                  {{ g.capaian_kompetensi || '-' }}
                </td>
                <td class="border border-black py-2 px-1 text-center align-top font-black text-emerald-800">{{ g.predikat || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pedoman Interval Nilai -->
        <div class="mb-6 font-sans">
          <h5 class="font-bold text-[11px] uppercase mb-1">INTERVAL NILAI:</h5>
          <table class="w-64 border-collapse border border-black text-[10px]">
            <thead>
              <tr class="bg-slate-100 text-center font-bold">
                <th class="border border-black py-1 px-1">Interval Nilai</th>
                <th class="border border-black py-1 px-1">Predikat</th>
                <th class="border border-black py-1 px-1">Keterangan</th>
              </tr>
            </thead>
            <tbody class="text-center">
              <tr><td class="border border-black py-0.5 px-1">&gt; 92 - 100</td><td class="border border-black font-bold">A</td><td class="border border-black">Sangat Baik</td></tr>
              <tr><td class="border border-black py-0.5 px-1">&gt; 83 - 92</td><td class="border border-black font-bold">B</td><td class="border border-black">Baik</td></tr>
              <tr><td class="border border-black py-0.5 px-1">75 - 83</td><td class="border border-black font-bold">C</td><td class="border border-black">Cukup</td></tr>
              <tr><td class="border border-black py-0.5 px-1">&lt; 75</td><td class="border border-black font-bold">D</td><td class="border border-black">Kurang</td></tr>
            </tbody>
          </table>
        </div>

        <!-- Tanda Tangan -->
        <div class="font-sans text-xs pt-3">
          <div class="text-right mb-6">{{ props.activeReportCard.tempat_tanggal || 'Purwokerto' }}</div>
          <div class="grid grid-cols-3 text-center gap-4">
            <div>
              <p>Mengetahui,</p>
              <p class="font-bold">Orang Tua / Wali</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-36 mx-auto">................................</p>
            </div>
            <div>
              <p>&nbsp;</p>
              <p class="font-bold">Wali Kelas</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-44 mx-auto font-bold">{{ props.activeReportCard.wali_kelas_nama || 'Wali Kelas' }}</p>
            </div>
            <div>
              <p>Mengetahui,</p>
              <p class="font-bold">Kepala Sekolah</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-48 mx-auto font-bold">{{ props.activeReportCard.kepala_sekolah_nama || 'Kepala Sekolah' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- (C) PRINT KURIKULUM MERDEKA -->
    <div v-else-if="props.mode === 'print_raport'" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 space-y-4">
      <div class="flex items-center justify-between print:hidden bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <button
          @click="emit('back')"
          class="px-4 py-2 border border-slate-200 bg-white hover:bg-slate-50 cursor-pointer rounded-xl text-xs font-bold text-slate-700 transition-colors"
        >
          ← Kembali ke Editor
        </button>
        <button
          @click="handlePrintBrowser"
          class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-extrabold shadow-md flex items-center gap-2 cursor-pointer transition-colors"
        >
          <span>🖨️</span>
          <span>Cetak Dokumen (Ctrl + P)</span>
        </button>
      </div>

      <div class="bg-white p-8 sm:p-12 shadow-xl border border-slate-200 rounded-xl text-black font-serif print:shadow-none print:border-none print:p-0">
        <div class="text-center pb-4 border-b-2 border-black mb-6">
          <h2 class="text-xl sm:text-2xl font-bold uppercase tracking-wider">LAPORAN HASIL BELAJAR</h2>
          <p class="text-xs tracking-widest uppercase font-sans mt-0.5 font-semibold">
            SD ISLAM BINA INSAN MANDIRI PURWOKERTO
          </p>
        </div>

        <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-xs mb-6 font-sans">
          <div class="flex"><span class="w-28 font-semibold">Nama Murid</span><span class="mr-2">:</span><span class="font-bold uppercase">{{ props.activeReportCard.student?.nama_lengkap }}</span></div>
          <div class="flex"><span class="w-28 font-semibold">Kelas</span><span class="mr-2">:</span><span>{{ props.activeReportCard.school_class?.nama || '-' }}</span></div>
          <div class="flex"><span class="w-28 font-semibold">NISN</span><span class="mr-2">:</span><span>{{ props.activeReportCard.student?.nisn || '-' }}</span></div>
          <div class="flex"><span class="w-28 font-semibold">Fase</span><span class="mr-2">:</span><span>{{ props.activeReportCard.fase || 'A' }}</span></div>
          <div class="flex"><span class="w-28 font-semibold">Sekolah</span><span class="mr-2">:</span><span>{{ props.schoolProfile?.nama_sekolah || 'SD Islam Bina Insan Mandiri' }}</span></div>
          <div class="flex"><span class="w-28 font-semibold">Semester</span><span class="mr-2">:</span><span>{{ props.activeReportCard.semester }}</span></div>
          <div class="flex"><span class="w-28 font-semibold">Alamat</span><span class="mr-2">:</span><span class="truncate">{{ props.activeReportCard.student?.alamat_jalan || props.schoolProfile?.alamat || 'Purwokerto' }}</span></div>
          <div class="flex"><span class="w-28 font-semibold">Tahun Ajaran</span><span class="mr-2">:</span><span>{{ props.activeReportCard.academic_year?.nama || '2025/2026' }}</span></div>
        </div>

        <!-- Tabel Nilai Intrakurikuler -->
        <div class="mb-6">
          <h4 class="font-bold text-xs uppercase tracking-wider mb-2 font-sans border-b border-black pb-1">
            A. Nilai Intrakurikuler
          </h4>
          <table class="w-full border-collapse border border-black text-xs font-sans">
            <thead>
              <tr class="bg-slate-100 text-center font-bold">
                <th class="border border-black py-2 px-2 w-8">No.</th>
                <th class="border border-black py-2 px-3 text-left w-64">Mata Pelajaran</th>
                <th class="border border-black py-2 px-2 w-20">Nilai Akhir</th>
                <th class="border border-black py-2 px-3 text-left">Capaian Kompetensi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(g, gIdx) in props.activeReportCard.grades" :key="gIdx">
                <td class="border border-black py-1.5 px-2 text-center align-top">{{ gIdx + 1 }}</td>
                <td class="border border-black py-1.5 px-3 align-top font-semibold">{{ g.nama_mapel }}</td>
                <td class="border border-black py-1.5 px-2 text-center align-top font-bold">{{ g.nilai_akhir ?? '-' }}</td>
                <td class="border border-black py-1.5 px-3 align-top text-[11px] leading-relaxed">
                  {{ g.capaian_kompetensi || '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Kokurikuler (P5) -->
        <div class="mb-6 font-sans">
          <h4 class="font-bold text-xs uppercase tracking-wider mb-2 border-b border-black pb-1">
            B. Kokurikuler
          </h4>
          <div class="border border-black p-3 rounded-none text-xs leading-relaxed">
            <div class="font-bold text-slate-900 mb-1">
              Tema: {{ props.activeReportCard.kokurikuler_tema || '-' }}
            </div>
            <p class="text-[11px] text-justify">
              {{ props.activeReportCard.kokurikuler_deskripsi || 'Ananda telah berpartisipasi aktif dalam kegiatan kokurikuler dan menunjukkan capaian berkembang sesuai harapan.' }}
            </p>
          </div>
        </div>

        <!-- Ekstrakurikuler -->
        <div class="mb-6 font-sans">
          <h4 class="font-bold text-xs uppercase tracking-wider mb-2 border-b border-black pb-1">
            C. Ekstrakurikuler
          </h4>
          <table class="w-full border-collapse border border-black text-xs">
            <thead>
              <tr class="bg-slate-100 text-center font-bold">
                <th class="border border-black py-1.5 px-2 w-8">No.</th>
                <th class="border border-black py-1.5 px-3 text-left w-56">Ekstrakurikuler</th>
                <th class="border border-black py-1.5 px-3 text-left">Keterangan / Capaian</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(e, eIdx) in props.activeReportCard.extracurriculars" :key="eIdx">
                <td class="border border-black py-1.5 px-2 text-center">{{ eIdx + 1 }}</td>
                <td class="border border-black py-1.5 px-3 font-semibold">{{ e.kegiatan }}</td>
                <td class="border border-black py-1.5 px-3">{{ e.predikat }} - {{ e.keterangan || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Ketidakhadiran & Catatan -->
        <div class="grid grid-cols-2 gap-6 mb-8 font-sans">
          <div>
            <h4 class="font-bold text-xs uppercase tracking-wider mb-2 border-b border-black pb-1">
              D. Ketidakhadiran
            </h4>
            <table class="w-full border-collapse border border-black text-xs">
              <tbody>
                <tr>
                  <td class="border border-black py-1 px-3 w-36">Sakit</td>
                  <td class="border border-black py-1 px-3">: {{ props.activeReportCard.sakit }} hari</td>
                </tr>
                <tr>
                  <td class="border border-black py-1 px-3">Izin</td>
                  <td class="border border-black py-1 px-3">: {{ props.activeReportCard.izin }} hari</td>
                </tr>
                <tr>
                  <td class="border border-black py-1 px-3">Tanpa Keterangan</td>
                  <td class="border border-black py-1 px-3">: {{ props.activeReportCard.alpa }} hari</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div>
            <h4 class="font-bold text-xs uppercase tracking-wider mb-2 border-b border-black pb-1">
              E. Catatan Wali Kelas
            </h4>
            <div class="border border-black p-3 text-xs italic leading-relaxed min-h-[75px]">
              {{ props.activeReportCard.catatan_wali_kelas || 'Pertahankan prestasimu dan terus bersemangat dalam menuntut ilmu.' }}
            </div>
          </div>
        </div>

        <!-- Tanda Tangan -->
        <div class="font-sans text-xs pt-4">
          <div class="text-right mb-6">{{ props.activeReportCard.tempat_tanggal || 'Purwokerto' }}</div>
          <div class="grid grid-cols-3 text-center gap-4">
            <div>
              <p>Mengetahui,</p>
              <p class="font-bold">Orang Tua / Wali</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-36 mx-auto">................................</p>
            </div>
            <div>
              <p>&nbsp;</p>
              <p class="font-bold">Wali Kelas</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-44 mx-auto font-bold">{{ props.activeReportCard.wali_kelas_nama || 'Wali Kelas' }}</p>
            </div>
            <div>
              <p>Mengetahui,</p>
              <p class="font-bold">Kepala Sekolah</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-48 mx-auto font-bold">{{ props.activeReportCard.kepala_sekolah_nama || 'Kepala Sekolah' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
@media print {
  @page {
    size: A4 portrait;
    margin: 15mm 15mm 15mm 15mm;
  }
  body {
    background: white !important;
    color: black !important;
  }
}
</style>
