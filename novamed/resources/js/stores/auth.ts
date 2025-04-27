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
        },

        async logout() {
            try {
                // Dekoduj token XSRF z ciasteczek
                const xsrfTokenCookie = document.cookie
                    .split('; ')
                    .find(row => row.startsWith('XSRF-TOKEN='));

                const xsrfToken = xsrfTokenCookie ?
                    decodeURIComponent(xsrfTokenCookie.split('=')[1]) : '';

                try {
                    // Spróbuj standardową ścieżkę Laravel
                    await axios.post('/logout', {}, {
                        headers: {
                            'Accept': 'application/json',
                            'X-XSRF-TOKEN': xsrfToken
                        },
                        withCredentials: true
                    });
                } catch (logoutError) {
                    console.warn('Błąd wylogowania na standardowej ścieżce:', logoutError);

                    try {
                        // Alternatywnie spróbuj ścieżkę API
                        await axios.post('/api/v1/logout', {}, {
                            headers: {
                                'Accept': 'application/json',
                                'X-XSRF-TOKEN': xsrfToken
                            },
                            withCredentials: true
                        });
                    } catch (apiLogoutError) {
                        console.warn('Błąd wylogowania na ścieżce API:', apiLogoutError);
                    }
                }

                // Lokalne wylogowanie zawsze się wykona
                this.user = null;
                localStorage.removeItem('auth.token');

                window.location.href = '/login';
                return true;
            } catch (error) {
                console.error('Błąd podczas wylogowywania:', error);
                throw error;
            }
        }
    }
});
