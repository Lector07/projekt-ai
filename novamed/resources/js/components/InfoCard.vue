<template>
    <div
        class="border border-gray-200 rounded-lg p-4 hover:shadow-lg transition-all duration-300 bg-gray-50 dark:border-neutral-700 dark:bg-neutral-800"
    >
        <h4 class="font-bold text-lg text-gray-800 dark:text-gray-100">{{ title }}</h4>
        <p v-if="subtitle" class="text-gray-600 dark:text-gray-400 mt-2">{{ subtitle }}</p>
        <p v-if="description" class="mt-3 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">{{ description }}</p>
        <div v-if="price" class="mt-3 text-nova-accent font-semibold">
            {{ price }} zł
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    title: string;
    subtitle?: string;
    description?: string;
    price?: number | string;
}>();
</script>
