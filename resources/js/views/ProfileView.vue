<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { errorMessage } from '../lib/apiError'
import { formatDate } from '../lib/format'
import http from '../lib/http'
import type {
    TelegramConnectionStatus,
    TelegramLinkResponse,
    TelegramReminder,
    TelegramReminderFrequency,
    TelegramRemindersResponse,
    TelegramSubscription,
    TelegramSubscriptionsResponse,
} from '../types/telegram'

const connection = ref<TelegramConnectionStatus | null>(null)
const subscriptions = ref<TelegramSubscription[]>([])
const reminders = ref<TelegramReminder[]>([])
const reminderMessage = ref('')
const remindAt = ref('')
const reminderFrequency = ref<TelegramReminderFrequency | ''>('')
const editingReminderUuid = ref<string | null>(null)
const editingReminderMessage = ref('')
const editingRemindAt = ref('')
const editingReminderFrequency = ref<TelegramReminderFrequency | ''>('')
const timezone = ref('UTC')
const timezones = ref<string[]>([])
const error = ref('')

const connectionLabel = computed(() => {
    return connection.value?.connected ? 'Подключён' : 'Не подключён'
})

const connectButtonLabel = computed(() => {
    return connection.value?.connected ? 'Подключить заново' : 'Подключить Telegram'
})

async function fetchTelegramSettings(): Promise<void> {
    const [subscriptionsResponse, remindersResponse] = await Promise.all([
        http.get<TelegramSubscriptionsResponse>('/api/telegram/subscriptions'),
        http.get<TelegramRemindersResponse>('/api/telegram/reminders'),
    ])

    subscriptions.value = subscriptionsResponse.data.data
    reminders.value = remindersResponse.data.data
}

async function fetchProfile(): Promise<void> {
    try {
        const response = await http.get<TelegramConnectionStatus>('/api/telegram/connection')
        connection.value = response.data
        timezone.value = response.data.timezone
        timezones.value = response.data.timezones

        if (connection.value.connected) {
            await fetchTelegramSettings()
        } else {
            subscriptions.value = []
            reminders.value = []
        }

        error.value = ''
    } catch (requestError) {
        error.value = errorMessage(requestError, 'Не удалось получить настройки Telegram.')
    }
}

async function updateTimezone(): Promise<void> {
    try {
        await http.patch('/api/telegram/timezone', {
            timezone: timezone.value,
        })
        error.value = ''
    } catch (requestError) {
        error.value = errorMessage(requestError, 'Не удалось сохранить часовой пояс.')
        await fetchProfile()
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

async function disconnectTelegram(): Promise<void> {
    if (!window.confirm('Отключить Telegram?')) {
        return
    }

    try {
        await http.delete('/api/telegram/connection')

        connection.value = {
            connected: false,
            linked_at: null,
            chat_name: null,
            timezone: timezone.value,
            timezones: timezones.value,
        }
        subscriptions.value = []
        reminders.value = []
        cancelEditingReminder()
        error.value = ''
    } catch (requestError) {
        error.value = errorMessage(requestError, 'Не удалось отключить Telegram.')
    }
}

async function updateSubscriptions(): Promise<void> {
    try {
        const response = await http.put<TelegramSubscriptionsResponse>('/api/telegram/subscriptions', {
            subscriptions: subscriptions.value
                .filter(subscription => subscription.enabled)
                .map(subscription => subscription.key),
        })

        subscriptions.value = response.data.data
        error.value = ''
    } catch (requestError) {
        const message = errorMessage(requestError, 'Не удалось сохранить подписки.')
        await fetchProfile()
        error.value = message
    }
}

async function createReminder(): Promise<void> {
    if (reminderMessage.value.trim() === '' || remindAt.value === '') {
        return
    }

    try {
        await http.post('/api/telegram/reminders', {
            message: reminderMessage.value.trim(),
            remind_at: new Date(remindAt.value).toISOString(),
            frequency: reminderFrequency.value || null,
        })

        reminderMessage.value = ''
        remindAt.value = ''
        reminderFrequency.value = ''
        error.value = ''
        await fetchTelegramSettings()
    } catch (requestError) {
        error.value = errorMessage(requestError, 'Не удалось создать напоминание.')
    }
}

function startEditingReminder(reminder: TelegramReminder): void {
    editingReminderUuid.value = reminder.uuid
    editingReminderMessage.value = reminder.message
    editingRemindAt.value = toDateTimeLocal(reminder.remind_at)
    editingReminderFrequency.value = reminder.frequency ?? ''
}

function cancelEditingReminder(): void {
    editingReminderUuid.value = null
    editingReminderMessage.value = ''
    editingRemindAt.value = ''
    editingReminderFrequency.value = ''
}

async function updateReminder(reminderUuid: string): Promise<void> {
    if (editingReminderMessage.value.trim() === '' || editingRemindAt.value === '') {
        return
    }

    try {
        const response = await http.patch<{ data: TelegramReminder }>(
            `/api/telegram/reminders/${reminderUuid}`,
            {
                message: editingReminderMessage.value.trim(),
                remind_at: new Date(editingRemindAt.value).toISOString(),
                frequency: editingReminderFrequency.value || null,
            },
        )

        reminders.value = reminders.value.map(reminder => {
            return reminder.uuid === reminderUuid ? response.data.data : reminder
        })
        error.value = ''
        cancelEditingReminder()
    } catch (requestError) {
        error.value = errorMessage(requestError, 'Не удалось изменить напоминание.')
    }
}

async function deleteReminder(reminderUuid: string): Promise<void> {
    try {
        await http.delete(`/api/telegram/reminders/${reminderUuid}`)
        reminders.value = reminders.value.filter(reminder => reminder.uuid !== reminderUuid)
        error.value = ''
    } catch (requestError) {
        error.value = errorMessage(requestError, 'Не удалось удалить напоминание.')
    }
}

function refreshAfterFocus(): void {
    void fetchProfile()
}

function frequencyLabel(frequency: TelegramReminderFrequency | null): string {
    const labels: Record<TelegramReminderFrequency, string> = {
        hourly: 'каждый час',
        daily: 'каждый день',
        weekly: 'каждую неделю',
        monthly: 'каждый месяц',
    }

    return frequency === null ? '' : labels[frequency]
}

function toDateTimeLocal(value: string): string {
    const date = new Date(value)
    const localDate = new Date(date.getTime() - date.getTimezoneOffset() * 60_000)

    return localDate.toISOString().slice(0, 16)
}

onMounted(() => {
    void fetchProfile()
    window.addEventListener('focus', refreshAfterFocus)
})

onUnmounted(() => {
    window.removeEventListener('focus', refreshAfterFocus)
})
</script>

<template>
    <section class="mx-auto max-w-2xl space-y-5">
        <h1 class="text-xl font-semibold text-slate-950">Профиль</h1>

        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <label for="timezone" class="block font-medium text-slate-950">Часовой пояс</label>
            <select
                id="timezone"
                v-model="timezone"
                class="mt-3 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-500"
                @change="updateTimezone"
            >
                <option v-for="zone in timezones" :key="zone" :value="zone">
                    {{ zone }}
                </option>
            </select>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-medium text-slate-950">Telegram</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ connectionLabel }}</p>
                    <p v-if="connection?.chat_name" class="mt-1 text-sm text-slate-500">
                        {{ connection.chat_name }}
                    </p>
                    <p v-if="connection?.linked_at" class="mt-1 text-xs text-slate-400">
                        {{ formatDate(connection.linked_at) }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        v-if="connection?.connected"
                        type="button"
                        class="rounded-md border border-red-200 bg-white px-3 py-2 text-sm text-red-700 hover:bg-red-50"
                        @click="disconnectTelegram"
                    >
                        Отключить
                    </button>
                    <button
                        type="button"
                        class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                        @click="connectTelegram"
                    >
                        {{ connectButtonLabel }}
                    </button>
                </div>
            </div>
        </div>

        <template v-if="connection?.connected">
            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <h2 class="font-medium text-slate-950">Подписки</h2>

                <label
                    v-for="subscription in subscriptions"
                    :key="subscription.key"
                    class="mt-4 flex cursor-pointer items-center justify-between gap-4 border-t border-slate-100 pt-4 text-sm text-slate-700"
                >
                    {{ subscription.label }}
                    <input
                        v-model="subscription.enabled"
                        type="checkbox"
                        class="size-4 rounded border-slate-300"
                        @change="updateSubscriptions"
                    >
                </label>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <h2 class="font-medium text-slate-950">Новое напоминание</h2>

                <form class="mt-4 space-y-3" @submit.prevent="createReminder">
                    <textarea
                        v-model="reminderMessage"
                        rows="3"
                        maxlength="1000"
                        required
                        placeholder="Что напомнить"
                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-500"
                    />

                    <div class="flex flex-wrap items-center gap-3">
                        <input
                            v-model="remindAt"
                            type="datetime-local"
                            required
                            class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-500"
                        >
                        <select
                            v-model="reminderFrequency"
                            class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-500"
                        >
                            <option value="">Один раз</option>
                            <option value="hourly">Каждый час</option>
                            <option value="daily">Каждый день</option>
                            <option value="weekly">Каждую неделю</option>
                            <option value="monthly">Каждый месяц</option>
                        </select>
                        <button
                            type="submit"
                            class="rounded-md bg-slate-900 px-3 py-2 text-sm text-white hover:bg-slate-700"
                        >
                            Создать
                        </button>
                    </div>
                </form>
            </div>

            <div v-if="reminders.length > 0" class="rounded-lg border border-slate-200 bg-white p-5">
                <h2 class="font-medium text-slate-950">Напоминания</h2>

                <div
                    v-for="reminder in reminders"
                    :key="reminder.uuid"
                    class="mt-4 border-t border-slate-100 pt-4"
                >
                    <form
                        v-if="editingReminderUuid === reminder.uuid"
                        class="space-y-3"
                        @submit.prevent="updateReminder(reminder.uuid)"
                    >
                        <textarea
                            v-model="editingReminderMessage"
                            rows="3"
                            maxlength="1000"
                            required
                            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-500"
                        />

                        <div class="flex flex-wrap items-center gap-2">
                            <input
                                v-model="editingRemindAt"
                                type="datetime-local"
                                required
                                class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-500"
                            >
                            <select
                                v-model="editingReminderFrequency"
                                class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-500"
                            >
                                <option value="">Один раз</option>
                                <option value="hourly">Каждый час</option>
                                <option value="daily">Каждый день</option>
                                <option value="weekly">Каждую неделю</option>
                                <option value="monthly">Каждый месяц</option>
                            </select>
                            <button
                                type="submit"
                                class="rounded-md bg-slate-900 px-3 py-2 text-sm text-white hover:bg-slate-700"
                            >
                                Сохранить
                            </button>
                            <button
                                type="button"
                                class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                                @click="cancelEditingReminder"
                            >
                                Отмена
                            </button>
                        </div>
                    </form>

                    <div v-else class="flex items-start justify-between gap-4">
                        <div>
                            <p class="whitespace-pre-wrap text-sm text-slate-800">{{ reminder.message }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ formatDate(reminder.remind_at) }}
                                <span v-if="reminder.frequency"> · {{ frequencyLabel(reminder.frequency) }}</span>
                                <span v-else-if="reminder.dispatched_at"> · отправлено</span>
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                v-if="!reminder.dispatched_at || reminder.frequency"
                                type="button"
                                class="text-xs text-slate-600 hover:text-slate-900"
                                @click="startEditingReminder(reminder)"
                            >
                                Изменить
                            </button>
                            <button
                                type="button"
                                class="text-xs text-red-600 hover:text-red-800"
                                @click="deleteReminder(reminder.uuid)"
                            >
                                Удалить
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </section>
</template>
