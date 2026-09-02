<script setup>
import { reactive, ref } from 'vue';
import { useAuth } from '../../composables/useAuth'
import { useRouter } from 'vue-router';
import { errorMessage, validationErrors } from '../../lib/apiError'

const router = useRouter();
const { login } = useAuth();

const form = reactive({
    email: '',
    password: '',
    remember: false
});

const fieldErrors = ref({});
const generalError = ref(null);
async function submit() {
    fieldErrors.value = {};
    generalError.value = null;

    try{
        await login({email: form.email, password: form.password, remember: form.remember});
        form.password = '';
        await router.push({ name: 'products' });
    } catch(requestError) {
        fieldErrors.value = validationErrors(requestError)
        generalError.value = Object.keys(fieldErrors.value).length === 0
            ? errorMessage(requestError, 'Не удалось войти')
            : null
    }
}
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <div>
            <label for="email" class="mb-1 block text-sm text-slate-600">Электронная почта</label>

            <input
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="email"
                class="w-full rounded-md border border-slate-300 px-3 py-2 outline-none focus:border-slate-500"
            >

            <p v-if="fieldErrors.email" class="mt-1 text-xs text-red-600">
                {{ fieldErrors.email[0] }}
            </p>
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm text-slate-600">Пароль</label>

            <input
                id="password"
                v-model="form.password"
                type="password"
                autocomplete="current-password"
                class="w-full rounded-md border border-slate-300 px-3 py-2 outline-none focus:border-slate-500"
            >

            <p v-if="fieldErrors.password" class="mt-1 text-xs text-red-600">
                {{ fieldErrors.password[0] }}
            </p>
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input v-model="form.remember" type="checkbox" class="rounded border-slate-300">
            Запомнить меня
        </label>

        <p v-if="generalError" class="text-sm text-red-600">
            {{ generalError }}
        </p>

        <button type="submit" class="w-full rounded-md bg-slate-900 px-3 py-2 text-white hover:bg-slate-700">
            Войти
        </button>
    </form>
</template>
