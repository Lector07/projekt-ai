<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { useRoute, RouterLink } from 'vue-router'; // Importuj RouterLink

defineProps<{
    items: NavItem[];
}>();

const route = useRoute();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="item.href === route.path"
                    :tooltip="item.title"
                >
                    <RouterLink :to="item.href ?? '#'" class="flex items-center gap-2">
                        <component v-if="item.icon" :is="item.icon" class="w-5 h-5" />
                        <span class="text-sm font-medium">{{ item.title }}</span>
                    </RouterLink>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
