<script setup lang="ts">
import { RouterLink, type RouteLocationRaw } from 'vue-router'; // Importuj RouterLink

interface Props {
    to: RouteLocationRaw; // Zmieniono href na 'to' dla spójności z router-link
    tabindex?: number;
    // Usunięto method i as - nie są potrzebne dla router-link
}

defineProps<Props>();
</script>

<template>
    <!-- Użyj router-link zamiast Link -->
    <RouterLink
        :to="to"
        :tabindex="tabindex"
        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current dark:decoration-neutral-500"
    >
        <slot />
    </RouterLink>
</template>
