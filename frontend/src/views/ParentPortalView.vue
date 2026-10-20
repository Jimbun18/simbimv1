<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    <!-- 1. GREETING & HERO HEADER -->
    <div class="bg-gradient-to-br from-emerald-900 via-emerald-800 to-teal-950 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden border border-emerald-700/40">
      <!-- Decorative ambient glows -->
      <div class="absolute -right-12 -top-12 w-48 h-48 bg-emerald-500/15 rounded-full blur-2xl pointer-events-none"></div>
      <div class="absolute -left-12 -bottom-12 w-48 h-48 bg-teal-500/15 rounded-full blur-2xl pointer-events-none"></div>

      <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="px-3.5 py-1 bg-white/10 backdrop-blur-md rounded-full text-xs font-bold uppercase tracking-wider text-emerald-200 border border-white/20">
              Portal Terpadu Wali Murid
            </span>
            <span class="text-xs text-emerald-300 font-semibold">• TA 2024/2025</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight mt-2">
            Bapak/Ibu {{ auth.userName }}
          </h1>
          <p class="text-xs sm:text-sm text-emerald-200/90 mt-1 font-medium">
            Pantau kehadiran, capaian tahfidz, kegiatan harian, dan administrasi ananda di SDI Bina Insan Mandiri Purwokerto.
          </p>
        </div>

        <div class="hidden sm:flex items-center gap-2 px-3.5 py-2 bg-black/20 backdrop-blur-md rounded-2xl border border-white/10 text-xs text-emerald-200">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
          <span>Sistem Informasi Terhubung</span>
        </div>
      </div>

      <!-- Child Switcher Tabs (If parent has 1 or more children) -->
      <div v-if="children.length > 0" class="mt-6 pt-5 border-t border-white/10">
        <div class="text-[11px] font-bold text-emerald-300 uppercase tracking-wider mb-2.5">
          Pilih Ananda Tercinta:
        </div>
        <div class="flex gap-2.5 overflow-x-auto pb-1 scrollbar-thin">
          <button
            v-for="c in children"
            :key="c.id"
            @click="selectChild(c.id)"
            :class="selectedChildId === c.id 
              ? 'bg-white text-emerald-950 font-black shadow-lg scale-102 border-white' 
              : 'bg-white/10 text-white hover:bg-white/20 border-white/15'"
            class="px-4 py-2.5 rounded-2xl text-xs whitespace-nowrap transition-all duration-200 flex items-center gap-2.5 border cursor-pointer"
          >
            <div 
              class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[10px]"
              :class="selectedChildId === c.id ? 'bg-emerald-100 text-emerald-800' : 'bg-white/20 text-white'"
            >
              {{ c.nama_lengkap.charAt(0) }}
            </div>
            <span>{{ c.nama_lengkap }}</span>
            <span 
              class="px-2 py-0.5 rounded-md text-[10px] font-bold"
              :class="selectedChildId === c.id ? 'bg-emerald-100 text-emerald-900' : 'bg-black/20 text-emerald-200'"
            >
              {{ c.school_class?.nama || c.school_class?.nama_kelas || '-' }}
            </span>
          </button>
        </div>
      </div>
    </div>

    <!-- 2. LOADING STATE -->
    <div v-if="loading" class="p-12 text-center text-slate-400 bg-white rounded-3xl border border-slate-100 shadow-xs">
      <div class="inline-block animate-spin w-8 h-8 border-3 border-emerald-600 border-t-transparent rounded-full mb-3"></div>
      <p class="text-sm font-medium text-slate-600">Memuat data dan capaian ananda tercinta...</p>
    </div>

    <!-- 3. EMPTY STATE (No Children connected) -->
    <div v-else-if="children.length === 0" class="bg-white rounded-3xl p-8 sm:p-12 text-center border border-slate-200/80 shadow-xs space-y-4">
      <div class="w-16 h-16 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center mx-auto text-2xl font-bold">
        ℹ️
      </div>
      <div>
        <h3 class="text-lg font-bold text-slate-800">Akun Belum Terhubung dengan Data Siswa</h3>
        <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto mt-1">
          Akun wali murid Anda belum ditautkan ke data siswa aktif. Silakan hubungi Staf Administrasi / Tata Usaha SDI Bina Insan Mandiri Purwokerto untuk verifikasi nomor identitas anak.
        </p>
      </div>
    </div>

    <!-- 4. ACTIVE CHILD DATA CONTENT -->
    <div v-else-if="summary" class="space-y-6">
      
      <!-- A. Child Profile Identity Card -->
      <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <!-- Avatar Ananda -->
          <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-2xl font-black shrink-0 border border-emerald-200 shadow-2xs overflow-hidden">
            <img 
              v-if="summary.student?.foto_url || summary.student?.foto_path" 
              :src="summary.student?.foto_url || `/storage/${summary.student?.foto_path}`" 
              alt="Foto Siswa" 
              class="w-full h-full object-cover" 
            />
            <span v-else>{{ (summary.student?.nama_lengkap || 'S').charAt(0) }}</span>
          </div>

          <div>
            <div class="flex items-center gap-2">
              <h2 class="text-base sm:text-lg font-black text-slate-900">
                {{ summary.student?.nama_lengkap }}
              </h2>
              <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[10px] font-extrabold uppercase">
                {{ summary.student?.status || 'Aktif' }}
              </span>
            </div>
            
            <p class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-2">
              <span>NIS: <strong class="text-slate-700 font-mono">{{ summary.student?.nis || '-' }}</strong></span>
              <span>•</span>
              <span>NISN: <strong class="text-slate-700 font-mono">{{ summary.student?.nisn || '-' }}</strong></span>
              <span>•</span>
              <span>Kelas: <strong class="text-slate-800 font-bold">{{ String(summary.student?.school_class?.nama || summary.student?.class_id || '-').replace(/^Kelas\s+/i, '') }}</strong></span>
            </p>

            <div class="text-xs text-slate-600 mt-1.5 flex flex-wrap items-center gap-3">
              <span class="flex items-center gap-1.5 text-slate-500">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>Wali Kelas: <strong class="text-slate-800">{{ homeroomTeacherName }}</strong></span>
              </span>

              <!-- WhatsApp Wali Kelas Button if phone available -->
              <a 
                v-if="homeroomTeacherPhone" 
                :href="getWhatsAppUrl(homeroomTeacherPhone, summary.student?.nama_lengkap)" 
                target="_blank" 
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1 rounded-xl transition"
                title="Kirim pesan WhatsApp ke Wali Kelas"
              >
                <span>Hubungi Wali Kelas</span>
                <span>↗</span>
              </a>
            </div>
          </div>
        </div>

        <div class="sm:text-right flex sm:flex-col items-center sm:items-end justify-between border-t sm:border-t-0 pt-3 sm:pt-0 border-slate-100">
          <span class="text-[11px] text-slate-400 font-semibold uppercase">Tahun Pelajaran</span>
          <span class="text-xs sm:text-sm font-extrabold text-slate-800">2024/2025</span>
          <span class="text-[10px] text-slate-400">Semester Ganjil</span>
        </div>
      </div>

      <!-- B. 4 Quick Stat Highlights (Responsive Grid: 2 Col Mobile, 4 Col Tablet/Desktop) -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Kehadiran Bulan Ini -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Presensi Bulan Ini</span>
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
          </div>
          <div class="mt-3">
            <div class="text-2xl sm:text-3xl font-black text-emerald-800 font-mono">
              {{ attendanceSummary.persentase_kehadiran ?? 100 }}%
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
              <strong class="text-slate-800">{{ attendanceSummary.hadir ?? 0 }} hari</strong> dari {{ attendanceSummary.total_pertemuan_bulan_ini ?? 0 }} pertemuan
            </div>
          </div>
        </div>

        <!-- 2. Status Kehadiran Hari Ini -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status Hari Ini</span>
            <span class="text-xs">📅</span>
          </div>
          <div class="mt-3">
            <div 
              class="text-base sm:text-lg font-black uppercase"
              :class="todayAttendanceStatusClass"
            >
              {{ todayAttendanceStatusText }}
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
              {{ attendanceSummary.today ? 'Tercatat di sistem presensi' : 'Belum ada data presensi hari ini' }}
            </div>
          </div>
        </div>

        <!-- 3. Capaian Tahfidz Terakhir -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tahfidz Terkini</span>
            <span class="text-xs">📖</span>
          </div>
          <div class="mt-3">
            <div class="text-sm sm:text-base font-bold text-slate-900 truncate" :title="latestProgressTitle">
              {{ latestProgressTitle }}
            </div>
            <div class="text-[11px] text-emerald-700 font-semibold mt-1">
              {{ latestProgressPredicate }}
            </div>
          </div>
        </div>

        <!-- 4. Status Tagihan SPP -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status SPP</span>
            <span 
              class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase"
              :class="financialSummary.status_tagihan === 'lunas' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
            >
              {{ financialSummary.status_tagihan === 'lunas' ? 'Lunas' : 'Ada Tagihan' }}
            </span>
          </div>
          <div class="mt-3">
            <div class="text-lg sm:text-xl font-black text-slate-900 font-mono">
              Rp {{ formatRupiah(financialSummary.sisa_tagihan ?? 0) }}
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
              {{ financialSummary.sisa_tagihan <= 0 ? 'Semua tagihan lunas' : 'Sisa tagihan bulan berjalan' }}
            </div>
          </div>
        </div>
      </div>

      <!-- C. SECTIONAL TABS (Perkembangan Belajar, Presensi, Kegiatan Harian, Keuangan SPP, Pengumuman) -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <!-- Tab Headers -->
        <div class="flex border-b border-slate-100 px-4 sm:px-6 gap-2 sm:gap-4 overflow-x-auto scrollbar-thin">
          <button 
            @click="activeTab = 'progress'"
            :class="activeTab === 'progress' ? 'border-emerald-600 text-emerald-700 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
            class="py-4 border-b-2 text-xs sm:text-sm whitespace-nowrap transition-all flex items-center gap-2 cursor-pointer"
          >
            <span>Jurnal Tahfidz & Karakter</span>
          </button>

          <button 
            @click="activeTab = 'attendance'"
            :class="activeTab === 'attendance' ? 'border-emerald-600 text-emerald-700 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
            class="py-4 border-b-2 text-xs sm:text-sm whitespace-nowrap transition-all flex items-center gap-2 cursor-pointer"
          >
            <span>Rekap Kehadiran</span>
          </button>

          <button 
            @click="activeTab = 'activities'"
            :class="activeTab === 'activities' ? 'border-emerald-600 text-emerald-700 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
            class="py-4 border-b-2 text-xs sm:text-sm whitespace-nowrap transition-all flex items-center gap-2 cursor-pointer"
          >
            <span>Kegiatan Kelas</span>
          </button>

          <button 
            @click="activeTab = 'finance'"
            :class="activeTab === 'finance' ? 'border-emerald-600 text-emerald-700 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
            class="py-4 border-b-2 text-xs sm:text-sm whitespace-nowrap transition-all flex items-center gap-2 cursor-pointer"
          >
            <span>Tagihan & SPP</span>
          </button>

          <button 
            @click="activeTab = 'announcements'"
            :class="activeTab === 'announcements' ? 'border-emerald-600 text-emerald-700 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
            class="py-4 border-b-2 text-xs sm:text-sm whitespace-nowrap transition-all flex items-center gap-2 cursor-pointer"
          >
            <span>Pengumuman Sekolah</span>
          </button>

          <button 
            @click="activeTab = 'rapor'; fetchChildReportCards()"
            :class="activeTab === 'rapor' ? 'border-emerald-600 text-emerald-700 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
            class="py-4 border-b-2 text-xs sm:text-sm whitespace-nowrap transition-all flex items-center gap-2 cursor-pointer"
          >
            <span>📑 E-Rapor Ananda</span>
            <span v-if="reportCardList.length > 0" class="px-1.5 py-0.5 rounded-full text-[10px] bg-emerald-100 text-emerald-800 font-black">
              {{ reportCardList.length }}
            </span>
          </button>
        </div>

        <!-- Tab 1: JURNAL TAHFIDZ & KARAKTER -->
        <div v-if="activeTab === 'progress'" class="p-5 sm:p-7 space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
              <h3 class="text-sm sm:text-base font-bold text-slate-900">Catatan Capaian Belajar & Tahfidz Al-Qur'an</h3>
              <p class="text-xs text-slate-500">Pantau perkembangan hafalan surat, sholat, dan pembiasaan akhlak ananda</p>
            </div>
            <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-100">
              SDI Bina Insan Mandiri
            </span>
          </div>

          <div v-if="latestProgressList.length > 0" class="divide-y divide-slate-100">
            <div 
              v-for="p in latestProgressList" 
              :key="p.id" 
              class="py-4 flex flex-col sm:flex-row sm:items-start justify-between gap-3 hover:bg-slate-50/70 p-3 rounded-2xl transition"
            >
              <div class="space-y-1.5 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span 
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase"
                    :class="{
                      'bg-emerald-100 text-emerald-800': p.kategori === 'tahfidz',
                      'bg-blue-100 text-blue-800': p.kategori === 'sholat',
                      'bg-purple-100 text-purple-800': p.kategori === 'karakter',
                      'bg-amber-100 text-amber-800': p.kategori === 'akademik',
                      'bg-gray-100 text-gray-800': p.kategori === 'ekskul'
                    }"
                  >
                    {{ p.kategori }}
                  </span>
                  <span class="font-bold text-slate-900 text-sm">{{ p.capaian }}</span>
                  <span v-if="p.nilai" class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    Nilai: {{ p.nilai }}
                  </span>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed pl-1">
                  {{ p.catatan || 'Capaian materi dicatat oleh ustadz pembimbing, alhamdulillah ananda menunjukkan ketekunan belajar.' }}
                </p>
                <div class="text-[11px] text-slate-400 pl-1">
                  <span>Pembimbing: {{ p.teacher?.nama_lengkap || 'Ustadz/Ustadzah Pengampu' }}</span>
                </div>
              </div>

              <div class="sm:text-right shrink-0">
                <span class="text-xs font-mono font-medium text-slate-500 bg-slate-100 px-2.5 py-1 rounded-xl">
                  {{ formatDate(p.tanggal) }}
                </span>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-12 text-slate-400 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
            <p class="text-xs">Belum ada catatan jurnal progres untuk ananda pada periode ini.</p>
          </div>
        </div>

        <!-- Tab 2: REKAP KEHADIRAN -->
        <div v-if="activeTab === 'attendance'" class="p-5 sm:p-7 space-y-6">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-sm sm:text-base font-bold text-slate-900">Rekapitulasi Kehadiran Siswa</h3>
            <p class="text-xs text-slate-500">Rincian kehadiran ananda di kelas selama bulan berjalan</p>
          </div>

          <!-- 4 Grid Breakdown Status Kehadiran -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
            <div class="p-4 bg-emerald-50 rounded-2xl border border-emerald-100">
              <div class="text-2xl font-black text-emerald-800 font-mono">{{ attendanceSummary.hadir ?? 0 }}</div>
              <div class="text-xs font-bold text-emerald-700 mt-1">Hadir (Hari)</div>
            </div>
            <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100">
              <div class="text-2xl font-black text-amber-800 font-mono">{{ attendanceSummary.sakit ?? 0 }}</div>
              <div class="text-xs font-bold text-amber-700 mt-1">Sakit</div>
            </div>
            <div class="p-4 bg-sky-50 rounded-2xl border border-sky-100">
              <div class="text-2xl font-black text-sky-800 font-mono">{{ attendanceSummary.izin ?? 0 }}</div>
              <div class="text-xs font-bold text-sky-700 mt-1">Izin</div>
            </div>
            <div class="p-4 bg-rose-50 rounded-2xl border border-rose-100">
              <div class="text-2xl font-black text-rose-800 font-mono">{{ attendanceSummary.alpa ?? 0 }}</div>
              <div class="text-xs font-bold text-rose-700 mt-1">Tanpa Keterangan</div>
            </div>
          </div>

          <!-- Riwayat Presensi Terakhir -->
          <div class="space-y-3 pt-2">
            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">
              Catatan Presensi 7 Hari Terakhir:
            </h4>

            <div v-if="recentAttendanceList.length > 0" class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="bg-slate-50 text-slate-600 font-bold border-b border-slate-100">
                    <th class="py-2.5 px-3 rounded-l-xl">Tanggal</th>
                    <th class="py-2.5 px-3">Status Kehadiran</th>
                    <th class="py-2.5 px-3 rounded-r-xl">Catatan / Keterangan</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="att in recentAttendanceList" :key="att.id" class="hover:bg-slate-50/60 transition">
                    <td class="py-2.5 px-3 font-medium text-slate-800 whitespace-nowrap">{{ formatDate(att.tanggal) }}</td>
                    <td class="py-2.5 px-3">
                      <span 
                        class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase"
                        :class="{
                          'bg-emerald-100 text-emerald-800': att.status === 'hadir',
                          'bg-amber-100 text-amber-800': att.status === 'sakit',
                          'bg-sky-100 text-sky-800': att.status === 'izin',
                          'bg-rose-100 text-rose-800': att.status === 'alpa'
                        }"
                      >
                        {{ att.status }}
                      </span>
                    </td>
                    <td class="py-2.5 px-3 text-slate-500">{{ att.keterangan || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="text-center py-6 text-slate-400 text-xs bg-slate-50 rounded-2xl">
              Belum ada riwayat harian yang tercatat minggu ini.
            </div>
          </div>
        </div>

        <!-- Tab 3: KEGIATAN KELAS -->
        <div v-if="activeTab === 'activities'" class="p-5 sm:p-7 space-y-4">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-sm sm:text-base font-bold text-slate-900">Kegiatan Harian Kelas Ananda</h3>
            <p class="text-xs text-slate-500">Aktivitas pembelajaran tematik, ibadah bersama, dan proyek kelas yang berlangsung</p>
          </div>

          <div v-if="latestActivitiesList.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div 
              v-for="act in latestActivitiesList" 
              :key="act.id" 
              class="p-5 rounded-2xl border border-slate-100 bg-slate-50/50 space-y-2.5 flex flex-col justify-between"
            >
              <div>
                <div class="flex items-center justify-between text-xs text-slate-400 mb-1.5">
                  <span class="bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-bold uppercase text-[10px]">{{ act.kategori }}</span>
                  <span class="font-mono">{{ formatDate(act.tanggal) }}</span>
                </div>
                <h4 class="font-bold text-slate-900 text-sm">{{ act.judul }}</h4>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ act.deskripsi }}</p>
              </div>
              <div class="pt-2 border-t border-slate-200/60 text-[11px] text-slate-400">
                <span>Guru Pembimbing: <strong class="text-slate-700">{{ act.teacher?.nama_lengkap || 'Guru Kelas' }}</strong></span>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-12 text-slate-400 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
            <p class="text-xs">Belum ada agenda kegiatan kelas yang dipublikasikan baru-baru ini.</p>
          </div>
        </div>

        <!-- Tab 4: TAGIHAN & KEUANGAN SPP -->
        <div v-if="activeTab === 'finance'" class="p-5 sm:p-7 space-y-6">
          <div class="border-b border-slate-100 pb-3 flex flex-col sm:flex-row justify-between sm:items-center gap-2">
            <div>
              <h3 class="text-sm sm:text-base font-bold text-slate-900">Rincian Tagihan & Administrasi SPP</h3>
              <p class="text-xs text-slate-500">Pantau status pembayaran iuran sekolah dan SPP bulanan ananda secara transparan</p>
            </div>
            <span 
              class="px-3 py-1 rounded-full text-xs font-black uppercase inline-block w-fit"
              :class="financialSummary.status_tagihan === 'lunas' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
            >
              {{ financialSummary.status_tagihan === 'lunas' ? 'Semua Tagihan Lunas' : 'Ada Tagihan Aktif' }}
            </span>
          </div>

          <!-- Ringkasan Keuangan Box -->
          <div class="p-5 bg-gradient-to-r from-slate-50 to-emerald-50/30 rounded-3xl border border-slate-200/80 flex flex-col sm:flex-row justify-between gap-4">
            <div>
              <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Tagihan Terdaftar</span>
              <div class="text-xl sm:text-2xl font-black text-slate-900 font-mono mt-1">
                Rp {{ formatRupiah(financialSummary.total_tagihan ?? 0) }}
              </div>
            </div>
            <div>
              <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Sudah Terbayar</span>
              <div class="text-xl sm:text-2xl font-black text-emerald-700 font-mono mt-1">
                Rp {{ formatRupiah(financialSummary.jumlah_terbayar ?? 0) }}
              </div>
            </div>
            <div>
              <span class="text-xs font-semibold text-rose-600 uppercase tracking-wider">Sisa Belum Dibayar</span>
              <div class="text-xl sm:text-2xl font-black text-rose-600 font-mono mt-1">
                Rp {{ formatRupiah(financialSummary.sisa_tagihan ?? 0) }}
              </div>
            </div>
          </div>

          <!-- Rincian Daftar Invoice -->
          <div class="space-y-3">
            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Daftar Tagihan Ananda:</h4>

            <div v-if="invoiceList.length > 0" class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="bg-slate-50 text-slate-600 font-bold border-b border-slate-100">
                    <th class="py-2.5 px-3 rounded-l-xl">Jenis Iuran</th>
                    <th class="py-2.5 px-3">Periode</th>
                    <th class="py-2.5 px-3">Jatuh Tempo</th>
                    <th class="py-2.5 px-3">Nominal</th>
                    <th class="py-2.5 px-3">Terbayar</th>
                    <th class="py-2.5 px-3">Sisa</th>
                    <th class="py-2.5 px-3 rounded-r-xl">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="inv in invoiceList" :key="inv.id" class="hover:bg-slate-50/60 transition">
                    <td class="py-3 px-3 font-bold text-slate-800">{{ inv.fee_type?.nama || 'Iuran SPP Sekolah' }}</td>
                    <td class="py-3 px-3 font-mono text-slate-600">{{ inv.periode || '-' }}</td>
                    <td class="py-3 px-3 font-mono text-slate-600">{{ formatDate(inv.jatuh_tempo) }}</td>
                    <td class="py-3 px-3 font-mono font-bold text-slate-900">Rp {{ formatRupiah(inv.jumlah) }}</td>
                    <td class="py-3 px-3 font-mono text-emerald-700 font-semibold">Rp {{ formatRupiah(inv.jumlah_dibayar) }}</td>
                    <td class="py-3 px-3 font-mono text-rose-600 font-bold">Rp {{ formatRupiah(inv.sisa) }}</td>
                    <td class="py-3 px-3">
                      <span 
                        class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase"
                        :class="inv.status === 'lunas' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                      >
                        {{ inv.status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="text-center py-8 text-slate-400 text-xs bg-slate-50 rounded-2xl">
              Alhamdulillah, belum ada tagihan aktif untuk ananda.
            </div>
          </div>

          <!-- Petunjuk Pembayaran -->
          <div class="p-4 bg-emerald-50/60 rounded-2xl border border-emerald-200/80 text-xs text-slate-700 space-y-1.5">
            <div class="font-bold text-emerald-900 flex items-center gap-1.5">
              <span>💳 Petunjuk Pembayaran:</span>
            </div>
            <p class="leading-relaxed">
              Pembayaran SPP dapat dilakukan secara langsung di Loket Kasir Tata Usaha SDI Bina Insan Mandiri Purwokerto, atau melalui transfer ke rekening resmi sekolah (Bank Syariah Indonesia). Bukti transfer dapat dikonfirmasikan ke bagian keuangan.
            </p>
          </div>
        </div>

        <!-- Tab 5: PENGUMUMAN SEKOLAH -->
        <div v-if="activeTab === 'announcements'" class="p-5 sm:p-7 space-y-4">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-sm sm:text-base font-bold text-slate-900">Pengumuman & Informasi Resmi Sekolah</h3>
            <p class="text-xs text-slate-500">Pemberitahuan resmi, surat edaran, dan kalender kegiatan dari pihak sekolah</p>
          </div>

          <div v-if="announcementList.length > 0" class="space-y-4">
            <div 
              v-for="anc in announcementList" 
              :key="anc.id"
              class="p-5 rounded-2xl border transition space-y-2.5"
              :class="anc.is_pinned ? 'bg-amber-50/40 border-amber-200 shadow-2xs' : 'bg-white border-slate-200/80 shadow-xs'"
            >
              <div class="flex items-center justify-between text-xs">
                <div class="flex items-center gap-2">
                  <span v-if="anc.is_pinned" class="bg-amber-100 text-amber-900 font-extrabold text-[10px] px-2 py-0.5 rounded">
                    📌 SEMATAN
                  </span>
                  <span class="bg-emerald-100 text-emerald-800 font-bold text-[10px] px-2 py-0.5 rounded uppercase">
                    {{ anc.kategori || 'Umum' }}
                  </span>
                </div>
                <span class="text-slate-400 font-mono text-[11px]">{{ formatDate(anc.tgl_terbit) }}</span>
              </div>

              <h4 class="font-bold text-slate-900 text-base">{{ anc.judul }}</h4>
              <p class="text-xs sm:text-sm text-slate-600 leading-relaxed whitespace-pre-line">{{ anc.konten }}</p>
            </div>
          </div>

          <div v-else class="text-center py-12 text-slate-400 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
            <p class="text-xs">Belum ada pengumuman baru untuk wali murid saat ini.</p>
          </div>
        </div>

        <!-- Tab 6: E-RAPOR RESMI ANANDA -->
        <div v-if="activeTab === 'rapor'" class="p-5 sm:p-7 space-y-6">
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
          <div v-if="loadingReportCards" class="py-12 text-center text-slate-400">
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
                  @click="openReportDetailModal(rc)"
                  class="flex-1 py-2 px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center justify-center gap-1.5 cursor-pointer"
                >
                  <span>👁️</span>
                  <span>Lihat & Unduh</span>
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>

    <!-- MODAL PREVIEW DETAIL RAPOR UNTUK WALI MURID -->
    <div
      v-if="showReportModal && selectedReportDetail"
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
                  {{ selectedReportDetail.jenis_rapor?.replace('_', ' ') }}
                </span>
                <span class="text-xs text-emerald-200 font-bold">
                  {{ selectedReportDetail.periode || selectedReportDetail.semester }}
                </span>
              </div>
              <h3 class="text-lg font-black text-white mt-0.5">
                Rapor {{ summary?.student?.nama_lengkap }}
              </h3>
            </div>
          </div>
          <button
            @click="showReportModal = false"
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
              <strong class="text-slate-900">{{ selectedReportDetail.academic_year?.nama || '-' }}</strong>
            </div>
            <div>
              <span class="text-slate-400 block text-[10px] uppercase font-semibold">Rombel Kelas</span>
              <strong class="text-slate-900">{{ selectedReportDetail.school_class?.nama_kelas || '-' }}</strong>
            </div>
            <div>
              <span class="text-slate-400 block text-[10px] uppercase font-semibold">Fase Kurikulum</span>
              <strong class="text-slate-900">Fase {{ selectedReportDetail.fase || 'A' }}</strong>
            </div>
            <div>
              <span class="text-slate-400 block text-[10px] uppercase font-semibold">Kehadiran (S/I/A)</span>
              <strong class="text-emerald-800">
                S: {{ selectedReportDetail.sakit || 0 }} • I: {{ selectedReportDetail.izin || 0 }} • A: {{ selectedReportDetail.alpa || 0 }}
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
                  <tr v-if="!selectedReportDetail.grades || selectedReportDetail.grades.length === 0">
                    <td colspan="5" class="py-6 text-center text-slate-400 italic">Belum ada daftar nilai tercatat.</td>
                  </tr>
                  <tr v-for="(g, idx) in selectedReportDetail.grades" :key="idx" class="hover:bg-slate-50/50">
                    <td class="py-2.5 px-3 text-center text-slate-400 font-mono">{{ idx + 1 }}</td>
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
          <div v-if="selectedReportDetail.catatan_wali_kelas" class="p-4 bg-emerald-50/50 border border-emerald-200 rounded-2xl">
            <h5 class="font-bold text-emerald-950 text-xs mb-1">Catatan & Pesan Wali Kelas:</h5>
            <p class="text-xs text-emerald-900 leading-relaxed italic">
              "{{ selectedReportDetail.catatan_wali_kelas }}"
            </p>
          </div>

          <!-- Validasi & Tanda Tangan -->
          <div class="grid grid-cols-2 gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-200 text-center">
            <div>
              <span class="text-slate-400 text-[10px] block">Wali Kelas Pengampu:</span>
              <strong class="text-slate-900 text-xs block mt-1">{{ selectedReportDetail.wali_kelas_nama || '-' }}</strong>
              <span class="text-slate-400 text-[10px] font-mono">NIP: {{ selectedReportDetail.wali_kelas_nip || '-' }}</span>
            </div>
            <div>
              <span class="text-slate-400 text-[10px] block">Disahkan oleh Kepala Sekolah:</span>
              <strong class="text-slate-900 text-xs block mt-1">{{ selectedReportDetail.kepala_sekolah_nama || '-' }}</strong>
              <span class="text-slate-400 text-[10px] font-mono">NIP: {{ selectedReportDetail.kepala_sekolah_nip || '-' }}</span>
            </div>
          </div>
        </div>

        <!-- Modal Footer Actions -->
        <div class="p-4 bg-slate-100/80 border-t border-slate-200 flex items-center justify-between gap-3">
          <button
            @click="showReportModal = false"
            class="px-4 py-2 bg-white hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 transition cursor-pointer"
          >
            Tutup
          </button>
          <button
            @click="printReportDetail"
            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black rounded-xl shadow-md transition flex items-center gap-1.5 cursor-pointer"
          >
            <span>🖨️</span>
            <span>Cetak / Unduh PDF Dokumen Resmi</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import api from '../services/api';

const auth = useAuthStore();
const children = ref<any[]>([]);
const selectedChildId = ref<number | null>(null);
const summary = ref<any>(null);
const loading = ref(false);
const activeTab = ref<'progress' | 'attendance' | 'activities' | 'finance' | 'announcements' | 'rapor'>('progress');
const reportCardList = ref<any[]>([]);
const loadingReportCards = ref(false);
const showReportModal = ref(false);
const selectedReportDetail = ref<any>(null);

const fetchChildReportCards = async () => {
  if (!selectedChildId.value) return;
  loadingReportCards.value = true;
  try {
    const res = await api.get(`/parent/children/${selectedChildId.value}/report-cards`);
    if (res.data.success && res.data.data) {
      reportCardList.value = res.data.data;
    }
  } catch (err) {
    console.error('Gagal mengambil e-rapor ananda:', err);
    reportCardList.value = [];
  } finally {
    loadingReportCards.value = false;
  }
};

const openReportDetailModal = async (rc: any) => {
  try {
    const res = await api.get(`/report-cards/${rc.id}`);
    if (res.data.success && res.data.data) {
      selectedReportDetail.value = res.data.data;
      showReportModal.value = true;
      return;
    }
  } catch (err) {
    console.error('Gagal mengambil detail rapor:', err);
  }
  selectedReportDetail.value = rc;
  showReportModal.value = true;
};

const printReportDetail = () => {
  window.print();
};

const formatRupiah = (val: number | string | null | undefined) => {
  return new Intl.NumberFormat('id-ID').format(Math.round(Number(val) || 0));
};

const formatDate = (dateStr: string | null | undefined): string => {
  if (!dateStr) return '-';
  try {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return String(dateStr).substring(0, 10);
    return d.toLocaleDateString('id-ID', {
      weekday: 'short',
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    });
  } catch {
    return String(dateStr);
  }
};

// Computed safe accessors
const attendanceSummary = computed(() => {
  return summary.value?.attendance_summary || summary.value?.presensi_bulan_ini || {};
});

const financialSummary = computed(() => {
  return summary.value?.keuangan || {};
});

const recentAttendanceList = computed(() => {
  return attendanceSummary.value.recent || [];
});

const latestProgressList = computed(() => {
  return summary.value?.latest_progress || summary.value?.progres_terakhir || [];
});

const latestActivitiesList = computed(() => {
  return summary.value?.latest_activities || [];
});

const announcementList = computed(() => {
  return summary.value?.announcements || [];
});

const invoiceList = computed(() => {
  return financialSummary.value.invoices || [];
});

const homeroomTeacherName = computed(() => {
  return summary.value?.student?.school_class?.homeroom_teacher?.nama_lengkap || 'Ustadz/Ustadzah Wali Kelas';
});

const homeroomTeacherPhone = computed(() => {
  return summary.value?.student?.school_class?.homeroom_teacher?.no_hp || '';
});

const getWhatsAppUrl = (phone: string, studentName: string) => {
  let cleanPhone = phone.replace(/[^0-9]/g, '');
  if (cleanPhone.startsWith('0')) {
    cleanPhone = '62' + cleanPhone.slice(1);
  }
  const msg = encodeURIComponent(`Assalamu'alaikum Ustadz/Ustadzah, mohon izin menanyakan terkait ananda ${studentName || ''}.`);
  return `https://wa.me/${cleanPhone}?text=${msg}`;
};

const todayAttendanceStatusText = computed(() => {
  const today = attendanceSummary.value.today;
  if (!today) return 'Belum Absen';
  if (today.status === 'hadir') return 'Hadir di Kelas';
  if (today.status === 'sakit') return 'Sakit';
  if (today.status === 'izin') return 'Izin';
  if (today.status === 'alpa') return 'Tanpa Keterangan';
  return today.status;
});

const todayAttendanceStatusClass = computed(() => {
  const today = attendanceSummary.value.today;
  if (!today) return 'text-slate-400';
  if (today.status === 'hadir') return 'text-emerald-700';
  if (today.status === 'sakit') return 'text-amber-700';
  if (today.status === 'izin') return 'text-sky-700';
  return 'text-rose-700';
});

const latestProgressTitle = computed(() => {
  const list = latestProgressList.value;
  if (list && list.length > 0) {
    return list[0].capaian || list[0].kategori || 'Tercatat';
  }
  return 'Belum ada catatan';
});

const latestProgressPredicate = computed(() => {
  const list = latestProgressList.value;
  if (list && list.length > 0 && list[0].nilai) {
    return `Predikat: ${list[0].nilai}`;
  }
  return 'Rajin & Mutqin';
});

const fetchChildren = async () => {
  loading.value = true;
  try {
    const res = await api.get('/parent/children');
    if (res.data.success && res.data.data.length > 0) {
      children.value = res.data.data;
      selectChild(children.value[0].id);
    }
  } catch (err) {
    console.error('Gagal mengambil daftar anak', err);
  } finally {
    loading.value = false;
  }
};

const selectChild = async (id: number) => {
  selectedChildId.value = id;
  loading.value = true;
  try {
    const res = await api.get(`/parent/children/${id}/summary`);
    if (res.data.success) {
      summary.value = res.data.data;
    }
  } catch (err) {
    console.error('Gagal mengambil ringkasan anak', err);
  } finally {
    loading.value = false;
    fetchChildReportCards();
  }
};

onMounted(() => {
  fetchChildren();
});
</script>
