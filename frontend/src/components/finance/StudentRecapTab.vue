<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '../../services/api'
import { formatRupiah, formatPhoneForWA } from './types'
import { formatDateIndo } from '../../utils/date'
import type { Invoice, Payment } from './types'

const props = defineProps<{
  classes: any[]
  academicYears: any[]
}>()

const emit = defineEmits<{
  (e: 'pay', invoice: Invoice): void
  (e: 'view-receipt', paymentId: number): void
}>()

// Page View Mode: 'list' (Daftar Siswa) | 'detail' (Halaman Penuh Rincian 12 Bulan & Jenis Biaya)
const subViewMode = ref<'list' | 'detail'>('list')

// State for List Mode
const loading = ref(false)
const students = ref<any[]>([])
const summary = ref({
  total_siswa: 0,
  total_tagihan: 0,
  total_dibayar: 0,
  total_piutang: 0
})

const filter = ref({
  search: '',
  class_id: '',
  status: ''
})

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 25,
  total: 0
})

// State for Detail Mode
const selectedStudentId = ref<number | null>(null)
const detailLoading = ref(false)
const detailAcademicYearId = ref<number | string>('')
const studentDetail = ref<{
  student: any
  academic_year: any
  spp_fee_type: any
  summary: {
    total_tagihan: number
    total_dibayar: number
    total_sisa: number
    count_total: number
    count_lunas: number
    count_belum_lunas: number
  }
  matriks_12_bulan: Array<{
    urutan: number
    bulan_angka: number
    bulan_nama: string
    tahun: number
    periode_key: string
    label: string
    has_invoice: boolean
    invoice: Invoice | null
    nominal_standar: number
    status: 'lunas' | 'sebagian' | 'belum_bayar' | 'belum_terbit'
    jumlah: number
    jumlah_dibayar: number
    sisa: number
  }>
  rekap_jenis_biaya: Array<{
    fee_type_id: number
    kode: string
    nama: string
    periode: string
    nominal_standar: number
    keterangan: string
    count_invoices: number
    total_tagihan: number
    total_dibayar: number
    total_sisa: number
    status: string
    invoices: Invoice[]
  }>
  tagihan_belum_lunas: Invoice[]
  tagihan_lunas: Invoice[]
  riwayat_pembayaran: Payment[]
} | null>(null)

// Detail Active Tab (Inside Detail Full-Page View)
const activeDetailSection = ref<'12_months' | 'fee_types' | 'unpaid' | 'history'>('12_months')

// Fetch recap list
const fetchRecap = async (page = 1) => {
  loading.value = true
  pagination.value.current_page = page
  try {
    const params: any = {
      page,
      per_page: pagination.value.per_page,
    }
    if (filter.value.search.trim()) params.search = filter.value.search.trim()
    if (filter.value.class_id) params.class_id = filter.value.class_id
    if (filter.value.status) params.status = filter.value.status

    const res = await api.get('/finance/student-recap', { params })
    students.value = res.data.data || []
    if (res.data.summary) {
      summary.value = res.data.summary
    }
    if (res.data.meta) {
      pagination.value = {
        current_page: res.data.meta.current_page,
        last_page: res.data.meta.last_page,
        per_page: res.data.meta.per_page,
        total: res.data.meta.total
      }
    }
  } catch (err) {
    console.error('Gagal memuat rekap keuangan siswa:', err)
  } finally {
    loading.value = false
  }
}

// Reset filters
const resetFilter = () => {
  filter.value.search = ''
  filter.value.class_id = ''
  filter.value.status = ''
  fetchRecap(1)
}

// Open Full-Page Detail (NO POPUP)
const openStudentDetail = async (student: any, yearId?: number | string) => {
  selectedStudentId.value = student.id
  subViewMode.value = 'detail'
  detailLoading.value = true
  studentDetail.value = null
  activeDetailSection.value = '12_months'
  window.scrollTo({ top: 0, behavior: 'smooth' })

  if (yearId !== undefined) {
    detailAcademicYearId.value = yearId
  } else if (!detailAcademicYearId.value && props.academicYears?.length) {
    const activeAy = props.academicYears.find((ay: any) => ay.status === 'aktif') || props.academicYears[0]
    detailAcademicYearId.value = activeAy ? activeAy.id : ''
  }

  try {
    const params: any = {}
    if (detailAcademicYearId.value) {
      params.academic_year_id = detailAcademicYearId.value
    }
    const res = await api.get(`/finance/student-recap/${student.id}`, { params })
    studentDetail.value = res.data.data
    if (res.data.data?.academic_year?.id && !detailAcademicYearId.value) {
      detailAcademicYearId.value = res.data.data.academic_year.id
    }
  } catch (err) {
    console.error('Gagal mengambil rincian detail keuangan siswa:', err)
  } finally {
    detailLoading.value = false
  }
}

// Reload detail when academic year changes
const reloadStudentDetail = async () => {
  if (!selectedStudentId.value) return
  detailLoading.value = true
  try {
    const params: any = {}
    if (detailAcademicYearId.value) {
      params.academic_year_id = detailAcademicYearId.value
    }
    const res = await api.get(`/finance/student-recap/${selectedStudentId.value}`, { params })
    studentDetail.value = res.data.data
  } catch (err) {
    console.error('Gagal memuat ulang rincian detail keuangan siswa:', err)
  } finally {
    detailLoading.value = false
  }
}

// Return to List View
const backToList = () => {
  subViewMode.value = 'list'
  selectedStudentId.value = null
  studentDetail.value = null
  fetchRecap(pagination.value.current_page)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Quick action: Pay cashier directly
const handlePay = (inv: Invoice) => {
  emit('pay', inv)
}

// View official receipt
const handleViewReceipt = (p: Payment) => {
  emit('view-receipt', p.id)
}

// Quick create single invoice if not yet generated
const quickCreateInvoice = async (monthItem: any) => {
  if (!studentDetail.value || !studentDetail.value.spp_fee_type) return
  const s = studentDetail.value.student
  const ft = studentDetail.value.spp_fee_type
  const ay = studentDetail.value.academic_year

  const confirmGen = confirm(`Terbitkan tagihan SPP ${monthItem.label} untuk ${s.nama_lengkap} sebesar ${formatRupiah(monthItem.nominal_standar)}?`)
  if (!confirmGen) return

  try {
    const payload = {
      student_id: s.id,
      fee_type_id: ft.id,
      academic_year_id: ay ? ay.id : 1,
      periode: monthItem.periode_key,
      jumlah: monthItem.nominal_standar,
      jatuh_tempo: `${monthItem.periode_key}-10`,
      keterangan: `SPP Bulan ${monthItem.label}`
    }
    await api.post('/invoices', payload)
    // Reload detail
    const res = await api.get(`/finance/student-recap/${s.id}`)
    studentDetail.value = res.data.data
  } catch (err: any) {
    alert(err.response?.data?.message || 'Gagal menerbitkan tagihan.')
  }
}

// Quick create invoice for any FeeType
const quickCreateFeeTypeInvoice = async (feeType: any) => {
  if (!studentDetail.value) return
  const s = studentDetail.value.student
  const ay = studentDetail.value.academic_year

  const confirmGen = confirm(`Terbitkan tagihan "${feeType.nama}" untuk ${s.nama_lengkap} sebesar ${formatRupiah(feeType.nominal_standar)}?`)
  if (!confirmGen) return

  try {
    const currentPeriode = new Date().toISOString().substring(0, 7)
    const payload = {
      student_id: s.id,
      fee_type_id: feeType.fee_type_id,
      academic_year_id: ay ? ay.id : 1,
      periode: currentPeriode,
      jumlah: feeType.nominal_standar,
      jatuh_tempo: new Date(new Date().getFullYear(), new Date().getMonth() + 1, 10).toISOString().split('T')[0],
      keterangan: `Tagihan ${feeType.nama} siswa ${s.nama_lengkap}`
    }
    await api.post('/invoices', payload)
    // Reload detail
    const res = await api.get(`/finance/student-recap/${s.id}`)
    studentDetail.value = res.data.data
  } catch (err: any) {
    alert(err.response?.data?.message || 'Gagal menerbitkan tagihan.')
  }
}

// Send WhatsApp recap message
const sendWhatsAppStudentSummary = () => {
  if (!studentDetail.value) return
  const s = studentDetail.value.student
  const sum = studentDetail.value.summary
  const unpaid = studentDetail.value.tagihan_belum_lunas

  const rawPhone = s.wali?.no_hp || ''
  const phone = formatPhoneForWA(rawPhone)
  if (!phone) {
    alert('Nomor WhatsApp wali murid tidak tersedia.')
    return
  }

  const guardianName = s.wali?.nama || 'Wali Murid'
  let msg = `Assalamu'alaikum Warahmatullahi Wabarakatuh,\n`
  msg += `Yth. Bapak/Ibu *${guardianName}* (Wali dari ananda *${s.nama_lengkap}*, Kelas: *${s.kelas}*).\n\n`
  msg += `Berikut kami sampaikan resume rekap administrasi keuangan sekolah ananda di *SDI Bina Insan Mandiri Purwokerto*:\n\n`
  msg += `• *Total Tagihan Tercatat:* ${formatRupiah(sum.total_tagihan)}\n`
  msg += `• *Total Sudah Terbayar:* ${formatRupiah(sum.total_dibayar)}\n`
  msg += `• *Sisa Tunggakan:* *${formatRupiah(sum.total_sisa)}*\n\n`

  if (unpaid.length > 0) {
    msg += `*Rincian Tagihan yang Belum Dilunasi:*\n`
    unpaid.forEach((item, idx) => {
      const feeName = (item as any).fee_type?.nama || (item as any).feeType?.nama || 'Biaya Sekolah'
      msg += `${idx + 1}. *${feeName}* (${item.periode || '-'})\n`
      msg += `   Sisa: *${formatRupiah(item.sisa)}* (Jatuh tempo: ${item.jatuh_tempo || '-'})\n`
    })
    msg += `\nPembayaran dapat dilakukan langsung di Kantor Tata Usaha SDI BIM atau melalui transfer ke rekening resmi sekolah.\n`
  } else {
    msg += `Alhamdulillah, seluruh kewajiban administrasi tagihan sekolah ananda telah *LUNAS*. Jazakumullahu khairan katsiran atas kerja sama dan dukungannya.\n`
  }

  msg += `\n_— Bagian Keuangan SDI Bina Insan Mandiri Purwokerto_`

  const waUrl = `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`
  window.open(waUrl, '_blank')
}

// Print full detail page
const printDetailPage = () => {
  window.print()
}

onMounted(() => {
  fetchRecap(1)
})
</script>

<template>
  <div class="space-y-6">
    <!-- ========================================== -->
    <!-- VIEW MODE 1: DAFTAR REKAP SISWA (LIST)     -->
    <!-- ========================================== -->
    <div v-if="subViewMode === 'list'" class="space-y-5">
      <!-- Top KPI Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
          <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Siswa</span>
          <h4 class="text-xl sm:text-2xl font-black text-slate-800 mt-0.5">{{ summary.total_siswa }}</h4>
          <span class="text-[11px] text-slate-500">Terdaftar di Rekap</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
          <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Tagihan Siswa</span>
          <h4 class="text-xl sm:text-2xl font-black text-slate-800 mt-0.5">{{ formatRupiah(summary.total_tagihan) }}</h4>
          <span class="text-[11px] text-slate-500">Kewajiban Seluruh Siswa</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
          <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Total Terbayar</span>
          <h4 class="text-xl sm:text-2xl font-black text-emerald-600 mt-0.5">{{ formatRupiah(summary.total_dibayar) }}</h4>
          <span class="text-[11px] text-emerald-700 font-semibold">Telah Diterima Kasir</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
          <span class="text-[11px] font-bold text-rose-600 uppercase tracking-wider">Total Sisa Piutang</span>
          <h4 class="text-xl sm:text-2xl font-black text-rose-600 mt-0.5">{{ formatRupiah(summary.total_piutang) }}</h4>
          <span class="text-[11px] text-rose-600 font-semibold">Tunggakan Belum Lunas</span>
        </div>
      </div>

      <!-- Filter & Search Bar -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-center flex-1">
          <!-- Search Input -->
          <div class="relative flex-1 max-w-sm">
            <input 
              v-model="filter.search"
              @keyup.enter="fetchRecap(1)"
              type="text"
              placeholder="Cari nama siswa, NIS, atau NISN..."
              class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            />
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>

          <!-- Filter Kelas -->
          <select
            v-model="filter.class_id"
            @change="fetchRecap(1)"
            class="border border-slate-200 rounded-xl px-3 py-2 text-xs sm:text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none text-slate-700"
          >
            <option value="">Semua Kelas</option>
            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nama }}</option>
          </select>

          <!-- Filter Status Pembayaran -->
          <select
            v-model="filter.status"
            @change="fetchRecap(1)"
            class="border border-slate-200 rounded-xl px-3 py-2 text-xs sm:text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none text-slate-700"
          >
            <option value="">Semua Status</option>
            <option value="lunas">Bebas Tunggakan (Lunas)</option>
            <option value="menunggak">Ada Tunggakan / Belum Lunas</option>
            <option value="belum_ada_tagihan">Belum Ada Tagihan</option>
          </select>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <button
            v-if="filter.search || filter.class_id || filter.status"
            @click="resetFilter"
            class="px-3 py-2 text-xs font-semibold text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-xl transition cursor-pointer"
          >
            Reset Filter
          </button>
          <button
            @click="fetchRecap(pagination.current_page)"
            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl transition cursor-pointer"
          >
            <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Muat Ulang
          </button>
        </div>
      </div>

      <!-- Student Table -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[850px]">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                <th class="p-3.5 w-12 text-center">No</th>
                <th class="p-3.5">Nama Siswa</th>
                <th class="p-3.5 w-24">Kelas</th>
                <th class="p-3.5">Wali Murid / Kontak</th>
                <th class="p-3.5 text-right">Total Tagihan</th>
                <th class="p-3.5 text-right">Sudah Dibayar</th>
                <th class="p-3.5 text-right">Sisa / Tunggakan</th>
                <th class="p-3.5 text-center">Status</th>
                <th class="p-3.5 text-center w-28">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="loading && students.length === 0">
                <td colspan="9" class="text-center py-12 text-slate-400">
                  <div class="flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Memuat rekap keuangan siswa...</span>
                  </div>
                </td>
              </tr>
              <tr v-else-if="students.length === 0">
                <td colspan="9" class="text-center py-12 text-slate-400">
                  Tidak ada data siswa yang cocok dengan filter.
                </td>
              </tr>
              <tr 
                v-for="(st, idx) in students" 
                :key="st.id"
                class="hover:bg-slate-50/80 transition"
              >
                <!-- No -->
                <td class="p-3.5 text-center text-slate-400 font-mono text-xs">
                  {{ ((pagination.current_page - 1) * pagination.per_page) + idx + 1 }}
                </td>

                <!-- Nama Siswa -->
                <td class="p-3.5">
                  <div class="font-bold text-slate-900 text-sm">{{ st.nama_lengkap }}</div>
                  <div class="text-[11px] text-slate-400 font-mono">
                    NIS: {{ st.nis || '-' }} {{ st.nisn ? `• NISN: ${st.nisn}` : '' }}
                  </div>
                </td>

                <!-- Kelas -->
                <td class="p-3.5 whitespace-nowrap">
                  <span class="px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-800 font-bold text-xs border border-emerald-200">
                    {{ st.kelas }}
                  </span>
                </td>

                <!-- Wali & Kontak -->
                <td class="p-3.5">
                  <div v-if="st.wali" class="text-xs">
                    <div class="font-medium text-slate-800">{{ st.wali.nama }} ({{ st.wali.hubungan }})</div>
                    <div class="text-slate-500 font-mono text-[11px] mt-0.5 flex items-center gap-1">
                      <span>{{ st.wali.no_hp || '-' }}</span>
                      <a 
                        v-if="st.wali.no_hp"
                        :href="`https://wa.me/${formatPhoneForWA(st.wali.no_hp)}`"
                        target="_blank"
                        class="text-emerald-600 hover:text-emerald-700 font-bold"
                        title="Kirim pesan WhatsApp"
                      >
                        (WA)
                      </a>
                    </div>
                  </div>
                  <span v-else class="text-slate-400 text-xs italic">Belum terhubung</span>
                </td>

                <!-- Total Tagihan -->
                <td class="p-3.5 text-right font-medium text-slate-700 whitespace-nowrap">
                  {{ formatRupiah(st.total_tagihan) }}
                </td>

                <!-- Sudah Dibayar -->
                <td class="p-3.5 text-right font-bold text-emerald-600 whitespace-nowrap">
                  {{ formatRupiah(st.total_dibayar) }}
                </td>

                <!-- Sisa / Tunggakan -->
                <td class="p-3.5 text-right font-black whitespace-nowrap" :class="st.total_sisa > 0 ? 'text-rose-600' : 'text-slate-400'">
                  {{ formatRupiah(st.total_sisa) }}
                </td>

                <!-- Status -->
                <td class="p-3.5 text-center whitespace-nowrap">
                  <span 
                    v-if="st.status_pembayaran === 'lunas'" 
                    class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    Lunas
                  </span>
                  <span 
                    v-else-if="st.status_pembayaran === 'menunggak'" 
                    class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200 inline-flex items-center gap-1"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    Ada Tunggakan
                  </span>
                  <span 
                    v-else-if="st.status_pembayaran === 'belum_bayar'" 
                    class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200 inline-flex items-center gap-1"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                    Belum Bayar
                  </span>
                  <span 
                    v-else 
                    class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500"
                  >
                    Belum Ada Tagihan
                  </span>
                </td>

                <!-- Aksi -->
                <td class="p-3.5 text-center whitespace-nowrap">
                  <button
                    @click="openStudentDetail(st)"
                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition shadow-xs hover:shadow flex items-center gap-1 mx-auto cursor-pointer"
                    title="Buka Halaman Rincian 12 Bulan & Jenis Biaya"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span>Detail</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.total > 0" class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600 bg-slate-50/50">
          <div>
            Menampilkan 
            <span class="font-bold text-slate-800">{{ ((pagination.current_page - 1) * pagination.per_page) + 1 }}</span>
            -
            <span class="font-bold text-slate-800">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</span>
            dari 
            <span class="font-bold text-slate-800">{{ pagination.total }}</span>
            siswa
          </div>

          <div class="flex items-center gap-1.5">
            <button 
              :disabled="pagination.current_page <= 1"
              @click="fetchRecap(pagination.current_page - 1)"
              class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white font-medium hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
            >
              Sebelumnya
            </button>
            <span class="px-2 font-bold text-slate-700">Halaman {{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <button 
              :disabled="pagination.current_page >= pagination.last_page"
              @click="fetchRecap(pagination.current_page + 1)"
              class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white font-medium hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW MODE 2: HALAMAN PENUH RINCIAN DETAIL (12 BULAN & JENIS TAGIHAN)     -->
    <!-- (TIDAK MEMAKAI POPUP, PENUH DAN KOMPREHENSIF)                            -->
    <!-- ========================================================================= -->
    <div v-else-if="subViewMode === 'detail'" class="space-y-6">
      <!-- Back Navigation Header -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
        <div class="flex items-center gap-3">
          <button
            @click="backToList"
            class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs flex items-center gap-1.5 transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Kembali ke Daftar Rekap</span>
          </button>
          <div>
            <h2 class="text-lg font-black text-slate-900">
              Rincian Keuangan & Administrasi Siswa
            </h2>
            <p class="text-xs text-slate-500">
              Tahun Ajaran {{ studentDetail?.academic_year?.nama || '2026/2027' }} • Rekap 12 Bulan & Terhubung ke Master Tarif
            </p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 self-stretch md:self-auto justify-between md:justify-end">
          <!-- Pilihan Tahun Ajaran (Switch Periode 12 Bulan) -->
          <div class="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
            <span class="text-xs font-bold text-slate-600">Tahun Ajaran:</span>
            <select
              v-model="detailAcademicYearId"
              @change="reloadStudentDetail"
              class="border-0 bg-transparent text-xs font-black text-emerald-700 focus:ring-0 focus:outline-none cursor-pointer"
            >
              <option v-for="ay in academicYears" :key="ay.id" :value="ay.id">
                TA {{ ay.nama }} {{ ay.status === 'aktif' ? '(Aktif)' : '' }}
              </option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="sendWhatsAppStudentSummary"
              class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer"
            >
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.173.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.072.043.419-.101.824z" />
              </svg>
              <span>Kirim Rincian ke WA</span>
            </button>
            <button
              @click="printDetailPage"
              class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center gap-1.5 cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
              <span>Cetak Rekap</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="detailLoading" class="py-20 text-center text-slate-400 bg-white rounded-2xl border border-slate-200/80 p-8 space-y-3">
        <svg class="w-10 h-10 animate-spin text-emerald-600 mx-auto" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
        <p class="text-sm font-semibold text-slate-600">Memuat rincian 12 bulan dan jenis tagihan siswa...</p>
      </div>

      <!-- Detail Content -->
      <div v-else-if="studentDetail" class="space-y-6">
        <!-- Student Info Header Card & KPI Summary -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-6">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-5 pb-5 border-b border-slate-100">
            <!-- Student Profile -->
            <div class="flex items-center gap-4">
              <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-700 text-white font-black text-2xl flex items-center justify-center shadow-md">
                {{ studentDetail.student.nama_lengkap.substring(0, 2).toUpperCase() }}
              </div>
              <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                  <h3 class="text-xl font-black text-slate-900">{{ studentDetail.student.nama_lengkap }}</h3>
                  <span class="px-2.5 py-0.5 rounded-lg bg-emerald-100 text-emerald-900 font-black text-xs border border-emerald-200">
                    Kelas {{ studentDetail.student.kelas }}
                  </span>
                  <span 
                    v-if="studentDetail.summary.total_sisa <= 0 && studentDetail.summary.total_tagihan > 0"
                    class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 inline-flex items-center gap-1"
                  >
                    ✓ Bebas Tunggakan
                  </span>
                  <span 
                    v-else-if="studentDetail.summary.total_sisa > 0"
                    class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300 inline-flex items-center gap-1"
                  >
                    ⚠ Ada Tunggakan
                  </span>
                </div>
                <div class="text-xs text-slate-500 font-mono mt-1 flex flex-wrap items-center gap-x-4 gap-y-1">
                  <span>NIS: <b class="text-slate-800">{{ studentDetail.student.nis || '-' }}</b></span>
                  <span>NISN: <b class="text-slate-800">{{ studentDetail.student.nisn || '-' }}</b></span>
                  <span>Wali: <b class="text-slate-800">{{ studentDetail.student.wali?.nama || '-' }}</b> ({{ studentDetail.student.wali?.hubungan || 'Wali' }})</span>
                  <span>No. WA: <b class="text-slate-800">{{ studentDetail.student.wali?.no_hp || '-' }}</b></span>
                </div>
              </div>
            </div>
          </div>

          <!-- 3 Highlight Metrics -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
              <span class="text-xs font-bold text-slate-500 uppercase">Total Kewajiban Tagihan</span>
              <div class="text-2xl font-black text-slate-900 mt-1">
                {{ formatRupiah(studentDetail.summary.total_tagihan) }}
              </div>
              <span class="text-xs text-slate-500 font-medium">{{ studentDetail.summary.count_total }} tagihan diterbitkan</span>
            </div>
            <div class="bg-emerald-50/70 p-4 rounded-xl border border-emerald-200">
              <span class="text-xs font-bold text-emerald-700 uppercase">Total Sudah Terbayar</span>
              <div class="text-2xl font-black text-emerald-700 mt-1">
                {{ formatRupiah(studentDetail.summary.total_dibayar) }}
              </div>
              <span class="text-xs text-emerald-800 font-semibold">{{ studentDetail.summary.count_lunas }} tagihan lunas</span>
            </div>
            <div class="bg-rose-50/70 p-4 rounded-xl border border-rose-200">
              <span class="text-xs font-bold text-rose-700 uppercase">Total Sisa Piutang / Tunggakan</span>
              <div class="text-2xl font-black text-rose-600 mt-1">
                {{ formatRupiah(studentDetail.summary.total_sisa) }}
              </div>
              <span class="text-xs text-rose-700 font-semibold">{{ studentDetail.summary.count_belum_lunas }} tagihan belum selesai</span>
            </div>
          </div>
        </div>

        <!-- Section Switcher Tabs -->
        <div class="flex items-center gap-2 border-b border-slate-200 pb-2 overflow-x-auto">
          <button
            @click="activeDetailSection = '12_months'"
            :class="activeDetailSection === '12_months' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 font-semibold'"
            class="px-4 py-2.5 rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5 whitespace-nowrap"
          >
            <span>🗓️</span>
            <span>Matriks 12 Bulan SPP</span>
          </button>
          <button
            @click="activeDetailSection = 'fee_types'"
            :class="activeDetailSection === 'fee_types' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 font-semibold'"
            class="px-4 py-2.5 rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5 whitespace-nowrap"
          >
            <span>📑</span>
            <span>Rincian Jenis Biaya (Tarif)</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeDetailSection === 'fee_types' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'">
              {{ studentDetail.rekap_jenis_biaya.length }}
            </span>
          </button>
          <button
            @click="activeDetailSection = 'unpaid'"
            :class="activeDetailSection === 'unpaid' ? 'bg-rose-600 text-white font-bold shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 font-semibold'"
            class="px-4 py-2.5 rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5 whitespace-nowrap"
          >
            <span>❌</span>
            <span>Semua Tunggakan Aktif</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeDetailSection === 'unpaid' ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-800'">
              {{ studentDetail.tagihan_belum_lunas.length }}
            </span>
          </button>
          <button
            @click="activeDetailSection = 'history'"
            :class="activeDetailSection === 'history' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 font-semibold'"
            class="px-4 py-2.5 rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5 whitespace-nowrap"
          >
            <span>🧾</span>
            <span>Riwayat Kuitansi Pembayaran</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeDetailSection === 'history' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'">
              {{ studentDetail.riwayat_pembayaran.length }}
            </span>
          </button>
        </div>

        <!-- ============================================================== -->
        <!-- SECTION 1: MATRIKS 12 BULAN SPP (JULI s/d JUNI)               -->
        <!-- ============================================================== -->
        <div v-if="activeDetailSection === '12_months'" class="space-y-4">
          <div class="bg-emerald-50/60 p-4 rounded-2xl border border-emerald-200/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
              <h4 class="font-black text-slate-900 text-sm flex items-center gap-2">
                <span>🗓️</span>
                <span>Buku Pembayaran SPP Bulanan (Tahun Ajaran {{ studentDetail.academic_year?.nama || '2026/2027' }})</span>
              </h4>
              <p class="text-xs text-slate-600 mt-0.5">
                Memantau status pelunasan 12 bulan (Juli s/d Juni). Tarif dasar: <b>{{ formatRupiah(studentDetail.spp_fee_type?.nominal || 350000) }} / bulan</b>.
              </p>
            </div>
            <div class="text-xs text-slate-500 font-medium">
              12 Periode Akademik
            </div>
          </div>

          <!-- 12 Months Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div 
              v-for="m in studentDetail.matriks_12_bulan" 
              :key="m.periode_key"
              class="bg-white rounded-2xl border p-4 shadow-2xs transition hover:shadow-md flex flex-col justify-between"
              :class="{
                'border-emerald-300 bg-emerald-50/20': m.status === 'lunas',
                'border-rose-300 bg-rose-50/30': m.status === 'belum_bayar',
                'border-amber-300 bg-amber-50/20': m.status === 'sebagian',
                'border-slate-200 bg-slate-50/50': m.status === 'belum_terbit'
              }"
            >
              <div>
                <!-- Month Header -->
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                  <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center font-mono">
                      {{ m.urutan }}
                    </span>
                    <span class="font-black text-slate-900 text-sm">{{ m.label }}</span>
                  </div>
                  <!-- Status Badge -->
                  <div>
                    <span 
                      v-if="m.status === 'lunas'" 
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"
                    >
                      Lunas ✓
                    </span>
                    <span 
                      v-else-if="m.status === 'sebagian'" 
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200"
                    >
                      Cicilan
                    </span>
                    <span 
                      v-else-if="m.status === 'belum_bayar'" 
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200"
                    >
                      Belum Bayar
                    </span>
                    <span 
                      v-else 
                      class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-200 text-slate-600"
                    >
                      Belum Terbit
                    </span>
                  </div>
                </div>

                <!-- Financial Details for this month -->
                <div class="py-3 space-y-1.5 text-xs">
                  <div class="flex items-center justify-between text-slate-500">
                    <span>Nominal SPP:</span>
                    <span class="font-bold text-slate-800">{{ formatRupiah(m.jumlah) }}</span>
                  </div>
                  <div class="flex items-center justify-between text-slate-500">
                    <span>Sudah Dibayar:</span>
                    <span class="font-bold text-emerald-600">{{ formatRupiah(m.jumlah_dibayar) }}</span>
                  </div>
                  <div class="flex items-center justify-between pt-1 border-t border-slate-100">
                    <span class="font-semibold text-slate-700">Sisa Tunggakan:</span>
                    <span class="font-black text-sm" :class="m.sisa > 0 ? 'text-rose-600' : 'text-slate-400'">
                      {{ formatRupiah(m.sisa) }}
                    </span>
                  </div>
                  <div v-if="m.invoice?.no_tagihan" class="text-[10px] text-slate-400 font-mono pt-1">
                    No: {{ m.invoice.no_tagihan }}
                  </div>
                </div>
              </div>

              <!-- Action button on card -->
              <div class="pt-2 border-t border-slate-100">
                <!-- If has unpaid invoice: Pay button -->
                <button
                  v-if="m.invoice && m.sisa > 0"
                  @click="handlePay(m.invoice)"
                  class="w-full py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                  <span>Bayar SPP {{ m.bulan_nama }}</span>
                </button>

                <!-- If already paid: View check -->
                <div 
                  v-else-if="m.status === 'lunas'" 
                  class="py-1 text-center text-xs font-bold text-emerald-700 bg-emerald-100/60 rounded-xl"
                >
                  Terbayar Lunas ✓
                </div>

                <!-- If not yet generated: Quick Generate Button -->
                <button
                  v-else
                  @click="quickCreateInvoice(m)"
                  class="w-full py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition flex items-center justify-center gap-1 cursor-pointer"
                  title="Generate tagihan SPP untuk bulan ini"
                >
                  <span>+ Terbitkan Tagihan</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- SECTION 2: RINCIAN BERDASARKAN TARIF & JENIS BIAYA (MASTER)    -->
        <!-- ============================================================== -->
        <div v-if="activeDetailSection === 'fee_types'" class="space-y-4">
          <div class="bg-indigo-50/60 p-4 rounded-2xl border border-indigo-200/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
              <h4 class="font-black text-slate-900 text-sm flex items-center gap-2">
                <span>📑</span>
                <span>Rekap Terhubung ke Menu Tarif & Jenis Biaya</span>
              </h4>
              <p class="text-xs text-slate-600 mt-0.5">
                Kewajiban administrasi dikelompokkan sesuai jenis biaya (SPP, Uang Pangkal, Seragam, Kegiatan, Daftar Ulang, dll).
              </p>
            </div>
            <router-link
              to="/finance?tab=fee_types"
              class="px-3 py-1.5 bg-white border border-indigo-200 hover:bg-indigo-50 text-indigo-700 text-xs font-bold rounded-xl transition flex items-center gap-1 shadow-2xs"
            >
              <span>⚙️ Kelola Master Tarif</span>
            </router-link>
          </div>

          <!-- Fee Types List -->
          <div class="space-y-3">
            <div 
              v-for="ft in studentDetail.rekap_jenis_biaya" 
              :key="ft.fee_type_id"
              class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 space-y-3"
            >
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                  <span class="px-2.5 py-1 rounded-xl bg-slate-100 text-slate-800 font-mono font-bold text-xs uppercase border border-slate-200">
                    {{ ft.kode }}
                  </span>
                  <div>
                    <div class="flex items-center gap-2">
                      <h4 class="font-black text-slate-900 text-base">{{ ft.nama }}</h4>
                      <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 uppercase">
                        {{ ft.periode }}
                      </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                      Tarif Standar Sekolah: <b>{{ formatRupiah(ft.nominal_standar) }}</b> 
                      {{ ft.keterangan ? `• ${ft.keterangan}` : '' }}
                    </p>
                  </div>
                </div>

                <div class="flex items-center gap-4 self-end sm:self-center">
                  <div class="text-right">
                    <span class="text-[10px] text-slate-400 block uppercase font-bold">Status Tagihan</span>
                    <span 
                      v-if="ft.status === 'lunas'" 
                      class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 inline-block"
                    >
                      Lunas ✓
                    </span>
                    <span 
                      v-else-if="ft.status === 'sebagian'" 
                      class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200 inline-block"
                    >
                      Cicilan
                    </span>
                    <span 
                      v-else-if="ft.status === 'belum_bayar'" 
                      class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200 inline-block"
                    >
                      Ada Tunggakan
                    </span>
                    <span 
                      v-else 
                      class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500 inline-block"
                    >
                      Belum Ada Tagihan
                    </span>
                  </div>

                  <!-- Quick Generate Button if no invoice -->
                  <button
                    v-if="ft.count_invoices === 0"
                    @click="quickCreateFeeTypeInvoice(ft)"
                    class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl font-bold text-xs transition cursor-pointer"
                  >
                    + Terbitkan Tagihan
                  </button>
                </div>
              </div>

              <!-- Metric mini-bar -->
              <div class="grid grid-cols-3 gap-2 bg-slate-50/70 p-3 rounded-xl border border-slate-100 text-xs">
                <div>
                  <span class="text-slate-400 block text-[10px] font-bold uppercase">Total Tagihan</span>
                  <span class="font-bold text-slate-800">{{ formatRupiah(ft.total_tagihan) }}</span>
                  <span class="text-[10px] text-slate-400 block">({{ ft.count_invoices }} tagihan)</span>
                </div>
                <div>
                  <span class="text-emerald-700 block text-[10px] font-bold uppercase">Sudah Dibayar</span>
                  <span class="font-bold text-emerald-700">{{ formatRupiah(ft.total_dibayar) }}</span>
                </div>
                <div>
                  <span class="text-rose-700 block text-[10px] font-bold uppercase">Sisa Tunggakan</span>
                  <span class="font-black" :class="ft.total_sisa > 0 ? 'text-rose-600' : 'text-slate-400'">
                    {{ formatRupiah(ft.total_sisa) }}
                  </span>
                </div>
              </div>

              <!-- Invoices list under this FeeType -->
              <div v-if="ft.invoices.length > 0" class="space-y-1.5 pt-1">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                  Daftar Transaksi Tagihan:
                </span>
                <div 
                  v-for="inv in ft.invoices" 
                  :key="inv.id"
                  class="p-2.5 bg-white rounded-xl border border-slate-200/80 flex items-center justify-between text-xs"
                >
                  <div class="flex items-center gap-2">
                    <span class="font-mono text-slate-500 font-bold">{{ inv.no_tagihan }}</span>
                    <span class="px-2 py-0.5 bg-slate-100 rounded text-[10px] font-semibold">{{ inv.periode || '-' }}</span>
                    <span class="text-slate-400">• Jatuh Tempo: {{ formatDateIndo(inv.jatuh_tempo) }}</span>
                  </div>

                  <div class="flex items-center gap-3">
                    <div class="text-right">
                      <span class="text-slate-500">Total: <b>{{ formatRupiah(inv.jumlah) }}</b></span>
                      <span class="mx-1 text-slate-300">|</span>
                      <span :class="inv.sisa > 0 ? 'text-rose-600 font-black' : 'text-emerald-600 font-bold'">
                        Sisa: {{ formatRupiah(inv.sisa) }}
                      </span>
                    </div>

                    <button
                      v-if="inv.sisa > 0"
                      @click="handlePay(inv)"
                      class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-[11px] transition cursor-pointer"
                    >
                      Bayar
                    </button>
                    <span v-else class="text-emerald-700 font-bold text-[11px]">
                      Lunas ✓
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- SECTION 3: SEMUA TUNGGAKAN AKTIF (BELUM LUNAS)                 -->
        <!-- ============================================================== -->
        <div v-if="activeDetailSection === 'unpaid'" class="space-y-3">
          <div v-if="studentDetail.tagihan_belum_lunas.length === 0" class="py-16 text-center text-slate-400 bg-white rounded-2xl border border-slate-200/80 p-8">
            <span class="text-3xl">🎉</span>
            <h4 class="font-black text-slate-900 text-base mt-2">Tidak Ada Tunggakan!</h4>
            <p class="text-xs text-slate-500 mt-1">Seluruh tagihan sekolah siswa ini telah selesai dan berstatus lunas.</p>
          </div>
          <div v-else class="space-y-2.5">
            <div 
              v-for="inv in studentDetail.tagihan_belum_lunas" 
              :key="inv.id"
              class="p-4 bg-white rounded-2xl border border-rose-200 shadow-2xs hover:shadow-xs transition flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3"
            >
              <div>
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-mono text-xs text-slate-600 font-bold">{{ inv.no_tagihan }}</span>
                  <span class="font-black text-slate-900 text-sm">
                    {{ (inv as any).fee_type?.nama || (inv as any).feeType?.nama || 'Tagihan Biaya' }}
                  </span>
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-200">
                    Periode: {{ inv.periode || '-' }}
                  </span>
                </div>
                <div class="text-xs text-slate-500 mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-0.5">
                  <span>Total Tagihan: <b class="text-slate-800">{{ formatRupiah(inv.jumlah) }}</b></span>
                  <span>Sudah Dibayar: <b class="text-emerald-700">{{ formatRupiah(inv.jumlah_dibayar) }}</b></span>
                  <span>Batas Jatuh Tempo: <b class="text-rose-700">{{ formatDateIndo(inv.jatuh_tempo) }}</b></span>
                </div>
              </div>

              <div class="flex items-center gap-3 self-end sm:self-center shrink-0">
                <div class="text-right">
                  <span class="text-[10px] text-rose-500 block uppercase font-bold">Sisa Tunggakan</span>
                  <span class="text-base font-black text-rose-600">{{ formatRupiah(inv.sisa) }}</span>
                </div>

                <button
                  @click="handlePay(inv)"
                  class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                  <span>Bayar Kasir</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- SECTION 4: RIWAYAT KUITANSI PEMBAYARAN                        -->
        <!-- ============================================================== -->
        <div v-if="activeDetailSection === 'history'" class="space-y-3">
          <div v-if="studentDetail.riwayat_pembayaran.length === 0" class="py-16 text-center text-slate-400 bg-white rounded-2xl border border-slate-200/80 p-8">
            <h4 class="font-bold text-slate-800 text-sm">Belum Ada Riwayat Kuitansi Pembayaran</h4>
            <p class="text-xs text-slate-500 mt-1">Pembayaran yang tercatat oleh kasir akan muncul di sini.</p>
          </div>
          <div v-else class="space-y-2.5">
            <div 
              v-for="p in studentDetail.riwayat_pembayaran" 
              :key="p.id"
              class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3"
            >
              <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                  <span class="font-mono text-xs font-black text-slate-800">{{ p.no_kuitansi }}</span>
                  <span class="font-bold text-slate-900 text-sm">
                    {{ p.invoice?.feeType?.nama || (p.invoice as any)?.fee_type?.nama || 'Tagihan Biaya' }}
                  </span>
                  <span class="px-2 py-0.5 rounded text-[10px] bg-indigo-50 text-indigo-700 font-bold uppercase">
                    {{ p.metode }}
                  </span>
                </div>
                <div class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-x-4 gap-y-0.5">
                  <span>Tanggal Bayar: <b>{{ formatDateIndo(p.tanggal) }}</b></span>
                  <span>Petugas Kasir: <b>{{ p.recorder?.nama || 'Kasir TU' }}</b></span>
                  <span v-if="p.catatan" class="italic text-slate-400 font-mono">"{{ p.catatan }}"</span>
                </div>
              </div>

              <div class="flex items-center gap-3 self-end sm:self-center">
                <div class="text-right">
                  <span class="text-[10px] text-slate-400 block uppercase font-bold">Nominal Terbayar</span>
                  <span class="text-base font-black text-emerald-700">{{ formatRupiah(p.jumlah) }}</span>
                </div>

                <button
                  @click="handleViewReceipt(p)"
                  class="px-3 py-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center gap-1 shadow-2xs cursor-pointer"
                >
                  <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                  </svg>
                  <span>Cetak Kuitansi</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
