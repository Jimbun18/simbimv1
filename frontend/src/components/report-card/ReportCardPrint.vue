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
    let capaian = existing?.capaian_kompetensi || ''
    if (capaian && !capaian.includes(nick)) {
      capaian = capaian.replace(/ananda/gi, `ananda ${nick}`)
    }
    const predikat = existing?.predikat || '-'
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
// 5 Klaster Tema Rapor Triwulan Tematik Terpadu BIM (Abrisam 1 / Triwulan 3)
const DEFAULT_5_TEMAS = [
  {
    tema: 'Mathematics and Sciences',
    nama: 'Matematika dan Ilmu Pengetahuan Alam',
  },
  {
    tema: 'Art, Language, and Indonesian Cultures',
    nama: 'Seni Budaya Bahasa (Bahasa Jawa, Bahasa Indonesia, Bahasa Inggris, dan Bahasa Arab)',
  },
  {
    tema: 'Social Sciences and Citizenship',
    nama: 'Ilmu Pengetahuan Sosial, Pendidikan Pancasila dan Kewarganegaraan',
  },
  {
    tema: 'Physical Education, Health and Life Skills',
    nama: 'Pendidikan Jasmani Olahraga dan Kesehatan, Keterampilan Hidup',
  },
  {
    tema: 'Islamic Education',
    nama: "Quran Hadits\nFiqih\nAkidah Akhlak\nSejarah Kebudayaan Islam",
  },
]

const resolvedTematicGrades = computed(() => {
  const grades = props.activeReportCard?.grades || []
  return DEFAULT_5_TEMAS.map((t, idx) => {
    const existing = grades.find((g) => 
      (g.tema && g.tema.toLowerCase() === t.tema.toLowerCase()) || 
      (g.nama_mapel && g.nama_mapel.toLowerCase().includes(t.tema.toLowerCase())) ||
      g.urutan === idx + 1
    ) || grades[idx]
    return {
      urutan: idx + 1,
      tema: existing?.tema || t.tema,
      nama_mapel: existing?.nama_mapel || t.nama,
      nilai_akhir: existing?.nilai_akhir ?? null,
      predikat: existing?.predikat || null,
      capaian_kompetensi: existing?.capaian_kompetensi || null,
    }
  })
})

// Pagination dinamis cerdas: pastikan halaman sebelumnya sudah penuh dulu baru nambah halaman
interface TematicPage {
  pageNumber: number
  grades: Array<{
    urutan: number
    tema: string
    nama_mapel: string
    nilai_akhir: number | null
    predikat: string | null
    capaian_kompetensi: string | null
  }>
  isFirstPage: boolean
}

const tematicPages = computed(() => {
  const allGrades = resolvedTematicGrades.value
  const pages: TematicPage[] = []

  // Hitung tinggi riil baris tema berdasarkan judul, muatan pelajaran, dan panjang narasi teks
  const calculateRowHeight = (item: (typeof allGrades)[0]) => {
    // Tinggi minimal (base height) per tema saat teks capaian kosong atau singkat
    const baseHeights: Record<number, number> = {
      1: 52, // Mathematics and Sciences
      2: 72, // Art, Language, and Indonesian Cultures
      3: 56, // Social Sciences and Citizenship
      4: 64, // Physical Education, Health and Life Skills
      5: 76, // Islamic Education (4 mata pelajaran)
    }
    const baseH = baseHeights[item.urutan] || 60

    const text = (item.capaian_kompetensi || '').trim()
    if (text.length <= 2) {
      return baseH
    }

    // Tiap baris teks di kolom capaian (lebar ~330px) menampung sekitar 54 karakter
    // Tinggi per baris teks ~16.5px + padding tabel ~16px
    const lines = Math.ceil(text.length / 54)
    const capaianH = 16 + lines * 16.5

    return Math.max(baseH, capaianH)
  }

  // Kapasitas tinggi baris tabel A4 (Tinggi total A4 = ~1058px setelah padding):
  // Halaman 1: Dikurangi Kop (~125px) + Judul (~45px) + Identitas (~90px) + Ribbon (~35px) + Header Tabel (~30px) + Subheader Muatan (~25px) + Footer (~30px) + Safety Margin
  // Tersedia kapasitas baris tabel Halaman 1 = 640px
  // Halaman 2 ke atas: Header Tabel (~30px) + Footer (~30px) + Safety Margin
  // Tersedia kapasitas baris tabel Halaman 2+ = 920px
  const P1_BUDGET = 640
  const P2_BUDGET = 920

  let currentPageGrades: typeof allGrades = []
  let currentUsedHeight = 0
  let isP1 = true

  for (let i = 0; i < allGrades.length; i++) {
    const item = allGrades[i]
    const rowH = calculateRowHeight(item)
    const maxBudget = isP1 ? P1_BUDGET : P2_BUDGET

    // Cek apakah item ini masih muat di halaman saat ini
    if (currentPageGrades.length === 0 || currentUsedHeight + rowH <= maxBudget) {
      // Masih muat: Masukkan ke halaman saat ini
      currentPageGrades.push(item)
      currentUsedHeight += rowH
    } else {
      // Tidak muat (halaman saat ini sudah penuh terisi maksimal):
      // Finalisasi halaman saat ini lalu buka halaman berikutnya
      pages.push({
        pageNumber: pages.length + 1,
        grades: [...currentPageGrades],
        isFirstPage: isP1,
      })
      currentPageGrades = [item]
      currentUsedHeight = rowH
      isP1 = false
    }
  }

  if (currentPageGrades.length > 0) {
    pages.push({
      pageNumber: pages.length + 1,
      grades: [...currentPageGrades],
      isFirstPage: isP1,
    })
  }

  return pages
})

const totalTematicPages = computed(() => tematicPages.value.length + 1)

// 5 Default Ekstrakurikuler SDI BIM
const DEFAULT_5_EKSTRA = [
  { kegiatan: 'Pramuka', predikat: 'A', keterangan: 'Ananda dapat mengikuti pramuka siaga, menyebutkan dwidarma, lambang pramuka.' },
  { kegiatan: 'Renang', predikat: '-', keterangan: '-' },
  { kegiatan: 'Pencak Silat', predikat: 'A', keterangan: 'Ananda dapat menirukan salam pencak silat, gerakan katak melempar tubuh, tangkisan, ikrar tentang, dan sambaran.' },
  { kegiatan: 'Mading', predikat: 'A', keterangan: 'Ananda sudah baik dalam membuat poster, komik.' },
  { kegiatan: 'Public Speaking', predikat: 'A', keterangan: 'Ananda dapat mengikuti public speaking dengan baik, berani tampil di depan menjadi pembawa acara, pidato dan membaca puisi.' },
]

const resolvedTematicEkstra = computed(() => {
  const ekstras = props.activeReportCard?.extracurriculars || []
  if (ekstras.length > 0) {
    return ekstras
  }
  return DEFAULT_5_EKSTRA
})

// ==================== RAPOR KURIKULUM MERDEKA RESMI (SEMESTER 1 & 2) ====================
const DEFAULT_18_MERDEKA_SUBJECTS = [
  { nama: 'IPS', kelompok: 'Umum' },
  { nama: 'Pendidikan Pancasila', kelompok: 'Umum' },
  { nama: 'Bahasa Indonesia', kelompok: 'Umum' },
  { nama: 'Bahasa Inggris', kelompok: 'Umum' },
  { nama: 'Bahasa Arab', kelompok: 'Umum' },
  { nama: 'Matematika', kelompok: 'Umum' },
  { nama: 'Ilmu Pengetahuan Alam', kelompok: 'Umum' },
  { nama: 'Pendidikan Jasmani Olahraga dan Kesehatan', kelompok: 'Umum' },
  { nama: 'Life Skill', kelompok: 'Umum' },
  { nama: 'Gardening', kelompok: 'Umum' },
  { nama: 'Crafting', kelompok: 'Umum' },
  { nama: 'Ekonomi Jasa', kelompok: 'Umum' },
  { nama: 'Fiqih', kelompok: 'Agama' },
  { nama: "Qur'an Hadist", kelompok: 'Agama' },
  { nama: 'Aqidah Akhlak', kelompok: 'Agama' },
  { nama: 'SKI', kelompok: 'Agama' },
  { nama: 'Seni Rupa', kelompok: 'Umum' },
  { nama: 'Bahasa Jawa', kelompok: 'Muatan Lokal' },
]

const formattedClassRoman = computed(() => {
  const sc = props.activeReportCard?.school_class
  const raw = sc?.tingkat || sc?.nama || '1'
  const match = String(raw).match(/\d+/)
  const num = match ? parseInt(match[0], 10) : 1
  const romans = ['', 'I', 'II', 'III', 'IV', 'V', 'VI']
  return romans[num] || 'I'
})

const resolvedMerdekaGrades = computed(() => {
  const grades = props.activeReportCard?.grades || []
  if (grades.length > 0) {
    return DEFAULT_18_MERDEKA_SUBJECTS.map((def, idx) => {
      const existing = grades.find(
        (g) =>
          (g.nama_mapel && g.nama_mapel.toLowerCase() === def.nama.toLowerCase()) ||
          g.urutan === idx + 1
      ) || grades[idx]
      return {
        urutan: idx + 1,
        nama_mapel: existing?.nama_mapel || def.nama,
        kelompok: existing?.kelompok || def.kelompok,
        nilai_akhir: existing?.nilai_akhir ?? null,
        capaian_kompetensi: existing?.capaian_kompetensi || null,
      }
    })
  }
  return DEFAULT_18_MERDEKA_SUBJECTS.map((def, idx) => ({
    urutan: idx + 1,
    nama_mapel: def.nama,
    kelompok: def.kelompok,
    nilai_akhir: null,
    capaian_kompetensi: null,
  }))
})

const DEFAULT_5_MERDEKA_EKSTRA = [
  { kegiatan: 'Pramuka', keterangan: 'Baik' },
  { kegiatan: 'Renang', keterangan: 'Baik' },
  { kegiatan: 'Mading', keterangan: 'Baik' },
  { kegiatan: 'Pencak Silat', keterangan: 'Baik' },
  { kegiatan: 'Publik Speaking', keterangan: 'Baik' },
]

const resolvedMerdekaEkstra = computed(() => {
  const ekstras = props.activeReportCard?.extracurriculars || []
  if (ekstras.length > 0) {
    return ekstras
  }
  return DEFAULT_5_MERDEKA_EKSTRA
})

const defaultKokurikulerDeskripsi = computed(() => {
  return (
    props.activeReportCard?.kokurikuler_deskripsi ||
    `Ananda ${studentNickname.value} menunjukkan rasa ingin tahu yang sangat tinggi pada setiap kegiatan kokurikuler. Saat kunjungan ke Perpustakaan UIN Saizu, Ananda sangat antusias mencari dan membaca berbagai jenis buku. Pada kegiatan kunjungan Ziarah, Museum Jenderal Soedirman dan gelar karya, Ananda tidak sabar mencoba hal baru serta berani mengeksplorasi setiap sudut tempat untuk menambah wawasannya. Diharapkan rasa ingin tahu dan semangat literasi Ananda terus dibimbing agar menjadi pembelajar yang mandiri, fokus dan berprestasi.`
  )
})

const defaultCatatanWaliKelas = computed(() => {
  return (
    props.activeReportCard?.catatan_wali_kelas ||
    `Selamat, Ananda ${studentNickname.value} menunjukkan sikap belajar yang baik, rajin, disiplin. Emosi sudah mulai stabil dan bisa mengendalikan diri. Yuk, terus semangat dan terus belajar bersabar dan menerima yaa ${studentNickname.value}. Terimakasih Sudah hebat.`
  )
})

interface MerdekaPage {
  pageNumber: number
  grades: Array<{
    urutan: number
    nama_mapel: string
    kelompok: string
    nilai_akhir: number | null
    capaian_kompetensi: string | null
  }>
  isFirstPage: boolean
  hasKokurikulerAndEkstra: boolean
}

const displayStudentAddress = computed(() => {
  const st = props.activeReportCard?.student
  if (st?.alamat && st.alamat.trim() !== '') {
    return st.alamat.trim()
  }
  if (st?.alamat_jalan && st.alamat_jalan.trim() !== '') {
    const parts = [st.alamat_jalan.trim()]
    if (st.rt || st.rw) {
      parts.push(`RT ${st.rt || '-'} RW ${st.rw || '-'}`)
    }
    const sub = st.desa_kelurahan || st.dusun
    if (sub) parts.push(sub)
    if (st.kecamatan) parts.push(st.kecamatan)
    if (st.kab_kota) parts.push(st.kab_kota)
    return parts.join(', ')
  }
  const parts = [st?.dusun, st?.desa_kelurahan, st?.kelurahan, st?.kecamatan, st?.kab_kota].filter(Boolean)
  if (parts.length > 0) {
    return parts.join(', ')
  }
  return 'Griya Satria Bukit Permata Blok E-16'
})

const merdekaPages = computed(() => {
  const allGrades = resolvedMerdekaGrades.value
  const pages: MerdekaPage[] = []

  const calculateMerdekaRowHeight = (item: (typeof allGrades)[0]) => {
    const text = (item.capaian_kompetensi || '').trim()
    if (text.length <= 2) return 36
    const lines = Math.ceil(text.length / 70)
    return 14 + lines * 16.5
  }

  // Kapasitas Halaman 1: Total A4 ~1058px - Header/Meta/Footer ~280px = ~770px
  // Kapasitas Halaman 2+: Total A4 ~1058px - TableHeader/Footer ~80px = ~910px
  const P1_BUDGET = 770
  const P2_BUDGET = 910

  let currentPageGrades: typeof allGrades = []
  let currentUsedHeight = 0
  let isFirst = true

  for (let i = 0; i < allGrades.length; i++) {
    const item = allGrades[i]
    const rowH = calculateMerdekaRowHeight(item)
    const maxBudget = isFirst ? P1_BUDGET : P2_BUDGET

    if (currentPageGrades.length === 0 || currentUsedHeight + rowH <= maxBudget) {
      currentPageGrades.push(item)
      currentUsedHeight += rowH
    } else {
      pages.push({
        pageNumber: pages.length + 1,
        grades: [...currentPageGrades],
        isFirstPage: isFirst,
        hasKokurikulerAndEkstra: false,
      })
      currentPageGrades = [item]
      currentUsedHeight = rowH
      isFirst = false
    }
  }

  if (currentPageGrades.length > 0) {
    const maxBudget = isFirst ? P1_BUDGET : P2_BUDGET
    const remaining = maxBudget - currentUsedHeight
    // Kokurikuler & Ekstrakurikuler butuh ~260px
    const canFitKokurikulerAndEkstra = remaining >= 260

    pages.push({
      pageNumber: pages.length + 1,
      grades: [...currentPageGrades],
      isFirstPage: isFirst,
      hasKokurikulerAndEkstra: canFitKokurikulerAndEkstra,
    })
  }

  return pages
})

const lastMerdekaPageHasKokurikuler = computed(() => {
  const last = merdekaPages.value[merdekaPages.value.length - 1]
  return last?.hasKokurikulerAndEkstra || false
})

const totalMerdekaPages = computed(() => {
  // Jika halaman terakhir tabel sudah memuat Kokurikuler & Ekstra, maka tinggal + 1 halaman pengesahan
  // Jika tidak, maka + 1 atau + 2 sesuai kelengkapan
  return merdekaPages.value.length + 1
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

        <!-- (A) FORMAT 1: RAPOR TRIWULAN TEMATIK TERPADU BIM (MENYESUAIKAN ISI TEKS & RAPIH) -->
    <div v-if="props.mode === 'print_tematik'" class="print-container max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-10 space-y-8 print:space-y-0 print:p-0 print:m-0 print:max-w-none print:w-full">
      
      <!-- LEMBAR TABEL TEMATIK DINAMIS (Menyesuaikan panjang narasi capaian pembelajaran) -->
      <div
        v-for="page in tematicPages"
        :key="'tematic-page-' + page.pageNumber"
        class="a4-page page-break report-paper bg-white p-8 sm:p-10 shadow-xl border border-slate-200 rounded-xl text-black font-sans print:shadow-none print:border-none print:p-0 print:m-0 flex flex-col justify-between min-h-[1050px]"
      >
        <div>
          <!-- KOP SURAT & IDENTITAS HANYA MUNCUL DI HALAMAN PERTAMA (LEMBAR 1) -->
          <template v-if="page.isFirstPage">
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
                    Kec. Purwokerto Selatan Kab. Banyumas Kode Pos 53144 Telp. {{ props.schoolProfile?.telepon || '(0281) 7702469' }}
                  </p>
                </div>
              </div>
              <!-- Garis Tipis Kop Surat -->
              <div class="border-t border-black mt-1"></div>
            </div>

            <!-- Judul Dokumen -->
            <div class="text-center my-2.5 font-sans">
              <h2 class="text-sm sm:text-base font-black tracking-wide uppercase leading-tight">
                LAPORAN HASIL BELAJAR
              </h2>
              <h3 class="text-xs sm:text-sm font-black tracking-wider uppercase mt-0.5">
                {{ (props.activeReportCard.periode || 'TRIWULAN II').toUpperCase() }}
              </h3>
            </div>

            <!-- Kotak Identitas Siswa (Rounded Border Biru) -->
            <div class="border-2 border-sky-500 rounded-xl p-2.5 mb-3 text-[11px] font-sans">
              <div class="grid grid-cols-2 gap-x-4 gap-y-1.5">
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
                      {{ props.activeReportCard.student?.nis || '-' }} / {{ props.activeReportCard.student?.nisn || '-' }}
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
                <div class="space-y-1.5">
                  <div class="flex items-center">
                    <span class="w-20 font-bold text-slate-800 shrink-0">Semester</span>
                    <span class="mr-2 font-bold">:</span>
                    <div class="flex-1 border border-sky-400 rounded-md px-2 py-0.5 font-semibold text-slate-900 bg-white">
                      {{ props.activeReportCard.semester === 'Genap' ? 'II (Genap)' : 'I (Gasal)' }}
                    </div>
                  </div>
                  <div class="flex items-center">
                    <span class="w-20 font-bold text-slate-800 shrink-0">Fase</span>
                    <span class="mr-2 font-bold">:</span>
                    <div class="flex-1 border border-sky-400 rounded-md px-2 py-0.5 font-semibold text-slate-900 bg-white">
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

            <!-- Green Ribbon: MUATAN NASIONAL -->
            <div class="flex items-center gap-2 mb-2">
              <div class="inline-flex items-center gap-1.5 bg-emerald-600 text-white font-extrabold text-[11px] uppercase tracking-wider px-3 py-1 rounded-md shadow-xs">
                <span>🎒</span>
                <span>MUATAN NASIONAL</span>
              </div>
            </div>
          </template>

          <!-- TABEL TEMA (LEBAR MENYESUAIKAN TEKS & RAPIH) -->
          <table class="w-full border-collapse border border-black text-xs font-sans table-auto">
            <colgroup>
              <col class="w-[36px]" />
              <col class="w-[140px]" />
              <col class="w-[165px]" />
              <col class="w-[36px]" />
              <col class="w-[30px]" />
              <col class="w-auto" />
            </colgroup>
            <thead>
              <tr class="bg-[#d9d9d9] text-center font-bold">
                <th class="border border-black py-1.5 px-1 w-[36px] text-center">No</th>
                <th class="border border-black py-1.5 px-2 text-center w-[140px]">Tema</th>
                <th class="border border-black py-1.5 px-2 text-center w-[165px]">Muatan Pelajaran</th>
                <th colspan="2" class="border border-black py-1.5 px-1 text-center w-[66px]">Nilai Akhir</th>
                <th class="border border-black py-1.5 px-2 text-center">Capaian Kompetensi/Tujuan Pembelajaran</th>
              </tr>
            </thead>
            <tbody>
              <!-- Subheader Muatan Nasional (Hanya di Halaman Pertama) -->
              <tr v-if="page.isFirstPage">
                <td colspan="6" class="border border-black py-1 px-2 font-bold text-[11px] bg-white text-left">
                  Muatan Nasional
                </td>
              </tr>
              <tr v-for="(g, idx) in page.grades" :key="'g-' + idx">
                <td class="border border-black py-2 px-1 text-center align-top font-bold text-xs">{{ g.urutan }}</td>
                <td class="border border-black py-2 px-2.5 align-top font-bold text-[11px] leading-snug">{{ g.tema }}</td>
                <td class="border border-black py-2 px-2.5 align-top text-[11px] font-medium leading-snug whitespace-pre-line">{{ g.nama_mapel }}</td>
                <td class="border border-black py-2 px-1 text-center align-middle font-bold text-xs w-[36px]">{{ g.nilai_akhir ?? '-' }}</td>
                <td class="border border-black py-2 px-1 text-center align-middle font-bold text-xs w-[30px]">{{ g.predikat || '-' }}</td>
                <td class="border border-black py-2 px-3 align-top text-[10.5px] leading-relaxed text-justify">
                  {{ g.capaian_kompetensi || '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Footer Halaman Tabel -->
        <div class="border-t border-slate-300 pt-1.5 flex items-center justify-between text-[10px] text-slate-600 font-sans mt-4">
          <span>Sekolah Dasar Islam Bina Insan Mandiri Purwokerto</span>
          <span class="font-semibold text-slate-800">{{ studentNickname }}</span>
          <span class="font-bold text-slate-800">{{ page.pageNumber }}</span>
        </div>
      </div>

      <!-- ==================== LEMBAR TERAKHIR (EKSTRAKURIKULER, CATATAN, ABSENSI & TTD) ==================== -->
      <div class="a4-page page-break report-paper bg-white p-8 sm:p-10 shadow-xl border border-slate-200 rounded-xl text-black font-sans print:shadow-none print:border-none print:p-0 print:m-0 flex flex-col justify-between min-h-[1050px]">
        <div class="space-y-4">
          <!-- Yellow Ribbon: EKSTRAKURIKULER -->
          <div class="flex items-center gap-2">
            <div class="inline-flex items-center gap-1.5 bg-amber-400 text-amber-950 font-black text-[11px] uppercase tracking-wider px-3 py-1 rounded-md shadow-xs border border-amber-300">
              <span>🏅</span>
              <span>EKSTRAKURIKULER</span>
            </div>
          </div>

          <!-- Tabel Ekstrakurikuler -->
          <table class="w-full border-collapse border border-black text-xs font-sans">
            <thead>
              <tr class="bg-[#d9d9d9] text-center font-bold">
                <th class="border border-black py-1.5 px-2 w-8 text-center">No</th>
                <th class="border border-black py-1.5 px-3 text-left w-40">Ekstrakurikuler</th>
                <th class="border border-black py-1.5 px-2 text-center w-20">Predikat</th>
                <th class="border border-black py-1.5 px-3 text-left">Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(e, eIdx) in resolvedTematicEkstra" :key="'ekstra-' + eIdx">
                <td class="border border-black py-1.5 px-2 text-center">{{ eIdx + 1 }}</td>
                <td class="border border-black py-1.5 px-3 font-semibold">{{ e.kegiatan }}</td>
                <td class="border border-black py-1.5 px-2 text-center font-bold">{{ e.predikat || '-' }}</td>
                <td class="border border-black py-1.5 px-3 text-[11px]">{{ e.keterangan || '-' }}</td>
              </tr>
            </tbody>
          </table>

          <!-- Catatan Guru Box -->
          <div class="border border-black font-sans text-xs">
            <div class="bg-[#d9d9d9] font-bold px-3 py-1.5 border-b border-black text-[11px]">
              Catatan Guru
            </div>
            <div class="p-3 text-[11px] leading-relaxed text-justify min-h-[48px]">
              {{ props.activeReportCard.catatan_wali_kelas || 'Pertahankan prestasi. Lebih sungguh-sungguh lagi dalam belajar. Baarakallahu fiik.' }}
            </div>
          </div>

          <!-- Tabel ABSENSI (Kiri Bawah) -->
          <div class="w-64 font-sans text-xs">
            <table class="w-full border-collapse border border-black">
              <thead>
                <tr class="bg-[#d9d9d9] text-center font-bold">
                  <th colspan="3" class="border border-black py-1 px-2 text-center uppercase tracking-wider text-[11px]">
                    ABSENSI
                  </th>
                </tr>
                <tr class="bg-[#d9d9d9] text-center font-bold text-[10.5px]">
                  <th class="border border-black py-1 px-1.5 w-8">No</th>
                  <th class="border border-black py-1 px-3 text-left">Alasan Ketidakhadiran</th>
                  <th class="border border-black py-1 px-2 text-center w-16">Jumlah</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="border border-black py-1 px-1.5 text-center">1</td>
                  <td class="border border-black py-1 px-3">Sakit</td>
                  <td class="border border-black py-1 px-2 text-center font-bold">{{ props.activeReportCard.sakit ?? '-' }}</td>
                </tr>
                <tr>
                  <td class="border border-black py-1 px-1.5 text-center">2</td>
                  <td class="border border-black py-1 px-3">Izin</td>
                  <td class="border border-black py-1 px-2 text-center font-bold">{{ props.activeReportCard.izin ?? '-' }}</td>
                </tr>
                <tr>
                  <td class="border border-black py-1 px-1.5 text-center">3</td>
                  <td class="border border-black py-1 px-3">Tanpa Keterangan</td>
                  <td class="border border-black py-1 px-2 text-center font-bold">{{ props.activeReportCard.alpa ?? '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Titimangsa & Pengesahan Tanda Tangan -->
          <div class="font-sans text-xs pt-2">
            <div class="text-right pr-6 font-semibold mb-2">
              {{ props.activeReportCard.tempat_tanggal || 'Purwokerto, 4 April 2026' }}
            </div>

            <div class="grid grid-cols-2 text-center mb-6">
              <div>
                <p class="font-medium">Orang Tua</p>
                <div class="h-16"></div>
                <p class="border-b border-black w-44 mx-auto"></p>
              </div>
              <div>
                <p class="font-medium">Wali Kelas</p>
                <div class="h-16"></div>
                <p class="font-bold border-b border-black w-48 mx-auto">
                  {{ props.activeReportCard.wali_kelas_nama || 'Wali Kelas, S.Pd' }}
                </p>
              </div>
            </div>

            <div class="text-center">
              <p class="text-[11px] text-slate-700">Mengetahui,</p>
              <p class="font-bold">PLT Kepala Sekolah</p>
              <div class="h-16"></div>
              <p class="font-bold underline w-56 mx-auto">
                {{ props.activeReportCard.kepala_sekolah_nama || 'Yuntatul Inayah, A.Md., S.Pd' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Footer Lembar Terakhir -->
        <div class="border-t border-slate-300 pt-1.5 flex items-center justify-between text-[10px] text-slate-600 font-sans mt-4">
          <span>Sekolah Dasar Islam Bina Insan Mandiri Purwokerto</span>
          <span class="font-semibold text-slate-800">{{ studentNickname }}</span>
          <span class="font-bold text-slate-800">{{ totalTematicPages }}</span>
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

    <!-- (C) FORMAT 3: RAPOR KURIKULUM MERDEKA RESMI (PERSIS DOKUMEN RESMI RAPOR SEMESTER 1) -->
    <div v-else-if="props.mode === 'print_raport'" class="print-container max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-10 space-y-8 print:space-y-0 print:p-0 print:m-0 print:max-w-none print:w-full">
      
      <!-- LEMBAR TABEL MAPEL MERDEKA DINAMIS (Halaman penuh dulu baru nambah halaman) -->
      <div
        v-for="page in merdekaPages"
        :key="'merdeka-page-' + page.pageNumber"
        class="a4-page page-break report-paper bg-white p-8 sm:p-10 shadow-xl border border-slate-200 rounded-xl text-black font-sans print:shadow-none print:border-none print:p-0 print:m-0 flex flex-col justify-between min-h-[1050px]"
      >
        <div>
          <!-- JUDUL & DATA SISWA HANYA DI HALAMAN 1 (Sesuai rapor semester 1.pdf) -->
          <template v-if="page.isFirstPage">
            <!-- Judul Dokumen Resmi -->
            <div class="text-center mb-6 pt-2">
              <h1 class="text-base sm:text-lg font-black uppercase tracking-wider text-black">
                LAPORAN HASIL BELAJAR
              </h1>
            </div>

            <!-- Student Metadata (2 Kolom Presisi Sempurna persis rapor semester 1.pdf) -->
            <div class="grid grid-cols-[1.45fr_1fr] gap-x-8 mb-4 text-xs font-sans text-black">
              <!-- Kolom Kiri -->
              <table class="w-full border-collapse">
                <tbody>
                  <tr>
                    <td class="w-24 py-0.5 align-top font-normal text-slate-900 whitespace-nowrap">Nama Murid</td>
                    <td class="w-3 py-0.5 align-top text-center font-normal">:</td>
                    <td class="py-0.5 align-top font-bold uppercase text-black pl-1.5">{{ props.activeReportCard.student?.nama_lengkap }}</td>
                  </tr>
                  <tr>
                    <td class="w-24 py-0.5 align-top font-normal text-slate-900 whitespace-nowrap">NISN</td>
                    <td class="w-3 py-0.5 align-top text-center font-normal">:</td>
                    <td class="py-0.5 align-top font-medium text-black pl-1.5">{{ props.activeReportCard.student?.nisn || '-' }}</td>
                  </tr>
                  <tr>
                    <td class="w-24 py-0.5 align-top font-normal text-slate-900 whitespace-nowrap">Sekolah</td>
                    <td class="w-3 py-0.5 align-top text-center font-normal">:</td>
                    <td class="py-0.5 align-top font-medium text-black pl-1.5">{{ props.schoolProfile?.nama_sekolah || 'SDI Bina Insan Mandiri Purwokerto' }}</td>
                  </tr>
                  <tr>
                    <td class="w-24 py-0.5 align-top font-normal text-slate-900 whitespace-nowrap">Alamat</td>
                    <td class="w-3 py-0.5 align-top text-center font-normal">:</td>
                    <td class="py-0.5 align-top font-medium text-black leading-snug pl-1.5">
                      {{ displayStudentAddress }}
                    </td>
                  </tr>
                </tbody>
              </table>

              <!-- Kolom Kanan -->
              <table class="w-full border-collapse">
                <tbody>
                  <tr>
                    <td class="w-24 py-0.5 align-top font-normal text-slate-900 whitespace-nowrap">Kelas</td>
                    <td class="w-3 py-0.5 align-top text-center font-normal">:</td>
                    <td class="py-0.5 align-top font-bold text-black pl-1.5">{{ formattedClassRoman }}</td>
                  </tr>
                  <tr>
                    <td class="w-24 py-0.5 align-top font-normal text-slate-900 whitespace-nowrap">Fase</td>
                    <td class="w-3 py-0.5 align-top text-center font-normal">:</td>
                    <td class="py-0.5 align-top font-medium text-black pl-1.5">{{ props.activeReportCard.fase || 'A' }}</td>
                  </tr>
                  <tr>
                    <td class="w-24 py-0.5 align-top font-normal text-slate-900 whitespace-nowrap">Semester</td>
                    <td class="w-3 py-0.5 align-top text-center font-normal">:</td>
                    <td class="py-0.5 align-top font-medium text-black pl-1.5">{{ props.activeReportCard.semester === 'Gasal' ? 'Gasal' : 'Genap' }}</td>
                  </tr>
                  <tr>
                    <td class="w-24 py-0.5 align-top font-normal text-slate-900 whitespace-nowrap">Tahun Ajaran</td>
                    <td class="w-3 py-0.5 align-top text-center font-normal">:</td>
                    <td class="py-0.5 align-top font-medium text-black pl-1.5">{{ props.activeReportCard.academic_year?.nama || '2025/2026' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>

          <!-- TABEL MATA PELAJARAN (LEBAR PAS MENYESUAIKAN TEKS & RAPIH) -->
          <table class="w-full border-collapse border border-black text-xs font-sans table-auto">
            <colgroup>
              <col class="w-[36px]" />
              <col class="w-[195px]" />
              <col class="w-[60px]" />
              <col class="w-auto" />
            </colgroup>
            <thead>
              <tr class="bg-white text-center font-bold">
                <th class="border border-black py-2 px-1 w-[36px] text-center">No.</th>
                <th class="border border-black py-2 px-3 text-center w-[195px]">Mata Pelajaran</th>
                <th class="border border-black py-2 px-2 text-center w-[60px]">Nilai Akhir</th>
                <th class="border border-black py-2 px-3 text-center">Capaian Kompetensi</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="(g, idx) in page.grades" :key="'g-' + idx">
                <!-- Subheader Muatan Lokal sebelum Bahasa Jawa -->
                <tr v-if="g.kelompok === 'Muatan Lokal' && (idx === 0 || page.grades[idx-1]?.kelompok !== 'Muatan Lokal')">
                  <td colspan="4" class="border border-black py-1 px-3 font-bold text-xs bg-slate-50 text-left">
                    Muatan Lokal
                  </td>
                </tr>
                <tr>
                  <td class="border border-black py-2 px-1 text-center align-top font-bold text-xs">{{ g.urutan }}</td>
                  <td class="border border-black py-2 px-3 align-top font-semibold text-xs leading-snug">{{ g.nama_mapel }}</td>
                  <td class="border border-black py-2 px-2 text-center align-middle font-bold text-xs">{{ g.nilai_akhir ?? '-' }}</td>
                  <td class="border border-black py-2 px-3 align-top text-[10.5px] leading-relaxed text-justify">
                    {{ g.capaian_kompetensi || '-' }}
                  </td>
                </tr>
              </template>
            </tbody>
          </table>

          <!-- JIKA HALAMAN INI MASIH CUKUP MEMUAT KOKURIKULER & EKSTRA (Seperti Halaman 3 rapor semester 1.pdf) -->
          <template v-if="page.hasKokurikulerAndEkstra">
            <!-- Box Kokurikuler (P5) -->
            <div class="mt-4 font-sans text-xs">
              <table class="w-full border-collapse border border-black">
                <thead>
                  <tr class="bg-slate-50 text-center font-bold">
                    <th class="border border-black py-1 px-3 text-center uppercase tracking-wider text-xs">
                      Kokurikuler
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="border border-black p-3 text-[10.5px] leading-relaxed text-justify">
                      {{ defaultKokurikulerDeskripsi }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Tabel Ekstrakurikuler -->
            <div class="mt-4 font-sans text-xs">
              <table class="w-full border-collapse border border-black">
                <thead>
                  <tr class="bg-white text-center font-bold">
                    <th class="border border-black py-1.5 px-2 w-[36px] text-center">No.</th>
                    <th class="border border-black py-1.5 px-3 text-left w-56">Ekstrakurikuler</th>
                    <th class="border border-black py-1.5 px-3 text-left">Keterangan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(e, eIdx) in resolvedMerdekaEkstra" :key="'ekstra-embed-' + eIdx">
                    <td class="border border-black py-1.5 px-2 text-center align-top">{{ eIdx + 1 }}</td>
                    <td class="border border-black py-1.5 px-3 align-top font-semibold">{{ e.kegiatan }}</td>
                    <td class="border border-black py-1.5 px-3 align-top text-[11px]">{{ e.keterangan || 'Baik' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
        </div>

        <!-- Running Footer Halaman -->
        <div class="border-t border-slate-300 pt-1.5 flex items-center justify-between text-[10px] text-slate-600 font-sans mt-4">
          <span>Sekolah Dasar Islam Bina Insan Mandiri Purwokerto</span>
          <span class="font-semibold text-slate-800">{{ studentNickname }}</span>
          <span class="font-bold text-slate-800">{{ page.pageNumber }}</span>
        </div>
      </div>

      <!-- LEMBAR TERAKHIR: PENGESAHAN, PRESENSI & TANGGAPAN ORANG TUA (Persis Halaman 4 rapor semester 1.pdf) -->
      <div
        class="a4-page page-break report-paper bg-white p-8 sm:p-10 shadow-xl border border-slate-200 rounded-xl text-black font-sans print:shadow-none print:border-none print:p-0 print:m-0 flex flex-col justify-between min-h-[1050px]"
      >
        <div class="space-y-4">
          <!-- JIKA KOKURIKULER BELUM MUAT DI LEMBAR SEBELUMNYA, TAMPILKAN DI SINI -->
          <template v-if="!lastMerdekaPageHasKokurikuler">
            <!-- Box Kokurikuler (P5) -->
            <div class="font-sans text-xs">
              <table class="w-full border-collapse border border-black">
                <thead>
                  <tr class="bg-slate-50 text-center font-bold">
                    <th class="border border-black py-1 px-3 text-center uppercase tracking-wider text-xs">
                      Kokurikuler
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="border border-black p-3 text-[10.5px] leading-relaxed text-justify">
                      {{ defaultKokurikulerDeskripsi }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Tabel Ekstrakurikuler -->
            <div class="font-sans text-xs">
              <table class="w-full border-collapse border border-black">
                <thead>
                  <tr class="bg-white text-center font-bold">
                    <th class="border border-black py-1.5 px-2 w-[36px] text-center">No.</th>
                    <th class="border border-black py-1.5 px-3 text-left w-56">Ekstrakurikuler</th>
                    <th class="border border-black py-1.5 px-3 text-left">Keterangan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(e, eIdx) in resolvedMerdekaEkstra" :key="'ekstra-final-' + eIdx">
                    <td class="border border-black py-1.5 px-2 text-center align-top">{{ eIdx + 1 }}</td>
                    <td class="border border-black py-1.5 px-3 align-top font-semibold">{{ e.kegiatan }}</td>
                    <td class="border border-black py-1.5 px-3 align-top text-[11px]">{{ e.keterangan || 'Baik' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>

          <!-- SIDE-BY-SIDE: KETIDAKHADIRAN & CATATAN WALI KELAS -->
          <div class="grid grid-cols-12 gap-4 font-sans text-xs">
            <!-- Kiri: Ketidakhadiran -->
            <div class="col-span-5">
              <table class="w-full border-collapse border border-black">
                <thead>
                  <tr class="bg-white text-center font-bold">
                    <th colspan="2" class="border border-black py-1 px-2 text-center uppercase tracking-wider text-xs">
                      Ketidakhadiran
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="border border-black py-1.5 px-3 font-medium">Sakit</td>
                    <td class="border border-black py-1.5 px-3 text-center font-bold w-20">{{ props.activeReportCard.sakit ?? 0 }} hari</td>
                  </tr>
                  <tr>
                    <td class="border border-black py-1.5 px-3 font-medium">Izin</td>
                    <td class="border border-black py-1.5 px-3 text-center font-bold w-20">{{ props.activeReportCard.izin ?? 0 }} hari</td>
                  </tr>
                  <tr>
                    <td class="border border-black py-1.5 px-3 font-medium">Tanpa Keterangan</td>
                    <td class="border border-black py-1.5 px-3 text-center font-bold w-20">{{ props.activeReportCard.alpa ?? 0 }} hari</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Kanan: Catatan Wali Kelas -->
            <div class="col-span-7">
              <table class="w-full border-collapse border border-black h-full">
                <thead>
                  <tr class="bg-white text-center font-bold">
                    <th class="border border-black py-1 px-2 text-center uppercase tracking-wider text-xs">
                      Catatan Wali Kelas
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="border border-black p-2.5 text-[11px] leading-relaxed text-justify align-top min-h-[70px]">
                      {{ defaultCatatanWaliKelas }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- BOX: TANGGAPAN ORANG TUA / WALI MURID -->
          <div class="font-sans text-xs">
            <table class="w-full border-collapse border border-black">
              <thead>
                <tr class="bg-white text-center font-bold">
                  <th class="border border-black py-1 px-2 text-center uppercase tracking-wider text-xs">
                    Tanggapan Orang Tua / Wali Murid
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="border border-black h-16 p-2"></td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- TITIMANGSA & TANDA TANGAN (Persis Format Resmi BIM) -->
          <div class="font-sans text-xs pt-2">
            <div class="text-right pr-12 font-medium mb-3">
              {{ props.activeReportCard.tempat_tanggal || (props.activeReportCard.semester === 'Gasal' ? 'Purwokerto, 20 Desember 2026' : 'Purwokerto, 20 Juni 2026') }}
            </div>

            <div class="grid grid-cols-2 text-center mb-8">
              <div>
                <p class="font-medium">Orang Tua Murid</p>
                <div class="h-16"></div>
                <p class="border-b border-black w-44 mx-auto"></p>
              </div>
              <div>
                <p class="font-medium">Wali Kelas</p>
                <div class="h-16"></div>
                <p class="font-bold border-b border-black w-48 mx-auto">
                  {{ props.activeReportCard.wali_kelas_nama || 'Rasiti, S.Pd' }}
                </p>
              </div>
            </div>

            <div class="text-center">
              <p class="font-medium">Kepala Sekolah</p>
              <div class="h-16"></div>
              <p class="font-bold border-b border-black w-56 mx-auto">
                {{ props.activeReportCard.kepala_sekolah_nama || 'Yuniatul Inayah, A.Md., S.Pd.' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Running Footer Lembar Terakhir -->
        <div class="border-t border-slate-300 pt-1.5 flex items-center justify-between text-[10px] text-slate-600 font-sans mt-4">
          <span>Sekolah Dasar Islam Bina Insan Mandiri Purwokerto</span>
          <span class="font-semibold text-slate-800">{{ studentNickname }}</span>
          <span class="font-bold text-slate-800">{{ totalMerdekaPages }}</span>
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
