export interface FeeType {
  id: number
  kode: string
  nama: string
  nominal: number
  periode: 'bulanan' | 'semester' | 'tahunan' | 'sekali'
  is_aktif: boolean
  keterangan?: string
}

export interface StudentReference {
  id: number
  nama_lengkap: string
  nis?: string
  nisn?: string
  kelas?: string
  no_hp_siswa?: string
  telepon?: string
  school_class?: {
    id: number
    nama: string
    tingkat: number
  }
  guardians?: any[]
}

export interface Invoice {
  id: number
  no_tagihan: string
  student_id: number
  fee_type_id: number
  academic_year_id: number
  periode: string
  jumlah: number
  jumlah_dibayar: number
  sisa: number
  status: 'belum_bayar' | 'sebagian' | 'lunas' | 'jatuh_tempo'
  jatuh_tempo?: string
  student?: StudentReference
  fee_type?: FeeType
  payments?: Payment[]
}

export interface Payment {
  id: number
  invoice_id: number
  no_kuitansi: string
  tanggal: string
  jumlah: number
  metode: string
  catatan?: string
  is_batal: boolean
  invoice?: Invoice
  recorder?: {
    id: number
    nama: string
  }
}

export interface CashTransaction {
  id: number
  no_transaksi: string
  tanggal: string
  tipe: 'masuk' | 'keluar'
  kategori: string
  keterangan: string
  jumlah: number
  recorder?: {
    id: number
    nama: string
  }
}

export interface ReceiptData {
  id: number
  no_kuitansi: string
  no_tagihan: string
  tanggal: string
  student_name: string
  student_nis?: string
  class_name?: string
  fee_name: string
  period: string
  jumlah: number
  metode: string
  sisa_tagihan: number
  status_tagihan: string
  catatan?: string
  recorded_by?: string
  terbilang: string
}

export interface FinanceSummary {
  total_invoiced: number
  total_paid: number
  total_remaining: number
  cash_balance: number
}

export const formatRupiah = (val: number | string | null | undefined): string => {
  const num = Number(val) || 0
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num)
}

export const getGuardianPhone = (inv: any): { phone: string; name: string } => {
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

export const formatPhoneForWA = (raw: string): string => {
  let cleaned = (raw || '').replace(/\D/g, '')
  if (cleaned.startsWith('0')) {
    cleaned = '62' + cleaned.substring(1)
  } else if (cleaned.startsWith('8')) {
    cleaned = '62' + cleaned
  }
  return cleaned
}

export const generateWAMessage = (inv: any, guardianName: string): string => {
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
