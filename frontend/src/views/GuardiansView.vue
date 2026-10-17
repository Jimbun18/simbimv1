<template>
  <div class="space-y-5 sm:space-y-6">
    <!-- ============================================================== -->
    <!-- VIEW 1: DAFTAR TABEL WALI MURID (LIST VIEW)                    -->
    <!-- ============================================================== -->
    <div v-if="currentView === 'list'" class="space-y-5 sm:space-y-6">
      <!-- 1. Header & Summary Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
        <div>
          <h1 class="text-lg sm:text-xl font-bold text-gray-900 leading-tight">Manajemen Wali Murid & Akun Portal</h1>
          <p class="text-xs text-gray-500 mt-0.5">Kelola data orang tua/wali siswa, akun login portal wali murid, serta relasi saudara kandung (kakak-adik).</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button
            @click="openCreateForm"
            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/20 transition cursor-pointer active:scale-95"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Tambah Wali Murid</span>
          </button>
        </div>
      </div>

      <!-- 2. Stat Highlights (3 Kartu Ringkasan Riil) -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
            👨‍👩‍👧‍👦
          </div>
          <div>
            <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Total Wali Murid</div>
            <div class="text-xl font-black text-gray-900 mt-0.5">
              {{ stats.total_guardians || meta.total || guardians.length }} Orang
            </div>
          </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shrink-0">
            📱
          </div>
          <div>
            <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Akun Portal Aktif</div>
            <div class="text-xl font-black text-gray-900 mt-0.5">
              {{ stats.active_accounts }} Akun
            </div>
          </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-xs flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
            🎒
          </div>
          <div>
            <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Siswa Terhubung</div>
            <div class="text-xl font-black text-gray-900 mt-0.5">
              {{ stats.connected_students || totalConnectedStudents }} Siswa
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Toolbar & Filter -->
      <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex flex-col md:flex-row gap-3 items-center justify-between">
        <div class="flex-1 w-full md:w-auto relative">
          <input
            v-model="search"
            @input="debouncedFetch"
            type="text"
            placeholder="Cari berdasarkan nama wali, NIK, nama siswa/NIS, atau WhatsApp..."
            class="w-full px-4 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none transition"
          />
        </div>

        <div class="flex flex-wrap gap-2 w-full md:w-auto">
          <select
            v-model="filterHubungan"
            @change="fetchGuardians(1)"
            class="px-3 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-gray-700"
          >
            <option value="">Semua Hubungan</option>
            <option value="ayah">Ayah</option>
            <option value="ibu">Ibu</option>
            <option value="wali">Wali</option>
          </select>

          <select
            v-model="filterAkun"
            class="px-3 py-2.5 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-gray-700"
          >
            <option value="">Semua Status Akun</option>
            <option value="has_account">Sudah Punya Akun</option>
            <option value="no_account">Belum Ada Akun</option>
          </select>
        </div>
      </div>

      <!-- 4. Table Wali Murid -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-gray-600 min-w-[850px]">
            <thead class="bg-gray-50 text-[11px] font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
              <tr>
                <th class="py-3.5 px-4 w-12 text-center">No</th>
                <th class="py-3.5 px-4">Nama Wali Murid</th>
                <th class="py-3.5 px-4">Kontak & NIK</th>
                <th class="py-3.5 px-4">Akun Portal SIM-BIM</th>
                <th class="py-3.5 px-4">Anak Tanggungan (Kakak-Adik)</th>
                <th class="py-3.5 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="loading">
                <td colspan="6" class="py-12 text-center text-gray-400">
                  <div class="inline-block animate-spin w-6 h-6 border-2 border-emerald-600 border-t-transparent rounded-full mb-2"></div>
                  <div>Memuat data wali murid...</div>
                </td>
              </tr>

              <tr v-else-if="filteredGuardians.length === 0">
                <td colspan="6" class="py-12 text-center text-gray-400">
                  Belum ada data wali murid yang sesuai dengan filter pencarian.
                </td>
              </tr>

              <tr v-for="(g, idx) in filteredGuardians" :key="g.id" class="hover:bg-gray-50/70 transition">
                <td class="py-3.5 px-4 text-gray-400 font-mono text-center">{{ idx + 1 }}</td>
                
                <!-- Nama & Hubungan -->
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-xs shrink-0">
                      {{ g.nama_lengkap.charAt(0) }}
                    </div>
                    <div>
                      <div class="font-bold text-gray-900">{{ g.nama_lengkap }}</div>
                      <span 
                        class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                        :class="g.hubungan === 'ayah' ? 'bg-blue-50 text-blue-700 border border-blue-200' : (g.hubungan === 'ibu' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200')"
                      >
                        {{ g.hubungan }}
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Kontak & NIK -->
                <td class="py-3.5 px-4">
                  <div class="space-y-0.5">
                    <div class="font-mono text-gray-800 font-medium flex items-center gap-1.5">
                      <span class="text-emerald-600 font-bold">WA:</span> {{ g.no_whatsapp || g.no_hp || '-' }}
                    </div>
                    <div class="font-mono text-[11px] text-gray-400">NIK: {{ g.nik || '-' }}</div>
                    <div class="text-[11px] text-gray-500 truncate max-w-xs">{{ g.alamat_jalan || '-' }}</div>
                  </div>
                </td>

                <!-- Akun Portal SIM-BIM -->
                <td class="py-3.5 px-4">
                  <div v-if="g.user" class="space-y-1">
                    <div class="flex items-center gap-1.5">
                      <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                      <span class="font-mono font-bold text-gray-800 text-[11px]">@{{ g.user.username }}</span>
                    </div>
                    <button
                      @click="handleResetPassword(g)"
                      class="text-[10px] text-sky-600 hover:text-sky-800 underline block cursor-pointer"
                    >
                      Reset Password
                    </button>
                  </div>
                  <div v-else>
                    <button
                      @click="handleCreateAccount(g)"
                      class="px-2.5 py-1 bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 rounded-lg text-[10px] font-bold transition cursor-pointer"
                    >
                      + Buat Akun
                    </button>
                  </div>
                </td>

                <!-- Anak Tanggungan (Kakak-Adik) -->
                <td class="py-3.5 px-4">
                  <div class="space-y-1.5">
                    <div v-if="g.students && g.students.length > 0" class="flex flex-wrap gap-1.5">
                      <button
                        v-for="s in g.students"
                        :key="s.id"
                        type="button"
                        @click="goToStudent(s)"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 hover:text-emerald-900 border border-emerald-200/80 hover:border-emerald-300 rounded-lg text-[11px] font-semibold transition cursor-pointer group shadow-2xs"
                        :title="`Klik untuk membuka profil data siswa: ${s.nama_lengkap} (${s.nis})`"
                      >
                        <span>🎒</span>
                        <span class="group-hover:underline font-bold">{{ s.nama_lengkap }}</span>
                        <span class="text-emerald-600 font-mono text-[10px]">({{ s.nis }})</span>
                        <svg class="w-3 h-3 text-emerald-600 transition-transform group-hover:translate-x-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                      </button>
                    </div>
                    <div v-else class="text-gray-400 text-[11px] italic">
                      Belum terhubung ke siswa
                    </div>
                    <button
                      @click="openModalManageStudents(g)"
                      class="text-[10px] font-bold text-emerald-700 hover:text-emerald-900 bg-white hover:bg-emerald-50 border border-emerald-200 rounded-lg px-2 py-0.5 transition cursor-pointer flex items-center gap-1"
                    >
                      <span>🔗 Kelola Anak (Kakak-Adik)</span>
                    </button>
                  </div>
                </td>

                <!-- Aksi -->
                <td class="py-3.5 px-4 text-right">
                  <div class="inline-flex items-center gap-1">
                    <button
                      @click="openEditForm(g)"
                      class="p-1.5 text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition cursor-pointer"
                      title="Edit Biodata Wali"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>

                    <button
                      @click="handleDelete(g)"
                      class="p-1.5 text-gray-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer"
                      title="Hapus Data Wali"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- 5. Footer Pagination & Baris Data -->
        <div class="px-5 py-3.5 bg-gray-50/80 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
          <div class="text-gray-500">
            Menampilkan <strong class="text-gray-800">{{ meta.total > 0 ? (meta.current_page - 1) * meta.per_page + 1 : 0 }} - {{ Math.min(meta.current_page * meta.per_page, meta.total) }}</strong> dari <strong class="text-gray-800">{{ meta.total }}</strong> wali murid
          </div>

          <div class="flex items-center gap-3">
            <div class="flex items-center gap-1.5 text-gray-500">
              <span>Baris per halaman:</span>
              <select
                v-model="perPage"
                @change="fetchGuardians(1)"
                class="px-2 py-1 bg-white border border-gray-200 rounded-lg text-xs outline-none focus:ring-1 focus:ring-emerald-500"
              >
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
                <option :value="-1">Semua</option>
              </select>
            </div>

            <div class="flex items-center gap-1">
              <button
                :disabled="meta.current_page <= 1"
                @click="fetchGuardians(meta.current_page - 1)"
                class="px-2.5 py-1 bg-white border border-gray-200 hover:bg-gray-100 disabled:opacity-40 disabled:hover:bg-white rounded-lg transition font-medium cursor-pointer"
              >
                Prev
              </button>
              <span class="px-2 font-mono font-bold text-gray-700">
                {{ meta.current_page }} / {{ meta.last_page || 1 }}
              </span>
              <button
                :disabled="meta.current_page >= meta.last_page"
                @click="fetchGuardians(meta.current_page + 1)"
                class="px-2.5 py-1 bg-white border border-gray-200 hover:bg-gray-100 disabled:opacity-40 disabled:hover:bg-white rounded-lg transition font-medium cursor-pointer"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- VIEW 2: HALAMAN FORMULIR WALI PENUH (SUB-PAGE REPLACING LIST)  -->
    <!-- Menggantikan popup modal menjadi halaman form penuh & lega     -->
    <!-- ============================================================== -->
    <div v-else-if="currentView === 'form'" class="space-y-6 animate-in fade-in duration-200">
      <!-- Top Navigation & Action Banner -->
      <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
          <button
            @click="closeForm"
            type="button"
            class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center justify-center shrink-0 shadow-xs group cursor-pointer"
            title="Kembali ke Daftar Wali Murid"
          >
            <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
              <span>Wali Murid</span>
              <span>/</span>
              <button type="button" @click="closeForm" class="hover:text-emerald-600 hover:underline cursor-pointer">Data Wali Murid</button>
              <span>/</span>
              <span class="text-emerald-700 font-bold">{{ isEditing ? 'Edit Wali' : 'Tambah Wali Baru' }}</span>
            </div>
            <h1 class="text-lg sm:text-2xl font-black text-gray-900 leading-tight">
              {{ isEditing ? `Edit Data Wali: ${form.nama_lengkap || '...'}` : 'Formulir Wali Murid Baru' }}
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              {{ isEditing ? 'Perbarui identitas orang tua/wali murid, nomor kontak WhatsApp, dan domisili.' : 'Lengkapi data orang tua/wali murid untuk menghubungkannya ke akun portal dan siswa.' }}
            </p>
          </div>
        </div>

        <!-- Top Action Buttons -->
        <div class="flex items-center gap-2.5 self-end sm:self-center">
          <button
            @click="closeForm"
            type="button"
            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition cursor-pointer"
          >
            Batal
          </button>
          <button
            @click="submitForm"
            :disabled="saving"
            type="button"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/25 transition disabled:opacity-50 active:scale-95 cursor-pointer"
          >
            <svg v-if="saving" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ saving ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Data Wali') }}</span>
          </button>
        </div>
      </div>

      <!-- Main Form Grid: 12 Cols -->
      <form @submit.prevent="submitForm" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- LEFT COLUMN: Live Preview Card Wali (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
          <div class="bg-gradient-to-br from-slate-900 via-emerald-950 to-slate-900 p-5 rounded-2xl text-white shadow-lg space-y-4">
            <div class="flex items-center justify-between text-emerald-400 text-xs font-bold">
              <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                KARTU WALI MURID (PREVIEW)
              </span>
              <span class="font-mono text-[10px] text-slate-400">PORTAL SIM-BIM</span>
            </div>

            <div class="flex items-center gap-3.5 pt-1">
              <div class="w-14 h-14 rounded-xl overflow-hidden bg-white/10 border border-white/20 shrink-0 flex items-center justify-center text-xl">
                👨‍👩‍👦
              </div>
              <div class="min-w-0 flex-1">
                <h4 class="text-sm font-bold truncate text-white">
                  {{ form.nama_lengkap || 'Nama Lengkap Wali' }}
                </h4>
                <p class="text-[11px] font-mono text-emerald-300">
                  WA: {{ form.no_hp || '08----------' }}
                </p>
                <div class="flex items-center gap-1.5 mt-1">
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider bg-white/15 text-slate-200">
                    {{ form.hubungan }}
                  </span>
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-500/20 text-emerald-300">
                    {{ form.status_hidup === 'hidup' ? 'Aktif' : 'Meninggal' }}
                  </span>
                </div>
              </div>
            </div>

            <div class="pt-3 border-t border-white/10 text-[11px] text-slate-300 flex justify-between items-center">
              <span>Wilayah Domisili:</span>
              <span class="font-bold text-emerald-300">{{ form.kab_kota || 'Banyumas' }}</span>
            </div>
          </div>

          <!-- Petunjuk Card -->
          <div class="bg-emerald-50/70 p-4 rounded-2xl border border-emerald-100 text-xs text-emerald-950 space-y-1.5">
            <div class="font-bold flex items-center gap-1.5 text-emerald-800">
              <span>💡</span> Penggunaan Akun Portal
            </div>
            <p class="text-[11px] text-emerald-700 leading-relaxed">
              Nomor WhatsApp akan digunakan wali murid untuk masuk ke <strong>Portal Wali</strong> untuk memantau kehadiran siswa secara realtime, rekapan presensi, dan informasi KBM.
            </p>
          </div>
        </div>

        <!-- RIGHT COLUMN: Form Sections (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
          
          <!-- CARD 1: Identitas & Hubungan Wali -->
          <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                  <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">1</span>
                  Identitas Pokok Wali Murid
                </h3>
                <p class="text-xs text-gray-500 mt-0.5 ml-8">Hubungan dengan siswa, nama lengkap, NIK, dan status.</p>
              </div>
              <span class="text-[10px] text-rose-500 font-semibold">* Wajib diisi</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Hubungan Keluarga <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-3 gap-2">
                  <button
                    type="button"
                    @click="form.hubungan = 'ayah'"
                    :class="form.hubungan === 'ayah' ? 'bg-blue-50 border-blue-400 text-blue-700 font-bold shadow-xs' : 'bg-gray-50 border-gray-200 text-gray-600'"
                    class="py-2 px-2 rounded-xl border text-xs flex items-center justify-center gap-1 transition cursor-pointer"
                  >
                    <span>Ayah</span>
                  </button>
                  <button
                    type="button"
                    @click="form.hubungan = 'ibu'"
                    :class="form.hubungan === 'ibu' ? 'bg-rose-50 border-rose-400 text-rose-700 font-bold shadow-xs' : 'bg-gray-50 border-gray-200 text-gray-600'"
                    class="py-2 px-2 rounded-xl border text-xs flex items-center justify-center gap-1 transition cursor-pointer"
                  >
                    <span>Ibu</span>
                  </button>
                  <button
                    type="button"
                    @click="form.hubungan = 'wali'"
                    :class="form.hubungan === 'wali' ? 'bg-amber-50 border-amber-400 text-amber-700 font-bold shadow-xs' : 'bg-gray-50 border-gray-200 text-gray-600'"
                    class="py-2 px-2 rounded-xl border text-xs flex items-center justify-center gap-1 transition cursor-pointer"
                  >
                    <span>Wali</span>
                  </button>
                </div>
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                <input
                  v-model="form.nama_lengkap"
                  type="text"
                  required
                  placeholder="Contoh: Hendra Setiawan"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold text-gray-800 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">NIK (16 Digit KTP)</label>
                <input
                  v-model="form.nik"
                  type="text"
                  maxlength="16"
                  placeholder="Contoh: 330201..."
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Status Keberadaan</label>
                <select
                  v-model="form.status_hidup"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white transition"
                >
                  <option value="hidup">Masih Hidup</option>
                  <option value="meninggal">Meninggal Dunia</option>
                </select>
              </div>
            </div>
          </div>

          <!-- CARD 2: Kontak & Tempat Tanggal Lahir -->
          <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <div class="border-b border-gray-100 pb-3">
              <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">2</span>
                Kontak & Identitas Kelahiran
              </h3>
              <p class="text-xs text-gray-500 mt-0.5 ml-8">Nomor WhatsApp aktif digunakan untuk notifikasi kehadiran dan autentikasi portal.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">No. WhatsApp / HP Aktif <span class="text-rose-500">*</span></label>
                <input
                  v-model="form.no_hp"
                  type="text"
                  required
                  placeholder="Contoh: 081234567890"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono font-bold text-gray-800 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Alamat Email</label>
                <input
                  v-model="form.email"
                  type="email"
                  placeholder="wali@gmail.com"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Tempat Lahir</label>
                <input
                  v-model="form.tempat_lahir"
                  type="text"
                  placeholder="Purwokerto"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Tanggal Lahir</label>
                <input
                  v-model="form.tgl_lahir"
                  type="date"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>
            </div>
          </div>

          <!-- CARD 3: Alamat Domisili -->
          <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <div class="border-b border-gray-100 pb-3">
              <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">3</span>
                Alamat Domisili Tempat Tinggal
              </h3>
              <p class="text-xs text-gray-500 mt-0.5 ml-8">Alamat rumah tempat tinggal orang tua/wali murid saat ini.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Alamat Jalan / RT RW</label>
                <input
                  v-model="form.alamat_jalan"
                  type="text"
                  placeholder="Jl. Raya No. ..."
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Kabupaten / Kota</label>
                <input
                  v-model="form.kab_kota"
                  type="text"
                  placeholder="Banyumas"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
                />
              </div>
            </div>

            <!-- Akun Portal Otomatis (Saat Tambah Baru) -->
            <div v-if="!isEditing" class="p-4 bg-emerald-50 rounded-2xl border border-emerald-100 flex items-center gap-3">
              <input
                type="checkbox"
                id="createAcc"
                v-model="form.create_user_account"
                class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-gray-300"
              />
              <label for="createAcc" class="text-xs font-semibold text-emerald-950 cursor-pointer">
                Buatkan otomatis akun login Portal Wali Murid (Password default: <code>Password123!</code>)
              </label>
            </div>
          </div>

          <!-- Bottom Sticky Action Bar -->
          <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between gap-4">
            <button
              @click="closeForm"
              type="button"
              class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition flex items-center gap-2 cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              <span>Kembali ke Daftar Wali</span>
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
                :disabled="saving"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/25 transition disabled:opacity-50 active:scale-95 cursor-pointer"
              >
                <svg v-if="saving" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ saving ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Data Wali') }}</span>
              </button>
            </div>
          </div>

        </div>
      </form>
    </div>

    <!-- 6. MODAL KELOLA RELASI ANAK (KASUS KAKAK-ADIK) -->
    <div v-if="selectedGuardianForStudents" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
      <div class="bg-white rounded-3xl max-w-xl w-full max-h-[90vh] overflow-hidden shadow-2xl border border-gray-100 flex flex-col">
        <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between">
          <div>
            <h3 class="text-base font-bold text-gray-900">Kelola Anak Tanggungan</h3>
            <p class="text-xs text-gray-500">
              Hubungkan siswa (saudara kandung / kakak-adik) ke akun <strong>{{ selectedGuardianForStudents.nama_lengkap }}</strong>
            </p>
          </div>
          <button @click="selectedGuardianForStudents = null" class="text-gray-400 hover:text-gray-600 text-lg font-bold cursor-pointer">&times;</button>
        </div>

        <div class="p-4 border-b border-gray-100">
          <input
            v-model="studentSearchQuery"
            type="text"
            placeholder="Ketik nama atau NIS anak untuk mencari..."
            class="w-full px-3.5 py-2 bg-gray-50 text-xs rounded-xl border border-gray-200 focus:bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
          />
        </div>

        <div class="overflow-y-auto p-4 space-y-2 flex-1 max-h-96">
          <div
            v-for="s in filteredAllStudents"
            :key="s.id"
            @click="toggleStudentSelection(s.id)"
            class="p-3 rounded-2xl border transition flex items-center justify-between cursor-pointer"
            :class="selectedStudentIds.includes(s.id) ? 'bg-emerald-50/70 border-emerald-300 shadow-2xs' : 'bg-gray-50/50 border-gray-100 hover:bg-gray-100/60'"
          >
            <div class="flex items-center gap-3">
              <input
                type="checkbox"
                :checked="selectedStudentIds.includes(s.id)"
                @click.stop="toggleStudentSelection(s.id)"
                class="rounded text-emerald-600 focus:ring-emerald-500 cursor-pointer"
              />
              <div>
                <div class="font-bold text-gray-900 text-xs">{{ s.nama_lengkap }}</div>
                <div class="text-[11px] text-gray-500 font-mono">
                  NIS: {{ s.nis }} • Kelas: {{ s.school_class?.nama || s.class_id || '-' }}
                </div>
              </div>
            </div>

            <span
              v-if="selectedStudentIds.includes(s.id)"
              class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 text-[10px] font-bold"
            >
              Terhubung
            </span>
          </div>

          <div v-if="filteredAllStudents.length === 0" class="py-8 text-center text-gray-400 text-xs">
            Tidak ada data siswa yang cocok dengan pencarian.
          </div>
        </div>

        <div class="p-4 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
          <div class="text-xs font-semibold text-gray-600">
            Terpilih: <strong>{{ selectedStudentIds.length }} Siswa</strong>
          </div>
          <div class="flex gap-2">
            <button
              @click="selectedGuardianForStudents = null"
              class="px-4 py-2 text-gray-600 hover:bg-gray-200 rounded-xl text-xs font-semibold transition cursor-pointer"
            >
              Batal
            </button>
            <button
              @click="saveStudentRelations"
              :disabled="savingRelations"
              class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md transition disabled:opacity-50 cursor-pointer"
            >
              {{ savingRelations ? 'Menyimpan...' : 'Simpan Relasi' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';
import { confirmDeleteWithKeyword, showSuccess, showError } from '../services/swal';
import { toInputDate } from '../utils/date';

const router = useRouter();

// View Mode: 'list' (tabel) atau 'form' (halaman formulir penuh menimpa tabel)
const currentView = ref<'list' | 'form'>('list');

const guardians = ref<any[]>([]);
const allStudents = ref<any[]>([]);
const search = ref('');
const filterHubungan = ref('');
const filterAkun = ref('');
const loading = ref(false);
const saving = ref(false);
const savingRelations = ref(false);

// Pagination & Stats
const currentPage = ref(1);
const perPage = ref(20);
const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
});
const stats = ref({
  total_guardians: 0,
  active_accounts: 0,
  connected_students: 0,
});

// Form State
const isEditing = ref(false);
const editingId = ref<number | null>(null);
const form = ref<any>({
  hubungan: 'ayah',
  nama_lengkap: '',
  nik: '',
  tempat_lahir: 'Purwokerto',
  tgl_lahir: '',
  status_hidup: 'hidup',
  no_hp: '',
  email: '',
  alamat_jalan: '',
  kab_kota: 'Banyumas',
  create_user_account: true,
});

// Student Linkage State
const selectedGuardianForStudents = ref<any>(null);
const selectedStudentIds = ref<number[]>([]);
const studentSearchQuery = ref('');

const goToStudent = (s: any) => {
  router.push({
    path: '/students',
    query: { search: s.nis, open_id: s.id }
  });
};

const totalConnectedStudents = computed(() => {
  if (stats.value.connected_students) return stats.value.connected_students;
  const set = new Set<number>();
  guardians.value.forEach(g => {
    if (g.students) {
      g.students.forEach((s: any) => set.add(s.id));
    }
  });
  return set.size;
});

const filteredGuardians = computed(() => {
  return guardians.value.filter(g => {
    if (filterAkun.value === 'has_account' && !g.user_id) return false;
    if (filterAkun.value === 'no_account' && g.user_id) return false;
    return true;
  });
});

const filteredAllStudents = computed(() => {
  if (!studentSearchQuery.value) return allStudents.value;
  const q = studentSearchQuery.value.toLowerCase();
  return allStudents.value.filter(s => {
    return s.nama_lengkap?.toLowerCase().includes(q) || s.nis?.includes(q);
  });
});

let debounceTimer: any = null;
const debouncedFetch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    fetchGuardians(1);
  }, 300);
};

const fetchGuardians = async (page = 1) => {
  loading.value = true;
  currentPage.value = page;
  try {
    const params: any = {
      page: currentPage.value,
      per_page: perPage.value,
    };
    if (search.value) params.search = search.value;
    if (filterHubungan.value) params.hubungan = filterHubungan.value;

    const res = await api.get('/guardians', { params });
    if (res.data.success) {
      guardians.value = res.data.data || [];
      if (res.data.meta) meta.value = res.data.meta;
      if (res.data.stats) stats.value = res.data.stats;
    }
  } catch (err) {
    console.error('Gagal mengambil data wali murid', err);
  } finally {
    loading.value = false;
  }
};

const fetchAllStudents = async () => {
  try {
    const res = await api.get('/students', { params: { all: 'true' } });
    if (res.data.success) {
      allStudents.value = res.data.data?.data || res.data.data || [];
    }
  } catch (err) {
    console.error('Gagal mengambil daftar siswa', err);
  }
};

// ==============================================================
// FORM VIEW TRANSITIONS (Halaman Form Baru Menimpa Tabel)
// ==============================================================
const openCreateForm = () => {
  isEditing.value = false;
  editingId.value = null;
  form.value = {
    hubungan: 'ayah',
    nama_lengkap: '',
    nik: '',
    tempat_lahir: 'Purwokerto',
    tgl_lahir: '',
    status_hidup: 'hidup',
    no_hp: '',
    email: '',
    alamat_jalan: '',
    kab_kota: 'Banyumas',
    create_user_account: true,
  };
  currentView.value = 'form';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const openEditForm = (g: any) => {
  isEditing.value = true;
  editingId.value = g.id;
  form.value = {
    hubungan: g.hubungan || 'ayah',
    nama_lengkap: g.nama_lengkap || '',
    nik: g.nik || '',
    tempat_lahir: g.tempat_lahir || 'Purwokerto',
    tgl_lahir: toInputDate(g.tgl_lahir),
    status_hidup: g.status_hidup || 'hidup',
    no_hp: g.no_whatsapp || g.no_hp || '',
    email: g.email || '',
    alamat_jalan: g.alamat_jalan || '',
    kab_kota: g.kab_kota || 'Banyumas',
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
    const payload = { ...form.value };
    if (!payload.nik) delete payload.nik;
    if (!payload.email) delete payload.email;
    if (!payload.tgl_lahir) delete payload.tgl_lahir;

    if (isEditing.value && editingId.value) {
      delete payload.create_user_account;
      const res = await api.put(`/guardians/${editingId.value}`, payload);
      if (res.data.success) {
        showSuccess('Berhasil!', 'Data wali murid berhasil diperbarui.');
        closeForm();
        fetchGuardians();
      }
    } else {
      const res = await api.post('/guardians', payload);
      if (res.data.success) {
        showSuccess('Berhasil!', 'Wali murid baru berhasil ditambahkan.');
        closeForm();
        fetchGuardians();
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err, 'Terjadi kesalahan saat menyimpan data wali murid.');
  } finally {
    saving.value = false;
  }
};

const handleCreateAccount = async (g: any) => {
  try {
    const res = await api.post(`/guardians/${g.id}/create-account`);
    if (res.data.success) {
      showSuccess('Akun Dibuat!', res.data.message);
      fetchGuardians();
    }
  } catch (err: any) {
    showError('Gagal Membuat Akun!', err, 'Terjadi kendala saat membuat akun wali murid.');
  }
};

const handleResetPassword = async (g: any) => {
  if (!g.user_id) return;
  try {
    const res = await api.post(`/users/${g.user_id}/reset-password`);
    if (res.data.success) {
      showSuccess('Kata Sandi Direset!', res.data.message || 'Kata sandi berhasil direset ke Password123!');
    }
  } catch (err: any) {
    showError('Gagal Reset Password!', err, 'Terjadi kendala saat mereset kata sandi.');
  }
};

const openModalManageStudents = (g: any) => {
  selectedGuardianForStudents.value = g;
  selectedStudentIds.value = g.students ? g.students.map((s: any) => s.id) : [];
  studentSearchQuery.value = '';
};

const toggleStudentSelection = (studentId: number) => {
  const idx = selectedStudentIds.value.indexOf(studentId);
  if (idx > -1) {
    selectedStudentIds.value.splice(idx, 1);
  } else {
    selectedStudentIds.value.push(studentId);
  }
};

const saveStudentRelations = async () => {
  if (!selectedGuardianForStudents.value) return;
  savingRelations.value = true;
  try {
    const res = await api.post(`/guardians/${selectedGuardianForStudents.value.id}/sync-students`, {
      student_ids: selectedStudentIds.value,
    });
    if (res.data.success) {
      showSuccess('Berhasil!', 'Relasi anak tanggungan berhasil diperbarui.');
      selectedGuardianForStudents.value = null;
      fetchGuardians();
    }
  } catch (err: any) {
    showError('Gagal Menyimpan Relasi!', err, 'Terjadi kendala saat menghubungkan data anak.');
  } finally {
    savingRelations.value = false;
  }
};

const handleDelete = async (g: any) => {
  const confirmed = await confirmDeleteWithKeyword(`wali murid "${g.nama_lengkap}"`);
  if (!confirmed) return;

  try {
    const res = await api.delete(`/guardians/${g.id}`);
    if (res.data.success) {
      showSuccess('Berhasil Dihapus!', res.data.message);
      fetchGuardians();
    }
  } catch (err: any) {
    showError('Gagal Menghapus!', err, 'Data wali murid tidak dapat dihapus.');
  }
};

onMounted(() => {
  fetchGuardians();
  fetchAllStudents();
});
</script>
