<template>
  <div class="space-y-6">
    <!-- A. Child Profile Identity Card -->
    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <!-- Avatar Ananda -->
        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-2xl font-black shrink-0 border border-emerald-200 shadow-2xs overflow-hidden">
          <img 
            v-if="student.foto_url || student.foto_path" 
            :src="student.foto_url || `/storage/${student.foto_path}`" 
            alt="Foto Siswa" 
            class="w-full h-full object-cover" 
          />
          <span v-else>{{ (student.nama_lengkap || 'S').charAt(0) }}</span>
        </div>

        <div>
          <div class="flex items-center gap-2">
            <h2 class="text-base sm:text-lg font-black text-slate-900">
              {{ student.nama_lengkap }}
            </h2>
            <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[10px] font-extrabold uppercase">
              {{ student.status || 'Aktif' }}
            </span>
          </div>
          
          <p class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-2">
            <span>NIS: <strong class="text-slate-700 font-mono">{{ student.nis || '-' }}</strong></span>
            <span>•</span>
            <span>NISN: <strong class="text-slate-700 font-mono">{{ student.nisn || '-' }}</strong></span>
            <span>•</span>
            <span>Kelas: <strong class="text-slate-800 font-bold">{{ String(student.school_class?.nama || student.class_id || '-').replace(/^Kelas\s+/i, '') }}</strong></span>
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
              :href="getWhatsAppUrl(homeroomTeacherPhone, student.nama_lengkap)" 
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
            {{ (financialSummary.sisa_tagihan ?? 0) <= 0 ? 'Semua tagihan lunas' : 'Sisa tagihan bulan berjalan' }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Child, AttendanceSummary, FinancialSummary, ProgressItem } from './types';

const props = defineProps<{
  student: Child;
  attendanceSummary: AttendanceSummary;
  financialSummary: FinancialSummary;
  latestProgressList: ProgressItem[];
}>();

const formatRupiah = (val: number | string | null | undefined) => {
  return new Intl.NumberFormat('id-ID').format(Math.round(Number(val) || 0));
};

const homeroomTeacherName = computed(() => {
  return props.student.school_class?.homeroom_teacher?.nama_lengkap || 'Ustadz/Ustadzah Wali Kelas';
});

const homeroomTeacherPhone = computed(() => {
  return props.student.school_class?.homeroom_teacher?.no_hp || '';
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
  const today = props.attendanceSummary.today;
  if (!today) return 'Belum Absen';
  if (today.status === 'hadir') return 'Hadir di Kelas';
  if (today.status === 'sakit') return 'Sakit';
  if (today.status === 'izin') return 'Izin';
  if (today.status === 'alpa') return 'Tanpa Keterangan';
  return today.status;
});

const todayAttendanceStatusClass = computed(() => {
  const today = props.attendanceSummary.today;
  if (!today) return 'text-slate-400';
  if (today.status === 'hadir') return 'text-emerald-700';
  if (today.status === 'sakit') return 'text-amber-700';
  if (today.status === 'izin') return 'text-sky-700';
  return 'text-rose-700';
});

const latestProgressTitle = computed(() => {
  const list = props.latestProgressList;
  if (list && list.length > 0) {
    return list[0].capaian || list[0].kategori || 'Tercatat';
  }
  return 'Belum ada catatan';
});

const latestProgressPredicate = computed(() => {
  const list = props.latestProgressList;
  if (list && list.length > 0 && list[0].nilai) {
    return `Predikat: ${list[0].nilai}`;
  }
  return 'Rajin & Mutqin';
});
</script>
