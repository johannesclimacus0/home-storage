<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import http from '../lib/http'
import { errorMessage, validationErrors } from '../lib/apiError'
import type { ValidationErrors } from '../types/api'

const route = useRoute()
const router = useRouter()
const pageError = ref<string | null>(null)
const fieldErrors = ref<ValidationErrors>({})
const form = reactive({
    email: typeof route.query.email === 'string' ? route.query.email : '',
    password: '',
    passwordConfirmation: '',
})

async function submit(): Promise<void> {
    pageError.value = null
    fieldErrors.value = {}

    try {
        await http.get('/sanctum/csrf-cookie')
        await http.post('/reset-password', {
            token: route.params.token,
            email: form.email,
            password: form.password,
            password_confirmation: form.passwordConfirmation,
        })
        await router.push({ name: 'login', query: { reset: '1' } })
    } catch (requestError: unknown) {
        fieldErrors.value = validationErrors(requestError)
        pageError.value = errorMessage(requestError, 'Не удалось изменить пароль.')
    }
}
</script>

<template>
    <main class="grid min-h-screen place-items-center bg-slate-50 px-4">
        <section class="w-full max-w-sm rounded-lg border border-slate-200 bg-white p-6">
            <h1 class="text-lg font-semibold text-slate-950">Новый пароль</h1>
            <p v-if="pageError" class="mt-3 text-sm text-red-600">{{ pageError }}</p>
            <form class="mt-5 space-y-4" @submit.prevent="submit">
                <label class="block text-sm"><span class="mb-1 block text-slate-600">Электронная почта</span><input v-model="form.email" type="email" class="w-full rounded-md border border-slate-300 px-3 py-2"><span v-if="fieldErrors.email" class="mt-1 block text-xs text-red-600">{{ fieldErrors.email[0] }}</span></label>
                <label class="block text-sm"><span class="mb-1 block text-slate-600">Новый пароль</span><input v-model="form.password" type="password" autocomplete="new-password" class="w-full rounded-md border border-slate-300 px-3 py-2"><span v-if="fieldErrors.password" class="mt-1 block text-xs text-red-600">{{ fieldErrors.password[0] }}</span></label>
                <label class="block text-sm"><span class="mb-1 block text-slate-600">Подтвердите пароль</span><input v-model="form.passwordConfirmation" type="password" autocomplete="new-password" class="w-full rounded-md border border-slate-300 px-3 py-2"></label>
                <button class="w-full rounded-md bg-slate-900 px-3 py-2 text-white">Изменить пароль</button>
            </form>
        </section>
    </main>
</template>
