import { ref } from 'vue';
import http from '../lib/http.js'

const user = ref(null);
const loading = ref(false);
const authError = ref(null);
const initialized = ref(false)

async function fetchUser() {
    loading.value = true;
    authError.value = null;

    try {
        const response = await http.get('/api/user');

        user.value = response.data
    } catch (requestError) {
        user.value = null;

        if (requestError.response?.status !== 401) {
            authError.value = requestError;
        }
    } finally {
        loading.value = false;
        initialized.value = true
    }
}

async function login(credentials) {
    await http.get('/sanctum/csrf-cookie');
    await http.post('/login', credentials);

    await fetchUser();
}

async function logout() {
    await http.post('/logout');

    user.value = null;
}

async function initializeAuth(){
    if(initialized.value){
        return;
    }
    await fetchUser();
}

export function useAuth() {
    return {
        user,
        loading,
        authError,
        fetchUser,
        login,
        logout,
        initialized,
        initializeAuth,
    }
}
