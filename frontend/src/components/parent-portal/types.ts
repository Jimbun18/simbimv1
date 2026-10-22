export interface Child {
  id: number;
  nama_lengkap: string;
  nis: string;
  nisn?: string;
  status?: string;
  foto_url?: string;
  foto_path?: string;
  class_id?: number | string;
  school_class?: {
    nama?: string;
    nama_kelas?: string;
    homeroom_teacher?: {
      nama_lengkap?: string;
      no_hp?: string;
    };
  };
}

export interface AttendanceRecord {
  id: number;
  tanggal: string;
  status: string;
  keterangan?: string;
}

export interface AttendanceSummary {
  persentase_kehadiran?: number;
  hadir?: number;
  sakit?: number;
  izin?: number;
  alpa?: number;
  total_pertemuan_bulan_ini?: number;
  today?: {
    status?: string;
  };
  recent?: AttendanceRecord[];
}

export interface InvoiceItem {
  id: number;
  periode?: string;
  jatuh_tempo?: string;
  jumlah: number;
  jumlah_dibayar: number;
  sisa: number;
  status: string;
  fee_type?: {
    nama?: string;
  };
}

export interface FinancialSummary {
  status_tagihan?: string;
  total_tagihan?: number;
  jumlah_terbayar?: number;
  sisa_tagihan?: number;
  invoices?: InvoiceItem[];
}

export interface ProgressItem {
  id: number;
  kategori: string;
  capaian: string;
  nilai?: string | number;
  catatan?: string;
  tanggal: string;
  teacher?: {
    nama_lengkap?: string;
  };
}

export interface ActivityItem {
  id: number;
  kategori: string;
  judul: string;
  deskripsi: string;
  tanggal: string;
  teacher?: {
    nama_lengkap?: string;
  };
}

export interface AnnouncementItem {
  id: number;
  judul: string;
  konten: string;
  kategori?: string;
  is_pinned?: boolean;
  tgl_terbit: string;
}

export interface ReportCardGrade {
  nama_mapel?: string;
  sub_aspek?: string;
  tema?: string;
  nilai_akhir?: number | string;
  predikat?: string;
  capaian_kompetensi?: string;
}

export interface ReportCardSummary {
  id: number;
  jenis_rapor: string;
  periode?: string;
  semester?: string;
  wali_kelas_nama?: string;
  kepala_sekolah_nama?: string;
  academic_year?: {
    nama?: string;
  };
  school_class?: {
    nama_kelas?: string;
  };
}

export interface ReportCardDetail extends ReportCardSummary {
  fase?: string;
  sakit?: number;
  izin?: number;
  alpa?: number;
  grades?: ReportCardGrade[];
  catatan_wali_kelas?: string;
  wali_kelas_nip?: string;
  kepala_sekolah_nip?: string;
}

export interface ChildSummary {
  student: Child;
  attendance_summary?: AttendanceSummary;
  presensi_bulan_ini?: AttendanceSummary;
  keuangan?: FinancialSummary;
  latest_progress?: ProgressItem[];
  progres_terakhir?: ProgressItem[];
  latest_activities?: ActivityItem[];
  announcements?: AnnouncementItem[];
}

export type ParentPortalTab = 'progress' | 'attendance' | 'activities' | 'finance' | 'announcements' | 'rapor';
