<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../services/api'
import { showConfirm, showSuccess, showError, confirmDeleteWithKeyword } from '../services/swal'
import Swal from 'sweetalert2'

import type { Invoice, Payment, CashTransaction, FeeType, ReceiptData } from '../components/finance/types'
import { formatRupiah, getGuardianPhone, formatPhoneForWA, generateWAMessage } from '../components/finance/types'

import InvoicesTab from '../components/finance/InvoicesTab.vue'
import PaymentsTab from '../components/finance/PaymentsTab.vue'
import StudentRecapTab from '../components/finance/StudentRecapTab.vue'
import BatchGeneratorTab from '../components/finance/BatchGeneratorTab.vue'
import CashbookTab from '../components/finance/CashbookTab.vue'
import FeeTypesTab from '../components/finance/FeeTypesTab.vue'
import CashierPaymentForm from '../components/finance/CashierPaymentForm.vue'
import OfficialReceiptSheet from '../components/finance/OfficialReceiptSheet.vue'
import CashTransactionForm from '../components/finance/CashTransactionForm.vue'
import FeeTypeForm from '../components/finance/FeeTypeForm.vue'
import InfoTooltip from '../components/common/InfoTooltip.vue'

const route = useRoute()
const router = useRouter()

// Sub-view modes: 'main' (tabs) | 'pay' | 'receipt' | 'cash_form' | 'fee_form'
const viewMode = ref<'main' | 'pay' | 'receipt' | 'cash_form' | 'fee_form'>('main')

// Active tab in main view
const activeTab = ref<'invoices' | 'payments' | 'student_recap' | 'batch' | 'cashbook' | 'fee_types'>('invoices')
const loading = ref(false)
const message = ref<{ type: 'success' | 'error' | 'warning'; text: string } | null>(null)

// Summary State
const summary = ref({
  total_invoiced: 0,
  total_paid: 0,
  total_remaining: 0,
  cash_balance: 0
})

// Invoices State
const invoices = ref<Invoice[]>([])
const invoiceFilter = ref({
  status: '',
  class_id: '',
  academic_year_id: '',
  search: ''
})

// Master Data Dropdowns
const classes = ref<any[]>([])
const feeTypes = ref<FeeType[]>([])
const academicYears = ref<any[]>([])

// Batch Generator State
const batchForm = ref({
  class_id: null as number | null,
  fee_type_id: 1 as number | string,
  academic_year_id: 1 as number | string,
  periode: new Date().toISOString().substring(0, 7),
  jatuh_tempo: new Date(new Date().getFullYear(), new Date().getMonth() + 1, 10).toISOString().split('T')[0]
})
const batchGenerating = ref(false)

// Cashier Payment State
const selectedInvoice = ref<Invoice | null>(null)

// Receipt State
const receiptData = ref<ReceiptData | null>(null)

// Payments History State
const paymentsList = ref<Payment[]>([])
const paymentFilter = ref({
  metode: '',
  search: ''
})

// Cashbook State
const transactions = ref<CashTransaction[]>([])
const cashFilter = ref({
  tipe: '',
  search: ''
})
const cashbookSummary = ref({
  total_masuk: 0,
  total_keluar: 0,
  saldo: 0
})

// Fee Type Form State
const selectedFeeType = ref<FeeType | null>(null)

const showMessage = (text: string, type: 'success' | 'error' | 'warning' = 'success') => {
  message.value = { type, text }
  setTimeout(() => (message.value = null), 4500)
}

// Fetch Metadata
const fetchMetadata = async () => {
  try {
    const [cRes, fRes, aRes] = await Promise.all([
      api.get('/classes', { params: { all: true } }),
      api.get('/fee-types'),
      api.get('/academic-years')
    ])
    classes.value = cRes.data.data?.data || cRes.data.data || []
    feeTypes.value = fRes.data.data || []
    academicYears.value = aRes.data.data || []

    if (academicYears.value.length > 0) {
      const activeYear = academicYears.value.find((y: any) => y.is_aktif || y.status === 'aktif') || academicYears.value[0]
      batchForm.value.academic_year_id = activeYear.id
    }
    if (feeTypes.value.length > 0) {
      batchForm.value.fee_type_id = feeTypes.value[0].id
    }
  } catch (err) {
    console.error('Failed to load metadata', err)
  }
}

// Fetch Invoices
const fetchInvoices = async () => {
  loading.value = true
  try {
    const params: any = {}
    if (invoiceFilter.value.status) params.status = invoiceFilter.value.status
    if (invoiceFilter.value.class_id) params.class_id = invoiceFilter.value.class_id
    if (invoiceFilter.value.academic_year_id) params.academic_year_id = invoiceFilter.value.academic_year_id

    if (!invoiceFilter.value.class_id) {
      params.all = true
    }

    const res = await api.get('/invoices', { params })
    invoices.value = res.data.data?.data || res.data.data || []

    if (res.data.summary) {
      summary.value.total_invoiced = Number(res.data.summary.total_nominal) || 0
      summary.value.total_paid = Number(res.data.summary.total_terbayar) || 0
      summary.value.total_remaining = Number(res.data.summary.total_sisa) || 0
    } else {
      let sumTotal = 0, sumPaid = 0, sumRemaining = 0
      invoices.value.forEach(inv => {
        sumTotal += Number(inv.jumlah) || 0
        sumPaid += Number(inv.jumlah_dibayar) || 0
        sumRemaining += Number(inv.sisa) || 0
      })
      summary.value.total_invoiced = sumTotal
      summary.value.total_paid = sumPaid
      summary.value.total_remaining = sumRemaining
    }
  } catch (err) {
    console.error('Failed to fetch invoices', err)
  } finally {
    loading.value = false
  }
}

// Fetch Payments History
const fetchPayments = async () => {
  loading.value = true
  try {
    const params: any = {}
    if (paymentFilter.value.metode) params.metode = paymentFilter.value.metode

    const res = await api.get('/payments', { params })
    paymentsList.value = res.data.data?.data || res.data.data || []
  } catch (err) {
    console.error('Failed to fetch payments', err)
  } finally {
    loading.value = false
  }
}

// Fetch Cashbook
const fetchCashbook = async () => {
  loading.value = true
  try {
    const params: any = {}
    if (cashFilter.value.tipe) params.tipe = cashFilter.value.tipe

    const res = await api.get('/finance-transactions', { params })
    transactions.value = res.data.data?.data || res.data.data || []
    if (res.data.summary) {
      cashbookSummary.value = res.data.summary
    }
  } catch (err) {
    console.error('Failed to load cashbook', err)
  } finally {
    loading.value = false
  }
}

// Open Full-Page Payment Cashier
const openPaymentPage = (inv: Invoice) => {
  selectedInvoice.value = inv
  viewMode.value = 'pay'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// On payment completed
const onPaymentCompleted = async (paymentId: number) => {
  await fetchInvoices()
  await viewReceipt(paymentId)
}

// View Receipt
const viewReceipt = async (paymentId: number) => {
  loading.value = true
  try {
    const res = await api.get(`/payments/${paymentId}/receipt`)
    const raw = res.data.data || {}

    receiptData.value = {
      id: raw.id || paymentId,
      no_kuitansi: raw.no_kuitansi || '-',
      no_tagihan: raw.no_tagihan || '-',
      tanggal: raw.tanggal || new Date().toLocaleDateString('id-ID'),
      student_name: raw.student_name || raw.student?.nama || '-',
      student_nis: raw.student?.nis || '-',
      class_name: raw.class_name || raw.student?.kelas || '-',
      fee_name: raw.fee_name || raw.fee_type || 'Biaya Sekolah',
      period: raw.period || raw.periode || '-',
      jumlah: Number(raw.jumlah ?? raw.jumlah_bayar ?? 0),
      metode: (raw.metode || 'TUNAI').toUpperCase(),
      sisa_tagihan: Number(raw.sisa_tagihan ?? 0),
      status_tagihan: (raw.status_tagihan || 'LUNAS').toUpperCase(),
      catatan: raw.catatan || '',
      recorded_by: raw.recorded_by || raw.kasir || 'Petugas Tata Usaha',
      terbilang: (raw.terbilang || '').replace(/\s*rupiah\s*$/i, '').trim(),
    }

    viewMode.value = 'receipt'
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } catch (err: any) {
    showMessage('Gagal memuat kuitansi: ' + (err.response?.data?.message || err.message), 'error')
  } finally {
    loading.value = false
  }
}

// Batch Invoices Generator
const generateBatchInvoices = async () => {
  batchGenerating.value = true
  try {
    const payload: any = {
      fee_type_id: Number(batchForm.value.fee_type_id) || undefined,
      academic_year_id: Number(batchForm.value.academic_year_id) || undefined,
      periode: batchForm.value.periode,
      jatuh_tempo: batchForm.value.jatuh_tempo,
      tgl_jatuh_tempo: batchForm.value.jatuh_tempo
    }
    if (batchForm.value.class_id) {
      payload.class_id = Number(batchForm.value.class_id)
    } else {
      payload.class_id = null
    }

    const res = await api.post('/invoices/generate-batch', payload)
    showSuccess('Berhasil', res.data.message || 'Tagihan massal berhasil di-generate!')
    await fetchInvoices()
    switchTab('invoices')
  } catch (err: any) {
    let errorMsg = err.response?.data?.message || 'Gagal generate tagihan'
    if (err.response?.data?.errors) {
      const errList = Object.values(err.response.data.errors).flat()
      if (errList.length > 0) {
        errorMsg = errList.join(', ')
      }
    }
    showError('Gagal Generate Tagihan', errorMsg)
  } finally {
    batchGenerating.value = false
  }
}

// Delete Invoice
const deleteInvoice = async (inv: Invoice) => {
  if (inv.status === 'lunas' || Number(inv.jumlah_dibayar) > 0) {
    showError('Tidak Dapat Dihapus', 'Tagihan yang sudah memiliki riwayat pembayaran tidak dapat dihapus.')
    return
  }

  const confirmed = await showConfirm(
    'Hapus Tagihan?',
    `Apakah Anda yakin ingin menghapus tagihan ${inv.no_tagihan} untuk ${inv.student?.nama_lengkap}?`,
    'Ya, Hapus',
    'warning'
  )

  if (confirmed) {
    try {
      await api.delete(`/invoices/${inv.id}`)
      showSuccess('Berhasil', 'Tagihan berhasil dihapus.')
      await fetchInvoices()
    } catch (err: any) {
      showError('Gagal Menghapus', err.response?.data?.message || 'Terjadi kesalahan saat menghapus tagihan.')
    }
  }
}

// Cancel Payment
const cancelPayment = async (payment: Payment) => {
  if (payment.is_batal) return

  const result = await Swal.fire({
    title: 'Batalkan Pembayaran?',
    text: `Masukkan alasan pembatalan untuk kuitansi ${payment.no_kuitansi}:`,
    input: 'text',
    inputPlaceholder: 'Contoh: Salah input kasir / duplikasi pembayaran',
    showCancelButton: true,
    confirmButtonText: 'Batalkan Pembayaran',
    confirmButtonColor: '#e11d48',
    cancelButtonText: 'Batal',
    inputValidator: (val) => {
      if (!val || val.trim().length < 5) {
        return 'Alasan pembatalan minimal 5 karakter!'
      }
      return null
    }
  })

  if (result.isConfirmed && result.value) {
    try {
      await api.post(`/payments/${payment.id}/cancel`, { alasan_batal: result.value })
      showSuccess('Pembayaran Dibatalkan', 'Pembayaran dan transaksi kas telah dibatalkan dengan jurnal pembalik.')
      await fetchPayments()
      await fetchInvoices()
      await fetchCashbook()
    } catch (err: any) {
      showError('Gagal Membatalkan', err.response?.data?.message || 'Terjadi kesalahan saat membatalkan pembayaran.')
    }
  }
}

// Send WhatsApp
const sendWhatsApp = async (inv: Invoice) => {
  const contact = getGuardianPhone(inv)
  let phone = formatPhoneForWA(contact.phone)
  const guardianName = contact.name

  if (!phone || phone.length < 9) {
    const promptResult = await Swal.fire({
      title: 'Nomor WhatsApp Wali Murid',
      html: `Nomor WhatsApp belum tercatat untuk wali dari <strong>${inv.student?.nama_lengkap}</strong>.<br>Silakan masukkan nomor WhatsApp tujuan:`,
      input: 'text',
      inputPlaceholder: 'Contoh: 081234567890',
      showCancelButton: true,
      confirmButtonText: 'Lanjutkan',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#059669',
      customClass: {
        popup: 'rounded-3xl shadow-xl font-sans',
        confirmButton: 'rounded-xl px-5 py-2.5 font-bold',
        cancelButton: 'rounded-xl px-5 py-2.5 font-semibold',
      },
      inputValidator: (val) => {
        if (!val || val.trim().length < 9) {
          return 'Nomor WhatsApp harus valid (minimal 9 digit)!'
        }
        return null
      }
    })

    if (!promptResult.isConfirmed || !promptResult.value) return
    phone = formatPhoneForWA(promptResult.value)
  }

  const isLunas = inv.status === 'lunas'
  const messageText = generateWAMessage(inv, guardianName)

  const confirmSend = await Swal.fire({
    title: isLunas ? 'Kirim Apresiasi Lunas via WA' : 'Kirim Tagihan SPP via WA',
    html: `
      <div class="text-left space-y-2.5 font-sans text-xs">
        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-1">
          <div><span class="text-slate-400">Tujuan:</span> <strong class="text-slate-800">${guardianName}</strong></div>
          <div><span class="text-slate-400">No. WhatsApp:</span> <strong class="text-emerald-700 font-mono">+${phone}</strong></div>
          <div><span class="text-slate-400">Status Tagihan:</span> <span class="font-bold uppercase ${isLunas ? 'text-emerald-600' : 'text-rose-600'}">${inv.status.replace('_', ' ')}</span></div>
        </div>
        <div>
          <label class="block font-bold text-slate-600 mb-1">Pratinjau Pesan yang Dikirim:</label>
          <div class="bg-emerald-50/60 p-3 rounded-xl border border-emerald-200 text-slate-700 whitespace-pre-wrap font-sans text-[11px] max-h-48 overflow-y-auto leading-relaxed">${messageText}</div>
        </div>
      </div>
    `,
    showCancelButton: true,
    confirmButtonColor: '#25D366',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Buka WhatsApp Sekarang',
    cancelButtonText: 'Tutup',
    customClass: {
      popup: 'rounded-3xl shadow-2xl font-sans max-w-lg',
      confirmButton: 'rounded-xl px-5 py-2.5 font-bold text-xs text-white shadow-md',
      cancelButton: 'rounded-xl px-5 py-2.5 font-semibold text-xs',
    }
  })

  if (confirmSend.isConfirmed) {
    const waUrl = `https://wa.me/${phone}?text=${encodeURIComponent(messageText)}`
    window.open(waUrl, '_blank')
  }
}

// Delete Fee Type
const deleteFeeType = async (fee: FeeType) => {
  const confirmed = await confirmDeleteWithKeyword(`Tarif: ${fee.nama}`, fee.kode || fee.nama)
  if (confirmed) {
    try {
      await api.delete(`/fee-types/${fee.id}`)
      showSuccess('Berhasil Dihapus', `Jenis biaya ${fee.nama} berhasil dihapus.`)
      await fetchMetadata()
    } catch (err: any) {
      showError('Gagal Menghapus', err.response?.data?.message || 'Jenis biaya ini mungkin sedang digunakan oleh tagihan.')
    }
  }
}

// Open Full-Page Cash Form
const openCashForm = () => {
  viewMode.value = 'cash_form'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Open Full-Page Fee Type Form
const openFeeTypeForm = (fee?: FeeType) => {
  selectedFeeType.value = fee || null
  viewMode.value = 'fee_form'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Switch tabs and update query
const switchTab = (tab: 'invoices' | 'payments' | 'student_recap' | 'batch' | 'cashbook' | 'fee_types') => {
  activeTab.value = tab
  viewMode.value = 'main'
  router.replace({ query: { ...route.query, tab } })
  if (tab === 'invoices') fetchInvoices()
  else if (tab === 'payments') fetchPayments()
  else if (tab === 'cashbook') fetchCashbook()
  else if (tab === 'fee_types') fetchMetadata()
}

// Watchers and lifecycle
watch(() => route.query.tab, (newTab) => {
  if (newTab && ['invoices', 'payments', 'student_recap', 'batch', 'cashbook', 'fee_types'].includes(newTab as string)) {
    if (activeTab.value !== newTab) {
      activeTab.value = newTab as any
      viewMode.value = 'main'
      if (newTab === 'invoices') fetchInvoices()
      else if (newTab === 'payments') fetchPayments()
      else if (newTab === 'cashbook') fetchCashbook()
      else if (newTab === 'fee_types') fetchMetadata()
    }
  }
})

onMounted(async () => {
  if (route.query.tab && ['invoices', 'payments', 'student_recap', 'batch', 'cashbook', 'fee_types'].includes(route.query.tab as string)) {
    activeTab.value = route.query.tab as any
  }
  await fetchMetadata()
  await fetchCashbook()
  if (activeTab.value === 'invoices') fetchInvoices()
  else if (activeTab.value === 'payments') fetchPayments()
})
</script>

<template>
  <div class="space-y-6 pb-12">
    <!-- Toast Alert Message -->
    <div 
      v-if="message" 
      class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl text-sm font-semibold transition-all transform animate-bounce print:hidden"
      :class="{
        'bg-emerald-600 text-white shadow-emerald-600/30': message.type === 'success',
        'bg-rose-600 text-white shadow-rose-600/30': message.type === 'error',
        'bg-amber-500 text-white shadow-amber-500/30': message.type === 'warning'
      }"
    >
      <span>{{ message.text }}</span>
      <button @click="message = null" class="ml-2 text-white/80 hover:text-white cursor-pointer">&times;</button>
    </div>

    <!-- VIEW MODE 1: MAIN DASHBOARD TABS -->
    <div v-if="viewMode === 'main'" class="space-y-6">
      <!-- Header Minimalis & Rapi -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-4 sm:p-5 rounded-2xl border border-slate-100 shadow-xs">
        <div class="flex items-center gap-2.5">
          <h1 class="text-lg sm:text-xl font-bold text-slate-900 leading-tight">Keuangan & SPP</h1>
          <InfoTooltip text="Pusat pengelolaan tagihan SPP siswa, kasir penerimaan pembayaran, penerbitan kuitansi resmi, dan buku kas operasional SDI Bina Insan Mandiri." />
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button 
            v-if="activeTab === 'invoices' || activeTab === 'payments'"
            @click="switchTab('batch')"
            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-3.5 py-1.5 rounded-xl shadow-xs transition flex items-center gap-1.5 active:scale-95 cursor-pointer"
          >
            <span>+ Generate SPP Massal</span>
          </button>
          <button 
            v-else-if="activeTab === 'cashbook'"
            @click="openCashForm"
            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-3.5 py-1.5 rounded-xl shadow-xs transition flex items-center gap-1.5 active:scale-95 cursor-pointer"
          >
            <span>+ Catat Transaksi Kas</span>
          </button>
          <button 
            v-else-if="activeTab === 'fee_types'"
            @click="openFeeTypeForm()"
            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-3.5 py-1.5 rounded-xl shadow-xs transition flex items-center gap-1.5 active:scale-95 cursor-pointer"
          >
            <span>+ Tambah Jenis Biaya</span>
          </button>
        </div>
      </div>

      <!-- KPI Finance Cards Minimalis -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Tagihan</span>
            <InfoTooltip size="xs" text="Akumulasi seluruh tagihan siswa pada tahun ajaran berjalan." />
          </div>
          <h3 class="text-xl font-black text-slate-800 mt-1.5">{{ formatRupiah(summary.total_invoiced) }}</h3>
          <span class="text-[10px] text-slate-400 mt-1">Tahun Ajaran Aktif</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Terbayar</span>
            <InfoTooltip size="xs" text="Realisasi penerimaan dana SPP yang sudah disahkan kasir." />
          </div>
          <h3 class="text-xl font-black text-emerald-600 mt-1.5">{{ formatRupiah(summary.total_paid) }}</h3>
          <span class="text-[10px] text-emerald-600 mt-1">Penerimaan Kasir</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sisa Piutang</span>
            <InfoTooltip size="xs" text="Sisa tagihan siswa yang belum lunas atau menunggak." />
          </div>
          <h3 class="text-xl font-black text-rose-600 mt-1.5">{{ formatRupiah(summary.total_remaining) }}</h3>
          <span class="text-[10px] text-rose-600 mt-1">Tunggakan Siswa</span>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-xs flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Saldo Kas</span>
            <InfoTooltip size="xs" text="Saldo bersih buku kas umum (kas masuk dikurangi kas keluar)." />
          </div>
          <h3 class="text-xl font-black text-indigo-700 mt-1.5">{{ formatRupiah(cashbookSummary.saldo) }}</h3>
          <span class="text-[10px] text-indigo-600 mt-1">Kas Operasional</span>
        </div>
      </div>

      <!-- TAB 1: INVOICES -->
      <InvoicesTab
        v-if="activeTab === 'invoices'"
        :invoices="invoices"
        :classes="classes"
        :academic-years="academicYears"
        :loading="loading"
        v-model:filter="invoiceFilter"
        @filter-change="fetchInvoices"
        @pay="openPaymentPage"
        @view-receipt="viewReceipt"
        @send-wa="sendWhatsApp"
        @delete-invoice="deleteInvoice"
      />

      <!-- TAB 2: PAYMENTS -->
      <PaymentsTab
        v-if="activeTab === 'payments'"
        :payments="paymentsList"
        :loading="loading"
        v-model:filter="paymentFilter"
        @filter-change="fetchPayments"
        @view-receipt="viewReceipt"
        @cancel-payment="cancelPayment"
      />

      <!-- TAB: REKAP KEUANGAN SISWA -->
      <StudentRecapTab
        v-if="activeTab === 'student_recap'"
        :classes="classes"
        :academic-years="academicYears"
        @pay="openPaymentPage"
        @view-receipt="viewReceipt"
      />

      <!-- TAB 3: BATCH GENERATOR -->
      <BatchGeneratorTab
        v-if="activeTab === 'batch'"
        :classes="classes"
        :fee-types="feeTypes"
        :academic-years="academicYears"
        v-model:batch-form="batchForm"
        :batch-generating="batchGenerating"
        @generate="generateBatchInvoices"
      />

      <!-- TAB 4: CASHBOOK -->
      <CashbookTab
        v-if="activeTab === 'cashbook'"
        :transactions="transactions"
        :loading="loading"
        v-model:filter="cashFilter"
        @filter-change="fetchCashbook"
        @open-cash-form="openCashForm"
      />

      <!-- TAB 5: FEE TYPES -->
      <FeeTypesTab
        v-if="activeTab === 'fee_types'"
        :fee-types="feeTypes"
        @add-fee-type="() => openFeeTypeForm()"
        @edit-fee-type="(f) => openFeeTypeForm(f)"
        @delete-fee-type="deleteFeeType"
      />
    </div>

    <!-- VIEW MODE 2: CASHIER PAYMENT FORM -->
    <CashierPaymentForm
      v-else-if="viewMode === 'pay' && selectedInvoice"
      :selected-invoice="selectedInvoice"
      @close="viewMode = 'main'"
      @paid="onPaymentCompleted"
      @notify="showMessage"
    />

    <!-- VIEW MODE 3: OFFICIAL RECEIPT SHEET -->
    <OfficialReceiptSheet
      v-else-if="viewMode === 'receipt' && receiptData"
      :receipt-data="receiptData"
      @close="viewMode = 'main'"
    />

    <!-- VIEW MODE 4: CASH TRANSACTION FORM -->
    <CashTransactionForm
      v-else-if="viewMode === 'cash_form'"
      @close="viewMode = 'main'"
      @saved="async () => { await fetchCashbook(); switchTab('cashbook'); }"
      @notify="showMessage"
    />

    <!-- VIEW MODE 5: FEE TYPE FORM -->
    <FeeTypeForm
      v-else-if="viewMode === 'fee_form'"
      :initial-data="selectedFeeType"
      @close="viewMode = 'main'"
      @saved="async () => { await fetchMetadata(); switchTab('fee_types'); }"
      @notify="showMessage"
    />
  </div>
</template>
