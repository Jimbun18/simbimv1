export interface LinkedStudent {
  id: number;
  nis: string;
  nama_lengkap: string;
  class_id?: number | string;
  school_class?: {
    id: number;
    nama: string;
  };
}

export interface GuardianUser {
  id: number;
  username: string;
  email?: string;
}

export interface Guardian {
  id: number;
  hubungan: 'ayah' | 'ibu' | 'wali' | string;
  nama_lengkap: string;
  nik?: string;
  no_hp?: string;
  no_whatsapp?: string;
  email?: string;
  tempat_lahir?: string;
  tgl_lahir?: string;
  status_hidup?: 'hidup' | 'meninggal' | string;
  alamat_jalan?: string;
  kab_kota?: string;
  user_id?: number | null;
  user?: GuardianUser | null;
  students?: LinkedStudent[];
}

export interface GuardianFormData {
  hubungan: 'ayah' | 'ibu' | 'wali' | string;
  nama_lengkap: string;
  nik: string;
  tempat_lahir: string;
  tgl_lahir: string;
  status_hidup: 'hidup' | 'meninggal' | string;
  no_hp: string;
  email: string;
  alamat_jalan: string;
  kab_kota: string;
  create_user_account?: boolean;
}

export interface GuardianStats {
  total_guardians: number;
  active_accounts: number;
  connected_students: number;
}

export interface GuardianMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}
