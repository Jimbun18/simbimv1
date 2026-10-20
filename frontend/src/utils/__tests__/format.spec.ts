import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { formatRupiah, formatDate, formatDateIndo, formatTime, calculateAge } from '../format';

describe('formatRupiah', () => {
  it('handles empty, zero, and nullish inputs identically to old variants', () => {
    expect(formatRupiah(null)).toBe('0');
    expect(formatRupiah(undefined)).toBe('0');
    expect(formatRupiah('')).toBe('0');
    expect(formatRupiah(0)).toBe('0');
    expect(formatRupiah('0')).toBe('0');
  });

  it('formats standard integer monetary amounts with id-ID dot thousand separator', () => {
    expect(formatRupiah(350000)).toBe('350.000');
    expect(formatRupiah('350000')).toBe('350.000');
    expect(formatRupiah(1500000)).toBe('1.500.000');
    expect(formatRupiah('250000.00')).toBe('250.000');
  });

  it('preserves decimals by default for database decimal(12,2) values', () => {
    expect(formatRupiah('250000.50')).toBe('250.000,5');
    expect(formatRupiah(250000.75)).toBe('250.000,75');
  });

  it('supports rounding when round: true is requested (matching ParentPortal variant)', () => {
    expect(formatRupiah('250000.50', { round: true })).toBe('250.001');
    expect(formatRupiah(250000.75, { round: true })).toBe('250.001');
  });

  it('safely handles non-numeric and NaN values returning "0"', () => {
    expect(formatRupiah('abc')).toBe('0');
    expect(formatRupiah(NaN)).toBe('0');
  });
});

describe('formatDate', () => {
  it('returns "-" for nullish and empty inputs', () => {
    expect(formatDate(null)).toBe('-');
    expect(formatDate(undefined)).toBe('-');
    expect(formatDate('')).toBe('-');
    expect(formatDate('invalid-date')).toBe('-');
  });

  it('formats short variant ("17 Agu 2026") matching formatDateIndo', () => {
    expect(formatDate('2026-08-17', 'short')).toBe('17 Agu 2026');
    expect(formatDateIndo('2026-08-17')).toBe('17 Agu 2026');
  });

  it('formats with-day variant ("Sen, 17 Agu 2026") matching ParentPortal pattern', () => {
    expect(formatDate('2026-08-17', 'with-day')).toBe('Sen, 17 Agu 2026');
  });

  it('formats full variant ("Senin, 17 Agustus 2026") matching attendance history pattern', () => {
    expect(formatDate('2026-08-17', 'full')).toBe('Senin, 17 Agustus 2026');
  });

  it('formats with-time variant matching backup log pattern', () => {
    // 2026-08-17T08:00:00 in local time
    const d = new Date(2026, 7, 17, 8, 0, 0);
    expect(formatDate(d, 'with-time')).toBe('17 Agu 2026, 08.00');
  });
});

describe('formatTime', () => {
  it('formats valid time string to HH:mm', () => {
    expect(formatTime('07:30:00')).toBe('07:30');
    expect(formatTime('14:45')).toBe('14:45');
  });

  it('returns default fallback "-" when empty or nullish', () => {
    expect(formatTime(null)).toBe('-');
    expect(formatTime(undefined)).toBe('-');
    expect(formatTime('')).toBe('-');
  });

  it('supports custom fallback e.g. "" for MyScheduleView', () => {
    expect(formatTime(null, '')).toBe('');
    expect(formatTime(undefined, '')).toBe('');
    expect(formatTime('', '')).toBe('');
  });
});

describe('calculateAge', () => {
  beforeEach(() => {
    vi.useFakeTimers();
    vi.setSystemTime(new Date(2026, 9, 20, 12, 0, 0)); // 20 Oktober 2026
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('returns empty string for nullish, empty, or invalid inputs', () => {
    expect(calculateAge(null)).toBe('');
    expect(calculateAge(undefined)).toBe('');
    expect(calculateAge('')).toBe('');
    expect(calculateAge('not-a-date')).toBe('');
  });

  it('calculates age correctly with years and months', () => {
    // Born 15 Mei 2018 -> 8 Tahun 5 Bulan (pada 20 Okt 2026)
    expect(calculateAge('2018-05-15')).toBe('8 Tahun 5 Bulan');

    // Born 20 Okt 2020 -> 6 Tahun 0 Bulan
    expect(calculateAge('2020-10-20')).toBe('6 Tahun 0 Bulan');

    // Born 25 Okt 2020 -> 5 Tahun 11 Bulan (belum ulang tahun di bulan ini)
    expect(calculateAge('2020-10-25')).toBe('5 Tahun 11 Bulan');
  });
});
