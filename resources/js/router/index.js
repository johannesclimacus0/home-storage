import { createRouter, createWebHistory } from 'vue-router';
import { useAuth } from '../composables/useAuth.js';
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
            meta: {
                guestOnly: true,
            },
        },
        {
            path: '/verify-email',
            name: 'verify-email',
            component: VerifyEmailView,
            meta: {
                requiresAuth: true,
                unverifiedOnly: true,
            },
        },
        {
            path: '/dashboard',
            name: 'dashboard',
            component: DashboardView,
            meta: {
                requiresAuth: true,
                requiresVerifiedEmail: true,
            },
        },
    ],
});
const {
    user,
    initializeAuth,
} = useAuth();
router.beforeEach(async (to) => {
    await initializeAuth();

    if (to.meta.requiresAuth && !user.value) {
        return {
            name: 'login',
        };
    }
    if (to.meta.requiresVerifiedEmail && !user.value?.email_verified_at) {
        return {
            name: 'verify-email',
        };
    }
    if (to.meta.guestOnly && user.value) {
        return {
            name: user.value.email_verified_at
                ? 'dashboard'
                : 'verify-email',
        };
    }
    if (to.meta.unverifiedOnly && user.value?.email_verified_at) {
        return {
            name: 'dashboard',
        };
    }
});
export default router;
