<!-- src/components/errors/ErrorPage.vue -->
<template>
    <div class="error-container">
        <h1 class="error-code">{{ code }}</h1>
        <h2 class="error-title">{{ title }}</h2>
        <p class="error-message">{{ message }}</p>
        <button @click="goBack" class="btn btn-primary">Powrót</button>
        <router-link v-if="code === 401" to="/login" class="btn btn-secondary">Zaloguj się</router-link>
    </div>
</template>

<script setup lang="ts">
import { defineProps } from 'vue';
import { useRouter } from 'vue-router';

const props = defineProps<{
    code: number;
    title: string;
    message: string;
}>();

const router = useRouter();

const goBack = () => {
    router.go(-1);
};
</script>

<style scoped>
.error-container {
    text-align: center;
    padding: 50px 20px;
    max-width: 600px;
    margin: 0 auto;
}
.error-code {
    font-size: 120px;
    color: #f44336;
    margin-bottom: 0;
}
.error-title {
    font-size: 32px;
    margin-top: 0;
}
.error-message {
    font-size: 18px;
    color: #555;
    margin-bottom: 30px;
}
.btn {
    margin: 0 10px;
}
</style>
