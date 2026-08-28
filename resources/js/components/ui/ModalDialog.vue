<script setup lang="ts">
defineProps<{
    open: boolean
    title: string
}>()

defineEmits<{
    close: []
}>()
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-50 grid place-items-center bg-black/25 p-4" @mousedown.self="$emit('close')">
            <section
                class="w-full max-w-md rounded-lg border border-slate-200 bg-white shadow-xl"
                role="dialog"
                aria-modal="true"
                :aria-label="title"
            >
                <header class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <h2 class="font-medium text-slate-900">{{ title }}</h2>
                    <button
                        type="button"
                        class="rounded px-2 py-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Закрыть"
                        @click="$emit('close')"
                    >
                        ×
                    </button>
                </header>

                <div class="p-4">
                    <slot />
                </div>
            </section>
        </div>
    </Teleport>
</template>
