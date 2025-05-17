<!-- @/components/ui/input-error/InputError.vue -->
<script setup lang="ts">
defineProps<{
    message?: string | null;
}>();
</script>

<template>
    <p v-if="message" class="text-sm font-medium text-red-500 mt-1">
        {{ message }}
    </p>
</template>
