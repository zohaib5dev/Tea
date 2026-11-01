import axios from 'axios';

// Same-origin JSON API. Laravel's XSRF-TOKEN cookie is sent back automatically as X-XSRF-TOKEN.
const http = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
});

http.interceptors.response.use(
    (r) => r,
    (err) => {
        // Suspended account: the server already signed it out.
        if (err.response?.status === 403 && err.response.data?.banned) {
            location.assign('/login?banned=1');
            return Promise.reject(err);
        }

        // Session ended: go to the login page.
        if (err.response?.status === 401 && !location.pathname.startsWith('/login')) {
            location.assign('/login');
        }
        return Promise.reject(err);
    },
);

/** Readable text from a failed request (first validation error, or the server message). */
export function errorText(err, fallback = 'Something went wrong. Please try again.') {
    const data = err?.response?.data;
    if (data?.errors) return Object.values(data.errors)[0][0];
    return data?.message || fallback;
}

export default http;
