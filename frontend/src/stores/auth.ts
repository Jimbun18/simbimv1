import { defineStore } from 'pinia';
import api from '../services/api';

export interface UserProfile {
  id: number;
  nama: string;
  username: string;
  email: string | null;
  no_hp: string | null;
  role: 'kepala_sekolah' | 'staf_admin' | 'guru' | 'wali';
  status: string;
  must_change_password: boolean;
  foto_path: string | null;
  profile_detail?: any;
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('simbim_token') as string | null,
    user: (localStorage.getItem('simbim_user')
      ? JSON.parse(localStorage.getItem('simbim_user')!)
      : null) as UserProfile | null,
    loading: false,
    error: null as string | null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    role: (state) => state.user?.role ?? null,
    isAdmin: (state) => state.user?.role === 'kepala_sekolah' || state.user?.role === 'staf_admin',
    isKepalaSekolah: (state) => state.user?.role === 'kepala_sekolah',
    isGuru: (state) => state.user?.role === 'guru',
    isWali: (state) => state.user?.role === 'wali',
    userName: (state) => state.user?.nama ?? 'Pengguna',
    teacherProfile: (state) =>
      state.user?.profile_detail && (state.user?.role === 'guru' || state.user?.role === 'kepala_sekolah')
        ? state.user.profile_detail
        : null,
    isWaliKelas: (state) => {
      const homerooms = state.user?.profile_detail?.homeroom_classes;
      return Array.isArray(homerooms) && homerooms.length > 0;
    },
    homeroomClasses: (state): Array<{ id: number; kode: string; nama: string; tingkat?: number }> => {
      const homerooms = state.user?.profile_detail?.homeroom_classes;
      return Array.isArray(homerooms) ? homerooms : [];
    },
    primaryHomeroomClass: (state): { id: number; kode: string; nama: string; tingkat?: number } | null => {
      const homerooms = state.user?.profile_detail?.homeroom_classes;
      return Array.isArray(homerooms) && homerooms.length > 0 ? homerooms[0] : null;
    },
  },

  actions: {
    async login(identifier: string, password: string): Promise<boolean> {
      this.loading = true;
      this.error = null;

      try {
        const response = await api.post('/auth/login', {
          login: identifier,
          username: identifier,
          password: password,
        });

        if (response.data.success) {
          const { token, user } = response.data.data;
          this.token = token;
          this.user = user;

          localStorage.setItem('simbim_token', token);
          localStorage.setItem('simbim_user', JSON.stringify(user));
          return true;
        }

        this.error = response.data.message || 'Login gagal';
        return false;
      } catch (err: any) {
        this.error = err.response?.data?.message || 'Terjadi kesalahan saat login.';
        return false;
      } finally {
        this.loading = false;
      }
    },

    async fetchProfile(): Promise<void> {
      if (!this.token) return;
      try {
        const response = await api.get('/me');
        if (response.data.success) {
          this.user = response.data.data;
          localStorage.setItem('simbim_user', JSON.stringify(this.user));
        }
      } catch (err) {
        // Interceptor handles 401
      }
    },

    async logout(): Promise<void> {
      try {
        if (this.token) {
          await api.post('/auth/logout');
        }
      } catch (err) {
        // Lanjutkan logout lokal
      } finally {
        this.token = null;
        this.user = null;
        localStorage.removeItem('simbim_token');
        localStorage.removeItem('simbim_user');
      }
    },
  },
});
