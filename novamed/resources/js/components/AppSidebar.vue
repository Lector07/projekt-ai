<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem
} from '@/components/ui/sidebar';
import {type NavItem} from '@/types';
import {BookOpen, Folder, LayoutGrid, Slice, Contact, LayoutDashboard, Users, Stethoscope, ClipboardList, Calendar} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { useAuthStore } from '@/stores/auth';
import { computed } from 'vue';

const authStore = useAuthStore();

// Sprawdzanie czy użytkownik jest administratorem
const isAdmin = computed(() => {
    if (!authStore.user) return false;
    return (authStore.user as any).role === 'admin' ||
        ((authStore.user as any).roles && (authStore.user as any).roles.includes('admin'));
});

const isPatient = computed(() => {
    if (!authStore.user) return false;
    return (authStore.user as any).role === 'patient' ||
        ((authStore.user as any).roles && (authStore.user as any).roles.includes('patient'));
});

const mainNavItems: NavItem[] = [
    {
        title: 'Strona główna',
        to: {name: 'dashboard'}, // Zmienione z href na to z nazwą trasy
        icon: LayoutGrid,
    },
    {
        title: 'Zabiegi',
        to: {name: 'procedures'},
        icon: Slice,
    },
    {
        title: 'Lekarze',
        to: {name: 'doctors'},
        icon: Contact,
    },
];

// Linki administracyjne
const adminNavItems: NavItem[] = [
    {
        title: 'Panel Admina',
        to: { name: 'admin.dashboard' },
        icon: LayoutDashboard,
    },
    {
        title: 'Użytkownicy',
        to: { name: 'admin.users' },
        icon: Users,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/Lector07/projekt-ai', // Zewnętrzny link - href jest OK
        external: true, // Dodaj flagę dla zewnętrznych linków
        icon: Folder,
    },
];
</script>

<template>
    <Sidebar
        variant="inset"
        collapsible="icon"
        class="text-nova-light"
    >
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <RouterLink :to="{ name: 'dashboard' }">
                            <AppLogo/>
                        </RouterLink>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain v-if="isPatient" :items="mainNavItems"/>
            <NavMain v-if="isAdmin" :items="adminNavItems" label="Administracja" class="mt-4"/>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems"/>
            <NavUser/>
        </SidebarFooter>
    </Sidebar>
    <slot/>
</template>
