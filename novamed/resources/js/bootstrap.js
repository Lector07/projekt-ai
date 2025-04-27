// resources/js/bootstrap.js (jeśli używasz JS)
// lub resources/js/bootstrap.ts (jeśli używasz TS)

import axios from 'axios';

window.axios = axios; // Udostępnij axios globalnie (opcjonalne, ale często w starter kitach)

// Ustaw podstawowy URL dla wszystkich żądań Axios
// Powinien wskazywać na Twój serwer Laravela
window.axios.defaults.baseURL = 'http://127.0.0.1:8000'; // Lub zmienna środowiskowa

// Ustaw standardowe nagłówki
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// --- Kluczowe dla Sanctum SPA Authentication ---
// Włącz wysyłanie ciasteczek (w tym sesyjnych) z żądaniami
window.axios.defaults.withCredentials = true;
// Axios automatycznie użyje ciasteczka XSRF-TOKEN i wyśle je w nagłówku X-XSRF-TOKEN
// (Laravel Sanctum dostarcza endpoint /sanctum/csrf-cookie do pobrania tego ciasteczka)
// --- Koniec sekcji Sanctum ---

// Możesz tu dodać import innych bibliotek, np. lodash, echo (dla WebSockets)
// import Echo from 'laravel-echo';
// import Pusher from 'pusher-js';
// window.Pusher = Pusher;
// window.Echo = new Echo({ ... });

console.log('Bootstrap loaded'); // Dodaj log, aby potwierdzić załadowanie
