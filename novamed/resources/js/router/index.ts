import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import HomePage from '../pages/Welcome.vue';
import LoginPage from '../pages/auth/Login.vue';
import RegisterPage from '../pages/auth/Register.vue';
import DashboardPage from '../pages/Dashboard.vue';
import ProfileSettingsPage from '../pages/settings/Profile.vue';
import ResetPasswordPage from '../pages/auth/ResetPassword.vue';
import { storeToRefs } from 'pinia';

const routes: Array<RouteRecordRaw> = [
    { path: '/', name: 'home', component: HomePage, meta: { title: 'Strona Główna' } },
    { path: '/login', name: 'login', component: LoginPage, meta: { title: 'Logowanie', requiresGuest: true } },
    { path: '/register', name: 'register', component: RegisterPage, meta: { title: 'Rejestracja', requiresGuest: true } },
    { path: '/dashboard', name: 'dashboard', component: DashboardPage, meta: { title: 'Panel Pacjenta', requiresAuth: true } },

    // Przekierowanie głównego widoku ustawień do profilu
    {
        path: '/settings',
        name: 'settings',
        redirect: { name: 'settings.profile' },
        meta: { title: 'Ustawienia', requiresAuth: true }
    },

    // Definiujemy każdą podstronę jako osobną trasę (nie zagnieżdżoną)
    {
        path: '/settings/profile',
        name: 'settings.profile',
        component: ProfileSettingsPage,
        meta: { title: 'Ustawienia Profilu', requiresAuth: true }
    },
    {
        path: '/settings/password',
        name: 'settings.password',
        component: () => import('@/pages/settings/Password.vue'),
        meta: { title: 'Zmiana Hasła', requiresAuth: true }
    },
    {
        path: '/settings/appearance',
        name: 'settings.appearance',
        component: () => import('@/pages/settings/Appearance.vue'),
        meta: { title: 'Wygląd Aplikacji', requiresAuth: true }
    },

    // Zachowujemy starą ścieżkę dla kompatybilności
    {
        path: '/profile/settings',
        name: 'profile.settings',
        component: ProfileSettingsPage,
        meta: { title: 'Ustawienia Profilu', requiresAuth: true }
    },

    {
        path: '/reset-password/:token',
        name: 'password.reset',
        component: ResetPasswordPage,
        props: true,
        meta: { title: 'Reset Hasła', requiresGuest: true }
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

const defaultTitle = 'Nova Med';
let authInitialized = false;

router.beforeEach(async (to, from, next) => {
    console.log(`Guard: przed nawigacją do ${String(to.name)}`);

    const authStore = useAuthStore();
    const { isLoggedIn } = storeToRefs(authStore);

    if (!authInitialized) {
        console.log('Inicjalizacja autoryzacji...');
        try {
            await authStore.initAuth();
            authInitialized = true;
            console.log('Autoryzacja zainicjowana');
        } catch (error) {
            console.error('Błąd inicjalizacji autoryzacji:', error);
        }
    }

    const requiresAuth = to.meta.requiresAuth === true;
    const requiresGuest = to.meta.requiresGuest === true;

    console.log(`Trasa ${to.path}: requiresAuth=${requiresAuth}, requiresGuest=${requiresGuest}, isLoggedIn=${isLoggedIn.value}`);

    if (requiresAuth && !isLoggedIn.value) {
        console.log(`Przekierowanie do logowania z ${to.path}`);
        next({ name: 'login' });
    } else if (requiresGuest && isLoggedIn.value) {
        console.log('Przekierowanie do panelu z logowania/rejestracji');
        next({ name: 'dashboard' });
    } else {
        console.log(`Przechodzę do ${String(to.name)}`);
        next();
    }
});

router.afterEach((to) => {
    document.title = to.meta.title ? `${to.meta.title} | ${defaultTitle}` : defaultTitle;
});

export default router;
