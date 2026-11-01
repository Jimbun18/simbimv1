import { Capacitor } from '@capacitor/core';
import { BiometricAuth } from '@aparajita/capacitor-biometric-auth';

const STORAGE_KEY_ENABLED = 'simbim_biometric_enabled';
const STORAGE_KEY_AUTH_PAYLOAD = 'simbim_biometric_payload';

export interface BiometricAuthPayload {
  token: string;
  user: any;
  savedAt: string;
}

export const biometricService = {
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
   * Mengaktifkan login biometrik dan menyimpan token sesi saat ini
   */
  enableBiometric(user: any, token: string): void {
    const payload: BiometricAuthPayload = {
      token,
      user,
      savedAt: new Date().toISOString(),
    };
    localStorage.setItem(STORAGE_KEY_ENABLED, 'true');
    localStorage.setItem(STORAGE_KEY_AUTH_PAYLOAD, JSON.stringify(payload));
  },

  /**
   * Menonaktifkan login biometrik
   */
  disableBiometric(): void {
    localStorage.removeItem(STORAGE_KEY_ENABLED);
    localStorage.removeItem(STORAGE_KEY_AUTH_PAYLOAD);
  },

  /**
   * Menjalankan dialog sensor sidik jari bawaan Android (BiometricPrompt)
   */
  async authenticate(reason = 'Tempelkan sidik jari Anda untuk masuk ke SIM-BIM Purwokerto'): Promise<boolean> {
    try {
      await BiometricAuth.authenticate({
        reason,
        cancelTitle: 'Gunakan Kata Sandi',
        allowDeviceCredential: true,
        androidTitle: 'Masuk dengan Sidik Jari',
        androidSubtitle: 'SDI Bina Insan Mandiri Purwokerto',
      });
      return true;
    } catch (err: any) {
      console.warn('Biometric authentication failed or cancelled:', err);
      return false;
    }
  },
};

export default biometricService;
