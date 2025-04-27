import './bootstrap';
import '../css/app.css';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { createPinia } from 'pinia';
import './services/axios';

const app = createApp(App);
const pinia = createPinia();

// Najpierw zainicjalizuj pinia, potem router
app.use(pinia);
app.use(router);

app.mount('#app');
