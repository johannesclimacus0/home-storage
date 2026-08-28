<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import http from '../lib/http'
import { useHouseholds } from '../composables/useHouseholds'
import { baseUnit, formatQuantity, measurementTypeLabel } from '../lib/format'
import { errorMessage, validationErrors } from '../lib/apiError'
import type { ApiResponse, ValidationErrors } from '../types/api'
import type {
    CatalogProduct,
    HouseholdProduct,
    MeasurementType,
    MeasurementUnit,
    StorageLocation,
} from '../types/inventory'
import ModalDialog from '../components/ui/ModalDialog.vue'

type StockOperation = 'add' | 'consume'

const { selectedHouseholdUuid } = useHouseholds()
const products = ref<HouseholdProduct[]>([])
const catalog = ref<CatalogProduct[]>([])
const locations = ref<StorageLocation[]>([])
const pageError = ref<string | null>(null)
const formError = ref<string | null>(null)
const fieldErrors = ref<ValidationErrors>({})
const search = ref('')
const lowStockOnly = ref(false)
const addProductOpen = ref(false)
const thresholdProduct = ref<HouseholdProduct | null>(null)
const stockProduct = ref<HouseholdProduct | null>(null)
const stockOperation = ref<StockOperation>('add')

const addProductForm = reactive({ productUuid: '', threshold: '0' })
const thresholdForm = reactive({ threshold: '0' })
const stockForm = reactive({
    locationUuid: '',
    quantity: '1',
    unit: 'piece' as MeasurementUnit,
})

const visibleProducts = computed(() => {
    const query = search.value.trim().toLocaleLowerCase()

    return products.value.filter(product => {
        const matchesSearch = query === '' || product.name.toLocaleLowerCase().includes(query)
        return matchesSearch && (!lowStockOnly.value || product.is_low_stock)
    })
})

const availableCatalog = computed(() => {
    const existing = new Set(products.value.map(product => product.uuid))
    return catalog.value.filter(product => !existing.has(product.uuid))
})

const stockUnits = computed(() =>
    stockProduct.value ? unitsFor(stockProduct.value.measurement_type) : []
)

watch(selectedHouseholdUuid, loadInventory, { immediate: true })

async function loadInventory(): Promise<void> {
    if (selectedHouseholdUuid.value === null) {
        products.value = []
        locations.value = []
        return
    }

    pageError.value = null

    try {
        const householdUuid = selectedHouseholdUuid.value
        const [productResponse, catalogResponse, locationResponse] = await Promise.all([
            http.get<ApiResponse<HouseholdProduct[]>>(`/api/households/${householdUuid}/products`),
            http.get<ApiResponse<CatalogProduct[]>>('/api/products'),
            http.get<ApiResponse<StorageLocation[]>>(`/api/households/${householdUuid}/storage-locations`),
        ])

        if (selectedHouseholdUuid.value !== householdUuid) return

        products.value = productResponse.data.data
        catalog.value = catalogResponse.data.data
        locations.value = locationResponse.data.data
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось загрузить запасы.')
    }
}

function openAddProduct(): void {
    clearFormState()
    addProductForm.productUuid = availableCatalog.value[0]?.uuid ?? ''
    addProductForm.threshold = '0'
    addProductOpen.value = true
}

async function addProduct(): Promise<void> {
    if (selectedHouseholdUuid.value === null) return

    clearFormState()

    try {
        await http.post(`/api/households/${selectedHouseholdUuid.value}/products`, {
            product_uuid: addProductForm.productUuid,
            low_stock_threshold: addProductForm.threshold,
        })
        addProductOpen.value = false
        await loadInventory()
    } catch (requestError: unknown) {
        setFormError(requestError)
    }
}

function openThreshold(product: HouseholdProduct): void {
    clearFormState()
    thresholdProduct.value = product
    thresholdForm.threshold = product.low_stock_threshold
}

async function updateThreshold(): Promise<void> {
    if (selectedHouseholdUuid.value === null || thresholdProduct.value === null) return

    clearFormState()

    try {
        await http.patch(
            `/api/households/${selectedHouseholdUuid.value}/products/${thresholdProduct.value.uuid}`,
            { low_stock_threshold: thresholdForm.threshold }
        )
        thresholdProduct.value = null
        await loadInventory()
    } catch (requestError: unknown) {
        setFormError(requestError)
    }
}

function openStock(product: HouseholdProduct, operation: StockOperation): void {
    clearFormState()
    stockProduct.value = product
    stockOperation.value = operation
    stockForm.locationUuid = locations.value[0]?.uuid ?? ''
    stockForm.quantity = '1'
    stockForm.unit = preferredUnit(product.measurement_type)
}

async function submitStock(): Promise<void> {
    if (selectedHouseholdUuid.value === null || stockProduct.value === null) return

    clearFormState()
    const suffix = stockOperation.value === 'add' ? 'stocks' : 'consume'

    try {
        await http.post(
            `/api/households/${selectedHouseholdUuid.value}/products/${stockProduct.value.uuid}/${suffix}`,
            {
                storage_location_uuid: stockForm.locationUuid,
                quantity: stockForm.quantity,
                unit: stockForm.unit,
            }
        )
        stockProduct.value = null
        await loadInventory()
    } catch (requestError: unknown) {
        setFormError(requestError)
    }
}

async function removeProduct(product: HouseholdProduct): Promise<void> {
    if (
        selectedHouseholdUuid.value === null ||
        !window.confirm(`Удалить «${product.name}» из запасов этого дома?`)
    ) return

    try {
        await http.delete(`/api/households/${selectedHouseholdUuid.value}/products/${product.uuid}`)
        products.value = products.value.filter(item => item.uuid !== product.uuid)
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось удалить продукт.')
    }
}

function selectedCatalogProduct(): CatalogProduct | undefined {
    return catalog.value.find(product => product.uuid === addProductForm.productUuid)
}

function unitsFor(type: MeasurementType): Array<{ value: MeasurementUnit; label: string }> {
    if (type === 'mass') return [{ value: 'kg', label: 'кг' }, { value: 'g', label: 'г' }]
    if (type === 'volume') return [{ value: 'l', label: 'л' }, { value: 'ml', label: 'мл' }]
    return [{ value: 'piece', label: 'шт.' }]
}

function preferredUnit(type: MeasurementType): MeasurementUnit {
    return type === 'mass' ? 'kg' : type === 'volume' ? 'l' : 'piece'
}

function clearFormState(): void {
    formError.value = null
    fieldErrors.value = {}
}

function setFormError(requestError: unknown): void {
    fieldErrors.value = validationErrors(requestError)
    formError.value = errorMessage(requestError, 'Не удалось сохранить изменения.')
}
</script>

<template>
    <div>
        <header class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-950">Запасы</h1>
            </div>
            <button
                type="button"
                :disabled="selectedHouseholdUuid === null"
                class="rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-40"
                @click="openAddProduct"
            >
                Добавить продукт
            </button>
        </header>

        <div v-if="selectedHouseholdUuid === null" class="rounded-lg border border-slate-200 bg-white p-5 text-slate-500">
            Создайте или выберите дом, чтобы управлять запасами.
            <RouterLink :to="{ name: 'household-settings' }" class="ml-1 text-slate-900 underline">Дома</RouterLink>
        </div>

        <template v-else>
            <p v-if="pageError" class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ pageError }}</p>

            <div class="mb-3 flex flex-wrap items-center gap-3">
                <input v-model="search" type="search" placeholder="Поиск продуктов" class="w-full max-w-xs rounded-md border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-slate-500">
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input v-model="lowStockOnly" type="checkbox" class="accent-slate-900">
                    Только заканчивающиеся
                </label>
                <span class="ml-auto text-xs text-slate-400">Продуктов: {{ visibleProducts.length }}</span>
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                <p v-if="visibleProducts.length === 0" class="p-5 text-sm text-slate-500">Продукты не найдены.</p>

                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 text-xs font-medium tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-2.5">Продукт</th>
                                <th class="px-4 py-2.5">Доступно</th>
                                <th class="px-4 py-2.5">Порог</th>
                                <th class="px-4 py-2.5">Состояние</th>
                                <th class="px-4 py-2.5 text-right">Действия</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="product in visibleProducts" :key="product.uuid">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ product.name }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ formatQuantity(product.total_quantity, product.measurement_type) }}</td>
                                <td class="px-4 py-3 tabular-nums text-slate-500">{{ formatQuantity(product.low_stock_threshold, product.measurement_type) }}</td>
                                <td class="px-4 py-3">
                                    <span :class="product.is_low_stock ? 'text-amber-700' : 'text-emerald-700'">{{ product.is_low_stock ? 'Заканчивается' : 'В наличии' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1">
                                        <button class="rounded px-2 py-1 text-slate-600 hover:bg-slate-100" @click="openStock(product, 'add')">Пополнить</button>
                                        <button class="rounded px-2 py-1 text-slate-600 hover:bg-slate-100" @click="openStock(product, 'consume')">Списать</button>
                                        <button class="rounded px-2 py-1 text-slate-600 hover:bg-slate-100" @click="openThreshold(product)">Порог</button>
                                        <button class="rounded px-2 py-1 text-red-600 hover:bg-red-50" @click="removeProduct(product)">Удалить</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <ModalDialog :open="addProductOpen" title="Добавить продукт в дом" @close="addProductOpen = false">
            <form class="space-y-4" @submit.prevent="addProduct">
                <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>
                <label class="block text-sm">
                    <span class="mb-1 block text-slate-600">Продукт из каталога</span>
                    <select v-model="addProductForm.productUuid" class="w-full rounded-md border border-slate-300 px-3 py-2">
                        <option value="" disabled>Выберите продукт</option>
                        <option v-for="product in availableCatalog" :key="product.uuid" :value="product.uuid">{{ product.name }} · {{ measurementTypeLabel(product.measurement_type) }}</option>
                    </select>
                    <span v-if="fieldErrors.product_uuid" class="mt-1 block text-xs text-red-600">{{ fieldErrors.product_uuid[0] }}</span>
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-slate-600">Порог уведомления<span v-if="selectedCatalogProduct()">, {{ baseUnit(selectedCatalogProduct()!.measurement_type) }}</span></span>
                    <input v-model="addProductForm.threshold" inputmode="decimal" class="w-full rounded-md border border-slate-300 px-3 py-2">
                    <span v-if="fieldErrors.low_stock_threshold" class="mt-1 block text-xs text-red-600">{{ fieldErrors.low_stock_threshold[0] }}</span>
                </label>
                <p v-if="availableCatalog.length === 0" class="text-sm text-slate-500">Все продукты из каталога уже добавлены в этот дом.</p>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-100" @click="addProductOpen = false">Отмена</button>
                    <button :disabled="addProductForm.productUuid === ''" class="rounded-md bg-slate-900 px-3 py-2 text-sm text-white disabled:opacity-40">Добавить</button>
                </div>
            </form>
        </ModalDialog>

        <ModalDialog :open="thresholdProduct !== null" title="Порог уведомления" @close="thresholdProduct = null">
            <form class="space-y-4" @submit.prevent="updateThreshold">
                <p class="text-sm text-slate-600">{{ thresholdProduct?.name }}</p>
                <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>
                <label class="block text-sm">
                    <span class="mb-1 block text-slate-600">Порог, {{ thresholdProduct ? baseUnit(thresholdProduct.measurement_type) : '' }}</span>
                    <input v-model="thresholdForm.threshold" inputmode="decimal" class="w-full rounded-md border border-slate-300 px-3 py-2">
                    <span v-if="fieldErrors.low_stock_threshold" class="mt-1 block text-xs text-red-600">{{ fieldErrors.low_stock_threshold[0] }}</span>
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-100" @click="thresholdProduct = null">Отмена</button>
                    <button class="rounded-md bg-slate-900 px-3 py-2 text-sm text-white">Сохранить</button>
                </div>
            </form>
        </ModalDialog>

        <ModalDialog :open="stockProduct !== null" :title="stockOperation === 'add' ? 'Пополнить запас' : 'Списать запас'" @close="stockProduct = null">
            <form class="space-y-4" @submit.prevent="submitStock">
                <p class="text-sm text-slate-600">{{ stockProduct?.name }}</p>
                <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>
                <p v-if="locations.length === 0" class="rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-800">Сначала создайте место хранения.</p>
                <label class="block text-sm">
                    <span class="mb-1 block text-slate-600">Место хранения</span>
                    <select v-model="stockForm.locationUuid" class="w-full rounded-md border border-slate-300 px-3 py-2">
                        <option v-for="location in locations" :key="location.uuid" :value="location.uuid">{{ location.name }}</option>
                    </select>
                    <span v-if="fieldErrors.storage_location_uuid" class="mt-1 block text-xs text-red-600">{{ fieldErrors.storage_location_uuid[0] }}</span>
                </label>
                <div class="grid grid-cols-[1fr_7rem] gap-3">
                    <label class="block text-sm">
                        <span class="mb-1 block text-slate-600">Количество</span>
                        <input v-model="stockForm.quantity" inputmode="decimal" class="w-full rounded-md border border-slate-300 px-3 py-2">
                    </label>
                    <label class="block text-sm">
                        <span class="mb-1 block text-slate-600">Единица</span>
                        <select v-model="stockForm.unit" class="w-full rounded-md border border-slate-300 px-3 py-2">
                            <option v-for="unit in stockUnits" :key="unit.value" :value="unit.value">{{ unit.label }}</option>
                        </select>
                    </label>
                </div>
                <span v-if="fieldErrors.quantity" class="block text-xs text-red-600">{{ fieldErrors.quantity[0] }}</span>
                <span v-if="fieldErrors.unit" class="block text-xs text-red-600">{{ fieldErrors.unit[0] }}</span>
                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-100" @click="stockProduct = null">Отмена</button>
                    <button :disabled="locations.length === 0" class="rounded-md bg-slate-900 px-3 py-2 text-sm text-white disabled:opacity-40">{{ stockOperation === 'add' ? 'Пополнить' : 'Списать' }}</button>
                </div>
            </form>
        </ModalDialog>
    </div>
</template>
