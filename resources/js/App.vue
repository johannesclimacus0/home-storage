<script setup lang="ts">
import Notification from './components/ui/Notification.vue'
import { useAuth } from './composables/useAuth'

const { user } = useAuth()
</script>

<template>
    <RouterView />
    <Notification v-if="user !== null" :user-id="user.id" />
</template>
