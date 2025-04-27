// resources/js/stores/auth.ts
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios'; // Zakładamy, że Axios jest skonfigurowany w bootstrap.js
import type { User } from '@/types'; // Zaimportuj lub zdefiniuj typ User

// Definiujemy store o ID 'auth'
export const useAuthStore = defineStore('auth', () => {
    // --- State ---
    // Przechowuje dane zalogowanego użytkownika lub null
    const user = ref<User | null>(null);

    // --- Getters (jako Computed Properties) ---
    // Sprawdza, czy użytkownik jest zalogowany
    const isLoggedIn = computed(() => user.value !== null);

    // --- Actions ---
    // Ustawia użytkownika (np. po logowaniu lub pobraniu danych)
    function setUser(userData: User | null) {
        user.value = userData;
    }

    // Akcja "wylogowania" (czyści stan użytkownika)
    function logout() {
        setUser(null);
        // Możesz tu dodać czyszczenie np. localStorage, jeśli coś tam przechowujesz
    }

    // Akcja pobierania danych użytkownika z API (np. przy starcie aplikacji)
    // Sprawdza, czy istnieje ważna sesja po stronie serwera
    async function fetchUser() {
        try {
            // Uderz do endpointu /api/v1/user, który zwraca zalogowanego użytkownika
            // Middleware 'auth:sanctum' na tej trasie zadba o resztę
            const response = await axios.get('/api/v1/user');
            setUser(response.data); // Ustaw użytkownika na podstawie odpowiedzi
        } catch (error: any) {
            // Jeśli wystąpi błąd (np. 401 Unauthorized), oznacza to brak ważnej sesji
            console.error('Nie udało się pobrać użytkownika:', error.response?.data?.message || error.message);
            setUser(null); // Upewnij się, że użytkownik jest ustawiony na null
        }
    }

    // Udostępnij stan i akcje
    return {
        user,
        isLoggedIn,
        setUser,
        logout,
        fetchUser,
    };
});

// Opcjonalnie zdefiniuj typ User, jeśli nie masz go w @/types
/*
export interface User {
    id: number;
    name: string;
    email: string;
    profile_picture_path?: string | null;
    roles?: Array<{ id: number; name: string; slug: string }>;
    // inne potrzebne pola
}
*/
