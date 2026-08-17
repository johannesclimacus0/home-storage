<script setup>
import { useAuth } from './composables/useAuth.js';

const {
    loading,
    authError,
} = useAuth();


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
