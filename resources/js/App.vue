<script setup>
import { onMounted } from 'vue';
import { useAuth } from './composables/useAuth.js';

const {
    loading,
    authError,
    fetchUser,
} = useAuth();

onMounted(() => {
    fetchUser();
});
</script>

<template>
    <p v-if="loading">
        Загрузка...
    </p>

    <p v-else-if="authError">
        Не удалось загрузить приложение.
    </p>

    <RouterView v-else />
</template>
