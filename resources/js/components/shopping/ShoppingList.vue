<script setup lang="ts">
import type { MeasurementUnit } from '../../types/inventory'
import { measurementUnitLabel } from '../../lib/format'
import type ShoppingItem from '../../types/shoppingItem'

defineProps<{ items: ShoppingItem[] }>()
const emit = defineEmits<{
    toggle: [item: ShoppingItem]
    purchase: [item: ShoppingItem]
    update: [itemUuid: string, quantity: string, unit: MeasurementUnit]
    remove: [itemUuid: string]
}>()

function updateQuantity(event: Event, item: ShoppingItem): void {
    emit('update', item.uuid, (event.target as HTMLInputElement).value, item.unit)
}
</script>

<template>
    <article v-for="item in items" :key="item.uuid" class="flex flex-col gap-3 border-b border-slate-100 py-3 last:border-0 sm:flex-row sm:items-center">
        <button type="button" class="size-5 shrink-0 rounded border text-xs" :class="item.completed_at === null ? 'border-slate-300 bg-white' : 'border-slate-900 bg-slate-900 text-white'" :aria-label="item.completed_at === null ? 'Отметить выполненным' : 'Вернуть в список'" @click="emit('toggle', item)">
            {{ item.completed_at === null ? '' : '✓' }}
        </button>
        <div class="min-w-0 flex-1">
            <p class="font-medium text-slate-900" :class="{ 'text-slate-400 line-through': item.completed_at !== null }">{{ item.product.name }}</p>
            <p class="text-xs text-slate-400">Добавил: {{ item.added_by.name }}</p>
        </div>
        <div class="flex items-center gap-2">
            <input :value="item.quantity" type="number" min="0.001" step="0.001" class="w-28 rounded-md border border-slate-300 px-2 py-1.5 text-sm" aria-label="Необходимое количество" @change="updateQuantity($event, item)">
            <span class="w-10 text-sm text-slate-500">{{ measurementUnitLabel(item.unit) }}</span>
            <button v-if="item.completed_at === null" type="button" class="rounded px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-100" @click="emit('purchase', item)">Куплено</button>
            <button type="button" class="rounded px-2 py-1.5 text-sm text-red-600 hover:bg-red-50" @click="emit('remove', item.uuid)">Удалить</button>
        </div>
    </article>
</template>
