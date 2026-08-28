<script setup lang="ts">
import { onMounted, ref } from 'vue'
import http from '../lib/http'
import { errorMessage } from '../lib/apiError'
import { formatDate } from '../lib/format'
import type { PaginatedResponse, PaginationMeta } from '../types/api'
import type { UserNotification } from '../types/notification'
import PaginationNav from '../components/ui/PaginationNav.vue'

const notifications = ref<UserNotification[]>([])
const meta = ref<PaginationMeta | null>(null)
const pageError = ref<string | null>(null)

onMounted(() => loadNotifications())

async function loadNotifications(page = 1): Promise<void> {
    pageError.value = null

    try {
        const response = await http.get<PaginatedResponse<UserNotification>>('/api/notifications', {
            params: { page, per_page: 8 },
        })
        notifications.value = response.data.data
        meta.value = response.data.meta
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось загрузить уведомления.')
    }
}

async function markAsRead(notification: UserNotification): Promise<void> {
    if (notification.read_at !== null) return

    try {
        const response = await http.patch<{ data: UserNotification }>(
            `/api/notifications/${notification.uuid}/read`
        )
        notifications.value = notifications.value.map(item =>
            item.uuid === notification.uuid ? response.data.data : item
        )
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось отметить уведомление прочитанным.')
    }
}

async function markAllAsRead(): Promise<void> {
    try {
        await http.patch('/api/notifications/read-all')
        const readAt = new Date().toISOString()
        notifications.value = notifications.value.map(notification => ({
            ...notification,
            read_at: notification.read_at ?? readAt,
        }))
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось отметить уведомления прочитанными.')
    }
}

function textValue(notification: UserNotification, key: string): string {
    const value = notification.data[key]
    return typeof value === 'string' || typeof value === 'number' ? String(value) : ''
}

function notificationClasses(notification: UserNotification): string {
    return notification.read_at === null ? 'bg-amber-50/50' : ''
}
</script>

<template>
    <div>
        <header class="mb-5 flex items-end justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-950">Уведомления</h1>
            </div>
            <button class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50" @click="markAllAsRead">Прочитать все</button>
        </header>

        <p v-if="pageError" class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-red-700">{{ pageError }}</p>
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <p v-if="notifications.length === 0" class="p-5 text-slate-500">Уведомлений нет.</p>
            <ul v-else class="divide-y divide-slate-100">
                <li
                    v-for="notification in notifications"
                    :key="notification.uuid"
                    class="flex gap-3 px-4 py-3"
                    :class="notificationClasses(notification)"
                >
                    <span v-if="notification.read_at === null" class="mt-2 size-1.5 shrink-0 rounded-full bg-amber-500"></span>
                    <span v-else class="w-1.5 shrink-0"></span>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-slate-900">
                            Заканчивается: {{ textValue(notification, 'product_name') || 'продукт' }}
                        </p>
                        <p class="mt-0.5 text-sm text-slate-500">
                            {{ textValue(notification, 'household_name') }}
                            <template v-if="textValue(notification, 'quantity')">
                                · доступно {{ textValue(notification, 'quantity') }}
                            </template>
                        </p>
                        <p class="mt-1 text-xs text-slate-400">{{ formatDate(notification.created_at) }}</p>
                    </div>
                    <button v-if="notification.read_at === null" class="self-start rounded px-2 py-1 text-xs text-slate-600 hover:bg-white" @click="markAsRead(notification)">Прочитано</button>
                </li>
            </ul>
        </div>
        <PaginationNav class="mt-3" :meta="meta" @change="loadNotifications" />
    </div>
</template>
