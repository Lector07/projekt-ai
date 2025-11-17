import router from '@/router';
import axios from 'axios';


axios.defaults.baseURL = import.meta.env.PROD
    ? ''
    : 'http://127.0.0.1:8000';

axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['Content-Type'] = 'application/json';

// Interceptor do dodawania Bearer token
axios.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
}, (error) => {
    return Promise.reject(error);
});

declare module 'axios' {
    interface AxiosRequestConfig {
        skipAuthRedirect?: boolean;
        skipErrorRedirect?: boolean;
    }

    interface AxiosInstance {
        checkAuth(url: string, options?: object): Promise<any>;
    }
}

const logoutEndpoints = ['/api/v1/auth/logout', '/api/auth/logout', '/logout', '/api/v1/logout'];
const authCheckEndpoints = ['/api/v1/user', '/api/auth/user', '/api/user'];
const skipRedirectEndpoints = [
    '/api/v1/appointments/check-availability',
    'api/v1/patient/appointments',
    '/api/v1/doctors/availability',
    '/api/v1/admin/procedures',
    '/api/v1/admin/users',
    '/api/v1/admin/procedure-categories',
    '/api/v1/admin/appointments',
    'api/v1/doctor/profile',
    '/api/v1/register',
    '/api/v1/login',
    '/api/v1/password/reset',
    '/api/v1/password/email',
    '/api/v1/auth/logout', '/api/auth/logout', '/logout', '/api/v1/logout',
    '/api/v1/admin/doctors',
    '/api/v1/user/password'
];

const isLogoutUrl = (url: string | undefined | null) => {
    return url && logoutEndpoints.some(endpoint => url === endpoint || url.includes(endpoint));
};

const isAuthCheckUrl = (url: string | undefined | null) => {
    return url && authCheckEndpoints.some(endpoint => url === endpoint || url.includes(endpoint));
};

const shouldSkipRedirect = (url: string | undefined | null) => {
    return url && skipRedirectEndpoints.some(endpoint => url.includes(endpoint));
};

axios.interceptors.response.use(
    (response) => response,
    (error) => {
        const skipAuthRedirect = error.config?.headers?.['X-Skip-Auth-Redirect'];

        if (skipAuthRedirect && (error.response?.status === 401 || error.response?.status === 419)) {
            return Promise.reject(error);
        }

        if (error.response) {
            const status = error.response.status;
            const url = error.config?.url;

            if (shouldSkipRedirect(url) || error.config?.skipErrorRedirect) {
                return Promise.reject(error);
            }

            if (status === 401 && isAuthCheckUrl(url)) {
                return Promise.reject(error);
            }

            if ((status === 401 || status === 405 || status === 422 || status === 500) && isLogoutUrl(url)) {
                return Promise.reject(error);
            }

            if (status === 401 && error.config?.skipAuthRedirect) {
                return Promise.reject(error);
            }

            switch (status) {
                case 400:
                    router.push('/400');
                    break;
                case 401:
                    localStorage.removeItem('auth_token');
                    router.push('/401');
                    break;
                case 403:
                    router.push('/403');
                    break;
                case 404:
                    router.push('/404');
                    break;
                case 405:
                    router.push('/405');
                    break;
                case 419:
                    router.push('/419');
                    break;
                case 500:
                    router.push('/500');
                    break;
                case 503:
                    router.push('/503');
                    break;
                default:
                    router.push('/500');
                    break;
            }
        } else {
            router.push('/500');
        }

        return Promise.reject(error);
    },
);

axios.checkAuth = (url: string, options = {}) => {
    return axios.get(url, { ...options, skipAuthRedirect: true });
};

export default axios;
