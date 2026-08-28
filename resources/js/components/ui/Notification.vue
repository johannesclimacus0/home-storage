<script setup lang="ts">
import { ref } from 'vue'
import { useEchoNotification } from '@laravel/echo-vue'
import { formatQuantity } from '../../lib/format'
import type { MeasurementType } from '../../types/inventory'

interface LowStockBroadcast {
    household_uuid: string
    household_name: string
    product_uuid: string
    product_name: string
    measurement_type: MeasurementType
    quantity: string
    threshold: string
    became_low_at: string
}

interface LowStockToast extends LowStockBroadcast {
    id: string
}

const props = defineProps<{
    userId: number
}>()

const notifications = ref<LowStockToast[]>([])

useEchoNotification<LowStockBroadcast>(
    `App.Models.User.${props.userId}`,
    notification => {
        notifications.value = [
            {
                id: notification.id,
                household_uuid: notification.household_uuid,
                household_name: notification.household_name,
                product_uuid: notification.product_uuid,
                product_name: notification.product_name,
                measurement_type: notification.measurement_type,
                quantity: notification.quantity,
                threshold: notification.threshold,
                became_low_at: notification.became_low_at,
            },
            ...notifications.value.filter(item => item.id !== notification.id),
        ].slice(0, 3)
    }
)

function dismiss(notificationId: string): void {
    notifications.value = notifications.value.filter(
        notification => notification.id !== notificationId
    )
}
</script>

<template>
    <aside
        v-if="notifications.length > 0"
        aria-label="Новые уведомления"
        aria-live="polite"
        class="fixed inset-x-4 bottom-4 z-50 flex flex-col gap-2 sm:left-auto sm:w-96"
    >
        <article
            v-for="notification in notifications"
            :key="notification.id"
            class="rounded-lg border border-slate-200 bg-white p-4 shadow-lg shadow-slate-950/10"
            role="status"
        >
            <div class="flex items-start gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-950">
                        Заканчивается: {{ notification.product_name }}
                    </p>
                    <p class="mt-1 text-sm text-slate-600">
                        осталось {{ formatQuantity(notification.quantity, notification.measurement_type) }}
                    </p>
                    <RouterLink
                        :to="{ name: 'notifications' }"
                        class="mt-3 inline-block text-sm font-medium text-slate-700 underline decoration-slate-300 underline-offset-4 hover:text-slate-950"
                        @click="dismiss(notification.id)"
                    >
                        Посмотреть уведомления
                    </RouterLink>
                </div>

                <button
                    type="button"
                    class="-mr-1 -mt-1 rounded p-1 text-lg leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Закрыть уведомление"
                    @click="dismiss(notification.id)"
                >
                    ×
                </button>
            </div>
        </article>
    </aside>
</template>
