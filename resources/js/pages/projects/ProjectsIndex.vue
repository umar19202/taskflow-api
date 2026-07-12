<template>
    <AppLayout>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-normal mb-1">Projects</h1>
                <p class="text-gray-500">Manage your workspaces.</p>
            </div>
            <router-link to="/projects/create" class="btn btn-primary btn-sm">
                <i class="ti ti-plus text-sm"></i>
                New Project
            </router-link>
        </div>

        <div v-if="loading" class="flex justify-center py-8">
            <Spinner size="lg" color="#E66239" />
        </div>

        <div v-else-if="projects.length === 0" class="text-gray-400 text-sm py-8 text-center">
            No projects yet.
            <router-link to="/projects/create" class="text-primary hover:underline ml-1">Create your first project</router-link>
        </div>

        <div v-else class="card">
            <table class="inv-table">
                <thead>
                    <tr>
                        <th class="w-10">#</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Tasks</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(project, index) in projects" :key="project.id">
                        <td class="text-gray-400 text-xs">{{ rowNumber(index) }}</td>
                        <td>
                            <router-link :to="`/projects/${project.id}`" class="font-medium hover:text-primary">
                                {{ project.name }}
                            </router-link>
                            <p v-if="project.description" class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">
                                {{ project.description }}
                            </p>
                        </td>
                        <td>
                            <span class="badge" :class="project.status === 'active' ? 'badge-success' : 'badge-warning'">
                                {{ project.status }}
                            </span>
                        </td>
                        <td class="text-gray-500">
                            <span>{{ project.active_tasks }}</span>
                            <span class="text-gray-300">/</span>
                            <span>{{ project.total_tasks }}</span>
                        </td>
                        <td class="text-right">
                            <div class="inline-flex items-center gap-1">
                                <router-link :to="`/projects/${project.id}`" class="btn btn-light btn-icon btn-sm">
                                    <i class="ti ti-eye"></i>
                                </router-link>
                                <router-link :to="`/projects/${project.id}/edit`" class="btn btn-light btn-icon btn-sm">
                                    <i class="ti ti-pencil"></i>
                                </router-link>
                                <button @click="confirmDelete(project)" class="btn btn-light btn-icon btn-sm">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <Pagination v-if="links" :links="links" @page-change="fetchProjects" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppLayout from '../../Layouts/AppLayout.vue'
import Pagination from '../../Components/Pagination.vue'
import Spinner from '../../Components/Spinner.vue'
import api from '../../services/api'
import { useFlashStore } from '../../stores/flash'
import { useConfirm } from '../../composables/useConfirm'

const projects = ref([])
const links = ref(null)
const loading = ref(true)
const currentPage = ref(1)

const flash = useFlashStore()
const { confirm } = useConfirm()

function rowNumber(index) {
    return (currentPage.value - 1) * (links.value?.per_page ?? 2) + index + 1
}

async function fetchProjects(page = 1) {
    loading.value = true
    currentPage.value = page
    try {
        const { data } = await api.get(`/projects?page=${page}`)
        projects.value = data.data
        links.value = data.meta.pagination
    } finally {
        loading.value = false
    }
}

async function confirmDelete(project) {
    const ok = await confirm({
        title: 'Delete Project',
        message: `Are you sure you want to delete "${project.name}"? This action cannot be undone.`,
        danger: true,
    })

    if (!ok) return

    try {
        await api.delete(`/projects/${project.id}`)
        flash.success('Project deleted successfully.')
        await fetchProjects()
    } catch {
        flash.error('Failed to delete project.')
    }
}

onMounted(() => fetchProjects())
</script>
