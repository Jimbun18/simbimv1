<script setup lang="ts">
import { computed } from 'vue'
import type { ReportCardDetail, SchoolProfileData } from './types'

const props = defineProps<{
  mode: 'print_tematik' | 'print_karakter' | 'print_raport'
  activeReportCard: ReportCardDetail
  schoolProfile?: SchoolProfileData
}>()

const emit = defineEmits<{
  (e: 'back'): void
}>()

const handlePrintBrowser = () => {
  window.print()
}

// Nama panggilan siswa untuk footer (misal: 'Kamayel')
const studentNickname = computed(() => {
  const st = props.activeReportCard.student
  if (!st) return 'Siswa'
  if (st.nama_panggilan) return st.nama_panggilan
  const parts = (st.nama_lengkap || '').trim().split(' ')
  return parts[parts.length - 1] || parts[0] || 'Siswa'
})

// Deteksi apakah rapor saat ini adalah Triwulan 1 atau Triwulan 3
const isTriwulan1Or3 = computed(() => {
  const p = (props.activeReportCard?.periode || '').toLowerCase().trim()
  if (!p) {
    return props.mode === 'print_karakter'
  }
  return (
    p.includes('triwulan 1') ||
    p.includes('triwulan i') ||
    p.includes('triwulan_1') ||
    p.includes('triwulan 3') ||
    p.includes('triwulan iii') ||
    p.includes('triwulan_3')
  )
})

const displayPeriodeTitle = computed(() => {
  const p = (props.activeReportCard?.periode || '').trim()
  if (p) return p.toUpperCase()
  return 'TRIWULAN I'
})

// 17 Butir Karakter & Life Skill SDI BIM dengan fallback lengkap
const DEFAULT_17_ITEMS = [
  // Aspek Sikap (9 butir)
  {
    aspek: 'Aspek Sikap',
    kelompok: 'Emosi & Karakter yang Kuat (Emotional & Spiritual Quotient)',
    sub_aspek: 'Motivasi diri (meyakinkan kita bahwa kita mampu, berpikir positif, visioner)',
    capaian: 'Alhamdulillah ananda sudah baik dalam membuat keputusan demi perkembangan dirinya.',
    predikat: 'B',
  },
  {
    aspek: 'Aspek Sikap',
    kelompok: 'Emosi & Karakter yang Kuat (Emotional & Spiritual Quotient)',
    sub_aspek: 'Mengendalikan emosi (mengelola emosi, mengendalikan diri, dan hati)',
    capaian: 'Alhamdulillah ananda sudah baik dalam mengucapkan 4 kata ajaib (tolong, maaf, permisi, dan terima kasih)',
    predikat: 'A',
  },
  {
    aspek: 'Aspek Sikap',
    kelompok: 'Emosi & Karakter yang Kuat (Emotional & Spiritual Quotient)',
    sub_aspek: 'Empati, kepedulian pada lingkungan sosial dan lingkungan alam',
    capaian: 'Alhamdulillah ananda sudah baik dalam menjaga kebersihan kelas dan lingkungan sekolah, seperti menjalankan piket sesuai jadwal dan membuang sampah pada tempatnya.',
    predikat: 'A',
  },
  {
    aspek: 'Aspek Sikap',
    kelompok: 'Emosi & Karakter yang Kuat (Emotional & Spiritual Quotient)',
    sub_aspek: 'Membangun fisik dan mental yang kuat',
    capaian: 'Alhamdulillah ananda sudah sangat baik dalam keberaniannya untuk berkata tidak terhadap hal-hal yang tidak disukai.',
    predikat: 'B',
  },
  {
    aspek: 'Aspek Sikap',
    kelompok: 'Emosi & Karakter yang Kuat (Emotional & Spiritual Quotient)',
    sub_aspek: 'Akhlakul Karimah (memiliki perilaku dan sikap yang baik/Islami)',
    capaian: 'Alhamdulillah ananda sudah sangat baik dalam berjabat tangan dengan ustadz dan ustadzah, menerapkan adab makan dan minum, serta menerapkan adab keluar masuk kamar mandi.',
    predikat: 'A',
  },
  {
    aspek: 'Aspek Sikap',
    kelompok: 'Emosi & Karakter yang Kuat (Emotional & Spiritual Quotient)',
    sub_aspek: 'Disiplin Diri (Self Discipline - melakukan tanpa diperintah)',
    capaian: 'Alhamdulillah ananda sudah baik dalam mengikuti upacara dan tertib mengikuti pelajaran.',
    predikat: 'B',
  },
  {
    aspek: 'Aspek Sikap',
    kelompok: 'Emosi & Karakter yang Kuat (Emotional & Spiritual Quotient)',
    sub_aspek: 'Manajemen waktu (mengelola waktu, scheduling)',
    capaian: 'Alhamdulillah ananda sudah baik dalam menyelesaikan tugas sesuai dengan waktu yang ditentukan.',
    predikat: 'A',
  },
  {
    aspek: 'Aspek Sikap',
    kelompok: 'Emosi & Karakter yang Kuat (Emotional & Spiritual Quotient)',
    sub_aspek: 'Konsisten (istiqomah)',
    capaian: 'Alhamdulillah ananda sudah baik dalam melaksanakan shalat lima waktu dengan tepat waktu, seperti mengerjakan shalat Dzuhur berjamaah pada saat di sekolah',
    predikat: 'B',
  },
  {
    aspek: 'Aspek Sikap',
    kelompok: 'Emosi & Karakter yang Kuat (Emotional & Spiritual Quotient)',
    sub_aspek: 'Fleksibilitas (kemampuan beradaptasi)',
    capaian: 'Alhamdulillah ananda sudah baik dalam berbaur dengan teman, seperti berjabat tangan dan menyapa/memberi salam pada saat berpapasan dengan teman.',
    predikat: 'B',
  },

  // Aspek Pengetahuan (7 butir)
  {
    aspek: 'Aspek Pengetahuan',
    kelompok: 'Belajar Efektif (Effective Learning)',
    sub_aspek: 'Pembelajaran efektif (belajar di mana saja, kapan saja, dan dengan siapa saja; semua adalah ilmu)',
    capaian: 'Alhamdulillah ananda sudah baik dalam belajar berkelompok besar dan kecil, seperti pada saat di halaqah maupun pembelajaran tema.',
    predikat: 'A',
  },
  {
    aspek: 'Aspek Pengetahuan',
    kelompok: 'Belajar Efektif (Effective Learning)',
    sub_aspek: 'Membaca buku (membiasakan untuk membaca buku; jendela ilmu)',
    capaian: 'Alhamdulillah ananda sudah baik dalam menceritakan kembali buku cerita yang dibacanya, seperti pada saat pembelajaran kisah Fatimah Az-Zahra binti Rasulullah.',
    predikat: 'A',
  },
  {
    aspek: 'Aspek Pengetahuan',
    kelompok: 'Belajar Efektif (Effective Learning)',
    sub_aspek: 'Berfikir kritis (banyak literasi dan referensi; observasi, analisis, evaluasi, dan tindak lanjut)',
    capaian: 'Alhamdulillah ananda sudah baik dalam mengidentifikasi dan mengolah informasi dan gagasan, seperti pada saat ada pertanyaan dan Ananda mampu menjawabnya.',
    predikat: 'A',
  },
  {
    aspek: 'Aspek Pengetahuan',
    kelompok: 'Komunikasi (Communication)',
    sub_aspek: 'Cara komunikasi yang efektif (mampu berkomunikasi dengan baik)',
    capaian: 'Alhamdulillah ananda sudah baik dalam menjadi pendengar yang baik dan memberikan apresiasi terhadap teman, seperti menghibur temannya pada saat sedih dan dapat memberikan nasihat yang baik kepada teman.',
    predikat: 'A',
  },
  {
    aspek: 'Aspek Pengetahuan',
    kelompok: 'Komunikasi (Communication)',
    sub_aspek: 'Persuasi (meyakinkan orang lain/presentasi)',
    capaian: 'Alhamdulillah ananda sudah baik dalam bersikap jujur.',
    predikat: 'B',
  },
  {
    aspek: 'Aspek Pengetahuan',
    kelompok: 'Komunikasi (Communication)',
    sub_aspek: 'Membangun networking (silaturahmi, membangun hubungan dan bersosialisasi)',
    capaian: 'Alhamdulillah ananda sudah baik dalam keterlibatan dalam aktivitas kelas/pembelajaran, seperti menjalankan peran sebagai pengurus kelas.',
    predikat: 'B',
  },
  {
    aspek: 'Aspek Pengetahuan',
    kelompok: 'Komunikasi (Communication)',
    sub_aspek: 'Belajar bahasa asing (berkomunikasi dan bersosialisasi dengan banyak orang dan banyak negara)',
    capaian: 'Alhamdulillah ananda sudah baik dalam pembendaharaan kosa kata bahasa Inggris, Arab, dan bahasa Jawa (Anggota keluarga, bilangan 1-10)',
    predikat: 'A',
  },

  // Aspek Keterampilan (1 butir)
  {
    aspek: 'Aspek Keterampilan',
    kelompok: 'Penguasaan Teknologi dan Keterampilan Hidup (Technology Mastery and Life Skill)',
    sub_aspek: 'Kecakapan Hidup (bertanam, berkebun, beternak, memasak, dan membuat peralatan sederhana)',
    capaian: 'Alhamdulillah ananda sudah sangat baik dalam melakukan kegiatan berkebun, mengenal berbagai tanamanan dan cara merawatnya.',
    predikat: 'A',
  },
]

// Normalisasi list 17 butir agar lengkap
const normalized17Items = computed(() => {
  const nick = studentNickname.value
  const userGrades = props.activeReportCard.grades || []

  return DEFAULT_17_ITEMS.map((def, idx) => {
    const existing = userGrades[idx]
    const subAspek = existing?.sub_aspek || existing?.nama_mapel || def.sub_aspek
    let capaian = existing?.capaian_kompetensi || def.capaian
    if (capaian && !capaian.includes(nick)) {
      capaian = capaian.replace(/ananda/gi, `ananda ${nick}`)
    }
    const predikat = existing?.predikat || def.predikat
    const kelompok = existing?.tema || def.kelompok

    return {
      urutan: idx + 1,
      aspek: def.aspek,
      kelompok,
      sub_aspek: subAspek,
      capaian,
      predikat,
    }
  })
})

// Paginasi 3 Lembar sesuai dokumen cetak asli:
// Halaman 1: Butir 1 s/d 5 (Aspek Sikap)
const page1Items = computed(() => normalized17Items.value.slice(0, 5))

// Halaman 2: Butir 6 s/d 9 (Lanjutan Aspek Sikap) & Butir 10 s/d 14 (Aspek Pengetahuan)
const page2SikapItems = computed(() => normalized17Items.value.slice(5, 9))
const page2PengetahuanItems = computed(() => normalized17Items.value.slice(9, 14))

// Halaman 3: Butir 15 s/d 16 (Lanjutan Aspek Pengetahuan) & Butir 17 (Aspek Keterampilan)
const page3PengetahuanItems = computed(() => normalized17Items.value.slice(14, 16))
const page3KeterampilanItems = computed(() => normalized17Items.value.slice(16, 17))

// NISN saja tanpa NIS (contoh: 3187371280)
const studentNisnOnly = computed(() => {
  const st = props.activeReportCard.student
  return st?.nisn || st?.nis || '-'
})

// Format kelas: angka dan ejaan saja (contoh: 1 (satu))
const formattedClass = computed(() => {
  const sc = props.activeReportCard.school_class || props.activeReportCard.student?.school_class
  const raw = sc?.tingkat || sc?.nama || '1'
  const match = String(raw).match(/\d+/)
  const num = match ? parseInt(match[0], 10) : 1
  const ejaan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas', 'dua belas']
  const textEjaan = ejaan[num] || 'satu'
  return `${num} (${textEjaan})`
})
</script>

<template>
  <div class="print-container">
    <!-- TOP ACTION CONTROL BAR (HIDDEN ON PRINT) -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-2 no-print print:hidden">
      <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <button
            @click="emit('back')"
            class="px-3.5 py-2 border border-slate-200 bg-white hover:bg-slate-50 cursor-pointer rounded-xl text-xs font-bold text-slate-700 transition-colors flex items-center gap-1.5 shadow-2xs"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Kembali ke Editor</span>
          </button>

          <div class="hidden sm:block h-6 w-px bg-slate-200"></div>

          <div>
            <div class="text-xs font-bold text-slate-900">
              Pratinjau Cetak: <span class="text-emerald-700 font-black">{{ props.activeReportCard.student?.nama_lengkap }}</span>
            </div>
            <div class="text-[11px] text-slate-500 font-medium">
              Kelas {{ props.activeReportCard.school_class?.nama || '-' }} &bull; {{ props.activeReportCard.periode || props.activeReportCard.semester }} &bull; Format Standar A4
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="handlePrintBrowser"
            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-extrabold shadow-md flex items-center gap-2 cursor-pointer transition-all hover:shadow-lg"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            <span>Cetak Dokumen (Ctrl + P)</span>
          </button>
        </div>
      </div>
    </div>

    <!-- (A) FORMAT 1: RAPOR TRIWULAN TEMATIK TERPADU BIM -->
    <div v-if="props.mode === 'print_tematik'" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-10">
      <div class="report-paper bg-white p-8 sm:p-12 shadow-xl border border-slate-200 rounded-xl text-black font-serif print:shadow-none print:border-none print:p-0 print:m-0">
        <!-- KOP SURAT RESMI LEMBAGA -->
        <div class="border-b-[3px] border-black pb-2 mb-3 font-serif">
          <div class="flex items-center gap-4">
            <div class="w-20 h-20 shrink-0 flex items-center justify-center">
              <img
                :src="props.schoolProfile?.logo_path || '/logo.png'"
                alt="Logo SDI BIM"
                class="w-full h-full object-contain"
              />
            </div>
            <div class="flex-1 text-center text-black leading-tight">
              <h4 class="text-xs font-bold uppercase tracking-widest text-slate-800 font-sans">
                {{ props.schoolProfile?.yayasan || 'YAYASAN BINA INSAN MANDIRI' }}
              </h4>
              <h1 class="text-base sm:text-lg font-black uppercase tracking-wide text-slate-900 mt-0.5">
                {{ props.schoolProfile?.nama_sekolah || 'SEKOLAH DASAR ISLAM BINA INSAN MANDIRI PURWOKERTO' }}
              </h1>
              <p class="text-[10.5px] font-sans mt-0.5 font-semibold text-slate-800">
                NPSN: {{ props.schoolProfile?.npsn || '70036695' }} &bull; NSS: {{ props.schoolProfile?.nss || '102030405060' }}
              </p>
              <p class="text-[10px] font-sans mt-0.5 text-slate-800">
                {{ props.schoolProfile?.alamat || 'Jl. Wadas Kelir RT.04 RW.05 Kel. Karangklesem, Kec. Purwokerto Selatan, Kab. Banyumas 53144' }}
              </p>
              <p class="text-[9.5px] font-sans text-slate-700">
                Telp: {{ props.schoolProfile?.telepon || '0281-635812' }} &bull; Email: {{ props.schoolProfile?.email || 'sdibim.pwt@gmail.com' }} &bull; Website: {{ props.schoolProfile?.website || 'https://sdibim.sch.id' }}
              </p>
            </div>
          </div>
          <!-- Garis Ganda Kop Surat -->
          <div class="border-t border-black mt-1.5"></div>
        </div>

        <!-- Judul Dokumen -->
        <div class="text-center my-3">
          <h2 class="text-base font-black uppercase tracking-wider font-sans underline decoration-1 underline-offset-4">
            LAPORAN HASIL BELAJAR PESERTA DIDIK
          </h2>
          <p class="text-xs font-bold uppercase tracking-wider font-sans text-slate-800 mt-1">
            TRIWULAN TEMATIK TERPADU &bull; {{ (props.activeReportCard.periode || 'TRIWULAN I').toUpperCase() }}
          </p>
        </div>

        <!-- Student Meta Box -->
        <div class="grid grid-cols-2 gap-x-8 gap-y-1 text-xs mb-4 font-sans border border-black p-2.5 bg-slate-50/20">
          <div class="flex"><span class="w-28 text-slate-700 font-medium">Nama Peserta Didik</span><span class="mr-2">:</span><strong class="uppercase text-slate-900">{{ props.activeReportCard.student?.nama_lengkap }}</strong></div>
          <div class="flex"><span class="w-28 text-slate-700 font-medium">Kelas / Rombel</span><span class="mr-2">:</span><strong class="text-slate-900">{{ props.activeReportCard.school_class?.nama || '-' }}</strong></div>
          <div class="flex"><span class="w-28 text-slate-700 font-medium">NIS / NISN</span><span class="mr-2">:</span><span>{{ props.activeReportCard.student?.nis || '-' }} / {{ props.activeReportCard.student?.nisn || '-' }}</span></div>
          <div class="flex"><span class="w-28 text-slate-700 font-medium">Fase</span><span class="mr-2">:</span><span class="font-bold">Fase {{ props.activeReportCard.fase || 'A' }}</span></div>
          <div class="flex"><span class="w-28 text-slate-700 font-medium">Semester</span><span class="mr-2">:</span><span>{{ props.activeReportCard.semester }}</span></div>
          <div class="flex"><span class="w-28 text-slate-700 font-medium">Tahun Pelajaran</span><span class="mr-2">:</span><span>{{ props.activeReportCard.academic_year?.nama || '2025/2026' }}</span></div>
        </div>

        <!-- Tabel Muatan Tematik (5 Klaster Tema BIM) -->
        <div class="mb-4">
          <table class="w-full border-collapse border border-black text-xs font-sans">
            <thead>
              <tr class="bg-slate-100 text-center font-bold">
                <th class="border border-black py-2 px-1 w-8">No</th>
                <th class="border border-black py-2 px-2 text-left w-36">Tema</th>
                <th class="border border-black py-2 px-2 text-left w-44">Muatan Pelajaran</th>
                <th class="border border-black py-2 px-1 w-12">Nilai</th>
                <th class="border border-black py-2 px-1 w-12">Predikat</th>
                <th class="border border-black py-2 px-2 text-left">Capaian Kompetensi / Narasi Pembelajaran</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(g, idx) in props.activeReportCard.grades" :key="idx">
                <td class="border border-black py-2 px-1 text-center align-top">{{ idx + 1 }}</td>
                <td class="border border-black py-2 px-2 align-top font-bold text-[11px]">{{ g.tema || 'Tematik' }}</td>
                <td class="border border-black py-2 px-2 align-top text-[11px] font-semibold">{{ g.nama_mapel }}</td>
                <td class="border border-black py-2 px-1 text-center align-top font-bold">{{ g.nilai_akhir ?? '-' }}</td>
                <td class="border border-black py-2 px-1 text-center align-top font-black text-slate-900">{{ g.predikat || '-' }}</td>
                <td class="border border-black py-2 px-2 align-top text-[11px] leading-relaxed text-justify">
                  {{ g.capaian_kompetensi || '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Ekstrakurikuler -->
        <div class="mb-4 font-sans">
          <h4 class="font-bold text-xs uppercase tracking-wider mb-1.5 border-b border-black pb-0.5">
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
        <div class="grid grid-cols-2 gap-4 mb-4 font-sans">
          <div class="border border-black p-3">
            <h5 class="font-bold text-xs uppercase mb-1">Catatan Guru / Wali Kelas</h5>
            <p class="text-xs italic leading-relaxed text-justify">
              {{ props.activeReportCard.catatan_wali_kelas || 'Pertahankan prestasi dan terus bersemangat dalam belajar. Baarakallahu fiik.' }}
            </p>
          </div>

          <div class="border border-black p-3">
            <h5 class="font-bold text-xs uppercase mb-1">Ketidakhadiran (Absensi)</h5>
            <div class="text-xs space-y-0.5">
              <div class="flex justify-between border-b border-slate-200 pb-0.5"><span>Sakit:</span><strong>{{ props.activeReportCard.sakit }} hari</strong></div>
              <div class="flex justify-between border-b border-slate-200 pb-0.5"><span>Izin:</span><strong>{{ props.activeReportCard.izin }} hari</strong></div>
              <div class="flex justify-between"><span>Tanpa Keterangan:</span><strong>{{ props.activeReportCard.alpa }} hari</strong></div>
            </div>
          </div>
        </div>

        <!-- Lembar Tanda Tangan (3 Kolom Resmi - disembunyikan untuk Triwulan 1 & 3) -->
        <div v-if="!isTriwulan1Or3" class="font-sans text-xs pt-2">
          <div class="text-right mb-4">{{ props.activeReportCard.tempat_tanggal || 'Purwokerto' }}</div>
          <div class="grid grid-cols-3 text-center gap-4">
            <div>
              <p>Mengetahui,</p>
              <p class="font-bold">Orang Tua / Wali Murid</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-36 mx-auto">................................</p>
            </div>
            <div>
              <p>&nbsp;</p>
              <p class="font-bold">Wali Kelas</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-44 mx-auto font-bold">{{ props.activeReportCard.wali_kelas_nama || 'Wali Kelas' }}</p>
              <p v-if="props.activeReportCard.wali_kelas_nip && props.activeReportCard.wali_kelas_nip !== '-'" class="text-[10px] text-slate-700">
                NIP: {{ props.activeReportCard.wali_kelas_nip }}
              </p>
            </div>
            <div>
              <p>Mengetahui,</p>
              <p class="font-bold">Kepala Sekolah</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-48 mx-auto font-bold">{{ props.activeReportCard.kepala_sekolah_nama || 'Kepala Sekolah' }}</p>
              <p v-if="props.activeReportCard.kepala_sekolah_nip && props.activeReportCard.kepala_sekolah_nip !== '-'" class="text-[10px] text-slate-700">
                NIP: {{ props.activeReportCard.kepala_sekolah_nip }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- (B) FORMAT 2: LAPORAN PERKEMBANGAN PENGUATAN KARAKTER DAN LIFE SKILL (3 HALAMAN PERSIS DOKUMEN RESMI) -->
    <div v-else-if="props.mode === 'print_karakter'" class="print-container max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-10 space-y-8 print:space-y-0 print:p-0 print:m-0 print:max-w-none print:w-full">
      
      <!-- ==================== LEMBAR 1 (HALAMAN 1) ==================== -->
      <div class="a4-page page-break report-paper bg-white p-8 sm:p-10 shadow-xl border border-slate-200 rounded-xl text-black font-sans print:shadow-none print:border-none print:p-0 print:m-0">
        <!-- KOP SURAT RESMI LEMBAGA -->
        <div class="border-b-[3px] border-black pb-1.5 mb-2.5 font-serif">
          <div class="flex items-center gap-3">
            <div class="w-16 h-16 shrink-0 flex items-center justify-center">
              <img
                :src="props.schoolProfile?.logo_path || '/logo.png'"
                alt="Logo SDI BIM"
                class="w-full h-full object-contain"
              />
            </div>
            <div class="flex-1 text-center text-black leading-tight">
              <h4 class="text-[11px] font-bold uppercase tracking-widest text-slate-800 font-sans">
                {{ props.schoolProfile?.yayasan || 'YAYASAN BINA INSAN MANDIRI BANYUMAS' }}
              </h4>
              <h1 class="text-sm sm:text-base font-black uppercase tracking-wide text-slate-900 mt-0.5 font-sans">
                {{ props.schoolProfile?.nama_sekolah || 'SEKOLAH DASAR ISLAM BINA INSAN MANDIRI PURWOKERTO' }}
              </h1>
              <p class="text-[9.5px] font-sans mt-0.5 text-slate-800">
                {{ props.schoolProfile?.alamat || 'Jalan Wadas Kelir RT 04 RW 05 Kelurahan Karangklesem' }}
              </p>
              <p class="text-[9px] font-sans text-slate-700">
                Kec. Purwokerto Selatan Kab. Banyumas Kode Pos 53144 Telp. {{ props.schoolProfile?.telepon || '(0281) 7782489' }}
              </p>
            </div>
          </div>
          <!-- Garis Tipis Kop Surat -->
          <div class="border-t border-black mt-1"></div>
        </div>

        <!-- Judul Dokumen Halaman 1 -->
        <div class="text-center my-2 font-sans">
          <h2 class="text-sm sm:text-base font-black tracking-wide uppercase leading-tight">
            LAPORAN PERKEMBANGAN
          </h2>
          <h2 class="text-sm sm:text-base font-black tracking-wide uppercase leading-tight">
            PENGUATAN KARAKTER DAN LIFE SKILL
          </h2>
          <div class="inline-block border-b-2 border-black pb-0.5 mt-0.5">
            <h3 class="text-xs sm:text-sm font-black tracking-wider uppercase">
              {{ displayPeriodeTitle }}
            </h3>
          </div>
        </div>

        <!-- Kotak Identitas Siswa (Rounded Border Biru seperti foto dokumen) -->
        <div class="border-2 border-sky-500 rounded-xl p-2.5 mb-3 text-[11px] font-sans">
          <div class="grid grid-cols-2 gap-x-4 gap-y-1.5">
            <!-- Kolom Kiri: Nama, NIS/NISN, Kelas -->
            <div class="space-y-1.5">
              <div class="flex items-center">
                <span class="w-16 font-bold text-slate-800 shrink-0">Nama</span>
                <span class="mr-2 font-bold">:</span>
                <div class="flex-1 border border-sky-400 rounded-md px-2 py-0.5 font-bold text-slate-900 bg-white truncate">
                  {{ props.activeReportCard.student?.nama_lengkap }}
                </div>
              </div>
              <div class="flex items-center">
                <span class="w-16 font-bold text-slate-800 shrink-0">NIS/NISN</span>
                <span class="mr-2 font-bold">:</span>
                <div class="flex-1 border border-sky-400 rounded-md px-2 py-0.5 font-semibold text-slate-900 bg-white">
                  {{ studentNisnOnly }}
                </div>
              </div>
              <div class="flex items-center">
                <span class="w-16 font-bold text-slate-800 shrink-0">Kelas</span>
                <span class="mr-2 font-bold">:</span>
                <div class="flex-1 border border-sky-400 rounded-md px-2 py-0.5 font-semibold text-slate-900 bg-white">
                  {{ formattedClass }}
                </div>
              </div>
            </div>

            <!-- Kolom Kanan: Semester, Fase, Tahun Ajaran -->
            <div class="space-y-1.5">
              <div class="flex items-center">
                <span class="w-20 font-bold text-slate-800 shrink-0">Semester</span>
                <span class="mr-2 font-bold">:</span>
                <div class="flex-1 border border-sky-400 rounded-md px-2 py-0.5 font-semibold text-slate-900 bg-white">
                  {{ props.activeReportCard.semester === 'Gasal' ? 'I (Ganjil)' : 'II (Genap)' }}
                </div>
              </div>
              <div class="flex items-center">
                <span class="w-20 font-bold text-slate-800 shrink-0">Fase</span>
                <span class="mr-2 font-bold">:</span>
                <div class="flex-1 border border-sky-400 rounded-md px-2 py-0.5 font-bold text-slate-900 bg-white">
                  {{ props.activeReportCard.fase || 'A' }}
                </div>
              </div>
              <div class="flex items-center">
                <span class="w-20 font-bold text-slate-800 shrink-0">Tahun Ajaran</span>
                <span class="mr-2 font-bold">:</span>
                <div class="flex-1 border border-sky-400 rounded-md px-2 py-0.5 font-semibold text-slate-900 bg-white">
                  {{ props.activeReportCard.academic_year?.nama || '2025/2026' }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabel Halaman 1 (5 Butir Pertama Aspek Sikap dengan 2 Sub-kolom) -->
        <div class="mb-4">
          <table class="w-full border-collapse border border-black text-[11px] font-sans">
            <thead>
              <tr class="bg-slate-200 text-center font-bold text-slate-900">
                <th class="border border-black py-1.5 px-1 w-8">No</th>
                <th class="border border-black py-1.5 px-2 w-24">Aspek Penguatan dan Program</th>
                <th colspan="2" class="border border-black py-1.5 px-2 text-center">Aspek Sikap Penguatan Karakter dan Life Skill</th>
                <th class="border border-black py-1.5 px-2 text-center">Pelaksanaan Implementasi</th>
                <th class="border border-black py-1.5 px-1 w-12 text-center text-slate-900 font-bold">Nilai</th>
              </tr>
            </thead>
            <tbody>
              <!-- Baris 1: Kolom 3 berisi Emosi & Karakter yang Kuat (Emotional & Spiritual Quotient) dengan rowspan 5 -->
              <tr>
                <td rowspan="5" class="border border-black py-2 px-1 text-center align-top font-bold">1</td>
                <td rowspan="5" class="border border-black py-2 px-2 text-center align-top font-bold text-xs bg-slate-50/40">
                  Aspek Sikap
                </td>
                <td rowspan="5" class="border border-black py-2 px-2 align-top font-bold text-[10.5px] leading-snug w-36">
                  Emosi & Karakter yang Kuat (Emotional & Spiritual Quotient)
                </td>
                <td class="border border-black py-2 px-2 align-top font-medium leading-snug w-48">
                  {{ page1Items[0]?.sub_aspek }}
                </td>
                <td class="border border-black py-2 px-2.5 align-top leading-relaxed text-justify text-[10.5px]">
                  {{ page1Items[0]?.capaian }}
                </td>
                <td class="border border-black py-2 px-1 text-center align-top font-black text-slate-900 text-xs">
                  {{ page1Items[0]?.predikat }}
                </td>
              </tr>

              <!-- Baris 2 s/d 5: Butir 2-5 Aspek Sikap di bawah Emosi & Karakter yang Kuat (termasuk Akhlakul Karimah) -->
              <tr v-for="(item, idx) in page1Items.slice(1)" :key="'p1-' + idx">
                <td class="border border-black py-2 px-2 align-top font-medium leading-snug w-48">
                  {{ item.sub_aspek }}
                </td>
                <td class="border border-black py-2 px-2.5 align-top leading-relaxed text-justify text-[10.5px]">
                  {{ item.capaian }}
                </td>
                <td class="border border-black py-2 px-1 text-center align-top font-black text-slate-900 text-xs">
                  {{ item.predikat }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Footer Halaman 1 -->
        <div class="border-t border-slate-300 pt-2 flex items-center justify-between text-[10px] text-slate-600 font-sans mt-auto">
          <span>Sekolah Dasar Islam Bina Insan Mandiri Purwokerto</span>
          <span class="font-semibold text-slate-800">{{ studentNickname }}</span>
          <span class="font-bold">1</span>
        </div>
      </div>

      <!-- ==================== LEMBAR 2 (HALAMAN 2) ==================== -->
      <div class="a4-page page-break report-paper bg-white p-8 sm:p-10 shadow-xl border border-slate-200 rounded-xl text-black font-sans print:shadow-none print:border-none print:p-0 print:m-0">
        <!-- Tabel Halaman 2: 4 Butir Sisa Aspek Sikap + 5 Butir Pertama Aspek Pengetahuan -->
        <div class="mb-4">
          <table class="w-full border-collapse border border-black text-[11px] font-sans">
            <thead>
              <tr class="bg-slate-200 text-center font-bold text-slate-900">
                <th class="border border-black py-1.5 px-1 w-8">No</th>
                <th class="border border-black py-1.5 px-2 w-24">Aspek Penguatan dan Program</th>
                <th colspan="2" class="border border-black py-1.5 px-2 text-center">Aspek Sikap Penguatan Karakter dan Life Skill</th>
                <th class="border border-black py-1.5 px-2 text-center">Pelaksanaan Implementasi</th>
                <th class="border border-black py-1.5 px-1 w-12 text-center text-slate-900 font-bold">Nilai</th>
              </tr>
            </thead>
            <tbody>
              <!-- Lanjutan No 1: Aspek Sikap (Butir 6 s/d 9) -->
              <!-- Baris 6: Disiplin Diri (Self Dicipline) dengan rowspan 4 -->
              <tr>
                <td rowspan="4" class="border border-black py-2 px-1 text-center align-top font-bold text-slate-400"></td>
                <td rowspan="4" class="border border-black py-2 px-2 text-center align-top font-bold text-xs bg-slate-50/40 text-slate-400"></td>
                <td rowspan="4" class="border border-black py-2 px-2 align-top font-bold text-[10.5px] leading-snug w-36">
                  Disiplin Diri (Self Dicipline)
                </td>
                <td class="border border-black py-2 px-2 align-top font-medium leading-snug w-48">
                  {{ page2SikapItems[0]?.sub_aspek }}
                </td>
                <td class="border border-black py-2 px-2.5 align-top leading-relaxed text-justify text-[10.5px]">
                  {{ page2SikapItems[0]?.capaian }}
                </td>
                <td class="border border-black py-2 px-1 text-center align-top font-black text-slate-900 text-xs">
                  {{ page2SikapItems[0]?.predikat }}
                </td>
              </tr>

              <!-- Baris 7, 8, 9: Manajemen waktu, Konsisten, Fleksibilitas (Hanya kolom butir karena kolom kiri tercakup oleh rowspan 4) -->
              <tr v-for="(item, sIdx) in page2SikapItems.slice(1)" :key="'p2-sikap-' + sIdx">
                <td class="border border-black py-2 px-2 align-top font-medium leading-snug w-48">
                  {{ item.sub_aspek }}
                </td>
                <td class="border border-black py-2 px-2.5 align-top leading-relaxed text-justify text-[10.5px]">
                  {{ item.capaian }}
                </td>
                <td class="border border-black py-2 px-1 text-center align-top font-black text-slate-900 text-xs">
                  {{ item.predikat }}
                </td>
              </tr>

              <!-- No 2: Aspek Pengetahuan (Butir 10 s/d 14 di Halaman 2) -->
              <!-- Baris 10: Belajar Efektif (Effective Learning) dengan rowspan 3 -->
              <tr>
                <td rowspan="5" class="border border-black py-2 px-1 text-center align-top font-bold">2</td>
                <td rowspan="5" class="border border-black py-2 px-2 text-center align-top font-bold text-xs bg-slate-50/40">
                  Aspek Pengetahuan
                </td>
                <td rowspan="3" class="border border-black py-2 px-2 align-top font-bold text-[10.5px] leading-snug w-36">
                  Belajar Efektif (Effective Learning)
                </td>
                <td class="border border-black py-2 px-2 align-top font-medium leading-snug w-48">
                  {{ page2PengetahuanItems[0]?.sub_aspek }}
                </td>
                <td class="border border-black py-2 px-2.5 align-top leading-relaxed text-justify text-[10.5px]">
                  {{ page2PengetahuanItems[0]?.capaian }}
                </td>
                <td class="border border-black py-2 px-1 text-center align-top font-black text-slate-900 text-xs">
                  {{ page2PengetahuanItems[0]?.predikat }}
                </td>
              </tr>

              <!-- Baris 11 & 12: Membaca buku, Berfikir kritis -->
              <tr v-for="(item, pIdx) in page2PengetahuanItems.slice(1, 3)" :key="'p2-peng1-' + pIdx">
                <td class="border border-black py-2 px-2 align-top font-medium leading-snug w-48">
                  {{ item.sub_aspek }}
                </td>
                <td class="border border-black py-2 px-2.5 align-top leading-relaxed text-justify text-[10.5px]">
                  {{ item.capaian }}
                </td>
                <td class="border border-black py-2 px-1 text-center align-top font-black text-slate-900 text-xs">
                  {{ item.predikat }}
                </td>
              </tr>

              <!-- Baris 13: Komunikasi (Communication) dengan rowspan 2 -->
              <tr>
                <td rowspan="2" class="border border-black py-2 px-2 align-top font-bold text-[10.5px] leading-snug w-36">
                  Komunikasi (Communication)
                </td>
                <td class="border border-black py-2 px-2 align-top font-medium leading-snug w-48">
                  {{ page2PengetahuanItems[3]?.sub_aspek }}
                </td>
                <td class="border border-black py-2 px-2.5 align-top leading-relaxed text-justify text-[10.5px]">
                  {{ page2PengetahuanItems[3]?.capaian }}
                </td>
                <td class="border border-black py-2 px-1 text-center align-top font-black text-slate-900 text-xs">
                  {{ page2PengetahuanItems[3]?.predikat }}
                </td>
              </tr>

              <!-- Baris 14: Persuasi -->
              <tr>
                <td class="border border-black py-2 px-2 align-top font-medium leading-snug w-48">
                  {{ page2PengetahuanItems[4]?.sub_aspek }}
                </td>
                <td class="border border-black py-2 px-2.5 align-top leading-relaxed text-justify text-[10.5px]">
                  {{ page2PengetahuanItems[4]?.capaian }}
                </td>
                <td class="border border-black py-2 px-1 text-center align-top font-black text-slate-900 text-xs">
                  {{ page2PengetahuanItems[4]?.predikat }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Footer Halaman 2 -->
        <div class="border-t border-slate-300 pt-2 flex items-center justify-between text-[10px] text-slate-600 font-sans mt-auto">
          <span>Sekolah Dasar Islam Bina Insan Mandiri Purwokerto</span>
          <span class="font-semibold text-slate-800">{{ studentNickname }}</span>
          <span class="font-bold">2</span>
        </div>
      </div>

      <!-- ==================== LEMBAR 3 (HALAMAN 3) ==================== -->
      <div class="a4-page report-paper bg-white p-8 sm:p-10 shadow-xl border border-slate-200 rounded-xl text-black font-sans print:shadow-none print:border-none print:p-0 print:m-0">
        <!-- Tabel Halaman 3: 2 Butir Sisa Aspek Pengetahuan + 1 Butir Aspek Keterampilan -->
        <div class="mb-4">
          <table class="w-full border-collapse border border-black text-[11px] font-sans">
            <thead>
              <tr class="bg-slate-200 text-center font-bold text-slate-900">
                <th class="border border-black py-1.5 px-1 w-8">No</th>
                <th class="border border-black py-1.5 px-2 w-24">Aspek Penguatan dan Program</th>
                <th colspan="2" class="border border-black py-1.5 px-2 text-center">Aspek Sikap Penguatan Karakter dan Life Skill</th>
                <th class="border border-black py-1.5 px-2 text-center">Pelaksanaan Implementasi</th>
                <th class="border border-black py-1.5 px-1 w-12 text-center text-slate-900 font-bold">Nilai</th>
              </tr>
            </thead>
            <tbody>
              <!-- Lanjutan No 2: Aspek Pengetahuan (Butir 15 s/d 16: Komunikasi) -->
              <!-- Baris 15: Membangun networking di bawah Komunikasi (Communication) -->
              <tr>
                <td rowspan="2" class="border border-black py-2 px-1 text-center align-top font-bold text-slate-400"></td>
                <td rowspan="2" class="border border-black py-2 px-2 text-center align-top font-bold text-xs bg-slate-50/40 text-slate-400"></td>
                <td rowspan="2" class="border border-black py-2 px-2 align-top font-bold text-[10.5px] leading-snug w-36">
                  Komunikasi (Communication)
                </td>
                <td class="border border-black py-2 px-2 align-top font-medium leading-snug w-48">
                  {{ page3PengetahuanItems[0]?.sub_aspek }}
                </td>
                <td class="border border-black py-2 px-2.5 align-top leading-relaxed text-justify text-[10.5px]">
                  {{ page3PengetahuanItems[0]?.capaian }}
                </td>
                <td class="border border-black py-2 px-1 text-center align-top font-black text-slate-900 text-xs">
                  {{ page3PengetahuanItems[0]?.predikat }}
                </td>
              </tr>

              <!-- Baris 16: Belajar bahasa asing di bawah Komunikasi (Communication) -->
              <tr>
                <td class="border border-black py-2 px-2 align-top font-medium leading-snug w-48">
                  {{ page3PengetahuanItems[1]?.sub_aspek }}
                </td>
                <td class="border border-black py-2 px-2.5 align-top leading-relaxed text-justify text-[10.5px]">
                  {{ page3PengetahuanItems[1]?.capaian }}
                </td>
                <td class="border border-black py-2 px-1 text-center align-top font-black text-slate-900 text-xs">
                  {{ page3PengetahuanItems[1]?.predikat }}
                </td>
              </tr>

              <!-- No 3: Aspek Keterampilan (Butir 17) -->
              <tr>
                <td class="border border-black py-2 px-1 text-center align-top font-bold">3</td>
                <td class="border border-black py-2 px-2 text-center align-top font-bold text-xs bg-slate-50/40">
                  Aspek Keterampilan
                </td>
                <td class="border border-black py-2 px-2 align-top font-bold text-[10.5px] leading-snug w-36">
                  Penguasaan Teknologi dan Keterampilan Hidup (Technology Mastery and Life Skill)
                </td>
                <td class="border border-black py-2 px-2 align-top font-medium leading-snug w-48">
                  {{ page3KeterampilanItems[0]?.sub_aspek }}
                </td>
                <td class="border border-black py-2 px-2.5 align-top leading-relaxed text-justify text-[10.5px]">
                  {{ page3KeterampilanItems[0]?.capaian }}
                </td>
                <td class="border border-black py-2 px-1 text-center align-top font-black text-slate-900 text-xs">
                  {{ page3KeterampilanItems[0]?.predikat }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Tabel INTERVAL NILAI (Persis Foto User) -->
        <div class="mb-5 font-sans">
          <h4 class="font-extrabold text-xs uppercase tracking-wider mb-1.5 text-slate-900">
            INTERVAL NILAI
          </h4>
          <table class="w-80 border-collapse border border-black text-[11px]">
            <thead>
              <tr class="bg-slate-200 text-center font-bold text-slate-900">
                <th class="border border-black py-1 px-2 w-32">Interval Nilai</th>
                <th class="border border-black py-1 px-2 w-16">Predikat</th>
                <th class="border border-black py-1 px-2">Keterangan</th>
              </tr>
            </thead>
            <tbody class="text-center font-semibold">
              <tr>
                <td class="border border-black py-1 px-2">&gt; 92-100</td>
                <td class="border border-black py-1 px-2 font-black">A</td>
                <td class="border border-black py-1 px-2">Sangat Baik</td>
              </tr>
              <tr>
                <td class="border border-black py-1 px-2">&gt; 83-92</td>
                <td class="border border-black py-1 px-2 font-black">B</td>
                <td class="border border-black py-1 px-2">Baik</td>
              </tr>
              <tr>
                <td class="border border-black py-1 px-2">&ge; 75-83</td>
                <td class="border border-black py-1 px-2 font-black">C</td>
                <td class="border border-black py-1 px-2">Cukup</td>
              </tr>
              <tr>
                <td class="border border-black py-1 px-2">&lt; 75</td>
                <td class="border border-black py-1 px-2 font-black">D</td>
                <td class="border border-black py-1 px-2">Kurang</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Tanda Tangan Resmi Lembaga (Disembunyikan untuk Triwulan 1 & 3 sesuai ketentuan resmi) -->
        <div v-if="!isTriwulan1Or3" class="font-sans text-xs pt-2">
          <div class="text-right mb-4">{{ props.activeReportCard.tempat_tanggal || 'Purwokerto' }}</div>
          <div class="grid grid-cols-3 text-center gap-4">
            <div>
              <p>Mengetahui,</p>
              <p class="font-bold">Orang Tua / Wali Murid</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-36 mx-auto">................................</p>
            </div>
            <div>
              <p>&nbsp;</p>
              <p class="font-bold">Wali Kelas</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-44 mx-auto font-bold">{{ props.activeReportCard.wali_kelas_nama || 'Wali Kelas' }}</p>
              <p v-if="props.activeReportCard.wali_kelas_nip && props.activeReportCard.wali_kelas_nip !== '-'" class="text-[10px] text-slate-700">
                NIP: {{ props.activeReportCard.wali_kelas_nip }}
              </p>
            </div>
            <div>
              <p>Mengetahui,</p>
              <p class="font-bold">Kepala Sekolah</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-48 mx-auto font-bold">{{ props.activeReportCard.kepala_sekolah_nama || 'Kepala Sekolah' }}</p>
              <p v-if="props.activeReportCard.kepala_sekolah_nip && props.activeReportCard.kepala_sekolah_nip !== '-'" class="text-[10px] text-slate-700">
                NIP: {{ props.activeReportCard.kepala_sekolah_nip }}
              </p>
            </div>
          </div>
        </div>

        <!-- Footer Halaman 3 -->
        <div class="border-t border-slate-300 pt-2 flex items-center justify-between text-[10px] text-slate-600 font-sans mt-4">
          <span>Sekolah Dasar Islam Bina Insan Mandiri Purwokerto</span>
          <span class="font-semibold text-slate-800">{{ studentNickname }}</span>
          <span class="font-bold">3</span>
        </div>
      </div>
    </div>

    <!-- (C) FORMAT 3: RAPOR KURIKULUM MERDEKA RESMI -->
    <div v-else-if="props.mode === 'print_raport'" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-10">
      <div class="report-paper bg-white p-8 sm:p-12 shadow-xl border border-slate-200 rounded-xl text-black font-serif print:shadow-none print:border-none print:p-0 print:m-0">
        <!-- KOP SURAT RESMI LEMBAGA -->
        <div class="border-b-[3px] border-black pb-2 mb-3 font-serif">
          <div class="flex items-center gap-4">
            <div class="w-20 h-20 shrink-0 flex items-center justify-center">
              <img
                :src="props.schoolProfile?.logo_path || '/logo.png'"
                alt="Logo SDI BIM"
                class="w-full h-full object-contain"
              />
            </div>
            <div class="flex-1 text-center text-black leading-tight">
              <h4 class="text-xs font-bold uppercase tracking-widest text-slate-800 font-sans">
                {{ props.schoolProfile?.yayasan || 'YAYASAN BINA INSAN MANDIRI' }}
              </h4>
              <h1 class="text-base sm:text-lg font-black uppercase tracking-wide text-slate-900 mt-0.5">
                {{ props.schoolProfile?.nama_sekolah || 'SEKOLAH DASAR ISLAM BINA INSAN MANDIRI PURWOKERTO' }}
              </h1>
              <p class="text-[10.5px] font-sans mt-0.5 font-semibold text-slate-800">
                NPSN: {{ props.schoolProfile?.npsn || '70036695' }} &bull; NSS: {{ props.schoolProfile?.nss || '102030405060' }}
              </p>
              <p class="text-[10px] font-sans mt-0.5 text-slate-800">
                {{ props.schoolProfile?.alamat || 'Jl. Wadas Kelir RT.04 RW.05 Kel. Karangklesem, Kec. Purwokerto Selatan, Kab. Banyumas 53144' }}
              </p>
              <p class="text-[9.5px] font-sans text-slate-700">
                Telp: {{ props.schoolProfile?.telepon || '0281-635812' }} &bull; Email: {{ props.schoolProfile?.email || 'sdibim.pwt@gmail.com' }} &bull; Website: {{ props.schoolProfile?.website || 'https://sdibim.sch.id' }}
              </p>
            </div>
          </div>
          <!-- Garis Ganda Kop Surat -->
          <div class="border-t border-black mt-1.5"></div>
        </div>

        <!-- Judul Dokumen -->
        <div class="text-center my-3">
          <h2 class="text-base font-black uppercase tracking-wider font-sans underline decoration-1 underline-offset-4">
            LAPORAN HASIL BELAJAR (RAPOR)
          </h2>
          <p class="text-xs font-bold uppercase tracking-wider font-sans text-slate-800 mt-1">
            KURIKULUM MERDEKA &bull; {{ (props.activeReportCard.periode || (props.activeReportCard.semester === 'Gasal' ? 'Semester Gasal' : 'Semester Genap')).toUpperCase() }}
          </p>
        </div>

        <!-- Student Meta Box -->
        <div class="grid grid-cols-2 gap-x-8 gap-y-1 text-xs mb-5 font-sans border border-black p-2.5 bg-slate-50/20">
          <div class="flex"><span class="w-28 text-slate-700 font-medium">Nama Peserta Didik</span><span class="mr-2">:</span><strong class="uppercase text-slate-900">{{ props.activeReportCard.student?.nama_lengkap }}</strong></div>
          <div class="flex"><span class="w-28 text-slate-700 font-medium">Kelas / Rombel</span><span class="mr-2">:</span><strong class="text-slate-900">{{ props.activeReportCard.school_class?.nama || '-' }}</strong></div>
          <div class="flex"><span class="w-28 text-slate-700 font-medium">NISN / NIS</span><span class="mr-2">:</span><span>{{ props.activeReportCard.student?.nisn || '-' }} / {{ props.activeReportCard.student?.nis || '-' }}</span></div>
          <div class="flex"><span class="w-28 text-slate-700 font-medium">Fase</span><span class="mr-2">:</span><span class="font-bold">Fase {{ props.activeReportCard.fase || 'A' }}</span></div>
          <div class="flex"><span class="w-28 text-slate-700 font-medium">Semester</span><span class="mr-2">:</span><span>{{ props.activeReportCard.semester }}</span></div>
          <div class="flex"><span class="w-28 text-slate-700 font-medium">Tahun Pelajaran</span><span class="mr-2">:</span><span>{{ props.activeReportCard.academic_year?.nama || '2025/2026' }}</span></div>
        </div>

        <!-- A. Nilai Intrakurikuler -->
        <div class="mb-5">
          <h4 class="font-bold text-xs uppercase tracking-wider mb-2 font-sans border-b border-black pb-1">
            A. Nilai Intrakurikuler
          </h4>
          <table class="w-full border-collapse border border-black text-xs font-sans">
            <thead>
              <tr class="bg-slate-100 text-center font-bold">
                <th class="border border-black py-2 px-2 w-8">No.</th>
                <th class="border border-black py-2 px-3 text-left w-56">Mata Pelajaran</th>
                <th class="border border-black py-2 px-2 w-20">Nilai Akhir</th>
                <th class="border border-black py-2 px-3 text-left">Capaian Kompetensi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(g, gIdx) in props.activeReportCard.grades" :key="gIdx">
                <td class="border border-black py-1.5 px-2 text-center align-top">{{ gIdx + 1 }}</td>
                <td class="border border-black py-1.5 px-3 align-top font-semibold">{{ g.nama_mapel }}</td>
                <td class="border border-black py-1.5 px-2 text-center align-top font-bold">{{ g.nilai_akhir ?? '-' }}</td>
                <td class="border border-black py-1.5 px-3 align-top text-[11px] leading-relaxed text-justify">
                  {{ g.capaian_kompetensi || '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- B. Kokurikuler (P5) -->
        <div class="mb-5 font-sans">
          <h4 class="font-bold text-xs uppercase tracking-wider mb-2 border-b border-black pb-1">
            B. Projek Penguatan Profil Pelajar Pancasila (P5)
          </h4>
          <div class="border border-black p-3.5 text-xs leading-relaxed space-y-1">
            <div class="font-bold text-slate-900">
              Tema Projek: {{ props.activeReportCard.kokurikuler_tema || 'Kearifan Lokal dan Gaya Hidup Berkelanjutan' }}
            </div>
            <div v-if="props.activeReportCard.kokurikuler_dimensi" class="text-[11px] font-semibold text-slate-700">
              Dimensi: {{ props.activeReportCard.kokurikuler_dimensi }}
            </div>
            <p class="text-[11px] text-justify pt-1 leading-relaxed">
              {{ props.activeReportCard.kokurikuler_deskripsi || 'Ananda telah berpartisipasi aktif dalam kegiatan projek kokurikuler dan menunjukkan capaian berkembang sesuai harapan dalam kerja sama dan kemandirian.' }}
            </p>
          </div>
        </div>

        <!-- C. Ekstrakurikuler -->
        <div class="mb-5 font-sans">
          <h4 class="font-bold text-xs uppercase tracking-wider mb-2 border-b border-black pb-1">
            C. Ekstrakurikuler
          </h4>
          <table class="w-full border-collapse border border-black text-xs">
            <thead>
              <tr class="bg-slate-100 text-center font-bold">
                <th class="border border-black py-1.5 px-2 w-8">No.</th>
                <th class="border border-black py-1.5 px-3 text-left w-52">Kegiatan Ekstrakurikuler</th>
                <th class="border border-black py-1.5 px-2 text-center w-24">Predikat</th>
                <th class="border border-black py-1.5 px-3 text-left">Keterangan / Capaian</th>
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

        <!-- D & E: Ketidakhadiran & Catatan Wali Kelas -->
        <div class="grid grid-cols-2 gap-5 mb-5 font-sans">
          <div>
            <h4 class="font-bold text-xs uppercase tracking-wider mb-2 border-b border-black pb-1">
              D. Ketidakhadiran
            </h4>
            <table class="w-full border-collapse border border-black text-xs">
              <tbody>
                <tr>
                  <td class="border border-black py-1 px-3 w-36">Sakit</td>
                  <td class="border border-black py-1 px-3 font-semibold">: {{ props.activeReportCard.sakit }} hari</td>
                </tr>
                <tr>
                  <td class="border border-black py-1 px-3">Izin</td>
                  <td class="border border-black py-1 px-3 font-semibold">: {{ props.activeReportCard.izin }} hari</td>
                </tr>
                <tr>
                  <td class="border border-black py-1 px-3">Tanpa Keterangan</td>
                  <td class="border border-black py-1 px-3 font-semibold">: {{ props.activeReportCard.alpa }} hari</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div>
            <h4 class="font-bold text-xs uppercase tracking-wider mb-2 border-b border-black pb-1">
              E. Catatan Wali Kelas
            </h4>
            <div class="border border-black p-3 text-xs italic leading-relaxed min-h-[75px] text-justify">
              {{ props.activeReportCard.catatan_wali_kelas || 'Pertahankan prestasimu dan terus bersemangat dalam menuntut ilmu. Baarakallahu fiik.' }}
            </div>
          </div>
        </div>

        <!-- Lembar Tanda Tangan (3 Kolom Resmi) -->
        <div class="font-sans text-xs pt-3">
          <div class="text-right mb-4">{{ props.activeReportCard.tempat_tanggal || 'Purwokerto' }}</div>
          <div class="grid grid-cols-3 text-center gap-4">
            <div>
              <p>Mengetahui,</p>
              <p class="font-bold">Orang Tua / Wali Murid</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-36 mx-auto">................................</p>
            </div>
            <div>
              <p>&nbsp;</p>
              <p class="font-bold">Wali Kelas</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-44 mx-auto font-bold">{{ props.activeReportCard.wali_kelas_nama || 'Wali Kelas' }}</p>
              <p v-if="props.activeReportCard.wali_kelas_nip && props.activeReportCard.wali_kelas_nip !== '-'" class="text-[10px] text-slate-700">
                NIP: {{ props.activeReportCard.wali_kelas_nip }}
              </p>
            </div>
            <div>
              <p>Mengetahui,</p>
              <p class="font-bold">Kepala Sekolah</p>
              <div class="h-16"></div>
              <p class="border-t border-black pt-1 w-48 mx-auto font-bold">{{ props.activeReportCard.kepala_sekolah_nama || 'Kepala Sekolah' }}</p>
              <p v-if="props.activeReportCard.kepala_sekolah_nip && props.activeReportCard.kepala_sekolah_nip !== '-'" class="text-[10px] text-slate-700">
                NIP: {{ props.activeReportCard.kepala_sekolah_nip }}
              </p>
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
    margin: 8mm 12mm 8mm 12mm;
  }

  aside,
  nav,
  header,
  .no-print,
  .print\:hidden,
  .print-hidden,
  button {
    display: none !important;
  }

  html,
  body,
  #app,
  main {
    background: #ffffff !important;
    color: #000000 !important;
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    min-width: 100% !important;
    box-shadow: none !important;
    border: none !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  .print-container {
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
  }

  .report-paper {
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
  }

  .a4-page {
    box-sizing: border-box !important;
    width: 100% !important;
    height: auto !important;
    min-height: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
    page-break-inside: avoid !important;
    break-inside: avoid !important;
  }

  .page-break {
    page-break-after: always !important;
    break-after: page !important;
  }

  table {
    border-collapse: collapse !important;
    width: 100% !important;
  }

  tr {
    page-break-inside: avoid !important;
    break-inside: avoid !important;
  }

  thead {
    display: table-header-group;
  }
}
</style>
