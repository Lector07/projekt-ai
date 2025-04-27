<script setup lang="ts">
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { RouterLink } from 'vue-router'; // Importuj RouterLink

interface BreadcrumbItemType { // Zmieniono nazwę interfejsu dla jasności
    title: string;
    href?: string; // href jest teraz opcjonalny
    routeName?: string; // Opcjonalnie: nazwa trasy dla router-link
}

defineProps<{
    breadcrumbs: BreadcrumbItemType[];
}>();
</script>

<template>
    <Breadcrumb>
        <BreadcrumbList>
            <template v-for="(item, index) in breadcrumbs" :key="index">
                <BreadcrumbItem>
                    <template v-if="index === breadcrumbs.length - 1">
                        <BreadcrumbPage>{{ item.title }}</BreadcrumbPage>
                    </template>
                    <template v-else>
                        <BreadcrumbLink as-child>
                            <!-- Użyj router-link jeśli podano routeName lub href dla ścieżki wewnętrznej -->
                            <RouterLink v-if="item.routeName || (item.href && item.href.startsWith('/'))" :to="item.routeName ? { name: item.routeName } : item.href ?? '#'">{{ item.title }}</RouterLink>
                            <!-- W przeciwnym razie zwykły link <a> -->
                            <a v-else :href="item.href ?? '#'">{{ item.title }}</a>
                        </BreadcrumbLink>
                    </template>
                </BreadcrumbItem>
                <BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1" />
            </template>
        </BreadcrumbList>
    </Breadcrumb>
</template>
