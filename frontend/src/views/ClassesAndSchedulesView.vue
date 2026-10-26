<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';

// Modular Child Components
import ClassTab from '../components/classes-schedules/ClassTab.vue';
import ClassForm from '../components/classes-schedules/ClassForm.vue';
import SubjectTab from '../components/classes-schedules/SubjectTab.vue';
import SubjectForm from '../components/classes-schedules/SubjectForm.vue';
import TeacherAssignmentTab from '../components/classes-schedules/TeacherAssignmentTab.vue';
import ScheduleTab from '../components/classes-schedules/ScheduleTab.vue';
import ScheduleForm from '../components/classes-schedules/ScheduleForm.vue';
import TimeSlotTab from '../components/classes-schedules/TimeSlotTab.vue';
import RoomTab from '../components/classes-schedules/RoomTab.vue';
import AcademicYearTab from '../components/classes-schedules/AcademicYearTab.vue';
import CalendarTab from '../components/classes-schedules/CalendarTab.vue';

const route = useRoute();

// View Mode: 'tabs' | 'form-class' | 'form-subject' | 'form-schedule'
const viewMode = ref<'tabs' | 'form-class' | 'form-subject' | 'form-schedule'>('tabs');
const activeTab = ref<string>('classes');

// Selected models for editing
const selectedClass = ref<any | null>(null);
const selectedSubject = ref<any | null>(null);
const selectedSchedule = ref<any | null>(null);

// Component Refs for header actions
const classTabRef = ref<InstanceType<typeof ClassTab> | null>(null);
const assignmentTabRef = ref<InstanceType<typeof TeacherAssignmentTab> | null>(null);
const roomTabRef = ref<InstanceType<typeof RoomTab> | null>(null);
const academicYearTabRef = ref<InstanceType<typeof AcademicYearTab> | null>(null);
const calendarTabRef = ref<InstanceType<typeof CalendarTab> | null>(null);

// Class Handlers
const openCreateClassForm = () => {
  selectedClass.value = null;
  viewMode.value = 'form-class';
};
const openEditClassForm = (item: any) => {
  selectedClass.value = item;
  viewMode.value = 'form-class';
};
const handleClassSaved = () => {
  viewMode.value = 'tabs';
  classTabRef.value?.fetchClasses();
};

// Subject Handlers
const openCreateSubjectForm = () => {
  selectedSubject.value = null;
  viewMode.value = 'form-subject';
};
const openEditSubjectForm = (item: any) => {
  selectedSubject.value = item;
  viewMode.value = 'form-subject';
};
const handleSubjectSaved = () => {
  viewMode.value = 'tabs';
};

// Schedule Handlers
const openCreateScheduleForm = () => {
  selectedSchedule.value = null;
  viewMode.value = 'form-schedule';
};
const openEditScheduleForm = (item: any) => {
  selectedSchedule.value = item;
  viewMode.value = 'form-schedule';
};
const handleScheduleSaved = () => {
  viewMode.value = 'tabs';
};

// Sync activeTab with URL Query Parameter
watch(
  () => route.query.tab,
  (newTab) => {
    const targetTab = typeof newTab === 'string' && newTab ? newTab : 'classes';
    activeTab.value = targetTab;
    viewMode.value = 'tabs';
  },
  { immediate: true }
);

onMounted(() => {
  if (route.query.tab && typeof route.query.tab === 'string') {
    activeTab.value = route.query.tab;
  }
});
</script>

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
              activeTab === 'academic_years' ? 'Tahun Ajaran & Kenaikan Kelas' :
              activeTab === 'calendar' ? 'Kalender Akademik & Hari Libur' : 'Kelas, Ruangan & Jadwal KBM'
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
              activeTab === 'academic_years' ? 'Kelola status tahun ajaran aktif, pergantian semester, penutupan periode, dan kenaikan kelas siswa secara massal.' :
              activeTab === 'calendar' ? 'Kelola jadwal kalender akademik, hari libur nasional/sekolah, dan perhitungan hari efektif belajar.' : 'Kelola rombel kelas aktif, alokasi wali kelas, jam pelajaran, dan jadwal kegiatan belajar mengajar.'
            }}
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button
            v-if="activeTab === 'classes'"
            @click="classTabRef?.exportClassesExcel()"
            class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-xl border border-emerald-200 transition cursor-pointer"
            title="Export Data Kelas & Rombel ke Excel"
          >
            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Export Excel</span>
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
            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer active:scale-95"
          >
            + Tambah Rombel Kelas
          </button>
          <button
            v-else-if="activeTab === 'subjects'"
            @click="openCreateSubjectForm"
            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer active:scale-95"
          >
            + Tambah Pelajaran / Tema
          </button>
          <button
            v-else-if="activeTab === 'assignments'"
            @click="assignmentTabRef?.openCreateAssignmentModal()"
            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer active:scale-95"
          >
            + Tambah Pembagian Mengajar
          </button>
          <button
            v-else-if="activeTab === 'schedules'"
            @click="openCreateScheduleForm"
            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer active:scale-95"
          >
            + Tambah Jadwal KBM
          </button>
          <button
            v-else-if="activeTab === 'academic_years'"
            @click="academicYearTabRef?.openCreateYearModal()"
            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer active:scale-95"
          >
            + Buat Tahun Ajaran Baru
          </button>
          <button
            v-else-if="activeTab === 'rooms'"
            @click="roomTabRef?.openCreateRoomModal()"
            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer active:scale-95"
          >
            + Tambah Ruang Kelas
          </button>
          <template v-else-if="activeTab === 'calendar'">
            <button
              @click="calendarTabRef?.openSeedModal()"
              class="inline-flex items-center gap-2 px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-xl border border-indigo-200 transition cursor-pointer active:scale-95"
              title="Generate Otomatis Hari Libur Nasional & Agenda Semester"
            >
              <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
              <span>Template Libur</span>
            </button>
            <button
              @click="calendarTabRef?.openCreateModal()"
              class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer active:scale-95"
            >
              + Tambah Agenda / Libur
            </button>
          </template>
        </div>
      </div>

      <!-- Tab Content (Lazy-loaded per tab) -->
      <ClassTab
        v-if="activeTab === 'classes'"
        ref="classTabRef"
        @create="openCreateClassForm"
        @edit="openEditClassForm"
      />

      <SubjectTab
        v-else-if="activeTab === 'subjects'"
        @create="openCreateSubjectForm"
        @edit="openEditSubjectForm"
      />

      <TeacherAssignmentTab
        v-else-if="activeTab === 'assignments'"
        ref="assignmentTabRef"
      />

      <ScheduleTab
        v-else-if="activeTab === 'schedules'"
        @create="openCreateScheduleForm"
        @edit="openEditScheduleForm"
      />

      <TimeSlotTab
        v-else-if="activeTab === 'slots'"
      />

      <RoomTab
        v-else-if="activeTab === 'rooms'"
        ref="roomTabRef"
      />

      <AcademicYearTab
        v-else-if="activeTab === 'academic_years'"
        ref="academicYearTabRef"
      />

      <CalendarTab
        v-else-if="activeTab === 'calendar'"
        ref="calendarTabRef"
      />
    </div>

    <!-- ============================================================== -->
    <!-- VIEW 2: FORM FULL PAGE - TAMBAH / EDIT KELAS                   -->
    <!-- ============================================================== -->
    <ClassForm
      v-else-if="viewMode === 'form-class'"
      :initial-data="selectedClass"
      :class-item="selectedClass"
      @cancel="viewMode = 'tabs'"
      @saved="handleClassSaved"
    />

    <!-- ============================================================== -->
    <!-- VIEW 3: FORM FULL PAGE - TAMBAH / EDIT PELAJARAN               -->
    <!-- ============================================================== -->
    <SubjectForm
      v-else-if="viewMode === 'form-subject'"
      :initial-data="selectedSubject"
      :subject-item="selectedSubject"
      @cancel="viewMode = 'tabs'"
      @saved="handleSubjectSaved"
    />

    <!-- ============================================================== -->
    <!-- VIEW 4: FORM FULL PAGE - TAMBAH / EDIT JADWAL                  -->
    <!-- ============================================================== -->
    <ScheduleForm
      v-else-if="viewMode === 'form-schedule'"
      :initial-data="selectedSchedule"
      :schedule-item="selectedSchedule"
      @cancel="viewMode = 'tabs'"
      @saved="handleScheduleSaved"
    />
  </div>
</template>

<style>
@media print {
  /* Sembunyikan elemen UI yang tidak perlu dicetak ke kertas */
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
