/**
 * Menghitung masa bergabung / durasi dari startDate sampai endDate (atau sekarang).
 * Mengembalikan format terperinci: misal "2 tahun 3 bulan 12 hari", "4 bulan 5 hari", atau "8 hari".
 */
export const calculateTenure = (startDateStr: string | null | undefined, endDateStr?: string | null | undefined): string => {
  if (!startDateStr) return '-';

  const start = new Date(startDateStr);
  if (isNaN(start.getTime())) return '-';

  const end = endDateStr ? new Date(endDateStr) : new Date();
  if (isNaN(end.getTime()) || end < start) return '0 hari';

  let years = end.getFullYear() - start.getFullYear();
  let months = end.getMonth() - start.getMonth();
  let days = end.getDate() - start.getDate();

  if (days < 0) {
    // Ambil jumlah hari di bulan sebelumnya dari tanggal end
    const prevMonth = new Date(end.getFullYear(), end.getMonth(), 0);
    days += prevMonth.getDate();
    months -= 1;
  }

  if (months < 0) {
    years -= 1;
    months += 12;
  }

  const parts: string[] = [];
  if (years > 0) parts.push(`${years} thn`);
  if (months > 0) parts.push(`${months} bln`);
  if (days > 0 || parts.length === 0) parts.push(`${days} hr`);

  return parts.join(' ');
};

/**
 * Format tanggal Indonesia ramah pengguna: "15 Jul 2024"
 */
export const formatDateIndo = (dateStr: string | null | undefined): string => {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return '-';
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

/**
 * Mengonversi tanggal ke format 'YYYY-MM-DD' yang dibutuhkan oleh <input type="date">.
 * Menangani format string ISO (misal '2024-05-15T00:00:00Z'), 'YYYY-MM-DD', atau Date object tanpa pergeseran zona waktu.
 */
export const toInputDate = (val: any): string => {
  if (!val) return '';
  if (typeof val === 'string') {
    if (/^\d{4}-\d{2}-\d{2}$/.test(val)) return val;
    const match = val.match(/^(\d{4}-\d{2}-\d{2})/);
    if (match) return match[1];
  }
  try {
    const d = new Date(val);
    if (!isNaN(d.getTime())) {
      const year = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    }
  } catch {}
  return '';
};

