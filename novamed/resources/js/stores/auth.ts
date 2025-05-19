import { defineStore } from 'pinia';
import axios from 'axios';
import type { User } from '@/types';

const fetchUserData = async (): Promise<User | null> => {
    try {
        const response = await axios.get('/api/v1/user', {
            headers: {
                'Accept': 'application/json'
            }
        });

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
                const xsrfTokenCookie = document.cookie
                    .split('; ')
                    .find(row => row.startsWith('XSRF-TOKEN='));

                const xsrfToken = xsrfTokenCookie ?
                    decodeURIComponent(xsrfTokenCookie.split('=')[1]) : '';

                try {
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
