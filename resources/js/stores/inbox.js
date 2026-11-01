import { defineStore } from 'pinia';
import http from '@/lib/http';

/** Friends + group chats with previews and unread counts. Shared by the top bar, Messages page and chat sidebar. */
export const useInbox = defineStore('inbox', {
    state: () => ({ friends: [], groups: [], loaded: false, activeId: null }),

    getters: {
        friendsUnread: (s) => s.friends.reduce((n, f) => n + (f.unread || 0), 0),
        groupsUnread: (s) => s.groups.reduce((n, g) => n + (g.unread || 0), 0),
        totalUnread() { return this.friendsUnread + this.groupsUnread; },
    },

    actions: {
        async load(force = false) {
            if (this.loaded && !force) return;
            const { data } = await http.get('/conversations');
            this.friends = data.friends;
            this.groups = data.groups;
            this.loaded = true;
        },

        clearUnread(conversationId) {
            const row = this.groups.find((g) => g.id === conversationId) || this.friends.find((f) => f.conversation_id === conversationId);
            if (row) row.unread = 0;
        },

        /** A message arrived or changed: update the row and move it to the top of its list. */
        touch({ conversationId, userId, type, preview, unread }) {
            let list = this.groups;
            let row = list.find((g) => g.id === conversationId);

            if (!row) {
                list = this.friends;
                row = list.find((f) => f.conversation_id === conversationId)
                    || (type !== 'group' && userId ? list.find((f) => f.id === userId) : null);
            }
            if (!row) return;

            if (list === this.friends) row.conversation_id = conversationId;
            row.preview = preview;
            row.last_message_at = new Date().toISOString();
            if (unread) row.unread = (row.unread || 0) + 1;

            list.splice(list.indexOf(row), 1);
            list.unshift(row);
        },
    },
});
