import type { CatalogProduct, MeasurementUnit } from './inventory'

export default interface ShoppingItem {
    uuid: string
    product: CatalogProduct
    quantity: string
    unit: MeasurementUnit
    completed_at: string | null
    added_by: { id: number; name: string }
    created_at: string | null
}

export interface NewShoppingItemData {
    productUuid: string
    quantity: string
    unit: MeasurementUnit
}
