<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import HouseholdSelect from '../components/households/HouseholdSelect.vue'

const router = useRouter()
const { user, logout } = useAuth()

const loggingOut = ref(false)
const logoutError = ref(null)
const userMenuOpen = ref(false)

async function handleLogout() {
    loggingOut.value = true
    logoutError.value = null

    try {
        await logout()
        userMenuOpen.value = false
        await router.push({ name: 'login' })
    } catch (error) {
        logoutError.value = error
    } finally {
        loggingOut.value = false
    }
}
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-sm text-slate-900">
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white">
            <div class="grid h-14 grid-cols-[1fr_auto_1fr] items-center px-4 sm:px-6">
                <div class="min-w-0 justify-self-start">
                    <HouseholdSelect />
                </div>

                <nav class="flex max-w-[52vw] items-center gap-1 overflow-x-auto">
                    <RouterLink
                        :to="{ name: 'products' }"
                        class="whitespace-nowrap rounded-md px-3 py-1.5 text-sm text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                        active-class="bg-slate-100 text-slate-950"
                    >
                        Запасы
                    </RouterLink>
                    <RouterLink
                        :to="{ name: 'locations' }"
                        class="whitespace-nowrap rounded-md px-3 py-1.5 text-sm text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                        active-class="bg-slate-100 text-slate-950"
                    >
                        Места хранения
                    </RouterLink>
                    <RouterLink
                        :to="{ name: 'stock-history' }"
                        class="whitespace-nowrap rounded-md px-3 py-1.5 text-sm text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                        active-class="bg-slate-100 text-slate-950"
                    >
                        История
                    </RouterLink>
                    <RouterLink
                        :to="{ name: 'catalog' }"
                        class="whitespace-nowrap rounded-md px-3 py-1.5 text-sm text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                        active-class="bg-slate-100 text-slate-950"
                    >
                        Каталог
                    </RouterLink>
                    <RouterLink
                        :to="{ name: 'shopping-list' }"
                        class="whitespace-nowrap rounded-md px-3 py-1.5 text-sm text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                        active-class="bg-slate-100 text-slate-950"
                    >
                        Покупки
                    </RouterLink>
                    <RouterLink
                        :to="{ name: 'household-chat' }"
                        class="whitespace-nowrap rounded-md px-3 py-1.5 text-sm text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                        active-class="bg-slate-100 text-slate-950"
                    >
                        Чат
                    </RouterLink>
                </nav>

                <div class="relative justify-self-end">
                    <button
                        type="button"
                        class="flex max-w-32 items-center gap-1.5 rounded-md px-2 py-1.5 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 sm:max-w-48"
                        :aria-expanded="userMenuOpen"
                        aria-haspopup="menu"
                        @click="userMenuOpen = !userMenuOpen"
                    >
                        <span class="truncate">{{ user?.name }}</span>
                    </button>

                    <div
                        v-if="userMenuOpen"
                        class="absolute right-0 top-10 z-40 w-48 rounded-lg border border-slate-200 bg-white p-1 shadow-lg"
                        role="menu"
                    >
                        <RouterLink
                            :to="{ name: 'household-settings' }"
                            role="menuitem"
                            class="block rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-950"
                            @click="userMenuOpen = false"
                        >
                            Дома
                        </RouterLink>
                        <RouterLink
                            :to="{ name: 'notifications' }"
                            role="menuitem"
                            class="block rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-950"
                            @click="userMenuOpen = false"
                        >
                            Уведомления
                        </RouterLink>
                        <div class="my-1 border-t border-slate-100"></div>
                        <button
                            type="button"
                            role="menuitem"
                            :disabled="loggingOut"
                            class="w-full rounded-md px-3 py-2 text-left text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 disabled:cursor-wait disabled:opacity-50"
                            @click="handleLogout"
                        >
                            {{ loggingOut ? 'Выходим…' : 'Выйти' }}
                        </button>

                        <p v-if="logoutError" class="px-3 py-2 text-xs text-red-600">
                            Не удалось выйти.
                        </p>
                    </div>
                </div>
            </div>
        </header>

        <button
            v-if="userMenuOpen"
            type="button"
            class="fixed inset-0 z-20 cursor-default"
            aria-label="Закрыть меню пользователя"
            @click="userMenuOpen = false"
        ></button>

        <main class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
            <RouterView />
        </main>
    </div>
</template>
