<script setup>
import LoginForm from '../components/auth/LoginForm.vue';
import { useRoute } from 'vue-router';

const route = useRoute();
</script>

<template>
    <main class="grid min-h-screen place-items-center bg-slate-50 px-4">
        <section class="w-full max-w-sm rounded-lg border border-slate-200 bg-white p-6">
            <h1 class="mb-5 text-lg font-semibold text-slate-950">Вход</h1>
            <p v-if="route.query.reset" class="-mt-2 mb-4 text-sm text-emerald-700">Пароль изменён.</p>
            <LoginForm />
            <div class="mt-4 flex justify-between text-sm">
                <RouterLink :to="{ name: 'forgot-password' }" class="text-slate-500 hover:text-slate-900">Забыли пароль?</RouterLink>
                <RouterLink :to="{ name: 'register' }" class="text-slate-700 hover:text-slate-950">Регистрация</RouterLink>
            </div>
        </section>
    </main>
</template>
