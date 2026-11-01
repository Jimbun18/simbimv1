import axios, { type AxiosInstance, type AxiosResponse } from 'axios';
import { Capacitor } from '@capacitor/core';

export interface ApiResponse<T = any> {
  success: boolean;
  message: string;
  data: T;
  meta?: any;
  summary?: any;
}

const getApiBaseUrl = (): string => {
  // Di aplikasi native Android Capacitor, arahkan langsung ke domain live
  if (Capacitor.isNativePlatform()) {
    return 'https://sdislambims.my.id/api/v1';
  }
  if (import.meta.env.VITE_API_BASE_URL) {
    return import.meta.env.VITE_API_BASE_URL;
  }
  return '/api/v1';
};

const api: AxiosInstance = axios.create({
  baseURL: getApiBaseUrl(),
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 30000,
});

// Request interceptor: lampirkan Bearer token jika ada
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('simbim_token');
  if (token && config.headers) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  if (config.data instanceof FormData && config.headers) {
    delete config.headers['Content-Type'];
  }
  return config;
}, (error) => {
  return Promise.reject(error);
});

// Response interceptor: tangani status 401 unauthenticated
api.interceptors.response.use(
  (response: AxiosResponse) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      localStorage.removeItem('simbim_token');
      localStorage.removeItem('simbim_user');
      if (window.location.pathname !== '/login') {
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);

export default api;
