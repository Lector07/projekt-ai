<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary">
        <AppLogoIcon class="size-4 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm text-nova-light active:text-gray-700 dark:text-nova-light">
        <span class="mb-0.5 truncate font-semibold leading-none">Nova Med</span>
    </div>
</template>
