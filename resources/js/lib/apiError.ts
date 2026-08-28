import axios from 'axios'
import type { ValidationErrors } from '../types/api'

export function validationErrors(error: unknown): ValidationErrors {
    if (!axios.isAxiosError(error) || error.response?.status !== 422) {
        return {}
    }

    return error.response.data?.errors ?? {}
}

export function errorMessage(error: unknown, fallback = 'Что-то пошло не так.'): string {
    if (!axios.isAxiosError(error)) {
        return fallback
    }

    return error.response?.data?.message ?? fallback
}
