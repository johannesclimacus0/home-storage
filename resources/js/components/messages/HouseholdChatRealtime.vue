<script setup lang="ts">
import { useEcho } from '@laravel/echo-vue'
import type {
    BroadcastHouseholdMessage,
    HouseholdMessageSentBroadcast
} from '../../types/message'

const props = defineProps<{
    householdUuid: string
}>()

const emit = defineEmits<{
    received: [message: BroadcastHouseholdMessage]
}>()

useEcho<HouseholdMessageSentBroadcast>(
    `households.${props.householdUuid}`,
    '.household.message.sent',
    payload => emit('received', payload.message)
)
</script>
