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

export type ScheduleTimeStatus =
  | 'bukan_hari_ini'
  | 'belum_mulai'
  | 'sedang_berlangsung'
  | 'sudah_selesai'

export interface ScheduleStatusInfo {
  status: ScheduleTimeStatus
  badgeText: string
  badgeClass: string
  canUpload: boolean
  canView: boolean
  buttonText: string
  buttonClass: string
  disabledReason?: string
}

export const getScheduleTimeStatus = (
  sch: MergedScheduleItem | ScheduleItem,
  activeDateStr: string,
  refDate: Date = new Date(),
  toleranceMinutesAfter: number = 5
): ScheduleTimeStatus => {
  if (!isTodaySchedule(sch, activeDateStr)) {
    return 'bukan_hari_ini'
  }

  const hours = String(refDate.getHours()).padStart(2, '0')
  const minutes = String(refDate.getMinutes()).padStart(2, '0')
  const currentTime = `${hours}:${minutes}`

  const jamMulai = (sch as MergedScheduleItem).jam_mulai_efektif || formatTimeHM(sch.time_slot?.jam_mulai) || '00:00'
  const jamSelesai = (sch as MergedScheduleItem).jam_selesai_efektif || formatTimeHM(sch.time_slot?.jam_selesai) || '23:59'

  if (currentTime < jamMulai) {
    return 'belum_mulai'
  }

  // Toleransi menit setelah jam selesai sebelum ditutup total
  let limitSelesai = jamSelesai
  if (toleranceMinutesAfter > 0) {
    const parts = jamSelesai.split(':').map(Number)
    if (parts.length >= 2) {
      const totalM = parts[0] * 60 + parts[1] + toleranceMinutesAfter
      const h = Math.floor(totalM / 60) % 24
      const m = totalM % 60
      limitSelesai = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`
    }
  }

  if (currentTime > limitSelesai) {
    return 'sudah_selesai'
  }

  return 'sedang_berlangsung'
}

export const getScheduleStatusInfo = (
  sch: MergedScheduleItem,
  activeDateStr: string,
  refDate: Date = new Date()
): ScheduleStatusInfo => {
  const timeStatus = getScheduleTimeStatus(sch, activeDateStr, refDate)
  const hasEvidence = ((sch as any).today_evidence_count ?? 0) > 0
  const jamMulai = sch.jam_mulai_efektif || '07:30'
  const jamSelesai = sch.jam_selesai_efektif || '08:05'

  if (timeStatus === 'bukan_hari_ini') {
    return {
      status: 'bukan_hari_ini',
      badgeText: 'BUKAN HARI INI',
      badgeClass: 'bg-black/30 backdrop-blur-xs text-[10px] font-bold uppercase tracking-wider rounded-full border border-white/20 text-red-100 shrink-0 shadow-xs',
      canUpload: false,
      canView: hasEvidence,
      buttonText: hasEvidence ? 'Lihat Bukti KBM Terunggah' : `Hanya Dibuka Hari ${sch.hari.toUpperCase()}`,
      buttonClass: hasEvidence
        ? 'bg-slate-700 hover:bg-slate-800 text-white shadow-xs'
        : 'bg-slate-50 border border-slate-200 text-slate-400 font-semibold cursor-not-allowed',
      disabledReason: `Pengunggahan bukti KBM ditutup. Hanya dapat diakses pada hari ${sch.hari.toUpperCase()}.`,
    }
  }

  if (timeStatus === 'belum_mulai') {
    return {
      status: 'belum_mulai',
      badgeText: `BELUM DIMULAI (JAM ${jamMulai})`,
      badgeClass: 'bg-amber-400/90 text-slate-950 font-black text-[10px] uppercase tracking-wider rounded-full border border-amber-300 shrink-0 shadow-xs',
      canUpload: false,
      canView: false,
      buttonText: `Belum Masuk Jam Pelajaran (Mulai ${jamMulai} WIB)`,
      buttonClass: 'bg-amber-50/80 border border-amber-200 text-amber-800 font-bold cursor-not-allowed',
      disabledReason: `Jam pelajaran belum dimulai. Pengunggahan bukti KBM hanya dapat diakses saat jam pelajaran berlangsung (${jamMulai} - ${jamSelesai} WIB).`,
    }
  }

  if (timeStatus === 'sedang_berlangsung') {
    return {
      status: 'sedang_berlangsung',
      badgeText: '🟢 SEDANG BERLANGSUNG',
      badgeClass: 'bg-white text-emerald-800 font-extrabold text-[10px] uppercase tracking-wider rounded-full border border-emerald-200 shrink-0 shadow-md animate-pulse',
      canUpload: true,
      canView: true,
      buttonText: hasEvidence ? 'Lihat / Edit Bukti KBM & Jurnal' : '📸 Unggah Bukti KBM & Jurnal (Sedang Berlangsung)',
      buttonClass: hasEvidence
        ? 'bg-purple-600 hover:bg-purple-700 text-white shadow-xs shadow-purple-600/20 active:scale-[0.98]'
        : 'bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black shadow-md shadow-emerald-500/30 active:scale-[0.98]',
    }
  }

  // sudah_selesai
  return {
    status: 'sudah_selesai',
    badgeText: hasEvidence ? '✓ SELESAI (TERUNGGAH)' : '⏱️ SUDAH BERAKHIR',
    badgeClass: hasEvidence
      ? 'bg-white/20 text-white text-[10px] font-bold uppercase tracking-wider rounded-full border border-white/30 shrink-0 shadow-xs backdrop-blur-xs'
      : 'bg-white/20 text-white text-[10px] font-bold uppercase tracking-wider rounded-full border border-white/25 shrink-0 shadow-xs backdrop-blur-xs',
    canUpload: false,
    canView: hasEvidence,
    buttonText: hasEvidence
      ? 'Lihat Bukti KBM (Selesai)'
      : `Jam Pelajaran Telah Berakhir (Tutup ${jamSelesai} WIB)`,
    buttonClass: hasEvidence
      ? 'bg-slate-700 hover:bg-slate-800 text-white shadow-xs active:scale-[0.98]'
      : 'bg-slate-50 border border-slate-200 text-slate-400 font-semibold cursor-not-allowed',
    disabledReason: hasEvidence
      ? undefined
      : `Waktu pengunggahan telah berakhir. Pengunggahan bukti KBM dibatasi hanya saat jam pelajaran sedang berlangsung (${jamMulai} - ${jamSelesai} WIB).`,
  }
}

export const getCardTheme = (sch: ScheduleItem | MergedScheduleItem, activeDateStr: string, refDate?: Date) => {
  if (isTodaySchedule(sch, activeDateStr)) {
    const timeStatus = getScheduleTimeStatus(sch, activeDateStr, refDate)
    if (timeStatus === 'sedang_berlangsung') {
      return {
        bg: 'bg-emerald-500', // Bright Vibrant Emerald Green (Ongoing)
        text: 'text-white',
        badge: 'bg-white text-emerald-900 border-white',
        accent: 'border-emerald-400',
        isToday: true,
        label: 'SEDANG BERLANGSUNG',
      }
    } else if (timeStatus === 'belum_mulai') {
      return {
        bg: 'bg-emerald-500', // Bright Emerald Green (Upcoming Today)
        text: 'text-white',
        badge: 'bg-amber-300 text-slate-950 border-amber-400',
        accent: 'border-emerald-400',
        isToday: true,
        label: 'HARI INI (BELUM MULAI)',
      }
    } else {
      return {
        bg: 'bg-emerald-500', // Bright Emerald Green (Finished Today)
        text: 'text-white',
        badge: 'bg-white/20 text-white border-white/25',
        accent: 'border-emerald-400',
        isToday: true,
        label: 'HARI INI (SELESAI)',
      }
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

  let totalMinutes = 35
  try {
    const diff = calcMinutes(jamMulaiRaw, jamSelesaiRaw)
    if (diff > 0 && diff < 300) totalMinutes = diff
  } catch {
    totalMinutes = 35
  }

  // Hitung JP berdasarkan durasi menit standar SD (35 menit per JP)
  let durasiJp = 1
  if (totalMinutes >= 55 && totalMinutes <= 85) {
    durasiJp = 2
  } else if (totalMinutes >= 86 && totalMinutes <= 125) {
    durasiJp = 3
  } else if (totalMinutes >= 126 && totalMinutes <= 165) {
    durasiJp = 4
  } else if (totalMinutes > 165) {
    durasiJp = Math.max(1, Math.round(totalMinutes / 35))
  }

  // Deteksi jika nama slot menyebutkan Blok JP atau JP tertentu
  const nameMatch = item.time_slot?.nama?.match(/(?:Blok\s*)?(\d+)\s*JP/i)
  if (nameMatch && nameMatch[1]) {
    const parsedJp = parseInt(nameMatch[1], 10)
    if (!isNaN(parsedJp) && parsedJp > durasiJp) {
      durasiJp = parsedJp
    }
  }

  const slotMin = 35
  const durasiLabel = `${durasiJp} JP (${durasiJp}x${slotMin}m)`
  const sesiLabel = item.time_slot?.nama
    ? (durasiJp > 1 && !item.time_slot.nama.includes('JP')
        ? `${item.time_slot.nama} (${durasiJp} JP)`
        : item.time_slot.nama)
    : `Sesi ${urutan} (${durasiJp} JP)`

  return {
    ...item,
    schedule_ids: [item.id],
    durasi_jp: durasiJp,
    durasi_menit_per_jp: slotMin,
    durasi_label: durasiLabel,
    first_urutan: urutan,
    last_urutan: urutan,
    jam_selesai_raw: jamSelesaiRaw,
    jam_mulai_efektif: formatTimeHM(jamMulaiRaw),
    jam_selesai_efektif: formatTimeHM(jamSelesaiRaw),
    sesi_label: sesiLabel,
  }
}

export const mergeConsecutiveSchedules = (items: ScheduleItem[]): MergedScheduleItem[] => {
  if (!items || items.length === 0) return []

  const dayOrder: Record<string, number> = {
    senin: 1, selasa: 2, rabu: 3, kamis: 4, jumat: 5, sabtu: 6, minggu: 7
  }

  // Urutkan berdasarkan hari, guru, kelas, mapel, lalu jam/urutan slot
  const sorted = [...items].sort((a, b) => {
    const dA = dayOrder[a.hari.toLowerCase()] || 99
    const dB = dayOrder[b.hari.toLowerCase()] || 99
    if (dA !== dB) return dA - dB

    if (a.teacher_id !== b.teacher_id) return a.teacher_id - b.teacher_id
    if (a.class_id !== b.class_id) return a.class_id - b.class_id
    if (a.subject_id !== b.subject_id) return a.subject_id - b.subject_id

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

    // Konteks pembelajaran yang sama: Hari, Guru, Kelas, dan Mapel sama
    const isSameContext =
      last.hari.toLowerCase() === item.hari.toLowerCase() &&
      last.teacher_id === item.teacher_id &&
      last.class_id === item.class_id &&
      last.subject_id === item.subject_id

    const itemJamMulai = item.time_slot?.jam_mulai ? formatTimeHM(item.time_slot.jam_mulai) : ''
    const lastJamSelesai = last.jam_selesai_efektif

    const itemUrutan = item.time_slot?.urutan ?? 0
    const lastLastUrutan = last.last_urutan ?? 0

    // Kriteria gabung:
    // 1. Waktu mulai slot baru persis sama dengan waktu selesai slot sebelumnya
    // 2. Waktu slot baru bertabrakan/overlap dengan slot sebelumnya
    // 3. Urutan berurutan (urutan + 1) dengan jeda maksimal <= 10 menit (atau pergantian sesi langsung)
    const isAdjacentTime = Boolean(itemJamMulai && lastJamSelesai && itemJamMulai === lastJamSelesai)
    const isOverlappingTime = Boolean(
      item.time_slot?.jam_mulai &&
      last.jam_selesai_raw &&
      item.time_slot.jam_mulai.substring(0, 5) < last.jam_selesai_raw.substring(0, 5)
    )
    const isConsecutiveUrutan = Boolean(
      itemUrutan > 0 &&
      lastLastUrutan > 0 &&
      (itemUrutan === lastLastUrutan + 1 || itemUrutan === lastLastUrutan) &&
      (!itemJamMulai || !lastJamSelesai || calcMinutes(lastJamSelesai, itemJamMulai) <= 10)
    )

    const isConsecutive = isSameContext && (isAdjacentTime || isOverlappingTime || isConsecutiveUrutan)

    if (isConsecutive) {
      last.schedule_ids.push(item.id)
      last.last_urutan = Math.max(last.last_urutan, itemUrutan)
      last.first_urutan = Math.min(last.first_urutan, itemUrutan > 0 ? itemUrutan : last.first_urutan)

      // Perbarui jam selesai ke waktu paling akhir
      if (item.time_slot?.jam_selesai && item.time_slot.jam_selesai > last.jam_selesai_raw) {
        last.jam_selesai_raw = item.time_slot.jam_selesai
        last.jam_selesai_efektif = formatTimeHM(item.time_slot.jam_selesai)
      }

      // Perbarui ruangan jika sebelumnya null
      if (!last.room_id && item.room_id) {
        last.room_id = item.room_id
        last.room = item.room
      }

      if ((item as any).today_evidence_count > 0) {
        last.today_evidence_count = Math.max(last.today_evidence_count, (item as any).today_evidence_count)
      }

      // Hitung total menit dari awal sesi pertama sampai akhir sesi terakhir
      let totalMinutes = 70
      try {
        const diff = calcMinutes(last.jam_mulai_efektif, last.jam_selesai_efektif)
        if (diff > 0) totalMinutes = diff
      } catch {
        totalMinutes = (last.durasi_jp + 1) * 35
      }

      let calculatedJp = Math.round(totalMinutes / 35)
      if (calculatedJp < last.schedule_ids.length) {
        calculatedJp = last.schedule_ids.length
      }
      last.durasi_jp = Math.max(1, calculatedJp)

      const slotMin = 35
      last.durasi_label = `${last.durasi_jp} JP (${last.durasi_jp}x${slotMin}m)`
      if (last.first_urutan !== last.last_urutan) {
        last.sesi_label = `Sesi ${last.first_urutan} - ${last.last_urutan} (${last.durasi_jp} JP)`
      } else {
        last.sesi_label = `Sesi ${last.first_urutan} (${last.durasi_jp} JP)`
      }
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
