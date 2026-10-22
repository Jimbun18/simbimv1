<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '../services/api';

// Modular Child Components
import StudentList from '../components/students/StudentList.vue';
import StudentForm from '../components/students/StudentForm.vue';
import StudentDetail from '../components/students/StudentDetail.vue';

const route = useRoute();

// View Mode: 'list' (tabel) | 'form' (formulir tambah/edit) | 'detail' (biodata lengkap)
const currentView = ref<'list' | 'form' | 'detail'>('list');

const selectedStudent = ref<any | null>(null);
const studentListRef = ref<InstanceType<typeof StudentList> | null>(null);

// Navigation Handlers
const openCreateForm = () => {
  selectedStudent.value = null;
  currentView.value = 'form';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const openEditForm = (student: any) => {
  selectedStudent.value = student;
  currentView.value = 'form';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const openDetail = (student: any) => {
  selectedStudent.value = student;
  currentView.value = 'detail';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const handleFormSaved = () => {
  currentView.value = 'list';
  studentListRef.value?.fetchStudents();
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const handleBackToList = () => {
  currentView.value = 'list';
  selectedStudent.value = null;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const handleRouteQuery = async () => {
  if (route.query.search && studentListRef.value) {
    studentListRef.value.setSearch(String(route.query.search));
  }

  if (route.query.open_id) {
    const targetId = Number(route.query.open_id);
    try {
      const res = await api.get(`/students/${targetId}`);
      if (res.data.success && res.data.data) {
        openDetail(res.data.data);
      }
    } catch (err) {
      console.error('Gagal membuka detail siswa via open_id:', err);
    }
  }
};

onMounted(async () => {
  await handleRouteQuery();
});

watch(
  () => route.query.open_id,
  (newId) => {
    if (newId) {
      handleRouteQuery();
    }
  }
);
</script>

<template>
  <div class="space-y-5 sm:space-y-6">
    <!-- VIEW 1: DAFTAR TABEL SISWA (LIST VIEW) -->
    <StudentList
      v-if="currentView === 'list'"
      ref="studentListRef"
      @create="openCreateForm"
      @edit="openEditForm"
      @detail="openDetail"
    />

    <!-- VIEW 2: HALAMAN FORMULIR SISWA DAPODIK (TAMBAH & EDIT) -->
    <StudentForm
      v-else-if="currentView === 'form'"
      :initial-student="selectedStudent"
      @cancel="handleBackToList"
      @saved="handleFormSaved"
    />

    <!-- VIEW 3: HALAMAN DETAIL BIODATA SISWA LENGKAP DAPODIK -->
    <StudentDetail
      v-else-if="currentView === 'detail' && selectedStudent"
      :student="selectedStudent"
      @back="handleBackToList"
      @edit="openEditForm"
    />
  </div>
</template>
