import tahfidzImg from '../../assets/tahfid.png'
import tahsinImg from '../../assets/tahsin.jpg'
import threeLangImg from '../../assets/treelang.jpeg'
import akhlakIslamiImg from '../../assets/ahlakislami.png'
import leadershipImg from '../../assets/leadership.jpeg'
import entrepreneurshipImg from '../../assets/enterprener.png'
import lifeSkillImg from '../../assets/lifeskill.png'
import cookingDayImg from '../../assets/cokingday.png'
import pencakSilatImg from '../../assets/pencaksilat.jpeg'
import renangImg from '../../assets/berenang.png'
import pramukaImg from '../../assets/pramuka.png'

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

export interface DeepLearningPillar {
  title: string
  subtitle: string
  desc: string
  practices: string[]
  icon: string
  color: string
}

export type FitrahPillar = DeepLearningPillar

export interface FeaturedProgram {
  id: number
  number: number
  title: string
  subtitle: string
  desc: string
  icon: string
  image?: string
  gradient: string
  badgeColor: string
  category: string
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
    role: 'Guru Kelas 4 & Koordinator Kurikulum Deep Learning',
    category: 'Guru Kelas',
    education: 'S1 Pendidikan',
    quote: 'Menumbuhkan rasa ingin tahu dan nalar kritis siswa melalui pembelajaran kontekstual yang menyenangkan.',
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
    role: 'Guru Kelas 1 & Pembina Karakter Siswa',
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

// 3. PILAR PENDIDIKAN BERBASIS DEEP LEARNING (PEMBELAJARAN MENDALAM)
export const deepLearningPillars: DeepLearningPillar[] = [
  {
    title: 'Mindful Learning (Pembelajaran Penuh Kesadaran)',
    subtitle: 'Menumbuhkan Spiritualitas Tauhid, Fokus, & Keterhubungan Jiwa',
    desc: 'Anak belajar dengan kesadaran penuh, memahami makna dan tujuan hakiki mencari ilmu sebagai hamba Allah. Bukan sekadar menghafal secara mekanis, melainkan menyerap ilmu dengan ketenangan batin, adab mulia, dan penghayatan mendalam.',
    practices: [
      'Pembiasaan Sholat Dhuha, Tadabbur Al-Qur\'an, & Dzikir Pagi bersama',
      'Mindful Reflection (refleksi berkala untuk memupuk kejujuran & evaluasi diri)',
      'Menghubungkan setiap materi sains & tematik dengan kebesaran ciptaan Allah',
      'Menciptakan iklim kelas yang tenang, aman secara psikologis, dan penuh empati',
    ],
    icon: '✨',
    color: 'emerald',
  },
  {
    title: 'Meaningful Learning (Pembelajaran Bermakna & Kontekstual)',
    subtitle: 'Mengaitkan Pengetahuan dengan Realitas Kehidupan & Pemecahan Masalah',
    desc: 'Setiap konsep dipelajari secara mendalam (deep inquiry), relevan dengan keseharian, dan aplikatif. Siswa didorong memahami "mengapa" dan "bagaimana" suatu konsep bekerja, bukan sekadar "apa" definisi teks semata.',
    practices: [
      'Pembelajaran Berbasis Proyek Nyata (Project-Based Learning) & Sains Terapan',
      'Eksplorasi eksperimen langsung di laboratorium alam terbuka sekolah',
      'Penerapan literasi finansial & matematika kontekstual (Program Market Day Syar\'i)',
      'Pengembangan nalar kritis dialogis (Higher Order Thinking Skills / HOTS)',
    ],
    icon: '🔬',
    color: 'teal',
  },
  {
    title: 'Joyful Learning (Pembelajaran yang Menggembirakan)',
    subtitle: 'Menjaga Rasa Ingin Tahu (Curiosity) Alami & Cinta Belajar Sepanjang Hayat',
    desc: 'Suasana belajar dirancang membahagiakan, bebas dari tekanan berlebihan, dan membangkitkan gairah eksplorasi. Anak tidak merasa terpaksa belajar, melainkan menikmati setiap proses penemuan ilmu baru dengan antusias.',
    practices: [
      'Metode belajar gamifikasi, kuis interaktif, dan simulasi kreatif',
      'Pojok literasi kelas & pembiasaan membaca buku berkualitas secara mandiri',
      'Kegiatan belajar di alam (Outbound, jelajah sains, dan mini agro-farming)',
      'Apresiasi tulus atas setiap usaha anak tanpa perbandingan negatif atau pelabelan',
    ],
    icon: '🎨',
    color: 'amber',
  },
  {
    title: 'Mastery & Collaboration (Penguasaan Mendalam & Kolaboratif)',
    subtitle: 'Membangun Kompetensi Abad 21 & Karakter Pembina Peradaban',
    desc: 'Pendidikan deep learning mengasah kecakapan kolaborasi, komunikasi, dan ketuntasan materi secara bertahap. Siswa dilatih bekerja sama saling melengkapi, peduli sesama, dan berani tampil percaya diri memimpin kebaikan.',
    practices: [
      'Kerja kelompok kolaboratif, presentasi karya, dan musyawarah siswa',
      'Bimbingan Tahsin Tartil berulang & Tahfidz Mutqin dengan pendampingan personal',
      'Penyaluran minat bakat: Robotik, Panahan, Seni Kaligrafi, & Muhadharah (Public Speaking)',
      'Program kepemimpinan sosial dan kepedulian lingkungan sekitar',
    ],
    icon: '🤝',
    color: 'sky',
  },
]

// Alias untuk kompatibilitas
export const fitrahPillars = deepLearningPillars

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
    desc: 'Siswa dilatih mandiri, berani mengambil keputusan, jujur, bertanggung jawab, dan memiliki kepekaan sosial untuk peduli pada permasalahan lingkungan sekitarnya.',
    icon: '🌟',
  },
  {
    title: 'Generasi Qur\'ani yang Relevan dengan Zaman',
    desc: 'Kokoh dalam hafalan dan pemahaman Al-Qur\'an, sekaligus adaptif dan mahir memanfaatkan sains teknologi untuk kemaslahatan umat Islam dan dunia global.',
    icon: '🌍',
  },
]

// 4.1 DATA 8 PROGRAM UNGGULAN SDI BINA INSAN MANDIRI
export const featuredPrograms: FeaturedProgram[] = [
  {
    id: 1,
    number: 1,
    title: 'Tahfidz Al-Qur\'an',
    subtitle: 'Bimbingan Hafalan Al-Qur\'an Intensif & Bersanad',
    desc: 'Program terstruktur tahfidz Al-Qur\'an juz 30, 29, dan surah pilihan dengan metode talaqqi, tasmi\', dan muraja\'ah harian terbimbing bersama dewan asatidz tahfidz.',
    icon: '📖',
    image: tahfidzImg,
    gradient: 'from-emerald-700 via-emerald-800 to-teal-900',
    badgeColor: 'bg-emerald-100 text-emerald-800 border-emerald-200',
    category: 'Al-Qur\'an'
  },
  {
    id: 2,
    number: 2,
    title: 'Tahsin Al-Qur\'an',
    subtitle: 'Standarisasi Makharijul Huruf & Kaidah Tajwid',
    desc: 'Pembinaan perbaikan kualitas bacaan Al-Qur\'an siswa/siswi sejak dini agar fasih, tartil, dan memahami hukum tajwid dengan evaluasi berkala per jilid metode terstandar.',
    icon: '🕌',
    image: tahsinImg,
    gradient: 'from-teal-700 via-teal-800 to-cyan-900',
    badgeColor: 'bg-teal-100 text-teal-800 border-teal-200',
    category: 'Al-Qur\'an'
  },
  {
    id: 3,
    number: 3,
    title: 'Three Language',
    subtitle: 'Bahasa Arab, Bahasa Inggris, & Bahasa Indonesia',
    desc: 'Pembiasaan lingkungan berbahasa aktif melalui kosakata harian (daily vocabulary), percakapan tematik, serta pembekalan komunikasi global yang santun dan percaya diri.',
    icon: '🌍',
    image: threeLangImg,
    gradient: 'from-blue-700 via-indigo-800 to-slate-900',
    badgeColor: 'bg-blue-100 text-blue-800 border-blue-200',
    category: 'Bahasa & Wawasan'
  },
  {
    id: 4,
    number: 4,
    title: 'Pembiasaan Akhlak Islami',
    subtitle: 'Karakter Qur\'ani, Adab Keseharian & Ibadah Praktis',
    desc: 'Internalisasi nilai-nilai Islam melalui shalat dhuha & dhuhur berjama\'ah, dzikir pagi, adab makan minum Islami, kepedulian sosial, serta Ikrar Janji Siswa/Siswi SDI BIM.',
    icon: '🤲',
    image: akhlakIslamiImg,
    gradient: 'from-emerald-800 via-green-850 to-teal-950',
    badgeColor: 'bg-emerald-100 text-emerald-800 border-emerald-200',
    category: 'Karakter & Adab'
  },
  {
    id: 5,
    number: 5,
    title: 'Leadership',
    subtitle: 'Kepemimpinan Siswa/Siswi, Tanggung Jawab & Keberanian',
    desc: 'Melatih jiwa kepemimpinan siswa/siswi melalui giliran memimpin doa, petugas upacara, kepanduan Pramuka SIT, ketua kelompok, dan dinamika keorganisasian sekolah.',
    icon: '👑',
    image: leadershipImg,
    gradient: 'from-amber-700 via-orange-800 to-slate-900',
    badgeColor: 'bg-amber-100 text-amber-900 border-amber-200',
    category: 'Kepemimpinan'
  },
  {
    id: 6,
    number: 6,
    title: 'Entrepreneurship',
    subtitle: 'Jiwa Wirausaha Kreatif, Market Day & Literasi Finansial',
    desc: 'Edukasi kemandirian finansial dan etika berniaga Islami melalui agenda Market Day siswa/siswi, pembuatan produk kreatif, perhitungan modal keuntungan, dan sedekah hasil usaha.',
    icon: '💼',
    image: entrepreneurshipImg,
    gradient: 'from-purple-700 via-violet-850 to-indigo-950',
    badgeColor: 'bg-purple-100 text-purple-800 border-purple-200',
    category: 'Kreativitas & Bisnis'
  },
  {
    id: 7,
    number: 7,
    title: 'Life Skill',
    subtitle: 'Kecakapan Hidup, Kemandirian Praktis & Problem Solving',
    desc: 'Pembiasaan keterampilan hidup mandiri seperti mencuci peralatan makan sendiri, merapikan perlengkapan belajar, kebersihan diri, pertolongan pertama (P3K dasar), dan ketangkasan fisik.',
    icon: '🛠️',
    image: lifeSkillImg,
    gradient: 'from-teal-800 via-emerald-850 to-slate-900',
    badgeColor: 'bg-teal-100 text-teal-800 border-teal-200',
    category: 'Kecakapan Hidup'
  },
  {
    id: 8,
    number: 8,
    title: 'Cooking Day',
    subtitle: 'Edukasi Makanan Halal-Thayyib & Kerjasama Siswa/Siswi',
    desc: 'Kegiatan edukatif memasak bersama yang melatih kerjasama tim siswa/siswi, mengenal gizi seimbang makanan halal dan thayyib, adab dapur, serta menghargai jerih payah orang tua.',
    icon: '🍳',
    image: cookingDayImg,
    gradient: 'from-rose-700 via-orange-800 to-amber-950',
    badgeColor: 'bg-rose-100 text-rose-800 border-rose-200',
    category: 'Eksplorasi Praktis'
  },
]

// 4B. DATA EKSTRAKURIKULER (WAJIB & PILIHAN)
export interface ExtracurricularItem {
  id: number
  name: string
  type: 'wajib' | 'pilihan'
  typeLabel: string
  subtitle: string
  desc: string
  schedule?: string
  targetLevel?: string
  icon: string
  gradient: string
  badgeColor: string
  image?: string // Foto menyusul
  benefits: string[]
}

export const extracurricularActivities: ExtracurricularItem[] = [
  // --- EKSTRAKURIKULER WAJIB (4) ---
  {
    id: 1,
    name: 'Pencak Silat',
    type: 'wajib',
    typeLabel: 'Ekstrakurikuler Wajib',
    subtitle: 'Kecakapan Bela Diri Islami & Ketahanan Mental',
    desc: 'Membentuk fisik yang tangkas, kedisiplinan diri, ketahanan mental, serta menanamkan adab ksatria muslim yang senantiasa menjaga kebenaran dan melindungi sesama.',
    schedule: 'Setiap Pekan',
    targetLevel: 'Kelas 1 - 6',
    icon: '🥋',
    image: pencakSilatImg,
    gradient: 'from-emerald-700 via-teal-850 to-slate-900',
    badgeColor: 'bg-emerald-100 text-emerald-800 border-emerald-200',
    benefits: [
      'Ketangkasan & Kebugaran Motorik',
      'Adab Santun & Perlindungan Diri',
      'Disiplin Mental Ksatria Muslim'
    ]
  },
  {
    id: 2,
    name: 'Renang',
    type: 'wajib',
    typeLabel: 'Ekstrakurikuler Wajib',
    subtitle: 'Olahraga Sunnah, Kekuatan Paru & Ketahanan Fisik',
    desc: 'Mengamalkan sunnah Rasulullah SAW, melatih kekuatan pernapasan, kebugaran otot jantung, postur tubuh tegap, serta keterampilan bertahan hidup di air.',
    schedule: 'Berkala per Jenjang',
    targetLevel: 'Kelas 1 - 6',
    icon: '🏊',
    image: renangImg,
    gradient: 'from-cyan-700 via-blue-850 to-slate-900',
    badgeColor: 'bg-cyan-100 text-cyan-800 border-cyan-200',
    benefits: [
      'Mengamalkan Olahraga Sunnah',
      'Ketahanan Pernapasan & Jantung',
      'Kecakapan Bertahan di Air'
    ]
  },
  {
    id: 3,
    name: 'BIM Voice',
    type: 'wajib',
    typeLabel: 'Ekstrakurikuler Wajib',
    subtitle: 'Harmoni Vokal, Nasyid Islami & Artikulasi Nada',
    desc: 'Wadah kreasi olah vokal, harmoni paduan suara nasyid islami, pelafalan artikulasi indah, dan pembiasaan syiar syair kebaikan yang mengasah musikalitas ruhani.',
    schedule: 'Setiap Pekan',
    targetLevel: 'Kelas 1 - 6',
    icon: '🎙️',
    gradient: 'from-amber-600 via-orange-800 to-slate-900',
    badgeColor: 'bg-amber-100 text-amber-900 border-amber-200',
    benefits: [
      'Olah Vokal & Harmoni Nada',
      'Syiar Syair Kebaikan Islami',
      'Percaya Diri Tampil di Panggung'
    ]
  },
  {
    id: 4,
    name: 'Pramuka (SIT)',
    type: 'wajib',
    typeLabel: 'Ekstrakurikuler Wajib',
    subtitle: 'Kepanduan Berkarakter, Cinta Alam & Kemandirian',
    desc: 'Pramuka Sekolah Islam Terpadu yang menumbuhkan jiwa kepemimpinan, kepedulian sosial, kemandirian hidup di alam terbuka, serta kekompakan ukhuwah regu.',
    schedule: 'Setiap Hari Jumat / Sabtu',
    targetLevel: 'Kelas 1 - 6',
    icon: '⛺',
    image: pramukaImg,
    gradient: 'from-emerald-800 via-teal-900 to-slate-955',
    badgeColor: 'bg-emerald-100 text-emerald-800 border-emerald-200',
    benefits: [
      'Kemandirian & Survival Alam',
      'Kepemimpinan & Tanggung Jawab',
      'Kekompakan Ukhuwah Regu'
    ]
  },

  // --- EKSTRAKURIKULER PILIHAN (7) ---
  {
    id: 5,
    name: 'Bulu Tangkis (Badminton)',
    type: 'pilihan',
    typeLabel: 'Ekstrakurikuler Pilihan',
    subtitle: 'Kelincahan Gerak, Kecepatan Refleks & Sportivitas',
    desc: 'Melatih koordinasi mata dan tangan, kelincahan gerak kaki, kecepatan reaksi, serta sportivitas tinggi dalam permainan bulu tangkis yang kompetitif dan menyenangkan.',
    schedule: 'Sesi Sore / Akhir Pekan',
    targetLevel: 'Peminatan Siswa',
    icon: '🏸',
    gradient: 'from-blue-600 via-indigo-850 to-slate-900',
    badgeColor: 'bg-blue-100 text-blue-800 border-blue-200',
    benefits: [
      'Kelincahan Gerak & Refleks Cepat',
      'Stamina Fisik & Postur Seimbang',
      'Jiwa Sportif & Mental Juara'
    ]
  },
  {
    id: 6,
    name: 'Catur',
    type: 'pilihan',
    typeLabel: 'Ekstrakurikuler Pilihan',
    subtitle: 'Asah Nalar Logika, Strategi Taktis & Konsentrasi',
    desc: 'Mengasah kemampuan daya nalar logika, konsentrasi berpikir mendalam (deep thinking), perencanaan strategi jangka panjang, dan ketenangan mengambil keputusan terbaik.',
    schedule: 'Sesi Sore / Akhir Pekan',
    targetLevel: 'Peminatan Siswa',
    icon: '♟️',
    gradient: 'from-slate-700 via-slate-850 to-slate-950',
    badgeColor: 'bg-slate-200 text-slate-800 border-slate-300',
    benefits: [
      'Daya Nalar Kritis & Logika Taktis',
      'Konsentrasi & Fokus Mendalam',
      'Ketenangan Problem Solving'
    ]
  },
  {
    id: 7,
    name: 'Kaligrafi (Khat Islami)',
    type: 'pilihan',
    typeLabel: 'Ekstrakurikuler Pilihan',
    subtitle: 'Seni Khat Al-Qur\'an, Ketelitian & Kesabaran Jiwa',
    desc: 'Mempelajari keindahan kaidah khat Naskhi dan Tsuluts dalam menulis ayat-ayat suci Al-Qur\'an, melatih ketelitian jemari, kesabaran, dan cita rasa seni islami bernilai tinggi.',
    schedule: 'Sesi Sore / Akhir Pekan',
    targetLevel: 'Peminatan Siswa',
    icon: '✒️',
    gradient: 'from-teal-700 via-emerald-850 to-slate-900',
    badgeColor: 'bg-teal-100 text-teal-800 border-teal-200',
    benefits: [
      'Kaidah Penulisan Khat Indah',
      'Ketelitian & Kesabaran Jiwa',
      'Apresiasi Estetika Qur\'ani'
    ]
  },
  {
    id: 8,
    name: 'Public Speaking (Muhadharah)',
    type: 'pilihan',
    typeLabel: 'Ekstrakurikuler Pilihan',
    subtitle: 'Retorika Berbicara, Percaya Diri & Dakwah Cilik',
    desc: 'Membina keberanian berbicara di depan khalayak umum, teknik intonasi suara, artikulasi dakwah cilik, bahasa tubuh percaya diri, serta menyusun gagasan ceramah secara terstruktur.',
    schedule: 'Sesi Sore / Akhir Pekan',
    targetLevel: 'Peminatan Siswa',
    icon: '🗣️',
    gradient: 'from-violet-700 via-purple-850 to-slate-900',
    badgeColor: 'bg-purple-100 text-purple-800 border-purple-200',
    benefits: [
      'Keberanian Tampil di Publik',
      'Retorika Ceramah & Dakwah',
      'Komunikasi Percaya Diri'
    ]
  },
  {
    id: 9,
    name: 'Penulis Cilik',
    type: 'pilihan',
    typeLabel: 'Ekstrakurikuler Pilihan',
    subtitle: 'Kreativitas Menulis, Cerita Islami & Literasi Sekolah',
    desc: 'Menghidupkan imajinasi kreatif siswa melalui penulisan cerita pendek, puisi islami, reportase kegiatan sekolah, dan pembiasaan gemar membaca buku bermutu sejak dini.',
    schedule: 'Sesi Sore / Akhir Pekan',
    targetLevel: 'Peminatan Siswa',
    icon: '📝',
    gradient: 'from-amber-700 via-yellow-850 to-slate-900',
    badgeColor: 'bg-amber-100 text-amber-800 border-amber-200',
    benefits: [
      'Imajinasi Kreatif & Tata Bahasa',
      'Menulis Cerita & Puisi Bermakna',
      'Penerbitan Mading Sekolah'
    ]
  },
  {
    id: 10,
    name: 'Seni Tari',
    type: 'pilihan',
    typeLabel: 'Ekstrakurikuler Pilihan',
    subtitle: 'Seni Gerak Santun Tradisional & Nuansa Islami',
    desc: 'Mengekspresikan kelenturan gerak tubuh dalam tarian tradisional dan kreasi bernuansa islami yang santun, harmonis, serta memupuk kecintaan pada kekayaan budaya Nusantara.',
    schedule: 'Sesi Sore / Akhir Pekan',
    targetLevel: 'Peminatan Siswa',
    icon: '💃',
    gradient: 'from-rose-600 via-pink-800 to-slate-900',
    badgeColor: 'bg-rose-100 text-rose-800 border-rose-200',
    benefits: [
      'Kelenturan Gerak & Postur',
      'Gerak Santun Beradab Islami',
      'Cinta Warisan Budaya Nusantara'
    ]
  },
  {
    id: 11,
    name: 'Seni Musik',
    type: 'pilihan',
    typeLabel: 'Ekstrakurikuler Pilihan',
    subtitle: 'Ketukan Ritmis Rebana Hadroh & Harmoni Musik',
    desc: 'Mengenal ketukan ritmis alat musik rebana hadroh dan ensambel musik sekolah, melatih sinkronisasi ketukan tim, kepekaan telinga musikal, dan mengiringi shalawat nabi.',
    schedule: 'Sesi Sore / Akhir Pekan',
    targetLevel: 'Peminatan Siswa',
    icon: '🥁',
    gradient: 'from-orange-600 via-red-800 to-slate-900',
    badgeColor: 'bg-orange-100 text-orange-800 border-orange-200',
    benefits: [
      'Ketukan Ritmis Hadroh & Rebana',
      'Sinkronisasi Nada & Irama Tim',
      'Syiar Shalawat Nabi SAW'
    ]
  }
]

// 5. DATA ARTIKEL & WARTA SEKOLAH
export const articlesData: ArticleItem[] = [
  {
    id: 1,
    title: 'Menumbuhkan Pembelajaran Mendalam (Deep Learning) & Karakter Anak di Era Digital',
    category: 'Edukasi & Karakter',
    author: 'Rasiti, S.Pd.',
    authorRole: 'Kepala Sekolah SDI BIM',
    date: '20 Oktober 2026',
    readTime: '4 menit baca',
    summary: 'Bagaimana menumbuhkan ketertarikan anak kepada Allah Subhanahu wa Ta\'ala dan daya nalar mendalam di tengah era gawai digital.',
    tags: ['Parenting', 'Deep Learning', 'Pendidikan Karakter'],
    content: [
      'Di era keterbukaan informasi digital saat ini, tantangan terbesar para orang tua bukan sekadar membatasi screen-time anak, melainkan bagaimana menanamkan "benteng batin" yang kokoh berupa keimanan dan daya nalar mendalam.',
      'Dalam pendekatan Pendidikan Berbasis Deep Learning (Mindful, Meaningful, & Joyful Learning), anak pada usia sekolah dasar membutuhkan penanaman makna, keteladanan, dan visualisasi kebaikan Allah sebelum dibebani tuntutan hafalan yang mekanis.',
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
    summary: 'Metode praktis mendampingi hafalan Al-Qur\'an siswa di rumah dengan riang gembira dan ikatan kasih sayang yang erat.',
    tags: ['Tahfidz', 'Muroja\'ah', 'Tips Siswa'],
    content: [
      'Menghafal Al-Qur\'an adalah perjalanan cinta, bukan perlombaan kecepatan semata. Banyak siswa yang merasa tertekan saat muroja\'ah karena suasana belajar yang tegang dan penuh target angka.',
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
    summary: 'Serunya siswa belajar matematika praktis, kreativitas produk, serta adab kejujuran jual beli dalam program Market Day tahunan.',
    tags: ['Market Day', 'Kemandirian', 'KBM Kreatif'],
    content: [
      'Halaman kampus SDI Bina Insan Mandiri Purwokerto semarak dengan gelaran tahunan Market Day. Siswa dari berbagai jenjang belajar merancang produk jajanan sehat, kerajinan tangan, hingga mempraktikkan proses jual beli.',
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
    desc: 'Siswa mengamati siklus fotosintesis dan keanekaragaman flora di lingkungan hijau sekolah.',
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
    desc: 'Melatih kepemimpinan, kerjasama regu, dan ketangkasan fisik siswa di alam terbuka.',
    color: 'from-cyan-700 to-slate-900',
    icon: '⛺',
  },
  {
    id: 5,
    title: 'Pentas Seni Siswa & Kaligrafi Islami',
    category: 'kbm',
    categoryLabel: 'KBM Seni Islami',
    date: 'Agustus 2026',
    desc: 'Pameran karya kaligrafi siswa dan pawai budaya islami menyemarakkan ukhuwah.',
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

// 7. DATA PRESTASI SISWA & SEKOLAH
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
    recipient: 'Tim Robotik Siswa SDI BIM',
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
    desc: 'Penyelesaian administrasi pendaftaran, pengukuran perlengkapan seragam, dan penyambutan Masa Ta\'aruf Siswa Baru.',
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
  'Usia calon siswa minimal 6 tahun pada bulan Juli 2026',
  'Fotokopi Akta Kelahiran calon siswa (2 lembar)',
  'Fotokopi Kartu Keluarga (KK) yang masih berlaku (2 lembar)',
  'Fotokopi KTP kedua orang tua / wali murid (2 lembar)',
  'Pas foto berwarna ukuran 3x4 (3 lembar)',
  'Ijazah / Surat Keterangan Lulus dari TK / RA (dapat menyusul)',
  'Mengisi formulir pendaftaran dan surat komitmen sinergi orang tua',
]
