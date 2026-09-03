<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import http from '../lib/http'
import { errorMessage, validationErrors } from '../lib/apiError'
import type { ApiResponse, ValidationErrors } from '../types/api'
import type { Recipe } from '../types/recipe'

const route = useRoute()
const router = useRouter()
const { user } = useAuth()
const recipe = ref<Recipe | null>(null)
const pageError = ref<string | null>(null)
const fieldErrors = ref<ValidationErrors>({})
const image = ref<File | null>(null)
const removeImage = ref(false)
const recipeUuid = computed(() => route.params.recipeUuid as string | undefined)
const editing = computed(() => recipeUuid.value !== undefined)
const canEdit = computed(() => !editing.value || recipe.value?.creator?.id === user.value?.id)
const form = reactive({
    title: '',
    description: '',
    servings: 1,
    beforeCookingMinutes: 0,
    cookingMinutes: 0,
})

onMounted(loadRecipe)

async function loadRecipe(): Promise<void> {
    if (!editing.value || recipeUuid.value === undefined) return

    try {
        const response = await http.get<ApiResponse<Recipe>>(`/api/recipes/${recipeUuid.value}`)
        recipe.value = response.data.data
        form.title = recipe.value.title
        form.description = recipe.value.description ?? ''
        form.servings = recipe.value.servings
        form.beforeCookingMinutes = recipe.value.before_cooking_minutes
        form.cookingMinutes = recipe.value.cooking_minutes
        image.value = null
        removeImage.value = false
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось загрузить рецепт.')
    }
}

async function submit(): Promise<void> {
    pageError.value = null
    fieldErrors.value = {}

    const payload = new FormData()
    payload.append('title', form.title)
    payload.append('description', form.description.trim())
    payload.append('servings', String(form.servings))
    payload.append('before_cooking_minutes', String(form.beforeCookingMinutes))
    payload.append('cooking_minutes', String(form.cookingMinutes))

    if (image.value !== null) {
        payload.append('image', image.value)
    }

    try {
        let response

        if (editing.value && recipeUuid.value !== undefined) {
            payload.append('_method', 'PUT')
            payload.append('remove_image', removeImage.value ? '1' : '0')
            response = await http.post<ApiResponse<Recipe>>(
                `/api/recipes/${recipeUuid.value}`,
                payload
            )
        } else {
            response = await http.post<ApiResponse<Recipe>>('/api/recipes', payload)
        }

        await router.push({
            name: 'recipe-show',
            params: { recipeUuid: response.data.data.uuid },
        })
    } catch (requestError: unknown) {
        fieldErrors.value = validationErrors(requestError)
        pageError.value = errorMessage(requestError, 'Не удалось сохранить рецепт.')
    }
}

function selectImage(event: Event): void {
    const input = event.target as HTMLInputElement
    image.value = input.files?.[0] ?? null

    if (image.value !== null) {
        removeImage.value = false
    }
}
</script>

<template>
    <div class="mx-auto max-w-3xl space-y-6">
        <header class="flex items-center justify-between gap-3 border-b border-slate-200 pb-4">
            <h1 class="text-2xl font-semibold text-slate-900">
                {{ editing ? 'Редактирование рецепта' : 'Новый рецепт' }}
            </h1>
            <RouterLink
                :to="editing && recipeUuid ? { name: 'recipe-show', params: { recipeUuid } } : { name: 'recipes' }"
                class="text-sm text-slate-600 hover:text-slate-900"
            >
                Отмена
            </RouterLink>
        </header>

        <p v-if="pageError" class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ pageError }}
        </p>

        <section v-if="editing && recipe && !canEdit" class="border border-slate-200 bg-white p-5 text-slate-600">
            Редактировать рецепт может только его автор.
        </section>

        <form v-else class="space-y-6 border border-slate-200 bg-white p-5 sm:p-6" @submit.prevent="submit">
            <label class="block text-sm">
                <span class="mb-1 block text-slate-600">Название</span>
                <input v-model="form.title" class="w-full rounded border border-slate-300 px-3 py-2 outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
                <span v-if="fieldErrors.title" class="mt-1 block text-xs text-red-600">{{ fieldErrors.title[0] }}</span>
            </label>

            <label class="block text-sm">
                <span class="mb-1 block text-slate-600">Описание</span>
                <textarea v-model="form.description" rows="5" class="w-full resize-y rounded border border-slate-300 px-3 py-2 outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500"></textarea>
                <span v-if="fieldErrors.description" class="mt-1 block text-xs text-red-600">{{ fieldErrors.description[0] }}</span>
            </label>

            <div class="space-y-3 text-sm">
                <span class="block text-slate-600">Изображение</span>

                <img
                    v-if="editing && recipe?.image_url && image === null && !removeImage"
                    :src="recipe.image_url"
                    :alt="recipe.title"
                    class="max-h-64 max-w-full rounded border border-slate-200 object-cover"
                >

                <input
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="block w-full rounded border border-slate-300 bg-white px-3 py-2 text-sm file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:text-slate-700"
                    @change="selectImage"
                >

                <p v-if="image" class="text-xs text-slate-500">Выбран файл: {{ image.name }}</p>
                <span v-if="fieldErrors.image" class="block text-xs text-red-600">{{ fieldErrors.image[0] }}</span>

                <label v-if="editing && recipe?.image_url && image === null" class="flex items-center gap-2 text-slate-600">
                    <input v-model="removeImage" type="checkbox" class="rounded border-slate-300">
                    Удалить текущее изображение
                </label>

                <p class="text-xs text-slate-400">JPG, PNG или WebP, не более 5 МБ.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <label class="block text-sm">
                    <span class="mb-1 block text-slate-600">Порций</span>
                    <input v-model.number="form.servings" type="number" min="1" max="32767" class="w-full rounded border border-slate-300 px-3 py-2 focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
                    <span v-if="fieldErrors.servings" class="mt-1 block text-xs text-red-600">{{ fieldErrors.servings[0] }}</span>
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-slate-600">Подготовка, мин.</span>
                    <input v-model.number="form.beforeCookingMinutes" type="number" min="0" max="32767" class="w-full rounded border border-slate-300 px-3 py-2 focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
                    <span v-if="fieldErrors.before_cooking_minutes" class="mt-1 block text-xs text-red-600">{{ fieldErrors.before_cooking_minutes[0] }}</span>
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-slate-600">Приготовление, мин.</span>
                    <input v-model.number="form.cookingMinutes" type="number" min="0" max="32767" class="w-full rounded border border-slate-300 px-3 py-2 focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
                    <span v-if="fieldErrors.cooking_minutes" class="mt-1 block text-xs text-red-600">{{ fieldErrors.cooking_minutes[0] }}</span>
                </label>
            </div>

            <div class="flex justify-end border-t border-slate-200 pt-5">
                <button type="submit" class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    {{ editing ? 'Сохранить' : 'Создать рецепт' }}
                </button>
            </div>
        </form>
    </div>
</template>
