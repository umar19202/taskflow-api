<template>
    <AppLayout>
        <div v-if="loading" class="flex justify-center py-8">
            <Spinner size="lg" color="#E66239" />
        </div>

        <template v-else-if="project">
            <div class="mb-6">
                <router-link to="/projects" class="text-sm text-gray-500 hover:text-primary inline-flex items-center gap-1 mb-2">
                    <i class="ti ti-arrow-left"></i>
                    Back to Projects
                </router-link>

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-normal mb-1">{{ project.name }}</h1>
                        <p v-if="project.description" class="text-gray-500">{{ project.description }}</p>
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

                <div class="flex gap-4 mt-4">
                    <div class="text-sm">
                        <span class="text-gray-400">Total tasks:</span>
                        <span class="font-medium ml-1">{{ project.total_tasks }}</span>
                    </div>
                    <div class="text-sm">
                        <span class="text-gray-400">Active:</span>
                        <span class="font-medium ml-1">{{ project.active_tasks }}</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="text-base font-medium mb-0">Tasks</h4>
                    <button class="btn btn-primary btn-sm">
                        <i class="ti ti-plus text-sm"></i>
                        New Task
                    </button>
                </div>

                <div v-if="tasks.length === 0" class="text-gray-400 text-sm py-8 text-center">
                    No tasks yet.
                </div>

                <table v-else class="inv-table">
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
                            <td class="text-gray-500">{{ task.assignee?.name ?? 'Unassigned' }}</td>
                            <td class="text-gray-500">
                                <span :class="{ 'text-danger font-medium': task.is_overdue }">
                                    {{ task.due_date ?? '—' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button class="btn btn-light btn-icon btn-sm">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                    <button class="btn btn-light btn-icon btn-sm">
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

                <Pagination v-if="taskLinks" :links="taskLinks" @page-change="fetchTasks" />
            </div>
        </template>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppLayout from '../../Layouts/AppLayout.vue'
import Pagination from '../../Components/Pagination.vue'
import Spinner from '../../Components/Spinner.vue'
import api from '../../services/api'
import { useFlashStore } from '../../stores/flash'
import { useConfirm } from '../../composables/useConfirm'

const route = useRoute()
const flash = useFlashStore()
const { confirm } = useConfirm()
const project = ref(null)
const tasks = ref([])
const taskLinks = ref(null)
const loading = ref(true)

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
    const { data } = await api.get(`/projects/${route.params.id}/tasks?page=${page}`)
    tasks.value = data.data
    taskLinks.value = data.meta.pagination
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
        const { data: projectRes } = await api.get(`/projects/${route.params.id}`)
        project.value = projectRes.data
        await fetchTasks()
    } finally {
        loading.value = false
    }
})
</script>
