export interface MessageSender {
    id: number
    name: string
}

export default interface HouseholdMessage {
    uuid: string
    content: string | null
    sender: MessageSender
    is_mine: boolean
    edited_at: string | null
    deleted_at: string | null
    created_at: string
}

export type BroadcastHouseholdMessage = Omit<HouseholdMessage, 'is_mine'>

export interface HouseholdMessageSentBroadcast {
    message: BroadcastHouseholdMessage
}

export interface CursorMessageResponse {
    data: HouseholdMessage[]
    links: {
        first?: string | null
        last?: string | null
        prev: string | null
        next: string | null
    }
}
