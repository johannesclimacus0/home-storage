<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import http from '../lib/http'
import { useHouseholds } from '../composables/useHouseholds'
import { errorMessage, validationErrors } from '../lib/apiError'
import type { ApiResponse, ValidationErrors } from '../types/api'
import type { StorageLocation } from '../types/inventory'
import ModalDialog from '../components/ui/ModalDialog.vue'

const { selectedHouseholdUuid } = useHouseholds()
const locations = ref<StorageLocation[]>([])
const pageError = ref<string | null>(null)
const formError = ref<string | null>(null)
const fieldErrors = ref<ValidationErrors>({})
const modalOpen = ref(false)
const editingLocation = ref<StorageLocation | null>(null)
const form = reactive({ name: '' })

watch(selectedHouseholdUuid, loadLocations, { immediate: true })

async function loadLocations(): Promise<void> {
    if (selectedHouseholdUuid.value === null) {
        locations.value = []
        return
    }

    pageError.value = null

    try {
        const householdUuid = selectedHouseholdUuid.value
        const response = await http.get<ApiResponse<StorageLocation[]>>(
            `/api/households/${householdUuid}/storage-locations`
        )

        if (selectedHouseholdUuid.value !== householdUuid) return
        locations.value = response.data.data
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось загрузить места хранения.')
    }
}

function openCreate(): void {
    editingLocation.value = null
    form.name = ''
    clearErrors()
    modalOpen.value = true
}

function openEdit(location: StorageLocation): void {
    editingLocation.value = location
    form.name = location.name
    clearErrors()
    modalOpen.value = true
}

async function saveLocation(): Promise<void> {
    if (selectedHouseholdUuid.value === null) return

    clearErrors()

    try {
        const baseUrl = `/api/households/${selectedHouseholdUuid.value}/storage-locations`
        if (editingLocation.value === null) {
            await http.post(baseUrl, { name: form.name })
        } else {
            await http.patch(`${baseUrl}/${editingLocation.value.uuid}`, { name: form.name })
        }

        modalOpen.value = false
        await loadLocations()
    } catch (requestError: unknown) {
        fieldErrors.value = validationErrors(requestError)
        formError.value = errorMessage(requestError, 'Не удалось сохранить место хранения.')
    }
}

async function removeLocation(location: StorageLocation): Promise<void> {
    if (
        selectedHouseholdUuid.value === null ||
        !window.confirm(`Удалить место «${location.name}»?`)
    ) return

    try {
        await http.delete(`/api/households/${selectedHouseholdUuid.value}/storage-locations/${location.uuid}`)
        locations.value = locations.value.filter(item => item.uuid !== location.uuid)
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось удалить место хранения.')
    }
}

function clearErrors(): void {
    formError.value = null
    fieldErrors.value = {}
}
</script>

<template>
    <div>
        <header class="mb-5 flex items-end justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-950">Места хранения</h1>
            </div>
            <button :disabled="selectedHouseholdUuid === null" class="rounded-md bg-slate-900 px-3 py-2 text-sm text-white disabled:opacity-40" @click="openCreate">Новое место</button>
        </header>

        <p v-if="pageError" class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-red-700">{{ pageError }}</p>
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <p v-if="selectedHouseholdUuid === null" class="p-5 text-slate-500">Сначала выберите дом.</p>
            <p v-else-if="locations.length === 0" class="p-5 text-slate-500">Мест хранения пока нет.</p>
            <ul v-else class="divide-y divide-slate-100">
                <li v-for="location in locations" :key="location.uuid" class="flex items-center justify-between gap-3 px-4 py-3">
                    <span class="font-medium text-slate-900">{{ location.name }}</span>
                    <div class="flex gap-1">
                        <button class="rounded px-2 py-1 text-slate-600 hover:bg-slate-100" @click="openEdit(location)">Переименовать</button>
                        <button class="rounded px-2 py-1 text-red-600 hover:bg-red-50" @click="removeLocation(location)">Удалить</button>
                    </div>
                </li>
            </ul>
        </div>

        <ModalDialog :open="modalOpen" :title="editingLocation ? 'Переименовать место' : 'Новое место хранения'" @close="modalOpen = false">
            <form class="space-y-4" @submit.prevent="saveLocation">
                <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>
                <label class="block text-sm">
                    <span class="mb-1 block text-slate-600">Название</span>
                    <input v-model="form.name" autofocus class="w-full rounded-md border border-slate-300 px-3 py-2">
                    <span v-if="fieldErrors.name" class="mt-1 block text-xs text-red-600">{{ fieldErrors.name[0] }}</span>
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-md px-3 py-2 text-slate-600 hover:bg-slate-100" @click="modalOpen = false">Отмена</button>
                    <button class="rounded-md bg-slate-900 px-3 py-2 text-white">Сохранить</button>
                </div>
            </form>
        </ModalDialog>
    </div>
</template>
