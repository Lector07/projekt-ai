// novamed/resources/js/router/index.ts
import {createRouter, createWebHistory, type RouteRecordRaw} from 'vue-router';
import {useAuthStore} from '@/stores/auth';
import HomePage from '../pages/Public/Welcome.vue';
import LoginPage from '../pages/auth/Login.vue';
import RegisterPage from '../pages/auth/Register.vue';
import DashboardPatientPage from '../pages/Patient/DashboardPatient.vue'; // Używane jako domyślny dashboard
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
import BookAppointmentPage from '@/pages/Patient/Appointments/BookAppointment.vue';
import DashboardDoctorPage from "@/pages/Doctor/DashboardDoctor.vue";
import DoctorAppointmentPage from "@/pages/Doctor/Appointments/DoctorAppointmentsListPage.vue";
import DoctorAppointmentDetailPage from "@/pages/Doctor/Appointments/DoctorAppointmentDetailPage.vue";
import DoctorScheduleEventPage from "@/pages/Doctor/Schedule/DoctorSchedulePage.vue";


const adminRoutes: Array<RouteRecordRaw> = [
    {
        path: '/admin/dashboard',
        name: 'admin.dashboard',
        component: DashboardAdmin,
        meta: { title: 'Statystyki', requiresAuth: true, requiresAdmin: true }
    },
    {
        path:'/admin/users',
        name:'admin.users',
        component: IndexPageUsers,
        meta: { title: 'Użytkownicy', requiresAuth: true, requiresAdmin: true }
    },
    {
        path: '/admin/doctors',
        name: 'admin.doctors',
        component: IndexPageDoctorsDoctors,
        meta: { title: 'Lekarze', requiresAuth: true, requiresAdmin: true }
    },
    {
        path: '/admin/procedures',
        name: 'admin.procedures',
        component: IndexPageProcedures,
        meta: { title: 'Zabiegi', requiresAuth: true, requiresAdmin: true }
    },
    {
        path: '/admin/procedure-categories',
        name: 'admin.procedure-categories',
        component: IndexPageCategories,
        meta: { title: 'Kategorie procedur', requiresAuth: true, requiresAdmin: true }
    },
    {
        path: '/admin/appointments',
        name: 'admin.appointments',
        component: IndexPageAppointments,
        meta: { title: 'Wizyty', requiresAuth: true, requiresAdmin: true }
    },
    {
        path: '/admin/doctors/:id',
        name: 'admin-doctor-details',
        component: DoctorDetailPageAdmin,
        props: true, // Dodaj props: true, aby ID było przekazywane jako prop
        meta: { title: 'Szczegóły lekarza', requiresAuth: true, requiresAdmin: true }
    },
    {
        path: '/admin/patients/:id',
        name: 'admin-patient-details',
        component: UserDetailPageAdmin,
        props: true,
        meta: { title: 'Szczegóły pacjenta', requiresAuth: true, requiresAdmin: true }
    },
    {
        path: '/admin/appointment/:id',
        name: 'admin-appointment-details',
        component: AppointmentDetailPageAdmin,
        props: true,
        meta: { title: 'Szczegóły wizyty', requiresAuth: true, requiresAdmin: true }
    },
];

const doctorRoutes: Array<RouteRecordRaw> = [
    {
        path: '/doctor/dashboard',
        name: 'doctor.dashboard', // Użyj tej nazwy w beforeEnter dla /dashboard
        component: DashboardDoctorPage, // Użyj poprawnej nazwy komponentu
        meta: {
            title: 'Panel Lekarza',
            requiresAuth: true,
            requiresDoctor: true, // Meta field dla ochrony trasy
        }
    },
    {
        path: '/doctor/appointments',
        name: 'doctor.appointments.index', // Nazwa, do której linkuje dashboard lekarza
        component: DoctorAppointmentPage,
        meta: { title: 'Moje Wizyty - Panel Lekarza', requiresAuth: true, requiresDoctor: true }
    },
    {
        path: '/doctor/appointments/:id', // :id to parametr ID wizyty
        name: 'doctor.appointments.show', // Nazwa używana np. w DoctorAppointmentsListPage.vue
        component: DoctorAppointmentDetailPage,
        props: true,
        meta: { title: 'Szczegóły Wizyty', requiresAuth: true, requiresDoctor: true }
    },
    {
        path: '/doctor/schedule/events',
        name: 'doctor.schedule.events',
        component: DoctorScheduleEventPage,
        meta: { title: 'Zarządzanie Grafikiem', requiresAuth: true, requiresDoctor: true }
    },
    // Tutaj możesz dodać inne trasy dla lekarza, np.:
    // { path: '/doctor/appointments', name: 'doctor.appointments.index', component: ..., meta: { requiresAuth: true, requiresDoctor: true } },
    // { path: '/doctor/appointments/:id', name: 'doctor.appointments.show', component: ..., meta: { requiresAuth: true, requiresDoctor: true } },
    // { path: '/doctor/profile', name: 'doctor.profile.show', component: ..., meta: { requiresAuth: true, requiresDoctor: true } },
];

const routes: Array<RouteRecordRaw> = [
    {path: '/', name: 'home', component: HomePage, meta: {title: 'Strona Główna'}},
    {path: '/login', name: 'login', component: LoginPage, meta: {title: 'Logowanie', requiresGuest: true}},
    {path: '/register', name: 'register', component: RegisterPage, meta: {title: 'Rejestracja', requiresGuest: true}},
    {
        path: '/dashboard',
        name: 'dashboard',
        component: DashboardPatientPage, // Domyślny komponent, jeśli rola nie jest admin/doctor
        meta: {
            requiresAuth: true,
            dynamicTitle: true
        },
        beforeEnter: (to, from, next) => {
            const authStore = useAuthStore();
            if (!authStore.user) { // Dodatkowe zabezpieczenie
                return next({ name: 'login' });
            }
            const userRole = (authStore.user as any).role;

            if (userRole === 'admin') {
                return next({ name: 'admin.dashboard' });
            } else if (userRole === 'doctor') {
                // Zamiast ustawiać tytuł tutaj i renderować DashboardPatientPage,
                // przekieruj na dedykowaną trasę dashboardu lekarza.
                return next({ name: 'doctor.dashboard' });
            } else { // patient
                to.meta.title = 'Panel Pacjenta';
                // Pozwól na załadowanie DashboardPatientPage (już zdefiniowany w 'component')
                next();
            }
        }
    },
    {
        path: '/settings',
        name: 'settings',
        redirect: {name: 'settings.profile'},
        meta: {title: 'Ustawienia', requiresAuth: true}
    },
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
    {
        path: '/profile/settings', // Stara ścieżka
        name: 'profile.settings',
        redirect: { name: 'settings.profile' } // Przekieruj na nową
    },
    {
        path: '/reset-password/:token',
        name: 'password.reset',
        component: ResetPasswordPage,
        props: true,
        meta: {title: 'Reset Hasła', requiresGuest: true}
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: ForgotPasswordPage,
        meta: {title: 'Zapomniałem Hasła', requiresGuest: true}
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
        props: true,
        meta: {title: 'Szczegóły zabiegu', requiresAuth: true}
    },
    {
        path: '/doctors/:id',
        name: 'doctor.detail',
        component: DoctorDetailPage,
        props: true,
        meta: {title: 'Szczegóły lekarza', requiresAuth: true} // Poprawka literówki
    },
    {
        path: '/patient/appointments',
        name: 'patient.appointments',
        component: PatientAppointmentsPage,
        meta: { title: 'Moje Wizyty', requiresAuth: true }
    },
    {
        path: '/patient/appointments/:id',
        name: 'patient.appointments.show',
        component: PatientAppointmentDetailPage,
        props: true,
        meta: { title: 'Szczegóły Wizyty', requiresAuth: true }
    },
    {
        path: '/patient/appointments/book',
        name: 'book.appointment',
        component: BookAppointmentPage, // Użyj poprawnej nazwy komponentu
        meta: {title: 'Rezerwacja Wizyty', requiresAuth: true}
    },
    ...adminRoutes,
    ...doctorRoutes // <<< --- DODAJ TRASY LEKARZA DO GŁÓWNEJ TABLICY
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

const defaultTitle = 'Nova Med';
let authInitialized = false;

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    // Nie używaj storeToRefs tutaj, jeśli isLoggedIn jest prostym getterem
    // const { isLoggedIn } = storeToRefs(authStore); // Można pominąć, jeśli odwołujesz się przez authStore.isLoggedIn

    if (!authInitialized) {
        console.log('Router Guard: Inicjalizacja autoryzacji...');
        try {
            await authStore.initAuth();
            authInitialized = true;
            console.log('Router Guard: Autoryzacja zainicjowana. Zalogowany:', authStore.isLoggedIn);
        } catch (error) {
            console.error('Router Guard: Błąd inicjalizacji autoryzacji:', error);
        }
    }

    const requiresAuth = to.meta.requiresAuth === true;
    const requiresGuest = to.meta.requiresGuest === true;
    const requiresAdmin = to.meta.requiresAdmin === true;
    const requiresDoctor = to.meta.requiresDoctor === true; // Odczytaj meta field

    const isAdmin = () => {
        if (!authStore.user) return false;
        return (authStore.user as any).role === 'admin'; // Uproszczone
    };

    const isDoctor = () => { // Zdefiniuj funkcję isDoctor
        if (!authStore.user) return false;
        return (authStore.user as any).role === 'doctor'; // Uproszczone
    };

    console.log(`Router Guard: Nawigacja do ${to.path}. Wymaga Auth: ${requiresAuth}, Zalogowany: ${authStore.isLoggedIn}, Wymaga Admin: ${requiresAdmin}, Jest Admin: ${isAdmin()}, Wymaga Doctor: ${requiresDoctor}, Jest Doctor: ${isDoctor()}`);

    if (requiresAuth && !authStore.isLoggedIn) {
        console.log(`Router Guard: Przekierowanie do logowania z ${to.path} (wymaga autoryzacji, brak zalogowania)`);
        next({name: 'login', query: { redirect: to.fullPath } }); // Przekaż ścieżkę przekierowania
    } else if (requiresGuest && authStore.isLoggedIn) {
        console.log('Router Guard: Przekierowanie do dashboardu (wymaga gościa, użytkownik zalogowany)');
        next({name: 'dashboard'});
    } else if (requiresAdmin && !isAdmin()) {
        console.log('Router Guard: Brak uprawnień admina, przekierowanie do dashboardu');
        next({name: 'dashboard'}); // Lub na stronę błędu 403/dostępu zabronionego
    } else if (requiresDoctor && !isDoctor()) { // <<< --- DODAJ SPRAWDZENIE DLA LEKARZA
        console.log('Router Guard: Brak uprawnień lekarza, przekierowanie do dashboardu');
        next({ name: 'dashboard' }); // Lub na stronę błędu 403/dostępu zabronionego
    }
    else {
        console.log(`Router Guard: Przechodzę do ${String(to.name || to.path)}`);
        next();
    }
});

router.afterEach((to) => {
    document.title = to.meta.title ? `${to.meta.title} | ${defaultTitle}` : defaultTitle;
});

export default router;
