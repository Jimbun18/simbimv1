<template>
  <div class="min-h-screen relative flex items-center justify-center p-3 sm:p-6 lg:p-8 overflow-hidden">
    <!-- BACKGROUND UTAMA: Foto Santri & Guru SDI BIM (login.jpeg) -->
    <div class="absolute inset-0 z-0">
      <img 
        :src="loginBg" 
        alt="Latar Belakang SIM-BIM" 
        class="w-full h-full object-cover object-center filter blur-[3px] scale-105"
      />
      <!-- Overlay Gradien Hijau Zamrud Elegan & Gelap untuk Kontras Maksimal -->
      <div class="absolute inset-0 bg-gradient-to-br from-emerald-950/88 via-slate-950/82 to-teal-950/88"></div>
      <div class="absolute inset-0 bg-black/25"></div>
    </div>
    
    <!-- RESPONSIVE LOGIN CONTAINER: Adapts automatically between Mobile, Tablet, and Desktop Monitor -->
    <div class="w-full max-w-md md:max-w-xl lg:max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden border border-emerald-100/40 backdrop-blur-md grid grid-cols-1 lg:grid-cols-12 transition-all relative z-10">
      
      <!-- DESKTOP / WIDE MONITOR LEFT BRANDING SIDEBAR (Visible on Monitor >= 1024px) -->
      <div class="hidden lg:flex lg:col-span-5 p-8 flex-col justify-between text-white relative overflow-hidden">
        <!-- Background Image Foto Santri & Pendidik SDI BIM -->
        <div class="absolute inset-0 z-0">
          <img 
            :src="loginBg" 
            alt="Santri & Pendidik SDI Bina Insan Mandiri" 
            class="w-full h-full object-cover object-top scale-105"
          />
          <!-- Overlay Gelap Zamrud Elegan agar Teks Sangat Jelas Terbaca -->
          <div class="absolute inset-0 bg-gradient-to-b from-emerald-950/92 via-emerald-900/85 to-slate-950/95"></div>
        </div>

        <!-- Ambient Glow -->
        <div class="absolute -right-10 -top-10 w-48 h-48 bg-emerald-500/20 rounded-full blur-2xl pointer-events-none z-10"></div>
        <div class="absolute -left-10 -bottom-10 w-48 h-48 bg-teal-500/20 rounded-full blur-2xl pointer-events-none z-10"></div>

        <div class="relative z-20">
          <div class="flex items-center gap-3">
            <img 
              src="/logo.png" 
              alt="Logo SDI Bina Insan Mandiri" 
              class="w-12 h-12 object-contain drop-shadow-md"
            />
            <div>
              <h2 class="font-black text-lg text-white leading-tight">SIM-BIM</h2>
              <p class="text-xs text-emerald-200 font-semibold">SDI Bina Insan Mandiri</p>
            </div>
          </div>

          <div class="mt-8 space-y-4">
            <span class="inline-block px-3 py-1 bg-white/10 text-emerald-200 text-xs font-bold rounded-full border border-white/20">
              Sistem Informasi Manajemen Terpadu
            </span>
            <h3 class="text-2xl font-black text-white leading-snug">
              Membentuk Generasi Qur'ani, Berakhlak Mulia & Berprestasi
            </h3>
            <p class="text-xs text-emerald-100/80 leading-relaxed">
              Platform digital terintegrasi untuk Guru, Kepala Sekolah, Staf Administrasi, dan Wali Murid SDI Bina Insan Mandiri Purwokerto.
            </p>
          </div>

          <!-- Feature badges list -->
          <div class="mt-8 space-y-2.5 text-xs text-emerald-100">
            <div class="flex items-center gap-2.5">
              <div class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-300 flex items-center justify-center shrink-0">✓</div>
              <span>Presensi Mandiri Terverifikasi Radius GPS</span>
            </div>
            <div class="flex items-center gap-2.5">
              <div class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-300 flex items-center justify-center shrink-0">✓</div>
              <span>Jurnal Capaian Tahfidz & Karakter Harian</span>
            </div>
            <div class="flex items-center gap-2.5">
              <div class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-300 flex items-center justify-center shrink-0">✓</div>
              <span>Portal Wali Murid & Notifikasi Realtime</span>
            </div>
            <div class="flex items-center gap-2.5">
              <div class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-300 flex items-center justify-center shrink-0">✓</div>
              <span>Manajemen Keuangan SPP & Tagihan Sekolah</span>
            </div>
          </div>
        </div>

        <div class="relative z-20 pt-6 border-t border-white/10 text-[11px] text-emerald-300/80 flex justify-between items-center">
          <span>Purwokerto, Jawa Tengah</span>
          <span>TA 2024/2025</span>
        </div>
      </div>

      <!-- RIGHT PANEL / MAIN LOGIN FORM (Mobile, Tablet, and Desktop Monitor) -->
      <div class="lg:col-span-7 p-6 sm:p-8 md:p-10 flex flex-col justify-center relative">
        <!-- Back to Landing Link -->
        <div class="mb-4 flex justify-between items-center">
          <router-link 
            to="/" 
            class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-800 hover:text-emerald-950 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Halaman Utama</span>
          </router-link>
          <span class="text-[11px] text-slate-400 font-medium">SIM-BIM Purwokerto</span>
        </div>

        <!-- Header (Displayed on Mobile & Tablet, Hidden on Large Desktop) -->
        <div class="text-center mb-6 lg:mb-8">
          <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto mb-3 flex items-center justify-center lg:hidden">
            <img 
              src="/logo.png" 
              alt="Logo SDI Bina Insan Mandiri" 
              class="w-full h-full object-contain drop-shadow-md"
            />
          </div>
          <h1 class="text-xl sm:text-2xl font-black text-gray-900 tracking-tight leading-tight">
            Masuk ke SIM-BIM
          </h1>
          <p class="text-xs sm:text-sm text-gray-500 mt-1 font-medium">
            SDI Bina Insan Mandiri Purwokerto
          </p>
          <span class="inline-block lg:hidden mt-2 px-3 py-0.5 bg-emerald-50 text-emerald-800 text-[11px] font-bold rounded-full border border-emerald-200">
            Portal Guru, Tendik, & Wali Murid
          </span>
        </div>

        <!-- Alert Error Message -->
        <div 
          v-if="auth.error" 
          class="mb-5 p-3.5 bg-rose-50 border border-rose-200 text-rose-700 text-xs sm:text-sm rounded-2xl flex items-center gap-2.5 animate-shake"
        >
          <svg class="w-5 h-5 shrink-0 text-rose-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <span class="font-medium">{{ auth.error }}</span>
        </div>

        <!-- Form Login -->
        <form @submit.prevent="handleLogin" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Username / Email / No. HP
            </label>
            <div class="relative">
              <input
                v-model="identifier"
                type="text"
                required
                autocomplete="username"
                placeholder="Masukkan username atau no. HP"
                class="w-full px-4 py-3 rounded-2xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none text-gray-900 text-sm transition"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Kata Sandi
            </label>
            <div class="relative">
              <input
                v-model="password"
                type="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
                class="w-full px-4 py-3 rounded-2xl border border-gray-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none text-gray-900 text-sm transition"
              />
            </div>
          </div>

          <button
            type="submit"
            :disabled="auth.loading"
            class="w-full mt-3 py-3.5 px-4 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold rounded-2xl shadow-lg shadow-emerald-600/30 transition disabled:opacity-50 flex items-center justify-center gap-2 text-sm cursor-pointer"
          >
            <svg v-if="auth.loading" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ auth.loading ? 'Memverifikasi...' : 'Masuk ke Aplikasi' }}</span>
          </button>
        </form>

        <!-- Quick Demo Switcher (Responsif 2 col on mobile, 4 col on tablet/desktop) -->
        <div class="mt-6 pt-5 border-t border-gray-100 text-center">
          <p class="text-xs text-gray-500 font-semibold mb-2.5">Login Cepat Akun Demo (Uji Coba):</p>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
            <button
              type="button"
              @click="quickFill('guru', 'Password123!')"
              class="py-2.5 px-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold rounded-xl border border-emerald-200 transition text-[11px] flex flex-col items-center justify-center gap-0.5 cursor-pointer"
            >
              <span>Guru</span>
              <span class="text-[9px] text-emerald-600 font-normal">Presensi & KBM</span>
            </button>
            <button
              type="button"
              @click="quickFill('admin', 'Password123!')"
              class="py-2.5 px-2 bg-gray-50 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200 text-gray-700 font-semibold rounded-xl border border-gray-200 transition text-[11px] flex flex-col items-center justify-center gap-0.5 cursor-pointer"
            >
              <span>Staf Admin</span>
              <span class="text-[9px] text-gray-400 font-normal">Kelola Data</span>
            </button>
            <button
              type="button"
              @click="quickFill('kepsek', 'Password123!')"
              class="py-2.5 px-2 bg-gray-50 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200 text-gray-700 font-semibold rounded-xl border border-gray-200 transition text-[11px] flex flex-col items-center justify-center gap-0.5 cursor-pointer"
            >
              <span>Kepsek</span>
              <span class="text-[9px] text-gray-400 font-normal">Monitoring</span>
            </button>
            <button
              type="button"
              @click="quickFill('wali', 'Password123!')"
              class="py-2.5 px-2 bg-gray-50 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200 text-gray-700 font-semibold rounded-xl border border-gray-200 transition text-[11px] flex flex-col items-center justify-center gap-0.5 cursor-pointer"
            >
              <span>Wali Murid</span>
              <span class="text-[9px] text-gray-400 font-normal">Portal Anak</span>
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import loginBg from '../assets/login.jpeg';

const router = useRouter();
const auth = useAuthStore();

const identifier = ref('');
const password = ref('');

const handleLogin = async () => {
  const success = await auth.login(identifier.value, password.value);
  if (success) {
    if (auth.isAdmin) router.push('/dashboard');
    else if (auth.isGuru) router.push('/presensi');
    else if (auth.isWali) router.push('/portal-wali');
    else router.push('/dashboard');
  }
};

const quickFill = (user: string, pass: string) => {
  identifier.value = user;
  password.value = pass;
};
</script>
