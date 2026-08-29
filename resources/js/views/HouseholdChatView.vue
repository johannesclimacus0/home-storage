<script setup lang="ts">
import { nextTick, ref, watch } from 'vue'
import { useHouseholds } from '../composables/useHouseholds'
import { useAuth } from '../composables/useAuth'
import HouseholdChatRealtime from '../components/messages/HouseholdChatRealtime.vue'
import { errorMessage } from '../lib/apiError'
import { formatDate } from '../lib/format'
import http from '../lib/http'
import type { ApiResponse } from '../types/api'
import type HouseholdMessage from '../types/message'
import type {BroadcastHouseholdMessage, CursorMessageResponse, MessageDeletedBroadcast, MessageUpdatedBroadcast} from '../types/message'

const { selectedHouseholdUuid, activeHousehold } = useHouseholds()
const { user } = useAuth()
const messages = ref<HouseholdMessage[]>([])
const content = ref('')
const nextPageUrl = ref<string | null>(null)
const pageError = ref<string | null>(null)
const editingUuid = ref<string | null>(null)
const editingContent = ref('')
const messageList = ref<HTMLElement | null>(null)

watch(selectedHouseholdUuid, loadMessages, { immediate: true })

async function loadMessages(): Promise<void> {
    messages.value = []
    nextPageUrl.value = null
    pageError.value = null

    if (selectedHouseholdUuid.value === null) return

    const householdUuid = selectedHouseholdUuid.value

    try {
        const response = await http.get<CursorMessageResponse>(
            `/api/households/${householdUuid}/messages?per_page=30`
        )

        if (selectedHouseholdUuid.value !== householdUuid) return

        messages.value = [...response.data.data].reverse()
        nextPageUrl.value = response.data.links.next
        await nextTick()
        scrollToBottom()
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось загрузить сообщения.')
    }
}

async function loadOlderMessages(): Promise<void> {
    if (nextPageUrl.value === null) return

    const previousHeight = messageList.value?.scrollHeight ?? 0

    try {
        const response = await http.get<CursorMessageResponse>(nextPageUrl.value)
        messages.value = [
            ...response.data.data.reverse(),
            ...messages.value
        ]
        nextPageUrl.value = response.data.links.next
        await nextTick()

        if (messageList.value !== null) {
            messageList.value.scrollTop += messageList.value.scrollHeight - previousHeight
        }
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось загрузить предыдущие сообщения.')
    }
}

async function sendMessage(): Promise<void> {
    const householdUuid = selectedHouseholdUuid.value
    const preparedContent = content.value.trim()

    if (householdUuid === null || preparedContent === '') return

    try {
        const response = await http.post<ApiResponse<HouseholdMessage>>(
            `/api/households/${householdUuid}/messages`,
            { content: preparedContent }
        )
        mergeMessage(response.data.data)
        content.value = ''
        await nextTick()
        scrollToBottom()
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось отправить сообщение.')
    }
}

function startEditing(message: HouseholdMessage): void {
    editingUuid.value = message.uuid
    editingContent.value = message.content ?? ''
}

function cancelEditing(): void {
    editingUuid.value = null
    editingContent.value = ''
}

async function saveMessage(messageUuid: string): Promise<void> {
    const householdUuid = selectedHouseholdUuid.value
    const preparedContent = editingContent.value.trim()

    if (householdUuid === null || preparedContent === '') return

    try {
        const response = await http.patch<ApiResponse<HouseholdMessage>>(
            `/api/households/${householdUuid}/messages/${messageUuid}`,
            { content: preparedContent }
        )
        replaceMessage(response.data.data)
        cancelEditing()
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось изменить сообщение.')
    }
}

async function deleteMessage(message: HouseholdMessage): Promise<void> {
    const householdUuid = selectedHouseholdUuid.value

    if (householdUuid === null || !window.confirm('Удалить сообщение?')) return

    try {
        await http.delete(`/api/households/${householdUuid}/messages/${message.uuid}`)
        replaceMessage({
            ...message,
            content: null,
            deleted_at: new Date().toISOString()
        })
    } catch (requestError: unknown) {
        pageError.value = errorMessage(requestError, 'Не удалось удалить сообщение.')
    }
}

function replaceMessage(updatedMessage: HouseholdMessage): void {
    messages.value = messages.value.map(message =>
        message.uuid === updatedMessage.uuid ? updatedMessage : message
    )
}

function handleReceivedMessage(
    receivedMessage: BroadcastHouseholdMessage
): void {
    const exists = messages.value.some(
        message => message.uuid === receivedMessage.uuid
    )

    if (exists) return

    messages.value = [
        ...messages.value,
        {
            ...receivedMessage,
            is_mine: receivedMessage.sender.id === user.value?.id
        }
    ]

    nextTick(scrollToBottom)
}

function handleUpdatedMessage(
    updatedMessage: MessageUpdatedBroadcast['message']
): void {
    messages.value = messages.value.map(message => {
        if (message.uuid !== updatedMessage.uuid) {
            return message
        }

        return {
            ...message,
            content: updatedMessage.content,
            edited_at: updatedMessage.edited_at
        }
    })
}

function handleDeletedMessage(
    deletedMessage: MessageDeletedBroadcast['message']
): void {
    messages.value = messages.value.map(message => {
        if (message.uuid !== deletedMessage.uuid) {
            return message
        }

        return {
            ...message,
            content: null,
            deleted_at: deletedMessage.deleted_at
        }
    })
}

function mergeMessage(message: HouseholdMessage): void {
    const existingMessage = messages.value.some(item => item.uuid === message.uuid)

    if (existingMessage) {
        replaceMessage(message)
        return
    }

    messages.value = [...messages.value, message]
}

function messageAlignmentClass(message: HouseholdMessage): string {
    return message.is_mine ? 'justify-end' : 'justify-start'
}

function messageBubbleClass(message: HouseholdMessage): string {
    return message.is_mine
        ? 'rounded-br-sm bg-slate-900 text-white'
        : 'rounded-bl-sm bg-slate-100 text-slate-900'
}

function messageMetadataClass(message: HouseholdMessage): string {
    return message.is_mine ? 'justify-end' : 'justify-start'
}

function scrollToBottom(): void {
    messageList.value?.scrollTo({
        top: messageList.value.scrollHeight,
        behavior: 'smooth'
    })
}
</script>

<template>
    <div class="mx-auto max-w-4xl">
        <HouseholdChatRealtime
            v-if="selectedHouseholdUuid !== null"
            :key="selectedHouseholdUuid"
            :household-uuid="selectedHouseholdUuid"
            @received="handleReceivedMessage"
            @updated="handleUpdatedMessage"
            @deleted="handleDeletedMessage"
        />

        <p v-if="pageError" class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ pageError }}
        </p>

        <p v-if="selectedHouseholdUuid === null" class="mt-4 rounded-md border border-slate-200 bg-white p-5 text-slate-500">
            Сначала выберите дом.
        </p>

        <section v-else class="flex h-[calc(100vh-7rem)] min-h-[32rem] flex-col overflow-hidden rounded-md border border-slate-200 bg-white">
            <header class="flex h-14 shrink-0 items-center border-b border-slate-200 px-4">
                <div class="min-w-0">
                    <h1 class="truncate text-sm font-medium text-slate-950">{{ activeHousehold?.name }}</h1>
                    <p class="text-xs text-slate-400">Общий чат дома</p>
                </div>
            </header>

            <div ref="messageList" class="flex-1 overflow-y-auto px-4 py-5 sm:px-6">
                <div v-if="nextPageUrl" class="mb-6 text-center">
                    <button type="button" class="rounded px-3 py-1.5 text-xs text-slate-500 hover:bg-slate-100 hover:text-slate-800" @click="loadOlderMessages">
                        Показать предыдущие
                    </button>
                </div>

                <div v-if="messages.length === 0" class="flex h-full items-center justify-center">
                    <p class="text-sm text-slate-400">Напишите первое сообщение</p>
                </div>

                <div v-else class="space-y-1.5">
                    <article
                        v-for="message in messages"
                        :key="message.uuid"
                        class="group flex py-0.5"
                        :class="messageAlignmentClass(message)"
                    >
                        <div class="max-w-[85%] sm:max-w-[70%]">
                            <div v-if="!message.is_mine" class="mb-1 px-1 text-xs text-slate-500">
                                {{ message.sender.name }}
                            </div>

                            <div
                                class="rounded-lg px-3 py-2"
                                :class="messageBubbleClass(message)"
                            >
                                <p v-if="message.deleted_at" class="text-sm italic opacity-50">Сообщение удалено</p>

                                <form v-else-if="editingUuid === message.uuid" class="space-y-2" @submit.prevent="saveMessage(message.uuid)">
                                    <textarea v-model="editingContent" maxlength="2000" rows="3" class="w-full rounded border border-slate-600 bg-slate-800 px-2 py-1.5 text-sm text-white outline-none placeholder:text-slate-400 focus:border-slate-400"></textarea>
                                    <div class="flex justify-end gap-3 text-xs">
                                        <button type="button" class="text-slate-300 hover:text-white" @click="cancelEditing">Отмена</button>
                                        <button type="submit" class="rounded bg-white px-2.5 py-1 font-medium text-slate-900 hover:bg-slate-100">Сохранить</button>
                                    </div>
                                </form>

                                <p v-else class="whitespace-pre-wrap break-words text-sm leading-5">{{ message.content }}</p>
                            </div>

                            <div class="mt-1 flex items-center gap-2 px-1 text-[11px] text-slate-400" :class="messageMetadataClass(message)">
                                <time :datetime="message.created_at">{{ formatDate(message.created_at) }}</time>
                                <span v-if="message.edited_at && !message.deleted_at">изменено</span>
                                <template v-if="message.is_mine && !message.deleted_at && editingUuid !== message.uuid">
                                    <button type="button" class="hover:text-slate-700" @click="startEditing(message)">Изменить</button>
                                    <button type="button" class="hover:text-red-600" @click="deleteMessage(message)">Удалить</button>
                                </template>
                            </div>
                        </div>
                    </article>
                </div>
            </div>

            <form class="flex shrink-0 items-end gap-2 border-t border-slate-200 bg-white p-3" @submit.prevent="sendMessage">
                <label class="sr-only" for="message-content">Сообщение</label>
                <textarea
                    id="message-content"
                    v-model="content"
                    maxlength="2000"
                    rows="1"
                    placeholder="Сообщение"
                    class="max-h-32 min-h-10 flex-1 resize-none rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm leading-5 outline-none focus:border-slate-500"
                    @keydown.enter.exact.prevent="sendMessage"
                ></textarea>
                <button type="submit" :disabled="content.trim() === ''" class="h-10 rounded-md bg-slate-900 px-4 text-sm text-white hover:bg-slate-800 disabled:opacity-40">
                    Отправить
                </button>
            </form>
        </section>
    </div>
</template>
