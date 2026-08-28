import axios from 'axios'
import { ref } from 'vue'
import http from '../lib/http'
import type { AuthUser, LoginCredentials } from '../types/auth'

const user = ref<AuthUser | null>(null)
const authError = ref<unknown>(null)
const initialized = ref(false)

let initializationRequest: Promise<void> | null = null

async function fetchUser(): Promise<void> {
    authError.value = null

    try {
        const response = await http.get<AuthUser>('/api/user')
        user.value = response.data
    } catch (requestError: unknown) {
        user.value = null

        if (!axios.isAxiosError(requestError) || requestError.response?.status !== 401) {
            authError.value = requestError
        }
    } finally {
        initialized.value = true
    }
}

async function login(credentials: LoginCredentials): Promise<void> {
    await http.get('/sanctum/csrf-cookie')
    await http.post('/login', credentials)
    await fetchUser()
}

async function logout(): Promise<void> {
    await http.post('/logout')
    user.value = null
    initialized.value = true
}

function initializeAuth(): Promise<void> {
    if (initialized.value) {
        return Promise.resolve()
    }

    if (initializationRequest === null) {
        initializationRequest = fetchUser().finally(() => {
            initializationRequest = null
        })
    }

    return initializationRequest
}

export function useAuth() {
    return {
        user,
        authError,
        initialized,
        fetchUser,
        login,
        logout,
        initializeAuth,
    }
}
