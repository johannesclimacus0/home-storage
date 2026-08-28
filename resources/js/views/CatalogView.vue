<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import http from '../lib/http'
import { errorMessage, validationErrors } from '../lib/apiError'
import type { ApiResponse, ValidationErrors } from '../types/api'
import type { CatalogProduct, MeasurementType } from '../types/inventory'
import ModalDialog from '../components/ui/ModalDialog.vue'
import { measurementTypeLabel } from '../lib/format'

const products = ref<CatalogProduct[]>([])
const pageError = ref<string | null>(null)
const formError = ref<string | null>(null)
const fieldErrors = ref<ValidationErrors>({})
const search = ref('')
const modalOpen = ref(false)
const form = reactive<{ name: string; measurementType: MeasurementType }>({
    name: '',
    measurementType: 'count',
})

const visibleProducts = computed(() => {
    const query = search.value.trim().toLocaleLowerCase()
    return query === ''
        ? products.value
        : products.value.filter(product => product.name.toLocaleLowerCase().includes(query))
})

onMounted(loadProducts)

async function loadProducts(): Promise<void> {
    pageError.value = null

    try {
        const response = await http.get<ApiResponse<CatalogProduct[]>>('/api/products')
        products.value = response.data.data
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось загрузить каталог продуктов.')
    }
}

function openCreate(): void {
    form.name = ''
    form.measurementType = 'count'
    formError.value = null
    fieldErrors.value = {}
    modalOpen.value = true
}

async function createProduct(): Promise<void> {
    formError.value = null
    fieldErrors.value = {}

    try {
        await http.post('/api/products', {
            name: form.name,
            measurement_type: form.measurementType,
        })
        modalOpen.value = false
        await loadProducts()
    } catch (requestError: unknown) {
        fieldErrors.value = validationErrors(requestError)
        formError.value = errorMessage(requestError, 'Не удалось создать продукт.')
    }
}
</script>

<template>
    <div>
        <header class="mb-5 flex items-end justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-950">Каталог продуктов</h1>
            </div>
            <button class="rounded-md bg-slate-900 px-3 py-2 text-sm text-white" @click="openCreate">Новый продукт</button>
        </header>

        <input v-model="search" type="search" placeholder="Поиск по каталогу" class="mb-3 w-full max-w-xs rounded-md border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-slate-500">
        <p v-if="pageError" class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-red-700">{{ pageError }}</p>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <p v-if="visibleProducts.length === 0" class="p-5 text-slate-500">Продукты не найдены.</p>
            <table v-else class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs tracking-wide text-slate-500">
                    <tr><th class="px-4 py-2.5">Название</th><th class="px-4 py-2.5">Тип измерения</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="product in visibleProducts" :key="product.uuid">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ product.name }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ measurementTypeLabel(product.measurement_type) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <ModalDialog :open="modalOpen" title="Новый продукт в каталоге" @close="modalOpen = false">
            <form class="space-y-4" @submit.prevent="createProduct">
                <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>
                <label class="block text-sm">
                    <span class="mb-1 block text-slate-600">Название</span>
                    <input v-model="form.name" class="w-full rounded-md border border-slate-300 px-3 py-2">
                    <span v-if="fieldErrors.name" class="mt-1 block text-xs text-red-600">{{ fieldErrors.name[0] }}</span>
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-slate-600">Тип измерения</span>
                    <select v-model="form.measurementType" class="w-full rounded-md border border-slate-300 px-3 py-2">
                        <option value="count">Количество</option>
                        <option value="mass">Масса</option>
                        <option value="volume">Объём</option>
                    </select>
                    <span v-if="fieldErrors.measurement_type" class="mt-1 block text-xs text-red-600">{{ fieldErrors.measurement_type[0] }}</span>
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-md px-3 py-2 text-slate-600 hover:bg-slate-100" @click="modalOpen = false">Отмена</button>
                    <button class="rounded-md bg-slate-900 px-3 py-2 text-white">Создать</button>
                </div>
            </form>
        </ModalDialog>
    </div>
</template>
