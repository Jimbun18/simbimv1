<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'
import { showSuccess, showError, showWarning } from '../../services/swal'
import { compressImage } from '../../utils/imageCompressor'
import LandingNavbar from '../../components/landing/LandingNavbar.vue'
import LandingFooter from '../../components/landing/LandingFooter.vue'

const router = useRouter()

// State Form Data
const form = ref({
  jenjang: 'SD',
  tahun_ajaran: '2026/2027',
  gelombang: 'Gelombang 1',

  // Identitas Siswa
  nama_lengkap: '',
  nama_panggilan: '',
  jenis_kelamin: 'L',
  tempat_lahir: '',
  tanggal_lahir: '',
  usia_daftar: '',
  jumlah_saudara: 0,
  anak_ke: 1,
  alamat_lengkap: '',
  tinggal_dengan: 'Ayah & Ibu',
  asal_sekolah: '',
  alamat_asal_sekolah: '',
  is_pindahan: false,
  pindah_ke_kelas: '',
  alasan_pindah: '',

  // Data Ayah
  nama_ayah: '',
  ttl_ayah: '',
  pendidikan_ayah: 'S1',
  pekerjaan_ayah: '',
  penghasilan_ayah: 'Rp > 2.000.000',
  alamat_ayah: '',
  no_hp_ayah: '',

  // Data Ibu
  nama_ibu: '',
  ttl_ibu: '',
  pendidikan_ibu: 'S1',
  pekerjaan_ibu: '',
  penghasilan_ibu: '',
  alamat_ibu: '',
  no_hp_ibu: '',
})

// File state
const pasFotoFile = ref<File | null>(null)
const pasFotoPreview = ref<string>('')
const pasFotoInfo = ref<string>('')

const aktaFile = ref<File | null>(null)
const aktaFileName = ref<string>('')

const kkFile = ref<File | null>(null)
const kkFileName = ref<string>('')

const pasFotoInput = ref<HTMLInputElement | null>(null)
const aktaInput = ref<HTMLInputElement | null>(null)
const kkInput = ref<HTMLInputElement | null>(null)

// UI State
const submitting = ref(false)
const submissionSuccess = ref(false)
const submittedData = ref<any>(null)

// Format bytes
const formatBytes = (bytes: number): string => {
  if (bytes === 0) return '0 B'
  if (bytes > 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
  return Math.round(bytes / 1024) + ' KB'
}

// Auto calculate usia ketika tanggal lahir diubah
watch(() => form.value.tanggal_lahir, (newVal) => {
  if (!newVal) {
    form.value.usia_daftar = ''
    return
  }

  const birthDate = new Date(newVal)
  const today = new Date()

  let years = today.getFullYear() - birthDate.getFullYear()
  let months = today.getMonth() - birthDate.getMonth()

  if (today.getDate() < birthDate.getDate()) {
    months--
  }

  if (months < 0) {
    years--
    months += 12
  }

  if (years >= 0 && months >= 0) {
    form.value.usia_daftar = `${years} tahun ${months} bulan`
  }
})

// Salin alamat siswa ke alamat orang tua otomatis
const copyAlamatToParents = () => {
  if (!form.value.alamat_ayah && form.value.alamat_lengkap) {
    form.value.alamat_ayah = form.value.alamat_lengkap
  }
  if (!form.value.alamat_ibu && form.value.alamat_lengkap) {
    form.value.alamat_ibu = form.value.alamat_lengkap
  }
}

// Upload Pas Foto (Kompresi Otomatis)
const onPasFotoChange = async (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    const rawFile = target.files[0]
    try {
      const compressed = await compressImage(rawFile, 800, 0.85)
      pasFotoFile.value = compressed
      pasFotoPreview.value = URL.createObjectURL(compressed)
      const hemat = Math.round((1 - compressed.size / rawFile.size) * 100)
      pasFotoInfo.value = `${formatBytes(rawFile.size)} ➔ ${formatBytes(compressed.size)} (Hemat ${hemat > 0 ? hemat : 0}%)`
    } catch {
      pasFotoFile.value = rawFile
      pasFotoPreview.value = URL.createObjectURL(rawFile)
      pasFotoInfo.value = formatBytes(rawFile.size)
    }
  }
}

// Upload Akta Kelahiran
const onAktaChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    aktaFile.value = target.files[0]
    aktaFileName.value = target.files[0].name
  }
}

// Upload Kartu Keluarga
const onKkChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    kkFile.value = target.files[0]
    kkFileName.value = target.files[0].name
  }
}

// Handle Submit Form
const handleSubmit = async () => {
  // Validasi dasar
  if (!form.value.nama_lengkap.trim()) {
    showWarning('Perhatian', 'Nama lengkap calon peserta didik wajib diisi.')
    return
  }
  if (!form.value.nama_panggilan.trim()) {
    showWarning('Perhatian', 'Nama panggilan wajib diisi.')
    return
  }
  if (!form.value.tempat_lahir.trim() || !form.value.tanggal_lahir) {
    showWarning('Perhatian', 'Tempat dan tanggal lahir calon siswa wajib diisi.')
    return
  }
  if (!form.value.alamat_lengkap.trim()) {
    showWarning('Perhatian', 'Alamat lengkap wajib diisi.')
    return
  }
  if (!form.value.nama_ayah.trim() || !form.value.no_hp_ayah.trim()) {
    showWarning('Perhatian', 'Nama dan No. WhatsApp Ayah wajib diisi.')
    return
  }
  if (!form.value.nama_ibu.trim() || !form.value.no_hp_ibu.trim()) {
    showWarning('Perhatian', 'Nama dan No. WhatsApp Ibu wajib diisi.')
    return
  }

  submitting.value = true

  const formData = new FormData()
  // Data Text
  formData.append('jenjang', form.value.jenjang)
  formData.append('tahun_ajaran', form.value.tahun_ajaran)
  formData.append('gelombang', form.value.gelombang)
  formData.append('nama_lengkap', form.value.nama_lengkap.trim())
  formData.append('nama_panggilan', form.value.nama_panggilan.trim())
  formData.append('jenis_kelamin', form.value.jenis_kelamin)
  formData.append('tempat_lahir', form.value.tempat_lahir.trim())
  formData.append('tanggal_lahir', form.value.tanggal_lahir)
  formData.append('usia_daftar', form.value.usia_daftar)
  formData.append('jumlah_saudara', String(form.value.jumlah_saudara || 0))
  formData.append('anak_ke', String(form.value.anak_ke || 1))
  formData.append('alamat_lengkap', form.value.alamat_lengkap.trim())
  formData.append('tinggal_dengan', form.value.tinggal_dengan)
  formData.append('asal_sekolah', form.value.asal_sekolah.trim())
  formData.append('alamat_asal_sekolah', form.value.alamat_asal_sekolah.trim())

  if (form.value.is_pindahan) {
    formData.append('pindah_ke_kelas', form.value.pindah_ke_kelas.trim())
    formData.append('alasan_pindah', form.value.alasan_pindah.trim())
  }

  formData.append('nama_ayah', form.value.nama_ayah.trim())
  formData.append('ttl_ayah', form.value.ttl_ayah.trim())
  formData.append('pendidikan_ayah', form.value.pendidikan_ayah)
  formData.append('pekerjaan_ayah', form.value.pekerjaan_ayah.trim())
  formData.append('penghasilan_ayah', form.value.penghasilan_ayah)
  formData.append('alamat_ayah', form.value.alamat_ayah.trim() || form.value.alamat_lengkap.trim())
  formData.append('no_hp_ayah', form.value.no_hp_ayah.trim())

  formData.append('nama_ibu', form.value.nama_ibu.trim())
  formData.append('ttl_ibu', form.value.ttl_ibu.trim())
  formData.append('pendidikan_ibu', form.value.pendidikan_ibu)
  formData.append('pekerjaan_ibu', form.value.pekerjaan_ibu.trim())
  formData.append('penghasilan_ibu', form.value.penghasilan_ibu.trim())
  formData.append('alamat_ibu', form.value.alamat_ibu.trim() || form.value.alamat_lengkap.trim())
  formData.append('no_hp_ibu', form.value.no_hp_ibu.trim())

  // Files
  if (pasFotoFile.value) {
    formData.append('pas_foto', pasFotoFile.value)
  }
  if (aktaFile.value) {
    formData.append('akta_kelahiran', aktaFile.value)
  }
  if (kkFile.value) {
    formData.append('kartu_keluarga', kkFile.value)
  }

  try {
    const res = await api.post('/public/ppdb/register', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    if (res.data?.success) {
      submittedData.value = res.data.data
      submissionSuccess.value = true
      window.scrollTo({ top: 0, behavior: 'smooth' })
      showSuccess('Pendaftaran Berhasil!', 'Data formulir PPDB ananda berhasil kami terima.')
    }
  } catch (err: any) {
    showError('Gagal Mengirim', err.response?.data?.message || 'Terjadi kesalahan saat mengirim pendaftaran.')
  } finally {
    submitting.value = false
  }
}

// Tombol Salin Nomor Pendaftaran
const copyRegistrationNumber = () => {
  if (submittedData.value?.nomor_pendaftaran) {
    navigator.clipboard.writeText(submittedData.value.nomor_pendaftaran)
    showSuccess('Tersalin!', 'Nomor pendaftaran berhasil disalin ke clipboard.')
  }
}

// Buka Chat WhatsApp Panitia untuk Konfirmasi
const openWhatsAppConfirmation = () => {
  if (!submittedData.value) return
  const noDaftar = submittedData.value.nomor_pendaftaran
  const namaAnak = submittedData.value.nama_lengkap
  const jenjang = submittedData.value.jenjang
  const namaAyah = submittedData.value.nama_ayah
  const noHp = submittedData.value.no_hp_ayah || submittedData.value.no_hp_ibu

  const text = encodeURIComponent(
    `Bismillah, Assalamu'alaikum Warahmatullahi Wabarakatuh.\n\n` +
    `Yth. Panitia PPDB SIT Bina Insan Mandiri Purwokerto,\n` +
    `Saya telah mengisi Formulir PPDB Online dengan data sbb:\n` +
    `• No. Pendaftaran: *${noDaftar}*\n` +
    `• Nama Calon Siswa: *${namaAnak}*\n` +
    `• Jenjang: *${jenjang}*\n` +
    `• Nama Orang Tua: *${namaAyah}*\n` +
    `• Kontak WhatsApp: ${noHp}\n\n` +
    `Mohon konfirmasi dan informasi tahapan observasi selanjutnya. Jazakumullahu khairan katsiran.`
  )

  window.open(`https://wa.me/6285250725032?text=${text}`, '_blank')
}

// Cetak Bukti Pendaftaran
const printRegistrationSlip = () => {
  window.print()
}

onMounted(() => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-800 font-sans selection:bg-emerald-500 selection:text-white pb-20">
    <LandingNavbar />

    <!-- Print Only Slip Pendaftaran -->
    <div v-if="submissionSuccess && submittedData" class="hidden print:block p-8 bg-white text-black font-sans">
      <div class="border-b-2 border-slate-900 pb-4 mb-6 flex items-center justify-between">
        <div>
          <h1 class="text-xl font-bold uppercase tracking-tight">TANDA BUKTI PENDAFTARAN PPDB ONLINE</h1>
          <p class="text-sm font-semibold text-slate-700">PAUD Terpadu (KB & TK) & SD Islam Terpadu Bina Insan Mandiri</p>
          <p class="text-xs text-slate-500">Jl. M. Yamin RT 02 RW 03 No. 09 Karangklesem, Purwokerto Selatan (0852-5072-5032)</p>
        </div>
        <div class="text-right">
          <div class="text-xs font-mono font-bold text-slate-500 uppercase">NO. PENDAFTARAN</div>
          <div class="text-lg font-black font-mono text-emerald-800">{{ submittedData.nomor_pendaftaran }}</div>
        </div>
      </div>

      <table class="w-full text-xs border border-slate-300 mb-6">
        <tbody>
          <tr class="border-b"><td class="p-2 font-bold w-1/3 bg-slate-100">Jenjang Pilihan</td><td class="p-2 font-bold text-emerald-800">{{ submittedData.jenjang }}</td></tr>
          <tr class="border-b"><td class="p-2 font-bold bg-slate-100">Nama Lengkap Siswa</td><td class="p-2 font-bold">{{ submittedData.nama_lengkap }} ({{ submittedData.nama_panggilan }})</td></tr>
          <tr class="border-b"><td class="p-2 font-bold bg-slate-100">Jenis Kelamin / TTL</td><td class="p-2">{{ submittedData.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }} | {{ submittedData.tempat_lahir }}, {{ submittedData.tanggal_lahir }}</td></tr>
          <tr class="border-b"><td class="p-2 font-bold bg-slate-100">Usia Saat Mendaftar</td><td class="p-2">{{ submittedData.usia_daftar }}</td></tr>
          <tr class="border-b"><td class="p-2 font-bold bg-slate-100">Alamat Tempat Tinggal</td><td class="p-2">{{ submittedData.alamat_lengkap }}</td></tr>
          <tr class="border-b"><td class="p-2 font-bold bg-slate-100">Asal Sekolah</td><td class="p-2">{{ submittedData.asal_sekolah || '-' }}</td></tr>
          <tr class="border-b"><td class="p-2 font-bold bg-slate-100">Nama Ayah / No WA</td><td class="p-2">{{ submittedData.nama_ayah }} ({{ submittedData.no_hp_ayah }})</td></tr>
          <tr class="border-b"><td class="p-2 font-bold bg-slate-100">Nama Ibu / No WA</td><td class="p-2">{{ submittedData.nama_ibu }} ({{ submittedData.no_hp_ibu }})</td></tr>
          <tr><td class="p-2 font-bold bg-slate-100">Tanggal Pendaftaran</td><td class="p-2">{{ new Date(submittedData.created_at).toLocaleString('id-ID') }} WIB</td></tr>
        </tbody>
      </table>

      <div class="mt-8 pt-4 border-t border-slate-300 text-xs text-slate-600 flex justify-between">
        <div>
          <p>Catatan:</p>
          <p>• Simpan lembar ini sebagai tanda bukti resmi pendaftaran.</p>
          <p>• Konfirmasikan nomor pendaftaran ke WhatsApp Panitia: 0852-5072-5032.</p>
        </div>
        <div class="text-center w-48">
          <p>Purwokerto, {{ new Date().toLocaleDateString('id-ID') }}</p>
          <p class="mt-12 font-bold">Panitia PPDB SIT BIM</p>
        </div>
      </div>
    </div>

    <!-- TAMPILAN SUKSES PENDAFTARAN (NON-PRINT) -->
    <div v-if="submissionSuccess && submittedData" class="max-w-3xl mx-auto px-4 pt-12 print:hidden animate-fadeIn space-y-6">
      <div class="bg-white rounded-3xl p-8 sm:p-10 shadow-xl border border-emerald-200 text-center space-y-6 relative overflow-hidden">
        <div class="absolute -top-16 -right-16 w-48 h-48 bg-emerald-100/60 rounded-full blur-2xl pointer-events-none"></div>

        <div class="w-20 h-20 bg-emerald-100 text-emerald-700 rounded-3xl flex items-center justify-center text-4xl mx-auto shadow-inner">
          ✓
        </div>

        <div>
          <span class="px-3.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider">
            Alhamdulillah Pendaftaran Diterima
          </span>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mt-3">
            Formulir PPDB Online Berhasil Dikirim!
          </h1>
          <p class="text-xs sm:text-sm text-slate-600 max-w-xl mx-auto mt-2 leading-relaxed">
            Terima kasih telah mempercayakan pendidikan ananda <strong>{{ submittedData.nama_lengkap }}</strong> kepada <strong>SIT Bina Insan Mandiri Purwokerto</strong>.
          </p>
        </div>

        <!-- Box Nomor Pendaftaran -->
        <div class="p-5 sm:p-6 bg-gradient-to-br from-emerald-50 to-teal-50/80 rounded-2xl border border-emerald-200 max-w-md mx-auto text-center space-y-2">
          <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nomor Registrasi Resmi:</div>
          <div class="text-2xl sm:text-3xl font-black font-mono text-emerald-900 tracking-wider">
            {{ submittedData.nomor_pendaftaran }}
          </div>
          <div class="flex items-center justify-center gap-2 pt-2">
            <button
              @click="copyRegistrationNumber"
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white hover:bg-emerald-100 text-emerald-800 text-xs font-bold transition border border-emerald-300 shadow-xs cursor-pointer"
            >
              <span>📋</span> Salin Nomor
            </button>
            <span class="text-xs text-slate-400">•</span>
            <span class="text-xs font-semibold text-emerald-700">Jenjang: {{ submittedData.jenjang }}</span>
          </div>
        </div>

        <!-- Tombol Aksi WhatsApp & Cetak -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
          <button
            @click="openWhatsAppConfirmation"
            type="button"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-lg shadow-emerald-600/30 transition hover:-translate-y-0.5 cursor-pointer"
          >
            <span>💬</span> Konfirmasi via WhatsApp Panitia
          </button>

          <button
            @click="printRegistrationSlip"
            type="button"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-white hover:bg-slate-100 text-slate-800 font-bold text-sm border border-slate-300 shadow-xs transition cursor-pointer"
          >
            <span>🖨️</span> Cetak / Simpan Bukti (PDF)
          </button>
        </div>

        <div class="pt-6 border-t border-slate-100 text-xs text-slate-500 text-center">
          <p>Panitia PPDB akan segera menghubungi Anda melalui WhatsApp untuk jadwal observasi fitrah ananda.</p>
          <router-link to="/" class="mt-2 inline-block font-bold text-emerald-700 hover:underline">
            &larr; Kembali ke Beranda
          </router-link>
        </div>
      </div>
    </div>

    <!-- FORMULIR INPUT PPDB ONLINE (NON-PRINT) -->
    <div v-else class="max-w-4xl mx-auto px-4 pt-10 sm:pt-14 print:hidden space-y-8">
      
      <!-- Header Dokumen Formulir Sesuai PDF -->
      <div class="bg-white rounded-3xl p-6 sm:p-10 shadow-sm border border-slate-200/90 relative overflow-hidden">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 border-b border-slate-100 pb-6 mb-6">
          <div class="flex items-center gap-4">
            <img 
              src="/logo.png" 
              alt="Logo BIM" 
              class="w-16 h-16 sm:w-20 sm:h-20 object-contain drop-shadow-sm shrink-0" 
            />
            <div>
              <span class="px-3 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase tracking-wider">
                PPDB Terpadu Online
              </span>
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 mt-1 leading-snug">
                Sistem Penerimaan Murid Baru (SPMB / PPDB)
              </h1>
              <p class="text-xs sm:text-sm font-bold text-emerald-700">
                Sekolah Islam Terpadu Bina Insan Mandiri Purwokerto
              </p>
              <p class="text-[11px] font-medium text-slate-500 mt-0.5">
                Tahun Ajaran 2026/2027 • Jenjang PAUD Terpadu (KB & TK) &amp; SDIT
              </p>
            </div>
          </div>
        </div>

        <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <span class="text-base">📍</span>
            <span><strong>Sekretariat:</strong> Jl. M. Yamin RT 02 RW 03 No. 09 Karangklesem, Purwokerto Selatan 53144</span>
          </div>
          <div class="font-mono text-emerald-800 font-bold shrink-0">
            📞 0852-5072-5032
          </div>
        </div>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-8">

        <!-- SECTION 1: PILIHAN JENJANG PENDIDIKAN -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/90 space-y-4">
          <div class="flex items-center gap-2 text-emerald-800 font-bold text-sm sm:text-base border-b border-slate-100 pb-3">
            <span>🎓</span>
            <h2>1. Jenjang Pendidikan Yang Dipilih *</h2>
          </div>

          <p class="text-xs text-slate-500">Pilihlah jenjang pendidikan yang sesuai dengan usia dan kesiapan ananda:</p>

          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3.5">
            <label 
              class="p-4 rounded-2xl border-2 cursor-pointer transition flex flex-col justify-between"
              :class="form.jenjang === 'KB' ? 'border-emerald-600 bg-emerald-50/70 shadow-sm' : 'border-slate-200 hover:border-slate-300 bg-white'"
            >
              <div class="flex items-center justify-between mb-2">
                <span class="font-bold text-sm text-slate-900">KB</span>
                <input type="radio" v-model="form.jenjang" value="KB" class="text-emerald-600 focus:ring-emerald-500" />
              </div>
              <div class="text-xs text-emerald-800 font-semibold">3 Tahun - 4 Tahun</div>
              <div class="text-[10px] text-slate-400 mt-1">Kelompok Bermain</div>
            </label>

            <label 
              class="p-4 rounded-2xl border-2 cursor-pointer transition flex flex-col justify-between"
              :class="form.jenjang === 'TK A' ? 'border-emerald-600 bg-emerald-50/70 shadow-sm' : 'border-slate-200 hover:border-slate-300 bg-white'"
            >
              <div class="flex items-center justify-between mb-2">
                <span class="font-bold text-sm text-slate-900">TK A</span>
                <input type="radio" v-model="form.jenjang" value="TK A" class="text-emerald-600 focus:ring-emerald-500" />
              </div>
              <div class="text-xs text-emerald-800 font-semibold">4 Tahun - 5 Tahun</div>
              <div class="text-[10px] text-slate-400 mt-1">Taman Kanak-kanak A</div>
            </label>

            <label 
              class="p-4 rounded-2xl border-2 cursor-pointer transition flex flex-col justify-between"
              :class="form.jenjang === 'TK B' ? 'border-emerald-600 bg-emerald-50/70 shadow-sm' : 'border-slate-200 hover:border-slate-300 bg-white'"
            >
              <div class="flex items-center justify-between mb-2">
                <span class="font-bold text-sm text-slate-900">TK B</span>
                <input type="radio" v-model="form.jenjang" value="TK B" class="text-emerald-600 focus:ring-emerald-500" />
              </div>
              <div class="text-xs text-emerald-800 font-semibold">5 Tahun - 6 Tahun</div>
              <div class="text-[10px] text-slate-400 mt-1">Taman Kanak-kanak B</div>
            </label>

            <label 
              class="p-4 rounded-2xl border-2 cursor-pointer transition flex flex-col justify-between"
              :class="form.jenjang === 'SD' ? 'border-emerald-600 bg-emerald-50/70 shadow-sm' : 'border-slate-200 hover:border-slate-300 bg-white'"
            >
              <div class="flex items-center justify-between mb-2">
                <span class="font-bold text-sm text-slate-900">SD</span>
                <input type="radio" v-model="form.jenjang" value="SD" class="text-emerald-600 focus:ring-emerald-500" />
              </div>
              <div class="text-xs text-emerald-800 font-semibold">Minimal 6 Tahun</div>
              <div class="text-[10px] text-slate-400 mt-1">Sekolah Dasar (Kelas 1)</div>
            </label>
          </div>
        </div>

        <!-- SECTION 2: IDENTITAS CALON PESERTA DIDIK -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/90 space-y-5">
          <div class="flex items-center gap-2 text-emerald-800 font-bold text-sm sm:text-base border-b border-slate-100 pb-3">
            <span>🧒</span>
            <h2>2. Identitas Calon Peserta Didik</h2>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Siswa *</label>
              <input 
                v-model="form.nama_lengkap" 
                type="text" 
                required 
                placeholder="Contoh: AGHA BIMA EKADANTA"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Panggilan *</label>
              <input 
                v-model="form.nama_panggilan" 
                type="text" 
                required 
                placeholder="Contoh: AGHA"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Kelamin *</label>
              <div class="flex items-center gap-3 pt-1">
                <label class="flex items-center gap-2 text-xs font-medium text-slate-800 cursor-pointer">
                  <input type="radio" v-model="form.jenis_kelamin" value="L" class="text-emerald-600 focus:ring-emerald-500" />
                  <span>Laki - Laki</span>
                </label>
                <label class="flex items-center gap-2 text-xs font-medium text-slate-800 cursor-pointer">
                  <input type="radio" v-model="form.jenis_kelamin" value="P" class="text-emerald-600 focus:ring-emerald-500" />
                  <span>Perempuan</span>
                </label>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Tempat Lahir *</label>
              <input 
                v-model="form.tempat_lahir" 
                type="text" 
                required 
                placeholder="Contoh: BANYUMAS"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Lahir *</label>
              <input 
                v-model="form.tanggal_lahir" 
                type="date" 
                required 
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Usia Ketika Mendaftar</label>
              <input 
                v-model="form.usia_daftar" 
                type="text" 
                placeholder="Terhitung otomatis (misal: 5 tahun 7 bulan)"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-800 bg-slate-50 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Anak Ke</label>
              <input 
                v-model.number="form.anak_ke" 
                type="number" 
                min="1" 
                placeholder="1"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Saudara Kandung</label>
              <input 
                v-model.number="form.jumlah_saudara" 
                type="number" 
                min="0" 
                placeholder="1"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Lengkap Tempat Tinggal *</label>
            <textarea 
              v-model="form.alamat_lengkap" 
              @blur="copyAlamatToParents"
              rows="2" 
              required 
              placeholder="Contoh: Griya Satria Bukit Permata Blok 10 No 6 Desa Sidabowa Kecamatan Patikraja Kabupaten Banyumas"
              class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
            ></textarea>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Bertempat Tinggal Dengan *</label>
              <select 
                v-model="form.tinggal_dengan"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden bg-white"
              >
                <option value="Ayah & Ibu">Ayah & Ibu</option>
                <option value="Ibu">Ibu</option>
                <option value="Ayah">Ayah</option>
                <option value="Kakek & Nenek">Kakek & Nenek</option>
                <option value="Bibi">Bibi</option>
                <option value="Uwa">Uwa</option>
                <option value="Wali">Wali / Lainnya</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Asal Sekolah Sebelumnya</label>
              <input 
                v-model="form.asal_sekolah" 
                type="text" 
                placeholder="Contoh: TK Lukmanul Hakim / PAUD Nurul Iman"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Asal Sekolah</label>
            <input 
              v-model="form.alamat_asal_sekolah" 
              type="text" 
              placeholder="Contoh: JL HOS Notosuwiryo No 5 Kelurahan Teluk Kecamatan Purwokerto Selatan"
              class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
            />
          </div>

          <!-- Opsi Siswa Pindahan -->
          <div class="pt-3 border-t border-slate-100">
            <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-800">
              <input type="checkbox" v-model="form.is_pindahan" class="rounded text-emerald-600 focus:ring-emerald-500" />
              <span>Centang jika ananda merupakan Siswa Pindahan / Mutasi</span>
            </label>

            <div v-if="form.is_pindahan" class="mt-3 p-4 bg-emerald-50/50 rounded-2xl border border-emerald-200 space-y-3">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1">Pindah ke Kelas</label>
                  <input 
                    v-model="form.pindah_ke_kelas" 
                    type="text" 
                    placeholder="Contoh: Kelas 2 SD / Kelas 3 SD"
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs sm:text-sm bg-white"
                  />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Pindah</label>
                  <input 
                    v-model="form.alasan_pindah" 
                    type="text" 
                    placeholder="Contoh: Mengikuti domisili orang tua"
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs sm:text-sm bg-white"
                  />
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- SECTION 3: DATA AYAH -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/90 space-y-5">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2 text-emerald-800 font-bold text-sm sm:text-base">
              <span>👨</span>
              <h2>3. Data Ayah Kandung</h2>
            </div>
            <span class="text-[11px] text-slate-400">Dimohon mengisi sesuai Kartu Keluarga</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Ayah *</label>
              <input 
                v-model="form.nama_ayah" 
                type="text" 
                required 
                placeholder="Contoh: ANGGITA FITRIANTO"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Tempat dan Tanggal Lahir Ayah</label>
              <input 
                v-model="form.ttl_ayah" 
                type="text" 
                placeholder="Contoh: Banyumas, 6 April 1992"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Pendidikan Ayah</label>
              <input 
                v-model="form.pendidikan_ayah" 
                type="text" 
                placeholder="Contoh: S1 / SMA"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Pekerjaan Ayah</label>
              <input 
                v-model="form.pekerjaan_ayah" 
                type="text" 
                placeholder="Contoh: PNS / Wiraswasta / Karyawan"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Penghasilan per-Bulan *</label>
              <select 
                v-model="form.penghasilan_ayah"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden bg-white"
              >
                <option value="Rp > 2.000.000">Rp &gt; 2.000.000</option>
                <option value="Rp 1.000.000 - Rp 2.000.000">Rp 1.000.000 - Rp 2.000.000</option>
                <option value="Rp < 1.000.000">Rp &lt; 1.000.000</option>
                <option value="Rp > 5.000.000">Rp &gt; 5.000.000</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Lengkap Ayah</label>
              <input 
                v-model="form.alamat_ayah" 
                type="text" 
                placeholder="Alamat domisili ayah (kosongkan jika sama dengan siswa)"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor HP / WhatsApp Ayah *</label>
              <input 
                v-model="form.no_hp_ayah" 
                type="tel" 
                required 
                placeholder="Contoh: 085726226262"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden font-mono"
              />
            </div>
          </div>

        </div>

        <!-- SECTION 4: DATA IBU -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/90 space-y-5">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2 text-emerald-800 font-bold text-sm sm:text-base">
              <span>🧕</span>
              <h2>4. Data Ibu Kandung</h2>
            </div>
            <span class="text-[11px] text-slate-400">Dimohon mengisi sesuai Kartu Keluarga</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Ibu *</label>
              <input 
                v-model="form.nama_ibu" 
                type="text" 
                required 
                placeholder="Contoh: RETNO ANGGRAENI"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Tempat dan Tanggal Lahir Ibu</label>
              <input 
                v-model="form.ttl_ibu" 
                type="text" 
                placeholder="Contoh: Purwokerto,Banyumas, 21 Mei 1992"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Pendidikan Ibu</label>
              <input 
                v-model="form.pendidikan_ibu" 
                type="text" 
                placeholder="Contoh: S1 / D3"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Pekerjaan Ibu</label>
              <input 
                v-model="form.pekerjaan_ibu" 
                type="text" 
                placeholder="Contoh: Mengurus Rumah Tangga / Guru / Bidan"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Penghasilan per-Bulan (Opsional)</label>
              <input 
                v-model="form.penghasilan_ibu" 
                type="text" 
                placeholder="Contoh: - atau Rp 2.000.000"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Lengkap Ibu</label>
              <input 
                v-model="form.alamat_ibu" 
                type="text" 
                placeholder="Alamat domisili ibu (kosongkan jika sama dengan siswa)"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">No. Telepon / HP / WhatsApp Ibu *</label>
              <input 
                v-model="form.no_hp_ibu" 
                type="tel" 
                required 
                placeholder="Contoh: 085697383695"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden font-mono"
              />
            </div>
          </div>

        </div>

        <!-- SECTION 5: SYARAT PENDAFTARAN & LAMPIRAN BERKAS -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/90 space-y-5">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2 text-emerald-800 font-bold text-sm sm:text-base">
              <span>📎</span>
              <h2>5. Syarat Pendaftaran / Unggah Berkas</h2>
            </div>
          </div>

          <div class="p-3.5 bg-amber-50 rounded-2xl border border-amber-200 text-xs text-amber-900 flex items-start gap-2">
            <span class="text-base shrink-0">💡</span>
            <span>Mohon sertakan scan atau foto yang jelas untuk <strong>Akta Kelahiran</strong>, <strong>Kartu Keluarga</strong>, dan <strong>Pas Foto 3x4 (berwarna)</strong>. Berkas juga dapat disusulkan saat konfirmasi atau observasi langsung.</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <!-- 1. Pas Foto 3x4 -->
            <div class="space-y-2">
              <label class="block text-xs font-semibold text-slate-700">Pas Foto Calon Siswa (3x4)</label>
              <div 
                @click="pasFotoInput?.click()"
                class="border-2 border-dashed border-emerald-300 hover:border-emerald-500 bg-emerald-50/30 rounded-2xl p-4 flex flex-col items-center justify-center cursor-pointer min-h-[170px] text-center transition group relative overflow-hidden"
              >
                <img 
                  v-if="pasFotoPreview" 
                  :src="pasFotoPreview" 
                  alt="Pas Foto" 
                  class="w-24 h-32 object-cover rounded-xl shadow-md border border-white"
                />
                <div v-else class="space-y-1">
                  <span class="text-3xl block mb-1">📸</span>
                  <p class="text-xs font-bold text-emerald-800">Pilih Pas Foto</p>
                  <p class="text-[10px] text-slate-400">JPG, PNG, WebP (Maks 10MB)</p>
                </div>
              </div>
              <input 
                ref="pasFotoInput" 
                type="file" 
                accept="image/*" 
                class="hidden" 
                @change="onPasFotoChange" 
              />
              <div v-if="pasFotoInfo" class="text-[10px] text-emerald-700 font-medium truncate">
                ⚡ {{ pasFotoInfo }}
              </div>
            </div>

            <!-- 2. Akta Kelahiran -->
            <div class="space-y-2">
              <label class="block text-xs font-semibold text-slate-700">Scan Akta Kelahiran</label>
              <div 
                @click="aktaInput?.click()"
                class="border-2 border-dashed border-slate-300 hover:border-emerald-500 bg-slate-50 hover:bg-emerald-50/30 rounded-2xl p-4 flex flex-col items-center justify-center cursor-pointer min-h-[170px] text-center transition group relative"
              >
                <div v-if="aktaFileName" class="space-y-1">
                  <span class="text-3xl block text-emerald-600 mb-1">📄</span>
                  <p class="text-xs font-bold text-emerald-800 break-all line-clamp-2">{{ aktaFileName }}</p>
                  <p class="text-[10px] text-emerald-600">✓ Berkas terpilih</p>
                </div>
                <div v-else class="space-y-1">
                  <span class="text-3xl block mb-1">📜</span>
                  <p class="text-xs font-bold text-slate-700">Pilih File Akta</p>
                  <p class="text-[10px] text-slate-400">Foto (JPG/PNG) atau Dokumen PDF</p>
                </div>
              </div>
              <input 
                ref="aktaInput" 
                type="file" 
                accept="image/*,application/pdf" 
                class="hidden" 
                @change="onAktaChange" 
              />
            </div>

            <!-- 3. Kartu Keluarga -->
            <div class="space-y-2">
              <label class="block text-xs font-semibold text-slate-700">Scan Kartu Keluarga (KK)</label>
              <div 
                @click="kkInput?.click()"
                class="border-2 border-dashed border-slate-300 hover:border-emerald-500 bg-slate-50 hover:bg-emerald-50/30 rounded-2xl p-4 flex flex-col items-center justify-center cursor-pointer min-h-[170px] text-center transition group relative"
              >
                <div v-if="kkFileName" class="space-y-1">
                  <span class="text-3xl block text-emerald-600 mb-1">👨‍👩‍👧‍👦</span>
                  <p class="text-xs font-bold text-emerald-800 break-all line-clamp-2">{{ kkFileName }}</p>
                  <p class="text-[10px] text-emerald-600">✓ Berkas terpilih</p>
                </div>
                <div v-else class="space-y-1">
                  <span class="text-3xl block mb-1">📑</span>
                  <p class="text-xs font-bold text-slate-700">Pilih File KK</p>
                  <p class="text-[10px] text-slate-400">Foto (JPG/PNG) atau Dokumen PDF</p>
                </div>
              </div>
              <input 
                ref="kkInput" 
                type="file" 
                accept="image/*,application/pdf" 
                class="hidden" 
                @change="onKkChange" 
              />
            </div>
          </div>

        </div>

        <!-- Tombol Submit Akhir -->
        <div class="bg-gradient-to-r from-emerald-800 via-teal-800 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col sm:flex-row items-center justify-between gap-5">
          <div class="space-y-1 text-center sm:text-left">
            <h3 class="text-base sm:text-lg font-black text-white">Pastikan Data Terisi Dengan Benar</h3>
            <p class="text-xs text-emerald-100/90 leading-relaxed">
              Setelah pendaftaran terkirim, Anda akan mendapatkan Nomor Registrasi resmi untuk konfirmasi dan observasi fitrah.
            </p>
          </div>

          <button
            type="submit"
            :disabled="submitting"
            v-splash="'amber'"
            class="btn-splash btn-sheen w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black text-sm sm:text-base shadow-xl shadow-amber-500/20 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 shrink-0"
          >
            <span v-if="submitting" class="w-4 h-4 border-2 border-slate-950 border-t-transparent rounded-full animate-spin"></span>
            <span>{{ submitting ? 'Mengirim Data...' : 'Kirim Formulir PPDB Online' }}</span>
            <span v-if="!submitting">&rarr;</span>
          </button>
        </div>

      </form>
    </div>

    <LandingFooter />
  </div>
</template>

<style scoped>
@media print {
  body {
    background: white !important;
  }
}
</style>

