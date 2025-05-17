<!-- @/components/ui/badge/Badge.vue -->
<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    variant?: 'default' | 'outline' | 'secondary' | 'destructive';
}>(), {
    variant: 'default'
});

const variantClasses = computed(() => {
    const classes = {
        default: 'bg-primary text-primary-foreground hover:bg-primary/80',
        outline: 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
        secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
        destructive: 'bg-destructive text-destructive-foreground hover:bg-destructive/80'
    };
    return classes[props.variant];
});
</script>

<template>
    <div
        class="inline-flex items-center rounded-md px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
        :class="[variantClasses]"
    >
        <slot />
    </div>
</template>
