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
import {BookOpen, Folder, LayoutGrid} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import "tailwindcss";

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        to: {name: 'dashboard'}, // Zmienione z href na to z nazwą trasy
        icon: LayoutGrid,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit', // Zewnętrzny link - href jest OK
        external: true, // Dodaj flagę dla zewnętrznych linków
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits', // Zewnętrzny link - href jest OK
        external: true, // Dodaj flagę dla zewnętrznych linków
        icon: BookOpen,
    },
];
</script>

<template>

    <Sidebar
        variant="inset"
        collapsible="icon"
        class="bg-[#001f54]"
    >
        <SidebarHeader class="bg-[#001f54] text-[#fefcfb]">
            <SidebarMenu class="bg-[#001f54]">
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <RouterLink :to="{ name: 'dashboard' }">
                            <AppLogo/>
                        </RouterLink>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="bg-[#001f54] text-[#fefcfb]">
            <NavMain :items="mainNavItems"/>
        </SidebarContent>

        <SidebarFooter class="bg-[#001f54] text-[#fefcfb]" >
            <NavFooter  :items="footerNavItems" class="text-[#fefcfb]"/>
            <NavUser/>
        </SidebarFooter>
    </Sidebar>
    <slot/>
</template>

