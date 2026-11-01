import { defineStore } from 'pinia';

let next = 1;

export const useUi = defineStore('ui', {
    state: () => ({ toasts: [] }),
    actions: {
        toast(text, ms = 3000) {
            const id = next++;
            this.toasts.push({ id, text });
            setTimeout(() => { this.toasts = this.toasts.filter((t) => t.id !== id); }, ms);
        },
    },
});
