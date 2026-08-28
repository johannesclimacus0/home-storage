import type { MeasurementType } from '../types/inventory'

export function formatDate(value: string | null): string {
    if (value === null) {
        return '—'
    }

    return new Intl.DateTimeFormat('ru-RU', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value))
}

export function formatQuantity(value: string, type: MeasurementType): string {
    const quantity = Number(value)

    if (type === 'mass') {
        return quantity >= 1000
            ? `${formatNumber(quantity / 1000)} кг`
            : `${formatNumber(quantity)} г`
    }

    if (type === 'volume') {
        return quantity >= 1000
            ? `${formatNumber(quantity / 1000)} л`
            : `${formatNumber(quantity)} мл`
    }

    return `${formatNumber(quantity)} шт.`
}

export function baseUnit(type: MeasurementType): string {
    return type === 'mass' ? 'г' : type === 'volume' ? 'мл' : 'шт.'
}

export function measurementTypeLabel(type: MeasurementType): string {
    return type === 'mass' ? 'Масса' : type === 'volume' ? 'Объём' : 'Количество'
}

export function measurementUnitLabel(unit: string): string {
    const labels: Record<string, string> = {
        g: 'г',
        kg: 'кг',
        ml: 'мл',
        l: 'л',
        piece: 'шт.',
    }

    return labels[unit] ?? unit
}

export function movementTypeLabel(type: string): string {
    const labels: Record<string, string> = {
        purchase: 'Покупка',
        consumption: 'Расход',
        adjustment: 'Корректировка',
    }

    return labels[type] ?? type
}

function formatNumber(value: number): string {
    return new Intl.NumberFormat('ru-RU', {
        maximumFractionDigits: 3,
    }).format(value)
}
