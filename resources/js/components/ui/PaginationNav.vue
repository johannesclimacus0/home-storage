<script setup lang="ts">
import type { PaginationMeta } from '../../types/api'

defineProps<{
    meta: PaginationMeta | null
}>()

defineEmits<{
    change: [page: number]
}>()
</script>

<template>
    <div v-if="meta && meta.last_page > 1" class="flex items-center justify-center border-t border-slate-200 pt-3">
        <div class="flex gap-1">
            <button
                type="button"
                :disabled="meta.current_page === 1"
                class="rounded border border-slate-200 px-2.5 py-1.5 text-xs text-slate-600 hover:bg-slate-50 disabled:opacity-40"
                @click="$emit('change', meta.current_page - 1)"
            >
                Назад
            </button>
            <button
                type="button"
                :disabled="meta.current_page === meta.last_page"
                class="rounded border border-slate-200 px-2.5 py-1.5 text-xs text-slate-600 hover:bg-slate-50 disabled:opacity-40"
                @click="$emit('change', meta.current_page + 1)"
            >
                Далее
            </button>
        </div>
    </div>
</template>
