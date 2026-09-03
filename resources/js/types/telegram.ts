export interface TelegramConnectionStatus {
    connected: boolean
    linked_at: string | null
    chat_name: string | null
}

export interface TelegramLinkResponse {
    link: string
    expires_in: number
}
