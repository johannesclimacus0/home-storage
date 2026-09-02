<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import http from '../lib/http'
import { useAuth } from '../composables/useAuth'
import { errorMessage } from '../lib/apiError'

const router = useRouter()
const { logout } = useAuth()
const sent = ref(false)
const error = ref(null)

async function resend() {
    sent.value = false
    error.value = null

    try {
        await http.post('/email/verification-notification')
        sent.value = true
    } catch (requestError) {
        error.value = errorMessage(requestError, 'Не удалось отправить письмо для подтверждения.')
    }
}

async function signOut() {
    await logout()
    await router.push({ name: 'login' })
}
</script>

<template>
    <main class="grid min-h-screen place-items-center bg-slate-50 px-4">
        <section class="w-full max-w-sm rounded-lg border border-slate-200 bg-white p-6">
            <h1 class="text-lg font-semibold text-slate-950">Подтвердите почту</h1>
            <p v-if="sent" class="mt-4 text-sm text-emerald-700">Новое письмо для подтверждения отправлено.</p>
            <p v-if="error" class="mt-4 text-sm text-red-600">{{ error }}</p>
            <div class="mt-5 flex gap-2">
                <button class="rounded-md bg-slate-900 px-3 py-2 text-sm text-white" @click="resend">Отправить повторно</button>
                <button class="rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-100" @click="signOut">Выйти</button>
            </div>
        </section>
    </main>
</template>
