<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import ShoppingList from '../components/shopping/ShoppingList.vue'
import ShoppingListForm from '../components/shopping/ShoppingListForm.vue'
import ModalDialog from '../components/ui/ModalDialog.vue'
import { useHouseholds } from '../composables/useHouseholds'
import http from '../lib/http'
import { errorMessage } from '../lib/apiError'
import type { ApiResponse } from '../types/api'
import type { CatalogProduct, MeasurementUnit, StorageLocation } from '../types/inventory'
import type ShoppingItem from '../types/shoppingItem'
import type { NewShoppingItemData } from '../types/shoppingItem'

type ItemFilter = 'all' | 'active' | 'completed'

const { selectedHouseholdUuid } = useHouseholds()
const items = ref<ShoppingItem[]>([])
const products = ref<CatalogProduct[]>([])
const locations = ref<StorageLocation[]>([])
const selectedFilter = ref<ItemFilter>('all')
const pageError = ref<string | null>(null)
const purchaseItem = ref<ShoppingItem | null>(null)
const purchaseLocationUuid = ref('')
const remainingItemCount = computed(() => items.value.filter(item => item.completed_at === null).length)
const visibleItems = computed(() => {
    if (selectedFilter.value === 'active') return items.value.filter(item => item.completed_at === null)
    if (selectedFilter.value === 'completed') return items.value.filter(item => item.completed_at !== null)
    return items.value
})

onMounted(loadProducts)
watch(selectedHouseholdUuid, loadItems, { immediate: true })
watch(selectedHouseholdUuid, loadLocations, { immediate: true })

async function loadProducts(): Promise<void> {
    try {
        const response = await http.get<ApiResponse<CatalogProduct[]>>('/api/products')
        products.value = response.data.data
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось загрузить каталог продуктов.')
    }
}

async function loadItems(): Promise<void> {
    if (selectedHouseholdUuid.value === null) {
        items.value = []
        return
    }
    pageError.value = null
    const householdUuid = selectedHouseholdUuid.value
    try {
        const response = await http.get<ApiResponse<ShoppingItem[]>>(`/api/households/${householdUuid}/shopping-list-items`)
        if (selectedHouseholdUuid.value === householdUuid) items.value = response.data.data
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось загрузить список покупок.')
    }
}

async function loadLocations(): Promise<void> {
    if (selectedHouseholdUuid.value === null) {
        locations.value = []
        return
    }
    const householdUuid = selectedHouseholdUuid.value
    try {
        const response = await http.get<ApiResponse<StorageLocation[]>>(`/api/households/${householdUuid}/storage-locations`)
        if (selectedHouseholdUuid.value === householdUuid) locations.value = response.data.data
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось загрузить места хранения.')
    }
}

async function addItem(data: NewShoppingItemData): Promise<void> {
    if (selectedHouseholdUuid.value === null) return
    try {
        await http.post(`/api/households/${selectedHouseholdUuid.value}/shopping-list-items`, {
            product_uuid: data.productUuid,
            quantity: data.quantity,
            unit: data.unit,
        })
        await loadItems()
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось добавить товар.')
    }
}

async function toggleItem(item: ShoppingItem): Promise<void> {
    if (selectedHouseholdUuid.value === null) return
    const operation = item.completed_at === null ? 'complete' : 'reopen'
    try {
        const response = await http.patch<ApiResponse<ShoppingItem>>(`/api/households/${selectedHouseholdUuid.value}/shopping-list-items/${item.uuid}/${operation}`)
        replaceItem(response.data.data)
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось обновить товар.')
    }
}

async function updateItem(itemUuid: string, quantity: string, unit: MeasurementUnit): Promise<void> {
    if (selectedHouseholdUuid.value === null) return
    try {
        const response = await http.patch<ApiResponse<ShoppingItem>>(`/api/households/${selectedHouseholdUuid.value}/shopping-list-items/${itemUuid}`, { quantity, unit })
        replaceItem(response.data.data)
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось изменить количество.')
        await loadItems()
    }
}

async function removeItem(itemUuid: string): Promise<void> {
    if (selectedHouseholdUuid.value === null || !window.confirm('Удалить этот товар из списка?')) return
    try {
        await http.delete(`/api/households/${selectedHouseholdUuid.value}/shopping-list-items/${itemUuid}`)
        items.value = items.value.filter(item => item.uuid !== itemUuid)
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось удалить товар.')
    }
}

function openPurchase(item: ShoppingItem): void {
    purchaseItem.value = item
    purchaseLocationUuid.value = locations.value[0]?.uuid ?? ''
}

async function confirmPurchase(): Promise<void> {
    if (selectedHouseholdUuid.value === null || purchaseItem.value === null || purchaseLocationUuid.value === '') return
    try {
        const response = await http.post<ApiResponse<ShoppingItem>>(
            `/api/households/${selectedHouseholdUuid.value}/shopping-list-items/${purchaseItem.value.uuid}/purchase`,
            { storage_location_uuid: purchaseLocationUuid.value }
        )
        replaceItem(response.data.data)
        purchaseItem.value = null
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось добавить покупку в запасы. Убедитесь, что продукт отслеживается в этом доме.')
    }
}

function replaceItem(updatedItem: ShoppingItem): void {
    items.value = items.value.map(item => item.uuid === updatedItem.uuid ? updatedItem : item)
}

function filterLabel(filter: ItemFilter): string {
    return filter === 'active' ? 'Активные' : filter === 'completed' ? 'Выполненные' : 'Все'
}
</script>

<template>
    <div class="space-y-5">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-950">Список покупок</h1>
                <p class="mt-1 text-sm text-slate-500">Осталось купить: {{ remainingItemCount }}</p>
            </div>
            <fieldset class="inline-flex w-fit rounded-md border border-slate-200 bg-white p-1 text-sm">
                <legend class="sr-only">Фильтр списка покупок</legend>
                <label v-for="filter in (['all', 'active', 'completed'] as ItemFilter[])" :key="filter" class="cursor-pointer rounded px-3 py-1.5 capitalize" :class="selectedFilter === filter ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'">
                    <input v-model="selectedFilter" class="sr-only" type="radio" :value="filter">
                    {{ filterLabel(filter) }}
                </label>
            </fieldset>
        </header>

        <p v-if="pageError" class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ pageError }}</p>
        <p v-if="selectedHouseholdUuid === null" class="rounded-lg border border-slate-200 bg-white p-5 text-slate-500">Сначала выберите дом.</p>
        <template v-else>
            <ShoppingListForm :products="products" @add="addItem" />
            <section class="rounded-lg border border-slate-200 bg-white px-4">
                <p v-if="visibleItems.length === 0" class="py-10 text-center text-sm text-slate-500">Здесь пока ничего нет.</p>
                <ShoppingList v-else :items="visibleItems" @toggle="toggleItem" @purchase="openPurchase" @update="updateItem" @remove="removeItem" />
            </section>
        </template>

        <ModalDialog :open="purchaseItem !== null" title="Добавить покупку в запасы" @close="purchaseItem = null">
            <form class="space-y-4" @submit.prevent="confirmPurchase">
                <p class="text-sm text-slate-600">
                    {{ purchaseItem?.product.name }} · {{ purchaseItem?.quantity }} {{ purchaseItem?.unit }}
                </p>
                <label class="block text-sm">
                    <span class="mb-1 block text-slate-600">Место хранения</span>
                    <select v-model="purchaseLocationUuid" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2">
                        <option value="">Выберите место</option>
                        <option v-for="location in locations" :key="location.uuid" :value="location.uuid">{{ location.name }}</option>
                    </select>
                </label>
                <p v-if="locations.length === 0" class="text-sm text-red-600">Сначала создайте место хранения.</p>
                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-md px-3 py-2 text-slate-600 hover:bg-slate-100" @click="purchaseItem = null">Отмена</button>
                    <button :disabled="purchaseLocationUuid === ''" class="rounded-md bg-slate-900 px-3 py-2 text-white disabled:opacity-40">Добавить в запасы</button>
                </div>
            </form>
        </ModalDialog>
    </div>
</template>
