<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { RouterLink, useRoute } from 'vue-router';

const route = useRoute();
const name = route.meta.name;
const quote = route.meta.quote as { message: string; author: string } | undefined;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
        <!-- Lewa kolumna zmodyfikowana -->
        <div class="lg:p-8">
            <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-xl font-medium tracking-tight" v-if="title">{{ title }}</h1>
                    <p class="text-muted-foreground text-sm" v-if="description">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>
        <div class="bg-muted relative hidden h-full flex-col items-center justify-center p-10 text-white lg:flex dark:border-r">
            <div class="absolute inset-0" />
            <div class="relative z-20 flex flex-col items-center text-center">
                <RouterLink :to="{ name: 'home' }" class="relative z-20 flex items-center text-lg font-medium">
                    <AppLogoIcon class="mr-2 h-48 w-48 fill-current text-white" />
                </RouterLink>
                <div v-if="quote" class="relative z-20 mt-8">
                    <blockquote class="space-y-2">
                        <p class="text-lg">“{{ quote.message }}”</p>
                        <footer class="text-sm text-neutral-300">{{ quote.author }}</footer>
                    </blockquote>
                </div>
            </div>
        </div>
    </div>
</template>
