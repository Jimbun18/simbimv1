import { createRouter, createWebHistory } from 'vue-router';
import LoginView from '../views/LoginView.vue';
import DashboardView from '../views/DashboardView.vue';
import AttendanceView from '../views/AttendanceView.vue';
import ParentPortalView from '../views/ParentPortalView.vue';
import BackupView from '../views/BackupView.vue';
import StudentsView from '../views/StudentsView.vue';
import TeachersView from '../views/TeachersView.vue';
import ClassesAndSchedulesView from '../views/ClassesAndSchedulesView.vue';
import ExcelImportView from '../views/ExcelImportView.vue';
import AttendanceManagementView from '../views/AttendanceManagementView.vue';
import ProgressAndActivitiesView from '../views/ProgressAndActivitiesView.vue';
import FinanceView from '../views/FinanceView.vue';
import AssetsView from '../views/AssetsView.vue';
import UsersAndSettingsView from '../views/UsersAndSettingsView.vue';
import ProfileView from '../views/ProfileView.vue';
import GuardiansView from '../views/GuardiansView.vue';
import MyScheduleView from '../views/MyScheduleView.vue';
import LandingView from '../views/LandingView.vue';
import ReportCardView from '../views/ReportCardView.vue';
import MyAttendanceHistoryView from '../views/MyAttendanceHistoryView.vue';
import { useAuthStore } from '../stores/auth';

const routes = [
  {
    path: '/',
    name: 'landing',
    component: LandingView,
    meta: { layout: 'landing' },
  },
  {
    path: '/home',
    redirect: '/',
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { guest: true },
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: DashboardView,
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/students',
    name: 'students',
    component: StudentsView,
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin', 'guru'] },
  },
  {
    path: '/teachers',
    name: 'teachers',
    component: TeachersView,
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/guardians',
    name: 'guardians',
    component: GuardiansView,
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/classes-schedules',
    name: 'classes-schedules',
    component: ClassesAndSchedulesView,
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/my-schedule',
    name: 'my-schedule',
    component: MyScheduleView,
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin', 'guru'] },
  },
  {
    path: '/excel-imports',
    name: 'excel-imports',
    component: ExcelImportView,
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/presensi',
    name: 'presensi',
    component: AttendanceView,
    meta: { requiresAuth: true },
  },
  {
    path: '/profile',
    name: 'profile',
    component: ProfileView,
    meta: { requiresAuth: true },
  },
  {
    path: '/attendance-management',
    name: 'attendance-management',
    component: AttendanceManagementView,
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/my-attendance-history',
    name: 'my-attendance-history',
    component: MyAttendanceHistoryView,
    meta: { requiresAuth: true, role: ['guru', 'kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/progress-activities',
    name: 'progress-activities',
    component: ProgressAndActivitiesView,
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin', 'guru'] },
  },
  {
    path: '/rapor',
    name: 'rapor',
    component: ReportCardView,
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/finance',
    name: 'finance',
    component: FinanceView,
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/assets',
    name: 'assets',
    component: AssetsView,
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/users-settings',
    name: 'users-settings',
    component: UsersAndSettingsView,
    meta: { requiresAuth: true, role: ['kepala_sekolah', 'staf_admin'] },
  },
  {
    path: '/portal-wali',
    name: 'portal-wali',
    component: ParentPortalView,
    meta: { requiresAuth: true, role: ['wali'] },
  },
  {
    path: '/backup',
    name: 'backup',
    component: BackupView,
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
