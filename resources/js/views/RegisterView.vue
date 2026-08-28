<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import http from '../lib/http'
import { useAuth } from '../composables/useAuth'
import { errorMessage, validationErrors } from '../lib/apiError'
import type { ValidationErrors } from '../types/api'

const router = useRouter()
const { fetchUser } = useAuth()
const pageError = ref<string | null>(null)
const fieldErrors = ref<ValidationErrors>({})
const form = reactive({ name: '', email: '', password: '', passwordConfirmation: '' })

async function submit(): Promise<void> {
    pageError.value = null
    fieldErrors.value = {}

    try {
        await http.get('/sanctum/csrf-cookie')
        await http.post('/register', {
            name: form.name,
            email: form.email,
            password: form.password,
            password_confirmation: form.passwordConfirmation,
        })
        await fetchUser()
        await router.push({ name: 'verify-email' })
    } catch (requestError: unknown) {
        fieldErrors.value = validationErrors(requestError)
        pageError.value = errorMessage(requestError, 'Не удалось создать аккаунт.')
    }
}
</script>

<template>
    <main class="grid min-h-screen place-items-center bg-slate-50 px-4 py-8">
        <section class="w-full max-w-sm rounded-lg border border-slate-200 bg-white p-6">
            <h1 class="text-lg font-semibold text-slate-950">Регистрация</h1>
            <p v-if="pageError" class="mt-3 text-sm text-red-600">{{ pageError }}</p>
            <form class="mt-5 space-y-4" @submit.prevent="submit">
                <label class="block text-sm"><span class="mb-1 block text-slate-600">Имя</span><input v-model="form.name" autocomplete="name" class="w-full rounded-md border border-slate-300 px-3 py-2"><span v-if="fieldErrors.name" class="mt-1 block text-xs text-red-600">{{ fieldErrors.name[0] }}</span></label>
                <label class="block text-sm"><span class="mb-1 block text-slate-600">Электронная почта</span><input v-model="form.email" type="email" autocomplete="email" class="w-full rounded-md border border-slate-300 px-3 py-2"><span v-if="fieldErrors.email" class="mt-1 block text-xs text-red-600">{{ fieldErrors.email[0] }}</span></label>
                <label class="block text-sm"><span class="mb-1 block text-slate-600">Пароль</span><input v-model="form.password" type="password" autocomplete="new-password" class="w-full rounded-md border border-slate-300 px-3 py-2"><span v-if="fieldErrors.password" class="mt-1 block text-xs text-red-600">{{ fieldErrors.password[0] }}</span></label>
                <label class="block text-sm"><span class="mb-1 block text-slate-600">Подтвердите пароль</span><input v-model="form.passwordConfirmation" type="password" autocomplete="new-password" class="w-full rounded-md border border-slate-300 px-3 py-2"></label>
                <button class="w-full rounded-md bg-slate-900 px-3 py-2 text-white">Создать аккаунт</button>
            </form>
            <RouterLink :to="{ name: 'login' }" class="mt-4 block text-center text-sm text-slate-500 hover:text-slate-900">Вернуться ко входу</RouterLink>
        </section>
    </main>
</template>
