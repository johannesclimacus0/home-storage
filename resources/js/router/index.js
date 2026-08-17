import { createRouter, createWebHistory } from 'vue-router';

import DashboardView from '../views/DashboardView.vue';
import LoginView from '../views/LoginView.vue';
import VerifyEmailView from '../views/VerifyEmailView.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            redirect: '/dashboard',
        },
        {
            path: '/login',
            name: 'login',
            component: LoginView,
        },
        {
            path: '/verify-email',
            name: 'verify-email',
            component: VerifyEmailView,
        },
        {
            path: '/dashboard',
            name: 'dashboard',
            component: DashboardView,
        },
    ],
});

export default router;
