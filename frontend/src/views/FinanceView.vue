<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '../services/api'
import { showConfirm, showSuccess, showError, confirmDeleteWithKeyword } from '../services/swal'
import Swal from 'sweetalert2'

const route = useRoute()

// Sub-view modes: 'main' (tabs) | 'pay' (full-page kasir) | 'receipt' (full-page kuitansi resmi) | 'cash_form' (full-page catat kas) | 'fee_form' (full-page tambah/edit tarif)
const viewMode = ref<'main' | 'pay' | 'receipt' | 'cash_form' | 'fee_form'>('main')

// Active tab in main view
const activeTab = ref<'invoices' | 'payments' | 'batch' | 'cashbook' | 'fee_types'>('invoices')
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
const invoices = ref<any[]>([])
const invoiceFilter = ref({
  status: '',
  class_id: '',
  academic_year_id: '',
  search: ''
})

// Master Data Dropdowns
const classes = ref<any[]>([])
const feeTypes = ref<any[]>([])
const academicYears = ref<any[]>([])

// Batch Generator State
const batchForm = ref({
  class_id: null as number | null,
  fee_type_id: 1 as number | string,
  academic_year_id: 1 as number | string,
  periode: new Date().toISOString().substring(0, 7), // YYYY-MM
  jatuh_tempo: new Date(new Date().getFullYear(), new Date().getMonth() + 1, 10).toISOString().split('T')[0]
})
const batchGenerating = ref(false)

// Cashier Payment State (Full-Page Sub-View)
const selectedInvoice = ref<any>(null)
const payForm = ref({
  invoice_id: 0,
  jumlah: 0,
  metode: 'tunai',
  catatan: ''
})
const paying = ref(false)

// Receipt State (Full-Page Printable Sub-View)
const receiptData = ref<any>(null)

// Payments History State
const paymentsList = ref<any[]>([])
const paymentFilter = ref({
  metode: '',
  search: ''
})

// Cashbook State
const transactions = ref<any[]>([])
const cashFilter = ref({
  tipe: '',
  search: ''
})
const cashbookSummary = ref({
  total_masuk: 0,
  total_keluar: 0,
  saldo: 0
})
const cashForm = ref({
  tipe: 'keluar',
  kategori: 'operasional',
  jumlah: 0,
  keterangan: '',
  tanggal: new Date().toISOString().split('T')[0]
})
const savingCash = ref(false)

// Fee Type Form State
const feeTypeForm = ref({
  id: 0,
  kode: '',
  nama: '',
  nominal: 0,
  periode: 'bulanan',
  is_aktif: true,
  keterangan: ''
})
const savingFeeType = ref(false)

const showMessage = (text: string, type: 'success' | 'error' | 'warning' = 'success') => {
  message.value = { type, text }
  setTimeout(() => (message.value = null), 4500)
}

const formatRupiah = (val: number | string | null | undefined) => {
  const num = Number(val) || 0
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num)
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

    // Jika filter kelas kosong ("Semua Kelas"), muat seluruh data tagihan (bukan dibatasi 25)
    if (!invoiceFilter.value.class_id) {
      params.all = true
    }

    const res = await api.get('/invoices', { params })
    invoices.value = res.data.data?.data || res.data.data || []

    // Summary calculation from authoritative backend aggregate
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

// Filtered Invoices (including client search)
const filteredInvoices = computed(() => {
  if (!invoiceFilter.value.search) return invoices.value
  const q = invoiceFilter.value.search.toLowerCase().trim()
  return invoices.value.filter(inv =>
    inv.no_tagihan?.toLowerCase().includes(q) ||
    inv.student?.nama_lengkap?.toLowerCase().includes(q) ||
    inv.student?.nis?.toLowerCase().includes(q) ||
    inv.student?.nisn?.toLowerCase().includes(q)
  )
})

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

// Filtered Payments
const filteredPayments = computed(() => {
  if (!paymentFilter.value.search) return paymentsList.value
  const q = paymentFilter.value.search.toLowerCase().trim()
  return paymentsList.value.filter(p =>
    p.no_kuitansi?.toLowerCase().includes(q) ||
    p.invoice?.no_tagihan?.toLowerCase().includes(q) ||
    p.invoice?.student?.nama_lengkap?.toLowerCase().includes(q) ||
    p.invoice?.student?.nis?.toLowerCase().includes(q)
  )
})

// Open Full-Page Payment Cashier (NO POPUP)
const openPaymentPage = (inv: any) => {
  selectedInvoice.value = inv
  payForm.value.invoice_id = inv.id
  payForm.value.jumlah = inv.sisa
  payForm.value.metode = 'tunai'
  payForm.value.catatan = `Pembayaran tagihan ${inv.no_tagihan}`
  viewMode.value = 'pay'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Submit Payment
const submitPayment = async () => {
  paying.value = true
  try {
    const res = await api.post('/payments', payForm.value)
    showMessage('Pembayaran berhasil dicatat & kuitansi resmi diterbitkan!')
    fetchInvoices()

    // Langsung buka kuitansi resmi (Full-Page)
    if (res.data.data?.id) {
      await viewReceipt(res.data.data.id)
    } else {
      viewMode.value = 'main'
    }
  } catch (err: any) {
    showMessage(err.response?.data?.message || 'Gagal mencatat pembayaran', 'error')
  } finally {
    paying.value = false
  }
}

// View Receipt (Full-Page, NO POPUP)
const viewReceipt = async (paymentId: number) => {
  loading.value = true
  try {
    const res = await api.get(`/payments/${paymentId}/receipt`)
    const raw = res.data.data || {}

    // Normalisasi data kuitansi agar terisi 100% akurat tanpa nilai kosong / undefined
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

// Print Receipt (Browser Print)
const printReceipt = () => {
  window.print()
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
    fetchInvoices()
    activeTab.value = 'invoices'
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

// Cashbook
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

// Filtered Cashbook Transactions
const filteredTransactions = computed(() => {
  if (!cashFilter.value.search) return transactions.value
  const q = cashFilter.value.search.toLowerCase().trim()
  return transactions.value.filter(t =>
    t.no_transaksi?.toLowerCase().includes(q) ||
    t.keterangan?.toLowerCase().includes(q) ||
    t.kategori?.toLowerCase().includes(q) ||
    t.recorder?.nama?.toLowerCase().includes(q)
  )
})

// Delete Invoice (Hapus Tagihan belum dibayar)
const deleteInvoice = async (inv: any) => {
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
      fetchInvoices()
    } catch (err: any) {
      showError('Gagal Menghapus', err.response?.data?.message || 'Terjadi kesalahan saat menghapus tagihan.')
    }
  }
}

// Cancel Payment with Reversal
const cancelPayment = async (payment: any) => {
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
      fetchPayments()
      fetchInvoices()
      fetchCashbook()
    } catch (err: any) {
      showError('Gagal Membatalkan', err.response?.data?.message || 'Terjadi kesalahan saat membatalkan pembayaran.')
    }
  }
}

// WhatsApp Notification Helper Functions
const getGuardianPhone = (inv: any): { phone: string; name: string } => {
  const guardians = inv.student?.guardians || []
  const primary = guardians.find((g: any) => g.pivot?.is_kontak_utama) ||
                  guardians.find((g: any) => g.pivot?.boleh_lihat_keuangan) ||
                  guardians[0]

  if (primary) {
    const rawPhone = primary.no_whatsapp || primary.no_hp || ''
    return { phone: rawPhone, name: primary.nama_lengkap || 'Wali Murid' }
  }

  const studentPhone = inv.student?.no_hp_siswa || inv.student?.telepon || ''
  return { phone: studentPhone, name: `Wali dari ${inv.student?.nama_lengkap || 'Siswa'}` }
}

const formatPhoneForWA = (raw: string): string => {
  let cleaned = (raw || '').replace(/\D/g, '')
  if (cleaned.startsWith('0')) {
    cleaned = '62' + cleaned.substring(1)
  } else if (cleaned.startsWith('8')) {
    cleaned = '62' + cleaned
  }
  return cleaned
}

const generateWAMessage = (inv: any, guardianName: string): string => {
  const studentName = inv.student?.nama_lengkap || 'Ananda'
  const className = inv.student?.school_class?.nama || 'SDI BIM'
  const feeName = inv.fee_type?.nama || 'Biaya Sekolah'
  const periode = inv.periode || '-'
  const noTagihan = inv.no_tagihan || '-'
  const jumlahTotal = formatRupiah(inv.jumlah)
  const jumlahBayar = formatRupiah(inv.jumlah_dibayar)
  const sisa = formatRupiah(inv.sisa)
  const tglJatuhTempo = inv.jatuh_tempo || '-'

  if (inv.status === 'lunas') {
    return `Assalamu'alaikum Warahmatullahi Wabarakatuh,\n` +
      `Yth. Bapak/Ibu *${guardianName}* (Wali dari ananda *${studentName}*, *${className}*).\n\n` +
      `Alhamdulillahirabbil'alamin, kami dari Bagian Keuangan *SDI Bina Insan Mandiri Purwokerto* mengonfirmasi bahwa tagihan sekolah ananda telah *LUNAS*:\n\n` +
      `• *No. Tagihan:* ${noTagihan}\n` +
      `• *Jenis Biaya:* ${feeName}\n` +
      `• *Periode:* ${periode}\n` +
      `• *Jumlah Dibayar:* ${jumlahBayar}\n` +
      `• *Status:* LUNAS ✓\n\n` +
      `Kami mengucapkan terima kasih yang sebesar-besarnya (*Jazakumullahu khairan katsiran*) atas amanah, komitmen, dan kerja sama Bapak/Ibu dalam mendukung kelancaran kegiatan belajar mengajar ananda di SDI BIM. Semoga ananda senantiasa berprestasi, menjadi anak yang sholeh/sholehah, dan membanggakan keluarga. Aamiin.\n\n` +
      `Wassalamu'alaikum Warahmatullahi Wabarakatuh.\n` +
      `_— Bagian Keuangan SDI Bina Insan Mandiri Purwokerto_`
  } else {
    return `Assalamu'alaikum Warahmatullahi Wabarakatuh,\n` +
      `Yth. Bapak/Ibu *${guardianName}* (Wali dari ananda *${studentName}*, *${className}*).\n\n` +
      `Semoga Bapak/Ibu sekeluarga senantiasa berada dalam lindungan Allah SWT serta diberikan kesehatan dan kelapangan rezeki.\n\n` +
      `Kami dari Bagian Keuangan *SDI Bina Insan Mandiri Purwokerto* ingin menginformasikan rincian tagihan sekolah ananda sebagai berikut:\n\n` +
      `• *No. Tagihan:* ${noTagihan}\n` +
      `• *Jenis Biaya:* ${feeName}\n` +
      `• *Periode:* ${periode}\n` +
      `• *Total Tagihan:* ${jumlahTotal}\n` +
      `• *Sudah Dibayar:* ${jumlahBayar}\n` +
      `• *Sisa Tagihan:* *${sisa}*\n` +
      `• *Batas Jatuh Tempo:* ${tglJatuhTempo}\n\n` +
      `Pembayaran dapat dilakukan melalui Loket Kasir Tata Usaha SDI BIM atau transfer ke rekening resmi sekolah (Bank Syariah Indonesia).\n` +
      `_Mohon abaikan pemberitahuan ini apabila Bapak/Ibu telah melakukan pelunasan sebelumnya._\n\n` +
      `Jazakumullahu khairan katsiran atas perhatian, kerja sama, dan komitmen Bapak/Ibu.\n\n` +
      `Wassalamu'alaikum Warahmatullahi Wabarakatuh.\n` +
      `_— Bagian Keuangan SDI Bina Insan Mandiri Purwokerto_`
  }
}

const sendWhatsApp = async (inv: any) => {
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
const deleteFeeType = async (fee: any) => {
  const confirmed = await confirmDeleteWithKeyword(`Tarif: ${fee.nama}`, fee.kode || fee.nama)
  if (confirmed) {
    try {
      await api.delete(`/fee-types/${fee.id}`)
      showSuccess('Berhasil Dihapus', `Jenis biaya ${fee.nama} berhasil dihapus.`)
      fetchMetadata()
    } catch (err: any) {
      showError('Gagal Menghapus', err.response?.data?.message || 'Jenis biaya ini mungkin sedang digunakan oleh tagihan.')
    }
  }
}

// Open Full-Page Cash Form (NO POPUP)
const openCashForm = () => {
  cashForm.value = {
    tipe: 'keluar',
    kategori: 'operasional',
    jumlah: 0,
    keterangan: '',
    tanggal: new Date().toISOString().split('T')[0]
  }
  viewMode.value = 'cash_form'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Submit Cash Transaction
const submitCashTransaction = async () => {
  savingCash.value = true
  try {
    await api.post('/finance-transactions', cashForm.value)
    showMessage('Transaksi kas berhasil dicatat!')
    viewMode.value = 'main'
    activeTab.value = 'cashbook'
    fetchCashbook()
  } catch (err: any) {
    showMessage(err.response?.data?.message || 'Gagal menyimpan transaksi kas', 'error')
  } finally {
    savingCash.value = false
  }
}

// Open Full-Page Fee Type Form (NO POPUP)
const openFeeTypeForm = (fee?: any) => {
  if (fee) {
    feeTypeForm.value = {
      id: fee.id,
      kode: fee.kode,
      nama: fee.nama,
      nominal: fee.nominal,
      periode: fee.periode,
      is_aktif: fee.is_aktif,
      keterangan: fee.keterangan || ''
    }
  } else {
    feeTypeForm.value = {
      id: 0,
      kode: '',
      nama: '',
      nominal: 0,
      periode: 'bulanan',
      is_aktif: true,
      keterangan: ''
    }
  }
  viewMode.value = 'fee_form'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Submit Fee Type
const submitFeeType = async () => {
  savingFeeType.value = true
  try {
    if (feeTypeForm.value.id) {
      await api.put(`/fee-types/${feeTypeForm.value.id}`, feeTypeForm.value)
      showMessage('Tarif biaya berhasil diperbarui!')
    } else {
      await api.post('/fee-types', feeTypeForm.value)
      showMessage('Tarif biaya baru berhasil ditambahkan!')
    }
    viewMode.value = 'main'
    activeTab.value = 'fee_types'
    fetchMetadata()
  } catch (err: any) {
    showMessage(err.response?.data?.message || 'Gagal menyimpan tarif biaya', 'error')
  } finally {
    savingFeeType.value = false
  }
}

watch(() => route.query.tab, (newTab) => {
  if (newTab && ['invoices', 'payments', 'batch', 'cashbook', 'fee_types'].includes(newTab as string)) {
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
  if (route.query.tab && ['invoices', 'payments', 'batch', 'cashbook', 'fee_types'].includes(route.query.tab as string)) {
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
      <button @click="message = null" class="ml-2 text-white/80 hover:text-white">&times;</button>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW MODE 1: MAIN DASHBOARD TABS (DEFAULT VIEW) -->
    <!-- ========================================================================= -->
    <div v-if="viewMode === 'main'" class="space-y-6">
      <!-- Header Banner -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-gradient-to-br from-emerald-800 via-emerald-700 to-teal-800 p-6 sm:p-7 rounded-3xl text-white shadow-xl shadow-emerald-950/10">
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <span class="px-3 py-1 bg-emerald-500/30 backdrop-blur-md rounded-full text-xs font-bold uppercase tracking-wider text-emerald-100 border border-emerald-300/30">
              Sistem Keuangan Terpadu
            </span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Manajemen Keuangan & SPP</h1>
          <p class="text-sm text-emerald-100/90 max-w-2xl">
            Kelola tagihan SPP siswa, kasir penerimaan pembayaran, penerbitan kuitansi resmi, dan buku kas operasional SDI Bina Insan Mandiri.
          </p>
        </div>
        <div class="flex items-center gap-2.5">
          <button 
            @click="activeTab = 'batch'"
            class="bg-white text-emerald-900 hover:bg-emerald-50 font-bold text-xs sm:text-sm px-4 py-2.5 rounded-xl shadow-md transition flex items-center gap-2 active:scale-95"
          >
            <span>+ Generate SPP Massal</span>
          </button>
          <button 
            @click="openCashForm"
            class="bg-emerald-900/60 hover:bg-emerald-900/80 text-white border border-emerald-400/30 font-bold text-xs sm:text-sm px-4 py-2.5 rounded-xl shadow-md transition flex items-center gap-2 active:scale-95"
          >
            <span>+ Catat Kas</span>
          </button>
        </div>
      </div>

      <!-- KPI Finance Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
          <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Tagihan</span>
          <h3 class="text-2xl font-black text-slate-800 mt-1">{{ formatRupiah(summary.total_invoiced) }}</h3>
          <span class="text-[11px] text-slate-500 font-medium">Tahun Ajaran Berjalan</span>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
          <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Terbayar</span>
          <h3 class="text-2xl font-black text-emerald-600 mt-1">{{ formatRupiah(summary.total_paid) }}</h3>
          <span class="text-[11px] text-emerald-700 font-semibold">Realisasi Penerimaan Kasir</span>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
          <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Sisa Piutang</span>
          <h3 class="text-2xl font-black text-rose-600 mt-1">{{ formatRupiah(summary.total_remaining) }}</h3>
          <span class="text-[11px] text-rose-600 font-medium">Belum Dilunasi</span>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
          <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Saldo Kas Operasional</span>
          <h3 class="text-2xl font-black text-indigo-700 mt-1">{{ formatRupiah(cashbookSummary.saldo) }}</h3>
          <span class="text-[11px] text-indigo-600 font-semibold">Buku Kas Umum</span>
        </div>
      </div>

      <!-- TAB 1: INVOICES (TAGIHAN SISWA) -->
      <div v-if="activeTab === 'invoices'" class="space-y-4">
        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-wrap gap-4 items-center justify-between">
          <div class="flex flex-wrap items-center gap-3">
            <div>
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Status Tagihan</label>
              <select 
                v-model="invoiceFilter.status" 
                @change="fetchInvoices"
                class="border border-slate-200 bg-slate-50 rounded-xl px-3 py-1.5 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
              >
                <option value="">Semua Status</option>
                <option value="belum_bayar">Belum Bayar</option>
                <option value="sebagian">Sebagian</option>
                <option value="lunas">Lunas</option>
                <option value="jatuh_tempo">Jatuh Tempo</option>
              </select>
            </div>
            <div>
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Filter Rombel Kelas</label>
              <select 
                v-model="invoiceFilter.class_id" 
                @change="fetchInvoices"
                class="border border-slate-200 bg-slate-50 rounded-xl px-3 py-1.5 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
              >
                <option value="">Semua Kelas</option>
                <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nama }} (Tingkat {{ c.tingkat }})</option>
              </select>
            </div>
            <div>
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Tahun Ajaran</label>
              <select 
                v-model="invoiceFilter.academic_year_id" 
                @change="fetchInvoices"
                class="border border-slate-200 bg-slate-50 rounded-xl px-3 py-1.5 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
              >
                <option value="">Semua Tahun Ajaran</option>
                <option v-for="y in academicYears" :key="y.id" :value="y.id">{{ y.nama }}</option>
              </select>
            </div>
          </div>
          <div class="w-full sm:w-64">
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Cari Tagihan</label>
            <input 
              v-model="invoiceFilter.search" 
              type="text" 
              placeholder="Cari siswa atau no. tagihan..." 
              class="w-full border border-slate-200 bg-slate-50 rounded-xl px-3 py-1.5 text-xs text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            />
          </div>
        </div>

        <!-- Invoices Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[800px]">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                  <th class="p-3 text-center w-12">No.</th>
                  <th class="p-3">No. Tagihan</th>
                  <th class="p-3">Nama Siswa</th>
                  <th class="p-3">Kelas</th>
                  <th class="p-3">Jenis Biaya / Periode</th>
                  <th class="p-3 text-right">Nominal</th>
                  <th class="p-3 text-right">Sisa Tagihan</th>
                  <th class="p-3 text-center">Status</th>
                  <th class="p-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr v-if="loading">
                  <td colspan="9" class="text-center py-8 text-slate-400 animate-pulse">Memuat data tagihan...</td>
                </tr>
                <tr v-else-if="filteredInvoices.length === 0">
                  <td colspan="9" class="text-center py-10 text-slate-400">Tidak ada tagihan yang sesuai dengan filter.</td>
                </tr>
                <tr v-for="(inv, index) in filteredInvoices" :key="inv.id" class="hover:bg-slate-50/80 transition">
                  <td class="p-3 text-center font-mono text-xs font-bold text-slate-400 whitespace-nowrap">{{ index + 1 }}</td>
                  <td class="p-3 font-mono text-xs font-bold text-slate-700 whitespace-nowrap">{{ inv.no_tagihan }}</td>
                  <td class="p-3 whitespace-nowrap">
                    <div class="font-extrabold text-slate-900">{{ inv.student?.nama_lengkap }}</div>
                    <div class="text-[10px] text-slate-400 font-mono">NIS: {{ inv.student?.nis || '-' }} • NISN: {{ inv.student?.nisn || '-' }}</div>
                  </td>
                  <td class="p-3 text-xs text-slate-600 whitespace-nowrap">{{ inv.student?.school_class?.nama || '-' }}</td>
                  <td class="p-3 text-xs text-slate-700 whitespace-nowrap">
                    <div class="font-bold text-slate-800">{{ inv.fee_type?.nama }}</div>
                    <div class="text-slate-400 text-[10px]">Periode: {{ inv.periode }}</div>
                  </td>
                  <td class="p-3 font-mono text-xs font-semibold text-right whitespace-nowrap">{{ formatRupiah(inv.jumlah) }}</td>
                  <td class="p-3 font-mono text-xs font-bold text-right whitespace-nowrap" :class="inv.sisa > 0 ? 'text-rose-600' : 'text-emerald-600'">
                    {{ formatRupiah(inv.sisa) }}
                  </td>
                  <td class="p-3 text-center whitespace-nowrap">
                    <span 
                      class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider border"
                      :class="{
                        'bg-emerald-100 text-emerald-800 border-emerald-300': inv.status === 'lunas',
                        'bg-amber-100 text-amber-800 border-amber-300': inv.status === 'sebagian',
                        'bg-rose-100 text-rose-800 border-rose-300': inv.status === 'belum_bayar' || inv.status === 'jatuh_tempo'
                      }"
                    >
                      {{ inv.status.replace('_', ' ') }}
                    </span>
                  </td>
                  <td class="p-3 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-2">
                      <!-- Tombol Kasir Bayar (Buka Form Satu Halaman Penuh, TANPA POPUP) -->
                      <button 
                        v-if="inv.status !== 'lunas'"
                        @click="openPaymentPage(inv)"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-3 py-1.5 rounded-xl shadow-xs transition flex items-center gap-1.5"
                        title="Bayar tagihan di kasir"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Kasir Bayar</span>
                      </button>

                      <!-- Tombol Lihat Kuitansi jika ada pembayaran -->
                      <button 
                        v-if="inv.payments && inv.payments.length > 0"
                        @click="viewReceipt(inv.payments[0].id)"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-3 py-1.5 rounded-xl transition flex items-center gap-1.5"
                        title="Lihat Kuitansi Pembayaran Terakhir"
                      >
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Lihat Kuitansi</span>
                      </button>

                      <!-- Tombol WhatsApp: Penagihan (Belum Lunas) atau Apresiasi (Lunas) -->
                      <button 
                        @click="sendWhatsApp(inv)"
                        class="px-2.5 py-1.5 rounded-xl transition flex items-center gap-1.5 font-bold text-xs shadow-xs"
                        :class="inv.status === 'lunas' 
                          ? 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300' 
                          : 'bg-[#25D366] hover:bg-[#20ba59] text-white shadow-emerald-900/10'"
                        :title="inv.status === 'lunas' ? 'Kirim Pesan Apresiasi / Terima Kasih via WhatsApp' : 'Kirim Penagihan Tagihan via WhatsApp'"
                      >
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                          <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                        </svg>
                        <span>{{ inv.status === 'lunas' ? 'Apresiasi' : 'Kirim Tagihan' }}</span>
                      </button>

                      <!-- Tombol Hapus Tagihan (Hanya jika belum pernah dibayar) -->
                      <button 
                        v-if="inv.status === 'belum_bayar' && (!inv.payments || inv.payments.length === 0)"
                        @click="deleteInvoice(inv)"
                        class="bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-bold px-2.5 py-1.5 rounded-xl transition flex items-center gap-1 border border-rose-200"
                        title="Hapus Tagihan"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 2: RIWAYAT KUITANSI & PEMBAYARAN -->
      <div v-if="activeTab === 'payments'" class="space-y-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-wrap gap-4 items-center justify-between">
          <div>
            <h3 class="font-extrabold text-sm text-slate-900">Riwayat Pembayaran & Kuitansi Kasir</h3>
            <p class="text-xs text-slate-500">Seluruh bukti transaksi pembayaran yang telah dicatat dan kuitansi resmi sekolah.</p>
          </div>
          <div class="flex items-center gap-3">
            <select 
              v-model="paymentFilter.metode" 
              @change="fetchPayments"
              class="border border-slate-200 bg-slate-50 rounded-xl px-3 py-1.5 text-xs font-semibold text-slate-700"
            >
              <option value="">Semua Metode</option>
              <option value="tunai">Tunai</option>
              <option value="transfer">Transfer</option>
              <option value="qris">QRIS</option>
            </select>
            <input 
              v-model="paymentFilter.search" 
              type="text" 
              placeholder="Cari no. kuitansi atau siswa..." 
              class="border border-slate-200 bg-slate-50 rounded-xl px-3 py-1.5 text-xs text-slate-700 w-56"
            />
          </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[800px]">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                  <th class="p-3">No. Kuitansi</th>
                  <th class="p-3">Tanggal</th>
                  <th class="p-3">Nama Siswa</th>
                  <th class="p-3">Biaya / Tagihan</th>
                  <th class="p-3 text-center">Metode</th>
                  <th class="p-3 text-right">Jumlah Dibayar</th>
                  <th class="p-3">Petugas Kasir</th>
                  <th class="p-3 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr v-if="loading">
                  <td colspan="8" class="text-center py-8 text-slate-400 animate-pulse">Memuat riwayat pembayaran...</td>
                </tr>
                <tr v-else-if="filteredPayments.length === 0">
                  <td colspan="8" class="text-center py-10 text-slate-400">Belum ada data pembayaran kuitansi.</td>
                </tr>
                <tr v-for="p in filteredPayments" :key="p.id" class="hover:bg-slate-50/80 transition" :class="{ 'opacity-60 bg-rose-50/30': p.is_batal }">
                  <td class="p-3 font-mono text-xs font-bold whitespace-nowrap">
                    <div :class="p.is_batal ? 'text-slate-400 line-through' : 'text-emerald-800'">{{ p.no_kuitansi }}</div>
                    <div v-if="p.is_batal" class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-rose-100 text-rose-700 border border-rose-200">
                      DIBATALKAN
                    </div>
                  </td>
                  <td class="p-3 font-mono text-xs text-slate-500 whitespace-nowrap">{{ p.tanggal }}</td>
                  <td class="p-3 whitespace-nowrap">
                    <div class="font-extrabold text-slate-900">{{ p.invoice?.student?.nama_lengkap }}</div>
                    <div class="text-[10px] text-slate-400">{{ p.invoice?.student?.school_class?.nama || '-' }}</div>
                  </td>
                  <td class="p-3 whitespace-nowrap">
                    <div class="font-bold text-slate-800">{{ p.invoice?.fee_type?.nama }}</div>
                    <div class="text-slate-400 text-[10px]">No. Tagihan: {{ p.invoice?.no_tagihan }}</div>
                  </td>
                  <td class="p-3 text-center whitespace-nowrap">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-700">
                      {{ p.metode }}
                    </span>
                  </td>
                  <td class="p-3 font-mono text-xs font-extrabold text-right whitespace-nowrap" :class="p.is_batal ? 'text-slate-400 line-through' : 'text-emerald-700'">
                    {{ formatRupiah(p.jumlah) }}
                  </td>
                  <td class="p-3 text-xs text-slate-500 whitespace-nowrap">{{ p.recorder?.nama || 'Admin' }}</td>
                  <td class="p-3 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-2">
                      <button 
                        @click="viewReceipt(p.id)"
                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs"
                        title="Buka Lembar Kuitansi"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Kuitansi</span>
                      </button>
                      <button 
                        v-if="!p.is_batal"
                        @click="cancelPayment(p)"
                        class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl text-xs font-bold transition flex items-center gap-1 border border-rose-200"
                        title="Batalkan Pembayaran (Jurnal Pembalik)"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>Batal</span>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 3: BATCH GENERATOR -->
      <div v-if="activeTab === 'batch'" class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 max-w-2xl mx-auto space-y-6">
        <div>
          <h3 class="text-lg font-black text-slate-900">Generator Tagihan SPP Massal</h3>
          <p class="text-xs text-slate-500 mt-1">Buat tagihan SPP secara otomatis untuk seluruh siswa dalam satu rombel kelas atau seluruh sekolah untuk periode bulan tertentu.</p>
        </div>

        <form @submit.prevent="generateBatchInvoices" class="space-y-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pilih Target Rombel Siswa</label>
            <select v-model="batchForm.class_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
              <option :value="null">Semua Rombel (Seluruh Siswa Aktif Sekolah)</option>
              <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.nama }} (Tingkat {{ c.tingkat }})</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Jenis Biaya</label>
              <select v-model="batchForm.fee_type_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                <option v-for="f in feeTypes" :key="f.id" :value="f.id">{{ f.nama }} ({{ formatRupiah(f.nominal) }})</option>
              </select>
            </div>
            <div>
              <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tahun Ajaran</label>
              <select v-model="batchForm.academic_year_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                <option v-for="y in academicYears" :key="y.id" :value="y.id">{{ y.nama }} ({{ y.status === 'aktif' ? 'Aktif' : 'Non-Aktif' }})</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Periode Bulan (YYYY-MM)</label>
              <input type="month" v-model="batchForm.periode" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Batas Jatuh Tempo</label>
              <input type="date" v-model="batchForm.jatuh_tempo" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none" />
            </div>
          </div>

          <div class="pt-4 border-t flex justify-end">
            <button 
              type="submit" 
              :disabled="batchGenerating"
              class="bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs px-6 py-2.5 rounded-xl shadow-md transition disabled:opacity-50 flex items-center gap-2"
            >
              <span v-if="batchGenerating">Memproses Generate Tagihan...</span>
              <span v-else>Generate Tagihan Massal</span>
            </button>
          </div>
        </form>
      </div>

      <!-- TAB 4: CASHBOOK (BUKU KAS) -->
      <div v-if="activeTab === 'cashbook'" class="space-y-4">
        <!-- Filter Bar Cashbook -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-wrap gap-4 items-center justify-between">
          <div class="flex flex-wrap items-center gap-3">
            <div>
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Arus Kas</label>
              <select 
                v-model="cashFilter.tipe" 
                @change="fetchCashbook"
                class="border border-slate-200 bg-slate-50 rounded-xl px-3 py-1.5 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
              >
                <option value="">Semua Arus Kas</option>
                <option value="masuk">Kas Masuk (Penerimaan)</option>
                <option value="keluar">Kas Keluar (Pengeluaran)</option>
              </select>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-56 sm:w-64">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Cari Transaksi</label>
              <input 
                v-model="cashFilter.search" 
                type="text" 
                placeholder="Cari keterangan, bukti..." 
                class="w-full border border-slate-200 bg-slate-50 rounded-xl px-3 py-1.5 text-xs text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
              />
            </div>
            <button 
              @click="openCashForm" 
              class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-xs transition self-end"
            >
              + Catat Kas
            </button>
          </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs sm:text-sm min-w-[700px]">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                  <th class="p-3">Tanggal</th>
                  <th class="p-3">No. Bukti / Transaksi</th>
                  <th class="p-3">Tipe</th>
                  <th class="p-3">Kategori</th>
                  <th class="p-3">Keterangan</th>
                  <th class="p-3 text-right">Jumlah</th>
                  <th class="p-3">Dicatat Oleh</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr v-if="loading">
                  <td colspan="7" class="text-center py-8 text-slate-400 animate-pulse">Memuat buku kas...</td>
                </tr>
                <tr v-else-if="filteredTransactions.length === 0">
                  <td colspan="7" class="text-center py-8 text-slate-400">Belum ada transaksi kas yang sesuai.</td>
                </tr>
                <tr v-for="t in filteredTransactions" :key="t.id" class="hover:bg-slate-50/70">
                  <td class="p-3 font-mono text-xs text-slate-500 whitespace-nowrap">{{ t.tanggal }}</td>
                  <td class="p-3 font-mono text-xs font-bold text-slate-700 whitespace-nowrap">{{ t.no_transaksi }}</td>
                  <td class="p-3 whitespace-nowrap">
                    <span 
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                      :class="t.tipe === 'masuk' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                    >
                      {{ t.tipe }}
                    </span>
                  </td>
                  <td class="p-3 text-xs text-slate-700 capitalize whitespace-nowrap">{{ t.kategori.replace('_', ' ') }}</td>
                  <td class="p-3 text-xs text-slate-600 max-w-sm truncate">{{ t.keterangan }}</td>
                  <td 
                    class="p-3 font-mono text-xs font-bold text-right whitespace-nowrap"
                    :class="t.tipe === 'masuk' ? 'text-emerald-600' : 'text-rose-600'"
                  >
                    {{ t.tipe === 'masuk' ? '+' : '-' }} {{ formatRupiah(t.jumlah) }}
                  </td>
                  <td class="p-3 text-xs text-slate-500 whitespace-nowrap">{{ t.recorder?.nama || 'Admin' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 5: FEE TYPES (TARIF BIAYA) -->
      <div v-if="activeTab === 'fee_types'" class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 space-y-4">
        <div class="flex justify-between items-center border-b border-slate-100 pb-4">
          <div>
            <h3 class="font-extrabold text-base text-slate-900">Master Tarif & Jenis Biaya</h3>
            <p class="text-xs text-slate-500">Daftar tarif resmi SPP bulanan, gedung, seragam, kegiatan dll.</p>
          </div>
          <button 
            @click="openFeeTypeForm()"
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition"
          >
            + Tambah Jenis Biaya
          </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="f in feeTypes" :key="f.id" class="p-4 border rounded-2xl bg-slate-50 space-y-2 hover:bg-white hover:shadow-xs transition">
            <div class="flex justify-between items-start">
              <span class="text-xs font-mono bg-white px-2 py-0.5 rounded-lg border border-slate-200 font-bold text-slate-600">{{ f.kode }}</span>
              <span class="text-[10px] uppercase font-bold text-emerald-800 bg-emerald-100 px-2.5 py-0.5 rounded-full">{{ f.periode }}</span>
            </div>
            <h4 class="font-bold text-slate-800 text-sm">{{ f.nama }}</h4>
            <div class="text-lg font-black text-emerald-700">{{ formatRupiah(f.nominal) }}</div>
            <div class="text-[11px] text-slate-500">{{ f.keterangan || '-' }}</div>
            <div class="pt-2 border-t flex justify-end gap-3">
              <button 
                @click="openFeeTypeForm(f)"
                class="text-xs text-emerald-700 font-bold hover:underline"
              >
                Edit Tarif
              </button>
              <button 
                @click="deleteFeeType(f)"
                class="text-xs text-rose-600 font-bold hover:underline"
              >
                Hapus
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW MODE 2: FORM KASIR PEMBAYARAN TAGIHAN (FULL-PAGE SUB-VIEW, NO POPUP) -->
    <!-- ========================================================================= -->
    <div v-else-if="viewMode === 'pay' && selectedInvoice" class="max-w-2xl mx-auto space-y-6">
      <div class="flex items-center gap-3">
        <button 
          @click="viewMode = 'main'"
          class="p-2.5 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 rounded-2xl transition flex items-center gap-1.5 text-xs font-bold"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Kembali ke Daftar Tagihan</span>
        </button>
      </div>

      <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
        <div class="border-b border-slate-100 pb-4">
          <h2 class="text-xl font-black text-slate-900">Kasir Pembayaran Tagihan</h2>
          <p class="text-xs text-slate-500 mt-1">Catat setoran pembayaran siswa secara langsung dan terbitkan kuitansi resmi sekolah.</p>
        </div>

        <!-- Student & Invoice Banner -->
        <div class="bg-emerald-50/70 p-4 rounded-2xl border border-emerald-100 space-y-2">
          <div class="flex justify-between items-start">
            <div>
              <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800">Nama Siswa:</span>
              <h3 class="text-base font-extrabold text-emerald-950">{{ selectedInvoice.student?.nama_lengkap }}</h3>
              <p class="text-xs text-emerald-700">NIS: {{ selectedInvoice.student?.nis || '-' }} • Kelas: {{ selectedInvoice.student?.school_class?.nama || '-' }}</p>
            </div>
            <div class="text-right">
              <span class="text-[10px] font-mono font-bold text-emerald-700 bg-white px-2 py-0.5 rounded-lg border border-emerald-200">{{ selectedInvoice.no_tagihan }}</span>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-2 pt-2 border-t border-emerald-200/60 text-xs">
            <div>
              <span class="text-slate-500">Biaya:</span> <strong class="text-slate-800">{{ selectedInvoice.fee_type?.nama }}</strong>
            </div>
            <div>
              <span class="text-slate-500">Periode:</span> <strong class="text-slate-800">{{ selectedInvoice.periode }}</strong>
            </div>
            <div>
              <span class="text-slate-500">Total Tagihan:</span> <strong class="text-slate-800">{{ formatRupiah(selectedInvoice.jumlah) }}</strong>
            </div>
            <div>
              <span class="text-slate-500">Sisa Tagihan:</span> <strong class="text-rose-700 font-mono font-bold">{{ formatRupiah(selectedInvoice.sisa) }}</strong>
            </div>
          </div>
        </div>

        <form @submit.prevent="submitPayment" class="space-y-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nominal Pembayaran (Rp)</label>
            <input 
              v-model.number="payForm.jumlah" 
              type="number" 
              min="1000"
              :max="selectedInvoice?.sisa" 
              required 
              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-mono text-lg font-black text-emerald-700 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none" 
            />
            <span class="text-[10px] text-slate-400 mt-1 block">Maksimal pembayaran sesuai sisa tagihan: {{ formatRupiah(selectedInvoice?.sisa) }}</span>
          </div>

          <div>
            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Metode Pembayaran</label>
            <select v-model="payForm.metode" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
              <option value="tunai">Tunai / Cash di Kasir</option>
              <option value="transfer">Transfer Bank Sekolah</option>
              <option value="qris">QRIS Standar</option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Catatan Tambahan</label>
            <input v-model="payForm.catatan" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none" />
          </div>

          <div class="pt-4 border-t flex justify-end gap-3">
            <button 
              type="button" 
              @click="viewMode = 'main'" 
              class="px-5 py-2.5 text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl font-bold transition"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="paying"
              class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl shadow-md transition disabled:opacity-50 flex items-center gap-2"
            >
              <svg v-if="paying" class="animate-spin w-4 h-4" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span>{{ paying ? 'Memproses...' : 'Konfirmasi Pembayaran & Terbitkan Kuitansi' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW MODE 3: LEMBAR RESMI KUITANSI PEMBAYARAN (FULL-PAGE PRINTABLE, NO POPUP) -->
    <!-- ========================================================================= -->
    <div v-else-if="viewMode === 'receipt' && receiptData" class="max-w-2xl mx-auto space-y-6">
      <!-- Top Action Bar (Hidden on Print) -->
      <div class="flex items-center justify-between print:hidden bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <button 
          @click="viewMode = 'main'"
          class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Selesai & Kembali</span>
        </button>

        <button 
          @click="printReceipt"
          class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-extrabold shadow-md transition flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          <span>Cetak Kuitansi Resmi (Ctrl + P)</span>
        </button>
      </div>

      <!-- OFFICIAL RECEIPT DOCUMENT SHEET -->
      <div class="bg-white rounded-3xl p-6 sm:p-10 border border-slate-200 shadow-lg space-y-6 text-slate-800 print:shadow-none print:border-none print:p-0 print:m-0" id="official-receipt-sheet">
        <!-- Receipt Header -->
        <div class="border-b-2 border-dashed border-slate-300 pb-4 flex justify-between items-start">
          <div class="flex items-center gap-3 sm:gap-4">
            <img src="/logo.png" alt="Logo" class="w-14 h-14 sm:w-16 sm:h-16 object-contain" />
            <div>
              <h2 class="font-black text-slate-900 text-base sm:text-lg leading-tight uppercase">SDI BINA INSAN MANDIRI PURWOKERTO</h2>
              <p class="text-[11px] sm:text-xs text-slate-600">Jl. Wadas Kelir RT.04 RW.05 Kel. Karangklesem, Kec. Purwokerto Selatan, Kab. Banyumas 53144</p>
              <p class="text-[10px] text-slate-400">NPSN: 70036695 • Telp: (0281) 635812</p>
            </div>
          </div>
          <div class="text-right">
            <span class="text-[10px] sm:text-xs font-black bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-md uppercase border border-emerald-200">
              BUKTI RESMI
            </span>
            <div class="font-mono text-xs sm:text-sm font-bold text-slate-800 mt-2">{{ receiptData.no_kuitansi }}</div>
          </div>
        </div>

        <!-- Receipt Body Details -->
        <div class="space-y-2.5 text-xs text-slate-700">
          <div class="flex justify-between py-1.5 border-b border-slate-100">
            <span class="text-slate-400">Telah terima dari:</span>
            <span class="font-black text-slate-900 text-sm uppercase">{{ receiptData.student_name }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-slate-100">
            <span class="text-slate-400">Nomor Induk Siswa (NIS):</span>
            <span class="font-mono font-bold text-slate-800">{{ receiptData.student_nis || '-' }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-slate-100">
            <span class="text-slate-400">Kelas / Rombel:</span>
            <span class="font-bold text-slate-800">{{ receiptData.class_name || '-' }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-slate-100">
            <span class="text-slate-400">Untuk Pembayaran:</span>
            <span class="font-bold text-slate-900">{{ receiptData.fee_name }} (Periode: {{ receiptData.period }})</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-slate-100">
            <span class="text-slate-400">Metode Bayar:</span>
            <span class="uppercase font-bold text-slate-800">{{ receiptData.metode }}</span>
          </div>
          <div class="flex justify-between py-1.5 border-b border-slate-100">
            <span class="text-slate-400">Tanggal Pembayaran:</span>
            <span class="font-mono text-slate-800">{{ receiptData.tanggal }}</span>
          </div>

          <!-- Total Box -->
          <div class="bg-emerald-50/70 p-5 rounded-2xl mt-4 border border-emerald-200">
            <div class="flex justify-between items-center">
              <span class="font-bold text-emerald-950 text-xs sm:text-sm uppercase tracking-wider">JUMLAH DIBAYAR:</span>
              <span class="font-mono text-xl sm:text-2xl font-black text-emerald-700">{{ formatRupiah(receiptData.jumlah) }}</span>
            </div>
            <div class="text-xs italic text-slate-600 mt-2">
              <strong>Terbilang:</strong> {{ receiptData.terbilang }} Rupiah.
            </div>
            <div v-if="receiptData.sisa_tagihan > 0" class="mt-2 pt-2 border-t border-emerald-200/60 text-xs text-rose-700 font-semibold flex justify-between">
              <span>Sisa Tagihan yang Belum Dibayar:</span>
              <span class="font-mono font-bold">{{ formatRupiah(receiptData.sisa_tagihan) }}</span>
            </div>
            <div v-else class="mt-2 pt-2 border-t border-emerald-200/60 text-xs text-emerald-800 font-bold flex justify-between">
              <span>Status Tagihan:</span>
              <span>LUNAS ✓</span>
            </div>
          </div>
        </div>

        <!-- Receipt Signatures -->
        <div class="grid grid-cols-2 text-center text-xs pt-4 text-slate-700">
          <div>
            <p class="text-slate-500">Penyetor / Wali Murid</p>
            <div class="h-16"></div>
            <p class="font-bold underline text-slate-800">( ........................................ )</p>
          </div>
          <div>
            <p class="text-slate-500">Purwokerto, {{ receiptData.tanggal }}</p>
            <p class="font-semibold text-slate-700">Kasir / Bagian Keuangan</p>
            <div class="h-16 flex items-center justify-center font-serif text-emerald-700 italic text-xs font-bold">
              [ LUNAS SDI BIM ]
            </div>
            <p class="font-bold underline text-slate-900">{{ receiptData.recorded_by || 'Bendahara Sekolah' }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW MODE 4: FORM CATAT TRANSAKSI KAS (FULL-PAGE SUB-VIEW, NO POPUP) -->
    <!-- ========================================================================= -->
    <div v-else-if="viewMode === 'cash_form'" class="max-w-xl mx-auto space-y-6">
      <div class="flex items-center gap-3">
        <button 
          @click="viewMode = 'main'"
          class="p-2.5 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 rounded-2xl transition flex items-center gap-1.5 text-xs font-bold"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Kembali ke Buku Kas</span>
        </button>
      </div>

      <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
        <div class="border-b border-slate-100 pb-4">
          <h2 class="text-xl font-black text-slate-900">Catat Transaksi Buku Kas</h2>
          <p class="text-xs text-slate-500 mt-1">Pencatatan pemasukan dana hibah, donasi, atau pengeluaran belanja operasional KBM.</p>
        </div>

        <form @submit.prevent="submitCashTransaction" class="space-y-4 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tipe Transaksi</label>
              <select v-model="cashForm.tipe" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-semibold text-slate-800 focus:outline-none">
                <option value="keluar">Kas Keluar (Pengeluaran)</option>
                <option value="masuk">Kas Masuk (Penerimaan)</option>
              </select>
            </div>
            <div>
              <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Kategori Transaksi</label>
              <select v-model="cashForm.kategori" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-semibold text-slate-800 focus:outline-none">
                <option value="operasional">Operasional KBM</option>
                <option value="listrik_air_internet">Listrik & Internet</option>
                <option value="atk">ATK & Percetakan</option>
                <option value="honor">Honorarium / Insentif</option>
                <option value="pemeliharaan">Pemeliharaan Sarpras</option>
                <option value="lainnya">Lainnya</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nominal Transaksi (Rp)</label>
            <input v-model.number="cashForm.jumlah" type="number" required min="1000" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-mono text-base font-black text-slate-800 focus:bg-white focus:outline-none" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Keterangan / Rincian Penggunaan</label>
            <textarea v-model="cashForm.keterangan" rows="3" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-800 focus:bg-white focus:outline-none" placeholder="Tuliskan tujuan dan rincian transaksi kas..."></textarea>
          </div>

          <div>
            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tanggal Transaksi</label>
            <input v-model="cashForm.tanggal" type="date" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-semibold text-slate-800 focus:outline-none" />
          </div>

          <div class="pt-4 border-t flex justify-end gap-3">
            <button type="button" @click="viewMode = 'main'" class="px-5 py-2.5 text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl font-bold transition">Batal</button>
            <button type="submit" :disabled="savingCash" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl shadow-md transition disabled:opacity-50">
              {{ savingCash ? 'Menyimpan...' : 'Simpan Transaksi Kas' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW MODE 5: FORM TAMBAH / EDIT TARIF BIAYA (FULL-PAGE SUB-VIEW, NO POPUP) -->
    <!-- ========================================================================= -->
    <div v-else-if="viewMode === 'fee_form'" class="max-w-xl mx-auto space-y-6">
      <div class="flex items-center gap-3">
        <button 
          @click="viewMode = 'main'"
          class="p-2.5 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 rounded-2xl transition flex items-center gap-1.5 text-xs font-bold"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Kembali ke Master Tarif</span>
        </button>
      </div>

      <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
        <div class="border-b border-slate-100 pb-4">
          <h2 class="text-xl font-black text-slate-900">
            {{ feeTypeForm.id ? 'Edit Tarif & Jenis Biaya' : 'Tambah Jenis Biaya Baru' }}
          </h2>
          <p class="text-xs text-slate-500 mt-1">Tentukan kode, nama jenis biaya, periode tagihan, dan besaran nominal default.</p>
        </div>

        <form @submit.prevent="submitFeeType" class="space-y-4 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Kode Biaya</label>
              <input v-model="feeTypeForm.kode" type="text" required placeholder="Contoh: SPP, GEDUNG" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-mono uppercase font-bold text-slate-800 focus:outline-none" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Periode Tagihan</label>
              <select v-model="feeTypeForm.periode" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-semibold text-slate-800 focus:outline-none">
                <option value="bulanan">Bulanan</option>
                <option value="semester">Semester</option>
                <option value="tahunan">Tahunan</option>
                <option value="sekali">Sekali Bayar</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Jenis Biaya</label>
            <input v-model="feeTypeForm.nama" type="text" required placeholder="Contoh: SPP Bulanan Kelas 1-6" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 font-bold text-slate-800 focus:outline-none" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nominal Standar (Rp)</label>
            <input v-model.number="feeTypeForm.nominal" type="number" required min="0" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-mono text-base font-black text-emerald-700 focus:bg-white focus:outline-none" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">Keterangan Tambahan</label>
            <textarea v-model="feeTypeForm.keterangan" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-800 focus:outline-none"></textarea>
          </div>

          <div class="pt-4 border-t flex justify-end gap-3">
            <button type="button" @click="viewMode = 'main'" class="px-5 py-2.5 text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl font-bold transition">Batal</button>
            <button type="submit" :disabled="savingFeeType" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl shadow-md transition disabled:opacity-50">
              {{ savingFeeType ? 'Menyimpan...' : 'Simpan Jenis Biaya' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
@media print {
  @page {
    size: auto;
    margin: 10mm;
  }
  body {
    background: white !important;
    color: black !important;
  }
}
</style>
