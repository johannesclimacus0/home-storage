export interface ApiResponse<T> {
    data: T
}

export interface PaginationMeta {
    current_page: number
    from: number | null
    last_page: number
    per_page: number
    to: number | null
    total: number
}

export interface PaginatedResponse<T> {
    data: T[]
    meta: PaginationMeta
}

export type ValidationErrors = Record<string, string[]>
