import { useRouter } from 'vue-router';
import http, { errorText } from '@/lib/http';
import { useUi } from '@/stores/ui';
import { useCounters } from '@/stores/counters';

export function useFriendActions() {
    const ui = useUi();
    const counters = useCounters();
    const router = useRouter();

    async function run(request, fallback) {
        try {
            const { data } = await request();
            ui.toast(data?.message || fallback);
            return true;
        } catch (e) {
            ui.toast(errorText(e));
            return false;
        }
    }

    return {
        add: (userId) => run(() => http.post(`/friends/${userId}/add`), 'Friend request sent.'),
        remove: (userId) => run(() => http.delete(`/friends/${userId}`), 'Removed.'),
        async accept(friendshipId) {
            const ok = await run(() => http.post(`/friendships/${friendshipId}/accept`), 'You are now friends.');
            if (ok) counters.pending = Math.max(0, counters.pending - 1);
            return ok;
        },
        async decline(friendshipId) {
            const ok = await run(() => http.post(`/friendships/${friendshipId}/decline`), 'Request declined.');
            if (ok) counters.pending = Math.max(0, counters.pending - 1);
            return ok;
        },
        /** Open (or create) the private chat with a friend. */
        async message(userId) {
            try {
                const { data } = await http.post('/conversations', { user_id: userId });
                router.push(`/messages/${data.conversation_id}`);
            } catch (e) {
                ui.toast(errorText(e));
            }
        },
    };
}
