<template>
  <div class="space-y-6 animate-in fade-in duration-200">
    <!-- Top Navigation & Action Banner -->
    <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-3.5">
        <button
          @click="$emit('cancel')"
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
            <button type="button" @click="$emit('cancel')" class="hover:text-emerald-600 hover:underline cursor-pointer">Data Wali Murid</button>
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
          @click="$emit('cancel')"
          type="button"
          class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition cursor-pointer"
        >
          Batal
        </button>
        <button
          @click="$emit('submit')"
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
    <form @submit.prevent="$emit('submit')" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      
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
              class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-gray-300 cursor-pointer"
            />
            <label for="createAcc" class="text-xs font-semibold text-emerald-950 cursor-pointer">
              Buatkan otomatis akun login Portal Wali Murid (Password default: <code>Password123!</code>)
            </label>
          </div>
        </div>

        <!-- Bottom Sticky Action Bar -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between gap-4">
          <button
            @click="$emit('cancel')"
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
              @click="$emit('cancel')"
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
</template>

<script setup lang="ts">
import type { GuardianFormData } from './types';

defineProps<{
  form: GuardianFormData;
  isEditing: boolean;
  saving: boolean;
}>();

defineEmits<{
  (e: 'submit'): void;
  (e: 'cancel'): void;
}>();
</script>
