<template>
  <div class="space-y-6 max-w-6xl mx-auto">
    <!-- ALERT NOTIFICATION -->
    <div 
      v-if="message" 
      :class="isSuccess ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'" 
      class="p-4 rounded-2xl border text-sm flex justify-between items-center shadow-xs transition-all"
    >
      <div class="flex items-center gap-2.5">
        <svg v-if="isSuccess" class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <svg v-else class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="font-medium">{{ message }}</span>
      </div>
      <button @click="message = ''" class="font-bold text-xs hover:opacity-70 px-2 py-1">&times;</button>
    </div>

    <!-- PROFILE HERO HEADER -->
    <div class="bg-gradient-to-br from-emerald-900 via-emerald-850 to-teal-950 text-white rounded-3xl p-6 sm:p-8 shadow-xl relative overflow-hidden border border-emerald-700/40">
      <div class="absolute -right-12 -top-12 w-48 h-48 bg-emerald-500/15 rounded-full blur-2xl pointer-events-none"></div>
      <div class="absolute -left-12 -bottom-12 w-48 h-48 bg-teal-500/15 rounded-full blur-2xl pointer-events-none"></div>

      <div class="relative z-10 flex flex-col sm:flex-row items-center sm:items-start gap-6">
        <!-- Avatar / Foto Profil with Edit Overlay -->
        <div class="relative group">
          <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-emerald-700/60 border-2 border-emerald-400/40 overflow-hidden shadow-lg flex items-center justify-center text-3xl font-black text-white shrink-0 relative">
            <img 
              v-if="avatarPreview || profileData.foto_path" 
              :src="avatarPreview || getStorageUrl(profileData.foto_path)" 
              alt="Foto Profil" 
              class="w-full h-full object-cover" 
            />
            <span v-else>{{ (profileData.nama || auth.userName).charAt(0).toUpperCase() }}</span>

            <!-- Spinner saat mengunggah -->
            <div v-if="uploadingAvatar" class="absolute inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center">
              <svg class="animate-spin h-6 w-6 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
          </div>

          <!-- Tombol Aksi: Ganti Foto & Hapus Foto -->
          <div class="absolute -bottom-2 -right-2 flex items-center gap-1">
            <button 
              type="button"
              @click="triggerAvatarInput"
              :disabled="uploadingAvatar"
              class="bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white p-2 rounded-xl shadow-md border border-white/20 transition cursor-pointer"
              title="Ganti Foto Profil"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </button>
            <button 
              v-if="profileData.foto_path"
              type="button"
              @click="removeAvatar"
              :disabled="uploadingAvatar"
              class="bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white p-2 rounded-xl shadow-md border border-white/20 transition cursor-pointer"
              title="Hapus Foto Profil"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>

          <input 
            type="file" 
            ref="avatarInputRef" 
            accept="image/jpeg,image/png,image/webp,image/jpg" 
            class="hidden" 
            @change="onAvatarSelected" 
          />
        </div>

        <!-- Name, Role & School Info -->
        <div class="flex-1 text-center sm:text-left space-y-2">
          <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
            <span class="px-3 py-1 bg-white/10 backdrop-blur-md rounded-full text-xs font-bold uppercase tracking-wider text-emerald-200 border border-white/20">
              {{ auth.isWali ? 'Wali Murid' : (profileData.role ? profileData.role.replace('_', ' ') : 'Guru Pendidik') }}
            </span>
            <span class="px-3 py-1 bg-emerald-500/20 text-emerald-300 rounded-full text-xs font-semibold border border-emerald-400/30">
              {{ auth.isWali ? 'Orang Tua / Wali Siswa' : (auth.isAdmin ? 'Tenaga Kependidikan' : 'Aktif Mengajar') }}
            </span>
          </div>

          <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
            {{ profileData.nama || auth.userName }}
          </h1>

          <p class="text-xs sm:text-sm text-emerald-200/90 font-medium">
            SDI Bina Insan Mandiri Purwokerto • Username: <span class="font-mono font-bold text-white">@{{ profileData.username }}</span>
          </p>

          <!-- Detail PTK Guru / Staf -->
          <div v-if="!auth.isWali && (teacherDetail || staffDetail)" class="pt-2 flex flex-wrap items-center justify-center sm:justify-start gap-4 text-xs text-emerald-100">
            <span v-if="teacherDetail?.nip" class="flex items-center gap-1.5">
              <strong>NIP:</strong> {{ teacherDetail.nip }}
            </span>
            <span v-if="teacherDetail?.nuptk" class="flex items-center gap-1.5">
              <strong>NUPTK:</strong> {{ teacherDetail.nuptk }}
            </span>
            <span v-if="teacherDetail?.jenis_ptk" class="flex items-center gap-1.5">
              <strong>Bidang:</strong> {{ teacherDetail.jenis_ptk }}
            </span>
          </div>

          <!-- Detail Identitas Wali Murid -->
          <div v-else-if="auth.isWali && guardianDetail" class="pt-2 flex flex-wrap items-center justify-center sm:justify-start gap-4 text-xs text-emerald-100">
            <span v-if="guardianDetail?.hubungan" class="flex items-center gap-1.5 capitalize">
              <strong>Hubungan:</strong> {{ guardianDetail.hubungan.replace('_', ' ') }}
            </span>
            <span v-if="guardianDetail?.nik" class="flex items-center gap-1.5 font-mono">
              <strong>NIK:</strong> {{ guardianDetail.nik }}
            </span>
            <span v-if="guardianDetail?.status_hidup" class="flex items-center gap-1.5 capitalize">
              <strong>Status:</strong> {{ guardianDetail.status_hidup }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- TABS NAVIGATION: Biodata, Data Guru / Wali, Keamanan Password -->
    <div class="flex border-b border-slate-200 gap-2 overflow-x-auto">
      <button 
        @click="activeTab = 'biodata'"
        :class="activeTab === 'biodata' ? 'border-emerald-600 text-emerald-700 font-bold bg-white' : 'border-transparent text-slate-500 hover:text-slate-700'"
        class="py-3 px-5 border-b-2 text-sm transition-all flex items-center gap-2 rounded-t-2xl cursor-pointer"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        <span>Biodata & Kontak</span>
      </button>

      <!-- Khusus Guru / Tendik (BUKAN Wali Murid) -->
      <button 
        v-if="!auth.isWali && teacherDetail"
        @click="activeTab = 'kepegawaian'"
        :class="activeTab === 'kepegawaian' ? 'border-emerald-600 text-emerald-700 font-bold bg-white' : 'border-transparent text-slate-500 hover:text-slate-700'"
        class="py-3 px-5 border-b-2 text-sm transition-all flex items-center gap-2 rounded-t-2xl cursor-pointer"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
        <span>Data Kepegawaian</span>
      </button>

      <!-- Khusus Wali Murid -->
      <button 
        v-if="auth.isWali && guardianDetail"
        @click="activeTab = 'wali'"
        :class="activeTab === 'wali' ? 'border-emerald-600 text-emerald-700 font-bold bg-white' : 'border-transparent text-slate-500 hover:text-slate-700'"
        class="py-3 px-5 border-b-2 text-sm transition-all flex items-center gap-2 rounded-t-2xl cursor-pointer"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <span>Data Wali Murid</span>
      </button>

      <button 
        @click="activeTab = 'security'"
        :class="activeTab === 'security' ? 'border-emerald-600 text-emerald-700 font-bold bg-white' : 'border-transparent text-slate-500 hover:text-slate-700'"
        class="py-3 px-5 border-b-2 text-sm transition-all flex items-center gap-2 rounded-t-2xl cursor-pointer"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
        <span>Keamanan & Password</span>
      </button>
    </div>

    <!-- TAB 1: FORM EDIT BIODATA & KONTAK -->
    <div v-if="activeTab === 'biodata'" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
      <div class="border-b border-slate-100 pb-4">
        <h3 class="text-base font-bold text-slate-800">Ubah Informasi Kontak & Akun</h3>
        <p class="text-xs text-slate-500">Perbarui nama tampilan, alamat email, dan nomor WhatsApp aktif Anda</p>
      </div>

      <form @submit.prevent="saveProfile" class="space-y-4 max-w-2xl text-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block font-semibold text-slate-700 mb-1 text-xs uppercase tracking-wider">Nama Lengkap</label>
            <input 
              v-model="profileForm.nama" 
              type="text" 
              required 
              class="w-full border border-slate-200 rounded-2xl p-3 focus:ring-2 focus:ring-emerald-500 focus:outline-none" 
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1 text-xs uppercase tracking-wider">Username Sistem</label>
            <input 
              :value="profileData.username" 
              type="text" 
              disabled 
              class="w-full border border-slate-200 rounded-2xl p-3 bg-slate-50 text-slate-500 cursor-not-allowed font-mono text-xs" 
            />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block font-semibold text-slate-700 mb-1 text-xs uppercase tracking-wider">Alamat Email</label>
            <input 
              v-model="profileForm.email" 
              type="email" 
              placeholder="nama@email.com" 
              class="w-full border border-slate-200 rounded-2xl p-3 focus:ring-2 focus:ring-emerald-500 focus:outline-none" 
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1 text-xs uppercase tracking-wider">No. WhatsApp / HP</label>
            <input 
              v-model="profileForm.no_hp" 
              type="text" 
              placeholder="08123456789" 
              class="w-full border border-slate-200 rounded-2xl p-3 focus:ring-2 focus:ring-emerald-500 focus:outline-none" 
            />
          </div>
        </div>

        <div class="pt-4 flex items-center justify-end gap-3">
          <button 
            type="submit" 
            :disabled="savingProfile"
            class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold rounded-2xl shadow-md transition disabled:opacity-50 flex items-center gap-2 cursor-pointer"
          >
            <svg v-if="savingProfile" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ savingProfile ? 'Menyimpan...' : 'Simpan Perubahan Biodata' }}</span>
          </button>
        </div>
      </form>
    </div>    <!-- TAB 2A: DATA KEPEGAWAIAN GURU (READ-ONLY DETAILS, KHUSUS GURU/TENDIK) -->
    <div v-if="activeTab === 'kepegawaian' && !auth.isWali && (teacherDetail || staffDetail)" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
      <div class="border-b border-slate-100 pb-4 flex justify-between items-center">
        <div>
          <h3 class="text-base font-bold text-slate-800">Data Pokok Kepegawaian & Kependidikan</h3>
          <p class="text-xs text-slate-500">Informasi resmi dari Data Pokok Pendidikan (Dapodik) sekolah</p>
        </div>
        <span class="text-[11px] font-bold text-slate-400 bg-slate-100 px-3 py-1 rounded-full">
          Dikelola Administrator
        </span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 text-xs">
        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
          <span class="text-slate-400 font-semibold uppercase text-[10px]">Nomor Induk Kependudukan (NIK)</span>
          <div class="font-bold text-slate-800 text-sm font-mono">{{ teacherDetail?.nik || staffDetail?.nik || '-' }}</div>
        </div>

        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
          <span class="text-slate-400 font-semibold uppercase text-[10px]">NUPTK</span>
          <div class="font-bold text-slate-800 text-sm font-mono">{{ teacherDetail?.nuptk || staffDetail?.nuptk || '-' }}</div>
        </div>

        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
          <span class="text-slate-400 font-semibold uppercase text-[10px]">Status Kepegawaian</span>
          <div class="font-bold text-slate-800 text-sm">{{ teacherDetail?.status_kepegawaian || staffDetail?.status_kepegawaian || 'Pegawai Tetap / Kontrak' }}</div>
        </div>

        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
          <span class="text-slate-400 font-semibold uppercase text-[10px]">Jenis PTK / Jabatan</span>
          <div class="font-bold text-slate-800 text-sm">{{ teacherDetail?.jenis_ptk || teacherDetail?.jabatan || staffDetail?.jabatan || 'Tenaga Kependidikan / Administrasi' }}</div>
        </div>

        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
          <span class="text-slate-400 font-semibold uppercase text-[10px]">Tugas Tambahan</span>
          <div class="font-bold text-slate-800 text-sm">{{ teacherDetail?.tugas_tambahan || staffDetail?.tugas_tambahan || '-' }}</div>
        </div>

        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
          <span class="text-slate-400 font-semibold uppercase text-[10px]">Jam Mengajar / Minggu</span>
          <div class="font-bold text-slate-800 text-sm">{{ teacherDetail ? (teacherDetail.jam_mengajar_per_minggu ? teacherDetail.jam_mengajar_per_minggu + ' Jam' : '-') : (staffDetail ? '40 Jam / Minggu (Penuh Waktu)' : '-') }}</div>
        </div>

        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
          <span class="text-slate-400 font-semibold uppercase text-[10px]">Pendidikan Terakhir</span>
          <div class="font-bold text-slate-800 text-sm">
            {{ teacherDetail?.pendidikan_terakhir || staffDetail?.pendidikan_terakhir || 'S1' }} {{ teacherDetail?.jurusan_prodi ? ' - ' + teacherDetail.jurusan_prodi : '' }}
          </div>
          <div class="text-[11px] text-slate-500">{{ teacherDetail?.institusi || '' }}</div>
        </div>

        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
          <span class="text-slate-400 font-semibold uppercase text-[10px]">Alamat Domisili</span>
          <div class="font-bold text-slate-800 text-sm">{{ teacherDetail?.alamat_jalan || staffDetail?.alamat_jalan || 'Purwokerto, Banyumas' }}</div>
          <div class="text-[11px] text-slate-500">
            {{ [(teacherDetail?.desa_kelurahan || staffDetail?.desa_kelurahan), (teacherDetail?.kecamatan || staffDetail?.kecamatan), (teacherDetail?.kab_kota || staffDetail?.kab_kota)].filter(Boolean).join(', ') }}
          </div>
        </div>

        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
          <span class="text-slate-400 font-semibold uppercase text-[10px]">Kontak Darurat</span>
          <div class="font-bold text-slate-800 text-sm">{{ teacherDetail?.kontak_darurat_nama || staffDetail?.kontak_darurat_nama || '-' }}</div>
          <div class="text-[11px] text-slate-500">{{ teacherDetail?.kontak_darurat_hp || staffDetail?.kontak_darurat_hp || '-' }} ({{ teacherDetail?.kontak_darurat_hubungan || staffDetail?.kontak_darurat_hubungan || '-' }})</div>
        </div>
      </div>
    </div>

    <!-- TAB 2B: DATA KEPENDUDUKAN & KELUARGA (KHUSUS WALI MURID) -->
    <div v-if="activeTab === 'wali' && auth.isWali && guardianDetail" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
      <div class="border-b border-slate-100 pb-4 flex justify-between items-center">
        <div>
          <h3 class="text-base font-bold text-slate-800">Data Kependudukan Orang Tua / Wali</h3>
          <p class="text-xs text-slate-500">Informasi identitas resmi wali murid yang terhubung dengan data ananda</p>
        </div>
        <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full">
          Terverifikasi Sekolah
        </span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 text-xs">
        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
          <span class="text-slate-400 font-semibold uppercase text-[10px]">Hubungan Keluarga</span>
          <div class="font-bold text-slate-800 text-sm capitalize">{{ guardianDetail.hubungan?.replace('_', ' ') || 'Orang Tua' }}</div>
        </div>

        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
          <span class="text-slate-400 font-semibold uppercase text-[10px]">Nomor Induk Kependudukan (NIK)</span>
          <div class="font-bold text-slate-800 text-sm font-mono">{{ guardianDetail.nik || '-' }}</div>
        </div>

        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
          <span class="text-slate-400 font-semibold uppercase text-[10px]">Tempat, Tanggal Lahir</span>
          <div class="font-bold text-slate-800 text-sm">
            {{ guardianDetail.tempat_lahir || 'Purwokerto' }}{{ guardianDetail.tgl_lahir ? ', ' + guardianDetail.tgl_lahir : '' }}
          </div>
        </div>

        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
          <span class="text-slate-400 font-semibold uppercase text-[10px]">Nomor WhatsApp</span>
          <div class="font-bold text-slate-800 text-sm font-mono">{{ guardianDetail.no_whatsapp || guardianDetail.no_hp || '-' }}</div>
        </div>

        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
          <span class="text-slate-400 font-semibold uppercase text-[10px]">Status Hidup</span>
          <div class="font-bold text-slate-800 text-sm capitalize">{{ guardianDetail.status_hidup || 'Hidup' }}</div>
        </div>

        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
          <span class="text-slate-400 font-semibold uppercase text-[10px]">Alamat Rumah</span>
          <div class="font-bold text-slate-800 text-sm">{{ guardianDetail.alamat_jalan || '-' }}</div>
          <div class="text-[11px] text-slate-500">
            {{ [guardianDetail.desa_kelurahan, guardianDetail.kecamatan, guardianDetail.kab_kota, guardianDetail.provinsi].filter(Boolean).join(', ') }}
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 3: FORM GANTI KATA SANDI / PASSWORD -->
    <div v-if="activeTab === 'security'" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
      <div class="border-b border-slate-100 pb-4">
        <h3 class="text-base font-bold text-slate-800">Ganti Kata Sandi Akun</h3>
        <p class="text-xs text-slate-500">Pastikan menggunakan kombinasi kata sandi yang aman dan tidak mudah ditebak</p>
      </div>

      <form @submit.prevent="changePassword" class="space-y-4 max-w-xl text-sm">
        <div>
          <label class="block font-semibold text-slate-700 mb-1 text-xs uppercase tracking-wider">Kata Sandi Saat Ini</label>
          <input 
            v-model="passwordForm.current_password" 
            type="password" 
            required 
            placeholder="••••••••" 
            class="w-full border border-slate-200 rounded-2xl p-3 focus:ring-2 focus:ring-emerald-500 focus:outline-none" 
          />
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1 text-xs uppercase tracking-wider">Kata Sandi Baru</label>
          <input 
            v-model="passwordForm.new_password" 
            type="password" 
            required 
            minlength="8" 
            placeholder="Minimal 8 karakter" 
            class="w-full border border-slate-200 rounded-2xl p-3 focus:ring-2 focus:ring-emerald-500 focus:outline-none" 
          />
        </div>

        <div>
          <label class="block font-semibold text-slate-700 mb-1 text-xs uppercase tracking-wider">Konfirmasi Kata Sandi Baru</label>
          <input 
            v-model="passwordForm.new_password_confirmation" 
            type="password" 
            required 
            minlength="8" 
            placeholder="Ulangi kata sandi baru" 
            class="w-full border border-slate-200 rounded-2xl p-3 focus:ring-2 focus:ring-emerald-500 focus:outline-none" 
          />
        </div>

        <div class="pt-4 flex items-center justify-end gap-3">
          <button 
            type="submit" 
            :disabled="changingPassword"
            class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold rounded-2xl shadow-md transition disabled:opacity-50 flex items-center gap-2 cursor-pointer"
          >
            <svg v-if="changingPassword" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ changingPassword ? 'Menyimpan...' : 'Perbarui Kata Sandi' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import api from '../services/api';
import { showConfirm } from '../services/swal';

const auth = useAuthStore();
const activeTab = ref<'biodata' | 'kepegawaian' | 'wali' | 'security'>('biodata');

const profileData = ref<any>({});
const teacherDetail = ref<any>(null);
const staffDetail = ref<any>(null);
const guardianDetail = ref<any>(null);

const profileForm = ref({
  nama: '',
  email: '',
  no_hp: '',
});

const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
});

const avatarInputRef = ref<HTMLInputElement | null>(null);
const avatarPreview = ref<string | null>(null);
const selectedAvatarFile = ref<File | null>(null);
const uploadingAvatar = ref(false);

const message = ref('');
const isSuccess = ref(true);
const savingProfile = ref(false);
const changingPassword = ref(false);

const showNotification = (text: string, success = true) => {
  message.value = text;
  isSuccess.value = success;
  setTimeout(() => {
    message.value = '';
  }, 5000);
};

const getStorageUrl = (path: string | null) => {
  if (!path) return '';
  if (path.startsWith('http://') || path.startsWith('https://')) return path;
  const cleanPath = path.startsWith('/') ? path.slice(1) : path;
  if (cleanPath.startsWith('storage/')) {
    return `/${cleanPath}`;
  }
  return `/storage/${cleanPath}`;
};

const triggerAvatarInput = () => {
  avatarInputRef.value?.click();
};

const onAvatarSelected = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    const file = target.files[0];
    selectedAvatarFile.value = file;
    avatarPreview.value = URL.createObjectURL(file);
    // Langsung simpan foto profil ke server
    uploadAvatar(file);
  }
};

const uploadAvatar = async (file: File) => {
  uploadingAvatar.value = true;
  const formData = new FormData();
  formData.append('foto', file);
  formData.append('_method', 'PUT');

  try {
    const res = await api.post('/me', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    if (res.data.success) {
      showNotification('Foto profil berhasil diperbarui!');
      if (res.data.data?.foto_path) {
        profileData.value.foto_path = res.data.data.foto_path;
      }
      avatarPreview.value = null;
      await auth.fetchProfile();
    }
  } catch (err: any) {
    avatarPreview.value = null;
    showNotification(err.response?.data?.message || 'Gagal mengunggah foto profil', false);
  } finally {
    uploadingAvatar.value = false;
    if (avatarInputRef.value) {
      avatarInputRef.value.value = '';
    }
  }
};

const removeAvatar = async () => {
  const confirmed = await showConfirm(
    'Hapus Foto Profil',
    'Apakah Anda yakin ingin menghapus foto profil ini?',
    'Ya, Hapus'
  );
  if (!confirmed) return;
  uploadingAvatar.value = true;
  try {
    const res = await api.delete('/me/photo');
    if (res.data.success) {
      showNotification('Foto profil berhasil dihapus.');
      profileData.value.foto_path = null;
      avatarPreview.value = null;
      await auth.fetchProfile();
    }
  } catch (err: any) {
    showNotification(err.response?.data?.message || 'Gagal menghapus foto profil', false);
  } finally {
    uploadingAvatar.value = false;
  }
};

const fetchProfileData = async () => {
  try {
    const res = await api.get('/me');
    if (res.data.success) {
      profileData.value = res.data.data;
      if (auth.isWali) {
        guardianDetail.value = res.data.data.profile_detail;
        teacherDetail.value = null;
        staffDetail.value = null;
      } else if (res.data.data.profile_detail?.jenis_ptk || res.data.data.profile_detail?.nuptk !== undefined) {
        teacherDetail.value = res.data.data.profile_detail;
        staffDetail.value = null;
        guardianDetail.value = null;
      } else {
        staffDetail.value = res.data.data.profile_detail;
        teacherDetail.value = null;
        guardianDetail.value = null;
      }

      profileForm.value.nama = res.data.data.nama || '';
      profileForm.value.email = res.data.data.email || '';
      profileForm.value.no_hp = res.data.data.no_hp || '';
    }
  } catch (err) {
    console.error('Gagal mengambil data profil', err);
  }
};

const saveProfile = async () => {
  savingProfile.value = true;
  try {
    const res = await api.put('/me', profileForm.value);
    if (res.data.success) {
      showNotification('Profil berhasil diperbarui!');
      auth.fetchProfile();
    }
  } catch (err: any) {
    showNotification(err.response?.data?.message || 'Gagal memperbarui profil', false);
  } finally {
    savingProfile.value = false;
  }
};

const changePassword = async () => {
  if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
    showNotification('Konfirmasi kata sandi baru tidak cocok.', false);
    return;
  }

  changingPassword.value = true;
  try {
    const res = await api.put('/me/password', passwordForm.value);
    if (res.data.success) {
      showNotification('Kata sandi berhasil diperbarui!');
      passwordForm.value = {
        current_password: '',
        new_password: '',
        new_password_confirmation: '',
      };
    }
  } catch (err: any) {
    showNotification(err.response?.data?.message || 'Gagal mengubah kata sandi', false);
  } finally {
    changingPassword.value = false;
  }
};

onMounted(() => {
  fetchProfileData();
});
</script>
