// resources/js/stores/auth.ts
import { defineStore } from 'pinia';
import axios from 'axios'; // Zakładamy, że Axios jest skonfigurowany w bootstrap.js
import type { User } from '@/types'; // Zaimportuj lub zdefiniuj typ User

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null as User | null, // Przechowuje dane zalogowanego użytkownika lub null
    }),
    getters: {
        isLoggedIn: (state) => !!state.user, // Sprawdza, czy użytkownik jest zalogowany
    },
    actions: {
        setUser(user: User | null) {
            this.user = user; // Ustawia użytkownika (np. po logowaniu lub pobraniu danych)
        },
        logout() {
            this.user = null; // Czyści stan użytkownika
        },
        async fetchUser() {
            try {
                // Pobiera dane użytkownika z API
                const response = await axios.get('/api/v1/user');
                this.setUser(response.data); // Ustawia użytkownika na podstawie odpowiedzi
            } catch (error: any) {
                console.error('Nie udało się pobrać użytkownika:', error.response?.data?.message || error.message);
                this.setUser(null); // Ustawia użytkownika na null w przypadku błędu
            }
        },
    },
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
