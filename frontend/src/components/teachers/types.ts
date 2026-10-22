import { calculateTenure, formatDateIndo, toInputDate } from '../../utils/date'

export interface Teacher {
  id: number
  nik: string
  nip?: string | null
  nuptk?: string | null
  nama_lengkap: string
  jenis_kelamin: 'L' | 'P'
  tempat_lahir?: string
  tgl_lahir?: string
  agama?: number | string
  alamat_jalan?: string
  kab_kota?: string
  no_hp: string
  email?: string | null
  jenis_ptk: 'guru_kelas' | 'guru_mapel' | 'guru_agama' | 'guru_olahraga' | 'kepala_sekolah' | string
  jabatan?: string | null
  tugas_tambahan?: string | null
  status_kepegawaian: 'gty' | 'gtt' | 'pns' | 'pppk' | 'honorer' | string
  status: 'aktif' | 'cuti' | 'nonaktif' | 'pensiun' | string
  tgl_mulai_tugas?: string
  foto_url?: string | null
  foto_path?: string | null
  pendidikan_terakhir?: string | null
  jurusan_prodi?: string | null
  status_sertifikasi?: string | null
  jam_mengajar_per_minggu?: number | null
  education?: {
    nama: string
  }
  user?: {
    id: number
    username: string
    email?: string | null
    status?: string
  }
  homeroom_classes?: Array<{
    id: number
    nama: string
    tingkat: number
  }>
  assignments?: Array<{
    id: number
    subject?: {
      id: number
      nama: string
      kode: string
    }
    class?: {
      id: number
      nama: string
    }
  }>
}

export interface TeacherFormData {
  nik: string
  nip: string
  nama_lengkap: string
  jenis_kelamin: 'L' | 'P'
  tempat_lahir: string
  tgl_lahir: string
  agama: number | string
  alamat_jalan: string
  kab_kota: string
  no_hp: string
  email: string
  jenis_ptk: string
  jabatan: string
  tugas_tambahan: string
  status_kepegawaian: string
  status: string
  tgl_mulai_tugas: string
  create_user_account?: boolean
}

export const getPhotoUrl = (url?: string | null, path?: string | null): string => {
  if (url) return url
  if (path) {
    return path.startsWith('http') ? path : `/storage/${path}`
  }
  return ''
}

export const getInitials = (name?: string): string => {
  if (!name) return '?'
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
}

export { calculateTenure, formatDateIndo, toInputDate }
