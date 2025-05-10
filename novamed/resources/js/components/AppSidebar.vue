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
import {BookOpen, Folder, LayoutGrid, Slice, Contact} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';


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
            <NavMain :items="mainNavItems"/>
        </SidebarContent>

        <SidebarFooter >
            <NavFooter :items="footerNavItems"/>
            <NavUser/>
        </SidebarFooter>
    </Sidebar>
    <slot/>
</template>

