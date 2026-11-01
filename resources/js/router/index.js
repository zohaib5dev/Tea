import { createRouter, createWebHistory } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';
import LoginView from '@/views/LoginView.vue';
import RegisterView from '@/views/RegisterView.vue';
import FeedView from '@/views/FeedView.vue';
import PostView from '@/views/PostView.vue';
import ProfileView from '@/views/ProfileView.vue';
import FriendsView from '@/views/FriendsView.vue';
import MessagesView from '@/views/MessagesView.vue';
import ChatView from '@/views/ChatView.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import AdminDashboard from '@/views/admin/AdminDashboard.vue';
import AdminUsers from '@/views/admin/AdminUsers.vue';
import AdminPosts from '@/views/admin/AdminPosts.vue';
import AdminComments from '@/views/admin/AdminComments.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/login', component: LoginView, meta: { guest: true, title: 'Log in' } },
        { path: '/register', component: RegisterView, meta: { guest: true, title: 'Create account' } },
        {
            path: '/',
            component: AppLayout,
            meta: { auth: true },
            children: [
                { path: '', redirect: '/feed' },
                { path: 'feed', component: FeedView, meta: { title: 'Feed' } },
                { path: 'posts/:id', component: PostView, meta: { title: 'Post' } },
                { path: 'users/:id', component: ProfileView, meta: { title: 'Profile' } },
                { path: 'profile', redirect: () => `/users/${window.__USER__.id}` },
                { path: 'friends', component: FriendsView, meta: { title: 'Friends' } },
                { path: 'messages', component: MessagesView, meta: { title: 'Messages' } },
                { path: 'messages/:id', component: ChatView, meta: { title: 'Chat' } },
                {
                    path: 'admin',
                    component: AdminLayout,
                    meta: { admin: true },
                    children: [
                        { path: '', component: AdminDashboard, meta: { title: 'Admin' } },
                        { path: 'users', component: AdminUsers, meta: { title: 'Admin: users' } },
                        { path: 'posts', component: AdminPosts, meta: { title: 'Admin: posts' } },
                        { path: 'comments', component: AdminComments, meta: { title: 'Admin: comments' } },
                    ],
                },
            ],
        },
        { path: '/:pathMatch(.*)*', redirect: '/feed' },
    ],
    scrollBehavior: () => ({ top: 0 }),
});

router.beforeEach((to) => {
    const user = window.__USER__;
    if (to.meta.guest && user) return '/feed';
    if (to.matched.some((r) => r.meta.auth) && !user) return '/login';
    if (to.matched.some((r) => r.meta.admin) && !user?.is_admin) return '/feed';
});

router.afterEach((to) => {
    document.title = `${to.meta.title || ''} · ${window.__APP_NAME__ || 'App'}`;
});

export default router;
