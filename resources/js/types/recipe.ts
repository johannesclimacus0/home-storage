import type { CatalogProduct, MeasurementUnit } from './inventory'

export interface RecipeCreator {
    id: number
    name: string
}

export interface RecipeSummary {
    uuid: string
    title: string
    description: string | null
    servings: number
    before_cooking_minutes: number
    cooking_minutes: number
    creator: RecipeCreator | null
    ingredients_count: number
    steps_count: number
}

export interface RecipeIngredient {
    uuid: string
    product: CatalogProduct
    quantity: string
    position: number
    is_optional: boolean
    note: string | null
}

export interface RecipeStep {
    uuid: string
    position: number
    description: string
}

export interface Recipe extends Omit<RecipeSummary, 'ingredients_count' | 'steps_count'> {
    ingredients: RecipeIngredient[]
    steps: RecipeStep[]
    created_at: string
    updated_at: string
}

export interface RecipePayload {
    title: string
    description: string | null
    servings: number
    before_cooking_minutes: number
    cooking_minutes: number
}

export interface RecipeIngredientPayload {
    product_uuid: string
    quantity: string
    unit: MeasurementUnit
    is_optional: boolean
    note: string | null
}
