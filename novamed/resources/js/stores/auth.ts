import { defineStore } from 'pinia';
import axios from 'axios';
import type { User } from '@/types/index'; // Upewnij się, że ścieżka i eksport User są poprawne

/**
 * Fetches user data from the API.
 * It expects the API to return an object战争, where the actual user data is nested under a 'data' key,
 * which is a common pattern for Laravel API Resources.
 */
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
        isInitializing: true, // Flaga informująca, czy trwa inicjalizacja
    }),

    getters: {
        isLoggedIn: (state): boolean => !!state.user,
        // Getter dla avatara, jeśli potrzebujesz dodatkowej logiki (np. domyślny avatar)
        // W tym przypadku, zakładając, że UserResource zwraca 'avatar' lub 'profile_picture_url'
        // bezpośredni dostęp state.user.avatar będzie działał.
        // userAvatar: (state): string | null => {
        //     if (!state.user) return null;
        //     return state.user.avatar || state.user.profile_picture_url || null; // Dostosuj do nazwy pola
        // }
    },

    actions: {
        async initAuth() {
            console.log('auth.ts - Rozpoczęcie initAuth.');
            this.isInitializing = true;
            try {
                const userData = await fetchUserData();
                console.log('auth.ts - Dane użytkownika otrzymane przez initAuth:', JSON.parse(JSON.stringify(userData)));
                this.user = userData; // Przypisanie danych użytkownika (lub null) do stanu
            } catch (error) { // Ten catch jest na wszelki wypadek, fetchUserData już loguje błędy
                console.error('auth.ts - Błąd w akcji initAuth podczas przypisywania danych:', error);
                this.user = null;
            } finally {
                this.isInitializing = false;
                console.log('auth.ts - Zakończono initAuth. Stan użytkownika:', JSON.parse(JSON.stringify(this.user)));
            }
        },

        async logout() {
            console.log('auth.ts - Rozpoczęcie wylogowywania.');
            try {
                // Token CSRF jest zazwyczaj obsługiwany automatycznie przez Axios, jeśli Sanctum jest poprawnie skonfigurowane
                // z ciasteczkiem XSRF-TOKEN. Poniższy kod jest dodatkowym zabezpieczeniem, ale może nie być konieczny.
                // const xsrfTokenCookie = document.cookie
                //     .split('; ')
                //     .find(row => row.startsWith('XSRF-TOKEN='));
                // const xsrfToken = xsrfTokenCookie ? decodeURIComponent(xsrfTokenCookie.split('=')[1]) : '';

                try {
                    // Laravel Sanctum dla SPA zazwyczaj używa endpointu /logout (lub /api/logout, jeśli tak zdefiniowałeś)
                    // który obsługuje sesje webowe.
                    await axios.post('/logout', {} /*, { headers: { 'X-XSRF-TOKEN': xsrfToken } }*/ );
                    console.log('auth.ts - Wylogowanie przez /logout zakończone sukcesem.');
                } catch (logoutError) {
                    console.warn('auth.ts - Błąd wylogowania na standardowej ścieżce (/logout), próba /api/v1/logout:', logoutError);
                    // Próba wylogowania przez endpoint API, jeśli standardowy zawiódł lub nie istnieje
                    try {
                        await axios.post('/api/v1/logout', {} /*, { headers: { 'X-XSRF-TOKEN': xsrfToken } }*/ );
                        console.log('auth.ts - Wylogowanie przez /api/v1/logout zakończone sukcesem.');
                    } catch (apiLogoutError) {
                        console.warn('auth.ts - Błąd wylogowania na ścieżce API (/api/v1/logout):', apiLogoutError);
                        // Nawet jeśli oba endpointy zawiodą, kontynuujemy czyszczenie po stronie klienta
                    }
                }

                this.user = null;
                // localStorage.removeItem('auth.token'); // Zazwyczaj niepotrzebne przy Sanctum SPA auth (oparte na ciasteczkach)
                // Chyba że przechowujesz tam dodatkowe tokeny ręcznie.

                // Przekierowanie na stronę główną. Można też użyć routera Vue, jeśli jest dostępny globalnie,
                // ale window.location.href jest pewniejsze dla pełnego odświeżenia.
                window.location.href = '/';
                return true;
            } catch (error) { // Ten catch jest na wypadek nieprzewidzianych błędów w logice logout
                console.error('auth.ts - Krytyczny błąd podczas wylogowywania:', error);
                // Mimo błędu, spróbuj wyczyścić stan użytkownika lokalnie
                this.user = null;
                // localStorage.removeItem('auth.token');
                throw error; // Rzuć błąd dalej, jeśli inne części aplikacji muszą na niego zareagować
            }
        }
    }
});
