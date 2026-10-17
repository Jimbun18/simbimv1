<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api';
import { useAuthStore } from '../stores/auth';

const route = useRoute();
const router = useRouter();

interface TimeSlot {
  id: number;
  nama: string;
  jam_mulai: string;
  jam_selesai: string;
  urutan: number;
  hari?: string;
}

interface SchoolClass {
  id: number;
  nama: string;
  kode: string;
  tingkat: number;
  active_students_count?: number;
}

interface Subject {
  id: number;
  nama: string;
  kode: string;
  kelompok?: string;
  is_tematik?: boolean;
  tema_nomor?: number;
  muatan_pelajaran?: string[];
}

interface Teacher {
  id: number;
  nama_lengkap: string;
  nip?: string;
}

interface Room {
  id: number;
  nama: string;
  kode?: string;
}

interface ScheduleItem {
  id: number;
  academic_year_id: number;
  class_id: number;
  subject_id: number;
  teacher_id: number;
  room_id: number | null;
  hari: string;
  time_slot_id: number;
  status: string;
  today_attendance_count: number;
  today_evidence_count: number;
  school_class?: SchoolClass;
  subject?: Subject;
  teacher?: Teacher;
  room?: Room;
  time_slot?: TimeSlot;
}

interface MergedScheduleItem extends ScheduleItem {
  schedule_ids: number[];
  durasi_jp: number;
  durasi_menit_per_jp: number;
  durasi_label: string;
  jam_mulai_efektif: string;
  jam_selesai_efektif: string;
  sesi_label: string;
  first_urutan: number;
  last_urutan: number;
  jam_selesai_raw: string;
}

interface AttendanceStudent {
  student_id: number;
  nis: string;
  nisn?: string;
  nama_lengkap: string;
  jenis_kelamin: 'L' | 'P';
  foto_path?: string | null;
  status: 'hadir' | 'izin' | 'sakit' | 'alpa';
  keterangan?: string | null;
  attendance_id?: number | null;
}

const auth = useAuthStore();

// State
const loading = ref(false);
const schedules = ref<ScheduleItem[]>([]);
const teachersList = ref<Teacher[]>([]);
const selectedTeacherId = ref<string>('');
const selectedHari = ref<string>('');
const searchQuery = ref<string>('');
const activeDate = ref<string>(new Date().toISOString().split('T')[0]);
const sortByTodayFirst = ref<boolean>(true);

// Alert Notification
const alertState = ref<{ show: boolean; title: string; message: string; type: 'success' | 'error' | 'warning' }>({
  show: false,
  title: '',
  message: '',
  type: 'success',
});

const showAlert = (title: string, message: string, type: 'success' | 'error' | 'warning' = 'success') => {
  alertState.value = { show: true, title, message, type };
  setTimeout(() => {
    alertState.value.show = false;
  }, 4500);
};

// View Mode: 'schedule_list' | 'kbm_evidence' | 'homeroom_attendance'
const currentViewMode = ref<'schedule_list' | 'kbm_evidence' | 'homeroom_attendance'>('schedule_list');

// Evidence Form State (KBM Tatap Muka Guru)
const activeSchedule = ref<MergedScheduleItem | null>(null);
const attendanceDate = ref<string>(new Date().toISOString().split('T')[0]);
const requireEvidence = ref(true);
const existingEvidence = ref<any>(null);
const evidencePhotoFile = ref<File | null>(null);
const evidencePhotoPreview = ref<string>('');
const evidenceMateriAjar = ref('');
const evidenceCatatan = ref('');
const savingEvidence = ref(false);
const loadingEvidence = ref(false);

// Homeroom Daily Attendance State (Wali Kelas 1x Sehari)
const selectedHomeroomClass = ref<any>(null);
const homeroomAttendanceDate = ref<string>(new Date().toISOString().split('T')[0]);
const homeroomStudents = ref<any[]>([]);
const homeroomAttendances = ref<Record<number, 'hadir' | 'izin' | 'sakit' | 'alpa'>>({});
const homeroomNotes = ref<Record<number, string>>({});
const loadingHomeroom = ref(false);
const savingHomeroom = ref(false);
const homeroomSearchQuery = ref('');

// ==============================================================
// LOGIKA WARNA HIJAU & MERAH (Sesuai Referensi Gambar LMS)
// HIJAU = Jadwal Hari Ini (otomatis berganti setiap jam 00:00)
// MERAH = Jadwal Bukan Hari Ini
// ==============================================================
const getIndonesianDayName = (dateStr: string) => {
  const d = new Date(dateStr);
  const dayIndex = d.getDay();
  const days = ['minggu', 'senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'];
  return days[dayIndex] || 'senin';
};

const currentTodayName = computed(() => {
  return getIndonesianDayName(activeDate.value);
});

const isTodaySchedule = (sch: ScheduleItem | MergedScheduleItem) => {
  return sch.hari.toLowerCase() === currentTodayName.value;
};

const getCardTheme = (sch: ScheduleItem | MergedScheduleItem) => {
  if (isTodaySchedule(sch)) {
    // HIJAU (Jadwal Hari Ini) - Sesuai Card 1 (Digital Kreatif)
    return {
      bg: 'bg-[#137333]', // Emerald Green
      text: 'text-white',
      badge: 'bg-[#0d5926] text-emerald-100 border-[#1b8e45]',
      accent: 'border-emerald-600',
      isToday: true,
      label: 'HARI INI',
    };
  } else {
    // MERAH (Jadwal Bukan Hari Ini) - Sesuai Card 2 & 3 (Proyek SI & Sistem Operasi)
    return {
      bg: 'bg-[#b3261e]', // Crimson Red
      text: 'text-white',
      badge: 'bg-[#8c1d18] text-red-100 border-[#d32f2f]',
      accent: 'border-red-600',
      isToday: false,
      label: 'BUKAN HARI INI',
    };
  }
};

// ==============================================================
// AUTO-UPDATE SETIAP JAM 00:00 (Pergantian Hari Otomatis)
// ==============================================================
let midnightTimer: any = null;
let intervalCheckTimer: any = null;

const updateCurrentDate = () => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const newDateStr = `${year}-${month}-${day}`;

  if (activeDate.value !== newDateStr) {
    activeDate.value = newDateStr;
    loadSchedules();
  }
};

const scheduleMidnightUpdate = () => {
  if (midnightTimer) clearTimeout(midnightTimer);
  const now = new Date();
  // Target: jam 00:00:02 keesokan harinya
  const tomorrow = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 0, 0, 2);
  const msUntilMidnight = tomorrow.getTime() - now.getTime();

  midnightTimer = setTimeout(() => {
    updateCurrentDate();
    scheduleMidnightUpdate(); // Jadwalkan kembali untuk malam berikutnya
  }, msUntilMidnight);
};

// Load teachers for admin selector
const loadTeachers = async () => {
  if (!auth.isAdmin) return;
  try {
    const res = await api.get('/teachers');
    if (res.data.success) {
      teachersList.value = res.data.data || [];
    }
  } catch (err) {
    console.error('Gagal memuat daftar guru', err);
  }
};

// Load schedules
const loadSchedules = async () => {
  loading.value = true;
  try {
    const params: any = {
      tanggal: activeDate.value,
    };

    // If teacher, only load their own schedules
    if (auth.isGuru && !auth.isAdmin) {
      params.my_schedules = 1;
    } else if (auth.isAdmin) {
      if (selectedTeacherId.value === 'me') {
        params.my_schedules = 1;
      } else if (selectedTeacherId.value) {
        params.teacher_id = selectedTeacherId.value;
      }
    }

    if (selectedHari.value) {
      params.hari = selectedHari.value;
    }

    const res = await api.get('/schedules', { params });
    if (res.data.success) {
      schedules.value = res.data.data || [];
    }
  } catch (err: any) {
    console.error('Gagal mengambil jadwal KBM', err);
    showAlert('Gagal Memuat Jadwal', err.response?.data?.message || 'Terjadi kendala saat memuat jadwal.', 'error');
  } finally {
    loading.value = false;
  }
};

// ==============================================================
// LOGIKA PENGGABUNGAN JADWAL BERURUTAN (CONSECUTIVE SESSIONS MERGING)
// Contoh: Guru A mengajar Tema 1 di Kelas 1 durasi 2x35m (Sesi 1 & 2)
// Disatukan menjadi 1 kartu 2 JP (2x35m) dengan rentang waktu 06:30 - 07:40
// ==============================================================
const formatTimeHM = (timeStr?: string) => {
  if (!timeStr) return '';
  const parts = timeStr.split(':');
  if (parts.length >= 2) {
    return `${parts[0]}:${parts[1]}`;
  }
  return timeStr;
};

const calcMinutes = (startStr: string, endStr: string) => {
  const [h1, m1] = startStr.split(':').map(Number);
  const [h2, m2] = endStr.split(':').map(Number);
  return (h2 * 60 + m2) - (h1 * 60 + m1);
};

const createMergedItem = (item: ScheduleItem): MergedScheduleItem => {
  const urutan = item.time_slot?.urutan ?? 1;
  const jamMulaiRaw = item.time_slot?.jam_mulai || '07:30';
  const jamSelesaiRaw = item.time_slot?.jam_selesai || '08:05';

  let slotMin = 35;
  try {
    const diff = calcMinutes(jamMulaiRaw, jamSelesaiRaw);
    if (diff > 0 && diff < 180) slotMin = diff;
  } catch {
    slotMin = 35;
  }

  return {
    ...item,
    schedule_ids: [item.id],
    durasi_jp: 1,
    durasi_menit_per_jp: slotMin,
    durasi_label: `1 JP (1x${slotMin}m)`,
    first_urutan: urutan,
    last_urutan: urutan,
    jam_selesai_raw: jamSelesaiRaw,
    jam_mulai_efektif: formatTimeHM(jamMulaiRaw),
    jam_selesai_efektif: formatTimeHM(jamSelesaiRaw),
    sesi_label: item.time_slot?.nama || `Sesi ${urutan} (JP ${urutan})`,
  };
};

const mergeConsecutiveSchedules = (items: ScheduleItem[]): MergedScheduleItem[] => {
  if (!items || items.length === 0) return [];

  const dayOrder: Record<string, number> = {
    senin: 1, selasa: 2, rabu: 3, kamis: 4, jumat: 5, sabtu: 6, minggu: 7
  };

  // Urutkan jadwal: Hari -> Guru -> Kelas -> Mapel -> Ruang -> Urutan/Jam Mulai
  const sorted = [...items].sort((a, b) => {
    const dA = dayOrder[a.hari.toLowerCase()] || 99;
    const dB = dayOrder[b.hari.toLowerCase()] || 99;
    if (dA !== dB) return dA - dB;

    if (a.teacher_id !== b.teacher_id) return a.teacher_id - b.teacher_id;
    if (a.class_id !== b.class_id) return a.class_id - b.class_id;
    if (a.subject_id !== b.subject_id) return a.subject_id - b.subject_id;
    const rA = a.room_id || 0;
    const rB = b.room_id || 0;
    if (rA !== rB) return rA - rB;

    const uA = a.time_slot?.urutan ?? 0;
    const uB = b.time_slot?.urutan ?? 0;
    if (uA !== uB) return uA - uB;

    return (a.time_slot?.jam_mulai || '').localeCompare(b.time_slot?.jam_mulai || '');
  });

  const merged: MergedScheduleItem[] = [];

  for (const item of sorted) {
    if (merged.length === 0) {
      merged.push(createMergedItem(item));
      continue;
    }

    const last = merged[merged.length - 1];

    // Syarat penggabungan:
    // 1. Hari, Guru, Kelas, Mapel, dan Ruangan sama
    // 2. Sesi berurutan (urutan n+1 ATAU jam_mulai == jam_selesai sesi sebelumnya)
    const isSameContext =
      last.hari.toLowerCase() === item.hari.toLowerCase() &&
      last.teacher_id === item.teacher_id &&
      last.class_id === item.class_id &&
      last.subject_id === item.subject_id &&
      (last.room_id || 0) === (item.room_id || 0);

    const isConsecutive =
      isSameContext &&
      ((item.time_slot?.urutan != null && last.last_urutan != null && item.time_slot.urutan === last.last_urutan + 1) ||
       (item.time_slot?.jam_mulai && last.jam_selesai_raw && item.time_slot.jam_mulai.substring(0, 5) === last.jam_selesai_raw.substring(0, 5)));

    if (isConsecutive) {
      // Gabungkan ke kartu sesi sebelumnya
      last.schedule_ids.push(item.id);
      last.durasi_jp += 1;
      last.last_urutan = item.time_slot?.urutan ?? (last.last_urutan + 1);

      if (item.time_slot?.jam_selesai) {
        last.jam_selesai_raw = item.time_slot.jam_selesai;
        last.jam_selesai_efektif = formatTimeHM(item.time_slot.jam_selesai);
      }

      if ((item as any).today_evidence_count > 0) {
        last.today_evidence_count = Math.max(last.today_evidence_count, (item as any).today_evidence_count);
      }

      const slotMin = last.durasi_menit_per_jp || 35;
      last.durasi_label = `${last.durasi_jp} JP (${last.durasi_jp}x${slotMin}m)`;
      last.sesi_label = `Sesi ${last.first_urutan} - ${last.last_urutan} (${last.durasi_jp} JP)`;
    } else {
      merged.push(createMergedItem(item));
    }
  }

  return merged;
};

// Filtered schedules (digabung dulu per sesi berurutan, lalu difilter)
const filteredSchedules = computed<MergedScheduleItem[]>(() => {
  const mergedList = mergeConsecutiveSchedules(schedules.value);
  let list = mergedList;

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter((s) => {
      const subjectName = s.subject?.nama?.toLowerCase() || '';
      const subjectCode = s.subject?.kode?.toLowerCase() || '';
      const className = s.school_class?.nama?.toLowerCase() || '';
      const teacherName = s.teacher?.nama_lengkap?.toLowerCase() || '';
      const roomName = s.room?.nama?.toLowerCase() || '';
      return (
        subjectName.includes(q) ||
        subjectCode.includes(q) ||
        className.includes(q) ||
        teacherName.includes(q) ||
        roomName.includes(q)
      );
    });
  }

  // Jika melihat Semua Hari dan mode sortByTodayFirst aktif, posisikan jadwal HIJAU (Hari Ini) di paling atas
  if (!selectedHari.value && sortByTodayFirst.value) {
    list.sort((a, b) => {
      const aToday = isTodaySchedule(a) ? 0 : 1;
      const bToday = isTodaySchedule(b) ? 0 : 1;
      return aToday - bToday;
    });
  }

  return list;
});

// Stats ringkas berdasarkan kartu KBM yang efektif
const todayMergedSchedules = computed(() => {
  return mergeConsecutiveSchedules(schedules.value).filter(
    (s) => s.hari.toLowerCase() === currentTodayName.value
  );
});

const todaySchedulesCount = computed(() => todayMergedSchedules.value.length);
const completedTodayCount = computed(() => todayMergedSchedules.value.filter(s => s.today_evidence_count > 0).length);

// ==============================================================
// 1. KBM EVIDENCE & JURNAL MENGAJAR (GURU MATA PELAJARAN)
// ==============================================================
const openKbmEvidenceForm = async (schedule: MergedScheduleItem) => {
  // Batasi: Hanya boleh dibuka jika jadwal hari ini atau sudah ada bukti tersimpan (atau admin)
  if (!isTodaySchedule(schedule) && !(schedule as any).today_evidence_count && !auth.isAdmin) {
    showAlert(
      'Jadwal Belum Dibuka',
      `Jadwal pelajaran ini berlangsung pada hari ${schedule.hari.toUpperCase()}. Pengunggahan bukti foto dan jurnal KBM hanya dapat dilakukan saat hari pelaksanaan KBM tersebut.`,
      'warning'
    );
    return;
  }

  activeSchedule.value = schedule;
  attendanceDate.value = activeDate.value;
  evidencePhotoFile.value = null;
  evidencePhotoPreview.value = '';
  evidenceMateriAjar.value = '';
  evidenceCatatan.value = '';
  existingEvidence.value = null;
  currentViewMode.value = 'kbm_evidence';
  loadingEvidence.value = true;

  try {
    const targetIds = (schedule.schedule_ids && schedule.schedule_ids.length > 0)
      ? schedule.schedule_ids
      : [schedule.id];

    let foundEvidence: any = null;
    for (const sid of targetIds) {
      const res = await api.get('/attendance/evidence', {
        params: {
          schedule_id: sid,
          tanggal: attendanceDate.value,
        },
      });

      if (res.data.success && res.data.data && res.data.data.length > 0) {
        foundEvidence = res.data.data[0];
        break;
      }
    }

    if (foundEvidence) {
      existingEvidence.value = foundEvidence;
      evidenceMateriAjar.value = foundEvidence.materi_ajar || '';
      evidenceCatatan.value = foundEvidence.catatan || '';
      if (foundEvidence.photo_path) {
        evidencePhotoPreview.value = foundEvidence.photo_path.startsWith('http')
          ? foundEvidence.photo_path
          : `/storage/${foundEvidence.photo_path}`;
      }
    }
  } catch (err: any) {
    console.error('Gagal memuat bukti KBM', err);
  } finally {
    loadingEvidence.value = false;
  }
};

const handleEvidencePhotoChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    const file = target.files[0];
    evidencePhotoFile.value = file;
    evidencePhotoPreview.value = URL.createObjectURL(file);
  }
};

const submitKbmEvidence = async () => {
  if (!activeSchedule.value) return;

  // Validasi: Bukti KBM hanya boleh disimpan pada hari yang sesuai dengan jadwal
  if (!isTodaySchedule(activeSchedule.value) && !auth.isAdmin) {
    showAlert(
      'Pengunggahan Tidak Diizinkan',
      `Bukti KBM dan jurnal hanya dapat disimpan pada hari yang sesuai dengan jadwal (${activeSchedule.value.hari.toUpperCase()}).`,
      'error'
    );
    return;
  }

  if (requireEvidence.value && !existingEvidence.value && !evidencePhotoFile.value) {
    showAlert(
      'Foto Bukti Mengajar Wajib',
      'Silakan lampirkan foto suasana belajar / aktivitas kelas sebagai bukti KBM tatap muka.',
      'warning'
    );
    return;
  }

  savingEvidence.value = true;
  try {
    const targetIds = (activeSchedule.value.schedule_ids && activeSchedule.value.schedule_ids.length > 0)
      ? activeSchedule.value.schedule_ids
      : [activeSchedule.value.id];

    // Simpan bukti KBM untuk semua sesi yang digabung (misal 2x35m sekaligus)
    await Promise.all(
      targetIds.map(async (schId) => {
        const formData = new FormData();
        formData.append('schedule_id', String(schId));
        formData.append('tanggal', attendanceDate.value);
        if (evidencePhotoFile.value) {
          formData.append('foto', evidencePhotoFile.value);
        }
        if (evidenceMateriAjar.value) formData.append('materi_ajar', evidenceMateriAjar.value);
        if (evidenceCatatan.value) formData.append('catatan', evidenceCatatan.value);

        return api.post('/attendance/evidence', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      })
    );

    showAlert(
      'Bukti KBM & Jurnal Berhasil Disimpan!',
      `Dokumentasi KBM untuk kelas ${activeSchedule.value.school_class?.nama} - ${activeSchedule.value.subject?.nama} (${activeSchedule.value.durasi_label}) telah tersimpan.`,
      'success'
    );
    await loadSchedules();
    currentViewMode.value = 'schedule_list';
  } catch (err: any) {
    console.error('Gagal menyimpan bukti KBM', err);
    showAlert('Gagal Menyimpan Bukti KBM', err.response?.data?.message || 'Terjadi kendala sistem.', 'error');
  } finally {
    savingEvidence.value = false;
  }
};

// ==============================================================
// 2. PRESENSI HARIAN SISWA (KHUSUS WALI KELAS 1X SEHARI)
// ==============================================================
const homeroomMeta = ref<any>(null);
const todayHomeroomStatus = ref<any>(null);

const isHomeroomAttendanceLocked = computed(() => {
  return Boolean(homeroomMeta.value?.already_recorded && !auth.isAdmin);
});

const checkTodayHomeroomStatus = async () => {
  if (!auth.isWaliKelas || !auth.primaryHomeroomClass) return;
  try {
    const res = await api.get('/attendance/students/session-sheet', {
      params: {
        class_id: auth.primaryHomeroomClass.id,
        tanggal: activeDate.value,
      },
    });
    if (res.data.success && res.data.data) {
      todayHomeroomStatus.value = res.data.data;
    }
  } catch (err) {
    console.error('Gagal mengecek status presensi hari ini:', err);
  }
};

const openHomeroomAttendance = async (targetClass?: any) => {
  const cls = targetClass || auth.primaryHomeroomClass;
  if (!cls) return;
  selectedHomeroomClass.value = cls;
  homeroomAttendanceDate.value = activeDate.value;
  homeroomStudents.value = [];
  homeroomAttendances.value = {};
  homeroomNotes.value = {};
  homeroomSearchQuery.value = '';
  homeroomMeta.value = null;
  currentViewMode.value = 'homeroom_attendance';
  await loadHomeroomStudents();
};

const loadHomeroomStudents = async () => {
  if (!selectedHomeroomClass.value) return;
  loadingHomeroom.value = true;
  try {
    const res = await api.get('/attendance/students/session-sheet', {
      params: {
        class_id: selectedHomeroomClass.value.id,
        tanggal: homeroomAttendanceDate.value,
      },
    });
    if (res.data.success && res.data.data) {
      const data = res.data.data;
      homeroomMeta.value = {
        already_recorded: data.already_recorded,
        recorded_count: data.recorded_count,
        recorded_at: data.recorded_at,
        updated_at: data.updated_at,
        recorded_by: data.recorded_by,
        summary: data.summary,
      };
      homeroomStudents.value = data.students || [];
      homeroomAttendances.value = {};
      homeroomNotes.value = {};
      homeroomStudents.value.forEach((s: any) => {
        homeroomAttendances.value[s.student_id] = s.status || 'hadir';
        homeroomNotes.value[s.student_id] = s.keterangan || '';
      });
    }
  } catch (err: any) {
    console.error('Gagal memuat lembar presensi wali kelas', err);
    showAlert('Gagal Memuat Presensi', err.response?.data?.message || 'Terjadi kesalahan sistem.', 'error');
  } finally {
    loadingHomeroom.value = false;
  }
};

const setAllHomeroomStatus = (status: 'hadir' | 'izin' | 'sakit' | 'alpa') => {
  homeroomStudents.value.forEach((s) => {
    homeroomAttendances.value[s.student_id] = status;
  });
};

const submitHomeroomAttendance = async () => {
  if (isHomeroomAttendanceLocked.value) {
    showAlert('Presensi Terkunci', 'Presensi hari ini sudah divalidasi dan tidak dapat diubah kembali oleh Wali Kelas. Tombol telah dinonaktifkan.', 'warning');
    return;
  }
  if (!selectedHomeroomClass.value || homeroomStudents.value.length === 0) return;
  savingHomeroom.value = true;
  try {
    const attendances = homeroomStudents.value.map((s) => ({
      student_id: s.student_id,
      status: homeroomAttendances.value[s.student_id] || 'hadir',
      keterangan: homeroomNotes.value[s.student_id] || null,
    }));

    const res = await api.post('/attendance/students', {
      class_id: selectedHomeroomClass.value.id,
      tanggal: homeroomAttendanceDate.value,
      attendances,
    });

    if (res.data.success) {
      showAlert(
        'Presensi Harian Disimpan & Tervalidasi!',
        `Presensi ${homeroomStudents.value.length} siswa rombel ${selectedHomeroomClass.value.nama} berhasil dicatat oleh Wali Kelas dan langsung masuk ke monitoring kepala sekolah & admin.`,
        'success'
      );
      await checkTodayHomeroomStatus();
      await loadHomeroomStudents();
      closeHomeroomView();
    }
  } catch (err: any) {
    console.error('Gagal menyimpan presensi harian', err);
    showAlert('Gagal Menyimpan Presensi', err.response?.data?.message || 'Terjadi kesalahan sistem.', 'error');
  } finally {
    savingHomeroom.value = false;
  }
};

const closeHomeroomView = () => {
  currentViewMode.value = 'schedule_list';
  if (route.query.mode === 'homeroom') {
    router.replace({ path: '/my-schedule' });
  }
};

const filteredHomeroomStudents = computed(() => {
  if (!homeroomSearchQuery.value.trim()) return homeroomStudents.value;
  const q = homeroomSearchQuery.value.toLowerCase();
  return homeroomStudents.value.filter(
    (s) => s.nama_lengkap.toLowerCase().includes(q) || (s.nis && s.nis.toLowerCase().includes(q))
  );
});

const homeroomStats = computed(() => {
  const list = homeroomStudents.value;
  const hadir = list.filter((s) => (homeroomAttendances.value[s.student_id] || s.status) === 'hadir').length;
  const izin = list.filter((s) => homeroomAttendances.value[s.student_id] === 'izin').length;
  const sakit = list.filter((s) => homeroomAttendances.value[s.student_id] === 'sakit').length;
  const alpa = list.filter((s) => homeroomAttendances.value[s.student_id] === 'alpa').length;
  return { hadir, izin, sakit, alpa, total: list.length };
});

const getInitials = (name?: string) => {
  if (!name) return '?';
  const parts = name.trim().split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
};

const getPhotoUrl = (path?: string | null) => {
  if (!path) return '';
  return path.startsWith('http') ? path : `/storage/${path}`;
};

onMounted(async () => {
  updateCurrentDate();
  scheduleMidnightUpdate();
  // Interval 30 detik untuk mendeteksi wake-up dari sleep mode atau pergantian hari
  intervalCheckTimer = setInterval(updateCurrentDate, 30000);

  await loadTeachers();
  await loadSchedules();
  await checkTodayHomeroomStatus();

  if (route.query.mode === 'homeroom' && (auth.isWaliKelas || auth.isAdmin)) {
    openHomeroomAttendance();
  }
});

watch(
  () => route.query.mode,
  (mode) => {
    if (mode === 'homeroom' && (auth.isWaliKelas || auth.isAdmin)) {
      openHomeroomAttendance();
    } else if (!mode && currentViewMode.value === 'homeroom_attendance') {
      currentViewMode.value = 'schedule_list';
    }
  }
);

onUnmounted(() => {
  if (midnightTimer) clearTimeout(midnightTimer);
  if (intervalCheckTimer) clearInterval(intervalCheckTimer);
});
</script>

<template>
  <div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Alert Floating Notification -->
    <transition
      enter-active-class="transform ease-out duration-300 transition"
      enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="alertState.show"
        class="fixed top-5 right-5 z-50 max-w-sm w-full bg-white rounded-2xl shadow-2xl border p-4 flex items-start gap-3"
        :class="
          alertState.type === 'success'
            ? 'border-emerald-200 bg-emerald-50/90 text-emerald-900'
            : alertState.type === 'warning'
            ? 'border-amber-200 bg-amber-50/90 text-amber-900'
            : 'border-rose-200 bg-rose-50/90 text-rose-900'
        "
      >
        <div class="shrink-0 mt-0.5">
          <svg v-if="alertState.type === 'success'" class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <svg v-else-if="alertState.type === 'warning'" class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <svg v-else class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </div>
        <div class="flex-1">
          <h4 class="text-xs font-bold">{{ alertState.title }}</h4>
          <p class="text-xs mt-0.5 opacity-90 leading-relaxed">{{ alertState.message }}</p>
        </div>
        <button @click="alertState.show = false" class="text-gray-400 hover:text-gray-600 font-bold text-sm">&times;</button>
      </div>
    </transition>

    <!-- ============================================================== -->
    <!-- VIEW MODE 1: DAFTAR JADWAL MENGAJAR (SCHEDULE LIST)            -->
    <!-- ============================================================== -->
    <div v-if="currentViewMode === 'schedule_list'" class="space-y-6">
      <!-- Wali Kelas Special Banner & Shortcut to Daily Homeroom Attendance -->
      <div
        v-if="auth.isWaliKelas"
        class="bg-gradient-to-r from-emerald-900 to-slate-900 text-white rounded-3xl p-5 sm:p-6 shadow-md border border-emerald-500/30 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
      >
        <div class="flex items-start sm:items-center gap-4">
          <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-400/40 text-emerald-300 flex items-center justify-center text-2xl shrink-0">
            ⭐
          </div>
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-400/20 text-emerald-300 border border-emerald-400/30">
                Wali Kelas Rombel
              </span>
              <span class="text-xs text-slate-300 font-semibold">
                Kelas: <strong class="text-white font-bold">{{ auth.primaryHomeroomClass?.nama }}</strong>
              </span>

              <!-- Status Validasi Presensi Hari Ini -->
              <span
                v-if="todayHomeroomStatus?.already_recorded"
                class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-500 text-slate-950 flex items-center gap-1 shadow-xs"
              >
                <span>✓ Sudah Diabsen Hari Ini (Terkunci)</span>
              </span>
              <span
                v-else
                class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-amber-400 text-slate-950 flex items-center gap-1 shadow-xs animate-pulse"
              >
                <span>⚠️ Belum Mengabsen Hari Ini</span>
              </span>
            </div>
            <h2 class="text-base font-bold text-white mt-1">Presensi Harian Siswa (Wali Kelas)</h2>
            <p v-if="todayHomeroomStatus?.already_recorded" class="text-xs text-emerald-200 mt-0.5">
              Presensi hari ini tervalidasi: <strong>{{ todayHomeroomStatus.summary?.hadir }} Hadir</strong>, 
              <strong>{{ todayHomeroomStatus.summary?.izin }} Izin</strong>, 
              <strong>{{ todayHomeroomStatus.summary?.sakit }} Sakit</strong>, 
              <strong>{{ todayHomeroomStatus.summary?.alpa }} Alpa</strong>.
              Tercatat oleh {{ todayHomeroomStatus.recorded_by || 'Wali Kelas' }} • Otomatis masuk ke monitoring admin & kepala sekolah.
              <span class="block text-emerald-300 font-semibold mt-0.5">🔒 Pengabsenan telah selesai dan terkunci (hanya 1x sehari).</span>
            </p>
            <p v-else class="text-xs text-slate-300 mt-0.5">
              Presensi kehadiran siswa rombel dilakukan sehari sekali oleh Anda sebagai Wali Kelas. Silakan lakukan pengabsenan hari ini.
            </p>
          </div>
        </div>
        <div class="shrink-0 flex items-center gap-2">
          <button
            @click="openHomeroomAttendance()"
            :class="todayHomeroomStatus?.already_recorded 
              ? 'bg-slate-800 hover:bg-slate-700 text-emerald-300 border border-emerald-500/40 shadow-md' 
              : 'bg-emerald-500 hover:bg-emerald-400 text-slate-950 shadow-lg shadow-emerald-500/20'"
            class="px-5 py-2.5 font-bold rounded-xl text-xs transition flex items-center gap-2 cursor-pointer"
          >
            <span>{{ todayHomeroomStatus?.already_recorded ? '👁️' : '📋' }}</span>
            <span>{{ todayHomeroomStatus?.already_recorded ? 'Lihat Rekap Presensi (Terkunci)' : 'Catat Presensi Siswa Sekarang' }}</span>
          </button>
        </div>
      </div>

      <!-- Top Hero Header -->
      <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-100 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-5">
        <div class="space-y-1.5">
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[11px] font-bold rounded-full uppercase tracking-wider">
              Portal KBM Guru
            </span>
            <span v-if="auth.isGuru && !auth.isAdmin" class="text-xs text-slate-500 font-medium">
              Jadwal Mengajar Anda
            </span>
            <span v-else-if="auth.isAdmin" class="text-xs text-purple-700 font-medium bg-purple-50 px-2 py-0.5 rounded-full border border-purple-200">
              Mode Administrator & Kepala Sekolah
            </span>
          </div>
          <h1 class="text-2xl font-black text-slate-900 tracking-tight">
            Jadwal Mengajar & Jurnal KBM
          </h1>
          <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
            Tampilan kartu jadwal pelajaran untuk guru pengampu. Klik tombol <strong>Unggah Bukti KBM & Jurnal</strong> untuk melampirkan dokumentasi kegiatan kelas dan materi ajar tatap muka.
          </p>
        </div>

        <!-- Quick Metrics Bar -->
        <div class="flex items-center gap-3 shrink-0">
          <div class="bg-slate-50 border border-slate-200/60 rounded-2xl p-3.5 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold text-lg">
              {{ todaySchedulesCount }}
            </div>
            <div>
              <div class="text-[11px] text-slate-400 font-medium uppercase tracking-wider">Jadwal Hari Ini</div>
              <div class="text-xs font-bold text-slate-800">
                <span class="text-emerald-600">{{ completedTodayCount }}</span> dari {{ todaySchedulesCount }} Bukti Terunggah
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filter & Toolbar Bar -->
      <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <!-- Filter Hari (Pill Selector) -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 scrollbar-none text-xs">
          <button
            @click="selectedHari = ''; loadSchedules()"
            :class="selectedHari === '' ? 'bg-slate-900 text-white font-bold shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            class="px-3.5 py-2 rounded-xl transition whitespace-nowrap"
          >
            Semua Hari ({{ schedules.length }})
          </button>
          <button
            @click="selectedHari = currentTodayName; loadSchedules()"
            :class="selectedHari === currentTodayName ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 font-semibold'"
            class="px-3.5 py-2 rounded-xl transition whitespace-nowrap flex items-center gap-1.5"
          >
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            Hari Ini ({{ currentTodayName.toUpperCase() }})
          </button>
          <button
            v-for="hari in ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu']"
            :key="hari"
            @click="selectedHari = hari; loadSchedules()"
            :class="selectedHari === hari ? 'bg-slate-900 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            class="px-3.5 py-2 rounded-xl transition whitespace-nowrap capitalize font-medium"
          >
            {{ hari }}
          </button>
        </div>

        <!-- Controls Right: Teacher Selector (for Admin) & Search Box -->
        <div class="flex flex-wrap items-center gap-2.5">
          <!-- Admin Teacher Filter -->
          <div v-if="auth.isAdmin" class="min-w-[200px]">
            <select
              v-model="selectedTeacherId"
              @change="loadSchedules"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500"
            >
              <option value="">Semua Guru Pengampu</option>
              <option value="me" v-if="auth.teacherProfile">Jadwal Mengajar Saya ({{ auth.userName }})</option>
              <option v-for="t in teachersList" :key="t.id" :value="String(t.id)">
                {{ t.nama_lengkap }}
              </option>
            </select>
          </div>

          <!-- Search Input -->
          <div class="relative flex-1 sm:w-64">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari mapel, kelas, atau ruang..."
              class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 transition"
            />
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>

          <button
            @click="loadSchedules"
            :disabled="loading"
            class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition cursor-pointer"
            title="Segarkan Jadwal"
          >
            <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Indikator Makna Warna Hijau & Merah -->
      <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
        <div class="flex flex-wrap items-center gap-2 sm:gap-4">
          <span class="font-bold text-slate-800 flex items-center gap-1.5">
            <span>🎨</span>
            <span>Arti Warna Kartu:</span>
          </span>
          <div class="flex items-center gap-1.5 bg-emerald-50 text-emerald-900 border border-emerald-200 px-3 py-1 rounded-xl font-bold">
            <span class="w-3 h-3 rounded-full bg-[#137333] shadow-xs inline-block"></span>
            <span>HIJAU = Jadwal Hari Ini ({{ currentTodayName.toUpperCase() }})</span>
          </div>
          <div class="flex items-center gap-1.5 bg-red-50 text-red-900 border border-red-200 px-3 py-1 rounded-xl font-bold">
            <span class="w-3 h-3 rounded-full bg-[#b3261e] shadow-xs inline-block"></span>
            <span>MERAH = Jadwal Bukan Hari Ini</span>
          </div>
        </div>

        <div class="flex items-center gap-3 text-[11px] text-slate-500">
          <button
            v-if="!selectedHari"
            @click="sortByTodayFirst = !sortByTodayFirst"
            type="button"
            class="hover:text-emerald-700 font-semibold underline transition cursor-pointer"
          >
            {{ sortByTodayFirst ? 'Urutan: Hari Ini Teratas' : 'Urutan: Sesuai Urutan Hari' }}
          </button>
          <span class="hidden sm:inline text-slate-300">•</span>
          <span class="flex items-center gap-1 text-slate-400">
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Otomatis berganti status jam 00:00 WIB
          </span>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="py-24 text-center">
        <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-emerald-500 border-t-transparent mb-3"></div>
        <p class="text-xs font-semibold text-slate-500">Memuat kartu jadwal mengajar...</p>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="filteredSchedules.length === 0"
        class="bg-white rounded-3xl p-12 border border-dashed border-slate-200 text-center space-y-4 shadow-xs"
      >
        <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto text-2xl font-bold">
          📅
        </div>
        <div class="max-w-md mx-auto space-y-1">
          <h3 class="text-base font-bold text-slate-800">Tidak Ada Jadwal Mengajar</h3>
          <p class="text-xs text-slate-500 leading-relaxed">
            {{
              auth.isGuru && !auth.isAdmin
                ? 'Anda belum memiliki jadwal mengajar pada pilihan filter ini. Silakan koordinasi dengan Bagian Kurikulum/Staf Admin jika ada penyesuaian jadwal.'
                : 'Tidak ditemukan jadwal KBM untuk kriteria yang dipilih. Anda dapat membuat jadwal baru di menu Manajemen Kelas & Jadwal.'
            }}
          </p>
        </div>
        <router-link
          v-if="auth.isAdmin"
          to="/classes-schedules"
          class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-md transition"
        >
          + Buat Jadwal KBM Baru
        </router-link>
      </div>

      <!-- GRID CARDS KBM (Compact & Elegant Redesign) -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div
          v-for="sch in filteredSchedules"
          :key="sch.id"
          class="bg-white rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-lg transition-all duration-300 flex flex-col justify-between overflow-hidden group"
        >
          <div>
            <!-- CARD HEADER (Color banner) -->
            <div
              class="px-4 py-3.5 text-white relative flex flex-col justify-between min-h-[96px] transition-colors"
              :class="getCardTheme(sch).bg"
            >
              <!-- Top Row: Hari, Waktu Efektif & Status Hari Ini -->
              <div class="flex items-center justify-between gap-2 mb-1.5">
                <div class="flex items-center gap-1.5 text-xs font-bold tracking-wide">
                  <svg class="w-3.5 h-3.5 opacity-90 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>{{ sch.hari.toUpperCase() }} • {{ sch.jam_mulai_efektif }} - {{ sch.jam_selesai_efektif }}</span>
                </div>

                <!-- Status Badge -->
                <div
                  v-if="isTodaySchedule(sch)"
                  class="px-2 py-0.5 bg-black/30 backdrop-blur-xs text-[10px] font-black uppercase tracking-wider rounded-full flex items-center gap-1 border border-white/20 text-white shrink-0 shadow-xs"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-ping"></span>
                  <span>HARI INI</span>
                </div>
                <div
                  v-else
                  class="px-2 py-0.5 bg-black/30 backdrop-blur-xs text-[10px] font-bold uppercase tracking-wider rounded-full border border-white/20 text-red-100 shrink-0 shadow-xs"
                >
                  <span>BUKAN HARI INI</span>
                </div>
              </div>

              <!-- Subject Title & Duration Badge -->
              <div class="flex items-start justify-between gap-2">
                <h2 class="text-sm sm:text-base font-black tracking-wide uppercase line-clamp-2 leading-snug drop-shadow-xs">
                  {{ sch.subject?.nama || 'MATA PELAJARAN' }}
                </h2>
                <!-- Badge Durasi Jam Pelajaran (Contoh: 2 JP (2x35m)) -->
                <span
                  class="shrink-0 px-2 py-0.5 rounded-md text-[10px] sm:text-[11px] font-black bg-white/20 backdrop-blur-xs text-white border border-white/30 whitespace-nowrap shadow-xs"
                >
                  {{ sch.durasi_label }}
                </span>
              </div>

              <!-- Tematik Muatan Pelajaran (Mini Chips) -->
              <div
                v-if="sch.subject?.is_tematik && sch.subject?.muatan_pelajaran?.length"
                class="mt-2 flex flex-wrap items-center gap-1"
              >
                <span
                  v-for="muatan in sch.subject.muatan_pelajaran"
                  :key="muatan"
                  class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-black/25 text-white/95 border border-white/20"
                >
                  {{ muatan }}
                </span>
              </div>
            </div>

            <!-- CARD BODY (Compact 2-Column Info Grid) -->
            <div class="p-4 space-y-3 text-xs text-slate-700 bg-white">
              <div class="grid grid-cols-2 gap-2">
                <!-- Rombel Kelas -->
                <div class="p-2.5 bg-slate-50/80 rounded-xl border border-slate-100 flex items-start gap-2">
                  <span class="text-purple-600 text-sm shrink-0">👥</span>
                  <div class="min-w-0">
                    <p class="text-[9px] uppercase font-bold text-slate-400 leading-tight">Kelas / Rombel</p>
                    <p class="font-bold text-purple-700 truncate text-xs">{{ sch.school_class?.nama || '-' }}</p>
                    <p class="text-[10px] text-slate-400">Tingkat {{ sch.school_class?.tingkat ?? 1 }}</p>
                  </div>
                </div>

                <!-- No Ruangan & Siswa -->
                <div class="p-2.5 bg-slate-50/80 rounded-xl border border-slate-100 flex items-start gap-2">
                  <span class="text-emerald-600 text-sm shrink-0">📍</span>
                  <div class="min-w-0">
                    <p class="text-[9px] uppercase font-bold text-slate-400 leading-tight">Ruangan</p>
                    <p class="font-bold text-slate-800 truncate text-xs">{{ sch.room?.nama || sch.room?.kode || 'Ruang Belajar' }}</p>
                    <p class="text-[10px] text-slate-400">{{ sch.school_class?.active_students_count ?? 0 }} Siswa</p>
                  </div>
                </div>

                <!-- Guru Pengampu -->
                <div class="p-2.5 bg-slate-50/80 rounded-xl border border-slate-100 flex items-start gap-2 col-span-2">
                  <span class="text-indigo-600 text-sm shrink-0">👤</span>
                  <div class="min-w-0 flex-1 flex items-center justify-between gap-2">
                    <div class="min-w-0">
                      <p class="text-[9px] uppercase font-bold text-slate-400 leading-tight">Guru Pengampu</p>
                      <p class="font-bold text-slate-900 truncate text-xs">{{ sch.teacher?.nama_lengkap || '-' }}</p>
                      <p v-if="sch.teacher?.nip" class="text-[10px] text-slate-400 font-mono truncate">NIP: {{ sch.teacher.nip }}</p>
                    </div>
                    <span
                      v-if="sch.subject?.is_tematik"
                      class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-black bg-purple-100 text-purple-700 border border-purple-200"
                    >
                      TEMA {{ sch.subject?.tema_nomor || '' }}
                    </span>
                  </div>
                </div>

                <!-- Sesi / Jam Pelajaran Gabungan -->
                <div class="p-2.5 bg-slate-50/80 rounded-xl border border-slate-100 flex items-start gap-2 col-span-2">
                  <span class="text-amber-600 text-sm shrink-0">⏱️</span>
                  <div class="min-w-0 flex-1 flex items-center justify-between gap-2">
                    <div>
                      <p class="text-[9px] uppercase font-bold text-slate-400 leading-tight">Sesi Pembelajaran</p>
                      <p class="font-bold text-slate-800 text-xs">{{ sch.sesi_label }}</p>
                    </div>
                    <span class="text-[11px] font-mono font-bold text-slate-600 bg-white px-2 py-0.5 rounded border border-slate-200 shrink-0">
                      {{ sch.jam_mulai_efektif }} - {{ sch.jam_selesai_efektif }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Status Bukti KBM Hari Ini -->
              <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                <span class="text-[11px] text-slate-400 font-medium">Status KBM:</span>
                <span
                  v-if="(sch as any).today_evidence_count > 0"
                  class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[11px] font-bold flex items-center gap-1"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  Bukti Terunggah
                </span>
                <span
                  v-else-if="isTodaySchedule(sch)"
                  class="px-2.5 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-[11px] font-bold flex items-center gap-1.5"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                  Belum Unggah Bukti
                </span>
                <span
                  v-else
                  class="px-2.5 py-0.5 bg-slate-100 text-slate-500 rounded-full text-[11px] font-medium"
                >
                  Jadwal Hari {{ sch.hari.toUpperCase() }}
                </span>
              </div>
            </div>
          </div>

          <!-- CARD FOOTER ACTION BAR -->
          <div class="p-4 pt-0 bg-white">
            <button
              v-if="isTodaySchedule(sch) || (sch as any).today_evidence_count > 0 || auth.isAdmin"
              @click="openKbmEvidenceForm(sch)"
              :class="
                (sch as any).today_evidence_count > 0
                  ? 'bg-purple-600 hover:bg-purple-700 text-white shadow-xs shadow-purple-600/20 active:scale-[0.98]'
                  : 'bg-[#137333] hover:bg-[#0d5926] text-white shadow-xs shadow-emerald-700/25 active:scale-[0.98]'
              "
              class="w-full py-2.5 px-3.5 font-bold text-xs rounded-xl transition-all flex items-center justify-center gap-2 cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
              </svg>
              <span>{{
                (sch as any).today_evidence_count > 0
                  ? 'Lihat / Edit Bukti KBM & Jurnal'
                  : 'Unggah Bukti KBM & Jurnal (Hari Ini)'
              }}</span>
            </button>

            <!-- Jika BUKAN jadwal hari ini: DITUTUP / TIDAK BISA UNGGAH -->
            <div
              v-else
              class="w-full py-2.5 px-3 bg-slate-50 border border-slate-200 text-slate-400 font-semibold text-xs rounded-xl flex items-center justify-center gap-1.5 select-none cursor-not-allowed text-center"
              :title="`Pengunggahan bukti KBM ditutup. Hanya dapat diunggah pada hari ${sch.hari.toUpperCase()}.`"
            >
              <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              <span class="truncate">Hanya Dibuka Hari {{ sch.hari.toUpperCase() }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- VIEW MODE 2: UNGGAH BUKTI KBM & JURNAL MENGAJAR (FULL PAGE)    -->
    <!-- ============================================================== -->
    <div v-else-if="currentViewMode === 'kbm_evidence'" class="space-y-6">
      <!-- Top Action Bar -->
      <div class="flex items-center justify-between bg-white p-4 sm:p-5 rounded-3xl border border-slate-100 shadow-sm">
        <button
          @click="currentViewMode = 'schedule_list'"
          class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl transition cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Kembali ke Jadwal</span>
        </button>
        <div class="flex items-center gap-2 text-xs">
          <span class="text-slate-400 font-medium">Tanggal KBM:</span>
          <input
            v-model="attendanceDate"
            type="date"
            :disabled="!auth.isAdmin"
            class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 disabled:opacity-75 disabled:cursor-not-allowed"
          />
        </div>
      </div>

      <!-- Main Evidence & Journal Form Card -->
      <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-sm space-y-6">
        <!-- Schedule Details Header Banner -->
        <div class="p-5 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 text-white flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="space-y-1">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="px-2.5 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-full text-[10px] font-bold uppercase tracking-wider">
                Dokumentasi KBM
              </span>
              <span class="text-xs text-slate-300">
                Ruang: {{ activeSchedule?.room?.nama || 'Ruang Belajar' }}
              </span>
            </div>
            <h2 class="text-xl font-black text-white tracking-tight flex items-center gap-2 flex-wrap">
              <span>{{ activeSchedule?.subject?.nama }} — {{ activeSchedule?.school_class?.nama }}</span>
              <span
                v-if="activeSchedule?.subject?.is_tematik"
                class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-purple-500/25 text-purple-300 border border-purple-400/30"
              >
                TEMATIK TEMA {{ activeSchedule?.subject?.tema_nomor }}
              </span>
            </h2>
            <p class="text-xs text-slate-300">
              Guru Pengampu: <strong class="text-white">{{ activeSchedule?.teacher?.nama_lengkap }}</strong> •
              Sesi: <strong>{{ activeSchedule?.sesi_label }}</strong> ({{ activeSchedule?.jam_mulai_efektif }} - {{ activeSchedule?.jam_selesai_efektif }})
            </p>
          </div>
          <div class="shrink-0 flex items-center gap-2">
            <span
              v-if="existingEvidence || evidencePhotoPreview"
              class="px-3 py-1 bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 rounded-full text-xs font-bold flex items-center gap-1.5"
            >
              <span>✓</span>
              <span>Bukti Tersimpan</span>
            </span>
          </div>
        </div>

        <!-- Form Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Left: Photo Upload & Preview -->
          <div class="space-y-3">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
              Foto Suasana Kelas / Kegiatan KBM *
            </label>
            <div
              class="border-2 border-dashed border-slate-300 rounded-3xl p-4 text-center bg-slate-50 hover:bg-slate-100/70 hover:border-emerald-500 transition relative overflow-hidden flex flex-col items-center justify-center min-h-[220px]"
            >
              <img
                v-if="evidencePhotoPreview"
                :src="evidencePhotoPreview"
                alt="Preview Bukti Mengajar"
                class="w-full h-56 object-cover rounded-2xl shadow-sm"
              />
              <div v-else class="space-y-2 p-4">
                <div class="w-14 h-14 bg-emerald-100 text-emerald-700 rounded-2xl flex items-center justify-center text-2xl mx-auto">
                  📸
                </div>
                <span class="text-xs font-bold text-slate-800 block">Ambil / Unggah Foto Bukti Kelas</span>
                <span class="text-[11px] text-slate-400 block max-w-xs mx-auto">
                  Lampirkan foto suasana belajar mengajar tatap muka sebagai bukti fisik pelaksanaan KBM.
                </span>
                <span class="inline-block px-3 py-1 bg-white border border-slate-200 rounded-xl text-[11px] font-semibold text-slate-600 shadow-2xs">
                  Pilih File (JPG, PNG, WebP maks 5MB)
                </span>
              </div>
              <input
                type="file"
                accept="image/*"
                capture="environment"
                @change="handleEvidencePhotoChange"
                class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
              />
            </div>
          </div>

          <!-- Right: Materi & Catatan Jurnal -->
          <div class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">
                Pokok Bahasan / Materi Ajar *
              </label>
              <input
                v-model="evidenceMateriAjar"
                type="text"
                placeholder="Contoh: Bab 2 - Perkalian & Pembagian / Surah At-Tin Ayat 1-4"
                class="w-full border border-slate-300 rounded-2xl px-4 py-3 text-xs font-semibold focus:ring-2 focus:ring-emerald-500 bg-white shadow-2xs"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">
                Catatan KBM / Evaluasi Pembelajaran
              </label>
              <textarea
                v-model="evidenceCatatan"
                rows="5"
                placeholder="Contoh: Siswa aktif berdiskusi kelompok, evaluasi tugas berjalan tertib, materi tersampaikan sesuai RPP..."
                class="w-full border border-slate-300 rounded-2xl p-4 text-xs font-medium focus:ring-2 focus:ring-emerald-500 bg-white shadow-2xs resize-none"
              ></textarea>
            </div>
            <div class="p-3.5 bg-emerald-50 rounded-2xl border border-emerald-200/80 text-xs text-emerald-800 flex items-start gap-2.5">
              <span class="text-base shrink-0">ℹ️</span>
              <p class="text-[11px] leading-relaxed">
                Presensi kehadiran siswa dilakukan sekali sehari oleh Wali Kelas masing-masing. Guru mapel cukup mengunggah foto bukti KBM dan jurnal materi ajar di atas.
              </p>
            </div>
          </div>
        </div>

        <!-- Action Footer -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
          <button
            type="button"
            @click="currentViewMode = 'schedule_list'"
            class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-xs font-bold hover:bg-slate-50 transition cursor-pointer"
          >
            Batal
          </button>
          <button
            type="button"
            @click="submitKbmEvidence"
            :disabled="savingEvidence || loadingEvidence"
            class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/25 transition disabled:opacity-50 flex items-center gap-2 cursor-pointer"
          >
            <svg v-if="savingEvidence" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span v-else>💾</span>
            <span>{{ savingEvidence ? 'Menyimpan Bukti KBM...' : 'Simpan Bukti KBM & Jurnal' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- VIEW MODE 3: PRESENSI HARIAN SISWA (WALI KELAS FULL PAGE)      -->
    <!-- ============================================================== -->
    <div v-else-if="currentViewMode === 'homeroom_attendance'" class="space-y-6">
      <!-- Top Action Bar -->
      <div class="flex items-center justify-between bg-white p-4 sm:p-5 rounded-3xl border border-slate-100 shadow-sm">
        <button
          @click="closeHomeroomView"
          class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl transition cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Kembali ke Jadwal</span>
        </button>
        <div class="flex items-center gap-2 text-xs">
          <span class="text-slate-400 font-medium">Tanggal Presensi:</span>
          <input
            v-model="homeroomAttendanceDate"
            @change="loadHomeroomStudents"
            type="date"
            class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-800 focus:outline-hidden focus:ring-2 focus:ring-emerald-500"
          />
        </div>
      </div>

      <!-- Main Homeroom Attendance Card -->
      <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded-full uppercase tracking-wider">
                Presensi Harian Wali Kelas
              </span>
              <span class="text-xs text-slate-400">1x Sehari</span>
            </div>
            <h2 class="text-xl font-black text-slate-900 mt-1">
              Rombel Kelas: {{ selectedHomeroomClass?.nama }}
            </h2>
            <p class="text-xs text-slate-500">
              Catat kehadiran harian siswa rombel binaan Anda. Rekap ini langsung terhubung ke buku induk dan rapor.
            </p>

            <!-- Switcher jika Wali Kelas memegang lebih dari 1 rombel -->
            <div v-if="auth.homeroomClasses.length > 1" class="mt-3 flex items-center gap-2 flex-wrap">
              <span class="text-[11px] text-slate-400 font-medium">Pilih Rombel:</span>
              <button
                v-for="c in auth.homeroomClasses"
                :key="c.id"
                @click="openHomeroomAttendance(c)"
                :class="selectedHomeroomClass?.id === c.id ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                class="px-3 py-1 rounded-lg text-xs transition cursor-pointer"
              >
                {{ c.nama }}
              </button>
            </div>
          </div>

          <!-- Quick Hadirkan Semua Button -->
          <div v-if="!isHomeroomAttendanceLocked" class="flex items-center gap-2">
            <button
              @click="setAllHomeroomStatus('hadir')"
              class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition shadow-md shadow-emerald-600/20 flex items-center gap-1.5 cursor-pointer"
            >
              <span>✓</span>
              <span>Hadirkan Semua</span>
            </button>
          </div>
          <div v-else class="flex items-center gap-2">
            <span class="px-3.5 py-1.5 bg-slate-100 text-slate-600 rounded-xl text-xs font-bold flex items-center gap-1.5 border border-slate-200 shadow-2xs">
              <span>🔒 Mode Lihat Rekap (Terkunci)</span>
            </span>
          </div>
        </div>

        <!-- Status Validasi Presensi Hari Ini untuk Lembar Input -->
        <div
          v-if="homeroomMeta?.already_recorded"
          class="p-4.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-950 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs"
        >
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black text-sm shrink-0">
              ✓
            </div>
            <div>
              <h4 class="font-bold text-xs sm:text-sm text-emerald-950 flex items-center gap-2 flex-wrap">
                <span>Presensi Rombel Ini Sudah Tervalidasi & Tercatat Hari Ini</span>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-200 text-emerald-900 border border-emerald-300">
                  🔒 TERKUNCI
                </span>
              </h4>
              <p class="text-xs text-emerald-800 mt-1 leading-relaxed">
                Tercatat pada <strong>{{ homeroomMeta.updated_at ? new Date(homeroomMeta.updated_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB' : '-' }}</strong> 
                oleh <strong>{{ homeroomMeta.recorded_by || 'Wali Kelas' }}</strong>. 
                <span v-if="!auth.isAdmin">
                  Sesuai ketentuan, pengabsenan hanya dapat dilakukan 1 kali dalam sehari. Tombol simpan dan ubah telah <strong>dinonaktifkan</strong>. Jika terdapat kekeliruan data, silakan hubungi Administrator atau Kepala Sekolah.
                </span>
                <span v-else>
                  Mode Administrator / Kepala Sekolah: Anda memiliki hak akses supervisi penuh untuk mengoreksi data presensi rombel ini.
                </span>
              </p>
            </div>
          </div>
          <span class="px-3 py-1 bg-emerald-600 text-white rounded-xl text-[10px] font-extrabold uppercase shrink-0 text-center self-start sm:self-center">
            Status: Sudah Diabsen
          </span>
        </div>

        <div
          v-else
          class="p-4.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-950 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs"
        >
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-xl bg-amber-400 text-slate-950 flex items-center justify-center font-black text-sm shrink-0">
              ⚠️
            </div>
            <div>
              <h4 class="font-bold text-xs sm:text-sm text-amber-950">
                Presensi Rombel Ini Belum Dicatat untuk Tanggal Terpilih
              </h4>
              <p class="text-xs text-amber-800 mt-0.5 leading-relaxed">
                Silakan periksa dan sesuaikan kehadiran siswa di bawah ini, lalu klik <strong>Simpan Presensi Harian Siswa</strong>.
              </p>
            </div>
          </div>
          <span class="px-3 py-1 bg-amber-500 text-slate-950 rounded-xl text-[10px] font-extrabold uppercase shrink-0 text-center self-start sm:self-center">
            Status: Menunggu Pengisian
          </span>
        </div>

        <!-- Attendance Stats Chips Bar -->
        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="flex flex-wrap items-center gap-2 sm:gap-3">
            <span class="font-bold text-slate-700">Total: {{ homeroomStats.total }} Siswa</span>
            <span class="text-slate-300">•</span>
            <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 font-bold rounded-lg text-[11px]">
              Hadir: {{ homeroomStats.hadir }}
            </span>
            <span class="px-2.5 py-1 bg-amber-100 text-amber-800 font-bold rounded-lg text-[11px]">
              Izin: {{ homeroomStats.izin }}
            </span>
            <span class="px-2.5 py-1 bg-sky-100 text-sky-800 font-bold rounded-lg text-[11px]">
              Sakit: {{ homeroomStats.sakit }}
            </span>
            <span class="px-2.5 py-1 bg-rose-100 text-rose-800 font-bold rounded-lg text-[11px]">
              Alpa: {{ homeroomStats.alpa }}
            </span>
          </div>

          <!-- Search Filter -->
          <div class="relative w-full sm:w-56">
            <input
              v-model="homeroomSearchQuery"
              type="text"
              placeholder="Cari siswa..."
              class="w-full pl-8 pr-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs focus:outline-hidden focus:ring-1 focus:ring-emerald-500"
            />
            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>

        <!-- Student Attendance List Table -->
        <div v-if="loadingHomeroom" class="py-16 text-center text-slate-400 text-xs">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-3 border-emerald-500 border-t-transparent mb-2"></div>
          <p>Memuat lembar presensi kelas binaan...</p>
        </div>

        <div v-else class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-xs">
          <div class="divide-y divide-slate-100">
            <div
              v-for="(st, sIdx) in filteredHomeroomStudents"
              :key="st.student_id"
              class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/70 transition"
            >
              <!-- Student Identity -->
              <div class="flex items-center gap-3">
                <span class="text-[11px] font-mono text-slate-400 w-6 text-center">{{ sIdx + 1 }}</span>
                <div class="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
                  <img
                    v-if="st.foto_path"
                    :src="getPhotoUrl(st.foto_path)"
                    :alt="st.nama_lengkap"
                    class="w-full h-full object-cover"
                    @error="(e: any) => e.target.style.display = 'none'"
                  />
                  <span v-else class="text-[11px] font-bold text-slate-500">{{ getInitials(st.nama_lengkap) }}</span>
                </div>
                <div>
                  <h5 class="text-xs font-bold text-slate-900">{{ st.nama_lengkap }}</h5>
                  <div class="flex items-center gap-2 text-[10px] text-slate-400 mt-0.5">
                    <span class="font-mono">NIS: {{ st.nis || '-' }}</span>
                    <span>•</span>
                    <span :class="st.jenis_kelamin === 'L' ? 'text-sky-600 font-semibold' : 'text-rose-600 font-semibold'">
                      {{ st.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Radio Pill Buttons & Notes -->
              <div class="flex items-center gap-2 flex-wrap self-end sm:self-center">
                <!-- Hadir -->
                <button
                  type="button"
                  :disabled="isHomeroomAttendanceLocked"
                  @click="!isHomeroomAttendanceLocked && (homeroomAttendances[st.student_id] = 'hadir')"
                  :class="[
                    homeroomAttendances[st.student_id] === 'hadir' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600',
                    isHomeroomAttendanceLocked ? 'cursor-not-allowed opacity-90' : 'hover:bg-emerald-50 hover:text-emerald-700 cursor-pointer'
                  ]"
                  class="px-3 py-1.5 rounded-xl text-xs transition"
                >
                  Hadir
                </button>
                <!-- Izin -->
                <button
                  type="button"
                  :disabled="isHomeroomAttendanceLocked"
                  @click="!isHomeroomAttendanceLocked && (homeroomAttendances[st.student_id] = 'izin')"
                  :class="[
                    homeroomAttendances[st.student_id] === 'izin' ? 'bg-amber-500 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600',
                    isHomeroomAttendanceLocked ? 'cursor-not-allowed opacity-90' : 'hover:bg-amber-50 hover:text-amber-700 cursor-pointer'
                  ]"
                  class="px-3 py-1.5 rounded-xl text-xs transition"
                >
                  Izin
                </button>
                <!-- Sakit -->
                <button
                  type="button"
                  :disabled="isHomeroomAttendanceLocked"
                  @click="!isHomeroomAttendanceLocked && (homeroomAttendances[st.student_id] = 'sakit')"
                  :class="[
                    homeroomAttendances[st.student_id] === 'sakit' ? 'bg-sky-500 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600',
                    isHomeroomAttendanceLocked ? 'cursor-not-allowed opacity-90' : 'hover:bg-sky-50 hover:text-sky-700 cursor-pointer'
                  ]"
                  class="px-3 py-1.5 rounded-xl text-xs transition"
                >
                  Sakit
                </button>
                <!-- Alpa -->
                <button
                  type="button"
                  :disabled="isHomeroomAttendanceLocked"
                  @click="!isHomeroomAttendanceLocked && (homeroomAttendances[st.student_id] = 'alpa')"
                  :class="[
                    homeroomAttendances[st.student_id] === 'alpa' ? 'bg-rose-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600',
                    isHomeroomAttendanceLocked ? 'cursor-not-allowed opacity-90' : 'hover:bg-rose-50 hover:text-rose-700 cursor-pointer'
                  ]"
                  class="px-3 py-1.5 rounded-xl text-xs transition"
                >
                  Alpa
                </button>

                <!-- Optional Note input if not Hadir -->
                <input
                  v-if="homeroomAttendances[st.student_id] !== 'hadir'"
                  v-model="homeroomNotes[st.student_id]"
                  :disabled="isHomeroomAttendanceLocked"
                  type="text"
                  placeholder="Keterangan..."
                  class="w-28 px-2.5 py-1 text-xs border border-slate-200 rounded-xl bg-white disabled:bg-slate-100 disabled:cursor-not-allowed disabled:text-slate-500"
                />
              </div>
            </div>

            <div v-if="filteredHomeroomStudents.length === 0" class="py-12 text-center text-slate-400 text-xs">
              Tidak ada data siswa yang cocok dengan pencarian.
            </div>
          </div>
        </div>

        <!-- Submit Button Footer -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-slate-100">
          <div v-if="isHomeroomAttendanceLocked" class="text-xs text-amber-800 font-semibold flex items-center gap-1.5 bg-amber-50 px-3 py-2 rounded-xl border border-amber-200">
            <span>🔒</span>
            <span>Presensi hari ini telah tervalidasi. Tombol dinonaktifkan (Pengabsenan hanya 1x sehari).</span>
          </div>
          <div v-else class="text-xs text-slate-400">
            Pastikan data kehadiran murid sudah sesuai sebelum menekan tombol simpan.
          </div>

          <div class="flex items-center gap-3 self-end sm:self-auto">
            <button
              type="button"
              @click="closeHomeroomView"
              class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-xs font-bold hover:bg-slate-50 transition cursor-pointer"
            >
              {{ isHomeroomAttendanceLocked ? 'Tutup' : 'Batal' }}
            </button>
            <button
              v-if="isHomeroomAttendanceLocked"
              type="button"
              disabled
              class="px-6 py-2.5 bg-slate-200 text-slate-400 border border-slate-300 rounded-xl text-xs font-bold shadow-none flex items-center gap-2 cursor-not-allowed"
              title="Wali kelas sudah mengabsen hari ini. Tombol dinonaktifkan."
            >
              <span>🔒</span>
              <span>Presensi Sudah Divalidasi (Terkunci)</span>
            </button>
            <button
              v-else
              type="button"
              @click="submitHomeroomAttendance"
              :disabled="savingHomeroom || homeroomStudents.length === 0"
              class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/25 transition disabled:opacity-50 flex items-center gap-2 cursor-pointer"
            >
              <svg v-if="savingHomeroom" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span v-else>💾</span>
              <span>{{ savingHomeroom ? 'Menyimpan Presensi...' : 'Simpan Presensi Harian Siswa' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
