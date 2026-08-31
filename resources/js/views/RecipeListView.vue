<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import PaginationNav from '../components/ui/PaginationNav.vue'
import SearchInput from '../components/ui/SearchInput.vue'
import http from '../lib/http'
import { errorMessage } from '../lib/apiError'
import type { PaginatedResponse, PaginationMeta } from '../types/api'
import type { RecipeSummary } from '../types/recipe'

const recipes = ref<RecipeSummary[]>([])
const meta = ref<PaginationMeta | null>(null)
const pageError = ref<string | null>(null)
const search = ref('')

const visibleRecipes = computed(() => {
    const query = search.value.trim().toLocaleLowerCase()

    if (query === '') return recipes.value

    return recipes.value.filter(recipe =>
        recipe.title.toLocaleLowerCase().includes(query)
        || recipe.description?.toLocaleLowerCase().includes(query)
    )
})

onMounted(() => loadRecipes(1))

async function loadRecipes(page: number): Promise<void> {
    pageError.value = null

    try {
        const response = await http.get<PaginatedResponse<RecipeSummary>>('/api/recipes', {
            params: { page, per_page: 12 },
        })

        recipes.value = response.data.data
        meta.value = response.data.meta
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось загрузить рецепты.')
    }
}

function totalMinutes(recipe: RecipeSummary): number {
    return recipe.before_cooking_minutes + recipe.cooking_minutes
}
</script>

<template>
    <div class="space-y-6">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-4">
            <h1 class="text-2xl font-semibold text-slate-900">Рецепты</h1>
            <RouterLink
                :to="{ name: 'recipe-create' }"
                class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
            >
                Новый рецепт
            </RouterLink>
        </header>

        <SearchInput
            v-model="search"
            placeholder="Поиск по рецептам"
        />

        <p v-if="pageError" class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ pageError }}
        </p>

        <section v-if="visibleRecipes.length === 0" class="border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
            Рецепты не найдены.
        </section>

        <section v-else class="divide-y divide-slate-200 border-y border-slate-200">
            <RouterLink
                v-for="recipe in visibleRecipes"
                :key="recipe.uuid"
                :to="{ name: 'recipe-show', params: { recipeUuid: recipe.uuid } }"
                class="group grid gap-3 bg-white px-4 py-5 hover:bg-slate-50 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"
            >
                <div class="min-w-0">
                    <h2 class="font-semibold text-slate-900 group-hover:text-slate-600">{{ recipe.title }}</h2>
                    <p class="mt-1 line-clamp-2 max-w-3xl text-sm leading-5 text-slate-600">
                        {{ recipe.description || 'Описание не добавлено.' }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500 sm:justify-end">
                    <span>{{ totalMinutes(recipe) }} мин.</span>
                    <span>{{ recipe.servings }} порц.</span>
                    <span>{{ recipe.ingredients_count }} инг.</span>
                </div>
            </RouterLink>
        </section>

        <PaginationNav :meta="meta" @change="loadRecipes" />
    </div>
</template>
