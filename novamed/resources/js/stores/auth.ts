import { defineStore } from 'pinia';
import axios from 'axios';
import type { User } from '@/types.d';


const fetchUserData = async (): Promise<User | null> => {
    try {
        const response = await axios.get('/api/v1/user', {
            headers: {
                'Accept': 'application/json'
            }
        });

        if (typeof response.data === 'string' && response.data.includes('<!DOCTYPE html>')) {
            return null;
        }

        if (response.data && response.data.data && typeof response.data.data === 'object') {
            return response.data.data as User;
        } else {
            return null;
        }
    } catch (error: any) {
        if (axios.isAxiosError(error) && error.response?.status === 401) {
            console.log('auth.ts - Użytkownik nie jest zalogowany (401).');
        } else {
            console.error('auth.ts - Błąd podczas pobierania danych użytkownika:', error);
        }
        return null;
    }
};

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null as User | null,
        isInitializing: true,
    }),

    getters: {
        isLoggedIn: (state): boolean => !!state.user,

    },

    actions: {
        async initAuth() {
            this.isInitializing = true;
            try {
                const userData = await fetchUserData();
                this.user = userData;
            } catch (error) {
                this.user = null;
            } finally {
                this.isInitializing = false;
            }
        },

        async logout() {
            try {
                try {
                    await axios.post('/logout', {});
                } catch (logoutError) {
                    console.warn('auth.ts - Błąd wylogowania na standardowej ścieżce (/logout), próba /api/v1/logout:', logoutError);
                    try {
                        await axios.post('/api/v1/logout', {});
                    } catch (apiLogoutError) {
                        console.warn('auth.ts - Błąd wylogowania na ścieżce API (/api/v1/logout):', apiLogoutError);
                    }
                }

                this.user = null;
                window.location.href = '/';
                return true;
            } catch (error) {
                console.error('auth.ts - Krytyczny błąd podczas wylogowywania:', error);
                this.user = null;
                throw error;
            }
        }
    }
});
