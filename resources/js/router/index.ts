import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import LoginView from '../views/LoginView.vue'
import VerifyEmailView from '../views/VerifyEmailView.vue'
import ProductView from '../views/ProductView.vue'
import ShoppingListView from '../views/ShoppingListView.vue'
import ClientLayout from '../layouts/ClientLayout.vue'
import StorageLocationsView from '../views/StorageLocationsView.vue'
import StockMovementsView from '../views/StockMovementsView.vue'
import CatalogView from '../views/CatalogView.vue'
import HouseholdSettingsView from '../views/HouseholdSettingsView.vue'
import NotificationsView from '../views/NotificationsView.vue'
import RegisterView from '../views/RegisterView.vue'
import ForgotPasswordView from '../views/ForgotPasswordView.vue'
import ResetPasswordView from '../views/ResetPasswordView.vue'
import HouseholdChatView from '../views/HouseholdChatView.vue'
import RecipeListView from '../views/RecipeListView.vue'
import RecipeDetailView from '../views/RecipeDetailView.vue'
import RecipeFormView from '../views/RecipeFormView.vue'

declare module 'vue-router' {
    interface RouteMeta {
        auth?: boolean
        guest?: boolean
    }
}

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/login',
            name: 'login',
            component: LoginView,
            meta: { guest: true },
        },
        {
            path: '/verify-email',
            name: 'verify-email',
            component: VerifyEmailView,
            meta: { auth: true },
        },
        {
            path: '/register',
            name: 'register',
            component: RegisterView,
            meta: { guest: true },
        },
        {
            path: '/forgot-password',
            name: 'forgot-password',
            component: ForgotPasswordView,
            meta: { guest: true },
        },
        {
            path: '/reset-password/:token',
            name: 'reset-password',
            component: ResetPasswordView,
            meta: { guest: true },
        },
        {
            path: '/',
            component: ClientLayout,
            meta: { auth: true },
            children: [
                { path: '', redirect: { name: 'products' } },
                { path: 'dashboard', name: 'dashboard', redirect: { name: 'products' } },
                { path: 'products', name: 'products', component: ProductView },
                { path: 'shopping-list', name: 'shopping-list', component: ShoppingListView },
                { path: 'locations', name: 'locations', component: StorageLocationsView },
                { path: 'history', name: 'stock-history', component: StockMovementsView },
                { path: 'catalog', name: 'catalog', component: CatalogView },
                { path: 'households', name: 'household-settings', component: HouseholdSettingsView },
                { path: 'notifications', name: 'notifications', component: NotificationsView },
                { path: 'chat', name: 'household-chat', component: HouseholdChatView },
                { path: 'recipes', name: 'recipes', component: RecipeListView },
                { path: 'recipes/new', name: 'recipe-create', component: RecipeFormView },
                { path: 'recipes/:recipeUuid', name: 'recipe-show', component: RecipeDetailView },
                { path: 'recipes/:recipeUuid/edit', name: 'recipe-edit', component: RecipeFormView },
            ],
        },
    ],
})

const { user, initializeAuth } = useAuth()

router.beforeEach(async to => {
    await initializeAuth()

    if (to.meta.auth && !user.value) {
        return { name: 'login' }
    }

    if (to.meta.guest && user.value) {
        return {
            name: user.value.email_verified_at ? 'products' : 'verify-email',
        }
    }

    if (user.value && !user.value.email_verified_at && to.name !== 'verify-email') {
        return { name: 'verify-email' }
    }

    if (user.value?.email_verified_at && to.name === 'verify-email') {
        return { name: 'products' }
    }
})

export default router
