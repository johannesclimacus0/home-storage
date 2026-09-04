export interface TelegramConnectionStatus {
    connected: boolean
    linked_at: string | null
    chat_name: string | null
    timezone: string
    timezones: string[]
}

export interface TelegramLinkResponse {
    link: string
    expires_in: number
}

export interface TelegramSubscription {
    key: string
    label: string
    enabled: boolean
}

export interface TelegramSubscriptionsResponse {
    data: TelegramSubscription[]
}

export interface TelegramReminder {
    uuid: string
    message: string
    remind_at: string
    frequency: TelegramReminderFrequency | null
    dispatched_at: string | null
}

export type TelegramReminderFrequency = 'hourly' | 'daily' | 'weekly' | 'monthly'

export interface TelegramRemindersResponse {
    data: TelegramReminder[]
}
