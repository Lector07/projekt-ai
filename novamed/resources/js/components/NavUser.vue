<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { ChevronsUpDown } from 'lucide-vue-next';
import UserMenuContent from './UserMenuContent.vue';
import { useAuthStore } from '@/stores/auth'; // Importuj store
import { computed } from 'vue';

const authStore = useAuthStore();
const user = computed(() => authStore.user); // Pobierz użytkownika ze store'a
const { isMobile, state } = useSidebar();

console.log('Auth Store User:', authStore.user);
console.log('Is Logged In:', authStore.isLoggedIn);
</script>

<template>
    <SidebarMenu v-if="user">
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton size="lg" class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground">
                        <UserInfo :user="user" />
                        <ChevronsUpDown class="ml-auto size-4" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-[var(--radix-dropdown-menu-trigger-width)] min-w-56 rounded-lg"
                :side="isMobile ? 'bottom' : state === 'collapsed' ? 'right' : 'bottom'"
                align="end"
                :side-offset="4"
                >
                <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
