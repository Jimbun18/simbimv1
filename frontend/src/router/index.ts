import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
  {
    path: '/',
    name: 'landing',
    component: () => import('../views/LandingView.vue'),
    meta: { layout: 'landing' },
  },
  {
    path: '/profil',
    name: 'landing-profile',
    component: () => import('../views/landing/LandingProfileView.vue'),
    meta: { layout: 'landing' },
  },
  {
    path: '/pendidikan',
    name: 'landing-education',
    component: () => import('../views/landing/LandingEducationView.vue'),
    meta: { layout: 'landing' },
  },
  {
    path: '/artikel',
    name: 'landing-articles',
    component: () => import('../views/landing/LandingArticlesView.vue'),
    meta: { layout: 'landing' },
  },
  {
    path: '/galeri',
    name: 'landing-gallery',
    component: () => import('../views/landing/LandingGalleryView.vue'),
    meta: { layout: 'landing' },
  },
  {
    path: '/prestasi',
    name: 'landing-achievements',
    component: () => import('../views/landing/LandingAchievementsView.vue'),
    meta: { layout: 'landing' },
  },
  {
    path: '/spmb',
    name: 'landing-spmb',
    component: () => import('../views/landing/LandingSpmbView.vue'),
    meta: { layout: 'landing' },
  },
  {
    path: '/home',
    redirect: '/',
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/LoginView.vue'),
    meta: { guest: true },
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('../views/DashboardView.vue'),
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/students',
    name: 'students',
    component: () => import('../views/StudentsView.vue'),
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin', 'guru'] },
  },
  {
    path: '/teachers',
    name: 'teachers',
    component: () => import('../views/TeachersView.vue'),
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/guardians',
    name: 'guardians',
    component: () => import('../views/GuardiansView.vue'),
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/classes-schedules',
    name: 'classes-schedules',
    component: () => import('../views/ClassesAndSchedulesView.vue'),
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/my-schedule',
    name: 'my-schedule',
    component: () => import('../views/MyScheduleView.vue'),
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin', 'guru'] },
  },
  {
    path: '/excel-imports',
    name: 'excel-imports',
    component: () => import('../views/ExcelImportView.vue'),
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/presensi',
    name: 'presensi',
    component: () => import('../views/AttendanceView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/profile',
    name: 'profile',
    component: () => import('../views/ProfileView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/attendance-management',
    name: 'attendance-management',
    component: () => import('../views/AttendanceManagementView.vue'),
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/my-attendance-history',
    name: 'my-attendance-history',
    component: () => import('../views/MyAttendanceHistoryView.vue'),
    meta: { requiresAuth: true, role: ['guru', 'kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/progress-activities',
    name: 'progress-activities',
    component: () => import('../views/ProgressAndActivitiesView.vue'),
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin', 'guru'] },
  },
  {
    path: '/rapor',
    name: 'rapor',
    component: () => import('../views/ReportCardView.vue'),
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin', 'guru'] },
  },
  {
    path: '/finance',
    name: 'finance',
    component: () => import('../views/FinanceView.vue'),
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/assets',
    name: 'assets',
    component: () => import('../views/AssetsView.vue'),
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/users-settings',
    name: 'users-settings',
    component: () => import('../views/UsersAndSettingsView.vue'),
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/cms',
    name: 'cms',
    component: () => import('../views/CmsView.vue'),
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/portal-wali',
    name: 'portal-wali',
    component: () => import('../views/ParentPortalView.vue'),
    meta: { requiresAuth: true, role: ['wali'] },
  },
  {
    path: '/backup',
    name: 'backup',
    component: () => import('../views/BackupView.vue'),
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, _from, next) => {
  const auth = useAuthStore();

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return next({ name: 'login' });
  }

  if (to.meta.guest && auth.isAuthenticated) {
    if (auth.isAdmin) return next({ name: 'dashboard' });
    if (auth.isGuru) return next({ name: 'presensi' });
    if (auth.isWali) return next({ name: 'portal-wali' });
    return next({ name: 'dashboard' });
  }

  if (to.meta.role && Array.isArray(to.meta.role)) {
    const allowedRoles = to.meta.role as string[];
    const userRole = auth.user?.role;
    if (userRole && !allowedRoles.includes(userRole)) {
      if (auth.isGuru) return next({ name: 'presensi' });
      if (auth.isWali) return next({ name: 'portal-wali' });
      return next({ name: 'dashboard' });
    }
  }

  next();
});

export default router;
