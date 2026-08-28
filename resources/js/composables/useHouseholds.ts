import { computed, ref } from 'vue'
import type Household from '../types/household'
import http from '../lib/http'

const households = ref<Household[]>([])
const selectedHouseholdUuid = ref<string | null>(null)
const error = ref<unknown>(null)
const initialized = ref(false)
const activeHousehold = computed(() =>
    households.value.find(household => household.uuid === selectedHouseholdUuid.value) ?? null
)

let pendingRequest: Promise<void> | null = null

function fetchHouseholds(force = false): Promise<void> {
    if (pendingRequest !== null) {
        return pendingRequest
    }

    if (initialized.value && !force) {
        return Promise.resolve()
    }

    pendingRequest = loadHouseholds().finally(() => {
        pendingRequest = null
    })

    return pendingRequest
}

async function loadHouseholds(): Promise<void> {
    error.value = null

    try {
        const response = await http.get<{ data: Household[] }>('/api/households')
        households.value = response.data.data

        const selectedStillExists = households.value.some(
            household => household.uuid === selectedHouseholdUuid.value
        )

        if (!selectedStillExists) {
            selectedHouseholdUuid.value = households.value[0]?.uuid ?? null
        }
    } catch (requestError: unknown) {
        households.value = []
        selectedHouseholdUuid.value = null
        error.value = requestError
    } finally {
        initialized.value = true
    }
}

export function useHouseholds() {
    return {
        households,
        selectedHouseholdUuid,
        activeHousehold,
        error,
        initialized,
        fetchHouseholds,
        refreshHouseholds: () => fetchHouseholds(true),
    }
}
