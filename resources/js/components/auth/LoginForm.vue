<script setup>
import { reactive, ref } from 'vue';
import { useAuth } from '../../composables/useAuth.js'
import { useRouter } from 'vue-router';

const router = useRouter();
const { login } = useAuth();

const form = reactive({
    email: '',
    password: ''
});

const fieldErrors = ref({});
const generalError = ref(null);
const submitting = ref(false);

async function submit() {
    submitting.value = true;
    fieldErrors.value = {};
    generalError.value = null;

    try{
        await login({email: form.email, password: form.password});
        form.password = '';
        await router.push({
            name: 'dashboard',
        });
    } catch(requestError) {
        if(requestError.response?.status === 422){
            fieldErrors.value = requestError.response.data.errors ?? {};
        }
        else{
            generalError.value = 'Не удалось войти';
        }
    } finally {
        submitting.value = false
    }
}
</script>

<template>
    <form @submit.prevent="submit">
        <div>
            <label for="email">Email</label>

            <input
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="email"
            >

            <p v-if="fieldErrors.email">
                {{ fieldErrors.email[0] }}
            </p>
        </div>

        <div>
            <label for="password">Пароль</label>

            <input
                id="password"
                v-model="form.password"
                type="password"
                autocomplete="current-password"
            >

            <p v-if="fieldErrors.password">
                {{ fieldErrors.password[0] }}
            </p>
        </div>

        <p v-if="generalError">
            {{ generalError }}
        </p>

        <button type="submit" :disabled="submitting">
            {{ submitting ? 'Вход...' : 'Войти' }}
        </button>
    </form>
</template>

<style scoped>

</style>
