export interface UserNotification {
    uuid: string
    type: string
    data: Record<string, unknown>
    read_at: string | null
    created_at: string
}
