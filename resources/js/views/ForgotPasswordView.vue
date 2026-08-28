<script setup lang="ts">
import { ref } from 'vue'
import http from '../lib/http'
import { errorMessage, validationErrors } from '../lib/apiError'

const email = ref('')
const sent = ref(false)
const message = ref<string | null>(null)

async function submit(): Promise<void> {
    message.value = null

    try {
        await http.get('/sanctum/csrf-cookie')
        await http.post('/forgot-password', { email: email.value })
        sent.value = true
    } catch (requestError: unknown) {
        message.value = validationErrors(requestError).email?.[0]
            ?? errorMessage(requestError, 'Не удалось запросить ссылку для сброса пароля.')
    }
}
</script>

<template>
    <main class="grid min-h-screen place-items-center bg-slate-50 px-4">
        <section class="w-full max-w-sm rounded-lg border border-slate-200 bg-white p-6">
            <h1 class="text-lg font-semibold text-slate-950">Сброс пароля</h1>
            <p v-if="sent" class="mt-4 text-sm text-emerald-700">Если аккаунт существует, ссылка для сброса отправлена.</p>
            <form v-else class="mt-5 space-y-4" @submit.prevent="submit">
                <label class="block text-sm"><span class="mb-1 block text-slate-600">Электронная почта</span><input v-model="email" type="email" autocomplete="email" class="w-full rounded-md border border-slate-300 px-3 py-2"></label>
                <p v-if="message" class="text-sm text-red-600">{{ message }}</p>
                <button class="w-full rounded-md bg-slate-900 px-3 py-2 text-white">Отправить ссылку</button>
            </form>
            <RouterLink :to="{ name: 'login' }" class="mt-4 block text-center text-sm text-slate-500 hover:text-slate-900">Вернуться ко входу</RouterLink>
        </section>
    </main>
</template>
