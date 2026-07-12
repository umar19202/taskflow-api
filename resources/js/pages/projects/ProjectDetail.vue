<template>
    <AppLayout>
        <div v-if="loading" class="flex justify-center py-8">
            <Spinner size="lg" color="#E66239" />
        </div>

        <template v-else-if="project">
            <div class="mb-6">
                <router-link to="/projects" class="btn btn-light btn-sm mb-4">
                    <i class="ti ti-arrow-left"></i>
                    Back to Projects
                </router-link>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h1 class="text-2xl font-semibold">{{ project.name }}</h1>
                                <p v-if="project.description" class="text-gray-500 mt-2 leading-relaxed">{{ project.description }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="badge" :class="project.status === 'active' ? 'badge-success' : 'badge-warning'">
                                    {{ project.status }}
                                </span>
                                <router-link :to="`/projects/${project.id}/edit`" class="btn btn-light btn-icon btn-sm">
                                    <i class="ti ti-pencil"></i>
                                </router-link>
                            </div>
                        </div>

                        <div class="flex gap-6 mt-5 pt-5 border-t border-gray-100">
                            <div class="flex items-center gap-2 text-sm">
                                <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                                    <i class="ti ti-list-check text-primary text-sm"></i>
                                </span>
                                <div>
                                    <span class="text-gray-400">Total tasks</span>
                                    <p class="font-semibold text-gray-800 -mt-0.5">{{ project.total_tasks }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <span class="w-8 h-8 rounded-lg bg-success/10 flex items-center justify-center">
                                    <i class="ti ti-player-play text-success text-sm"></i>
                                </span>
                                <div>
                                    <span class="text-gray-400">Active</span>
                                    <p class="font-semibold text-gray-800 -mt-0.5">{{ project.active_tasks }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="text-base font-medium mb-0">Tasks</h4>
                    <button @click="showTaskForm = true" class="btn btn-primary btn-sm">
                        <i class="ti ti-plus text-sm"></i>
                        New Task
                    </button>
                </div>

                <div class="px-6 py-3 border-b border-gray-100 bg-gray-50/50">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative">
                            <i class="ti ti-checklist text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2 text-sm pointer-events-none"></i>
                            <select v-model="filters.status" class="form-select text-xs py-1.5 pl-8 pr-7 min-w-[140px] appearance-none">
                                <option value="">All Statuses</option>
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="in_review">In Review</option>
                                <option value="done">Done</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <i class="ti ti-chevron-down text-gray-400 absolute right-2 top-1/2 -translate-y-1/2 text-[10px] pointer-events-none"></i>
                        </div>

                        <div class="relative">
                            <i class="ti ti-flag text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2 text-sm pointer-events-none"></i>
                            <select v-model="filters.priority" class="form-select text-xs py-1.5 pl-8 pr-7 min-w-[140px] appearance-none">
                                <option value="">All Priorities</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                            <i class="ti ti-chevron-down text-gray-400 absolute right-2 top-1/2 -translate-y-1/2 text-[10px] pointer-events-none"></i>
                        </div>

                        <div class="relative">
                            <i class="ti ti-user text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2 text-sm pointer-events-none"></i>
                            <select v-model="filters.assigned_to" class="form-select text-xs py-1.5 pl-8 pr-7 min-w-[150px] appearance-none">
                                <option value="">All Assignees</option>
                                <option value="unassigned">Unassigned</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>
                            <i class="ti ti-chevron-down text-gray-400 absolute right-2 top-1/2 -translate-y-1/2 text-[10px] pointer-events-none"></i>
                        </div>

                        <div class="h-6 w-px bg-gray-200 mx-1"></div>

                        <button @click="filters.overdue = filters.overdue ? '' : '1'"
                            class="flex items-center gap-2 text-xs px-3 py-1.5 rounded-lg transition-colors"
                            :class="filters.overdue ? 'bg-danger/10 text-danger font-medium' : 'text-gray-500 hover:bg-gray-100'">
                            <span class="relative inline-block w-8 h-4 rounded-full transition-colors"
                                :class="filters.overdue ? 'bg-danger' : 'bg-gray-300'">
                                <span class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full shadow transition-transform"
                                    :class="filters.overdue ? 'translate-x-4' : ''">
                                </span>
                            </span>
                            Overdue
                        </button>

                        <div v-if="hasActiveFilters" class="flex items-center gap-2 ml-auto">
                            <span class="text-xs bg-primary/10 text-primary font-medium px-2 py-0.5 rounded-full">
                                {{ activeFilterCount }} active
                            </span>
                            <button @click="clearFilters"
                                class="text-xs text-gray-400 hover:text-danger flex items-center gap-1 transition-colors">
                                <i class="ti ti-x"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="fetching" class="flex justify-center py-8">
                    <Spinner size="md" color="#E66239" />
                </div>

                <template v-else-if="tasks.length === 0">
                    <div class="text-gray-400 text-sm py-12 text-center">
                        <i class="ti ti-search text-2xl block mb-2 opacity-50"></i>
                        <template v-if="hasActiveFilters">No tasks match the applied filters.</template>
                        <template v-else>No tasks yet. Create one to get started.</template>
                    </div>
                </template>

                <div v-else class="table-wrap">
                    <table class="inv-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Assignee</th>
                            <th>Due Date</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="task in tasks" :key="task.id">
                            <td class="font-medium">{{ task.title }}</td>
                            <td>
                                <span class="badge" :class="statusBadge(task.status.value)">
                                    {{ task.status.label }}
                                </span>
                            </td>
                            <td>
                                <span class="badge" :class="priorityBadge(task.priority)">
                                    {{ task.priority }}
                                </span>
                            </td>
                            <td class="text-gray-500">
                                <div v-if="task.assignee" class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-semibold shrink-0">
                                        {{ initials(task.assignee.name) }}
                                    </span>
                                    <span>{{ task.assignee.name }}</span>
                                </div>
                                <span v-else class="text-gray-400">Unassigned</span>
                            </td>
                            <td>
                                <span :class="{ 'text-danger font-medium': task.is_overdue, 'text-gray-500': !task.is_overdue }">
                                    <template v-if="task.due_date">
                                        {{ task.due_date }}
                                        <span v-if="task.is_overdue" class="text-xs ml-1">(Overdue)</span>
                                    </template>
                                    <span v-else class="text-gray-400">—</span>
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button @click="viewTask(task)" class="btn btn-light btn-icon btn-sm">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                    <button @click="editTask(task)" class="btn btn-light btn-icon btn-sm">
                                        <i class="ti ti-pencil"></i>
                                    </button>
                                    <button @click="confirmDeleteTask(task)" class="btn btn-light btn-icon btn-sm">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    </div>

                <Pagination v-if="taskLinks" :links="taskLinks" @page-change="fetchTasks" />
            </div>
        </template>

        <TaskFormModal
            :show="showTaskForm"
            :project-id="Number(route.params.id)"
            :task="selectedTask"
            @close="showTaskForm = false; selectedTask = null"
            @saved="fetchTasks"
        />

        <TaskViewModal
            :show="showTaskView"
            :task="selectedTask"
            @close="showTaskView = false; selectedTask = null"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppLayout from '../../Layouts/AppLayout.vue'
import Pagination from '../../Components/Pagination.vue'
import Spinner from '../../Components/Spinner.vue'
import api from '../../services/api'
import { useFlashStore } from '../../stores/flash'
import { useConfirm } from '../../composables/useConfirm'
import TaskFormModal from '../../Components/TaskFormModal.vue'
import TaskViewModal from '../../Components/TaskViewModal.vue'

const route = useRoute()
const flash = useFlashStore()
const { confirm } = useConfirm()
const project = ref(null)
const showTaskForm = ref(false)
const showTaskView = ref(false)
const selectedTask = ref(null)
const tasks = ref([])
const taskLinks = ref(null)
const loading = ref(true)
const fetching = ref(false)
const users = ref([])

const filters = ref({
    status: '',
    priority: '',
    assigned_to: '',
    overdue: '',
})

const hasActiveFilters = computed(() =>
    filters.value.status || filters.value.priority || filters.value.assigned_to || filters.value.overdue
)

const activeFilterCount = computed(() =>
    [filters.value.status, filters.value.priority, filters.value.assigned_to, filters.value.overdue].filter(Boolean).length
)

function initials(name) {
    if (!name) return '?'
    return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
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

async function fetchTasks(page = 1) {
    fetching.value = true
    try {
        const params = new URLSearchParams({ page })
        Object.entries(filters.value).forEach(([k, v]) => {
            if (v) params.set(k, v)
        })
        const { data } = await api.get(`/projects/${route.params.id}/tasks?${params}`)
        tasks.value = data.data
        taskLinks.value = data.meta.pagination
    } finally {
        fetching.value = false
    }
}

function clearFilters() {
    filters.value = { status: '', priority: '', assigned_to: '', overdue: '' }
}

watch(filters, () => fetchTasks(1), { deep: true })

function viewTask(task) {
    selectedTask.value = task
    showTaskView.value = true
}

function editTask(task) {
    selectedTask.value = task
    showTaskForm.value = true
}

async function confirmDeleteTask(task) {
    const ok = await confirm({
        title: 'Delete Task',
        message: `Are you sure you want to delete "${task.title}"? This action cannot be undone.`,
        danger: true,
    })

    if (!ok) return

    try {
        await api.delete(`/tasks/${task.id}`)
        flash.success('Task deleted successfully.')
        await fetchTasks()
    } catch {
        flash.error('Failed to delete task.')
    }
}

onMounted(async () => {
    try {
        const [projectRes, usersRes] = await Promise.all([
            api.get(`/projects/${route.params.id}`),
            api.get('/users'),
        ])
        project.value = projectRes.data.data
        users.value = usersRes.data.data
        await fetchTasks()
    } finally {
        loading.value = false
    }
})
</script>
