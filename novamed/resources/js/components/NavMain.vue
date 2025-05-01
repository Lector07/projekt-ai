<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { useRoute, RouterLink, RouteLocationRaw } from 'vue-router';

defineProps<{
    items: NavItem[];
}>();

const route = useRoute();

// Funkcja do sprawdzania, czy obiekt trasy ma właściwość name
function hasNameProperty(to: RouteLocationRaw): to is { name: string } {
    return typeof to === 'object' && to !== null && 'name' in to;
}

// Funkcja pomocnicza do sprawdzania, czy element jest aktywny
function isActive(item: NavItem): boolean {
    if (item.to) {
        // Sprawdzanie dopasowania nazwy trasy
        if (hasNameProperty(item.to) && route.name) {
            return route.name === item.to.name;
        }

        // Sprawdzanie dopasowania ścieżki
        if (typeof item.to === 'string') {
            return route.path === item.to;
        } else if ('path' in item.to) {
            return route.path === item.to.path;
        }
    }

    // Sprawdzanie tradycyjnego href
    return item.href === route.path;
}
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel class="text-nova-light">Platforma</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isActive(item)"
                    :tooltip="item.title"
                >
                    <!-- Dla wewnętrznych linków używamy RouterLink -->
                    <RouterLink
                        v-if="item.to"
                        :to="item.to"
                        class="flex items-center gap-2 text-nova-light hover:text-nova-dark"
                    >
                        <component v-if="item.icon" :is="item.icon" class="w-5 h-5" />
                        <span class="text-sm font-medium ">{{ item.title }}</span>
                    </RouterLink>

                    <!-- Dla zewnętrznych linków używamy zwykłego <a> -->
                    <a
                        v-else-if="item.href"
                        :href="item.href"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center gap-2"
                    >
                        <component v-if="item.icon" :is="item.icon" class="w-5 h-5" />
                        <span class="text-sm font-medium">{{ item.title }}</span>
                    </a>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
