<script setup lang="ts">
import { onMounted } from 'vue'
import { useHouseholds } from '../../composables/useHouseholds'

const {
    households,
    selectedHouseholdUuid,
    error,
    fetchHouseholds,
    refreshHouseholds,
} = useHouseholds()

onMounted(() => {
    fetchHouseholds()
})
</script>

<template>
    <button
        v-if="error"
        type="button"
        class="rounded-md px-2 py-1.5 text-xs text-red-600 hover:bg-red-50"
        @click="refreshHouseholds"
    >
        Не удалось загрузить дома. Повторить
    </button>

    <span v-else-if="households.length === 0" class="text-xs text-slate-400">
        Нет домов
    </span>

    <select
        v-else
        v-model="selectedHouseholdUuid"
        aria-label="Текущий дом"
        class="max-w-36 rounded-md border border-slate-200 bg-white px-2 py-1.5 text-sm text-slate-700 outline-none hover:border-slate-300 focus:border-slate-400 sm:max-w-52"
    >
        <option
            v-for="household in households"
            :key="household.uuid"
            :value="household.uuid"
        >
            {{ household.name }}
        </option>
    </select>
</template>
