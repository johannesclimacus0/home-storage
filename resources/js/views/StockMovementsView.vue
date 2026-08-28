<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import http from '../lib/http'
import { useHouseholds } from '../composables/useHouseholds'
import { errorMessage } from '../lib/apiError'
import { formatDate, measurementUnitLabel, movementTypeLabel } from '../lib/format'
import type { ApiResponse, PaginatedResponse, PaginationMeta } from '../types/api'
import type { HouseholdProduct, StockMovement, StockMovementType } from '../types/inventory'
import PaginationNav from '../components/ui/PaginationNav.vue'

const { selectedHouseholdUuid } = useHouseholds()
const movements = ref<StockMovement[]>([])
const products = ref<HouseholdProduct[]>([])
const meta = ref<PaginationMeta | null>(null)
const pageError = ref<string | null>(null)
const filters = reactive<{ productUuid: string; type: '' | StockMovementType }>({
    productUuid: '',
    type: '',
})

watch(selectedHouseholdUuid, () => loadMovements(1), { immediate: true })

async function loadMovements(page = 1): Promise<void> {
    if (selectedHouseholdUuid.value === null) {
        movements.value = []
        meta.value = null
        return
    }

    pageError.value = null

    try {
        const householdUuid = selectedHouseholdUuid.value
        const [movementResponse, productResponse] = await Promise.all([
            http.get<PaginatedResponse<StockMovement>>(`/api/households/${householdUuid}/stock-movements`, {
                params: {
                    page,
                    per_page: 20,
                    product_uuid: filters.productUuid || undefined,
                    type: filters.type || undefined,
                },
            }),
            http.get<ApiResponse<HouseholdProduct[]>>(`/api/households/${householdUuid}/products`),
        ])

        if (selectedHouseholdUuid.value !== householdUuid) return

        movements.value = movementResponse.data.data
        meta.value = movementResponse.data.meta
        products.value = productResponse.data.data
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось загрузить историю запасов.')
    }
}
</script>

<template>
    <div>
        <header class="mb-5">
            <h1 class="text-xl font-semibold text-slate-950">История запасов</h1>
        </header>

        <form class="mb-3 flex flex-wrap items-end gap-3" @submit.prevent="loadMovements(1)">
            <label class="text-sm">
                <span class="mb-1 block text-xs text-slate-500">Продукт</span>
                <select v-model="filters.productUuid" class="min-w-44 rounded-md border border-slate-300 bg-white px-3 py-2">
                    <option value="">Все продукты</option>
                    <option v-for="product in products" :key="product.uuid" :value="product.uuid">{{ product.name }}</option>
                </select>
            </label>
            <label class="text-sm">
                <span class="mb-1 block text-xs text-slate-500">Тип</span>
                <select v-model="filters.type" class="rounded-md border border-slate-300 bg-white px-3 py-2">
                    <option value="">Все типы</option>
                    <option value="purchase">Покупка</option>
                    <option value="consumption">Расход</option>
                    <option value="adjustment">Корректировка</option>
                </select>
            </label>
            <button class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Применить</button>
        </form>

        <p v-if="pageError" class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-red-700">{{ pageError }}</p>
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <p v-if="selectedHouseholdUuid === null" class="p-5 text-slate-500">Сначала выберите дом.</p>
            <p v-else-if="movements.length === 0" class="p-5 text-slate-500">Движения запасов не найдены.</p>
            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5">Дата</th>
                            <th class="px-4 py-2.5">Продукт</th>
                            <th class="px-4 py-2.5">Тип</th>
                            <th class="px-4 py-2.5">Введено</th>
                            <th class="px-4 py-2.5">Остаток</th>
                            <th class="px-4 py-2.5">Место</th>
                            <th class="px-4 py-2.5">Пользователь</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="movement in movements" :key="movement.uuid">
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ formatDate(movement.created_at) }}</td>
                            <td class="px-4 py-3 font-medium">{{ movement.product.name }}</td>
                            <td class="px-4 py-3">{{ movementTypeLabel(movement.type) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ movement.input.quantity }} {{ measurementUnitLabel(movement.input.unit) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ movement.quantity_before }} → {{ movement.quantity_after }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ movement.storage_location.name }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ movement.actor.name }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <PaginationNav class="mt-3" :meta="meta" @change="loadMovements" />
    </div>
</template>
