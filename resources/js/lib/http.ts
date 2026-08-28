import axios from 'axios'
import type { AxiosError, InternalAxiosRequestConfig } from 'axios'

interface RetryableRequestConfig extends InternalAxiosRequestConfig {
    _csrfRetried?: boolean
}

const http = axios.create({
    baseURL: '/',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
    },
})

http.interceptors.response.use(
    response => response,
    async (error: AxiosError) => {
        const config = error.config as RetryableRequestConfig | undefined

        if (error.response?.status === 419 && config && !config._csrfRetried) {
            config._csrfRetried = true

            await axios.get('/sanctum/csrf-cookie', {
                withCredentials: true,
                withXSRFToken: true,
            })

            return http.request(config)
        }

        if (
            error.response?.status === 401 &&
            config?.url !== '/api/user' &&
            window.location.pathname !== '/login'
        ) {
            window.location.assign('/login')
        }

        return Promise.reject(error)
    }
)

export default http
