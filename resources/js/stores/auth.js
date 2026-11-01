import { defineStore } from 'pinia';
import http from '@/lib/http';

export const useAuth = defineStore('auth', {
    state: () => ({ user: window.__USER__ || null }),
    actions: {
        async logout() {
            try { await http.post('/logout'); } finally { window.location.assign('/login'); }   // full reload = fresh CSRF token
        },
    },
});
