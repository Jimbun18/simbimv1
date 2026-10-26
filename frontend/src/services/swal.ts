import Swal from 'sweetalert2';

/**
 * Kamus pemetaan nama field teknis ke istilah bahasa Indonesia yang ramah pengguna
 */
const FIELD_LABELS: Record<string, string> = {
  create_user_account: 'Pilihan Buat Akun Pengguna',
  'create user account': 'Pilihan Buat Akun Pengguna',
  nama_lengkap: 'Nama Lengkap',
  nama_panggilan: 'Nama Panggilan',
  nik: 'Nomor Induk Kependudukan (NIK)',
  nip: 'NIP / NUPTK',
  nuptk: 'NUPTK',
  nis: 'Nomor Induk Siswa (NIS)',
  nisn: 'NISN',
  jenis_kelamin: 'Jenis Kelamin',
  tempat_lahir: 'Tempat Lahir',
  tgl_lahir: 'Tanggal Lahir',
  agama: 'Agama',
  alamat_jalan: 'Alamat Jalan',
  rt: 'RT',
  rw: 'RW',
  desa_kelurahan: 'Desa / Kelurahan',
  kecamatan: 'Kecamatan',
  kab_kota: 'Kabupaten / Kota',
  provinsi: 'Provinsi',
  kode_pos: 'Kode Pos',
  no_hp: 'Nomor WhatsApp / HP',
  no_hp_siswa: 'Nomor WhatsApp / HP Siswa',
  no_hp_ibu: 'Nomor WhatsApp Ibu',
  no_hp_ayah: 'Nomor WhatsApp Ayah',
  email: 'Alamat Email',
  jenis_ptk: 'Jenis PTK (Pendidik/Tendik)',
  status_kepegawaian: 'Status Kepegawaian',
  tgl_mulai_tugas: 'Tanggal Mulai Tugas',
  tgl_akhir_tugas: 'Tanggal Akhir Tugas',
  tgl_masuk: 'Tanggal Masuk / Bergabung',
  tahun_masuk: 'Tahun Masuk',
  current_class_id: 'Rombongan Belajar (Kelas)',
  class_id: 'Rombongan Belajar (Kelas)',
  foto: 'Foto Profil',
  file: 'Berkas File',
  password: 'Kata Sandi',
  password_confirmation: 'Konfirmasi Kata Sandi',
  username: 'Nama Pengguna (Username)',
  role: 'Peran Pengguna (Role)',
  nama_kelas: 'Nama Kelas',
  tingkat: 'Tingkat Kelas',
  tahun_ajaran_id: 'Tahun Ajaran',
  jam_per_minggu: 'Beban Jam Pelajaran (JP)',
  tema_nomor: 'Nomor Tema',
  muatan_pelajaran: 'Muatan Campuran Pelajaran',
  tingkat_berlaku: 'Tingkat Kelas',
  kkm: 'Nilai KKM Standar',
};

/**
 * Menerjemahkan nama field teknis menjadi nama yang mudah dipahami
 */
const humanizeField = (field: string): string => {
  const cleanKey = field.toLowerCase().replace(/\s+/g, '_');
  if (FIELD_LABELS[cleanKey]) return FIELD_LABELS[cleanKey];
  if (FIELD_LABELS[field.toLowerCase()]) return FIELD_LABELS[field.toLowerCase()];
  
  // Format fallback: huruf kapital per kata tanpa underscore
  return field
    .replace(/[._]/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase());
};

/**
 * Menerjemahkan pesan validasi bawaan Laravel / English ke Bahasa Indonesia yang ramah pengguna
 */
const translateMessage = (msg: string): string => {
  if (!msg || typeof msg !== 'string') return '';
  let text = msg.trim();

  // Pola spesifik kode validasi Laravel mentah
  if (text === 'validation.max.numeric') {
    return 'Nilai angka melebihi batas maksimal yang diperbolehkan.';
  }
  if (text === 'validation.min.numeric') {
    return 'Nilai angka kurang dari batas minimal yang diperbolehkan.';
  }
  if (text === 'validation.required') {
    return 'Kolom isian wajib diisi.';
  }
  if (text === 'validation.in') {
    return 'Pilihan yang dipilih tidak valid atau tidak sesuai dengan opsi yang tersedia.';
  }
  if (text === 'validation.not_in') {
    return 'Pilihan yang dipilih tidak diperbolehkan.';
  }
  if (text === 'validation.unique') {
    return 'Data ini sudah terdaftar sebelumnya di sistem.';
  }

  // Pola spesifik create user account
  if (/create user account/i.test(text) && /true or false/i.test(text)) {
    return 'Pilihan "Buat Akun Pengguna" harus dipilih Ya atau Tidak.';
  }

  // Pola regex umum Laravel
  const patterns: Array<[RegExp, (m: RegExpMatchArray) => string]> = [
    [/^The (.+) field is required\b/i, (m) => `${humanizeField(m[1])} wajib diisi dan tidak boleh dikosongkan.`],
    [/^The (.+) has already been taken\b/i, (m) => `${humanizeField(m[1])} sudah terdaftar di sistem. Silakan periksa kembali atau gunakan nilai lain.`],
    [/^The (.+) field must be true or false\b/i, (m) => `${humanizeField(m[1])} harus berupa pilihan Ya atau Tidak.`],
    [/^The (.+) must be a valid email address\b/i, (m) => `${humanizeField(m[1])} harus berupa format email yang valid (contoh: nama@email.com).`],
    [/^The (.+) must be an image\b/i, (m) => `${humanizeField(m[1])} harus berupa file gambar (JPG, PNG, atau WebP).`],
    [/^The (.+) must not be greater than (\d+) kilobytes\b/i, (m) => `Ukuran ${humanizeField(m[1])} maksimal ${m[2]} KB (tidak boleh lebih besar).`],
    [/^The (.+) must not be greater than (\d+) characters\b/i, (m) => `${humanizeField(m[1])} maksimal ${m[2]} karakter.`],
    [/^The (.+) must be at least (\d+) characters\b/i, (m) => `${humanizeField(m[1])} minimal harus ${m[2]} karakter.`],
    [/^The (.+) (?:field )?must not be greater than (\d+)\b/i, (m) => `${humanizeField(m[1])} maksimal ${m[2]}.`],
    [/^The (.+) (?:field )?must be at least (\d+)\b/i, (m) => `${humanizeField(m[1])} minimal ${m[2]}.`],
    [/^The (.+) must be a date before today\b/i, (m) => `${humanizeField(m[1])} harus berupa tanggal sebelum hari ini.`],
    [/^The (.+) format is invalid\b/i, (m) => `Format ${humanizeField(m[1])} tidak sesuai ketentuan.`],
    [/^The selected (.+) is invalid\b/i, (m) => `Pilihan pada ${humanizeField(m[1])} tidak valid.`],
  ];

  for (const [pattern, handler] of patterns) {
    const match = text.match(pattern);
    if (match) {
      return handler(match);
    }
  }

  // Terjemahan umum pesan status HTTP
  if (/Network Error/i.test(text)) {
    return 'Gagal terhubung ke server. Pastikan koneksi internet atau server backend sedang berjalan.';
  }
  if (/Unauthenticated/i.test(text)) {
    return 'Sesi masuk Anda telah berakhir. Silakan login kembali ke sistem.';
  }
  if (/Server Error/i.test(text)) {
    return 'Terjadi kendala internal pada server. Silakan coba kembali atau hubungi admin.';
  }

  return text;
};

/**
 * Parsing error dari berbagai bentuk (Axios error, string, objek response)
 */
export const extractErrorInfo = (errorOrText: any, fallbackText: string = 'Terjadi kesalahan pada sistem.') => {
  let mainMessage = fallbackText;
  const detailList: string[] = [];
  let suggestion = 'Silakan periksa kembali isian formulir Anda lalu coba simpan kembali.';

  if (!errorOrText) {
    return { mainMessage, detailList, suggestion };
  }

  // Jika berupa string langsung
  if (typeof errorOrText === 'string') {
    mainMessage = translateMessage(errorOrText);
    return { mainMessage, detailList, suggestion };
  }

  // Cek respon Axios
  const resData = errorOrText.response?.data || errorOrText.data || (typeof errorOrText === 'object' ? errorOrText : null);

  if (resData) {
    // 1. Cek validasi Laravel errors: { [field]: string[] }
    if (resData.errors && typeof resData.errors === 'object') {
      mainMessage = 'Terdapat beberapa data yang belum sesuai atau belum lengkap:';
      suggestion = 'Periksa kolom-kolom yang disebutkan di bawah ini, perbaiki data yang salah, lalu klik tombol simpan kembali.';

      for (const field in resData.errors) {
        const fieldErrors = resData.errors[field];
        if (Array.isArray(fieldErrors)) {
          fieldErrors.forEach((e: string) => {
            detailList.push(translateMessage(e));
          });
        } else if (typeof fieldErrors === 'string') {
          detailList.push(translateMessage(fieldErrors));
        }
      }
    } else if (resData.message) {
      mainMessage = translateMessage(resData.message);
    }
  } else if (errorOrText.message) {
    mainMessage = translateMessage(errorOrText.message);
  }

  // Jika status HTTP 401 / 403
  if (errorOrText.response?.status === 401) {
    mainMessage = 'Sesi masuk Anda telah habis.';
    suggestion = 'Silakan lakukan login ulang untuk melanjutkan aktivitas Anda.';
  } else if (errorOrText.response?.status === 403) {
    mainMessage = 'Akses ditolak.';
    suggestion = 'Akun Anda tidak memiliki izin untuk melakukan tindakan ini.';
  } else if (errorOrText.response?.status === 500) {
    suggestion = 'Terdapat kendala teknis pada server. Jika terus berulang, silakan laporkan ke tim teknis.';
  }

  return { mainMessage, detailList, suggestion };
};

/**
 * Konfigurasi SweetAlert2 bertema SDI BIM (Emerald / Slate / Modern)
 */
export const showSuccess = (title: string, text?: string) => {
  return Swal.fire({
    title,
    text,
    icon: 'success',
    confirmButtonColor: '#059669', // Emerald-600
    confirmButtonText: 'OK',
    customClass: {
      popup: 'rounded-3xl shadow-2xl font-sans',
      confirmButton: 'rounded-xl px-6 py-2.5 font-bold shadow-md hover:shadow-lg transition-all',
    },
  });
};

export const showWarning = (title: string, text?: string) => {
  return Swal.fire({
    title,
    text,
    icon: 'warning',
    confirmButtonColor: '#d97706', // Amber-600
    confirmButtonText: 'Mengerti',
    customClass: {
      popup: 'rounded-3xl shadow-2xl font-sans',
      confirmButton: 'rounded-xl px-6 py-2.5 font-bold shadow-md hover:shadow-lg transition-all',
    },
  });
};

export const showInfo = (title: string, text?: string) => {
  return Swal.fire({
    title,
    text,
    icon: 'info',
    confirmButtonColor: '#0284c7', // Sky-600
    confirmButtonText: 'Mengerti',
    customClass: {
      popup: 'rounded-3xl shadow-2xl font-sans',
      confirmButton: 'rounded-xl px-6 py-2.5 font-bold shadow-md hover:shadow-lg transition-all',
    },
  });
};

/**
 * Menampilkan pesan kesalahan informatif dan ramah bagi pengguna awam
 */
export const showError = (title: string, errorOrText?: any, fallbackText?: string) => {
  const { mainMessage, detailList, suggestion } = extractErrorInfo(errorOrText, fallbackText);

  let htmlContent = `
    <div class="text-left font-sans space-y-3 pt-1">
      <p class="text-sm text-slate-700 font-medium leading-relaxed">
        ${mainMessage}
      </p>
  `;

  if (detailList.length > 0) {
    htmlContent += `
      <div class="bg-rose-50 border border-rose-200/90 rounded-2xl p-3.5 text-xs text-rose-950 shadow-sm space-y-2">
        <div class="font-bold flex items-center gap-1.5 text-rose-800 text-xs uppercase tracking-wider">
          <span class="inline-block w-2 h-2 rounded-full bg-rose-500"></span>
          Petunjuk Bagian Yang Perlu Diperbaiki:
        </div>
        <ul class="list-disc list-inside space-y-1.5 pl-1 text-slate-800 leading-relaxed font-normal">
          ${detailList.map((item) => `<li><span class="font-medium text-rose-950">${item}</span></li>`).join('')}
        </ul>
      </div>
    `;
  }

  if (suggestion) {
    htmlContent += `
      <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-3 text-xs text-slate-600 shadow-sm">
        <span class="font-semibold text-slate-800">Saran:</span> ${suggestion}
      </div>
    `;
  }

  htmlContent += `</div>`;

  return Swal.fire({
    title,
    html: htmlContent,
    icon: 'error',
    confirmButtonColor: '#e11d48', // Rose-600
    confirmButtonText: 'Saya Mengerti',
    customClass: {
      popup: 'rounded-3xl shadow-2xl font-sans p-6 max-w-lg',
      confirmButton: 'rounded-xl px-6 py-2.5 font-bold shadow-md hover:shadow-lg transition-all text-sm',
    },
  });
};

export const showConfirm = async (
  title: string,
  text?: string,
  confirmText: string = 'Ya, Lanjutkan',
  icon: 'warning' | 'question' | 'info' | 'error' = 'warning'
) => {
  const result = await Swal.fire({
    title,
    text,
    icon,
    showCancelButton: true,
    confirmButtonColor: '#059669',
    cancelButtonColor: '#64748b',
    confirmButtonText: confirmText,
    cancelButtonText: 'Batal',
    customClass: {
      popup: 'rounded-3xl shadow-xl font-sans',
      confirmButton: 'rounded-xl px-5 py-2.5 font-bold',
      cancelButton: 'rounded-xl px-5 py-2.5 font-semibold',
    },
  });
  return result.isConfirmed;
};

/**
 * Konfirmasi hapus dengan validasi mengetik kata "hapus" atau kode khusus
 */
export const confirmDeleteWithKeyword = async (
  itemName: string = 'data ini',
  keyword: string = 'hapus'
): Promise<boolean> => {
  const targetKeyword = (keyword || 'hapus').trim().toLowerCase();
  const result = await Swal.fire({
    title: 'Konfirmasi Penghapusan',
    html: `Tindakan ini tidak dapat dibatalkan.<br>Ketik kata <strong class="text-rose-600 font-mono select-none">${targetKeyword}</strong> di bawah ini untuk konfirmasi menghapus <strong>${itemName}</strong>:`,
    icon: 'warning',
    input: 'text',
    inputPlaceholder: `Ketik "${targetKeyword}" di sini...`,
    showCancelButton: true,
    confirmButtonColor: '#e11d48', // Rose-600
    cancelButtonColor: '#64748b', // Slate-500
    confirmButtonText: 'Hapus Data Sekarang',
    cancelButtonText: 'Batal',
    customClass: {
      popup: 'rounded-3xl shadow-2xl font-sans p-6',
      input: 'rounded-xl border-gray-300 text-center font-mono',
      confirmButton: 'rounded-xl px-5 py-2.5 font-bold text-sm',
      cancelButton: 'rounded-xl px-5 py-2.5 font-semibold text-sm',
    },
    inputValidator: (value) => {
      if (!value) {
        return `Anda harus mengetik "${targetKeyword}" untuk melanjutkan!`;
      }
      if (value.trim().toLowerCase() !== targetKeyword) {
        return `Kata konfirmasi salah. Harap ketik "${targetKeyword}" secara tepat!`;
      }
      return null;
    },
  });

  return result.isConfirmed;
};

export default {
  showSuccess,
  showWarning,
  showInfo,
  showError,
  showConfirm,
  confirmDeleteWithKeyword,
};
