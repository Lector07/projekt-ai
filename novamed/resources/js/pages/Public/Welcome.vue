<script setup lang="ts">
import {computed, onMounted, ref} from 'vue';
import {useAuthStore} from '@/stores/auth';
import axios from 'axios';
import {LoaderCircle} from 'lucide-vue-next';
import Heading from "@/components/Heading.vue";
import {Button} from '@/components/ui/button';
import {
    Pagination,
    PaginationEllipsis,
    PaginationFirst,
    PaginationPrevious,
    PaginationLast,
    PaginationNext
} from '@/components/ui/pagination';
import {PaginationList, PaginationListItem} from 'reka-ui';
import {Skeleton} from '@/components/ui/skeleton';

interface Procedure {
    id: number;
    name: string;
    description: string;
    base_price: number;
}

interface Doctor {
    id: number;
    title?: string;
    first_name: string;  // zmienione z name
    last_name: string;   // zmienione z surname
    specialization: string;
    bio: string;         // zmienione z description
}

const authStore = useAuthStore();

const isLoggedIn = computed(() => authStore.isLoggedIn);
const user = computed(() => authStore.user); // User nie jest używany, ale może być w przyszłości

// Dane z bazy z odpowiednim typowaniem
const procedures = ref<Procedure[]>([]);
const doctors = ref<Doctor[]>([]);
const loading = ref(true);
const error = ref(false);


const activeTab = ref(0);
const getInitials = (firstName: string, lastName: string): string => {
    return `${firstName.charAt(0)}${lastName.charAt(0)}`;
};


const doctorsCurrentPage = ref(1);
const doctorsLastPage = ref(1);
const doctorsMeta = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 6
});

// Paginacja dla procedur
const proceduresCurrentPage = ref(1);
const proceduresLastPage = ref(1);
const proceduresMeta = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 9
});

// Zmodyfikowana funkcja pobierania danych
const fetchData = async () => {
    loading.value = true;
    error.value = false;

    try {
        // Pobieramy tylko dane dla aktywnej zakładki
        if (activeTab.value === 0) {
            // Tylko procedury
            const proceduresResponse = await axios.get('/api/procedures', {
                params: {
                    page: proceduresCurrentPage.value,
                    per_page: proceduresMeta.value.per_page
                }
            });
            procedures.value = proceduresResponse.data.data;
            proceduresMeta.value = {
                current_page: proceduresResponse.data.current_page,
                last_page: proceduresResponse.data.last_page,
                total: proceduresResponse.data.total,
                per_page: proceduresResponse.data.per_page
            };
        } else {
            // Tylko lekarze
            const doctorsResponse = await axios.get('/api/doctors', {
                params: {
                    page: doctorsCurrentPage.value,
                    per_page: doctorsMeta.value.per_page
                }
            });
            doctors.value = doctorsResponse.data.data;
            doctorsMeta.value = {
                current_page: doctorsResponse.data.current_page,
                last_page: doctorsResponse.data.last_page,
                total: doctorsResponse.data.total,
                per_page: doctorsResponse.data.per_page
            };
        }
    } catch (e) {
        console.error('Błąd podczas pobierania danych:', e);
        error.value = true;
    } finally {
        loading.value = false;
    }
};

// Funkcje do obsługi zmiany zakładek
const changeTab = (index: number) => {
    if (activeTab.value !== index) {
        activeTab.value = index;
        // Pobierz dane dla nowej zakładki tylko jeśli jeszcze nie były pobrane
        if ((index === 0 && procedures.value.length === 0) ||
            (index === 1 && doctors.value.length === 0)) {
            fetchData();
        }
    }
};

// Funkcje do obsługi zmiany stron z zabezpieczeniem przed wielokrotnym kliknięciem
const changeDoctorsPage = (page: number) => {
    if (loading.value) return; // Zapobiega wielokrotnym żądaniom
    if (doctorsCurrentPage.value !== page) {
        doctorsCurrentPage.value = page;
        fetchData();
    }
};

const changeProceduresPage = (page: number) => {
    if (loading.value) return; // Zapobiega wielokrotnym żądaniom
    if (proceduresCurrentPage.value !== page) {
        proceduresCurrentPage.value = page;
        fetchData();
    }
};
onMounted(() => {
    fetchData();
});
</script>

<template>
    <div
        class="flex mt-14 min-h-screen flex-col items-center bg-nova-light p-6 text-[#1b1b18] lg:justify-center lg:p-8">
        <div
            class="duration-750 starting:opacity-0 flex w-full items-center justify-center opacity-100 transition-opacity lg:grow">
            <main
                class="flex w-full max-w-[550px] flex-col-reverse overflow-hidden rounded-lg min-h-[550px] lg:max-w-6xl lg:flex-row lg:min-h-[550px]">
                <div
                    class="flex-1 rounded-bl-lg rounded-br-lg bg-white p-6 pb-12 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] lg:rounded-br-none lg:rounded-tl-lg lg:p-20"
                >
                    <h1 class="mb-1 font-bold text-2xl">Witaj w <span class="text-nova-primary">NOVA</span><span
                        class="text-nova-accent">MED</span></h1>
                    <p class="mb-2 mt-4 text-algin text-[#706f6c] dark:text-[#A1A09A]">
                        W NOVAMED łączymy nowoczesną technologię z doświadczeniem, oferując najwyższy standard opieki
                        medycznej. Nasza misja to nie tylko poprawa wyglądu, ale przede wszystkim podniesienie jakości
                        życia naszych pacjentów.
                    </p>
                    <ul class="mb-2 flex flex-col  lg:mb-6"><span class="mb-1 text-base">Co możesz znaleźć w naszej aplikacji?</span>
                        <li
                            class="relative flex items-center gap-4 py-2 before:absolute before:bottom-8 before:left-[0.4rem] before:top-0 before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]"
                        >
                            <span class="text-[#706f6c] mt-1 dark:text-[#A1A09A]">Rejestracja online</span>
                        </li>
                        <li
                            class="relative flex items-center gap-4 py-2 before:absolute before:bottom-8 before:left-[0.4rem] before:top-0 before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]"
                        >
                            <span class="text-[#706f6c] mt-1 dark:text-[#A1A09A]">Bezpośredni kontakt z zespołem</span>
                        </li>
                        <li
                            class="relative flex items-center gap-4 py-2 before:absolute before:bottom-8 before:left-[0.4rem] before:top-0 before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]"
                        >
                            <span class="text-[#706f6c] mt-1 dark:text-[#A1A09A]">Informacje o zabiegach</span>
                        </li>
                    </ul>
                    <ul class="flex gap-3 text-sm leading-normal">
                        <li v-if="!isLoggedIn">
                            <router-link :to="{ name: 'login' }"
                                         class="inline-block rounded-sm border border-nova-primary px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-nova-accent dark:border-nova-primary dark:text-[#EDEDEC] dark:hover:border-nova-accent duration-100 ease-in transform transition-transform hover:scale-105"
                            >
                                Zaloguj się
                            </router-link>
                        </li>
                        <li v-if="!isLoggedIn">
                            <router-link :to="{ name: 'register' }"
                                         class="inline-block rounded-sm border border-nova-primary px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-nova-accent dark:border-nova-primary dark:text-[#EDEDEC] dark:hover:border-nova-accent duration-100 ease-in transform transition-transform hover:scale-105"
                            >
                                Zarejestruj się
                            </router-link>
                        </li>
                        <li v-if="isLoggedIn">
                            <router-link
                                :to="{ name: 'dashboard' }"
                                class="inline-block rounded-sm border border-nova-primary px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-nova-accent dark:border-nova-primary dark:text-[#EDEDEC] dark:hover:border-nova-accent duration-100 ease-in transform transition-transform hover:scale-105"
                            >
                                Strona główna
                            </router-link>
                        </li>
                    </ul>
                </div>
                <div
                    class="flex relative justify-center items-center -mb-px mx-auto aspect-335/335 w-full shrink overflow-hidden rounded-t-lg bg-nova-light dark:bg-nova-primary lg:-ml-px lg:mb-0 lg:aspect-auto lg:w-[438px] lg:rounded-r-lg lg:rounded-t-none"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        xmlns:xlink="http://www.w3.org/1999/xlink"
                        width="500"
                        zoomAndPan="magnify"
                        viewBox="0 0 600 599.999999"
                        height="500"
                        preserveAspectRatio="xMidYMid meet"
                        version="1.0"
                        class="transition-transform duration-1000 starting:scale-50 lg:scale-100"
                    >
                        <defs>
                            <g/>
                        </defs>
                        <g fill="#1282a2" fill-opacity="1">
                            <g transform="translate(62.445428, 451.838621)">
                                <g>
                                    <path
                                        d="M 46.4375 0 L 46.375 -27.09375 L 33.09375 -4.765625 L 28.375 -4.765625 L 15.15625 -26.515625 L 15.15625 0 L 5.359375 0 L 5.359375 -45.15625 L 14 -45.15625 L 30.890625 -17.09375 L 47.53125 -45.15625 L 56.109375 -45.15625 L 56.25 0 Z M 46.4375 0 "
                                    />
                                </g>
                            </g>
                        </g>
                        <g fill="#1282a2" fill-opacity="1">
                            <g transform="translate(125.2619, 451.838621)">
                                <g>
                                    <path
                                        d="M 40.3125 -8.390625 L 40.3125 0 L 5.359375 0 L 5.359375 -45.15625 L 39.46875 -45.15625 L 39.46875 -36.765625 L 15.734375 -36.765625 L 15.734375 -26.96875 L 36.703125 -26.96875 L 36.703125 -18.828125 L 15.734375 -18.828125 L 15.734375 -8.390625 Z M 40.3125 -8.390625 "
                                    />
                                </g>
                            </g>
                        </g>
                        <g fill="#1282a2" fill-opacity="1">
                            <g transform="translate(169.762286, 451.838621)">
                                <g>
                                    <path
                                        d="M 5.359375 -45.15625 L 25.859375 -45.15625 C 30.765625 -45.15625 35.097656 -44.21875 38.859375 -42.34375 C 42.617188 -40.46875 45.539062 -37.832031 47.625 -34.4375 C 49.71875 -31.039062 50.765625 -27.085938 50.765625 -22.578125 C 50.765625 -18.054688 49.71875 -14.097656 47.625 -10.703125 C 45.539062 -7.304688 42.617188 -4.671875 38.859375 -2.796875 C 35.097656 -0.929688 30.765625 0 25.859375 0 L 5.359375 0 Z M 25.34375 -8.578125 C 29.863281 -8.578125 33.46875 -9.832031 36.15625 -12.34375 C 38.84375 -14.863281 40.1875 -18.273438 40.1875 -22.578125 C 40.1875 -26.878906 38.84375 -30.285156 36.15625 -32.796875 C 33.46875 -35.316406 29.863281 -36.578125 25.34375 -36.578125 L 15.796875 -36.578125 L 15.796875 -8.578125 Z M 25.34375 -8.578125 "
                                    />
                                </g>
                            </g>
                        </g>
                        <path
                            stroke-linecap="butt"
                            transform="matrix(0.75, 0, 0, 0.75, 264.792469, 108.750015)"
                            fill="none"
                            stroke-linejoin="miter"
                            d="M 295.156929 401.999997 L 294.334012 245.953115 L 218.672551 372.947912 L 181.610049 372.947912 L 106.360046 250.161449 L 106.360046 401.999997 L 29.464209 401.999997 L 29.464209 107.661442 L 97.943379 107.661442 L 201.427758 277.572908 L 302.344429 107.661442 L 370.922557 107.661442 L 371.745474 401.999997 Z M 295.156929 401.999997 "
                            stroke="#1282a2"
                            stroke-width="13.666802"
                            stroke-opacity="1"
                            stroke-miterlimit="4"
                        />
                        <g fill="#001f54" fill-opacity="1">
                            <g transform="translate(249.544266, 468.483984)">
                                <g>
                                    <path
                                        d="M 17.46875 -5.15625 L 6.3125 -5.15625 L 4.109375 0 L -0.15625 0 L 9.875 -22.3125 L 13.96875 -22.3125 L 24.03125 0 L 19.703125 0 Z M 16.09375 -8.421875 L 11.890625 -18.171875 L 7.71875 -8.421875 Z M 16.09375 -8.421875 "
                                    />
                                </g>
                            </g>
                        </g>
                        <g fill="#001f54" fill-opacity="1">
                            <g transform="translate(284.278187, 468.483984)">
                                <g>
                                    <path
                                        d="M 19.734375 -3.46875 L 19.734375 0 L 3 0 L 3 -22.3125 L 19.28125 -22.3125 L 19.28125 -18.84375 L 7.140625 -18.84375 L 7.140625 -13.03125 L 17.90625 -13.03125 L 17.90625 -9.625 L 7.140625 -9.625 L 7.140625 -3.46875 Z M 19.734375 -3.46875 "
                                    />
                                </g>
                            </g>
                        </g>
                        <g fill="#001f54" fill-opacity="1">
                            <g transform="translate(316.494923, 468.483984)">
                                <g>
                                    <path
                                        d="M 9.90625 0.3125 C 8.1875 0.3125 6.523438 0.0703125 4.921875 -0.40625 C 3.316406 -0.894531 2.046875 -1.546875 1.109375 -2.359375 L 2.546875 -5.578125 C 3.460938 -4.859375 4.582031 -4.269531 5.90625 -3.8125 C 7.238281 -3.351562 8.570312 -3.125 9.90625 -3.125 C 11.570312 -3.125 12.8125 -3.390625 13.625 -3.921875 C 14.445312 -4.453125 14.859375 -5.15625 14.859375 -6.03125 C 14.859375 -6.664062 14.628906 -7.1875 14.171875 -7.59375 C 13.710938 -8.007812 13.128906 -8.332031 12.421875 -8.5625 C 11.722656 -8.800781 10.769531 -9.070312 9.5625 -9.375 C 7.863281 -9.78125 6.488281 -10.179688 5.4375 -10.578125 C 4.382812 -10.984375 3.476562 -11.613281 2.71875 -12.46875 C 1.96875 -13.332031 1.59375 -14.5 1.59375 -15.96875 C 1.59375 -17.195312 1.925781 -18.316406 2.59375 -19.328125 C 3.269531 -20.335938 4.28125 -21.140625 5.625 -21.734375 C 6.976562 -22.328125 8.628906 -22.625 10.578125 -22.625 C 11.941406 -22.625 13.28125 -22.453125 14.59375 -22.109375 C 15.914062 -21.773438 17.054688 -21.289062 18.015625 -20.65625 L 16.703125 -17.4375 C 15.722656 -18.007812 14.703125 -18.441406 13.640625 -18.734375 C 12.578125 -19.035156 11.546875 -19.1875 10.546875 -19.1875 C 8.910156 -19.1875 7.691406 -18.910156 6.890625 -18.359375 C 6.097656 -17.804688 5.703125 -17.070312 5.703125 -16.15625 C 5.703125 -15.519531 5.929688 -15 6.390625 -14.59375 C 6.847656 -14.195312 7.425781 -13.878906 8.125 -13.640625 C 8.832031 -13.410156 9.789062 -13.144531 11 -12.84375 C 12.65625 -12.457031 14.015625 -12.054688 15.078125 -11.640625 C 16.140625 -11.234375 17.046875 -10.601562 17.796875 -9.75 C 18.554688 -8.90625 18.9375 -7.757812 18.9375 -6.3125 C 18.9375 -5.082031 18.597656 -3.96875 17.921875 -2.96875 C 17.253906 -1.96875 16.238281 -1.171875 14.875 -0.578125 C 13.519531 0.015625 11.863281 0.3125 9.90625 0.3125 Z M 9.90625 0.3125 "
                                    />
                                </g>
                            </g>
                        </g>
                        <g fill="#001f54" fill-opacity="1">
                            <g transform="translate(347.405254, 468.483984)">
                                <g>
                                    <path
                                        d="M 7.515625 -18.8125 L 0.125 -18.8125 L 0.125 -22.3125 L 19.0625 -22.3125 L 19.0625 -18.8125 L 11.671875 -18.8125 L 11.671875 0 L 7.515625 0 Z M 7.515625 -18.8125 "
                                    />
                                </g>
                            </g>
                        </g>
                        <g fill="#001f54" fill-opacity="1">
                            <g transform="translate(377.455286, 468.483984)">
                                <g>
                                    <path
                                        d="M 22.828125 -22.3125 L 22.828125 0 L 18.671875 0 L 18.671875 -9.5625 L 7.140625 -9.5625 L 7.140625 0 L 3 0 L 3 -22.3125 L 7.140625 -22.3125 L 7.140625 -13.09375 L 18.671875 -13.09375 L 18.671875 -22.3125 Z M 22.828125 -22.3125 "
                                    />
                                </g>
                            </g>
                        </g>
                        <g fill="#1282a2" fill-opacity="1">
                            <g transform="translate(414.134103, 468.483984)">
                                <g>
                                    <path
                                        d="M 19.734375 -3.46875 L 19.734375 0 L 3 0 L 3 -22.3125 L 19.28125 -22.3125 L 19.28125 -18.84375 L 7.140625 -18.84375 L 7.140625 -13.03125 L 17.90625 -13.03125 L 17.90625 -9.625 L 7.140625 -9.625 L 7.140625 -3.46875 Z M 19.734375 -3.46875 "
                                    />
                                </g>
                            </g>
                        </g>
                        <g fill="#1282a2" fill-opacity="1">
                            <g transform="translate(446.350839, 468.483984)">
                                <g>
                                    <path
                                        d="M 7.515625 -18.8125 L 0.125 -18.8125 L 0.125 -22.3125 L 19.0625 -22.3125 L 19.0625 -18.8125 L 11.671875 -18.8125 L 11.671875 0 L 7.515625 0 Z M 7.515625 -18.8125 "
                                    />
                                </g>
                            </g>
                        </g>
                        <g fill="#1282a2" fill-opacity="1">
                            <g transform="translate(476.400877, 468.483984)">
                                <g>
                                    <path d="M 3 -22.3125 L 7.140625 -22.3125 L 7.140625 0 L 3 0 Z M 3 -22.3125 "/>
                                </g>
                            </g>
                        </g>
                        <g fill="#1282a2" fill-opacity="1">
                            <g transform="translate(497.433623, 468.483984)">
                                <g>
                                    <path
                                        d="M 13.328125 0.3125 C 11.066406 0.3125 9.03125 -0.175781 7.21875 -1.15625 C 5.40625 -2.144531 3.984375 -3.507812 2.953125 -5.25 C 1.921875 -7 1.40625 -8.96875 1.40625 -11.15625 C 1.40625 -13.34375 1.925781 -15.304688 2.96875 -17.046875 C 4.007812 -18.796875 5.4375 -20.160156 7.25 -21.140625 C 9.070312 -22.128906 11.109375 -22.625 13.359375 -22.625 C 15.179688 -22.625 16.847656 -22.304688 18.359375 -21.671875 C 19.867188 -21.035156 21.144531 -20.113281 22.1875 -18.90625 L 19.5 -16.390625 C 17.882812 -18.128906 15.898438 -19 13.546875 -19 C 12.015625 -19 10.644531 -18.660156 9.4375 -17.984375 C 8.226562 -17.316406 7.28125 -16.390625 6.59375 -15.203125 C 5.914062 -14.015625 5.578125 -12.664062 5.578125 -11.15625 C 5.578125 -9.644531 5.914062 -8.296875 6.59375 -7.109375 C 7.28125 -5.921875 8.226562 -4.988281 9.4375 -4.3125 C 10.644531 -3.644531 12.015625 -3.3125 13.546875 -3.3125 C 15.898438 -3.3125 17.882812 -4.191406 19.5 -5.953125 L 22.1875 -3.40625 C 21.144531 -2.195312 19.863281 -1.273438 18.34375 -0.640625 C 16.820312 -0.00390625 15.148438 0.3125 13.328125 0.3125 Z M 13.328125 0.3125 "
                                    />
                                </g>
                            </g>
                        </g>
                        <g fill="#1282a2" fill-opacity="1">
                            <g transform="translate(531.46656, 468.483984)">
                                <g>
                                    <path
                                        d="M 9.90625 0.3125 C 8.1875 0.3125 6.523438 0.0703125 4.921875 -0.40625 C 3.316406 -0.894531 2.046875 -1.546875 1.109375 -2.359375 L 2.546875 -5.578125 C 3.460938 -4.859375 4.582031 -4.269531 5.90625 -3.8125 C 7.238281 -3.351562 8.570312 -3.125 9.90625 -3.125 C 11.570312 -3.125 12.8125 -3.390625 13.625 -3.921875 C 14.445312 -4.453125 14.859375 -5.15625 14.859375 -6.03125 C 14.859375 -6.664062 14.628906 -7.1875 14.171875 -7.59375 C 13.710938 -8.007812 13.128906 -8.332031 12.421875 -8.5625 C 11.722656 -8.800781 10.769531 -9.070312 9.5625 -9.375 C 7.863281 -9.78125 6.488281 -10.179688 5.4375 -10.578125 C 4.382812 -10.984375 3.476562 -11.613281 2.71875 -12.46875 C 1.96875 -13.332031 1.59375 -14.5 1.59375 -15.96875 C 1.59375 -17.195312 1.925781 -18.316406 2.59375 -19.328125 C 3.269531 -20.335938 4.28125 -21.140625 5.625 -21.734375 C 6.976562 -22.328125 8.628906 -22.625 10.578125 -22.625 C 11.941406 -22.625 13.28125 -22.453125 14.59375 -22.109375 C 15.914062 -21.773438 17.054688 -21.289062 18.015625 -20.65625 L 16.703125 -17.4375 C 15.722656 -18.007812 14.703125 -18.441406 13.640625 -18.734375 C 12.578125 -19.035156 11.546875 -19.1875 10.546875 -19.1875 C 8.910156 -19.1875 7.691406 -18.910156 6.890625 -18.359375 C 6.097656 -17.804688 5.703125 -17.070312 5.703125 -16.15625 C 5.703125 -15.519531 5.929688 -15 6.390625 -14.59375 C 6.847656 -14.195312 7.425781 -13.878906 8.125 -13.640625 C 8.832031 -13.410156 9.789062 -13.144531 11 -12.84375 C 12.65625 -12.457031 14.015625 -12.054688 15.078125 -11.640625 C 16.140625 -11.234375 17.046875 -10.601562 17.796875 -9.75 C 18.554688 -8.90625 18.9375 -7.757812 18.9375 -6.3125 C 18.9375 -5.082031 18.597656 -3.96875 17.921875 -2.96875 C 17.253906 -1.96875 16.238281 -1.171875 14.875 -0.578125 C 13.519531 0.015625 11.863281 0.3125 9.90625 0.3125 Z M 9.90625 0.3125 "
                                    />
                                </g>
                            </g>
                        </g>
                        <path
                            stroke-linecap="butt"
                            transform="matrix(0.75, 0, 0, 0.75, 35.515835, 23.264766)"
                            fill="none"
                            stroke-linejoin="miter"
                            d="M 309.530983 107.662608 L 309.530983 402.001162 L 240.952855 402.001162 L 110.978891 245.131364 L 110.978891 402.001162 L 29.463263 402.001162 L 29.463263 107.662608 L 97.942432 107.662608 L 227.911188 264.532406 L 227.911188 107.662608 Z M 309.530983 107.662608 "
                            stroke="#001f54"
                            stroke-width="13.666802"
                            stroke-opacity="1"
                            stroke-miterlimit="4"
                        />
                        <path
                            stroke-linecap="butt"
                            transform="matrix(0.75, 0.000000000000000318, -0.000000000000000318, 0.75, 62.952192, 336.072692)"
                            fill="none"
                            stroke-linejoin="miter"
                            d="M 0.00124398 5.502036 L 267.787714 5.502036 "
                            stroke="#1282a2"
                            stroke-width="11"
                            stroke-opacity="1"
                            stroke-miterlimit="4"
                        />
                        <path
                            stroke-linecap="butt"
                            transform="matrix(0.75, 0.000000000000001038, -0.000000000000001038, 0.75, 292.316589, 420.742082)"
                            fill="none"
                            stroke-linejoin="miter"
                            d="M -0.000244237 5.500141 L 328.629978 5.500141 "
                            stroke="#001f54"
                            stroke-width="11"
                            stroke-opacity="1"
                            stroke-miterlimit="4"
                        />
                        <g fill="#001f54" fill-opacity="1">
                            <g transform="translate(61.536322, 400.677696)">
                                <g>
                                    <path
                                        d="M 46.765625 -45.15625 L 46.765625 0 L 38.1875 0 L 15.671875 -27.40625 L 15.671875 0 L 5.359375 0 L 5.359375 -45.15625 L 14 -45.15625 L 36.4375 -17.734375 L 36.4375 -45.15625 Z M 46.765625 -45.15625 "
                                    />
                                </g>
                            </g>
                        </g>
                        <g fill="#001f54" fill-opacity="1">
                            <g transform="translate(114.872277, 400.677696)">
                                <g>
                                    <path
                                        d="M 27.21875 0.78125 C 22.53125 0.78125 18.304688 -0.226562 14.546875 -2.25 C 10.785156 -4.269531 7.835938 -7.054688 5.703125 -10.609375 C 3.578125 -14.160156 2.515625 -18.148438 2.515625 -22.578125 C 2.515625 -27.003906 3.578125 -30.988281 5.703125 -34.53125 C 7.835938 -38.082031 10.785156 -40.867188 14.546875 -42.890625 C 18.304688 -44.910156 22.53125 -45.921875 27.21875 -45.921875 C 31.90625 -45.921875 36.117188 -44.910156 39.859375 -42.890625 C 43.597656 -40.867188 46.539062 -38.082031 48.6875 -34.53125 C 50.84375 -30.988281 51.921875 -27.003906 51.921875 -22.578125 C 51.921875 -18.148438 50.84375 -14.160156 48.6875 -10.609375 C 46.539062 -7.054688 43.597656 -4.269531 39.859375 -2.25 C 36.117188 -0.226562 31.90625 0.78125 27.21875 0.78125 Z M 27.21875 -8.125 C 29.882812 -8.125 32.289062 -8.734375 34.4375 -9.953125 C 36.59375 -11.179688 38.28125 -12.890625 39.5 -15.078125 C 40.726562 -17.273438 41.34375 -19.773438 41.34375 -22.578125 C 41.34375 -25.367188 40.726562 -27.859375 39.5 -30.046875 C 38.28125 -32.242188 36.59375 -33.953125 34.4375 -35.171875 C 32.289062 -36.398438 29.882812 -37.015625 27.21875 -37.015625 C 24.550781 -37.015625 22.140625 -36.398438 19.984375 -35.171875 C 17.835938 -33.953125 16.148438 -32.242188 14.921875 -30.046875 C 13.703125 -27.859375 13.09375 -25.367188 13.09375 -22.578125 C 13.09375 -19.773438 13.703125 -17.273438 14.921875 -15.078125 C 16.148438 -12.890625 17.835938 -11.179688 19.984375 -9.953125 C 22.140625 -8.734375 24.550781 -8.125 27.21875 -8.125 Z M 27.21875 -8.125 "
                                    />
                                </g>
                            </g>
                        </g>
                        <g fill="#001f54" fill-opacity="1">
                            <g transform="translate(170.530002, 400.677696)">
                                <g>
                                    <path
                                        d="M 48.765625 -45.15625 L 29.21875 0 L 18.90625 0 L -0.578125 -45.15625 L 10.703125 -45.15625 L 24.453125 -12.90625 L 38.375 -45.15625 Z M 48.765625 -45.15625 "
                                    />
                                </g>
                            </g>
                        </g>
                        <g fill="#001f54" fill-opacity="1">
                            <g transform="translate(219.867391, 400.677696)">
                                <g>
                                    <path
                                        d="M 35.09375 -9.671875 L 14.125 -9.671875 L 10.125 0 L -0.578125 0 L 19.546875 -45.15625 L 29.859375 -45.15625 L 50.046875 0 L 39.09375 0 Z M 31.796875 -17.609375 L 24.640625 -34.890625 L 17.484375 -17.609375 Z M 31.796875 -17.609375 "
                                    />
                                </g>
                            </g>
                        </g>
                    </svg>
                    <div
                        class="absolute inset-0 rounded-t-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] lg:overflow-hidden lg:rounded-r-lg lg:rounded-t-none"
                    />
                </div>
            </main>
        </div>

        <div class="w-full max-w-8xl mx-auto mt-16 px-2 pb-16">
            <div v-if="error" class="text-center text-red-500 py-12 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <div class="text-xl font-semibold mb-2">Wystąpił błąd</div>
                <p>Nie udało się pobrać danych. Spróbuj odświeżyć stronę.</p>
            </div>

            <div v-else class="relative">
                <div class="flex flex-wrap justify-center mb-8 border-b border-gray-200 dark:border-gray-700">
                    <button
                        v-for="(tab, index) in ['Procedury Medyczne', 'Nasi Specjaliści']"
                        :key="index"
                        @click="changeTab(index)"
                        class="px-6 py-3 text-base font-medium transition-all duration-200 border-b-2 focus:outline-none"
                        :class="[
        activeTab === index
            ? 'border-nova-primary text-nova-primary'
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
    ]"
                        :disabled="loading"
                    >
                        {{ tab }}
                    </button>
                </div>

                <!-- Zakładka procedur -->
                <div v-show="activeTab === 0" class="transition-opacity duration-300">
                    <!-- Skeleton loader dla procedur -->
                    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="i in 9" :key="i"
                             class="bg-white dark:bg-neutral-800 rounded-lg overflow-hidden shadow-md border border-gray-100 dark:border-neutral-700">
                            <div class="p-5">
                                <div class="flex justify-between items-start mb-3">
                                    <Skeleton class="h-6 w-2/3"/>
                                    <Skeleton class="h-8 w-20 rounded-full"/>
                                </div>
                                <Skeleton class="h-4 w-full mb-2"/>
                                <Skeleton class="h-4 w-5/6 mb-2"/>
                                <Skeleton class="h-4 w-3/4"/>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="procedures.length === 0"
                         class="text-center bg-gray-50 dark:bg-neutral-800 rounded-lg p-8">
                        <div class="text-gray-500 dark:text-gray-400">Brak dostępnych procedur</div>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div
                            v-for="procedure in procedures"
                            :key="procedure.id"
                            class="bg-white dark:bg-neutral-800 rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 dark:border-neutral-700"
                        >
                            <div class="p-5">
                                <div class="flex justify-between items-start">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{
                                            procedure.name
                                        }}</h3>
                                    <span
                                        class="bg-nova-primary/10 text-nova-primary px-6 py-2 rounded-full text-sm font-medium">
                                {{ procedure.base_price }} zł
                            </span>
                                </div>
                                <p class="mt-3 text-gray-600 dark:text-gray-300">{{ procedure.description }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="!loading && procedures.length > 0 && proceduresMeta && proceduresMeta.last_page > 1"
                         class="mt-8 flex justify-center">
                        <Pagination
                            :items-per-page="proceduresMeta.per_page"
                            :total="proceduresMeta.total"
                            :sibling-count="1"
                            show-edges
                            :default-page="proceduresMeta.current_page"
                            @update:page="changeProceduresPage"
                        >
                            <PaginationList v-slot="{ items }" class="flex items-center gap-1">
                                <PaginationFirst @click="changeProceduresPage(1)"
                                                 class="dark:bg-gray-700 dark:text-white dark:border-gray-600"
                                                 :disabled="loading"/>
                                <PaginationPrevious
                                    @click="changeProceduresPage(Math.max(1, proceduresMeta.current_page - 1))"
                                    class="dark:bg-gray-700 dark:text-white dark:border-gray-600" :disabled="loading"/>

                                <template v-for="(item, index) in items" :key="index">
                                    <PaginationListItem v-if="item.type === 'page'" :value="item.value" as-child>
                                        <Button
                                            :variant="proceduresMeta.current_page === item.value ? 'default' : 'outline'"
                                            :class="proceduresMeta.current_page === item.value ? 'bg-nova-primary hover:bg-nova-accent text-white' : 'dark:bg-gray-700 dark:text-white dark:border-gray-600'"
                                            size="sm"
                                            :disabled="loading"
                                        >
                                            {{ item.value }}
                                        </Button>
                                    </PaginationListItem>
                                    <PaginationEllipsis v-else :index="index" class="dark:text-gray-400"/>
                                </template>

                                <PaginationNext
                                    @click="changeProceduresPage(Math.min(proceduresMeta.last_page, proceduresMeta.current_page + 1))"
                                    class="dark:bg-gray-700 dark:text-white dark:border-gray-600" :disabled="loading"/>
                                <PaginationLast @click="changeProceduresPage(proceduresMeta.last_page)"
                                                class="dark:bg-gray-700 dark:text-white dark:border-gray-600"
                                                :disabled="loading"/>
                            </PaginationList>
                        </Pagination>
                    </div>
                </div>

                <!-- Zakładka specjalistów -->
                <div v-show="activeTab === 1" class="transition-opacity duration-300">
                    <!-- Skeleton loader dla specjalistów -->
                    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="i in 6" :key="i"
                             class="bg-white dark:bg-neutral-800 rounded-lg overflow-hidden shadow-md border border-gray-100 dark:border-neutral-700">
                            <!-- Gradient header -->
                            <Skeleton class="h-32 w-full"/>
                            <div class="relative p-5">
                                <!-- Avatar/inicjały -->
                                <div class="absolute -top-10 left-5">
                                    <Skeleton class="w-16 h-16 rounded-full"/>
                                </div>
                                <div class="ml-20">
                                    <!-- Imię i nazwisko lekarza -->
                                    <Skeleton class="h-6 w-40 mb-2"/>
                                    <!-- Specjalizacja -->
                                    <Skeleton class="h-4 w-24"/>
                                </div>
                                <!-- Bio/opis -->
                                <div class="mt-4">
                                    <Skeleton class="h-4 w-full mb-2"/>
                                    <Skeleton class="h-4 w-3/4 mb-2"/>
                                    <Skeleton class="h-4 w-5/6"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="doctors.length === 0"
                         class="text-center bg-gray-50 dark:bg-neutral-800 rounded-lg p-8">
                        <div class="text-gray-500 dark:text-gray-400">Brak dostępnych specjalistów</div>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div
                            v-for="doctor in doctors"
                            :key="doctor.id"
                            class="bg-white dark:bg-neutral-800 rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 border border-gray-100 dark:border-neutral-700"
                        >
                            <div class="h-32 bg-gradient-to-r from-nova-primary/20 to-nova-accent/20"></div>
                            <div class="relative p-5">
                                <div class="absolute -top-10 left-5">
                                    <div
                                        class="w-16 h-16 rounded-full bg-nova-primary/20 flex items-center justify-center text-nova-primary text-xl font-bold">
                                        {{ getInitials(doctor.first_name, doctor.last_name) }}
                                    </div>
                                </div>
                                <div class="ml-20">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ doctor.title || 'Dr' }} {{ doctor.first_name }} {{ doctor.last_name }}
                                    </h3>
                                    <span class="text-nova-primary">{{ doctor.specialization }}</span>
                                </div>
                                <p class="mt-4 text-gray-600 dark:text-gray-300 line-clamp-3">{{ doctor.bio }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="!loading && doctors.length > 0 && doctorsMeta && doctorsMeta.last_page > 1"
                         class="mt-6 flex justify-center">
                        <Pagination
                            :items-per-page="doctorsMeta.per_page"
                            :total="doctorsMeta.total"
                            :sibling-count="1"
                            show-edges
                            :default-page="doctorsMeta.current_page"
                            @update:page="changeDoctorsPage"
                        >
                            <PaginationList v-slot="{ items }" class="flex items-center gap-1">
                                <PaginationFirst @click="changeDoctorsPage(1)"
                                                 class="dark:bg-gray-700 dark:text-white dark:border-gray-600"
                                                 :disabled="loading"/>
                                <PaginationPrevious
                                    @click="changeDoctorsPage(Math.max(1, doctorsMeta.current_page - 1))"
                                    class="dark:bg-gray-700 dark:text-white dark:border-gray-600" :disabled="loading"/>

                                <template v-for="(item, index) in items" :key="index">
                                    <PaginationListItem v-if="item.type === 'page'" :value="item.value" as-child>
                                        <Button
                                            :variant="doctorsMeta.current_page === item.value ? 'default' : 'outline'"
                                            :class="doctorsMeta.current_page === item.value ? 'bg-nova-primary hover:bg-nova-accent text-white' : 'dark:bg-gray-700 dark:text-white dark:border-gray-600'"
                                            size="sm"
                                            :disabled="loading"
                                        >
                                            {{ item.value }}
                                        </Button>
                                    </PaginationListItem>
                                    <PaginationEllipsis v-else :index="index" class="dark:text-gray-400"/>
                                </template>

                                <PaginationNext
                                    @click="changeDoctorsPage(Math.min(doctorsMeta.last_page, doctorsMeta.current_page + 1))"
                                    class="dark:bg-gray-700 dark:text-white dark:border-gray-600" :disabled="loading"/>
                                <PaginationLast @click="changeDoctorsPage(doctorsMeta.last_page)"
                                                class="dark:bg-gray-700 dark:text-white dark:border-gray-600"
                                                :disabled="loading"/>
                            </PaginationList>
                        </Pagination>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
