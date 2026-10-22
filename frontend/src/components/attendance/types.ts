export const GOOGLE_MAPS_URL = 'https://maps.app.goo.gl/2DjGnYuHWWaXNLX69'

export interface TeacherAttendance {
  id: number
  teacher_id: number
  tanggal: string
  jam_masuk?: string | null
  jam_keluar?: string | null
  status: 'hadir' | 'terlambat' | 'izin' | 'sakit' | 'alpa' | 'dinas' | string
  is_terlambat?: boolean
  menit_keterlambatan?: number
  keterangan?: string | null
  foto_masuk_url?: string | null
  foto_keluar_url?: string | null
  lat_masuk?: number | null
  lng_masuk?: number | null
  lat_keluar?: number | null
  lng_keluar?: number | null
}

export interface SchoolConfig {
  latitude: number | string
  longitude: number | string
  radius_geofence_m: number | string
  jam_masuk_default?: string
  jam_pulang_default?: string
  toleransi_menit?: number
}

export interface LeaveForm {
  status: 'izin' | 'sakit' | 'dinas'
  tanggal_mulai: string
  tanggal_selesai: string
  keterangan: string
}

export const formatTimeDisplay = (timeStr: string | null | undefined): string => {
  if (!timeStr) return '--:--'
  const clean = timeStr.substring(0, 5)
  return `${clean} WIB`
}

export const formatSpacedTime = (timeStr: string | null | undefined): string => {
  if (!timeStr) return '--  :  --'
  const clean = timeStr.substring(0, 5)
  return clean.replace(':', '  :  ')
}

export const formatKeteranganDisplay = (h: any): string => {
  if (!h) return '-'
  if (h.keterangan) {
    if (h.keterangan.includes('Terlambat') && !h.keterangan.includes('toleransi')) {
      return `${h.keterangan} dari toleransi`
    }
    return h.keterangan
  }
  if (h.is_terlambat && h.menit_keterlambatan) {
    return `Terlambat ${h.menit_keterlambatan} menit dari toleransi`
  }
  if (h.status === 'hadir') return 'Tepat waktu'
  return '-'
}

export const formatDateDisplay = (dateStr: string | null | undefined): string => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return dateStr
    return d.toLocaleDateString('id-ID', {
      weekday: 'short',
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    })
  } catch {
    return dateStr
  }
}

export const calculateDistance = (lat1: number, lon1: number, lat2: number, lon2: number): number => {
  const R = 6371e3
  const phi1 = (lat1 * Math.PI) / 180
  const phi2 = (lat2 * Math.PI) / 180
  const deltaPhi = ((lat2 - lat1) * Math.PI) / 180
  const deltaLambda = ((lon2 - lon1) * Math.PI) / 180

  const a = Math.sin(deltaPhi / 2) * Math.sin(deltaPhi / 2) +
            Math.cos(phi1) * Math.cos(phi2) *
            Math.sin(deltaLambda / 2) * Math.sin(deltaLambda / 2)
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))

  return Math.round(R * c)
}
