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
    const authStore = useAuthStore(); // Pobierz store

    // Ustaw tytuł
    document.title = to.meta.title ? `${to.meta.title} - ${defaultTitle}` : defaultTitle;

    // Sprawdź stan zalogowania BEZ fetchUser()
    const requiresAuth = to.meta.requiresAuth;
    const requiresGuest = to.meta.requiresGuest;
    const isLoggedIn = authStore.isLoggedIn; // Polegaj na stanie ze store'a

    // Logika przekierowań
    if (requiresAuth && !isLoggedIn) {
        next({ name: 'login', query: { redirect: to.fullPath } });
    } else if (requiresGuest && isLoggedIn) {
        next({ name: 'dashboard' });
    } else {
        next(); // Pozwól na nawigację
    }
    // Nie ma już bloku try...catch ani fetchUser
});

export default router;
