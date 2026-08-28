<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import type { CatalogProduct, MeasurementUnit } from '../../types/inventory'
import type { NewShoppingItemData } from '../../types/shoppingItem'
import { measurementUnitLabel } from '../../lib/format'

const props = defineProps<{ products: CatalogProduct[] }>()
const emit = defineEmits<{ add: [item: NewShoppingItemData] }>()
const form = reactive<NewShoppingItemData>({ productUuid: '', quantity: '1', unit: 'piece' })
const error = ref<string | null>(null)
const selectedProduct = computed(() => props.products.find(product => product.uuid === form.productUuid) ?? null)
const units = computed<MeasurementUnit[]>(() => {
    if (selectedProduct.value?.measurement_type === 'mass') return ['g', 'kg']
    if (selectedProduct.value?.measurement_type === 'volume') return ['ml', 'l']
    return ['piece']
})

watch(selectedProduct, () => {
    form.unit = units.value[0]
})

function submit(): void {
    if (form.productUuid === '' || Number(form.quantity) <= 0) {
        error.value = 'Выберите продукт и укажите количество больше нуля.'
        return
    }

    error.value = null
    emit('add', {
        ...form,
        quantity: String(form.quantity)
    })
    Object.assign(form, { productUuid: '', quantity: '1', unit: 'piece' })
}
</script>

<template>
    <form class="rounded-lg border border-slate-200 bg-white p-4" @submit.prevent="submit">
        <p v-if="error" class="mb-3 text-sm text-red-600">{{ error }}</p>
        <div class="grid gap-3 sm:grid-cols-[1fr_8rem_7rem_auto] sm:items-end">
            <label class="block text-sm">
                <span class="mb-1 block text-slate-600">Продукт</span>
                <select v-model="form.productUuid" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2">
                    <option value="">Выберите продукт</option>
                    <option v-for="product in products" :key="product.uuid" :value="product.uuid">{{ product.name }}</option>
                </select>
            </label>
            <label class="block text-sm">
                <span class="mb-1 block text-slate-600">Количество</span>
                <input v-model="form.quantity" type="number" min="0.001" step="0.001" class="w-full rounded-md border border-slate-300 px-3 py-2">
            </label>
            <label class="block text-sm">
                <span class="mb-1 block text-slate-600">Единица</span>
                <select v-model="form.unit" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2">
                    <option v-for="unit in units" :key="unit" :value="unit">{{ measurementUnitLabel(unit) }}</option>
                </select>
            </label>
            <button class="rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-700">Добавить</button>
        </div>
    </form>
</template>
