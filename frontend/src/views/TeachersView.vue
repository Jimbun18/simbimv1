<template>
  <div class="space-y-5 sm:space-y-6">
    <!-- ============================================================== -->
    <!-- VIEW 1: DAFTAR TABEL GURU & TENDIK (LIST VIEW)                 -->
    <!-- ============================================================== -->
    <div v-if="currentView === 'list'" class="space-y-5 sm:space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm">
        <div>
          <h1 class="text-lg sm:text-xl font-bold text-gray-900 leading-tight">Manajemen Pendidik & Tenaga Kependidikan (PTK)</h1>
          <p class="text-xs text-gray-500 mt-0.5">Daftar Guru dan Staf Administrasi/Tendik SDI Bina Insan Mandiri Purwokerto.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button
            @click="exportToExcel"
            :disabled="exporting"
            class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-xl border border-emerald-200 transition disabled:opacity-50"
            title="Export Data Guru & Tendik ke Excel"
          >
            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span v-if="exporting">Mengekspor...</span>
            <span v-else>Export Excel</span>
          </button>
          <router-link
            to="/excel-imports"
            class="inline-flex items-center gap-2 px-3.5 py-2 bg-sky-50 hover:bg-sky-100 text-sky-700 text-xs font-semibold rounded-xl border border-sky-200 transition"
          >
            Import Excel Guru
          </router-link>
          <button
            @click="openCreateForm"
            class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-md shadow-sky-600/20 transition active:scale-95"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Tambah Guru / Tendik</span>
          </button>
        </div>
      </div>

      <!-- Filter & Search Toolbar -->
      <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row gap-3 items-center justify-between">
        <div class="flex-1 w-full md:w-auto relative">
          <input
            v-model="search"
            @input="fetchTeachers"
            type="text"
            placeholder="Cari berdasarkan NIP, NIK, atau Nama Guru..."
            class="w-full px-4 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-sky-500 outline-none transition"
          />
        </div>

        <div class="flex gap-2 w-full md:w-auto">
          <select
            v-model="filterJenisPtk"
            @change="fetchTeachers"
            class="px-3 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-sky-500 outline-none text-gray-700"
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

      <!-- Table Guru -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-gray-600 min-w-[950px]">
            <thead class="bg-gray-50 text-[11px] font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
              <tr>
                <th class="py-3.5 px-4 w-12 text-center">No</th>
                <th class="py-3.5 px-4 w-14 text-center">Foto</th>
                <th class="py-3.5 px-4">NIP / NUPTK</th>
                <th class="py-3.5 px-4">Nama Lengkap & Gelar</th>
                <th class="py-3.5 px-4">Jabatan & Tugas Utama</th>
                <th class="py-3.5 px-4">Penugasan / Mengajar</th>
                <th class="py-3.5 px-4">Jenis PTK</th>
                <th class="py-3.5 px-4">Status & Masa Kerja</th>
                <th class="py-3.5 px-4">No. HP / WA</th>
                <th class="py-3.5 px-4 text-center">Status</th>
                <th class="py-3.5 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(t, idx) in teachers" :key="t.id" class="hover:bg-gray-50/70 transition">
                <td class="py-3 px-4 text-gray-400 font-mono text-center">{{ idx + 1 }}</td>
                <td class="py-2.5 px-4 text-center">
                  <div class="w-9 h-9 rounded-full overflow-hidden bg-gray-100 border border-gray-200 flex items-center justify-center mx-auto shadow-xs shrink-0">
                    <img
                      v-if="t.foto_url || t.foto_path"
                      :src="getPhotoUrl(t.foto_url, t.foto_path)"
                      :alt="t.nama_lengkap"
                      class="w-full h-full object-cover"
                      @error="(e: any) => e.target.style.display = 'none'"
                    />
                    <span v-else class="text-[11px] font-bold text-gray-500 uppercase">
                      {{ getInitials(t.nama_lengkap) }}
                    </span>
                  </div>
                </td>
                <td class="py-3 px-4 font-mono font-medium text-gray-700 whitespace-nowrap">
                  {{ t.nip ?? '-' }}
                </td>
                <td class="py-3 px-4 font-bold text-gray-900 whitespace-nowrap">
                  <button @click="openDetail(t)" class="text-left hover:text-sky-600 transition font-bold block cursor-pointer">
                    {{ t.nama_lengkap }}
                  </button>
                  <div class="text-[10px] text-gray-400 font-normal">{{ t.email ?? '-' }}</div>
                </td>
                <td class="py-3 px-4 whitespace-nowrap">
                  <div class="flex flex-col gap-1 items-start">
                    <span v-if="t.jabatan" class="px-2 py-0.5 bg-sky-50 text-sky-800 rounded-md border border-sky-200 text-[10px] font-bold">
                      {{ t.jabatan }}
                    </span>
                    <span v-else class="text-[11px] text-gray-400">-</span>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <div class="flex flex-wrap gap-1 max-w-[220px]">
                    <span v-if="t.tugas_tambahan" class="px-2 py-0.5 bg-amber-50 text-amber-800 rounded-md border border-amber-200 text-[10px] font-semibold">
                      {{ t.tugas_tambahan }}
                    </span>
                    <template v-else-if="t.assignments && t.assignments.length">
                      <span v-for="asg in t.assignments" :key="asg.id" class="px-1.5 py-0.5 bg-purple-50 text-purple-700 rounded-md border border-purple-200 text-[10px] font-medium">
                        {{ asg.subject?.nama }}
                      </span>
                    </template>
                    <span v-else class="text-[11px] text-gray-400">-</span>
                  </div>
                </td>
                <td class="py-3 px-4 whitespace-nowrap">
                  <span class="px-2 py-0.5 bg-slate-100 text-slate-800 rounded-md border border-slate-200 text-[10px] font-semibold uppercase">
                    {{ t.jenis_ptk?.replace('_', ' ') }}
                  </span>
                </td>
                <td class="py-3 px-4 whitespace-nowrap">
                  <div class="font-semibold text-gray-900 text-xs uppercase">{{ t.status_kepegawaian ?? 'GTY' }}</div>
                  <div class="text-[10px] text-gray-400">{{ calculateTenure(t.tgl_mulai_tugas) }}</div>
                </td>
                <td class="py-3 px-4 text-gray-600 font-mono whitespace-nowrap">
                  {{ t.no_hp }}
                </td>
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-full border border-emerald-200 text-[10px] font-bold uppercase">
                    {{ t.status }}
                  </span>
                </td>
                <td class="py-3 px-4 text-right whitespace-nowrap space-x-1.5">
                  <button
                    @click="openDetail(t)"
                    class="px-2.5 py-1 bg-gray-50 hover:bg-sky-50 hover:text-sky-700 rounded-lg text-xs font-semibold transition border border-gray-200 cursor-pointer"
                  >
                    Detail
                  </button>
                  <button
                    @click="openEditForm(t)"
                    class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg text-xs font-semibold transition border border-amber-200 cursor-pointer"
                  >
                    Edit
                  </button>
                  <button
                    @click="handleDeleteTeacher(t)"
                    class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-semibold transition cursor-pointer"
                  >
                    Hapus
                  </button>
                </td>
              </tr>
              <tr v-if="teachers.length === 0">
                <td colspan="11" class="py-8 text-center text-gray-400">
                  {{ loading ? 'Memuat data guru...' : 'Belum ada data guru yang cocok.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- VIEW 2: HALAMAN FORMULIR GURU PENUH (SUB-PAGE REPLACING LIST)  -->
    <!-- Menggantikan popup modal menjadi halaman form penuh & lega     -->
    <!-- ============================================================== -->
    <div v-else-if="currentView === 'form'" class="space-y-6 animate-in fade-in duration-200">
      <!-- Top Navigation & Action Banner -->
      <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
          <button
            @click="closeForm"
            type="button"
            class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center justify-center shrink-0 shadow-xs group"
            title="Kembali ke Daftar Guru & Tendik"
          >
            <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
              <span>Kepegawaian</span>
              <span>/</span>
              <button type="button" @click="closeForm" class="hover:text-sky-600 hover:underline">Data Guru & Tendik</button>
              <span>/</span>
              <span class="text-sky-700 font-bold">{{ isEditing ? 'Edit Guru' : 'Tambah Guru Baru' }}</span>
            </div>
            <h1 class="text-lg sm:text-2xl font-black text-gray-900 leading-tight">
              {{ isEditing ? `Edit Pendidik: ${form.nama_lengkap || '...'}` : 'Formulir Pendidik & Tendik Baru' }}
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              {{ isEditing ? 'Perbarui identitas kepegawaian, penugasan jenis PTK, dan kontak guru.' : 'Lengkapi formulir di bawah ini untuk mendaftarkan pendidik atau tenaga kependidikan baru.' }}
            </p>
          </div>
        </div>

        <!-- Top Action Buttons -->
        <div class="flex items-center gap-2.5 self-end sm:self-center">
          <button
            @click="closeForm"
            type="button"
            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition"
          >
            Batal
          </button>
          <button
            @click="submitForm"
            :disabled="saving"
            type="button"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-md shadow-sky-600/25 transition disabled:opacity-50 active:scale-95"
          >
            <svg v-if="saving" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ saving ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Data Guru') }}</span>
          </button>
        </div>
      </div>

      <!-- Main Form Grid: 12 Cols -->
      <form @submit.prevent="submitForm" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- LEFT COLUMN: Foto Profil & ID Card Guru Preview (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
          <!-- Photo Upload Card -->
          <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-gray-900">Pas Foto Guru / Tendik</h3>
              <span class="text-[10px] text-gray-400 font-semibold uppercase">Opsional</span>
            </div>

            <div class="flex flex-col items-center justify-center p-6 bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl group hover:border-sky-400 transition">
              <div class="w-28 h-28 rounded-2xl overflow-hidden bg-white border border-slate-200 shadow-sm flex items-center justify-center relative mb-3">
                <img
                  v-if="photoPreview"
                  :src="photoPreview"
                  alt="Preview Foto"
                  class="w-full h-full object-cover"
                />
                <div v-else class="text-center p-2 text-slate-400">
                  <svg class="w-10 h-10 mx-auto text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  <span class="text-[10px] font-semibold mt-1 block">Belum ada foto</span>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <label class="px-3 py-1.5 bg-sky-50 hover:bg-sky-100 text-sky-700 text-xs font-bold rounded-xl border border-sky-200 cursor-pointer transition shadow-xs">
                  Pilih Foto
                  <input
                    ref="fileInputRef"
                    type="file"
                    accept="image/png, image/jpeg, image/jpg, image/webp"
                    @change="handlePhotoChange"
                    class="hidden"
                  />
                </label>
                <button
                  v-if="photoPreview || photoFile"
                  type="button"
                  @click="clearPhoto"
                  class="px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 border border-rose-200 rounded-xl transition"
                >
                  Hapus
                </button>
              </div>
              <p class="text-[10px] text-gray-400 text-center mt-2">JPG, PNG, atau WebP. Maks 5 MB.</p>
            </div>
          </div>

          <!-- Live ID Card Preview Card (Teacher Profile) -->
          <div class="bg-gradient-to-br from-slate-900 via-sky-950 to-slate-900 p-5 rounded-2xl text-white shadow-lg space-y-4">
            <div class="flex items-center justify-between text-sky-400 text-xs font-bold">
              <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-sky-400 animate-ping"></span>
                KARTU PENDIDIK (PREVIEW)
              </span>
              <span class="font-mono text-[10px] text-slate-400">SIM-BIM</span>
            </div>

            <div class="flex items-center gap-3.5 pt-1">
              <div class="w-14 h-14 rounded-xl overflow-hidden bg-white/10 border border-white/20 shrink-0 flex items-center justify-center">
                <img
                  v-if="photoPreview"
                  :src="photoPreview"
                  alt="Preview"
                  class="w-full h-full object-cover"
                />
                <span v-else class="text-sm font-black text-sky-300 uppercase">
                  {{ getInitials(form.nama_lengkap || 'Guru') }}
                </span>
              </div>
              <div class="min-w-0 flex-1">
                <h4 class="text-sm font-bold truncate text-white">
                  {{ form.nama_lengkap || 'Nama Lengkap & Gelar' }}
                </h4>
                <p class="text-[11px] font-mono text-sky-300">
                  NIP: {{ form.nip || '-------' }}
                </p>
                <div class="flex items-center gap-1.5 mt-1">
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-white/15 text-slate-200 uppercase">
                    {{ form.jenis_ptk?.replace('_', ' ') }}
                  </span>
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-sky-500/20 text-sky-300 uppercase">
                    {{ form.status_kepegawaian }}
                  </span>
                </div>
              </div>
            </div>

            <div class="pt-3 border-t border-white/10 text-[11px] text-slate-300 flex justify-between items-center">
              <span>Status Keaktifan:</span>
              <span class="font-bold uppercase tracking-wider text-emerald-400">{{ form.status }}</span>
            </div>
          </div>

          <!-- Petunjuk Card -->
          <div class="bg-sky-50/70 p-4 rounded-2xl border border-sky-100 text-xs text-sky-950 space-y-1.5">
            <div class="font-bold flex items-center gap-1.5 text-sky-800">
              <span>💡</span> Catatan Petugas Kepegawaian
            </div>
            <p class="text-[11px] text-sky-700 leading-relaxed">
              Nomor WhatsApp digunakan untuk autentikasi dan komunikasi jadwal mengajar. Centang opsi pembuatan akun agar guru otomatis mendapatkan hak akses masuk ke jadwal dan presensi kelas.
            </p>
          </div>
        </div>

        <!-- RIGHT COLUMN: Form Sections (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
          
          <!-- CARD 1: Identitas Pokok Pendidik -->
          <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                  <span class="w-6 h-6 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center text-xs font-black">1</span>
                  Identitas Pokok Pendidik / Tendik
                </h3>
                <p class="text-xs text-gray-500 mt-0.5 ml-8">Informasi nama lengkap beserta gelar, NIK, NIP, jenis kelamin, dan tanggal lahir.</p>
              </div>
              <span class="text-[10px] text-rose-500 font-semibold">* Wajib diisi</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Lengkap & Gelar <span class="text-rose-500">*</span></label>
                <input
                  v-model="form.nama_lengkap"
                  type="text"
                  required
                  placeholder="Contoh: Ustadz Ahmad Fauzi, S.Pd.I."
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold text-gray-800 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">NIK (16 Digit KTP) <span class="text-rose-500">*</span></label>
                <input
                  v-model="form.nik"
                  type="text"
                  required
                  maxlength="16"
                  placeholder="Contoh: 330201..."
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">NIP / NUPTK</label>
                <input
                  v-model="form.nip"
                  type="text"
                  placeholder="Contoh: 19850101..."
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Jenis Kelamin <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-2 gap-2">
                  <button
                    type="button"
                    @click="form.jenis_kelamin = 'L'"
                    :class="form.jenis_kelamin === 'L' ? 'bg-sky-50 border-sky-400 text-sky-700 font-bold shadow-xs' : 'bg-gray-50 border-gray-200 text-gray-600'"
                    class="py-2 px-3 rounded-xl border text-xs flex items-center justify-center gap-1.5 transition"
                  >
                    <span>👨</span>
                    <span>Laki-laki</span>
                  </button>
                  <button
                    type="button"
                    @click="form.jenis_kelamin = 'P'"
                    :class="form.jenis_kelamin === 'P' ? 'bg-rose-50 border-rose-400 text-rose-700 font-bold shadow-xs' : 'bg-gray-50 border-gray-200 text-gray-600'"
                    class="py-2 px-3 rounded-xl border text-xs flex items-center justify-center gap-1.5 transition"
                  >
                    <span>👩</span>
                    <span>Perempuan</span>
                  </button>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Tempat Lahir <span class="text-rose-500">*</span></label>
                <input
                  v-model="form.tempat_lahir"
                  type="text"
                  required
                  placeholder="Purwokerto"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Tanggal Lahir <span class="text-rose-500">*</span></label>
                <input
                  v-model="form.tgl_lahir"
                  type="date"
                  required
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition"
                />
              </div>
            </div>
          </div>

          <!-- CARD 2: Penugasan & Status Kepegawaian -->
          <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <div class="border-b border-gray-100 pb-3">
              <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center text-xs font-black">2</span>
                Penugasan & Status Kepegawaian
              </h3>
              <p class="text-xs text-gray-500 mt-0.5 ml-8">Tentukan tugas pokok PTK, status ikatan kerja yayasan, dan masa tugas.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Jenis PTK <span class="text-rose-500">*</span></label>
                <select
                  v-model="form.jenis_ptk"
                  required
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none bg-white font-semibold text-sky-800 transition"
                >
                  <option value="guru_kelas">Guru Kelas</option>
                  <option value="guru_mapel">Guru Mapel</option>
                  <option value="guru_agama">Guru Agama / Tahfidz</option>
                  <option value="guru_olahraga">Guru Olahraga</option>
                  <option value="kepala_sekolah">Kepala Sekolah</option>
                </select>
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Status Kepegawaian <span class="text-rose-500">*</span></label>
                <select
                  v-model="form.status_kepegawaian"
                  required
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none bg-white transition"
                >
                  <option value="gty">GTY (Tetap Yayasan)</option>
                  <option value="gtt">GTT (Tidak Tetap)</option>
                  <option value="pns">PNS DPK</option>
                  <option value="pppk">PPPK</option>
                  <option value="honorer">Honorer</option>
                </select>
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Status Keaktifan <span class="text-rose-500">*</span></label>
                <select
                  v-model="form.status"
                  required
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none bg-white transition"
                >
                  <option value="aktif">Aktif</option>
                  <option value="cuti">Cuti</option>
                  <option value="nonaktif">Nonaktif</option>
                  <option value="pensiun">Pensiun</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Jabatan Struktural / Utama</label>
                <input
                  v-model="form.jabatan"
                  type="text"
                  placeholder="Contoh: Kepala Sekolah, Guru Kelas 1, Staf Tata Usaha, Guru BK"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Tugas Tambahan</label>
                <input
                  v-model="form.tugas_tambahan"
                  type="text"
                  placeholder="Contoh: Wali Kelas 1, Guru Tema 2, Guru Tema 4"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition"
                />
              </div>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Masa Bergabung / TMT Mulai Tugas <span class="text-rose-500">*</span></label>
              <input
                v-model="form.tgl_mulai_tugas"
                type="date"
                required
                class="w-full sm:w-1/2 border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition"
              />
            </div>
          </div>

          <!-- CARD 3: Alamat Domisili & Kontak -->
          <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <div class="border-b border-gray-100 pb-3">
              <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center text-xs font-black">3</span>
                Alamat Domisili & Kontak PTK
              </h3>
              <p class="text-xs text-gray-500 mt-0.5 ml-8">Kontak aktif untuk pengiriman jadwal mengajar dan koordinasi sekolah.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Alamat Domisili Lengkap <span class="text-rose-500">*</span></label>
                <input
                  v-model="form.alamat_jalan"
                  type="text"
                  required
                  placeholder="Jl. / RT / RW / Desa..."
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Kabupaten / Kota <span class="text-rose-500">*</span></label>
                <input
                  v-model="form.kab_kota"
                  type="text"
                  required
                  placeholder="Contoh: Banyumas"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">No. WhatsApp / HP Aktif <span class="text-rose-500">*</span></label>
                <input
                  v-model="form.no_hp"
                  type="text"
                  required
                  placeholder="081234567890"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Email</label>
                <input
                  v-model="form.email"
                  type="email"
                  placeholder="guru@simbim.sch.id"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition"
                />
              </div>
            </div>

            <!-- Akun Login Otomatis (Saat Tambah Baru) -->
            <div v-if="!isEditing" class="p-4 bg-sky-50 rounded-2xl border border-sky-100 flex items-center gap-3">
              <input
                type="checkbox"
                id="createUser"
                v-model="form.create_user_account"
                class="w-4 h-4 rounded text-sky-600 focus:ring-sky-500 border-gray-300"
              />
              <label for="createUser" class="text-xs font-semibold text-sky-900 cursor-pointer">
                Buatkan otomatis akun login SIM-BIM dengan password default (<code>Password123!</code>)
              </label>
            </div>
          </div>

          <!-- Bottom Sticky Action Bar -->
          <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between gap-4">
            <button
              @click="closeForm"
              type="button"
              class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition flex items-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              <span>Kembali ke Daftar Guru</span>
            </button>

            <div class="flex items-center gap-3">
              <button
                @click="closeForm"
                type="button"
                class="px-4 py-2.5 text-gray-500 hover:text-gray-700 text-xs font-semibold transition"
              >
                Batal
              </button>
              <button
                type="submit"
                :disabled="saving"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-md shadow-sky-600/25 transition disabled:opacity-50 active:scale-95"
              >
                <svg v-if="saving" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ saving ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Data Guru') }}</span>
              </button>
            </div>
          </div>

        </div>
      </form>
    </div>

    <!-- ============================================================== -->
    <!-- VIEW 3: HALAMAN DETAIL GURU & TENDIK PENUH (SUB-PAGE REPLACING LIST) -->
    <!-- ============================================================== -->
    <div v-else-if="currentView === 'detail' && selectedTeacher" class="space-y-6 animate-in fade-in duration-200">
      <!-- Top Navigation Banner -->
      <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
          <button
            @click="closeDetail"
            type="button"
            class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center justify-center shrink-0 shadow-xs group cursor-pointer"
            title="Kembali ke Daftar Guru & Tendik"
          >
            <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
              <span>Kepegawaian</span>
              <span>/</span>
              <button type="button" @click="closeDetail" class="hover:text-sky-600 hover:underline cursor-pointer">Data Guru & Tendik</button>
              <span>/</span>
              <span class="text-sky-700 font-bold">Detail Profil PTK</span>
            </div>
            <h1 class="text-lg sm:text-2xl font-black text-gray-900 leading-tight">
              {{ selectedTeacher.nama_lengkap }}
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              Informasi lengkap profil pendidik, amanah tugas jabatan, wali kelas, serta beban kegiatan mengajar.
            </p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2.5 self-end sm:self-center">
          <button
            @click="closeDetail"
            type="button"
            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition cursor-pointer"
          >
            Kembali
          </button>
          <button
            @click="editFromDetail(selectedTeacher)"
            type="button"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl shadow-md shadow-amber-500/25 transition cursor-pointer active:scale-95"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            <span>Edit Profil Guru</span>
          </button>
        </div>
      </div>

      <!-- Hero Profile Header Card -->
      <div class="bg-gradient-to-r from-sky-900 via-slate-900 to-sky-950 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="flex flex-col md:flex-row items-start md:items-center gap-6 relative z-10">
          <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl overflow-hidden bg-white/10 border-2 border-white/20 shadow-lg shrink-0 flex items-center justify-center">
            <img
              v-if="selectedTeacher.foto_url || selectedTeacher.foto_path"
              :src="getPhotoUrl(selectedTeacher.foto_url, selectedTeacher.foto_path)"
              :alt="selectedTeacher.nama_lengkap"
              class="w-full h-full object-cover"
            />
            <span v-else class="text-3xl font-black text-sky-200 uppercase">
              {{ getInitials(selectedTeacher.nama_lengkap) }}
            </span>
          </div>

          <div class="space-y-2 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <span v-if="selectedTeacher.jabatan" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-sky-500/30 text-sky-200 border border-sky-400/30 shadow-xs">
                {{ selectedTeacher.jabatan }}
              </span>
              <span v-if="selectedTeacher.tugas_tambahan" class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/25 text-amber-200 border border-amber-400/30">
                {{ selectedTeacher.tugas_tambahan }}
              </span>
              <span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                {{ selectedTeacher.status }}
              </span>
            </div>

            <h2 class="text-xl sm:text-3xl font-black text-white leading-tight">
              {{ selectedTeacher.nama_lengkap }}
            </h2>

            <div class="flex flex-wrap items-center gap-y-1 gap-x-4 text-xs text-sky-200 font-mono">
              <span v-if="selectedTeacher.nip">NIP: {{ selectedTeacher.nip }}</span>
              <span v-else>NIP: -</span>
              <span>•</span>
              <span>NIK: {{ selectedTeacher.nik }}</span>
              <span v-if="selectedTeacher.nuptk">• NUPTK: {{ selectedTeacher.nuptk }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Main 4-Grid Cards Details -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Card 1: Penugasan Tugas Pokok, Wali Kelas & Mapel -->
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
          <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
              <span class="w-6 h-6 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center text-xs font-black">1</span>
              Amanah Jabatan & Tugas Mengajar
            </h3>
            <span class="text-[10px] uppercase font-bold text-sky-600 bg-sky-50 px-2.5 py-1 rounded-lg">KBM & Akademik</span>
          </div>

          <div class="space-y-3.5 text-xs">
            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between">
              <span class="text-gray-500 font-medium">Jabatan Utama</span>
              <span class="font-bold text-gray-900 text-sm">{{ selectedTeacher.jabatan || '-' }}</span>
            </div>

            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between">
              <span class="text-gray-500 font-medium">Tugas Tambahan</span>
              <span class="font-bold text-amber-700">{{ selectedTeacher.tugas_tambahan || '-' }}</span>
            </div>

            <!-- Amanah Wali Kelas -->
            <div class="p-3.5 bg-purple-50/60 rounded-xl border border-purple-100">
              <span class="text-purple-700 font-bold block mb-1">Amanah Wali Kelas:</span>
              <div v-if="selectedTeacher.homeroom_classes && selectedTeacher.homeroom_classes.length > 0" class="flex flex-wrap gap-2 mt-1">
                <span v-for="c in selectedTeacher.homeroom_classes" :key="c.id" class="px-3 py-1 bg-purple-600 text-white rounded-lg font-bold shadow-xs">
                  {{ c.nama }} (Tingkat {{ c.tingkat }})
                </span>
              </div>
              <div v-else class="text-gray-500 italic">
                Bukan wali kelas (Tenaga pengajar mata pelajaran / staf tata usaha).
              </div>
            </div>

            <!-- Penugasan Mata Pelajaran / Tematik -->
            <div class="p-3.5 bg-sky-50/60 rounded-xl border border-sky-100">
              <span class="text-sky-800 font-bold block mb-1">Mata Pelajaran & Tematik yang Diampu:</span>
              <div v-if="selectedTeacher.assignments && selectedTeacher.assignments.length > 0" class="space-y-1.5 mt-2">
                <div v-for="asg in selectedTeacher.assignments" :key="asg.id" class="flex items-center justify-between bg-white px-3 py-2 rounded-lg border border-sky-200/60 text-xs">
                  <div class="font-bold text-gray-800">
                    {{ asg.subject?.nama }}
                    <span class="font-mono text-[10px] text-sky-600 ml-1">({{ asg.subject?.kode }})</span>
                  </div>
                  <span class="text-[11px] font-semibold text-purple-700 bg-purple-50 px-2 py-0.5 rounded-md border border-purple-200">
                    {{ asg.class?.nama ?? 'Semua Rombel' }}
                  </span>
                </div>
              </div>
              <div v-else class="text-gray-500 italic">
                Belum ada alokasi mata pelajaran terdaftar.
              </div>
            </div>

            <div class="flex items-center justify-between pt-1 text-gray-500">
              <span>Beban Jam Mengajar Mingguan:</span>
              <span class="font-bold text-gray-900 font-mono">{{ selectedTeacher.jam_mengajar_per_minggu ? `${selectedTeacher.jam_mengajar_per_minggu} Jam / Minggu` : '24 Jam / Minggu (Standar)' }}</span>
            </div>
          </div>
        </div>

        <!-- Card 2: Status Kepegawaian & Kualifikasi -->
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
          <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
              <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">2</span>
              Data Kepegawaian & Kualifikasi
            </h3>
            <span class="text-[10px] uppercase font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-lg">Yayasan & Dinas</span>
          </div>

          <div class="grid grid-cols-2 gap-3.5 text-xs">
            <div class="p-3 bg-gray-50 rounded-xl">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Jenis PTK</span>
              <div class="font-bold text-gray-800 uppercase mt-0.5">{{ selectedTeacher.jenis_ptk?.replace('_', ' ') }}</div>
            </div>

            <div class="p-3 bg-gray-50 rounded-xl">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Status Ikatan Kerja</span>
              <div class="font-bold text-gray-800 uppercase mt-0.5">{{ selectedTeacher.status_kepegawaian ?? 'GTY' }}</div>
            </div>

            <div class="p-3 bg-gray-50 rounded-xl">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Masa Kerja</span>
              <div class="font-bold text-gray-800 mt-0.5">{{ calculateTenure(selectedTeacher.tgl_mulai_tugas) }}</div>
            </div>

            <div class="p-3 bg-gray-50 rounded-xl">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">TMT Mulai Tugas</span>
              <div class="font-bold text-gray-800 mt-0.5">{{ formatDateIndo(selectedTeacher.tgl_mulai_tugas) }}</div>
            </div>

            <div class="p-3 bg-gray-50 rounded-xl">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Pendidikan Terakhir</span>
              <div class="font-bold text-gray-800 mt-0.5">{{ selectedTeacher.education?.nama || selectedTeacher.pendidikan_terakhir || 'S1 (Sarjana)' }}</div>
            </div>

            <div class="p-3 bg-gray-50 rounded-xl">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Jurusan / Program Studi</span>
              <div class="font-bold text-gray-800 mt-0.5">{{ selectedTeacher.jurusan_prodi || 'Pendidikan' }}</div>
            </div>
          </div>

          <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-100 text-xs flex items-center justify-between">
            <span class="text-gray-500 font-medium">Sertifikasi Pendidik</span>
            <span class="font-bold uppercase" :class="selectedTeacher.status_sertifikasi === 'sudah' ? 'text-emerald-700' : 'text-gray-600'">
              {{ selectedTeacher.status_sertifikasi === 'sudah' ? 'Tersertifikasi' : 'Belum Sertifikasi' }}
            </span>
          </div>
        </div>

        <!-- Card 3: Biodata Pribadi & Alamat Domisili -->
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
          <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
              <span class="w-6 h-6 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-black">3</span>
              Biodata Pribadi & Domisili
            </h3>
            <span class="text-[10px] uppercase font-bold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-lg">Identitas</span>
          </div>

          <div class="grid grid-cols-2 gap-3.5 text-xs">
            <div class="p-3 bg-gray-50 rounded-xl">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">NIK (KTP)</span>
              <div class="font-mono font-bold text-gray-800 mt-0.5">{{ selectedTeacher.nik }}</div>
            </div>

            <div class="p-3 bg-gray-50 rounded-xl">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Jenis Kelamin</span>
              <div class="font-bold text-gray-800 mt-0.5">{{ selectedTeacher.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</div>
            </div>

            <div class="p-3 bg-gray-50 rounded-xl col-span-2">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Tempat & Tanggal Lahir</span>
              <div class="font-medium text-gray-800 mt-0.5">{{ selectedTeacher.tempat_lahir || 'Purwokerto' }}, {{ formatDateIndo(selectedTeacher.tgl_lahir) }}</div>
            </div>

            <div class="p-3 bg-gray-50 rounded-xl col-span-2">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Alamat Tempat Tinggal</span>
              <div class="font-medium text-gray-800 mt-0.5">{{ selectedTeacher.alamat_jalan }}, {{ selectedTeacher.kab_kota || 'Banyumas' }}</div>
            </div>
          </div>
        </div>

        <!-- Card 4: Kontak Aktif & Akses SIM-BIM -->
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
          <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
              <span class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-black">4</span>
              Kontak & Akses Sistem SIM-BIM
            </h3>
            <span class="text-[10px] uppercase font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg">Login Akun</span>
          </div>

          <div class="space-y-3 text-xs">
            <div class="p-3.5 bg-gray-50 rounded-xl flex items-center justify-between">
              <span class="text-gray-500 font-medium">No. WhatsApp / HP</span>
              <span class="font-mono font-bold text-gray-900">{{ selectedTeacher.no_hp }}</span>
            </div>

            <div class="p-3.5 bg-gray-50 rounded-xl flex items-center justify-between">
              <span class="text-gray-500 font-medium">Alamat Email</span>
              <span class="font-medium text-gray-900">{{ selectedTeacher.email || '-' }}</span>
            </div>

            <div class="p-3.5 bg-indigo-50/60 rounded-xl border border-indigo-100 space-y-2">
              <span class="text-indigo-900 font-bold block">Status Akun Login SIM-BIM:</span>
              <div class="flex items-center justify-between text-xs">
                <span class="text-gray-600">Username:</span>
                <span class="font-mono font-bold text-indigo-700">{{ selectedTeacher.user?.username || selectedTeacher.nip || selectedTeacher.nik }}</span>
              </div>
              <div class="flex items-center justify-between text-xs">
                <span class="text-gray-600">Email Akun:</span>
                <span class="font-medium text-gray-800">{{ selectedTeacher.user?.email || selectedTeacher.email || '-' }}</span>
              </div>
              <div class="flex items-center justify-between text-xs pt-1 border-t border-indigo-200/50">
                <span class="text-gray-600">Status Akses:</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800">
                  {{ selectedTeacher.user?.status ?? 'Aktif' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../services/api';
import { confirmDeleteWithKeyword, showSuccess, showError } from '../services/swal';
import { calculateTenure, formatDateIndo, toInputDate } from '../utils/date';

// View Mode: 'list' (tabel), 'form' (halaman formulir penuh), atau 'detail' (halaman biodata penuh)
const currentView = ref<'list' | 'form' | 'detail'>('list');

const teachers = ref<any[]>([]);
const search = ref('');
const filterJenisPtk = ref('');
const loading = ref(false);
const saving = ref(false);
const exporting = ref(false);
const selectedTeacher = ref<any>(null);

// Photo Upload State
const fileInputRef = ref<HTMLInputElement | null>(null);
const photoFile = ref<File | null>(null);
const photoPreview = ref<string>('');

const getPhotoUrl = (url?: string, path?: string) => {
  if (url) return url;
  if (path) {
    return path.startsWith('http') ? path : `/storage/${path}`;
  }
  return '';
};

const getInitials = (name?: string) => {
  if (!name) return '?';
  const parts = name.trim().split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
};

const handlePhotoChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    if (file.size > 5 * 1024 * 1024) {
      showError('File Terlalu Besar', 'Ukuran foto maksimal 5 MB.');
      target.value = '';
      return;
    }
    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
  }
};

const clearPhoto = () => {
  photoFile.value = null;
  photoPreview.value = '';
  if (fileInputRef.value) fileInputRef.value.value = '';
};

// Form State
const isEditing = ref(false);
const editingId = ref<number | null>(null);
const form = ref<any>({
  nik: '',
  nip: '',
  nama_lengkap: '',
  jenis_kelamin: 'L',
  tempat_lahir: 'Purwokerto',
  tgl_lahir: '1990-01-01',
  agama: 1,
  alamat_jalan: '',
  kab_kota: 'Banyumas',
  no_hp: '',
  email: '',
  jenis_ptk: 'guru_kelas',
  jabatan: '',
  tugas_tambahan: '',
  status_kepegawaian: 'gty',
  status: 'aktif',
  tgl_mulai_tugas: new Date().toISOString().split('T')[0],
  create_user_account: true,
});

const fetchTeachers = async () => {
  loading.value = true;
  try {
    const params: any = {};
    if (search.value) params.search = search.value;
    if (filterJenisPtk.value) params.jenis_ptk = filterJenisPtk.value;

    const res = await api.get('/teachers', { params });
    if (res.data.success) {
      teachers.value = res.data.data;
      if (selectedTeacher.value) {
        const updated = teachers.value.find((t: any) => t.id === selectedTeacher.value.id);
        if (updated) selectedTeacher.value = updated;
      }
    }
  } catch (err) {
    console.error('Gagal mengambil data guru', err);
  } finally {
    loading.value = false;
  }
};

const exportToExcel = async () => {
  exporting.value = true;
  try {
    const params: any = {};
    if (search.value) params.search = search.value;
    if (filterJenisPtk.value) params.jenis_ptk = filterJenisPtk.value;

    const res = await api.get('/teachers/export', {
      params,
      responseType: 'blob',
    });

    const blob = new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `Master_Data_Guru_Tendik_${new Date().toISOString().slice(0, 10)}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    showSuccess('Berhasil!', 'File Excel data guru & tendik berhasil diunduh.');
  } catch (err: any) {
    showError('Gagal Export Excel!', err, 'Terjadi kendala saat mengunduh berkas Excel.');
  } finally {
    exporting.value = false;
  }
};

// ==============================================================
// DETAIL VIEW TRANSITIONS (Halaman Biodata Penuh Menimpa Tabel)
// ==============================================================
const openDetail = (teacher: any) => {
  selectedTeacher.value = teacher;
  currentView.value = 'detail';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const closeDetail = () => {
  selectedTeacher.value = null;
  currentView.value = 'list';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const editFromDetail = (teacher: any) => {
  openEditForm(teacher);
};

// ==============================================================
// FORM VIEW TRANSITIONS (Halaman Form Baru Menimpa Tabel)
// ==============================================================
const openCreateForm = () => {
  isEditing.value = false;
  editingId.value = null;
  photoFile.value = null;
  photoPreview.value = '';
  if (fileInputRef.value) fileInputRef.value.value = '';
  form.value = {
    nik: '',
    nip: '',
    nama_lengkap: '',
    jenis_kelamin: 'L',
    tempat_lahir: 'Purwokerto',
    tgl_lahir: '1990-01-01',
    agama: 1,
    alamat_jalan: '',
    kab_kota: 'Banyumas',
    no_hp: '',
    email: '',
    jenis_ptk: 'guru_kelas',
    jabatan: '',
    tugas_tambahan: '',
    status_kepegawaian: 'gty',
    status: 'aktif',
    tgl_mulai_tugas: new Date().toISOString().split('T')[0],
    create_user_account: true,
  };
  currentView.value = 'form';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const openEditForm = (teacher: any) => {
  isEditing.value = true;
  editingId.value = teacher.id;
  photoFile.value = null;
  photoPreview.value = getPhotoUrl(teacher.foto_url, teacher.foto_path);
  if (fileInputRef.value) fileInputRef.value.value = '';
  form.value = {
    nik: teacher.nik || '',
    nip: teacher.nip || '',
    nama_lengkap: teacher.nama_lengkap || '',
    jenis_kelamin: teacher.jenis_kelamin || 'L',
    tempat_lahir: teacher.tempat_lahir || 'Purwokerto',
    tgl_lahir: toInputDate(teacher.tgl_lahir),
    agama: teacher.agama || 1,
    alamat_jalan: teacher.alamat_jalan || '',
    kab_kota: teacher.kab_kota || 'Banyumas',
    no_hp: teacher.no_hp || '',
    email: teacher.email || '',
    jenis_ptk: teacher.jenis_ptk || 'guru_kelas',
    jabatan: teacher.jabatan || '',
    tugas_tambahan: teacher.tugas_tambahan || '',
    status_kepegawaian: teacher.status_kepegawaian || 'gty',
    status: teacher.status || 'aktif',
    tgl_mulai_tugas: toInputDate(teacher.tgl_mulai_tugas) || new Date().toISOString().split('T')[0],
  };
  currentView.value = 'form';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const closeForm = () => {
  if (selectedTeacher.value) {
    currentView.value = 'detail';
  } else {
    currentView.value = 'list';
  }
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const submitForm = async () => {
  saving.value = true;
  try {
    const payload: any = { ...form.value };
    if (!payload.nip) delete payload.nip;
    if (!payload.email) delete payload.email;

    const formData = new FormData();
    for (const key in payload) {
      if (payload[key] !== null && payload[key] !== undefined) {
        if (key === 'create_user_account') {
          formData.append('create_user_account', payload[key] ? '1' : '0');
        } else {
          formData.append(key, payload[key]);
        }
      }
    }

    if (photoFile.value) {
      formData.append('foto', photoFile.value);
    }

    if (isEditing.value && editingId.value) {
      formData.delete('create_user_account');
      formData.append('_method', 'PUT');
      const res = await api.post(`/teachers/${editingId.value}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      if (res.data.success) {
        showSuccess('Berhasil!', 'Data guru berhasil diperbarui.');
        closeForm();
        fetchTeachers();
      }
    } else {
      const res = await api.post('/teachers', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      if (res.data.success) {
        showSuccess('Berhasil!', 'Data guru baru berhasil ditambahkan.');
        closeForm();
        fetchTeachers();
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err, 'Terjadi kesalahan saat menyimpan data guru.');
  } finally {
    saving.value = false;
  }
};

const handleDeleteTeacher = async (teacher: any) => {
  const confirmed = await confirmDeleteWithKeyword(`guru "${teacher.nama_lengkap}" (NIP: ${teacher.nip || teacher.nik})`);
  if (!confirmed) return;

  try {
    const res = await api.delete(`/teachers/${teacher.id}`);
    if (res.data.success) {
      showSuccess('Berhasil Dihapus!', res.data.message);
      fetchTeachers();
    }
  } catch (err: any) {
    showError('Gagal Menghapus!', err, 'Data guru tidak dapat dihapus.');
  }
};

onMounted(() => {
  fetchTeachers();
});
</script>
