<script setup lang="ts">
defineProps<{
    modelValue: string
    placeholder?: string
}>()

defineEmits<{
    'update:modelValue': [value: string]
}>()
</script>

<template>
    <input
        :value="modelValue"
        type="search"
        :placeholder="placeholder"
        class="w-full max-w-xs rounded-md border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-slate-500"
        @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    >
</template>
