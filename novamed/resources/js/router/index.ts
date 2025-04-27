import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import HomePage from '../pages/Welcome.vue';
import LoginPage from '../pages/auth/Login.vue';
import RegisterPage from '../pages/auth/Register.vue';
import DashboardPage from '../pages/Dashboard.vue';
import ProfileSettingsPage from '../pages/settings/Profile.vue';
import ResetPasswordPage from '../pages/auth/ResetPassword.vue';

const routes: Array<RouteRecordRaw> = [
    { path: '/', name: 'home', component: HomePage, meta: { title: 'Strona Główna' } },
    { path: '/login', name: 'login', component: LoginPage, meta: { title: 'Logowanie', requiresGuest: true } },
    { path: '/register', name: 'register', component: RegisterPage, meta: { title: 'Rejestracja', requiresGuest: true } },
    { path: '/dashboard', name: 'dashboard', component: DashboardPage, meta: { title: 'Panel Pacjenta', requiresAuth: true } },
    { path: '/profile/settings', name: 'profile.settings', component: ProfileSettingsPage, meta: { title: 'Ustawienia Profilu', requiresAuth: true } },
    { path: '/reset-password/:token', name: 'password.reset', component: ResetPasswordPage, props: true, meta: { title: 'Reset Hasła', requiresGuest: true } },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

const defaultTitle = 'Nova Med';

router.beforeEach((to, from, next) => {
    console.log('Guard: before next() for', to.name);

    const authStore = useAuthStore();
    const isLoggedIn = authStore.isLoggedIn;

    const requiresAuth = to.meta.requiresAuth === true;
    const requiresGuest = to.meta.requiresGuest === true;

    console.log(`Route ${to.path}: requiresAuth=${requiresAuth}, requiresGuest=${requiresGuest}, isLoggedIn=${isLoggedIn}`);

    if (requiresAuth && !isLoggedIn) {
        console.log('Redirecting to login from', to.fullPath);
        next({ name: 'login', query: { redirect: to.fullPath } });
    } else if (requiresGuest && isLoggedIn) {
        console.log('Redirecting to dashboard');
        next({ name: 'dashboard' });
    } else {
        console.log('Proceeding to', to.name);
        next();
    }
});

// Aktualizacja tytułu strony
router.afterEach((to) => {
    document.title = to.meta.title ? `${to.meta.title} | ${defaultTitle}` : defaultTitle;
});

export default router;
