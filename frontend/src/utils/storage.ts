import { Capacitor } from '@capacitor/core';

/**
 * Mengubah path relatif penyimpanan berkas/foto menjadi URL yang valid,
 * baik saat berjalan di Web Browser maupun di aplikasi Android (Capacitor).
 */
export const getStorageUrl = (path: string | null | undefined): string => {
  if (!path) return '';
  if (path.startsWith('http://') || path.startsWith('https://')) return path;

  const cleanPath = path.startsWith('/') ? path.slice(1) : path;
  const baseUrl = Capacitor.isNativePlatform() ? 'https://sdislambims.my.id' : '';

  if (cleanPath.startsWith('storage/')) {
    return `${baseUrl}/${cleanPath}`;
  }
  return `${baseUrl}/storage/${cleanPath}`;
};

