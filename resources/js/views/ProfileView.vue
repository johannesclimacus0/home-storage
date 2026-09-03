<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { errorMessage } from '../lib/apiError'
import { formatDate } from '../lib/format'
import http from '../lib/http'
import type { TelegramConnectionStatus, TelegramLinkResponse } from '../types/telegram'

const connection = ref<TelegramConnectionStatus | null>(null)
const error = ref('')

const connectionLabel = computed(() => {
    return connection.value?.connected ? 'Подключён' : 'Не подключён'
})

const connectButtonLabel = computed(() => {
    return connection.value?.connected ? 'Подключить заново' : 'Подключить Telegram'
})

async function fetchConnection(): Promise<void> {
    try {
        const response = await http.get<TelegramConnectionStatus>('/api/telegram/connection')
        connection.value = response.data
        error.value = ''
    } catch (requestError) {
        error.value = errorMessage(requestError, 'Не удалось получить состояние Telegram.')
    }
}

async function connectTelegram(): Promise<void> {
    try {
        const response = await http.post<TelegramLinkResponse>('/api/telegram/link')
        error.value = ''
        window.open(response.data.link, '_blank', 'noopener,noreferrer')
    } catch (requestError) {
        error.value = errorMessage(requestError, 'Не удалось создать ссылку подключения.')
    }
}

function refreshAfterFocus(): void {
    void fetchConnection()
}

onMounted(() => {
    void fetchConnection()
    window.addEventListener('focus', refreshAfterFocus)
})

onUnmounted(() => {
    window.removeEventListener('focus', refreshAfterFocus)
})
</script>

<template>
    <section class="mx-auto max-w-xl">
        <h1 class="mb-5 text-xl font-semibold text-slate-950">Профиль</h1>

        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-medium text-slate-950">Telegram</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ connectionLabel }}</p>
                    <p v-if="connection?.linked_at" class="mt-1 text-xs text-slate-400">
                        {{ formatDate(connection.linked_at) }}
                    </p>
                </div>

                <button
                    type="button"
                    class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                    @click="connectTelegram"
                >
                    {{ connectButtonLabel }}
                </button>
            </div>

            <p v-if="error" class="mt-4 text-sm text-red-600">{{ error }}</p>
        </div>
    </section>
</template>
