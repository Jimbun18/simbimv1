<script setup lang="ts">
import type { Teacher } from './types'
import { getPhotoUrl, getInitials, calculateTenure, formatDateIndo } from './types'

defineProps<{
  teacher: Teacher
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'edit', teacher: Teacher): void
  (e: 'delete', teacher: Teacher): void
}>()
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-200">
    <!-- Top Navigation Banner -->
    <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-3.5">
        <button
          @click="emit('close')"
          type="button"
          class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center justify-center shrink-0 shadow-2xs group cursor-pointer"
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
            <button type="button" @click="emit('close')" class="hover:text-sky-600 hover:underline cursor-pointer">Data Guru & Tendik</button>
            <span>/</span>
            <span class="text-sky-700 font-bold">Detail Profil PTK</span>
          </div>
          <h1 class="text-lg sm:text-2xl font-black text-gray-900 leading-tight">
            {{ teacher.nama_lengkap }}
          </h1>
          <p class="text-xs text-gray-500 mt-0.5">
            Informasi lengkap profil pendidik, amanah tugas jabatan, wali kelas, serta beban kegiatan mengajar.
          </p>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-2.5 self-end sm:self-center">
        <button
          @click="emit('close')"
          type="button"
          class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition cursor-pointer"
        >
          Kembali
        </button>
        <button
          @click="emit('edit', teacher)"
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
            v-if="teacher.foto_url || teacher.foto_path"
            :src="getPhotoUrl(teacher.foto_url, teacher.foto_path)"
            :alt="teacher.nama_lengkap"
            class="w-full h-full object-cover"
          />
          <span v-else class="text-3xl font-black text-sky-200 uppercase">
            {{ getInitials(teacher.nama_lengkap) }}
          </span>
        </div>

        <div class="space-y-2 flex-1">
          <div class="flex flex-wrap items-center gap-2">
            <span v-if="teacher.jabatan" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-sky-500/30 text-sky-200 border border-sky-400/30 shadow-2xs">
              {{ teacher.jabatan }}
            </span>
            <span v-if="teacher.tugas_tambahan" class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/25 text-amber-200 border border-amber-400/30">
              {{ teacher.tugas_tambahan }}
            </span>
            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
              {{ teacher.status }}
            </span>
          </div>

          <h2 class="text-xl sm:text-3xl font-black text-white leading-tight">
            {{ teacher.nama_lengkap }}
          </h2>

          <div class="flex flex-wrap items-center gap-y-1 gap-x-4 text-xs text-sky-200 font-mono">
            <span v-if="teacher.nip">NIP: {{ teacher.nip }}</span>
            <span v-else>NIP: -</span>
            <span>•</span>
            <span>NIK: {{ teacher.nik }}</span>
            <span v-if="teacher.nuptk">• NUPTK: {{ teacher.nuptk }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Main 4-Grid Cards Details -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Card 1: Penugasan Tugas Pokok, Wali Kelas & Mapel -->
      <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
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
            <span class="font-bold text-gray-900 text-sm">{{ teacher.jabatan || '-' }}</span>
          </div>

          <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between">
            <span class="text-gray-500 font-medium">Tugas Tambahan</span>
            <span class="font-bold text-amber-700">{{ teacher.tugas_tambahan || '-' }}</span>
          </div>

          <!-- Amanah Wali Kelas -->
          <div class="p-3.5 bg-purple-50/60 rounded-xl border border-purple-100">
            <span class="text-purple-700 font-bold block mb-1">Amanah Wali Kelas:</span>
            <div v-if="teacher.homeroom_classes && teacher.homeroom_classes.length > 0" class="flex flex-wrap gap-2 mt-1">
              <span v-for="c in teacher.homeroom_classes" :key="c.id" class="px-3 py-1 bg-purple-600 text-white rounded-lg font-bold shadow-2xs">
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
            <div v-if="teacher.assignments && teacher.assignments.length > 0" class="space-y-1.5 mt-2">
              <div v-for="asg in teacher.assignments" :key="asg.id" class="flex items-center justify-between bg-white px-3 py-2 rounded-lg border border-sky-200/60 text-xs">
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
            <span class="font-bold text-gray-900 font-mono">{{ teacher.jam_mengajar_per_minggu ? `${teacher.jam_mengajar_per_minggu} Jam / Minggu` : '24 Jam / Minggu (Standar)' }}</span>
          </div>
        </div>
      </div>

      <!-- Card 2: Status Kepegawaian & Kualifikasi -->
      <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
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
            <div class="font-bold text-gray-800 uppercase mt-0.5">{{ teacher.jenis_ptk?.replace('_', ' ') }}</div>
          </div>

          <div class="p-3 bg-gray-50 rounded-xl">
            <span class="text-gray-400 block text-[10px] uppercase font-semibold">Status Ikatan Kerja</span>
            <div class="font-bold text-gray-800 uppercase mt-0.5">{{ teacher.status_kepegawaian ?? 'GTY' }}</div>
          </div>

          <div class="p-3 bg-gray-50 rounded-xl">
            <span class="text-gray-400 block text-[10px] uppercase font-semibold">Masa Kerja</span>
            <div class="font-bold text-gray-800 mt-0.5">{{ calculateTenure(teacher.tgl_mulai_tugas) }}</div>
          </div>

          <div class="p-3 bg-gray-50 rounded-xl">
            <span class="text-gray-400 block text-[10px] uppercase font-semibold">TMT Mulai Tugas</span>
            <div class="font-bold text-gray-800 mt-0.5">{{ formatDateIndo(teacher.tgl_mulai_tugas) }}</div>
          </div>

          <div class="p-3 bg-gray-50 rounded-xl">
            <span class="text-gray-400 block text-[10px] uppercase font-semibold">Pendidikan Terakhir</span>
            <div class="font-bold text-gray-800 mt-0.5">{{ teacher.education?.nama || teacher.pendidikan_terakhir || 'S1 (Sarjana)' }}</div>
          </div>

          <div class="p-3 bg-gray-50 rounded-xl">
            <span class="text-gray-400 block text-[10px] uppercase font-semibold">Jurusan / Program Studi</span>
            <div class="font-bold text-gray-800 mt-0.5">{{ teacher.jurusan_prodi || 'Pendidikan' }}</div>
          </div>
        </div>

        <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-100 text-xs flex items-center justify-between">
          <span class="text-gray-500 font-medium">Sertifikasi Pendidik</span>
          <span class="font-bold uppercase" :class="teacher.status_sertifikasi === 'sudah' ? 'text-emerald-700' : 'text-gray-600'">
            {{ teacher.status_sertifikasi === 'sudah' ? 'Tersertifikasi' : 'Belum Sertifikasi' }}
          </span>
        </div>
      </div>

      <!-- Card 3: Biodata Pribadi & Alamat Domisili -->
      <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
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
            <div class="font-mono font-bold text-gray-800 mt-0.5">{{ teacher.nik }}</div>
          </div>

          <div class="p-3 bg-gray-50 rounded-xl">
            <span class="text-gray-400 block text-[10px] uppercase font-semibold">Jenis Kelamin</span>
            <div class="font-bold text-gray-800 mt-0.5">{{ teacher.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</div>
          </div>

          <div class="p-3 bg-gray-50 rounded-xl col-span-2">
            <span class="text-gray-400 block text-[10px] uppercase font-semibold">Tempat & Tanggal Lahir</span>
            <div class="font-medium text-gray-800 mt-0.5">{{ teacher.tempat_lahir || 'Purwokerto' }}, {{ formatDateIndo(teacher.tgl_lahir) }}</div>
          </div>

          <div class="p-3 bg-gray-50 rounded-xl col-span-2">
            <span class="text-gray-400 block text-[10px] uppercase font-semibold">Alamat Tempat Tinggal</span>
            <div class="font-medium text-gray-800 mt-0.5">{{ teacher.alamat_jalan }}, {{ teacher.kab_kota || 'Banyumas' }}</div>
          </div>
        </div>
      </div>

      <!-- Card 4: Kontak Aktif & Akses SIM-BIM -->
      <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
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
            <span class="font-mono font-bold text-gray-900">{{ teacher.no_hp }}</span>
          </div>

          <div class="p-3.5 bg-gray-50 rounded-xl flex items-center justify-between">
            <span class="text-gray-500 font-medium">Alamat Email</span>
            <span class="font-medium text-gray-900">{{ teacher.email || '-' }}</span>
          </div>

          <div class="p-3.5 bg-indigo-50/60 rounded-xl border border-indigo-100 space-y-2">
            <span class="text-indigo-900 font-bold block">Status Akun Login SIM-BIM:</span>
            <div class="flex items-center justify-between text-xs">
              <span class="text-gray-600">Username:</span>
              <span class="font-mono font-bold text-indigo-700">{{ teacher.user?.username || teacher.nip || teacher.nik }}</span>
            </div>
            <div class="flex items-center justify-between text-xs">
              <span class="text-gray-600">Email Akun:</span>
              <span class="font-medium text-gray-800">{{ teacher.user?.email || teacher.email || '-' }}</span>
            </div>
            <div class="flex items-center justify-between text-xs pt-1 border-t border-indigo-200/50">
              <span class="text-gray-600">Status Akses:</span>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800">
                {{ teacher.user?.status ?? 'Aktif' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
