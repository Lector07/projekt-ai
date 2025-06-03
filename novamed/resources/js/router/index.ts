// novamed/resources/js/router/index.ts
import {createRouter, createWebHistory, type RouteRecordRaw} from 'vue-router';
import {useAuthStore} from '@/stores/auth';
import HomePage from '../pages/Public/Welcome.vue';
import LoginPage from '../pages/auth/Login.vue';
import RegisterPage from '../pages/auth/Register.vue';
import DashboardPatientPage from '../pages/Patient/DashboardPatient.vue';
import ProfileSettingsPage from '../pages/settings/Profile.vue';
import ResetPasswordPage from '../pages/auth/ResetPassword.vue';
import ForgotPasswordPage from '../pages/auth/ForgotPassword.vue';
import ProceduresPage from '../pages/SharedProtected/ProceduresPage.vue';
import {storeToRefs} from 'pinia';
import DoctorsPage from "@/pages/SharedProtected/DoctorsPage.vue";
import ProcedureDetailPage from "@/pages/SharedProtected/ProcedureDetailPage.vue";
import DoctorDetailPage from "@/pages/SharedProtected/DoctorDetailPage.vue";
import DashboardAdmin from "@/pages/Admin/DashboardAdmin.vue";
import IndexPageUsers from "@/pages/Admin/Users/IndexPageUsers.vue";
import IndexPageDoctorsDoctors from "@/pages/Admin/Doctors/IndexPageDoctors.vue";
import IndexPageProcedures from "@/pages/Admin/Procedures/IndexPageProcedures.vue";
import IndexPageCategories from "@/pages/Admin/ProcedureCategories/IndexPageCategories.vue";
import IndexPageAppointments from "@/pages/Admin/Appointments/IndexPageAppointments.vue";
import DoctorDetailPageAdmin from "@/pages/Admin/Doctors/DoctorDetailPageAdmin.vue";
import UserDetailPageAdmin from "@/pages/Admin/Users/UserDetailPageAdmin.vue";
import AppointmentDetailPageAdmin from "@/pages/Admin/Appointments/AppointmentDetailPageAdmin.vue";
import PatientAppointmentsPage from "@/pages/Patient/Appointments/PatientAppointmentsPage.vue";
import PatientAppointmentDetailPage from '@/pages/Patient/Appointments/AppointmentDetailPage.vue';
import BookAppointment from '@/pages/Patient/Appointments/BookAppointment.vue';


const adminRoutes: Array<RouteRecordRaw> = [
    {
        path: '/admin/dashboard',
        name: 'admin.dashboard',
        component: DashboardAdmin,
        meta: {
            title: 'Statystyki',
            requiresAuth: true,
            requiresAdmin: true
        }
    },
    {
        path:'/admin/users'
        , name:'admin.users',
        component: IndexPageUsers,
        meta: {
            title: 'Użytkownicy',
            requiresAuth: true,
            requiresAdmin: true
        }
    },
    {
        path: '/admin/doctors',
        name: 'admin.doctors',
        component: IndexPageDoctorsDoctors,
        meta: {
            title: 'Lekarze',
            requiresAuth: true,
            requiresAdmin: true
        }
    },
    {
        path: '/admin/procedures',
        name: 'admin.procedures',
        component: IndexPageProcedures,
        meta: {
            title: 'Zabiegi',
            requiresAuth: true,
            requiresAdmin: true
        }
    },
    {
        path: '/admin/procedure-categories',
        name: 'admin.procedure-categories',
        component: IndexPageCategories,
        meta: {
            title: 'Kategorie procedur',
            requiresAuth: true,
            requiresAdmin: true
        }
    },
    {
        path: '/admin/appointments',
        name: 'admin.appointments',
        component: IndexPageAppointments,
        meta: {
            title: 'Wizyty',
            requiresAuth: true,
            requiresAdmin: true
        }
    },
    {
        path: '/admin/doctors/:id',
        name: 'admin-doctor-details',
        component: DoctorDetailPageAdmin,
        meta: {
            title: 'Szczegóły lekarza',
            requiresAuth: true,
            requiresAdmin: true
        }
    },
    {
        path: '/admin/patients/:id',
        name: 'admin-patient-details',
        component: UserDetailPageAdmin,
        meta: {
            title: 'Szczegóły pacjenta',
            requiresAuth: true,
            requiresAdmin: true
        }
    },
    {
        path: '/admin/appointment/:id',
        name: 'admin-appointment-details',
        component: AppointmentDetailPageAdmin,
        meta: {
            title: 'Szczegóły wizyty',
            requiresAuth: true,
            requiresAdmin: true
        }
    },
];

const routes: Array<RouteRecordRaw> = [
    {path: '/', name: 'home', component: HomePage, meta: {title: 'Strona Główna'}},
    {path: '/login', name: 'login', component: LoginPage, meta: {title: 'Logowanie', requiresGuest: true}},
    {path: '/register', name: 'register', component: RegisterPage, meta: {title: 'Rejestracja', requiresGuest: true}},
    {
        path: '/dashboard',
        name: 'dashboard',
        component: DashboardPatientPage,
        meta: {
            requiresAuth: true,
            dynamicTitle: true
        },
        beforeEnter: (to, from, next) => {
            const authStore = useAuthStore();

            if (authStore.user) {
                const userRole =
                    (authStore.user as any).role ||
                    ((authStore.user as any).roles && (authStore.user as any).roles[0]) ||
                    'patient'; // Domyślna rola

                if (userRole === 'admin') {
                    return next({ name: 'admin.dashboard' });
                } else if (userRole === 'doctor') {
                    to.meta.title = 'Panel Lekarza';
                } else {
                    to.meta.title = 'Panel Pacjenta';
                }
            } else {
                to.meta.title = 'Panel';
            }

            next();
        }
    },
    {
        path: '/settings',
        name: 'settings',
        redirect: {name: 'settings.profile'},
        meta: {title: 'Ustawienia', requiresAuth: true}
    },

    // Definiujemy każdą podstronę jako osobną trasę (nie zagnieżdżoną)
    {
        path: '/settings/profile',
        name: 'settings.profile',
        component: ProfileSettingsPage,
        meta: {title: 'Ustawienia Profilu', requiresAuth: true}
    },
    {
        path: '/settings/password',
        name: 'settings.password',
        component: () => import('@/pages/settings/Password.vue'),
        meta: {title: 'Zmiana Hasła', requiresAuth: true}
    },
    {
        path: '/settings/appearance',
        name: 'settings.appearance',
        component: () => import('@/pages/settings/Appearance.vue'),
        meta: {title: 'Wygląd Aplikacji', requiresAuth: true}
    },

    // Zachowujemy starą ścieżkę dla kompatybilności
    {
        path: '/profile/settings',
        name: 'profile.settings',
        component: ProfileSettingsPage,
        meta: {title: 'Ustawienia Profilu', requiresAuth: true}
    },

    {
        path: '/reset-password/:token',
        name: 'password.reset',
        component: ResetPasswordPage,
        props: true,
        meta: {title: 'Reset Hasła', requiresGuest: true}
    },
    {
        path: '/forgot-password',      // Ścieżka URL
        name: 'forgot-password',     // <<< Nazwa, której szuka router-link
        component: ForgotPasswordPage, // Komponent do wyświetlenia
        meta: {title: 'Zapomniałem Hasła', requiresGuest: true} // Meta dane
    },
    {
        path: '/procedures',
        name: 'procedures',
        component: ProceduresPage,
        meta: {title: 'Zabiegi', requiresAuth: true}
    },
    {
        path: '/doctors',
        name: 'doctors',
        component: DoctorsPage,
        meta: {title: 'Lekarze', requiresAuth: true}
    },
    {
        path: '/procedures/:id',
        name: 'procedure.detail',
        component: ProcedureDetailPage,
        meta: {title: 'Szczegóły zabiegu', requiresAuth: true}
    },
    {
        path: '/doctors/:id',
        name: 'doctor.detail',
        component: DoctorDetailPage,
        meta: {title: 'Szegóły lekarza', requiresAuth: true}
    },
    {
        path: '/patient/appointments', // Ścieżka URL
        name: 'patient.appointments',  // Nazwa trasy (może być inna, np. 'my.appointments')
        component: PatientAppointmentsPage,
        meta: { title: 'Moje Wizyty', requiresAuth: true /*, requiresPatient: true - jeśli masz taki middleware */ }
    },
    {
        path: '/patient/appointments/:id', // :id to parametr ID wizyty
        name: 'patient.appointments.show', // Nazwa używana w router-link/push
        component: PatientAppointmentDetailPage,
        props: true, // Aby ID było przekazywane jako prop do komponentu
        meta: { title: 'Szczegóły Wizyty', requiresAuth: true }
    },
    {
        path: '/patient/appointments/book', // Ścieżka URL
        name: 'book.appointment',
        component: BookAppointment,
        meta: {title: 'Rezerwacja Wizyty', requiresAuth: true}
    },
    ...adminRoutes
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
    const {isLoggedIn} = storeToRefs(authStore);

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
    const requiresAdmin = to.meta.requiresAdmin === true;

    // Funkcja sprawdzająca czy użytkownik jest administratorem
    const isAdmin = () => {
        if (!authStore.user) return false;
        return (authStore.user as any).role === 'admin' ||
            ((authStore.user as any).roles && (authStore.user as any).roles.includes('admin'));
    };

    console.log(`Trasa ${to.path}: requiresAuth=${requiresAuth}, requiresGuest=${requiresGuest}, isLoggedIn=${isLoggedIn.value}`);

    if (requiresAuth && !isLoggedIn.value) {
        console.log(`Przekierowanie do logowania z ${to.path}`);
        next({name: 'login'});
    } else if (requiresGuest && isLoggedIn.value) {
        console.log('Przekierowanie do panelu z logowania/rejestracji');
        next({name: 'dashboard'});
    } else if (requiresAdmin && !isAdmin()) {
        console.log('Brak uprawnień administratora, przekierowanie do dashboard');
        next({name: 'dashboard'});
    } else {
        console.log(`Przechodzę do ${String(to.name)}`);
        next();
    }
});

router.afterEach((to) => {
    document.title = to.meta.title ? `${to.meta.title} | ${defaultTitle}` : defaultTitle;
});

export default router;
