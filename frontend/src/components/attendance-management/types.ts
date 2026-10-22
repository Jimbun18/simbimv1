export interface SummaryStats {
  total_record: number
  hadir: number
  izin: number
  sakit: number
  alpa: number
  persentase_kehadiran: number
}

export interface StudentPagination {
  current_page: number
  last_page: number
  total: number
  per_page: number
}

export const getPhotoUrl = (path?: string | null): string => {
  if (!path) return ''
  return path.startsWith('http') ? path : `/storage/${path}`
}

export const getInitials = (name?: string): string => {
  if (!name) return '?'
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
}
