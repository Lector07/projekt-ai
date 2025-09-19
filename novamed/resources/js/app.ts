import './bootstrap';
import '../css/app.css';
import { createApp } from 'vue';
import PrimeVue from 'primevue/config';
import App from './App.vue';
import router from './router';
import { createPinia } from 'pinia';
import './services/axios';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import 'primeicons/primeicons.css'
import Lara from 'primevue/themes/lara';



if (localStorage.getItem('appearance') === 'dark' ||
    (!localStorage.getItem('appearance') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
} else {
    document.documentElement.classList.remove('dark');
}


const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(PrimeVue);
app.use(ToastService);
app.use(ConfirmationService);


app.mount('#app');
