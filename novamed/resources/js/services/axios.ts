// resources/js/services/axios.ts
import axios from 'axios';

// Konfiguracja dla uwierzytelniania sesyjnego Sanctum
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;  // Kluczowe dla ciasteczek sesyjnych
axios.defaults.headers.common['Accept'] = 'application/json';

// Interceptor odpowiedzi dla obsługi błędów autoryzacji
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            console.log('Wykryto brak autoryzacji');
            // Możesz dodać przekierowanie do strony logowania
            // window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export default axios;
