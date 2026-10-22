export interface TimeSlot {
  id: number
  nama: string
  jam_mulai: string
  jam_selesai: string
  urutan: number
  hari?: string
}

export interface SchoolClass {
  id: number
  nama: string
  kode: string
  tingkat: number
  active_students_count?: number
}

export interface Subject {
  id: number
  nama: string
  kode: string
  kelompok?: string
  is_tematik?: boolean
  tema_nomor?: number
  muatan_pelajaran?: string[]
}

export interface Teacher {
  id: number
  nama_lengkap: string
  nip?: string
}

export interface Room {
  id: number
  nama: string
  kode?: string
}

export interface ScheduleItem {
  id: number
  academic_year_id: number
  class_id: number
  subject_id: number
  teacher_id: number
  room_id: number | null
  hari: string
  time_slot_id: number
  status: string
  today_attendance_count: number
  today_evidence_count: number
  school_class?: SchoolClass
  subject?: Subject
  teacher?: Teacher
  room?: Room
  time_slot?: TimeSlot
}

export interface MergedScheduleItem extends ScheduleItem {
  schedule_ids: number[]
  durasi_jp: number
  durasi_menit_per_jp: number
  durasi_label: string
  jam_mulai_efektif: string
  jam_selesai_efektif: string
  sesi_label: string
  first_urutan: number
  last_urutan: number
  jam_selesai_raw: string
}

export const getIndonesianDayName = (dateStr: string): string => {
  const d = new Date(dateStr)
  const dayIndex = d.getDay()
  const days = ['minggu', 'senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu']
  return days[dayIndex] || 'senin'
}

export const isTodaySchedule = (sch: ScheduleItem | MergedScheduleItem, activeDateStr: string): boolean => {
  const todayName = getIndonesianDayName(activeDateStr)
  return sch.hari.toLowerCase() === todayName
}

export const getCardTheme = (sch: ScheduleItem | MergedScheduleItem, activeDateStr: string) => {
  if (isTodaySchedule(sch, activeDateStr)) {
    return {
      bg: 'bg-[#137333]', // Emerald Green
      text: 'text-white',
      badge: 'bg-[#0d5926] text-emerald-100 border-[#1b8e45]',
      accent: 'border-emerald-600',
      isToday: true,
      label: 'HARI INI',
    }
  } else {
    return {
      bg: 'bg-[#b3261e]', // Crimson Red
      text: 'text-white',
      badge: 'bg-[#8c1d18] text-red-100 border-[#d32f2f]',
      accent: 'border-red-600',
      isToday: false,
      label: 'BUKAN HARI INI',
    }
  }
}

export const formatTimeHM = (timeStr?: string): string => {
  if (!timeStr) return ''
  const parts = timeStr.split(':')
  if (parts.length >= 2) {
    return `${parts[0]}:${parts[1]}`
  }
  return timeStr
}

export const calcMinutes = (startStr: string, endStr: string): number => {
  const [h1, m1] = startStr.split(':').map(Number)
  const [h2, m2] = endStr.split(':').map(Number)
  return (h2 * 60 + m2) - (h1 * 60 + m1)
}

export const createMergedItem = (item: ScheduleItem): MergedScheduleItem => {
  const urutan = item.time_slot?.urutan ?? 1
  const jamMulaiRaw = item.time_slot?.jam_mulai || '07:30'
  const jamSelesaiRaw = item.time_slot?.jam_selesai || '08:05'

  let slotMin = 35
  try {
    const diff = calcMinutes(jamMulaiRaw, jamSelesaiRaw)
    if (diff > 0 && diff < 180) slotMin = diff
  } catch {
    slotMin = 35
  }

  return {
    ...item,
    schedule_ids: [item.id],
    durasi_jp: 1,
    durasi_menit_per_jp: slotMin,
    durasi_label: `1 JP (1x${slotMin}m)`,
    first_urutan: urutan,
    last_urutan: urutan,
    jam_selesai_raw: jamSelesaiRaw,
    jam_mulai_efektif: formatTimeHM(jamMulaiRaw),
    jam_selesai_efektif: formatTimeHM(jamSelesaiRaw),
    sesi_label: item.time_slot?.nama || `Sesi ${urutan} (JP ${urutan})`,
  }
}

export const mergeConsecutiveSchedules = (items: ScheduleItem[]): MergedScheduleItem[] => {
  if (!items || items.length === 0) return []

  const dayOrder: Record<string, number> = {
    senin: 1, selasa: 2, rabu: 3, kamis: 4, jumat: 5, sabtu: 6, minggu: 7
  }

  const sorted = [...items].sort((a, b) => {
    const dA = dayOrder[a.hari.toLowerCase()] || 99
    const dB = dayOrder[b.hari.toLowerCase()] || 99
    if (dA !== dB) return dA - dB

    if (a.teacher_id !== b.teacher_id) return a.teacher_id - b.teacher_id
    if (a.class_id !== b.class_id) return a.class_id - b.class_id
    if (a.subject_id !== b.subject_id) return a.subject_id - b.subject_id
    const rA = a.room_id || 0
    const rB = b.room_id || 0
    if (rA !== rB) return rA - rB

    const uA = a.time_slot?.urutan ?? 0
    const uB = b.time_slot?.urutan ?? 0
    if (uA !== uB) return uA - uB

    return (a.time_slot?.jam_mulai || '').localeCompare(b.time_slot?.jam_mulai || '')
  })

  const merged: MergedScheduleItem[] = []

  for (const item of sorted) {
    if (merged.length === 0) {
      merged.push(createMergedItem(item))
      continue
    }

    const last = merged[merged.length - 1]

    const isSameContext =
      last.hari.toLowerCase() === item.hari.toLowerCase() &&
      last.teacher_id === item.teacher_id &&
      last.class_id === item.class_id &&
      last.subject_id === item.subject_id &&
      (last.room_id || 0) === (item.room_id || 0)

    const isConsecutive =
      isSameContext &&
      ((item.time_slot?.urutan != null && last.last_urutan != null && item.time_slot.urutan === last.last_urutan + 1) ||
       (item.time_slot?.jam_mulai && last.jam_selesai_raw && item.time_slot.jam_mulai.substring(0, 5) === last.jam_selesai_raw.substring(0, 5)))

    if (isConsecutive) {
      last.schedule_ids.push(item.id)
      last.durasi_jp += 1
      last.last_urutan = item.time_slot?.urutan ?? (last.last_urutan + 1)

      if (item.time_slot?.jam_selesai) {
        last.jam_selesai_raw = item.time_slot.jam_selesai
        last.jam_selesai_efektif = formatTimeHM(item.time_slot.jam_selesai)
      }

      if ((item as any).today_evidence_count > 0) {
        last.today_evidence_count = Math.max(last.today_evidence_count, (item as any).today_evidence_count)
      }

      const slotMin = last.durasi_menit_per_jp || 35
      last.durasi_label = `${last.durasi_jp} JP (${last.durasi_jp}x${slotMin}m)`
      last.sesi_label = `Sesi ${last.first_urutan} - ${last.last_urutan} (${last.durasi_jp} JP)`
    } else {
      merged.push(createMergedItem(item))
    }
  }

  return merged
}

export const getInitials = (name?: string): string => {
  if (!name) return '?'
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
}

export const getPhotoUrl = (path?: string | null): string => {
  if (!path) return ''
  return path.startsWith('http') ? path : `/storage/${path}`
}

export interface HomeroomStudent {
  student_id: number
  nama_lengkap: string
  nis?: string
  jenis_kelamin: string
  foto_path?: string | null
  status?: 'hadir' | 'izin' | 'sakit' | 'alpa'
  keterangan?: string
}

export interface HomeroomMeta {
  already_recorded: boolean
  recorded_count?: number
  recorded_at?: string
  updated_at?: string
  recorded_by?: string
  summary?: any
}
