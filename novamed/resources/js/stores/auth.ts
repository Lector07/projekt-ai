import { defineStore } from 'pinia';
import axios from 'axios';
import type { User } from '@/types';

// Funkcja pomocnicza do pobierania danych użytkownika
const fetchUserData = async (): Promise<User | null> => {
    try {
        // Dodaj nagłówek Accept dla JSON - to kluczowa zmiana
        const response = await axios.get('/api/v1/user', {
            headers: {
                'Accept': 'application/json'
            }
        });

        // Sprawdź, czy odpowiedź zawiera HTML (co sugeruje błąd)
        if (typeof response.data === 'string' && response.data.includes('<!DOCTYPE html>')) {
            console.error('API zwróciło HTML zamiast danych JSON');
            return null;
        }

        return response.data;
    } catch (error) {
        console.error('Błąd podczas pobierania danych użytkownika:', error);
        return null;
    }
};

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null as User | null,
        isInitializing: true,
    }),

    getters: {
        isLoggedIn: (state) => !!state.user,
    },

    actions: {
        async initAuth() {
            this.isInitializing = true;

            try {
                const userData = await fetchUserData();
                this.user = userData;
            } catch (error) {
                console.error('Błąd podczas inicjalizacji autoryzacji:', error);
                this.user = null;
            } finally {
                this.isInitializing = false;
            }
        }
    }
});
