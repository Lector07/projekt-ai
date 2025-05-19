<template>
    <div>
        <main>
            <router-view v-slot="{ Component, route }">
                    <component :is="Component"/>
            </router-view>
        </main>

        <footer>
        </footer>
    </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

onMounted(async () => {
    await authStore.initAuth();
});
</script>

<style scoped>

</style>
