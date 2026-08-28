<script setup lang="ts">
import type Product from '../../types/product'
import { measurementTypeLabel } from '../../lib/format'

defineProps<{
    products: Product[]
}>()
const emit = defineEmits<{
    consume: [productUuid: string]
}>()
</script>

<template>
    <article
        v-for="product in products"
        :key="product.uuid"
        class="flex flex-col gap-4 rounded-xl border p-4 transition sm:flex-row sm:items-center"
        :class="product.quantity <= product.lowStockThreshold
            ? 'border-amber-200 bg-amber-50/70'
            : 'border-slate-200 bg-white hover:border-slate-300'"
    >
        <div class="min-w-0 flex-1">
            <h2 class="font-medium text-slate-900">{{ product.name }}</h2>
            <p class="mt-1 text-sm capitalize text-slate-500">
                {{ measurementTypeLabel(product.measurementType) }}
            </p>
        </div>

        <strong class="text-sm text-slate-700">{{ product.quantity }}</strong>

        <button
            type="button"
            :disabled="product.quantity === 0"
            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-400"
            @click="emit('consume', product.uuid)"
        >
            Списать одну единицу
        </button>
    </article>

</template>
