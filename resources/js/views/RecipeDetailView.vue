<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ModalDialog from '../components/ui/ModalDialog.vue'
import PaginationNav from '../components/ui/PaginationNav.vue'
import { useAuth } from '../composables/useAuth'
import { useHouseholds } from '../composables/useHouseholds'
import http from '../lib/http'
import { errorMessage, validationErrors } from '../lib/apiError'
import { formatDate, formatQuantity, measurementUnitLabel } from '../lib/format'
import type { ApiResponse, PaginatedResponse, PaginationMeta, ValidationErrors } from '../types/api'
import type { CatalogProduct, MeasurementType, MeasurementUnit } from '../types/inventory'
import type {
    Recipe,
    RecipeAvailability,
    RecipeIngredient,
    RecipeIngredientPayload,
    RecipeStep,
} from '../types/recipe'
import type RecipeNote from '../types/note'

const route = useRoute()
const router = useRouter()
const { user } = useAuth()
const { selectedHouseholdUuid, fetchHouseholds } = useHouseholds()
const recipe = ref<Recipe | null>(null)
const availability = ref<RecipeAvailability | null>(null)
const shoppingListMessage = ref<string | null>(null)
const products = ref<CatalogProduct[]>([])
const pageError = ref<string | null>(null)
const formError = ref<string | null>(null)
const fieldErrors = ref<ValidationErrors>({})
const ingredientModalOpen = ref(false)
const editingIngredient = ref<RecipeIngredient | null>(null)
const stepModalOpen = ref(false)
const editingStep = ref<RecipeStep | null>(null)
const notes = ref<RecipeNote[]>([])
const notesMeta = ref<PaginationMeta | null>(null)
const noteModalOpen = ref(false)
const editingNote = ref<RecipeNote | null>(null)

const ingredientForm = reactive({
    productUuid: '',
    quantity: '1',
    unit: 'piece' as MeasurementUnit,
    isOptional: false,
    note: '',
})
const stepForm = reactive({ description: '' })
const noteForm = reactive({ content: '' })

const recipeUuid = computed(() => route.params.recipeUuid as string)
const canEdit = computed(() => recipe.value?.creator?.id === user.value?.id)
const totalMinutes = computed(() => {
    if (recipe.value === null) return 0
    return recipe.value.before_cooking_minutes + recipe.value.cooking_minutes
})
const availableProducts = computed(() => {
    const existingUuids = new Set(
        recipe.value?.ingredients
            .filter(ingredient => ingredient.uuid !== editingIngredient.value?.uuid)
            .map(ingredient => ingredient.product.uuid) ?? []
    )

    return products.value.filter(product => !existingUuids.has(product.uuid))
})
const selectedProduct = computed(() =>
    products.value.find(product => product.uuid === ingredientForm.productUuid) ?? null
)
const ingredientUnits = computed(() => unitsFor(selectedProduct.value?.measurement_type ?? 'count'))

onMounted(async () => {
    await fetchHouseholds()
    await Promise.all([loadRecipe(), loadProducts(), loadNotes(), loadAvailability()])
})

watch(selectedHouseholdUuid, () => loadAvailability())

async function loadAvailability(): Promise<void> {
    shoppingListMessage.value = null

    if (selectedHouseholdUuid.value === null) {
        availability.value = null
        return
    }

    try {
        const response = await http.get<ApiResponse<RecipeAvailability>>(
            `/api/households/${selectedHouseholdUuid.value}/recipes/${recipeUuid.value}/availability`
        )
        availability.value = response.data.data
    } catch (requestError: unknown) {
        availability.value = null
        pageError.value = errorMessage(requestError, 'Не удалось проверить наличие продуктов.')
    }
}

async function addMissingToShoppingList(): Promise<void> {
    if (selectedHouseholdUuid.value === null || availability.value?.can_make) return

    try {
        const response = await http.post<{ data: unknown[] }>(
            `/api/households/${selectedHouseholdUuid.value}/recipes/${recipeUuid.value}/shopping-list-items`
        )
        const count = response.data.data.length
        shoppingListMessage.value = count === 0
            ? 'Все необходимые продукты уже есть в списке.'
            : `Добавлено в список покупок: ${count}.`
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось добавить продукты в список покупок.')
    }
}

async function loadNotes(page = 1): Promise<void> {
    try {
        const response = await http.get<PaginatedResponse<RecipeNote>>(
            `/api/recipes/${recipeUuid.value}/notes`,
            { params: { page, per_page: 5 } }
        )
        notes.value = response.data.data
        notesMeta.value = response.data.meta
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось загрузить заметки.')
    }
}

function openNoteCreate(): void {
    clearFormErrors()
    editingNote.value = null
    noteForm.content = ''
    noteModalOpen.value = true
}

function openNoteEdit(note: RecipeNote): void {
    clearFormErrors()
    editingNote.value = note
    noteForm.content = note.content
    noteModalOpen.value = true
}

function closeNoteModal(): void {
    noteModalOpen.value = false
    editingNote.value = null
}

async function saveNote(): Promise<void> {
    clearFormErrors()

    try {
        if (editingNote.value === null) {
            await http.post(`/api/recipes/${recipeUuid.value}/notes`, {
                content: noteForm.content,
            })
        } else {
            await http.patch(`/api/recipes/${recipeUuid.value}/notes/${editingNote.value.uuid}`, {
                content: noteForm.content,
            })
        }

        const page = editingNote.value === null ? 1 : notesMeta.value?.current_page ?? 1
        closeNoteModal()
        await loadNotes(page)
    } catch (requestError: unknown) {
        fieldErrors.value = validationErrors(requestError)
        formError.value = errorMessage(requestError, 'Не удалось сохранить заметку.')
    }
}

async function deleteNote(note: RecipeNote): Promise<void> {
    if (!window.confirm('Удалить заметку?')) return

    try {
        await http.delete(`/api/recipes/${recipeUuid.value}/notes/${note.uuid}`)
        const currentPage = notesMeta.value?.current_page ?? 1
        const nextPage = notes.value.length === 1 && currentPage > 1 ? currentPage - 1 : currentPage
        await loadNotes(nextPage)
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось удалить заметку.')
    }
}

async function loadRecipe(): Promise<void> {
    pageError.value = null

    try {
        const response = await http.get<ApiResponse<Recipe>>(`/api/recipes/${recipeUuid.value}`)
        recipe.value = response.data.data
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось загрузить рецепт.')
    }
}

async function loadProducts(): Promise<void> {
    try {
        const response = await http.get<ApiResponse<CatalogProduct[]>>('/api/products')
        products.value = response.data.data
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось загрузить каталог продуктов.')
    }
}

function openIngredientCreate(): void {
    clearFormErrors()
    editingIngredient.value = null
    ingredientForm.productUuid = availableProducts.value[0]?.uuid ?? ''
    ingredientForm.quantity = '1'
    ingredientForm.unit = preferredUnit(selectedProduct.value?.measurement_type ?? 'count')
    ingredientForm.isOptional = false
    ingredientForm.note = ''
    ingredientModalOpen.value = true
}

function openIngredientEdit(ingredient: RecipeIngredient): void {
    clearFormErrors()
    editingIngredient.value = ingredient
    ingredientForm.productUuid = ingredient.product.uuid
    ingredientForm.quantity = ingredient.quantity
    ingredientForm.unit = baseMeasurementUnit(ingredient.product.measurement_type)
    ingredientForm.isOptional = ingredient.is_optional
    ingredientForm.note = ingredient.note ?? ''
    ingredientModalOpen.value = true
}

function closeIngredientModal(): void {
    ingredientModalOpen.value = false
    editingIngredient.value = null
}

function syncIngredientUnit(): void {
    ingredientForm.unit = preferredUnit(selectedProduct.value?.measurement_type ?? 'count')
}

async function saveIngredient(): Promise<void> {
    if (ingredientForm.productUuid === '') return
    clearFormErrors()

    const payload: RecipeIngredientPayload = {
        product_uuid: ingredientForm.productUuid,
        quantity: ingredientForm.quantity,
        unit: ingredientForm.unit,
        is_optional: ingredientForm.isOptional,
        note: ingredientForm.note.trim() || null,
    }

    try {
        if (editingIngredient.value === null) {
            await http.post(`/api/recipes/${recipeUuid.value}/ingredients`, payload)
        } else {
            await http.put(
                `/api/recipes/${recipeUuid.value}/ingredients/${editingIngredient.value.uuid}`,
                payload
            )
        }

        closeIngredientModal()
        await loadRecipe()
    } catch (requestError: unknown) {
        fieldErrors.value = validationErrors(requestError)
        formError.value = errorMessage(requestError, 'Не удалось сохранить ингредиент.')
    }
}

async function deleteIngredient(ingredient: RecipeIngredient): Promise<void> {
    if (!window.confirm(`Удалить ингредиент «${ingredient.product.name}»?`)) return

    try {
        await http.delete(`/api/recipes/${recipeUuid.value}/ingredients/${ingredient.uuid}`)
        await loadRecipe()
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось удалить ингредиент.')
    }
}

function openStepCreate(): void {
    clearFormErrors()
    editingStep.value = null
    stepForm.description = ''
    stepModalOpen.value = true
}

function openStepEdit(step: RecipeStep): void {
    clearFormErrors()
    editingStep.value = step
    stepForm.description = step.description
    stepModalOpen.value = true
}

function closeStepModal(): void {
    stepModalOpen.value = false
    editingStep.value = null
}

async function saveStep(): Promise<void> {
    clearFormErrors()

    try {
        if (editingStep.value === null) {
            await http.post(`/api/recipes/${recipeUuid.value}/steps`, {
                description: stepForm.description,
            })
        } else {
            await http.put(`/api/recipes/${recipeUuid.value}/steps/${editingStep.value.uuid}`, {
                description: stepForm.description,
            })
        }

        closeStepModal()
        await loadRecipe()
    } catch (requestError: unknown) {
        fieldErrors.value = validationErrors(requestError)
        formError.value = errorMessage(requestError, 'Не удалось сохранить шаг.')
    }
}

async function deleteStep(step: RecipeStep): Promise<void> {
    if (!window.confirm(`Удалить шаг ${step.position}?`)) return

    try {
        await http.delete(`/api/recipes/${recipeUuid.value}/steps/${step.uuid}`)
        await loadRecipe()
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось удалить шаг.')
    }
}

async function deleteRecipe(): Promise<void> {
    if (recipe.value === null || !window.confirm(`Удалить рецепт «${recipe.value.title}»?`)) return

    try {
        await http.delete(`/api/recipes/${recipe.value.uuid}`)
        await router.push({ name: 'recipes' })
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось удалить рецепт.')
    }
}

function clearFormErrors(): void {
    formError.value = null
    fieldErrors.value = {}
}

function unitsFor(type: MeasurementType): Array<{ value: MeasurementUnit; label: string }> {
    if (type === 'mass') return [{ value: 'g', label: 'г' }, { value: 'kg', label: 'кг' }]
    if (type === 'volume') return [{ value: 'ml', label: 'мл' }, { value: 'l', label: 'л' }]
    return [{ value: 'piece', label: 'шт.' }]
}

function preferredUnit(type: MeasurementType): MeasurementUnit {
    return type === 'mass' ? 'g' : type === 'volume' ? 'ml' : 'piece'
}

function baseMeasurementUnit(type: MeasurementType): MeasurementUnit {
    return preferredUnit(type)
}
</script>

<template>
    <div class="space-y-6">
        <p v-if="pageError" class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ pageError }}
        </p>

        <template v-if="recipe">
            <img
                v-if="recipe.image_url"
                :src="recipe.image_url"
                :alt="recipe.title"
                class="max-h-[28rem] w-full rounded object-cover"
            >

            <header class="border-b border-slate-200 pb-5">
                <RouterLink :to="{ name: 'recipes' }" class="mb-3 inline-block text-sm text-slate-600 hover:text-slate-900">
                    ← Все рецепты
                </RouterLink>

                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ recipe.title }}</h1>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ recipe.creator ? `Автор: ${recipe.creator.name}` : 'Системный рецепт' }}
                    </p>
                    <p class="mt-3 max-w-3xl whitespace-pre-line text-sm leading-6 text-slate-600">
                        {{ recipe.description || 'Описание не добавлено.' }}
                    </p>
                </div>

                <div v-if="canEdit" class="flex shrink-0 gap-2">
                    <RouterLink
                        :to="{ name: 'recipe-edit', params: { recipeUuid: recipe.uuid } }"
                        class="rounded border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                    >
                        Редактировать
                    </RouterLink>
                    <button type="button" class="px-3 py-2 text-sm text-red-600 hover:text-red-800" @click="deleteRecipe">
                        Удалить
                    </button>
                </div>
                </div>
            </header>

            <section class="flex flex-wrap gap-x-8 gap-y-3 border-b border-slate-200 pb-5 text-sm">
                <p><span class="text-slate-500">Порций:</span> <strong class="font-medium text-slate-900">{{ recipe.servings }}</strong></p>
                <p><span class="text-slate-500">Подготовка:</span> <strong class="font-medium text-slate-900">{{ recipe.before_cooking_minutes }} мин.</strong></p>
                <p><span class="text-slate-500">Приготовление:</span> <strong class="font-medium text-slate-900">{{ recipe.cooking_minutes }} мин.</strong></p>
                <p><span class="text-slate-500">Всего:</span> <strong class="font-medium text-slate-900">{{ totalMinutes }} мин.</strong></p>
            </section>

            <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_300px]">
                <section>
                    <header class="mb-3 flex items-center justify-between border-b border-slate-300 pb-2">
                        <h2 class="text-lg font-semibold text-slate-900">Приготовление</h2>
                        <button v-if="canEdit" type="button" class="text-sm text-slate-600 hover:text-slate-900" @click="openStepCreate">
                            Добавить шаг
                        </button>
                    </header>

                    <p v-if="recipe.steps.length === 0" class="py-3 text-sm text-slate-500">Шаги ещё не добавлены.</p>
                    <ol v-else class="divide-y divide-slate-200">
                        <li v-for="step in recipe.steps" :key="step.uuid" class="flex gap-3 py-4">
                            <span class="shrink-0 pt-0.5 text-sm font-medium text-slate-500">
                                {{ step.position }}.
                            </span>
                            <p class="min-w-0 flex-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{ step.description }}</p>
                            <div v-if="canEdit" class="flex shrink-0 self-start text-xs">
                                <button class="px-2 py-1 text-slate-600 hover:text-slate-900" @click="openStepEdit(step)">Изменить</button>
                                <button class="px-2 py-1 text-red-600 hover:text-red-800" @click="deleteStep(step)">Удалить</button>
                            </div>
                        </li>
                    </ol>
                </section>

                <aside class="space-y-4">
                <section class="h-fit border border-slate-200 bg-slate-50">
                    <header class="flex items-center justify-between border-b border-slate-200 bg-slate-100 px-4 py-3">
                        <h2 class="font-semibold text-slate-900">Ингредиенты</h2>
                        <button v-if="canEdit" type="button" class="text-sm text-slate-600 hover:text-slate-900" @click="openIngredientCreate">
                            Добавить
                        </button>
                    </header>

                    <p v-if="recipe.ingredients.length === 0" class="p-5 text-sm text-slate-500">Ингредиенты ещё не добавлены.</p>
                    <ul v-else class="divide-y divide-slate-200">
                        <li v-for="ingredient in recipe.ingredients" :key="ingredient.uuid" class="px-4 py-3">
                            <div class="flex items-start gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-slate-800">
                                        {{ ingredient.product.name }}
                                        <span v-if="ingredient.is_optional" class="font-normal text-slate-400">(необязательно)</span>
                                    </p>
                                    <p v-if="ingredient.note" class="mt-0.5 text-xs text-slate-500">{{ ingredient.note }}</p>
                                </div>
                                <span class="shrink-0 text-sm text-slate-600">
                                    {{ formatQuantity(ingredient.quantity, ingredient.product.measurement_type) }}
                                </span>
                            </div>
                            <div v-if="canEdit" class="mt-2 flex justify-end text-xs">
                                <button class="px-2 py-1 text-slate-600 hover:text-slate-900" @click="openIngredientEdit(ingredient)">Изменить</button>
                                <button class="px-2 py-1 text-red-600 hover:text-red-800" @click="deleteIngredient(ingredient)">Удалить</button>
                            </div>
                        </li>
                    </ul>
                </section>

                <section v-if="availability" class="border border-slate-200 bg-white">
                    <header class="border-b border-slate-200 px-4 py-3">
                        <h2 class="font-semibold text-slate-900">Продукты дома</h2>
                        <p
                            class="mt-1 text-sm"
                            :class="availability.can_make ? 'text-emerald-700' : 'text-amber-700'"
                        >
                            {{ availability.can_make
                                ? 'Всё необходимое есть'
                                : `Не хватает продуктов: ${availability.missing_required_count}` }}
                        </p>
                    </header>

                    <ul class="divide-y divide-slate-100">
                        <li
                            v-for="ingredient in availability.ingredients"
                            :key="ingredient.ingredient_uuid"
                            class="px-4 py-3 text-sm"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-slate-700">
                                    {{ ingredient.product.name }}
                                    <span v-if="ingredient.is_optional" class="text-slate-400">(необязательно)</span>
                                </span>
                                <span :class="ingredient.sufficient ? 'text-emerald-700' : 'text-amber-700'">
                                    {{ ingredient.sufficient ? 'есть' : `−${formatQuantity(
                                        ingredient.missing_quantity,
                                        ingredient.product.measurement_type
                                    )}` }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-slate-400">
                                Есть {{ formatQuantity(ingredient.available_quantity, ingredient.product.measurement_type) }}
                                из {{ formatQuantity(ingredient.required_quantity, ingredient.product.measurement_type) }}
                            </p>
                        </li>
                    </ul>

                    <div v-if="!availability.can_make" class="border-t border-slate-200 p-3">
                        <button
                            type="button"
                            class="w-full rounded border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100"
                            @click="addMissingToShoppingList"
                        >
                            Добавить недостающее в покупки
                        </button>
                        <p v-if="shoppingListMessage" class="mt-2 text-xs text-emerald-700">
                            {{ shoppingListMessage }}
                        </p>
                    </div>
                </section>
                </aside>
            </div>

            <section class="border-t border-slate-200 pt-5">
                <header class="mb-3 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Мои заметки</h2>
                    <button type="button" class="text-sm text-slate-600 hover:text-slate-900" @click="openNoteCreate">
                        Добавить
                    </button>
                </header>

                <p v-if="notes.length === 0" class="py-3 text-sm text-slate-500">Заметок пока нет.</p>
                <ul v-else class="divide-y divide-slate-200 border-y border-slate-200">
                    <li v-for="note in notes" :key="note.uuid" class="py-4">
                        <p class="whitespace-pre-wrap break-words text-sm leading-6 text-slate-700">{{ note.content }}</p>
                        <div class="mt-2 flex items-center justify-between gap-3 text-xs">
                            <span class="text-slate-400">{{ formatDate(note.updated_at) }}</span>
                            <div class="flex">
                                <button class="px-2 py-1 text-slate-600 hover:text-slate-900" @click="openNoteEdit(note)">Изменить</button>
                                <button class="px-2 py-1 text-red-600 hover:text-red-800" @click="deleteNote(note)">Удалить</button>
                            </div>
                        </div>
                    </li>
                </ul>
                <PaginationNav class="mt-3" :meta="notesMeta" @change="loadNotes" />
            </section>

        </template>

        <ModalDialog
            :open="ingredientModalOpen"
            :title="editingIngredient ? 'Изменить ингредиент' : 'Новый ингредиент'"
            @close="closeIngredientModal"
        >
            <form class="space-y-4" @submit.prevent="saveIngredient">
                <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>

                <label class="block text-sm">
                    <span class="mb-1 block text-slate-600">Продукт</span>
                    <select v-model="ingredientForm.productUuid" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2" @change="syncIngredientUnit">
                        <option v-for="product in availableProducts" :key="product.uuid" :value="product.uuid">
                            {{ product.name }}
                        </option>
                    </select>
                    <span v-if="fieldErrors.product_uuid" class="mt-1 block text-xs text-red-600">{{ fieldErrors.product_uuid[0] }}</span>
                </label>

                <div class="grid grid-cols-[1fr_120px] gap-3">
                    <label class="block text-sm">
                        <span class="mb-1 block text-slate-600">Количество</span>
                        <input v-model="ingredientForm.quantity" inputmode="decimal" class="w-full rounded-md border border-slate-300 px-3 py-2">
                        <span v-if="fieldErrors.quantity" class="mt-1 block text-xs text-red-600">{{ fieldErrors.quantity[0] }}</span>
                    </label>
                    <label class="block text-sm">
                        <span class="mb-1 block text-slate-600">Единица</span>
                        <select v-model="ingredientForm.unit" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2">
                            <option v-for="unit in ingredientUnits" :key="unit.value" :value="unit.value">
                                {{ measurementUnitLabel(unit.value) }}
                            </option>
                        </select>
                    </label>
                </div>

                <label class="block text-sm">
                    <span class="mb-1 block text-slate-600">Примечание</span>
                    <input v-model="ingredientForm.note" class="w-full rounded-md border border-slate-300 px-3 py-2">
                    <span v-if="fieldErrors.note" class="mt-1 block text-xs text-red-600">{{ fieldErrors.note[0] }}</span>
                </label>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input v-model="ingredientForm.isOptional" type="checkbox" class="accent-slate-900">
                    Необязательный ингредиент
                </label>

                <p v-if="availableProducts.length === 0" class="text-sm text-slate-500">Все продукты каталога уже добавлены.</p>

                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-md px-3 py-2 text-slate-600 hover:bg-slate-100" @click="closeIngredientModal">Отмена</button>
                    <button :disabled="ingredientForm.productUuid === ''" class="rounded-md bg-slate-900 px-3 py-2 text-white disabled:opacity-40">Сохранить</button>
                </div>
            </form>
        </ModalDialog>

        <ModalDialog
            :open="stepModalOpen"
            :title="editingStep ? 'Изменить шаг' : 'Новый шаг'"
            @close="closeStepModal"
        >
            <form class="space-y-4" @submit.prevent="saveStep">
                <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>
                <label class="block text-sm">
                    <span class="mb-1 block text-slate-600">Описание шага</span>
                    <textarea v-model="stepForm.description" rows="6" class="w-full resize-y rounded-md border border-slate-300 px-3 py-2"></textarea>
                    <span v-if="fieldErrors.description" class="mt-1 block text-xs text-red-600">{{ fieldErrors.description[0] }}</span>
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-md px-3 py-2 text-slate-600 hover:bg-slate-100" @click="closeStepModal">Отмена</button>
                    <button class="rounded-md bg-slate-900 px-3 py-2 text-white">Сохранить</button>
                </div>
            </form>
        </ModalDialog>

        <ModalDialog
            :open="noteModalOpen"
            :title="editingNote ? 'Изменить заметку' : 'Новая заметка'"
            @close="closeNoteModal"
        >
            <form class="space-y-4" @submit.prevent="saveNote">
                <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>
                <label class="block text-sm">
                    <span class="mb-1 block text-slate-600">Текст</span>
                    <textarea v-model="noteForm.content" rows="7" maxlength="10000" class="w-full resize-y rounded-md border border-slate-300 px-3 py-2"></textarea>
                    <span v-if="fieldErrors.content" class="mt-1 block text-xs text-red-600">{{ fieldErrors.content[0] }}</span>
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-md px-3 py-2 text-slate-600 hover:bg-slate-100" @click="closeNoteModal">Отмена</button>
                    <button class="rounded-md bg-slate-900 px-3 py-2 text-white">Сохранить</button>
                </div>
            </form>
        </ModalDialog>
    </div>
</template>
