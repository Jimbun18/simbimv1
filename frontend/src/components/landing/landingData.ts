export interface TeacherProfile {
  id: number
  name: string
  role: string
  category: 'Pimpinan' | 'Guru Kelas' | 'Guru Mapel' | 'Bimbingan Konseling' | 'Staf'
  education: string
  quote: string
  avatarBg: string
}

export interface StudentPledge {
  number: number
  title: string
  desc: string
  icon: string
}

export interface FitrahPillar {
  title: string
  subtitle: string
  desc: string
  practices: string[]
  icon: string
  color: string
}

export interface ArticleItem {
  id: number
  title: string
  category: string
  author: string
  authorRole: string
  date: string
  readTime: string
  summary: string
  content: string[]
  tags: string[]
}

export interface GalleryItem {
  id: number
  title: string
  category: 'kbm' | 'tahfidz' | 'marketday' | 'outbound'
  categoryLabel: string
  date: string
  desc: string
  color: string
  icon: string
}

export interface AchievementItem {
  id: number
  title: string
  category: 'Tahfidz Qur\'an' | 'Akademik Sains' | 'Seni & Bahasa' | 'Teknologi'
  rank: string
  level: string
  year: string
  recipient: string
  desc: string
  badgeColor: string
}

// 1. DATA TENAGA PENDIDIK & KEPENDIDIKAN
export const teachersData: TeacherProfile[] = [
  {
    id: 1,
    name: 'Rasiti, S.Pd.',
    role: 'Kepala Sekolah',
    category: 'Pimpinan',
    education: 'S1 Pendidikan Guru',
    quote: 'Mendidik dengan hati nurani, merawat fitrah setiap anak sebagai amanah peradaban umat.',
    avatarBg: 'from-emerald-600 to-teal-800',
  },
  {
    id: 13,
    name: 'Lulu Farihati, S.Pd.',
    role: 'Guru Kelas 4 & Koordinator Kurikulum Fitrah',
    category: 'Guru Kelas',
    education: 'S1 Pendidikan',
    quote: 'Menumbuhkan rasa ingin tahu dan nalar kritis santri melalui pembelajaran kontekstual yang menyenangkan.',
    avatarBg: 'from-teal-600 to-emerald-700',
  },
  {
    id: 7,
    name: 'Hilda Anika Ramadani, S.Pd.',
    role: 'Guru Kelas 2 & Koordinator Tahfidz Al-Qur\'an',
    category: 'Guru Kelas',
    education: 'S1 Pendidikan',
    quote: 'Menanamkan cinta Al-Qur\'an sejak dini dengan metode tahsin yang lembut dan tartil.',
    avatarBg: 'from-cyan-600 to-teal-800',
  },
  {
    id: 8,
    name: 'Elsa Rahmawati',
    role: 'Guru Kelas 3 & Pembina Literasi',
    category: 'Guru Kelas',
    education: 'Pendidikan Guru Sekolah Dasar',
    quote: 'Setiap anak adalah bintang yang unik, tugas kita membimbing cahayanya agar bersinar terang.',
    avatarBg: 'from-emerald-700 to-green-900',
  },
  {
    id: 9,
    name: 'Rusydah Hasnaa Taqiyah Hastuti, S.Pd.',
    role: 'Guru Kelas 1 & Pembina Karakter Fitrah',
    category: 'Guru Kelas',
    education: 'S1 Pendidikan',
    quote: 'Masa transisi awal sekolah yang hangat, penuh kasih, dan sarat nilai adab mulia.',
    avatarBg: 'from-amber-600 to-emerald-800',
  },
  {
    id: 10,
    name: 'Abdul Rosyid Hamid, S.Pd.',
    role: 'Guru PAI & Muatan Karakter Islami',
    category: 'Guru Mapel',
    education: 'S1 Pendidikan Agama Islam',
    quote: 'Membina aqidah yang kokoh dan akhlak mulia sebagai pondasi kepribadian muslim sejati.',
    avatarBg: 'from-emerald-800 to-teal-950',
  },
  {
    id: 12,
    name: 'Nur Kafi Ramadani, S.Psi.',
    role: 'Konselor Tumbuh Kembang & Bimbingan Konseling (BK)',
    category: 'Bimbingan Konseling',
    education: 'S1 Psikologi',
    quote: 'Mendampingi keunikan emosi dan fitrah perkembangan anak bersama sinergi erat orang tua.',
    avatarBg: 'from-teal-700 to-slate-800',
  },
  {
    id: 11,
    name: 'Bayu Caesario Nor Saputra',
    role: 'Staf Tata Usaha & Layanan SIM-BIM',
    category: 'Staf',
    education: 'Administrasi & Teknologi Pendidikan',
    quote: 'Pelayanan administrasi sekolah yang transparan, sigap, dan terintegrasi digital.',
    avatarBg: 'from-slate-700 to-emerald-900',
  },
]

// 2. JANJI SISWA SDI BINA INSAN MANDIRI
export const studentPledges: StudentPledge[] = [
  {
    number: 1,
    title: 'Bertaqwa kepada Allah SWT & Meneladani Rasulullah SAW',
    desc: 'Senantiasa menjalankan perintah Allah, menjauhi larangan-Nya, dan meneladani kemuliaan akhlak Rasulullah Shallallahu \'Alaihi Wasallam di manapun berada.',
    icon: '🕌',
  },
  {
    number: 2,
    title: 'Berbakti kepada Orang Tua & Menghormati Guru',
    desc: 'Menyayangi dan taat kepada ayah bunda, serta senantiasa menghormati, mematuhi nasihat, dan mendoakan bapak ibu guru yang tulus membimbing.',
    icon: '🤝',
  },
  {
    number: 3,
    title: 'Mencintai, Membaca & Menghafal Al-Qur\'an',
    desc: 'Menjadikan Al-Qur\'an sebagai pedoman hidup, rajin membaca dengan tartil, bersungguh-sungguh menambah hafalan mutqin, dan mengamalkannya.',
    icon: '📖',
  },
  {
    number: 4,
    title: 'Menuntut Ilmu dengan Sungguh-Sungguh',
    desc: 'Bersemangat belajar, aktif bernalar, tekun mengeksplorasi ilmu pengetahuan, dan menjauhi rasa malas demi meraih ridho Allah Subhanahu wa Ta\'ala.',
    icon: '💡',
  },
  {
    number: 5,
    title: 'Menjaga Adab, Kejujuran, Kebersihan & Ukhuwah',
    desc: 'Berperilaku jujur dalam perkataan dan tindakan, menjaga kebersihan diri dan lingkungan sekolah, serta berkasih sayang kepada sesama teman.',
    icon: '🌱',
  },
  {
    number: 6,
    title: 'Menjadi Pribadi Mandiri Pembina Peradaban',
    desc: 'Membiasakan hidup disiplin dan bertanggung jawab atas diri sendiri, serta siap menjadi generasi tangguh yang berkontribusi membangun peradaban umat.',
    icon: '⭐',
  },
]

// 3. PILAR PENDIDIKAN BERBASIS FITRAH
export const fitrahPillars: FitrahPillar[] = [
  {
    title: 'Fitrah Keimanan (Spiritualitas & Tauhid)',
    subtitle: 'Menumbuhkan Cinta Allah Sebelum Beban Syariat',
    desc: 'Setiap anak terlahir bertauhid. Di SDI BIM, fitrah keimanan dirawat bukan dengan doktrin yang kaku, melainkan menghadirkan kekaguman pada ciptaan Allah, sholat berjamaah yang dirindukan, dan dzikir penuh penghayatan.',
    practices: [
      'Pembiasaan Sholat Dhuha dan Sholat Berjamaah di Masjid',
      'Tadabbur alam & mentadaburi ayat-ayat kauniyah',
      'Kisah keteladanan Nabi & Sahabat yang menggugah jiwa',
      'Doa dan adab harian islami yang terintegrasi di setiap aktivitas',
    ],
    icon: '✨',
    color: 'emerald',
  },
  {
    title: 'Fitrah Belajar & Nalar Kritis',
    subtitle: 'Menjaga Rasa Ingin Tahu (Curiosity) yang Alami',
    desc: 'Anak-anak pada dasarnya adalah pembelajar sejati yang haus eksplorasi. Kami menciptakan iklim kelas yang merangsang daya nalar kritis, eksperimen sains langsung, dan literasi tematik tanpa membebani mentalitas anak.',
    practices: [
      'Pembelajaran Tematik Berbasis Proyek (Project-Based Learning)',
      'Eksplorasi sains indoor dan outdoor di alam terbuka',
      'Pojok literasi kelas & pembiasaan membaca buku berkualitas',
      'Metode tanya-jawab dialogis (Socratic method) yang menghargai gagasan anak',
    ],
    icon: '🔬',
    color: 'teal',
  },
  {
    title: 'Fitrah Bakat & Kepemimpinan',
    subtitle: 'Mengenali Peran Keunikan Setiap Individu',
    desc: 'Tidak ada anak yang bodoh. Setiap santri dianugerahi potensi unik yang kelak menjadi jalan kontribusinya bagi peradaban. Kami membantu orang tua dan anak memetakan bakat minatnya secara personal.',
    practices: [
      'Pemetaan observasi bakat & kecerdasan majemuk (Multiple Intelligences)',
      'Program unjuk karya dan panggung apresiasi santri',
      'Market Day untuk melatih jiwa wirausaha dan muamalah syar\'i',
      'Ekstrakurikuler minat: Tahfidz, Robotik, Panahan, Bela Diri, Seni Kaligrafi',
    ],
    icon: '🎯',
    color: 'amber',
  },
  {
    title: 'Fitrah Perkembangan & Seksualitas',
    subtitle: 'Menjaga Identitas Gender, Kehormatan, dan Adab',
    desc: 'Pendidikan fitrah seksualitas membimbing anak memahami identitasnya sebagai laki-laki atau perempuan sejati, menjaga aurat, menumbuhkan rasa malu (haya\'), serta adab pergaulan islami sesuai tahap usianya.',
    practices: [
      'Penanaman identitas peran kelelakian dan keperempuanan islami',
      'Edukasi perlindungan diri dan batasan aurat sejak dini',
      'Pemisahan adab tempat wudhu dan aktivitas sesuai syariat',
      'Pendampingan konselor ramah anak untuk kesehatan mental & emosi',
    ],
    icon: '🛡️',
    color: 'sky',
  },
]

// 4. TUJUAN PENDIDIKAN MEMBANGUN SEBUAH PERADABAN
export const civilizationPoints = [
  {
    title: 'Bukan Sekadar Mengejar Angka Nilai',
    desc: 'Pendidikan sejati melampaui lembar ijazah. Nilai akademis adalah sarana, namun tujuan tertingginya adalah mencetak manusia yang sadar akan tujuan penciptaannya sebagai hamba Allah dan khalifah di bumi.',
    icon: '🎓',
  },
  {
    title: 'Menghubungkan Tri Sentra Pendidikan',
    desc: 'Pembangunan peradaban mustahil dicapai sekolah sendirian. Kami mengikat sinergi utuh antara Rumah (keluarga), Sekolah (pendidik), dan Masjid (masyarakat) dalam satu frekuensi visi.',
    icon: '🏛️',
  },
  {
    title: 'Kemandirian & Ketangguhan Karakter',
    desc: 'Santri dilatih mandiri, berani mengambil keputusan, jujur, bertanggung jawab, dan memiliki kepekaan sosial untuk peduli pada permasalahan lingkungan sekitarnya.',
    icon: '🌟',
  },
  {
    title: 'Generasi Qur\'ani yang Relevan dengan Zaman',
    desc: 'Kokoh dalam hafalan dan pemahaman Al-Qur\'an, sekaligus adaptif dan mahir memanfaatkan sains teknologi untuk kemaslahatan umat Islam dan dunia global.',
    icon: '🌍',
  },
]

// 5. DATA ARTIKEL & WARTA SEKOLAH
export const articlesData: ArticleItem[] = [
  {
    id: 1,
    title: 'Menumbuhkan Fitrah Keimanan Anak di Tengah Gempuran Era Digital',
    category: 'Parenting Fitrah',
    author: 'Rasiti, S.Pd.',
    authorRole: 'Kepala Sekolah SDI BIM',
    date: '20 Oktober 2026',
    readTime: '4 menit baca',
    summary: 'Bagaimana menumbuhkan ketertarikan anak kepada Allah Subhanahu wa Ta\'ala sebelum mengenalkan gawai digital dan gadget modern di rumah.',
    tags: ['Parenting', 'Fitrah Keimanan', 'Pendidikan Anak'],
    content: [
      'Di era keterbukaan informasi digital saat ini, tantangan terbesar para orang tua bukan sekadar membatasi screen-time anak, melainkan bagaimana menanamkan "benteng batin" yang kokoh berupa fitrah keimanan.',
      'Dalam konsep Pendidikan Berbasis Fitrah, anak pada usia pra-baligh (khususnya usia 0-7 dan 7-10 tahun) membutuhkan penanaman rasa cinta, keteladanan, dan visualisasi kebaikan Allah sebelum dibebani doktrin hukum yang menakutkan.',
      'Ajaklah ananda mengamati indahnya pergantian siang dan malam, ciptaan pepohonan di halaman rumah, dan nikmat sehat. Ketika rasa takjub kepada Sang Pencipta telah mekar di sanubari anak, ketaatan beribadah dan menjaga adab akan mengalir secara alami tanpa perlu paksaan.',
      'SDI Bina Insan Mandiri Purwokerto terus berkomitmen membersamai ayah bunda dalam menyelaraskan kurikulum di sekolah dengan iklim pendidikan ruhiyah di rumah.',
    ],
  },
  {
    id: 2,
    title: 'Kiat Muroja\'ah Menyenangkan: Menghafal Al-Qur\'an Tanpa Beban Bersama Ananda',
    category: 'Edukasi Qur\'ani',
    author: 'Abdul Rosyid Hamid, S.Pd.',
    authorRole: 'Koordinator Tahfidz SDI BIM',
    date: '14 Oktober 2026',
    readTime: '5 menit baca',
    summary: 'Metode praktis mendampingi hafalan Al-Qur\'an santri di rumah dengan riang gembira dan ikatan kasih sayang yang erat.',
    tags: ['Tahfidz', 'Muroja\'ah', 'Tips Santri'],
    content: [
      'Menghafal Al-Qur\'an adalah perjalanan cinta, bukan perlombaan kecepatan semata. Banyak santri yang merasa tertekan saat muroja\'ah karena suasana belajar yang tegang dan penuh target angka.',
      'Di SDI BIM, kami menerapkan pendekatan "Tahsin Tartil Berulang dengan Nada yang Indah". Anak diajak mendengarkan bacaan berulang-ulang hingga ayat tersebut akrab di telinga mereka sebelum mulai melafalkannya.',
      'Bagi para orang tua, jadikan momen muroja\'ah sebagai quality time bersama ananda. Selipkan hadiah pelukan hangat, dengarkan bacaannya dengan penuh antusias, dan bacakan terjemahan kisah menarik di balik surat yang sedang dihafal.',
      'Dengan demikian, Al-Qur\'an akan menjadi sahabat terbaik anak yang menentramkan hatinya hingga tumbuh dewasa kelak.',
    ],
  },
  {
    id: 3,
    title: 'Sinergi Tri Sentra: Kolaborasi Erat Sekolah, Keluarga, dan Masjid',
    category: 'Warta Sekolah',
    author: 'Lulu Farihati, S.Pd.',
    authorRole: 'Guru Kelas & Kurikulum',
    date: '08 Oktober 2026',
    readTime: '3 menit baca',
    summary: 'Pendidikan terbaik lahir dari keselarasan pandang antara apa yang diajarkan bapak ibu guru di kelas dengan pembiasaan di rumah.',
    tags: ['Sinergi', 'Keluarga', 'Komunitas'],
    content: [
      'Pendidikan peradaban ibarat bangunan megah yang disangga tiga pilar utama: keluarga sebagai madrasah pertama, sekolah sebagai pengasah potensi nalar dan adab, serta masjid sebagai pusat penguatan ruhiyah komunitas.',
      'Melalui Portal SIM-BIM, orang tua murid dapat melihat perkembangan mutaba\'ah harian, agenda KBM, dan capaian ananda secara real-time. Namun lebih dari itu, komunikasi tatap muka dan keselarasan visi orang tua menjadi kunci utama.',
      'Mari bersama-sama kita rawat amanah agung ini demi lahirnya generasi muslim yang kokoh aqidahnya dan mulia pekertinya.',
    ],
  },
  {
    id: 4,
    title: 'Market Day SDI BIM: Menumbuhkan Jiwa Mandiri dan Adab Muamalah Sejak Dini',
    category: 'Kegiatan Siswa',
    author: 'Hilda Anika Ramadani, S.Pd.',
    authorRole: 'Koordinator Kesiswaan',
    date: '01 Oktober 2026',
    readTime: '3 menit baca',
    summary: 'Serunya santri belajar matematika praktis, kreativitas produk, serta adab kejujuran jual beli dalam program Market Day tahunan.',
    tags: ['Market Day', 'Kemandirian', 'KBM Kreatif'],
    content: [
      'Halaman kampus SDI Bina Insan Mandiri Purwokerto semarak dengan gelaran tahunan Market Day. Santri dari berbagai jenjang belajar merancang produk jajanan sehat, kerajinan tangan, hingga mempraktikkan proses jual beli.',
      'Bukan sekadar perputaran uang koin, kegiatan ini mengajarkan nilai kejujuran timbangan, keramahan melayani pembeli, mengucapkan terima kasih dan doa keberkahan, serta menghitung kembalian uang secara mandiri.',
      'Kegiatan seperti ini membuktikan bahwa pembelajaran tematik terpadu dapat dihidupkan dengan begitu bermakna dan berkesan bagi seluruh siswa.',
    ],
  },
]

// 6. DATA GALERI KEGIATAN SEKOLAH
export const galleryItems: GalleryItem[] = [
  {
    id: 1,
    title: 'KBM Tematik Outdoor & Eksplorasi Sains Alam',
    category: 'kbm',
    categoryLabel: 'KBM Sains & Alam',
    date: 'Oktober 2026',
    desc: 'Santri mengamati siklus fotosintesis dan keanekaragaman flora di lingkungan hijau sekolah.',
    color: 'from-emerald-700 to-teal-900',
    icon: '🌿',
  },
  {
    id: 2,
    title: 'Halaqah Tahfidz & Tasmi\' Al-Qur\'an Juz 30',
    category: 'tahfidz',
    categoryLabel: 'Tahfidz Qur\'an',
    date: 'September 2026',
    desc: 'Ujian tasmi\' sekali duduk hafalan juz 30 di hadapan para asatidz dan disaksikan orang tua.',
    color: 'from-teal-700 to-emerald-950',
    icon: '📖',
  },
  {
    id: 3,
    title: 'Market Day Cilik: Latihan Adab Berniaga Syar\'i',
    category: 'marketday',
    categoryLabel: 'Market Day',
    date: 'September 2026',
    desc: 'Praktik muamalah, menghitung kembalian, dan mempromosikan produk olahan sehat kreasi siswa.',
    color: 'from-amber-600 to-emerald-800',
    icon: '🛍️',
  },
  {
    id: 4,
    title: 'Rihlah Edukatif & Outbound Karakter Mandiri',
    category: 'outbound',
    categoryLabel: 'Outbound & Rihlah',
    date: 'Agustus 2026',
    desc: 'Melatih kepemimpinan, kerjasama regu, dan ketangkasan fisik santri di alam terbuka.',
    color: 'from-cyan-700 to-slate-900',
    icon: '⛺',
  },
  {
    id: 5,
    title: 'Peringatan Hari Santri & Unjuk Seni Kaligrafi',
    category: 'kbm',
    categoryLabel: 'KBM Seni Islami',
    date: 'Agustus 2026',
    desc: 'Pameran karya kaligrafi santri dan pawai budaya islami menyemarakkan ukhuwah.',
    color: 'from-emerald-800 to-teal-900',
    icon: '🎨',
  },
  {
    id: 6,
    title: 'Qur\'an Camp & Pembiasaan Qiyamul Lail',
    category: 'tahfidz',
    categoryLabel: 'Tahfidz Camp',
    date: 'Juli 2026',
    desc: 'Malam bina iman dan taqwa, muroja\'ah intensif di bawah bimbingan ustadz-ustadzah.',
    color: 'from-indigo-900 to-teal-950',
    icon: '🌙',
  },
]

// 7. DATA PRESTASI SANTRI & SEKOLAH
export const achievementsData: AchievementItem[] = [
  {
    id: 1,
    title: 'Juara 1 Lomba Tahfidz Al-Qur\'an 1 Juz Tingkat SD/MI',
    category: 'Tahfidz Qur\'an',
    rank: 'Juara 1',
    level: 'Kabupaten Banyumas',
    year: '2026',
    recipient: 'Muhammad Fatih Robbani (Kelas 4)',
    desc: 'Meraih nilai tertinggi tajwid, fashahah, dan kelancaran hafalan juz 30 di ajang Festival Anak Sholeh.',
    badgeColor: 'bg-amber-100 text-amber-900 border-amber-300',
  },
  {
    id: 2,
    title: 'Medali Emas Olimpiade Sains & Matematika Terpadu',
    category: 'Akademik Sains',
    rank: 'Medali Emas',
    level: 'Karesidenan Banyumas',
    year: '2026',
    recipient: 'Aisyah Humaira Putri (Kelas 5)',
    desc: 'Menuntaskan eksperimen nalar sains dan pemecahan masalah matematika tematik dengan skor sempurna.',
    badgeColor: 'bg-emerald-100 text-emerald-900 border-emerald-300',
  },
  {
    id: 3,
    title: 'Juara 2 Lomba Pildacil & Da\'i Muda Tingkat Pelajar',
    category: 'Seni & Bahasa',
    rank: 'Juara 2',
    level: 'Kecamatan Purwokerto Selatan',
    year: '2025',
    recipient: 'Bilal Ahmad Al-Faruq (Kelas 3)',
    desc: 'Membawakan tausiyah inspiratif bertema "Birrul Walidain Kunci Sukses Masa Depan" dengan percaya diri.',
    badgeColor: 'bg-teal-100 text-teal-900 border-teal-300',
  },
  {
    id: 4,
    title: 'Juara Harapan 1 Kompetisi Robotik & Kreativitas Sains',
    category: 'Teknologi',
    rank: 'Harapan 1',
    level: 'Provinsi Jawa Tengah',
    year: '2025',
    recipient: 'Tim Robotik Santri SDI BIM',
    desc: 'Merancang inovasi sensor pengingat adzan otomatis berbasis mikrokontroler ramah anak.',
    badgeColor: 'bg-sky-100 text-sky-900 border-sky-300',
  },
]

// 8. DATA INFORMASI SPMB
export const spmbSteps = [
  {
    step: 1,
    title: 'Pendaftaran Online / Datang Langsung',
    desc: 'Mengisi formulir pendaftaran melalui website SIM-BIM atau hadir langsung di kantor layanan SPMB SDI BIM.',
    icon: '📝',
  },
  {
    step: 2,
    title: 'Observasi Kesiapan & Pemetaan Fitrah',
    desc: 'Kegiatan bermain edukatif menyenangkan bersama ananda untuk mengenali kesiapan motorik, kognitif, dan fitrah belajarnya.',
    icon: '🧸',
  },
  {
    step: 3,
    title: 'Wawancara Sinergi Orang Tua',
    desc: 'Dialog penyelarasan visi misi antara keluarga dan pihak sekolah guna memastikan kesiapan membersamai ananda bersama.',
    icon: '💬',
  },
  {
    step: 4,
    title: 'Pengumuman Hasil Seleksi',
    desc: 'Hasil observasi dan pemetaan fitrah diumumkan melalui portal SPMB dan pesan resmi panitia kepada orang tua.',
    icon: '📢',
  },
  {
    step: 5,
    title: 'Daftar Ulang & Pengambilan Seragam',
    desc: 'Penyelesaian administrasi pendaftaran, pengukuran perlengkapan seragam, dan penyambutan Masa Ta\'aruf Santri Baru.',
    icon: '🎒',
  },
]

export const spmbWaves = [
  {
    name: 'Gelombang 1 (Inden & Beasiswa Khusus)',
    period: '01 November 2025 - 31 Januari 2026',
    status: 'Dibuka',
    badge: 'Prioritas & Keringanan Biaya Sarana',
    isOpen: true,
  },
  {
    name: 'Gelombang 2 (Reguler)',
    period: '01 Februari 2026 - 30 April 2026',
    status: 'Segera Dibuka',
    badge: 'Kuota Terbatas per Rombel',
    isOpen: false,
  },
  {
    name: 'Gelombang 3 (Jika Kuota Masih Tersedia)',
    period: '01 Mei 2026 - 30 Juni 2026',
    status: 'Tentatif',
    badge: 'Maksimal 28 Siswa per Kelas',
    isOpen: false,
  },
]

export const spmbRequirements = [
  'Usia calon santri minimal 6 tahun pada bulan Juli 2026',
  'Fotokopi Akta Kelahiran calon santri (2 lembar)',
  'Fotokopi Kartu Keluarga (KK) yang masih berlaku (2 lembar)',
  'Fotokopi KTP kedua orang tua / wali murid (2 lembar)',
  'Pas foto berwarna ukuran 3x4 (3 lembar)',
  'Ijazah / Surat Keterangan Lulus dari TK / RA (dapat menyusul)',
  'Mengisi formulir pendaftaran dan surat komitmen sinergi orang tua',
]
