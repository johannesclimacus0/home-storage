export type MeasurementType = 'mass' | 'volume' | 'count'
export type MeasurementUnit = 'g' | 'kg' | 'ml' | 'l' | 'piece'
export type StockMovementType = 'purchase' | 'consumption' | 'adjustment'

export interface CatalogProduct {
    uuid: string
    name: string
    measurement_type: MeasurementType
}

export interface HouseholdProduct extends CatalogProduct {
    low_stock_threshold: string
    total_quantity: string
    is_low_stock: boolean
    low_stock_since: string | null
}

export interface StorageLocation {
    uuid: string
    household_uuid: string
    name: string
}

export interface StockMovement {
    uuid: string
    type: StockMovementType
    product: { uuid: string | null; name: string }
    storage_location: { uuid: string | null; name: string }
    actor: { id: number | null; name: string }
    input: { quantity: string; unit: MeasurementUnit }
    quantity_delta: string
    quantity_before: string
    quantity_after: string
    created_at: string
}
