<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '../services/api'
import { confirmDeleteWithKeyword, showSuccess, showError } from '../services/swal'
import type { Teacher } from '../components/teachers/types'
import TeacherList from '../components/teachers/TeacherList.vue'
import TeacherForm from '../components/teachers/TeacherForm.vue'
import TeacherDetail from '../components/teachers/TeacherDetail.vue'

// View Mode: 'list' | 'form' | 'detail'
const currentView = ref<'list' | 'form' | 'detail'>('list')

const teachers = ref<Teacher[]>([])
const search = ref('')
const filterJenisPtk = ref('')
const loading = ref(false)
const saving = ref(false)
const exporting = ref(false)

const selectedTeacher = ref<Teacher | null>(null)
const isEditing = ref(false)

const fetchTeachers = async () => {
  loading.value = true
  try {
    const params: any = {}
    if (search.value) params.search = search.value
    if (filterJenisPtk.value) params.jenis_ptk = filterJenisPtk.value

    const res = await api.get('/teachers', { params })
    if (res.data.success) {
      teachers.value = res.data.data
      if (selectedTeacher.value) {
        const updated = teachers.value.find((t) => t.id === selectedTeacher.value?.id)
        if (updated) selectedTeacher.value = updated
      }
    }
  } catch (err) {
    console.error('Gagal mengambil data guru', err)
  } finally {
    loading.value = false
  }
}

const exportToExcel = async () => {
  exporting.value = true
  try {
    const params: any = {}
    if (search.value) params.search = search.value
    if (filterJenisPtk.value) params.jenis_ptk = filterJenisPtk.value

    const res = await api.get('/teachers/export', {
      params,
      responseType: 'blob',
    })

    const blob = new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Master_Data_Guru_Tendik_${new Date().toISOString().slice(0, 10)}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    showSuccess('Berhasil!', 'File Excel data guru & tendik berhasil diunduh.')
  } catch (err: any) {
    showError('Gagal Export Excel!', err, 'Terjadi kendala saat mengunduh berkas Excel.')
  } finally {
    exporting.value = false
  }
}

const openCreateForm = () => {
  isEditing.value = false
  selectedTeacher.value = null
  currentView.value = 'form'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const openEditForm = (teacher: Teacher) => {
  isEditing.value = true
  selectedTeacher.value = teacher
  currentView.value = 'form'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const openDetail = (teacher: Teacher) => {
  selectedTeacher.value = teacher
  currentView.value = 'detail'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const closeDetail = () => {
  selectedTeacher.value = null
  currentView.value = 'list'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const closeForm = () => {
  if (selectedTeacher.value && isEditing.value) {
    currentView.value = 'detail'
  } else {
    selectedTeacher.value = null
    currentView.value = 'list'
  }
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleFormSubmit = async ({ formData, isEditing: editing, id }: { formData: FormData; isEditing: boolean; id?: number }) => {
  saving.value = true
  try {
    if (editing && id) {
      formData.delete('create_user_account')
      formData.append('_method', 'PUT')
      const res = await api.post(`/teachers/${id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      if (res.data.success) {
        showSuccess('Berhasil!', 'Data guru berhasil diperbarui.')
        closeForm()
        await fetchTeachers()
      }
    } else {
      const res = await api.post('/teachers', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      if (res.data.success) {
        showSuccess('Berhasil!', 'Data guru baru berhasil ditambahkan.')
        closeForm()
        await fetchTeachers()
      }
    }
  } catch (err: any) {
    showError('Gagal Menyimpan!', err, 'Terjadi kesalahan saat menyimpan data guru.')
  } finally {
    saving.value = false
  }
}

const handleDeleteTeacher = async (teacher: Teacher) => {
  const confirmed = await confirmDeleteWithKeyword(`guru "${teacher.nama_lengkap}" (NIP: ${teacher.nip || teacher.nik})`)
  if (!confirmed) return

  try {
    const res = await api.delete(`/teachers/${teacher.id}`)
    if (res.data.success) {
      showSuccess('Berhasil Dihapus!', res.data.message)
      if (currentView.value === 'detail') {
        closeDetail()
      }
      await fetchTeachers()
    }
  } catch (err: any) {
    showError('Gagal Menghapus!', err, 'Data guru tidak dapat dihapus.')
  }
}

onMounted(() => {
  fetchTeachers()
})
</script>

<template>
  <div class="space-y-5 sm:space-y-6">
    <!-- VIEW 1: DAFTAR TABEL GURU & TENDIK (LIST VIEW) -->
    <TeacherList
      v-if="currentView === 'list'"
      :teachers="teachers"
      :loading="loading"
      :exporting="exporting"
      v-model:search="search"
      v-model:filter-jenis-ptk="filterJenisPtk"
      @filter-change="fetchTeachers"
      @add="openCreateForm"
      @edit="openEditForm"
      @detail="openDetail"
      @delete="handleDeleteTeacher"
      @export="exportToExcel"
    />

    <!-- VIEW 2: FORM TAMBAH / EDIT GURU (FULL-PAGE FORM VIEW) -->
    <TeacherForm
      v-else-if="currentView === 'form'"
      :initial-data="selectedTeacher"
      :is-editing="isEditing"
      :saving="saving"
      @close="closeForm"
      @submit="handleFormSubmit"
    />

    <!-- VIEW 3: HALAMAN DETAIL GURU & TENDIK PENUH (DETAIL VIEW) -->
    <TeacherDetail
      v-else-if="currentView === 'detail' && selectedTeacher"
      :teacher="selectedTeacher"
      @close="closeDetail"
      @edit="openEditForm"
      @delete="handleDeleteTeacher"
    />
  </div>
</template>
