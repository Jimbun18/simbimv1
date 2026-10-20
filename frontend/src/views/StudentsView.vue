<template>
  <div class="space-y-5 sm:space-y-6">
    <!-- ============================================================== -->
    <!-- VIEW 1: DAFTAR TABEL SISWA (LIST VIEW)                         -->
    <!-- ============================================================== -->
    <div v-if="currentView === 'list'" class="space-y-5 sm:space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm">
        <div>
          <h1 class="text-lg sm:text-xl font-bold text-gray-900 leading-tight">Manajemen Data Siswa</h1>
          <p class="text-xs text-gray-500 mt-0.5">Daftar seluruh siswa aktif, identitas lengkap Dapodik, rombel kelas, dan kontak orang tua/wali murid.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button
            @click="exportToExcel"
            :disabled="exporting"
            class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-xl border border-emerald-200 transition disabled:opacity-50"
            title="Export Data Siswa ke Excel"
          >
            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span v-if="exporting">Mengekspor...</span>
            <span v-else>Export Excel</span>
          </button>
          <router-link
            v-if="auth.isAdmin"
            to="/excel-imports"
            class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-xl border border-emerald-200 transition"
          >
            Import Dapodik / Excel
          </router-link>
          <button
            v-if="auth.isAdmin"
            @click="openCreateForm"
            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/20 transition active:scale-95"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Tambah Siswa Baru</span>
          </button>
        </div>
      </div>

      <!-- Filter & Search Toolbar -->
      <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
        <div class="flex-1 relative">
          <input
            v-model="search"
            @input="fetchStudents"
            type="text"
            placeholder="Cari NIS, NISN, atau Nama Siswa..."
            class="w-full px-4 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none transition"
          />
        </div>

        <div class="flex flex-wrap sm:nowrap gap-2">
          <select
            v-model="filterClass"
            @change="fetchStudents"
            class="w-full sm:w-auto px-3 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-gray-700"
          >
            <option value="">Semua Rombel</option>
            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nama_kelas || c.nama }}</option>
          </select>

          <select
            v-model="filterStatus"
            @change="fetchStudents"
            class="w-full sm:w-auto px-3 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-gray-700"
          >
            <option value="aktif">Status: Aktif</option>
            <option value="lulus">Status: Lulus</option>
            <option value="pindah">Status: Pindah</option>
            <option value="mutasi_keluar">Status: Mutasi Keluar</option>
            <option value="drop_out">Status: Drop Out</option>
          </select>
        </div>
      </div>

      <!-- Table Siswa -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-gray-600 min-w-[850px]">
            <thead class="bg-gray-50 text-[11px] font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
              <tr>
                <th class="py-3.5 px-4 w-12 text-center">No</th>
                <th class="py-3.5 px-4 w-14 text-center">Foto</th>
                <th class="py-3.5 px-4">NIS / NISN</th>
                <th class="py-3.5 px-4">Nama Lengkap & NIK</th>
                <th class="py-3.5 px-4 text-center">L/P</th>
                <th class="py-3.5 px-4">Rombel Kelas</th>
                <th class="py-3.5 px-4">TTL & Usia</th>
                <th class="py-3.5 px-4">Orang Tua / Wali</th>
                <th class="py-3.5 px-4 text-center">Status</th>
                <th class="py-3.5 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="loading">
                <td colspan="10" class="text-center py-8 text-gray-400">Memuat data kesiswaan...</td>
              </tr>
              <tr v-else-if="students.length === 0">
                <td colspan="10" class="text-center py-8 text-gray-400">Tidak ada data siswa yang cocok dengan filter.</td>
              </tr>
              <tr v-for="(s, idx) in students" :key="s.id" class="hover:bg-gray-50/70 transition">
                <td class="py-3 px-4 text-gray-400 font-mono text-center">{{ idx + 1 }}</td>
                <td class="py-2.5 px-4 text-center">
                  <div class="w-9 h-9 rounded-full overflow-hidden bg-gray-100 border border-gray-200 flex items-center justify-center mx-auto shadow-xs shrink-0">
                    <img
                      v-if="s.foto_url || s.foto_path"
                      :src="getPhotoUrl(s.foto_url, s.foto_path)"
                      :alt="s.nama_lengkap"
                      class="w-full h-full object-cover"
                      @error="(e: any) => e.target.style.display = 'none'"
                    />
                    <span v-else class="text-[11px] font-bold text-gray-500 uppercase">
                      {{ getInitials(s.nama_lengkap) }}
                    </span>
                  </div>
                </td>
                <td class="py-3 px-4 font-mono font-medium text-gray-900 whitespace-nowrap">
                  <div>{{ s.nis }}</div>
                  <div class="text-[10px] text-gray-400">NISN: {{ s.nisn ?? '-' }}</div>
                </td>
                <td class="py-3 px-4">
                  <div class="font-bold text-gray-900">{{ s.nama_lengkap }}</div>
                  <div class="text-[10px] text-gray-400 font-mono">NIK: {{ s.nik ?? '-' }}</div>
                </td>
                <td class="py-3 px-4 text-center">
                  <span :class="s.jenis_kelamin === 'L' ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-rose-50 text-rose-700 border-rose-200'" class="px-2 py-0.5 rounded-full border text-[10px] font-bold">
                    {{ s.jenis_kelamin }}
                  </span>
                </td>
                <td class="py-3 px-4 font-medium text-emerald-800 whitespace-nowrap">
                  <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-100 font-semibold text-[11px]">
                    {{ s.current_class?.nama || s.school_class?.nama || s.class?.nama || s.class?.nama_kelas || '-' }}
                  </span>
                </td>
                <td class="py-3 px-4 whitespace-nowrap">
                  <div class="font-medium text-gray-900 text-xs">{{ s.tempat_lahir || '-' }}</div>
                  <div class="text-[10px] text-gray-400">{{ formatDateIndo(s.tgl_lahir) }}</div>
                </td>
                <td class="py-3 px-4 whitespace-nowrap">
                  <div v-if="getStudentParentName(s)" class="font-medium text-gray-900">
                    {{ getStudentParentName(s) }}
                  </div>
                  <div v-else class="text-gray-400 italic text-[11px]">-</div>
                  <div class="text-[10px] text-gray-400">{{ s.no_hp_siswa || getStudentParentPhone(s) || '-' }}</div>
                </td>
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                    {{ s.status }}
                  </span>
                </td>
                <td class="py-3 px-4 text-right whitespace-nowrap space-x-1.5">
                  <button
                    @click="openDetail(s)"
                    class="px-2.5 py-1 bg-gray-50 hover:bg-emerald-50 text-emerald-700 border border-gray-200 hover:border-emerald-200 rounded-lg text-xs font-semibold transition"
                  >
                    Detail
                  </button>
                  <button
                    v-if="auth.isAdmin"
                    @click="openEditForm(s)"
                    class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-semibold transition"
                  >
                    Edit
                  </button>
                  <button
                    v-if="auth.isAdmin"
                    @click="handleDeleteStudent(s)"
                    class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-semibold transition"
                  >
                    Hapus
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- VIEW 2: HALAMAN FORMULIR SISWA LENGKAP DAPODIK (TAMBAH & EDIT) -->
    <!-- ============================================================== -->
    <div v-else-if="currentView === 'form'" class="space-y-6 animate-in fade-in duration-200">
      <!-- Top Navigation & Action Banner -->
      <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
          <button
            @click="closeForm"
            type="button"
            class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center justify-center shrink-0 shadow-xs group"
            title="Kembali ke Daftar Siswa"
          >
            <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
              <span>Kesiswaan</span>
              <span>/</span>
              <button type="button" @click="closeForm" class="hover:text-emerald-600 hover:underline">Data Siswa</button>
              <span>/</span>
              <span class="text-emerald-700 font-bold">{{ isEditing ? 'Edit Siswa (Format Dapodik)' : 'Tambah Siswa Baru (Format Dapodik)' }}</span>
            </div>
            <h1 class="text-lg sm:text-2xl font-black text-gray-900 leading-tight">
              {{ isEditing ? `Edit Data Siswa: ${form.nama_lengkap || '...'}` : 'Formulir Pendaftaran Siswa (Standar Dapodik Kemdikbud)' }}
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              {{ isEditing ? 'Perbarui seluruh field biodata kesiswaan sesuai kolom resmi Dapodik Kemdikbud.' : 'Lengkapi formulir terpadu sesuai standar Dapodik untuk menambahkan siswa baru ke sistem.' }}
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
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/25 transition disabled:opacity-50 active:scale-95"
          >
            <svg v-if="saving" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ saving ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Siswa Baru') }}</span>
          </button>
        </div>
      </div>

      <!-- Main Form Grid: 12 Cols -->
      <form @submit.prevent="submitForm" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- LEFT COLUMN: Foto Profil & ID Card Preview (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
          <!-- Photo Upload Card -->
          <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-gray-900">Pas Foto Siswa</h3>
              <span class="text-[10px] text-gray-400 font-semibold uppercase">Opsional</span>
            </div>

            <div class="flex flex-col items-center justify-center p-6 bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl group hover:border-emerald-400 transition">
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
                <label class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold rounded-xl border border-emerald-200 cursor-pointer transition shadow-xs">
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

          <!-- Live ID Card Preview Card -->
          <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-950 p-5 rounded-2xl text-white shadow-lg space-y-4">
            <div class="flex items-center justify-between text-emerald-400 text-xs font-bold">
              <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                KARTU SISWA (PREVIEW)
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
                <span v-else class="text-sm font-black text-emerald-300 uppercase">
                  {{ getInitials(form.nama_lengkap || 'Siswa') }}
                </span>
              </div>
              <div class="min-w-0 flex-1">
                <h4 class="text-sm font-bold truncate text-white">
                  {{ form.nama_lengkap || 'Nama Lengkap Siswa' }}
                </h4>
                <p class="text-[11px] font-mono text-emerald-300">
                  NIS: {{ form.nis || '-------' }} • NISN: {{ form.nisn || '-' }}
                </p>
                <div class="flex items-center gap-1.5 mt-1">
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-white/15 text-slate-200">
                    {{ getSelectedClassName() }}
                  </span>
                  <span :class="form.jenis_kelamin === 'L' ? 'bg-sky-500/20 text-sky-300' : 'bg-rose-500/20 text-rose-300'" class="px-2 py-0.5 rounded-full text-[9px] font-bold">
                    {{ form.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
                  </span>
                </div>
              </div>
            </div>

            <div class="pt-3 border-t border-white/10 text-[11px] text-slate-300 flex justify-between items-center">
              <span>Status Kesiswaan:</span>
              <span class="font-bold uppercase tracking-wider text-emerald-400">{{ form.status }}</span>
            </div>
          </div>

          <!-- Quick Navigation Card -->
          <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm space-y-2 text-xs">
            <div class="font-bold text-gray-800 pb-1 border-b border-gray-100 flex items-center justify-between">
              <span>Formulir Kolom Dapodik</span>
              <span class="text-[10px] text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md font-bold">6 Bagian</span>
            </div>
            <ul class="space-y-1.5 text-gray-500">
              <li class="flex items-center gap-2 hover:text-emerald-700 cursor-pointer py-1">
                <span class="w-5 h-5 rounded-md bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-[10px]">1</span>
                <span>Identitas Pokok Siswa</span>
              </li>
              <li class="flex items-center gap-2 hover:text-emerald-700 cursor-pointer py-1">
                <span class="w-5 h-5 rounded-md bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-[10px]">2</span>
                <span>Rombel & Status Pendaftaran</span>
              </li>
              <li class="flex items-center gap-2 hover:text-emerald-700 cursor-pointer py-1">
                <span class="w-5 h-5 rounded-md bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-[10px]">3</span>
                <span>Alamat Domisili & Transportasi</span>
              </li>
              <li class="flex items-center gap-2 hover:text-emerald-700 cursor-pointer py-1">
                <span class="w-5 h-5 rounded-md bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-[10px]">4</span>
                <span>Orang Tua & Wali Murid</span>
              </li>
              <li class="flex items-center gap-2 hover:text-emerald-700 cursor-pointer py-1">
                <span class="w-5 h-5 rounded-md bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-[10px]">5</span>
                <span>Data Fisik & Kesehatan</span>
              </li>
              <li class="flex items-center gap-2 hover:text-emerald-700 cursor-pointer py-1">
                <span class="w-5 h-5 rounded-md bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-[10px]">6</span>
                <span>Bantuan & Rekening Bank</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- RIGHT COLUMN: 6 Form Sections Matching Dapodik Columns (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
          
          <!-- SEKSI 1: IDENTITAS POKOK SISWA (Dapodik Kolom 1-10) -->
          <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                  <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">1</span>
                  Identitas Pokok Siswa (Dapodik)
                </h3>
                <p class="text-xs text-gray-500 mt-0.5 ml-8">Nama, NIPD/NIS, NISN, NIK, No. KK, Akta Lahir, Tempat/Tgl Lahir, dan Agama.</p>
              </div>
              <span class="text-[10px] text-rose-500 font-semibold">* Wajib diisi</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Lengkap Siswa <span class="text-rose-500">*</span></label>
                <input
                  v-model="form.nama_lengkap"
                  type="text"
                  required
                  placeholder="Nama sesuai Akta Lahir / KK"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold text-gray-800 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Panggilan</label>
                <input
                  v-model="form.nama_panggilan"
                  type="text"
                  placeholder="Nama sapaan sehari-hari"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">NIPD / Nomor Induk Siswa (NIS) <span class="text-rose-500">*</span></label>
                <input
                  v-model="form.nis"
                  type="text"
                  required
                  placeholder="Contoh: BIM0001"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono font-medium focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">NISN (10 Digit)</label>
                <input
                  v-model="form.nisn"
                  type="text"
                  maxlength="10"
                  placeholder="Contoh: 3163985785"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
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
                    <span>👦</span>
                    <span>L</span>
                  </button>
                  <button
                    type="button"
                    @click="form.jenis_kelamin = 'P'"
                    :class="form.jenis_kelamin === 'P' ? 'bg-rose-50 border-rose-400 text-rose-700 font-bold shadow-xs' : 'bg-gray-50 border-gray-200 text-gray-600'"
                    class="py-2 px-3 rounded-xl border text-xs flex items-center justify-center gap-1.5 transition"
                  >
                    <span>👧</span>
                    <span>P</span>
                  </button>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">NIK Siswa (16 Digit)</label>
                <input
                  v-model="form.nik"
                  type="text"
                  maxlength="16"
                  placeholder="3302..."
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Nomor Kartu Keluarga (No. KK)</label>
                <input
                  v-model="form.no_kk"
                  type="text"
                  maxlength="16"
                  placeholder="3302..."
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">No. Registrasi Akta Lahir</label>
                <input
                  v-model="form.no_akta_lahir"
                  type="text"
                  placeholder="Contoh: 3302-LT-..."
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Tempat Lahir <span class="text-rose-500">*</span></label>
                <input
                  v-model="form.tempat_lahir"
                  type="text"
                  required
                  placeholder="Contoh: Banyumas"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Tanggal Lahir <span class="text-rose-500">*</span></label>
                <input
                  v-model="form.tgl_lahir"
                  type="date"
                  required
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
                <span v-if="studentAge" class="text-[10px] text-emerald-600 font-semibold block mt-1">
                  Usia: {{ studentAge }}
                </span>
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Agama & Kepercayaan <span class="text-rose-500">*</span></label>
                <select
                  v-model="form.agama"
                  required
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white transition"
                >
                  <option v-for="r in religions" :key="r.id" :value="r.id">{{ r.nama }}</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Kewarganegaraan</label>
                <select
                  v-model="form.kewarganegaraan"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white transition"
                >
                  <option value="WNI">WNI (Warga Negara Indonesia)</option>
                  <option value="WNA">WNA (Warga Negara Asing)</option>
                </select>
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Anak Ke-berapa</label>
                <input
                  v-model="form.anak_ke"
                  type="number"
                  min="1"
                  placeholder="1"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Jumlah Saudara Kandung</label>
                <input
                  v-model="form.jumlah_saudara"
                  type="number"
                  min="0"
                  placeholder="0"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Kebutuhan Khusus Siswa (Jika Ada)</label>
              <input
                v-model="form.kebutuhan_khusus"
                type="text"
                placeholder="Kosongkan jika tidak ada / Normal"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>
          </div>

          <!-- SEKSI 2: PENEMPATAN ROMBEL & STATUS SEKOLAH (Dapodik Kolom 22, 28-30, 39) -->
          <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <div class="border-b border-gray-100 pb-3">
              <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">2</span>
                Rombel Saat Ini & Riwayat Pendaftaran
              </h3>
              <p class="text-xs text-gray-500 mt-0.5 ml-8">Rombel Dapodik (Ibnu Sina, Al Khawarizmi, Al Ghazali, An Nawawi), tanggal masuk, dan asal sekolah.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Rombel Saat Ini (Kelas)</label>
                <select
                  v-model="form.current_class_id"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white transition"
                >
                  <option :value="null">-- Belum Masuk Rombel --</option>
                  <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nama_kelas || c.nama }}</option>
                </select>
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Tanggal Masuk / Bergabung <span class="text-rose-500">*</span></label>
                <input
                  v-model="form.tgl_masuk"
                  type="date"
                  required
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Status Kesiswaan <span class="text-rose-500">*</span></label>
                <select
                  v-model="form.status"
                  required
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white transition"
                >
                  <option value="aktif">Aktif</option>
                  <option value="lulus">Lulus</option>
                  <option value="mutasi_keluar">Mutasi Keluar</option>
                  <option value="drop_out">Drop Out</option>
                  <option value="meninggal">Meninggal</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Sekolah Asal (TK / PAUD)</label>
                <input
                  v-model="form.asal_sekolah_tk"
                  type="text"
                  placeholder="Contoh: TK BINA INSAN MANDIRI SCHOOL"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Jenis Pendaftaran</label>
                <select
                  v-model="form.jenis_pendaftaran"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white transition"
                >
                  <option value="baru">Siswa Baru</option>
                  <option value="pindahan">Siswa Pindahan</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">SKHUN (Jika Ada)</label>
                <input
                  v-model="form.skhun"
                  type="text"
                  placeholder="Nomor SKHUN"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">No. Peserta Ujian Nasional</label>
                <input
                  v-model="form.no_peserta_un"
                  type="text"
                  placeholder="Nomor Peserta UN"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">No. Seri Ijazah</label>
                <input
                  v-model="form.no_seri_ijazah"
                  type="text"
                  placeholder="Nomor Seri Ijazah"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>
            </div>
          </div>

          <!-- SEKSI 3: ALAMAT DOMISILI & TRANSPORTASI (Dapodik Kolom 11-21, 41) -->
          <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <div class="border-b border-gray-100 pb-3">
              <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">3</span>
                Alamat Domisili, Transportasi & Kontak
              </h3>
              <p class="text-xs text-gray-500 mt-0.5 ml-8">Alamat tempat tinggal, RT/RW, Dusun, Desa/Kelurahan, Kecamatan, Jarak ke Sekolah, dan No. HP.</p>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Alamat Jalan / Tempat Tinggal <span class="text-rose-500">*</span></label>
              <input
                v-model="form.alamat_jalan"
                type="text"
                required
                placeholder="Jl. / Gang / Blok / No. Rumah"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
              />
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">RT</label>
                <input
                  v-model="form.rt"
                  type="text"
                  placeholder="01"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">RW</label>
                <input
                  v-model="form.rw"
                  type="text"
                  placeholder="02"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Dusun</label>
                <input
                  v-model="form.dusun"
                  type="text"
                  placeholder="Nama Dusun"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Kode Pos</label>
                <input
                  v-model="form.kode_pos"
                  type="text"
                  maxlength="5"
                  placeholder="53144"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Kelurahan / Desa</label>
                <input
                  v-model="form.desa_kelurahan"
                  type="text"
                  placeholder="Contoh: Karangklesem"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Kecamatan</label>
                <input
                  v-model="form.kecamatan"
                  type="text"
                  placeholder="Contoh: Purwokerto Selatan"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Kabupaten / Kota <span class="text-rose-500">*</span></label>
                <input
                  v-model="form.kab_kota"
                  type="text"
                  required
                  placeholder="Contoh: Banyumas"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Jenis Tinggal</label>
                <select
                  v-model="form.tinggal_bersama"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white transition"
                >
                  <option value="orang_tua">Bersama Orang Tua</option>
                  <option value="wali">Bersama Wali</option>
                  <option value="asrama">Asrama / Pesantren</option>
                  <option value="kost">Kost</option>
                  <option value="lainnya">Lainnya</option>
                </select>
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Alat Transportasi</label>
                <select
                  v-model="form.transportasi"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white transition"
                >
                  <option :value="null">-- Pilih Transportasi --</option>
                  <option v-for="t in transports" :key="t.id" :value="t.id">{{ t.nama }}</option>
                </select>
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Jarak ke Sekolah (KM)</label>
                <input
                  v-model="form.jarak_ke_sekolah_km"
                  type="number"
                  step="0.1"
                  placeholder="Contoh: 1.5"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">No. HP Siswa / Kontak Utama</label>
                <input
                  v-model="form.no_hp_siswa"
                  type="text"
                  placeholder="081234567890"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Telepon Rumah</label>
                <input
                  v-model="form.telepon"
                  type="text"
                  placeholder="0281..."
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">E-Mail</label>
                <input
                  v-model="form.email"
                  type="email"
                  placeholder="nama@email.com"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Titik Lintang (Latitude)</label>
                <input
                  v-model="form.latitude"
                  type="text"
                  placeholder="-7.4243"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Titik Bujur (Longitude)</label>
                <input
                  v-model="form.longitude"
                  type="text"
                  placeholder="109.2301"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>
            </div>
          </div>

          <!-- SEKSI 4: DATA ORANG TUA & WALI (Dapodik Kolom 25-27) -->
          <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-5">
            <div class="border-b border-gray-100 pb-3">
              <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">4</span>
                Data Orang Tua & Wali (Dapodik)
              </h3>
              <p class="text-xs text-gray-500 mt-0.5 ml-8">Profil Ayah Kandung, Ibu Kandung, dan Wali Murid sesuai dokumen resmi.</p>
            </div>

            <!-- Sub-kartu: Data Ayah -->
            <div class="p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80 space-y-3">
              <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                  <span class="text-blue-600">👨</span> DATA AYAH KANDUNG
                </h4>
                <span class="text-[10px] text-slate-500">Kolom Y - AD</span>
              </div>
              
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Lengkap Ayah</label>
                  <input
                    v-model="form.nama_ayah"
                    type="text"
                    placeholder="Nama Lengkap Ayah"
                    class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs font-medium focus:ring-2 focus:ring-emerald-500 outline-none"
                  />
                </div>
                <div>
                  <label class="block font-semibold text-gray-700 text-xs mb-1">NIK Ayah (16 Digit)</label>
                  <input
                    v-model="form.nik_ayah"
                    type="text"
                    maxlength="16"
                    placeholder="3302..."
                    class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 outline-none"
                  />
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <label class="block font-semibold text-gray-700 text-xs mb-1">Jenjang Pendidikan Ayah</label>
                  <input
                    v-model="form.pendidikan_ayah"
                    type="text"
                    placeholder="Contoh: S1 / SMA"
                    class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                  />
                </div>
                <div>
                  <label class="block font-semibold text-gray-700 text-xs mb-1">Pekerjaan Ayah</label>
                  <input
                    v-model="form.pekerjaan_ayah"
                    type="text"
                    placeholder="Contoh: Wiraswasta / Karyawan"
                    class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                  />
                </div>
                <div>
                  <label class="block font-semibold text-gray-700 text-xs mb-1">No. HP / WA Ayah</label>
                  <input
                    v-model="form.no_hp_ayah"
                    type="text"
                    placeholder="081234567890"
                    class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 outline-none"
                  />
                </div>
              </div>
            </div>

            <!-- Sub-kartu: Data Ibu -->
            <div class="p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80 space-y-3">
              <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                  <span class="text-rose-500">👩</span> DATA IBU KANDUNG
                </h4>
                <span class="text-[10px] text-slate-500">Kolom AE - AJ</span>
              </div>
              
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Lengkap Ibu</label>
                  <input
                    v-model="form.nama_ibu"
                    type="text"
                    placeholder="Nama Lengkap Ibu"
                    class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs font-medium focus:ring-2 focus:ring-emerald-500 outline-none"
                  />
                </div>
                <div>
                  <label class="block font-semibold text-gray-700 text-xs mb-1">NIK Ibu (16 Digit)</label>
                  <input
                    v-model="form.nik_ibu"
                    type="text"
                    maxlength="16"
                    placeholder="3302..."
                    class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 outline-none"
                  />
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <label class="block font-semibold text-gray-700 text-xs mb-1">Jenjang Pendidikan Ibu</label>
                  <input
                    v-model="form.pendidikan_ibu"
                    type="text"
                    placeholder="Contoh: S1 / D3 / SMA"
                    class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                  />
                </div>
                <div>
                  <label class="block font-semibold text-gray-700 text-xs mb-1">Pekerjaan Ibu</label>
                  <input
                    v-model="form.pekerjaan_ibu"
                    type="text"
                    placeholder="Contoh: Ibu Rumah Tangga / Karyawan"
                    class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                  />
                </div>
                <div>
                  <label class="block font-semibold text-gray-700 text-xs mb-1">No. HP / WA Ibu</label>
                  <input
                    v-model="form.no_hp_ibu"
                    type="text"
                    placeholder="081234567890"
                    class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 outline-none"
                  />
                </div>
              </div>
            </div>

            <!-- Sub-kartu: Data Wali (Opsional) -->
            <div class="p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80 space-y-3">
              <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                  <span class="text-amber-500">👥</span> DATA WALI (JIKA TINGGAL BERSAMA WALI)
                </h4>
                <span class="text-[10px] text-slate-400 uppercase">Opsional</span>
              </div>
              
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Lengkap Wali</label>
                  <input
                    v-model="form.nama_wali"
                    type="text"
                    placeholder="Nama Lengkap Wali"
                    class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                  />
                </div>
                <div>
                  <label class="block font-semibold text-gray-700 text-xs mb-1">NIK Wali</label>
                  <input
                    v-model="form.nik_wali"
                    type="text"
                    maxlength="16"
                    placeholder="3302..."
                    class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 outline-none"
                  />
                </div>
                <div>
                  <label class="block font-semibold text-gray-700 text-xs mb-1">Pekerjaan Wali</label>
                  <input
                    v-model="form.pekerjaan_wali"
                    type="text"
                    placeholder="Pekerjaan Wali"
                    class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- SEKSI 5: DATA FISIK & PERIODIK (Dapodik Kolom 43) -->
          <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <div class="border-b border-gray-100 pb-3">
              <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">5</span>
                Data Periodik & Kondisi Fisik Siswa
              </h3>
              <p class="text-xs text-gray-500 mt-0.5 ml-8">Tinggi badan, berat badan, lingkar kepala, dan catatan kesehatan.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 pt-1">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Tinggi Badan (cm)</label>
                <input
                  v-model="form.tinggi_cm"
                  type="number"
                  step="0.1"
                  placeholder="Contoh: 125"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Berat Badan (kg)</label>
                <input
                  v-model="form.berat_kg"
                  type="number"
                  step="0.1"
                  placeholder="Contoh: 24"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Lingkar Kepala (cm)</label>
                <input
                  v-model="form.lingkar_kepala"
                  type="number"
                  step="0.1"
                  placeholder="Contoh: 50"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Golongan Darah</label>
                <select
                  v-model="form.golongan_darah"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none bg-white transition"
                >
                  <option value="">-- Pilih --</option>
                  <option value="A">A</option>
                  <option value="B">B</option>
                  <option value="AB">AB</option>
                  <option value="O">O</option>
                  <option value="tidak_tahu">Tidak Tahu</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Riwayat Penyakit (Jika Ada)</label>
                <input
                  v-model="form.riwayat_penyakit"
                  type="text"
                  placeholder="Riwayat penyakit khusus..."
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Alergi (Makanan / Obat)</label>
                <input
                  v-model="form.alergi"
                  type="text"
                  placeholder="Alergi udang, dingin, dll."
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition"
                />
              </div>
            </div>
          </div>

          <!-- SEKSI 6: KESEJAHTERAAN PESERTA DIDIK & BANK (Dapodik Kolom 23-24, 31-37) -->
          <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <div class="border-b border-gray-100 pb-3">
              <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">6</span>
                Kesejahteraan Peserta Didik & Rekening Bank (Dapodik)
              </h3>
              <p class="text-xs text-gray-500 mt-0.5 ml-8">Penerima KPS, KIP, PIP, Nomor KKS, dan rekening bank penyalur bantuan.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-1">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Penerima KPS / PKH</label>
                <select
                  v-model="form.penerima_kps"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none bg-white transition"
                >
                  <option value="Tidak">Tidak</option>
                  <option value="Ya">Ya (Penerima KPS)</option>
                </select>
              </div>

              <div class="sm:col-span-2">
                <label class="block font-semibold text-gray-700 text-xs mb-1">No. KPS (Jika Menerima)</label>
                <input
                  v-model="form.no_kps"
                  type="text"
                  :disabled="form.penerima_kps === 'Tidak'"
                  placeholder="Nomor kartu KPS / PKH"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition disabled:bg-gray-100 disabled:text-gray-400"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Penerima KIP (Kartu Indonesia Pintar)</label>
                <select
                  v-model="form.penerima_kip"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none bg-white transition"
                >
                  <option value="Tidak">Tidak</option>
                  <option value="Ya">Ya (Penerima KIP)</option>
                </select>
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Nomor KIP</label>
                <input
                  v-model="form.nomor_kip"
                  type="text"
                  :disabled="form.penerima_kip === 'Tidak'"
                  placeholder="Nomor Kartu KIP"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition disabled:bg-gray-100 disabled:text-gray-400"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Tertera di KIP</label>
                <input
                  v-model="form.nama_di_kip"
                  type="text"
                  :disabled="form.penerima_kip === 'Tidak'"
                  placeholder="Nama sesuai KIP"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition disabled:bg-gray-100 disabled:text-gray-400"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Nomor KKS</label>
                <input
                  v-model="form.nomor_kks"
                  type="text"
                  placeholder="Nomor Kartu Keluarga Sejahtera"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Layak PIP (Usulan Sekolah)</label>
                <select
                  v-model="form.layak_pip"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none bg-white transition"
                >
                  <option value="Tidak">Tidak</option>
                  <option value="Ya">Ya (Diusulkan Layak PIP)</option>
                </select>
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Alasan Layak PIP</label>
                <input
                  v-model="form.alasan_layak_pip"
                  type="text"
                  placeholder="Alasan pengusulan PIP"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none transition"
                />
              </div>
            </div>

            <!-- Rekening Bank -->
            <div class="p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80 space-y-3">
              <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                  <span>🏦</span> REKENING BANK SISWA (KOLOM AY - BA)
                </h4>
                <span class="text-[10px] text-slate-400 uppercase">Opsional</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Bank</label>
                  <input
                    v-model="form.bank"
                    type="text"
                    placeholder="Contoh: BRI / BNI / BSI"
                    class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                  />
                </div>
                <div>
                  <label class="block font-semibold text-gray-700 text-xs mb-1">Nomor Rekening Bank</label>
                  <input
                    v-model="form.nomor_rekening_bank"
                    type="text"
                    placeholder="Contoh: 0123-01-..."
                    class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 outline-none"
                  />
                </div>
                <div>
                  <label class="block font-semibold text-gray-700 text-xs mb-1">Rekening Atas Nama</label>
                  <input
                    v-model="form.rekening_atas_nama"
                    type="text"
                    placeholder="Nama pemilik rekening"
                    class="w-full bg-white border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                  />
                </div>
              </div>
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
              <span>Kembali ke Daftar Siswa</span>
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
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/25 transition disabled:opacity-50 active:scale-95"
              >
                <svg v-if="saving" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ saving ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Siswa Baru') }}</span>
              </button>
            </div>
          </div>

        </div>
      </form>
    </div>

    <!-- ============================================================== -->
    <!-- VIEW 3: HALAMAN DETAIL BIODATA SISWA LENGKAP DAPODIK (MENIMPA) -->
    <!-- ============================================================== -->
    <div v-else-if="currentView === 'detail' && selectedStudent" class="space-y-6 animate-in fade-in duration-200">
      <!-- Top Navigation & Action Banner -->
      <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
          <button
            @click="closeDetail"
            type="button"
            class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center justify-center shrink-0 shadow-xs group"
            title="Kembali ke Daftar Siswa"
          >
            <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
              <span>Kesiswaan</span>
              <span>/</span>
              <button type="button" @click="closeDetail" class="hover:text-emerald-600 hover:underline">Data Siswa</button>
              <span>/</span>
              <span class="text-emerald-700 font-bold">Detail Profil Siswa (Format Dapodik)</span>
            </div>
            <h1 class="text-lg sm:text-2xl font-black text-gray-900 leading-tight">
              Detail Siswa: {{ selectedStudent.nama_lengkap }}
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              Berkas biodata lengkap dan data terpadu kesiswaan resmi sesuai 66 kolom Dapodik Kemdikbud.
            </p>
          </div>
        </div>

        <!-- Top Action Buttons -->
        <div class="flex items-center gap-2.5 self-end sm:self-center">
          <button
            @click="closeDetail"
            type="button"
            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition flex items-center gap-1.5"
          >
            <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Kembali ke Daftar</span>
          </button>
          <button
            v-if="auth.isAdmin"
            @click="editFromDetail(selectedStudent)"
            type="button"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl shadow-md shadow-amber-500/25 transition active:scale-95"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
            <span>Edit Siswa Ini</span>
          </button>
        </div>
      </div>

      <!-- Hero Header Card: Ringkasan Siswa -->
      <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="flex items-center gap-5">
          <!-- Avatar / Foto -->
          <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl overflow-hidden bg-slate-100 border-2 border-emerald-500/20 shrink-0 shadow-sm flex items-center justify-center">
            <img
              v-if="selectedStudent.foto_url || selectedStudent.foto_path"
              :src="getPhotoUrl(selectedStudent.foto_url, selectedStudent.foto_path)"
              :alt="selectedStudent.nama_lengkap"
              class="w-full h-full object-cover"
            />
            <span v-else class="text-2xl font-black text-gray-400 uppercase">
              {{ getInitials(selectedStudent.nama_lengkap) }}
            </span>
          </div>

          <!-- Info Pokok -->
          <div class="space-y-1.5">
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="text-xl sm:text-2xl font-black text-gray-900 leading-tight">
                {{ selectedStudent.nama_lengkap }}
              </h2>
              <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                {{ selectedStudent.status || 'AKTIF' }}
              </span>
              <span
                class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                :class="selectedStudent.jenis_kelamin === 'L' ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-pink-50 text-pink-700 border-pink-200'"
              >
                {{ selectedStudent.jenis_kelamin === 'L' ? '👦 Laki-laki (L)' : '👧 Perempuan (P)' }}
              </span>
            </div>

            <div class="flex flex-wrap items-center gap-y-1 gap-x-4 text-xs text-gray-600">
              <span class="inline-flex items-center gap-1 font-mono">
                <span class="text-gray-400 font-normal">NIS:</span>
                <strong class="text-gray-800">{{ selectedStudent.nis }}</strong>
              </span>
              <span class="inline-flex items-center gap-1 font-mono">
                <span class="text-gray-400 font-normal">NISN:</span>
                <strong class="text-gray-800">{{ selectedStudent.nisn || '-' }}</strong>
              </span>
              <span class="inline-flex items-center gap-1 font-mono">
                <span class="text-gray-400 font-normal">NIK:</span>
                <strong class="text-gray-800">{{ selectedStudent.nik || '-' }}</strong>
              </span>
              <span class="inline-flex items-center gap-1">
                <span class="text-gray-400">Rombel:</span>
                <strong class="text-emerald-700">{{ selectedStudent.school_class?.nama_kelas || selectedStudent.current_class?.nama_kelas || '-' }}</strong>
              </span>
            </div>

            <p class="text-xs text-gray-400">
              ID Database: #{{ selectedStudent.id }} • Terdaftar sejak: {{ formatDateIndo(selectedStudent.tgl_masuk) || '-' }}
            </p>
          </div>
        </div>

        <div class="flex flex-wrap sm:flex-nowrap gap-3 self-stretch md:self-auto justify-end">
          <div class="px-4 py-3 bg-slate-50 border border-slate-100 rounded-2xl text-center min-w-[120px]">
            <span class="text-[10px] text-gray-400 uppercase font-semibold block">Tempat & Tanggal Lahir</span>
            <span class="text-xs font-bold text-gray-800 block mt-0.5">
              {{ selectedStudent.tempat_lahir || '-' }}, {{ formatDateIndo(selectedStudent.tgl_lahir) }}
            </span>
          </div>
          <div class="px-4 py-3 bg-slate-50 border border-slate-100 rounded-2xl text-center min-w-[120px]">
            <span class="text-[10px] text-gray-400 uppercase font-semibold block">Kontak Utama</span>
            <span class="text-xs font-bold text-emerald-700 block mt-0.5">
              {{ selectedStudent.no_hp_siswa || getStudentParentPhone(selectedStudent) || '-' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Tab Navigation & Content Container -->
      <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <!-- Tab Navigation Bar -->
        <div class="border-b border-gray-100 bg-slate-50/50 px-4 sm:px-6 flex overflow-x-auto gap-2 text-xs font-semibold">
          <button
            @click="detailTab = 'identitas'"
            :class="detailTab === 'identitas' ? 'border-emerald-600 text-emerald-700 bg-white shadow-xs font-bold' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100/60'"
            class="py-3.5 px-4 border-b-2 rounded-t-xl whitespace-nowrap transition flex items-center gap-2"
          >
            <span>👤 Identitas Pokok</span>
          </button>
          <button
            @click="detailTab = 'rombel'"
            :class="detailTab === 'rombel' ? 'border-emerald-600 text-emerald-700 bg-white shadow-xs font-bold' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100/60'"
            class="py-3.5 px-4 border-b-2 rounded-t-xl whitespace-nowrap transition flex items-center gap-2"
          >
            <span>🏫 Rombel & Riwayat</span>
          </button>
          <button
            @click="detailTab = 'alamat'"
            :class="detailTab === 'alamat' ? 'border-emerald-600 text-emerald-700 bg-white shadow-xs font-bold' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100/60'"
            class="py-3.5 px-4 border-b-2 rounded-t-xl whitespace-nowrap transition flex items-center gap-2"
          >
            <span>📍 Alamat & Kontak</span>
          </button>
          <button
            @click="detailTab = 'ortu'"
            :class="detailTab === 'ortu' ? 'border-emerald-600 text-emerald-700 bg-white shadow-xs font-bold' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100/60'"
            class="py-3.5 px-4 border-b-2 rounded-t-xl whitespace-nowrap transition flex items-center gap-2"
          >
            <span>👨‍👩‍👧 Orang Tua & Wali</span>
          </button>
          <button
            @click="detailTab = 'fisik'"
            :class="detailTab === 'fisik' ? 'border-emerald-600 text-emerald-700 bg-white shadow-xs font-bold' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100/60'"
            class="py-3.5 px-4 border-b-2 rounded-t-xl whitespace-nowrap transition flex items-center gap-2"
          >
            <span>🩺 Fisik & Periodik</span>
          </button>
          <button
            @click="detailTab = 'bantuan'"
            :class="detailTab === 'bantuan' ? 'border-emerald-600 text-emerald-700 bg-white shadow-xs font-bold' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100/60'"
            class="py-3.5 px-4 border-b-2 rounded-t-xl whitespace-nowrap transition flex items-center gap-2"
          >
            <span>💳 Bantuan & Bank</span>
          </button>
          <button
            @click="detailTab = 'rapor'"
            :class="detailTab === 'rapor' ? 'border-emerald-600 text-emerald-700 bg-white shadow-xs font-bold' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-100/60'"
            class="py-3.5 px-4 border-b-2 rounded-t-xl whitespace-nowrap transition flex items-center gap-2"
          >
            <span>📑 Arsip & Riwayat Rapor</span>
            <span v-if="studentReportHistory.length > 0" class="px-1.5 py-0.5 rounded-full text-[10px] bg-emerald-100 text-emerald-800 font-black">
              {{ studentReportHistory.reduce((acc: number, y: any) => acc + (y.report_cards?.length || 0), 0) }}
            </span>
          </button>
        </div>

        <!-- Tab Content Body (Full Page Height, Spacious) -->
        <div class="p-6 sm:p-8 space-y-6 text-xs">
          <!-- TAB 1: IDENTITAS POKOK -->
          <div v-if="detailTab === 'identitas'" class="space-y-6">
            <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-gray-900">Identitas Pokok Siswa</h3>
                <p class="text-gray-500 text-[11px]">Sesuai Dapodik Kemdikbud Kolom 1 - 10</p>
              </div>
              <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-[11px] font-bold">Kolom 1 - 10</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Nama Lengkap</span>
                <strong class="text-gray-900 block text-sm mt-0.5">{{ selectedStudent.nama_lengkap }}</strong>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Nama Panggilan</span>
                <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.nama_panggilan || '-' }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Jenis Kelamin</span>
                <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.jenis_kelamin === 'L' ? 'Laki-laki (L)' : 'Perempuan (P)' }}</div>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">NIS / NIPD</span>
                <div class="text-gray-900 font-mono font-bold text-sm mt-0.5">{{ selectedStudent.nis }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">NISN</span>
                <div class="text-gray-900 font-mono font-medium text-sm mt-0.5">{{ selectedStudent.nisn || '-' }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">NIK Siswa</span>
                <div class="text-gray-900 font-mono font-medium text-sm mt-0.5">{{ selectedStudent.nik || '-' }}</div>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Tempat, Tanggal Lahir</span>
                <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.tempat_lahir || '-' }}, {{ formatDateIndo(selectedStudent.tgl_lahir) }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Agama & Kepercayaan</span>
                <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.religion?.nama || 'Islam' }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Kewarganegaraan</span>
                <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.kewarganegaraan || 'WNI' }}</div>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Nomor Kartu Keluarga (KK)</span>
                <div class="text-gray-800 font-mono text-sm mt-0.5">{{ selectedStudent.no_kk || '-' }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">No. Registrasi Akta Lahir</span>
                <div class="text-gray-800 font-mono text-sm mt-0.5">{{ selectedStudent.no_akta_lahir || '-' }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Anak Ke-</span>
                <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.anak_ke ?? 1 }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Jumlah Saudara Kandung</span>
                <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.jumlah_saudara ?? 0 }}</div>
              </div>
            </div>

            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Kebutuhan Khusus</span>
              <div class="text-gray-800 font-medium mt-1">{{ selectedStudent.kebutuhan_khusus || 'Tidak ada / Normal' }}</div>
            </div>
          </div>

          <!-- TAB 2: ROMBEL & PENDAFTARAN -->
          <div v-if="detailTab === 'rombel'" class="space-y-6">
            <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-gray-900">Rombongan Belajar & Riwayat Pendaftaran</h3>
                <p class="text-gray-500 text-[11px]">Sesuai Dapodik Kemdikbud Kolom 22, 28-30, 39</p>
              </div>
              <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-[11px] font-bold">Kolom 22, 28-30, 39</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Rombel Saat Ini</span>
                <div class="text-emerald-700 font-bold text-base mt-0.5">
                  {{ selectedStudent.school_class?.nama_kelas || selectedStudent.current_class?.nama_kelas || '-' }}
                </div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Tanggal Masuk Sekolah</span>
                <div class="text-gray-800 font-medium text-sm mt-0.5">{{ formatDateIndo(selectedStudent.tgl_masuk) || '-' }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Status Kesiswaan</span>
                <div class="mt-0.5">
                  <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800">
                    {{ selectedStudent.status }}
                  </span>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Jenis Pendaftaran</span>
                <div class="text-gray-800 font-medium text-sm mt-0.5 capitalize">{{ selectedStudent.jenis_pendaftaran || 'Siswa Baru' }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Sekolah Asal (TK / PAUD)</span>
                <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.asal_sekolah_tk || '-' }}</div>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">SKHUN (Jika Ada)</span>
                <div class="text-gray-800 font-mono text-sm mt-0.5">{{ selectedStudent.skhun || '-' }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">No. Peserta Ujian Nasional</span>
                <div class="text-gray-800 font-mono text-sm mt-0.5">{{ selectedStudent.no_peserta_un || '-' }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">No. Seri Ijazah</span>
                <div class="text-gray-800 font-mono text-sm mt-0.5">{{ selectedStudent.no_seri_ijazah || '-' }}</div>
              </div>
            </div>
          </div>

          <!-- TAB 3: ALAMAT & TRANSPORTASI -->
          <div v-if="detailTab === 'alamat'" class="space-y-6">
            <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-gray-900">Alamat Domisili, Transportasi & Kontak</h3>
                <p class="text-gray-500 text-[11px]">Sesuai Dapodik Kemdikbud Kolom 11 - 21, 41</p>
              </div>
              <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-[11px] font-bold">Kolom 11 - 21, 41</span>
            </div>

            <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
              <span class="text-gray-400 block text-[10px] uppercase font-semibold">Alamat Jalan Lengkap</span>
              <div class="text-gray-900 font-medium text-sm mt-0.5">{{ selectedStudent.alamat_jalan || '-' }}</div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">RT / RW</span>
                <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.rt || '-' }} / {{ selectedStudent.rw || '-' }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Dusun</span>
                <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.dusun || '-' }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Kelurahan / Desa</span>
                <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.desa_kelurahan || '-' }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Kecamatan</span>
                <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.kecamatan || '-' }}</div>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Kabupaten / Kota</span>
                <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.kab_kota || 'Banyumas' }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Provinsi</span>
                <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.provinsi || 'Jawa Tengah' }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Kode Pos</span>
                <div class="text-gray-800 font-mono text-sm mt-0.5">{{ selectedStudent.kode_pos || '-' }}</div>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Tempat Tinggal Bersama</span>
                <div class="text-gray-800 font-medium text-sm mt-0.5 capitalize">{{ selectedStudent.tinggal_bersama?.replace('_', ' ') || 'Orang Tua' }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Moda Transportasi</span>
                <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.transportation?.nama || 'Sepeda Motor / Mobil Pribadi' }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">No. HP / WA Siswa</span>
                <div class="text-emerald-700 font-mono font-bold text-sm mt-0.5">{{ selectedStudent.no_hp_siswa || '-' }}</div>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Telepon Rumah</span>
                <div class="text-gray-800 font-mono text-sm mt-0.5">{{ selectedStudent.telepon || '-' }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Email Siswa</span>
                <div class="text-gray-800 text-sm mt-0.5">{{ selectedStudent.email || '-' }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Jarak & Waktu Tempuh</span>
                <div class="text-gray-800 text-sm mt-0.5">
                  {{ selectedStudent.jarak_ke_sekolah_km ? `${selectedStudent.jarak_ke_sekolah_km} km` : '-' }} /
                  {{ selectedStudent.waktu_tempuh_menit ? `${selectedStudent.waktu_tempuh_menit} menit` : '-' }}
                </div>
              </div>
            </div>
          </div>

          <!-- TAB 4: ORANG TUA & WALI -->
          <div v-if="detailTab === 'ortu'" class="space-y-6">
            <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-gray-900">Data Orang Tua & Wali Siswa</h3>
                <p class="text-gray-500 text-[11px]">Sesuai Dapodik Kemdikbud Kolom 25 - 27 (Ayah Y-AD, Ibu AE-AJ, Wali AK-AP)</p>
              </div>
              <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-[11px] font-bold">Kolom 25 - 27</span>
            </div>

            <!-- AYAH KANDUNG -->
            <div class="p-5 bg-sky-50/40 rounded-2xl border border-sky-100">
              <div class="flex items-center justify-between border-b border-sky-100 pb-2.5 mb-3">
                <span class="font-bold text-sky-900 text-sm flex items-center gap-2">
                  <span>👨</span> Data Ayah Kandung
                </span>
                <span class="text-[10px] text-sky-600 bg-sky-100/60 px-2 py-0.5 rounded-full font-mono">Dapodik Kolom Y-AD</span>
              </div>
              <div v-if="getGuardianByRelation(selectedStudent, 'ayah')" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">Nama Lengkap</span>
                  <div class="font-bold text-gray-900 text-sm">{{ getGuardianByRelation(selectedStudent, 'ayah')?.nama_lengkap }}</div>
                </div>
                <div>
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">NIK Ayah</span>
                  <div class="text-gray-800 font-mono text-sm">{{ getGuardianByRelation(selectedStudent, 'ayah')?.nik || '-' }}</div>
                </div>
                <div>
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">No. HP / WA</span>
                  <div class="text-emerald-700 font-mono font-bold text-sm">{{ getGuardianByRelation(selectedStudent, 'ayah')?.no_hp || '-' }}</div>
                </div>
                <div>
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">Pekerjaan</span>
                  <div class="text-gray-800 text-sm">{{ getGuardianByRelation(selectedStudent, 'ayah')?.pekerjaan || '-' }}</div>
                </div>
                <div>
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">Pendidikan Terakhir</span>
                  <div class="text-gray-800 text-sm">{{ getGuardianByRelation(selectedStudent, 'ayah')?.pendidikan_terakhir || '-' }}</div>
                </div>
                <div>
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">Penghasilan Bulanan</span>
                  <div class="text-gray-800 text-sm">{{ getGuardianByRelation(selectedStudent, 'ayah')?.penghasilan_bulanan || '-' }}</div>
                </div>
              </div>
              <div v-else class="text-gray-400 text-xs italic py-2">
                Belum ada data ayah kandung tersimpan.
              </div>
            </div>

            <!-- IBU KANDUNG -->
            <div class="p-5 bg-rose-50/40 rounded-2xl border border-rose-100">
              <div class="flex items-center justify-between border-b border-rose-100 pb-2.5 mb-3">
                <span class="font-bold text-rose-900 text-sm flex items-center gap-2">
                  <span>👩</span> Data Ibu Kandung
                </span>
                <span class="text-[10px] text-rose-600 bg-rose-100/60 px-2 py-0.5 rounded-full font-mono">Dapodik Kolom AE-AJ</span>
              </div>
              <div v-if="getGuardianByRelation(selectedStudent, 'ibu')" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">Nama Lengkap</span>
                  <div class="font-bold text-gray-900 text-sm">{{ getGuardianByRelation(selectedStudent, 'ibu')?.nama_lengkap }}</div>
                </div>
                <div>
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">NIK Ibu</span>
                  <div class="text-gray-800 font-mono text-sm">{{ getGuardianByRelation(selectedStudent, 'ibu')?.nik || '-' }}</div>
                </div>
                <div>
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">No. HP / WA</span>
                  <div class="text-emerald-700 font-mono font-bold text-sm">{{ getGuardianByRelation(selectedStudent, 'ibu')?.no_hp || '-' }}</div>
                </div>
                <div>
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">Pekerjaan</span>
                  <div class="text-gray-800 text-sm">{{ getGuardianByRelation(selectedStudent, 'ibu')?.pekerjaan || '-' }}</div>
                </div>
                <div>
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">Pendidikan Terakhir</span>
                  <div class="text-gray-800 text-sm">{{ getGuardianByRelation(selectedStudent, 'ibu')?.pendidikan_terakhir || '-' }}</div>
                </div>
                <div>
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">Penghasilan Bulanan</span>
                  <div class="text-gray-800 text-sm">{{ getGuardianByRelation(selectedStudent, 'ibu')?.penghasilan_bulanan || '-' }}</div>
                </div>
              </div>
              <div v-else class="text-gray-400 text-xs italic py-2">
                Belum ada data ibu kandung tersimpan.
              </div>
            </div>

            <!-- WALI MURID -->
            <div class="p-5 bg-amber-50/40 rounded-2xl border border-amber-100">
              <div class="flex items-center justify-between border-b border-amber-100 pb-2.5 mb-3">
                <span class="font-bold text-amber-900 text-sm flex items-center gap-2">
                  <span>🧑‍💼</span> Data Wali Murid
                </span>
                <span class="text-[10px] text-amber-600 bg-amber-100/60 px-2 py-0.5 rounded-full font-mono">Dapodik Kolom AK-AP</span>
              </div>
              <div v-if="getGuardianByRelation(selectedStudent, 'wali')" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">Nama Lengkap</span>
                  <div class="font-bold text-gray-900 text-sm">{{ getGuardianByRelation(selectedStudent, 'wali')?.nama_lengkap }}</div>
                </div>
                <div>
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">NIK Wali</span>
                  <div class="text-gray-800 font-mono text-sm">{{ getGuardianByRelation(selectedStudent, 'wali')?.nik || '-' }}</div>
                </div>
                <div>
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">No. HP / WA</span>
                  <div class="text-emerald-700 font-mono font-bold text-sm">{{ getGuardianByRelation(selectedStudent, 'wali')?.no_hp || '-' }}</div>
                </div>
                <div>
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">Pekerjaan</span>
                  <div class="text-gray-800 text-sm">{{ getGuardianByRelation(selectedStudent, 'wali')?.pekerjaan || '-' }}</div>
                </div>
                <div>
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">Pendidikan Terakhir</span>
                  <div class="text-gray-800 text-sm">{{ getGuardianByRelation(selectedStudent, 'wali')?.pendidikan_terakhir || '-' }}</div>
                </div>
                <div>
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">Penghasilan Bulanan</span>
                  <div class="text-gray-800 text-sm">{{ getGuardianByRelation(selectedStudent, 'wali')?.penghasilan_bulanan || '-' }}</div>
                </div>
              </div>
              <div v-else class="text-gray-400 text-xs italic py-2">
                Tidak ada data wali murid (siswa tinggal bersama orang tua kandung).
              </div>
            </div>
          </div>

          <!-- TAB 5: FISIK & PERIODIK -->
          <div v-if="detailTab === 'fisik'" class="space-y-6">
            <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-gray-900">Data Periodik & Kondisi Fisik Siswa</h3>
                <p class="text-gray-500 text-[11px]">Sesuai Dapodik Kemdikbud Kolom 43</p>
              </div>
              <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-[11px] font-bold">Kolom 43</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100 text-center">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Tinggi Badan</span>
                <div class="text-emerald-700 font-black text-xl mt-1">
                  {{ selectedStudent.tinggi_cm ? `${selectedStudent.tinggi_cm} cm` : '-' }}
                </div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100 text-center">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Berat Badan</span>
                <div class="text-emerald-700 font-black text-xl mt-1">
                  {{ selectedStudent.berat_kg ? `${selectedStudent.berat_kg} kg` : '-' }}
                </div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100 text-center">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Lingkar Kepala</span>
                <div class="text-emerald-700 font-black text-xl mt-1">
                  {{ selectedStudent.lingkar_kepala ? `${selectedStudent.lingkar_kepala} cm` : '-' }}
                </div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100 text-center">
                <span class="text-rose-600 font-black text-xl mt-1">
                  {{ selectedStudent.golongan_darah || '-' }}
                </span>
                <span class="text-gray-400 block text-[10px] uppercase font-semibold mt-0.5">Gol. Darah</span>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Riwayat Penyakit</span>
                <div class="text-gray-800 text-sm mt-1">{{ selectedStudent.riwayat_penyakit || 'Tidak ada riwayat penyakit berat' }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Alergi Makanan / Obat</span>
                <div class="text-gray-800 text-sm mt-1">{{ selectedStudent.alergi || 'Tidak ada alergi' }}</div>
              </div>
            </div>
          </div>

          <!-- TAB 6: BANTUAN & BANK -->
          <div v-if="detailTab === 'bantuan'" class="space-y-6">
            <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-gray-900">Kesejahteraan Peserta Didik & Rekening Bank</h3>
                <p class="text-gray-500 text-[11px]">Sesuai Dapodik Kemdikbud Kolom 23-24, 31-37</p>
              </div>
              <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-[11px] font-bold">Kolom 23-24, 31-37</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Penerima KPS / PKH</span>
                <div class="text-gray-900 font-bold text-sm mt-0.5">{{ selectedStudent.penerima_kps || 'Tidak' }}</div>
                <div v-if="selectedStudent.no_kps" class="text-xs text-gray-500 font-mono mt-1">No. KPS: {{ selectedStudent.no_kps }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Penerima KIP</span>
                <div class="text-gray-900 font-bold text-sm mt-0.5">{{ selectedStudent.penerima_kip || 'Tidak' }}</div>
                <div v-if="selectedStudent.nomor_kip" class="text-xs text-gray-500 font-mono mt-1">No. KIP: {{ selectedStudent.nomor_kip }}</div>
                <div v-if="selectedStudent.nama_di_kip" class="text-xs text-gray-500 mt-0.5">Nama di KIP: {{ selectedStudent.nama_di_kip }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Nomor KKS</span>
                <div class="text-gray-900 font-mono text-sm mt-0.5">{{ selectedStudent.nomor_kks || '-' }}</div>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Layak PIP</span>
                <div class="text-gray-900 font-bold text-sm mt-0.5">{{ selectedStudent.layak_pip || 'Tidak' }}</div>
              </div>
              <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Alasan Layak PIP</span>
                <div class="text-gray-800 text-sm mt-0.5">{{ selectedStudent.alasan_layak_pip || '-' }}</div>
              </div>
            </div>

            <!-- Rekening Bank -->
            <div class="p-5 bg-emerald-50/30 rounded-2xl border border-emerald-100 space-y-3">
              <div class="flex items-center gap-2 text-emerald-800 font-bold text-sm">
                <span>🏦 Rekening Bank Penyalur</span>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-3.5 rounded-xl border border-emerald-100">
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">Bank Penyalur</span>
                  <div class="text-gray-900 font-bold text-sm mt-0.5">{{ selectedStudent.bank || '-' }}</div>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-emerald-100">
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">Nomor Rekening</span>
                  <div class="text-emerald-700 font-mono font-bold text-sm mt-0.5">{{ selectedStudent.nomor_rekening_bank || '-' }}</div>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-emerald-100">
                  <span class="text-gray-400 block text-[10px] uppercase font-semibold">Atas Nama</span>
                  <div class="text-gray-800 font-medium text-sm mt-0.5">{{ selectedStudent.rekening_atas_nama || '-' }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB 7: ARSIP & RIWAYAT RAPOR (TIMELINE PORTFOLIO) -->
          <div v-if="detailTab === 'rapor'" class="space-y-6">
            <div class="border-b border-gray-100 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
              <div>
                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                  <span>📑</span>
                  <span>Buku Induk & Arsip Rapor Digital</span>
                </h3>
                <p class="text-gray-500 text-[11px] mt-0.5">
                  Rekam jejak riwayat perkembangan belajar, intrakurikuler, P5, tematik terpadu, dan karakter siswa per tahun ajaran.
                </p>
              </div>
              <router-link
                to="/rapor"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-xl font-bold text-[11px] border border-emerald-200 transition"
              >
                <span>Buka Menu Olah Rapor</span>
                <span>↗</span>
              </router-link>
            </div>

            <!-- Loading State -->
            <div v-if="loadingReportHistory" class="py-12 text-center text-slate-400">
              <div class="inline-block animate-spin w-6 h-6 border-2 border-emerald-600 border-t-transparent rounded-full mb-2"></div>
              <div>Memuat arsip riwayat rapor siswa...</div>
            </div>

            <!-- Empty State -->
            <div v-else-if="studentReportHistory.length === 0" class="py-12 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
              <div class="text-3xl mb-2">📂</div>
              <div class="text-xs font-bold text-slate-700">Belum Ada Arsip Rapor Tercatat</div>
              <p class="text-[11px] text-slate-400 mt-1 max-w-sm mx-auto">
                Rapor untuk siswa ini belum diinisialisasi atau belum dibuat di menu Rapor Siswa.
              </p>
            </div>

            <!-- Timeline Years -->
            <div v-else class="space-y-6">
              <div
                v-for="yearBlock in studentReportHistory"
                :key="yearBlock.academic_year.id"
                class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden"
              >
                <!-- Year Header -->
                <div class="p-4 bg-gradient-to-r from-slate-50 to-slate-100/70 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-xs shadow-xs">
                      TA
                    </div>
                    <div>
                      <div class="flex items-center gap-2">
                        <span class="text-sm font-black text-slate-900">
                          Tahun Pelajaran {{ yearBlock.academic_year.nama }}
                        </span>
                        <span
                          class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase border"
                          :class="yearBlock.academic_year.status === 'aktif' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'"
                        >
                          {{ yearBlock.academic_year.status }}
                        </span>
                      </div>
                      <div class="text-[11px] text-slate-500 mt-0.5">
                        Rombel: <strong>{{ yearBlock.class?.nama_kelas || yearBlock.class?.nama || '-' }}</strong>
                        <span v-if="yearBlock.nomor_absen"> • No. Absen: {{ yearBlock.nomor_absen }}</span>
                        • Status: <strong class="capitalize">{{ yearBlock.status_kelas }}</strong>
                      </div>
                    </div>
                  </div>

                  <div class="text-right text-[11px]">
                    <span class="text-slate-500">Total Rapor: </span>
                    <strong class="text-slate-900">{{ yearBlock.report_cards?.length || 0 }}</strong>
                    <span class="mx-1">•</span>
                    <span class="text-emerald-700 font-bold">{{ yearBlock.final_count || 0 }} Disahkan KS</span>
                  </div>
                </div>

                <!-- Cards for each report card in this year -->
                <div class="p-4 sm:p-5">
                  <div v-if="!yearBlock.report_cards || yearBlock.report_cards.length === 0" class="text-slate-400 text-xs italic py-2">
                    Belum ada rapor yang dibuat pada tahun pelajaran ini.
                  </div>
                  <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                    <div
                      v-for="rc in yearBlock.report_cards"
                      :key="rc.id"
                      class="p-4 rounded-2xl border transition-all hover:shadow-xs flex flex-col justify-between"
                      :class="{
                        'bg-emerald-50/30 border-emerald-200': rc.status === 'final',
                        'bg-blue-50/30 border-blue-200': rc.status === 'menunggu_validasi',
                        'bg-amber-50/30 border-amber-200': rc.status === 'draft',
                        'bg-rose-50/30 border-rose-200': rc.status === 'perlu_revisi',
                      }"
                    >
                      <div>
                        <!-- Header badge -->
                        <div class="flex items-center justify-between gap-2 mb-2">
                          <span
                            class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider"
                            :class="{
                              'bg-indigo-100 text-indigo-800': rc.jenis_rapor === 'kurikulum_merdeka',
                              'bg-amber-100 text-amber-800': rc.jenis_rapor === 'triwulan_tematik',
                              'bg-purple-100 text-purple-800': rc.jenis_rapor === 'karakter_lifeskill',
                            }"
                          >
                            {{ rc.jenis_rapor === 'kurikulum_merdeka' ? 'Kurikulum Merdeka' : (rc.jenis_rapor === 'triwulan_tematik' ? 'Tematik Terpadu' : 'Karakter & Life Skill') }}
                          </span>

                          <span
                            class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                            :class="{
                              'bg-emerald-100 text-emerald-800 border-emerald-300': rc.status === 'final',
                              'bg-blue-100 text-blue-800 border-blue-300': rc.status === 'menunggu_validasi',
                              'bg-amber-100 text-amber-800 border-amber-300': rc.status === 'draft',
                              'bg-rose-100 text-rose-800 border-rose-300': rc.status === 'perlu_revisi',
                            }"
                          >
                            {{ rc.status === 'final' ? '✓ Disahkan KS' : (rc.status === 'menunggu_validasi' ? '⏳ Menunggu KS' : (rc.status === 'perlu_revisi' ? '⚠️ Perlu Revisi' : 'Draf')) }}
                          </span>
                        </div>

                        <div class="text-xs font-black text-slate-800">
                          {{ rc.periode || (rc.semester === 'gasal' ? 'Semester Gasal' : 'Semester Genap') }}
                        </div>
                        <div class="text-[11px] text-slate-500 mt-0.5">
                          Fase {{ rc.fase || 'A' }} • {{ rc.grades_count || 0 }} Nilai Aspek/Mapel
                        </div>

                        <div class="mt-2.5 pt-2.5 border-t border-slate-200/60 text-[10px] text-slate-500 space-y-0.5">
                          <div v-if="rc.wali_kelas_nama">Wali Kelas: <strong class="text-slate-700">{{ rc.wali_kelas_nama }}</strong></div>
                          <div v-if="rc.kepala_sekolah_nama">Kepala Sekolah: <strong class="text-slate-700">{{ rc.kepala_sekolah_nama }}</strong></div>
                          <div v-if="rc.validated_at" class="text-emerald-700">Divalidasi: {{ formatDateIndo(rc.validated_at) }}</div>
                        </div>
                      </div>

                      <div class="mt-3 pt-2 flex items-center justify-between gap-2 border-t border-slate-200/60">
                        <router-link
                          :to="`/rapor?class_id=${rc.class_id}&semester=${rc.semester}&jenis_rapor=${rc.jenis_rapor}`"
                          class="px-2.5 py-1 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg text-[11px] font-bold text-slate-700 transition"
                        >
                          Buka di Menu Rapor
                        </router-link>

                        <router-link
                          :to="`/rapor?class_id=${rc.class_id}&semester=${rc.semester}&jenis_rapor=${rc.jenis_rapor}`"
                          class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold shadow-xs transition flex items-center gap-1 cursor-pointer"
                          title="Cetak format resmi dokumen rapor ini"
                        >
                          <span>🖨️</span>
                          <span>Cetak</span>
                        </router-link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab Bottom Bar -->
        <div class="p-4 sm:p-6 border-t border-gray-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-3">
          <div class="text-xs text-gray-400 font-mono">
            ID Database: #{{ selectedStudent.id }} • Terakhir diperbarui: {{ selectedStudent.updated_at ? formatDateIndo(selectedStudent.updated_at) : '-' }}
          </div>
          <div class="flex items-center gap-3">
            <button
              @click="closeDetail"
              class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition"
            >
              Kembali ke Daftar
            </button>
            <button
              v-if="auth.isAdmin"
              @click="editFromDetail(selectedStudent)"
              class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl shadow-xs transition"
            >
              Edit Siswa Ini
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '../services/api';
import { useAuthStore } from '../stores/auth';
import { confirmDeleteWithKeyword, showSuccess, showError } from '../services/swal';
import { calculateTenure, formatDateIndo, toInputDate } from '../utils/date';

const route = useRoute();
const auth = useAuthStore();

// View Mode: 'list' (tabel) atau 'form' (halaman formulir penuh menimpa tabel)
const currentView = ref<'list' | 'form' | 'detail'>('list');

const students = ref<any[]>([]);
const classes = ref<any[]>([]);
const religions = ref<any[]>([]);
const transports = ref<any[]>([]);
const educationList = ref<any[]>([]);
const occupationList = ref<any[]>([]);

const search = ref('');
const filterClass = ref('');
const filterStatus = ref('aktif');
const loading = ref(false);
const saving = ref(false);
const exporting = ref(false);

const selectedStudent = ref<any>(null);
const detailTab = ref<'identitas' | 'rombel' | 'alamat' | 'ortu' | 'fisik' | 'bantuan' | 'rapor'>('identitas');
const studentReportHistory = ref<any[]>([]);
const loadingReportHistory = ref(false);

const fetchStudentReportHistory = async (studentId: number) => {
  loadingReportHistory.value = true;
  try {
    const res = await api.get(`/report-cards/student/${studentId}/history`);
    if (res.data.success && res.data.data) {
      studentReportHistory.value = res.data.data.timeline || [];
    }
  } catch (err) {
    console.error('Gagal mengambil riwayat rapor siswa:', err);
    studentReportHistory.value = [];
  } finally {
    loadingReportHistory.value = false;
  }
};

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

// Form State with all 66 Dapodik columns
const isEditing = ref(false);
const editingId = ref<number | null>(null);
const form = ref<any>({
  nis: '',
  nisn: '',
  nik: '',
  no_kk: '',
  no_akta_lahir: '',
  nama_lengkap: '',
  nama_panggilan: '',
  jenis_kelamin: 'L',
  tempat_lahir: 'Banyumas',
  tgl_lahir: '2018-01-01',
  agama: 1,
  kewarganegaraan: 'WNI',
  anak_ke: 1,
  jumlah_saudara: 0,
  kebutuhan_khusus: '',

  current_class_id: null,
  tgl_masuk: new Date().toISOString().split('T')[0],
  status: 'aktif',
  jenis_pendaftaran: 'baru',
  asal_sekolah_tk: '',
  skhun: '',
  no_peserta_un: '',
  no_seri_ijazah: '',

  alamat_jalan: '',
  rt: '',
  rw: '',
  dusun: '',
  desa_kelurahan: '',
  kecamatan: '',
  kab_kota: 'Banyumas',
  provinsi: 'Jawa Tengah',
  kode_pos: '',
  tinggal_bersama: 'orang_tua',
  transportasi: null,
  jarak_ke_sekolah_km: '',
  waktu_tempuh_menit: '',
  latitude: '',
  longitude: '',
  no_hp_siswa: '',
  telepon: '',
  email: '',

  // Data Ayah
  nama_ayah: '',
  nik_ayah: '',
  pekerjaan_ayah: '',
  pendidikan_ayah: '',
  penghasilan_ayah: '',
  no_hp_ayah: '',

  // Data Ibu
  nama_ibu: '',
  nik_ibu: '',
  pekerjaan_ibu: '',
  pendidikan_ibu: '',
  penghasilan_ibu: '',
  no_hp_ibu: '',

  // Data Wali
  nama_wali: '',
  nik_wali: '',
  pekerjaan_wali: '',
  pendidikan_wali: '',
  penghasilan_wali: '',
  no_hp_wali: '',

  // Fisik & Periodik
  tinggi_cm: '',
  berat_kg: '',
  lingkar_kepala: '',
  golongan_darah: '',
  riwayat_penyakit: '',
  alergi: '',

  // Kesejahteraan & Bank
  penerima_kps: 'Tidak',
  no_kps: '',
  penerima_kip: 'Tidak',
  nomor_kip: '',
  nama_di_kip: '',
  nomor_kks: '',
  layak_pip: 'Tidak',
  alasan_layak_pip: '',
  bank: '',
  nomor_rekening_bank: '',
  rekening_atas_nama: '',
});

// Computed: Umur Siswa Otomatis dari Tanggal Lahir
const studentAge = computed(() => {
  if (!form.value.tgl_lahir) return '';
  const birth = new Date(form.value.tgl_lahir);
  if (isNaN(birth.getTime())) return '';
  const today = new Date();
  let years = today.getFullYear() - birth.getFullYear();
  let months = today.getMonth() - birth.getMonth();
  if (months < 0 || (months === 0 && today.getDate() < birth.getDate())) {
    years--;
    months += 12;
  }
  if (years < 0) return '';
  return `${years} Tahun ${months} Bulan`;
});

// Helper: Nama Rombel Kelas Terpilih untuk Live ID Card
const getSelectedClassName = () => {
  if (!form.value.current_class_id) return 'Belum Ada Kelas';
  const found = classes.value.find((c: any) => c.id === form.value.current_class_id);
  return found ? (found.nama_kelas || found.nama) : 'Belum Ada Kelas';
};

// Helper: Parent Name / Phone from relations
const getStudentParentName = (student: any) => {
  if (!student.guardians || student.guardians.length === 0) return '';
  const primary = student.guardians.find((g: any) => g.pivot?.is_kontak_utama) || student.guardians[0];
  return primary ? `${primary.nama_lengkap} (${primary.hubungan || primary.pivot?.hubungan || 'Wali'})` : '';
};

const getStudentParentPhone = (student: any) => {
  if (!student.guardians || student.guardians.length === 0) return '';
  const primary = student.guardians.find((g: any) => g.pivot?.is_kontak_utama) || student.guardians[0];
  return primary?.no_hp || '';
};

const getGuardianByRelation = (student: any, hubungan: string) => {
  if (!student || !student.guardians) return null;
  return student.guardians.find((g: any) => g.hubungan === hubungan || g.pivot?.hubungan === hubungan);
};

const fetchReferences = async () => {
  try {
    const res = await api.get('/references');
    if (res.data.success && res.data.data) {
      if (res.data.data.religions) religions.value = res.data.data.religions;
      if (res.data.data.transports) transports.value = res.data.data.transports;
      if (res.data.data.education) educationList.value = res.data.data.education;
      if (res.data.data.occupations) occupationList.value = res.data.data.occupations;
    }
  } catch (err) {
    console.error('Gagal mengambil data referensi', err);
  }
};

const fetchStudents = async () => {
  loading.value = true;
  try {
    const params: any = { status: filterStatus.value };
    if (search.value) params.search = search.value;
    if (filterClass.value) params.class_id = filterClass.value;

    const res = await api.get('/students', { params });
    if (res.data.success) {
      students.value = res.data.data?.data || res.data.data || [];
    }
  } catch (err) {
    console.error('Gagal mengambil data siswa', err);
  } finally {
    loading.value = false;
  }
};

const exportToExcel = async () => {
  exporting.value = true;
  try {
    const params: any = { status: filterStatus.value };
    if (search.value) params.search = search.value;
    if (filterClass.value) params.class_id = filterClass.value;

    const res = await api.get('/students/export', {
      params,
      responseType: 'blob',
    });

    const blob = new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `Master_Data_Siswa_Dapodik_${new Date().toISOString().slice(0, 10)}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    showSuccess('Berhasil!', 'File Excel data siswa berhasil diunduh.');
  } catch (err: any) {
    showError('Gagal Export Excel!', err, 'Terjadi kendala saat mengunduh berkas Excel.');
  } finally {
    exporting.value = false;
  }
};

const fetchClasses = async () => {
  try {
    const res = await api.get('/classes');
    if (res.data.success) {
      classes.value = res.data.data || [];
    }
  } catch (err) {
    console.error('Gagal mengambil data kelas', err);
  }
};

const openDetail = async (student: any) => {
  detailTab.value = 'identitas';
  fetchStudentReportHistory(student.id);
  try {
    const res = await api.get(`/students/${student.id}`);
    if (res.data.success && res.data.data) {
      selectedStudent.value = res.data.data;
      currentView.value = 'detail';
      window.scrollTo({ top: 0, behavior: 'smooth' });
      return;
    }
  } catch (err) {
    console.error('Gagal memuat detail lengkap:', err);
  }
  selectedStudent.value = student;
  currentView.value = 'detail';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const closeDetail = () => {
  currentView.value = 'list';
  selectedStudent.value = null;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const editFromDetail = (student: any) => {
  openEditForm(student);
};

// ==============================================================
// FORM VIEW TRANSITIONS (Halaman Form Baru Menimpa Halaman Tabel)
// ==============================================================
const openCreateForm = () => {
  isEditing.value = false;
  editingId.value = null;
  photoFile.value = null;
  photoPreview.value = '';
  if (fileInputRef.value) fileInputRef.value.value = '';
  form.value = {
    nis: '',
    nisn: '',
    nik: '',
    no_kk: '',
    no_akta_lahir: '',
    nama_lengkap: '',
    nama_panggilan: '',
    jenis_kelamin: 'L',
    tempat_lahir: 'Banyumas',
    tgl_lahir: '2018-01-01',
    agama: religions.value[0]?.id || 1,
    kewarganegaraan: 'WNI',
    anak_ke: 1,
    jumlah_saudara: 0,
    kebutuhan_khusus: '',

    current_class_id: classes.value[0]?.id || null,
    tgl_masuk: new Date().toISOString().split('T')[0],
    status: 'aktif',
    jenis_pendaftaran: 'baru',
    asal_sekolah_tk: '',
    skhun: '',
    no_peserta_un: '',
    no_seri_ijazah: '',

    alamat_jalan: '',
    rt: '',
    rw: '',
    dusun: '',
    desa_kelurahan: '',
    kecamatan: '',
    kab_kota: 'Banyumas',
    provinsi: 'Jawa Tengah',
    kode_pos: '',
    tinggal_bersama: 'orang_tua',
    transportasi: null,
    jarak_ke_sekolah_km: '',
    waktu_tempuh_menit: '',
    latitude: '',
    longitude: '',
    no_hp_siswa: '',
    telepon: '',
    email: '',

    nama_ayah: '',
    nik_ayah: '',
    pekerjaan_ayah: '',
    pendidikan_ayah: '',
    penghasilan_ayah: '',
    no_hp_ayah: '',

    nama_ibu: '',
    nik_ibu: '',
    pekerjaan_ibu: '',
    pendidikan_ibu: '',
    penghasilan_ibu: '',
    no_hp_ibu: '',

    nama_wali: '',
    nik_wali: '',
    pekerjaan_wali: '',
    pendidikan_wali: '',
    penghasilan_wali: '',
    no_hp_wali: '',

    tinggi_cm: '',
    berat_kg: '',
    lingkar_kepala: '',
    golongan_darah: '',
    riwayat_penyakit: '',
    alergi: '',

    penerima_kps: 'Tidak',
    no_kps: '',
    penerima_kip: 'Tidak',
    nomor_kip: '',
    nama_di_kip: '',
    nomor_kks: '',
    layak_pip: 'Tidak',
    alasan_layak_pip: '',
    bank: '',
    nomor_rekening_bank: '',
    rekening_atas_nama: '',
  };
  currentView.value = 'form';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const openEditForm = async (student: any) => {
  isEditing.value = true;
  editingId.value = student.id;

  let s = student;
  try {
    const res = await api.get(`/students/${student.id}`);
    if (res.data.success && res.data.data) {
      s = res.data.data;
    }
  } catch (err) {
    console.error('Gagal mengambil data lengkap untuk edit:', err);
  }

  const ayah = getGuardianByRelation(s, 'ayah');
  const ibu = getGuardianByRelation(s, 'ibu');
  const wali = getGuardianByRelation(s, 'wali');

  photoFile.value = null;
  photoPreview.value = getPhotoUrl(s.foto_url, s.foto_path);
  if (fileInputRef.value) fileInputRef.value.value = '';

  form.value = {
    nis: s.nis || '',
    nisn: s.nisn || '',
    nik: s.nik || '',
    no_kk: s.no_kk || '',
    no_akta_lahir: s.no_akta_lahir || '',
    nama_lengkap: s.nama_lengkap || '',
    nama_panggilan: s.nama_panggilan || '',
    jenis_kelamin: s.jenis_kelamin || 'L',
    tempat_lahir: s.tempat_lahir || 'Banyumas',
    tgl_lahir: toInputDate(s.tgl_lahir),
    agama: s.agama || religions.value[0]?.id || 1,
    kewarganegaraan: s.kewarganegaraan || 'WNI',
    anak_ke: s.anak_ke || 1,
    jumlah_saudara: s.jumlah_saudara || 0,
    kebutuhan_khusus: s.kebutuhan_khusus || '',

    current_class_id: s.class_id || s.current_class_id || s.current_class?.id || s.school_class?.id || null,
    tgl_masuk: toInputDate(s.tgl_masuk) || new Date().toISOString().split('T')[0],
    status: s.status || 'aktif',
    jenis_pendaftaran: s.jenis_pendaftaran || 'baru',
    asal_sekolah_tk: s.asal_sekolah_tk || '',
    skhun: s.skhun || '',
    no_peserta_un: s.no_peserta_un || '',
    no_seri_ijazah: s.no_seri_ijazah || '',

    alamat_jalan: s.alamat_jalan || '',
    rt: s.rt || '',
    rw: s.rw || '',
    dusun: s.dusun || '',
    desa_kelurahan: s.desa_kelurahan || '',
    kecamatan: s.kecamatan || '',
    kab_kota: s.kab_kota || 'Banyumas',
    provinsi: s.provinsi || 'Jawa Tengah',
    kode_pos: s.kode_pos || '',
    tinggal_bersama: s.tinggal_bersama || 'orang_tua',
    transportasi: s.transportasi || null,
    jarak_ke_sekolah_km: s.jarak_ke_sekolah_km || '',
    waktu_tempuh_menit: s.waktu_tempuh_menit || '',
    latitude: s.latitude || '',
    longitude: s.longitude || '',
    no_hp_siswa: s.no_hp_siswa || '',
    telepon: s.telepon || '',
    email: s.email || '',

    // Data Ayah
    nama_ayah: ayah?.nama_lengkap || '',
    nik_ayah: ayah?.nik || '',
    pekerjaan_ayah: ayah?.pekerjaan || '',
    pendidikan_ayah: ayah?.pendidikan_terakhir || '',
    penghasilan_ayah: ayah?.penghasilan_bulanan || '',
    no_hp_ayah: ayah?.no_hp || '',

    // Data Ibu
    nama_ibu: ibu?.nama_lengkap || '',
    nik_ibu: ibu?.nik || '',
    pekerjaan_ibu: ibu?.pekerjaan || '',
    pendidikan_ibu: ibu?.pendidikan_terakhir || '',
    penghasilan_ibu: ibu?.penghasilan_bulanan || '',
    no_hp_ibu: ibu?.no_hp || '',

    // Data Wali
    nama_wali: wali?.nama_lengkap || '',
    nik_wali: wali?.nik || '',
    pekerjaan_wali: wali?.pekerjaan || '',
    pendidikan_wali: wali?.pendidikan_terakhir || '',
    penghasilan_wali: wali?.penghasilan_bulanan || '',
    no_hp_wali: wali?.no_hp || '',

    // Fisik
    tinggi_cm: s.tinggi_cm || '',
    berat_kg: s.berat_kg || '',
    lingkar_kepala: s.lingkar_kepala || '',
    golongan_darah: s.golongan_darah || '',
    riwayat_penyakit: s.riwayat_penyakit || '',
    alergi: s.alergi || '',

    // Kesejahteraan & Bank
    penerima_kps: s.penerima_kps || 'Tidak',
    no_kps: s.no_kps || '',
    penerima_kip: s.penerima_kip || 'Tidak',
    nomor_kip: s.nomor_kip || '',
    nama_di_kip: s.nama_di_kip || '',
    nomor_kks: s.nomor_kks || '',
    layak_pip: s.layak_pip || 'Tidak',
    alasan_layak_pip: s.alasan_layak_pip || '',
    bank: s.bank || '',
    nomor_rekening_bank: s.nomor_rekening_bank || '',
    rekening_atas_nama: s.rekening_atas_nama || '',
  };
  currentView.value = 'form';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const closeForm = () => {
  currentView.value = 'list';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const submitForm = async () => {
  saving.value = true;
  try {
    const payload: any = { ...form.value };
    if (!payload.nisn) delete payload.nisn;
    if (!payload.nik) delete payload.nik;
    if (!payload.no_kk) delete payload.no_kk;
    if (!payload.no_akta_lahir) delete payload.no_akta_lahir;
    if (!payload.nama_panggilan) delete payload.nama_panggilan;
    if (!payload.no_hp_siswa) delete payload.no_hp_siswa;
    if (!payload.telepon) delete payload.telepon;
    if (!payload.email) delete payload.email;

    if (payload.tgl_masuk) {
      payload.tahun_masuk = new Date(payload.tgl_masuk).getFullYear();
    }

    if (payload.current_class_id) {
      payload.class_id = payload.current_class_id;
    }

    const formData = new FormData();
    for (const key in payload) {
      if (payload[key] !== null && payload[key] !== undefined && payload[key] !== '') {
        formData.append(key, payload[key]);
      } else if (key === 'current_class_id' || key === 'class_id') {
        formData.append(key, '');
      }
    }

    if (photoFile.value) {
      formData.append('foto', photoFile.value);
    }

    if (isEditing.value && editingId.value) {
      formData.append('_method', 'PUT');
      const res = await api.post(`/students/${editingId.value}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      if (res.data.success) {
        showSuccess('Berhasil!', 'Data siswa (format Dapodik) berhasil diperbarui.');
        closeForm();
        fetchStudents();
      }
    } else {
      const res = await api.post('/students', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      if (res.data.success) {
        showSuccess('Berhasil!', 'Data siswa baru (format Dapodik) berhasil ditambahkan.');
        closeForm();
        fetchStudents();
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err, 'Terjadi kesalahan saat menyimpan data siswa.');
  } finally {
    saving.value = false;
  }
};

const handleDeleteStudent = async (student: any) => {
  const confirmed = await confirmDeleteWithKeyword(`siswa "${student.nama_lengkap}" (NIS: ${student.nis})`);
  if (!confirmed) return;

  try {
    const res = await api.delete(`/students/${student.id}`);
    if (res.data.success) {
      showSuccess('Berhasil Dihapus!', res.data.message);
      fetchStudents();
    }
  } catch (err: any) {
    showError('Gagal Menghapus!', err, 'Data siswa tidak dapat dihapus.');
  }
};

const handleRouteQuery = async () => {
  if (route.query.search) {
    search.value = String(route.query.search);
  }
  await fetchStudents();

  if (route.query.open_id) {
    const targetId = Number(route.query.open_id);
    const found = students.value.find((s: any) => s.id === targetId);
    if (found) {
      openDetail(found);
    } else {
      try {
        const res = await api.get(`/students/${targetId}`);
        if (res.data.success && res.data.data) {
          openDetail(res.data.data);
        }
      } catch (err) {
        console.error('Gagal membuka detail siswa via open_id:', err);
      }
    }
  }
};

onMounted(async () => {
  await handleRouteQuery();
  fetchClasses();
  fetchReferences();
});

watch(() => route.query.open_id, () => {
  if (route.query.open_id) {
    handleRouteQuery();
  }
});
</script>
