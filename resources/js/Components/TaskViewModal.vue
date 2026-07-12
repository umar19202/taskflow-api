<template>
    <Teleport to="body">
        <div v-if="show" class="overlay show" @click.self="$emit('close')">
            <div class="fixed inset-0 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto" @click.stop
                    :style="{ borderLeft: '4px solid ' + priorityColor(task.priority) }">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full" :style="{ background: priorityColor(task.priority) }"></span>
                            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Task</h3>
                        </div>
                        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 text-lg leading-none p-1 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>

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
                                    <i class="ti ti-message text-gray-500 text-sm"></i>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-400 font-medium">Comments</span>
                                    <p class="mt-0.5 text-sm font-medium text-gray-800">{{ task.comments_count ?? 0 }}</p>
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

                    <div class="px-6 pb-5 flex items-center justify-between">
                        <span class="text-xs text-gray-400">Created {{ formatDate(task.created_at) }}</span>
                        <button @click="$emit('close')" class="btn btn-light btn-sm">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
defineProps({
    show: { type: Boolean, default: false },
    task: { type: Object, default: null },
})

defineEmits(['close'])

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

function formatDate(iso) {
    if (!iso) return '—'
    const d = new Date(iso)
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
