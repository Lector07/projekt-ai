<script setup lang="ts">

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Ustawienia',
        href: '/settings'
    },
    {
        title: 'Ustawienia wyglądu',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Ustawienia wyglądu" description="Zmień ustawienia wyglądu." />
                <AppearanceTabs />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
