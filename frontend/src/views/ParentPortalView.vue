<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    <!-- 1. GREETING & HERO HEADER WITH CHILD SWITCHER -->
    <ParentHeroHeader
      :user-name="auth.userName"
      :children="children"
      :selected-child-id="selectedChildId"
      @select-child="selectChild"
    />

    <!-- 2. LOADING STATE -->
    <div v-if="loading" class="p-12 text-center text-slate-400 bg-white rounded-3xl border border-slate-100 shadow-xs">
      <div class="inline-block animate-spin w-8 h-8 border-3 border-emerald-600 border-t-transparent rounded-full mb-3"></div>
      <p class="text-sm font-medium text-slate-600">Memuat data dan capaian ananda tercinta...</p>
    </div>

    <!-- 3. EMPTY STATE (No Children connected) -->
    <div v-else-if="children.length === 0" class="bg-white rounded-3xl p-8 sm:p-12 text-center border border-slate-200/80 shadow-xs space-y-4">
      <div class="w-16 h-16 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center mx-auto text-2xl font-bold">
        ℹ️
      </div>
      <div>
        <h3 class="text-lg font-bold text-slate-800">Akun Belum Terhubung dengan Data Siswa</h3>
        <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto mt-1">
          Akun wali murid Anda belum ditautkan ke data siswa aktif. Silakan hubungi Staf Administrasi / Tata Usaha SDI Bina Insan Mandiri Purwokerto untuk verifikasi nomor identitas anak.
        </p>
      </div>
    </div>

    <!-- 4. ACTIVE CHILD DATA CONTENT -->
    <div v-else-if="summary" class="space-y-6">
      <!-- A. Child Profile Identity Card & 4 Quick Stat Highlights -->
      <ParentChildProfileCard
        :student="summary.student"
        :attendance-summary="attendanceSummary"
        :financial-summary="financialSummary"
        :latest-progress-list="latestProgressList"
      />

      <!-- B. SECTIONAL TABS -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <!-- Tab Headers -->
        <div class="flex border-b border-slate-100 px-4 sm:px-6 gap-2 sm:gap-4 overflow-x-auto scrollbar-thin">
          <button 
            @click="activeTab = 'progress'"
            :class="activeTab === 'progress' ? 'border-emerald-600 text-emerald-700 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
            class="py-4 border-b-2 text-xs sm:text-sm whitespace-nowrap transition-all flex items-center gap-2 cursor-pointer"
          >
            <span>Jurnal Tahfidz & Karakter</span>
          </button>

          <button 
            @click="activeTab = 'attendance'"
            :class="activeTab === 'attendance' ? 'border-emerald-600 text-emerald-700 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
            class="py-4 border-b-2 text-xs sm:text-sm whitespace-nowrap transition-all flex items-center gap-2 cursor-pointer"
          >
            <span>Rekap Kehadiran</span>
          </button>

          <button 
            @click="activeTab = 'activities'"
            :class="activeTab === 'activities' ? 'border-emerald-600 text-emerald-700 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
            class="py-4 border-b-2 text-xs sm:text-sm whitespace-nowrap transition-all flex items-center gap-2 cursor-pointer"
          >
            <span>Kegiatan Kelas</span>
          </button>

          <button 
            @click="activeTab = 'finance'"
            :class="activeTab === 'finance' ? 'border-emerald-600 text-emerald-700 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
            class="py-4 border-b-2 text-xs sm:text-sm whitespace-nowrap transition-all flex items-center gap-2 cursor-pointer"
          >
            <span>Tagihan & SPP</span>
          </button>

          <button 
            @click="activeTab = 'announcements'"
            :class="activeTab === 'announcements' ? 'border-emerald-600 text-emerald-700 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
            class="py-4 border-b-2 text-xs sm:text-sm whitespace-nowrap transition-all flex items-center gap-2 cursor-pointer"
          >
            <span>Pengumuman Sekolah</span>
          </button>

          <button 
            @click="onSelectRaporTab"
            :class="activeTab === 'rapor' ? 'border-emerald-600 text-emerald-700 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
            class="py-4 border-b-2 text-xs sm:text-sm whitespace-nowrap transition-all flex items-center gap-2 cursor-pointer"
          >
            <span>📑 E-Rapor Ananda</span>
            <span v-if="reportCardList.length > 0" class="px-1.5 py-0.5 rounded-full text-[10px] bg-emerald-100 text-emerald-800 font-black">
              {{ reportCardList.length }}
            </span>
          </button>
        </div>

        <!-- Tab 1: JURNAL TAHFIDZ & KARAKTER -->
        <ParentProgressTab
          v-if="activeTab === 'progress'"
          :latest-progress-list="latestProgressList"
        />

        <!-- Tab 2: REKAP KEHADIRAN -->
        <ParentAttendanceTab
          v-if="activeTab === 'attendance'"
          :attendance-summary="attendanceSummary"
          :recent-attendance-list="recentAttendanceList"
        />

        <!-- Tab 3: KEGIATAN KELAS -->
        <ParentActivitiesTab
          v-if="activeTab === 'activities'"
          :latest-activities-list="latestActivitiesList"
        />

        <!-- Tab 4: TAGIHAN & KEUANGAN SPP -->
        <ParentFinanceTab
          v-if="activeTab === 'finance'"
          :financial-summary="financialSummary"
          :invoice-list="invoiceList"
        />

        <!-- Tab 5: PENGUMUMAN SEKOLAH -->
        <ParentAnnouncementsTab
          v-if="activeTab === 'announcements'"
          :announcement-list="announcementList"
        />

        <!-- Tab 6: E-RAPOR RESMI ANANDA -->
        <ParentReportCardsTab
          v-if="activeTab === 'rapor'"
          :report-card-list="reportCardList"
          :loading="loadingReportCards"
          @open-detail="openReportDetailModal"
        />
      </div>
    </div>

    <!-- MODAL PREVIEW DETAIL RAPOR UNTUK WALI MURID -->
    <ParentReportDetailModal
      :show="showReportModal"
      :report-detail="selectedReportDetail"
      :student-name="summary?.student?.nama_lengkap"
      @close="showReportModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import api from '../services/api';

import ParentHeroHeader from '../components/parent-portal/ParentHeroHeader.vue';
import ParentChildProfileCard from '../components/parent-portal/ParentChildProfileCard.vue';
import ParentProgressTab from '../components/parent-portal/ParentProgressTab.vue';
import ParentAttendanceTab from '../components/parent-portal/ParentAttendanceTab.vue';
import ParentActivitiesTab from '../components/parent-portal/ParentActivitiesTab.vue';
import ParentFinanceTab from '../components/parent-portal/ParentFinanceTab.vue';
import ParentAnnouncementsTab from '../components/parent-portal/ParentAnnouncementsTab.vue';
import ParentReportCardsTab from '../components/parent-portal/ParentReportCardsTab.vue';
import ParentReportDetailModal from '../components/parent-portal/ParentReportDetailModal.vue';

import type {
  Child,
  ChildSummary,
  ParentPortalTab,
  ReportCardSummary,
  ReportCardDetail
} from '../components/parent-portal/types';

const auth = useAuthStore();
const children = ref<Child[]>([]);
const selectedChildId = ref<number | null>(null);
const summary = ref<ChildSummary | null>(null);
const loading = ref(false);
const activeTab = ref<ParentPortalTab>('progress');
const reportCardList = ref<ReportCardSummary[]>([]);
const loadingReportCards = ref(false);
const showReportModal = ref(false);
const selectedReportDetail = ref<ReportCardDetail | null>(null);

// Computed accessors
const attendanceSummary = computed(() => {
  return summary.value?.attendance_summary || summary.value?.presensi_bulan_ini || {};
});

const financialSummary = computed(() => {
  return summary.value?.keuangan || {};
});

const recentAttendanceList = computed(() => {
  return attendanceSummary.value.recent || [];
});

const latestProgressList = computed(() => {
  return summary.value?.latest_progress || summary.value?.progres_terakhir || [];
});

const latestActivitiesList = computed(() => {
  return summary.value?.latest_activities || [];
});

const announcementList = computed(() => {
  return summary.value?.announcements || [];
});

const invoiceList = computed(() => {
  return financialSummary.value.invoices || [];
});

const onSelectRaporTab = () => {
  activeTab.value = 'rapor';
  fetchChildReportCards();
};

const fetchChildReportCards = async () => {
  if (!selectedChildId.value) return;
  loadingReportCards.value = true;
  try {
    const res = await api.get(`/parent/children/${selectedChildId.value}/report-cards`);
    if (res.data.success && res.data.data) {
      reportCardList.value = res.data.data;
    }
  } catch (err) {
    console.error('Gagal mengambil e-rapor ananda:', err);
    reportCardList.value = [];
  } finally {
    loadingReportCards.value = false;
  }
};

const openReportDetailModal = async (rc: ReportCardSummary) => {
  try {
    const res = await api.get(`/report-cards/${rc.id}`);
    if (res.data.success && res.data.data) {
      selectedReportDetail.value = res.data.data;
      showReportModal.value = true;
      return;
    }
  } catch (err) {
    console.error('Gagal mengambil detail rapor:', err);
  }
  selectedReportDetail.value = rc as ReportCardDetail;
  showReportModal.value = true;
};

const fetchChildren = async () => {
  loading.value = true;
  try {
    const res = await api.get('/parent/children');
    if (res.data.success && res.data.data?.length > 0) {
      children.value = res.data.data;
      selectChild(children.value[0].id);
    }
  } catch (err) {
    console.error('Gagal mengambil daftar anak', err);
  } finally {
    loading.value = false;
  }
};

const selectChild = async (id: number) => {
  selectedChildId.value = id;
  loading.value = true;
  try {
    const res = await api.get(`/parent/children/${id}/summary`);
    if (res.data.success) {
      summary.value = res.data.data;
    }
  } catch (err) {
    console.error('Gagal mengambil ringkasan anak', err);
  } finally {
    loading.value = false;
    fetchChildReportCards();
  }
};

onMounted(() => {
  fetchChildren();
});
</script>
