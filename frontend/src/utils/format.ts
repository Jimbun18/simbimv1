/**
 * src/utils/format.ts
 * Utilitas bersama pemformat mata uang, tanggal, waktu, dan umur untuk SIM-BIM.
 */

export interface FormatRupiahOptions {
  /** Jika true, nilai akan dibulatkan ke bilangan bulat terdekat. Default: false */
  round?: boolean;
}

/**
 * Format angka atau string numerik ke format mata uang Rupiah (tanpa prefix 'Rp').
 * Contoh: 350000 -> "350.000", "250000.50" -> "250.000,5" (desimal dipertahankan),
 *         atau dengan options { round: true } -> "250.001".
 * Nilai null, undefined, '', 'abc', NaN aman mengembalikan "0".
 */
export const formatRupiah = (
  val: number | string | null | undefined,
  options?: FormatRupiahOptions
): string => {
  if (val === null || val === undefined || val === '') return '0';
  const num = typeof val === 'string' ? Number(val) : val;
  if (isNaN(num)) return '0';

  const target = options?.round ? Math.round(num) : num;
  return new Intl.NumberFormat('id-ID').format(target);
};

export type DateVariant = 'short' | 'with-day' | 'full' | 'with-time';

/**
 * Format tanggal ke teks ramah pengguna sesuai varian:
 * - 'short': "17 Agu 2026" (default / format tabel standar)
 * - 'with-day': "Sen, 17 Agu 2026" (ParentPortal & Presensi harian)
 * - 'full': "Senin, 17 Agustus 2026" (Riwayat presensi lengkap)
 * - 'with-time': "17 Agu 2026, 08.00" (Riwayat backup / log)
 */
export const formatDate = (
  dateStr: string | Date | null | undefined,
  variant: DateVariant = 'short'
): string => {
  if (!dateStr) return '-';
  const d = typeof dateStr === 'string' ? new Date(dateStr) : dateStr;
  if (isNaN(d.getTime())) return '-';

  switch (variant) {
    case 'with-day':
      return d.toLocaleDateString('id-ID', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
      });
    case 'full':
      return d.toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
      });
    case 'with-time': {
      const datePart = d.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
      });
      const timePart = d
        .toLocaleTimeString('id-ID', {
          hour: '2-digit',
          minute: '2-digit',
          hour12: false,
        })
        .replace(':', '.');
      return `${datePart}, ${timePart}`;
    }
    case 'short':
    default:
      return d.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
      });
  }
};

/**
 * Alias formatDate(dateStr, 'short') agar kompatibel dengan formatDateIndo yang ada.
 */
export const formatDateIndo = (dateStr: string | null | undefined): string => {
  return formatDate(dateStr, 'short');
};

/**
 * Format waktu jam:menit (HH:mm) dari string waktu ISO atau "HH:mm:ss".
 * Contoh: "07:30:00" -> "07:30"
 * @param timeStr string waktu
 * @param fallback default '-' jika null/empty; kirim '' jika dibutuhkan empty string
 */
export const formatTime = (timeStr?: string | null, fallback = '-'): string => {
  if (!timeStr) return fallback;
  return timeStr.substring(0, 5);
};

/**
 * Menghitung umur dari tanggal lahir.
 * Contoh output: "8 Tahun 5 Bulan"
 * Mengembalikan string kosong '' jika input kosong atau tidak valid.
 */
export const calculateAge = (
  birthDateStr: string | null | undefined,
  referenceDate = new Date()
): string => {
  if (!birthDateStr) return '';
  const birth = new Date(birthDateStr);
  if (isNaN(birth.getTime())) return '';

  let years = referenceDate.getFullYear() - birth.getFullYear();
  let months = referenceDate.getMonth() - birth.getMonth();
  const days = referenceDate.getDate() - birth.getDate();

  if (days < 0) {
    months--;
  }

  if (months < 0) {
    years--;
    months += 12;
  }

  if (years < 0) return '';
  return `${years} Tahun ${months} Bulan`;
};
