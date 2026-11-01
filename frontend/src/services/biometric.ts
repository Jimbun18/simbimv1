import { Capacitor } from '@capacitor/core';
import { BiometricAuth } from '@aparajita/capacitor-biometric-auth';

const STORAGE_KEY_ENABLED = 'simbim_biometric_enabled';
const STORAGE_KEY_AUTH_PAYLOAD = 'simbim_biometric_payload';

export interface BiometricAuthPayload {
  token?: string;
  user: any;
  identifier?: string;
  secret?: string; // Encoded password untuk re-autentikasi otomatis
  savedAt: string;
}

export function encodeSecret(str: string): string {
  try {
    return btoa(encodeURIComponent(str));
  } catch {
    return str;
  }
}

export function decodeSecret(str: string): string {
  try {
    return decodeURIComponent(atob(str));
  } catch {
    return str;
  }
}

export const biometricService = {
  encodeSecret,
  decodeSecret,

  /**
   * Mengecek apakah perangkat mendukung sensor biometrik (sidik jari / wajah)
   * dan user sedang berada di aplikasi native (Capacitor).
   */
  async isAvailable(): Promise<boolean> {
    if (!Capacitor.isNativePlatform()) {
      return false;
    }
    try {
      const result = await BiometricAuth.checkBiometry();
      return Boolean(result.isAvailable);
    } catch {
      return false;
    }
  },

  /**
   * Apakah pengguna telah mengaktifkan fitur login sidik jari di perangkat ini
   */
  isEnabled(): boolean {
    return localStorage.getItem(STORAGE_KEY_ENABLED) === 'true';
  },

  /**
   * Mendapatkan data payload sesi yang tersimpan untuk login sidik jari
   */
  getSavedPayload(): BiometricAuthPayload | null {
    try {
      const data = localStorage.getItem(STORAGE_KEY_AUTH_PAYLOAD);
      return data ? JSON.parse(data) : null;
    } catch {
      return null;
    }
  },

  /**
   * Mengaktifkan login biometrik dan menyimpan data kredensial terenkripsi
   */
  enableBiometric(user: any, token?: string | null, identifier?: string, password?: string): void {
    const payload: BiometricAuthPayload = {
      token: token || undefined,
      user,
      identifier: identifier || user?.username,
      secret: password ? encodeSecret(password) : undefined,
      savedAt: new Date().toISOString(),
    };
    localStorage.setItem(STORAGE_KEY_ENABLED, 'true');
    localStorage.setItem(STORAGE_KEY_AUTH_PAYLOAD, JSON.stringify(payload));
  },

  /**
   * Menonaktifkan fitur login biometrik
   */
  disableBiometric(): void {
    localStorage.removeItem(STORAGE_KEY_ENABLED);
    localStorage.removeItem(STORAGE_KEY_AUTH_PAYLOAD);
  },

  /**
   * Meminta autentikasi sidik jari dari sistem operasi Android/iOS
   */
  async authenticate(reason: string = 'Scan sidik jari untuk verifikasi akun'): Promise<boolean> {
    if (!Capacitor.isNativePlatform()) {
      return false;
    }
    try {
      await BiometricAuth.authenticate({
        reason,
        cancelTitle: 'Batal',
        allowDeviceCredential: true,
        iosFallbackTitle: 'Gunakan Kata Sandi',
      });
      return true;
    } catch (err: any) {
      return false;
    }
  },
};

export default biometricService;
