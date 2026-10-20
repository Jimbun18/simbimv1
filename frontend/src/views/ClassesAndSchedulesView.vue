<template>
  <div class="space-y-6">
    <!-- ============================================================== -->
    <!-- VIEW 1: TAMPILAN TABS UTAMA (LIST/TABEL KELAS & JADWAL)         -->
    <!-- ============================================================== -->
    <div v-if="viewMode === 'tabs'" class="space-y-6">
      <!-- Header Dinamis Sesuai Sub-menu Sidebar yang Aktif -->
      <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold text-gray-900">
            {{
              activeTab === 'classes' ? 'Rombel Kelas' :
              activeTab === 'subjects' ? 'Mata Pelajaran & Tematik' :
              activeTab === 'assignments' ? 'Pembagian Mengajar Guru' :
              activeTab === 'schedules' ? 'Jadwal Mingguan KBM' :
              activeTab === 'slots' ? 'Jam Pelajaran (Time Slots)' :
              activeTab === 'rooms' ? 'Ruang Kelas & Fasilitas' :
              activeTab === 'academic_years' ? 'Tahun Ajaran & Kenaikan Kelas' : 'Kelas, Ruangan & Jadwal KBM'
            }}
          </h1>
          <p class="text-xs text-gray-500 mt-0.5">
            {{
              activeTab === 'classes' ? 'Kelola data rombel kelas aktif, alokasi wali kelas, dan kapasitas siswa.' :
              activeTab === 'subjects' ? 'Kelola kurikulum tematik terpadu, muatan pelajaran terintegrasi, dan mapel reguler.' :
              activeTab === 'assignments' ? 'Kelola pembagian penugasan guru pengampu tema/pelajaran untuk tiap rombel kelas.' :
              activeTab === 'schedules' ? 'Kelola dan pantau susunan jadwal kegiatan belajar mengajar (KBM) mingguan.' :
              activeTab === 'slots' ? 'Kelola alokasi waktu sesi dan jam pelajaran kegiatan belajar mengajar.' :
              activeTab === 'rooms' ? 'Kelola data ruangan kelas dan fasilitas pembelajaran.' :
              activeTab === 'academic_years' ? 'Kelola status tahun ajaran aktif, pergantian semester, penutupan periode, dan kenaikan kelas siswa secara massal.' : 'Kelola rombel kelas aktif, alokasi wali kelas, jam pelajaran, dan jadwal kegiatan belajar mengajar.'
            }}
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button
            v-if="activeTab === 'classes'"
            @click="exportClassesExcel"
            :disabled="exporting"
            class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-xl border border-emerald-200 transition disabled:opacity-50 cursor-pointer"
            title="Export Data Kelas & Rombel ke Excel"
          >
            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span v-if="exporting">Mengekspor...</span>
            <span v-else>Export Excel</span>
          </button>
          <router-link
            to="/my-schedule"
            class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-xl border border-emerald-200 transition"
            title="Buka Tampilan Kartu Jadwal Guru & Masuk Kelas"
          >
            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <span>Kartu Jadwal Guru</span>
          </router-link>
          <button
            v-if="activeTab === 'classes'"
            @click="openCreateClassForm"
            class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md shadow-purple-600/20 transition cursor-pointer active:scale-95"
          >
            + Tambah Rombel Kelas
          </button>
          <button
            v-else-if="activeTab === 'subjects'"
            @click="openCreateSubjectForm"
            class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md shadow-purple-600/20 transition cursor-pointer active:scale-95"
          >
            + Tambah Pelajaran / Tema
          </button>
          <button
            v-else-if="activeTab === 'assignments'"
            @click="openCreateAssignmentModal"
            class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md shadow-purple-600/20 transition cursor-pointer active:scale-95"
          >
            + Tambah Pembagian Mengajar
          </button>
          <button
            v-else-if="activeTab === 'schedules'"
            @click="openCreateScheduleForm"
            class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md shadow-purple-600/20 transition cursor-pointer active:scale-95"
          >
            + Tambah Jadwal KBM
          </button>
          <button
            v-else-if="activeTab === 'academic_years'"
            @click="openCreateYearModal"
            class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md shadow-purple-600/20 transition cursor-pointer active:scale-95"
          >
            + Buat Tahun Ajaran Baru
          </button>
          <button
            v-else-if="activeTab === 'rooms'"
            @click="openCreateRoomModal"
            class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md shadow-purple-600/20 transition cursor-pointer active:scale-95"
          >
            + Tambah Ruang Kelas
          </button>
        </div>
      </div>

      <!-- Tab 1: Rombel Kelas -->
      <div v-if="activeTab === 'classes'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="c in classes" :key="c.id" class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm space-y-3 hover:shadow-md transition flex flex-col justify-between">
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="px-2.5 py-1 bg-purple-50 text-purple-700 border border-purple-200 rounded-lg text-xs font-bold font-mono">
                {{ c.kode }}
              </span>
              <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-[10px] font-bold uppercase">
                {{ c.status }}
              </span>
            </div>

            <div>
              <h3 class="text-base font-bold text-gray-900">{{ c.nama }}</h3>
              <p class="text-xs text-gray-500">Tingkat {{ c.tingkat }} (Paralel {{ c.paralel ?? 'A' }})</p>
            </div>

            <div class="pt-3 border-t border-gray-50 space-y-1.5 text-xs text-gray-600">
              <div class="flex items-center justify-between">
                <span class="text-gray-400">Wali Kelas:</span>
                <span class="font-bold text-gray-800">{{ c.homeroom_teacher?.nama_lengkap || c.wali_kelas?.nama_lengkap || 'Belum Ditugaskan' }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-gray-400">Ruangan:</span>
                <span class="font-medium text-gray-700">{{ c.room?.nama ?? '-' }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-gray-400">Kapasitas:</span>
                <span class="font-medium text-gray-700">{{ c.kapasitas ?? 28 }} Siswa</span>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
            <button
              @click="openEditClassForm(c)"
              class="px-3 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg text-xs font-semibold transition border border-amber-200 cursor-pointer"
            >
              Edit
            </button>
            <button
              @click="handleDeleteClass(c)"
              class="px-3 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-semibold transition border border-rose-200 cursor-pointer"
            >
              Hapus
            </button>
          </div>
        </div>
        <div v-if="classes.length === 0" class="col-span-full py-12 text-center text-gray-400">
          Belum ada data rombel kelas. Klik "+ Tambah Rombel Kelas" untuk memulai.
        </div>
      </div>

      <!-- Tab 2: Mata Pelajaran & Tematik Terpadu -->
      <div v-else-if="activeTab === 'subjects'" class="space-y-4">
        <!-- Quick Stats Cards for Pelajaran & Tematik -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center text-xl shrink-0">
              📚
            </div>
            <div>
              <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Pelajaran</div>
              <div class="text-lg font-black text-gray-900">{{ subjectsList.length }} Mapel</div>
            </div>
          </div>

          <div class="bg-white p-4 rounded-2xl border border-purple-100/80 shadow-xs flex items-center gap-3.5 bg-gradient-to-br from-purple-50/40 to-white">
            <div class="w-11 h-11 rounded-xl bg-purple-600 text-white flex items-center justify-center text-xl shrink-0 shadow-xs">
              ✨
            </div>
            <div>
              <div class="text-[11px] font-bold text-purple-600 uppercase tracking-wider">Tematik Terpadu</div>
              <div class="text-lg font-black text-purple-950">{{ tematikCount }} Tema</div>
            </div>
          </div>

          <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl shrink-0">
              📘
            </div>
            <div>
              <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Mapel Reguler</div>
              <div class="text-lg font-black text-gray-900">{{ regulerCount }} Mapel</div>
            </div>
          </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
          <div class="flex-1 relative">
            <input
              v-model="searchSubject"
              type="text"
              placeholder="Cari kode, nama pelajaran/tema, atau muatan..."
              class="w-full px-4 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-purple-500 outline-none transition"
            />
          </div>

          <div class="flex flex-wrap sm:flex-nowrap gap-2">
            <select
              v-model="filterSubjectType"
              class="px-3 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-purple-500 outline-none text-gray-700"
            >
              <option value="all">Semua Tipe Pelajaran</option>
              <option value="tematik">✨ Khusus Tematik Terpadu</option>
              <option value="reguler">📘 Khusus Reguler / Mandiri</option>
            </select>

            <select
              v-model="filterSubjectLevel"
              class="px-3 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-purple-500 outline-none text-gray-700"
            >
              <option value="">Semua Tingkat Kelas</option>
              <option value="Kelas 1">Kelas 1</option>
              <option value="Kelas 2">Kelas 2</option>
              <option value="Kelas 3">Kelas 3</option>
              <option value="Kelas 4">Kelas 4</option>
              <option value="Kelas 5">Kelas 5</option>
              <option value="Kelas 6">Kelas 6</option>
            </select>
          </div>
        </div>

        <!-- Cards Grid Mata Pelajaran & Tematik -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="sub in filteredSubjects"
            :key="sub.id"
            :class="sub.is_tematik ? 'border-purple-200/80 bg-gradient-to-br from-purple-50/20 via-white to-white' : 'border-gray-100 bg-white'"
            class="p-5 rounded-2xl border shadow-sm hover:shadow-md transition flex flex-col justify-between"
          >
            <div class="space-y-3">
              <!-- Top Badges -->
              <div class="flex items-center justify-between">
                <span
                  v-if="sub.is_tematik"
                  class="px-2.5 py-1 bg-purple-600 text-white rounded-lg text-[10px] font-black uppercase tracking-wider shadow-xs flex items-center gap-1"
                >
                  <span>✨</span>
                  <span>TEMATIK (Tema {{ sub.tema_nomor || 1 }})</span>
                </span>
                <span
                  v-else
                  class="px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg text-[10px] font-bold uppercase tracking-wider"
                >
                  📘 {{ sub.kelompok }}
                </span>

                <span class="px-2 py-0.5 font-mono text-xs font-bold bg-gray-100 text-gray-800 rounded-md">
                  {{ sub.kode }}
                </span>
              </div>

              <!-- Title & Target Level -->
              <div>
                <h3 class="text-base font-black text-gray-900 leading-snug">
                  {{ sub.nama }}
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">
                  Berlaku: <strong class="text-gray-700">{{ sub.tingkat_berlaku || 'Semua Tingkat' }}</strong>
                </p>
              </div>

              <!-- Muatan Campuran Pelajaran (Khusus Tematik) -->
              <div v-if="sub.is_tematik && sub.muatan_pelajaran && sub.muatan_pelajaran.length" class="space-y-1.5 pt-1">
                <span class="text-[10px] font-bold text-purple-700 uppercase tracking-wider block">
                  Muatan Pelajaran Terintegrasi:
                </span>
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="muatan in sub.muatan_pelajaran"
                    :key="muatan"
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200"
                  >
                    {{ muatan }}
                  </span>
                </div>
              </div>

              <!-- Deskripsi Ringkas -->
              <p v-if="sub.deskripsi" class="text-xs text-gray-500 italic line-clamp-2">
                {{ sub.deskripsi }}
              </p>

              <!-- Meta Footer Info -->
              <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                <span>JP / Minggu: <strong class="text-gray-800">{{ sub.jam_per_minggu ?? 4 }} JP</strong></span>
                <span>KKM: <strong class="text-gray-800">{{ sub.kkm ?? 75 }}</strong></span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2 mt-4">
              <button
                @click="openEditSubjectForm(sub)"
                class="px-3 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg text-xs font-semibold transition border border-amber-200 cursor-pointer"
              >
                Edit
              </button>
              <button
                @click="handleDeleteSubject(sub)"
                class="px-3 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-semibold transition border border-rose-200 cursor-pointer"
              >
                Hapus
              </button>
            </div>
          </div>

          <div v-if="filteredSubjects.length === 0" class="col-span-full py-12 text-center text-gray-400">
            Tidak ada mata pelajaran atau tema yang sesuai dengan filter.
          </div>
        </div>
      </div>

      <!-- Tab Penugasan: Pembagian Mengajar Guru (CRUD Tema - Guru - Kelas) -->
      <div v-else-if="activeTab === 'assignments'" class="space-y-5">
        <!-- Control Header & Toolbar -->
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 bg-purple-50 text-purple-700 text-[10px] font-black uppercase tracking-wider rounded-md border border-purple-200">
                Alokasi Mengajar
              </span>
              <span class="text-xs text-gray-400">TP 2026/2027 (Ganjil)</span>
            </div>
            <h2 class="text-lg font-black text-gray-900 mt-1">
              Pembagian Mengajar Guru (Mata Pelajaran & Tematik)
            </h2>
            <p class="text-xs text-gray-500">
              Kelola data tema apa saja, siapa guru pengampunya, dan di kelas mana saja mengajar. Perubahan di sini otomatis terhubung langsung ke jadwal KBM mingguan.
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <button
              @click="syncAssignmentsWithSchedule"
              :disabled="syncingAssignments || assignmentsList.length === 0"
              class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-md shadow-emerald-600/20 flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
              title="Sinkronkan seluruh penugasan guru ini ke jadwal mingguan"
            >
              <svg v-if="syncingAssignments" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span v-else>⚡</span>
              <span>{{ syncingAssignments ? 'Menyinkronkan...' : 'Sinkronkan ke Jadwal Mingguan' }}</span>
            </button>
            <button
              @click="openCreateAssignmentModal"
              class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition shadow-md shadow-purple-600/20 flex items-center gap-1.5 cursor-pointer"
            >
              <span>+</span>
              <span>Tambah Penugasan</span>
            </button>
          </div>
        </div>

        <!-- KPI Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
          <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Total Penugasan</span>
            <div class="text-xl font-black text-gray-900 mt-0.5">{{ assignmentsList.length }}</div>
            <span class="text-[10px] text-purple-600 font-semibold">Tema & Mapel</span>
          </div>
          <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Guru Terlibat</span>
            <div class="text-xl font-black text-emerald-600 mt-0.5">{{ uniqueTeachersCount }}</div>
            <span class="text-[10px] text-gray-500">Pendidik Aktif</span>
          </div>
          <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Rombel Tercover</span>
            <div class="text-xl font-black text-blue-600 mt-0.5">{{ uniqueClassesCount }}</div>
            <span class="text-[10px] text-gray-500">Kelas 1 s/d Kelas 4</span>
          </div>
          <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Keterangan Khusus</span>
            <div class="text-xs font-bold text-amber-700 mt-1">Tema 4: Pagi & Siang</div>
            <span class="text-[10px] text-gray-400">Rosyid (Pagi) • Bayu (Siang)</span>
          </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-3.5 rounded-2xl border border-gray-100 shadow-2xs flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="flex flex-wrap items-center gap-2 flex-1">
            <select
              v-model="assignmentFilterSubject"
              class="border border-gray-200 rounded-xl px-3 py-1.5 text-xs bg-white text-gray-700 focus:ring-2 focus:ring-purple-500 outline-none"
            >
              <option value="">-- Semua Tema / Mapel --</option>
              <option v-for="sub in subjectsList" :key="sub.id" :value="sub.id">
                {{ sub.kode }} - {{ sub.nama }}
              </option>
            </select>

            <select
              v-model="assignmentFilterTeacher"
              class="border border-gray-200 rounded-xl px-3 py-1.5 text-xs bg-white text-gray-700 focus:ring-2 focus:ring-purple-500 outline-none"
            >
              <option value="">-- Semua Guru --</option>
              <option v-for="t in teachersList" :key="t.id" :value="t.id">
                {{ t.nama_lengkap }}
              </option>
            </select>

            <select
              v-model="assignmentFilterClass"
              class="border border-gray-200 rounded-xl px-3 py-1.5 text-xs bg-white text-gray-700 focus:ring-2 focus:ring-purple-500 outline-none"
            >
              <option value="">-- Semua Rombel Kelas --</option>
              <option v-for="c in classes" :key="c.id" :value="c.id">
                {{ c.nama }}
              </option>
            </select>
          </div>

          <span class="text-gray-400 font-mono text-[11px] shrink-0">
            Menampilkan {{ filteredAssignments.length }} dari {{ assignmentsList.length }} Penugasan
          </span>
        </div>

        <!-- Assignments Table -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-[760px]">
              <thead class="bg-gray-50/80 border-b border-gray-100 text-gray-500 font-bold uppercase text-[10px] tracking-wider">
                <tr>
                  <th class="py-3 px-4 w-12 text-center">No</th>
                  <th class="py-3 px-4">Mata Pelajaran / Tema</th>
                  <th class="py-3 px-4">Guru Pengampu</th>
                  <th class="py-3 px-4">Rombel Kelas</th>
                  <th class="py-3 px-4">Sesi Waktu / Keterangan</th>
                  <th class="py-3 px-4 text-center">Beban Jam</th>
                  <th class="py-3 px-4 text-center w-28">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-if="loadingAssignments">
                  <td colspan="7" class="py-12 text-center text-gray-400">
                    <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-purple-500 border-t-transparent mb-1"></div>
                    <div>Memuat pembagian mengajar guru...</div>
                  </td>
                </tr>
                <tr v-else-if="filteredAssignments.length === 0">
                  <td colspan="7" class="py-12 text-center text-gray-400">
                    Belum ada penugasan mengajar yang cocok dengan filter.
                  </td>
                </tr>
                <tr
                  v-for="(item, idx) in filteredAssignments"
                  :key="item.id"
                  class="hover:bg-purple-50/30 transition group"
                >
                  <td class="py-3.5 px-4 text-center font-mono text-gray-400">{{ idx + 1 }}</td>
                  <td class="py-3.5 px-4">
                    <div class="flex items-center gap-2">
                      <span
                        class="px-2 py-0.5 rounded-md font-black font-mono text-[10px] tracking-wider shrink-0"
                        :class="item.subject?.is_tematik ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-slate-100 text-slate-700 border border-slate-200'"
                      >
                        {{ item.subject?.kode || 'MAPEL' }}
                      </span>
                      <div>
                        <span class="font-bold text-gray-900 block">{{ item.subject?.nama }}</span>
                        <span v-if="item.subject?.is_tematik" class="text-[10px] text-purple-600 font-semibold">Tematik Terpadu</span>
                      </div>
                    </div>
                  </td>
                  <td class="py-3.5 px-4">
                    <div class="flex items-center gap-2.5">
                      <div class="w-7 h-7 rounded-full bg-purple-100 text-purple-700 font-bold flex items-center justify-center text-xs shrink-0">
                        {{ (item.teacher?.nama_lengkap || '?').substring(0, 2).toUpperCase() }}
                      </div>
                      <div>
                        <strong class="text-gray-900 font-bold block">{{ item.teacher?.nama_lengkap }}</strong>
                        <span class="text-[10px] text-gray-400 font-mono">{{ item.teacher?.nip ? `NIP. ${item.teacher.nip}` : 'Pendidik' }}</span>
                      </div>
                    </div>
                  </td>
                  <td class="py-3.5 px-4">
                    <span class="px-2.5 py-1 rounded-xl bg-blue-50 text-blue-800 font-bold text-xs border border-blue-200 flex items-center gap-1 w-fit">
                      <span>🏫</span>
                      <span>{{ item.school_class?.nama || item.class?.nama || 'Kelas' }}</span>
                    </span>
                  </td>
                  <td class="py-3.5 px-4">
                    <span
                      class="px-2.5 py-1 rounded-lg text-[11px] font-semibold flex items-center gap-1 w-fit"
                      :class="item.keterangan && item.keterangan.includes('Pagi')
                        ? 'bg-amber-50 text-amber-900 border border-amber-300 font-bold'
                        : item.keterangan && item.keterangan.includes('Siang')
                        ? 'bg-sky-50 text-sky-900 border border-sky-300 font-bold'
                        : 'bg-emerald-50 text-emerald-800 border border-emerald-200'"
                    >
                      <span>{{ item.keterangan && item.keterangan.includes('Pagi') ? '☀️' : item.keterangan && item.keterangan.includes('Siang') ? '⛅' : '📖' }}</span>
                      <span>{{ item.keterangan || 'Reguler' }}</span>
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-center font-bold text-gray-700">
                    {{ item.jam_per_minggu || 4 }} JP
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                      <button
                        @click="openEditAssignmentModal(item)"
                        class="p-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg transition border border-amber-200 cursor-pointer"
                        title="Edit Penugasan / Ganti Guru Pengampu"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                      </button>
                      <button
                        @click="handleDeleteAssignment(item)"
                        class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg transition border border-rose-200 cursor-pointer"
                        title="Hapus Penugasan Ini"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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

      <!-- Tab 3: Jadwal Pelajaran (Matriks Grid Resmi Sesuai Format Cetak) -->
      <div v-else-if="activeTab === 'schedules'" class="space-y-4">
        <!-- Control Header & Rombel Switcher Toolbar -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4 no-print">
          <!-- Class Selector Pills -->
          <div class="space-y-1.5">
            <div class="flex items-center gap-2">
              <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Pilih Rombel Kelas:</span>
              <span class="px-2 py-0.5 bg-purple-50 text-purple-700 text-[10px] font-bold rounded-md border border-purple-200">
                TP 2026/2027 (Ganjil)
              </span>
            </div>
            <div class="flex flex-wrap items-center gap-1.5">
              <button
                v-for="cls in classes"
                :key="cls.id"
                @click="selectClass(cls.id)"
                :class="selectedClassId === cls.id ? 'bg-purple-600 text-white shadow-xs font-bold ring-2 ring-purple-600/30' : 'bg-gray-50 hover:bg-gray-100 text-gray-700 font-semibold border border-gray-200'"
                class="px-3.5 py-1.5 rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5"
              >
                <span>{{ cls.nama }}</span>
                <span class="text-[10px] opacity-75 font-mono">({{ cls.kode }})</span>
              </button>
            </div>
          </div>

          <!-- Actions Toolbar -->
          <div class="flex flex-wrap items-center gap-2">
            <!-- View Mode Toggle -->
            <div class="bg-gray-100 p-0.5 rounded-xl flex items-center text-xs border border-gray-200">
              <button
                @click="scheduleViewMode = 'matrix'"
                :class="scheduleViewMode === 'matrix' ? 'bg-white text-gray-900 font-bold shadow-2xs' : 'text-gray-500 hover:text-gray-800'"
                class="px-2.5 py-1 rounded-lg transition cursor-pointer flex items-center gap-1"
                title="Tampilan Matriks Grid Resmi (Sesuai Kertas Jadwal)"
              >
                <span>📊</span>
                <span class="hidden sm:inline">Matriks Resmi</span>
              </button>
              <button
                @click="scheduleViewMode = 'table'"
                :class="scheduleViewMode === 'table' ? 'bg-white text-gray-900 font-bold shadow-2xs' : 'text-gray-500 hover:text-gray-800'"
                class="px-2.5 py-1 rounded-lg transition cursor-pointer flex items-center gap-1"
                title="Tampilan Daftar Tabel Linear"
              >
                <span>📋</span>
                <span class="hidden sm:inline">Daftar Baris</span>
              </button>
            </div>

            <!-- Print Button -->
            <button
              @click="printSchedule"
              class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer active:scale-95"
              title="Cetak Jadwal Pelajaran Format Resmi A4 Landscape"
            >
              <svg class="w-4 h-4 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
              <span>Cetak A4</span>
            </button>

            <!-- Quick Template Loader -->
            <button
              @click="applyOfficialTemplateToClass"
              :disabled="loadingSchedules"
              class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer active:scale-95 disabled:opacity-50"
              title="Terapkan Susunan Jadwal Standar (Lentera, Tematik, Shalat, Ekskul)"
            >
              <span>⚡</span>
              <span>Terapkan Template</span>
            </button>

            <!-- Clear Schedule -->
            <button
              @click="clearEntireClassSchedules"
              :disabled="loadingSchedules"
              class="inline-flex items-center gap-1 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-semibold transition cursor-pointer disabled:opacity-50"
              title="Kosongkan seluruh slot jadwal kelas ini"
            >
              <span>🧹</span>
              <span class="hidden sm:inline">Bersihkan</span>
            </button>
          </div>
        </div>

        <!-- Empty Class Schedule Helper Banner -->
        <div v-if="scheduleViewMode === 'matrix' && schedules.length === 0" class="p-3.5 bg-blue-50/90 border border-blue-200 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs text-blue-950 no-print">
          <div class="flex items-center gap-2.5">
            <span class="text-xl">📅</span>
            <div>
              <p class="font-bold text-sm text-blue-950">Jadwal {{ selectedClass?.nama }} Belum Memiliki Sesi Terjadwal</p>
              <p class="text-blue-700 text-xs mt-0.5">
                Anda dapat klik <strong>"⚡ Terapkan Template"</strong> untuk menyalin susunan jadwal standar dinas, atau klik sel <strong>+ Atur</strong> mana saja pada tabel di bawah untuk mengisi sesi.
              </p>
            </div>
          </div>
          <button
            type="button"
            @click="applyOfficialTemplateToClass"
            :disabled="loadingSchedules"
            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-xs transition active:scale-95 cursor-pointer whitespace-nowrap flex items-center gap-1.5 disabled:opacity-50"
          >
            <span>⚡ Terapkan Template Sekarang</span>
          </button>
        </div>

        <!-- Notification Banner: Hint for Interactive Grid -->
        <div v-if="scheduleViewMode === 'matrix'" class="p-3 bg-amber-50/80 border border-amber-200/80 rounded-xl flex items-center justify-between text-xs text-amber-900 no-print">
          <div class="flex items-center gap-2">
            <span class="text-base">💡</span>
            <span><strong>Tip Penggunaan:</strong> Klik pada sel tema atau kegiatan mana saja di tabel untuk langsung mengubah judul, mata pelajaran, guru pengampu, atau catatan.</span>
          </div>
          <span class="font-bold text-[11px] bg-amber-200/70 px-2 py-0.5 rounded text-amber-950">Klik Sel untuk Edit</span>
        </div>

        <!-- ============================================================== -->
        <!-- VIEW A: RESMI MATRIX GRID TIMETABLE (PERSIS FOTO KERTAS JADWAL) -->
        <!-- ============================================================== -->
        <div v-if="scheduleViewMode === 'matrix'" class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-200 shadow-sm print-area">
          <!-- Timetable Header Title (Always printed) -->
          <div class="text-center mb-4">
            <h2 class="text-sm sm:text-base md:text-lg font-black text-gray-900 tracking-wider uppercase">
              JADWAL PELAJARAN {{ selectedClass?.nama?.toUpperCase() || 'KELAS 3' }}
            </h2>
            <p class="text-xs sm:text-sm font-bold text-gray-700 tracking-wider uppercase">
              TAHUN PELAJARAN 2026/2027
            </p>
          </div>

          <!-- The 10-Column Timetable Table -->
          <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-amber-800 text-xs table-fixed min-w-[950px] select-none">
              <!-- Colgroup for consistent widths -->
              <colgroup>
                <col style="width: 55px;" />
                <col style="width: 88px;" />
                <col style="width: 130px;" />
                <col style="width: 88px;" />
                <col style="width: 125px;" />
                <col style="width: 125px;" />
                <col style="width: 125px;" />
                <col style="width: 125px;" />
                <col style="width: 88px;" />
                <col style="width: 145px;" />
              </colgroup>

              <!-- Golden Header (Persis Foto) -->
              <thead>
                <tr class="bg-[#d97706] text-black font-extrabold uppercase text-[11px] sm:text-xs">
                  <th class="border border-amber-800 py-2 px-1 text-center font-black">JP</th>
                  <th class="border border-amber-800 py-2 px-1 text-center font-black">JAM</th>
                  <th class="border border-amber-800 py-2 px-2 text-center font-black">SENIN</th>
                  <th class="border border-amber-800 py-2 px-1 text-center font-black">JAM</th>
                  <th class="border border-amber-800 py-2 px-2 text-center font-black">SELASA</th>
                  <th class="border border-amber-800 py-2 px-2 text-center font-black">RABU</th>
                  <th class="border border-amber-800 py-2 px-2 text-center font-black">KAMIS</th>
                  <th class="border border-amber-800 py-2 px-2 text-center font-black">JUM'AT</th>
                  <th class="border border-amber-800 py-2 px-1 text-center font-black">JAM</th>
                  <th class="border border-amber-800 py-2 px-2 text-center font-black">SABTU</th>
                </tr>
              </thead>

              <tbody>
                <!-- ROW 1: 06.30 - 07.05 -->
                <tr>
                  <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/40 text-gray-800 py-2">1 x 35</td>
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-2 text-[11px] whitespace-nowrap">{{ formatSlotTime('senin', 1, '06.30 - 07.05') }}</td>
                  <!-- Senin Cell (Urutan 1) -->
                  <td @click="openQuickSet('senin', 1)" :class="cellStyleClass('senin', 1)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 1)">
                    <CellContent :hari="'senin'" :urutan="1" fallback="LENTERA" />
                  </td>
                  <!-- Jam Sel-Jum (Spans Row 1 & 2) -->
                  <td rowspan="2" class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-2 text-[11px] whitespace-nowrap align-middle">
                    {{ formatSlotTime('selasa', 1, '06.30 - 07.50') }}
                  </td>
                  <!-- Selasa (Urutan 1) -->
                  <td @click="openQuickSet('selasa', 1)" :class="cellStyleClass('selasa', 1)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 1)">
                    <CellContent :hari="'selasa'" :urutan="1" fallback="LENTERA" />
                  </td>
                  <!-- Rabu (Urutan 1) -->
                  <td @click="openQuickSet('rabu', 1)" :class="cellStyleClass('rabu', 1)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 1)">
                    <CellContent :hari="'rabu'" :urutan="1" fallback="LENTERA" />
                  </td>
                  <!-- Kamis (Urutan 1) -->
                  <td @click="openQuickSet('kamis', 1)" :class="cellStyleClass('kamis', 1)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 1)">
                    <CellContent :hari="'kamis'" :urutan="1" fallback="LENTERA" />
                  </td>
                  <!-- Jum'at (Urutan 1) -->
                  <td @click="openQuickSet('jumat', 1)" :class="cellStyleClass('jumat', 1)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 1)">
                    <CellContent :hari="'jumat'" :urutan="1" fallback="LENTERA" />
                  </td>
                  <!-- Jam Sabtu -->
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-2 text-[11px] whitespace-nowrap">{{ formatSlotTime('sabtu', 1, '07.00 - 07.30') }}</td>
                  <!-- Sabtu (Urutan 1) -->
                  <td @click="openQuickSet('sabtu', 1)" :class="cellStyleClass('sabtu', 1)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('sabtu', 1)">
                    <CellContent :hari="'sabtu'" :urutan="1" fallback="Dzikir+ Senam" />
                  </td>
                </tr>

                <!-- ROW 2: 07.05 - 07.40 -->
                <tr>
                  <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/40 text-gray-800 py-2">1 x 35</td>
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-2 text-[11px] whitespace-nowrap">{{ formatSlotTime('senin', 2, '07.05 - 07.40') }}</td>
                  <!-- Senin (Urutan 2) -->
                  <td @click="openQuickSet('senin', 2)" :class="cellStyleClass('senin', 2)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 2)">
                    <CellContent :hari="'senin'" :urutan="2" fallback="Upacara" />
                  </td>
                  <!-- Sel-Jum Jam spanned from row 1 -->
                  <!-- Selasa (Urutan 2) -->
                  <td @click="openQuickSet('selasa', 2)" :class="cellStyleClass('selasa', 2)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 2)">
                    <CellContent :hari="'selasa'" :urutan="2" fallback="LENTERA" />
                  </td>
                  <!-- Rabu (Urutan 2) -->
                  <td @click="openQuickSet('rabu', 2)" :class="cellStyleClass('rabu', 2)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 2)">
                    <CellContent :hari="'rabu'" :urutan="2" fallback="LENTERA" />
                  </td>
                  <!-- Kamis (Urutan 2) -->
                  <td @click="openQuickSet('kamis', 2)" :class="cellStyleClass('kamis', 2)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 2)">
                    <CellContent :hari="'kamis'" :urutan="2" fallback="LENTERA" />
                  </td>
                  <!-- Jum'at (Urutan 2) -->
                  <td @click="openQuickSet('jumat', 2)" :class="cellStyleClass('jumat', 2)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 2)">
                    <CellContent :hari="'jumat'" :urutan="2" fallback="LENTERA" />
                  </td>
                  <!-- Jam Sabtu -->
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-2 text-[11px] whitespace-nowrap">{{ formatSlotTime('sabtu', 2, '07.30 - 08.30') }}</td>
                  <!-- Sabtu (Urutan 2) -->
                  <td @click="openQuickSet('sabtu', 2)" :class="cellStyleClass('sabtu', 2)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('sabtu', 2)">
                    <CellContent :hari="'sabtu'" :urutan="2" fallback="Ekstrakulikuler Pilihan" />
                  </td>
                </tr>

                <!-- ROW 3: Asmaul Husna -->
                <tr>
                  <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/20 text-gray-400 py-1.5">-</td>
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[11px] whitespace-nowrap">{{ formatSlotTime('senin', 3, '07.40 - 08.00') }}</td>
                  <!-- Senin (Urutan 3) -->
                  <td @click="openQuickSet('senin', 3)" :class="cellStyleClass('senin', 3)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 3)">
                    <CellContent :hari="'senin'" :urutan="3" fallback="Asmaul Husna" />
                  </td>
                  <!-- Jam Sel-Jum -->
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[11px] whitespace-nowrap">{{ formatSlotTime('selasa', 3, '07.50 - 08.00') }}</td>
                  <!-- Selasa (Urutan 3) -->
                  <td @click="openQuickSet('selasa', 3)" :class="cellStyleClass('selasa', 3)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 3)">
                    <CellContent :hari="'selasa'" :urutan="3" fallback="Asmaul Husna" />
                  </td>
                  <!-- Rabu (Urutan 3) -->
                  <td @click="openQuickSet('rabu', 3)" :class="cellStyleClass('rabu', 3)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 3)">
                    <CellContent :hari="'rabu'" :urutan="3" fallback="Asmaul Husna" />
                  </td>
                  <!-- Kamis (Urutan 3) -->
                  <td @click="openQuickSet('kamis', 3)" :class="cellStyleClass('kamis', 3)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 3)">
                    <CellContent :hari="'kamis'" :urutan="3" fallback="Asmaul Husna" />
                  </td>
                  <!-- Jum'at (Urutan 3) -->
                  <td @click="openQuickSet('jumat', 3)" :class="cellStyleClass('jumat', 3)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 3)">
                    <CellContent :hari="'jumat'" :urutan="3" fallback="Asmaul Husna" />
                  </td>
                  <!-- Jam Sabtu -->
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[11px] whitespace-nowrap">{{ formatSlotTime('sabtu', 3, '08.25 - 08.30') }}</td>
                  <!-- Sabtu (Urutan 3) -->
                  <td @click="openQuickSet('sabtu', 3)" :class="cellStyleClass('sabtu', 3)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('sabtu', 3)">
                    <CellContent :hari="'sabtu'" :urutan="3" fallback="Asmaul Husna" />
                  </td>
                </tr>

                <!-- ROW 4: 08.00 - 08.35 -->
                <tr>
                  <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/40 text-gray-800 py-2">1 X 35</td>
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-2 text-[11px] whitespace-nowrap">{{ formatSlotTime('senin', 4, '08.00 - 08.35') }}</td>
                  <!-- Senin (Urutan 4) -->
                  <td @click="openQuickSet('senin', 4)" :class="cellStyleClass('senin', 4)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 4)">
                    <CellContent :hari="'senin'" :urutan="4" fallback="TEMA 3" />
                  </td>
                  <!-- Jam Sel-Jum -->
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-2 text-[11px] whitespace-nowrap">{{ formatSlotTime('selasa', 4, '08.00 - 09.10') }}</td>
                  <!-- Selasa (Urutan 4) -->
                  <td @click="openQuickSet('selasa', 4)" :class="cellStyleClass('selasa', 4)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 4)">
                    <CellContent :hari="'selasa'" :urutan="4" fallback="TEMA 1" />
                  </td>
                  <!-- Rabu (Urutan 4) -->
                  <td @click="openQuickSet('rabu', 4)" :class="cellStyleClass('rabu', 4)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 4)">
                    <CellContent :hari="'rabu'" :urutan="4" fallback="TEMA 4" />
                  </td>
                  <!-- Kamis (Urutan 4) -->
                  <td @click="openQuickSet('kamis', 4)" :class="cellStyleClass('kamis', 4)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 4)">
                    <CellContent :hari="'kamis'" :urutan="4" fallback="TEMA 5" />
                  </td>
                  <!-- Jum'at (Urutan 4) -->
                  <td @click="openQuickSet('jumat', 4)" :class="cellStyleClass('jumat', 4)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 4)">
                    <CellContent :hari="'jumat'" :urutan="4" fallback="TEMA 2" />
                  </td>
                  <!-- Jam Sabtu -->
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-2 text-[11px] whitespace-nowrap">{{ formatSlotTime('sabtu', 4, '08.30 - 09.00') }}</td>
                  <!-- Sabtu (Urutan 4) -->
                  <td @click="openQuickSet('sabtu', 4)" :class="cellStyleClass('sabtu', 4)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('sabtu', 4)">
                    <CellContent :hari="'sabtu'" :urutan="4" fallback="Ekstrakulikuler Wajib" />
                  </td>
                </tr>

                <!-- ROW 5: 08.35 - 09.10 -->
                <tr>
                  <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/40 text-gray-800 py-2">1 X 35</td>
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-2 text-[11px] whitespace-nowrap">{{ formatSlotTime('senin', 5, '08.35 - 09.10') }}</td>
                  <!-- Senin (Urutan 5) -->
                  <td @click="openQuickSet('senin', 5)" :class="cellStyleClass('senin', 5)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 5)">
                    <CellContent :hari="'senin'" :urutan="5" fallback="TEMA 3" />
                  </td>
                  <!-- Jam Sel-Jum -->
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-2 text-[11px] whitespace-nowrap">{{ formatSlotTime('senin', 5, '08.35 - 09.10') }}</td>
                  <!-- Selasa (Urutan 5) -->
                  <td @click="openQuickSet('selasa', 5)" :class="cellStyleClass('selasa', 5)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 5)">
                    <CellContent :hari="'selasa'" :urutan="5" fallback="TEMA 1" />
                  </td>
                  <!-- Rabu (Urutan 5) -->
                  <td @click="openQuickSet('rabu', 5)" :class="cellStyleClass('rabu', 5)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 5)">
                    <CellContent :hari="'rabu'" :urutan="5" fallback="TEMA 4" />
                  </td>
                  <!-- Kamis (Urutan 5) -->
                  <td @click="openQuickSet('kamis', 5)" :class="cellStyleClass('kamis', 5)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 5)">
                    <CellContent :hari="'kamis'" :urutan="5" fallback="TEMA 5" />
                  </td>
                  <!-- Jum'at (Urutan 5) -->
                  <td @click="openQuickSet('jumat', 5)" :class="cellStyleClass('jumat', 5)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 5)">
                    <CellContent :hari="'jumat'" :urutan="5" fallback="TEMA 2" />
                  </td>
                  <!-- Jam Sabtu Empty -->
                  <td class="border border-amber-800 bg-[#fde047]"></td>
                  <!-- Sabtu Empty -->
                  <td class="border border-amber-800 bg-white"></td>
                </tr>

                <!-- ROW 6: Shalat Dhuha + Istirahat -->
                <tr>
                  <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/20 text-gray-400 py-1.5">-</td>
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[11px] whitespace-nowrap">{{ formatSlotTime('senin', 6, '09.10 - 09.40') }}</td>
                  <!-- Senin (Urutan 6) -->
                  <td @click="openQuickSet('senin', 6)" :class="cellStyleClass('senin', 6)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 6)">
                    <CellContent :hari="'senin'" :urutan="6" fallback="Shalat Dhuha + Istirahat" />
                  </td>
                  <!-- Jam Sel-Jum -->
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[11px] whitespace-nowrap">{{ formatSlotTime('senin', 6, '09.10 - 09.40') }}</td>
                  <!-- Selasa (Urutan 6) -->
                  <td @click="openQuickSet('selasa', 6)" :class="cellStyleClass('selasa', 6)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 6)">
                    <CellContent :hari="'selasa'" :urutan="6" fallback="Shalat Dhuha + Istirahat" />
                  </td>
                  <!-- Rabu (Urutan 6) -->
                  <td @click="openQuickSet('rabu', 6)" :class="cellStyleClass('rabu', 6)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 6)">
                    <CellContent :hari="'rabu'" :urutan="6" fallback="Shalat Dhuha + Istirahat" />
                  </td>
                  <!-- Kamis (Urutan 6) -->
                  <td @click="openQuickSet('kamis', 6)" :class="cellStyleClass('kamis', 6)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 6)">
                    <CellContent :hari="'kamis'" :urutan="6" fallback="Shalat Dhuha + Istirahat" />
                  </td>
                  <!-- Jum'at (Urutan 6) -->
                  <td @click="openQuickSet('jumat', 6)" :class="cellStyleClass('jumat', 6)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 6)">
                    <CellContent :hari="'jumat'" :urutan="6" fallback="Shalat Dhuha + Istirahat" />
                  </td>
                  <!-- Jam Sabtu -->
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[11px] whitespace-nowrap">{{ formatSlotTime('sabtu', 6, '09.30 - 10.00') }}</td>
                  <!-- Sabtu (Urutan 6) -->
                  <td @click="openQuickSet('sabtu', 6)" :class="cellStyleClass('sabtu', 6)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('sabtu', 6)">
                    <CellContent :hari="'sabtu'" :urutan="6" fallback="Ekstrakulikuler Wajib" />
                  </td>
                </tr>

                <!-- ROW 7: 3X35 (09.40 - 11.20) -->
                <tr>
                  <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/40 text-gray-800 py-2">3X35</td>
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-2 text-[11px] whitespace-nowrap">{{ formatSlotTime('senin', 7, '09.40 - 11.20') }}</td>
                  <!-- Senin (Urutan 7) -->
                  <td @click="openQuickSet('senin', 7)" :class="cellStyleClass('senin', 7)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 7)">
                    <CellContent :hari="'senin'" :urutan="7" fallback="TEMA 3" />
                  </td>
                  <!-- Jam Sel-Jum -->
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-2 text-[11px] whitespace-nowrap">{{ formatSlotTime('senin', 7, '09.40 - 11.20') }}</td>
                  <!-- Selasa (Urutan 7) -->
                  <td @click="openQuickSet('selasa', 7)" :class="cellStyleClass('selasa', 7)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 7)">
                    <CellContent :hari="'selasa'" :urutan="7" fallback="TEMA 1" />
                  </td>
                  <!-- Rabu (Urutan 7) -->
                  <td @click="openQuickSet('rabu', 7)" :class="cellStyleClass('rabu', 7)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 7)">
                    <CellContent :hari="'rabu'" :urutan="7" fallback="TEMA 4" />
                  </td>
                  <!-- Kamis (Urutan 7) -->
                  <td @click="openQuickSet('kamis', 7)" :class="cellStyleClass('kamis', 7)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 7)">
                    <CellContent :hari="'kamis'" :urutan="7" fallback="TEMA 5" />
                  </td>
                  <!-- Jum'at (Urutan 7) -->
                  <td @click="openQuickSet('jumat', 7)" :class="cellStyleClass('jumat', 7)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 7)">
                    <CellContent :hari="'jumat'" :urutan="7" fallback="TEMA 2" />
                  </td>
                  <!-- Catatan Box (Spans Row 7 to 10 and 2 columns: Jam Sabtu + Sabtu) -->
                  <td rowspan="4" colspan="2" class="border border-amber-800 bg-[#cbd5e1] p-3 text-left align-top select-none font-sans">
                    <div class="text-xs font-bold text-gray-900 mb-2">Catatan:</div>
                    <div class="text-[11px] text-gray-800 space-y-1.5 font-medium leading-relaxed">
                      <div><strong class="font-bold text-gray-900">Kamis:</strong> Javanese language</div>
                      <div><strong class="font-bold text-gray-900">Jum'at:</strong> Arabic language</div>
                    </div>
                  </td>
                </tr>

                <!-- ROW 8: Shalat Dzuhur + Makan siang -->
                <tr>
                  <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/20 text-gray-400 py-1.5">-</td>
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[11px] whitespace-nowrap">{{ formatSlotTime('senin', 8, '11.20 - 12.10') }}</td>
                  <!-- Senin (Urutan 8) -->
                  <td @click="openQuickSet('senin', 8)" :class="cellStyleClass('senin', 8)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 8)">
                    <CellContent :hari="'senin'" :urutan="8" fallback="Shalat Dzuhur + Makan siang" />
                  </td>
                  <!-- Jam Sel-Jum -->
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[11px] whitespace-nowrap">{{ formatSlotTime('senin', 8, '11.20 - 12.10') }}</td>
                  <!-- Selasa (Urutan 8) -->
                  <td @click="openQuickSet('selasa', 8)" :class="cellStyleClass('selasa', 8)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 8)">
                    <CellContent :hari="'selasa'" :urutan="8" fallback="Shalat Dzuhur + Makan siang" />
                  </td>
                  <!-- Rabu (Urutan 8) -->
                  <td @click="openQuickSet('rabu', 8)" :class="cellStyleClass('rabu', 8)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 8)">
                    <CellContent :hari="'rabu'" :urutan="8" fallback="Shalat Dzuhur + Makan siang" />
                  </td>
                  <!-- Kamis (Urutan 8) -->
                  <td @click="openQuickSet('kamis', 8)" :class="cellStyleClass('kamis', 8)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 8)">
                    <CellContent :hari="'kamis'" :urutan="8" fallback="Shalat Dzuhur + Makan siang" />
                  </td>
                  <!-- Jum'at (Urutan 8) -->
                  <td @click="openQuickSet('jumat', 8)" :class="cellStyleClass('jumat', 8)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 8)">
                    <CellContent :hari="'jumat'" :urutan="8" fallback="Shalat Dzuhur + Makan siang" />
                  </td>
                  <!-- Sabtu covered by Catatan Box -->
                </tr>

                <!-- ROW 9: 12.10 - 12.45 -->
                <tr>
                  <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/40 text-gray-800 py-2">1 X 35</td>
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-2 text-[11px] whitespace-nowrap">{{ formatSlotTime('senin', 9, '12.10 - 12.45') }}</td>
                  <!-- Senin (Urutan 9) -->
                  <td @click="openQuickSet('senin', 9)" :class="cellStyleClass('senin', 9)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 9)">
                    <CellContent :hari="'senin'" :urutan="9" fallback="TEMA 3" />
                  </td>
                  <!-- Jam Sel-Jum -->
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-2 text-[11px] whitespace-nowrap">{{ formatSlotTime('senin', 9, '12.10 - 12.45') }}</td>
                  <!-- Selasa (Urutan 9) -->
                  <td @click="openQuickSet('selasa', 9)" :class="cellStyleClass('selasa', 9)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 9)">
                    <CellContent :hari="'selasa'" :urutan="9" fallback="TEMA 1" />
                  </td>
                  <!-- Rabu (Urutan 9) -->
                  <td @click="openQuickSet('rabu', 9)" :class="cellStyleClass('rabu', 9)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 9)">
                    <CellContent :hari="'rabu'" :urutan="9" fallback="TEMA 1" />
                  </td>
                  <!-- Kamis (Urutan 9) -->
                  <td @click="openQuickSet('kamis', 9)" :class="cellStyleClass('kamis', 9)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 9)">
                    <CellContent :hari="'kamis'" :urutan="9" fallback="TEMA 5" />
                  </td>
                  <!-- Jum'at (Urutan 9) -->
                  <td @click="openQuickSet('jumat', 9)" :class="cellStyleClass('jumat', 9)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 9)">
                    <CellContent :hari="'jumat'" :urutan="9" fallback="TEMA 2" />
                  </td>
                  <!-- Sabtu covered by Catatan Box -->
                </tr>

                <!-- ROW 10: 12.45 - 13.20 -->
                <tr>
                  <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/40 text-gray-800 py-2">1 X 35</td>
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-2 text-[11px] whitespace-nowrap">{{ formatSlotTime('senin', 10, '12.45 - 13.20') }}</td>
                  <!-- Senin (Urutan 10) -->
                  <td @click="openQuickSet('senin', 10)" :class="cellStyleClass('senin', 10)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 10)">
                    <CellContent :hari="'senin'" :urutan="10" fallback="TEMA 3" />
                  </td>
                  <!-- Jam Sel-Jum -->
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-2 text-[11px] whitespace-nowrap">{{ formatSlotTime('senin', 10, '12.45 - 13.20') }}</td>
                  <!-- Selasa (Urutan 10) -->
                  <td @click="openQuickSet('selasa', 10)" :class="cellStyleClass('selasa', 10)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 10)">
                    <CellContent :hari="'selasa'" :urutan="10" fallback="TEMA 1" />
                  </td>
                  <!-- Rabu (Urutan 10) -->
                  <td @click="openQuickSet('rabu', 10)" :class="cellStyleClass('rabu', 10)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 10)">
                    <CellContent :hari="'rabu'" :urutan="10" fallback="TEMA 2" />
                  </td>
                  <!-- Kamis (Urutan 10) -->
                  <td @click="openQuickSet('kamis', 10)" :class="cellStyleClass('kamis', 10)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 10)">
                    <CellContent :hari="'kamis'" :urutan="10" fallback="TEMA 5" />
                  </td>
                  <!-- Jum'at (Urutan 10) -->
                  <td @click="openQuickSet('jumat', 10)" :class="cellStyleClass('jumat', 10)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 10)">
                    <CellContent :hari="'jumat'" :urutan="10" fallback="TEMA 2" />
                  </td>
                  <!-- Sabtu covered by Catatan Box -->
                </tr>

                <!-- ROW 11: 13.20 - 13.30 (Lentera Refleksi) -->
                <tr>
                  <td class="border border-amber-800 text-center font-bold bg-[#fef3c7]/20 text-gray-400 py-1.5">-</td>
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[11px] whitespace-nowrap">{{ formatSlotTime('senin', 11, '13.20 - 13.30') }}</td>
                  <!-- Senin (Urutan 11) -->
                  <td @click="openQuickSet('senin', 11)" :class="cellStyleClass('senin', 11)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('senin', 11)">
                    <CellContent :hari="'senin'" :urutan="11" fallback="LENTERA" />
                  </td>
                  <!-- Jam Sel-Jum -->
                  <td class="border border-amber-800 text-center font-semibold bg-[#fde047] text-gray-900 py-1.5 text-[11px] whitespace-nowrap">{{ formatSlotTime('senin', 11, '13.20 - 13.30') }}</td>
                  <!-- Selasa (Urutan 11) -->
                  <td @click="openQuickSet('selasa', 11)" :class="cellStyleClass('selasa', 11)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('selasa', 11)">
                    <CellContent :hari="'selasa'" :urutan="11" fallback="LENTERA" />
                  </td>
                  <!-- Rabu (Urutan 11) -->
                  <td @click="openQuickSet('rabu', 11)" :class="cellStyleClass('rabu', 11)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('rabu', 11)">
                    <CellContent :hari="'rabu'" :urutan="11" fallback="LENTERA" />
                  </td>
                  <!-- Kamis (Urutan 11) -->
                  <td @click="openQuickSet('kamis', 11)" :class="cellStyleClass('kamis', 11)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('kamis', 11)">
                    <CellContent :hari="'kamis'" :urutan="11" fallback="LENTERA" />
                  </td>
                  <!-- Jum'at (Urutan 11) -->
                  <td @click="openQuickSet('jumat', 11)" :class="cellStyleClass('jumat', 11)" class="border border-amber-800 p-1.5 text-center cursor-pointer transition select-none hover:ring-2 hover:ring-purple-500/40" :title="cellTooltip('jumat', 11)">
                    <CellContent :hari="'jumat'" :urutan="11" fallback="LENTERA" />
                  </td>
                  <!-- Jam Sabtu Empty -->
                  <td class="border border-amber-800 bg-[#fde047]"></td>
                  <!-- Sabtu Empty -->
                  <td class="border border-amber-800 bg-white"></td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Color Legend & Guidance -->
          <div class="mt-4 pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500 no-print">
            <div class="flex flex-wrap items-center gap-2">
              <span class="font-bold text-gray-700">Keterangan Warna:</span>
              <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-[#86efac] text-emerald-950 font-bold border border-emerald-300">
                <span>Tema 3</span>
              </span>
              <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-[#fca5a5] text-red-950 font-bold border border-red-300">
                <span>Tema 1</span>
              </span>
              <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-[#fed7aa] text-amber-950 font-bold border border-amber-300">
                <span>Tema 4</span>
              </span>
              <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-[#e0e7ff] text-indigo-950 font-bold border border-indigo-200">
                <span>Tema 5</span>
              </span>
              <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-[#7dd3fc] text-sky-950 font-bold border border-sky-300">
                <span>Tema 2</span>
              </span>
              <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-white text-gray-800 font-bold border border-gray-300">
                <span>Pembiasaan & Ibadah</span>
              </span>
            </div>
            <div class="text-[11px] text-gray-400">
              Total Sesi Terdaftar: <strong class="text-gray-800 font-mono">{{ schedules.length }} slot</strong>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- VIEW B: DAFTAR TABEL LINEAR                                    -->
        <!-- ============================================================== -->
        <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900">
              Daftar Sesi Jadwal Kelas {{ selectedClass?.nama }}
            </h3>
            <button
              @click="openCreateScheduleForm"
              class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition cursor-pointer"
            >
              + Tambah Jadwal Manual
            </button>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-600 min-w-[700px]">
              <thead class="bg-gray-50 text-[11px] font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                <tr>
                  <th class="py-3 px-4">Hari</th>
                  <th class="py-3 px-4">Urutan / Waktu</th>
                  <th class="py-3 px-4">Kegiatan / Pelajaran</th>
                  <th class="py-3 px-4">Tipe</th>
                  <th class="py-3 px-4">Guru Pengampu</th>
                  <th class="py-3 px-4">Ruangan</th>
                  <th class="py-3 px-4">Catatan</th>
                  <th class="py-3 px-4 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="sch in sortedSchedules" :key="sch.id" class="hover:bg-gray-50/70 transition">
                  <td class="py-2.5 px-4 font-bold capitalize text-purple-700">{{ sch.hari }}</td>
                  <td class="py-2.5 px-4 font-mono whitespace-nowrap">
                    <span class="font-bold text-gray-900">Slot {{ sch.time_slot?.urutan || '-' }}</span>
                    <span class="text-gray-400 text-[11px] block">{{ sch.time_slot?.jam_mulai?.slice(0, 5) ?? '-' }} - {{ sch.time_slot?.jam_selesai?.slice(0, 5) ?? '-' }}</span>
                  </td>
                  <td class="py-2.5 px-4">
                    <div class="font-bold text-gray-900">
                      {{ sch.judul_kegiatan || sch.subject?.nama || '-' }}
                    </div>
                    <span v-if="sch.subject?.is_tematik" class="px-1.5 py-0.5 bg-purple-100 text-purple-800 rounded text-[9px] font-bold border border-purple-200">
                      Tema {{ sch.subject?.tema_nomor }}
                    </span>
                  </td>
                  <td class="py-2.5 px-4">
                    <span
                      :class="{
                        'bg-emerald-50 text-emerald-700': sch.tipe_kegiatan === 'kbm',
                        'bg-blue-50 text-blue-700': sch.tipe_kegiatan === 'pembiasaan',
                        'bg-amber-50 text-amber-700': sch.tipe_kegiatan === 'istirahat',
                        'bg-purple-50 text-purple-700': sch.tipe_kegiatan === 'ekskul',
                      }"
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                    >
                      {{ sch.tipe_kegiatan || 'kbm' }}
                    </span>
                  </td>
                  <td class="py-2.5 px-4">{{ sch.teacher?.nama_lengkap ?? '-' }}</td>
                  <td class="py-2.5 px-4 font-mono">{{ sch.room?.nama ?? sch.school_class?.room?.nama ?? '-' }}</td>
                  <td class="py-2.5 px-4 text-gray-500 italic">{{ sch.catatan || '-' }}</td>
                  <td class="py-2.5 px-4 text-right space-x-1 whitespace-nowrap">
                    <button
                      @click="openEditScheduleForm(sch)"
                      class="px-2 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg font-semibold transition border border-amber-200 text-[10px] cursor-pointer"
                    >
                      Edit
                    </button>
                    <button
                      @click="handleDeleteSchedule(sch)"
                      class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg font-semibold transition border border-rose-200 text-[10px] cursor-pointer"
                    >
                      Hapus
                    </button>
                  </td>
                </tr>
                <tr v-if="schedules.length === 0">
                  <td colspan="8" class="py-12 text-center text-gray-400">
                    Belum ada data jadwal untuk kelas ini. Klik "Terapkan Template" untuk mengisi otomatis jadwal resmi.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Tab 4: Jam Pelajaran (Time Slots) -->
      <div v-else-if="activeTab === 'slots'" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-100">
          <h2 class="text-sm font-bold text-gray-900">Alokasi Waktu Sesi Pembelajaran (Time Slots)</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-gray-600 min-w-[550px]">
            <thead class="bg-gray-50 text-[11px] font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
              <tr>
                <th class="py-3 px-4">Urutan</th>
                <th class="py-3 px-4">Hari</th>
                <th class="py-3 px-4">Nama Sesi</th>
                <th class="py-3 px-4">Jenis</th>
                <th class="py-3 px-4">Jam Mulai</th>
                <th class="py-3 px-4">Jam Selesai</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="ts in timeSlots" :key="ts.id" class="hover:bg-gray-50/70 transition">
                <td class="py-2.5 px-4 font-mono font-bold">{{ ts.urutan }}</td>
                <td class="py-2.5 px-4 capitalize font-semibold">{{ ts.hari }}</td>
                <td class="py-2.5 px-4 font-bold text-gray-900">{{ ts.nama }}</td>
                <td class="py-2.5 px-4">
                  <span :class="ts.jenis === 'belajar' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase">
                    {{ ts.jenis }}
                  </span>
                </td>
                <td class="py-2.5 px-4 font-mono">{{ ts.jam_mulai }}</td>
                <td class="py-2.5 px-4 font-mono">{{ ts.jam_selesai }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab 5: Ruangan Kelas & Fasilitas -->
      <div v-else-if="activeTab === 'rooms'" class="space-y-4">
        <!-- Filter & Search Bar -->
        <div class="bg-white p-3.5 rounded-2xl border border-gray-100 shadow-2xs flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="flex flex-wrap items-center gap-2 flex-1">
            <div class="relative min-w-[200px] flex-1 sm:flex-initial">
              <input
                v-model="searchRoom"
                type="text"
                placeholder="Cari nama, kode, atau gedung..."
                class="w-full border border-gray-200 rounded-xl pl-8 pr-3 py-1.5 text-xs bg-white text-gray-700 focus:ring-2 focus:ring-purple-500 outline-none"
              />
              <span class="absolute left-2.5 top-2 text-gray-400">🔍</span>
            </div>

            <select
              v-model="filterRoomJenis"
              class="border border-gray-200 rounded-xl px-3 py-1.5 text-xs bg-white text-gray-700 focus:ring-2 focus:ring-purple-500 outline-none font-medium"
            >
              <option value="">-- Semua Jenis Ruangan --</option>
              <option value="ruang_kelas">Ruang Kelas</option>
              <option value="lab">Laboratorium</option>
              <option value="perpustakaan">Perpustakaan</option>
              <option value="aula">Aula</option>
              <option value="mushola">Mushola</option>
              <option value="lainnya">Lainnya</option>
            </select>

            <select
              v-model="filterRoomKondisi"
              class="border border-gray-200 rounded-xl px-3 py-1.5 text-xs bg-white text-gray-700 focus:ring-2 focus:ring-purple-500 outline-none font-medium"
            >
              <option value="">-- Semua Kondisi --</option>
              <option value="baik">Kondisi Baik</option>
              <option value="rusak_ringan">Rusak Ringan</option>
              <option value="rusak_berat">Rusak Berat</option>
            </select>
          </div>

          <span class="text-gray-400 font-mono text-[11px] shrink-0">
            Menampilkan {{ filteredRooms.length }} dari {{ rooms.length }} Ruangan
          </span>
        </div>

        <!-- Room Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="r in filteredRooms"
            :key="r.id"
            class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm space-y-3 hover:shadow-md transition flex flex-col justify-between"
          >
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                  <span class="font-mono font-bold text-xs bg-gray-100 px-2 py-0.5 rounded text-gray-700 border border-gray-200">
                    {{ r.kode }}
                  </span>
                  <span
                    class="text-[10px] font-bold px-2 py-0.5 rounded-full capitalize border"
                    :class="{
                      'bg-purple-50 text-purple-700 border-purple-200': r.jenis === 'ruang_kelas',
                      'bg-blue-50 text-blue-700 border-blue-200': r.jenis === 'lab',
                      'bg-amber-50 text-amber-700 border-amber-200': r.jenis === 'perpustakaan',
                      'bg-indigo-50 text-indigo-700 border-indigo-200': r.jenis === 'aula',
                      'bg-emerald-50 text-emerald-700 border-emerald-200': r.jenis === 'mushola',
                      'bg-slate-50 text-slate-700 border-slate-200': !r.jenis || r.jenis === 'lainnya',
                    }"
                  >
                    {{ r.jenis ? r.jenis.replace('_', ' ') : 'Ruang' }}
                  </span>
                </div>

                <span
                  class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase"
                  :class="{
                    'bg-emerald-50 text-emerald-700': (r.kondisi ?? 'baik') === 'baik',
                    'bg-amber-50 text-amber-700': r.kondisi === 'rusak_ringan',
                    'bg-rose-50 text-rose-700': r.kondisi === 'rusak_berat',
                  }"
                >
                  {{ r.kondisi ? r.kondisi.replace('_', ' ') : 'Baik' }}
                </span>
              </div>

              <div>
                <h3 class="text-sm font-bold text-gray-900 leading-snug">{{ r.nama }}</h3>
                <p class="text-xs text-gray-500 mt-1">
                  {{ r.gedung_lantai || 'Gedung Sekolah' }} • Kapasitas: <strong class="text-gray-700">{{ r.kapasitas ?? 30 }} Orang</strong>
                </p>
              </div>
            </div>

            <!-- Card Actions -->
            <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
              <button
                @click="openEditRoomModal(r)"
                type="button"
                class="px-3 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg text-xs font-semibold transition border border-amber-200 cursor-pointer"
              >
                Edit
              </button>
              <button
                @click="handleDeleteRoom(r)"
                type="button"
                class="px-3 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-semibold transition border border-rose-200 cursor-pointer"
              >
                Hapus
              </button>
            </div>
          </div>

          <div v-if="filteredRooms.length === 0" class="col-span-full py-12 text-center text-gray-400 bg-white rounded-2xl border border-gray-100">
            Belum ada data ruangan yang cocok. Klik "+ Tambah Ruang Kelas" untuk mendaftarkan ruangan baru.
          </div>
        </div>
      </div>

      <!-- Tab 6: Tahun Ajaran & Kenaikan Kelas -->
      <div v-else-if="activeTab === 'academic_years'" class="space-y-6">
        <!-- Panel 1: Master Tahun Ajaran & Semester Aktif -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-xs space-y-5">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
              <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                <span>🗓️</span>
                <span>Tahun Ajaran & Semester Aktif</span>
              </h2>
              <p class="text-xs text-slate-500 mt-0.5">
                Pengaturan manual pergantian semester kalender sekolah dan aktivasi tahun ajaran baru.
              </p>
            </div>
            <button
              @click="openCreateYearModal"
              type="button"
              class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-1.5 self-start sm:self-auto cursor-pointer"
            >
              <span>+ Buat Tahun Ajaran</span>
            </button>
          </div>

          <!-- Cards List of Academic Years -->
          <div v-if="loadingAcademic" class="py-8 text-center text-slate-400">
            <div class="inline-block animate-spin w-6 h-6 border-2 border-emerald-600 border-t-transparent rounded-full mb-2"></div>
            <div class="text-xs">Memuat data tahun ajaran...</div>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="ay in academicYearsList"
              :key="ay.id"
              class="p-5 rounded-3xl border transition-all flex flex-col justify-between"
              :class="ay.status === 'aktif' ? 'bg-emerald-50/40 border-emerald-300 ring-2 ring-emerald-500/20 shadow-xs' : 'bg-white border-slate-200 hover:border-slate-300'"
            >
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-lg font-black text-slate-900 font-mono tracking-tight">
                    {{ ay.nama }}
                  </span>
                  <span
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase border"
                    :class="{
                      'bg-emerald-100 text-emerald-800 border-emerald-200': ay.status === 'aktif',
                      'bg-blue-100 text-blue-800 border-blue-200': ay.status === 'rencana',
                      'bg-slate-100 text-slate-600 border-slate-200': ay.status === 'selesai',
                    }"
                  >
                    {{ ay.status === 'aktif' ? '● Aktif' : ay.status }}
                  </span>
                </div>

                <div class="text-xs text-slate-500">
                  Periode: {{ ay.tgl_mulai }} s.d. {{ ay.tgl_selesai }}
                </div>

                <!-- Semesters in this year -->
                <div class="pt-2 border-t border-slate-200/60 space-y-2">
                  <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status Semester:</div>
                  <div
                    v-for="sem in ay.semesters"
                    :key="sem.id"
                    class="flex items-center justify-between p-2 rounded-xl text-xs bg-slate-50 border border-slate-100"
                  >
                    <div>
                      <span class="font-bold uppercase text-slate-800">Semester {{ sem.semester }}</span>
                      <span
                        class="ml-2 px-1.5 py-0.2 rounded text-[10px] font-bold capitalize"
                        :class="sem.status === 'aktif' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'"
                      >
                        {{ sem.status }}
                      </span>
                    </div>

                    <button
                      v-if="sem.status !== 'aktif'"
                      @click="handleActivateSemester(sem)"
                      class="px-2 py-0.5 bg-white hover:bg-slate-200 text-slate-700 rounded-lg text-[10px] font-bold border border-slate-200 transition cursor-pointer"
                    >
                      Aktifkan
                    </button>
                    <span v-else class="text-[10px] font-black text-emerald-700">Aktif Sekarang</span>
                  </div>
                </div>
              </div>

              <div class="mt-4 pt-3 border-t border-slate-200/60 flex items-center justify-between">
                <span class="text-xs text-slate-500">
                  {{ ay.classes_count ?? 0 }} Rombel
                </span>
                <button
                  v-if="ay.status !== 'aktif'"
                  @click="handleSetActiveAcademicYear(ay)"
                  class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer"
                >
                  Set Jadi Tahun Aktif
                </button>
                <span v-else class="text-xs font-black text-emerald-700">✓ Sedang Berjalan</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Panel 2: Wizard Kenaikan Kelas & Kelulusan Massal -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-xs space-y-6">
          <div class="border-b border-slate-100 pb-4">
            <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
              <span>🎓</span>
              <span>Wizard Kenaikan Kelas & Kelulusan Massal</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
              Proses perpindahan siswa ke rombel tahun ajaran baru secara massal, penetapan tinggal kelas, atau kelulusan alumni.
            </p>
          </div>

          <!-- Wizard Selectors -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-5 bg-slate-50 rounded-2xl border border-slate-200/80">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">
                1. Rombel Kelas Asal (Sumber) *
              </label>
              <select
                v-model="promoteSourceClassId"
                @change="fetchStudentsForPromotion"
                class="w-full bg-white border border-slate-200 rounded-xl p-2.5 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500 outline-none"
              >
                <option :value="null">-- Pilih Kelas Asal --</option>
                <option v-for="c in classes" :key="c.id" :value="c.id">
                  {{ c.nama }} (Tingkat {{ c.tingkat }})
                </option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">
                2. Tahun Ajaran Tujuan *
              </label>
              <select
                v-model="promoteTargetYearId"
                class="w-full bg-white border border-slate-200 rounded-xl p-2.5 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500 outline-none"
              >
                <option :value="null">-- Pilih Tahun Ajaran Baru --</option>
                <option v-for="ay in academicYearsList" :key="ay.id" :value="ay.id">
                  {{ ay.nama }} ({{ ay.status }})
                </option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">
                3. Rombel Kelas Tujuan *
              </label>
              <select
                v-model="promoteTargetClassId"
                class="w-full bg-white border border-slate-200 rounded-xl p-2.5 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500 outline-none"
              >
                <option :value="null">-- Pilih Kelas Tujuan (Kosongkan jika Lulus Semua) --</option>
                <option v-for="c in classes" :key="c.id" :value="c.id">
                  {{ c.nama }} (Tingkat {{ c.tingkat }})
                </option>
              </select>
            </div>
          </div>

          <!-- Quick Action Buttons -->
          <div v-if="studentsToPromote.length > 0" class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
              <span class="text-xs font-bold text-slate-600">Aksi Cepat:</span>
              <button
                @click="setAllPromotionStatus('naik')"
                type="button"
                class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold transition cursor-pointer"
              >
                Set Semua Naik Kelas
              </button>
              <button
                @click="setAllPromotionStatus('lulus')"
                type="button"
                class="px-3 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-800 border border-purple-200 rounded-xl text-xs font-bold transition cursor-pointer"
              >
                Set Semua Lulus (Alumni)
              </button>
            </div>

            <div class="text-xs font-bold text-slate-700">
              Total Siswa: <strong class="text-emerald-700">{{ studentsToPromote.length }} Siswa</strong>
            </div>
          </div>

          <!-- Table of Students for Promotion -->
          <div v-if="loadingStudentsToPromote" class="py-12 text-center text-slate-400">
            <div class="inline-block animate-spin w-6 h-6 border-2 border-emerald-600 border-t-transparent rounded-full mb-2"></div>
            <div class="text-xs">Memuat daftar siswa kelas terpilih...</div>
          </div>

          <div v-else-if="studentsToPromote.length === 0" class="py-12 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-xs text-slate-400">
            Pilih kelas asal di atas untuk menampilkan daftar siswa yang akan diproses kenaikan kelasnya.
          </div>

          <div v-else class="border border-slate-200 rounded-2xl overflow-hidden">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-100/80 text-slate-700 font-extrabold uppercase text-[10px] border-b border-slate-200">
                <tr>
                  <th class="py-3 px-4 w-12 text-center">No</th>
                  <th class="py-3 px-4 w-28">NIS / NISN</th>
                  <th class="py-3 px-4">Nama Lengkap Siswa</th>
                  <th class="py-3 px-4 w-44">Status Kenaikan</th>
                  <th class="py-3 px-4 w-28 text-center">No. Absen Baru</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr v-for="(s, idx) in studentsToPromote" :key="s.student_id" class="hover:bg-slate-50/70">
                  <td class="py-2.5 px-4 text-center text-slate-400 font-mono">{{ idx + 1 }}</td>
                  <td class="py-2.5 px-4 font-mono text-[11px] text-slate-600">
                    <div>{{ s.nis || '-' }}</div>
                  </td>
                  <td class="py-2.5 px-4 font-bold text-slate-900">
                    {{ s.nama_lengkap }}
                  </td>
                  <td class="py-2.5 px-4">
                    <select
                      v-model="s.status_kelas"
                      class="w-full border rounded-xl p-1.5 text-xs font-bold outline-none"
                      :class="{
                        'bg-emerald-50 text-emerald-800 border-emerald-200': s.status_kelas === 'naik',
                        'bg-purple-50 text-purple-800 border-purple-200': s.status_kelas === 'lulus',
                        'bg-amber-50 text-amber-800 border-amber-200': s.status_kelas === 'tinggal_kelas',
                        'bg-rose-50 text-rose-800 border-rose-200': s.status_kelas === 'pindah',
                      }"
                    >
                      <option value="naik">Naik Kelas</option>
                      <option value="tinggal_kelas">Tinggal Kelas</option>
                      <option value="lulus">Lulus / Alumni</option>
                      <option value="pindah">Pindah / Mutasi</option>
                    </select>
                  </td>
                  <td class="py-2.5 px-4 text-center">
                    <input
                      v-model.number="s.nomor_absen"
                      type="number"
                      placeholder="Absen"
                      class="w-20 text-center border border-slate-200 rounded-lg p-1 text-xs font-mono font-bold"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Bottom Execute Button -->
          <div v-if="studentsToPromote.length > 0" class="pt-4 border-t border-slate-100 flex items-center justify-end">
            <button
              @click="handleExecutePromotion"
              :disabled="promoting"
              type="button"
              class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-md transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <span v-if="promoting">Memproses...</span>
              <span v-else>🚀 Eksekusi Kenaikan Kelas ({{ studentsToPromote.length }} Siswa)</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL BUAT TAHUN AJARAN BARU -->
    <div
      v-if="showCreateYearModal"
      class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5"
    >
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4 animate-in fade-in zoom-in-95">
        <div class="flex items-center justify-between border-b pb-3">
          <h3 class="text-sm font-bold text-slate-900">Buat Tahun Ajaran Baru</h3>
          <button @click="showCreateYearModal = false" class="text-slate-400 hover:text-slate-700 font-bold">✕</button>
        </div>

        <form @submit.prevent="submitCreateYearForm" class="space-y-4 text-xs">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Format Nama Tahun Ajaran (YYYY/YYYY) *</label>
            <input
              v-model="newYearForm.nama"
              type="text"
              required
              placeholder="Contoh: 2025/2026"
              class="w-full border border-slate-200 rounded-xl p-2.5 text-xs font-mono font-bold focus:ring-2 focus:ring-emerald-500 outline-none"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Tanggal Mulai *</label>
              <input
                v-model="newYearForm.tgl_mulai"
                type="date"
                required
                class="w-full border border-slate-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
              />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Tanggal Selesai *</label>
              <input
                v-model="newYearForm.tgl_selesai"
                type="date"
                required
                class="w-full border border-slate-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
              />
            </div>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Status Awal</label>
            <select
              v-model="newYearForm.status"
              class="w-full border border-slate-200 rounded-xl p-2.5 text-xs bg-white font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500 outline-none"
            >
              <option value="rencana">Rencana (Belum Diaktifkan)</option>
              <option value="aktif">Langsung Aktifkan Sekarang</option>
            </select>
          </div>

          <div class="pt-3 border-t flex items-center justify-end gap-2">
            <button
              @click="showCreateYearModal = false"
              type="button"
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold shadow-xs cursor-pointer"
            >
              Simpan Tahun Ajaran
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL TAMBAH / EDIT RUANG KELAS -->
    <div
      v-if="isRoomModalOpen"
      class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5"
    >
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4 animate-in fade-in zoom-in-95">
        <div class="flex items-center justify-between border-b pb-3">
          <div>
            <h3 class="text-sm font-bold text-slate-900">
              {{ isEditingRoom ? `Edit Ruangan: ${roomForm.nama || '...'}` : 'Tambah Ruang Kelas / Fasilitas Baru' }}
            </h3>
            <p class="text-[11px] text-slate-500">
              {{ isEditingRoom ? 'Perbarui informasi kode, jenis, kapasitas, atau kondisi ruangan.' : 'Daftarkan ruangan kelas atau fasilitas sekolah baru ke dalam sistem.' }}
            </p>
          </div>
          <button @click="isRoomModalOpen = false" class="text-slate-400 hover:text-slate-700 font-bold p-1 cursor-pointer">✕</button>
        </div>

        <form @submit.prevent="submitRoomForm" class="space-y-4 text-xs">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Kode Ruangan *</label>
              <input
                v-model="roomForm.kode"
                type="text"
                required
                maxlength="15"
                placeholder="Contoh: R103 / LAB02"
                class="w-full border border-slate-200 rounded-xl p-2.5 text-xs font-mono font-bold uppercase focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Jenis Ruangan *</label>
              <select
                v-model="roomForm.jenis"
                required
                class="w-full border border-slate-200 rounded-xl p-2.5 text-xs bg-white font-bold text-slate-800 focus:ring-2 focus:ring-purple-500 outline-none"
              >
                <option value="ruang_kelas">Ruang Kelas Pembelajaran</option>
                <option value="lab">Laboratorium (Komputer/IPA)</option>
                <option value="perpustakaan">Perpustakaan</option>
                <option value="aula">Aula / Gedung Serbaguna</option>
                <option value="mushola">Mushola / Ibadah</option>
                <option value="lainnya">Fasilitas Lainnya</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Nama Ruangan / Fasilitas *</label>
            <input
              v-model="roomForm.nama"
              type="text"
              required
              maxlength="100"
              placeholder="Contoh: Ruang 3 Ali bin Abi Thalib"
              class="w-full border border-slate-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-purple-500 outline-none"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Gedung & Lantai</label>
              <input
                v-model="roomForm.gedung_lantai"
                type="text"
                maxlength="50"
                placeholder="Contoh: Gedung A Lantai 1"
                class="w-full border border-slate-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Kapasitas (Orang / Siswa)</label>
              <input
                v-model.number="roomForm.kapasitas"
                type="number"
                min="1"
                placeholder="Contoh: 28"
                class="w-full border border-slate-200 rounded-xl p-2.5 text-xs font-mono font-bold focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Kondisi Ruangan</label>
            <select
              v-model="roomForm.kondisi"
              class="w-full border border-slate-200 rounded-xl p-2.5 text-xs bg-white font-bold text-slate-800 focus:ring-2 focus:ring-purple-500 outline-none"
            >
              <option value="baik">Baik (Siap Digunakan)</option>
              <option value="rusak_ringan">Rusak Ringan (Perlu Perawatan)</option>
              <option value="rusak_berat">Rusak Berat (Tidak Layak)</option>
            </select>
          </div>

          <div class="pt-3 border-t flex items-center justify-end gap-2">
            <button
              @click="isRoomModalOpen = false"
              type="button"
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="savingRoom"
              class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-bold shadow-xs cursor-pointer disabled:opacity-50"
            >
              {{ savingRoom ? 'Menyimpan...' : (isEditingRoom ? 'Simpan Perubahan' : 'Buat Ruangan') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- VIEW 2: HALAMAN FORMULIR ROMBEL KELAS (DEDICATED FULL-PAGE)    -->
    <!-- ============================================================== -->
    <div v-else-if="viewMode === 'form-class'" class="space-y-6 animate-in fade-in duration-200">
      <!-- Top Navigation & Action Banner -->
      <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
          <button
            @click="closeForm"
            type="button"
            class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center justify-center shrink-0 shadow-xs group cursor-pointer"
            title="Kembali ke Daftar Rombel Kelas"
          >
            <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
              <span>Akademik</span>
              <span>/</span>
              <button type="button" @click="closeForm" class="hover:text-purple-600 hover:underline cursor-pointer">Rombel Kelas</button>
              <span>/</span>
              <span class="text-purple-700 font-bold">{{ isEditingClass ? 'Edit Rombel' : 'Tambah Rombel Baru' }}</span>
            </div>
            <h1 class="text-lg sm:text-2xl font-black text-gray-900 leading-tight">
              {{ isEditingClass ? `Edit Rombel: ${classForm.nama || '...'}` : 'Formulir Rombel Kelas Baru' }}
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              {{ isEditingClass ? 'Perbarui tingkat, nama rombel, wali kelas penanggung jawab, dan ruangan.' : 'Daftarkan rombel kelas baru dan tetapkan wali kelasnya.' }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2.5 self-end sm:self-center">
          <button
            @click="closeForm"
            type="button"
            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition cursor-pointer"
          >
            Batal
          </button>
          <button
            @click="submitClassForm"
            :disabled="savingClass"
            type="button"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md shadow-purple-600/25 transition disabled:opacity-50 active:scale-95 cursor-pointer"
          >
            <svg v-if="savingClass" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ savingClass ? 'Menyimpan...' : (isEditingClass ? 'Simpan Perubahan' : 'Buat Rombel Kelas') }}</span>
          </button>
        </div>
      </div>

      <!-- Main Form Grid: 12 Cols -->
      <form @submit.prevent="submitClassForm" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- LEFT COLUMN: Live Preview Card (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
          <div class="bg-gradient-to-br from-slate-900 via-purple-950 to-slate-900 p-5 rounded-2xl text-white shadow-lg space-y-4">
            <div class="flex items-center justify-between text-purple-400 text-xs font-bold">
              <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-purple-400 animate-ping"></span>
                KARTU ROMBEL (PREVIEW)
              </span>
              <span class="font-mono text-[10px] text-slate-400">SIM-BIM</span>
            </div>

            <div class="pt-2">
              <span class="px-2.5 py-1 bg-purple-500/30 text-purple-200 border border-purple-400/30 rounded-lg text-xs font-mono font-bold">
                {{ classForm.kode || 'KODE' }}
              </span>
              <h3 class="text-xl font-black mt-2 text-white">
                {{ classForm.nama || 'Nama Rombel Kelas' }}
              </h3>
              <p class="text-xs text-slate-300 mt-0.5">
                Tingkat {{ classForm.tingkat || 1 }} • Paralel {{ classForm.paralel || 'A' }}
              </p>
            </div>

            <div class="pt-3 border-t border-white/10 space-y-1.5 text-xs text-slate-300">
              <div class="flex justify-between">
                <span>Wali Kelas:</span>
                <span class="font-bold text-white">{{ getTeacherName(classForm.wali_kelas_id) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Ruang Belajar:</span>
                <span class="font-bold text-white">{{ getRoomName(classForm.room_id) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Kapasitas:</span>
                <span class="font-bold text-purple-300">{{ classForm.kapasitas || 28 }} Siswa</span>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: Form Inputs (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
          <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-gray-900 border-b pb-3">Identitas & Spesifikasi Rombel</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Kode Kelas *</label>
                <input
                  v-model="classForm.kode"
                  type="text"
                  required
                  placeholder="Contoh: 1A"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono uppercase font-bold focus:ring-2 focus:ring-purple-500 outline-none"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Rombel *</label>
                <input
                  v-model="classForm.nama"
                  type="text"
                  required
                  placeholder="Contoh: Kelas 1 A (Al-Fatih)"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-purple-500 outline-none"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Tingkat (1 - 6) *</label>
                <input
                  v-model.number="classForm.tingkat"
                  type="number"
                  min="1"
                  max="6"
                  required
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-purple-500 outline-none"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Paralel / Huruf</label>
                <input
                  v-model="classForm.paralel"
                  type="text"
                  placeholder="A / B"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs uppercase focus:ring-2 focus:ring-purple-500 outline-none"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Kapasitas Maksimal (Siswa)</label>
                <input
                  v-model.number="classForm.kapasitas"
                  type="number"
                  min="1"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-purple-500 outline-none"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Wali Kelas Penanggung Jawab</label>
                <select
                  v-model="classForm.wali_kelas_id"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white focus:ring-2 focus:ring-purple-500 outline-none"
                >
                  <option :value="null">-- Belum Ditugaskan --</option>
                  <option v-for="t in teachersList" :key="t.id" :value="t.id">
                    {{ t.nama_lengkap }}{{ t.jenis_ptk === 'kepala_sekolah' ? ' (Kepala Sekolah)' : '' }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Ruangan Kelas</label>
                <select
                  v-model="classForm.room_id"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white focus:ring-2 focus:ring-purple-500 outline-none"
                >
                  <option :value="null">-- Pilih Ruangan --</option>
                  <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.nama }} ({{ r.kode }})</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Bottom Action Bar -->
          <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between gap-4">
            <button
              @click="closeForm"
              type="button"
              class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition flex items-center gap-2 cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              <span>Kembali ke Rombel</span>
            </button>

            <div class="flex items-center gap-3">
              <button
                @click="closeForm"
                type="button"
                class="px-4 py-2.5 text-gray-500 hover:text-gray-700 text-xs font-semibold transition cursor-pointer"
              >
                Batal
              </button>
              <button
                type="submit"
                :disabled="savingClass"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md shadow-purple-600/25 transition disabled:opacity-50 active:scale-95 cursor-pointer"
              >
                <svg v-if="savingClass" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ savingClass ? 'Menyimpan...' : (isEditingClass ? 'Simpan Perubahan' : 'Buat Rombel Kelas') }}</span>
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- ============================================================== -->
    <!-- VIEW 3: HALAMAN FORMULIR MATA PELAJARAN & TEMATIK (FULL-PAGE)  -->
    <!-- ============================================================== -->
    <div v-else-if="viewMode === 'form-subject'" class="space-y-6 animate-in fade-in duration-200">
      <!-- Top Navigation & Action Banner -->
      <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
          <button
            @click="closeForm"
            type="button"
            class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center justify-center shrink-0 shadow-xs group cursor-pointer"
            title="Kembali ke Mata Pelajaran & Tematik"
          >
            <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
              <span>Kurikulum</span>
              <span>/</span>
              <button type="button" @click="closeForm" class="hover:text-purple-600 hover:underline cursor-pointer">Mata Pelajaran & Tematik</button>
              <span>/</span>
              <span class="text-purple-700 font-bold">{{ isEditingSubject ? 'Edit Tema/Mapel' : 'Tambah Baru' }}</span>
            </div>
            <h1 class="text-lg sm:text-2xl font-black text-gray-900 leading-tight">
              {{ isEditingSubject ? `Edit: ${subjectForm.nama || '...'}` : 'Formulir Pelajaran & Tematik Baru' }}
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              Kelola kurikulum Tematik Terpadu (Tema 1-5+) maupun mata pelajaran reguler mandiri.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2.5 self-end sm:self-center">
          <button
            @click="closeForm"
            type="button"
            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition cursor-pointer"
          >
            Batal
          </button>
          <button
            @click="submitSubjectForm"
            :disabled="savingSubject"
            type="button"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md shadow-purple-600/25 transition disabled:opacity-50 active:scale-95 cursor-pointer"
          >
            <svg v-if="savingSubject" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ savingSubject ? 'Menyimpan...' : (isEditingSubject ? 'Simpan Perubahan' : 'Buat Pelajaran / Tema') }}</span>
          </button>
        </div>
      </div>

      <!-- Main Form Grid: 12 Cols -->
      <form @submit.prevent="submitSubjectForm" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- LEFT COLUMN: Live Preview Card (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
          <div
            :class="subjectForm.is_tematik ? 'from-slate-900 via-purple-950 to-slate-900' : 'from-slate-900 via-emerald-950 to-slate-900'"
            class="bg-gradient-to-br p-5 rounded-2xl text-white shadow-lg space-y-4"
          >
            <div class="flex items-center justify-between text-xs font-bold" :class="subjectForm.is_tematik ? 'text-purple-300' : 'text-emerald-300'">
              <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full animate-ping" :class="subjectForm.is_tematik ? 'bg-purple-400' : 'bg-emerald-400'"></span>
                PREVIEW KARTU KURIKULUM
              </span>
              <span class="font-mono text-[10px] text-slate-400">{{ subjectForm.kode || 'KODE' }}</span>
            </div>

            <div class="pt-2">
              <span
                v-if="subjectForm.is_tematik"
                class="px-2.5 py-1 bg-purple-500/30 text-purple-200 border border-purple-400/30 rounded-lg text-xs font-bold"
              >
                ✨ TEMATIK (Tema {{ subjectForm.tema_nomor || 1 }})
              </span>
              <span
                v-else
                class="px-2.5 py-1 bg-emerald-500/30 text-emerald-200 border border-emerald-400/30 rounded-lg text-xs font-bold uppercase"
              >
                📘 {{ subjectForm.kelompok || 'REGULER' }}
              </span>

              <h3 class="text-xl font-black mt-2 text-white">
                {{ subjectForm.nama || 'Nama Pelajaran / Tema' }}
              </h3>
              <p class="text-xs text-slate-300 mt-0.5">
                Target: {{ subjectForm.tingkat_berlaku || 'Semua Tingkat' }}
              </p>
            </div>

            <!-- Muatan Chips di Preview -->
            <div v-if="subjectForm.is_tematik && subjectForm.muatan_pelajaran.length" class="space-y-1 pt-1">
              <span class="text-[10px] font-bold text-purple-300 uppercase">Muatan Campuran:</span>
              <div class="flex flex-wrap gap-1">
                <span
                  v-for="m in subjectForm.muatan_pelajaran"
                  :key="m"
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white"
                >
                  {{ m }}
                </span>
              </div>
            </div>

            <div class="pt-3 border-t border-white/10 flex justify-between text-xs text-slate-300">
              <span>Beban: <strong>{{ subjectForm.jam_per_minggu || 4 }} JP / Minggu</strong></span>
              <span>KKM: <strong>{{ subjectForm.kkm || 75 }}</strong></span>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: Form Inputs (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
          <!-- Model Switcher Card -->
          <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm space-y-3">
            <label class="block font-bold text-gray-800 text-xs">Pilih Model Kurikulum *</label>
            <div class="grid grid-cols-2 gap-3">
              <button
                type="button"
                @click="subjectForm.is_tematik = true; subjectForm.kelompok = 'tematik'; onTemaNomorChange()"
                :class="subjectForm.is_tematik ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/20' : 'bg-gray-50 text-gray-700 border border-gray-200 hover:bg-gray-100 font-semibold'"
                class="py-3 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition cursor-pointer"
              >
                <span>✨</span>
                <span>Tematik Terpadu (Tema 1 s.d 5+)</span>
              </button>
              <button
                type="button"
                @click="subjectForm.is_tematik = false; subjectForm.kelompok = 'umum'"
                :class="!subjectForm.is_tematik ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'bg-gray-50 text-gray-700 border border-gray-200 hover:bg-gray-100 font-semibold'"
                class="py-3 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition cursor-pointer"
              >
                <span>📘</span>
                <span>Reguler / Mandiri (PAI, PJOK, dll)</span>
              </button>
            </div>
          </div>

          <!-- Section: Jika Tematik -->
          <div v-if="subjectForm.is_tematik" class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-purple-900 border-b pb-3">Konfigurasi Tematik Terpadu</h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Nomor Tema *</label>
                <select
                  v-model.number="subjectForm.tema_nomor"
                  @change="onTemaNomorChange"
                  required
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white font-bold text-purple-700 focus:ring-2 focus:ring-purple-500 outline-none"
                >
                  <option v-for="n in 12" :key="n" :value="n">Tema {{ n }}</option>
                </select>
              </div>

              <div class="sm:col-span-2">
                <label class="block font-semibold text-gray-700 text-xs mb-1">Kode Tema *</label>
                <input
                  v-model="subjectForm.kode"
                  type="text"
                  required
                  placeholder="Contoh: TEMA-1"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs uppercase font-mono font-bold focus:ring-2 focus:ring-purple-500 outline-none"
                />
              </div>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Judul / Nama Tema Lengkap *</label>
              <input
                v-model="subjectForm.nama"
                type="text"
                required
                placeholder="Contoh: Tema 1: Indahnya Kebersamaan"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold text-gray-900 focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>

            <!-- Muatan Campuran Pelajaran Selector -->
            <div class="space-y-2 pt-2">
              <label class="block font-bold text-gray-800 text-xs">
                Muatan Campuran Pelajaran (Sub-Pelajaran) *
              </label>
              <p class="text-[11px] text-gray-500">
                Pilih muatan yang digabung di dalam tema ini (contoh: Tema 1 gabungan Matematika dan IPA):
              </p>

              <!-- Quick Badges -->
              <div class="flex flex-wrap gap-1.5 pt-1">
                <button
                  type="button"
                  v-for="opt in commonMuatanOptions"
                  :key="opt"
                  @click="toggleMuatan(opt)"
                  :class="subjectForm.muatan_pelajaran.includes(opt) ? 'bg-purple-600 text-white font-bold shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                  class="px-3 py-1.5 rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5"
                >
                  <span>{{ subjectForm.muatan_pelajaran.includes(opt) ? '✓' : '+' }}</span>
                  <span>{{ opt }}</span>
                </button>
              </div>

              <!-- Custom Muatan Input -->
              <div class="flex gap-2 pt-1">
                <input
                  v-model="customMuatanInput"
                  @keyup.enter.prevent="addCustomMuatan"
                  type="text"
                  placeholder="Ketik nama muatan lain lalu tekan Tambah..."
                  class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-purple-500 outline-none"
                />
                <button
                  type="button"
                  @click="addCustomMuatan"
                  class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold text-xs transition cursor-pointer"
                >
                  + Tambah Muatan
                </button>
              </div>

              <!-- Active Muatan Chips -->
              <div v-if="subjectForm.muatan_pelajaran.length" class="mt-2 flex flex-wrap gap-1.5 p-3 bg-purple-50/50 rounded-xl border border-purple-100">
                <span class="text-[11px] text-purple-700 font-bold self-center mr-1">Terpilih:</span>
                <span
                  v-for="(m, idx) in subjectForm.muatan_pelajaran"
                  :key="m"
                  class="px-2.5 py-1 bg-white text-purple-900 rounded-lg font-bold text-xs border border-purple-200 shadow-2xs flex items-center gap-1.5"
                >
                  <span>{{ m }}</span>
                  <button type="button" @click="removeMuatan(idx)" class="text-rose-500 hover:text-rose-700 font-black cursor-pointer">&times;</button>
                </span>
              </div>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Deskripsi / Ruang Lingkup Tema</label>
              <textarea
                v-model="subjectForm.deskripsi"
                rows="2"
                placeholder="Catatan kompetensi dasar materi tema..."
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-purple-500 outline-none"
              ></textarea>
            </div>
          </div>

          <!-- Section: Jika Reguler -->
          <div v-else class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-gray-900 border-b pb-3">Informasi Mata Pelajaran Reguler</h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="sm:col-span-2">
                <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Mata Pelajaran *</label>
                <input
                  v-model="subjectForm.nama"
                  type="text"
                  required
                  placeholder="Contoh: Pendidikan Agama Islam & Budi Pekerti"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold text-gray-900 focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Kode Mapel *</label>
                <input
                  v-model="subjectForm.kode"
                  type="text"
                  required
                  placeholder="Contoh: PAI"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs uppercase font-mono font-bold focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Kelompok Mata Pelajaran *</label>
              <select v-model="subjectForm.kelompok" required class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white focus:ring-2 focus:ring-emerald-500 outline-none">
                <option value="umum">Umum</option>
                <option value="agama">Agama</option>
                <option value="muatan_lokal">Muatan Lokal</option>
                <option value="ekstrakurikuler">Ekstrakurikuler</option>
              </select>
            </div>
          </div>

          <!-- Tingkat Berlaku, JP & KKM -->
          <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Tingkat Kelas</label>
                <select v-model="subjectForm.tingkat_berlaku" class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white focus:ring-2 focus:ring-purple-500 outline-none">
                  <option value="Semua Tingkat">Semua Tingkat</option>
                  <option value="Kelas 1">Kelas 1</option>
                  <option value="Kelas 2">Kelas 2</option>
                  <option value="Kelas 3">Kelas 3</option>
                  <option value="Kelas 4">Kelas 4</option>
                  <option value="Kelas 5">Kelas 5</option>
                  <option value="Kelas 6">Kelas 6</option>
                </select>
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Beban Jam / Minggu (JP)</label>
                <input
                  v-model.number="subjectForm.jam_per_minggu"
                  type="number"
                  min="1"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-purple-500 outline-none"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">KKM Standar</label>
                <input
                  v-model.number="subjectForm.kkm"
                  type="number"
                  min="0"
                  max="100"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-purple-500 outline-none"
                />
              </div>
            </div>
          </div>

          <!-- Bottom Action Bar -->
          <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between gap-4">
            <button
              @click="closeForm"
              type="button"
              class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition flex items-center gap-2 cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              <span>Kembali ke Pelajaran</span>
            </button>

            <div class="flex items-center gap-3">
              <button
                @click="closeForm"
                type="button"
                class="px-4 py-2.5 text-gray-500 hover:text-gray-700 text-xs font-semibold transition cursor-pointer"
              >
                Batal
              </button>
              <button
                type="submit"
                :disabled="savingSubject"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md shadow-purple-600/25 transition disabled:opacity-50 active:scale-95 cursor-pointer"
              >
                <svg v-if="savingSubject" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ savingSubject ? 'Menyimpan...' : (isEditingSubject ? 'Simpan Perubahan' : 'Buat Pelajaran / Tema') }}</span>
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- ============================================================== -->
    <!-- VIEW 4: HALAMAN FORMULIR JADWAL KBM (DEDICATED FULL-PAGE)       -->
    <!-- ============================================================== -->
    <div v-else-if="viewMode === 'form-schedule'" class="space-y-6 animate-in fade-in duration-200">
      <!-- Top Navigation & Action Banner -->
      <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
          <button
            @click="closeForm"
            type="button"
            class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center justify-center shrink-0 shadow-xs group cursor-pointer"
            title="Kembali ke Jadwal KBM"
          >
            <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
              <span>Akademik</span>
              <span>/</span>
              <button type="button" @click="closeForm" class="hover:text-purple-600 hover:underline cursor-pointer">Jadwal KBM</button>
              <span>/</span>
              <span class="text-purple-700 font-bold">{{ isEditingSchedule ? 'Edit Jadwal' : 'Tambah Jadwal Baru' }}</span>
            </div>
            <h1 class="text-lg sm:text-2xl font-black text-gray-900 leading-tight">
              {{ isEditingSchedule ? 'Edit Jadwal KBM' : 'Formulir Jadwal KBM Baru' }}
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              Tentukan hari, alokasi jam/sesi, rombel kelas, mata pelajaran/tema, guru pengampu, dan ruangan.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2.5 self-end sm:self-center">
          <button
            @click="closeForm"
            type="button"
            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition cursor-pointer"
          >
            Batal
          </button>
          <button
            @click="submitScheduleForm"
            :disabled="savingSchedule"
            type="button"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md shadow-purple-600/25 transition disabled:opacity-50 active:scale-95 cursor-pointer"
          >
            <svg v-if="savingSchedule" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ savingSchedule ? 'Menyimpan...' : (isEditingSchedule ? 'Simpan Perubahan' : 'Buat Jadwal KBM') }}</span>
          </button>
        </div>
      </div>

      <!-- Main Form Grid: 12 Cols -->
      <form @submit.prevent="submitScheduleForm" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- LEFT COLUMN: Live Preview Card Jadwal (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
          <div class="bg-gradient-to-br from-slate-900 via-purple-950 to-slate-900 p-5 rounded-2xl text-white shadow-lg space-y-4">
            <div class="flex items-center justify-between text-purple-300 text-xs font-bold">
              <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-purple-400 animate-ping"></span>
                KARTU JADWAL (PREVIEW)
              </span>
              <span class="font-mono text-[10px] text-slate-400 uppercase">{{ scheduleForm.hari }}</span>
            </div>

            <div class="pt-2">
              <span class="px-2.5 py-1 bg-white/20 text-white rounded-lg text-xs font-bold capitalize">
                {{ scheduleForm.hari }} • {{ getTimeSlotLabel(scheduleForm.time_slot_id) }}
              </span>

              <h3 class="text-lg font-black mt-3 text-white">
                {{ getSubjectName(scheduleForm.subject_id) }}
              </h3>
              <p class="text-xs text-purple-200 mt-0.5">
                Rombel: <strong class="text-white">{{ getClassName(scheduleForm.class_id) }}</strong>
              </p>
            </div>

            <div class="pt-3 border-t border-white/10 space-y-1.5 text-xs text-slate-300">
              <div class="flex justify-between">
                <span>Guru:</span>
                <span class="font-bold text-white">{{ getTeacherName(scheduleForm.teacher_id) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Ruang:</span>
                <span class="font-bold text-white">{{ getRoomName(scheduleForm.room_id) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: Form Inputs (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
          <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-gray-900 border-b pb-3">Alokasi Waktu & Rombel KBM</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Hari Belajar *</label>
                <select
                  v-model="scheduleForm.hari"
                  required
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white font-bold capitalize focus:ring-2 focus:ring-purple-500 outline-none"
                >
                  <option value="senin">Senin</option>
                  <option value="selasa">Selasa</option>
                  <option value="rabu">Rabu</option>
                  <option value="kamis">Kamis</option>
                  <option value="jumat">Jumat</option>
                  <option value="sabtu">Sabtu</option>
                </select>
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Sesi Jam Pelajaran (Time Slot) *</label>
                <select
                  v-model="scheduleForm.time_slot_id"
                  required
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white font-medium focus:ring-2 focus:ring-purple-500 outline-none"
                >
                  <option v-for="ts in timeSlots" :key="ts.id" :value="ts.id">
                    {{ ts.nama }} ({{ ts.jam_mulai }} - {{ ts.jam_selesai }})
                  </option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Rombel Kelas *</label>
                <select
                  v-model="scheduleForm.class_id"
                  required
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white font-bold text-gray-800 focus:ring-2 focus:ring-purple-500 outline-none"
                >
                  <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nama }} ({{ c.kode }})</option>
                </select>
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Guru Pengampu *</label>
                <select
                  v-model="scheduleForm.teacher_id"
                  required
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white font-medium focus:ring-2 focus:ring-purple-500 outline-none"
                >
                  <option v-for="t in teachersList" :key="t.id" :value="t.id">
                    {{ t.nama_lengkap }}{{ t.jenis_ptk === 'kepala_sekolah' ? ' (Kepala Sekolah)' : '' }}
                  </option>
                </select>
              </div>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Mata Pelajaran / Tema *</label>
              <select
                v-model="scheduleForm.subject_id"
                required
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white font-bold focus:ring-2 focus:ring-purple-500 outline-none"
              >
                <option :value="null" disabled>-- Pilih Pelajaran atau Tema --</option>
                <optgroup label="✨ Pelajaran Tematik Terpadu">
                  <option v-for="sub in subjectsList.filter(s => s.is_tematik)" :key="sub.id" :value="sub.id">
                    {{ sub.display_name || sub.nama }} ({{ sub.kode }})
                  </option>
                </optgroup>
                <optgroup label="📘 Mata Pelajaran Reguler / Mandiri">
                  <option v-for="sub in subjectsList.filter(s => !s.is_tematik)" :key="sub.id" :value="sub.id">
                    {{ sub.nama }} ({{ sub.kode }})
                  </option>
                </optgroup>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Ruangan Khusus (Opsional)</label>
              <select
                v-model="scheduleForm.room_id"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white focus:ring-2 focus:ring-purple-500 outline-none"
              >
                <option :value="null">-- Mengikuti Ruangan Kelas --</option>
                <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.nama }} ({{ r.kode }})</option>
              </select>
            </div>
          </div>

          <!-- Bottom Action Bar -->
          <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between gap-4">
            <button
              @click="closeForm"
              type="button"
              class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition flex items-center gap-2 cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              <span>Kembali ke Jadwal</span>
            </button>

            <div class="flex items-center gap-3">
              <button
                @click="closeForm"
                type="button"
                class="px-4 py-2.5 text-gray-500 hover:text-gray-700 text-xs font-semibold transition cursor-pointer"
              >
                Batal
              </button>
              <button
                type="submit"
                :disabled="savingSchedule"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md shadow-purple-600/25 transition disabled:opacity-50 active:scale-95 cursor-pointer"
              >
                <svg v-if="savingSchedule" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ savingSchedule ? 'Menyimpan...' : (isEditingSchedule ? 'Simpan Perubahan' : 'Buat Jadwal KBM') }}</span>
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- ============================================================== -->
    <!-- QUICK SET CELL MODAL (ATUR JADWAL INTERAKTIF CEPAT)            -->
    <!-- ============================================================== -->
    <div
      v-if="isQuickSetModalOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 no-print animate-in fade-in duration-150"
      @click.self="isQuickSetModalOpen = false"
    >
      <div class="bg-white rounded-3xl border border-gray-100 shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[92vh]">
        <!-- Modal Header -->
        <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-amber-500/10 via-purple-500/5 to-white">
          <div class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-2xl bg-amber-500 text-white font-black flex items-center justify-center text-sm shadow-sm uppercase">
              {{ quickSetForm.hari?.slice(0, 3) }}
            </span>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-base font-black text-gray-900 leading-tight">
                  Atur Sesi: {{ quickSetForm.hari?.toUpperCase() }}
                </h3>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-purple-100 text-purple-800">
                  {{ selectedClass?.nama || 'Kelas' }}
                </span>
              </div>
              <p class="text-xs text-gray-500 font-mono mt-0.5">
                {{ quickSetForm.jam_label }} • Slot Urutan ke-{{ quickSetForm.slot_urutan }}
              </p>
            </div>
          </div>
          <button
            @click="isQuickSetModalOpen = false"
            class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-800 flex items-center justify-center transition cursor-pointer font-bold text-lg"
          >
            &times;
          </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-5 overflow-y-auto space-y-4">
          <!-- Waktu & Jam Sesi (Bisa disesuaikan per kelas/tahun) -->
          <div class="bg-amber-50/70 p-3 rounded-2xl border border-amber-200/80">
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-[11px] font-bold text-amber-950 uppercase tracking-wider block">
                ⏰ Waktu Sesi (Dapat Disesuaikan per Kelas/Tahun)
              </span>
              <span class="text-[10px] text-amber-800 font-mono font-semibold">Slot #{{ quickSetForm.slot_urutan }}</span>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="text-[10px] font-bold text-gray-600 block mb-0.5">Jam Mulai:</label>
                <input
                  v-model="quickSetForm.jam_mulai"
                  type="time"
                  class="w-full bg-white border border-amber-300 rounded-xl px-2.5 py-1.5 text-xs font-bold text-gray-900 focus:ring-2 focus:ring-amber-500 outline-none"
                />
              </div>
              <div>
                <label class="text-[10px] font-bold text-gray-600 block mb-0.5">Jam Selesai:</label>
                <input
                  v-model="quickSetForm.jam_selesai"
                  type="time"
                  class="w-full bg-white border border-amber-300 rounded-xl px-2.5 py-1.5 text-xs font-bold text-gray-900 focus:ring-2 focus:ring-amber-500 outline-none"
                />
              </div>
            </div>
          </div>

          <!-- 1-Click Preset Chips -->
          <div class="space-y-2">
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block">
              Pilihan Cepat (1-Klik Terapkan Tema / Kegiatan)
            </label>

            <!-- Tematik Pills -->
            <div class="flex flex-wrap gap-1.5">
              <button
                type="button"
                @click="applyPreset('TEMA 1')"
                class="px-2.5 py-1 rounded-lg text-xs font-black bg-[#fca5a5] hover:bg-[#f87171] text-red-950 border border-red-300 transition cursor-pointer"
              >
                TEMA 1
              </button>
              <button
                type="button"
                @click="applyPreset('TEMA 2')"
                class="px-2.5 py-1 rounded-lg text-xs font-black bg-[#7dd3fc] hover:bg-[#38bdf8] text-sky-950 border border-sky-300 transition cursor-pointer"
              >
                TEMA 2
              </button>
              <button
                type="button"
                @click="applyPreset('TEMA 3')"
                class="px-2.5 py-1 rounded-lg text-xs font-black bg-[#86efac] hover:bg-[#4ade80] text-emerald-950 border border-emerald-300 transition cursor-pointer"
              >
                TEMA 3
              </button>
              <button
                type="button"
                @click="applyPreset('TEMA 4')"
                class="px-2.5 py-1 rounded-lg text-xs font-black bg-[#fed7aa] hover:bg-[#fb923c] text-amber-950 border border-amber-300 transition cursor-pointer"
              >
                TEMA 4
              </button>
              <button
                type="button"
                @click="applyPreset('TEMA 5')"
                class="px-2.5 py-1 rounded-lg text-xs font-black bg-[#e0e7ff] hover:bg-[#c7d2fe] text-indigo-950 border border-indigo-200 transition cursor-pointer"
              >
                TEMA 5
              </button>
            </div>

            <!-- Pembiasaan & Ibadah Pills -->
            <div class="flex flex-wrap gap-1.5 pt-1">
              <button
                type="button"
                v-for="p in ['LENTERA', 'Upacara', 'Asmaul Husna', 'Shalat Dhuha + Istirahat', 'Shalat Dzuhur + Makan siang', 'Dzikir+ Senam', 'Ekstrakulikuler Pilihan', 'Ekstrakulikuler Wajib']"
                :key="p"
                @click="applyPreset(p)"
                class="px-2 py-0.5 rounded-lg text-[11px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-200 transition cursor-pointer"
              >
                {{ p }}
              </button>
            </div>
          </div>

          <div class="border-t border-gray-100 pt-3 space-y-3">
            <!-- Mata Pelajaran Dropdown -->
            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">Mata Pelajaran (Hubungkan ke Kurikulum)</label>
              <select
                v-model="quickSetForm.subject_id"
                @change="onSubjectChange"
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs bg-white text-gray-800 font-semibold focus:ring-2 focus:ring-purple-500 outline-none"
              >
                <option :value="null">-- Bukan Mapel Khusus (Pembiasaan / Ibadah / Istirahat) --</option>
                <option v-for="sub in subjectsList" :key="sub.id" :value="sub.id">
                  {{ sub.nama }} ({{ sub.kode }})
                </option>
              </select>
            </div>

            <!-- Title Input -->
            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">Judul / Nama Sesi Kegiatan *</label>
              <input
                v-model="quickSetForm.judul_kegiatan"
                type="text"
                placeholder="Contoh: TEMA 1, LENTERA, Upacara, Shalat Dhuha..."
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>

            <!-- Tipe Kegiatan -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
              <button
                type="button"
                v-for="tk in [
                  { val: 'kbm', label: 'KBM / Mapel' },
                  { val: 'pembiasaan', label: 'Pembiasaan' },
                  { val: 'istirahat', label: 'Istirahat' },
                  { val: 'ekskul', label: 'Ekskul' }
                ]"
                :key="tk.val"
                @click="quickSetForm.tipe_kegiatan = tk.val"
                :class="quickSetForm.tipe_kegiatan === tk.val ? 'bg-purple-600 text-white font-bold' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                class="py-1.5 px-2 rounded-xl text-[11px] text-center transition cursor-pointer"
              >
                {{ tk.label }}
              </button>
            </div>

            <!-- Guru Pengampu -->
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="text-xs font-bold text-gray-700">Guru Pengampu</label>
                <span v-if="quickSetForm.teacher_id" class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                  ✓ Terhubung ke Guru
                </span>
              </div>
              <select
                v-model="quickSetForm.teacher_id"
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs bg-white font-semibold text-gray-800 focus:ring-2 focus:ring-purple-500 outline-none"
              >
                <option :value="null">-- Tanpa Guru Pengampu Khusus --</option>
                <option v-for="t in teachersList" :key="t.id" :value="t.id">
                  {{ t.nama_lengkap }} ({{ t.jabatan || 'Guru' }})
                </option>
              </select>
              <p class="text-[11px] text-gray-500 mt-1">
                Guru yang dipilih akan langsung tampil di sel jadwal kelas ini dan tercatat dalam data mengajar.
              </p>
            </div>

            <!-- Catatan Khusus -->
            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">Catatan Khusus (Opsional)</label>
              <input
                v-model="quickSetForm.catatan"
                type="text"
                placeholder="Contoh: Javanese language, Arabic language, Bawa alat shalat..."
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-800 focus:ring-2 focus:ring-purple-500 outline-none"
              />
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-4 border-t border-gray-100 bg-gray-50 flex items-center justify-between gap-2">
          <button
            v-if="quickSetForm.is_existing"
            type="button"
            @click="clearCurrentSlot"
            :disabled="savingQuickSet"
            class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition cursor-pointer disabled:opacity-50"
          >
            Kosongkan Slot
          </button>
          <div v-else></div>

          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="isQuickSetModalOpen = false"
              class="px-3.5 py-2 text-gray-500 hover:text-gray-700 text-xs font-semibold transition cursor-pointer"
            >
              Batal
            </button>
            <button
              type="button"
              @click="saveQuickSet"
              :disabled="savingQuickSet || !quickSetForm.judul_kegiatan"
              class="inline-flex items-center gap-1.5 px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md shadow-purple-600/20 transition disabled:opacity-50 cursor-pointer active:scale-95"
            >
              <svg v-if="savingQuickSet" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span>{{ savingQuickSet ? 'Menyimpan...' : 'Simpan Jadwal' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Form Tambah / Edit Pembagian Mengajar Guru (Teacher Assignment) -->
    <div
      v-if="isAssignmentModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs animate-fade-in"
    >
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg border border-purple-100 overflow-hidden animate-scale-up">
        <!-- Modal Header -->
        <div class="px-6 py-5 bg-gradient-to-r from-purple-700 via-indigo-700 to-purple-800 text-white flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-lg shadow-inner">
              👥
            </div>
            <div>
              <h3 class="text-base font-extrabold tracking-tight">
                {{ isEditingAssignment ? 'Edit Pembagian Mengajar Guru' : 'Tambah Pembagian Mengajar Guru' }}
              </h3>
              <p class="text-xs text-purple-200">
                Atur guru pengampu mata pelajaran / tema untuk rombel kelas
              </p>
            </div>
          </div>
          <button
            @click="isAssignmentModalOpen = false"
            class="text-white/80 hover:text-white p-1.5 rounded-xl hover:bg-white/10 transition cursor-pointer"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Modal Body Form -->
        <form @submit.prevent="saveAssignmentForm" class="p-6 space-y-4">
          <!-- Guru Pengampu -->
          <div>
            <label class="block text-xs font-bold text-gray-700 mb-1.5">
              Guru Pengampu <span class="text-rose-500">*</span>
            </label>
            <select
              v-model="assignmentForm.teacher_id"
              required
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs bg-white text-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition font-medium"
            >
              <option value="" disabled>-- Pilih Guru Pendidik --</option>
              <option v-for="t in teachersList" :key="t.id" :value="t.id">
                {{ t.nama_lengkap }} ({{ t.nip ? `NIP. ${t.nip}` : 'Pendidik' }})
              </option>
            </select>
          </div>

          <!-- Mata Pelajaran / Tema -->
          <div>
            <label class="block text-xs font-bold text-gray-700 mb-1.5">
              Mata Pelajaran / Tema Tematik <span class="text-rose-500">*</span>
            </label>
            <select
              v-model="assignmentForm.subject_id"
              required
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs bg-white text-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition font-medium"
            >
              <option value="" disabled>-- Pilih Mata Pelajaran / Tema --</option>
              <option v-for="s in subjectsList" :key="s.id" :value="s.id">
                {{ s.kode }} - {{ s.nama }} {{ s.is_tematik ? '(Tematik)' : '' }}
              </option>
            </select>
          </div>

          <!-- Rombel Kelas -->
          <div>
            <label class="block text-xs font-bold text-gray-700 mb-1.5">
              Rombel Kelas <span class="text-rose-500">*</span>
            </label>
            <select
              v-model="assignmentForm.class_id"
              required
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs bg-white text-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition font-medium"
            >
              <option value="" disabled>-- Pilih Rombel Kelas --</option>
              <option v-for="c in classes" :key="c.id" :value="c.id">
                {{ c.nama }} ({{ c.kode }})
              </option>
            </select>
          </div>

          <!-- Sesi Waktu / Keterangan Khusus & Beban Jam -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1.5">
                Beban Jam (JP / Minggu)
              </label>
              <input
                v-model.number="assignmentForm.jam_per_minggu"
                type="number"
                min="1"
                max="40"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs bg-white text-gray-800 focus:ring-2 focus:ring-purple-500 outline-none transition font-medium"
                placeholder="Contoh: 4"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1.5">
                Sesi Waktu / Keterangan
              </label>
              <select
                v-model="assignmentForm.keterangan"
                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs bg-white text-gray-800 focus:ring-2 focus:ring-purple-500 outline-none transition font-medium"
              >
                <option value="Reguler (Seluruh Sesi)">Reguler (Seluruh Sesi)</option>
                <option value="Pagi: Jam ke-1 s/d 4 (Sebelum Istirahat)">Pagi: Jam ke-1 s/d 4 (Sebelum Istirahat)</option>
                <option value="Siang: Jam ke-5 s/d 7 (Sesudah Istirahat)">Siang: Jam ke-5 s/d 7 (Sesudah Istirahat)</option>
                <option value="Sesi Tertentu">Sesi Tertentu (Kustom)</option>
              </select>
            </div>
          </div>

          <!-- Opsi Keterangan Custom jika Sesi Tertentu -->
          <div v-if="assignmentForm.keterangan === 'Sesi Tertentu'">
            <label class="block text-xs font-bold text-gray-700 mb-1.5">
              Catatan Sesi Kustom
            </label>
            <input
              v-model="assignmentForm.keterangan_custom"
              type="text"
              placeholder="Contoh: Khusus hari Jumat / Sesi 1-2"
              class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs bg-white text-gray-800 focus:ring-2 focus:ring-purple-500 outline-none transition"
            />
          </div>

          <!-- Info Box Sinkronisasi Jadwal -->
          <div class="p-3.5 bg-purple-50 rounded-2xl border border-purple-200/80 flex items-start gap-2.5 text-xs text-purple-900">
            <span class="text-base shrink-0">⚡</span>
            <div class="leading-relaxed">
              <strong>Tersambung Langsung ke Jadwal Mingguan:</strong>
              <p class="text-[11px] text-purple-700 mt-0.5">
                Menyimpan data ini akan otomatis memperbarui pengampu tema di jadwal pelajaran mingguan kelas yang bersangkutan sesuai sesi waktunya.
              </p>
            </div>
          </div>

          <!-- Modal Footer Actions -->
          <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2.5">
            <button
              type="button"
              @click="isAssignmentModalOpen = false"
              class="px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-600 hover:bg-gray-50 transition cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="savingAssignment"
              class="inline-flex items-center gap-1.5 px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md shadow-purple-600/20 transition disabled:opacity-50 cursor-pointer active:scale-95"
            >
              <svg v-if="savingAssignment" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span>{{ savingAssignment ? 'Menyimpan...' : (isEditingAssignment ? 'Perbarui Penugasan' : 'Simpan Penugasan') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, defineComponent, h } from 'vue';
import { useRoute } from 'vue-router';
import api from '../services/api';
import { confirmDeleteWithKeyword, showSuccess, showError, showConfirm } from '../services/swal';

const route = useRoute();

// View Mode: 'tabs' (tampilan utama daftar) atau form penuh ('form-class', 'form-subject', 'form-schedule')
const viewMode = ref<'tabs' | 'form-class' | 'form-subject' | 'form-schedule'>('tabs');
const activeTab = ref('classes');

const classes = ref<any[]>([]);
const schedules = ref<any[]>([]);
const timeSlots = ref<any[]>([]);
const rooms = ref<any[]>([]);
const teachersList = ref<any[]>([]);
const subjectsList = ref<any[]>([]);
const activeYearId = ref<number | null>(null);

// ==========================================
// TEACHER ASSIGNMENTS (PEMBAGIAN MENGAJAR GURU)
// ==========================================
const assignmentsList = ref<any[]>([]);
const loadingAssignments = ref(false);
const syncingAssignments = ref(false);
const isAssignmentModalOpen = ref(false);
const isEditingAssignment = ref(false);
const editingAssignmentId = ref<number | null>(null);
const savingAssignment = ref(false);

const assignmentFilterSubject = ref('');
const assignmentFilterTeacher = ref('');
const assignmentFilterClass = ref('');

const assignmentForm = ref<any>({
  teacher_id: '',
  subject_id: '',
  class_id: '',
  academic_year_id: null,
  jam_per_minggu: 4,
  keterangan: 'Reguler (Seluruh Sesi)',
  keterangan_custom: '',
});

const filteredAssignments = computed(() => {
  return assignmentsList.value.filter((item: any) => {
    if (assignmentFilterSubject.value && item.subject_id !== Number(assignmentFilterSubject.value)) {
      return false;
    }
    if (assignmentFilterTeacher.value && item.teacher_id !== Number(assignmentFilterTeacher.value)) {
      return false;
    }
    if (assignmentFilterClass.value && item.class_id !== Number(assignmentFilterClass.value)) {
      return false;
    }
    return true;
  });
});

const uniqueTeachersCount = computed(() => {
  const ids = new Set(assignmentsList.value.map((a: any) => a.teacher_id));
  return ids.size;
});

const uniqueClassesCount = computed(() => {
  const ids = new Set(assignmentsList.value.map((a: any) => a.class_id));
  return ids.size;
});

const selectedClassId = ref<number | null>(3);
const scheduleViewMode = ref<'matrix' | 'table'>('matrix');
const loadingSchedules = ref(false);

const selectedClass = computed(() => {
  return classes.value.find(c => c.id === selectedClassId.value) || classes.value[0];
});

const selectClass = (clsId: number) => {
  selectedClassId.value = clsId;
  fetchClassSchedules();
};

// ==========================================
// SUBJECT & TEMATIK STATE & METHODS
// ==========================================
const searchSubject = ref('');
const filterSubjectType = ref('all');
const filterSubjectLevel = ref('');
const isEditingSubject = ref(false);
const editingSubjectId = ref<number | null>(null);
const savingSubject = ref(false);
const customMuatanInput = ref('');
const commonMuatanOptions = [
  'Matematika', 'IPA', 'Bahasa Indonesia', 'PPKn', 'SBdP', 'IPS', 'PJOK', 'Pendidikan Agama Islam', 'Bahasa Inggris', 'Bahasa Jawa'
];

const subjectForm = ref<any>({
  is_tematik: true,
  tema_nomor: 1,
  nama: 'Tema 1: Indahnya Kebersamaan',
  kode: 'TEMA-1',
  muatan_pelajaran: ['Matematika', 'IPA'],
  deskripsi: '',
  kelompok: 'tematik',
  tingkat_berlaku: 'Kelas 1',
  jam_per_minggu: 6,
  kkm: 75,
  status: 'aktif',
});

// Cell Content Component for Table Grid
const CellContent = defineComponent({
  props: {
    hari: { type: String, required: true },
    urutan: { type: Number, required: true },
    fallback: { type: String, default: '' },
  },
  setup(props) {
    return () => {
      const s = cellSchedule(props.hari, props.urutan);
      let title = '';
      if (s) {
        title = s.judul_kegiatan || s.subject?.nama || props.fallback;
      } else if (selectedClassId.value === 3 || (schedules.value.length > 0 && props.fallback)) {
        title = props.fallback;
      }

      if (!title) {
        return h(
          'div',
          { class: 'text-gray-300 hover:text-purple-600 font-bold text-[11px] py-1 flex items-center justify-center gap-1 transition' },
          [
            h('span', { class: 'text-xs leading-none font-bold' }, '+'),
            h('span', { class: 'text-[9px] font-medium leading-none' }, 'Atur')
          ]
        );
      }

      const teacher = s?.teacher?.nama_lengkap || '';
      const catatan = s?.catatan || '';

      const children: any[] = [
        h('div', { class: 'font-extrabold leading-tight text-[11px] sm:text-xs text-gray-900 tracking-tight' }, title)
      ];

      if (teacher) {
        children.push(
          h(
            'div',
            { class: 'text-[9px] font-bold text-purple-950 bg-black/5 hover:bg-black/10 px-1 py-0.5 rounded mt-0.5 truncate leading-tight transition inline-block max-w-full' },
            teacher
          )
        );
      }

      if (catatan) {
        children.push(
          h('div', { class: 'text-[8.5px] italic text-gray-700/90 truncate leading-tight mt-0.5' }, catatan)
        );
      }

      return h('div', { class: 'leading-tight py-0.5 select-none' }, children);
    };
  }
});

const formatSlotTime = (hari: string, urutan: number, fallback: string = '') => {
  const ts = timeSlots.value.find(t => t.hari === hari && t.urutan === urutan);
  if (ts?.jam_mulai && ts?.jam_selesai) {
    return `${ts.jam_mulai.slice(0, 5)} - ${ts.jam_selesai.slice(0, 5)}`;
  }
  return fallback;
};

// ==========================================
// QUICK SET STATE & METHODS
// ==========================================
const isQuickSetModalOpen = ref(false);
const savingQuickSet = ref(false);
const quickSetForm = ref<any>({
  hari: '',
  slot_urutan: 1,
  time_slot_id: null,
  jam_mulai: '08:00',
  jam_selesai: '08:35',
  jam_label: '',
  judul_kegiatan: '',
  tipe_kegiatan: 'kbm',
  subject_id: null,
  teacher_id: null,
  catatan: '',
  is_existing: false,
});

const cellSchedule = (hari: string, urutan: number) => {
  return schedules.value.find(s => s.hari === hari && s.time_slot?.urutan === urutan);
};

const cellTitle = (hari: string, urutan: number, fallback: string = '') => {
  const s = cellSchedule(hari, urutan);
  if (s) {
    return s.judul_kegiatan || s.subject?.nama || fallback;
  }
  if (selectedClassId.value === 3 || schedules.value.length > 0) {
    return fallback;
  }
  return '';
};

const cellStyleClass = (hari: string, urutan: number) => {
  const s = cellSchedule(hari, urutan);
  if (!s && schedules.value.length === 0 && selectedClassId.value !== 3) {
    return 'bg-amber-50/20 hover:bg-purple-50 text-gray-400 font-normal';
  }
  const name = (s ? (s.judul_kegiatan || s.subject?.nama || '') : '').toUpperCase();
  
  if (name.includes('TEMA 1')) return 'bg-[#fca5a5] hover:bg-[#f87171] text-red-950 font-black';
  if (name.includes('TEMA 2')) return 'bg-[#7dd3fc] hover:bg-[#38bdf8] text-sky-950 font-black';
  if (name.includes('TEMA 3')) return 'bg-[#86efac] hover:bg-[#4ade80] text-emerald-950 font-black';
  if (name.includes('TEMA 4')) return 'bg-[#fed7aa] hover:bg-[#fb923c] text-amber-950 font-black';
  if (name.includes('TEMA 5')) return 'bg-[#e0e7ff] hover:bg-[#c7d2fe] text-indigo-950 font-black';
  
  if (s) {
    return 'bg-white hover:bg-gray-100 text-gray-900 font-bold';
  }
  return 'bg-white hover:bg-purple-50/50 text-gray-800 font-bold';
};

const cellTooltip = (hari: string, urutan: number) => {
  const s = cellSchedule(hari, urutan);
  if (s) {
    let tip = `${s.judul_kegiatan || s.subject?.nama} (${(s.tipe_kegiatan || 'kbm').toUpperCase()})`;
    if (s.teacher) tip += ` • Guru: ${s.teacher.nama_lengkap}`;
    if (s.catatan) tip += ` • Catatan: ${s.catatan}`;
    return tip;
  }
  return `Klik untuk mengatur slot ${hari} urutan ${urutan}`;
};

const openQuickSet = (hari: string, urutan: number) => {
  const ts = timeSlots.value.find(t => t.hari === hari && t.urutan === urutan);
  const existing = cellSchedule(hari, urutan);

  const jamMulai = existing?.time_slot?.jam_mulai?.slice(0, 5) || ts?.jam_mulai?.slice(0, 5) || '08:00';
  const jamSelesai = existing?.time_slot?.jam_selesai?.slice(0, 5) || ts?.jam_selesai?.slice(0, 5) || '08:35';

  quickSetForm.value = {
    hari,
    slot_urutan: urutan,
    time_slot_id: ts?.id || existing?.time_slot_id || null,
    jam_mulai: jamMulai,
    jam_selesai: jamSelesai,
    jam_label: `${jamMulai} - ${jamSelesai}`,
    judul_kegiatan: existing?.judul_kegiatan || existing?.subject?.nama || (existing ? '' : cellTitle(hari, urutan, '')),
    tipe_kegiatan: existing?.tipe_kegiatan || 'kbm',
    subject_id: existing?.subject_id || null,
    teacher_id: existing?.teacher_id || null,
    catatan: existing?.catatan || '',
    is_existing: Boolean(existing),
  };

  // If no teacher selected yet, auto-suggest based on homeroom teacher of this class if available
  if (!quickSetForm.value.teacher_id && selectedClass.value?.homeroom_teacher_id) {
    quickSetForm.value.teacher_id = selectedClass.value.homeroom_teacher_id;
  }

  isQuickSetModalOpen.value = true;
};

const findAssignedTeacherForSubject = (subId: number, slotUrutan: number = 1): number | null => {
  if (!subId || !selectedClassId.value) return null;

  // Cari seluruh penugasan guru resmi untuk mapel/tema ini di rombel kelas terpilih
  const matches = assignmentsList.value.filter(
    (a: any) => a.subject_id === subId && a.class_id === selectedClassId.value
  );

  if (matches.length === 1) {
    return matches[0].teacher_id;
  }

  // Jika ada lebih dari satu guru pengampu di kelas ini (misalnya Rosyid Pagi & Bayu Siang di Tema 4)
  if (matches.length > 1) {
    if (slotUrutan <= 4) {
      const pagiMatch = matches.find((m: any) => m.keterangan && m.keterangan.toLowerCase().includes('pagi'));
      if (pagiMatch) return pagiMatch.teacher_id;
    } else {
      const siangMatch = matches.find((m: any) => m.keterangan && m.keterangan.toLowerCase().includes('siang'));
      if (siangMatch) return siangMatch.teacher_id;
    }
    return matches[0].teacher_id;
  }

  // Fallback ke wali kelas jika tidak ada penugasan khusus
  if (selectedClass.value?.homeroom_teacher_id) {
    return selectedClass.value.homeroom_teacher_id;
  }

  return null;
};

const onSubjectChange = () => {
  const subId = quickSetForm.value.subject_id;
  if (!subId) return;
  const sub = subjectsList.value.find(s => s.id === subId);
  if (!sub) return;

  quickSetForm.value.judul_kegiatan = sub.nama;
  quickSetForm.value.tipe_kegiatan = 'kbm';

  // Hubungkan otomatis guru pengampu dari data penugasan resmi rombel kelas ini
  const assignedTeacherId = findAssignedTeacherForSubject(subId, quickSetForm.value.slot_urutan || 1);
  if (assignedTeacherId) {
    quickSetForm.value.teacher_id = assignedTeacherId;
  }
};

const applyPreset = (preset: string) => {
  quickSetForm.value.judul_kegiatan = preset;
  
  if (preset.startsWith('TEMA')) {
    quickSetForm.value.tipe_kegiatan = 'kbm';
    const num = parseInt(preset.replace(/\D/g, '')) || 1;
    const sub = subjectsList.value.find(s => s.is_tematik && (s.tema_nomor === num || s.kode === `TEMA${num}` || s.kode === `TEMA-${num}`));
    if (sub) {
      quickSetForm.value.subject_id = sub.id;
      const assignedTeacherId = findAssignedTeacherForSubject(sub.id, quickSetForm.value.slot_urutan || 1);
      if (assignedTeacherId) {
        quickSetForm.value.teacher_id = assignedTeacherId;
      }
    }
  } else if (['LENTERA', 'Upacara', 'Asmaul Husna', 'Dzikir+ Senam'].includes(preset)) {
    quickSetForm.value.tipe_kegiatan = 'pembiasaan';
    quickSetForm.value.subject_id = null;
    quickSetForm.value.teacher_id = null;
  } else if (['Shalat Dhuha + Istirahat', 'Shalat Dzuhur + Makan siang'].includes(preset)) {
    quickSetForm.value.tipe_kegiatan = 'istirahat';
    quickSetForm.value.subject_id = null;
    quickSetForm.value.teacher_id = null;
  } else if (preset.includes('Ekstrakulikuler')) {
    quickSetForm.value.tipe_kegiatan = 'ekskul';
    quickSetForm.value.subject_id = null;
  }
};

const saveQuickSet = async () => {
  if (!quickSetForm.value.time_slot_id) {
    showError('Gagal!', 'Data time slot waktu belum tersedia untuk sesi ini.');
    return;
  }

  savingQuickSet.value = true;
  try {
    const payload = {
      action: 'save',
      academic_year_id: activeYearId.value,
      class_id: selectedClassId.value,
      hari: quickSetForm.value.hari,
      time_slot_id: quickSetForm.value.time_slot_id,
      jam_mulai: quickSetForm.value.jam_mulai ? (quickSetForm.value.jam_mulai.length === 5 ? `${quickSetForm.value.jam_mulai}:00` : quickSetForm.value.jam_mulai) : null,
      jam_selesai: quickSetForm.value.jam_selesai ? (quickSetForm.value.jam_selesai.length === 5 ? `${quickSetForm.value.jam_selesai}:00` : quickSetForm.value.jam_selesai) : null,
      subject_id: quickSetForm.value.subject_id || null,
      teacher_id: quickSetForm.value.teacher_id || null,
      room_id: null,
      judul_kegiatan: quickSetForm.value.judul_kegiatan,
      tipe_kegiatan: quickSetForm.value.tipe_kegiatan || 'kbm',
      catatan: quickSetForm.value.catatan || null,
      force: true,
    };

    const res = await api.post('/schedules/quick-set', payload);
    if (res.data.success) {
      showSuccess('Berhasil!', 'Slot jadwal berhasil diperbarui.');
      isQuickSetModalOpen.value = false;
      await fetchClassSchedules();

      // Refresh time slots if times were changed
      try {
        const resSlots = await api.get('/time-slots');
        if (resSlots.data.success) {
          timeSlots.value = resSlots.data.data;
        }
      } catch (e) {
        // ignore
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err.response?.data?.message || 'Terjadi kesalahan saat menyimpan jadwal.');
  } finally {
    savingQuickSet.value = false;
  }
};

const tematikCount = computed(() => subjectsList.value.filter(s => s.is_tematik).length);
const regulerCount = computed(() => subjectsList.value.filter(s => !s.is_tematik).length);

const filteredSubjects = computed(() => {
  return subjectsList.value.filter(s => {
    if (searchSubject.value.trim()) {
      const q = searchSubject.value.toLowerCase();
      const matchName = s.nama?.toLowerCase().includes(q);
      const matchCode = s.kode?.toLowerCase().includes(q);
      const matchMuatan = Array.isArray(s.muatan_pelajaran) && s.muatan_pelajaran.some((m: string) => m.toLowerCase().includes(q));
      if (!matchName && !matchCode && !matchMuatan) return false;
    }

    if (filterSubjectType.value === 'tematik' && !s.is_tematik) return false;
    if (filterSubjectType.value === 'reguler' && s.is_tematik) return false;

    if (filterSubjectLevel.value && s.tingkat_berlaku && !s.tingkat_berlaku.includes(filterSubjectLevel.value) && s.tingkat_berlaku !== 'Semua Tingkat') {
      return false;
    }

    return true;
  });
});

const onTemaNomorChange = () => {
  if (!isEditingSubject.value) {
    const num = subjectForm.value.tema_nomor || 1;
    subjectForm.value.kode = `TEMA-${num}`;
    if (!subjectForm.value.nama || subjectForm.value.nama.startsWith('Tema ')) {
      subjectForm.value.nama = `Tema ${num}`;
    }
  }
};

const toggleMuatan = (opt: string) => {
  const arr = subjectForm.value.muatan_pelajaran || [];
  const idx = arr.indexOf(opt);
  if (idx > -1) {
    arr.splice(idx, 1);
  } else {
    arr.push(opt);
  }
  subjectForm.value.muatan_pelajaran = [...arr];
};

const addCustomMuatan = () => {
  const val = customMuatanInput.value.trim();
  if (!val) return;
  if (!subjectForm.value.muatan_pelajaran.includes(val)) {
    subjectForm.value.muatan_pelajaran.push(val);
  }
  customMuatanInput.value = '';
};

const removeMuatan = (idx: number | string) => {
  subjectForm.value.muatan_pelajaran.splice(Number(idx), 1);
};

// ==========================================
// CLASS STATE & METHODS
// ==========================================
const isEditingClass = ref(false);
const editingClassId = ref<number | null>(null);
const savingClass = ref(false);
const exporting = ref(false);
const classForm = ref<any>({
  academic_year_id: null,
  kode: '',
  nama: '',
  tingkat: 1,
  paralel: 'A',
  wali_kelas_id: null,
  room_id: null,
  kapasitas: 28,
  status: 'aktif',
});

// ==========================================
// SCHEDULE STATE & METHODS
// ==========================================
const isEditingSchedule = ref(false);
const editingScheduleId = ref<number | null>(null);
const savingSchedule = ref(false);
const scheduleForm = ref<any>({
  academic_year_id: null,
  class_id: null,
  subject_id: null,
  teacher_id: null,
  room_id: null,
  hari: 'senin',
  time_slot_id: null,
  status: 'aktif',
});

// Helpers for preview cards
const getTeacherName = (id: any) => {
  if (!id) return 'Belum Dipilih';
  const found = teachersList.value.find(t => t.id === id);
  return found ? found.nama_lengkap : 'Belum Dipilih';
};

const getRoomName = (id: any) => {
  if (!id) return 'Ruang Default';
  const found = rooms.value.find(r => r.id === id);
  return found ? `${found.nama} (${found.kode})` : 'Ruang Default';
};

const getClassName = (id: any) => {
  if (!id) return 'Belum Dipilih';
  const found = classes.value.find(c => c.id === id);
  return found ? found.nama : 'Belum Dipilih';
};

const getSubjectName = (id: any) => {
  if (!id) return 'Pelajaran Belum Dipilih';
  const found = subjectsList.value.find(s => s.id === id);
  return found ? (found.display_name || found.nama) : 'Pelajaran Belum Dipilih';
};

const getTimeSlotLabel = (id: any) => {
  if (!id) return '';
  const found = timeSlots.value.find(ts => ts.id === id);
  return found ? `${found.nama} (${found.jam_mulai}-${found.jam_selesai})` : '';
};

// ==========================================
// INTERACTIVE SCHEDULE MATRIX & TIMETABLE STATE
// ==========================================

const sortedSchedules = computed(() => {
  const dayOrder: Record<string, number> = {
    senin: 1, selasa: 2, rabu: 3, kamis: 4, jumat: 5, sabtu: 6
  };
  return [...schedules.value].sort((a, b) => {
    const da = dayOrder[a.hari] || 7;
    const db = dayOrder[b.hari] || 7;
    if (da !== db) return da - db;
    return (a.time_slot?.urutan || 0) - (b.time_slot?.urutan || 0);
  });
});

// ==========================================
// TEACHER ASSIGNMENTS CRUD METHODS
// ==========================================
const fetchTeacherAssignments = async () => {
  loadingAssignments.value = true;
  try {
    const res = await api.get('/teacher-assignments', {
      params: { all: true, academic_year_id: activeYearId.value || undefined }
    });
    if (res.data.success) {
      assignmentsList.value = res.data.data;
    }
  } catch (err) {
    console.error('Gagal memuat pembagian mengajar guru:', err);
  } finally {
    loadingAssignments.value = false;
  }
};

const openCreateAssignmentModal = () => {
  isEditingAssignment.value = false;
  editingAssignmentId.value = null;
  assignmentForm.value = {
    teacher_id: '',
    subject_id: '',
    class_id: selectedClassId.value || (classes.value[0]?.id || ''),
    academic_year_id: activeYearId.value,
    jam_per_minggu: 4,
    keterangan: 'Reguler (Seluruh Sesi)',
    keterangan_custom: '',
  };
  isAssignmentModalOpen.value = true;
};

const openEditAssignmentModal = (item: any) => {
  isEditingAssignment.value = true;
  editingAssignmentId.value = item.id;

  let ket = item.keterangan || 'Reguler (Seluruh Sesi)';
  let ketCustom = '';
  const standardOptions = [
    'Reguler (Seluruh Sesi)',
    'Pagi: Jam ke-1 s/d 4 (Sebelum Istirahat)',
    'Siang: Jam ke-5 s/d 7 (Sesudah Istirahat)'
  ];
  if (!standardOptions.includes(ket)) {
    ketCustom = ket;
    ket = 'Sesi Tertentu';
  }

  assignmentForm.value = {
    teacher_id: item.teacher_id,
    subject_id: item.subject_id,
    class_id: item.class_id,
    academic_year_id: item.academic_year_id || activeYearId.value,
    jam_per_minggu: item.jam_per_minggu || 4,
    keterangan: ket,
    keterangan_custom: ketCustom,
  };
  isAssignmentModalOpen.value = true;
};

const saveAssignmentForm = async () => {
  if (!assignmentForm.value.teacher_id || !assignmentForm.value.subject_id || !assignmentForm.value.class_id) {
    showError('Validasi Gagal', 'Silakan lengkapi Guru, Mata Pelajaran/Tema, dan Rombel Kelas.');
    return;
  }

  savingAssignment.value = true;
  try {
    const finalKeterangan = assignmentForm.value.keterangan === 'Sesi Tertentu'
      ? (assignmentForm.value.keterangan_custom || 'Sesi Tertentu')
      : assignmentForm.value.keterangan;

    const payload = {
      teacher_id: assignmentForm.value.teacher_id,
      subject_id: assignmentForm.value.subject_id,
      class_id: assignmentForm.value.class_id,
      academic_year_id: assignmentForm.value.academic_year_id || activeYearId.value,
      jam_per_minggu: assignmentForm.value.jam_per_minggu || 4,
      keterangan: finalKeterangan,
    };

    let res;
    if (isEditingAssignment.value && editingAssignmentId.value) {
      res = await api.put(`/teacher-assignments/${editingAssignmentId.value}`, payload);
    } else {
      res = await api.post('/teacher-assignments', payload);
    }

    if (res.data.success) {
      showSuccess(
        isEditingAssignment.value ? 'Penugasan Diperbarui!' : 'Penugasan Ditambahkan!',
        res.data.message || 'Data pembagian mengajar guru berhasil disimpan dan disinkronkan ke jadwal.'
      );
      isAssignmentModalOpen.value = false;
      await fetchTeacherAssignments();
      if (selectedClassId.value === payload.class_id) {
        await fetchClassSchedules();
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err, 'Terjadi kesalahan saat menyimpan penugasan guru.');
  } finally {
    savingAssignment.value = false;
  }
};

const handleDeleteAssignment = async (item: any) => {
  const teacherName = item.teacher?.nama_lengkap || 'Guru';
  const subjectName = item.subject?.nama || 'Mata Pelajaran';
  const className = item.school_class?.nama || item.class?.nama || 'Kelas';

  const confirmed = await confirmDeleteWithKeyword(`penugasan ${teacherName} ${subjectName} ${className}`);
  if (!confirmed) return;

  try {
    const res = await api.delete(`/teacher-assignments/${item.id}`);
    if (res.data.success) {
      showSuccess('Berhasil Dihapus!', res.data.message);
      await fetchTeacherAssignments();
    }
  } catch (err: any) {
    showError('Gagal Menghapus!', err, 'Penugasan tidak dapat dihapus.');
  }
};

const syncAssignmentsWithSchedule = async () => {
  const confirmed = await showConfirm(
    'Sinkronkan ke Jadwal Mingguan?',
    'Sistem akan menyinkronkan seluruh pembagian mengajar guru resmi ke slot jadwal mingguan kelas 1-4. Slot tematik akan otomatis diperbarui gurunya sesuai penugasan.',
    'Ya, Sinkronkan Sekarang'
  );
  if (!confirmed) return;

  syncingAssignments.value = true;
  try {
    const res = await api.post('/teacher-assignments/sync-to-schedules', {
      academic_year_id: activeYearId.value
    });
    if (res.data.success) {
      showSuccess('Sinkronisasi Berhasil!', res.data.message);
      await fetchTeacherAssignments();
      await fetchClassSchedules();
    }
  } catch (err: any) {
    showError('Gagal Sinkronisasi!', err, 'Gagal menyinkronkan ke jadwal.');
  } finally {
    syncingAssignments.value = false;
  }
};

const fetchClassSchedules = async () => {
  if (!selectedClassId.value) return;
  loadingSchedules.value = true;
  try {
    const params: any = {
      class_id: selectedClassId.value,
      all: true,
    };
    if (activeYearId.value) params.academic_year_id = activeYearId.value;

    const res = await api.get('/schedules', { params });
    if (res.data.success) {
      schedules.value = res.data.data;
    }
  } catch (err) {
    console.error('Gagal mengambil jadwal kelas', err);
  } finally {
    loadingSchedules.value = false;
  }
};

watch(selectedClassId, (newId) => {
  if (newId && activeTab.value === 'schedules') {
    fetchClassSchedules();
  }
});

const clearCurrentSlot = async () => {
  if (!quickSetForm.value.time_slot_id) return;

  savingQuickSet.value = true;
  try {
    const payload = {
      action: 'delete',
      academic_year_id: activeYearId.value,
      class_id: selectedClassId.value,
      hari: quickSetForm.value.hari,
      time_slot_id: quickSetForm.value.time_slot_id,
    };

    const res = await api.post('/schedules/quick-set', payload);
    if (res.data.success) {
      showSuccess('Dikosongkan!', 'Slot jadwal berhasil dikosongkan.');
      isQuickSetModalOpen.value = false;
      await fetchClassSchedules();
    }
  } catch (err: any) {
    showError('Gagal!', err.response?.data?.message || 'Gagal mengosongkan slot.');
  } finally {
    savingQuickSet.value = false;
  }
};

const applyOfficialTemplateToClass = async () => {
  const cls = selectedClass.value;
  const name = cls?.nama || 'kelas terpilih';

  const confirmed = await showConfirm(
    'Terapkan Jadwal Resmi?',
    `Susunan jadwal standar (11 sesi x 6 hari seperti foto dinas) akan diterapkan pada ${name}. Jadwal yang sudah ada pada kelas ini akan diperbarui.`,
    'Ya, Terapkan Jadwal'
  );

  if (!confirmed) return;

  loadingSchedules.value = true;
  try {
    const res = await api.post('/schedules/apply-template', {
      class_id: selectedClassId.value,
      academic_year_id: activeYearId.value,
    });
    if (res.data.success) {
      showSuccess('Berhasil Diterapkan!', res.data.message);
      await fetchClassSchedules();
    }
  } catch (err: any) {
    showError('Gagal Menerapkan Template!', err.response?.data?.message || 'Terjadi kesalahan saat menyalin template jadwal.');
  } finally {
    loadingSchedules.value = false;
  }
};

const clearEntireClassSchedules = async () => {
  const cls = selectedClass.value;
  const name = cls?.nama || 'kelas terpilih';

  const confirmed = await confirmDeleteWithKeyword(`seluruh jadwal ${name}`);
  if (!confirmed) return;

  loadingSchedules.value = true;
  try {
    const res = await api.post('/schedules/clear-class', {
      class_id: selectedClassId.value,
      academic_year_id: activeYearId.value,
    });
    if (res.data.success) {
      showSuccess('Berhasil Dibersihkan!', res.data.message);
      await fetchClassSchedules();
    }
  } catch (err: any) {
    showError('Gagal Membersihkan!', err.response?.data?.message || 'Gagal membersihkan jadwal kelas.');
  } finally {
    loadingSchedules.value = false;
  }
};

const printSchedule = () => {
  window.print();
};

const initialLoadedTabs = ref<Record<string, boolean>>({});
const isLoadingCurrentTab = ref(false);

const loadTabData = async (tab: string, forceRefresh: boolean = false) => {
  // Jika tab sudah pernah dimuat dan tidak diminta force refresh, pakai cache memori (instant 0ms)
  if (!forceRefresh && initialLoadedTabs.value[tab]) {
    return;
  }

  isLoadingCurrentTab.value = true;
  try {
    const promises: Promise<any>[] = [];

    // Academic Year selalu diambil jika belum ada di memori
    if (!activeYearId.value) {
      promises.push(
        api.get('/academic-years').then((res: any) => {
          if (res.data.success && res.data.data) {
            const activeYear = res.data.data.find((y: any) => y.is_aktif) || res.data.data[0];
            if (activeYear) activeYearId.value = activeYear.id;
          }
        }).catch(() => {})
      );
    }

    if (tab === 'classes') {
      if (classes.value.length === 0 || forceRefresh) {
        promises.push(
          api.get('/classes', { params: { all: true } }).then((res: any) => {
            if (res.data.success) {
              classes.value = res.data.data;
              if (!selectedClassId.value && classes.value.length > 0) {
                const cls3 = classes.value.find((c: any) => c.nama.includes('3'));
                selectedClassId.value = cls3 ? cls3.id : classes.value[0].id;
              }
            }
          })
        );
      }
      if (teachersList.value.length === 0) {
        promises.push(
          api.get('/teachers').then((res: any) => {
            if (res.data.success) teachersList.value = res.data.data;
          }).catch(() => {})
        );
      }
      if (rooms.value.length === 0) {
        promises.push(
          api.get('/rooms').then((res: any) => {
            if (res.data.success) rooms.value = res.data.data;
          }).catch(() => {})
        );
      }
    } else if (tab === 'subjects') {
      if (subjectsList.value.length === 0 || forceRefresh) {
        promises.push(
          api.get('/subjects', { params: { all: true } }).then((res: any) => {
            if (res.data.success) subjectsList.value = res.data.data;
          })
        );
      }
    } else if (tab === 'assignments') {
      promises.push(fetchTeacherAssignments());
      if (teachersList.value.length === 0) {
        promises.push(
          api.get('/teachers').then((res: any) => {
            if (res.data.success) teachersList.value = res.data.data;
          }).catch(() => {})
        );
      }
      if (subjectsList.value.length === 0) {
        promises.push(
          api.get('/subjects', { params: { all: true } }).then((res: any) => {
            if (res.data.success) subjectsList.value = res.data.data;
          }).catch(() => {})
        );
      }
      if (classes.value.length === 0) {
        promises.push(
          api.get('/classes', { params: { all: true } }).then((res: any) => {
            if (res.data.success) classes.value = res.data.data;
          }).catch(() => {})
        );
      }
    } else if (tab === 'schedules') {
      if (classes.value.length === 0) {
        promises.push(
          api.get('/classes', { params: { all: true } }).then((res: any) => {
            if (res.data.success) {
              classes.value = res.data.data;
              if (!selectedClassId.value && classes.value.length > 0) {
                const cls3 = classes.value.find((c: any) => c.nama.includes('3'));
                selectedClassId.value = cls3 ? cls3.id : classes.value[0].id;
              }
            }
          })
        );
      }
      if (timeSlots.value.length === 0) {
        promises.push(
          api.get('/time-slots').then((res: any) => {
            if (res.data.success) timeSlots.value = res.data.data;
          }).catch(() => {})
        );
      }
      if (teachersList.value.length === 0) {
        promises.push(
          api.get('/teachers').then((res: any) => {
            if (res.data.success) teachersList.value = res.data.data;
          }).catch(() => {})
        );
      }
      if (subjectsList.value.length === 0) {
        promises.push(
          api.get('/subjects', { params: { all: true } }).then((res: any) => {
            if (res.data.success) subjectsList.value = res.data.data;
          }).catch(() => {})
        );
      }
      if (assignmentsList.value.length === 0) {
        promises.push(fetchTeacherAssignments());
      }
      promises.push(fetchClassSchedules());
    } else if (tab === 'slots') {
      if (timeSlots.value.length === 0 || forceRefresh) {
        promises.push(
          api.get('/time-slots').then((res: any) => {
            if (res.data.success) timeSlots.value = res.data.data;
          })
        );
      }
    } else if (tab === 'rooms') {
      if (rooms.value.length === 0 || forceRefresh) {
        promises.push(
          api.get('/rooms').then((res: any) => {
            if (res.data.success) rooms.value = res.data.data;
          })
        );
      }
    } else if (tab === 'academic_years') {
      promises.push(fetchAcademicYearsData());
      if (classes.value.length === 0 || forceRefresh) {
        promises.push(
          api.get('/classes', { params: { all: true } }).then((res: any) => {
            if (res.data.success) classes.value = res.data.data;
          })
        );
      }
    }

    await Promise.all(promises);
    initialLoadedTabs.value[tab] = true;
  } catch (err) {
    console.error('Gagal memuat data tab:', tab, err);
  } finally {
    isLoadingCurrentTab.value = false;
  }
};

// Alias kompatibel untuk tempat lain yang memanggil loadAll()
const loadAll = async () => {
  await loadTabData(activeTab.value, true);
};

const exportClassesExcel = async () => {
  exporting.value = true;
  try {
    const params: any = {};
    if (activeYearId.value) params.academic_year_id = activeYearId.value;

    const res = await api.get('/classes/export', {
      params,
      responseType: 'blob',
    });

    const blob = new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `Master_Data_Kelas_Rombel_${new Date().toISOString().slice(0, 10)}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    showSuccess('Berhasil!', 'File Excel data kelas & rombel berhasil diunduh.');
  } catch (err: any) {
    showError('Gagal Export Excel!', err, 'Terjadi kendala saat mengunduh berkas Excel data kelas.');
  } finally {
    exporting.value = false;
  }
};

const closeForm = () => {
  viewMode.value = 'tabs';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

// ==========================================
// FORM OPENERS
// ==========================================
const openCreateClassForm = () => {
  isEditingClass.value = false;
  editingClassId.value = null;
  classForm.value = {
    academic_year_id: activeYearId.value,
    kode: '',
    nama: '',
    tingkat: 1,
    paralel: 'A',
    wali_kelas_id: null,
    room_id: rooms.value[0]?.id || null,
    kapasitas: 28,
    status: 'aktif',
  };
  viewMode.value = 'form-class';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const openEditClassForm = (c: any) => {
  isEditingClass.value = true;
  editingClassId.value = c.id;
  classForm.value = {
    academic_year_id: c.academic_year_id || activeYearId.value,
    kode: c.kode || '',
    nama: c.nama || '',
    tingkat: c.tingkat || 1,
    paralel: c.paralel || '',
    wali_kelas_id: c.wali_kelas_id || c.wali_kelas?.id || null,
    room_id: c.room_id || c.room?.id || null,
    kapasitas: c.kapasitas || 28,
    status: c.status || 'aktif',
  };
  viewMode.value = 'form-class';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const openCreateSubjectForm = () => {
  isEditingSubject.value = false;
  editingSubjectId.value = null;
  subjectForm.value = {
    is_tematik: true,
    tema_nomor: 1,
    nama: 'Tema 1: Indahnya Kebersamaan',
    kode: 'TEMA-1',
    muatan_pelajaran: ['Matematika', 'IPA'],
    deskripsi: '',
    kelompok: 'tematik',
    tingkat_berlaku: 'Kelas 1',
    jam_per_minggu: 6,
    kkm: 75,
    status: 'aktif',
  };
  viewMode.value = 'form-subject';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const openEditSubjectForm = (sub: any) => {
  isEditingSubject.value = true;
  editingSubjectId.value = sub.id;
  subjectForm.value = {
    is_tematik: Boolean(sub.is_tematik),
    tema_nomor: sub.tema_nomor || 1,
    nama: sub.nama || '',
    kode: sub.kode || '',
    muatan_pelajaran: Array.isArray(sub.muatan_pelajaran) ? [...sub.muatan_pelajaran] : [],
    deskripsi: sub.deskripsi || '',
    kelompok: String(sub.kelompok || (sub.is_tematik ? 'tematik' : 'umum')).toLowerCase(),
    tingkat_berlaku: sub.tingkat_berlaku || 'Semua Tingkat',
    jam_per_minggu: sub.jam_per_minggu || 4,
    kkm: sub.kkm || 75,
    status: String(sub.status || 'aktif').toLowerCase(),
  };
  viewMode.value = 'form-subject';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const openCreateScheduleForm = () => {
  isEditingSchedule.value = false;
  editingScheduleId.value = null;
  scheduleForm.value = {
    academic_year_id: activeYearId.value,
    class_id: classes.value[0]?.id || null,
    subject_id: subjectsList.value[0]?.id || null,
    teacher_id: teachersList.value[0]?.id || null,
    room_id: null,
    hari: 'senin',
    time_slot_id: timeSlots.value[0]?.id || null,
    status: 'aktif',
  };
  viewMode.value = 'form-schedule';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const openEditScheduleForm = (sch: any) => {
  isEditingSchedule.value = true;
  editingScheduleId.value = sch.id;
  scheduleForm.value = {
    academic_year_id: sch.academic_year_id || activeYearId.value,
    class_id: sch.class_id || sch.school_class?.id || null,
    subject_id: sch.subject_id || sch.subject?.id || null,
    teacher_id: sch.teacher_id || sch.teacher?.id || null,
    room_id: sch.room_id || sch.room?.id || null,
    hari: sch.hari || 'senin',
    time_slot_id: sch.time_slot_id || sch.time_slot?.id || null,
    status: sch.status || 'aktif',
  };
  viewMode.value = 'form-schedule';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

// ==========================================
// FORM SUBMITTERS
// ==========================================
const submitClassForm = async () => {
  savingClass.value = true;
  try {
    const payload = { ...classForm.value };
    if (!payload.academic_year_id) payload.academic_year_id = activeYearId.value;

    if (isEditingClass.value && editingClassId.value) {
      const res = await api.put(`/classes/${editingClassId.value}`, payload);
      if (res.data.success) {
        showSuccess('Berhasil!', 'Data rombel kelas berhasil diperbarui.');
        activeTab.value = 'classes';
        closeForm();
        loadAll();
      }
    } else {
      const res = await api.post('/classes', payload);
      if (res.data.success) {
        showSuccess('Berhasil!', 'Rombel kelas baru berhasil dibuat.');
        activeTab.value = 'classes';
        closeForm();
        loadAll();
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err, 'Terjadi kesalahan saat menyimpan rombel.');
  } finally {
    savingClass.value = false;
  }
};

const submitSubjectForm = async () => {
  savingSubject.value = true;
  try {
    const payload = { ...subjectForm.value };
    if (payload.is_tematik) {
      payload.kelompok = 'tematik';
    } else if (payload.kelompok) {
      payload.kelompok = String(payload.kelompok).toLowerCase();
    }
    if (payload.status) {
      payload.status = String(payload.status).toLowerCase();
    }

    if (isEditingSubject.value && editingSubjectId.value) {
      const res = await api.put(`/subjects/${editingSubjectId.value}`, payload);
      if (res.data.success) {
        showSuccess('Berhasil!', 'Mata pelajaran / Tema berhasil diperbarui.');
        activeTab.value = 'subjects';
        closeForm();
        loadAll();
      }
    } else {
      const res = await api.post('/subjects', payload);
      if (res.data.success) {
        showSuccess('Berhasil!', 'Mata pelajaran / Tema baru berhasil ditambahkan.');
        activeTab.value = 'subjects';
        closeForm();
        loadAll();
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err, 'Periksa kembali kelengkapan formulir.');
  } finally {
    savingSubject.value = false;
  }
};

const submitScheduleForm = async () => {
  savingSchedule.value = true;
  try {
    const payload = { ...scheduleForm.value };
    if (!payload.academic_year_id) payload.academic_year_id = activeYearId.value;

    if (isEditingSchedule.value && editingScheduleId.value) {
      const res = await api.put(`/schedules/${editingScheduleId.value}`, payload);
      if (res.data.success) {
        showSuccess('Berhasil!', 'Jadwal KBM berhasil diperbarui.');
        activeTab.value = 'schedules';
        closeForm();
        loadAll();
      }
    } else {
      const res = await api.post('/schedules', payload);
      if (res.data.success) {
        showSuccess('Berhasil!', 'Jadwal KBM baru berhasil ditambahkan.');
        activeTab.value = 'schedules';
        closeForm();
        loadAll();
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err, 'Terjadi konflik jadwal atau validasi gagal.');
  } finally {
    savingSchedule.value = false;
  }
};

// ==========================================
// DELETERS
// ==========================================
const handleDeleteClass = async (c: any) => {
  const confirmed = await confirmDeleteWithKeyword(`rombel "${c.nama}" (${c.kode})`);
  if (!confirmed) return;

  try {
    const res = await api.delete(`/classes/${c.id}`);
    if (res.data.success) {
      showSuccess('Berhasil Dihapus!', res.data.message);
      loadAll();
    }
  } catch (err: any) {
    showError('Gagal Menghapus!', err, 'Rombel tidak dapat dihapus.');
  }
};

const handleDeleteSubject = async (sub: any) => {
  const confirmed = await confirmDeleteWithKeyword(`pelajaran ${sub.nama} (${sub.kode})`);
  if (!confirmed) return;

  try {
    const res = await api.delete(`/subjects/${sub.id}`);
    if (res.data.success) {
      showSuccess('Terhapus!', 'Mata pelajaran berhasil dihapus.');
      loadAll();
    }
  } catch (err: any) {
    showError('Gagal Menghapus!', err, 'Mata pelajaran masih digunakan pada jadwal.');
  }
};

const handleDeleteSchedule = async (sch: any) => {
  const confirmed = await confirmDeleteWithKeyword(`jadwal ${sch.hari} kelas ${sch.school_class?.nama || ''} mapel ${sch.subject?.nama || ''}`);
  if (!confirmed) return;

  try {
    const res = await api.delete(`/schedules/${sch.id}`);
    if (res.data.success) {
      showSuccess('Berhasil Dihapus!', res.data.message);
      loadAll();
    }
  } catch (err: any) {
    showError('Gagal Menghapus!', err, 'Jadwal tidak dapat dihapus.');
  }
};

// ==========================================
// RUANG KELAS & FASILITAS
// ==========================================
const isRoomModalOpen = ref(false);
const isEditingRoom = ref(false);
const editingRoomId = ref<number | null>(null);
const savingRoom = ref(false);

const roomForm = ref({
  kode: '',
  nama: '',
  jenis: 'ruang_kelas',
  gedung_lantai: '',
  kapasitas: 28,
  kondisi: 'baik',
});

const searchRoom = ref('');
const filterRoomJenis = ref('');
const filterRoomKondisi = ref('');

const filteredRooms = computed(() => {
  return rooms.value.filter((r) => {
    if (searchRoom.value.trim()) {
      const q = searchRoom.value.toLowerCase();
      const matchName = r.nama?.toLowerCase().includes(q);
      const matchCode = r.kode?.toLowerCase().includes(q);
      const matchGedung = r.gedung_lantai?.toLowerCase().includes(q);
      if (!matchName && !matchCode && !matchGedung) return false;
    }
    if (filterRoomJenis.value && r.jenis !== filterRoomJenis.value) {
      return false;
    }
    if (filterRoomKondisi.value && r.kondisi !== filterRoomKondisi.value) {
      return false;
    }
    return true;
  });
});

const openCreateRoomModal = () => {
  isEditingRoom.value = false;
  editingRoomId.value = null;
  roomForm.value = {
    kode: '',
    nama: '',
    jenis: 'ruang_kelas',
    gedung_lantai: '',
    kapasitas: 28,
    kondisi: 'baik',
  };
  isRoomModalOpen.value = true;
};

const openEditRoomModal = (r: any) => {
  isEditingRoom.value = true;
  editingRoomId.value = r.id;
  roomForm.value = {
    kode: r.kode || '',
    nama: r.nama || '',
    jenis: r.jenis || 'ruang_kelas',
    gedung_lantai: r.gedung_lantai || '',
    kapasitas: r.kapasitas ?? 28,
    kondisi: r.kondisi || 'baik',
  };
  isRoomModalOpen.value = true;
};

const submitRoomForm = async () => {
  savingRoom.value = true;
  try {
    const payload = {
      kode: roomForm.value.kode?.trim().toUpperCase(),
      nama: roomForm.value.nama?.trim(),
      jenis: roomForm.value.jenis,
      gedung_lantai: roomForm.value.gedung_lantai?.trim() || null,
      kapasitas: roomForm.value.kapasitas ? Number(roomForm.value.kapasitas) : 28,
      kondisi: roomForm.value.kondisi || 'baik',
    };

    if (isEditingRoom.value && editingRoomId.value) {
      const res = await api.put(`/rooms/${editingRoomId.value}`, payload);
      if (res.data.success) {
        showSuccess('Berhasil!', res.data.message || 'Data ruangan berhasil diperbarui.');
        isRoomModalOpen.value = false;
        await loadTabData('rooms', true);
      }
    } else {
      const res = await api.post('/rooms', payload);
      if (res.data.success) {
        showSuccess('Berhasil!', res.data.message || 'Ruangan baru berhasil ditambahkan.');
        isRoomModalOpen.value = false;
        await loadTabData('rooms', true);
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err, 'Periksa kelengkapan kode dan nama ruangan.');
  } finally {
    savingRoom.value = false;
  }
};

const handleDeleteRoom = async (r: any) => {
  const confirmed = await confirmDeleteWithKeyword(`ruangan "${r.nama}" (${r.kode})`);
  if (!confirmed) return;

  try {
    const res = await api.delete(`/rooms/${r.id}`);
    if (res.data.success) {
      showSuccess('Berhasil Dihapus!', res.data.message || 'Ruangan berhasil dihapus.');
      await loadTabData('rooms', true);
    }
  } catch (err: any) {
    showError('Gagal Menghapus!', err, 'Ruangan tidak dapat dihapus karena masih digunakan pada rombel kelas atau jadwal KBM.');
  }
};

// ==========================================
// MASTER TAHUN AJARAN & KENAIKAN KELAS
// ==========================================
const academicYearsList = ref<any[]>([]);
const loadingAcademic = ref(false);

const fetchAcademicYearsData = async () => {
  loadingAcademic.value = true;
  try {
    const res = await api.get('/academic-years');
    if (res.data.success && res.data.data) {
      academicYearsList.value = res.data.data;
      const activeYear = res.data.data.find((y: any) => y.is_aktif || y.status === 'aktif');
      if (activeYear) {
        activeYearId.value = activeYear.id;
        if (!promoteTargetYearId.value) {
          promoteTargetYearId.value = activeYear.id;
        }
      }
    }
  } catch (err: any) {
    console.error('Gagal mengambil data tahun ajaran', err);
  } finally {
    loadingAcademic.value = false;
  }
};

const handleSetActiveAcademicYear = async (year: any) => {
  const confirmed = await showConfirm(
    `Aktifkan Tahun Ajaran ${year.nama}?`,
    `Tahun ajaran ini akan dijadikan tahun ajaran aktif sistem SIMBIM. Rombel, presensi, dan e-rapor akan menyesuaikan tahun ajaran ini.`,
    'Ya, Aktifkan'
  );
  if (!confirmed) return;

  try {
    const res = await api.post(`/academic-years/${year.id}/set-active`);
    if (res.data.success) {
      showSuccess('Berhasil!', res.data.message || `Tahun ajaran ${year.nama} kini aktif.`);
      await fetchAcademicYearsData();
    }
  } catch (err: any) {
    showError('Gagal Mengaktifkan!', err, 'Gagal mengatur tahun ajaran aktif.');
  }
};

const handleActivateSemester = async (sem: any) => {
  const confirmed = await showConfirm(
    `Aktifkan ${sem.nama}?`,
    `Semester aktif akan dipindahkan ke ${sem.nama}. Semester sebelumnya akan ditutup status aktifnya.`,
    'Ya, Aktifkan Semester'
  );
  if (!confirmed) return;

  try {
    const res = await api.post(`/semesters/${sem.id}/activate`);
    if (res.data.success) {
      showSuccess('Berhasil!', res.data.message || `Semester ${sem.nama} kini aktif.`);
      await fetchAcademicYearsData();
    }
  } catch (err: any) {
    showError('Gagal Mengaktifkan Semester!', err, 'Gagal mengubah semester aktif.');
  }
};

const showCreateYearModal = ref(false);
const newYearForm = ref({
  nama: '',
  tgl_mulai: '',
  tgl_selesai: '',
  status: 'rencana',
});

const openCreateYearModal = () => {
  newYearForm.value = {
    nama: '',
    tgl_mulai: '',
    tgl_selesai: '',
    status: 'rencana',
  };
  showCreateYearModal.value = true;
};

const submitCreateYearForm = async () => {
  try {
    const res = await api.post('/academic-years', newYearForm.value);
    if (res.data.success) {
      showSuccess('Berhasil!', 'Tahun ajaran baru dan semester ganjil/genap berhasil dibuat.');
      showCreateYearModal.value = false;
      await fetchAcademicYearsData();
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err, 'Periksa format tahun ajaran dan tanggal.');
  }
};

const promoteSourceClassId = ref<number | null>(null);
const promoteTargetYearId = ref<number | null>(null);
const promoteTargetClassId = ref<number | null>(null);
const studentsToPromote = ref<any[]>([]);
const loadingStudentsToPromote = ref(false);
const promoting = ref(false);

const fetchStudentsForPromotion = async () => {
  if (!promoteSourceClassId.value) {
    studentsToPromote.value = [];
    return;
  }
  loadingStudentsToPromote.value = true;
  try {
    let list: any[] = [];
    const res = await api.get('/students', {
      params: { class_id: promoteSourceClassId.value, all: true, status: 'aktif' }
    });
    if (res.data.success && Array.isArray(res.data.data) && res.data.data.length > 0) {
      list = res.data.data;
    } else {
      const resClass = await api.get(`/classes/${promoteSourceClassId.value}`);
      if (resClass.data.success && resClass.data.data) {
        list = resClass.data.data.active_students || resClass.data.data.students || [];
      }
    }

    studentsToPromote.value = list.map((s: any, idx: number) => ({
      student_id: s.id,
      nama_lengkap: s.nama_lengkap,
      nis: s.nis || '',
      nisn: s.nisn || '',
      status_kelas: 'naik',
      nomor_absen: s.nomor_absen ?? (idx + 1),
    }));
  } catch (err: any) {
    showError('Gagal Memuat Siswa!', err, 'Gagal mengambil daftar siswa dari rombel asal.');
  } finally {
    loadingStudentsToPromote.value = false;
  }
};

const setAllPromotionStatus = (status: string) => {
  studentsToPromote.value.forEach(s => {
    s.status_kelas = status;
  });
};

const handleExecutePromotion = async () => {
  if (!promoteSourceClassId.value || !promoteTargetYearId.value) {
    showError('Validasi Gagal', null, 'Harap pilih kelas asal dan tahun ajaran tujuan.');
    return;
  }

  const hasNaikOrTinggal = studentsToPromote.value.some(s => ['naik', 'tinggal_kelas'].includes(s.status_kelas));
  if (hasNaikOrTinggal && !promoteTargetClassId.value) {
    showError('Validasi Gagal', null, 'Untuk siswa yang naik atau tinggal kelas, kelas tujuan wajib dipilih.');
    return;
  }

  const confirmed = await showConfirm(
    'Konfirmasi Kenaikan / Kelulusan Kelas',
    `Yakin ingin mengeksekusi proses untuk ${studentsToPromote.value.length} siswa? Data riwayat kelas lama akan diarsipkan dan siswa akan dialokasikan ke kelas baru.`,
    'Ya, Eksekusi Sekarang'
  );
  if (!confirmed) return;

  promoting.value = true;
  try {
    const payload = {
      source_class_id: promoteSourceClassId.value,
      target_year_id: promoteTargetYearId.value,
      target_class_id: promoteTargetClassId.value || null,
      students: studentsToPromote.value.map(s => ({
        student_id: s.student_id,
        status_kelas: s.status_kelas,
        nomor_absen: s.nomor_absen || null,
      }))
    };

    const res = await api.post('/classes/promote-students', payload);
    if (res.data.success) {
      showSuccess('Berhasil!', res.data.message || 'Kenaikan kelas massal berhasil dieksekusi.');
      await fetchStudentsForPromotion();
      await loadTabData('classes', true);
    }
  } catch (err: any) {
    showError('Gagal Eksekusi!', err, 'Gagal memproses kenaikan kelas siswa.');
  } finally {
    promoting.value = false;
  }
};

watch(() => route.query.tab, (newTab) => {
  const targetTab = (typeof newTab === 'string' && newTab) ? newTab : 'classes';
  activeTab.value = targetTab;
  viewMode.value = 'tabs';
  loadTabData(targetTab);
}, { immediate: true });

onMounted(() => {
  if (route.query.tab && typeof route.query.tab === 'string') {
    activeTab.value = route.query.tab;
  }
});
</script>

<style>
@media print {
  /* Hide UI elements that shouldn't appear on paper printout */
  aside,
  nav,
  header,
  .no-print,
  button,
  .router-link,
  a[href] {
    display: none !important;
  }

  body,
  html,
  #app,
  main {
    background: #ffffff !important;
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
  }

  @page {
    size: A4 landscape;
    margin: 5mm 8mm;
  }

  .print-area {
    padding: 0 !important;
    margin: 0 !important;
    border: none !important;
    box-shadow: none !important;
    width: 100% !important;
    max-width: 100% !important;
  }

  table {
    width: 100% !important;
    border-collapse: collapse !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  th,
  td {
    border: 1px solid #78350f !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
}
</style>
