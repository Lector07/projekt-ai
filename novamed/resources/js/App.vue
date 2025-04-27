
<template>
    <div>
        <main>

            <router-view v-slot="{ Component }">
                <transition name="fade" mode="out-in">
                    <component :is="Component" />
                </transition>
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

    if (authStore.user === null) {
        await authStore.fetchUser();

    }
});
</script>

<style scoped>
/* Style dla App.vue, np. przejścia */
nav a {
    margin: 0 5px;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
