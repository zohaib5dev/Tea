import { defineStore } from 'pinia';
import http from '@/lib/http';

export const useCounters = defineStore('counters', {
    state: () => ({ pending: 0 }),
    actions: {
        async load() {
            try { this.pending = (await http.get('/counters')).data.pending_requests; } catch { /* badge is optional */ }
        },
    },
});
