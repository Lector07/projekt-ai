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
                this.user = await fetchUserData();
            } catch (_error) {
                this.user = null;
            } finally {
                this.isInitializing = false;
            }
        },

        async login(email: string, password: string) {
            try {
                const response = await axios.post('/api/v1/login', {
                    email,
                    password
                });

                if (response.data.token) {
                    localStorage.setItem('auth_token', response.data.token);
                }

                if (response.data.user) {
                    this.user = response.data.user;
                }

                return response.data;
            } catch (error) {
                console.error('auth.ts - Błąd logowania:', error);
                throw error;
            }
        },

        async register(name: string, email: string, password: string, password_confirmation: string) {
            try {
                const response = await axios.post('/api/v1/register', {
                    name,
                    email,
                    password,
                    password_confirmation
                });

                return response.data;
            } catch (error) {
                console.error('auth.ts - Błąd rejestracji:', error);
                throw error;
            }
        },

        async logout() {
            try {
                try {
                    await axios.post('/api/v1/logout', {});
                } catch (logoutError) {
                    console.warn('auth.ts - Błąd wylogowania:', logoutError);
                }

                localStorage.removeItem('auth_token');
                this.user = null;
                window.location.href = '/';
                return true;
            } catch (error) {
                console.error('auth.ts - Krytyczny błąd podczas wylogowywania:', error);
                localStorage.removeItem('auth_token');
                this.user = null;
                throw error;
            }
        }
    }
});
