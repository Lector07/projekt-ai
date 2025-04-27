<script setup lang="ts">
import { SidebarProvider } from '@/components/ui/sidebar';
import { useRoute } from 'vue-router';
import { SharedData } from '@/types';

interface Props {
    variant?: 'header' | 'sidebar';
}

defineProps<Props>();


const isOpen = useRoute().query.sidebarOpen === 'true';
</script>

<template>
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
    </div>
    <SidebarProvider v-else :default-open="isOpen">
        <slot />
    </SidebarProvider>
</template>
