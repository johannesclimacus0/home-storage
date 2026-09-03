import type { CatalogProduct, MeasurementUnit } from './inventory'

export interface RecipeCreator {
    id: number
    name: string
}

export interface RecipeSummary {
    uuid: string
    title: string
    description: string | null
    image_url: string | null
    servings: number
    before_cooking_minutes: number
    cooking_minutes: number
    creator: RecipeCreator | null
    ingredients_count: number
    steps_count: number
}

export type RecipeAvailabilityFilter = 'all' | 'available' | 'missing'

export interface RecipeAvailabilitySummary {
    can_make: boolean
    missing_required_count: number
}

export interface HouseholdRecipeSummary extends RecipeSummary {
    availability: RecipeAvailabilitySummary
}

export interface RecipeIngredientAvailability {
    ingredient_uuid: string
    product: CatalogProduct
    required_quantity: string
    available_quantity: string
    missing_quantity: string
    is_optional: boolean
    sufficient: boolean
}

export interface RecipeAvailability extends RecipeAvailabilitySummary {
    recipe_uuid: string
    ingredients: RecipeIngredientAvailability[]
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

export interface RecipeIngredientPayload {
    product_uuid: string
    quantity: string
    unit: MeasurementUnit
    is_optional: boolean
    note: string | null
}
