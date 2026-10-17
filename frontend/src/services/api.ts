import axios, { type AxiosInstance, type AxiosResponse } from 'axios';

export interface ApiResponse<T = any> {
  success: boolean;
  message: string;
  data: T;
  meta?: any;
  summary?: any;
}

const api: AxiosInstance = axios.create({
  baseURL: '/api/v1',
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
