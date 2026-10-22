export interface SubjectGrade {
  id?: number;
  nama_mapel: string;
  kelompok?: string;
  tema?: string | null;
  aspek?: string | null;
  sub_aspek?: string | null;
  nilai_akhir?: number | null | string;
  predikat?: string | null;
  capaian_kompetensi?: string | null;
  urutan?: number;
}

export interface Extracurricular {
  id?: number;
  kegiatan: string;
  predikat: string;
  keterangan: string | null;
  urutan?: number;
}

export interface ReportCardDetail {
  id: number;
  student_id: number;
  class_id: number;
  academic_year_id: number;
  semester: string;
  jenis_rapor: 'kurikulum_merdeka' | 'triwulan_tematik' | 'karakter_lifeskill';
  periode?: string | null;
  fase: string;
  tempat_tanggal: string;
  tanggal_rapor?: string | null;
  wali_kelas_nama?: string | null;
  wali_kelas_nip?: string | null;
  kepala_sekolah_nama?: string | null;
  kepala_sekolah_nip?: string | null;
  kokurikuler_tema?: string | null;
  kokurikuler_dimensi?: string | null;
  kokurikuler_target?: string | null;
  kokurikuler_capaian?: string | null;
  kokurikuler_deskripsi?: string | null;
  sakit: number;
  izin: number;
  alpa: number;
  catatan_wali_kelas?: string | null;
  tanggapan_orang_tua?: string | null;
  status: 'draft' | 'menunggu_validasi' | 'final' | 'perlu_revisi';
  submitted_at?: string | null;
  submitted_by?: number | null;
  submitter?: { id: number; nama: string } | null;
  validated_at?: string | null;
  validated_by?: number | null;
  validator?: { id: number; nama: string } | null;
  validation_notes?: string | null;
  student: {
    id: number;
    nis: string;
    nisn: string;
    nama_lengkap: string;
    jenis_kelamin: string;
    tempat_lahir?: string;
    tgl_lahir?: string;
    alamat_jalan?: string;
    desa_kelurahan?: string;
    kecamatan?: string;
    kab_kota?: string;
    religion?: { nama: string };
    asal_sekolah_tk?: string;
    tgl_masuk?: string;
    foto_path?: string | null;
    guardians?: any[];
  };
  school_class?: {
    id: number;
    nama: string;
    tingkat: number;
  };
  academic_year?: {
    id: number;
    nama: string;
  };
  grades: SubjectGrade[];
  extracurriculars: Extracurricular[];
}
