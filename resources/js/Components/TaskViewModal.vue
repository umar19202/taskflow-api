<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[1050] bg-black/45 backdrop-blur-[1px]" @click.self="$emit('close')">
            <div class="flex items-center justify-center min-h-full p-4" @click.stop>
                <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col" @click.stop
                    :style="{ borderLeft: '4px solid ' + priorityColor(task.priority) }">

                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full" :style="{ background: priorityColor(task.priority) }"></span>
                            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Task</h3>
                        </div>
                        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 text-lg leading-none p-1 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>

                    <div class="overflow-y-auto">
                        <div class="px-6 pt-5 pb-2">
                            <div class="flex items-start justify-between gap-4">
                                <h2 class="text-xl font-semibold leading-snug">{{ task.title }}</h2>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="badge" :class="statusBadge(task.status.value)">
                                        {{ task.status.label }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 pb-2">
                            <p v-if="task.description" class="text-gray-600 leading-relaxed whitespace-pre-wrap">
                                {{ task.description }}
                            </p>
                            <p v-else class="text-gray-400 italic text-sm">No description provided.</p>
                        </div>

                        <div class="mx-6 my-4 p-5 bg-gray-50 rounded-xl">
                            <div class="grid grid-cols-2 gap-y-5 gap-x-8">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center shrink-0 mt-0.5">
                                        <i class="ti ti-flag text-primary text-sm"></i>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400 font-medium">Priority</span>
                                        <div class="mt-0.5">
                                            <span class="badge text-xs" :class="priorityBadge(task.priority)">
                                                {{ task.priority }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-warning/10 flex items-center justify-center shrink-0 mt-0.5">
                                        <i class="ti ti-calendar text-warning text-sm"></i>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400 font-medium">Due Date</span>
                                        <p class="mt-0.5 text-sm font-medium"
                                            :class="{ 'text-danger': task.is_overdue, 'text-gray-800': !task.is_overdue }">
                                            <template v-if="task.due_date">
                                                {{ task.due_date }}
                                                <span v-if="task.is_overdue" class="text-xs ml-1 font-semibold">(Overdue)</span>
                                            </template>
                                            <span v-else class="text-gray-400 font-normal">Not set</span>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-success/10 flex items-center justify-center shrink-0 mt-0.5">
                                        <i class="ti ti-user-check text-success text-sm"></i>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400 font-medium">Assignee</span>
                                        <div class="mt-0.5 flex items-center gap-2">
                                            <template v-if="task.assignee">
                                                <span class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-semibold shrink-0">
                                                    {{ initials(task.assignee.name) }}
                                                </span>
                                                <span class="text-sm font-medium text-gray-800">{{ task.assignee.name }}</span>
                                            </template>
                                            <span v-else class="text-sm text-gray-400">Unassigned</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-info/10 flex items-center justify-center shrink-0 mt-0.5">
                                        <i class="ti ti-user text-info text-sm"></i>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400 font-medium">Created by</span>
                                        <div class="mt-0.5 flex items-center gap-2">
                                            <template v-if="task.creator">
                                                <span class="w-6 h-6 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center text-xs font-semibold shrink-0">
                                                    {{ initials(task.creator.name) }}
                                                </span>
                                                <span class="text-sm font-medium text-gray-800">{{ task.creator.name }}</span>
                                            </template>
                                            <span v-else class="text-sm text-gray-400">—</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center shrink-0 mt-0.5">
                                        <i class="ti ti-calendar-plus text-gray-500 text-sm"></i>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400 font-medium">Created</span>
                                        <p class="mt-0.5 text-sm text-gray-600">{{ formatDate(task.created_at) }}</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center shrink-0 mt-0.5">
                                        <i class="ti ti-clock text-gray-500 text-sm"></i>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400 font-medium">Updated</span>
                                        <p class="mt-0.5 text-sm text-gray-600">{{ formatDate(task.updated_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 mx-6 pt-6 pb-6">
                            <div class="flex items-center justify-between mb-5">
                                <div class="flex items-center gap-2">
                                    <i class="ti ti-message text-gray-400 text-sm"></i>
                                    <h4 class="text-sm font-semibold text-gray-900">Comments</h4>
                                    <span v-if="totalComments > 0" class="text-xs text-gray-400">({{ totalComments }})</span>
                                </div>
                            </div>

                            <div v-if="commentsLoading && comments.length === 0" class="flex justify-center py-6">
                                <svg class="animate-spin h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                            </div>

                            <template v-else-if="comments.length === 0">
                                <div class="flex flex-col items-center justify-center py-8 text-gray-400">
                                    <i class="ti ti-message-off text-2xl mb-2 opacity-40"></i>
                                    <p class="text-sm">No comments yet.</p>
                                    <p class="text-xs text-gray-300 mt-1">Be the first to share your thoughts.</p>
                                </div>
                            </template>

                            <template v-else>
                                <div class="flex items-center justify-center gap-3 pb-4">
                                    <button v-if="hasOlder" @click="loadOlder" :disabled="loadingOlder"
                                        class="text-xs font-medium text-primary hover:text-primary/80 transition-colors inline-flex items-center gap-1"
                                    >
                                        <template v-if="loadingOlder">
                                            <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                            </svg>
                                            Loading
                                        </template>
                                        <template v-else>
                                            <i class="ti ti-chevron-up text-[11px]"></i>
                                            Show older ({{ totalComments - comments.length }})
                                        </template>
                                    </button>
                                    <button v-if="hasLoadedOlder" @click="showLess"
                                        class="text-xs font-medium text-gray-500 hover:text-gray-700 transition-colors inline-flex items-center gap-1"
                                    >
                                        <i class="ti ti-chevron-down text-[11px]"></i>
                                        Show less
                                    </button>
                                </div>

                                <div class="space-y-5">
                                    <div v-for="comment in comments" :key="comment.id" class="flex gap-3">
                                        <span class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-semibold shrink-0 mt-0.5">
                                            {{ initials(comment.author?.name) }}
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-0.5">
                                                <span class="text-sm font-medium text-gray-900">{{ comment.author?.name }}</span>
                                                <span class="text-xs text-gray-400">{{ timeAgo(comment.created_at) }}</span>
                                            </div>

                                            <div v-if="editingId !== comment.id" class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap break-words">
                                                {{ comment.body }}
                                            </div>

                                            <div v-else>
                                                <textarea v-model="editBody"
                                                    class="form-control text-sm w-full"
                                                    rows="3"
                                                    @keydown.ctrl.enter="saveEdit(comment)"
                                                ></textarea>
                                                <div class="flex items-center gap-2 mt-2">
                                                    <button @click="saveEdit(comment)" class="btn btn-primary btn-sm" :disabled="!editBody.trim()">
                                                        Save
                                                    </button>
                                                    <button @click="cancelEdit" class="btn btn-light btn-sm">
                                                        Cancel
                                                    </button>
                                                </div>
                                            </div>

                                            <div v-if="editingId !== comment.id && canModify(comment)" class="flex items-center gap-3 mt-1.5">
                                                <button v-if="canEdit(comment)" @click="startEdit(comment)"
                                                    class="text-xs text-gray-400 hover:text-primary transition-colors flex items-center gap-1">
                                                    <i class="ti ti-pencil text-[11px]"></i> Edit
                                                </button>
                                                <button @click="deleteComment(comment)"
                                                    class="text-xs text-gray-400 hover:text-danger transition-colors flex items-center gap-1">
                                                    <i class="ti ti-trash text-[11px]"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div class="mt-6 pt-5 border-t border-gray-100">
                                <div class="flex gap-3">
                                    <span class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-semibold shrink-0 mt-1">
                                        {{ currentUserInitials }}
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <textarea v-model="newComment"
                                            placeholder="Write a comment..."
                                            class="form-control text-sm w-full"
                                            rows="2"
                                            @keydown.ctrl.enter="submitComment"
                                        ></textarea>
                                        <div class="flex items-center justify-between mt-2">
                                            <span class="text-xs text-gray-400">Ctrl+Enter to send</span>
                                            <button @click="submitComment"
                                                class="btn btn-primary btn-sm"
                                                :disabled="!newComment.trim() || submitting"
                                            >
                                                <template v-if="submitting">
                                                    <svg class="animate-spin h-3.5 w-3.5 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                                    </svg>
                                                    Sending
                                                </template>
                                                <template v-else>
                                                    <i class="ti ti-send text-sm"></i> Comment
                                                </template>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 pb-6 flex items-center justify-end">
                            <button @click="$emit('close')" class="btn btn-light btn-sm">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
import { useFlashStore } from '../stores/flash'
const props = defineProps({
    show: { type: Boolean, default: false },
    task: { type: Object, default: null },
    ownerId: { type: Number, default: null },
})

const emit = defineEmits(['close'])
const auth = useAuthStore()
const flash = useFlashStore()

const comments = ref([])
const commentsLoading = ref(false)
const loadingOlder = ref(false)
const currentPage = ref(1)
const lastPage = ref(null)
const totalComments = ref(0)
const initialCount = ref(0)

const PER_PAGE = 5

const newComment = ref('')
const submitting = ref(false)

const editingId = ref(null)
const editBody = ref('')

const hasOlder = computed(() => lastPage.value && currentPage.value < lastPage.value)
const hasLoadedOlder = computed(() => comments.value.length > initialCount.value)

const currentUserInitials = computed(() => {
    if (!auth.user?.name) return '?'
    return auth.user.name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
})

watch(() => props.show, async (val) => {
    document.body.style.overflow = val ? 'hidden' : ''
    if (val && props.task?.id) {
        await fetchComments()
    } else if (!val) {
        resetComments()
    }
})

function resetComments() {
    comments.value = []
    currentPage.value = 1
    lastPage.value = null
    totalComments.value = 0
    initialCount.value = 0
    newComment.value = ''
    editingId.value = null
    editBody.value = ''
}

async function fetchComments() {
    if (!props.task?.id) return
    commentsLoading.value = true
    try {
        const { data } = await api.get(`/tasks/${props.task.id}/comments?page=1`)
        comments.value = data.data.reverse()
        currentPage.value = data.meta.pagination.current_page
        lastPage.value = data.meta.pagination.last_page
        totalComments.value = data.meta.pagination.total
        initialCount.value = data.data.length
    } catch {
        flash.error('Failed to load comments.')
    } finally {
        commentsLoading.value = false
    }
}

async function loadOlder() {
    if (currentPage.value >= lastPage.value) return
    loadingOlder.value = true
    try {
        const { data } = await api.get(`/tasks/${props.task.id}/comments?page=${currentPage.value + 1}`)
        const older = data.data.reverse()
        const existingIds = new Set(comments.value.map(c => c.id))
        const deduped = older.filter(c => !existingIds.has(c.id))
        comments.value = [...deduped, ...comments.value]
        currentPage.value++
    } catch {
        flash.error('Failed to load comments.')
    } finally {
        loadingOlder.value = false
    }
}

function showLess() {
    comments.value = comments.value.slice(-initialCount.value)
    currentPage.value = 1
}

async function submitComment() {
    if (!newComment.value.trim() || submitting.value) return
    submitting.value = true
    try {
        const { data } = await api.post(`/tasks/${props.task.id}/comments`, { body: newComment.value })
        comments.value = [...comments.value, data.data]
        newComment.value = ''
        totalComments.value++
        initialCount.value++
        lastPage.value = Math.ceil(totalComments.value / PER_PAGE)
    } catch (err) {
        if (err.response?.status === 422) {
            flash.error(err.response.data?.errors?.body?.[0] ?? 'Validation failed.')
        } else {
            flash.error('Failed to post comment.')
        }
    } finally {
        submitting.value = false
    }
}

function startEdit(comment) {
    editingId.value = comment.id
    editBody.value = comment.body
}

function cancelEdit() {
    editingId.value = null
    editBody.value = ''
}

async function saveEdit(comment) {
    if (!editBody.value.trim()) return
    try {
        const { data } = await api.put(`/comments/${comment.id}`, { body: editBody.value })
        const idx = comments.value.findIndex(c => c.id === comment.id)
        if (idx !== -1) {
            comments.value[idx] = data.data
        }
        editingId.value = null
        editBody.value = ''
    } catch {
        flash.error('Failed to update comment.')
    }
}

async function deleteComment(comment) {
    try {
        await api.delete(`/comments/${comment.id}`)
        const idx = comments.value.findIndex(c => c.id === comment.id)
        const len = comments.value.length
        comments.value = comments.value.filter(c => c.id !== comment.id)
        totalComments.value--
        if (idx !== -1 && idx >= len - initialCount.value) {
            initialCount.value = Math.max(0, initialCount.value - 1)
        }
        lastPage.value = Math.max(1, Math.ceil(totalComments.value / PER_PAGE))
    } catch {
        flash.error('Failed to delete comment.')
    }
}

function canEdit(comment) {
    return auth.user?.id === comment.author?.id
}

function canModify(comment) {
    return auth.user?.id === comment.author?.id || auth.user?.id === props.ownerId
}

function initials(name) {
    if (!name) return '?'
    return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
}

function priorityColor(priority) {
    return { low: '#00B8DB', medium: '#E66239', high: '#F0B100', urgent: '#FB2C36' }[priority] ?? '#E66239'
}

function statusBadge(status) {
    return {
        open: 'badge-info',
        in_progress: 'badge-primary',
        in_review: 'badge-warning',
        done: 'badge-success',
        cancelled: 'badge-danger',
    }[status] ?? 'badge-info'
}

function priorityBadge(priority) {
    return {
        low: 'badge-info',
        medium: 'badge-primary',
        high: 'badge-warning',
        urgent: 'badge-danger',
    }[priority] ?? 'badge-primary'
}

function timeAgo(iso) {
    if (!iso) return ''
    const diff = Date.now() - new Date(iso).getTime()
    const mins = Math.floor(diff / 60000)
    if (mins < 1) return 'just now'
    if (mins < 60) return `${mins}m ago`
    const hours = Math.floor(mins / 60)
    if (hours < 24) return `${hours}h ago`
    const days = Math.floor(hours / 24)
    if (days < 30) return `${days}d ago`
    const months = Math.floor(days / 30)
    if (months < 12) return `${months}mo ago`
    return `${Math.floor(months / 12)}y ago`
}

function formatDate(iso) {
    if (!iso) return '—'
    const d = new Date(iso)
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
