<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '../services/api'

const loading = ref(false)
const message = ref<{ type: 'success' | 'error'; text: string } | null>(null)

// View Mode: 'list' (tabel aset) atau 'form' (halaman formulir penuh)
const viewMode = ref<'list' | 'form'>('list')

// Assets State
const assets = ref<any[]>([])
const rooms = ref<any[]>([])
const summary = ref({
  total_aset: 0,
  total_unit: 0,
  total_nilai: 0,
  kondisi: { baik: 0, rusak_ringan: 0, rusak_berat: 0, hilang: 0 }
})

const filterKategori = ref('')
const filterKondisi = ref('')
const search = ref('')

// Add/Edit Form State
const isEditing = ref(false)
const form = ref({
  id: 0,
  kode: '',
  nama: '',
  kategori: 'elektronik',
  lokasi: 'Ruang Guru',
  room_id: null as number | null,
  tahun_perolehan: new Date().getFullYear(),
  nilai: 0,
  kondisi: 'baik',
  jumlah: 1,
  satuan: 'Unit',
  keterangan: ''
})

// Maintenance Modal
const showMaintenanceModal = ref(false)
const selectedAsset = ref<any>(null)
const maintenanceForm = ref({
  deskripsi: '',
  biaya: 150000,
  pelaksana: 'Teknisi Lokal',
  kondisi_sesudah: 'baik',
  tanggal: new Date().toISOString().split('T')[0]
})

// Loan Modal
const showLoanModal = ref(false)
const loanForm = ref({
  peminjam_nama: '',
  keperluan: '',
  tgl_rencana_kembali: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
})

const showMessage = (text: string, type: 'success' | 'error' = 'success') => {
  message.value = { type, text }
  setTimeout(() => (message.value = null), 4000)
}

const formatRupiah = (val: number | string) => {
  const num = Number(val) || 0
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num)
}

const fetchRooms = async () => {
  try {
    const res = await api.get('/rooms')
    rooms.value = res.data.data || []
  } catch (err) {
    console.error('Failed to load rooms', err)
  }
}

const fetchAssets = async () => {
  loading.value = true
  try {
    const params: any = {}
    if (filterKategori.value) params.kategori = filterKategori.value
    if (filterKondisi.value) params.kondisi = filterKondisi.value
    if (search.value) params.search = search.value

    const res = await api.get('/assets', { params })
    assets.value = res.data.data?.data || res.data.data || []
    if (res.data.summary) {
      summary.value = res.data.summary
    }
  } catch (err) {
    console.error('Failed to load assets', err)
  } finally {
    loading.value = false
  }
}

const openAddForm = () => {
  isEditing.value = false
  form.value = {
    id: 0,
    kode: 'AST-' + Math.floor(1000 + Math.random() * 9000),
    nama: '',
    kategori: 'elektronik',
    lokasi: rooms.value[0]?.nama || 'Ruang Guru',
    room_id: rooms.value[0]?.id || null,
    tahun_perolehan: new Date().getFullYear(),
    nilai: 1000000,
    kondisi: 'baik',
    jumlah: 1,
    satuan: 'Unit',
    keterangan: ''
  }
  viewMode.value = 'form'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const openEditForm = (item: any) => {
  isEditing.value = true
  form.value = {
    id: item.id,
    kode: item.kode,
    nama: item.nama,
    kategori: item.kategori,
    lokasi: item.lokasi || item.room?.nama || 'Ruang Guru',
    room_id: item.room_id || null,
    tahun_perolehan: item.tahun_perolehan || new Date().getFullYear(),
    nilai: item.nilai || 0,
    kondisi: item.kondisi || 'baik',
    jumlah: item.jumlah || 1,
    satuan: item.satuan || 'Unit',
    keterangan: item.keterangan || ''
  }
  viewMode.value = 'form'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const closeForm = () => {
  viewMode.value = 'list'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const saveAsset = async () => {
  try {
    if (isEditing.value) {
      await api.put(`/assets/${form.value.id}`, form.value)
      showMessage('Data aset inventaris berhasil diperbarui!')
    } else {
      await api.post('/assets', form.value)
      showMessage('Aset inventaris baru berhasil ditambahkan!')
    }
    closeForm()
    fetchAssets()
  } catch (err: any) {
    showMessage(err.response?.data?.message || 'Gagal menyimpan aset', 'error')
  }
}

const openMaintenance = (item: any) => {
  selectedAsset.value = item
  maintenanceForm.value = {
    deskripsi: 'Servis berkala & perbaikan operasional',
    biaya: 150000,
    pelaksana: 'Teknisi Lokal',
    kondisi_sesudah: 'baik',
    tanggal: new Date().toISOString().split('T')[0]
  }
  showMaintenanceModal.value = true
}

const submitMaintenance = async () => {
  try {
    await api.post(`/assets/${selectedAsset.value.id}/maintenance`, maintenanceForm.value)
    showMessage('Catatan pemeliharaan aset berhasil disimpan!')
    showMaintenanceModal.value = false
    fetchAssets()
  } catch (err: any) {
    showMessage(err.response?.data?.message || 'Gagal mencatat pemeliharaan', 'error')
  }
}

const openLoan = (item: any) => {
  selectedAsset.value = item
  loanForm.value = {
    peminjam_nama: '',
    keperluan: 'Kegiatan Belajar Mengajar / Ekstrakurikuler',
    tgl_rencana_kembali: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
  }
  showLoanModal.value = true
}

const submitLoan = async () => {
  try {
    await api.post(`/assets/${selectedAsset.value.id}/borrow`, loanForm.value)
    showMessage('Peminjaman barang berhasil dicatat!')
    showLoanModal.value = false
    fetchAssets()
  } catch (err: any) {
    showMessage(err.response?.data?.message || 'Gagal mencatat peminjaman', 'error')
  }
}

onMounted(async () => {
  await fetchRooms()
  fetchAssets()
})
</script>

<template>
  <div class="space-y-6">
    <!-- VIEW 1: TABEL ASET (LIST VIEW) -->
    <div v-if="viewMode === 'list'" class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Sarana, Prasarana & Aset</h1>
          <p class="text-sm text-gray-500 mt-1">Inventarisasi sarpras sekolah, pencatatan jadwal perawatan, dan sirkulasi peminjaman barang.</p>
        </div>
        <button 
          @click="openAddForm"
          class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-md transition flex items-center gap-2 cursor-pointer active:scale-95"
        >
          <span>+ Tambah Aset Baru</span>
        </button>
      </div>

      <!-- Alert Message -->
      <div 
        v-if="message" 
        :class="message.type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200'"
        class="p-4 rounded-xl border text-sm flex items-center justify-between shadow-sm transition-all"
      >
        <span>{{ message.text }}</span>
        <button @click="message = null" class="font-bold opacity-60 hover:opacity-100">&times;</button>
      </div>

      <!-- Summary Stat Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
          <span class="text-xs text-gray-400 font-semibold uppercase">Total Jenis Aset</span>
          <h3 class="text-2xl font-bold text-gray-800 mt-1">{{ summary.total_aset }} Item</h3>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
          <span class="text-xs text-gray-400 font-semibold uppercase">Total Fisik Unit</span>
          <h3 class="text-2xl font-bold text-gray-800 mt-1">{{ summary.total_unit }} Unit</h3>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
          <span class="text-xs text-gray-400 font-semibold uppercase">Total Estimasi Nilai</span>
          <h3 class="text-xl font-bold text-emerald-600 mt-1">{{ formatRupiah(summary.total_nilai) }}</h3>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
          <span class="text-xs text-gray-400 font-semibold uppercase">Kondisi Prima</span>
          <h3 class="text-2xl font-bold text-blue-600 mt-1">{{ summary.kondisi.baik }} Unit</h3>
        </div>
      </div>

      <!-- Filters & Toolbar -->
      <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-wrap gap-4 items-center justify-between">
        <div class="flex flex-wrap items-center gap-3">
          <input 
            v-model="search" 
            @input="fetchAssets"
            type="text" 
            placeholder="Cari kode, nama aset..." 
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm w-64 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
          />
          <select 
            v-model="filterKategori" 
            @change="fetchAssets"
            class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
          >
            <option value="">Semua Kategori</option>
            <option value="elektronik">Elektronik</option>
            <option value="mebel">Mebel & Perabot</option>
            <option value="alat_peraga">Alat Peraga</option>
            <option value="buku">Buku</option>
            <option value="olahraga">Olahraga</option>
            <option value="gedung_ruang">Gedung / Ruang</option>
          </select>
          <select 
            v-model="filterKondisi" 
            @change="fetchAssets"
            class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
          >
            <option value="">Semua Kondisi</option>
            <option value="baik">Kondisi Baik</option>
            <option value="rusak_ringan">Rusak Ringan</option>
            <option value="rusak_berat">Rusak Berat</option>
          </select>
        </div>
      </div>

      <!-- Table Aset -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[750px]">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 font-semibold">
                <th class="p-3">Kode</th>
                <th class="p-3">Nama Aset</th>
                <th class="p-3">Kategori</th>
                <th class="p-3">Lokasi</th>
                <th class="p-3 text-center">Jumlah</th>
                <th class="p-3 text-right">Nilai / Unit</th>
                <th class="p-3 text-center">Kondisi</th>
                <th class="p-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="loading">
                <td colspan="8" class="text-center py-8 text-gray-400">Memuat data aset...</td>
              </tr>
              <tr v-else-if="assets.length === 0">
                <td colspan="8" class="text-center py-8 text-gray-400">Tidak ada aset yang terdata.</td>
              </tr>
              <tr v-for="item in assets" :key="item.id" class="hover:bg-gray-50/70 transition">
                <td class="p-3 font-mono text-xs font-bold text-gray-700 whitespace-nowrap">{{ item.kode }}</td>
                <td class="p-3 font-semibold text-gray-800 whitespace-nowrap">{{ item.nama }}</td>
                <td class="p-3 whitespace-nowrap">
                  <span class="bg-gray-100 text-gray-700 text-xs px-2.5 py-0.5 rounded font-medium capitalize">
                    {{ item.kategori.replace('_', ' ') }}
                  </span>
                </td>
                <td class="p-3 text-xs text-gray-600 whitespace-nowrap">{{ item.lokasi || item.room?.nama || '-' }}</td>
                <td class="p-3 text-center font-bold text-xs whitespace-nowrap">{{ item.jumlah }} {{ item.satuan }}</td>
                <td class="p-3 font-mono text-xs font-semibold text-right whitespace-nowrap">{{ formatRupiah(item.nilai) }}</td>
                <td class="p-3 text-center whitespace-nowrap">
                  <span 
                    class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider"
                    :class="{
                      'bg-emerald-100 text-emerald-800': item.kondisi === 'baik',
                      'bg-amber-100 text-amber-800': item.kondisi === 'rusak_ringan',
                      'bg-rose-100 text-rose-800': item.kondisi === 'rusak_berat' || item.kondisi === 'hilang'
                    }"
                  >
                    {{ item.kondisi.replace('_', ' ') }}
                  </span>
                </td>
                <td class="p-3 text-right whitespace-nowrap">
                  <div class="flex justify-end gap-1.5">
                    <button 
                      @click="openEditForm(item)"
                      class="bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs px-2.5 py-1 rounded-lg font-medium border border-amber-200 cursor-pointer"
                    >
                      Edit
                    </button>
                    <button 
                      @click="openMaintenance(item)"
                      class="bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs px-2.5 py-1 rounded-lg font-medium border border-blue-200 cursor-pointer"
                      title="Catat Perawatan"
                    >
                      Rawat
                    </button>
                    <button 
                      @click="openLoan(item)"
                      class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs px-2.5 py-1 rounded-lg font-medium border border-emerald-200 cursor-pointer"
                      title="Pinjamkan Barang"
                    >
                      Pinjam
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- VIEW 2: HALAMAN FORMULIR ASET PENUH (DEDICATED FULL-PAGE) -->
    <div v-else-if="viewMode === 'form'" class="space-y-6 animate-in fade-in duration-200">
      <!-- Top Navigation & Action Banner -->
      <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
          <button
            @click="closeForm"
            type="button"
            class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center justify-center shrink-0 shadow-xs group cursor-pointer"
            title="Kembali ke Daftar Aset"
          >
            <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
              <span>Inventaris</span>
              <span>/</span>
              <button type="button" @click="closeForm" class="hover:text-emerald-600 hover:underline cursor-pointer">Sarpras & Aset</button>
              <span>/</span>
              <span class="text-emerald-700 font-bold">{{ isEditing ? 'Edit Aset' : 'Tambah Aset Baru' }}</span>
            </div>
            <h1 class="text-lg sm:text-2xl font-black text-gray-900 leading-tight">
              {{ isEditing ? `Edit Aset: ${form.nama || '...'}` : 'Formulir Inventaris Aset Baru' }}
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              {{ isEditing ? 'Perbarui spesifikasi barang, kondisi terkini, lokasi ruangan, dan nilai aset.' : 'Lengkapi formulir pendataan sarana dan prasarana sekolah.' }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2.5 self-end sm:self-center">
          <button
            @click="closeForm"
            type="button"
            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition cursor-pointer"
          >
            Batal
          </button>
          <button
            @click="saveAsset"
            type="button"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/25 transition active:scale-95 cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ isEditing ? 'Simpan Perubahan' : 'Simpan Aset Baru' }}</span>
          </button>
        </div>
      </div>

      <!-- Main Form Grid: 12 Cols -->
      <form @submit.prevent="saveAsset" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- LEFT COLUMN: Live Preview Card (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
          <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-950 p-5 rounded-2xl text-white shadow-lg space-y-4">
            <div class="flex items-center justify-between text-emerald-400 text-xs font-bold">
              <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                KARTU ASET (PREVIEW)
              </span>
              <span class="font-mono text-[10px] text-slate-400">{{ form.kode || 'KODE' }}</span>
            </div>

            <div class="pt-2">
              <span class="px-2.5 py-1 bg-white/15 text-slate-200 rounded-lg text-xs font-bold capitalize">
                {{ form.kategori.replace('_', ' ') }}
              </span>
              <h3 class="text-xl font-black mt-2 text-white">
                {{ form.nama || 'Nama Barang / Aset' }}
              </h3>
              <p class="text-xs text-slate-300 mt-0.5">
                Lokasi: <strong class="text-emerald-300">{{ form.lokasi || 'Ruang Guru' }}</strong>
              </p>
            </div>

            <div class="pt-3 border-t border-white/10 space-y-1.5 text-xs text-slate-300">
              <div class="flex justify-between">
                <span>Jumlah Fisik:</span>
                <span class="font-bold text-white">{{ form.jumlah }} {{ form.satuan }}</span>
              </div>
              <div class="flex justify-between">
                <span>Nilai / Unit:</span>
                <span class="font-mono text-emerald-300 font-bold">{{ formatRupiah(form.nilai) }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span>Kondisi Fisik:</span>
                <span 
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                  :class="form.kondisi === 'baik' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300'"
                >
                  {{ form.kondisi.replace('_', ' ') }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: Form Inputs (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
          <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-gray-900 border-b pb-3">Spesifikasi & Detail Barang</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Kode Barang *</label>
                <input
                  v-model="form.kode"
                  type="text"
                  required
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono font-bold focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Kategori Barang *</label>
                <select
                  v-model="form.kategori"
                  required
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white font-medium focus:ring-2 focus:ring-emerald-500 outline-none"
                >
                  <option value="elektronik">Elektronik</option>
                  <option value="mebel">Mebel & Perabot</option>
                  <option value="alat_peraga">Alat Peraga</option>
                  <option value="buku">Buku & Pustaka</option>
                  <option value="olahraga">Olahraga</option>
                  <option value="gedung_ruang">Gedung / Ruang</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Nama Barang / Aset *</label>
              <input
                v-model="form.nama"
                type="text"
                required
                placeholder="Contoh: Smart TV 55 Inch Lab Komputer"
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold text-gray-800 focus:ring-2 focus:ring-emerald-500 outline-none"
              />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Lokasi Ruangan</label>
                <select
                  v-model="form.room_id"
                  @change="() => {
                    const r = rooms.find((rm: any) => rm.id === form.room_id);
                    if (r) form.lokasi = r.nama;
                  }"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white focus:ring-2 focus:ring-emerald-500 outline-none"
                >
                  <option :value="null">-- Ruangan Umum / Lainnya --</option>
                  <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.nama }} ({{ r.kode }})</option>
                </select>
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Kondisi Fisik *</label>
                <select
                  v-model="form.kondisi"
                  required
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs bg-white font-bold focus:ring-2 focus:ring-emerald-500 outline-none"
                >
                  <option value="baik">Kondisi Baik (Siap Pakai)</option>
                  <option value="rusak_ringan">Rusak Ringan (Perlu Servis)</option>
                  <option value="rusak_berat">Rusak Berat (Tidak Berfungsi)</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Jumlah *</label>
                <input
                  v-model.number="form.jumlah"
                  type="number"
                  min="1"
                  required
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-bold focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Satuan *</label>
                <input
                  v-model="form.satuan"
                  type="text"
                  required
                  placeholder="Unit / Buah / Set"
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>

              <div>
                <label class="block font-semibold text-gray-700 text-xs mb-1">Nilai Perolehan / Unit (Rp)</label>
                <input
                  v-model.number="form.nilai"
                  type="number"
                  min="0"
                  required
                  class="w-full border border-gray-200 rounded-xl p-2.5 text-xs font-mono font-bold focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>
            </div>

            <div>
              <label class="block font-semibold text-gray-700 text-xs mb-1">Keterangan Tambahan</label>
              <textarea
                v-model="form.keterangan"
                rows="2"
                placeholder="Catatan nomor seri, sumber dana bos, dsb..."
                class="w-full border border-gray-200 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-emerald-500 outline-none"
              ></textarea>
            </div>
          </div>

          <!-- Bottom Action Bar -->
          <div class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between gap-4">
            <button
              @click="closeForm"
              type="button"
              class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition flex items-center gap-2 cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              <span>Kembali ke Daftar Aset</span>
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
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/25 transition active:scale-95 cursor-pointer"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ isEditing ? 'Simpan Perubahan' : 'Simpan Aset Baru' }}</span>
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- MODAL RAWAT ASET (Sub-Dialog Sederhana Pemeliharaan) -->
    <div v-if="showMaintenanceModal && selectedAsset" class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-3 sm:p-4">
      <div class="bg-white rounded-3xl max-w-md w-full max-h-[90vh] overflow-y-auto p-5 sm:p-6 shadow-2xl space-y-4">
        <div class="flex justify-between items-center border-b pb-3">
          <h3 class="font-bold text-gray-800 text-base sm:text-lg">Catat Perawatan Aset</h3>
          <button @click="showMaintenanceModal = false" class="text-gray-400 hover:text-gray-600 text-xl cursor-pointer">&times;</button>
        </div>
        <div class="bg-blue-50 p-3 rounded-xl border border-blue-100 text-xs">
          Aset: <strong class="text-blue-900">{{ selectedAsset.nama }}</strong> ({{ selectedAsset.kode }})
        </div>
        <form @submit.prevent="submitMaintenance" class="space-y-3 text-sm">
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Tanggal Perawatan</label>
            <input v-model="maintenanceForm.tanggal" type="date" required class="w-full border rounded-xl p-2.5 text-xs" />
          </div>
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Deskripsi Kerusakan / Servis</label>
            <textarea v-model="maintenanceForm.deskripsi" required rows="2" class="w-full border rounded-xl p-2.5 text-xs"></textarea>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-gray-700 mb-1">Biaya (Rp)</label>
              <input v-model.number="maintenanceForm.biaya" type="number" min="0" required class="w-full border rounded-xl p-2.5 text-xs font-mono" />
            </div>
            <div>
              <label class="block font-semibold text-gray-700 mb-1">Kondisi Hasil</label>
              <select v-model="maintenanceForm.kondisi_sesudah" class="w-full border rounded-xl p-2.5 text-xs bg-white">
                <option value="baik">Kondisi Baik</option>
                <option value="rusak_ringan">Rusak Ringan</option>
                <option value="rusak_berat">Rusak Berat</option>
              </select>
            </div>
          </div>
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Teknisi / Bengkel Servis</label>
            <input v-model="maintenanceForm.pelaksana" type="text" placeholder="Nama teknisi / toko servis" class="w-full border rounded-xl p-2.5 text-xs" />
          </div>
          <div class="flex justify-end gap-2 pt-2 border-t">
            <button type="button" @click="showMaintenanceModal = false" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-xl text-xs cursor-pointer">Batal</button>
            <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-md text-xs cursor-pointer">Simpan Perawatan</button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL PINJAM ASET (Sub-Dialog Sederhana Peminjaman) -->
    <div v-if="showLoanModal && selectedAsset" class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-3 sm:p-4">
      <div class="bg-white rounded-3xl max-w-md w-full max-h-[90vh] overflow-y-auto p-5 sm:p-6 shadow-2xl space-y-4">
        <div class="flex justify-between items-center border-b pb-3">
          <h3 class="font-bold text-gray-800 text-base sm:text-lg">Peminjaman Barang</h3>
          <button @click="showLoanModal = false" class="text-gray-400 hover:text-gray-600 text-xl cursor-pointer">&times;</button>
        </div>
        <div class="bg-emerald-50 p-3 rounded-xl border border-emerald-100 text-xs">
          Aset: <strong class="text-emerald-900">{{ selectedAsset.nama }}</strong> ({{ selectedAsset.kode }})
        </div>
        <form @submit.prevent="submitLoan" class="space-y-3 text-sm">
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Nama Peminjam / Guru / Staf</label>
            <input v-model="loanForm.peminjam_nama" type="text" required placeholder="Contoh: Pak Budi Santoso" class="w-full border rounded-xl p-2.5 text-xs" />
          </div>
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Keperluan Peminjaman</label>
            <textarea v-model="loanForm.keperluan" required rows="2" class="w-full border rounded-xl p-2.5 text-xs"></textarea>
          </div>
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Rencana Tanggal Pengembalian</label>
            <input v-model="loanForm.tgl_rencana_kembali" type="date" required class="w-full border rounded-xl p-2.5 text-xs" />
          </div>
          <div class="flex justify-end gap-2 pt-2 border-t">
            <button type="button" @click="showLoanModal = false" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-xl text-xs cursor-pointer">Batal</button>
            <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md text-xs cursor-pointer">Catat Peminjaman</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
