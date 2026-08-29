<script setup lang="ts">
import { useEcho } from '@laravel/echo-vue'
import type {
    HouseholdMessageSentBroadcast,
    MessageDeletedBroadcast,
    MessageUpdatedBroadcast
} from '../../types/message'

const props = defineProps<{
    householdUuid: string
}>()

const emit = defineEmits<{
    received: [message: HouseholdMessageSentBroadcast['message']]
    updated: [message: MessageUpdatedBroadcast['message']]
    deleted: [message: MessageDeletedBroadcast['message']]
}>()

useEcho<HouseholdMessageSentBroadcast>(
    `households.${props.householdUuid}`,
    '.household.message.sent',
    payload => emit('received', payload.message)
)

useEcho<MessageUpdatedBroadcast>(
    `households.${props.householdUuid}`,
    '.household.message.updated',
    payload => emit('updated', payload.message)
)

useEcho<MessageDeletedBroadcast>(
    `households.${props.householdUuid}`,
    '.household.message.deleted',
    payload => emit('deleted', payload.message)
)
</script>
