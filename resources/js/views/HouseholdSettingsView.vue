<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import http from '../lib/http'
import { useAuth } from '../composables/useAuth'
import { useHouseholds } from '../composables/useHouseholds'
import { errorMessage, validationErrors } from '../lib/apiError'
import { formatDate } from '../lib/format'
import type { ApiResponse, ValidationErrors } from '../types/api'
import type Household from '../types/household'
import type { HouseholdDetails, HouseholdMember } from '../types/household'

const { user } = useAuth()
const {
    activeHousehold,
    selectedHouseholdUuid,
    refreshHouseholds,
} = useHouseholds()

const details = ref<HouseholdDetails | null>(null)
const pageError = ref<string | null>(null)
const successMessage = ref<string | null>(null)
const fieldErrors = ref<ValidationErrors>({})
const createForm = reactive({ name: '' })
const renameForm = reactive({ name: '' })
const memberForm = reactive({ email: '' })
const reminderForm = reactive({ enabled: true, intervalHours: 24 })
const currentUserId = computed(() => user.value?.id ?? null)

watch(selectedHouseholdUuid, loadDetails, { immediate: true })

async function loadDetails(): Promise<void> {
    clearMessages()

    if (selectedHouseholdUuid.value === null) {
        details.value = null
        return
    }

    try {
        const householdUuid = selectedHouseholdUuid.value
        const response = await http.get<ApiResponse<HouseholdDetails>>(
            `/api/households/${householdUuid}`
        )

        if (selectedHouseholdUuid.value !== householdUuid) return
        details.value = response.data.data
        renameForm.name = response.data.data.name
        reminderForm.enabled = activeHousehold.value?.low_stock_reminders_enabled ?? true
        reminderForm.intervalHours = activeHousehold.value?.low_stock_reminder_interval_hours ?? 24
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось загрузить настройки дома.')
    }
}

async function createHousehold(): Promise<void> {
    clearMessages()

    try {
        const response = await http.post<ApiResponse<Household>>('/api/households', {
            name: createForm.name,
        })
        createForm.name = ''
        await refreshHouseholds()
        selectedHouseholdUuid.value = response.data.data.uuid
        successMessage.value = 'Дом создан.'
    } catch (requestError: unknown) {
        handleFailure(requestError, 'Не удалось создать дом.')
    }
}

async function renameHousehold(): Promise<void> {
    if (selectedHouseholdUuid.value === null) return

    clearMessages()

    try {
        await http.patch(`/api/households/${selectedHouseholdUuid.value}`, {
            name: renameForm.name,
        })
        await refreshHouseholds()
        await loadDetails()
        successMessage.value = 'Название дома изменено.'
    } catch (requestError: unknown) {
        handleFailure(requestError, 'Не удалось переименовать дом.')
    }
}

async function addMember(): Promise<void> {
    if (selectedHouseholdUuid.value === null) return

    clearMessages()

    try {
        await http.post(`/api/households/${selectedHouseholdUuid.value}/members`, {
            email: memberForm.email,
        })
        memberForm.email = ''
        await loadDetails()
        successMessage.value = 'Участник добавлен.'
    } catch (requestError: unknown) {
        handleFailure(requestError, 'Не удалось добавить участника.')
    }
}

async function removeMember(member: HouseholdMember): Promise<void> {
    if (
        selectedHouseholdUuid.value === null ||
        !window.confirm(`Удалить участника ${member.name} из дома?`)
    ) return

    clearMessages()

    try {
        await http.delete(`/api/households/${selectedHouseholdUuid.value}/members/${member.user_id}`)
        await loadDetails()
        successMessage.value = 'Участник удалён.'
    } catch (requestError: unknown) {
        handleFailure(requestError, 'Не удалось удалить участника.')
    }
}

async function transferOwnership(member: HouseholdMember): Promise<void> {
    if (
        selectedHouseholdUuid.value === null ||
        !window.confirm(`Передать права владельца пользователю ${member.name}?`)
    ) return

    clearMessages()

    try {
        await http.patch(`/api/households/${selectedHouseholdUuid.value}/owner`, {
            new_owner_user_id: member.user_id,
        })
        await refreshHouseholds()
        await loadDetails()
        successMessage.value = 'Права владельца переданы.'
    } catch (requestError: unknown) {
        handleFailure(requestError, 'Не удалось передать права владельца.')
    }
}

async function saveReminderSettings(): Promise<void> {
    if (selectedHouseholdUuid.value === null) return

    clearMessages()

    try {
        await http.patch(`/api/households/${selectedHouseholdUuid.value}/low-stock-reminder-settings`, {
            enabled: reminderForm.enabled,
            interval_hours: reminderForm.intervalHours,
        })
        await refreshHouseholds()
        successMessage.value = 'Настройки напоминаний сохранены.'
    } catch (requestError: unknown) {
        handleFailure(requestError, 'Не удалось сохранить настройки напоминаний.')
    }
}

async function leaveHousehold(): Promise<void> {
    if (
        selectedHouseholdUuid.value === null ||
        !window.confirm('Покинуть этот дом? Права владельца могут быть переданы автоматически.')
    ) return

    clearMessages()

    try {
        await http.delete(`/api/households/${selectedHouseholdUuid.value}/membership`)
        await refreshHouseholds()
        await loadDetails()
    } catch (requestError: unknown) {
        handleFailure(requestError, 'Не удалось покинуть дом.')
    }
}

async function deleteHousehold(): Promise<void> {
    if (
        selectedHouseholdUuid.value === null ||
        !window.confirm('Удалить этот дом и все его данные? Это действие нельзя отменить.')
    ) return

    clearMessages()

    try {
        await http.delete(`/api/households/${selectedHouseholdUuid.value}`)
        await refreshHouseholds()
        await loadDetails()
    } catch (requestError: unknown) {
        handleFailure(requestError, 'Не удалось удалить дом.')
    }
}

function clearMessages(): void {
    pageError.value = null
    successMessage.value = null
    fieldErrors.value = {}
}

function handleFailure(requestError: unknown, fallback: string): void {
    fieldErrors.value = validationErrors(requestError)
    pageError.value = errorMessage(requestError, fallback)
}
</script>

<template>
    <div>
        <header class="mb-5">
            <h1 class="text-xl font-semibold text-slate-950">Дома</h1>
        </header>

        <p v-if="pageError" class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-red-700">{{ pageError }}</p>
        <p v-if="successMessage" class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-emerald-700">{{ successMessage }}</p>

        <section class="mb-4 rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="mb-3 font-medium text-slate-900">Создать дом</h2>
            <form class="flex max-w-xl flex-col gap-2 sm:flex-row" @submit.prevent="createHousehold">
                <div class="flex-1">
                    <input v-model="createForm.name" placeholder="Название дома" class="w-full rounded-md border border-slate-300 px-3 py-2">
                    <span v-if="fieldErrors.name" class="mt-1 block text-xs text-red-600">{{ fieldErrors.name[0] }}</span>
                </div>
                <button class="rounded-md bg-slate-900 px-3 py-2 text-white">Создать</button>
            </form>
        </section>

        <p v-if="details === null" class="rounded-lg border border-slate-200 bg-white p-5 text-slate-500">Выберите существующий дом или создайте новый.</p>

        <template v-else>
            <section class="mb-4 rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="mb-3 font-medium text-slate-900">Основные настройки</h2>
                <form class="flex max-w-xl flex-col gap-2 sm:flex-row" @submit.prevent="renameHousehold">
                    <input v-model="renameForm.name" :disabled="details.role !== 'owner'" class="flex-1 rounded-md border border-slate-300 px-3 py-2 disabled:bg-slate-50 disabled:text-slate-500">
                    <button v-if="details.role === 'owner'" class="rounded-md border border-slate-300 px-3 py-2 text-slate-700 hover:bg-slate-50">Переименовать</button>
                </form>
            </section>

            <section class="mb-4 overflow-hidden rounded-lg border border-slate-200 bg-white">
                <div class="border-b border-slate-200 p-4">
                    <h2 class="font-medium text-slate-900">Участники</h2>
                    <form v-if="details.role === 'owner'" class="mt-3 flex max-w-xl flex-col gap-2 sm:flex-row" @submit.prevent="addMember">
                        <input v-model="memberForm.email" type="email" placeholder="Почта участника" class="flex-1 rounded-md border border-slate-300 px-3 py-2">
                        <button class="rounded-md border border-slate-300 px-3 py-2 text-slate-700 hover:bg-slate-50">Добавить</button>
                    </form>
                    <span v-if="fieldErrors.email" class="mt-1 block text-xs text-red-600">{{ fieldErrors.email[0] }}</span>
                </div>
                <ul class="divide-y divide-slate-100">
                    <li v-for="member in details.members" :key="member.user_id" class="flex flex-wrap items-center gap-3 px-4 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-slate-900">{{ member.name }} <span v-if="member.user_id === currentUserId" class="font-normal text-slate-400">(вы)</span></p>
                            <p class="truncate text-xs text-slate-500">{{ member.email }} · вступил(а) {{ formatDate(member.joined_at) }}</p>
                        </div>
                        <span class="text-xs text-slate-500">{{ member.role === 'owner' ? 'Владелец' : 'Участник' }}</span>
                        <template v-if="details.role === 'owner' && member.role === 'member'">
                            <button class="rounded px-2 py-1 text-slate-600 hover:bg-slate-100" @click="transferOwnership(member)">Сделать владельцем</button>
                            <button class="rounded px-2 py-1 text-red-600 hover:bg-red-50" @click="removeMember(member)">Удалить</button>
                        </template>
                    </li>
                </ul>
            </section>

            <section class="mb-4 rounded-lg border border-slate-200 bg-white p-4">
                <h2 class="font-medium text-slate-900">Напоминания о запасах</h2>
                <form class="mt-3 flex flex-wrap items-end gap-3" @submit.prevent="saveReminderSettings">
                    <label class="flex items-center gap-2 pb-2 text-sm text-slate-700">
                        <input v-model="reminderForm.enabled" type="checkbox" class="accent-slate-900">
                        Включены
                    </label>
                    <label class="text-sm">
                        <span class="mb-1 block text-xs text-slate-500">Повторять каждые, часов</span>
                        <input v-model.number="reminderForm.intervalHours" type="number" min="1" max="720" class="w-32 rounded-md border border-slate-300 px-3 py-2">
                    </label>
                    <button class="rounded-md border border-slate-300 px-3 py-2 text-slate-700 hover:bg-slate-50">Сохранить</button>
                </form>
                <span v-if="fieldErrors.interval_hours" class="mt-1 block text-xs text-red-600">{{ fieldErrors.interval_hours[0] }}</span>
            </section>

            <section class="rounded-lg border border-red-200 bg-white p-4">
                <div class="flex flex-wrap gap-2">
                    <button class="rounded-md border border-slate-300 px-3 py-2 text-slate-700 hover:bg-slate-50" @click="leaveHousehold">Покинуть дом</button>
                    <button v-if="details.role === 'owner'" class="rounded-md border border-red-300 px-3 py-2 text-red-700 hover:bg-red-50" @click="deleteHousehold">Удалить дом</button>
                </div>
            </section>
        </template>
    </div>
</template>
