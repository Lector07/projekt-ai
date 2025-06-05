import router from '@/router';
import axios from 'axios';

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;
axios.defaults.headers.common['Accept'] = 'application/json';

const logoutEndpoints = ['/api/v1/auth/logout', '/api/auth/logout', '/logout'];

const isLogoutUrl = (url) => {
    return url && logoutEndpoints.some(endpoint => url === endpoint || url.includes(endpoint));
};

axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response) {
            const status = error.response.status;
            const url = error.config?.url;

            if (status === 401 && url && (url === '/api/v1/user' || url.includes('/api/v1/user'))) {
                return Promise.reject(error);
            }

            if ((status === 401 || status === 405) && isLogoutUrl(url)) {
                return Promise.reject(error);
            }

            switch (status) {
                case 400:
                    router.push('/400');
                    break;
                case 401:
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

export default axios;
