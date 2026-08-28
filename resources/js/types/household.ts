export default interface Household {
    uuid: string
    name: string
    role: 'owner' | 'member'
    low_stock_reminders_enabled: boolean
    low_stock_reminder_interval_hours: number
}

export interface HouseholdMember {
    user_id: number
    name: string
    email: string
    role: 'owner' | 'member'
    joined_at: string
}

export interface HouseholdDetails {
    uuid: string
    name: string
    role: 'owner' | 'member'
    members: HouseholdMember[]
}
